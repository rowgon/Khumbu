<?php

/**
 * Registry for persistent domain-level issues.
 *
 * Manages the lifecycle of non-code issues related to the environment, external APIs,
 * and business domain constraints. Unlike technical logs, these issues are treated
 * as stateful entities to provide actionable feedback and guided resolution paths
 * within the UI/UX layer.
 *
 * @since 9.6.14
 *
 * @version 1.20
 *
 */
class CRB_Issues {

	/**
	 * The identifier used to store the registry data in the persistent storage.
	 * Private implementation detail: external code should not access storage directly.
	 */
	private const STORAGE_KEY = 'cerber_issues';

	/**
	 * Key for the unique internal identifier.
	 */
	public const KEY_ID = 'id';

	/**
	 * Key for the issue creation timestamp.
	 */
	public const KEY_CREATED_AT = 'created_at';

	/**
	 * Key for the issue reoccurrence timestamp.
	 */
	public const KEY_LAST_OCCURRED_AT = 'last_occurred_at';

	/**
	 * Key for the human-readable issue message.
	 */
	public const KEY_MESSAGE = 'message';

	/**
	 * Key for the issue severity level.
	 */
	public const KEY_SEVERITY = 'severity';

	/**
	 * Key for the issue type ('event' or 'ongoing').
	 */
	public const KEY_TYPE = 'type';

	/**
	 * Key for the dismissibility flag (boolean).
	 * Determines if the user can manually remove the issue from the UI.
	 */
	public const KEY_DISMISSABLE = 'dismissable';

	/**
	 * Key for the occurrence counter.
	 */
	public const KEY_COUNT = 'count';

	/**
	 * Key for the resolution action identifier or link.
	 */
	public const KEY_RESOLUTION = 'resolution';

	/**
	 * Key for the related setting identifier.
	 */
	public const KEY_SETTING_ID = 'setting_id';

	/**
	 * Key for the documentation page URL.
	 */
	public const KEY_DOC_PAGE = 'doc_page';

	/**
	 * Key for additional context details.
	 */
	public const KEY_CONTEXT = 'context';

	/**
	 * Issue Type: Event (Transient).
	 * Represents a point-in-time occurrence.
	 */
	public const TYPE_EVENT = 'event';

	/**
	 * Issue Type: Ongoing (Persistent).
	 * Represents a continuous condition or state.
	 */
	public const TYPE_ONGOING = 'ongoing';

	/**
	 * Severity Level: Critical.
	 * Requires immediate attention. Replaces 'error'.
	 */
	public const SEVERITY_CRITICAL = 'critical';

	/**
	 * Severity Level: Warning.
	 * Potential issue that should be investigated.
	 */
	public const SEVERITY_WARNING = 'warning';

	/**
	 * Severity Level: Advisory.
	 * Informational message or recommendation. Replaces 'info'.
	 */
	public const SEVERITY_ADVISORY = 'advisory';

	/**
	 * Default Time-To-Live (TTL) for transient events in seconds (24 hours).
	 * Used by the maintenance method to prune stale issues.
	 */
	public const DEFAULT_TTL = 86400;

