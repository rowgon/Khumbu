<?php

namespace FluentSupport\App\Services\Integrations\FluentBot;

use WP_Error;

class FluentBotAPI
{
    protected $apiUrl;

    protected $apiKey;

    public function __construct(string $apiUrl, string $apiKey = '')
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function makeRequest(int $ticketId, $prompt, array $args = [])
    {
        $response = $this->sendRequest($args);

        if (is_wp_error($response)) {
            $message = $response->get_error_message();
            $code = $response->get_error_code();
            return new \WP_Error($code, $message);
        }

        $rawBody = wp_remote_retrieve_body($response);
        $responseBody = json_decode($rawBody, true) ?? [];

        if (!$responseBody || !is_array($responseBody)) {
            return new \WP_Error('fluent_bot_error', __('Invalid or empty response from API', 'fluent-support'));
        }

        if (!empty($responseBody['error'])) {
            $message = $responseBody['error']['message'] ?? __('Unknown error occurred', 'fluent-support');
            return new \WP_Error('fluent_bot_error', $message);
        }

        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode !== 200) {
            $error = $responseBody['message'] ?? __('Something went wrong.', 'fluent-support');
            return new \WP_Error($statusCode, $error);
        }

        $content = $responseBody['response'] ?? '';

        if (empty($content)) {
            return new \WP_Error('fluent_bot_error', __('No AI response found in the API response.', 'fluent-support'));
        }

        $tokenUsage = $responseBody['token_usage'] ?? [];
        $totalTokens = ($tokenUsage['input_tokens'] ?? 0) + ($tokenUsage['output_tokens'] ?? 0);
        do_action('fluent_support/ai_response_success', $ticketId, $prompt, $totalTokens, "FluentBot");

        // Return both content and chat_id if available
        return [
            'content' => $content,
            'chat_id' => $responseBody['chat_id'] ?? null
        ];
    }

    public function makeStreamRequest(int $ticketId, $prompt, array $args = [])
    {
        $timeout = apply_filters('fs_ai_request_timeout', 120);

        // Use cURL for streaming
        // Note: Using cURL here because WordPress HTTP API doesn't support streaming SSE responses
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_exec
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_close
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_getinfo
        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_error
        // PluginCheck:ignoreFile
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($args));
        $streamHeaders = ['Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $streamHeaders[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $streamHeaders);

        $buffer = '';
        $conversationId = null;
        $streamTokens = 0;

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$buffer, &$conversationId, &$streamTokens) {
            $buffer .= $data;

            // Process complete SSE events from the AI API
            $events = explode("\n\n", $buffer);
            $buffer = array_pop($events); // Keep incomplete event in buffer

            foreach ($events as $event) {
                if (trim($event)) {
                    $lines = explode("\n", $event);
                    $eventType = '';
                    $eventId = '';
                    $eventDataLines = [];

                    foreach ($lines as $line) {
                        if (strpos($line, 'event: ') === 0) {
                            $eventType = trim((string)substr($line, 7));
                        } elseif (strpos($line, 'id: ') === 0) {
                            $eventId = trim((string)substr($line, 4));
                        } elseif (strpos($line, 'data: ') === 0) {
                            $eventDataLines[] = (string)substr($line, 6);
                        }
                    }

                    // Forward the event to the browser with proper formatting
                    if ($eventType) {
                        echo "event: ".esc_html($eventType)."\n";

                        // Include ID if present
                        if ($eventId !== '') {
                            echo "id: ".esc_html($eventId)."\n";
                        }

                        // Handle multiple data lines properly
                        if (!empty($eventDataLines)) {
                            foreach ($eventDataLines as $dataLine) {
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw SSE payload from trusted bot endpoint; rendered output sanitized client-side
                                echo "data: ".$dataLine."\n";
                            }
                        } else {
                            echo "data: \n";
                        }

                        echo "\n";

                        // Store chat_id for later use
                        if ($eventType === 'chat_id' && !empty($eventDataLines)) {
                            $conversationId = $eventDataLines[0];
                        } elseif ($eventType === 'token_usage' && !empty($eventDataLines)) {
                            $usage = json_decode($eventDataLines[0], true);
                            if (is_array($usage)) {
                                $streamTokens = ($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0);
                            }
                        }

                        flush();
                    }
                }
            }

            return strlen($data);
        });

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // Smaller buffer for faster streaming

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_error($ch)) {
            echo "event: error\n";
            echo "data: " . json_encode(['error' => curl_error($ch)]) . "\n\n";
            flush();
        }

        curl_close($ch);

        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_exec
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_close
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_getinfo
        // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_error

        if ($httpCode === 200) {
            do_action('fluent_support/ai_response_success', $ticketId, $prompt, $streamTokens, "FluentBot");
        }
    }

    protected function sendRequest(array $payload)
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $timeout = apply_filters('fs_ai_request_timeout', 60);

        return wp_remote_post($this->apiUrl, [
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
            'timeout' => $timeout,
        ]);
    }
}
