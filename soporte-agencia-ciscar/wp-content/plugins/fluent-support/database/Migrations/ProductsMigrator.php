<?php

namespace FluentSupport\Database\Migrations;

class ProductsMigrator
{
    static $tableName = 'fs_products';

    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `source_uid` BIGINT(20) UNSIGNED NULL,
                `mailbox_id` BIGINT(20) UNSIGNED NULL,
                `title` VARCHAR(192) NULL,
                `description` TEXT NULL,
                `settings` LONGTEXT NULL,
                `source` VARCHAR(100) DEFAULT 'local',
                `created_by` BIGINT(20) UNSIGNED NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_mailbox_id` (`mailbox_id`),
                INDEX `idx_created_at` (`created_at`),
                INDEX `idx_source` (`source`)
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

        // $table is always $wpdb->prefix . 'fs_products' — not user input.
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
            'idx_mailbox_id' => 'mailbox_id',
            'idx_created_at' => 'created_at',
            'idx_source'     => 'source',
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
