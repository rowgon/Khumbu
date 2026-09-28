<?php

/**
 * Class CRB_Deferred_Tasks
 *
 * Deferred task queue for the WordPress admin context.
 *
 * Manages a best-effort queue of deferred tasks to keep interactive screens responsive
 * by postponing non-critical work.
 *
 * Intended Use:
 * - UI hydration and secondary refresh work.
 * - Maintenance tasks safe to run on a best-effort basis.
 *
 * Constraints & Limitations:
 * - Tasks must be idempotent and tolerate interruption.
 * - Not suitable for long-running jobs or strictly ordered operations.
 * - No execution guarantees; designed for opportunistic runs.
 *
 * Architecture:
 * - Tasks are stored via a global key with deterministic IDs.
 * - Features fault isolation, execution logging, and zombie task protection.
 *
 */
class CRB_Deferred_Tasks {

	/**
	 * Storage key for persisting tasks in the database.
	 */
	const STORAGE_KEY = '_background_tasks';

	/**
	 * Storage key for persisting the execution log of the last batch.
	 */
	const LOG_STORAGE_KEY = '_background_tasks_log';

	/**
	 * Maximum number of execution attempts for tasks with conditional execution (exec_until).
	 * Prevents infinite loops (Zombie tasks).
	 */
	const MAX_RETRIES = 5;

	/**
	 * Executes background tasks stored in the queue.
	 *
	 * Optionally filters execution to a specific set of tasks provided in the list.
	 * Returns a structured execution log where each entry is an associative array.
	 * Saves the execution log to the database for historical reference of the last batch.
	 *
	 * @param array<int, string>|null $tasks_to_run A list of Task IDs to be executed.
	 * If null or empty, all queued tasks are executed.
	 *
	 * @return array<string, array<string, mixed>> Log of executed tasks.
	 * Structure: ['status' => 'executed'|'error'|'aborted', 'result' => mixed, 'output' => string, 'error' => string|null]
	 *
	 * @since 8.6.4
	 */
	public static function launcher( ?array $tasks_to_run = null ): array {
		$execution_log = array();

		if ( ! $task_list = self::get_all() ) {
			// Save empty log to reflect that nothing ran
			cerber_update_set( self::LOG_STORAGE_KEY, $execution_log );

			return $execution_log;
		}

		if ( ! empty( $tasks_to_run ) ) {
			// Filter the main list to include only keys present in the provided list of IDs
			// We flip the list of IDs to use array_intersect_key for efficient hash lookup
			$task_list = array_intersect_key( $task_list, array_flip( $tasks_to_run ) );
		}

		if ( empty( $task_list ) ) {
			// Save empty log to reflect that nothing ran after filtering
			cerber_update_set( self::LOG_STORAGE_KEY, $execution_log );

			return $execution_log;
		}

		// Orchestration Loop: Delegates specific task processing to a dedicated method
		foreach ( $task_list as $task_id => $task_details ) {
			$execution_log[ $task_id ] = self::process_single_task( (string) $task_id, $task_details );
		}

		// Persist the execution log of the current batch
		cerber_update_set( self::LOG_STORAGE_KEY, $execution_log );

		return $execution_log;
	}

	/**
	 * Retrieves all pending background tasks from storage.
	 *
	 * @return array<string, array> An associative array where key is Task ID and value is task details.
	 *
	 * @since 8.6.4
	 */
	public static function get_all(): array {
		$tasks = cerber_get_set( self::STORAGE_KEY );

		if ( ! $tasks || ! is_array( $tasks ) ) {
			$tasks = array();
		}

		return $tasks;
	}

	/**
	 * Retrieves the execution log of the last batch run.
	 *
	 * @return array<string, array<string, mixed>> The log of the last executed batch.
	 * Returns an empty array if no log exists or it is invalid.
	 *
	 * @since 9.6.12
	 */
	public static function get_last_execution_log(): array {
		$log = cerber_get_set( self::LOG_STORAGE_KEY );

		if ( ! $log || ! is_array( $log ) ) {
			return array();
		}

		return $log;
	}

