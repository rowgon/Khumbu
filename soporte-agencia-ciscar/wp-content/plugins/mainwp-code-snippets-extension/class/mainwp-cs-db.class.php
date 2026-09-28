<?php
/**
 * MainWP Code Snippets Extension - Database Class
 *
 * @package MainWP\CodeSnippets
 */

/**
 * Class MainWP_CS_DB
 *
 * Handles all database operations for the Code Snippets extension.
 *
 * @since 1.0.0
 */
class MainWP_CS_DB {

	/**
	 * Current database schema version.
	 *
	 * @var string
	 */
	private $mainwp_code_snippets_db_version = '1.7';

	/**
	 * Singleton instance.
	 *
	 * @var MainWP_CS_DB
	 */
	private static $instance = null;

	/**
	 * Table name prefix (e.g. wp_mainwp_).
	 *
	 * @var string
	 */
	private $table_prefix;

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_CS_DB
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
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	/**
	 * Build a full table name from a suffix.
	 *
	 * @param string $suffix Table suffix (e.g. 'codesnippet').
	 * @return string Full table name.
	 */
	public function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	/**
	 * Install or upgrade the database schema.
	 *
	 * Creates the codesnippet table if it does not exist, runs any necessary
	 * data migrations, and seeds default snippets on first install.
	 *
	 * @return void
	 */
	public function install() {
		global $wpdb;

		$current_version = get_option( 'mainwp_code_snippets_db_version', '' );
		$table           = $this->table_name( 'codesnippet' );
		$table_exists    = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		$table_missing   = ! $table_exists;

		if ( $table_exists && '1.3' === $current_version ) {
			$snippet = $this->get_codesnippet_by( 'title', 'Count Published Posts' );
			if ( is_object( $snippet ) ) {
				$this->update_codesnippet(
					array(
						'id'   => $snippet->id,
						'code' => "include_once(ABSPATH . WPINC . '/pluggable.php');\n\$count_posts = wp_count_posts();\necho get_bloginfo('name').' has '.\$count_posts->publish.' published posts.';",
					)
				);
			}
		}

		if ( $current_version === $this->mainwp_code_snippets_db_version && $table_exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'codesnippet' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`userid` int(11) NOT NULL DEFAULT 0,
`title` text NOT NULL,
`snippet_slug` varchar(32) NOT NULL,
`description` text NOT NULL,
`code` text NOT NULL,
`sites` text NOT NULL,
`groups` text NOT NULL,
`clients` text NOT NULL,
`type` varchar(1) NOT NULL,
`date` int(11) NOT NULL,
PRIMARY KEY  (`id`)  ) ' . $charset_collate;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $tbl );

		$table_exists = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		if ( ! $table_exists ) {
			return;
		}

		if ( ! empty( $current_version ) && version_compare( $current_version, '1.7', '<' ) ) {
			$wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"UPDATE `{$this->table_name( 'codesnippet' )}` SET `clients` = %s WHERE `clients` = ''",
					base64_encode( serialize( array() ) ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				)
			);
		}

		$default_snippets = array(
			array(
				'title'       => 'Remove Admin Bar',
				'description' => 'Removes the admin bar on the front end. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong>',
				'type'        => 'S',
				'code'        => "add_filter( 'show_admin_bar', '__return_false' );",
			),
			array(
				'title'       => 'Customise Admin Footer',
				'description' => 'Customise the footer in admin area. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong>',
				'type'        => 'S',
				'code'        => "function wpfme_footer_admin () {\n  echo 'Site developed and maintained by <a href=\"#\" target=\"_blank\">Your Name Here</a> and powered by <a href=\"http://wordpress.org\" target=\"_blank\">WordPress</a>.';\n}\nadd_filter('admin_footer_text', 'wpfme_footer_admin');",
			),
			array(
				'title'       => 'Remove WordPress Version',
				'description' => 'Removes the WordPress version number. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong>',
				'type'        => 'S',
				'code'        => "remove_action('wp_head', 'wp_generator');",
			),
			array(
				'title'       => 'Obscure Login Error Messages',
				'description' => 'Obscure login screen error messages. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong>',
				'type'        => 'S',
				'code'        => "function wpfme_login_obscure() {\n  return '<strong>Sorry</strong>: Think you have gone wrong somwhere!';\n}\nadd_filter( 'login_errors', 'wpfme_login_obscure' );",
			),
			array(
				'title'       => 'Login Shake Effect',
				'description' => 'Removes Wordpress login shake effect when error occurs. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong>',
				'type'        => 'S',
				'code'        => "function wps_login_error() {\n  remove_action('login_head', 'wp_shake_js', 12);\n}\nadd_action('login_head', 'wps_login_error');",
			),
			array(
				'title'       => 'Count Published Posts',
				'description' => 'Displays the number of published posts on the child site. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong>',
				'type'        => 'R',
				'code'        => "include_once(ABSPATH . WPINC . '/pluggable.php');\n\$count_posts = wp_count_posts();\necho get_bloginfo('name').' has '.\$count_posts->publish.' published posts.';",
			),
		);

