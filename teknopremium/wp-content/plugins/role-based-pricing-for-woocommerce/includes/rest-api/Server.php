<?php
/**
 * Initialize this version of the REST API.
 *
 * @package WooCommerce\RestApi
 */

namespace Addify\Role_based_Pricing\RestApi;

defined( 'ABSPATH' ) || exit;

/*
 * Class responsible for loading the REST API and all REST API namespaces.
 */

use Automattic\WooCommerce\RestApi\Utilities\SingletonTrait;

/**
 * Class Start
 */
class Server {

	use SingletonTrait;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}//end init()


	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		require ADDIFY_CSP_PLUGINDIR . 'includes/rest-api/controllers/class-af-c-s-p-rest-rules-controller.php';
		require ADDIFY_CSP_PLUGINDIR . 'includes/rest-api/controllers/class-af-c-s-p-rest-product-pricing-controller.php';
		require ADDIFY_CSP_PLUGINDIR . 'includes/rest-api/controllers/class-af-c-s-p-rest-category-pricing-controller.php';

		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}//end register_rest_routes()


	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		return apply_filters(
			'addify_csp_rest_api_get_rest_namespaces',
			array(
				'afcsp' => $this->get_controllers(),
			)
		);
	}//end get_rest_namespaces()


	/**
	 * List of controllers in the namespace.
	 *
	 * @return array
	 */
	protected function get_controllers() {
		return array(
			'role_based_product_pricing'  => 'AF_C_S_P_Rest_Product_Pricing_Controller',
			'role_based_category_pricing' => 'AF_C_S_P_Rest_Category_Pricing_Controller',
			'role_based_rules'            => 'AF_C_S_P_Rest_Rules_Controller',
		);
	}//end get_controllers()
}//end class
