<?php

namespace FluentSupport\App\Services\Notifications;

use FluentSupport\App\Models\Notification;
use FluentSupport\App\Models\NotificationUser;
use FluentSupport\Framework\Support\Arr;
use InvalidArgumentException;

class NotificationService
{
    public function createNotification(array $data, array $recipientPersonIds = [])
    {
        $settings = new NotificationSettings();
        $recipientPersonIds = $this->normalizeRecipientPersonIds($recipientPersonIds);

        if (!$recipientPersonIds) {
            return null;
        }

        if (!$settings->canUseNotificationTables(true)) {
            return null;
        }

        $eventType = NotificationEventMap::normalizeEventType(Arr::get($data, 'event_type'));
        $expectedCategory = NotificationEventMap::resolveCategory($eventType);
        $category = Arr::get($data, 'category');

        if ($category && $category !== $expectedCategory) {
            throw new InvalidArgumentException('Notification category must match the event_type.');
        }

        $category = $expectedCategory;

        if (!$settings->isEnabled() || !$settings->isEventEnabled($eventType)) {
            return null;
        }

        $actorId = (int) Arr::get($data, 'actor_id');
        if (!$settings->allowsSelfNotifications() && $actorId) {
            $recipientPersonIds = array_values(array_diff($recipientPersonIds, [$actorId]));
        }

        if (!$recipientPersonIds) {
            return null;
        }

        $notification = (new Notification())->getConnection()->transaction(function () use ($data, $recipientPersonIds, $eventType, $category) {
            $notification = Notification::create([
                'actor_id'        => Arr::get($data, 'actor_id'),
                'ticket_id'       => Arr::get($data, 'ticket_id'),
                'conversation_id' => Arr::get($data, 'conversation_id'),
                'event_type'      => $eventType,
                'category'        => $category,
                'payload'         => Arr::get($data, 'payload', [])
            ]);

            $this->attachRecipients($notification->id, $recipientPersonIds, Arr::get($data, 'channel', 'web'));

            return $notification;
        });

        return $notification;
    }

    public function attachRecipients($notificationId, array $recipientPersonIds, $channel = 'web')
    {
        if (!(new NotificationSettings())->canUseNotificationTables()) {
            return [];
        }

        $recipientPersonIds = $this->normalizeRecipientPersonIds($recipientPersonIds);

        if (!$recipientPersonIds) {
            return [];
        }

        $now = current_time('mysql');
        $rows = [];

        foreach ($recipientPersonIds as $recipientPersonId) {
            $rows[] = [
                'notification_id' => $notificationId,
                'user_id'         => $recipientPersonId,
                'channel'         => $channel ?: 'web',
                'is_read'         => 0,
                'created_at'      => $now,
                'updated_at'      => $now
            ];
        }

        if ($rows) {
            NotificationUser::insertOrIgnore($rows);
        }

        return $rows;
    }

    protected function normalizeRecipientPersonIds(array $recipientPersonIds)
    {
        return array_values(array_unique(array_map('intval', array_filter($recipientPersonIds))));
    }

    public function markAsRead($notificationId, $personId)
    {
        if (!(new NotificationSettings())->canUseNotificationTables()) {
            return 0;
        }

        $now = current_time('mysql');

        return NotificationUser::where('notification_id', $notificationId)
            ->where('user_id', $personId)
            ->update([
                'is_read'    => 1,
                'read_at'    => $now,
                'updated_at' => $now
            ]);
    }

    public function markAllAsRead($personId)
    {
        if (!(new NotificationSettings())->canUseNotificationTables()) {
            return 0;
        }

        $now = current_time('mysql');

        return NotificationUser::where('user_id', $personId)
            ->where('is_read', 0)
            ->update([
                'is_read'    => 1,
                'read_at'    => $now,
                'updated_at' => $now
            ]);
    }

    public function getUnreadCount($personId, array $filters = [])
    {
        return (new NotificationQueryService())->getUnreadCount($personId, $filters);
    }

    public function paginateForPerson($personId, array $filters = [])
    {
        return (new NotificationQueryService())->paginateForPerson($personId, $filters);
    }

    public function getRecentForPerson($personId, array $filters = [])
    {
        return (new NotificationQueryService())->getRecentForPerson($personId, $filters);
    }
}
