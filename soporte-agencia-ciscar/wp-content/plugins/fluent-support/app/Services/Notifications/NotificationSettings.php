<?php

namespace FluentSupport\App\Services\Notifications;

use FluentSupport\App\Services\Helper;
use FluentSupport\Database\Migrations\NotificationsMigrator;
use FluentSupport\Database\Migrations\NotificationUsersMigrator;

class NotificationSettings
{
    const OPTION_KEY = '_internal_notification_settings';

    public function get($cached = true)
    {
        static $settings;

        if ($cached && $settings) {
            return $settings;
        }

        $settings = $this->normalize(wp_parse_args(Helper::getOption(static::OPTION_KEY, []), $this->defaults()));
        $settings['enabled'] = $this->getGlobalEnabled();

        return $settings;
    }

    public function save(array $settings)
    {
        $settings = $this->normalize(wp_parse_args($settings, $this->defaults()));

        if ($settings['enabled'] === 'yes') {
            $this->ensureNotificationTables();
        }

        return Helper::updateOption(static::OPTION_KEY, $settings);
    }

    public function defaults()
    {
        return [
            'enabled'                   => 'no',
            'assignment_notifications'  => 'yes',
            'agent_reply_notifications' => 'yes',
            'customer_reply_notifications' => 'yes',
            'workflow_notifications'    => 'yes',
            'status_notifications'      => 'yes',
            'self_notifications'        => 'no'
        ];
    }

    public function getFields()
    {
        return [
            'enabled' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Internal Notifications', 'fluent-support'),
                'inline_help'    => __('Enable the in-app notification bell, unread counts, and notification event storage for agents.', 'fluent-support')
            ],
            'assignment_notifications' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Assignment Notifications', 'fluent-support'),
                'dependency'     => [
                    'depends_on' => 'enabled',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
            'customer_reply_notifications' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Customer Reply Notifications', 'fluent-support'),
                'dependency'     => [
                    'depends_on' => 'enabled',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
            'workflow_notifications' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Workflow Notifications', 'fluent-support'),
                'dependency'     => [
                    'depends_on' => 'enabled',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
            'status_notifications' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Ticket Status Notifications', 'fluent-support'),
                'dependency'     => [
                    'depends_on' => 'enabled',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
            'agent_reply_notifications' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Notify Assigned Agent When Another Agent Replies', 'fluent-support'),
                'dependency'     => [
                    'depends_on' => 'enabled',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
            'self_notifications' => [
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Allow Self Notifications', 'fluent-support'),
                'inline_help'    => __('By default, agents are not notified for their own actions. Enable this only if agents should receive notifications when they are already the normal recipient for an action they performed themselves.', 'fluent-support'),
                'dependency'     => [
                    'depends_on' => 'enabled',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
        ];
    }

    public function isEnabled()
    {
        return $this->get()['enabled'] === 'yes';
    }

    public function canUseNotificationTables($ensureTables = false)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ($ensureTables) {
            $this->ensureNotificationTables();
        }

        return $this->notificationTablesExist();
    }

    public function allowsSelfNotifications()
    {
        return $this->get()['self_notifications'] === 'yes';
    }

    public function isEventEnabled($eventType)
    {
        $eventType = NotificationEventMap::normalizeEventType($eventType);
        $settings = $this->get();

        $eventSettingsMap = [
            NotificationEventMap::TICKET_ASSIGNED   => 'assignment_notifications',
            NotificationEventMap::TICKET_REASSIGNED => 'assignment_notifications',
            NotificationEventMap::AGENT_REPLIED     => 'agent_reply_notifications',
            NotificationEventMap::CUSTOMER_REPLIED  => 'customer_reply_notifications',
            NotificationEventMap::TICKET_CLOSED     => 'status_notifications',
            NotificationEventMap::TICKET_REOPENED   => 'status_notifications',
            NotificationEventMap::WORKFLOW_TRIGGERED => 'workflow_notifications',
        ];

        $settingKey = $eventSettingsMap[$eventType] ?? null;

        if (!$settingKey) {
            return true;
        }

        return ($settings[$settingKey] ?? 'yes') === 'yes';
    }

    protected function normalize(array $settings)
    {
        $toggleKeys = [
            'enabled',
            'assignment_notifications',
            'agent_reply_notifications',
            'customer_reply_notifications',
            'workflow_notifications',
            'status_notifications',
            'self_notifications',
        ];

        foreach ($toggleKeys as $toggleKey) {
            $settings[$toggleKey] = (($settings[$toggleKey] ?? 'no') === 'yes') ? 'yes' : 'no';
        }

        unset($settings['polling_interval']);
        unset($settings['mention_notifications']);
        unset($settings['enabled_categories']);

        return $settings;
    }

    protected function getGlobalEnabled()
    {
        $globalSettings = Helper::getOption('global_business_settings', []);

        if (is_array($globalSettings) && array_key_exists('internal_notifications_enabled', $globalSettings)) {
            return $globalSettings['internal_notifications_enabled'] === 'yes' ? 'yes' : 'no';
        }

        return 'no';
    }

    public function ensureNotificationTables()
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        NotificationsMigrator::migrate();
        NotificationUsersMigrator::migrate();
    }

    public function notificationTablesExist()
    {
        global $wpdb;

        $notificationsTable = $wpdb->prefix . NotificationsMigrator::$tableName;
        $notificationUsersTable = $wpdb->prefix . NotificationUsersMigrator::$tableName;

        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $notificationsTable)) === $notificationsTable
            && $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $notificationUsersTable)) === $notificationUsersTable;
    }
}