	/**
	 * Register an issue into the storage.
	 *
	 * Issues are organized by section and identified by a unique code.
	 *
	 * @param string $code    Unique code identifying the issue (e.g., 'api_timeout').
	 * @param string $message Human-readable description for the user.
	 * @param array  $details Optional configuration. Supported keys:
	 * - 'section' (string): Domain section. Default: 'generic'.
	 * - 'type' (string): CRB_Issues::TYPE_EVENT or CRB_Issues::TYPE_ONGOING. Default: 'ongoing'.
	 * - 'severity' (string): 'critical', 'warning', 'advisory'. Default: 'critical'.
	 * - 'dismissable' (bool): Explicitly allow/disallow dismissing. Default depends on type.
	 * - 'resolution' (string): Action ID or URL for guided resolution.
	 * - 'setting_id' (string): Identifier of the related configuration setting.
	 * - 'doc_page' (string): URL to the relevant documentation page.
	 * - 'context' (array): Debug data (e.g., API response codes).
	 *
	 * @return void
	 *
	 * @since 9.3.4
	 */
	public static function add( string $code, string $message, array $details = [] ): void {

		$code = self::normalize_key( $code );

		if ( ! $code ) {
			return;
		}

		$section_raw = $details['section'] ?? '';
		$section = self::normalize_key( (string) $section_raw );

		if ( ! $section ) {
			$section = 'generic';
		}

		$severity = $details['severity'] ?? '';

		if ( ! in_array( $severity, [ self::SEVERITY_CRITICAL, self::SEVERITY_WARNING, self::SEVERITY_ADVISORY ], true ) ) {
			$severity = self::SEVERITY_CRITICAL;
		}

		// Determine the issue type. Default is 'ongoing'.
		$type_raw = $details['type'] ?? '';
		if ( $type_raw === self::TYPE_EVENT ) {
			$type = self::TYPE_EVENT;
		} else {
			$type = self::TYPE_ONGOING;
		}

		$issues = self::get_all();
		$existing = $issues[ $section ][ $code ] ?? [];

		// Determine dismissibility
		if ( isset( $details['dismissable'] ) ) {
			// Explicit override provided
			$dismissable = (bool) $details['dismissable'];
		} elseif ( isset( $existing[ self::KEY_DISMISSABLE ] ) ) {
			// Persist existing state on update
			$dismissable = (bool) $existing[ self::KEY_DISMISSABLE ];
		} else {
			// Default based on type: Events are dismissable, Ongoing are not
			$dismissable = ( $type === self::TYPE_EVENT );
		}

		// Increment counter
		$count = isset( $existing[ self::KEY_COUNT ] ) ? (int) $existing[ self::KEY_COUNT ] : 0;
		$count++;

		// Preserve existing ID or generate a new one
		$id = isset( $existing[ self::KEY_ID ] ) ? (string) $existing[ self::KEY_ID ] : self::generate_id( $code );

		$resolution = $details['resolution'] ?? false;
		$doc_page   = isset( $details['doc_page'] ) ? trim( (string) $details['doc_page'] ) : '';
		$context    = $details['context'] ?? [];

		$setting_id_raw = $details['setting_id'] ?? '';
		$setting_id = self::normalize_key( (string) $setting_id_raw );

		$now = time();
		$created_at = $existing[ self::KEY_CREATED_AT ] ?? $now;

		$issues[ $section ][ $code ] = [
			self::KEY_ID               => $id,
			self::KEY_CREATED_AT       => $created_at,
			self::KEY_LAST_OCCURRED_AT => $now,
			self::KEY_MESSAGE          => $message,
			self::KEY_SEVERITY         => $severity,
			self::KEY_TYPE             => $type,
			self::KEY_DISMISSABLE      => $dismissable,
			self::KEY_COUNT            => $count,
			self::KEY_RESOLUTION       => $resolution,
			self::KEY_SETTING_ID       => $setting_id,
			self::KEY_DOC_PAGE         => $doc_page,
			self::KEY_CONTEXT          => $context,
		];

		cerber_update_set( self::STORAGE_KEY, $issues );
	}

	/**
	 * Retrieve all issues from storage.
	 *
	 * Returns the complete structured registry grouped by section.
	 *
	 * @return array<string, array<string, array{
	 * id: string,
	 * created_at: int,
	 * last_occurred_at: int,
	 * message: string,
	 * severity: string,
	 * type: string,
	 * dismissable: bool,
	 * count: int,
	 * resolution: string|false,
	 * setting_id: string,
	 * doc_page: string,
	 * context: array
	 * }>> Structure: [section][code] => [issue_data]
	 *
	 * @since 9.3.4
	 */
	public static function get_all(): array {
		$issues = cerber_get_set( self::STORAGE_KEY );

		if ( ! is_array( $issues ) ) {
			return [];
		}

		return $issues;
	}

