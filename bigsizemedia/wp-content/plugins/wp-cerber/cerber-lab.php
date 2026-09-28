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

// If this file is called directly, abort executing.
if ( ! defined( 'WPINC' ) ) { exit; }

const LAB_NODE_MAX = 7; // Maximum node ID
const LAB_DELAY_MAX = 2000; // milliseconds, reasonable maximum of processing time while connecting to a node
const LAB_RECHECK = 15 * 60; // seconds, allowed interval for rechecking nodes
const LAB_INTERVAL = 180; // seconds, push interval
const LAB_DNS_TTL = 3 * 24 * 3600; // seconds, interval of updating DNS cache for nodes IPs
const LAB_IP_OK = 100; // an ideal, the best possible reputation
const LAB_KEY_LENGTH = 32;
const LAB_LICENSE_GRACE = 3600 * 24 * 3;
const LAB_SITE_KEY_DATA = '_cerberkey_';

/**
 * Is IP blocked globally in Cerber Lab?
 *
 * @param string $ip IP address to check against global black list
 * @param bool $ask If true, send request to Cerber Lab if no IP in the local cache found
 *
 * @return bool true if IP is blocked
 */
function lab_is_blocked( $ip = '', $ask = true ) {

	if ( ! $ip ) {
		$ip = cerber_get_remote_ip();
	}

	if ( is_ip_private( $ip ) ) {
		return false;
	}

	$tag = cerber_acl_check( $ip );
	if ( $tag == 'W' ) {
		return false;
	}
	if ( $tag == 'B' ) {
		return true;
	}

	if ( ! lab_lab() ) {
		return false;
	}

	$rep = lab_get_reputation( $ip, $ask );

	if ( is_numeric( $rep ) && $rep < LAB_IP_OK ) {
		return true;
	}

	return false;
}

/**
 * Return reputation for a given IP
 *
 * @param string $ip
 * @param bool $ask If true, send request to Cerber Lab (if no IP in the local cache found)
 *
 * @return int  Reputation for a given IP
 */
function lab_get_reputation( $ip, $ask = true ) {

	if ( ! $ip = filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return LAB_IP_OK;
	}
	if ( is_ip_private( $ip ) ) {
		return LAB_IP_OK;
	}

	$reputation = cerber_db_get_var( 'SELECT reputation FROM ' . CERBER_LAB_IP_TABLE . ' WHERE ip = "' . $ip . '"' );
	if ( is_numeric( $reputation ) ) {
		return $reputation;
	}
	elseif (!$ask){
		return LAB_IP_OK;
	}

	$ip_id    = cerber_get_id_ip( $ip );
	$lab_data = lab_api_send_request( array( 'ask_cerberlab' => array( $ip_id => $ip ) ) );

	if ( ! $lab_data || empty( $lab_data['response']['payload'][ $ip_id ]['reputation'] ) ) {
		$reputation = LAB_IP_OK;
		$ip_data = array();
		$ip_data['reputation']['value'] = $reputation;
		$ip_data['reputation']['ttl'] = 600;
	}
	else {
		$reputation = absint( $lab_data['response']['payload'][ $ip_id ]['reputation']['value'] );
		$ip_data = $lab_data['response']['payload'][ $ip_id ];
	}

	lab_reputation_update($ip , $ip_data);

	if ( ! empty( $lab_data['response']['payload'][ $ip_id ]['network']['geo'] ) ) {
		lab_geo_update($ip, $lab_data['response']['payload'][ $ip_id ]);
	}

	return $reputation;
}

function lab_reputation_update( $ip, $ip_data ) {

	if ( empty( $ip_data['reputation'] ) ) {
		return;
	}

	if ( ! $ip = filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return;
	}

	$reputation = absint( $ip_data['reputation']['value'] );
	$expires    = time() + absint( $ip_data['reputation']['ttl'] );

	if ( cerber_db_get_var( 'SELECT COUNT(ip) FROM ' . CERBER_LAB_IP_TABLE . ' WHERE ip = "' . $ip . '"' ) ) {
		cerber_db_query( 'UPDATE ' . CERBER_LAB_IP_TABLE . ' SET reputation = ' . $reputation . ', expires = ' . $expires . ' WHERE ip = "' . $ip . '"' );
	}
	else {
		cerber_db_query( 'INSERT INTO ' . CERBER_LAB_IP_TABLE . ' (ip, reputation, expires) VALUES ("' . $ip . '",' . $reputation . ',' . $expires . ')' );
	}
}

/**
 * Send request to a Cerber Lab node.
 *
 * @param array $workload Workload
 * @param string|int $payload_key Return this element from the payload if it exists
 *
 * @return array|bool
 */
