<?php
/**
 * Admin Class
 *
 * @package role-based-pricing
 */

if (!defined('ABSPATH')) {
	exit; // Restrict for direct access.
}

if (!class_exists('AF_C_S_P_Admin')) {

	class AF_C_S_P_Admin {
		/**
		 * Main __construct function start.
		 */
		public function __construct() {

			add_action('admin_enqueue_scripts', array( $this, 'csp_admin_assets' ));
			// Product Level.
			// Create the custom tab.
			add_filter('woocommerce_product_data_tabs', array( $this, 'create_csp_tab' ));
			// Add the custom fields.
			add_action('woocommerce_product_data_panels', array( $this, 'display_csp_fields' ));
			// Save the custom fields.
			add_action('woocommerce_process_product_meta', array( $this, 'save_csp_fields' ));

			// For Variable Products.
			add_action('woocommerce_product_after_variable_attributes', array( $this, 'csp_variable_fields' ), 10, 3);
			add_action('woocommerce_save_product_variation', array( $this, 'csp_save_custom_field_variations' ), 10, 2);

			// Rule Based.
			add_action('add_meta_boxes', array( $this, 'csp_add_custom_meta_box' ));
			add_action('save_post_csp_rules', array( $this, 'csp_add_custom_meta_save' ));
			add_filter('manage_csp_rules_posts_columns', array( $this, 'csp_rules_custom_columns' ));
			add_action('manage_csp_rules_posts_custom_column', array( $this, 'csp_rules_custom_column' ), 10, 2);

			add_action('admin_menu', array( $this, 'csp_custom_menu_admin' ));
			add_action('admin_init', array( $this, 'csp_options' ));

			add_action('admin_init', array( $this, 'csp_export_csv' ));

			add_action('wp_ajax_cspsearchProducts', array( $this, 'cspsearchProducts' ));
			add_action('wp_ajax_cspsearchUsers', array( $this, 'cspsearchUsers' ));
			add_action('wp_loaded', array( $this, 'afrpb_import_export_pro_prices' ));

			if (isset($_POST['afrole_save_hide_price']) && '' != $_POST['afrole_save_hide_price']) {

				include_once ABSPATH . 'wp-includes/pluggable.php';

				if (!empty($_REQUEST['afroleprice_nonce_field'])) {

					$retrieved_nonce = sanitize_text_field(wp_unslash($_REQUEST['afroleprice_nonce_field']));
				} else {
					$retrieved_nonce = 0;
				}

				if (!wp_verify_nonce($retrieved_nonce, 'afroleprice_nonce_action')) {

					die('Failed security check');
				}

				$this->afrolebase_save_data();
				add_action('admin_notices', array( $this, 'afrolebase_author_admin_notice' ));
			}
		}//end __construct()


		/**
		 * Imports the Product level pricing
		 *
		 * @return void
		 */
		public function afrpb_import_export_pro_prices() {

			if (isset($_POST['afrbp_import_prices'])) {



				$retrieved_nonce = isset($_REQUEST['afrbp_import_nonce_field']) ? sanitize_text_field(wp_unslash($_REQUEST['afrbp_import_nonce_field'])) : '';

				if (!wp_verify_nonce($retrieved_nonce, 'afrbp_import_action')) {
					die(esc_html__('Security Violated.', 'addify_role_price'));
				}

				$response = include_once ADDIFY_CSP_PLUGINDIR . 'includes/af-import-product-level-pricing.php';

				if ($response) {
					add_action('admin_notices', array( $this, 'afrbp_import_success_notice' ));
				}
			}
			if (isset($_POST['afrbp_export_prices'])) {



				$retrieved_nonce = isset($_REQUEST['afrbp_import_nonce_field']) ? sanitize_text_field(wp_unslash($_REQUEST['afrbp_import_nonce_field'])) : '';

				if (!wp_verify_nonce($retrieved_nonce, 'afrbp_import_action')) {
					die(esc_html__('Security Violated.', 'addify_role_price'));
				}

				$response = include_once ADDIFY_CSP_PLUGINDIR . 'includes/af-export-product-level-pricing.php';

				if (!$response) {
					add_action('admin_notices', array( $this, 'afrbp_export_fail_notice' ));
				}
			}
		}//end afrpb_import_export_pro_prices()


		/**
		 * File Upload Notice
		 *
		 * @return void
		 */
		public function afrbp_import_success_notice() {
			?>
			<div class="updated notice notice-success is-dismissible">
				<p><?php echo esc_html__('Prices imported successfully.', 'addify_role_price'); ?></p>
			</div>
			<?php
		}//end afrbp_import_success_notice()

		/**
		 * Export fail Notice 
		 *
		 * @return void
		 */
		public function afrbp_export_fail_notice() {
			?>
			<div class="updated notice notice-success is-dismissible">
				<p><?php echo esc_html__('No Product Level Prices found to export!', 'addify_role_price'); ?></p>
			</div>
			<?php
		}//end afrbp_export_fail_notice()


		/**
		 * Export csv
		 *
		 * @return void
		 */
		public function csp_export_csv() {

			if (isset($_GET['action']) && 'export_csp_rules' == $_GET['action']) {
				if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), '_nonce')) {
					$export = new AF_C_S_P_Export();
					$export->print_csv_file();
				} else {
					esc_html_e('The link you followed has expired', 'addify_role_price');
					exit;
				}
			}
		}//end csp_export_csv()


		/**
		 * Main csp_admin_assets start.
		 *
		 * @return void
		 */
		public function csp_admin_assets() {

			$af_screen = get_current_screen();
			wp_enqueue_style('addify_csp_admin_css', ADDIFY_CSP_URL . '/assets/css/addify_csp_admin_css.css', false, '1.1.0');
			wp_enqueue_script('jquery-ui-progressbar');
			wp_enqueue_script('addify_csp_admin_js', ADDIFY_CSP_URL . '/assets/js/addify_csp_admin_js.js', false, '1.1.0');
			$csp_data = array(
				'admin_url'    => admin_url('admin-ajax.php'),
				'nonce'        => wp_create_nonce('afrolebase-ajax-nonce'),
				'export_href'  => wp_nonce_url(admin_url('edit.php?post_type=csp_rules&action=export_csp_rules'), '_nonce'),
				'export_title' => __('Export', 'addify_role_price'),
				'import_title' => __('Import', 'addify_role_price'),
				'import_href'  => admin_url('edit.php?post_type=csp_rules&page=csp-settings&tab=import'),

			);
			wp_localize_script('addify_csp_admin_js', 'csp_php_vars', $csp_data);
			// Select2 css and js.
			wp_enqueue_script('jquery');

			if ('csp_rules' == $af_screen->post_type || 'csp_rules_page_csp-settings' == $af_screen->id) {
				wp_enqueue_style('addify_ps-select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css', false, '1.0');
				wp_enqueue_style('addify_ps-select2-bscss', 'https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2-bootstrap.css', false, '1.0');
				wp_enqueue_script('addify_ps-select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', false, '1.0');
			}
		}//end csp_admin_assets()

		/**
		 * Create csp tabs
		 *
		 * @param mixed $tabs Args.
		 * @return mixed
		 */
		public function create_csp_tab( $tabs ) {
			$tabs['addify_csp_customer'] = array(
				'label'    => esc_html__('Role Based Pricing(By Customers)', 'addify_role_price'), // The name of your panel.
				'target'   => 'addify_csp_panel_customer', // Will be used to create an anchor link so needs to be unique.
				'class'    => array( 'addify_csp_tab', 'show_if_simple' ), // Class for your panel tab - helps hide/show depending on product type.
				'priority' => 80, // Where your panel will appear. By default, 70 is last item.
			);

			$tabs['addify_csp_role'] = array(
				'label'    => esc_html__('Role Based Pricing(By User Roles)', 'addify_role_price'), // The name of your panel.
				'target'   => 'addify_csp_panel_role', // Will be used to create an anchor link so needs to be unique.
				'class'    => array( 'addify_csp_tab', 'show_if_simple' ), // Class for your panel tab - helps hide/show depending on product type.
				'priority' => 80, // Where your panel will appear. By default, 70 is last item.
			);
			return $tabs;
		}//end create_csp_tab()

		/**
		 * Main display_csp_fields start.
		 *
		 * @return void
		 */
		public function display_csp_fields() {

			global $post;

			$cus_base_prices = get_post_meta($post->ID, '_cus_base_price', true);
			wp_nonce_field('csp_nonce_action', 'csp_nonce_field');

			require ADDIFY_CSP_PLUGINDIR . 'includes/admin/meta-boxes/product/csp-product-level.php';
		}//end display_csp_fields()

		/**
		 * Variable fields
		 *
		 * @param mixed $loop           Args.
		 * @param mixed $variation_data Args.
		 * @param mixed $variation      Args.
		 * @return void
		 */
		public function csp_variable_fields( $loop, $variation_data, $variation ) {

			$cus_base_prices = get_post_meta($variation->ID, '_cus_base_price', true);
			wp_nonce_field('csp_nonce_action', 'csp_nonce_field');

			require ADDIFY_CSP_PLUGINDIR . 'includes/admin/meta-boxes/product/csp-product-level-variable-product.php';
		}//end csp_variable_fields()


		/**
		 * Main save_csp_fields start.
		 *
		 * @param init $post_id Args.
		 * @return void
		 */
		public function save_csp_fields( $post_id ) {

			$product = wc_get_product($post_id);

			if ('variable' != $product->get_type()) {

				if (isset($_POST['cus_base_price'])) {

					if (!empty($_REQUEST['csp_nonce_field'])) {

						$retrieved_nonce = sanitize_text_field(wp_unslash($_REQUEST['csp_nonce_field']));
					} else {
						$retrieved_nonce = 0;
					}

					if (!wp_verify_nonce($retrieved_nonce, 'csp_nonce_action')) {

						die('Failed security check');
					}

					$cus_base_price = sanitize_meta('', wp_unslash($_POST['cus_base_price']), '');
				} else {
					$cus_base_price = '';
				}

				if (!empty($cus_base_price)) {

					$product->update_meta_data('_cus_base_price', $cus_base_price);
				} else {

					$product->delete_meta_data('_cus_base_price');
				}

				// Role based.
				global $wp_roles;
				$roles = $wp_roles->get_names();

				foreach ($roles as $key => $value) {

					if ('variable' != $product->get_type()) {

						if (!empty($_POST['role_price'][ $key ])) {
							if (!empty($_POST['role_price'][ $key ]['discount_type'])) {
								$product->update_meta_data('_role_base_price_' . $key, serialize(sanitize_meta('', wp_unslash($_POST['role_price'][ $key ]), '')));
							} else {
								$product->delete_meta_data('_role_base_price_' . $key);
							}
						}
					}
				}

				if (!empty($_POST['role_price']['guest'])) {
					if (!empty($_POST['role_price']['guest']['discount_type'])) {
						$product->update_meta_data('_role_base_price_guest', serialize(sanitize_meta('', wp_unslash($_POST['role_price']['guest']), '')));
					} else {
						$product->delete_meta_data('_role_base_price_guest');
					}
				}

				$product->save();
			}
		}//end save_csp_fields()

		/**
		 * Main csp_save_custom_field_variations start.
		 *
		 * @param mixed $variation_id Args.
		 * @param mixed $i            Args.
		 * @return void
		 */
		public function csp_save_custom_field_variations( $variation_id, $i ) {

			if (isset($_POST['cus_base_price'][ $variation_id ])) {

				if (!empty($_REQUEST['csp_nonce_field'])) {

					$retrieved_nonce = sanitize_text_field(wp_unslash($_REQUEST['csp_nonce_field']));
				} else {
					$retrieved_nonce = 0;
				}

				if (!wp_verify_nonce($retrieved_nonce, 'csp_nonce_action')) {

					die('Failed security check');
				}

				$cus_base_price = sanitize_meta('', wp_unslash($_POST['cus_base_price'][ $variation_id ], ''), '');
			} else {
				$cus_base_price = '';
			}

			if ('' != $cus_base_price) {
				update_post_meta($variation_id, '_cus_base_price', $cus_base_price);
			} else {
				update_post_meta($variation_id, '_cus_base_price', '');
			}

			global $wp_roles;
			$roles = $wp_roles->get_names();

			foreach ($roles as $key => $value) {

				if (!empty($_POST['role_price'][ $i ][ $key ])) {
					if (!empty($_POST['role_price'][ $i ][ $key ]['discount_type'])) {

						update_post_meta($variation_id, '_role_base_price_' . $key, serialize(sanitize_meta('', wp_unslash($_POST['role_price'][ $i ][ $key ]), '')));
					} else {
						delete_post_meta($variation_id, '_role_base_price_' . $key);
					}
				}
			}

			if (!empty($_POST['role_price'][ $i ]['guest'])) {
				if (!empty($_POST['role_price'][ $i ]['guest']['discount_type'])) {
					update_post_meta($variation_id, '_role_base_price_guest', serialize(sanitize_meta('', wp_unslash($_POST['role_price'][ $i ]['guest']), '')));
				} else {
					delete_post_meta($variation_id, '_role_base_price_guest');
				}
			}
		}//end csp_save_custom_field_variations()


		/**
		 * Main csp_add_custom_meta_box start.
		 *
		 * @return void
		 */
		public function csp_add_custom_meta_box() {

			add_meta_box('csp-meta-box', esc_html__('Rule Details', 'addify_role_price'), array( $this, 'csp_meta_box_callback' ), 'csp_rules', 'normal', 'high', null);
		}//end csp_add_custom_meta_box()


		/**
		 * Main csp_meta_box_callback start.
		 *
		 * @return void
		 */
		public function csp_meta_box_callback() {

			global $post;
			wp_nonce_field('csp_nonce_action', 'csp_nonce_field');
			$rcus_base_price = get_post_meta($post->ID, 'rcus_base_price', true);

			$csp_applied_on_categories = get_post_meta($post->ID, 'csp_applied_on_categories', true);

			require ADDIFY_CSP_PLUGINDIR . 'includes/admin/meta-boxes/rules/csp-rule-level.php';
		}//end csp_meta_box_callback()


		/**
		 * Main function start.
		 *
		 * @param integer $post_id Args.
		 * @return void
		 */
		public function csp_add_custom_meta_save( $post_id ) {

			// Bail if we're doing an auto save.
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}

			// If our current user can't edit this post, bail.
			if (!current_user_can('edit_posts')) {
				return;
			}

			if (isset($_POST['csp_rules'])) {

				if (!empty($_REQUEST['csp_nonce_field'])) {

					$retrieved_nonce = sanitize_text_field(wp_unslash($_REQUEST['csp_nonce_field']));
				} else {
					$retrieved_nonce = 0;
				}

				if (!wp_verify_nonce($retrieved_nonce, 'csp_nonce_action')) {

					die('Failed security check');
				}
			}

			if (!empty($_SESSION['allfetchedrules'])) {

				session_unset(sanitize_meta('', $_SESSION['allfetchedrules'], ''));
			}

			remove_action('save_post_csp_rules', array( $this, 'csp_add_custom_meta_save' ));

			if (isset($_POST['csp_rule_priority'])) {
				wp_update_post(
					array(
						'ID'         => intval($post_id),
						'menu_order' => sanitize_text_field(wp_unslash($_POST['csp_rule_priority'])),
					)
				);
			}

			add_action('save_post_csp_rules', array( $this, 'csp_add_custom_meta_save' ));

			if (isset($_POST['csp_apply_on_all_products'])) {
				update_post_meta($post_id, 'csp_apply_on_all_products', sanitize_text_field(wp_unslash($_POST['csp_apply_on_all_products'])));
			} else {
				delete_post_meta($post_id, 'csp_apply_on_all_products', '');
			}

			if (isset($_POST['csp_applied_on_products'])) {
				update_post_meta($post_id, 'csp_applied_on_products', sanitize_meta('', wp_unslash($_POST['csp_applied_on_products']), ''));
			} else {
				delete_post_meta($post_id, 'csp_applied_on_products');
			}

			if (isset($_POST['csp_applied_on_categories'])) {
				update_post_meta($post_id, 'csp_applied_on_categories', sanitize_meta('', wp_unslash($_POST['csp_applied_on_categories']), ''));
			} else {
				delete_post_meta($post_id, 'csp_applied_on_categories');
			}

			if (isset($_POST['rcus_base_price'])) {
				update_post_meta($post_id, 'rcus_base_price', sanitize_meta('', wp_unslash($_POST['rcus_base_price']), ''));
			} else {
				delete_post_meta($post_id, 'rcus_base_price');
			}

			global $wp_roles;
			$roles = $wp_roles->get_names();

			foreach ($roles as $key => $value) {

				if (isset($_POST['rrole_base_price'][ $key ])) {
					if (!empty($_POST['rrole_base_price'][ $key ]['discount_type'])) {
						update_post_meta($post_id, 'rrole_base_price_' . $key, serialize(sanitize_meta('', wp_unslash($_POST['rrole_base_price'][ $key ]), '')));
					} else {
						delete_post_meta($post_id, 'rrole_base_price_' . $key);
					}
				}
			}

			if (!empty($_POST['rrole_base_price']['guest'])) {
				if (!empty($_POST['rrole_base_price']['guest']['discount_type'])) {
					update_post_meta($post_id, 'rrole_base_price_guest', serialize(sanitize_meta('', wp_unslash($_POST['rrole_base_price']['guest']), '')));
				} else {
					delete_post_meta($post_id, 'rrole_base_price_guest');
				}
			}
		}//end csp_add_custom_meta_save()


		/**
		 * Main csp_rules_custom_columns start.
		 *
		 * @param init $columns Args.
		 * @return \init
		 */
		public function csp_rules_custom_columns( $columns ) {

			unset($columns['date']);
			$columns['csp_rule_priority'] = esc_html__('Rule Priority', 'addify_role_price');
			$columns['date']              = esc_html__('Date Published', 'addify_role_price');

			return $columns;
		}//end csp_rules_custom_columns()

		/**
		 * Main csp_rules_custom_column start.
		 *
		 * @param init $column  Args.
		 *
		 * @param init $post_id Args.
		 * @return void
		 */
		public function csp_rules_custom_column( $column, $post_id ) {

			$postt = get_post($post_id);

			switch ($column) {
				case 'csp_rule_priority':
					echo esc_attr($postt->menu_order);
					break;
			}
		}//end csp_rules_custom_column()

		/**
		 * Main csp_custom_menu_admin start.
		 *
		 * @return void
		 */
		public function csp_custom_menu_admin() {

			add_submenu_page(
				'edit.php?post_type=csp_rules',
				esc_html__('Hide Price', 'addify_role_price'),
				esc_html__('Hide Price', 'addify_role_price'),
				'manage_options',
				'csp-hide-pirce',
				array( $this, 'csp_hide_price_page' )
			);

			add_submenu_page(
				'edit.php?post_type=csp_rules',
				esc_html__('Settings', 'addify_role_price'),
				esc_html__('Settings', 'addify_role_price'),
				'manage_options',
				'csp-settings',
				array( $this, 'csp_settings_page' )
			);
		}//end csp_custom_menu_admin()


		/**
		 * Main csp_hide_price_page start.
		 *
		 * @return void
		 */
		public function csp_hide_price_page() {

			require ADDIFY_CSP_PLUGINDIR . 'includes/admin/settings/csp-hide-price.php';
		}//end csp_hide_price_page()


		/**
		 * Main csp_settings_page start.
		 *
		 * @return void
		 */
		public function csp_settings_page() {

			if (isset($_GET['tab'])) {
				$active_tab = sanitize_text_field(wp_unslash($_GET['tab']));
			} else {
				$active_tab = 'tab_one';
			}
			?>
			<div class="wrap">
				<h2><?php echo esc_html__('Role Based Pricing', 'addify_role_price'); ?></h2>
				<?php settings_errors(); ?>

				<h2 class="nav-tab-wrapper">

					<a href="?post_type=csp_rules&page=csp-settings&tab=tab_one" class="nav-tab <?php echo esc_attr($active_tab) == 'tab_one' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('General Settings', 'addify_role_price'); ?></a>
					<a href="?post_type=csp_rules&page=csp-settings&tab=import" class="nav-tab <?php echo esc_attr($active_tab) == 'import' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Import Rules', 'addify_role_price'); ?></a>
					<a href="?post_type=csp_rules&page=csp-settings&tab=import_product_price" class="nav-tab <?php echo esc_attr($active_tab) == 'import_product_price' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Import/Export Product Level Pricing', 'addify_role_price'); ?></a>
				</h2>

				<?php
				if ('import' !== $active_tab && 'import_product_price' !== $active_tab) {
					?>
					<form method="post" action="options.php">
					<?php
				}

				if ('tab_one' == $active_tab) {
					settings_fields('setting-group-1');
					do_settings_sections('addify-role-pricing-1');
				} elseif ('import' == $active_tab) {
					include_once ADDIFY_CSP_PLUGINDIR . 'includes/admin/settings/tabs/import-rules.php';
				} elseif ('import_product_price' == $active_tab) {
					include_once ADDIFY_CSP_PLUGINDIR . 'includes/admin/settings/tabs/import-product-rules.php';
				}

				?>
					<?php
					if ('import' !== $active_tab && 'import_product_price' !== $active_tab) {
						submit_button();
						?>
					</form>
						<?php
					}
					?>

			</div>
			<?php
		}//end csp_settings_page()

		/**
		 * Main csp_settings_page start.
		 *
		 * @return void
		 */
		public function csp_options() {

			add_settings_section(
				'page_1_section',         // ID used to identify this section and with which to register options.
				'',   // Title to be displayed on the administration page.
				array( $this, 'csp_page_1_section_callback' ), // Callback used to render the description of the section.
				'addify-role-pricing-1'                           // Page on which to add this section of options.
			);
			add_settings_field(
				'csp_apply_disc_excl_tax', // ID used to identify the field throughout the theme.
				esc_html__('Calculate Discount on Price Exclusive of Tax', 'addify_role_price'),
				array( $this, 'csp_apply_disc_incl_tax_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('It will calculate the discounts after removing the tax from price in case of prices inclusive of tax. e.g. $100 is product price inclusive of tax. $10 is tax on price. So discount will be calculated on $90. The price inclusive of tax after discount will be calculated again according to rates of tax.', 'addify_role_price'),
				)
			);
			register_setting(
				'setting-group-1',
				'csp_apply_disc_excl_tax'
			);

			add_settings_field(
				'csp_enforce_min_max_qt', // ID used to identify the field throughout the theme.
				esc_html__('Enforce Min & Max Quantity', 'addify_role_price'),
				array( $this, 'csp_enforce_min_max_qt_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('If this option is enabled, the user will not be allowed to add to cart beyond the minimum and maximum quantity.', 'addify_role_price'),
				)
			);
			register_setting(
				'setting-group-1',
				'csp_enforce_min_max_qt'
			);




			add_settings_field(
				'csp_min_qty_error_msg',                      // ID used to identify the field throughout the theme.
				esc_html__('Min Qty Error Message', 'addify_role_price'),                           // The label to the left of the option interface element.
				array( $this, 'csp_min_qty_error_msg_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('This message will be used when user add quantity less than minimum qty set. Use "%u" for number of quantity.', 'addify_role_price'),
					'class' => 'af_csp_qty_msg',
				)
			);
			register_setting(
				'setting-group-1',
				'csp_min_qty_error_msg'
			);

			add_settings_field(
				'csp_max_qty_error_msg',                      // ID used to identify the field throughout the theme.
				esc_html__('Max Qty Error Message', 'addify_role_price'),                           // The label to the left of the option interface element.
				array( $this, 'csp_max_qty_error_msg_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('This message will be used when user add quantity greater than maximum qty set. Use "%u" for number of quantity.', 'addify_role_price'),
					'class' => 'af_csp_qty_msg',
				)
			);
			register_setting(
				'setting-group-1',
				'csp_max_qty_error_msg'
			);

			add_settings_field(
				'csp_update_cart_error_msg',                      // ID used to identify the field throughout the theme.
				esc_html__('Update Cart Error Message', 'addify_role_price'),                           // The label to the left of the option interface element.
				array( $this, 'csp_update_cart_error_msg_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('This message will be used when user update product in cart. Use "%pro" for Product Name, "%min" for Minimum Quantity and "%max" for Maximum Quantity. ', 'addify_role_price'),
					'class' => 'af_csp_qty_msg',
				)
			);
			register_setting(
				'setting-group-1',
				'csp_update_cart_error_msg'
			);
			add_settings_field(
				'csp_exclude_user_roles',                      // ID used to identify the field throughout the theme.
				esc_html__('Exclude User Roles', 'addify_role_price'),                           // The label to the left of the option interface element.
				array( $this, 'csp_exclude_user_roles_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('Select the user roles for which the option to set the role base price will be excluded.', 'addify_role_price'),
				)
			);
			register_setting(
				'setting-group-1',
				'csp_exclude_user_roles'
			);
			add_settings_field(
				'csp_sel_price_apply',                      // ID used to identify the field throughout the theme.
				esc_html__('Apply Discount on', 'addify_role_price'),                           // The label to the left of the option interface element.
				array( $this, 'csp_sel_price_apply_callback' ),   // The name of the function responsible for rendering the option interface.
				'addify-role-pricing-1',                          // The page on which this option will be displayed.
				'page_1_section',         // The name of the section to which this field belongs.
				array(                              // The array of arguments to pass to the callback. In this case, just a description.
					wp_kses_post('Select the option on which the discount defined by plugin will be applied.', 'addify_role_price'),
					wp_kses_post('By default, the discount will be applied on the sale price it it exits; otherwise, it will be applied on regular prices.', 'addify_role_price'),
					wp_kses_post('For Regular Price Option the discount will be applied on the regular prices.', 'addify_role_price'),
					wp_kses_post('For the third option the discount will be ignored if sale prices are defined.', 'addify_role_price'),
				)
			);
			register_setting(
				'setting-group-1',
				'csp_sel_price_apply'
			);
		}//end csp_options()

		/**
		 * Main csp_page_1_section_callback start.
		 *
		 * @return void
		 */
		public function csp_page_1_section_callback() {
			?>

			<p><?php echo esc_html__('Manage module general settings from here.', 'addify_role_price'); ?></p>

			<?php
		}//end csp_page_1_section_callback()

		/**
		 * Main function start.
		 *
		 * @param init $args Args.
		 * @return void
		 */
		public function csp_apply_disc_incl_tax_callback( $args ) {
			?>

			<input type="checkbox" name="csp_apply_disc_excl_tax" id="csp_apply_disc_excl_tax" class="login_title2" value="yes" <?php echo checked('yes', esc_attr(get_option('csp_apply_disc_excl_tax'))); ?>>
			<p class="description csp_min_qty_error_msg"> <?php echo esc_attr($args[0]); ?> </p>

			<?php
		}//end csp_apply_disc_incl_tax_callback()

		/**
		 * Enforce minimum and maximum quantity
		 *
		 * @param init $args .
		 * @return void
		 */
		public function csp_enforce_min_max_qt_callback( $args ) {



			add_option('csp_enforce_min_max_qt', 'checked');

			if (get_option('csp_enforce_min_max_qt') == true) {
				$display = 'checked';
			} else {
				$display = '';
			}
			update_option('csp_enforce_min_max_qt', $display);



			?>

			<input type="checkbox" name="csp_enforce_min_max_qt" id="csp_enforce_min_max_qt" class="login_title2" <?php echo esc_attr(get_option('csp_enforce_min_max_qt')); ?>>
			<p class="description csp_min_qty_error_msg"> <?php echo esc_attr($args[0]); ?> </p>

			<?php
		}//end csp_enforce_min_max_qt_callback()

		/**
		 * Main function start.
		 *
		 * @param init $args Args.
		 * @return void
		 */
		public function csp_min_qty_error_msg_callback( $args ) {
			?>

			<input type="text" name="csp_min_qty_error_msg" id="csp_min_qty_error_msg" class="login_title2" value="<?php echo esc_attr(get_option('csp_min_qty_error_msg')); ?>" />
			<p class="description csp_min_qty_error_msg"> <?php echo esc_attr($args[0]); ?> </p>

			<?php
		}//end csp_min_qty_error_msg_callback()
		
		/**
		 * Main function start.
		 *
		 * @param init $args Args.
		 * @return void
		 */
		public function csp_max_qty_error_msg_callback( $args ) {
			?>

			<input type="text" name="csp_max_qty_error_msg" id="csp_max_qty_error_msg" class="login_title2" value="<?php echo esc_attr(get_option('csp_max_qty_error_msg')); ?>" />
			<p class="description csp_max_qty_error_msg"> <?php echo esc_attr($args[0]); ?> </p>

			<?php
		}//end csp_max_qty_error_msg_callback()

		/**
		 * Main function start.
		 *
		 * @param init $args Args.
		 * @return void
		 */
		public function csp_update_cart_error_msg_callback( $args ) {

			?>

			<input type="text" name="csp_update_cart_error_msg" id="csp_update_cart_error_msg" class="login_title2" value="<?php echo esc_attr(get_option('csp_update_cart_error_msg')); ?>" />
			<p class="description csp_update_cart_error_msg"> <?php echo esc_attr($args[0]); ?> </p>

			<?php
		}//end csp_update_cart_error_msg_callback()
	
		/**
		 * Main function start.
		 *
		 * @param init $args Args.
		 * @return void
		 */
		public function csp_sel_price_apply_callback( $args ) {

			$value = get_option('csp_sel_price_apply');
			$value = empty($value) ? 'sale_price' : $value;
			?>
			<input type="radio" name="csp_sel_price_apply" id="" value="sale_price" <?php echo checked('sale_price', esc_attr($value)); ?> />
			<?php echo esc_html__('Sale Price', 'addify_role_price'); ?>
			<br />
			<input type="radio" name="csp_sel_price_apply" id="" value="reg_price" <?php echo checked('reg_price', esc_attr($value)); ?> />
			<?php echo esc_html__('Regular Price', 'addify_role_price'); ?>
			<br />
			<input type="radio" name="csp_sel_price_apply" id="" value="ignore" <?php echo checked('ignore', esc_attr($value)); ?> />
			<?php echo esc_html__('Ignore if Sale Prices Exist', 'addify_role_price'); ?>

			<p class="description csp_exclude_user_roles"> <?php echo esc_attr($args[0]); ?> </p>
			<p class="description csp_exclude_user_roles"> <?php echo esc_attr($args[1]); ?> </p>
			<p class="description csp_exclude_user_roles"> <?php echo esc_attr($args[2]); ?> </p>
			<p class="description csp_exclude_user_roles"> <?php echo esc_attr($args[3]); ?> </p>
			<?php
		}//end csp_sel_price_apply_callback()
	

		/**
		 * Main function start.
		 *
		 * @param init $args Args.
		 * @return void
		 */
		public function csp_exclude_user_roles_callback( $args ) {
			$values = (array) get_option('csp_exclude_user_roles');
			?>

			<ul>
				<?php
				global $wp_roles;
				$roles = $wp_roles->get_names();
				foreach ($roles as $key => $value) {
					?>
					<li>
						<input type="checkbox" class="parent" name="csp_exclude_user_roles[]" id="" value="<?php echo esc_attr($key); ?>" <?php echo in_array((string) $key, $values, true) ? 'checked' : ''; ?> />
						<?php echo esc_attr($value); ?>
					</li>
				<?php } ?>
				<li>
					<input type="checkbox" class="parent" name="csp_exclude_user_roles[]" id="" value="guest" <?php echo in_array('guest', $values, true) ? 'checked' : ''; ?> />
					<?php echo esc_html__('Guest', 'addify_role_price'); ?>
				</li>
			</ul>

			<p class="description csp_exclude_user_roles"> <?php echo esc_attr($args[0]); ?> </p>

			<?php
		}//end csp_exclude_user_roles_callback()

		/**
		 * Main function start.
		 *
		 * @return undefined
		 */
		public function cspsearchProducts() {

			if (isset($_POST['nonce']) && '' != $_POST['nonce']) {

				$nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
			} else {
				$nonce = 0;
			}

			if (isset($_POST['q']) && '' != $_POST['q']) {

				if (!wp_verify_nonce($nonce, 'afrolebase-ajax-nonce')) {

					die('Failed ajax security check!');
				}

				$pro = sanitize_text_field(wp_unslash($_POST['q']));
			} else {

				$pro = '';
			}

			$data_array = array();
			$args       = array(
				'post_type'   => array( 'product' ),
				'post_status' => 'publish',
				'numberposts' => -1,
				's'           => $pro,
			);
			$pros       = get_posts($args);

			if (!empty($pros)) {

				foreach ($pros as $proo) {

					$title        = ( mb_strlen($proo->post_title) > 50 ) ? mb_substr($proo->post_title, 0, 49) . '...' : $proo->post_title;
					$data_array[] = array( $proo->ID, $title ); // Array( Post ID, Post Title ).
				}
			}

			echo wp_json_encode($data_array);

			die();
		}//end cspsearchProducts()

		/**
		 * Main function start.
		 *
		 * @return undefined
		 */
		public function cspsearchUsers() {

			if (isset($_POST['nonce']) && '' != $_POST['nonce']) {

				$nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
			} else {
				$nonce = 0;
			}

			if (isset($_POST['q']) && '' != $_POST['q']) {

				if (!wp_verify_nonce($nonce, 'afrolebase-ajax-nonce')) {

					die('Failed ajax security check!');
				}

				$pro = sanitize_text_field(wp_unslash($_POST['q']));
			} else {

				$pro = '';
			}

			$data_array  = array();
			$users       = new WP_User_Query(
				array(
					'search'         => '*' . esc_attr($pro) . '*',
					'search_columns' => array(
						'user_login',
						'user_nicename',
						'user_email',
						'user_url',
					),
				)
			);
			$users_found = $users->get_results();

			if (!empty($users_found)) {

				foreach ($users_found as $proo) {

					$title        = $proo->display_name . '(' . $proo->user_email . ')';
					$data_array[] = array( $proo->ID, $title ); // Array( User ID, User name and email ).
				}
			}

			echo wp_json_encode($data_array);

			die();
		}//end cspsearchUsers()

		/**
		 * Main function start.
		 *
		 * @return void
		 */
		public function afrolebase_save_data() {

			global $wp;

			if (!empty($_POST)) {

				if (!empty($_REQUEST['afroleprice_nonce_field'])) {

					$retrieved_nonce = sanitize_text_field(wp_unslash($_REQUEST['afroleprice_nonce_field']));
				} else {
					$retrieved_nonce = 0;
				}

				if (!wp_verify_nonce($retrieved_nonce, 'afroleprice_nonce_action')) {

					die('Failed security check');
				}

				if (!isset($_POST['csp_enable_hide_pirce'])) {

					update_option('csp_enable_hide_pirce', '');
				}

				if (!isset($_POST['csp_enable_hide_pirce_guest'])) {

					update_option('csp_enable_hide_pirce_guest', '');
				}

				if (!isset($_POST['csp_enable_hide_pirce_registered'])) {

					update_option('csp_enable_hide_pirce_registered', '');
				}

				if (!isset($_POST['csp_hide_cart_button'])) {

					update_option('csp_hide_cart_button', '');
				}

				if (!isset($_POST['csp_hide_price'])) {

					update_option('csp_hide_price', '');
				}

				if (!isset($_POST['csp_hide_products'])) {

					update_option('csp_hide_products', serialize(array()));
				}

				if (!isset($_POST['csp_hide_user_role'])) {

					update_option('csp_hide_user_role', serialize(array()));
				}

				if (!isset($_POST['cps_hide_categories'])) {

					update_option('cps_hide_categories', serialize(array()));
				}

				foreach ($_POST as $key => $value) {

					if ('afrole_save_hide_price' != $key) {

						if ('csp_hide_user_role' == $key || 'csp_hide_products' == $key || 'cps_hide_categories' == $key) {

							update_option(esc_attr($key), serialize(sanitize_meta('', $value, '')));
						} else {
							update_option(esc_attr($key), esc_attr($value));
						}
					}
				}
			}
		}//end afrolebase_save_data()

		/**
		 * Main function start.
		 *
		 * @return void
		 */
		public function afrolebase_author_admin_notice() {
			?>
			<div class="updated notice notice-success is-dismissible">
				<p><?php echo esc_html__('Settings saved successfully.', 'addify_role_price'); ?></p>
			</div>
			<?php
		}//end afrolebase_author_admin_notice()
	}//end class


	new AF_C_S_P_Admin();
}
