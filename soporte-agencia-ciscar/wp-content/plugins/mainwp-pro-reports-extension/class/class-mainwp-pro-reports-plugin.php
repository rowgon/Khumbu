<?php

class MainWP_Pro_Reports_Plugin {

	private $option_handle  = 'mainwp_creport_branding_option';
	private $option         = array();
	private static $order   = '';
	private static $orderby = '';
	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Pro_Reports_Plugin();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );

		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_pro_reports_active_plugin', array( $this, 'ajax_active_plugin' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_upgrade_plugin', array( $this, 'ajax_upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_showhide_plugin', array( $this, 'ajax_showhide_wsal' ) );
		add_filter( 'mainwp_header_actions_right', array( $this, 'screen_options' ), 10, 2 );
		$this->handle_sites_screen_settings();
	}

	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}


	/**
	 * Method handle_sites_screen_settings()
	 *
	 * Handle sites screen settings
	 */
	public function handle_sites_screen_settings() {
		if ( isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'ProReportsSitesScrOptions' ) ) {
			$show_cols = array();
			foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST ) ) as $key => $val ) {
				if ( false !== strpos( $key, 'mainwp_show_column_' ) ) {
					$col               = str_replace( 'mainwp_show_column_', '', $key );
					$show_cols[ $col ] = 1;
				}
			}
			if ( isset( $_POST['show_columns_name'] ) ) {
				foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST['show_columns_name'] ) ) as $col ) {
					if ( ! isset( $show_cols[ $col ] ) ) {
						$show_cols[ $col ] = 0; // uncheck, hide columns.
					}
				}
			}
			$user = wp_get_current_user();
			if ( $user ) {
				update_user_option( $user->ID, 'mainwp_settings_show_pro_reports_sites_columns', $show_cols, true );
			}
		}
	}

	/**
	 * Method screen_options()
	 *
	 * Create Screen Options button.
	 *
	 * @param mixed $input Screen options button HTML.
	 *
	 * @return mixed Screen sptions button.
	 */
	public function screen_options( $input ) {
		if ( isset( $_GET['page'] ) && 'Extensions-Mainwp-Pro-Reports-Extension' == $_GET['page'] ) {
			if ( ! isset( $_GET['tab'] ) || 'dashboard' == $_GET['tab'] ) {
				$input .= '<a class="ui button basic icon" onclick="mainwp_pro_reports_sites_screen_options(); return false;" data-inverted="" data-position="bottom right" href="#" target="_blank" data-tooltip="' . esc_html__( 'Screen Options', 'mainwp' ) . '"><i class="cog icon"></i></a>';
			}
		}
		return $input;
	}

	public function site_synced( $website, $information = array() ) {
		$website_id = $website->id;
		if ( is_array( $information ) && isset( $information['syncClientReportData'] ) && is_array( $information['syncClientReportData'] ) ) {
			$data = $information['syncClientReportData'];
			if ( isset( $data['firsttime_activated'] ) ) {
				$creportSettings = $this->get_option( 'settings' );

				if ( ! is_array( $creportSettings ) ) {
					$creportSettings = array();
				}

				$creportSettings[ $website_id ]['first_time'] = $data['firsttime_activated'];

				$this->set_option( 'settings', $creportSettings );
			}
		}
	}


	/**
	 * Get columns.
	 *
	 * @return array Array of column names.
	 */
	public static function get_columns() {
		return array(
			'site'        => esc_html__( 'Site', 'mainwp-pro-reports-extension' ),
			'sign-in'     => '<i class="sign in icon"></i>',
			'url'         => esc_html__( 'URL', 'mainwp-pro-reports-extension' ),
			'version'     => esc_html__( 'Version', 'mainwp-pro-reports-extension' ),
			'hidden'      => esc_html__( 'Hidden', 'mainwp-pro-reports-extension' ),
			'last-report' => esc_html__( 'Last Report', 'mainwp-pro-reports-extension' ),
			'log-start'   => esc_html__( 'Log Start', 'mainwp-pro-reports-extension' ),
			'actions'     => esc_html__( 'Expiry Date', 'mainwp-pro-reports-extension' ),
			'db-size'     => esc_html__( 'DB Size', 'mainwp-pro-reports-extension' ),
		);
	}


	public static function gen_dashboard_tab() {
		?>
		<table id="mainwp-pro-reports-sites-table" class="ui table" style="width:100%">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox" id="cb-select-all-top"></span></th>
					<th id="site"><?php esc_html_e( 'Site', 'mainwp-pro-reports-extension' ); ?></th>
					<th id="sign-in" class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th id="url"><?php esc_html_e( 'URL', 'mainwp-pro-reports-extension' ); ?></th>
					<th id="version" style="text-align:center"><?php esc_html_e( 'Version', 'mainwp-pro-reports-extension' ); ?></th>
					<th id="hidden" style="text-align:center"><?php esc_html_e( 'Hidden', 'mainwp-pro-reports-extension' ); ?></th>
					<th id="last-report"><?php esc_html_e( 'Last Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th id="db-size"><?php esc_html_e( 'Reports DB Size', 'mainwp-pro-reports-extension' ); ?></th>
					<th id="actions" class="no-sort collapsing"></th>
				</tr>
			</thead>
			<tbody>
				<?php
				self::gen_dashboard_table_rows();
				?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox" id="cb-select-all-bottom"></span></th>
					<th><?php esc_html_e( 'Site', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php esc_html_e( 'URL', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Version', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Hidden', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Last Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php esc_html_e( 'Reports DB Size', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"></th>
				</tr>
			</tfoot>
		</table>
		<?php self::render_screen_options_modal(); ?>
		<script type="text/javascript">
			jQuery( document ).ready( function () {
				var $pro_reports_sites_table = jQuery( '#mainwp-pro-reports-sites-table' ).DataTable( {
					"stateSave": true,
					"stateDuration": 0, // forever
					"colReorder" : { columns: ":not(.check-column):not(:last-child)" },
					"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
					"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
					"order": [ [ 1, "asc" ] ],
					"language": { "emptyTable": "No websites were found with the Child Reports plugin installed." },
					"drawCallback": function( settings ) {
						jQuery('#mainwp-pro-reports-sites-table .ui.checkbox').checkbox();
						jQuery('#mainwp-pro-reports-sites-table .ui.dropdown').dropdown();
						mainwp_datatable_fix_menu_overflow('#mainwp-pro-reports-sites-table', -50 );

					},
					'select': {
						items: 'row',
						style: 'multi+shift',
						selector: 'tr>td:not(.not-selectable)'
					},
				} ).on('select', function (e, dt, type, indexes) {
					if( 'row' == type ){
						dt.rows(indexes)
						.nodes()
						.to$().find('td.check-column .ui.checkbox' ).checkbox('set checked');
					}
				}).on('deselect', function (e, dt, type, indexes) {
					if( 'row' == type ){
						dt.rows(indexes)
						.nodes()
						.to$().find('td.check-column .ui.checkbox' ).checkbox('set unchecked');
					}
				}).on( 'columns-reordered', function ( e, settings, details ) {
					console.log('columns-reordered');
					setTimeout(() => {
						jQuery( '#mainwp-pro-reports-sites-table .ui.dropdown' ).dropdown();
						jQuery( '#mainwp-pro-reports-sites-table .ui.checkbox' ).checkbox();
						mainwp_datatable_fix_menu_overflow('#mainwp-pro-reports-sites-table', -50 );
					}, 1000);
				} );
				jQuery('#mainwp-pro-reports-sites-table .ui.checkbox').checkbox();
				jQuery('#mainwp-pro-reports-sites-table .ui.dropdown').dropdown();
				mainwp_datatable_fix_menu_overflow('#mainwp-pro-reports-sites-table', -50 );
				_init_pro_reports_sites_screen();
			});

			_init_pro_reports_sites_screen = function() {
					jQuery( '#mainwp-pro-reports-sites-screen-options-modal input[type=checkbox][id^="mainwp_show_column_"]' ).each( function() {
						var check_id = jQuery( this ).attr( 'id' );
						col_id = check_id.replace( "mainwp_show_column_", "" );
						try {
							$pro_reports_sites_table.column( '#' + col_id ).visible( jQuery( this ).is( ':checked' ) );
							if ( check_id.indexOf( "mainwp_show_column_desktop" ) >= 0 ) {
								col_id = check_id.replace( "mainwp_show_column_desktop", "" );
								$pro_reports_sites_table.column( '#mobile' + col_id ).visible( jQuery( this ).is( ':checked' ) ); // to set mobile columns.
							}
						} catch( err ) {
							// to fix js error.
						}
					} );
			};
			mainwp_pro_reports_sites_screen_options = function () {
					jQuery( '#mainwp-pro-reports-sites-screen-options-modal' ).modal( {
						allowMultiple: true,
						onHide: function () {
						}
					} ).modal( 'show' );

					jQuery( '#pro-reports-sites-screen-options-form' ).submit( function() {
						if ( jQuery('input[name=reset_proreports_sites_columns_order]').attr( 'value' ) == 1 ) {
							$pro_reports_sites_table.colReorder.reset();
						}
						jQuery( '#mainwp-pro-reports-sites-screen-options-modal' ).modal( 'hide' );
					} );
					return false;
			};
		</script>
		<?php
	}

	public static function gen_dashboard_table_rows() {

		$websites = self::get_instance()->get_websites_reports();

		$location    = 'options-general.php?page=mainwp-reports-page';
		$plugin_slug = 'mainwp-child-reports/mainwp-child-reports.php';
		$plugin_name = 'MainWP Child Reports';

		foreach ( $websites as $website ) {
			$website_id = $website['id'];

			$class_active = ( isset( $website['plugin_activated'] ) && ! empty( $website['plugin_activated'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['reports_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version = '';
			if ( isset( $website['reports_upgrade'] ) ) {
				if ( isset( $website['reports_upgrade']['new_version'] ) ) {
					$version = $website['reports_upgrade']['new_version'];
				}
			}
			// echo var_dump( $website );
			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['plugin_version'] ) ) ? $website['plugin_version'] : 'N/A'; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>" data-tooltip="<?php esc_attr_e( 'Click to jump to the site Overview page', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted=""><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" data-tooltip="<?php esc_attr_e( 'Jump to the site WP Admin', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted=""><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank" data-tooltip="<?php esc_attr_e( 'Jump to the site front page', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted=""><?php echo $website['url']; ?></a></td>
				<td style="text-align:center"><span class="updating"></span> <?php echo ( isset( $website['reports_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['plugin_version'] ) ) ? $website['plugin_version'] : 'N/A'; ?></td>
				<td style="text-align:center"><span class="visibility"></span> <span class="wp-reports-visibility"><?php echo ( 1 == $website['hide_stream'] ) ? esc_html__( 'Yes', 'mainwp-pro-reports-extension' ) : esc_html__( 'No', 'mainwp-pro-reports-extension' ); ?></span></td>
				<td>
					<?php if ( $website['last_report'] ) : ?>
						<?php echo MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $website['last_report'] ) ); ?>
					<?php else : ?>
						<?php esc_html_e( 'No reports sent yet.', 'mainwp-pro-reports-extension' ); ?>
					<?php endif; ?>
				</td>
				<td data-order="<?php echo isset( $website['db_size'] ) ? $website['db_size'] : ''; ?>">
					<?php if ( isset( $website['db_size'] ) ) : ?>
						<?php echo esc_html( $website['db_size'] ) . ' MB'; ?>
					<?php endif; ?>
				</td>
				<td class="not-selectable">
					<div class="ui right pointing dropdown basic icon mini green button" style="z-index:999" >
						<span data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-pro-reports-extension' ); ?>" data-position="left center" data-inverted=""><i class="ellipsis horizontal icon"></i></span>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php esc_html_e( 'Overview', 'mainwp-pro-reports-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php esc_html_e( 'Edit', 'mainwp-pro-reports-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php esc_html_e( 'Open Child Reports', 'mainwp-pro-reports-extension' ); ?></a>
							<?php if ( 1 == $website['hide_stream'] ) : ?>
							<a class="item mainwp-pro-reports-showhide-plugin" href="#" showhide="show"><?php esc_html_e( 'Unhide Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php else : ?>
							<a class="item mainwp-pro-reports-showhide-plugin" href="#" showhide="hide"><?php esc_html_e( 'Hide Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['plugin_activated'] ) && empty( $website['plugin_activated'] ) ) : ?>
							<a class="item mainwp-pro-reports-activate-plugin" href="#"><?php esc_html_e( 'Activate Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['reports_upgrade'] ) ) : ?>
							<a class="item mainwp-pro-reports-update-plugin" href="#"><?php esc_html_e( 'Update Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</td>
				</tr>
			<?php
		}
	}

	/**
	 * Render screen options.
	 *
	 * @return array Array of default column names.
	 */
	public static function render_screen_options_modal() {

		$columns = self::get_columns();

		$show_cols = get_user_option( 'mainwp_settings_show_pro_reports_sites_columns' );

		if ( ! is_array( $show_cols ) ) {
			$show_cols = array();
		}

		?>
		<div class="ui modal" id="mainwp-pro-reports-sites-screen-options-modal">
		<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="scrolling content ui form">
				<form method="POST" action="" id="pro-reports-sites-screen-options-form">
					<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
					<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'ProReportsSitesScrOptions' ); ?>" />
						<div class="ui grid field">
							<label class="six wide column"><?php esc_html_e( 'Show columns', 'mainwp-pro-reports-extension' ); ?></label>
							<div class="ten wide column">
								<ul class="mainwp_hide_wpmenu_checkboxes">
									<?php
									foreach ( $columns as $name => $title ) {
										?>
										<li>
											<div class="ui checkbox">
												<input type="checkbox"
												<?php
												$show_col = ! isset( $show_cols[ $name ] ) || ( 1 == $show_cols[ $name ] );
												if ( $show_col ) {
													echo 'checked="checked"';
												}
												?>
												id="mainwp_show_column_<?php echo esc_attr( $name ); ?>" name="mainwp_show_column_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $name ); ?>">
												<label for="mainwp_show_column_<?php echo esc_attr( $name ); ?>" ><?php echo $title; ?></label>
												<input type="hidden" value="<?php echo esc_attr( $name ); ?>" name="show_columns_name[]" />
											</div>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
					</div>
				</div>
			<div class="actions">
					<div class="ui two columns grid">
						<div class="left aligned column">
							<span data-tooltip="<?php esc_attr_e( 'Returns this page to the state it was in when installed. The feature also restores any column you have moved through the drag and drop feature on the page.', 'mainwp-pro-reports-extension' ); ?>" data-inverted="" data-position="top center"><input type="button" class="ui button" name="reset" id="reset-pro-reports-settings" value="<?php esc_attr_e( 'Reset Page', 'mainwp-pro-reports-extension' ); ?>" /></span>
						</div>
						<div class="ui right aligned column">
					<input type="submit" class="ui green button" name="btnSubmit" id="submit-proreportssites-settings" value="<?php esc_attr_e( 'Save Settings', 'mainwp-pro-reports-extension' ); ?>" />
				</div>
					</div>
				</div>
				<input type="hidden" name="reset_proreports_sites_columns_order" value="0">
			</form>
		</div>
		<div class="ui small modal" id="mainwp-pro-reports-sites-site-preview-screen-options-modal">
		<i class="close icon"></i>
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="scrolling content ui form">
				<span><?php esc_html_e( 'Would you like to turn on home screen previews? This function queries WordPress.com servers to capture a screenshot of your site the same way comments shows you preview of URLs.', 'mainwp-pro-reports-extension' ); ?>
			</div>
			<div class="actions">
				<div class="ui ok button"><?php esc_html_e( 'Yes', 'mainwp-pro-reports-extension' ); ?></div>
				<div class="ui cancel button"><?php esc_html_e( 'No', 'mainwp-pro-reports-extension' ); ?></div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery( '#reset-pro-reports-settings' ).on( 'click', function () {
					mainwp_confirm( esc_html__( 'Are you sure.' ), function() {
						jQuery( '.mainwp_hide_wpmenu_checkboxes input[id^="mainwp_show_column_"]' ).prop( 'checked', false );
						//default columns
						var cols = ['site','url','actions'];
						jQuery.each( cols, function ( index, value ) {
							jQuery( '.mainwp_hide_wpmenu_checkboxes input[id="mainwp_show_column_' + value + '"]' ).prop( 'checked', true );
						} );
						jQuery( 'input[name=reset_proreports_sites_columns_order]' ).attr( 'value', 1 );
						jQuery( '#submit-proreportssites-settings' ).click();
					}, false, false, true );
					return false;
				} );
			} );
		</script>
		<?php
	}


	public function get_websites_reports() {

		global $mainWPProReportsExtensionActivator;

		$filter_groups = '';
		if ( isset( $_GET['group'] ) && ! empty( $_GET['group'] ) ) {
			$filter_groups = $_GET['group'];
		}

		$params = array(
			'extra_select_wp_fields' => array( 'plugin_upgrades', 'plugins' ),
			'extra_view'             => array( 'site_info', 'mainwp_pro_reports_last_report' ),
		);

		$sql_sites = apply_filters( 'mainwp_getsqlwebsites_for_current_user', false, $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $params );

		$websites = MainWP_Pro_Reports_DB::get_instance()->query( $sql_sites );

		$websites_stream = array();

		$streamHide = $this->get_option( 'hide_stream_plugin' );

		if ( ! is_array( $streamHide ) ) {
				$streamHide = array();
		}

		$creportSettings = $this->get_option( 'settings' );

		if ( ! is_array( $creportSettings ) ) {
			$creportSettings = array();
		}

		if ( ! empty( $filter_groups ) ) {
			$filter_groups = explode( '-', $filter_groups );
		}

		if ( ! is_array( $filter_groups ) ) {
			$filter_groups = array();
		}

		if ( MainWP_Pro_Reports_DB::num_rows( $websites ) ) {
			if ( empty( $filter_groups ) ) {
				while ( $websites && ( $website = MainWP_Pro_Reports_DB::fetch_object( $websites ) ) ) {
					if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							$creportSiteSettings = array();

							if ( isset( $creportSettings[ $website->id ] ) ) {
								$creportSiteSettings = $creportSettings[ $website->id ];

								if ( ! is_array( $creportSiteSettings ) ) {
									$creportSiteSettings = array();
								}
							}

							foreach ( $plugins as $plugin ) {
								if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {

									$site = MainWP_Pro_Reports_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['plugin_activated'] = 1;
									} else {
										$site['plugin_activated'] = 0;
									}
									// get upgrade info
									$site['plugin_version'] = $plugin['version'];
									$plugin_upgrades        = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'] ) ) {
											$upgrade = $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'];
											if ( isset( $upgrade['update'] ) ) {
												$site['reports_upgrade'] = $upgrade['update'];
											}
										}
									}

									$site['hide_stream'] = 0;
									if ( isset( $streamHide[ $website->id ] ) && $streamHide[ $website->id ] ) {
										$site['hide_stream'] = 1;
									}

									if ( isset( $creportSiteSettings['first_time'] ) ) {
										$site['first_time'] = $creportSiteSettings['first_time'];
									}
									$site['last_report'] = isset( $website->mainwp_pro_reports_last_report ) ? $website->mainwp_pro_reports_last_report : 0;
									$site_info           = ! empty( $website->site_info ) ? json_decode( $website->site_info, true ) : array();
									$site['db_size']     = is_array( $site_info ) && isset( $site_info['db_size'] ) ? $site_info['db_size'] : 0;
									$websites_stream[]   = $site;
									break;
								}
							}
						}
					}
				}
			} else {
				global $mainWPProReportsExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array(), $filter_groups );
				$sites          = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				while ( $websites && ( $website = MainWP_Pro_Reports_DB::fetch_object( $websites ) ) ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							$creportSiteSettings = array();

							if ( isset( $creportSettings[ $website->id ] ) ) {
								$creportSiteSettings = $creportSettings[ $website->id ];
							}

							if ( ! is_array( $creportSiteSettings ) ) {
								$creportSiteSettings = array();
							}

							foreach ( $plugins as $plugin ) {
								if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {

									$site = MainWP_Pro_Reports_Utility::map_site( $website, array( 'id', 'name', 'url' ) );

									if ( $plugin['active'] ) {
										$site['plugin_activated'] = 1;
									} else {
										$site['plugin_activated'] = 0;
									}

									$site['plugin_version'] = $plugin['version'];

									// get upgrade info
									$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'] ) ) {
											$upgrade = $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'];
											if ( isset( $upgrade['update'] ) ) {
																$site['reports_upgrade'] = $upgrade['update'];
											}
										}
									}

									$site['hide_stream'] = 0;
									if ( isset( $streamHide[ $website->id ] ) && $streamHide[ $website->id ] ) {
										$site['hide_stream'] = 1;
									}
									if ( isset( $creportSiteSettings['first_time'] ) ) {
										$site['first_time'] = $creportSiteSettings['first_time'];
									}
									$site['last_report'] = isset( $website->mainwp_pro_reports_last_report ) ? $website->mainwp_pro_reports_last_report : 0;
									$site_info           = ! empty( $website->site_info ) ? json_decode( $website->site_info, true ) : array();
									$site['db_size']     = is_array( $site_info ) && isset( $site_info['db_size'] ) ? $site_info['db_size'] : 0;
									$websites_stream[]   = $site;
									break;
								}
							}
						}
					}
				}
			}
		}

		if ( $websites ) {
			MainWP_Pro_Reports_DB::free_result( $websites );
		}

		// if search action.
		$search_sites = array();
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$find = trim( $_GET['s'] );
			foreach ( $websites_stream as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_stream = $search_sites;
		}
		unset( $search_sites );

		return $websites_stream;
	}

	public static function gen_actions_bar() {
		global $mainWPProReportsExtensionActivator;

		$groups = apply_filters( 'mainwp_getgroups', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );

		$filter_groups = array();
		if ( isset( $_GET['group'] ) && ! empty( $_GET['group'] ) ) {
			$filter_groups = explode( '-', $_GET['group'] );
		}

		?>
		<div class="mainwp-actions-bar">
			<div class="ui two column grid mini form">
				<div class="left aligned middle aligned column">
					<select id="mainwp-pro-reports-actions" class="ui dropdown">
						<option selected="selected" value=""><?php esc_html_e( 'Bulk Actions', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="activate-selected"><?php esc_html_e( 'Activate', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="update-selected"><?php esc_html_e( 'Update', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="hide-selected"><?php esc_html_e( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="show-selected"><?php esc_html_e( 'Unhide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
					<input type="button" value="<?php esc_html_e( 'Apply' ); ?>" class="ui mini button" id="mainwp-pro-reports-actions-button">
				</div>
				<div class="right aligned middle aligned column">
					<form method="post" action="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=dashboard">
						<select name="mainwp-pro-reports-groups-selection" id="mainwp-pro-reports-groups-selection" multiple="" class="ui multiple search dropdown">
							<option value=""><?php esc_html_e( 'All Tags', 'mainwp-pro-reports-extension' ); ?></option>
							<?php
							if ( is_array( $groups ) && count( $groups ) > 0 ) {
								foreach ( $groups as $group ) {

									$_select = '';
									if ( in_array( $group['id'], $filter_groups ) ) {
										$_select = 'selected ';
									}

									echo '<option value="' . $group['id'] . '" ' . $_select . '>' . $group['name'] . '</option>';
								}
							}
							?>
						</select>
						<input class="ui mini button" type="button" name="mainwp-pro-reports-sites-filter-button" id="mainwp-pro-reports-sites-filter-button" value="<?php esc_html_e( 'Filter Sites', 'mainwp-reports-extension' ); ?>">
					</form>
				</div>
			</div>
		</div>
		<?php
		return;
	}

	public function ajax_active_plugin() {
		MainWP_Pro_Reports_Overview::verify_nonce();
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function ajax_upgrade_plugin() {
		MainWP_Pro_Reports_Overview::verify_nonce();
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_wsal() {
		MainWP_Pro_Reports_Overview::verify_nonce();
		$siteid   = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainWPProReportsExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'set_showhide',
				'showhide'   => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $siteid, 'client_report', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
				$hide_stream = $this->get_option( 'hide_stream_plugin' );
				if ( ! is_array( $hide_stream ) ) {
					$hide_stream = array();
				}
				$hide_stream[ $siteid ] = ( 'hide' === $showhide ) ? 1 : 0;
				$this->set_option( 'hide_stream_plugin', $hide_stream );
			}

			die( json_encode( $information ) );
		}
		die();
	}
}