function lab_api_send_request( $workload = array(), $payload_key = null ) {
	global $node_delay;

	$push = lab_get_push();

	if ( ! $workload && ! $push ) {
		return false;
	}

	$key = lab_get_key();

	if ( $workload && empty( $key[2] ) && ! $push ) {
		return false;
	}

	$site = lab_get_site_meta( false );

	$request = array(
		'key'          => $key,
		'workload'     => $workload,
		'push'         => $push,
		'lang'         => (string) $site['lang'],
		'wp_ver'       => (string) $site['wp_ver'],
		'multi'        => is_multisite() ? 1 : 0,
		'version'      => CERBER_VER,
		'PHP'          => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
		'sapi'         => PHP_SAPI,
		'srv_name'     => substr( $_SERVER['SERVER_SOFTWARE'] ?? '', 0, 30 ),
		'cache_status' => (int) CRB_Cache::checker(),
		'time'         => time(),
	);

	if ( lab_lab() ) {
		$request['site_url'] = cerber_get_site_url();
		$request['site_home'] = cerber_get_home_url();
	}

	$ret = lab_send_request( $request );

	// If something goes wrong, take the next closest node
	if ( ! $ret ) {
		$ret = lab_send_request( $request );
	}
	elseif ( ( $node_delay * 1000 ) > LAB_DELAY_MAX ) {
		lab_check_nodes(); // Recheck nodes for further requests
	}

	if ( $push && $ret ) {
		lab_trunc_push();
	}

	if ( $payload_key ) {
		return crb_array_get( $ret, array( 'response', 'payload', $payload_key ) );
	}

	return $ret;
}

/**
 * Send an HTTP request to a node.
 *
 * @param $request array
 * @param null $node_id Node ID if not set, will use the last closest and active node
 * @param string $scheme http|https
 *
 * @return array|bool The response of a node on the success request otherwise false on any error
 */
function lab_send_request( $request, $node_id = null, $scheme = null ) {
	global $node_delay, $cerber_lab_last_net_error, $cerber_lab_last_node_id;

	if ( ! function_exists( 'curl_init' ) || ! function_exists( 'curl_exec' ) ) {
		return false;
	}

	if ( $scheme != 'http' && $scheme != 'https' ) {
		$scheme = 'https';
	}

	$node = lab_get_node( $node_id );

	$body = array();
	$body['container'] = $request;
	$body['nodes'] = lab_get_nodes( true );

	$request_body = json_encode( $body );
	if ( JSON_ERROR_NONE != json_last_error() ) {
		//'Unable to encode request: '.json_last_error_msg(), array(__FUNCTION__,__LINE__));
		return false;
	}

	$headers = array(
		'Host:' . $node[1],
		'Content-Type: application/json',
		'Accept: application/json',
		'Cerber: ' . CERBER_VER,
		/*	'Authorization: Bearer ' . $fields['key']*/
	);

	$curl = curl_init();
	if ( ! $curl ) {
		cerber_error_log( 'Unable to initialize cURL', 'CLOUD' );

		return false;
	}

	$url = $scheme . '://' . $node[1] . '/engine/v1/';

	crb_configure_curl( $curl, array(
		CURLOPT_URL               => $url,
		CURLOPT_POST              => true,
		CURLOPT_HTTPHEADER        => $headers,
		CURLOPT_POSTFIELDS        => $request_body,
		CURLOPT_RETURNTRANSFER    => true,
		CURLOPT_USERAGENT         => 'Cerber Security Plugin ' . CERBER_VER,
		CURLOPT_CONNECTTIMEOUT    => 2,
		CURLOPT_TIMEOUT           => 4, // including CURLOPT_CONNECTTIMEOUT
		CURLOPT_DNS_CACHE_TIMEOUT => 4 * 3600,
		CURLOPT_SSL_VERIFYHOST    => 2,
		CURLOPT_SSL_VERIFYPEER    => true,
		CURLOPT_CAINFO            => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
	) );

	global $wp_cerber_relay;

	if ( $wp_cerber_relay ) {
		$headers[] = 'Relay: 1';
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
	}

	cerber_diag_log( 'Sending request to: ' . $url, 'CLOUD' );
	cerber_diag_log( 'Request body: ' . print_r( $body, 1 ), 'CLOUD' );

	$start = microtime( true );
	$data = @curl_exec( $curl );
	$stop = microtime( true );

	$cerber_lab_last_node_id = $node[0];

	$node_delay = $stop - $start;

	if ( $data ) {
		$response = lab_parse_response( $data );
	}
	else {
		$response['status'] = 0;
		$code = intval( curl_getinfo( $curl, CURLINFO_HTTP_CODE ) );

		if ( $curl_err = curl_error( $curl ) ) {
			$curl_err .= '[' . curl_errno( $curl ) . ']';
			cerber_error_log( 'cURL => ' . $curl_err, 'CLOUD' );
		}
		else {
			$curl_err = '';
		}

		$response['error'] = 'Network error occurred while connecting to the node #' . $node[0] . ' (HTTP ' . $code . ') ' . $curl_err;
	}

	$curl_info = curl_getinfo( $curl );

	lab_update_node_last( $node[0], array(
		$node_delay,
		$response['status'],
		$response['error'],
		microtime( true ),
		$scheme,
		$curl_info['primary_ip'] ?? '',
		// @since 9.6.1
		'local_ip'    => $curl_info['local_ip'] ?? '',
		'node_host'   => $node[1],
		'outgoing_ip' => $response['net_connection_ip'] ?? '',
		'node_id'     => $node[0]
	) );

	if ( $response['error'] ) {
		$cerber_lab_last_net_error = $response['error'];
		cerber_error_log( $response['error'], 'CLOUD' );

		return false;
	}
	elseif ( defined( 'CERBER_CLOUD_DEBUG' ) && CERBER_CLOUD_DEBUG ) {
		cerber_diag_log( 'Response: ' . print_r( $response, 1 ), 'CLOUD' );
	}

	return $response;
}

