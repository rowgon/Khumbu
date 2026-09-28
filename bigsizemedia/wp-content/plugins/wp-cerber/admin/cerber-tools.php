<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licensed under the GNU GPL

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*

*========================================================================*
|                                                                        |
|	       ATTENTION!  Do not change or edit this file!                  |
|                                                                        |
*========================================================================*

*/

function cerber_show_imex() {

	$form = '<h3>' . __( 'Export WP Cerber Settings to File', 'wp-cerber' ) . '</h3>';
	$form .= '<p>' . __( 'Export a configuration file to back up your settings and restore them on another site.', 'wp-cerber' ) . '</p>';
	$form .= '<p>' . __( 'What do you want to include in the export?', 'wp-cerber' ) . '</p><form action="" method="get">';
	$form .= '<input id="exportset" name="exportset" value="1" type="checkbox" checked> <label for="exportset">' . __( 'Settings', 'wp-cerber' ) . '</label>';
	$form .= '<p><input id="exportacl" name="exportacl" value="1" type="checkbox" checked> <label for="exportacl">' . __( 'Access Lists', 'wp-cerber' ) . '</label>';
	$form .= '<p><input type="submit" name="cerber_export" id="submit" class="button button-primary" value="' . __( 'Download File', 'wp-cerber' ) . '"></form>';

	$nf = wp_nonce_field( 'crb_import', 'crb_field' );

	$form .= '<h3 style="margin-top:2em;">' . __( 'Import WP Cerber Settings from File', 'wp-cerber' ) . '</h3>';
	$form .= '<p>' . __( 'Import a configuration file to replace your existing settings.', 'wp-cerber' ) . '</p>';
	/* translators: %s is the maximum file size allowed for upload. */
	$form .= '<p>' . __( 'Select file to import.', 'wp-cerber' ) . ' ' . sprintf( __( 'Maximum upload file size: %s.' ), esc_html( size_format( wp_max_upload_size() ) ) );
	$form .= '<form action="" method="post" enctype="multipart/form-data">' . $nf;
	$form .= '<p><input type="file" name="ifile" id="ifile" required="required">';
	$form .= '<p>' . __( 'What do you want to import?', 'wp-cerber' ) . '</p><p><input id="importset" name="importset" value="1" type="checkbox" checked> <label for="importset">' . __( 'Settings', 'wp-cerber' ) . '</label>';
	$form .= '<p><input id="importacl" name="importacl" value="1" type="checkbox" checked> <label for="importacl">' . __( 'Access Lists', 'wp-cerber' ) . '</label>';
	$form .= '<p><input type="submit" name="cerber_import" id="submit" class="button button-primary" value="' . __( 'Upload File', 'wp-cerber' ) . '"></p></form>';

	$form .= '<h3 style="margin-top:2em;">' . __( 'Restore Default WP Cerber Settings', 'wp-cerber' ) . '</h3>';
	$form .= '<p>' . __( 'Revert WP Cerber settings to their defaults. Your Custom Login URL and IP Access Lists will not be changed.', 'wp-cerber' ) . '</p>';
	$form .= '<p>' . __( 'To get the most out of WP Cerber, follow these steps:', 'wp-cerber' ) . ' <a target="_blank" href="https://wpcerber.com/getting-started/">Getting Stared Guide</a></p>';

	$form .= '<p>
				<input type="button" class="button button-primary" value="' . __( 'Restore Default Settings', 'wp-cerber' ) . '" onclick="button_default_settings()" />
				
				<script id="wp-cerber-js-' . crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ) . '">
				
				function button_default_settings(){
		                if (confirm( crb_admin_messages.are_you_sure )) {
			                let click_url = "' . cerber_admin_link_add( array( 'load_settings' => 'default', 'cerber_admin_do' => 'load_defaults' ) ) . '";
			                window.location = click_url.replace(/&amp;|&#038;/g,"&");
					    }
	            }
	            
	            </script>
			</p>';

	$form .= '<h3 id="crb-bulk-load-acl" style="margin-top:2em;">' . __( 'Bulk importing IP Access List entries', 'wp-cerber' ) . '</h3>';

	$form .= '<form method="post"><input type="hidden" name="acl_text" value="1">' . $nf;
	$form .= '<p><input type="radio" name="target_acl" value="W" checked="checked">' . __( 'Load entries to the Allowed IP Access List', 'wp-cerber' ) . '</p>';
	$form .= '<p><input type="radio" name="target_acl" value="B">' . __( 'Load entries to the Blocked IP Access List', 'wp-cerber' ) . '</p>';
	$form .= '<p><textarea class="crb-monospace" name="import_acl_entries" rows="8" cols="70" placeholder="Enter access list entries, one item per line. To add entry comments, use the CSV format."></textarea></p>';
	$form .= '<p><input type="submit" name="cerber_import" id="submit" class="button button-primary" value="' . __( 'Load entries', 'wp-cerber' ) . '"></p></form>';

	echo $form;
}

/**
 * Export WP Cerber settings to a file
 *
 * @return void
 */
function crb_do_export_settings() {
	global $wpdb;

	if ( ! cerber_is_http_get() || ! isset( $_GET['cerber_export'] ) ) {
		return;
	}

	if ( ! cerber_user_can_manage() ) {
		wp_die( 'Error!' );
	}

	$p = cerber_plugin_data();

	$data = array(
		'cerber_version' => $p['Version'],
		'home'           => cerber_get_home_url(),
		'date'           => date( 'd M Y H:i:s' )
	);

	if ( ! empty( $_GET['exportset'] ) ) {
		$data ['options'] = crb_get_settings();
		$data ['geo-rules'] = cerber_get_geo_rules();
	}

	if ( ! empty( $_GET['exportacl'] ) ) {
		$data ['acl'] = $wpdb->get_results( 'SELECT ip, tag, comments, acl_slice FROM ' . CERBER_ACL_TABLE, ARRAY_N );
	}

	$file = json_encode( $data );
	$file .= '==/' . strlen( $file ) . '/' . crc32( $file ) . '/EOF';

	crb_send_file_headers( 'wp-cerber-settings-' . crb_site_label() . '.config', 'text/plain' );

	echo $file;
	exit;
}

/**
 * Import plugin settings from a file
 *
 */
function crb_do_import() {
	global $wpdb;

	if ( ! isset( $_POST['cerber_import'] ) || ! cerber_is_http_post() ) {
		return;
	}

	check_admin_referer( 'crb_import', 'crb_field' );

	if ( ! cerber_user_can_manage() ) {
		wp_die( 'Import failed.' );
	}

	// Bulk load ACL
	if ( isset( $_POST['acl_text'] ) ) {
		if ( ! ( $text = crb_get_post_fields( 'import_acl_entries' ) )
		     || ! ( $tag = crb_get_post_fields( 'target_acl', false, 'W|B' ) ) ) {
			cerber_admin_notice( 'No data provided' );

			return;
		}

		$text = sanitize_textarea_field( $text );
		$list = explode( PHP_EOL, $text );
		$count = 0;

		foreach ( $list as $line ) {
			if ( ! $line ) {
				continue;
			}

			list( $ip, $comment ) = explode( ',', $line . ',', 3 );
			$ip = preg_replace( CRB_IP_NET_RANGE, ' ', $ip );
			$ip = preg_replace( '/\s+/', ' ', $ip );

			if ( ! $ip ) {
				continue;
			}

			if ( $tag == 'B' ) {
				if ( ! cerber_acl_can_be_blocked( $ip ) ) {
					cerber_admin_notice( 'Cannot be blacklisted: ' . $ip );

					continue;
				}
			}

			$comment = trim( strip_tags( stripslashes( $comment ) ) );
			$result = cerber_acl_add( $ip, $tag, $comment );

			if ( $result !== true ) {
				$msg = 'SKIPPED: ' . $ip . ' ' . $comment;
				if ( crb_is_wp_error( $result ) ) {
					$msg .= ' - ' . $result->get_error_message();
				}

				cerber_admin_notice( $msg );
			}
			else {
				$count ++;
			}
		}

		if ( $count ) {
			$msg = $count . ' access list entries were loaded. <a href="' . cerber_admin_link( 'acl' ) . '">Manage access lists</a>.';
		}
		else {
			$msg = 'No entries were loaded';
		}

		cerber_admin_message( $msg );

		return;
	}

	// Import from a file
	$ok = true;
	if ( ! is_uploaded_file( $_FILES['ifile']['tmp_name'] ) ) {
		cerber_admin_notice( __( 'No file was uploaded or file is corrupted', 'wp-cerber' ) );

		return;
	}
    elseif ( $file = file_get_contents( $_FILES['ifile']['tmp_name'] ) ) {
		$p = strrpos( $file, '==/' );
		$data = substr( $file, 0, $p );
		$sys = explode( '/', substr( $file, $p ) );
		if ( $sys[3] == 'EOF' && crc32( $data ) == $sys[2] && ( $data = json_decode( $data, true ) ) ) {

			$info = array();

			if ( isset( $_POST['importset'] )
			     && ! empty( $data['options'] )
			     && is_array( $data['options'] ) ) {

				$data['options']['loginpath'] = urldecode( $data['options']['loginpath'] ); // needed for filter cerber_sanitize_m()

				if ( $data['home'] != cerber_get_home_url() ) {
					$data['options']['sitekey'] = crb_get_settings( 'sitekey' );
					$data['options']['secretkey'] = crb_get_settings( 'secretkey' );
				}

				$save = $data['options'];

				if ( ! lab_lab() ) {
					$save = array_merge( $save, crb_get_default_pro() );
				}

				cerber_settings_update( $save, 'all' ); // @since 9.3.4

				if ( isset( $data['geo-rules'] ) && lab_lab() ) {
					update_site_option( CERBER_GEO_RULES, $data['geo-rules'] );
				}

				CRB_Issues::delete_all();
				crb_purge_settings_cache();
				cerber_upgrade_settings( $data['cerber_version'] ); // In case it was settings from an older version

				// The imported settings are in their final state: refresh the backup copy

				CRB_Settings_Backup::sync( CRB_Settings_Backup::CONTEXT_SETTINGS_IMPORT );

				$info[] = __( 'Plugin settings have been imported successfully', 'wp-cerber' );
			}

			if ( isset( $_POST['importacl'] ) ) {
				if ( ! empty( $data['acl'] )
				     && is_array( $data['acl'] ) ) {

					$acl_ok = true;
					$acl_count = 0;

					if ( false === $wpdb->query( "DELETE FROM " . CERBER_ACL_TABLE ) ) {
						$acl_ok = false;
					}

					foreach ( $data['acl'] as $row ) {
						if ( ! cerber_acl_add( $row[0], $row[1], crb_array_get( $row, 2, '' ), crb_array_get( $row, 3, 0 ) ) ) {
							$acl_ok = false;
							break;
						}
						$acl_count ++;
					}

					if ( ! $acl_ok ) {
						cerber_admin_notice( __( 'A database error occurred while importing access list entries', 'wp-cerber' ) );
					}

					cerber_acl_fixer();

					if ( $acl_count ) {
						/* translators: %d is the number of IP Access List entries imported. */
						$info[] = sprintf( __( 'IP Access List entries imported: %d', 'wp-cerber' ), $acl_count );
					}
				}
				else {
					$info[] = __( 'No IP Access List entries found in the uploaded file', 'wp-cerber' );
				}
			}

			if ( $info ) {
				$info[] = 'Source file: ' . $_FILES['ifile']['name'];
				$info[] = 'Website: ' . $data['home'];
				$info[] = 'WP Cerber version: ' . $data['cerber_version'];
				$info[] = 'Export date: ' . $data['date'];

				$info = crb_attr_escape( $info );
				cerber_admin_message( $info );
			}
		}
		else {
			$ok = false;
		}
	}

	if ( ! $ok ) {
		cerber_admin_notice( __( 'Error while parsing file', 'wp-cerber' ) );
	}
}

/**
 * @return void
 *
 * @since 8.9.6.3
 */
function crb_show_phpinfo() {
	if ( ! cerber_is_admin_page( array( 'tab' => 'diagnostic', 'cerber-show' => 'php_info' ) )
	     || ! is_super_admin() ) {
		return;
	}

	phpinfo();
	exit();
}

/**
 * Displays admin diagnostic page
 */
function cerber_show_diag_page() {
	global $cerber_diag_start;

	$cerber_diag_start = microtime( true );

	cerber_cache_enable();

	?>

    <form id="diagnostic">

		<?php

		crb_show_environment_diag();

		cerber_show_wp_diag();

		if ( $errors = CRB_Bug_Hunter::create_log_view( 'crb-sw-errors-list' ) ) {
			$cta = '<p>' . __( 'Something’s not quite right, but we’re always working to make WP Cerber more robust. Help us improve by reporting these issues here:', 'wp-cerber' ) . ' <a href="https://wpcerber.com/bug-report/" target="_blank">https://wpcerber.com/bug-report/</a></p>';
			crb_show_diag_section( 'WP Cerber Software Errors', $errors, array( 'copy_class' => 'crb-sw-errors-list', 'subtitle' => $cta ) );
		}

		crb_show_diag_section_ajax( 'Database Info' );

		$server = $_SERVER;

		// Remove sensitive data

		if ( ! empty( $server['HTTP_COOKIE'] ) ) {
			unset( $server['HTTP_COOKIE'] );
		}
		if ( ! empty( $server['HTTP_X_COOKIES'] ) ) {
			unset( $server['HTTP_X_COOKIES'] );
		}

		ksort( $server );
		$srv_vars = array();

		foreach ( $server as $key => $value ) {

			$value_view = '';

			if ( is_array( $value ) ) {
				if ( $table_element = crb_ui_table_view( $key, $value ) ) {
					$value_view = crb_ui_renderer()->render_element( $table_element );
				}
			}
			else {
				$value_view = @strip_tags( $value );
			}

			$srv_vars[] = array( $key, $value_view );
		}

		crb_show_diag_section( 'Server Environment Variables', cerber_make_plain_table( $srv_vars ) );

		$buttons = '<p style="text-align: right;">
                <a class="button button-secondary" href="' . cerber_admin_link_add( [ 'clear_up_lab_cache' => 1 ] ) . '">Clear Cache</a>
                <a class="button button-secondary cerber-page-loader-candidate crb-page-loader-force" href="' . cerber_admin_link_add( [ 'force_check_nodes' => 1 ] ) . '">Recheck Status</a>
            </p>';
		crb_show_diag_section( 'Cerber Security Cloud Status', lab_status() . $buttons );

		crb_show_diag_section( 'Maintenance Tasks', crb_maint_status() );

		if ( $report = get_site_option( '_cerber_report' ) ) {

			$rep_info = array();

			foreach ( $report as $id => $item ) {
				if ( is_numeric( $id ) ) {
					continue; // Skip OLD format
				}

				if ( $item[1] ) {
					$result = ' | Status: OK | recipients: ' . $item[1]['email'];
				}
				else {
					$result = '<span style="color: red;">Unable to send email</span>';
				}

				$rep_info[] = $id . ' | ' . cerber_ago_time( $item[0] ) . ' (' . cerber_date( $item[0] ) . ')' . $result;
			}

			crb_show_diag_section( 'Email Reporting', '<p>' . implode( '</p><p>', $rep_info ) . '</p>' );
		}

		if ( $alerts = CRB_Activity_Alerts::get_alerts_list() ) {

			$rep = '<ol>';

			foreach ( $alerts as $hash => $alert ) {

				$al_info = array();

				if ( ! empty( $alert[13] ) ) {
					if ( $alert[13] < time() ) {
						$al_info [] = 'Expired';
					}
					else {
						$al_info [] = 'Expires on ' . cerber_date( $alert[13] );
					}
				}

				if ( ! empty( $alert[11] ) ) {
					if ( $alert[11] <= $alert[12] ) {
						$al_info [] = 'Inactive (limit has reached)';
					}
					else {
						$al_info [] = 'Remains ' . ( $alert[11] - $alert[12] );
					}
				}

				if ( ! empty( $alert[14] ) ) {
					$al_info [] = 'Ignore rate limiting';
				}

				if ( ! empty( $alert[15] ) ) {
					$al_info [] = 'Email';
				}

				if ( ! empty( $alert[16] ) ) {
					$al_info [] = 'Mobile';
				}

				if ( ! empty( $alert[17] ) ) {
					if ( $us = crb_get_userdata( $alert[17] ) ) {
						$rp = ( get_current_user_id() == $alert[17] ) ? 'Your email' : ( 'User\'s email (' . $us->display_name . ')' );
						$al_info [] = $rp . ': ' . $us->user_email;
					}
					else {
						CRB_Activity_Alerts::delete_alert( $hash );
					}
				}

				if ( $al_info = implode( ' | ', $al_info ) ) {
					$al_info = ' | ' . $al_info;
				}

				$rep .= '<li>ID: ' . $hash . ' ' . $al_info . ' | <a href = "' . cerber_admin_link( crb_admin_get_tab() ) . '&amp;unsubscribeme=' . $hash . '">' . __( 'Delete', 'wp-cerber' ) . '</a></li>';
			}

			$rep .= '</ol>';

			$rep .= '<p><a target="_blank" href="https://wpcerber.com/wordpress-notifications-made-easy/">Read more on alerts and notifications</a></p>';

			crb_show_diag_section( 'Alerts', $rep );
		}

		if ( $status = CRB_DS::get_status() ) {
			crb_show_diag_section( 'Data Shield Status', '<ul><li>' . implode( '</il><li>', $status ) . '</li></ul>' );
		}

		crb_show_diag_section( 'WP Cerber Cache', '<p style="text-align: right;"><a class="button button-secondary" href="' . cerber_admin_link_add( [ 'clear_up_the_cache' => 1 ] ) . '">Clear</a></p>' );

		?>
    </form>
	<?php
}

/**
 * Renders and outputs a section on the Diagnostic admin page.
 *
 * This function generates a section with a title, optional subtitle, and HTML content.
 * Optionally, it can enable a "Copy to Clipboard" feature for elements within the section.
 *
 * @param string $title The plain text title displayed at the top of the section.
 * @param string $content The HTML-formatted content displayed within the section.
 * @param array $args Optional parameters:
 *
 * @type string $subtitle An optional subtitle displayed below the title.
 * @type string $copy_class The CSS class name for elements that can be copied to the clipboard.
 *                              If provided, a "Copy to Clipboard" link is rendered, allowing users
 *                              to copy text content from elements with this class.
 *
 * @return void
 */
function crb_show_diag_section( string $title, string $content, array $args = [] ) {
	global $cerber_diag_start;
	static $previous;

	if ( ! $previous ) {
		$previous = $cerber_diag_start;
	}

	$took_time = 1000 * ( microtime( true ) - $previous );

	$copy = isset( $args['copy_class'] ) ? crb_copy_to_clipboard( $args['copy_class'], false ) : '';
	$header = crb_generate_html_flex( array( '<h3>' . $title . '</h3>', $copy ) );

	$subtitle = $args['subtitle'] ?? '';

	echo '<div class="crb-diag-section" data-took_time="' . $took_time . '">' . $header . $subtitle . '<div class="crb-diag-inner">' . $content . '</div></div>' . "\n";

	$previous = microtime( true );
}

/**
 * AJAX section
 *
 * @param string $title
 */
function crb_show_diag_section_ajax( string $title ) {
	echo '<div class="crb-diag-section"><h3>' . $title . '</h3><div class="crb-diag-inner crb_async_content" data-ajax_route="diagnostic_tools"></div></div>';
}

/**
 * Render the license key admin screen with a form and diagnostic information.
 *
 * @return void
 */
function cerber_show_lic() {
	$key = lab_get_key();
	$valid = '';
	$site_ip_row = '';
	if ( ! empty( $key[2] ) ) {
		$lic = $key[2];
		if ( lab_validate_lic( $lic, $message, $site_ip ) ) {
			$valid = '
                <p><span style="color: green;">This key is valid until ' . $message . '</span></p>
                <p>To move the key to another website or web server, please follow these steps: <a href="https://my.wpcerber.com/how-to-move-license-key/" target="_blank" rel="noopener noreferrer">https://my.wpcerber.com/how-to-move-license-key/</a></p>';
		}
		else {
			$message = crb_escape_html( $message );
			$valid = '<p><span style="color: red;">This license key is invalid or expired.</span></p>
			<p>If you believe this key is valid, please follow these steps: <a href="https://my.wpcerber.com/how-to-fix-invalid-or-expired-key/" target="_blank" rel="noopener noreferrer">https://my.wpcerber.com/how-to-fix-invalid-or-expired-key/</a></p>
			<p>Error Code:' . $message . '</p>';
		}

		if ( $site_ip ) {
			$site_ip_row = '<tr>
                <th scope="row">Site IP Address</th>
                <td><p class="crb-monospace">' . $site_ip . '</p>
                </td>
            </tr>';
		}
	}
	else {
		$lic = '';
	}
	?>
    <form method="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">License key</th>
                <td>
                    <input name="cerber_license" value="<?php echo $lic; ?>" size="<?php echo LAB_KEY_LENGTH; ?>" maxlength="<?php echo LAB_KEY_LENGTH; ?>" type="text" class="crb-monospace" placeholder="Enter your license key here">
					<?php echo $valid; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Site ID</th>
                <td>
					<?php echo '<p class="crb-monospace">' . $key[0] . '</p>'; ?>
                </td>
            </tr>
			<?php echo $site_ip_row; ?>
            <tbody>
        </table>
        <div style="padding-left: 220px">
            <input type="hidden" name="cerber_admin_do" value="install_key">
			<?php
			cerber_nonce_field( 'control', true );
			submit_button( __( 'Save', 'wp-cerber' ) );
			?>
        </div>
    </form>
	<?php
}

/**
 * Generates system and WordPress info reports
 *
 * @return void
 */
function cerber_show_wp_diag() {
	global $wpdb;

	$reports = array();

	// System Info report

	$tz = date_default_timezone_get();
	$tz_element = ( $tz !== 'UTC' ) ? crb_ui_element( 'span', [ 'style' => 'color: red;' ], $tz ) : $tz;

	if ( $c = CRB_Cache::checker() ) {

		$builder = CRB_UI_Content_Builder::create()
		                                 ->add_text( 'Yes | ' . cerber_date( $c, false ) . ' (' . cerber_ago_time( $c ) . ') ' );

		if ( $stat = CRB_Cache::get_stat( true ) ) {
			$builder->add_text( ' | Cerber\'s entries: ' . count( $stat[1] ) . ' | ' )
			        ->add_element( crb_ui_element( 'confirmation_link', [], null, [ 'label' => 'Clear the cache', 'href' => _crb_admin_link_add( [ 'cerber_admin_do' => 'clear_cache' ] ) ] ) );
		}

		$cache_info = $builder->to_element( 'span' );
	}
	else {
		$cache_info = 'Not detected';
	}

	if ( $disabled = @ini_get( 'disable_functions' ) ) {
		$disabled = str_replace( ',', ', ', $disabled );
	}

	$opt = ( is_multisite() ) ? $wpdb->sitemeta : $wpdb->options;

	$curl_version = curl_version();
	$libcurl_info = $curl_version['version'];
    if ( $curl_version['version_number'] < 0x073B00 ) {
	    $libcurl_info .= ' — update to 7.59.0+ recommended for better IPv6/IPv4 handling';
    }

	$sys_report = [
		[ 'Web Server', $_SERVER['SERVER_SOFTWARE'] ],
		[ 'PHP version', phpversion() ],
		[ 'Server API', PHP_SAPI ],
		[ 'Server platform', PHP_OS ],
		[ 'Memory limit', @ini_get( 'memory_limit' ) ],
		[ 'Default PHP timezone', $tz_element ],
		[ 'Disabled PHP functions', $disabled ],
		[ 'WordPress version', cerber_get_wp_version() ],
		[ 'WordPress locale', cerber_get_wp_locale() ],
		[ 'WordPress options DB table', $opt ],
		[ 'MySQLi', crb_ui_status_span( function_exists( 'mysqli_connect' ) ) ],
		[ 'MySQL Native Driver (mysqlnd)', crb_ui_status_span( function_exists( 'mysqli_fetch_all' ) ) ],
		[ 'libcurl version', $libcurl_info ],
		[ 'PHP allow_url_fopen', ini_get( 'allow_url_fopen' ) ? 'Enabled' : 'Disabled' ],
		[ 'PHP allow_url_include', crb_ui_status_span( ! ini_get( 'allow_url_include' ), 'Disabled', 'Enabled' ) ],
		[ 'Persistent object cache', $cache_info ],
		[ 'Loaded php.ini file', php_ini_loaded_file() ?: 'Unknown' ],
		[
			'Detailed PHP information',
			crb_ui_link( _crb_admin_link_add( [ 'cerber-show' => 'php_info' ] ), 'View phpinfo()', [ 'target' => '_blank' ] )
		],
	];

	if ( cerber_get_site_url() != cerber_get_home_url()
	     || 2 < substr_count( cerber_get_site_url(), '/' ) ) {
		$sys_report[] = [ 'Subdirectory WP installation', 'YES' ];
		$sys_report[] = [ 'Site URL', cerber_get_site_url() . '/' ];
		$sys_report[] = [ 'Home URL', cerber_get_home_url() . '/' ];
	}

	if ( nexus_is_valid_request() ) {
		$sys_report[] = [ 'The IP address of the main website is detected as', cerber_get_remote_ip() ];
	}
	else {
		$sys_report[] = [
			'Your detected IP address',
			crb_ui_element( 'div', [], [
				cerber_get_remote_ip() . ' — this IP address must match the one displayed on this page: ',
				crb_ui_link( 'https://wpcerber.com/what-is-my-ip/', 'What Is My IP Address', [ 'target' => '_blank' ] ),
				')',
			] )
		];
	}

	$reports[] = [ 'title' => 'System Info', 'content' => crb_ui_make_plain_table( $sys_report ) ];

	// Folders and System Files report

	$folder = cerber_get_my_folder();

	if ( crb_is_wp_error( $folder ) ) {
		$folder = $folder->get_error_message();
	}
	else {
		$folder .= 'quarantine' . DIRECTORY_SEPARATOR;
	}

	if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
		$wp_config = ABSPATH . 'wp-config.php';
	}
    elseif ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
		$wp_config = dirname( ABSPATH ) . '/wp-config.php';
	}
	else {
		$wp_config = 'Error. No config file found.';
	}

	$folders_data = [
		[ 'WordPress root directory (ABSPATH) ', ABSPATH ],
		[ 'WordPress uploads directory', cerber_get_upload_dir() ],
		[ 'WordPress content directory', dirname( cerber_get_plugins_dir() ) ],
		[ 'WordPress plugins directory', cerber_get_plugins_dir() ],
		[ 'WordPress themes directory', cerber_get_themes_dir() ],
		[ 'WordPress must-use plugin directory (WPMU_PLUGIN_DIR) ', WPMU_PLUGIN_DIR ],
		[ 'WordPress config file', $wp_config ],
		[ 'Server directory for temporary files', sys_get_temp_dir() ],
		[ 'PHP directory for uploading files', ini_get( 'upload_tmp_dir' ) ],
		[ 'PHP directory for user session data', session_save_path() ],
		[ 'WP Cerber\'s quarantine directory', $folder ],
		[ 'WP Cerber\'s diagnostic log', cerber_get_diag_log() ]
	];

	if ( file_exists( ABSPATH . '.htaccess' ) ) {
		$folders_data[] = array( 'Main .htaccess file', ABSPATH . '.htaccess' );
	}

	$folders_data = crb_populate_fs_entries( $folders_data );

	$folders_data[] = array( 'Directory separator', DIRECTORY_SEPARATOR );

	$reports[] = [ 'title' => 'File System', 'content' => crb_ui_make_plain_table( $folders_data ) ];

	// Multisite Info report, if applicable

	if ( is_multisite() ) {
		$mu_data = array();
		$mu_folders = array();

		if ( defined( 'UPLOADS' ) ) {
			$mu_data[] = array( 'Constant UPLOADS defined', UPLOADS, '', '' );
			$mu_folders = array( UPLOADS, ABSPATH . UPLOADS );
		}
		if ( defined( 'BLOGUPLOADDIR' ) ) {
			$mu_data[] = array( 'Constant BLOGUPLOADDIR defined', BLOGUPLOADDIR, '', '' );
			$mu_folders = array( BLOGUPLOADDIR, BLOGUPLOADDIR );
		}
		if ( defined( 'UPLOADBLOGSDIR' ) ) {
			$mu_data[] = array( 'Constant UPLOADBLOGSDIR defined', UPLOADBLOGSDIR, '', '' );
			$mu_folders = array( UPLOADBLOGSDIR, ABSPATH . UPLOADBLOGSDIR );
		}

		$mupl = cerber_get_upload_dir_mu();
		$mu_folders[] = array( 'Uploads folder for websites', $mupl ?: 'Not found' );

		$mu_folders = crb_populate_fs_entries( $mu_folders );
		$mu_data = array_merge( $mu_data, $mu_folders );

		$reports[] = [ 'title' => 'Multisite Info', 'content' => crb_ui_make_plain_table( $mu_data ) ];
	}

	// WP Cerber constants report

	$crb_constants_data = [];

	foreach ( cerber_get_constants() as $constant ) {
		if ( defined( $constant ) ) {
			$crb_constants_data[] = [ $constant, constant( $constant ) ];
		}
	}

	if ( $crb_constants_data ) {
		$reports[] = [ 'title' => 'Defined WP Cerber Constants', 'content' => crb_ui_make_plain_table( $crb_constants_data ) ];
	}

	// Plugins report

	$plugins_data = [];
	$active_plugins = get_option( 'active_plugins' );

	foreach ( $active_plugins as $plugin_file ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
		$plugins_data[] = [ $plugin_data['Name'], $plugin_data['Version'] ];
	}

	$reports[] = [ 'title' => 'Active Plugins', 'content' => crb_ui_make_plain_table( $plugins_data ) ];

	// Display all diagnostic sections

	foreach ( $reports as $report_item ) {
		$diag_section = crb_ui_make_diag_section( $report_item['title'], $report_item['content'] );
		echo crb_ui_renderer()->render_element( $diag_section );
	}
}

