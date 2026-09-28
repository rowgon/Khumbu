<?php

namespace FluentSupport\App\Services\Notifications;

class NotificationFormatter
{
    public function formatPaginated($notifications)
    {
        $formattedNotifications = $notifications->toArray();
        $formattedNotifications['data'] = $this->formatMany($formattedNotifications['data']);

        return $formattedNotifications;
    }

    public function formatMany($notifications)
    {
        $formattedNotifications = [];

        foreach ($notifications as $notification) {
            $formattedNotifications[] = $this->format($notification);
        }

        return $formattedNotifications;
    }

    public function format($notification)
    {
        $notification = is_array($notification) ? $notification : $notification->toArray();
        $displayData = NotificationEventMap::displayData($notification['event_type'] ?? '');

        $notification = $this->toSafePayload($notification);
        $notification['event_label'] = $displayData['label'];
        $notification['event_icon'] = $displayData['icon'];
        $notification['summary'] = $this->getSummary($notification);

        return $notification;
    }

    protected function toSafePayload($notification)
    {
        return [
            'id'              => (int) ($notification['id'] ?? 0),
            'actor_id'        => !empty($notification['actor_id']) ? (int) $notification['actor_id'] : null,
            'ticket_id'       => !empty($notification['ticket_id']) ? (int) $notification['ticket_id'] : null,
            'conversation_id' => !empty($notification['conversation_id']) ? (int) $notification['conversation_id'] : null,
            'event_type'      => $notification['event_type'] ?? '',
            'category'        => $notification['category'] ?? '',
            'payload'         => $this->sanitizePayload($notification['payload'] ?? []),
            'actor'           => $this->formatActor($notification['actor'] ?? null),
            'ticket'          => $this->formatTicket($notification['ticket'] ?? null),
            'recipients'      => $this->formatRecipients($notification['recipients'] ?? []),
            'created_at'      => $notification['created_at'] ?? null,
            'updated_at'      => $notification['updated_at'] ?? null,
        ];
    }

    protected function sanitizePayload($payload)
    {
        $payload = is_array($payload) ? $payload : [];
        $safePayload = [];

        foreach (['ticket_title', 'ticket_priority', 'conversation_type', 'content_preview', 'status', 'assignment_type', 'workflow_name', 'summary'] as $key) {
            if (array_key_exists($key, $payload)) {
                $safePayload[$key] = sanitize_text_field($payload[$key]);
            }
        }

        foreach (['customer', 'agent', 'actor', 'assigner', 'assigned_agent'] as $personKey) {
            if (!empty($payload[$personKey]) && is_array($payload[$personKey])) {
                $safePayload[$personKey] = $this->formatPayloadPerson($payload[$personKey]);
            }
        }

        if (!empty($payload['mentioned_handles']) && is_array($payload['mentioned_handles'])) {
            $safePayload['mentioned_handles'] = array_values(array_map('sanitize_text_field', $payload['mentioned_handles']));
        }

        return $safePayload;
    }

