<?php

namespace FluentSupport\Database\Migrations;

class NotificationUsersMigrator
{
    static $tableName = 'fs_notification_users';

    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `notification_id` BIGINT(20) UNSIGNED NOT NULL,
                `user_id` BIGINT(20) UNSIGNED NOT NULL,
                `channel` VARCHAR(100) NOT NULL DEFAULT 'web',
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `read_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_notification_id` (`notification_id`),
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_user_is_read_created_at` (`user_id`, `is_read`, `created_at`),
                INDEX `idx_user_notification_id` (`user_id`, `notification_id`),
                UNIQUE KEY `uniq_notification_user` (`notification_id`, `user_id`)
            ) $charsetCollate;";

            return dbDelta($sql);
        }

        return false;
    }
}