/**
 * Populate each filesystem entry with its access status and permission bits.
 *
 * 'Not writable' reflects process-level writability only, not the breadth of the
 * permission bits. A path may carry wide bits (e.g. 0777) yet still report 'Not writable'
 * on a read-only mount or under an immutable attribute, ACL, or MAC policy.
 *
 * @param array<int, array{0: string, 1: string, 2?: string, 3?: string}> $fs_entries
 *
 * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
 *     Same entries with index 2 set to 'Writable', 'Not writable', 'Not found', or
 *     'Access denied', and index 3 set to the octal permission string or '' when absent.
 */
function crb_populate_fs_entries( $fs_entries ) {
	foreach ( $fs_entries as &$entry ) {
		$entry[2] = '';
		$entry[3] = '';

		// Path is present and accessible: report writability and permission bits
		if ( @file_exists( $entry[1] ) ) {
			$entry[2] = wp_is_writable( $entry[1] ) ? 'Writable' : 'Not writable';
			$entry[3] = cerber_get_chmod( $entry[1] );

			continue;
		}

		// Path is absent: distinguish a missing target from an unreadable parent
		$parent = dirname( $entry[1] );
		$entry[2] = ( @is_dir( $parent ) && @is_readable( $parent ) ) ? 'Not found' : 'Access denied';
	}

	unset( $entry );

	return $fs_entries;
}

