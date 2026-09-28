<?php

namespace FluentSupport\Database\Migrations;

class MetaMigrator
{
    static $tableName = 'fs_meta';

    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `object_type` VARCHAR(192) NULL,
                `object_id` BIGINT(20) NULL,
                `key` VARCHAR(192) NULL,
                `value` LONGTEXT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_object_type` (`object_type`),
                INDEX `idx_object_id` (`object_id`),
                INDEX `idx_key` (`key`),
                INDEX `idx_object_type_created_at` (`object_type`, `created_at`)
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

        // $table is always $wpdb->prefix . 'fs_meta' — not user input.
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
            'idx_object_type' => ['object_type'],
            'idx_object_id'   => ['object_id'],
            'idx_key'         => ['key'],
            // Serves CleanupHandler::cleanExpiredAuthChallenges(), which purges
            // rows by object_type + created_at on every hourly cron run.
            'idx_object_type_created_at' => ['object_type', 'created_at'],
        ];

        // Add missing indexes. $table is esc_sql()'d above; $index_name and
        // $columns are hardcoded array literals — no user input reaches this query.
        foreach ($indexes as $index_name => $columns) {
            if (!in_array($index_name, $existing_index_names)) {
                $columnList = '`' . implode('`, `', $columns) . '`';
                // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- all identifiers are either esc_sql()'d or hardcoded literals.
                $wpdb->query("ALTER TABLE `{$table}` ADD INDEX `{$index_name}` ({$columnList})");
            }
        }
    }
}
