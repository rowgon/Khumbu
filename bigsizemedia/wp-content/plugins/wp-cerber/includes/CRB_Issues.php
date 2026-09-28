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
 * @version 1.22
 *
 */
final class CRB_Issues {

	/**
	 * An array of allowed HTML elements and attributes in user-facing messages.
	 */
	const ALLOWED_HTML = array(
		'b'      => array(),
		'strong' => array(),
		'i'      => array(),
		'code'   => array(),
		'samp'   => array(),
		'br'     => array(),
		'p'      => array(),
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
	);

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
	 * Key for the sprintf-style arguments applied to the issue message at render time.
	 */
	public const KEY_MESSAGE_ARGS = 'message_args';

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
	 * Internal key for the class that pre-processes an issue before it is returned.
	 */
	private const KEY_CALLBACK_CLASS = 'callback_class';

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
	 * Registers an issue occurrence in persistent storage.
	 *
	 * Creates a new issue or updates an existing issue identified by the same section
	 * and code. An update preserves the issue ID and creation timestamp, increments the
	 * occurrence count, and refreshes the last occurrence timestamp.
	 *
	 * When provided, the callback class must expose a public static pre_process( array $issue ) method.
	 * CRB_Issues::fetch() invokes this method before returning the issue and passes one normalized issue
	 * array containing the public fields documented by CRB_Issues::fetch(), including the section and code.
	 *
	 * The callback may return false to delete the issue as resolved, an array containing
	 * supported field updates, or an empty array to keep the issue unchanged. Omitting the
	 * callback class when updating an issue preserves the previously registered callback.
	 *
	 * @param string $code           Unique code identifying the issue within its section.
	 * @param string $message        Human-readable issue description, always produced by a translation function and retried against the translation catalog at render time.
	 * @param array  $details        Optional configuration. Supported keys:
	 * - 'message_args' (array): Scalar values of the sprintf-style placeholders of the message.
	 * - 'section' (string): Domain section. Default: 'generic'.
	 * - 'type' (string): CRB_Issues::TYPE_EVENT or CRB_Issues::TYPE_ONGOING. Default: 'ongoing'.
	 * - 'severity' (string): 'critical', 'warning', 'advisory'. Default: 'critical'.
	 * - 'dismissable' (bool): Explicitly allow/disallow dismissing. Default depends on type.
	 * - 'resolution' (string): Action ID or URL for guided resolution.
	 * - 'setting_id' (string): Identifier of the related configuration setting.
	 * - 'doc_page' (string): URL to the relevant documentation page.
	 * - 'context' (array): Debug data (e.g., API response codes).
	 * @param string $callback_class Optional class name exposing public static pre_process( array $issue ).
	 *
	 * @return void
	 *
	 * @see self::ALLOWED_HTML for allowed tags in $message
	 *
	 * @since 9.3.4
	 */
	public static function register(
		string $code,
		string $message,
		array $details = [],
		string $callback_class = ''
	): void {

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

		// Determine dismissibility.
		if ( isset( $details['dismissable'] ) ) {
			$dismissable = (bool) $details['dismissable'];
		} elseif ( isset( $existing[ self::KEY_DISMISSABLE ] ) ) {
			$dismissable = (bool) $existing[ self::KEY_DISMISSABLE ];
		} else {
			$dismissable = ( $type === self::TYPE_EVENT );
		}

		// Preserve the previously registered callback when an update omits it.
		$callback_class = trim( $callback_class );
		if ( $callback_class === '' ) {
			$callback_class = isset( $existing[ self::KEY_CALLBACK_CLASS ] )
				? (string) $existing[ self::KEY_CALLBACK_CLASS ]
				: '';
		} elseif ( ! is_callable( array( $callback_class, 'pre_process' ) ) ) {
			$callback_class = isset( $existing[ self::KEY_CALLBACK_CLASS ] )
				? (string) $existing[ self::KEY_CALLBACK_CLASS ]
				: '';
		}

		// Increment counter.
		$count = isset( $existing[ self::KEY_COUNT ] ) ? (int) $existing[ self::KEY_COUNT ] : 0;
		$count++;

		// Preserve existing ID or generate a new one.
		$id = isset( $existing[ self::KEY_ID ] ) ? (string) $existing[ self::KEY_ID ] : self::generate_id( $code );

		$resolution = $details['resolution'] ?? false;
		$doc_page   = isset( $details['doc_page'] ) ? trim( (string) $details['doc_page'] ) : '';
		$context    = $details['context'] ?? [];

		// The message arguments are persisted, so only scalar values are kept. Any other value
		// would not survive serialization reliably and cannot be rendered as a placeholder value.
		$message_args = [];

		if ( ! empty( $details['message_args'] ) && is_array( $details['message_args'] ) ) {
			$message_args = array_values( array_filter( $details['message_args'], 'is_scalar' ) );
		}

		$setting_id_raw = $details['setting_id'] ?? '';
		$setting_id = self::normalize_key( (string) $setting_id_raw );

		$now = time();
		$created_at = $existing[ self::KEY_CREATED_AT ] ?? $now;

		$issues[ $section ][ $code ] = [
			self::KEY_ID               => $id,
			self::KEY_CREATED_AT       => $created_at,
			self::KEY_LAST_OCCURRED_AT => $now,
			self::KEY_MESSAGE          => $message,
			self::KEY_MESSAGE_ARGS     => $message_args,
			self::KEY_SEVERITY         => $severity,
			self::KEY_TYPE             => $type,
			self::KEY_DISMISSABLE      => $dismissable,
			self::KEY_COUNT            => $count,
			self::KEY_RESOLUTION       => $resolution,
			self::KEY_SETTING_ID       => $setting_id,
			self::KEY_DOC_PAGE         => $doc_page,
			self::KEY_CONTEXT          => $context,
			self::KEY_CALLBACK_CLASS   => $callback_class,
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
	 * message_args?: array<int, string|int|float|bool>,
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
	 * Registered callback classes are invoked after the stored message has been rendered, so a
	 * message a callback returns is final and is never translated or formatted again. The static
	 * pre_process() method receives the normalized issue and may return an allowed field
	 * patch, an empty array to keep it unchanged, or false to delete a resolved issue.
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
		$filter_severity = $criteria['severity'] ?? [];
		$filter_type     = isset( $criteria['type'] ) ? (string) $criteria['type'] : '';

		if ( is_string( $filter_severity ) && $filter_severity !== '' ) {
			$filter_severity = [ $filter_severity ];
		}

		foreach ( $issues as $section => $section_issues ) {
			// Filter by section when requested.
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

				// Filter by issue type when requested.
				$type = isset( $data[ self::KEY_TYPE ] ) ? (string) $data[ self::KEY_TYPE ] : self::TYPE_ONGOING;
				if ( $filter_type !== '' && $type !== $filter_type ) {
					continue;
				}

				// Filter by severity when requested.
				$severity = isset( $data[ self::KEY_SEVERITY ] ) ? (string) $data[ self::KEY_SEVERITY ] : self::SEVERITY_CRITICAL;
				if ( ! empty( $filter_severity ) && ! in_array( $severity, $filter_severity, true ) ) {
					continue;
				}

				// Localize the stored message before it enters the public contract of this method.
				$message_args = is_array( $data[ self::KEY_MESSAGE_ARGS ] ?? null ) ? $data[ self::KEY_MESSAGE_ARGS ] : [];
				$message = self::render_message( (string) ( $data[ self::KEY_MESSAGE ] ?? '' ), $message_args );

				// Normalize stored data to the public fetch() contract before pre-processing.
				$issue = [
					self::KEY_ID               => (string) ( $data[ self::KEY_ID ] ?? '' ),
					self::KEY_CREATED_AT       => (int) ( $data[ self::KEY_CREATED_AT ] ?? 0 ),
					self::KEY_LAST_OCCURRED_AT => (int) ( $data[ self::KEY_LAST_OCCURRED_AT ] ?? 0 ),
					self::KEY_MESSAGE          => $message,
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

				// Apply the issue-specific callback to the normalized record.
				$issue = self::apply_pre_process( $issue, (string) ( $data[ self::KEY_CALLBACK_CLASS ] ?? '' ) );

				if ( $issue === false ) {
					continue;
				}

				$result[] = $issue;
			}
		}

		// Sort by the most recent occurrence first.
		usort( $result, function ( $first_issue, $second_issue ) {
			return $second_issue[ self::KEY_LAST_OCCURRED_AT ] - $first_issue[ self::KEY_LAST_OCCURRED_AT ];
		} );

		return $result;
	}

	/**
	 * Retries the translation of a stored issue message in the locale active at the moment of the call.
	 *
	 * Formatting is applied to the translated message and only when arguments were stored, so the
	 * msgid stays the translation key and a message holding a stray percent sign is never passed to
	 * a formatting call it was not written for.
	 *
	 * @param string $stored_message Stored message retried against the translation catalog.
	 * @param array<int, string|int|float|bool> $message_args Optional sprintf-style arguments of the message.
	 *
	 * @return string Translated message, formatted when arguments are given, or an empty string when no message is stored.
	 *
	 * @since 9.9.1
	 */
	private static function render_message( string $stored_message, array $message_args = [] ): string {

		if ( $stored_message === '' ) {
			return '';
		}

		$translated_message = translate( $stored_message, 'wp-cerber' );

		if ( ! $message_args ) {
			return $translated_message;
		}

		// A translation is free to change the number of placeholders, so formatting is treated as an
		// operation that can fail regardless of what the producer of the issue stored.
		$formatting_error = '';

		try {
			$formatted_message = vsprintf( $translated_message, $message_args );
		}
		catch ( Throwable $exception ) {
			$formatted_message = false;
			$formatting_error = ' ' . $exception->getMessage();
		}

		// PHP 7.4 reports arguments that do not satisfy the placeholders by returning false, while
		// PHP 8 throws instead. Both report the same defect and lead to the same recovery.
		if ( ! is_string( $formatted_message ) ) {
			if ( class_exists( 'CRB_Bug_Hunter' ) ) {
				CRB_Bug_Hunter::collect_throwable( new RuntimeException( 'Unable to format the issue message "' . $stored_message . '" with ' . count( $message_args ) . ' stored argument(s).' . $formatting_error ) );
			}

			// An unformatted message with visible placeholders is degraded output. Interrupting the
			// rendering of the admin UI over a defective message is not an acceptable alternative.
			return $translated_message;
		}

		return $formatted_message;
	}

	/**
	 * Applies a registered issue callback before the issue enters the summary pipeline.
	 *
	 * Callback failures are non-destructive. The original issue remains visible when the
	 * callback class is unavailable, not callable, throws an exception, or returns an invalid value.
	 *
	 * @param array<string, mixed> $issue          Normalized persistent issue.
	 * @param string               $callback_class Optional callback class name.
	 *
	 * @return array<string, mixed>|false Updated issue, or false when the issue is resolved.
	 */
	private static function apply_pre_process( array $issue, string $callback_class ) {
		if ( $callback_class === '' || ! is_callable( array( $callback_class, 'pre_process' ) ) ) {
			return $issue;
		}

		try {
			$changes = $callback_class::pre_process( $issue );
		}
		catch ( Throwable $exception ) {
			if ( class_exists( 'CRB_Bug_Hunter' ) ) {
				CRB_Bug_Hunter::collect_throwable( $exception );
			}

			return $issue;
		}

		if ( $changes === false ) {
			self::delete_item( (string) $issue['code'], (string) $issue['section'] );
			return false;
		}

		if ( empty( $changes )
		     || ! is_array( $changes ) ) {
			return $issue;
		}

		$allowed_keys = [
			self::KEY_MESSAGE,
			self::KEY_SEVERITY,
			self::KEY_DISMISSABLE,
			self::KEY_RESOLUTION,
			self::KEY_SETTING_ID,
			self::KEY_DOC_PAGE,
			self::KEY_CONTEXT,
		];

		foreach ( $allowed_keys as $allowed_key ) {
			if ( array_key_exists( $allowed_key, $changes ) ) {
				$issue[ $allowed_key ] = $changes[ $allowed_key ];
			}
		}

		return $issue;
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
				// Match the normalized ID strictly to avoid type-coercion collisions.
				if ( isset( $data[ self::KEY_ID ] ) && (string) $data[ self::KEY_ID ] === $id ) {
					unset( $issues[ $section ][ $code ] );
					$is_modified = true;

					// Remove the section when its final issue has been deleted.
					if ( empty( $issues[ $section ] ) ) {
						unset( $issues[ $section ] );
					}

					// The internal ID is unique, so stop after the first match.
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
				// Prune only transient events; ongoing issues require explicit resolution.
				$type = $data[ self::KEY_TYPE ] ?? self::TYPE_ONGOING;

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

			// Remove sections left empty after pruning.
			if ( empty( $issues[ $section ] ) ) {
				unset( $issues[ $section ] );
				$is_modified = true;
			}
		}

		// Avoid a storage write when maintenance made no changes.
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