/**
 * Generates plain HTML table for internal use. Cell values must be escaped.
 *
 * @param array $table_rows Table body rows. Values must be escaped.
 * @param array $table_header Table header cells. Values must be escaped.
 * @param bool $first_header If true highlight values in the first column
 * @param bool $eq
 *
 * @return string HTML code of the table
 */
function cerber_make_plain_table( array $table_rows, array $table_header = [], bool $first_header = false, bool $eq = false ) {
	$class = 'crb-monospace ';

	if ( $first_header ) {
		$class .= ' crb-plain-fh ';
	}

	if ( ! $eq ) {
		$class .= ' crb-plain-fcw ';
	}

	$ret = '<div class="crb-plain-table"><table class="' . $class . '">';

	if ( $table_header ) {
		$ret .= '<tr class="crb-plain-header"><td>' . implode( '</td><td>', $table_header ) . '</td></tr>';
	}

	foreach ( $table_rows as $row ) {

		$bottom_row = '';

		if ( $err = $row['error'] ?? '' ) {
			unset( $row['error'] );
			$bottom_row = '<tr class="crb-error"><td colspan="' . count( $row ) . '">' . $err . '</td></tr>';
		}

		if ( ! is_array( $row ) ) {
			$row = array( $row );
		}

		$ret .= '<tr><td>' . implode( '</td><td>', $row ) . '</td></tr>' . $bottom_row;
	}

	$ret .= '</table></div>';

	return $ret;
}

