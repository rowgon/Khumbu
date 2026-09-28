<?php

/**
 * Class Revalt
 *
 * A return-value object that carries the complete outcome of an operation:
 * the result payload, the error state, and an optional diagnostic chain
 * across architectural layers.
 *
 * Revalt always represents a COMPLETED operation. It is returned by value
 * and never passed down the call stack for lower-level code to populate.
 *
 * USAGE
 * -----
 * - Instantiate inside the function or method that owns the operation.
 * - Return the Revalt instance as the completed operation outcome.
 * - Manage successful results with put_results(), merge(), get_results().
 * - Handle errors with add_error(), get_error_messages(), get_error_data().
 * - Check error presence using has_errors() before reading results.
 * - When propagating errors across layers, see PROPAGATION PATTERNS below.
 *
 * AT A GLANCE
 * -----------
 * - Unified return type: callers always receive a Revalt, never a union of payload-or-error.
 * - Multiple errors per instance, each with a code, one or more messages, and arbitrary structured data.
 * - Execution stack: a Revalt may reference the Revalt returned by a lower layer, forming a single-linked chain similar to Exception::$previous.
 * - Root cause traversal: get_root_cause() returns the deepest error in the chain; get_stack() returns the full trace for logging.
 * - Result helpers for common payload shapes: get_element(), get_slice(), get_results_list(), count_results().
 *
 * CALLER CONTRACT
 * ---------------
 * Every caller MUST check has_errors() before using get_results().
 * A Revalt may technically hold both a payload and errors. Treat
 * has_errors() === true as authoritative: do not rely on get_results()
 * when errors are present unless the specific producer documents
 * partial-success semantics.
 *
 * PRODUCING A REVALT
 * ------------------
 * Success:
 *     return new Revalt( $results );
 *
 * Success, assembled incrementally:
 *     $result = new Revalt();
 *     $result->put_results( $partial );
 *     $result->merge( $more );
 *     return $result;
 *
 * Error:
 *     return new Revalt( null, 'unique_error_code', 'Human readable message' );
 *
 * Error with structured data:
 *     return new Revalt( null, 'invalid_input', 'Field foo is missing', array( 'field' => 'foo' ) );
 *
 *
 *  PROPAGATION PATTERNS
 *  --------------------
 *  When the current layer receives an erroring Revalt from below:
 *
 *  Pattern A. Pass through unchanged. Use when this layer adds no context:
 *
 *      $lower = lower_layer();
 *      if ( $lower->has_errors() ) {
 *          return $lower;
 *      }
 *
 *  Pattern B. Wrap with this layer's context. Use when the caller needs
 *  an error code at this layer's abstraction, while diagnostics still
 *  need to reach the original root cause:
 *
 *      $lower = lower_layer();
 *      if ( $lower->has_errors() ) {
 *          return new Revalt( null, 'upper_failed', 'Could not complete X', null, $lower );
 *      }
 *
 *  Pattern C. Mutate via add_error(). ONLY on a Revalt this layer created. Never on a Revalt returned from a lower layer.
 *
 * ANTI-PATTERNS
 * -------------
 * Do NOT mutate a Revalt received from a lower layer:
 *
 *     // WRONG
 *     $lower = lower_layer();
 *     $lower->add_error( 'upper_failed', '...' );
 *     return $lower;
 *
 * Do NOT call get_results() without checking has_errors() first.
 *
 * ERROR LOGGING (OPTIONAL)
 * ------------------------
 * Revalt can send errors to an external logger configured globally via
 * Revalt::set_error_logger(). Two modes:
 *
 * - Revalt::LOG_BUFFERED (default): errors accumulate in the Revalt instance and
 *   are sent to the logger only when log_errors() is explicitly called.
 *
 * - Revalt::LOG_INSTANT: errors are sent to the logger the moment add_error() is called.
 *
 * @version 8.6
 */
class Revalt {
	/**
	 * Stores the results of an operation, which may be a scalar value, object, or collection.
	 *
	 * @var mixed
	 *
	 */
	protected $operation_results = null;

	/**
	 * Stores the list of errors.
	 *
	 * @var array<string, string[]>
	 */
	protected array $errors = array();

	/**
	 * Stores optional error data for each error code.
	 *
	 * @var array<string, array>
	 */
	protected array $error_data = array();

	/**
	 * Reference to the previous Revalt instance in the execution stack.
	 *
	 * This property establishes a single directional chain of result objects.
	 * Each Revalt represents a logical layer and may reference the layer below it.
	 * The chain forms a simple linked structure similar to PHP Exception::$previous,
	 * but used here for propagating structured execution results instead of exceptions.
	 *
	 * It enables propagating low-level diagnostic information up the execution stack.
	 *
	 * Null if no execution stack was involved.
	 *
	 * @var Revalt|null
	 *
	 * @since 7.0
	 */
	protected ?Revalt $previous_result = null;

