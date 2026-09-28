<?php

namespace FluentSupport\App\Services\Integrations\AI\Providers;

use FluentSupport\App\Services\Integrations\AI\BaseAIProvider;
use WP_Error;

class OpenAIProvider extends BaseAIProvider
{
    protected $completionsUrl = 'https://api.openai.com/v1/chat/completions';

    public function getProviderName(): string
    {
        return 'OpenAI';
    }

    public function getAvailableModels(): array
    {
        $models = [
            ['value' => 'gpt-4.1',              'label' => 'GPT-4.1'],
            ['value' => 'gpt-4.1-mini',         'label' => 'GPT-4.1 Mini'],
            ['value' => 'gpt-4.1-nano',         'label' => 'GPT-4.1 Nano'],
            ['value' => 'gpt-4o',               'label' => 'GPT-4o'],
            ['value' => 'gpt-4o-mini',          'label' => 'GPT-4o Mini'],
            ['value' => 'gpt-4o-2024-08-06',    'label' => 'GPT-4o (2024-08-06)'],
            ['value' => 'gpt-4o-mini-2024-07-18', 'label' => 'GPT-4o Mini (2024-07-18)'],
            ['value' => 'gpt-4-turbo',          'label' => 'GPT-4 Turbo'],
            ['value' => 'gpt-4',                'label' => 'GPT-4'],
            ['value' => 'gpt-3.5-turbo',        'label' => 'GPT-3.5 Turbo'],
            ['value' => 'o3',                   'label' => 'o3'],
            ['value' => 'o3-mini',              'label' => 'o3-mini'],
            ['value' => 'o4-mini',              'label' => 'o4-mini'],
            ['value' => 'o1',                   'label' => 'o1'],
        ];

        return apply_filters('fluent_support/supported_openai_models', $models);
    }

    /**
     * @param int    $ticketId
     * @param string $prompt
     * @param array  $messages
     * @return string|WP_Error
     */
    public function generateResponse(int $ticketId, string $prompt, array $messages = [])
    {
        if (empty($messages)) {
            $messages = [['role' => 'system', 'content' => $prompt]];
        }

        $payload = [
            'model'    => $this->model,
            'messages' => $messages,
        ];

        $headers = ['Authorization' => 'Bearer ' . $this->apiKey];

        $body = $this->sendRequest($this->completionsUrl, $payload, $headers);

        if (is_wp_error($body)) {
            return $body;
        }

        $content = $body['choices'][0]['message']['content'] ?? '';
        $tokens  = ($body['usage']['prompt_tokens'] ?? 0) + ($body['usage']['completion_tokens'] ?? 0);

        $this->fireSuccessAction($ticketId, $prompt, $tokens);

        return $content;
    }

}