/*
 * Create database diagnostic report
 *
 *
 */
function cerber_db_diag() {
	global $wpdb;

	$ret = array();

	$db_info = array();

	$db_info[] = array( 'Database server', ( $v = cerber_db_get_var( "SELECT VERSION()" ) ) ? $v : 'Unknown' );

	$db_info[] = array( 'Database name', DB_NAME );

	$var = crb_get_mysql_var( 'innodb_buffer_pool_size' );
	$pool_size = round( $var / 1048576 );
	$inno = $pool_size . ' MB';
	if ( $pool_size < 16 ) {
		$inno .= ' Your pool size is extremely small!';
	}
    elseif ( $pool_size < 64 ) {
		$inno .= ' It seems your pool size is too small.';
	}
	$db_info[] = array( 'InnoDB buffer pool size', $inno );

	$var = crb_get_mysql_var( 'max_allowed_packet' );
	$db_info[] = array( 'Max allowed packet size', round( $var / 1048576 ) . ' MB' );

	$db_info[] = array( 'Charset', $wpdb->charset );
	$db_info[] = array( 'Collate', $wpdb->collate );

	$ret[] = cerber_make_plain_table( $db_info );

	$ret[] = cerber_table_info( CERBER_LOG_TABLE );
	$ret[] = cerber_table_info( CERBER_ACL_TABLE );
	$ret[] = cerber_table_info( CRB_LOCKOUT_TABLE );
	$ret[] = cerber_table_info( CERBER_TRAF_TABLE );

	$err = '';
	if ( $errors = get_site_option( '_cerber_db_errors' ) ) {
		$err = '<p style="color: #DF0000;">Some minor DB errors were detected</p><textarea>';
		foreach ( $errors as $error ) {
			$err .= $error[0] . "\n" . $error[1] . "\n" . cerber_auto_date( $error[2], false ) . "\n------------------------\n";
		}
		$err .= '</textarea>';
		update_site_option( '_cerber_db_errors', '' );
	}

	$action = cerber_admin_link( 'diagnostic', array( 'force_repair_db' => 1 ), true, false );

	return $err . implode( '<br />', $ret ) . '<p style="text-align: right;"><a class="button button-secondary" href="' . $action . '">Repair Cerber\'s Tables</a></p>';
}

