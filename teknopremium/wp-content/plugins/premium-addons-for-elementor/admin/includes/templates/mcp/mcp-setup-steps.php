<?php
/**
 * Application password setup steps — included by mcp-config.php.
 *
 * Split out so the steps can be rendered plainly for a user who never connected
 * and folded inside a <details> for one who did, without either tag being opened
 * in one conditional and closed in another.
 *
 * Runs in mcp-config.php's scope and reads $pw_status, $profile_url,
 * $form_action, $used_error and $used_password from it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

		<h4 class="pa-mcp-step-heading">
			<span class="pa-mcp-step-badge">1</span>
			<?php esc_html_e( 'Create an Application Password', 'premium-addons-for-elementor' ); ?>
		</h4>

		<?php if ( ! $pw_status['available'] ) : ?>
			<div class="notice notice-error inline">
				<p><strong><?php echo esc_html( $pw_status['message'] ); ?></strong></p>
			</div>
		<?php else : ?>

			<ol class="pa-mcp-steps">
				<li>
					<?php
					printf(
						/* translators: 1: opening <a> tag linking to the profile page, 2: closing </a> tag. */
						esc_html__( 'Open your %1$sprofile page%2$s.', 'premium-addons-for-elementor' ),
						'<a href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener noreferrer">', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL is escaped with esc_url().
						'</a>'
					);
					?>
				</li>
				<li><?php esc_html_e( 'Enter a name you will recognize (e.g. “Claude on laptop”) in the “New Application Password Name” field.', 'premium-addons-for-elementor' ); ?></li>
				<li><?php esc_html_e( 'Click “Add Application Password”.', 'premium-addons-for-elementor' ); ?></li>
				<li><?php esc_html_e( 'Copy the generated password.', 'premium-addons-for-elementor' ); ?></li>
				<li><?php esc_html_e( 'Paste it below.', 'premium-addons-for-elementor' ); ?></li>
			</ol>

			<form method="post" class="pa-mcp-password-form" action="<?php echo $form_action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above. ?>">
				<?php wp_nonce_field( 'pa_mcp_use_existing_password' ); ?>
				<label class="pa-mcp-field-label" for="pa-mcp-existing-password">
					<?php esc_html_e( 'Paste the password value', 'premium-addons-for-elementor' ); ?>
				</label>
				<input type="text" id="pa-mcp-existing-password" name="pa_mcp_existing_password" class="regular-text" autocomplete="off" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx">
				<button type="submit" name="pa_mcp_use_existing_password" class="button button-primary">
					<?php esc_html_e( 'Use this password', 'premium-addons-for-elementor' ); ?>
				</button>
				<?php if ( null !== $used_error ) : ?>
					<div class="notice notice-error inline">
						<p><?php echo esc_html( $used_error->get_error_message() ); ?></p>
					</div>
				<?php elseif ( null !== $used_password ) : ?>
					<div class="notice notice-success inline">
						<p><?php esc_html_e( 'Connection details generated below.', 'premium-addons-for-elementor' ); ?></p>
					</div>
				<?php endif; ?>
			</form>
		<?php endif; ?>
