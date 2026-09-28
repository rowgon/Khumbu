<?php

// Exit early if the file is accessed directly instead of through WordPress.
if (!defined('ABSPATH')) {
    exit();
}

// Ensure this file only runs during plugin uninstallation.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

/**
 * Remove all data created by the maintenance mode plugin.
 *
 * This is executed when WordPress deletes the plugin from the admin interface.
 * It removes the custom database tables used to store access keys and unrestricted IPs,
 * and deletes the plugin options stored in the options table.
 */
function hkdev_delete_plugin() {
    global $wpdb;

    // List of custom tables created by the plugin.
    $tables = array(
        $wpdb->prefix . 'hkdev_mm_access_keys',
        $wpdb->prefix . 'hkdev_mm_unrestricted_ips',
    );

    // Drop each table if it exists.
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }

    // Remove plugin options from the WordPress options table.
    delete_option('hkdev_mm');
    delete_option('hkdev_maintenance_mode_version');
}

// Run the cleanup routine.
hkdev_delete_plugin();