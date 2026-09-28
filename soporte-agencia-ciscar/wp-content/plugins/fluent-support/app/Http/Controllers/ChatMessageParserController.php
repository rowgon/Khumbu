<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\ThirdParty\HandleSlackEvent;
use FluentSupport\App\Services\ThirdParty\HandleTelegramEvent;

/**
 *  ChatMessageParserController class is responsible for getting information from integrated 3rd party request and response
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */

class ChatMessageParserController extends Controller
{
    /**
     * handleTelegramWebhook will get the content from telegram, check the data validity and create response in a ticket
     * @param Request $request
     * @param HandleTelegramEvent $handler
     * @param string $token
     * @throws Exception
     * @return mixed
     */
    public function handleTelegramWebhook(Request $request, HandleTelegramEvent $handler, $token)
    {
        if (!$this->verifyWebhookSignature('telegram', $request)) {
            return $this->sendError([
                'message' => __('Invalid request signature.', 'fluent-support'),
                'status'  => false
            ], 403);
        }

        try {
            return $this->sendSuccess([
                'message' => __('Response has been successfully recorded', 'fluent-support'),
                'status'  => true,
                'data'    => $handler->handleEvent($request->all(), $token)
            ]);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e),
                'status'  => false
            ]);
        }
    }

    /**
     * handleSlackEvent responsible for getting information from integrated slack request and response
     * @param Request $request
     * @param HandleSlackEvent $handler
     * @param $token
     * @return array
     */
    public function handleSlackEvent(Request $request, HandleSlackEvent $handler, $token)
    {
        if (!$this->verifyWebhookSignature('slack', $request)) {
            return $this->sendError([
                'message' => __('Invalid request signature.', 'fluent-support'),
                'status'  => false
            ], 403);
        }

        if ($request->getSafe('type', 'sanitize_text_field') === 'url_verification') {
            return new \WP_REST_Response($request->getSafe('challenge', 'sanitize_text_field'), 200, [
                'Content-Type' => 'text/plain; charset=utf-8'
            ]);
        }

        try {
            return $this->sendSuccess([
                'message' => 'received',
                'result' => $handler->handleEvent($token)
            ]);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e),
                'status'  => false
            ]);
        }
    }

    /**
     * Verify webhook signature using platform-specific logic.
     *
     * Pro plugin or third-party code may hook into the filter
     * 'fluent_support/verify_webhook_signature_{platform}' to enable
     * cryptographic signature verification. When no verifier is
     * registered, fall back to the existing token-based validation
     * handled by the downstream webhook handlers.
     *
     * @param string $platform 'telegram' or 'slack'
     * @param Request $request
     * @return bool
     */
    private function verifyWebhookSignature($platform, Request $request)
    {
        $hookName = 'fluent_support/verify_webhook_signature_' . $platform;

        if (!has_filter($hookName)) {
            return true;
        }

        return (bool) apply_filters($hookName, false, $request);
    }
}
