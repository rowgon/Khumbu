<?php
/**
 * Ajax Class
 *
 * @package role-based-pricing
 */

defined('ABSPATH') || exit;

/**
 * AF_C_S_P_Ajax
 */
class AF_C_S_P_Ajax {

	/**
	 * Import Controller
	 *
	 * @var $import_controller import.
	 */
	private $import_controller;
	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

		$this->import_controller = new AF_C_S_P_Import();
		// CSV file upload function.
		add_action('wp_ajax_afcsp_upload_csv', array( $this, 'upload_csv_file' ));

		// Import upload function.
		add_action('wp_ajax_afcsp_import_members', array( $this, 'import_pricing_rules' ));
	}//end __construct()

	/**
	 * Import PRicing rules.
	 *
	 * @return void
	 */
	public function import_pricing_rules() {

		if (empty($_POST['afcsp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['afcsp_nonce'])), 'afrolebase-ajax-nonce')) {
			die(esc_html__('The Link You Followed Has Expired', 'addify_role_price'));
		}

		if (!current_user_can('import')) {
			die(esc_html__('You are not allowed to import files.', 'addify_role_price'));
		}

		$end_of_file = false;

		$file_path = get_option('afcsp_uploaded_file');

		if (!file_exists($file_path)) {
			ob_start();
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php 
						// Translators: %s File Type. 
						?>
						<?php echo esc_html(sprintf(__('Uploaded file is missing. Try uploading the file again', 'addify_role_price'), $file_type['type'])); ?>
					</strong>
				</p>
			</div>
			<?php

			wp_send_json(
				array(
					'success' => false,
					'message' => ob_get_clean(),
				)
			);
		}

		$file_type = wp_check_filetype(basename($file_path));

		if ('text/csv' != $file_type['type']) {
			ob_start();
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php // Translators: %s File Type. ?>
						<?php echo esc_html(sprintf(__('%s File type is not allowed.', 'addify_role_price'), $file_type['type'])); ?>
					</strong>
				</p>
			</div>
			<?php

			wp_send_json(
				array(
					'success' => false,
					'message' => ob_get_clean(),
				)
			);
		}

		$start = isset($_POST['start']) ? intval($_POST['start']) : '';
		$end   = isset($_POST['end']) ? intval($_POST['end']) : '';

		try {

			$associative_data = array();

			if ($file_path) {

				$file_data       = file($file_path);
				$file_data       = array_filter($file_data);
				$columns_mapping = array();

				if (is_array($file_data) && $end < count($file_data)) {

					$first_line = current($file_data);

					for ($i = $start; $i <= $end; $i++) {

						if (0 == $i) {
							continue;
						}

						if (isset($file_data[ $i ])) {

							$data                   = $file_data[ $i ];
							$associative_data[ $i ] = $this->import_controller->get_associative_data($data, $first_line);
						}
					}
				}

				if ($end + 1 >= count($file_data)) {
					$end_of_file = true;
				}
			}

			$this->import_controller->import_rules($associative_data);

			if ($end_of_file) {

				ob_start();
				?>
				<div id="message" class="notice notice-success afcsp_file_upload_error">
					<p>
						<strong>
							<?php
							// Translators: %s: Number of members.
							printf(esc_html__('%s customer pricing rules has been imported successfully.', 'addify_role_price'), intval($end));
							?>
						</strong>
						<a href="<?php echo esc_url(admin_url('edit.php?post_type=csp_rules')); ?>">
							<?php esc_html_e('View Rules', 'addify_role_price'); ?>
						</a>
					</p>
				</div>
				<?php

				wp_send_json(
					array(
						'success'   => true,
						'completed' => true,
						'lines'     => $end,
						'message'   => ob_get_clean(),
					)
				);
			} else {

				ob_start();
				?>
				<div id="message" class="notice notice-success afcsp_file_upload_error">
					<p>
						<strong>
							<?php
							// Translators: %s: Number of members.
							printf(esc_html__('%s members has been imported successfully.', 'addify_role_price'), intval($end) + 1);
							?>
						</strong>
					</p>
				</div>
				<?php

				wp_send_json(
					array(
						'success'   => true,
						'completed' => false,
						'lines'     => $end,
						'message'   => ob_get_clean(),
					)
				);
			}
		} catch (Exception $ex) {

			ob_start();
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php echo esc_html($ex->getMessage()); ?>
					</strong>
				</p>
			</div>
			<?php

			wp_send_json(
				array(
					'success'   => false,
					'completed' => false,
					'message'   => ob_get_clean(),
				)
			);
		}
	}//end import_pricing_rules()


	/**
	 * Import members
	 *
	 * @return void
	 */
	public function upload_csv_file() {

		if (empty($_POST['afcsp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['afcsp_nonce'])), 'afrolebase-ajax-nonce')) {
			die(esc_html__('The Link You Followed Has Expired', 'addify_role_price'));
		}

		if (!current_user_can('upload_files')) {
			die(esc_html__('You are not allowed to upload_files.', 'addify_role_price'));
		}

		$file = isset($_FILES['afcsp_import_file']) ? sanitize_meta('', $_FILES['afcsp_import_file'], '') : '';

		if (empty($file) || empty($file['name'])) {

			ob_start();
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php esc_html_e('kindly select a file to upload.', 'addify_role_price'); ?>
					</strong>
				</p>
			</div>
			<?php

			wp_send_json(
				array(
					'success' => false,
					'message' => ob_get_clean(),
				)
			);
		}

		$file_type = wp_check_filetype(basename($file['name']));

		if ('text/csv' != $file_type['type']) {
			ob_start();
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php // Translators: %s File Type. ?>
						<?php echo esc_html(sprintf(__('%s File type is not allowed.', 'addify_role_price'), $file_type['type'])); ?>
					</strong>
				</p>
			</div>
			<?php

			wp_send_json(
				array(
					'success' => false,
					'message' => ob_get_clean(),
				)
			);
		}

		$media_path     = afcsp_get_media_path();
		$file_upload_to = $media_path . $file['name'];

		$realFilePath = realpath($file_upload_to);
		$realBasePath = realpath($media_path);

		if (false === $realFilePath || strpos($realFilePath, $realBasePath) !== 0) {
			wp_send_json(
				array(
					'success' => false,
					'message' => 'Invalid Path',
				)
			);
		}

		if (file_exists($file_upload_to)) {
			unlink($file_upload_to);
		}

		move_uploaded_file($file['tmp_name'], $file_upload_to);

		if (file_exists($file_upload_to)) {

			update_option('afcsp_uploaded_file', $file_upload_to);
			$lines = file($file_upload_to);
			if (is_array($lines)) {
				wp_send_json(
					array(
						'success'     => true,
						'file_path'   => $file_upload_to,
						'total_lines' => count($lines),
					)
				);
			} else {
				ob_start();
				?>
				<div id="message" class="error afcsp_file_upload_error">
					<p>
						<strong>
							<?php esc_html_e('System has failed to read the file.', 'addify_role_price'); ?>
						</strong>
					</p>
				</div>
				<?php

				wp_send_json(
					array(
						'success' => false,
						'message' => ob_get_clean(),
					)
				);
			}
		} else {

			ob_start();
			?>
			<div id="message" class="error afcsp_file_upload_error">
				<p>
					<strong>
						<?php esc_html_e('System has failed to upload the file.', 'addify_role_price'); ?>
					</strong>
				</p>
			</div>
			<?php

			wp_send_json(
				array(
					'success' => false,
					'message' => ob_get_clean(),
				)
			);
		}
	}//end upload_csv_file()
}//end class


new AF_C_S_P_Ajax();
