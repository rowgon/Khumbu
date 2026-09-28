<?php

/**
 * Automatic backup and recovery of the plugin settings stored in the CERBER_CONFIG option.
 *
 * Maintains a single backup slot in the plugin key-value storage holding the last known
 * valid copy of the plugin settings. The backup is refreshed after successful settings
 * updates, settings imports, plugin upgrades, and once a day by the scheduled maintenance
 * task. It is used to automatically restore the settings when the corruption detector in
 * crb_get_settings() encounters a stored value that cannot be unserialized.
 *
 * The backup payload is persisted as a JSON-encoded string of the following shape:
 * array{settings: array<string, mixed>, user_id: int, timestamp: int, context: string}
 *
 * The class is also registered as the message resolver of the issues it reports. Its
 * pre_process() method rebuilds the issue message from the stored issue code and context
 * whenever CRB_Issues::fetch() returns one of these issues, so the administrator reads it
 * in the locale active at render time rather than in the locale active at recovery time.
 *
 * None of the methods call crb_get_settings(), so the class is safe to use inside
 * the settings loading path.
 */
final class CRB_Settings_Backup {

	/**
	 * The key the backup payload is stored under in the plugin key-value storage.
	 * Private implementation detail: external code should not access the backup directly.
	 */
	private const BACKUP_KEY = 'cerber_settings_backup';

	/**
	 * Backup context: a successful administrative settings update.
	 */
	public const CONTEXT_SETTINGS_UPDATE = 'settings_update';

	/**
	 * Backup context: a successful settings import.
	 */
	public const CONTEXT_SETTINGS_IMPORT = 'settings_import';

	/**
	 * Backup context: a plugin upgrade.
	 */
	public const CONTEXT_PLUGIN_UPGRADE = 'plugin_upgrade';

	/**
	 * Backup context: a daily run of the scheduled maintenance task.
	 */
	public const CONTEXT_SCHEDULED_SYNC = 'scheduled_sync';

	/**
	 * Issue code reported when the settings have been restored from the backup copy.
	 * Selects the successful restoration group in the message registry.
	 */
	private const ISSUE_SETTINGS_RESTORED = 'settings_restored';

	/**
	 * Issue code reported when the corrupted settings could not be restored from the backup copy.
	 * Selects the failed recovery group in the message registry.
	 */
	private const ISSUE_CORRUPTED_SETTINGS = 'corrupted_settings';

	/**
	 * @var bool True after a restoration attempt has been made within the current request.
	 */
	private static $recovery_attempted = false;

	/**
	 * Saves the currently stored plugin settings as the backup copy in the key-value storage.
	 *
	 * Reads the raw CERBER_CONFIG option and stores it together with the initiating user ID,
	 * the current timestamp, and the given context. Refuses to update the backup if the
	 * currently stored settings are missing, corrupted, or empty, so the last known valid
	 * backup is never overwritten with invalid settings.
	 *
	 * @param string $backup_context Non-empty code describing the operation that triggered the backup.
	 *
	 * @return bool True if the backup copy was updated. False if the context is empty, the stored settings are invalid, or the write failed.
	 */
	public static function sync( string $backup_context ): bool {

		if ( trim( $backup_context ) === '' ) {
			return false;
		}

		$current_settings = get_site_option( CERBER_CONFIG );

		if ( ! is_array( $current_settings )
		     || ! $current_settings ) {
			return false;
		}

		$backup_payload = array(
			'settings'  => $current_settings,
			'user_id'   => (int) get_current_user_id(),
			'timestamp' => time(),
			'context'   => $backup_context,
		);

		$encoded_backup = json_encode( $backup_payload );

		if ( ! is_string( $encoded_backup ) ) {
			return false;
		}

		return cerber_update_set( self::BACKUP_KEY, $encoded_backup, 0, false, 0, false );
	}

