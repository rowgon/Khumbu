<?php

namespace FluentSupport\App\Services\Integrations\AI;

use FluentSupport\App\Services\Helper;
use WP_Error;

class AIProviderFactory
{
    private static $instance = null;

    private static $allowedProviders = ['openai', 'gemini', 'anthropic'];

    /**
     * @param string $provider
     * @param string $apiKey
     * @param string $model
     * @return BaseAIProvider|WP_Error
     */
    public static function make(string $provider, string $apiKey, string $model)
    {
        switch ($provider) {
            case 'openai':
                return new Providers\OpenAIProvider($apiKey, $model);
            case 'gemini':
                return new Providers\GeminiProvider($apiKey, $model);
            case 'anthropic':
                return new Providers\AnthropicProvider($apiKey, $model);
            default:
                return new WP_Error('invalid_provider', __('Invalid AI provider.', 'fluent-support'));
        }
    }

    /**
     * @return BaseAIProvider|WP_Error
     */
    public static function makeFromSettings()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $settings = Helper::getAIProviderSettings();

        if (($settings['enabled'] ?? 'yes') === 'no') {
            return new WP_Error('ai_disabled', __('AI features are disabled.', 'fluent-support'));
        }

        $provider = $settings['provider'] ?? 'openai';

        if (empty($settings['api_key'])) {
            return new WP_Error('no_api_key', __('No AI provider configured.', 'fluent-support'));
        }
        $instance = self::make($provider, $settings['api_key'], $settings['model'] ?? '');

        if (!is_wp_error($instance)) {
            self::$instance = $instance;
        }

        return $instance;
    }

    public static function clearCache(): void
    {
        self::$instance = null;
    }

    public static function getAllowedProviders(): array
    {
        return self::$allowedProviders;
    }
}