	const MAX_STACK_DEPTH = 100;

	/**
	 * Errors accumulate in the instance and are sent to the logger only when log_errors() is explicitly called. Default mode.
	 */
	const LOG_BUFFERED = 0;

	/**
	 * Errors are sent to the logger the moment add_error() is called.
	 */
	const LOG_INSTANT = 1;

	/** @var callable|null */
	private static $error_logger = null;

	/** @var int */
	private static int $log_mode = self::LOG_BUFFERED;

	/**
	 * Initializes the results of operations with optional error message and error data.
	 *
	 * @param mixed $results Results of operations.
	 * @param string $error_code Optional. Error code.
	 * @param string $error_message Optional. Error message.
	 * @param mixed $error_data Optional. Error data.
	 * @param Revalt|null $previous_result Optional. Reference to the previous Revalt instance in the execution stack. It enables context and error propagation in multi-level architectures.
	 *
	 *
	 */
	public function __construct( $results = null, string $error_code = '', string $error_message = '', $error_data = null, ?Revalt $previous_result = null ) {
		$this->operation_results = $results;
		$this->set_previous_result( $previous_result );

		if ( empty( $error_code ) ) {
			return;
		}

		$this->add_error( $error_code, $error_message, $error_data );
	}

	/**
	 * Put results of operations. Replaces existing ones.
	 *
	 * @param mixed $results Results of operations
	 * @param Revalt|null $previous_result Optional. Reference to the previous Revalt instance in the execution stack. It enables context and error propagation in multi-level architectures.
	 *
	 * @return void
	 */
	public function put_results( $results, ?Revalt $previous_result = null ) {
		$this->operation_results = $results;
		$this->set_previous_result( $previous_result );
	}

	/**
	 * Sets the previous operation result used as lower-level diagnostic context.
	 *
	 * @param Revalt|null $previous_result Reference to the previous Revalt instance in the execution stack.
	 *
	 * @return void
	 *
	 * @since 8.1
	 */
	private function set_previous_result( ?Revalt $previous_result = null ): void {

		if ( ! ( $previous_result instanceof Revalt )
		     || $previous_result === $this ) {
			return;
		}

		$this->previous_result = $previous_result;
	}

	/**
	 * Merges the elements of results and the given array together.
	 * Note: existing non-array (scalar) results will be destroyed.
	 *
	 * @param array $results Results of operations
	 * @param Revalt|null $previous_result Optional. Reference to the previous Revalt instance in the execution stack. It enables context and error propagation in multi-level architectures.
	 *
	 * @return void
	 */
	public function merge( array $results, ?Revalt $previous_result = null ) {
		if ( ! is_array( $this->operation_results ) ) {
			$this->operation_results = array();
		}

		$this->operation_results = array_merge( $this->operation_results, $results );
		$this->set_previous_result( $previous_result );
	}

	/**
	 * A handler for a fully successful operation.
	 *
	 * Note: It cleans all existing/accumulated errors and replaces the operation results with new results.
	 * Use this method if operations completely went well. For a partial success result, use Revalt::put_results() or new Revalt().
	 *
	 * @param mixed $results Results of operations
	 * @param Revalt|null $previous_result Optional. Reference to the previous Revalt instance in the execution stack. It enables context propagation in multi-level architectures.
	 *
	 * @return void
	 */
	public function success( $results, ?Revalt $previous_result = null ) {
		$this->put_results( $results, $previous_result );

		// Explicitly clean the error state, making the successful outcome self-contained.
		$this->errors = array();
		$this->error_data = array();
	}

	/**
	 * Returns the results of operations.
	 *
	 * @param mixed $default Default value returned when no result payload exists.
	 *
	 * @return mixed The result payload previously set by the constructor, Revalt::put_results(), Revalt::success(), or Revalt::merge().
	 */
	public function get_results( $default = null ) {
		if ( $this->operation_results === null ) {
			return $default;
		}

		return $this->operation_results;
	}

	/**
	 * Retrieve the preceding Revalt instance in the execution stack.
	 *
	 * This method allows traversing the result chain to get underlying results
	 * or to inspect the underlying diagnostic information (errors) of the current operation.
	 *
	 * @return Revalt|null Preceding Revalt instance. Null if this is the root of the stack OR no execution stack was involved.
	 *
	 * @since 7.0
	 */
	public function get_previous_result(): ?Revalt {
		return $this->previous_result;
	}