	/**
	 * Restores the plugin settings after data corruption was detected.
	 *
	 * Attempts to restore a validated backup first. If backup recovery is unavailable or
	 * fails, replaces the corrupted option with default settings as the final recovery path.
	 * Only one restoration attempt per request is performed to prevent recursive cycles.
	 * This method never creates a new backup and never calls crb_get_settings().
	 *
	 * @param int $corrupted_length Byte length of the corrupted raw settings value, used for diagnostics.
	 *
	 * @return array<string, mixed>|false The backup settings on successful backup recovery, false when the default-settings recovery path was taken, regardless of whether the default settings were successfully applied.
	 */
	public static function recover( int $corrupted_length ) {

		if ( self::$recovery_attempted ) {
			return false;
		}

		self::$recovery_attempted = true;

		$encoded_backup = cerber_get_set( self::BACKUP_KEY, 0, false, false );

		$failure_reason = '';
		$backup_payload = array();

		if ( ! $encoded_backup
		     || ! is_string( $encoded_backup ) ) {
			$failure_reason = 'backup_missing';
		}
		else {
			$decoded_payload = json_decode( $encoded_backup, true );

			if ( self::is_backup_payload_valid( $decoded_payload ) ) {
				$backup_payload = $decoded_payload;
			}
			else {
				$failure_reason = 'backup_invalid';
			}
		}

		if ( ! $failure_reason
		     && ! self::replace_settings( $backup_payload['settings'] ) ) {
			$failure_reason = 'restore_write_failed';
		}

		if ( $failure_reason ) {
			$default_settings_restored = self::replace_settings( crb_get_default_values() );

			self::report_failure(
				$failure_reason,
				$corrupted_length,
				$backup_payload,
				$default_settings_restored
			);

			return false;
		}

		self::report_success( $backup_payload, $corrupted_length );

		return $backup_payload['settings'];
	}

	/**
	 * Rebuilds the message of an issue reported by this class every time the issue is fetched.
	 *
	 * Resolves the message from the stored issue code and context so it is translated in the
	 * locale active when the issue is rendered. Keeps the stored message when the issue code
	 * is unknown or the context lacks the values required to produce an accurate message.
	 *
	 * @param array{code?: string, context?: array<string, mixed>} $issue Normalized issue supplied by CRB_Issues::fetch().
	 *
	 * @return array{message?: string} Message replacement for the fetched issue, or an empty array to keep the stored message.
	 */
	public static function pre_process( array $issue ): array {

		$issue_code = (string) ( $issue['code'] ?? '' );
		$issue_context = $issue[ CRB_Issues::KEY_CONTEXT ] ?? array();

		// Both messages state a value recorded during the recovery attempt, so a context that does not establish that value cannot produce an accurate message.

		$is_resolvable = false;

		if ( $issue_code === self::ISSUE_SETTINGS_RESTORED ) {
			$is_resolvable = ( (int) ( $issue_context['backup_timestamp'] ?? 0 ) > 0 );
		}
		elseif ( $issue_code === self::ISSUE_CORRUPTED_SETTINGS ) {
			// A recorded outcome of the default settings recovery is a boolean. Any other value leaves the outcome undetermined.
			$is_resolvable = is_bool( $issue_context['default_settings_restored'] ?? '' );
		}

		if ( ! $is_resolvable ) {
			return array();
		}

		// The normalized issue exposes the recorded context under the same key the issue was registered with, so it is a valid resolver input.

		return array( CRB_Issues::KEY_MESSAGE => self::get_issue_message( $issue_code, $issue ) );
	}

	/**
	 * Replaces the canonical plugin settings option with the given settings.
	 *
	 * Deletes the option first to invalidate stale WordPress option-cache state and ensure
	 * update_site_option() performs a real database write.
	 *
	 * @param array<string, mixed> $settings Complete settings payload to store.
	 *
	 * @return bool True when the replacement settings were stored successfully.
	 */
	private static function replace_settings( array $settings ): bool {

		if ( ! $settings ) {
			return false;
		}

		delete_site_option( CERBER_CONFIG );

		return update_site_option( CERBER_CONFIG, $settings );
	}

