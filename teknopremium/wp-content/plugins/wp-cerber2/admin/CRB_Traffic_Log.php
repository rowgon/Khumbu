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

/**
 * Owns the application-level Traffic Log boundary.
 *
 *
 * @since 9.8.1
 */
final class CRB_Traffic_Log {

	// Note: values is not specified yet

	const QUERY_PARAMS = array(
		'filter_sid'            => 'to_be_specified',
		'filter_http_code'      => 'to_be_specified',
		'filter_http_code_mode' => 'to_be_specified',
		'filter_ip'             => 'to_be_specified',
		'filter_processing'     => 'to_be_specified',
		'filter_user'           => 'to_be_specified',
		'filter_user_alt'       => 'to_be_specified',
		'filter_user_mode'      => 'to_be_specified',
		'filter_wp_type'        => 'to_be_specified',
		'filter_wp_type_mode'   => 'to_be_specified',
		'search_traffic'        => 'to_be_specified',
		'filter_method'         => 'to_be_specified',
		'filter_activity'       => 'to_be_specified',
		'filter_set'            => 'to_be_specified',
		'filter_errors'         => 'to_be_specified',
	);

	/**
	 * Builds a normalized traffic-log query payload from filter arguments.
	 *
	 * Resolves pagination, applies supported filters, and compiles row, count, and range SQL
	 * statements for the traffic-log dataset. The returned payload always has the same shape.
	 *
	 * On success, SQL keys contain executable statements and 'filters' contains normalized
	 * echo-back filter values. On failure, SQL keys are empty strings and 'filters' contains
	 * the available echo-back filter values. Chained diagnostics preserve lower-level causes
	 * when available.
	 *
	 * Callers must treat has_errors() as authoritative and must not execute SQL from an
	 * erroring result.
	 *
	 * @param array<string,mixed> $args Optional query argument overrides.
	 *
	 * @return Revalt<array{sql:string,count_sql:string,range_sql:string,filters:array{per_page:int,activity:array<int>,ip:string,processing:int,user:string,wp_type:int}}>
	 *         Stable query payload with compiled SQL on success or empty SQL strings on failure.
	 *
	 * @since 6.0
	 */
	public static function parse_query( array $args = array() ): Revalt {

		$filters = self::default_filter_values();

		// Pagination is resolved here so the builder can compile LIMIT and OFFSET directly.
		$per_page = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : crb_admin_get_per_page();
		$page_offset = max( 0, ( crb_get_page_num() - 1 ) * $per_page );
		$filters['per_page'] = $per_page;

		$db_result = warp_get_db();

		if ( $db_result->has_errors() ) {
			return new Revalt( self::make_query_payload( $filters ), 'traffic_query_db_unavailable', 'Traffic log query aborted: database layer unavailable', null, $db_result );
		}

		/** @var CRB_Database $db */
		$db = $db_result->get_results();
		$qb = $db->table( CERBER_TRAF_TABLE )->alias( 'log' );

		$q = crb_admin_parse_query( array_keys( self::QUERY_PARAMS ), $args );

		$join_activity = false;

		// Request ID filter (session_id column)
		if ( $q->filter_sid ) {

			// session_id is marked as "Request ID" in the UI
			$sid = (string) $q->filter_sid;

			if ( ! preg_match( '/^[A-Za-z0-9]{1,32}$/', $sid ) ) {
				return new Revalt( self::make_query_payload( $filters ), 'traffic_query_invalid_request_id', 'Traffic log query aborted: invalid request ID' );
			}

			$qb->where( 'log.session_id', '=', $sid );
		}

		// HTTP response code filter: a list of codes, or a single code with an optional greater-than mode.
		if ( $q->filter_http_code ) {
			if ( is_array( $q->filter_http_code ) ) {
				$http_codes = array_filter( array_map( 'absint', $q->filter_http_code ) );
				if ( $http_codes ) {
					$qb->where( 'log.http_code', 'IN', $http_codes );
				}
			}
			else {
				$code_operator = ( $q->filter_http_code_mode == 'GT' ) ? '>' : '=';
				$qb->where( 'log.http_code', $code_operator, absint( $q->filter_http_code ) );
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
			$filters['ip'] = preg_replace( CRB_IP_NET_RANGE, '', $q->filter_ip );
		}

		// Processing-time filter (longer than the given number of milliseconds).
		if ( $q->filter_processing ) {
			$proc = absint( $q->filter_processing );
			$qb->where( 'log.processing', '>', $proc );
			$filters['processing'] = $proc;
		}

		// User filter: any authenticated user, or a list of user IDs, with an optional exclude mode.
		$filter_user = $q->filter_user !== false ? $q->filter_user : ( $q->filter_user_alt !== false ? $q->filter_user_alt : '' );

		if ( $filter_user == '*' ) {
			$user_operator = empty( $q->filter_user_mode ) ? '!=' : '=';
			$qb->where( 'log.user_id', $user_operator, 0 );
			$filters['user'] = '*';
		}
		else {
			$matches = array();
			if ( preg_match_all( '/\d+/', $filter_user, $matches ) ) {
				$user_ids = array_map( 'absint', $matches[0] );
				$user_operator = empty( $q->filter_user_mode ) ? 'IN' : 'NOT IN';
				$qb->where( 'log.user_id', $user_operator, $user_ids );

				if ( $user_operator === 'IN' ) {
					$filters['user'] = implode( ',', $user_ids );
				}
			}
		}

		// WordPress request type filter, with an optional greater-than mode.
		if ( $q->filter_wp_type ) {
			$wp_type = absint( $q->filter_wp_type );
			$type_operator = ( $q->filter_wp_type_mode === 'GT' ) ? '>' : '=';
			$qb->where( 'log.wp_type', $type_operator, $wp_type );
			$filters['wp_type'] = $wp_type;
		}

		// Search across the stored request data. Each search term is passed through
		// CRB_Database::escape_like() so user-supplied wildcards are matched literally.
		if ( $q->search_traffic ) {
			$search = stripslashes_deep( $q->search_traffic );

			if ( $search['ip'] ) {
				if ( $ip = filter_var( $search['ip'], FILTER_VALIDATE_IP ) ) {
					$qb->where( 'log.ip', '=', $ip );
					$filters['ip'] = $ip;
				}
				else {
					$qb->where( 'log.ip', 'LIKE', '%' . CRB_Database::escape_like( $search['ip'] ) . '%' );
				}
			}
			if ( $search['uri'] ) {
				$qb->where( 'log.uri', 'LIKE', '%' . CRB_Database::escape_like( $search['uri'] ) . '%' );
			}
			if ( $search['fields'] ) {
				$qb->where( 'log.request_fields', 'LIKE', '%' . CRB_Database::escape_like( $search['fields'] ) . '%' );
			}
			if ( $search['details'] ) {
				$qb->where( 'log.request_details', 'LIKE', '%' . CRB_Database::escape_like( $search['details'] ) . '%' );
			}
			if ( $search['date_from'] ) {
				if ( $stamp = strtotime( 'midnight ' . $search['date_from'] ) ) {
					$gmt_offset = get_option( 'gmt_offset' ) * 3600;
					$qb->where( 'log.stamp', '>=', absint( $stamp ) - $gmt_offset );
				}
			}
			if ( $search['date_to'] ) {
				if ( $stamp = 24 * 3600 + strtotime( 'midnight ' . $search['date_to'] ) ) {
					$gmt_offset = get_option( 'gmt_offset' ) * 3600;
					$qb->where( 'log.stamp', '<=', absint( $stamp ) - $gmt_offset );
				}
			}
			if ( ! $q->filter_errors && $search['errors'] ) {
				$qb->where( 'log.php_errors', 'LIKE', '%' . CRB_Database::escape_like( $search['errors'] ) . '%' );
			}
		}

		// HTTP request method filter.
		if ( $q->filter_method ) {
			$qb->where( 'log.request_method', '=', $q->filter_method );
		}

		// Activity filter.
		$activity_in = array();
		if ( $q->filter_set ) {
			$activity_in = crb_get_filter_set( $q->filter_set );
		}
		elseif ( $q->filter_activity ) {
			$activity_in = crb_sanitize_int( $q->filter_activity );
		}
		if ( $activity_in ) {
			$qb->where( 'act.activity', 'IN', $activity_in );
			$filters['activity'] = $activity_in;
			$join_activity = true;
		}

		// Any-software-error filter: an HTTP 500 response or a non-empty stored PHP error list.
		if ( $q->filter_errors ) {
			$qb->where_any( array(
				array( 'log.http_code', '=', 500 ),
				array( 'log.php_errors', '!=', '' ),
			) );
		}

		// The activity join is shared by the row and the count statements.
		if ( $join_activity ) {
			$qb->join( CERBER_LOG_TABLE, 'log.session_id', 'act.session_id', 'INNER', 'act' );
		}

		// Count statement: cloned from the filter-only state, before projection, ordering, and limits.
		$count_qb = clone $qb;
		$count_sql = $count_qb->select_count( 'log.stamp', 'total' )->to_sql();

		// Companion MIN/MAX query for the export header date range.
		$range_qb = clone $qb;
		$range_sql = $range_qb->select_min( 'log.stamp', 'oldest' )->select_max( 'log.stamp', 'newest' )->to_sql();

		// Row statement: the session id leads the projection so the cached reader can key rows by it.
		$qb->select( 'log.session_id' );

		if ( ! empty( $args['columns'] ) ) {
			foreach ( $args['columns'] as $col_name ) {
				$qb->select( 'log.' . $col_name );
			}
		}
		else {
			$qb->select( 'log.*' );
		}

		$qb->order_by( 'log.stamp', 'DESC' );

		if ( $per_page > 0 ) {
			$qb->limit( $per_page )->offset( $page_offset );
		}

		$sql = $qb->to_sql();

		// Propagate builder validation failures as a structured error.
		if ( $qb->get_state()->has_errors() ) {
			return new Revalt( self::make_query_payload( $filters ), 'traffic_query_build_failed', 'Traffic log query aborted: query builder validation failed', null, $qb->get_state() );
		}

		return new Revalt( self::make_query_payload( $filters, $sql, $count_sql, $range_sql ) );
	}

	/**
	 * Retrieves traffic log rows for the log-browser screen.
	 *
	 * Builds the traffic-log query from the supplied filter arguments, runs the row and count reads,
	 * and decodes serialized request payload columns before returning. Rows are keyed by session id
	 * so the renderer and related lookups can address them directly.
	 *
	 * On success the payload carries:
	 * - 'rows'    decoded traffic log row objects keyed by session id; empty when nothing matches;
	 * - 'total'   total number of matching rows for pagination;
	 * - 'filters' echo-back filter values reused by the renderer.
	 *
	 * On query construction failure, no database reads are performed. The returned result keeps a stable
	 * payload shape with empty SQL strings and the available echo-back filter values. Callers must treat
	 * has_errors() as authoritative and must not consume row data from an erroring result.
	 *
	 * @param array<string,mixed> $args Optional query argument overrides.
	 *
	 * @return Revalt<array{rows:array<string,object>,total:int,filters:array<string,mixed>}|array{sql:string,count_sql:string,range_sql:string,filters:array<string,mixed>}>
	 *         Successful row-read payload, or stable query-construction payload on failure.
	 *
	 * @since 9.8.1
	 */
	public static function fetch_rows( array $args = array() ): Revalt {

		$parsed = self::parse_query( $args );

		if ( $parsed->has_errors() ) {
			return $parsed;
		}

		$sql_query = $parsed->get_element( 'sql', '' );
		$sql_found = $parsed->get_element( 'count_sql', '' );
		$filters   = $parsed->get_element( 'filters', array() );

		// Run the row and count reads through the query cache. The row projection leads with the
		// session id, so MYSQL_FETCH_OBJECT_K keys the returned rows by it.

		list( $traffic_log_rows, $total_rows ) = crb_q_cache_get( array(
			array( $sql_query, MYSQL_FETCH_OBJECT_K ),
			array( $sql_found )
		), CERBER_TRAF_TABLE );

		if ( is_object( $traffic_log_rows ) ) { // Due to possible JSON from cache
			$traffic_log_rows = get_object_vars( $traffic_log_rows );
		}

		// Decode the serialized request payload columns once after fetching.

		if ( $traffic_log_rows ) {
			foreach ( $traffic_log_rows as $key => $row ) {
				$traffic_log_rows[ $key ]->request_details = crb_auto_decode( $row->request_details );
				$traffic_log_rows[ $key ]->request_fields = crb_auto_decode( $row->request_fields );
				$traffic_log_rows[ $key ]->php_errors = crb_auto_decode( $row->php_errors );
			}
		}

		$total_rows = (int) ( $total_rows[0][0] ?? 0 );

		return new Revalt( array(
			'rows'    => $traffic_log_rows ?: array(),
			'total'   => $total_rows,
			'filters' => $filters,
		) );
	}

	/**
	 * Builds the traffic export query and opens an unbuffered row stream for CSV export.
	 * The stream is a generator that yields one raw traffic row object (stdClass) per iteration.
	 *
	 * Resolves the export metadata eagerly while the connection is still free: the header total runs as
	 * a separate buffered COUNT query and the date range as a buffered MIN/MAX query, because the
	 * unbuffered row stream locks the connection while it is consumed. The export is always unbounded:
	 * per_page is forced to 0 so parse_query() emits no LIMIT.
	 *
	 * On success the payload carries:
	 *  - 'stream'  a Generator yielding raw traffic row objects, never wrapped into Revalt;
	 *  - 'total'   the total number of matching rows for the header (0 when the count is unavailable);
	 *  - 'filters' the echo-back filter values for the export header;
	 *  - 'range'   array{oldest:int,newest:int} of the oldest and newest request timestamps, or an
	 *              empty array when no rows match or the range query is unavailable.
	 *
	 * The returned stream owns the database connection until it is fully consumed or destroyed:
	 * iterating it to completion and stopping early both release the unbuffered result. The caller must
	 * iterate it (even an empty export) so the connection is unlocked.
	 *
	 * @param array<string,mixed> $args Optional query argument overrides, including a 'columns'
	 *                                  projection subset. per_page is ignored: the export is unbounded.
	 *
	 * @return Revalt<array{stream:\Generator,total:int,filters:array{per_page:int,activity:array<int>,ip:string,processing:int,user:string,wp_type:int},range:array{}|array{oldest:int,newest:int}}>
	 *         Success carries the export payload described above. On failure the payload is absent and
	 *         the lower-layer Revalt is chained for root-cause diagnostics.
	 *
	 * @since 9.8.1
	 */
	public static function stream_log( array $args = array() ): Revalt {

		// The export is always unbounded: force per_page to 0 so parse_query() emits no LIMIT.
		$args['per_page'] = 0;

		$parsed = self::parse_query( $args );

		// A failed build aborts before any query runs. The parser Revalt is chained for diagnostics.

		if ( $parsed->has_errors() ) {
			return new Revalt( null, 'traffic_export_query_build_failed', 'Traffic export aborted: unable to build the export query', null, $parsed );
		}

		$built = $parsed->get_results();

		$db_result = warp_get_db();

		if ( $db_result->has_errors() ) {
			return new Revalt( null, 'traffic_export_db_unavailable', 'Traffic export aborted: database layer unavailable', null, $db_result );
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
			return new Revalt( null, 'traffic_export_stream_failed', 'Traffic export aborted: unable to open the row stream', null, $stream_result );
		}

		// Wrap the unbuffered result in a generator that releases the connection when the consumer
		// reaches the last row or stops early. Raw row objects are yielded as-is.

		$row_stream = ( static function () use ( $stream_result ): \Generator {

			$row_iterator = $stream_result->get_results();

			try {
				foreach ( $row_iterator as $traffic_row ) {
					yield $traffic_row;
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
	 * Returns the echo-back filter values in their default (no-filter) shape.
	 *
	 * Callers read a fixed set of keys from the filters array, so the shape must stay complete even
	 * when no filter is active. parse_query() seeds the filters from this.
	 *
	 * @return array{per_page:int,activity:array<int>,ip:string,processing:int,user:string,wp_type:int}
	 */
	private static function default_filter_values(): array {
		return array(
			'per_page'   => 0,
			'activity'   => array(),
			'ip'         => '',
			'processing' => 0,
			'user'       => '',
			'wp_type'    => 0,
		);
	}


	/**
	 * Builds a stable traffic query payload.
	 *
	 * Empty SQL strings mean that no executable query was produced.
	 *
	 * @param array<string,mixed> $filters Normalized echo-back filter values.
	 * @param string $sql Optional row SELECT statement.
	 * @param string $count_sql Optional COUNT SELECT statement.
	 * @param string $range_sql Optional MIN/MAX range SELECT statement.
	 *
	 * @return array{sql:string,count_sql:string,range_sql:string,filters:array<string,mixed>}
	 *
	 * @see parse_query()
	 */
	private static function make_query_payload( array $filters, string $sql = '', string $count_sql = '', string $range_sql = '' ): array {
		return array(
			'sql'       => $sql,
			'count_sql' => $count_sql,
			'range_sql' => $range_sql,
			'filters'   => $filters,
		);
	}

	/**
	 * Is log empty?
	 *
	 * @return bool True if the activity log is empty (nothing is logged)
	 *
	 */
	static function is_empty(): bool {

		$db_result = warp_get_db();
		$probe = $db_result->has_errors()
			? $db_result
			: $db_result->get_results()->table( CERBER_TRAF_TABLE )->select( 'stamp' )->limit( 1 )->get_value();

		// It is safer to treat the log as non-empty if a DB error occurs.
		if ( $probe->get_results() || $probe->has_errors() ) {
			return false;
		}

		return true;
	}
}
