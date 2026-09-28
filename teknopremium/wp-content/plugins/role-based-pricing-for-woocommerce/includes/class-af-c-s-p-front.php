<?php
/**
 * Front class
 *
 * @package role-based-pricing
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('AF_C_S_P_Front')) {

	/**
	 * Front class.
	 */
	class AF_C_S_P_Front {

		public $allfetchedrules;

		/**
		 * Main __construct start.
		 */
		public function __construct() {

			$this->allfetchedrules = $this->csp_load();

			add_action('wp_loaded', array( $this, 'csp_front_scripts' ));

			$enfore_min_max_qty = get_option('csp_enforce_min_max_qt');

			if (!empty($enfore_min_max_qty) && 'checked' == $enfore_min_max_qty) {
				// Min and Max Qty validation.
				add_filter('woocommerce_add_to_cart_validation', array( $this, 'csp_validate_min_max_qty' ), 10, 4);
				add_filter('woocommerce_update_cart_validation', array( $this, 'csp_update_cart_quantity_validation' ), 10, 4);
				add_filter('woocommerce_store_api_product_quantity_minimum', array( $this, 'csp_update_cart_quantity_validation_cart_item_min' ), 10, 3 );
				add_filter('woocommerce_store_api_product_quantity_maximum', array( $this, 'csp_update_cart_quantity_validation_cart_item_max' ), 10, 3 );
		
			}
		}//end __construct()


/**
 * Main function start.
 *
 * @param init $passed        Args.
 *
 * @param init $cart_item_key Args.
 *
 * @param init $values        Args.
 *
 * @param init $qty           Args.
 * 
 * @return false|\init
 */

		public function csp_update_cart_quantity_validation_cart_item_max( $value, $product, $cart_item ) {

			$product_id = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0;

			// Get the variation ID if it's a variation, or set to 0 if not
			$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;

			$user = wp_get_current_user();

			if (is_user_logged_in()) {

				if (0 == $variation_id) {
					// Simple Product.
					// Get customer specific price.
					$pro            = wc_get_product($product_id);
					$cus_base_price = get_post_meta($product_id, '_cus_base_price', true);
					$product        = wc_get_product($product_id);
					// Get role base price.
					foreach ($user->roles as $valuez) {

						$role_base_price = get_post_meta($product_id, '_role_base_price_' . $valuez, true);

						$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if (( !empty($max_qty) )) {
						 
							
										return $max_qty;
									} 
								}
							}
						}
					}

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

						// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
								$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							if (( !empty($max_qty) )) {
						 
								return $max_qty;
							}
						}
					}

					// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {

						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) )) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $valuez) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $valuez, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {
									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {

											// Date Validation.
											$current_date = gmdate('Y-m-d');

											if (!empty($rule_cus_price['start_date'])) {
												$start_date = $rule_cus_price['start_date'];
											} else {
												$start_date = $current_date;
											}

											if (!empty($rule_cus_price['end_date'])) {
												$end_date = $rule_cus_price['end_date'];
											} else {
												$end_date = $current_date;
											}

											if ($current_date < $start_date || $current_date > $end_date) {
												continue;
											}

											if (!empty($rule_cus_price['discount_value'])) {

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (( !empty($max_qty) )) {
							   
										return $max_qty;
												} 
											}
										}
									}
								}

								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (1 != count($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($max_qty) ) ) {
								
												return $max_qty;
										} 
									}
								}
							}
						}
					}
				} else {
		 

							// Variable Product.
						// Get customer specific price.
						$pro            = wc_get_product( $variation_id);
						$cus_base_price = get_post_meta($variation_id, '_cus_base_price', true);
						$product        = wc_get_product($variation_id);
						// Get role base price.
					foreach ($user->roles as $valuez) {

					$role_base_price = get_post_meta($variation_id, '_role_base_price_' . $valuez, true);

					$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}

									if (( !empty($max_qty) )) {
						 
							
									return $max_qty;
									} 
								}
							}
						}
					}

				// Role Based Pricing.
				// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

						// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
								$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							if (( !empty($max_qty) )) {
						 
							
							return $max_qty;
							} 
						}
					}

				// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $valuez) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $valuez, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {

									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {


											// Date Validation.
											$current_date = gmdate('Y-m-d');

											if (!empty($rule_cus_price['start_date'])) {
												$start_date = $rule_cus_price['start_date'];
											} else {
												$start_date = $current_date;
											}

											if (!empty($rule_cus_price['end_date'])) {
												$end_date = $rule_cus_price['end_date'];
											} else {
												$end_date = $current_date;
											}

											if ($current_date < $start_date || $current_date > $end_date) {
												continue;
											}


											if (!empty($rule_cus_price['discount_value'])) {

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (( !empty($max_qty) )) {
						 
												return $max_qty;
												} 
											}
										}
									}
								}

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {


										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}


										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($max_qty) )) {
						 
										return $max_qty;
										} 
									}
								}
							}
						}
					}

		 
				}
			} elseif (!is_user_logged_in()) {
			// if user not logged in starts
	 
 
				if (0 == $variation_id) {
				// Simple Products.
				// Get customer specific price.
				$pro            = wc_get_product($product_id);
				$cus_base_price = get_post_meta($product_id, '_cus_base_price', true);
				$product        = wc_get_product($product_id);
				// Get role base price.
				$role_base_price_guest = get_post_meta($product_id, '_role_base_price_guest', true);
				$afrbp_prices_guest    = unserialize($role_base_price_guest);

				// Role Based Pricing.
				// check if there is customer specific pricing then role base pricing will not work.
					if (!empty($afrbp_prices_guest) && !empty($afrbp_prices_guest['discount_value'])) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if (( !empty($max_qty) )) {
						 
								return $max_qty;
							} 
						}
					}
				// Rules - guest users.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);
								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices_guest)) {

									// Product Price.
									if (!empty($rule_afrbp_prices_guest['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices_guest['start_date'])) {
											$start_date = $rule_afrbp_prices_guest['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices_guest['end_date'])) {
											$end_date = $rule_afrbp_prices_guest['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
											$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
											$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($max_qty) )) {
						 
							
								return $max_qty;
										} 
									}
								}
							}
						}
					}



	 





				} else {
//if not logged in and variation id starts



	// Variable Products.
	// Simple Products.
	// Get customer specific price.
	$pro            = wc_get_product($variation_id);
	$cus_base_price = get_post_meta($variation_id, '_cus_base_price', true);
	$product        = wc_get_product($variation_id);
	// Get role base price.
	$role_base_price_guest = get_post_meta($variation_id, '_role_base_price_guest', true);
	$afrbp_prices_guest    = (array) unserialize($role_base_price_guest);

	// Role Based Pricing.
	// Check if there is customer specific pricing then role base pricing will not work.
					if (!empty($afrbp_prices_guest) && !empty($afrbp_prices_guest['discount_value'])) {

									// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

					$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

					$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

					// Date Validation.
					$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if (( !empty($max_qty) )) {
						 
						 
								return $max_qty;
							} 
						}
					}

				// Rules - guest users.
					if (empty($this->allfetchedrules)) {
						echo '';
					} else {
						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) )) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!isset($rule_afrbp_prices_guest['discount_value']) || empty($rule_afrbp_prices_guest['discount_value'])) {
									$rule_afrbp_prices_guest['discount_value'] = '';
								}
								// Product Price.
								if (!empty($rule_afrbp_prices_guest['discount_value'])) {

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($rule_afrbp_prices_guest['start_date'])) {
										$start_date = $rule_afrbp_prices_guest['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($rule_afrbp_prices_guest['end_date'])) {
										$end_date = $rule_afrbp_prices_guest['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}



									if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
										$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
										$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
									} else {
										$max_qty = '';
									}

									if (( !empty($max_qty) )) {
						 
							
									return $max_qty;
									} 
								}
							}
						}
					}
		 











