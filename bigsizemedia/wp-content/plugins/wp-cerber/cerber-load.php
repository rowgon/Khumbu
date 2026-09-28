<?php
/*
 * Copyright (C) 2015-2026 Cerber Tech Inc., https://wpcerber.com
 * SPDX-License-Identifier: GPL-2.0-or-later
 *
 * This file is part of WP Cerber Security.
 *
 * WP Cerber Security is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Cerber Security is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Cerber Security. If not, see <https://www.gnu.org/licenses/>.
 */

// If this file is called directly, abort executing.
if ( ! defined( 'WPINC' ) ) {
	exit;
}

// WP Cerber's DB tables

const CERBER_LOG_TABLE = 'cerber_log';
const CERBER_QMEM_TABLE = 'cerber_qmem';
const CERBER_TRAF_TABLE = 'cerber_traffic';
const CERBER_ACL_TABLE = 'cerber_acl';
const CRB_LOCKOUT_TABLE = 'cerber_blocks';
const CERBER_LAB_TABLE = 'cerber_lab';
const CERBER_LAB_IP_TABLE = 'cerber_lab_ip';
const CERBER_LAB_NET_TABLE = 'cerber_lab_net';
const CERBER_GEO_TABLE = 'cerber_countries';
const CRB_SCANFILES_TABLE = 'cerber_files';

const CERBER_SETS_TABLE = 'cerber_sets';
const CERBER_MS_TABLE = 'cerber_ms';
const CERBER_MS_LIST_TABLE = 'cerber_ms_lists';
const CERBER_USS_TABLE = 'cerber_uss';

const CERBER_DB_TYPES = array(
	CRB_SCANFILES_TABLE => array(
		'scan_id'     => 'int',
		'scan_type'   => 'int',
		'scan_mode'   => 'int',
		'scan_status' => 'int',
		'scan_step'   => 'int',
	),
);

const CERBER_BUKEY = '_crb_blocked';
const CERBER_PREFIX = '_cerber_';
const CERBER_MARKER1 = 'WP CERBER GROOVE';
const CERBER_MARKER2 = 'WP CERBER CLAMPS';
const CRB_REMOTE_IP_UNAVAILABLE = '0.0.0.0';

const WP_LOGIN_SCRIPT = 'wp-login.php';
const WP_REG_URI = 'wp-register.php';
const WP_SIGNUP_SCRIPT = 'wp-signup.php';
const WP_XMLRPC_SCRIPT = 'xmlrpc.php';
const WP_TRACKBACK_SCRIPT = 'wp-trackback.php';
const WP_PING_SCRIPT = 'wp-trackback.php';
const WP_COMMENT_SCRIPT = 'wp-comments-post.php';

const GOO_RECAPTCHA_URL = 'https://www.google.com/recaptcha/api/siteverify';

const CERBER_CIREC_LIMIT = 30; // Upper limit for allowed nested values during inspection for malware

const CRB_USER_SET = 'cerber_user';
const CRB_SITE_SET = 'cerber_site_meta';

const CRB_ISSUE_SET = 'cerber_issues';

const CRB_CNTX_SAFE = 1;
const CRB_CNTX_NEXUS = 2;

spl_autoload_register( function ( $class_name ) {
	static $classes = [
		'Revalt'                 => '/includes/Revalt.php',
		'CRB_Bug_Hunter'         => '/includes/cerber-bug-hunter.php',
		'CRB_Net'                => '/net/cerber-net.php',
		'CRB_RDAP_Client'        => '/net/cerber-rdap.php',
		'CRB_User_Agent_Parser'  => '/includes/CRB_User_Agent_Parser.php',
		'CRB_Deferred_Tasks'     => '/includes/CRB_Deferred_Tasks.php',
		'CRB_Issues'             => '/includes/CRB_Issues.php',
		'CRB_Issue_Monitor'      => '/includes/CRB_Issue_Monitor.php',
		'CRB_Process_Monitor'    => '/includes/CRB_Process_Monitor.php',
		'CRB_Messaging'          => '/includes/CRB_Messaging.php',
		'CRB_Activity'           => '/includes/CRB_Activity.php',
		'CRB_Activity_Alerts'    => '/includes/CRB_Activity_Alerts.php',
		'CRB_Schema_Definitions' => '/includes/CRB_Schema_Definitions.php',
		'CRB_Settings_Backup'    => '/includes/CRB_Settings_Backup.php',
		'CRB_JS_Detector'        => '/includes/detectors/CRB_JS_Detector.php',
	];

	if ( $file = $classes[ $class_name ] ?? '' ) {
		require_once( __DIR__ . $file );

		// Should be logged if a file doesn't exist?

		return;
	}
} );

require_once( __DIR__ . '/cerber-pluggable.php' );
require_once( __DIR__ . '/cerber-common.php' );
require_once( __DIR__ . '/net/cerber-ip.php' );
require_once( __DIR__ . '/includes/utils/crb-array-functions.php' );
require_once( __DIR__ . '/includes/db-warp/db-warp.php' );
require_once( __DIR__ . '/includes/cerber-geo.php' );
require_once( __DIR__ . '/includes/cerber-localization.php' );
require_once( __DIR__ . '/includes/ui-factory/ui-factory.php' );
require_once( __DIR__ . '/includes/cerber-ui-blocks.php' );
require_once( __DIR__ . '/cerber-codex.php' );
require_once( __DIR__ . '/cerber-settings.php' );
include_once( __DIR__ . '/cerber-request.php' );
require_once( __DIR__ . '/cerber-lab.php' );
require_once( __DIR__ . '/cerber-scanner.php' );
require_once( __DIR__ . '/cerber-2fa.php' );
require_once( __DIR__ . '/nexus/cerber-nexus.php' );
require_once( __DIR__ . '/cerber-ds.php' );
require_once( __DIR__ . '/includes/addons/cerber-addons.php' );
require_once( __DIR__ . '/api/cerber-addons-api.php' );

nexus_init();

if ( defined( 'WP_ADMIN' ) || defined( 'WP_NETWORK_ADMIN' ) ) {
	cerber_load_admin_code();
}

// =============================================================================================

class WP_Cerber {
	private $remote_ip;
	private $session_id;
	private $status = null;
	private $options;

	private $recaptcha = null; // Can recaptcha be verified with current request
	private $recaptcha_verified = null; // Is recaptcha successfully verified with current request
	public $recaptcha_here = null; // Is recaptcha widget enabled on the currently displayed page

	private $uri_prohibited = null;
	private $deny = null;
	private $acl = null;

	//private $boot_source_file = '';
	//private $boot_target_file = '';

	public $garbage = false; // Garbage has been deleted

	final function __construct() {

		$this->session_id = crb_random_string( 24 );

		$this->options = crb_get_settings();

		$this->remote_ip = cerber_get_remote_ip();

		$this->reCaptchaInit();

		$this->deleteGarbage();

		// Condition to check reCAPTCHA

		add_action( 'login_init', array( $this, 'reCaptchaNow' ) );

	}

	/**
	 * @since 6.3.3
	 */
	final public function isURIProhibited() {

		if ( isset( $this->uri_prohibited ) ) {
			return $this->uri_prohibited;
		}

		if ( crb_acl_is_allowed() ) {
			$this->uri_prohibited = false;

			return false;
		}

		$script = cerber_last_uri();
		$script = urldecode( $script ); // @since 8.1
		if ( substr( $script, - 4 ) != '.php' ) {
			$script .= '.php'; // Apache MultiViews enabled?
		}

		if ( $script ) {
			if ( $script == WP_LOGIN_SCRIPT
			     || $script == WP_SIGNUP_SCRIPT
			     || ( $script == WP_REG_URI && ! get_option( 'users_can_register' ) ) ) {
				if ( ! empty( $this->options['wplogin'] ) ) {
					CRB_Globals::set_act_status( 19, 'wplogin' );
					cerber_log( CRB_EV_PUR );
					crb_apply_soft_ip_lockout( $this->remote_ip, 702, $script );
					$this->uri_prohibited = true;

					return true;
				}
				if ( ( ! empty( $this->options['loginnowp'] ) && $this->options['loginnowp'] != 2 )
				     || $this->isDeny() ) {
					CRB_Globals::set_act_status( 10, 'loginnowp' );
					cerber_log( CRB_EV_PUR );
					$this->uri_prohibited = true;

					return true;
				}
			}
            elseif ( $script == WP_XMLRPC_SCRIPT || $script == WP_TRACKBACK_SCRIPT ) {
				if ( ! empty( $this->options['xmlrpc'] )
				     || $this->isDeny() ) {
					CRB_Globals::set_ctrl_setting( 'xmlrpc' );
					cerber_log( 71 );
					$this->uri_prohibited = true;

					return true;
				}
				if ( ! cerber_geo_allowed( 'geo_xmlrpc' ) ) {
					CRB_Globals::set_act_status( 16, 'geo_xmlrpc' );
					cerber_log( 71 );
					$this->uri_prohibited = true;

					return true;
				}
			}
			// @since 8.8
            elseif ( $script == WP_COMMENT_SCRIPT && cerber_is_custom_comment() ) {
				cerber_log( CRB_EV_PUR );
				$this->uri_prohibited = true;

				return true;
			}
		}

		$this->uri_prohibited = false;

		return $this->uri_prohibited;
	}

	/**
	 * @since 6.3.3
	 */
	final public function CheckProhibitedURI() {
		if ( is_admin() ) {
			return false;
		}

		if ( $this->isURIProhibited() ) {
			if ( $this->options['page404'] ) {
				cerber_404_page();
			}

			return true;
		}

		return false;
	}

	/**
	 * @since 6.3.3
	 */
	final public function InspectRequest() {
		$deny = false;
		$act = 18;

		if ( cerber_is_http_post() ) {
			if ( ! cerber_is_ip_allowed( null, CRB_CNTX_SAFE ) ) {
				$deny = true;
				$act = 18;
			}
		}
        elseif ( cerber_get_non_wp_fields() ) {
			if ( ! cerber_is_ip_allowed( null, CRB_CNTX_SAFE ) ) {
				$deny = true;
				$act = 100;
			}
		}

		if ( ! $deny && ( $files = CRB_Request::get_files() ) ) {
			foreach ( $files as $item ) {
				if ( $reason = $this->isProhibitedFilename( $item['source_name'] ) ) {
					$deny = true;
					$act = $reason;
					break;
				}
			}
		}


		if ( $deny ) {
			CRB_Globals::set_ctrl_setting( 'tienabled' );
			cerber_log( $act );
			cerber_forbidden_page();
		}
	}

	/**
	 * @since 6.3.3
	 */
	final public function isProhibitedFilename( $file_name ) {

		$prohibited = array( '.htaccess' );
		if ( in_array( $file_name, $prohibited ) ) {
			CRB_Globals::set_act_status( CRB_STS_52 );

			return 57;
		}

		if ( cerber_detect_exec_extension( $file_name, array( 'js' ) ) ) {
			CRB_Globals::set_act_status( CRB_STS_51 );

			return 56;
		}

		return false;
	}

	/**
	 * @since 6.3.3
	 */
	final public function isDeny() {
		if ( isset( $this->deny ) ) {
			return $this->deny;
		}

		$this->acl = cerber_acl_check();

		if ( $this->acl == 'B' || ! cerber_is_ip_allowed() ) {
			$this->deny = true;
		}
		else {
			$this->deny = false;
		}

		return $this->deny;
	}

	final public function getRequestID() {
		return $this->session_id;
	}

	/**
	 * Builds a user message about login restrictions related to the client IP.
	 *
	 * Returns a message when the current request is blocked or limited by security rules.
	 *
	 * @return string User-facing message.
	 */
	final public function get_auth_restriction_message(): string {

		$status = 0; // Default state, generic "not allowed to log in" message

		if ( $block = cerber_block_check() ) {
			$status = $block->reason_id;
		}
        elseif ( crb_acl_is_blocked()
		         || lab_is_blocked( null, false ) ) {
			$status = 1;
		}
        elseif ( cerber_is_citadel() ) {
			$status = 3;
		}

		switch ( $status ) {
			case 701: // Exceeded the allowed number of login attempts
				$block = crb_get_lockout();
				$seconds_left = (int) max( 0, $block->block_until - time() );
				$minutes = (int) max( 1, ceil( $seconds_left / 60 ) );

				$msg = sprintf(
					_n(
						'You have exceeded the number of allowed login attempts. Please try again in %d minute.',
						'You have exceeded the number of allowed login attempts. Please try again in %d minutes.',
						$minutes,
						'wp-cerber'
					),
					$minutes
				);

				return apply_filters( 'cerber_msg_reached', $msg, $minutes );

			default:
				return apply_filters( 'cerber_msg_blocked', __( 'You are not allowed to log in. Ask your administrator for assistance.', 'wp-cerber' ), $status );
		}
	}

	/**
	 * Builds a user message about the remaining login attempts.
	 *
	 * The message is returned only if the remaining attempts
	 * are fewer than the configured limit. If no message is needed,
	 * an empty string is returned.
	 *
	 * @return string User message or an empty string.
	 */
	final public function get_remaining_login_attempts_message(): string {
		$acl = ! $this->options['limitwhite'];

		$remain = cerber_get_remain_count( array( 'acl' => $acl ) );

		if ( $remain < $this->options['attempts'] ) {
			if ( $remain == 0 ) {
				$remain = 1;  // with some settings or when lockout was manually removed, we need to have 1 attempt.
			}

			$msg = crb_determine_plural_text(
				$remain,
				__( 'You have only one login attempt remaining.', 'wp-cerber' ),

				/* translators: %d is the number of remaining login attempts. */
				_n(
					'You have %d login attempt remaining.',
					'You have %d login attempts remaining.',
					$remain,
					'wp-cerber'
				)
			);

			return apply_filters( 'cerber_msg_remain', $msg, $remain );
		}

		return '';
	}

	final public function getSettings( $name = null ) {
		if ( ! empty( $name ) ) {
			if ( isset( $this->options[ $name ] ) ) {
				return $this->options[ $name ];
			}
			else {
				return false;
			}
		}

		return $this->options;
	}

	/**
	 * Adding reCAPTCHA widgets
	 *
	 */
	final public function reCaptchaInit() {

		if ( empty( $this->options['sitekey'] )
		     || empty( $this->options['secretkey'] )
		     || ( crb_get_settings( 'recapipwhite' ) && crb_acl_is_allowed() ) ) {
			return;
		}

		// Native WP forms
		add_action( 'login_form', function () {
			get_wp_cerber()->reCaptcha( 'widget', 'recaplogin' );
		} );
		add_filter( 'login_form_middle', function ( $value ) {
			$value .= get_wp_cerber()->reCaptcha( 'widget', 'recaplogin', false );

			return $value;
		} );
		add_action( 'lostpassword_form', function () {
			get_wp_cerber()->reCaptcha( 'widget', 'recaplost' );
		} );
		add_action( 'register_form', function () {
			if ( ! did_action( 'woocommerce_register_form_start' ) ) {
				get_wp_cerber()->reCaptcha( 'widget', 'recapreg' );
			}
		} );

		// Support for WooCommerce forms: @since 3.8

		add_action( 'woocommerce_login_form', function () {
			get_wp_cerber()->reCaptcha( 'widget', 'recapwoologin' );
		} );
		add_action( 'woocommerce_lostpassword_form', function () {
			get_wp_cerber()->reCaptcha( 'widget', 'recapwoolost' );
		} );
		add_action( 'woocommerce_register_form', function () {
			if ( ! did_action( 'woocommerce_register_form_start' ) ) {
				return;
			}
			get_wp_cerber()->reCaptcha( 'widget', 'recapwooreg' );
		} );
		add_filter( 'woocommerce_process_login_errors', function ( $validation_error ) {
			$wp_cerber = get_wp_cerber();
			//$wp_cerber->reCaptchaNow();
			if ( ! $wp_cerber->reCaptchaValidate( 'woologin', true ) ) {

				return new WP_Error( 'incorrect_recaptcha', $wp_cerber->reCaptchaMsg( 'woocommerce-login' ) );
			}

			return $validation_error;
		} );

		add_filter( 'allow_password_reset', function ( $var ) {
			static $done; // 'allow_password_reset' is fired in WooCommerce and WP (twice in different functions)

			if ( ! $done && crb_is_woo_reset() ) {
				$done = true;
				$wp_cerber = get_wp_cerber();
				$login = crb_get_user_login_field();

				if ( ! $wp_cerber->reCaptchaValidate( 'woolost', true ) ) {

					cerber_log( CRB_EV_PRD, $login );

					return new WP_Error( 'incorrect_recaptcha', $wp_cerber->reCaptchaMsg( 'woocommerce-lost' ) );
				}

				cerber_log( CRB_EV_PRS, $login );
			}

			return $var;
		}, PHP_INT_MAX );

		add_filter( 'woocommerce_process_registration_errors', function ( $validation_error ) {
			$wp_cerber = get_wp_cerber();
			//$wp_cerber->reCaptchaNow();
			if ( ! $wp_cerber->reCaptchaValidate( 'wooreg', true ) ) {

				cerber_log( 54 );

				return new WP_Error( 'incorrect_recaptcha', $wp_cerber->reCaptchaMsg( 'woocommerce-register' ) );
			}

			return $validation_error;
		} );

	}

	/**
	 * Generates reCAPTCHA HTML
	 *
	 * @param string $part 'style' or 'widget'
	 * @param null $option what plugin setting must be set to show the reCAPTCHA
	 * @param bool $echo if false, return the code, otherwise show it
	 *
	 * @return null|string
	 */
	final public function reCaptcha( $part = '', $option = null, $echo = true ) {
		if ( empty( $this->options['sitekey'] ) || empty( $this->options['secretkey'] )
		     || ( $option && empty( $this->options[ $option ] ) )
		) {
			return null;
		}

		$sitekey = $this->options['sitekey'];
		$ret = '';

		switch ( $part ) {
			case 'style': // for default login WP form only - fit it in width nicely.
				?>
                <style>
                    #rc-imageselect, .g-recaptcha {
                        transform: scale(0.9);
                        -webkit-transform: scale(0.9);
                        transform-origin: 0 0;
                        -webkit-transform-origin: 0 0;
                    }

                    .g-recaptcha {
                        margin: 16px 0 20px 0;
                    }
                </style>
				<?php
				break;
			case 'widget':
				if ( ! empty( $this->options[ $option ] ) ) {
					$this->recaptcha_here = true;

					//if ($this->options['invirecap']) $ret = '<div data-size="invisible" class="g-recaptcha" data-sitekey="' . $sitekey . '" data-callback="now_submit_the_form" id="cerber-recaptcha" data-badge="bottomright"></div>';
					if ( $this->options['invirecap'] ) {
						$ret = '<span class="cerber-form-marker"></span><div data-size="invisible" class="g-recaptcha" data-sitekey="' . $sitekey . '" data-callback="now_submit_the_form" id="cerber-recaptcha" data-badge="bottomright"></div>';
					}
					else {
						$ret = '<span class="cerber-form-marker"></span><div class="g-recaptcha" data-sitekey="' . $sitekey . '" data-callback="form_button_enabler" id="cerber-recaptcha"></div>';
					}

					//$ret = '<span class="cerber-form-marker g-recaptcha"></span>';

				}
				break;
		}
		if ( $echo ) {
			echo $ret;
			$ret = null;
		}

		return $ret;
		/*
			<script type="text/javascript">
				var onloadCallback = function() {
					//document.getElementById("wp-submit").disabled = true;
					grecaptcha.render("c-recaptcha", {"sitekey" : "<?php echo $sitekey; ?>" });
					//document.getElementById("wp-submit").disabled = false;
				};
			</script>
			<script src = "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=<?php echo $lang; ?>" async defer></script>
			*/
	}

	/**
	 * Validate reCAPTCHA by calling Google service
	 * Returns true on success or if validation is not needed (reCAPTCHA is not enabled for the given form)
	 *
	 * @param string $form Form ID (slug)
	 * @param boolean $force Force validation without pre-checks
	 *
	 * @return bool true on success false on failure
	 */
	final public function reCaptchaValidate( $form = null, $force = false ) {

		if ( crb_get_settings( 'recapipwhite' ) && crb_acl_is_allowed() ) {
			return true;
		}

		if ( ! $force ) {
			if ( ! $this->recaptcha ) {
				return true;
			}
		}

		if ( $this->recaptcha_verified != null ) {
			return $this->recaptcha_verified;
		}

		if ( $form == 'comment' && $this->options['recapcomauth'] && is_user_logged_in() ) {
			return true;
		}

		if ( ! $form ) {
			$form = $_REQUEST['action'] ?? 'login';
		}

		$forms = array( // known pairs: form => specific plugin setting
			'lostpassword' => 'recaplost',
			'register'     => 'recapreg',
			'login'        => 'recaplogin',
			'comment'      => 'recapcom',
			'woologin'     => 'recapwoologin',
			'woolost'      => 'recapwoolost',
			'wooreg'       => 'recapwooreg',
		);

		$setting_id = $forms[ $form ] ?? false;

		if ( $setting_id ) {
			if ( empty( $this->options[ $setting_id ] ) ) {
				return true; // no validation required: using reCAPTCHA is not enabed in the plugin settings
			}
		}
		else {
			return true; // we don't know this form
		}

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			// Among other issues it means invalid reCAPTCHA key or/and secret

			CRB_Globals::set_bot_status( CRB_STS_532 );
			CRB_Globals::set_ctrl_setting( $setting_id );
			$this->reCaptchaFailed( $form );

			return false;
		}

		$result = $this->reCaptchaRequest( $_POST['g-recaptcha-response'] );

		if ( ! $result ) {
			CRB_Globals::set_bot_status( 534 );
			CRB_Globals::set_ctrl_setting( $setting_id );

			return false;
		}

		if ( ! empty( $result['success'] ) ) {
			$this->recaptcha_verified = true;
			CRB_Globals::set_bot_status( 531 );

			return true;
		}

		$this->recaptcha_verified = false;
		CRB_Globals::set_bot_status( CRB_STS_532 );
		CRB_Globals::set_ctrl_setting( $setting_id );

		if ( ! empty( $result['error-codes'] ) ) {
			if ( in_array( 'invalid-input-secret', (array) $result['error-codes'] ) ) {
				CRB_Globals::set_bot_status( 533 );
			}
		}

		$this->reCaptchaFailed( $form );

		return false;
	}

	final function reCaptchaFailed( $context = '' ) {

		if ( $this->options['recaptcha-period']
		     && $this->options['recaptcha-number']
		     && $this->options['recaptcha-within'] ) {

			if ( crb_acl_is_allowed() ) {
				return;
			}

			$range = time() - absint( $this->options['recaptcha-within'] ) * 60;

			$num = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE ip = "' . $this->remote_ip . '" AND ac_bot = ' . CRB_STS_532 . ' AND stamp > ' . $range );

			$num ++; // Current failed attempt

			if ( $num >= $this->options['recaptcha-number'] ) {
				crb_apply_ip_lockout( $this->remote_ip, 705 );
			}

		}

	}

	/**
	 * A form with possible reCAPTCHA has been submitted.
	 * Allow to process reCAPTCHA by setting a global flag.
	 * Must be called before reCaptchaValidate();
	 *
	 */
	final public function reCaptchaNow() {
		if ( cerber_is_http_post() && $this->options['sitekey'] && $this->options['secretkey'] ) {
			$this->recaptcha = true;
		}
	}

	/**
	 * Make a request to the Google reCaptcha web service
	 *
	 * @param string $response Google specific field from the submitted form (widget)
	 *
	 * @return false|array Response of the Google service or false on failure
	 */
	final public function reCaptchaRequest( $response = '' ) {

		if ( ! $response ) {
			if ( ! $response = crb_array_get( $_POST, 'g-recaptcha-response' ) ) {
				return false;
			}
		}

		$curl = @curl_init(); // @since 4.32
		if ( ! $curl ) {
			crb_add_admin_error( 'Unable to initialize cURL library' );

			return false;
		}

		$opt = crb_configure_curl( $curl, array(
			CURLOPT_URL            => GOO_RECAPTCHA_URL,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => array( 'secret' => $this->options['secretkey'], 'response' => $response ),
			CURLOPT_RETURNTRANSFER => true,
		) );

		if ( ! $opt ) {
			crb_add_admin_error( curl_error( $curl ) );

			return false;
		}

		$result = @curl_exec( $curl );
		if ( ! $result ) {
			crb_add_admin_error( curl_error( $curl ) );
			$result = false;
		}

		return json_decode( $result, true );

	}

	final public function reCaptchaMsg( $context = null ) {
		if ( crb_get_settings( 'invirecap' ) ) {
			$msg = __( 'Human verification failed.', 'wp-cerber' );
		}
		else {
			$msg = __( 'Human verification failed. Please click the square box in the reCAPTCHA block below.', 'wp-cerber' );
		}

		return apply_filters( 'cerber_msg_recaptcha', $msg, $context );
	}

	final public function deleteGarbage() {
		if ( $this->garbage ) {
			return;
		}

		$last = cerber_get_set( 'garbage_collector', null, false );

		if ( $last > ( time() - 60 ) ) { // We do this once a minute
			$this->garbage = true;

			return;
		}

		crb_delete_expired_lockouts();

		cerber_update_set( 'garbage_collector', time(), null, false );
		$this->garbage = true;
	}
}

function cerber_init() {
	static $done = false;

	if ( $done ) {
		return;
	}

	crb_enable_test_environment_logging();

	cerber_on_plugin_activation();

	cerber_error_control();

	if ( crb_get_settings( 'tiphperr' )
	     || crb_get_settings( 'log_crb_errors' ) ) {

		set_exception_handler( [ 'CRB_Bug_Hunter', 'exception_handler' ] );
		set_error_handler( [ 'CRB_Bug_Hunter', 'error_handler' ] );
	}

	cerber_upgrade_all();

	get_wp_cerber();

	cerber_beast();

	$antibot = cerber_antibot_gene();
	if ( $antibot && ! empty( $antibot[1] ) ) {
		foreach ( $antibot[1] as $item ) {
			cerber_set_cookie( $item[0], $item[1], time() + 3600 * 24 );
		}
	}

	// Redirection control: no default aliases for redirections
	if ( crb_block_admin_redirections() ) {
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
	}

	$hooks = apply_filters( 'cerber_antibot_hooks', array() );
	if ( ! empty( $hooks['login_register'] ) ) {
		foreach ( $hooks['login_register'] as $hook ) {
			add_action( $hook, 'cerber_login_register_stuff', 1000 );
		}
	}

	/*add_action( 'wp_upgrade', function () {
		lab_get_site_meta();
	} );*/

	$done = true;
}

/**
 * Returns the singleton instance of WP_Cerber.
 *
 * The object is created on the first call and reused afterwards.
 *
 * @return WP_Cerber The main WP Cerber object.
 * @since 6.0
 *
 */
function get_wp_cerber(): WP_Cerber {

	static $the_wp_cerber = null;

	if ( ! isset( $the_wp_cerber ) ) {
		$the_wp_cerber = new WP_Cerber();
	}

	return $the_wp_cerber;
}

add_action( 'plugins_loaded', function () {

	cerber_error_control();

	get_wp_cerber();

	if ( ! wp_next_scheduled( 'cerber_bg_launcher' ) ) {
		wp_schedule_event( time(), 'crb_five', 'cerber_bg_launcher' );
	}

}, 1000 );

function cerber_load_admin_code() {

	spl_autoload_register( function ( $class_name ) {
		static $classes = [
			'CRB_Traffic_Log'        => '/admin/CRB_Traffic_Log.php',
			'CRB_Traffic_Log_Screen' => '/admin/CRB_Traffic_Log_Screen.php',
			'CRB_Widgets'            => '/admin/includes/CRB_Widgets.php',
			'CRB_Settings_Renderer'  => '/admin/includes/CRB_Settings_Renderer.php',
			'CRB_Settings_Registry'  => '/admin/includes/CRB_Settings_Registry.php',
			'CRB_Sessions_Table'     => '/admin/includes/CRB_Sessions_Table.php',
		];

		if ( $file = $classes[ $class_name ] ?? '' ) {
			require_once( __DIR__ . $file );

			return;
		}

		// Unknown class or a file doesn't exist - should be logged?

	} );

	//cerber_cache_enable();

	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	require_once( __DIR__ . '/admin/cerber-admin.php' );
	require_once( __DIR__ . '/admin/cerber-admin-settings.php' );
	require_once( __DIR__ . '/admin/cerber-users.php' );
	require_once( __DIR__ . '/admin/cerber-tools.php' );
	require_once( __DIR__ . '/admin/cerber-dashboard.php' );
	require_once( __DIR__ . '/cerber-toolbox.php' );
	require_once( __DIR__ . '/admin/kb/cerber-azoth.php' );
	require_once( __DIR__ . '/admin/cerber-admin-help.php' );
	require_once( __DIR__ . '/admin/cerber-admin-acl.php' );

}

/**
 * If we need WP auth constants to be available.
 * It makes sense only in "Standard mode" and if WP Cerber executes its code before WP filters.
 *
 * @since 8.8
 */
function cerber_load_wp_constants() {

	require_once( ABSPATH . WPINC . '/default-constants.php' );

	if ( is_multisite() ) {
		ms_cookie_constants();
	}

	wp_cookie_constants();
}

/**
 * More profound analysis of activities
 *
 */
function cerber_extra_vision() {

	if ( ! CRB_Activity::get_logged()
	     || CRB_Globals::$blocked ) {
		return;
	}

	$deep_look = array();

	if ( CRB_Globals::$bot_status ) {
		$deep_look [] = array( 'bot' => array( CRB_STS_11, CRB_STS_532 ), 'allowed' => 5, 'period' => 10, 'reason_id' => 706, 'duration' => 15, 'soft_block' => true );
	}

	// Multiple different malicious activities

	$deep_look [] = array( 'act_list' => crb_get_activity_set( 'mitigation' ), 'allowed' => 10, 'period' => 15, 'reason_id' => 707, 'soft_block' => true );

	// Excessive usage or a brute-force attack.
	// No exceptions for known users since they can also pose threats.
	// Users with accounts may exploit their access to attack the website, seeking privilege escalation.

	$deep_look [] = array( 'act_list' => CRB_EV_PRD, 'allowed' => 10, 'period' => 10, 'reason_id' => 725, 'status_id' => 546 );
	$deep_look [] = array( 'act_list' => 400, 'allowed' => 10, 'period' => 10, 'reason_id' => 721, 'status_id' => 542 );

	foreach ( $deep_look as $rules ) {
		if ( crb_check_and_block( $rules ) ) {
			return;
		}
	}
}

/**
 * Check the activity log for suspicious events and block IP if it exceeded the given thresholds
 * No exception for known users.
 *
 * @param array $params
 *
 * @return bool
 *
 * @since 9.5.8
 */
function crb_check_and_block( $params = array() ) {

	static $defaults = array(
		// Calculation parameters
		'ip'         => null,
		'act_list'   => array(),

		// Lockout parameters
		'reason_id'  => null,
		'status_id'  => 18,
		'soft_block' => false,
		'duration'   => 0,
	);

	$params = array_merge( $defaults, $params );

	if ( $params['act_list']
	     && ! CRB_Activity::is_logged( $params['act_list'] ) ) {
		return false;
	}

	$remain = cerber_get_remain_count( $params );

	if ( $remain > 0 ) {
		return false;
	}

	if ( ! $params['soft_block'] ) {
		$result = crb_apply_ip_lockout( $params['ip'], $params['reason_id'], '', $params['duration'] );
	}
	else {
		$result = crb_apply_soft_ip_lockout( $params['ip'], $params['reason_id'], '', $params['duration'] );
	}

	if ( $result ) {
		CRB_Globals::set_act_status( $params['status_id'] );
	}

	return true;
}

/**
 * Displays WordPress login page and terminates the execution of the script.
 *
 * @return void
 */
function cerber_show_login_page() {

	if ( crb_get_settings( 'loginpath' )
	     && cerber_is_login_request() ) {

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );  // @since 5.7.6
		}
		@ini_set( 'display_startup_errors', 0 );
		@ini_set( 'display_errors', 0 );
		add_action( 'login_init', function () {
			@ini_set( 'display_startup_errors', 0 );
			@ini_set( 'display_errors', 0 );
		} );

		// Prevent getting "Undefined variable" error
		$user_login = '';
		$error = '';

		require( ABSPATH . WP_LOGIN_SCRIPT ); // load default wp-login.php form
		exit;
	}
}

/**
 * Check if the current HTTP request is a login/register/lost password page request
 *
 * @return bool
 */
function cerber_is_login_request() {
	static $ret;

	if ( isset( $ret ) ) {
		return $ret;
	}

	$ret = false;

	if ( $path = crb_get_settings( 'loginpath' ) ) {

		$uri = $_SERVER['REQUEST_URI'];

		if ( $pos = strpos( $uri, '?' ) ) {
			$uri = substr( $uri, 0, $pos );
		}

		$components = explode( '/', rtrim( $uri, '/' ) );
		$last = end( $components );

		if ( $path === $last
		     && ! cerber_is_rest_url() ) {
			$ret = true;
		}
	}
    elseif ( CRB_Request::is_script( '/' . WP_LOGIN_SCRIPT ) ) {
		$ret = true;
	}

	return $ret;
}

/**
 * Does the current location (URL) requires a user to be logged in to view
 *
 * @param $allowed_url string An URL that is allowed to view without authentication
 *
 * @return bool
 */
