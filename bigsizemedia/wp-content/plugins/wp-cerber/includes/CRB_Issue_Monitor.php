<?php

/**
 * Coordinates detector-based system issue discovery and lifecycle management.
 *
 * This class owns the detector registry and execution policy. It runs detectors
 * when their schedule or request context makes them due, reconciles active and
 * resolved findings with CRB_Issues, and surfaces critical issues to administrators
 * during the current request.
 *
 * It serves as the coordination layer between individual issue detectors,
 * the persistent issue registry, and immediate administrative notifications.
 *
 * @version 1.5
 * @updated 2026-08-09
 * @since 9.6.14
 */
final class CRB_Issue_Monitor {

	/**
	 * Displays the critical issues detected in the current request as admin notices.
	 *
	 * Called on every plugin admin page except the dashboard, which shows the System Readiness widget instead.
	 * Executes the due detectors through run(), which also synchronizes persistent storage,
	 * and renders only the findings of severity SEVERITY_CRITICAL.
	 *
	 * The text comes from the detector result in memory, not from CRB_Issues::fetch(), so it is
	 * neither translated at render time nor formatted with stored message arguments.
	 *
	 * @return void
	 */
	public static function detect_critical_issues(): void {

		// 1. Execute detectors and gather active issues
		$issues = self::run();

		if ( empty( $issues ) ) {
			return;
		}

		$ui_warning_messages = array();

		// 2. Prepare UI representation
		foreach ( $issues as $issue_code => $details ) {
			$message = $details['message'];
			$issue_details = $details['issue_details'];

			$severity = $issue_details['severity'] ?? '';

			// Skip non-critical messages in the UI notice context
			if ( $severity == CRB_Issues::SEVERITY_CRITICAL ) {
				// Construct a temporary issue array to reuse the formatter logic
				$temp_issue = array(
					'code'    => $issue_code,
					'message' => $message,
					'context' => $issue_details['context'] ?? array(),
				);

				$enriched_issue = self::enrich_issue_details( $temp_issue );
				$final_message = $enriched_issue['message'];

				// For simple admin notices, we join the diagnostic details array into a string
				// to preserve the existing visual behavior (concatenation).
				if ( ! empty( $enriched_issue['diagnostic_details'] ) && is_array( $enriched_issue['diagnostic_details'] ) ) {
					$final_message .= ' ' . implode( '. ', $enriched_issue['diagnostic_details'] );
				}

				$formatted_message = self::format_ui_message( $final_message, $issue_details );

				// Using the issue code as key ensures unique notices
				$ui_warning_messages[ $issue_code ] = '<b>' . __( 'Warning!', 'wp-cerber' ) . '</b> ' . $formatted_message;
			}
		}

		// 3. Render admin notices
		if ( ! empty( $ui_warning_messages ) ) {
			cerber_admin_notice( $ui_warning_messages );
		}
	}

	/**
	 * Returns every persistent issue for the System Readiness widget of the dashboard.
	 *
	 * Forces a full detector run, which rewrites the stored copy of every detector issue in the
	 * current request, then reads the whole registry through CRB_Issues::fetch(), covering all
	 * severities and all producers. An issue the forced run did not rewrite, for example one whose
	 * detector threw, is returned as it was stored. Domain-specific formatters then enrich the
	 * messages with diagnostic details taken from the issue context.
	 *
	 * @return array<int, array{
	 * id: string,
	 * code: string,
	 * section: string,
	 * message: string,
	 * severity: string,
	 * type: string,
	 * created_at: int,
	 * last_occurred_at: int,
	 * count: int,
	 * dismissable: bool,
	 * doc_page: string,
	 * resolution: string|false,
	 * setting_id: string,
	 * context: array,
	 * diagnostic_details?: array<string>
	 * }> List of all collected issues sorted by relevance and enriched with details.
	 */
	public static function build_summary(): array {
		// 1. Force run detectors to update the storage with fresh data
		self::run( true );

		// 2. Retrieve issues from the global storage using the standard fetch mechanism
		if ( class_exists( 'CRB_Issues' ) ) {
			$issues = CRB_Issues::fetch();

			// 3. Apply domain-specific formatters
			foreach ( $issues as &$issue ) {
				$issue = self::enrich_issue_details( $issue );
			}
			unset( $issue ); // Break reference

			return $issues;
		}

		return array();
	}

