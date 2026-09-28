<?php
/**
 * Plugin Name: MainWP Code Snippets Extension
 * Plugin URI: https://mainwp.com
 * Description: The MainWP Code Snippets Extension is a powerful PHP platform that enables you to execute php code and scripts on your child sites and view the output on your Dashboard. Requires the MainWP Dashboard plugin.
 * Version: 5.1.1
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://docs.mainwp.com/add-ons/development/code-snippets-extension
 */

if ( ! defined( 'MAINWP_CODE_SNIPPETS_PLUGIN_FILE' ) ) {
	define( 'MAINWP_CODE_SNIPPETS_PLUGIN_FILE', __FILE__ );
}

/**
 * Class MainWP_CS_Extension
 *
 * Handles asset enqueuing and plugin meta links.
 *
 * @since 1.0.0
 */
class MainWP_CS_Extension {

	/**
	 * Singleton instance.
	 *
	 * @var MainWP_CS_Extension
	 */
	public static $instance = null;

	/**
	 * Base URL of the plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * Plugin basename (e.g. mainwp-code-snippets-extension/mainwp-code-snippets-extension.php).
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_CS_Extension
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'localization' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Initialize the DB schema and register AJAX handlers.
	 *
	 * @return void
	 */
	public function init() {
		MainWP_CS_DB::get_instance()->install();
		MainWP_CS::get_instance()->init();
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @return void
	 */
	public function localization() {
		load_plugin_textdomain( 'mainwp-code-snippets-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add a "Check for updates" link to the plugin row in the Plugins list.
	 *
	 * Only shown when the extension has an active API license key.
	 *
	 * @param string[] $plugin_meta Array of plugin row meta links.
	 * @param string   $plugin_file Plugin basename being rendered.
	 * @return string[] Possibly modified meta links array.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug !== $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data )
			|| ! isset( $api_data['activated_key'] )
			|| 'Activated' !== $api_data['activated_key']
			|| empty( $api_data['api_key'] )
		) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="' . esc_attr__( 'Check for updates', 'mainwp-code-snippets-extension' ) . '">' . esc_html__( 'Check for updates now', 'mainwp-code-snippets-extension' ) . '</a>';

		return $plugin_meta;
	}

	/**
	 * Enqueue scripts and styles on the extension admin page.
	 *
	 * @return void
	 */
	public function admin_init() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'Extensions-Mainwp-Code-Snippets-Extension' !== $page ) {
			return;
		}

		wp_enqueue_script( 'mainwp-cs-extension-code-bundle', $this->plugin_url . 'libs/codemirror6/dist/bundle.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'mainwp-cs-extension', $this->plugin_url . 'js/mainwp-codesnippets.js', array( 'mainwp-cs-extension-code-bundle' ), '1.0.0', true );
		wp_enqueue_style( 'mainwp-cs-extension', $this->plugin_url . 'css/mainwp-codesnippets.css', array(), '1.0.0' );
	}
}

/**
 * Class MainWP_CS_Extension_Activator
 *
 * Handles plugin activation, deactivation, MainWP integration, and access control.
 *
 * @since 1.0.0
 */
class MainWP_CS_Extension_Activator {

	/**
	 * Whether the MainWP Dashboard plugin is active.
	 *
	 * @var bool
	 */
	protected $mainwpMainActivated = false;

	/**
	 * Whether this extension is enabled in MainWP.
	 *
	 * @var bool|array
	 */
	protected $childEnabled = false;

	/**
	 * Child authentication key provided by MainWP.
	 *
	 * @var bool|string
	 */
	protected $childKey = false;

	/**
	 * Path to this plugin file (used as the child file identifier).
	 *
	 * @var string
	 */
	protected $childFile;

	/**
	 * Plugin handle / text domain slug.
	 *
	 * @var string
	 */
	protected $plugin_handle = 'mainwp-code-snippets-extension';

	/**
	 * Product name used for license/update checks.
	 *
	 * @var string
	 */
	protected $product_id = 'MainWP Code Snippets Extension';

