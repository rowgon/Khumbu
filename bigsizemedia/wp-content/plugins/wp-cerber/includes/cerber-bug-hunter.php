<?php
/**
 * Internal WP Cerber subsystem for collecting and processing PHP errors.
 *
 * The class has two purposes:
 *
 * 1. WP Cerber code QA. It captures PHP runtime errors, fatal shutdown errors, and
 *    uncaught Throwables, then persists and reports errors originating from the
 *    WP Cerber codebase for diagnostic analysis and code quality monitoring.
 *
 * 2. Traffic Inspector diagnostics. It keeps a request-wide buffer of PHP errors
 *    originating from any code executed during the request so Traffic Inspector can
 *    optionally store those errors as diagnostic context with the corresponding HTTP
 *    request record.
 *
 * These scopes are intentionally different. The in-memory request buffer is not
 * limited to WP Cerber code, while the persistent Bug Hunter error log and remote
 * reporting are restricted to errors originating from WP Cerber files.
 *
 * The installed PHP error and exception handlers act as diagnostic observers.
 *
 * @version 2.1
 *
 * @since 9.6.9.1
 */
final class CRB_Bug_Hunter {

	/**
	 * Log file name for PHP errors occurring in WP Cerber's code.
	 */
	public const CRB_ERROR_LOG = 'cerber-errors.log';

	/**
	 * Remote service URL for reporting PHP errors occurring in WP Cerber's code.
	 */
	public const SERVICE_URL = 'https://downloads.wpcerber.com/bughunter/';

	// Limits and settings
	public const BODY_SIZE_LIMIT = 64 * 1024; // 64KB
	public const MSG_MAX_LEN = 2048;
	public const FILE_MAX_LEN = 1024;
	public const URI_MAX_LEN = 1024;
	public const REMOTE_TIMEOUT = 3;
	public const DEFAULT_LINES_TO_KEEP = 50;

	/**
	 * PHP errors collected during the current request. Not limited to errors occurring in WP Cerber's code.
	 *
	 * Structure: [errno, message, file, line, uri]
	 *
	 * @var array<int, array{0: int, 1: string, 2: string, 3: int, 4: string}>
	 */
	private static array $collected_errors = [];

	/**
	 * Number of collected errors already processed by save_errors().
	 *
	 * Acts as a cursor into the collected errors: entries before it are persisted,
	 * entries starting at it are pending. Keeps the log write queue separate from
	 * the request-wide list of collected errors.
	 *
	 * @var int
	 */
	private static int $handled_error_count = 0;

	/**
	 * Whether an uncaught Throwable has been successfully captured by the exception handler.
	 *
	 * PHP reports the same failure again as a fatal error during shutdown, so the
	 * shutdown handler must skip it to avoid recording the incident twice. The flag
	 * stays unset when the capture itself fails, which keeps the shutdown handler
	 * available as a fallback.
	 *
	 * @var bool
	 */
	private static bool $throwable_reported = false;

	/**
	 * Index for deduplicating errors within a single request.
	 *
	 * @var array<string, bool>
	 */
	private static array $dedup_index = [];

	/**
	 * The folder where WP Cerber is installed. No trailing slash.
	 *
	 * @var string
	 */
	private static string $plugin_dir = '';

	/**
	 * Returns the full path to the WP Cerber error log file.
	 *
	 * @return string Empty string if the diagnostic directory is unavailable.
	 *
	 * @since 9.6.9.1
	 */
	private static function get_log_file_path(): string {

		$dir = crb_get_diag_dir();

		if ( ! $dir ) {
			return '';
		}

		return $dir . self::CRB_ERROR_LOG;
	}

	/**
	 * Reads all lines from the error log file.
	 *
	 * @return string[] Array of log lines or an empty array if unreadable.
	 *
	 * @since 9.6.9.1
	 */
	private static function read_log_lines(): array {

		$file_path = self::get_log_file_path();

		// Robustness: explicitly check availability and readability
		if ( ! $file_path || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return array();
		}

		$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		if ( ! is_array( $lines ) ) {
			return array();
		}

		return $lines;
	}