/**
 * Parse node response and detect possible errors
 *
 * @param $response
 *
 * @return array
 */
function lab_parse_response( $response ) {
	$ret = array( 'status' => 1, 'error' => false );

	if ( ! empty( $response ) ) {
		$ret = json_decode( $response, true );
		if ( JSON_ERROR_NONE != json_last_error() ) {
			$ret['status'] = 0;
			$ret['error'] = 'JSON ERROR: ' . json_last_error_msg();
		}
		// Is everything is OK?
		if ( empty( $ret['key'] ) || ! empty( $ret['error'] ) ) {
			$ret['status'] = 0; // Not OK
		}
	}
	else {
		$ret['status'] = 0;
		$ret['error'] = 'No node answer';
	}

	if ( ! isset( $ret['error'] ) ) {
		$ret['error'] = false;
	}

	return $ret;
}

/**
 * Return "the best" (closest) node if $node_id is not specified
 *
 * @param $node_id integer node ID
 *
 * @return array First element is the ID of the closest node, second one is the node hostname
 */
function lab_get_node( $node_id = null ) {

	$best_id = ( $node_id = absint( $node_id ) ) ? $node_id : null;

	if ( ! $best_id ) {

		if ( ( $nodes = lab_get_nodes() )
		     && ! empty( $nodes['best'] ) ) {

			$best_id = absint( $nodes['best'] );

			if ( empty( $nodes['nodes'][ $best_id ]['last'][1] ) ) {

				// This node encountered an error during the last request, let's pick another one

				unset( $nodes['nodes'][ $best_id ] );
				$best_id = lab_best_node( $nodes['nodes'] );
			}
		}
	}

	if ( ! $best_id || $best_id > LAB_NODE_MAX ) {
		$best_id = rand( 1, LAB_NODE_MAX );
	}

	$name = 'node' . $best_id . '.cerberlab.net';

	return array( $best_id, $name );
}

/**
 * Check all nodes and find the closest and active one.
 * 
 * @param bool $force If true performs checking nodes without checking allowed interval LAB_RECHECK
 * @param bool $kick_dns If true preload DNS cache to eliminate DNS resolving delay
 *
 * @return bool|int
 */
function lab_check_nodes( $force = false, $kick_dns = false ) {

	$nodes = lab_get_nodes();

	if ( ! $force
	     && isset( $nodes['last_check'] )
	     && ( time() - $nodes['last_check'] ) < LAB_RECHECK ) {
		return false;
	}

	$nodes['nodes'] = array(); // clean up before testing

	cerber_update_set( '_cerberlab_', $nodes );

	for ( $i = 1; $i <= LAB_NODE_MAX; $i ++ ) {
		if ( $kick_dns ) {
			@dns_get_record( 'node' . $i . '.cerberlab.net', DNS_A );
			@dns_get_record( 'node' . $i . '.cerberlab.net', DNS_AAAA );
		}

		lab_send_request( array( 'test' => 'test', 'key' => 1 ), $i );
	}

	$nodes = lab_get_nodes();
	$nodes['best'] = lab_best_node( $nodes['nodes'] );
	$nodes['last_check'] = time();

	cerber_update_set( '_cerberlab_', $nodes );

	return $nodes['best'];
}

/**
 * Find the best (closest) and active node in the list of nodes
 *
 * @param array $nodes
 *
 * @return int the ID of a node, 0 if no node available
 */
function lab_best_node( $nodes = array() ) {
	if ( empty( $nodes ) ) {
		return 0;
	}

	$active_nodes = array();
	foreach ( $nodes as $id => $data ) {
		if ( $data['last']['1'] ) {  // only active nodes must be in the list
			$active_nodes[ $id ] = $data['last']['0'];
		}
	}

	if ( $active_nodes ) {
		asort( $active_nodes );
		reset( $active_nodes );
		$best_id = key( $active_nodes );
	}
	else {
		$best_id = 0;  // no active nodes found :-(
	}

	return $best_id;
}

