<?php
/**
 * AI-client tab navigation and config panels.
 *
 * Included from mcp-config.php into its scope, once per connection method.
 * Expects from that scope:
 * - $configs      Client configuration map from MCP_Settings::build_client_configs().
 * - $tabs_prefix  Branch id prefix (e.g. 'pw', 'oauth') — both branches live in
 *                 the DOM at once, so every element id must be branch-unique.
 * - $mcp_is_local / $mcp_scheme for the bridge TLS note.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PremiumAddons\Admin\Includes\MCP_Settings;

// Read by every mcp-copy-block.php include below, directly and through
// mcp-oauth-setup.php, which this file includes into its own scope.
$copy_alias = MCP_Settings::default_server_name();
?>

				<div class="pa-mcp-clients-nav">
					<?php
					$is_first = true;

					foreach ( $configs as $client_key => $config ) :
						?>
						<button type="button" class="pa-mcp-client-tab<?php echo esc_attr( $is_first ? ' is-active' : '' ); ?>" data-pa-mcp-panel="pa-mcp-<?php echo esc_attr( $tabs_prefix ); ?>-panel-<?php echo esc_attr( $client_key ); ?>">
							<?php echo esc_html( (string) $config['label'] ); ?>
						</button>
						<?php
						$is_first = false;
					endforeach;
					?>
				</div>

				<div class="pa-mcp-client-panels">
					<?php
					$is_first = true;

					foreach ( $configs as $client_key => $config ) :
						$panel_prefix = 'pa-mcp-' . $tabs_prefix;
						?>
						<div class="pa-mcp-client-panel" id="<?php echo esc_attr( $panel_prefix . '-panel-' . $client_key ); ?>"<?php echo esc_attr( $is_first ? '' : ' hidden' ); ?>>
							<?php if ( ! empty( $config['oauth'] ) ) : ?>
								<?php include PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-oauth-setup.php'; ?>
							<?php elseif ( ! empty( $config['code'] ) ) : ?>
								<?php if ( ! empty( $config['steps'] ) ) : ?>
									<?php
									$steps_blocks    = $config['steps'];
									$steps_id_prefix = $panel_prefix . '-steps-' . $client_key;
									include PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-steps.php';
									?>
								<?php else : ?>
									<p class="pa-mcp-hint"><?php echo esc_html( (string) $config['hint'] ); ?></p>

									<?php
									$copy_id      = $panel_prefix . '-code-' . $client_key;
									$copy_text    = $config['code'];
									$copy_label   = __( 'Copy configuration', 'premium-addons-for-elementor' );
									$copy_primary = true;
									include PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-copy-block.php';
									?>
								<?php endif; ?>

								<?php if ( null !== $config['deeplink'] ) : ?>
									<p><a class="button pa-mcp-deeplink" href="<?php echo esc_url( (string) $config['deeplink'], array( 'cursor' ) ); ?>"><?php esc_html_e( 'Install in Cursor', 'premium-addons-for-elementor' ); ?></a></p>
									<p class="description pa-mcp-hint">
										<?php
										printf(
											/* translators: %s: connection name written into the client configuration. */
											esc_html__( 'This installs as %s; rename it later in mcp.json if needed.', 'premium-addons-for-elementor' ),
											esc_html( $copy_alias )
										);
										?>
									</p>
								<?php endif; ?>

								<?php if ( 'bridge' === $config['shape'] && $mcp_is_local && 'https' === $mcp_scheme ) : ?>
									<div class="pa-mcp-advisory pa-mcp-tls-advisory">
										<pre class="pa-mcp-tls-note" id="<?php echo esc_attr( $panel_prefix . '-tls-note-' . $client_key ); ?>"><?php esc_html_e( 'If your client rejects the certificate, trust your local CA (LocalWP/mkcert). As a last resort, you can set NODE_TLS_REJECT_UNAUTHORIZED=0 for the bridge; understand that this disables certificate checks.', 'premium-addons-for-elementor' ); ?></pre>
										<button type="button" class="button pa-mcp-copy" data-pa-mcp-copy="<?php echo esc_attr( $panel_prefix . '-tls-note-' . $client_key ); ?>" data-pa-mcp-copied="<?php esc_attr_e( 'Copied!', 'premium-addons-for-elementor' ); ?>"><?php esc_html_e( 'Copy TLS note', 'premium-addons-for-elementor' ); ?></button>
									</div>
								<?php endif; ?>

								<details class="pa-mcp-agent-prompt">
									<summary><?php esc_html_e( 'Ask your agent to configure it', 'premium-addons-for-elementor' ); ?></summary>
									<?php
									$copy_id      = $panel_prefix . '-prompt-' . $client_key;
									$copy_text    = $config['prompt'];
									$copy_label   = __( 'Copy agent prompt', 'premium-addons-for-elementor' );
									$copy_primary = false;
									include PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-copy-block.php';
									?>
								</details>
							<?php else : ?>
								<?php
								$copy_id      = $panel_prefix . '-prompt-' . $client_key;
								$copy_text    = $config['prompt'];
								$copy_label   = __( 'Copy agent prompt', 'premium-addons-for-elementor' );
								$copy_primary = true;
								include PREMIUM_ADDONS_PATH . 'admin/includes/templates/mcp/mcp-copy-block.php';
								?>
							<?php endif; ?>
						</div>
						<?php
						$is_first = false;
					endforeach;
					?>
				</div>
