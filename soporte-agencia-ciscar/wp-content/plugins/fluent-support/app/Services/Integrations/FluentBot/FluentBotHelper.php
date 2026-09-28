<?php
namespace FluentSupport\App\Services\Integrations\FluentBot;

use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Services\Integrations\FluentBot\FluentBotAPI;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\App\Services\Helper;
use WP_Error;
class FluentBotHelper
{
    const BASE_URL = 'https://dash.fluentbot.ai/api';

    const ENDPOINTS = [
        'default' => '/responses',
        'ticket_reply' => '/chat/fs',
    ];

    /**
     * Resolve the FluentBot API base URL. Defaults to production; overridable
     * for local/staging via the `FLUENTBOT_API_BASE_URL` constant (wp-config)
     * or the `fluent_support/fluentbot_api_base_url` filter.
     */
    private function apiBaseUrl(): string
    {
        if (defined('FLUENTBOT_API_BASE_URL') && FLUENTBOT_API_BASE_URL) {
            return rtrim((string) FLUENTBOT_API_BASE_URL, '/');
        }

        return rtrim((string) apply_filters('fluent_support/fluentbot_api_base_url', static::BASE_URL), '/');
    }

    public function generateStreamResponse($prompt, $ticket, $productId, $conversationId = null, $selectedConversations = null, $includeTicketContent = true, $seedMessages = null, $webSearch = false, $temperature = 0)
    {
        $prompt = apply_filters('fluent_support/generate_response', $prompt, $ticket);

        $ticketMessages = [];
        if ($selectedConversations !== null) {
            $ticketMessages = $this->getSelectedTicketMessages($ticket, $selectedConversations, $includeTicketContent);
        } else {
            $ticketMessages = $this->getTicketMessages($ticket, $includeTicketContent);
        }

        $payload = [
            'source' => 'fluent_support',
            'prompt' => $prompt,
            'stream' => true,
            'chat_id' => $conversationId ?? null,
            'enable_web_search' => (bool) $webSearch,
            'temperature' => (float) $temperature,
        ];

        if (!empty($ticketMessages)) {
            $payload['ticket_conversation'] = $ticketMessages;
        }

        if (!empty($seedMessages)) {
            $payload['seed_messages'] = $seedMessages;
        }

        return $this->makeStreamAPICall($payload, $prompt, $ticket->id, 'ticket_reply', $productId);
    }

    public function modifyResponse($prompt, $selectedText, $ticketId)
    {
        $prompt = apply_filters('fluent_support/modify_selected_text', $prompt);
        $payload = [
            'message' => "Instruction: {$prompt} Now apply this to the given text: {$selectedText}",
        ];

        return $this->makeAPICall($payload, $prompt, $ticketId);
    }

    public function generateTicketSummary($ticket)
    {
        $prompt = 'Provide a summary of the ticket from the customer\'s perspective. Each step should start with "-". Break it down into concise steps, with a maximum of 6 steps. Each step should be within 6 words per line. Use full stops for separation.';
        $prompt = apply_filters('fluent_support/generate_ticket_summary', $prompt);

        $messages = $this->getSimpleTicketMessages($ticket);
        $payload = [
            'message' => "Instruction: {$prompt} Ticket Data: " . json_encode($messages),
        ];

        return $this->makeAPICall($payload, $prompt, $ticket->id);
    }

    public function generateTicketTone($ticket)
    {
        $prompt = 'What is the tone of this ticket? Is it positive, negative, or neutral? Provide a response with a single word.';
        $prompt = apply_filters('fluent_support/find_customer_sentiment', $prompt);

        $messages = $this->getSimpleTicketMessages($ticket);
        $payload = [
            'message' => "Instruction: {$prompt} Ticket Data: " . json_encode($messages),
        ];

        return $this->makeAPICall($payload, $prompt, $ticket->id);
    }

    public function getPresetPrompts(string $type): array
    {
        if ($type === 'modifyResponse') {
            return $this->getModifyResponsePresets();
        }

        if ($type === 'createResponse') {
            $customPresets = $this->getCustomPresets();
            if (!empty($customPresets)) {
                return $customPresets;
            }
            return $this->getCreateResponsePresets();
        }

        return [];
    }

    public function getCustomPresets(): array
    {
        $meta = Meta::where([
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_presets'
        ])->orderByDesc('id')->first();

        if (!$meta) {
            return [];
        }

        $presets = Helper::safeUnserialize($meta->value);

        return is_array($presets) ? $presets : [];
    }