	/**
	 * Returns the results of operations as an array, ensuring the return type is always array.
	 *
	 * @param array $default Default value if no results.
	 *
	 * @return array Results of operations as an array.
	 */
	public function get_results_list( array $default = array() ): array {
		$val = $this->get_results( $default );

		if ( ! is_array( $val ) ) {
			$val = array( $val );
		}

		return $val;
	}

	/**
	 * Returns a slice of the results
	 *
	 * @param int $offset
	 * @param int|null $length
	 * @param bool $preserve_keys
	 *
	 * @return array
	 *
	 * @see array_slice()
	 *
	 */
	public function get_slice( int $offset, ?int $length, bool $preserve_keys = false ): array {
		if ( ! is_array( $this->operation_results ) ) {
			return array();
		}

		return array_slice( $this->operation_results, $offset, $length, $preserve_keys );
	}

	/**
	 * Returns an element from the results
	 *
	 * @param string|int $key The array key to return the value for
	 * @param mixed $default Default value if no element can be returned
	 *
	 * @return mixed Value from the results
	 *
	 */
	public function get_element( $key, $default = null ) {
		if ( ! is_array( $this->operation_results ) ) {
			return $default;
		}

		return $this->operation_results[ $key ] ?? $default;
	}

	/**
	 * Returns the number of elements in the results.
	 *
	 * Note: this method returns 1 if the stored result is a non-array value (e.g., string, int, or object)
	 *
	 * @return int
	 */
	public function count_results(): int {
		if ( $this->operation_results === null ) {
			return 0;
		}

		if ( is_array( $this->operation_results ) ) {
			return count( $this->operation_results );
		}

		return 1;
	}

	/**
	 * Retrieves all error codes.
	 *
	 *
	 * @return array List of error codes, if available.
	 */
	public function get_all_error_codes(): array {
		if ( ! $this->has_errors() ) {
			return array();
		}

		return array_keys( $this->errors );
	}

	/**
	 * Retrieves the first error code available.
	 *
	 *
	 * @return string Empty string, if no error codes.
	 */
	public function get_error_code(): string {
		$codes = $this->get_all_error_codes();

		if ( empty( $codes ) ) {
			return '';
		}

		return (string) $codes[0];
	}

	/**
	 * Retrieves all error messages, or the error messages for the given error code.
	 *
	 *
	 * @param string $code Optional. Error code to retrieve the messages for.
	 *                         Default empty string.
	 *
	 * @return string[] Error strings on success, or empty array if there are none.
	 *
	 */
	public function get_error_messages( string $code = '' ): array {
		// Return all messages if no code specified.
		if ( empty( $code ) ) {
			$all_messages = array();
			foreach ( $this->errors as $code => $messages ) {
				$all_messages = array_merge( $all_messages, $messages );
			}

			return $all_messages;
		}

		return $this->errors[ $code ] ?? array();
	}

	/**
	 * Gets a single error message.
	 *
	 * This will get the first message available for the code. If no code is
	 * given then the first code available will be used.
	 *
	 * @param string $code Optional. Error code to retrieve the message for.
	 *                         Default empty string.
	 *
	 * @return string The error message.
	 *
	 */
	public function get_error_message( string $code = '' ): string {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		$messages = $this->get_error_messages( $code );

		if ( empty( $messages ) ) {
			return '';
		}

		return $messages[0];
	}

	/**
	 * Retrieves the most recently added error data for an error code.
	 *
	 * @param string $code Optional. Error code. Default empty string.
	 *
	 * @return mixed Error data, if it exists, otherwise NULL.
	 *
	 */
	public function get_error_data( string $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		if ( ! empty( $this->error_data[ $code ] ) ) {
			$count = count( $this->error_data[ $code ] );
			return $this->error_data[ $code ][ $count - 1 ];
		}

		return null;
	}

	/**
	 * Retrieves all error data for an error code in the order in which the data was added.
	 *
	 * @param string $code Error code.
	 *
	 * @return array Array of error data, if it exists.
	 *
	 * @since 5.6.0
	 */
	public function get_all_error_data( string $code = '' ): array {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		return $this->error_data[ $code ] ?? array();
	}