	/**
	 * Saves accumulated PHP errors occurring in the WP Cerber codebase to the WP Cerber error log file.
	 *
	 * Performs filtering, formatting (JSON), and thread-safe writing (flock).
	 *
	 * Only the errors that have not been processed yet are handled. The request-wide
	 * list of collected errors is preserved for the consumers that run later, such as
	 * the traffic logger, so this method may be safely called several times per request.
	 *
	 * @return void
	 *
	 * @since 9.6.9.1
	 */
	public static function save_errors(): void {
		// Take the errors that have not been written to the log file yet
		$errors = array_slice( self::$collected_errors, self::$handled_error_count );

		// Advance the cursor immediately to prevent duplicate processing on the subsequent calls
		self::$handled_error_count = count( self::$collected_errors );

		if ( empty( $errors ) ) {
			return;
		}

		$crb_errors = array();
		$php        = phpversion();
		$wp         = cerber_get_wp_version();

		foreach ( $errors as $error_info ) {

			if ( ! isset( $error_info[0], $error_info[1], $error_info[2], $error_info[3] ) ) {
				continue;
			}

			$err_level = $error_info[0];
			$msg       = $error_info[1];
			$file      = $error_info[2];
			$line      = $error_info[3];
			$uri       = $error_info[4] ?? '';

			if ( self::is_suppressed( $err_level, $msg ) ) {
				continue;
			}

			// We strictly log WP Cerber errors only

			if ( ! self::is_own_file( $file ) ) {
				continue;
			}

			$msg = substr( $msg, 0, self::MSG_MAX_LEN );
			$file = substr( $file, 0, self::FILE_MAX_LEN );
			$uri = substr( $uri, 0, self::URI_MAX_LEN );

			$crb_errors[] = array( $err_level, $msg, $file, $line, CERBER_VER, $php, $wp, $uri );
		}

		if ( empty( $crb_errors ) ) {
			return;
		}

		// One log entry can contain multiple errors
		$log_entry = array(
			'time'   => time(),
			'errors' => $crb_errors,
		);

		$json = json_encode( $log_entry, JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			return;
		}

		$file_path = self::get_log_file_path();
		if ( ! $file_path ) {
			return;
		}

		// Robustness: Check write permissions explicitly to avoid warnings
		if ( file_exists( $file_path ) && ! is_writable( $file_path ) ) {
			return;
		}
		// If file doesn't exist, check directory permissions
		if ( ! file_exists( $file_path ) ) {
			$dir = dirname( $file_path );
			if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
				return;
			}
		}

		$log = fopen( $file_path, 'a' );
		if ( false === $log ) {
			return;
		}

		// Robustness: Use explicit flock check. If locked, skip writing to avoid waiting/blocking.
		if ( flock( $log, LOCK_EX | LOCK_NB ) ) {
			fwrite( $log, $json . PHP_EOL );
			flock( $log, LOCK_UN );
		}

