<?php

/**
 * Central catalog of WP Cerber settings screens, sections, and field definitions.
 *
 * This registry owns the application-level contract that connects stored setting
 * identifiers to the admin UI, settings update pipeline, contextual help, Nexus
 * settings processing, and add-on settings screens. It is the authoritative source
 * for discovering which fields belong to a screen and for resolving a setting back
 * to the navigation metadata needed by nearby admin components.
 *
 * The registry is read-only with respect to persisted settings: it does not save,
 * delete, or validate submitted option values. Building the catalog may invoke
 * registered add-on configuration providers and context-aware services used to
 * populate admin choices, so callers should treat the returned definitions as
 * request-context dependent rather than immutable application constants.
 *
 * @since 9.8.4
 */
final class CRB_Settings_Registry {

	/**
	 * Mapping admin page tab ID => settings screen ID
	 */
	const TAB_TO_SCREEN = array(
		'scan_settings'    => 'scanner',
		'scan_schedule'    => 'schedule', // see const CERBER_OPT_E = 'cerber-schedule';
		'scan_policy'      => 'policies',
		'ti_settings'      => 'traffic',
		'captcha'          => 'recaptcha',
		'cerber-recaptcha' => 'antispam',
		'global_policies'  => 'users',
		'cerber-shield'    => 'user_shield', // see const CERBER_OPT_US = 'cerber-user_shield';

		'cerber-nexus' => 'nexus-slave',
		'nexus_slave'  => 'nexus-slave',
	);

	/**
	 * Retrieves settings definitions for the requested registry scope.
	 *
	 * With no arguments, returns all registered section definitions keyed by section ID.
	 * With settings_screen_id, returns only sections attached to that known settings screen.
	 * With setting, returns the matching field definition enriched with its section and
	 * navigation metadata. Unknown screens, unknown settings, or non-array arguments return false.
	 * Add-on screens and sections are included when registered. Some field labels and
	 * descriptions may reflect license state or the current admin page context.
	 *
	 * @param mixed $args Optional lookup arguments. Pass an array with supported keys
	 *                    settings_screen_id or setting.
	 *
	 * @return array<string,array<string,mixed>>|array<string,mixed>|false Section definitions,
	 *                                                                  a field definition, or false.
	 *
	 * @see cerber_get_defaults()
	 */
	public static function get_config( $args = array() ) {

		static $sections, $section_to_screen;

		if ( $args && ! is_array( $args ) ) {
			return false;
		}

		// Register addon setting screens
		$addons_config = CRB_Addons::get_settings_config();

		$screens = self::get_screens();

		if ( ! empty( $addons_config['screens'] ) ) {
			$screens = array_merge( $screens, $addons_config['screens'] );
		}

		if ( ! $sections ) {
			$sections = self::get_sections();
		}

		if ( ! empty( $addons_config['sections'] ) ) {
			$sections = array_merge( $sections, $addons_config['sections'] );
		}

		// Some settings are available in the PRO version only

		if ( ! lab_lab() ) {
			$sections['slave_settings']['fields']['slave_access']['label'] = '<a href="https://wpcerber.com/pro/" target="_blank">' . __( 'The full access mode requires the PRO version of WP Cerber', 'wp-cerber' ) . '</a>';
			$sections['smtp']['section_desc'] .= ' [ <a href="https://wpcerber.com/pro/" target="_blank">' . __( 'Available in the professional version of WP Cerber', 'wp-cerber' ) . '</a> ]';
		}

		// Single screen configuration

		if ( $settings_screen_id = crb_array_get( $args, 'settings_screen_id' ) ) {
			if ( empty( $screens[ $settings_screen_id ] ) ) {
				return false;
			}

			return array_intersect_key( $sections, array_flip( $screens[ $settings_screen_id ] ) );
		}

		// Single setting configuration

		if ( $setting = crb_array_get( $args, 'setting' ) ) {

			// Prepare mapping (linking) sections to screens

			if ( ! $section_to_screen ) {
				$section_to_screen = array();
				foreach ( $screens as $settings_screen_id => $section_list ) {
					$section_to_screen = array_merge( $section_to_screen, array_fill_keys( $section_list, $settings_screen_id ) );
				}
			}

			// Lookup setting definition

			foreach ( $sections as $section_id => $sect ) {
				if ( isset( $sect['fields'][ $setting ] ) ) {

					$field = $sect['fields'][ $setting ];

					// Populate additional details

					$field['section_name'] = $sect['name'] ?? '';
					$field['section_id'] = $section_id;
					$field['settings_screen_id'] = $section_to_screen[ $section_id ];
					$field['page_tab_id'] = self::screen_to_tab( $section_to_screen[ $section_id ] );

					return $field;
				}
			}

			return false;

		}

		// All sections, screens, fields.

		return $sections;
	}

	/**
	 * Returns the built-in settings screens and their ordered section IDs.
	 *
	 * Screen IDs are internal registry identifiers used by settings forms, renderers,
	 * and validation logic. The returned map does not include add-on screens; callers
	 * that need the effective registry should use get_config().
	 *
	 * @return array<string,array<string>> Screen ID to ordered section ID list.
	 */
	private static function get_screens(): array {

		// Screen ID => Set of sections

		return array(
			'main'          => array( 'boot', 'liloa', 'stspec', 'proactive', 'custom', 'citadel', 'activity', 'prefs' ),
			'users'         => array( 'us', 'us_reg', 'us_auth', 'us_misc', 'pdata' ),
			'hardening'     => array( 'hwp', 'rapi' ),
			'notifications' => array( 'notify', 'smtp', 'pushit', 'reports' ),
			'traffic'       => array( 'tmain', 'tierrs', 'tlog' ),
			'scanner'       => array( 'smain', 'smisc' ),
			'schedule'      => array( 's1', 's2' ),
			//'policies'      => array( 'scanpls', 'suploads', 'scanrecover', 'scanexcl' ),
			'policies'      => array( 'scanpls', 'scanrecover', 'scanexcl' ),
			'antispam'      => array( 'antibot', 'antibot_more', 'commproc' ),
			'recaptcha'     => array( 'recap' ),
			'user_shield'   => array( 'acc_protect', 'role_protect' ),
			'opt_shield'    => array( 'opt_protect' ),
			'nexus-slave'   => array( 'slave_settings' ),
			'nexus_master'  => array( 'master_settings' ),
		);
	}