/**
 * Creates mini report about given database table
 *
 * @param string $db_table Database table name
 *
 * @return string HTML report
 */
function cerber_table_info( string $db_table ): string {

	if ( ! cerber_db_is_table( $db_table ) ) {
		return '<p style="color: #DF0000;">ERROR. Database table ' . $db_table . ' not found! Click the repair button below.</p>';
	}

	//-------------

	$db_result = warp_get_db();

	if ( $db_result->has_errors() ) {
		return '<p style="color: #DF0000;">ERROR. Unable to obtain a database connection.</p>';
	}

	/** @var CRB_Database $db */
	$db = $db_result->get_results();

	$schema = new CRB_Schema_Manager( $db );

	$columns_result = $schema->get_metadata(
		CRB_Schema_Manager::META_FULL_COLUMNS,
		array( 'table' => $db_table )
	);

	$db_table_columns = $columns_result->has_errors() ? [] : $columns_result->get_results( [] );
	$db_table_indexes = crb_get_db_table_index( $db_table );

	$header = array( 'Field', 'Type', 'Collation', 'Index' );
	$colums_report_rows = array();
	foreach ( $db_table_columns as $column_info ) {
		$column_info = crb_normalize_to_array_deep( $column_info );
		$column_name = array_shift( $column_info );
		$type = array_shift( $column_info );
		$collation = array_shift( $column_info );

		$col_indexes = $db_table_indexes[ $column_name ] ?? array();
		$index_info = '';
		if ( $col_indexes ) {
			$index_info = 'Yes';
			$index_info .= ( ( min( array_column( $col_indexes, 'Non_unique' ) ) ) > 0 ) ? '' : '/U';
		}

		$colums_report_rows[] = array( $column_name, $type, $collation, $index_info );
	}
	$columns_html = cerber_make_plain_table( $colums_report_rows, $header );

	//-------------
	$rows_total_result = $db->table( $db_table )->select_count( '*', 'rows_total' )->get_value( 'rows_total' );
	$rows_total = $rows_total_result->has_errors() ? 0 : absint( $rows_total_result->get_results( 0 ) );

	// ------------
	$table_status_result = $schema->get_metadata(
		CRB_Schema_Manager::META_TABLE_STATUS,
		array( 'name' => $db_table )
	);

	$db_table_status = $table_status_result->has_errors() ? [] : $table_status_result->get_results( [] )[0] ?? [];

	$status_report_rows = array();
	foreach ( $db_table_status as $key => $value ) {
		$status_report_rows[] = array( $key, $value );
	}
	$status_html = cerber_make_plain_table( $status_report_rows, [], true );

    //-------------
	$truncate_link = '';
	if ( $rows_total ) {
		$truncate_link = crb_confirmation_link( _crb_admin_link_add( [ 'truncate' => $db_table ] ), 'Delete all rows', __( 'Confirm emptying the table. This operation is permanent and cannot be undone.', 'wp-cerber' ), 'crb-button-tiny' );
	}

	return '<p style="font-size: 110%;">Table: <b>' . $db_table . '</b>, rows: ' . $rows_total . ' ' . $truncate_link . '</p><table class="diag-table"><tr><td class="diag-td">' . $columns_html . '</td><td class="diag-td">' . $status_html . '</td></tr></table>';
}