	/**
	 * Verifies if the instance contains errors.
	 *
	 * @return bool If the instance contains errors.
	 *
	 * @since 5.1.0
	 */
	public function has_errors(): bool {
		if ( ! empty( $this->errors ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Adds an error or appends an additional message to an existing error.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param mixed $data Optional. Error data. Stored whenever it is not null.
	 * @param Revalt|null $previous_result Deprecated. Still honored for backward compatibility.
	 *
	 * @return void
	 *
	 */
	public function add_error( string $code, string $message, $data = null, ?Revalt $previous_result = null ) {
		$this->errors[ $code ][] = $message;

		if ( $data !== null ) {
			$this->add_error_data( $data, $code );
		}

		$this->set_previous_result( $previous_result );

		if ( self::$log_mode === self::LOG_INSTANT && self::$error_logger !== null ) {
			( self::$error_logger )( $code, $message, $data );
		}
	}

	/**
	 * Adds data to an error with the given code.
	 *
	 * @param mixed $data Error data.
	 * @param string $code Optional. Error code. Default empty string.
	 *                     When empty, the first existing error code is used (see get_error_code()).
	 *
	 * @return void
	 *
	 */
	public function add_error_data( $data, string $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		$this->error_data[ $code ][] = $data;
	}

	/**
	 * Get the entire execution stack as an array e.g. for debugging.
	 *
	 * Each entry describes one layer of the chain. The `data` key holds that layer's
	 * result payload (get_results()), not its error data.
	 *
	 * @return array<int, array{level: int, has_errors: bool, codes: array, messages: string[], data: mixed}>
	 *               List of all layers in the chain, starting from the current one (level 0).
	 *
	 * @since 7.0
	 */
	public function get_stack(): array {
		$trace = array();
		$current = $this;
		$level = 0;

		while ( $current instanceof Revalt
		        && $level < $this::MAX_STACK_DEPTH ) {
			$trace[] = array(
				'level'      => $level ++,
				'has_errors' => $current->has_errors(),
				'codes'      => $current->get_all_error_codes(),
				'messages'   => $current->get_error_messages(),
				'data'       => $current->get_results(),
			);

			$current = $current->get_previous_result();
		}

		return $trace;
	}

	/**
	 * Retrieves details of the deepest error in the execution stack (Root Cause).
	 *
	 * Traverses the stack downwards and finds the last Revalt instance that contains errors.
	 * Useful when the top-level error is generic (e.g., "Action failed") and you need
	 * the specific low-level reason (e.g., "DB Connection Refused").
	 *
	 * @return array{level: int, code: string, message: string, data: mixed}|array{} Empty array if no errors found.
	 *
	 * @since 7.0
	 */
	public function get_root_cause(): array {
		$root_cause = array();
		$current = $this;
		$level = 0;

		while ( $current instanceof Revalt
		        && $level < $this::MAX_STACK_DEPTH ) {

			if ( $current->has_errors() ) {
				$root_cause = array(
					'level'   => $level,
					'code'    => $current->get_error_code(),
					'message' => $current->get_error_message(),
					'data'    => $current->get_error_data(),
				);
			}

			$level ++;
			$current = $current->get_previous_result();
		}

		return $root_cause;
	}

	/**
	 * Configure error logging behavior for all Revalt instances.
	 *
	 * Note: this affects all instances.
	 *
	 * @param callable|null $logger Receives (string $code, string $message, mixed $data).
	 *                              Pass null to disable logging entirely.
	 * @param int $mode LOG_BUFFERED (default) or LOG_INSTANT.
	 *                              See the constants for behaviour details.
	 *
	 * @since 8.5
	 */
	public static function set_error_logger( ?callable $logger, int $mode = self::LOG_BUFFERED ): void {
		self::$error_logger = $logger;
		self::$log_mode = $mode;
	}

	/**
	 * Send all accumulated errors to the configured logger.
	 *
	 * Note: set_error_logger() must be called once, globally, beforehand to configure the logger.
	 *
	 * Walks the previous_result chain; each entry is prefixed with [L{n}],
	 * where n is the chain depth (L0 = current layer, higher = deeper).
	 *
	 * Note: if Revalt::LOG_INSTANT is enabled, this method may duplicate log entries.
	 *
	 * Does not clear the error state. No-op if no logger is configured.
	 *
	 * @since 8.5
	 */
	public function log_errors(): void {
		if ( self::$error_logger === null ) {
			return;
		}

		$current = $this;
		$level = 0;

		while ( $current instanceof Revalt
		        && $level < self::MAX_STACK_DEPTH ) {

			$prefix = '[L' . $level . '] ';

			foreach ( $current->errors as $code => $messages ) {
				// 1. Every message for the code. Nothing is dropped.
				foreach ( $messages as $message ) {
					( self::$error_logger )( $code, $prefix . $message, null );
				}

				// 2. Every accumulated data item, attached to the last message.
				$all_data = $current->get_all_error_data( $code );

				if ( ! empty( $all_data ) ) {
					$last_message = $messages[ array_key_last( $messages ) ];
					foreach ( $all_data as $data ) {
						( self::$error_logger )( $code, $prefix . $last_message, $data );
					}
				}
			}

			$level++;
			$current = $current->get_previous_result();
		}
	}
}