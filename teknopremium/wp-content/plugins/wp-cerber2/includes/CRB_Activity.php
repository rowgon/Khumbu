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
 * Owns the application-level Activity Log boundary.
 *
 * This class is the central service for recording security-relevant activity
 * events, querying them for administrative screens, and streaming them for export.
 * It keeps activity-log concerns behind one stable service boundary so callers do
 * not need to know the log table shape, query filter semantics, cache invalidation
 * rules, or export query strategy. Nearby components provide the lower-level work:
 * DB Warp executes the database operations, CRB_Activity_Alerts reacts to newly
 * recorded events, and the administrative UI consumes the filtered reads and export
 * streams produced here.
 *
 * Its expected side effects are deliberate and bounded to the activity subsystem:
 * writing and deleting activity rows, refreshing log-status cache metadata,
 * dispatching configured activity alerts, and forwarding selected events to the Lab
 * integration.
 *
 * @since 9.6.6.10
 */
final class CRB_Activity {
	/**
	 * Activity IDs logged during the current HTTP request.
	 *
	 * @var array<int,int>
	 */
	private static array $logged = array();

	/**
	 * Activity and user logged during the current HTTP request for log deduplication.
	 *
	 * @var array<string,bool>
	 */
	private static array $dedup = array();

	/**
	 * The list of activities not to log
	 *
	 * @var array<int>
	 */
	private static array $ignore = array();

	// HTTP query string parameters with mapping
	// Parameter name => DB table field, if direct mapping is applicable

	const EVENT_FILTERING_PARAMS = array(
		'filter_activity'   => 'activity',
		'filter_status'     => 'ac_status',
		'filter_set'        => false,
		'filter_ip'         => 'ip',
		'filter_login'      => 'user_login',
		'filter_user'       => 'user_id',
		'search_activity'   => false,
		'filter_sid'        => 'session_id',
		'search_url'        => false,
		'filter_country'    => 'country',
		'filter_time_begin' => false,
		'filter_time_end'   => false,
	);

	/**
	 * Storage key for caching database table status.
	 *
	 */
	const LOG_TABLE_STATUS = 'activity_table_status';

	/**
	 * Get records from the activity log using specified conditions
	 *
	 * @param array $activity List of activity IDs.
	 * @param array $user User parameters ('email' or 'id').
	 * @param array $order Order parameters ('DESC' => 'column_name' or 'ASC' => 'column_name').
	 * @param string $limit SQL LIMIT clause. Example: '10' or '0, 20'
	 *
	 * @return object[] Array of log record objects. An empty array if no records are found.
	 */
	public static function get_log( array $activity = [], array $user = [], $order = [], $limit = '' ): array {

		// Resolve the optional user filter to a numeric ID before touching the database.
		$user_id = 0;

		if ( ! empty( $user['email'] ) ) {
			$user_obj = get_user_by( 'email', $user['email'] );

			if ( ! $user_obj ) {
				return array();
			}

			$user_id = absint( $user_obj->ID );
		}
		elseif ( ! empty( $user['id'] ) ) {
			$user_id = absint( $user['id'] );
		}

		// Build and run the read through the centralized query builder.
		$db_result = warp_get_db();

		if ( $db_result->has_errors() ) {
			return array();
		}

		/** @var CRB_Database $db */
		$db = $db_result->get_results();
		$qb = $db->table( CERBER_LOG_TABLE );

		if ( $activity ) {
			$qb->where( 'activity', 'IN', array_map( 'absint', $activity ) );
		}

		if ( $user_id ) {
			$qb->where( 'user_id', '=', $user_id );
		}

		// Ordering, restricted to the supported DESC/ASC directions.
		if ( $order ) {
			$order_key = key( $order );
			$order_column = crb_sanitize_id( current( $order ) );

			// Sanitize the column so an unusable value degrades to "no ordering"
			// instead of turning the whole read into a builder error that would
			// zero the result set.
			if ( in_array( $order_key, [ 'DESC', 'ASC' ], true ) && $order_column !== '' ) {
				$qb->order_by( $order_column, $order_key );
			}
		}

		// Pagination expressed as 'count' or 'offset,count'.
		if ( $limit ) {
			$clean = preg_replace( '/[^0-9,]/', '', (string) $limit );

			if ( strpos( $clean, ',' ) !== false ) {
				list( $offset_part, $count_part ) = explode( ',', $clean, 2 );
				$qb->limit( (int) $count_part )->offset( (int) $offset_part );
			}
			elseif ( $clean !== '' ) {
				$qb->limit( (int) $clean );
			}
		}

		$result = $qb->get_query_results( CRB_Database::FETCH_OBJECTS );

		if ( $result->has_errors() ) {
			return array();
		}

		return $result->get_results( array() ) ?: array();
	}

