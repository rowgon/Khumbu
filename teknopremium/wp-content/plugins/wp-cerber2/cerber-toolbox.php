<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licenced under the GNU GPL.

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

/**
 *
 * Periodically and occasionally used routines
 *
 */

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

/**
 * Send email notification if  plugin is available
 *
 * @param bool $no_check_freq If true, ignore the frequency setting
 * @param bool $no_check_history If true, do not check sending history. Use for testing.
 * @param bool $result The results of sending
 * @param array $info Error messages if any
 *
 * @return integer|false false if there is no information about updates, otherwise the number of messages sent
 *
 * @since 9.4.3
 */
function crb_plugin_update_notifier( $no_check_freq = false, $no_check_history = false, &$result = false, &$info = array() ) {

	if ( ! crb_get_settings( 'notify_plugin_update' ) ) {
		return false;
	}

	$updates = get_site_transient( 'update_plugins' );
	$interval = ( ! lab_lab() ) ? 24 : (int) crb_get_settings( 'notify_plugin_update_freq' );
	$interval = HOUR_IN_SECONDS * ( ( $interval < 1 ) ? 1 : $interval );

	$prev = cerber_get_set( 'plugin_update_alerting_status' );

	if ( ! $no_check_freq
	     && isset( $prev[0] )
	     && $prev[0] > ( time() - $interval ) ) {
		return false;
	}

	if ( ! $updates
	     || empty( $updates->last_checked )
	     || empty( $updates->response )
	     || ( $updates->last_checked < ( time() - $interval ) ) ) {

		delete_site_transient( 'update_plugins' );
		wp_update_plugins();

		$updates = get_site_transient( 'update_plugins' );
	}

	$errors = 0;
	$sent = 0;

	if ( empty( $updates->response ) ) {
		cerber_update_set( 'plugin_update_alerting_status',
			array(
				time(),
				( $updates->last_checked ?? 0 ),
				( $updates->checked ?? 0 ),
				$errors,
				$sent
			) );

		$info[] = __( 'No updates found.', 'wp-cerber' );

		if ( empty( $updates->checked ) ) {
			$info[] = __( 'It seems outgoing Internet connections are not allowed on your website.', 'wp-cerber' );
		}

		return false;
	}

	$history = cerber_get_set( 'plugin_update_alerting' );

	if ( ! is_array( $history ) ) {
		$history = array();
	}

	$brief = ( ! lab_lab() ) ? 0 : crb_get_settings( 'notify_plugin_update_brf' );
	$active_plugins = get_option( 'active_plugins' );
	$result = false;

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); // get_plugin_data()

	foreach ( $updates->response as $plugin => $new_data ) {
		if ( ! $no_check_history && isset( $history[ $plugin ][ $new_data->new_version ] ) ) {
			continue;
		}

		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

		$name = htmlspecialchars_decode( $plugin_data['Name'] );

		$notes = array();

		if ( ! empty( $new_data->requires )
		     && ! crb_wp_version_compare( $new_data->requires ) ) {
			/* translators: Here %s is a version number like 6.1 */
			$notes[] = '[!] ' . sprintf( __( 'This update requires WordPress version %1$s or higher, you have %2$s', 'wp-cerber' ), $new_data->requires, ( $brief ? '*' : cerber_get_wp_version() ) );
		}

		if ( ! empty( $new_data->requires_php )
		     && version_compare( $new_data->requires_php, phpversion(), '>' ) ) {
			/* translators: Here %s is a version number like 6.1 */
			$notes[] = '[!] ' . sprintf( __( 'This update requires PHP version %1$s or higher, you have %2$s', 'wp-cerber' ), $new_data->requires_php, ( $brief ? '*' : phpversion() ) );
		}

		if ( ! empty( $new_data->tested )
		     && crb_wp_version_compare( $new_data->tested, '>' ) ) {
			$notes[] = '[!] ' . __( 'This update has not been tested with your version of WordPress', 'wp-cerber' );
		}

		$msg = array(
			__( 'There is an update to the plugin installed on your website.', 'wp-cerber' ),
		);

		if ( $notes ) {
			$msg = array_merge( $msg, $notes );
		}

		$active = ( in_array( $plugin, $active_plugins ) ) ? __( 'Yes', 'wp-cerber' ) : __( 'No', 'wp-cerber' );

		$msg = array_merge( $msg, array(
			/* translators: %s is the website name. */
			sprintf( __( 'Website: %s', 'wp-cerber' ), crb_get_blogname_decoded() ),
			/* translators: %s is the plugin name. */
			sprintf( __( 'Plugin: %s', 'wp-cerber' ), $name ),
			/* translators: %s is either Yes or No. */
			sprintf( __( 'Active: %s', 'wp-cerber' ), $active ),
			/* translators: %s is the installed version number. */
			sprintf( __( 'Installed version: %s', 'wp-cerber' ), ( $brief ? '*' : crb_boring_escape( $plugin_data['Version'] ) ) ),
			/* translators: %s is the new version number. */
			sprintf( __( 'New version: %s', 'wp-cerber' ), $new_data->new_version ),
		) );

		if ( ! empty( $new_data->tested ) ) {
			/* translators: %s is the WordPress version the plugin is tested up to. */
			$msg[] = sprintf( __( 'Tested up to: WordPress %s', 'wp-cerber' ), $new_data->tested );
		}

		/* translators: %s is the plugin page URL. */
		$msg[] = sprintf( __( 'Plugin page: %s', 'wp-cerber' ), $new_data->url );

		if ( ! $brief ) {
			/* translators: %s is the URL to manage plugins. */
			$msg[] = sprintf( __( 'Manage plugins on your website: %s', 'wp-cerber' ), admin_url( 'plugins.php' ) );
		}

		$args = ( ! lab_lab() ) ? array() : array( 'recipients_setting' => 'notify_plugin_update_to' );

		$result = CRB_Messaging::send( 'generic', array(
			/* translators: Here %s is a name of software package (module). */
			'subj' => sprintf( __( 'A new version of %s is available', 'wp-cerber' ), $name ),
			'text' => $msg
		), array( 'email' => 1, 'pushbullet' => 0 ), true, $args );

		if ( $result ) {
			$sent ++;
			$history[ $plugin ][ $new_data->new_version ] = time();
			if ( ! $no_check_history ) {
				cerber_update_set( 'plugin_update_alerting', $history );
			}
		}
		else {
			$errors ++;
			CRB_Issues::add( __FUNCTION__, __( 'Delivery of update notification emails was unsuccessful. Notifications about security updates may not reach you. Check email notification settings and recipient addresses.', 'wp-cerber' ), array( 'type' => CRB_Issues::TYPE_EVENT, 'severity' => CRB_Issues::SEVERITY_WARNING ) );
		}
	}

	cerber_update_set( 'plugin_update_alerting_status',
		array(
			time(),
			( $updates->last_checked ?? 0 ),
			( $updates->checked ?? 0 ),
			$errors,
			$sent,
			( is_array( $result ) ? $result : 0 )
		) );

	return $sent;
}

