<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Notifications\NotificationCategory;
use FluentSupport\App\Services\Notifications\NotificationFormatter;
use FluentSupport\App\Services\Notifications\NotificationService;
use FluentSupport\Framework\Http\Request\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $agent = Helper::getAgentByUserId();

        if (!$agent) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
            ]);
        }

        $notificationService = new NotificationService();
        $formatter = new NotificationFormatter();

        return array_merge([
            'notifications' => $formatter->formatPaginated($notificationService->paginateForPerson($agent->id, $this->getFilters($request))),
            'unread_count'  => $notificationService->getUnreadCount($agent->id)
        ], $this->getNotificationMeta());
    }

    public function unread(Request $request)
    {
        $agent = Helper::getAgentByUserId();

        if (!$agent) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
            ]);
        }

        $filters = array_merge($this->getFilters($request), [
            'status' => 'unread',
            'limit'  => $request->getSafe('limit', 'intval')
        ]);

        $notificationService = new NotificationService();
        $formatter = new NotificationFormatter();

        return array_merge([
            'notifications' => $formatter->formatMany($notificationService->getRecentForPerson($agent->id, $filters)),
            'unread_count'  => $notificationService->getUnreadCount($agent->id)
        ], $this->getNotificationMeta());
    }

    public function unreadCount(Request $request)
    {
        $agent = Helper::getAgentByUserId();

        if (!$agent) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
            ]);
        }

        return [
            'count' => (new NotificationService())->getUnreadCount($agent->id, $this->getFilters($request))
        ];
    }

    public function markRead(Request $request, $notification_id)
    {
        $agent = Helper::getAgentByUserId();

        if (!$agent) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
            ]);
        }

        return [
            'updated'      => (new NotificationService())->markAsRead((int) $notification_id, $agent->id),
            'unread_count' => (new NotificationService())->getUnreadCount($agent->id)
        ];
    }

    public function markAllRead(Request $request)
    {
        $agent = Helper::getAgentByUserId();

        if (!$agent) {
            return $this->sendError([
                'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
            ]);
        }

        return [
            'updated'      => (new NotificationService())->markAllAsRead($agent->id),
            'unread_count' => (new NotificationService())->getUnreadCount($agent->id)
        ];
    }

    protected function getFilters(Request $request)
    {
        return [
            'category' => $request->getSafe('category', 'sanitize_text_field'),
            'status'   => $request->getSafe('status', 'sanitize_text_field'),
            'ticket_id' => $request->getSafe('ticket_id', 'intval'),
            'per_page' => $request->getSafe('per_page', 'intval')
        ];
    }

    protected function getNotificationMeta()
    {
        $categories = NotificationCategory::options();

        return [
            'meta' => [
                'categories' => $categories
            ],
            'notification_categories' => $categories
        ];
    }
}