	/**
	 * Log an activity event into the activity log.
	 *
	 * @param int $activity Activity ID.
	 * @param string $login Login used or any additional information. Empty by default.
	 * @param int $user_id User ID; defaults to the current user when zero.
	 * @param int $status Activity status; resolved automatically when zero.
	 * @param string $ip Remote IP address; auto-detected when empty.
	 *
	 * @return bool True when the event row was written. False when the event was
	 *              skipped (duplicate or ignored), the IP address is invalid, or
	 *              the database write failed.
	 *
	 * @since 3.0
	 */
	public static function log( int $activity, string $login = '', int $user_id = 0, int $status = 0, string $ip = '' ): bool {
		global $user_ID;

		$activity = absint( $activity );

		if ( empty( $user_id ) ) {
			$user_id = ( $user_ID ) ?: 0;
		}

		$user_id = absint( $user_id );

		$key = $activity . '-' . $user_id;

		if ( ( isset( self::$dedup[ $key ] )
		       || isset( self::$ignore[ $activity ] ) )
		     && ! defined( 'CRB_ALLOW_MULTIPLE' ) ) {
			return false;
		}

		self::$dedup[ $key ] = true;
		self::$logged[ $activity ] = $activity;

		// Assemble and normalize the event; an empty result signals an invalid IP

		$event = self::build_event( $activity, $login, $user_id, $status, $ip );

		if ( ! $event ) {
			return false;
		}

		$log_row = $event['row'];

		// Persist the event to the activity log

		$ret = self::persist_event( $log_row );

		// If alerts exist, lazy-load the alert class and dispatch a notification

		if ( cerber_get_site_option( CRB_ALL_ALERTS ) ) {
			CRB_Activity_Alerts::dispatch( array(
				'activity'   => $log_row['activity'],
				'status'     => $log_row['ac_status'],
				'user_id'    => $log_row['user_id'],
				'ac_by_user' => $log_row['ac_by_user'],
				'ip'         => $log_row['ip'],
				'ip_long'    => $log_row['ip_long'],
				'login'      => $log_row['user_login'],
				'url'        => $event['url'],
				'country'    => $log_row['country'],
			) );
		}

		// Lab --------------------------------------------------------------------

		if ( in_array( $log_row['activity'], array( CRB_EV_CMS, CRB_EV_SCD, CRB_EV_SFD, 40, CRB_EV_PUR, CRB_EV_LDN, 55, 56, 71 ) ) ) {
			lab_save_push( $log_row['ip'], $log_row['activity'] );
		}

		return $ret;
	}

	/**
	 * Assemble and normalize a single activity event from the raw log arguments.
	 *
	 * Resolves and validates the IP address, derives all event fields (IP long,
	 * IPv6 /64 network, timestamp, request URL, status, country, session ID and
	 * the control details string) and packs the database row. The method has no
	 * side effects: its result is consumed by persist_event() and the alert
	 * dispatcher.
	 *
	 * @param int $activity Activity ID, already normalized by the caller.
	 * @param string $login Login used or any additional information.
	 * @param int $user_id User ID, already normalized by the caller.
	 * @param int $status Activity status; resolved here when zero.
	 * @param string $ip Remote IP address; auto-detected when empty.
	 *
	 * @return array{row:array<string,mixed>,url:string}|array{} The assembled event, or an empty array when the supplied IP address is invalid.
	 */
	private static function build_event( int $activity, string $login, int $user_id, int $status, string $ip ): array {
		$wp_cerber = get_wp_cerber();

		// Resolve the IP address: auto-detect when empty, reject when malformed

		if ( empty( $ip ) ) {
			$ip = cerber_get_remote_ip();
		}
		elseif ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return array();
		}

		if ( cerber_is_ipv4( $ip ) ) {
			$ip_long = ip2long( $ip );
		}
		else {
			$ip_long = 1;
		}

		$ipv6_net_64 = crb_pack_ipv6_net64( $ip );

		$stamp = microtime( true );

		// Build the request URL from the host and the path without the query string

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$query_position = strpos( $request_uri, '?' );
		$request_path = ( $query_position !== false )
			? substr( $request_uri, 0, $query_position )
			: $request_uri;

		// Remove non-ASCII = robustness
		$request_path = preg_replace( '/[^\x20-\x7E]/', '', (string) $request_path );