/**
 * Retrieves the BTREE index information for columns in a specified database table.
 *
 * @param string $db_table The name of the database table for which to retrieve column index information.
 *
 * @return array An associative array where each key is a column name and the value is an array of index details for that column.
 */
function crb_get_db_table_index( string $db_table ): array {
	static $cache = array();

	if ( ! isset( $cache[ $db_table ] ) ) {

		$columns_result = warp_run_db(
			static function ( CRB_Database $db ) use ( $db_table ): Revalt {
				$schema = new CRB_Schema_Manager( $db );

				return $schema->get_metadata(
					CRB_Schema_Manager::META_INDEXES,
					array( 'table' => $db_table )
				);
			}
		);

		$cache[ $db_table ] = $columns_result->has_errors() ? [] : $columns_result->get_results( [] );
	}

	$table_indexes = [];

	foreach ( $cache[ $db_table ] as $index_info ) {
		$row = [];

        // Make keys canonical
        foreach ( $index_info as $k => $v ) {
			$row[ ucfirst( strtolower( $k ) ) ] = $v;
		}

		if ( empty( $row['Column_name'] )
		     || 'BTREE' !== ( $row['Index_type'] ?? '' ) ) {
			continue;
		}
		$table_indexes[ $row['Column_name'] ][] = $row;
	}

	return $table_indexes;
}

/**
 * Checks the server environment for possible issues and creates a diagnostic report section.
 *
 * @return void
 */
function crb_show_environment_diag() {

	$issues = [];

	// Check the PHP runtime version.
	if ( version_compare( '7.4', phpversion(), '>' ) ) {
		$issues[] = crb_ui_element(
			'p',
			[],
			crb_ui_formatted_text(
				'Your website runs on an outdated (unsupported) version of PHP which is %1$s. We strongly encourage you to upgrade PHP to a newer version. See more at: %2$s',
				[
					phpversion(),
					crb_ui_link( 'https://www.php.net/supported-versions.php', 'https://www.php.net/supported-versions.php', [ 'target' => '_blank' ] ),
				]
			)
		);
	}

	// Check the PHP response-code helper required by plugin flows.
	if ( ! function_exists( 'http_response_code' ) ) {
		$issues[] = crb_ui_element(
			'p',
			[],
			crb_ui_formatted_text(
				'The PHP function %1$s is not found or disabled.',
				[ crb_ui_element( 'b', [], 'http_response_code()' ) ]
			)
		);
	}

	// Check the multibyte string extension used for UTF-8 safe text handling.
	if ( ! function_exists( 'mb_convert_encoding' ) ) {
		$issues[] = crb_ui_element(
			'p',
			[],
			crb_ui_formatted_text(
				'A PHP extension %1$s is not enabled on your website. Some plugin features will not work properly. You need to enable the PHP mbstring extension (multibyte strings support) in your hosting control panel.',
				[ crb_ui_element( 'b', [], 'mbstring' ) ]
			)
		);
	}

	// Check the server request timestamp needed for precise request timing.
	if ( ! isset( $_SERVER['REQUEST_TIME_FLOAT'] ) || ! is_numeric( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
		$issues[] = crb_ui_element(
			'p',
			[],
			crb_ui_formatted_text(
				'The server environment variable %1$s is not set correctly.',
				[ crb_ui_element( 'b', [], '$_SERVER[\'REQUEST_TIME_FLOAT\']' ) ]
			)
		);
	}

	// Check whether WP Cerber can resolve client IP addresses.
	if ( cerber_get_remote_ip() === CRB_REMOTE_IP_UNAVAILABLE ) {
		$issues[] = crb_ui_element(
			'p',
			[],
			crb_ui_formatted_text(
				'WP Cerber is unable to detect IP addresses correctly. How to fix: %1$s',
				[ crb_ui_link( 'https://wpcerber.com/wordpress-ip-address-detection/', 'Solving problem with incorrect IP address detection' ) ]
			)
		);
	}

	if ( empty( $issues ) ) {
		return;
	}

	// Render the diagnostic issue set without an intermediate content builder chain.
	$issues_container = crb_ui_element( 'div', [], crb_ui_element_set( 'p', $issues ) );

	$section_title = 'Some issues have been detected. They can affect plugin functionality.';
	$diag_section = crb_ui_make_diag_section( $section_title, $issues_container );

	echo crb_ui_renderer()->render_element( $diag_section );
}


/**
 * Generates maintenance diagnostic report
 *
 * @return string
 */
function crb_maint_status(): string {
	$ret = cerber_cron_diag();

	if ( crb_get_settings( 'cerber_sw_repo' )
	     && $last = cerber_get_set( 'last_update_check' ) ) {

		$status = array();

		if ( $err = $last['error'] ?? '' ) {
			$status[] = 'Last plugin update check failed: ' . $err;
		}

		if ( $time = $last['success'] ?? '' ) {
			$status[] = 'Last plugin update check: ' . cerber_auto_date( $time );
		}

		if ( $status = array_filter( $status ) ) {
			$ret .= '<p>' . implode( '</p><p>', $status ) . '</p>';
		}

	}

	return $ret;
}

/**
 * Generates cron/background tasks status report
 *
 * @return string
 */
function cerber_cron_diag(): string {

	$planned = array();
	$crb_crons = array(
		'cerber_hourly_1' => 'Hourly task #1',
		'cerber_hourly_2' => 'Hourly task #2',
		'cerber_daily'    => 'Daily task',
		//'cerber_bg_launcher' => 'Background tasks'
	);
	foreach ( _get_cron_array() as $time => $item ) {
		foreach ( $crb_crons as $key => $val ) {
			if ( ! empty( $item[ $key ] ) ) {
				$planned[ $key ] = $val . ' scheduled for ' . cerber_date( $time ) . ' (' . cerber_ago_time( $time ) . ')';
			}
		}
	}

	unset( $crb_crons['cerber_daily'] );
	$crb_crons['cerber_daily_1'] = 'Daily task';

	$errors = array();
	$ok = array();
	$no_cron = false;
	foreach ( $crb_crons as $key => $task ) {
		$h = get_site_transient( $key );
		if ( ! $h || ! is_array( $h ) ) {
			$errors[] = $task . ' has never been executed';
			if ( $oldest = cerber_db_get_var( 'SELECT MIN(stamp) FROM ' . CERBER_LOG_TABLE ) ) {
				if ( $oldest < ( time() - 24 * 3600 ) ) {
					$no_cron = true;
				}
			}
			continue;
		}
		if ( empty( $h[1] ) ) {
			$errors[] = $task . ' has not finished correctly';
			continue;
		}
		$end = $h[1];
		/*
		if ( $end < ( time() - 2 * 3600 ) ) {
			$errors[] = $val . ' has been executed ' . cerber_ago_time( $end );
		}
		else {
			$ok[] = $val . ' has been executed ' . cerber_ago_time( $end );
		}
		*/
		$dur = $end - $h[0];
		if ( $dur > 60 ) {
			$errors[] = $task . ' has been executed ' . cerber_ago_time( $end ) . ' and it took ' . $dur . ' seconds.';
		}
		else {
			$ok[] = $task . ' has been executed ' . cerber_ago_time( $end ) . ' and it took ' . $dur . ' seconds.';
		}
	}

	$ret = '';

	if ( $errors ) {
		$ret .= '<p style="color: red;">' . implode( '<br/>', $errors ) . '</p>';
	}
	if ( $ok ) {
		$ret .= '<p>' . implode( '<br/>', $ok ) . '</p>';
	}
	if ( $planned ) {
		$ret .= '<p>' . implode( '<br/>', $planned ) . '</p>';
	}

	$num = 0;
	if ( $bg = CRB_Deferred_Tasks::get_all() ) {
		$num = count( $bg );
	}
	$ret .= '<p>Background tasks: ' . $num . '</p>';

	if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
		$ret .= '<p>Note: the internal WordPress cron launcher is disabled on this site.</p>';
		if ( $no_cron ) {
			$ret .= '<p>An external cron launcher has not been configured or does not work properly.</p>';
		}
	}

	return $ret;
}

