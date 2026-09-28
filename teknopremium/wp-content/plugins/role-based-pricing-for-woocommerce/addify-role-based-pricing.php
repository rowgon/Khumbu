<?php
/**
 * Plugin Name:       Role Based Pricing for WooCommerce
 * Plugin URI:        https://woocommerce.com/products/role-based-pricing-for-woocommerce/
 * Description:        WooCommerce Role Based Pricing plugin empowers merchants to set product prices based on user roles and individual customers. (PLEASE TAKE BACKUP BEFORE UPDATING THE PLUGIN).
 * Version:           2.2.2
 * Author:            Addify
 * Developed By:      Addify
 * Author URI:        https://woocommerce.com/vendor/addify/
 * Support:           https://woocommerce.com/vendor/addify/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path:       /languages
 * Text Domain:       addify_role_price
 * WC requires at least: 4.0
 * WC tested up to: 10.7.0
 * Requires at least: 6.5
 * Tested up to: 6.9.4
 * Requires PHP: 7.4
 * Woo: 4971447:64b09622d1a85d9a1480337b46b432a1

 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Addify_Customer_And_Role_Pricing' ) ) {

	/**
	 * Class start
	 */
	class Addify_Customer_And_Role_Pricing {

		/**
		 * Constructor start
		 */
		public function __construct() {

			add_action( 'wp_loaded', array( $this, 'main_init' ) );
			add_action( 'init', array( $this, 'csp_custom_post' ) );

			$this->constant_vars();
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-price.php';
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-main.php';
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-export.php';
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-import.php';
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-ajax.php';
			
			require ADDIFY_CSP_PLUGINDIR . 'includes/af-product-pricing-functions.php';
			
			if ( is_admin() ) {
				require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-admin.php';
			} else {
				require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-front.php';
			}

			
			add_action('plugins_loaded', array( $this, 'af_csp_load_addons_class' ), 100 );

			// HOPS compatibility.
			add_action('before_woocommerce_init', array( $this, 'afrbp_HOPS_Compatibility' ));  

			add_action( 'plugins_loaded', array( $this, 'afrbp_checks' ) ); 
		}//end __construct()


		public function afrbp_checks() {

			// Check for multisite.
			if ( ! is_multisite() && ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				add_action( 'admin_notices', array( $this, 'afcsp_admin_notice' ));
			} else {

				add_action( 'init', array( $this, 'load_rest_api' ) );
			}
		}


		/**
		 * Admin notices
		 *
		 * @return void
		 */
		public function afcsp_admin_notice() {

			$afcsp_allowed_tags = array(
				'a'      => array(
					'class' => array(),
					'href'  => array(),
					'rel'   => array(),
					'title' => array(),
				),
				'b'      => array(),

				'div'    => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'p'      => array(
					'class' => array(),
				),
				'strong' => array(),

			);

			// Deactivate the plugin.
			deactivate_plugins( __FILE__ );

			$afcsp_woo_check = '<div id="message" class="error">
				<p><strong>Role Based Prices for WooCommerce plugin is inactive.</strong> The <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce plugin</a> must be active for this plugin to work. Please install &amp; activate WooCommerce »</p></div>';
			echo wp_kses( __( $afcsp_woo_check, 'addify_role_price' ), $afcsp_allowed_tags );
		}//end afcsp_admin_notice()

		/**
		 * HOPS compatibility.
		 *
		 * @return void
		 */
		public function afrbp_HOPS_Compatibility() {

			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}//end afrbp_HOPS_Compatibility()

		/**
		 * Loads Addon class
		 *
		 * @return void
		 */
		public function af_csp_load_addons_class() {
			if (class_exists('WC_Product_Addons')) {
				require ADDIFY_CSP_PLUGINDIR . 'includes/integrations/class-af-csp-add-ons.php';
			}
		}//end af_csp_load_addons_class()

		/**
		 * Loads Rest Api
		 *
		 * @return void
		 */
		public function load_rest_api() {
			require ADDIFY_CSP_PLUGINDIR . 'includes/rest-api/Server.php';
			\Addify\Role_based_Pricing\RestApi\Server::instance()->init();
		}//end load_rest_api()

		/**
		 * Loads text domain
		 *
		 * @return void
		 */
		public function main_init() {
			if ( function_exists( 'load_plugin_textdomain' ) ) {
				load_plugin_textdomain( 'addify_role_price', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			}
		}//end main_init()

		/**
		 * Global Variables
		 *
		 * @return void
		 */
		public function constant_vars() {

			if ( ! defined( 'ADDIFY_CSP_URL' ) ) {
				define( 'ADDIFY_CSP_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'ADDIFY_CSP_BASENAME' ) ) {
				define( 'ADDIFY_CSP_BASENAME', plugin_basename( __FILE__ ) );
			}

			if ( ! defined( 'ADDIFY_CSP_PLUGINDIR' ) ) {
				define( 'ADDIFY_CSP_PLUGINDIR', plugin_dir_path( __FILE__ ) );
			}
		}//end constant_vars()

		/**
		 * Register Custom Post type
		 *
		 * @return void
		 */
		public function csp_custom_post() {

			$labels = array(
				'name'                => __( 'Role Based Pricing Rules', 'addify_role_price' ),
				'singular_name'       => __( 'Role Based Pricing Rules', 'addify_role_price' ),
				'add_new'             => __( 'Add New Rule', 'addify_role_price' ),
				'add_new_item'        => __( 'Add Rule', 'addify_role_price' ),
				'edit_item'           => __( 'Edit Rule', 'addify_role_price' ),
				'new_item'            => __( 'New Rule', 'addify_role_price' ),
				'view_item'           => __( 'View Rule', 'addify_role_price' ),
				'search_items'        => __( 'Search Rule', 'addify_role_price' ),
				'exclude_from_search' => true,
				'not_found'           => __( 'No rule found', 'addify_role_price' ),
				'not_found_in_trash'  => __( 'No rule found in trash', 'addify_role_price' ),
				'parent_item_colon'   => '',
				'all_items'           => __( 'All Rules', 'addify_role_price' ),
				'menu_name'           => __( 'Role Based Pricing', 'addify_role_price' ),
			);

			$args = array(
				'labels'             => $labels,
				'menu_icon'          => plugin_dir_url( __FILE__ ) . 'assets/img/small_logo_grey.png',
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'capability_type'    => 'post',
				'public'             => true,
				'publicly_queryable' => true,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'rewrite'            => array(
					'slug'       => 'csp-rule',
					'with_front' => false,
				),
				'supports'           => array( 'title' ),
			);

			register_post_type( 'csp_rules', $args );
		}//end csp_custom_post()
	}//end class


	new Addify_Customer_And_Role_Pricing();
}
