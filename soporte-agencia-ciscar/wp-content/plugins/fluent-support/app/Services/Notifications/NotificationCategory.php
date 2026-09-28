<?php

namespace FluentSupport\App\Services\Notifications;

class NotificationCategory
{
    const MENTIONS = 'mentions';
    const TICKET_ACTIVITY = 'ticket_activity';
    const AUTOMATION_TRIGGERS = 'automation_triggers';

    public static function all()
    {
        return [
            static::MENTIONS,
            static::TICKET_ACTIVITY,
            static::AUTOMATION_TRIGGERS,
        ];
    }

    public static function options()
    {
        return [
            [
                'key'   => 'all',
                'label' => __('All', 'fluent-support')
            ],
            [
                'key'   => static::MENTIONS,
                'label' => __('Mentions', 'fluent-support')
            ],
            [
                'key'   => static::TICKET_ACTIVITY,
                'label' => __('Ticket Activity', 'fluent-support')
            ],
            [
                'key'   => static::AUTOMATION_TRIGGERS,
                'label' => __('Automation Triggers', 'fluent-support')
            ]
        ];
    }

    public static function isValid($category)
    {
        return in_array($category, static::all(), true);
    }
}