	/**
	 * Adds a new background task to the execution queue.
	 *
	 * Uses deterministic ID generation to prevent duplicates even if parameter order varies.
	 * It constructs a strictly typed task structure to avoid garbage data.
	 * Restricts callbacks to strings or static methods (array of strings) for safe serialization.
	 *
	 * @param callable|string|array $callback The function or method to be executed.
	 * Must be a string or a static method array ['Class', 'Method'].
	 * Closures and Object instances are NOT allowed.
	 * @param array $task_options Configuration for the task.
	 * Supported keys:
	 * - 'args': (array) Parameters to pass to the callback.
	 * - 'load_admin': (bool) Whether to load admin code before execution.
	 * - 'exec_until': (mixed) Result condition for task deletion.
	 * - 'return': (bool) If true, captures return value.
	 * - 'run_js': (string) JavaScript to run after execution.
	 * - 'retry_limit': (int) Optional custom limit for execution attempts (overrides default).
	 * @param bool $is_high_priority If true, prepends the task to the beginning of the queue.
	 *
	 * @return bool|WP_Error True if the task was successfully added, false if it already exists, or WP_Error on failure.
	 *
	 * @since 8.6.4
	 */
	public static function add( $callback, array $task_options = array(), bool $is_high_priority = false ) {

		// 1. Security Constraint: Only allow serializable callbacks (Strings or Static Methods)
		// We explicitly reject Closures and Object instances to prevent serialization issues and side effects.
		$is_valid_type = is_string( $callback ) || ( is_array( $callback ) && count( $callback ) === 2 && is_string( $callback[0] ) && is_string( $callback[1] ) );

		if ( ! $is_valid_type ) {
			return new WP_Error( 'bg_tasks_invalid_type', 'Invalid callback type. Only strings and static methods are allowed.' );
		}

		// 2. Validation: Ensure it is actually callable
		if ( ! is_callable( $callback ) ) {
			return new WP_Error( 'bg_tasks_nope', 'Specified function ' . crb_make_callable_name( $callback ) . ' is not callable or not defined.' );
		}

		// 3. Construct a strictly typed task structure to avoid garbage data.
		// We use null coalescing to provide safe defaults (Pragmatism).
		$task_details = array(
			'func'        => $callback,
			'args'        => isset( $task_options['args'] ) && is_array( $task_options['args'] ) ? $task_options['args'] : array(),
			'load_admin'  => ! empty( $task_options['load_admin'] ),
			'exec_until'  => $task_options['exec_until'] ?? null,
			'return'      => ! empty( $task_options['return'] ),
			'run_js'      => $task_options['run_js'] ?? '',
			'retry_limit' => isset( $task_options['retry_limit'] ) ? (int) $task_options['retry_limit'] : null,
			'retry_count' => 0, // Initialize retry counter for zombie protection
		);

		// Remove null values to save space, keeping logic consistent with legacy behavior
		if ( is_null( $task_details['exec_until'] ) ) {
			unset( $task_details['exec_until'] );
		}
		if ( is_null( $task_details['retry_limit'] ) ) {
			unset( $task_details['retry_limit'] );
		}

		// 4. Ensure Determinism: Sort keys recursively to guarantee the same ID for the same data.
		// We sort the details directly as the stored order does not affect logic, but reduces noise.
		self::recursive_ksort( $task_details );

		$task_id = sha1( serialize( $task_details ) );

		// 5. Fetch current state only after preparation is done
		$current_task_list = self::get_all();

		if ( isset( $current_task_list[ $task_id ] ) ) {
			return false;
		}

		// 6. Update the list efficiently
		if ( $is_high_priority ) {
			// Union operator (+) is faster/cleaner than array_merge for associative arrays where we want to prepend
			$current_task_list = array( $task_id => $task_details ) + $current_task_list;
		}
		else {
			$current_task_list[ $task_id ] = $task_details;
		}

		return cerber_update_set( self::STORAGE_KEY, $current_task_list );
	}

	/**
	 * Removes a specific background task from the queue.
	 *
	 * @param string $task_id The unique identifier of the task to delete.
	 *
	 * @return bool True if the task was found and deleted, false otherwise.
	 *
	 * @since 8.6.4
	 */
	public static function delete( string $task_id = '' ): bool {

		if ( ! $current_task_list = self::get_all() ) {
			return false;
		}

		if ( ! isset( $current_task_list[ $task_id ] ) ) {
			return false;
		}

		unset( $current_task_list[ $task_id ] );

		return cerber_update_set( self::STORAGE_KEY, $current_task_list );
	}

