<?php

namespace FluentSupport\App\Services\Integrations\AI\Providers;

use FluentSupport\App\Services\Integrations\AI\BaseAIProvider;
use WP_Error;

class GeminiProvider extends BaseAIProvider
{
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function getProviderName(): string
    {
        return 'Gemini';
    }

    public function getAvailableModels(): array
    {
        $models = [
            ['value' => 'gemini-2.5-pro',            'label' => 'Gemini 2.5 Pro (Most Powerful)'],
            ['value' => 'gemini-2.5-flash',          'label' => 'Gemini 2.5 Flash (Recommended)'],
            ['value' => 'gemini-2.5-flash-lite',     'label' => 'Gemini 2.5 Flash Lite (Fast)'],
            ['value' => 'gemini-2.0-flash-lite',     'label' => 'Gemini 2.0 Flash Lite (Fastest)'],
        ];

        return apply_filters('fluent_support/supported_gemini_models', $models);
    }

    /**
     * @param int    $ticketId
     * @param string $prompt
     * @param array  $messages
     * @return string|WP_Error
     */
    public function generateResponse(int $ticketId, string $prompt, array $messages = [])
    {
        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        list($systemText, $contents) = $this->convertMessages($messages, $prompt);

        $payload = ['contents' => $contents];

        if ($systemText) {
            $payload['system_instruction'] = ['parts' => [['text' => $systemText]]];
        }

        // Gemini uses API key in URL — no Authorization header needed
        $body = $this->sendRequest($url, $payload, []);

        if (is_wp_error($body)) {
            return $body;
        }

        $content = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokens  = ($body['usageMetadata']['promptTokenCount'] ?? 0)
                 + ($body['usageMetadata']['candidatesTokenCount'] ?? 0);

        $this->fireSuccessAction($ticketId, $prompt, $tokens);

        return $content;
    }

    /**
     * Converts OpenAI-format messages array to Gemini format.
     * Returns [systemText, contents].
     */
    private function convertMessages(array $messages, string $fallbackPrompt): array
    {
        if (empty($messages)) {
            return ['', [['role' => 'user', 'parts' => [['text' => $fallbackPrompt]]]]];
        }

        $systemText = '';
        $contents   = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemText = $msg['content'];
            } else {
                $contents[] = [
                    'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]],
                ];
            }
        }

        if (empty($contents)) {
            $contents   = [['role' => 'user', 'parts' => [['text' => $systemText ?: $fallbackPrompt]]]];
            $systemText = '';
        }

        return [$systemText, $contents];
    }

}

