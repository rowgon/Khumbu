<?php
/**
 * Pricing functions
 *
 * @package role-based-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !function_exists( 'addify_csp_get_role_based_price') ) {

	/**
	 * Return a role based price of product.
	 *
	 * @param  object $product   Product id or object.
	 * @param  object $user      User id or object.
	 * @param  object $user_role User Role.
	 * @return float
	 */
	function addify_csp_get_role_based_price( $product, $user = false, $user_role = 'guest' ) {

		if ( !class_exists( 'AF_C_S_P_Price' ) ) {
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-price.php';
		}

		if ( is_int( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( !is_a( $product, 'WC_Product') ) {
			return false;
		}

		$price_object = new AF_C_S_P_Price();

		return $price_object->get_price_of_product( $product, $user, $user_role );
	}//end addify_csp_get_role_based_price()

}

if ( !function_exists( 'addify_csp_get_role_based_price_html') ) {

	/**
	 * Return a role based price of product.
	 *
	 * @param  string $price_html HTML of product price.
	 * @param  object $product    Product id or object.
	 * @param  object $user       User id or object.
	 * @param  object $user_role  User Role.
	 * @return boolean
	 */
	function addify_csp_get_role_based_price_html( $price_html, $product, $user = false, $user_role = 'guest' ) {

		if ( !class_exists( 'AF_C_S_P_Price' ) ) {
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-price.php';
		}

		if ( is_int( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( !is_a( $product, 'WC_Product') ) {
			return $price_html;
		}

		$price_object = new AF_C_S_P_Price();

		return $price_object->get_price_html_of_product( $price_html, $product, $user, $user_role );
	}//end addify_csp_get_role_based_price_html()

}

if ( !function_exists( 'addify_csp_have_price_of_product') ) {

	/**
	 * Return true if product have role based pricing.
	 *
	 * @param  object $product   Product id or object.
	 * @param  object $user      User id or object.
	 * @param  object $user_role User Role.
	 * @return boolean
	 */
	function addify_csp_have_price_of_product( $product, $user = false, $user_role = 'guest' ) {

		if ( !class_exists( 'AF_C_S_P_Price' ) ) {
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-price.php';
		}

		if ( is_int( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( !is_a( $product, 'WC_Product') ) {
			return false;
		}

		$price_object = new AF_C_S_P_Price();

		return $price_object->have_price_of_product( $product, $user, $user_role );
	}//end addify_csp_have_price_of_product()

}

if ( !function_exists( 'addify_csp_is_product_price_hidden') ) {

	/**
	 * Return true if product price is hidden.
	 *
	 * @param  object $product   Product id or object.
	 * @param  object $user      User id or object.
	 * @param  object $user_role User Role.
	 * @return boolean
	 */
	function addify_csp_is_product_price_hidden( $product, $user = false, $user_role = 'guest' ) {

		if ( !class_exists( 'AF_C_S_P_Price' ) ) {
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-price.php';
		}

		if ( is_int( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( !is_a( $product, 'WC_Product') ) {
			return false;
		}

		$price_object = new AF_C_S_P_Price();

		return $price_object->is_product_price_hidden( $product, $user, $user_role );
	}//end addify_csp_is_product_price_hidden()

}

if ( !function_exists( 'addify_csp_is_product_add_to_cart_button_hidden') ) {

	/**
	 * Return true if add to cart button is hidden for product.
	 *
	 * @param  object $product   Product id or object.
	 * @param  object $user      User id or object.
	 * @param  object $user_role User Role.
	 * @return boolean
	 */
	function addify_csp_is_product_add_to_cart_button_hidden( $product, $user = false, $user_role = 'guest' ) {

		if ( !class_exists( 'AF_C_S_P_Price' ) ) {
			require ADDIFY_CSP_PLUGINDIR . 'includes/class-af-c-s-p-price.php';
		}

		if ( is_int( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( !is_a( $product, 'WC_Product') ) {
			return false;
		}

		$price_object = new AF_C_S_P_Price();

		return $price_object->is_product_add_to_cart_button_hidden( $product, $user, $user_role );
	}//end addify_csp_is_product_add_to_cart_button_hidden()

}

if ( !function_exists( 'afcsp_get_media_path') ) {

	/**
	 * Return true if add to cart button is hidden for product.
	 *
	 * @return boolean
	 */
	function afcsp_get_media_path() {

		$upload_dir = wp_upload_dir();

		$upload_path = $upload_dir['basedir'] . '/addify-role-pricing/';

		if (!is_dir($upload_path)) {
			mkdir($upload_path);
		}

		return $upload_path;
	}//end afcsp_get_media_path()

}

if ( !function_exists( 'afcsp_get_media_url') ) {

	/**
	 * Return true if add to cart button is hidden for product.
	 *
	 * @return string
	 */
	function afcsp_get_media_url() {

		$upload_dir = wp_upload_dir();

		$upload_url = $upload_dir['baseurl'] . '/addify-role-pricing/';

		return $upload_url;
	}//end afcsp_get_media_url()

}


if ( !function_exists( 'afcsp_get_all_categories') ) {


	function afcsp_get_all_categories() {

		$afcsp_product_categories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,        
		) );
		
		$afcsp_category_ids = array();
		
		if ( ! empty( $afcsp_product_categories ) && ! is_wp_error( $afcsp_product_categories ) ) {
			foreach ( $afcsp_product_categories as $category ) {
				$afcsp_category_id = $category->term_id;
			
				$afcsp_category_ids[] = $afcsp_category_id;
			}
		}
		
		return $afcsp_category_ids;
	}
}
