<?php

namespace FluentSupport\Database\Migrations;

class MailBoxMigrator
{
    static $tableName = 'fs_mail_boxes';

    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `name` VARCHAR(192) NOT NULL,
                `slug` VARCHAR(192) NOT NULL,
                `box_type` VARCHAR(50) default 'web',
                `email` VARCHAR(192) NOT NULL,
                `mapped_email` VARCHAR(192) NULL,
                `email_footer` LONGTEXT NULL,
                `settings` LONGTEXT NULL,
                `avatar` VARCHAR(192) NULL,
                `created_by` BIGINT(20) UNSIGNED NULL,
                `is_default` ENUM('yes', 'no') DEFAULT 'no',
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_slug` (`slug`),
                INDEX `idx_email` (`email`),
                INDEX `idx_created_by` (`created_by`),
                INDEX `idx_box_type` (`box_type`)
            ) $charsetCollate;";
            $created = dbDelta($sql);
            return $created;
        } else {
            static::alterTable($table);
        }

        return false;
    }

    public static function alterTable($table)
    {
        static::addMissingIndexes($table);
    }

    public static function addMissingIndexes($table)
    {
        global $wpdb;

        // $table is always $wpdb->prefix . 'fs_mail_boxes' — not user input.
        // esc_sql() is the correct escaping for SQL identifiers; $wpdb->prepare()
        // cannot quote identifiers in WP < 6.2 (no %i placeholder available).
        $table = esc_sql($table);

        // Get existing indexes
        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}`"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing_index_names = [];

        foreach ($existing_indexes as $index) {
            $existing_index_names[] = $index->Key_name;
        }

        // Desired indexes — keys and values are all hardcoded string literals.
        $indexes = [
            'idx_slug'       => 'slug',
            'idx_email'      => 'email',
            'idx_created_by' => 'created_by',
            'idx_box_type'   => 'box_type',
        ];

        // Add missing indexes. $table is esc_sql()'d above; $index_name and
        // $column_name are hardcoded array literals — no user input reaches this query.
        foreach ($indexes as $index_name => $column_name) {
            if (!in_array($index_name, $existing_index_names)) {
                // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- all identifiers are either esc_sql()'d or hardcoded literals.
                $wpdb->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` (`{$column_name}`)");
            }
        }
    }
}
