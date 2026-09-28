<?php
/**
 * Plugin Name:		Maintenance Mode
 * Plugin URI:		https://helderk.com/
 * Description:		Simple Maintenance Mode for Developers
 * Version:			3.2.1
 * Tested up to:	7.0.2
 * Text Domain:		hkdev-maintenance-mode
 * Domain Path:		/languages/
 * License:			GPLv2 or later
 * Author:			helderk
 * Copyright:
 *  Modifications: helderk 2020-2025
 *  Original: Jack Finch 2010-2012 and Peter Hardy-vanDoorn 2018
 * Donate link: https://paypal.me/helderk
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Stop direct access to the plugin files for security.
if (!defined('ABSPATH')) die(); // exit if accessed directly

// Load the main plugin class that contains the maintenance mode logic.
include_once( plugin_dir_path( __FILE__ ) . 'class-hkdev-maintenance-mode.php' );

// Initialize the plugin after WordPress has loaded core functions.
add_action('init', 'hkdev_maintenance_mode_initialize');

/**
 * Prepare the plugin state when it is activated.
 *
 * This runs once during activation and initializes the maintenance mode class.
 */
function hkdev_maintenance_mode_activate() {
    if (class_exists('HkDevMaintenanceMode')) {
        $maintenance_mode = new HkDevMaintenanceMode();
        $maintenance_mode->init();
    }
}

register_activation_hook(__FILE__, 'hkdev_maintenance_mode_activate');

/**
 * Main bootstrap function for the plugin.
 *
 * This loads translations, creates the core plugin object, registers the
 * admin page, loads editor assets on the settings screen, and wires the
 * maintenance-mode hooks and AJAX actions used by the plugin.
 */
function hkdev_maintenance_mode_initialize(){

	// Load translations from the languages folder.
	load_plugin_textdomain('hkdev-maintenance-mode', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    if (class_exists("HkDevMaintenanceMode")) {
        global $hkdev_MM;
        $hkdev_MM = new HkDevMaintenanceMode();
    }

	// Register the admin settings page for users who can manage options.
	if (!function_exists("hkdev_maintenance_mode_ap")) {
		function hkdev_maintenance_mode_ap() {
			if (current_user_can('manage_options')) {
				global $hkdev_MM;
				global $ajax_nonce; 
				$ajax_nonce = wp_create_nonce("hkdev_nonce"); 
				if (!isset($hkdev_MM)) return;
				if (function_exists('add_options_page')) {
					add_options_page( 
						__("Maintenance Mode", 'hkdev-maintenance-mode'), 
						__("Maintenance Mode", 'hkdev-maintenance-mode'), 
						'manage_options', 
						'hkdev_Maintenance_Mode', 
						array($hkdev_MM, 'print_admin_page')
					);
				}
			}
		}
	}

	// Load CodeMirror and Select2 assets only on the plugin settings page.
	if (!function_exists("hkdev_codemirror_enqueue_scripts")) {
		function hkdev_codemirror_enqueue_scripts($hook_suffix) {
			if ($hook_suffix == 'settings_page_hkdev_Maintenance_Mode') {
				if (function_exists('wp_enqueue_code_editor')) {
					$cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'text/html'));
					wp_localize_script('jquery', 'cm_settings', $cm_settings);
				}

				if (wp_style_is('wp-codemirror', 'registered')) {
					wp_enqueue_style('wp-codemirror');
				}

				wp_enqueue_style('hkdev_select2', plugin_dir_url(__FILE__) . '/assets/select2.min.css', array(), '4.1.0' );
				wp_enqueue_script('hkdev_select2', plugin_dir_url(__FILE__) . '/assets/select2.min.js', array('jquery'), '4.1.0', true );
			}
		}
	}

	// Register the WordPress hooks, filters and AJAX actions used by the plugin.
	if( isset( $hkdev_MM ) ) {

		// Disable the REST API when maintenance mode is enabled.
	 	$admin_options = $hkdev_MM->get_admin_options();
		if($admin_options['enable_mm']=='YES') {
			$disable_rest_api = plugin_dir_path( __FILE__ ) . 'hkdev-disable-rest-api.php';
			if( file_exists( $disable_rest_api ) ) {
				include_once( $disable_rest_api );
				
			} else {
				//add WP notice notice-error
				add_action( 'admin_notices', 'hkdev_maintenance_mode_rest_api_error_notice' );
			}
		}
	

		// Register the main admin and frontend actions.
		add_action( 'admin_menu',			 'hkdev_maintenance_mode_ap' );
		add_action( 'admin_bar_menu',		 array( $hkdev_MM, 'ab_indicator'), 100 ); //hk
		add_action( 'admin_head',			 array( $hkdev_MM, 'ab_indicator_style' ) ); //hk
		add_action( 'send_headers',			 array( $hkdev_MM, 'process_redirect'), 1 );
		add_action( 'admin_notices',		 array( $hkdev_MM, 'display_status_if_active' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($hkdev_MM, 'action_links'));
		
		// Register the AJAX endpoints used by the settings page.
		add_action( 'wp_ajax_hkdev_mm_getposts',  array( $hkdev_MM, 'get_posts_ajax_callback') ); // wp_ajax_{action}
		add_action( 'wp_ajax_hkdev_mm_toggle_maintenance_mode', array( $hkdev_MM, 'toggle_maintenance_mode') );
		add_action( 'wp_ajax_hkdev_mm_add_ip',    array( $hkdev_MM, 'add_new_ip'       ) );
		add_action( 'wp_ajax_hkdev_mm_toggle_ip', array( $hkdev_MM, 'toggle_ip_status' ) );
		add_action( 'wp_ajax_hkdev_mm_delete_ip', array( $hkdev_MM, 'delete_ip'        ) );
		add_action( 'wp_ajax_hkdev_mm_add_ak',    array( $hkdev_MM, 'add_new_ak'       ) );
		add_action( 'wp_ajax_hkdev_mm_toggle_ak', array( $hkdev_MM, 'toggle_ak_status' ) );
		add_action( 'wp_ajax_hkdev_mm_delete_ak', array( $hkdev_MM, 'delete_ak'        ) );
		add_action( 'wp_ajax_hkdev_mm_resend_ak', array( $hkdev_MM, 'resend_ak'        ) );

		add_action( 'admin_enqueue_scripts', 'hkdev_codemirror_enqueue_scripts' ); //hk
		
	}

}

function hkdev_maintenance_mode_rest_api_error_notice() {
	echo '<div class="notice notice-error"><p>' . esc_html__( 'The REST API is not disabled because the plugin has encountered an error. Please reinstall the plugin.', 'hkdev-maintenance-mode' ) . '</p></div>';
}
