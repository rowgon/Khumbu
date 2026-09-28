<?php
/**
 * Plugin Name: MainWP Pro Reports Extension
 *  Plugin URI: https://mainwp.com
 *  Description: MainWP Pro Reports Extension allows you to generate pro reports for your child sites. Requires MainWP Dashboard.
 * Version: 5.0.5
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/pro-reports/
 */
if ( ! defined( 'MAINWP_PRO_REPORTS_PLUGIN_FILE' ) ) {
	define( 'MAINWP_PRO_REPORTS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_PRO_REPORTS_PLUGIN_DIR' ) ) {
	define( 'MAINWP_PRO_REPORTS_PLUGIN_DIR', plugin_dir_path( MAINWP_PRO_REPORTS_PLUGIN_FILE ) );
}

if ( ! defined( 'MAINWP_PRO_REPORTS_PLUGIN_URL' ) ) {
	define( 'MAINWP_PRO_REPORTS_PLUGIN_URL', plugin_dir_url( MAINWP_PRO_REPORTS_PLUGIN_FILE ) );
}


if ( ! defined( 'MAINWP_PRO_REPORTS_LOG_PRIORITY' ) ) {
	define( 'MAINWP_PRO_REPORTS_LOG_PRIORITY', 50 );
}

class MainWP_Pro_Reports_Extension {

	public static $instance = null;
	public static $version  = '5.0.3';

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action( 'init', array( &$this, 'init_connector' ), 8 ); // must init before mainwp log init connector.
		add_action( 'init', array( &$this, 'localization' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp_sync_extensions_options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );
		add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( MainWP_Pro_Reports_Plugin::get_instance(), 'site_synced' ), 10, 2 );
		add_action( 'mainwp_delete_site', array( &$this, 'on_delete_site' ), 10, 1 );
		// not used
		add_action( 'mainwp_sucuri_scan_done', array( &$this, 'sucuri_scan_done' ), 10, 4 ); // to fix action for wp cli.
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		add_filter( 'mainwp_log_specific_actions', array( $this, 'hook_log_specific_actions' ), 10, 2 );

