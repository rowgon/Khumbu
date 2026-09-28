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

/**
 * Dispatches admin alert notifications for a logged activity event.
 *
 * Encapsulates the matching of the subscribed alert rules stored in the
 * CRB_ALL_ALERTS site option against a single activity event and the delivery
 * of a single notification per HTTP request via CRB_Messaging.
 *
 * @since 9.8.1
 */
final class CRB_Activity_Alerts {

	// Positional mapping: parameter names, default values, and position.
	// Critical: the order of these fields MUST NOT be changed because this is a positional array.
	// This array represents the list of fields and their order for each saved alert.
	// This structure is a combination of event parameters and alert settings.
	// See also CRB_Activity::EVENT_FILTERING_PARAMS

	public const CRB_ALERT_PARAMS = array(
		'filter_activity' => 0, // 0
		'filter_user'     => 0, // 1
		'begin'           => 0, // 2 - IPv4 long, synthetic key, value is derivative from filter_ip
		'end'             => 0, // 3 - IPv4 long, synthetic key, value is derivative from filter_ip
		'filter_ip'       => 0, // 4 - IP
		'filter_login'    => 0, // 5
		'search_activity' => 0, // 6
		'filter_role'     => 0, // 7
		'user_ids'        => 0, // 8
		// @since 8.9.6
		'search_url'      => 0, // 9
		'filter_status'   => 0, // 10

		// Non-event alert settings, see CRB_NON_EVENT_PARAMS

		'al_limit'           => 0, // 11 - limit on the number of alerts to send, number
		'al_count'           => 0, // 12 - counts the number of sent alerts, number
		'al_expires'         => 0, // 13 - do not send alerts after this date
		'al_ignore_rate'     => 0, // 14 - ignore global alert limits, flag
		// @since 8.9.7
		'al_send_emails'     => 0, // 15 - send via email, flag
		'al_send_pushbullet' => 0, // 16 - send via Pushbullet, flag
		// @since 9.4.2
		'al_send_me'         => 0, // 17 - send to the email of the user enabled the alert, user id
	);

	// Alert setting fields not related to event parameters

	public const CRB_NON_EVENT_PARAMS = array(
		'al_limit',
		'al_count',
		'al_expires',
		'al_ignore_rate',
		'al_send_emails',
		'al_send_pushbullet',
		'al_send_me',
	);