/**
 * If WordPress core find an update earlier than WP Cerber,
 * notify admin (ASAP) using postponed tasks
 *
 */
add_action( 'set_site_transient_update_plugins', function () {
	cerber_update_set( 'event_wp_found_updates', 1, null, false );
} );

/**
 * Remove temporary and obsolete data from WP Cerber DB tables and logs
 *
 * @return void
 *
 * @since 9.4.2.4
 */
function crb_log_maintainer() {

	// Get non-cached settings since they can be filled with default values in case of a DB error
	$settings = crb_get_settings( '', true, false );

	// Capture current time once to ensure consistency across all operations
	$now = time();

	// --- 1. Activity Log Cleanup ---

	$days = absint( $settings['keeplog'] ?? 0 ) ?: cerber_get_defaults( 'keeplog' );
	$days_auth = absint( $settings['keeplog_auth'] ?? 0 ) ?: $days;

	if ( $days === $days_auth ) {
		CRB_Activity::delete( [ 'stamp' => [ '<', $now - ( $days * DAY_IN_SECONDS ) ] ] );
	}
	else {
		// Guests/Bots (user_id 0)
		CRB_Activity::delete( [ 'user_id' => 0, 'stamp' => [ '<', $now - ( $days * DAY_IN_SECONDS ) ] ] );
		// Authenticated users
		CRB_Activity::delete( [ 'user_id' => [ '!=', 0 ], 'stamp' => [ '<', $now - ( $days_auth * DAY_IN_SECONDS ) ] ] );
	}

	// --- 2. Traffic Log Cleanup ---

	$days = absint( $settings['tikeeprec'] ?? 0 ) ?: cerber_get_defaults( 'tikeeprec' );
	$days_auth = absint( $settings['tikeeprec_auth'] ?? 0 ) ?: $days;

	if ( $days === $days_auth ) {
		cerber_db_query( 'DELETE FROM ' . CERBER_TRAF_TABLE . ' WHERE stamp < ' . ( $now - ( $days * DAY_IN_SECONDS ) ) );
	}
	else {
		cerber_db_query( 'DELETE FROM ' . CERBER_TRAF_TABLE . ' WHERE user_id = 0 AND stamp < ' . ( $now - ( $days * DAY_IN_SECONDS ) ) );
		cerber_db_query( 'DELETE FROM ' . CERBER_TRAF_TABLE . ' WHERE user_id != 0 AND stamp < ' . ( $now - ( $days_auth * DAY_IN_SECONDS ) ) );
	}

	// --- 3. Other Cleanup ---

	cerber_db_query( 'DELETE FROM ' . CERBER_LAB_IP_TABLE . ' WHERE expires < ' . $now );

	// --- 4. Spam Comments Cleanup ---

	$trash_after_days = absint( $settings['trashafter'] ?? 0 );

	// Check if enabled and days > 0
	if ( ! empty( $settings['trashafter-enabled'] ) && $trash_after_days > 0 ) {

		$wp_stats = wp_count_comments();

		$monitor = new CRB_Process_Monitor( '_spam_cleanup_status' );
		$monitor->set( 'total_spam_comments', (int) $wp_stats->spam );

		// Calculate timestamp threshold
		$threshold_ts = $now - ( $trash_after_days * DAY_IN_SECONDS );

		// Format as GMT MySQL Datetime string for robust SQL query
		$threshold_str = gmdate( 'Y-m-d H:i:s', $threshold_ts );

		$spam_comments = get_comments( [
			'status'     => 'spam',
			'date_query' => [
				[
					'column'    => 'comment_date_gmt',
					'before'    => $threshold_str,
					'inclusive' => true,
				],
			],
			'fields'     => 'ids',
			'number'     => 1000, // Limit batch size to prevent timeouts on huge spam lists
		] );

		$monitor->set( 'batch_size', count( $spam_comments ) );

		if ( $spam_comments ) {

			foreach ( $spam_comments as $comment_id ) {
				wp_trash_comment( $comment_id );
			}
		}

		$monitor->finish();
	}
}

