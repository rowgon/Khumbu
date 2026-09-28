<?php
/**
 * MCP Settings.
 */

namespace PremiumAddons\Admin\Includes;

// Block direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Admin controller backing the Configure MCP Server step. The dashboard tabs
 * themselves are registered in Admin_Helper::set_admin_tabs().
 *
 * @since 4.11.74
 */
class MCP_Settings {

	/**
	 * Connection name used when the site URL yields nothing usable.
	 *
	 * @var string
	 */
	const DEFAULT_SERVER_NAME = 'premium-addons';

	/**
	 * Longest connection name derived from the site URL.
	 *
	 * @var int
	 */
	const MAX_SERVER_NAME_LENGTH = 60;

	/**
	 * Placeholder replaced by the derived connection name in the admin UI.
	 *
	 * @var string
	 */
	const NAME_TOKEN = '%%PA_NAME%%';

	/**
	 * Pinned mcp-remote version used by bridge clients.
	 *
	 * @var string
	 */
	const MCP_REMOTE_VERSION = '0.1.38';

	/**
	 * @var MCP_Settings|null
	 */
	private static $instance = null;

	/**
	 * Whether WordPress Application Passwords can be generated on this site.
	 *
	 * @return array {
	 *     @type bool   $available Whether a password can be generated now.
	 *     @type string $reason    One of available|unsupported|filtered.
	 *     @type string $message   User-facing explanation when unavailable.
	 * }
	 */
	public static function app_passwords_status() {

		if ( wp_is_application_passwords_available() ) {
			return array(
				'available' => true,
				'reason'    => 'available',
				'message'   => '',
			);
		}

		if ( ! wp_is_application_passwords_supported() ) {
			return array(
				'available' => false,
				'reason'    => 'unsupported',
				'message'   => __( 'Application Passwords require HTTPS, or WP_ENVIRONMENT_TYPE set to "local" on local sites.', 'premium-addons-for-elementor' ),
			);
		}

		return array(
			'available' => false,
			'reason'    => 'filtered',
			'message'   => __( 'Application Passwords are disabled on this site, likely by a security plugin. Re-enable them to connect an AI client.', 'premium-addons-for-elementor' ),
		);
	}

