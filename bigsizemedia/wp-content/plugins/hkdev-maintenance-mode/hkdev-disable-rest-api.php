<?php

/**
 * Restrict access to the WordPress REST API while maintenance mode is active.
 *
 * This file is included only when the plugin is enabled and maintenance mode is on.
 * Its purpose is to prevent unauthenticated visitors from interacting with the REST API
 * while the site is in maintenance mode.
 */

// Stop direct access to the plugin file for security.
if (!defined('ABSPATH')) {
    die();
}

// Remove REST API links from the page head and headers so the API is not exposed.
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
remove_action('template_redirect', 'rest_output_link_header', 11);

// Register the REST API restriction hook depending on the WordPress version.
if (version_compare(get_bloginfo('version'), '4.7', '>=')) {
    add_filter('rest_authentication_errors', 'disable_wp_rest_api');
} else {
    disable_wp_rest_api_legacy();
}

/**
 * Block REST API access for unauthenticated users unless they are explicitly allowed.
 *
 * The function returns the original access value when the request is allowed,
 * or a WP_Error object that forces WordPress to reject the request.
 */
function disable_wp_rest_api($access) {
    global $hkdev_MM;

    if (is_user_logged_in()) {
        return $access;
    }

    if (disable_wp_rest_api_allow_access()) {
        return $access;
    }

    if (isset($hkdev_MM) && is_object($hkdev_MM) && method_exists($hkdev_MM, 'is_request_exempt_from_maintenance')) {
        if ($hkdev_MM->is_request_exempt_from_maintenance()) {
            return $access;
        }
    }

    $message = apply_filters('disable_wp_rest_api_error', __('REST API restricted to authenticated users.', 'hkdev-maintenance-mode'));
    return new WP_Error('rest_login_required', $message, array('status' => rest_authorization_required_code()));
}

/**
 * Allow certain requests through the restriction layer when needed.
 *
 * This can be used by other code or filters to explicitly permit selected requests.
 */
function disable_wp_rest_api_allow_access() {

    $post_var   = apply_filters('disable_wp_rest_api_post_var', false);
	$server_var = apply_filters('disable_wp_rest_api_server_var', false);
	
	if (!empty($post_var)) {
		if (is_array($post_var)) {
			foreach($post_var as $var) {
				if (isset($_POST[$var]) && !empty($_POST[$var])) return true;
			}
		} else {
			if (isset($_POST[$post_var]) && !empty($_POST[$post_var])) return true;
		}
	}
	
	if (!empty($server_var)) {
		if (is_array($server_var)) {
			foreach($server_var as $var) {
				if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === $var) return true;
			}
		} else {
			if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === $server_var) return true;
		}
	}
	
	return false;
}

/**
 * Legacy fallback for older WordPress versions.
 *
 * Older versions use different filters to disable the REST API, so this helper
 * disables both the old JSON API and the newer REST API endpoints.
 */
function disable_wp_rest_api_legacy() {
    // REST API 1.x
    add_filter('json_enabled', '__return_false');
    add_filter('json_jsonp_enabled', '__return_false');
    // REST API 2.x
    add_filter('rest_enabled', '__return_false');
    add_filter('rest_jsonp_enabled', '__return_false');
}