		$http_host = $_SERVER['HTTP_HOST'] ?? '';
		$http_host = preg_replace( '/[^a-z0-9.\-:\[\]]/i', '', (string) $http_host );

		$url = $http_host . $request_path;

		// Resolve the status when the caller did not provide one

		if ( ! $status ) {
			if ( $activity != CRB_EV_IPL && $activity != CRB_EV_NEL ) {
				$status = cerber_get_status( $ip, $activity );
			}
			elseif ( CRB_Globals::$blocked ) {
				$status = CRB_Globals::$blocked;
			}
		}

		$ac_bot = absint( CRB_Globals::$bot_status );
		$ac_by_user = absint( CRB_Globals::$user_id );

		$ctrl_txt = '';

		if ( $ctrl = CRB_Globals::get_ctrl_settings() ) {
			$ctrl_txt = implode( ',', $ctrl ); // @since 9.6.1.1
		}

		$details = $ctrl_txt . '|0|0|0|' . $url;
		$details = substr( $details, 0, 250 );

		$country = (string) lab_get_country( $ip );
		$status = absint( $status ); // Note: @since 8.9.4 $status is stored in a separate "ac_status" column
		$session_id = $wp_cerber->getRequestID();

		$log_row = array(
			'ip'          => $ip,
			'ip_long'     => $ip_long,
			'ipv6_net_64' => $ipv6_net_64,
			'user_login'  => $login,
			'user_id'     => $user_id,
			'stamp'       => $stamp,
			'activity'    => $activity,
			'session_id'  => $session_id,
			'country'     => $country,
			'details'     => $details,
			'ac_status'   => $status,
			'ac_bot'      => $ac_bot,
			'ac_by_user'  => $ac_by_user,
		);