	/**
	 * Process the use-existing password submission.
	 *
	 * Called once at the top of the MCP config template. The pasted value
	 * is only echoed back into the connection details, never stored on the site.
	 *
	 * @return array {
	 *     @type string|null    $existing_password Plaintext value pasted by the user.
	 *     @type \WP_Error|null  $existing_error    Validation error for the pasted value.
	 * }
	 */
	public function maybe_handle_password_forms() {

		$result = array(
			'existing_password' => null,
			'existing_error'    => null,
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			return $result;
		}

		if ( isset( $_POST['pa_mcp_use_existing_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in validate_existing_password().

			$is_existing = $this->validate_existing_password();

			if ( is_wp_error( $is_existing ) ) {
				$result['existing_error'] = $is_existing;
			} else {
				$result['existing_password'] = $is_existing;
			}
		}

		return $result;
	}

	/**
	 * Validate an application password pasted by the user.
	 *
	 * @return string|\WP_Error Trimmed value on success, WP_Error otherwise.
	 */
	private function validate_existing_password() {

		check_admin_referer( 'pa_mcp_use_existing_password' );

		$value = isset( $_POST['pa_mcp_existing_password'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['pa_mcp_existing_password'] ) ) ) : '';

		if ( '' === $value ) {
			return new \WP_Error( 'empty', __( 'Paste the application password value before submitting.', 'premium-addons-for-elementor' ) );
		}

		if ( strlen( $value ) < 16 ) {
			return new \WP_Error( 'too_short', __( 'That does not look like an application password. WordPress application passwords are at least 16 characters long.', 'premium-addons-for-elementor' ) );
		}

		return $value;
	}

	/**
	 * Supported AI clients shown in the "Connect Your AI Client" section.
	 *
	 * Every client connects the same way — a streamable-HTTP MCP server
	 * authenticated with a WordPress application password or OAuth. The array
	 * order is the tab display order: the clients with a guided setup (steps or
	 * a copy-paste snippet) come first, then the prompt-only clients. Keep it in
	 * sync with the Compatible AI clients list in the product vision.
	 *
	 * @since 4.11.74
	 *
	 * @return array<string,string> Map of client key => display label.
	 */
	public static function get_supported_clients() {

		return array(
			'claude-code'    => 'Claude Code',
			'claude-desktop' => 'Claude Desktop',
			'claude-ai'      => 'Claude (claude.ai)',
			'chatgpt'        => 'ChatGPT',
			'codex-chatgpt'  => 'Codex in ChatGPT Desktop',
			'codex'          => 'Codex CLI',
			'cursor'         => 'Cursor',
			'vs-code'        => 'VS Code',
			'antigravity'    => 'Antigravity',
			'github-copilot' => 'GitHub Copilot',
			'windsurf'       => 'Windsurf',
			'cline'          => 'Cline',
			'gemini-cli'     => 'Gemini CLI',
			'kilo-code'      => 'Kilo Code',
			'opencode'       => 'OpenCode',
		);
	}

	/**
	 * Whether the OAuth connect method may be offered on this site: HTTPS, or a
	 * local host in an environment explicitly marked local. Derived from
	 * home_url() so it stays safe to call before $wp_rewrite exists.
	 *
	 * @return bool
	 */
	public static function oauth_transport_allowed() {

		$url = home_url();

		return 'https' === self::endpoint_scheme( $url )
			|| ( 'local' === wp_get_environment_type() && self::is_local_host( self::get_endpoint_host( $url ) ) );
	}

	/**
	 * Build connection details for every supported AI client. An empty password
	 * builds the OAuth variant, where every snippet omits the Authorization
	 * header and the client runs the OAuth flow itself.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $username     WordPress username. Unused when $password is ''.
	 * @param string $password     WordPress application password. Omit for OAuth.
	 * @param string $alias        Local client alias. Empty derives it from the site URL.
	 * @return array<string,array<string,mixed>> Client configuration map.
	 */
	public function build_client_configs( $endpoint_url, $username = '', $password = '', $alias = '' ) {

		$clients            = self::get_supported_clients();
		$shape_map          = self::client_shape_map();
		$auth_header        = '' === $password ? '' : 'Basic ' . self::basic_auth_token( $username, $password );
		$is_oauth           = '' === $auth_header;
		$oauth_map          = $is_oauth ? self::client_oauth_map( $endpoint_url ) : array();
		$clean_alias        = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $alias );
		$deeplink_alias     = '' !== $clean_alias ? $clean_alias : self::default_server_name();
		$connection_context = array(
			'endpoint_url'   => $endpoint_url,
			'auth_header'    => $auth_header,
			'deeplink_alias' => $deeplink_alias,
		);
		$configs            = array();

		foreach ( $clients as $client_key => $client_label ) {

			if ( ! $is_oauth && in_array( $client_key, self::oauth_only_clients(), true ) ) {
				continue;
			}

			// OAuth mode is not the password entry minus a header: its instructions
			// come from their own per-client descriptor, so none of the password
			// snippet shapes are built for it.
			if ( $is_oauth ) {
				$configs[ $client_key ] = array(
					'label' => $client_label,
					'oauth' => $this->build_oauth_setup( $client_key, $oauth_map, $connection_context ),
				);

				continue;
			}

			$client_shape = isset( $shape_map[ $client_key ] ) ? $shape_map[ $client_key ] : null;

			$configs[ $client_key ] = $this->build_client_config( $client_key, $client_label, $client_shape, $connection_context );
		}

		return $configs;
	}