	/**
	 * Match the current activity event against the subscribed alert rules and
	 * send at most one notification per HTTP request.
	 *
	 * The method reads the alert subscriptions from the CRB_ALL_ALERTS site option,
	 * matches them against the event, sends the first matching alert through
	 * CRB_Messaging, and best-effort persists the updated rate-limit counter.
	 *
	 * @param array{activity:int,status:int,user_id:int,ac_by_user:int,ip:string,ip_long:int,login:string,url:string,country:string} $event Context of the logged activity event.
	 *
	 * @return void
	 */
	public static function dispatch( array $event ): void {

		$alert_list = self::get_alerts_list();

		if ( empty( $alert_list ) ) {
			return;
		}

		$activity = $event['activity'];
		$status = $event['status'];
		$user_id = $event['user_id'];
		$ac_by_user = $event['ac_by_user'];
		$ip = $event['ip'];
		$ip_long = $event['ip_long'];
		$login = $event['login'];
		$url = $event['url'];
		$country = $event['country'];

		$update_alerts = false;

		$alert_config_keys = array_keys( self::CRB_ALERT_PARAMS );

		foreach ( $alert_list as $hash => $alert_config ) {

			$updated = false;

			// Map the positional alert array onto its named fields (CRB_ALERT_PARAMS order).
			// Short legacy alerts are padded and overlong ones truncated to keep the mapping count-safe.

			$alert_values = array_slice( array_pad( array_values( $alert_config ), count( $alert_config_keys ), 0 ), 0, count( $alert_config_keys ) );
			$alert_named = array_combine( $alert_config_keys, $alert_values );

			// Check if all alert parameters match.
			// Not all parameters can exist as they saved in DB when a parameter may not be introduced yet.

			if ( ! empty( $alert_named['filter_status'] )
			     && $alert_named['filter_status'] != $status ) {
				continue;
			}

			if ( ! empty( $alert_named['filter_user'] )
			     && $alert_named['filter_user'] != $user_id
			     && $alert_named['filter_user'] != $ac_by_user ) {
				continue;
			}

			if ( ! empty( $alert_named['al_expires'] )
			     && ( $expires = absint( $alert_named['al_expires'] ) )
			     && $expires < time() ) {
				continue;
			}

			if ( ! empty( $alert_named['al_limit'] ) ) {
				if ( $alert_named['al_limit'] <= $alert_named['al_count'] ) {
					continue;
				}

				$alert_named['al_count'] ++;
				$updated = true;
			}

			if ( ! empty( $alert_named['filter_activity'] ) ) {
				if ( ! in_array( $activity, $alert_named['filter_activity'] ) ) {
					continue;
				}
			}

			if ( ! empty( $alert_named['end'] )
			     && ( $ip_long < $alert_named['begin'] || $alert_named['end'] < $ip_long ) ) {
				continue;
			}

			if ( ! empty( $alert_named['filter_ip'] )
			     && $alert_named['filter_ip'] != $ip ) {
				continue;
			}

			if ( ! empty( $alert_named['filter_login'] )
			     && $alert_named['filter_login'] != $login ) {
				continue;
			}

			if ( ! empty( $alert_named['search_url'] )
			     && false === strpos( $url, $alert_named['search_url'] ) ) {
				continue;
			}

			if ( ! empty( $alert_named['search_activity'] ) ) {
				$search_needle = $alert_named['search_activity'];
				$none = true;
				if ( false !== strpos( $ip, $search_needle ) ) {
					$none = false;
				}
				elseif ( false !== mb_stripos( $login, $search_needle ) ) {
					$none = false;
				}
				elseif ( $user_id && ( $event_user = crb_get_userdata( $user_id ) ) ) {
					if ( false !== mb_stripos( $event_user->user_firstname, $search_needle )
					     || false !== mb_stripos( $event_user->user_lastname, $search_needle )
					     || false !== mb_stripos( $event_user->nickname, $search_needle ) ) {
						$none = false;
					}
				}

				// No alert parameters match, continue to the next alert.

				if ( $none ) {
					continue;
				}
			}

			// Alert parameters match, prepare and send an alert email.

			$ac_lbl = crb_get_activity_label( $activity, $user_id, $ac_by_user, false );

			$status_lbl = '';

			if ( $status ) {
				$status_list = cerber_get_labels( 'status' ) + crb_get_lockout_reason();
				if ( $status_lbl = $status_list[ $status ] ?? '' ) {
					$status_lbl = ' (' . $status_lbl . ')';
				}
			}

			$msg = array();

			/* translators: %s is the activity label with optional status. */
			$msg[] = sprintf( __( 'Activity: %s', 'wp-cerber' ), $ac_lbl . $status_lbl );
			$msg_masked = $msg;

			$coname = $country ? ' (' . crb_get_country_name( $country ) . ')' : '';

			/* translators: %s is the IP address with optional country name. */
			$msg[] = sprintf( __( 'IP address: %s', 'wp-cerber' ), $ip . $coname );
			$msg_masked[] = sprintf( __( 'IP address: %s', 'wp-cerber' ), crb_mask_ip( $ip ) . $coname );

			if ( $user_id ) {
				$u = crb_get_userdata( $user_id );
				/* translators: %s is the user display name. */
				$msg[] = sprintf( __( 'User: %s', 'wp-cerber' ), $u->display_name );
				$msg_masked[] = sprintf( __( 'User: %s', 'wp-cerber' ), $u->display_name );
			}

			if ( $login ) {
				/* translators: %s is the username used for the login attempt. */
				$msg[] = sprintf( __( 'Username used: %s', 'wp-cerber' ), $login );
				$msg_masked[] = sprintf( __( 'Username used: %s', 'wp-cerber' ), crb_mask_login( $login ) );
			}

			if ( ! empty( $alert_named['search_activity'] ) ) {
				/* translators: %s is the search string. */
				$msg[] = sprintf( __( 'Search string: %s', 'wp-cerber' ), $alert_named['search_activity'] );
				$msg_masked[] = sprintf( __( 'Search string: %s', 'wp-cerber' ), $alert_named['search_activity'] );
			}

			// Make links to the Activity log page and to delete this alert.
			// $alert_named is keyed in CRB_ALERT_PARAMS order, so intersecting on the
			// filtering params yields each query parameter aligned with its correct value.

			$activity_params = array_intersect_key( $alert_named, CRB_Activity::EVENT_FILTERING_PARAMS );

			// Note that the links are escaped for safety, and may not be properly rendered in a plain email.

			/* translators: %s is the URL to view activity in the Dashboard. */
			$more = sprintf( __( 'View activity in the Dashboard: %s', 'wp-cerber' ), crb_admin_link_for_html( 'activity', $activity_params ) );
			/* translators: %s is the URL to delete the alert. */
			$more .= "\n\n" . sprintf( __( 'To delete the alert, click here: %s', 'wp-cerber' ), crb_admin_link_for_html( 'activity', [ 'unsubscribeme' => $hash ] ) );

			$ignore = $alert_named['al_ignore_rate'];
			$use_email = ! empty( $alert_named['al_send_emails'] ) || ! empty( $alert_named['al_send_me'] );
			$extra = array();

			if ( ! empty( $alert_named['al_send_me'] ) ) {
				$extra = array( 'user_list' => array( $alert_named['al_send_me'] ) );
			}

			$channels = array( 'email' => $use_email, 'pushbullet' => ! empty( $alert_named['al_send_pushbullet'] ) );

			// Crucial for old alerts (no channels configured at all)

			if ( ! array_filter( $channels ) ) {
				$channels = array(); // All channels are in use
			}

			$sent = CRB_Messaging::send( 'send_alert', array(
				'subj'        => $ac_lbl,
				'text'        => $msg,
				'text_masked' => $msg_masked,
				'more'        => $more,
				'ip'          => $ip
			), $channels, $ignore, $extra );

			if ( $sent && $updated ) {
				$update_alerts = true;
				// Write the incremented counter back in the original positional format.
				$alert_list[ $hash ] = array_values( $alert_named );
			}

			// Only one notification per event makes sense: guards if alert configurations are overlapping.
			// TODO: we should make it channel-aware and recipient-aware: if these settings are different we should allow sending more than one.
			break;
		}

		if ( $update_alerts ) {
			if ( ! update_site_option( CRB_ALL_ALERTS, $alert_list ) ) {
				cerber_error_log( 'Unable to update the list of alerts', 'ALERTS' );
			}
		}


	}

