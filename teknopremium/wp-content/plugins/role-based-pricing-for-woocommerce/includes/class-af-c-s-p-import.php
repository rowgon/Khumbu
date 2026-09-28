<?php
/**
 * Import Rules
 *
 * @package role-based-pricing
 */

defined('ABSPATH') || exit;

/**
 * AF_C_S_P_Import
 */
class AF_C_S_P_Import {

	/** 
	 * Rules
	 *
	 * @var $all_pricing_rules Rules.
	 */
	protected $all_pricing_rules;

	/** 
	 * Array columns
	 *
	 * @var $array_columns Cols.
	 */
	private $array_columns = array(
		'ID'             => array( 'id', 'rule_id', 'user_id', 'rule id', 'user id' ),
		'title'          => array( 'title' ),
		'all_products'   => array( 'all products', 'all_products' ),
		'products'       => array( 'products', 'products_ids', 'products ids' ),
		'categories'     => array( 'categories', 'categories ids', 'categories_ids' ),
		'customer_rules' => array( 'customer rules', 'customer_rules' ),
	);

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

		$this->all_pricing_rules = $this->load_pricing_rules();
	}//end __construct()

	/**
	 * Loads all pricing rules
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

		return get_posts($args);
	}//end load_pricing_rules()

	/**
	 * Gets meta keys
	 *
	 * @return string[]
	 */
	public function get_meta_keys() {

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = __('Guest', 'addify_role_price');

		$meta_keys = array(
			'ID',
			'csp_apply_on_all_products',
			'csp_applied_on_products',
			'csp_applied_on_categories',
			'rcus_base_price',
		);

		foreach ($roles as $key => $value) {
			$meta_keys[] = 'rrole_base_price_' . $key;
		}

		return $meta_keys;
	}//end get_meta_keys()

	/**
	 * Get Associative data
	 *
	 * @param mixed $data    Args.
	 * @param mixed $colunms Args.
	 * @return array
	 */
	public function get_associative_data( $data, $colunms ) {

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = __('Guest', 'addify_role_price');

		foreach ($roles as $key => $value) {

			$this->array_columns[ $key ] = array_unique(array( strtolower($value), str_replace(' ', '_', $value) ));
		}

		$data    = str_getcsv($data);
		$colunms = is_array($colunms) ? $colunms : str_getcsv($colunms);

		array_walk($colunms, array( $this, 'filter_column_names' ));

		$assoc_arr = array();

		foreach ($this->array_columns as $key => $data_columns) {
			foreach ($data_columns as $title) {

				if (false !== array_search($title, $colunms)) {
					$assoc_arr[ $key ] = $data[ array_search($title, $colunms) ];
					continue 2;
				}
			}
		}

		return $assoc_arr;
	}//end get_associative_data()

	/**
	 * Import Rules
	 *
	 * @param mixed $assoc_arr Args.
	 * @return void
	 */
	public function import_rules( $assoc_arr ) {

		global $wp_roles;
		$roles          = $wp_roles->get_names();
		$roles['guest'] = __('Guest', 'addify_role_price');

		foreach ($assoc_arr as $line_number => $line_data) {

			$rule_id = isset($line_data['ID']) ? intval($line_data['ID']) : 0;
			$title   = isset($line_data['title']) ? $line_data['title'] : __('Rule Imported', 'addify_role_price');
			$post    = get_post($rule_id);

			if (is_a($post, 'WP_Post')) {

				wp_update_post(
					array(
						'ID'         => $rule_id,
						'post_title' => $title,
					)
				);
			} else {

				$rule_id = wp_insert_post(
					array(
						'post_type'   => 'csp_rules',
						'post_title'  => $title,
						'post_status' => 'publish',
					)
				);
			}

			$all_products = isset($line_data['all_products']) ? $line_data['all_products'] : 0;
			$products     = isset($line_data['products']) ? $line_data['products'] : 0;
			$categories   = isset($line_data['categories']) ? $line_data['categories'] : 0;

			update_post_meta($rule_id, 'csp_apply_on_all_products', $all_products);
			update_post_meta($rule_id, 'csp_applied_on_products', explode(',', $products));
			update_post_meta($rule_id, 'csp_applied_on_categories', explode(',', $categories));

			$customer_rules = isset($line_data['customer_rules']) ? $line_data['customer_rules'] : 0;

			if (!empty($customer_rules)) {

				$customer_rules = explode('||', $customer_rules);

				if (!empty($customer_rules)) {

					$rules_data = array();
					foreach ($customer_rules as $key => $value) {

						$current_data = explode(',', $value);

						$data_array                   = array();
						$data_array['customer_name']  = isset($current_data[0]) ? $current_data[0] : 0;
						$data_array['discount_type']  = isset($current_data[1]) ? $current_data[1] : 0;
						$data_array['discount_value'] = isset($current_data[2]) ? $current_data[2] : 0;
						$data_array['min_qty']        = isset($current_data[3]) ? $current_data[3] : 0;
						$data_array['max_qty']        = isset($current_data[4]) ? $current_data[4] : 0;
						$data_array['start_date']     = isset($current_data[5]) ? $current_data[5] : 0;
						$data_array['end_date']       = isset($current_data[6]) ? $current_data[6] : 0;

						$data_array['replace_orignal_price'] = isset($current_data[7]) ? $current_data[7] : '';
					}

					update_post_meta($rule_id, 'rcus_base_price', $rules_data);
				}
			}

			foreach ($roles as $key => $name) {

				if (isset($line_data[ $key ])) {

					$current_data = explode(',', $line_data[ $key ]);

					$data_array['discount_type']  = isset($current_data[0]) ? $current_data[0] : 0;
					$data_array['discount_value'] = isset($current_data[1]) ? $current_data[1] : 0;
					$data_array['min_qty']        = isset($current_data[2]) ? $current_data[2] : 0;
					$data_array['max_qty']        = isset($current_data[3]) ? $current_data[3] : 0;
					$data_array['start_date']     = isset($current_data[4]) ? $current_data[4] : 0;
					$data_array['end_date']       = isset($current_data[5]) ? $current_data[5] : 0;

					$data_array['replace_orignal_price'] = isset($current_data[6]) ? $current_data[6] : '';

					$data = serialize($data_array);
					update_post_meta($rule_id, 'rrole_base_price_' . $key, $data);
				}
			}
		}
	}//end import_rules()

	/**
	 * Filters column names
	 *
	 * @param mixed $column_name Args.
	 * @return string
	 */
	public function filter_column_names( &$column_name ) {
		$column_name = strtolower(trim($column_name));
	}//end filter_column_names()
}//end class