		if ( ! empty( $current_version ) && version_compare( $current_version, '1.6', '<' ) ) {
			foreach ( $default_snippets as $snippet ) {
				$existing = $this->get_codesnippet_by( 'title', $snippet['title'] );
				if ( is_object( $existing ) ) {
					$this->update_codesnippet(
						array(
							'id'          => $existing->id,
							'description' => $snippet['description'],
						)
					);
				}
			}
		}

		if ( $table_missing || ! get_option( 'mainwp_code_snippets_added_default_snippets' ) ) {
			foreach ( $default_snippets as $snippet ) {
				$snippet['snippet_slug'] = MainWP_CS_Utility::rand_string();
				$this->update_codesnippet( $snippet );
			}
			update_option( 'mainwp_code_snippets_added_default_snippets', true );
		}

		update_option( 'mainwp_code_snippets_db_version', $this->mainwp_code_snippets_db_version );
	}

	/**
	 * Insert or update a code snippet record.
	 *
	 * If `$snippet['id']` is set and non-zero, the existing row is updated.
	 * Otherwise a new row is inserted. When multi-user mode is active, operations
	 * are scoped to the current user's ID.
	 *
	 * @param array    $snippet  Associative array of snippet fields.
	 * @param int|null $user_id  Optional user ID override. Defaults to current user in multi-user mode.
	 * @return object|false Updated/inserted snippet object, or false on failure.
	 */
	public function update_codesnippet( $snippet, $user_id = null ) {
		global $wpdb;

		if ( null === $user_id && true === apply_filters( 'mainwp_is_multi_user', false ) ) {
			$user_id = get_current_user_id();
		}

		$id = isset( $snippet['id'] ) ? absint( $snippet['id'] ) : 0;

		$snippet['date'] = time();

		if ( $id ) {
			$data = $snippet;
			unset( $data['id'] );

			$where = array( 'id' => $id );
			if ( $user_id ) {
				$where['userid'] = $user_id;
			}

			$result = $wpdb->update( $this->table_name( 'codesnippet' ), $data, $where );

			if ( false !== $result ) {
				return $this->get_codesnippet_by( 'id', $id );
			}
		} else {
			if ( $user_id ) {
				$snippet['userid'] = $user_id;
			} elseif ( ! isset( $snippet['userid'] ) ) {
				$snippet['userid'] = 0;
			}

			foreach ( array( 'sites', 'groups', 'clients' ) as $selection_field ) {
				if ( ! isset( $snippet[ $selection_field ] ) ) {
					$snippet[ $selection_field ] = base64_encode( serialize( array() ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				}
			}

			$result = $wpdb->insert( $this->table_name( 'codesnippet' ), $snippet );

			if ( $result ) {
				return $this->get_codesnippet_by( 'id', $wpdb->insert_id );
			}
		}

		return false;
	}

	/**
	 * Retrieve one or all code snippets.
	 *
	 * @param string      $by      Lookup field: 'all', 'id', or 'title'.
	 * @param mixed       $value   Value to match (ignored when $by is 'all').
	 * @param string|null $orderby Column name to order results by (only used when $by is 'all').
	 * @return object|object[]|false Single row object, array of row objects, or false on failure.
	 */
	public function get_codesnippet_by( $by = 'all', $value = null, $orderby = null ) {
		global $wpdb;

		if ( 'all' !== $by && empty( $value ) ) {
			return false;
		}

		$table = $this->table_name( 'codesnippet' );

		if ( 'all' === $by ) {
			$order = null !== $orderby
				? 'ORDER BY ' . esc_sql( $orderby )
				: 'ORDER BY date DESC';

			return $wpdb->get_results( "SELECT * FROM `{$table}` WHERE 1 = 1 {$order}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( 'title' === $by ) {
			return $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `title` = %s", $value ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		if ( 'id' === $by ) {
			return $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d", absint( $value ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		return false;
	}

	/**
	 * Delete a code snippet from the database.
	 *
	 * When multi-user mode is active, the delete is scoped to the current user.
	 *
	 * @param int $cs_id Snippet ID to delete.
	 * @return bool True on success, false on failure.
	 */
	public function remove_codesnippet( $cs_id ) {
		global $wpdb;

		$cs_id = absint( $cs_id );
		if ( ! $cs_id ) {
			return false;
		}

		$where = array( 'id' => $cs_id );

		if ( true === apply_filters( 'mainwp_is_multi_user', false ) ) {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$where['userid'] = $user_id;
			}
		}

		return (bool) $wpdb->delete( $this->table_name( 'codesnippet' ), $where );
	}
}