	/**
	 * Resolve one client's OAuth setup block, filling in its deeplink. Clients
	 * with no entry get the generic "paste the URL, then sign in" shape.
	 *
	 * @param string               $client_key         Client key.
	 * @param array                $oauth_map          OAuth setup map.
	 * @param array<string,string> $connection_context Endpoint and alias context.
	 * @return array<string,mixed> OAuth setup block.
	 */
	private function build_oauth_setup( $client_key, $oauth_map, $connection_context ) {

		$setup = isset( $oauth_map[ $client_key ] ) ? $oauth_map[ $client_key ] : array( 'type' => 'url' );

		$setup['deeplink'] = isset( $setup['deeplink'] )
			? self::build_oauth_deeplink( $setup['deeplink'], $connection_context['deeplink_alias'], $connection_context['endpoint_url'] )
			: null;

		return $setup;
	}

	/**
	 * @param string                    $client_key   Client key.
	 * @param string                    $client_label Client display label.
	 * @param array<string,string>|null $client_shape First-class client shape, or null.
	 * @param array<string,string>      $connection_context Endpoint and authentication context.
	 * @return array<string,mixed> Client configuration entry.
	 */
	private function build_client_config( $client_key, $client_label, $client_shape, $connection_context ) {

		$code         = null;
		$windows_code = '';
		$deeplink     = null;

		if ( null !== $client_shape ) {
			$code = $this->build_client_snippet( $client_shape, $connection_context['endpoint_url'], $connection_context['auth_header'] );

			if ( 'bridge' === $client_shape['shape'] ) {
				$windows_code = $this->build_bridge_windows_snippet( $connection_context['endpoint_url'], $connection_context['auth_header'], self::MCP_REMOTE_VERSION );
			}

			if ( 'cursor' === $client_key ) {
				$deeplink = $this->build_cursor_deeplink( $connection_context['deeplink_alias'], $connection_context['endpoint_url'], $connection_context['auth_header'] );
			}
		}

		return array(
			'label'    => $client_label,
			'shape'    => null !== $client_shape ? $client_shape['shape'] : null,
			'lang'     => null !== $client_shape ? $client_shape['lang'] : null,
			'hint'     => null !== $client_shape && isset( $client_shape['hint'] ) ? $client_shape['hint'] : null,
			'code'     => $code,
			'steps'    => null !== $code ? self::client_setup_steps( $client_key, $code, $windows_code ) : array(),
			'deeplink' => $deeplink,
			'prompt'   => $this->build_agent_prompt( $client_label, $connection_context['endpoint_url'], $connection_context['auth_header'] ),
		);
	}