/**
 * Returns the order in which nodes have to be queried.
 *
 * The returned sequence contains every existing node ID exactly once, therefore a caller
 * iterating over it is guaranteed to reach any operational node. The closest known node goes
 * first unless it failed during its last request, then the nodes that answered during their
 * last request ordered by ascending response delay, then the remaining ones in random order to
 * spread the load of the websites having no node statistics collected yet.
 *
 * @return array<int, int> Node IDs in the order of querying, each one in the range from 1 to LAB_NODE_MAX
 */
function lab_get_node_sequence(): array {
	$nodes = lab_get_nodes();
	$node_stats_list = (array) crb_array_get( $nodes, 'nodes', array() );
	$sequence = array();

	// The closest node detected during the last check of the nodes, skipped if it is known to be down

	$best_id = absint( crb_array_get( $nodes, 'best', 0 ) );
	$best_failed = isset( $node_stats_list[ $best_id ]['last'] ) && empty( $node_stats_list[ $best_id ]['last'][1] );

	if ( $best_id
	     && $best_id <= LAB_NODE_MAX
	     && ! $best_failed ) {
		$sequence[] = $best_id;
	}

	// Nodes with a successful last response, the fastest one first

	$responded = array();

	foreach ( $node_stats_list as $node_id => $node_stats ) {
		$node_id = absint( $node_id );

		if ( $node_id
		     && $node_id <= LAB_NODE_MAX
		     && ! empty( $node_stats['last'][1] ) ) {
			$responded[ $node_id ] = (float) ( $node_stats['last'][0] ?? 0 );
		}
	}

	asort( $responded );

	$sequence = array_unique( array_merge( $sequence, array_keys( $responded ) ) );

	// Nodes having no statistics and nodes that failed during their last request

	$remaining = array_diff( range( 1, LAB_NODE_MAX ), $sequence );
	shuffle( $remaining );

	return array_values( array_merge( $sequence, $remaining ) );
}

/**
 * Update last node connection info
 *
 * @param int $node_id
 * @param array $last
 *
 * @return bool
 */
function lab_update_node_last( $node_id, $last ) {
	$nodes = lab_get_nodes();
	$nodes['nodes'][ $node_id ]['last'] = $last;

	if ( $last[1] ) {
		$nodes['last_node_ok'] = $last;
	}
	else {
		$nodes['last_node_failed'] = $last;
	}

	return cerber_update_set( '_cerberlab_', $nodes );
}

function lab_get_nodes( $no_sensitive = false ) {
	$nodes = cerber_get_set( '_cerberlab_' );

	if ( ! $nodes || ! is_array( $nodes ) ) {
		$nodes = array();
	}

	if ( $no_sensitive && ! empty( $nodes['nodes'] ) ) {
		foreach ( $nodes['nodes'] as $node ) {
			if ( isset( $node['last']['local_ip'] ) ) {
				unset( $node['last']['local_ip'] );
			}
		}
	}

	return $nodes;
}

/**
 * Check if the Cerber Cloud alive
 *
 * @return bool|int The number of active nodes, false otherwise
 */
function lab_is_cloud_ok(){
	$nodes = lab_get_nodes();
	if ( ! $nodes ) {
		return false;
	}
	$n = 0;
	foreach ( $nodes['nodes'] as $id => $node ) {
		if ($node['last'][1]){
			$n++;
		}
	}
	if ($n > 0){
		return $n;
	}

	return false;
}

/**
 * Save data for lab
 *
 * @param $ip string IP address
 * @param $reason_id integer Why IP is malicious
 * @param $details
 */
function lab_save_push( $ip, $reason_id, $details = null ) {
	static $done = false;

	if ( $done || cerber_check_groove() ) {
		return; // Known user
	}

	$ip = filter_var( $ip, FILTER_VALIDATE_IP );

	if ( ! $ip || is_ip_private( $ip ) || crb_acl_is_allowed( $ip ) || ! ( crb_get_settings( 'cerberlab' ) || lab_lab() ) ) {
		return;
	}

	$reason_id = absint( $reason_id );

	if ( in_array( $reason_id, array( 708, 709, 55 ) ) ) {
		$details = array( 'uri' => $_SERVER['REQUEST_URI'] );
	}
	elseif ( $reason_id == 100 ) {
		$details = absint( CRB_Globals::$act_status );
	}
	elseif ( $reason_id == CRB_EV_LDN
	         && CRB_Globals::$act_status == 50 ) {
		$details = absint( CRB_Globals::$act_status );
	}

	if ( is_array( $details ) ) {
		$details = serialize( $details );
	}

	$details = cerber_db_real_escape( $details );

	cerber_db_query( 'INSERT INTO ' . CERBER_LAB_TABLE . ' (ip, reason_id, details, stamp) VALUES ("' . $ip . '",' . $reason_id . ',"' . $details . '",' . time() . ')' );

	$done = true;
}
/**
 * Get data for lab
 *
 * @return array
 */
function lab_get_push(): array {
	$result = cerber_db_get_results( 'SELECT * FROM ' . CERBER_LAB_TABLE );
	if ( $result ) {
		return array( 'type_1' => $result );
	}

	return [];
}