		fclose( $log );
	}

	/**
	 * Collects PHP errors.
	 *
	 * @param int    $errno   Error level (e.g., E_WARNING, E_NOTICE).
	 * @param string $errstr  Error message.
	 * @param string $errfile Filename where the error occurred.
	 * @param int    $errline Line number.
	 *
	 * @return void
	 */
	public static function collect_error( int $errno, string $errstr, string $errfile, int $errline ): void {
		// URI is intentionally excluded to deduplicate the same defect across different pages.
		$dedup_key = sha1( $errno . '|' . $errfile . '|' . $errline . '|' . $errstr );

		if ( isset( self::$dedup_index[ $dedup_key ] ) ) {
			return;
		}

		self::$dedup_index[ $dedup_key ] = true;

		$uri = $_SERVER['REQUEST_URI'] ?? '';

		self::$collected_errors[] = array( $errno, $errstr, $errfile, $errline, $uri );
	}

	/**
	 * Returns the list of currently collected PHP errors.
	 *
	 * @return array<int, array{0: int, 1: string, 2: string, 3: int, 4: string}>
	 */
	public static function get_collected_errors(): array {
		return self::$collected_errors;
	}

	/**
	 * Checks if there are any collected errors in the buffer.
	 *
	 * Optionally filters by specific error code(s).
	 *
	 * @param int|int[]|null $code Optional error code(s) to check for.
	 * See https://www.php.net/manual/en/errorfunc.constants.php
	 *
	 * @return bool True if relevant errors exist, false otherwise.
	 */
	public static function has_errors( $code = null ): bool {
		if ( empty( self::$collected_errors ) ) {
			return false;
		}

		if ( null === $code ) {
			return true;
		}

		// Extract error codes (index 0) from the collected errors
		$collected_codes = array_column( self::$collected_errors, 0 );

		if ( is_array( $code ) ) {
			return ! empty( array_intersect( $collected_codes, $code ) );
		}

		return in_array( $code, $collected_codes, true );
	}

	/**
	 * Captures a Throwable.
	 *
	 * Normalizes the exception/error into a standard array format and adds it to the internal buffer.
	 *
	 * @param Throwable $e The caught exception or error.
	 *
	 * @return void
	 */
	public static function collect_throwable( Throwable $e ): void {
		list( $errno, $errstr, $errfile, $errline ) = self::normalize_throwable( $e );

		self::collect_error( $errno, $errstr, $errfile, $errline );
	}

	/**
	 * Custom exception handler compatible with set_exception_handler().
	 *
	 * Logs the exception, immediately persists the buffer to disk, and re-throws the
	 * exception so that the standard PHP fatal error processing continues. Handling the
	 * exception without re-throwing it would consume the failure and leave error_get_last(),
	 * the PHP error log, the HTTP 500 status, the WordPress fatal error handler, and the
	 * theme and plugin file editor rollback mechanism without any information about it.
	 *
	 * A failure of the diagnostic persistence never prevents the exception from being
	 * re-thrown. PHP does not invoke the exception handler again for an exception thrown
	 * inside it, therefore the re-throw cannot cause recursion.
	 *
	 * @param Throwable $e The uncaught exception.
	 *
	 * @return void
	 *
	 * @throws Throwable The given exception is always re-thrown.
	 */
	public static function exception_handler( Throwable $e ): void {

		// Capture the failure where it occurs: at shutdown, error_get_last() may already be
		// overwritten by an unrelated error, or the shutdown chain may be aborted entirely
		try {
			self::collect_throwable( $e );

			// The direct capture succeeded, so the shutdown handler must not collect the same fatal again
			self::$throwable_reported = true;

			self::save_errors();
		}
		catch ( Throwable $diagnostic_failure ) {
			// Bug Hunter observes failures and must never be able to replace or hide them.
			// A capture failure leaves the flag unset, which keeps the shutdown path as a fallback.
		}

		// Resume the standard PHP fatal error processing
		throw $e;
	}

	/**
	 * Custom error handler compatible with set_error_handler().
	 *
	 * Collects all errors regardless of error_reporting level or the silence operator (@).
	 *
	 * @param int    $errno   The level of the error raised.
	 * @param string $errstr  The error message.
	 * @param string $errfile The filename that the error was raised in.
	 * @param int    $errline The line number the error was raised at.
	 *
	 * @return bool Always returns false to allow standard PHP error handling to continue.
	 */
	public static function error_handler( int $errno, string $errstr, string $errfile, int $errline ): bool {

		self::collect_error( $errno, $errstr, $errfile, $errline );

		// Allow standard PHP error handling to proceed
		return false;
	}

	/**
	 * Handles the final error from error_get_last() during script shutdown.
	 *
	 * This method should be called inside a register_shutdown_function() callback.
	 * It ensures that fatal errors (which are not caught by set_error_handler) are logged.
	 *
	 * An uncaught Throwable already captured by the exception handler is ignored here,
	 * because PHP reports the same incident again as a fatal error at this point.
	 *
	 * The caller must read error_get_last() before performing any shutdown work of its own and
	 * pass the captured value here. PHP keeps a single "last error" slot that every later
	 * diagnostic overwrites, including a silenced one, therefore an error read after other work
	 * has run is not necessarily the error that terminated the request. Such a snapshot is a
	 * best effort and not a guarantee: shutdown callbacks registered earlier, among them the
	 * WordPress core fatal error handler, run first and may already have replaced the slot.
	 *
	 * @param array{type: int, message: string, file: string, line: int}|null $error The error array provided by error_get_last().
	 *
	 * @return void
	 */
	public static function handle_shutdown_error( ?array $error ): void {
		// Avoid recording the incident already captured directly by the exception handler
		if ( self::$throwable_reported ) {
			return;
		}

		// The error handler catches regular runtime errors.
		// Here we only need to capture errors that set_error_handler cannot handle (e.g. E_ERROR, E_PARSE).
		$uncatchable_errors = array(
			E_ERROR,
			E_PARSE,
			E_CORE_ERROR,
			E_CORE_WARNING,
			E_COMPILE_ERROR,
			E_COMPILE_WARNING,
		);

		if ( $error
		     && isset( $error['type'], $error['message'], $error['file'], $error['line'] )
		     && in_array( $error['type'], $uncatchable_errors, true )
		) {
			self::collect_error(
				(int) $error['type'],
				(string) $error['message'],
				(string) $error['file'],
				(int) $error['line']
			);
		}
	}

	/**
	 * Converts a Throwable into a standard error array.
	 *
	 * Unwraps nested exceptions to find the root cause.
	 *
	 * @param Throwable $e
	 *
	 * @return array{0: int, 1: string, 2: string, 3: int}
	 */
	private static function normalize_throwable( Throwable $e ): array {
		while ( $e->getPrevious() instanceof Throwable ) {
			$e = $e->getPrevious();
		}

		$errstr  = $e->getMessage();
		$errfile = $e->getFile();
		$errline = (int) $e->getLine();

		if ( $e instanceof ErrorException ) {
			$errno = (int) $e->getSeverity();
			return array( $errno, $errstr, $errfile, $errline );
		}

		if ( $e instanceof Error ) {
			return array( E_ERROR, $errstr, $errfile, $errline );
		}

		return array( E_USER_ERROR, $errstr, $errfile, $errline );
	}

	/**
	 * Determines whether the given file is located in the WP Cerber plugin directory.
	 *
	 * @param string $file Absolute file path reported by PHP.
	 *
	 * @return bool True if the file resides within the WP Cerber directory tree. False otherwise.
	 *
	 * @since 9.6.16
	 */
	private static function is_own_file( string $file ): bool {
		if ( ! self::$plugin_dir ) {
			self::$plugin_dir = cerber_plugin_dir();
		}

		return strpos( $file, self::$plugin_dir ) === 0;
	}

	/**
	 * Checks whether a warning message can be safely suppressed to avoid log noise.
	 *
	 * @param int    $err_level  Error level reported by PHP.
	 * @param string $msg Error message text.
	 *
	 * @return bool True if the error matches a known suppressed warning. False otherwise.
	 *
	 * @since 9.6.16
	 */
	private static function is_suppressed( int $err_level, string $msg ): bool {

		if ( E_WARNING === $err_level
		     && false !== strripos( $msg, 'Permission denied' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Truncates the WP Cerber error log file, retaining the last N lines.
	 *
	 * @param int $lines_to_keep Number of lines to preserve (default: 50).
	 *
	 * @return void
	 *
	 * @since 9.6.9.1
	 */
	public static function truncate_log_file( int $lines_to_keep = self::DEFAULT_LINES_TO_KEEP ): void {

		$file_path = self::get_log_file_path();

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}

		// Robustness: Check write permissions before attempting to modify
		if ( ! is_writable( $file_path ) ) {
			return;
		}

		if ( ! $lines_to_keep ) {
			// Explicit empty string write, check result if needed, but file_put_contents usually sufficient here
			file_put_contents( $file_path, '' );
			return;
		}

		$lines = self::read_log_lines();

		if ( empty( $lines ) || count( $lines ) <= $lines_to_keep ) {
			return;
		}

		$lines_to_keep = max( 1, $lines_to_keep );
		$lines         = array_slice( $lines, - $lines_to_keep );

		$log = fopen( $file_path, 'w' );

		if ( false === $log ) {
			return;
		}

		// Robustness: Exclusive blocking lock for truncation (blocking is acceptable here for maintenance task)
		if ( flock( $log, LOCK_EX ) ) {
			foreach ( $lines as $line ) {
				fwrite( $log, $line . PHP_EOL );
			}
			flock( $log, LOCK_UN );
		}

		fclose( $log );
	}

	/**
	 * Executes maintenance tasks: flush errors and truncate the WP Cerber error log.
	 *
	 * @return void
	 *
	 * @since 9.6.9.1
	 */
	public static function run_maintenance_tasks(): void {
		self::flush_errors();
		self::truncate_log_file();
	}

	/**
	 * Sends accumulated WP Cerber PHP errors to the remote WP Cerber's collector.
	 *
	 * Performs data anonymization before sending.
	 *
	 * @return void
	 *
	 * @since 9.6.9.1
	 */
	public static function flush_errors(): void {
		$lines = self::read_log_lines();

		if ( empty( $lines ) ) {
			return;
		}

		$payload = array(
			'crb_version' => CERBER_VER,
			'wp_version'  => cerber_get_wp_version(),
			'revision'    => 1,
			'bug_pile'    => array(),
		);

		$root_dir = rtrim( ABSPATH, '/' );

		// Which entry we processed last time, if any
		$last_entry = (int) cerber_get_set( 'bug_hunter_last_processed', 0, false );
		$save_last  = 0;

		foreach ( $lines as $line ) {
			$entry = json_decode( $line, true );

			if ( json_last_error() !== JSON_ERROR_NONE
			     || empty( $entry['time'] )
			     || $entry['time'] <= $last_entry ) {
				continue;
			}

			// Remove sensitive data (anonymisation)
			if ( isset( $entry['errors'] ) && is_array( $entry['errors'] ) ) {
				foreach ( $entry['errors'] as &$error ) {
					// Ensure the error structure is valid before access
					if ( isset( $error[2] ) ) {
						$error[2] = str_replace( $root_dir, '', $error[2] );
					}
					if ( isset( $error[7] ) ) {
						$error[7] = crb_strip_query_params( $error[7], array(
							'page',
							'tab',
							'cerber_admin_do',
							'filter_activity',
							'filter_set',
						) );
					}
				}
				unset( $error ); // Break reference
			}

			// Build payload
			$payload['bug_pile'][] = $entry;
			$test_body             = json_encode( $payload, JSON_UNESCAPED_UNICODE );

			if ( false === $test_body || strlen( $test_body ) > self::BODY_SIZE_LIMIT ) {
				array_pop( $payload['bug_pile'] );
				break;
			}

			$save_last = $entry['time'];
		}

		if ( empty( $payload['bug_pile'] ) ) {
			return;
		}

		$body = json_encode( $payload, JSON_UNESCAPED_UNICODE );

		if ( false === $body ) {
			return;
		}

		$args = array(
			'body'    => $body,
			'headers' => array(
				'Content-Type'    => 'application/json',
				'X-Body-Checksum' => md5( $body ),
			),
			'timeout' => self::REMOTE_TIMEOUT,
		);

		$diag_enabled = defined( 'CERBER_NETWORK_DEBUG' ) && CERBER_NETWORK_DEBUG;

		if ( $diag_enabled ) {
			cerber_diag_log( 'Sending BugHunter payload (' . strlen( $body ) . ' bytes)', 'BugHunter' );
		}

		$response = wp_remote_post( self::SERVICE_URL, $args );

		if ( crb_is_wp_error( $response ) ) {
			if ( $diag_enabled ) {
				cerber_error_log( 'BugHunter request failed: ' . $response->get_error_message(), 'BugHunter' );
			}

			return;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code !== 200 ) {
			if ( $diag_enabled ) {
				cerber_error_log( 'BugHunter bad response code: ' . $code, 'BugHunter' );
			}
		} else {
			// HTTP 200 OK = SUCCESS
			// Set marker for the next submission
			cerber_update_set( 'bug_hunter_last_processed', $save_last, 0, false );
		}
	}

	/**
	 * Generates an HTML table view of the error log.
	 *
	 * @param string $item_class CSS class for log entry wrappers.
	 *
	 * @return string Formatted HTML content or empty string if no logs found.
	 *
	 * @since 9.6.9.1
	 */
	public static function create_log_view( string $item_class = '' ): string {

		$lines = self::read_log_lines();

		if ( empty( $lines ) ) {
			return '';
		}

		$entries  = array();
		$root_dir = rtrim( ABSPATH, '/' );

		foreach ( array_reverse( $lines ) as $line ) {
			$entry = json_decode( $line, true );

			if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $entry['time'], $entry['errors'] ) ) {
				continue;
			}

			$errors = '';

			foreach ( $entry['errors'] as $error ) {
				// Robustness: ensure array keys exist before access
				$level = isset( $error[0] ) ? cerber_get_err_level( $error[0] ) : 'Unknown';
				$file  = isset( $error[2] ) ? str_replace( $root_dir, '', $error[2] ) : '';
				$line  = isset( $error[3] ) ? crb_absint( $error[3] ) : 0;
				$msg   = isset( $error[1] ) ? str_replace( $root_dir, '', $error[1] ) : '';
				$ver   = $error[4] ?? '';
				$php   = $error[5] ?? '';
				$wp    = $error[6] ?? '';
				$uri   = $error[7] ?? '';

				$errors .= "Level:\t" . $level . '<br/>';
				$errors .= "File:\t" . crb_escape_html( $file ) . '<br/>';
				$errors .= "Line:\t" . $line . '<br/>';
				$errors .= "Error:\t" . crb_escape_html( $msg ) . '<br/>';
				$errors .= "Version:\t" . crb_escape_html( $ver ) . '<br/>';
				$errors .= "PHP:\t" . crb_escape_html( $php ) . '<br/>';
				$errors .= "WP:\t" . crb_escape_html( $wp ) . '<br/>';
				$errors .= "URI:\t" . crb_escape_html( $uri ) . '<br/>';
			}

			$entries[] = array(
				cerber_date( $entry['time'] ),
				'<div class="' . crb_boring_escape( $item_class ) . '" style="white-space: pre-wrap; tab-size: 10;">' . $errors . '</div>' . "\n",
			);
		}

		$ret = cerber_make_plain_table( $entries );

		return $ret;
	}
}