    public function saveCustomPresets(array $presets): array
    {
        $sanitized = [];
        foreach ($presets as $index => $preset) {
            if (empty($preset['label']) || empty($preset['description'])) {
                continue;
            }
            $sanitized[] = [
                'label'       => sanitize_text_field($preset['label']),
                'text'        => sanitize_text_field($preset['text'] ?? 'preset_' . $index),
                'description' => sanitize_textarea_field($preset['description']),
                'position'    => intval($preset['position'] ?? $index),
            ];
        }

        usort($sanitized, function ($a, $b) {
            return $a['position'] - $b['position'];
        });

        $where = [
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_presets'
        ];

        $existing = Meta::where($where)->orderByDesc('id')->first();

        if (empty($sanitized)) {
            if ($existing) {
                // Delete all rows for this key (including duplicates)
                Meta::where($where)->delete();
            }
            return [];
        }

        $serialized = maybe_serialize($sanitized);

        if ($existing) {
            // Update the latest row; do not prune siblings — concurrent first-writes could
            // race and delete each other's inserts, leaving zero rows (data loss).
            // Reads use orderByDesc('id')->first() so duplicates are harmless at read time.
            $existing->update(['value' => $serialized]);
        } else {
            Meta::create(array_merge($where, ['value' => $serialized]));
        }

        return $sanitized;
    }

    private function makeAPICall(array $payload, string $prompt, int $ticketId, string $type = 'default', $productId = null )
    {
        $apiUrl = $this->apiBaseUrl() . static::ENDPOINTS[$type];

        $credentials = $this->resolveApiCredentials($productId);

        if (is_wp_error($credentials)) {
            return $credentials;
        }

        // Use bot_id instead of botId for the new API
        $payload['bot_id'] = $credentials['botId'];

        $api = new FluentBotAPI($apiUrl, $credentials['apiKey']);
        $result = $api->makeRequest($ticketId, $prompt, $payload);

        // For ticket_reply endpoint, return the full result with chat_id
        // For other endpoints, return just the content for backward compatibility
        if ($type === 'ticket_reply' && is_array($result) && isset($result['content'])) {
            return $result;
        } elseif (is_array($result) && isset($result['content'])) {
            return $result['content'];
        }

        return $result;
    }

    private function makeStreamAPICall(array $payload, string $prompt, int $ticketId, string $type = 'default', $productId = null)
    {
        $apiUrl = $this->apiBaseUrl() . static::ENDPOINTS[$type];

        $credentials = $this->resolveApiCredentials($productId);

        if (is_wp_error($credentials)) {
            // Emit as an SSE `error` event (not a bare `data:` frame) so the client
            // shows a proper error state instead of rendering the JSON as AI text.
            echo "event: error\n";
            echo "data: " . json_encode(['error' => $credentials->get_error_message()]) . "\n\n";
            return;
        }

        // Use bot_id instead of botId for the new API
        $payload['bot_id'] = $credentials['botId'];

        $api = new FluentBotAPI($apiUrl, $credentials['apiKey']);
        $api->makeStreamRequest($ticketId, $prompt, $payload);
    }

    public function getChatMessages($chatId, $productId = null, $cursor = null)
    {
        $credentials = $this->resolveApiCredentials($productId);

        if (is_wp_error($credentials)) {
            return $credentials;
        }

        $botId = $credentials['botId'];
        $url = $this->apiBaseUrl() . '/bots/' . $botId . '/chats/' . $chatId . '/messages';

        if ($cursor) {
            $url .= '?cursor=' . urlencode($cursor);
        }

        $response = wp_remote_get($url, [
            'headers' => $this->requestHeaders($credentials['apiKey']),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true) ?: [];

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return new \WP_Error(
                'fluent_bot_messages_error',
                $body['message'] ?? __('Failed to fetch chat messages.', 'fluent-support')
            );
        }

        return $body;
    }