function cerber_auth_required( $allowed_url ) {
	if ( $allowed_url && CRB_Request::is_full_url_equal( $allowed_url ) ) {
		return false;
	}
	if ( cerber_is_login_request() ) {
		return false;
	}
	if ( CRB_Request::is_script( array( '/' . WP_LOGIN_SCRIPT, '/' . WP_SIGNUP_SCRIPT, '/wp-activate.php' ) ) ) {
		return false;
	}
	if ( CRB_Request::is_full_url_start_with( wp_login_url() ) ) {
		return false;
	}
	if ( class_exists( 'WooCommerce' ) ) {
		if ( CRB_Request::is_full_url_start_with( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Returns login entered by a user on the standard WordPress and WooCommerce login forms
 *
 * @param string $default Default value
 *
 * @return string
 */
function crb_get_user_login_field( $default = '' ) {
	if ( ! empty( $_POST['user_login'] ) ) {
		return sanitize_user( stripslashes( $_POST['user_login'] ) );
	}

	return $default;
}

// Authentication --------------------------------------------------------------------

remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
remove_filter( 'authenticate', 'wp_authenticate_application_password', 20 );
add_filter( 'authenticate', function ( $user, $username, $password ) {
	return cerber_authenticate( $user, $username, $password );
}, PHP_INT_MAX, 3 ); // PHP_INT_MAX @since 8.8
/**
 * Authenticates the user, performing various security checks.
 *
 * @param null|WP_User|WP_Error $user
 * @param string $username
 * @param string $password
 *
 * @return WP_User|WP_Error
 */
function cerber_authenticate( $user, $username, $password = '' ) {

	if ( $username
	     && ( crb_get_settings( 'loginnowp' ) == 2 )
	     && ! crb_acl_is_allowed()
	     && CRB_Request::is_script( '/' . WP_LOGIN_SCRIPT ) ) {

		CRB_Globals::set_ctrl_setting( 'loginnowp' );

		return crb_login_error( $username, CRB_EV_LDN, 50 );
	}

	// reCAPTCHA
	if ( ! cerber_is_api_request()
	     && ! get_wp_cerber()->reCaptchaValidate() ) {

		cerber_log( CRB_EV_LDN, $username );

		return new WP_Error( 'incorrect_recaptcha',
			'<strong>' . __( 'ERROR:', 'wp-cerber' ) . ' </strong>' .
			get_wp_cerber()->reCaptchaMsg( 'login' ) );
	}

	// Prohibited usernames
	if ( $username
	     && crb_is_username_prohibited( $username ) ) {

		CRB_Globals::set_ctrl_setting( 'prohibited' );

		if ( ! crb_get_settings( 'prohibited_rule' ) ) {
			crb_apply_ip_lockout( null, 704, $username );
		}

		return crb_login_error( $username, 52 );
	}

	$user = wp_authenticate_username_password( $user, $username, $password );
	$user = wp_authenticate_email_password( $user, $username, $password );

	// Application passwords
	$app_checked = false;
	$app = false;
	if ( ! ( $user instanceof WP_User ) ) {
		$app_checked = true;
		$user = wp_authenticate_application_password( $user, $username, $password );
		$app = $user instanceof WP_User;
	}

	// TODO: split the function into two parts:
	// 1. before user identification - do IP-based checks
	// 2. after user identification and password check - do user-based and role-based checks
	$user = cerber_restrict_auth( $user, $app );

	// Authentication failed or denied by cerber_restrict_auth()
	if ( ! ( $user instanceof WP_User ) || ! $user->ID ) {

		if ( crb_is_wp_error( $user ) ) {

			$err_code = $user->get_error_code();

			$ignore_codes = array( 'empty_username', 'empty_password', 'expired_session' );
			if ( ! in_array( $err_code, $ignore_codes ) ) {
				cerber_login_failed( $username );
			}

			if ( crb_get_settings( 'nologinhint' )
			     && ( $err_code == 'invalid_email' || $err_code == 'invalid_username' )
			     && ! crb_acl_is_allowed() ) {

				if ( ! $msg = crb_get_settings( 'nologinhint_msg' ) ) {
					return crb_login_error( $username );
				}

				return new WP_Error( 'cerber_login_error', sprintf( $msg, $username ) );
			}

		}

		return $user;
	}

	// Application passwords policies
	if ( cerber_is_api_request() ) {
		$app_pwd = cerber_get_user_policy( 'app_pwd', $user, 'app_pwd' );
		$deny = false;

		if ( ( 2 == $app_pwd && ! $app_checked )
		     || 3 == $app_pwd ) {
			$deny = true;
		}

		if ( $deny ) {
			cerber_log( 152, $username, 0, CRB_STS_25 );
			status_header( 403 );

			return new WP_Error( 'app_password_denied', 'Authentication failed' );
		}
	}

	// Shadowing
	if ( crb_get_settings( 'ds_4acc' ) && CRB_DS::is_ready( 1 ) ) {

		if ( ! CRB_DS::is_user_valid( $user->ID ) ) {
			return crb_login_error( $username, CRB_EV_LDN, 35 );
		}

		if ( ! $app_checked ) {
			$pwd = CRB_DS::get_user_pass( $user->ID );
			if ( ! $pwd || ( $password && ! wp_check_password( $password, $pwd, $user->ID ) ) ) {
				return crb_login_error( $username, CRB_EV_LDN, 36 );
			}
		}
	}

	// Authenticated via API
	if ( cerber_is_api_request() ) {
		if ( $app_checked ) {
			cerber_log( 151, $username, $user->ID );
		}
		else {
			cerber_log( CRB_EV_LIN, $username, $user->ID );
		}
	}

	CRB_Globals::$user_id = $user->ID;

	return $user;
}

add_filter( 'wp_is_application_passwords_available_for_user', 'cerber_is_app_passwords', PHP_INT_MAX, 2 );
function cerber_is_app_passwords( $var, $user ) {
	if ( $user instanceof WP_User ) {
		if ( 3 == cerber_get_user_policy( 'app_pwd', $user, 'app_pwd' ) ) {
			return false;
		}
	}

	return $var;
}

/**
 * Stops (restricts) authentication of a user once the user identified (existing users)
 *
 * @param WP_User $user Valid WP_User object to check
 * @param bool $app If set to true, the user has been authenticated with an application password
 *
 * @return WP_User|WP_Error
 */
function cerber_restrict_auth( $user, $app = false ) {

	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	$deny = false;
	$user_msg = '';

	if ( $b = crb_is_user_blocked( $user->ID ) ) {
		$user_msg = $b['blocked_msg'];
		CRB_Globals::set_act_status( CRB_STS_29 );
		$deny = true;
	}
    elseif ( ! $app && ( $b = crb_check_user_limits( $user->ID ) ) ) {
		$user_msg = $b;
		CRB_Globals::set_act_status( 38 );
		$deny = true;
	}
    elseif ( crb_acl_is_allowed() ) { // TODO: Must be checked before user identification
		$deny = false;
	}
    elseif ( ! cerber_is_ip_allowed() ) { // TODO: Must be checked before user identification
		$deny = true;
	}
    elseif ( ! cerber_geo_allowed( 'geo_login', $user ) ) {
		CRB_Globals::set_act_status( 16 );
		$deny = true;
	}
    elseif ( lab_is_blocked( cerber_get_remote_ip() ) ) {
		CRB_Globals::set_act_status( 15 );
		$deny = true;
	}

	if ( $deny ) {
		status_header( 403 );
		$error = new WP_Error();
		if ( ! $user_msg ) {
			$user_msg = get_wp_cerber()->get_auth_restriction_message();
		}
		$error->add( 'cerber_auth_error', $user_msg, array( 'user_id' => $user->ID ) );

		return $error;
	}

	return $user;
}

/**
 * Logs authentication errors, generates WP_Error object
 *
 * @param string $username
 * @param int $act
 * @param int $status
 *
 * @return WP_Error
 */
function crb_login_error( $username = '', $act = null, $status = null ) {

	CRB_Globals::set_act_status( $status );

	if ( $act ) {
		cerber_log( $act, $username );
	}

	// Create with a message identical to the default WP

	if ( ! is_email( $username ) ) {
		$msg =
			/* translators: Here %s is a user login. */
			__( 'The password you entered for the username %s is incorrect.', 'wp-cerber' );
	}
	else {
		$msg =
			/* translators: Here %s is an email address. */
			__( 'The password you entered for the email address %s is incorrect.', 'wp-cerber' );
	}

	return new WP_Error(
		'incorrect_password',
		'<strong>' . __( 'Error', 'wp-cerber' ) . ':</strong> ' . sprintf( $msg, '<strong>' . $username . '</strong>' )
		. ' <a href="' . wp_lostpassword_url() . '"> ' . __( 'Lost your password?' ) . '</a>' );
}

add_action( 'wp_login', function ( $login, $user ) {
	cerber_user_login( $login, $user );
}, 0, 2 );
/**
 * Executes after the user has successfully logged in.
 *
 * @param $login string
 * @param $user WP_User
 */
function cerber_user_login( $login, $user ) {

	CRB_Globals::$user_id = $user->ID;

	if ( ! empty( $_POST['log'] ) && ! empty( $_POST['pwd'] ) ) { // default WP login form
		$user_login = crb_escape_html( $_POST['log'] );
	}
	else {
		$user_login = $login;
	}

	$status = CRB_2FA::main_controller( $user_login, $user );

	if ( crb_is_wp_error( $status ) ) {
		cerber_error_log( $status->get_error_message() . ' | RID: ' . get_wp_cerber()->getRequestID(), '2FA' );
	}
    elseif ( $status ) {
		crb_sessions_update( $user->ID, CRB_2FA::get_session_token_hash(), array( 'mfa_status' => $status ) );
	}

	crb_update_login_history( $user->ID );

	cerber_log( CRB_EV_LIN, $user_login, $user->ID );

}

add_action( 'set_auth_cookie', function ( $auth_cookie, $expire, $expiration, $user_id, $scheme, $token ) {

	CRB_2FA::$token = $token;

	// Catching user switching and authentications without using a login form

	add_action( 'set_current_user', function () { // deferred to allow the possible 'wp_login' action to be logged first
		global $current_user;
		if ( $current_user instanceof WP_User ) {
			cerber_user_login( $current_user->user_login, $current_user );
		}
	} );

}, 10, 6 );

/**
 * Saves login details
 *
 * @param int $user_id
 * @param bool $reset_2fa
 *
 * @return void
 */
function crb_update_login_history( $user_id, $reset_2fa = false ) {

	$cus = cerber_get_set( CRB_USER_SET, $user_id );

	if ( ! $cus || ! is_array( $cus ) ) {
		$cus = array();
	}

	$ip = cerber_get_remote_ip();

	$cus['last_login'] = array(
		// @since 8.3
		'ip' => $ip,
		'ua' => sha1( crb_array_get( $_SERVER, 'HTTP_USER_AGENT', '' ) ),
		// @since 9.4.1
		'ts' => time(),
		'cn' => lab_get_country( $ip )
	);

	if ( ! isset( $cus['2fa_history'] ) ) {
		$cus['2fa_history'] = array( 0, time() );
	}

	if ( $reset_2fa ) {
		$cus['2fa_history'] = array( 1, time() );
	}
	else {
		$cus['2fa_history'][0] ++;
	}

	cerber_update_set( CRB_USER_SET, $cus, $user_id );
}

/**
 *
 * Handler for failed login attempts
 *
 * @param string $user_login
 *
 */
function cerber_login_failed( $user_login ) {
	static $is_processed = false;

	if ( $is_processed ) {
		return;
	}

	$is_processed = true;

	$ip = cerber_get_remote_ip();
	$acl = cerber_acl_check( $ip );

	$no_user = ! cerber_get_user( $user_login );

	$act = CRB_EV_LFL; // Generic login failed (interactive), the default

	if ( cerber_is_api_request() ) {
		$act = 152;
	}
	else {

		// TODO this should be refactored together with cerber_restrict_auth() to make things clear in the log

		if ( $no_user ) {
			$act = 51;
		}
        elseif ( in_array( CRB_Globals::$act_status, array( 15, 16, CRB_STS_25, CRB_STS_29, 38 ) )
		         || ! cerber_is_ip_allowed( $ip ) ) {
			$act = CRB_EV_LDN;
		}
	}

	cerber_log( $act, $user_login );

	if ( $acl == 'W' && ! crb_get_settings( 'limitwhite' ) ) {
		return;
	}

	if ( crb_get_settings( 'usefile' ) ) {
		cerber_file_log( $user_login, $ip );
	}

	if ( ! cerber_is_wp_ajax() ) { // Needs additional researching and, maybe, refactoring
		status_header( 403 );
	}

	if ( $acl == 'B' ) {
		return;
	}

	// Must the Citadel mode be activated?

	if ( crb_get_settings( 'citadel_on' )
	     && ( $per = crb_get_settings( 'ciperiod' ) )
	     && ! cerber_is_citadel() ) {
		$range = time() - $per * 60;
		$lockouts = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE activity = ' . CRB_EV_LFL . ' AND stamp > ' . $range );
		if ( $lockouts >= crb_get_settings( 'cilimit' ) ) {
			CRB_Globals::set_ctrl_setting( 'citadel_on' );
			cerber_enable_citadel();
		}
	}

	if ( $no_user && crb_get_settings( 'nonusers' ) ) {
		CRB_Globals::set_ctrl_setting( 'nonusers' );
		crb_apply_ip_lockout( $ip, 703, $user_login );
	}
    elseif ( cerber_get_remain_count( array( 'acl' => false ) ) < 1 ) { //Limit on the number of login attempts is reached
		crb_apply_ip_lockout( $ip, 701 );
	}

}

// ------------ User Sessions

// do_action( "added_{$meta_type}_meta", $mid, $object_id, $meta_key, $_meta_value );
add_action( 'added_user_meta', function ( $meta_id, $user_id, $meta_key, $_meta_value ) {
	if ( $meta_key === 'session_tokens' ) {
		crb_sessions_update_user_data( $user_id, $_meta_value );
	}
}, 10, 4 );

add_action( 'update_user_meta', function ( $meta_id, $user_id, $meta_key, $_meta_value ) {

	if ( $meta_key !== 'session_tokens'
	     || CRB_Globals::$session_status !== null ) {
		return;
	}

	$old_value = get_metadata_raw( 'user', $user_id, $meta_key, true );

	if ( ! is_array( $old_value ) ) {
		return;
	}

	if ( ! is_array( $_meta_value ) ) {
		$_meta_value = array();
	}

	$new = count( $_meta_value );

	if ( count( $old_value ) > $new ) {
		if ( $new == 0 ) {
			CRB_Globals::$session_status = 530;
		}
		else {
			CRB_Globals::$session_status = 0;
		}
	}
	else {
		CRB_Globals::$session_status = null;
	}

}, 10, 4 );

// do_action( "updated_{$meta_type}_meta", $meta_id, $object_id, $meta_key, $_meta_value );
add_action( 'updated_user_meta', function ( $meta_id, $user_id, $meta_key, $_meta_value ) {

	if ( $meta_key === 'session_tokens' ) {
		crb_sessions_update_user_data( $user_id, $_meta_value );
		if ( CRB_Globals::$session_status !== null ) {
			cerber_log( CRB_EV_UST, '', $user_id, CRB_Globals::$session_status );
			CRB_Globals::$session_status = null;
		}
	}

}, 10, 4 );

// do_action( "deleted_{$meta_type}_meta", $meta_ids, $object_id, $meta_key, $_meta_value );
add_action( 'deleted_user_meta', function ( $meta_ids, $user_id, $meta_key, $_meta_value ) {
	if ( $meta_key === 'session_tokens' ) {

		$query = 'DELETE FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE;

		if ( $user_id ) {
			$query .= ' WHERE user_id = ' . crb_absint( $user_id );
			cerber_log( CRB_EV_UST, '', $user_id, 530 ); // Terminated by admin
			CRB_Globals::set_act_status_if( 530 );
		}
		else {
			$buffer = cerber_get_set( 'diagnostic_buffer' );
			$buffer = ( ! is_array( $buffer ) ) ? array() : array_slice( $buffer, - 50 );
			$buffer[] = array( 'e' => 'USS1', 't' => time(), 'r' => get_wp_cerber()->getRequestID() );
			cerber_update_set( 'diagnostic_buffer', $buffer, null, true, time() + WEEK_IN_SECONDS );
		}

		cerber_db_query( $query );
	}
}, 10, 4 );

/**
 * Keep the user sessions table up to date. Typically executes after every update of "session_tokens" user meta.
 *
 * @param int $user_id User ID
 * @param array $wp_sessions List of user sessions from the "session_tokens" user meta
 *
 * @return bool
 */
function crb_sessions_update_user_data( $user_id, $wp_sessions = null ) {
	global $wpdb;

	$user_id = crb_absint( $user_id );

	if ( $wp_sessions === null ) {
		$user_meta = cerber_db_get_var( 'SELECT um.* FROM ' . $wpdb->usermeta . ' um JOIN ' . $wpdb->users . ' us ON (um.user_id = us.ID) WHERE um.user_id = ' . $user_id . ' AND um.meta_key = "session_tokens"' );

		if ( $user_meta && ! empty( $user_meta['meta_value'] ) ) {
			$wp_sessions = crb_unserialize( $user_meta['meta_value'] );
		}
	}

	return crb_sessions_sync( $user_id, $wp_sessions, true );
}

/**
 * Updating session information in the session table
 *
 * @param integer $user_id
 * @param string $wp_session_token
 * @param array $update
 *
 * @return bool|mysqli_result
 *
 * @since 9.6.3.2
 */
function crb_sessions_update( $user_id, $wp_session_token, $update ) {
	return cerber_db_update( cerber_get_db_prefix() . CERBER_USS_TABLE, array( 'user_id' => crb_absint( $user_id ), 'wp_session_token' => crb_sanitize_alphanum( $wp_session_token ) ), $update );
}

/**
 * Synchronize all user sessions from scratch
 *
 */
function crb_sessions_sync_all() {
	global $wpdb;

	if ( cerber_db_is_empty( cerber_get_db_prefix() . CERBER_USS_TABLE )
	     || 300 < cerber_db_count( (string) $wpdb->users, 'ID' ) ) {

		return crb_sessions_bulk_load();
	}

	return crb_sessions_update_all();
}

/**
 * Loads all user sessions in bulk mode. Doesn't preserve existing session data.
 *
 * @return bool
 */
function crb_sessions_bulk_load() {
	global $wpdb;

	$table = cerber_get_db_prefix() . CERBER_USS_TABLE;

	cerber_db_query( 'DELETE FROM ' . $table );

	$query = 'SELECT um.* FROM ' . $wpdb->usermeta . ' um JOIN ' . $wpdb->users . ' us ON (um.user_id = us.ID) WHERE um.meta_key = "session_tokens"';

	if ( ! $metas = cerber_db_get_results( $query ) ) {
		return false;
	}

	foreach ( $metas as $user_meta ) {

		$sessions = crb_unserialize( $user_meta['meta_value'] );
		$user_id = crb_absint( $user_meta['user_id'] );

		if ( empty( $sessions ) ) {
			continue;
		}

		foreach ( $sessions as $token => $data ) {
			if ( $data['expiration'] < time() ) {
				continue;
			}

			$ip = filter_var( $data['ip'], FILTER_VALIDATE_IP );
			$started = crb_absint( $data['login'] );
			$expires = crb_absint( $data['expiration'] );
			$token = crb_sanitize_alphanum( $token );

			cerber_db_query( 'INSERT INTO ' . $table . ' (user_id, ip, started, expires, wp_session_token) VALUES (' . $user_id . ',"' . $ip . '",' . $started . ',' . $expires . ',"' . $token . '")' );
		}
	}

	return true;
}

/**
 * Synchronizes all user sessions in bulk preserving existing rows in the sessions table
 *
 * @return bool
 *
 * @since 9.6.3.2
 */
function crb_sessions_update_all() {
	global $wpdb;

	crb_sessions_delete_expired();

	$table = cerber_get_db_prefix() . CERBER_USS_TABLE;

	$query = 'SELECT um.* FROM ' . $wpdb->usermeta . ' um JOIN ' . $wpdb->users . ' us ON (um.user_id = us.ID) WHERE um.meta_key = "session_tokens"';

	if ( ! $metas = cerber_db_get_results( $query ) ) {

		cerber_db_query( 'DELETE FROM ' . $table );

		return false;
	}

	$logged_in_users = array();

	foreach ( $metas as $user_meta ) {

		$sessions = $user_meta['meta_value'] ? crb_unserialize( $user_meta['meta_value'] ) : [];
		$user_id = crb_absint( $user_meta['user_id'] );
		$logged_in_users[] = $user_id;

		crb_sessions_sync( $user_id, $sessions );
	}

	// Delete non-existing sessions

	cerber_db_query( 'DELETE FROM ' . $table . ' WHERE user_id NOT IN (' . implode( ',', $logged_in_users ) . ')' );

	return true;
}

/**
 * Synchronizes the user session data in the sessions table with the session data stored in WordPress meta table.
 * Preserves existing rows in the sessions table.
 *
 * @param int $user_id The ID of the user whose sessions are being synchronized.
 * @param array $wp_sessions An associative array of user sessions from WordPress 'session_tokens' user meta.
 * @param bool $interactive If true, additional details will be saved when the user logs in interactively.
 *
 * @return bool Returns true on successful synchronization, false on failure.
 *
 * @since 9.6.3.2
 */
function crb_sessions_sync( $user_id, $wp_sessions, $interactive = false ) {

	$table = cerber_get_db_prefix() . CERBER_USS_TABLE;
	$errors = false;

	if ( empty( $wp_sessions ) ) {
		if ( ! cerber_db_query( 'DELETE FROM ' . $table . ' WHERE user_id = ' . $user_id ) ) {
			return false;
		}

		return true;
	}

	// Delete obsolete user sessions if any

	if ( $existing_tokens = cerber_db_get_col( 'SELECT wp_session_token FROM ' . $table . ' WHERE user_id = ' . $user_id ) ) {

		$existing_sessions = array_flip( $existing_tokens );

		if ( $delete = array_diff_key( $existing_sessions, $wp_sessions ) ) {

			$delete = crb_sanitize_alphanum( array_keys( $delete ) );

			if ( ! cerber_db_query( 'DELETE FROM ' . $table . ' WHERE user_id = ' . $user_id . ' AND wp_session_token IN ("' . implode( '","', $delete ) . '")' ) ) {
				$errors = true;
			}
		}

		// Filter out user sessions to add

		$wp_sessions = array_diff_key( $wp_sessions, $existing_sessions );
	}

	if ( $interactive ) {

		// User is logging in now (login form submitted)

		$wp_sessions = array_map( function ( $data ) {
			$data['ip'] = cerber_get_remote_ip();
			$data['sess_id'] = get_wp_cerber()->getRequestID();
			$data['cnt'] = lab_get_country( cerber_get_remote_ip() );

			return $data;

		}, $wp_sessions );

	}

	foreach ( $wp_sessions as $token => $data ) {
		if ( $data['expiration'] < time() ) {
			continue;
		}

		$ip = filter_var( $data['ip'], FILTER_VALIDATE_IP );
		$started = crb_absint( $data['login'] );
		$expires = crb_absint( $data['expiration'] );
		$request_id = crb_sanitize_alphanum( $data['sess_id'] ?? '' );
		$country = crb_sanitize_alphanum( $data['cnt'] ?? '' );
		$token = crb_sanitize_alphanum( $token );

		if ( ! cerber_db_query( 'INSERT INTO ' . $table . ' (user_id, ip, country, started, expires, session_id, wp_session_token) VALUES (' . $user_id . ',"' . $ip . '","' . $country . '",' . $started . ',' . $expires . ',"' . $request_id . '","' . $token . '")' ) ) {
			$errors = true;
		}
	}

	return ! $errors;
}

function crb_sessions_delete_expired() {
	static $done;
	if ( $done ) {
		return;
	}

	cerber_db_query( 'DELETE FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE . ' WHERE expires < ' . time() );

	$done = true;
}

/**
 * Returns the number of user sessions, optionally filtered by user ID.
 *
 * @param int|null $user_id Optional. The user ID to filter sessions by. Default is null (counts all sessions).
 *
 * @return int The count of sessions for the specified user ID or for all users if no ID is provided.
 */
function crb_sessions_get_num( $user_id = null ): int {

	$conditions = $user_id ? [ 'user_id' => absint( $user_id ) ] : [];

	return (int) cerber_db_count( cerber_get_db_prefix() . CERBER_USS_TABLE, 'user_id', $conditions );
}

/**
 * Terminates specified user sessions by updating user meta directly in the DB
 *
 * @param array|string $tokens Session tokens to kill
 * @param int $user_id Users the sessions to kill belongs to
 * @param bool $admin If true, it is executing in the WP dashboard
 *
 * @return int
 */
function crb_sessions_kill( $tokens, $user_id = null, $admin = true ) {

	if ( ! is_array( $tokens ) ) {
		$tokens = array( $tokens );
	}

	if ( ! $user_id ) {

		$tokens = crb_sanitize_alphanum( $tokens );
		$users = cerber_db_get_col( 'SELECT user_id FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE . ' WHERE wp_session_token IN ("' . implode( '","', $tokens ) . '")' );
	}
	else {
		$users = array( $user_id );
	}

	if ( ! $users || ! $tokens ) {
		return 0;
	}

	$kill = array_flip( $tokens );
	$total = 0;
	$errors = 0;

	// Prevent termination the current admin session
	if ( $token = crb_get_session_token() ) {
		unset( $kill[ cerber_hash_token( $token ) ] );
	}

	foreach ( $users as $user_id ) {
		$count = 0;

		$sessions = get_user_meta( $user_id, 'session_tokens', true );

		if ( empty( $sessions ) || ! is_array( $sessions ) ) {
			continue;
		}
		if ( ! $do_this = array_intersect_key( $kill, $sessions ) ) {
			continue;
		}

		foreach ( $do_this as $key => $nothing ) {
			unset( $sessions[ $key ] );
			unset( $kill[ $key ] );
			$count ++;
		}

		if ( $count ) {
			if ( update_user_meta( $user_id, 'session_tokens', $sessions ) ) {
				$total += $count;
			}
			else {
				$errors ++;
			}
		}
	}

	if ( $admin ) {
		if ( $errors ) {
			cerber_admin_notice( 'Error: Unable to update user meta data.' );
		}

		if ( $total ) {

			$msg = crb_determine_plural_text(
				$total,
				__( 'User session has been terminated', 'wp-cerber' ),

				/* translators: %d is the number of terminated user sessions. */
				_n(
					'%d user session has been terminated',
					'%d user sessions have been terminated',
					$total,
					'wp-cerber'
				)
			);

			cerber_admin_message( $msg );
		}
		else {
			cerber_admin_notice( 'No user sessions found.' );
		}
	}

	return $total;
}


// Enforce restrictions for the current user

add_action( 'set_current_user', function () { // the normal way
	global $current_user;
	cerber_restrict_user( $current_user->ID );
}, 0 );

add_action( 'init', function () { // backup for 'set_current_user' hook which might not be invoked
	cerber_restrict_user( get_current_user_id() );
}, 0 );

function cerber_restrict_user( $current_user_id ) {
	static $done;

	if ( $done ) {
		return;
	}

	$done = true;

	CRB_2FA::check_for_errors( $current_user_id );

	if ( ! $current_user_id ) {
		return;
	}

	if ( ( $sts = ( crb_is_user_blocked( $current_user_id ) ? CRB_STS_29 : 0 ) )
	     || ( $sts = ( CRB_DS::is_user_valid( $current_user_id ) ? 0 : 35 ) )
	     || ( $sts = ( crb_acl_is_blocked() ? 14 : 0 ) ) // @since 8.2.4
	     || ( $sts = ( cerber_geo_allowed( 'geo_login', $current_user_id ) ? 0 : 16 ) ) ) { // @since 8.2.3

		crb_force_current_user_logout( $sts );

		if ( is_admin() ) {
			crb_redirect( cerber_get_home_url() );
		}
		else {
			crb_safe_redirect( CRB_Request::full_url() );
		}

		exit;
	}

	CRB_2FA::restrict_and_verify( $current_user_id );

	if ( ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
	     && is_admin()
	     && ! is_super_admin() ) {
		if ( cerber_get_user_policy( 'nodashboard', $current_user_id ) ) {

			crb_redirect( home_url() );
			exit;
		}
	}

	if ( cerber_get_user_policy( 'notoolbar', $current_user_id ) ) {
		show_admin_bar( false );
	}

}

add_filter( 'login_redirect', function ( $redirect_to, $requested_redirect_to, $user ) {

	if ( $to = crb_redirect_by_policy( $user, 'rdr_login' ) ) {

		CRB_Globals::$redirect_url = $to;

		return $to;
	}

	return $redirect_to;
}, PHP_INT_MAX, 3 );

add_filter( 'logout_redirect', function ( $redirect_to, $requested_redirect_to, $user ) {

	if ( $to = crb_redirect_by_policy( $user, 'rdr_logout' ) ) {

		CRB_Globals::$redirect_url = $to;

		return $to;
	}

	if ( ( crb_get_settings( 'loginpath' ) )
	     && empty( $requested_redirect_to )
	     && cerber_is_login_request() ) {
		$redirect_to = cerber_get_custom_login_url() . '?loggedout=true'; // Replace the default WP redirection
	}

	return $redirect_to;
}, PHP_INT_MAX, 3 );

if ( crb_get_settings( 'loginpath' ) ) {

	add_filter( 'lostpassword_redirect', function ( $redirect_to ) {

		if ( ( crb_get_settings( 'loginpath' ) )
		     && cerber_is_login_request() ) {
			$redirect_to = cerber_get_custom_login_url() . '?checkemail=confirm'; // Replace the default WP redirection
		}

		return $redirect_to;
	}, PHP_INT_MAX );

	add_filter( 'registration_redirect', function ( $redirect_to ) {

		if ( ( crb_get_settings( 'loginpath' ) )
		     && cerber_is_login_request() ) {
			$redirect_to = cerber_get_custom_login_url() . '?checkemail=registered'; // Replace the default WP redirection
		}

		return $redirect_to;
	}, PHP_INT_MAX );

}

if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) == 'POST' ) {

	add_filter( 'lostpassword_errors', function ( $errors, $user_data ) {
		if ( $user_data || CRB_Globals::$reset_pwd_denied ) {
			return $errors;
		}

		cerber_log( CRB_EV_PRD, crb_get_user_login_field(), 0, 35 );

		if ( crb_get_settings( 'nopasshint' )
		     && ! crb_acl_is_allowed() ) {

			// Mimic the default redirection, see "case 'retrievepassword':" in wp-login.php
			$redirect_to = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'wp-login.php?checkemail=confirm';

			crb_safe_redirect( $redirect_to );
			exit;
		}

		return $errors;

	}, PHP_INT_MAX, 2 );
}
elseif ( crb_get_settings( 'nopasshint' )
         && ! crb_acl_is_allowed() ) {

	add_filter( 'wp_login_errors', function ( $errors, $user_data ) {

		if ( $errors->get_error_code() == 'confirm'
		     && $errors->get_error_data() == 'message' ) {

			if ( ! $msg = crb_get_settings( 'nopasshint_msg' ) ) {
				$msg = __( 'If we have found your account, we have sent the confirmation link to the email address on the account.', 'wp-cerber' );
			}

			$errors = new WP_Error( 'confirm', $msg, 'message' ); // Do not change!
		}

		return $errors;

	}, PHP_INT_MAX, 2 );
}

function cerber_parse_redir( $url, $user ) {
	if ( strpos( $url, '{{' ) ) {
		$url = preg_replace( '/{{user_id}}/', $user->ID, $url );
	}

	return $url;
}

/**
 * Generates a redirection URL for the given user.
 * It's based on the user role and requested user policy.
 *
 * @param int | WP_User $user
 * @param string $policy_id
 *
 * @return string Full redirection URL if any configured, empty string otherwise.
 */
function crb_redirect_by_policy( $user, string $policy_id ): string {

	if ( $user
	     && ! crb_is_wp_error( $user )
	     && ( $to = cerber_get_user_policy( $policy_id, $user ) ) ) {

		$force_redirect_to = cerber_parse_redir( $to, $user );

		if ( ! strpos( $force_redirect_to, '://' ) ) {
			$force_redirect_to = cerber_get_home_url() . '/' . ltrim( $force_redirect_to, '/' );
		}

		return $force_redirect_to;
	}

	return '';
}

function crb_force_current_user_logout( $status = null ) {
	global $current_user, $userdata, $user_ID;

	CRB_Globals::set_act_status( ( ! $status ) ? 26 : absint( $status ) );

	if ( $current_user instanceof WP_User ) {
		$uid = $current_user->ID;
	}
	else {
		$uid = get_current_user_id();
	}

	@wp_logout();

	CRB_2FA::delete_2fa( $uid );

	$current_user = null;
	$userdata = null;
	$user_ID = null;
}

/**
 * Evaluates registration restrictions based on security settings.
 * It handles user registration validation via WordPress hooks (e.g. 'registration_errors').
 *
 * This function checks multiple security layers: allowlists, limits, bot detection,
 * reCaptcha, prohibited usernames/emails, IP blocklists, and GeoIP rules.
 *
 * Warning: Strict type hinting is not allowed, parameters are passed via a WP hook.
 *
 * @param string $user_login The desired user login name.
 * @param string $user_email The user's email address.
 *
 * @return array{0: string, 1: string}|false Returns `false` if registration is allowed.
 * Returns a tuple `[error_code, error_message]` if prohibited.
 *
 * @code-type Security Validator
 */
function cerber_is_registration_prohibited( $user_login, $user_email = '' ) {

	$error_code = null;
	$error_msg = '';
	$custom_msg = '';
	$wp_cerber = get_wp_cerber();

	if ( crb_get_settings( 'regwhite' )
	     && ! crb_acl_is_allowed()
	     && lab_lab() ) {
		CRB_Globals::set_ctrl_setting( 'regwhite' );
		cerber_log( 54, '', 0, 37 );
		$error_code = 'ip_denied';
		if ( ! $custom_msg = crb_get_settings( 'regwhite_msg' ) ) {
			$error_msg = __( 'You are not allowed to register.', 'wp-cerber' );
		}
	}
    elseif ( crb_is_reg_limit_reached() ) {
		CRB_Globals::set_ctrl_setting( 'reglimit_num' );
		cerber_log( 54, '', 0, 17 );
		$error_code = 'ip_denied';
		$error_msg = apply_filters( 'cerber_msg_denied', __( 'You are not allowed to register.', 'wp-cerber' ), 'register' );
	}
    elseif ( cerber_is_bot( 'botsreg' ) ) {
		CRB_Globals::set_ctrl_setting( 'botsreg' );
		cerber_log( 54 );
		$error_code = 'bot_detected';
		$error_msg = apply_filters( 'cerber_msg_denied', __( 'You are not allowed to register.', 'wp-cerber' ), 'register' );
	}
    elseif ( ! $wp_cerber->reCaptchaValidate() ) {
		cerber_log( 54, '', 0, CRB_STS_532 );
		$error_code = 'incorrect_recaptcha';
		$error_msg = $wp_cerber->reCaptchaMsg( 'register' );
	}
    elseif ( crb_is_username_prohibited( $user_login ) ) {
		CRB_Globals::set_ctrl_setting( 'prohibited' );
		cerber_log( 54, '', 0, CRB_STS_30 );
		$error_code = 'prohibited_login';
		$error_msg = apply_filters( 'cerber_msg_prohibited', __( 'Username is not allowed. Please choose another one.', 'wp-cerber' ), 'register' );
	}
    elseif ( ! cerber_is_email_permited( $user_email ) ) {
		CRB_Globals::set_ctrl_setting( 'emrule' );
		cerber_log( 54, '', 0, 31 );
		$error_code = 'prohibited_email';
		if ( ! $custom_msg = crb_get_settings( 'emlist_msg' ) ) {
			$error_msg = apply_filters( 'cerber_msg_prohibited_email', __( 'Email address is not permitted. Please choose another one.', 'wp-cerber' ), 'register' );
		}
	}
    elseif ( ! cerber_is_ip_allowed() || lab_is_blocked( cerber_get_remote_ip() ) ) {
		cerber_log( 54 );
		$error_code = 'ip_denied';
		$error_msg = apply_filters( 'cerber_msg_denied', __( 'You are not allowed to register.', 'wp-cerber' ), 'register' );
	}
    elseif ( ! cerber_geo_allowed( 'geo_register' ) ) {
		cerber_log( 54, '', 0, 16 );
		$error_code = 'country_denied';
		$error_msg = apply_filters( 'cerber_msg_denied', __( 'You are not allowed to register.', 'wp-cerber' ), 'register' );
	}

	if ( $error_code ) {

		$final_msg = $custom_msg ?: ( '<strong>' . __( 'ERROR:', 'wp-cerber' ) . ' </strong>' . $error_msg );

		return array( $error_code, $final_msg );
	}

	return false;
}

/**
 * Restrict email addresses
 *
 * @param $email string
 *
 * @return bool
 */
function cerber_is_email_permited( $email ) {

	if ( ! $email ) {
		return true;
	}

	if ( ( ! $rule = crb_get_settings( 'emrule' ) )
	     || ( ! $list = (array) crb_get_settings( 'emlist' ) ) ) {
		return true;
	}

	if ( $rule == 1 ) {
		$ret = false;
	}
    elseif ( $rule == 2 ) {
		$ret = true;
	}
	else {
		return true;
	}

	$email = strtolower( $email );

	foreach ( $list as $item ) {
		if ( $item[0] == '/' && substr( $item, - 1 ) == '/' ) {
			$pattern = $item . 'i'; // we permit to specify any REGEX
			if ( @preg_match( $pattern, $email ) ) {
				return $ret;
			}
		}
        elseif ( false !== strpos( $item, '*' ) ) {
			$wildcard = '.+?';
			$pattern = '/^' . str_replace( array( '.', '*' ), array( '\.', $wildcard ), $item ) . '$/i';
			if ( @preg_match( $pattern, $email ) ) {
				return $ret;
			}
		}
        elseif ( $email === $item ) {
			return $ret;
		}
	}

	return ! $ret;
}

/**
 * Limit on user registrations per IP
 *
 * @return bool
 */
function crb_is_reg_limit_reached() {

	if ( ! lab_lab() ) {
		return false;
	}

	if ( ! crb_get_settings( 'reglimit_min' ) || ! crb_get_settings( 'reglimit_num' ) ) {
		return false;
	}

	if ( crb_acl_is_allowed() ) {
		return false;
	}

	$ip = cerber_get_remote_ip();
	$stamp = absint( time() - 60 * crb_get_settings( 'reglimit_min' ) );
	$count = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE ip = "' . $ip . '" AND activity = 2 AND stamp > ' . $stamp );
	if ( $count >= crb_get_settings( 'reglimit_num' ) ) {

		return true;
	}

	return false;
}

// The default WP registration form
add_filter( 'registration_errors', function ( $errors, $sanitized_user_login, $user_email ) {

	$result = cerber_is_registration_prohibited( $sanitized_user_login, $user_email );

	if ( $result ) {
		return new WP_Error( $result[0], $result[1] );
	}

	return $errors;
}, 10, 3 );

/**
 * Inserting users programmatically via wp_insert_user()
 *
 * @since 8.6.3.3
 */
add_filter( 'wp_pre_insert_user_data', function ( $data, $update, $user_id ) {
	/*if ( $update || is_admin() ) {
		return $data;
	}*/

	if ( ! $update && ! is_admin() ) {

		$user_login = crb_array_get( $data, 'user_login' );
		$user_email = crb_array_get( $data, 'user_email' );

		if ( cerber_is_registration_prohibited( $user_login, $user_email ) ) {
			return null;
		}
	}

	if ( $update ) {
		$old_user_data = get_userdata( $user_id );
		if ( $data['user_pass'] != $old_user_data->user_pass ) {
			crb_pass_reset( $old_user_data );
		}
	}

	return $data;
}, PHP_INT_MAX, 3 );

// Validation for MU and BuddyPress
add_filter( 'wpmu_validate_user_signup', function ( $signup_data ) {

	$sanitized_user_login = sanitize_user( $signup_data['user_name'], true );

	if ( $check = cerber_is_registration_prohibited( $sanitized_user_login, $signup_data['user_email'] ) ) {
		$signup_data['errors'] = new WP_Error( 'user_name', $check[1] );
	}

	return $signup_data;
}, PHP_INT_MAX );

// Filter out prohibited usernames
add_filter( 'illegal_user_logins', function ( $list ) {
	if ( ! is_admin_user_edit() ) {
		$list = (array) crb_get_settings( 'prohibited' );
	}

	return $list;
}, PHP_INT_MAX );

add_filter( 'option_users_can_register', function ( $value ) {
	//if ( ! cerber_is_allowed() || !cerber_geo_allowed( 'geo_register' )) {
	if ( ! cerber_is_ip_allowed() || crb_is_reg_limit_reached() ) {
		return false;
	}
	if ( crb_get_settings( 'regwhite' )
	     && ! crb_acl_is_allowed()
	     && lab_lab() ) {
		return false;
	}

	return $value;
}, PHP_INT_MAX );

// Comments (commenting) section ----------------------------------------------------------

if ( cerber_is_custom_comment() ) {
	add_filter( 'comment_form_defaults', function ( $defaults ) {
		$defaults['action'] = site_url( '/' . crb_get_compiled( 'custom_comm_slug' ) );

		return $defaults;
	} );
}

/**
 * Process comments submitted via the Custom comment URL
 *
 * @since 8.8
 */
function cerber_custom_comment_process() {
	if ( cerber_is_custom_comment() && CRB_Request::is_comment_sent() ) {
		require( ABSPATH . WP_COMMENT_SCRIPT ); // load the default wp-comments-post.php processor
		exit;
	}
}

/**
 * Is Custom comment URL is enabled?
 *
 * @return bool
 *
 * @since 8.8
 */
function cerber_is_custom_comment() {
	if ( crb_get_settings( 'customcomm' ) && cerber_is_permalink_enabled() ) {
		return true;
	}

	return false;
}


/**
 * If a comment must be marked as spam
 *
 */
add_filter( 'pre_comment_approved', function ( $approved, $commentdata ) {
	if ( 1 == crb_get_settings( 'spamcomm' ) && ! cerber_is_comment_allowed() ) {
		$approved = 'spam';
	}

	return $approved;
}, 10, 2 );

/**
 * If a comment must be denied
 *
 */
add_action( 'pre_comment_on_post', function ( $comment_post_ID ) {

	$deny = false;

	if ( 1 != crb_get_settings( 'spamcomm' ) && ! cerber_is_comment_allowed() ) {
		$deny = true;
	}
    elseif ( ! cerber_geo_allowed( 'geo_comment' ) ) {
		CRB_Globals::set_act_status( 16 );
		cerber_log( 19 );
		$deny = true;
	}

	if ( $deny ) {
		cerber_set_cookie( 'cerber_post_id', $comment_post_ID, time() + 60, '/' );
		$comments = get_comments( array( 'number' => '1', 'post_id' => $comment_post_ID ) );
		if ( $comments ) {
			$loc = get_comment_link( $comments[0]->comment_ID );
		}
		else {
			$loc = get_permalink( $comment_post_ID ) . '#cerber-recaptcha-msg';
		}

		crb_safe_redirect( $loc );
		exit;
	}

} );

/**
 * If submit comments via REST API is not allowed
 *
 */
add_filter( 'rest_allow_anonymous_comments', function ( $allowed, $request ) {

	if ( ! cerber_is_ip_allowed() ) {
		$allowed = false;
	}
	if ( ! cerber_geo_allowed( 'geo_comment' ) ) {
		cerber_log( 19 );
		CRB_Globals::set_act_status( 16 );
		$allowed = false;
	}
    elseif ( lab_is_blocked( cerber_get_remote_ip() ) ) {
		$allowed = false;
	}

	return $allowed;
}, 10, 2 );

/**
 * Check if a submitted comment is allowed
 *
 * @return bool
 */
function cerber_is_comment_allowed() {

	if ( is_admin() ) {
		return true;
	}

	$deny = false;
	$remain = 1;
	$mark = ( 1 == crb_get_settings( 'spamcomm' ) );

	if ( ! cerber_is_ip_allowed() ) {
		$deny = $mark ? CRB_EV_CMS : 19;
	}
    elseif ( cerber_is_bot( 'botscomm' ) ) {
		CRB_Globals::set_ctrl_setting( 'botscomm' );
		$deny = $mark ? CRB_EV_CMS : CRB_EV_SCD;
		//$remain = cerber_get_remain_count( array( 'act_list' => array( CRB_EV_CMS, CRB_EV_SCD ), 'allowed' => 3, 'period' => 60 ) );
	}
    elseif ( ! get_wp_cerber()->reCaptchaValidate( 'comment', true ) ) {
		$deny = $mark ? CRB_EV_CMS : CRB_EV_SCD;
	}
    elseif ( lab_is_blocked( cerber_get_remote_ip() ) ) {
		$deny = $mark ? CRB_EV_CMS : 19;
	}

	if ( $deny ) {
		cerber_log( $deny );
		$ret = false;
	}
	else {
		$ret = true;
	}

	/*if ( $remain < 1 ) {
		crb_apply_ip_lockout( null, 706, '', 1 );
	}*/

	return $ret;
}

/**
 * Showing reCAPTCHA widget.
 * Displaying an error message on the comment form for a human.
 *
 */
add_filter( 'comment_form_submit_field', function ( $value ) {
	global $post;

	if ( isset( $post->ID )
	     && cerber_get_cookie( 'cerber_post_id' ) == $post->ID ) {
		//echo '<div id="cerber-recaptcha-msg">' . __( 'ERROR:', 'wp-cerber' ) . ' ' . $wp_cerber->reCaptchaMsg( 'comment' ) . '</div>';
		echo '<div id="cerber-recaptcha-msg">' . __( 'ERROR: Sorry, human verification failed.', 'wp-cerber' ) . '</div>';
		$p = cerber_get_cookie_prefix();
		echo '<script type="text/javascript">document.cookie = "' . $p . 'cerber_post_id=0;path=/";</script>';
	}

	if ( ! crb_get_settings( 'recapcomauth' ) || ! is_user_logged_in() ) {
		get_wp_cerber()->reCaptcha( 'widget', 'recapcom' );
	}

	if ( cerber_is_custom_comment() ) {
		echo '<input type="hidden" name="' . crb_get_compiled( 'custom_comm_mark' ) . '" value="' . rand( 1, 100 ) . '">';
	}

	return $value;
} );


/*
	Replace default login/logout URL with Custom login page URL
*/
add_filter( 'site_url', 'cerber_login_logout', 9999, 4 );
add_filter( 'network_site_url', 'cerber_login_logout', 9999, 3 );
function cerber_login_logout( $url, $path, $scheme, $blog_id = 0 ) { // $blog_id only for 'site_url'

	if ( $login_path = crb_get_settings( 'loginpath' ) ) {
		$url = str_replace( WP_LOGIN_SCRIPT, $login_path . '/', $url );
	}

	return $url;
}

/*
	Replace default logout redirect URL with Custom login page URL
*/
add_filter( 'wp_redirect', 'cerber_login_redirect', 9999, 2 );
function cerber_login_redirect( $location, $status ) {

	if ( ( crb_get_settings( 'loginpath' ) )
	     && ( 0 === strpos( $location, WP_LOGIN_SCRIPT . '?' ) ) ) {
		$loc = explode( '?', $location );
		$location = cerber_get_custom_login_url() . '?' . $loc[1];

		CRB_Globals::$redirect_url = $location;
	}

	return $location;
}

add_action( 'init', function () {

	cerber_cookie_bad_proc();

	if ( crb_get_settings( 'adminphp' ) ) {
		if ( defined( 'CONCATENATE_SCRIPTS' ) ) {

			define( 'CONCATENATE_SCRIPTS_BY_CRB', false );

			if ( CONCATENATE_SCRIPTS ) {
				if ( is_admin() || nexus_is_valid_request() ) {
					CRB_Issues::register( 'conscripts', __( 'The PHP constant CONCATENATE_SCRIPTS is already defined somewhere else', 'wp-cerber' ), array( 'setting_id' => 'adminphp' ) );
				}
			}
		}
        elseif ( ! cerber_check_groove_x() ) {
			define( 'CONCATENATE_SCRIPTS', false );
			define( 'CONCATENATE_SCRIPTS_BY_CRB', true );
		}
	}

	if ( ! is_admin()
	     && ! cerber_is_wp_cron() ) {
		cerber_access_control();
		cerber_auth_access();
	}

	cerber_custom_comment_process();

	cerber_post_control();

	if ( is_admin() ) {
		cerber_load_admin_code();
	}

	crb_load_localization();

	// ====================================================

	if ( crb_get_settings( 'nologinlang' ) ) {
		add_filter( 'login_display_language_dropdown', '__return_false' );
	}

	if ( ! empty( $_POST['rememberme'] )
	     && ( crb_get_settings( 'no_rememberme' ) || crb_get_settings( 'auth_expire' ) ) ) {

		// See do_action( "login_form_{$action}" );
		add_action( 'login_form_login', function () {
			$_POST['rememberme'] = ''; // Ugly workaround
		}, 0 );

		if ( class_exists( 'WooCommerce' ) ) {
			add_filter( 'woocommerce_login_credentials', function ( $creds ) {
				$creds['rememberme'] = false;

				return $creds;
			} );
		}
	}

	if ( ( ! defined( 'CERBER_OLD_LP' ) || ! CERBER_OLD_LP ) // This constant is deprecated
	     && ! crb_get_settings( 'logindeferred' ) ) {
		cerber_show_login_page();
	}

	if ( class_exists( 'WooCommerce' )
	     && ( crb_get_settings( 'no_rememberme' ) || crb_get_settings( 'auth_expire' ) ) ) {
		add_action( 'wp_head', function () {
			?>
            <style>
                .woocommerce .woocommerce-form-login label.woocommerce-form-login__rememberme {
                    display: none;
                }
            </style>
			<?php
		} );
	}

}, 0 );

if ( ( defined( 'CERBER_OLD_LP' ) && CERBER_OLD_LP )
     || crb_get_settings( 'logindeferred' ) ) {
	add_action( 'init', 'cerber_show_login_page', 20 );
}

/**
 * Restrict access to some vital parts of WP
 *
 */
function cerber_access_control() {

	if ( crb_acl_is_allowed() ) {
		CRB_Globals::set_act_status( 500 );
		CRB_Globals::$req_status = 500;

		return;
	}

	$wp_cerber = get_wp_cerber();

	if ( $wp_cerber->isURIProhibited() ) {
		cerber_404_page();
	}

	$opt = crb_get_settings();

	// REST API
	if ( $wp_cerber->isDeny() ) {
		cerber_block_rest_api();
	}
    elseif ( cerber_is_rest_url() ) {
		$rest_allowed = true;

		if ( ! cerber_is_rest_permitted() ) {
			CRB_Globals::set_act_status( 520 );
			$rest_allowed = false;
		}

		if ( $rest_allowed
		     && ! cerber_geo_allowed( 'geo_restapi' ) ) {
			CRB_Globals::set_act_status( 16, 'geo_restapi' );
			$rest_allowed = false;
		}

		if ( ! $rest_allowed ) {
			cerber_block_rest_api();
		}
	}

	// Some XML-RPC stuff
	if ( $wp_cerber->isDeny() || ! empty( $opt['xmlrpc'] ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'pings_open', '__return_false' );
		add_filter( 'bloginfo_url', 'cerber_pingback_url', 10, 2 );
		remove_action( 'wp_head', 'rsd_link', 10 );
		remove_action( 'wp_head', 'wlwmanifest_link', 10 );
	}

	// Feeds
	if ( $wp_cerber->isDeny() || ! empty( $opt['nofeeds'] ) ) {
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );

		remove_action( 'do_feed_rdf', 'do_feed_rdf', 10 );
		remove_action( 'do_feed_rss', 'do_feed_rss', 10 );
		remove_action( 'do_feed_rss2', 'do_feed_rss2', 10 );
		remove_action( 'do_feed_atom', 'do_feed_atom', 10 );
		remove_action( 'do_pings', 'do_all_pings', 10 );

		add_action( 'do_feed_rdf', 'cerber_404_page', 1 );
		add_action( 'do_feed_rss', 'cerber_404_page', 1 );
		add_action( 'do_feed_rss2', 'cerber_404_page', 1 );
		add_action( 'do_feed_atom', 'cerber_404_page', 1 );
		add_action( 'do_feed_rss2_comments', 'cerber_404_page', 1 );
		add_action( 'do_feed_atom_comments', 'cerber_404_page', 1 );
	}

}

function cerber_auth_access() {

	$opt = crb_get_settings();

	if ( ! empty( $opt['authonlyacl'] )
	     && crb_acl_is_allowed() ) {
		return;
	}

	if ( ! empty( $opt['authonly'] )
	     && ! is_user_logged_in()
	     && cerber_auth_required( $opt['authonlyredir'] ) ) {

		if ( $opt['authonlyredir'] ) {
			$redirect = ( CRB_Request::is_script( '/wp-admin/options.php' ) && wp_get_referer() ) ? wp_get_referer() : set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			crb_redirect( add_query_arg( 'redirect_to', $redirect, $opt['authonlyredir'] ) );

			exit;
		}

		auth_redirect();
	}
}

/**
 * Anti-spam & anti-bot engine
 *
 */
function cerber_post_control() {

	if ( ! cerber_is_http_post()
	     || ( crb_get_settings( 'botsipwhite' ) && crb_acl_is_allowed() ) ) {
		return;
	}

	if ( ! cerber_antibot_enabled( 'botsany' ) && ! cerber_get_geo_rules( 'geo_submit' ) ) {
		return;
	}

	// Exceptions -----------------------------------------------------------------------

	if ( cerber_is_antibot_exception() ) {
		return;
	}

	// Let's make the checks

	$deny = false;

	if ( ! cerber_is_ip_allowed( null, CRB_CNTX_SAFE ) ) {
		$deny = true;
		cerber_log( 18 );
	}
    elseif ( cerber_is_bot( 'botsany' ) ) {
		$deny = true;
		CRB_Globals::set_ctrl_setting( 'botsany' );
		cerber_log( CRB_EV_SFD );
	}
    elseif ( ! cerber_geo_allowed( 'geo_submit' ) ) {
		$deny = true;
		CRB_Globals::set_act_status( 16, 'geo_submit' );
		cerber_log( 18 );
	}
    elseif ( lab_is_blocked( null, true ) ) {
		$deny = true;
		CRB_Globals::set_act_status( 18 );
		cerber_log( 18 );
	}

	if ( $deny ) {
		cerber_forbidden_page();
	}

}

/**
 * Exception for POST request control
 *
 * @return bool
 */
function cerber_is_antibot_exception() {

	if ( cerber_is_wp_cron() ) {
		return true;
	}

	// Admin || AJAX requests by unauthorized users
	if ( is_admin() ) {
		if ( cerber_is_wp_ajax() ) {
			if ( is_user_logged_in() ) {
				return true;
			}
			if ( class_exists( 'WooCommerce' ) ) {
				// Background processes launcher? P.S. wc_privacy_cleanup
				if ( crb_arrays_similar( $_GET, array(
						'nonce'  => 'crb_is_alphanumeric',
						'action' => 'crb_is_alphanumeric'
					) )
				     && ! preg_grep( '/[^\d]/', array_keys( $_POST ) ) ) { // If other than numeric keys in array
					return true;
				}
			}
		}
		else {
			return true;
		}
	}

	// Standard WordPress Comments
	if ( CRB_Request::is_comment_sent() ) {
		return true;
	}

	// XML-RPC
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		return true;
	}

	// Trackback
	if ( is_trackback() ) {
		return true;
	}

	// Login page
	if ( cerber_is_login_request() ) {
		return true;
	}

	// REST API (except Contact Form 7 submission)
	if ( cerber_is_rest_url() ) {
		if ( false === strpos( $_SERVER['REQUEST_URI'], 'contact-form-7' ) ) {
			return true;
		}
	}

	if ( class_exists( 'WooCommerce' ) ) {
		if ( cerber_is_permalink_enabled() ) {
			if ( CRB_Request::is_full_url_start_with( cerber_get_home_url() . '/wc-api/' ) ) {
				return true;
			}
		}
        elseif ( ! empty( $_GET['wc-api'] ) ) {
			if ( cerber_check_remote_domain( array( '*.paypal.com', '*.stripe.com' ) ) ) {
				return true;
			}
		}
	}

	// Upgrading WP, see update-core.php
	if ( count( $_GET ) == 1
	     && count( $_POST ) == 0
	     && ( $p = cerber_get_get( 'step' ) )
	     && ( $p == 'upgrade_db' )
	     && substr( cerber_script_filename(), - 21 ) == '/wp-admin/upgrade.php' ) {
		return true;
	}

	// Cloud Scanner
	if ( cerber_is_cloud_request() ) {
		return true;
	}

	if ( nexus_is_valid_request() ) {
		return true;
	}

	return false;
}

/**
 * What anti-bot mode to use
 *
 * @return int 1 = Cookies + Fields, 2 = Cookies only
 */
function cerber_antibot_mode() {

	if ( current_user_can( 'manage_options' ) ) {
		return 2;
	}

	if ( cerber_is_wp_ajax() ) {
		if ( crb_get_settings( 'botssafe' ) ) {
			return 2;
		}
		if ( ! empty( $_POST['action'] ) ) {
			if ( $_POST['action'] == 'heartbeat' ) { // WP heartbeat
				//$nonce_state = wp_verify_nonce( $_POST['_nonce'], 'heartbeat-nonce' );
				return 2;
			}
		}

	}

	if ( cerber_get_uri_script() ) {
		return 1;
	}

	// Theme customizer by WP
	if ( isset( $_GET['customize_changeset_uuid'] )
	     && isset( $_GET['customize_theme'] )
	     && isset( $_POST['customize_changeset_uuid'] )
	     && isset( $_POST['wp_customize'] ) ) {
		if ( current_user_can( 'customize' ) ) {
			return 2;
		}
	}

	// Check for third-party exceptions

	if ( class_exists( 'WooCommerce' ) ) {

		if ( ! empty( $_GET['wc-ajax'] )
		     && count( $_GET ) == 1
		     && CRB_Request::is_root_request()
		     && in_array( $_GET['wc-ajax'], array(
				'get_refreshed_fragments',
				'apply_coupon',
				'remove_coupon',
				'update_shipping_method',
				'get_cart_totals',
				'update_order_review',
				'add_to_cart',
				'remove_from_cart',
				'checkout',
				'get_variation',
				'get_customer_location',
			) ) ) {

			return 2;
		}

		if ( cerber_is_permalink_enabled() ) {
			//if ( function_exists( 'wc_get_page_id' ) && 0 === strpos( cerber_get_site_root() . cerber_purify_uri(), get_permalink( wc_get_page_id( 'checkout' ) ) ) ) {
			if ( function_exists( 'wc_get_page_id' ) && CRB_Request::is_full_url_start_with( get_permalink( wc_get_page_id( 'checkout' ) ) ) ) {
				return 2;
			}
		}
		else {
			if ( ! empty( $_GET['order-received'] ) && ! empty( $_GET['key'] ) ) {
				return 2;
			}
		}
	}

	if ( class_exists( 'GFForms' ) ) {
		if ( count( $_GET ) == 2 &&
		     ! empty( $_GET['gf_page'] ) &&
		     ! empty( $_GET['id'] ) &&
		     is_user_logged_in()
		) {

			return 2;
		}
	}

	return 1;
}

/*
 * Disable pingback URL (hide from HEAD)
 */
function cerber_pingback_url( $output, $show ) {
	if ( $show == 'pingback_url' ) {
		$output = '';
	}

	return $output;
}

/**
 * Disable REST API
 *
 */
function cerber_block_rest_api() {
	// OLD WP
	add_filter( 'json_enabled', '__return_false' );
	add_filter( 'json_jsonp_enabled', '__return_false' );
	// @since WP 4.7
	add_filter( 'rest_jsonp_enabled', '__return_false' );
	// Links
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	// Default REST API hooks from default-filters.php
	remove_action( 'init', 'rest_api_init' );
	remove_action( 'rest_api_init', 'rest_api_default_filters', 10 );
	remove_action( 'rest_api_init', 'register_initial_settings', 10 );
	remove_action( 'rest_api_init', 'create_initial_rest_routes', 99 );
	remove_action( 'parse_request', 'rest_api_loaded' );

	if ( cerber_is_rest_url() ) {
		cerber_log( 70 );
		cerber_forbidden_page();
	}
}

/*
 * Redirection control: standard admin/login redirections
 *
 */
add_filter( 'wp_redirect', function ( $location ) {
	global $current_user;

	if ( ( ! $current_user || $current_user->ID == 0 )
	     && crb_block_admin_redirections() ) {
		$rdr = explode( 'redirect_to=', $location );
		if ( isset( $rdr[1] ) ) {
			$redirect_to = urldecode( $rdr[1] ); // a normal
			$redirect_to = urldecode( $redirect_to ); // @since 8.1 - may be twice encoded to bypass
			if ( false !== strpos( $redirect_to, '/wp-admin/' ) ) {
				cerber_404_page();
			}
		}
	}

	return $location;
}, 0 );

function crb_block_admin_redirections() {
	if ( crb_get_settings( 'noredirect' ) && ! cerber_check_groove_x() ) {
		return true;
	}

	return false;
}

// Stop user enumeration ---------------------------------------------------------

if ( crb_get_settings( 'stopenum' ) ) {
	add_action( 'template_redirect', function () {

		if ( ! $a = crb_array_get( $_GET, 'author' ) ) {
			if ( ! $a = crb_array_get( $_POST, 'author' ) ) { // @since 8.1
				return;
			}
		}

		if ( preg_match( '/\d/', $a ) && ! is_admin() ) {

			CRB_Globals::set_ctrl_setting( 'stopenum' );

			cerber_404_page();
		}

	}, 0 );
}

if ( crb_get_settings( 'stopenum_oembed' ) ) {
	add_filter( 'oembed_response_data', function ( $data, $post, $width, $height ) {
		unset( $data['author_url'] );
		unset( $data['author_name'] );

		return $data;
	}, PHP_INT_MAX, 4 );
}


if ( crb_get_settings( 'stopenum_sitemap' ) ) {
	add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
		if ( $name == 'users' ) {
			$provider = false;
		}

		return $provider;
	}, PHP_INT_MAX, 2 );
}

