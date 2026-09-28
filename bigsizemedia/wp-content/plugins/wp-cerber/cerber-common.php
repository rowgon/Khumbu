<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licensed under the GNU GPL.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/*

*========================================================================*
|                                                                        |
|	       ATTENTION!  Do not change or edit this file!                  |
|                                                                        |
*========================================================================*

*/

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

const MYSQL_FETCH_OBJECT = 5;
const MYSQL_FETCH_OBJECT_K = 6;
const CRB_IP_NET_RANGE = '/[^a-f\d\-\.\:\*\/]+/i';
const CRB_SANITIZE_KEY = '/[^a-z_\-\d.:\/]/i';
const CRB_SANITIZE_ID = '/[^\w-]/i';
const CRB_GROOVE = 'cerber_groove';
const CRB_NEXT_LVL = 2147483647; // May not be changed ever, a marker of using role-based or global policies since 9.6

// Should be defined here
const CRB_ALL_ALERTS = '_cerber_subs';

// Known alert channels
const CRB_CHANNELS = array( 'email' => 1, 'pushbullet' => 1 );

// Event IDs

const CRB_EV_LIN = 5;
const CRB_EV_LFL = 7;
const CRB_EV_IPL = 10;
const CRB_EV_NEL = 11;
const CRB_EV_CMS = 15;
const CRB_EV_SCD = 16;
const CRB_EV_SFD = 17;
const CRB_EV_PUR = 50;
const CRB_EV_LDN = 53;

const CRB_EV_PRS = 21;
const CRB_EV_UST = 22;
const CRB_EV_PRD = 25;

// Status IDs

const CRB_STS_25 = 25;
const CRB_STS_29 = 29;
const CRB_STS_30 = 30;

const CRB_STS_11 = 11;
const CRB_STS_51 = 51;
const CRB_STS_52 = 52;
const CRB_STS_532 = 532;

// Storage keys for dashboard messages and announcements
const CRB_ADMIN_NOTICE = 'admin_notice';
const CRB_ADMIN_MESSAGE = 'admin_message';
const CRB_ADMIN_WIDE = 'cerber_admin_wide';
const CRB_ADMIN_ANNOUNCE = 'cerber_admin_info';

/**
 * Known WP scripts
 * @since 6.0
 *
 */
function cerber_get_wp_scripts() {
	$scripts = array( WP_LOGIN_SCRIPT, WP_REG_URI, WP_XMLRPC_SCRIPT, WP_TRACKBACK_SCRIPT, WP_PING_SCRIPT, WP_SIGNUP_SCRIPT );
	if ( ! cerber_is_custom_comment() ) {
		$scripts[] = WP_COMMENT_SCRIPT;
	}

	return array_map( function ( $e ) {
		return '/' . $e;
	}, $scripts );
}

/**
 * Returns WP Cerber nonce
 *
 * @param string $action
 *
 * @return false|string
 *
 * @since 8.9.5.3
 */
function crb_create_nonce( $action = 'control' ) {
	static $cache = array();

	if ( ! isset( $cache[ $action ] ) ) {
		crb_load_dependencies( 'wp_create_nonce', true );
		$cache[ $action ] = crb_sanitize_id( wp_create_nonce( $action ) );
	}

	return $cache[ $action ];
}

/**
 * Return Site URL + /wp-admin/admin.php
 *
 * @return string
 *
 * @since 8.9.5.3
 */
function crb_get_admin_base(){
	static $adm_base;

	if ( ! isset( $adm_base ) ) {

		if ( nexus_is_valid_request() ) {
			$base = crb_sanitize_base_admin_url( nexus_request_data()->base );
		}
		else {
			$base = ( ! is_multisite() ) ? admin_url() : network_admin_url();
		}

		$adm_base = rtrim( $base, '/' ) . '/admin.php';
	}

	return $adm_base;
}

/**
 * Return an HTML-escaped URL for an admin page. The result is safe to use in an HTML context, such as 'href' attributes.
 *
 * This is a convenient wrapper function that combines URL generation and HTML escaping in a single call.
 *
 * The output of this function is NOT a raw URL. It contains HTML entities (e.g., '&amp;').
 * Do NOT use the output of this function for non-HTML contexts like HTTP 'Location' headers or plain text emails.
 *
 * @param string $tab_id Tab that identifies the admin page.
 * @param array $args Query parameters to add to the final admin URL.
 * @param bool $add_nonce If true, adds a WordPress nonce to the URL.
 *
 * @return string The HTML-safe, escaped URL string.
 *
 * @since 9.6.9.4
 *
 * @version Sequoia
 */
function crb_admin_link_for_html( string $tab_id = '', array $args = [], bool $add_nonce = false ): string {
	$raw_url = cerber_admin_link( $tab_id, $args, $add_nonce );

	return crb_escape_url( $raw_url );
}

/**
 * Returns a link (full URL) to a WP Cerber's admin page.
 * Constructs a raw URL with a given tab and query string parameters.
 *
 * IMPORTANT: The returned URL is a raw URL. The caller is RESPONSIBLE for
 * escaping it correctly for the final output context (e.g., using a function
 * `crb_escape_url()` before printing it in an HTML attribute).
 *
 * @param string $tab Tab that identifies the admin page.
 * @param array $args Query parameters to add to the final admin URL.
 * @param bool $add_nonce If true, adds a WordPress nonce to the URL.
 *
 * @return string The raw, unescaped, fully-formed URL.
 *
 * @see crb_escape_url()
 *
 * @version Sequoia
 *
 */
function cerber_admin_link( string $tab = '', array $args = [], bool $add_nonce = false ): string {

	$tab = $tab ?: $args['tab'] ?? '';
	unset( $args['tab'] );

	$page = $args['page'] ?? '';
	unset( $args['page'] );

	// Determine page parameter since it's mandatory

	if ( ! $page && $tab ) {
		if ( ! function_exists( 'crb_determine_page' ) ) {
			cerber_load_admin_code();
		}
		$page = crb_determine_page( $tab );
		$tab = ( $tab == $page ) ? '' : $tab;
	}

	if ( ! $page ) {
		$page = 'cerber-security';
	}

	$query_params = [];
	$query_params['page'] = crb_sanitize_id( $page );

	if ( $tab ) {
		$query_params['tab'] = crb_sanitize_id( $tab );
	}

	$query_params = array_merge( $query_params, $args );

	if ( $add_nonce ) {
		$query_params['cerber_nonce'] = crb_create_nonce();
	}

	$base_link = crb_get_admin_base();

	if ( empty( $query_params ) ) {
		return $base_link;
	}

	return $base_link . '?' . http_build_query( $query_params );
}

/**
 * Return a modified link to the currently displaying WP Cerber admin page.
 * Return a link to the WP Cerber dashboard if called outside WP Cerber admin pages.
 * Note: use `_crb_admin_link_add()` when creating links for UI Factory elements and helpers.
 *
 * @param array $args Query parameters to add to the link to the currently displayed WP Cerber admin page.
 * @param bool $preserve If true, preservers GET parameters of the current request other than 'page' and 'tab'.
 * @param bool $add_nonce If true, add a WP Cerber's nonce.
 *
 * @return string Absolute URL, escaped and safe to use in HTML context
 */
function cerber_admin_link_add( $args = array(), $preserve = false, $add_nonce = true ): string {
	return crb_escape_url( _crb_admin_link_add( $args, $preserve, $add_nonce ) );
}

/**
 * Return a modified link to the currently displaying WP Cerber admin page.
 * Return a link to the WP Cerber dashboard if called outside WP Cerber admin pages.
 *
 * @param array $args Query parameters to add to the link to the currently displayed WP Cerber admin page.
 * @param bool $preserve If true, preservers GET parameters of the current request other than 'page' and 'tab'.
 * @param bool $add_nonce If true, add a WP Cerber's nonce.
 *
 * @return string Unescaped absolute URL
 */
function _crb_admin_link_add( $args = array(), $preserve = false, $add_nonce = true ): string {
	$filter = $preserve ? array() : [ 'tab', 'page' ];
	$params = crb_get_referrer_params( $filter );
	$params = array_merge( $params, $args );

	return cerber_admin_link( '', $params, $add_nonce );
}

/**
 * Generates a raw URL to the Activity log page with optional filters by activities and a status.
 *
 * IMPORTANT: The returned URL is a raw URL. The caller is RESPONSIBLE for
 * escaping it correctly for the final output context (e.g., with crb_escape_url()).
 *
 * @param array $activity_ids An array of activity IDs to filter by.
 * @param int   $status_id    A status ID to filter by.
 *
 * @return string The raw, fully-formed URL to the Activity log.
 *
 * @see crb_escape_url()
 *
 * @version Sequoia
 */
function cerber_activity_link(array $activity_ids = [], int $status_id = 0): string
{
	$params = [];

	if ( ! empty( $activity_ids ) ) {
		$params['filter_activity'] = array_map( 'crb_absint', $activity_ids );
	}

	if ( $status_id ) {
		$params['filter_status'] = crb_absint( $status_id );
	}

	return cerber_admin_link('activity', $params);
}

/**
 * Builds an HTML link to the Traffic admin page.
 * The function returns an <a> tag with the link text "Check for requests".
 *
 * @param array $args Optional query parameters added to the Traffic admin URL.
 * @param bool  $as_a_button Whether to render the link with button styling.
 *
 * @return string HTML anchor tag linking to the Traffic page.
 */
function crb_get_traffic_link( array $args = array(), bool $as_a_button = true ): string {
	$traffic_log_url = cerber_admin_link( 'traffic', $args );

	return '<a ' . ( $as_a_button ? 'class="crb-button-tiny"' : '' ) . ' href="' . crb_escape_url( $traffic_log_url ) . '">' . __( 'Check for requests', 'wp-cerber' ) . '</a>';
}

/**
 * Returns the Custom login URL with trailing slash
 *
 * @return string Full URL if the custom login page is configured in the WP Cerber settings, empty string otherwise
 */
function cerber_get_custom_login_url(): string {

	if ( $path = crb_get_settings( 'loginpath' ) ) {
		return cerber_get_site_url() . '/' . $path . '/';
	}

	return '';
}

/**
 * Extracts the website domain (or IP address) and the WordPress installation folder (path) of the website.
 * Doesn't return the folder of the WordPress files if it's installed in a separate folder.
 *
 * @return string[] An array containing two elements:
 *                  [0] string - The website domain or IP address.
 *                  [1] string - The optional folder path of the folder where the WordPress files are installed. No trailing slash.
 *
 * @see get_site_url()
 *
 * @since 8.6.6.1
 */
function crb_parse_site_url(): array {
	static $result;

	if ( isset( $result ) ) {
		return $result;
	}

	$site_url = cerber_get_site_url();
	$result = crb_parse_url( $site_url );

	return $result;
}

/**
 * Extracts the website domain (or IP address) and the public path (folder) of the website.
 * Doesn't return the folder of the WordPress files if they are installed in a separate folder.
 *
 * @return string[] An array containing two elements:
 *                  [0] string - The website domain or IP address.
 *                  [1] string - The optional folder path of the website. No trailing slash.
 *
 * @see get_home_url()
 *
 * @since 8.6.6.1
 */
function crb_parse_home_url(): array {
	static $result;

	if ( isset( $result ) ) {
		return $result;
	}

	$home_url = cerber_get_home_url();
	$result = crb_parse_url( $home_url );

	return $result;
}

/**
 * Extracts the hostname (domain or IP address) and the URL path (folder) from the given full URL.
 *
 * @param string $url Full URL, e.g. https://domain.com/wp
 *
 * @return string[] An array containing two elements:
 *                  [0] string - The website domain or IP address.
 *                  [1] string - The optional folder path if it presents in the URL. No trailing slash.
 *
 * @since 8.6.6.1
 */
function crb_parse_url( string $url ): array {

	$p1 = strpos( $url, '//' );
	$offset = ( $p1 !== false ) ? $p1 + 2 : 0;
	$sub_folder_pos = ( strlen( $url ) > $offset ) ? strpos( $url, '/', $offset ) : false;

	if ( $sub_folder_pos !== false ) {
		$host = substr( $url, 0, $sub_folder_pos );
		$sub_folder = rtrim( substr( $url, $sub_folder_pos ), '/' );
	}
	else {
		$host = $url;
		$sub_folder = '';
	}

	return array( $host, $sub_folder );
}

/**
 * Always includes the path to the WordPress installation folder, e.g. https://domain.com/wp
 *
 * It's a base URL for all WordPress admin locations (URLs), including the default WordPress login, password reset and registration pages.
 * For public locations and REST API URLs use cerber_get_home_url()
 *
 * @return string URL of the WordPress installation without trailing slash.
 *
 * @since 7.9.4
 *
 */
function cerber_get_site_url(): string {
	static $url;

	if ( isset( $url ) ) {
		return $url;
	}

	$url = trim( get_site_url(), '/' );

	return $url;
}

/**
 * Returns the URL of the website home page.
 *
 * It's a base URL for all public web pages and REST API URLs.
 * For admin locations and login URLs use cerber_get_site_url()
 *
 * If WordPress files are installed in a separate folder, this URL does not include the path to that folder: https://wordpress.org/support/article/giving-wordpress-its-own-directory/
 *
 * @return string URL of the website home page without trailing slash
 *
 * @since 7.9.4
 *
 */
function cerber_get_home_url(): string {
	static $url;

	if ( ! isset( $url ) ) {
		$url = trim( get_home_url(), '/' );
	}

	return $url;
}

/**
 * Generates a site label for creating file names of files generated by WP Cerber.
 *
 * The label is based on the site's home URL, sanitized to replace non-alphanumeric characters with hyphens.
 *
 * @return string A sanitized string representing the site label.
 *
 * @since 9.5.1
 */
function crb_site_label(): string {
	$home = cerber_get_home_url();

	return trim( preg_replace( '/\W+/', '-', substr( $home, strpos( $home, '//' ) + 2 ) ), '-' );
}

/**
 * For non-HTML cases. Not suitable for HTML rendering.
 *
 * @return string
 */
function crb_get_blogname_decoded() {
	return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
}

/**
 * @param int $begin Unix timestamp
 * @param int $end Unix timestamp
 *
 * @return array[]
 */
function cerber_calculate_kpi( $begin, $end ) {

	// TODO: Add spam performance as percentage Denied / Allowed comments

	return array(
		array(
			__( 'Malicious activities mitigated', 'wp-cerber' ) . '</a>',
			cerber_count_log( crb_get_activity_set( 'malicious' ), $begin, $end )
		),
		array( __( 'Spam comments denied', 'wp-cerber' ), cerber_count_log( array( CRB_EV_CMS, CRB_EV_SCD ), $begin, $end ) ),
		array( __( 'Spam form submissions denied', 'wp-cerber' ), cerber_count_log( array( CRB_EV_SFD ), $begin, $end ) ),
		array( __( 'Malicious IP addresses detected', 'wp-cerber' ), cerber_count_log( crb_get_activity_set( 'malicious' ), $begin, $end, 'ip', true ) ),
		array( __( 'Lockouts occurred', 'wp-cerber' ), cerber_count_log( array( CRB_EV_IPL, CRB_EV_NEL ), $begin, $end ) ),
	);

}

/**
 * The list devices available to send notifications
 *
 * @param string $token
 *
 * @return array|false|WP_Error
 *
 */
function cerber_pb_get_devices( $token = '' ) {

	cerber_delete_set( 'pushbullet_name' );

	if ( ! $token ) {
		if ( ! $token = crb_get_settings( 'pbtoken' ) ) {
			return false;
		}
	}

	$ret = array();

	$curl = @curl_init();

	if ( ! $curl ) {
		return false;
	}

	$headers = array(
		'Authorization: Bearer ' . $token
	);

	crb_configure_curl( $curl, array(
		CURLOPT_URL               => 'https://api.pushbullet.com/v2/devices',
		CURLOPT_HTTPHEADER        => $headers,
		CURLOPT_RETURNTRANSFER    => true,
		CURLOPT_CONNECTTIMEOUT    => 2,
		CURLOPT_TIMEOUT           => 4, // including CURLOPT_CONNECTTIMEOUT
		CURLOPT_DNS_CACHE_TIMEOUT => 4 * 3600,
	) );

	$result = @curl_exec( $curl );
	$curl_error = curl_error( $curl );

	$response = json_decode( $result, true );

	if ( JSON_ERROR_NONE == json_last_error()
	     && isset( $response['devices'] ) ) {
		foreach ( $response['devices'] as $device ) {
			if ( ! empty( $device['active'] )
			     && ( $iden = $device['iden'] ?? false ) ) {
				$nn = $device['nickname'] ?? 'Device ' . $iden;
				$ret[ $iden ] = $nn;
			}
		}
	}
	else {
		if ( $response['error'] ) {
			$e = 'Pushbullet ' . $response['error']['message'];
		}
		elseif ( $curl_error ) {
			$e = $curl_error;
		}
		else {
			$e = 'Unknown cURL error';
		}

		$ret = new WP_Error( 'cerber_pb_error', $e );
	}

	return $ret;
}

/**
 * Send push message via Pushbullet
 *
 * @param string $title
 * @param string $text
 * @param string $more Additional text (links)
 * @param string $footer
 *
 * @return bool|WP_Error True on success
 */
function cerber_pb_send( $title, $text, $more = '', $footer = '' ) {

	if ( ! $text ) {
		return false;
	}

	if ( ! $token = crb_get_settings( 'pbtoken' ) ) {
		return false;
	}

	$body = $text;

	if ( $format = crb_get_settings( 'pb_format' ) ) {
		if ( $format == 1 ) {
			$body .= $more;
		}
	}
	else {
		$body .= $more . $footer;
	}

	$params = array( 'type' => 'note', 'title' => $title, 'body' => $body, 'sender_name' => 'WP Cerber' );

	if ( $device = crb_get_settings( 'pbdevice' ) ) {
		if ( $device != 'all' && $device != 'N' ) {
			$params['device_iden'] = $device;
		}
	}

	$headers = array(
		'Access-Token: ' . $token,
		'Content-Type: application/json'
	);

	$curl = @curl_init();
	if ( ! $curl ) {
		return false;
	}

	crb_configure_curl( $curl, array(
		CURLOPT_URL               => 'https://api.pushbullet.com/v2/pushes',
		CURLOPT_POST              => true,
		CURLOPT_HTTPHEADER        => $headers,
		CURLOPT_POSTFIELDS        => json_encode( $params ),
		CURLOPT_RETURNTRANSFER    => true,
		CURLOPT_CONNECTTIMEOUT    => 2,
		CURLOPT_TIMEOUT           => 4, // including CURLOPT_CONNECTTIMEOUT
		CURLOPT_DNS_CACHE_TIMEOUT => 4 * 3600,
	) );

	$result = @curl_exec( $curl );

	if ( $curl_error = curl_error( $curl ) ) {
		$ret = new WP_Error( 'cerber_pb_error', $curl_error );
	}
	else {
		$ret = true;
	}

	return $ret;
}

/**
 * Returns the name of the selected Pushbullet device
 *
 * @return string Sanitized name of the device retrieved from Pushbullet
 *
 * @since 8.9.5.3
 */
function cerber_pb_get_active() {

	if ( false !== $name = cerber_get_set( 'pushbullet_name', null, false ) ) {
		return $name;
	}

	// Updating the cache

	$device = crb_get_settings( 'pbdevice' );

	$name = '';

	if ( $device == 'all' ) {
		$name = 'all connected devices';
	}
	elseif ( $device
	         && ( $list = cerber_pb_get_devices() )
	         && ! crb_is_wp_error( $list ) ) {
		$name = crb_escape_html( $list[ $device ] ) ?? '';
	}

	cerber_update_set( 'pushbullet_name', $name, null, false, time() + 3600 );

	return $name;
}

/**
 * Health check-up and self-repairing for vital parts
 *
 */
function cerber_watchdog( $full = false ) {

	warp_get_db( true );

	if ( $full ) {
		cerber_create_db( false );
		cerber_upgrade_db();

		return;
	}
	if ( ! cerber_db_is_table( CERBER_LOG_TABLE )
	     || ! cerber_db_is_table( CRB_LOCKOUT_TABLE )
	     || ! cerber_db_is_table( CERBER_LAB_IP_TABLE )
	) {
		cerber_create_db( false );
		cerber_upgrade_db();
	}
}

/**
 * Returns the resolved client IP address for the current request.
 * The only function allowed to be used to retrieve the client's IP address.
 *
 * @return string Client IP address, or CRB_REMOTE_IP_UNAVAILABLE if the IP address unavailable.
 *
 * @since 6.0
 *
 * @version Sequoia
 */
function cerber_get_remote_ip(): string {
	static $remote_ip;

	if ( isset( $remote_ip ) ) {
		return $remote_ip;
	}

	$remote_ip = crb_resolve_remote_ip();

	if ( $remote_ip === '' ) {
		$remote_ip = (string) filter_var( $_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP );
	}

	if ( $remote_ip === '' ) {
		// Includes WP-CLI and malformed server environments.
		$remote_ip = CRB_REMOTE_IP_UNAVAILABLE;
	}

	$remote_ip = crb_unwrap_ipv4_mapped_ipv6( $remote_ip );

	if ( $remote_ip === '' ) {
		$remote_ip = CRB_REMOTE_IP_UNAVAILABLE;
	}

	if ( cerber_is_ipv6( $remote_ip ) ) {
		$remote_ip = crb_normalize_ipv6( $remote_ip );
	}

	return $remote_ip;
}

/**
 * Resolve the client IP address according to the configured IP source.
 *
 * Uses CERBER_IP_KEY when defined, proxy headers when proxy support is enabled,
 * and REMOTE_ADDR otherwise. In diagnostic mode, returns an error message if
 * the configured source fails, or an empty string when no issue is detected.
 *
 * @param bool $diagnostic Whether to return a diagnostic message instead of the resolved IP address.
 *
 * @return string Resolved client IP address, diagnostic message, or an empty string.
 *
 * @since 9.6.3.3
 *
 * @version Sequoia
 */
function crb_resolve_remote_ip( bool $diagnostic = false ): string {

	$remote_ip = '';
	$err_msg = '';

	if ( defined( 'CERBER_IP_KEY' ) && CERBER_IP_KEY ) {
		$ip_key = (string) CERBER_IP_KEY;
		$remote_ip = (string) filter_var( $_SERVER[ $ip_key ] ?? '', FILTER_VALIDATE_IP );

		if ( $diagnostic && ! $remote_ip ) {
			if ( isset( $_SERVER[ $ip_key] ) ) {
				/* translators: %s: The value of the CERBER_IP_KEY constant. */
				$err_msg = sprintf( __( 'Unable to detect IP addresses. The defined constant CERBER_IP_KEY cannot be used to retrieve an IP address because the corresponding value does not contain a valid IP address. Ensure that the correct value is specified for this constant. Currently, it is defined as: "%s".', 'wp-cerber' ), esc_html( CERBER_IP_KEY ) );
			}
			else {
				/* translators: %s: The value of the CERBER_IP_KEY constant. */
				$err_msg = sprintf( __( 'Unable to detect IP addresses. The constant CERBER_IP_KEY you specified is mistyped or contains invalid characters. Currently, it is defined as: "%s".', 'wp-cerber' ), esc_html( CERBER_IP_KEY ) );
			}
		}
	}
	elseif ( crb_get_settings( 'proxy' ) ) {
		$remote_ip = crb_extract_ip_from_headers();

		if ( ! $remote_ip ) {
			$remote_ip = (string) filter_var( $_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP );
		}

		if ( $diagnostic && ! $remote_ip ) {
			$err_msg = __( 'Unable to detect IP addresses. No HTTP headers containing a valid IP address were detected. Ensure that your proxy server is configured to include the remote IP address in its HTTP headers.', 'wp-cerber' );
		}
	}
	else {
		$remote_ip = (string) filter_var( $_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP );

		if ( $diagnostic && ! $remote_ip ) {
			$err_msg = __( 'Unable to detect IP addresses. This might be because your website is behind a proxy server. If that is the case, enable proxy support in the plugin settings.', 'wp-cerber' );
		}
	}

	if ( $diagnostic ) {
		return $err_msg;
	}

	return $remote_ip;
}