    public function createFeedback($messageId, $reaction, $comment, $productId = null, $chatId = null)
    {
        $credentials = $this->resolveApiCredentials($productId);

        if (is_wp_error($credentials)) {
            return $credentials;
        }

        $payload = [
            'bot_id'     => $credentials['botId'],
            'message_id' => $messageId,
            'reaction'   => $reaction,
            'comments'   => $comment,
        ];

        // Bind feedback to the ticket's chat so upstream can enforce message-to-chat ownership.
        if (!empty($chatId)) {
            $payload['chat_id'] = $chatId;
        }

        $response = wp_remote_post($this->apiBaseUrl() . '/feedbacks', [
            'headers' => $this->requestHeaders($credentials['apiKey']),
            'body'    => wp_json_encode($payload),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true) ?: [];
        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 400) {
            return new \WP_Error('feedback_error', $body['message'] ?? __('Failed to save feedback.', 'fluent-support'));
        }

        return $body;
    }

    public function deleteFeedback($feedbackId, $productId = null, $chatId = null)
    {
        $credentials = $this->resolveApiCredentials($productId);

        if (is_wp_error($credentials)) {
            return $credentials;
        }

        $payload = [
            'bot_id' => $credentials['botId'],
        ];

        // Bind delete to the ticket's chat so upstream can enforce feedback-to-chat ownership.
        if (!empty($chatId)) {
            $payload['chat_id'] = $chatId;
        }

        $response = wp_remote_request($this->apiBaseUrl() . '/feedbacks/' . $feedbackId, [
            'method'  => 'DELETE',
            'headers' => $this->requestHeaders($credentials['apiKey']),
            'body'    => wp_json_encode($payload),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true) ?: [];
        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 400) {
            return new \WP_Error('feedback_error', $body['message'] ?? __('Failed to delete feedback.', 'fluent-support'));
        }

        return $body;
    }

    private function resolveApiCredentials($productId)
    {
        $meta = Meta::where([
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_config'
        ])->orderByDesc('id')->first();

        $config = $meta ? Helper::safeUnserialize($meta->value) : [];
        if (!is_array($config)) {
            $config = [];
        }

        // Default true for backward compatibility with configs saved before this flag existed.
        $generalBotEnabled = !array_key_exists('generalBotEnabled', $config)
            || filter_var($config['generalBotEnabled'], FILTER_VALIDATE_BOOLEAN);

        $generalBotId = $config['generalBotId'] ?? '';
        $botId = $generalBotEnabled ? $generalBotId : '';
        $matchedProductMapping = false;

        if ($productId && !empty($config['productMappings']) && is_array($config['productMappings'])) {
            foreach ($config['productMappings'] as $mapping) {
                if ((int)$mapping['productId'] === (int)$productId) {
                    $mappingBotId = trim((string)($mapping['botId'] ?? ''));
                    if ($mappingBotId !== '') {
                        $botId = $mappingBotId;
                        $matchedProductMapping = true;
                    }
                    break;
                }
            }
        }

        if (!$botId) {
            // Distinguish the "general bot disabled with no product mapping" case so admins
            // see a clear reason rather than a generic missing-credentials error.
            if (!$matchedProductMapping && !$generalBotEnabled) {
                return new \WP_Error(
                    'general_bot_disabled',
                    __('General bot is disabled and no product-specific bot is configured for this product.', 'fluent-support')
                );
            }
            return new \WP_Error(
                'missing_bot_credentials',
                __('Bot ID is not set for this product.', 'fluent-support')
            );
        }

        // The FluentBot API is team-scoped: a single API key authenticates every
        // bot in the team, so one general key covers both the general and any
        // product-specific bot. It is now required — the API rejects anonymous
        // calls — so surface a clear config error instead of a raw 401.
        $apiKey = trim((string)($config['generalApiKey'] ?? ''));
        if ($apiKey === '') {
            return new \WP_Error(
                'missing_api_key',
                __('FluentBot API key is not set. Add it in the FluentBot integration settings.', 'fluent-support')
            );
        }

        return [
            'botId'  => $botId,
            'apiKey' => $apiKey,
        ];
    }

    /**
     * Build the outbound request headers, attaching the team API key as a
     * Bearer token so upstream can authenticate + team-scope the call.
     */
    private function requestHeaders(string $apiKey): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if ($apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
        }

