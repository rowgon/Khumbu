<?php
/**
 * MainWP Code Snippets Extension - Core Class
 *
 * @package MainWP\CodeSnippets
 */

/**
 * Class MainWP_CS
 *
 * Handles all core functionality for the Code Snippets extension,
 * including AJAX handlers and page rendering.
 *
 * @since 1.0.0
 */
class MainWP_CS {

	/**
	 * Singleton instance.
	 *
	 * @var MainWP_CS
	 */
	public static $instance = null;

	/**
	 * Option handle for extension settings.
	 *
	 * @var string
	 */
	protected $option_handle = 'mainwp_code_snippets_options';

	/**
	 * Cached options array.
	 *
	 * @var array
	 */
	protected $option;

	/**
	 * Allowed snippet type values.
	 *
	 * R = Return info, S = Execute on child sites, C = Save to wp-config.php
	 *
	 * @var string[]
	 */
	private static $allowed_types = array( 'R', 'S', 'C' );

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_CS
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->option = get_option( $this->option_handle, array() );
	}

	/**
	 * Register AJAX action hooks.
	 *
	 * @return void
	 */
	public function init() {
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_run_snippet_loading', array( $this, 'run_snippet_loading' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_run_snippet', array( $this, 'run_snippet' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_delete_snippet', array( $this, 'delete_snippet' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_save_snippet', array( $this, 'save_snippet' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_clear_on_site_loading', array( $this, 'snippet_clear_site_loading' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_clear_on_site', array( $this, 'ajax_clear_snippet_site' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_update_site_loading', array( $this, 'update_snippet_site_loading' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_update_site', array( $this, 'update_snippet_site' ) );
		do_action( 'mainwp_ajax_add_action', 'mainwp_snippet_delete_on_site', array( $this, 'ajax_delete_snippet_site' ) );
	}

	/**
	 * Get an extension option value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if key not set.
	 * @return mixed
	 */
	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	/**
	 * Set an extension option value.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return bool
	 */
	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	/**
	 * Normalize selected site, group, or client IDs.
	 *
	 * @param mixed $values Selected ID values.
	 * @return int[] Sanitized unique IDs.
	 */
	private static function normalize_selected_ids( $values ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $values ) ) ) );
	}

	/**
	 * Decode a saved snippet selection field.
	 *
	 * @param object $snippet Snippet database row object.
	 * @param string $field   Snippet field name.
	 * @return int[] Sanitized selected IDs.
	 */
	private static function get_snippet_selected_ids( $snippet, $field ) {
		if ( ! isset( $snippet->{$field} ) ) {
			return array();
		}

		return self::normalize_selected_ids( maybe_unserialize( base64_decode( $snippet->{$field} ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}

	/**
	 * AJAX handler: Delete a snippet from the database.
	 *
	 * Returns plain-text 'SUCCESS' or 'FAIL' (not JSON) to match JS checks.
	 *
	 * @return void
	 */
	public function delete_snippet() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_delete_snippet' );

		$id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;

		if ( ! $id ) {
			die( 'FAIL' );
		}

		if ( MainWP_CS_DB::get_instance()->remove_codesnippet( $id ) ) {
			die( 'SUCCESS' );
		}

		die( 'FAIL' );
	}

	/**
	 * AJAX handler: Save or update a snippet.
	 *
	 * @return void
	 */
	public function save_snippet() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_save_snippet' );

		if ( ! isset( $_POST['snippet_title'] ) ) {
			wp_send_json( array() );
		}

		$snippet_id = isset( $_POST['snippet_id'] ) ? absint( $_POST['snippet_id'] ) : 0;
		$raw_type   = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$type       = in_array( $raw_type, self::$allowed_types, true ) ? $raw_type : 'R';

		$snippet = array(
			'code'        => isset( $_POST['code'] ) ? $_POST['code'] : '',
			'title'       => isset( $_POST['snippet_title'] ) ? sanitize_text_field( wp_unslash( $_POST['snippet_title'] ) ) : '',
			'description' => isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '',
			'type'        => $type,
		);

		$clean_code = self::check_code_snippet( $snippet['code'] );

		if ( 'CODEEMPTY' === $clean_code ) {
			wp_send_json(
				array(
					'status'  => 'FAIL',
					'message' => __( 'Snippet cannot be empty. Please, enter the snippet and try again.', 'mainwp-code-snippets-extension' ),
				)
			);
		}

		$syntax_check = self::validate_php_snippet_syntax( $clean_code );

		if ( is_wp_error( $syntax_check ) ) {
			wp_send_json(
				array(
					'status'  => 'FAIL',
					'message' => $syntax_check->get_error_message(),
				)
			);
		}

		if ( $snippet_id ) {
			$snippet['id'] = $snippet_id;
		} else {
			$snippet['snippet_slug'] = MainWP_CS_Utility::rand_string();
		}

		$selected_wp     = array();
		$selected_group  = array();
		$selected_client = array();
		$select_by       = isset( $_POST['select_by'] ) ? sanitize_text_field( wp_unslash( $_POST['select_by'] ) ) : '';

		if ( 'site' === $select_by ) {
			$selected_wp = self::normalize_selected_ids( isset( $_POST['sites'] ) ? wp_unslash( $_POST['sites'] ) : array() );
		} elseif ( 'group' === $select_by ) {
			$selected_group = self::normalize_selected_ids( isset( $_POST['groups'] ) ? wp_unslash( $_POST['groups'] ) : array() );
		} elseif ( 'client' === $select_by ) {
			$selected_client = self::normalize_selected_ids( isset( $_POST['clients'] ) ? wp_unslash( $_POST['clients'] ) : array() );
		}

		$snippet['sites']   = base64_encode( serialize( $selected_wp ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$snippet['groups']  = base64_encode( serialize( $selected_group ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$snippet['clients'] = base64_encode( serialize( $selected_client ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		$get_snippet = MainWP_CS_DB::get_instance()->update_codesnippet( $snippet );

		if ( false !== $get_snippet ) {
			wp_send_json(
				array(
					'id'     => $get_snippet->id,
					'slug'   => $get_snippet->snippet_slug,
					'type'   => $get_snippet->type,
					'status' => 'SUCCESS',
				)
			);
		}

		wp_send_json( array() );
	}

	/**
	 * Build a standard snippet validation failure response.
	 *
	 * @param string $message Error message.
	 * @return array
	 */
	private static function snippet_validation_error_response( $message ) {
		return array(
			'status' => 'FAIL',
			'error'  => $message,
			'result' => '',
		);
	}

	/**
	 * Validate PHP snippet syntax without executing the snippet.
	 *
	 * File-scope declarations are rejected because snippets are later executed
	 * as raw eval() code or inserted after existing statements in wp-config.php.
	 *
	 * @param string $code PHP snippet code.
	 * @return true|WP_Error
	 */
	private static function validate_php_snippet_syntax( $code ) {
		if ( '' === trim( $code ) ) {
			return true;
		}

		if ( self::has_php_file_scope_directive( $code ) ) {
			return new WP_Error(
				'mainwp_code_snippets_file_scope_directive',
				__( 'PHP snippets cannot include top-level namespace, declare, or use statements. Snippets are executed as raw PHP code, not standalone PHP files. Remove the file-scope directive before saving.', 'mainwp-code-snippets-extension' )
			);
		}

		try {
			eval( "if ( false ) {\n" . $code . "\n}" ); // phpcs:ignore -- Syntax check only; guarded block prevents execution.
		} catch ( Throwable $e ) {
			return new WP_Error(
				'mainwp_code_snippets_php_syntax_error',
				sprintf(
					/* translators: %s: PHP parser error message. */
					__( 'PHP syntax check failed: %s', 'mainwp-code-snippets-extension' ),
					$e->getMessage()
				)
			);
		}

		return true;
	}

	/**
	 * Check for file-scope PHP declarations that cannot safely run in snippets.
	 *
	 * @param string $code PHP snippet code.
	 * @return bool
	 */
	private static function has_php_file_scope_directive( $code ) {
		$tokens      = token_get_all( "<?php\n" . $code );
		$brace_depth = 0;

		foreach ( $tokens as $index => $token ) {
			if ( '{' === $token ) {
				++$brace_depth;
				continue;
			}

			if ( '}' === $token ) {
				$brace_depth = max( 0, $brace_depth - 1 );
				continue;
			}

			if ( 0 !== $brace_depth || ! is_array( $token ) ) {
				continue;
			}

			if ( T_NAMESPACE === $token[0] || T_DECLARE === $token[0] ) {
				return true;
			}

			if ( T_USE === $token[0] && '(' !== self::get_next_significant_php_token( $tokens, $index ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the next non-whitespace/comment PHP token.
	 *
	 * @param array $tokens Token list.
	 * @param int   $index  Current token index.
	 * @return int|string|null
	 */
	private static function get_next_significant_php_token( $tokens, $index ) {
		$count = count( $tokens );

		for ( $i = $index + 1; $i < $count; ++$i ) {
			$token = $tokens[ $i ];

			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}

			return is_array( $token ) ? $token[0] : $token;
		}

		return null;
	}

	/**
	 * AJAX handler: Build the site list HTML for running a snippet.
	 *
	 * Outputs an HTML row per connected site, then exits.
	 *
	 * @return void
	 */
	public function run_snippet_loading() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_run_snippet_loading' );

		global $mainWPCSExtensionActivator;

		$sites   = array();
		$groups  = array();
		$clients = array();
		$type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'R';
		$type    = in_array( $type, self::$allowed_types, true ) ? $type : 'R';

		if ( isset( $_POST['sites'] ) && is_array( $_POST['sites'] ) ) {
			$sites = self::normalize_selected_ids( wp_unslash( $_POST['sites'] ) );
		}

		if ( isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ) {
			$groups = self::normalize_selected_ids( wp_unslash( $_POST['groups'] ) );
		}

		if ( isset( $_POST['clients'] ) && is_array( $_POST['clients'] ) ) {
			$clients = self::normalize_selected_ids( wp_unslash( $_POST['clients'] ) );
		}

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $sites, $groups, false, $clients );

		if ( ! is_array( $dbwebsites ) || empty( $dbwebsites ) ) {
			echo esc_html__( 'No child sites found. Please make sure your sites are properly connected.', 'mainwp-code-snippets-extension' );
			exit;
		}

		foreach ( $dbwebsites as $website ) {
			self::render_snippet_site_process_item( 'mainwp-snippet-item', $website->id, $website->name, $website->url, 0, 'R' === $type, 'R' === $type );
		}
		exit;
	}

	/**
	 * AJAX handler: Execute a snippet on a specific child site.
	 *
	 * @return void
	 */
	public function run_snippet() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_run_snippet' );

		$siteid = isset( $_POST['siteId'] ) ? absint( $_POST['siteId'] ) : 0;
		$code   = self::check_code_snippet( isset( $_POST['code'] ) ? $_POST['code'] : '' );

		if ( empty( $siteid ) ) {
			wp_send_json( 'FAIL' );
		}

		if ( 'CODEEMPTY' === $code ) {
			wp_send_json( 'CODEEMPTY' );
		}

		$syntax_check = self::validate_php_snippet_syntax( $code );

		if ( is_wp_error( $syntax_check ) ) {
			wp_send_json( self::snippet_validation_error_response( $syntax_check->get_error_message() ) );
		}

		global $mainWPCSExtensionActivator;

		$post_data   = array(
			'action' => 'run_snippet',
			'code'   => $code,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $siteid, 'code_snippet', $post_data );

		wp_send_json( $information );
	}

	/**
	 * Render one child-site row for snippet modal processing.
	 *
	 * @param string $classes     Additional process classes.
	 * @param int    $site_id     Child site ID.
	 * @param string $site_name   Child site name.
	 * @param string $site_url    Child site URL.
	 * @param int    $snippet_id  Optional snippet ID attribute.
	 * @param bool   $show_output Whether to render an output console panel.
	 * @param bool   $open_output Whether the output accordion is open initially.
	 * @return void
	 */
	private static function render_snippet_site_process_item( $classes, $site_id, $site_name, $site_url, $snippet_id = 0, $show_output = true, $open_output = true ) {
		$active_class = $open_output ? ' active' : '';

		if ( $show_output ) {
			?>
			<div
				class="ui styled fluid accordion mainwp-snippet-site-accordion <?php echo esc_attr( $classes ); ?>"
				<?php if ( $snippet_id ) : ?>
				snippetid="<?php echo absint( $snippet_id ); ?>"
				<?php endif; ?>
				siteid="<?php echo absint( $site_id ); ?>"
				status="queue">
				<div class="title <?php echo esc_attr( $active_class ); ?>">
					<i class="dropdown icon"></i>
					<a class="mainwp-snippet-site-name" href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a>
					<span class="status right floated"><i class="clock outline icon"></i></span>
				</div>
				<div class="content<?php echo esc_attr( $active_class ); ?>">
					<pre class="mainwp-snippet-output"></pre>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div
			class="ui segment mainwp-snippet-site-row <?php echo esc_attr( $classes ); ?>"
			<?php if ( $snippet_id ) : ?>
			snippetid="<?php echo absint( $snippet_id ); ?>"
			<?php endif; ?>
			siteid="<?php echo absint( $site_id ); ?>"
			status="queue">
			<div class="ui grid">
				<div class="two column row">
					<div class="column"><a class="mainwp-snippet-site-name" href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a></div>
					<div class="right aligned column"><span class="status"><i class="clock outline icon"></i></span></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the delete-snippet-from-sites page.
	 *
	 * Called when deleteonsites=1 is in the URL. Fetches connected child sites
	 * assigned to the snippet and renders the cleanup modal. If no sites are
	 * available, deletes the snippet from the database directly and shows a
	 * message before redirecting.
	 *
	 * @param int $snippetId The snippet ID to delete from child sites.
	 * @return void
	 */
	public static function render_delete_snippet_on_sites( $snippetId ) {
		$snippetId = absint( $snippetId );

		if ( empty( $snippetId ) ) {
			echo '<div class="ui red message">' . esc_html__( 'Snippet ID is empty.', 'mainwp-code-snippets-extension' ) . '</div>';
			return;
		}

		$snippet = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'id', $snippetId );

		if ( ! is_object( $snippet ) ) {
			echo '<div class="ui red message">' . esc_html__( 'Snippet not found.', 'mainwp-code-snippets-extension' ) . '</div>';
			?>
			<script type="text/javascript">
				setTimeout( function() {
					window.location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
				}, 2000 );
			</script>
			<?php
			return;
		}

		$websites = self::get_instance()->get_snippet_dbsites( $snippet );

		$dbwebsites = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$site_id     = is_array( $website ) && isset( $website['id'] ) ? absint( $website['id'] ) : ( isset( $website->id ) ? absint( $website->id ) : 0 );
				$sync_errors = is_array( $website ) && isset( $website['sync_errors'] ) ? $website['sync_errors'] : ( isset( $website->sync_errors ) ? $website->sync_errors : '' );

				if ( empty( $site_id ) || '' !== $sync_errors ) {
					continue;
				}

				$dbwebsites[ $site_id ] = $website;
			}
		}

		if ( empty( $dbwebsites ) ) {
			$deleted = MainWP_CS_DB::get_instance()->remove_codesnippet( $snippetId );
			if ( $deleted ) {
				echo '<div class="ui green message">' . esc_html__( 'No selected child sites found. Snippet has been deleted from the database.', 'mainwp-code-snippets-extension' ) . '</div>';
			} else {
				echo '<div class="ui red message">' . esc_html__( 'No selected child sites found. Snippet could not be deleted. Please try again.', 'mainwp-code-snippets-extension' ) . '</div>';
			}
			?>
			<script type="text/javascript">
				setTimeout( function() {
					window.location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
				}, 2000 );
			</script>
			<?php
			return;
		}

		$delete_site_count = count( $dbwebsites );
		?>
		<div class="ui modal" id="mainwp-code-snippets-cleaning-sites-modal">
			<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Cleaning Sites', 'mainwp-code-snippets-extension' ); ?></div>
			<div
				class="ui green progress mainwp-modal-progress"
				id="mainwp-code-snippets-cleaning-sites-progress"
				data-value="0"
				data-total="<?php echo absint( $delete_site_count ); ?>">
				<div class="bar"><div class="progress"></div></div>
				<div class="label">
					<?php
					printf(
						/* translators: %d: Number of selected child sites. */
						esc_html__( '0 / %d Processed', 'mainwp-code-snippets-extension' ),
						absint( $delete_site_count )
					);
					?>
				</div>
			</div>
			<div class="scrolling content">
				<div id="mainwp-modal-message-zone" class="ui message" style="display:none"></div>
				
				<div class="ui divided list">
					<?php foreach ( $dbwebsites as $website ) : ?>
					<?php
					$site_id   = is_array( $website ) && isset( $website['id'] ) ? absint( $website['id'] ) : absint( $website->id );
					$site_name = is_array( $website ) && isset( $website['name'] ) ? $website['name'] : $website->name;
					$site_url  = is_array( $website ) && isset( $website['url'] ) ? $website['url'] : $website->url;
					?>
					<div class="item">
						<a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a>
						<span class="mainwp-code-snippets-snippet-to-delete right floated" snippetid="<?php echo absint( $snippet->id ); ?>" siteid="<?php echo absint( $site_id ); ?>" status="queue">
							<span class="status"><i class="clock outline icon"></i></span>
						</span>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="actions">
				<input type="hidden" id="mainwp_snippet_delete_id" value="<?php echo absint( $snippet->id ); ?>">
				<input type="hidden" id="mainwp_snippet_slug_value" name="mainwp_snippet_slug_value" value="<?php echo esc_attr( $snippet->snippet_slug ); ?>">
				<input type="hidden" id="mainwp_snippet_type_value" name="mainwp_snippet_type_value" value="<?php echo esc_attr( $snippet->type ); ?>">
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( '#mainwp-code-snippets-cleaning-sites-modal' ).modal( {
					onHide: function () {
						window.location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
						return false;
					}
				} ).modal( 'show' );
				mainwp_snippet_delete_sites_start_next();
			} );
		</script>
		<?php
	}

	/**
	 * AJAX handler: Clear snippet from a single site (pre-save cleanup flow).
	 *
	 * @return void
	 */
	public function ajax_clear_snippet_site() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_clear_on_site' );
		$this->delete_snippet_site();
	}

	/**
	 * AJAX handler: Delete snippet from a single site (delete flow).
	 *
	 * @return void
	 */
	public function ajax_delete_snippet_site() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_delete_on_site' );
		$this->delete_snippet_site();
	}

	/**
	 * Send a request to a child site to remove a snippet file.
	 *
	 * Shared by ajax_clear_snippet_site() and ajax_delete_snippet_site().
	 *
	 * @return void
	 */
	public function delete_snippet_site() {
		$siteid      = isset( $_POST['siteId'] ) ? absint( $_POST['siteId'] ) : 0;
		$snippetslug = isset( $_POST['snippetSlug'] ) ? sanitize_text_field( wp_unslash( $_POST['snippetSlug'] ) ) : '';
		$raw_type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$type        = in_array( $raw_type, self::$allowed_types, true ) ? $raw_type : '';

		if ( empty( $siteid ) || empty( $snippetslug ) || empty( $type ) ) {
			wp_send_json( 'FAIL' );
		}

		global $mainWPCSExtensionActivator;

		$post_data   = array(
			'action' => 'delete_snippet',
			'slug'   => $snippetslug,
			'type'   => $type,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $siteid, 'code_snippet', $post_data );

		wp_send_json( $information );
	}

	/**
	 * AJAX handler: Build site list HTML for the snippet-clear confirmation step.
	 *
	 * Outputs an HTML row per connected site, then exits.
	 *
	 * @return void
	 */
	public function snippet_clear_site_loading() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_clear_on_site_loading' );

		global $mainWPCSExtensionActivator;

		$snippet  = self::check_snippet_post_value();
		$websites = apply_filters( 'mainwp_getsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), null );

		$dbwebsites = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( '' !== $website['sync_errors'] ) {
					continue;
				}
				$dbwebsites[ $website['id'] ] = $website;
			}
		}

		if ( empty( $dbwebsites ) ) {
			die( 'NOSITES' );
		}

		foreach ( $dbwebsites as $website ) {
			self::render_snippet_site_process_item( 'mainwp-clear-snippet-item', $website['id'], $website['name'], $website['url'], $snippet->id, false, false );
		}
		exit;
	}

	/**
	 * Strip PHP open/close tags and whitespace from a raw code string.
	 *
	 * @param string $code Raw code string (may be slashed).
	 * @return string|string Returns cleaned code, or the string 'CODEEMPTY' if blank after cleaning.
	 */
	public static function check_code_snippet( $code ) {
		$code = wp_unslash( $code );
		$code = preg_replace( '/^\s*<\?(?:php|=)?/i', '', $code );
		$code = preg_replace( '/\?>\s*$/', '', $code );
		$code = trim( $code );

		if ( empty( $code ) ) {
			return 'CODEEMPTY';
		}
		return $code;
	}

	/**
	 * Validate and fetch a snippet using the `snippetId` POST value.
	 *
	 * Outputs an error HTML fragment and calls wp_die() if the ID is missing
	 * or the snippet does not exist.
	 *
	 * @return object Snippet database row object.
	 */
	public static function check_snippet_post_value() {
		$snippet_id = isset( $_POST['snippetId'] ) ? absint( $_POST['snippetId'] ) : 0;

		if ( empty( $snippet_id ) ) {
			wp_die( '<div class="ui red message">' . esc_html__( 'Snippet ID empty.', 'mainwp-code-snippets-extension' ) . '</div>' );
		}

		$snippet = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'id', $snippet_id );

		if ( ! is_object( $snippet ) ) {
			wp_die( '<div class="ui red message">' . esc_html__( 'Snippet not found.', 'mainwp-code-snippets-extension' ) . '</div>' );
		}

		return $snippet;
	}

	/**
	 * Fetch connected child sites assigned to a snippet.
	 *
	 * Decodes the snippet's stored sites/groups/clients data and returns the
	 * matching database site objects from MainWP.
	 *
	 * @param object $snippet Snippet database row object.
	 * @return array Array of site objects.
	 */
	public function get_snippet_dbsites( $snippet ) {
		global $mainWPCSExtensionActivator;

		$sites   = self::get_snippet_selected_ids( $snippet, 'sites' );
		$groups  = self::get_snippet_selected_ids( $snippet, 'groups' );
		$clients = self::get_snippet_selected_ids( $snippet, 'clients' );

		return apply_filters( 'mainwp_getdbsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $sites, $groups, false, $clients );
	}

	/**
	 * AJAX handler: Build site list HTML for updating a snippet on child sites.
	 *
	 * Outputs an HTML row per site the snippet is assigned to, then exits.
	 *
	 * @return void
	 */
	public static function update_snippet_site_loading() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_update_site_loading' );

		$snippet = self::check_snippet_post_value();
		$code    = self::check_code_snippet( $snippet->code );

		if ( 'CODEEMPTY' === $code ) {
			wp_die( '<div class="ui red message">' . esc_html__( 'Snippet is empty.', 'mainwp-code-snippets-extension' ) . '</div>' );
		}

		$syntax_check = self::validate_php_snippet_syntax( $code );

		if ( is_wp_error( $syntax_check ) ) {
			wp_die( '<div class="ui red message">' . esc_html( $syntax_check->get_error_message() ) . '</div>' );
		}

		$dbwebsites = self::get_instance()->get_snippet_dbsites( $snippet );

		if ( ! is_array( $dbwebsites ) || empty( $dbwebsites ) ) {
			die( 'NOSITES' );
		}

		foreach ( $dbwebsites as $website ) {
			self::render_snippet_site_process_item( 'mainwp-update-snippet-item', $website->id, $website->name, $website->url, $snippet->id, false, false );
		}
		exit;
	}

	/**
	 * AJAX handler: Push a saved snippet to a specific child site.
	 *
	 * @return void
	 */
	public function update_snippet_site() {
		do_action( 'mainwp_secure_request', 'mainwp_snippet_update_site' );

		$siteid      = isset( $_POST['siteId'] ) ? absint( $_POST['siteId'] ) : 0;
		$snippetslug = isset( $_POST['snippetSlug'] ) ? sanitize_text_field( wp_unslash( $_POST['snippetSlug'] ) ) : '';
		$raw_type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$type        = in_array( $raw_type, self::$allowed_types, true ) ? $raw_type : '';

		if ( empty( $siteid ) || empty( $snippetslug ) ) {
			wp_send_json( 'FAIL' );
		}

		$code = self::check_code_snippet( isset( $_POST['code'] ) ? $_POST['code'] : '' );

		if ( 'CODEEMPTY' === $code ) {
			wp_send_json( 'CODEEMPTY' );
		}

		$syntax_check = self::validate_php_snippet_syntax( $code );

		if ( is_wp_error( $syntax_check ) ) {
			wp_send_json( self::snippet_validation_error_response( $syntax_check->get_error_message() ) );
		}

		global $mainWPCSExtensionActivator;

		$post_data   = array(
			'action' => 'save_snippet',
			'code'   => $code,
			'slug'   => $snippetslug,
			'type'   => $type,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $siteid, 'code_snippet', $post_data );

		wp_send_json( $information );
	}

	/**
	 * Render the snippet editor / new snippet form.
	 *
	 * @return void
	 */
	public static function render_settings() {
		$snippet = false;

		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			$snippet = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'id', absint( $_GET['id'] ) );
		}

		$code_id         = 0;
		$_code           = '';
		$_title          = '';
		$_desc           = '';
		$_slug           = '';
		$_type           = '';
		$type_run        = '';
		$type_save       = '';
		$type_config     = '';
		$selected_sites   = array();
		$selected_groups  = array();
		$selected_clients = array();
		$message         = '';
		$msg_class       = 'green';

		if ( is_object( $snippet ) ) {
			$code_id         = absint( $snippet->id );
			$_code           = $snippet->code;
			$_title          = $snippet->title;
			$_desc           = $snippet->description;
			$_slug           = $snippet->snippet_slug;
			$selected_sites   = self::get_snippet_selected_ids( $snippet, 'sites' );
			$selected_groups  = self::get_snippet_selected_ids( $snippet, 'groups' );
			$selected_clients = self::get_snippet_selected_ids( $snippet, 'clients' );
			$_type           = $snippet->type;

			if ( 'R' === $_type ) {
				$type_run = 'checked';
			} elseif ( 'S' === $_type ) {
				$type_save = 'checked';
			} elseif ( 'C' === $_type ) {
				$type_config = 'checked';
			}
		}

		if ( empty( $type_run ) && empty( $type_save ) && empty( $type_config ) ) {
			$type_run = 'checked';
		}

		if ( ! is_array( $selected_sites ) ) {
			$selected_sites = array();
		}

		if ( ! is_array( $selected_groups ) ) {
			$selected_groups = array();
		}

		if ( ! is_array( $selected_clients ) ) {
			$selected_clients = array();
		}

		if ( isset( $_GET['message'] ) ) {
			$raw_message = sanitize_text_field( wp_unslash( $_GET['message'] ) );
			if ( '1' === $raw_message ) {
				$message = __( 'Snippet has been saved.', 'mainwp-code-snippets-extension' );
			} elseif ( '-1' === $raw_message ) {
				$message   = __( 'Saving snippet failed. Please, try again.', 'mainwp-code-snippets-extension' );
				$msg_class = 'red';
			}
		}

		$snippet_type_options = array(
			'S' => array(
				'id'      => 'rad-snippet-type-save',
				'checked' => $type_save,
				'icon'    => 'bolt',
				'label'   => __( 'Execute on Site', 'mainwp-code-snippets-extension' ),
				'help'    => __( 'Runs this snippet on selected child sites and saves it there, in child site database.', 'mainwp-code-snippets-extension' ),
			),
			'R' => array(
				'id'      => 'rad-snippet-type-run',
				'checked' => $type_run,
				'icon'    => 'eye',
				'label'   => __( 'Return Info', 'mainwp-code-snippets-extension' ),
				'help'    => __( 'Runs this snippet on selected child sites and displays returned output in the console.', 'mainwp-code-snippets-extension' ),
			),
			'C' => array(
				'id'      => 'rad-snippet-type-config',
				'checked' => $type_config,
				'icon'    => 'file alternate outline',
				'label'   => __( 'Add to wp-config.php', 'mainwp-code-snippets-extension' ),
				'help'    => __( 'Saves this snippet to wp-config.php on selected child sites and executes from there.', 'mainwp-code-snippets-extension' ),
			),
		);

		$snippet_type_help = $snippet_type_options['R']['help'];
		foreach ( $snippet_type_options as $snippet_type_option ) {
			if ( ! empty( $snippet_type_option['checked'] ) ) {
				$snippet_type_help = $snippet_type_option['help'];
				break;
			}
		}
		?>
		<form method="POST" id="mainwp_snippet_edit_form" action="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension">
			<input type="hidden" id="mainwp_snippet_id_value" name="mainwp_snippet_id_value" value="<?php echo absint( $code_id ); ?>">
			<input type="hidden" id="mainwp_snippet_slug_value" name="mainwp_snippet_slug_value" value="<?php echo esc_attr( $_slug ); ?>">
			<input type="hidden" id="mainwp_snippet_type_value" name="mainwp_snippet_type_value" value="<?php echo esc_attr( $_type ); ?>">
			<input type="hidden" name="snp_security" value="<?php echo esc_attr( wp_create_nonce( 'mainwp-save-snippet' ) ); ?>">
			<div class="mainwp-main-content ui padded segment">
				<?php if ( ! empty( $message ) ) : ?>
				<div class="ui message <?php echo esc_attr( $msg_class ); ?>"><i class="close icon"></i> <?php echo esc_html( $message ); ?></div>
				<?php endif; ?>
				<div class="ui message" id="mainwp-message-zone"></div>
				<div class="ui secondary fitted segment">
					<div class="ui secondary top attached segment" style="margin-bottom:0;">
						<div class="ui two column grid">
							<div class="column">
								<div class="ui small form">
									<div class="field" style="margin-bottom:0!important;">
										<div class="ui grid">
											<div class="four wide middle aligned column"><label><?php esc_html_e( 'Snippet Title', 'mainwp-code-snippets-extension' ); ?> <span class="ui small red text"><?php esc_html_e( '(required)', 'mainwp-code-snippets-extension' ); ?></span></label></div>
											<div class="twelve wide middle aligned column"><input type="text" id="snp_snippet_title" name="snp_snippet_title" value="<?php echo esc_attr( wp_unslash( $_title ) ); ?>"/></div>
										</div>
									</div>
									<div class="field" style="margin-bottom:0!important;">
										<div class="ui grid">
											<div class="four wide middle aligned column"><label><?php esc_html_e( 'Snippet Description', 'mainwp-code-snippets-extension' ); ?></label></div>
											<div class="twelve wide middle aligned column"><input type="text" id="snp_snippet_desc" name="snp_snippet_desc" value="<?php echo esc_attr( wp_unslash( $_desc ) ); ?>"/></div>
										</div>
									</div>
								</div>
							</div>
							<div class="right aligned middle aligned column">
								<div class="mainwp-snippet-type-field">
									<?php foreach ( $snippet_type_options as $snippet_type => $snippet_type_option ) : ?>
										<input type="radio" id="<?php echo esc_attr( $snippet_type_option['id'] ); ?>" class="mainwp-snippet-type-input" name="snp_snippet_type" value="<?php echo esc_attr( $snippet_type ); ?>" <?php echo esc_attr( $snippet_type_option['checked'] ); ?> style="display:none;">
									<?php endforeach; ?>
									<div class="ui basic buttons mainwp-snippet-type-selector" role="group" aria-label="<?php esc_attr_e( 'Snippet Type', 'mainwp-code-snippets-extension' ); ?>">
										<?php foreach ( $snippet_type_options as $snippet_type => $snippet_type_option ) : ?>
											<button
												type="button"
												class="ui basic button mainwp-snippet-type-button <?php echo ! empty( $snippet_type_option['checked'] ) ? 'active green' : ''; ?>"
												data-snippet-type="<?php echo esc_attr( $snippet_type ); ?>"
												data-snippet-type-help="<?php echo esc_attr( $snippet_type_option['help'] ); ?>"
												aria-pressed="<?php echo ! empty( $snippet_type_option['checked'] ) ? 'true' : 'false'; ?>">
												<i class="<?php echo esc_attr( $snippet_type_option['icon'] ); ?> icon"></i><?php echo esc_html( $snippet_type_option['label'] ); ?>
											</button>
										<?php endforeach; ?>
									</div>
									<div>
										<div class="ui small pointing label mainwp-snippet-type-help">
											<?php echo esc_html( $snippet_type_help ); ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="ui fitted segment" style="margin-bottom:0;">
						<textarea id="mainwp-code-snippets-code-editor" name="mainwp-code-snippets-code-editor" rows="50" spellcheck="false"><?php echo ! empty( $_code ) ? esc_textarea( wp_unslash( $_code ) ) : ''; ?></textarea>
						<div id="mainwp-code-snippets-code-editor-holder"></div>
					</div>
					<div class="ui secondary bottom attached segment">
						<?php esc_html_e( 'MainWP is not responsible for the code that you run on your sites. Use this tool with extreme care and at your own risk. It is recommended that you run any code on a test site before releasing on live sites.', 'mainwp-code-snippets-extension' ); ?>
					</div>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites ui accordion mainwp-sidebar-accordion">
					<div class="title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Select Sites', 'mainwp-code-snippets-extension' ); ?></div>
					<div class="content active">
						<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_sites, $selected_groups, true, $selected_clients ); ?>
					</div>
				</div>

				<div class="ui fitted divider"></div>

				<div class="mainwp-search-submit">
					<div class="ui grid">
						<div class="sixteen wide middle aligned column">
							<a href="javascript:void(0);" id="mainwp-code-snippetes-execute-snippet-button" class="ui green fluid big button"><i class="play icon"></i> <?php esc_attr_e( 'Run Snippet', 'mainwp-code-snippets-extension' ); ?></a>
						</div>
						<div class="eight wide middle aligned column">
							<a href="javascript:void(0);" id="mainwp-code-snippetes-save-snippet-button" class="ui green basic fluid button"><i class="save outline icon"></i> <?php esc_html_e( 'Save', 'mainwp-code-snippets-extension' ); ?></a>
						</div>
						<?php if ( $code_id ) : ?>
						<div class="eight wide middle aligned column">
							<a href="javascript:void(0);" id="mainwp-code-snippetes-delete-snippet-button" class="ui fluid basic button"><i class="trash icon"></i> <?php esc_html_e( 'Delete', 'mainwp-code-snippets-extension' ); ?></a>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</form>
		<div class="ui clearing hidden divider"></div>
		<div class="ui modal" id="mainwp-code-snippets-console-modal">
			<i class="close icon mainwp-reload"></i>
			<div class="header mainwp-code-snippet-console-header">
				<div id="mainwp-code-snippet-output-title"><?php esc_html_e( 'Console', 'mainwp-code-snippets-extension' ); ?></div>
				<div class="sub header" id="mainwp-code-snippet-output-log"><?php esc_html_e( 'Ready', 'mainwp-code-snippets-extension' ); ?></div>
			</div>
			<div class="ui green progress mainwp-modal-progress" id="mainwp-code-snippet-output-progress" style="display:none;">
				<div class="bar"><div class="progress"></div></div>
				<div class="label"></div>
			</div>
			<div class="scrolling content">
				<div id="mainwp-code-snippet-output"></div>
			</div>
			<div class="actions">
				<div class="ui two column grid">
					<div class="left aligned column">
						<button type="button" class="ui red basic button mainwp-code-snippet-stop-button"><?php esc_html_e( 'Stop Process', 'mainwp-code-snippets-extension' ); ?></button>
					</div>
					<div class="right aligned column">
						<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets" class="ui left button"><?php esc_html_e( 'Back to Snippets', 'mainwp-code-snippets-extension' ); ?></a>
						<button type="button" class="ui green button mainwp-code-snippet-edit-button"><?php esc_html_e( 'Edit Snippet', 'mainwp-code-snippets-extension' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		self::render_delete_modal();
	}

	/**
	 * Render the snippets list table.
	 *
	 * @return void
	 */
	public static function render_list() {
		$snippets = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'all', null, 'title' );
		?>

		<table class="ui unstackable table" id="mainwp-code-snippets-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Snippet', 'mainwp-code-snippets-extension' ); ?></th>
					<th class="min-tablet"><?php esc_html_e( 'Type', 'mainwp-code-snippets-extension' ); ?></th>
					<th class="min-tablet collapsing"><?php esc_html_e( 'Target', 'mainwp-code-snippets-extension' ); ?></th>
					<th class="min-tablet"><?php esc_html_e( 'Last Edited', 'mainwp-code-snippets-extension' ); ?></th>
					<th class="no-sort min-tablet"></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( is_array( $snippets ) && ! empty( $snippets ) ) : ?>
					<?php self::render_table_content( $snippets ); ?>
				<?php endif; ?>
			</tbody>
		</table>

		<script type="text/javascript">
		jQuery( function () {
			var responsive = jQuery( window ).width() <= 1140;
			jQuery( '#mainwp-code-snippets-table' ).DataTable( {
				"columnDefs" : [ { "orderable": false, "targets": "no-sort" } ],
				"responsive" : responsive,
				"language": {
					"emptyTable": "<?php echo esc_js( __( "You don't have any saved code snippets yet. Go to the Execute Snippets page to create and save your first snippet.", 'mainwp-code-snippets-extension' ) ); ?>"
				},
				"stateSave": true,
				"stateDuration": 0,
				"colReorder" : { columns: ":not(:last-child)" },
				"drawCallback": function() {
					jQuery( '#mainwp-code-snippets-table .ui.dropdown' ).dropdown();
				}
			} ).on( 'columns-reordered', function () {
				setTimeout( function() {
					jQuery( '#mainwp-code-snippets-table .ui.dropdown' ).dropdown();
				}, 1000 );
			} );
			jQuery( '#mainwp-code-snippets-table .ui.dropdown' ).dropdown();
		} );
		</script>
		<?php
		self::render_delete_modal();
	}

	/**
	 * Render the delete snippet confirmation modal.
	 *
	 * Shared between the editor page and the list page.
	 *
	 * @return void
	 */
	public static function render_delete_modal() {
		?>
		<div class="ui mini modal" id="mainwp-code-snippet-delete-snippet-modal">
			<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Delete Snippet', 'mainwp-code-snippets-extension' ); ?></div>
			<div class="scrolling content">
				<div class="ui form">
					<p><?php esc_html_e( 'Do you want to keep the snippet on the child sites?', 'mainwp-code-snippets-extension' ); ?></p>
					<div class="grouped fields">
						<div class="field">
							<div class="ui radio checkbox">
								<input type="radio" value="0" name="delete_snippet_child_site" checked>
								<label><?php esc_html_e( 'Yes, keep it.', 'mainwp-code-snippets-extension' ); ?></label>
							</div>
						</div>
						<div class="field">
							<div class="ui radio checkbox">
								<input type="radio" value="1" name="delete_snippet_child_site">
								<label><?php esc_html_e( 'No, remove it from sites.', 'mainwp-code-snippets-extension' ); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="actions">
				<input type="hidden" name="delete_snippetid" value="" />
				<input type="submit" value="<?php esc_attr_e( 'Delete', 'mainwp-code-snippets-extension' ); ?>" class="ui button green" id="mainwp-code-snippets-delete-snippet-button">
			</div>
		</div>
		<?php
	}

	/**
	 * Render table rows for the snippets list.
	 *
	 * @param object[] $snippets Array of snippet database row objects.
	 * @return void
	 */
	public static function render_table_content( $snippets ) {
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$type_labels = array(
			'S' => '<span class="ui small orange basic label"><i class="bolt icon"></i> ' . __( 'Execute', 'mainwp-code-snippets-extension' ) . '</span>',
			'R' => '<span class="ui small blue basic label"><i class="eye icon"></i> ' . __( 'Return', 'mainwp-code-snippets-extension' ) . '</span>',
			'C' => '<span class="ui small purple basic label"><i class="file alternate outline icon"></i> ' . __( 'wp-config.php', 'mainwp-code-snippets-extension' ) . '</span>',
		);

		foreach ( $snippets as $snippet ) :
			$type_label      = isset( $type_labels[ $snippet->type ] ) ? $type_labels[ $snippet->type ] : '';
			$edit_url        = esc_url( '?page=Extensions-Mainwp-Code-Snippets-Extension&tab=editor&id=' . absint( $snippet->id ) );
			$selected_sites   = self::get_snippet_selected_ids( $snippet, 'sites' );
			$selected_groups  = self::get_snippet_selected_ids( $snippet, 'groups' );
			$selected_clients = self::get_snippet_selected_ids( $snippet, 'clients' );
			$sites_count      = count( $selected_sites );
			$groups_count     = count( $selected_groups );
			$clients_count    = count( $selected_clients );
			$target_count     = $sites_count;
			$target_icon      = 'wordpress';
			$snippet_date     = absint( $snippet->date );
			$exact_date       = wp_date( $date_format, $snippet_date ) . ' ' . wp_date( $time_format, $snippet_date );

			if ( 0 === $sites_count && 0 < $groups_count ) {
				$target_count = $groups_count;
				$target_icon  = 'tag';
			} elseif ( 0 === $sites_count && 0 === $groups_count && 0 < $clients_count ) {
				$target_count = $clients_count;
				$target_icon  = 'users';
			}
			?>
			<tr id="snippet-<?php echo absint( $snippet->id ); ?>">
				<td>
					<div>
						<a href="<?php echo $edit_url; ?>"><?php echo esc_html( $snippet->title ); ?></a>
					</div>
					<span class="ui small text">
						<?php echo wp_kses( $snippet->description, array( 'br' => array(), 'strong' => array(), 'em' => array(), 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?>
					</span>
				</td>
				<td><?php echo wp_kses( $type_label, array( 'span' => array( 'class' => array() ), 'i' => array( 'class' => array() ) ) ); ?></td>
				<td data-order="<?php echo absint( $target_count ); ?>" class="center aligned">
					<span class="ui small basic grey label"><i class="<?php echo esc_attr( $target_icon ); ?> icon"></i><?php echo absint( $target_count ); ?></span>
				</td>
				<td data-order="<?php echo absint( $snippet_date ); ?>">
					<span data-tooltip="<?php echo esc_attr( $exact_date ); ?>" data-position="left center" data-inverted="">
						<?php echo esc_html( MainWP_CS_Utility::time_elapsed_string( $snippet_date ) ); ?>
					</span>
				</td>
				<td class="right aligned">
					<a class="ui mini green icon button" href="<?php echo $edit_url; ?>"><i class="play alt icon"></i></a>
					<a href="#" class="snippet_list_delete_item ui mini grey basic icon button" type="<?php echo esc_attr( $snippet->type ); ?>" id="<?php echo absint( $snippet->id ); ?>"><i class="trash alt icon"></i></a>
				</td>
			</tr>
			<?php
		endforeach;
	}

	/**
	 * Render the extension main page.
	 *
	 * Dispatches to the editor, list, or delete-on-sites view depending on URL params.
	 *
	 * @return void
	 */
	public static function render() {
		$current_tab = 'snippets';

		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			if ( 'snippets' === $tab ) {
				$current_tab = 'snippets';
			} elseif ( 'new' === $tab ) {
				$current_tab = 'new';
			} elseif ( 'editor' === $tab ) {
				$current_tab = 'editor';
			}
		}

		if ( isset( $_GET['id'], $_GET['deleteonsites'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['deleteonsites'] ) ) ) {
			self::render_delete_snippet_on_sites( absint( $_GET['id'] ) );
			return;
		}
		?>
		<div id="mainwp-code-snippets">
			<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-code-snippets-menu">
				<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets" class="item <?php echo 'snippets' === $current_tab ? 'active' : ''; ?>"><i class="list icon"></i> <?php esc_html_e( 'Snippets', 'mainwp-code-snippets-extension' ); ?></a>
				<?php if ( 'editor' === $current_tab ) : ?>
				<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=editor" class="item <?php echo 'editor' === $current_tab ? 'active' : ''; ?>"><i class="code icon"></i> <?php esc_html_e( 'Run Snippet', 'mainwp-code-snippets-extension' ); ?></a>
				<?php else : ?>
				<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=new" class="item <?php echo 'new' === $current_tab ? 'active' : ''; ?>"><i class="code icon"></i> <?php esc_html_e( 'New Snippet', 'mainwp-code-snippets-extension' ); ?></a>
				<?php endif; ?>
			</div>
			<?php if ( 'snippets' === $current_tab ) : ?>
				<div class="mainwp-actions-bar">
					<div class="ui two column grid">
						<div class="left aligned middle aligned column"></div>
						<div class="right aligned middle aligned column">
							<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=new" class="ui mini green basic button"><i class="plus icon"></i> <?php esc_html_e( 'New Snippet', 'mainwp-code-snippets-extension' ); ?></a>
						</div>
					</div>
				</div>
				<div class="ui padded segment" id="mainwp-code-snippets-saved-snippets-tab">
					<?php self::render_list(); ?>
				</div>
			<?php elseif ( 'new' === $current_tab || 'editor' === $current_tab ) : ?>
				<div id="mainwp-code-snippets-new-snippet-tab">
					<?php self::render_settings(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
