<?php
/**
 * Configure MCP Server — first accordion item of the AI Abilities tab.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumAddons\Admin\Includes\MCP_Settings;
use PremiumAddons\Includes\Abilities\Bootstrap as Abilities_Bootstrap;
use PremiumAddons\Includes\Abilities\Connection_Log;
use PremiumAddons\Includes\Abilities\OAuth;

$pw_status         = MCP_Settings::app_passwords_status();
$profile_url       = admin_url( 'profile.php#application-passwords-section' );
$mcp_endpoint      = rest_url( Abilities_Bootstrap::server_route() );
$mcp_endpoint_host = MCP_Settings::get_endpoint_host( $mcp_endpoint );
$mcp_scheme        = MCP_Settings::endpoint_scheme( $mcp_endpoint );
$mcp_is_local      = MCP_Settings::is_local_host( $mcp_endpoint_host );

// OAuth connect method. The snippets embed no secret, so the OAuth branch is
// rendered on every load and the chooser only switches visibility; enabling is
// an AJAX opt-in that creates the tables on the way.
$oauth_reason    = OAuth\Bootstrap::unavailable_reason();
$oauth_available = '' === $oauth_reason;
$oauth_enabled   = OAuth\Bootstrap::is_registered();

// Raw opt-in state, tracked apart from is_registered(): a gate that starts
// failing after opt-in must not hide the kill switch while tokens are live.
$oauth_on = (bool) get_option( OAuth\Bootstrap::OPTION_ENABLED );

// Keep the form on this tab after submitting (tabs are routed by URL hash).
$form_action = esc_url( admin_url( 'admin.php?page=' . self::$page_slug . '#tab=ai-abilities' ) );

// A client this user already connected turns the setup steps into a reference
// they open on purpose, instead of a wall of instructions on every visit.
// $mcp, $mcp_state, $is_configured, $used_password and $used_error come from
// ai-abilities.php, which includes this file into its own scope.
$state_ago   = $is_configured ? human_time_diff( $mcp_state['time'] ) : '';
$setup_steps = PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-setup-steps.php';
$client_tabs = PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-client-tabs.php';
?>

		<?php if ( $is_configured ) : ?>
			<div class="notice notice-success inline pa-mcp-connected-notice">
				<p>
					<strong>
						<?php
						if ( Connection_Log::STATE_ACTIVE === $mcp_state['state'] ) {
							printf(
								/* translators: 1: number of open sessions, 2: human-readable time difference. */
								esc_html( _n( 'Active now — %1$s session · last activity %2$s ago', 'Active now — %1$s sessions · last activity %2$s ago', $mcp_state['count'], 'premium-addons-for-elementor' ) ),
								esc_html( number_format_i18n( $mcp_state['count'] ) ),
								esc_html( $state_ago )
							);
						} else {
							printf(
								/* translators: %s: human-readable time difference. */
								esc_html__( 'Connected — last connected %s ago.', 'premium-addons-for-elementor' ),
								esc_html( $state_ago )
							);
						}
						?>
					</strong>
				</p>
				<p>
					<?php if ( $oauth_enabled ) : ?>
						<?php // A button, not an anchor: a fragment href would set a hash the tab router cannot parse and bounce the user to the first tab. ?>
						<button type="button" class="button-link pa-mcp-oauth-manage">
							<?php esc_html_e( 'Manage or revoke access', 'premium-addons-for-elementor' ); ?>
						</button>
					<?php else : ?>
						<a href="<?php echo esc_url( $profile_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Manage or revoke access', 'premium-addons-for-elementor' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="pa-mcp-method-chooser">
			<p class="pa-mcp-field-label"><?php esc_html_e( 'Connection method', 'premium-addons-for-elementor' ); ?></p>

			<?php
			// Both methods keep working at once, so the chooser is a view preference,
			// not a reflection of which one the site has enabled. It renders on
			// Application Password and the script restores this admin's last pick.
			?>
			<div class="pa-mcp-method-cards">
				<label class="pa-mcp-method-card is-active">
					<input type="radio" name="pa-mcp-method" value="password" checked>
					<span class="pa-mcp-method-title"><?php esc_html_e( 'Application Password', 'premium-addons-for-elementor' ); ?></span>
					<span class="pa-mcp-method-desc"><?php esc_html_e( 'Create a WordPress application password and paste it into your client configuration.', 'premium-addons-for-elementor' ); ?></span>
				</label>

				<label class="pa-mcp-method-card<?php echo esc_attr( $oauth_available ? '' : ' is-locked' ); ?>">
					<input type="radio" name="pa-mcp-method" value="oauth" <?php disabled( ! $oauth_available ); ?> data-pa-oauth-enabled="<?php echo esc_attr( $oauth_enabled ? '1' : '0' ); ?>">
					<span class="pa-mcp-method-title">
						<?php esc_html_e( 'OAuth', 'premium-addons-for-elementor' ); ?>
						<?php if ( $oauth_available ) : ?>
							<span class="pa-mcp-method-badge"><?php esc_html_e( 'Recommended', 'premium-addons-for-elementor' ); ?></span>
						<?php endif; ?>
					</span>
					<span class="pa-mcp-method-desc"><?php esc_html_e( 'Approve the connection in your browser — no secret to copy, and access tokens expire automatically.', 'premium-addons-for-elementor' ); ?></span>
					<?php if ( ! $oauth_available ) : ?>
						<span class="pa-mcp-method-desc"><strong><?php echo esc_html( $oauth_reason ); ?></strong></span>
					<?php endif; ?>
				</label>
			</div>

			<div class="pa-mcp-method-status" role="status"></div>
		</div>

		<div id="pa-mcp-branch-password" class="pa-mcp-branch">

			<?php if ( $is_configured ) : ?>
				<?php // Stays open after a submission so the form's own result is not hidden. ?>
				<details class="pa-mcp-setup-fold"<?php echo null !== $used_password || null !== $used_error ? ' open' : ''; ?>>
					<summary><?php esc_html_e( 'Connect another client or reconnect', 'premium-addons-for-elementor' ); ?></summary>
					<?php include $setup_steps; ?>
				</details>
			<?php else : ?>
				<?php include $setup_steps; ?>
			<?php endif; ?>

			<?php
			// "Connect Your AI Client" — shown only right after a password is pasted,
			// never on a normal page load, since the connection details embed the secret.
			$connect_password = $used_password;

			if ( null !== $connect_password ) :
				$mcp_username = wp_get_current_user()->user_login;
				$configs      = $mcp->build_client_configs( $mcp_endpoint, $mcp_username, $connect_password );
				$tabs_prefix  = 'pw';
				?>

				<hr class="pa-mcp-divider">

				<div class="pa-mcp-connect">

					<h4 class="pa-mcp-step-heading">
						<span class="pa-mcp-step-badge">2</span>
						<?php esc_html_e( 'Connect Your AI Client', 'premium-addons-for-elementor' ); ?>
					</h4>

					<p class="pa-mcp-step-desc">
						<?php esc_html_e( 'Pick your AI client and copy its configuration or click the prompt below it.', 'premium-addons-for-elementor' ); ?>
					</p>

					<?php include $client_tabs; ?>

					<p class="description pa-mcp-connect-note">
						<?php esc_html_e( 'These connection details contain your application password. Treat them like a password: the config file stores the credential, and anyone with it can act on your site as you. If it is exposed, go to Users → Profile and revoke the application password.', 'premium-addons-for-elementor' ); ?>
					</p>

				</div>
			<?php endif; ?>
		</div>

		<?php if ( $oauth_available ) : ?>
			<div id="pa-mcp-branch-oauth" class="pa-mcp-branch" hidden>

				<div class="pa-mcp-connect">

					<p class="pa-mcp-step-desc">
						<?php esc_html_e( 'Pick your AI client and copy its configuration.', 'premium-addons-for-elementor' ); ?>
					</p>

					<?php
					$configs     = $mcp->build_client_configs( $mcp_endpoint );
					$tabs_prefix = 'oauth';
					include $client_tabs;
					?>

					<p class="description pa-mcp-connect-note">
						<?php esc_html_e( 'These connection details contain no secret. Each client registers itself and gets its own access token when you approve it in the browser.', 'premium-addons-for-elementor' ); ?>
					</p>

				</div>

			</div>
		<?php endif; ?>

		<?php // Outside the branch above: once OAuth has been switched on, the kill switch stays reachable even if a gate later fails. ?>
		<?php if ( $oauth_available || $oauth_on ) : ?>
			<div class="pa-mcp-oauth-disconnect-row"<?php echo $oauth_on ? '' : ' hidden'; ?>>

				<?php if ( $oauth_on && ! $oauth_available ) : ?>
					<div class="notice notice-warning inline">
						<p><strong><?php echo esc_html( $oauth_reason ); ?></strong></p>
						<p><?php esc_html_e( 'OAuth is still switched on and the tokens it issued remain valid. Disconnect below to revoke them.', 'premium-addons-for-elementor' ); ?></p>
					</div>
				<?php endif; ?>

				<button type="button" class="button" id="pa-mcp-oauth-disconnect" data-pa-confirm="<?php esc_attr_e( 'Disconnect every connected AI client? Each one will have to be approved again in the browser before it can reconnect.', 'premium-addons-for-elementor' ); ?>">
					<?php esc_html_e( 'Disconnect all clients', 'premium-addons-for-elementor' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'Revokes every OAuth token this site has issued. All connected clients are disconnected immediately.', 'premium-addons-for-elementor' ); ?>
				</p>
			</div>
		<?php endif; ?>
