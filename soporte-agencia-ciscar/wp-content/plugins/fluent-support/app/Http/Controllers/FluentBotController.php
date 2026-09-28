<?php

namespace FluentSupport\App\Http\Controllers;


use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Http\Controllers\Controller;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Integrations\FluentBot\FluentBotService;

class FluentBotController extends Controller
{
    private const CHAT_ID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const MAX_SELECTED_CONVERSATIONS = 500;
    private const MAX_SEED_MESSAGES = 50;
    private const MAX_SEED_MESSAGE_LENGTH = 5000;

    public function getPresetPrompts(Request $request)
    {
        $type = $request->getSafe('type', 'sanitize_text_field');

        try {
            return (new FluentBotService())->getPresetPrompts($type);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    // Ticket-safe runtime config for the chat panel: tells the UI which products have a bot
    // configured and whether a general bot exists. Never exposes bot IDs. Readable by any
    // agent with ticket-route access so the panel works without AdminSettingsPolicy.
    public function getRuntimeConfig()
    {
        $meta = Meta::where([
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_config'
        ])->orderByDesc('id')->first();

        $settings = $meta ? Helper::safeUnserialize($meta->value) : [];
        if (!is_array($settings)) {
            $settings = [];
        }

        // generalBotEnabled defaults to true for backward compat with configs saved
        // before the flag existed. The runtime-config consumer uses this to decide
        // whether to show the "General Bot" option in the ticket-side product dropdown.
        $generalBotEnabled = !array_key_exists('generalBotEnabled', $settings)
            || filter_var($settings['generalBotEnabled'], FILTER_VALIDATE_BOOLEAN);

        $hasGeneralBot = $generalBotEnabled
            && !empty($settings['generalBotId'])
            && trim((string) $settings['generalBotId']) !== '';

        $configuredProductIds = [];
        foreach (($settings['productMappings'] ?? []) as $mapping) {
            if (!is_array($mapping)) {
                continue;
            }
            $botId = $mapping['botId'] ?? '';
            if (trim((string) $botId) === '') {
                continue;
            }
            $productId = intval($mapping['productId'] ?? 0);
            if ($productId > 0) {
                $configuredProductIds[] = $productId;
            }
        }

        return [
            'hasGeneralBot'        => $hasGeneralBot,
            'configuredProductIds' => array_values(array_unique($configuredProductIds)),
        ];
    }

    public function generateResponse(Request $request, $id)
    {
        $ticketId = intval($id);
        $productId = $request->getSafe('product_id', 'intval');
        $prompt = $request->getSafe('content', 'sanitize_text_field');
        $conversationId = $request->getSafe('chat_id', 'sanitize_text_field', '');
        $selectedText = $request->getSafe('selectedText', 'sanitize_text_field', '');
        $type = $request->getSafe('type', 'sanitize_text_field', 'response');

        try {
            $customAI = new FluentBotService();

            $ticket = Ticket::findOrFail($ticketId);
            $this->ensureCanAccessTicket($ticket);

            if ($type === 'modifyResponse') {
                $result = $customAI->modifyResponse($prompt, $selectedText, $ticketId);
            } else {
                $ticket->load('responses');
                $result = $customAI->generateResponse($prompt, $ticket, $productId, $conversationId ?: null);
            }

            return $result;
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }



    public function generateStreamResponse(Request $request, $id)
    {
        $ticketId = intval($id);
        $productId = $request->getSafe('product_id', 'intval');
        $prompt = $request->getSafe('content', 'sanitize_text_field');
        $selectedText = $request->getSafe('selectedText', 'sanitize_text_field', '');
        $type = $request->getSafe('type', 'sanitize_text_field', 'response');
        // Distinguish "key absent" (use full ticket context) from "explicit empty list"
        // (user intentionally deselected all responses — keep selected mode).
        // Normalize to non-empty int IDs and cap length to prevent abuse.
        $selectedConversations = $request->exists('selected_conversations')
            ? array_slice(
                array_values(array_filter(array_map('intval', (array) $request->get('selected_conversations', [])))),
                0, self::MAX_SELECTED_CONVERSATIONS
            )
            : null;
        $includeTicketContent = filter_var($request->get('include_ticket_content', true), FILTER_VALIDATE_BOOLEAN);
        $webSearch = filter_var($request->get('web_search', false), FILTER_VALIDATE_BOOLEAN);
        $temperature = max(0, min(2, floatval($request->get('temperature', 0))));

        // Cap and sanitize seed messages: only allowed roles, bounded content length, count capped.
        $seedMessagesRaw = array_slice((array) $request->get('conversation_history', []), -self::MAX_SEED_MESSAGES);
        $seedMessages = [];
        foreach ($seedMessagesRaw as $m) {
            if (!is_array($m)) {
                continue;
            }
            $role = $m['role'] ?? '';
            if (!in_array($role, ['visitor', 'ai'], true)) {
                continue;
            }
            $content = (string) ($m['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $seedMessages[] = [
                'role'    => $role,
                'content' => mb_substr($content, 0, self::MAX_SEED_MESSAGE_LENGTH),
            ];
        }

        $resetChat = filter_var($request->get('reset_chat', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $customAI = new FluentBotService();
            $ticket = Ticket::findOrFail($ticketId);
            $this->ensureCanAccessTicket($ticket);

            // Authorization passed — safe to touch ticket meta.
            // If client requests a reset, clear stored chat mapping before reading.
            // Otherwise only use the ticket's stored chat_id — ignore client-supplied values.
            if ($resetChat) {
                // Match the stricter permission gate used by deleteChatId — broader
                // ticket-read access should not be enough to clear persisted chat state.
                if (!(\FluentSupport\App\Modules\PermissionManager::canManageTickets()
                    || \FluentSupport\App\Modules\PermissionManager::currentUserCan('fst_draft_reply'))) {
                    throw new \Exception(__('You do not have permission to reset chat', 'fluent-support'));
                }
                $this->deleteTicketMeta($ticketId, '_fluent_bot_chat_id');
                $this->deleteTicketMeta($ticketId, '_fluent_bot_chat_product');
                $conversationId = '';
            } else {
                $storedChat = $this->getTicketMeta($ticketId, '_fluent_bot_chat_id');
                $conversationId = $storedChat ? $storedChat->value : '';
                // Guard against corrupt stored values; fall back to fresh chat upstream
                if ($conversationId && !preg_match(self::CHAT_ID_PATTERN, $conversationId)) {
                    $conversationId = '';
                }
            }

            if ($type === 'modifyResponse') {
                $result = $customAI->modifyResponse($prompt, $selectedText, $ticketId);
                return $result;
            } else {
                // Skip eager-load when selected-context mode is active — helper fetches targeted rows
                if ($selectedConversations === null) {
                    $ticket->load('responses');
                }

                // Prevent WordPress and PHP from flushing/compressing buffers on shutdown
                remove_action('shutdown', 'wp_ob_end_flush_all', 1);
                @ini_set('zlib.output_compression', 'Off');
                @ini_set('output_buffering', 'Off');
                @ini_set('output_handler', '');

                // Clear all output buffers for raw SSE streaming
                $maxLevels = 10;
                while (ob_get_level() && $maxLevels-- > 0) {
                    @ob_end_clean();
                }

                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');
                header('Connection: keep-alive');
                header('X-Accel-Buffering: no');

                // Send initial connection event
                echo "event: connected\n";
                echo "data: Connection established\n\n";
                flush();

                // Start streaming response
                $customAI->generateStreamResponse($prompt, $ticket, $productId, $conversationId ?: null, $selectedConversations, $includeTicketContent, $seedMessages ?: null, $webSearch, $temperature);

                // Send end event
                echo "event: end\n";
                echo "data: Stream completed\n\n";
                flush();

                exit;
            }
        } catch (\Exception $e) {
            // Send error as SSE event. Inline message extraction here because
            // Helper::getSafeErrorMessage() throws ValidationException and would
            // short-circuit the echo/flush/exit below.
            $message = $e->getMessage() ?: __('Something went wrong. Please try again later.', 'fluent-support');
            echo "event: error\n";
            echo "data: " . json_encode(['message' => esc_html($message)]) . "\n\n";
            flush();
            exit;
        }
    }

    public function getTicketSummary(Request $request, $id)
    {
        try {
            $ticketId = intval($id);
            $ticket = Ticket::with('responses')->findOrFail($ticketId);
            $this->ensureCanAccessTicket($ticket);

            return (new FluentBotService())->getTicketSummary($ticket);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getTicketTone(Request $request, $id)
    {
        try {
            $ticketId = intval($id);
            $ticket = Ticket::with('responses')->findOrFail($ticketId);
            $this->ensureCanAccessTicket($ticket);

            return (new FluentBotService())->getTicketTone($ticket);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    private function authorizeTicketAccess($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $this->ensureCanAccessTicket($ticket);
        return $ticket;
    }

    public function getChatId(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);

        $meta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_id');
        $productMeta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_product');

        return [
            'chat_id' => $meta ? $meta->value : null,
            'product_id' => $productMeta ? (int) $productMeta->value : null
        ];
    }

    public function getChatMessages(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);
        $cursor = $request->getSafe('cursor', 'sanitize_text_field', '');

        $meta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_id');

        if (!$meta || !$meta->value) {
            return ['data' => [], 'next_cursor' => null];
        }

        $productMeta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_product');
        $productId = $productMeta ? (int) $productMeta->value : $request->getSafe('product_id', 'intval');

        $result = (new FluentBotService())->getChatMessages($meta->value, $productId, $cursor ?: null);

        if (is_wp_error($result)) {
            return $this->sendError(['message' => $result->get_error_message()]);
        }

        unset($result['path'], $result['prev_cursor'], $result['prev_page_url'], $result['next_page_url']);

        return $result;
    }

    public function saveChatId(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);
        $chatId = $request->getSafe('chat_id', 'sanitize_text_field');
        $productId = $request->getSafe('product_id', 'intval', 0);
        $title = $request->getSafe('title', 'sanitize_text_field', '');

        // Validate UUID format
        if (!$chatId || !preg_match(self::CHAT_ID_PATTERN, $chatId)) {
            return $this->sendError(['message' => __('Invalid chat_id format', 'fluent-support')], 422);
        }

        $db = \FluentSupport\App\App::getInstance('db');
        $result = $db->transaction(function () use ($ticketId, $chatId, $productId) {
            // Lock the ticket row (always exists) to serialize concurrent first-writes for this ticket.
            // lockForUpdate on a non-existent meta row is a no-op, so we anchor to the parent row instead.
            Ticket::where('id', $ticketId)->lockForUpdate()->first();

            $existing = Meta::where([
                'object_type' => 'ticket_meta',
                'object_id'   => $ticketId,
                'key'         => '_fluent_bot_chat_id',
            ])->orderByDesc('id')->first();

            // Only conflict if the stored value is a valid UUID that differs from the new one.
            // A corrupt/legacy stored value should be overwritten — the stream endpoint already
            // treats it as empty, so keeping the 409 would trap tickets in a broken state.
            if ($existing
                && $existing->value
                && preg_match(self::CHAT_ID_PATTERN, (string) $existing->value)
                && $existing->value !== $chatId) {
                return ['error' => __('Chat ID already set for this ticket', 'fluent-support')];
            }

            $this->upsertTicketMeta($ticketId, '_fluent_bot_chat_id', $chatId);
            $this->upsertTicketMeta($ticketId, '_fluent_bot_chat_product', $productId);
            return null;
        });

        if ($result) {
            return $this->sendError(['message' => $result['error']], 409);
        }

        // Track the conversation so it appears in the ticket's "Past conversations"
        // list. Idempotent per chat_id — repeated saves of the same id are no-ops.
        $this->appendChatHistory($ticketId, $chatId, $productId, $title);

        return [
            'success' => true,
            'chat_id' => $chatId
        ];
    }

    private function upsertTicketMeta($ticketId, $key, $value)
    {
        $where = [
            'object_type' => 'ticket_meta',
            'object_id'   => $ticketId,
            'key'         => $key,
        ];

        $meta = Meta::where($where)->orderByDesc('id')->first();

        if ($meta) {
            $meta->value = $value;
            $meta->save();
        } else {
            Meta::create(array_merge($where, ['value' => $value]));
        }

        // Do not prune siblings here — concurrent first-writes could race and delete each
        // other's inserts, leaving zero rows. Reads use orderByDesc('id')->first() so
        // duplicates are harmless at read time. saveChatId() serializes via ticket-row lock.
    }

    private function getTicketMeta($ticketId, $key)
    {
        return Meta::where([
            'object_type' => 'ticket_meta',
            'object_id'   => $ticketId,
            'key'         => $key,
        ])->orderByDesc('id')->first();
    }

    private function deleteTicketMeta($ticketId, $key)
    {
        Meta::where([
            'object_type' => 'ticket_meta',
            'object_id'   => $ticketId,
            'key'         => $key,
        ])->delete();
    }

    public function deleteChatId(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);

        Meta::where('object_type', 'ticket_meta')
            ->where('object_id', $ticketId)
            ->whereIn('key', [
                '_fluent_bot_chat_id',
                '_fluent_bot_chat_product',
                '_fluent_bot_context_selection',
            ])
            ->delete();

        return [
            'success' => true
        ];
    }

    /**
     * List the ticket's past FluentBot conversations (newest first) plus the
     * currently-active chat_id, for the "Past conversations" switcher.
     */
    public function getConversations(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);

        $active = $this->getTicketMeta($ticketId, '_fluent_bot_chat_id');

        return [
            'conversations'  => $this->readChatHistory($ticketId),
            'active_chat_id' => $active ? $active->value : null,
        ];
    }

    /**
     * Make a past conversation the active one so the next message continues it.
     * Only a chat_id already recorded in THIS ticket's history may be selected —
     * the stream endpoint trusts the stored chat_id, so this is the ownership gate.
     */
    public function switchConversation(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);
        $chatId = $request->getSafe('chat_id', 'sanitize_text_field');

        if (!$chatId || !preg_match(self::CHAT_ID_PATTERN, $chatId)) {
            return $this->sendError(['message' => __('Invalid chat_id format', 'fluent-support')], 422);
        }

        $entry = null;
        foreach ($this->readChatHistory($ticketId) as $h) {
            if (isset($h['chat_id']) && $h['chat_id'] === $chatId) {
                $entry = $h;
                break;
            }
        }

        if (!$entry) {
            return $this->sendError(['message' => __('Conversation not found for this ticket.', 'fluent-support')], 404);
        }

        $productId = (int) ($entry['product_id'] ?? 0);
        $this->upsertTicketMeta($ticketId, '_fluent_bot_chat_id', $chatId);
        $this->upsertTicketMeta($ticketId, '_fluent_bot_chat_product', $productId);

        return [
            'success'    => true,
            'chat_id'    => $chatId,
            'product_id' => $productId,
        ];
    }

    private function readChatHistory($ticketId): array
    {
        $meta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_history');
        $history = ($meta && $meta->value) ? json_decode($meta->value, true) : [];

        return is_array($history) ? array_values($history) : [];
    }

    /**
     * Prepend a conversation to the ticket's history. Idempotent per chat_id;
     * capped so ticket meta cannot grow unbounded.
     */
    private function appendChatHistory($ticketId, $chatId, $productId, $title = '')
    {
        $history = $this->readChatHistory($ticketId);

        foreach ($history as $entry) {
            if (isset($entry['chat_id']) && $entry['chat_id'] === $chatId) {
                return;
            }
        }

        array_unshift($history, [
            'chat_id'    => $chatId,
            'product_id' => (int) $productId,
            'title'      => $title !== '' ? $title : __('Conversation', 'fluent-support'),
            'created_at' => time(),
        ]);

        $history = array_slice($history, 0, 20);

        $this->upsertTicketMeta($ticketId, '_fluent_bot_chat_history', wp_json_encode($history));
    }

    public function getContextSelection(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);

        $meta = $this->getTicketMeta($ticketId, '_fluent_bot_context_selection');

        if (!$meta) {
            return ['data' => null];
        }

        return ['data' => Helper::safeUnserialize($meta->value)];
    }

    public function saveContextSelection(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);
        $selectedIds = (array) $request->get('selected_ids', []);
        $knownIds = (array) $request->get('known_ids', []);
        $includeTicketContent = filter_var($request->get('include_ticket_content', true), FILTER_VALIDATE_BOOLEAN);

        // Cap array sizes to prevent meta-row bloat from malicious input
        $data = [
            'selectedIds'          => array_slice(array_map('intval', $selectedIds), 0, 500),
            'knownIds'             => array_slice(array_map('intval', $knownIds), 0, 500),
            'includeTicketContent' => $includeTicketContent,
        ];

        $serialized = maybe_serialize($data);

        $this->upsertTicketMeta($ticketId, '_fluent_bot_context_selection', $serialized);

        return ['success' => true];
    }

    private function resolveTicketFeedbackContext($ticketId)
    {
        $chatMeta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_id');
        if (!$chatMeta || !$chatMeta->value) {
            return null;
        }

        $productMeta = $this->getTicketMeta($ticketId, '_fluent_bot_chat_product');
        return [
            'chat_id'    => $chatMeta->value,
            'product_id' => $productMeta ? (int) $productMeta->value : 0,
        ];
    }

    public function createFeedback(Request $request, $id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);

        // fluent-bot Message PKs are UUIDs — sanitize as text, not intval.
        $messageId = $request->getSafe('message_id', 'sanitize_text_field');
        $reaction = $request->getSafe('reaction', 'sanitize_text_field');
        $comment = $request->getSafe('comments', 'sanitize_textarea_field', '');

        if (!$messageId || !wp_is_uuid($messageId)) {
            return $this->sendError(['message' => __('Invalid message_id', 'fluent-support')], 422);
        }

        if (!$reaction || !in_array($reaction, ['positive', 'negative'], true)) {
            return $this->sendError(['message' => __('Invalid reaction', 'fluent-support')], 422);
        }

        $ctx = $this->resolveTicketFeedbackContext($ticketId);
        if (!$ctx) {
            return $this->sendError(['message' => __('No active chat for this ticket', 'fluent-support')], 422);
        }

        $helper = new \FluentSupport\App\Services\Integrations\FluentBot\FluentBotHelper();
        $result = $helper->createFeedback($messageId, $reaction, $comment ?: null, $ctx['product_id'], $ctx['chat_id']);

        if (is_wp_error($result)) {
            return $this->sendError(['message' => $result->get_error_message()], 500);
        }

        return $result;
    }

    public function deleteFeedback(Request $request, $id, $feedback_id)
    {
        $ticketId = intval($id);
        $this->authorizeTicketAccess($ticketId);

        // fluent-bot Feedback PKs are integers (unlike message ids, which are UUIDs).
        $feedbackId = intval($feedback_id);

        if (!$feedbackId || $feedbackId < 1) {
            return $this->sendError(['message' => __('Invalid feedback_id', 'fluent-support')], 422);
        }

        $ctx = $this->resolveTicketFeedbackContext($ticketId);
        if (!$ctx) {
            return $this->sendError(['message' => __('No active chat for this ticket', 'fluent-support')], 422);
        }

        $helper = new \FluentSupport\App\Services\Integrations\FluentBot\FluentBotHelper();
        $result = $helper->deleteFeedback($feedbackId, $ctx['product_id'], $ctx['chat_id']);

        if (is_wp_error($result)) {
            return $this->sendError(['message' => $result->get_error_message()], 500);
        }

        return $result;
    }
}
