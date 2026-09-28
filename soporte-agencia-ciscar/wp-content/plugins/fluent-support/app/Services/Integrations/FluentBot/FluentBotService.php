<?php

namespace FluentSupport\App\Services\Integrations\FluentBot;

use FluentSupport\App\Services\Integrations\FluentBot\FluentBotHelper;

class FluentBotService
{
    public function getPresetPrompts($type): array
    {
        return (new FluentBotHelper())->getPresetPrompts($type);
    }

    public function modifyResponse(string $prompt, $selectedText, $ticketId)
    {
        return (new FluentBotHelper())->modifyResponse($prompt, $selectedText, $ticketId);
    }

    public function generateResponse(string $responseContent, $ticket, $productId, $chatId = null)
    {
        return (new FluentBotHelper())->generateResponse($responseContent, $ticket, $productId, $chatId);
    }

    public function generateStreamResponse(string $responseContent, $ticket, $productId, $chatId = null, $selectedConversations = null, $includeTicketContent = true, $seedMessages = null, $webSearch = false, $temperature = 0)
    {
        return (new FluentBotHelper())->generateStreamResponse($responseContent, $ticket, $productId, $chatId, $selectedConversations, $includeTicketContent, $seedMessages, $webSearch, $temperature);
    }

    public function getTicketSummary($ticket)
    {
        return (new FluentBotHelper())->generateTicketSummary($ticket);
    }

    public function getTicketTone($ticket)
    {
        return (new FluentBotHelper())->generateTicketTone($ticket);
    }

    public function getChatMessages($chatId, $productId = null, $cursor = null)
    {
        return (new FluentBotHelper())->getChatMessages($chatId, $productId, $cursor);
    }

    public function getCustomPresets(): array
    {
        return (new FluentBotHelper())->getCustomPresets();
    }

    public function saveCustomPresets(array $presets): array
    {
        return (new FluentBotHelper())->saveCustomPresets($presets);
    }

}
