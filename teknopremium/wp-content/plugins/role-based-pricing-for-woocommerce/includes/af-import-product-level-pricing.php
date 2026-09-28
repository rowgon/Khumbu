<?php
/**
 * Import Product Level Rules
 *
 * @package role-based-pricing
 */

global $woocommerce;

$retrieved_nonce = isset($_REQUEST['afrbp_import_nonce_field']) ? sanitize_text_field(wp_unslash($_REQUEST['afrbp_import_nonce_field'])) : '';

if (!wp_verify_nonce($retrieved_nonce, 'afrbp_import_action')) {
	die(esc_html__('Security Violated.', 'addify_role_price'));
}

if (!current_user_can('upload_files')) {
	die(esc_html__('You are not allowed to upload_files.', 'addify_role_price'));
}

$file = isset($_FILES['afrbp_import_csv_file']) ? sanitize_meta('', $_FILES['afrbp_import_csv_file'], '') : '';

if (empty($file['name'])) {
	add_action('admin_notices', function () {
		?>
		<div id="message" class="error afcsp_file_upload_error">
			<p>
				<strong>
					<?php
					/* 
						translators: %s File Type       
					*/  
					?>
					<?php esc_html_e('Upload a CSV file to import.', 'addify_role_price'); ?>
				</strong>
			</p>
		</div>
		<?php
	});

	return false;
}

$file_type = wp_check_filetype(basename($file['name']));
$file_type = isset($file_type['type']) ? $file_type['type'] : '';

if ('text/csv' != $file_type) {

	add_action('admin_notices', function () {
		?>
		<div id="message" class="error afcsp_file_upload_error">
			<p>
				<strong>
					<?php esc_html_e('File type is not allowed.', 'addify_role_price'); ?>
				</strong>
			</p>
		</div>
		<?php
	});

	return false;
}

$realFilePath = realpath($file['tmp_name']);

if (false === $realFilePath) {

	add_action('admin_notices', function () {
		?>
		<div id="message" class="error afcsp_file_upload_error">
			<p>
				<strong>
					<?php esc_html_e('Invalid Path.', 'addify_role_price'); ?>
				</strong>
			</p>
		</div>
		<?php
	});

	return false;
}

if (isset($file['tmp_name']) && is_uploaded_file(sanitize_text_field($file['tmp_name']))) {

	$afb2bFile = sanitize_text_field($file['tmp_name']);

	try {

		$csvFile = fopen($afb2bFile, 'r');

		fgetcsv($csvFile);

		$arrdup  = array();
		$arrdup1 = array();

		while (( $line = fgetcsv($csvFile) ) !== false) {


			// Get row data.
			$ID                    = isset($line[0]) ? $line[0] : '';
			$SKU                   = isset($line[1]) ? $line[1] : '';
			$pro_name              = isset($line[2]) ? $line[2] : ''; // Product Name is just for reference.
			$user_role             = isset($line[3]) ? $line[3] : '';
			$customer_name         = isset($line[4]) ? $line[4] : '';
			$min_qty               = isset($line[5]) ? $line[5] : '';
			$max_qty               = isset($line[6]) ? $line[6] : '';
			$adjustment_type       = isset($line[7]) ? $line[7] : '';
			$price_value           = isset($line[8]) ? $line[8] : '';
			$start_date            = isset($line[8]) ? $line[9] : '';
			$end_date              = isset($line[8]) ? $line[10] : '';
			$replace_orignal_price = isset($line[9]) ? $line[11] : '';

			$customer = get_user_by('email', $customer_name);

			if (!empty($user_role) && !empty($price_value)) {
				$arrdup[ $ID ] = array(
					'discount_type'         => $adjustment_type,
					'discount_value'        => $price_value,
					'min_qty'               => $min_qty,
					'max_qty'               => $max_qty,
					'start_date'            => $start_date,
					'end_date'              => $end_date,
					'replace_orignal_price' => $replace_orignal_price,
				);
			}

			if (!empty($arrdup[ $ID ])) {
				$role_price = sanitize_meta('', $arrdup[ $ID ], '');
				$role_price = maybe_serialize($role_price);
				update_post_meta($ID, '_role_base_price_' . $user_role, $role_price);
			}


			if (!empty($customer_name) && !empty($price_value) && is_a($customer, 'WP_User')) {
				$arrdup1[ $ID ][] = array(
					'ID'                    => $ID,
					'SKU'                   => $SKU,
					'customer_name'         => $customer->ID,
					'discount_type'         => $adjustment_type,
					'discount_value'        => $price_value,
					'min_qty'               => $min_qty,
					'max_qty'               => $max_qty,
					'start_date'            => $start_date,
					'end_date'              => $end_date,
					'replace_orignal_price' => $replace_orignal_price,
				);
			}
		}



		foreach ($arrdup1 as $key1 => $aa1) {

			if (!empty($aa1)) {

				$cus_price = sanitize_meta('', $aa1, '');


				update_post_meta($key1, '_cus_base_price', $cus_price);
			}
		}

		fclose($csvFile);
	} catch (Exception $ex) {

		add_action('admin_notices', function () {
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php echo esc_html($ex->getMessage()); ?>
					</strong>
				</p>
			</div>
			<?php
		});

		return false;
	}

	return true;
}

return false;