	/**
	 * Applies domain-specific formatting logic to an issue based on its code.
	 *
	 * This method populates the 'diagnostic_details' field if a dedicated formatter exists.
	 * It also merges raw diagnostic lines from the issue context if provided.
	 *
	 * @param array $issue The raw issue array from storage.
	 *
	 * @return array The enriched issue array with 'diagnostic_details' (array of strings) populated if applicable.
	 */
	private static function enrich_issue_details( array $issue ): array {
		if ( empty( $issue['code'] ) ) {
			return $issue;
		}

		$diagnostic_details = array();

		// Dispatch based on issue code
		switch ( $issue['code'] ) {
			case CRB_Messaging::SEND_ISSUE_CODE:
				$diagnostic_details = self::format_email_issue( $issue );
				break;
			// Future formatters can be added here
		}

		// Merge raw diagnostic messages from the context if available
		if ( ! empty( $issue['context']['raw_diagnostic'] ) && is_array( $issue['context']['raw_diagnostic'] ) ) {
			$diagnostic_details = array_merge( $diagnostic_details, $issue['context']['raw_diagnostic'] );
		}

		if ( ! empty( $diagnostic_details ) ) {
			$issue['diagnostic_details'] = $diagnostic_details;
		}

		return $issue;
	}

	/**
	 * Formats email error issues using the raw mailer context data.
	 *
	 * @param array $issue The issue array containing context data.
	 *
	 * @return array<string> An array of diagnostic detail strings.
	 */
	private static function format_email_issue( array $issue ): array {
		$context = $issue['context'] ?? array();
		$mailer_data = $context['mailer_data'] ?? array();

		if ( empty( $mailer_data ) ) {
			return array();
		}

		$details = array();

		$error_msg = $mailer_data['error_message'] ?? '';
		$code = $mailer_data['exception_code'] ?? '';
		$smtp_host = $mailer_data['smtp_host'] ?? '';
		$smtp_user = $mailer_data['smtp_user'] ?? '';
		$to = $mailer_data['to'] ?? '';
		$subject = $mailer_data['subject'] ?? '';

		$details[] = $error_msg;

		if ( $smtp_host ) {
			$details[] = __( 'SMTP server:', 'wp-cerber' ) . ' ' . $smtp_host;
		}

		if ( $smtp_user ) {
			$details[] = __( 'SMTP username:', 'wp-cerber' ) . ' ' . $smtp_user;
		}

		if ( ! empty( $to ) ) {
			$recipients = is_array( $to ) ? implode( ',', $to ) : (string) $to;
			$details[] = __( 'Recipients:', 'wp-cerber' ) . ' ' . $recipients;
		}

		if ( $subject ) {
			$details[] = __( 'Subject:', 'wp-cerber' ) . ' "' . $subject . '"';
		}

		if ( $code ) {
			$details[] = __( 'Error code:', 'wp-cerber' ) . ' ' . $code;
		}

		return $details;
	}

	/**
	 * Executes detectors, synchronizes persistent storage, and returns the issues detected now.
	 *
	 * Registers every finding reported as active and deletes every finding reported as resolved,
	 * which makes this method the mechanism that populates CRB_Issues with detector issues. A
	 * detector that was not due, or that threw and was skipped, leaves its stored issues untouched.
	 * The returned messages are built during this request and are never read back from storage.
	 *
	 * @param bool $force_run Optional. If true, runs every detector regardless of its schedule.
	 *
	 * @return array<string, array{message: string, issue_details: array}> Associative array of active issues where keys are the issue codes.
	 */
	public static function run( bool $force_run = false ): array {

		// 1. Run detectors and gather all statuses (both active and resolved)

		$detection_results = self::run_detectors( $force_run );

		if ( empty( $detection_results ) ) {
			return array();
		}

		$active_issues = array();

		// 2. Process results: Synchronize with storage
		foreach ( $detection_results as $issue_code => $status_data ) {

			if ( is_array( $status_data ) ) {
				// Case: Issue Detected -> Register/Update in storage
				$message = $status_data['message'] ?? '';
				$issue_details = $status_data['issue_details'] ?? array();

				// Ensure details are array
				if ( ! is_array( $issue_details ) ) {
					$issue_details = array();
				}

				CRB_Issues::register( $issue_code, $message, $issue_details );

				// Add to the active list to be returned
				$active_issues[ $issue_code ] = array(
					'message'       => $message,
					'issue_details' => $issue_details,
				);
			}
			else {
				// Case: Issue Resolved (false returned) -> Remove from storage
				CRB_Issues::delete_item( $issue_code );
			}
		}

		return $active_issues;
	}