	/**
	 * Current software version string.
	 *
	 * @var string
	 */
	protected $software_version = '5.1.1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->childFile = __FILE__;

		spl_autoload_register( array( $this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( $this, 'get_this_extension' ) );

		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( false !== $this->mainwpMainActivated ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( $this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_notices', array( $this, 'mainwp_error_notice' ) );
	}

	/**
	 * PSR-0 style autoloader for MainWP_CS_* classes.
	 *
	 * Converts class names like MainWP_CS_Db to mainwp-cs-db.class.php and
	 * requires the file from the class/ subdirectory.
	 *
	 * @param string $class_name Class name to load.
	 * @return void
	 */
	public function autoload( $class_name ) {
		$class_name = str_replace( '_', '-', strtolower( $class_name ) );
		if ( 0 !== strpos( $class_name, 'mainwp-cs' ) ) {
			return;
		}
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR
			. str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) )
			. 'class' . DIRECTORY_SEPARATOR
			. $class_name . '.class.php';

		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}

	/**
	 * Handle post-activation redirect to the Extensions page.
	 *
	 * @return void
	 */
	public function admin_init() {
		if ( 'yes' === get_option( 'mainwp_code_snippets_extension_activated' ) ) {
			delete_option( 'mainwp_code_snippets_extension_activated' );
			wp_safe_redirect( admin_url( 'admin.php?page=Extensions' ) );
			exit;
		}
	}

	/**
	 * Register this extension with the MainWP extensions filter.
	 *
	 * @param array $args Existing extensions array.
	 * @return array Extensions array with this extension appended.
	 */
	public function get_this_extension( $args ) {
		$args[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( $this, 'settings' ),
			'apiManager' => true,
		);
		return $args;
	}

	/**
	 * Render the extension settings page within the MainWP page wrapper.
	 *
	 * @return void
	 */
	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_CS::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * Activate this extension once MainWP is confirmed active.
	 *
	 * Fetches the child key and schedules asset/AJAX registration via plugins_loaded.
	 *
	 * @return void
	 */
	public function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Instantiate the extension after all plugins are loaded.
	 *
	 * Skipped if the current user does not have access to this extension.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-code-snippets-extension' ) ) {
			return;
		}
		new MainWP_CS_Extension();
	}

	/**
	 * Display an admin notice when the MainWP Dashboard plugin is not active.
	 *
	 * @return void
	 */
	public function mainwp_error_notice() {
		global $current_screen;
		if ( isset( $current_screen->parent_base ) && 'plugins' === $current_screen->parent_base && ! $this->mainwpMainActivated ) {
			echo '<div class="error"><p>'
				. wp_kses(
					__( 'MainWP Code Snippets Extension requires <a href="https://mainwp.com/" target="_blank" rel="noopener noreferrer">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank" rel="noopener noreferrer">MainWP Dashboard Plugin</a> first.', 'mainwp-code-snippets-extension' ),
					array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
				)
				. '</p></div>';
		}
	}

	/**
	 * Get the child authentication key.
	 *
	 * @return bool|string
	 */
	public function get_child_key() {
		return $this->childKey;
	}

	/**
	 * Get the child file path (this plugin's main file).
	 *
	 * @return string
	 */
	public function get_child_file() {
		return $this->childFile;
	}

	/**
	 * Store an option, inserting if it does not exist or updating if it does.
	 *
	 * @param string $option_name  Option name.
	 * @param mixed  $option_value Option value.
	 * @return bool True on success.
	 */
	public function update_option( $option_name, $option_value ) {
		$success = add_option( $option_name, $option_value, '', 'no' );
		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}
		return $success;
	}

	/**
	 * Plugin activation hook callback.
	 *
	 * @return void
	 */
	public function activate() {
		do_action(
			'mainwp_activate_extention',
			$this->plugin_handle,
			array(
				'product_id'       => $this->product_id,
				'software_version' => $this->software_version,
			)
		);
	}

	/**
	 * Plugin deactivation hook callback.
	 *
	 * @return void
	 */
	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPCSExtensionActivator;
$mainWPCSExtensionActivator = new MainWP_CS_Extension_Activator();
