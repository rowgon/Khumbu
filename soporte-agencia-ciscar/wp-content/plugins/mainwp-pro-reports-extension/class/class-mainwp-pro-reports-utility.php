<?php

class MainWP_Pro_Reports_Utility {

	private static $instance = null;

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// contructor.
	}

	public static function get_timestamp( $timestamp ) {
		$gmtOffset = get_option( 'gmt_offset' );

		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

	public static function format_timestamp( $timestamp, $gmt = false ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp, $gmt );
	}

	public static function format_datestamp( $timestamp, $gmt = false ) {
		return date_i18n( get_option( 'date_format' ), $timestamp, $gmt );
	}

	public static function format_date( $timestamp ) {
		return date_i18n( get_option( 'date_format' ), $timestamp );
	}

	static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	public static function map_site( &$website, $keys ) {
		$outputSite = array();
		foreach ( $keys as $key ) {
			$outputSite[ $key ] = $website->$key;
		}
		return $outputSite;
	}

	/**
	 * Method map_fields()
	 *
	 * Map Site.
	 *
	 * @param mixed $website Website to map.
	 * @param mixed $keys Keys to map.
	 * @param bool  $object_output Output format array|object.
	 *
	 * @return object $outputSite Mapped site.
	 */
    public static function map_fields( &$website, $keys, $object_output = false ) { //phpcs:ignore -- NOSONAR - complex.
		$outputSite = array();
		if ( ! empty( $website ) ) {
			if ( is_object( $website ) ) {
				foreach ( $keys as $key ) {
					if ( property_exists( $website, $key ) ) {
						$outputSite[ $key ] = $website->$key;
					}
				}
			} elseif ( is_array( $website ) ) {
				foreach ( $keys as $key ) {
					$outputSite[ $key ] = $website[ $key ];
				}
			}
		}

		if ( $object_output ) {
			return (object) $outputSite;
		} else {
			return $outputSite;
		}
	}


	static function sec2hms( $sec, $padHours = false ) {

		// start with a blank string
		$hms = '';

		// do the hours first: there are 3600 seconds in an hour, so if we divide
		// the total number of seconds by 3600 and throw away the remainder, we're
		// left with the number of hours in those seconds
		$hours = intval( intval( $sec ) / 3600 );

		// add hours to $hms (with a leading 0 if asked for)
		$hms .= ( $padHours ) ? str_pad( $hours, 2, '0', STR_PAD_LEFT ) . ':' : $hours . ':';

		// dividing the total seconds by 60 will give us the number of minutes
		// in total, but we're interested in *minutes past the hour* and to get
		// this, we have to divide by 60 again and then use the remainder
		$minutes = intval( ( $sec / 60 ) % 60 );

		// add minutes to $hms (with a leading 0 if needed)
		$hms .= str_pad( $minutes, 2, '0', STR_PAD_LEFT ) . ':';

		// seconds past the minute are found by dividing the total number of seconds
		// by 60 and using the remainder
		$seconds = intval( $sec % 60 );

		// add seconds to $hms (with a leading 0 if needed)
		$hms .= str_pad( $seconds, 2, '0', STR_PAD_LEFT );

		// done!
		return $hms;
	}


	public static function esc_content( $content, $type = '' ) {
		if ( $type == 'note' ) {

			$allowed_html = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'p'      => array(),
				'hr'     => array(),
				'ul'     => array(),
				'ol'     => array(),
				'li'     => array(),
				'h1'     => array(),
				'h2'     => array(),
			);

			$content = wp_kses( $content, $allowed_html );

		} else {
			$content = stripslashes( $content );
			$content = wp_kses_post( wpautop( wptexturize( $content ) ) );
		}

		return $content;
	}




	/**
	 * Get Websites
	 *
	 * Gets all child sites through the 'mainwp_getsites' filter.
	 *
	 * @param array|null $site_id  Child sites ID.
	 *
	 * @return array Child sites array.
	 */
	public static function get_websites( $site_id = null ) {
		global $mainWPProReportsExtensionActivator;
		return apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id, false );
	}

	/**
	 * Get Websites.
	 *
	 * Gets all child sites through the 'mainwp_getsites' filter.
	 *
	 * @param array $site_ids  Child sites IDs.
	 * @param array $group_ids Groups IDs.
	 *
	 * @return array Child sites array.
	 */
	public static function get_db_websites( $site_ids, $group_ids = array(), $client_ids = array() ) {
		global $mainWPProReportsExtensionActivator;

		if ( ! is_array( $site_ids ) ) {
			$site_ids = array();
		}

		if ( ! is_array( $group_ids ) ) {
			$group_ids = array();
		}

		if ( ! is_array( $client_ids ) ) {
			$client_ids = array();
		}

		if ( ! empty( $site_ids ) || ! empty( $group_ids ) || ! empty( $client_ids ) ) {
			$params = array(
				'sites'   => $site_ids,
				'groups'  => $group_ids,
				'clients' => $client_ids,
			);
			return apply_filters( 'mainwp_get_db_websites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $params );
		}
		return false;
	}


	/**
	 * Method get_attachment_url().
	 */
	public static function get_attachment_url( $att_id, $encode_url = false ) {
		$url = wp_get_attachment_url( $att_id );
		return $encode_url ? rawurlencode( $url ) : $url;
	}


	/**
	 * Method get_included_image_url().
	 */
	public static function get_included_image_url( $image, $encode_url = false ) {
		$url = MAINWP_PRO_REPORTS_PLUGIN_URL . 'images/' . $image;
		return $encode_url ? rawurlencode( $url ) : $url;
	}

	/**
	 * Method may_decode_unSerialize().
	 *
	 * May decode and unserialize value.
	 *
	 * @param string $value string value.
	 *
	 * @return array array of values.
	 */
	public static function may_decode_unSerialize( $value ) {
		if ( ! empty( $value ) ) {

			// to compatible.
			$tmp = base64_decode( $value );
			if ( maybe_unserialize( $tmp ) ) {
				$tmp = @unserialize( $tmp ); // to fix  E_NOTICE in case FALSE.
			}
			// end.

			if ( ! is_array( $tmp ) ) {
				$tmp = json_decode( $value, true );
			}

			if ( is_array( $tmp ) ) {
				return $tmp;
			}
		}
		return array();
	}

	/**
	 * Debugging log info.
	 *
	 * Sets logging for debugging purpose.
	 *
	 * @param string $message Log info message.
	 */
	public static function log_info( $message ) {
		self::log_debug( $message, 2 );
	}

	/**
	 * Debugging log.
	 *
	 * Sets logging for debugging purpose.
	 *
	 * @param string $message Log debug message.
	 */
	public static function log_debug( $message, $type = false ) {
		$cron = '';
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$cron = 'CRON :: ';
		}
		// Set color: 0 - LOG, 1 - WARNING, 2 - INFO, 3- DEBUG.
		$log_color = 3;
		if ( false !== $type ) {
			$log_color = intval( $type );
			if ( ! in_array( $log_color, array( 0, 1, 2, 3 ) ) ) {
				$log_color = 2;
			}
		}
		$log = $cron . $message;
		do_action( 'mainwp_log_action', 'Pro Reports :: ' . $log, MAINWP_PRO_REPORTS_LOG_PRIORITY, $log_color );
	}
}