	/**
	 * Returns built-in settings section definitions and their field definitions.
	 *
	 * Each section may define display metadata and a fields map keyed by setting ID.
	 * Field definitions describe labels, input types, allowed sets, render callbacks,
	 * validation constraints, visibility constraints, and related UI metadata consumed
	 * by the settings renderer and settings update pipeline. The result does not include
	 * add-on sections. Values can depend on the current admin page context, such as
	 * Pushbullet device choices shown on the notifications screen.
	 *
	 * @return array<string,array<string,mixed>> Section definitions keyed by section ID.
	 */
	private static function get_sections(): array {

		// Pushbullet devices
		$pb_set = array();
		if ( cerber_is_admin_page( array( 'tab' => 'notifications' ) ) ) {
			$pb_set = cerber_pb_get_devices();
			if ( is_array( $pb_set ) ) {
				if ( ! empty( $pb_set ) ) {
					$pb_set = array( 'all' => __( 'All connected devices', 'wp-cerber' ) ) + $pb_set;
				}
				else {
					$pb_set = array( 'N' => __( 'No devices found', 'wp-cerber' ) );
				}
			}
			else {
				$pb_set = array( 'N' => __( 'Not available', 'wp-cerber' ) );
			}
		}

		return array(
			'boot'      => array(
				'name'   => __( 'Initialization Mode', 'wp-cerber' ),
				'section_desc'   => __( 'How WP Cerber loads its core and security mechanisms', 'wp-cerber' ),
				'fields' => array(
					'boot-mode' => array(
						'title' => __( 'Load security engine', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							__( 'Legacy mode', 'wp-cerber' ),
							__( 'Standard mode', 'wp-cerber' )
						)
					),
				),
			),
			'liloa'     => array(
				'name'    => __( 'Login Security', 'wp-cerber' ),
				'section_desc'    => __( 'Brute-force attack mitigation and user authentication settings', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/wordpress-login-security/',
				'fields'  => array(
					'limit_attempts'  => array(
						'title'          => __( 'Limit login attempts', 'wp-cerber' ),
						/* translators: %1$s and %2$s are input fields for number of retries and time period. */
						'label'          => __( '%1$s retries are allowed within %2$s minutes', 'wp-cerber' ),
						'setting_ids'    => array( 'attempts', 'period' ), // if defined, will be passed to input_renderer
						'input_renderer' => function ( $label, $setting_ids, $value, $settings, $attrs, $name_prefix ) {
							$s1 = $setting_ids[0];
							$s2 = $setting_ids[1];

							return sprintf( $label,
								cerber_digi_field( $name_prefix . '[' . $s1 . ']', $settings[ $s1 ], 'crb-first-field' ),
								cerber_digi_field( $name_prefix . '[' . $s2 . ']', $settings[ $s2 ] ) );
						},
					),
					'lockout'         => array(
						'type'    => 'digits',
						'min_val' => 1,
						'title'   => __( 'Block IP address for', 'wp-cerber' ),
						'label'   => __( 'minutes', 'wp-cerber' ),
					),
					'aggressive'      => array(
						'title'          => __( 'Mitigate aggressive attempts', 'wp-cerber' ),
						/* translators: %1$s, %2$s, and %3$s are input fields for hours, number of lockouts, and hours. */
						'label'          => __( 'Increase lockout duration to %1$s hours after %2$s lockouts in the last %3$s hours', 'wp-cerber' ),
						'setting_ids'    => array( 'agperiod', 'aglocks', 'aglast' ), // if defined, will be passed to input_renderer
						'input_renderer' => function ( $label, $setting_ids, $value, $settings, $attrs, $name_prefix ) {
							$s1 = $setting_ids[0];
							$s2 = $setting_ids[1];
							$s3 = $setting_ids[2];

							return sprintf( $label,
								cerber_digi_field( $name_prefix . '[' . $s1 . ']', $settings[ $s1 ] ),
								cerber_digi_field( $name_prefix . '[' . $s2 . ']', $settings[ $s2 ] ),
								cerber_digi_field( $name_prefix . '[' . $s3 . ']', $settings[ $s3 ] ) );
						},
					),
					'limitwhite'      => array(
						'title' => __( 'Ignore the Allowed IP Access List', 'wp-cerber' ),
						'label' => __( 'Apply limit login rules to IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'loginnowp'       => array(
						'title'        => __( 'Processing wp-login.php authentication requests', 'wp-cerber' ),
						'type'         => 'select',
						'set'          => array(
							__( 'Default processing', 'wp-cerber' ),
							__( 'Block access to wp-login.php', 'wp-cerber' ),
							__( 'Deny authentication through wp-login.php', 'wp-cerber' )
						),
						'act_relation' => array(
							array( array( 2 ), array( 'filter_activity' => CRB_EV_LDN, 'filter_status' => 50 ), __( 'View violations in the log', 'wp-cerber' ) ),
							array( array( 1 ), array( 'filter_activity' => CRB_EV_PUR, 'filter_status' => array( 0, 10 ), 'search_url' => '/wp-login.php' ), __( 'View violations in the log', 'wp-cerber' ) )
						),
					),
					'nologinhint'     => array(
						'title' => __( 'Disable the default login error message', 'wp-cerber' ),
						'label' => __( 'Do not reveal non-existing usernames and emails in the failed login attempt message', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'nologinhint_msg' => array(
						'title'   => __( 'Custom login error message', 'wp-cerber' ),
						'label'   => __( 'An optional error message to be displayed when attempting to log in with a non-existing username or a non-existing email', 'wp-cerber' ),
						'type'    => 'textarea',
						'enabler' => array( 'nologinhint' ),
					),
					'nopasshint'      => array(
						'title'       => __( 'Disable the default password reset error message', 'wp-cerber' ),
						'label'       => __( 'Do not reveal non-existing usernames and emails in the password reset error message', 'wp-cerber' ),
						'type'        => 'checkbox',
						'requires_wp' => '5.5'
					),
					'nopasshint_msg'  => array(
						'title'       => __( 'Custom password reset confirmation message', 'wp-cerber' ),
						'label'       => __( 'An optional message displayed when attempting to reset the password for both existing and non-existing usernames or email addresses.', 'wp-cerber' ),
						'type'        => 'textarea',
						'enabler'     => array( 'nopasshint' ),
						'requires_wp' => '5.5'
					),
				),
			),
			'custom' => array(
				'name'             => __( 'Custom login page', 'wp-cerber' ),
				'section_advisory' => ( ! cerber_is_permalink_enabled() ) ? __( 'This feature requires WordPress permalinks to be enabled. Set the Permalink structure to any option other than Plain in the WordPress settings.', 'wp-cerber' ) : '',
				'section_desc'     => ( cerber_is_permalink_enabled() ) ? __( 'Be careful about enabling these options. If you forget your Custom login URL, you will be unable to log in.', 'wp-cerber' ) : '',
				'doclink'          => 'https://wpcerber.com/how-to-rename-wp-login-php/',
				'fields'           => array(
					'loginpath'     => array(
						'type'      => 'prefixed',
						'prefix'    => cerber_get_site_url() . '/',
						'title'     => __( 'Custom login URL', 'wp-cerber' ),
						'label'     => __( 'A unique string that does not overlap with slugs of the existing pages or posts', 'wp-cerber' ),
						'label_pos' => 'below',
						'attr'      => array( 'title' => __( 'Custom login URL may contain Latin alphanumeric characters, dashes and underscores only', 'wp-cerber' ) ),
						'size'      => 30,
						'pattern'   => '[a-zA-Z0-9\-_]{1,100}',
					),
					'logindeferred' => array(
						'title' => __( 'Deferred rendering', 'wp-cerber' ),
						'label' => __( 'Defer rendering the custom login page', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				),
			),
			'proactive' => array(
				'name'   => __( 'Proactive security rules', 'wp-cerber' ),
				'section_desc'   => __( 'Make your protection smarter!', 'wp-cerber' ),
				'fields' => array(
					'noredirect' => array(
						'title' => __( 'Disable dashboard redirection', 'wp-cerber' ),
						'label' => __( 'Disable automatic redirection to the login page when /wp-admin/ is requested by an unauthorized request', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'wplogin'    => array(
						'title' => __( 'Requests to wp-login.php are strictly prohibited', 'wp-cerber' ),
						'label' => __( 'Immediately block IP address after any request to wp-login.php', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'subnet'     => array(
						'title' => __( 'Block the attacker\'s subnet', 'wp-cerber' ),
						'label' => __( 'Block all IP addresses on the same Class C network as the detected attacker\'s IP address', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				),
			),
			'stspec' => array(
				'name'   => __( 'Site-specific settings', 'wp-cerber' ),
				'fields' => array(
					'proxy'            => array(
						'title'      => __( 'Site connection', 'wp-cerber' ),
						'label'      => __( 'My site is behind a reverse proxy', 'wp-cerber' ),
						'type'       => 'checkbox',
						'doclink'    => 'https://wpcerber.com/wordpress-ip-address-detection/',
						'pre_update' => function ( $val, $old_val ) {
							if ( $val ) {
								if ( ! $ip = crb_extract_ip_from_headers() ) {
									cerber_admin_notice( array(
										__( 'The reverse proxy mode has not been enabled. No valid proxy headers found.', 'wp-cerber' ),
										'<a href="https://wpcerber.com/wordpress-ip-address-detection/" target="_blank">' . __( 'Documentation', 'wp-cerber' ) . '</a>',
									) );

									$val = '';
								}
								elseif ( ! $old_val ) {
									/* translators: %1$s is the detected IP address, %2$s is a link to verify the IP address. */
									cerber_admin_message( sprintf( __( 'Your IP address is detected as %1$s. Make sure it is equal to your IP address on this page: %2$s.', 'wp-cerber' ), $ip, '<a href="https://wpcerber.com/what-is-my-ip/" target="_blank">What Is My IP Address</a>' ) );
								}
							}

							return $val;
						},
					),
					'cookiepref'       => array(
						'title'       => __( 'Prefix for plugin cookies', 'wp-cerber' ),
						'attr'        => array( 'title' => __( 'Prefix may contain only Latin alphanumeric characters and underscores', 'wp-cerber' ) ),
						'placeholder' => 'Latin alphanumeric characters or underscores',
						'size'        => 24,
						'pattern'     => '[a-zA-Z0-9_]{1,24}',
						'on_change'   => function () {
							if ( crb_get_settings( 'adminphp' ) ) {
								crb_htaccess_admin( 'main' );
							}
						},
						'rollback'    => function () {
							return ! empty( CRB_Globals::$htaccess_failure['main'] );
						},
					),
					'page404'          => array(
						'title' => __( 'When attempting to access prohibited URLs', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							__( 'Use 404 template from the active theme', 'wp-cerber' ),
							__( 'Display simple 404 page', 'wp-cerber' ),
							__( 'Redirect to the specified URL', 'wp-cerber' ),
						)
					),
					'page404_redirect' => array(
						'title'       => __( 'Redirection URL', 'wp-cerber' ),
						'type'        => 'url',
						'placeholder' => __( 'Full or relative URL to redirect request to', 'wp-cerber' ),
						'maxlength'   => 1000,
						'enabler'     => array( 'page404', 2 ),
					),
					'main_use_proxy'   => array(
						'title'         => __( 'Use WordPress proxy settings', 'wp-cerber' ),
						'label'         => __( 'Use proxy server for outgoing network connections', 'wp-cerber' ),
						'type'          => 'checkbox',
						'requires_true' => function () {
							return ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) );
						},
					),
					'cerber_sw_repo'   => array(
						'title'     => __( "Use WP Cerber's plugin repository", 'wp-cerber' ),
						'label'     => __( 'Allow updating WP Cerber and its translations from the official WP Cerber website', 'wp-cerber' ),
						'type'      => 'checkbox',
						'doclink'   => 'https://wpcerber.com/cerber-sw-repository/',
						'on_change' => function () {
							delete_site_transient( 'update_plugins' );
						},
					),
					'cerber_sw_auto'   => array(
						'title'   => __( 'Automatically update WP Cerber', 'wp-cerber' ),
						'label'   => __( 'Automatically install new versions of WP Cerber when they are available', 'wp-cerber' ),
						'type'    => 'checkbox',
						'doclink' => 'https://wpcerber.com/cerber-sw-repository/',
					),
					'log_crb_errors'   => array(
						'title'     => __( 'Collect WP Cerber software errors', 'wp-cerber' ),
						'label'     => __( 'Collect and save WP Cerber software errors to a diagnostic file', 'wp-cerber' ),
						'type'      => 'checkbox',
						'on_change' => function ( $new ) {
							if ( ! $new ) {
								CRB_Bug_Hunter::truncate_log_file( 0 );
							}
						},
					),
				),
			),
			'citadel'   => array(
				'name'   => __( 'Citadel mode', 'wp-cerber' ),
				'section_desc'   => __( 'In Citadel Mode, no one can log into the website except IP addresses from the Allowed IP Access List. Active user sessions will not be affected.', 'wp-cerber' ),
				'fields' => array(
					'citadel_on'  => array(
						'title'   => __( 'Enable authentication log monitoring', 'wp-cerber' ),
						//'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
						'type'    => 'checkbox',
					),
					'cithreshold' => array(
						'title'          => __( 'Citadel mode threshold', 'wp-cerber' ),
						'enabler'        => array( 'citadel_on' ),
						/* translators: %1$s and %2$s are input fields for failed attempts and time period. */
						'label'          => __( 'Enable after %1$s failed login attempts in the last %2$s minutes', 'wp-cerber' ),
						'setting_ids'    => array( 'cilimit', 'ciperiod' ), // if defined, will be passed to input_renderer
						'input_renderer' => function ( $label, $setting_ids, $value, $settings, $attrs, $name_prefix, $data_atts ) {
							$s1 = $setting_ids[0];
							$s2 = $setting_ids[1];

							return sprintf( $label,
								cerber_digi_field( $name_prefix . '[' . $s1 . ']', $settings[ $s1 ] ),
								cerber_digi_field( $name_prefix . '[' . $s2 . ']', $settings[ $s2 ] )
								. '<i ' . $data_atts . '></i>' );
						},
					),
					'ciduration'  => array(
						'title'   => __( 'Citadel mode duration', 'wp-cerber' ),
						'label'   => __( 'minutes', 'wp-cerber' ),
						'type'    => 'digits',
						'min_val' => 1,
						'enabler' => array( 'citadel_on' ),
					),
					'cinotify'    => array(
						'title'   => __( 'Notify admin', 'wp-cerber' ),
						'type'    => 'checkbox',
						'label'   => __( 'Send email notification to the admin', 'wp-cerber' ) . crb_test_notify_link( array( 'type' => 'citadel_mode' ) ),
						'enabler' => array( 'citadel_on' ),
					),
				),
			),
			'activity'  => array(
				'name'   => __( 'Activity', 'wp-cerber' ),
				'fields' => array(
					'keeplog'      => array(
						'title'   => __( 'Keep log records of not logged in visitors for', 'wp-cerber' ),
						'label'   => __( 'days', 'wp-cerber' ),
						//'label'  => __( 'days, not logged in visitors', 'wp-cerber' ),
						'type'    => 'digits',
						'min_val' => 1
					),
					'keeplog_auth' => array(
						'title'   => __( 'Keep log records of logged in users for', 'wp-cerber' ),
						'label'   => __( 'days', 'wp-cerber' ),
						//'label'  => __( 'days, logged in users', 'wp-cerber' ),
						'type'    => 'digits',
						'min_val' => 1
					),
					'cerberlab'    => array(
						'title'   => __( 'Cerber Security Cloud connection', 'wp-cerber' ),
						'label'   => __( 'Send malicious IP addresses to the Cerber Security Cloud', 'wp-cerber' ),
						'type'    => 'checkbox',
						'doclink' => 'https://wpcerber.com/cerber-laboratory/'
					),
					'usefile'      => array(
						'title'      => __( 'Write failed logins to a log file', 'wp-cerber' ),
						'label'      => __( 'Log failed logins in a syslog-style format for automated IP banning tools', 'wp-cerber' ),
						'type'       => 'checkbox',
						'row_attr'   => function ( &$att ) {
							$att['classes'][] = ( defined( 'CERBER_FAIL_LOG' ) || function_exists( 'syslog' ) ) ? '' : 'crb-disabled-colors';
						},
						'pre_render' => function ( &$val, &$att ) {
							$att['disabled'] = ( defined( 'CERBER_FAIL_LOG' ) || function_exists( 'syslog' ) ) ? 0 : 1;
						},
						'doclink'    => 'https://wpcerber.com/how-to-protect-wordpress-with-fail2ban/'
					),
				),
			),
			'prefs' => array(
				'name'   => __( 'Personal Preferences', 'wp-cerber' ),
				'fields' => array(
					'ip_extra'       => array(
						'title' => __( 'Show IP WHOIS data', 'wp-cerber' ),
						'label' => __( 'Retrieve IP address WHOIS information when viewing the logs', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'dateformat'     => array(
						'title'     => __( 'Date format', 'wp-cerber' ),
						/* translators: %s is the default date format example. */
						'label'     => sprintf( __( 'if empty, the default format %s will be used', 'wp-cerber' ), '<b>' . date( crb_get_default_dt_format(), time() ) . '</b>' ),
						'doclink'   => 'https://wpcerber.com/date-format-setting/',
						'label_pos' => 'below',
						'size'      => 16,
					),
					'plain_date'     => array(
						'title' => __( 'Date format for CSV export', 'wp-cerber' ),
						'label' => __( 'Use ISO 8601 date format for CSV export files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'admin_lang'     => array(
						'title' => 'Use English',
						'label' => 'Use English for the plugin admin pages',
						'type'  => 'checkbox',
						'enabler'    => array( 'cerber_sw_repo', '' ),
					),
					'admin_locale' => array(
						'title'      => __( 'Language for the plugin admin pages', 'wp-cerber' ),
						'type'       => 'select',
						'pre_render' => function ( &$val, &$att, &$config ) {

							$result = crb_get_admin_languages( $set );

							$set = array_merge( array( 0 => __( 'Default language', 'wp-cerber' ) ), $set );

							$config['set'] = $set;

							if ( crb_is_wp_error( $result ) ) {
								crb_admin_error_notice( $result );
							}
						},
						'on_change' => function ( $new ) {
							if ( $new
							     && $new != 'en_US'
							     && ! crb_is_translation_exists( $new ) ) {

								cerber_admin_message( __( 'Translations for the selected language will be installed shortly.', 'wp-cerber' ) );
								CRB_Deferred_Tasks::add( 'crb_download_translations', array( 'args' => array( $new ), 'load_admin' => 1 ) );
							}
						},
						'enabler'    => array( 'cerber_sw_repo' ),
					),
					'top_admin_menu' => array(
						'title' => __( 'Shift admin menu', 'wp-cerber' ),
						'label' => __( 'Shift the WP Cerber admin menu to the top when navigating through WP Cerber admin pages', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'no_white_my_ip' => array(
						'title' => __( 'My IP address', 'wp-cerber' ),
						'label' => __( 'Do not add my IP address to the Allowed IP Access List upon plugin activation', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					/*'log_errors' => array(
						'title' => __( 'Log critical errors', 'wp-cerber' ),
						'type'  => 'checkbox',
					),*/
				),
			),

			'hwp'  => array(
				'name'   => __( 'Hardening WordPress', 'wp-cerber' ),
				'section_desc'   => __( 'These restrictions do not apply to IP addresses in the Allowed IP Access List', 'wp-cerber' ),
				'fields' => array(
					'stopenum'            => array(
						'title' => __( 'Stop user enumeration', 'wp-cerber' ),
						'label' => __( 'Block access to user pages like /?author=n', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'stopenum_oembed'     => array(
						'title' => __( 'Prevent username discovery', 'wp-cerber' ),
						'label' => __( 'Prevent username discovery via oEmbed', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'stopenum_sitemap'    => array(
						'title' => __( 'Prevent username discovery', 'wp-cerber' ),
						'label' => __( 'Prevent username discovery via user XML sitemaps', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'nouserpages_bylogin' => array(
						'title' => __( 'Stop exposing user details', 'wp-cerber' ),
						'label' => __( 'Block access to user pages via their usernames', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'adminphp'            => array(
						'title'      => __( 'Protect admin scripts', 'wp-cerber' ),
						'label'      => __( 'Block unauthorized access to load-scripts.php and load-styles.php', 'wp-cerber' ),
						'type'       => 'checkbox',
						'on_change'  => function () {
							crb_htaccess_admin( 'main' );
						},
						'rollback'   => function () {
							return ! empty( CRB_Globals::$htaccess_failure['main'] );
						},
						'row_attr'   => function ( &$att ) {
							$att['classes'][] = crb_is_apache_mod_loaded( 'mod_rewrite' ) ? '' : 'crb-disabled-colors';
						},
						'pre_render' => function ( &$val, &$att ) {
							$att['disabled'] = crb_is_apache_mod_loaded( 'mod_rewrite' ) ? 0 : 1;
						},
					),
					'phpnoupl'            => array(
						'title'     => __( 'Disable PHP in uploads', 'wp-cerber' ),
						'label'     => __( 'Block execution of PHP scripts in the WordPress media folder', 'wp-cerber' ),
						'type'      => 'checkbox',
						'on_change' => function () {
							crb_htaccess_admin( 'media' );
						},
						'rollback'  => function () {
							return ! empty( CRB_Globals::$htaccess_failure['media'] );
						},
					),
					'nophperr'            => array(
						'title' => __( 'Disable PHP error displaying', 'wp-cerber' ),
						'label' => __( 'Do not show PHP errors on my website', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'xmlrpc'              => array(
						'title' => __( 'Disable XML-RPC', 'wp-cerber' ),
						'label' => __( 'Block access to the XML-RPC server (including Pingbacks and Trackbacks)', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'nofeeds'             => array(
						'title' => __( 'Disable feeds', 'wp-cerber' ),
						'label' => __( 'Block access to the RSS, Atom and RDF feeds', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				),
			),
			'rapi' => array(
				'name'    => __( 'Access to WordPress REST API', 'wp-cerber' ),
				'section_desc'    => __( 'Restrict or completely block access to the WordPress REST API according to your needs', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/restrict-access-to-wordpress-rest-api/',
				'fields'  => array(
					'norestuser'       => array(
						'title' => __( 'Stop user enumeration', 'wp-cerber' ),
						'label' => __( "Block access to users' data via REST API", 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'norestuser_roles' => array(
						'title'   => __( "Allow access to users' data via REST API for these roles", 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'norestuser' ),
					),
					'norest'           => array(
						'title' => __( 'Disable REST API', 'wp-cerber' ),
						'label' => __( 'Block access to WordPress REST API except any of the following', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'restauth'         => array(
						'title'   => __( 'Allow logged-in users', 'wp-cerber' ),
						'label'   => __( 'Allow access to REST API for logged-in users', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'norest' ),
					),
					'restroles'        => array(
						'title'   => __( 'Allow REST API for these roles', 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'norest' ),
					),
					'restwhite'        => array(
						'title'          => __( 'Allow these REST API namespaces', 'wp-cerber' ),
						'type'           => 'textarea',
						'delimiter'      => "\n",
						'list'           => true,
						'label'          => __( 'Specify REST API namespaces to permit when the REST API is disabled. One namespace per line.', 'wp-cerber' ),
						'doclink'        => 'https://wpcerber.com/restrict-access-to-wordpress-rest-api/',
						'enabler'        => array( 'norest' ),
						'callback_under' => function () {
							return '[ <a href="' . crb_admin_link_for_html( 'traffic', array( 'filter_wp_type' => 520 ) ) . '">' . __( 'View all REST API requests', 'wp-cerber' ) . '</a> ] [ <a href="' . cerber_admin_link( 'activity', array( 'filter_activity' => 70 ) ) . '">' . __( 'View denied REST API requests', 'wp-cerber' ) . '</a>]';
						},
						'pre_update'     => function ( $val ) {
							return cerber_text2array( $val, "\n", function ( $v ) {
								$v = preg_replace( '/[^a-z_\-\d\/]/i', '', $v );

								return trim( $v, '/' );
							} );
						},
					),
				),
			),

			'acc_protect'  => array(
				'name'   => __( 'User accounts protection', 'wp-cerber' ),
				//'section_desc'   => 'These policies prevent site takeover (admin dashboard hijacking) by creating accounts with administrator privileges',
				'section_desc'   => __( 'These security measures protect against site takeovers by preventing creation of unauthorized administrator accounts and escalation of user privileges.', 'wp-cerber' ),
				'fields' => array(
					'ds_4acc'       => array(
						'title'     => __( 'Protect user accounts', 'wp-cerber' ),
						'label'     => __( 'Restrict user account creation and management with the following policies', 'wp-cerber' ),
						//'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
						'type'      => 'checkbox',
						'on_change' => function ( $new ) {
							if ( ! empty( $new ) ) {
								CRB_DS::enable_shadowing( 1 );
							}
							else {
								CRB_DS::disable_shadowing( 1 );
							}
						},
					),
					'ds_regs_roles' => array(
						'title'   => __( 'User registrations are limited to these roles', 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'ds_4acc' ),
					),
					'ds_add_acc'    => array(
						'title'   => __( 'Users with these roles are permitted to create new accounts', 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'ds_4acc' ),
					),
					'ds_edit_acc'   => array(
						'title'   => __( 'Users with these roles are permitted to change sensitive user data', 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'ds_4acc' ),
					),
					'ds_4acc_acl'   => array(
						'title'   => __( 'Use the Allowed IP Access List', 'wp-cerber' ),
						'label'   => __( 'Do not apply these policies to the IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'ds_4acc' ),
					),
				),
			),
			'role_protect' => array(
				'name'   => __( 'User roles protection', 'wp-cerber' ),
				'section_desc'   => __( 'These security measures prevent site takeovers by blocking unauthorized creation of new user roles and escalation of role capabilities.', 'wp-cerber' ),
				'fields' => array(
					'ds_4roles'     => array(
						'title'     => __( 'Protect user roles', 'wp-cerber' ),
						'label'     => __( "Restrict roles and capabilities management with the following policies", 'wp-cerber' ),
						//'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
						'type'      => 'checkbox',
						'on_change' => function ( $new ) {
							if ( ! empty( $new ) ) {
								CRB_DS::enable_shadowing( 2 );
							}
							else {
								CRB_DS::disable_shadowing( 2 );
							}
						},
					),
					'ds_add_role'   => array(
						'title'   => __( 'Users with these roles are permitted to add new roles', 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'ds_4roles' ),
					),
					'ds_edit_role'  => array(
						'title'   => __( "Users with these roles are permitted to change role capabilities", 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'ds_4roles' ),
					),
					'ds_4roles_acl' => array(
						'title'   => __( 'Use the Allowed IP Access List', 'wp-cerber' ),
						'label'   => __( 'Do not apply these policies to the IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'ds_4roles' ),
					),
				),
			),
			'opt_protect'  => array(
				'name'   => __( 'Site settings protection', 'wp-cerber' ),
				'section_desc'   => __( 'These security measures prevent malware injection by blocking unauthorized changes to vital WordPress settings.', 'wp-cerber' ),
				'fields' => array(
					'ds_4opts'       => array(
						'title'     => __( 'Protect site settings', 'wp-cerber' ),
						'label'     => __( 'Restrict updating site settings with the following policies', 'wp-cerber' ),
						//'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
						'type'      => 'checkbox',
						'on_change' => function ( $new ) {
							if ( ! empty( $new ) ) {
								CRB_DS::enable_shadowing( 3 );
							}
							else {
								CRB_DS::disable_shadowing( 3 );
							}
						},
					),
					'ds_4opts_roles' => array(
						'title'   => __( 'Users with these roles are permitted to change protected settings', 'wp-cerber' ),
						'type'    => 'role_select',
						'enabler' => array( 'ds_4opts' ),
					),
					'ds_4opts_list'  => array(
						'title'   => __( 'Protected WordPress settings', 'wp-cerber' ),
						'type'    => 'checkbox_set',
						'set'     => CRB_DS::get_settings_list(),
						'enabler' => array( 'ds_4opts' ),
					),
					'ds_4opts_acl'   => array(
						'title'   => __( 'Use the Allowed IP Access List', 'wp-cerber' ),
						'label'   => __( 'Do not apply these policies to the IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'ds_4opts' ),
					),
				),
			),

			'us_reg' => array(
				'name'   => __( 'User registration', 'wp-cerber' ),
				'section_desc'   => __( 'These rules control the user registration process. Use them to restrict who can register based on your security requirements and preferences.', 'wp-cerber' ),
				'fields' => array(
					'reglimit'     => array(
						'title'          => __( 'Registration limit', 'wp-cerber' ),
						/* translators: %1$s and %2$s are input fields for number of registrations and time period. */
						'label'          => __( '%1$s registrations are allowed from a single IP address within %2$s minutes', 'wp-cerber' ),
						'setting_ids'    => array( 'reglimit_num', 'reglimit_min' ), // if defined, will be passed to input_renderer
						'input_renderer' => function ( $label, $setting_ids, $value, $settings, $attrs, $name_prefix ) {
							$s1 = $setting_ids[0];
							$s2 = $setting_ids[1];

							return sprintf( $label,
								cerber_digi_field( $name_prefix . '[' . $s1 . ']', $settings[ $s1 ], 'crb-first-field' ),
								cerber_digi_field( $name_prefix . '[' . $s2 . ']', $settings[ $s2 ], '', array( 'size' => 4, 'maxln' => 4, ) ) );
						},
					),
					'emrule'       => array(
						'title' => __( 'Restrict email addresses', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							__( 'No restrictions', 'wp-cerber' ),
							__( 'Block registration for email addresses matching the following', 'wp-cerber' ),
							__( 'Allow registration only for email addresses matching the following', 'wp-cerber' ),
						)
					),
					'emlist'       => array(
						'title'          => '',
						'label'          => __( 'Specify email addresses, wildcards or REGEX patterns. Use comma to separate items. To specify a REGEX pattern wrap a pattern in two forward slashes.', 'wp-cerber' ),
						'type'           => 'textarea',
						'list'           => true,
						'delimiter'      => '/(?<!{\d),(?!\d*}.*?\/)/',
						'delimiter_show' => ',',
						'apply'          => 'strtolower',
						'enabler'        => array( 'emrule', '[1,2]' ),
					),
					'emlist_msg'   => array(
						'title'   => __( 'User message', 'wp-cerber' ),
						'label'   => __( "This optional message is shown if a user's email address is not allowed for registration", 'wp-cerber' ),
						'type'    => 'textarea',
						'enabler' => array( 'emrule', '[1,2]' ),
					),
					'regwhite'     => array(
						'title' => __( 'Limit registration to trusted IP addresses only', 'wp-cerber' ),
						'label' => __( 'Only users whose IP addresses are on the Allowed IP Access List can register on the website', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'regwhite_msg' => array(
						'title'   => __( 'User message', 'wp-cerber' ),
						'label'   => __( "This optional message is shown if a user's IP address is not on the Allowed IP Access List", 'wp-cerber' ),
						'type'    => 'textarea',
						'enabler' => array( 'regwhite' ),
					),
				)
			),

			'us_auth' => array(
				'name'   => __( 'User Authentication', 'wp-cerber' ),
				'section_desc'   => __( 'These rules apply to the user login process. Use them to control how users sign in and to protect your website from unauthorized access.', 'wp-cerber' ),
				'fields' => array(
					/*'nonexisting_local' => array(
						'title'        => __( 'When attempting to log in with non-existing username', 'wp-cerber' ),
						'type'         => 'select',
						'label'        => 'Take this action if the submitted username or email does not belong to any user',
						'set'          => array(
							0 => __( 'No specific action', 'wp-cerber' ),
							1 => __( 'Abort user authentication and block remote IP address', 'wp-cerber' ),
							2 => __( 'Abort user authentication', 'wp-cerber' ),
						),
						//'act_relation' => array(
						//	array( false, array( 'filter_activity' => 51 ), __( 'Search in the log', 'wp-cerber' ) ),
						//),
						'upgrade_once' => 'nonusers'
					),*/
					'nonusers'   => array(
						'title' => __( 'Non-existing users are strictly prohibited', 'wp-cerber' ),
						'label' => __( 'Immediately block IP address when attempting to log in with a non-existing username', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'prohibited'    => array(
						'title'          => __( 'Prohibited usernames', 'wp-cerber' ),
						//'label'          => __( 'Usernames from this list are not allowed to log in or register. Any IP address, have tried to use any of these usernames, will be immediately blocked. Use comma to separate logins. To specify a REGEX pattern wrap a pattern in two forward slashes.', 'wp-cerber' ),
						'label'          => __( 'Usernames from this list are not allowed to log in or register. Use comma to separate usernames. To specify a REGEX pattern wrap a pattern in two forward slashes.', 'wp-cerber' ),
						'type'           => 'textarea',
						'list'           => true,
						'delimiter'      => '/(?<!{\d),(?!\d*}.*?\/)/',
						'delimiter_show' => ',',
						'apply'          => 'strtolower',
					),
					'prohibited_rule' => array(
						'title' => __( 'When attempting to log in with prohibited username', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							0 => __( 'Abort user authentication and block remote IP address', 'wp-cerber' ),
							1 => __( 'Abort user authentication', 'wp-cerber' ),
						)
					),
					'auth_expire'   => array(
						'title'     => __( 'User session expiration time', 'wp-cerber' ),
						'label'     => __( 'minutes (leave empty to use the default WordPress value)', 'wp-cerber' ),
						'size'      => 6,
						'type'      => 'digits',
						'min_val'   => 1,
						'empty_val' => true, // Empty values allowed
					),
					'no_rememberme' => array(
						'title' => __( 'Disable "Remember Me" on the login form', 'wp-cerber' ),
						'label' => __( 'Disable the "Remember Me" checkbox on the WordPress login form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'app_pwd'       => array(
						'title' => __( 'Application Passwords', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							1 => __( 'Enabled, access to API using standard user passwords is allowed', 'wp-cerber' ),
							2 => __( 'Enabled, no access to API using standard user passwords', 'wp-cerber' ),
							3 => __( 'Disabled', 'wp-cerber' ),
						)
					),
				)
			),

			'us' => array(
				'name'    => __( 'Limit Website Access', 'wp-cerber' ),
				'section_desc'    => __( 'Hide your website from the public by allowing access only to logged-in users or trusted IP addresses. All other visitors will be redirected to the login page or a custom URL.', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
				'fields'  => array(
					'authonly'    => array(
						'title'   => __( 'Allow access for logged-in users', 'wp-cerber' ),
						'label'   => __( 'Only registered and logged-in users can access your website', 'wp-cerber' ),
						'type'    => 'checkbox',
					),
					'authonlyacl' => array(
						'title'   => __( 'Allow access from trusted IP addresses', 'wp-cerber' ),
						'label'   => __( 'Let visitors from IP addresses in the Allowed IP Access List access your website without logging in', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'authonly' ),
					),
					'authonlymsg' => array(
						'title'       => __( 'Login page message', 'wp-cerber' ),
						'placeholder' => __( 'An optional login form message', 'wp-cerber' ),
						'label'       => __( 'This message appears above the login form. Use it to explain why login is required or to give additional instructions to users.', 'wp-cerber' ),
						'type'        => 'textarea',
						'apply'       => 'strip_tags',
						'enabler'     => array( 'authonly' ),
					),
					'authonlyredir' => array(
						'title'       => __( 'Redirect unauthorized visitors to this URL', 'wp-cerber' ),
						'label'       => __( 'Optional. Leave blank to show the message above instead of redirecting.', 'wp-cerber' ),
						'placeholder' => 'https://',
						'type'        => 'url',
						'maxlength'   => 1000,
						'enabler'     => array( 'authonly' ),
					),
				)
			),

			'us_misc' => array(
				'name'   => __( 'Look and Layout', 'wp-cerber' ),
				'section_desc'   => __( 'Customize how things appear in the login page and admin pages.', 'wp-cerber' ),
				'fields' => array(
					'nologinlang'   => array(
						'title'         => __( 'Disable login language switcher', 'wp-cerber' ),
						'label'         => __( 'Disable the login language switcher on the WordPress login page', 'wp-cerber' ),
						'type'          => 'checkbox',
						'requires_wp'   => '5.9',
						'requires_true' => function () {
							return (bool) get_available_languages();
						},
					),
					'usersort'      => array(
						'title' => __( 'Sort users by registration date', 'wp-cerber' ),
						'label' => __( 'On the Users admin page, display the most recently registered users first', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				)
			),

			'pdata' => array(
				'name'    => __( 'Personal Data', 'wp-cerber' ),
				//'section_desc'   => __( 'These features help your organization to be in compliance with data privacy laws', 'wp-cerber' ),
				'section_desc'    => __( 'These features help your organization to be in compliance with personal data protection laws', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/wordpress/gdpr/',
				'fields'  => array(
					'pdata_erase'    => array(
						'title'   => __( 'Enable data erase', 'wp-cerber' ),
						//'label'   => __( 'Only registered and logged in website users have access to the website', 'wp-cerber' ),
						//'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
						'type'    => 'checkbox',
					),
					'pdata_sessions' => array(
						'title'   => __( 'Terminate user sessions', 'wp-cerber' ),
						'label'   => __( 'Delete user sessions data when user data is erased', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'pdata_erase' ),
					),
					'pdata_export'   => array(
						'title'   => __( 'Enable data export', 'wp-cerber' ),
						//'label'   => __( 'Only registered and logged in website users have access to the website', 'wp-cerber' ),
						//'doclink' => 'https://wpcerber.com/only-logged-in-wordpress-users/',
						'type'    => 'checkbox',
					),
					'pdata_act'      => array(
						'title'   => __( 'Include activity log events', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'pdata_export' ),
					),
					'pdata_trf'      => array(
						'title'   => __( 'Include traffic log entries', 'wp-cerber' ),
						'type'    => 'checkbox_set',
						'set'     => array(
							1 => __( 'Request URL', 'wp-cerber' ),
							2 => __( 'Form fields data', 'wp-cerber' ),
							3 => __( 'Cookies', 'wp-cerber' )
						),
						'enabler' => array( 'pdata_export' ),
					),
				),
			),

			'notify' => array(
				'name'    => __( 'Email notifications', 'wp-cerber' ),
				'section_desc'    => __( 'Configure email parameters for notifications, reports, and alerts', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/wordpress-notifications-made-easy/',
				'fields'  => array(
					'email'                     => array(
						'title'       => __( 'Where to send emails generated by WP Cerber', 'wp-cerber' ),
						'placeholder' => __( 'Use comma to specify multiple values', 'wp-cerber' ),
						'delimiter'   => ',',
						'list'        => true,
						'maxlength'   => 1000,
						/* translators: %s is the website administrator email address. */
						'label'       => sprintf( __( 'if empty, the website administrator email %s will be used', 'wp-cerber' ), '<b>' . get_site_option( 'admin_email' ) . '</b>' )
					),
					'emailrate'                 => array(
						'title' => __( 'Limit on the allowed number of alerts', 'wp-cerber' ),
						'label' => __( 'notifications are allowed per hour (0 means unlimited)', 'wp-cerber' ),
						'type'  => 'digits',
					),
					'email_mask'                => array(
						'title' => __( 'Mask sensitive data', 'wp-cerber' ),
						'label' => __( 'Mask usernames and IP addresses in notifications and alerts', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'email_format'              => array(
						'title' => __( 'Message format', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							2 => __( 'Plain', 'wp-cerber' ),
							1 => __( 'Brief', 'wp-cerber' ),
							0 => __( 'Verbose', 'wp-cerber' )
						),
					),
					'notify_above' => array(
						'title'          => __( 'Lockout notification', 'wp-cerber' ),
						'field_switcher' => __( 'Send notification if the number of active lockouts above', 'wp-cerber' ),
						'label'          => crb_test_notify_link( array( 'channel' => 'email' ) ),
						'type'           => 'digits',
						'upgrade_delete' => 'above',
					),
					'notify-new-ver'            => array(
						'title' => __( 'New version of WP Cerber is available', 'wp-cerber' ),
						'label' => __( 'Send notification when a new version of WP Cerber is available', 'wp-cerber' ),
						'type'  => 'checkbox'
					),
					'notify_plugin_update'      => array(
						'title' => __( 'Plugin update is available', 'wp-cerber' ),
						'label' => __( 'Send notification when a new version of a plugin is available', 'wp-cerber' ) . crb_test_notify_link( array(
								'type'  => 'plugin_updates',
								'title' => __( 'Click to send now', 'wp-cerber' )
							), 'notify_plugin_update' ),
						'type'  => 'checkbox'
					),
					'notify_plugin_update_freq' => array(
						'type'    => 'digits',
						'min_val' => 1,
						'title'   => __( 'Checking frequency', 'wp-cerber' ),
						'label'   => __( 'hours interval check', 'wp-cerber' ),
						'enabler' => array( 'notify_plugin_update' ),
					),
					'notify_plugin_update_brf'  => array(
						'title'   => __( 'Brief notification format', 'wp-cerber' ),
						'label'   => __( 'Hide software versions and website URLs in notifications', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'notify_plugin_update' ),
					),
					'notify_plugin_update_to'   => array(
						'title'       => __( 'Where to email notifications', 'wp-cerber' ),
						'label'       => __( 'if empty, the email addresses from the notification settings will be used', 'wp-cerber' ),
						'placeholder' => implode( ', ', crb_build_email_recipients() ),
						'delimiter'   => ',',
						'list'        => true,
						'maxlength'   => 1000,
						'enabler'     => array( 'notify_plugin_update' ),
					),
				),
			),

			'smtp' => array(
				'name'   => __( 'Mail Transport', 'wp-cerber' ),
				'section_desc'   => __( 'Email server for sending emails generated by WP Cerber', 'wp-cerber' ),
				//'doclink' => 'https://wpcerber.com/wordpress-notifications-made-easy/',
				'fields' => array(
					'use_smtp'       => array(
						'title' => __( 'Use SMTP', 'wp-cerber' ),
						'label' => __( 'Use SMTP server to send emails', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'smtp_host'      => array(
						'title'     => __( 'SMTP host', 'wp-cerber' ),
						'size'      => 32,
						'maxlength' => 64,
						'enabler'   => array( 'use_smtp' ),
						'pattern'   => '[.-_\d\w]+',
						'attr'      => array( 'title' => 'Valid hostname or IP address' ),
						'validate'  => array( 'required' => 1 )
					),
					'smtp_port'      => array(
						'title'    => __( 'SMTP port', 'wp-cerber' ),
						'size'     => 32,
						'enabler'  => array( 'use_smtp' ),
						'pattern'  => '\d{1,5}',
						'attr'     => array( 'title' => 'Number' ),
						'validate' => array( 'required' => 1 )
					),
					'smtp_encr'      => array(
						'title'   => __( 'SMTP encryption', 'wp-cerber' ),
						'type'    => 'select',
						'set'     => array(
							0     => __( 'None', 'wp-cerber' ),
							'tls' => 'TLS',
							'ssl' => 'SSL',
						),
						'enabler' => array( 'use_smtp' ),
					),
					'smtp_pwd'       => array(
						'title'     => __( 'SMTP password', 'wp-cerber' ),
						'size'      => 32,
						'maxlength' => 64,
						'enabler'   => array( 'use_smtp' ),
						'validate'  => array( 'required' => 1 )
					),
					'smtp_user'      => array(
						'title'     => __( 'SMTP username', 'wp-cerber' ),
						'size'      => 32,
						'maxlength' => 64,
						'enabler'   => array( 'use_smtp' ),
						'validate'  => array( 'required' => 1 )
					),
					'smtp_from'      => array(
						'title'       => __( 'SMTP From email', 'wp-cerber' ),
						'placeholder' => __( 'If empty, the SMTP username is used', 'wp-cerber' ),
						'size'        => 32,
						'maxlength'   => 64,
						'enabler'     => array( 'use_smtp' ),
						'validate'    => array( 'satisfy' => 'is_email' )
					),
					'smtp_from_name' => array(
						'title'     => __( 'SMTP From name', 'wp-cerber' ),
						'size'      => 32,
						'maxlength' => 64,
						'enabler'   => array( 'use_smtp' ),
					),
					/*'smtp_backup'    => array(
						'title'   => __( 'Backup transport', 'wp-cerber' ),
						'type'    => 'select',
						'set'     => array(
							__( 'Do not use', 'wp-cerber' ),
							__( 'Default WordPress mailer', 'wp-cerber' ),
							__( 'Mobile messaging', 'wp-cerber' ),
						),
						'enabler' => array( 'use_smtp' ),
					),*/
				),
			),

			'pushit'  => array(
				'name'    => __( 'Push notifications', 'wp-cerber' ),
				'section_desc'    => __( 'Get notified instantly with mobile and desktop notifications', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/wordpress-mobile-and-browser-notifications-pushbullet/',
				'fields'  => array(
					'pbtoken'   => array(
						'title' => __( 'Pushbullet access token', 'wp-cerber' ),
					),
					'pbdevice'  => array(
						'title'   => __( 'Pushbullet device', 'wp-cerber' ),
						'type'    => 'select',
						'set'     => $pb_set,
						'enabler' => array( 'pbtoken' ),
					),
					'pbrate'    => array(
						'title'   => __( 'Limit on the allowed number of alerts', 'wp-cerber' ),
						'label'   => __( 'notifications are allowed per hour (0 means unlimited)', 'wp-cerber' ),
						'type'    => 'digits',
						'enabler' => array( 'pbtoken' ),
					),
					'pb_mask'   => array(
						'title'   => __( 'Mask sensitive data', 'wp-cerber' ),
						'label'   => __( 'Mask usernames and IP addresses in notifications and alerts', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'pbtoken' ),
					),
					'pb_format' => array(
						'title'   => __( 'Message format', 'wp-cerber' ),
						'type'    => 'select',
						'enabler' => array( 'pbtoken' ),
						'set'     => array(
							2 => __( 'Plain', 'wp-cerber' ),
							1 => __( 'Brief', 'wp-cerber' ),
							0 => __( 'Verbose', 'wp-cerber' )
						),
					),
					'pbnotify'  => array(
						'title'          => __( 'Lockout notification', 'wp-cerber' ),
						'field_switcher' => __( 'Send notification if the number of active lockouts above', 'wp-cerber' ),
						'label'          => crb_test_notify_link( array( 'channel' => 'pushbullet' ) ),
						'enabler'        => array( 'pbtoken' ),
						'type'           => 'digits',
					),
				),
			),
			'reports' => array(
				'name'   => __( 'Activity Reports', 'wp-cerber' ),
				'section_desc'   => __( 'Activity report is a summary of all logged activities and suspicious events occurred during the selected period of time', 'wp-cerber' ),
				'fields' => array(
					'enable-report'          => array(
						'title' => __( 'Enable weekly reporting', 'wp-cerber' ),
						'type'  => 'checkbox'
					),
					'wreports'               => array(
						'title'   => __( 'Send weekly reports on', 'wp-cerber' ),
						'type'    => 'reptime',
						'enabler' => array( 'enable-report' ),
					),
					'wreports_7'             => array(
						'title'   => __( 'Generate 7-day reports', 'wp-cerber' ),
						'label'   => __( 'Generate report for the last 7 days instead of the previous calendar week', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'enable-report' ),
					),
					'email-report'           => array(
						'title'       => __( 'Where to email reports', 'wp-cerber' ),
						'label'       => __( 'if empty, the email addresses from the notification settings will be used', 'wp-cerber' ),
						//'placeholder' => __( 'Use comma to specify multiple values', 'wp-cerber' ),
						'placeholder' => implode( ', ', crb_build_email_recipients() ),
						'delimiter'   => ',',
						'list'        => true,
						'maxlength'   => 1000,
						'enabler'     => array( 'enable-report' ),
					),
					'monthly_report'         => array(
						'title'      => __( 'Enable monthly reporting', 'wp-cerber' ),
						'type'       => 'checkbox',
						'pre_update' => function ( $val, $old_val, &$settings ) {
							if ( $val ) {
								$chaged = false;

								if ( ! empty( $settings['monthly_30'] ) ) {
									$min = 31;
								}
								else {
									$min = 31 + absint( $settings['monthly_on']['day'] );
								}

								if ( $settings['keeplog'] < $min ) {
									$settings['keeplog'] = $min;
									$chaged = true;
								}
								if ( $settings['keeplog_auth'] < $min ) {
									$settings['keeplog_auth'] = $min;
									$chaged = true;
								}

								if ( $chaged ) {
									/* translators: %d is the number of days activity log entries will be kept. */
									cerber_admin_message( sprintf( __( 'To generate complete reports, activity log entries will be kept for %d days.', 'wp-cerber' ), $min ) );
								}
							}

							return $val;
						},
					),
					'monthly_on'             => array(
						'title'   => __( 'Send monthly reports on the nth day of month', 'wp-cerber' ),
						'type'    => 'day_time_picker',
						'period'  => 'one_month',
						'enabler' => array( 'monthly_report' ),
					),
					'monthly_30'             => array(
						'title'   => __( 'Generate 30-day reports', 'wp-cerber' ),
						'label'   => __( 'Generate report for the last 30 days instead of the previous calendar month', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'monthly_report' ),
					),
					'email_report_one_month' => array(
						'title'       => __( 'Where to email reports', 'wp-cerber' ),
						'label'       => __( 'if empty, the email addresses from the notification settings will be used', 'wp-cerber' ),
						//'placeholder' => __( 'Use comma to specify multiple values', 'wp-cerber' ),
						'placeholder' => implode( ', ', crb_build_email_recipients() ),
						'delimiter'   => ',',
						'list'        => true,
						'maxlength'   => 1000,
						'enabler'     => array( 'monthly_report' ),
					),
				),
			),

			'tmain'  => array(
				'name'    => __( 'Traffic Inspection', 'wp-cerber' ),
				'section_desc'    => __( 'Traffic Inspector is a context-aware web application firewall (WAF) that protects your website by recognizing and denying malicious HTTP requests', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/traffic-inspector-in-a-nutshell/',
				'fields'  => array(
					'tienabled'      => array(
						'title' => __( 'Firewall inspection mode', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							__( 'Disabled', 'wp-cerber' ),
							__( 'Maximum compatibility', 'wp-cerber' ),
							__( 'Maximum security', 'wp-cerber' )
						),
					),
					'tiipwhite'      => array(
						'title'   => __( 'Use the Allowed IP Access List', 'wp-cerber' ),
						'label'   => __( 'Use less restrictive security filters for IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'tienabled', '[1,2]' ),
					),
					'tiwhite'        => array(
						'title'      => __( 'Exclude these locations from inspection by the firewall', 'wp-cerber' ),
						'type'       => 'textarea',
						'delimiter'  => "\n",
						'list'       => true,
						'label'      => __( 'Specify a request path to exclude matching requests from firewall inspection. Omit your website domain name and all GET parameters. One exception per line. To specify a REGEX pattern, enclose the exception in two braces.', 'wp-cerber' ),
						'doclink'    => 'https://wpcerber.com/wordpress-probing-for-vulnerable-php-code/',
						'enabler'    => array( 'tienabled', '[1,2]' ),
						'validate_value' => function ( $val ) {
							$result = crb_validate_regex_field( $val );

							$errs = array();

							foreach ( $val as $item ) {

								if ( $item[0] === '{' && substr( $item, - 1 ) === '}' ) {
									// It's a regex pattern, skip further checks
									continue;
								}

								if ( strrpos( $item, '://' ) !== false ) {
									$errs[] = sprintf(
										__( 'Full URLs are not supported here: %s', 'wp-cerber' ),
										crb_escape_html( $item )
									);
								}
								elseif ( strrpos( $item, '?' ) !== false ) {
									$errs[] = sprintf(
										__( 'Query parameters are not supported here: %s', 'wp-cerber' ),
										crb_escape_html( $item )
									);
								}
							}

							if ( $errs ) {
								if ( ! is_wp_error( $result ) ) {
									$result = new WP_Error();
								}

								foreach ( $errs as $err ) {
									$result->add( 'prohibited_value', $err );
								}
							}

							return $result;
						},
					),
					'tiwhite_header' => array(
						'title'     => __( 'Exclude requests with these HTTP headers from inspection by the firewall', 'wp-cerber' ),
						'type'      => 'textarea',
						'delimiter' => "\n",
						'list'      => true,
						'label'     => __( 'Specify colon-separated name and value pairs to exclude matching requests from firewall inspection. One header per line.', 'wp-cerber' ),
						'enabler'   => array( 'tienabled', '[1,2]' ),
					),
				),
			),
			'tierrs' => array(
				'name'   => __( 'Erroneous Request Shielding', 'wp-cerber' ),
				//'section_desc'   => 'Block IP addresses that generate excessive HTTP 404 requests.',
				'section_desc'   => __( 'Block IP addresses that send excessive requests for non-existing pages or scan website for security breaches', 'wp-cerber' ),
				'fields' => array(
					'tierrmon'    => array(
						'title' => __( 'Error shielding mode', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							__( 'Disabled', 'wp-cerber' ),
							__( 'Maximum compatibility', 'wp-cerber' ),
							__( 'Maximum security', 'wp-cerber' )
						)
					),
					'tierrnoauth' => array(
						'title'   => __( 'Ignore logged-in users', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'tierrmon', '[1,2]' ),
					),
				),
			),
			'tlog'   => array(
				'name'    => __( 'Traffic Logging', 'wp-cerber' ),
				'section_desc'    => __( 'Enable optional traffic logging if you need to monitor suspicious and malicious activity or solve security issues', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/wordpress-traffic-logging/',
				'fields'  => array(
					'timode'         => array(
						'title' => __( 'Logging mode', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							0 => __( 'Logging disabled', 'wp-cerber' ),
							3 => __( 'Minimal', 'wp-cerber' ),
							1 => __( 'Smart', 'wp-cerber' ),
							2 => __( 'All traffic', 'wp-cerber' )
						),
					),
					'tilogrestapi'   => array(
						'title'   => __( 'Log all REST API requests', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', 3 ),
					),
					'tilogxmlrpc'    => array(
						'title'   => __( 'Log all XML-RPC requests', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', 3 ),
					),
					'tinocrabs'      => array(
						'title'   => __( 'Do not log known crawlers', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'tinolocs'       => array(
						'title'     => __( 'Do not log these locations', 'wp-cerber' ),
						'type'      => 'textarea',
						'list'      => true,
						'delimiter' => "\n",
						'label'     => __( 'Specify a request path to exclude matching requests from logging. Omit the website domain name. One exception per line. To specify a REGEX pattern, enclose the exception in two braces. Requests starting with an excluded path or matching a REGEX pattern are not logged.', 'wp-cerber' ),
						'enabler'   => array( 'timode', '[1,2,3]' ),
						'validate_value' => function ( $val ) {
							return crb_validate_regex_field( $val );
						}
					),
					'tinoua'         => array(
						'title'     => __( 'Do not log these User-Agents', 'wp-cerber' ),
						'type'      => 'textarea',
						'list'      => true,
						'delimiter' => "\n",
						'label'     => __( 'Specify User-Agents to exclude requests from logging. One item per line.', 'wp-cerber' ),
						'enabler'   => array( 'timode', '[1,2,3]' ),
					),
					'tifields'       => array(
						'title'   => __( 'Save request fields', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'timask'         => array(
						'title'       => __( 'Mask these form fields', 'wp-cerber' ),
						'maxlength'   => 1000,
						'placeholder' => __( 'Use comma to specify multiple values', 'wp-cerber' ),
						'list'        => true,
						'delimiter'   => ',',
						'enabler'     => array( 'timode', '[1,2,3]' ),
					),
					'tihdrs'         => array(
						'title'   => __( 'Save request headers', 'wp-cerber' ),
						'label'   => __( '', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'tihdrs_sent'    => array(
						'title'   => __( 'Save response headers', 'wp-cerber' ),
						'label'   => __( '', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'ticandy'        => array(
						'title'   => __( 'Save request cookies', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'ticandy_sent'   => array(
						'title'   => __( 'Save response cookies', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'tisenv'         => array(
						'title'   => __( 'Save $_SERVER', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'tiphperr'       => array(
						'title'   => __( 'Save software errors', 'wp-cerber' ),
						'type'    => 'checkbox',
						'enabler' => array( 'timode', '[1,2,3]' ),
					),
					'tithreshold'    => array(
						'title'      => __( 'Page generation time threshold', 'wp-cerber' ),
						'label'      => __( 'milliseconds', 'wp-cerber' ),
						'type'       => 'digits',
						'size'       => 4,
						'enabler'    => array( 'timode', '[1,2,3]' ),
						'pre_update' => function ( $val ) {
							if ( $val ) {
								$val = crb_absint( $val );
							}

							return $val;
						},
					),
					'tikeeprec'      => array(
						'title'   => __( 'Keep log records of not logged in visitors for', 'wp-cerber' ),
						'label'   => __( 'days', 'wp-cerber' ),
						'type'    => 'digits',
						'size'    => 4,
						'min_val' => 1,
						/*'pre_update' => function ( $val ) {
							$val = crb_absint( $val );
							if ( $val == 0 ) {
								$val = 1;
								cerber_admin_notice( 'You may not set <b>Keep records for</b> to 0 days. To completely disable logging, set <b>Logging mode</b> to Logging disabled.' );
							}

							return $val;
						},*/
					),
					'tikeeprec_auth' => array(
						'title'   => __( 'Keep log records of logged in users for', 'wp-cerber' ),
						'label'   => __( 'days', 'wp-cerber' ),
						'type'    => 'digits',
						'size'    => 4,
						'min_val' => 1,
					),
				),
			),

			'smain' => array(
				'name'    => __( 'Scanner settings', 'wp-cerber' ),
				'section_desc'    => __( 'The scanner monitors file changes, verifies the integrity of WordPress, plugins, and themes, and detects malware', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/wordpress-security-scanner/',
				'fields'  => array(
					'scan_inew'    => array(
						'title' => __( 'Scan for new files', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							0 => __( 'Disabled', 'wp-cerber' ),
							1 => __( 'Executable files', 'wp-cerber' ),
							2 => __( 'All files', 'wp-cerber' ),
						)
					),
					'scan_imod'    => array(
						'title' => __( 'Scan for file modifications', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							0 => __( 'Disabled', 'wp-cerber' ),
							1 => __( 'Executable files', 'wp-cerber' ),
							2 => __( 'All files', 'wp-cerber' ),
						)
					),
					'scan_abon_pl'    => array(
						'title' => __( 'Scan for abandoned plugins', 'wp-cerber' ),
						'label' => __( 'Detect and report plugins that received no updates for the specified period of time', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_abon_pl_period'    => array(
						'title'   => __( 'Months without updates to flag plugin as abandoned', 'wp-cerber' ),
						'type'    => 'digits',
						'min_val' => 1,
						'enabler' => array( 'scan_abon_pl' ),
					),
					'scan_owner_pl'    => array(
						'title' => __( 'Scan for plugin ownership changes', 'wp-cerber' ),
						'label' => __( 'Detect and report plugins with changes in ownership', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_tmp'     => array(
						'title' => __( "Scan temporary directories", 'wp-cerber' ),
						'label' => __( 'Scan web server temporary directories for malicious files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_sess'    => array(
						'title' => __( 'Scan sessions directory', 'wp-cerber' ),
						'label' => __( 'Scan the web server sessions directory for malicious files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_uext'    => array(
						'title'        => __( 'Unwanted file extensions', 'wp-cerber' ),
						'list'         => true,
						'delimiter'    => ',',
						'regex_filter' => '[".?*/\'\\\\]',
						'apply'        => 'strtolower',
						'deny_filter'  => array( 'php', 'js', 'css', 'txt', 'po', 'mo', 'pot' ),
						'label'        => __( 'Specify file extensions to search for. Full scan only. Use comma to separate items.', 'wp-cerber' )
					),
					'scan_cpt'     => array(
						'title'     => __( 'Custom signatures', 'wp-cerber' ),
						'type'      => 'textarea',
						'list'      => true,
						'delimiter' => "\n",
						'label'     => __( 'Specify custom PHP code signatures. One item per line. To specify a REGEX pattern, enclose a whole line in two braces.', 'wp-cerber' ) . ' <a target="_blank" href="https://wpcerber.com/malware-scanner-settings/">Read more</a>'
					),
					'scan_exclude' => array(
						'title'      => __( 'Directories to exclude', 'wp-cerber' ),
						'type'       => 'textarea',
						'delimiter'  => "\n",
						'list'       => true,
						'label'      => __( 'Specify directories to exclude from scanning. One directory per line.', 'wp-cerber' ),
						'pre_update' => function ( $val ) {
							return cerber_normal_dirs( $val );
						},
						'on_change'  => function () {
							cerber_admin_message( __( 'Please run a new scan to get consistent and accurate results.', 'wp-cerber' ) );
						},
					),
				),
			),
			'smisc' => array(
				'name'   => __( 'Miscellaneous Settings', 'wp-cerber' ),
				'fields' => array(
					'scan_chmod'    => array(
						'title' => __( 'Change filesystem permissions', 'wp-cerber' ),
						'label' => __( 'Change file and directory permissions if it is required to delete files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_debug'    => self::get_field_template( 'diagnostic_log', [ 'diag_log' => 'Logging of integrity scan operations' ] ),
					'scan_qcleanup' => array(
						'title'   => __( 'Delete quarantined files after', 'wp-cerber' ),
						'type'    => 'digits',
						'min_val' => 1,
						'label'   => __( 'days', 'wp-cerber' ),
					),
				),
			),

			's1' => array(
				'name'    => __( 'Automated recurring scan schedule', 'wp-cerber' ),
				'section_desc'    => __( 'The scanner automatically scans the website, removes malware and sends email reports with the results of a scan', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/automated-recurring-malware-scans/',
				'fields'  => array(
					'scan_aquick' => array(
						'title' => __( 'Launch Quick Scan', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => cerber_get_qs(),
					),
					'scan_afull'  => array(
						'title'          => __( 'Launch Full Scan', 'wp-cerber' ),
						'type'           => 'timepicker',
						'field_switcher' => __( 'once a day at', 'wp-cerber' ),
					),
				),
			),
			's2' => array(
				'name'    => __( 'Scan results reporting', 'wp-cerber' ),
				'section_desc'    => __( 'Configure what issues to include in the email report and the condition for sending reports', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/automated-recurring-malware-scans/',
				'fields'  => array(
					'scan_reinc'   => array(
						'title' => __( 'Report an issue if any of the following is true', 'wp-cerber' ),
						'type'  => 'checkbox_set',
						'set'   => array(
							           1 => __( 'Low severity', 'wp-cerber' ),
							           2 => __( 'Medium severity', 'wp-cerber' ),
							           3 => __( 'High severity', 'wp-cerber' )
						           ) + cerber_get_issue_title( array( CERBER_IMD, CERBER_UXT, 50, 51, CERBER_VULN, CERBER_ABP, CERBER_CHO ) ),
					),
					'scan_relimit' => array(
						'title' => __( 'Send email report', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array(
							1 => __( 'After every scan', 'wp-cerber' ),
							3 => __( 'If any changes in scan results occurred', 'wp-cerber' ),
							5 => __( 'If new issues found', 'wp-cerber' ),
						)
					),
					'scan_isize'   => array(
						'title' => __( 'Include file sizes', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_ierrors' => array(
						'title' => __( 'Include scan errors', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'email-scan'   => array(
						'title'       => __( 'Email Address', 'wp-cerber' ),
						'label'       => __( 'if empty, the email addresses from the notification settings will be used', 'wp-cerber' ),
						//'placeholder' => __( 'Use comma to specify multiple values', 'wp-cerber' ),
						'placeholder' => implode( ', ', crb_build_email_recipients() ),
						'delimiter'   => ',',
						'list'        => true,
						'maxlength'   => 1000,
					),
				),
			),

			'scanpls'     => array(
				'name'    => __( 'Automatic cleanup of malware and suspicious files', 'wp-cerber' ),
				'section_desc'    => __( 'These policies are automatically enforced at the end of every scan based on its results. All affected files are moved to the quarantine.', 'wp-cerber' ),
				'doclink' => 'https://wpcerber.com/automatic-malware-removal-wordpress/',
				'fields'  => array(
					'scan_delunatt'  => array(
						'title' => __( 'Delete unattended files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_delupl'    => array(
						'title' => __( 'Delete files in the WordPress uploads directory', 'wp-cerber' ),
						'type'  => 'checkbox_set',
						'set'   => array(
							1 => __( 'Low severity', 'wp-cerber' ),
							2 => __( 'Medium severity', 'wp-cerber' ),
							3 => __( 'High severity', 'wp-cerber' ),
						),
					),
					'scan_delunwant' => array(
						'title' => __( 'Delete files with unwanted extensions', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				),
			),
			'suploads'    => array(
				'name'   => __( 'WordPress uploads analysis', 'wp-cerber' ),
				'section_desc'   => __( 'Keep the WordPress uploads directory clean and secure. Detect injected files with public web access, report them, and remove malicious ones.', 'wp-cerber' ),
				//'doclink' => 'https://wpcerber.com/wordpress-security-scanner/',
				//'pro_section'    => 1,
				'fields' => array(
					'scan_media'      => array(
						'title' => __( 'Analyze the uploads directory', 'wp-cerber' ),
						'label' => __( 'Analyze the WordPress uploads directory to detect injected files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_skip_media' => array(
						'title'        => __( 'Skip files with these extensions', 'wp-cerber' ),
						//'label'        => __( 'List of file extensions to ignore', 'wp-cerber' ),
						'label'        => __( 'Ignore files with these extensions', 'wp-cerber' ),
						'placeholder'  => __( 'Use comma to separate multiple extensions', 'wp-cerber' ),
						'list'         => true,
						'delimiter'    => ',',
						'regex_filter' => '[".?*/\'\\\\]',
						'apply'        => 'strtolower',
						'maxlength'    => 1000,
						'enabler'      => array( 'scan_media' ),
					),
					'scan_del_media'  => array(
						'title'        => __( 'Prohibited extensions', 'wp-cerber' ),
						//'label'        => __( 'List of file extensions allowed to be deleted', 'wp-cerber' ),
						'label'        => __( 'Delete publicly accessible files with these extensions', 'wp-cerber' ),
						'placeholder'  => __( 'Use comma to separate multiple extensions', 'wp-cerber' ),
						'list'         => true,
						'delimiter'    => ',',
						'regex_filter' => '[".?*/\'\\\\]',
						'apply'        => 'strtolower',
						'maxlength'    => 1000,
						'enabler'      => array( 'scan_media' ),
					),
				),
			),
			'scanrecover' => array(
				'name'   => __( 'Automatic recovery of modified and infected files', 'wp-cerber' ),
				'fields' => array(
					'scan_recover_wp' => array(
						'title' => __( 'Recover WordPress files', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_recover_pl' => array(
						'title' => __( "Recover plugins' files", 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				),
			),
			'scanexcl'    => array(
				'name'   => __( 'Global Exclusions', 'wp-cerber' ),
				'section_desc'   => __( 'These files will never be deleted during automatic cleanup. Be careful about configuring these settings. Improper configuration may lead to failure to delete malicious files.', 'wp-cerber' ),
				'fields' => array(
					'scan_delexdir'  => array(
						'title'      => __( 'Files in these directories', 'wp-cerber' ),
						'type'       => 'textarea',
						'delimiter'  => "\n",
						'list'       => true,
						'label'      => __( 'Use absolute or relative to the home directory paths. One directory per line.', 'wp-cerber' ),
						'pre_update' => function ( $val ) {
							return cerber_normal_dirs( $val );
						},
					),
					'scan_delexext'  => array(
						'title'        => __( 'Files with these extensions', 'wp-cerber' ),
						'type'         => 'textarea',
						'list'         => true,
						'delimiter'    => ',',
						'regex_filter' => '[".?*/\'\\\\]',
						'apply'        => 'strtolower',
						'label'        => __( 'Use comma to separate items.', 'wp-cerber' )
					),
					'scan_nodeltemp' => array(
						'title' => __( 'Files in temporary directories', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'scan_nodelsess' => array(
						'title' => __( 'Files in the sessions directory', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
				),
			),


			'antibot'      => array(
				'name'     => __( 'Cerber anti-spam engine', 'wp-cerber' ),
				'section_desc'     => __( 'Spam protection for registration, comment, and other forms on the website', 'wp-cerber' ),
				'doclink'  => 'https://wpcerber.com/antispam-for-wordpress-contact-forms/',
				'seclinks' => array(
					array(
						__( 'View bot events', 'wp-cerber' ),
						crb_admin_link_for_html( 'activity', array( 'filter_status' => array( CRB_STS_11, 706 ) ) )
					)
				),
				'fields'   => array(
					'botsreg'    => array(
						'title' => __( 'Protect registration form', 'wp-cerber' ),
						'label' => __( 'Protect the standard WordPress registration form with bot detection engine', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'botscomm'   => array(
						'title' => __( 'Protect comment form', 'wp-cerber' ),
						'label' => __( 'Protect the standard WordPress comment form with bot detection engine', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'customcomm' => array(
						'title' => __( 'Custom comment URL', 'wp-cerber' ),
						'label' => __( 'Use custom URL for the WordPress comment form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'botsany'    => array(
						'title'          => __( 'Protect other forms', 'wp-cerber' ),
						'label'          => __( 'Protect all forms on the website with bot detection engine', 'wp-cerber' ),
						'type'           => 'checkbox',
						'callback_under' => function () {
							if ( ! defined( 'CERBER_DISABLE_SPAM_FILTER' ) ) {
								return '';
							}

							$list = explode( ',', (string) CERBER_DISABLE_SPAM_FILTER );
							$titles = array();
							$home = cerber_get_home_url();

							foreach ( $list as $pid ) {
								if ( $t = get_the_title( $pid ) ) {
									$titles [] = '<a href="' . $home . '/?p=' . (int) $pid . '" target="_blank">' . $t . '</a> (ID ' . $pid . ')';
								}
							}

							if ( $titles ) {
								$ret = '<p>Forms on the following pages are not analyzed: form submissions will be denied by the anti-spam engine.</p>';
								$ret .= '<ul style="margin-bottom: 0;"><li>' . implode( '</li><li>', $titles ) . '</li></ul>';
							}
							else {
								$ret = 'Note: you have specified the CERBER_DISABLE_SPAM_FILTER constant, but no pages with given IDs found.';
							}

							return $ret;
						}
					),
				)
			),
			'antibot_more' => array(
				'name'   => __( 'Adjust anti-spam engine', 'wp-cerber' ),
				'section_desc'   => __( 'These settings enable you to fine-tune the behavior of anti-spam algorithms and avoid false positives', 'wp-cerber' ),
				'fields' => array(
					'botssafe'         => array(
						'title' => __( 'Enable safe AJAX mode', 'wp-cerber' ),
						'label' => __( 'Use less strict spam filtering for AJAX form submissions. May increase spam risk slightly.', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'botsnoauth'       => array(
						'title' => __( 'Disable spam checks for logged-in users', 'wp-cerber' ),
						'label' => __( 'Disable spam checks and bot detection engine for logged-in users', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'botsipwhite'      => array(
						'title' => __( 'Use the Allowed IP Access List', 'wp-cerber' ),
						'label' => __( 'Disable bot detection engine for IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'botswhite'        => array(
						'title'     => __( 'Exclude these locations from scanning for spam', 'wp-cerber' ),
						'label'     => __( 'Specify a string to match anywhere in the request path or query string to exclude matching requests from anti-spam inspection. Omit the website domain name. One exception per line. To specify a REGEX pattern, enclose the exception in two braces.', 'wp-cerber' ),
						'type'      => 'textarea',
						'list'      => true,
						'delimiter' => "\n",
						'doclink'   => 'https://wpcerber.com/antispam-exception-for-specific-http-request/',
						'validate_value' => function ( $val ) {
							return crb_validate_regex_field( $val );
						},
					),
					'botswhite_header' => array(
						'title'     => __( 'Exclude requests with these HTTP headers from scanning for spam', 'wp-cerber' ),
						'label'     => __( 'Specify colon-separated name and value pairs to exclude matching requests from scanning for spam. One header per line.', 'wp-cerber' ),
						'type'      => 'textarea',
						'list'      => true,
						'delimiter' => "\n",
					),
				)
			),
			'commproc'     => array(
				'name'   => __( 'Comment processing', 'wp-cerber' ),
				'section_desc'   => __( 'How the plugin processes comments submitted through the standard comment form', 'wp-cerber' ),
				'fields' => array(
					'spamcomm'   => array(
						'title' => __( 'If a spam comment detected', 'wp-cerber' ),
						'type'  => 'select',
						'set'   => array( __( 'Deny it completely', 'wp-cerber' ), __( 'Mark it as spam', 'wp-cerber' ) )
					),
					'trashafter' => array(
						'title'          => __( 'Trash spam comments', 'wp-cerber' ),
						'type'           => 'digits',
						'field_switcher' => __( 'Move spam comments to trash after', 'wp-cerber' ),
						'label'          => __( 'days', 'wp-cerber' ),
					),
				)
			),

			'recap' => array(
				'name'     => __( 'reCAPTCHA settings', 'wp-cerber' ),
				'section_desc'     => __( 'Before you can start using reCAPTCHA, you have to obtain Site key and Secret key on the Google website', 'wp-cerber' ),
				'doclink'  => 'https://wpcerber.com/how-to-setup-recaptcha/',
				'seclinks' => array(
					array(
						__( 'View reCAPTCHA events', 'wp-cerber' ),
						crb_admin_link_for_html( 'activity', array( 'filter_status' => array( 531, CRB_STS_532, 533, 534, 706 ) ) )
					)
				),
				'fields'   => array(
					'sitekey'       => array(
						'title' => __( 'Site key', 'wp-cerber' ),
						'type'  => 'text',
					),
					'secretkey'     => array(
						'title' => __( 'Secret key', 'wp-cerber' ),
						'type'  => 'text',
					),
					'invirecap'     => array(
						'title' => __( 'Invisible reCAPTCHA', 'wp-cerber' ),
						'label' => __( 'Enable invisible reCAPTCHA (do not enable it unless you get and enter the Site and Secret keys for the invisible version)', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recapreg'      => array(
						'title' => __( 'Protect registration form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WordPress registration form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recapwooreg'   => array(
						'title' => __( 'Protect WooCommerce registration form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WooCommerce registration form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recaplost'     => array(
						'title' => __( 'Protect lost password form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WordPress lost password form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recapwoolost'  => array(
						'title' => __( 'Protect WooCommerce lost password form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WooCommerce lost password form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recaplogin'    => array(
						'title' => __( 'Protect login form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WordPress login form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recapwoologin' => array(
						'title' => __( 'Protect WooCommerce login form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WooCommerce login form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recapcom'      => array(
						'title' => __( 'Protect comment form', 'wp-cerber' ),
						'label' => __( 'Enable reCAPTCHA for WordPress comment form', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recapcomauth'  => array(
						'title'   => '',
						'label'   => __( 'Disable reCAPTCHA for logged-in users', 'wp-cerber' ),
						'enabler' => array( 'recapcom' ),
						'type'    => 'checkbox',
					),
					'recapipwhite'  => array(
						'title' => __( 'Use the Allowed IP Access List', 'wp-cerber' ),
						'label' => __( 'Disable reCAPTCHA for IP addresses in the Allowed IP Access List', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'recaplimit'    => array(
						'title'          => __( 'Limit attempts', 'wp-cerber' ),
						/* translators: %1$s, %2$s, and %3$s are input fields for lockout minutes, failed attempts, and time period. */
						'label'          => __( 'Lock out IP address for %1$s minutes after %2$s failed attempts within %3$s minutes', 'wp-cerber' ),
						'setting_ids'    => array( 'recaptcha-period', 'recaptcha-number', 'recaptcha-within' ), // if defined, will be passed to input_renderer
						'input_renderer' => function ( $label, $setting_ids, $value, $settings, $attrs, $name_prefix ) {
							$s1 = $setting_ids[0];
							$s2 = $setting_ids[1];
							$s3 = $setting_ids[2];

							return sprintf( $label,
								cerber_digi_field( $name_prefix . '[' . $s1 . ']', $settings[ $s1 ] ),
								cerber_digi_field( $name_prefix . '[' . $s2 . ']', $settings[ $s2 ] ),
								cerber_digi_field( $name_prefix . '[' . $s3 . ']', $settings[ $s3 ] ) );
						},
					),
				)
			),

			'master_settings' => array(
				'name'   => __( 'Main website settings', 'wp-cerber' ),
				'fields' => array(
					/*('master_cache'    => array(
						'title' => __( 'Cache Time', 'wp-cerber' ),
						'type'  => 'text',
					),*/
					'master_tolist'    => array(
						'title' => __( 'Return to the website list', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'master_swshow'    => array(
						'title' => __( 'Show "Switched to" notification', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'master_at_site'   => array(
						'title' => __( 'Add @ site to the page title', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'master_locale'    => array(
						'title' => __( 'Use my language', 'wp-cerber' ),
						'label' => __( 'Display admin pages of remote websites using my language', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'cerber_hub_proxy' => array(
						'title'         => __( 'Use WordPress proxy settings', 'wp-cerber' ),
						'label'         => __( 'Use proxy server to connect to managed websites', 'wp-cerber' ),
						'type'          => 'checkbox',
						'requires_true' => function () {
							return ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) );
						},
					),
					/*
					'master_dt'      => array(
						'title' => __( 'Use master datetime format', 'wp-cerber' ),
						'type'  => 'checkbox',
					),
					'master_tz'      => array(
						'title' => __( 'Use master timezone', 'wp-cerber' ),
						'type'  => 'checkbox',
					),*/
					'master_diag' => self::get_field_template( 'diagnostic_log', [ 'diag_log' => 'Logging of Cerber.Hub main website operations' ] ),
				)
			),
			'slave_settings' => array(
				'name'   => '',
				//'info'   => __( 'User related settings', 'wp-cerber' ),
				'fields' => array(
					'slave_ips'    => array(
						'title' => __( 'Limit access by IP address', 'wp-cerber' ),
						//'placeholder' => 'The IP address of the main website',
						'type'  => 'text',
					),
					'slave_access' => array(
						'title'     => __( 'Access to this website', 'wp-cerber' ),
						'type'      => 'select',
						'set'       => array(
							2 => __( 'Full access mode', 'wp-cerber' ),
							4 => __( 'Read-only mode', 'wp-cerber' ),
							8 => __( 'Disabled', 'wp-cerber' )
						),
						'label_pos' => 'below',
					),
					'slave_diag'   => self::get_field_template( 'diagnostic_log', [ 'diag_log' => 'Logging of operations initiated by the Cerber.Hub main website' ] ),
				)
			)
		);
	}

	/**
	 * Retrieves a flat list of all setting fields for a given screen, or all the setting fields from the registry.
	 *
	 * @param string $settings_screen_id An optional settings screen ID.
	 *
	 * @return array The list of all WP Cerber setting fields.
	 *
	 * @since 9.1.5
	 */
	public static function get_setting_fields( $settings_screen_id = '' ): array {
		static $all;

		if ( ! $settings_screen_id ) {
			if ( ! $all ) {
				$all = self::get_config();
			}
			$sections = $all;
		}
		else {
			$sections = self::get_config( array( 'settings_screen_id' => $settings_screen_id ) );
		}

		$ret = array();

		foreach ( $sections as $section_config ) {
			if ( $fields = crb_array_get( $section_config, 'fields' ) ) {
				$ret = array_merge( $ret, $fields );
			}
		}

		return $ret;
	}

	/**
	 * Resolves a settings screen ID to the admin page tab ID used for navigation.
	 *
	 * Built-in aliases are resolved through TAB_TO_SCREEN, add-on screens are resolved
	 * through the add-on registry, and unknown screen IDs are returned unchanged so
	 * callers can still build stable links for screens without an explicit alias.
	 *
	 * @param string $settings_screen_id Settings screen ID from this registry.
	 *
	 * @return string Admin page tab ID or the original screen ID when no mapping exists.
	 */
	private static function screen_to_tab( $settings_screen_id ) {
		static $map;

		if ( ! $map ) {
			$map = array_flip( self::TAB_TO_SCREEN );
		}

		if ( isset( $map[ $settings_screen_id ] ) ) {
			return $map[ $settings_screen_id ];
		}

		if ( $tab = CRB_Addons::get_addon_tab( $settings_screen_id ) ) {
			return $tab;
		}

		return $settings_screen_id;
	}

	/**
	 * Return a setting field template.
	 * Expected to be extended with other types of fields and templates in the future.
	 *
	 * @param string $type Field type
	 * @param array<string,mixed> $field_overrides Field config entries that override the template ones
	 *
	 * @return array<string,mixed> Setting field configuration
	 *
	 * @since 9.6.7.5
	 */
	static private function get_field_template( string $type, array $field_overrides = array() ): array {
		$ret = array();

		switch ( $type ) {
			case 'diagnostic_log':
				$ret = array(
					'title'    => __( 'Enable diagnostic logging', 'wp-cerber' ),
					/* translators: %s is a link to the Diagnostic Log page. */
					'label'    => sprintf( __( 'Once enabled, the log is available here: %s', 'wp-cerber' ), ' <a target="_blank" href="' . crb_admin_link_for_html( 'diag-log' ) . '">' . __( 'Diagnostic Log', 'wp-cerber' ) . '</a>' ),
					'type'     => 'checkbox',
					'diag_log' => 'Module not specified', // Placeholder
					'rollback' => function ( $new ) {
						if ( ! $new ) {
							return false;
						}

						$check = cerber_get_the_folder( true );
						if ( crb_is_wp_error( $check ) ) {
							cerber_admin_notice( $check->get_error_message() );

							return true;
						}

						return false;
					},
				);
		}

		if ( $field_overrides ) {
			$ret = array_merge( $ret, $field_overrides );
		}

		return $ret;
	}
}