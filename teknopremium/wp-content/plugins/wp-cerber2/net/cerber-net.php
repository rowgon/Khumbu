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
 * This class handles network requests using cURL library.
 *
 * @since 9.6.2.6
 */
class CRB_Net {

	/**
	 * Redirection messages (3xx) - Start with Capital letter, complete sentences.
	 * Context: "The server returned status code 301: [Message]"
	 */
	private const HTTP_REDIRECTION_MESSAGES = [
		301 => 'Moved Permanently. The requested resource has been assigned a new permanent URI.',
		302 => 'Found. The requested resource resides temporarily under a different URI.',
		304 => 'Not Modified. The resource has not been modified since the last request.',
	];

	/**
	 * Error messages (4xx, 5xx) - Start with 'a'/'an', continuation of sentence.
	 * Context: "This status indicates [message]"
	 */
	private const HTTP_ERROR_MESSAGES = [
		// 4xx: Client Errors
		400 => 'a Bad Request error. The server could not understand the request due to malformed syntax.',
		401 => 'an Unauthorized error. Authentication is required and has failed or has not yet been provided.',
		402 => 'a Payment Required error. Reserved for future use.',
		403 => 'a Forbidden error. The server understood the request but refuses to fulfill it.',
		404 => 'a Not Found error. The requested resource could not be found.',
		405 => 'a Method Not Allowed error. The request method is not supported for the target resource.',
		429 => 'a Too Many Requests error. The user has sent too many requests in a given amount of time.',

		// 5xx: Server Errors
		500 => 'an Internal Server Error. The server encountered an unexpected condition that prevented it from fulfilling the request.',
		502 => 'a Bad Gateway error. The server received an invalid response from an upstream server.',
		503 => 'a Service Unavailable error. The server is currently unable to handle the request (maintenance or overload).',
		504 => 'a Gateway Timeout error. The server did not receive a timely response from an upstream server.',
	];

	/**
	 * The URL of the request
	 */
	private string $url = '';

	/**
	 * CurlHandle class in PHP 8 and resource in PHP 7
	 *
	 * @var CurlHandle|resource|false|null
	 */
	private $curl = null;

	/**
	 * cURL result on any executed request
	 *
	 * @var bool|string
	 */
	private $result = '';

	/**
	 * Response HTTP headers
	 */
	private array $response_headers = array();

	/**
	 * Request response body
	 */
	private string $response_body = '';

	/**
	 * HTTP code of the last request
	 */
	private string $curl_error = '';

	/**
	 * HTTP code of the last request
	 */
	private int $code = 0;

	/**
	 * If true, save details info to the WP Cerber diagnostic log
	 *
	 * @var bool
	 */
	private bool $debug = false;

	/**
	 * If true, save headers into a variable
	 */
	private bool $include_headers = false;

	/**
	 * The full URL or its components
	 */
	private array $remote_location = [];

	/**
	 * Remote host for the current request
	 */
	private string $remote_host = '';

	/**
	 * Local, object-level cache of rate-limited hosts
	 *
	 */
	private array $rate_limited = [];

	/**
	 * Default cURL options
	 *
	 * @var array
	 */
	const CURL_DEFAULTS = array(
		CURLOPT_RETURNTRANSFER    => true,
		CURLOPT_CONNECTTIMEOUT    => 3,
		CURLOPT_TIMEOUT           => 6, // including CURLOPT_CONNECTTIMEOUT
		CURLOPT_DNS_CACHE_TIMEOUT => 4 * 3600,
	);

	const RATE_LIMIT_KEY = 'net_rate_limit_';

	/**
	 * @param bool $debug_log If true, debug information will be saved to the WP Cerber diagnostic log
	 */
	function __construct( bool $debug_log = false ) {
		if ( defined( 'CERBER_NETWORK_DEBUG' ) && CERBER_NETWORK_DEBUG ) {
			$debug_log = true;
		}

		$this->debug = $debug_log;
	}

	/**
	 * Results of the last cURL execution
	 *
	 * @return bool|string
	 */
	function get_result() {
		return $this->result;
	}

	/**
	 * Returns response HTTP headers, if they requested via parameter in http_get() method
	 *
	 * @return array
	 */
	function get_headers(): array {
		return $this->response_headers;
	}

	/**
	 * Returns response body
	 *
	 * @return string
	 */
	function get_body(): string {
		return $this->response_body;
	}

	/**
	 * HTTP code of the last request
	 *
	 * @return int
	 */
	function get_code(): int {
		return $this->code;
	}