//if not logged in varation id ends 


				}







	//if user not logged in ends 
			}

			return $value;
		}//end csp_update_cart_quantity_validation()






/**
* Function for `woocommerce_store_api_product_quantity_(value_type)` filter-hook.
* 
* @param mixed       $value     The value being filtered.
* @param \WC_Product $product   The product object.
* @param array|null  $cart_item The cart item if the product exists in the cart, or null.
*
* @return mixed
*/

		public function csp_update_cart_quantity_validation_cart_item_min( $value, $product, $cart_item ) {

		$product_id = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0;

		// Get the variation ID if it's a variation, or set to 0 if not
		$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;

		$user = wp_get_current_user();

			if (is_user_logged_in()) {

				if (0 == $variation_id) {
					// Simple Product.
					// Get customer specific price.
					$pro            = wc_get_product($product_id);
					$cus_base_price = get_post_meta($product_id, '_cus_base_price', true);
					$product        = wc_get_product($product_id);
					// Get role base price.
					foreach ($user->roles as $valuez) {

				$role_base_price = get_post_meta($product_id, '_role_base_price_' . $valuez, true);

				$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
								continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if (( !empty($min_qty) )) {
					
					   
										return $min_qty;
									} 
								}
							}
						}
					}

				// Role Based Pricing.
				// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

							// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
										$min_qty = intval($afrbp_prices['min_qty']);
							} else {
									$min_qty = '';
							}

			// Date Validation.
			$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
							$end_date = $afrbp_prices['end_date'];
							} else {
						$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							if (( !empty($min_qty) )) {
					
									return $min_qty;
							}
						}
					}

				// Rules.
					if (empty($this->allfetchedrules)) {

							echo '';
					} else {

							$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {



						foreach ($all_rules as $rule) {

			$istrue = false;

			$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
			$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
			$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);



			   
							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) )) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $valuez) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $valuez, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {
									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {

											// Date Validation.
											$current_date = gmdate('Y-m-d');

											if (!empty($rule_cus_price['start_date'])) {
												$start_date = $rule_cus_price['start_date'];
											} else {
												$start_date = $current_date;
											}

											if (!empty($rule_cus_price['end_date'])) {
												$end_date = $rule_cus_price['end_date'];
											} else {
												$end_date = $current_date;
											}

											if ($current_date < $start_date || $current_date > $end_date) {
												continue;
											}

											if (!empty($rule_cus_price['discount_value'])) {

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (( !empty($min_qty) )) {
						  
												return $min_qty;
												} 
											}
										}
									}
								}

								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (1 != count($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($min_qty) ) ) {
						   
											return $min_qty;
										} 
									}
								}
							}
						}
					}
				} else {
	

						// Variable Product.
					// Get customer specific price.
					$pro            = wc_get_product( $variation_id);
					$cus_base_price = get_post_meta($variation_id, '_cus_base_price', true);
					$product        = wc_get_product($variation_id);
					// Get role base price.
					foreach ($user->roles as $valuez) {

					$role_base_price = get_post_meta($variation_id, '_role_base_price_' . $valuez, true);

					$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}

									if (( !empty($min_qty) )) {
					
					   
								return $min_qty;
									} 
								}
							}
						}
					}

				// Role Based Pricing.
				// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

							// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
									$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

					// Date Validation.
					$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices['start_date'])) {
							$start_date = $afrbp_prices['start_date'];
							} else {
							$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
					$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							if (( !empty($min_qty) )) {
					
					   
									return $min_qty;
							} 
						}
					}

				// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

					$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

						$istrue = false;

						$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
						$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
					$categories                  = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
										$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
									$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $valuez) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $valuez, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {

									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {


											// Date Validation.
											$current_date = gmdate('Y-m-d');

											if (!empty($rule_cus_price['start_date'])) {
												$start_date = $rule_cus_price['start_date'];
											} else {
												$start_date = $current_date;
											}

											if (!empty($rule_cus_price['end_date'])) {
												$end_date = $rule_cus_price['end_date'];
											} else {
												$end_date = $current_date;
											}

											if ($current_date < $start_date || $current_date > $end_date) {
												continue;
											}


											if (!empty($rule_cus_price['discount_value'])) {

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (( !empty($min_qty) )) {
					
											return $min_qty;
												} 
											}
										}
									}
								}

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {


										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}


										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($min_qty) )) {
					
									return $min_qty;
										} 
									}
								}
							}
						}
					}

	
				}
			} elseif (!is_user_logged_in()) {
	// if user not logged in starts


				if (0 == $variation_id) {
					// Simple Products.
					// Get customer specific price.
					$pro            = wc_get_product($product_id);
					$cus_base_price = get_post_meta($product_id, '_cus_base_price', true);
					$product        = wc_get_product($product_id);
					// Get role base price.
					$role_base_price_guest = get_post_meta($product_id, '_role_base_price_guest', true);
					$afrbp_prices_guest    = unserialize($role_base_price_guest);

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (!empty($afrbp_prices_guest) && !empty($afrbp_prices_guest['discount_value'])) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if (( !empty($min_qty) )) {
					
								return $min_qty;
							} 
						}
					}
					// Rules - guest users.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);
								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices_guest)) {

									// Product Price.
									if (!empty($rule_afrbp_prices_guest['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices_guest['start_date'])) {
											$start_date = $rule_afrbp_prices_guest['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices_guest['end_date'])) {
											$end_date = $rule_afrbp_prices_guest['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
											$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
											$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($min_qty) )) {
					
					   
									return $min_qty;
										} 
									}
								}
							}
						}
					}









				} else {
		//if not logged in and variation id starts



		// Variable Products.
					// Simple Products.
					// Get customer specific price.
					$pro            = wc_get_product($variation_id);
					$cus_base_price = get_post_meta($variation_id, '_cus_base_price', true);
					$product        = wc_get_product($variation_id);
					// Get role base price.
					$role_base_price_guest = get_post_meta($variation_id, '_role_base_price_guest', true);
					$afrbp_prices_guest    = (array) unserialize($role_base_price_guest);

					// Role Based Pricing.
					// Check if there is customer specific pricing then role base pricing will not work.
					if (!empty($afrbp_prices_guest) && !empty($afrbp_prices_guest['discount_value'])) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $value;
							}

							if (( !empty($min_qty) )) {
					
					
								return $min_qty;
							} 
						}
					}

					// Rules - guest users.
					if (empty($this->allfetchedrules)) {
						echo '';
					} else {
						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) )) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!isset($rule_afrbp_prices_guest['discount_value']) || empty($rule_afrbp_prices_guest['discount_value'])) {
									$rule_afrbp_prices_guest['discount_value'] = '';
								}
								// Product Price.
								if (!empty($rule_afrbp_prices_guest['discount_value'])) {

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($rule_afrbp_prices_guest['start_date'])) {
										$start_date = $rule_afrbp_prices_guest['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($rule_afrbp_prices_guest['end_date'])) {
										$end_date = $rule_afrbp_prices_guest['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}



									if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
										$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
										$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
									} else {
										$max_qty = '';
									}

									if (( !empty($min_qty) )) {
					
					   
										return $min_qty;
									} 
								}
							}
						}
					}
	











		//if not logged in varation id ends 


				}







	//if user not logged in ends 
			}

