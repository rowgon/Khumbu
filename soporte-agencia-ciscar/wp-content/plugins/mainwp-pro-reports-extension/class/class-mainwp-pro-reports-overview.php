<?php

class MainWP_Pro_Reports_Overview {

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'admin_init_preview_generated_content' ), 999, 10, 2 );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_pro_reports_delete_token', array( &$this, 'ajax_delete_token' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_save_token', array( &$this, 'ajax_save_token' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_do_action_report', array( &$this, 'ajax_do_action_report' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_load_sites', array( &$this, 'ajax_load_sites' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_generate_report', array( &$this, 'ajax_generate_report_content' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_email_message_preview', array( &$this, 'ajax_email_message_preview' ), 10, 3 );
	}

	public static function show_mainwp_message( $notice_id ) {
		$status = get_user_option( 'mainwp_notice_saved_status' );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}

	public function admin_init_preview_generated_content() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Mainwp-Pro-Reports-Extension' && isset( $_GET['action'] ) && 'preview_report' === $_GET['action'] && isset( $_GET['id'] ) && isset( $_GET['pro_reports_action_nonce'] ) ) {
			$report_id = intval( $_GET['id'] );
			echo $this->render_preview_report( $report_id );
			exit();
		}
	}


	public static function verify_nonce() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], '_wpnonce_mainwp_pro_reports' ) ) {
			die( json_encode( array( 'error' => esc_html__( 'Invalid nonce!', 'mainwp-pro-reports-extension' ) ) ) );
		}
	}


	// Render extenison page
	public function render_overview() {
		self::render_tabs();
	}

	// Render extenison page tabs
	public static function render_tabs() {

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'dashboard' ) {
				$current_tab = 'dashboard';
			} elseif ( $_GET['tab'] == 'reports' ) {
				$current_tab = 'reports';
			} elseif ( $_GET['tab'] == 'tokens' ) {
				$current_tab = 'tokens';
			} elseif ( $_GET['tab'] == 'report' ) {
				$current_tab = 'report';
			} elseif ( $_GET['tab'] == 'edit_report' ) {
				$current_tab = 'edit_report';
			}
		} else {
			$current_tab = 'reports';
		}

		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-pro-reports-menu">
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=dashboard" class="item <?php echo ( $current_tab == 'dashboard' ) ? 'active' : ''; ?>"><i class="tasks icon"></i> <?php esc_html_e( 'Child Reports Dashboard', 'mainwp-pro-reports-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=reports" class="item <?php echo ( $current_tab == 'reports' || $current_tab == '' ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php esc_html_e( 'Reports', 'mainwp-pro-reports-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report" class="item <?php echo ( $current_tab == 'report' ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php esc_html_e( 'Create Report', 'mainwp-pro-reports-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=tokens" class="item <?php echo ( $current_tab == 'tokens' ) ? 'active' : ''; ?>"><i class="code icon"></i> <?php esc_html_e( 'Tokens', 'mainwp-pro-reports-extension' ); ?></a>
		</div>

		<?php

		self::get_instance()->get_tabs_content( $current_tab );
	}

	// Render extenison page tabs content
	public function get_tabs_content( $current_tab = '' ) {

		if ( $current_tab == 'dashboard' ) {
			?>
			<?php MainWP_Pro_Reports_Plugin::gen_actions_bar(); ?>
			<div class="ui segment" id="mainwp-pro-reports-dashboard">
				<?php MainWP_Pro_Reports_Plugin::gen_dashboard_tab(); ?>
			</div>
			<?php
		} elseif ( $current_tab == '' || $current_tab == 'reports' ) {
			?>
			<div id="mainwp-pro-reports-tab" class="ui segment">
				<?php $this->get_pro_reports(); ?>
			</div>
			<?php
		} elseif ( $current_tab == 'report' ) {
			?>
			<div id="mainwp-pro-reports-report-tab">
				<?php $this->render_report(); ?>
			</div>
			<?php
		} elseif ( $current_tab == 'tokens' ) {
			?>
			<div id="mainwp-pro-reports-custom-tokens-tab" class="ui segment">
				<?php $this->get_pro_reports_custom_tokens(); ?>
			</div>
			<?php
		}
	}

	// Display reports table
	public function get_pro_reports() {
		$reports = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'all' );
		if ( ! is_array( $reports ) ) {
			$reports = array();
		}
		$client_reports = array();
		foreach ( $reports as $report ) {
			$client_reports[] = $report;
		}

		$count_reports = count( $client_reports );
		?>
		<?php if ( $count_reports > 0 ) : ?>
		<table id="mainwp-client-reports-reports-table" class="ui single line table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Client', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Send To', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Last Sent', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Date Range', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Schedule', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php self::get_pro_reports_table_body( $client_reports ); ?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php esc_html_e( 'Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Client', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Send To', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Last Sent', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Date Range', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Schedule', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
			jQuery(function(){
				jQuery( '#mainwp-client-reports-reports-table' ).DataTable( {
					"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
					"stateSave": true,
					"colReorder" : { columns: ":not(:last-child)" },
					"stateDuration": 0,
					"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
					"language": { "emptyTable": "No reports created yet. Go to the Create Report tab to create reports." },
					"drawCallback": function( settings ) {
						jQuery( '#mainwp-client-reports-reports-table .ui.dropdown').dropdown();
						mainwp_datatable_fix_menu_overflow('#mainwp-client-reports-reports-table', -50 );
					},
				} ).on( 'columns-reordered', function ( e, settings, details ) {
					console.log('columns-reordered');
					setTimeout(() => {
						jQuery( '#mainwp-client-reports-reports-table .ui.dropdown' ).dropdown();
						mainwp_datatable_fix_menu_overflow('#mainwp-client-reports-reports-table', -50 );
					}, 1000);
				} );
				jQuery( '#mainwp-client-reports-reports-table .ui.dropdown' ).dropdown();
				mainwp_datatable_fix_menu_overflow('#mainwp-client-reports-reports-table', -50 );
			});
		</script>
		<?php else : ?>
		<table id="mainwp-client-reports-reports-table" class="ui single line table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Client', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Send To', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Last Sent', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Date Range', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Schedule', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="7">
					<?php esc_html_e( 'No reports created yet! Click', 'mainwp-pro-reports-extension' ); ?> <a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report"><?php esc_html_e( 'Create Report', 'mainwp-pro-reports-extension' ); ?></a> <?php esc_html_e( 'and start creating reports. ', 'mainwp-pro-reports-extension' ); ?>
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th><?php esc_html_e( 'Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Client', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Send To', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Last Sent', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Date Range', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Schedule', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php endif; ?>
		<?php
	}

	// Display report table body
	public static function get_pro_reports_table_body( $reports ) {

		$recurring_schedule = array(
			'daily'   => esc_html__( 'Daily', 'mainwp-pro-reports-extension' ),
			'weekly'  => esc_html__( 'Weekly', 'mainwp-pro-reports-extension' ),
			'monthly' => esc_html__( 'Monthly', 'mainwp-pro-reports-extension' ),
		);

		$_show_completed_siteids = apply_filters( 'mainwp_pro_reports_table_show_completed_site_ids', false );

		foreach ( $reports as $report ) {

			$is_scheduled = $report->scheduled ? true : false;

			$sche_column = esc_html__( 'Manual', 'mainwp-pro-reports-extension' );

			if ( ! empty( $report->recurring_schedule ) && $is_scheduled ) {
				$sche_column = $recurring_schedule[ $report->recurring_schedule ];
				if ( ! empty( $report->schedule_nextsend ) ) {
					$sche_column .= '<br><em>Next Run: ' . MainWP_Pro_Reports_Utility::format_timestamp( $report->schedule_nextsend ) . '</em>'; // display time in UTC.
				}
			}

			$sel_sites  = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
			$sel_groups = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );

			if ( ! is_array( $sel_sites ) ) {
				$sel_sites = array();
			}

			if ( ! is_array( $sel_groups ) ) {
				$sel_groups = array();
			}

			$disable_act_buttons = true;
			if ( count( $sel_sites ) > 0 || count( $sel_groups ) > 0 ) {
				$disable_act_buttons = false;
			}

			?>

			<tr report-id="<?php echo $report->id; ?>">
				<td><a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&id=<?php echo $report->id; ?>"><?php echo stripslashes( $report->title ); ?></a></td>
				<td><?php echo esc_html( stripslashes( $report->send_to_name ) ); ?></td>
				<td><?php echo esc_html( stripslashes( $report->send_to_email ) ); ?></td>
				<td>
				<?php
				echo ! empty( $report->lastsend ) ? MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $report->lastsend ) ) : ''; // display local time.

				if ( $is_scheduled && $_show_completed_siteids ) {
						$comp_ids = ! empty( $report->completed_sites ) ? @json_decode( $report->completed_sites, true ) : false;

						$str_info = '';
					if ( ! empty( $comp_ids ) && is_array( $comp_ids ) ) {

						$failed_ids = $success_ids = $gen_failed_ids = array();
						foreach ( $comp_ids as $sid => $val ) {
							if ( $val == 1 ) {
								$success_ids[] = $sid;
							} elseif ( $val == 0 ) {
								$failed_ids[] = $sid;
							} elseif ( $val > 1 ) { // generated content failed
								$gen_failed_ids[] = $sid;
							}
						}

						if ( ! empty( $success_ids ) ) {
							$str_info = 'Success: ' . count( $success_ids );
						}

						if ( ! empty( $failed_ids ) ) {
							$str_info .= '<br/>';
							$str_info .= 'Send failed: ' . count( $failed_ids );
						}

						if ( ! empty( $gen_failed_ids ) ) {
							$str_info .= '<br/>';
							$str_info .= 'Generate failed: ' . count( $gen_failed_ids ) . ' (' . implode( ',', $gen_failed_ids ) . ')';
						}
					}

					if ( $str_info != '' ) {
						?>
						<br/>
						<em>
						<?php echo $str_info; ?>
						</em>
						<?php
					}
				}

				?>
				</td>
				<td>
					<?php
					if ( $is_scheduled ) {
						$date_from = $report->date_from_nextsend;
						$date_to   = $report->date_to_nextsend;
						if ( empty( $date_from ) && $report->date_from ) {
							$date_from = $report->date_from;
						}
						if ( empty( $date_to ) && $report->date_to ) {
							$date_to = $report->date_to;
						}
					} else {
						$date_from = $report->date_from;
						$date_to   = $report->date_to;
					}
					?>
					<?php echo ! empty( $date_from ) ? MainWP_Pro_Reports_Utility::format_datestamp( $date_from, true ) . ' - ' : ''; ?>
					<?php echo ! empty( $date_to ) ? MainWP_Pro_Reports_Utility::format_datestamp( $date_to, true ) : ''; ?>
				</td>
				<td><?php echo $sche_column; ?></td>
				<td>
					<div class="ui right pointing dropdown icon mini basic green button" style="z-index:999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&id=<?php echo $report->id; ?>"><?php esc_html_e( 'Edit', 'mainwp-pro-reports-extension' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=replicate&id=<?php echo $report->id; ?>&pro_reports_action_nonce=<?php echo wp_create_nonce( 'pro_reports_action_nonce' ); ?>"><?php esc_html_e( 'Duplicate', 'mainwp-pro-reports-extension' ); ?></a>
							<?php if ( ! $disable_act_buttons ) : ?>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=preview&id=<?php echo $report->id; ?>&pro_reports_action_nonce=<?php echo wp_create_nonce( 'pro_reports_action_nonce' ); ?>"><?php esc_html_e( 'Preview' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=save_pdf&id=<?php echo $report->id; ?>&pro_reports_action_nonce=<?php echo wp_create_nonce( 'pro_reports_action_nonce' ); ?>" ><?php esc_html_e( 'Download PDF' ); ?></a>
							<?php endif; ?>
							<?php if ( ! $disable_act_buttons && ! $is_scheduled ) : ?>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=send&id=<?php echo $report->id; ?>&pro_reports_action_nonce=<?php echo wp_create_nonce( 'pro_reports_action_nonce' ); ?>"><?php esc_html_e( 'Send' ); ?></a>
							<?php endif; ?>
							<a href="#" action="delete" class="item reports_action_row_lnk"><?php esc_html_e( 'Delete', 'mainwp-pro-reports-extension' ); ?></a>
						</div>
					</div>
					<span class="status"></span>
				</td>
			</tr>
			<?php
		}
	}

	// Display New & Edit report tab content
	public function render_report() {
		$messages      = array();
		$errors        = array();
		$report_action = '';
		$report_id     = 0;
		$report        = false;

		if ( isset( $_GET['action'] ) ) {
			if ( 'send' === (string) $_GET['action'] ) {
				$report_action = 'send';
			} elseif ( 'preview' === (string) $_GET['action'] ) {
					$report_action = 'preview';
			} elseif ( 'preview_generated' === (string) $_GET['action'] ) {
					$report_action = 'preview_generated';
			} elseif ( 'replicate' === (string) $_REQUEST['action'] ) {
					$report_action = 'replicate';
			} elseif ( 'save_pdf' === (string) $_REQUEST['action'] ) {
					$report_action = 'get_save_pdf';
			} elseif ( 'download_pdf' === $_GET['action'] ) {
					$report_action = 'download_pdf';
			}
		} elseif ( isset( $_POST['pro-report-action'] ) ) {
			if ( 'send' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'send';
			} elseif ( 'send_test' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'send_test';
			} elseif ( 'save_pdf' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'save_pdf';
			} elseif ( 'preview' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'preview';
			}
		}

		if ( isset( $_REQUEST['id'] ) ) {
			$report_id = $_REQUEST['id'];
		}

		$result_save = array();

		if ( isset( $_POST['pro-report-action'] ) && ! empty( $_POST['pro-report-action'] ) ) {
			$result_save = $this->handle_report_saving();
			$report_id   = isset( $result_save['id'] ) && $result_save['id'] ? $result_save['id'] : 0;
			if ( isset( $result_save['message'] ) ) {
				$messages = $result_save['message'];
			}
			if ( isset( $result_save['error'] ) ) {
				$errors = $result_save['error'];
			}
		}

		if ( isset( $_GET['message'] ) ) {
			if ( 1 == $_GET['message'] ) {
				$messages[] = esc_html__( 'The report(s) have been dispatched successfully.', 'mainwp-pro-reports-extension' );
			}
		}

		if ( $report_id && $report_action == 'download_pdf' ) {
			// !=== 1 - pdf report ===!//
			$report_pdf = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
			if ( $report_pdf ) {
				$time            = time();
				$report_contents = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_content( $report_id );

				if ( $report_contents ) {
					?>
					<script type="text/javascript">
						jQuery(document).ready( function ($) {
								<?php
								foreach ( $report_contents as $_content ) {
									$site_id   = $_content->site_id;
									$content   = $_content->report_content_pdf;
									$suffix_id = md5( $time . '_' . $site_id . '_' . $report_id );
									set_transient( 'mainwp_report_pdf_' . $suffix_id, $content, 3 * MINUTE_IN_SECONDS ); // 3 mins cache.
									unset( $report_pdf );
									?>
										window.open( 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=saveaspdf&id=<?php echo esc_attr( $report_id ); ?>&time=<?php echo esc_attr( $time ); ?>&siteid=<?php echo intval( $site_id ); ?>&_nonce_savepdf=<?php echo wp_create_nonce( '_nonce_savepdf' ); ?>', '_blank' );
									<?php
								}
								?>
						});
					</script>
					<?php
				}
				$messages[] = esc_html__( 'PDF document(s) creation in progress.', 'mainwp-pro-reports-extension' );
			}
		}

		if ( $report_id ) {
			if ( false == $report ) {
				$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
			}
		}

		if ( $report_action == 'replicate' ) {
			$report->id           = $report_id = 0;
			$report->title        = '';
			$report->attach_files = '';
			// do not replicate client info
			$report->send_to_name  = '[client.name]';
			$report->send_to_email = '[client.email]';
			$report->bcc_email     = '';
			$report->client        = '[client.name]';
			// $report->subject = 'Report for [client.site.name]'; // replicate subject
			$report->client_id = 0;
		}

		$selected_websites = array();
		$selected_wpgroups = array();
		$selected_clients  = array();

		if ( isset( $_GET['selected_sites'] ) && ! empty( $_GET['selected_sites'] ) ) {
			$selected_websites = explode( ',', $_GET['selected_sites'] );
		}

		$selected_site = 0;

		if ( isset( $_REQUEST['action'] ) ) {
			if ( 'newreport' == $_REQUEST['action'] ) {
				if ( isset( $_GET['selected_site'] ) && ! empty( $_GET['selected_site'] ) ) {
					$selected_site = $_GET['selected_site'];
				}
				$report_action = 'create_new';
			}
		}

		$sel_sites   = array();
		$sel_groups  = array();
		$sel_clients = array();

		if ( $report_action == 'preview' || $report_action == 'send' || $report_action == 'send_test' ) {

			$check_valid = true;

			if ( empty( $report ) || ! is_object( $report ) ) {
				$errors[]    = esc_html__( 'Report data appears to be invalid or corrupted. Please try again. For ongoing issues, kindly contact our support team for assistance.' );
				$check_valid = false;
			} else {
				$sel_sites   = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
				$sel_groups  = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );
				$sel_clients = ! empty( $report->clients ) ? json_decode( $report->clients ) : array();

				if ( ( ! is_array( $sel_sites ) || count( $sel_sites ) == 0 ) && ( ! is_array( $sel_clients ) || count( $sel_clients ) == 0 ) && ( ! is_array( $sel_groups ) || count( $sel_groups ) == 0 ) ) {
					$errors[]    = esc_html__( 'Please select at least one website, tag, or client.', 'mainwp-pro-reports-extension' );
					$check_valid = false;
				}
			}

			if ( ! $check_valid ) {
				if ( $report_action == 'send' || $report_action == 'preview' ) {
					$report_action = '';
				}
			}

			if ( $report_action == 'send' && empty( $report->send_to_email ) ) {
				$errors[]      = esc_html__( 'Report Send to email address missing! This field is mandatory.', 'mainwp-client-reports-extension' );
				$report_action = '';
			}

			if ( $report_action == 'send' && $report->scheduled ) {
				$errors[]      = esc_html__( 'The scheduled report failed to send. Please check the report settings and scheduling configurations, and try resending. For further assistance, contact support.', 'mainwp-client-reports-extension' );
				$report_action = '';
			}
		}

		$str_error   = ( is_array( $errors ) && count( $errors ) > 0 ) ? implode( '<br/>', $errors ) : '';
		$str_message = ( is_array( $messages ) && count( $messages ) > 0 ) ? implode( '<br/>', $messages ) : '';

		global $mainWPProReportsExtensionActivator;

		$sites_with_creport = array();

		$params = array(
			'extra_select_wp_fields' => array( 'plugin_upgrades', 'plugins' ),
		);

		$sql_sites = apply_filters( 'mainwp_getsqlwebsites_for_current_user', false, $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $params );

		$websites = MainWP_Pro_Reports_DB::get_instance()->query( $sql_sites );

		while ( $websites && ( $website = MainWP_Pro_Reports_DB::fetch_object( $websites ) ) ) {
			if ( '' !== $website->sync_errors ) {
				continue;
			}

			if ( $website && $website->plugins != '' ) {
				$plugins = json_decode( $website->plugins, 1 );
				if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
					foreach ( $plugins as $plugin ) {
						if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {
							if ( $plugin['active'] ) {
								$sites_with_creport[] = $website->id;
								break;
							}
						}
					}
				}
			}
		}

		if ( $websites ) {
			MainWP_Pro_Reports_DB::free_result( $websites );
		}

		$scheduled_creport = false;
		if ( ! empty( $report ) && ! empty( $report->scheduled ) ) {
			$scheduled_creport = true;
		}

		$scheduled_report = false;

		if ( ! empty( $report ) && ! empty( $report->scheduled ) ) {
			$scheduled_report = true;
		}

		if ( ! empty( $report ) ) {
			$selected_websites = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
			$selected_wpgroups = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );
			$selected_clients  = ! empty( $report->clients ) ? json_decode( $report->clients, true ) : array();
		}

		if ( ! is_array( $selected_websites ) ) {
			$selected_websites = array();
		}
		if ( ! is_array( $selected_wpgroups ) ) {
			$selected_wpgroups = array();
		}
		if ( ! is_array( $selected_clients ) ) {
			$selected_clients = array();
		}

		?>
		<form method="post" enctype="multipart/form-data" id="mainwp-pro-report-form" action="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report<?php echo ! empty( $report_id ) ? '&id=' . $report_id : ''; ?>">
			<div class="mainwp-main-content ui segment">
				<div class="ui hidden fitted divider"></div>
				<?php if ( ! empty( $str_error ) ) : ?>
				<div class="ui red message" ><?php echo $str_error; ?><i class="close icon mainwp-notice-hide"></i></div>
				<?php endif; ?>
				<?php if ( ! empty( $str_message ) ) : ?>
				<div  class="ui green message" ><?php echo $str_message; ?><i class="close icon mainwp-notice-hide"></i></div>
				<?php endif; ?>
				<div class="ui yellow message" id="edit-reports-message-zone" style="display:none"></div>
				<!-- Report options -->
				<?php $this->new_report_setting( $report ); ?>
				<!-- End Report options -->
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites ui accordion mainwp-sidebar-accordion" id="mainwp-pro-reports-select-sites-box">
					<div class="ui title active">
						<i class="dropdown icon"></i>
						<?php esc_html_e( 'Select Sites', 'mainwp-pro-reports-extension' ); ?>
					</div>
					<div class="content active">
						<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-create-report-select-sites-info' ) ) : ?>
						<div class="ui info message">
						<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-create-report-select-sites-info"></i>
							<?php echo esc_html__( 'The Select Sites shows only your websites where you have the MainWP Child Reports plugin installed.', 'mainwp-pro-reports-extension' ); ?>
						</div>
						<?php endif; ?>
						<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_websites, $selected_wpgroups, true, $selected_clients ); ?>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="button" value="<?php esc_html_e( 'Save Draft', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-save-report-button" name="mainwp-pro-reports-save-report-button" class="ui big fluid button">
					<div class="ui hidden fitted divider"></div>
					<input type="submit" value="<?php esc_html_e( 'Preview Report', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-preview-report-button" name="mainwp-pro-reports-preview-report-button" class="ui big green basic fluid button">
					<div class="ui hidden fitted divider"></div>
					<input type="submit" value="<?php esc_html_e( 'Download PDF', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-pdf-button" name="mainwp-pro-reports-pdf-button" class="ui big green basic fluid button">
					<div class="ui hidden fitted divider"></div>
					<input type="submit" value="<?php esc_html_e( 'Send Now', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-send-report-button" name="mainwp-pro-reports-send-report-button" class="ui big green fluid button">
					<input type="submit" value="<?php esc_html_e( 'Schedule Report', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-schedule-report-button" name="mainwp-pro-reports-schedule-report-button" class="ui big green fluid button">
				</div>
			</div>
			<input type="hidden" name="pro-report-action" id="pro-report-action" value="">
			<input type="hidden" name="report-id" value="<?php echo ( is_object( $report ) && isset( $report->id ) ) ? $report->id : '0'; ?>">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mainwp-pro-reports-nonce' ); ?>">
		</form>

		<div class="ui small modal" id="mainwp-pro-reports-generating-report-modal">
			<div class="header"><?php echo $report ? esc_html( $report->title ) : ''; ?></div>
			<div class="scrolling content" id="mainwp-pro-reports-generating-report-content">
			</div>
		</div>
		<?php
		$show_preview = false;
		?>
		<div class="ui large longer modal" id="mainwp-pro-reports-preview-modal">
			<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Report Preview', 'mainwp-client-reports-extension' ); ?></div>
			<div class="content" style="padding:0;" id="mainwp-pro-reports-preview-content">
				<?php
				if ( is_object( $report ) ) {
					if ( $report_action == 'preview_generated' ) {
						echo self::render_preview_modal_content( $report );
						$show_preview = true;
					}
				}
				?>
			</div>
			<div class="actions">
				<div class="ui two columns grid">
					<div class="left aligned middle aligned column">
						<input type="button" <?php echo $scheduled_creport ? 'style="display:none"' : ''; ?> value="<?php esc_html_e( 'Send Now' ); ?>" class="ui button green" id="mainwp-pro-reports-preview-send-button"/>
					</div>
					<div class="right aligned middle aligned column">
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				mainwp_pro_reports_remove_sites_without_reports_plugin('<?php echo implode( ',', $sites_with_creport ); ?>');
				<?php
				if ( $report_action != '' && in_array( $report_action, array( 'preview', 'send_test', 'save_pdf', 'get_save_pdf', 'send' ) ) ) {
					?>
						mainwp_reports_to_load_sites( '<?php echo esc_html( $report_action ); ?>', '<?php echo intval( $report_id ); ?>' );
					<?php
				}
				if ( $report_action == 'create_new' && $selected_site ) {
					?>
					$('#selected_sites_<?php echo $selected_site; ?>').trigger('click');
					<?php
				}

				if ( $show_preview ) {
					?>
						jQuery( '#mainwp-pro-reports-preview-modal' ).modal({onHide: function(){
							jQuery('#mainwp-pro-reports-preview-content').html('');
						}}).modal( 'show' );
					<?php
				}
				?>
				});
			</script>

		<?php
	}


	public static function render_preview_modal_content( $report ) {
		ob_start();
		?>
		<?php
		$check_ok = false;
		if ( ! empty( $report ) ) {
			$report_contents = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_content( $report->id );
			if ( is_array( $report_contents ) ) {
				?>
				<iframe width="100%" style="min-height:1220px" name="reports_preview_iframe" id="reports_preview_iframe" src="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&action=preview_report&id=<?php echo $report->id; ?>&pro_reports_action_nonce=<?php echo wp_create_nonce( 'pro_reports_action_nonce' ); ?>"  ></iframe>
				<?php
				$check_ok = true;
			}
		}

		if ( ! $check_ok ) {
			?>
			<div class="ui yellow message"><?php esc_html_e( 'Preview could not be generated. Please try again.', 'mainwp-pro-reports-extension' ); ?></div>
			<?php
		}
		?>
		<?php
		$output = ob_get_clean();
		return $output;
	}


	public function handle_report_saving() {

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'mainwp-pro-reports-nonce' ) ) {
			$messages             = $errors = array();
			$report               = array();
			$current_attach_files = '';

			$recalculate_recurring_date = false;
			$current_recurring_schedule = '';
			$current_recurring_day      = '';

			$settings = array();

			if ( isset( $_REQUEST['id'] ) && ! empty( $_REQUEST['id'] ) ) {
				$report               = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $_REQUEST['id'], null, null, ARRAY_A );
				$current_attach_files = $report['attach_files'];

				if ( ! $report['scheduled'] ) {
					$recalculate_recurring_date = true;
				} else {
					$current_recurring_schedule = $report['recurring_schedule'];
					$current_recurring_day      = $report['recurring_day'];
				}

				if ( $report && ! empty( $report['settings'] ) ) {
					$settings = $report['settings'];
					if ( ! is_array( $settings ) ) {
						$settings = array();
					}
				}
			} else {
				$recalculate_recurring_date = true;
			}

			if ( isset( $_POST['pro-report-title'] ) && ( $title = trim( $_POST['pro-report-title'] ) ) != '' ) {
				$report['title'] = $title;
			}

			if ( isset( $_POST['pro-report-template'] ) ) {
				$report['template'] = $_POST['pro-report-template'];
			}

			if ( isset( $_POST['pro-report-email-template'] ) ) {
				$report['template_email'] = $_POST['pro-report-email-template'];
			}

				$scheduled_report = false;
			if ( isset( $_POST['pro-report-type'] ) && ! empty( $_POST['pro-report-type'] ) ) {
				$report['scheduled'] = 1;
				$scheduled_report    = true;
			} else {
				$report['scheduled'] = 0;
			}

			if ( ! $scheduled_report ) {
					$start_time = $end_time = 0;
				if ( isset( $_POST['pro-report-from-date'] ) && ( $start_date = trim( $_POST['pro-report-from-date'] ) ) != '' ) {
					$start_time = strtotime( $start_date . ' ' . date( 'H:i:s' ) );
				}
				if ( isset( $_POST['pro-report-to-date'] ) && ( $end_date = trim( $_POST['pro-report-to-date'] ) ) != '' ) {
					$end_time = strtotime( $end_date . ' ' . date( 'H:i:s' ) );
				}
				if ( $start_time > $end_time ) {
					$tmp        = $start_time;
					$start_time = $end_time;
					$end_time   = $tmp;
				}

				if ( $start_time > 0 && $end_time > 0 ) {
					$start_time = mktime( 0, 0, 0, date( 'm', $start_time ), date( 'd', $start_time ), date( 'Y', $start_time ) );
					$end_time   = mktime( 23, 59, 59, date( 'm', $end_time ), date( 'd', $end_time ), date( 'Y', $end_time ) );
				}

					$report['date_from'] = $start_time;
					$report['date_to']   = $end_time;
			}

			if ( isset( $_POST['pro-report-custom-pdf-file-name'] ) && ( $pdf_file_name = sanitize_text_field( $_POST['pro-report-custom-pdf-file-name'] ) ) != '' ) {
				$report['custom_pdf_file_name'] = $pdf_file_name;
			}

			// if ( isset( $_POST['pro-report-client-id'] ) ) {
			// $report['client_id'] = intval( $_POST['pro-report-client-id'] );
			// }

			if ( isset( $_POST['pro-report-from-name'] ) ) {
				$report['fname'] = trim( $_POST['pro-report-from-name'] );
			}

			$from_email = '';
			if ( ! empty( $_POST['pro-report-from-email'] ) ) {
				$from_email = trim( $_POST['pro-report-from-email'] );
				if ( ! empty( $from_email ) ) {
					if ( preg_match( '/\[[^\]]+\]/is', $from_email, $matches ) ) {
						$from_email = $matches[0]; // first token
					} elseif ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $from_email ) ) {
						$from_email = ''; // incorrect email
					} else {
						// ok, valid email
					}
				}
			}

			if ( $from_email == '' ) {
				$errors[] = esc_html__( 'The email address specified in the "Send from" field of the report settings is not valid. Please verify the details and attempt to regenerate the report.', 'mainwp-pro-reports-extension' );
			}

			$report['femail'] = $from_email;

			if ( isset( $_POST['pro-report-to-client'] ) ) {
				$report['send_to_name'] = trim( $_POST['pro-report-to-client'] );
			}

			$reply_email = '';
			if ( ! empty( $_POST['pro-report-reply-to'] ) ) {
				$reply_email = trim( $_POST['pro-report-reply-to'] );
				if ( ! empty( $reply_email ) ) {
					if ( preg_match( '/\[[^\]]+\]/is', $reply_email, $matches ) ) {
						$reply_email = $matches[0]; // first token
					} elseif ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $reply_email ) ) {
						$reply_email = ''; // incorrect email
					} else {
						// ok, valid email
					}
				}
			}

			$report['reply_to'] = $reply_email;

			if ( isset( $_POST['pro-report-reply-to-name'] ) ) {
				$report['reply_to_name'] = trim( $_POST['pro-report-reply-to-name'] );
			}

			$report['showhide_sections'] = json_encode( $_POST['pro-report-showhide-sections'] );

				$to_email     = '';
				$valid_emails = array();
			if ( ! empty( $_POST['pro-report-to-email'] ) ) {
				$to_emails = explode( ',', trim( $_POST['pro-report-to-email'] ) );
				if ( is_array( $to_emails ) ) {
					foreach ( $to_emails as $_email ) {
						$_email = trim( $_email );
						if ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $_email ) && ! preg_match( '/^\[.+\]/is', $_email ) ) {
							// $errors[] = esc_html__( 'Incorrect Email Address in the Send To field.', 'mainwp-pro-reports-extension' );
							// avoid incorrect email
						} else {
							$valid_emails[] = $_email;
						}
					}
				}
			}

			if ( count( $valid_emails ) > 0 ) {
				$to_email = implode( ',', $valid_emails );
			} else {
				$to_email = '';
				$errors[] = esc_html__( 'The email address specified in the "Send to" field of the report settings is not valid. Please verify the details and attempt to regenerate the report.', 'mainwp-pro-reports-extension' );
			}

			$report['send_to_email'] = $to_email;

			$bcc_email = '';

			if ( ! empty( $_POST['pro-report-bcc-email'] ) ) {
				$bcc_email = trim( wp_unslash( $_POST['pro-report-bcc-email'] ) );
				if ( ! empty( $bcc_email ) ) {
					if ( preg_match( '/\[[^\]]+\]/is', $bcc_email, $matches ) ) {
						$bcc_email = $matches[0]; // first token
					} else {
						$valid_bcc_emails = array();
						$bcc_emails       = preg_split( '/(;|,)/', $bcc_email );
						if ( is_array( $bcc_emails ) ) {
							foreach ( $bcc_emails as $_bcc ) {
								$_bcc = trim( $_bcc );
								if ( ! empty( $_bcc ) && preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $_bcc ) ) {
									$valid_bcc_emails[] = $_bcc;
								}
							}
						}
						if ( ! empty( $valid_bcc_emails ) ) {
							$bcc_email = implode( ',', $valid_bcc_emails );
						} else {
							$bcc_email = '';
						}
					}
				}
			}

			$report['bcc_email'] = $bcc_email;

			if ( isset( $_POST['pro-report-email-subject'] ) ) {
				$report['subject'] = trim( $_POST['pro-report-email-subject'] );
			}

			if ( isset( $_POST['pro-report-email-message'] ) ) {
				$report['message'] = trim( $_POST['pro-report-email-message'] );
			}

				$report['recurring_schedule'] = '';

			if ( isset( $_POST['pro-report-schedule'] ) ) {
				$report['recurring_schedule'] = trim( $_POST['pro-report-schedule'] );
			}

				$report['recurring_day'] = '';

			if ( $scheduled_report ) {
				if ( $report['recurring_schedule'] == 'monthly' ) {
					$report['recurring_day'] = intval( $_POST['pro-report-schedule-month-day'] );
				} elseif ( $report['recurring_schedule'] == 'weekly' ) {
					$report['recurring_day'] = intval( $_POST['pro-report-schedule-day'] );
				} elseif ( $report['recurring_schedule'] == 'daily' ) {
					// nothing, send everyday
				} else {
					$report['recurring_schedule'] = ''; // will not schedule send
				}

				if ( ( $current_recurring_schedule != $report['recurring_schedule'] ) || ( $current_recurring_day != $report['recurring_day'] ) ) {
					$recalculate_recurring_date = true;
				}

					// only update date when create new report
					// or change from one-time to scheduled report
					// or schedule settings changed
				if ( $recalculate_recurring_date ) {
					$cal_recurring = self::calc_recurring_date( $report['recurring_schedule'], $report['recurring_day'] );   // saving handle, do not need to pass offset data time
					if ( is_array( $cal_recurring ) ) {
						$report['date_from']          = $cal_recurring['date_from'];
						$report['date_to']            = $cal_recurring['date_to'];
						$report['date_from_nextsend'] = 0; // need to be 0, will recalculate when schedule send
						$report['date_to_nextsend']   = 0; // need to be 0, will recalculate when schedule send
						$report['schedule_nextsend']  = $cal_recurring['date_send'];
						$report['completed']          = $cal_recurring['date_send']; // to fix continue sending
					}
				}
			}

			if ( isset( $_POST['pro-report-schedule-send-email'] ) ) {
				$report['schedule_send_email'] = trim( $_POST['pro-report-schedule-send-email'] );
			}

			$report['schedule_bcc_me'] = isset( $_POST['pro-report-schedule-send-email-bcc-me'] ) ? 1 : 0;
			$creport_dir               = MainWP_Pro_Reports_Template::get_instance()->get_mainwp_sub_dir( 'report-attached' );

			if ( isset( $_POST['pro-report-heading'] ) ) {
				$report['heading'] = trim( $_POST['pro-report-heading'] );
			}

			if ( isset( $_POST['pro-report-intro'] ) ) {
				$report['intro'] = trim( $_POST['pro-report-intro'] );
			}

			if ( isset( $_POST['pro-report-outro'] ) ) {
				$report['outro'] = trim( $_POST['pro-report-outro'] );
			}

			if ( isset( $_POST['pro-report-text-color'] ) ) {
				$settings ['text_color'] = sanitize_text_field( $_POST['pro-report-text-color'] );
			}

			if ( isset( $_POST['pro-report-accent-color'] ) ) {
				$settings['accent_color'] = sanitize_text_field( $_POST['pro-report-accent-color'] );
			}

			if ( isset( $_POST['pro-report-background-color'] ) ) {
				$settings['background_color'] = sanitize_text_field( $_POST['pro-report-background-color'] );
			}

			if ( isset( $_POST['pro-report-first-page-background-color'] ) ) {
				$settings['first_page_background_color'] = sanitize_text_field( $_POST['pro-report-first-page-background-color'] );
			}
			if ( isset( $_POST['pro-report-link-background-color'] ) ) {
				$settings['link_color'] = sanitize_text_field( $_POST['pro-report-link-background-color'] );
			}
			if ( isset( $_POST['pro-report-table-background-color'] ) ) {
				$settings['table_background_color'] = sanitize_text_field( $_POST['pro-report-table-background-color'] );
			}
			if ( isset( $_POST['pro-report-table-header-color'] ) ) {
				$settings['table_header_color'] = sanitize_text_field( $_POST['pro-report-table-header-color'] );
			}
			if ( isset( $_POST['pro-report-table-header-text-color'] ) ) {
				$settings['table_header_text_color'] = sanitize_text_field( $_POST['pro-report-table-header-text-color'] );
			}
			if ( isset( $_POST['pro-report-table-border-color'] ) ) {
				$settings['table_border_color'] = sanitize_text_field( $_POST['pro-report-table-border-color'] );
			}

			if ( isset( $_POST['mainwp-summary-heading'] ) ) {
				$settings['mainwp_summary_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-summary-heading'] ) );
			}

			if ( isset( $_POST['mainwp-updates-heading'] ) ) {
				$settings['mainwp_updates_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-updates-heading'] ) );
			}

			if ( isset( $_POST['mainwp-posts-heading'] ) ) {
				$settings['mainwp_posts_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-posts-heading'] ) );
			}

			if ( isset( $_POST['mainwp-pages-heading'] ) ) {
				$settings['mainwp_pages_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-pages-heading'] ) );
			}

			if ( isset( $_POST['mainwp-users-heading'] ) ) {
				$settings['mainwp_users_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-users-heading'] ) );
			}

			if ( isset( $_POST['mainwp-comments-heading'] ) ) {
				$settings['mainwp_comments_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-comments-heading'] ) );
			}

			if ( isset( $_POST['mainwp-security-heading'] ) ) {
				$settings['mainwp_security_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-security-heading'] ) );
			}

			if ( isset( $_POST['mainwp-backups-heading'] ) ) {
				$settings['mainwp_backups_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-backups-heading'] ) );
			}

			if ( isset( $_POST['mainwp-analytics-heading'] ) ) {
				$settings['mainwp_analytics_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-analytics-heading'] ) );
			}
			if ( isset( $_POST['mainwp-fathom-analytics-heading'] ) ) {
				$settings['mainwp_fathom_analytics_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-fathom-analytics-heading'] ) );
			}
			if ( isset( $_POST['mainwp-matomo-analytics-heading'] ) ) {
				$settings['mainwp_matomo_analytics_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-matomo-analytics-heading'] ) );
			}
			if ( isset( $_POST['mainwp-uptime-heading'] ) ) {
				$settings['mainwp_uptime_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-uptime-heading'] ) );
			}

			if ( isset( $_POST['mainwp-performance-heading'] ) ) {
				$settings['mainwp_performance_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-performance-heading'] ) );
			}

			if ( isset( $_POST['mainwp-maintenance-heading'] ) ) {
				$settings['mainwp_maintenance_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-maintenance-heading'] ) );
			}

			if ( isset( $_POST['mainwp-atarim-heading'] ) ) {
				$settings['mainwp_atarim_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-atarim-heading'] ) );
			}

			if ( isset( $_POST['mainwp-general-heading'] ) ) {
				$settings['mainwp_general_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-general-heading'] ) );
			}

			if ( isset( $_POST['mainwp-domain-heading'] ) ) {
				$settings['mainwp_domain_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-domain-heading'] ) );
			}

			if ( isset( $_POST['mainwp-ssl-heading'] ) ) {
				$settings['mainwp_ssl_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-heading'] ) );
			}

			if ( isset( $_POST['mainwp-misc-heading'] ) ) {
				$settings['mainwp_misc_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-misc-heading'] ) );
			}

			if ( isset( $_POST['mainwp-vuln-heading'] ) ) {
				$settings['mainwp_vuln_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-vuln-heading'] ) );
			}

			if ( isset( $_POST['mainwp-wpupdates-heading'] ) ) {
				$settings['mainwp_wpupdates_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-wpupdates-heading'] ) );
			}

			if ( isset( $_POST['mainwp-pluginsupdates-heading'] ) ) {
				$settings['mainwp_pluginsupdates_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-pluginsupdates-heading'] ) );
			}

			if ( isset( $_POST['mainwp-themesupdates-heading'] ) ) {
				$settings['mainwp_themesupdates_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-themesupdates-heading'] ) );
			}

			if ( isset( $_POST['mainwp-newposts-heading'] ) ) {
				$settings['mainwp_newposts_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-newposts-heading'] ) );
			}

			if ( isset( $_POST['mainwp-updatedposts-heading'] ) ) {
				$settings['mainwp_updatedposts_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-updatedposts-heading'] ) );
			}

			if ( isset( $_POST['mainwp-deletedposts-heading'] ) ) {
				$settings['mainwp_deletedposts_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-deletedposts-heading'] ) );
			}

			if ( isset( $_POST['mainwp-newpages-heading'] ) ) {
				$settings['mainwp_newpages_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-newpages-heading'] ) );
			}

			if ( isset( $_POST['mainwp-updatedpages-heading'] ) ) {
				$settings['mainwp_updatedpages_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-updatedpages-heading'] ) );
			}

			if ( isset( $_POST['mainwp-deletedpages-heading'] ) ) {
				$settings['mainwp_deletedpages_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-deletedpages-heading'] ) );
			}

			if ( isset( $_POST['mainwp-newusers-heading'] ) ) {
				$settings['mainwp_newusers_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-newusers-heading'] ) );
			}

			if ( isset( $_POST['mainwp-updatedusers-heading'] ) ) {
				$settings['mainwp_updatedusers_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-updatedusers-heading'] ) );
			}

			if ( isset( $_POST['mainwp-deletedusers-heading'] ) ) {
				$settings['mainwp_deletedusers_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-deletedusers-heading'] ) );
			}

			if ( isset( $_POST['mainwp-newcomments-heading'] ) ) {
				$settings['mainwp_newcomments_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-newcomments-heading'] ) );
			}

			if ( isset( $_POST['mainwp-updatedcomments-heading'] ) ) {
				$settings['mainwp_updatedcomments_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-updatedcomments-heading'] ) );
			}

			if ( isset( $_POST['mainwp-deletedcomments-heading'] ) ) {
				$settings['mainwp_deletedcomments_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-deletedcomments-heading'] ) );
			}

			if ( isset( $_POST['mainwp-spamcomments-heading'] ) ) {
				$settings['mainwp_spamcomments_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-spamcomments-heading'] ) );
			}

			if ( isset( $_POST['mainwp-approvedcomments-heading'] ) ) {
				$settings['mainwp_approvedcomments_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-approvedcomments-heading'] ) );
			}

			if ( isset( $_POST['mainwp-sucuri-heading'] ) ) {
				$settings['mainwp_sucuri_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-sucuri-heading'] ) );
			}

			if ( isset( $_POST['mainwp-wordfence-heading'] ) ) {
				$settings['mainwp_wordfence_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-wordfence-heading'] ) );
			}

			if ( isset( $_POST['mainwp-ithemes-heading'] ) ) {
				$settings['mainwp_ithemes_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ithemes-heading'] ) );
			}

			if ( isset( $_POST['mainwp-ithemesbfp-heading'] ) ) {
				$settings['mainwp_ithemesbfp_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ithemesbfp-heading'] ) );
			}

			// Table Headers

			if ( isset( $_POST['mainwp-website-heading'] ) ) {
				$settings['mainwp_website_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-website-heading'] ) );
			}
			if ( isset( $_POST['mainwp-version-heading'] ) ) {
				$settings['mainwp_version_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-version-heading'] ) );
			}
			if ( isset( $_POST['mainwp-phpversion-heading'] ) ) {
				$settings['mainwp_phpversion_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-phpversion-heading'] ) );
			}
			if ( isset( $_POST['mainwp-oldversion-heading'] ) ) {
				$settings['mainwp_oldversion_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-oldversion-heading'] ) );
			}
			if ( isset( $_POST['mainwp-newversion-heading'] ) ) {
				$settings['mainwp_newversion_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-newversion-heading'] ) );
			}
			if ( isset( $_POST['mainwp-date-heading'] ) ) {
				$settings['mainwp_date_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-date-heading'] ) );
			}
			if ( isset( $_POST['mainwp-daterange-heading'] ) ) {
				$settings['mainwp_daterange_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-daterange-heading'] ) );
			}
			if ( isset( $_POST['mainwp-details-heading'] ) ) {
				$settings['mainwp_details_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-details-heading'] ) );
			}
			if ( isset( $_POST['mainwp-task-heading'] ) ) {
				$settings['mainwp_task_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-task-heading'] ) );
			}
			if ( isset( $_POST['mainwp-title-heading'] ) ) {
				$settings['mainwp_title_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-title-heading'] ) );
			}
			if ( isset( $_POST['mainwp-type-heading'] ) ) {
				$settings['mainwp_type_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-type-heading'] ) );
			}
			if ( isset( $_POST['mainwp-comment-heading'] ) ) {
				$settings['mainwp_comment_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-comment-heading'] ) );
			}
			if ( isset( $_POST['mainwp-role-heading'] ) ) {
				$settings['mainwp_role_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-role-heading'] ) );
			}
			if ( isset( $_POST['mainwp-user-heading'] ) ) {
				$settings['mainwp_user_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-user-heading'] ) );
			}
			if ( isset( $_POST['mainwp-theme-heading'] ) ) {
				$settings['mainwp_theme_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-theme-heading'] ) );
			}
			if ( isset( $_POST['mainwp-plugin-heading'] ) ) {
				$settings['mainwp_plugin_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-plugin-heading'] ) );
			}
			if ( isset( $_POST['mainwp-status-heading'] ) ) {
				$settings['mainwp_status_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-status-heading'] ) );
			}
			if ( isset( $_POST['mainwp-webtrust-heading'] ) ) {
				$settings['mainwp_webtrust_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-webtrust-heading'] ) );
			}
			if ( isset( $_POST['mainwp-period-heading'] ) ) {
				$settings['mainwp_period_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-period-heading'] ) );
			}
			if ( isset( $_POST['mainwp-performancescore-heading'] ) ) {
				$settings['mainwp_performancescore_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-performancescore-heading'] ) );
			}
			if ( isset( $_POST['mainwp-accessibilityscore-heading'] ) ) {
				$settings['mainwp_accessibilityscore_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-accessibilityscore-heading'] ) );
			}
			if ( isset( $_POST['mainwp-bestpracticesscore-heading'] ) ) {
				$settings['mainwp_bestpracticesscore_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-bestpracticesscore-heading'] ) );
			}
			if ( isset( $_POST['mainwp-seoscore-heading'] ) ) {
				$settings['mainwp_seoscore_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-seoscore-heading'] ) );
			}
			if ( isset( $_POST['mainwp-desktop-heading'] ) ) {
				$settings['mainwp_desktop_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-desktop-heading'] ) );
			}
			if ( isset( $_POST['mainwp-mobile-heading'] ) ) {
				$settings['mainwp_mobile_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-mobile-heading'] ) );
			}

			if ( isset( $_POST['mainwp-ssl-cname-heading'] ) ) {
				$settings['mainwp_ssl_cname_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-cname-heading'] ) );
			}
			if ( isset( $_POST['mainwp-ssl-issuer-heading'] ) ) {
				$settings['mainwp_ssl_issuer_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-issuer-heading'] ) );
			}
			if ( isset( $_POST['mainwp-ssl-valid-from-heading'] ) ) {
				$settings['mainwp_ssl_valid_from_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-valid-from-heading'] ) );
			}
			if ( isset( $_POST['mainwp-ssl-valid-to-heading'] ) ) {
				$settings['mainwp_ssl_valid_to_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-valid-to-heading'] ) );
			}
			if ( isset( $_POST['mainwp-ssl-expires-heading'] ) ) {
				$settings['mainwp_ssl_expires_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-expires-heading'] ) );
			}
			if ( isset( $_POST['mainwp-ssl-status-heading'] ) ) {
				$settings['mainwp_ssl_status_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-status-heading'] ) );
			}
			if ( isset( $_POST['mainwp-ssl-last-check-heading'] ) ) {
				$settings['mainwp_ssl_last_check_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-ssl-last-check-heading'] ) );
			}

			if ( isset( $_POST['mainwp-uptime7-heading'] ) ) {
				$settings['mainwp_uptime7_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-uptime7-heading'] ) );
			}
			if ( isset( $_POST['mainwp-overalluptime-heading'] ) ) {
				$settings['mainwp_overalluptime_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-overalluptime-heading'] ) );
			}
			if ( isset( $_POST['mainwp-uptime15-heading'] ) ) {
				$settings['mainwp_uptime15_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-uptime15-heading'] ) );
			}
			if ( isset( $_POST['mainwp-uptime30-heading'] ) ) {
				$settings['mainwp_uptime30_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-uptime30-heading'] ) );
			}
			if ( isset( $_POST['mainwp-uptime45-heading'] ) ) {
				$settings['mainwp_uptime45_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-uptime45-heading'] ) );
			}
			if ( isset( $_POST['mainwp-uptime60-heading'] ) ) {
				$settings['mainwp_uptime60_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-uptime60-heading'] ) );
			}
			if ( isset( $_POST['mainwp-websitevisits-heading'] ) ) {
				$settings['mainwp_websitevisits_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-websitevisits-heading'] ) );
			}
			if ( isset( $_POST['mainwp-pagevisits-heading'] ) ) {
				$settings['mainwp_pagevisits_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-pagevisits-heading'] ) );
			}
			if ( isset( $_POST['mainwp-pageviews-heading'] ) ) {
				$settings['mainwp_pageviews_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-pageviews-heading'] ) );
			}
			if ( isset( $_POST['mainwp-bouncerate-heading'] ) ) {
				$settings['mainwp_bouncerate_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-bouncerate-heading'] ) );
			}
			if ( isset( $_POST['mainwp-averagetime-heading'] ) ) {
				$settings['mainwp_averagetime_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-averagetime-heading'] ) );
			}
			if ( isset( $_POST['mainwp-newvisits-heading'] ) ) {
				$settings['mainwp_newvisits_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-newvisits-heading'] ) );
			}
			if ( isset( $_POST['mainwp-pluginsthreats-heading'] ) ) {
				$settings['mainwp_pluginsthreats_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-pluginsthreats-heading'] ) );
			}
			if ( isset( $_POST['mainwp-themesthreats-heading'] ) ) {
				$settings['mainwp_themesthreats_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-themesthreats-heading'] ) );
			}
			if ( isset( $_POST['mainwp-expirydate-heading'] ) ) {
				$settings['mainwp_expirydate_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-expirydate-heading'] ) );
			}
			if ( isset( $_POST['mainwp-expiresin-heading'] ) ) {
				$settings['mainwp_expiresin_heading'] = sanitize_text_field( wp_unslash( $_POST['mainwp-expiresin-heading'] ) );
			}

			$settings = apply_filters( 'mainwp_pro_reports_save_report_settings', $settings );

			$report['settings'] = wp_json_encode( $settings );

			$return                    = array();
			$report['logo_id']         = intval( $_POST['pro-report-logo'] );
			$report['header_image_id'] = intval( $_POST['pro-report-header-image'] );

			// attach files.
			$attach_files = 'NOTCHANGE';

			if ( isset( $_POST['pro-report-email-attachement-remove-files'] ) && '1' == $_POST['pro-report-email-attachement-remove-files'] ) {
				$attach_files = '';
				if ( ! empty( $current_attach_files ) ) {
					$this->delete_attach_files( $current_attach_files, $creport_dir );
				}
			}

			if ( isset( $_FILES['pro-report-email-attachements'] ) && ! empty( $_FILES['pro-report-email-attachements']['name'][0] ) ) {
				if ( ! empty( $current_attach_files ) ) {
					$this->delete_attach_files( $current_attach_files, $creport_dir );
				}

				$output = $this->handle_upload_files( $_FILES['pro-report-email-attachements'], $creport_dir );
				// print_r($output);
				if ( isset( $output['error'] ) ) {
					$return['error'] = $output['error'];
				}

				if ( is_array( $output ) && isset( $output['filenames'] ) && ! empty( $output['filenames'] ) ) {
					$attach_files = implode( ', ', $output['filenames'] );
				}
			}

			if ( 'NOTCHANGE' !== $attach_files ) {
				$report['attach_files'] = $attach_files;
			}

			// end /////

			$selected_websites = array();
			$selected_wpgroups = array();
			$selected_clients  = array();

			if ( isset( $_POST['select_by'] ) ) {
				if ( isset( $_POST['selected_sites'] ) && is_array( $_POST['selected_sites'] ) ) {
					foreach ( $_POST['selected_sites'] as $selected ) {
						$selected_websites[] = intval( $selected );
					}
				}

				if ( isset( $_POST['selected_groups'] ) && is_array( $_POST['selected_groups'] ) ) {
					foreach ( $_POST['selected_groups'] as $selected ) {
						$selected_wpgroups[] = intval( $selected );
					}
				}

				if ( isset( $_POST['selected_clients'] ) && is_array( $_POST['selected_clients'] ) ) {
					foreach ( $_POST['selected_clients'] as $selected ) {
						$selected_clients[] = intval( $selected );
					}
				}
			}

			$report['type'] = 1;

			$report['sites']   = ! empty( $selected_websites ) ? wp_json_encode( $selected_websites ) : '';
			$report['groups']  = ! empty( $selected_wpgroups ) ? wp_json_encode( $selected_wpgroups ) : '';
			$report['clients'] = ! empty( $selected_clients ) ? wp_json_encode( $selected_clients ) : '';

			if ( 'schedule' === $_POST['pro-report-action'] ) {
				$report['scheduled'] = 1;
			}

			if ( 'save' === $_POST['pro-report-action'] ||
					'send' === $_POST['pro-report-action'] ||
					'save_pdf' === $_POST['pro-report-action'] ||
					'schedule' === $_POST['pro-report-action'] ||
					'preview' === (string) $_POST['pro-report-action'] ||
					'send_test' === (string) $_POST['pro-report-action']
				) {

				if ( $result = MainWP_Pro_Reports_DB::get_instance()->update_report( $report ) ) {
					$return['id'] = $result->id;
					$messages[]   = esc_html__( 'The report has been successfully saved.', 'mainwp-pro-reports-extension' );
					MainWP_Pro_Reports_DB::get_instance()->delete_generated_report_content( $result->id ); // to clear reports generated content
				} else {
					$messages[] = esc_html__( ' The report was saved with no modifications made.', 'mainwp-pro-reports-extension' );
				}
					$return['saved'] = true;
			}

			if ( ! isset( $return['id'] ) && isset( $report['id'] ) ) {
				$return['id'] = $report['id'];
			}

			if ( count( $errors ) > 0 ) {
				$return['error'] = $errors;
			}

			if ( count( $messages ) > 0 ) {
				$return['message'] = $messages;
			}
				return $return;
		}

		return null;
	}


	public static function get_analytics_heading( $analytic, $settings = array() ) {

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$field = '';

		$default_heading = esc_html__( 'Analytics', 'mainwp-pro-reports-extension' );
		if ( 'google' == $analytic ) {
			$field           = 'mainwp_analytics_heading';
			$default_heading = esc_html__( 'Google Analytics', 'mainwp-pro-reports-extension' );
		} elseif ( 'fathom' == $analytic ) {
			$field           = 'mainwp_fathom_analytics_heading';
			$default_heading = esc_html__( 'Fathom Analytics', 'mainwp-pro-reports-extension' );
		} elseif ( 'matomo' == $analytic ) {
			$field           = 'mainwp_matomo_analytics_heading';
			$default_heading = esc_html__( 'Matomo Analytics', 'mainwp-pro-reports-extension' );
		}

		if ( ! empty( $field ) ) {
			return isset( $settings[ $field ] ) ? $settings[ $field ] : $default_heading;
		}
		return $default_heading;
	}


	public static function get_content_heading( $field, $settings, $def_value = '' ) {
		return is_array( $settings ) && isset( $settings[ $field ] ) ? $settings[ $field ] : $def_value;
	}

	public static function cal_days_in_month( $month, $year ) {
		if ( function_exists( 'cal_days_in_month' ) ) {
			$max_d = cal_days_in_month( CAL_GREGORIAN, $month, $year );
		} else {
			$max_d = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		}
		return $max_d;
	}

	public static function calc_recurring_date( $schedule, $recurring_day ) {

		if ( empty( $schedule ) ) {
			return false;
		}

		$the_time  = time(); // UTC time;
		$date_from = $date_to = $date_send = 0;

		if ( 'daily' == $schedule ) {
			$date_from = strtotime( date( 'Y-m-d', $the_time ) . ' 00:00:00' );
			$date_to   = strtotime( date( 'Y-m-d', $the_time ) . ' 23:59:59' );
			$date_send = $date_to + 2;
		} elseif ( 'weekly' == $schedule ) {
			// for strtotime()
			$day_of_week = array(
				1 => 'monday',
				2 => 'tuesday',
				3 => 'wednesday',
				4 => 'thursday',
				5 => 'friday',
				6 => 'saturday',
				7 => 'sunday',
			);

			$date_from = strtotime( date( 'Y-m-d', $the_time ) . ' 00:00:00' );
			$date_to   = $date_from + 7 * 24 * 3600 - 1;

			$date_send = strtotime( 'next ' . $day_of_week[ $recurring_day ] ) + 1;  // day of next week
			if ( $date_send < $date_to ) { // to fix
				$date_send += 7 * 24 * 3600;
			}
		} elseif ( 'monthly' == $schedule ) {
			$first_date = date( 'Y-m-01', $the_time ); // first day of the month
			$last_date  = date( 'Y-m-t', $the_time ); // Date t parameter return days number in current month.

			$date_from = strtotime( $first_date . ' 00:00:00' );
			$date_to   = strtotime( $last_date . ' 23:59:59' );

			$cal_month = date( 'm', $the_time ) + 1;
			$cal_year  = date( 'Y', $the_time );

			if ( $cal_month > 12 ) {
					$cal_month = $cal_month - 12;
					$cal_year += 1;
			}

			$max_d = self::cal_days_in_month( $cal_month, $cal_year );
			if ( $recurring_day > $max_d ) {
				$recurring_day = $max_d;
			}
			$date_send = mktime( 0, 0, 1, $cal_month, $recurring_day, $cal_year );
		}

		return array(
			'date_from' => $date_from,
			'date_to'   => $date_to,
			'date_send' => $date_send,
		);
	}


	public function delete_attach_files( $files, $dir ) {
		$files = explode( ',', $files );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$file      = trim( $file );
				$file_path = $dir . $file;
				if ( file_exists( $file_path ) ) {
					@unlink( $file_path );
				}
			}
		}
	}


	public function handle_upload_files( $file_input, $dest_dir ) {
		$output      = array();
		$attachFiles = array();

		$allowed_files = array( 'jpeg', 'jpg', 'gif', 'png', 'rar', 'zip', 'pdf' );

		$tmp_files = $file_input['tmp_name'];

		if ( is_array( $tmp_files ) ) {
			foreach ( $tmp_files as $i => $tmp_file ) {
				if ( ( UPLOAD_ERR_OK == $file_input['error'][ $i ] ) && is_uploaded_file( $tmp_file ) ) {
					$file_size = $file_input['size'][ $i ];
					$file_name = $file_input['name'][ $i ];
					$file_ext  = strtolower( end( explode( '.', $file_name ) ) );
					if ( ( $file_size > 5 * 1024 * 1024 ) ) {
						$output['error'][] = $file_name . ' - ' . esc_html__( 'File size too big' );
					} elseif ( ! in_array( $file_ext, $allowed_files ) ) {
						$output['error'][] = $file_name . ' - ' . esc_html__( 'File type are not allowed' );
					} else {
						$dest_file = $dest_dir . $file_name;
						$dest_file = dirname( $dest_file ) . '/' . wp_unique_filename( dirname( $dest_file ), basename( $dest_file ) );

						if ( move_uploaded_file( $tmp_file, $dest_file ) ) {
							$attachFiles[] = basename( $dest_file );
						} else {
							$output['error'][] = $file_name . ' - ' . esc_html__( 'Can not copy file' );
						}
					}
				}
			}
		}

		$output['filenames'] = $attachFiles;
		return $output;
	}

	public static function handle_upload_image( $file_input, $dest_dir, $max_height, $max_width = null ) {
		$output         = array();
		$processed_file = '';

		if ( UPLOAD_ERR_OK == $file_input['error'] ) {
			$tmp_file = $file_input['tmp_name'];

			if ( is_uploaded_file( $tmp_file ) ) {
				$file_size      = $file_input['size'];
				$file_type      = $file_input['type'];
				$file_name      = $file_input['name'];
				$file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

				if ( ( $file_size > 500 * 1025 ) ) { // 500KB
					$output['error'][] = 'File size is too large.';
				} elseif (
						( 'image/jpeg' != $file_type ) &&
						( 'image/jpg' != $file_type ) &&
						( 'image/gif' != $file_type ) &&
						( 'image/png' != $file_type )
				) {
					$output['error'][] = 'File Type is not allowed.';
				} elseif (
						( 'jpeg' != $file_extension ) &&
						( 'jpg' != $file_extension ) &&
						( 'gif' != $file_extension ) &&
						( 'png' != $file_extension )
				) {
					$output['error'][] = 'File Extension is not allowed.';
				} else {
					$dest_file = $dest_dir . $file_name;
					$dest_file = dirname( $dest_file ) . '/' . wp_unique_filename( dirname( $dest_file ), basename( $dest_file ) );

					if ( move_uploaded_file( $tmp_file, $dest_file ) ) {
						if ( file_exists( $dest_file ) ) {
							list( $width, $height, $type, $attr ) = getimagesize( $dest_file );
						}

						$resize = false;
						if ( $height > $max_height ) {
							$dst_height = $max_height;
							$dst_width  = $width * $max_height / $height;
							$resize     = true;
						}

						if ( $resize ) {
							$src          = $dest_file;
							$cropped_file = wp_crop_image( $src, 0, 0, $width, $height, $dst_width, $dst_height, false );
							if ( ! $cropped_file || is_wp_error( $cropped_file ) ) {
								$output['error'][] = esc_html__( 'Can not resize the image.' );
							} else {
								@unlink( $dest_file );
								$processed_file = basename( $cropped_file );
							}
						} else {
							$processed_file = basename( $dest_file );
						}
						$output['filename'] = $processed_file;
					} else {
						$output['error'][] = 'Can not copy the file.';
					}
				}
			}
		}

		return $output;
	}


	// Display custom tokens table
	public function get_pro_reports_custom_tokens() {
		$tokens = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
		?>
		<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-manage-tokens-info' ) ) : ?>
		<div class="ui blue message">
			<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-manage-tokens-info"></i>
			<?php echo esc_html__( 'These tokens will allow you to display data you have set in the Child Site edit screen. For each child site, go to the site Edit page to set the token values. Once values are set, you will easily display data for the selected sites in reports.', 'mainwp-pro-reports-extension' ); ?>
		</div>
		<?php endif; ?>
		<table id="mainwp-pro-reports-custom-tokens-table" class="ui striped compact table" style="width:100%">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Token Name', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Token Description', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( is_array( $tokens ) && count( $tokens ) > 0 ) : ?>
					<?php foreach ( (array) $tokens as $token ) : ?>
						<?php
						if ( ! $token ) {
							continue;}
						?>
						<tr class="mainwp-token" token-id="<?php echo $token->id; ?>">
							<td class="token-name">[<?php echo stripslashes( $token->token_name ); ?>]</td>
							<td class="token-description"><?php echo stripslashes( $token->token_description ); ?></td>
							<td>
								<div class="ui right pointing dropdown icon mini basic green button">
									<i class="ellipsis horizontal icon"></i>
									<div class="menu">
										<a class="item" id="mainwp-pro-reports-edit-custom-token" href="#"><?php esc_html_e( 'Edit', 'mainwp-pro-reports-extension' ); ?></a>
										<a class="item" id="mainwp-pro-reports-delete-custom-token" href="#"><?php esc_html_e( 'Delete', 'mainwp-pro-reports-extension' ); ?></a>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th><a class="ui mini green button" href="#" id="mainwp-pro-reports-new-custom-token-button"><?php esc_html_e( 'New Token', 'mainwp-pro-reports-extension' ); ?></a></th>
					<th><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>

		<script type="text/javascript">
			jQuery(function(){
				// Init datatables
				jQuery( '#mainwp-pro-reports-custom-tokens-table' ).DataTable( {
					"stateSave": true,
					"stateDuration": 0,
					"colReorder" : { columns: ":not(:last-child)" },
					"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
					"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
					"order": [ [ 0, "asc" ] ],
					"language": { "emptyTable": "No tokens found." },
					"drawCallback" : function( settings ) {
						jQuery( '#mainwp-pro-reports-custom-tokens-table .ui.dropdown').dropdown();
						mainwp_datatable_fix_menu_overflow('#mainwp-pro-reports-custom-tokens-table', -50 );
					},
				} ).on( 'columns-reordered', function ( e, settings, details ) {
					console.log('columns-reordered');
					setTimeout(() => {
						jQuery( '#mainwp-pro-reports-custom-tokens-table .ui.dropdown' ).dropdown();
						mainwp_datatable_fix_menu_overflow('#mainwp-pro-reports-custom-tokens-table', -50 );
					}, 1000);
				} );
				mainwp_datatable_fix_menu_overflow('#mainwp-pro-reports-custom-tokens-table', -50 );
			} );
		</script>

		<div class="ui modal" id="mainwp-pro-reports-new-custom-token-modal">
		<i class="close icon"></i>
			<div class="header"><?php echo esc_html__( 'Custom Token', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="content ui mini form">
				<div class="ui yellow message" style="display:none"></div>
				<div class="field">
					<label><?php esc_html_e( 'Token Name', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-name" name="token-name" placeholder="<?php esc_attr_e( 'Enter token name (without of square brackets)', 'mainwp-pro-reports-extension' ); ?>">
				</div>
				<div class="field">
					<label><?php esc_html_e( 'Token Description', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-description" name="token-description" placeholder="<?php esc_attr_e( 'Enter token description', 'mainwp-pro-reports-extension' ); ?>">
				</div>
			</div>
			<div class="actions">
				<input type="button" class="ui green button" id="mainwp-pro-reports-create-new-custom-token" value="<?php esc_attr_e( 'Save Token', 'mainwp-pro-reports-extension' ); ?>">
			</div>
		</div>

		<div class="ui small modal" id="mainwp-pro-reports-update-token-modal">
		<i class="close icon"></i>
			<div class="header"><?php echo esc_html__( 'Custom Token', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="content ui mini form">
				<div class="ui yellow message" style="display:none"></div>
				<div class="field">
					<label><?php esc_html_e( 'Token Name', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-name" name="token-name" placeholder="<?php esc_attr_e( 'Enter token name (without of square brackets)', 'mainwp-pro-reports-extension' ); ?>">
				</div>
				<div class="field">
					<label><?php esc_html_e( 'Token Description', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-description" name="token-description" placeholder="<?php esc_attr_e( 'Enter token description', 'mainwp-pro-reports-extension' ); ?>">
				</div>
				<input type="hidden" value="" id="token-id" name="token-id">
			</div>
			<div class="actions">
				<input type="button"  class="ui green button" id="mainwp-save-pro-reports-custom-token" value="<?php esc_attr_e( 'Save Token', 'mainwp-pro-reports-extension' ); ?>">
			</div>
		</div>
		<?php
	}


	// Report options
	public function new_report_setting( $report = null ) {
		$scheduled_report  = false;
		$recurringSchedule = '';

		if ( ! empty( $report ) ) {
			if ( ! empty( $report->scheduled ) ) {
				$scheduled_report = true;
			}
			$recurringSchedule = $report->recurring_schedule;
		}
		?>
		<div class="ui form <?php echo $recurringSchedule; ?>" id="mainwp-pro-report-settings">
		<?php $this->get_pro_report_options( $report ); ?>
		</div>
		<div class="ui form">
		<?php $this->get_pro_report_customization_options( $report ); ?>
		</div>
		<div class="ui form">
		<?php $this->get_pro_report_email_options( $report ); ?>
		</div>
		<?php
	}

	public function get_pro_report_options( $report = null ) {
		$title               = '';
		$from                = '';
		$to                  = '';
		$logo                = '';
		$recurring_schedule  = '';
		$recurring_date      = '';
		$recurring_month     = '';
		$recurring_day       = '';
		$current_template    = '';
		$schedule_send_email = 'email_auto';
		$schedule_bcc_me     = 0;
		$scheduled_report    = false;
		$send_on_style       = $send_on_day_of_week_style = $send_on_day_of_mon_style = $send_on_month_style = $monthly_style = 'style="display:none"';
		$messages            = array();
		$pdf_filename        = '';

		$recurring_types = array(
			'daily'   => esc_html__( 'Daily', 'mainwp-pro-reports-extension' ),
			'weekly'  => esc_html__( 'Weekly', 'mainwp-pro-reports-extension' ),
			'monthly' => esc_html__( 'Monthly', 'mainwp-pro-reports-extension' ),
		);

		$day_of_week = array(
			1 => esc_html__( 'Monday', 'mainwp-pro-reports-extension' ),
			2 => esc_html__( 'Tuesday', 'mainwp-pro-reports-extension' ),
			3 => esc_html__( 'Wednesday', 'mainwp-pro-reports-extension' ),
			4 => esc_html__( 'Thursday', 'mainwp-pro-reports-extension' ),
			5 => esc_html__( 'Friday', 'mainwp-pro-reports-extension' ),
			6 => esc_html__( 'Saturday', 'mainwp-pro-reports-extension' ),
			7 => esc_html__( 'Sunday', 'mainwp-pro-reports-extension' ),
		);

		if ( ! empty( $report ) ) {
			$title               = $report->title;
			$from                = ! empty( $report->date_from ) ? date( 'Y-m-d', $report->date_from ) : '';
			$to                  = ! empty( $report->date_to ) ? date( 'Y-m-d', $report->date_to ) : '';
			$recurring_schedule  = $report->recurring_schedule;
			$recurring_day       = $report->recurring_day;
			$schedule_send_email = $report->schedule_send_email;
			$schedule_bcc_me     = isset( $report->schedule_bcc_me ) ? $report->schedule_bcc_me : 0;
			$scheduled_report    = isset( $report->scheduled ) && ! empty( $report->scheduled ) ? true : false;
			$current_template    = $report->template;

			if ( $scheduled_report && ( $recurring_schedule == 'weekly' || $recurring_schedule == 'monthly' ) ) {
				$send_on_style = '';
				if ( $recurring_schedule == 'weekly' ) {
					$send_on_day_of_week_style = '';
				} elseif ( $recurring_schedule == 'monthly' ) {
					$send_on_day_of_mon_style = $monthly_style = '';
					$recurring_date           = $recurring_day;
				}
			}

			$pdf_filename = $report->custom_pdf_file_name;

		}

		if ( $scheduled_report && ! empty( $recurring_schedule ) ) {
			$messages[] = esc_html__( 'The report is now successfully scheduled for delivery.', 'mainwp-pro-reports-extension' );
		}

		?>

		<h3 class="ui dividing header"><?php echo esc_html__( 'Report Settings', 'mainwp-pro-reports-extension' ); ?></h3>
		<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-create-report-general-settings' ) ) : ?>
		<div class="ui blue message">
			<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-create-report-general-settings"></i>
			<?php echo esc_html__( 'Create Reports page consists of two major sections: the Report Content & Design Customization section, in which you customize the PDF that is attached to the email, and the Email Settings section, in which you customize the actual email that gets sent to your clients.', 'mainwp-pro-reports-extension' ); ?>
		</div>
		<?php endif; ?>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Report title', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter your report title. It is for internal use only.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-title" id="pro-report-title" placeholder="Required field" value="<?php echo esc_attr( stripslashes( $title ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Report type', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'If you need to send this report only once, select the "One-time" option. If you want to set automated reports, select "Recurring".', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name='pro-report-type' id="pro-report-type" class="ui dropdown">
					<option value="0" <?php echo ! $scheduled_report ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'One-time', 'mainwp-pro-reports-extension' ); ?></option>
					<option value="1" <?php echo $scheduled_report ? 'selected="selected"' : ''; ?>><?php esc_html_e( 'Recurring', 'mainwp-pro-reports-extension' ); ?></option>
				</select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_schedule_selection_wrap">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Schedule', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Do you want to run Daily, Weekly or Monthly reports?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name='pro-report-schedule' id="pro-report-schedule-select" class="ui dropdown">
					<option value=""><?php esc_html_e( 'Off', 'mainwp-pro-reports-extension' ); ?></option>
					<?php
					foreach ( $recurring_types as $value => $title ) {
						$_select = '';
						if ( $recurring_schedule == $value ) {
							$_select = 'selected';
						}
						echo '<option value="' . $value . '" ' . $_select . '>' . $title . '</option>';
					}
					?>
				</select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_send_on_day_of_week_wrap">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Send report on', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'When do you want to send the report?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name='pro-report-schedule-day' id="pro-report-schedule-day" class="ui dropdown">
					<?php
					foreach ( $day_of_week as $value => $title ) {
						$_select = '';
						if ( $recurring_day == $value ) {
							$_select = 'selected';
						}
						echo '<option value="' . $value . '" ' . $_select . '>' . $title . '</option>';
					}
					?>
				</select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_send_on_day_of_month_wrap">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Send on', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'When do you want to send the report?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name="pro-report-schedule-month-day" id="pro-report-schedule-month-day" class="ui dropdown">
				<?php
				$day_suffix = array(
					1 => 'st',
					2 => 'nd',
					3 => 'rd',
				);
				for ( $x = 1; $x <= 31; $x++ ) {
					$_select = '';
					if ( $recurring_date == $x ) {
						$_select = 'selected';
					}
					$remain = $x % 10;
					$day_sf = isset( $day_suffix[ $remain ] ) ? $day_suffix[ $remain ] : 'th';
					echo '<option value="' . $x . '" ' . $_select . '>' . $x . $day_sf . ' of the month</option>';
				}
				?>
					</select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_date_range_wrap">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Report date range', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Select the date range for the report?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="ui calendar">
					<div class="ui input left icon">
						<i class="calendar icon"></i>
						<input type="text" placeholder="From (yyyy-m-d)" name="pro-report-from-date" id="pro-report-from-date" value="<?php echo $from; ?>"/>
					</div>
				</div>
			</div>
			<div class="three wide column">
				<div class="ui calendar">
					<div class="ui input left icon">
						<i class="calendar icon"></i>
						<input type="text" placeholder="To (yyyy-m-d)" name="pro-report-to-date" id="pro-report-to-date" value="<?php echo $to; ?>" />
					</div>
				</div>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_additional_options_wrap">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Additional options', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column">
				<div class="ui radio checkbox" data-tooltip="<?php esc_attr_e( 'If selected, report will be sent to you for a review. Once you make sure that report is ok, you will need to send it to your client.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<input type="radio" name="pro-report-schedule-send-email" value="email_review" id="pro-report-schedule-send-email-me-review" <?php echo ( 'email_review' == $schedule_send_email ) ? 'checked' : ''; ?>/><label for="pro-report-schedule-send-email-me-review"><?php esc_html_e( 'Email me when report is complete so I can review', 'mainwp-pro-reports-extension' ); ?></label>
				</div>
				<br />
				<div class="ui radio checkbox" data-tooltip="<?php esc_attr_e( 'If selected, report will be sent to your client directly.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<input type="radio" name="pro-report-schedule-send-email" value="email_auto" id="pro-report-schedule-send-email-auto" <?php echo ( 'email_auto' == $schedule_send_email ) ? 'checked' : ''; ?>/><label for="pro-report-schedule-send-email-auto"><?php esc_html_e( 'Automatically email my client the report', 'mainwp-pro-reports-extension' ); ?></label>
				</div>
				<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="ui checkbox" data-tooltip="<?php esc_attr_e( 'If selected, report will be sent to your client and you.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<input type="checkbox" name="pro-report-schedule-send-email-bcc-me" value="1" id="pro-report-schedule-send-email-bcc-me" <?php echo $schedule_bcc_me ? 'checked' : ''; ?> /><label for="pro-report-schedule-send-email-bcc-me"><?php esc_html_e( 'BCC me on report email', 'mainwp-pro-reports-extension' ); ?></label>
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Report PDF filename', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column parent-tokens-modal" input-id="pro-report-custom-pdf-file-name" data-tooltip="<?php esc_attr_e( 'Enter your custom PDF filename. If left blank, MainWP will use generic filename.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="ui right aligned secondary segment" style="padding:0px">
					<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
				</div>
				<input type="text" name="pro-report-custom-pdf-file-name" id="pro-report-custom-pdf-file-name" placeholder="<?php esc_attr_e( 'Enter a custom PDF filename (optional)', 'mainwp-pro-reports-extension' ); ?>" value="<?php echo esc_attr( stripslashes( $pdf_filename ) ); ?>" />
			</div>
		</div>

		<script type="text/javascript">
			jQuery( document ).ready(function(){
				mainwp_pro_reports_date_selection_init();
			});
		</script>

		<div class="ui hidden divider"></div>

		<h3 class="ui dividing header"><?php echo esc_html__( 'Report Template Selection', 'mainwp-pro-reports-extension' ); ?></h3>

		<?php
		$extraHeaders = array(
			'TemplateName'  => 'Template Name',
			'Description'   => 'Description',
			'Version'       => 'Version',
			'Author'        => 'Author',
			'ScreenshotURI' => 'Screenshot URI',
		);
		$tempHeaders  = array();

		$temp_files = MainWP_Pro_Reports_Template::get_instance()->get_template_files();
		foreach ( $temp_files as $template => $file_name ) {
			$path                     = MainWP_Pro_Reports_Template::get_instance()->get_template_file_path( $template );
			$tempHeaders[ $template ] = get_file_data( $path, $extraHeaders );
		}

		?>

		<div class="ui grid field">
			<label class="four wide column middle aligned"></label>
			<div class="twelve wide column">
				<div class="ui info message" id="mainwp-pro-reports-template-selection-info">
					<p><?php echo esc_html__( 'Select one of the available report templates. If needed, you can create a new custom template from scratch or copy and edit one of the existing templates.', 'mainwp-pro-reports-extension' ); ?></p>
					<p><?php echo esc_html__( 'To create a new template, download the copy of the extension from the My Account area and copy one of the default template located in the ../plugins/mainwp-pro-reports-extension/templates/reports/ folder of the extension and copy it to the ../wp-content/uploads/mainwp/report-templates/ directory and rename the file. Once copied, you can use your favorite code editor to edit it.', 'mainwp-pro-reports-extension' ); ?></p>
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo esc_html__( 'Report template', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="four wide column" data-tooltip="<?php esc_attr_e( 'Select one of the available report templates.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name="pro-report-template" id="pro-report-template" class="ui dropdown fluid not-auto-init" >
				<?php
				foreach ( $temp_files as $file => $template ) {
					$_select = '';
					if ( $current_template == $file ) {
						$_select = 'selected';
					}
					echo '<option value="' . $file . '" ' . $_select . ' tab-value="' . str_replace( '/', '-', $file ) . '">' . $template . '</option>';
				}
				?>
				</select>
			</div>
			<div class="four wide middle aligned column" id="reports-tab-template-details">
				<a href="javascript:void(0);"  class="mainwp-pro-reports-templ-more-detail ui button"><?php esc_html_e( 'See Template Info', 'mainwp-pro-reports-extension' ); ?></a>
				<?php foreach ( $tempHeaders as $file => $header ) : ?>
				<div class="ui tab secondary segment mainwp-report-template-info <?php echo ( $file == $current_template ) ? 'active' : ''; ?>" data-tab="<?php echo esc_html( str_replace( '/', '-', $file ) ); ?>">
						<div class="mainwp-pro-reports-templ-more-info" style="display: none">
							<div class="ui grid">
								<?php if ( isset( $header['ScreenshotURI'] ) ) : ?>
								<div class="ten wide column">
										<a href="<?php echo WP_CONTENT_URL . '/' . esc_html( $header['ScreenshotURI'] ); ?>" target="_blank">
										<img src="<?php echo WP_CONTENT_URL . '/' . esc_html( $header['ScreenshotURI'] ); ?>" alt="Screenshot URI" style="width:100%">
										</a>
									</div>
								<?php endif; ?>
							<div class="six wide column">
									<p><strong><?php esc_html_e( 'Template name:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['TemplateName'] ) ? esc_html( $header['TemplateName'] ) : ''; ?></p>
									<p><strong><?php esc_html_e( 'Description:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['Description'] ) ? esc_html( $header['Description'] ) : ''; ?></p>
								<p><strong><?php esc_html_e( 'Template author:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['Author'] ) ? esc_html( $header['Author'] ) : ''; ?></p>
								<p><strong><?php esc_html_e( 'Template version:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['Version'] ) ? esc_html( $header['Version'] ) : ''; ?></p>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
				<div class="ui modal mainwp-pro-reports-templ-info-modal">
				<i class="close icon"></i>
					<div class="header"><?php esc_html_e( 'Report Template Details', 'mainwp-pro-reports-extension' ); ?></div>
					<div class="ui content"></div>
					<div class="actions">
			</div>
		</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery( document ).ready( function ($) {
				jQuery('#pro-report-template').dropdown({
						onChange: function( val, text, $choice ) {
							var tab = $('#pro-report-template').find(":selected").attr('tab-value');
							$('#reports-tab-template-details').tab('change tab', tab);
						}
					}
				);
				if (mainwpParams.use_wp_datepicker == 1) {
					jQuery( '#mainwp-pro-reports-report-tab .ui.calendar input[type=text]' ).datepicker( { dateFormat: "yy-mm-dd" } );
				} else {
					$('#mainwp-pro-reports-report-tab .ui.calendar').calendar({
							type: 'date',
							monthFirst: false,
							formatter: {
								date: function ( date ) {
									if (!date) return '';
									var day = date.getDate();
									var month = date.getMonth() + 1;
									var year = date.getFullYear();

									if (month < 10) {
										month = '0' + month;
									}
									if (day < 10) {
										day = '0' + day;
									}
									return year + '-' + month + '-' + day;
								}
							}
					});
				}
				$(document).on("click", '.mainwp-pro-reports-templ-more-detail', function(e) {
					$('.mainwp-pro-reports-templ-info-modal').find('.ui.content').html( $(this).parent( '#reports-tab-template-details' ).find('.mainwp-report-template-info.active').find('.mainwp-pro-reports-templ-more-info').html());
					$('.mainwp-pro-reports-templ-info-modal').modal({
						onHide: function(){
							$(this).find('.ui.content').html('');
						}
					}).modal( 'show' );
				});
			});
		</script>
		<?php
	}


	public function get_pro_report_customization_options( $report = null ) {

		$heading = esc_html__( 'Website Care Report', 'mainwp-pro-reports-extension' );
		$intro   = esc_html__( 'Thank you for trusting us with your website. In this report, you can find a summary of your website condition and maintenance services provided in the period of [report.daterange].', 'mainwp-pro-reports-extension' );
		$outro   = esc_html__( 'If you have any further questions, please feel free to contact us.', 'mainwp-pro-reports-extension' );

		$mainwp_analytics_heading        = self::get_analytics_heading( 'google' );
		$mainwp_fathom_analytics_heading = self::get_analytics_heading( 'fathom' );
		$mainwp_matomo_analytics_heading = self::get_analytics_heading( 'matomo' );

		// Custom headings.
		$mainwp_summary_heading          = esc_html__( 'Website Care Summary', 'mainwp-pro-reports-extension' );
		$mainwp_updates_heading          = esc_html__( 'Performed Updates', 'mainwp-pro-reports-extension' );
		$mainwp_posts_heading            = esc_html__( 'Posts Management', 'mainwp-pro-reports-extension' );
		$mainwp_pages_heading            = esc_html__( 'Pages Management', 'mainwp-pro-reports-extension' );
		$mainwp_users_heading            = esc_html__( 'Users Management', 'mainwp-pro-reports-extension' );
		$mainwp_comments_heading         = esc_html__( 'Comments Management', 'mainwp-pro-reports-extension' );
		$mainwp_security_heading         = esc_html__( 'Security', 'mainwp-pro-reports-extension' );
		$mainwp_backups_heading          = esc_html__( 'Backups', 'mainwp-pro-reports-extension' );
		$mainwp_analytics_heading        = esc_html__( 'Analytics', 'mainwp-pro-reports-extension' );
		$mainwp_uptime_heading           = esc_html__( 'Uptime Monitoring', 'mainwp-pro-reports-extension' );
		$mainwp_performance_heading      = esc_html__( 'Website Performance', 'mainwp-pro-reports-extension' );
		$mainwp_maintenance_heading      = esc_html__( 'Optimization & Maintenance', 'mainwp-pro-reports-extension' );
		$mainwp_atarim_heading           = esc_html__( 'Atarim Tasks', 'mainwp-pro-reports-extension' );
		$mainwp_general_heading          = esc_html__( 'General Information', 'mainwp-pro-reports-extension' );
		$mainwp_domain_heading           = esc_html__( 'Domain Status', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_heading              = esc_html__( 'SSL Certificate Status', 'mainwp-pro-reports-extension' );
		$mainwp_misc_heading             = esc_html__( 'Misc Information', 'mainwp-pro-reports-extension' );
		$mainwp_vuln_heading             = esc_html__( 'Vulnerabilities', 'mainwp-pro-reports-extension' );
		$mainwp_wpupdates_heading        = esc_html__( 'WordPress Updates', 'mainwp-pro-reports-extension' );
		$mainwp_pluginsupdates_heading   = esc_html__( 'Plugins Updates', 'mainwp-pro-reports-extension' );
		$mainwp_themesupdates_heading    = esc_html__( 'Themes Updates', 'mainwp-pro-reports-extension' );
		$mainwp_newposts_heading         = esc_html__( 'New Posts', 'mainwp-pro-reports-extension' );
		$mainwp_updatedposts_heading     = esc_html__( 'Updated Posts', 'mainwp-pro-reports-extension' );
		$mainwp_deletedposts_heading     = esc_html__( 'Deleted Posts', 'mainwp-pro-reports-extension' );
		$mainwp_newpages_heading         = esc_html__( 'New Pages', 'mainwp-pro-reports-extension' );
		$mainwp_updatedpages_heading     = esc_html__( 'Updated Pages', 'mainwp-pro-reports-extension' );
		$mainwp_deletedpages_heading     = esc_html__( 'Deleted Pages', 'mainwp-pro-reports-extension' );
		$mainwp_newusers_heading         = esc_html__( 'New Users', 'mainwp-pro-reports-extension' );
		$mainwp_updatedusers_heading     = esc_html__( 'Updated Users', 'mainwp-pro-reports-extension' );
		$mainwp_deletedusers_heading     = esc_html__( 'Deleted Users', 'mainwp-pro-reports-extension' );
		$mainwp_newcomments_heading      = esc_html__( 'New Comments', 'mainwp-pro-reports-extension' );
		$mainwp_updatedcomments_heading  = esc_html__( 'Updated Comments', 'mainwp-pro-reports-extension' );
		$mainwp_deletedcomments_heading  = esc_html__( 'Deleted Comments', 'mainwp-pro-reports-extension' );
		$mainwp_spamcomments_heading     = esc_html__( 'Spam Comments', 'mainwp-pro-reports-extension' );
		$mainwp_approvedcomments_heading = esc_html__( 'Approved Comments', 'mainwp-pro-reports-extension' );
		$mainwp_sucuri_heading           = esc_html__( 'Sucuri Scans', 'mainwp-pro-reports-extension' );
		$mainwp_wordfence_heading        = esc_html__( 'Wordfence Security Scans', 'mainwp-pro-reports-extension' );
		$mainwp_ithemes_heading          = esc_html__( 'iThemes Security Scans', 'mainwp-pro-reports-extension' );
		$mainwp_ithemesbfp_heading       = esc_html__( 'Brute Force Protection', 'mainwp-pro-reports-extension' );

		// Table Headers.
		$mainwp_website_heading            = esc_html__( 'Website', 'mainwp-pro-reports-extension' );
		$mainwp_version_heading            = esc_html__( 'Version', 'mainwp-pro-reports-extension' );
		$mainwp_phpversion_heading         = esc_html__( 'PHP Version', 'mainwp-pro-reports-extension' );
		$mainwp_oldversion_heading         = esc_html__( 'Old Version', 'mainwp-pro-reports-extension' );
		$mainwp_newversion_heading         = esc_html__( 'New Version', 'mainwp-pro-reports-extension' );
		$mainwp_date_heading               = esc_html__( 'Date', 'mainwp-pro-reports-extension' );
		$mainwp_daterange_heading          = esc_html__( 'Date Range', 'mainwp-pro-reports-extension' );
		$mainwp_details_heading            = esc_html__( 'Details', 'mainwp-pro-reports-extension' );
		$mainwp_task_heading               = esc_html__( 'Task', 'mainwp-pro-reports-extension' );
		$mainwp_title_heading              = esc_html__( 'Title', 'mainwp-pro-reports-extension' );
		$mainwp_type_heading               = esc_html__( 'Type', 'mainwp-pro-reports-extension' );
		$mainwp_comment_heading            = esc_html__( 'Comment', 'mainwp-pro-reports-extension' );
		$mainwp_role_heading               = esc_html__( 'Role', 'mainwp-pro-reports-extension' );
		$mainwp_user_heading               = esc_html__( 'User', 'mainwp-pro-reports-extension' );
		$mainwp_theme_heading              = esc_html__( 'Theme', 'mainwp-pro-reports-extension' );
		$mainwp_plugin_heading             = esc_html__( 'Plugin', 'mainwp-pro-reports-extension' );
		$mainwp_status_heading             = esc_html__( 'Status', 'mainwp-pro-reports-extension' );
		$mainwp_webtrust_heading           = esc_html__( 'Webtrust', 'mainwp-pro-reports-extension' );
		$mainwp_period_heading             = esc_html__( 'Period', 'mainwp-pro-reports-extension' );
		$mainwp_performancescore_heading   = esc_html__( 'Performance Score', 'mainwp-pro-reports-extension' );
		$mainwp_accessibilityscore_heading = esc_html__( 'Accessibility Score', 'mainwp-pro-reports-extension' );
		$mainwp_bestpracticesscore_heading = esc_html__( 'Best Practices Score', 'mainwp-pro-reports-extension' );
		$mainwp_seoscore_heading           = esc_html__( 'SEO Score', 'mainwp-pro-reports-extension' );
		$mainwp_desktop_heading            = esc_html__( 'Desktop', 'mainwp-pro-reports-extension' );
		$mainwp_mobile_heading             = esc_html__( 'Mobile', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_cname_heading          = esc_html__( 'CNAME', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_issuer_heading         = esc_html__( 'Certificate Issuer', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_valid_from_heading     = esc_html__( 'Valid From', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_valid_to_heading       = esc_html__( 'Valid To', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_expires_heading        = esc_html__( 'Expires', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_status_heading         = esc_html__( 'Certificate Status', 'mainwp-pro-reports-extension' );
		$mainwp_ssl_last_check_heading     = esc_html__( 'Checked On', 'mainwp-pro-reports-extension' );
		$mainwp_overalluptime_heading      = esc_html__( 'Overall Uptime', 'mainwp-pro-reports-extension' );
		$mainwp_uptime7_heading            = esc_html__( 'Last 7 Days', 'mainwp-pro-reports-extension' );
		$mainwp_uptime15_heading           = esc_html__( 'Last 15 Days', 'mainwp-pro-reports-extension' );
		$mainwp_uptime30_heading           = esc_html__( 'Last 30 Days', 'mainwp-pro-reports-extension' );
		$mainwp_uptime45_heading           = esc_html__( 'Last 45 Days', 'mainwp-pro-reports-extension' );
		$mainwp_uptime60_heading           = esc_html__( 'Last 60 Days', 'mainwp-pro-reports-extension' );
		$mainwp_websitevisits_heading      = esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' );
		$mainwp_pagevisits_heading         = esc_html__( 'Page Visits', 'mainwp-pro-reports-extension' );
		$mainwp_pageviews_heading          = esc_html__( 'Page Views', 'mainwp-pro-reports-extension' );
		$mainwp_bouncerate_heading         = esc_html__( 'Bounce Rate', 'mainwp-pro-reports-extension' );
		$mainwp_averagetime_heading        = esc_html__( 'Average Time', 'mainwp-pro-reports-extension' );
		$mainwp_newvisits_heading          = esc_html__( 'New Visits', 'mainwp-pro-reports-extension' );
		$mainwp_pluginsthreats_heading     = esc_html__( 'Plugins Threats', 'mainwp-pro-reports-extension' );
		$mainwp_themesthreats_heading      = esc_html__( 'Themes Threats', 'mainwp-pro-reports-extension' );
		$mainwp_expirydate_heading         = esc_html__( 'Expiry Date', 'mainwp-pro-reports-extension' );
		$mainwp_expiresin_heading          = esc_html__( 'Expires In', 'mainwp-pro-reports-extension' );

		$logo         = $header_image = '';
		$logo_id      = $header_image_id = 0;
		$bg_color     = '#ffffff';
		$text_color   = '#666666';
		$accent_color = '#18A4E0';

		$first_page_bg_color     = '#1c1d1b';
		$link_color              = '#18A4E0';
		$table_background_color  = '#fafafa';
		$table_header_color      = '#dddddd';
		$table_header_text_color = '#333333';
		$table_border_color      = '#eeeeee';

		$showhide_sections = array();

		$enable_media     = apply_filters( 'mainwp_pro_reports_enable_media_editor', false );
		$enable_quicktags = apply_filters( 'mainwp_pro_reports_enable_quicktags_editor', false );
		$standard_editor  = apply_filters( 'mainwp_pro_reports_enable_standard_editor', false );

		$settings = array();

		if ( ! empty( $report ) ) {

			$logo_id = $report->logo_id;
			if ( $logo_id ) {
				$logo = MainWP_Pro_Reports_Utility::get_attachment_url( $report->logo_id, false );
			}

			$header_image_id = $report->header_image_id;
			if ( $header_image_id ) {
				$header_image = MainWP_Pro_Reports_Utility::get_attachment_url( $report->header_image_id, false );
			}

			$heading = $report->heading;
			$intro   = $report->intro;
			$outro   = $report->outro;

			$settings = $report->settings;

			$mainwp_analytics_heading        = self::get_analytics_heading( 'google', $settings );
			$mainwp_fathom_analytics_heading = self::get_analytics_heading( 'fathom', $settings );
			$mainwp_matomo_analytics_heading = self::get_analytics_heading( 'matomo', $settings );

			if ( ! empty( $settings ) && is_array( $settings ) ) {
				$bg_color                = isset( $settings['background_color'] ) ? $settings['background_color'] : '';
				$text_color              = isset( $settings['text_color'] ) ? $settings['text_color'] : '';
				$accent_color            = isset( $settings['accent_color'] ) ? $settings['accent_color'] : '';
				$first_page_bg_color     = isset( $settings['first_page_background_color'] ) ? $settings['first_page_background_color'] : '';
				$link_color              = isset( $settings['link_color'] ) ? $settings['link_color'] : '';
				$table_background_color  = isset( $settings['table_background_color'] ) ? $settings['table_background_color'] : '';
				$table_header_color      = isset( $settings['table_header_color'] ) ? $settings['table_header_color'] : '';
				$table_header_text_color = isset( $settings['table_header_text_color'] ) ? $settings['table_header_text_color'] : '';
				$table_border_color      = isset( $settings['table_border_color'] ) ? $settings['table_border_color'] : '';

				$mainwp_summary_heading          = isset( $settings['mainwp_summary_heading'] ) ? $settings['mainwp_summary_heading'] : esc_html__( 'Website Care Summary', 'mainwp-pro-reports-extension' );
				$mainwp_updates_heading          = isset( $settings['mainwp_updates_heading'] ) ? $settings['mainwp_updates_heading'] : esc_html__( 'Updates', 'mainwp-pro-reports-extension' );
				$mainwp_posts_heading            = isset( $settings['mainwp_posts_heading'] ) ? $settings['mainwp_posts_heading'] : esc_html__( 'Posts Management', 'mainwp-pro-reports-extension' );
				$mainwp_pages_heading            = isset( $settings['mainwp_pages_heading'] ) ? $settings['mainwp_pages_heading'] : esc_html__( 'Pages Management', 'mainwp-pro-reports-extension' );
				$mainwp_users_heading            = isset( $settings['mainwp_users_heading'] ) ? $settings['mainwp_users_heading'] : esc_html__( 'Users Management', 'mainwp-pro-reports-extension' );
				$mainwp_comments_heading         = isset( $settings['mainwp_comments_heading'] ) ? $settings['mainwp_comments_heading'] : esc_html__( 'Comments Management', 'mainwp-pro-reports-extension' );
				$mainwp_security_heading         = isset( $settings['mainwp_security_heading'] ) ? $settings['mainwp_security_heading'] : esc_html__( 'Security', 'mainwp-pro-reports-extension' );
				$mainwp_backups_heading          = isset( $settings['mainwp_backups_heading'] ) ? $settings['mainwp_backups_heading'] : esc_html__( 'Backups', 'mainwp-pro-reports-extension' );
				$mainwp_uptime_heading           = isset( $settings['mainwp_uptime_heading'] ) ? $settings['mainwp_uptime_heading'] : esc_html__( 'Uptime Monitoring', 'mainwp-pro-reports-extension' );
				$mainwp_performance_heading      = isset( $settings['mainwp_performance_heading'] ) ? $settings['mainwp_performance_heading'] : esc_html__( 'Website Performance', 'mainwp-pro-reports-extension' );
				$mainwp_maintenance_heading      = isset( $settings['mainwp_maintenance_heading'] ) ? $settings['mainwp_maintenance_heading'] : esc_html__( 'Optimization & Maintenance', 'mainwp-pro-reports-extension' );
				$mainwp_atarim_heading           = isset( $settings['mainwp_atarim_heading'] ) ? $settings['mainwp_atarim_heading'] : esc_html__( 'Atarim Tasks', 'mainwp-pro-reports-extension' );
				$mainwp_general_heading          = isset( $settings['mainwp_general_heading'] ) ? $settings['mainwp_general_heading'] : esc_html__( 'General Information', 'mainwp-pro-reports-extension' );
				$mainwp_domain_heading           = isset( $settings['mainwp_domain_heading'] ) ? $settings['mainwp_domain_heading'] : esc_html__( 'Domain Status', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_heading              = isset( $settings['mainwp_ssl_heading'] ) ? $settings['mainwp_ssl_heading'] : esc_html__( 'SSL Certificate Status', 'mainwp-pro-reports-extension' );
				$mainwp_misc_heading             = isset( $settings['mainwp_misc_heading'] ) ? $settings['mainwp_misc_heading'] : esc_html__( 'Misc Information', 'mainwp-pro-reports-extension' );
				$mainwp_vuln_heading             = isset( $settings['mainwp_vuln_heading'] ) ? $settings['mainwp_vuln_heading'] : esc_html__( 'Vulnerabilities', 'mainwp-pro-reports-extension' );
				$mainwp_wpupdates_heading        = isset( $settings['mainwp_wpupdates_heading'] ) ? $settings['mainwp_wpupdates_heading'] : esc_html__( 'WordPress Updates', 'mainwp-pro-reports-extension' );
				$mainwp_pluginsupdates_heading   = isset( $settings['mainwp_pluginsupdates_heading'] ) ? $settings['mainwp_pluginsupdates_heading'] : esc_html__( 'Plugins Updates', 'mainwp-pro-reports-extension' );
				$mainwp_themesupdates_heading    = isset( $settings['mainwp_themesupdates_heading'] ) ? $settings['mainwp_themesupdates_heading'] : esc_html__( 'Themes Updates', 'mainwp-pro-reports-extension' );
				$mainwp_newposts_heading         = isset( $settings['mainwp_newposts_heading'] ) ? $settings['mainwp_newposts_heading'] : esc_html__( 'New Posts', 'mainwp-pro-reports-extension' );
				$mainwp_updatedposts_heading     = isset( $settings['mainwp_updatedposts_heading'] ) ? $settings['mainwp_updatedposts_heading'] : esc_html__( 'Updated Posts', 'mainwp-pro-reports-extension' );
				$mainwp_deletedposts_heading     = isset( $settings['mainwp_deletedposts_heading'] ) ? $settings['mainwp_deletedposts_heading'] : esc_html__( 'Deleted Posts', 'mainwp-pro-reports-extension' );
				$mainwp_newpages_heading         = isset( $settings['mainwp_newpages_heading'] ) ? $settings['mainwp_newpages_heading'] : esc_html__( 'New Pages', 'mainwp-pro-reports-extension' );
				$mainwp_updatedpages_heading     = isset( $settings['mainwp_updatedpages_heading'] ) ? $settings['mainwp_updatedpages_heading'] : esc_html__( 'Updated Pages', 'mainwp-pro-reports-extension' );
				$mainwp_deletedpages_heading     = isset( $settings['mainwp_deletedpages_heading'] ) ? $settings['mainwp_deletedpages_heading'] : esc_html__( 'Deleted Pages', 'mainwp-pro-reports-extension' );
				$mainwp_newusers_heading         = isset( $settings['mainwp_newusers_heading'] ) ? $settings['mainwp_newusers_heading'] : esc_html__( 'New Users', 'mainwp-pro-reports-extension' );
				$mainwp_updatedusers_heading     = isset( $settings['mainwp_updatedusers_heading'] ) ? $settings['mainwp_updatedusers_heading'] : esc_html__( 'Updated Users', 'mainwp-pro-reports-extension' );
				$mainwp_deletedusers_heading     = isset( $settings['mainwp_deletedusers_heading'] ) ? $settings['mainwp_deletedusers_heading'] : esc_html__( 'Deleted Users', 'mainwp-pro-reports-extension' );
				$mainwp_newcomments_heading      = isset( $settings['mainwp_newcomments_heading'] ) ? $settings['mainwp_newcomments_heading'] : esc_html__( 'New Comments', 'mainwp-pro-reports-extension' );
				$mainwp_updatedcomments_heading  = isset( $settings['mainwp_updatedcomments_heading'] ) ? $settings['mainwp_updatedcomments_heading'] : esc_html__( 'Updated Comments', 'mainwp-pro-reports-extension' );
				$mainwp_deletedcomments_heading  = isset( $settings['mainwp_deletedcomments_heading'] ) ? $settings['mainwp_deletedcomments_heading'] : esc_html__( 'Deleted Comments', 'mainwp-pro-reports-extension' );
				$mainwp_spamcomments_heading     = isset( $settings['mainwp_spamcomments_heading'] ) ? $settings['mainwp_spamcomments_heading'] : esc_html__( 'Spam Comments', 'mainwp-pro-reports-extension' );
				$mainwp_approvedcomments_heading = isset( $settings['mainwp_approvedcomments_heading'] ) ? $settings['mainwp_approvedcomments_heading'] : esc_html__( 'Approved Comments', 'mainwp-pro-reports-extension' );
				$mainwp_sucuri_heading           = isset( $settings['mainwp_sucuri_heading'] ) ? $settings['mainwp_sucuri_heading'] : esc_html__( 'Sucuri Scans', 'mainwp-pro-reports-extension' );
				$mainwp_wordfence_heading        = isset( $settings['mainwp_wordfence_heading'] ) ? $settings['mainwp_wordfence_heading'] : esc_html__( 'Wordfence Security Scans', 'mainwp-pro-reports-extension' );
				$mainwp_ithemes_heading          = isset( $settings['mainwp_ithemes_heading'] ) ? $settings['mainwp_ithemes_heading'] : esc_html__( 'iThemes Security Scans', 'mainwp-pro-reports-extension' );
				$mainwp_ithemesbfp_heading       = isset( $settings['mainwp_ithemesbfp_heading'] ) ? $settings['mainwp_ithemesbfp_heading'] : esc_html__( 'Brute Force Protection', 'mainwp-pro-reports-extension' );

				// Table Headers.
				$mainwp_website_heading            = isset( $settings['mainwp_website_heading'] ) ? $settings['mainwp_website_heading'] : esc_html__( 'Website', 'mainwp-pro-reports-extension' );
				$mainwp_version_heading            = isset( $settings['mainwp_version_heading'] ) ? $settings['mainwp_version_heading'] : esc_html__( 'Version', 'mainwp-pro-reports-extension' );
				$mainwp_phpversion_heading         = isset( $settings['mainwp_phpversion_heading'] ) ? $settings['mainwp_phpversion_heading'] : esc_html__( 'PHP Version', 'mainwp-pro-reports-extension' );
				$mainwp_oldversion_heading         = isset( $settings['mainwp_oldversion_heading'] ) ? $settings['mainwp_oldversion_heading'] : esc_html__( 'Old Version', 'mainwp-pro-reports-extension' );
				$mainwp_newversion_heading         = isset( $settings['mainwp_newversion_heading'] ) ? $settings['mainwp_newversion_heading'] : esc_html__( 'New Version', 'mainwp-pro-reports-extension' );
				$mainwp_date_heading               = isset( $settings['mainwp_date_heading'] ) ? $settings['mainwp_date_heading'] : esc_html__( 'Date', 'mainwp-pro-reports-extension' );
				$mainwp_daterange_heading          = isset( $settings['mainwp_daterange_heading'] ) ? $settings['mainwp_daterange_heading'] : esc_html__( 'Date Range', 'mainwp-pro-reports-extension' );
				$mainwp_details_heading            = isset( $settings['mainwp_details_heading'] ) ? $settings['mainwp_details_heading'] : esc_html__( 'Details', 'mainwp-pro-reports-extension' );
				$mainwp_task_heading               = isset( $settings['mainwp_task_heading'] ) ? $settings['mainwp_task_heading'] : esc_html__( 'Task', 'mainwp-pro-reports-extension' );
				$mainwp_title_heading              = isset( $settings['mainwp_title_heading'] ) ? $settings['mainwp_title_heading'] : esc_html__( 'Title', 'mainwp-pro-reports-extension' );
				$mainwp_type_heading               = isset( $settings['mainwp_type_heading'] ) ? $settings['mainwp_type_heading'] : esc_html__( 'Type', 'mainwp-pro-reports-extension' );
				$mainwp_comment_heading            = isset( $settings['mainwp_comment_heading'] ) ? $settings['mainwp_comment_heading'] : esc_html__( 'Comment', 'mainwp-pro-reports-extension' );
				$mainwp_role_heading               = isset( $settings['mainwp_role_heading'] ) ? $settings['mainwp_role_heading'] : esc_html__( 'Role', 'mainwp-pro-reports-extension' );
				$mainwp_user_heading               = isset( $settings['mainwp_user_heading'] ) ? $settings['mainwp_user_heading'] : esc_html__( 'User', 'mainwp-pro-reports-extension' );
				$mainwp_theme_heading              = isset( $settings['mainwp_theme_heading'] ) ? $settings['mainwp_theme_heading'] : esc_html__( 'Theme', 'mainwp-pro-reports-extension' );
				$mainwp_plugin_heading             = isset( $settings['mainwp_plugin_heading'] ) ? $settings['mainwp_plugin_heading'] : esc_html__( 'Plugin', 'mainwp-pro-reports-extension' );
				$mainwp_status_heading             = isset( $settings['mainwp_status_heading'] ) ? $settings['mainwp_status_heading'] : esc_html__( 'Status', 'mainwp-pro-reports-extension' );
				$mainwp_webtrust_heading           = isset( $settings['mainwp_webtrust_heading'] ) ? $settings['mainwp_webtrust_heading'] : esc_html__( 'Webtrust', 'mainwp-pro-reports-extension' );
				$mainwp_period_heading             = isset( $settings['mainwp_period_heading'] ) ? $settings['mainwp_period_heading'] : esc_html__( 'Period', 'mainwp-pro-reports-extension' );
				$mainwp_performancescore_heading   = isset( $settings['mainwp_performancescore_heading'] ) ? $settings['mainwp_performancescore_heading'] : esc_html__( 'Performance Score', 'mainwp-pro-reports-extension' );
				$mainwp_accessibilityscore_heading = isset( $settings['mainwp_accessibilityscore_heading'] ) ? $settings['mainwp_accessibilityscore_heading'] : esc_html__( 'Accessibility Score', 'mainwp-pro-reports-extension' );
				$mainwp_bestpracticesscore_heading = isset( $settings['mainwp_bestpracticesscore_heading'] ) ? $settings['mainwp_bestpracticesscore_heading'] : esc_html__( 'Best Practices Score', 'mainwp-pro-reports-extension' );
				$mainwp_seoscore_heading           = isset( $settings['mainwp_seoscore_heading'] ) ? $settings['mainwp_seoscore_heading'] : esc_html__( 'SEO Score', 'mainwp-pro-reports-extension' );
				$mainwp_desktop_heading            = isset( $settings['mainwp_desktop_heading'] ) ? $settings['mainwp_desktop_heading'] : esc_html__( 'Desktop', 'mainwp-pro-reports-extension' );
				$mainwp_mobile_heading             = isset( $settings['mainwp_mobile_heading'] ) ? $settings['mainwp_mobile_heading'] : esc_html__( 'Mobile', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_cname_heading          = isset( $settings['mainwp_ssl_cname_heading'] ) ? $settings['mainwp_ssl_cname_heading'] : esc_html__( 'CNAME', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_issuer_heading         = isset( $settings['mainwp_ssl_issuer_heading'] ) ? $settings['mainwp_ssl_issuer_heading'] : esc_html__( 'Certificate Issuer', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_valid_from_heading     = isset( $settings['mainwp_ssl_valid_from_heading'] ) ? $settings['mainwp_ssl_valid_from_heading'] : esc_html__( 'Valid From', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_valid_to_heading       = isset( $settings['mainwp_ssl_valid_to_heading'] ) ? $settings['mainwp_ssl_valid_to_heading'] : esc_html__( 'Valid To', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_expires_heading        = isset( $settings['mainwp_ssl_expires_heading'] ) ? $settings['mainwp_ssl_expires_heading'] : esc_html__( 'Expires', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_status_heading         = isset( $settings['mainwp_ssl_status_heading'] ) ? $settings['mainwp_ssl_status_heading'] : esc_html__( 'Certificate Status', 'mainwp-pro-reports-extension' );
				$mainwp_ssl_last_check_heading     = isset( $settings['mainwp_ssl_last_check_heading'] ) ? $settings['mainwp_ssl_last_check_heading'] : esc_html__( 'Checked On', 'mainwp-pro-reports-extension' );
				$mainwp_overalluptime_heading      = isset( $settings['mainwp_overalluptime_heading'] ) ? $settings['mainwp_overalluptime_heading'] : esc_html__( 'Overall Uptime', 'mainwp-pro-reports-extension' );
				$mainwp_uptime7_heading            = isset( $settings['mainwp_uptime7_heading'] ) ? $settings['mainwp_uptime7_heading'] : esc_html__( 'Last 7 Days', 'mainwp-pro-reports-extension' );
				$mainwp_uptime15_heading           = isset( $settings['mainwp_uptime15_heading'] ) ? $settings['mainwp_uptime15_heading'] : esc_html__( 'Last 15 Days', 'mainwp-pro-reports-extension' );
				$mainwp_uptime30_heading           = isset( $settings['mainwp_uptime30_heading'] ) ? $settings['mainwp_uptime30_heading'] : esc_html__( 'Last 30 Days', 'mainwp-pro-reports-extension' );
				$mainwp_uptime45_heading           = isset( $settings['mainwp_uptime45_heading'] ) ? $settings['mainwp_uptime45_heading'] : esc_html__( 'Last 45 Days', 'mainwp-pro-reports-extension' );
				$mainwp_uptime60_heading           = isset( $settings['mainwp_uptime60_heading'] ) ? $settings['mainwp_uptime60_heading'] : esc_html__( 'Last 60 Days', 'mainwp-pro-reports-extension' );
				$mainwp_websitevisits_heading      = isset( $settings['mainwp_websitevisits_heading'] ) ? $settings['mainwp_websitevisits_heading'] : esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' );
				$mainwp_pagevisits_heading         = isset( $settings['mainwp_pagevisits_heading'] ) ? $settings['mainwp_pagevisits_heading'] : esc_html__( 'Page Visits', 'mainwp-pro-reports-extension' );
				$mainwp_pageviews_heading          = isset( $settings['mainwp_pageviews_heading'] ) ? $settings['mainwp_pageviews_heading'] : esc_html__( 'Page Views', 'mainwp-pro-reports-extension' );
				$mainwp_bouncerate_heading         = isset( $settings['mainwp_bouncerate_heading'] ) ? $settings['mainwp_bouncerate_heading'] : esc_html__( 'Bounce Rate', 'mainwp-pro-reports-extension' );
				$mainwp_averagetime_heading        = isset( $settings['mainwp_averagetime_heading'] ) ? $settings['mainwp_averagetime_heading'] : esc_html__( 'Average Time', 'mainwp-pro-reports-extension' );
				$mainwp_newvisits_heading          = isset( $settings['mainwp_newvisits_heading'] ) ? $settings['mainwp_newvisits_heading'] : esc_html__( 'New Visits', 'mainwp-pro-reports-extension' );
				$mainwp_pluginsthreats_heading     = isset( $settings['mainwp_pluginsthreats_heading'] ) ? $settings['mainwp_pluginsthreats_heading'] : esc_html__( 'Plugins Threats', 'mainwp-pro-reports-extension' );
				$mainwp_themesthreats_heading      = isset( $settings['mainwp_themesthreats_heading'] ) ? $settings['mainwp_themesthreats_heading'] : esc_html__( 'Themes Threats', 'mainwp-pro-reports-extension' );
				$mainwp_expirydate_heading         = isset( $settings['mainwp_expirydate_heading'] ) ? $settings['mainwp_expirydate_heading'] : esc_html__( 'Expiry Date', 'mainwp-pro-reports-extension' );
				$mainwp_expiresin_heading          = isset( $settings['mainwp_expiresin_heading'] ) ? $settings['mainwp_expiresin_heading'] : esc_html__( 'Expires In', 'mainwp-pro-reports-extension' );

			} else {
				// to compatible with old colors settings.
				$bg_color     = $report->background_color;
				$text_color   = $report->text_color;
				$accent_color = $report->accent_color;
			}

			if ( ! empty( $report->showhide_sections ) ) {
				$showhide_sections = json_decode( $report->showhide_sections, 1 );
			}
		}

		if ( ! is_array( $showhide_sections ) ) {
			$showhide_sections = array();
		}

		?>
		<div class="ui hidden divider"></div>

		<h3 class="ui dividing header"><?php echo esc_html__( 'Report Content & Design Customization', 'mainwp-pro-reports-extension' ); ?></h3>

		<div id="mainwp-pro-reports-customizations-menu" class="ui inverted fluid four item large pointing menu">
			<a class="active item" data-tab="content">
				<i class="edit icon"></i>
				<?php echo esc_html__( 'Custom Content', 'mainwp-pro-reports-extension' ); ?>
			</a>
			<a class="item" data-tab="sections">
				<i class="check square outline icon"></i>
				<?php echo esc_html__( 'Report Data', 'mainwp-pro-reports-extension' ); ?>
			</a>
			<a class="item" data-tab="images">
				<i class="image icon"></i>
				<?php echo esc_html__( 'Custom Branding', 'mainwp-pro-reports-extension' ); ?>
			</a>
			<a class="item" data-tab="headings">
				<i class="heading icon"></i>
				<?php echo esc_html__( 'Custom Titles', 'mainwp-pro-reports-extension' ); ?>
			</a>
		</div>


		<div class="ui active tab secondary segment" data-tab="content" style="margin-top: -13px;">
			<div class="ui grid field">
				<label class="four wide column"><?php echo esc_html__( 'Report heading', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="twelve wide column parent-tokens-modal" input-id="pro-report-heading" data-tooltip="<?php esc_attr_e( 'Enter the report heading. It will be displayed in the top of the first page of the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="bottom left" data-inverted="">
					<div class="ui right aligned secondary segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
					<input type="text" name="pro-report-heading" id="pro-report-heading" placeholder="<?php echo esc_html__( 'Website Care Report', 'mainwp-pro-reports-extension' ); ?>" value="<?php echo esc_attr( stripslashes( $heading ) ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column"><?php echo esc_html__( 'Report introduction message', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="twelve wide column parent-tokens-modal" editor-id="pro-report-intro" data-tooltip="<?php esc_attr_e( 'Optionally, you can add introduction content here.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<div class="ui right aligned secondary segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
						<?php
						remove_editor_styles(); // stop custom theme styling interfering with the editor
						wp_editor(
							stripslashes( $intro ),
							'pro-report-intro',
							array(
								'textarea_name' => 'pro-report-intro',
								'textarea_rows' => 10,
								'teeny'         => $standard_editor ? false : true,
								'media_buttons' => $enable_media ? true : false,
								'quicktags'     => $enable_quicktags ? true : false,
							)
						);
						?>
					</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column"><?php echo esc_html__( 'Report closing message', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="twelve wide column">
					<div class="parent-tokens-modal" editor-id="pro-report-outro" data-tooltip="<?php esc_attr_e( 'Optionally, you can add closing content here.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
						<div class="ui right aligned secondary segment" style="padding:0px">
							<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
						</div>
						<?php
						wp_editor(
							stripslashes( $outro ),
							'pro-report-outro',
							array(
								'textarea_name' => 'pro-report-outro',
								'textarea_rows' => 10,
								'teeny'         => $standard_editor ? false : true,
								'media_buttons' => $enable_media ? true : false,
								'quicktags'     => $enable_quicktags ? true : false,
							)
						);
						?>
				</div>
			</div>
		</div>
		</div>
		<?php
		$default_val = 0;
		?>
		<div class="ui tab secondary segment" data-tab="sections" style="margin-top: -13px;">
			<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-report-data-settings' ) ) : ?>
			<div class="ui blue message">
				<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-report-data-settings"></i>
				<div class="ui list">
					<div class="item"><?php echo esc_html__( 'If selected "Show," the extension will always show the section in the report even if there is no recorded data for this section.', 'mainwp-pro-reports-extension' ); ?></div>
					<div class="item"><?php echo esc_html__( 'If selected "Hide," the section will always be hidden in reports regardless.', 'mainwp-pro-reports-extension' ); ?></div>
					<div class="item"><?php echo esc_html__( 'If selected "Hide if empty," the section will be included only in case there is data to be displayed.', 'mainwp-pro-reports-extension' ); ?></div>
				</div>
			</div>
			<?php endif; ?>

			<div class="ui two columns grid">
			<div class="column">
			<!-- SHOW -->
			<?php $wp_up_val = isset( $showhide_sections['wp-update'] ) ? intval( $showhide_sections['wp-update'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'WordPress updates', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[wp-update]">
						<option value="0" <?php echo $wp_up_val == 0 ? 'selected="selected"' : ''; ?> ><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of WP updates > 0 in selected time range -->
						<option value="1" <?php echo $wp_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $wp_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php $plugins_up_val = isset( $showhide_sections['plugins-updates'] ) ? intval( $showhide_sections['plugins-updates'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Plugins updates', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[plugins-updates]">
						<option value="0" <?php echo $plugins_up_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Plugin updates > 0 in selected time range -->
						<option value="1" <?php echo $plugins_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $plugins_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php $themes_up_val = isset( $showhide_sections['themes-updates'] ) ? intval( $showhide_sections['themes-updates'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Themes updates', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[themes-updates]">
						<option value="0" <?php echo $themes_up_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
						<option value="1" <?php echo $themes_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $themes_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
					<?php $posts_up_val = isset( $showhide_sections['posts-updates'] ) ? intval( $showhide_sections['posts-updates'] ) : $default_val; ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Posts updates', 'mainwp-pro-reports-extension' ); ?></label>
					<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<select class="ui dropdown" name="pro-report-showhide-sections[posts-updates]">
								<option value="0" <?php echo $posts_up_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
								<option value="1" <?php echo $posts_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
								<option value="2" <?php echo $posts_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
							</select>
						</div>
					</div>
					<?php $pages_up_val = isset( $showhide_sections['pages-updates'] ) ? intval( $showhide_sections['pages-updates'] ) : $default_val; ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Pages updates', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<select class="ui dropdown" name="pro-report-showhide-sections[pages-updates]">
								<option value="0" <?php echo $pages_up_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
								<option value="1" <?php echo $pages_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
								<option value="2" <?php echo $pages_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
							</select>
						</div>
					</div>
					<?php $users_val = isset( $showhide_sections['users'] ) ? intval( $showhide_sections['users'] ) : $default_val; ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Users updates', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<select class="ui dropdown" name="pro-report-showhide-sections[users]">
								<option value="0" <?php echo $users_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
								<option value="1" <?php echo $users_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
								<option value="2" <?php echo $users_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
							</select>
						</div>
					</div>
			<!-- ### -->
			<!-- SHOW ONLY IF DM INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-comments-extension/mainwp-comments-extension.php' ) ) { ?>
				<?php $comments_val = isset( $showhide_sections['comments'] ) ? intval( $showhide_sections['comments'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Comments updates', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[comments]">
						<option value="0" <?php echo $comments_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if monitor is created for the site and uptimeratio is not empty or N/A -->
						<option value="1" <?php echo $comments_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $comments_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
				</div>
				<div class="column">
			<!-- SHOW ONLY IF AUM INSTALLED -->
			<?php if ( is_plugin_active( 'advanced-uptime-monitor-extension/advanced-uptime-monitor-extension.php' ) ) { ?>
				<?php $uptime_val = isset( $showhide_sections['uptime'] ) ? intval( $showhide_sections['uptime'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Uptime monitoring', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[uptime]">
						<option value="0" <?php echo $uptime_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if monitor is created for the site and uptimeratio is not empty or N/A -->
						<option value="1" <?php echo $uptime_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $uptime_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF DM INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-domain-monitor-extension/mainwp-domain-monitor-extension.php' ) ) { ?>
				<?php $domain_val = isset( $showhide_sections['domain'] ) ? intval( $showhide_sections['domain'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Domain monitoring', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[domain]">
						<option value="0" <?php echo $domain_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if monitor is created for the site and uptimeratio is not empty or N/A -->
						<option value="1" <?php echo $domain_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $domain_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF DM INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-ssl-monitor-extension/mainwp-ssl-monitor-extension.php' ) ) { ?>
				<?php $ssl_val = isset( $showhide_sections['ssl'] ) ? intval( $showhide_sections['ssl'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'SSL monitoring', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[ssl]">
						<option value="0" <?php echo $ssl_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if monitor is created for the site and uptimeratio is not empty or N/A -->
						<option value="1" <?php echo $ssl_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $ssl_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF SUCURI INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-sucuri-extension/mainwp-sucuri-extension.php' ) ) { ?>
				<?php $security_val = isset( $showhide_sections['security'] ) ? intval( $showhide_sections['security'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Sucuri', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[security]">
						<option value="0" <?php echo $security_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="1" <?php echo $security_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $security_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF Wordfence INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-wordfence-extension/mainwp-wordfence-extension.php' ) ) { ?>
				<?php $wordfence_val = isset( $showhide_sections['wordfence'] ) ? intval( $showhide_sections['wordfence'] ) : $default_val; ?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo esc_html__( 'Wordfence', 'mainwp-pro-reports-extension' ); ?></label>
					<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[wordfence]">
						<option value="0" <?php echo $wordfence_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- Show if number of scans is > 0 -->
						<option value="1" <?php echo $wordfence_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $wordfence_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF JP Protect INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-ithemes-security-extension/mainwp-ithemes-security-extension.php' ) ) { ?>
				<?php $itsecurity_val = isset( $showhide_sections['itsecurity'] ) ? intval( $showhide_sections['itsecurity'] ) : $default_val; ?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo esc_html__( 'iThemes Security', 'mainwp-pro-reports-extension' ); ?></label>
					<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[itsecurity]">
						<option value="0" <?php echo $itsecurity_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- Show if number of scans is > 0 -->
						<option value="1" <?php echo $itsecurity_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $itsecurity_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF JP Protect INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-jetpack-protect-extension/mainwp-jetpack-protect-extension.php' ) ) { ?>
				<?php $protect_val = isset( $showhide_sections['protect'] ) ? intval( $showhide_sections['protect'] ) : $default_val; ?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo esc_html__( 'Jetpack Protect', 'mainwp-pro-reports-extension' ); ?></label>
					<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[protect]">
						<option value="0" <?php echo $protect_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- Show if number of scans is > 0 -->
						<option value="1" <?php echo $protect_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $protect_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF ANY Suported BAckup Extension INSTALLED -->
			<?php
			if ( is_plugin_active( 'mainwp-backwpup-extension/mainwp-backwpup-extension.php' )
						|| is_plugin_active( 'mainwp-backupwordpress-extension/mainwp-backupwordpress-extension.php' )
						|| is_plugin_active( 'mainwp-buddy-extension/mainwp-buddy-extension.php' )
						|| is_plugin_active( 'mainwp-updraftplus-extension/mainwp-updraftplus-extension.php' )
						|| is_plugin_active( 'mainwp-timecapsule-extension/mainwp-timecapsule-extension.php' )
						|| is_plugin_active( 'wpvivid-backup-mainwp/wpvivid-backup-mainwp.php' )
			) {
				?>
				<?php $backups_val = isset( $showhide_sections['backups'] ) ? intval( $showhide_sections['backups'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Backups', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[backups]">
						<option value="0" <?php echo $backups_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
						<option value="1" <?php echo $backups_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $backups_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF GA INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-google-analytics-extension/mainwp-google-analytics-extension.php' ) ) { ?>
				<?php $ga_val = isset( $showhide_sections['ga'] ) ? intval( $showhide_sections['ga'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Google Analytics', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[ga]">
						<option value="0" <?php echo $ga_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any GA data from the site -->
						<option value="1" <?php echo $ga_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $ga_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF PIWIK INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-piwik-extension/mainwp-piwik-extension.php' ) ) { ?>
				<?php $matomo_val = isset( $showhide_sections['matomo'] ) ? intval( $showhide_sections['matomo'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Matomo Analytics', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[matomo]">
						<option value="0" <?php echo $matomo_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any PIWIK data from the site -->
						<option value="1" <?php echo $matomo_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $matomo_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF PageSPeed INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-page-speed-extension/mainwp-page-speed-extension.php' ) ) { ?>
				<?php $pagespeed_val = isset( $showhide_sections['pagespeed'] ) ? intval( $showhide_sections['pagespeed'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'PageSpeed', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[pagespeed]">
						<option value="0" <?php echo $pagespeed_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any PS data for the site -->
						<option value="1" <?php echo $pagespeed_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $pagespeed_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF Lighthouse INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-lighthouse-extension/mainwp-lighthouse-extension.php' ) ) { ?>
				<?php $lighthouse_val = isset( $showhide_sections['lighthouse'] ) ? intval( $showhide_sections['lighthouse'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Lighthouse', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[lighthouse]">
						<option value="0" <?php echo $lighthouse_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any LH data for the site -->
						<option value="1" <?php echo $lighthouse_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $lighthouse_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF ATARIM INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-atarim-extension/mainwp-atarim-extension.php' ) ) { ?>
				<?php $atarim_val = isset( $showhide_sections['atarim'] ) ? intval( $showhide_sections['atarim'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Atarim', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[atarim]">
						<option value="0" <?php echo $atarim_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any Atarim data for the site -->
						<option value="1" <?php echo $atarim_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $atarim_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF Maintenance INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-maintenance-extension/mainwp-maintenance-extension.php' ) ) { ?>
				<?php $maintenance_val = isset( $showhide_sections['maintenance'] ) ? intval( $showhide_sections['maintenance'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo esc_html__( 'Maintenance', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[maintenance]">
						<option value="0" <?php echo $maintenance_val == 0 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- Show if number of scans is > 0 -->
						<option value="1" <?php echo $maintenance_val == 1 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $maintenance_val == 2 ? 'selected="selected"' : ''; ?>><?php echo esc_html__( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<?php do_action( 'mainwp_pro_reports_showhide_settings', $showhide_sections, $default_val ); ?>
			</div>
		</div>

		</div>


		<div class="ui tab secondary segment accordion" id="mainwp-pro-reports-headings-accordion" data-tab="headings" style="margin-top: -13px;">
			<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-create-report-custom-titles-settings' ) ) : ?>
			<div class="ui blue message">
				<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-create-report-custom-titles-settings"></i>
				<?php echo esc_html__( 'Use this section to translate or just rewrite report titles, subtitles and table headers. This is optional. If default ones are fine for you, you can skip this step.', 'mainwp-pro-reports-extension' ); ?>
			</div>
			<?php endif; ?>
			<h4 class="ui dividing header title active"><i class="dropdown icon"></i> <?php esc_html_e( 'Titles & Subtitles', 'mainwp-pro-reports-extension' ); ?></h4>
			<div class="ui three columns grid content active">
				<div class="column">
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Summary', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-summary-heading" id="mainwp-summary-heading" value="<?php echo esc_attr( stripslashes( $mainwp_summary_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'General Information', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-general-heading" id="mainwp-general-heading" value="<?php echo esc_attr( stripslashes( $mainwp_general_heading ) ); ?>" />
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Misc Information', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-misc-heading" id="mainwp-misc-heading" value="<?php echo esc_attr( stripslashes( $mainwp_misc_heading ) ); ?>" />
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Updates', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-updates-heading" id="mainwp-updates-heading" value="<?php echo esc_attr( stripslashes( $mainwp_updates_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'WordPress Updates', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-wpupdates-heading" id="mainwp-wpupdates-heading" value="<?php echo esc_attr( stripslashes( $mainwp_wpupdates_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Plugins Updates', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-pluginsupdates-heading" id="mainwp-pluginsupdates-heading" value="<?php echo esc_attr( stripslashes( $mainwp_pluginsupdates_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Themes Updates', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-themesupdates-heading" id="mainwp-themesupdates-heading" value="<?php echo esc_attr( stripslashes( $mainwp_themesupdates_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Posts', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-posts-heading" id="mainwp-posts-heading" value="<?php echo esc_attr( stripslashes( $mainwp_posts_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'New Posts', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-newposts-heading" id="mainwp-newposts-heading" value="<?php echo esc_attr( stripslashes( $mainwp_newposts_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Updated Posts', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-updatedposts-heading" id="mainwp-updatedposts-heading" value="<?php echo esc_attr( stripslashes( $mainwp_updatedposts_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Deleted Posts', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-deletedposts-heading" id="mainwp-deletedposts-heading" value="<?php echo esc_attr( stripslashes( $mainwp_deletedposts_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Pages', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-pages-heading" id="mainwp-pages-heading" value="<?php echo esc_attr( stripslashes( $mainwp_pages_heading ) ); ?>" />
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'New Pages', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-newpages-heading" id="mainwp-newpages-heading" value="<?php echo esc_attr( stripslashes( $mainwp_newpages_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Updated Pages', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-updatedpages-heading" id="mainwp-updatedpages-heading" value="<?php echo esc_attr( stripslashes( $mainwp_updatedpages_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Deleted Pages', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-deletedpages-heading" id="mainwp-deletedpages-heading" value="<?php echo esc_attr( stripslashes( $mainwp_deletedpages_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Users', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-users-heading" id="mainwp-users-heading" value="<?php echo esc_attr( stripslashes( $mainwp_users_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'New Users', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-newusers-heading" id="mainwp-newusers-heading" value="<?php echo esc_attr( stripslashes( $mainwp_newusers_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Updated Users', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-updatedusers-heading" id="mainwp-updatedusers-heading" value="<?php echo esc_attr( stripslashes( $mainwp_updatedusers_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Deleted Users', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-deletedusers-heading" id="mainwp-deletedusers-heading" value="<?php echo esc_attr( stripslashes( $mainwp_deletedusers_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Comments', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-comments-heading" id="mainwp-comments-heading" value="<?php echo esc_attr( stripslashes( $mainwp_comments_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'New Comments', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-newcomments-heading" id="mainwp-newcomments-heading" value="<?php echo esc_attr( stripslashes( $mainwp_newcomments_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Updated Comments', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-updatedcomments-heading" id="mainwp-updatedcomments-heading" value="<?php echo esc_attr( stripslashes( $mainwp_updatedcomments_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Deleted Comments', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-deletedcomments-heading" id="mainwp-deletedcomments-heading" value="<?php echo esc_attr( stripslashes( $mainwp_deletedcomments_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Spam Comments', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-spamcomments-heading" id="mainwp-spamcomments-heading" value="<?php echo esc_attr( stripslashes( $mainwp_spamcomments_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Approved Comments', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-approvedcomments-heading" id="mainwp-approvedcomments-heading" value="<?php echo esc_attr( stripslashes( $mainwp_approvedcomments_heading ) ); ?>" />
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Backups', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-backups-heading" id="mainwp-backups-heading" value="<?php echo esc_attr( stripslashes( $mainwp_backups_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Google Analytics', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-analytics-heading" id="mainwp-analytics-heading" value="<?php echo esc_attr( stripslashes( $mainwp_analytics_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Fathom Analytics', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-fathom-analytics-heading" id="mainwp-fathom-analytics-heading" value="<?php echo esc_attr( stripslashes( $mainwp_fathom_analytics_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Matomo Analytics', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-matomo-analytics-heading" id="mainwp-matomo-analytics-heading" value="<?php echo esc_attr( stripslashes( $mainwp_matomo_analytics_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Uptime Monitoring', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-uptime-heading" id="mainwp-uptime-heading" value="<?php echo esc_attr( stripslashes( $mainwp_uptime_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Domain Status', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-domain-heading" id="mainwp-domain-heading" value="<?php echo esc_attr( stripslashes( $mainwp_domain_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'SSL Certificate Status', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-heading" id="mainwp-ssl-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Optimization', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-maintenance-heading" id="mainwp-maintenance-heading" value="<?php echo esc_attr( stripslashes( $mainwp_maintenance_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Performance', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-performance-heading" id="mainwp-performance-heading" value="<?php echo esc_attr( stripslashes( $mainwp_performance_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Security', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-security-heading" id="mainwp-security-heading" value="<?php echo esc_attr( stripslashes( $mainwp_security_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Sucuri Scans', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-sucuri-heading" id="mainwp-sucuri-heading" value="<?php echo esc_attr( stripslashes( $mainwp_sucuri_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Wordfence Scans', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-wordfence-heading" id="mainwp-wordfence-heading" value="<?php echo esc_attr( stripslashes( $mainwp_wordfence_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'iThemes Security Scans', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ithemes-heading" id="mainwp-ithemes-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ithemes_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Brute Force Protection', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ithemesbfp-heading" id="mainwp-ithemesbfp-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ithemesbfp_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Vulnerabilities', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-vuln-heading" id="mainwp-vuln-heading" value="<?php echo esc_attr( stripslashes( $mainwp_vuln_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Atarim Tasks', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-atarim-heading" id="mainwp-atarim-heading" value="<?php echo esc_attr( stripslashes( $mainwp_atarim_heading ) ); ?>" />
						</div>
					</div>
					<?php
					do_action( 'mainwp_pro_reports_content_settings_custom_titles', $settings );
					?>
				</div>
			</div>

			<h4 class="ui dividing header title"><i class="dropdown icon"></i> <?php esc_html_e( 'Table Headings', 'mainwp-pro-reports-extension' ); ?></h4>

			<div class="ui three columns grid content">
				<div class="column">
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Website', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-website-heading" id="mainwp-website-heading" value="<?php echo esc_attr( stripslashes( $mainwp_website_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Version', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-version-heading" id="mainwp-version-heading" value="<?php echo esc_attr( stripslashes( $mainwp_version_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'PHP Version', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-phpversion-heading" id="mainwp-phpversion-heading" value="<?php echo esc_attr( stripslashes( $mainwp_phpversion_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Old Version', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-oldversion-heading" id="mainwp-oldversion-heading" value="<?php echo esc_attr( stripslashes( $mainwp_oldversion_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'New Version', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-newversion-heading" id="mainwp-newversion-heading" value="<?php echo esc_attr( stripslashes( $mainwp_newversion_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Date', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-date-heading" id="mainwp-date-heading" value="<?php echo esc_attr( stripslashes( $mainwp_date_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Date Range', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-daterange-heading" id="mainwp-daterange-heading" value="<?php echo esc_attr( stripslashes( $mainwp_daterange_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Details', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-details-heading" id="mainwp-details-heading" value="<?php echo esc_attr( stripslashes( $mainwp_details_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Task', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-task-heading" id="mainwp-task-heading" value="<?php echo esc_attr( stripslashes( $mainwp_task_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Title', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-title-heading" id="mainwp-title-heading" value="<?php echo esc_attr( stripslashes( $mainwp_title_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Type', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-type-heading" id="mainwp-type-heading" value="<?php echo esc_attr( stripslashes( $mainwp_type_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Comment', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-comment-heading" id="mainwp-comment-heading" value="<?php echo esc_attr( stripslashes( $mainwp_comment_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Role', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-role-heading" id="mainwp-role-heading" value="<?php echo esc_attr( stripslashes( $mainwp_role_heading ) ); ?>" />
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'User', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-user-heading" id="mainwp-user-heading" value="<?php echo esc_attr( stripslashes( $mainwp_user_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Theme', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-theme-heading" id="mainwp-theme-heading" value="<?php echo esc_attr( stripslashes( $mainwp_theme_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Plugin', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-plugin-heading" id="mainwp-plugin-heading" value="<?php echo esc_attr( stripslashes( $mainwp_plugin_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Status', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-status-heading" id="mainwp-status-heading" value="<?php echo esc_attr( stripslashes( $mainwp_status_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Webtrust', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-webtrust-heading" id="mainwp-webtrust-heading" value="<?php echo esc_attr( stripslashes( $mainwp_webtrust_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Period', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-period-heading" id="mainwp-period-heading" value="<?php echo esc_attr( stripslashes( $mainwp_period_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Performance score', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-performancescore-heading" id="mainwp-performancescore-heading" value="<?php echo esc_attr( stripslashes( $mainwp_performancescore_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Accessibility score', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-accessibilityscore-heading" id="mainwp-accessibilityscore-heading" value="<?php echo esc_attr( stripslashes( $mainwp_accessibilityscore_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Best Practices score', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-bestpracticesscore-heading" id="mainwp-bestpracticesscore-heading" value="<?php echo esc_attr( stripslashes( $mainwp_bestpracticesscore_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'SEO score', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-seoscore-heading" id="mainwp-seoscore-heading" value="<?php echo esc_attr( stripslashes( $mainwp_seoscore_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Desktop', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-desktop-heading" id="mainwp-desktop-heading" value="<?php echo esc_attr( stripslashes( $mainwp_desktop_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Mobile', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-mobile-heading" id="mainwp-mobile-heading" value="<?php echo esc_attr( stripslashes( $mainwp_mobile_heading ) ); ?>" />
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'CNAME', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-cname-heading" id="mainwp-ssl-cname-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_cname_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Certificate Issuer', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-issuer-heading" id="mainwp-ssl-issuer-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_issuer_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Valid From', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-valid-from-heading" id="mainwp-ssl-valid-from-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_valid_from_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Valid To', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-valid-to-heading" id="mainwp-ssl-valid-to-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_valid_to_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Expires', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-expires-heading" id="mainwp-ssl-expires-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_expires_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Certificate Status', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-status-heading" id="mainwp-ssl-status-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_status_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Checked On', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-ssl-last-check-heading" id="mainwp-ssl-last-check-heading" value="<?php echo esc_attr( stripslashes( $mainwp_ssl_last_check_heading ) ); ?>" />
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Overall Uptime', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-overalluptime-heading" id="mainwp-overalluptime-heading" value="<?php echo esc_attr( stripslashes( $mainwp_overalluptime_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Expiry Date', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-expirydate-heading" id="mainwp-expirydate-heading" value="<?php echo esc_attr( stripslashes( $mainwp_expirydate_heading ) ); ?>" />
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Last 7 Days', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-uptime7-heading" id="mainwp-uptime7-heading" value="<?php echo esc_attr( stripslashes( $mainwp_uptime7_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Last 15 Days', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-uptime15-heading" id="mainwp-uptime15-heading" value="<?php echo esc_attr( stripslashes( $mainwp_uptime15_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Last 30 Days', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-uptime30-heading" id="mainwp-uptime30-heading" value="<?php echo esc_attr( stripslashes( $mainwp_uptime30_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Last 45 Days', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-uptime45-heading" id="mainwp-uptime45-heading" value="<?php echo esc_attr( stripslashes( $mainwp_uptime45_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Last 60 Days', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-uptime60-heading" id="mainwp-uptime60-heading" value="<?php echo esc_attr( stripslashes( $mainwp_uptime60_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-websitevisits-heading" id="mainwp-websitevisits-heading" value="<?php echo esc_attr( stripslashes( $mainwp_websitevisits_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Page Visits', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-pagevisits-heading" id="mainwp-pagevisits-heading" value="<?php echo esc_attr( stripslashes( $mainwp_pagevisits_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Page Views', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-pageviews-heading" id="mainwp-pageviews-heading" value="<?php echo esc_attr( stripslashes( $mainwp_pageviews_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Bounce Rate', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-bouncerate-heading" id="mainwp-bouncerate-heading" value="<?php echo esc_attr( stripslashes( $mainwp_bouncerate_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Average Time', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-averagetime-heading" id="mainwp-averagetime-heading" value="<?php echo esc_attr( stripslashes( $mainwp_averagetime_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'New Visits', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-newvisits-heading" id="mainwp-newvisits-heading" value="<?php echo esc_attr( stripslashes( $mainwp_newvisits_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Plugins Threats', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-pluginsthreats-heading" id="mainwp-pluginsthreats-heading" value="<?php echo esc_attr( stripslashes( $mainwp_pluginsthreats_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Themes Threats', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-themesthreats-heading" id="mainwp-themesthreats-heading" value="<?php echo esc_attr( stripslashes( $mainwp_themesthreats_heading ) ); ?>" />
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo esc_html__( 'Expires In', 'mainwp-pro-reports-extension' ); ?></label>
						<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set custom or leave deafult.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
							<input type="text" name="mainwp-expiresin-heading" id="mainwp-expiresin-heading" value="<?php echo esc_attr( stripslashes( $mainwp_expiresin_heading ) ); ?>" />
						</div>
					</div>
					<?php do_action( 'mainwp_pro_reports_content_settings_table_headings', $settings ); ?>
				</div>
			</div>
		</div>


		<div class="ui tab secondary segment" data-tab="images" style="margin-top: -13px;">
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Your logo', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column uploader-row-wrapper" data-tooltip="<?php esc_attr_e( 'Upload your logo here.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<p class="image_wrapper">
					<?php if ( ! empty( $logo ) ) : ?>
						<img src="<?php echo $logo; ?>" style="max-width: 400px;"/>
					<?php endif; ?>
					</p>
					<input type="hidden" class="image_wp_media_post_id" value="<?php echo intval( $logo_id ); ?>" name="pro-report-logo">
					<input type="button" href="#" class="upload_image_button ui mini green button" value="<?php esc_html_e( 'Upload', 'mainwp-pro-reports-extension' ); ?>">
					<input type="button" href="#" class="remove_image_button ui mini button" value="<?php esc_html_e( 'Remove', 'mainwp-pro-reports-extension' ); ?>">
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Header Image', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column uploader-row-wrapper" data-tooltip="<?php esc_attr_e( 'Upload your custom first page image here or leave blank to use default one.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<p class="image_wrapper">
					<?php if ( ! empty( $header_image ) ) : ?>
					<img src="<?php echo $header_image; ?>" style="max-width:100%;"/>
					<?php endif; ?>
					</p>
					<input type="hidden" class="image_wp_media_post_id" value="<?php echo intval( $header_image_id ); ?>" name="pro-report-header-image">
					<input type="button" href="#" class="upload_image_button ui mini green button" value="<?php esc_html_e( 'Upload', 'mainwp-pro-reports-extension' ); ?>">
					<input type="button" href="#" class="remove_image_button ui mini button" value="<?php esc_html_e( 'Remove', 'mainwp-pro-reports-extension' ); ?>">
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'First page background color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set first page background color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-first-page-background-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-first-page-background-color"  value="<?php echo esc_html( $first_page_bg_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Background color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set page background color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-background-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-background-color"  value="<?php echo esc_html( $bg_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Text color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set default text color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-text-color" data-default-color="#444444" class="pro-report-color-picker" id="pro-report-text-color"  value="<?php echo esc_html( $text_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Accent color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set accent color. It will be applied to links, heading and other important data.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-accent-color" data-default-color="#666666" class="pro-report-color-picker" id="pro-report-accent-color"  value="<?php echo esc_html( $accent_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Link color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set link color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-link-background-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-link-background-color"  value="<?php echo esc_html( $link_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Table background color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set table background color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-table-background-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-table-background-color"  value="<?php echo esc_html( $table_background_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Table header background color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set table header color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-table-header-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-table-header-color"  value="<?php echo esc_html( $table_header_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Table header text color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set table header text color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-table-header-text-color" data-default-color="#333333" class="pro-report-color-picker" id="pro-report-table-header-text-color"  value="<?php echo esc_html( $table_header_text_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo esc_html__( 'Table border color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set table border color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-table-border-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-table-border-color"  value="<?php echo esc_html( $table_border_color ); ?>" />
				</div>
			</div>
		</div>
	<script type="text/javascript">
		jQuery( document ).ready( function () {

			jQuery( '#mainwp-pro-reports-headings-accordion' ).accordion();

			jQuery( '#mainwp-pro-reports-customizations-menu .item' ).tab();

				var formfield;
				jQuery(document).on("click", '.upload_image_button', function(e) {
							e.preventDefault();
							formfield = jQuery(this).closest('.uploader-row-wrapper');
							var file_frame;
							// media frame.
							file_frame = wp.media({
								title: 'Select a image to upload',
								button: {
									text: 'Use this image',
								},
								multiple: false
							});
							// Image is selected
							file_frame.on( 'select', function() {
								attachment = file_frame.state().get('selection').first().toJSON();
								formfield.val( attachment.url );
								if(formfield.find("img").length > 0)
									formfield.find("img").attr("src", attachment.url);
								else
									formfield.find(".image_wrapper").append('<img src="'+attachment.url+'" />');
								formfield.find(".image_wp_media_post_id").val( attachment.id );
							});

							file_frame.on('open',function() {
								var selection =  file_frame.state().get('selection');
								var selected_id = formfield.find(".image_wp_media_post_id").val();
								if (selected_id) {
									var attachment = wp.media.attachment( selected_id );
									attachment.fetch();
									selection.add( attachment ? [ attachment ] : [] );
								}
							});

							// open the modal
							file_frame.open();
				});

				jQuery(document).on( "click", '.remove_image_button', function(e) {
					e.preventDefault();
					formfield = jQuery(this).closest('.uploader-row-wrapper');
					if(formfield.find("img").length > 0)
						formfield.find("img").remove();
					formfield.find(".image_wp_media_post_id").val( 0 );
				});


				jQuery('.pro-report-color-picker').wpColorPicker({
					hide: true,
					palettes: true
				});
			} );

		</script>
		<?php
	}

	public function get_pro_report_email_options( $report = null ) {
		$from_name              = '';
		$from_email             = '';
		$to_client_name         = '[client.name]';
		$to_email               = '[client.email]';
		$email_subject          = 'Report for [client.site.name]';
		$email_message          = esc_html__( 'Hi, here is the website care report for the past month. Find it attached', 'mainwp-pro-reports-extension' );
		$bcc_email              = '';
		$current_email_template = '';
		$attachFiles            = '';
		$reply_to_email         = '';
		$reply_to_name          = '';

		if ( ! empty( $report ) ) {
			$from_name  = $report->fname;
			$from_email = $report->femail;

			$to_email               = $report->send_to_email;
			$bcc_email              = $report->bcc_email;
			$to_client_name         = $report->send_to_name;
			$email_subject          = $report->subject;
			$email_message          = $report->message;
			$attachFiles            = isset( $report->attach_files ) ? $report->attach_files : '';
			$current_email_template = $report->template_email;
			$reply_to_email         = $report->reply_to;
			$reply_to_name          = $report->reply_to_name;
		}

		$tokens = MainWP_Pro_Reports_DB::get_instance()->get_tokens();

		$enable_media     = apply_filters( 'mainwp_pro_reports_enable_media_editor', false );
		$enable_quicktags = apply_filters( 'mainwp_pro_reports_enable_quicktags_editor', false );
		$standard_editor  = apply_filters( 'mainwp_pro_reports_enable_standard_editor', false );

		?>
		<div class="ui hidden divider"></div>

		<h3 class="ui dividing header"><?php echo esc_html__( 'Email Settings', 'mainwp-pro-reports-extension' ); ?></h3>

		<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-report-email-settings' ) ) : ?>
			<div class="ui blue message">
				<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-report-email-settings"></i>
				<?php echo esc_html__( 'If you are having problems with your MainWP Dashboard site not sending emails, you can consider installing some SMTP plugin that will allow you to route emails through your favorite SMTP provider.', 'mainwp-pro-reports-extension' ); ?>
			</div>
		<?php endif; ?>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Send email from', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the "Send from" email address. Usually, this will be your email address.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-from-email" id="pro-report-from-email" placeholder="Email (required)" value="<?php echo esc_attr( stripslashes( $from_email ) ); ?>" />
			</div>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the "Send from" name. Usually, this will be your or your company name.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-from-name" id="pro-report-from-name" placeholder="Name" value="<?php echo esc_attr( stripslashes( $from_name ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo esc_html__( 'Send email to', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the recipient\'s email address or use the corresponding token. If needed, add multiple email addresses separated by a comma.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" input-id="pro-report-to-email">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
				<input type="text" name="pro-report-to-email" placeholder="Email (required)" value="<?php echo esc_attr( stripslashes( $to_email ) ); ?>" id="pro-report-to-email"/>
			</div>
			</div>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the recipient\'s name or use the corresponding token.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" input-id="pro-report-to-client">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
					<input type="text" name="pro-report-to-client" placeholder="Client" value="<?php echo esc_attr( stripslashes( $to_client_name ) ); ?>" id="pro-report-to-client" />
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Reply-to', 'mainwp-client-reports-extension' ); ?></label>
			<div class="six wide column">
				<input type="text" name="pro-report-reply-to" placeholder="Reply-to Email address (optional)" value="<?php echo esc_attr( stripslashes( $reply_to_email ) ); ?>" />
			</div>
			<div class="six wide column">
				<input type="text" name="pro-report-reply-to-name" placeholder="Name" value="<?php echo esc_attr( stripslashes( $reply_to_name ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo esc_html__( 'Subject', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="twelve wide column" data-tooltip="<?php esc_attr_e( 'Enter the email subject. Tokens are allowed.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" input-id="pro-report-email-subject">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
				<input type="text" name="pro-report-email-subject" value="<?php echo esc_attr( stripslashes( $email_subject ) ); ?>" id="pro-report-email-subject" />
			</div>
		</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo esc_html__( 'Email message', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="twelve wide column" data-tooltip="<?php esc_attr_e( 'Enter the email content. Tokens are allowed.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" editor-id="pro-report-email-message">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo esc_html__( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a> | <a href="#" id="mainwp-email-preview-button"><?php echo esc_html__( 'Preview message', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
						<?php
						wp_editor(
							stripslashes( $email_message ),
							'pro-report-email-message',
							array(
								'textarea_name' => 'pro-report-email-message',
								'textarea_rows' => 10,
								'teeny'         => $standard_editor ? false : true,
								'media_buttons' => $enable_media ? true : false,
								'quicktags'     => $enable_quicktags ? true : false,
							)
						);
						?>
				</div>
			</div>
			</div>

		<?php	$temp_email_files = MainWP_Pro_Reports_Template::get_instance()->get_template_email_files(); ?>

		<div class="ui grid field">
			<label class="four wide column middle aligned"></label>
			<div class="twelve wide column">
				<div class="ui info message" id="mainwp-pro-reports-email-template-selection-info">
					<p><?php echo esc_html__( 'If needed, you can create a new custom template from scratch or copy and edit the existing template.', 'mainwp-pro-reports-extension' ); ?></p>
					<p><?php echo esc_html__( 'To create a new template, download the copy of the extension from the My Account area and copy one of the default template located in the /mainwp-pro-reports-extension/templates/emails/ folder of the extension and copy it to the ../wp-content/uploads/mainwp/report-email-templates/ directory and rename the file. Once copied, you can use your favorite code editor to edit it.', 'mainwp-pro-reports-extension' ); ?></p>
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Email template', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Select one of the available email templates.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">

				<select name="pro-report-email-template" id="pro-report-email-template" class="ui dropdown" >
				<?php
				foreach ( $temp_email_files as $file => $template ) {
					$_select = '';
					if ( $current_email_template == $file ) {
						$_select = 'selected';
					}
					echo '<option value="' . $file . '" ' . $_select . '>' . $template . '</option>';
				}
				?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Email BCC', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Optionally, enter the BCC email address.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-bcc-email" id="pro-report-bcc-email" placeholder="Email address (optional)" value="<?php echo esc_attr( stripslashes( $bcc_email ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo esc_html__( 'Additional email attachment', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Optionally, add another email attachment if needed.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<?php if ( ! empty( $attachFiles ) ) : ?>
						<div class="eight wide column">
						<div><?php echo $attachFiles; ?></div>
						<span class="ui checkbox">
							<input type="checkbox" value="1"  id="pro-report-email-attachement-remove-files" name="pro-report-email-attachement-remove-files">
							<label for="pro-report-email-attachement-remove-files"><?php esc_html_e( 'Delete attached files', 'mainwp-pro-reports-extension' ); ?></label>
						</span>
						</div>
					<?php endif; ?>
					<div class="ui file input">
						<input type="file" name="pro-report-email-attachements[]"  id="pro-report-email-attachements[]" multiple="true">
					</div>
			</div>
		</div>
		<input type="hidden" name="pro-report-client-id" value="0">

		<div class="ui mini modal" id="mainwp-pro-reports-insert-tokens-modal">
		<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Available Tokens', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="scrolling content">
				<?php if ( self::show_mainwp_message( 'mainwp-pro-reports-insert-tokens-info' ) ) : ?>
				<div class="ui info message">
					<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-insert-tokens-info"></i>
					<?php echo esc_html__( 'Tokens let you use client data in your emails. Simply click one below to insert it.', 'mainwp-pro-reports-extension' ); ?>
				</div>
				<?php endif; ?>
				<div class="ui hidden divider"></div>
				<?php if ( is_array( $tokens ) && count( $tokens ) > 0 ) : ?>
					<div class="ui relaxed divided list">
					<?php foreach ( (array) $tokens as $token ) : ?>
						<?php
						if ( ! $token ) {
							continue;}
						?>
						<a href="#" class="item pro-reports-edit-insert-token" token-id="<?php echo $token->id; ?>" token-value="[<?php echo stripslashes( $token->token_name ); ?>]">
						<div class="content">
							<div class="header">[<?php echo stripslashes( $token->token_name ); ?>]</div>
							<span style="font-size:1rem;color:rgba(0,0,0,.6);"><?php echo stripslashes( $token->token_description ); ?></span>
						</div>
					</a>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

		</div>

		<div class="ui small modal" id="mainwp-pro-reports-preview-email-modal">
		<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Message Preview', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="scrolling content">
				<div class="ui segments">
					<div class="ui secondary segment" id="mainwp-pro-email-subject-show"></div>
					<div class="ui segment" id="mainwp-pro-email-message-show"></div>
				</div>
			</div>

		</div>

		<script type="text/javascript">
			var current_InsertingInputId = false;
			var current_InsertingInputType = false;
			jQuery( '.mainwp-tokens-modal' ).on( 'click', function(e) {
					var parent = jQuery(this).closest('.parent-tokens-modal');
					current_InsertingInputId = parent.attr('editor-id');
					if (typeof current_InsertingInputId !== typeof undefined && current_InsertingInputId!== false) {
						current_InsertingInputType = 'editor';
					} else {
						current_InsertingInputId = parent.attr('input-id');
						current_InsertingInputType = 'text';
					}
					jQuery( '#mainwp-pro-reports-insert-tokens-modal' ).modal( 'show' );

					return false;
			} );

			jQuery( document ).on('click', '.pro-reports-edit-insert-token', function (e) {
				var replace_text = jQuery( this ).attr('token-value');
				console.log(replace_text);

				if (current_InsertingInputType == 'editor') { // editor input
					var edName = current_InsertingInputId;
					var editor = tinyMCE.get( edName );
				}

				var set_new_pos = replace_text.length;
				if (editor != null && typeof (editor) !== "undefined" && editor.isHidden() == false) {
					editor.execCommand( 'mceInsertContent', false, replace_text );
					var cursor = editor.dom.select( 'span#crp_ed_cursor' );
					if (cursor != null && typeof (cursor[0]) !== "undefined") {
						editor.selection.select( cursor[0] ).remove();
					}
				} else {
					var obj = jQuery( "#" + current_InsertingInputId );
					var str = obj.val();
					var pos = pro_reports_getPos( obj[0] );
					str = str.substring( 0, pos ) + replace_text + str.substring( pos, str.length )
					obj.val( str );
					set_new_pos += pos;
					pro_reports_setPos( obj[0], set_new_pos, set_new_pos );
				}
				jQuery( '#mainwp-pro-reports-insert-tokens-modal' ).modal( 'hide' );
				return false;
			});

			function pro_reports_getPos(obj) {
				var pos = 0;	// IE Support
				if (document.selection) {
					obj.focus();
					var range = document.selection.createRange();
					range.moveStart( 'character', -obj.value.length );
					pos = range.text.length;
				} // Firefox support
				else if (obj.selectionStart || obj.selectionStart == '0') {
					pos = obj.selectionStart;
				}
				return (pos);
			}

			function pro_reports_setPos(obj, selectionStart, selectionEnd) {
				if (document.selection) {
					obj.focus();
					var range = document.selection.createRange();
					range.collapse( true );
					range.moveEnd( 'character', selectionEnd );
					range.moveStart( 'character', selectionStart );
					range.select();
				} // Firefox support
				else {
					obj.focus();
					obj.setSelectionRange( selectionStart, selectionEnd );
				}
			}

		</script>
		<?php
	}


	// Delete Custom Tokens
	public function ajax_delete_token() {
		self::verify_nonce();
		$ret      = array( 'success' => false );
		$token_id = intval( $_POST['token_id'] );
		if ( MainWP_Pro_Reports_DB::get_instance()->delete_token_by( 'id', $token_id ) ) {
			$ret['success'] = true;
		}
		echo json_encode( $ret );
		exit;
	}

	// Save Custom Tokens
	public function ajax_save_token() {
		self::verify_nonce();
		$return            = array(
			'success' => false,
			'error'   => '',
			'message' => '',
		);
		$token_name        = sanitize_text_field( $_POST['token_name'] );
		$token_name        = trim( $token_name, '[]' );
		$token_description = sanitize_text_field( $_POST['token_description'] );

		// update
		if ( isset( $_POST['token_id'] ) && $token_id = intval( $_POST['token_id'] ) ) {
			$current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'id', $token_id );
			if ( $current && $current->token_name == $token_name && $current->token_description == $token_description ) {
				$return['success'] = true;
				$return['message'] = esc_html__( 'Token has been saved without changes.', 'mainwp-pro-reports-extension' );
			} elseif ( ( $current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'token_name', $token_name ) ) && $current->id != $token_id ) {
				$return['error'] = esc_html__( 'Token already exists, try different token name.', 'mainwp-pro-reports-extension' );
			} elseif ( $token = MainWP_Pro_Reports_DB::get_instance()->update_token(
				$token_id,
				array(
					'token_name'        => $token_name,
					'token_description' => $token_description,
				)
			) ) {
				$return['success'] = true;
			}
		} elseif ( $current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'token_name', $token_name ) ) { // add new
				$return['error'] = esc_html__( 'Token already exists, try different token name.', 'mainwp-pro-reports-extension' );
		} elseif ( $token = MainWP_Pro_Reports_DB::get_instance()->add_token(
			array(
				'token_name'        => $token_name,
				'token_description' => $token_description,
				'type'              => 0,
			)
		) ) {

				$return['success'] = true;
		} else {
			$return['error'] = esc_html__( 'Undefined error occurred. Please try again.', 'mainwp-pro-reports-extension' );
		}
		echo wp_json_encode( $return );
		exit;
	}


	public static function ajax_load_sites() {

		self::verify_nonce();

		global $mainWPProReportsExtensionActivator;
		$report_id = $_POST['report_id'];
		$websites  = array();

		if ( $report_id ) {
			$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
			if ( $report ) {
				$sel_sites   = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
				$sel_groups  = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );
				$sel_clients = ! empty( $report->clients ) ? json_decode( $report->clients, true ) : array();

				if ( ! is_array( $sel_sites ) ) {
					$sel_sites = array();
				}

				if ( ! is_array( $sel_groups ) ) {
					$sel_groups = array();
				}

				if ( ! is_array( $sel_clients ) ) {
					$sel_clients = array();
				}

				$dbwebsites = MainWP_Pro_Reports_Utility::get_db_websites( $sel_sites, $sel_groups, $sel_clients );
				if ( is_array( $dbwebsites ) ) {
					foreach ( $dbwebsites as $site ) {
						$websites[] = MainWP_Pro_Reports_Utility::map_site( $site, array( 'id', 'name', 'url' ) );
					}
				}
			}
		}

		$error = '';
		if ( empty( $report ) ) {
			$error = esc_html__( 'Report could not be found.', 'mainwp-reports-extension' );
		} elseif ( count( $websites ) == 0 ) {
			$error = esc_html__( 'There are no selected sites for the report. Please select your site(s) first.', 'mainwp-reports-extension' );
		}

		$html = '';

		if ( empty( $error ) ) {
			ob_start();
			?>
			<div class="ui relaxed divided list">
				<?php foreach ( $websites as $website ) : ?>
					<div class="item">
						<?php echo $website['name']; ?>
						<span class="siteItemProcess right floated" action="" site-id="<?php echo $website['id']; ?>" status="queue">
						<span class="status"><i class="clock outline icon"></i></span> <i style="display: none;" class="notched circle loading icon"></i></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			$html = ob_get_clean();
		}

		if ( ! empty( $error ) ) {
			$error = '<div class="ui yellow message">' . $error . '</div>';
			die( $error );
		}

		die( $html );
	}

	public function ajax_generate_report_content() {

		self::verify_nonce();

		$report_id = $_POST['report_id'];
		$site_id   = $_POST['site_id'];
		$what      = $_POST['what'];

		if ( empty( $site_id ) || empty( $report_id ) ) {
			die( json_encode( array( 'error' => esc_html__( 'Invalid data.' ) ) ) );
		}

		$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );

		if ( empty( $report ) ) { // is not group report
			die( json_encode( array( 'error' => esc_html__( 'Report could not be found.', 'mainwp-reports-extension' ) ) ) );
		}

		global $mainWPProReportsExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array( $site_id ), array() );

		$site = array();

		if ( is_array( $dbwebsites ) ) {

			$site           = current( $dbwebsites );
			$site           = MainWP_Pro_Reports_Utility::map_site( $site, array( 'id', 'name', 'url' ) );
			$cust_from_date = $cust_to_date = 0;

			if ( $what == 'preview' && $report->scheduled ) {

				$preview_recurring = self::calc_recurring_date( $report->recurring_schedule, $report->recurring_day ); // to preview, do not need to pass offset data time

				if ( is_array( $preview_recurring ) ) {
					if ( 'daily' == $report->recurring_schedule ) {
						$cust_from_date = $preview_recurring['date_from'] - 24 * 3600;
						$cust_to_date   = $preview_recurring['date_to'] - 24 * 3600;
					} elseif ( 'weekly' == $report->recurring_schedule ) {
							$cust_from_date = $preview_recurring['date_from'] - 7 * 24 * 3600;
							$cust_to_date   = $preview_recurring['date_to'] - 7 * 24 * 3600;
					}
					// elseif ( 'monthly' == $report->recurring_schedule ) {
					// $cust_from_date = strtotime( 'first day of last month' );
					// $cust_from_date = strtotime( date( 'Y-m-d', $cust_from_date ) . ' 00:00:00' );
					// $cust_to_date   = strtotime( 'last day of last month' );
					// $cust_to_date   = strtotime( date( 'Y-m-d', $cust_to_date ) . ' 23:59:59' );
					// }
				}
			}

			$generate_for = $what;
			// ajax: to generate report content.
			if ( MainWP_Pro_Reports::get_instance()->generate_report_content_and_data_for_website( $report, $site, $cust_from_date, $cust_to_date, $generate_for ) ) {
				// to reload updated data.
				$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
				if ( $what == 'send_test' || $what == 'send' ) {
					try {
						$data = MainWP_Pro_Reports::get_instance()->prepare_content_report_email( $report, $what, $site ); // generated report content.
						if ( ! $data || ! MainWP_Pro_Reports_Schedule::get_instance()->send_onetime_report_email( $data, $report, $site ) ) {
							die( json_encode( array( 'error' => esc_html__( 'Undefined error. Email could not be sent.', 'mainwp-pro-reports-extension' ) ) ) );
						}
					} catch ( \Exception $e ) {
						die( json_encode( array( 'error' => $e->getMessage() ) ) );
					}
				}
				die( json_encode( array( 'result' => 'success' ) ) );
			} else {
				die( json_encode( array( 'error' => esc_html__( 'Data saved successfully.', 'mainwp-pro-reports-extension' ) ) ) );
			}
		}
		die( json_encode( array( 'error' => esc_html__( 'Invalid Site ID.', 'mainwp-pro-reports-extension' ) ) ) );
	}

	public static function ajax_email_message_preview() {

		self::verify_nonce();

		$site_id = isset( $_POST['site_id'] ) ? intval( $_POST['site_id'] ) : 0;
		$subject = $_POST['subject'];
		$message = $_POST['message'];

		if ( empty( $site_id ) ) {
			// get random site id
			global $mainWPProReportsExtensionActivator;
			$websites  = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );
			$sites_ids = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $website ) {
					$sites_ids[] = $website['id'];
				}
			}
			$site_id = $sites_ids[ array_rand( $sites_ids ) ];
		}

		// find and replace tokens values
		if ( $site_id && ( MainWP_Pro_Reports::has_tokens( $subject ) || MainWP_Pro_Reports::has_tokens( $message ) ) ) {
			$sites_token = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $site_id, 'token_name' );
			if ( ! is_array( $sites_token ) ) {
				$sites_token = array();
			}

			$search_token = $replace_value = array();
			// to support report tokens
			// $search_token[] = '[report.daterange]';
			// $replace_value[] = MainWP_Pro_Reports_Utility::format_timestamp( $report->date_from ) . ' - ' . MainWP_Pro_Reports_Utility::format_timestamp( $report->date_to );
			$search_token[]  = '[report.send.date]';
			$now             = time();
			$replace_value[] = MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $now ) );

			foreach ( $sites_token as $token_name => $token ) {
				$search_token[]  = '[' . $token_name . ']';
				$replace_value[] = $token->token_value;
			}

			if ( MainWP_Pro_Reports::has_tokens( $subject ) ) {
				$subject = str_replace( $search_token, $replace_value, $subject );
			}
			if ( MainWP_Pro_Reports::has_tokens( $message ) ) {
				$message = str_replace( $search_token, $replace_value, $message );
			}
		}

		$message = nl2br( $message ); // to fix
		wp_send_json(
			array(
				'subject' => $subject,
				'message' => $message,
			)
		);
	}



	/**
	 * Method render_preview_report()
	 *
	 * Render preview report.
	 */
	public function render_preview_report( $report_id ) {
		$content  = '';
		$check_ok = false;

		if ( $report_id ) {
			$results = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_content( $report_id );
			if ( is_array( $results ) ) {
				foreach ( $results as $result ) {
					$content .= $result->report_content;
					$check_ok = true;
				}
			}
		}

		if ( ! $check_ok ) {
			$content .= '<div class="ui yellow message">' . esc_html__( 'Preview could not be generated. Please try again.', 'mainwp-pro-reports-extension' ) . '</div>';
		}

		ob_start();
		echo $content;
		$html = ob_get_clean();
		return $html;
	}


	public function ajax_do_action_report() {

		self::verify_nonce();

		$report_id = intval( $_POST['reportId'] );
		$action    = $_POST['what'];

		if ( empty( $report_id ) ) {
			die( json_encode( array( 'error' => esc_html__( 'Invalid report ID. Please, try again.', 'mainwp-pro-reports-extension' ) ) ) );
		}

		$ret     = array();
		$success = false;
		switch ( $action ) {
			case 'delete':
				if ( MainWP_Pro_Reports_DB::get_instance()->delete_report_by( 'id', $report_id ) ) {
					$success = true;
				}
				break;
			default:
				break;
		}

		if ( $success ) {
			$ret['status'] = 'success';
		}

		echo json_encode( $ret );
		exit;
	}
}