	/**
	 * Perform an HTTP GET request to the given location.
	 *
	 * @param array{key?: string} $location The full URL or its components. Accepts the following keys:
	 *  - full_url (string): The full URL
	 *
	 *  OR
	 *
	 *  - scheme (string): Optional scheme of the URL (e.g., "http" or "https").
	 *  - host (string): The hostname of the URL.
	 *  - path (string): The path of the URL.
	 *  - query (string): The query string of the URL.
	 *
	 * @param array $options Optional. Additional cURL options for the request. Default is an empty array.
	 * @param bool $include_headers Optional. If true, save HTTP headers to a variable.
	 * @param int[] $acceptable_response_code Optional.
	 *
	 * @return true|WP_Error The result of the GET request. If an error occurs, a WP_Error object is returned.
	 */
	function http_get( array $location, array $options = array(), $include_headers = false, $acceptable_response_code = array( 200 ) ) {

		$url = $this->prepare_url( $location );

		if ( crb_is_wp_error( $url ) ) {
			return $url;
		}

		$this->url = $url;

		$this->debug_log( 'Preparing to send GET request to ' . $this->url );

		$mandatory = array( CURLOPT_USERAGENT => 'WordPress/' . get_bloginfo( 'version' ) . ';' );

		if ( $include_headers ) {
			$mandatory[ CURLOPT_HEADER ] = true;
		}

		$options = array( CURLOPT_URL => $this->url ) + $options + self::CURL_DEFAULTS + $mandatory;

		$result = $this->init_curl( $options );

		if ( crb_is_wp_error( $result ) ) {
			return $result;
		}

		$this->include_headers = (bool) $include_headers;

		$result = $this->send_request();

		if ( $this->code === 429 ) {
			$this->suspend_requests();
		}

		if ( crb_is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! in_array( $this->code, $acceptable_response_code ) ) {
			return $this->error( 'curl_http_error', $this->get_http_status_message( $this->code ) );
		}

		return true;
	}

	/**
	 * Sends an HTTP request using cURL and returns the result. It may contain HTTP headers.
	 *
	 * @return true|string|WP_Error The result of the network request or a WP_Error object if the request failed.
	 */
	private function send_request() {

		if ( $this->is_host_rate_limited() ) {
			return $this->error( 'location_throttled', 'Rate limit for the given host exceeded. Please try again later.' );
		}

		// Reset vars

		$this->response_headers = array();
		$this->response_body = '';

		$this->debug_log( 'Sending request to ' . $this->url );

		$this->result = curl_exec( $this->curl );

		$this->code = intval( curl_getinfo( $this->curl, CURLINFO_HTTP_CODE ) );

		$this->debug_log( 'Response HTTP code: ' . $this->code );
		//$this->debug_log( 'cURL result: ' . (string) $this->result );

		$this->curl_error = curl_error( $this->curl );

		// Note: this code works when CURLOPT_RETURNTRANSFER is enabled

		if ( $this->result === false
		     || $this->curl_error ) {
			$info = $this->curl_error ? ' (' . $this->curl_error . ')' : '';
			$info .= ' HTTP CODE ' . $this->code;

			$this->curl = null;

			return $this->error( 'curl_net_error', 'Network request failed.' . $info );
		}

		// Extract headers

		if ( $this->include_headers ) {
			$h_size = curl_getinfo( $this->curl, CURLINFO_HEADER_SIZE );
			$this->response_body = substr( $this->result, $h_size );
			$headers = substr( $this->result, 0, $h_size );

			$header_lines = explode( "\r\n", trim( $headers ) );

			foreach ( $header_lines as $header_line ) {
				$parts = explode( ':', $header_line, 2 );
				if ( count( $parts ) == 2 ) {
					$this->response_headers[ trim( $parts[0] ) ] = trim( $parts[1] );
				}
			}
		}
		else {
			$this->response_body = (string) $this->result;
		}

		$this->curl = null;

		return $this->result;
	}

	/**
	 * Initializes and configures a cURL session with the provided options.
	 *
	 * @param array $options An array containing the cURL options to be set.
	 *
	 * @return bool|WP_Error True if the cURL session is successfully initialized and configured, WP_Error otherwise
	 */
	private function init_curl( array $options = array() ) {

		if ( ! $this->curl = curl_init() ) {
			return $this->error( 'curl_init_error', 'Unable to initialize cURL PHP library' );
		}

		if ( ! crb_configure_curl( $this->curl, $options ) ) {

			$this->curl = null;

			return $this->error( 'curl_init_error', 'Unable to set network options for cURL' );
		}

		return true;
	}