	/**
	 * Numbered Application Password walkthrough for a first-class client: how to
	 * reach its configuration through the client's own UI, the snippet to paste,
	 * and a final check. Clients without an entry keep the hint + snippet
	 * rendering.
	 *
	 * Verified against each vendor's setup documentation — when a client
	 * changes its menus, update the matching case here.
	 *
	 * @since 4.11.99
	 *
	 * @param string $client_key   Client key.
	 * @param string $code         Built configuration snippet.
	 * @param string $windows_code Windows bridge variant, '' for non-bridge clients.
	 * @return array<int,array<string,mixed>> Step blocks: title, desc, copy — or copies, a list of {badge, desc, copy} variants.
	 */
	private static function client_setup_steps( $client_key, $code, $windows_code = '' ) {

		$codex_config_location = __( 'Your home folder is /Users/<you> on Mac and C:\Users\<you> on Windows.', 'premium-addons-for-elementor' );

		switch ( $client_key ) {
			case 'claude-code':
				return array(
					array(
						'title' => __( 'Paste this command into your terminal and run it', 'premium-addons-for-elementor' ),
						'desc'  => __( 'It works from any folder and connects every project.', 'premium-addons-for-elementor' ),
						'copy'  => $code,
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Open Claude Code and type /mcp — the connection should be listed with its tools.', 'premium-addons-for-elementor' ),
					),
				);

			case 'claude-desktop':
				return array(
					array(
						'title' => __( 'In Claude Desktop, open the Claude menu → Settings → Developer → Edit Config', 'premium-addons-for-elementor' ),
						'desc'  => __( 'This creates claude_desktop_config.json if needed and opens its folder — no need to search for it. The connection runs through a small helper started with npx, so Node.js (nodejs.org) must be installed.', 'premium-addons-for-elementor' ),
					),
					array(
						'title'  => __( 'Open claude_desktop_config.json in any text editor and paste the config for your system', 'premium-addons-for-elementor' ),
						'desc'   => __( 'If the file already lists other servers, add just this server entry inside the existing "mcpServers" section instead of replacing the file.', 'premium-addons-for-elementor' ),
						'copies' => array(
							array(
								'badge' => __( 'macOS / Linux', 'premium-addons-for-elementor' ),
								'copy'  => $code,
							),
							array(
								'badge' => __( 'Windows', 'premium-addons-for-elementor' ),
								'desc'  => __( 'Windows starts the helper through cmd and keeps the password in the "env" entry — the macOS entry does not launch on Windows.', 'premium-addons-for-elementor' ),
								'copy'  => $windows_code,
							),
						),
					),
					array(
						'title' => __( 'Quit Claude Desktop completely, then reopen it', 'premium-addons-for-elementor' ),
						'desc'  => __( 'The file is read only at launch. On Windows, quit from the system-tray icon — closing the window is not enough.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Click the + button at the bottom of the chat box, then open Connectors → Manage connectors — the connection and its tools should be listed.', 'premium-addons-for-elementor' ),
					),
				);

			case 'codex-chatgpt':
				return array(
					array(
						'title' => __( 'Open (or create) config.toml inside the .codex folder in your home folder', 'premium-addons-for-elementor' ),
						'desc'  => $codex_config_location . ' ' . __( 'The ChatGPT Desktop app, Codex CLI and the IDE extension all read this same file; the app\'s MCP settings screen cannot add the password header, which is why this is a file edit.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Add this to the end of the file and save', 'premium-addons-for-elementor' ),
						'copy'  => $code,
					),
					array(
						'title' => __( 'Restart the ChatGPT Desktop app', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Settings → MCP servers should then list the connection.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Type /mcp in the composer — the connection should be listed.', 'premium-addons-for-elementor' ),
					),
				);

			case 'codex':
				return array(
					array(
						'title' => __( 'Open (or create) config.toml inside the .codex folder in your home folder', 'premium-addons-for-elementor' ),
						'desc'  => $codex_config_location . ' ' . __( 'The folder exists after running Codex once and may be hidden by the file manager.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Add this to the end of the file and save', 'premium-addons-for-elementor' ),
						'copy'  => $code,
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Run this in your terminal — the connection should be listed.', 'premium-addons-for-elementor' ),
						'copy'  => 'codex mcp list',
					),
				);
		}

		return array();
	}

	/**
	 * Clients that can only be connected with OAuth, so they are hidden from the
	 * Application Password branch rather than shown with instructions that
	 * cannot work. Both are cloud clients whose connector UI takes no custom
	 * request header (ChatGPT's New Plugin dialog offers OAuth, No Auth and
	 * Mixed only — verified against the live UI).
	 *
	 * @return array<int,string> Client keys.
	 */
	private static function oauth_only_clients() {
		return array( 'claude-ai', 'chatgpt' );
	}

	/**
	 * How each client is set up in OAuth mode. This is not the password shape
	 * with the header removed: several clients are configured somewhere else
	 * entirely once there is no credential to paste.
	 *
	 * Types: cmd (one terminal command), connector (the client's own Connectors
	 * UI), config (a config-file snippet), steps (an ordered walkthrough).
	 * Anything without an entry falls back to "paste the URL, then sign in".
	 * An optional docs URL links the client's full connection guide above the
	 * steps.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return array<string,array<string,mixed>> OAuth setup map keyed by client.
	 */
	private static function client_oauth_map( $endpoint_url ) {

		$name = self::NAME_TOKEN;

		return array(
			'claude-code'    => array(
				'type' => 'cmd',
				'cmd'  => sprintf( 'claude mcp add --scope user --transport http %1$s "%2$s"', $name, $endpoint_url ),
			),
			'claude-desktop' => array(
				'type' => 'connector',
				'app'  => __( 'Claude Desktop', 'premium-addons-for-elementor' ),
			),
			'claude-ai'      => array(
				'type'     => 'connector',
				'app'      => 'claude.ai',
				'deeplink' => 'claude-ai',
				'note'     => __( 'Works on every Claude plan (free plans can add one custom connector). On Team and Enterprise an administrator may have to allow custom connectors first. Claude connects from Anthropic\'s cloud, so your site must be reachable from the internet.', 'premium-addons-for-elementor' ),
				'docs'     => 'https://premiumaddons.com/docs/connect-claude-to-build-wordpress-elementor-pages/',
			),
			'chatgpt'        => array(
				'type'  => 'steps',
				'note'  => __( 'ChatGPT connects from OpenAI\'s cloud, so your site must be reachable from the internet. Use ChatGPT on the web; on Business and Enterprise workspaces an administrator may have to allow Developer mode first.', 'premium-addons-for-elementor' ),
				'docs'  => 'https://premiumaddons.com/docs/connect-chatgpt-to-wordpress-elementor-website/',
				'steps' => array(
					array(
						'title' => __( 'Turn on Developer mode', 'premium-addons-for-elementor' ),
						'desc'  => __( 'In ChatGPT, open your profile menu → Settings → Security and login → Developer mode and switch it on. ChatGPT marks it "elevated risk" because it allows unverified connectors — this connection is your own site.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Open Plugins in the left sidebar and click the + button', 'premium-addons-for-elementor' ),
						'desc'  => __( 'The + next to the "Search plugins" field opens the New Plugin dialog.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Name the plugin', 'premium-addons-for-elementor' ),
						'copy'  => $name,
					),
					array(
						'title' => __( 'Paste this URL under Connection, then select Create', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Keep Connection on "Server URL" and Authentication on "OAuth", then tick "I understand and want to continue". Your browser opens so you can approve the connection.', 'premium-addons-for-elementor' ),
						'copy'  => $endpoint_url,
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'desc'  => __( 'The plugin appears in the Plugins page\'s Installed row. Mention it in a chat to use its tools.', 'premium-addons-for-elementor' ),
					),
				),
			),
			'codex-chatgpt'  => array(
				'type'  => 'steps',
				'steps' => array(
					array(
						'title' => __( 'In the ChatGPT Desktop app, open Settings → MCP servers → Add server', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Choose Streamable HTTP as the transport.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Use this name', 'premium-addons-for-elementor' ),
						'copy'  => $name,
					),
					array(
						'title' => __( 'Paste this URL, save, then select Restart', 'premium-addons-for-elementor' ),
						'copy'  => $endpoint_url,
					),
					array(
						'title' => __( 'Select Authenticate next to the connection', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Your browser opens so you can approve it.', 'premium-addons-for-elementor' ),
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Type /mcp in the composer — the connection should be listed.', 'premium-addons-for-elementor' ),
					),
				),
			),
			'cursor'         => array(
				'type'     => 'config',
				'paths'    => array( '~/.cursor/mcp.json', '.cursor/mcp.json' ),
				'template' => sprintf( "{\n    \"mcpServers\": {\n        \"%1\$s\": {\n            \"url\": \"%2\$s\"\n        }\n    }\n}", $name, $endpoint_url ),
				'deeplink' => 'cursor',
			),
			'vs-code'        => array(
				'type'     => 'config',
				'paths'    => array( __( 'the file opened by Command Palette → "MCP: Open User Configuration"', 'premium-addons-for-elementor' ) ),
				'template' => sprintf( "{\n    \"servers\": {\n        \"%1\$s\": {\n            \"type\": \"http\",\n            \"url\": \"%2\$s\"\n        }\n    }\n}", $name, $endpoint_url ),
			),
			// Codex never starts the OAuth flow on its own, so the login command
			// is part of the setup, not a note.
			'codex'          => array(
				'type'  => 'steps',
				'steps' => array(
					array(
						'title' => __( 'Add the connection from your terminal', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Needs a recent Codex version — on an older version, add the URL to ~/.codex/config.toml instead.', 'premium-addons-for-elementor' ),
						'copy'  => sprintf( 'codex mcp add %1$s --url "%2$s"', $name, $endpoint_url ),
					),
					array(
						'title' => __( 'Authorize it from your terminal', 'premium-addons-for-elementor' ),
						'desc'  => __( 'Codex does not open the sign-in on its own, so this step is required.', 'premium-addons-for-elementor' ),
						'copy'  => sprintf( 'codex mcp login %s', $name ),
					),
					array(
						'title' => __( 'Check it worked', 'premium-addons-for-elementor' ),
						'copy'  => 'codex mcp list',
					),
				),
			),
		);
	}

	/**
	 * Build the one-click install URL for a client that accepts one.
	 *
	 * @param string $kind         Deeplink kind: cursor or claude-ai.
	 * @param string $alias        Local client alias.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return string|null Deeplink, or null when the client has none.
	 */
	private static function build_oauth_deeplink( $kind, $alias, $endpoint_url ) {

		if ( 'cursor' === $kind ) {
			$json = (string) wp_json_encode( array( 'url' => $endpoint_url ), JSON_UNESCAPED_SLASHES );

			return 'cursor://anysphere.cursor-deeplink/mcp/install?name=' . rawurlencode( $alias ) . '&config=' . rawurlencode( base64_encode( $json ) );
		}

		if ( 'claude-ai' === $kind ) {
			return 'https://claude.ai/customize/connectors?modal=add-custom-connector&connectorName=' . rawurlencode( $alias ) . '&connectorUrl=' . rawurlencode( $endpoint_url );
		}

		return null;
	}

	/**
	 * Get the pinned configuration shape for first-class clients.
	 *
	 * A client with an entry in client_setup_steps() renders a numbered
	 * walkthrough instead of a hint line, so it carries no hint here.
	 *
	 * @return array<string,array<string,string>> First-class client shape map.
	 */
	private static function client_shape_map() {

		return array(
			'claude-code'    => array(
				'shape'   => 'shell',
				'variant' => 'claude-mcp-add',
				'lang'    => 'shell',
			),
			'claude-desktop' => array(
				'shape'   => 'bridge',
				'variant' => 'mcp-remote',
				'lang'    => 'json',
			),
			'codex-chatgpt'  => array(
				'shape'   => 'toml',
				'variant' => 'mcp_servers',
				'lang'    => 'toml',
			),
			'codex'          => array(
				'shape'   => 'toml',
				'variant' => 'mcp_servers',
				'lang'    => 'toml',
			),
			'cursor'         => array(
				'shape'   => 'native',
				'variant' => 'mcpServers',
				'lang'    => 'json',
				'hint'    => __( 'Configuration file: ~/.cursor/mcp.json (or project .cursor/mcp.json)', 'premium-addons-for-elementor' ),
			),
			'vs-code'        => array(
				'shape'   => 'native',
				'variant' => 'servers',
				'lang'    => 'json',
				'hint'    => __( 'Command Palette → "MCP: Open User Configuration" (available in all workspaces).', 'premium-addons-for-elementor' ),
			),
		);
	}

	/**
	 * Build an HTTP Basic authentication token. Spaces are stripped because
	 * WordPress displays application passwords space-separated but does not
	 * accept them that way.
	 *
	 * @param string $username WordPress username.
	 * @param string $password WordPress application password.
	 * @return string Base64-encoded HTTP Basic token.
	 */
	private static function basic_auth_token( $username, $password ) {

		$password = str_replace( ' ', '', $password );

		return base64_encode( $username . ':' . $password );
	}

	/**
	 * Dispatch a first-class client configuration to its format builder.
	 *
	 * @param array<string,string> $client_shape Client shape definition.
	 * @param string               $endpoint_url MCP endpoint URL.
	 * @param string               $auth_header  Authorization header value, or '' for OAuth.
	 * @return string Client configuration snippet.
	 */
	private function build_client_snippet( $client_shape, $endpoint_url, $auth_header ) {

		switch ( $client_shape['shape'] ) {
			case 'shell':
				return $this->build_shell_snippet( $endpoint_url, $auth_header );
			case 'native':
				return $this->build_native_json_snippet( $client_shape['variant'], $endpoint_url, $auth_header );
			case 'bridge':
				return $this->build_bridge_snippet( $endpoint_url, $auth_header, self::MCP_REMOTE_VERSION );
			case 'toml':
				return $this->build_toml_snippet( $endpoint_url, $auth_header );
		}

		throw new \InvalidArgumentException( 'Unsupported MCP client shape.' );
	}

	/**
	 * Build the Claude Code shell command.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_header  Authorization header value.
	 * @return string Shell command.
	 */
	private function build_shell_snippet( $endpoint_url, $auth_header ) {

		return sprintf(
			'claude mcp add --scope user --transport http %1$s "%2$s" --header "Authorization: %3$s"',
			self::NAME_TOKEN,
			$endpoint_url,
			$auth_header
		);
	}

	/**
	 * Build a native remote-HTTP JSON configuration.
	 *
	 * @param string $variant      Native JSON variant.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_header  Authorization header value.
	 * @return string JSON configuration.
	 */
	private function build_native_json_snippet( $variant, $endpoint_url, $auth_header ) {

		$server = array(
			'url'     => $endpoint_url,
			'headers' => array(
				'Authorization' => $auth_header,
			),
		);

		if ( 'servers' === $variant ) {
			$server = array_merge( array( 'type' => 'http' ), $server );
		}

		$config = array(
			$variant => array(
				self::NAME_TOKEN => $server,
			),
		);

		return (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build a Claude Desktop mcp-remote bridge configuration.
	 *
	 * @param string $endpoint_url         MCP endpoint URL.
	 * @param string $auth_header          Authorization header value.
	 * @param string $pinned_remote_version Pinned mcp-remote package version.
	 * @return string JSON configuration.
	 */
	private function build_bridge_snippet( $endpoint_url, $auth_header, $pinned_remote_version ) {

		$args = array(
			'-y',
			'mcp-remote@' . $pinned_remote_version,
			$endpoint_url,
			'--header',
			'Authorization: ' . $auth_header,
		);

		$config = array(
			'mcpServers' => array(
				self::NAME_TOKEN => array(
					'command' => 'npx',
					'args'    => $args,
				),
			),
		);

		return (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build the Windows bridge variant — launched through cmd with the
	 * credential in env, because Windows breaks the npx spawn and arg spaces.
	 *
	 * @since 4.11.99
	 *
	 * @param string $endpoint_url          MCP endpoint URL.
	 * @param string $auth_header           Authorization header value.
	 * @param string $pinned_remote_version Pinned mcp-remote package version.
	 * @return string JSON configuration.
	 */
	private function build_bridge_windows_snippet( $endpoint_url, $auth_header, $pinned_remote_version ) {

		$config = array(
			'mcpServers' => array(
				self::NAME_TOKEN => array(
					'command' => 'cmd',
					'args'    => array(
						'/c',
						'npx',
						'-y',
						'mcp-remote@' . $pinned_remote_version,
						$endpoint_url,
						'--header',
						'Authorization:${AUTH_HEADER}', // Substituted by mcp-remote at run time, not the shell.
					),
					'env'     => array(
						'AUTH_HEADER' => $auth_header,
					),
				),
			),
		);

		return (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build a native Codex Streamable HTTP configuration.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_header  Authorization header value.
	 * @return string TOML configuration.
	 */
	private function build_toml_snippet( $endpoint_url, $auth_header ) {

		$endpoint_url = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $endpoint_url );

		return sprintf(
			"[mcp_servers.%1\$s]\nurl = \"%2\$s\"\nhttp_headers = { \"Authorization\" = \"%3\$s\" }",
			self::NAME_TOKEN,
			$endpoint_url,
			str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $auth_header )
		);
	}

	/**
	 * Build a one-click Cursor install URL.
	 *
	 * @param string $alias        Local client alias.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_header  Authorization header value.
	 * @return string Cursor deeplink.
	 */
	private function build_cursor_deeplink( $alias, $endpoint_url, $auth_header ) {

		$config = array(
			'url'     => $endpoint_url,
			'headers' => array(
				'Authorization' => $auth_header,
			),
		);

		$json = (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		return 'cursor://anysphere.cursor-deeplink/mcp/install?name=' . rawurlencode( $alias ) . '&config=' . rawurlencode( base64_encode( $json ) );
	}

	/**
	 * Build the universal plain-English fallback prompt.
	 *
	 * @param string $client_label Client display label.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_header  Authorization header value.
	 * @return string Agent prompt.
	 */
	private function build_agent_prompt( $client_label, $endpoint_url, $auth_header ) {

		return sprintf(
			/* translators: 1: AI client name, 2: local server alias, 3: MCP endpoint URL, 4: HTTP Basic authorization value. */
			__(
				'Connect %1$s to my WordPress site by adding the MCP server below, then list its tools to confirm the connection works:

- Name: %2$s
- Transport: Streamable HTTP
- URL: %3$s
- Auth: HTTP Basic
- Authorization header: %4$s',
				'premium-addons-for-elementor'
			),
			$client_label,
			self::NAME_TOKEN,
			$endpoint_url,
			$auth_header
		);
	}

	/**
	 * Connection name written into every client snippet.
	 *
	 * Derived from the site URL so someone connecting a live site, its staging
	 * copy and a local copy ends up with three distinct servers instead of three
	 * called "premium-addons". The path is part of it because subdirectory
	 * installs share a host and would otherwise still collide.
	 *
	 * @since 4.11.99
	 *
	 * @return string Connection name, matching [A-Za-z0-9_-]+.
	 */
	public static function default_server_name() {

		$home = home_url();
		$host = preg_replace( '/^www\./', '', self::get_endpoint_host( $home ) );
		$slug = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $host . (string) wp_parse_url( $home, PHP_URL_PATH ) ) ), '-' );

		if ( '' === $slug ) {
			return self::DEFAULT_SERVER_NAME;
		}

		$name = 'pa-' . $slug;

		return strlen( $name ) > self::MAX_SERVER_NAME_LENGTH
			? rtrim( substr( $name, 0, self::MAX_SERVER_NAME_LENGTH ), '-' )
			: $name;
	}

	/**
	 * Get the host used by the actual MCP endpoint.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return string Endpoint host.
	 */
	public static function get_endpoint_host( $endpoint_url ) {

		$host = wp_parse_url( $endpoint_url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
		}

		return is_string( $host ) ? strtolower( trim( $host, '[]' ) ) : '';
	}

	/**
	 * Get the scheme used by the actual MCP endpoint.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return string http or https.
	 */
	public static function endpoint_scheme( $endpoint_url ) {

		$scheme = strtolower( (string) wp_parse_url( $endpoint_url, PHP_URL_SCHEME ) );

		return 'https' === $scheme ? 'https' : 'http';
	}

	/**
	 * Determine whether a host is local or private.
	 *
	 * @param string $host Endpoint host.
	 * @return bool Whether the host is local.
	 */
	public static function is_local_host( $host ) {

		$host     = strtolower( trim( $host, '[]' ) );
		$suffixes = array( 'local', 'test', 'localhost', 'ddev.site', 'lndo.site' );

		foreach ( $suffixes as $suffix ) {
			if ( $host === $suffix || substr( $host, -strlen( '.' . $suffix ) ) === '.' . $suffix ) {
				return true;
			}
		}

		if ( '::1' === $host || 0 === strpos( $host, '127.' ) || 0 === strpos( $host, '10.' ) || 0 === strpos( $host, '192.168.' ) ) {
			return true;
		}

		return 1 === preg_match( '/^172\.(1[6-9]|2[0-9]|3[01])\./', $host );
	}

	/**
	 * @return MCP_Settings
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}
}