/**
 * Updating old activity log records to the new row format (introduced in v 3.1)
 *
 * @since 4.0
 */
function crb_once_upgrade_log() {

	if ( ! $ips = cerber_db_get_col( 'SELECT DISTINCT ip FROM ' . CERBER_LOG_TABLE . ' WHERE ip_long = 0 LIMIT 50' ) ) {
		return;
	}

	foreach ( $ips as $ip ) {
		$ip_long = cerber_is_ipv4( $ip ) ? ip2long( $ip ) : 1;
		cerber_db_query( 'UPDATE ' . CERBER_LOG_TABLE . ' SET ip_long = ' . $ip_long . ' WHERE ip = "' . $ip .'" AND ip_long = 0');
	}
}

/**
 * Copying last login data to the user sets in bulk
 *
 * @return void
 *
 * @since 9.4.2
 */
function crb_once_upgrade_cbla() {
	$status = cerber_get_set( 'cerber_db_status' ) ?: array();
	$lal = $status['lal'] ?? false;

	if ( 'done' == $lal ) {
		return;
	}

	$table = cerber_get_db_prefix() . CERBER_SETS_TABLE;

	if ( 'progress' != $lal ) {
		if ( ! cerber_db_query( 'UPDATE ' . $table . ' SET argo = 1 WHERE the_key = "' . CRB_USER_SET . '"' ) ) {
			$status['lal'] = 'done';
			cerber_update_set( 'cerber_db_status', $status );

			return;
		}
		$status['lal'] = 'progress';
		cerber_update_set( 'cerber_db_status', $status );
	}
	elseif ( ! cerber_db_get_var( 'SELECT the_key FROM ' . $table . ' WHERE the_key = "' . CRB_USER_SET . '" AND argo = 1 LIMIT 1' ) ) {
		$status['lal'] = 'done';
		cerber_update_set( 'cerber_db_status', $status );

		return;
	}

	if ( ! $users = cerber_db_get_col( 'SELECT the_id FROM ' . $table . ' WHERE the_key = "' . CRB_USER_SET . '" AND argo = 1 LIMIT 1000' ) ) {

		return;
	}

	cerber_cache_disable();

	foreach ( $users as $user_id ) {
		crb_get_last_user_login( $user_id );
		cerber_db_query( 'UPDATE ' . $table . ' SET argo = 0 WHERE the_key = "' . CRB_USER_SET . '" AND the_id = ' . $user_id );
	}

	if ( $db_errors = cerber_db_get_errors() ) {
		$db_errors = array_slice( $db_errors, 0, 10 );
		cerber_admin_notice( 'Database errors occurred while upgrading user sets to a new format.' );
		cerber_admin_notice( $db_errors );
	}
}