	/**
	 * Execute detectors based on the current context, individual schedules, or a force flag.
	 *
	 * This function orchestrates the execution of detectors defined in the registry.
	 * It accumulates results from all executed detectors into a single status map.
	 *
	 * @param bool $force_run Optional. If true, forces all detectors to run regardless of their schedule or context.
	 *
	 * @return array<string, array{message: string, issue_details: array}|false> Map of issue codes to their status (array of details if active, false if resolved).
	 */
	public static function run_detectors( bool $force_run = false ): array {
		$detector_registry = self::get_registry();
		$accumulated_results = array(); // Map of Code => Status
		$executed_detector_ids = array();
		$active_admin_page_id = crb_admin_get_page();
		$current_timestamp = time();

		// -------------------------------------------------------------------------
		// I/O Optimization: Batch Load Timers
		// -------------------------------------------------------------------------
		// Instead of fetching a separate option for each detector, we load all timers
		// from a single storage entry. This reduces database reads to O(1).
		$timer_storage_key = '_issue_detector_timers';
		$detector_last_run_map = cerber_get_set( $timer_storage_key );
		$has_unsaved_timer_changes = false;

		if ( ! is_array( $detector_last_run_map ) ) {
			$detector_last_run_map = array();
		}

		foreach ( $detector_registry as $detector_config ) {
			$detector_id = $detector_config['id'];

			$is_execution_due = $force_run;

			if ( ! $is_execution_due ) {
				// ---------------------------------------------------------------------
				// 1. Check "Always Run" Rule
				// ---------------------------------------------------------------------
				if ( ! empty( $detector_config['always_run'] ) ) {
					$is_execution_due = true;
				}
				// ---------------------------------------------------------------------
				// 2. Check "Contextual Run" Rule (Page Context)
				// ---------------------------------------------------------------------
				elseif ( ! empty( $detector_config['page_run'] ) && $detector_config['page_run'] === $active_admin_page_id ) {
					$is_execution_due = true;
				}
				// ---------------------------------------------------------------------
				// 3. Check "Periodic Run" Rule (Time Interval)
				// ---------------------------------------------------------------------
				elseif ( ! empty( $detector_config['periodic_run'] ) ) {
					$run_interval_seconds = (int) $detector_config['periodic_run'];
					// Retrieve last run time from the local array cache.
					$last_run_timestamp = isset( $detector_last_run_map[ $detector_id ] ) ? (int) $detector_last_run_map[ $detector_id ] : 0;

					if ( ( $current_timestamp - $last_run_timestamp ) > $run_interval_seconds ) {
						$is_execution_due = true;
					}
				}
			}

			// ---------------------------------------------------------------------
			// 4. Execution, Fault Tolerance & State Update
			// ---------------------------------------------------------------------

			// Verify if the detector should run and hasn't been executed yet in this request.
			if ( $is_execution_due && ! in_array( $detector_id, $executed_detector_ids, true ) ) {

				try {
					// Execute the callback.
					$detection_result = call_user_func( $detector_config['callback'] );

					$executed_detector_ids[] = $detector_id;

					if ( ! empty( $detector_config['periodic_run'] ) ) {
						$detector_last_run_map[ $detector_id ] = $current_timestamp;
						$has_unsaved_timer_changes = true;
					}

					// Merge results into the main map
					if ( is_array( $detection_result ) ) {
						$accumulated_results = array_merge( $accumulated_results, $detection_result );
					}

				} catch ( Throwable $e ) {
					// Fault Tolerance: Log the crash and continue with the next detector.
					// We use the central bug hunter service to log the stack trace and details.
					if ( class_exists( 'CRB_Bug_Hunter' ) ) {
						CRB_Bug_Hunter::collect_throwable( $e );
					}
				}
			}
		}

		// -------------------------------------------------------------------------
		// I/O Optimization: Batch Save Timers
		// -------------------------------------------------------------------------
		// If any timer was updated during execution, we save the entire array back to storage.
		if ( $has_unsaved_timer_changes ) {
			cerber_update_set( $timer_storage_key, $detector_last_run_map );
		}

		return $accumulated_results;
	}