	/**
	 * Create the URL for the given location.
	 *
	 * @param array{key?: string} $location The full URL or its components. Accepts the following keys:
	 * - full_url (string): The full URL
	 *
	 * OR
	 *
	 * - scheme (string): Optional scheme of the URL (e.g., "http" or "https").
	 * - host (string): The hostname of the URL.
	 * - path (string): The path of the URL.
	 * - query (string): The query string of the URL.
	 *
	 * @return string|WP_Error The valid URL generated from the given location. If the URL is invalid, a WP_Error object is returned.
	 */
	private function prepare_url( array $location ) {

		static $defaults = array(
			'full_url' => '',
			'scheme'   => 'https',
			'host'     => '',
			'path'     => '',
			'query'    => '',
		);

		$this->remote_location = [];
		$this->remote_host = '';

		$location = array_merge( $defaults, array_filter( $location, 'is_string' ) );

		if ( ! $url = $location['full_url'] ?? '' ) {

			if ( ! $hostname = $this->validate_hostname( $location['host'] ) ) {
				return $this->error( 'invalid_hostname', 'Invalid hostname specified.' );
			}

			$this->remote_host = $hostname;

			$url = $location['scheme'] . '://' . rtrim( $hostname, '/' ) . '/' . ltrim( $location['path'], '/' ) . $location['query'];
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return $this->error( 'invalid_url', 'Invalid URL specified.' );
		}

		$this->remote_location = $location;

		return $url;
	}

	/**
	 * Check whether sending requests to the host is rate limited at the moment or not.
	 *
	 * @return bool True if sending requests to the host is not allowed at the moment.
	 */
	function is_host_rate_limited(): bool {

		if ( ! $host = $this->get_remote_host() ) {
			return false;
		}

		$saved = false;

		if ( ! $expires = $this->rate_limited[ $host ] ?? 0 ) {
			if ( ! $expires = cerber_get_set( self::RATE_LIMIT_KEY . $host, null, false, true ) ) {
				return false;
			}

			$saved = true;
		}

		if ( $expires > time() ) {

			// Populate local cache

			$this->rate_limited[ $host ] = $expires;

			return true;
		}

		// Expired, clear restrictions

		if ( $saved ) {
			cerber_delete_set( self::RATE_LIMIT_KEY . $host );
		}

		$this->rate_limited[ $host ] = 0;

		return false;
	}

	/**
	 * Pause sending requests to a rate limited host
	 *
	 * @return void
	 */
	private function suspend_requests() {

		if ( ! $host = $this->get_remote_host() ) {
			return;
		}

		if ( $this->rate_limited[ $host ] ) {
			return;
		}

		if ( $this->rate_limited[ $host ] = cerber_get_set( self::RATE_LIMIT_KEY . $host, null, false ) ) {
			return;
		}

		$expires = time() + 3 * 60;

		$this->rate_limited[ $host ] = $expires;

		cerber_update_set( self::RATE_LIMIT_KEY . $host, $expires, null, false, $expires, true );
	}

	/**
	 * Retrieves the remote host for the current request
	 *
	 * If the host is not already set in the location array, it will lazily fetch
	 * the host from the URL using the `parse_url()` function.
	 *
	 * @return string The valid remote host.
	 */
	private function get_remote_host(): string {

		if ( isset( $this->remote_host ) ) {
			return $this->remote_host;
		}

		if ( ! $this->remote_host = $this->remote_location['host'] ) {
			$this->remote_host = (string) parse_url( $this->url, PHP_URL_HOST );
		}

		return $this->remote_host;
	}

	/**
	 * Generate a WP_Error object for sending request errors.
	 *
	 * @param string $code Error code to be set for the WP_Error object.
	 * @param string $message Error message to be set for the WP_Error object.
	 *
	 * @return WP_Error WP_Error object representing the request error.
	 */
	private function error( string $code, string $message = '' ): WP_Error {

		$this->debug_log( $message, true );

		return new WP_Error( $code, $message );
	}

	/**
	 * Returns a human-readable message for HTTP status codes.
	 *
	 * @param int $code The HTTP status code.
	 *
	 * @return string A message explaining the status code.
	 */
	private function get_http_status_message( int $code ): string {
		// 1. Handle Redirections (3xx)
		if ( isset( self::HTTP_REDIRECTION_MESSAGES[ $code ] ) ) {
			return 'The server returned status code ' . $code . ': ' . self::HTTP_REDIRECTION_MESSAGES[ $code ];
		}

		// 2. Handle Errors (4xx, 5xx) & Unknowns
		$description = self::HTTP_ERROR_MESSAGES[ $code ] ?? 'an unknown error occurred.';

		return 'The HTTP request failed with status code ' . $code . '. This status indicates ' . $description;
	}

	/**
	 * Validate hostname
	 *
	 * @param string $hostname The hostname to validate.
	 *
	 * @return string|false Return a string with the hostname if it's valid, false otherwise.
	 */
	function validate_hostname( string $hostname ) {

		if ( $ret = filter_var( $hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			return $ret;
		}

		if ( $ret = filter_var( $hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) ) {
			return $ret;
		}

		return false;
	}

	/**
	 * Log debug information.
	 *
	 * @param string $string The text to be logged.
	 * @param bool $error Optional. Whether the text is an error or not. Default is false.
	 *
	 * @return void
	 */
	private function debug_log( string $string, bool $error = false ) {
		if ( ! $this->debug ) {
			return;
		}

		if ( $error ) {
			cerber_error_log( $string, 'NETWORK' );
		}
		else {
			cerber_diag_log( $string, 'NETWORK' );
		}
	}
}