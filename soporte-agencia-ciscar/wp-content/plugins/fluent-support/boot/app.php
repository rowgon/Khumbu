<?php

defined('ABSPATH') or die;

use FluentSupport\Framework\Foundation\Application;
use FluentSupport\App\Hooks\Handlers\ActivationHandler;
use FluentSupport\App\Hooks\Handlers\DeactivationHandler;

return function ($file) {

    require_once FLUENT_SUPPORT_PLUGIN_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

    register_activation_hook($file, function () {
        (new ActivationHandler)->handle();
    });

    register_deactivation_hook($file, function () {
        (new DeactivationHandler)->handle();
    });

    add_action('plugins_loaded', function () use ($file) {
        $application = new Application($file);

        $currentDbVersion = get_option('fluent_support_db_version');
        if (!$currentDbVersion || version_compare($currentDbVersion, FLUENT_SUPPORT_DB_VERSION, '<')) {
            \FluentSupport\Database\DBMigrator::run();
            update_option('fluent_support_db_version', FLUENT_SUPPORT_DB_VERSION, 'no');
        }

        do_action('fluent_support_loaded', $application);
        do_action('fluent_support_addons_loaded', $application);

        // add_action('init', function () {
        //     load_plugin_textdomain('fluent-support', false, 'fluent-support/language/');
        // });

        add_action('fluent_support/admin_app_loaded', function () {
            if (!wp_next_scheduled('fluent_support_hourly_tasks')) {
                wp_schedule_event(time(), 'hourly', 'fluent_support_hourly_tasks');
            }

            if (!wp_next_scheduled('fluent_support_daily_tasks')) {
                wp_schedule_event(time(), 'daily', 'fluent_support_daily_tasks');
            }

            if (!wp_next_scheduled('fluent_support_weekly_tasks')) {
                wp_schedule_event(time(), 'weekly', 'fluent_support_weekly_tasks');
            }

            global $wpdb;
            $table = esc_sql($wpdb->prefix . 'fs_tickets');
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table is sanitized via esc_sql(); no user input.
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM `{$table}` LIKE 'serial_number'");
            if ($column_exists) {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $has_null = $wpdb->get_var("SELECT id FROM `{$table}` WHERE `serial_number` IS NULL LIMIT 1");
                if ($has_null) {
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $wpdb->query("UPDATE `{$table}` SET `serial_number` = `id` WHERE `serial_number` IS NULL");
                }
            }

            /*
             * The below schedule is powered by Action Scheduler by WooCommerce
             * It will run every 30 minutes.
             */
            if (false === as_next_scheduled_action('fluent_support_half_hourly')) {
                as_schedule_recurring_action(time(), 1800, 'fluent_support_half_hourly', [], 'fluent-support', true);
            }

        });

    });
};