	/**
	 * Retrieve a flat list of issues matching specific criteria.
	 *
	 * Useful for UI loops and dashboards where section grouping is not required.
	 *
	 * @param array $criteria Optional filters. Supported keys:
	 * - 'section' (string): Filter by specific section.
	 * - 'severity' (string|array): Filter by one or more severity levels.
	 * - 'type' (string): Filter by issue type (e.g., 'ongoing').
	 *
	 * @return array<int, array{
	 * id: string,
	 * created_at: int,
	 * last_occurred_at: int,
	 * message: string,
	 * severity: string,
	 * type: string,
	 * dismissable: bool,
	 * count: int,
	 * resolution: string|false,
	 * setting_id: string,
	 * doc_page: string,
	 * context: array,
	 * section: string,
	 * code: string
	 * }> List of issue arrays, sorted by last occurrence (descending).
	 *
	 * @since 9.6.14
	 */
	public static function fetch( array $criteria = [] ): array {
		$issues = self::get_all();

		if ( empty( $issues ) ) {
			return [];
		}

		$result = [];
		$filter_section  = isset( $criteria['section'] ) ? self::normalize_key( (string) $criteria['section'] ) : '';
		$filter_severity = isset( $criteria['severity'] ) ? $criteria['severity'] : [];
		$filter_type     = isset( $criteria['type'] ) ? (string) $criteria['type'] : '';

		if ( is_string( $filter_severity ) && $filter_severity !== '' ) {
			$filter_severity = [ $filter_severity ];
		}

		foreach ( $issues as $section => $section_issues ) {
			// Filter by section if requested
			if ( $filter_section !== '' && $section !== $filter_section ) {
				continue;
			}

			if ( ! is_array( $section_issues ) ) {
				continue;
			}

			foreach ( $section_issues as $code => $data ) {
				if ( ! is_array( $data ) ) {
					continue;
				}

				// Filter by type
				$type = isset( $data[ self::KEY_TYPE ] ) ? (string) $data[ self::KEY_TYPE ] : self::TYPE_ONGOING;
				if ( $filter_type !== '' && $type !== $filter_type ) {
					continue;
				}

				// Filter by severity
				$severity = isset( $data[ self::KEY_SEVERITY ] ) ? (string) $data[ self::KEY_SEVERITY ] : self::SEVERITY_CRITICAL;
				if ( ! empty( $filter_severity ) ) {
					if ( ! in_array( $severity, $filter_severity, true ) ) {
						continue;
					}
				}

				// Reconstruct the issue array with strict typing to ensure the contract
				// This guarantees that the returned array matches the PHPDoc structure exactly
				$result[] = [
					self::KEY_ID               => (string) ( $data[ self::KEY_ID ] ?? '' ),
					self::KEY_CREATED_AT       => (int) ( $data[ self::KEY_CREATED_AT ] ?? 0 ),
					self::KEY_LAST_OCCURRED_AT => (int) ( $data[ self::KEY_LAST_OCCURRED_AT ] ?? 0 ),
					self::KEY_MESSAGE          => (string) ( $data[ self::KEY_MESSAGE ] ?? '' ),
					self::KEY_SEVERITY         => $severity,
					self::KEY_TYPE             => $type,
					self::KEY_DISMISSABLE      => (bool) ( $data[ self::KEY_DISMISSABLE ] ?? false ),
					self::KEY_COUNT            => (int) ( $data[ self::KEY_COUNT ] ?? 0 ),
					self::KEY_RESOLUTION       => isset( $data[ self::KEY_RESOLUTION ] ) ? $data[ self::KEY_RESOLUTION ] : false,
					self::KEY_SETTING_ID       => (string) ( $data[ self::KEY_SETTING_ID ] ?? '' ),
					self::KEY_DOC_PAGE         => (string) ( $data[ self::KEY_DOC_PAGE ] ?? '' ),
					self::KEY_CONTEXT          => is_array( $data[ self::KEY_CONTEXT ] ?? null ) ? $data[ self::KEY_CONTEXT ] : [],
					'section'                  => (string) $section,
					'code'                     => (string) $code,
				];
			}
		}

		// Sort by last occurrence (newest first)
		usort( $result, function ( $a, $b ) {
			return $b[ self::KEY_LAST_OCCURRED_AT ] - $a[ self::KEY_LAST_OCCURRED_AT ];
		} );

		return $result;
	}

