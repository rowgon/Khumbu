<?php
/**
 * Export Rules
 *
 * @package role-based-pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class starts.
 */
class AF_C_S_P_Export {

	/**
	 * Pricing rules
	 *
	 * @var $all_pricing_rules Rules.
	 */
	public $all_pricing_rules;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->all_pricing_rules = $this->load_pricing_rules();
	}//end __construct()


	/**
	 * Load all pricing rules.
	 *
	 * @return \WP_Post[]|int[]
	 */
	public function load_pricing_rules() {

		$args = array(
			'post_type'   => 'csp_rules',
			'post_status' => 'publish',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'numberposts' => -1,
			'fields'      => 'ids',
		);

		return get_posts( $args );
	}//end load_pricing_rules()

	/**
	 * Get column headings.
	 *
	 * @return array
	 */
	public function get_columns_headings() {

		$heading = array();

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = __('Guest', 'addify_role_price');

		$heading[] = __('ID', 'addify_role_price');
		$heading[] = __('Title', 'addify_role_price');
		$heading[] = __('ALl products', 'addify_role_price');
		$heading[] = __('Products', 'addify_role_price');
		$heading[] = __('Categories', 'addify_role_price');
		$heading[] = __('Rule Status', 'addify_role_price');
		$heading[] = __('Rule Priority', 'addify_role_price');
		$heading[] = __('Customer Rules', 'addify_role_price');

		foreach ( $roles as $key => $value ) {
			$heading[] = $value;
		}

		return $heading;
	}//end get_columns_headings()

	/**
	 * Get rules
	 *
	 * @param mixed $object Args.
	 * @return array
	 */
	public function get_rule_data( $object ) {

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = 'Guest';

		$rule_data = array();

		$rule_data['id']           = $object->ID;
		$rule_data['title']        = $object->post_title;
		$rule_data['all_products'] = get_post_meta( $object->ID, 'csp_apply_on_all_products', true);

		$products              = (array) get_post_meta( $object->ID, 'csp_applied_on_products', true);
		$rule_data['products'] = implode(',', $products);

		$categories              = (array) get_post_meta( $object->ID, 'csp_applied_on_categories', true);
		$rule_data['categories'] = implode(',', $categories);

		$rule_data['post_status'] = $object->post_status;
		$rule_data['priority']    = $object->menu_order;

		$customer_rules = get_post_meta( $object->ID, 'rcus_base_price', true);
		$customer_data  = '';

		if ( !empty( $customer_rules ) ) {
			foreach ( $customer_rules as $customer_rule_data ) {

				if ( !empty( $customer_data ) ) {
					$customer_data = $customer_data . '||' . implode(',', (array) $customer_rule_data);
				} else {
					$customer_data = implode(',', (array) $customer_rule_data);
				}
			}
		}

		$rule_data['customer_rules'] = $customer_data;

		foreach ( $roles as $key => $value ) {

			$rule_role_base_price = get_post_meta( $object->ID, 'rrole_base_price_' . $key, true );
			$rule_data[ $key ]    = implode(',', (array) unserialize( $rule_role_base_price ) );
		}

		return $rule_data;
	}//end get_rule_data()

	/**
	 * Get the rules
	 *
	 * @return array
	 */
	public function get_rules_data() {

		$rules_data = array();

		foreach ( $this->all_pricing_rules as $rule_id ) {

			$object                 = get_post( $rule_id );
			$rules_data[ $rule_id ] = $this->get_rule_data($object);
		}

		return $rules_data;
	}//end get_rules_data()

	/**
	 * Print csv file
	 *
	 * @return never
	 */
	public function print_csv_file() {

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="pricing-rules.csv";' );
		header('Content-Transfer-Encoding: binary');

		$file = fopen( ADDIFY_CSP_PLUGINDIR . 'assets/export/export.csv', 'w+');

		$heading = $this->get_columns_headings();

		fputcsv($file, $heading);  

		$data = $this->get_rules_data();

		foreach ( $data as $rule_id => $data ) {
			fputcsv($file, (array) $data);  
		}

		echo wp_kses_post( file_get_contents( ADDIFY_CSP_URL . 'assets/export/export.csv' ) );

		fclose($file);
		exit;
	}//end print_csv_file()
}//end class