	/**
	 * Formats a single issue message for UI display.
	 *
	 * Appends a documentation link if a 'doc_page' key exists in the error data.
	 * This ensures that presentation logic (HTML) is kept separate from storage logic.
	 *
	 * @param string $message The raw issue message.
	 * @param array $issue_details Contextual data associated with the issue.
	 *
	 * @return string The formatted message containing HTML if applicable.
	 */
	private static function format_ui_message( string $message, array $issue_details ): string {
		$settings_id = (string) $issue_details['setting_id'] ?? '';

		if ( $settings_id &&
		     $link = crb_get_setting_link( $settings_id ) ) {
			$message .= $link;
		}

		$documentation_url = $issue_details['doc_page'] ?? '';

		if ( $documentation_url ) {
			$message .= ' [&nbsp;<a href="' . crb_escape_url( $documentation_url ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Documentation', 'wp-cerber' ) . '</a>&nbsp;]';
		}

		return $message;
	}

	/**
	 * Retrieve the registry of issue detectors.
	 *
	 * Provides a list of periodic and contextual detectors extracted from the legacy monitor.
	 * Each entry defines the execution scope, interval, and logic callback.
	 *
	 * Registry Entry Structure:
	 * - 'id'           (string)   : Unique detector ID.
	 * - 'callback'     (callable) : Closure strictly returning an associative array of issue statuses.
	 * - 'periodic_run' (int)      : Optional. Interval in seconds for periodic execution.
	 * - 'page_run'     (string)   : Optional. Admin page ID where this detector must run immediately (contextual).
	 * - 'always_run'   (bool)     : Optional. If true, runs unconditionally on every run.
	 *
	 * Callback Contract:
	 * All detector callbacks must strictly return an associative array.
	 * - Key: Issue Code (string).
	 * - Value: Array (issue details) if active, FALSE if resolved/inactive.
	 *
	 * @return array<int, array{
	 * id: string,
	 * callback: callable(): array<string, array{message: string, issue_details: array}|false>,
	 * periodic_run?: int,
	 * page_run?: string,
	 * always_run?: bool
	 * }> List of registered detectors.
	 */
	private static function get_registry(): array {
		static $detector_registry = null;

		if ( null !== $detector_registry ) {
			return $detector_registry;
		}

		// -------------------------------------------------------------------------
		// Logic Definitions
		// -------------------------------------------------------------------------

		$security_detector = function (): array {
			$results = [
				'security_ip_detection_failed' => false,
				'security_boot_mode_mismatch'  => false,
			];

			// 1. Check IP detection availability
			if ( $issue = crb_resolve_remote_ip( true ) ) {
				$results['security_ip_detection_failed'] = [
					'message'       => $issue,
					'issue_details' => [
						'doc_page' => 'https://wpcerber.com/wordpress-ip-address-detection/',
						'severity' => CRB_Issues::SEVERITY_CRITICAL,
					]
				];
			}

			// 2. Check Boot Mode consistency
			if ( cerber_get_mode() != crb_get_settings( 'boot-mode' ) ) {
				$results['security_boot_mode_mismatch'] = [
					'message'       => __( 'WP Cerber is initialized in a different mode that does not match the plugin settings. Check the "Load security engine" setting.', 'wp-cerber' ),
					'issue_details' => [
						'setting_id' => 'boot-mode',
						'severity'   => CRB_Issues::SEVERITY_WARNING
					]
				];
			}

			return $results;
		};

		$auto_updates_detector = function (): array {
			$results = [
				'update_repo_disabled'    => false,
				'update_repo_unreachable' => false,
				'update_wp_auto_disabled' => false,
			];

			$repo_link = 'https://wpcerber.com/automatic-updates-for-wp-cerber/';

			// Phase 1: Repository Access Check ---

			if ( ! crb_get_settings( 'cerber_sw_repo' ) ) {
				// Case: Repository disabled in settings
				$results['update_repo_disabled'] = [
					'message'       => __( 'WP Cerber repository is disabled. As a result, your installation does not receive updates and security fixes. Enable the repository in the main plugin settings to keep WP Cerber fully up to date.', 'wp-cerber' ),
					'issue_details' => [
						'setting_id' => 'cerber_sw_repo',
						'doc_page'   => $repo_link,
						'severity'   => CRB_Issues::SEVERITY_ADVISORY,
					]
				];
			}
			elseif ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
				// Case: Repository enabled but external requests are blocked by WP
				$issue_message = '';

				if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
					$issue_message = __( 'To enable WP Cerber updates, add the <samp>WP_ACCESSIBLE_HOSTS</samp> constant defined as "downloads.wpcerber.com" to your <samp>wp-config.php</samp> file', 'wp-cerber' );
				}
				else {
					$hosts = WP_ACCESSIBLE_HOSTS;
					if ( false === strpos( $hosts, 'downloads.wpcerber.com' ) && false === strpos( $hosts, '*.wpcerber.com' ) ) {
						/* translators: %s is the current value of the WP_ACCESSIBLE_HOSTS constant. */
						$const_def = empty( $hosts ) ? __( 'Currently, the <samp>WP_ACCESSIBLE_HOSTS</samp> constant contains no allowed hosts', 'wp-cerber' ) : sprintf( __( 'Currently, the <samp>WP_ACCESSIBLE_HOSTS</samp> constant is defined as: %s', 'wp-cerber' ), crb_escape_html( $hosts ) );
						$issue_message = __( 'To enable WP Cerber updates, add <strong>downloads.wpcerber.com</strong> to the <samp>WP_ACCESSIBLE_HOSTS</samp> constant', 'wp-cerber' ) . ' ' . $const_def;
					}
				}

				if ( $issue_message ) {
					$results['update_repo_unreachable'] = [
						'message'       => $issue_message,
						'issue_details' => [
							'doc_page' => $repo_link,
							'severity' => CRB_Issues::SEVERITY_WARNING,
						]
					];
				}
			}