function lab_trunc_push(){
	cerber_db_query( 'TRUNCATE TABLE ' . CERBER_LAB_TABLE );
	cerber_db_query( 'DELETE FROM ' . CERBER_LAB_TABLE ); // TRUNCATE might not work on a weird hosting
}

function cerber_push_lab() {
	if ( ! crb_get_settings( 'cerberlab' )
	     || cerber_get_set( '_cerberpush_', null, false ) ) {
		return;
	}

	lab_api_send_request();

	cerber_update_set( '_cerberpush_', 1, null, false, time() + LAB_INTERVAL );
}

/**
 * Creates Site ID based on its URL
 *
 * @return string Site ID
 */
function lab_generate_site_id(): string {
	if ( is_multisite() ) {
		$home = network_home_url();
	}
	else {
		$home = cerber_get_site_url();
	}

	$home = rtrim( trim( $home ), '/' );
	$id   = substr( $home, strpos( $home, '//' ) + 2 );

	return md5( $id );
}

/**
 * Returns array with a set of site-related keys.
 *
 * @param $refresh
 * @param $nocache
 *
 * @return array
 */
function lab_get_key( $refresh = false, $nocache = false ): array {
	static $key = null;

	if ( ! isset( $key ) || $nocache ) {
		$key = cerber_get_set( LAB_SITE_KEY_DATA );
	}

	if ( $refresh
	     || ! $key
	     || ! is_array( $key )
	     || ! is_int( $key[1] ?? '' )
	     || empty( $key[4] ?? '' )
	     || ! crb_validate_alphanum( $key[0] ?? '' ) ) {

		if ( empty( $key ) || ! is_array( $key ) ) {
			$key = array( '' );
		}

		if ( ! crb_validate_alphanum( $key[0] )
		     || ( 2 < substr_count( cerber_get_site_url(), '/' ) ) ) { // Fix: WP is installed in a subdirectory, rewrite old, domain-based site ID
			$key[0] = lab_generate_site_id();
		}

		$key[1] = time();

		if ( empty( $key[4] ) ) {
			$key[4] = 'SK//' . str_shuffle( '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
		}

		cerber_update_set( LAB_SITE_KEY_DATA, $key );
	}

	return $key;
}

function lab_update_key( $lic, $expires = 0 ) {
	$key    = lab_get_key();
	$key[2] = strtoupper( $lic );
	$key[3] = absint( $expires );
	delete_site_option( LAB_SITE_KEY_DATA ); // deprecated
	cerber_update_set( LAB_SITE_KEY_DATA, $key );
	lab_get_key( false, true ); // reload the static cache
}

/**
 * Validates a license key against Cerber Lab's licensing system.
 *
 * On success, the message reference receives the formatted license expiration date.
 * On failure, the message reference receives diagnostic markers and, when available, the network error
 * details of this validation only.
 *
 * @param string $lic_key License key to validate, normalized to upper case. If empty, the currently stored license key is used.
 * @param string $msg Reference variable that receives the validation message or diagnostic details.
 * @param string $site_ip Reference variable that receives the site IP address detected during validation.
 *
 * @return bool True if the license key is confirmed valid and not expired, false otherwise.
 */
function lab_validate_lic( $lic_key = '', &$msg = '', &$site_ip = '' ) {
	global $cerber_lab_last_net_error, $cerber_lab_last_node_id;

	$msg = '';
	$lic_key = strtoupper( trim( (string) $lic_key ) );
	$is_candidate_key = ( $lic_key !== '' );
	$key_data = lab_get_key();

	if ( ! $is_candidate_key ) {
		if ( empty( $key_data[2] ) ) {
			$msg = '(1)';

			return false;
		}

		$lic_key = $key_data[2];
	}

	$request = array(
		'key'           => array_slice( $key_data, 0, 10, true ),
		'validate'      => $lic_key,
		'version'       => CERBER_VER,
		'site_url'      => cerber_get_site_url(),
		'site_url_raw'  => (string) get_option( 'siteurl' ),
		'site_home'     => cerber_get_home_url(),
		'site_home_raw' => (string) get_option( 'home' ),
		'multi'         => (bool) is_multisite(),
	);

	// Drop the diagnostic details left by the cloud requests made earlier in this PHP process.
	// Both globals are set by lab_send_request() and are never cleared by it, so without this
	// reset an unrelated failure is reported to the customer as a detail of this validation.

	$cerber_lab_last_net_error = '';
	$cerber_lab_last_node_id = 0;

	// Query the nodes one by one until one of them returns a verdict of the licensing system.
	// The sweep is limited by the number of the nodes only, so that a valid license key is
	// confirmed as long as at least one node is operational and the server environment allows
	// the sweep to complete.

	$lab_response = false;
	$has_verdict = false;
	$expires_gmt = 0;
	$nodes_tried = 0;

	foreach ( lab_get_node_sequence() as $node_id ) {

		$nodes_tried ++;

		if ( ! $node_answer = lab_send_request( $request, $node_id ) ) {
			continue;
		}

		// Keep the answer for diagnostics, it may carry no usable verdict at all

		$lab_response = $node_answer;

		// The expiration timestamp is the verdict of the licensing system. A node always returns it
		// as a number, and the zero value means the key is not valid, so no other node has to be
		// queried. A missing or non-numeric value is a broken answer of that node rather than a
		// rejection of the key, therefore the sweep goes on.

		$node_verdict = $node_answer['response']['expires_gmt'] ?? '';

		if ( is_numeric( $node_verdict ) ) {
			$expires_gmt = $node_verdict;
			$has_verdict = true;

			break;
		}
	}

	if ( ! empty( $cerber_lab_last_net_error ) ) {
		$msg .= $cerber_lab_last_net_error;
	}

	if ( ! $lab_response ) {
		cerber_admin_notice( __( 'A network error occurred while verifying the license key. Please try again in a few minutes.', 'wp-cerber' ) );
		$msg .= '(2)';
		$expires = 0;
	}
	elseif ( ! $has_verdict ) {
		cerber_admin_notice( __( 'A cloud error occurred while verifying the license key. Please try again in a few minutes.', 'wp-cerber' ) );
		$msg .= '(3)';
		$expires = 0;
	}
	else {
		$msg .= '(4)';
		$expires = absint( $expires_gmt );
	}

	// Update the status of the license key

	if ( ! $is_candidate_key
	     || $expires ) {
		lab_update_key( $lic_key, $expires );
	}

	// Prepare contextual data

	$site_ip = $lab_response['net_connection_ip'] ?? __( 'Unknown', 'wp-cerber' );

	if ( ! $expires ) {

		// Report the value the last answering node returned in place of the verdict. A broken answer
		// may carry a value of any type: only a number or a string is printable as it is, any other
		// type is reported by its name. That keeps a boolean from looking like a timestamp and keeps
		// a non-scalar away from crb_escape_html(), which throws a TypeError on PHP 8.

		$reported_verdict = crb_array_get( $lab_response, array( 'response', 'expires_gmt' ), '@' );

		if ( ! is_string( $reported_verdict )
		     && ! is_numeric( $reported_verdict ) ) {
			$reported_verdict = gettype( $reported_verdict );
		}

		$msg .= '(5.' . $nodes_tried . '.' . $cerber_lab_last_node_id . '.' . crb_escape_html( (string) $reported_verdict ) . ')';

		return false;
	}

	if ( time() > ( $expires + LAB_LICENSE_GRACE ) ) {
		$msg = '(6)';

		return false;
	}

	$msg = cerber_date( $expires, false );

	return true;
}

/**
 * Check if cloud services available
 *
 * @param int $with_date
 *
 * @return bool|int|string False means no cloud service available to use on this site
 */
function lab_lab( $with_date = 0 ) {
	static $exp;

	if ( ! isset( $exp ) ) {

		if ( $context = nexus_get_context() ) {
			if ( ! $context->site_key ) {
				$exp = false;
			}
			else {
				$exp = $context->site_key;
			}
		}
		else {

			$key = lab_get_key();

			if ( empty( $key[2] ) || empty( $key[3] ) ) {
				$exp = false;
			}
			else {
				$exp = $key[3];
			}
		}
	}

	if ( ! $exp ) {
		return false;
	}

	if ( time() > ( $exp + LAB_LICENSE_GRACE ) ) {
		return false;
	}

	if ( ! $with_date ) {
		return true;
	}

	if ( $with_date == 2 ) {
		return $exp;
	}

	return cerber_date( $exp, false );
}

function lab_indicator(){
	if ( lab_is_cloud_ok() && lab_lab() ) {
		$key = lab_get_key();
		$sid = 'Site ID: '.$key[0];
		return '<div title="'.$sid.'" style="margin-left:10px; float: right; font-weight: normal; font-size: 80%; padding: 0.35em 0.6em 0.35em 0.6em; color: #fff; background-color: #00ae65cc;"><i style="font-size:1.5em; vertical-align: top; line-height: 1;" class="crb-icon crb-icon-bxs-shield"></i></div>';
		//return '<div title="'.$sid.'" style="font-size: 80%; padding: 0.35em 0.6em 0.35em 0.6em; color: #fff; background-color: #00ae65cc;"><i style="font-size:1.5em; vertical-align: top; line-height: 1;" class="crb-icon crb-icon-bxs-shield"></i></div>';
		//return '<div title="'.$sid.'" style="float: right; font-weight: normal; font-size: 80%; padding: 0.35em 0.6em 0.35em 0.6em; color: #fff; background-color: #51AE43;"><span style="vertical-align: top; line-height: 1;" class="dashicons dashicons-yes"></span> Cerber Security Cloud Protection is active</div>';
	}

	return '';
}

/**
 * Opt in for the connection to Cerber Lab
 *
 */
function lab_show_opt_in_message(){

	if ( lab_lab() || crb_get_settings( 'cerberlab' ) ) {
		return;
	}

	if ( $o = get_site_option( '_lab_opt_in_' ) ) {
		if ( ( $o[1] + 30 * DAY_IN_SECONDS ) > time() ) {
			return;
		}
	}

	if ( ! crb_was_activated( WEEK_IN_SECONDS ) ) {
		return;
	}

	$heading = __( 'Want to make WP Cerber even more powerful?', 'wp-cerber' );
	$explanation = __( 'Allow WP Cerber to send locked out malicious IP addresses to Cerber Lab. This helps the plugin team to develop new algorithms for WP Cerber that will defend WordPress against new threats and botnets that are appearing  everyday. You can disable the sending in the plugin settings at any time.', 'wp-cerber' );
	$opt_in_label = __( 'OK, nail them all', 'wp-cerber' );
	$opt_out_label = __( 'NO, maybe later', 'wp-cerber' );

	// The two controls share the lab_action context and are told apart server side by their dismiss ID.

	$opt_in_element = crb_ui_element( 'div', array( 'style' => 'width: 70%; min-height: 200px;' ), array(
		crb_ui_element( 'h2', array(), $heading ),
		crb_ui_element( 'p', array(), $explanation ),
		crb_ui_element( 'p', array( 'style' => 'float:left;' ),
			crb_ui_link( 'https://wpcerber.com/cerber-laboratory/', __( 'Know more', 'wp-cerber' ), array( 'target' => '_blank' ) )
		),
		crb_ui_element( 'p', array( 'style' => 'text-align:right; margin-top: 2em;' ), array(
			crb_ui_dismiss_control( 'lab_action', $opt_in_label, 'lab_ok' ),
			// The renderer joins children without a separator, so the gap between the two buttons is an explicit child.
			' ',
			crb_ui_dismiss_control( 'lab_action', $opt_out_label, 'lab_no' ),
		) ),
	) );

	crb_show_admin_announcement( $opt_in_element, false );
}

/**
 * Save a user choice
 *
 * @param string $button
 */
function lab_user_opt_in( $button = '' ) {

	if ( ! in_array( $button, array( 'lab_ok', 'lab_no' ) ) ) {
		return;
	}

	if ( ! $settings = crb_get_settings() ) {
		return;
	}

	if ( $button == 'lab_ok' ) {
		$a = array( 'YES', time() );
		$settings['cerberlab'] = 1;
	}
	else {
		$a = array( 'NO', time() );
		$settings['cerberlab'] = 0;
	}

	cerber_settings_update( $settings, 'all' );
	update_site_option( '_lab_opt_in_', $a );
}


/**
 * Return country ISO code
 *
 * @param $ip array|string  IP address(es)
 * @param bool $cache_only  Use local cache. If false and an IP is not in the cache, sends a request to the Cerber Lab GEO service.
 *
 * @return array|string|false A list of country codes if a list of IPs provided, otherwise a string with the country code.
 */
function lab_get_country( $ip, $cache_only = true ) {
	global $remote_country;

	if ( ! lab_lab() ) {
		return false;
	}

	if ( ! is_array( $remote_country ) ) {
		$remote_country = array();
	}

	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$ip_id = cerber_get_id_ip( $ip );
		if ( isset( $remote_country[ $ip_id ] ) ) {
			return $remote_country[ $ip_id ];
		}
	}

	if ( ! is_array( $ip ) ) {
		$ip_list = array( $ip );
	}
	else {
		$ip_list = $ip;
	}

	$ret = array();
	$ask = array();

	foreach ( $ip_list as $item ) {
		if ( ! filter_var( $item, FILTER_VALIDATE_IP ) ) {
			continue;
		}

		$ip_id = cerber_get_id_ip( $item );
		$ret[ $ip_id ] = null;

		if ( is_ip_private( $item ) ) {
			continue;
		}

		if ( cerber_is_ipv4( $item ) ) {
			$ip_long = ip2long( $item );
			$where = ' WHERE ip_long_begin <= ' . $ip_long . ' AND ' . $ip_long . ' <= ip_long_end';
		}
		else {
			$where = ' WHERE ip = "' . $item . '"';
		}

		$country = cerber_db_get_var( 'SELECT country FROM ' . CERBER_LAB_NET_TABLE . $where );

		if ( $country ) {
			$ret[ $ip_id ] = $country;
		}
		elseif ( ! $cache_only ) {
			$ask[ $ip_id ] = $item;
		}
	}

	if ( ! $cache_only && $ask ) {
		$lab_data = lab_api_send_request( array( 'ask_cerberlab' => $ask ) );

		if ( ! empty( $lab_data['response']['payload'] ) ) {
			foreach ( $lab_data['response']['payload'] as $ip_id => $ip_data ) {
				//foreach ( $ask as $ip_id => $ip_ask ) {
				//if ( ! empty( $lab_data['response']['payload'][ $ip_id ] ) ) {
				//$ip_data = $lab_data['response']['payload'][ $ip_id ];
				lab_geo_update( $ip_data['ip'], $ip_data );
				lab_reputation_update( $ip_data['ip'], $ip_data );
				$ret[ $ip_id ] = crb_array_get( $ip_data, array( 'network', 'geo', 'country_iso' ), '' );
				//}
			}
		}

	}

	$remote_country = array_merge( $remote_country, $ret );

	if ( ! is_array( $ip ) ) {
		if ( ! empty( $ret ) ) {
			$ret = current( $ret );
		}
		else {
			$ret = '';
		}
	}

	return $ret;

}