	/**
	 * Returns a list of alert parameters for the currently displaying admin page in a specific order.
	 * The keys are used to create an alert URL.
	 * Values are used to calculate an alert hash.
	 *
	 * @return array<string,mixed> The set of parameters
	 */
	public static function get_alert_params(): array {

		// A set of alert parameters
		// A strictly particular order due to further using numeric array indexes.

		$alert_params = self::CRB_ALERT_PARAMS;
		$query_params = crb_get_query_params();

		if ( ! array_intersect_key( $alert_params, $query_params ) ) {
			return $alert_params; // No parameters in the current request
		}

		// Fill the elements with actual data from the query

		// The IP address field is processing differently than other fields

		if ( ! empty( $query_params['filter_ip'] ) ) {
			$begin = 0;
			$end = 0;

			// Is it a IP range?
			$ip = cerber_any2range( $query_params['filter_ip'] );

			if ( is_array( $ip ) ) {
				$begin = $ip['begin'];
				$end = $ip['end'];
				$ip = 0;
			}
			elseif ( ! $ip ) {
				$ip = 0;
			}

			$alert_params['begin'] = $begin;
			$alert_params['end'] = $end;
			$alert_params['filter_ip'] = $ip;
		}

		// Getting values of the request fields (used as alert parameters) except IP related

		$temp_subset = $alert_params;
		unset( $temp_subset['begin'], $temp_subset['end'], $temp_subset['filter_ip'] );

		foreach ( array_keys( $temp_subset ) as $key ) {
			if ( ! empty( $query_params[ $key ] ) ) {
				if ( is_array( $query_params[ $key ] ) ) {
					$alert_params[ $key ] = array_map( 'trim', $query_params[ $key ] );
				}
				else {
					$alert_params[ $key ] = trim( $query_params[ $key ] );
				}
			}
			else {
				$alert_params[ $key ] = 0;
			}
		}

		// Preparing/sanitizing values of the alert parameters

		if ( ! empty( $alert_params['al_expires'] ) ) {
			$time = 24 * 3600 + strtotime( 'midnight ' . $alert_params['al_expires'] );
			$alert_params['al_expires'] = $time - get_option( 'gmt_offset' ) * 3600;
		}

		$integer_fields = array( 'al_limit', 'al_ignore_rate', 'al_send_emails', 'al_send_pushbullet', 'al_send_me', );

		foreach ( $integer_fields as $int_field ) {
			$alert_params[ $int_field ] = crb_absint( $alert_params[ $int_field ] );
		}

		if ( ! is_array( $alert_params['filter_activity'] ) ) {
			$alert_params['filter_activity'] = array( $alert_params['filter_activity'] );
		}
		$alert_params['filter_activity'] = array_filter( $alert_params['filter_activity'] );

		// Basic XSS sanitization

		array_walk_recursive( $alert_params, function ( &$item ) {
			$item = str_replace( array( '<', '>', '[', ']', '"', "'" ), '', $item );
		} );

		return $alert_params;
	}

	/**
	 * Creates an activity alert from the current admin request parameters.
	 *
	 * Skips creation and shows a notice if an identical alert already exists.
	 *
	 * @return void
	 */
	public static function create_alert() {
		$params = self::get_alert_params();

		if ( self::alert_exists( $params ) ) {
			cerber_admin_notice( 'An alert with given parameters exists.' );

			return;
		}

		$alerts = self::get_alerts_list();
		$alerts[ self::generate_alert_id( $params ) ] = array_values( $params );

		self::save_alerts( $alerts, __( 'The alert has been created', 'wp-cerber' ) );
	}