	/**
	 * Get the total number of registered issues.
	 *
	 * Optionally filters the count by issue type.
	 *
	 * @param string $type Optional. Filter by issue type (e.g., CRB_Issues::TYPE_EVENT).
	 * If empty, counts all issues. Default: ''.
	 *
	 * @return int The total count of matching issues.
	 *
	 * @since 9.6.14
	 */
	public static function get_count( string $type = '' ): int {
		$issues = self::get_all();
		$count  = 0;

		foreach ( $issues as $section_issues ) {
			if ( ! is_array( $section_issues ) ) {
				continue;
			}

			foreach ( $section_issues as $data ) {
				if ( ! is_array( $data ) ) {
					continue;
				}

				if ( '' === $type ) {
					$count++;
					continue;
				}

				$issue_type = isset( $data[ self::KEY_TYPE ] ) ? (string) $data[ self::KEY_TYPE ] : self::TYPE_ONGOING;

				if ( $issue_type === $type ) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Delete a specific issue identified by its code and optional section.
	 *
	 * If the section is not provided, it defaults to 'generic', mirroring the behavior of the add() method.
	 *
	 * @param string $code    The unique code of the issue.
	 * @param string $section Optional. The domain section of the issue. Default: 'generic'.
	 *
	 * @return void
	 *
	 * @since 9.3.4
	 */
	public static function delete_item( string $code, string $section = '' ): void {
		$code = self::normalize_key( $code );

		if ( ! $code ) {
			return;
		}

		$section = self::normalize_key( $section );

		if ( ! $section ) {
			$section = 'generic';
		}

		$issues = self::get_all();

		if ( empty( $issues ) ) {
			return;
		}

		if ( ! isset( $issues[ $section ][ $code ] ) ) {
			return;
		}

		unset( $issues[ $section ][ $code ] );

		// Clean up empty sections to keep storage tidy
		if ( empty( $issues[ $section ] ) ) {
			unset( $issues[ $section ] );
		}

		cerber_update_set( self::STORAGE_KEY, $issues );
	}

	/**
	 * Delete a specific issue by its unique internal ID.
	 *
	 * Performs a search across all sections to find and remove the issue with the matching ID.
	 * This method is useful when the context (section/code) is not available, e.g., in flat lists.
	 *
	 * @param string $id The unique issue identifier.
	 *
	 * @return bool|WP_Error True on success, WP_Error object on failure.
	 *
	 * @since 9.6.14
	 */
	public static function delete_by_id( string $id ) {
		$id = self::normalize_key( $id );

		if ( ! $id ) {
			return new WP_Error( 'cerber_issue_invalid_id', 'Invalid issue ID provided.' );
		}

		$issues = self::get_all();

		if ( empty( $issues ) ) {
			return new WP_Error( 'cerber_issue_not_found', 'Issue registry is empty.' );
		}

		$is_modified = false;

		foreach ( $issues as $section => $section_issues ) {
			foreach ( $section_issues as $code => $data ) {
				// Strict type check for safety
				if ( isset( $data[ self::KEY_ID ] ) && (string) $data[ self::KEY_ID ] === $id ) {
					unset( $issues[ $section ][ $code ] );
					$is_modified = true;

					// If section becomes empty, remove it to keep storage clean
					if ( empty( $issues[ $section ] ) ) {
						unset( $issues[ $section ] );
					}

					// ID is unique, so we can stop searching immediately
					break 2;
				}
			}
		}

		if ( $is_modified ) {
			cerber_update_set( self::STORAGE_KEY, $issues );
			return true;
		}

		return new WP_Error( 'cerber_issue_not_found', 'Issue with the specified ID not found.' );
	}

	/**
	 * Delete all issues from a specific section.
	 *
	 * @param string $section Section name.
	 *
	 * @return void
	 *
	 * @since 9.3.4
	 */
	public static function delete_section( string $section ): void {
		$issues = self::get_all();

		if ( empty( $issues ) ) {
			return;
		}

		$section = self::normalize_key( $section );

		if ( ! isset( $issues[ $section ] ) ) {
			return;
		}

		unset( $issues[ $section ] );

		cerber_update_set( self::STORAGE_KEY, $issues );
	}

	/**
	 * Delete all issues from storage.
	 *
	 * @return void
	 *
	 * @since 9.3.4
	 */
	public static function delete_all(): void {
		cerber_delete_set( self::STORAGE_KEY );
	}

	/**
	 * Perform maintenance to prune stale 'event' issues.
	 *
	 * Removes issues of type TYPE_EVENT that have not recurred within the specified TTL.
	 * Does NOT remove TYPE_ONGOING issues, as they persist until resolved.
	 *
	 * @param int $ttl_seconds Time-to-live in seconds. Defaults to 24 hours (86400).
	 *
	 * @return int The number of pruned issues.
	 *
	 * @since 9.3.4
	 */
	public static function maintenance( int $ttl_seconds = self::DEFAULT_TTL ): int {
		$issues = self::get_all();

		if ( empty( $issues ) ) {
			return 0;
		}

		$now          = time();
		$pruned_count = 0;
		$is_modified  = false;

		foreach ( $issues as $section => $section_issues ) {
			foreach ( $section_issues as $code => $data ) {
				// Only prune transient events, preserve ongoing states
				$type = $data[ self::KEY_TYPE ] ?? self::TYPE_ONGOING; // Fallback to ongoing for safety if undefined

				if ( $type !== self::TYPE_EVENT ) {
					continue;
				}

				$last_occurred = isset( $data[ self::KEY_LAST_OCCURRED_AT ] ) ? (int) $data[ self::KEY_LAST_OCCURRED_AT ] : 0;

				if ( ( $now - $last_occurred ) > $ttl_seconds ) {
					unset( $issues[ $section ][ $code ] );
					$pruned_count++;
					$is_modified = true;
				}
			}

			// Clean up empty sections
			if ( empty( $issues[ $section ] ) ) {
				unset( $issues[ $section ] );
				$is_modified = true;
			}
		}

		if ( $is_modified ) {
			cerber_update_set( self::STORAGE_KEY, $issues );
		}

		return $pruned_count;
	}

	/**
	 * Normalize a key string to ensure it contains only safe ASCII characters.
	 *
	 * Allowed characters: a-z, A-Z, 0-9, underscore (_), hyphen (-).
	 *
	 * @param string $key The raw key string.
	 *
	 * @return string The sanitized key.
	 */
	private static function normalize_key( string $key ): string {
		return preg_replace( '/[^a-zA-Z0-9_-]/', '', $key );
	}

	/**
	 * Generate a unique identifier for an issue based on time and code.
	 *
	 * @param string $code The issue code.
	 *
	 * @return string
	 */
	private static function generate_id( string $code ): string {
		return sha1( microtime() . $code );
	}
}