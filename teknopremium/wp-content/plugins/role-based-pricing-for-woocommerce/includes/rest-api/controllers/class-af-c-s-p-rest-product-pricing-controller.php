<?php

/**
 * REST API role based pricing controller
 *
 * Handles requests to the /role_based_pricing/product endpoint.
 *
 * @package Addify\RestApi
 * @since    3.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * REST API role based pricing controller class.
 *
 * @package Addify\RestApi
 * @extends WC_REST_CRUD_Controller
 */
class AF_C_S_P_Rest_Product_Pricing_Controller extends WC_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'role_based_pricing';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Price Controller Object.
	 *
	 * @var string
	 */
	protected $price_controller;

	/**
	 * Role based pricing actions.
	 */
	public function __construct() {
	}//end __construct()


	/**
	 * Register the routes for role based pricing.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/product/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __('product id to get the role based price.', 'addify_role_price'),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_product_prices' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}//end register_routes()

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {

		$object = $this->get_object((int) $request['id']);

		if ($object && 0 !== $object->get_id() && !wc_rest_check_post_permissions($this->post_type, 'read', $object->get_id())) {
			return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot view this resource.', 'woocommerce'), array( 'status' => rest_authorization_required_code() ));
		}

		return true;
	}//end get_item_permissions_check()


	/**
	 * Prepare a single product output for response.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @since  3.0.0
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$context       = !empty($request['context']) ? $request['context'] : 'view';
		$this->request = $request;
		$data          = $this->get_rule_data($object, $context, $request);

		$data     = $this->filter_response_by_context($data, $context);
		$response = rest_ensure_response($data);

		/*
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */

		return apply_filters('woocommerce_rest_prepare_csp_rules_object', $response, $object, $request);
	}//end prepare_object_for_response()

	/**
	 * Get Rules Data
	 *
	 * @param mixed $object  Args.
	 * @param mixed $context Args.
	 * @param mixed $request Args.
	 * @return array
	 */
	public function get_rule_data( $object, $context, $request ) {

		$rule_data = array();

		$rule_data['rule_id']           = $object->ID;
		$rule_data['title']             = $object->post_title;
		$rule_data['all_products']      = get_post_meta($object->ID, 'csp_apply_on_all_products', true);
		$rule_data['products']          = get_post_meta($object->ID, 'csp_applied_on_products', true);
		$rule_data['categories']        = get_post_meta($object->ID, 'csp_applied_on_categories', true);
		$rule_data['date_created']      = $object->post_date;
		$rule_data['date_created_gmt']  = $object->post_date_gmt;
		$rule_data['date_modified']     = $object->post_modified;
		$rule_data['date_modified_gmt'] = $object->post_modified_gmt;
		$rule_data['post_status']       = $object->post_status;
		$rule_data['priority']          = $object->menu_order;
		$rule_data['customers_rules']   = get_post_meta($object->ID, 'rcus_base_price', true);
		$rule_data['user_roles_rules']  = $this->get_roles_data($object->ID);

		return $rule_data;
	}//end get_rule_data()

	/**
	 * Get Product Data
	 *
	 * @param mixed $object  Args.
	 * @param mixed $context Args.
	 * @param mixed $request Args.
	 * @return array
	 */
	public function get_product_level( $product, $context, $request ) {

		$rule_data = array();

		// Get customer specific price.
		$cus_base_price  = get_post_meta($product->get_id(), '_cus_base_price', true);
		$role_base_price = $this->get_product_roles_data($product->get_id());

		if ('variable' != $product->get_type()) {

			$rule_data['product_id']       = $product->get_id();
			$rule_data['title']            = $product->get_name();
			$rule_data['sku']              = $product->get_sku();
			$rule_data['type']             = $product->get_type();
			$rule_data['customers_rules']  = $cus_base_price;
			$rule_data['user_roles_rules'] = $role_base_price;
		} else {

			$rule_data['product_id'] = $product->get_id();
			$rule_data['title']      = $product->get_name();
			$rule_data['sku']        = $product->get_sku();
			$rule_data['type']       = $product->get_type();

			foreach ($product->get_children() as $product_id) {

				if (!wc_rest_check_post_permissions($this->post_type, 'read', $product_id)) {
					continue;
				}

				$cus_base_price  = get_post_meta($product_id, '_cus_base_price', true);
				$role_base_price = $this->get_product_roles_data($product_id);

				$variation = wc_get_product($product_id);

				$rule_data['variations'][ $product_id ]['variation_id']     =  $variation->get_id();
				$rule_data['variations'][ $product_id ]['title']            =  $variation->get_name();
				$rule_data['variations'][ $product_id ]['type']             =  $variation->get_type();
				$rule_data['variations'][ $product_id ]['customers_rules']  =  $cus_base_price;
				$rule_data['variations'][ $product_id ]['user_roles_rules'] =  $role_base_price;
			}
		}

		return $rule_data;
	}//end get_product_level()

	/**
	 * Get Object
	 *
	 * @param integer $product_id Args.
	 * @return object
	 */
	protected function get_object( $product_id ) {

		return wc_get_product($product_id);
	}//end get_object()


	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Product role based pricing rules',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __('Unique identifier for the resource.', 'addify_role_price'),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'             => array(
					'description' => __('Rule title.', 'addify_role_price'),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'all_products'      => array(
					'description' => __('Rules applied on all products or not.', 'addify_role_price'),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'products'          => array(
					'description' => __('Applied products ids.', 'addify_role_price'),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'categories'        => array(
					'description' => __('Applied categories ids.', 'addify_role_price'),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'      => array(
					'description' => __("The date the product was created, in the site's timezone.", 'addify_role_price'),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'  => array(
					'description' => __('The date the product was created, as GMT.', 'addify_role_price'),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'     => array(
					'description' => __("The date the product was last modified, in the site's timezone.", 'addify_role_price'),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __('The date the product was last modified, as GMT.', 'addify_role_price'),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'post_status'       => array(
					'description' => __('Rule status (post status).', 'addify_role_price'),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge(array_keys(get_post_statuses()), array( 'future' )),
					'context'     => array( 'view', 'edit' ),
				),
				'priority'          => array(
					'description' => __('Rule Priority.', 'addify_role_price'),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'customers_rules'   => array(
					'description' => __('List of customer rules.', 'addify_role_price'),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'array',
						'properties' => array(
							'customer_id'           => array(
								'description' => __('User ID.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_type'         => array(
								'description' => __('Discount Type.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_value'        => array(
								'description' => __('Discount value.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'min_qty'               => array(
								'description' => __('Minimum Quantity.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'max_qty'               => array(
								'description' => __('Maximum Quantity.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'replace_orignal_price' => array(
								'description' => __('Replace Original Price.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'user_roles_rules'  => array(
					'description' => __('List of customer rules.', 'addify_role_price'),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'array',
						'properties' => array(
							'user_role'             => array(
								'description' => __('User role.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_type'         => array(
								'description' => __('Discount Type.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_value'        => array(
								'description' => __('Discount value.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'min_qty'               => array(
								'description' => __('Minimum Quantity.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'max_qty'               => array(
								'description' => __('Maximum Quantity.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'replace_orignal_price' => array(
								'description' => __('Replace Original Price.', 'addify_role_price'),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);
		return $this->add_additional_fields_schema($schema);
	}//end get_item_schema()

	/**
	 * Get roles data
	 *
	 * @param mixed $product_id Args.
	 * @return array
	 */
	public function get_roles_data( $product_id ) {

		$roles_data = array();

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = 'Guest';

		foreach ($roles as $key => $value) {

			$rule_role_base_price = get_post_meta($product_id, 'rrole_base_price_' . $key, true);
			$roles_data[ $key ]   = (array) unserialize($rule_role_base_price);
		}

		return $roles_data;
	}//end get_roles_data()

	/**
	 * Get roles data
	 *
	 * @param mixed $product_id Args.
	 * @return array
	 */
	public function get_product_roles_data( $product_id ) {

		$roles_data = array();

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = 'Guest';

		foreach ($roles as $key => $value) {

			$rule_role_base_price = get_post_meta($product_id, '_role_base_price_' . $key, true);
			$roles_data[ $key ]   = (array) unserialize($rule_role_base_price);
		}

		return $roles_data;
	}//end get_product_roles_data()

	/**
	 * Get Prices
	 *
	 * @param mixed  $request Args.
	 * @param string $context Args.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_product_prices( $request, $context = 'view' ) {

		$product = $this->get_object((int) $request['id']);

		if (!$product || 0 === $product->get_id()) {
			return new WP_Error("woocommerce_rest_{$this->post_type}_invalid_id", __('Invalid ID.', 'addify_role_price'), array( 'status' => 404 ));
		}

		$rules_Ids = get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'csp_rules',
				'post_status' => 'publish',
				'orderby'     => 'menu_order',
				'fields'      => 'ids',
			)
		);

		$data = array();

		$data[] = $this->get_product_level($product, $context, $request);

		foreach ($rules_Ids as $rule_id) {

			$all        = get_post_meta($rule_id, 'csp_apply_on_all_products', true);
			$products   = (array) get_post_meta($rule_id, 'csp_applied_on_products', true);
			$categories = (array) get_post_meta($rule_id, 'csp_applied_on_categories', true);

			if ('yes' == $all || in_array($product->get_id(), $products)) {
				$rule_object = get_post($rule_id);
				$data[]      = $this->prepare_object_for_response($rule_object, $request);
			} elseif (!empty($categories) && has_term($categories, 'product_cat', $product->get_id())) {
				$rule_object = get_post($rule_id);
				$data[]      = $this->prepare_object_for_response($rule_object, $request);
			}
		}

		$response = rest_ensure_response($data);

		return $response;
	}//end get_product_prices()
}//end class
