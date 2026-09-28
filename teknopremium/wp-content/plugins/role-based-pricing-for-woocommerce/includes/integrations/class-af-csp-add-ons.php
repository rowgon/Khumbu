<?php
/**
 * Addons
 *
 * @package role-based-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class start
 */
class AF_C_S_P_AddOns {

	/**
	 * Constructor start
	 */
	public function __construct() {
		
		$this->af_price = new AF_C_S_P_Price();

		add_filter('woocommerce_product_get_price', array( $this, 'af_csp_include_addons_prices' ), 90, 2);

		add_filter('woocommerce_product_variation_get_price', array( $this, 'af_csp_include_addons_prices' ), 90, 2);

		add_filter('woocommerce_before_calculate_totals', array( $this, 'af_csp_adjust_mini_cart_addons_prices' ), 90, 1);
	}//end __construct()

	/**
	 * Include Addons Prices
	 *
	 * @param mixed $price   Args.
	 * @param mixed $product Args.
	 * @return mixed
	 */
	public function af_csp_include_addons_prices( $price, $product ) {
		
		// WooCommerce Add-Ons Compatibility.
		if ( is_ajax() || is_product() || is_product_category() || is_product_tag() || is_archive() || is_front_page() || is_home() ) {
			
			$role_based_price = $this->af_price->get_price_of_product( $product );

			if ( false !== $role_based_price ) {
				do_action('addify_addons_prices', $role_based_price, $product);
				return $role_based_price;
			}           
			
		}
		do_action('addify_addons_prices', $price, $product);
		return $price;
	}//end af_csp_include_addons_prices()

	/**
	 * Mini cart Addons Price
	 *
	 * @param mixed $cart
	 * @return void
	 */
	public function af_csp_adjust_mini_cart_addons_prices( $cart ) {
		
		foreach ($cart->get_cart() as $key => $cart_item_data) {

			$role_based_price = $this->af_price->get_price_of_product( wc_get_product( $cart_item_data['data']->get_id() ) );

			$price = !is_bool( $role_based_price ) ? floatval( $role_based_price ) : $role_based_price;

			$regular_price = floatval( $cart_item_data['data']->get_regular_price( 'edit' ) );

			$sale_price = floatval( $cart_item_data['data']->get_sale_price( 'edit' ) );

			if (!$price) {
				continue;
			}

			foreach ( $cart_item_data['addons'] as $addon ) {
				$price_type  = $addon['price_type'];
				$addon_price = $addon['price'];

				switch ( $price_type ) {
					case 'percentage_based':
						$price         += (float) ( $price * ( $addon_price / 100 ) );
						$regular_price += (float) ( $regular_price * ( $addon_price / 100 ) );
						$sale_price    += (float) ( $sale_price * ( $addon_price / 100 ) );
						break;
					case 'flat_fee':
						$price         += (float) ( $addon_price / $cart_item_data['quantity'] );
						$regular_price += (float) ( $addon_price / $cart_item_data['quantity'] );
						$sale_price    += (float) ( $addon_price / $cart_item_data['quantity'] );
						break;
					default:
						$price         += (float) $addon_price;
						$regular_price += (float) $addon_price;
						$sale_price    += (float) $addon_price;
						break;
				}
			}

			$cart_item_data['data']->set_price( $price );

			// Only update regular price if it was defined.
			$has_regular_price = is_numeric( $cart_item_data['data']->get_regular_price( 'edit' ) );
			if ( $has_regular_price ) {
				$cart_item_data['data']->set_regular_price( $regular_price );
			}

			// Only update sale price if it was defined.
			$has_sale_price = is_numeric( $cart_item_data['data']->get_sale_price( 'edit' ) );
			if ( $has_sale_price ) {
				$cart_item_data['data']->set_sale_price( $sale_price );
			}

		}
	}//end af_csp_adjust_mini_cart_addons_prices()
}//end class


new AF_C_S_P_AddOns();