if ( crb_get_settings( 'nouserpages_bylogin' ) ) {

	add_filter( 'author_link', function ( $link, $author_id ) {
		// TODO: Needs to be controlled by website admin to avoid phishing/scam links in case of user generated content
		/*if ( $author_id
		     && ( $d = get_userdata( $author_id ) )
		     && $d->user_url ) {

			return $d->user_url;
		}*/

		return cerber_get_home_url();

	}, PHP_INT_MAX, 2 );

	// Requests by user details are not allowed.
	// Unfortunately, in some WordPress environments, this approach doesn't work

	/*add_filter( 'query_vars', function ( $query_vars ) {

		if ( $key = array_search( 'author', $query_vars ) ) {
			unset( $query_vars[ $key ] );
		}

		if ( $key = array_search( 'author_name', $query_vars ) ) {
			unset( $query_vars[ $key ] );
		}

		return $query_vars;
	}, PHP_INT_MAX );*/

	add_action( 'template_redirect', function () {

		if ( is_author()
		     || ( ( cerber_get_get( 'author_name' )
		            || cerber_get_post( 'author_name' ) )
		          && ! is_admin() ) ) {
			cerber_404_page();
		}

	}, 0 );
}

/**
 * Can WP Cerber shows any login form error message?
 *
 * @return bool
 */
function cerber_can_show_messages() {

	if ( 'login' != ( $_REQUEST['action'] ?? 'login' ) ) {
		return false;
	}

	if ( 'confirm' == ( $_REQUEST['checkemail'] ?? '' ) ) {
		return false;
	}

	return true;
}


// Cookies ---------------------------------------------------------------------------------

add_action( 'auth_cookie_valid', 'cerber_cookie_one', 10, 2 );
function cerber_cookie_one( $cookie_elements = null, $user = null ) {
	if ( ! $user ) {
		$user = wp_get_current_user();
	}

	CRB_Globals::$user_id = $user->ID;

	// Mark user with Cerber Groove
	// TODO: remove filter, add IP address and user agent
	$expire = time() + apply_filters( 'auth_cookie_expiration', 14 * 24 * 3600, $user->ID, true ) + ( 24 * 3600 );
	cerber_set_groove( $expire );
}

/*
	Mark switched user with Cerber Groove
	@since 1.6
*/
add_action( 'set_logged_in_cookie', 'cerber_cookie2', 10, 5 );
function cerber_cookie2( $logged_in_cookie, $expire, $expiration, $user_id, $logged_in ) {
	cerber_set_groove( $expire );
}

/*
	Monitoring BAD auth cookies
*/
add_action( 'auth_cookie_bad_username', 'cerber_cookie_bad' );
add_action( 'auth_cookie_bad_hash', 'cerber_cookie_bad' );
add_action( 'auth_cookie_bad_session_token', 'cerber_cookie_bad' );
function cerber_cookie_bad( $cookie_elements ) {
	global $cerber_auth_cookie_bad;

	$cerber_auth_cookie_bad = array( 1, $cookie_elements['username'] );
}

/**
 * Bad (invalid) auth cookie handler
 *
 * @since 8.9.4
 *
 */
function cerber_cookie_bad_proc() {
	global $cerber_auth_cookie_bad;

	if ( empty( $cerber_auth_cookie_bad ) ) {
		return;
	}

	if ( ! headers_sent() ) {
		wp_clear_auth_cookie();
		CRB_Globals::set_act_status( 40 );
	}
	else {
		CRB_Globals::set_act_status( 39 );
	}

	cerber_login_failed( $cerber_auth_cookie_bad[1] );
}

/**
 * Is bot detection engine enabled for a given location
 *
 * @param $location string|array An identifier of a place where we are checking for a bot
 *
 * @return bool true if enabled
 */
function cerber_antibot_enabled( $location ) {

	if ( crb_get_settings( 'botsipwhite' ) && crb_acl_is_allowed() ) {
		return false;
	}

	if ( crb_get_settings( 'botsnoauth' ) && is_user_logged_in() ) {
		return false;
	}

	if ( is_array( $location ) ) {
		foreach ( $location as $loc ) {
			if ( crb_get_settings( $loc ) ) {
				return true;
			}
		}
	}
	else {
		if ( crb_get_settings( $location ) ) {
			return true;
		}
	}

	return false;
}

/**
 *
 * @param $location string|array Location (setting)
 *
 */
function cerber_antibot_code( $location ) {

	if ( defined( 'CERBER_DISABLE_SPAM_FILTER' )
	     && is_singular() ) {
		$list = explode( ',', (string) CERBER_DISABLE_SPAM_FILTER );
		$pid = (int) get_queried_object_id();
		if ( in_array( $pid, $list ) ) {
			return;
		}
	}

	if ( ! cerber_antibot_enabled( $location ) ) {
		return;
	}

	$values = cerber_antibot_gene();

	if ( empty( $values ) || ! is_array( $values ) ) {
		return;
	}

	?>
    <script type="text/javascript">
        jQuery(function ($) {

            for (let i = 0; i < document.forms.length; ++i) {
                let form = document.forms[i];
				<?php
				foreach ( $values[0] as $value ) {
					echo 'if ($(form).attr("method") != "get") { $(form).append(\'<input type="hidden" name="' . $value[0] . '" value="' . $value[1] . '" />\'); }' . "\n";
				}
				?>
            }

            $(document).on('submit', 'form', function () {
				<?php
				foreach ( $values[0] as $value ) {
					echo 'if ($(this).attr("method") != "get") { $(this).append(\'<input type="hidden" name="' . $value[0] . '" value="' . $value[1] . '" />\'); }' . "\n";
				}
				?>
                return true;
            });

            jQuery.ajaxSetup({
                beforeSend: function (e, data) {

                    if (data.type !== 'POST') return;

                    if (typeof data.data === 'object' && data.data !== null) {
						<?php
						foreach ( $values[0] as $value ) {
							echo 'data.data.append("' . $value[0] . '", "' . $value[1] . '");' . "\n";
						}
						?>
                    }
                    else {
                        data.data = data.data + '<?php
							foreach ( $values[0] as $value ) {
								echo '&' . $value[0] . '=' . $value[1];
							}
							?>';
                    }
                }
            });

        });
    </script>
	<?php

}

/**
 * Generates and saves antibot markers
 *
 * @return array|bool
 */
function cerber_antibot_gene( $recreate = false ) {

	if ( ! crb_get_settings( 'botsany' )
	     && ! crb_get_settings( 'botscomm' )
	     && ! crb_get_settings( 'botsreg' ) ) {
		return false;
	}

	$ret = array();

	if ( ! $recreate ) {
		$ret = cerber_get_site_option( 'cerber-antibot' );
	}

	if ( $recreate || ! $ret ) {

		$ret = array();

		$max = rand( 2, 4 );
		for ( $i = 1; $i <= $max; $i ++ ) {
			$string1 = crb_random_string( 6, 16, false, true, '_-' );
			$string2 = crb_random_string( 6, 16, true, true, '.@*_[]' );
			$ret[0][] = array( $string1, $string2 );
		}

		$max = rand( 2, 4 );
		for ( $i = 1; $i <= $max; $i ++ ) {
			$string1 = crb_random_string( 6, 16, false, true, '_-' );
			$string2 = crb_random_string( 6, 16, true, true, '.@*_[]' );
			$ret[1][] = array( $string1, $string2 );
		}

		update_site_option( 'cerber-antibot', $ret );
	}

	return $ret;
}

/**
 * Is a POST request (a form) submitted by a bot?
 *
 * @param $location string An identifier of a place where we are checking for a bot
 *
 * @return bool
 */
function cerber_is_bot( $location ) {
	static $ret = null;

	$remote_ip = cerber_get_remote_ip();

	if ( crb_is_loopback_ip( $remote_ip ) ) {
		return false;
	}

	if ( isset( $ret ) ) {
		return $ret;
	}

	if ( ! $location || ! cerber_is_http_post() || cerber_is_wp_cron() ) {
		$ret = false;

		return $ret;
	}

	// Admin || AJAX requests by unauthorized users
	if ( is_admin() ) {
		if ( cerber_is_wp_ajax() ) {
			if ( is_user_logged_in() ) {
				$ret = false;
			}
            elseif ( ! empty( $_POST['action'] ) ) {
				if ( $_POST['action'] == 'heartbeat' ) { // WP heartbeat
					//$nonce_state = wp_verify_nonce( $_POST['_nonce'], 'heartbeat-nonce' );
					$ret = false;
				}
			}
		}
		else {
			$ret = false;
		}
	}

	if ( $ret !== null ) {
		return $ret;
	}

	if ( ! cerber_antibot_enabled( $location ) ) {
		$ret = false;

		return $ret;
	}

	// Exceptions by URI

	if ( ( $list = crb_get_settings( 'botswhite' ) )
	     && is_array( $list ) ) {

		$uri = '/' . trim( $_SERVER['REQUEST_URI'], '/' );
		$uri_slash = $uri . ( ( empty( $_GET ) ) ? '/' : '' ); // @since 8.8

		foreach ( $list as $item ) {

			if ( ! is_string( $item ) || $item === '' ) {
				continue;
			}

			if ( $item[0] == '{' && substr( $item, - 1 ) == '}' ) {
				$regex_body = substr( $item, 1, - 1 );
				if ( ! $regex_body ) {
					continue;
				}

				$regex_body = str_replace( '~', '\~', $regex_body );
				$pattern = '~' . $regex_body . '~i'; // ~ in use since 9.6.3.1

				if ( @preg_match( $pattern, $uri ) ) {
					CRB_Globals::$req_status = 502;

					$ret = false;

					return $ret;
				}
			}
			else {
				$cmp = ( substr( $item, - 1 ) == '/' ) ? $uri_slash : $uri; // @since 8.8 Someone may specify trailing slash
				if ( false !== strpos( $cmp, $item ) ) {
					CRB_Globals::$req_status = 502;

					$ret = false;

					return $ret;
				}
			}
		}
	}

	// Exceptions by HTTP header

	if ( $header_list = crb_get_settings( 'botswhite_header' ) ) {
		foreach ( (array) $header_list as $header ) {
			if ( crb_request_header_matches( $header ) ) {
				CRB_Globals::$req_status = 505;

				$ret = false;

				return $ret;
			}
		}
	}

	$antibot = cerber_antibot_gene();

	$ret = false;

	if ( ! empty( $antibot ) ) {

		$mode = cerber_antibot_mode();

		if ( $mode == 1 ) {
			foreach ( $antibot[0] as $fields ) {
				if ( empty( $_POST[ $fields[0] ] ) || $_POST[ $fields[0] ] != $fields[1] ) {
					$ret = true;
					break;
				}
			}
		}

		if ( ! $ret ) {
			foreach ( $antibot[1] as $fields ) {
				if ( cerber_get_cookie( $fields[0] ) != $fields[1] ) {
					$ret = true;
					break;
				}
			}
		}

		if ( $ret ) {
			CRB_Globals::set_bot_status( CRB_STS_11 );
			lab_save_push( $remote_ip, 333 );
		}
	}

	return $ret;
}

function cerber_geo_allowed( $rule_id = '', $user = null ) {

	if ( ! $rule_id || cerber_is_wp_cron() || ! lab_lab() ) {
		return true;
	}

	if ( crb_acl_is_allowed() ) {
		return true;
	}

	if ( $user ) {

		if ( $user instanceof WP_User ) {
			$roles = $user->roles;
		}
		else {
			$user = get_userdata( $user );
			$roles = $user->roles;
		}

		if ( $roles ) {
			foreach ( $roles as $role ) {
				$ret = cerber_check_geo( $rule_id . '_' . $role );
				if ( $ret !== 0 ) { // This rule exists and country was successfully checked
					return $ret;
				}
			}
		}
	}

	$ret = cerber_check_geo( $rule_id );

	if ( $ret === 0 ) {
		return true;
	}

	return $ret;
}

