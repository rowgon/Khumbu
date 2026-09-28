<?php

/**
 * Class CRB_Issue_Monitor
 *
 * Serves as the central domain service for detecting, monitoring, and managing
 * system issues and configuration anomalies within WP Cerber.
 *
 * Responsibilities:
 * 1. Registry: Defines the rules and logic for issue detection.
 * 2. Runner: Orchestrates the execution of detectors based on schedules and context.
 * 3. Management: Handles user interactions such as dismissing issues (future scope).
 *
 * @since 9.6.14
 * @version 1.43
 * @updated 2026-01-26
 */
class CRB_Issue_Monitor {

	/**
	 * Main UI entry point. Monitors system health and displays admin notices.
	 *
	 * This method acts as the Presentation Layer. It invokes the detection logic via run(),
	 * receiving a normalized array of active issues, and handles their visualization (Admin Notices).
	 *
	 * @return void
	 */
	public static function monitor(): void {

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
	 * Generates a comprehensive summary of all system issues.
	 *
	 * This method acts as a high-level facade for dashboards or reporting tools.
	 * It forces a fresh detection cycle to ensure data is up-to-date, retrieves
	 * the full list of issues from persistent storage, and applies domain-specific
	 * formatters to enrich issue messages with context data.
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
	 * Orchestrates the detection process, persists results, and returns active issues.
	 *
	 * This is the pure Business Logic Layer.
	 * It ensures the storage (CRB_Issues) is synchronized with the current system state
	 * by adding newly detected issues AND removing resolved ones (Auto-Healing).
	 *
	 * Workflow:
	 * 1. Runs detectors to get the full map of issue statuses.
	 * 2. Updates the persistent storage (Add/Remove).
	 * 3. Returns a flat, associative array of ACTIVE issues for the caller.
	 *
	 * @param bool $force_run Optional. If true, forces all detectors to run regardless of schedules.
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

				CRB_Issues::add( $issue_code, $message, $issue_details );

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
						CRB_Bug_Hunter::log_throwable( $e );
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
			if ( $issue = cerber_extract_remote_ip( true ) ) {
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

			// Enhanced cURL availability check
			$curl_issue = '';
			if ( ! extension_loaded( 'curl' ) ) {
				$curl_issue = __( 'The <strong>cURL</strong> PHP extension is not available. It is required for downloading security updates from the cloud. Enable the cURL PHP extension on your server or ask your hosting provider for assistance.', 'wp-cerber' );
			}
			elseif ( ! function_exists( 'curl_init' ) ) {
				$curl_issue = __( 'The <strong>curl_init</strong> function is disabled on this server. It is required for downloading security updates from the cloud. Please check the <samp>disable_functions</samp> directive in <samp>php.ini</samp> or ask your hosting provider to enable cURL support in PHP.', 'wp-cerber' );
			}
			else {
				$curl = curl_init();
				if ( false === $curl ) {
					$curl_issue = __( 'Unable to initialize <strong>cURL</strong> library. It is required for downloading security updates from the cloud. Check PHP configuration or contact your hosting provider to verify cURL support.', 'wp-cerber' );
				}
				else {
					$version = curl_version();
					if ( ! ( $version['features'] & CURL_VERSION_SSL ) ) {
						$curl_issue = __( 'The installed <strong>cURL</strong> library does not support <strong>SSL/TLS</strong> encryption. It is required for downloading security updates from the cloud. Ask your hosting provider to enable SSL/TLS support in the cURL library.', 'wp-cerber' );
					}
				}
			}

			if ( $curl_issue ) {
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
				$message = sprintf( __( 'WP Cerber requires PHP version %1$s or higher, but your web server is currently running PHP %2$s. Please update PHP to еру required version or newer.', 'wp-cerber' ), CERBER_REQ_PHP, phpversion() );
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
		);

		return $detector_registry;
	}
}