function cerber_show_diag_log() {
	$file = cerber_get_diag_log();

	if ( ! is_file( $file ) ) {
		echo '<p>' . __( 'The log file has not been created yet.', 'wp-cerber' ) . '</p>';

		return;
	}

	if ( ! $fs = filesize( $file ) ) {
		echo '<p>' . __( 'The diagnostic log file is empty.', 'wp-cerber' ) . '</p>';

		return;
	}

	$reverse_log = crb_get_query_params( 'reverse_log', '\d' );

	$clear = crb_confirmation_link( _crb_admin_link_add( array(
		'cerber_admin_do' => 'manage_diag_log',
		'do_this'         => 'clear_it',
	) ), __( 'Clear log', 'wp-cerber' ) );

	$dnl = '<a href="' . cerber_admin_link_add( array(
			'cerber_admin_do' => 'export',
			'type'            => 'get_diag_log',
		) ) . '">' . __( 'Download log', 'wp-cerber' ) . '</a>';

	$reverse = '<a href="' . cerber_admin_link_add( array(
			'reverse_log' => ( $reverse_log ) ? 0 : 1,
		), false ) . '">' . __( 'View log in reverse', 'wp-cerber' ) . '</a>';

	// Log file changes

	$mtime = cerber_get_date( $file );

	$meta = get_user_meta( get_current_user_id(), 'clast_log_view', true );

	if ( ! is_array( $meta ) ) {
		$meta = array();
	}

	$change = $meta['last_change'][ $mtime ] ?? '';

	if ( ! $change ) {
		$bytes = (int) ( $fs - ( $meta['size'] ?? $fs ) );
		$change = ( 0 != $bytes ) ? '( ' . sprintf( "%+d", $bytes ) . ' bytes)' : '';
	}

	$lupd = cerber_auto_date( $mtime ) . ' ' . $change;

	unset( $meta['last_change'] ); // Delete outdated entries
	$meta['last_change'][ $mtime ] = $change;
	$meta['size'] = $fs;

	update_user_meta( get_current_user_id(), 'clast_log_view', $meta );

	echo '<div id="crb-log-nav"><div>Size: <b>' . number_format( $fs, 0, ' ', ' ' ) . ' bytes</b> &nbsp;|&nbsp; Last update: <b>' . $lupd . '</b></div><div>[ ' . $reverse . ' &nbsp;|&nbsp; ' . $dnl . ' &nbsp;|&nbsp; ' . $clear . ' ]</div></div>';

	if ( empty( $reverse_log ) ) {
		$log = @fopen( $file, 'r' );
		$text = fread( $log, 10000000 );
		if ( ! $text ) {
			return;
		}

		fclose( $log );

		echo '<div id="crb-log-viewer"><pre>';

		echo nl2br( htmlspecialchars( $text, ENT_SUBSTITUTE ) );

		echo '</pre></div>';

		unset( $text );
	}
	else {
		$lines = file( $file );
		if ( ! $lines ) {
			return;
		}

		echo '<div id="crb-log-viewer"><pre>';

		for ( $i = count( $lines ) - 1; $i >= 0; $i -- ) {
			echo htmlspecialchars( $lines[ $i ], ENT_SUBSTITUTE ) . '<br/>';
		}

		echo '</pre></div>';

		unset( $lines );
	}

}

function cerber_manage_diag_log( $operation ) {
	if ( $operation == 'clear_it' ) {
		crb_truncate_diagnostic_log( 0 );
	}
    elseif ( $operation == 'download' ) {
		crb_send_file_headers( 'wpcerber.log', 'text/plain', true );
		readfile( cerber_get_diag_log() );
		exit;
	}
}

function cerber_show_change_log() {

	// Render the two informational callouts through the UI Factory.
	echo crb_ui_renderer()->render_element( crb_ui_fragment( [
		crb_ui_element( 'div', [ 'class' => 'crb-changelog-callout-primary' ], [
			crb_ui_element( 'h3', [ 'style' => 'color: #fff' ], __( 'You might have missed new features', 'wp-cerber' ) ),
			crb_ui_element( 'p', [], __( 'WP Cerber is continuously developed, with approximately 12 releases every year. They regularly introduce new features, performance optimizations, and bug fixes. We encourage you to review the changelog to get information on the latest release, as well as previous updates.', 'wp-cerber' ) ),
		] ),
		crb_ui_element( 'div', [ 'class' => 'crb-changelog-callout-secondary' ],
			crb_ui_element( 'p', [], crb_ui_formatted_text(
				/* translators: %1$s is a link to the WP Cerber account on X (formerly Twitter). */
				__( 'For more detailed information on each update, we recommend reading the release notes on wpcerber.com, as some releases can include breaking changes that might affect how WP Cerber works on your site. To stay informed about the latest updates, %1$s.', 'wp-cerber' ),
				[ crb_ui_link( 'https://twitter.com/wpcerber', __( 'follow us on X', 'wp-cerber' ), [ 'target' => '_blank', 'rel' => 'noopener noreferrer', 'style' => 'color: yellow;' ] ) ]
			) )
		),
	] ) );

	$changelog_lines = cerber_parse_change_log();

	if ( $changelog_lines ) {

		// Interleave a <br> between lines, mirroring the previous implode( '<br/>', ... ).
		$view_children = [];

		foreach ( $changelog_lines as $line_index => $changelog_line ) {
			if ( $line_index > 0 ) {
				$view_children[] = crb_ui_element( 'br' );
			}

			$view_children[] = $changelog_line;
		}
	}
	else {
		$view_children = 'File changelog.txt not found';
	}

	echo crb_ui_renderer()->render_element(
		crb_ui_element( 'div', [ 'id' => 'crb-changelog-view', 'class' => '' ], $view_children )
	);
}