        return $headers;
    }

    private function getTicketMessages($ticket, $includeTicketContent = true): array
    {
        $messages = [];
        $ticketArray = $ticket->toArray();

        if ($includeTicketContent && !empty($ticketArray['content'])) {
            $messages[] = [
                'role' => 'customer',
                'message' => $this->cleanText($ticketArray['content']),
            ];
        }

        foreach (Arr::get($ticketArray, 'responses', []) as $response) {
            $role = Arr::get($response, 'person.person_type') === 'customer' ? 'customer' : 'support_agent';
            $messages[] = [
                'role' => $role,
                'message' => $this->cleanText(Arr::get($response, 'content', '')),
            ];
        }

        return $messages;
    }

    private function getSelectedTicketMessages($ticket, array $selectedIds, $includeTicketContent = true): array
    {
        $messages = [];

        if ($includeTicketContent && !empty($ticket->content)) {
            $messages[] = [
                'role' => 'customer',
                'message' => $this->cleanText($ticket->content),
            ];
        }

        $conversationIds = array_map('intval', array_filter($selectedIds, 'is_numeric'));

        if (!empty($conversationIds)) {
            $responses = Conversation::where('ticket_id', $ticket->id)
                ->whereIn('id', $conversationIds)
                ->where('conversation_type', 'response')
                ->with('person:id,person_type')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($responses as $resp) {
                $role = ($resp->person && $resp->person->person_type === 'customer') ? 'customer' : 'support_agent';
                $messages[] = [
                    'role' => $role,
                    'message' => $this->cleanText($resp->content ?? ''),
                ];
            }
        }

        return $messages;
    }

    private function getSimpleTicketMessages($ticket): array
    {
        $messages = [];
        $ticketArray = $ticket->toArray();

        if (!empty($ticketArray['content'])) {
            $messages[] = [
                'role' => 'visitor',
                'message' => $this->cleanText($ticketArray['content']),
            ];
        }

        foreach (Arr::get($ticketArray, 'responses', []) as $response) {
            $role = Arr::get($response, 'person.person_type') === 'customer' ? 'visitor' : 'ai';
            $messages[] = [
                'role' => $role,
                'message' => $this->cleanText(Arr::get($response, 'content', '')),
            ];
        }

        return $messages;
    }



    private function cleanText(string $text): string
    {
        return trim(wp_strip_all_tags($text));
    }

    private function getModifyResponsePresets(): array
    {
        $presets = [
            [
                'label' => 'Improve Writing',
                'text' => 'shorten',
                'description' => 'Use AI to refine the text by removing unnecessary words and making it more concise while retaining the original meaning and key information.'
            ],
            [
                'label' => 'Fix Spelling & Grammar',
                'text' => 'lengthen',
                'description' => 'Apply AI to correct any spelling and grammatical errors in the text, ensuring it is free of mistakes and reads professionally.'
            ],
            [
                'label' => 'Make Shorter',
                'text' => 'friendly',
                'description' => 'AI will modify the text to make it shorter and more casual, making it suitable for informal or friendly communication.'
            ],
            [
                'label' => 'Make Longer',
                'text' => 'professional',
                'description' => 'Enhance the text by adding more details and using refined language to make it more formal and detailed, appropriate for professional settings.'
            ],
            [
                'label' => 'Simplify Language',
                'text' => 'simplify',
                'description' => 'Utilize AI to simplify complex phrases and terminology, making the text easier to read and understand for a general audience.'
            ]
        ];

        return apply_filters('fluent_support/get_modify_response_preset_prompts', $presets);
    }

    private function getCreateResponsePresets(): array
    {
        $presets = [
            [
                'label' => 'Request More Information',
                'text' => 'requestInfo',
                'description' => 'Ask the customer to provide additional details or clarification about the issue they reported. This helps in gathering more information to resolve the issue effectively.'
            ],
            [
                'label' => 'Acknowledge Issue',
                'text' => 'acknowledgeIssue',
                'description' => 'Confirm receipt of the customer\'s issue and reassure them that it is being investigated. This demonstrates that their concern is being taken seriously.'
            ],
            [
                'label' => 'Provide Solution',
                'text' => 'provideSolution',
                'description' => 'Offer a comprehensive solution or resolution to the problem described by the customer. This should address their concerns and provide actionable steps to resolve the issue.'
            ],
            [
                'label' => 'Follow Up',
                'text' => 'followUp',
                'description' => 'Reach out to the customer after a solution has been provided to ensure that their issue has been resolved to their satisfaction. This helps in confirming the resolution and maintaining good customer relations.'
            ],
            [
                'label' => 'Close Ticket',
                'text' => 'closeTicket',
                'description' => 'Notify the customer that their ticket will be closed as the issue has been resolved. Ensure that all their concerns are addressed before closing the ticket.'
            ]
        ];

        return apply_filters('fluent_support/get_create_response_preset_prompts', $presets);
    }
}