	/**
	 * Deletes an activity alert by its hash ID.
	 *
	 * @param string $hash Hash ID of the alert. When empty, it is derived from the current request parameters.
	 *
	 * @return void
	 */
	public static function delete_alert( string $hash = '' ) {
		if ( ! $hash ) {
			$hash = self::generate_alert_id( self::get_alert_params() );
		}

		$alerts = self::get_alerts_list();

		if ( ! isset( $alerts[ $hash ] ) ) {
			crb_add_admin_error( 'No alert with the given ID found' );

			return;
		}

		unset( $alerts[ $hash ] );

		self::save_alerts( $alerts, __( 'The alert has been deleted', 'wp-cerber' ) );
	}

	/**
	 * Returns the stored list of activity alerts.
	 *
	 * @return array<string,array> Map of alert hash to its stored values.
	 */
	static function get_alerts_list(): array {
		$alerts = cerber_get_site_option( CRB_ALL_ALERTS );

		return ( $alerts && is_array( $alerts ) ) ? $alerts : array();
	}

	/**
	 * Persists the list of activity alerts and reports the result to the admin.
	 *
	 * @param array<string,array> $alerts Full list of alerts to store.
	 * @param string $success_msg Message shown on a successful update.
	 *
	 * @return bool
	 */
	private static function save_alerts( array $alerts, string $success_msg = '' ): bool {
		if ( update_site_option( CRB_ALL_ALERTS, $alerts ) ) {
			cerber_admin_message( $success_msg );

			return true;
		}
		else {
			crb_add_admin_error( 'Unable to update alerts!' );

			return false;
		}
	}

	/**
	 * Convert old alert format: adding new parameters to existing alerts and updating their hashes.
	 *
	 * @return bool
	 *
	 * @since 8.9.5.5
	 */
	public static function migrate(): bool {
		if ( ! $alerts = self::get_alerts_list() ) {
			return true;
		}

		$test = current( $alerts );

		if ( isset( $test[9] ) ) {
			return true;
		}

		// Old structure needs to be converted

		$new_format = array();

		foreach ( $alerts as $alert ) {
			$alert[9] = 0;
			$alert[10] = 0;
			$new_format[ self::generate_alert_id( $alert ) ] = $alert;
		}

		if ( ! self::save_alerts( $new_format ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete expired alerts.
	 *
	 * @return bool True on success or if no alerts exist, false on save failure.
	 */
	public static function delete_expired(): bool {
		if ( ! $alerts = self::get_alerts_list() ) {
			return true;
		}

		$has_changes = false;

		foreach ( $alerts as $hash => $alert ) {
			if ( ! self::is_alert_expired( $alert ) ) {
				continue;
			}

			unset( $alerts[ $hash ] );
			$has_changes = true;
		}

		if ( ! $has_changes ) {
			return true;
		}

		if ( ! self::save_alerts( $alerts ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the given alert has expired.
	 *
	 * @param array $alert The alert data array.
	 *
	 * @return bool True if expired, false otherwise.
	 *
	 * @since 8.9.5.4
	 */
	public static function is_alert_expired( array $alert ): bool {
		// Check if the threshold count has reached or exceeded the maximum allowed limit.
		if ( ! empty( $alert[11] ) && ! empty( $alert[12] ) && $alert[11] <= $alert[12] ) {
			return true;
		}

		// Check if the explicit expiration timestamp has passed.
		if ( ! empty( $alert[13] ) && $alert[13] < time() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if an alert with the given parameters exists.
	 *
	 * @param array $params Optional alert parameters. When empty, derived from the current request.
	 *
	 * @return bool
	 *
	 * @since 8.9.5.6
	 */
	public static function alert_exists( array $params = array() ): bool {

		$alerts = self::get_alerts_list();

		if ( ! $alerts ) {
			return false;
		}

		if ( ! $params ) {
			$params = self::get_alert_params();
		}

		$hash = self::generate_alert_id( $params );

		if ( isset( $alerts[ $hash ] )
		     && ! self::is_alert_expired( $alerts[ $hash ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Generates an alert ID based on its filtering parameters
	 *
	 * @param array $alert_params
	 *
	 * @return string 40-character hexadecimal number used as ID
	 *
	 * @since 8.9.6
	 */
	public static function generate_alert_id( array $alert_params ): string {
		return sha1( json_encode( array_values( array_diff_key( $alert_params, array_flip( CRB_Activity_Alerts::CRB_NON_EVENT_PARAMS ) ) ) ) );
	}

}