/**
 * Extracts the first valid IP address from proxy headers.
 *
 * Checks X-Forwarded-For first, then falls back to X-Real-IP.
 *
 * This helper intentionally does not reject private or reserved addresses
 * because WordPress may run inside corporate networks, VPNs, containers,
 * staging environments, etc.
 *
 * @return string The first valid forwarded IP address, or an empty string if none is found.
 *
 * @since 9.4.2.4
 *
 * @version Sequoia
 */
function crb_extract_ip_from_headers(): string {

	$proxy_header = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

	if ( is_string( $proxy_header ) && $proxy_header !== '' ) {
		$list = explode( ',', $proxy_header );

		// Process from left to right as a best-effort approach for real-world WordPress environments.

		foreach ( $list as $maybe_ip ) {
			if ( $remote_ip = filter_var( trim( $maybe_ip ), FILTER_VALIDATE_IP ) ) {
				return $remote_ip;
			}
		}
	}

	// Fall back to X-Real-IP if X-Forwarded-For is missing or contains no valid address.

	$real_ip_header = $_SERVER['HTTP_X_REAL_IP'] ?? '';

	if ( is_string( $real_ip_header ) && $real_ip_header !== '' ) {
		if ( $remote_ip = filter_var( trim( $real_ip_header ), FILTER_VALIDATE_IP ) ) {
			return $remote_ip;
		}
	}

	return '';
}

/**
 * Extract an IPv4 mapped address from IPv6.
 *
 * This function intentionally does not unwrap deprecated IPv4-compatible IPv6
 * addresses (::a.b.c.d), because they do not have the same operational meaning
 * as IPv4-mapped IPv6 addresses in modern web server environments.
 *
 * @param string $ip Raw IP address.
 *
 * @return string Canonical IP address, or an empty string if the input is invalid.
 *
 * @since 9.7.5
 *
 * @version Sequoia
 */
function crb_unwrap_ipv4_mapped_ipv6( string $ip ): string {
	$ip = trim( $ip );

	if ( $ip === '' ) {
		return '';
	}

	if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return $ip;
	}

	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		return '';
	}

	$binary_ip = inet_pton( $ip );

	if ( $binary_ip === false || strlen( $binary_ip ) !== 16 ) {
		return '';
	}

	$mapped_prefix = str_repeat( "\x00", 10 ) . "\xff\xff";

	if ( substr( $binary_ip, 0, 12 ) === $mapped_prefix ) {
		$unwrapped_ip = inet_ntop( substr( $binary_ip, 12, 4 ) );

		if ( $unwrapped_ip !== false && filter_var( $unwrapped_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return $unwrapped_ip;
		}

		return '';
	}

	$normalized_ip = inet_ntop( $binary_ip );

	return $normalized_ip !== false ? $normalized_ip : '';
}

/**
 * Packs a genuine IPv6 address into an 8-byte binary string representing its /64 prefix.
 *
 * The returned value is suitable for storage in a BINARY(8) database column.
 * Returns null for IPv4 addresses, invalid IP addresses,
 * IPv4-mapped IPv6 addresses, and deprecated IPv4-compatible IPv6 addresses.
 *
 * Private IPv6 addresses are accepted to support private hosting environments.
 *
 * @param string $ip Raw IP address.
 *
 * @return string|null 8-byte IPv6 network prefix, or null when not applicable.
 *
 * @since 9.7.5
 *
 * @version Sequoia
 */
function crb_pack_ipv6_net64( string $ip ): ?string {
	$ip = trim( $ip );

	if ( $ip === '' ) {
		return null;
	}

	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		return null;
	}

	$binary_ip = inet_pton( $ip );

	if ( $binary_ip === false || strlen( $binary_ip ) !== 16 ) {
		return null;
	}

	$ipv4_compatible_prefix = str_repeat( "\x00", 12 );
	$ipv4_mapped_prefix     = str_repeat( "\x00", 10 ) . "\xff\xff";
	$address_prefix_96      = substr( $binary_ip, 0, 12 );

	// Reject IPv4-compatible IPv6 (::/96) and IPv4-mapped IPv6 (::ffff:0:0/96).
	if ( $address_prefix_96 === $ipv4_compatible_prefix || $address_prefix_96 === $ipv4_mapped_prefix ) {
		return null;
	}

	return substr( $binary_ip, 0, 8 );
}

/**
 * Converts an IP address into a safe string format (id_ip).
 *
 * The resulting string can be safely used as an array key, in HTML attributes, or other contexts where certain characters might be restricted.
 *
 * @param string $ip The IP address to be converted. Can be an IPv4 or IPv6 address.
 *
 * @return string The converted string, where dots are replaced with dashes and colons are replaced with underscores.
 *
 * @since 2.2
 */
function cerber_get_id_ip( $ip ) {
	return strtr( $ip, '.:', '-_' );
}

/**
 * Converts a safe string (id_ip) back into an IP address format.
 *
 * @param string $ip_id This should be a string previously generated by `cerber_get_id_ip`.
 *
 * @return string The reconstructed IP address in its original format (IPv4 or IPv6).
 *
 * @since 2.2
 */
function cerber_get_ip_id( $ip_id ) {
	return strtr( $ip_id, '-_', '.:' );
}

/**
 * Check if given IP address is a valid single IP v4 address
 *
 * @param $ip
 *
 * @return bool
 */
function cerber_is_ipv4( $ip ) {
	return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
}

function cerber_is_ipv6( $ip ) {
	return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
}

/**
 * Check if a given IP address belongs to a private network (private IP).
 * Universal: support IPv6 and IPv4.
 *
 * @param $ip string An IP address to check
 *
 * @return bool True if IP is in the private range, false otherwise
 */
function is_ip_private( $ip ) {

	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE ) ) {
		return true;
	}
	elseif ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE ) ) {
		return true;
	}

	return false;
}

function cerber_is_ip( $ip ) {
	return filter_var( $ip, FILTER_VALIDATE_IP );
}

/**
 * Hostname validation: it's either a valid domain or a valid IP address
 *
 * @param string $str
 *
 * @return bool
 *
 * @since 9.5.1
 */
function crb_is_valid_hostname( $str ) {
	return ( filter_var( $str, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME )
	         || filter_var( $str, FILTER_VALIDATE_IP ) );
}

/**
 * Expands shortened IPv6 to full IPv6 address
 *
 * @param $ip string IPv6 address
 *
 * @return string Full IPv6 address
 */
function cerber_ipv6_expand( $ip ) {
	$full_hex = (string) bin2hex( inet_pton( $ip ) );

	return implode( ':', str_split( $full_hex, 4 ) );
}

/**
 * Normalizes the textual representation of an IPv6 address.
 *
 * Returns the original value if it is not a valid IPv6 address.
 *
 * @param string $ip IPv6 address to normalize.
 *
 * @return string Normalized IPv6 address or the original value if invalid.
 */
function crb_normalize_ipv6( $ip ): string {
	if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
		return $ip;
	}

	return inet_ntop( inet_pton( $ip ) );
}

/**
 * @param string|array $val
 *
 * for objects see map_deep();
 */
function crb_trim_deep( &$val ) {
	if ( ! is_array( $val ) ) {
		$val = trim( $val );
	}

	array_walk_recursive( $val, function ( &$v ) {
		$v = trim( $v );
	} );
}

/**
 * - Checks for invalid UTF-8,
 * - Converts single `<` characters to entities
 * - Strips all tags
 * - Removes tabs, and extra whitespace
 * - Strips octets
 *
 * @param string|array $val
 *
 * Note: _sanitize_text_fields removes HTML tags
 *
 */
function crb_sanitize_deep( &$val ) {
	if ( ! is_array( $val ) ) {
		if ( $val && ! is_numeric( $val ) ) { // crucial since sanitize_text_fields() convert all values to strings
			$val = _sanitize_text_fields( $val, true );
		}
	}
	else {
		array_walk_recursive( $val, function ( &$v ) {
			if ( $v && ! is_numeric( $v ) ) { // crucial since sanitize_text_fields() convert all values to strings
				$v = _sanitize_text_fields( $v, true );
			}
		} );
	}
}

/**
 * Sanitizes integer values
 *
 * @param array|int $val Input value
 * @param bool $make_list If true, returns result as an array
 * @param bool $keep_empty If false, removes empty elements from the resulting array
 *
 * @return array|int
 *
 * @since 8.9.5.2
 */
function crb_sanitize_int( $val, $make_list = true, $keep_empty = false ) {
	if ( ! is_array( $val ) ) {
		if ( $make_list ) {
			$val = array( $val );
		}
		else {
			return crb_absint( $val );
		}
	}

	array_walk_recursive( $val, function ( &$v ) {
		$v = crb_absint( $v );
	} );

	if ( ! $keep_empty ) {
		$val = array_filter( $val );
	}

	return $val;
}

/**
 * Returns an escaped and sanitized ID that is safe for any context.
 *
 * @param string $id
 *
 * @return string
 *
 * @since 9.6.1.2
 */
function crb_sanitize_id( $id = '' ) {
	if ( ! $id ) {
		return '';
	}

	return substr( preg_replace( CRB_SANITIZE_ID, '_', (string) $id ), 0, 64 );
}

/**
 * Sanitize a value by keeping only ASCII letters digits underscore and hyphen.
 *
 * When $check_utf8 is enabled invalid UTF 8 byte sequences are removed before
 * sanitizing the value. This option is intended for untrusted or binary input
 * where malformed UTF 8 may be present.
 *
 * @param mixed $value Input value to sanitize. Scalars and nested arrays are supported.
 * @param bool  $check_utf8 Whether to remove invalid UTF 8 byte sequences before sanitizing.
 *
 * @return mixed Sanitized value. Returns a string or an array mirroring the input structure.
 *
 * @since 9.6.3.2
 *
 * @version Sequoia
 */
function crb_sanitize_alphanum( $value, bool $check_utf8 = false ) {

	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = crb_sanitize_alphanum( $v, $check_utf8 );
		}

		return $value;
	}

	$value = (string) $value;

	if ( $check_utf8 && $value !== '' && ! preg_match( '//u', $value ) ) {
		$fixed = @iconv( 'UTF-8', 'UTF-8//IGNORE', $value );
		if ( $fixed !== false ) {
			$value = $fixed;
		}
	}

	return preg_replace( '/[^a-zA-Z0-9_-]/', '', $value );
}

/**
 * Sanitizes a free-text name for safe use as an RFC 5322 display name inside an
 * email recipient string such as '"Name" <email@example.com>'.
 *
 * Strips characters that could let the value break out of the quoted display-name
 * segment, terminate the angle-bracket address, or be misread by mail functions
 * (such as WordPress's wp_mail()) that split string recipients on a literal comma
 * instead of performing RFC 5322 quoted-string-aware parsing.
 *
 * @param string $name Raw display name, typically taken from a user profile field.
 *
 * @return string Sanitized name safe for embedding in a mail recipient string.
 *
 * @since 9.8.2
 */
function crb_sanitize_mail_display_name( $name ) {

	$name = (string) $name;
	$name = preg_replace( '/[\x00-\x1F\x7F",\\\\<>]/', '', $name ) ?? '';

	return trim( $name );
}

/**
 * Validates and sanitizes a base admin URL received from an untrusted source.
 *
 * This function ensures the URL:
 * - is a valid HTTP or HTTPS URL (no malware JavaScript),
 * - has a path that starts with /wp-admin,
 * - contains no query string or fragment,
 * - ends with a trailing slash.
 *
 * @param string $url Raw URL value from an untrusted source.
 *
 * @return string Sanitized admin URL ending with a slash, or empty string if invalid.
 *
 * @since 9.6.9.4
 *
 * @version Sequoia
 *
 */