return $value;
		}//end csp_update_cart_quantity_validation()




		/**
		 * Main function start.
		 */
		public function csp_front_scripts() {

			wp_enqueue_style('addify_csp_front_css', ADDIFY_CSP_URL . '/assets/css/addify_csp_front_css.css', false, '1.0');
			wp_enqueue_script('af_csp_front_js', ADDIFY_CSP_URL . 'assets/js/addify_csp_front_js.js', array( 'jquery' ), '1.0');
		}//end csp_front_scripts()


		/**
		 * Main function start.
		 *
		 * @return \WP_Post[]|int[]
		 */
		public function csp_load() {
			// Get Rules.
			$args = array(
				'post_type'   => 'csp_rules',
				'post_status' => 'publish',
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
				'numberposts' => -1,
			);
			return get_posts($args);
		}//end csp_load()


		/**
		 * Main function start.
		 *
		 * @param init $csppdata     Args.
		 *
		 * @param init $product_id   Args.
		 *
		 * @param init $qty          Args.
		 *
		 * @param init $variation_id Args.
		 * @return boolean|\init
		 */
		public function csp_validate_min_max_qty( $csppdata, $product_id, $qty, $variation_id = 0 ) {

			wc()->initialize_session();

			$user = wp_get_current_user();

			$prod = wc_get_product($variation_id);

			if (is_user_logged_in()) {

				if (0 == $variation_id) {
					// Simple Product.
					$targeted_id = $product_id;

					foreach (WC()->cart->get_cart() as $cart_item) {
						if ($cart_item['product_id'] == $targeted_id) {
							$oqty = $cart_item['quantity'];
							break; // Stop the loop if product is found.
						}
					}
					// Displaying the quantity if targeted product is in cart.
					if (!empty($oqty)) {

						$old_qty = $oqty;
					} else {
						$old_qty = 0;
					}

					// Get customer specific price.
					$cus_base_price = get_post_meta($product_id, '_cus_base_price', true);

					// Get role base price.
					foreach ($user->roles as $value) {

						$role_base_price = get_post_meta($product_id, '_role_base_price_' . $value, true);

						$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					// Get role base price.
					foreach ($user->roles as $value) {

						$role_base_price = get_post_meta($product_id, '_role_base_price_' . $value, true);

						$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}
									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
										$csppdata      = false;
										$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

										$csppdata      = false;
										$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} else {
										return true;
									}
								}
							}
						}
					}

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

						// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
								$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}
							// Date Validation.
							$current_date = gmdate('Y-m-d');

							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return true;
							}

							if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
								$csppdata      = false;
								$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

								$csppdata      = false;
								$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} else {
								return true;
							}
						}
					}

					// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					$product = wc_get_product($product_id);

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product_id, $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									// if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {.

									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product_id) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $value) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $value, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {
									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {

											if (!empty($rule_cus_price['discount_value'])) {

												// Date Validation.
												$current_date = gmdate('Y-m-d');

												if (!empty($rule_cus_price['start_date'])) {
													$start_date = $rule_cus_price['start_date'];
												} else {
													$start_date = $current_date;
												}

												if (!empty($rule_cus_price['end_date'])) {
													$end_date = $rule_cus_price['end_date'];
												} else {
													$end_date = $current_date;
												}

												if ($current_date < $start_date || $current_date > $end_date) {
													continue;
												}


												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (!empty($min_qty) && $old_qty + $qty < $min_qty) {

													$csppdata      = false;
													$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
													$this->csp_wc_add_notice($error_message);
													return $csppdata;
												} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

													$csppdata      = false;
													$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
													$this->csp_wc_add_notice($error_message);
													return $csppdata;
												} else {
													return true;
												}
											}
										}
									}
								}

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
											$csppdata      = false;
											$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
											$this->csp_wc_add_notice($error_message);
											return $csppdata;
										} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

											$csppdata      = false;
											$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
											$this->csp_wc_add_notice($error_message);
											return $csppdata;
										} else {
											return true;
										}
									}
								}
							}
						}
					}
				} else {

					// Variable Product.
					$targeted_id = $variation_id;

					foreach (WC()->cart->get_cart() as $cart_item) {
						if ($cart_item['variation_id'] == $targeted_id) {
							$oqty = $cart_item['quantity'];
							break; // Stop the loop if product is found.
						}
					}
					// Displaying the quantity if targeted product is in cart.
					if (!empty($oqty)) {

						$old_qty = $oqty;
					} else {
						$old_qty = 0;
					}

					// Get customer specific price.
					$cus_base_price = get_post_meta($variation_id, '_cus_base_price', true);

					// Get role base price.
					foreach ($user->roles as $value) {

						$role_base_price = get_post_meta($variation_id, '_role_base_price_' . $value, true);

						$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}
									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
										$csppdata      = false;
										$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

										$csppdata      = false;
										$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} else {
										return true;
									}
								}
							}
						}
					}

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

						// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
								$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							// Date Validation.
							$current_date = gmdate('Y-m-d');

							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return true;
							}

							if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
								$csppdata      = false;
								$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

								$csppdata      = false;
								$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} else {
								return true;
							}
						}
					}

					// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);
							$product                 = wc_get_product($product_id);
							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

								//  if (!empty($cat) && ( has_term($cat, 'product_cat', $product_id) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {.

									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product_id) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {
										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $value) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $value, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {
									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {

											if (!empty($rule_cus_price['discount_value'])) {

												// Date Validation.
												$current_date = gmdate('Y-m-d');

												if (!empty($rule_cus_price['start_date'])) {
													$start_date = $rule_cus_price['start_date'];
												} else {
													$start_date = $current_date;
												}

												if (!empty($rule_cus_price['end_date'])) {
													$end_date = $rule_cus_price['end_date'];
												} else {
													$end_date = $current_date;
												}

												if ($current_date < $start_date || $current_date > $end_date) {
													continue;
												}

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
													$csppdata      = false;
													$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
													$this->csp_wc_add_notice($error_message);
													return $csppdata;
												} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

													$csppdata      = false;
													$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
													$this->csp_wc_add_notice($error_message);
													return $csppdata;
												} else {
													return true;
												}
											}
										}
									}
								}

								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
											$csppdata      = false;
											$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
											$this->csp_wc_add_notice($error_message);
											return $csppdata;
										} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

											$csppdata      = false;
											$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
											$this->csp_wc_add_notice($error_message);
											return $csppdata;
										} else {
											return true;
										}
									}
								}
							}
						}
					}
				}
			} elseif (!is_user_logged_in()) {

				// For Guest Users.
				if (0 == $variation_id) {
					// Simple Product.
					$targeted_id = $product_id;

					foreach (WC()->cart->get_cart() as $cart_item) {
						if ($cart_item['product_id'] == $targeted_id) {
							$oqty = $cart_item['quantity'];
							break; // Stop the loop if product is found.
						}
					}
					// Displaying the quantity if targeted product is in cart.
					if (!empty($oqty)) {

						$old_qty = $oqty;
					} else {
						$old_qty = 0;
					}

					// Get customer specific price.
					$cus_base_price = get_post_meta($product_id, '_cus_base_price', true);

					// Get role base price.
					$role_base_price_guest = get_post_meta($product_id, '_role_base_price_guest', true);
					$afrbp_prices_guest    = (array) unserialize($role_base_price_guest);

					// Role Based Pricing.
					// Check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices_guest)) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;
							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return true;
							}

							if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
								$csppdata      = false;
								$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

								$csppdata      = false;
								$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} else {
								return true;
							}
						}
					}

					// Rules - guest users.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);
							$product                 = wc_get_product($product_id);
							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									//if (!empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {.
									
									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {
								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);

								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								// Date Validation.
								$current_date = gmdate('Y-m-d');

								if (!empty($rule_afrbp_prices_guest['start_date'])) {
									$start_date = $rule_afrbp_prices_guest['start_date'];
								} else {
									$start_date = $current_date;
								}

								if (!empty($rule_afrbp_prices_guest['end_date'])) {
									$end_date = $rule_afrbp_prices_guest['end_date'];
								} else {
									$end_date = $current_date;
								}

								if ($current_date < $start_date || $current_date > $end_date) {
									continue;
								}

								if (!empty($rule_afrbp_prices_guest['discount_value'])) {

									if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
										$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
										$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
									} else {
										$max_qty = '';
									}

									if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
										$csppdata      = false;
										$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

										$csppdata      = false;
										$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} else {
										return true;
									}
								}
							}
						}
					}
				} else {

					// Variable Product.
					$targeted_id = $variation_id;

					foreach (WC()->cart->get_cart() as $cart_item) {
						if ($cart_item['variation_id'] == $targeted_id) {
							$oqty = $cart_item['quantity'];
							break; // Stop the loop if product is found.
						}
					}
					// Displaying the quantity if targeted product is in cart.
					if (!empty($oqty)) {

						$old_qty = $oqty;
					} else {
						$old_qty = 0;
					}

					// Get customer specific price.
					$cus_base_price = get_post_meta($variation_id, '_cus_base_price', true);

					// Get role base price.
					$role_base_price_guest = get_post_meta($variation_id, '_role_base_price_guest', true);
					$afrbp_prices_guest    = (array) unserialize($role_base_price_guest);

					// Role Based Pricing.
					// Check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices_guest)) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return true;
							}

							if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
								$csppdata      = false;
								$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

								$csppdata      = false;
								$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
								$this->csp_wc_add_notice($error_message);
								return $csppdata;
							} else {
								return true;
							}
						}
					}

					// Rules - guest users.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);
							$product                 = wc_get_product($product_id);
							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									//if (!empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {
									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);

								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								// Date Validation.
								$current_date = gmdate('Y-m-d');

								if (!empty($rule_afrbp_prices_guest['start_date'])) {
									$start_date = $rule_afrbp_prices_guest['start_date'];
								} else {
									$start_date = $current_date;
								}

								if (!empty($rule_afrbp_prices_guest['end_date'])) {
									$end_date = $rule_afrbp_prices_guest['end_date'];
								} else {
									$end_date = $current_date;
								}

								if ($current_date < $start_date || $current_date > $end_date) {
									continue;
								}


								if (!empty($rule_afrbp_prices_guest['discount_value'])) {

									if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
										$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
										$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
									} else {
										$max_qty = '';
									}

									if (!empty($min_qty) && $old_qty + $qty < $min_qty) {
										$csppdata      = false;
										$error_message = sprintf(get_option('csp_min_qty_error_msg'), $min_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} elseif (!empty($max_qty) && $old_qty + $qty > $max_qty) {

										$csppdata      = false;
										$error_message = sprintf(get_option('csp_max_qty_error_msg'), $max_qty);
										$this->csp_wc_add_notice($error_message);
										return $csppdata;
									} else {
										return true;
									}
								}
							}
						}
					}
				}
			}

			return $csppdata;
		}//end csp_validate_min_max_qty()


		/**
		 * Main function start.
		 *
		 * @param init $passed        Args.
		 *
		 * @param init $cart_item_key Args.
		 *
		 * @param init $values        Args.
		 *
		 * @param init $qty           Args.
		 * 
		 * @return false|\init
		 */
		public function csp_update_cart_quantity_validation( $passed, $cart_item_key, $values, $qty ) {

			$user = wp_get_current_user();

			if (is_user_logged_in()) {

				if (0 == $values['variation_id']) {
					// Simple Product.
					// Get customer specific price.
					$pro            = wc_get_product($values['product_id']);
					$cus_base_price = get_post_meta($values['product_id'], '_cus_base_price', true);
					$product        = wc_get_product($values['product_id']);
					// Get role base price.
					foreach ($user->roles as $value) {

						$role_base_price = get_post_meta($values['product_id'], '_role_base_price_' . $value, true);

						$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
										$passed        = false;
										$arr           = array(
											'%pro' => $pro->get_title(),
											'%min' => $min_qty,
											'%max' => $max_qty,
										);
										$word          = get_option('csp_update_cart_error_msg');
										$error_message = strtr($word, $arr);

										$this->csp_wc_add_notice($error_message);
										return $passed;
									} else {
										return $passed;
									}
								}
							}
						}
					}

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {

						// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
								$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $passed;
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
								$passed        = false;
								$arr           = array(
									'%pro' => $pro->get_title(),
									'%min' => $min_qty,
									'%max' => $max_qty,
								);
								$word          = get_option('csp_update_cart_error_msg');
								$error_message = strtr($word, $arr);

								$this->csp_wc_add_notice($error_message);
								return $passed;
							} else {
								return $passed;
							}
						}
					}

					// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									// if (!empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {
									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {


										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $value) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $value, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {
									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {

											// Date Validation.
											$current_date = gmdate('Y-m-d');

											if (!empty($rule_cus_price['start_date'])) {
												$start_date = $rule_cus_price['start_date'];
											} else {
												$start_date = $current_date;
											}

											if (!empty($rule_cus_price['end_date'])) {
												$end_date = $rule_cus_price['end_date'];
											} else {
												$end_date = $current_date;
											}

											if ($current_date < $start_date || $current_date > $end_date) {
												continue;
											}

											if (!empty($rule_cus_price['discount_value'])) {

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
													$passed        = false;
													$arr           = array(
														'%pro' => $pro->get_title(),
														'%min' => $min_qty,
														'%max' => $max_qty,
													);
													$word          = get_option('csp_update_cart_error_msg');
													$error_message = strtr($word, $arr);

													$this->csp_wc_add_notice($error_message);
													return $passed;
												} else {
													return $passed;
												}
											}
										}
									}
								}

								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (1 != count($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
											$passed        = false;
											$arr           = array(
												'%pro' => $pro->get_title(),
												'%min' => $min_qty,
												'%max' => $max_qty,
											);
											$word          = get_option('csp_update_cart_error_msg');
											$error_message = strtr($word, $arr);

											$this->csp_wc_add_notice($error_message);
											return $passed;
										} else {
											return $passed;
										}
									}
								}
							}
						}
					}
				} else {

					// Variable Product.
					// Get customer specific price.
					$pro            = wc_get_product($values['variation_id']);
					$cus_base_price = get_post_meta($values['variation_id'], '_cus_base_price', true);
					$product        = wc_get_product($values['variation_id']);
					// Get role base price.
					foreach ($user->roles as $value) {

						$role_base_price = get_post_meta($values['variation_id'], '_role_base_price_' . $value, true);

						$afrbp_prices = (array) unserialize($role_base_price);

						if (empty($afrbp_prices['discount_value']) || empty($afrbp_prices['discount_type'])) {
							continue;
						} else {
							break;
						}
					}

					if (!empty($cus_base_price)) {
						foreach ($cus_base_price as $cus_price) {

							if (!empty($cus_price['customer_name']) && $user->ID == $cus_price['customer_name']) {

								if (!empty($cus_price['discount_value'])) {

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($cus_price['start_date'])) {
										$start_date = $cus_price['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($cus_price['end_date'])) {
										$end_date = $cus_price['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}

									if ('' != $cus_price['min_qty'] || 0 != $cus_price['min_qty']) {
										$min_qty = intval($cus_price['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $cus_price['max_qty'] || 0 != $cus_price['max_qty']) {
										$max_qty = intval($cus_price['max_qty']);
									} else {
										$max_qty = '';
									}

									if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
										$passed        = false;
										$arr           = array(
											'%pro' => $pro->get_title(),
											'%min' => $min_qty,
											'%max' => $max_qty,
										);
										$word          = get_option('csp_update_cart_error_msg');
										$error_message = strtr($word, $arr);

										$this->csp_wc_add_notice($error_message);
										return $passed;
									} else {
										return $passed;
									}
								}
							}
						}
					}

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (1 != count($afrbp_prices)) {



						// Product Price.
						if (!empty($afrbp_prices['discount_value'])) {

							if ('' != $afrbp_prices['min_qty'] || 0 != $afrbp_prices['min_qty']) {
								$min_qty = intval($afrbp_prices['min_qty']);
							} else {
								$min_qty = '';
							}

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices['start_date'])) {
								$start_date = $afrbp_prices['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices['end_date'])) {
								$end_date = $afrbp_prices['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $passed;
							}

							if ('' != $afrbp_prices['max_qty'] || 0 != $afrbp_prices['max_qty']) {
								$max_qty = intval($afrbp_prices['max_qty']);
							} else {
								$max_qty = '';
							}

							if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
								$passed        = false;
								$arr           = array(
									'%pro' => $pro->get_title(),
									'%min' => $min_qty,
									'%max' => $max_qty,
								);
								$word          = get_option('csp_update_cart_error_msg');
								$error_message = strtr($word, $arr);

								$this->csp_wc_add_notice($error_message);
								return $passed;
							} else {
								return $passed;
							}
						}
					}

					// Rules.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									//if (!empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {.
									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								foreach ($user->roles as $value) {

									$rule_role_base_price = get_post_meta($rule->ID, 'rrole_base_price_' . $value, true);

									$rule_afrbp_prices = (array) unserialize($rule_role_base_price);

									if (empty($rule_afrbp_prices['discount_value']) || empty($rule_afrbp_prices['discount_type'])) {
										continue;
									} else {
										break;
									}
								}

								if (!empty($rule_cus_base_price)) {

									foreach ($rule_cus_base_price as $rule_cus_price) {

										if (!empty($rule_cus_price['customer_name']) && $user->ID == $rule_cus_price['customer_name']) {


											// Date Validation.
											$current_date = gmdate('Y-m-d');

											if (!empty($rule_cus_price['start_date'])) {
												$start_date = $rule_cus_price['start_date'];
											} else {
												$start_date = $current_date;
											}

											if (!empty($rule_cus_price['end_date'])) {
												$end_date = $rule_cus_price['end_date'];
											} else {
												$end_date = $current_date;
											}

											if ($current_date < $start_date || $current_date > $end_date) {
												continue;
											}


											if (!empty($rule_cus_price['discount_value'])) {

												if ('' != $rule_cus_price['min_qty'] || 0 != $rule_cus_price['min_qty']) {
													$min_qty = intval($rule_cus_price['min_qty']);
												} else {
													$min_qty = '';
												}

												if ('' != $rule_cus_price['max_qty'] || 0 != $rule_cus_price['max_qty']) {
													$max_qty = intval($rule_cus_price['max_qty']);
												} else {
													$max_qty = '';
												}

												if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
													$passed        = false;
													$arr           = array(
														'%pro' => $pro->get_title(),
														'%min' => $min_qty,
														'%max' => $max_qty,
													);
													$word          = get_option('csp_update_cart_error_msg');
													$error_message = strtr($word, $arr);

													$this->csp_wc_add_notice($error_message);
												} else {
													return $passed;
												}
											}
										}
									}
								}

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices)) {

									// Product Price.
									if (!empty($rule_afrbp_prices['discount_value'])) {


										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices['start_date'])) {
											$start_date = $rule_afrbp_prices['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices['end_date'])) {
											$end_date = $rule_afrbp_prices['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}


										if ('' != $rule_afrbp_prices['min_qty'] || 0 != $rule_afrbp_prices['min_qty']) {
											$min_qty = intval($rule_afrbp_prices['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices['max_qty'] || 0 != $rule_afrbp_prices['max_qty']) {
											$max_qty = intval($rule_afrbp_prices['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
											$passed        = false;
											$arr           = array(
												'%pro' => $pro->get_title(),
												'%min' => $min_qty,
												'%max' => $max_qty,
											);
											$word          = get_option('csp_update_cart_error_msg');
											$error_message = strtr($word, $arr);

											$this->csp_wc_add_notice($error_message);
											return $passed;
										} else {
											return $passed;
										}
									}
								}
							}
						}
					}
				}
			} elseif (!is_user_logged_in()) {

				// For Guest Users.
				if (0 == $values['variation_id']) {
					// Simple Products.
					// Get customer specific price.
					$pro            = wc_get_product($values['product_id']);
					$cus_base_price = get_post_meta($values['product_id'], '_cus_base_price', true);
					$product        = wc_get_product($values['product_id']);
					// Get role base price.
					$role_base_price_guest = get_post_meta($values['product_id'], '_role_base_price_guest', true);
					$afrbp_prices_guest    = unserialize($role_base_price_guest);

					// Role Based Pricing.
					// check if there is customer specific pricing then role base pricing will not work.
					if (!empty($afrbp_prices_guest) && !empty($afrbp_prices_guest['discount_value'])) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $passed;
							}

							if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
								$passed        = false;
								$arr           = array(
									'%pro' => $pro->get_title(),
									'%min' => $min_qty,
									'%max' => $max_qty,
								);
								$word          = get_option('csp_update_cart_error_msg');
								$error_message = strtr($word, $arr);

								$this->csp_wc_add_notice($error_message);
								return $passed;
							} else {
								return $passed;
							}
						}
					}
					// Rules - guest users.
					if (empty($this->allfetchedrules)) {

						echo '';
					} else {

						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

								//  if (!empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {.
									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);
								// Role Based Pricing.
								// Check if there is customer specific pricing then role base pricing will not work.
								if (!empty($rule_afrbp_prices_guest)) {

									// Product Price.
									if (!empty($rule_afrbp_prices_guest['discount_value'])) {

										// Date Validation.
										$current_date = gmdate('Y-m-d');

										if (!empty($rule_afrbp_prices_guest['start_date'])) {
											$start_date = $rule_afrbp_prices_guest['start_date'];
										} else {
											$start_date = $current_date;
										}

										if (!empty($rule_afrbp_prices_guest['end_date'])) {
											$end_date = $rule_afrbp_prices_guest['end_date'];
										} else {
											$end_date = $current_date;
										}

										if ($current_date < $start_date || $current_date > $end_date) {
											continue;
										}

										if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
											$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
										} else {
											$min_qty = '';
										}

										if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
											$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
										} else {
											$max_qty = '';
										}

										if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
											$passed        = false;
											$arr           = array(
												'%pro' => $pro->get_title(),
												'%min' => $min_qty,
												'%max' => $max_qty,
											);
											$word          = get_option('csp_update_cart_error_msg');
											$error_message = strtr($word, $arr);

											$this->csp_wc_add_notice($error_message);
											return $passed;
										} else {
											return $passed;
										}
									}
								}
							}
						}
					}
				} else {
					// Variable Products.
					// Simple Products.
					// Get customer specific price.
					$pro            = wc_get_product($values['variation_id']);
					$cus_base_price = get_post_meta($values['variation_id'], '_cus_base_price', true);
					$product        = wc_get_product($values['variation_id']);
					// Get role base price.
					$role_base_price_guest = get_post_meta($values['variation_id'], '_role_base_price_guest', true);
					$afrbp_prices_guest    = (array) unserialize($role_base_price_guest);

					// Role Based Pricing.
					// Check if there is customer specific pricing then role base pricing will not work.
					if (!empty($afrbp_prices_guest) && !empty($afrbp_prices_guest['discount_value'])) {

						// Product Price.
						if (!empty($afrbp_prices_guest['discount_value'])) {

							$min_qty = isset($afrbp_prices_guest['min_qty']) ? intval($afrbp_prices_guest['min_qty']) : 0;

							$max_qty = isset($afrbp_prices_guest['max_qty']) ? intval($afrbp_prices_guest['max_qty']) : 0;

							// Date Validation.
							$current_date = gmdate('Y-m-d');
							if (!empty($afrbp_prices_guest['start_date'])) {
								$start_date = $afrbp_prices_guest['start_date'];
							} else {
								$start_date = $current_date;
							}

							if (!empty($afrbp_prices_guest['end_date'])) {
								$end_date = $afrbp_prices_guest['end_date'];
							} else {
								$end_date = $current_date;
							}

							if ($current_date < $start_date || $current_date > $end_date) {
								return $passed;
							}

							if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
								$passed        = false;
								$arr           = array(
									'%pro' => $pro->get_title(),
									'%min' => $min_qty,
									'%max' => $max_qty,
								);
								$word          = get_option('csp_update_cart_error_msg');
								$error_message = strtr($word, $arr);

								$this->csp_wc_add_notice($error_message);
								return $passed;
							} else {
								return $passed;
							}
						}
					}

					// Rules - guest users.
					if (empty($this->allfetchedrules)) {
						echo '';
					} else {
						$all_rules = $this->allfetchedrules;
					}

					if (!empty($all_rules)) {
						foreach ($all_rules as $rule) {

							$istrue = false;

							$applied_on_all_products = get_post_meta($rule->ID, 'csp_apply_on_all_products', true);
							$products                = get_post_meta($rule->ID, 'csp_applied_on_products', true);
							$categories              = get_post_meta($rule->ID, 'csp_applied_on_categories', true);

							if ('yes' == $applied_on_all_products) {
								$istrue = true;
							} elseif (!empty($products) && ( in_array($product->get_id(), $products) || in_array($product->get_parent_id(), $products) )) {
								$istrue = true;
							}


							if (!empty($categories)) {
								foreach ($categories as $cat) {

									//if (!empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) ) || ( has_term($cat, 'product_cat', $product->get_parent_id()) )) {.
									if ( !empty($cat) && ( has_term($cat, 'product_cat', $product->get_id()) || has_term($cat, 'product_cat', $product->get_parent_id()) ) ) {

										$istrue = true;
									}
								}
							}

							if ($istrue) {

								// Get Rule customer specific price.
								$rule_cus_base_price = get_post_meta($rule->ID, 'rcus_base_price', true);

								// Get role base price.
								$rule_role_base_price_guest = get_post_meta($rule->ID, 'rrole_base_price_guest', true);
								$rule_afrbp_prices_guest    = (array) unserialize($rule_role_base_price_guest);

								// Role Based Pricing.
								// check if there is customer specific pricing then role base pricing will not work.
								if (!isset($rule_afrbp_prices_guest['discount_value']) || empty($rule_afrbp_prices_guest['discount_value'])) {
									$rule_afrbp_prices_guest['discount_value'] = '';
								}
								// Product Price.
								if (!empty($rule_afrbp_prices_guest['discount_value'])) {

									// Date Validation.
									$current_date = gmdate('Y-m-d');

									if (!empty($rule_afrbp_prices_guest['start_date'])) {
										$start_date = $rule_afrbp_prices_guest['start_date'];
									} else {
										$start_date = $current_date;
									}

									if (!empty($rule_afrbp_prices_guest['end_date'])) {
										$end_date = $rule_afrbp_prices_guest['end_date'];
									} else {
										$end_date = $current_date;
									}

									if ($current_date < $start_date || $current_date > $end_date) {
										continue;
									}



									if ('' != $rule_afrbp_prices_guest['min_qty'] || 0 != $rule_afrbp_prices_guest['min_qty']) {
										$min_qty = intval($rule_afrbp_prices_guest['min_qty']);
									} else {
										$min_qty = '';
									}

									if ('' != $rule_afrbp_prices_guest['max_qty'] || 0 != $rule_afrbp_prices_guest['max_qty']) {
										$max_qty = intval($rule_afrbp_prices_guest['max_qty']);
									} else {
										$max_qty = '';
									}

									if (( !empty($min_qty) && $qty < $min_qty ) || ( !empty($max_qty) && $qty > $max_qty )) {
										$passed        = false;
										$arr           = array(
											'%pro' => $pro->get_title(),
											'%min' => $min_qty,
											'%max' => $max_qty,
										);
										$word          = get_option('csp_update_cart_error_msg');
										$error_message = strtr($word, $arr);

										$this->csp_wc_add_notice($error_message);
										return $passed;
									} else {
										return $passed;
									}
								}
							}
						}
					}
				}
			}

			return $passed;
		}//end csp_update_cart_quantity_validation()


		/**
		 * Main function start.
		 *
		 * @param init $string Args.
		 *
		 * @param init $type   Args.
		 * @return void
		 */
		public function csp_wc_add_notice( $string, $type = 'error' ) {

			wc()->initialize_session();
			wc()->session->set_customer_session_cookie(true);

			global $woocommerce;
			if (version_compare($woocommerce->version, 2.1, '>=')) {
				wc_add_notice($string, $type);
			} else {
				$woocommerce->add_error($string);
			}
		}//end csp_wc_add_notice()
	}//end class


	new AF_C_S_P_Front();
}
add_action('wp_enqueue_scripts', function () {

	wp_enqueue_script('jquery');

	wp_enqueue_script('my-ajax-script', plugins_url('../assets/js/addify_csp_front_js.js', __FILE__), false, '1.0.0', $in_footer = false);
	
	// Localize script with the AJAX URL and any additional data
	wp_localize_script('my-ajax-script', 'myAjaxObject', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce'   => wp_create_nonce('my_ajax_nonce'), // Optional for security
	));
});