/**
 * Handles information about a given plugin
 *
 * @since 9.6.2.6
 */
class CRB_Plugin {
	/**
	 * The last network/repo error if any
	 *
	 * @var string
	 */
	private static $last_error;


	/**
	 * Generates an end-user plugin status report
	 *
	 * @param string $slug The plugin slug.
	 * @param bool $refresh
	 *
	 * @return array An array containing the plugin status level and the status messages.
	 */
	static function get_plugin_status( string $slug, bool $refresh = false ): array {

		$status = array();

		if ( crb_get_settings( 'scan_abon_pl' ) ) {
			$period = crb_get_settings( 'scan_abon_pl_period' );
			$status['plugin_abnd'] = self::get_plugin_repo_status( $slug, $period, $refresh );
		}

		return $status;
	}

	/**
	 * Retrieves plugin ownership data using wordpress.org plugin API and update history of changes if any occurs
	 *
	 * @param string $slug Plugin slug
	 *
	 * @return array|WP_Error
	 */
	static function get_plugin_owner_status( string $slug ) {

		$fresh_data = self::get_plugin_authors( $slug );

		if ( crb_is_wp_error( $fresh_data ) ) {
			return $fresh_data;
		}

		$plugin_data = self::get_plugin_data( $slug );
		$update = false;
		$ownership = $plugin_data['ownership'] ?? false;

		if ( $ownership && is_array( $ownership ) ) {
			if ( $ownership['last']['owner'] != $fresh_data['owner'] ) {

				$update = true; // New owner

				if ( count( $ownership['history'] ) > 50 ) {
					ksort( $ownership['history'] );
					$ownership['history'] = array_slice( $ownership['history'], - 50 );
				}
			}
		}
		else {
			$ownership = array();
			$update = true;
		}

		if ( $update ) {
			$ownership['history'][ time() ] = $fresh_data;
			$ownership['last'] = $fresh_data;
			self::update_plugin_data( $slug, array( 'ownership' => $ownership ) );
		}

		return $ownership;
	}
	/**
	 * Retrieves the author of a plugin from the WordPress.org plugin repository.
	 *
	 * @param string $slug The slug of the plugin.
	 *
	 * @return array|WP_Error The author of the plugin, or WP_Error object if there is an error.
	 */
	static function get_plugin_authors( string $slug ) {

		$plugin_info = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

		if ( crb_is_wp_error( $plugin_info ) ) {
			return $plugin_info;
		}

		$author = '';

		if ( empty( $plugin_info->author_profile ) ) {
			return new WP_Error( 'invalid_plugin_api', 'Unable to retrieve authorship info due to invalid plugin API response received from wordpress.org.' );
		}

		foreach ( $plugin_info->contributors as $contributor => $data ) {
			if ( $data['profile'] == $plugin_info->author_profile ) {
				$author = $contributor;
				break;
			}
		}

		if ( ! $author ) {
			$author = $plugin_info->author_profile; // Way around
		}

		return array( 'owner' => $author, 'author_profile' => $plugin_info->author_profile, 'contributors' => $plugin_info->contributors );
	}