	/**
	 * Processes a single background task.
	 *
	 * Encapsulates validation, execution, error handling, and lifecycle management (retries/deletion)
	 * for a single unit of work.
	 *
	 * @param string $task_id The unique ID of the task.
	 * @param array<string, mixed> $task_details The task configuration and state.
	 *
	 * @return array<string, mixed> The execution result log entry.
	 *
	 * @since 9.6.12
	 */
	private static function process_single_task( string $task_id, array $task_details ): array {
		$callback = crb_array_get( $task_details, 'func' );

		// 1. Validation: Ensure callback is executable
		if ( ! is_callable( $callback ) ) {
			$error_message = 'Function ' . crb_make_callable_name( $callback ) . ' is not callable or not defined';
			cerber_error_log( $error_message, 'BG TASK' );
			self::delete( $task_id );

			return array(
				'status' => 'error',
				'error'  => $error_message,
			);
		}

		// 2. Garbage Collection: Remove tasks missing required 'exec_until' logic if implicitly expected
		// Note: Legacy behavior dictates deletion, but execution proceeds once.
		if ( ! isset( $task_details['exec_until'] ) ) {
			self::delete( $task_id );
		}

		// 3. Environment Setup
		if ( ! empty( $task_details['load_admin'] ) ) {
			cerber_load_admin_code();
		}

		// 4. Execution
		$callback_arguments = crb_array_get( $task_details, 'args', array() );

		try {
			nexus_diag_log( 'Launching bg task: ' . crb_make_callable_name( $callback ) );

			ob_start();
			$execution_result = call_user_func_array( $callback, $callback_arguments );
			$output_buffer = ob_get_clean();

			// 5. Post-Execution Logic
			if ( isset( $task_details['exec_until'] ) ) {
				if ( $task_details['exec_until'] === $execution_result ) {
					// Task completed successfully based on condition
					self::delete( $task_id );
				}
				else {
					// Logic for Zombie Tasks (Infinite Loop Protection)
					$current_retries = (int) ( $task_details['retry_count'] ?? 0 ) + 1;
					$max_retries = (int) ( $task_details['retry_limit'] ?? self::MAX_RETRIES );

					if ( $current_retries >= $max_retries ) {
						$abort_message = 'Task ' . $task_id . ' aborted: execution limit reached (' . $max_retries . ' attempts).';
						cerber_error_log( $abort_message, 'BG TASK' );
						self::delete( $task_id ); // Kill the zombie

						return array(
							'status' => 'aborted',
							'error'  => $abort_message,
							'result' => $execution_result,
							'output' => $output_buffer,
						);
					}
					else {
						// Update retry counter for the next run
						self::update( $task_id, array( 'retry_count' => $current_retries ) );
					}
				}
			}

			$log_entry = array(
				'status' => 'executed',
				'result' => $execution_result,
				'run_js' => crb_array_get( $task_details, 'run_js' ),
				'output' => $output_buffer,
			);

			// Capture WP_Error message if present
			if ( $execution_result instanceof WP_Error ) {
				$log_entry['error'] = $execution_result->get_error_message();
			}

			return $log_entry;

		} catch ( \Throwable $e ) {
			// Critical Fault Isolation
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			$error_message = 'Task execution failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
			cerber_error_log( $error_message, 'BG TASK' );

			return array(
				'status' => 'error',
				'error'  => $error_message,
			);
		}
	}

	/**
	 * Updates specific fields of an existing task.
	 *
	 * Useful for persisting state changes like retry counters without re-adding the task.
	 *
	 * @param string $task_id The ID of the task to update.
	 * @param array<string, mixed> $changes Associative array of fields to update.
	 *
	 * @return bool True on success, false if task not found.
	 *
	 * @since 9.6.12
	 */
	private static function update( string $task_id, array $changes ): bool {
		$task_list = self::get_all();

		if ( ! isset( $task_list[ $task_id ] ) ) {
			return false;
		}

		// Merge changes into existing task details
		$task_list[ $task_id ] = array_merge( $task_list[ $task_id ], $changes );

		return cerber_update_set( self::STORAGE_KEY, $task_list );
	}

	/**
	 * Helper to recursively sort array by keys for deterministic serialization.
	 *
	 * @param array $array Reference to the array to sort.
	 *
	 * @return void
	 *
	 * @since 9.6.12
	 */
	private static function recursive_ksort( array &$array ): void {
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				self::recursive_ksort( $value );
			}
		}
		ksort( $array );
	}
}