    protected function formatPayloadPerson($person)
    {
        return array_filter([
            'id'          => !empty($person['id']) ? (int) $person['id'] : null,
            'person_type' => !empty($person['person_type']) ? sanitize_text_field($person['person_type']) : null,
            'name'        => !empty($person['name']) ? sanitize_text_field($person['name']) : null,
            'photo'       => !empty($person['photo']) ? esc_url_raw($person['photo']) : null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    protected function formatActor($actor)
    {
        if (empty($actor) || !is_array($actor)) {
            return null;
        }

        return [
            'id'        => !empty($actor['id']) ? (int) $actor['id'] : null,
            'full_name' => !empty($actor['full_name']) ? sanitize_text_field($actor['full_name']) : '',
            'photo'     => !empty($actor['photo']) ? esc_url_raw($actor['photo']) : '',
        ];
    }

    protected function formatTicket($ticket)
    {
        if (empty($ticket) || !is_array($ticket)) {
            return null;
        }

        return [
            'id'    => !empty($ticket['id']) ? (int) $ticket['id'] : null,
            'title' => !empty($ticket['title']) ? sanitize_text_field($ticket['title']) : '',
        ];
    }

    protected function formatRecipients($recipients)
    {
        if (!is_array($recipients)) {
            return [];
        }

        return array_map(function ($recipient) {
            return [
                'id'      => !empty($recipient['id']) ? (int) $recipient['id'] : null,
                'user_id' => !empty($recipient['user_id']) ? (int) $recipient['user_id'] : null,
                'is_read' => !empty($recipient['is_read']) ? (int) $recipient['is_read'] : 0,
                'read_at' => $recipient['read_at'] ?? null,
            ];
        }, $recipients);
    }

    protected function getSummary($notification)
    {
        $eventType = NotificationEventMap::normalizeEventType($notification['event_type'] ?? '');
        $actorName = $this->getActorName($notification);
        $ticketTitle = $this->getTicketTitle($notification);

        switch ($eventType) {
            case NotificationEventMap::AGENT_MENTIONED:
                return $ticketTitle
                    ? sprintf(__('%1$s mentioned you in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s mentioned you', 'fluent-support'), $actorName);

            case NotificationEventMap::AGENT_REPLIED:
                return $ticketTitle
                    ? sprintf(__('%1$s replied to a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s replied to a ticket', 'fluent-support'), $actorName);

            case NotificationEventMap::TICKET_ASSIGNED:
                return $ticketTitle
                    ? sprintf(__('%1$s assigned a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s assigned a ticket', 'fluent-support'), $actorName);

            case NotificationEventMap::TICKET_REASSIGNED:
                return $ticketTitle
                    ? sprintf(__('%1$s reassigned a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s reassigned a ticket', 'fluent-support'), $actorName);

            case NotificationEventMap::CUSTOMER_REPLIED:
                return $ticketTitle
                    ? sprintf(__('%1$s replied to a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s replied to a ticket', 'fluent-support'), $actorName);

            case NotificationEventMap::TICKET_CLOSED:
                return $ticketTitle
                    ? sprintf(__('%1$s closed a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s closed a ticket', 'fluent-support'), $actorName);

            case NotificationEventMap::TICKET_REOPENED:
                return $ticketTitle
                    ? sprintf(__('%1$s reopened a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s reopened a ticket', 'fluent-support'), $actorName);

            case NotificationEventMap::WORKFLOW_TRIGGERED:
                return $ticketTitle
                    ? sprintf(__('%1$s triggered automation in %2$s', 'fluent-support'), $actorName, $ticketTitle)
                    : sprintf(__('%1$s triggered automation', 'fluent-support'), $actorName);
        }

        return $ticketTitle
            ? sprintf(__('%1$s updated a ticket in %2$s', 'fluent-support'), $actorName, $ticketTitle)
            : sprintf(__('%1$s updated a ticket', 'fluent-support'), $actorName);
    }

    protected function getActorName($notification)
    {
        if (!empty($notification['actor']['full_name'])) {
            return $notification['actor']['full_name'];
        }

        if (!empty($notification['payload']['customer']['name'])) {
            return $notification['payload']['customer']['name'];
        }

        if (!empty($notification['payload']['agent']['name'])) {
            return $notification['payload']['agent']['name'];
        }

        if (!empty($notification['payload']['actor']['name'])) {
            return $notification['payload']['actor']['name'];
        }

        if (!empty($notification['payload']['assigner']['name'])) {
            return $notification['payload']['assigner']['name'];
        }

        return __('Someone', 'fluent-support');
    }

    protected function getTicketTitle($notification)
    {
        if (!empty($notification['ticket']['title'])) {
            return $notification['ticket']['title'];
        }

        if (!empty($notification['payload']['ticket_title'])) {
            return $notification['payload']['ticket_title'];
        }

        return '';
    }
}