function cerber_check_geo( $rule_id ) {
	if ( ! $rule = cerber_get_geo_rules( $rule_id ) ) {
		return 0;
	}

	if ( ! $country = lab_get_country( cerber_get_remote_ip(), false ) ) {
		return 0;
	}

	if ( in_array( $country, $rule['list'] ) ) {
		if ( $rule['type'] == 'W' ) {
			return true;
		}

		return false;
	}

	if ( $rule['type'] == 'W' ) {
		return false;
	}

	return true;
}

/**
 * Retrieve and return GEO rule(s) from the DB
 *
 * @param string $rule_id ID of the rule
 *
 * @return bool|array False if no rule configured
 */
function cerber_get_geo_rules( $rule_id = '' ) {
	static $rules;
	global $wpdb;

	if ( ! isset( $rules ) || cerber_is_http_post() ) {
		if ( is_multisite() ) {
			$geo = cerber_db_get_var( 'SELECT meta_value FROM ' . $wpdb->sitemeta . ' WHERE meta_key = "' . CERBER_GEO_RULES . '"' );
		}
		else {
			$geo = cerber_db_get_var( 'SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = "' . CERBER_GEO_RULES . '"' );
		}

		if ( $geo ) {
			$rules = crb_unserialize( $geo );
		}
		else {
			$rules = false;

			return false;
		}
	}

	if ( $rule_id ) {
		$ret = ( ! empty( $rules[ $rule_id ] ) ) ? $rules[ $rule_id ] : false;
	}
	else {
		$ret = $rules;
	}

	return $ret;
}

/**
 * Set user session expiration
 *
 */
add_filter( 'auth_cookie_expiration', function ( $expire, $user_id, $remember ) {

	if ( $time = cerber_get_user_policy( 'auth_expire', $user_id, 'auth_expire' ) ) {
		$expire = 60 * $time;
	}
    elseif ( crb_get_settings( 'no_rememberme' ) ) {
		$expire = 2 * DAY_IN_SECONDS; // See wp_set_auth_cookie()
	}

	return $expire;
}, 10, 3 );

// add_action( 'wp_logout', function(){});
add_action( 'clear_auth_cookie', function () {

	$uid = get_current_user_id();
	if ( $uid ) {
		CRB_Globals::$user_id = $uid;
		cerber_log( 6, '', $uid, CRB_Globals::$act_status );
		CRB_2FA::delete_2fa( $uid );
	}

	cerber_set_cookie( 'cerber_nexus_id', 0, time(), '/' );
} );

// Lost passwords  --------------------------------------------------------------------

add_action( 'retrieve_password', function ( $user_login ) {
	CRB_Globals::$retrieve_password = true;
} );

add_filter( 'allow_password_reset', 'crb_check_pwd_reset', PHP_INT_MAX, 2 );

/**
 * @param bool|WP_Error $allow
 * @param int $user_id
 *
 * @return bool|WP_Error
 *
 * @since 8.9.5.3
 */
function crb_check_pwd_reset( $allow, $user_id ) {
	if ( ! $allow || ( $allow instanceof WP_Error && $allow->has_errors() ) ) {
		return $allow;
	}

	if ( $user_id && $user_data = crb_get_userdata( $user_id ) ) {

		$p = false;

		if ( ( $b = crb_is_user_blocked( $user_data->ID ) )
		     || $p = crb_is_username_prohibited( $user_data->user_login ) ) {

			if ( ! $allow instanceof WP_Error ) {
				$allow = new WP_Error;
			}

			$allow->add( 'cerber_pwd_reset_not_allowed', __( 'Sorry, password reset is not allowed for this user.', 'wp-cerber' ) );

			if ( CRB_Globals::$retrieve_password ) {
				if ( $p ) {
					CRB_Globals::set_ctrl_setting( 'prohibited' );
				}

				cerber_log( CRB_EV_PRD, crb_get_user_login_field( $user_data->user_login ), 0, ( $b ) ? CRB_STS_29 : CRB_STS_30 );
			}

			CRB_Globals::$reset_pwd_denied = true;
		}
	}

	return $allow;
}

/**
 * @return bool True if the WooCommerce reset form has been submitted
 */
function crb_is_woo_reset() {
	return ( isset( $_POST['wc_reset_password'], $_POST['user_login'] )
	         && class_exists( 'WooCommerce' ) );
}

add_action( 'login_head', 'cerber_login_head' );
function cerber_login_head() {

	if ( ! cerber_is_ip_allowed() )  :
		?>
        <style>
            form#loginform,
            form#lostpasswordform,
            #login p#nav {
                display: none;
            }
        </style>
	<?php
	endif;

	if ( crb_get_settings( 'no_rememberme' ) || crb_get_settings( 'auth_expire' ) )  :
		?>
        <style>
            p.forgetmenot {
                display: none;
            }
        </style>
	<?php
	endif;

	$wp_cerber = get_wp_cerber();

	$wp_cerber->reCaptcha( 'style' );
}

add_filter( 'shake_error_codes', 'cerber_login_failure_shake' );
function cerber_login_failure_shake( $shake_error_codes ) {
	$shake_error_codes[] = 'cerber_auth_error';

	return $shake_error_codes;
}

add_filter( 'wp_login_errors', 'cerber_login_form_notices', PHP_INT_MAX );
/**
 * Add error messages to show them on the WordPress login form
 *
 * @param WP_Error $errors
 *
 * @return WP_Error
 *
 * @since 9.6.5.3
 */
function cerber_login_form_notices( $errors ) {
	if ( ! is_wp_error( $errors ) ) {
		$errors = new WP_Error();
	}

	if ( CRB_Globals::$login_form_errors ) {
		foreach ( CRB_Globals::$login_form_errors as $id => $msg ) {
			$errors->add( 'cerber_login_form_' . $id, $msg );
		}
	}

	if ( crb_get_settings( 'authonly' )
	     && $msg = (string) crb_get_settings( 'authonlymsg' ) ) {

		$errors->add( 'cerber_login_form_auth_required', $msg );
	}

	if ( $msg = cerber_get_auth_notice() ) {
		$errors->add( 'cerber_login_form_notice', $msg );
	}

	return $errors;
}

/**
 * Builds a user notice to display above the login form.
 *
 * On POST, only the informative message about remaining login attempts is returned.
 *
 * On GET, messages are returned in the following priority:
 * 1) IP restrictions, 2) remaining login attempts, 3) optional custom message (authonly).
 *
 * Returns an empty string if no message should be displayed.
 *
 * @return string User message or an empty string.
 *
 * @see cerber_restrict_auth() Generates auth-related error messages.
 *
 * @since 9.6.5.3
 */
function cerber_get_auth_notice(): string {

	if ( ! cerber_can_show_messages() ) {
		return '';
	}

	$wp_cerber = get_wp_cerber();

	if ( cerber_is_http_post() ) {

		// POST: only show the informative "remaining attempts" message here, others are generated in cerber_restrict_auth()

		return $wp_cerber->get_remaining_login_attempts_message();
	}

	// The login form is being displayed, no POST auth

	if ( ! cerber_is_ip_allowed() ) {
		return $wp_cerber->get_auth_restriction_message();
	}

	if ( $msg = $wp_cerber->get_remaining_login_attempts_message() ) {
		return $msg;
	}

	return '';
}

add_action( 'login_form_lostpassword', 'cerber_lost_pwd_form_checks' );
/**
 * Checks before rendering the WordPress lost password (password reset) form
 *
 * @return void
 *
 * @since 9.6.5.3
 */
function cerber_lost_pwd_form_checks() {

	if ( ! cerber_is_ip_allowed() ) {
		add_filter( 'login_message', function ( $message ) {
			return ''; // Remove the default reset password message
		} );

		CRB_Globals::$reset_pwd_msg = __( 'You are not allowed to proceed. Ask your administrator for assistance.', 'wp-cerber' );
	}

	$wp_cerber = get_wp_cerber();

	if ( ! $wp_cerber->reCaptchaValidate() ) {

		// Abort password reset
		$_POST['user_login'] = null;

		cerber_log( CRB_EV_PRD, crb_get_user_login_field() );

		CRB_Globals::$reset_pwd_denied = true;
		CRB_Globals::$reset_pwd_msg = '<strong>' . __( 'Error:', 'wp-cerber' ) . ' </strong>' . $wp_cerber->reCaptchaMsg( 'lostpassword' );
	}
}

add_action( 'lost_password', 'cerber_lost_pwd_form_errors', PHP_INT_MAX );
/**
 * Add error messages to the WordPress lost password (password reset) form
 *
 * @param WP_Error $errors
 *
 * @return void
 *
 * @since 9.6.5.3
 */
function cerber_lost_pwd_form_errors( $errors ) {

	if ( CRB_Globals::$reset_pwd_msg
	     && is_wp_error( $errors ) ) {
		$errors->add( 'cerber_lost_pwd', CRB_Globals::$reset_pwd_msg );
	}
}

add_action( 'woocommerce_before_customer_login_form', 'cerber_wc_login_errors' );
/**
 * Check for possible error messages and display them on the standard WooCommerce login form
 *
 * @return void
 *
 * @since 9.6.5.5
 */
function cerber_wc_login_errors() {

	$errors = new WP_Error();
	cerber_login_form_notices( $errors );

	if ( is_wp_error( $errors ) && $errors->has_errors() ) {
		foreach ( $errors->get_error_messages() as $message ) {
			wc_add_notice( $message, 'error' );
		}
	}
}

add_action( 'woocommerce_before_lost_password_form', 'cerber_wc_lost_pwd_errors' );
/**
 * Check for possible error messages and display them on the standard WooCommerce lost password form
 *
 * @return void
 *
 * @since 9.6.5.5
 */
function cerber_wc_lost_pwd_errors() {

	cerber_lost_pwd_form_checks();

	if ( $msg = CRB_Globals::$reset_pwd_msg ) {
		wc_add_notice( $msg, 'error' );
	}
}

add_action( 'lostpassword_post', function ( $errors ) {

	// Lost password form has been submitted

	add_action( 'retrieve_password_key', function ( $login ) {

		cerber_log( CRB_EV_PRS, $login );

	} );

} );

add_action( 'password_reset', 'crb_pass_reset' );
add_action( 'crb_after_reset', 'crb_pass_reset', 10, 2 );

function crb_pass_reset( $user, $user_id = null ) {

	if ( ! $user && $user_id ) {
		$user = get_user_by( 'id', $user_id );
	}

	if ( ! $user ) {
		return;
	}

	cerber_log( 20, $user->user_login, $user->ID );

	// Do not log 'clear_auth_cookie' event (logout/login sequence) that occurs after password reset

	CRB_Activity::set_ignore( CRB_EV_LIN );
	CRB_Activity::set_ignore( 6 );
}

// Fires in wp_insert_user()
add_action( 'user_register', function ( $user_id ) { // @since 5.6
	$cid = get_current_user_id();
	if ( $user = get_user_by( 'ID', $user_id ) ) {
		if ( $cid && $cid != $user_id ) {
			$ac = 1;
		}
		else {
			$ac = 2;
		}
		cerber_log( $ac, $user->user_login, $user_id );
		crb_log_user_ip( $user_id, $cid );
	}
} );

// Fires after a new user has been created in WP dashboard.
add_action( 'edit_user_created_user', function ( $user_id, $notify = null ) {
	if ( $user_id && $user = get_user_by( 'ID', $user_id ) ) {
		cerber_log( 1, $user->user_login, $user_id );
		crb_log_user_ip( $user_id );
	}
}, 10, 2 );

// Log IP address of user registration independently
function crb_log_user_ip( $user_id, $by_user = null ) {
	if ( ! $user_id ) {
		return;
	}
	if ( ! $by_user ) {
		$by_user = get_current_user_id();
	}
	add_user_meta( $user_id, '_crb_reg_', array( 'IP' => cerber_get_remote_ip(), 'user' => $by_user ) );
}

if ( is_multisite() ) {
	add_action( 'wpmu_delete_user', 'crb_user_delete' );
}
else {
	add_action( 'delete_user', 'crb_user_delete' );
}
/**
 * @param $user_id
 *
 * @since 8.6.3.4
 */
function crb_user_delete( $user_id ) {
	global $__deleted_user;
	if ( ! $__deleted_user = get_user_by( 'ID', $user_id ) ) {
		return;
	}
	add_action( 'deleted_user', function ( $user_id ) {
		global $__deleted_user;
		cerber_log( 3, '', $user_id );
		$user_data = array( 'display_name' => $__deleted_user->display_name, 'roles' => $__deleted_user->roles );
		cerber_update_set( 'user_deleted', $user_data, $user_id );
	} );
}

// Lockouts routines ---------------------------------------------------------------------

/**
 * Lock out IP address if it is an alien IP only (browser does not have valid Cerber groove)
 *
 * @param $ip string IP address to block
 * @param integer $reason_id ID of reason of blocking
 * @param string $details Reason of blocking, additional textual info
 * @param null $duration Duration of blocking, minutes
 *
 * @return bool
 */
function crb_apply_soft_ip_lockout( $ip, $reason_id, $details = '', $duration = null ) {
	if ( cerber_check_groove() ) {
		return false;
	}

	return crb_apply_ip_lockout( $ip, $reason_id, $details, $duration );
}

/**
 * Locks out the given IP address
 *
 * @param $ip_address string IP address to block
 * @param integer $reason_id ID of reason of blocking
 * @param string $details Reason of blocking, additional textual info
 * @param int $duration Duration of blocking, minutes
 *
 * @return bool
 */
function crb_apply_ip_lockout( $ip_address, $reason_id, $details = '', $duration = null ) {

	if ( cerber_is_cloud_request() ) {
		return false;
	}

	//$wp_cerber = get_wp_cerber();
	//$wp_cerber->setProcessed();

	if ( empty( $ip_address ) || ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
		$ip_address = cerber_get_remote_ip();
	}

	if ( cerber_acl_check( $ip_address ) ) {
		return false;
	}

	$reason_id = absint( $reason_id );
	$insert = true;

	if ( $row = crb_get_lockout( $ip_address ) ) {
		if ( $row->reason_id == $reason_id ) {
			return false;
		}

		$insert = false;
	}

	if ( crb_get_settings( 'subnet' ) ) {
		$ip = cerber_get_subnet_ipv4( $ip_address );
		$activity = CRB_EV_NEL;
	}
	else {
		$ip = $ip_address;
		$activity = CRB_EV_IPL;
	}

	lab_save_push( $ip_address, $reason_id, $details );

	$reason = crb_get_lockout_reason( $reason_id );

	if ( $details ) {
		$reason .= ': ' . $details;
	}

	$reason_escaped = cerber_db_real_escape( $reason );

	if ( ! $duration ) {
		$duration = cerber_calc_duration( $ip );
	}

	$until = time() + 60 * $duration;

	$session_id = get_wp_cerber()->getRequestID();

	if ( $insert ) {
		$result = cerber_db_insert( CRB_LOCKOUT_TABLE,
			array(
				'ip'          => $ip,
				'block_until' => $until,
				'reason'      => $reason_escaped,
				'reason_id'   => $reason_id,
				'session_id'  => $session_id,
			),
			1062
		);
	}
	else {
		$result = cerber_db_update( CRB_LOCKOUT_TABLE,
			array( 'ip' => $ip ),
			array(
				'block_until' => $until,
				'reason'      => $reason_escaped,
				'reason_id'   => $reason_id,
				'session_id'  => $session_id,
			)
		);
	}

	if ( $result ) {
		$result = true;
		CRB_Globals::$blocked = $reason_id;

		if ( $insert ) {
			cerber_log( $activity, '', 0, 0, $ip_address );
		}

		crb_event_handler( 'ip_event', array(
			'e_type'    => 'locked',
			'ip'        => $ip_address,
			'reason_id' => $reason_id,
			'reason'    => $reason,
			'update'    => ! $insert
		) );

		if ( $insert ) {
			do_action( 'cerber_ip_locked', array( 'IP' => $ip_address, 'reason' => $reason ) );
		}
	}
	else {
		$result = false;
		cerber_db_error_log();
	}

	if ( $insert ) {
		crb_send_lockout_notification( $ip_address );
	}

	return $result;
}

/**
 *
 * Check if an IP address is currently locked out. With C subnet also.
 *
 * @param string $ip an IP address
 *
 * @return object|false object if IP is locked out, false otherwise
 */
function cerber_block_check( $ip = '' ) {
	static $cache = array();

	if ( ! isset( $cache[ $ip ] ) ) {
		$cache[ $ip ] = crb_get_lockout( $ip );
	}

	return $cache[ $ip ];
}

/**
 * Return the lockout row for an IP if it is locked out. With C subnet also.
 *
 * @param string $ip an IP address
 *
 * @return object|false object if IP is locked out, false otherwise
 */
function crb_get_lockout( $ip = '' ) {

	if ( ! $ip ) {
		$ip = cerber_get_remote_ip();
	}

	if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return false;
	}

	$where = ' WHERE ip = "' . $ip . '"';

	if ( cerber_is_ipv4( $ip ) ) {
		$subnet = cerber_get_subnet_ipv4( $ip );
		$where .= ' OR ip = "' . $subnet . '"';
	}

	if ( $ret = cerber_db_get_row( 'SELECT * FROM ' . CRB_LOCKOUT_TABLE . $where, MYSQL_FETCH_OBJECT ) ) {
		return $ret;
	}

	return false;
}

/**
 * Returns the number of currently locked out IPs
 *
 * @return int
 *
 * @since 3.0
 */
function crb_get_locked_out_num(): int {
	return (int) cerber_db_get_var( 'SELECT count(ip) FROM ' . CRB_LOCKOUT_TABLE );
}

function crb_delete_expired_lockouts() {
	static $done;
	if ( $done ) {
		return;
	}

	$time = time();

	if ( $list = cerber_db_get_col( 'SELECT ip FROM ' . CRB_LOCKOUT_TABLE . ' WHERE block_until < ' . $time ) ) {
		$result = cerber_db_query( 'DELETE FROM ' . CRB_LOCKOUT_TABLE . ' WHERE block_until < ' . $time );
		crb_event_handler( 'ip_event', array(
			'e_type' => 'unlocked',
			'ip'     => $list,
			'result' => $result
		) );
	}

	$done = true;
}

/**
 * Calculate duration for a lockout of an IP address based on settings
 *
 * @param string $ip
 *
 * @return integer Duration in minutes
 */
function cerber_calc_duration( $ip ) {
	$range = time() - crb_get_settings( 'aglast' ) * 3600;
	$lockouts = cerber_db_get_var( 'SELECT COUNT(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE ip = "' . $ip . '" AND activity IN (' . CRB_EV_IPL . ',' . CRB_EV_NEL . ') AND stamp > ' . $range );

	if ( $lockouts >= crb_get_settings( 'aglocks' ) ) {
		$duration = crb_get_settings( 'agperiod' ) * 60;
	}
	else {
		$duration = crb_get_settings( 'lockout' );
	}

	$duration = absint( $duration );

	if ( $duration < 1 ) {
		$duration = 1;
	}

	return $duration;
}

/**
 * Calculation of remaining attempts
 *
 * @param array $args
 *
 * $args['ip'] string an IP address
 * $args['acl'] bool if true will check the White IP ACL first
 * $args['bot'] int|array Value of the ac_bot column
 * $args['status'] int|array Value of the ac_status column
 * $args['act_list'] int|array Activity IDs
 * $args['allowed'] int Allowed events within $period
 * $args['period'] int Period for counting events in minutes
 *
 * @return int Allowed attempts for present moment
 */
function cerber_get_remain_count( array $args ) {

	$ip = $args['ip'] ?? '';
	$check_acl = $args['acl'] ?? true;
	$activity = $args['act_list'] ?? array( CRB_EV_LFL, 152, 51, 52 );
	$bot = $args['bot'] ?? array();
	$status = $args['status'] ?? array();
	$allowed = $args['allowed'] ?? 0;
	$period = $args['period'] ?? 0;

	if ( ! $ip ) {
		$ip = cerber_get_remote_ip();
	}
	else {
		if ( ! $ip = filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return 0;
		}
	}

	if ( ! $allowed ) {
		$allowed = absint( crb_get_settings( 'attempts' ) );
	}

	if ( $check_acl && crb_acl_is_allowed( $ip ) ) {
		return $allowed; // whitelist = infinity attempts
	}

	if ( ! $period ) {
		$period = absint( crb_get_settings( 'period' ) );
	}

	$range = time() - $period * 60;

	$where = array();

	// Bot status

	if ( ! is_array( $bot ) ) {
		$bot = array( $bot );
	}

	if ( $bot = array_filter( array_map( 'crb_absint', $bot ) ) ) {
		$where[] = 'ac_bot IN (' . implode( ',', $bot ) . ')';
	}

	// Status

	if ( ! is_array( $status ) ) {
		$status = array( $status );
	}

	if ( $status = array_filter( array_map( 'crb_absint', $status ) ) ) {
		$where[] = 'ac_status IN (' . implode( ',', $status ) . ')';
	}

	// Activity

	if ( ! is_array( $activity ) ) {
		$activity = array( $activity );
	}

	if ( $activity = array_filter( array_map( 'crb_absint', $activity ) ) ) {
		$where[] = 'activity IN (' . implode( ',', $activity ) . ')';
	}

	if ( empty( $where ) ) {
		return $allowed;
	}

	$where = 'ip = "' . $ip . '" AND ' . implode( ' AND ', $where );

	$attempts = cerber_db_get_var( 'SELECT COUNT(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE ' . $where . ' AND stamp > ' . $range );

	if ( ! $attempts ) {
		return $allowed;
	}
	else {
		$ret = $allowed - $attempts;
	}

	return ( $ret < 0 ) ? 0 : $ret;
}

/**
 * Is a given IP is allowed to do restricted things?
 * Here Cerber makes its decision.
 *
 * @param $ip string IP address
 * @param $context int What context?
 *
 * @return bool
 */
function cerber_is_ip_allowed( $ip = '', $context = null ) {

	if ( ! $ip ) {
		$ip = cerber_get_remote_ip();
	}
    elseif ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return false;
	}

	$tag = cerber_acl_check( $ip );
	if ( $tag == 'W' ) {
		return true;
	}
	if ( $tag == 'B' ) {
		CRB_Globals::set_act_status( 14 );

		return false;
	}

	if ( $b = crb_get_lockout( $ip ) ) {
		if ( ! in_array( $b->reason_id, crb_context_get_allowed( $context ) ) ) {
			CRB_Globals::set_act_status( 13 );

			return false;
		}
	}

	if ( $context != CRB_CNTX_NEXUS && cerber_is_citadel() ) {
		CRB_Globals::set_act_status( 19 );

		return false;
	}

	if ( lab_is_blocked( $ip, false ) ) {
		CRB_Globals::set_act_status( 15 );

		return false;
	}

	return true;
}

/**
 * @param int $context_id
 *
 * @return array
 */
function crb_context_get_allowed( $context_id ) {
	$sets = array( CRB_CNTX_SAFE => array( 701, 703, 704, 721 ) );

	if ( $context_id && isset( $sets[ $context_id ] ) ) {
		return $sets[ $context_id ];
	}

	return array();
}

/**
 * Check if a given username is not permitted to log in or register
 *
 * @param string $username
 *
 * @return bool true if username is prohibited
 */
