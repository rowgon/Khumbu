<?php

class MainWP_Pro_Reports_Hooks {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// construct.
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'mainwp_added_new_site', array( &$this, 'hook_update_site_update_tokens' ), 8, 1 );
		add_action( 'mainwp_update_site', array( &$this, 'hook_update_site_update_tokens' ), 8, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site_delete_tokens' ), 8, 1 );
		add_filter( 'mainwp_pro_reports_get_tokens_value', array( $this, 'hook_get_tokens_value' ), 10, 5 );

		add_filter( 'mainwp_pro_reports_generate_report_content', array( $this, 'hook_generate_report' ), 10, 5 );
		add_filter( 'mainwp_pro_reports_get_site_tokens', array( $this, 'hook_get_site_tokens' ), 10, 2 );
		add_filter( 'mainwp_pro_reports_generate_content', array( $this, 'hook_generate_content' ), 10, 5 );
		add_filter( 'mainwp_pro_reports_replace_site_token_values', array( $this, 'hook_replace_token_values' ), 10, 3 );

	}

	public function admin_init() {
		add_action( 'mainwp_shortcuts_widget', array( &$this, 'hook_shortcuts_widget' ), 10, 1 );
		add_filter( 'mainwp_managesites_column_url', array( &$this, 'managesites_column_url' ), 10, 2 );
		add_action( 'mainwp_managesite_backup', array( &$this, 'managesite_backup' ), 10, 3 );

	}

	/**
	 * Method hook_get_site_tokens().
	 *
	 * @param int $false input value.
	 * @param int $site_id The id of site
	 *
	 * @return array Site's tokens.
	 */
	public function hook_get_site_tokens( $false, $site_id ) {
		return MainWP_Pro_Reports::get_tokens_of_site( false, $site_id );
	}

	/**
	 * Method hook_replace_token_values().
	 *
	 * @param int        $string input value.
	 * @param int object $report report.
	 * @param int        $site_id The id of site
	 *
	 * @return array Site's tokens.
	 */
	public function hook_replace_token_values( $string, $report = false, $site_id = false ) {
		if ( MainWP_Pro_Reports::has_tokens( $string ) ) {
			if ( $site_id && $report ) {
				$tokens_values = MainWP_Pro_Reports::get_tokens_of_site( $report, $site_id );
				if ( $tokens_values ) {
					$string = MainWP_Pro_Reports::replace_site_tokens( $string, $tokens_values );
				}
			}
		}
		return $string;
	}

	/**
	 * Method hook_generate_content().
	 *
	 * @param int      $templ_content The content with tokens.
	 * @param int      $site_id The id of the site
	 * @param string|0 $from_date String of from date, date format 'Y-m-d H:i:s'
	 * @param string|0 $to_date String of to date, date format 'Y-m-d H:i:s'
	 * @param string   $type String of type.
	 *
	 * @return html content of generated content. False when something goes wrong.
	 */
	public function hook_generate_content( $templ_content, $site_id, $from_date = 0, $to_date = 0, $type = '' ) {

		if ( empty( $site_id ) || empty( $from_date ) || empty( $to_date ) ) {
			return $templ_content;
		}

		global $mainWPProReportsExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		} else {
			return $templ_content;
		}

		$filtered_reports = MainWP_Pro_Reports::get_instance()->filter_report_content( $templ_content, false, $website, $from_date, $to_date, $type );

		// to avoid error.
		if ( is_array( $filtered_reports ) && isset( $filtered_reports['error'] ) ) {
			return $templ_content;
		}

		if ( $type == 'raw' ) {
			return $filtered_reports;
		} else {
			$content = MainWP_Pro_Reports::generate_report_filtered_content( $filtered_reports );
		}

		return $content;
	}


	/**
	 * @param int      $report_id The id of the report
	 * @param int      $site_id The id of the site
	 * @param string|0 $from_date String of from date, date format 'Y-m-d H:i:s'
	 * @param string|0 $to_date String of to date, date format 'Y-m-d H:i:s'
	 *
	 * @return html content of generated report. False when something goes wrong.
	 */
	public function hook_generate_report( $report_id, $site_id, $from_date = 0, $to_date = 0, $type = '' ) {
		if ( empty( $report_id ) || empty( $site_id ) ) {
			return false;
		}

		$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );

		if ( empty( $report ) ) {
			return false;
		}

		global $mainWPProReportsExtensionActivator;
		$website = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			return false;
		}

		if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
			$from_date = strtotime( $from_date );
			$to_date   = strtotime( $to_date );
		} else {
			$from_date = $to_date = 0;
		}

		$templ_content = MainWP_Pro_Reports_Template::get_instance()->get_template_file_content( $report, $website );

		$filtered_reports = MainWP_Pro_Reports::get_instance()->filter_report_content( $templ_content, $report, $website, $from_date, $to_date, $type );
		if ( $type == 'raw' ) {
			return $filtered_reports;
		} else {
			$content = MainWP_Pro_Reports::generate_report_filtered_content( $filtered_reports );
		}
		return $content;
	}


	public function hook_update_site_update_tokens( $websiteId ) {
		global $mainWPProReportsExtensionActivator;

		if ( $websiteId ) {
			$tokens = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
			foreach ( $tokens as $token ) {
					$token_value = false;
					$input_name  = 'pro_reports_token_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );
				if ( isset( $_POST[ $input_name ] ) ) {
					$token_value = $_POST[ $input_name ];
				}

				if ( false !== $token_value ) {
					$current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'id', $token->id, $websiteId );
					if ( $current ) {
						MainWP_Pro_Reports_DB::get_instance()->update_token_site( $token->id, $token_value, $websiteId );
					} else {
						MainWP_Pro_Reports_DB::get_instance()->add_token_site( $token->id, $token_value, $websiteId );
					}
				}
			}
		}

	}


	public function delete_site_delete_tokens( $website ) {
		if ( $website ) {
			MainWP_Pro_Reports_DB::get_instance()->delete_site_tokens( false, $website->id );
		}
	}


	public function hook_shortcuts_widget( $website ) {
		if ( ! empty( $website ) ) {
			$found       = MainWP_Pro_Reports_DB::get_instance()->checked_if_site_have_report( $website->id );
			$reports_lnk = '';
			if ( $found ) {
				$reports_lnk = '<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&site=' . $website->id . '">' . esc_html__( 'Reports', 'mainwp-reports-extension' ) . '</a> | ';
			}
			?>
			<div class="mainwp-row">
				<div style="display: inline-block; width: 100px;"><?php esc_html_e( 'Client Reports:', 'mainwp-reports-extension' ); ?></div>
				<?php echo $reports_lnk; ?>
				<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=newreport&selected_site=<?php echo $website->id; ?>"><?php esc_html_e( 'New Report', 'mainwp-reports-extension' ); ?></a>
			</div>
			<?php
		}
	}


	public function managesites_column_url( $actions, $site_id ) {
		if ( ! empty( $site_id ) ) {
			$reports = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'site_id', $site_id );
			$link    = '';
			if ( is_array( $reports ) && count( $reports ) > 0 ) {
				$link = '<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&site=' . $site_id . '">' . esc_html__( 'Reports', 'mainwp-reports-extension' ) . '</a> ' .
						'( <a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=newreport&selected_site=' . $site_id . '">' . esc_html__( 'New', 'mainwp-reports-extension' ) . '</a> )';
			} else {
				$link = '<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=newreport&selected_site=' . $site_id . '">' . esc_html__( 'New Report', 'mainwp-reports-extension' ) . '</a>';
			}
			$actions['client_reports'] = $link;
		}
		return $actions;
	}

	function managesite_backup( $website, $args, $information ) {
		if ( empty( $website ) ) {
			return;
		}

		$type = isset( $args['type'] ) ? $args['type'] : '';

		if ( empty( $type ) ) {
			return;
		}

		global $mainWPProReportsExtensionActivator;

		$backup_type = ( 'full' == $type ) ? 'Full' : ( 'db' == $type ? 'Database' : '' );

		$message       = '';
		$backup_status = 'success';
		$backup_size   = 0;
		if ( isset( $information['error'] ) ) {
			$message       = $information['error'];
			$backup_status = 'failed';
		} elseif ( 'db' == $type && ! $information['db'] ) {
			$message       = 'Database backup failed.';
			$backup_status = 'failed';
		} elseif ( 'full' == $type && ! $information['full'] ) {
			$message       = 'Full backup failed.';
			$backup_status = 'failed';
		} elseif ( isset( $information['db'] ) ) {
			if ( false != $information['db'] ) {
				$message = 'Backup database success.';
			} elseif ( false != $information['full'] ) {
				$message = 'Full backup success.';
			}
			if ( isset( $information['size'] ) ) {
				$backup_size = $information['size'];
			}
		} else {
			$message       = 'Database backup failed due to an undefined error';
			$backup_status = 'failed';
		}

		// save results to child site stream
		$post_data = array(
			'mwp_action'  => 'save_backup_stream',
			'size'        => $backup_size,
			'message'     => $message,
			'destination' => 'Local Server',
			'status'      => $backup_status,
			'type'        => $backup_type,
		);
		apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $website->id, 'client_report', $post_data );
	}


	/**
	 * Hook get tokens value.
	 */
	public function hook_get_tokens_value( $input, $website, $tokens, $date_from = false, $date_to = false ) {
		if ( empty( $date_to ) ) {
			$date_to = time();
		}
		if ( empty( $date_from ) ) {
			$date_from = strtotime( '-1 month', $date_to );
		}

		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		$other_tokens = array(
			'body' => $tokens,
		);

		$site_id = 0;

		if ( is_numeric( $website ) ) {
			$site_id = $website;
		} elseif ( is_array( $website ) && isset( $website['id'] ) ) {
			$site_id = intval( $website['id'] );
		}

		$information = array();

		if ( $site_id ) {
			$information = MainWP_Pro_Reports::get_instance()->get_report_sections_tokens_values( $site_id, array(), $other_tokens, $date_from, $date_to );
			if ( is_array( $information ) && isset( $information['other_tokens_data'] ) && isset( $information['other_tokens_data']['body'] ) ) {
				return $information['other_tokens_data']['body'];
			}
		}

		return $information;
	}

}