	/**
	 * Create a plugin abandonment status message based on the information in the plugin repo
	 *
	 * @param string $slug Plugin slug.
	 * @param int $period Number of months to consider the plugin as being abandoned
	 * @param bool $refresh Force to refresh data stored in the local DB
	 *
	 * @return array An array containing the plugin status level and the status messages.
	 */
	static function get_plugin_repo_status( string $slug, int $period, bool $refresh = false ): array {

		$status = array();
		$status['plugin_slug'] = $slug;
		$status['updated'] = 0;

		$one_month = 30 * DAY_IN_SECONDS;

		// Threshold in UTC rounded to midnight

		$threshold = floor( ( time() - $period * $one_month ) / DAY_IN_SECONDS ) * DAY_IN_SECONDS;

		$data = self::get_plugin_data( $slug );

		// Update stored plugin data if needed

		$update = false;

		if ( ! $repo_data = $data['repo'] ?? false ) {
			$update = true;
		}
		elseif ( $err_code = $repo_data['err_code'] ?? false ) {
			if ( in_array( $err_code, array( CRB_PL722, CRB_PL724 ) )
			     || $repo_data['updated_uts'] < ( time() - 24 * 3600 ) ) {
				$update = true;
			}
		}
		elseif ( $repo_data['updated_uts'] < $threshold ) {
			$update = true;
		}
		elseif ( $repo_data['modified_uts'] <= $threshold ) { // Abandoned candidate, double check it
			$update = true;
		}

		if ( $refresh
		     || ( $update
		          && ! ( ( $repo_data['updated_uts'] ?? 0 ) > ( time() - 12 * 3600 ) ) ) ) { // Reasonable threshold

			$repo_data = self::update_plugin_repo_data( $slug );

			$status['updated'] = 1;
		}

		// Generating plugin status message/report

		$msg = '';

		if ( $err_code = $repo_data['err_code'] ?? false ) {
			if ( in_array( $err_code, array( CRB_PL722, CRB_PL723 ) ) ) {
				$level = CRB_SEV_NOTICE;
				$code = CRB_SA221;
			}
			else {
				$level = CRB_SEV_CRITICAL;
				$code = CRB_SA222;
			}

			$msg = crb_get_error_msg( $err_code ) . ' ' . self::$last_error;
		}
		else {

			// We got valid plugin data from the repo

			if ( $repo_data['modified_uts'] < $threshold ) {
				$level = CRB_SEV_WARNING;
				$code = CRB_SA223;

				$time_diff = time() - $repo_data['modified_uts'];
				$one_year = 365 * DAY_IN_SECONDS;

				if ( $time_diff > $one_year ) {
					$msg = __( 'It appears this plugin is abandoned, as it has not received any updates for over a year.', 'wp-cerber' );
				}
				elseif ( $time_diff >= 2 * $one_month ) {
					$msg = __( 'It appears this plugin is abandoned, as it has not received any updates for several months.', 'wp-cerber' );
				}
				elseif ( $time_diff >= $one_month ) {
					$msg = __( 'It appears this plugin is abandoned, as it has not received any updates for over a month.', 'wp-cerber' );
				}

				/* translators: Here %s is the date. */
				$msg .= ' ' . sprintf( __( 'The last update was at %s', 'wp-cerber' ), cerber_date( $repo_data['modified_uts'], false ) );
			}
			else {
				$msg = 'OK';
				$level = CRB_SEV_OK;
				$code = CRB_SA224;
			}
		}

		// Ready to show to end-user

		$status['sts_code'] = $code; // Status ID
		$status['level'] = $level; // Severity
		$status['status_msg'] = $msg; // Text message
		$status['repo_data'] = $repo_data;

		return $status;
	}

