<?php

namespace FluentSupport\App\Services\Notifications;

use InvalidArgumentException;

class NotificationEventMap
{
    const AGENT_MENTIONED = 'agent_mentioned';
    const AGENT_REPLIED = 'agent_replied';
    const TICKET_ASSIGNED = 'ticket_assigned';
    const TICKET_REASSIGNED = 'ticket_reassigned';
    const CUSTOMER_REPLIED = 'customer_replied';
    const TICKET_CLOSED = 'ticket_closed';
    const TICKET_REOPENED = 'ticket_reopened';
    const WORKFLOW_TRIGGERED = 'workflow_triggered';

    public static function normalizeEventType($eventType)
    {
        return static::legacyEventMap()[$eventType] ?? $eventType;
    }

    public static function all()
    {
        return array_keys(static::categoryMap());
    }

    public static function categoryMap()
    {
        return [
            static::AGENT_MENTIONED  => NotificationCategory::MENTIONS,
            static::AGENT_REPLIED    => NotificationCategory::TICKET_ACTIVITY,
            static::TICKET_ASSIGNED  => NotificationCategory::TICKET_ACTIVITY,
            static::TICKET_REASSIGNED => NotificationCategory::TICKET_ACTIVITY,
            static::CUSTOMER_REPLIED => NotificationCategory::TICKET_ACTIVITY,
            static::TICKET_CLOSED    => NotificationCategory::TICKET_ACTIVITY,
            static::TICKET_REOPENED  => NotificationCategory::TICKET_ACTIVITY,
            static::WORKFLOW_TRIGGERED => NotificationCategory::AUTOMATION_TRIGGERS,
        ];
    }

    public static function legacyEventMap()
    {
        return [
            'fluent_support/response_added_by_agent' => static::AGENT_REPLIED,
            'fluent_support/note_added_by_agent'     => static::AGENT_MENTIONED,
        ];
    }

    public static function displayMap()
    {
        return [
            static::AGENT_MENTIONED => [
                'label' => __('mentioned you', 'fluent-support'),
                'icon'  => 'reply'
            ],
            static::AGENT_REPLIED => [
                'label' => __('replied to a ticket', 'fluent-support'),
                'icon'  => 'reply'
            ],
            static::TICKET_ASSIGNED => [
                'label' => __('assigned a ticket', 'fluent-support'),
                'icon'  => 'agent'
            ],
            static::TICKET_REASSIGNED => [
                'label' => __('reassigned a ticket', 'fluent-support'),
                'icon'  => 'agent'
            ],
            static::CUSTOMER_REPLIED => [
                'label' => __('replied to a ticket', 'fluent-support'),
                'icon'  => 'reply'
            ],
            static::TICKET_CLOSED => [
                'label' => __('closed a ticket', 'fluent-support'),
                'icon'  => 'closeTicket'
            ],
            static::TICKET_REOPENED => [
                'label' => __('reopened a ticket', 'fluent-support'),
                'icon'  => 'refresh'
            ],
            static::WORKFLOW_TRIGGERED => [
                'label' => __('triggered automation', 'fluent-support'),
                'icon'  => 'workflow'
            ]
        ];
    }

    public static function displayData($eventType)
    {
        $eventType = static::normalizeEventType($eventType);

        return static::displayMap()[$eventType] ?? [
            'label' => __('updated a ticket', 'fluent-support'),
            'icon'  => 'reply'
        ];
    }

    public static function resolveCategory($eventType)
    {
        $eventType = static::normalizeEventType($eventType);
        $category = static::categoryMap()[$eventType] ?? null;

        if (!$category) {
            throw new InvalidArgumentException('Unsupported notification event type: ' . esc_html($eventType));
        }

        return $category;
    }

    public static function isValid($eventType)
    {
        return array_key_exists($eventType, static::categoryMap());
    }
}