function crb_sanitize_base_admin_url( $url ): string {

	if ( ! is_string( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return '';
	}

	$parts = parse_url( $url );

	if (
		empty( $parts['scheme'] ) ||
		empty( $parts['host'] ) ||
		empty( $parts['path'] ) ||
		isset( $parts['query'] ) ||
		isset( $parts['fragment'] )
	) {
		return '';
	}

	$scheme = strtolower( $parts['scheme'] );

	if ( $scheme !== 'http' && $scheme !== 'https' ) {
		return '';
	}

	if ( strpos( $parts['path'], '/wp-admin' ) !== 0 ) {
		return '';
	}

	return $scheme . '://' . $parts['host'] . rtrim( $parts['path'], '/' ) . '/';
}

/**
 * Strip log-forging characters from a hostname for safe single-line log output.
 *
 * Removes CR, LF and every character outside the hostname-safe set
 * (letters, digits, hyphens, dots, colons, square brackets), then truncates
 * to the 253-byte DNS limit. Returns an empty string if sanitization fails,
 * so a raw value can never leak into the log. Neutralizes separators, does
 * not validate the hostname per RFC.
 *
 * @param string $hostname Raw value from $_SERVER['HTTP_HOST'] or similar.
 * @return string Sanitized value safe for single-line log output.
 *
 * @since 9.7.4.1
 */
function crb_sanitize_log_hostname( $hostname ) {

	$clean = preg_replace( '/[^a-zA-Z0-9\-.:\[\]]/', '', $hostname );

	if ( ! is_string( $clean ) ) {
		return '';
	}

	return substr( $clean, 0, 253 );
}

/**
 * Simple and quick "escaping/sanitizing" for manually coded plain ASCII alphanumeric values.
 * Should not be used for any external or untrusted data (user input, DB, network etc.)
 *
 * @param string $val
 *
 * @return string
 *
 * @since 9.3.4
 */
function crb_boring_escape( $val = '' ) {
	if ( ! $val
	     || is_numeric( $val ) ) {
		return $val;
	}

	return preg_replace( '/[^\w\-\[\].]/u', '', (string) $val );
}

/**
 * Safely escapes a string for use in HTML content including HTML attributes and form field values.
 *
 * It is configured with best practices to prevent Cross-Site Scripting (XSS)
 * attacks when outputting dynamic data to an HTML page.
 *
 * WARNING: This function is for displaying text only. It is UNSAFE in contexts
 * where code is executed or URLs are processed. Do not use in:
 *
 * - JavaScript contexts (inside `<script>`, `onclick`, `onmouseover`, etc.).
 *
 * - URL attributes (`href`, `src`, `action`, etc.), as it does not prevent payloads like `javascript:`.
 *   @see crb_escape_url()
 *
 * - CSS contexts (inside `<style>` or the `style` attribute).
 *
 * Core security features:
 * - ENT_QUOTES: Escapes both single (') and double (") quotes, making it safe
 * to use within any quoted HTML attribute.
 * - ENT_SUBSTITUTE: Replaces invalid UTF-8 code unit sequences with the
 * Unicode replacement character (U+FFFD) instead of returning an empty
 * string. This prevents data loss and potential encoding-based attacks.
 * - UTF-8 Encoding: Explicitly sets the character encoding to UTF-8, ensuring
 * consistent and predictable behavior.
 *
 * @param string $text The raw, unescaped string to process.
 *
 * @return string The escaped string, safe for output in an HTML context.
 *
 * @since 9.5.7.2
 *
 * @version Sequoia
 */
function crb_escape_html( $text = '' ): string {

	return htmlentities( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * Escaping HTML attributes and form fields
 *
 * @param array|string $value
 *
 * @return array|string Escaped array or string
 *
 * @version Sequoia
 */
function crb_attr_escape( $value ) {
	if ( is_array( $value ) ) {
		array_walk_recursive( $value, function ( &$element ) {
			$element = crb_escape_html( $element );
		} );
	}
	else {
		$value = crb_escape_html( $value );
	}

	return $value;
}

/**
 * Escapes a URL for safe use in quoted HTML attributes such as "href" and "src".
 *
 * This is a lightweight, deterministic, hook-free escaping primitive for WP Cerber
 * UI hot paths. It accepts absolute HTTP(S), FTP(S), and mailto URLs, root-relative
 * URLs beginning with a single slash, and the literal '#' placeholder.
 *
 * Protocol-relative URLs, backslash network-path variants, unsupported schemes such
 * as javascript:, data:, and file:, and every other relative URL form are rejected.
 * A URL of an unsupported shape is rejected rather than rewritten into a different
 * value. The function performs minimal URL-shape validation only and is not a
 * general-purpose URL validator.
 *
 * The result is escaped for a quoted attribute. Placing it in an unquoted attribute
 * or in a JavaScript context is outside this contract.
 *
 * @param string $url The URL to process.
 *
 * @return string The escaped URL, an empty string for empty input, or the
 *                '#INVALID URL#' string for a URL of an unsupported shape.
 *
 * @version Sequoia
 */
function crb_escape_url( string $url = '' ): string {

	$url = trim( $url );

	if ( $url === '' ) {
		return '';
	}

	if ( $url === '#' ) {
		return '#';
	}

	// Browsers strip TAB, LF, and CR before URL parsing, which can change the URL shape:
	// '/<TAB>/example.com/x' would reach the browser as protocol-relative '//example.com/x'.
	if ( strpbrk( $url, "\t\n\r" ) !== false ) {
		return '#INVALID URL#';
	}

	// Accept a root-relative URL, rejecting the protocol-relative and the backslash
	// network-path forms, both of which point to a foreign host.
	if ( $url[0] === '/' ) {
		if ( isset( $url[1] )
		     && ( $url[1] === '/' || $url[1] === '\\' ) ) {
			return '#INVALID URL#';
		}
	}
	// Otherwise accept only the absolute URL schemes the WP Cerber UI relies on.
	elseif ( strncasecmp( $url, 'https://', 8 ) !== 0
	         && strncasecmp( $url, 'http://', 7 ) !== 0
	         && strncasecmp( $url, 'ftps://', 7 ) !== 0
	         && strncasecmp( $url, 'ftp://', 6 ) !== 0
	         && strncasecmp( $url, 'mailto:', 7 ) !== 0 ) {
		return '#INVALID URL#';
	}

	// Normalize the ampersands of a URL that a caller has already escaped for HTML,
	// so that escaping it here does not double-encode them.
	if ( strpos( $url, '&' ) !== false ) {
		$url = str_ireplace( '&amp;', '&', $url );
	}

	// ENT_SUBSTITUTE keeps a URL carrying invalid UTF-8 from collapsing to an empty
	// attribute value, which is indistinguishable from a missing URL for the caller.
	return htmlspecialchars( $url, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
}

/**
 * Escapes a path from $_SERVER['REQUEST_URI'] for safe output in an HTML context.
 *
 * @note Do not use it for escaping HTML attributes or JavaScript contexts. A dedicated function must be used for other contexts.
 *
 * @param string $path The raw URL path to escape.
 *
 * @return string The HTML-safe string.
 *
 * @version Sequoia
 *
 * @since 9.6.9.8
 */
function crb_escape_path_for_html( string $path ): string {

	return htmlspecialchars( $path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

/**
 * Validates and safely escapes an email address for use in "mailto:" href attributes.
 *
 * @param string $email The input email address.
 *
 * @return string Escaped and validated email address, or empty string if invalid.
 *
 * @since 9.6.9
 */
function crb_escape_email( string $email = '' ): string {

	$email = trim( $email );

	if ( ! $email ) {
		return '';
	}

	// Basic email validation
	if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
		return '';
	}

	// Final output-escape for insertion into HTML attribute
	return htmlspecialchars( $email, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * Escapes a string for safe use inside a JavaScript string literal that sits inside an HTML attribute (e.g., onclick="...").
 *
 * This provides double protection:
 * 1. Escapes characters for the JavaScript string context (' and \).
 * 2. Escapes the result for the HTML attribute context (<, >, &, ").
 *
 * @param string $text The raw text to escape.
 *
 * @return string The fully escaped text.
 *
 * @since 9.6.9.4
 *
 * @version Sequoia
 */
function crb_escape_string_for_js_in_html( string $text ): string {

	$js_escaped = strtr( $text, [
		'\\' => '\\\\',
		'\'' => '\\\'',
		"\r" => '',
		"\n" => '\\n',
	] );

	return crb_escape_html( $js_escaped );
}

/**
 * Safely encodes and escapes a PHP variable into a JSON string (JavaScript literal) for embedding in HTML.
 * This function is the recommended way to pass PHP data (strings, arrays, objects) to JavaScript.
 *
 * The resulting string is safe to be embedded directly into an HTML document,
 * for instance inside a data-* attribute or a <script> tag.
 * This function is the recommended way to pass PHP data (strings, arrays, objects) to JavaScript.
 *
 * Important: Do not wrap the output of this function in additional quotes in
 * your JavaScript, as it already includes them.
 *
 * @param  mixed  $data The PHP variable to encode (string, array, bool, etc.).
 *
 * @return string A JavaScript literal, escaped to be safe for an HTML context.
 *
 * @since 9.6.9.4
 *
 * @version Sequoia
 */
function crb_json_encode_for_html( $data ): string {

	$secure_flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
	$json = json_encode( $data, $secure_flags );

	if ( $json === false ) {
		// json_encode() can fail on things like recursion or invalid UTF-8 data.
		// For debugging, we can log the error here.
		return '{}';
	}

	return $json;
}

/**
 * Generates a JavaScript variable declaration with JSON-encoded data.
 *
 * @param string $var_name The desired name for the JavaScript variable. Must be a valid JS identifier.
 * @param array $data The PHP array to be encoded as a JSON string.
 *
 * @return string A complete and safe JavaScript statement, or an empty string if the variable name is invalid.
 *
 * @since 9.6.9.4
 *
 * @version Sequoia
 */
function crb_generate_safe_js_var( string $var_name, array $data ): string {

	// Validate JS variable name

	if ( ! preg_match( '/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $var_name ) ) {
		return '';
	}

	$json_value = crb_json_encode_for_html( $data );

	return 'var ' . $var_name . ' = ' . $json_value . ";\n";
}

/**
 * Strip HTML tags including content inside the <script></script> and <style></style> tags
 * See also wp_strip_all_tags()
 *
 * @param string $text
 * @param string|string[]|null $allowed_tags
 *
 * @return string
 *
 * @since 9.6.1.2
 */
function crb_strip_tags( $text = '', $allowed_tags = null ) {
	if ( ! $text ) {
		return '';
	}

	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@siu', '', $text );

	return strip_tags( $text, $allowed_tags );
}

/**
 * Validates a string:
 *
 * - Only Latin letters and digits
 * - At least one letter and one digit
 * - Must be exactly $exact_length characters long, if specified
 *
 * @param string $input
 * @param int|null $exact_length Exact required length (optional)
 *
 * @return bool True if the string contains digits and Latin letters only and at least one letter and one digit.
 *
 * @since 9.6.9.2
 */
function crb_validate_alphanum( string $input, ?int $exact_length = null ): bool {

	if ( preg_match( '/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d]+$/', $input ) !== 1 ) {
		return false;
	}

	if ( $exact_length !== null
	     && strlen( $input ) !== $exact_length ) {
		return false;
	}

	return true;
}

/**
 * Return true if a REST API URL has been requested
 *
 * @return bool
 * @since 3.0
 */
function cerber_is_rest_url() {
	global $wp_rewrite;
	static $ret = null;

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return true;
	}

	if ( isset( $_REQUEST['rest_route'] ) ) {
		return true;
	}

	if ( ! $wp_rewrite ) { // see get_rest_url() in the multisite mode
		return false;
	}

	if ( isset( $ret ) ) {
		return $ret;
	}

	$ret = false;

	if ( CRB_Request::get_rest_api_path() ) {
		$ret = true;
	}

	return $ret;
}

/**
 * @return bool
 *
 * @since 8.8
 */
function cerber_is_api_request() {
	return ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );
}

/**
 * Check if the current query is HTTP GET
 *
 * @return bool true if request method is GET
 */
function cerber_is_http_get() {
	if ( nexus_is_valid_request() ) {
		return ! nexus_request_data()->is_post;
	}
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
		return true;
	}

	return false;
}

/**
 * Check if the current query is HTTP POST
 *
 * @return bool true if request method is POST
 */
function cerber_is_http_post() {
	if ( nexus_is_valid_request() ) {
		return nexus_request_data()->is_post;
	}

	if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
		return true;
	}

	return false;
}

/**
 * Checks if it's a wp cron request
 *
 * @return bool
 */
function cerber_is_wp_cron() {
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return true;
	}
	if ( CRB_Request::is_script( '/wp-cron.php' ) ) {
		return true;
	}

	return false;
}

function cerber_is_wp_ajax( $use_filter = false ) {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return true;
	}

	// @since 8.1.3
	if ( $use_filter && function_exists( 'wp_doing_ajax' ) ) {
		return wp_doing_ajax();
	}

	return false;
}

/**
 * Checks whether the given variable is a WordPress Error
 *
 * @param mixed $thing
 * @param bool $add_issue If true, the error will be saved as an issue
 * @param string $issue_section Use this section when saving the issue
 *
 * @return bool True if the variable is an instance of WP_Error
 *
 * @since 9.0.4
 */
function crb_is_wp_error( $thing, bool $add_issue = false, string $issue_section = '' ): bool {
	if ( $thing instanceof WP_Error ) {
		if ( $add_issue ) {
			CRB_Issues::register(
				$thing->get_error_code(),
				$thing->get_error_message(),
				[ 'section' => $issue_section ]
			);
		}

		return true;
	}

	return false;
}

/**
 * @return bool True if it's the user edit/profile WordPress admin page
 */
function is_admin_user_edit() {
	if ( ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE )
	     || CRB_Request::is_script( array( '/wp-admin/user-edit.php', '/wp-admin/profile.php' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Returns a $_GET parameter with a given key
 *
 * @param $key string
 * @param $pattern string
 * @param $filter_var integer filter_var() filter ID
 *
 * @return bool|array|string
 */
function cerber_get_get( $key, $pattern = '', $filter_var = null ) {

	$ret = crb_array_get_validated( $_GET, $key, false, $pattern );

	if ( $filter_var ) {
		return filter_var( $ret, FILTER_VALIDATE_IP );
	}

	return $ret;
}

/**
 *
 * @param $key string
 * @param $pattern string
 *
 * @return bool|array|string
 */
function cerber_get_post( $key, $pattern = '' ) {
	return crb_array_get_validated( $_POST, $key, false, $pattern );
}

/**
 * Returns values of the query parameters (query string, $_GET parameters)
 *
 * @param string $key Query parameter
 * @param string $pattern REGEX pattern for value validation
 *
 * @return array|string|mixed
 */
function crb_get_query_params( string $key = '', string $pattern = '' ) {
	if ( nexus_is_valid_request() ) {
		if ( $key ) {
			return crb_array_get_validated( nexus_request_data()->get_params, $key, false, $pattern );
		}

		return (array) nexus_request_data()->get_params;
	}

	// Local context

	if ( $key ) {
		return cerber_get_get( $key, $pattern );
	}

	return (array) $_GET;
}

function crb_get_post_fields( $key = null, $default = false, $pattern = '' ) {
	if ( nexus_is_valid_request() ) {
		if ( nexus_request_data()->is_post ) {
			return nexus_request_data()->get_post_fields( $key, $default, $pattern );
		}

		return array();
	}

	if ( $key ) {
		return crb_array_get_validated( $_POST, $key, $default, $pattern );
	}

	return $_POST;
}

function crb_get_request_field( $field, $default = false ) {
	$fields = crb_get_request_fields();

	return crb_array_get( $fields, $field, $default );
}

function crb_get_request_fields() {
	if ( nexus_is_valid_request() ) {
		$ret = nexus_request_data()->get_params;
		if ( nexus_request_data()->is_post ) {
			$ret = array_merge( $ret, nexus_request_data()->get_post_fields() );
		}

		return $ret;
	}

	return $_REQUEST;
}

/**
 * Check if the WP Cerber settings permit access to the current REST request route
 *
 * @return bool True if the route is allowed
 */
function cerber_is_rest_permitted() {

	$rest_path = CRB_Request::get_rest_api_path();

	// Exception: application passwords route @since WP Cerber 8.8 & WP 5.6 -> permissions are checked in the WP core
	if ( preg_match( '#^wp/v\d+/users/\d+/application-passwords#', $rest_path ) ) {

		return true;
	}

	$opt = crb_get_settings();

	if ( ! empty( $opt['norestuser'] ) ) {
		$path = explode( '/', $rest_path );
		if ( $path
		     && count( $path ) > 2
		     && $path[0] == 'wp'
		     && $path[2] == 'users' ) {

			if ( is_super_admin() ) {

				CRB_Globals::$req_status = 509;
				return true;
			}

			if ( crb_wp_user_has_role( $opt['norestuser_roles'] ) ) {

				CRB_Globals::$req_status = 509;
				CRB_Globals::set_ctrl_setting( 'norestuser_roles' );

				return true;
			}

			CRB_Globals::set_ctrl_setting( 'norestuser' );

			return false;
		}

	}

	if ( empty( $opt['norest'] ) ) {

		return true;
	}

	CRB_Globals::set_ctrl_setting( 'norest' );

	CRB_Globals::$user_id = get_current_user_id();

	if ( $opt['restauth']
	     && is_user_logged_in() ) {

		CRB_Globals::set_ctrl_setting( 'restauth' );

		return true;
	}

	if ( ! empty( $opt['restwhite'] ) ) {

		$namespace = substr( $rest_path, 0, strpos( $rest_path, '/' ) );

		foreach ( $opt['restwhite'] as $allowed ) {
			if ( $allowed == $namespace ) {

				CRB_Globals::$req_status = 503;
				CRB_Globals::set_ctrl_setting( 'restwhite' );

				return true;
			}
		}

	}

	if ( crb_wp_user_has_role( $opt['restroles'] ) ) {

		CRB_Globals::$req_status = 509;
		CRB_Globals::set_ctrl_setting( 'restroles' );

		return true;
	}

	return false;
}

/**
 *
 * @return string Full URL including scheme, host, path and trailing slash
 *
 */
function crb_get_rest_url() {
	static $ret;

	if ( ! isset( $ret ) ) {
		$ret = get_rest_url();
	}

	return $ret;
}

/**
 * Check if a user has at least one role from the list
 *
 * @param array $roles
 * @param null $user_id
 *
 * @return bool
 */
function crb_wp_user_has_role( $roles = array(), $user_id = null ) {
	if ( ! $roles ) {
		return false;
	}

	if ( ! is_array( $roles ) ) {
		$roles = array( (string) $roles );
	}

	if ( ! $user_id ) {
		crb_load_dependencies( 'wp_get_current_user', true );
		$user = wp_get_current_user(); //
	}
	else {
		$user = crb_get_userdata( $user_id );
	}

	if ( ! $user || empty( $user->roles ) ) {
		return false;
	}

	if ( array_intersect( $user->roles, $roles ) ) {
		return true;
	}

	return false;
}

/**
 * Check if all user roles are in the list
 *
 * @param array|string $roles
 * @param int $user_id
 *
 * @return bool false if the user has role(s) other than listed in $roles
 */
function crb_user_has_role_strict( $roles, $user_id ) {
	if ( ! $user_id || ! $user = get_userdata( $user_id ) ) {
		return false;
	}

	if ( ! is_array( $roles ) ) {
		$roles = array( $roles );
	}

	$user_roles = ( is_array( $user->roles ) ) ? $user->roles : array();

	return ( ! array_diff( $user_roles, $roles ) );
}

/**
 * Returns block metadata if the user is manually blocked
 *
 * @param int $uid User ID
 *
 * @return false|array False if the user is not blocked
 */
function crb_is_user_blocked( $uid ) {
	if ( $uid
	     && ( $m = get_user_meta( $uid, CERBER_BUKEY, 1 ) )
	     && ! empty( $m['blocked'] )
	     && $m[ 'u' . $uid ] == $uid ) {
		return $m;
	}

	return false;
}

/**
 * Returns textual info on who and when blocked a user
 *
 * @param array $meta
 *
 * @return string
 *
 * @since 9.0.2
 */
function crb_user_blocked_by( array $meta ): string {
	if ( empty( $meta['blocked_by'] ) ) {
		return '';
	}

	if ( $meta['blocked_by'] == get_current_user_id() ) {
		$who = __( 'You', 'wp-cerber' );
	}
	else {
		$user = get_userdata( $meta['blocked_by'] );
		$who = '<a href="' . get_edit_user_link( $user->ID ) . '" target="_blank">' . $user->display_name . '</a>';
	}

	/* translators: Describes by whom and when a website user was blocked. %1$s: the name of the person (e.g., "John"), %2$s: the time (e.g., "11:00"). */
	return sprintf( _x( 'blocked by %1$s at %2$s', 'e.g. blocked by John at 11:00', 'wp-cerber' ), $who, cerber_date( $meta['blocked_time'] ) );
}

/**
 * Should be used as a last-resort since it loads pluggable function bundled with WordPress and this can prevent defining those functions by another plugin.
 *
 * @return bool
 *
 * @since 8.8
 *
 */
function crb_is_user_logged_in() {

	crb_load_dependencies( 'is_user_logged_in', true );

	return is_user_logged_in();
}

/**
 * Returns user session token for the current user session.
 *
 * WordPress stores the same token in several different cookies: LOGGED_IN_COOKIE, SECURE_AUTH_COOKIE, AUTH_COOKIE
 *
 * @return string
 *
 * @since 8.9.1
 */
function crb_get_session_token() {

	// First, trying to get it from LOGGED_IN_COOKIE

	if ( ! $token = wp_get_session_token() ) {

		// Trying another cookie: SECURE_AUTH_COOKIE or AUTH_COOKIE

		$cookie = wp_parse_auth_cookie();
		$token = crb_array_get( $cookie, 'token', '' );
	}

	return $token;
}

/**
 * Checks role-based user limits
 *
 * @param $user_id
 *
 * @return false|string Returns false if no restrictions, an error message otherwise.
 */
function crb_check_user_limits( $user_id ) {
	if ( ! $user_id ) {
		return false;
	}

	// Sessions

	if ( ! $limit = absint( cerber_get_user_policy( 'sess_limit', $user_id ) ) ) {
		return false;
	}

	$list = cerber_db_get_results( 'SELECT started, wp_session_token FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE . ' WHERE user_id = ' . absint( $user_id ) );
	if ( $list && ( count( $list ) >= $limit ) ) {
		if ( cerber_get_user_policy( 'sess_limit_policy', $user_id ) ) {
			if ( $msg = cerber_get_user_policy( 'sess_limit_msg', $user_id ) ) {
				return $msg;
			}

			return get_wp_cerber()->get_auth_restriction_message();
		}
		else {
			$started = array_column( $list, 'started' );
			array_multisort( $started, SORT_ASC, SORT_NUMERIC, $list );
			CRB_Globals::$session_status = 38;
			crb_sessions_kill( $list[0]['wp_session_token'], $user_id, false );
		}
	}

	return false;
}

/**
 * Filters the query parameters of a request URI, keeping only the allowed ones.
 *
 * @param string $request_uri The full request URI (e.g., from $_SERVER['REQUEST_URI']).
 * @param array $allowed_params List of allowed query parameter names.
 *
 * @return string The filtered request URI. The original one if no query parameters in URI.
 *
 * @since 9.6.9.1
 */
function crb_strip_query_params( string $request_uri, array $allowed_params = [] ): string {

	$parts = parse_url( $request_uri );

	if ( ! isset( $parts['query'] ) ) {
		return $request_uri;
	}

	parse_str( $parts['query'], $query_params );

	// Keep only allowed parameters
	$filtered_params = array_intersect_key( $query_params, array_flip( $allowed_params ) );
	$filtered_query = http_build_query( $filtered_params );

	$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
	$path = $parts['path'] ?? '';

	// Rebuild the URI

	$new_uri = $path;

	if ( $filtered_query !== '' ) {
		$new_uri .= '?' . $filtered_query;
	}

	$new_uri .= $fragment;

	return $new_uri;
}

/**
 * Return the last element in the path of the requested URI.
 *
 * @return bool|string
 */
function cerber_last_uri() {
	static $ret;

	if ( isset( $ret ) ) {
		return $ret;
	}

	$ret = strtolower( $_SERVER['REQUEST_URI'] );

	if ( $pos = strpos( $ret, '?' ) ) {
		$ret = substr( $ret, 0, $pos );
	}

	if ( $pos = strpos( $ret, '#' ) ) {
		$ret = substr( $ret, 0, $pos ); // @since 8.1 - malformed request URI
	}

	$ret = rtrim( $ret, '/' );
	$ret = substr( strrchr( $ret, '/' ), 1 );

	return $ret;
}

/**
 * Return the name of an executable script in the requested URI if it's present
 *
 * @return bool|string The script name or false if executable script is not detected
 */
function cerber_get_uri_script() {
	static $ret;

	if ( isset( $ret ) ) {
		return $ret;
	}

	$last = cerber_last_uri();

	if ( cerber_detect_exec_extension( $last ) ) {
		$ret = $last;
	}
	else {
		$ret = false;
	}

	return $ret;
}

/**
 * Detects an executable extension in a filename.
 * Supports double and N fake extensions.
 *
 * @param $line string Filename
 * @param array $extra A list of additional extensions to detect
 *
 * @return bool|string An extension if it's found, false otherwise
 */
function cerber_detect_exec_extension( $line, $extra = array() ) {
	static $executable = array( 'php', 'phtm', 'phtml', 'phps', 'shtm', 'shtml', 'jsp', 'asp', 'aspx', 'axd', 'exe', 'com', 'cgi', 'pl', 'py', 'pyc', 'pyo' );
	static $not_exec = array( 'jpg', 'png', 'svg', 'css', 'txt' );

	if ( empty( $line ) || ! strrpos( $line, '.' ) ) {
		return false;
	}

	if ( $extra ) {
		$ex_list = array_merge( $executable, $extra );
	}
	else {
		$ex_list = $executable;
	}

	$line = trim( $line );
	$line = trim( $line, '/' );

	$parts = explode( '.', $line );
	array_shift( $parts );

	// First and last are critical for most server environments
	$first_ext = array_shift( $parts );
	$last_ext = array_pop( $parts );

	if ( $first_ext ) {
		$first_ext = strtolower( $first_ext );
		if ( ! in_array( $first_ext, $not_exec ) ) {
			if ( in_array( $first_ext, $ex_list ) ) {
				return $first_ext;
			}
			if ( preg_match( '/php\d+/', $first_ext ) ) {
				return $first_ext;
			}
		}
	}

	if ( $last_ext ) {
		$last_ext = strtolower( $last_ext );
		if ( in_array( $last_ext, $ex_list ) ) {
			return $last_ext;
		}
		if ( preg_match( '/php\d+/', $last_ext ) ) {
			return $last_ext;
		}
	}

	return false;
}

/**
 * Remove extra slashes \ / from a script file name
 *
 * @return string|bool
 */
function cerber_script_filename() {
	return preg_replace( '/[\/\\\\]+/', '/', $_SERVER['SCRIPT_FILENAME'] ); // Windows server
}

function cerber_script_exists( $uri ) {
	$script_filename = cerber_script_filename();
	if ( is_multisite() && ! is_subdomain_install() ) {
		$path = explode( '/', $uri );
		if ( count( $path ) > 1 ) {
			$last = array_pop( $path );
			$virtual_sub_folder = array_pop( $path );
			$uri = implode( '/', $path ) . '/' . $last;
		}
	}
	if ( false === strrpos( $script_filename, $uri ) ) {
		return false;
	}

	return true;
}

/**
 * Activity labels and statues
 *
 * @param string $type
 * @param int $id
 *
 * @return array|string
 */
function cerber_get_labels( $type = 'activity', $id = 0 ) {

	if ( ! $labels = cerber_cache_get( 'labels' ) ) {

		// Initialize it

		$labels = array(
			'activity'    => array(),
			'activity_by' => array(),
			'status'      => array(),
		);

		$act = &$labels['activity'];
		$act_by = &$labels['activity_by'];

		// User actions
		$act[1] = __( 'User created', 'wp-cerber' );
		/* translators: Here %s is the name of a website administrator who created the user. */
		$act_by[1] = __( 'User created by %s', 'wp-cerber' );
		$act[2] = __( 'User registered', 'wp-cerber' );
		$act[3] = __( 'User deleted', 'wp-cerber' );
		/* translators: Here %s is the name of a website administrator who deleted the user. */
		$act_by[3] = __( 'User deleted by %s', 'wp-cerber' );
		$act[ CRB_EV_LIN ] = __( 'Logged in', 'wp-cerber' );
		$act[6] = __( 'Logged out', 'wp-cerber' );
		$act[ CRB_EV_LFL ] = __( 'Login failed', 'wp-cerber' );

		// WP Cerber actions - IP specific - lockouts
		$act[ CRB_EV_IPL ] = __( 'IP address locked out', 'wp-cerber' );
		$act[ CRB_EV_NEL ] = __( 'IP subnet locked out', 'wp-cerber' );

		// WP Cerber actions - denied
		$act[12] = __( 'Citadel mode activated', 'wp-cerber' );

		$act[ CRB_EV_CMS ] = __( 'Comment marked as spam', 'wp-cerber' ); // @since 9.6.1.9

		$act[ CRB_EV_SCD ] = __( 'Spam comment denied', 'wp-cerber' );
		$act[ CRB_EV_SFD ] = __( 'Spam form submission denied', 'wp-cerber' );
		$act[18] = __( 'Form submission denied', 'wp-cerber' );
		$act[19] = __( 'Comment denied', 'wp-cerber' );

		// Not in use anymore. Moved to status labels.
		//$act[13]=__('Locked out','wp-cerber');
		//$act[14]=__('IP blacklisted','wp-cerber');
		//$act[15]=__('Malicious activity detected','wp-cerber');
		// --------------------------------------------------------------

		// Other events
		$act[20] = __( 'Password changed', 'wp-cerber' );
		/* translators: Here %s is the name of a website administrator who changed the password. */
		$act_by[20] = __( 'Password changed by %s', 'wp-cerber' );
		$act[ CRB_EV_PRS ] = __( 'Password reset requested', 'wp-cerber' );
		$act[ CRB_EV_UST ] = __( 'User session terminated', 'wp-cerber' );
		/* translators: Here %s is the name of a website administrator who terminated the session. */
		$act_by[ CRB_EV_UST ] = __( 'User session terminated by %s', 'wp-cerber' );

		$act[ CRB_EV_PRD ] = __( 'Password reset request denied', 'wp-cerber' );

		// Not in use and replaced by statuses 532 - 534 since 8.9.4.
		$act[40] = __( 'reCAPTCHA verification failed', 'wp-cerber' );
		$act[41] = __( 'reCAPTCHA settings are incorrect', 'wp-cerber' );
		$act[42] = __( 'Request to the Google reCAPTCHA service failed', 'wp-cerber' );
		// --------------------------

		$act[CRB_EV_PUR] = __( 'Attempt to access prohibited URL', 'wp-cerber' );
		$act[51] = __( 'Attempt to log in with non-existing username', 'wp-cerber' );
		$act[52] = __( 'Attempt to log in with prohibited username', 'wp-cerber' );

		// WP Cerber's actions - denied
		$act[ CRB_EV_LDN ] = __( 'Attempt to log in denied', 'wp-cerber' );
		$act[54] = __( 'Attempt to register denied', 'wp-cerber' );
		$act[55] = __( 'Probing for vulnerable code', 'wp-cerber' );
		$act[56] = __( 'Attempt to upload malicious file denied', 'wp-cerber' );
		$act[57] = __( 'File upload denied', 'wp-cerber' );

		$act[70] = __( 'Request to REST API denied', 'wp-cerber' );
		$act[71] = __( 'Request to XML-RPC API denied', 'wp-cerber' );

		$act[72] = __( 'User creation denied', 'wp-cerber' );
		$act[73] = __( 'User row update denied', 'wp-cerber' );
		$act[74] = __( 'Role update denied', 'wp-cerber' );
		$act[75] = __( 'Setting update denied', 'wp-cerber' );
		$act[76] = __( 'User metadata update denied', 'wp-cerber' );

		$act[100] = __( 'Malicious request denied', 'wp-cerber' );

		// APIs
		$act[149] = __( 'User application password updated', 'wp-cerber' );
		$act[150] = __( 'User application password created', 'wp-cerber' );
		/* translators: Here %s is the name of a website administrator who created the password. */
		$act_by[150] = __( 'User application password created by %s', 'wp-cerber' );
		$act[151] = __( 'API request authorized', 'wp-cerber' );
		$act[152] = __( 'API request authorization failed', 'wp-cerber' );
		$act[153] = __( 'User application password deleted', 'wp-cerber' );
		/* translators: Here %s is the name of a website administrator who deleted the password. */
		$act_by[153] = __( 'User application password deleted by %s', 'wp-cerber' );

		// BuddyPress
		$act[200] = __( 'User activated', 'wp-cerber' );

		// Nexus (managed website)
		$act[300] = __( 'Invalid master credentials', 'wp-cerber' );

		$act[400] = __( 'Two-factor authentication enforced', 'wp-cerber' );

		// Statuses

		$sts = &$labels['status'];

		$sts[10] = __( 'Denied', 'wp-cerber' ); // @since 8.9.5.6
		$sts[ CRB_STS_11 ] = __( 'Bot detected', 'wp-cerber' );
		$sts[12] = __( 'Citadel mode is active', 'wp-cerber' );
		$sts[13] = __( 'IP address is locked out', 'wp-cerber' );
		$sts[14] = __( 'IP is blocklisted', 'wp-cerber' );
		$sts[15] = __( 'Malicious activity detected', 'wp-cerber' );
		$sts[16] = __( 'Blocked by country rule', 'wp-cerber' );
		$sts[17] = __( 'Limit reached', 'wp-cerber' );
		$sts[18] = __( 'Multiple suspicious activities', 'wp-cerber' );
		$sts[19] = __( 'Denied', 'wp-cerber' ); // @since 6.7.5

		$sts[20] = __( 'Suspicious number of fields', 'wp-cerber' );
		$sts[21] = __( 'Suspicious number of nested values', 'wp-cerber' );
		$sts[22] = __( 'Malicious code detected', 'wp-cerber' );
		$sts[23] = __( 'Suspicious SQL code detected', 'wp-cerber' );
		$sts[24] = __( 'Suspicious JavaScript code detected', 'wp-cerber' );
		$sts[ CRB_STS_25 ] = __( 'Blocked by administrator', 'wp-cerber' );
		$sts[26] = __( 'Site policy enforcement', 'wp-cerber' );
		$sts[27] = __( '2FA code verified', 'wp-cerber' );
		$sts[28] = __( 'Initiated by the user', 'wp-cerber' );
		$sts[ CRB_STS_29 ] = __( 'User blocked by administrator', 'wp-cerber' );
		$sts[ CRB_STS_30 ] = __( 'Username is prohibited', 'wp-cerber' );
		$sts[31] = __( 'Email address is prohibited', 'wp-cerber' );
		$sts[32] = __( 'User role is not allowed', 'wp-cerber' );
		$sts[33] = __( 'Permission denied', 'wp-cerber' );
		$sts[34] = __( 'Unauthorized access denied', 'wp-cerber' );
		$sts[35] = __( 'Invalid user', 'wp-cerber' );
		$sts[36] = __( 'Incorrect password', 'wp-cerber' );
		$sts[37] = __( 'IP address is not allowed', 'wp-cerber' );
		$sts[38] = __( 'User has reached the allowed number of concurrent sessions', 'wp-cerber' );
		$sts[39] = __( 'Invalid user cookies', 'wp-cerber' );
		$sts[40] = __( 'Invalid user cookies cleared', 'wp-cerber' );
		$sts[50] = __( 'Forbidden URL', 'wp-cerber' );
		$sts[ CRB_STS_51 ] = __( 'Executable file extension detected', 'wp-cerber' );
		$sts[ CRB_STS_52 ] = __( 'Filename is prohibited', 'wp-cerber' );

		$sts[ 55 ] = __( 'Pre-authentication block', 'wp-cerber' );

		// @since 8.6.4
		$sts[500] = __( 'IP is allowlisted', 'wp-cerber' );
		$sts[501] = __( 'Location exception applied', 'wp-cerber' );
		$sts[502] = __( 'Location exception applied', 'wp-cerber' );
		$sts[503] = __( 'Namespace exception applied', 'wp-cerber' );

		$sts[504] = __( 'Header exception applied', 'wp-cerber' );
		$sts[505] = __( 'Header exception applied', 'wp-cerber' );

		$sts[509] = __( 'Role-based exception applied', 'wp-cerber' );

		// IP is in the Allowed IP ACL, BUT appropriate 'Use the Allowed IP Access List' setting is NOT enabled
		// These status have different visual appearance in the logs
		$sts[510] = __( 'IP is allowlisted', 'wp-cerber' ); // TI
		$sts[511] = __( 'IP is allowlisted', 'wp-cerber' ); // DS
		$sts[512] = __( 'IP is allowlisted', 'wp-cerber' ); // DS

		$sts[520] = __( 'Access denied by plugin settings', 'wp-cerber' );

		// @since 8.9.4
		$sts[530] = __( 'Logged out everywhere', 'wp-cerber' );

		$sts[531] = __( 'reCAPTCHA verified', 'wp-cerber' );
		$sts[ CRB_STS_532 ] = __( 'reCAPTCHA verification failed', 'wp-cerber' );
		$sts[533] = __( 'reCAPTCHA settings are incorrect', 'wp-cerber' );
		$sts[534] = __( 'Request to the Google reCAPTCHA service failed', 'wp-cerber' );

		// @since 9.3.2
		$sts[540] = __( "User's IP address does not match the one used to log in", 'wp-cerber' );
		$sts[541] = __( "User's browser does not match the one used to log in", 'wp-cerber' );
		$sts[542] = __( 'Exceeded the allowed number of attempts to enter 2FA code', 'wp-cerber' );

		$sts[546] = __( 'Exceeded the allowed number of attempts to reset password', 'wp-cerber' );

		// Warning: 7xx is reserved to use in cerber_get_reason()

		cerber_cache_set( 'labels', $labels );
	}

	if ( $id ) {

		if ( isset( $labels[ $type ][ $id ] ) ) {
			return $labels[ $type ][ $id ];
		}

		/* translators: %1$d is the label ID, %2$s is the label type. */
		return sprintf( __( 'Unknown label (%1$d/%2$s)', 'wp-cerber' ), absint( $id ), $type );
	}

	return $labels[ $type ];
}

/**
 * Returns a label to be displayed in the logs
 *
 * @param int $activity
 * @param int $user_id
 * @param int $by_user_id
 * @param bool $link
 *
 * @return string
 *
 * @since 8.9.5.1
 */
function crb_get_activity_label( $activity, $user_id = 0, $by_user_id = 0, $link = true ) {
	static $user_link = array();

	if ( $by_user_id
	     && $user_id != $by_user_id
	     && $user_data = crb_get_userdata( $by_user_id ) ) {

		if ( $link ) {
			if ( empty( $user_link[ $by_user_id ] ) ) {
				$user_link[ $by_user_id ] = get_edit_user_link( $by_user_id );
			}
			$user_name = '<a href="' . $user_link[ $by_user_id ] . '">' . $user_data->display_name . '</a>';
		}
		else {
			$user_name = $user_data->display_name;
		}

		return sprintf( cerber_get_labels( 'activity_by', $activity ), $user_name );
	}

	return cerber_get_labels( 'activity', $activity );
}

/**
 * Returns a predefined set of activity type IDs to build URLs and generates SQL clauses
 * Safe to use in any context.
 *
 * @param int $set_id The set IDs
 *
 * @return int[]
 */
function crb_get_filter_set( $set_id ) {
	static $list = array( 1 => 'suspicious', 2 => 'login_issues', 3 => 'spam' );

	if ( ! isset( $list[ $set_id ] ) ) {
		return array();
	}

	return crb_get_activity_set( $list[ $set_id ] );
}

/**
 * Returns a predefined set of activity type IDs.
 *
 * @param string $set_id
 * @param bool $implode
 *
 * @return int[]|string
 */
function crb_get_activity_set( $set_id = 'malicious', $implode = false ) {

	static $sets = array(
		'malicious'  => array( CRB_EV_CMS, CRB_EV_SCD, CRB_EV_SFD, CRB_EV_PRD, 40, CRB_EV_PUR, 51, 52, CRB_EV_LDN, 54, 55, 56, 100 ),

		// Like 'malicious' but causes an IP lockout when hit the limit
		'mitigation' => array( 40, CRB_EV_PUR, 51, 52, CRB_EV_LDN, 55, 56, 100, 300 ),

		// Uses when an admin inspects logs with filter_set = 1
		'suspicious' => array( CRB_EV_IPL, CRB_EV_NEL, CRB_EV_CMS, CRB_EV_SCD, CRB_EV_SFD, CRB_EV_PRD, 40, CRB_EV_PUR, 51, 52, CRB_EV_LDN, 54, 55, 56, 57, 70, 71, 72, 73, 74, 75, 76, 100, 300 ),

		// Important events for the plugin dashboard metrics
		'dashboard' => array( 1, 2, 3, CRB_EV_LIN, 12, CRB_EV_CMS, CRB_EV_SCD, CRB_EV_SFD, 18, 19, CRB_EV_UST, 40, 41, 42, CRB_EV_PUR, 51, 52, CRB_EV_LDN, 54, 55, 56, 57, 72, 73, 74, 75, 76, 100, 149, 150, 200, 300, 400 ),

		'denied_by_crb' => array( 12, CRB_EV_CMS, CRB_EV_SCD, CRB_EV_SFD, 18, 19, CRB_EV_PRD, 41, 42, CRB_EV_PUR, 51, 52, CRB_EV_LDN, 54, 55, 56, 57, 70, 71, 72, 73, 74, 75, 76, 100 ),

		'login_issues' => array( CRB_EV_LFL, CRB_EV_PRS, CRB_EV_PRD, 51, 52, CRB_EV_LDN, 152 ),

		'blocked' => array( CRB_EV_IPL, CRB_EV_NEL ),

		'spam' => array( CRB_EV_CMS, CRB_EV_SCD, CRB_EV_SFD ),
	);

	if ( ! $set = $sets[ $set_id ] ?? false ) {
		return []; // @since 9.7
	}

	$set = array_map( 'absint', $set );

	if ( ! $implode ) {
		return $set;
	}

	return implode( ',', $set );

}

/**
 * Textual description of the reason the IP is locked out.
 *
 * @param int $reason_id
 * @param string $default
 *
 * @return string[]|string
 */
function crb_get_lockout_reason( $reason_id = null, $default = '' ) {

	if ( ! $reasons = cerber_cache_get( 'reasons' ) ) {

		$reasons = array();
		$reasons[701] = __( 'Exceeded the allowed number of login attempts', 'wp-cerber' );
		$reasons[702] = __( 'Attempt to access prohibited URL', 'wp-cerber' );
		$reasons[703] = __( 'Attempt to log in with non-existing username', 'wp-cerber' );
		$reasons[704] = __( 'Attempt to log in with prohibited username', 'wp-cerber' );
		$reasons[705] = __( 'Exceeded the allowed number of failed reCAPTCHA verifications', 'wp-cerber' );
		$reasons[706] = __( 'Excessive spam activity detected', 'wp-cerber' );
		$reasons[707] = __( 'Multiple suspicious activities detected', 'wp-cerber' );
		$reasons[708] = __( 'Probing for vulnerable code', 'wp-cerber' );
		$reasons[709] = __( 'Malicious code detected', 'wp-cerber' );
		$reasons[710] = __( 'Attempt to upload a file with malicious code', 'wp-cerber' );

		$reasons[711] = __( 'Too many erroneous requests', 'wp-cerber' );
		$reasons[712] = __( 'Too many suspicious requests', 'wp-cerber' );

		$reasons[721] = __( 'Exceeded the allowed number of attempts to enter 2FA code', 'wp-cerber' );

		$reasons[725] = __( 'Exceeded the allowed number of attempts to reset password', 'wp-cerber' );

		cerber_cache_set( 'reasons', $reasons );

	}

	if ( $reason_id ) {
		if ( isset( $reasons[ $reason_id ] ) ) {
			return $reasons[ $reason_id ];
		}

		if ( $default ) {
			return $default;
		}
		else {
			return __( 'Unknown', 'wp-cerber' );
		}
	}

	return $reasons;

}

function cerber_db_error_log( $errors = array() ) {
	global $wpdb;

	if ( ! $errors ) {
		$errors = array();

		if ( ! empty( $wpdb->last_error ) ) {
			$errors = array( array( $wpdb->last_error, $wpdb->last_query, microtime( true ) ) );
		}

		if ( $others = cerber_db_get_errors( true, false ) ) {
			$errors = array_merge( $errors, $others );
		}
	}

	if ( ! $errors ) {
		return;
	}

	if ( ! $old = get_site_option( '_cerber_db_errors' ) ) {
		$old = array();
	}

	update_site_option( '_cerber_db_errors', array_merge( $old, $errors ) );
}

/**
 * Extracts and saves error message(s) to show them as admin error messages
 *
 * @param WP_Error $err
 *
 * @since 9.6.2.1
 */
function crb_admin_error_notice( $err ) {

	if ( ! $messages = $err->get_error_messages() ) {
		return;
	}

	crb_add_admin_error( $messages );
}

/**
 * Adds localized "ERROR:" prefix to error message(s) and queues them for display.
 *
 * These messages are displayed when WordPress renders the WP Cerber admin page.
 *
 * @param string|string[] $messages Single error message or a list of error message.
 *
 * @return void
 *
 * @since 9.6.10.3
 */
function crb_add_admin_error( $messages ) {

	if ( is_string( $messages ) ) {
		$messages = array( $messages );
	}

	if ( ! is_array( $messages ) ) {
		return;
	}

	array_walk( $messages, function ( &$msg ) {
		/* translators: %s is the error message. */
		$msg = sprintf( __( 'ERROR: %s', 'wp-cerber' ), $msg );
	} );

	cerber_admin_notice( $messages );
}

/**
 * Queues error (red) admin message(s) to be displayed if a website admin is logged in and the admin page is being displayed
 *
 * @param string|array $msg
 *
 * @since 9.6.2.3
 */
function crb_admin_notice_interactive( $msg ) {
	if ( ! is_admin()
	     || ! crb_is_user_logged_in()
	     || ! is_super_admin() ) {
		return false;
	}

	cerber_admin_notice( $msg );
}

/**
 * Queues error (red) admin message(s) to be displayed in the dashboard.
 *
 * @param string|array $msg
 */
function cerber_admin_notice( $msg ) {
	crb_admin_add_msg( $msg, false, CRB_ADMIN_NOTICE );
}

/**
 * Queues an error notice to be displayed to site administrators.
 *
 * Notice will NOT be shown if $failure has no errors.
 *
 * See crb_admin_error_notice() for the WP_Error counterpart, which takes no
 * summary and reports the messages of a WP_Error as they are.
 *
 * @param string $notice Human-readable description of what could not be completed.
 * @param Revalt $failure Result returned by the failing operation.
 *
 * @return void
 *
 * @since 9.9.4
 */
function crb_admin_notice_failure( string $notice, Revalt $failure ) {

	if ( ! $failure->has_errors() ) {
		return;
	}

	$failure_messages = $failure->get_error_messages();

	// A chained result can carry a generic message at the top, so the deepest reason is added when it is not already reported.

	$root_cause = crb_array_get( $failure->get_root_cause(), 'message', '' );

	if ( $root_cause
	     && ! in_array( $root_cause, $failure_messages, true ) ) {
		$failure_messages[] = $root_cause;
	}

	crb_add_admin_error( $notice . ' ' . crb_escape_html( implode( ' ', $failure_messages ) ) );
}

/**
 * Queues success (green) admin notification message(s) to be displayed in the dashboard.
 *
 * @param string|string[] $msg
 * @param bool $prepend If true, the given message(s) will be shown above existing ones
 *
 * @return void
 */
function cerber_admin_message( $msg, $prepend = false ) {
	crb_admin_add_msg( $msg, $prepend, CRB_ADMIN_MESSAGE );
}

/**
 * Queues admin messages to be displayed in the WP Cerber dashboard
 *
 * @param string|string[] $new_msg
 * @param bool $prepend If true, the given message(s) will be shown above existing ones
 * @param string $type CRB_ADMIN_MESSAGE | CRB_ADMIN_NOTICE
 *
 * @return void
 */
function crb_admin_add_msg( $new_msg, $prepend = false, string $type = CRB_ADMIN_MESSAGE ) {

	if ( ! $new_msg || CRB_Globals::$doing_upgrade ) {
		return;
	}

	if ( ! is_array( $new_msg ) ) {
		$new_msg = array( $new_msg );
	}

	$messages = cerber_get_set( $type );

	if ( ! $messages || ! is_array( $messages ) ) {
		$messages = array();
	}

	if ( $messages ) {

		// Preventing duplicate messages

		foreach ( $new_msg as $key => $str_1 ) {
			foreach ( $messages as $str_2 ) {
				if ( sha1( $str_1 ) == sha1( $str_2 ) ) {
					unset( $new_msg[ $key ] );
				}
			}
		}

		if ( ! $new_msg ) {
			return;
		}
	}

	if ( $prepend ) {
		foreach ( $new_msg as $item ) {
			array_unshift( $messages, $item );
		}
	}
	else {
		$messages = array_merge( $messages, $new_msg );
	}

	cerber_update_set( $type, $messages );
}

function crb_clear_admin_msg() {
	cerber_update_set( CRB_ADMIN_NOTICE, array() );
	cerber_update_set( CRB_ADMIN_MESSAGE, array() );
	cerber_update_set( CRB_ADMIN_WIDE, '' );
}

/**
 * Check if the currently displaying (rendering) page is a WP Cerber admin page.
 * Optionally checks a set of GET params.
 * Optionally checks for the base of the WordPress admin screen.
 *
 * @param array $params Optional GET params to check
 * @param string $screen_base Optional WP admin screen
 *
 * @return bool True on a WP Cerber page
 */
function cerber_is_admin_page( $params = array(), $screen_base = '' ) {

	// If a non-admin location, return false

	if ( ! is_admin()
	     && ! nexus_is_valid_request() ) {
		return false;
	}

	// Check query params

	$query_params = crb_get_query_params();
	$is_cerber_page = false;

	if ( false !== strpos( $query_params['page'] ?? '', 'cerber-' ) ) {
		$is_cerber_page = true;
		if ( $params ) {
			foreach ( $params as $param => $value ) {
				if ( ! isset( $query_params[ $param ] ) ) {
					$is_cerber_page = false;
					break;
				}
				if ( ! is_array( $value ) ) {
					if ( $query_params[ $param ] != $value ) {
						$is_cerber_page = false;
						break;
					}
				}
				elseif ( ! in_array( $query_params[ $param ], $value ) ) {
					$is_cerber_page = false;
					break;
				}
			}
		}
	}

	if ( $is_cerber_page || ! $screen_base ) {
		return $is_cerber_page;
	}

	if ( ! function_exists( 'get_current_screen' ) || ! $screen = get_current_screen() ) {
		return false;
	}

	if ( $screen->base == $screen_base ) {
		return true;
	}

	return false;
}

/**
 * Check if the currently displaying (rendering) page is a WP Cerber dashboard page.
 *
 * @since 9.7
 */
function cerber_is_dashboard() {
	if ( cerber_is_admin_page( array( 'page' => 'cerber-security' ) ) ) {
		$query_params = crb_get_query_params();

		if ( 'dashboard' == ( $query_params['tab'] ?? '' ) ) {
			return true;
		}

		return count( $query_params ) === 1;
	}

	return false;
}

/**
 * Calculates the difference between curren time and returns human-readable "ago" time
 *
 * @param int|string|float $time Unix timestamp - time of an event
 *
 * @return string
 */
function cerber_ago_time( $time ): string {

	if ( ! is_numeric( $time ) ) {
		return __( 'Invalid Value', 'wp-cerber' );
	}

	$time = (int) $time;
	$current_time = time();

	$diff = abs( $current_time - $time );

	if ( $diff < MINUTE_IN_SECONDS ) {
		$secs = ( $diff <= 1 ) ? 1 : $diff;
		/* translators: Indicates time difference in seconds. Placeholder %d will be replaced by the number of seconds, e.g., "1 sec" or "45 secs". */
		$dt = sprintf( _n( '%d sec', '%d secs', $secs, 'wp-cerber' ), $secs );
	}
	else {
		$dt = human_time_diff( $time );
	}

	if ( $time <= $current_time ) {
		/* translators: Ago refers to a period in the past, starting backwards from now. Placeholder %s will be replaced by a time period in the past, e.g., "2 days ago", "1 hour ago". */
		return sprintf( __( '%s ago', 'wp-cerber' ), $dt );
	}

	/* translators: It is used as a preposition to describe future time, e.g., "in 6 hours" or "in 2 days". */
	return sprintf( _x( 'in %s', 'Preposition to describe future time', 'wp-cerber' ), $dt );
}

function cerber_auto_date( $time, $purify = true ): string {
	if ( ! $time ) {
		return __( 'Never', 'wp-cerber' );
	}

	return $time < ( time() - DAY_IN_SECONDS ) ? cerber_date( $time, $purify ) : cerber_ago_time( $time );
}

/**
 * Format date according to user settings and timezone
 *
 * @param $timestamp int Unix timestamp
 * @param $purify boolean If true adds html to have a better look on a web page
 *
 * @return string
 */
function cerber_date( $timestamp, $purify = true ): string {
	static $gmt_offset;

	if ( $gmt_offset === null ) {
		$gmt_offset = get_option( 'gmt_offset' ) * 3600;
	}

	$timestamp = $gmt_offset + absint( $timestamp );

	// @since 8.6.4: snippet is taken from new date_i18n()
	if ( function_exists( 'wp_date' ) ) { // wp_date() introduced in WP 5.3
		$local_time = gmdate( 'Y-m-d H:i:s', $timestamp );
		$timezone = wp_timezone();
		$datetime = date_create( $local_time, $timezone );
		$date = wp_date( cerber_get_dt_format(), $datetime->getTimestamp(), $timezone );
	}
	else { // Older WP
		$date = date_i18n( cerber_get_dt_format(), $timestamp );
	}

	if ( $purify ) {
		$date = str_replace( array( ',', ' am', ' pm', ' AM', ' PM' ), array( ',<wbr>', '&nbsp;am', '&nbsp;pm', '&nbsp;AM', '&nbsp;PM' ), $date );
	}

	return (string) $date;
}

function cerber_get_dt_format() {
	static $ret;

	if ( $ret !== null ) {
		return $ret;
	}

	if ( $ret = crb_get_settings( 'dateformat' ) ) {
		return $ret;
	}

	$ret = crb_get_default_dt_format();

	return $ret;

}

function crb_get_default_dt_format() {
	$tf = get_option( 'time_format' );
	$df = get_option( 'date_format' );

	return $df . ', ' . $tf;
}

function cerber_is_ampm() {
	if ( 'a' == strtolower( substr( trim( get_option( 'time_format' ) ), - 1 ) ) ) {
		return true;
	}

	return false;
}

function cerber_sec_from_time( $time ) {
	list( $h, $m ) = explode( ':', trim( $time ) );
	$h = absint( $h );
	$m = absint( $m );
	$ret = $h * 3600 + $m * 60;

	if ( strpos( strtolower( $time ), 'pm' ) ) {
		$ret += 12 * 3600;
	}

	return $ret;
}

function cerber_percent( $one, $two ) {
	if ( $one == 0 ) {
		if ( $two > 0 ) {
			$ret = '100';
		}
		else {
			$ret = '0';
		}
	}
	else {
		$ret = round( ( ( ( $two - $one ) / $one ) ) * 100 );
	}
	$style = '';
	if ( $ret < 0 ) {
		$style = 'color:#008000';
	}
	elseif ( $ret > 0 ) {
		$style = 'color:#FF0000';
	}
	if ( $ret > 0 ) {
		$ret = '+' . $ret;
	}

	return '<span style="' . $style . '">' . $ret . ' %</span>';
}

function crb_size_format( $fsize ) {
	$fsize = absint( $fsize );

	return ( $fsize < 1024 ) ? $fsize . '&nbsp;' . __( 'Bytes', 'wp-cerber' ) : size_format( $fsize );
}

/**
 * Return a user by login or email with automatic detection
 *
 * @param $login_email string login or email
 *
 * @return false|WP_User
 */
function cerber_get_user( $login_email ) {

	crb_load_dependencies( 'get_user_by' );

	if ( is_email( $login_email ) ) {
		return get_user_by( 'email', $login_email );
	}

	return get_user_by( 'login', $login_email );
}

/**
 * Retrieves user info by user ID
 *
 * The user object is looked up once per request and kept in a static cache. Every call
 * returns an independent copy of the cached object, so a caller may modify the object it
 * received without affecting any later caller. This preserves the guarantee of
 * get_user_by(), which builds a fresh object on every call, and which this cache would
 * otherwise silently take away: a destructive read such as array_shift() on the roles
 * property would consume the roles of the user for the rest of the request.
 *
 * The copy covers the user object and the data object carrying the user fields, which are
 * the two containers WP_User writes to. Array properties are copied by the value semantics
 * of PHP arrays. A value reachable only through a further nested object is shared with the
 * cached instance and must not be modified.
 *
 * @param int $user_id User ID. A falsy ID yields false without a lookup.
 *
 * @return false|WP_User An independent copy of the cached user object, or false when no
 *                       user with the given ID exists.
 *
 * @since 8.9.5.1
 */
function crb_get_userdata( $user_id ) {
	static $users;

	if ( ! $user_id ) {
		return false;
	}

	if ( ! isset( $users[ $user_id ] ) ) {

		crb_load_dependencies( 'get_user_by' );

		$users[ $user_id ] = get_user_by( 'id', $user_id );
	}

	// A user that does not exist is cached as false and reported as such.

	if ( ! $users[ $user_id ] ) {
		return false;
	}

	// Hand out a copy. The clone operator is shallow, so the data object holding the user
	// fields is copied on its own, otherwise a write through WP_User would reach the cache.

	$user_copy = clone $users[ $user_id ];
	$user_copy->data = clone $users[ $user_id ]->data;

	return $user_copy;
}

/**
 * Checks whether a database table exists.
 *
 * Thin compatibility wrapper over CRB_Schema_Manager::table_exists(). A database
 * dependency or query failure is collapsed to false to preserve the original
 * boolean contract. Callers that must distinguish an absent table from a failed
 * lookup should call CRB_Schema_Manager::table_exists() directly and inspect the Revalt.
 *
 * @param string $table Plain or database-qualified table name.
 *
 * @return bool True when the table exists.
 */
function cerber_db_is_table( string $table ): bool {
	$result = warp_run_db( function ( CRB_Database $db ) use ( $table ): Revalt {
		return ( new CRB_Schema_Manager( $db ) )->table_exists( $table );
	} );

	if ( $result->has_errors() ) {
		return false;
	}

	return (bool) $result->get_results( false );
}

/**
 * Checks if a DB table is empty.
 *
 * @param string $table Table name
 *
 * @return bool True if the table is empty, false if it contains any data
 *
 * @since 9.6.3.2
 */
function cerber_db_is_empty( string $table ): bool {

	if ( cerber_db_get_var( 'SELECT EXISTS(SELECT 1 FROM ' . cerber_db_ident( $table ) . ' LIMIT 1)' ) ) {
		return false;
	}

	return true;
}

/**
 * Check if a column is defined in a table
 *
 * @param $table string DB table name
 * @param $column string Field name
 *
 * @return bool true if field exists in a table
 */
function cerber_db_is_column( $table, $column ) {

	$table = preg_replace( '/[^\w\-]/', '', $table );
	$column = preg_replace( '/[^\w\-]/', '', $column );

	if ( cerber_db_get_results( 'SHOW FIELDS FROM ' . $table . ' WHERE FIELD = "' . $column . '"' ) ) {
		return true;
	}

	return false;
}

/**
 * Check if a table has an index
 *
 * @param $table string DB table name
 * @param $key string Index name
 *
 * @return bool true if an index defined for a table
 */
function cerber_db_is_index( $table, $key ) {

	$table = preg_replace( '/[^\w\-]/', '', $table );
	$key = preg_replace( '/[^\w\-]/', '', $key );

	if ( cerber_db_get_results( 'SHOW INDEX FROM ' . $table . ' WHERE KEY_NAME = "' . $key . '"' ) ) {
		return true;
	}

	return false;
}

/**
 * Return reCAPTCHA language code for reCAPTCHA widget
 *
 * @return string
 */
function cerber_recaptcha_lang() {
	static $lang = '';
	if ( ! $lang ) {
		$lang = crb_get_bloginfo( 'language' );
		//$trans = array('en-US' => 'en', 'de-DE' => 'de');
		//if (isset($trans[$lang])) $lang = $trans[$lang];
		$lang = substr( $lang, 0, 2 );
	}

	return $lang;
}

/**
 * Checks for a new version of WP Cerber and generates messages if needed
 *
 * @param bool $check_only if true, no messages are generated / sent
 *
 * @return string A new version, empty string otherwise
 *
 */
function cerber_check_new_version( $check_only = true ) {
	if ( ! $updates = get_site_transient( 'update_plugins' ) ) {
		return '';
	}

	$result = false;
	$new_ver = '';
	$key = CERBER_PLUGIN_ID;

	if ( isset( $updates->checked[ $key ] )
	     && isset( $updates->response[ $key ] ) ) {

		$old_ver = $updates->checked[ $key ];
		$new_ver = crb_boring_escape( $updates->response[ $key ]->new_version );

		if ( 1 === version_compare( $new_ver, $old_ver ) ) {
			$result = true;
		}
		else {
			$new_ver = '';
		}
	}

	if ( $check_only || ! $result ) {
		return $new_ver;
	}

	$link = 'https://wpcerber.com/?plugin_version=' . $new_ver;
	$msg_one = sprintf(
		__( 'A new version of WP Cerber Security is available. We recommend updating. To stay up to date with security and performance improvements, enable automatic updates. %s', 'wp-cerber' ),
		'<a href="https://wpcerber.com/automatic-updates-for-wp-cerber/" target="_blank" rel="noopener noreferrer">' . __( 'Learn how', 'wp-cerber' ) . '</a>.'
	);

	$msg_two = __( 'See what’s new in this version:', 'wp-cerber' );

	if ( ! cerber_get_set( '_cerber_message_new', 0, false ) ) {
		cerber_admin_message( array( $msg_one, $msg_two . ' ' . '<a href="' . $link . '" target="_blank">' . $link . '</a>' ) );
		cerber_update_set( '_cerber_message_new', 1, 0, false, time() + HOUR_IN_SECONDS );
	}

	if ( ! crb_get_settings( 'notify-new-ver' ) ) {
		return $new_ver;
	}

	$history = get_site_option( '_cerber_notify_new' );

	if ( ! $history || ! is_array( $history ) ) {
		$history = array();
	}

	if ( in_array( $new_ver, $history ) ) {
		return $new_ver;
	}

	/* translators: %s is the new plugin version number available for update. */
	CRB_Messaging::send( 'new_version', array(
		'subj' => sprintf( __( 'WP Cerber %s is available. Please update.', 'wp-cerber' ), $new_ver ),
		'text' => array( $msg_one, $msg_two . ' ' . $link )
	) );

	$history[] = $new_ver;
	update_site_option( '_cerber_notify_new', $history );

	return $new_ver;
}

/**
 * Is user agent string indicates bot (crawler)
 *
 * @param $ua
 *
 * @return integer 1 if ua string contains a bot definition, 0 otherwise
 * @since 6.0
 */
function cerber_is_crawler( $ua ) {
	if ( ! $ua ) {
		return 0;
	}
	$ua = strtolower( $ua );
	if ( preg_match( '/\(compatible\;(.+)\)/', $ua, $matches ) ) {
		$bot_info = explode( ';', $matches[1] );
		foreach ( $bot_info as $item ) {
			if ( strpos( $item, 'bot' )
			     || strpos( $item, 'crawler' )
			     || strpos( $item, 'spider' )
			     || strpos( $item, 'Yahoo! Slurp' )
			) {
				return 1;
			}
		}
	}
	elseif ( 0 === strpos( $ua, 'Wget/' ) ) {
		return 1;
	}

	return 0;
}

/**
 * Escapes a string for safe use in an SQL statement, using the database connection's character set.
 *
 * This function provides a wrapper around `mysqli_real_escape_string()`.
 *
 * If the database connection is unavailable, the function returns an empty
 * string as a best effort fallback.
 *
 * @reason: https://make.wordpress.org/core/2017/10/31/changed-behaviour-of-esc_sql-in-wordpress-4-8-3/
 *
 * @param string $str The string to be escaped.  Will be cast to a string.
 *
 * @return string The escaped string (no surrounding quotes), ready for use in an SQL query. Returns an empty string if
 *                the database connection is unavailable or the input string is empty (except
 *                when the input is the string "0", which returns "0").
 *@since 6.0
 *
 *@version Sequoia
 */
function cerber_db_real_escape( $str ): string {

	$str = (string) $str;

	if ( $str === '' ) {
		return '';
	}

	$mysqli = cerber_db_get_connection();
	if ( ! $mysqli ) {
		return '';
	}

	return mysqli_real_escape_string( $mysqli, $str );
}

/**
 * @param bool $erase
 * @param bool $flat If true returns an array of error messages, otherwise a multidimensional array
 *
 * @return array
 */
function cerber_db_get_errors( $erase = false, $flat = true ) {

	if ( ! isset( CRB_Globals::$db_errors ) ) {
		CRB_Globals::$db_errors = array();
	}

	$ret = (array) CRB_Globals::$db_errors;

	if ( $erase ) {
		CRB_Globals::$db_errors = array();
	}

	if ( $ret && $flat ) {
		$ret = array_map( function ( $e ) {
			if ( is_array( $e ) ) {
				return implode( ' ', $e );
			}

			return $e;
		}, $ret );
	}

	return $ret;
}

/**
 * Execute a direct SQL query on the website database
 *
 * The reason: https://make.wordpress.org/core/2017/10/31/changed-behaviour-of-esc_sql-in-wordpress-4-8-3/
 *
 * @param $query string An SQL query
 * @param int $ignore_error A MySQL error code to ignore when journaling errors (default 0 - do not ignore).  E.g., 1062 for duplicates.
 *
 * @return bool|mysqli_result False on any failure
 *
 * @since 6.0
 */
function cerber_db_query( $query, int $ignore_error = 0 ) {
	global $wpdb;

	if ( ! $mysqli = cerber_db_get_connection() ) {

		CRB_Globals::$db_errors[] = 'No database connection. Query failed: ' . $query;

		return false;
	}

	if ( defined( 'CRB_SAVEQUERIES' ) && CRB_SAVEQUERIES ) {
		$started = microtime( true );
	}

	$error_msg = '';

	//$ret = mysqli_query( $mysqli, $query, MYSQLI_USE_RESULT );
	if ( ! $ret = mysqli_query( $mysqli, $query ) ) {
		$error_code = mysqli_errno( $mysqli );
		$error_msg = mysqli_error( $mysqli );
		if ( $error_msg && $error_code && $error_code != $ignore_error ) {
			CRB_Globals::$db_errors[] = array( 'ERROR ' . $error_code . ': ' . $error_msg, $query, microtime( true ) );
		}
	}

	// cerber_check_groove()
	if ( defined( 'CRB_SAVEQUERIES' ) && CRB_SAVEQUERIES && is_object( $wpdb ) ) {
		$elapsed = microtime( true ) - $started;

		$backtrace = '';
		if ( function_exists( 'wp_debug_backtrace_summary' ) ) {
			$backtrace = wp_debug_backtrace_summary();
		}

		$stat = array( $query, $elapsed, $backtrace, $started, array( $error_msg ) );
		CRB_Globals::$db_requests[] = $stat;

		$wpdb->queries[] = $stat;
	}

	return $ret;
}

/**
 * Returns the number of affected rows from the most recent MySQL recent INSERT, UPDATE, DELETE, REPLACE query.
 *
 * @return int  The number of affected rows:
 *                A positive integer if rows were affected.
 *                0 if the query executed successfully, but no rows were changed *OR* if there is no active DB connection.
 *                -1 if the last query was not an INSERT, UPDATE, DELETE, or REPLACE, *OR* if a DB error occurred.
 *
 * @since 9.0.2
 *
 * @see cerber_db_query()
 */
function cerber_db_get_affected(): int {

	if ( $mysqli = cerber_db_get_connection() ) {
		return $mysqli->affected_rows;
	}

	return 0;
}

/**
 * Executes an SQL query and returns the results as an array.
 *
 * This function is a wrapper around mysqli_query and mysqli_fetch_all/mysqli_fetch_assoc/mysqli_fetch_object/mysqli_fetch_row.
 *
 *
 * @param string $query The SQL query to execute.  Must be properly escaped.
 * @param int    $type  (Optional) The type of array to return.
 *                       Can take the following values:
 *                       - MYSQLI_ASSOC (default): Returns an associative array where the keys are column names.
 *                       - MYSQL_FETCH_OBJECT: Returns an array of objects where the object properties are columns.
 *                       - MYSQL_FETCH_OBJECT_K: Returns an array of objects where the keys of the array are the values of the first column of the result,
 *                         and the values are objects representing the rows. If the first column is not unique,
 *                         then later rows will overwrite earlier ones with the same key.
 *                       - MYSQLI_NUM: Returns an array with numeric indices.
 *
 * @return array The results of the SQL query as an array. Returns an empty array if the query returned no results or an error occurred.
 *
 * @see cerber_db_get_errors()
 *
 */
function cerber_db_get_results( $query, $type = MYSQLI_ASSOC ) {

	if ( ! $result = cerber_db_query( $query ) ) {
		return array();
	}

	if ( $type === MYSQLI_ASSOC
	     && function_exists( 'mysqli_fetch_all' ) ) {

		$ret = mysqli_fetch_all( $result, MYSQLI_ASSOC ); // Requires mysqlnd driver

	}
	else {

		$ret = array();

		switch ( $type ) {
			case MYSQLI_ASSOC:
				while ( $row = mysqli_fetch_assoc( $result ) ) {
					$ret[] = $row;
				}
				break;
			case MYSQL_FETCH_OBJECT:
				while ( $row = mysqli_fetch_object( $result ) ) {
					$ret[] = $row;
				}
				break;
			case MYSQL_FETCH_OBJECT_K:
				while ( $row = mysqli_fetch_object( $result ) ) {
					$vars = get_object_vars( $row );
					$key = array_shift( $vars );
					$ret[ $key ] = $row;
				}
				break;
			default:
				while ( $row = mysqli_fetch_row( $result ) ) {
					$ret[] = $row;
				}
		}
	}

	mysqli_free_result( $result );

	return $ret;
}

/**
 * @param string $query
 * @param int $type
 *
 * @return false|array|object False if no data were retrieved
 */
function cerber_db_get_row( $query, $type = MYSQLI_ASSOC ) {

	if ( ! $result = cerber_db_query( $query ) ) {
		return false;
	}

	if ( $type == MYSQL_FETCH_OBJECT ) {
		$ret = $result->fetch_object();
	}
	else {
		$ret = $result->fetch_array( $type );
	}

	$result->free();

	return $ret;
}

/**
 * Executes an SQL query and returns the values of the first column of all rows in the result set.
 *
 * @param string $query The SQL query to execute
 * @return array A numerically indexed array of values from the first column of the query result
 */
function cerber_db_get_col( $query ) {
	if ( ! $result = cerber_db_query( $query ) ) {
		return array();
	}

	$ret = array();

	if ( function_exists( 'mysqli_fetch_all' ) ) {
		$rows = $result->fetch_all( MYSQLI_NUM );
		$ret = array_column( $rows, 0 );
		unset( $rows );
	}
	else {
		// No mysqlnd installed
		while ( $row = $result->fetch_row() ) {
			$ret[] = $row[0];
		}
	}

	$result->free();

	return $ret;
}

/**
 * Returns the value of the first field from the first row
 *
 * @param string $query
 *
 * @return bool|mixed
 */
function cerber_db_get_var( $query ) {

	if ( ! $result = cerber_db_query( $query ) ) {
		return false;
	}

	$row = mysqli_fetch_row( $result );
	$result->free();

	return $row ? $row[0] : false;
}

/**
 * Counts rows in a specified database table with optional conditions.
 *
 * @param string $table The name of the database table to count rows from.
 * @param string $field The field to be counted (default is '*'). Recommended to use an indexed field.
 * @param array $key_fields Optional associative array of conditional fields for the WHERE clause.
 *                            Format: ['column_name' => 'value', ...]
 *
 * @return int|false The number of rows matching the conditions, or 0 if an error occurs (e.g., non-ASCII input for $table, $field).
 *
 * @since 9.6.3.2
 */
function cerber_db_count( string $table, string $field = '*', array $key_fields = array() ) {

	if ( ! $table = cerber_db_ident( $table ) ) {
		return false;
	}

	if ( ! $field = cerber_db_ident( $field ) ) {
		$field = '*';
	}

	$where = $key_fields ? ' WHERE ' . cerber_db_make_where( $table, $key_fields ) : '';

	return cerber_db_get_var( 'SELECT COUNT(' . $field . ') FROM ' . $table . $where );
}

/**
 * Validate and normalize a simple MySQL identifier.
 *
 * Accepts a plain identifier, such as "column_name" or "123", or a single
 * qualified identifier, such as "table_name.column_name" or "123.456".
 * The function intentionally does not support quoted identifiers, wildcards,
 * expressions, reserved-word detection, or multi-level notation such as
 * "database.table.column".
 *
 * Allowed characters:
 * - ASCII letters A-Z and a-z
 * - digits 0-9, including as the first character
 * - underscore _
 * - one dot as a qualifier separator
 *
 * @param string $ident Raw identifier name.
 *
 * @return string Validated identifier, or an empty string if invalid.
 *
 * @since 9.6.16
 */
function cerber_db_ident( $ident ): string {
	$ident = (string) $ident;
	$ident = trim( $ident );

	if ( $ident === '' ) {
		return '';
	}

	if ( ! preg_match( '/\A[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?\z/', $ident ) ) {
		return '';
	}

	return $ident;
}


/**
 * Inserts a row into the specified database table, escaping table name, column names and values.
 *
 * @param string $table The table name (will be escaped).
 * @param array $data_fields An associative array of data to insert (column => value).
 * @param int $ignore_error A MySQL error code to ignore for journaling purposes (default 0 - do not ignore).  E.g., 1062 for duplicates.
 *
 * @return bool Returns true on success, false on failure
 *
 */
function cerber_db_insert( string $table, array $data_fields, int $ignore_error = 0 ) {

	$table = cerber_db_ident( $table );
	$fields = array_keys( $data_fields );
	$fields = array_map( 'cerber_db_ident', $fields );

	$values = array_map( function ( $value, $field ) use ( $table ) {
		return cerber_db_prepare( $table, $field, $value );
	}, $data_fields, array_keys( $data_fields ) );

	return cerber_db_query( 'INSERT INTO ' . $table . ' (' . implode( ',', $fields ) . ') VALUES (' . implode( ',', $values ) . ')', $ignore_error );
}

/**
 * Updates a row in the specified database table, escaping column names and values.
 *
 * @param string $table       The table name (will be escaped).
 * @param array  $key_fields  An associative array specifying the WHERE clause (column => value). Column names and values will be escaped/prepared.
 * @param array  $data_fields An associative array of data to update (column => value). Column names and values will be escaped/prepared.
 *
 * @return bool Returns true on success, false on failure.
 *
 * @since 8.8.6.3
 */
function cerber_db_update( string $table, array $key_fields, array $data_fields ) {

	if ( ! $where = cerber_db_make_where( $table, $key_fields ) ) {
		return false;
	}

	$set = array();

	foreach ( $data_fields as $field => $value ) {
		$set[] = cerber_db_ident( $field ) . ' = ' . cerber_db_prepare( $table, $field, $value );
	}

	$set = implode( ',', $set );

	return cerber_db_query( 'UPDATE ' . $table . ' SET ' . $set . ' WHERE ' . $where );
}

/**
 * Build a safe SQL WHERE conditions fragment.
 *
 * Returns a string that can be appended after 'WHERE '.
 * The returned string does not include the 'WHERE' keyword.
 *
 * @param string $table DB table name used by cerber_db_prepare() to format values.
 * @param array  $column_conditions Associative array of column conditions for the WHERE clause.
 *                                  Supported formats:
 *                                  - ['column_name' => 'value', ...] uses '=' operator
 *                                  - ['column_name' => ['operator', 'value'], ...]
 *
 * @return string SQL conditions joined with ' AND ', or an empty string if no valid conditions exist.
 *
 * @since 8.8.6.3
 *
 * @version Sequoia
 */
function cerber_db_make_where( string $table, array $column_conditions ): string {

	$where_conditions = array();
	$allowed_ops      = array( '=', '!=', '<>', '<', '<=', '>', '>=' );

	foreach ( $column_conditions as $column => $condition ) {

		$column = cerber_db_ident( (string) $column );
		if ( $column === '' ) {
			continue;
		}

		// Mode 1: implicit "=" comparison

		$operator = '=';
		$operand  = $condition;

		// Mode 2: explicit operator provided as [operator, value]

		if ( is_array( $condition ) ) {
			if ( count( $condition ) < 2 ) {
				continue;
			}

			$operator = trim( (string) $condition[0] );

			if ( ! in_array( $operator, $allowed_ops, true ) ) {
				continue;
			}

			$operand = $condition[1];
		}

		// Append a valid WHERE condition with a prepared value

		$where_conditions[] = $column . ' ' . $operator . ' ' . cerber_db_prepare( $table, $column, $operand );
	}

	return $where_conditions ? implode( ' AND ', $where_conditions ) : '';
}

/**
 * Prepare a value for safe use in SQL queries based on the column type.
 *
 * Returns a SQL literal ready for direct concatenation.
 * NULL values are not supported by contract.
 *
 * @param string $table  DB table name (validated by the caller).
 * @param string $column Column name (validated by the caller).
 * @param mixed  $value  Scalar value to prepare.
 *
 * @return string SQL literal.
 *
 * @version Sequoia
 */
function cerber_db_prepare( string $table, string $column, $value ): string {

	$type = CERBER_DB_TYPES[ $table ][ $column ] ?? '';

	switch ( $type ) {
		case 'int':
			return (string) (int) $value;
		default:
			$value = (string) $value;
			return '\'' . cerber_db_real_escape( $value ) . '\'';
	}
}

/**
 * Deletes rows from a database table using specified values of the given fields.
 *
 * @param string $table The name of the database table.
 * @param array $key_fields An associative array of conditional fields for the WHERE clause.
 *                          Format: ['column_name' => 'value', ...]
 *
 * @return int The number of affected rows
 *
 * @since 9.6.6.12
 */
function cerber_db_delete_rows( string $table, array $key_fields ): int {

	// We do not allow emptying tables.

	if ( ! $key_fields ) {
		return 0;
	}

	if ( ! $table = cerber_db_ident( $table ) ) {
		return 0;
	}

	$where = ( $key_fields ) ? ' WHERE ' . cerber_db_make_where( $table, $key_fields ) : '';

	if ( ! cerber_db_query( 'DELETE FROM ' . $table . $where ) ) {
		return 0;
	}

	return cerber_db_get_affected();
}

/**
 * Returns the active WordPress database connection.
 *
 * @return false|wpdb The wpdb instance if the connection is valid, or false on failure.
 *
 */
function cerber_get_db() {
	global $wpdb;
	static $db;

	if ( ! isset( CRB_Globals::$db_errors ) ) {
		CRB_Globals::$db_errors = array();
	}

	if ( ! $db
	     || empty( $db->dbh )
	     || ! $db->dbh instanceof MySQLi ) {

		if ( ! is_object( $wpdb )
		     || empty( $wpdb->dbh )
		     || ! $wpdb->dbh instanceof MySQLi ) {
			$db = cerber_db_connect();
		}
		else {
			$db = $wpdb;
		}
	}

	// Check if the attempt to connect has failed or the connection is lost

	if ( ! $db
	     || empty( $db->dbh )
	     || ! $db->dbh instanceof MySQLi ) {

		CRB_Globals::$db_errors[] = 'Unable to connect to the website database';

		return false;
	}

	return $db;
}

/**
 * Retrieves the active database connection.
 *
 * @return mysqli|false The MySQLi connection object.
 *
 * @since 9.6.6.12
 */
function cerber_db_get_connection() {
	if ( $db = cerber_get_db() ) {
		return $db->dbh;
	}

	return false;
}

/**
 * Returns the WordPress DB table prefix
 *
 * @return string
 */
function cerber_get_db_prefix(): string {
	global $wpdb;
	static $prefix = null;

	if ( $prefix === null ) {
		$prefix = $wpdb->base_prefix;
	}

	return (string) $prefix;
}

/**
 * Create a WP DB handler
 *
 * @return false|wpdb
 */
function cerber_db_connect() {
	if ( ! defined( 'CRB_ABSPATH' ) ) {
		define( 'CRB_ABSPATH', dirname( __FILE__, 4 ) );
	}

	$db_class = CRB_ABSPATH . '/' . WPINC . '/wp-db.php';

	$wp_config = CRB_ABSPATH . '/wp-config.php';
	if ( ! file_exists( $wp_config ) ) {
		$wp_config = dirname( CRB_ABSPATH ) . '/wp-config.php';
	}

	if ( file_exists( $db_class ) && $config = file_get_contents( $wp_config ) ) {
		$config = str_replace( '<?php', '', $config );
		$config = str_replace( '?>', '', $config );
		ob_start();
		@eval( $config ); // This eval is OK. Getting site DB connection parameters.
		ob_end_clean();
		if ( defined( 'DB_USER' ) && defined( 'DB_PASSWORD' ) && defined( 'DB_NAME' ) && defined( 'DB_HOST' ) ) {
			require_once( $db_class );

			return new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		}
	}

	return false;
}

function crb_get_mysql_var( $var ) {
	static $cache;
	if ( ! isset( $cache[ $var ] ) ) {
		if ( $v = cerber_db_get_row( 'SHOW VARIABLES LIKE "' . $var . '"' ) ) {
			$cache[ $var ] = $v['Value'];
		}
		else {
			$cache[ $var ] = false;
		}
	}

	return $cache[ $var ];
}

/**
 * Checks if a specified column in a given DB table has the expected data type and optional properties.
 *
 * This function retrieves the column definition from INFORMATION_SCHEMA.COLUMNS for the current database
 * and compares it to the expected values. The optional $expected associative array can contain one or more
 * of the following keys (in lower case), corresponding to MySQL column properties:
 *   - 'numeric_precision'
 *   - 'numeric_scale'
 *   - 'character_maximum_length'
 * and any other properties available in INFORMATION_SCHEMA.COLUMNS that can be compared.
 *
 * Example usage for a DECIMAL column:
 *   $expected = ['numeric_precision' => 14, 'numeric_scale' => 4];
 *
 * Example usage for a VARCHAR column:
 *   $expected = ['character_maximum_length' => 255];
 *
 * @param string $table_name The name of the table.
 * @param string $column_name The name of the column.
 * @param string $expected_type The expected data type (e.g., 'decimal', 'varchar', 'int').
 * @param array $expected (Optional) Associative array of expected properties.
 *
 * @return bool True if the column matches the expected definition, false otherwise.
 *
 * @since 9.6.6.3
 */
function cerber_db_check_column_type( string $table_name, string $column_name, string $expected_type, array $expected = [] ): bool {

	$table_name  = crb_sanitize_id( $table_name );
	$column_name = crb_sanitize_id( $column_name );

	$select_fields = "DATA_TYPE";

	if ( ! empty( $expected ) ) {
		$additional_fields = array_map( 'crb_sanitize_id', array_keys( $expected ) );
		$select_fields .= ", " . implode( ", ", $additional_fields );
	}

	$query = 'SELECT ' . $select_fields . '
              FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = "' . $table_name . '"
                AND COLUMN_NAME = "' . $column_name . '"
              LIMIT 1';

	$result = cerber_db_get_results( $query );

	if ( empty( $result ) ) {
		return false;
	}

	$column_def = $result[0];
	$column_def = array_change_key_case( $column_def, CASE_LOWER );

	// Check column type first

	if ( strtolower( $column_def['data_type'] ) !== strtolower( $expected_type ) ) {
		return false;
	}
	elseif ( ! $expected ) {
		return true;
	}

	// Check additional column type parameters

	foreach ( $expected as $parameter => $expected_value ) {

		if ( ! array_key_exists( $parameter, $column_def ) ) {
			return false;
		}

		if ( is_numeric( $expected_value ) ) {
			if ( intval( $column_def[ $parameter ] ) !== intval( $expected_value ) ) {
				return false;
			}
		} else {
			if ( $column_def[ $parameter ] != $expected_value ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Retrieves a value from the WP Cerber key-value storage and optionally from the persistent object cache.
 *
 * If the key exists in the storage and stored value is not expired, the stored value is returned.
 * If caching is enabled and the value present in the cache, it's returned.
 *
 * @param string     $key         Unique key (max 255 ASCII characters).
 * @param int        $id          Optional numerical key that can be used to retrieve elements stored under the same keys (default: 0).
 * @param bool       $unserialize Whether to unserialize returning element (default: true).
 * @param bool|null  $use_cache   If true, retrieves element from cache; if null, auto-detects (default: null).
 *
 * @return false|array|string Returns false if element does not exist or is expired; otherwise, returns the stored value.
 *
 * @see cerber_update_set()
 * @see cerber_delete_set()
 */
function cerber_get_set( $key, $id = 0, $unserialize = true, ?bool $use_cache = null ) {

	if ( ! $key = preg_replace( CRB_SANITIZE_KEY, '', $key ) ) {
		return false;
	}

	$ret = false;

	$id = crb_absint( $id );
	$cache_key = 'crb#' . $key . '#' . $id;
	$use_cache = $use_cache ?? cerber_cache_is_enabled();

	if ( $use_cache ) {
		$cache_value = cerber_cache_get( $cache_key, null );
		if ( $cache_value !== null ) {
			return $cache_value;
		}
	}

	if ( $row = cerber_db_get_row( 'SELECT * FROM ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' WHERE the_key = "' . $key . '" AND the_id = ' . $id ) ) {

		if ( $row['expires'] > 0 && $row['expires'] < time() ) {
			cerber_delete_set( $key, $id );

			if ( $use_cache ) {
				cerber_cache_delete( $cache_key );
			}

			return false;
		}

		if ( $unserialize ) {
			$ret = ! empty( $row['the_value'] ) ? crb_unserialize( $row['the_value'] ) : array();
		}
		else {
			$ret = $row['the_value'];
		}
	}

	if ( $use_cache ) {
		cerber_cache_set( $cache_key, $ret );
	}

	return $ret;
}

/**
 * Inserts or updates a value in the WP Cerber key-value storage.
 *
 * If the key already exists, the stored value and expiration time are updated.
 * If caching is enabled, the value is also stored in the cache.
 *
 * @param string     $key       Unique key (max 255 characters).
 * @param mixed      $value     Data to store.
 * @param int        $id        Optional numerical key that can be used to store data under the same keys (default: 0).
 * @param bool       $serialize Whether to serialize non-string values (default: true).
 * @param int        $expires   Expiration timestamp (UTC), 0 = never expires (default: 0).
 * @param bool|null  $use_cache If true, stores the value in the persistent object cache; if null, auto-detects (default: null).
 *
 * @return bool True on success, false on failure.
 *
 * @see cerber_get_set()
 * @see cerber_delete_set()
 */
function cerber_update_set( $key, $value, $id = 0, $serialize = true, $expires = 0, ?bool $use_cache = null ) {

	if ( ! $key = preg_replace( CRB_SANITIZE_KEY, '', $key ) ) {
		return false;
	}

	$expires = crb_absint( $expires );
	$id = crb_absint( $id );
	$cache_key = 'crb#' . $key . '#' . $id;

	$use_cache = $use_cache ?? cerber_cache_is_enabled();

	if ( $use_cache ) {
		$cache_ttl = ( $expires > 0 ) ? max( 0, $expires - time() ) : 0;
		cerber_cache_set( $cache_key, $value, $cache_ttl );
	}

	if ( $serialize && ! is_string( $value ) ) {
		$value = serialize( $value );
	}

	$value = cerber_db_real_escape( $value );

	$sql = 'INSERT INTO ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' (the_key, the_id, the_value, expires)
        VALUES ("' . $key . '",' . $id . ',"' . $value . '",' . $expires . ')
        ON DUPLICATE KEY UPDATE the_value = VALUES(the_value), expires = VALUES(expires)';

	unset( $value );

	return (bool) cerber_db_query( $sql );
}

/**
 * Delete value from the storage
 *
 * @param string $key
 * @param integer $id
 *
 * @return bool
 *
 * @see cerber_get_set()
 * @see cerber_update_set()
 */
function cerber_delete_set( $key, $id = null ) {

	if ( ! $key = preg_replace( CRB_SANITIZE_KEY, '', $key ) ) {
		return false;
	}

	$cache_key = 'crb#' . $key . '#';

	$id = ( $id !== null ) ? absint( $id ) : 0;
	$cache_key .= $id;

	cerber_cache_delete( $cache_key );

	if ( cerber_db_query( 'DELETE FROM ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' WHERE the_key = "' . $key . '" AND the_id = ' . $id ) ) {
		return true;
	}

	return false;
}

/**
 * Clean up all expired sets. Usually by cron.
 *
 * @param bool $all if true, deletes all sets that has expiration
 *
 * @return bool
 */
function cerber_delete_expired_set( $all = false ) {
	if ( ! $all ) {
		$where = 'expires > 0 AND expires < ' . time();
	}
	else {
		$where = 'expires > 0';
	}
	if ( cerber_db_query( 'DELETE FROM ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' WHERE ' . $where ) ) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Remove PHP comments from a given text string.
 *
 * This function performs a rough comment removal and may damage valid PHP code.
 *
 * @param string $string Source text.
 *
 * @return string
 */
function cerber_remove_comments( string $string = '' ): string {
	if ( $string === '' ) {
		return '';
	}

	// Fast pre-check: if no comment markers, skip regex
	if (
		strpos( $string, '/' ) === false &&
		strpos( $string, '#' ) === false
	) {
		return $string;
	}

	$result = preg_replace(
		[
			'~/\*.*?\*/~s', // Block comments
			'~//.*$~m',     // Single-line C-style comments
			'~#.*$~m',      // Single-line shell-style comments
		],
		'',
		$string
	);

	return is_string( $result ) ? $result : '';
}

/**
 * Set Cerber Groove to logged in user browser
 *
 * @param $expire
 */
function cerber_set_groove( $expire ) {
	if ( ! headers_sent() ) {
		cerber_set_cookie( CRB_GROOVE, md5( cerber_get_groove() ), $expire + 1 );

		$groove_x = cerber_get_groove_x();
		cerber_set_cookie( CRB_GROOVE . '_x_' . $groove_x[0], $groove_x[1], $expire + 1 );
	}
}

function cerber_is_auth_cookie( $text ) {
	return ( 0 === strpos( $text, cerber_get_cookie_prefix() . CRB_GROOVE ) );
}

/*
	Get the special Cerber Sign for using with cookies
*/
function cerber_get_groove() {
	$groove = cerber_get_site_option( 'cerber-groove', false );

	if ( empty( $groove ) ) {
		$groove = crb_random_string( 16 );
		update_site_option( 'cerber-groove', $groove );
	}

	return $groove;
}

/*
	Check if the special Cerber Sign valid
*/
function cerber_check_groove( $hash = '' ) {
	if ( ! $hash ) {
		if ( ! $hash = cerber_get_cookie( CRB_GROOVE ) ) {
			return false;
		}
	}
	$groove = cerber_get_groove();
	if ( $hash == md5( $groove ) ) {
		return true;
	}

	return false;
}

/**
 * @since 7.0
 */
function cerber_check_groove_x() {
	$groove_x = cerber_get_groove_x();
	if ( cerber_get_cookie( CRB_GROOVE . '_x_' . $groove_x[0] ) == $groove_x[1] ) {
		return true;
	}

	return false;
}

/**
 * Returns the special public Cerber Sign for using with cookies
 *
 * @param $regenerate bool
 *
 * @return array
 */
function cerber_get_groove_x( $regenerate = false ) {
	$groove_x = array();

	if ( ! $regenerate ) {
		$groove_x = cerber_get_site_option( 'cerber-groove-x' );
	}

	if ( $regenerate || empty( $groove_x ) || ! is_array( $groove_x ) ) {
		$groove_0 = crb_random_string( 24, 32 );
		$groove_1 = crb_random_string( 24, 32 );
		$groove_x = array( $groove_0, $groove_1 );
		update_site_option( 'cerber-groove-x', $groove_x );
		crb_update_cookie_dependent();
	}

	return $groove_x;
}

function cerber_get_cookie_path() {
	if ( defined( 'COOKIEPATH' ) ) {
		return COOKIEPATH;
	}

	return '/';
}

/**
 * Sets WP Cerber's cookies
 *
 * @param string $name
 * @param string $value
 * @param int $expire
 * @param string $path
 * @param string $domain
 * @param bool $http
 *
 * @return bool
 */
function cerber_set_cookie( $name, $value, $expire = 0, $path = "", $domain = "", $http = false ) {
	global $wp_cerber_cookies;

	if ( cerber_is_wp_cron() ) {
		return false;
	}

	if ( ! $path ) {
		$path = cerber_get_cookie_path();
	}

	$expire = absint( $expire );
	$expire = ( $expire > 43009401600 ) ? 43009401600 : $expire;

	$ret = setcookie( cerber_get_cookie_prefix() . $name, $value, array(
		'expires'  => $expire,
		'path'     => $path,
		'domain'   => $domain,
		'secure'   => is_ssl(),
		'httponly' => false,
		'samesite' => 'Strict',
	) );

	if ( $ret ) {
		$wp_cerber_cookies[ cerber_get_cookie_prefix() . $name ] = array( $expire, $value );
	}

	return $ret;
}

/**
 * Retrieves WP Cerber's cookies
 *
 * @param string $name
 * @param bool $default
 *
 * @return string|boolean value of the cookie, false if the cookie is not set
 */
function cerber_get_cookie( $name, $default = false ) {
	return crb_array_get( $_COOKIE, cerber_get_cookie_prefix() . $name, $default );
}

function cerber_get_cookie_prefix() {

	if ( $p = (string) crb_get_settings( 'cookiepref' ) ) {
		return $p;
	}

	return '';
}

function crb_update_cookie_dependent() {
	static $done = false;

	if ( $done ) {
		return;
	}

	register_shutdown_function( function () {
		cerber_htaccess_sync( 'main' ); // keep the .htaccess rules up to date
	} );

	$done = true;
}

/**
 * Synchronize plugin settings with rules in the .htaccess file
 *
 * @param $file string
 * @param $settings array
 *
 * @return bool|string|WP_Error
 */
function cerber_htaccess_sync( $file, $settings = array() ) {

	if ( ! $settings ) {
		$settings = crb_get_settings();
	}

	if ( 'main' == $file ) {
		$rules = array();

		if ( ! empty( $settings['adminphp'] )
		     && ( ! defined( 'CONCATENATE_SCRIPTS' ) || ! CONCATENATE_SCRIPTS ) ) {
			// https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2018-6389
			if ( ! crb_is_apache_mod_loaded( 'mod_rewrite' ) ) {

				return new WP_Error( 'no_mod', 'The Apache mod_rewrite module is not enabled on your web server. Ask your server administrator for assistance.' );
			}
			$groove_x = cerber_get_groove_x();
			$cookie = cerber_get_cookie_prefix() . CRB_GROOVE . '_x_' . $groove_x[0];
			$rules [] = '# Prevents exploitation of CVE-2018-6389 in WordPress admin scripts.';
			$rules [] = '# Note: if WP Cerber is active, do not remove this section, disable protection in the plugin settings instead.';
			$rules [] = '# Note: remove this section if WP Cerber is not active or has been deleted.';
			$rules [] = '<IfModule mod_rewrite.c>';
			$rules [] = 'RewriteEngine On';
			$rules [] = 'RewriteCond %{REQUEST_URI} ^.*/+wp-admin/+load-(scripts|styles)\.php$ [NC]';
			$rules [] = 'RewriteCond %{HTTP_COOKIE} !' . $cookie . '=' . $groove_x[1];
			$rules [] = 'RewriteRule ^ - [R=403,L]';
			$rules [] = '</IfModule>';
		}

		return cerber_update_htaccess( $file, $rules );
	}

	if ( 'media' == $file ) {
		/*if ( ! crb_is_php_mod() ) {
			return 'ERROR: The Apache PHP module mod_php is not active.';
		}*/
		$rules = array();

		if ( ! empty( $settings['phpnoupl'] ) ) {

			$rules = [
				'<IfModule mod_authz_core.c>',
				'<FilesMatch "\\.(php|phtml|php[3-8]?|phar|cgi|pl|py|pyc|pyo|sh)$">',
				'    SetHandler none',
				'    Require all denied',
				'</FilesMatch>',
				'</IfModule>',
				'',
				'<IfModule !mod_authz_core.c>',
				'<FilesMatch "\\.(php|phtml|php[3-8]?|phar|cgi|pl|py|pyc|pyo|sh)$">',
				'    SetHandler none',
				'    Order allow,deny',
				'    Deny from all',
				'</FilesMatch>',
				'</IfModule>',
				'',
				'<FilesMatch "\\.(php|phtml|php[3-8]?|phar|cgi|pl|py|pyc|pyo|sh)$">',
				'    <IfModule mod_php.c>',
				'        php_flag engine off',
				'    </IfModule>',
				'    <IfModule mod_php5.c>',
				'        php_flag engine off',
				'    </IfModule>',
				'    <IfModule mod_php7.c>',
				'        php_flag engine off',
				'    </IfModule>',
				'    <IfModule mod_php8.c>',
				'        php_flag engine off',
				'    </IfModule>',
				'</FilesMatch>',
				'',
				'<IfModule mod_mime.c>',
				'    RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .cgi .pl .py .pyc .pyo .sh',
				'</IfModule>',
				'',
				'<IfModule mod_cgi.c>',
				'    Options -ExecCGI',
				'</IfModule>',
			];

		}

		return cerber_update_htaccess( $file, $rules );
	}

	return false;
}

/**
 * Remove Cerber rules from the .htaccess file
 *
 */
function cerber_htaccess_clean_up() {
	cerber_update_htaccess( 'main', array() );
	cerber_update_htaccess( 'media', array() );
}

/**
 * Update the .htaccess file
 *
 * @param $file
 * @param array $rules A set of rules (array of strings) for the section. If empty, the section will be cleaned.
 *
 * @return string|WP_Error  String on success, WP_Error on a failure
 */
function cerber_update_htaccess( $file, $rules = array() ) {
	if ( $file == 'main' ) {
		$htaccess = cerber_get_htaccess_file();
		$marker = CERBER_MARKER1;
	}
	elseif ( $file == 'media' ) {
		$htaccess = cerber_get_upload_dir() . '/.htaccess';
		$marker = CERBER_MARKER2;
	}
	else {
		return new WP_Error( 'htaccess-io', 'Unknown .htaccess file specified' );
	}

	if ( ! is_file( $htaccess ) ) {
		if ( ! touch( $htaccess ) ) {
			return new WP_Error( 'htaccess-io', 'ERROR: Unable to create the file ' . $htaccess );
		}
	}
	elseif ( ! is_writable( $htaccess ) ) {
		return new WP_Error( 'htaccess-io', 'ERROR: Unable to update the file ' . $htaccess );
	}

	$result = crb_insert_with_markers( $htaccess, $marker, $rules );

	if ( $result || $result === 0 ) {
		$result = 'The file ' . $htaccess . ' has been updated';
	}
	else {
		$result = new WP_Error( 'htaccess-io', 'ERROR: Unable to update the file ' . $htaccess );
	}

	return $result;
}

/**
 * Return .htaccess filename with full path
 *
 * @return bool|string full filename if the file can be written, false otherwise
 */
function cerber_get_htaccess_file() {
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	$home_path = get_home_path();

	return $home_path . '.htaccess';
}

/**
 * Check if the remote domain match mask
 *
 * @param $domain_mask array|string Mask(s) to check remote domain against
 *
 * @return bool True if hostname match at least one domain from the list
 */
function cerber_check_remote_domain( $domain_mask ) {

	$hostname = @gethostbyaddr( cerber_get_remote_ip() );

	if ( ! $hostname || filter_var( $hostname, FILTER_VALIDATE_IP ) ) {
		return false;
	}

	if ( ! is_array( $domain_mask ) ) {
		$domain_mask = array( $domain_mask );
	}

	foreach ( $domain_mask as $mask ) {

		if ( substr_count( $mask, '.' ) != substr_count( $hostname, '.' ) ) {
			continue;
		}

		$parts = array_reverse( explode( '.', $hostname ) );

		$ok = true;

		foreach ( array_reverse( explode( '.', $mask ) ) as $i => $item ) {
			if ( $item != '*' && $item != $parts[ $i ] ) {
				$ok = false;
				break;
			}
		}

		if ( $ok == true ) {
			return true;
		}

	}

	return false;
}

/**
 * Install and de-install WP Cerber files for different boot modes
 *
 * @param $mode int A plugin boot mode
 *
 * @return int|WP_Error
 *
 * @since 6.3
 */
function cerber_set_boot_mode( $mode = null ) {
	if ( $mode === null ) {
		$mode = crb_get_settings( 'boot-mode' );
	}

	$source = dirname( cerber_plugin_file() ) . '/modules/aaa-wp-cerber.php';
	$target = WPMU_PLUGIN_DIR . '/aaa-wp-cerber.php';

	if ( $mode == 1 ) {

		if ( file_exists( $target ) ) {
			if ( sha1_file( $source, true ) == sha1_file( $target, true ) ) {
				return 1;
			}

			if ( ! wp_is_writable( $target ) ) {
				/* translators: %s is the file path. */
				return new WP_Error( 'cerber-boot', sprintf( __( 'Destination file is not writable: %s', 'wp-cerber' ), $target ) );
			}
		}

		// Copying the file

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			if ( ! mkdir( WPMU_PLUGIN_DIR, 0755, true ) ) {
				/* translators: %s is the directory path. */
				return new WP_Error( 'cerber-boot', sprintf( __( 'Unable to create required directory: %s', 'wp-cerber' ), WPMU_PLUGIN_DIR ) );
			}
		}

		if ( ! wp_is_writable( WPMU_PLUGIN_DIR ) ) {
			/* translators: %s is the directory path. */
			return new WP_Error( 'cerber-boot', sprintf( __( 'Destination directory is not writable: %s', 'wp-cerber' ), WPMU_PLUGIN_DIR ) );
		}

		if ( ! file_exists( $source ) ) {
			/* translators: %s is the file path. */
			return new WP_Error( 'cerber-boot', sprintf( __( 'Source file not found: %s', 'wp-cerber' ), $source ) );
		}

		if ( ! copy( $source, $target ) ) {
			/* translators: %s is the file path. */
			return new WP_Error( 'cerber-boot', sprintf( __( 'Unable to copy file: %s', 'wp-cerber' ), $target ) );
		}
		else {
			return 2;
		}
	}

	if ( file_exists( $target ) ) {
		if ( ! unlink( $target ) ) {
			/* translators: %s is the file path. */
			return new WP_Error( 'cerber-boot', sprintf( __( 'Unable to delete the file: %s', 'wp-cerber' ), $target ) );
		}
		else {
			cerber_diag_log( 'MU module deleted. IP:' . cerber_get_remote_ip() . ' METHOD: ' . $_SERVER['REQUEST_METHOD'] . ' ' . print_r( crb_get_post_fields(), 1 ) . ' UA: ' . $_SERVER['HTTP_USER_AGENT'] . ' REQ: ' . $_SERVER['REQUEST_URI'] );
			return 3;
		}
	}

	return 4;
}

/**
 * How WP Cerber was loaded (initialized)
 *
 * @return int
 *
 * @since 6.3
 */
function cerber_get_mode() {
	//if ( function_exists( 'cerber_mode' ) && defined( 'CERBER_MODE' ) ) {  // CERBER_MODE is not yet defined when Cerber.Hub renders admin pages on a managed website
	if ( function_exists( 'cerber_mode' ) ) {
		return cerber_mode();
	}

	return 0;
}

function cerber_is_permalink_enabled() {
	static $ret;

	if ( ! isset( $ret ) ) {
		$ret = (bool) get_option( 'permalink_structure' );
	}

	return $ret;
}

/**
 * @param string $dir Path to check
 * @param string $msg An error message
 * @param string $none An optional error message if the directory does not exist or is not within the allowed paths
 *
 * @return int 0 if the folder exists and writable
 *
 * @since 9.5.1
 */
function crb_check_dir( $dir, &$msg = '', $none = '' ) {
	$ret = 0;

	if ( ! is_dir( $dir ) ) {
		$ret = 1;
		$msg = $none ?: 'Required directory does not exist: ' . crb_escape_html( $dir );
	}
	elseif ( ! wp_is_writable( $dir ) ) {
		if ( ! chmod( $dir, 0755 ) ) {
			$ret = 2;
			$msg = 'Required directory is not writable. Please check write permissions and ownership of this directory: ' . crb_escape_html( $dir );
		}
	}

	return $ret;
}

/**
 * Implement basename() with multibyte support
 *
 * @param $file_name
 *
 * @return string
 */
function cerber_mb_basename( $file_name ) {
	$pos = mb_strrpos( $file_name, DIRECTORY_SEPARATOR );
	if ( $pos !== false ) {
		return mb_substr( $file_name, $pos + 1 );
	}

	return $file_name;
}

function cerber_get_extension( $file_name ) {
	$file_name = cerber_mb_basename( $file_name );
	$pos = mb_strpos( $file_name, '.' );
	if ( $pos !== false ) {
		if ( $ext = mb_substr( $file_name, $pos + 1 ) ) {
			return mb_strtolower( $ext );
		}
	}

	return  '';
}

/**
 * True if version of WP is equal or greater than specified one
 *
 * @param string $ver
 *
 * @return bool|int
 */
function crb_wp_version_compare( $ver, $comp = '>=' ) {
	return version_compare( cerber_get_wp_version(), (string) $ver, $comp );
}

/**
 * Returns an unaltered $wp_version variable
 *
 * @return string WordPress version
 */
function cerber_get_wp_version() {
	static $ver;

	if ( ! $ver ) {
		include( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'version.php' );
		$ver = (string) $wp_version;
	}

	return $ver;
}

/**
 * Returns an unaltered $wp_local_package variable
 *
 * @return string WordPress locale
 * @since 8.8.7.2
 */
function cerber_get_wp_locale() {
	static $lc;

	if ( ! $lc ) {
		//global $wp_local_package;
		include( ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'version.php' );
		$lc = $wp_local_package ?? 'en_US';
	}

	return $lc;
}

function crb_get_themes() {

	static $theme_headers = array(
		'Name'        => 'Theme Name',
		'ThemeURI'    => 'Theme URI',
		'Description' => 'Description',
		'Author'      => 'Author',
		'AuthorURI'   => 'Author URI',
		'Version'     => 'Version',
		'Template'    => 'Template',
		'Status'      => 'Status',
		'Tags'        => 'Tags',
		'TextDomain'  => 'Text Domain',
		'DomainPath'  => 'Domain Path',
	);

	$themes = array();

	if ( $list = search_theme_directories() ) {
		foreach ( $list as $key => $info ) {
			$css = $info['theme_root'] . '/' . $info['theme_file'];
			if ( is_readable( $css ) ) {
				$themes[ $key ] = get_file_data( $info['theme_root'] . '/' . $info['theme_file'], $theme_headers, 'theme' );
				$themes[ $key ]['theme_root'] = $info['theme_root'];
				$themes[ $key ]['theme_file'] = $info['theme_file'];
			}
		}
	}

	return $themes;
}

function cerber_is_base64_encoded( $val ) {
	$val = trim( $val );
	if ( empty( $val ) || is_numeric( $val ) || strlen( $val ) < 8 || preg_match( '/[^A-Z0-9\+\/=]/i', $val ) ) {
		return false;
	}
	if ( $val = @base64_decode( $val ) ) {
		if ( ! preg_match( '/[\x00-\x08\x0B-\x0C\x0E-\x1F]/', $val ) ) { // ASCII control characters must not be
			if ( preg_match( '/[A-Z]/i', $val ) ) { // Latin chars must be
				return $val;
			}
		}
	}


	return false;
}

function crb_is_alphanumeric( $str ) {
	return ! preg_match( '/[^\w\-]/', $str );
}

/**
 * Creates HTML code for labels formated for email messages using in-line CSS styles and the given issue ID
 *
 * @param int $iid The ID of the scan issue
 *
 * @return string The HTML code of the label
 */
function cerber_get_html_label( int $iid ) {

	$c = ( $iid == CERBER_FOK ) ? '#33be84' : '#e94045';

	return '<span style="background-color: ' . $c . '; color: #fff; margin-left: 6px; display: inline-block; line-height: 1em; padding: 3px 5px; font-size: 92%;">' . cerber_get_issue_labels( $iid ) . '</span>';
}

/**
 * Retrieve all HTTP request headers sent by the client.
 *
 * Only HTTP request headers are returned.
 *
 * @return array<string, string> Associative array of HTTP headers
 *
 */
function crb_getallheaders() {
	static $headers = null;

	if ( function_exists( 'getallheaders' ) ) {
		return getallheaders();
	}

	// PHP-FPM

	if ( $headers === null ) {

		$headers = array();

		foreach ( $_SERVER as $name => $value ) {
			if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
				$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
			}
		}

	}

	return $headers;
}

/**
 * Check if the client headers contain a given HTTP header and value.
 * Case-insensitive match for header name and value.
 *
 * Examples:
 * - 'X-Foo: bar'
 * - 'X-Foo:' (empty value is allowed)
 *
 * @param string $search_header A single HTTP header in colon-separated form
 *
 * @return bool True if the given header name exists and its value matches
 *
 * @since 9.6.1.11
 */
function crb_request_header_matches( $search_header ) {
	static $headers = null;

	if ( ! is_string( $search_header ) ) {
		return false;
	}

	$pos = strpos( $search_header, ':' );
	if ( $pos === false ) {
		return false;
	}

	$name = strtolower( trim( substr( $search_header, 0, $pos ) ) );
	$value = strtolower( trim( substr( $search_header, $pos + 1 ) ) );

	if ( $name === '' ) {
		return false;
	}

	if ( $headers === null ) {
		$headers = array_change_key_case( crb_getallheaders(), CASE_LOWER );
	}

	if ( ! isset( $headers[ $name ] ) ) {
		return false;
	}

	return strtolower( trim( $headers[ $name ] ) ) === $value;
}


/**
 * Write error message(s) to the WP Cerber's diagnostic log
 *
 * @param string|array $msg Diagnostic message(s) to save to the WP Cerber's diagnostic log
 * @param string $source Name of the subsystem that sends this message(s). Optional.
 *
 * @return bool
 */
function cerber_error_log( $msg, $source = '' ) {
	return cerber_diag_log( $msg, $source, true );
}

/**
 * Write message(s) to the WP Cerber's diagnostic log
 *
 * @param string|array $msg Diagnostic message(s) to save to the WP Cerber's diagnostic log
 * @param string $source Name of the subsystem that sends this message(s). Optional.
 * @param bool $error If true, handle message(s) as an error message. Optional.
 *
 * @return bool
 */
function cerber_diag_log( $msg, string $source = '', $error = false ) {

	if ( $source == 'CLOUD'
	     && ( ! defined( 'CERBER_CLOUD_DEBUG' )
	          || ! CERBER_CLOUD_DEBUG
	          || ( ! defined( 'WP_ADMIN' ) && ! defined( 'WP_NETWORK_ADMIN' ) ) ) ) {
		return false;
	}

	if ( ! $msg
	     || ( ! $log_file = cerber_get_diag_log() )
	     || ( ! $log = @fopen( $log_file, 'a' ) ) ) {
		return false;
	}

	if ( $source ) {
		$source = '[' . $source . ']';
	}
	if ( $error ) {
		$source .= ' ERROR: ';
	}
	if ( ! is_array( $msg ) ) {
		$msg = array( $msg );
	}

	$ret = true;

	foreach ( $msg as $line ) {
		if ( is_array( $line ) ) {
			$line = print_r( $line, 1 ); // workaround for CRB_Globals::$db_errors
		}

		if ( ! @fwrite( $log, '[' . cerber_date( time(), false ) . ']' . $source . ' ' . $line . PHP_EOL ) ) {
			$ret = false;
		}
	}

	@fclose( $log );

	return $ret;
}

/**
 * Returns the full file name of WP Cerber's diagnostic log or empty string on failure.
 *
 * @return string
 */
function cerber_get_diag_log(): string {

	if ( ! $dir = crb_get_diag_dir() ) {
		return '';
	}

	return $dir . 'cerber-debug.log';
}

/**
 * Returns a directory for storing optional WP Cerber diagnostic files.
 * The directory must be protected from public access over the Internet.
 *
 * If a constant CERBER_DIAG_DIR is defined and points to an existing directory,
 * it will be returned. Otherwise, a directory generated by cerber_get_the_folder()
 * will be used. If no valid directory can be determined, an empty string is returned.
 *
 * @return string Absolute path to the diagnostic directory with trailing slash or an empty string.
 *
 * @since 9.6.5.10
 */
function crb_get_diag_dir(): string {
	if ( defined( 'CERBER_DIAG_DIR' )
	     && is_dir( CERBER_DIAG_DIR ) ) {

		return rtrim( CERBER_DIAG_DIR, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	}
	elseif ( $dir = cerber_get_the_folder() ) {
		return $dir;
	}

	return '';
}

function crb_truncate_diagnostic_log( $bytes = 10000000 ) {

	update_user_meta( get_current_user_id(), 'clast_log_view', array() );

	$file = cerber_get_diag_log();

	if ( ! is_file( $file ) || filesize( $file ) <= $bytes ) {
		return;
	}
	if ( $bytes == 0 ) {
		$log = @fopen( $file, 'w' );
		@fclose( $log );

		return;
	}
	if ( $text = file_get_contents( $file ) ) {
		$text = substr( $text, 0 - $bytes );
		if ( ! $log = @fopen( $file, 'w' ) ) {
			return;
		}
		@fwrite( $log, $text );
		@fclose( $log );
	}
}

function crb_get_bloginfo( $what ) {
	static $info = array();

	if ( ! isset( $info[ $what ] ) ) {
		$info[ $what ] = get_bloginfo( $what );
	}

	return $info[ $what ];
}

function crb_is_php_mod() {
	require_once( ABSPATH . 'wp-admin/includes/misc.php' );
	if ( apache_mod_loaded( 'mod_php7' ) ) {
		return true;
	}
	if ( apache_mod_loaded( 'mod_php5' ) ) {
		return true;
	}

	return false;
}

/**
 * @param $dir string Directory to empty with a trailing directory separator
 *
 * @return int|WP_Error
 */
function cerber_empty_dir( $dir ) {
	//$trd = rtrim( $dir, '/\\' );
	if ( ! @is_dir( $dir )
	     || 0 === strpos( $dir, ABSPATH ) ) { // Workaround for a non-legitimate use of this function
		return new WP_Error( 'no-dir', 'This directory cannot be emptied' );
	}

	$files = @scandir( $dir );
	if ( ! is_array( $files ) || empty( $files ) ) {
		return true;
	}

	$fs = cerber_init_wp_filesystem();
	if ( crb_is_wp_error( $fs ) ) {
		return $fs;
	}

	$ret = true;

	foreach ( $files as $file ) {
		$full = $dir . $file;
		if ( @is_file( $full ) ) {
			if ( ! @unlink( $full ) ) {
				$ret = false;
			}
		}
		elseif ( ! in_array( $file, array( '..', '.' ) ) && is_dir( $full ) ) {
			if ( ! $fs->rmdir( $full, true ) ) {
				$ret = false;
			}
		}
	}

	if ( ! $ret ) {
		return new WP_Error( 'file-deletion-error', 'Some files or subdirectories in this directory cannot be deleted: ' . $dir );
	}

	return $ret;
}

/**
 * Creates folder(s) if it (or the whole path) doesn't exist.
 * Set permissions.
 *
 * @param string $target_dir
 * @param int $permissions
 *
 * @return bool|WP_Error
 * @since 9.5.4.2
 */
function crb_create_folder( $target_dir, $permissions = 0755 ) {
	if ( file_exists( $target_dir ) ) {
		return true;
	}

	$err = '';

	if ( ! mkdir( $target_dir, $permissions, true ) ) {
		$err = 'Unable to create directory: <b>' . $target_dir . '</b>. Check permissions of parent directory.';
	}
	elseif ( ! chmod( $target_dir, $permissions ) ) {
		$err = 'Unable to set directory permissions for ' . $target_dir;
	}

	if ( $err ) {
		return new WP_Error( 'cerber-dir', $err );
	}

	return true;
}

/**
 * Tries to raise PHP runtime limits for long-running operations.
 *
 * This is a best-effort helper. Some hosts, PHP-FPM pools, or server policies may reject these changes silently.
 *
 * The memory limit accepts PHP shorthand notation such as '256M', '1G', or '-1'.
 * Plain numeric values are interpreted by PHP as bytes.
 *
 * @param string $memory_limit Optional PHP memory_limit value, e.g. '256M'.
 *
 * @return void
 */
function crb_try_raise_php_limits( $memory_limit = '' ) {

	@ini_set( 'max_execution_time', 180 );

	if ( function_exists( 'set_time_limit' )
	     && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
		@set_time_limit( 0 );
	}

	if ( $memory_limit ) {
		@ini_set( 'memory_limit', $memory_limit );
	}
}

/**
 * Masks email address
 *
 * @param string $email
 *
 * @return string
 */
function cerber_mask_email( $email ) {
	list( $box, $host ) = explode( '@', $email );
	$box = str_pad( $box[0], strlen( $box ), '*' );
	$host = str_pad( substr( $host, strrpos( $host, '.' ) ), strlen( $host ), '*', STR_PAD_LEFT );

	return str_replace( '*', '&#8727;', $box . '@' . $host );
}

/**
 * Masks username (login)
 *
 * @param string $login
 *
 * @return string
 *
 * @since 8.9.6.4
 */
function crb_mask_login( $login ) {
	if ( is_email( $login ) ) {
		return cerber_mask_email( $login );
	}

	$strlen = mb_strlen( $login );

	return str_pad( mb_substr( $login, 0, intdiv( $strlen, 2 ) ), $strlen, '*' );
}

/**
 * Masks IP address
 *
 * @param string $ip
 *
 * @return string
 *
 * @since 8.9.6.4
 */
function crb_mask_ip( $ip = '' ) {
	if ( cerber_is_ipv6( $ip ) ) {
		// Look for the third colon
		$pos = strpos( $ip, ':', strpos( $ip, ':', strpos( $ip, ':' ) + 1 ) + 1 );
		$delimiter = ':';
	}
	else {
		// Look for the second dot
		$pos = strpos( $ip, '.', strpos( $ip, '.' ) + 1 );
		$delimiter = '.';
	}

	if ( ! $pos ) {
		return $ip;
	}

	$net = substr( $ip, 0, $pos );
	$sub = substr( $ip, $pos );

	return $net . preg_replace( '/[^' . $delimiter . ']/', '*', $sub );
}


/**
 * A modified clone of insert_with_markers() from wp-admin/includes/misc.php
 * Removed switch_to_locale() and related stuff that were introduced in WP 5.3. and cause problem if calling ite before 'init' hook.
 *
 * Inserts an array of strings into a file (.htaccess ), placing it between
 * BEGIN and END markers.
 *
 * Replaces existing marked info. Retains surrounding
 * data. Creates file if none exists.
 *
 * @param string $filename Filename to alter.
 * @param string $marker The marker to alter.
 * @param array|string $insertion The new content to insert.
 *
 * @return bool True on write success, false on failure.
 * @since 8.5.1
 *
 */
function crb_insert_with_markers( $filename, $marker, $insertion ) {
	if ( ! file_exists( $filename ) ) {
		if ( ! is_writable( dirname( $filename ) ) ) {
			return false;
		}
		if ( ! touch( $filename ) ) {
			return false;
		}
	}
	elseif ( ! is_writeable( $filename ) ) {
		return false;
	}

	if ( ! is_array( $insertion ) ) {
		$insertion = explode( "\n", $insertion );
	}

	$start_marker = "# BEGIN {$marker}";
	$end_marker = "# END {$marker}";

	$fp = fopen( $filename, 'r+' );
	if ( ! $fp ) {
		return false;
	}

	// Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
	flock( $fp, LOCK_EX );

	$lines = array();
	while ( ! feof( $fp ) ) {
		$lines[] = rtrim( fgets( $fp ), "\r\n" );
	}

	// Split out the existing file into the preceding lines, and those that appear after the marker
	$pre_lines = array();
	$post_lines = array();
	$existing_lines = array();
	$found_marker = false;
	$found_end_marker = false;
	foreach ( $lines as $line ) {
		if ( ! $found_marker && false !== strpos( $line, $start_marker ) ) {
			$found_marker = true;
			continue;
		}
		elseif ( ! $found_end_marker && false !== strpos( $line, $end_marker ) ) {
			$found_end_marker = true;
			continue;
		}
		if ( ! $found_marker ) {
			$pre_lines[] = $line;
		}
		elseif ( $found_marker && $found_end_marker ) {
			$post_lines[] = $line;
		}
		else {
			$existing_lines[] = $line;
		}
	}

	// Check to see if there was a change
	if ( $existing_lines === $insertion ) {
		flock( $fp, LOCK_UN );
		fclose( $fp );

		return true;
	}

	// Generate the new file data
	$new_file_data = implode(
		"\n",
		array_merge(
			$pre_lines,
			array( $start_marker ),
			$insertion,
			array( $end_marker ),
			$post_lines
		)
	);

	// Write to the start of the file, and truncate it to that length
	fseek( $fp, 0 );
	$bytes = fwrite( $fp, $new_file_data );
	if ( $bytes ) {
		ftruncate( $fp, ftell( $fp ) );
	}
	fflush( $fp );
	flock( $fp, LOCK_UN );
	fclose( $fp );

	return (bool) $bytes;
}

/**
 * @return WP_Error|WP_Filesystem_Direct
 */
function cerber_init_wp_filesystem() {
	global $wp_filesystem;

	if ( $wp_filesystem instanceof WP_Filesystem_Direct ) { // @since 8.1.5
		return $wp_filesystem;
	}

	require_once( ABSPATH . 'wp-admin/includes/file.php' );

	add_filter( 'filesystem_method', '__ret_direct' );

	if ( ! WP_Filesystem() ) {
		return new WP_Error( 'cerber-file', 'Unable to init WP_Filesystem' );
	}

	remove_filter( 'filesystem_method', '__ret_direct' );

	return $wp_filesystem;
}

function __ret_direct() {
	return 'direct';
}

/**
 * Performs a redirect to the specified URL.
 * It is not recommended for use for admin locations.
 * This function calls wp_redirect() to execute the redirection.
 *
 * Note: This function should almost always be followed by `exit;` to ensure proper redirection.
 *
 * @param string $url The absolute URL or relative path to redirect to.
 *
 * @return bool True if the redirection was initiated successfully, false if it was canceled.
 *
 * @since 9.6.6.4
 */
function crb_redirect( string $url ) {
	CRB_Globals::$redirect_url = $url;

	return wp_redirect( $url );
}

/**
 * Performs a safe (local) redirect to the specified URL.
 * This function calls wp_safe_redirect() to execute the redirection.
 *
 * Note: This function should almost always be followed by `exit;` to ensure proper redirection.
 *
 * @param string $url The absolute URL or relative path to redirect to.
 *
 * @return bool True if the redirection was initiated successfully, false if it was canceled.
 *
 * @since 9.6.6.4
 */
function crb_safe_redirect( string $url ) {
	CRB_Globals::$redirect_url = $url;

	return wp_safe_redirect( $url );
}

/**
 * Returns the given URL with an empty fragment, which makes a browser redirected to that URL
 * land on the page carrying an empty fragment, one that names nothing, instead of the
 * fragment of the page it is leaving.
 *
 * A browser following a redirect keeps the fragment of the URL it is leaving unless the URL
 * it is sent to states a fragment of its own. An empty fragment is a stated one, so it is
 * what discards the previous fragment. This is the only control a redirect has over the
 * fragment, since a fragment is never sent to the server and cannot be inspected here.
 * A fragment already present in the given URL is dropped.
 *
 * @param string $url The absolute URL or relative path to redirect to.
 *
 * @return string The URL with an empty fragment, ending with the hash character.
 *
 * @since 9.9.4.1
 */
function crb_url_discard_fragment( string $url ): string {

	$hash_position = mb_strpos( $url, '#' );

	if ( $hash_position !== false ) {
		$url = mb_substr( $url, 0, $hash_position );
	}

	return $url . '#';
}

/**
 * Generates a random string with the random length between $length_min and $length_max
 *
 * @param int $length_min Min length
 * @param int $length_max Max length
 * @param bool $inc_num Include numbers
 * @param bool $upper_case Include upper case
 * @param string $extra Additional characters (other than ASCII) to include
 *
 * @return string
 */
function crb_random_string( $length_min, $length_max = null, $inc_num = true, $upper_case = true, $extra = '' ) {
	static $alpha1 = 'abcdefghijklmnopqrstuvwxyz';
	static $alpha2 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	static $digits = '0123456789';

	if ( ! $length_max ) {
		$length_max = $length_min;
	}

	$str = $alpha1;

	if ( $inc_num ) {
		$str .= $digits;
	}

	if ( $upper_case ) {
		$str .= $alpha2;
	}

	if ( $extra ) {
		$str .= $extra;
	}

	$n = (int) ceil( $length_max / strlen( $str ) );

	if ( $n > 1 ) {
		$str = implode( '', array_fill( 0, $n, $str ) );
	}

	$length = ( $length_min != $length_max ) ? rand( $length_min, $length_max ) : $length_min;

	return substr( str_shuffle( $str ), 0, $length );
}

/**
 * Detects and decodes a serialized or JSON-encoded array.
 *
 * Empty, NULL, invalid JSON, JSON scalars, and unsupported serialized payloads
 * are treated as an empty array.
 *
 * @param string|null $encoded_text Serialized or JSON-encoded value.
 *
 * @return array<int|string, mixed>
 *
 * @since 8.8
 */
function crb_auto_decode( $encoded_text ): array {
	if ( ! $encoded_text ) {
		return array();
	}

	if ( ! is_string( $encoded_text ) ) {
		return array();
	}

	if ( $encoded_text[0] === 'a' ) {
		$decoded = crb_unserialize( $encoded_text );

		return is_array( $decoded ) ? $decoded : array();
	}

	$decoded = json_decode( $encoded_text, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return array();
	}

	return is_array( $decoded ) ? $decoded : array();
}

/**
 * A safe version of unserialize()
 *
 * @param string $string
 *
 * @return mixed
 *
 */
function crb_unserialize( &$string ) {
	return @unserialize( $string, [ 'allowed_classes' => false ] );
}

function crb_get_review_url( $vendor = null ) {
	static $urls = array(
		'tpilot'  => array( 'https://www.trustpilot.com/review/wpcerber.com', 'https://www.trustpilot.com/evaluate/wpcerber.com' ),
		'g2'      => array( 'https://www.g2.com/products/cerber-security-antispam-malware-scan/reviews/start' ),
		'wp'      => array( 'https://wordpress.org/support/plugin/wp-cerber/reviews/#new-post' ),
		'cap'     => array( 'https://reviews.capterra.com/new/187653' ),
		'tradius' => array( 'https://www.trustradius.com/products/wp-cerber-security/reviews' ),

	);

	$ret = $urls[ $vendor ];

	if ( $vendor == 'tpilot' ) {
		shuffle( $ret );
	}

	return $ret[0];
}

/**
 * Checks whether the plugin was activated more than the given number of seconds ago.
 *
 * @param int $ago Number of seconds since activation.
 *
 * @return bool True if the plugin was activated more than the given number of seconds ago, or if the activation time is unknown or invalid.
 */
function crb_was_activated( int $ago = 0 ): bool {
	static $activated;

	if ( ! isset( $activated ) ) {
		$activated = cerber_get_set( '_activated' );
	}

	if ( ! is_array( $activated )
	     || empty( $activated['time'] ) ) {
		return true;
	}

	return ( (int) $activated['time'] + $ago ) <= time();
}

/**
 * Return a "session verifier" to identify the current admin session among others admin sessions
 *
 * Copy of WP_Session_Tokens->hash_token();
 *
 * @param $token
 *
 * @return string
 */
function cerber_hash_token( $token ) {
	// If ext/hash is not present, use sha1() instead.
	if ( function_exists( 'hash' ) ) {
		return hash( 'sha256', $token );
	}
	else {
		return sha1( $token );
	}
}
/**
 * Convert a value to non-negative integer.
 *
 * @param mixed $val Data you wish to have converted to a non-negative integer.
 *
 * @return int A non-negative integer.
 *
 * @since 9.3.4
 */
function crb_absint( $val ) {
	return abs( (int) $val );
}

function crb_is_apache_mod_loaded( $mod = '' ) {
	static $loaded = array();

	if ( ! isset( $loaded[ $mod ] ) ) {
		$loaded[ $mod ] = apache_mod_loaded( 'mod_rewrite' );
	}

	return $loaded[ $mod ];
}

// The key-value cache

final class CRB_Cache {
	private static $cache = array();
	private static $stat = array();
	private static $wp_cache_group = 'wp_cerber';
	private static $wp_key_list = 'wp_cerber_list';

	static function set( $key, $value, $expire = 0 ): bool {
		$exp = 0;

		if ( $expire > 0 ) {
			$exp = time() + (int) $expire;
			if ( $exp < time() ) {
				return false;
			}
		}

		$element = array( $value, $exp );
		self::$cache[ $key ] = $element;

		if ( self::checker() ) {
			wp_cache_set( $key, $element, self::$wp_cache_group );

			$entries = wp_cache_get( self::$wp_key_list, self::$wp_key_list );
			if ( ! $entries ) {
				$entries = array();
			}
			$entries[ $key ] = $expire;
			wp_cache_set( self::$wp_key_list, $entries, self::$wp_key_list );
		}

		if ( ! isset( self::$stat[ $key ] ) ) {
			self::$stat[ $key ] = array( 0, 0 );
		}

		self::$stat[ $key ][0] ++;

		return true;
	}

	static function get( $key, $default = null ) {

		$element = crb_array_get( self::$cache, $key );

		if ( ! is_array( $element ) ) {
			if ( self::checker() ) {
				$element = wp_cache_get( $key, self::$wp_cache_group );
			}
		}

		if ( ! is_array( $element ) ) {
			return $default;
		}

		if ( ! empty( $element[1] ) && $element[1] < time() ) {
			self::delete( $key );

			return $default;
		}

		if ( ! isset( self::$stat[ $key ] ) ) {
			self::$stat[ $key ] = array( 0, 0 );
		}

		self::$stat[ $key ][1] ++;

		return $element[0];
	}

	static function delete( $key ) {
		unset( self::$cache[ $key ] );
		if ( self::checker() ) {
			wp_cache_delete( $key, self::$wp_cache_group );
		}
	}

	static function reset() {
		self::$cache = array();

		if ( $entries = wp_cache_get( self::$wp_key_list, self::$wp_key_list ) ) {
			foreach ( $entries as $entry => $exp ) {
				wp_cache_delete( $entry, self::$wp_cache_group );
			}

			wp_cache_delete( self::$wp_key_list, self::$wp_key_list );
		}
	}

	static function get_stat( $recheck = false ) {
		$entries = wp_cache_get( self::$wp_key_list, self::$wp_key_list );

		if ( $recheck && $entries ) { // Make sure that our list of existing key doesn't contain wrong entries
			foreach ( $entries as $key => $exp ) {
				if ( ! $element = wp_cache_get( $key, self::$wp_cache_group ) ) {
					unset( $entries[ $key ] );
				}
			}

			wp_cache_set( self::$wp_key_list, $entries, self::$wp_key_list );
		}

		if ( empty( $entries ) ) {
			$entries = array();
		}

		return array( self::$stat, $entries );
	}

	/**
	 * Checks if the WordPress persistent cache is functional by using a marker stored between HTTP requests.
	 *
	 * On the first call or if the cache is not operational, it initializes the marker and returns 0.
	 * If the cache works correctly, it will return the timestamp (`t`) that was set during the first successful check.
	 *
	 * @return int Returns 0 on the first call or if the cache is non-operational.
	 *             On subsequent requests, it returns the previously set timestamp from the first cache check,
	 *             confirming that the cache retains data across HTTP requests.
	 */
	static function checker() {

		$sid = get_wp_cerber()->getRequestID();
		$check = wp_cache_get( '__checker__', self::$wp_cache_group );

		if ( ! $check || ! isset( $check['t'] ) || ! isset( $check['s'] ) ) {
			wp_cache_set( '__checker__', array(
				't' => time(),
				's' => $sid
			), self::$wp_cache_group );

			return 0;
		}

		if ( $check['s'] == $sid ) {
			return 0;
		}

		return $check['t'];
	}
}

/**
 * Saves data to the WordPress persistent object cache if it's available.
 * Note: if no persistent object cache is available, any cached data will be lost between HTTP requests.
 * @see CRB_Cache::checker()
 *
 * @param $key string
 * @param $value mixed|array
 * @param $expire integer Cached data will expire in X seconds, 0 = never expires
 *
 * @return bool
 */
function cerber_cache_set( $key, $value, $expire = 0 ): bool {
	return CRB_Cache::set( $key, $value, $expire );
}

/**
 * Retrieves data from the WordPress persistent object cache if it's available.
 * Note: if no persistent object cache is available, the data will be lost between HTTP requests.
 *
 * @param $key string
 * @param $default mixed
 *
 * @return mixed|null
 */
function cerber_cache_get( $key, $default = null ) {
	return CRB_Cache::get( $key, $default );
}

function cerber_cache_delete( $key ) {
	CRB_Cache::delete( $key );
}

function cerber_cache_enable() {
	global $cerber_use_cache;
	$cerber_use_cache = true;
}

function cerber_cache_disable() {
	global $cerber_use_cache;
	$cerber_use_cache = false;
}

function cerber_cache_is_enabled() {
	global $cerber_use_cache;

	return ! empty( $cerber_use_cache );
}

/**
 * Retrieve data from the DB using SQL query and cache the results.
 * Return data from the cache if the table has not been changed.
 *
 * @param array|string $sql One or more SQL queries with optional data format of returning results
 * @param string $table DB table we're caching data for
 * @param bool $cache_only If true, returns data from the cache strictly
 * @param string[] $hash_fields Fields to calculate hash to detect changes in the table
 * @param int $order_by The ID of the table field in the $hash_fields to use for ORDER BY condition
 *
 * @return array|false
 *
 * @since 8.8.3.1
 */
function crb_q_cache_get( $sql, $table, $cache_only = false, $hash_fields = array( 'stamp', 'ip', 'session_id' ), $order_by = 0 ) {
	global $wp_cerber_q_cache;

	if ( is_string( $sql ) ) {
		$sql = array( array( $sql ) );
	}

	$single = ( count( $sql ) == 1 );

	$run = true;

	$cache_key = 'q_cache_' . sha1( implode( '|', array_column( $sql, 0 ) ) );
	$cache = cerber_get_set( $cache_key, 0, false, true );

	if ( $cache ) {
		$cache = json_decode( $cache );
		if ( $cache->hash == crb_get_table_hash( $table, $hash_fields, $order_by ) ) {
			$wp_cerber_q_cache = true;
			$run = false;
		}
	}

	if ( $run && $cache_only ) {
		return false;
	}

	if ( ! $run ) {
		$results = $cache->results;
	}
	else {

		$new_cache = array();

		$new_cache['hash'] = crb_get_table_hash( $table, $hash_fields, $order_by );

		$results = array();

		foreach ( $sql as $query ) {
			$results[] = cerber_db_get_results( $query[0], crb_array_get( $query, 1 ) );
		}

		$new_cache['results'] = $results;
		$new_cache = json_encode( $new_cache, JSON_UNESCAPED_UNICODE );

		cerber_update_set( $cache_key, $new_cache, 0, false, time() + 7200, true );
	}

	if ( $single ) {
		return $results[0];
	}

	return $results;
}

/**
 * Returns pseudo "hash" for a given log table to detect changes in the table
 *
 * @param string $table
 * @param string[] $hash_fields
 * @param int $order_by
 *
 * @return string
 * @since 8.8.3.1
 */
function crb_get_table_hash( $table, $hash_fields, $order_by ) {
	static $hashes;

	$fields = implode( ',', $hash_fields );
	$key = sha1( $table . '|' . $fields . '|' . $order_by );

	if ( ! isset( $hashes[ $key ] ) ) {
		if ( $data = cerber_db_get_row( 'SELECT ' . $fields . ' FROM ' . $table . ' ORDER BY ' . $hash_fields[ $order_by ] . ' DESC LIMIT 1' ) ) {
			$hashes[ $key ] = sha1( implode( '|', $data ) );
		}
		else {
			$hashes[ $key ] = '';
		}

	}

	return $hashes[ $key ];
}

add_filter( 'update_plugins_downloads.wpcerber.com', 'cerber_check_for_update', 10, 4 );

/**
 * Checks for a new version of WP Cerber and possible updates to translation files.
 * Retrieve data about the last version of WP Cerber and translation files using the URL from the official UpdateURI plugin header.
 *
 * See $update = apply_filters( "update_plugins_{$hostname}", false, $plugin_data, $plugin_file, $locales );
 *
 *
 * @param array|false $update Plugin update information.
 * @param array $plugin_data Plugin information.
 * @param string $plugin_file Plugin filename.
 * @param string[] $locales Installed locales to look up translations for.
 *
 * @return array|false
 *
 * @since 9.1.2
 */
function cerber_check_for_update( $update, $plugin_data, $plugin_file, $locales ) {

	if ( ! crb_get_settings( 'cerber_sw_repo' )
	     || ! $uri = crb_array_get( $plugin_data, 'UpdateURI' ) ) {
		return false;
	}

	$response = wp_remote_get( $uri );

	$err = '';

	if ( crb_is_wp_error( $response ) ) {
		$err = $response->get_error_message();
	}

	if ( ! $http_body = crb_array_get( $response, 'body' ) ) {
		cerber_update_set( 'last_update_check', array( 'error' => $err, 'no_body' => 1, 'uri' => $uri ) );

		return false;
	}

	$decoded_data = json_decode( $http_body, true );

	$release_info = crb_array_get( $decoded_data, $plugin_file, $update );

	if ( isset( $release_info['requires_wp'] ) ) {
		$release_info['requires'] = $release_info['requires_wp'];
	}

	$release_info['tested'] = cerber_get_wp_version(); // The last version of WP Cerber is always tested with the last version of WP
	$release_info['slug'] = 'wp-cerber';

	// External data, ensure it has correct format

	if ( ! is_array( $locales ) ) {
		$locales = array();
	}

	//$available = $release_info['trans_bucket'] ?? array();
	$available = crb_fetch_translation_updates(); // since 9.6.11

	if ( ! is_array( $available ) ) {
		$available = array();
	}

	$release_info['translations'] = crb_prepare_locales( $locales, $available );
	unset( $release_info['trans_bucket'] ); // Proprietary, not used by WP

	cerber_update_set( 'last_update_check', array( 'success' => time(), 'uri' => $uri ) );

	return $release_info;
}

add_filter( 'auto_update_plugin', function ( $update, $item ) {

	// $update = apply_filters( "auto_update_{$type}", $update, $item );

	if ( crb_get_settings( 'cerber_sw_auto' )
	     && isset( $item->plugin )
	     && $item->plugin == CERBER_PLUGIN_ID ) {
		return true;
	}

	return $update;

}, PHP_INT_MAX, 2 );


/**
 * Simple loader of JSON encoded payloads from a remote host.
 * Decodes the body of the response from the remote host expecting it is JSON encoded.
 *
 * @param string $url Secure URL to load the payload
 *
 * @return array|WP_Error Decoded array on success, WP_Error otherwise.
 *
 * @see wp_remote_get();
 *
 * @since 9.6.6.14
 */
function crb_get_remote_json( string $url ) {

	if ( 0 !== strpos( $url, 'https://' ) ) {
		return new WP_Error( 'unsafe_url', 'Invalid URL. It must begin with https://' );
	}

	if ( defined( 'CERBER_NETWORK_DEBUG' ) && CERBER_NETWORK_DEBUG ) {
		cerber_diag_log( 'Initiating downloading JSON payload from ' . $url );
	}

	$response = wp_remote_get( $url );

	if ( crb_is_wp_error( $response ) ) {

		return $response;
	}

	if ( ! $body = wp_remote_retrieve_body( $response ) ) {

		return new WP_Error( 'no_content', 'The response body is empty' );
	}

	$decoded = json_decode( $body, true );

	if ( ! $decoded
	     || JSON_ERROR_NONE != json_last_error() ) {

		return new WP_Error( 'json_not_found', 'Unable to decode JSON payload. ' . json_last_error_msg() );
	}

	if ( defined( 'CERBER_NETWORK_DEBUG' ) && CERBER_NETWORK_DEBUG ) {
		cerber_diag_log( 'Loaded and decoded successfully, size: ' . strlen( $body ) );
	}

	return $decoded;
}

/**
 * Returns full last login info if it exists, false otherwise.
 * Updates last login information, if it's empty.
 *
 * @param $user_id
 *
 * @return false|array
 *
 * @since 9.4.1
 */
function crb_get_last_user_login( $user_id ) {
	$user_set = cerber_get_set( CRB_USER_SET, $user_id );

	if ( $user_set['last_login']['cn'] ?? false ) {
		return $user_set['last_login'];
	}

	if ( ! $row = cerber_get_last_login( $user_id ) ) {
		return false;
	}

	// Updating introduced in 9.4.1 elements

	$user_set['last_login']['ts'] = $row->stamp;
	$user_set['last_login']['cn'] = $row->country ?: lab_get_country( $row->ip, false );

	cerber_update_set( CRB_USER_SET, $user_set, $user_id );

	return $user_set['last_login'];
}

/**
 * Add global cURL parameters
 *
 * @param CurlHandle|resource $curl
 * @param array $params
 *
 * @return bool true if all options were successfully set. If an option could
 * not be successfully set, false is immediately returned, ignoring any
 * future options in the options array.
 *
 * @since 9.5.4.3
 *
 */
function crb_configure_curl( $curl, $params, $setting = 'main_use_proxy' ) {
	global $wp_cerber_relay;

	if ( crb_get_settings( $setting ) ) {
		if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ) {

			$params[ CURLOPT_PROXYTYPE ] = defined( 'CERBER_PROXY_TYPE' ) ? CERBER_PROXY_TYPE : CURLPROXY_HTTP;
			$params[ CURLOPT_PROXY ] = WP_PROXY_HOST;
			$params[ CURLOPT_PROXYPORT ] = WP_PROXY_PORT;

			if ( defined( 'WP_PROXY_USERNAME' ) && defined( 'WP_PROXY_PASSWORD' ) ) {
				$params[ CURLOPT_PROXYAUTH ] = CURLAUTH_ANY;
				$params[ CURLOPT_PROXYUSERPWD ] = WP_PROXY_USERNAME . ':' . WP_PROXY_PASSWORD;
			}
		}

		$wp_cerber_relay = 1;
	}
	else {
		$wp_cerber_relay = 0;
	}

	try {
		if ( ! curl_setopt_array( $curl, $params ) ) {
			throw new Exception( 'Failed to set cURL options.' );
		}
	}
	catch ( Throwable $e ) {

		if ( defined( 'CERBER_NETWORK_DEBUG' ) && CERBER_NETWORK_DEBUG ) {
			cerber_error_log( $e->getMessage() . '. Error thrown on line ' . $e->getLine() . ' in file ' . $e->getFile() . '. cURL options provided: ' . print_r( $params, 1 ), 'NETWORK' );
		}

		return false;
	}

	return true;

}

/**
 * Returns the slug of a plugin based on the path to its main PHP file.
 *
 * @param string $path The path to the main plugin file (absolute or relative).

 * @return string The plugin slug.
 *
 * @since 9.6.2.6
 */
function crb_get_plugin_slug( string $path ): string {

	if ( strpos( $path, WP_PLUGIN_DIR ) === 0 ) {
		$path = str_replace( WP_PLUGIN_DIR, '', $path );
		$path = ltrim( $path, '/' );
	}

	if ( strpos( $path, '/' ) ) {
		$plugin_slug = dirname( $path );
	}
	elseif ( $path === 'hello.php' ) {
		$plugin_slug = 'hello-dolly';
	}
	else {
		$plugin_slug = preg_replace( '/\.php$/', '', $path );
	}

	return $plugin_slug;
}

/**
 * Load definitions of WordPress functions from its PHP files
 * This approach is suitable for 99% websites.
 * The problem may arise if the given function is defined in a plugin that is not loaded yet.
 *
 * @param string $func
 * @param bool $load_cons
 *
 * @return bool
 *
 * @since 9.5.5.1
 */
function crb_load_dependencies( $func, $load_cons = false ) {

	if ( function_exists( $func ) ) {
		return false;
	}

	if ( $load_cons ) {
		cerber_load_wp_constants();
	}

	$ret = true;

	switch ( $func ) {
		case 'wp_mail':
		case 'wp_create_nonce':
		case 'is_user_logged_in':
		case 'wp_get_current_user':
		case 'get_userdata':
		case 'get_user_by':
			require_once( ABSPATH . WPINC . '/pluggable.php' );
			break;
		case 'wp_is_auto_update_enabled_for_type':
			require_once( ABSPATH . 'wp-admin/includes/update.php' );
			break;
		default:
			$ret = false;
	}

	return $ret;
}

/**
 * A replacement for global PHP variables. It doesn't make them good (less ugly), but it helps to trace their usage easily (within IDE).
 *
 * @since 8.9.4
 *
 */
class CRB_Globals {
	static $session_status;
	static $act_status;
	static $do_not_log = array();
	static $reset_pwd_msg = '';
	static $reset_pwd_denied = false;
	static $retrieve_password = false; // User is trying to retrieve password
	static $login_form_errors = array();
	static $user_id;
	static $req_status = 0;
	private static $assets_url = '';
	static $ajax_loader = '';
	static $logged = array();
	static $blocked;
	static $db_requests = array();
	static $db_errors = array();
	static $bot_status = 0;
	static $htaccess_failure = array();
	private static $by_settings = array();
	static $admin_footer_html = '';
	static $redirect_url = '';

	static $doing_upgrade;

	static function admin_init() {
	}

	/**
	 * Returns a safe URL to the WP Cerber assets folder or a file in it.
	 *
	 * @param string $res A file in the assets folder
	 * @param bool $echo Echo the result if true
	 *
	 * @return string Safe for any HTML context
	 *
	 * @since 9.5.1
	 */
	static function assets_url( $res = '', $echo = false ) {

		if ( ! self::$assets_url ) {
			self::$assets_url = crb_escape_url( cerber_plugin_dir_url() ) . 'assets/';
		}

		$ret = self::$assets_url;

		if ( $res ) {
			$ret .= $res;
		}

		if ( $echo ) {
			echo $ret;
		}

		return $ret;
	}

	/**
	 * Alternate the asset URL
	 *
	 * @param string $url
	 *
	 * @return void
	 */
	static function set_assets_url( $url ) {
		self::$assets_url = crb_escape_url( $url );
	}

	/**
	 * @param integer $val
	 *
	 * @return void
	 */
	static function set_bot_status( $val ) {
		self::$bot_status = (int) $val;
		CRB_Globals::set_act_status( $val ); // For backward compatibility
	}

	/**
	 * @param integer $val
	 * @param string $setting_id
	 *
	 * @return void
	 */
	static function set_act_status_if( $val, $setting_id = '' ) {
		if ( ! self::$act_status ) {
			self::$act_status = $val;
			if ( $setting_id ) {
				self::$by_settings[] = $setting_id;
			}
		}
	}

	/**
	 * Save the status (why) WP Cerber took an action while processing the current request.
	 *
	 * @param integer $val
	 * @param string $setting_id
	 *
	 * @return void
	 *
	 * @since 9.5.8.1
	 */
	static function set_act_status( $val, $setting_id = '' ) {
		self::$act_status = $val;
		if ( $setting_id ) {
			self::$by_settings[] = $setting_id;
		}
	}

	/**
	 * Collects WP Cerber's settings that control its behavior while processing the current request.
	 * We expect just one, but it's possible it can be a combination of them
	 * since there are top-level settings that enables nested (low-level) ones.
	 *
	 * @param string $setting_id
	 *
	 * @return void
	 *
	 * @since 9.6.1.1
	 */
	static function set_ctrl_setting( $setting_id ) {
		self::$by_settings[] = $setting_id;
	}

	/**
	 * Returns WP Cerber's settings that control its behavior while processing the current request.
	 * We expect just one, but it's possible it can be a combination of them
	 * since there are top-level settings that enables nested (low-level) ones.
	 *
	 * @return array The last element is the most crucial, last in the sequence of settings that guided WP Cerber
	 *
	 * @since 9.6.1.1
	 */
	static function get_ctrl_settings() {
		return self::$by_settings;
	}

	/**
	 * Saves HTML for outputting it to the admin footer
	 *
	 * @param string $html
	 *
	 * @return void
	 *
 	 * @since 9.6.1.3
	 */
	static function to_admin_footer( $html ) {
		self::$admin_footer_html .= (string) $html;
	}
}
