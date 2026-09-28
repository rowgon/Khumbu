<?php

namespace FluentSupport\App\Services\Notifications;

use FluentSupport\App\Models\Notification;
use FluentSupport\App\Models\NotificationUser;
use FluentSupport\Framework\Pagination\LengthAwarePaginator;
use FluentSupport\Framework\Support\Arr;

class NotificationQueryService
{
    public function getNotificationsForPerson($personId, array $filters = [])
    {
        if (!(new NotificationSettings())->canUseNotificationTables(true)) {
            return null;
        }

        $query = Notification::whereHas('recipients', function ($recipientQuery) use ($personId, $filters) {
            $recipientQuery->where('user_id', $personId);

            $status = Arr::get($filters, 'status');
            if ($status === 'unread') {
                $recipientQuery->where('is_read', 0);
            } elseif ($status === 'read') {
                $recipientQuery->where('is_read', 1);
            }
        })->with([
            'actor' => function ($actorQuery) {
                $actorQuery->select(['id', 'first_name', 'last_name', 'email', 'avatar']);
            },
            'ticket' => function ($ticketQuery) {
                $ticketQuery->select(['id', 'title']);
            },
            'recipients' => function ($recipientQuery) use ($personId) {
                $recipientQuery->select(['id', 'notification_id', 'user_id', 'is_read', 'read_at'])
                    ->where('user_id', $personId);
            }
        ]);

        $this->applyTicketAccessScope($query);

        $category = Arr::get($filters, 'category');
        if ($category && $category !== 'all') {
            $query->byCategory($category);
        }

        $ticketId = Arr::get($filters, 'ticket_id');
        if ($ticketId) {
            $query->where('ticket_id', (int) $ticketId);
        }

        return $query->orderBy('created_at', 'DESC');
    }

    public function paginateForPerson($personId, array $filters = [])
    {
        $perPage = min(max(absint(Arr::get($filters, 'per_page', 15)), 1), 100);
        $query = $this->getNotificationsForPerson($personId, $filters);

        if (!$query) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $query->paginate($perPage);
    }

    public function getRecentForPerson($personId, array $filters = [])
    {
        $query = $this->getNotificationsForPerson($personId, $filters);

        if (!$query) {
            return [];
        }

        $limit = absint(Arr::get($filters, 'limit', 8));
        if (!$limit) {
            $limit = 8;
        }

        $limit = min($limit, 20);

        return $query->limit($limit)->get();
    }

    public function getUnreadCount($personId, array $filters = [])
    {
        if (!(new NotificationSettings())->canUseNotificationTables(true)) {
            return 0;
        }

        $query = NotificationUser::where('user_id', $personId)->unread();

        $category = Arr::get($filters, 'category');
        $ticketId = Arr::get($filters, 'ticket_id');

        $query->whereHas('notification', function ($notificationQuery) use ($category, $ticketId) {
            $this->applyTicketAccessScope($notificationQuery);

            if ($category && $category !== 'all') {
                $notificationQuery->byCategory($category);
            }

            if ($ticketId) {
                $notificationQuery->where('ticket_id', (int) $ticketId);
            }
        });

        return (int) $query->count();
    }

    protected function applyTicketAccessScope($query)
    {
        $query->whereHas('ticket', function ($ticketQuery) {
            do_action_ref_array('fluent_support/tickets_query_by_permission_ref', [&$ticketQuery, false]);
            do_action_ref_array('fluent_support\main_tickets_query', [&$ticketQuery, []]);
        });
    }
}