		return array( 'row' => $log_row, 'url' => $url );
	}

	/**
	 * Insert an assembled event row into the activity log table.
	 *
	 * Performs the database write through the DB Warp layer (parameterized
	 * insert) and refreshes the activity status cache on success. The supplied
	 * row is trusted as already normalized by build_event().
	 *
	 * @param array<string,mixed> $log_row Database row keyed by column name.
	 *
	 * @return bool True when the row was written, false on a database error.
	 */
	private static function persist_event( array $log_row ): bool {
		$db_result = warp_get_db();
		$write = $db_result->has_errors()
			? $db_result
			: $db_result->get_results()->table( CERBER_LOG_TABLE )->insert( $log_row );

		if ( $write->has_errors() ) {
			cerber_watchdog();

			return false;
		}

		self::set_status( array( 'data_modified' => $log_row['stamp'], 'hash' => sha1( $log_row['ip'] . $log_row['stamp'] . $log_row['session_id'] ) ) );

		return true;
	}

	/**
	 * Do not log this activity
	 *
	 * @param int $activity Activity ID
	 *
	 * @return void
	 */
	public static function set_ignore( int $activity ) {
		self::$ignore[ $activity ] = true;
	}

	/**
	 * Check if the given activities were logged during the current HTTP request
	 *
	 * @param int|int[] $activity ID of activities to check for.
	 *
	 * @return boolean
	 *
	 * @since 9.5.8
	 */
	public static function is_logged( $activity ) {
		if ( ! self::$logged ) {
			return false;
		}

		if ( ! is_array( $activity ) ) {
			$activity = array( $activity );
		}

		return ! empty( array_intersect( array_map( 'absint', $activity ), self::$logged ) );
	}

	/**
	 * Returns the list of activities logged during the current HTTP request
	 *
	 * @return array
	 *
	 * @since 9.6.6.10
	 */
	static function get_logged(): array {
		return self::$logged;
	}

	/**
	 * Is log empty?
	 *
	 * @return bool True if the activity log is empty (nothing is logged)
	 *
	 * @since 9.6.6.11
	 */
	static function is_empty(): bool {
		if ( self::get_status() ) {
			return false;
		}

		// cerber_db_is_empty()

		$db_result = warp_get_db();
		$probe = $db_result->has_errors()
			? $db_result
			: $db_result->get_results()->table( CERBER_LOG_TABLE )->select( 'ip' )->limit( 1 )->get_value();

		// It is safer to treat the log as non-empty if a DB error occurs.
		if ( $probe->get_results() || $probe->has_errors() ) {
			self::set_status( [ 'non_empty' => time() ] );

			return false;
		}

		//self::set_status( [ 'non_empty' => 0 ] );

		return true;
	}

	/**
	 * Get global log status data
	 *
	 * @return array
	 *
	 * @since 9.6.6.11
	 */
	private static function get_status(): array {
		$status = cerber_cache_get( self::LOG_TABLE_STATUS );

		if ( ! $status
		     || ! is_array( $status ) ) {
			$status = array();
		}

		return $status;
	}

	/**
	 * Set global log status data
	 *
	 * @param array $data Properties to set: ['name' => value]
	 *
	 * @return bool
	 *
	 * @since 9.6.6.11
	 */
	private static function set_status( array $data ): bool {

		$status = array_merge( self::get_status(), $data );

		return cerber_cache_set( self::LOG_TABLE_STATUS, $status );
	}

	/**
	 * Reset activity log status information
	 *
	 * @return bool
	 *
	 * @since 9.6.6.12
	 */
	static function clear_status(): bool {
		return cerber_cache_set( self::LOG_TABLE_STATUS, [] );
	}

	/**
	 * Checks if the activity log has been modified since the last log update info stored in $status.
	 *
	 * @param array $status Data returned by self::get_status()
	 *
	 * @return bool True if the log has been modified since the last log update info stored in $status
	 *
	 * @see self::get_status() Use to save the status of the activity log for further comparing.
	 *
	 * @since 9.6.6.11
	 */
	static function is_modified( array $status ): bool {
		$current = self::get_status();

		if ( empty( $current['data_modified'] )
		     || empty( $status['data_modified'] ) ) {
			return true;
		}

		return ( $current['data_modified'] > $status['data_modified'] );
	}

	/**
	 * Checks if the activity log table has been modified since the specified time.
	 * It can be used for cache invalidation.
	 *
	 * @param float|int $stamp Unix timestamp
	 *
	 * @return bool True if modified
	 *
	 * @since 9.6.6.11
	 */
	static function is_modified_since( $stamp ): bool {
		if ( ! $status = self::get_status() ) {
			return true;
		}

		return ( $stamp < ( $status['data_modified'] ?? PHP_INT_MAX ) );
	}

	/**
	 * Retrieves log rows as an array of objects, using data from cache
	 * if the log was not updated from the previous invocation
	 *
	 * If the data is not in the cache, or if the cached data is outdated (based on the global log's
	 * modification status), the builder executes the query against the database, and
	 * the results are cached.
	 *
	 * @param CRB_Query_Builder $qb Configured query builder that compiles and executes the activity log read.
	 * @param int|null &$num The number of rows in the resulting dataset. Passed by reference.
	 *
	 * @return array<object> An array of objects representing the log rows.
	 *                            An empty array if no rows are found or a database error occurs.
	 *
	 * @since 9.6.6.11
	 */
	private static function get_rows( CRB_Query_Builder $qb, ?int &$num = null ): array {

		$calculate_count = func_num_args() > 1; // We have to distinct calls with and without parameter $num

		$key = 'ac_cache_' . sha1( $qb->to_sql() . '/' . ( $calculate_count ? 'A' : 'B' ) );

		if ( ! ( $cache = cerber_cache_get( $key ) )
		     || self::is_modified( $cache['status'] ?? [] ) ) {

			$cache = array();

			// Execute the read through the builder's own terminal methods. The builder owns its
			// CRB_Database connection, so query construction and execution stay on one layer.

			$rows = array();
			$found_rows = 0;
			$query_result = $qb->get_query_results( CRB_Database::FETCH_OBJECTS );

			if ( ! $query_result->has_errors() ) {
				$rows = $query_result->get_results( array() );

				// FOUND_ROWS() reports the total recorded by the SQL_CALC_FOUND_ROWS SELECT that
				// get_query_results() just ran on the same connection. Read it through the builder's
				// companion only when the caller asked for the total and the query returned rows. A
				// count failure is non-critical and degrades to zero, matching the previous behavior.

				if ( $rows && $calculate_count ) {
					$found_result = $qb->get_found_rows();

					if ( ! $found_result->has_errors() ) {
						$found_rows = (int) $found_result->get_results( 0 );
					}
				}
			}

			// Preserve the previous contract: an empty result set (no match or a database
			// error) reports a zero total, a populated result without a count request reports
			// false, otherwise the FOUND_ROWS() total.

			if ( ! $rows ) {
				$cache['rows'] = array();
				$cache['num'] = 0;
			}
			else {
				$cache['rows'] = $rows;
				$cache['num'] = $calculate_count ? $found_rows : false;
			}

			$cache['status'] = self::get_status();

			cerber_cache_set( $key, $cache, 24 * 3600 );
		}

		$num = $cache['num'];

		return $cache['rows'];
	}

	/**
	 * Retrieves activity log rows using the request filters.
	 *
	 * Builds the SQL internally from the given arguments and executes it through the
	 * cached reader.
	 *
	 * On success the payload carries:
	 *  - 'rows'    an array of raw activity log row objects (stdClass); empty when nothing matches;
	 *  - 'total'   the total number of matching rows for pagination (SQL_CALC_FOUND_ROWS), or 0 when
	 *              pagination navigation is not required;
	 *  - 'filters' the echo-back filter values for rendering the filter form.
	 *
	 * @param array $args Optional arguments to use instead of the HTTP query string.
	 *
	 * @return Revalt<array{rows:array<object>,total:int,filters:array{per_page:int,falist:array<int>,ip:string,login:string,user_id:int,search:string,sid:string,in_url:string,country:string,time_begin:int,time_end:int}}>
	 *         Success carries the read payload described above. On failure the payload is absent and
	 *         the lower-layer Revalt is chained for root-cause diagnostics.
	 *
	 * @since 9.8.1
	 */
	public static function fetch( array $args = array() ): Revalt {

		$parsed = self::parse_query( $args );

		// A failed build aborts before any read. The parser Revalt is chained for diagnostics so the
		// caller can render the filter form from its own default-shaped values.

		if ( $parsed->has_errors() ) {
			return new Revalt( null, 'activity_fetch_query_build_failed', 'Activity fetch aborted: unable to build the activity query', null, $parsed );
		}

		$built = $parsed->get_results();

		$query_builder = $built['builder'];

		// Calculate the total count only when pagination navigation is required.

		$total = 0;

		if ( ! empty( $built['calc_on'] ) ) {
			$rows = self::get_rows( $query_builder, $total );
		}
		else {
			$rows = self::get_rows( $query_builder );
		}

		return new Revalt( array(
			'rows'    => $rows,
			'total'   => $total,
			'filters' => $built['filters'],
		) );
	}

	/**
	 * Builds the activity export query and opens an unbuffered row stream for CSV export.
	 * The stream is a generator that yields one raw activity row object (stdClass) per iteration.
	 *
	 * Resolves the export metadata eagerly while the connection is still free: the header total
	 * runs as a separate buffered COUNT query and the date range as a buffered MIN/MAX query,
	 * because the unbuffered row stream locks the connection while it is consumed.
	 *
	 * On success the payload carries:
	 *  - 'stream'  a Generator yielding raw activity row objects, never wrapped into Revalt;
	 *  - 'total'   the total number of matching rows for the header (0 when the count is unavailable);
	 *  - 'filters' the echo-back filter values for the export header;
	 *  - 'range'   array{oldest:int,newest:int} of the oldest and newest event timestamps, or an
	 *              empty array when no rows match or the range query is unavailable.
	 *
	 * The returned stream owns the database connection until it is fully consumed or destroyed:
	 * iterating it to completion and stopping early both release the unbuffered result. The caller
	 * must iterate it (even an empty export) so the connection is unlocked.
	 *
	 * @param array $args Optional arguments to use instead of the HTTP query string.
	 *
	 * @return Revalt<array{stream:\Generator,total:int,filters:array{per_page:int,falist:array<int>,ip:string,login:string,user_id:int,search:string,sid:string,in_url:string,country:string,time_begin:int,time_end:int},range:array{}|array{oldest:int,newest:int}}>
	 *         Success carries the export payload described above.
	 *
	 * @since 9.8.1
	 */
	public static function stream_log( array $args = array() ): Revalt {

		// Export uses the dedicated query variant: full projection, no LIMIT, and companion
		// COUNT and MIN/MAX queries for the header total and date range.

		$parsed = self::parse_query( $args, true );

		// A failed build aborts before any query runs. The parser Revalt is chained for diagnostics.

		if ( $parsed->has_errors() ) {
			return new Revalt( null, 'activity_export_query_build_failed', 'Activity export aborted: unable to build the export query', null, $parsed );
		}

		$built = $parsed->get_results();

		$db_result = warp_get_db();

		if ( $db_result->has_errors() ) {
			return new Revalt( null, 'activity_export_db_unavailable', 'Activity export aborted: database layer unavailable', null, $db_result );
		}

		/** @var CRB_Database $db */
		$db = $db_result->get_results();

		// Resolve the header total first, while the connection is still free. A count failure
		// is non-critical: the header simply reports zero rather than aborting the export.

		$total = 0;
		$count_result = $db->query( $built['count_sql'], CRB_Database::FETCH_OBJECTS );

		if ( ! $count_result->has_errors() ) {
			$count_rows = $count_result->get_results( array() );
			$total = isset( $count_rows[0] ) ? (int) $count_rows[0]->total : 0;
		}

		// Resolve the date range while the connection is still free. Failures and zero-row
		// exports (MIN/MAX return NULL) are non-critical: the header simply omits the range.

		$range = array();

		if ( $built['range_sql'] !== '' ) {
			$range_result = $db->query( $built['range_sql'], CRB_Database::FETCH_OBJECTS );

			if ( ! $range_result->has_errors() ) {
				$range_rows = $range_result->get_results( array() );

				if ( isset( $range_rows[0] ) && $range_rows[0]->oldest !== null && $range_rows[0]->newest !== null ) {
					$range = array(
						'oldest' => (int) $range_rows[0]->oldest,
						'newest' => (int) $range_rows[0]->newest,
					);
				}
			}
		}

		// Open the unbuffered row stream. It owns the connection until fully consumed or closed.
		// A stream failure is critical: abort with a chained error so the caller surfaces it before
		// any CSV output, instead of emitting a misleading empty export.

		$stream_result = $db->query_stream( $built['sql'], CRB_Database::FETCH_OBJECTS );

		if ( $stream_result->has_errors() ) {
			return new Revalt( null, 'activity_export_stream_failed', 'Activity export aborted: unable to open the row stream', null, $stream_result );
		}

		// Wrap the unbuffered result in a generator that releases the connection when the consumer
		// reaches the last row or stops early. Raw row objects are yielded as-is.

		$row_stream = ( static function () use ( $stream_result ): \Generator {

			$row_iterator = $stream_result->get_results();

			try {
				foreach ( $row_iterator as $activity_row ) {
					yield $activity_row;
				}
			} finally {

				// Cleanup unlocks the connection: drop every reference to the unbuffered result.

				$row_iterator = null;
				$stream_result = null;
			}
		} )();

		return new Revalt( array(
			'stream'  => $row_stream,
			'total'   => $total,
			'filters' => $built['filters'],
			'range'   => $range,
		) );
	}

	/**
	 * Parse query string arguments and create an SQL query for retrieving log rows from the activity log table
	 *
	 * @former cerber_activity_query()
	 *
	 * @param array $args Optional arguments to use them instead of using query string
	 * @param bool $export When true, builds the export variant: full projection joined with the
	 *                     users table, no LIMIT, the stream SQL under 'sql', and companion COUNT
	 *                     and MIN/MAX queries compiled into 'count_sql' and 'range_sql' for the
	 *                     streamed total and date range. Set only by stream_log(). When false, the
	 *                     read variant returns the configured query builder under 'builder' for the
	 *                     cached reader to execute and read FOUND_ROWS() from.
	 *
	 * @return Revalt<array{builder:CRB_Query_Builder,filters:array{per_page:int,falist:array<int>,ip:string,login:string,user_id:int,search:string,sid:string,in_url:string,country:string,time_begin:int,time_end:int},calc_on:bool}|array{sql:string,count_sql:string,range_sql:string,filters:array{per_page:int,falist:array<int>,ip:string,login:string,user_id:int,search:string,sid:string,in_url:string,country:string,time_begin:int,time_end:int},calc_on:bool}>
	 *         Success carries the compiled query payload: the read variant carries the configured query builder
	 *         under 'builder', while the export variant carries the compiled SQL bundle under 'sql', 'count_sql'
	 *         and 'range_sql'. On failure the payload is absent and the lower-layer Revalt is chained for
	 *         root-cause diagnostics.
	 *
	 * @since 4.16
	 */
	private static function parse_query( array $args = array(), bool $export = false ): Revalt {
		global $wpdb;

		$filters = self::default_filter_values();

		$q = crb_admin_parse_query( array_keys( self::EVENT_FILTERING_PARAMS ), $args );

		// Pagination, projection mode, and the users table are resolved here so the builder
		// can compile the full statement rather than only the WHERE clause.

		$per_page = ( isset( $args['per_page'] ) ) ? absint( $args['per_page'] ) : crb_admin_get_per_page();
		$page_offset = max( 0, ( crb_get_page_num() - 1 ) * $per_page );
		$calc_on = empty( $args['compact_view'] );
		$users_table = $wpdb->users;

		$filters['per_page'] = $per_page;

		$db_result = warp_get_db();

		if ( $db_result->has_errors() ) {
			return new Revalt( null, 'activity_query_db_unavailable', 'Activity query aborted: database layer unavailable', null, $db_result );
		}

		/** @var CRB_Database $db */
		$db = $db_result->get_results();
		$qb = $db->table( CERBER_LOG_TABLE )->alias( 'log' );

		// Activity filter: an explicit filter set takes precedence over the activity list.
		$falist = array();
		if ( ! empty( $q->filter_set ) ) {
			$falist = crb_get_filter_set( $q->filter_set );
		}
		elseif ( $q->filter_activity ) {
			$falist = crb_sanitize_int( $q->filter_activity );
		}
		if ( $falist ) {
			$qb->where( 'log.activity', 'IN', $falist );
		}
		$filters['falist'] = $falist;

		// Event status filter.
		if ( $q->filter_status ) {
			if ( $status = crb_sanitize_int( $q->filter_status ) ) {
				$qb->where( 'log.ac_status', 'IN', $status );
			}
		}

		// IP filter: a numeric range, a single address, or a guaranteed no-match fallback.
		if ( $q->filter_ip ) {
			$range = cerber_any2range( $q->filter_ip );
			if ( is_array( $range ) ) {
				$qb->where( 'log.ip_long', '>=', $range['begin'] )->where( 'log.ip_long', '<=', $range['end'] );
			}
			elseif ( cerber_is_ip_or_net( $q->filter_ip ) ) {
				$qb->where( 'log.ip', '=', $q->filter_ip );
			}
			else {
				$qb->where( 'log.ip', '=', 'produce-no-result' );
			}
			$filters['ip'] = preg_replace( CRB_IP_NET_RANGE, ' ', $q->filter_ip );
		}

		// Login filter: a pipe-separated list becomes an OR group, otherwise an exact match.
		if ( $q->filter_login ) {
			if ( strpos( $q->filter_login, '|' ) !== false ) {
				$login_conditions = array();
				foreach ( explode( '|', $q->filter_login ) as $single_login ) {
					$login_conditions[] = array( 'log.user_login', '=', $single_login );
				}
				$qb->where_any( $login_conditions );
			}
			else {
				$qb->where( 'log.user_login', '=', $q->filter_login );
			}
			$filters['login'] = crb_escape_html( $q->filter_login );
		}

		// User filter: any logged-in user, or a specific user as the actor or the target.
		if ( isset( $q->filter_user ) ) {
			if ( $q->filter_user == '*' ) {
				$qb->where( 'log.user_id', '!=', 0 );
				$filters['user_id'] = 0;
			}
			elseif ( is_numeric( $q->filter_user ) ) {
				$filter_user_id = absint( $q->filter_user );
				$qb->where_any( array(
					array( 'log.user_id', '=', $filter_user_id ),
					array( 'log.ac_by_user', '=', $filter_user_id ),
				) );
				$filters['user_id'] = $filter_user_id;
			}
		}

		// Free-text search across the login and the IP columns. User-supplied wildcards
		// stay active because the term is not passed through CRB_Database::escape_like().
		if ( $q->search_activity ) {
			$search = stripslashes( $q->search_activity );
			$filters['search'] = crb_escape_html( $search );
			$pattern = '%' . $search . '%';
			$qb->where_any( array(
				array( 'log.user_login', 'LIKE', $pattern ),
				array( 'log.ip', 'LIKE', $pattern ),
			) );
		}

		// Request ID filter (session_id column)
		if ( $q->filter_sid ) {

			// session_id is marked as "Request ID" in the UI

			$sid = (string) $q->filter_sid;

			if ( ! preg_match( '/^[A-Za-z0-9]{1,32}$/', $sid ) ) {
				return new Revalt(
					null,
					'invalid_request_id',
					'Request ID must contain only letters and digits and must not be longer than 32 characters.'
				);
			}

			$qb->where( 'log.session_id', '=', $sid );
			$filters['sid'] = $sid;
		}

		// URL search within the stored request details.
		if ( $q->search_url ) {
			$search = stripslashes( $q->search_url );
			$filters['in_url'] = crb_escape_html( $search );
			$qb->where( 'log.details', 'LIKE', '%' . $search . '%' );
		}

		// Country filter, limited to the stored country code length.
		if ( $q->filter_country ) {
			$country = substr( $q->filter_country, 0, 3 );
			$filters['country'] = crb_escape_html( $country );
			$qb->where( 'log.country', '=', $country );
		}

		// Time range on the event timestamp.
		if ( $begin = $q->filter_time_begin ) {
			$filters['time_begin'] = absint( $begin );
			$qb->where( 'log.stamp', '>=', absint( $begin ) );
		}

		if ( $end = $q->filter_time_end ) {
			$filters['time_end'] = absint( $end );
			$qb->where( 'log.stamp', '<=', absint( $end ) );
		}

		// Projection and pagination depend on the mode, selected explicitly by $export.
		// The export variant joins wp_users for the display name and the aliased login
		// column and omits LIMIT so stream_log() can stream every matching row in a single
		// unbuffered pass.

		$count_sql = '';
		$range_sql = '';

		if ( $export ) {

			// The unbuffered export stream locks the connection while it is consumed, so the
			// total for the CSV header cannot be read with SQL_CALC_FOUND_ROWS/FOUND_ROWS().
			// Compile a separate COUNT query from a snapshot of the filters taken before the
			// projection and ordering are applied.

			$count_qb = clone $qb;
			$count_sql = $count_qb->select_count( '*', 'total' )->to_sql();

			// Companion MIN/MAX query for the header date range, cloned from the same
			// pre-projection snapshot so it carries the identical filters.
			$range_qb = clone $qb;
			$range_sql = $range_qb->select_min( 'log.stamp', 'oldest' )
			                      ->select_max( 'log.stamp', 'newest' )
			                      ->to_sql();

			$qb->select( 'log.*' )
			   ->select( 'u.display_name' )
			   ->select_as( 'u.user_login', 'ulogin' )
			   ->join( $users_table, 'log.user_id', 'u.ID', 'LEFT', 'u' )
			   ->order_by( 'stamp', 'DESC' );
		}
		else {
			$qb->calc_found_rows( $calc_on )
			   ->order_by( 'stamp', 'DESC' );

			if ( $per_page > 0 ) {
				$qb->limit( $per_page )
				   ->offset( $page_offset );
			}
		}

		// Materialize the SQL once so any deferred to_sql() validation (for example a missing LIMIT
		// for a set OFFSET) is recorded in the builder state before it is inspected below.
		$sql = $qb->to_sql();

		// Propagate builder validation failures as a structured error so callers abort instead of
		// running a filters-stripped query. The builder state Revalt is chained for diagnostics.
		if ( $qb->get_state()->has_errors() ) {
			return new Revalt( null, 'activity_query_build_failed', 'Activity query aborted: query builder validation failed', null, $qb->get_state() );
		}

		// The export variant streams raw SQL plus separately compiled COUNT and MIN/MAX companions.
		// The read variant hands the configured builder to the cached reader, which executes it and
		// reads FOUND_ROWS() through the builder's own terminals.
		if ( $export ) {
			return new Revalt( array( 'sql' => $sql, 'count_sql' => $count_sql, 'range_sql' => $range_sql, 'filters' => $filters, 'calc_on' => $calc_on ) );
		}

		return new Revalt( array( 'builder' => $qb, 'filters' => $filters, 'calc_on' => $calc_on ) );
	}

	/**
	 * Returns the echo-back filter values in their default (no-filter) shape.
	 *
	 * Every consumer reads a fixed set of keys from the filters array, so the shape must stay
	 * complete even when query building fails. parse_query() seeds the filters from this, and the
	 * error paths of its callers reuse it to keep the by-reference contract intact.
	 *
	 * @return array{per_page:int,falist:array<int>,ip:string,login:string,user_id:int,search:string,sid:string,in_url:string,country:string,time_begin:int,time_end:int}
	 */
	private static function default_filter_values(): array {
		return array(
			'per_page'   => 0,
			'falist'     => array(),
			'ip'         => '',
			'login'      => '',
			'user_id'    => 0,
			'search'     => '',
			'sid'        => '',
			'in_url'     => '',
			'country'    => '',
			'time_begin' => 0,
			'time_end'   => 0,
		);
	}

	/**
	 * Deletes rows from the activity table using specified values of the given fields.
	 *
	 * @param array $key_fields An associative array of conditional fields for the WHERE clause.
	 *                          Format: ['column_name' => 'value', ...]
	 *
	 * @return int The number of affected rows
	 *
	 * @since 9.6.6.12
	 */
	static function delete( $key_fields = array() ): int {

		if ( ! $key_fields ) {
			return 0;
		}

		if ( $ret = cerber_db_delete_rows( CERBER_LOG_TABLE, $key_fields ) ) {
			self::clear_status();
		}

		return $ret;
	}
}
