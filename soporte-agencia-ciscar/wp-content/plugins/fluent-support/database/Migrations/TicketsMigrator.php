<?php

namespace FluentSupport\Database\Migrations;

class TicketsMigrator
{
    static $tableName = 'fs_tickets';

    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . static::$tableName;

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) != $table) {
            $sql = "CREATE TABLE $table (
                `id` BIGINT(20) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `customer_id` BIGINT(20) UNSIGNED NULL,
                `agent_id` BIGINT(20) UNSIGNED NULL,
                `mailbox_id` BIGINT(20) UNSIGNED NULL,
                `product_id` BIGINT(20) UNSIGNED NULL,
                `product_source` VARCHAR(192) NULL,
                `privacy` VARCHAR(100) DEFAULT 'private',
                `priority` VARCHAR(100) DEFAULT 'normal',
                `client_priority` VARCHAR(100) DEFAULT 'normal',
                `status` VARCHAR(100) DEFAULT 'new',
                `title` VARCHAR(192) NULL,
                `slug` VARCHAR(192) NULL,
                `hash` VARCHAR(192) NULL,
                `content_hash` VARCHAR(192) NULL,
                `message_id` VARCHAR(192) NULL,
                `source` VARCHAR(192) NULL,
                `content` LONGTEXT NULL,
                `secret_content` LONGTEXT NULL,
                `last_agent_response` TIMESTAMP NULL,
                `last_customer_response` TIMESTAMP NULL,
                `waiting_since` TIMESTAMP NULL,
                `response_count` INT(11) DEFAULT 0,
                `first_response_time` INT(11) NULL, /* Seconds took for first contact */
                `total_close_time` INT(11) NULL, /* Seconds took for closing this ticket */
                `resolved_at` TIMESTAMP NULL,
                `closed_by` BIGINT(20) UNSIGNED NULL,
                `created_by` BIGINT(20) UNSIGNED NULL,
                `serial_number` BIGINT UNSIGNED NULL,
                `ticket_number` VARCHAR(192) NULL DEFAULT NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                INDEX `idx_customer_id` (`customer_id`),
                INDEX `idx_agent_id` (`agent_id`),
                INDEX `idx_mailbox_id` (`mailbox_id`),
                INDEX `idx_product_id` (`product_id`),
                INDEX `idx_priority` (`priority`),
                INDEX `idx_client_priority` (`client_priority`),
                INDEX `idx_status` (`status`),
                INDEX `idx_created_at` (`created_at`),
                INDEX `idx_resolved_at` (`resolved_at`),
                INDEX `idx_status_resolved_at` (`status`, `resolved_at`),
                INDEX `idx_ticket_number` (`ticket_number`(191)),
                INDEX `idx_waiting_since_id` (`waiting_since`, `id`),
                INDEX `idx_updated_at_id` (`updated_at`, `id`),
                INDEX `idx_response_count_id` (`response_count`, `id`),
                UNIQUE KEY `uniq_serial_number` (`serial_number`)
            ) $charsetCollate;";
            $created = dbDelta($sql);
            return $created;
        } else {
            static::alterTable($table);        }

        return false;
    }

    public static function alterTable($table)
    {
        static::addMissingColumns($table);
        static::addMissingIndexes($table);
    }

    public static function addMissingColumns($table)
    {
        global $wpdb;

        // $table is always $wpdb->prefix . 'fs_tickets' — not user input.
        // esc_sql() is the correct escaping for SQL identifiers; $wpdb->prepare()
        // cannot quote identifiers in WP < 6.2 (no %i placeholder available).
        $table = esc_sql($table);

        // Get existing columns
        $existing_columns = $wpdb->get_col("DESC `{$table}`", 0); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Add waiting_since column if missing (beta user migration)
        if (!in_array('waiting_since', $existing_columns)) {
            // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- $table is sanitized via esc_sql(); column name is a hardcoded literal.
            $wpdb->query("ALTER TABLE `{$table}` ADD `waiting_since` TIMESTAMP NULL AFTER `last_customer_response`");
        }

        // Add created_by column to track agent who created ticket on behalf of customer
        if (!in_array('created_by', $existing_columns)) {
            // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- $table is sanitized via esc_sql(); column name is a hardcoded literal.
            $wpdb->query("ALTER TABLE `{$table}` ADD `created_by` BIGINT(20) UNSIGNED NULL AFTER `closed_by`");
        }

        if (!in_array('serial_number', $existing_columns)) {
            // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- $table is sanitized via esc_sql(); column name is a hardcoded literal.
            $wpdb->query("ALTER TABLE `{$table}` ADD `serial_number` BIGINT UNSIGNED NULL AFTER `created_by`");

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table is sanitized via esc_sql().
            $wpdb->query("UPDATE `{$table}` SET `serial_number` = `id` WHERE `serial_number` IS NULL");
        }

        if (!in_array('ticket_number', $existing_columns)) {
            // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- $table is sanitized via esc_sql(); column name is a hardcoded literal.
            $wpdb->query("ALTER TABLE `{$table}` ADD `ticket_number` VARCHAR(192) NULL DEFAULT NULL AFTER `serial_number`");
        }

    }

    public static function addMissingIndexes($table)
    {
        global $wpdb;

        // $table is always $wpdb->prefix . 'fs_tickets' — not user input.
        // esc_sql() is the correct escaping for SQL identifiers; $wpdb->prepare()
        // cannot quote identifiers in WP < 6.2 (no %i placeholder available).
        $table = esc_sql($table);

        // Get existing indexes
        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM `{$table}`"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing_index_names = [];

        foreach ($existing_indexes as $index) {
            $existing_index_names[] = $index->Key_name;
        }

        // Desired indexes — keys and values (including composite column lists) are
        // all hardcoded string literals; no user input reaches these queries.
        $indexes = [
            'idx_customer_id'      => '`customer_id`',
            'idx_agent_id'         => '`agent_id`',
            'idx_mailbox_id'       => '`mailbox_id`',
            'idx_product_id'       => '`product_id`',
            'idx_priority'         => '`priority`',
            'idx_client_priority'  => '`client_priority`',
            'idx_status'           => '`status`',
            'idx_created_at'       => '`created_at`',
            'idx_resolved_at'      => '`resolved_at`',
            'idx_status_resolved_at' => '`status`, `resolved_at`',
            'idx_ticket_number'    => '`ticket_number`(191)',
            'uniq_serial_number'   => '`serial_number`',
            'idx_waiting_since_id'  => '`waiting_since`, `id`',
            'idx_updated_at_id'     => '`updated_at`, `id`',
            'idx_response_count_id' => '`response_count`, `id`',
        ];
        // Add missing indexes. $table is esc_sql()'d above; $index_name and
        // $columns are hardcoded array literals — no user input reaches this query.
        foreach ($indexes as $index_name => $columns) {
            if (!in_array($index_name, $existing_index_names)) {
                // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- all identifiers are either esc_sql()'d or hardcoded literals.
                $indexType = $index_name === 'uniq_serial_number' ? 'UNIQUE KEY' : 'INDEX';
                $wpdb->query("ALTER TABLE `{$table}` ADD {$indexType} `{$index_name}` ({$columns})");
            }
        }
    }
}