		MainWP_Pro_Reports_DB::get_instance()->install();
		MainWP_Pro_Reports_Key::get_instance();
		MainWP_Pro_Reports::get_instance();
		MainWP_Pro_Reports_Plugin::get_instance();
		MainWP_Pro_Reports_Pdf::get_instance();
		MainWP_Pro_Reports_Hooks::get_instance();
		MainWP_Pro_Reports_Overview::get_instance();
		add_filter( 'cron_schedules', array( $this, 'getCronSchedules' ) );
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Mainwp-Pro-Reports-Extension' ) {
			wp_enqueue_style( 'mainwp-pro-reports-extension', MAINWP_PRO_REPORTS_PLUGIN_URL . 'css/mainwp-pro-reports-extension.css', array(), self::$version );
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'mainwp-pro-reports-extension', MAINWP_PRO_REPORTS_PLUGIN_URL . 'js/mainwp-pro-reports-extension.js', array( 'wp-color-picker' ), self::$version, true );
			wp_localize_script( 'mainwp-pro-reports-extension', 'mainwp_pro_reports_loc', array( 'nonce' => wp_create_nonce( '_wpnonce_mainwp_pro_reports' ) ) );
		}
	}

	public static function get_version() {
		return self::$version;
	}

	public function init_connector() {
		if ( class_exists( '\MainWP\Dashboard\Module\Log\Log_Manager' ) ) {
			add_filter( 'mainwp_module_log_connectors', array( $this, 'load_log_connector' ), 10, 2 );
		}
	}

	public function load_log_connector( $classes, $log ) {
		if ( is_array( $classes ) ) {
			if ( ! isset( $classes['pro-reports'] ) ) { // must same $class->name below.
				$class                   = new MainWP_Pro_Reports_Log_Connector( $log );
				$classes[ $class->name ] = $class;
			}
		}
		return $classes;
	}

	public function localization() {
		load_plugin_textdomain( 'mainwp-pro-reports-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Hook hook_log_specific_actions.
	 *
	 * Hook sites table sortable columns.
	 *
	 * @return mixed $sortable_columns.
	 */
	public function hook_log_specific_actions( $inputs ) {
		$inputs[ MAINWP_PRO_REPORTS_LOG_PRIORITY ] = __( 'Pro Reports', 'mainwp-pro-reports-extension' );
		return $inputs;
	}



	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( 'mainwp-pro-reports-extension/mainwp-pro-reports-extension.php' != $plugin_file ) {
			return $plugin_meta;
		}
		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );
		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}
		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for Update</a>';
		return $plugin_meta;
	}

	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$data['syncClientReportData'] = 1;
		return $data;
	}

	public function on_delete_site( $website ) {
		if ( $website ) {
			MainWP_Pro_Reports_DB::get_instance()->delete_generated_report_content( 0, $website->id ); // 0 to delete all report of the website
		}
	}

	public function admin_init() {
		MainWP_Pro_Reports_Pdf::get_instance()->admin_init();
	}

	public static function getCronSchedules( $schedules ) {
		$schedules['5minutely'] = array(
			'interval' => 5 * 60, // 5 minute in seconds
			'display'  => __( 'Once every 5 minutes', 'mainwp-pro-reports-extension' ),
		);
		return $schedules;
	}

	/**
	 * Update to work with -- WP Security Audit Log -- Fre and Pro
	 */
	function mainwp_sync_extensions_options( $values = array() ) {
		$values['mainwp-pro-reports-extension'] = array(
			'plugin_name' => 'MainWP Child Reports',
			'plugin_slug' => 'mainwp-child-reports/mainwp-child-reports.php',
			'no_setting'  => true,
		);
		return $values;
	}


	// hook to send sucuri scan logging to child site
	function sucuri_scan_done( $website_id, $scan_status, $data, $time_scan = false ) {
		$scan_result = array();

		if ( is_array( $data ) ) {
			$blacklisted    = isset( $data['BLACKLIST']['WARN'] ) ? true : false;
			$malware_exists = isset( $data['MALWARE']['WARN'] ) ? true : false;

			$status = array();
			if ( $blacklisted ) {
				$status[] = __( 'Site Blacklisted', 'mainwp-reports-extension' ); }
			if ( $malware_exists ) {
				$status[] = __( 'Site With Warnings', 'mainwp-reports-extension' ); }

			$scan_result['status']   = count( $status ) > 0 ? implode( ', ', $status ) : __( 'Verified Clear', 'mainwp-reports-extension' );
			$scan_result['webtrust'] = $blacklisted ? __( 'Site Blacklisted', 'mainwp-reports-extension' ) : __( 'Trusted', 'mainwp-reports-extension' );
		}

		$scan_data = array(
			'blacklisted'    => $blacklisted,
			'malware_exists' => $malware_exists,
		);

		// save results to child site stream
		$post_data = array(
			'mwp_action'  => 'save_sucuri_stream',
			'result'      => base64_encode( wp_json_encode( $scan_result ) ),
			'scan_status' => $scan_status,
			'scan_data'   => base64_encode( wp_json_encode( $scan_data ) ),
			'scan_time'   => $time_scan,
		);

		global $mainWPProReportsExtensionActivator;

		apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $website_id, 'client_report', $post_data );
	}
}

class MainWP_Pro_Reports_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-pro-reports-extension';
	protected $product_id       = 'MainWP Pro Reports Extension';
	protected $software_version = '5.0.5';

	public function __construct() {
		$this->childFile = __FILE__;

		spl_autoload_register( array( $this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		add_action( 'wp_loaded', array( 'MainWP_Pro_Reports_Schedule', 'init_cron' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );
		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
		add_action( 'mainwp_cronload_action', array( $this, 'load_cron_actions' ) );

		MainWP_Pro_Reports_Rest_Api::instance()->init();
	}

	function autoload( $class_name ) {
		$class_name = str_replace( '_', '-', strtolower( $class_name ) );
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'class' . DIRECTORY_SEPARATOR . 'class-' . $class_name . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}

	function load_cron_actions() {
		add_action( 'mainwp_managesite_schedule_backup', array( 'MainWP_Pro_Reports', 'managesite_schedule_backup' ), 10, 3 );
	}

	function get_this_extension( $pArray ) {

		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);

		return $pArray;
	}

	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Pro_Reports_Overview::get_instance()->render_overview();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}


	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-pro-reports-extension' ) ) {
			return;
		}
		add_action( 'mainwp_manage_sites_edit', array( 'MainWP_Pro_Reports', 'render_reports_site_tokens' ), 10, 1 );
		MainWP_Pro_Reports_Extension::get_instance();
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Pro Reports Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPProReportsExtensionActivator;
$mainWPProReportsExtensionActivator = new MainWP_Pro_Reports_Extension_Activator();
