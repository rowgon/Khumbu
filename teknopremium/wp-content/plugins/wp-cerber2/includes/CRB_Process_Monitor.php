<?php

/**
 *
 * Standardized wrapper for tracking background processes and metrics.
 * Encapsulates state management, timing, and storage persistence.
 *
 * ACTS AS A FULL MANAGER (CQRS Pattern):
 * - Instantiate via 'new' to START tracking a process (Write mode).
 * - Use static methods to RETRIEVE data (Read mode).
 *
 * Features:
 * - Uses internal Cerber storage API (cerber_get_set / cerber_update_set).
 * - Strict input validation (Allow List strategy).
 * - Fail-safe behavior (silently disables on error).
 *
 * @since 9.6.14
 */
class CRB_Process_Monitor {

	/**
	 * Keys managed internally by the class.
	 */
	private const RESERVED_KEYS = [ 'started_at', 'finished_at', 'duration' ];

	/**
	 * Limits and Patterns.
	 */
	private const MAX_METRIC_KEY_LENGTH  = 64;
	private const MAX_STORAGE_KEY_LENGTH = 64;
	private const STORAGE_KEY_PATTERN    = '/^[a-zA-Z0-9_\-]+$/';

	/** @var string */
	private string $storage_key = '';

	/** @var array */
	private array $data = [];

	/** @var int */
	private int $start_time = 0;

	/** @var bool */
	private bool $is_valid = false;

	/**
	 * CRB_Process_Monitor constructor (WRITE MODE).
	 *
	 * Calling this starts a NEW tracking session, overwriting previous data
	 * for this key via cerber_update_set().
	 *
	 * @param string $storage_key The unique key for Cerber storage.
	 */
	public function __construct( string $storage_key ) {
		// Validate key using centralized static helper
		if ( ! self::is_key_valid( $storage_key ) ) {
			$this->disable_instance();
			return;
		}

		$this->is_valid    = true;
		$this->storage_key = trim( $storage_key );
		$this->start_time  = time();

		// Initialize default deterministic structure
		$this->data = [
			'started_at' => $this->start_time,
			'duration'   => 0,
		];

		// Persist initial state immediately
		$this->save();
	}

	/**
	 * --- READER METHODS (STATIC) ---
	 */

	/**
	 * Retrieves the current status snapshot for a given process key using cerber_get_set().
	 *
	 * @param string $storage_key The unique key.
	 * @param array  $default     Value to return if no data exists or error occurs.
	 *
	 * @return array
	 */
	public static function get_status( string $storage_key, array $default = [] ): array {
		if ( ! self::is_key_valid( $storage_key ) ) {
			return $default;
		}

		$storage_key = trim( $storage_key );

		if ( ! function_exists( 'cerber_get_set' ) ) {
			return $default;
		}

		// Retrieve data using Cerber API
		// Defaults: id=0, unserialize=true, use_cache=null
		$data = cerber_get_set( $storage_key );

		// cerber_get_set returns false if key doesn't exist or expired
		if ( false === $data ) {
			return $default;
		}

		// Ensure we strictly return an array
		return is_array( $data ) ? $data : $default;
	}

	/**
	 * Retrieves a specific metric from the status snapshot.
	 *
	 * @param string $storage_key The unique key.
	 * @param string $metric_key  The specific data point.
	 * @param mixed  $default     Value to return if not found.
	 *
	 * @return mixed
	 */
	public static function get_metric( string $storage_key, string $metric_key, $default = null ) {
		$status = self::get_status( $storage_key );

		return $status[ $metric_key ] ?? $default;
	}

	/**
	 * Checks if a valid status exists for this key.
	 *
	 * @param string $storage_key
	 *
	 * @return bool
	 */
	public static function exists( string $storage_key ): bool {
		// We use get_status because it handles validation and API calls
		return ! empty( self::get_status( $storage_key ) );
	}

	/**
	 * --- WRITER METHODS (INSTANCE) ---
	 */

	/**
	 * Sets a specific metric value.
	 *
	 * @param string $key   Metric name (ASCII only).
	 * @param mixed  $value Value.
	 * @return self
	 */
	public function set( string $key, $value ): self {
		if ( ! $this->is_valid ) {
			return $this;
		}

		$key = trim( $key );

		if ( '' === $key || strlen( $key ) > self::MAX_METRIC_KEY_LENGTH ) {
			return $this;
		}

		if ( in_array( $key, self::RESERVED_KEYS, true ) ) {
			return $this;
		}

		$this->data[ $key ] = $value;

		return $this;
	}

	/**
	 * Bulk sets metrics.
	 *
	 * @param array $metrics
	 * @return self
	 */
	public function set_batch( array $metrics ): self {
		if ( ! $this->is_valid ) {
			return $this;
		}

		foreach ( $metrics as $key => $value ) {
			if ( is_string( $key ) ) {
				$this->set( $key, $value );
			}
		}

		return $this;
	}

	/**
	 * Persists the current state using cerber_update_set().
	 *
	 * @return void
	 */
	public function save(): void {
		if ( ! $this->is_valid || ! function_exists( 'cerber_update_set' ) ) {
			return;
		}

		// Save using Cerber API
		// Defaults: id=0, serialize=true, expires=0 (never), use_cache=null
		cerber_update_set( $this->storage_key, $this->data );
	}

	/**
	 * Finalizes the process and saves the report.
	 *
	 * @return void
	 */
	public function finish(): void {
		if ( ! $this->is_valid ) {
			return;
		}

		$now = time();
		$this->data['finished_at'] = $now;
		$this->data['duration']    = $now - $this->start_time;

		$this->save();
	}

	/**
	 * --- INTERNAL HELPERS ---
	 */

	private function disable_instance(): void {
		$this->is_valid    = false;
		$this->storage_key = '';
		$this->start_time  = 0;
	}

	/**
	 * Centralized validation logic for storage keys.
	 * Used by both Constructor (Write) and Static Reader methods.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	private static function is_key_valid( string $key ): bool {
		$key = trim( $key );

		if ( '' === $key ) {
			return false;
		}

		if ( strlen( $key ) > self::MAX_STORAGE_KEY_LENGTH ) {
			return false;
		}

		if ( ! preg_match( self::STORAGE_KEY_PATTERN, $key ) ) {
			return false;
		}

		return true;
	}
}