/**
 * Update local GEO cache with a given network data
 *
 * @param string $ip IP address which country we asked for
 * @param array $data IP and its network data
 */
function lab_geo_update( $ip = '', $data = array() ) {
	global $remote_country;
	if ( empty( $data['network']['geo'] ) ) {
		return;
	}

	if ( ! is_array( $remote_country ) ) {
		$remote_country = array();
	}

	$code = substr( $data['network']['geo']['country_iso'], 0, 3 );
	$remote_country[ cerber_get_id_ip( $ip ) ] = $code;
	$expires = time() + absint( $data['network']['geo']['country_expires'] );
	$begin = intval( $data['network']['begin'] );
	$end = intval( $data['network']['end'] );

	if ( cerber_is_ipv4( $ip ) ) {
		$where = ' WHERE ip_long_begin = ' . $begin . ' AND ip_long_end = ' . $end;
		//$ip = '';
	}
	else {
		$where = ' WHERE ip = "' . $ip . '"';
	}

	$exists = cerber_db_get_var( 'SELECT ip FROM ' . CERBER_LAB_NET_TABLE . $where );

	if ( $exists ) {
		cerber_db_query( 'UPDATE ' . CERBER_LAB_NET_TABLE . " SET expires = $expires, country = '$code' $where" );
	}
	else {
		cerber_db_query( 'INSERT INTO ' . CERBER_LAB_NET_TABLE . " (ip, ip_long_begin, ip_long_end, country, expires) VALUES ('{$ip}',{$begin},{$end},'{$code}',{$expires})" );
	}

	// The list of names of the countries

	if ( ! empty( $data['network']['geo']['country'] ) ) {
		foreach ( $data['network']['geo']['country'] as $locale => $name ) {
			$where  = ' WHERE country = "' . $code . '" AND locale = "' . $locale . '"';
			$exists = cerber_db_get_var( 'SELECT country FROM ' . CERBER_GEO_TABLE . $where );

			if ( ! $exists ) {
				cerber_db_query( 'INSERT INTO ' . CERBER_GEO_TABLE . ' (country, locale, country_name) VALUES ("' . $code . '","' . $locale . '","' . $name . '")' );
			}
			else {
				//$wpdb->query( 'UPDATE ' . CERBER_GEO_TABLE . ' SET country_name = "' . $name . '"' . $where );
			}
		}
	}
}

