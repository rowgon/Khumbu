<?php
/**
 * Import Rule level Settings
 *
 * @package role-based-pricing
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

$step_1 = __( 'File Upload', 'addify_role_price' );
$step_2 = __( 'Data Import', 'addify_role_price' );
$step_3 = __( 'Complete', 'addify_role_price' );
?>

<div class="addify_imp_exp_container">



	<div class="af_inner_flex">
		<p style="color: red; font-weight: bold; float: left; padding: 15px;"><?php echo esc_html__('Note: This import/export rule feature will be retiring from 31st December 2023. Please use "Import/Export Product Level Pricing" to bulk import and export role based pricing using a CSV file. If this feature is critical for your business, please contact us at support@addify.co for more details.', 'addify_role_price'); ?></p>
		<div class="af_progress_step">
			<div class="warp">
				<div class="line" style="width: 80%;"></div>
				<span id="step-1-flag" class="flag filled" style="left: -3%;"><?php echo esc_html( $step_1 ); ?></span>
				<span id="step-2-flag" class="flag" style="left: 39%;"><?php echo esc_html( $step_2 ); ?></span>
				<span id="step-3-flag" class="flag" style="left: 78%;"><?php echo esc_html( $step_3 ); ?></span>
			</div>
		</div>
		<div class="af_import_window">
			<section class="afcsp-file_upload-section">
				<form class="addify-importer" enctype="multipart/form-data" method="post">
					<header>
						<h2>
							<?php echo esc_html__('Import Pricing Rules from a CSV file', 'addify_role_price'); ?>
						</h2>
						<p>
							<?php echo esc_html__('This tool allows you to import pricing rules data to your store from a CSV. Existing ID(s) will be updated.', 'addify_role_price'); ?>
						</p>
						<p>
							<b><?php echo esc_html__('Note:', 'addify_role_price'); ?></b>
							<?php echo esc_html__('Export your pricing rules to get a sample file for CSV format.', 'addify_role_price'); ?>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url('edit.php?post_type=csp_rules&action=export_csp_rules'), '_nonce' ) ); ?>">
								<?php echo esc_html__('Export pricing rules', 'addify_role_price'); ?>
							</a>
						</p>
					</header>
						<table class="form-table addify-importer-options">
							<tbody>
								<tr>
									<th scope="row">
										<label for="upload">
											<?php echo esc_html__('Choose a CSV file from your computer:', 'addify_role_price'); ?>
										</label>
									</th>
									<td>
										<input type="file" id="afcsp_file_upload_field" name="afcsp_import_file" size="40">
										<br>
										<small>
											<?php echo esc_html__('Maximum size: 40 MB', 'addify_role_price'); ?>
										</small>
									</td>
								</tr>
							</tbody>
						</table>
					<div class="addify-actions">
						<button type="submit" id="afcsp_import_file_upload" class="button button-primary button-next" value="upload_csv_file" name="save_step">
							<?php echo esc_html__('Import', 'addify_role_price'); ?>
						</button>
						<?php wp_nonce_field(); ?>
					</div>
				</form>
			</section>

			<section class="afcsp-progress-section">
				<div class="afcsp-progress-bar" style="display:none;"></div>
			</section>

			<section class="afcsp-completed-section">
				<div class="afcsp-completed-bar" style="display:none;"></div>
			</section>
		</div>
	</div>
</div>