function crb_is_username_prohibited( $username ): bool {
	if ( ! $username ) {
		return false;
	}

	if ( $list = (array) crb_get_settings( 'prohibited' ) ) {

		$username_lower = strtolower( $username ); // since 'prohibited' gets lower case when settings are saved

		foreach ( $list as $item ) {
			if ( mb_substr( $item, 0, 1 ) == '/' && mb_substr( $item, - 1 ) == '/' ) {
				$pattern = trim( $item, '/' );
				if ( @mb_ereg_match( $pattern, $username, 'i' ) ) {
					return true;
				}
			}
            elseif ( $username_lower == $item ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Returns Status ID for the given IP address in the context of the given activity
 *
 * @param string $ip IP address
 * @param int $activity Activity ID
 *
 * @return int Status ID
 */
function cerber_get_status( string $ip, int $activity = 0 ): int {

	if ( ! empty( CRB_Globals::$act_status ) ) {
		return absint( CRB_Globals::$act_status );
	}

	if ( cerber_block_check( $ip ) ) {
		return 13;
	}

	if ( $tag = cerber_acl_check( $ip ) ) {
		if ( $tag == 'W' ) {
			if ( in_array( $activity, array( 1, 2, CRB_EV_LIN, 20, CRB_EV_PRS ) ) ) {
				return 500;
			}
			if ( in_array( $activity, array( 72, 73, 75, 76 ) ) ) {
				return 511;
			}
			if ( $activity == 74 ) {
				return 512;
			}

			return 0;
		}
        elseif ( $tag == 'B' ) {
			return 14;
		}
	}

	if ( cerber_is_citadel() ) {
		return 12;
	}

	if ( lab_is_blocked( $ip, false ) ) {
		return 15;
	}

	return 0;
}

// Access lists (ACL) routines ------------------------------------------------

/**
 * Is an IP manually allowlisted by admin?
 *
 * @param string $ip IP address
 *
 * @return bool True if the IP address is in the Allowed IP Acess List
 */
function crb_acl_is_allowed( $ip = null ) {

	if ( cerber_acl_check( $ip, 'W' ) ) {
		return true;
	}

	return false;
}

/**
 * Is an IP manually blocklisted by admin?
 *
 * @param string $ip IP address
 *
 * @return bool True if the IP address is in the Blocked IP Acess List
 */
function crb_acl_is_blocked( $ip = '' ) {
	$tag = cerber_acl_check( $ip );
	if ( $tag === 'W' ) {
		return false;
	}
    elseif ( $tag === 'B' ) {
		return true;
	}

	return false;
}

/**
 * Checks an IP address against the configured ACL.
 *
 * When a tag is specified, checks only the corresponding ACL.
 * When the tag is empty, checks both ACLs and returns the tag of the matching range.
 *
 * An empty IP address is replaced with the current remote IP address.
 *
 * Pass $row with other than the default (false) value to retrieve the full matching ACL record.
 * Full-row retrieval must not be used on hot paths.
 *
 * @param string $ip IP address, or an empty string to use the current remote IP.
 * @param string $tag ACL tag to check: 'W', 'B', or an empty string to check both.
 * @param int $acl_slice ACL slice ID. Zero (default) selects the global ACLs. Unsigned.
 * @param array<string, string>|false|null $row By-reference row selector and output.
 *
 * @return bool|string Returns true when checking only the Allowed ACL (tag 'W')
 *                      or only the Blocked ACL (tag 'B') and the IP is in the
 *                      list. When no tag is specified, returns 'W' if the IP is
 *                      in the Allowed ACL or 'B' if the IP is in the Blocked ACL.
 *                      Returns false if no matching range is found or the tag is
 *                      invalid.
 *
 */
function cerber_acl_check( $ip = null, string $tag = '', int $acl_slice = 0, &$row = false ) {
	static $cache, $row_cache;

	if ( ! $ip ) {
		$ip = cerber_get_remote_ip();
	}

	if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		$row = false;

		return false;
	}

	$acl_slice = absint( $acl_slice );

	$key = cerber_get_id_ip( $ip ) . $tag . $acl_slice;

	if ( isset( $cache[ $key ] ) ) {
		$row = $row_cache[ $key ];

		return $cache[ $key ];
	}

	if ( cerber_is_ipv6( $ip ) ) {

		// IPv6 logic

		$ret = cerber_ipv6_acl_check( $ip, $tag, $acl_slice, $row );
		$cache[ $key ] = $ret;
		$row = $row ? (object) $row : false;
		$row_cache[ $key ] = $row;

		return $ret;
	}

	// IPv4 logic

	$long = ip2long( $ip );
	$acl_slice = absint( $acl_slice );

	// Reject an unsupported tag before touching the database.
	if ( $tag && $tag !== 'W' && $tag !== 'B' ) {
		$row_cache[ $key ] = false;
		$cache[ $key ] = false;
		$row = false;

		return false;
	}

	$db_result = warp_get_db();

	if ( $db_result->has_errors() ) {

		// A connection failure is treated the same as "no match"

		$row_cache[ $key ] = false;
		$cache[ $key ] = false;
		$row = false;

		return false;
	}

	/** @var CRB_Database $db */
	$db = $db_result->get_results();

	if ( $tag ) {
		$qb = $db->table( CERBER_ACL_TABLE );
		$qb->where( 'acl_slice', '=', $acl_slice );
		$qb->where( 'ver6', '=', 0 );
		$qb->where( 'ip_long_begin', '<=', $long );
		$qb->where( 'ip_long_end', '>=', $long );
		$qb->where( 'tag', '=', $tag );

		$query_result = $qb->get_row( CRB_Database::FETCH_OBJECTS );

		if ( ! $query_result->has_errors() && $row = $query_result->get_results( false ) ) {
			$ret = true;
		}
		else {
			$ret = false;
			$row = false;
		}

		$row_cache[ $key ] = $row;
		$cache[ $key ] = $ret;

		return $ret;
	}

	// We use two queries because an IP address can overlap its network range, and the Allowed ACL has precedence.
	$qb = $db->table( CERBER_ACL_TABLE );
	$qb->where( 'acl_slice', '=', $acl_slice );
	$qb->where( 'ver6', '=', 0 );
	$qb->where( 'ip_long_begin', '<=', $long );
	$qb->where( 'ip_long_end', '>=', $long );
	$qb->where( 'tag', '=', 'W' );

	$query_result = $qb->get_row( CRB_Database::FETCH_OBJECTS );

	if ( ! $query_result->has_errors() && $row = $query_result->get_results( false ) ) {
		$row_cache[ $key ] = $row;
		$cache[ $key ] = $row->tag;

		return $row->tag;
	}

	$qb = $db->table( CERBER_ACL_TABLE );
	$qb->where( 'acl_slice', '=', $acl_slice );
	$qb->where( 'ver6', '=', 0 );
	$qb->where( 'ip_long_begin', '<=', $long );
	$qb->where( 'ip_long_end', '>=', $long );
	$qb->where( 'tag', '=', 'B' );

	$query_result = $qb->get_row( CRB_Database::FETCH_OBJECTS );

	if ( ! $query_result->has_errors() && $row = $query_result->get_results( false ) ) {
		$row_cache[ $key ] = $row;
		$cache[ $key ] = $row->tag;

		return $row->tag;
	}

	$row = false;
	$row_cache[ $key ] = false;
	$cache[ $key ] = false;

	return false;
}

/**
 * Checks an IPv6 address against the configured ACL.
 *
 * When a tag is specified, checks only the corresponding ACL.
 * When the tag is empty, checks both ACLs and returns the tag of the matching range.
 *
 * An empty IP address is replaced with the current remote IP address.
 *
 * Pass $row with other than the default (false) value to retrieve the full matching ACL record.
 * Full-row retrieval must not be used on hot paths.
 *
 * @param string $ip IPv6 address, or an empty string to use the current remote IP.
 * @param string $tag ACL tag to check: 'W', 'B', or an empty string to check both.
 * @param int $acl_slice ACL slice ID. Zero (default) selects the global ACLs.
 * @param array<string, string>|false|null $row By-reference row selector and output.
 *
 * @return bool|string Returns true when checking only the Allowed ACL (tag 'W')
 *                      or only the Blocked ACL (tag 'B') and the IP is in the
 *                      list. When no tag is specified, returns 'W' if the IP is
 *                      in the Allowed ACL or 'B' if the IP is in the Blocked ACL.
 *                      Returns false if no matching range is found or the tag is
 *                      invalid.
 *
 * @internal
 */
function cerber_ipv6_acl_check( $ip, $tag = '', $acl_slice = 0, &$row = false ) {

	if ( ! $ip ) {
		$ip = cerber_get_remote_ip();
	}

	$return_row = ( $row !== false );
	$row = [];

	list ( $ipv6_high, $ipv6_middle, $ipv6_low ) = crb_ipv6_to_int_triplet( $ip );

	$acl_slice = absint( $acl_slice );

	// Reject an unsupported tag before touching the database.
	if ( $tag && $tag != 'W' && $tag != 'B' ) {
		return false;
	}

	// A connection failure is treated the same as "no match", matching the
	// legacy cerber_db_get_results() contract of collapsing to an empty result.
	$db_result = warp_get_db();

	if ( $db_result->has_errors() ) {
		return false;
	}

	/** @var CRB_Database $db */
	$db = $db_result->get_results();

	// Shared range predicate; the tag filter and column list are added below.
	$qb = $db->table( CERBER_ACL_TABLE );
	$qb->where( 'acl_slice', '=', $acl_slice );
	$qb->where( 'ver6', '=', 1 );
	$qb->where( 'ip_long_begin', '<=', $ipv6_high );
	$qb->where( 'ip_long_end', '>=', $ipv6_high );

	if ( $tag ) {
		$qb->where( 'tag', '=', $tag );

		// Skip the unused columns when the caller does not need the full ACL row.
		if ( ! $return_row ) {
			$qb->select( 'ip_long_begin', 'ip_long_end', 'v6range' );
		}
	}
    elseif ( ! $return_row ) {
		// Same lean projection for the no-tag branch, plus the tag column the matcher needs.
		$qb->select( 'ip_long_begin', 'ip_long_end', 'v6range', 'tag' );
	}

	$query_result = $qb->get_query_results();

	if ( $query_result->has_errors() ) {
		return false;
	}

	$range_rows = $query_result->get_results( array() );

	if ( ! $range_rows ) {
		return false;
	}

	// A tag was requested: match against a single ACL list and report membership.
	if ( $tag ) {
		if ( ! crb_ipv6_is_in_range_list( $ipv6_high, $ipv6_middle, $ipv6_low, $range_rows, $key ) ) {
			return false;
		}

		if ( $return_row ) {
			$row = crb_array_get( $range_rows, $key );
		}

		return true;
	}

	// No tag was requested: resolve whichever list, Allowed or Blocked, matches.
	if ( ! $tag = crb_ipv6_get_tag( $ipv6_high, $ipv6_middle, $ipv6_low, $range_rows, $key ) ) {
		return false;
	}

	if ( $return_row ) {
		$row = crb_array_get( $range_rows, $key );
	}

	return $tag;
}

/**
 * Checks whether an IPv6 address falls within the given IPv6 range, both boundaries included.
 *
 * The range must originate from cerber_parse_ip_range(), which pre-splits the boundary addresses
 * into integer parts via crb_ipv6_prepare(). IPv4 addresses and IPv4 ranges are not supported
 * and produce meaningless comparisons, so the caller must dispatch by address family.
 *
 * The address is not validated here. An invalid string collapses to the same integer parts as
 * the :: address and therefore matches a range beginning at ::, so the caller must validate it.
 *
 * @param string $ip Valid IPv6 address.
 * @param array{begin:int, end:int, IPV6range:string} $range IPv6 range. Keys begin and end hold the leading integer part of the boundary addresses as returned by crb_ipv6_to_int_triplet(). Key IPV6range holds their remaining parts in "begin1#begin2#end1#end2" format. Any other key produced by cerber_parse_ip_range() is ignored.
 *
 * @return bool True if the address falls within the inclusive range.
 *
 * @version Sequoia
 */
function crb_ipv6_is_in_range( string $ip, array $range ): bool {
	list( $ipv6_high, $ipv6_middle, $ipv6_low ) = crb_ipv6_to_int_triplet( $ip );

	return crb_ipv6_chunks_are_in_range(
		$ipv6_high,
		$ipv6_middle,
		$ipv6_low,
		$range['begin'],
		$range['end'],
		$range['IPV6range']
	);
}

/**
 * Checks whether IPv6 address parts match any prepared ACL range.
 *
 * Each range row must contain the leading parts in ip_long_begin and
 * ip_long_end, and the remaining boundary parts in v6range. The rows are
 * expected to originate from cerber_ipv6_acl_check().
 *
 * When a matching range is found, $key receives its array key.
 *
 * @param int $ipv6_high Leading 60-bit part of the candidate IPv6 address.
 * @param int $ipv6_middle Middle 60-bit part of the candidate IPv6 address.
 * @param int $ipv6_low Trailing 8-bit part of the candidate IPv6 address.
 * @param array<int|string, array{ip_long_begin:string,ip_long_end:string,v6range:string}> $range_rows Prepared IPv6 ACL range rows.
 * @param int|string|null $key Receives the matching row key, or null when no range matches.
 *
 * @return bool True if the candidate matches any range.
 *
 * @version Sequoia
 */
function crb_ipv6_is_in_range_list(
	int $ipv6_high,
	int $ipv6_middle,
	int $ipv6_low,
	array $range_rows,
	&$key = null
): bool {
	$key = null;

	foreach ( $range_rows as $range_key => $range_row ) {
		if ( ! crb_ipv6_chunks_are_in_range(
			$ipv6_high,
			$ipv6_middle,
			$ipv6_low,
			(int) $range_row['ip_long_begin'],
			(int) $range_row['ip_long_end'],
			(string) $range_row['v6range']
		) ) {
			continue;
		}

		$key = $range_key;

		return true;
	}

	return false;
}

/**
 * Determines whether IPv6 address parts fall within an inclusive IPv6 range.
 *
 * The candidate address and range boundaries must be split by crb_ipv6_to_int_triplet().
 * The v6range value must contain the remaining boundary parts in the
 * "begin1#begin2#end1#end2" format produced by crb_ipv6_prepare().
 *
 * The comparison is lexicographical across all three parts. The second and
 * third parts are checked only when the candidate shares the leading part
 * with the corresponding range boundary.
 *
 * @param int $ipv6_high Leading 60-bit part of the candidate IPv6 address.
 * @param int $ipv6_middle Middle 60-bit part of the candidate IPv6 address.
 * @param int $ipv6_low Trailing 8-bit part of the candidate IPv6 address.
 * @param int $begin_d0 Leading 60-bit part of the lower range boundary.
 * @param int $end_d0 Leading 60-bit part of the upper range boundary.
 * @param string $v6range Remaining boundary parts in "begin1#begin2#end1#end2" format.
 *
 * @return bool True if the candidate parts fall within the inclusive range.
 *
 * @since 9.9.1
 *
 * @version Sequoia
 */
function crb_ipv6_chunks_are_in_range(
	int $ipv6_high,
	int $ipv6_middle,
	int $ipv6_low,
	int $begin_d0,
	int $end_d0,
	string $v6range
): bool {

	// Reject an invalid leading-part range.
	if ( $begin_d0 > $end_d0 ) {
		return false;
	}

	// Reject candidates outside the leading-part boundaries.
	if ( $ipv6_high < $begin_d0 || $ipv6_high > $end_d0 ) {
		return false;
	}

	// Parse and validate the remaining range parts.
	$range_chunks = explode( '#', $v6range );

	if ( count( $range_chunks ) !== 4 ) {
		return false;
	}

	$begin_d1 = (int) $range_chunks[0];
	$begin_d2 = (int) $range_chunks[1];
	$end_d1 = (int) $range_chunks[2];
	$end_d2 = (int) $range_chunks[3];

	// Ensure that a range within one leading part is ordered correctly.
	if ( $begin_d0 === $end_d0
	     && ! crb_compare_numbers( $end_d1, $end_d2, $begin_d1, $begin_d2 ) ) {
		return false;
	}

	// Apply the lower boundary only within its leading part.
	if ( $ipv6_high === $begin_d0
	     && ! crb_compare_numbers( $ipv6_middle, $ipv6_low, $begin_d1, $begin_d2 ) ) {
		return false;
	}

	// Apply the upper boundary only within its leading part.
	if ( $ipv6_high === $end_d0
	     && ! crb_compare_numbers( $end_d1, $end_d2, $ipv6_middle, $ipv6_low ) ) {
		return false;
	}

	return true;
}

/**
 * Returns the ACL tag for an IPv6 address range match.
 *
 * Allowed ACL entries have priority over blocked entries. If multiple blocked
 * ranges match, the first matching blocked row is retained unless a matching
 * allowed row is found later.
 *
 * When a matching range is found, $key receives the corresponding row key.
 *
 * @param int $ipv6_high Leading 60-bit part of the candidate IPv6 address.
 * @param int $ipv6_middle Middle 60-bit part of the candidate IPv6 address.
 * @param int $ipv6_low Trailing 8-bit part of the candidate IPv6 address.
 * @param array<int|string, array{ip_long_begin:int|string,ip_long_end:int|string,v6range:string,tag:string}> $range_rows IPv6 ACL rows.
 * @param int|string|null $key Receives the matching row key, or null when no range matches.
 *
 * @return false|string False when no range matches, 'B' for a blocked match (Blocked ACL), or 'W' for an allowed match (Allowed ACL).
 *
 * @version Sequoia
 */
function crb_ipv6_get_tag(
	int $ipv6_high,
	int $ipv6_middle,
	int $ipv6_low,
	array $range_rows,
	&$key = null
) {
	$blocked_key = null;
	$key = null;

	foreach ( $range_rows as $range_key => $range_row ) {
		$tag = (string) $range_row['tag'];

		if ( $tag !== 'W' && $tag !== 'B' ) {
			continue;
		}

		if ( ! crb_ipv6_chunks_are_in_range(
			$ipv6_high,
			$ipv6_middle,
			$ipv6_low,
			(int) $range_row['ip_long_begin'],
			(int) $range_row['ip_long_end'],
			(string) $range_row['v6range']
		) ) {
			continue;
		}

		// An allowed entry takes precedence over every blocked match.
		if ( $tag === 'W' ) {
			$key = $range_key;

			return 'W';
		}

		// Preserve the first blocked match while searching for an allowed one.
		if ( $blocked_key === null ) {
			$blocked_key = $range_key;
		}
	}

	if ( $blocked_key === null ) {
		return false;
	}

	$key = $blocked_key;

	return 'B';
}

/**
 * Returns true if the number $a1.$a2 is bigger than or equal the number $b1.$b2
 *
 * @param $a1 integer
 * @param $a2 integer
 * @param $b1 integer
 * @param $b2 integer
 *
 * @return bool
 */
function crb_compare_numbers( $a1, $a2, $b1, $b2 ) {
	if ( $a1 > $b1 ) {
		return true;
	}

	if ( $a1 < $b1 ) {
		return false;
	}

	if ( $a2 >= $b2 ) {
		return true;
	}

	return false;
}

/**
 * Converts an IPv6 address into an integer triplet for ordered 64-bit PHP and MySQL comparison.
 *
 * The 128-bit network-order representation is divided into two 60-bit integers and one 8-bit integer.
 * The three integers together form one ordered value and must be compared lexicographically,
 * from most significant to least significant. Do not compare them as independent numeric ranges.
 *
 * @param string $ip Valid IPv6 address.
 *
 * @return array{0:int,1:int,2:int} Integer IPv6 triplet in most-significant-first order.
 *
 * @version Sequoia
 */
function crb_ipv6_to_int_triplet( $ip ): array {
	$hex = bin2hex( inet_pton( $ip ) );

	return array(
		hexdec( substr( $hex, 0, 15 ) ),
		hexdec( substr( $hex, 15, 15 ) ),
		hexdec( substr( $hex, 30, 2 ) )
	);
}

/**
 * Build an IPv6 range as a fixed internal representation.
 *
 * @param string $begin Valid IPv6 address, range start.
 * @param string $end Valid IPv6 address, range end.
 *
 * @return array{0:int,1:int,2:string} Fixed internal representation:
 *                 [0] begin high 60-bit integer,
 *                 [1] end high 60-bit integer,
 *                 [2] '#'-joined tail in order: begin_middle, begin_low, end_middle, end_low.
 *
 * @version Sequoia
 */
function crb_ipv6_prepare( $begin, $end ) {
	list( $begin_high, $begin_middle, $begin_low ) = crb_ipv6_to_int_triplet( $begin );
	list( $end_high, $end_middle, $end_low ) = crb_ipv6_to_int_triplet( $end );

	return array(
		$begin_high,
		$end_high,
		$begin_middle . '#' . $begin_low . '#' . $end_middle . '#' . $end_low,
	);
}

/**
 * Write an authentication failure entry to the fail2ban-consumed log.
 *
 * Uses CERBER_FAIL_LOG (file) when defined, otherwise the syslog fallback
 * (facility LOG_AUTH, overridable via CERBER_LOG_FACILITY). The file timestamp
 * is rendered in the system timezone, not via current_time(), because
 * WordPress forces the PHP runtime to UTC and fail2ban expects local time;
 * override the zone with CERBER_LOG_TIMEZONE. Errors are suppressed so logging
 * never interrupts authentication.
 *
 * @param string $user_login Login name from the failed attempt.
 * @param string $ip Source IP of the attempt.
 *
 * @return void
 *
 */
function cerber_file_log( $user_login, $ip ) {

	// Sanitize client-controlled server variables before writing to logs to
	// prevent log-forging via injected newlines or separator characters.
	$server_name = crb_sanitize_log_hostname( $_SERVER['SERVER_NAME'] ?? '' );
	$http_host = crb_sanitize_log_hostname( $_SERVER['HTTP_HOST'] ?? '' );

	if ( defined( 'CERBER_FAIL_LOG' ) ) {

		if ( $log = @fopen( CERBER_FAIL_LOG, 'a' ) ) {
			$pid = absint( @posix_getpid() );
			@fwrite( $log, cerber_local_log_timestamp() . $server_name . ' Cerber(' . $http_host . ')[' . $pid . ']: Authentication failure for ' . $user_login . ' from ' . $ip . "\n" );
			@fclose( $log );
		}
	}
    elseif ( function_exists( 'syslog' ) ) {
		@openlog( 'Cerber(' . $http_host . ')', LOG_NDELAY | LOG_PID, defined( 'CERBER_LOG_FACILITY' ) ? CERBER_LOG_FACILITY : LOG_AUTH );
		@syslog( LOG_NOTICE, 'Authentication failure for ' . $user_login . ' from ' . $ip );
		@closelog();
	}
}

/**
 * Resolve the timezone name to use for fail2ban-compatible log timestamps.
 *
 * fail2ban matches timestamps against the system local time, while WordPress
 * forces the PHP default timezone to UTC at runtime. We therefore resolve the
 * system zone independently, preferring the source that best reflects what
 * fail2ban itself uses.
 *
 * @return string A timezone identifier (e.g. 'Europe/Madrid') or 'UTC'.
 *
 * @since 9.7.4.1
 */
function cerber_resolve_log_timezone() {

	// 1. Explicit admin override. Wins over everything, covers all edge cases.
	if ( defined( 'CERBER_LOG_TIMEZONE' ) ) {
		$timezone = CERBER_LOG_TIMEZONE;
		if ( is_string( $timezone ) ) {
			$timezone = trim( $timezone );
			if ( $timezone !== '' ) {
				return $timezone;
			}
		}
	}

	// 2. /etc/localtime symlink. This is what the OS (and thus fail2ban) uses.
	//    Match 'zoneinfo/' anywhere, so relative targets like
	//    '../usr/share/zoneinfo/Europe/Madrid' are handled too.
	if ( @is_link( '/etc/localtime' ) ) {
		$target = @readlink( '/etc/localtime' );
		if ( $target ) {
			$pos = strpos( $target, 'zoneinfo/' );
			if ( $pos !== false ) {
				return substr( $target, $pos + strlen( 'zoneinfo/' ) );
			}
		}
	}

	// 3. TZ environment variable, common in containers and some FPM pools.
	$env = getenv( 'TZ' );
	if ( $env ) {
		return $env;
	}

	// 4. Configured php.ini value. WordPress overrides the runtime default to
	//    UTC, but ini_get() still reports what the admin actually configured.
	$ini = ini_get( 'date.timezone' );
	if ( $ini ) {
		return $ini;
	}

	// 5. Predictable fallback.
	return 'UTC';
}

/**
 * Build a fail2ban-style timestamp in the resolved system timezone.
 *
 * Uses the procedural date functions and PHP's bundled timezone database for
 * correct DST handling. The default timezone is temporarily switched and then
 * restored. PHP serves one request per process, so this has no side effects on
 * concurrent code.
 *
 * @return string Formatted timestamp, e.g. 'May 22 14:03:51 '.
 *
 * @since 9.7.4.1
 */
function cerber_local_log_timestamp() {

	$tz = cerber_resolve_log_timezone();
	$saved = @date_default_timezone_get();

	// On an invalid identifier this returns false and leaves the timezone
	// unchanged, so date() below falls back to the saved (UTC) zone.
	@date_default_timezone_set( $tz );

	$ts = date( 'M j H:i:s ' );

	@date_default_timezone_set( $saved );

	return $ts;
}

/**
 * Converts a valid IPv4 address to wildcard notation Class C for its /24 range.
 *
 * Invalid IPv4 addresses are returned unchanged.
 *
 * @param string $ip Single IPv4 address.
 *
 * @return string IPv4/24 wildcard, such as 192.168.1.*, or the original value when the $ip is not a valid IPv4 address.
 *
 * @version Sequoia
 */
function cerber_get_subnet_ipv4( $ip ): string {
	if ( false === filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return $ip;
	}

	$last_octet_position = strrpos( $ip, '.' );

	return substr( $ip, 0, $last_octet_position + 1 ) . '*';
}

/**
 * Check if the given string is a valid IP address, wildcard, or CIDR.
 *
 * @param string $ip Expect to be valid a single IP address, wildcard, or CIDR.
 *
 * @return bool
 */
function cerber_is_ip_or_net( $ip ) {
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return true;
	}
	// WILDCARD: 192.168.1.*
	$ip = str_replace( '*', '0', $ip );
	//if ( @inet_pton( $ip ) ) {
	if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return true;
	}
	// CIDR: 192.168.1/24
	if ( strpos( $ip, '/' ) ) {
		$cidr = explode( '/', $ip );
		$net = $cidr[0];
		$mask = absint( $cidr[1] );
		$dots = substr_count( $net, '.' );
		if ( $dots < 3 ) {
			if ( $dots == 1 ) {
				$net .= '.0.0';
			}
            elseif ( $dots == 2 ) {
				$net .= '.0';
			}
		}
		if ( ! cerber_is_ipv4( $net ) ) {
			return false;
		}
		if ( ! is_numeric( $mask ) ) {
			return false;
		}

		return true;
	}

	return false;
}

/**
 * Tries to recognize a valid IP range specified with the dash in a given string.
 * Returns false if the given string doens't contain single IP or a range in any of three supported forms (dash, wildcard, CIDR).
 *
 * Supports IPv4 & IPv6.
 *
 * @param string $string String to parse as an IP range.
 *
 * @return array|bool|string Return the IP range as an array for a valid IP range, string in case of a valid single IP, wildcard, or CIDR. Returns false otherwise.
 *
 * @version Sequoia
 */
function cerber_parse_ip_range( $string ) {

	if ( cerber_is_ip_or_net( $string ) ) {
		return $string;
	}

	$explode = explode( '-', $string, 2 );
	if ( ! is_array( $explode ) || 2 != count( $explode ) ) {
		return false;
	}

	$begin_ip = filter_var( trim( $explode[0] ), FILTER_VALIDATE_IP );
	$end_ip = filter_var( trim( $explode[1] ), FILTER_VALIDATE_IP );

	if ( ! $begin_ip || ! $end_ip ) {
		return false;
	}

	if ( cerber_is_ipv4( $begin_ip ) && cerber_is_ipv4( $end_ip ) ) {
		$begin = ip2long( $begin_ip );
		$end = ip2long( $end_ip );

		if ( $begin >= $end ) {
			return false;
		}

		$ver6 = 0;
		$v6range = '';
	}
    elseif ( cerber_is_ipv6( $begin_ip ) && cerber_is_ipv6( $end_ip ) ) {
		$ver6 = 1;
		list( $begin, $end, $v6range ) = crb_ipv6_prepare( $begin_ip, $end_ip );

		if ( $begin > $end ) {
			return false;
		}

		if ( $begin === $end ) {
			list( $begin1, $begin2, $end1, $end2 ) = explode( '#', $v6range, 4 );

			if ( crb_compare_numbers( $begin1, $begin2, $end1, $end2 ) ) {
				return false;
			}
		}
	}
	else {
		return false;
	}

	return array(
		'range'     => $begin_ip . ' - ' . $end_ip,
		'begin_ip'  => $begin_ip,
		'end_ip'    => $end_ip,
		'begin'     => $begin,
		'end'       => $end,
		'IPV6'      => $ver6,
		'IPV6range' => $v6range
	);
}

/**
 * Convert a network wildcard string like x.x.x.* to an IP v4 range
 *
 * @param $wildcard string
 *
 * @return array|bool|string False if no wildcard found, otherwise result of cerber_parse_ip()
 */
function cerber_wildcard2range( $wildcard ) {
	if ( false === strpos( $wildcard, '*' ) ) {
		return false;
	}

	if ( ! strpos( $wildcard, ':' ) ) {
		$begin = str_replace( '*', '0', $wildcard );
		$end = str_replace( '*', '255', $wildcard );
		if ( ! cerber_is_ipv4( $begin ) || ! cerber_is_ipv4( $end ) ) {
			return false;
		}
	}
	else {
		$begin = str_replace( ':*', ':0000', $wildcard );
		$end = str_replace( ':*', ':ffff', $wildcard );
		if ( ! cerber_is_ipv6( $begin ) || ! cerber_is_ipv6( $end ) ) {
			return false;
		}
	}

	return cerber_parse_ip_range( $begin . ' - ' . $end );
}

/**
 * Convert a CIDR to an IP v4 range
 *
 * @param $cidr string
 *
 * @return array|bool|string
 */
function cerber_cidr2range( $cidr = '' ) {
	if ( ! strpos( $cidr, '/' ) ) {
		return false;
	}
	$cidr = explode( '/', $cidr );
	$net = $cidr[0];
	$mask = absint( $cidr[1] );
	$dots = substr_count( $net, '.' );
	if ( $dots < 3 ) { // not completed CIDR
		if ( $dots == 1 ) {
			$net .= '.0.0';
		}
        elseif ( $dots == 2 ) {
			$net .= '.0';
		}
	}
	if ( ! cerber_is_ipv4( $net ) ) {
		return false;
	}
	if ( ! is_numeric( $mask ) ) {
		return false;
	}

	if ( $mask == 32 ) {
		$begin_ip = $net;
		$end_ip = $net;
	}
	else {
		$begin_ip = long2ip( ( ip2long( $net ) ) & ( ( - 1 << ( 32 - (int) $mask ) ) ) );
		$end_ip = long2ip( ( ip2long( $net ) ) + pow( 2, ( 32 - (int) $mask ) ) - 1 );
	}

	return cerber_parse_ip_range( $begin_ip . ' - ' . $end_ip );
}

/**
 * Tries to recognize if a given string contains an IP range/CIDR/wildcard
 * Supports IPv4 & IPv6
 *
 * If returns false, there is no IP in the string in any form
 *
 * @param $string string Anything
 *
 * @return array|string Return an array if an IP range recognized, string with IP in case of a single IP, false otherwise
 */
function cerber_any2range( $string ) {
	if ( ! $string
	     || ! is_string( $string ) ) {
		return false;
	}

	$string = trim( $string );

	if ( filter_var( $string, FILTER_VALIDATE_IP ) ) {
		return $string;
	}

	// Do not change the order!
	$ret = cerber_wildcard2range( $string );
	if ( ! $ret ) {
		$ret = cerber_cidr2range( $string );
	}
	if ( ! $ret ) {
		$ret = crb_ipv6_cidr2range( $string );
	}
	if ( ! $ret ) {
		$ret = cerber_parse_ip_range( $string ); // must be last due to checking for cidr and wildcard
	}

	return $ret;
}

function crb_ipv6_cidr2range( $cidr ) {
	if ( ! strpos( $cidr, '/' ) ) {
		return false;
	}

	list( $net, $mask ) = explode( '/', $cidr );
	$mask = (int) $mask;
	if ( ! cerber_is_ipv6( $net ) || ! is_integer( $mask ) || $mask < 0 || $mask > 128 ) {
		return false;
	}

	$begin_hex = (string) bin2hex( inet_pton( $net ) );
	$begin_ip = cerber_ipv6_expand( $net );

	// These are cases that PHP can't handle as integers

	$exceptions = array(
		65 => '7fffffffffffffff',
		1  => '7fffffffffffffffffffffffffffffff',
		0  => 'ffffffffffffffffffffffffffffffff'
	);

	if ( isset( $exceptions[ $mask ] ) ) {
		$add = $exceptions[ $mask ];
	}
    elseif ( $mask >= 66 ) {
		$add = (string) dechex( pow( 2, ( 128 - $mask ) ) - 1 );
	}
	else { // $mask <= 64
		$add = (string) dechex( pow( 2, ( 128 - $mask - 64 ) ) - 1 ) . 'ffffffffffffffff';
	}

	$end_hex = str_pad( crb_summ_hex( $begin_hex, $add ), 32, '0', STR_PAD_LEFT );

	$end_ip = implode( ':', str_split( $end_hex, 4 ) );

	return cerber_parse_ip_range( $begin_ip . ' - ' . $end_ip );
}

/**
 * Calculate the summ of two any HEX numbers
 *
 * @param string $hex1 Number with no 0x prefix
 * @param string $hex2 Number with no 0x prefix
 *
 * @return string
 */
function crb_summ_hex( $hex1, $hex2 ) {
	$hex1 = ltrim( $hex1, '0' );
	$hex2 = ltrim( $hex2, '0' );

	if ( strlen( $hex1 ) > strlen( $hex2 ) ) {
		$h1 = $hex1;
		$h2 = $hex2;
	}
	else {
		$h1 = $hex2;
		$h2 = $hex1;
	}

	$h1 = str_split( (string) $h1 );
	$h2 = str_split( (string) $h2 );

	$h1 = array_reverse( array_map( 'hexdec', $h1 ) );
	$h2 = array_reverse( array_map( 'hexdec', $h2 ) );

	$max1 = count( $h1 ) - 1;
	$max2 = count( $h2 ) - 1;
	$i = 0;
	$r = 0;
	$finish = false;

	while ( $i <= $max1 && ! $finish ) {
		if ( $i <= $max2 ) {
			$h1[ $i ] = $h1[ $i ] + $h2[ $i ] + $r;
		}
		else {
			if ( ! $r ) {
				$finish = true;
			}
			$h1[ $i ] += $r;
		}
		if ( $h1[ $i ] >= 16 ) {
			$r = 1;
			$h1[ $i ] -= 16;
		}
		else {
			$r = 0;
		}
		$i ++;
	}

	if ( $r ) {
		$h1[] = 1;
	}

	$h1 = array_reverse( array_map( 'dechex', $h1 ) );

	return implode( '', $h1 );
}

/*
	Check for given IP address or subnet belong to this session.
*/
function cerber_is_myip( $ip ) {

	if ( ! is_string( $ip ) ) {
		return false;
	}

	$remote_ip = cerber_get_remote_ip();

	if ( $ip == $remote_ip ) {
		return true;
	}
	if ( $ip == cerber_get_subnet_ipv4( $remote_ip ) ) {
		return true;
	}

	return false;
}

/**
 * Checks whether an IP address belongs to the provided IP range.
 * Supports IPv4 & IPv6 ranges.
 *
 * @param array|string $range
 * @param string $ip
 *
 * @return bool
 */
function cerber_is_ip_in_range( $range, string $ip ) {

	if ( ! is_array( $range ) ) {
		return false;
	}

	// $range = IPv6 range

	if ( $range['IPV6'] ) {
		if ( cerber_is_ipv4( $ip ) ) {
			return false;
		}

		return crb_ipv6_is_in_range( $ip, $range );
	}

	// $range = IPv4 range

	if ( cerber_is_ipv6( $ip ) ) {
		return false;
	}

	$long = ip2long( $ip );

	if ( $range['begin'] <= $long && $long <= $range['end'] ) {
		return true;
	}

	return false;
}

/**
 * Display 404 page to bump bots and bad guys
 *
 * @param bool $simple If true, force displaying basic 404 page
 */
function cerber_404_page( $simple = false ) {
	global $wp_query;

	header_remove( 'Link' ); // Avoid "promoting" REST API URL by WordPress in rest_output_link_header()

	$method = crb_get_settings( 'page404' );

	if ( $method == 2
	     && $url = crb_get_settings( 'page404_redirect' ) ) {
		if ( ! headers_sent() ) {
			CRB_Globals::$redirect_url = $url;

			header( 'Location: ' . $url, true, 302 );
			exit;
		}
	}
    elseif ( ! $simple ) {
		if ( function_exists( 'status_header' ) ) {
			status_header( '404' );
		}
		if ( isset( $wp_query ) && is_object( $wp_query ) ) {
			$wp_query->set_404();
		}
		if ( 0 == $method ) {
			$template = null;

			// Avoid the fatal error "Call to a member function is_block_editor() on null"
			remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

			if ( function_exists( 'get_404_template' ) ) {
				$template = get_404_template();
			}

			if ( function_exists( 'apply_filters' ) ) {
				$template = apply_filters( 'cerber_404_template', $template );
			}

			if ( $template
			     && @file_exists( $template ) ) {
				include( $template );

				exit;
			}
		}
	}

	// Showing a simple 404 page

	header( 'HTTP/1.0 404 Not Found', true, 404 );
	echo '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ' . crb_escape_path_for_html( (string) $_SERVER['REQUEST_URI'] ) . ' was not found on this server.</p></body></html>';
	cerber_traffic_log(); // do not remove!

	exit;
}

/*
	Display Forbidden page
*/
function cerber_forbidden_page() {
	$wp_cerber = get_wp_cerber();
	$sid = strtoupper( $wp_cerber->getRequestID() );
	status_header( '403' );
	header( 'HTTP/1.0 403 Access Forbidden', true, 403 );
	?>
    <!DOCTYPE html>
    <html style="height: 100%;">
    <head>
        <meta charset="UTF-8">
        <title>403 Access Forbidden</title>
        <style>
            @media screen and (max-width: 800px) {
                body > div > div > div div {
                    display: block !important;
                    padding-right: 0 !important;
                }

                body {
                    text-align: center !important;
                }
            }
        </style>
    </head>
    <body style="height: 90%;">
    <div style="display: flex; align-items: center; justify-content: center; height: 90%;">
        <div style="background-color: #eee; width: 70%; border: solid 3px #ddd; padding: 1.5em 3em 3em 3em; font-family: Arial, Helvetica, sans-serif;">
            <div style="display: table-row;">
                <div style="display: table-cell; font-size: 150px; color: red; vertical-align: top; padding-right: 50px;">
                    &#9995;
                </div>
                <div style="display: table-cell; vertical-align: top;">
                    <h1 style="margin-top: 0;"><?php _e( "We're sorry, you are not allowed to proceed", 'wp-cerber' ); ?></h1>
                    <p><?php _e( 'Your request looks suspiciously similar to automated requests from spam posting software or it has been denied by a security policy configured by the website administrator.', 'wp-cerber' ); ?></p>
                    <p><?php _e( 'If you believe you should be able to perform this request, please let us know.', 'wp-cerber' ); ?></p>
                    <p style="margin-top: 2em;">
                    <pre style="color: #777">RID: <?php echo $sid; ?></pre>
                    </p>
                    <p style="margin-top: 2em; font-size: 80%">
                        Know more: <a href="https://wpcerber.com/rid-request-not-allowed-wordpress/" target="_blank" rel="noopener noreferrer">Documentation</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
	<?php
	cerber_traffic_log();  // do not remove!
	exit;
}

// Citadel mode -------------------------------------------------------------------------------------

function cerber_enable_citadel() {

	if ( ! crb_get_settings( 'citadel_on' ) ) {
		return;
	}

	if ( cerber_is_citadel() ) {
		return;
	}

	cerber_update_set( 'cerber_citadel', 1, null, false, time() + crb_get_settings( 'ciduration' ) * 60 );

	cerber_log( 12 );

	// Notify admin
	if ( crb_get_settings( 'cinotify' ) ) {
		CRB_Messaging::send( 'citadel_mode' );
	}
}

function cerber_disable_citadel() {
	cerber_delete_set( 'cerber_citadel' );
}

function cerber_is_citadel() {
	return (bool) cerber_get_set( 'cerber_citadel', null, false );
}

/**
 * @param string $ip_address
 *
 * @return array|bool
 *
 * @since 8.9.6.1
 */
function crb_send_lockout_notification( $ip_address = '' ) {
	$em = crb_get_settings( 'notify_above-enabled' );
	$pb = crb_get_settings( 'pbnotify-enabled' );

	if ( ! $em && ! $pb ) {
		return false;
	}

	$count = crb_get_locked_out_num();
	$send_email = ( $em && ( $count > crb_get_settings( 'notify_above' ) ) );

	$channels = array(
		'email' => $send_email,
	);

	if ( lab_lab() ) {
		$channels['pushbullet'] = ( $pb && ( $count > crb_get_settings( 'pbnotify' ) ) );
	}
	else {
		$channels['pushbullet'] = $send_email;
	}

	if ( array_filter( $channels ) ) {
		return CRB_Messaging::send( 'lockout', array( 'ip' => $ip_address ), $channels );
	}

	return false;
}

function cerber_enable_html() {
	return 'text/html';
}

/**
 * Generates a performance report
 *
 * @param array $args
 *
 * @return array
 */
function cerber_generate_email_report( $args ) {
	global $wpdb;

	$period = $args['report_id'] ?? 'one_week';

	if ( $period == 'one_week' ) {
		if ( crb_get_settings( 'wreports_7' ) ) {
			$end = strtotime( 'midnight' ) - 1;
			$begin = $end - 7 * 24 * 3600 + 1;
		}
		else {
			$end = strtotime( 'last sunday' ) + 24 * 3600 - 1;
			$begin = $end - 7 * 24 * 3600 + 1;
		}
		$title = __( 'Weekly Report', 'wp-cerber' );
	}
    elseif ( $period == 'one_month' ) {
		if ( crb_get_settings( 'monthly_30' ) ) {
			$end = strtotime( 'midnight' ) - 1;
			$begin = $end - 30 * 24 * 3600 + 1;
		}
		else {
			$date = getdate( strtotime( 'first day of previous month' ) );
			$begin = mktime( 0, 0, 0, $date['mon'], $date['mday'], $date['year'] );
			$date = getdate( strtotime( 'last day of previous month' ) );
			$end = mktime( 23, 59, 59, $date['mon'], $date['mday'], $date['year'] );
		}
		$title = __( 'Monthly Report', 'wp-cerber' );
	}
	else {
		return array( 'Unsupported period of time', 'Unsupported period of time' );
	}

	$report = '';

	$kpi_rows = array();
	$base_url = cerber_admin_link( 'activity', array(), false, false );
	$css_table = 'width: 95%; max-width: 1000px; margin:0 auto; margin-bottom: 10px; background-color: #f5f5f5; text-align: center; font-family: Arial, Helvetica, sans-serif;';
	$css_td = 'padding: 0.5em 0.5em 0.5em 1em; text-align: left;';
	$css_border = 'border-bottom: solid 2px #f9f9f9;';

	$site_name = ( is_multisite() ) ? get_site_option( 'site_name' ) : get_option( 'blogname' );

	$df = get_option( 'date_format' );

	$report .= '<div style="' . $css_table . '"><div style="margin:0 auto; text-align: center;"><p style="font-size: 130%; padding-top: 0.5em;">' . $site_name . '</p><p>' . $title . '</p><p style="padding-bottom: 1em;">' . date( $df, $begin ) . ' - ' . date( $df, $end ) . '</p></div></div>';

	$activites = $wpdb->get_results( 'SELECT activity, COUNT(activity) cnt FROM ' . CERBER_LOG_TABLE . ' WHERE stamp BETWEEN ' . $begin . ' AND ' . $end . ' GROUP by activity ORDER BY cnt DESC' );

	if ( $activites ) {

		// KPI
		$kpi_list = cerber_calculate_kpi( $begin, $end );

		foreach ( $kpi_list as $kpi ) {
			$kpi_rows[] = '<td style="' . $css_td . ' text-align: right;">' . $kpi[1] . '</td><td style="padding: 0.5em; text-align: left;">' . $kpi[0] . '</td>';
		}

		$report .= '<div style="text-align: center; ' . $css_table . '"><table style="font-size: 130%; margin:0 auto;"><tr>' . implode( '</tr><tr>', $kpi_rows ) . '</tr></table></div>';

		// Activities breakdown

		$rows = array();
		$rows[] = '<td style="' . $css_td . $css_border . '" colspan="2"><p style="line-height: 1.5em; font-weight: bold;">' . __( 'Activity details', 'wp-cerber' ) . '</p></td>';

		$lables = cerber_get_labels();

		foreach ( $activites as $a ) {
			$rows[] = '<td style="' . $css_border . $css_td . '">' . $lables[ $a->activity ] . '</td><td style="padding: 0.5em; text-align: center; width:10%;' . $css_border . '"><a href="' . $base_url . '&filter_activity=' . $a->activity . '">' . $a->cnt . '</a></td>';
		}

		$report .= '<table style="border-collapse: collapse; ' . $css_table . '"><tr>' . implode( '</tr><tr>', $rows ) . '</tr></table>';

		// Attempts to log in with non-existing usernames
		$attempts = $wpdb->get_results( 'SELECT user_login, COUNT(user_login) cnt FROM ' . CERBER_LOG_TABLE . ' WHERE activity = 51 AND (stamp BETWEEN ' . $begin . ' AND ' . $end . ') GROUP by user_login ORDER BY cnt DESC LIMIT 10' );

		if ( $attempts ) {
			$rows = array();
			$rows[] = '<td style="' . $css_td . $css_border . '" colspan="2"><p style="line-height: 1.5em; font-weight: bold;">' . __( 'Attempts to log in with non-existing usernames', 'wp-cerber' ) . '</p></td>';
			foreach ( $attempts as $a ) {
				$rows[] = '<td style="' . $css_border . $css_td . '">' . crb_escape_html( $a->user_login ) . '</td><td style="padding: 0.5em; text-align: center; width:10%;' . $css_border . '"><a href="' . $base_url . '&filter_login=' . $a->user_login . '">' . $a->cnt . '</a></td>';
			}
			$report .= '<table style="border-collapse: collapse; ' . $css_table . '"><tr>' . implode( '</tr><tr>', $rows ) . '</tr></table>';
		}
	}
	else {
		$report .= '<div style="text-align: center; ' . $css_table . '"><p style="padding: 1em;">No data logged within the specified date range to generate the report</p></div>';
	}

	$report = '<div style="width:100%; padding: 1em; text-align: center; background-color: #f9f9f9;">' . $report . '</div>';

	return array( $title, $report );
}


// Maintenance routines ----------------------------------------------------------------

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['crb_five'] = array(
		'interval' => 300,
		'display'  => 'Every 5 Minutes',
	);

	return $schedules;
} );

add_action( 'cerber_hourly_1', 'cerber_do_hourly_1' );
/**
 * Hourly maintenance runner #1.
 *
 * Acts as a scheduled execution entry point that coordinates periodic housekeeping
 * and consistency checks for the plugin runtime environment.
 *
 * This function is an orchestration layer only. Domain-specific work is delegated to dedicated
 * maintenance and upgrade routines.
 *
 * @return void
 *
 * @see cerber_do_hourly_2()
 */