function lab_cleanup_cache() {
	if ( ! cerber_user_can_manage() ) {
		return;
	}
	cerber_db_query( 'TRUNCATE TABLE ' . CERBER_LAB_NET_TABLE );
	cerber_db_query( 'TRUNCATE TABLE ' . CERBER_LAB_IP_TABLE );
}

/**
 * Return node ID for the current request if it is originated from the Cerber Cloud
 *
 * @return bool|int Node ID if the current request comes from a valid node or false otherwise
 */
function lab_get_real_node_id() {
	static $ret;

	if ( $ret !== null ) {
		return $ret;
	}

	$hostname = @gethostbyaddr( cerber_get_remote_ip() );
	if ( ! $hostname || filter_var( $hostname, FILTER_VALIDATE_IP ) ) {
		$ret = false;

		return $ret;
	}
	$domain = array_slice( explode( '.', $hostname ), - 3, 3 );
	if ( ! $domain || count( $domain ) != 3 ) {
		$ret = false;

		return $ret;
	}
	if ( $domain[1] . '.' . $domain[2] !== 'cerberlab.net' ) {
		$ret = false;

		return $ret;
	}

	$ret = absint( substr( $domain[0], 4, 2 ) ); // 0-99

	return $ret;
}

/**
 * Returns cached statistical site info
 *
 * @param bool $update If true, update (regenerate) the cache
 *
 * @return array
 */
function lab_get_site_meta( $update = true ) {

	if ( ! $update ) {
		$ret = cerber_get_set( CRB_SITE_SET, null, true, true );
	}
	else {
		$ret = false;
	}

	if ( empty( $ret ) || ! is_array( $ret ) ) {
		$ret = array(
			'lang'   => crb_get_bloginfo( 'language' ),
			'wp_ver' => cerber_get_wp_version(),
		);

		cerber_update_set( CRB_SITE_SET, $ret, null, true, time() + 7200, true );
	}

	return $ret;
}