/**
 * The list of all WP Cerber constants
 *
 * @return string[]
 *
 * @since 9.5.1
 */
function cerber_get_constants() {
	return array(
		'CERBER_FOLDER_PATH',
		'CERBER_DIAG_DIR',
		'CERBER_DISABLE_SPAM_FILTER',
		'CERBER_IP_KEY',
		'CERBER_EXPORT_CHUNK',
		'CERBER_FULL_URI',
		'CERBER_FAIL_LOG',
		'CERBER_LOG_FACILITY',
		'CERBER_LOG_TIMEZONE',
		'CERBER_HUB_UA',
		'CERBER_WP_OPTIONS',
		'CERBER_OLD_LP' // Deprecated
	);
}

/**
 * Generates the Cerber Security Cloud Status diagnostic report
 *
 * @return string Report to show in the Dashboard
 */
function lab_status() {

	$ret = '';

	if ( ! crb_get_settings( 'cerberlab' ) && ! lab_lab() ) {
		$ret .= '<p style = "color:red;"><b>Connection to Cerber Security Cloud is disabled</b></p>';
	}

	$nodes = lab_get_nodes();
	if ( empty( $nodes['nodes'] ) ) {
		return $ret . '<p>No diagnostic information available. No requests have been made yet.</p>';
	}

	$tb = array();
	ksort( $nodes['nodes'] );

	foreach ( $nodes['nodes'] as $id => $node ) {

		$last = $node['last'];
		$node_ip = $last[5]; // $curl_info['primary_ip']
		$node_host = $last['node_host'] ?? '';
		$net_error = (string) $last[2];

		if ( $net_error
		     && ( ! cerber_is_ip( $node_ip ) || is_ip_private( $node_ip ) ) ) {
			$net_error .= 'Unable to resolve the IP address of the node. Check DNS configuration on your web server: ask the admin of your web server for assistance.';
		}

		$delay = round( 1000 * $last[0] ) . ' ms';
		$ago = cerber_ago_time( $last[3] );

		$status = $last[1];

		if ( $status ) {
			$status = '<span style = "color:green;">' . $status . '</span>';
		}
		else {
			$status = 'Down';
			$delay = 'Unknown';
		}

		if ( $country = lab_get_country( $node_ip, false ) ) {
			$country = crb_get_country_name( $country );
		}
		else {
			$country = '';
		}

		$node_ipv4 = cerber_is_ipv4( $node_ip ) ? $node_ip : '';
		$node_ipv6 = cerber_is_ipv6( $node_ip ) ? $node_ip : '';

		if ( ! $node_ipv4 ) {

			if ( $dns_records_v4 = @dns_get_record( $node_host, DNS_A ) ) {

				foreach ( $dns_records_v4 as $record ) {
					if ( isset( $record['ip'] ) ) {
						$node_ipv4 = $record['ip'];
						break;
					}
				}
			}

			if ( ! $node_ipv4 ) {
				$node_ipv4 = 'Unknown';
			}
		}

		if ( ! $node_ipv6 ) {

			if ( $dns_records_v6 = @dns_get_record( $node_host, DNS_AAAA ) ) {

				foreach ( $dns_records_v6 as $record ) {
					if ( isset( $record['ipv6'] ) ) {
						$node_ipv6 = $record['ipv6'];
						break;
					}
				}
			}

			if ( ! $node_ipv6 ) {
				$node_ipv6 = 'Unknown';
			}
		}

		if ( ! $node_ipv4 && ! $node_ipv6 ) {
			$net_error .= ' Unable to resolve the IP address of the node. Check DNS configuration on your web server: ask the admin of your web server for assistance.';
		}

		$row = array(
			$id,
			$delay,
			$status,
			$node_ipv4,
			$node_ipv6,
			$country,
			$ago,
			$last[4],
		);

		if ( $last[2] ) {
			$row['error'] = $net_error;
		}

		$tb[] = $row;
	}

	$ret .= cerber_make_plain_table( $tb, array(
		'Node',
		'Processing time',
		'Operational status',
		'IPv4 address',
		'IPv6 address',
		'Location',
		'Last request',
		'Protocol'
	), false, true );

	if ( ! empty( $nodes['best'] ) ) {
		$ret .= '<p>Closest (fastest) node: ' . $nodes['best'] . '</p>';
	}

	// Last successful connection

	if ( ( $ok_data = $nodes['last_node_ok'] ?? false ) ) {
		$outgoing_ip = $ok_data['outgoing_ip'] ?? '';
		$web_server_ip = $ok_data['local_ip'] ?? '';
		$is_proxy = ( $web_server_ip != $outgoing_ip ) ? ' via a proxy server' : '';

		$hostname = gethostbyaddr( $outgoing_ip );
		$hostname = ! cerber_is_ip( $hostname ) ? ' (' . $hostname . ') ' : '';

		$ret .= '<p>Public outgoing IP address of your web server when connecting to node #' . $ok_data['node_id'] . ': ' . $outgoing_ip . $hostname . $is_proxy . '</p>';
	}

	// Last failed connection

	if ( ( $err_data = $nodes['last_node_failed'] ?? false ) ) {

		$web_server_ip = $err_data['local_ip'] ?? '';
		$node_ip = $err_data[5] ?? '';

		$ret .= '<p>Last failed connection to node #' . $err_data['node_id'];

		if ( $web_server_ip && $node_ip
		     && ( cerber_is_ipv4( $web_server_ip ) && ! cerber_is_ipv4( $node_ip )
		          || cerber_is_ipv6( $web_server_ip ) && ! cerber_is_ipv6( $node_ip ) ) ) {
			$ret .= ' CRITICAL ERROR: An IP address family mismatch detected. Resolving IP addresses on this web server does not work properly. In most cases, it is a temporary issue.';
		}

		$info = array();

		$info[] = 'date: ' . cerber_ago_time( $err_data['3'] );
		$info[] = 'local IP address: ' . ( $web_server_ip ?: 'unknown' );
		$info[] = $node_ip ? 'remote IP address: ' . $node_ip : '';

		$info = array_filter( $info );
		$ret .= ' (' . implode( ', ', $info ) . ')';
	}

	if ( ! empty( $nodes['last_check'] ) ) {
		$ret .= '<p>Last check for all nodes: ' . cerber_ago_time( $nodes['last_check'] ) . '</p>';
	}

	$key = lab_get_key();
	$ret .= '<p>Site ID: ' . $key[0] . '</p>';

	$ret .= '<p>To check the global status of the cloud, visit <a href="https://wpcerber.com/cloud-status/" target="_blank">Status Page</a>.</p>';

	return $ret;
}
