<?php
/**
 * REST API role based pricing controller
 *
 * Handles requests to the /role_based_pricing/category endpoint.
 *
 * @package Addify\RestApi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API role based pricing controller class.
 */
class AF_C_S_P_Rest_Category_Pricing_Controller extends WC_REST_Product_Categories_V2_Controller {

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
	 * Register the routes for role based pricing.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/category/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Category id to get the role based price.', 'addify_role_price' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_category_prices' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}//end register_routes()


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
		$context       = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$this->request = $request;
		$data          = $this->get_rule_data( $object, $context, $request );

		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

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
		return apply_filters( 'woocommerce_rest_prepare_csp_rules_object', $response, $object, $request );
	}//end prepare_object_for_response()

	/**
	 * Get Rules
	 *
	 * @param mixed $rule_id Args.
	 * @return array
	 */
	public function get_roles_data( $rule_id ) {

		$roles_data = array();

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = 'Guest';

		foreach ( $roles as $key => $value ) {

			$rule_role_base_price = get_post_meta( $rule_id, 'rrole_base_price_' . $key, true );
			$roles_data[ $key ]   = (array) unserialize( $rule_role_base_price );
		}

		return $roles_data;
	}//end get_roles_data()

	/**
	 * Get Rule data
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
		$rule_data['all_products']      = get_post_meta( $object->ID, 'csp_apply_on_all_products', true);
		$rule_data['products']          = get_post_meta( $object->ID, 'csp_applied_on_products', true);
		$rule_data['categories']        = get_post_meta( $object->ID, 'csp_applied_on_categories', true);
		$rule_data['date_created']      = $object->post_date;
		$rule_data['date_created_gmt']  = $object->post_date_gmt;
		$rule_data['date_modified']     = $object->post_modified;
		$rule_data['date_modified_gmt'] = $object->post_modified_gmt;
		$rule_data['post_status']       = $object->post_status; 
		$rule_data['priority']          = $object->menu_order;
		$rule_data['customers_rules']   = get_post_meta( $object->ID, 'rcus_base_price', true);
		$rule_data['user_roles_rules']  = $this->get_roles_data($object->ID);

		return $rule_data;
	}//end get_rule_data()

	/**
	 * Get Object
	 *
	 * @param mixed $term_id Args.
	 * @return \WP_Term|array|false
	 */
	public function get_object( $term_id ) {

		$term = get_term_by( 'id', $term_id, 'product_cat');

		if ( is_a( $term, 'WP_Term') ) {
			return $term;
		}

		return false;
	}//end get_object()

	/**
	 * Category Prices.
	 *
	 * @param mixed $request Args.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_category_prices( $request ) {

		$category = $this->get_object( (int) $request['id'] );

		if ( ! $category || 0 === $category->term_id ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'addify_role_price' ), array( 'status' => 404 ) );
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

		foreach ( $rules_Ids as $rule_id ) {

			$rule_object = get_post( $rule_id );
			
			$all        = get_post_meta( $rule_id, 'csp_apply_on_all_products', true);
			$categories = (array) get_post_meta( $rule_id, 'csp_applied_on_categories', true);

			if ( 'yes' === $all || in_array( $category->term_id, $categories ) ) {
			   
				$data[] = $this->prepare_object_for_response( $rule_object, $request );
			}
		}

		$response = rest_ensure_response( $data );
		return $response;
	}//end get_category_prices()


	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Category role based pricing rules',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the resource.', 'addify_role_price' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'             => array(
					'description' => __( 'Rule title.', 'addify_role_price' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'all_products'      => array(
					'description' => __( 'Rules applied on all products or not.', 'addify_role_price' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'products'          => array(
					'description' => __( 'Applied products ids.', 'addify_role_price' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'categories'        => array(
					'description' => __( 'Applied categories ids.', 'addify_role_price' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'      => array(
					'description' => __( "The date the product was created, in the site's timezone.", 'addify_role_price' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created_gmt'  => array(
					'description' => __( 'The date the product was created, as GMT.', 'addify_role_price' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'date_modified'     => array(
					'description' => __( "The date the product was last modified, in the site's timezone.", 'addify_role_price' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the product was last modified, as GMT.', 'addify_role_price' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'post_status'       => array(
					'description' => __( 'Rule status (post status).', 'addify_role_price' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'priority'          => array(
					'description' => __( 'Rule Priority.', 'addify_role_price' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'customers_rules'   => array(
					'description' => __( 'List of customer rules.', 'addify_role_price' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'array',
						'properties' => array(
							'customer_id'           => array(
								'description' => __( 'User ID.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_type'         => array(
								'description' => __( 'Discount Type.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_value'        => array(
								'description' => __( 'Discount value.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'min_qty'               => array(
								'description' => __( 'Minimum Quantity.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'max_qty'               => array(
								'description' => __( 'Maximum Quantity.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'replace_orignal_price' => array(
								'description' => __( 'Replace Original Price.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
				'user_roles_rules'  => array(
					'description' => __( 'List of customer rules.', 'addify_role_price' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'array',
						'properties' => array(
							'user_role'             => array(
								'description' => __( 'User role.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_type'         => array(
								'description' => __( 'Discount Type.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'discount_value'        => array(
								'description' => __( 'Discount value.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'min_qty'               => array(
								'description' => __( 'Minimum Quantity.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'max_qty'               => array(
								'description' => __( 'Maximum Quantity.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'replace_orignal_price' => array(
								'description' => __( 'Replace Original Price.', 'addify_role_price' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			),
		);
		return $this->add_additional_fields_schema( $schema );
	}//end get_item_schema()
}//end class