	/**
	 * Retrieves data from the repo and updates the plugin data in the database for the given plugin slug.
	 *
	 * @param string $slug The slug of the plugin.
	 *
	 * @return array The plugin data retrieved from the repo.
	 */
	private static function update_plugin_repo_data( string $slug ): array {

		$repo_data = array();

		$result = self::retrieve_plugin_repo_data( $slug );

		if ( crb_is_wp_error( $result ) ) {
			$repo_data['err_code'] = $result->get_error_code();
		}
		else {
			$result = self::sanitize( $result );
			$raw = $result;
			$repo_data['modified_date'] = $result['dateModified'] ?? '';
			$repo_data['modified_uts'] = $repo_data['modified_date'] ? strtotime( $repo_data['modified_date'] ) : '';
			$repo_data['last_version'] = $last_ver = $result['softwareVersion'] ?? '';

			$repo_data['raw_data'] = $raw;

			// Save history of changes

			$log = $repo_data['raw_log'] ?? false;

			if ( ! is_array( $log ) ) {
				$log = array();
			}

			if ( ! crb_array_search_row( $log, 'vrs', $last_ver ) ) {
				$log[] = array( 'vrs' => $last_ver, 'raw' => $repo_data['raw_data'] );
				$log = array_slice( $log, -10 );
			}

			$repo_data['raw_log'] = $log;
		}

		$repo_data['updated_uts'] = time();

		self::update_plugin_data( $slug, array( 'repo' => $repo_data ) );

		return $repo_data;
	}

	/**
	 * Sanitize and convert all values to strings in the given multi-dimensional array and limit total elements
	 *
	 * @param array &$data The input array to be sanitized.
	 * @param int $max_elements The maximum number of elements allowed in the array.
	 * @param int $element_count The current count of elements in the array.
	 * @return array The sanitized and limited array.
	 *
	 */
	static function sanitize( array &$data, int $max_elements = 100, int &$element_count = 0 ): array {
		$sanitized = [];

		foreach ( $data as $key => $value ) {
			if ( $element_count >= $max_elements ) {
				break;
			}

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = self::sanitize( $value, $max_elements, $element_count );
			}
			else {
				$value = (string) $value;
				$sanitized[ $key ] = substr( strip_tags( $value ), 0, 300 );
			}

			$element_count ++;
		}