	/**
	 * Validates the structure of the decoded backup payload.
	 *
	 * The writer always produces the full payload shape, so any deviation means
	 * the backup itself is damaged and must not be restored.
	 *
	 * @param mixed $decoded_payload Result of JSON decoding of the stored backup value.
	 *
	 * @return bool True if the payload contains non-empty settings, a positive timestamp, a non-empty context code, and a non-negative integer user ID.
	 */
	private static function is_backup_payload_valid( $decoded_payload ): bool {

		if ( ! is_array( $decoded_payload ) ) {
			return false;
		}

		$backup_settings = $decoded_payload['settings'] ?? false;

		if ( ! is_array( $backup_settings )
		     || ! $backup_settings ) {
			return false;
		}

		$backup_timestamp = $decoded_payload['timestamp'] ?? 0;

		if ( ! is_int( $backup_timestamp )
		     || $backup_timestamp <= 0 ) {
			return false;
		}

		$backup_context = $decoded_payload['context'] ?? '';

		if ( ! is_string( $backup_context )
		     || trim( $backup_context ) === '' ) {
			return false;
		}

		$backup_user_id = $decoded_payload['user_id'] ?? false;

		if ( ! is_int( $backup_user_id )
		     || $backup_user_id < 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Registers the successful restoration outcome in the issue registry.
	 *
	 * Removes the stale corruption issue and registers a dismissable warning advising
	 * the administrator to review the restored settings. The warning is also removed
	 * automatically when the administrator saves the plugin settings.
	 *
	 * @param array{settings: array<string, mixed>, user_id: int, timestamp: int, context: string} $backup_payload Validated backup payload that was restored.
	 * @param int $corrupted_length Byte length of the corrupted raw settings value.
	 *
	 * @return void
	 */
	private static function report_success( array $backup_payload, int $corrupted_length ): void {

		CRB_Issues::delete_item( self::ISSUE_CORRUPTED_SETTINGS, 'settings' );

		$issue_details = array(
			'section'     => 'settings',
			'type'        => CRB_Issues::TYPE_ONGOING,
			'severity'    => CRB_Issues::SEVERITY_WARNING,
			'dismissable' => true,
			'context'     => array(
				'stored_value_length' => $corrupted_length,
				'backup_timestamp'    => (int) $backup_payload['timestamp'],
				'backup_context'      => (string) $backup_payload['context'],
				'backup_user_id'      => (int) $backup_payload['user_id'],
			),
		);

		// The message is registered in the current locale and re-resolved by pre_process() when the issue is rendered.

		CRB_Issues::register(
			self::ISSUE_SETTINGS_RESTORED,
			self::get_issue_message( self::ISSUE_SETTINGS_RESTORED, $issue_details ),
			$issue_details,
			self::class
		);
	}

	/**
	 * Registers a failed backup restoration and the outcome of the default-settings recovery.
	 *
	 * Removes a possible stale restoration notice and registers a critical issue explaining
	 * why backup recovery failed, whether default settings were stored, and what the
	 * administrator should do next.
	 *
	 * @param string $failure_reason One of 'backup_missing', 'backup_invalid', 'restore_write_failed'.
	 * @param int $corrupted_length Byte length of the corrupted raw settings value.
	 * @param array{settings?: array<string, mixed>, user_id?: int, timestamp?: int, context?: string} $backup_payload Validated backup payload if it was decoded successfully, empty array otherwise.
	 * @param bool $default_settings_restored True if the corrupted option was replaced with default settings.
	 *
	 * @return void
	 */
	private static function report_failure( string $failure_reason, int $corrupted_length, array $backup_payload, bool $default_settings_restored ): void {

		CRB_Issues::delete_item( self::ISSUE_SETTINGS_RESTORED, 'settings' );

		$issue_context = array(
			'stored_value_length'       => $corrupted_length,
			'backup_status'             => $failure_reason,
			'default_settings_restored' => $default_settings_restored,
		);

		if ( $backup_payload ) {
			$issue_context['backup_timestamp'] = (int) ( $backup_payload['timestamp'] ?? 0 );
			$issue_context['backup_context'] = (string) ( $backup_payload['context'] ?? '' );
		}

		$issue_details = array(
			'section'  => 'settings',
			'type'     => CRB_Issues::TYPE_ONGOING,
			'severity' => CRB_Issues::SEVERITY_CRITICAL,
			'context'  => $issue_context,
		);

		// The message is registered in the current locale and re-resolved by pre_process() when the issue is rendered.

		CRB_Issues::register(
			self::ISSUE_CORRUPTED_SETTINGS,
			self::get_issue_message( self::ISSUE_CORRUPTED_SETTINGS, $issue_details ),
			$issue_details,
			self::class
		);
	}

	/**
	 * Resolves the user-facing message describing the outcome of a settings recovery attempt.
	 *
	 * Selects the restoration message by backup context or the recovery failure message by
	 * failure reason and default-settings outcome. Uses a generic message when a selector is
	 * unknown.
	 *
	 * @param string $issue_code Issue code the message is registered under: ISSUE_SETTINGS_RESTORED or ISSUE_CORRUPTED_SETTINGS.
	 * @param array{context?: array<string, mixed>} $issue_details Issue configuration passed to CRB_Issues::register() as the third parameter.
	 *
	 * @return string Translated message ready to be displayed to the administrator.
	 *
	 * @since 9.9.1
	 */
	private static function get_issue_message( string $issue_code, array $issue_details ): string {

		$issue_context = $issue_details['context'] ?? array();

		// Successful restoration: the message is selected by the context the restored backup copy was created in.

		if ( $issue_code === self::ISSUE_SETTINGS_RESTORED ) {

			$restoration_messages = array(
				self::CONTEXT_SETTINGS_UPDATE =>
					/* translators: %s is the localized date and time when the settings backup was created. */
					__( 'The plugin settings stored in the website database were corrupted and could not be loaded. The settings have been automatically restored from a backup copy created on %s during a plugin settings update. Some recent changes may be missing. To resolve this issue, review the plugin settings and save them.', 'wp-cerber' ),
				self::CONTEXT_SETTINGS_IMPORT =>
					/* translators: %s is the localized date and time when the settings backup was created. */
					__( 'The plugin settings stored in the website database were corrupted and could not be loaded. The settings have been automatically restored from a backup copy created on %s during a settings import. Some recent changes may be missing. To resolve this issue, review the plugin settings and save them.', 'wp-cerber' ),
				self::CONTEXT_PLUGIN_UPGRADE  =>
					/* translators: %s is the localized date and time when the settings backup was created. */
					__( 'The plugin settings stored in the website database were corrupted and could not be loaded. The settings have been automatically restored from a backup copy created on %s during a plugin upgrade. Some recent changes may be missing. To resolve this issue, review the plugin settings and save them.', 'wp-cerber' ),
				self::CONTEXT_SCHEDULED_SYNC  =>
					/* translators: %s is the localized date and time when the settings backup was created. */
					__( 'The plugin settings stored in the website database were corrupted and could not be loaded. The settings have been automatically restored from a backup copy created on %s during a scheduled daily synchronization. Some recent changes may be missing. To resolve this issue, review the plugin settings and save them.', 'wp-cerber' ),
			);

			/* translators: %s is the localized date and time when the settings backup was created. */
			$generic_restoration_message = __( 'The plugin settings stored in the website database were corrupted and could not be loaded. The settings have been automatically restored from a backup copy created on %s. Some recent changes may be missing. To resolve this issue, review the plugin settings and save them.', 'wp-cerber' );

			$backup_context = (string) ( $issue_context['backup_context'] ?? '' );
			$backup_date = cerber_date( (int) ( $issue_context['backup_timestamp'] ?? 0 ), false );

			return sprintf( $restoration_messages[ $backup_context ] ?? $generic_restoration_message, $backup_date );
		}

		// Failed restoration, reported under ISSUE_CORRUPTED_SETTINGS: the message is selected by the failure reason and states the outcome of the default settings recovery.

		$failure_reason = (string) ( $issue_context['backup_status'] ?? '' );
		$default_settings_restored = (bool) ( $issue_context['default_settings_restored'] ?? false );

		$failure_messages = array(
			'backup_missing'       => $default_settings_restored
				? __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration was not possible because no backup copy of the settings was available. The corrupted configuration has been replaced with default settings. Review the plugin settings and save any required changes.', 'wp-cerber' )
				: __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration was not possible because no backup copy of the settings was available. Default settings could not be saved to the database. Review the plugin settings and save any required changes.', 'wp-cerber' ),
			'backup_invalid'       => $default_settings_restored
				? __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration was not possible because the available backup copy was invalid. The corrupted configuration has been replaced with default settings. Review the plugin settings and save any required changes.', 'wp-cerber' )
				: __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration was not possible because the available backup copy was invalid. Default settings could not be saved to the database. Review the plugin settings and save any required changes.', 'wp-cerber' ),
			'restore_write_failed' => $default_settings_restored
				? __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration failed because the settings from the backup copy could not be written to the database. The corrupted configuration has been replaced with default settings. Review the plugin settings and save any required changes.', 'wp-cerber' )
				: __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration failed because the settings from the backup copy could not be written to the database. Default settings could not be saved to the database. Review the plugin settings and save any required changes.', 'wp-cerber' ),
		);

		$generic_failure_message = $default_settings_restored
			? __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration from a backup copy was not possible. The corrupted configuration has been replaced with default settings. Review the plugin settings and save any required changes.', 'wp-cerber' )
			: __( 'The plugin settings stored in the website database were corrupted and could not be loaded. Automatic restoration from a backup copy was not possible. Default settings could not be saved to the database. Review the plugin settings and save any required changes.', 'wp-cerber' );

		return $failure_messages[ $failure_reason ] ?? $generic_failure_message;
	}
}