function cerber_do_hourly_1( $force = false ) {

	$start = time();

	// Enforce an hourly execution cadence and prevent duplicate runs within the same interval.

	$process_key = 'cerber_hourly_1';

	if ( ( $last_run = get_site_transient( $process_key ) )
	     && date( 'G', $last_run[0] ) == date( 'G' ) ) {
		return;
	}

	set_site_transient( $process_key, array( $start ), 2 * 3600 );

	// Multisite cadence separately

	if ( is_multisite() ) {
		if ( ! $force && get_site_transient( 'cerber_multisite' ) ) {
			return;
		}
		set_site_transient( 'cerber_multisite', 'executed', 3600 );
	}

	require_once( __DIR__ . '/cerber-toolbox.php' );

	// Process obsolete things
	crb_log_maintainer();

	// Upgrading data to new formats
	crb_once_upgrade_log();
	crb_once_upgrade_cbla();

	// Keep the size of the log file small
	crb_truncate_diagnostic_log();

	crb_plugin_update_notifier();

	if ( ! CRB_Activity_Alerts::delete_expired() ) {
		cerber_error_log( 'Unable to update the list of alerts', 'ALERTS' );
	}

	if ( cerber_get_mode() != crb_get_settings( 'boot-mode' ) ) {
		cerber_set_boot_mode();
	}

	set_site_transient( $process_key, array( $start, time() ), 2 * 3600 );
}

add_action( 'cerber_hourly_2', 'cerber_do_hourly_2' );
/**
 * Hourly maintenance runner #2.
 *
 * Acts as a scheduled execution entry point that coordinates periodic housekeeping
 * and consistency checks for the plugin runtime environment.
 *
 * This function is an orchestration layer only. Domain-specific work is delegated to dedicated
 * maintenance and upgrade routines.
 *
 * @return void
 *
 * @see cerber_do_hourly_1()
 */
function cerber_do_hourly_2() {

	$start = time();

	// Enforce an hourly execution cadence and prevent duplicate runs within the same interval.

	$process_key = 'cerber_hourly_2';

	if ( ( $last_run = get_site_transient( $process_key ) )
	     && date( 'G', $last_run[0] ) == date( 'G' ) ) {
		return;
	}

	set_site_transient( $process_key, array( $start ), 2 * 3600 );

	// Ensure the integrity of the WP Cerber database tables

	cerber_watchdog( true );

	// Generate weekly reports

	$now = time() + get_option( 'gmt_offset' ) * 3600;

	if ( crb_get_settings( 'enable-report' )
	     && date( 'w', $now ) == crb_get_settings( 'wreports-day' )
	     && date( 'G', $now ) == crb_get_settings( 'wreports-time' ) ) {

		$result = CRB_Messaging::send( 'report' );

		if ( ! $sent = get_site_option( '_cerber_report' ) ) {
			$sent = array();
		}
		$sent['period_week'] = array( time(), $result );
		update_site_option( '_cerber_report', $sent );
	}

	// Generate monthly reports

	if ( crb_get_settings( 'monthly_report' )
	     && $on = crb_get_settings( 'monthly_on' ) ) {

		$last_day = date( 'j', strtotime( 'last day of this month' ) );
		$send_day = ( $on['day'] > $last_day ) ? $last_day : $on['day'];

		if ( date( 'j', $now ) == $send_day
		     && date( 'G', $now ) == $on['hours'] ) {

			$result = CRB_Messaging::send( 'report', array(), array(), false, array( 'report_id' => 'one_month' ) );

			if ( ! $sent = get_site_option( '_cerber_report' ) ) {
				$sent = array();
			}
			$sent['one_month'] = array( time(), $result );
			update_site_option( '_cerber_report', $sent );
		}
	}

	// Misc maintenance and housekeeping --------------------------------------

	cerber_delete_expired_set();

	if ( crb_get_settings( 'cerberlab' ) || lab_lab() ) {
		lab_check_nodes( true, true );
	}

	cerber_push_lab();

	cerber_cloud_sync();

	cerber_get_the_folder(); // Simply keep folder locked

	cerber_db_query( 'DELETE FROM ' . CERBER_QMEM_TABLE . ' WHERE stamp < ' . ( time() - 30 * 60 ) );

	set_site_transient( $process_key, array( $start, time() ), 2 * 3600 );
}

add_action( 'cerber_daily', 'cerber_daily_run' );
/**
 * Daily maintenance runner.
 *
 * Acts as a scheduled execution entry point that coordinates periodic housekeeping
 * and consistency checks for the plugin runtime environment.
 *
 * This function is an orchestration layer only. Domain-specific work is delegated to dedicated
 * maintenance and upgrade routines.
 *
 * @return void
 *
 * @see cerber_do_hourly_1()
 * @see cerber_do_hourly_2()
 */
function cerber_daily_run() {

	$start = time();

	// Enforce one start per calendar day and avoid duplicate starts.

	$process_key = 'cerber_daily_1';

	if ( ( $last_start = get_site_transient( $process_key ) )
	     && date( 'j', $last_start[0] ) == date( 'j' ) ) {
		return;
	}

	set_site_transient( $process_key, array( $start ), 48 * 3600 );

	// ------------------------

	// Refresh the backup copy of the plugin settings before any table maintenance below

	CRB_Settings_Backup::sync( CRB_Settings_Backup::CONTEXT_SCHEDULED_SYNC );

	cerber_do_hourly_1( true );

	$now_time = time();

	lab_validate_lic();

	cerber_db_query( 'DELETE FROM ' . CERBER_LAB_NET_TABLE . ' WHERE expires < ' . $now_time );
	cerber_db_query( 'DELETE FROM ' . CERBER_LAB_TABLE . ' WHERE stamp < ' . ( $now_time - 3600 ) ); // workaround for weird/misconfigured hostings

	// Delete sets if log entries for a user were deleted completely
	// @since 8.6.3.4
	$sql = 'SELECT the_id FROM ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' sets LEFT JOIN ' . CERBER_LOG_TABLE . ' log
		    ON log.user_id = sets.the_id
		    WHERE  sets.the_key = "user_deleted" AND log.user_id IS NULL';
	$user_ids1 = cerber_db_get_col( $sql );
	$sql = 'SELECT the_id FROM ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' sets LEFT JOIN ' . CERBER_TRAF_TABLE . ' log
		    ON log.user_id = sets.the_id
		    WHERE  sets.the_key = "user_deleted" AND log.user_id IS NULL';
	$user_ids2 = cerber_db_get_col( $sql );
	if ( $delete = array_intersect( $user_ids1, $user_ids2 ) ) {
		cerber_db_query( 'DELETE FROM ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' WHERE the_key = "user_deleted" AND the_id IN (' . implode( ',', $delete ) . ')' );
	}

	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_LOG_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_QMEM_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_TRAF_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_ACL_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CRB_LOCKOUT_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_LAB_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_LAB_IP_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . CERBER_LAB_NET_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . cerber_get_db_prefix() . CRB_SCANFILES_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . cerber_get_db_prefix() . CERBER_SETS_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . cerber_get_db_prefix() . CERBER_MS_LIST_TABLE );
	cerber_db_query( 'OPTIMIZE TABLE ' . cerber_get_db_prefix() . CERBER_USS_TABLE );

	cerber_check_new_version( false );

	if ( nexus_is_main() ) {
		if ( ( $ups = get_site_transient( 'update_plugins' ) ) && ! empty( $ups->response ) ) {
			nexus_update_updates( crb_normalize_to_array_deep( $ups->response ) );
		}

		nexus_delete_unused( 'nexus_servers', 'server_id' );
		nexus_delete_unused( 'nexus_countries', 'server_country' );
	}

	CRB_Cache::reset();

	// Cleanup the quarantine folder
	if ( $dirs = glob( cerber_get_the_folder() . 'quarantine' . '/*', GLOB_ONLYDIR ) ) {
		$sync = false;
		foreach ( $dirs as $dir ) {
			$d = basename( $dir );
			if ( is_numeric( $d ) ) {
				if ( $d < ( time() - DAY_IN_SECONDS * crb_get_settings( 'scan_qcleanup' ) ) ) {
					$fs = cerber_init_wp_filesystem();
					if ( ! crb_is_wp_error( $fs ) ) {
						$fs->delete( $dir, true );
						$sync = true;
					}
				}
			}
		}
		if ( $sync ) {
			_crb_qr_total_sync();
		}
	}
	else {
		_crb_qr_total_sync( 0 );
	}

	cerber_upgrade_db_deferred();

	if ( class_exists( 'CRB_Bug_Hunter' ) ) {
		CRB_Bug_Hunter::run_maintenance_tasks();
	}

	// TODO: implement holding previous values for a while
	// cerber_antibot_gene();

	set_site_transient( $process_key, array( $start, time() ), 48 * 3600 );
}

/**
 * Main CRON task scheduler
 *
 */
add_action( 'cerber_bg_launcher', function () {

	$next_hour = intval( floor( ( time() + 3600 ) / 3600 ) * 3600 );

	if ( ! wp_next_scheduled( 'cerber_hourly_1' ) ) {
		wp_schedule_event( $next_hour, 'hourly', 'cerber_hourly_1' );
	}

	if ( ! wp_next_scheduled( 'cerber_hourly_2' ) ) {
		wp_schedule_event( $next_hour + 600, 'hourly', 'cerber_hourly_2' );
	}

	if ( ! wp_next_scheduled( 'cerber_daily' ) ) {
		if ( ! $when = strtotime( 'midnight' ) + 24 * 3600 ) {
			$when = $next_hour;
		}
		wp_schedule_event( $when + 2 * 3600 + 1200, 'daily', 'cerber_daily' );
	}

	if ( defined( 'CRB_DOING_BG_TASK' ) ) {
		return;
	}

	define( 'CRB_DOING_BG_TASK', 1 );

	@ignore_user_abort( true );
	crb_try_raise_php_limits();

	if ( nexus_is_main() ) {
		nexus_schedule_refresh();
	}

	CRB_Deferred_Tasks::launcher();

	// Run postponed tasks

	require_once( __DIR__ . '/cerber-toolbox.php' );

	if ( cerber_get_set( 'event_wp_found_updates', null, false ) ) {
		crb_plugin_update_notifier( true );
		cerber_delete_set( 'event_wp_found_updates' );
	}

} );

/**
 * Generates a human-readable name for a callable to be used in diagnostic messages.
 *
 * Handles all types supported by call_user_func_array() safely:
 * - Strings (function names, static method calls)
 * - Arrays (object/method, class/method)
 * - Closures
 * - Invokable objects
 * - Non-callable values (returns type)
 *
 * @param mixed $callback The callback to inspect.
 *
 * @return string
 *
 * @since 9.6.2.1
 *
 * @version Sequoia
 */
function crb_make_callable_name( $callback ) {
	if ( is_string( $callback ) ) {
		return $callback;
	}

	if ( is_array( $callback ) ) {
		// Standard callable array: [ $object_or_class, $method_name ]
		if ( count( $callback ) === 2 ) {
			// Extract class name safely
			$class_name = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];

			// Extract method name safely (cast to string to handle edge cases)
			$method_name = isset( $callback[1] ) ? (string) $callback[1] : 'unknown';

			return $class_name . '::' . $method_name;
		}

		return 'Array'; // Fallback for malformed arrays to avoid dumping data
	}

	if ( $callback instanceof Closure ) {
		return 'Closure';
	}

	if ( is_object( $callback ) ) {
		// Handle Invokable objects or just return class name
		return get_class( $callback );
	}

	// Handle scalar types (int, float, bool, null) and resources
	return gettype( $callback );
}

/**
 * Log activity
 *
 * @param int $activity Activity ID
 * @param string $login Login used or any additional information
 * @param int $user_id User ID
 * @param int $status
 * @param string $ip IP Address
 *
 * @return bool
 * @since 3.0
 */
function cerber_log( $activity, $login = '', $user_id = 0, $status = 0, $ip = '' ) {

	return CRB_Activity::log( (int) $activity, (string) $login, (int) $user_id, (int) $status, (string) $ip );
}

/**
 * Return the last login record for a given user
 *
 * @param $user_id int|null
 * @param $user_email string
 *
 * @return false|object
 */
function cerber_get_last_login( $user_id, $user_email = '' ) {
	if ( $user_id ) {
		$u = array( 'id' => $user_id );
	}
    elseif ( $user_email ) {
		$u = array( 'email' => $user_email );
	}
	else {
		return false;
	}

	if ( $recs = CRB_Activity::get_log( array( CRB_EV_LIN ), $u, array( 'DESC' => 'stamp' ), 1 ) ) {
		return $recs[0];
	}

	return false;
}

/**
 * Finds the last failed/denied attempt to log in. Uses user login and email.
 * Returns an activity log entry.
 *
 * @param string $login
 * @param string $email
 * @param bool $denied
 *
 * @return false|object
 *
 * @since 9.0.2
 */
function crb_get_last_failed( $login, $email, $denied = false ) {
	$act = ( $denied ) ? 53 : CRB_EV_LFL;

	return cerber_db_get_row( 'SELECT * FROM ' . CERBER_LOG_TABLE . ' WHERE ( user_login = "' . $login . '" OR user_login = "' . $email . '" ) AND activity = ' . $act . ' ORDER BY stamp DESC LIMIT 1', MYSQL_FETCH_OBJECT );
}

/**
 * @param array $activity
 * @param int $begin
 * @param int $end
 * @param string $column
 * @param false $distinct
 *
 * @return int|mixed
 */
function cerber_count_log( $activity, $begin, $end, $column = 'ip', $distinct = false ) {

	$begin = ( (int) floor( (int) $begin / 90 ) ) * 90; // every 90 seconds - let SQL query be cached
	$end = ( (int) floor( (int) $end / 90 ) ) * 90; // every 90 seconds - let SQL query be cached

	$column = ( ( $distinct ) ? ' DISTINCT ' : '' ) . $column;
	$result = crb_q_cache_get( 'SELECT COUNT( ' . $column . ' ) FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . implode( ',', $activity ) . ') AND (stamp BETWEEN ' . $begin . ' AND ' . $end . ')', CERBER_LOG_TABLE );
	if ( empty( $result ) ) {
		return 0;
	}

	return $result[0][0];
}

/**
 * Load must have settings before the rest of the stuff during the plugin activation
 *
 * @since 7.8.3
 *
 */
function cerber_on_plugin_activation() {

	// This ensures required settings are initialized BEFORE the plugin code runs

	if ( cerber_get_get( 'action' ) === 'activate'
	     && cerber_get_get( 'plugin' ) === CERBER_PLUGIN_ID ) {

		// The plugin was just activated in wp-admin

		// If no plugin settings were found, we consider as new install

		if ( ! crb_get_settings( '', true, false ) ) {

			if ( ! defined( 'CRB_JUST_MARRIED' ) ) {
				define( 'CRB_JUST_MARRIED', 1 );
			}

			cerber_load_defaults();
		}
	}

	// A backup way

	add_action( 'activated_plugin', function ( $plugin ) {

		if ( $plugin !== CERBER_PLUGIN_ID ) {
			return;
		}

		// If no plugin settings were found, we consider as new install

		if ( ! crb_get_settings( '', true, false ) ) {

			if ( ! defined( 'CRB_JUST_MARRIED' ) ) {
				define( 'CRB_JUST_MARRIED', 1 );
			}

			cerber_load_defaults();
		}
	} );
}

/**
 * Post-activation stuff
 *
 */
register_activation_hook( cerber_plugin_file(), function () {

	load_plugin_textdomain( 'wp-cerber', false, 'wp-cerber/languages' );

	if ( version_compare( CERBER_REQ_PHP, phpversion(), '>' ) ) {
		/* translators: %1$s is the required PHP version, %2$s is the current PHP version. */
		cerber_stop_activating( '<h3>' . sprintf( __( 'WP Cerber requires PHP version %1$s or higher, but your web server is currently running PHP %2$s.', 'wp-cerber' ), CERBER_REQ_PHP, phpversion() ) . '</h3>' );
	}

	if ( ! crb_wp_version_compare( CERBER_REQ_WP ) ) {
		/* translators: %1$s is the required WordPress version, %2$s is the current WordPress version. */
		cerber_stop_activating( '<h3>' . sprintf( __( 'WP Cerber requires WordPress version %1$s or higher. Your WordPress version is %2$s. Please update your WordPress to the latest version.', 'wp-cerber' ), CERBER_REQ_WP, cerber_get_wp_version() ) . '</h3>' );
	}

	$db_errors = cerber_create_db();

	if ( $db_errors ) {
		$e = '';
		foreach ( $db_errors as $db_error ) {
			$e .= '<p>' . implode( '</p><p>', $db_error ) . '</p>';
		}
		cerber_stop_activating( '<h3>' . __( "Can't activate WP Cerber due to a database error.", 'wp-cerber' ) . '</h3>' . $e );
	}

	lab_get_key( true );

	cerber_upgrade_all( true );

	cerber_cookie_one();

	cerber_load_admin_code();

	CRB_Deferred_Tasks::add( 'crb_sessions_sync_all' );

	$allowed_ip_notice = array();

	if ( is_user_logged_in() ) {  // Not for remote plugin installation/activation

		$ip = cerber_get_remote_ip();

		if ( crb_get_lockout( $ip ) ) {
			if ( ! crb_delete_lockout( $ip ) ) {
				$sub = cerber_get_subnet_ipv4( $ip );
				crb_delete_lockout( $sub );
			}
		}

		if ( ! crb_get_settings( 'no_white_my_ip' ) ) {
			cerber_acl_add_allowed( $ip, 'My IP address (' . cerber_date( time(), false ) . ')' ); // Protection for non-experienced users
			/* translators: %s is the user's IP address. */
			$allowed_ip_notice[] = crb_ui_element( 'p', array(), sprintf( __( 'Your IP address %s has been added to the Allowed IP Access List', 'wp-cerber' ), cerber_get_remote_ip() ) );
		}

		cerber_disable_citadel();
	}

	cerber_htaccess_sync( 'main' );
	cerber_htaccess_sync( 'media' );

	cerber_set_boot_mode();

	crb_x_update_add_on_list();

	$announcement = array();

	$announcement[] = crb_ui_element( 'h2', array(), __( 'WP Cerber is now active and has started protecting your site', 'wp-cerber' ) );

	// The allowed-IP notice exists only when the IP address of the activating user was added to the list.

	$announcement = array_merge( $announcement, $allowed_ip_notice );

	$announcement[] = crb_ui_element( 'p', array( 'style' => 'font-size:130%;' ),
		crb_ui_link( 'https://wpcerber.com/getting-started/', __( 'Getting Started Guide', 'wp-cerber' ), array( 'target' => '_blank' ) )
	);

	// Each icon is an empty span carrying the icon font classes, and the spaces between the children are explicit because the renderer joins them without a separator.
	// Quick links kept disabled: main (crb-icon-bx-slider), scan_main (crb-icon-bx-radar), acl (crb-icon-bx-lock), antispam (crb-icon-bxs-shield), hardening (crb-icon-bx-shield-alt), notifications (crb-icon-bx-bell)

	$announcement[] = crb_ui_element( 'div', array( 'id' => 'crb-activation-msg' ),
		crb_ui_element( 'p', array(), array(
			' ',
			crb_ui_element( 'span', array( 'class' => 'crb-icon crb-icon-bx-layer' ) ),
			' ',
			crb_ui_link( cerber_admin_link( 'imex' ), __( 'Import settings', 'wp-cerber' ) ),
			' ',
			crb_ui_element( 'span', array( 'class' => 'crb-icon dashicons-before dashicons-twitter' ) ),
			' ',
			crb_ui_link( 'https://twitter.com/wpcerber', 'Follow Cerber on X', array( 'target' => '_blank' ) ),
			' ',
			crb_ui_element( 'span', array( 'class' => 'crb-icon dashicons-before dashicons-email-alt' ) ),
			' ',
			crb_ui_link( 'https://wpcerber.com/subscribe-newsletter/', "Subscribe to Cerber's newsletter", array( 'target' => '_blank' ) ),
		) )
	);

	$encoding_result = crb_ui_spec_encode( crb_ui_fragment( $announcement ) );

	if ( $encoding_result->has_errors() ) {

		// Clearing keeps the stored announcement deterministic: a value left by an earlier activation must not be displayed instead of the current one.

		crb_admin_notice_failure( 'Unable to store the activation announcement.', $encoding_result );
		cerber_update_set( CRB_ADMIN_WIDE, '' );
	}
	else {
		cerber_update_set( CRB_ADMIN_WIDE, $encoding_result->get_results() );
	}

	$pi = array();
	$pi['Version'] = CERBER_VER;
	$pi['time'] = time();
	$pi['user'] = get_current_user_id();
	// @since 9.4.2
	$pi['ip'] = cerber_get_remote_ip();
	$pi['ua'] = crb_array_get( $_SERVER, 'HTTP_USER_AGENT', '' );

	cerber_update_set( '_cerber_on', $pi ); // @since 9.4.2

	if ( ! defined( 'CRB_JUST_MARRIED' ) || ! CRB_JUST_MARRIED ) {
		return;
	}

	CRB_Messaging::send( 'plugin_activated' );

	if ( ! cerber_get_set( '_activated' ) ) {
		cerber_update_set( '_activated', $pi );
	}

} );

/*
	Abort activating plugin!
*/
function cerber_stop_activating( $msg ) {
	deactivate_plugins( CERBER_PLUGIN_ID );
	wp_die( $msg );
}

// Closure can't be used
register_uninstall_hook( cerber_plugin_file(), 'cerber_finito' );
function cerber_finito() {
	if ( ! is_super_admin() ) {
		return;
	}

	$dir = cerber_get_the_folder();
	if ( $dir && file_exists( $dir ) ) {
		$fs = cerber_init_wp_filesystem();
		if ( ! crb_is_wp_error( $fs ) ) {
			$fs->rmdir( $dir, true );
		}
	}

	$list = array(
		CRB_ALL_ALERTS,
		'_cerber_up',
		'_cerber_report',
		'cerber_tmp_old_settings',
		'cerber-groove',
		'cerber-groove-x',
		LAB_SITE_KEY_DATA,
		'cerber-antibot',
		CRB_ADMIN_ANNOUNCE,
		'_cerber_db_errors',
		'_cerber_notify_new'
	);

	$list = array_merge( $list, cerber_get_setting_list( true ) );
	foreach ( $list as $opt ) {
		delete_site_option( $opt );
	}

	// Must be executed last
	cerber_db_query( 'DROP TABLE IF EXISTS ' . implode( ',', cerber_get_tables() ) );
}

/**
 * Upgrade database tables, data and plugin settings
 *
 * @since 3.0
 *
 */
function cerber_upgrade_all( $force = false ) {

	$ver = get_site_option( '_cerber_up' );
	$diff_version = (string) crb_array_get( $ver, 'v' ) !== (string) CERBER_VER;

	if ( ! $force
	     && ! $diff_version ) {
		return;
	}

	$d = @ini_get( 'display_errors' );
	@ini_set( 'display_errors', 0 );

	@ignore_user_abort( true );

	crb_try_raise_php_limits();

	CRB_Globals::$doing_upgrade = true;

	if ( ! defined( 'CRB_DOING_UPGRADE' ) ) {
		define( 'CRB_DOING_UPGRADE', 1 );
	}

	cerber_load_admin_code();

	crb_clear_admin_msg();
	CRB_Issues::delete_all();
	cerber_create_db();

	if ( $errors = cerber_upgrade_db() ) {

	}

	cerber_antibot_gene( true );
	cerber_upgrade_settings( crb_array_get( $ver, 'v' ) );

	// The settings are in their final post-upgrade state: refresh the backup copy

	CRB_Settings_Backup::sync( CRB_Settings_Backup::CONTEXT_PLUGIN_UPGRADE );

	cerber_htaccess_sync( 'main' );
	cerber_htaccess_sync( 'media' );

	CRB_Deferred_Tasks::add( 'crb_download_translations', array( 'load_admin' => 1 ) );

	delete_site_option( '_cerber_report' );

	update_site_option( '_cerber_up', array( 'v' => CERBER_VER, 't' => time() ) );

	if ( $diff_version ) {
		cerber_push_the_news();
	}

	cerber_delete_expired_set( true );

    CRB_Cache::reset();

    if ( wp_next_scheduled( 'cerber_hourly' ) ) {
		wp_clear_scheduled_hook( 'cerber_hourly' ); // not in use since v. 5.8.
	}

	if ( ! CRB_Activity_Alerts::migrate() ) {
		cerber_error_log( 'Unable to migrate existing alerts to a new format.', 'ALERTS' );
	}

	// ----------------------------------------------------

	// Delete orphaned WP Cerber files

	$orphans = array(
		'/cerber-ripe.php',
		'/cerber-whois.php',
		'/net/cerber-ripe.php',
		'/net/cerber-whois.php',
		'/includes/User_Agent_Parser.php',
		'/jetflow.php',
		'/nexus/cerber-nexus-master.php',
		'/nexus/cerber-nexus-slave.php',
		'/nexus/cerber-slave-list.php',
		'/cerber-activity.php',
		'/cerber-addons.php',
	);

	foreach ( $orphans as $file ) {
		$delete = cerber_plugin_dir() . $file;
		if ( is_file( $delete ) && file_exists( $delete ) ) {
			unlink( $delete );
		}
	}

	// ----------------------------------------------------

	lab_get_key( true );

	CRB_Globals::$doing_upgrade = false;
	delete_site_transient( 'update_plugins' );

	$obsolete_sets = array( 'last_email_error' );
	foreach ( $obsolete_sets as $id ) {
		cerber_delete_set( $id );
	}

	@ini_set( 'display_errors', $d );
}

/**
 * Ensures all declared plugin tables exists and has proper schema (columns, indexes, etc.)
 * Runs schema maintenance for every table declared in CRB_Schema_Definitions.
 *
 * Return payload shape:
 *
 * array(
 *     'overall_status' => string,
 *     'tables_processed' => int,
 *     'tables_with_issues' => int,
 *     'table_results_by_name' => array<string, array<string, mixed>>,
 * )
 *
 * Possible 'overall_status' values:
 * - 'completed': all declared tables were processed and no table result had errors.
 * - 'completed_with_issues': all declared tables were processed, but at least one table result had errors.
 *
 * Possible returning errors:
 * - Propagates warp_get_db() errors when the database adapter cannot be obtained.
 * - 'schema_table_maintenance_issue': at least one table-level schema maintenance result had errors, see the full report for details.
 *
 * @return Revalt<array<string, mixed>> Contains the aggregate schema maintenance report and error data, if occured.
 *
 * @since 9.8.1
 */
function crb_ensure_all_table_schemas(): Revalt {
	$db_result = warp_get_db();

	if ( $db_result->has_errors() ) {
		return $db_result;
	}

	$result = new Revalt();
	$schema_manager = new CRB_Schema_Manager( $db_result->get_results() );

	$report = array(
		'overall_status'        => 'pending',
		'tables_processed'      => 0,
		'tables_with_issues'    => 0,
		'table_results_by_name' => array(),
	);

	foreach ( CRB_Schema_Definitions::get_tables() as $table_name => $table_definition ) {
		$table_name = (string) $table_name;

		$table_result = $schema_manager->ensure_table_schema( $table_name, $table_definition );

		$report['tables_processed'] ++;

		$report['table_results_by_name'][ $table_name ] = array(
			'has_errors'    => $table_result->has_errors(),
			'error_code'    => $table_result->get_error_code(),
			'error_message' => $table_result->get_error_message(),
			'report'        => $table_result->get_results( array() ),
		);

		if ( ! $table_result->has_errors() ) {
			continue;
		}

		$report['tables_with_issues'] ++;

		$result->add_error(
			'schema_table_maintenance_issue',
			'Schema maintenance reported an issue for table: ' . $table_name . '.',
			array(
				'table'         => $table_name,
				'error_code'    => $table_result->get_error_code(),
				'error_message' => $table_result->get_error_message(),
			)
		);
	}

	$report['overall_status'] = $report['tables_with_issues'] > 0
		? 'completed_with_issues'
		: 'completed';

	if ( $result->has_errors() ) {
		$result->put_results( $report );

		return $result;
	}

	$result->success( $report );

	return $result;
}

/**
 * Creates DB tables if they don't exist
 *
 * @param bool $recreate If true, recreate some tables completely (with data lost)
 *
 * @return array    Errors
 */
function cerber_create_db( $recreate = true ) {
	global $wpdb;

	crb_ensure_all_table_schemas();

	$wpdb->hide_errors();
	$db_errors = array();
	$sql = array();

	if ( ! cerber_db_is_table( CERBER_ACL_TABLE ) ) {
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . CERBER_ACL_TABLE . ' (
            ip varchar(39) CHARACTER SET ascii NOT NULL,
            tag char(1) NOT NULL,
            comments varchar(250) NOT NULL
	        ) DEFAULT CHARSET=utf8;
				';
	}

	if ( ! cerber_db_is_table( CRB_LOCKOUT_TABLE ) ) {
		$sql[] = '
	        CREATE TABLE IF NOT EXISTS ' . CRB_LOCKOUT_TABLE . ' (
		    ip varchar(39) CHARACTER SET ascii NOT NULL,
		    block_until bigint(20) unsigned NOT NULL,
		    reason varchar(250) NOT NULL,
		    reason_id int(11) unsigned NOT NULL DEFAULT "0",
		    UNIQUE KEY ip (ip)
			) DEFAULT CHARSET=utf8;			
				';
	}

	if ( ! cerber_db_is_table( CERBER_LAB_TABLE ) ) {
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . CERBER_LAB_TABLE . ' (
            ip varchar(39) CHARACTER SET ascii NOT NULL,
            reason_id int(11) unsigned NOT NULL DEFAULT "0",
            stamp bigint(20) unsigned NOT NULL,
            details text NOT NULL
			) DEFAULT CHARSET=utf8;
				';
	}


	if ( $recreate || ! cerber_db_is_table( CERBER_LAB_IP_TABLE ) ) {
		if ( $recreate && cerber_db_is_table( CERBER_LAB_IP_TABLE ) ) {
			$sql[] = 'DROP TABLE IF EXISTS ' . CERBER_LAB_IP_TABLE;
		}
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . CERBER_LAB_IP_TABLE . ' ( 
            ip varchar(39) CHARACTER SET ascii NOT NULL,
            reputation INT(11) UNSIGNED NOT NULL,
            expires INT(11) UNSIGNED NOT NULL, 
            PRIMARY KEY (ip)
			) DEFAULT CHARSET=utf8;
				';
	}

	if ( $recreate || ! cerber_db_is_table( CERBER_LAB_NET_TABLE ) ) {
		if ( $recreate && cerber_db_is_table( CERBER_LAB_NET_TABLE ) ) {
			$sql[] = 'DROP TABLE IF EXISTS ' . CERBER_LAB_NET_TABLE;
		}
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . CERBER_LAB_NET_TABLE . ' (
            ip varchar(39) CHARACTER SET ascii NOT NULL DEFAULT "",             
            ip_long_begin BIGINT UNSIGNED NOT NULL DEFAULT "0",
            ip_long_end BIGINT UNSIGNED NOT NULL DEFAULT "0",
            country CHAR(3) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT "",
            expires INT(11) UNSIGNED NOT NULL DEFAULT "0", 
            PRIMARY KEY (ip),
            KEY begin_end (ip_long_begin, ip_long_end)
			) DEFAULT CHARSET=utf8;
				';
	}

	if ( ! cerber_db_is_table( CERBER_GEO_TABLE ) ) {
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . CERBER_GEO_TABLE . ' (
            country CHAR(3) NOT NULL DEFAULT "" COMMENT "Country code",
            locale CHAR(10) NOT NULL DEFAULT "" COMMENT "Locale i18n",
            country_name VARCHAR(250) NOT NULL DEFAULT "",
            PRIMARY KEY (country, locale)
			) DEFAULT CHARSET=utf8;
				';
	}

	if ( ! cerber_db_is_table( cerber_get_db_prefix() . CRB_SCANFILES_TABLE ) ) {
		$sql[] = '
		    CREATE TABLE IF NOT EXISTS ' . cerber_get_db_prefix() . CRB_SCANFILES_TABLE . ' (
            scan_id INT(10) UNSIGNED NOT NULL,
            scan_type INT(10) UNSIGNED NOT NULL DEFAULT 1,
            scan_mode INT(10) UNSIGNED NOT NULL DEFAULT 0,
            scan_status INT(10) UNSIGNED NOT NULL DEFAULT 0,
            file_name_hash VARCHAR(255) CHARACTER SET ascii NOT NULL DEFAULT "",
            file_name TEXT NOT NULL,
            file_type INT(10) UNSIGNED NOT NULL DEFAULT 0,
            file_hash VARCHAR(255) CHARACTER SET ascii NOT NULL DEFAULT "",
            file_md5 VARCHAR(255) CHARACTER SET ascii NOT NULL DEFAULT "",
            file_hash_repo VARCHAR(255) CHARACTER SET ascii NOT NULL DEFAULT "",
            hash_match INT(10) UNSIGNED NOT NULL DEFAULT 0,
            file_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            file_perms INT(11) NOT NULL DEFAULT 0,
            file_writable INT(10) UNSIGNED NOT NULL DEFAULT 0,
            file_mtime INT(10) UNSIGNED NOT NULL DEFAULT 0,
            extra TEXT NOT NULL,
            PRIMARY KEY (scan_id, file_name_hash)
            ) DEFAULT CHARSET=utf8;
        ';
	}

	if ( ! cerber_db_is_table( cerber_get_db_prefix() . CERBER_SETS_TABLE ) ) {
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' (          
            the_key VARCHAR(255) CHARACTER SET ascii NOT NULL,
            the_id BIGINT(20) NOT NULL DEFAULT 0,
            the_value LONGTEXT NOT NULL,
            expires BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (the_key, the_id)
			) DEFAULT CHARSET=utf8;
        ';
	}

	if ( ! cerber_db_is_table( CERBER_QMEM_TABLE ) ) {
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . CERBER_QMEM_TABLE . ' (            
            ip varchar(39) CHARACTER SET ascii NOT NULL,
            http_code int(10) UNSIGNED NOT NULL,
            stamp int(10) UNSIGNED NOT NULL,
            KEY ip_stamp (ip, stamp)
			) DEFAULT CHARSET=utf8;
    			';
	}

	if ( ! cerber_db_is_table( cerber_get_db_prefix() . CERBER_USS_TABLE ) ) {
		$sql[] = '
            CREATE TABLE IF NOT EXISTS ' . cerber_get_db_prefix() . CERBER_USS_TABLE . ' (
            user_id bigint(20) UNSIGNED NOT NULL,
            ip varchar(39) CHARACTER SET ascii NOT NULL,
            country char(3) CHARACTER SET ascii NOT NULL DEFAULT "",
            started int(10) UNSIGNED NOT NULL,
            expires int(10) UNSIGNED NOT NULL,
            session_id char(32) CHARACTER SET ascii NOT NULL DEFAULT "",
            wp_session_token varchar(250) CHARACTER SET ascii NOT NULL,             
            KEY user_id (user_id)
			) DEFAULT CHARSET=utf8;
    			';
	}

	foreach ( $sql as $query ) {
		$query = str_replace( '"', '\'', $query );
		if ( ! $wpdb->query( $query ) && $wpdb->last_error ) {
			$db_errors[] = array( $wpdb->last_error, $wpdb->last_query );
		}
	}

	return $db_errors;
}

/**
 * Upgrade structure of existing DB tables
 *
 * @return array Errors occurred during upgrading database tables
 *
 * @see cerber_upgrade_db_deferred()
 *
 * @since 3.0
 */
