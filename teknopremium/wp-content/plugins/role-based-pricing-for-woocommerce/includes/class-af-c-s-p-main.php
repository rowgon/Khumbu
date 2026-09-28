<?php

/**
 * Main Class
 *
 * @package role-based-pricing
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('AF_C_S_P_Main')) {

	/**
	 * Main class start.
	 */
	class AF_C_S_P_Main {

		private $af_price;

		/**
		 * Main __construct start.
		 */
		public function __construct() {

			$this->af_price = new AF_C_S_P_Price();

			// Change Price HTML.
			add_filter('woocommerce_get_price_html', array( $this, 'af_csp_custom_price_html' ), 100, 2);
			// Hide add to cart shop page.
			add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'csp_replace_loop_add_to_cart_link' ), 10, 2);

			// Hide add to cart on product page.
			add_action('woocommerce_single_product_summary', array( $this, 'csp_hide_add_cart_product_page' ), 1, 0);

			add_action('woocommerce_before_calculate_totals', array( $this, 'csp_adjust_price_in_cart' ), 20, 1);
		
			add_action('woocommerce_ajax_order_items_added', array( $this, 'rbp_adjust_admin_order_prices' ), 100, 2);
		} //end __construct().


		/**
		 * Check for admin order
		 *
		 * @param mixed $added_items args.
		 * @param mixed $order args.
		 * @return void
		 */
		public function rbp_adjust_admin_order_prices( $added_items, $order ) {

			foreach ($order->get_items() as $item) {

				if ($order->get_user_id() && 'line_item' == $item->get_type()) {

					$product  = $item->get_product();
					$quantity = $item->get_quantity();

					$new_line_subtotal  = $item->get_subtotal();
					$new_line_subt_tax  = $item->get_subtotal_tax();
					$new_line_total     = $item->get_total();
					$new_line_total_tax = $item->get_total_tax();
					$taxes              = $item->get_taxes();

					$role_based_price = $this->rbp_get_role_based_price($product, $quantity, $order->get_user());

					// Update Order item prices
					$item->set_subtotal($role_based_price * $quantity);
					$item->set_subtotal_tax($new_line_subt_tax);
					$item->set_total($role_based_price * $quantity);
					$item->set_total_tax($new_line_total_tax);
					$item->set_taxes($taxes);
					$item->save();
				}
			}

			$order->calculate_totals();
			$order->save();
		}

		

		public function rbp_get_role_based_price( $product, $quantity, $user ) {

			if (empty($user)) {
				return $product->get_price();
			}

			$tax_display_mode = get_option('woocommerce_tax_display_shop');

			$user_roles = (array) $user->roles;
			foreach ($user_roles as $user_role) {
				$pro_price = $this->af_price->get_price_of_product($product, $user, $user_role, $quantity);
				$price     = !is_bool($pro_price) ? floatval($pro_price) : $pro_price;
				if (!empty($price)) {
					$active_price = 'incl' === $tax_display_mode ?  wc_get_price_including_tax($product, array(
						'qty'   => 1,
						'price' => $price,
					)) : wc_get_price_excluding_tax($product, array(
						'qty'   => 1,
						'price' => $price,
					));
				} else {
					$active_price = 'incl' === $tax_display_mode ?  wc_get_price_including_tax($product) : wc_get_price_excluding_tax($product);
				}

				return $active_price;
			}

			return $product->get_price();
		}

		/**
		 * Function to recalculate the price in the cart.
		 *
		 * @param mixed $cart_object Args.
		 * @return void
		 */
		public function csp_adjust_price_in_cart( $cart_object ) {

			if (did_action('woocommerce_before_calculate_totals') >= 2) {
				return;
			}

			$user       = wp_get_current_user();
			$user_roles = (array) $user->roles;


			foreach ($cart_object->get_cart() as $key => $value) {


				if (0 != $value['variation_id']) {

					$product_id = $value['variation_id'];
				} else {
					$product_id = $value['product_id'];
				}

				if (empty($user_roles)) {

					$user_role        = 'guest';
					$role_based_price = $this->af_price->get_price_of_product(wc_get_product($product_id), $user, $user_role, $value['quantity']);
					$price            = !is_bool($role_based_price) ? floatval($role_based_price) : $role_based_price;
				} else {
					foreach ($user_roles as $user_role) {
						$role_based_price = $this->af_price->get_price_of_product(wc_get_product($product_id), $user, $user_role, $value['quantity']);
						$price            = !is_bool($role_based_price) ? floatval($role_based_price) : $role_based_price;
						if ($price) {
							break;
						}
					}
				}


				if (empty($price)) {
					$price = $value['data']->get_price();
				}

				$value['data']->set_price($price);
			}
		} //end csp_adjust_price_in_cart()


		/**
		 * Function to apply the same calculations in mini cart
		 *
		 * @param mixed $price         Args.
		 * @param mixed $cart_item     Args.
		 * @param mixed $cart_item_key Args.
		 * @return mixed
		 */
		public function af_csp_cart_item_price_filter( $price, $cart_item, $cart_item_key ) {
			if (0 != $cart_item['variation_id']) {

				$product_id = $cart_item['variation_id'];
			} else {
				$product_id = $cart_item['product_id'];
			}
			$user       = wp_get_current_user();
			$user_roles = (array) $user->roles;

			if (empty($user_roles)) {

				$user_role        = 'guest';
				$role_based_price = $this->af_price->get_price_of_product(wc_get_product($product_id), $user, $user_role, $cart_item['quantity']);
				$new_price        = !is_bool($role_based_price) ? floatval($role_based_price) : $role_based_price;
			} else {
				foreach ($user_roles as $user_role) {
					$role_based_price = $this->af_price->get_price_of_product(wc_get_product($product_id), $user, $user_role, $cart_item['quantity']);
					$new_price        = !is_bool($role_based_price) ? floatval($role_based_price) : $role_based_price;
					if ($price) {
						break;
					}
				}
			}
			if (!empty($new_price)) {
				return $new_price;
			}
			return $price;
		} //end af_csp_cart_item_price_filter()


		/**
		 * Replace/hide add to cart button in product loop.
		 *
		 * @param string $html    HTML of add to cart button.
		 *
		 * @param object $product WC_Product class object.
		 * @return string|false|void
		 */
		public function csp_replace_loop_add_to_cart_link( $html, $product ) {

			$_button_text = get_option('csp_cart_button_text');
			$_button_link = get_option('csp_cart_button_link');

			if ($this->af_price->is_product_price_hidden($product) || $this->af_price->is_product_add_to_cart_button_hidden($product)) {

				if (empty($_button_text)) {
					return;
				} else {

					ob_start();
					?>
					<a href="<?php echo esc_url($_button_link); ?>" class="button add_to_cart_button">
						<?php echo esc_html($_button_text); ?>
					</a>
				<?php
					return ob_get_clean();
				}
			}

			return $html;
		} //end csp_replace_loop_add_to_cart_link()


		/**
		 * Replace custom price HTML of product.
		 *
		 * @param string $price   HTML of price.
		 *
		 * @param object $product WC_Product class Object.
		 * @return float
		 */
		public function af_csp_custom_price_html( $price, $product ) {


			return $this->af_price->get_price_html_of_product($price, $product);
		} //end af_csp_custom_price_html()


		/**
		 * Hide add to cart on product page.
		 *
		 * @return void
		 */
		public function csp_hide_add_cart_product_page() {

			global $product;

			if ($this->af_price->is_product_price_hidden($product) || $this->af_price->is_product_add_to_cart_button_hidden($product)) {

				if ('variable' == $product->get_type()) {

					remove_action('woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20);
					add_action('woocommerce_single_variation', array( $this, 'csp_custom_button_replacement' ), 30);
				} else {

					remove_action('woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30);
					add_action('woocommerce_simple_add_to_cart', array( $this, 'csp_custom_button_replacement' ), 40);
				}
			}
		} //end csp_hide_add_cart_product_page()


		/**
		 * Replace custom button with add to cart button.
		 *
		 * @return void
		 */
		public function csp_custom_button_replacement() {

			$csp_cart_button_text = get_option('csp_cart_button_text');
			$csp_cart_button_link = get_option('csp_cart_button_link');

			if (!empty($csp_cart_button_text)) {

				echo '<a href="' . esc_url($csp_cart_button_link) . '" rel="nofollow" class="button add_to_cart_button">' . esc_attr($csp_cart_button_text) . '</a>';
			} else {
				echo '';
			}
		} //end csp_custom_button_replacement()
	} //end class


	new AF_C_S_P_Main();
}
