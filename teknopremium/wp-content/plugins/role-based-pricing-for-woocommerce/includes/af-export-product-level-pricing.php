<?php
/**
 * Export product level rules
 *
 * @package role-based-pricing
 */

$args = array(
	'post_type'      => 'product',
	'posts_per_page' => -1,
	'fields'         => 'ids',
);
global $wp_roles;

$all_roles = array_merge(array( 'guest' => __('Guest', 'addify_osm') ), $wp_roles->get_names());

$r_prices        = array();
$cus_base_price  = array();
$role_base_price = array();

$products = (array) get_posts($args);
foreach ($products as $ID) {
	if (!empty(get_post_meta((int) $ID, '_cus_base_price', true))) {
		$cus_base_price[ $ID ] = get_post_meta((int) $ID, '_cus_base_price', true);
	}

	foreach ($all_roles as $key => $value) {

		if (!empty(get_post_meta((int) $ID, '_role_base_price_' . $key, true))) {
			$role_base_price[ $ID ][ $key ] = get_post_meta((int) $ID, '_role_base_price_' . $key, true);
		}
	}

	// Get the variations.
	$args = array(
		'post_type'      => 'product_variation',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'post_parent'    => $ID,
		'fields'         => 'ids',
	);
	if (!empty(get_posts($args))) {
		$variations = (array) get_posts($args);
		foreach ($variations as $varID) {
			if (!empty(get_post_meta((int) $varID, '_cus_base_price', true))) {
				$cus_base_price[ $varID ] = get_post_meta((int) $varID, '_cus_base_price', true);
			}
			foreach ($all_roles as $key => $value) {
				if (!empty(get_post_meta((int) $varID, '_role_base_price_' . $key, true))) {
					$role_base_price[ $varID ][ $key ] = get_post_meta((int) $varID, '_role_base_price_' . $key, true);
				}
			}
		}
	}
}
// Insert the missing data.
if (!empty($cus_base_price)) {


	foreach ($cus_base_price as $c_id => $data) {
		foreach ($data as $row_id => $row) {
			if (!isset($row['ID'])) {
				$row['ID']                          = $c_id;
				$cus_base_price[ $c_id ][ $row_id ] = $row;
			}

			if (!isset($row['user_role'])) {

				$row['user_role']                   = '';
				$cus_base_price[ $c_id ][ $row_id ] = $row;
			}
			if (!isset($row['replace_orignal_price'])) {

				$row['replace_orignal_price']       = '';
				$cus_base_price[ $c_id ][ $row_id ] = $row;
			}
		}
	}
}

if (!empty($role_base_price)) {
	foreach ($role_base_price as $subArray => $value) {
		foreach ($value as $r_role => $role_prices) {

			$r_prices[ $subArray ][ $r_role ] = maybe_unserialize($role_prices);
		}
	}
}

// Insert the missing data.
if (!empty($r_prices)) {

	foreach ($r_prices as $r_id => $data) {
		foreach ($data as $row_id => $row) {
			if (!isset($row['ID'])) {
				$row['ID']                    = $r_id;
				$r_prices[ $r_id ][ $row_id ] = $row;
			}
			if (!isset($row['user_role'])) {

				$row['user_role']             = $row_id;
				$r_prices[ $r_id ][ $row_id ] = $row;
			}
			if (!isset($row['customer_name'])) {

				$row['customer_name']         = '';
				$r_prices[ $r_id ][ $row_id ] = $row;
			}

			if (!isset($row['replace_orignal_price'])) {

				$row['replace_orignal_price'] = '';
				$r_prices[ $r_id ][ $row_id ] = $row;
			}
		}
	}
}



$prices = array_merge($cus_base_price, $r_prices);

if (empty($prices)) {
	return false;
}
$exportedData = array();
foreach ($prices as $ID => $subArray) {
	foreach ($subArray as $row) {
		$customer_data = get_userdata($row['customer_name']);
		if ($customer_data) {
			$customer_email = $customer_data->user_email;
		} else {
			$customer_email = '';
		}
		$pro = wc_get_product($row['ID']);
		if ($pro) {
			$p_name = $pro->get_name();
			$p_sku  = $pro->get_sku();
		} else {
			$p_name = '';
			$p_sku  = '';
		}

		// Maps the data to each index.
		$exportedData[] = array(
			'Id'                    => $row['ID'],
			'SKU'                   => $p_sku,
			'proName'               => $p_name,
			'user_role'             => $row['user_role'],
			'customer_name'         => $customer_email,
			'min_qty'               => $row['min_qty'],
			'max_qty'               => $row['max_qty'],
			'discount_type'         => $row['discount_type'],
			'discount_value'        => $row['discount_value'],
			'start_date'            => $row['start_date'],
			'end_date'              => $row['end_date'],
			'replace_orignal_price' => $row['replace_orignal_price'],

		);
	}
}
$header_row = array(
	'Product ID',
	'Product SKU',
	'Product Name',
	'User Role',
	'Customer Name',
	'Minimum Quantity',
	'Maximum Quantity',
	'Discount Type',
	'Discount Value',
	'Start Date',
	'End Date',
	'Replace Original',
);
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private', false);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="product-level-prices.csv";');
header('Content-Transfer-Encoding: binary');

$file = fopen(ADDIFY_CSP_PLUGINDIR . 'assets/export/export.csv', 'w+');



fputcsv($file, $header_row);




foreach ($exportedData as $row) {

	fputcsv($file, (array) $row);
}



echo wp_kses_post(file_get_contents(ADDIFY_CSP_URL . 'assets/export/export.csv'));

fclose($file);
exit;