function cerber_upgrade_db( $force = false ) {

	CRB_Deferred_Tasks::add( 'cerber_upgrade_db_deferred' );

	crb_ensure_all_table_schemas();

	// Obtain a schema manager for idempotent, self-validating index operations.
	// Index drops below run through it, so any failure is logged via the Revalt mechanism.
	$schema_manager = null;
	$schema_db_result = warp_get_db();

	if ( ! $schema_db_result->has_errors() ) {
		$schema_manager = new CRB_Schema_Manager( $schema_db_result->get_results() );
	}

	$sql = array();

	// @since 3.0
	if ( $force || ! cerber_db_check_column_type( CERBER_LOG_TABLE, 'stamp', 'decimal', [ 'numeric_precision' => 14, 'numeric_scale' => 4 ] ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . ' MODIFY COLUMN stamp DECIMAL(14,4) NOT NULL';
	}

	// @since 3.1
	if ( $force || ! cerber_db_is_column( CERBER_LOG_TABLE, 'ip_long' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . ' ADD ip_long BIGINT UNSIGNED NOT NULL DEFAULT "0" AFTER ip, ADD INDEX (ip_long)';
	}
	if ( $force || ! cerber_db_is_column( CERBER_ACL_TABLE, 'ip_long_begin' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_ACL_TABLE . ' ADD ip_long_begin BIGINT UNSIGNED NOT NULL DEFAULT "0" AFTER ip, ADD ip_long_end BIGINT UNSIGNED NOT NULL DEFAULT "0" AFTER ip_long_begin';
	}
	/*if ( $force || !cerber_is_index( CERBER_ACL_TABLE, 'ip_begin_end' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_ACL_TABLE . ' ADD UNIQUE ip_begin_end (ip, ip_long_begin, ip_long_end)';
	}*/
	if ( $schema_manager ) {
		$schema_manager->drop_index_if_exists( CERBER_ACL_TABLE, 'ip' );
	}

	// @since 4.8.2
	if ( $schema_manager ) {
		$schema_manager->drop_index_if_exists( CERBER_ACL_TABLE, 'begin_end' );
	}
	/*if ( $force || !cerber_is_index( CERBER_ACL_TABLE, 'begin_end_tag' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_ACL_TABLE . ' ADD INDEX begin_end_tag (ip_long_begin, ip_long_end, tag)';
	}*/

	// @since 4.9
	if ( $force || ! cerber_db_is_column( CERBER_LOG_TABLE, 'session_id' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . ' 
        ADD session_id CHAR(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT "",
        ADD country CHAR(3) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT "",
        ADD details VARCHAR(250) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT "";
      ';
	}

	// @since 6.1
	if ( $force || ! cerber_db_is_index( CERBER_LOG_TABLE, 'session_index' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . ' ADD INDEX session_index (session_id)';
	}

	// @since 7.0.3
	$sql[] = 'DROP TABLE IF EXISTS ' . CRB_SCANFILES_TABLE;

	// @since 7.1
	if ( $force || ! cerber_db_is_column( cerber_get_db_prefix() . CRB_SCANFILES_TABLE, 'file_status' ) ) {
		$sql[] = 'ALTER TABLE ' . cerber_get_db_prefix() . CRB_SCANFILES_TABLE . " ADD file_status INT UNSIGNED NOT NULL DEFAULT '0' AFTER scan_status";
	}

	// @since 7.5.2
	if ( $force || ! cerber_db_is_column( CRB_LOCKOUT_TABLE, 'reason_id' ) ) {
		$sql[] = 'ALTER TABLE ' . CRB_LOCKOUT_TABLE . ' ADD reason_id int(11) unsigned NOT NULL DEFAULT "0"';
	}

	// @since 7.8.6
	if ( $force || ! cerber_db_is_column( CERBER_TRAF_TABLE, 'php_errors' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_TRAF_TABLE . ' ADD php_errors TEXT NOT NULL AFTER blog_id';
	}

	// @since 8.5.4
	if ( $force || ! cerber_db_is_column( CERBER_ACL_TABLE, 'ver6' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_ACL_TABLE . '
		ADD acl_slice SMALLINT UNSIGNED NOT NULL DEFAULT 0, 
		ADD ver6 SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		ADD v6range VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT "",
		ADD req_uri VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT "",
		MODIFY COLUMN ip VARCHAR(81) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL
		';
	}
	if ( $force || ! cerber_db_is_index( CERBER_ACL_TABLE, 'main_for_selects' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_ACL_TABLE . ' ADD INDEX main_for_selects (acl_slice, ver6, ip_long_begin, ip_long_end, tag)';
	}
	if ( $schema_manager ) {
		$schema_manager->drop_index_if_exists( CERBER_ACL_TABLE, 'begin_end_tag' );
		$schema_manager->drop_index_if_exists( CERBER_ACL_TABLE, 'ip_begin_end' );
	}

	// @since 8.6.4
	if ( $force || ! cerber_db_is_column( cerber_get_db_prefix() . CRB_SCANFILES_TABLE, 'file_ext' ) ) {
		$sql[] = 'ALTER TABLE ' . cerber_get_db_prefix() . CRB_SCANFILES_TABLE . '
		ADD file_ext VARCHAR(255) NOT NULL DEFAULT "" AFTER file_mtime
		';
	}
	if ( $force || ! cerber_db_is_column( CERBER_TRAF_TABLE, 'req_status' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_TRAF_TABLE . ' ADD req_status int(10) UNSIGNED NOT NULL DEFAULT 0';
	}

	// @since 8.8.6.2
	if ( $force || ! cerber_db_is_column( cerber_get_db_prefix() . CRB_SCANFILES_TABLE, 'scan_step' ) ) {
		$sql[] = 'ALTER TABLE ' . cerber_get_db_prefix() . CRB_SCANFILES_TABLE . '
		ADD scan_step INT UNSIGNED NOT NULL DEFAULT 0 AFTER scan_mode
		';
	}

	// @since 8.9.4
	if ( $force || ! cerber_db_is_column( CERBER_LOG_TABLE, 'ac_status' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . '
		ADD ac_bot int(10) UNSIGNED NOT NULL DEFAULT 0,
		ADD ac_status int(10) UNSIGNED NOT NULL DEFAULT 0,
		ADD ac_by_user bigint(20) UNSIGNED NOT NULL DEFAULT 0';
	}

	// @since 9.4.2
	// Add maintenance fields
	if ( $force || ! cerber_db_is_column( cerber_get_db_prefix() . CERBER_SETS_TABLE, 'argo' ) ) {
		$sql[] = 'ALTER TABLE ' . cerber_get_db_prefix() . CERBER_SETS_TABLE . ' ADD argo int(10) UNSIGNED NOT NULL DEFAULT 0';
	}

	// @since 9.6.3.2
	if ( $force || ! cerber_db_is_column( cerber_get_db_prefix() . CERBER_USS_TABLE, 'mfa_status' ) ) {
		$sql[] = 'ALTER TABLE ' . cerber_get_db_prefix() . CERBER_USS_TABLE . '
		ADD mfa_status int(10) UNSIGNED NOT NULL DEFAULT 0';
	}

	// @since 9.6.7
	if ( $force || ! cerber_db_is_column( CRB_LOCKOUT_TABLE, 'session_id' ) ) {
		$sql[] = 'ALTER TABLE ' . CRB_LOCKOUT_TABLE . ' ADD session_id CHAR(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT ""';
	}

	// @since 9.7.4
	if ( $force || ! cerber_db_is_column( CERBER_LOG_TABLE, 'ipv6_net_64' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . ' ADD COLUMN ipv6_net_64 BINARY(8) NULL DEFAULT NULL AFTER ip_long';
	}
	if ( $force || ! cerber_db_is_index( CERBER_LOG_TABLE, 'idx_ipv6_net_64' ) ) {
		$sql[] = 'ALTER TABLE ' . CERBER_LOG_TABLE . ' ADD INDEX idx_ipv6_net_64 (ipv6_net_64)';
	}

	if ( ! empty( $sql ) ) {
		foreach ( $sql as $query ) {
			$query = str_replace( '"', '\'', $query );
			cerber_db_query( $query );
		}
	}

	cerber_acl_fixer();

	$db_errors = cerber_db_get_errors();

	if ( ! $force && $db_errors ) {
		CRB_Issues::register( __FUNCTION__, __( 'Database errors occurred while upgrading WP Cerber database tables.', 'wp-cerber' ), array( 'section' => 'upgrading_db', 'type' => CRB_Issues::TYPE_EVENT, 'context' => $db_errors ) );
		cerber_db_error_log( cerber_db_get_errors( true, false ) );
	}

	return $db_errors;
}

/**
 * All upgrade procedures that can be executed via a background or scheduled task
 *
 * @see cerber_upgrade_db()
 *
 * @since 8.8
 */
function cerber_upgrade_db_deferred() {

	$db_result = warp_get_db();

	if ( $db_result->has_errors() ) {
		return;
	}

	$db = $db_result->get_results();

	$schema = new CRB_Schema_Manager( $db );

	// Migrate the request_fields collation to utf8mb4_unicode_ci on utf8mb4 installs.
	list( $charset, $collate ) = cerber_db_detect_collate();

	if ( $charset === 'utf8mb4' ) {
		$columns_result = $schema->get_metadata(
			CRB_Schema_Manager::META_FULL_COLUMNS,
			array(
				'table' => CERBER_TRAF_TABLE,
				'field' => 'request_fields',
			)
		);

		if ( ! $columns_result->has_errors() ) {
			// get_metadata() returns an indexed list of rows keyed by original MySQL column names.
			$col_info = $columns_result->get_element( 0, array() );

			if ( $col_info && 'utf8mb4_unicode_ci' !== ( $col_info['Collation'] ?? '' ) ) {
				$db->query(
					'ALTER TABLE ' . CERBER_TRAF_TABLE
					. ' MODIFY COLUMN request_fields ' . $col_info['Type']
					. ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
				);
			}
		}
	}

	// Replace the unique index to support IPv6. The legacy index may already be gone,
	// so the idempotent drop treats an absent index as success; its outcome is intentionally not checked.
	$schema->drop_index_if_exists( CERBER_LAB_NET_TABLE, 'begin_end' );

	$schema->ensure_index(
		CERBER_LAB_NET_TABLE,
		'begin_end_long',
		array( 'ip_long_begin', 'ip_long_end' )
	);
}

function cerber_db_detect_collate() {
	global $wpdb;

	if ( ! $wpdb->charset ) {
		$wpdb->init_charset();
	}

	if ( 'utf8mb4' === $wpdb->charset || ( ! $wpdb->charset && $wpdb->has_cap( 'utf8mb4' ) ) ) {
		$charset = 'utf8mb4';
		$collate = 'utf8mb4_unicode_ci';
	}
	else {
		$charset = 'utf8';
		$collate = 'utf8_general_ci';
	}

	return array( $charset, $collate );
}

/**
 * Returns the list of all WP Cerber database tables
 *
 * @return string[]
 */
function cerber_get_tables() {
	return array(
		CERBER_LOG_TABLE,
		CERBER_QMEM_TABLE,
		CERBER_TRAF_TABLE,
		CERBER_ACL_TABLE,
		CRB_LOCKOUT_TABLE,
		CERBER_LAB_TABLE,
		CERBER_LAB_IP_TABLE,
		CERBER_LAB_NET_TABLE,
		CERBER_GEO_TABLE,
		cerber_get_db_prefix() . CRB_SCANFILES_TABLE,
		cerber_get_db_prefix() . CERBER_SETS_TABLE,
		cerber_get_db_prefix() . CERBER_MS_TABLE,
		cerber_get_db_prefix() . CERBER_MS_LIST_TABLE,
		cerber_get_db_prefix() . CERBER_USS_TABLE,
	);
}

/**
 * Upgrade outdated / corrupted rows in ACL
 *
 */
function cerber_acl_fixer() {

	$ips = cerber_db_get_col( 'SELECT ip FROM ' . CERBER_ACL_TABLE . ' WHERE ip_long_begin = 0 OR ip_long_end = 0 OR ip_long_begin = 7777777777' );

	if ( ! $ips ) {
		return;
	}

	foreach ( $ips as $ip ) {

		// Code from cerber_acl_add()

		$v6range = '';
		$ver6 = 0;

		if ( cerber_is_ipv4( $ip ) ) {
			$begin = ip2long( $ip );
			$end = ip2long( $ip );
		}
        elseif ( cerber_is_ipv6( $ip ) ) {
			$ip = crb_normalize_ipv6( $ip );
			list( $begin, $end, $v6range ) = crb_ipv6_prepare( $ip, $ip );
			$ver6 = 1;
		}
        elseif ( ( $range = cerber_any2range( $ip ) )
		         && is_array( $range ) ) {
			$ver6 = $range['IPV6'];
			$begin = $range['begin'];
			$end = $range['end'];
			$v6range = $range['IPV6range'];
		}
		else {
			continue;
		}

		$set = 'ip_long_begin = ' . $begin . ', ip_long_end = ' . $end . ', ver6 = ' . $ver6 . ', v6range = "' . $v6range . '"  WHERE ip = "' . $ip . '"';

		cerber_db_query( 'UPDATE ' . CERBER_ACL_TABLE . ' SET ' . $set );
	}
}

add_action( 'deac' . 'tivate_' . CERBER_PLUGIN_ID, function () {
	wp_clear_scheduled_hook( 'cerber_bg_launcher' );
	wp_clear_scheduled_hook( 'cerber_hourly_1' );
	wp_clear_scheduled_hook( 'cerber_hourly_2' );
	wp_clear_scheduled_hook( 'cerber_daily' );
	wp_clear_scheduled_hook( 'cerber_scheduled_hash' );

	cerber_htaccess_clean_up();
	cerber_set_boot_mode( 0 );
	cerber_delete_expired_set( true );
	cerber_delete_set( 'plugins_done' );
	cerber_delete_set( '_background_tasks' );

	$pi = array();
	$pi['Version'] = CERBER_VER;
	$pi['v'] = time();
	$pi['u'] = get_current_user_id();
	// @since 9.4.2
	$pi['ip'] = cerber_get_remote_ip();
	$pi['ua'] = crb_array_get( $_SERVER, 'HTTP_USER_AGENT', '' );
	cerber_update_set( '_cerber_off', $pi );

	CRB_Messaging::send( 'shutdown' );

	CRB_Cache::reset();

	crb_event_handler( 'deactivated', array() );
} );

/**
 * Fixing an issue with the empty user_id field in the WordPress comments table.
 * We use it to count comments for the Users page.
 *
 */
add_filter( 'preprocess_comment', 'cerber_add_uid' );
function cerber_add_uid( $commentdata ) {
	$current_user = wp_get_current_user();
	$commentdata['user_ID'] = $current_user->ID;

	return $commentdata;
}

/**
 * Load jQuery on the page
 *
 */
add_action( 'login_enqueue_scripts', 'cerber_login_scripts' );
function cerber_login_scripts() {
	if ( cerber_antibot_enabled( array( 'botsreg', 'botsany' ) ) ) {
		wp_enqueue_script( 'jquery' );
	}
}

add_action( 'wp_enqueue_scripts', 'cerber_scripts' );
function cerber_scripts() {
	if ( ( ( is_singular() || is_archive() ) && cerber_antibot_enabled( array( 'botscomm', 'botsany' ) ) )
	     || ( crb_get_settings( 'sitekey' ) && crb_get_settings( 'secretkey' ) )
	) {
		wp_enqueue_script( 'jquery' );
	}
}

/**
 * Footer stuff like JS code
 * Explicit rendering reCAPTCHA
 *
 */
add_action( 'login_footer', 'cerber_login_register_stuff', 1000 );
function cerber_login_register_stuff() {

	cerber_antibot_code( array( 'botsreg', 'botsany' ) );

	if ( ! get_wp_cerber()->recaptcha_here ) {
		return;
	}

	// Universal JS

	if ( ! crb_get_settings( 'invirecap' ) ) {
		// Classic version (visible reCAPTCHA)
		echo '<script src = https://www.google.com/recaptcha/api.js?hl=' . cerber_recaptcha_lang() . ' async defer></script>';
	}
	else {
		// Pure JS version with explicit rendering
		?>

        <script src="https://www.google.com/recaptcha/api.js?onload=init_recaptcha_widgets&render=explicit&hl=<?php echo cerber_recaptcha_lang(); ?>" async defer></script>

        <script type='text/javascript'>

            document.getElementById("cerber-recaptcha").remove();

            var init_recaptcha_widgets = function () {
                for (var i = 0; i < document.forms.length; ++i) {
                    var form = document.forms[i];
                    var place = form.querySelector('.cerber-form-marker');
                    if (null !== place) render_recaptcha_widget(form, place);
                }
            };

            function render_recaptcha_widget(form, place) {
                var place_id = grecaptcha.render(place, {
                    'callback': function (g_recaptcha_response) {
                        HTMLFormElement.prototype.submit.call(form);
                    },
                    'sitekey': '<?php echo crb_get_settings( 'sitekey' ); ?>',
                    'size': 'invisible',
                    'badge': 'bottomright'
                });

                form.onsubmit = function (event) {
                    event.preventDefault();
                    grecaptcha.execute(place_id);
                };

            }
        </script>
		<?php
	}
}

/**
 * Add Cerber's JS to the footer on the public pages
 *
 */
add_action( 'wp_footer', 'cerber_wp_footer', PHP_INT_MAX );
function cerber_wp_footer() {

	if ( is_singular() || is_archive() ) {
		cerber_antibot_code( array( 'botscomm', 'botsany' ) );
	}

	if ( ! get_wp_cerber()->recaptcha_here ) {
		return;
	}

	// jQuery version with support visible and invisible reCAPTCHA

	?>
    <script type="text/javascript">

        jQuery(function ($) {

            let recaptcha_ok = false;
            let the_recaptcha_widget = $("#cerber-recaptcha");
            let is_recaptcha_visible = ($(the_recaptcha_widget).data('size') !== 'invisible');

            let the_form = $(the_recaptcha_widget).closest("form");
            let the_button = $(the_form).find('input[type="submit"]');
            if (!the_button.length) {
                the_button = $(the_form).find(':button');
            }

            // visible
            if (the_button.length && is_recaptcha_visible) {
                the_button.prop("disabled", true);
                the_button.css("opacity", 0.5);
            }

            window.form_button_enabler = function () {
                if (!the_button.length) return;
                the_button.prop("disabled", false);
                the_button.css("opacity", 1);
            };

            // invisible
            if (!is_recaptcha_visible) {
                $(the_button).on('click', function (event) {
                    if (recaptcha_ok) return;
                    event.preventDefault();
                    grecaptcha.execute();
                });
            }

            window.now_submit_the_form = function () {
                recaptcha_ok = true;
                //$(the_button).click(); // this is only way to submit a form that contains "submit" inputs
                $(the_button).trigger('click'); // this is only way to submit a form that contains "submit" inputs
            };
        });
    </script>
    <script src="https://www.google.com/recaptcha/api.js?hl=<?php echo cerber_recaptcha_lang(); ?>" async defer></script>
	<?php
}

register_shutdown_function( function () {

	// Take the snapshot of the last reported error before any code below can overwrite it.
	// PHP keeps a single "last error" slot, and CRB_Bug_Hunter::error_handler() returns false,
	// which lets the internal PHP handler record every subsequent diagnostic, including the
	// ones silenced with @ and the ones below the error_reporting level. The routines called
	// further down query the database and send HTTP requests, so without this snapshot a fatal
	// error would be replaced with an unrelated warning and lost. Shutdown callbacks registered
	// earlier still run before this one, so the snapshot covers the window created here rather
	// than the whole shutdown stage.
	$terminating_error = error_get_last();

	if ( cerber_is_plugin_uninstalling() ) {
		return;
	}

	// Step 1
	cerber_extra_vision();

	// Step 2
	cerber_error_shield();

	cerber_push_lab();

	// Do not change the order of this sequence

	CRB_Bug_Hunter::handle_shutdown_error( $terminating_error );

	cerber_traffic_log();

	if ( crb_get_settings( 'log_crb_errors' ) ) {
		CRB_Bug_Hunter::save_errors();
	}

	// ----------------------------------------
} );

/**
 * Detects if the current admin AJAX request is legitimately uninstalling/updating/installing plugins
 *
 * @return bool True if the current request is a verified core plugin file operation (delete/update/install)
 *
 * @since 9.6.10
 */
function cerber_is_plugin_uninstalling(): bool {

	if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX )
	     || ! cerber_is_http_post() ) {
		return false;
	}

	$action = $_REQUEST['action'] ?? '';
	if ( ! in_array( $action, [ 'delete-plugin', 'update-plugin', 'install-plugin' ], true ) ) {
		return false;
	}

	if ( ! $nonce = $_REQUEST['_ajax_nonce'] ?? '' ) {
		return false;
	}

	if ( ! function_exists( 'wp_verify_nonce' ) ) {
		return false;
	}

	if ( ! wp_verify_nonce( (string) $nonce, 'updates' ) ) {
		return false;
	}

	return true;
}

/**
 * Monitoring erroneous requests
 *
 * @return void
 */
function cerber_error_shield() {

	$mode = crb_get_settings( 'tierrmon' );

	if ( ! $mode
	     || 400 > http_response_code()
	     || cerber_is_wp_cron()
	     || ( cerber_is_wp_ajax() && ( 400 == http_response_code() ) ) ) {

		return;
	}

	if ( ( crb_get_settings( 'tierrnoauth' )
	       && crb_is_user_logged_in() ) ) {
		return;
	}

	$ip = cerber_get_remote_ip();

	if ( cerber_block_check( $ip ) ) {
		return;
	}

	if ( $mode == 1 ) { // safe mode
		$time = 120;
		$limit = 10;
		$codes = array( 404 );
	}
	else {
		$time = 300;
		$limit = 5;
		$codes = array();
	}

	$code = http_response_code();

	if ( $codes
	     && in_array( $code, $codes ) ) {
		return;
	}

	$go = false;

	if ( cerber_is_http_post() ) {
		$go = true;
	}

	if ( ! $go && cerber_get_uri_script() ) {
		$go = true;
	}

	if ( ! $go ) {
		if ( $mode == 1 ) {
			if ( cerber_get_non_wp_fields() ) {
				$go = true;
			}
		}
		else {
			if ( ! empty( $_GET ) ) {
				$go = true;
			}
		}
	}

	if ( ! $go && cerber_is_rest_url() ) {
		$go = true;
	}

	if ( ! $go ) {
		return;
	}

	cerber_db_query( 'INSERT INTO ' . CERBER_QMEM_TABLE . ' (ip, http_code, stamp) 
	        VALUES ("' . $ip . '",' . intval( http_response_code() ) . ',' . time() . ')' );

	if ( ! CRB_Globals::$blocked ) {
		$t = time() - $time;
		$c = cerber_db_get_var( 'SELECT COUNT(ip) FROM ' . CERBER_QMEM_TABLE . ' WHERE  ip = "' . $ip . '" AND stamp > ' . $t );
		if ( $c >= $limit ) {
			CRB_Globals::set_ctrl_setting( 'tierrmon' );
			crb_apply_soft_ip_lockout( $ip, 711 );
			CRB_Globals::set_act_status( 18 );
		}
	}

}

/**
 * Logs the current HTTP request to the Traffic Inspector database table.
 *
 * Determines the WordPress request context, collects selected request metadata,
 * optionally captures request fields, headers, cookies, server variables,
 * response headers, redirect details, and collected PHP errors, then stores the
 * assembled traffic log entry in the traffic log table.
 *
 * The function is intended to run once per request. It skips logging for WP-CLI
 * executions, WP Cerber cloud requests, requests excluded by Traffic Inspector
 * rules, and bot requests when crawler logging is disabled.
 *
 *
 * @return void
 * @global float|int|string|null $wp_cerber_start_stamp Optional request start timestamp.
 * @global int|string|null $blog_id Current site ID in multisite installations.
 *
 * @global WP_Query|null $wp_query Current WordPress query object, when available.
 */
function cerber_traffic_log() {
	global $wp_query, $wp_cerber_start_stamp, $blog_id;
	static $done = false;

	if ( $done
	     || ( defined( 'WP_CLI' ) && WP_CLI ) // @since 9.5.7.4
	     || cerber_is_cloud_request() ) {
		return;
	}

	$wp_cerber = get_wp_cerber();

	$wp_type = 700;

	if ( cerber_is_wp_ajax() ) {
		/*
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'heartbeat' ) {
			return;
		}*/
		$wp_type = 500;
	}
    elseif ( is_admin() ) {
		$wp_type = 501;
	}
    elseif ( cerber_is_wp_cron() ) {
		$wp_type = 502;
	}
    elseif ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		$wp_type = 515;
	}
    elseif ( cerber_is_rest_url() ) {
		$wp_type = 520;
	}
	// Public part starts with 600
    elseif ( $wp_query && is_object( $wp_query ) ) {
		$wp_type = 600;
		if ( $wp_query->is_singular ) {
			$wp_type = 601;
		}
        elseif ( $wp_query->is_tag ) {
			$wp_type = 603;
		}
        elseif ( $wp_query->is_category ) {
			$wp_type = 604;
		}
        elseif ( $wp_query->is_search ) {
			$wp_type = 605;
		}
	}

	if ( function_exists( 'http_response_code' ) ) {
		$http_code = http_response_code();
	}
	else {
		$http_code = 200;
		if ( $wp_type > 600 ) {
			if ( $wp_query->is_404 ) {
				$http_code = 404;
			}
		}
	}

	$user_id = 0;

	if ( function_exists( 'get_current_user_id' ) ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id && CRB_Globals::$user_id ) {
		$user_id = absint( CRB_Globals::$user_id );
	}

	if ( ! cerber_to_log( $wp_type, $http_code, $user_id ) ) {
		return;
	}

	$done = true;

	if ( crb_is_log_exception() ) {
		return;
	}

	if ( $ua = crb_array_get( $_SERVER, 'HTTP_USER_AGENT', '' ) ) {
		$ua = substr( $ua, 0, 1000 );
	}

	$bot = cerber_is_crawler( $ua );
	if ( $bot && crb_get_settings( 'tinocrabs' ) ) {
		return;
	}

	$ip = cerber_get_remote_ip();
	$ip_long = 0;
	if ( cerber_is_ipv4( $ip ) ) {
		$ip_long = ip2long( $ip );
	}

	$wp_id = 0;
	if ( $wp_query && is_object( $wp_query ) ) {
		$wp_id = absint( $wp_query->get_queried_object_id() );
	}

	$session_id = $wp_cerber->getRequestID();

	$scheme = is_ssl() ? 'https' : 'http';
	$host = (string) ( $_SERVER['HTTP_HOST'] ?? '' );
	$path = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
	$uri = $scheme . '://' . $host . $path;

	// Prevent log forging

	$uri = str_replace(
		array( "\r", "\n", "\t" ),
		array( '\\r', '\\n', '\\t' ),
		$uri
	);

	$method = crb_sanitize_alphanum( $_SERVER['REQUEST_METHOD'] ?? '' );

	// Request fields

	$request_fields = '';

	if ( crb_get_settings( 'tifields' ) ) {

		$fields = array();

		if ( ! empty( $_POST ) ) {
			$fields[1] = cerber_prepare_fields( cerber_mask_fields( (array) $_POST ) );
		}

		if ( ! empty( $_GET ) ) {
			$fields[2] = cerber_prepare_fields( (array) $_GET );
		}

		if ( ! empty( $_FILES ) ) {
			$fields[3] = $_FILES;
		}

		if ( CRB_Request::is_json_header()
		     && $request_body_raw = file_get_contents( 'php://input' ) ) {
			$json = json_decode( $request_body_raw, true );
			if ( JSON_ERROR_NONE === json_last_error()
			     && is_array( $json ) ) {
				$fields[4] = cerber_prepare_fields( cerber_mask_fields( $json ) );
			}
		}

		if ( ! empty( $fields ) ) {
			$request_fields = json_encode( $fields, JSON_UNESCAPED_UNICODE );
		}
	}

	// Extra request details

	$details = array();
	$details[1] = $ua;

	if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		//$ref = mb_substr( $_SERVER['HTTP_REFERER'], 0, 1048576 ); // 1 Mb for ASCII
		$details[2] = filter_var( $_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL );
	}

	if ( $wp_type == 605 && ! empty( $_GET['s'] ) ) {
		$details[4] = $_GET['s'];
	}
	if ( $wp_type == 515 ) {
		// TODO: add a setting to enable it because there is a user/password in the php://input
		//$details[5] = file_get_contents('php://input');
	}
	if ( crb_get_settings( 'tihdrs' ) ) {
		$hds = crb_getallheaders();
		unset( $hds['Cookie'] );
		unset( $hds['cookie'] );
		ksort( $hds );
		$details[6] = $hds;
	}
	if ( crb_get_settings( 'tisenv' ) ) {
		$srv = $_SERVER;
		unset( $srv['HTTP_COOKIE'] );
		ksort( $srv );
		$details[7] = $srv;
	}
	if ( crb_get_settings( 'ticandy' ) && ! empty( $_COOKIE ) ) {
		$details[8] = $_COOKIE;
		ksort( $details[8] );
	}

	$cs = crb_get_settings( 'ticandy_sent' );
	$hs = crb_get_settings( 'tihdrs_sent' );

	if ( $all_server_headers = headers_list() ) {

		if ( $cs || $hs ) {
			$server_cookies = array();
			$server_headers = array();

			foreach ( $all_server_headers as $header ) {
				if ( 0 === stripos( $header, 'Set-Cookie:' ) ) {
					$server_cookies[] = substr( $header, 11 );
				}
				else {
					$server_headers[] = $header;
				}
			}

			if ( $cs ) {
				$details[9] = $server_cookies;
			}

			if ( $hs ) {
				$details[10] = $server_headers;
			}
		}

		// We always save the redirection URL, if it was sent

		if ( ! $hs ) {
			foreach ( $all_server_headers as $header ) {
				if ( 0 === stripos( $header, 'Location:' ) ) {
					$details[10] = array( $header );

					break;
				}
			}
		}

	}

	if ( $redirected = CRB_Globals::$redirect_url ) {
		$details[11] = mb_substr( $redirected, 0, 1024 );
	}

	if ( ! empty( $details ) ) {
		$details = cerber_prepare_fields( $details );
		$request_details = json_encode( $details, JSON_UNESCAPED_UNICODE );
	}
	else {
		$request_details = '';
	}

	// Software errors

	$php_err = '';

	if ( crb_get_settings( 'tiphperr' )
	     && CRB_Bug_Hunter::has_errors() ) {

		$php_errors = CRB_Bug_Hunter::get_collected_errors();

		// Filter out known false positives from WP core to reduce log noise

		foreach ( $php_errors as $key => $err ) {
			// A special case for is_readable() in class-phpass.php
			if ( $err[0] == E_WARNING ) {
				if ( '/wp-includes/class-phpass.php' == substr( $err[2], - 29 )
				     && strpos( $err[1], '/dev/urandom' ) ) {
					unset( $php_errors[ $key ] );
				}
			}
		}

		// Prepare errors for saving to the DB as a JSON string

		if ( $php_errors ) {

			$php_errors = array_slice( $php_errors, 0, 25 ); // A healthy limit

			foreach ( $php_errors as &$row ) {
				array_splice( $row, 4 );
			}
			unset( $row );

			$php_err = json_encode( $php_errors, JSON_UNESCAPED_UNICODE );

			if ( $php_err === false ) {
				$php_err = '';
			}
		}
	}

	// Timestamps
	if ( ! empty( $wp_cerber_start_stamp ) && is_numeric( $wp_cerber_start_stamp ) ) {
		$start = (float) $wp_cerber_start_stamp; // define this variable: $wp_cerber_start_stamp = microtime( true ); in wp-config.php
	}
	else {
		$start = cerber_request_time();
	}

	$processing = (int) ( 1000 * ( microtime( true ) - $start ) );

	$uri = cerber_db_real_escape( $uri );
	$request_details = cerber_db_real_escape( $request_details );
	$request_fields = cerber_db_real_escape( $request_fields );
	$php_err = cerber_db_real_escape( $php_err );

	if ( ! $req_status = absint( CRB_Globals::$req_status ) ) {
		if ( crb_acl_is_allowed() ) {
			$req_status = 510;
		}
	}

	$query = 'INSERT INTO ' . CERBER_TRAF_TABLE . ' 
	(ip, ip_long, uri, request_fields , request_details, session_id, user_id, stamp, processing, request_method, http_code, wp_id, wp_type, is_bot, blog_id, php_errors, req_status ) 
	VALUES ("' . $ip . '", ' . $ip_long . ',"' . $uri . '","' . $request_fields . '","' . $request_details . '", "' . $session_id . '", ' . $user_id . ', ' . $start . ',' . $processing . ', "' . $method . '", ' . $http_code . ',' . $wp_id . ', ' . $wp_type . ', ' . $bot . ', ' . absint( $blog_id ) . ',"' . $php_err . '",' . $req_status . ')';

	$ret = cerber_db_query( $query );

	if ( ! $ret ) {
		//cerber_diag_log( print_r( cerber_db_get_errors(), 1 ) );

		// mysqli_error($wpdb->dbh);

		// TODO: Daily software error report
		/*
		echo mysqli_sqlstate($wpdb->dbh);
		echo $wpdb->last_error;
		echo "<p>\n";
		echo $uri;
		echo "<p>\n";
		echo '<p>ERR '.$query.$wpdb->last_error;
		echo '<p>'.$wpdb->_real_escape( $uri );
		*/
	}

}

/**
 * To log or not to log current request?
 *
 * @param $wp_type integer
 * @param $http_code integer
 * @param $user_id integer
 *
 * @return bool
 * @since 6.0
 */
function cerber_to_log( $wp_type, $http_code, $user_id ) {

	if ( nexus_is_valid_request() ) {
		return false;
	}

	$mode = crb_get_settings( 'timode' );

	if ( $mode == 0 ) {
		return false;
	}

	if ( $wp_type == 520 && crb_get_settings( 'tilogrestapi' ) ) {
		return true;
	}

	if ( $wp_type == 515 && crb_get_settings( 'tilogxmlrpc' ) ) {
		return true;
	}

	// All traffic

	if ( $mode == 2 ) {
		if ( $wp_type < 515 ) { // Pure admin requests
			if ( $wp_type < 502 && ! $user_id ) { // @since 6.3
				return true;
			}
			//if ( $wp_type == 500 && 'admin-ajax.php' != cerber_get_uri_script() ) { // @since 7.8
			if ( $wp_type == 500 && ! CRB_Request::is_script( '/wp-admin/admin-ajax.php' ) ) { // @since 7.9.1
				return true;
			}

			return false;
		}

		return true;
	}

	// Minimal

	if ( $mode == 3 ) {

		if ( CRB_Activity::get_logged()
		     || CRB_Globals::$redirect_url
		     || CRB_Bug_Hunter::has_errors( E_ERROR ) ) {
			return true;
		}

		return false;
	}

	// Smart mode ---------------------------------------------------------

	if ( CRB_Globals::$req_status
	     || CRB_Globals::$blocked ) {
		return true;
	}

	if ( $tmp = CRB_Activity::get_logged() ) {
		unset( $tmp[ CRB_EV_LFL ], $tmp[51], $tmp[52] );
		if ( ! empty( $tmp ) ) {
			return true;
		}
	}

	if ( CRB_Bug_Hunter::has_errors( array( E_ERROR, E_WARNING ) ) ) {
		return true;
	}

	if ( $wp_type < 515 ) {
		if ( $wp_type < 502 && ! $user_id ) { // @since 6.3
			if ( ! empty( $_GET ) || ! empty( $_POST ) || ! empty( $_FILES ) ) {
				return true;
			}
		}
		if ( $wp_type == 500 && ! CRB_Request::is_script( '/wp-admin/admin-ajax.php' ) ) { // @since 7.8
			return true;
		}

		return false;
	}

	if ( $http_code >= 400 ||
	     $wp_type < 600 ||
	     $user_id ||
	     ! empty( $_POST ) ||
	     ! empty( $_FILES ) ||
	     CRB_Request::is_search()
	     || cerber_get_non_wp_fields() ) {
		return true;
	}

	if ( cerber_is_http_post()
	     || cerber_get_uri_script() ) {
		return true;
	}

	return false;
}

/**
 * Checks whether the current request should be excluded from logging.
 *
 * @return bool Returns true if the request must be excluded from logging, otherwise false.
 *
 * @since 8.6.5.2
 */
function crb_is_log_exception(): bool {

	// UA-based exceptions

	$ua_list = (array) crb_get_settings( 'tinoua' );
	if ( $ua_list ) {
		$ua = crb_array_get( $_SERVER, 'HTTP_USER_AGENT', '' );
		if ( $ua !== '' ) {
			$ua = substr( $ua, 0, 1000 );

			foreach ( $ua_list as $item ) {
				if ( ! is_string( $item ) || $item === '' ) {
					continue;
				}

				if ( stripos( $ua, $item ) !== false ) {
					return true;
				}
			}
		}
	}

	// URI-based exceptions

	$paths = (array) crb_get_settings( 'tinolocs' );
	if ( ! $paths ) {
		return false;
	}

	$request_uri = crb_array_get( $_SERVER, 'REQUEST_URI', '' );

	foreach ( $paths as $item ) {
		if ( ! is_string( $item ) || $item === '' ) {
			continue;
		}

		if ( $item[0] === '{' && substr( $item, - 1 ) === '}' ) {
			$regex_body = substr( $item, 1, - 1 );
			if ( ! $regex_body ) {
				continue;
			}

			$regex_body = str_replace( '~', '\~', $regex_body );
			$pattern = '~' . $regex_body . '~i';

			if ( @preg_match( $pattern, $request_uri ) ) {
				return true;
			}

		}
		else {
			if ( stripos( $request_uri, $item ) === 0 ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Mask sensitive request fields before saving in DB to prevent information leaks.
 *
 * @param array $fields Associative array of POST request fields or decoded JSON data
 *
 * @return array The input array with sensitive field values replaced by masked strings
 *
 * @since 6.0
 *
 * @version Sequoia
 */
function cerber_mask_fields( array $fields ): array {

	if ( ! $fields ) {
		return $fields;
	}

	$default_mask_fields = array(
		'pwd',
		'pass',
		'password',
		'password_1',
		'password_2',
		'post_password',
		'cerber-cloud-key',
	);

	$custom_mask_fields = (array) crb_get_settings( 'timask' );
	$mask_fields = array_merge( $default_mask_fields, $custom_mask_fields );
	$mask_fields = array_unique( $mask_fields );

	foreach ( $mask_fields as $field_name ) {
		if ( ! array_key_exists( $field_name, $fields ) ) {
			continue;
		}

		if ( ! is_scalar( $fields[ $field_name ] ) ) {
			continue;
		}

		$field_value = (string) $fields[ $field_name ];

		if ( $field_value === '' ) {
			continue;
		}

		$fields[ $field_name ] = str_repeat( '*', mb_strlen( $field_value ) );
	}

	return $fields;
}

/**
 * Recursively normalize request fields for database storage.
 *
 * Converts scalar values to strings, truncates them to a safe length,
 * and removes escaped slashes. Nested arrays are processed recursively.
 *
 * @param array $request_fields Request fields to normalize.
 *
 * @return array Normalized request fields.
 *
 * @since 6.0
 *
 * @version Sequoia
 */
function cerber_prepare_fields( array $request_fields ): array {

	foreach ( $request_fields as &$field ) {

		if ( is_array( $field ) ) {
			$field = cerber_prepare_fields( $field );
			continue;
		}

		if ( ! is_scalar( $field ) ) {
			$field = '';
			continue;
		}

		if ( is_bool( $field ) ) {
			$field = $field ? '1' : '0';
		}
		else {
			$field = (string) $field;
		}

		$field = mb_substr( $field, 0, 1048576 );
	}

	unset( $field );

	return stripslashes_deep( $request_fields );
}

/**
 * Return non WordPress public query $_GET fields (parameters)
 *
 * @return array
 * @since 6.0
 */
function cerber_get_non_wp_fields() {
	global $wp_query;
	static $result;

	if ( isset( $result ) ) {
		return $result;
	}

	$get_keys = array_keys( $_GET );

	if ( empty( $get_keys ) ) {
		$result = array();

		return $result;
	}

	if ( is_object( $wp_query ) ) {
		$keys = $wp_query->fill_query_vars( array() );
	}
    elseif ( class_exists( 'WP_Query' ) ) {
		$tmp = new WP_Query();
		$keys = $tmp->fill_query_vars( array() );
	}
	else {
		$keys = array();
	}

	$wp_keys = array_keys( $keys );  // WordPress GET fields for frontend

	// Some well-known fields
	$wp_keys[] = 'redirect_to';
	$wp_keys[] = 'reauth';
	$wp_keys[] = 'action';
	$wp_keys[] = '_wpnonce';
	$wp_keys[] = 'loggedout';
	$wp_keys[] = 'doing_wp_cron';
	$wp_keys[] = 'checkemail';

	// WP Customizer fields
	$wp_keys = array_merge( $wp_keys, array(
		'nonce',
		'_method',
		'wp_customize',
		'changeset_uuid',
		'customize_changeset_uuid',
		'customize_theme',
		'theme',
		'customize_messenger_channel',
		'customize_autosaved'
	) );

	$result = array_diff( $get_keys, $wp_keys );

	if ( ! $result ) {
		$result = array();
	}

	return $result;

}


/**
 *
 * @since 6.0
 */
function cerber_beast() {

	if ( cerber_is_wp_cron()
	     || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return;
	}

	$wp_cerber = get_wp_cerber();

	$wp_cerber->CheckProhibitedURI();

	// TI --------------------------------------------------------------------

	if ( ! $ti_mode = crb_get_settings( 'tienabled' ) ) {
		return;
	}

	// Exceptions by IP

	if ( crb_get_settings( 'tiipwhite' )
	     && crb_acl_is_allowed() ) {
		CRB_Globals::$req_status = 500;

		return;
	}

	if ( crb_ti_is_exception() ) {
		return;
	}

	$strict = ( $ti_mode > 1 );

	if ( is_admin() ) {
		if ( $strict ) {

			cerber_inspect_uploads();
		}

		return;
	}

	// Step one
	$wp_cerber->InspectRequest();

	// Step two
	//$uri_script = cerber_get_uri_script();
	$uri_script = CRB_Request::script();
	$uri = CRB_Request::uri_path();

	if ( $uri_script
	     && $script_filename = cerber_script_filename() ) { // @since 8.6.3.4

		$deny = false;
		$act_status = 0;

		// Scanning for executable scripts?

		if ( ! cerber_script_exists( $uri )
		     && ! cerber_is_login_request() ) {

			CRB_Globals::set_act_status( 19, 'tienabled' );
			cerber_log( 55 );

			if ( $strict ) {
				crb_apply_soft_ip_lockout( null, 708 );
			}

			cerber_forbidden_page();
		}
		else {

			// Direct access to a PHP script

			if ( crb_acl_is_blocked() ) {
				$deny = true;
				$act_status = 14;
			}
			//elseif ( ! in_array( $uri_script, cerber_get_wp_scripts() ) ) {
            elseif ( ! CRB_Request::is_script( cerber_get_wp_scripts() ) ) {
				if ( ! cerber_is_ip_allowed() ) {
					$deny = true;
					$act_status = 13;
				}
                elseif ( lab_is_blocked( null, true ) ) {
					$deny = true;
					$act_status = 15;
				}
			}
		}

		if ( $deny ) {
			CRB_Globals::set_act_status( $act_status, 'tienabled' );
			cerber_log( CRB_EV_PUR );
			cerber_forbidden_page();
		}
	}

	// Step three
	cerber_screen_request_fields();

	// Step four
	cerber_inspect_uploads();
}

/**
 * Check if the current request matches an exception
 *
 * @return bool True if the request is permitted based on configured exception
 *
 * @since 9.6.1.11
 */
function crb_ti_is_exception() {

	// Exceptions by URI path

	if ( $path_list = crb_get_settings( 'tiwhite' ) ) {

		$uri_path = CRB_Request::uri_path();

		foreach ( (array) $path_list as $item ) {

			if ( ! is_string( $item ) || $item === '' ) {
				continue;
			}

			if ( $item[0] == '{' && substr( $item, - 1 ) == '}' ) {
				$regex_body = substr( $item, 1, - 1 );
				if ( ! $regex_body ) {
					continue;
				}

				$regex_body = str_replace( '~', '\~', $regex_body );
				$pattern = '~' . $regex_body . '~i'; // ~ in use since 9.6.3.1

				if ( @preg_match( $pattern, $uri_path ) ) {
					CRB_Globals::$req_status = 501;

					return true;
				}
			}
			else {
				$cmp = ( substr( $item, - 1 ) == '/' ) ? $uri_path . '/' : $uri_path; // Someone may specify trailing slash
				if ( $item == $cmp ) {
					CRB_Globals::$req_status = 501;

					return true;
				}
			}
		}
	}

	// Exceptions by HTTP header

	if ( $header_list = crb_get_settings( 'tiwhite_header' ) ) {
		foreach ( (array) $header_list as $header ) {
			if ( crb_request_header_matches( $header ) ) {
				CRB_Globals::$req_status = 504;

				return true;
			}
		}
	}

	return false;
}

/**
 * Inspects POST & GET fields
 *
 */
function cerber_screen_request_fields() {
	global $cerber_in_context;

	$white = array();
	$found = false;

	if ( ! empty( $_GET ) ) {
		$cerber_in_context = 1;
		$found = cerber_inspect_array( $_GET, array( 's' ) );
	}

	if ( ! empty( $_POST ) && ! $found ) {
		//if ( CRB_Request::is_script( '/' . WP_COMMENT_SCRIPT ) ) {
		if ( CRB_Request::is_comment_sent() ) {
			$white = array( 'comment' );
		}
		$cerber_in_context = 2;
		$found = cerber_inspect_array( $_POST, $white );
	}

	if ( $found ) {
		CRB_Globals::set_ctrl_setting( 'tienabled' );
		cerber_log( $found );
		crb_apply_soft_ip_lockout( null, 709 );
		cerber_forbidden_page();
	}
}

/**
 * Recursively inspects values in a given multi-dimensional array
 *
 * @param array $array
 * @param array $white A list of elements to skip
 *
 * @return bool|int
 */
function cerber_inspect_array( $array, $white = array() ) {

	static $rec_limit = null;

	if ( ! $array ) {
		return false;
	}

	if ( $rec_limit === null ) {
		$rec_limit = CERBER_CIREC_LIMIT;
	}
	else {
		$rec_limit --;
		if ( $rec_limit <= 0 ) {
			$rec_limit = null;
			CRB_Globals::set_act_status( 20 );

			return 100;
		}
	}

	foreach ( $array as $key => $value ) {
		if ( in_array( $key, $white ) ) {
			continue;
		}
		if ( is_array( $value ) ) {
			$found = cerber_inspect_array( $value );
		}
		else {
			$found = cerber_inspect_value( $value, true );
		}
		if ( $found ) {
			return $found;
		}
	}

	$rec_limit ++;

	return false;
}

function cerber_inspect_value( $value = '', $reset = false ) {

	static $rec_limit = null; // Real recursion limit

	if ( ! $value || is_numeric( $value ) ) {
		return false;
	}

	if ( $reset ) {
		$rec_limit = null;
	}

	if ( $rec_limit === null ) {
		$rec_limit = CERBER_CIREC_LIMIT;
	}
	else {
		$rec_limit --;
		if ( $rec_limit <= 0 ) {
			$rec_limit = null;
			CRB_Globals::set_act_status( 21 );

			return 100;
		}
	}

	$found = false;

	if ( $varbyref = cerber_is_base64_encoded( $value ) ) {
		$found = cerber_inspect_value( $varbyref );
	}
	else {
		$parsed = cerber_detect_php_code( $value );
		if ( ! empty( $parsed[0] ) ) {
			CRB_Globals::set_act_status( 22 );
			$found = 100;
		}
        elseif ( ! empty( $parsed[1] ) ) {
			foreach ( $parsed[1] as $string ) {
				$found = cerber_inspect_value( $string );
				if ( $found ) {
					break;
				}
			}
		}
		if ( ! $found && cerber_detect_other_code( $value ) ) {
			CRB_Globals::set_act_status( 23 );
			$found = 100;
		}
		if ( ! $found && CRB_JS_Detector::detect_js_code( $value ) ) {
			CRB_Globals::set_act_status( 24 );
			$found = 100;
		}
	}

	$rec_limit ++;

	return $found;
}

/**
 * Heuristically detects PHP code and PHP constructs commonly abused in malicious code.
 *
 * The input may be an arbitrary or incomplete fragment. It is normalized and tokenized
 * to identify constructs associated with code execution, obfuscation, data exfiltration,
 * reconnaissance, file manipulation, and other potentially malicious activity.
 *
 * This function performs heuristic detection, not PHP syntax validation, and supports
 * detecting PHP code embedded in polyglot files and payloads.
 *
 * String literals found during tokenization are returned for further recursive inspection.
 *
 * @param mixed $value Value to inspect. It is converted to a string before tokenization.
 *                     It may contain request data, decoded payloads, file fragments, binary data,
 *                     or truncated PHP code.
 *
 * @return array{
 *     0: array{}|array{0:int,1:array{0:int,1:string}},
 *     1: array<int,string>
 * } Detection result containing a PHP token with its risk level and description,
 *   plus string literals extracted for further inspection.
 */
function cerber_detect_php_code( $value ): array {
	static $unsafe_tokens = null;

	$result = array( array(), array() );

	if ( ! is_string( $value ) || $value === '' || is_numeric( $value ) ) {
		return $result;
	}

	// Normalize whitespace plus the control bytes the PHP 7.4 lexer rejects.
    // The replacement must be a space: the lexer itself skips such a byte and restarts,
    // so removing it instead would join fragments and fabricate identifiers (ev<DEL>al -> eval).

	$prepared_string = preg_replace(
		'/[\s\x00-\x08\x0E-\x1F\x7F]+/',
		' ',
		cerber_remove_comments( $value )
	);

	if ( ! $prepared_string ) {
		return $result;
	}

	// Complete an unterminated comment to avoid false diagnostics and log noise. Can be used after removing valid comments only.
	if ( false !== strpos( $prepared_string, '/*' ) ) {
		$prepared_string .= '*/';
	}

	if ( false === strpos( $prepared_string, '<?php' ) ) {
		$prepared_string = '<?php ' . $prepared_string;
	}

	$tokens = @token_get_all( $prepared_string );

	if ( empty( $tokens ) ) {
		return $result;
	}

	$code_tokens = array( T_STRING, T_EVAL );

	if ( $unsafe_tokens === null ) {
		$unsafe_tokens = cerber_get_php_unsafe();
	}

	foreach ( $tokens as $token ) {
		if ( ! is_array( $token ) ) {
			continue;
		}

		if ( in_array( $token[0], $code_tokens ) && isset( $unsafe_tokens[ $token[1] ] ) ) {
			if ( preg_match( '/' . $token[1] . '\((?!\)).+\)/i', $prepared_string ) ) {
				$result[0] = array( $token[0], $unsafe_tokens[ $token[1] ] );
				break;
			}
		}
        elseif ( $token[0] == T_CONSTANT_ENCAPSED_STRING ) {
			$string = trim( $token[1], '\'"' );
			if ( ! $string || is_numeric( $string ) ) {
				continue;
			}
			$result[1][] = $string;
		}
	}

	return $result;
}

/**
 * @param string $value
 *
 * @return bool
 */
function cerber_detect_other_code( $value ) {
	global $cerber_in_context; // = 1 if $value is a value of a query string parameter ($_GET), = 2 if $value is a value of a POST form field ($_POST), 0 = file contents

	if ( ! $value ) {
		return false;
	}

	//static $sql = array( 'information_schema.', 'xp_cmdshell', 'FROM_BASE64', '@@' );

	$context = $cerber_in_context ?? 0; // = 1 if $value is a value of a query string parameter ($_GET), = 2 if $value is a value of a POST form field ($_POST), 0 = file contents
	$score = 0;

	if ( $context > 0 ) { // Typically it means $value came with an HTTP request from a remote computer
		$str = preg_replace( '#/\*(?:[^*]*(?:\*(?!/))*)*\*/#', '', $value ); // Remove comments
		if ( $context == 1 ) {
			if ( strlen( $value ) != strlen( $str ) ) {
				$score ++;
			}
		}
	}
	else {
		$str = $value;
	}

	if ( ! $str ) {
		return false;
	}

	if ( preg_match( '/\b(?:SELECT|INSERT|UPDATE|DELETE)\b/i', $str ) ) { // Does it contain SQL?
		$score ++;
		$p = stripos( $str, 'UNION' );
		if ( $p !== false ) {
			$score ++;
			if ( $context == 1 ) {
				return true;
			}
		}
		if ( preg_match( '/\b(?:information_schema|FROM_BASE64|wp_users|xp_cmdshell|LOAD_FILE)\b/i', $value ) ) {
			return true;
		}
		if ( $context < 1 ) {
			return false;
		}
		// $_GET & $_POST
		if ( preg_match( '/\b(?:name_const|unhex)\b/i', $value ) ) {
			$score ++;
		}
		if ( $score > 3 ) {
			return true;
		}
		$char = substr_count( strtoupper( $value ), 'CHAR' );
		if ( $char > 1 ) {
			return true;
		}
		$score += $char;
		if ( $score > 3 ) {
			return true;
		}
	}

	return false;
}

/**
 * @param string $str Text to process
 * @param string $type Signature type to use
 *
 * @return array[]|false
 */
function cerber_process_patterns( $str, $type ) {
	static $patterns;

	if ( ! isset( $patterns[ $type ] ) ) {
		switch ( $type ) {
			case 'php':
				$patterns[ $type ] = cerber_get_php_patterns();
				break;
			case 'js':
				$patterns[ $type ] = cerber_get_js_patterns();
				break;
			case 'htaccess':
				$patterns[ $type ] = cerber_get_ht_patterns();
				break;
			default:
				return false;
		}
	}

	$xdata = array();
	$severity = array();

	foreach ( $patterns[ $type ] as $pa ) {
		if ( $pa[1] == 2 ) { // 2 = REGEX

			if ( ! empty( $pa['not_regex'] ) ) {
				if ( preg_match( '/' . $pa['not_regex'] . '/i', $str ) ) {
					continue;
				}
			}

			$matches = array();

			if ( preg_match_all( '/' . $pa[2] . '/i', $str, $matches, PREG_OFFSET_CAPTURE ) ) {

				if ( ! empty( $pa['not_func'] ) && is_callable( $pa['not_func'] ) ) {
					foreach ( $matches[0] as $key => $match ) {
						if ( call_user_func( $pa['not_func'], $match[0], $str ) ) {
							unset( $matches[0][ $key ] );
						}
					}
				}

				if ( ! empty( $pa['func'] ) && is_callable( $pa['func'] ) ) {
					foreach ( $matches[0] as $key => $match ) {
						if ( ! call_user_func( $pa['func'], $match[0], $str ) ) {
							unset( $matches[0][ $key ] );
						}
					}
				}

				if ( ! empty( $matches[0] ) ) {
					$xdata[] = array( 2, $pa[0], array_values( $matches[0] ) );
					$severity[] = $pa[3];
				}
			}
		}
		else {
			if ( false !== stripos( $str, $pa[2] ) ) {
				$xdata[] = array( 2, $pa[0], array( array( $pa[2] ) ) );
				$severity[] = $pa[3];
			}
		}
	}

	return array( $xdata, $severity );
}

function cerber_inspect_uploads() {
	static $found = null;

	if ( $found !== null ) {
		return $found;
	}

	if ( empty( $_FILES ) ) {
		return false;
	}

	global $crb_uploaded_files;
	$crb_uploaded_files = array();

	array_walk_recursive( $_FILES, function ( $file_name ) {
		global $crb_uploaded_files;
		if ( $file_name
		     && is_string( $file_name )
		     && is_file( $file_name ) ) {
			$crb_uploaded_files[] = $file_name;
		}
	} );

	if ( empty( $crb_uploaded_files ) ) {
		return false;
	}

	$found = false;

	foreach ( $crb_uploaded_files as $file_name ) {
		if ( $f = @fopen( $file_name, 'r' ) ) {
			$str = @fread( $f, 100000 );
			@fclose( $f );
			if ( cerber_inspect_value( $str, true ) ) {
				$found = 56;
				if ( ! @unlink( $file_name ) ) {
					// if a system doesn't permit us to delete the file in the tmp uploads folder
					$target = cerber_get_the_folder() . 'must_be_deleted.tmp';
					@move_uploaded_file( $file_name, $target );
					@unlink( $target );
				}
			}
		}
	}

	if ( $found ) {
		CRB_Globals::set_ctrl_setting( 'tienabled' );
		cerber_log( $found );
		crb_apply_soft_ip_lockout( null, 710 );
	}

	return $found;
}

function cerber_error_control() {
	if ( crb_get_settings( 'nophperr' ) ) {
		@ini_set( 'display_startup_errors', 0 );
		@ini_set( 'display_errors', 0 );
	}
}

// Menu routines ---------------------------------------------------------------

// Hide/show menu items in public
add_filter( 'wp_get_nav_menu_items', function ( $items, $menu, $args ) {
	if ( is_admin() ) {
		return $items;
	}
	$logged = is_user_logged_in();
	foreach ( $items as $key => $item ) {
		// For *MENU*CERBER* See cerber_nav_menu_box() !!!
		if ( 0 === strpos( $item->attr_title, '*MENU*CERBER*' ) ) {
			$menu_id = explode( '|', $item->attr_title );
			switch ( $menu_id[1] ) {
				case 'wp-cerber-login-url':
					if ( $logged ) {
						unset( $items[ $key ] );
					}
					break;
				case 'wp-cerber-logout-url':
					if ( ! $logged ) {
						unset( $items[ $key ] );
					}
					break;
				case 'wp-cerber-reg-url':
					if ( $logged ) {
						unset( $items[ $key ] );
					}
					break;
				case 'wp-cerber-wc-login-url':
					if ( $logged ) {
						unset( $items[ $key ] );
					}
					break;
				case 'wp-cerber-wc-logout-url':
					if ( ! $logged ) {
						unset( $items[ $key ] );
					}
					break;
			}
		}
	}

	return $items;
}, 10, 3 );

// Set actual URL for a menu item based on a special value in title attribute
add_filter( 'nav_menu_link_attributes', function ( $atts ) {

	// For *MENU*CERBER* See cerber_nav_menu_box() !!!
	if ( 0 === strpos( $atts['title'] ?? '', '*MENU*CERBER*' ) ) {
		$title = explode( '|', $atts['title'] );
		$atts['title'] = '';

		$url = '#';
		// See cerber_nav_menu_items() !!!
		switch ( $title[1] ) {
			case 'wp-cerber-login-url':
				$url = wp_login_url();
				break;
			case 'wp-cerber-logout-url':
				$url = wp_logout_url();
				break;
			case 'wp-cerber-reg-url':
				if ( get_option( 'users_can_register' ) ) {
					$url = wp_registration_url();
				}
				break;
			case 'wp-cerber-wc-login-url':
				if ( class_exists( 'WooCommerce' ) ) {
					$url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
				}
				break;
			case 'wp-cerber-wc-logout-url':
				if ( class_exists( 'WooCommerce' ) ) {
					$url = wc_logout_url();
				}
				break;
		}

		$atts['href'] = $url;
	}

	return $atts;
}, 1 );

function cerber_push_the_news() {

	// The stored announcement describes the version the site is upgrading from, so an upgrade that produces no news must not leave it behind.

	if ( ! $news = cerber_parse_change_log( true ) ) {
		delete_site_option( CRB_ADMIN_ANNOUNCE );

		return;
	}

	$news = array_slice( $news, 0, 7 );

	// Wrap each per-line element in an <li> for the highlights list.
	$news_items = array_map( static function ( CRB_UI_Element $news_line ): CRB_UI_Element {
		return crb_ui_element( 'li', array(), $news_line );
	}, $news );

	$announcement = array();

	$announcement[] = crb_ui_element( 'h1', array(), 'Highlights from WP Cerber Security ' . CERBER_VER );

	$announcement[] = crb_ui_element( 'ul', array(), $news_items );

	$announcement[] = crb_ui_element( 'p', array( 'style' => 'margin-top: 18px; line-height: 1.3;' ), array(
		crb_ui_element( 'span', array( 'class' => 'dashicons-before dashicons-info-outline' ) ),
		"  \u{00A0} ",
		crb_ui_link( 'https://wpcerber.com/?plugin_version=' . CERBER_VER, 'Read more on wpcerber.com', array( 'target' => '_blank' ) ),
	) );

	// Optional changelog link (disabled): crb_ui_link( cerber_admin_link( 'changelog' ), 'See the whole history in the changelog' )

	if ( ! defined( 'CRB_JUST_MARRIED' )
	     && crb_was_activated( 2 * WEEK_IN_SECONDS )
	     && ! lab_lab() ) {

		$announcement[] = crb_ui_element( 'h2', array( 'style' => 'margin-top: 28px;' ), __( "We need your support to keep moving forward", 'wp-cerber' ) );

		$announcement[] = crb_ui_element( 'table', array( 'style' => 'margin-top: 20px;' ),
			crb_ui_element( 'tr', array(), array(
				crb_ui_element( 'td' ),
				crb_ui_element( 'td', array( 'style' => 'padding-top: 0;' ), __( 'By sharing your unique opinion on WP Cerber, you help the engineers behind the plugin make greater progress and help other professionals find the right software. You can leave your review on one of the following websites. Feel free to use your native language. Thanks!', 'wp-cerber' ) ),
			) )
		);

		// Capterra review link (disabled): crb_ui_link( crb_get_review_url( 'cap' ), 'Capterra', array( 'target' => '_blank' ) )
		$announcement[] = crb_ui_element( 'p', array(), array(
			crb_ui_link( crb_get_review_url( 'tpilot' ), 'Leave review on Trustpilot', array( 'target' => '_blank' ) ),
			" \u{00A0}|\u{00A0} ",
			crb_ui_link( crb_get_review_url( 'tradius' ), 'Leave review on TrustRadius', array( 'target' => '_blank' ) ),
		) );
	}
	else {

		$announcement[] = crb_ui_element( 'p', array( 'style' => 'margin-top: 24px;' ), array(
			crb_ui_element( 'span', array( 'class' => 'dashicons-before dashicons-email-alt' ) ),
			" \u{00A0} ",
			crb_ui_link( 'https://wpcerber.com/subscribe-newsletter/', "Subscribe to Cerber's newsletter" ),
		) );

		$announcement[] = crb_ui_element( 'p', array(), array(
			crb_ui_element( 'span', array( 'class' => 'dashicons-before dashicons-twitter' ) ),
			" \u{00A0} ",
			crb_ui_link( 'https://twitter.com/wpcerber', 'Follow Cerber on X' ),
		) );

		$announcement[] = crb_ui_element( 'p', array(), array(
			crb_ui_element( 'span', array( 'class' => 'dashicons-before dashicons-facebook' ) ),
			" \u{00A0} ",
			crb_ui_link( 'https://www.facebook.com/wpcerber/', 'Follow Cerber on Facebook' ),
		) );
	}

	// The dismiss control is not stored with the body. It is created at display time.

	$encoding_result = crb_ui_spec_encode( crb_ui_fragment( $announcement ) );

	if ( $encoding_result->has_errors() ) {

		crb_admin_notice_failure( 'Unable to store the admin announcement.', $encoding_result );
		delete_site_option( CRB_ADMIN_ANNOUNCE );

		return;
	}

	update_site_option( CRB_ADMIN_ANNOUNCE, $encoding_result->get_results() );
}

/**
 * Loads changelog entries and builds one UI Factory element per line.
 *
 * Sections in the changelog are separated by formatted version boundaries
 * enclosed in equal signs, such as =9.5.4=. Each returned element represents a
 * single changelog line: an inline fragment of text, crb-monospace code spans,
 * and links; a version-header fragment carrying a crb-version span; or, between
 * versions in full mode, a paragraph linking to the detailed release note. The
 * caller renders the elements through crb_ui_renderer().
 *
 * @param bool $last_version_only Optional. If true, returns only the entries for the latest version.
 *
 * @return array<int, CRB_UI_Element> Per-line UI elements, or an empty array on failure.
 */
function cerber_parse_change_log( bool $last_version_only = false ): array {
	if ( ! $changelog = file( cerber_get_plugins_dir() . '/wp-cerber/changelog.txt' ) ) {
		return array();
	}

	$result = array();
	$section_cnt = 0;
	$ver = '';

	foreach ( $changelog as $changelog_line ) {
		$changelog_line = trim( ltrim( $changelog_line, '* ' ) );

		if ( $changelog_line === '' ) {
			continue;
		}

		// A version boundary is only a section header when the line carries no link.
		$version_match = array();
		$has_link = (bool) preg_match( '/(\[.+?])(\(.+?\))/', $changelog_line );
		$is_version_boundary = ! $has_link && preg_match( '/=([.\d\s]+?)=/', $changelog_line, $version_match );

		if ( $is_version_boundary ) {

			// Latest-version mode: boundaries delimit the single section we keep and are not rendered.
			if ( $last_version_only ) {
				$section_cnt ++;

				if ( $section_cnt > 1 ) {
					break;
				}

				continue;
			}

			// Full mode: close the previous version with a release-note link before the new header.
			if ( $ver !== '' ) {
				$result[] = crb_ui_element(
					'p',
					array(),
					crb_ui_link(
						'https://wpcerber.com/wp-cerber-security-' . str_replace( array( '.', ' ' ), array( '-', '' ), $ver ) . '/',
						'Read detailed release note on wpcerber.com',
						array( 'target' => '_blank' )
					)
				);
			}

			$ver = $version_match[1];

			// Replace the =version= marker with a crb-version span, keeping any surrounding text.
			$version_children = array();

			foreach ( preg_split( '/=([.\d\s]+?)=/', $changelog_line, - 1, PREG_SPLIT_DELIM_CAPTURE ) as $version_segment_index => $version_segment ) {
				$version_children[] = ( $version_segment_index % 2 === 1 )
					? crb_ui_element( 'span', array( 'class' => 'crb-version' ), $version_segment )
					: $version_segment;
			}

			$result[] = crb_ui_fragment( $version_children );

			continue;
		}

		// Ordinary content line: inline text, code spans, and links.
		$result[] = crb_ui_fragment( cerber_change_log_inline_children( $changelog_line ) );
	}

	return $result;
}

/**
 * Tokenizes a single changelog line into ordered inline UI Factory children.
 *
 * The line is split into plain-text runs, backtick code spans, and Markdown
 * style links. Backtick fragments become crb-monospace span elements and
 * [label](url) sequences become semantic link elements opening in a new tab.
 * Plain-text runs are returned as raw strings so the renderer escapes them in
 * their own output context. Backtick spans are resolved before links, matching
 * the original parsing precedence. Empty text runs are dropped later by
 * crb_ui_fragment().
 *
 * @param string $changelog_line Trimmed changelog line without a leading list marker.
 *
 * @return array<int, CRB_UI_Element|string> Ordered inline children for crb_ui_fragment().
 */
function cerber_change_log_inline_children( string $changelog_line ): array {
	$inline_children = array();

	// Resolve backtick code spans first, preserving the captured code text.
	$code_segments = preg_split( '/`([^`\r\n]+)`/', $changelog_line, - 1, PREG_SPLIT_DELIM_CAPTURE );

	foreach ( $code_segments as $segment_index => $code_segment ) {

		// Odd segments are the captured code fragments between backticks.
		if ( $segment_index % 2 === 1 ) {
			$inline_children[] = crb_ui_element( 'span', array( 'class' => 'crb-monospace', 'style' => 'font-size: 100%' ), $code_segment );
			continue;
		}

		// Even segments are plain text that may still contain Markdown links.
		$link_matches = array();

		if ( ! preg_match_all( '/(\[.+?])(\(.+?\))/', $code_segment, $link_matches, PREG_OFFSET_CAPTURE ) ) {
			$inline_children[] = $code_segment;
			continue;
		}

		$text_cursor = 0;

		foreach ( $link_matches[0] as $match_index => $full_link_match ) {
			$link_offset = $full_link_match[1];

			// Plain text preceding the link.
			$inline_children[] = substr( $code_segment, $text_cursor, $link_offset - $text_cursor );

			// [label](url) becomes a semantic link; the renderer escapes the label and sanitizes the url.
			$link_label = trim( $link_matches[1][ $match_index ][0], '[]' );
			$link_url = trim( $link_matches[2][ $match_index ][0], '()' );
			$inline_children[] = crb_ui_link( $link_url, $link_label, array( 'target' => '_blank' ) );

			$text_cursor = $link_offset + strlen( $full_link_match[0] );
		}

		// Trailing plain text after the last link.
		$inline_children[] = substr( $code_segment, $text_cursor );
	}

	return $inline_children;
}

add_shortcode( 'wp_cerber_cookies', 'cerber_show_cookies' );
function cerber_show_cookies( $attr ) {
	global $wp_cerber_cookies;

	$html_atts = '';

	if ( isset( $attr['id'] ) ) {
		$html_atts .= ' id="' . esc_attr( $attr['id'] ) . '"';
	}

	if ( isset( $attr['style'] ) ) {
		$html_atts .= ' style="' . esc_attr( $attr['style'] ) . '"';
	}

	/*if ( ! $cookies = cerber_get_set( 'cerber_sweets' ) ) {
		return '';
	}*/

	$cookies = $wp_cerber_cookies;

	if ( ! is_user_logged_in() ) {
		foreach ( $cookies as $cookie => $data ) {
			if ( cerber_is_auth_cookie( $cookie ) ) {
				unset( $cookies[ $cookie ] );
			}
		}
	}

	$ret = '';

	if ( isset( $attr['text'] ) ) {
		$ret .= $attr['text'];
	}

	$type = crb_array_get( $attr, 'type' );

	switch ( $type ) {
		case 'comma':
			$ret .= implode( ', ', array_keys( $cookies ) );
			break;
		case 'table':
			$items = '';
			foreach ( $cookies as $cookie => $data ) {
				$items .= '<tr><td>' . $cookie . '</td></tr>';
			}

			$ret .= '<table ' . $html_atts . '><tbody>' . $items . '</tbody></table>';
			break;
		case 'list':
		default:
			$items = '';
			foreach ( $cookies as $cookie => $data ) {
				$items .= '<li>' . $cookie . '</li>';
			}

			$ret .= '<ul ' . $html_atts . '>' . $items . '</ul>';
	}

	return $ret;
}

add_action( 'wp_create_application_password', function ( $user_id, $new_item, $new_password, $args ) {
	cerber_log( 150, '', $user_id );
}, 0, 4 );

add_action( 'wp_update_application_password', function ( $user_id, $item, $update ) {
	cerber_log( 149, '', $user_id );
}, 0, 3 );

add_action( 'wp_delete_application_password', function ( $user_id, $item ) {
	cerber_log( 153, '', $user_id );
}, 0, 3 );

/**
 * Check if the current user is the website admin (can manage website)
 *
 * @return bool
 *
 * @since 8.6.9
 */
function cerber_user_can_manage() {
	if ( is_multisite() ) {
		$cap = 'manage_network';
	}
	else {
		$cap = 'manage_options';
	}

	return current_user_can( $cap );
}

/**
 * Enables error logging for the non-production automated test environment.
 */
function crb_enable_test_environment_logging(): void {
	if ( ! defined( 'CRB_TEST_ENVIRONMENT' )
	     || CRB_TEST_ENVIRONMENT !== true ) {
		return;
	}

	cerber_error_log( '*** TEST ENVIRONMENT ERROR LOGGING IS ENABLED ***' );

	Revalt::set_error_logger( function ( $code, $message, $data ): void {
		if ( $data === null ) {
			$details = '';
		}
        elseif ( is_string( $data ) ) {
			$details = $data;
		}
		else {
			$details = print_r( $data, true );
		}

		$src = defined( 'CRB_TEST_ENVIRONMENT_LOG_LABEL' )
			? (string) CRB_TEST_ENVIRONMENT_LOG_LABEL
			: 'SMOKE';

		cerber_error_log( $message . ' [ERROR CODE: ' . $code . '] ERROR DATA: ' . $details, $src );
	},
		Revalt::LOG_INSTANT );
}