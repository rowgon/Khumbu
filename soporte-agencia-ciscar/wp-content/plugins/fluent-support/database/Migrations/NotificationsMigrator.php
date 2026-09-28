<?php

namespace FluentSupport\Database\Migrations;

class NotificationsMigrator
{
    static $tableName = 'fs_notifications';

    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `actor_id` BIGINT(20) UNSIGNED NULL,
                `ticket_id` BIGINT(20) UNSIGNED NULL,
                `conversation_id` BIGINT(20) UNSIGNED NULL,
                `event_type` VARCHAR(192) NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `payload` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_conversation_id` (`conversation_id`),
                INDEX `idx_event_type` (`event_type`),
                INDEX `idx_created_at` (`created_at`),
                INDEX `idx_category_created_at` (`category`, `created_at`),
                INDEX `idx_ticket_created_at` (`ticket_id`, `created_at`)
            ) $charsetCollate;";

            return dbDelta($sql);
        }

        return false;
    }
}