			// Phase 2: Auto-Update Settings Check ---

			if ( crb_get_settings( 'cerber_sw_auto' ) ) {
				crb_load_dependencies( 'wp_is_auto_update_enabled_for_type' );

				if ( ! wp_is_auto_update_enabled_for_type( 'plugin' ) ) {
					$auto_const = ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) ? ' ' . __( 'The WordPress <samp>AUTOMATIC_UPDATER_DISABLED</samp> constant is defined.', 'wp-cerber' ) : '';

					$results['update_wp_auto_disabled'] = [
						'message'       => __( 'WP Cerber does not get automatic updates because automatic updates for plugins on this website are disabled.', 'wp-cerber' ) . ' ' . $auto_const,
						'issue_details' => [
							'doc_page' => $repo_link,
							'severity' => CRB_Issues::SEVERITY_ADVISORY,
						]
					];
				}
			}

			return $results;
		};

		$cerber_dir = function (): array {
			$results = [
				'cerber_dir_issue' => false,
			];

			if ( defined( 'CERBER_FOLDER_PATH' ) ) {
				$result = cerber_get_my_folder();
				if ( is_wp_error( $result ) ) {
					$results ['cerber_dir_issue'] = [
						'message'       => $result->get_error_message(),
						'issue_details' => $result->get_error_data() ?: []
					];
				}
			}

			return $results;
		};

		$shield_detector = function (): array {
			$results = [
				'cerber_ds_issue' => false,
			];

			$result = CRB_DS::check_errors();

			if ( is_wp_error( $result ) ) {
				$results ['cerber_ds_issue'] = [
					'message'       => $result->get_error_message(),
					'issue_details' => $result->get_error_data() ?: []
				];
			}

			return $results;
		};

		$environment_detector = function (): array {
			$results = [
				'env_ext_mbstring_missing'   => false,
				'env_ext_curl_broken'        => false,
				'traffic_inspector_disabled' => false,
			];

			$ex_list = get_loaded_extensions();

			if ( ! in_array( 'mbstring', $ex_list ) || ! function_exists( 'mb_convert_encoding' ) ) {
				$results['env_ext_mbstring_missing'] = [
					'message'       => __( 'Required PHP extension <strong>mbstring</strong> is not enabled on this website. Some plugin features do work properly. Please enable the PHP <strong>mbstring</strong> extension (multibyte string support) in your hosting control panel.', 'wp-cerber' ),
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_WARNING,
					]
				];
			}

			// The cURL availability check

			if ( $curl_issue = self::detect_curl_issues() ) {
				$results['env_ext_curl_broken'] = [
					'message'       => $curl_issue,
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_WARNING,
					]
				];
			}

			//3. Check if the firewall is disabled
			if ( ! crb_get_settings( 'tienabled' ) ) {
				$results['traffic_inspector_disabled'] = [
					'message'       => __( 'Traffic Inspector is disabled. Malicious traffic may reach your site without inspection and filtering. Enable Traffic Inspector to restore firewall protection.', 'wp-cerber' ),
					'issue_details' => [
						'setting_id' => 'tienabled',
						'severity'   => CRB_Issues::SEVERITY_CRITICAL
					]
				];
			}

			return $results;
		};

		$platform_requirements_detector = function (): array {
			$results = [
				'req_php_version_insufficient' => false,
				'req_wp_version_insufficient'  => false,
				'cloud_debug_active'           => false,
				'network_debug_active'         => false,
			];

			if ( version_compare( CERBER_REQ_PHP, phpversion(), '>' ) ) {
				/* translators: %1$s is the required PHP version, %2$s is the current PHP version. */
				$message = sprintf( __( 'WP Cerber requires PHP version %1$s or higher, but your web server is currently running PHP %2$s. Please update PHP to the required version or newer.', 'wp-cerber' ), CERBER_REQ_PHP, phpversion() );
				$results['req_php_version_insufficient'] = [
					'message'       => $message,
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_CRITICAL,
					]
				];
			}

			if ( ! crb_wp_version_compare( CERBER_REQ_WP ) ) {
				/* translators: %1$s is the required WordPress version, %2$s is the current WordPress version. */
				$message = sprintf( __( 'WP Cerber requires WordPress version %1$s or higher. Your WordPress version is %2$s. Please update your WordPress to the latest version.', 'wp-cerber' ), CERBER_REQ_WP, cerber_get_wp_version() );
				$results['req_wp_version_insufficient'] = [
					'message'       => $message,
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_WARNING,
					]
				];
			}

			if ( defined( 'CERBER_CLOUD_DEBUG' ) && CERBER_CLOUD_DEBUG ) {
				$results['cloud_debug_active'] = [
					'message'       => __( 'Diagnostic logging of cloud requests is enabled (CERBER_CLOUD_DEBUG is defined). This is intended for troubleshooting and should be disabled on production servers.', 'wp-cerber' ),
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_WARNING,
					]
				];
			}

			if ( defined( 'CERBER_NETWORK_DEBUG' ) && CERBER_NETWORK_DEBUG ) {
				$results['network_debug_active'] = [
					'message'       => __( 'Diagnostic logging of network requests is enabled (CERBER_NETWORK_DEBUG is defined). This is intended for troubleshooting and should be disabled on production servers.', 'wp-cerber' ),
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_WARNING,
					]
				];
			}

			return $results;
		};

		$database_driver_detector = function (): array {
			$results = [
				'db_driver_no_mysqlnd' => false,
			];

			// The MySQL Native Driver (mysqlnd) is what exposes mysqli_result::fetch_all().
			// Its absence indicates PHP's mysqli extension is built against the legacy
			// libmysqlclient library. Evaluate only when mysqli is the active driver to
			// avoid a false advisory on non-mysqli setups.
			if ( extension_loaded( 'mysqli' ) && ! function_exists( 'mysqli_fetch_all' ) ) {
				$results['db_driver_no_mysqlnd'] = [
					'message'       => __( 'This website runs on an outdated MySQL database driver. PHP on this server uses the legacy <strong>libmysqlclient</strong> library instead of the recommended MySQL Native Driver (<strong>mysqlnd</strong>). WP Cerber remains fully functional, but <strong>mysqlnd</strong> provides better performance, lower memory usage, and full compatibility with modern database features. For optimal performance, ask your hosting provider to enable the <strong>mysqlnd</strong> driver for PHP.', 'wp-cerber' ),
					'issue_details' => [
						'severity' => CRB_Issues::SEVERITY_ADVISORY,
					]
				];
			}

			return $results;
		};

		// -------------------------------------------------------------------------
		// Registry Declaration
		// -------------------------------------------------------------------------

		$detector_registry = array(
			array(
				'id'           => 'cerber-security',
				'callback'     => $security_detector,
				'periodic_run' => 120,
				'page_run'     => 'cerber-security',
			),
			array(
				'id'           => 'auto-updates',
				'callback'     => $auto_updates_detector,
				'periodic_run' => 120,
			),
			array(
				'id'           => 'cerber-integrity',
				'callback'     => $cerber_dir,
				'periodic_run' => 120,
				'page_run'     => 'cerber-integrity',
			),
			array(
				'id'           => 'cerber-shield',
				'callback'     => $shield_detector,
				'periodic_run' => 120,
				'page_run'     => 'cerber-shield',
			),
			array(
				'id'           => 'env',
				'callback'     => $environment_detector,
				'periodic_run' => 60,
			),
			array(
				'id'         => 'platform-requirements',
				'callback'   => $platform_requirements_detector,
				'always_run' => true,
			),
			array(
				'id'           => 'database-driver',
				'callback'     => $database_driver_detector,
				'periodic_run' => 3600,
			),
		);

		return $detector_registry;
	}

	/**
	 * Checks for cURL availability and SSL/TLS support.
	 *
	 * @return string A diagnostic readiness message if cURL is not available or not configured correctly.
	 *                Returns an empty string if no issues are detected.
	 * @since 9.6.14
	 */
	public static function detect_curl_issues(): string {

		if ( ! extension_loaded( 'curl' ) ) {
			return __( 'The <strong>cURL</strong> PHP extension is not available. It is required for downloading security updates from the cloud. Enable the cURL PHP extension on your server or ask your hosting provider for assistance.', 'wp-cerber' );
		}

		$required_functions = array(
			'curl_init',
			'curl_exec',
			'curl_setopt',
			'curl_errno',
			'curl_error',
			'curl_getinfo',
			'curl_version',
			'curl_setopt_array',
		);

		$missing_functions = array();

		foreach ( $required_functions as $function_name ) {
			if ( ! function_exists( $function_name ) ) {
				$missing_functions[] = $function_name;
			}
		}

		if ( $missing_functions ) {
			if ( 1 === count( $missing_functions ) ) {
				return sprintf(
					__( 'The <strong>%s</strong> function is disabled on this server. It is required for downloading security updates from the cloud. Please check the <samp>disable_functions</samp> directive in <samp>php.ini</samp> or ask your hosting provider to enable full cURL support in PHP.', 'wp-cerber' ),
					$missing_functions[0]
				);
			}

			$functions_list = '<samp>' . implode( '</samp>, <samp>', $missing_functions ) . '</samp>';

			return sprintf(
				__( 'The following <strong>cURL</strong> PHP functions are not available on this server: %s. They are required for downloading security updates from the cloud. Please check the <samp>disable_functions</samp> directive in <samp>php.ini</samp> or ask your hosting provider to enable full cURL support in PHP.', 'wp-cerber' ),
				$functions_list
			);
		}

		$curl = curl_init();

		if ( false === $curl ) {
			return __( 'Unable to initialize the <strong>cURL</strong> library. It is required for downloading security updates from the cloud. Check PHP configuration or contact your hosting provider to verify cURL support.', 'wp-cerber' );
		}

		$version = curl_version();

		if ( empty( $version['features'] ) || ! ( $version['features'] & CURL_VERSION_SSL ) ) {
			return __( 'The installed <strong>cURL</strong> library does not support <strong>SSL/TLS</strong> encryption. It is required for downloading security updates from the cloud. Ask your hosting provider to enable SSL/TLS support in the cURL library.', 'wp-cerber' );
		}

		return '';
	}
}