		return $sanitized;
	}

	/**
	 * Returns the plugin data stored locally in the DB.
	 *
	 * @param string $slug The slug of the plugin.
	 *
	 * @return array Plugin data as an array, an empty array if no data.
	 */
	static function get_plugin_data( string $slug ): array {

		$key = substr( 'pl_data_' . $slug, 0, 255 );
		$data = cerber_get_set( $key );

		if ( ! $data || ! is_array( $data ) ) {
			$data = array();
		}

		return $data;
	}

	/**
	 * Updates the plugin data with the given update array.
	 *
	 * @param string $slug The slug of the plugin to update.
	 * @param array $update The update array to merge with the existing plugin data.
	 *
	 * @return bool Returns true if the plugin data is updated successfully, otherwise false.
	 */
	static function update_plugin_data( string  $slug, array $update ) {

		$key = substr( 'pl_data_' . $slug, 0, 255 );
		$data = cerber_get_set( $key );

		if ( ! $data || ! is_array( $data ) ) {
			$data = array();
		}

		$data = array_merge( $data, $update );

		return cerber_update_set( $key, $data );
	}

	/**
	 * Retrieves plugin data from the WP.ORG repository by the given plugin slug (which is the plugin folder).
	 *
	 * @param string $slug The slug of the plugin.
	 *
	 * @return array|WP_Error Returns the extracted JSON-LD plugin data from the plugin webpage, or WP_Error object if there is an error.
	 */
	static function retrieve_plugin_repo_data( string $slug ) {

		if ( ! $slug = preg_replace( '/[^a-z\-\d_]/i', '', $slug ) ) {
			return new WP_Error( CRB_PL721 );
		}

		$network = new CRB_Net();

		$url = 'https://wordpress.org/plugins/' . $slug . '/';

		$result = $network->http_get( array(
			'host' => 'wordpress.org',
			'path' => '/plugins/' . $slug . '/'
		),
			array(
				CURLOPT_FOLLOWLOCATION => false,
			),
			true );

		if ( crb_is_wp_error( $result ) ) {

			if ( $network->is_host_rate_limited() ) {
				$err_code = CRB_PL722;
			}
			else {
				switch ( $network->get_code() ) {
					case 404: // No plugin in the repo
					case 301: // No plugin in the repo
						$err_code = CRB_PL723;
						break;
					default:
						$err_code = CRB_PL724;
						self::$last_error = 'URL: ' . $url . ', ERROR: ' . $result->get_error_message();;
				}
			}

			return new WP_Error( $err_code );
		}

		$html = $network->get_body();

		unset( $network );

		// Extract data from the HTML content

		$json = self::extract_ld_json( $html );

		if ( crb_is_wp_error( $json ) ) {
			return $json;
		}

		return self::extract_wp_plugin_data( $json, $slug );
	}

	/**
	 * Extracts plugin data from an array of JSON strings.
	 *
	 * @param array $payload An array of JSON strings to look for plugin data.
	 *
	 * @return array|WP_Error Returns an array containing plugin data, or a WP_Error object if no valid plugin data found.
	 */
	private static function extract_wp_plugin_data( array $payload ) {

		foreach ( $payload as $json ) {
			$decoded = json_decode( $json, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				continue;
			}

			// WP.ORG format

			if ( ( $decoded[0]['applicationCategory'] ?? '' ) === 'Plugin' &&
			     ( $decoded[0]['operatingSystem'] ?? '' ) === 'WordPress' ) {
				return $decoded[0];
			}
		}

		return new WP_Error( CRB_PL725 );

	}

	/**
	 * Extracts JSON-LD data from HTML content.
	 *
	 * @param string $html_content The HTML content to extract JSON-LD data from.
	 *
	 * @return array|WP_Error The extracted JSON-LD data as an associative array, or a WP_Error object if extraction fails.
	 *
	 * @since 9.6.2.4
	 */
	private static function extract_ld_json( string $html_content ) {

		preg_match( '/<head>(.*?)<\/head>/is', $html_content, $matches_head );

		if ( empty( $matches_head[1] ) ) {
			return new WP_Error( CRB_PL726 );
		}

		$head_content = $matches_head[1];

		preg_match_all( '/<script type="application\/ld\+json">(.*?)<\/script>/is', $head_content, $matches_script );

		if ( empty( $matches_script[1] ) ) {
			return new WP_Error( CRB_PL727 );
		}

		return $matches_script[1];
	}
}
