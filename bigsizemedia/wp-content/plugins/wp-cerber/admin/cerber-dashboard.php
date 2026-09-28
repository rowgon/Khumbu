<?php
/*
	Copyright (C) 2015-26 CERBER TECH INC., https://wpcerber.com

    Licensed under the GNU GPL.

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


if ( ! defined( 'WPINC' ) ) {
	exit;
}

const CRB_ADMIN_ICONS = array(
	'settings'  => 'bx-slider',
	'activity'  => 'bx-pulse',
	'know_more' => 'bx-idea',
);

if ( ! is_multisite() ) {
	add_action( 'admin_menu', 'cerber_admin_menu' );
	define( 'CRB_ADMIN_CAP', 'manage_options' );
}
else {
	add_action( 'network_admin_menu', 'cerber_admin_menu' );  // only network wide menu allowed in multisite mode
	define( 'CRB_ADMIN_CAP', 'manage_network' );
}

function cerber_admin_menu() {

	$position = 100;

	if ( cerber_is_admin_page() ) {
		if ( crb_get_settings( 'top_admin_menu' ) ) {
			$position = 1;
		}
	}

	$hook = add_menu_page( 'WP Cerber Security', 'WP Cerber', CRB_ADMIN_CAP, 'cerber-security', 'cerber_render_admin_page', 'dashicons-shield', $position );
	add_action( 'load-' . $hook, 'crb_admin_screen_options' );
	add_submenu_page( 'cerber-security', __( 'WP Cerber Dashboard', 'wp-cerber' ), __( 'Dashboard', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-security', 'cerber_render_admin_page' );

	$hook = add_submenu_page( 'cerber-security', __( 'WP Cerber: Traffic Inspector', 'wp-cerber' ), __( 'Traffic Inspector', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-traffic', 'cerber_render_admin_page' );
	add_action( 'load-' . $hook, 'crb_admin_screen_options' );

	if ( lab_lab() ) {
		add_submenu_page( 'cerber-security', __( 'WP Cerber: Data Shield Policies', 'wp-cerber' ), __( 'Data Shield', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-shield', 'cerber_render_admin_page' );
		//add_submenu_page( 'cerber-security', __( 'Cerber Security Rules', 'wp-cerber' ), __( 'Security Rules', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-rules', 'cerber_render_admin_page' );
	}

	add_submenu_page( 'cerber-security', __( 'WP Cerber: Security Rules', 'wp-cerber' ), __( 'Security Rules', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-rules', 'cerber_render_admin_page' );

	add_submenu_page( 'cerber-security', __( 'WP Cerber: User Security', 'wp-cerber' ), __( 'User Policies', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-users', 'cerber_render_admin_page' );

	if ( cerber_get_upload_dir_mu() ) {
		$hook = add_submenu_page( 'cerber-security', 'WP Cerber: Site Integrity', __( 'Site Integrity', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-integrity', 'cerber_render_admin_page' );
		add_action( 'load-' . $hook, 'crb_admin_screen_options' );
	}

	add_submenu_page( 'cerber-security', __( 'WP Cerber: Anti-spam Settings', 'wp-cerber' ), __( 'Anti-spam', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-recaptcha', 'cerber_render_admin_page' );

	$hook = add_submenu_page( 'cerber-security', 'Cerber.Hub', 'Cerber.Hub', CRB_ADMIN_CAP, 'cerber-nexus', 'nexus_admin_page' );
	if ( nexus_is_main() ) {
		add_action( 'load-' . $hook, 'nexus_master_screen' );
	}

	if ( ! CRB_Addons::none() ) {
		add_submenu_page( 'cerber-security', __( 'WP Cerber: Add-ons', 'wp-cerber' ), __( 'Add-ons', 'wp-cerber' ), CRB_ADMIN_CAP, CRB_ADDON_PAGE, 'cerber_render_admin_page' );
	}

	add_submenu_page( 'cerber-security', __( 'WP Cerber: Tools', 'wp-cerber' ), __( 'Tools', 'wp-cerber' ), CRB_ADMIN_CAP, 'cerber-tools', 'cerber_render_admin_page' );

	/* We use an ugly hack to make a link in the admin menu */

	add_submenu_page( 'users.php', __( 'User Sessions', 'wp-cerber' ), __( 'User Sessions', 'wp-cerber' ), 'manage_options', 'wp-cerber-user-sessions', 'show_mass_page' );

	global $submenu;

	if ( ! empty( $submenu['users.php'] ) ) {
		foreach ( $submenu['users.php'] as &$item ) {
			if ( $item[2] == 'wp-cerber-user-sessions' ) {
				$item[2] = cerber_admin_link( 'sessions' );
				break;
			}
		}
	}

	/* End of ugly hack */

}

add_action( 'admin_bar_menu', 'cerber_admin_bar' );
function cerber_admin_bar( $wp_admin_bar ) {
	if ( ! is_multisite() ) {
		return;
	}
	$args = array(
		'parent' => 'network-admin',
		'id'     => 'cerber_admin',
		'title'  => 'WP Cerber',
		'href'   => cerber_admin_link(),
	);
	$wp_admin_bar->add_node( $args );
}

/**
 * Outputs WP Cerber admin headers
 *
 * @return void
 *
 * @since 9.1.1
 */
function crb_admin_headers() {
	if ( ! cerber_is_admin_page()
	     || cerber_is_wp_ajax() ) {
		return;
	}

	//header( "Content-Security-Policy: base-uri 'self'; default-src 'self' 'unsafe-inline'; img-src https: data:; font-src https: data:; connect-src 'self'; object-src 'none';" );
	header( "Content-Security-Policy: base-uri 'self'; default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src https: data:; font-src https: data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none';" );
}

/**
 * Wrapper for all admin pages
 *
 * @param $title string
 * @param $tabs array
 * @param $active_tab string
 * @param $renderer callable
 */
function cerber_show_admin_page( $title, $tabs = array(), $active_tab = null, $renderer = null ) {

	if ( ! $active_tab ) {
		$active_tab = crb_admin_get_tab( $tabs );
	}

	if ( nexus_is_valid_request() ) {
		$title .= nexus_request_data()->at_site;
	}

	if ( ! cerber_is_dashboard() ) {
		CRB_Issue_Monitor::detect_critical_issues();
	}

	CRB_Wisdom::schedule_updating();

	?>
    <div id="crb-admin" class="wrap">

        <h1><?php echo $title; ?></h1>

		<?php

		cerber_show_admin_messages();

		cerber_show_tabs( $active_tab, $tabs );

		echo '<div id="crb-admin-content">';

		echo '<div id="crb-main" class="crb-tab-' . $active_tab . '">';

		if ( $active_tab == 'help' ) {
			cerber_render_help_page();
		}
        elseif ( is_callable( $renderer ) ) {
			$tab_data = crb_array_get( $tabs[ $active_tab ], 'tab_data' );
			call_user_func( $renderer, $active_tab, $tab_data );
		}

		echo '</div>';

		cerber_show_aside( $active_tab );

		echo '</div>';

		?>
    </div>
	<?php
}

/**
 * Displays lockouts in the Dashboard
 *
 * @param array $args
 * @param bool $echo
 *
 * @return string|void
 */
function cerber_show_lockouts_page( $args = array(), $echo = true ) {

	//$wp_cerber->deleteGarbage();

	$per_page = ( ! empty( $args['per_page'] ) ) ? $args['per_page'] : crb_admin_get_per_page();
	$limit = crb_build_sql_limit( $per_page );
	$controls = empty( $args['compact_view'] );

	if ( $rows = cerber_db_get_results( 'SELECT * FROM ' . CRB_LOCKOUT_TABLE . ' ORDER BY block_until DESC ' . $limit, MYSQL_FETCH_OBJECT ) ) {

		if ( $controls ) {
			$total = crb_get_locked_out_num();
		}

		$table_rows = array();
		$base_url = cerber_admin_link( 'activity' );

		$remove_base = cerber_admin_link( crb_admin_get_tab(), [], true );

		foreach ( $rows as $row ) {
			$ip = '<a href="' . $base_url . '&amp;filter_ip=' . $row->ip . '">' . $row->ip . '</a>';

			$closing = crb_get_icon( 'activity' ) . '<a href="' . $base_url . '&amp;filter_set=1&amp;filter_ip=' . $row->ip . '">' . __( 'View the log of suspicious and malicious activity from this IP address', 'wp-cerber' ) . '</a>';

			if ( ! $reason = CRB_Explainer::create_popup( 10, $row->reason_id, 0, '', '', $row->reason, $closing ) ) {
				$reason = '<a href="' . $base_url . '&amp;filter_set=1&amp;filter_ip=' . $row->ip . '">' . $row->reason . '</a>';
			}

			$ip_info = cerber_get_ip_info( $row->ip );
			if ( isset( $ip_info['hostname_html'] ) ) {
				$hostname = $ip_info['hostname_html'];
			}
			else {
				$ip_id = cerber_get_id_ip( $row->ip );
				$hostname = crb_get_ajax_placeholder( 'hostname', $ip_id );
			}

			if ( lab_lab() ) {
				$single_ip = str_replace( '*', '1', $row->ip );
				$country = '</td><td>' . crb_country_html( null, $single_ip );
			}
			else {
				$country = '';
			}

			$the_row = '<td>' . $ip . '</td><td>' . $hostname . $country . '</td><td>' . cerber_date( $row->block_until ) . '</td><td>' . $reason . '</td>';

			if ( $controls ) {
				/* translators: Control button label. Unlock means lifting the active lockout for an IP address. */
				$the_row .= '<td><a href="' . $remove_base . '&amp;cerber_admin_do=unlockip&amp;ip=' . esc_attr( $row->ip ) . '">' . __( 'Unlock', 'wp-cerber' ) . '</a></td>';
			}

			$table_rows[] = $the_row;
		}

		$heading = array(
			__( 'IP Address', 'wp-cerber' ),
			__( 'Hostname', 'wp-cerber' ),
			__( 'Country', 'wp-cerber' ),
			__( 'Expires', 'wp-cerber' ),
			__( 'Reason', 'wp-cerber' ),
			__( 'Action', 'wp-cerber' ),
		);

		if ( ! lab_lab() ) {
			unset( $heading[2] );
		}

		if ( $controls ) {
			$navi = cerber_page_navi( $total, $per_page );
			$hint = '<div class="cerber-margin"><p>' . __( 'Click the IP address to see its activity', 'wp-cerber' ) . '</p></div>';
		}
		else {
			$navi = '';
			$hint = '';
			unset( $heading[5] );
		}

		$titles = '<tr><th>' . implode( '</th><th>', $heading ) . '</th></tr>';

		$table = '<table id="crb-locked-out" class="widefat crb-table"><thead>' . $titles . '</thead><tfoot>' . $titles . '</tfoot>' . implode( '</tr><tr>', $table_rows ) . '</tr></table>';
		$ret = $table . $navi . $hint;

	}
	else {
		$ret = crb_ui_renderer()->render_element( crb_ui_message_box( array( __( 'No lockouts at the moment. The sky is clear.', 'wp-cerber' ) ) ) );
	}

	if ( $echo ) {
		echo $ret;

		return;
	}

	return $ret;
}

/**
 * @param string $ip
 *
 * @return int
 */
function crb_delete_lockout( $ip ) {

	$result = 0;

	if ( cerber_db_query( 'DELETE FROM ' . CRB_LOCKOUT_TABLE . ' WHERE ip = "' . cerber_db_real_escape( $ip ) . '"' ) ) {
		$result = cerber_db_get_affected();
	}

	crb_event_handler( 'ip_event', array(
		'e_type' => 'unlocked',
		'ip'     => $ip,
		'result' => $result
	) );

	return $result;
}

/*
	AJAX admin requests are landing here
*/
add_action( 'wp_ajax_cerber_ajax', 'cerber_admin_ajax' );
function cerber_admin_ajax() {

	$go = cerber_check_ajax_permissions( false );

	if ( $go === false ) {
		return false;
	}

	$admin = $go === true;

	$response = array();
	$request = crb_get_request_fields();

	if ( $admin && ( $ip = crb_array_get( $request, 'acl_delete' ) ) ) {
		if ( cerber_acl_remove( $ip, crb_array_get( $request, 'slice' ) ) ) {
			$response['deleted_ip'] = $ip;
		}
		else {
			$response['error'] = 'Unable to delete the entry';
		}
	}
    elseif ( ( $slug = crb_array_get( $request, 'crb_ajax_slug' ) )
	         && $list = crb_array_get( $request, 'crb_ajax_list' ) ) {

		$response['slug'] = $slug;
		$list = array_unique( $list );
		$response_data = array();

		/*
		$list = array_map(function ($ip_id){
			return cerber_get_ip_id( $ip_id );
        }, $list);
		$list = array_filter( $list, function ( $ip ) {
			if (filter_var( $ip, FILTER_VALIDATE_IP )){
			    return true;
            }
		});*/

		if ( $slug == 'hostname' || $slug == 'country' ) {
			$ip_list = array();
			foreach ( $list as $ip_id ) {
				if ( $ip = filter_var( cerber_get_ip_id( $ip_id ), FILTER_VALIDATE_IP ) ) {
					$ip_list[ $ip_id ] = $ip;
					$response_data[ $ip_id ] = '';
				}
				else {
					$response_data[ $ip_id ] = '-';
				}
			}
		}

		switch ( $slug ) {
			case 'hostname':
				foreach ( $ip_list as $ip_id => $ip ) {
					$ip_info = cerber_get_ip_info( $ip, false );
					$response_data[ $ip_id ] = $ip_info['hostname_html'];
				}
				break;
			case 'country':
				if ( $country_list = lab_get_country( $ip_list, false ) ) {
					foreach ( $country_list as $ip_id => $country ) {
						if ( $country ) {
							$response_data[ $ip_id ] = crb_get_flag_html( $country, crb_get_country_name( $country ) );
						}
						else {
							$response_data[ $ip_id ] = __( 'Unknown', 'wp-cerber' );
						}
					}
				}
				break;
			case 'cbcc':
				foreach ( $list as $user_id ) {
					$response_data[ $user_id ] = 0;
					$base = admin_url( 'edit-comments.php' );
					// to get this working we added filter 'preprocess_comment'
					if ( $com = get_comments( array( 'author__in' => $user_id ) ) ) {
						$response_data[ $user_id ] = '<a href="' . $base . '?user_id=' . $user_id . '" target="_blank">' . count( $com ) . '</a>';
					}
				}
				break;
			case 'cbla':
				$base = cerber_activity_link( array( CRB_EV_LIN ) );
				foreach ( $list as $user_id ) {
					$user_id = crb_absint( $user_id );

					if ( $last_log = crb_get_last_user_login( $user_id ) ) {
						$ip = $last_log['ip'];
						$time = $last_log['ts'];
						$date = ( $time ) ? cerber_date( $time ) : __( 'Unknown', 'wp-cerber' );

						if ( $ip
						     && ( $country = crb_country_html( $last_log['cn'], $ip, false ) ) ) {
							$country = '<br />' . $country;
						}
						else {
							$country = '';
						}

						$val = '<a href="' . $base . '&amp;filter_user=' . $user_id . '">' . $date . '</a>' . $country;
					}
					else {
						$val = __( 'Never', 'wp-cerber' );
					}

					$response_data[ $user_id ] = $val;
				}
				break;
			case 'cbfl':
				$base = cerber_activity_link( array( CRB_EV_LFL ) );
				foreach ( $list as $user_id ) {
					$u = crb_get_userdata( $user_id );
					$val = 0;

					$failed = crb_q_cache_get( 'SELECT COUNT(user_id) FROM ' . CERBER_LOG_TABLE . ' WHERE ( user_login = "' . $u->user_login . '" OR user_login = "' . $u->user_email . '" ) AND activity = ' . CRB_EV_LFL . ' AND stamp > ' . ( time() - 7 * 24 * 3600 ), CERBER_LOG_TABLE );

					if ( ! empty( $failed[0][0] ) ) {
						$val = '<a href="' . $base . '&amp;filter_login=' . $u->user_email . '|' . $u->user_login . '">' . $failed[0][0] . '</a>';
					}

					$response_data[ $user_id ] = $val;
				}
				break;
		}

		$response['data'] = $response_data;
	}
    elseif ( $admin && isset( $request['crb_dismiss_info'] ) ) {
		$context = $request['crb_context'] ?? '';
		$target_id = $request['crb_target_id'] ?? '';
		$diss_msg = 'OK';

		switch ( $context ) {
			case 'delete_single_issue':
				$result = CRB_Issues::delete_by_id( $target_id );
				if ( is_wp_error( $result ) ) {
					$diss_msg = $result->get_error_message();
				}
				break;
			case 'new_release_announcement':
				delete_site_option( CRB_ADMIN_ANNOUNCE );
				break;
			case 'lab_action':
				if ( in_array( $target_id, array( 'lab_ok', 'lab_no' ) ) ) {
					lab_user_opt_in( $target_id );
				}
				else {
					$diss_msg = 'Error. Unknown target.';
				}
				break;
			default:
				$diss_msg = 'Error. Unknown context.';
		}

		$response ['diss_result_msg'] = $diss_msg;
	}
    elseif ( $admin && ( $ajax_route = crb_sanitize_id( crb_array_get( $request, 'ajax_route' ) ) ) ) {

		$ref_params = crb_get_referrer_params();
		$response = array( 'html' => '', 'error' => '' );
		$result = '';

		switch ( $ajax_route ) {
			case 'dashboard_analytics':
				cerber_widgets_init();
				$result = CRB_Widgets::render_widget( $request['ds_widget'] ?? '', true );
				break;
			case 'scanner_analytics':
				$result = cerber_generate_insights( $request['itype'] );
				break;
			case 'user_activity_analytics':
				$result = crb_generate_user_insights( absint( $request['user_id'] ), $ref_params['tab'] ?? '' );
				break;
			case 'ip_extra_info':
				$result = cerber_ip_extra_view_ajax( $request, $ref_params );
				break;
			case 'ip_quick_analytics':
				$result = crb_generate_ip_insights( $request['ip_address'], $ref_params['tab'] );
				break;
			case 'diagnostic_tools':
				$result = cerber_db_diag();
				break;
			default:
				$response['error'] = 'Error: Unknown WP Cerber AJAX route (' . $ajax_route . ')';
		}

		if ( crb_is_wp_error( $result ) ) {
			$response ['error'] = $result->get_error_message();
		}
		else {
			$response ['html'] = $result;
		}
	}
    elseif ( $admin && ( $usearch = crb_array_get( $request, 'user_search' ) ) ) {
		$users = get_users( array( 'search' => '*' . esc_attr( $usearch ) . '*', ) );
		if ( $users ) {
			foreach ( $users as $user ) {
				$data = crb_get_userdata( $user->ID );
				$response[] = array(
					'id'   => $user->ID,
					'text' => crb_format_user_name( $data )
				);
			}
		}
	}
    elseif ( $admin && $action = $request['cerber_ajax_action'] ?? false ) {

		$msg = '*';
		$result = false;

		switch ( $action ) {
			case 'dashboard_save_sortable':

				$order = array_map( function ( $id ) {
					return crb_sanitize_id( $id );
				}, $request['dash_order'] );

				$result = CRB_Widgets::save_order( $order );

				break;
		}

		if ( crb_is_wp_error( $result ) ) {
			$response ['error'] = $result->get_error_message();
		}
		else {
			$msg = 'OK';
		}

		$response ['message'] = $msg;
	}

	if ( empty( $response ) ) {
		$response['error'] = 'Unknown AJAX request. Check your code, it\'s time to show your debugging skills!';
	}

	echo json_encode( $response );

	if ( ! nexus_is_valid_request() ) {
		wp_die();
	}
}

add_action( 'wp_ajax_cerber_local_ajax', function () {
	check_ajax_referer( 'crb-ajax-admin', 'ajax_nonce' );
	if ( ! is_super_admin() ) {
		wp_die( 'Oops! Access denied.' );
	}

	$done = array();

	switch ( crb_array_get( $_REQUEST, 'crb_ajax_do' ) ) {
		case 'bg_tasks_run':
			$done = CRB_Deferred_Tasks::launcher( crb_array_get( $_REQUEST, 'run_tasks', array() ) );
			break;
	}

	echo json_encode( array( 'done' => $done ) );
	exit;
} );

/**
 * @param string $group
 * @param string $item_id
 *
 * @return string
 */
function crb_get_ajax_placeholder( $group, $item_id ) {

	return '<img class="crb-ajax-load" data-ajax_group="' . $group . '" data-item_id="' . $item_id . '" src="' . CRB_Globals::assets_url( 'ajax-loader.gif' ) . '" />';
}

/*
 * Retrieve extended IP information
 * @since 2.2
 *
 */
function cerber_get_ip_info( $ip, $cache_only = true ) {

	$ip_id = 'info_' . cerber_get_id_ip( $ip );

	$ip_info = cerber_get_set( $ip_id );

	if ( $cache_only ) {
		return $ip_info;
	}

	if ( empty( $ip_info['hostname_html'] ) ) {
		$ip_info = array();

		if ( ! $hostname = @gethostbyaddr( $ip ) ) {
			$hostname = __( 'unknown', 'wp-cerber' );
		}

		$ip_info['hostname'] = $hostname;

		if ( ! filter_var( $hostname, FILTER_VALIDATE_IP ) ) {
			$hostname = str_replace( '.', '.<wbr>', $hostname );
		}

		$ip_info['hostname_html'] = $hostname;

		cerber_update_set( $ip_id, $ip_info, null, true, time() + 24 * 3600 );
	}

	return $ip_info;
}


/*
	Admin dashboard actions
*/
add_action( 'wp_loaded', // 'wp_loaded' @since 5.6
	function () {
		if ( ! is_admin() ) {
			return;
		}

		crb_process_admin_request();

	}, 0 );

/**
 * @param bool $is_post
 *
 * @return array|false|void
 *
 */
function crb_process_admin_request( $is_post = false ) {
	global $wpdb;

	if ( ! nexus_is_valid_request()
	     && ! cerber_user_can_manage() ) {
		return;
	}

	$get = crb_get_query_params();

	if ( ( ! $nonce = crb_array_get( $get, 'cerber_nonce' ) )
	     && ( ! $nonce = crb_get_post_fields( 'cerber_nonce' ) ) ) {

		return;
	}

	if ( ! wp_verify_nonce( $nonce, 'control' ) ) {
		// TODO: Investigate why is this fires while managing a remote website via Cerber.Hub
		//cerber_admin_notice( 'Nonce verification failed.' );

		return;
	}

	$post = crb_get_post_fields();

	//$q = crb_admin_parse_query( array( 'cerber_admin_do', 'ip' ) );

	$remove_args = array();

	if ( cerber_is_http_get() ) {
		if ( ( $do = crb_array_get( $get, 'cerber_admin_do' ) ) ) {
			switch ( $do ) {
				case 'unlockip':
					$err = '';
					$ip = crb_array_get( $get, 'ip' );

					if ( cerber_is_ip_or_net( $ip ) ) {
						if ( crb_delete_lockout( $ip ) ) {
							/* translators: %s is the IP address. */
							cerber_admin_message( sprintf( __( 'Lockout for %s was removed', 'wp-cerber' ), $ip ) );
						}
						else {
							$err = 'No lockout for the specified IP address ' . $ip . ' found.';
						}
					}
					else {
						$err = 'An invalid IP address has been specified.';
					}

					if ( $err ) {
						cerber_admin_notice( 'Request failed. ' . $err );
					}

					$remove_args[] = 'ip';
					break;
				case 'testnotify':
					$test_type = crb_array_get_validated( $get, 'type', '', '\w+' );
					$msg = array();

					if ( $test_type == 'plugin_updates' ) {
						if ( false === crb_plugin_update_notifier( true, true, $sent, $msg ) ) {
							$sent = false;
						}
					}
					else {

						$test_chan = crb_array_get_validated( $get, 'channel', '', '\w+' );
						$period = crb_array_get_validated( $get, 'test_period', 'one_week', '\w+' );

						$channels = array();

						if ( $test_chan && ( $test_chan != 'email' || lab_lab() ) ) {
							$channels = array_fill_keys( array_keys( CRB_CHANNELS ), 0 );
							$channels = array_merge( $channels, array( $test_chan => 1 ) );
						}

						$sent = CRB_Messaging::send( $test_type, array( 'subj' => ' *** ' . __( 'TEST MESSAGE', 'wp-cerber' ) . ' *** ' ), $channels, true, array( 'report_id' => $period ) );

					}

					if ( $sent !== false ) {
						$to = ' ' . implode( ', ', $sent );
						/* translators: Here %s is the name of a mobile device or/and email addresses. */
						cerber_admin_message( sprintf( __( 'A message has been sent to %s', 'wp-cerber' ), $to ) );
					}
                    elseif ( ! $msg ) {
						cerber_admin_notice( __( 'Unable to send a message', 'wp-cerber' ) );
					}
					else {
						cerber_admin_notice( $msg );
					}

					$remove_args[] = 'type';
					$remove_args[] = 'channel';
					break;
				case 'manage_alert':
					if ( 'create_alert' == crb_array_get( $get, 'mode' ) ) {
						CRB_Activity_Alerts::create_alert();
					}
					else {
						CRB_Activity_Alerts::delete_alert();
					}
					$remove_args = array_merge( $remove_args, CRB_Activity_Alerts::CRB_NON_EVENT_PARAMS );
					$remove_args[] = 'manage_alert';
					$remove_args[] = 'mode';
					break;
				case 'nexus_set_role':
					nexus_enable_role();
					crb_safe_redirect( cerber_admin_link( '', array( 'page' => 'cerber-nexus' ) ) );
					exit();
					break;
				case 'nexus_delete_slave':
					crb_safe_redirect( cerber_admin_link( 'nexus_sites' ) );
					exit();
					break;
				case 'nexus_site_table':
					if ( cerber_get_bulk_action() ) {
						nexus_do_bulk();
					}
					break;
				case 'scan_tegrity':
					$adm = crb_array_get( $get, 'crb_scan_adm' );
					$file = crb_array_get( $get, 'crb_file_id' );
					if ( in_array( $adm, array( 'delete', 'restore' ) ) ) {
						cerber_quarantine_do( $adm, crb_array_get( $get, 'crb_scan_id' ), crb_array_get( $get, 'crb_file_id' ) );
					}
                    elseif ( $adm == 'remove_ignore' ) {
						if ( crb_remove_ignore( $file ) ) {
							cerber_admin_message( 'The file has been removed from the list' );
						}
					}
					$remove_args = array( 'crb_scan_adm', 'crb_scan_id', 'crb_file_id' );
					break;
				case 'manage_diag_log':
					cerber_manage_diag_log( crb_array_get( $get, 'do_this' ) );
					$remove_args[] = 'do_this';
					break;
				case 'terminate_session':
					crb_sessions_kill( crb_array_get_validated( $get, 'id', false, '\w+' ), crb_array_get_validated( $get, 'user_id', false, '\d+' ) );
					$remove_args = array( 'user_id', 'id' );
					break;
				case 'crb_manage_sessions':
					if ( cerber_get_bulk_action() == 'bulk_session_terminate' ) {
						crb_sessions_kill( crb_array_get_validated( $get, 'ids', array(), '\w+' ) );
					}
                    elseif ( cerber_get_bulk_action() == 'bulk_block_user' ) {
						if ( ( $sids = crb_array_get_validated( $get, 'ids', array(), '\w+' ) )
						     && ( $users = cerber_db_get_col( 'SELECT user_id FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE . ' WHERE wp_session_token IN ("' . implode( '","', $sids ) . '")' ) ) ) {
							array_walk( $users, 'cerber_block_user' );
						}
					}
					break;
				case 'export':
					if ( nexus_is_valid_request() ) {
						return crb_admin_get_tokenized_link();
					}
					crb_admin_download_file( crb_array_get( $get, 'type' ) );
					break;
				case 'load_defaults':
					cerber_load_defaults();
					cerber_admin_message( __( 'Default settings have been loaded', 'wp-cerber' ) );
					break;
				case 'clear_cache':
					CRB_Cache::reset();
					break;
				default:
					return;
			}

			if ( nexus_is_valid_request() ) {
				return array( 'redirect' => true, 'remove_args' => $remove_args );
			}
			else {
				cerber_safe_redirect( $remove_args );
			}

		}

		// TODO: move to the switch above
		if ( cerber_get_get( 'citadel_do' ) == 'deactivate' ) {
			cerber_disable_citadel();
		}
        elseif ( isset( $_GET['force_repair_db'] ) ) {
			cerber_create_db();
			cerber_upgrade_db( true );
			cerber_admin_message( 'Cerber\'s database tables have been repaired and upgraded' );
			cerber_safe_redirect( 'force_repair_db' );
		}
        elseif ( $table = cerber_get_get( 'truncate', '[a-z_]+' ) ) {
			if ( 0 === strpos( $table, 'cerber_' ) ) {
				if ( $wpdb->query( 'TRUNCATE TABLE ' . $table ) ) {

					if ( $table == CERBER_LOG_TABLE ) {
						CRB_Activity::clear_status();
					}

					cerber_admin_message( 'Table ' . $table . ' has been truncated' );
				}
				else {
					cerber_admin_notice( $wpdb->last_error );
				}
			}
			cerber_safe_redirect( 'truncate' );
		}
        elseif ( isset( $_GET['force_check_nodes'] ) ) {
			$best = lab_check_nodes( true );
			cerber_admin_message( 'Connectivity to Cerber Security Cloud has been checked. The closest node: ' . $best );
			cerber_safe_redirect( 'force_check_nodes' );
		}
        elseif ( isset( $_GET['clear_up_lab_cache'] ) ) {
			lab_cleanup_cache();
			cerber_admin_message( 'The Cerber Security Cloud cache has been cleared' );
			cerber_safe_redirect( 'clear_up_lab_cache' );
		}
        elseif ( isset( $_GET['clear_up_the_cache'] ) ) {
			lab_cleanup_cache();
			CRB_Cache::reset();
			CRB_Wisdom::clear_cache( true );
			cerber_delete_expired_set( true );
			CRB_Issues::delete_all();
			cerber_admin_message( 'The plugin cache has been cleared' );
			cerber_safe_redirect( 'clear_up_the_cache' );
		}
	}

	if ( cerber_is_http_post() ) {

		$redirect = false;

		if ( ( $do = crb_array_get( $post, 'cerber_admin_do' ) ) ) {
			switch ( $do ) {
				case 'save_widget_list':
					cerber_widgets_init();
					CRB_Widgets::save_list( $post );
					$redirect = true;
					break;
				case 'update_role_policies':
					crb_settings_update_role_policies( $post );
					$redirect = true;
					break;
				case 'update_geo_rules':
					crb_settings_update_geo_rules( $post );
					break;
				case 'add2acl':
					crb_acl_form_process( $post );
					break;
				case 'add_slave':
					nexus_add_client( crb_array_get( $post, 'new_slave_token' ) );
					break;
				case 'install_key':
					// Sanitize: license keys are ASCII alphanumeric only.
					$lic = preg_replace( '/[^A-Z0-9]/i', '', crb_array_get( $post, 'cerber_license' ) );
					$lic_length = strlen( $lic );
					$redirect = true;

					// Case 1: empty submission removes the currently installed key.
					if ( $lic_length === 0 ) {
						lab_cleanup_cache();
						cerber_delete_expired_set( true );
						lab_update_key( '' );
						cerber_admin_message( __( 'The license key has been removed.', 'wp-cerber' ) );
						break;
					}

					// Case 2: wrong length - reject early and do not touch the stored key.
					if ( $lic_length !== LAB_KEY_LENGTH ) {
						cerber_admin_notice( sprintf(
							__( 'The license key you entered is not valid. A license key must be exactly %d characters long.', 'wp-cerber' ),
							LAB_KEY_LENGTH
						) );
						break;
					}

					// Case 3: correct length - validate BEFORE persisting so a bad key cannot overwrite a working one.
					lab_cleanup_cache();
					cerber_delete_expired_set( true );
					lab_get_site_meta();

					$validate_msg = '';
					$site_ip = '';
					$is_valid = lab_validate_lic( $lic, $validate_msg, $site_ip );

					if ( $is_valid ) {
						$msg = '<b>' . __( 'Great! Your license key is valid.', 'wp-cerber' ) . '</b>';
						$msg .= '<p>' . __( 'Now, whenever you see a green shield icon in the top right-hand corner of any WP Cerber admin page, it means the professional version works as intended, and your website is protected by WP Cerber Security Cloud.', 'wp-cerber' ) . '</p>';
						/* translators: %s is the URL to the client portal. */
						$msg .= '<p>' . sprintf( __( 'Please use our client portal to manage your subscription, license keys and get support at %s', 'wp-cerber' ), '<a target="_blank" href="https://my.wpcerber.com">https://my.wpcerber.com</a>' ) . '</p>';
						$msg .= '<p>' . __( 'Thank you for being our client.', 'wp-cerber' ) . '</p>';
						cerber_admin_message( $msg );
					}
					else {
						// lab_validate_lic() already emits its own admin notice via cerber_admin_notice() on network errors (code 2 or 3).
						// do not contradict it with an "invalid key" notice in that case.
						$is_network_error = ( mb_strpos( $validate_msg, '(2)' ) !== false ) || ( mb_strpos( $validate_msg, '(3)' ) !== false );

						if ( ! $is_network_error ) {
							cerber_admin_notice(
								array(
									__( 'Error! You have entered an invalid or expired license key.', 'wp-cerber' ),
									sprintf( __( 'Error Code: %s', 'wp-cerber' ), crb_escape_html( $validate_msg ) ),
								)
							);
						}
					}

					break;
			}

			if ( $redirect ) {
				if ( nexus_is_valid_request() ) {
					// No redirection is needed so far, we use a second 'get_page' request
					//return array( 'redirect' => true, 'remove_args' => $remove_args );
				}
				else {
					cerber_safe_redirect( $remove_args );
				}
			}
		}
	}

}

/**
 * Redirects safely to the current URL while removing specified query parameters.
 *
 * This function performs a safe local redirect using wp_safe_redirect() after removing the specified query
 * parameters from the current URL. It ensures that commonly used temporary query parameters are also removed.
 *
 * The URL redirected to states an empty fragment, one that names nothing. An admin action is complete
 * once it has been performed, so the single-use reveal declarations of the link that opened the page,
 * see crb_get_setting_link(), must not survive it and reveal the setting again.
 *
 * @param string|string[] $rem_args A single query parameter or an array of query parameters to be removed.
 *                                   If empty, only predefined, single-use parameters are removed.
 *
 * @return void
 */
function cerber_safe_redirect( $rem_args ) {
	if ( empty( $rem_args ) ) {
		$rem_args = array();
	}
    elseif ( ! is_array( $rem_args ) ) {
		$rem_args = array( (string) $rem_args );
	}

	// These are most used "temporary" query parameters

	$rem_args = array_merge( $rem_args, array(
		'_wp_http_referer',
		'_wpnonce',
		'cerber_nonce',
		'ids',
		'cerber_admin_do',
		'action',
		'action2'
	) );

	$url = remove_query_arg( $rem_args );

	crb_safe_redirect( crb_url_discard_fragment( $url ) );
	exit();
}

function crb_admin_get_tokenized_link() {
	if ( empty( nexus_request_data()->get_params ) ) {
		return false;
	}
	$key = crb_random_string( 26 );
	if ( cerber_update_set( '_the_key_' . $key, array( 'query' => nexus_request_data()->get_params ), 1, true, time() + 60 ) ) {
		return array( 'redirect' => true, 'redirect_url' => home_url( '?cerber_magic_key=' . $key ) );
	}

	cerber_admin_notice( 'Unable to generate a tokenized URL' );

	return false;
}

/**
 * Returns HTML code of a set of navigation links to be used for quick filtering logs.
 *
 * @param string $context Defines what links are included in the set
 *
 * @return string Escaped HTML code
 */
function crb_admin_activity_nav_links( $context = '' ) {

	$links = array();

	if ( ! $context ) {
		$links[] = array( array(), __( 'View all', 'wp-cerber' ) );

		$labels = cerber_get_labels( 'activity' );

		$set = array( CRB_EV_LIN );
		foreach ( $set as $item ) {
			$links[] = array( array( 'filter_activity' => $item ), $labels[ $item ] );
		}
	}

	if ( $context == 'users' ) {
		$links[] = array( array( 'filter_user' => '*' ), __( 'View all', 'wp-cerber' ) );
	}

	if ( $context != 'suspicious' ) {
		$links[] = array( array( 'filter_activity' => array( 1, 2 ) ), __( 'New users', 'wp-cerber' ) );
		$links[] = array( array( 'filter_set' => 2 ), __( 'Login issues', 'wp-cerber' ) );
	}

	if ( $context != 'users' ) {
		if ( ! $context ) {
			$txt = __( 'Suspicious activity', 'wp-cerber' );
		}
		else {
			$txt = __( 'View all', 'wp-cerber' );
		}

		$links[] = array( array( 'filter_set' => 1 ), $txt );
	}

	if ( ! $context ) {
		$links[] = array( array( 'filter_status' => array( CRB_STS_11, CRB_STS_532, 706 ) ), __( 'Spam Events', 'wp-cerber' ) );
		$links[] = array( array( 'filter_activity' => array( CRB_EV_IPL, CRB_EV_NEL ) ), __( 'IP locked out', 'wp-cerber' ) );
		$links[] = array( array( 'filter_user' => '*' ), __( 'Users', 'wp-cerber' ) );
		$links[] = array( array( 'filter_user' => 0 ), __( 'Non-authenticated', 'wp-cerber' ) );
		if ( ! nexus_is_valid_request() ) {
			$links[] = array( array( 'filter_user' => get_current_user_id() ), __( 'My activity', 'wp-cerber' ) );
			$links[] = array( array( 'filter_ip' => cerber_get_remote_ip() ), __( 'My IP', 'wp-cerber' ) );
		}
	}

	return crb_build_quick_nav_block( $links, 'activity' );

}

/**
 * Generates a quick navigation block and highlights the link matching the current query parameters.
 *
 * @param array $link_list A list of UI navigation link definitions.
 * @param string $tab Admin page tab to generate links for.
 * @param string $class CSS class for the link wrapper.
 *
 * @return string Escaped HTML.
 *
 * @since 8.8.3.2
 */
function crb_build_quick_nav_block( $link_list, $tab = 'activity', $class = '' ) {

	$current_params = crb_get_referrer_params();
	unset( $current_params['page'], $current_params['tab'], $current_params['pagen'] );

	$html = '';

	foreach ( $link_list as $link_config ) {
		$query_string = '';
		$selected = false;
		$remaining_params = $current_params;

		if ( $parameters = $link_config[0] ?? '' ) {

			$query_string .= '&' . http_build_query( $parameters );
			$selected = true;

			foreach ( $parameters as $name => $expected_value ) {
				if ( ! is_array( $expected_value ) ) {
					$actual_value = crb_array_get( $current_params, $name );

					// Treat a single-item query array as equivalent to its scalar form.
					if ( is_array( $actual_value ) && count( $actual_value ) === 1 ) {
						$actual_value = reset( $actual_value );
					}

					// A compound value cannot match a scalar link parameter.
					if ( ! is_scalar( $actual_value ) ) {
						$selected = false;
						continue;
					}

					if ( (string) $actual_value !== (string) $expected_value ) {
						$selected = false;
					}
					else {
						unset( $remaining_params[ $name ] );
					}
				}
				else {
					foreach ( $expected_value as $key => $expected_item_value ) {
						if ( crb_array_get( $current_params, array( $name, $key ) ) !== (string) $expected_item_value ) {
							$selected = false;
						}
						else {
							unset( $remaining_params[ $name ][ $key ] );
						}
					}
				}
			}
		}

		$remaining_params = array_filter( $remaining_params, function ( $val ) {
			return $val === 0 || ! empty( $val );
		} );

		$anchor = crb_escape_html( $link_config[1] );

		if ( $selected && empty( $remaining_params ) ) {
			$html .= '<span class="crb_selected">' . $anchor . '</span> ';
		}
		else {
			$html .= '<a class="crb-nav-link" href="' . crb_escape_url( cerber_admin_link( $tab ) . $query_string ) . '">' . $anchor . '</a> ';
		}
	}

	return '<div class="crb-tag-buttons ' . crb_sanitize_alphanum( $class ) . '">' . $html . '</div>';
}

/**
 * Renders the admin Activity log view as an HTML fragment.
 *
 * Reads activity rows through CRB_Activity::fetch() using either the current request query string
 * or the explicit overrides supplied in $args.
 *
 * Rendering options include per_page, compact_view, no_export, no_details, date, hide_columns, and context.
 *
 * In full mode (compact_view = false), the returned markup includes the quick filters, search form,
 * pagination, export link, alert controls, and contextual user or IP information
 * when a corresponding filter resolves to a known user or IP address.
 *
 * In compact mode (compact_view = true), only the table or no-results message is rendered, making it suitable for
 * dashboard widgets.
 *
 * The function performs database reads, may resolve WordPress users, may enqueue
 * lazy hostname placeholders, and emits HTML directly when $echo is true.
 *
 * @param array<string,mixed> $args Optional Activity query filters and rendering options.
 * @param bool $echo When true, echoes the generated HTML and returns null. When false,
 *                   returns the generated HTML string.
 *
 * @return string Generated HTML when $echo is false, otherwise null.
 */
function crb_render_activity_view( $args = array(), $echo = true ): string {

	$status_labels = cerber_get_labels( 'status' ) + crb_get_lockout_reason();
	$base_url = cerber_activity_link();

	$is_log_empty = CRB_Activity::is_empty();

	$export_link = '';
	$toolbar = '';
	$result_html = '';

	$fetched = CRB_Activity::fetch( $args );

	if ( $fetched->has_errors() ) {

		$result_html .= crb_ui_renderer()->render_element( crb_ui_log_fetch_error_box( $fetched ) );

		if ( ! $echo ) {
			return $result_html;
		}

		echo $result_html;

		return '';
	}

	$log_rows = $fetched->get_element( 'rows', array() );
	$total = (int) $fetched->get_element( 'total', 0 );
	$filter_values = $fetched->get_element( 'filters', array() );

	$per_page = $filter_values['per_page'];
	$filter_ip = $filter_values['ip'];
	$filter_login = $filter_values['login'];
	$user_id = $filter_values['user_id'];
	$search = $filter_values['search'];
	$sid = $filter_values['sid'];
	$in_url = $filter_values['in_url'];

	$sname = '';
	$extra_view = '';

	$_find = array();

	if ( $filter_login ) {
		$login = explode( '|', $filter_login );
		if ( is_email( $login[0] ) ) {
			$_find[] = array( 'email', $login[0] );
		}
		else {
			$_find[] = array( 'login', $login[0] );
		}

		if ( isset( $login[1] ) ) {
			if ( is_email( $login[1] ) ) {
				$_find[] = array( 'email', $login[1] );
			}
			else {
				$_find[] = array( 'login', $login[1] );
			}
		}
	}

	if ( $search ) {
		if ( cerber_is_ip( $search ) ) {
			$filter_ip = $search;
		}
		else {
			if ( is_email( $search ) ) {
				$_find[] = array( 'email', $search );
			}
			else {
				$_find[] = array( 'login', $search );
			}
		}
	}

	if ( ! $user_id && $_find ) {
		foreach ( $_find as $item ) {
			if ( $user = get_user_by( $item[0], $item[1] ) ) {
				$user_id = $user->ID;
				break;
			}
		}
	}

	if ( $user_id ) {
		$sname = crb_format_user_name( $user_id );
		$extra_view .= cerber_user_extra_view( $user_id );
	}

	if ( $filter_ip ) {
		$extra_view .= cerber_ip_extra_view( $filter_ip );
	}

	if ( $log_rows ) {

		$no_results = false;

		$hidden = array_flip( $args['hide_columns'] ?? array() );

		if ( ! lab_lab() ) {
			$hidden['crb_country_col'] = true;
		}

		$tbody = '';

		if ( ! defined( 'CERBER_FULL_URI' ) || ! CERBER_FULL_URI ) {
			$short = true;
			$site_url = cerber_get_home_url();
			$site_url = substr( $site_url, strpos( $site_url, '://' ) + 3 );
			$start = strlen( rtrim( $site_url, '/' ) );
		}
		else {
			$short = false;
			$start = 0;
		}

		foreach ( $log_rows as $row ) {

			$country = '';
			$user_cell = '';
			$username_cell = '';
			$svg = '';
			$act = (int) $row->activity;

			$ip_id = cerber_get_id_ip( $row->ip );

			$activity = '<span class="crb-activity-single actv' . $act . '" title="' . $act . '">' . crb_get_activity_label( $act, $row->user_id, $row->ac_by_user ) . '</span>';

			if ( empty( $args['no_details'] ) ) {
				$status = (int) $row->ac_status;

				if ( $sts_label = crb_array_get( $status_labels, $status ) ) {
					$activity .= ' <span class = "crb-log-status crb-status-' . $status . '" title="' . $status . '">' . $sts_label . '</span>';
				}

				$details = explode( '|', $row->details );
				$uri = crb_array_get( $details, 4 );

				$svg = CRB_Explainer::create_popup( $row->activity, $row->ac_status, $row->user_id, crb_array_get( $details, 0, '' ), $row->ip );

				if ( $uri
				     && ( $act < CRB_EV_IPL || $act > 12 ) ) {

					if ( $short && 0 === strpos( $uri, $site_url ) ) {
						$ac_uri = substr( $uri, $start );
					}
					else {
						$ac_uri = $uri;
					}

					$activity .= '<div class="crb_act_url" title="' . crb_attr_escape( $uri ) . '">' . str_replace( array( '-', '/' ), array(
							'<wbr>-',
							'<wbr>/'
						), crb_escape_html( urldecode( $ac_uri ) ) ) . '</div>';
				}

			}

			$activity = '<div class="' . crb_get_act_style( $act ) . '">' . $activity . '</div>';

			if ( ! isset( $hidden['crb_user_col'] ) ) {
				$user_cell = '<td>' . crb_admin_get_user_cell( $row->user_id, $base_url ) . '</td>';
			}

			if ( ! isset( $hidden['crb_username_col'] ) ) {
				if ( $row->user_login ) {
					$login_used = crb_escape_html( $row->user_login );
					$username_cell = '<a href="' . $base_url . '&amp;filter_login=' . $login_used . '">' . $login_used . '</a>';
				}

				$username_cell = '<td>' . $username_cell . '</td>';
			}

			$ip_info = cerber_get_ip_info( $row->ip );
			$hostname = $ip_info['hostname_html'] ?? crb_get_ajax_placeholder( 'hostname', $ip_id );

			if ( ! empty( $args['date'] ) && $args['date'] == 'ago' ) {
				$date = cerber_ago_time( $row->stamp );
			}
			else {
				$date = '<span title="' . $row->stamp . ' / ' . $row->session_id . ' / ' . $act . '">' . cerber_date( $row->stamp ) . '</span>';
			}

			if ( ! isset( $hidden['crb_country_col'] ) ) {
				$country = '<td>' . crb_country_html( $row->country, $row->ip ) . '</td>';
			}

			$tbody .= '<tr><td>' . crb_admin_ip_cell( $row->ip, $base_url . '&amp;filter_ip=' . $row->ip ) . '</td><td class="crb-hostname">' . $hostname . '</td>' . $country . '<td>' . $date . '</td><td class="crb-act-info"><div class="crb-act-holder">' . $activity . $svg . '</div></td>' . $user_cell . $username_cell . '</tr>';

		}

		$heading = array(
			'crb_ip_col'       => '<div class="crb_act_icon"></div>' . __( 'IP Address', 'wp-cerber' ),
			'crb_hostname_col' => __( 'Hostname', 'wp-cerber' ),
			'crb_country_col'  => __( 'Country', 'wp-cerber' ),
			'crb_date_col'     => __( 'Date', 'wp-cerber' ),
			'crb_event_col'    => __( 'Event', 'wp-cerber' ),
			'crb_user_col'     => __( 'Local User', 'wp-cerber' ),
			'crb_username_col' => __( 'Username', 'wp-cerber' )
		);

		if ( $hidden ) {
			$heading = array_diff_key( $heading, $hidden );
		}

		$table_header = '';
		$table_footer = '';

		foreach ( $heading as $id => $title ) {
			$table_header .= '<th id="' . $id . '" class="' . $id . '-class">' . $title . '</th>';
			$table_footer .= '<th class="' . $id . '-class">' . $title . '</th>';
		}

		$class = ' widefat crb-table cerber-margin crb-' . count( $heading ) . '-columns';

		if ( $context = $args['context'] ?? '' ) {
			$class .= ' crb-activity--' . crb_sanitize_id( $context );
		}

		$result_html = '<table id="crb-activity" class="' . $class . '"><thead><tr>' . $table_header . '</tr></thead><tfoot><tr>' . $table_footer . '</tr></tfoot><tbody>' . $tbody . '</tbody></table>';

		if ( empty( $args['compact_view'] ) ) {
			$result_html .= cerber_page_navi( $total, $per_page );
		}

		if ( empty( $args['no_export'] ) ) {
			$export_link .= '<a download class="button button-secondary cerber-button" href="' .
			                cerber_admin_link_add( array(
				                'cerber_admin_do' => 'export',
				                'type'            => 'activity',
			                ), true ) . '"><i class="crb-icon crb-icon-bx-download"></i> ' . __( 'Export', 'wp-cerber' ) . '</a>';
		}
	}
	else {

		// No results found -------------------------------------------------------------

		$no_results = true;

		// Is it a search (filter) request?

		$is_search = ( array_intersect_key( CRB_Activity::EVENT_FILTERING_PARAMS, crb_get_query_params() ) );

		$hints = array();

		if ( ! $is_search ) {
			$hints[] = __( 'No activity has been logged yet.', 'wp-cerber' );
		}
		else {
			$hints[] = __( 'No events found using the given search criteria', 'wp-cerber' );

			if ( CRB_Activity_Alerts::alert_exists() ) {
				$hints[] = crb_ui_element( 'p', array(), array(
					__( 'You will be notified when such an event occurs', 'wp-cerber' ) . ' [ ',
					crb_ui_raw_html( crb_admin_alert_dialog( false, '', __( 'Delete', 'wp-cerber' ) ) ),
					' ]',
				) );
			}
            elseif ( $alert_link = crb_admin_alert_dialog( false, __( 'Get me notified when such an event occurs', 'wp-cerber' ) ) ) {
				$hints[] = crb_ui_element( 'p', array(), crb_ui_raw_html( $alert_link ) );
			}

			if ( ! $is_log_empty ) {
				$hints[] = crb_ui_element( 'p', array(), crb_ui_link( $base_url, __( 'View all logged events', 'wp-cerber' ) ) );
			}

			$trf_params = array();
			if ( cerber_is_ip_or_net( $search ) ) {
				$trf_params['filter_ip'] = $search;
			}
			if ( $trf_params ) {
				$hints[] = crb_ui_element( 'p', array( 'class' => 'cerber-margin' ), crb_ui_link( cerber_admin_link( 'traffic', $trf_params ), __( 'Check for requests from the IP address', 'wp-cerber' ) . ' ' . $trf_params['filter_ip'] ) );
			}
		}

		// Let's render a info panel

		$result_html = crb_ui_renderer()->render_element( crb_ui_message_box( $hints ) );
	}

	if ( empty( $args['compact_view'] ) ) {

		$display_full = ( $sid || $in_url ) ? '' : 'display: none;';

		$act_filter_form = '<form action="' . cerber_activity_link() . '"><div id="crb-activity-fields"><div>'

		                   . crb_get_activity_dd()
		                   . cerber_select( 'filter_user', ( $user_id ) ? array( $user_id => $sname ) : array(), $user_id, 'crb-select2-ajax', '', false, esc_html__( 'Filter by registered user', 'wp-cerber' ), array( 'min_symbols' => 3 ) )
		                   . '<input type="text" value="' . $search . '" name="search_activity" placeholder="' . esc_html__( 'Search for IP or username', 'wp-cerber' ) . '">
	   
		   <div id="crb-more-activity-fields" style="' . $display_full . '">		           
		   <input type="text" value="' . $sid . '" name="filter_sid" placeholder="' . esc_html__( 'Request ID', 'wp-cerber' ) . '">
		   <input type="text" value="' . $in_url . '" name="search_url" placeholder="' . esc_html__( 'Search in URL', 'wp-cerber' ) . '">		           
		   </div>
		   
		   </div>
		   
		   <div class="crb-act-controls">
		   <button type="submit" class="cerber-button button button-primary">
		   ' . __( 'Filter', 'wp-cerber' ) . '
		   </button>		   
		   </div>
		   
		   <div class="crb-act-controls">
		   [&nbsp;<a href="#" class="crb-opener" data-target="crb-more-activity-fields">More</a>&nbsp;]		   
		   </div>
		   		   
		   </div>
		   		      
		   <input type="hidden" name="page" value="cerber-security" >
		   <input type="hidden" name="tab" value="activity">
		   </form>';

		$right_links = ! $no_results ? crb_admin_alert_dialog() : '';

		$toolbar_secondary = '<div id="crb-admin-toolbar-secondary-row"><div>' . $act_filter_form . '</div><div>' . $right_links . $export_link . '</div></div><br style="clear: both;">';

		$quick_nav = ! $is_log_empty ? crb_admin_activity_nav_links() : '';

		$toolbar_primary = '<div id="crb-admin-toolbar-primary-row"><div class="crb-quick-nav">' . $quick_nav . '</div></div>';

		$toolbar = '<div id="crb-admin-toolbar">' . $toolbar_primary . $toolbar_secondary . $extra_view . '</div>';

	}

	$return = $toolbar . $result_html;

	if ( ! $echo ) {
		return $return;
	}

	echo $return;

	return '';
}

/**
 * Generates activity breakdown report
 *
 * @param bool $just_check If true, will return true if the activity log contains data for generating the report
 *
 * @return string HTML code of the report table
 *
 * @since 9.6.4.1
 */
function cerber_activity_summary( $just_check = false ) {

	$stamp = time() - 24 * 3600;

	$rows = array();
	$not_in = ' activity NOT IN (' . crb_get_activity_set( 'blocked', true ) . ')';

	$logged = (bool) cerber_db_get_var( 'SELECT ip FROM ' . CERBER_LOG_TABLE . ' WHERE stamp >= ' . $stamp . ' AND ' . $not_in . ' LIMIT 1' );

	if ( ! $logged ) {
		return '';
	}

	if ( $just_check ) {
		return true;
	}

	$events = cerber_db_get_results( 'SELECT activity, COUNT(activity) cnt FROM ' . CERBER_LOG_TABLE . ' WHERE stamp >= ' . $stamp . ' AND ' . $not_in . ' GROUP by activity ORDER BY cnt DESC', MYSQL_FETCH_OBJECT );

	$events = array_slice( $events, 0, 10 );

	$events_not = cerber_db_get_results( 'SELECT activity, COUNT(activity) cnt FROM ' . CERBER_LOG_TABLE . ' WHERE stamp >= ' . $stamp . ' AND user_id = 0 GROUP by activity', MYSQL_FETCH_OBJECT_K );
	$events_aut = cerber_db_get_results( 'SELECT activity, COUNT(activity) cnt FROM ' . CERBER_LOG_TABLE . ' WHERE stamp >= ' . $stamp . ' AND user_id > 0 GROUP by activity', MYSQL_FETCH_OBJECT_K );

	$lables = cerber_get_labels();
	$ac_base = cerber_admin_link( 'activity' );
	$ac_base_time = $ac_base . '&amp;filter_time_begin=' . $stamp;

	foreach ( $events as $a ) {
		if ( $a->cnt < 1 ) {
			break;
		}

		$act = absint( $a->activity );

		$no_user = 0;
		if ( $r = crb_array_get( $events_not, $a->activity, 0 ) ) {
			$no_user = '<a href="' . $ac_base_time . '&amp;filter_activity=' . $act . '&amp;filter_user=0">' . $r->cnt . '</a>';
		}

		$yes_user = 0;
		if ( $r = crb_array_get( $events_aut, $a->activity, 0 ) ) {
			$yes_user = '<a href="' . $ac_base_time . '&amp;filter_activity=' . $act . '&amp;filter_user=*">' . $r->cnt . '</a>';
		}

		$rows[] = array(
			'<a href="' . $ac_base . '&amp;filter_activity=' . $act . '">' . $lables[ $act ] . '</a>',
			$yes_user,
			$no_user,
			'<a href="' . $ac_base_time . '&amp;filter_activity=' . $act . '">' . $a->cnt . '</a>'
		);
	}

	return cerber_make_table( $rows, array( __( 'Event', 'wp-cerber' ), __( 'Known Users', 'wp-cerber' ), __( 'Unauthenticated Users', 'w-cerber' ), __( 'Total', 'w-cerber' ) ), '', 'crb-table-align-right-2', '', '', false );
}

/**
 * Generates a report of the top 10 malicious IP addresses detected in the past 24 hours.
 *
 * The report includes the total number of incidents, spam activities, and blocked actions
 * for each IP address. Each entry also provides additional metadata such as hostname and
 * country (if available). The results are returned as an HTML table.
 *
 * @return string|array
 *
 * @since 9.6.4.7
 */
function crb_top_malicious_ip_report() {

	$stamp = time() - 24 * 3600;
	$in = crb_get_activity_set( 'suspicious', true );
	$in_spam = crb_get_activity_set( 'spam', true );

	$results = cerber_db_get_results(
		'SELECT ip, country, COUNT(*) AS total,
                SUM(activity IN (' . CRB_EV_IPL . ', ' . CRB_EV_NEL . ')) AS locked_out_sum,
                SUM(activity IN (' . $in_spam . ')) AS spam_sum 
         FROM ' . CERBER_LOG_TABLE . ' 
         WHERE stamp >= ' . $stamp . ' AND activity IN (' . $in . ') 
         GROUP BY ip 
         ORDER BY total DESC 
         LIMIT 10',
		MYSQL_FETCH_OBJECT
	);

	if ( empty( $results ) ) {
		return array( CRB_Widgets::NO_DATA => __( 'No malicious activity detected in the past 24 hours.', 'wp-cerber' ) );
	}

	$rows = [];

	$base_url = cerber_activity_link();
	$base_url_time = $base_url . '&amp;filter_time_begin=' . $stamp;
	$show_country = false;

	foreach ( $results as $row ) {

		$ip_id = cerber_get_id_ip( $row->ip );
		$ip_info = cerber_get_ip_info( $row->ip );
		$hostname = $ip_info['hostname_html'] ?? crb_get_ajax_placeholder( 'hostname', $ip_id );
		$country = crb_country_html( $row->country, $row->ip );

		$cells = [
			crb_admin_ip_cell( $row->ip, $base_url . '&amp;filter_ip=' . $row->ip ),
			array( 'cell' => $hostname, 'class' => 'crb-hostname' ),
		];

		if ( $country ) {
			$cells[] = $country;
			$show_country = true;
		}

		$link_base = $base_url_time . '&amp;filter_ip=' . $row->ip;

		$cells[] = '<a href="' . $link_base . '&amp;filter_set=1">' . $row->total . '</a>';
		$cells[] = $row->spam_sum ? '<a href="' . $link_base . '&amp;filter_set=3">' . $row->spam_sum . '</a>' : $row->spam_sum;
		$cells[] = $row->locked_out_sum ? '<a href="' . $link_base . '&amp;filter_activity[0]=' . CRB_EV_IPL . '&amp;filter_activity[1]=' . CRB_EV_NEL . '">' . $row->locked_out_sum . '</a>' : $row->locked_out_sum;

		$rows[] = $cells;
	}

	$heading = [ '<div class="crb_act_icon"></div>' . __( 'IP Address', 'wp-cerber' ), __( 'Hostname', 'wp-cerber' ) ];

	if ( $show_country ) {
		$heading[] = __( 'Country', 'wp-cerber' );
	}

	$heading[] = __( 'Incidents', 'wp-cerber' );
	$heading[] = __( 'Spam', 'wp-cerber' );
	$heading[] = __( 'Locked Out', 'wp-cerber' );

	return cerber_make_table(
		$rows,
		$heading,
		'',
		'crb-activity--dashboard crb-table-align-right-4',
		'',
		'',
		false
	);
}

/**
 * @param int $ac Activity ID
 *
 * @return string CSS class name
 *
 * @since 9.5.3.1
 */
function crb_get_act_style( $ac ) {
	static $green = array( CRB_EV_LIN );

	$ac = crb_absint( $ac );

	$class = '';

	if ( in_array( $ac, $green ) ) {
		$class = 'crb-act-granted';
	}
    elseif ( in_array( $ac, crb_get_activity_set( 'denied_by_crb' ) ) ) {
		$class = 'crb-act-denied';
	}

	return 'crb-act-' . $ac . ' ' . $class;
}

/**
 * Generates an HTML markup for a given IP address to be shown on the plugin admin pages
 *
 * @param string $ip Valid IP address
 * @param string $ip_link A link to wrap the IP address
 * @param string $html Any escaped HTML code or plain text
 *
 * @return string A formatted HTML view of a given IP address
 */
function crb_admin_ip_cell( $ip, $ip_link = '', $html = '' ) {
	static $cache = array();

	if ( isset( $cache[ $ip ] ) ) {
		return $cache[ $ip ];
	}

	$acl = cerber_acl_check( $ip, '', 0, $row );

	$tip = '';

	if ( ! empty( $row ) ) {
		$tip = $row->comments;
	}
	else {
		if ( $acl == 'W' ) {
			$tip = __( 'Allowed IP Access List', 'wp-cerber' );
		}
        elseif ( $acl == 'B' ) {
			$tip = __( 'Blocked IP Access List', 'wp-cerber' );
		}
	}

	if ( $b = cerber_block_check( $ip ) ) {
		$block = ' crb_color_blocked ';
		$tip = ( $tip ) ? ' / ' . $b->reason : $b->reason;
	}
	else {
		$block = '';
	}

	if ( $ip_link ) {
		$ip = '<a href="' . crb_escape_url( $ip_link ) . '">' . $ip . '</a>';
	}

	$cache[ $ip ] = '<div class="crb_css_table"><div><span class="crb_act_icon crb_ip_acl' . $acl . ' ' . $block . '" title="' . crb_attr_escape( $tip ) . '"></span></div><div>' . $ip . $html . '</div></div>';

	return $cache[ $ip ];
}

/*
 * Additional information about IP address
 */
function cerber_ip_extra_view( $ip, $context = 'activity' ) {
	if ( ! $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return '';
	}

	$html = crb_generate_ip_extra_view( $ip, $context, true );
	$class = 'crb-extra-info';
	$ajax = '';

	if ( ! $html ) {
		$ajax = ' data-ajax_route="ip_extra_info" data-ip="' . $ip . '"';
		$class .= ' crb_async_content';
		$html = UIS_LOADER_HTML;
	}

	return '<div id="crb-extra-ip-info" class="' . $class . '" ' . $ajax . '>' . $html . '</div>';
}

/**
 * @param array $request
 * @param array $ref_params
 *
 * @return array
 */
function cerber_ip_extra_view_ajax( $request, $ref_params ) {
	$ip = filter_var( $request['ip'], FILTER_VALIDATE_IP );
	$tab = $ref_params['tab'] ?? '';

	return crb_generate_ip_extra_view( $ip, $tab );
}

function crb_generate_ip_extra_view( $ip, $context = 'activity', $cache_only = false ) {

	if ( ! $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
		return '';
	}

	$ip_navs = '';

	if ( $context == 'activity' ) {
		$ip_navs .= crb_get_traffic_link( array( 'filter_ip' => $ip ) );
	}
	else {
		$ip_navs .= ' <a class="crb-button-tiny" href="' . cerber_admin_link( 'activity', array( 'filter_ip' => $ip ) ) . '">' . __( 'Check for activities', 'wp-cerber' ) . '</a>';
	}

	$acl = cerber_acl_check( $ip, '', 0, $row );

	$comments = ( $row ) ? crb_attr_escape( $row->comments ) : '';

	$ip_status = '';

	if ( $acl == 'W' ) {
		$ip_status .= '<span title="' . $comments . '" class="crb_color_green crb_ip_info_label">' . __( 'Allowed IP Access List', 'wp-cerber' ) . '</span>';
	}
    elseif ( $acl == 'B' ) {
		$ip_status .= '<span title="' . $comments . '" class="crb_color_black crb_ip_info_label">' . __( 'Blocked IP Access List', 'wp-cerber' ) . '</span>';
	}

	$key_cache = '_cache_ipx_' . $ip . $context . (string) $acl;

	if ( $block = cerber_block_check( $ip ) ) {
		$ip_status .= '<span class="crb_color_blocked crb_ip_info_label">' . __( 'Locked out', 'wp-cerber' ) . '</span><span class="crb-log-status crb-status-' . $block->reason_id . '">' . $block->reason . '</span>';
		$key_cache .= '_blocked';
	}

	if ( $cache = cerber_get_set( $key_cache, null, false, true ) ) {
		return $cache;
	}
    elseif ( $cache_only ) {
		return '';
	}

	$rdap_info = '';
	$country = '';
	$abuse = '';
	$network = '';
	$network_info = '';
	$network_navs = '';
	$to_cache = true;

	if ( crb_get_settings( 'ip_extra' ) ) {

		$ip_rdap = CRB_RDAP_Client::get_parsed_ip_info( $ip );

		if ( crb_is_wp_error( $ip_rdap ) ) {
			$rdap_info = $ip_rdap->get_error_message();
			$to_cache = false;
		}
		else {

			$rdap_info = CRB_RDAP_Client::render_html_view( $ip_rdap );

			if ( $country = $ip_rdap->country ) {
				$country = crb_get_flag_html( $country, '<b>' . crb_get_country_name( $country ) . ' (' . $country . ')</b>' );
			}

			if ( $network = $ip_rdap->cidr ) {
				$range = cerber_any2range( $network );
				/* translators: %s is the network CIDR. */
				$network_info = sprintf( __( 'Network: %s', 'wp-cerber' ), $network );
				$network_navs = '<a class="crb-button-tiny" href="' . cerber_admin_link( 'activity', array( 'filter_ip' => $range['range'] ) ) . '">' . __( 'Check for activities', 'wp-cerber' ) . '</a> ' . crb_get_traffic_link( array( 'filter_ip' => $range['range'] ) );
			}
		}
	}

	$form = '';

	if ( ! cerber_is_myip( $ip ) && ! cerber_acl_check( $ip ) ) {

		if ( $network ) {
			$net_button = '<button type="submit" value="' . $network . '" name="add_acl" class="button button-secondary cerber-button">';
		}
		else {
			$net_button = '<button disabled="disabled" class="button button-secondary cerber-button">';
		}

		$net_button .= __( 'Block subnet', 'wp-cerber' ) . '</button> ';

		$form = '<form id="add_acl_black" action="" method="post">
                    <input type="hidden" name="cerber_admin_do" value="add2acl">
				    <input type="hidden" name="add_acl_comment" value="">
				    <input type="hidden" name="acl_tag" value="B">
				    <button type="submit" value="' . $ip . '" name="add_acl" class="crb-button-tiny">' . __( 'Block IP address', 'wp-cerber' ) . '</button>' .
		        '<!-- <p>' . $net_button . '</p>-->' .
		        cerber_nonce_field( 'control' ) .
		        '</form>';
	}

	$ip_info = '<span id="crb-ip-address">' . $ip . '</span><span id="crb-ip-country">' . $country . '</span>';

	$ip_insights = crb_generate_ip_insights( $ip, $context );

	$ret = '
		<div class="crb_extra_info_row">
		
		<div id="crb_the_summary">
		    <div><div style="white-space: nowrap;">' . $ip_info . '</div><div class="crb-nav-row">' . $ip_status . ' ' . $ip_navs . ' ' . $form . '</div></div>
		    <div><div style="white-space: nowrap;">' . $network_info . '</div><div class="crb-nav-row">' . $network_navs . '</div></div>' . $abuse . '
		</div>
		<div class="crb_async_content" data-ajax_route="ip_quick_analytics" data-ip_address="' . $ip . '" data-the_active_tab="' . $context . '">' . $ip_insights . '
		</div>
						
		</div>
		
		<div id="crb-whois-data">' . $rdap_info . '</div>		
		';

	if ( $to_cache ) {
		cerber_update_set( $key_cache, $ret, 0, false, time() + 7200, true );
	}

	return $ret;
}

/**
 * @param string $ip
 * @param string $tab
 * @param false $cache_only
 *
 * @return string|false
 */
function crb_generate_ip_insights( $ip, $tab = 'activity', $cache_only = false ) {

	if ( $tab != 'activity' ) {
		$tab = 'traffic';
	}

	$result = '';
	$links = array();

	$labels = cerber_get_labels();

	if ( $data = crb_q_cache_get( 'SELECT COUNT(activity) as cnt, activity FROM ' . CERBER_LOG_TABLE . ' WHERE ip = "' . $ip . '" GROUP BY activity', CERBER_LOG_TABLE, $cache_only ) ) {
		$links[] = array( array( 'filter_ip' => $ip ), __( 'All' ) );
		foreach ( $data as $item ) {
			$links[] = array( array( 'filter_activity' => $item[1], 'filter_ip' => $ip ), crb_array_get( $labels, $item[1], 'Unknown' ) . ' - ' . $item[0] );
		}
	}

	if ( $links ) {
		$result = '<div id="crb-quick-insights">' . crb_build_quick_nav_block( $links, $tab ) . '</div>';
	}

	if ( ! $result ) {
		if ( $cache_only ) {
			return false;
		}

		$result = __( 'No activity has been logged yet.', 'wp-cerber' );
	}

	return $result;
}

/**
 * Prepare a short report (extra information) about a given user
 *
 * @param int $user_id
 * @param string $context
 *
 * @return string
 */
function cerber_user_extra_view( int $user_id, string $context = 'activity' ): string {
	global $wpdb;

	if ( ! $user = crb_get_userdata( $user_id ) ) {
		return '';
	}

	$ret = '';
	$class = '';
	$user_profile = '';
	$user_summary = array();

	if ( $avatar = (string) get_avatar( $user_id, 96 ) ) {
		$user_profile = '<div style="margin-bottom: 1em;">' . $avatar . '</div>';
	}

	if ( ! is_multisite() && $user->roles ) {
		$roles = array();
		$wp_roles = wp_roles();
		foreach ( $user->roles as $role ) {
			$roles[] = $wp_roles->roles[ $role ]['name'];
		}
		$roles = '<span class="crb_act_role">' . implode( ', ', $roles ) . '</span>';
	}
	else {
		$roles = '';
	}

	if ( ! nexus_is_valid_request() ) {
		$name = '<a href="' . get_edit_user_link( $user_id ) . '" target="_blank">' . $user->display_name . '</a>';
	}
	else {
		$name = $user->display_name;
	}

	$name = '<span class="crb-user-name">' . $name . '</span><p>' . $roles . '</p>';

	$user_profile .= '<div>' . $name . '</div>';

	// Last seen

	$seen = array();
	$seen[0] = $wpdb->get_row( 'SELECT stamp,ip FROM  ' . CERBER_TRAF_TABLE . ' WHERE user_id = ' . $user_id . ' ORDER BY stamp DESC LIMIT 1' );
	$seen[1] = $wpdb->get_row( 'SELECT stamp,ip FROM  ' . CERBER_LOG_TABLE . ' WHERE user_id = ' . $user_id . ' AND ac_by_user = 0 ORDER BY stamp DESC LIMIT 1' );
	$seen[2] = $wpdb->get_row( 'SELECT stamp,ip FROM  ' . CERBER_LOG_TABLE . ' WHERE ac_by_user = ' . $user_id . ' ORDER BY stamp DESC LIMIT 1' );

	$tmp = array();
	foreach ( $seen as $key => $log_row ) {
		$tmp[ $key ] = ( $log_row ) ? $log_row->stamp : 0;
	}

	if ( $max = max( $tmp ) ) {
		$max_keys = array_keys( $tmp, $max );
		$last_log = $seen[ $max_keys[0] ];

		$last_seen = '<span title="' . cerber_date( $last_log->stamp, false ) . '">' . cerber_ago_time( $last_log->stamp ) . '</span>';
		$country = crb_country_html( null, $last_log->ip );
		$user_summary[] = array( __( 'Last seen', 'wp-cerber' ), $last_seen, $country );
	}

	// Last login

	$last_login = crb_get_last_user_login( $user_id );

	if ( ! empty( $last_login ) ) {
		$country = crb_country_html( $last_login['cn'], $last_login['ip'] );
		$user_summary[] = array( __( 'Last login', 'wp-cerber' ), cerber_date( $last_login['ts'], false ), $country );
	}

	// Registered (created)

	if ( $time = strtotime( cerber_db_get_var( "SELECT user_registered FROM  {$wpdb->users} WHERE id = " . $user_id ) ) ) {
		$reg_date = cerber_auto_date( $time, false );
		$reg_event = __( 'Registered', 'wp-cerber' );
		$country = '';

		if ( $reg_meta = get_user_meta( $user_id, '_crb_reg_', true ) ) {
			if ( $reg_meta['IP'] ?? false ) {
				$country = crb_country_html( null, $reg_meta['IP'] );
			}
			if ( $reg_meta['user'] ?? false ) {
				$reg_event = crb_get_activity_label( 1, $user_id, $reg_meta['user'] );
			}
		}

		$user_summary[] = array( $reg_event, $reg_date, $country );
	}

	// Activated - BuddyPress

	if ( $log = CRB_Activity::get_log( array( 200 ), array( 'id' => $user_id ) ) ) {
		$acted = $log[0];
		$activated = cerber_auto_date( $acted->stamp );
		$country = crb_country_html( null, $acted->ip );

		$user_summary[] = array( __( 'Activated', 'wp-cerber' ), $activated, $country );
	}

	$usn = crb_sessions_get_num( $user_id );
	$sess_link = $usn ? '<a href="' . cerber_admin_link( 'sessions', array( 'filter_user' => $user_id ) ) . '">' . $usn . '</a>' : '0';
	$user_summary[] = array(
		'<div style="white-space: nowrap;">' . __( 'Active sessions', 'wp-cerber' ) . '</div>',
		$sess_link
	);

	// Make a link to switch to other log

	if ( $context == 'activity' ) {
		$link = crb_get_traffic_link( array( 'filter_user' => $user_id ), false );
	}
	else {
		$link = ' <a href="' . cerber_admin_link( 'activity', array( 'filter_user' => $user_id ) ) . '">' . __( 'Check for activities', 'wp-cerber' ) . '</a>';
	}

	$user_summary[] = array( $link );

	$summary = '';
	foreach ( $user_summary as $row ) {
		$row = array_pad( $row, 3, '' );
		$summary .= '<tr><td>' . implode( '</td><td>', $row ) . '</td></tr>';
	}

	if ( $us_sts = crb_get_user_auth_status( $user ) ) {
		$summary .= '<tr><td colspan="3"><div>' . crb_format_user_status( $us_sts ) . '</div></td></tr>';
	}

	$summary = '<table id="crb-user-summary">' . $summary . '</table>';

	$ins_class = '';
	if ( ! $ins = crb_generate_user_insights( $user_id, $context, true ) ) {
		$ins_class = 'crb_async_content';
	}

	$ret .= '<div class="crb_extra_info_row"><div>' . $user_profile . '</div><div id="crb_the_summary">' . $summary . '</div><div class="' . $ins_class . '" data-ajax_route="user_activity_analytics" data-user_id="' . $user_id . '" data-the_active_tab="' . $context . '">' . $ins . '</div></div>';

	return '<div class="crb-extra-info ' . $class . '" id="crb-user-extra-info">' . $ret . '</div>';

}

// Users -------------------------------------------------------------------------------------

add_filter( 'users_list_table_query_args', function ( $args ) {
	if ( crb_get_settings( 'usersort' ) && empty( $args['orderby'] ) ) {
		$args['orderby'] = 'user_registered';
		$args['order'] = 'desc';
	}

	return $args;
} );

/*
	Add custom columns to the Users admin screen
*/
add_filter( 'manage_users_columns', function ( $columns ) {
	return array_merge( $columns,
		array(
			'cbcc' => __( 'Comments', 'wp-cerber' ),
			'cbla' => __( 'Last login', 'wp-cerber' ),
			'cbfl' => '<span title="In last 24 hours">' . __( 'Failed login attempts', 'wp-cerber' ) . '</span>',
			'cbdr' => __( 'Registered', 'wp-cerber' )
		) );
} );
add_filter( 'manage_users_sortable_columns', function ( $sortable_columns ) {
	$sortable_columns['cbdr'] = 'user_registered';

	return $sortable_columns;
} );
/*
	Display custom columns on the Users screen
*/
add_filter( 'manage_users_custom_column', function ( $value, $column, $user_id ) {
	global $wpdb, $user_ID;

	$ret = $value;

	switch ( $column ) {
		case 'cbcc' :
		case 'cbla' :
		case 'cbfl' :
			$ret = crb_get_ajax_placeholder( $column, $user_id );
			break;
		case 'cbdr' :
			$time = strtotime( cerber_db_get_var( "SELECT user_registered FROM  $wpdb->users WHERE id = " . $user_id ) );
			$ret = cerber_auto_date( $time );
			if ( $reg_data = get_user_meta( $user_id, '_crb_reg_', true ) ) {

				if ( $ip = filter_var( $reg_data['IP'], FILTER_VALIDATE_IP ) ) {
					$act_link = cerber_admin_link( 'activity', array( 'filter_ip' => $ip ) );
					$ret .= '<br /><a href="' . $act_link . '">' . $ip . '</a>';
					if ( $country = crb_country_html( null, $ip ) ) {
						$ret .= '<br />' . $country;
					}
				}

				if ( $uid = absint( $reg_data['user'] ) ) {
					$name = cerber_db_get_var( 'SELECT meta_value FROM ' . $wpdb->usermeta . ' WHERE user_id  = ' . $uid . ' AND meta_key = "nickname"' );
					if ( ! $user_ID ) {
						$user_ID = get_current_user_id();
					}
					if ( $user_ID == $uid ) {
						$name .= ' (' . __( 'You', 'wp-cerber' ) . ')';
					}
					$ret .= '<br />' . $name;
				}
			}
			break;
	}

	return $ret;
}, 10, 3 );

/*
 	Registering WordPress Dashboard widgets
*/
if ( ! is_multisite() ) {
	add_action( 'wp_dashboard_setup', 'cerber_widgets' );
}
else {
	add_action( 'wp_network_dashboard_setup', 'cerber_widgets' );
}
function cerber_widgets() {
	if ( cerber_user_can_manage() ) {
		wp_add_dashboard_widget( 'cerber_quick', __( 'Cerber Quick View', 'wp-cerber' ), 'cerber_quick_w' );
	}
}

/*
	Cerber Quick View widget - WordPress Dashboard
*/
function cerber_quick_w() {

	$dash = cerber_admin_link();
	$act = cerber_admin_link( 'activity' );
	$traf = cerber_admin_link( 'traffic' );
	$scanner = cerber_admin_link( 'scan_main' );
	$acl = cerber_admin_link( 'acl' );
	$sess = cerber_admin_link( 'sessions' );
	$locks = cerber_admin_link( 'lockouts' );

	$s_count = cerber_db_get_var( 'SELECT COUNT(DISTINCT user_id) FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE );

	$failed = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . CRB_EV_LFL . ') AND stamp > ' . ( time() - 24 * 3600 ) );
	$failed_prev = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . CRB_EV_LFL . ') AND stamp > ' . ( time() - 48 * 3600 ) . ' AND stamp < ' . ( time() - 24 * 3600 ) );

	$failed_ch = cerber_percent( $failed_prev, $failed );

	$locked = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . CRB_EV_IPL . ',' . CRB_EV_NEL . ') AND stamp > ' . ( time() - 24 * 3600 ) );
	$locked_prev = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . CRB_EV_IPL . ',' . CRB_EV_NEL . ') AND stamp > ' . ( time() - 48 * 3600 ) . ' AND stamp < ' . ( time() - 24 * 3600 ) );

	$locked_ch = cerber_percent( $locked_prev, $locked );

	$lockouts = crb_get_locked_out_num();

	if ( $last = cerber_db_get_var( 'SELECT MAX(stamp) FROM ' . CERBER_LOG_TABLE . ' WHERE  activity IN (' . CRB_EV_IPL . ',' . CRB_EV_NEL . ')' ) ) {
		$last = cerber_ago_time( $last );
	}
	else {
		$last = __( 'Never', 'wp-cerber' );
	}

	$w_count = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_ACL_TABLE . ' WHERE tag ="W"' );
	$b_count = cerber_db_get_var( 'SELECT count(ip) FROM ' . CERBER_ACL_TABLE . ' WHERE tag ="B"' );

	if ( cerber_is_citadel() ) {
		$citadel = '<span style="color:#FF0000;">' . __( 'Active', 'wp-cerber' ) . '</span> (<a href="' . cerber_admin_link_add( [ 'citadel_do' => 'deactivate' ] ) . '">' . __( 'deactivate', 'wp-cerber' ) . '</a>)';
	}
	else {
		if ( crb_get_settings( 'citadel_on' ) && crb_get_settings( 'ciperiod' ) ) {
			$citadel = __( 'Inactive', 'wp-cerber' );
		}
		else {
			$citadel = __( 'Disabled', 'wp-cerber' );
		}
	}

	echo '<div class="cerber-widget">';

	echo '<table style="width:100%;"><tr><td style="width:50%; vertical-align:top;"><table><tr><td class="bigdig">' . $failed . '</td><td class="per">' . $failed_ch . '</td></tr></table><p>' . __( 'failed attempts in the last 24 hours', 'wp-cerber' ) . '<br/>(<a href="' . $act . '&amp;filter_activity=' . CRB_EV_LFL . '">' . __( 'view all', 'wp-cerber' ) . '</a>)</p></td>';
	echo '<td style="width:50%; vertical-align:top;"><table><tr><td class="bigdig">' . $locked . '</td><td class="per">' . $locked_ch . '</td></tr></table><p>' . __( 'lockouts in the last 24 hours', 'wp-cerber' ) . '<br/>(<a href="' . $act . '&amp;filter_activity[]=' . CRB_EV_IPL . '&filter_activity[]=' . CRB_EV_NEL . '">' . __( 'view all', 'wp-cerber' ) . '</a>)</p></td></tr></table>';

	echo '<table id="quick-info"><tr><td>' . __( 'Active lockouts', 'wp-cerber' ) . '</td><td><b><a href="' . $locks . '">' . $lockouts . '</a></b></td></tr>';
	echo '<tr><td>' . __( 'Last lockout', 'wp-cerber' ) . '</td><td>' . $last . '</td></tr>';

	/* translators: %d is the number of the users. */
	$us_label = sprintf( _n( '%d user', '%d users', $s_count, 'wp-cerber' ), $s_count );

	echo '<tr class="with-padding"><td>' . __( 'Logged-in users', 'wp-cerber' ) . '</td><td><b><a href="' . $sess . '">' . $us_label . '</a></b></td></tr>';

	/* translators: %d is the number of entries in the list. */
	$al_label = sprintf( _n( '%d entry', '%d entries', $w_count, 'wp-cerber' ), $w_count );

	echo '<tr class="with-padding"><td>' . __( 'Allowed IP Access List', 'wp-cerber' ) . '</td><td><b><a href="' . $acl . '">' . $al_label . '</a></b></td></tr>';

	/* translators: %d is the number of entries in the list. */
	$bl_label = sprintf( _n( '%d entry', '%d entries', $b_count, 'wp-cerber' ), $b_count );

	echo '<tr><td>' . __( 'Blocked IP Access List', 'wp-cerber' ) . '</td><td><b><a href="' . $acl . '">' . $bl_label . '</a></b></td></tr>';
	echo '<tr class="with-padding"><td>' . __( 'Citadel mode', 'wp-cerber' ) . '</td><td><b>' . $citadel . '</b></td></tr>';

	$status = ( ! crb_get_settings( 'tienabled' ) ) ? '<span style="color: red;">' . __( 'Disabled', 'wp-cerber' ) . '</span>' : __( 'Enabled', 'wp-cerber' );
	echo '<tr class="with-padding"><td>' . __( 'Traffic Inspector', 'wp-cerber' ) . '</td><td><b>' . $status . '</b></td></tr>';

	$lab = lab_lab();
	if ( $lab ) {
		$status = ( ! lab_is_cloud_ok() ) ? '<span style="color: red;">' . __( 'No connection', 'wp-cerber' ) . '</span>' : __( 'Active', 'wp-cerber' );
		echo '<tr><td>Cloud Protection</td><td><b>' . $status . '</b></td></tr>';
	}

	$s = '';
	$scan = cerber_get_scan();
	if ( ! $scan || ( WEEK_IN_SECONDS < ( time() - $scan['finished'] ) ) ) {
		$s = 'style="color: red;"';
	}

	if ( $scan ) {
		$lms = $scan['mode_h'] . ' ' . cerber_auto_date( $scan['started'] );
	}
	else {
		$lms = __( 'Never', 'wp-cerber' );
	}

	echo '<tr ' . $s . '><td>' . _x( 'Last malware scan', 'Example: Last malware scan: 23 Jan 2018', 'wp-cerber' ) . '</td><td><a href="' . $scanner . '">' . $lms . '</a></td></tr>';

	$link = cerber_admin_link( 'scan_schedule' );
	$quick = ( ! $lab || ! $q = absint( crb_get_settings( 'scan_aquick' ) ) ) ? __( 'Disabled', 'wp-cerber' ) : cerber_get_qs( $q );
	echo '<tr><td>' . __( 'Quick Scan', 'wp-cerber' ) . '</td><td><a href="' . $link . '">' . $quick . '</a></td></tr>';
	$f = ( ! $lab || ! crb_get_settings( 'scan_afull-enabled' ) ) ? __( 'Disabled', 'wp-cerber' ) : crb_get_settings( 'scan_afull' );
	echo '<tr><td>' . __( 'Full Scan', 'wp-cerber' ) . '</td><td><a href="' . $link . '">' . $f . '</a></td></tr>';

	/*
	$dev = crb_get_settings('pbdevice');
	if (!$dev || $dev == 'N') echo '<tr><td style="padding-top:15px;">'.__('Push notifications','wp-cerber').'</td><td style="padding-top:15px;"><b>not configured</b></td></tr>';
	*/
	echo '</table></div>';

	echo '<div class="wilinks">
	<a href="' . $dash . '"><i class="crb-icon crb-icon-bxs-dashboard"></i> ' . __( 'Dashboard', 'wp-cerber' ) . '</a> |
	<a href="' . $act . '"><i class="crb-icon crb-icon-bx-pulse"></i> ' . __( 'Activity', 'wp-cerber' ) . '</a> |
	<a href="' . $traf . '"><i class="crb-icon crb-icon-bx-show"></i> ' . __( 'Traffic', 'wp-cerber' ) . '</a> |
	<a href="' . $scanner . '"><i class="crb-icon crb-icon-bx-radar"></i> ' . __( 'Integrity', 'wp-cerber' ) . '</a>
	</div>';

	if ( cerber_check_new_version() ) {
		echo '<div class="up-cerber">' . __( 'A new version is available', 'wp-cerber' ) . '</div>';
	}
}

/**
 *
 * WP Cerber Dashboard
 *
 *
 */
function cerber_show_dashboard() {

	cerber_widgets_init();

	$all_widgets = CRB_Widgets::get_titles( true );

	echo '<div id="crb-dashboard-container">';

	foreach ( $all_widgets as $widget_id => $titles ) {

		$title = $titles[0];
		$subtitle = $titles[1] ? '<span class="crb-widget-subtitle"> &nbsp;&#x2500;&#x2500;&nbsp; ' . $titles[1] . '</span>' : '';

		$widget = CRB_Widgets::render_widget( $widget_id, false );

		$show_control = true;

		if ( crb_is_wp_error( $widget ) ) {
			$body = $widget->get_error_message();
		}
        elseif ( is_array( $widget ) ) {
			$body = $widget[0] ?? '';
			$show_control = $widget[1] ?? true;
		}
		else {
			$body = $widget;
		}

		if ( ! $body || ! is_string( $body ) ) {
			continue;
		}

		$controls = CRB_Widgets::get_controls( $widget_id );

		$visibility = $show_control ? '' : ' style="visibility: hidden;" ';

		$heading = ( ! CRB_Widgets::hide_header( $widget_id ) && $title ) ? '<div class="crb-widget-heading"><div class="crb-widget-title-cell"><h2 class="crb-widget-title crb-dash-padding">' . $title . '</h2>' . $subtitle . '</div><div class="crb-widget-controls" ' . $visibility . '>' . $controls . '</div></div>' : '';

		echo '<div class="crb-dashboard-element" data-widget_id="' . $widget_id . '">' . $heading . '<div class="crb-widget-content" data-from_cache="' . ( CRB_Widgets::$from_cache ? 1 : 0 ) . '">' . $body . '</div></div>';
	}

	echo '</div>';

	echo '<div style="text-align: right">' . crb_admin_screen_dialog() . '</div>';
}

/**
 * Initializing and registering dashboard widgets code.
 *
 * @return void
 *
 * @since 9.6.4.2
 */
function cerber_widgets_init() {

	CRB_Widgets::register( 'wgt_ac_kpi',
		__( 'Security Status Monitor', 'wp-cerber' ),
		'',
		'',
		function () {
			$kpi_list = cerber_calculate_kpi( time() - 24 * 3600, time() );

			$kpi_show = '';
			foreach ( $kpi_list as $kpi ) {
				$kpi_show .= '<td>' . $kpi[1] . '</td><td><span style="z-index: 10;">' . $kpi[0] . '</span></td>';
			}

			$kpi_show = '<table id="crb-kpi" class="" title="In the last 24 hours"><tr>' . $kpi_show . '</tr></table>';

			// TODO: add link "send daily report to my email"
			// $kpi_show .= '<p style="text-align: right; margin: 0;">' . __( 'in the last 24 hours', 'wp-cerber' ) . '</p>';
			return $kpi_show;

		},
		[ CRB_Activity::LOG_TABLE_STATUS ],
		[ 'no_heading' => true ] );

	CRB_Widgets::register( 'wgt_ac_breakdown',
		__( 'Activity Breakdown', 'wp-cerber' ),
		__( 'most frequent incidents and events in the last 24 hours', 'wp-cerber' ),
		'',
		function ( $ajax = false ) {

			return cerber_activity_summary();

			/* AJAX variant

			if ( $ajax ) {
				return cerber_activity_summary();
			}

			if ( cerber_activity_summary( true ) ) {
				return array( '', false, true );
			}

			return ''; */
		},
		array( CRB_Activity::LOG_TABLE_STATUS ) );

	CRB_Widgets::register( 'wgt_mali_top',
		__( 'Top Offending IP Addresses', 'wp-cerber' ),
		__( 'most active in the last 24 hours', 'wp-cerber' ),
		'',
		function ( $ajax = false ) {

			return crb_top_malicious_ip_report();
		},
		array( CRB_Activity::LOG_TABLE_STATUS ) );

	CRB_Widgets::register( 'wgt_new_users',
		__( 'New Users', 'wp-cerber' ),
		__( 'most recent user registrations', 'wp-cerber' ),
		crb_build_quick_nav_block( array( array( array( 'filter_activity' => array( 1, 2 ) ), __( 'View all', 'wp-cerber' ) ) ), 'activity' ),
		function () {

			$logged = cerber_db_get_var( 'SELECT ip FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (1,2) LIMIT 1' );

			if ( ! $logged ) {
				return array( CRB_Widgets::NO_DATA => __( 'No activity has been logged yet.', 'wp-cerber' ) );
			}

			return crb_render_activity_view( array(
				'filter_activity' => array( 1, 2 ),
				'per_page'        => 10,
				'compact_view'    => true,
				'no_export'       => true,
				'no_details'      => true,
				'date'            => 'ago',
				'hide_columns'    => array( 'crb_username_col' ),
				'context'         => 'dashboard',
			), false );

		},
		array( CRB_Activity::LOG_TABLE_STATUS, 60 ) );

	CRB_Widgets::register( 'wgt_login_issues',
		__( 'Login Issues', 'wp-cerber' ),
		__( 'most recent authentication issues and requests', 'wp-cerber' ),
		crb_build_quick_nav_block( array( array( array( 'filter_set' => '2' ), __( 'View all', 'wp-cerber' ) ) ), 'activity' ),
		function () {

			$in = crb_get_activity_set( 'login_issues', true );
			$logged = cerber_db_get_var( 'SELECT ip FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . $in . ') LIMIT 1' );

			if ( ! $logged ) {
				return array( CRB_Widgets::NO_DATA => __( 'No activity has been logged yet.', 'wp-cerber' ) );
			}

			return crb_render_activity_view( array(
				'filter_set'   => 2,
				'per_page'     => 10,
				'compact_view' => true,
				'no_export'    => true,
				'no_details'   => true,
				'date'         => 'ago',
				'hide_columns' => array( 'crb_user_col' ),
				'context'      => 'dashboard',
			), false );

		},
		array( CRB_Activity::LOG_TABLE_STATUS, 60 ) );

	CRB_Widgets::register(
		'wgt_user_activity',
		__( "Users' Activity", 'wp-cerber' ),
		__( 'most recent user events', 'wp-cerber' ),
		crb_admin_activity_nav_links( 'users' ),
		function () {

			$user_logged = warp_run_db( static function ( CRB_Database $db ): Revalt {
				return $db->table( CERBER_LOG_TABLE )
				          ->select( 'ip' )
				          ->where( 'user_id', '<>', 0 )
				          ->limit( 1 )
				          ->get_value();
			} );

			if ( $user_logged->has_errors() ) {
				return array(
					CRB_Widgets::NO_DATA => sprintf(
						__( 'An error occurred while retrieving data from the database table %s.', 'wp-cerber' ),
						'<code>' . CERBER_LOG_TABLE . '</code>'
					),
				);
			}

			if ( ! $user_logged->get_results() ) {
				return array( CRB_Widgets::NO_DATA => __( 'No user activity has been logged yet.', 'wp-cerber' ) );
			}

			return crb_render_activity_view( array(
				'filter_user'  => '*',
				'per_page'     => 10,
				'compact_view' => true,
				'no_export'    => true,
				'no_details'   => true,
				'date'         => 'ago',
				'hide_columns' => array( 'crb_username_col' ),
				'context'      => 'dashboard',
			), false );
		},
		array( CRB_Activity::LOG_TABLE_STATUS, 60 )
	);

	CRB_Widgets::register( 'wgt_mali_activity',
		__( 'Malicious Activity', 'wp-cerber' ),
		__( 'most recent violations of security rules', 'wp-cerber' ),
		crb_admin_activity_nav_links( 'suspicious' ),
		function () {

			$in = crb_get_activity_set( 'suspicious', true );
			$bad_logged = cerber_db_get_var( 'SELECT ip FROM ' . CERBER_LOG_TABLE . ' WHERE activity IN (' . $in . ') LIMIT 1' );

			if ( ! $bad_logged ) {
				return array( CRB_Widgets::NO_DATA => __( 'No activity has been logged yet.', 'wp-cerber' ) );
			}

			return crb_render_activity_view( array(
				'filter_set'   => 1,
				'per_page'     => 10,
				'compact_view' => true,
				'no_export'    => true,
				'no_details'   => true,
				'date'         => 'ago',
				'context'      => 'dashboard',
			), false );

		},
		array( CRB_Activity::LOG_TABLE_STATUS, 60 ) );

	CRB_Widgets::register( 'wgt_blocked_ips',
		__( 'Recently locked out IP addresses', 'wp-cerber' ),
		'',
		crb_build_quick_nav_block( array( array( array(), __( 'View all', 'wp-cerber' ) ) ), 'lockouts' ),
		function () {

			$locked = crb_get_locked_out_num();

			if ( ! $locked ) {
				return array( CRB_Widgets::NO_DATA => __( 'No lockouts at the moment. The sky is clear.', 'wp-cerber' ) );
			}

			return cerber_show_lockouts_page( array(
				'per_page'     => 10,
				'compact_view' => true
			), false );

		} );

	CRB_Widgets::register(
		'wgt_system_readiness',
		__( 'System Readiness', 'wp-cerber' ),
		__( 'key factors affecting security and stability', 'wp-cerber' ),
		'',
		function () {
			return crb_build_readiness_widget();
		},
		[],
		[ 'hideable' => false ]
	);

}

/**
 * Retrieve issues and generate the widget HTML.
 *
 * @return string|array HTML content string or an array with 'no_issues' message.
 *
 * @since 9.6.16
 */
function crb_build_readiness_widget() {
	$all_issues = CRB_Issue_Monitor::build_summary();

	if ( empty( $all_issues ) || ! is_array( $all_issues ) ) {
		return array( CRB_Widgets::NO_DATA => __( 'Your system configuration and environment appear to be in a ready state. No issues affecting security or stability were detected.', 'wp-cerber' ) );
	}

	$list_items = '';

	// Iterate through the list of issues
	foreach ( $all_issues as $issue_data ) {
		$message = $issue_data['message'] ?? '';

		if ( '' === $message ) {
			continue;
		}

		// 1. Build diagnostic details (optional)

		$diagnostic_output = '';
		$details = $issue_data['diagnostic_details'] ?? array();

		if ( ! empty( $details ) && is_array( $details ) ) {
			$sanitized_lines = array_map( function ( $line ) {
				$line = str_replace( array( '&#8230;', '…' ), '', $line );

				return crb_escape_html( crb_strip_tags( $line ) );
			}, $details );

			$diagnostic_content = implode( '<br>', $sanitized_lines );
			$diagnostic_output = '<div class="crb-issue-diagnostic-details">' . $diagnostic_content . '</div>';
		}

		// 2. Build date and controls blocks

		$controls_content = '';

		if ( isset( $issue_data['type'] )
		     && CRB_Issues::TYPE_EVENT === $issue_data['type'] ) {
			$timestamp = $issue_data['last_occurred_at'] ?? 0;
			if ( $timestamp ) {
				/* translators: %s is the date and time when the issue occurred last time. */
				$controls_content .= '<div class="crb-issue-date">' . sprintf( __( 'Last occurrence: %s', 'wp-cerber' ), cerber_auto_date( $timestamp ) ) . '</div>';
			}
		}

		if ( ! empty( $issue_data['dismissable'] ) && ! empty( $issue_data['id'] ) ) {
			$controls_content .= '<div class="crb-issue-dismiss">[ <a href="#" class="crb-dismiss-trigger" data-crb-context="delete_single_issue" data-crb-dismiss-id="' . crb_attr_escape( $issue_data['id'] ) . '">' . __( 'Dismiss', 'wp-cerber' ) . '</a> ]</div>';
		}

		$controls_output = '';
		if ( $controls_content ) {
			$controls_output = '<div class="crb-issue-controls">' . $controls_content . '</div>';
		}

		// 3. Build context links

		$setting_link = crb_get_setting_link( (string) $issue_data['setting_id'] ?? '' );

		$documentation_link = '';
		$doc_page_url = $issue_data['doc_page'] ?? '';

		if ( $doc_page_url ) {
			$documentation_link = ' [&nbsp;<a href="' . esc_url( $doc_page_url ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Documentation', 'wp-cerber' ) . '</a>&nbsp;]';
		}

		// 4. Build CSS classes

		$severity = $issue_data[ CRB_Issues::KEY_SEVERITY ] ?? '';
		$item_classes = 'crb-readiness-item crb-dismiss-scope';

		if ( $severity ) {
			$item_classes .= ' crb-severity-' . crb_attr_escape( $severity );
		}

		// 5. Build single item HTML

		$list_items .= '<div class="' . $item_classes . '"><div class="crb-issue-message">' . wp_kses( $message, CRB_Issues::ALLOWED_HTML ) . $setting_link . $documentation_link . '</div>' . $diagnostic_output . $controls_output . '</div>';
	}

	if ( '' === $list_items ) {
		return array( CRB_Widgets::NO_DATA => __( 'Your system configuration and environment appear to be in a ready state. No issues affecting security or stability were detected.', 'wp-cerber' ) );
	}

	return '<div class="crb-readiness-list">' . $list_items . '</div>';
}

/*
	Admin aside bar
*/
function cerber_show_aside( $tab ) {

	if ( in_array( $tab, array( 'nexus_sites', 'activity', 'lockouts', 'traffic' ) ) ) {
		return;
	}

	$aside = array();

	if ( $tab != 'scan_main' && ! lab_lab() && crb_was_activated( 1 * DAY_IN_SECONDS ) ) {
		/*$aside[] =
            '<a class="crb-button-one" style="background-color: #1DA1F2;" href="https://twitter.com/wpcerber" target="_blank"><span class="dashicons dashicons-twitter"></span> Follow Cerber on Twitter</a>';
		*/

		// <a class="crb-button-one" href="https://wpcerber.com/subscribe-newsletter/" target="_blank"><span class="dashicons dashicons-email-alt"></span> Subscribe to Cerber\'s newsletter</a>
		// <a class="crb-button-one" style="background-color: #3B5998;" href="https://www.facebook.com/wpcerber/" target="_blank"><span class="dashicons dashicons-facebook"></span> Follow Cerber on Facebook</a>
		//';


		$banners = array(
			array( 'bn4ra.png', 'https://wpcerber.com/pro/' ),
			array( 'bn5ra.png', 'https://wpcerber.com/pro/' ),
			array( 'talk_b1.png', 'https://talk.wpcerber.com' ),
		);

		if ( ! crb_admin_get_tab() ) {
			$n = 2;
		}
		else {
			$d = (int) date( 'z' );
			$n = ( $d & 1 ) ? 1 : 0;
		}

		$banner = $banners[ $n ];

		$banner_image = CRB_Globals::assets_url( $banner[0] );

		$aside[] = '<a href="' . $banner[1] . '" target="_blank"><img src="' . $banner_image . '" class="crb-full-width" /></a>';

	}

	$context_links = '';
	$docs = crb_get_doc_links();

	foreach ( $docs as $doc ) {
		$context_links .= '<p><a href="' . $doc[0] . '" target="_blank">' . $doc[1] . '</a></p>';
	}

	$aside[] = '<div class="crb-box" id="crb-blog"><div class="crb-box-inner"> 
			<h3>Documentation & How To</h3>           
            ' . $context_links . '</div></div>';

	if ( ! lab_lab()
	     && crb_was_activated( 2 * WEEK_IN_SECONDS ) ) {

		$r = array();
		$r[0] = crb_get_review_url( 'tpilot' );
		$r[1] = crb_get_review_url( 'cap' );
		$r[2] = crb_get_review_url( 'tradius' );

		shuffle( $r );

		$aside[] = '<a href="' . $r[0] . '" target="_blank"><img class="crb-full-width" src="' . CRB_Globals::assets_url( 'fb2b.png' ) . '" /></a>';
	}

	echo '<div id="crb-aside">' . implode( ' ', $aside ) . '</div>';
}

/**
 * Displaying admin messages (errors, notices, etc.) in the WP Dashboard
 *
 * @return void
 */
function cerber_show_admin_messages() {

	$msg_shown = false;

	if ( nexus_is_remote_context() ) {
		return;
	}

	if ( cerber_is_citadel() && cerber_user_can_manage() ) {
		$citadel_notice_element = crb_ui_element(
			'div',
			[
				'class' => 'update-nag',
				'style' => 'display: block; margin-top: 0; background-color: #fff; border-left: 6px solid #ff0000;',
			],
			[
				crb_ui_element(
					'p',
					[],
					[
						__( 'Logins are currently blocked. Citadel Mode was activated by WP Cerber in response to a traffic anomaly detected on your site. Only IP addresses on the Allowed IP Access List can log in.', 'wp-cerber' ) . ' ',
						crb_ui_link(
							_crb_admin_link_add( [ 'citadel_do' => 'deactivate' ] ),
							__( 'Deactivate', 'wp-cerber' )
						),
						' | ',
						crb_ui_link(
							cerber_admin_link( 'activity' ),
							__( 'View Activity', 'wp-cerber' )
						),
					]
				),
			]
		);

		echo crb_ui_renderer()->render_element( $citadel_notice_element );
	}

	// Check for proper context or admin page

	if ( ! ( nexus_is_valid_request()
	         || cerber_is_admin_page( null, 'plugins' ) ) ) {
		return;
	}

	$all_messages = array();

	if ( $notice = cerber_get_set( CRB_ADMIN_NOTICE ) ) {
		if ( is_array( $notice ) ) {
			$notice = '<p>' . implode( '</p><p>', $notice ) . '</p>';
		}
		$all_messages[] = array( $notice, 'crb-admin-warning' ); // red color
		cerber_update_set( CRB_ADMIN_NOTICE, array() );
	}

	if ( $message = cerber_get_set( CRB_ADMIN_MESSAGE ) ) {
		if ( is_array( $message ) ) {
			$message = '<p>' . implode( '</p><p>', $message ) . '</p>';
		}
		$all_messages[] = array( $message, 'updated' ); // green color
		cerber_update_set( CRB_ADMIN_MESSAGE, array() );
	}

	if ( $all_messages ) {

		foreach ( $all_messages as $item ) {

			$filtered = crb_purify_message( (string) $item[0] );

			$msg_element = crb_ui_element(
				'div',
				[
					'class' => $item[1] . ' notice is-dismissible crb-admin-message crb-dismiss-scope',
				],
				[
					crb_ui_raw_html( $filtered ),
					crb_ui_raw_html(
						'<button type="button" class="notice-dismiss crb-dismiss-trigger">' .
						'<span class="screen-reader-text">Dismiss this notice.</span>' .
						'</button>'
					),
				]
			);

			echo crb_ui_renderer()->render_element( $msg_element );
		}

		$msg_shown = true;
	}

	// Prominent announcement

	if ( $announcement = cerber_get_set( CRB_ADMIN_WIDE, null, false ) ) {

		$activation_result = crb_ui_spec_decode( (string) $announcement );

		// The announcement is shown once, so the stored document is cleared whether or not it could be displayed.

		cerber_update_set( CRB_ADMIN_WIDE, '' );

		if ( $activation_result->has_errors() ) {
			crb_admin_notice_failure( 'Unable to display the stored activation announcement.', $activation_result );
		}
		else {
			crb_show_admin_announcement( $activation_result->get_results(), false );
			$msg_shown = true;
		}
	}

	if ( $msg_shown || ! cerber_is_admin_page() ) {

		return;
	}

	// Big announcement

	if ( $news = get_site_option( CRB_ADMIN_ANNOUNCE ) ) {

		// The announcement is stored as a UI Spec document by cerber_push_the_news(), which runs during the plugin upgrade.

		$news_result = crb_ui_spec_decode( (string) $news );

		if ( $news_result->has_errors() ) {
			crb_admin_notice_failure( 'Unable to display the stored admin announcement.', $news_result );

			return;
		}

		crb_show_admin_announcement( $news_result->get_results(), true, 'new_release_announcement', __( 'Awesome!', 'wp-cerber' ) );

		return;
	}

	// Opt-in banner

	lab_show_opt_in_message();
}

/**
 * Sanitizes an admin message by allowing only specific HTML elements and attributes.
 *
 * This function uses wp_kses() to sanitize the provided message, ensuring
 * that only tags and attributes defined in the allow list are retained.
 *
 * Allowed tags reflect the actual markup used in:
 * - cerber_admin_message()
 * - cerber_admin_notice()
 *
 * @param string $string Admin message HTML to sanitize.
 *
 * @return string Sanitized HTML containing only allowed tags and attributes.
 */
function crb_purify_message( string $string = '' ): string {
	$allowed_tags = array(
		'h1'     => array(),
		'h2'     => array(
			'style' => true,
		),
		'p'      => array(
			'style' => true,
		),
		'br'     => array(),
		'div'    => array(
			'id'    => true,
			'style' => true,
		),
		'li'     => array(),
		'ul'     => array(),
		'ol'     => array(),
		'table'  => array(
			'style' => true,
		),
		'tr'     => array(),
		'td'     => array(
			'style' => true,
		),
		'b'      => array(),
		'strong' => array(),
		'i'      => array(
			'class' => true,
		),
		'span'   => array(
			'class' => true,
		),
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
	);

	return wp_kses( $string, $allowed_tags );
}

/**
 * Generates a link/button to display a dialog for creating an alert on the currently displaying Activity page
 *
 * @return string HTML code for using in the Dashboard HTML
 *
 * @since 8.9.6
 */
function crb_admin_alert_dialog( $button = true, $create_txt = null, $delete_txt = null ) {
	$alert_params = CRB_Activity_Alerts::get_alert_params();

	// Check if query parameters that are used in alerts are set and not empty.
	// All activities, without any filter are not allowed
	$empty = array_filter( array_values( $alert_params ) );

	if ( empty( $empty ) ) {
		return '';
	}

	$alerts = CRB_Activity_Alerts::get_alerts_list();

	// Limit to the number of subscriptions

	if ( $alerts && count( $alerts ) > 50 ) {
		return '';
	}

	$mode = ( CRB_Activity_Alerts::alert_exists( $alert_params ) ) ? 'delete_alert' : 'create_alert';

	if ( $mode == 'create_alert' ) {
		$label = $create_txt ?? __( 'Create Alert', 'wp-cerber' );
		$icon = ( $button ) ? 'crb-icon-bx-bell' : '';
	}
	else {
		$label = $delete_txt ?? __( 'Delete Alert', 'wp-cerber' );
		$icon = ( $button ) ? 'crb-icon-bx-bell-off' : '';
		$link = cerber_admin_link_add( array( 'cerber_admin_do' => 'manage_alert', 'mode' => 'delete_alert' ), true );

		if ( $button ) {
			return '<a class="button button-secondary cerber-button" href="' . $link . '" style="margin-right: 0.5em;"><i class="crb-icon ' . $icon . '"></i> ' . $label . '</a>';
		}

		return '<a href="' . $link . '">' . $label . '</a>';
	}

	// Create the alert dialog

	$em_list = crb_build_email_recipients();
	$mob_name = cerber_pb_get_active();

	if ( lab_lab() ) {

		$atts = array( 'type' => 'checkbox', 'data-validation_group' => 'required_min_one', 'checked' => true );

		$channels = array(
			'title'          => __( 'Channels to send alerts', 'wp-cerber' ),
			'visible'        =>
				array(
					'al_send_emails' => array(
						/* translators: Here %s is the email address(es). */
						'label' => sprintf( __( 'Send email alerts to %s', 'wp-cerber' ), implode( ', ', $em_list ) ),
						'value' => '1',
						'atts'  => $atts,
					),
					'al_send_me'     => array(
						'label' => sprintf( __( 'Send email alerts to my email', 'wp-cerber' ), $mob_name ),
						'value' => get_current_user_id(),
						'atts'  => $atts,
					),
				),
			'error_messages' => array( 'required_min_one' => __( 'Please select at least one channel', 'wp-cerber' ) )
		);

		if ( $mob_name ) {
			$channels['visible']['al_send_pushbullet'] = array(
				/* translators: Here %s is the name of a mobile device. */
				'label' => sprintf( __( 'Send mobile alerts to %s', 'wp-cerber' ), $mob_name ),
				'value' => '1',
				'atts'  => $atts,
			);
		}

	}
	else {

		if ( $mob_name ) {
			/* translators: Here %s is the name of a mobile device. */
			$mobile = sprintf( __( 'Mobile alerts will be sent to %s', 'wp-cerber' ), $mob_name );
		}
		else {
			$mobile = __( 'Mobile alerts are not configured', 'wp-cerber' );
		}

		$recipients = ( 1 < count( $em_list ) ) ? __( 'Email alerts will be sent to these emails:', 'wp-cerber' ) : __( 'Email alerts will be sent to this email:', 'wp-cerber' );
		$recipients .= ' ' . implode( ', ', $em_list );
		$recipients = '<p>' . $recipients . '</p><p>' . $mobile . '</p>';
		$recipients .= '<p><a href="https://wpcerber.com/wordpress-notifications-made-easy/" target="_blank">' . __( 'Documentation', 'wp-cerber' ) . '</a></p>';

		$channels = array(
			'html' => $recipients,
		);
	}


	// Extract values of the alert fields

	$alert_fields = array_intersect_key( $alert_params, $_GET );

	$limits = array(
		'title'   => __( 'Optional alert limits', 'wp-cerber' ),
		'hidden'  => array_merge( $alert_fields, array(
			'cerber_admin_do' => 'manage_alert',
			'mode'            => $mode
		) ),
		'visible' => array(
			'al_limit'       => array(
				'label' => __( 'Maximum number of alerts to send', 'wp-cerber' ),
				'value' => '',
				'atts'  => array(
					'type'        => 'text',
					'pattern'     => '\d+',
					'placeholder' => __( 'No limit', 'wp-cerber' ),
				),
			),
			'al_expires'     => array(
				'label' => __( 'Do not send alerts after this date', 'wp-cerber' ),
				'value' => '',
				'atts'  => array( 'type' => 'date', 'min' => date( 'Y-m-d' ) ),
			),
			'al_ignore_rate' => array(
				'label' => __( 'Ignore global rate limits', 'wp-cerber' ),
				'value' => '1',
				'atts'  => array( 'type' => 'checkbox' ),
			),
		),
	);

	return crb_create_popup_form( $limits, $channels, [ 'icon' => $icon, 'label' => $label ] );
}

/**
 * Deletes an alert using an alert hash from the $_GET parameter
 *
 */
function crb_delete_alert() {
	if ( ( $hash = cerber_get_get( 'unsubscribeme', '[a-z\d]+' ) )
	     && cerber_user_can_manage() ) {
		CRB_Activity_Alerts::delete_alert( $hash );

		crb_safe_redirect( remove_query_arg( 'unsubscribeme' ) );
		exit;
	}
}

/**
 * Generates pagination links
 *
 * @param int $total
 * @param int $per_page
 *
 * @return string
 */
function cerber_page_navi( $total, $per_page = 25 ) {

	$max_links = 10;
	$total = max( 0, intval( $total ) );
	$per_page = intval( $per_page );

	if ( $per_page <= 0 ) {
		$per_page = 25;
	}

	$page = crb_get_page_num();
	$base_url = crb_escape_url( remove_query_arg( 'pagen', add_query_arg( crb_get_query_params(), cerber_admin_link() ) ) );
	$last_page = ceil( $total / $per_page );

	if ( $last_page <= 1 ) {
		return '';
	}

	$links = array();
	$start = 1 + $max_links * intval( ( $page - 1 ) / $max_links );
	$end = $start + $max_links - 1;

	if ( $end > $last_page ) {
		$end = $last_page;
	}

	if ( $start > $max_links ) {
		$links[] = '<a href="' . $base_url . '&amp;pagen=' . ( $start - 1 ) . '" class="arrows"><b>&laquo;</b></a>';
	}

	for ( $i = $start; $i <= $end; $i ++ ) {
		if ( $page != $i ) {
			$links[] = '<a href="' . $base_url . '&amp;pagen=' . $i . '" >' . $i . '</a>';
		}
		else {
			$links[] = '<a class="active" style="font-size: 16px;">' . $i . '</a> ';
		}
	}

	if ( $end < $last_page ) {
		$links[] = '<a href="' . $base_url . '&amp;pagen=' . $i . '" class="arrows">&raquo;</a>';  // &#10141;
	}

	/* translators: %d is the number of entries in the list. */
	$total_entries = sprintf( _n( '%d entry', '%d entries', $total, 'wp-cerber' ), $total );

	return '<table class="cerber-margin" style="margin-top:1em; border-collapse: collapse;"><tr><td><div class="pagination">' . implode( ' ', $links ) . '</div></td><td><span style="margin-left:2em;"><b>' . $total_entries . '</b></span></td></tr></table>';
}

/**
 * Currently displaying page - for pagination purpose
 *
 * @return int
 */
function crb_get_page_num(): int {
	$q = crb_admin_parse_query( array( 'pagen' ) );

	return ( ! $q->pagen ) ? 1 : absint( $q->pagen );
}

/**
 * Return safe LIMIT clause
 *
 * @param integer $per_page
 *
 * @return string
 */
function crb_build_sql_limit( $per_page = 25 ) {
	$per_page = crb_absint( $per_page );

	return ' LIMIT ' . ( crb_get_page_num() - 1 ) * $per_page . ',' . $per_page;
}

/*
	Plugins admin page links
*/
add_filter( 'plugin_action_links_' . CERBER_PLUGIN_ID, 'cerber_action_links' );
add_filter( 'network_admin_plugin_action_links_' . CERBER_PLUGIN_ID, 'cerber_action_links' );
function cerber_action_links( $actions ) {
	$links = array();

	$links[] = '<a href="' . cerber_admin_link() . '">' . __( 'Dashboard', 'wp-cerber' ) . '</a>';

	if ( lab_lab() ) {
		$links[] = '<a href="https://my.wpcerber.com" target="_blank">' . __( 'Support', 'wp-cerber' ) . '</a>';
	}
	else {
		$links[] = '<a href="https://talk.wpcerber.com" target="_blank">' . __( 'Support Forum', 'wp-cerber' ) . '</a>';
	}

	return array_merge( $links, $actions );
}

add_filter( 'plugin_row_meta', function ( $plugin_meta, $plugin_file, $plugin_data, $status ) {

	if ( $plugin_file == CERBER_PLUGIN_ID
	     || $plugin_file == 'aaa-wp-cerber.php' ) {

		// Fix broken link pointing to a non-existing wordpress.org repository plugin profile

		foreach ( $plugin_meta as &$link ) {
			if ( strpos( $link, 'class="thickbox open-plugin-details-modal"' ) ) {
				$ver = esc_attr( str_replace( '.', '-', $plugin_data['Version'] ) );
				$link = preg_replace( '/^<a href="[^"]+"/u', '<a href="https://wpcerber.com/wp-cerber-security-' . $ver . '/?TB_iframe=true&width=600&height=550"', $link );
			}
		}

		// -----------------------------------------

		if ( lab_lab() ) {
			$plugin_meta[] = '<a href="https://my.wpcerber.com" target="_blank">' . __( 'Support', 'wp-cerber' ) . '</a>';
		}
		else {
			$plugin_meta[] = '<a href="https://talk.wpcerber.com" target="_blank">' . __( 'Support Forum', 'wp-cerber' ) . '</a>';
		}

		$plugin_meta[] = '<a href="https://wpcerber.com/toc/" target="_blank">' . __( 'Documentation', 'wp-cerber' ) . '</a>';
	}

	return $plugin_meta;
}, 10, 4 );

/*
function add_some_pointers() {
	?>
	<script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

		jQuery( function( $ ) {
			var options = {'content':'<h3>Info</h3><p>Cerber will request WHOIS database for extra information when you click on IP.</p>','position':{'edge':'right','align':'center'}};
			if ( ! options ) return;
			options = $.extend( options, {
				close: function() {
					//to do
				}
			});

			//$("#ip_extra").click(function(){
			//	$(this).pointer( options ).pointer('open');
			//});

			$('#subscribe-me').pointer( options ).pointer('open');

		});
	</script>
	<?php
}
add_action('admin_enqueue_scripts', 'cerber_admin_enqueue');
function cerber_admin_enqueue($hook) {
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
}
*/


add_action( 'admin_enqueue_scripts', 'cerber_admin_assets', 9999 );
function cerber_admin_assets() {

	$crb_assets_url = CRB_Globals::assets_url();

	if ( cerber_is_admin_page() ) {

		wp_register_style( 'crb_multi_css', $crb_assets_url . 'multi/multi.css', null, CERBER_VER );
		wp_enqueue_style( 'crb_multi_css' );
		wp_enqueue_script( 'crb_multi_js', $crb_assets_url . 'multi/multi.min.js', array(), CERBER_VER );

		wp_register_style( 'crb_tpicker_css', $crb_assets_url . 'tpicker/jquery.timepicker.min.css', null, CERBER_VER );
		wp_enqueue_style( 'crb_tpicker_css' );
		wp_enqueue_script( 'crb_tpicker', $crb_assets_url . 'tpicker/jquery.timepicker.min.js', array(), CERBER_VER );

		// Select2
		wp_register_style( 'select2css', $crb_assets_url . 'select2/dist/css/select2.min.css', null, null );
		wp_enqueue_style( 'select2css' );
		wp_enqueue_script( 'select2js', $crb_assets_url . 'select2/dist/js/select2.min.js', null, null, true );

		wp_register_style( 'crb_magnific_css', $crb_assets_url . 'magnific/magnific-popup.css', null, CERBER_VER );
		wp_enqueue_style( 'crb_magnific_css' );
		wp_enqueue_script( 'crb_magnific_js', $crb_assets_url . 'magnific/jquery.magnific-popup.min.js', null, CERBER_VER );

		wp_register_style( 'nexus_css', $crb_assets_url . 'nexus.css', null, CERBER_VER );
		wp_enqueue_style( 'nexus_css' );

		add_thickbox();

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-effects-bounce' );
		wp_enqueue_script( 'jquery-ui-sortable' );

	}

	if ( ! defined( 'CERBER_BETA' ) ) {

		wp_register_style( 'ui_stack_css', $crb_assets_url . 'ui-stack.css', null, CERBER_VER );
		wp_enqueue_style( 'ui_stack_css' );

		wp_enqueue_script( 'ui_stack_js', $crb_assets_url . 'ui-stack.js', array( 'jquery' ), CERBER_VER, true );
		wp_enqueue_script( 'cerber_js', $crb_assets_url . 'admin.js', array( 'jquery' ), CERBER_VER, true );

		if ( cerber_is_admin_page( array( 'page' => 'cerber-integrity' ) ) ) {
			wp_enqueue_script( 'cerber_scan', $crb_assets_url . 'scanner.js', array( 'jquery' ), CERBER_VER, true );
		}
	}

	wp_register_style( 'cerber_css_variables', $crb_assets_url . 'cerber-variables.css', null, CERBER_VER );
	wp_enqueue_style( 'cerber_css_variables' );

	wp_register_style( 'crb_icons_css', $crb_assets_url . 'icons/style.css', null, CERBER_VER );
	wp_enqueue_style( 'crb_icons_css' );

	wp_register_style( 'cerber_css', $crb_assets_url . 'admin.css', null, CERBER_VER );
	wp_enqueue_style( 'cerber_css' );

}

/*
 * JS & CSS for admin head
 *
 */
add_action( 'admin_head', 'cerber_admin_head' );
add_action( 'customize_controls_print_scripts', 'cerber_admin_head' ); // @since 5.8.1
function cerber_admin_head() {

	if ( cerber_is_admin_page() ) {
		remove_all_actions( 'admin_notices' ); // Remove aliens' notices on WP Cerber's page

		if ( crb_get_settings( 'top_admin_menu' ) ) {
			add_filter( 'admin_body_class', function ( $classes ) {
				return $classes . ' crb-top_admin_menu_enabled ';
			} );
		}
	}
	else {
		add_action( 'admin_notices', 'cerber_show_admin_messages', 999 );
		add_action( 'network_admin_notices', 'cerber_show_admin_messages', 999 );
	}

	?>

    <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

        var crb_admin_url = '<?php echo admin_url(); ?>';
        var crb_ajax_nonce = '<?php echo wp_create_nonce( 'crb-ajax-admin' ); ?>';
        var crb_ajax_loader = '<?php echo CRB_Globals::assets_url( 'ajax-loader.gif' ) ?>';
        var crb_lab_available = <?php echo lab_lab() ? 'true' : 'false'; ?>;

    </script>

    <style>
        #crb-admin-2fa-pins {
            border-left: solid 3px rgba(0, 121, 219, 0.63);
        }

        #crb-admin-2fa-pins table {
            border-collapse: collapse;
        }

        #crb-admin-2fa-pins table tr:first-child td {
            font-weight: bold;
        }

        #crb-admin-2fa-pins table td {
            padding: 6px 12px;
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>

	<?php

	global $pagenow;

	if ( 'users.php' === $pagenow ) :

		?>

        <style>

            /*.crb-user-blocked .crb-user-name,*/
            .crb-user-blocked td.column-username,
            .crb-user-prohibited td.column-username {
                color: red;
            }

            /* .crb-user-blocked .crb-user-name::after, */
            .crb-user-blocked td.column-username strong::after {
                content: ' <?php _e('BLOCKED','wp-cerber'); ?>';
            }

            .crb-user-prohibited td.column-username strong::after {
                content: ' <?php _e('PROHIBITED','wp-cerber'); ?>';
            }

        </style>

	<?php

	endif;

	if ( defined( 'CERBER_BETA' ) ) :
		?>
        <style id="wp-cerber-css-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
            <?php

             readfile( cerber_assets_dir().'/admin.css');
             readfile( cerber_assets_dir().'/ui-stack.css');

            ?>
        </style>
	<?php

	endif;

	if ( ! cerber_is_admin_page() ) {
		return;
	}

	?>

    <meta http-equiv="x-dns-prefetch-control" content="on"/>
    <link rel='dns-prefetch' href='//wpcerber.com'/>

    <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

		<?php

		echo 'var crb_admin_page = "' . crb_admin_get_page() . '";' . "\n";
		echo 'var crb_admin_tab = "' . crb_admin_get_tab() . '";' . "\n";
		echo 'var crb_user_locale = "' . substr( get_user_locale(), 0, 6 ) . '";' . "\n";

		?>

    </script>

    <style>

        /* Thickbox styles */
        #TB_title {
            background-color: var(--wp-admin-theme-color) !important;
            color: #fff;
        }

        .tb-close-icon {
            color: #fff !important;
        }

        #TB_window {
            font-family: 'Roboto', sans-serif;
        }

        /* Hide aliens' messages on WP Cerber's admin pages */
        .update-nag,
        #update-nag,
        #setting-error-tgmpa,
        .pms-cross-promo,
        .vc_license-activation-notice,
        #wordfenceConfigWarning,
        #crb-admin div#message.updated {
            display: none;
        }

    </style>
	<?php
}

/*
 * JS & CSS for admin footer
 *
 */
add_action( 'admin_footer', 'cerber_admin_footer' );
function cerber_admin_footer() {

	//add_some_pointers();

	crb_load_wp_js();

	// Add buttons to the user profile page

	$uid = 0;
	if ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE ) {
		$uid = get_current_user_id();
	}
    elseif ( ! empty( $_GET['user_id'] ) && is_admin_user_edit() ) {
		$uid = absint( $_GET['user_id'] );
	}

	if ( $uid && cerber_user_can_manage() ) {

		if ( $user = crb_get_userdata( $uid ) ) {
			if ( crb_is_user_blocked( $uid ) ) {
				?>
                <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
                    document.querySelector('#profile-page hr.wp-header-end').insertAdjacentHTML('beforebegin', '<span class="page-title-action" style="border: none; border-radius: 0; color: white; background-color: #e4383d; cursor: auto;"><?php echo __( 'User is blocked', 'wp-cerber' ); ?></span>');
                </script>
				<?php
			}
			if ( crb_is_username_prohibited( $user->user_login ) ) {
				?>
                <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
                    document.querySelector('#profile-page hr.wp-header-end').insertAdjacentHTML('beforebegin', '<span class="page-title-action" style="border: none; border-radius: 0; color: white; background-color: #e4383d; cursor: auto;"><?php echo __( 'Username is prohibited', 'wp-cerber' ); ?></span>');
                </script>
				<?php
			}
		}

		// Link to the user's activity log

		$user_links = '<a href="' . cerber_admin_link( 'activity', array( 'filter_user' => $uid ) ) . '" class="page-title-action crb-title-button">' . __( 'View Activity', 'wp-cerber' ) . '</a>';

		// Link to the user's sessions, if any

		if ( $uss = crb_sessions_get_num( $uid ) ) {
			$user_links .= '<a href="' . cerber_admin_link( 'sessions', array( 'filter_user' => $uid ) ) . '" class="page-title-action crb-title-button">' . __( 'Sessions', 'wp-cerber' ) . ' ' . $uss . '</a>';
		}

		?>

        <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
            document.querySelector('#profile-page h1.wp-heading-inline').insertAdjacentHTML('afterend', '<?php echo $user_links; ?>');
        </script>

		<?php
	}

	if ( cerber_user_can_manage() ) {
		// Link to the Sessions page - all users
		?>
        <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
            let html_target = document.querySelector('body.users-php #wpbody-content h1.wp-heading-inline')
            if (html_target) {
                html_target.insertAdjacentHTML('afterend', '<?php echo '<a href="' . cerber_admin_link( 'sessions' ) . '" class="page-title-action crb-title-button">' . __( 'Sessions', 'wp-cerber' ) . '</a>'; ?>');
            }
        </script>
		<?php
	}

	// Check for blocked users

	$blocked = array();
	$prohibited = array();

	if ( $uids = crb_users_on_the_page() ) {
		foreach ( $uids as $uid => $login ) {
			if ( crb_is_user_blocked( $uid ) ) {
				$blocked[] = $uid;
			}
            elseif ( crb_is_username_prohibited( $login ) ) {
				$prohibited[] = $uid;
			}
		}
	}

	?>

    <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

        // Highlight blocked users

        let crb_blocked_users = <?php echo '[' . implode( ',', $blocked ) . ']'; ?>;
        let crb_prohibited_users = <?php echo '[' . implode( ',', $prohibited ) . ']'; ?>;

        for (let uid of crb_blocked_users) {
            document.getElementById('user-' + uid)?.classList.add("crb-user-blocked");
        }

        for (let uid of crb_prohibited_users) {
            document.getElementById('user-' + uid)?.classList.add("crb-user-prohibited");
        }

        // Switching inputs on the profile page

        jQuery(function ($) {

            // Old (in use)

            let mrow = $('#profile-page .crb_blocked_txt');

            $("#crb_user_blocked").on('change', function () {
                if ($(this).is(':checked')) {
                    mrow.show();
                }
                else {
                    mrow.hide();
                }
            });

            // Conditional form fields (new)

            let crb_user_edit_fields = $('#crb-wp-user-edit');

            crb_user_edit_fields.find('input,select').on('change', function () {
                let enabler_id = $(this).attr('id');
                let enabler_val;

                if ('checkbox' === $(this).attr('type')) {
                    enabler_val = !!$(this).is(':checked');
                }
                else {
                    enabler_val = $(this).val();
                }

                crb_user_edit_fields.find('[data-input_parent="' + enabler_id + '"]').each(function () {
                    let input_data = $(this).data();
                    let method = 'hide';

                    if (typeof input_data['input_parent_value'] !== "undefined") {
                        let target = input_data['input_parent_value'];
                        if (Array.isArray(target)) {
                            for (let i = 0; i < target.length; i++) {
                                if (String(enabler_val) === String(target[i])) {
                                    method = 'show';
                                    break;
                                }
                            }
                        }
                        else {
                            if (String(enabler_val) === String(input_data['input_parent_value'])) {
                                method = 'show';
                            }
                        }
                    }
                    else {
                        if (enabler_val) {
                            method = 'show';
                        }
                    }

                    let input_wrapper = $(this).closest('tr');

                    if (method === 'show') {
                        input_wrapper.fadeIn(500);
                    }
                    else if (method === 'hide') {
                        input_wrapper.fadeOut();
                    }

                });
            });

        });

    </script>
	<?php

	/* Background tasks */

	$list = CRB_Deferred_Tasks::get_all();

	if ( $list ) {
		$list = array_slice( $list, 0, 20 );
		$list = array_keys( $list );
		?>

        <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

            let crb_bg_tasks = <?php echo '["' . implode( '","', $list ) . '"]'; ?>;

            jQuery(function ($) {

                if (crb_bg_tasks.length) {
                    console.log('Background tasks: ' + crb_bg_tasks.length);
                    $.post(ajaxurl, {
                            ajax_nonce: crb_ajax_nonce,
                            action: 'cerber_local_ajax',
                            crb_ajax_do: 'bg_tasks_run',
                            run_tasks: $(crb_bg_tasks).toArray(),
                        }
                    );
                }
            });
        </script>
		<?php
	}


	// Inter-Page Loader

	?>

    <div id="ui_page_overlay" style="display: none;"></div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const containers = [
                document.getElementById('crb-admin'),
                document.getElementById('toplevel_page_cerber-security'),
                document.getElementById('cerber_quick'),

                document.querySelector('body.users-php #wpbody-content'),
                document.querySelector('#wpbody-content #profile-page'),
                document.querySelector('#adminmenu #menu-users'),
            ];

            containers.forEach(container => {
                if (container) {
                    const links = container.querySelectorAll('a');
                    links.forEach(link => {
                        link.classList.add('cerber-page-loader-candidate');
                    });
                }
            });

            // Helper function to parse query parameters
            const getQueryParams = (url) => {
                const queryString = url.split('?')[1] || '';
                return Object.fromEntries(new URLSearchParams(queryString));
            };

            const excludedSchemes = ['mailto:', 'tel:', 'sms:', 'ftp:', 'geo:', 'javascript:'];
            const overlay = document.getElementById('ui_page_overlay');

            document.addEventListener('click', function (event) {
                let link;

                if (event.ctrlKey || event.metaKey) {
                    return;
                }

                if (event.target.tagName === 'A') {
                    link = event.target;
                }
                else {
                    link = event.target.closest('a');
                }

                if (!link || !link.classList.contains('cerber-page-loader-candidate')) {
                    return;
                }

                const href = link.getAttribute('href') || '';
                const hasOnclick = link.hasAttribute('onclick');
                const target = link.getAttribute('target') || '';

                // Check for exceptions

                if (
                    !href ||
                    hasOnclick ||
                    href.startsWith('#') ||
                    target ||
                    excludedSchemes.some(scheme => href.startsWith(scheme))
                ) {
                    return;
                }

                // The link looks valid, let's check it for specific locations (URLs) we will activate the overlay loader for

                const params = getQueryParams(href);

                // Conditions for overlay activation
                const validPages = ['cerber-security', 'cerber-traffic'];
                const validTabs = ['dashboard', 'activity', 'sessions', 'traffic'];

                if (!link.classList.contains('crb-page-loader-force')) {
                    if (params.cerber_nonce
                        || !params.page
                        || !validPages.includes(params.page)
                        || (params.tab && !validTabs.includes(params.tab))) {
                        return;
                    }
                }

                event.preventDefault();
                overlay.style.display = 'flex';

                // Trigger delayed navigation with delay to avoid "flashing effect"
                setTimeout(() => {
                    window.location.href = href;
                }, 200);

            });

        });

    </script>


	<?php

	// ------------------------------------------------------

	if ( ! cerber_is_admin_page() ) {
		return;
	}

	if ( defined( 'CERBER_BETA' ) ) :
		?>
        <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

			<?php
			readfile( cerber_assets_dir() . '/admin.js' );
			readfile( cerber_assets_dir() . '/ui-stack.js' );

			if ( cerber_is_admin_page( array( 'page' => 'cerber-integrity' ) ) ) {
				readfile( cerber_assets_dir() . '/scanner.js' );
			}
			?>

        </script>
	<?php
	endif;

	// Time pickers
	$format = 'H:i';
	if ( cerber_is_ampm() ) {
		$format = 'h:i A';
	}
	?>
    <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
        jQuery(function ($) {
            $('.crb-tpicker').timepicker({
                'step': 20,
                'forceRoundTime': true,
                'timeFormat': '<?php echo $format; ?>'
            });
        });
    </script>
	<?php

	if ( ! lab_lab()
	     && cerber_is_admin_page( array( 'tab' => array( 'scan_schedule', 'scan_policy' ) ) ) ) :
		?>
        <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
            jQuery(function ($) {
                $('input').attr('disabled', 'disabled');
                $('select').attr('disabled', 'disabled');
                $('.crb-slider').css('background-color', '#888');
                $('th').add('td').add('h2').css('color', '#888');

                $('#crb-main h2').first().before('<?php echo str_replace( array(
					"\n",
					"\r"
				), '', crb_admin_cool_features() ); ?>');
            });
        </script>
	<?php
	endif;

	?>

    <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">

		<?php

		if ( cerber_is_admin_page( array( 'page' => 'cerber-integrity' ) ) ) {

			echo crb_generate_safe_js_var( 'crb_scan_top_issues', crb_get_top_issue_ids() );
			echo crb_generate_safe_js_var( 'crb_scan_msg_steps', cerber_get_stage_titles_all() );
			echo crb_generate_safe_js_var( 'crb_scan_msg_issues', cerber_get_issue_title() );
			echo crb_generate_safe_js_var( 'crb_scan_msg_labels', cerber_get_issue_labels() );
			echo crb_generate_safe_js_var( 'crb_scan_msg_risks', cerber_get_risk_labels() );
			echo crb_generate_safe_js_var( 'crb_scan_msg_misc', crb_scan_messages() );
			echo crb_generate_safe_js_var( 'crb_txt_strings', cerber_get_strings() );
		}

		echo crb_generate_safe_js_var( 'crb_admin_messages', array( 'are_you_sure' => __( 'Are you sure?', 'wp-cerber' ) ) );


		// Copy to clipboard code for the Diagnostic page

		?>

        document.addEventListener('click', function (event) {

            if (event.target.matches('.crb-copy-to-clipboard')) {
                event.preventDefault();
                //event.stopImmediatePropagation();

                const copy_control = event.target;

                const targetClass = copy_control.dataset?.copy_clipboard_class;
                if (!targetClass) {
                    console.error("Required attribute 'data-copy_clipboard_class' not found.");
                    return;
                }

                const elements = document.querySelectorAll(`.${targetClass}`);
                if (!elements.length) {
                    console.error("No elements to copy. Check the class of the elements.");
                    return;
                }

                const isPlainText = copy_control.dataset?.plain_text === '1';

                const textToCopy = Array.from(elements)
                    .map(el => {
                        if (isPlainText) {
                            return el.textContent.trim();
                        }
                        else {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = el.innerHTML.replace(/<br\s*\/?>/gi, '\n');
                            return tempDiv.textContent.trim();
                        }
                    })
                    .join('\n');

                if (!textToCopy) {
                    console.warn("Nothing to copy.");
                    return;
                }

                navigator.clipboard.writeText(textToCopy)
                    .then(() => {

                        const originalText = copy_control.textContent;
                        copy_control.textContent = 'Copied!';

                        setTimeout(() => {
                            copy_control.textContent = originalText;
                        }, 2000);
                    })
                    .catch(err => console.error('Unable to copy to clipboard:', err));
            }
        });

    </script>

	<?php

	echo CRB_Globals::$admin_footer_html;
}

function crb_load_wp_js() {
	echo '<script src="' . CRB_Globals::assets_url( 'wp-admin.js' ) . '"></script>';
}

add_action( 'admin_enqueue_scripts', 'cerber_dequeue_unwanted_scripts', PHP_INT_MAX );

/**
 * Prevents loading of enemy JS files on WP Cerber admin pages
 *
 * @return void
 *
 * @since 9.6.1.5
 */
function cerber_dequeue_unwanted_scripts() {
	global $wp_scripts;

	if ( ! cerber_is_admin_page() ) {
		return;
	}

	$current_queue = $wp_scripts->queue;

	foreach ( $current_queue as $script_handle ) {

		if ( ( $script = $wp_scripts->registered[ $script_handle ] ?? false )
		     && isset( $script->src )
		     && crb_is_enemy_plugin_script( $script->src ) ) {

			wp_dequeue_script( $script_handle );
		}
	}
}

/**
 * Checks for enemy JS scripts
 *
 * @param string $source The source of a JS script
 *
 * @return bool True if the source may not be included as an asset
 *
 * @since 9.6.1.5
 */
function crb_is_enemy_plugin_script( $source ) {
	static $dir_url, $dir, $allowed = array( 'wp-cerber', 'query-monitor' );

	if ( ! $source ) {
		return false;
	}

	if ( ! $dir_url ) {
		$dir_url = plugin_dir_url( '' );
	}

	if ( ! $dir ) {
		$dir = cerber_get_plugins_dir() . DIRECTORY_SEPARATOR;
	}

	// Type one: a URL to a file

	if ( 0 === strpos( $source, 'https://' )
	     || 0 === strpos( $source, 'http://' ) ) {

		if ( 0 === strpos( $source, $dir_url ) ) {

			foreach ( $allowed as $slug ) {
				if ( 0 === strpos( $source, $dir_url . $slug . '/' ) ) {

					return false;
				}
			}

			return true;
		}

		return false;
	}

	// Type 2: an absolute path to a file

	if ( 0 === strpos( $source, $dir ) ) {
		foreach ( $allowed as $slug ) {
			if ( 0 === strpos( $source, $dir . $slug . DIRECTORY_SEPARATOR ) ) {
				return false;
			}
		}

		return true;
	}

	// Type 3: a relative path to a file

	$source = ABSPATH . ltrim( $source, '/\\' );

	if ( 0 === strpos( $source, $dir ) ) {
		foreach ( $allowed as $slug ) {
			if ( 0 === strpos( $source, $dir . $slug . DIRECTORY_SEPARATOR ) ) {

				return false;
			}
		}

		return true;
	}

	return false;
}


add_filter( 'admin_footer_text', function ( $text ) {
	if ( ! cerber_is_admin_page() ) {
		return $text;
	}

	return 'If you like <strong>WP Cerber</strong>, <a target="_blank" href="' . crb_get_review_url( 'tradius' ) . '">please give it a &#9733;&#9733;&#9733;&#9733;&#9733; review</a>. Thanks!';
}, PHP_INT_MAX );

add_filter( 'update_footer', function ( $text ) {
	if ( ! cerber_is_admin_page() ) {
		return $text;
	}

	$pr = '';
	$pro = false;
	$mode = '';

	if ( $remote = nexus_get_context() ) {
		$ver = $remote->plugin_v;
		if ( ( $data = nexus_get_remote_data() )
		     && $data['extra']['versions'][6] ?? '' ) {
			$pro = true;
		}
	}
	else {

		$ver = CERBER_VER;
		$pro = lab_lab();
		$mode = ( 1 == cerber_get_mode() ) ? 'in the Standard mode' : 'in the Legacy mode';

	}

	if ( $pro ) {
		$pr = 'PRO';
		$support = '| <a target="_blank" href="https://my.wpcerber.com">Get Support</a>';
	}
	else {
		$support = '| <a target="_blank" href="https://talk.wpcerber.com">Support Forum</a>';
	}

	return 'WP Cerber Security ' . $pr . ' ' . $ver . '. ' . $mode . ' ' . $support;

}, PHP_INT_MAX );

/*
 * Add per screen user settings
 */
function crb_admin_screen_options() {
	if ( ! $id = crb_get_configurable_screen() ) {
		return;
	}

	add_filter( 'screen_settings', function ( $form, $screen ) {
		// Fixing an invalid WP redirection implemented using wp_get_raw_referer().
		// Instead of using the "Referer" header sent by a user browser, we use the proper value we need.
		// A browser may strip $_GET parameters from the header.
		$form .= '<input id="crb-fix-referer" type="hidden" name="_wp_http_referer" value="' . crb_attr_escape( $_SERVER['REQUEST_URI'] ) . '" />';

		return $form;
	}, 0, 2 );

	add_screen_option( 'per_page', array(
		//'label' => __( 'Number of items per page:' ),
		'default' => 25,
		'option'  => 'cerber_screen_' . $id . '_page', // '_page' is mandatory since WP 5.4.2
	) );
}

/*
 * Enables saving screen options to the user's meta
 */
add_filter( 'set-screen-option', function ( $status, $option, $value ) {
	if ( $id = crb_get_configurable_screen() ) {
		if ( 'cerber_screen_' . $id . '_page' == $option ) {
			return $value;
		}
	}

	return $status;
}, 10, 3 );

/*
 * Returns false if the admin page has no screen options to configure
 *
 * @return false|string
 */
function crb_get_configurable_screen() {
	if ( ! $id = crb_admin_get_tab() ) {
		$id = crb_admin_get_page();
	}
	if ( $id == 'cerber-traffic' ) {
		$id = 'traffic';
	}
	if ( ( $id == 'cerber-nexus' ) && nexus_is_main() ) {
		return 'nexus_sites';
	}
	$ids = array( 'sessions', 'lockouts', 'activity', 'traffic', 'scan_quarantine', 'scan_ignore', 'nexus_sites' );
	if ( ! in_array( $id, $ids ) ) {
		return false;
	}

	return $id;
}

/*
 * Retrieve settings for the current screen
 *
 */
function crb_admin_get_per_page() {
	if ( is_multisite() ) {
		return 50;  // temporary workaround
	}

	if ( nexus_is_valid_request() ) {
		$per_page = nexus_request_data()->screen['per_page'];
	}
    elseif ( function_exists( 'get_current_screen' ) ) {
		set_current_screen();
		$screen = get_current_screen();

		if ( $screen_option = $screen->get_option( 'per_page', 'option' ) ) {
			$per_page = absint( get_user_meta( get_current_user_id(), $screen_option, true ) );
			if ( empty ( $per_page ) ) {
				$per_page = absint( $screen->get_option( 'per_page', 'default' ) );
			}
		}
	}

	if ( empty ( $per_page ) ) {
		$per_page = 25;
	}

	return absint( $per_page );
}

/**
 * Returns the vertical tab ID a tab key is rendered with.
 *
 * The tab ID appears in the data-tab-id attribute of the tab link and in the ID of the tab
 * content element, and the admin JavaScript matches a link fragment against it. A hyphen is
 * replaced with an underscore, so a tab key and its rendered tab ID are not interchangeable.
 * Anything addressing a rendered tab must obtain the ID here rather than assume the tab key.
 *
 * @param string $tab_key Tab key used in the tab configuration of crb_admin_render_vtabs().
 *
 * @return string Tab ID as rendered in the vertical tab markup.
 *
 * @since 9.7
 */
function crb_admin_get_vtab_id( string $tab_key ): string {

	return str_replace( '-', '_', $tab_key );
}

/**
 * Renders HTML code for a vertical tabbed UI layout within a form.
 *
 * @param array<string, array{title: string, content: string, desc?: string, callback?: string}> $tabs_config Tab configurations.
 * @param string $submit Submit button text. Defaults to 'Save Changes' if empty.
 * @param array<string, string|int> $hidden Hidden form fields as key-value pairs.
 * @param string $form_id ID attribute of the form element.
 *
 * @return void
 */
function crb_admin_render_vtabs( $tabs_config, $submit = '', $hidden = array(), $form_id = 'cerber_tabs' ) {

	$tablinks = '';
	$tabs = '';

	foreach ( $tabs_config as $tab_id => $tab ) {
		$tab_id = crb_admin_get_vtab_id( (string) $tab_id );
		$tablinks .= '<div class="tablinks" data-tab-id="' . $tab_id . '" data-callback="' . crb_array_get( $tab, 'callback', '' ) . '">' . $tab['title'] . '<br/><span>' . crb_array_get( $tab, 'desc', '' ) . '</span></div>';
		$tabs .= '<div id="tab-' . $tab_id . '" class="vtabcontent">' . $tab['content'] . '</div>';
	}

	echo '<form id="' . $form_id . '" method="post" action="" class="crb-settings-form">';

	if ( ! $submit ) {
		$submit = __( 'Save Changes', 'wp-cerber' );
	}

	echo '<table class="vtable" id="crb_vtabs_container" style="width: 100%; border-collapse: collapse;"><tr><td id="crb-vtabs"><div class="vtabs">' . $tablinks . '</div></td><td id="crb-vtabcontent">' . $tabs . '
    </td></tr><tr><td></td><td><div style="padding-left: 3em;">' . crb_admin_submit_button( $submit ) . '</div></td></tr></table>';

	cerber_nonce_field( 'control', true );

	if ( $hidden ) {
		foreach ( $hidden as $name => $val ) {
			echo '<input type="hidden" name="' . $name . '" value="' . $val . '">';
		}
	}

	echo '</form>';
}

function crb_admin_show_geo_rules() {

	$rules = cerber_geo_rule_set();

	$tabs_config = array();

	foreach ( $rules as $rule_id => $rule ) {

		list( $desc, $content ) = crb_admin_geo_selector( $rule_id, $rule );

		if ( isset( $rule['multi_set'] ) ) {
			$names = array( '---first' => $rule['multi_top'] . ' — ' . $desc );
			foreach ( $rule['multi_set'] as $item_id => $item_title ) {
				$id = $rule_id . '_' . $item_id;
				list( $d, $c ) = crb_admin_geo_selector( $id, $rule, 'crb-display-none' );
				$content .= $c;
				if ( cerber_get_geo_rules( $id ) ) {
					$desc = __( 'Role-based rules are configured', 'wp-cerber' );
					$names[ $item_id ] = $item_title . ' — ' . $d;
				}
				else {
					$names[ $item_id ] = $item_title;
				}
			}
			$content = '<p>' . cerber_select( 'sw_' . $rule_id, $names, null, 'crb-geo-switcher', null, null, null, array( 'rule-id' => $rule_id ) ) . '</p>' . $content;
		}

		$tabs_config[ $rule_id ] = array(
			'title'    => $rule['name'],
			'desc'     => $desc,
			'content'  => '<div style="padding-top: 22px;">' . $content . '</div>',
			'callback' => 'geo_rules_activator',
		);
	}

	?>

    <script id="wp-cerber-js-<?php echo crb_sanitize_id( __FUNCTION__ . '_' . __LINE__ ); ?>">
        window.geo_rules_activator = function (rule_id) {

            if (typeof search_fields === 'undefined') {
                var search_fields = jQuery('form#crb-geo-rules .multi-wrapper input:text');
            }

            search_fields.each(function () {
                if (jQuery(this).val()) {
                    jQuery(this).val(''); // Reset "Search" field

                    if (typeof wrapper === 'undefined') {
                        var wrapper = jQuery('#crb-geo-rules .multi-wrapper');
                    }

                    if (typeof select === 'undefined') {
                        var select = jQuery('#crb-geo-rules .crb-mega-select');
                    }

                    wrapper.remove();
                    select.removeAttr('data-multijs');
                    //document.querySelector('#crb-geo-rules .crb-mega-select').removeAttribute('data-multijs');
                    //document.querySelectorAll('#crb-geo-rules .crb-mega-select').removeAttribute('data-multijs');
                }
            });

            if (typeof geo_selectors === 'undefined') {
                var geo_selectors = jQuery('form#crb-geo-rules select.crb-mega-select');
            }

            geo_selectors.multi({'search_placeholder': '<?php echo crb_escape_string_for_js_in_html( __( 'Start typing here to find a country', 'wp-cerber' ) ); ?>'});

        };
    </script>

	<?php

	crb_admin_render_vtabs( $tabs_config, __( 'Save all rules', 'wp-cerber' ), array( 'cerber_admin_do' => 'update_geo_rules' ), 'crb-geo-rules' );

}

function crb_admin_geo_selector( $rule_id, $rule, $class = '' ) {

	$geo_config = cerber_get_geo_rules( $rule_id );
	$selector = crb_geo_country_selector( $geo_config, $rule_id, $rule );
	$opt = crb_get_settings();

	if ( ! empty( $geo_config['list'] ) ) {
		$num = count( $geo_config['list'] );
		$the_country = reset( $geo_config['list'] );

		if ( $geo_config['type'] == 'W' ) {
			if ( 1 === $num ) {
				/* translators: %s is the name of a country. */
				$info = sprintf( __( 'Permitted for one country (%s)', 'wp-cerber' ), $the_country );
			}
			else {
				/* translators: %d is the number of countries. */
				$info = sprintf( _n( 'Permitted for %d country', 'Permitted for %d countries', $num, 'wp-cerber' ), $num );
			}
		}
		else {
			if ( 1 === $num ) {
				/* translators: %s is the name of a country. */
				$info = sprintf( __( 'Not permitted for one country (%s)', 'wp-cerber' ), $the_country );
			}
			else {
				/* translators: %d is the number of countries. */
				$info = sprintf( _n( 'Not permitted for %d country', 'Not permitted for %d countries', $num, 'wp-cerber' ), $num );
			}
		}
	}
	else {
		$info = __( 'Any country is permitted', 'wp-cerber' );
	}

	$note = '';

	switch ( $rule_id ) {
		case 'geo_register':
			if ( ! get_option( 'users_can_register' ) ) {
				$note = __( 'User registration is disabled in the General WordPress Settings.', 'wp-cerber' );
			}
			break;
		case 'geo_restapi':
			if ( $opt['norest'] ) {
				$note = __( 'Access to REST API is restricted in the WP Cerber settings.', 'wp-cerber' );
			}
			break;
		case 'geo_xmlrpc':
			if ( $opt['xmlrpc'] ) {
				$note = __( 'XML-RPC is disabled in the Hardening settings of WP Cerber.', 'wp-cerber' );
			}
			break;
	}

	if ( $note ) {
		$note = '<p><span class="dashicons-before dashicons-warning"></span> ' . __( 'Heads up!', 'wp-cerber' ) . ' ' . $note . '</p>';
	}

	return array(
		$info,
		$note . '<div class="crb-geo-wrapper ' . $class . '" id="crb-geo-wrap_' . $rule_id . '">' . $selector . '</div>'
	);

}

/**
 * Generates GEO rule selector
 *
 * @param array $config Rule configuration
 * @param string $rule_id
 * @param array $rule
 *
 * @return string   HTML code of form
 */
function crb_geo_country_selector( $config = array(), $rule_id = '', $rule = array() ) {

	$ret = '<div class="crb-super-select"><select id="countries-' . $rule_id . '" name="crb-' . $rule_id . '-list[]" class="crb-mega-select" style="display: none;" multiple="multiple">';

	if ( ! empty( $config['list'] ) ) {
		$selected = $config['list'];
	}
	else {
		$selected = null;
	}

	foreach ( crb_get_country_list() as $code => $country ) {
		if ( $selected && in_array( $code, $selected ) ) {
			$sel = 'selected';
		}
		else {
			$sel = '';
		}
		$ret .= '<option ' . $sel . ' value="' . $code . '">' . $country . '</option>';
	}

	if ( ! empty( $config['type'] ) && $config['type'] == 'B' ) {
		$w = '';
		$b = 'checked="checked"';
	}
	else {
		$w = 'checked="checked"';
		$b = '';
	}

	if ( ! empty( $rule['desc'] ) ) {
		$desc = $rule['desc'];
	}
	else {
		$desc = '<span style="text-transform: lowercase;">' . $rule['name'] . '</span>';
	}


	$ret .= '
        </select>
        
        <div class="crb-super-details">
        <p><span style="opacity: 0.7;">' . __( 'Click on a country name to add it to the list of selected countries', 'wp-cerber' ) . '</span></p>
        
        <p style="margin-top: 2em;">
        <input type="radio" value="W" name="crb-' . $rule_id . '-type" id="geo-type-' . $rule_id . '-W" ' . $w . '>'
	        . '<label for="geo-type-' . $rule_id . '-W">'
	        /* translators: 'selected countries' refers to a list of countries. 'to' is the marker of an infinitive verb, e.g., "to use it". Placeholder %s will be replaced by an action/operation, such as "Submit forms". */
	        . sprintf( _x( 'Only selected countries are permitted to %s. All others are not.', 'to is a marker of infinitive, e.g. "to use it"', 'wp-cerber' ), $desc )
	        . '</label>
        <p>
        <input type="radio" value="B" name="crb-' . $rule_id . '-type" id="geo-type-' . $rule_id . '-B" ' . $b . '>
        <label for="geo-type-' . $rule_id . '-B">'
	        /* translators: 'selected countries' refers to a list of countries. 'to' is the marker of an infinitive verb, e.g., "to use it". Placeholder %s will be replaced by an action/operation, such as "Submit forms". */
	        . sprintf( _x( 'Selected countries are not permitted to %s. All others are allowed to.', 'to is a marker of infinitive, e.g. "to use it"', 'wp-cerber' ), $desc )
	        . '</label>
        </p>
        </div>
        
        </div>';

	return $ret;

}

/**
 * A list of available GEO rules
 *
 * @return array
 */
function cerber_geo_rule_set() {
	$set = wp_roles()->role_names;

	$rules = array(
		'geo_login'    => array(
			'name'      => __( 'Log into the website', 'wp-cerber' ),
			'multi_set' => $set,
			'multi_top' => __( 'All Users', 'wp-cerber' )
		),
		'geo_register' => array( 'name' => __( 'Register on the website', 'wp-cerber' ) ),
		'geo_submit'   => array( 'name' => __( 'Submit forms', 'wp-cerber' ) ),
		'geo_comment'  => array( 'name' => __( 'Post comments', 'wp-cerber' ) ),
		'geo_xmlrpc'   => array( 'name' => __( 'Use XML-RPC', 'wp-cerber' ) ),
		'geo_restapi'  => array( 'name' => __( 'Use REST API', 'wp-cerber' ) ),
	);

	return $rules;
}

function crb_settings_update_geo_rules( $post_fields = array() ) {
	global $check, $admin_country;

	if ( ! lab_lab() ) {
		return;
	}

	$check = array_keys( CERBER_COUNTRY_NAMES );

	// Prevent the admin country from being blocked

	if ( nexus_is_valid_request() ) {
		$admin_country = null;
	}
	else {
		$admin_country = lab_get_country( cerber_get_remote_ip(), false );
	}

	$geo = array();

	foreach ( cerber_geo_rule_set() as $rule_id => $rule ) {

		if ( $data = crb_admin_process_geo( $post_fields, $rule_id ) ) {
			$geo[ $rule_id ] = $data;
		}

		if ( isset( $rule['multi_set'] ) ) {
			foreach ( $rule['multi_set'] as $item_id => $item_title ) {
				$id = $rule_id . '_' . $item_id;
				if ( $data = crb_admin_process_geo( $post_fields, $id ) ) {
					$geo[ $id ] = $data;
				}
			}
		}
	}

	crb_sanitize_deep( $geo );

	if ( update_site_option( CERBER_GEO_RULES, $geo ) ) {
		cerber_admin_message( __( 'Security rules have been updated', 'wp-cerber' ) );
	}
}

function crb_admin_process_geo( $post, $rule_id ) {
	global $check, $admin_country;

	if ( empty( $post[ 'crb-' . $rule_id . '-list' ] ) || empty( $post[ 'crb-' . $rule_id . '-type' ] ) ) {
		return false;
	}

	$list = array_intersect( $post[ 'crb-' . $rule_id . '-list' ], $check );

	if ( $post[ 'crb-' . $rule_id . '-type' ] == 'B' ) {
		$type = 'B';
		if ( $admin_country && ( ( $key = array_search( $admin_country, $list ) ) !== false ) ) {
			unset( $list[ $key ] );
		}
	}
	else {
		$type = 'W';
		if ( $admin_country && ( ( $key = array_search( $admin_country, $list ) ) === false ) ) {
			array_push( $list, $admin_country );
		}
	}

	return array( 'list' => $list, 'type' => $type );
}

/**
 * Generates HTML for showing country in UI
 *
 * @param null $code
 * @param null $ip
 * @param bool $cache_only
 *
 * @return string
 */
function crb_country_html( $code = null, $ip = null, $cache_only = true ) {

	if ( ! lab_lab() ) {
		return '';
	}

	if ( ! $code ) {
		if ( $ip ) {
			$code = lab_get_country( $ip, $cache_only );
		}
		else {
			return '';
		}
	}

	if ( $code ) {
		$ret = crb_get_flag_html( $code, crb_get_country_name( $code ) );
	}
	else {
		$ip_id = cerber_get_id_ip( $ip );
		$ret = crb_get_ajax_placeholder( 'country', $ip_id );
	}

	return $ret;
}

// Traffic =====================================================================================

/**
 * Generates a label for the given HTTP code
 *
 * @param int $http_code
 *
 * @return string HTML code of the label, safe in any context
 *
 * @since 9.6.6.4
 */
function crb_get_http_code_label( int $http_code ): string {

	$http_class = 'crb-http-' . $http_code . ' ' . ( ( $http_code < 400 ) ? 'crb-http-ok' : 'crb-http-error' );

	if ( $http_code == 302 || $http_code == 301 ) {
		$text = 'REDIRECT ' . $http_code;
	}
	else {
		$text = 'HTTP ' . $http_code . ' ' . get_status_header_desc( $http_code );
	}

	return '<span class="' . $http_class . '"> ' . $text . '</span>';
}

/**
 * Marks WP Cerber's fields by transforming values into objects.
 *
 * @param array $fields Fields to apply conditional highlighting
 * @param int $type
 *
 * @return void
 *
 * @since 9.5.3.4
 */
function crb_highlight_fields_type_two( &$fields, $type = 1 ) {
	static $crb_fields;

	if ( ! $crb_fields ) {

		if ( ! $anti = cerber_antibot_gene() ) {
			return;
		}

		$crb_fields[1] = array_flip( array_column( $anti[0], 0 ) );

		// All cookies

		$crb_cookies = array_column( $anti[1], 0 );

		foreach ( $crb_cookies as &$val ) {
			$val = cerber_get_cookie_prefix() . $val;
		}

		$crb_cookies[] = cerber_get_cookie_prefix() . CRB_GROOVE;

		$groove_x = cerber_get_groove_x();
		$crb_cookies[] = cerber_get_cookie_prefix() . CRB_GROOVE . '_x_' . $groove_x[0];

		$crb_cookies[] = cerber_get_cookie_prefix() . 'cerber_nexus_id';

		$crb_fields[2] = array_flip( $crb_cookies );
	}

	foreach ( $fields as $field_id => &$value ) {
		if ( ! $value
		     || ! is_scalar( $value ) ) {
			continue;
		}

		if ( isset( $crb_fields[ $type ][ $field_id ] ) ) {
			$value = (object) array( 'element_class' => 'crb-highlight-this', 'element_value' => $value );
		}
	}
}

/**
 * Marks WP Cerber's fields by transforming values into objects.
 *
 * @param array $fields Fields to apply conditional highlighting
 * @param array $target These fields having these values will be highlighted
 *
 * @return void
 *
 * @since 9.6.6.4
 */
function crb_highlight_fields_type_one( array &$fields, array $target = array() ) {
	foreach ( $fields as $field_id => &$value ) {
		if ( ! $value
		     || ! is_scalar( $value ) ) {
			continue;
		}

		if ( isset( $target[ $field_id ] )
		     && $value == $target[ $field_id ] ) {
			$value = (object) array( 'element_class' => 'crb-highlight-this', 'element_value' => $value );
		}
	}
}

/**
 * Create a table view of an array to display it
 *
 * @param string $title
 * @param array $fields
 * @param bool $plain If true, treat $fields as a two-dimensional list
 * @param bool $nested
 *
 * @return string An escaped table view
 *
 * @deprecated 9.6.9.7 Use {@see crb_ui_table_view()}
 *
 */
function cerber_table_view( string $title, array $fields, $plain = false, $nested = false ) {
	if ( empty( $fields ) ) {
		return '';
	}

	$class = 'crb-fields-table ' . ( $nested ? 'crb-sub-table' : 'crb-top-table' );

	$view = '<table class="' . $class . '">';

	if ( $title ) {
		//$view .= '<tr><td colspan="2" class="crb-monospace-bold">' . $title . '</td></tr>';
		$view .= '<tr><td colspan="2">' . $title . '</td></tr>';
	}

	foreach ( $fields as $key => $value ) {

		$row_name = $key;
		$atts = '';

		if ( is_object( $value ) ) {
			$atts = ' class="' . crb_boring_escape( $value->element_class ) . '"';
			$value = $value->element_value;
		}

		if ( is_array( $value ) ) {
			if ( $plain
			     && is_scalar( $value[0] ) && is_scalar( $value[1] ) ) { // Make sure it is a two-dimensional list
				$row_name = $value[0];
				$content = '<div>' . nl2br( crb_escape_html( $value[1] ) ) . '</div>';
			}
			else {
				$content = cerber_table_view( '', $value, false, true );
			}
		}
		else {
			$content = '<div>' . nl2br( crb_escape_html( $value ) ) . '</div>';
		}

		$view .= '<tr><td ' . $atts . '>' . crb_escape_html( $row_name ) . '</td><td>' . $content . '</td></tr>';
	}

	$view .= '</table>';

	return $view;
}

function cerber_get_wp_type( $wp_type ) {
	$types = array(
		515 => 'XML-RPC',
		520 => 'REST API'
	);

	if ( isset( $types[ $wp_type ] ) ) {
		return $types[ $wp_type ];
	}

	return '';
}

/**
 * Returns the name of a PHP error level constant (e.g., E_WARNING) based on its numeric value.
 *
 * @param int $level Numeric PHP error level.
 *
 * @return string Name of the error level, or 'Unknown (<code>)' if not recognized.
 */
function cerber_get_err_level( $level ): string {

	static $list = [
		E_ERROR             => 'E_ERROR',
		E_WARNING           => 'E_WARNING',
		E_PARSE             => 'E_PARSE',
		E_NOTICE            => 'E_NOTICE',
		E_CORE_ERROR        => 'E_CORE_ERROR',
		E_CORE_WARNING      => 'E_CORE_WARNING',
		E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
		E_USER_ERROR        => 'E_USER_ERROR',
		E_USER_WARNING      => 'E_USER_WARNING',
		E_USER_NOTICE       => 'E_USER_NOTICE',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED        => 'E_DEPRECATED',
		E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
		E_ALL               => 'E_ALL',
	];

	return $list[ $level ] ?? 'Unknown (' . crb_absint( $level ) . ')';
}

/**
 * Check if admin AJAX is permitted.
 *
 */
function cerber_check_ajax_permissions( $strict = true ) {
	if ( nexus_is_valid_request() ) {
		/*
		$nonce = crb_array_get( nexus_request_data()->get_params, 'ajax_nonce' );
		if ( ! $nonce ) {
			$nonce = nexus_request_data()->get_post_data( 'ajax_nonce' );
		}
		if ( ! wp_verify_nonce( $nonce, 'crb-ajax-admin' ) ) {
			return false;
		}
		*/

		return true;
	}

	check_ajax_referer( 'crb-ajax-admin', 'ajax_nonce' );

	if ( ! cerber_user_can_manage() ) {
		if ( $strict ) {
			wp_die( 'Oops! Access denied.' );
		}

		return 0;
	}

	return true;
}

function crb_admin_allowed_ajax( $action ) {
	$list = array(
		'cerber_ajax',
		'cerber_scan_control',
		'cerber_ref_upload',
		'cerber_view_file',
		'cerber_scan_bulk_files'
	);

	return in_array( $action, $list );
}

function crb_is_task_permitted( $die = false ) {
	if ( is_super_admin()
	     || nexus_is_valid_request() ) {
		return true;
	}
	if ( $die ) {
		wp_die( 'Oops! Access denied.' );
	}

	return false;
}

/**
 * Identify and render Cerber's admin page
 *
 * @param string $page_id
 * @param string $active_tab
 */
function cerber_render_admin_page( $page_id = '', $active_tab = '' ) {

	if ( nexus_get_context() ) {
		nexus_show_remote_page();

		return;
	}

	$error = '';

	if ( ! $page_id ) {
		$page_id = crb_admin_get_page();
	}

	if ( $page = cerber_get_admin_page_config( $page_id ) ) {
		if ( ! empty( $page['pro_page'] ) && ! lab_lab() ) {
			cerber_admin_notice( array(
				__( 'These features are available in the professional version of WP Cerber.', 'wp-cerber' ),
				__( 'Know more about all advantages at', 'wp-cerber' ) . ' <a href="https://wpcerber.com/pro/" target="_blank">https://wpcerber.com/pro/</a>'
			) );
		}

		if ( ( $tab_filter = crb_array_get( $page, 'tab_filter' ) )
		     && is_callable( $tab_filter ) ) {
			$page['tabs'] = call_user_func( $tab_filter, $page['tabs'] );
		}

		cerber_show_admin_page( $page['title'], $page['tabs'], $active_tab, $page['callback'] );

	}
	else {
		$error = 'Unknown admin page: ' . crb_escape_html( $page_id );
	}

	if ( $error ) {
		echo '<div class="crb-generic-error">ERROR: ' . $error . '</div>';
	}
}

/**
 * Returns the configuration of the currently rendering WP Cerber admin page
 *
 * @param string $page
 *
 * @return array[]|false
 */
function cerber_get_admin_page_config( $page = '' ) {
	if ( ! $page ) {
		return false;
	}

	if ( $config = crb_get_admin_ui_config( $page ) ) {
		return $config;
	}

	if ( $page == CRB_ADDON_PAGE
	     && $config = CRB_Addons::get_admin_ui() ) {
		return $config;
	}

	return false;
}

/**
 * Configuration data describing UI of all WP Cerber admin pages.
 * To get UI phrases translated, must be invoked on or after the 'init' hook.
 *
 * @param string $page
 *
 * @return array[]|false
 */
function crb_get_admin_ui_config( $page = '' ) {

	// Note: Tab titles (and other phrases) are not translated if it's called before the 'init' hook

	$admin_pages = array(
		'cerber-security'  => array(
			'title'      => 'WP Cerber Security',
			'tabs'       => array(
				'dashboard'     => array( 'bxs-dashboard', __( 'Dashboard', 'wp-cerber' ) ),
				'activity'      => array( 'bx-pulse', __( 'Activity', 'wp-cerber' ) ),
				'sessions'      => array( 'bx-group', __( 'Sessions', 'wp-cerber' ) ),
				'lockouts'      => array( 'bxs-shield', __( 'Lockouts', 'wp-cerber' ) ),
				'main'          => array( 'bx-slider', __( 'Main Settings', 'wp-cerber' ) ),
				'acl'           => array( 'bx-lock', __( 'Access Lists', 'wp-cerber' ) ),
				'hardening'     => array( 'bx-shield-alt', __( 'Hardening', 'wp-cerber' ) ),
				//'users'         => array( 'bx-group', __( 'Users', 'wp-cerber' ) ),
				'notifications' => array( 'bx-bell', __( 'Notifications', 'wp-cerber' ) ),
			),
			'tab_filter' => function ( $tabs_heading ) {
				crb_delete_expired_lockouts();
				$lockouts_count = crb_get_locked_out_num();

				$acl_entries_count = (int) cerber_db_get_var( 'SELECT COUNT(ip) FROM ' . CERBER_ACL_TABLE . ' WHERE acl_slice = 0' );

				crb_sessions_delete_expired();
				$active_sessions_count = crb_sessions_get_num();
				if ( ! $active_sessions_count ) {
					CRB_Deferred_Tasks::add( 'crb_sessions_sync_all' );
				}

				$open_issues_count = CRB_Issues::get_count();
				if ( $open_issues_count ) {
					$tabs_heading['dashboard'][1] .= ' <sup>' . $open_issues_count . '</sup>';
				}

				$tabs_heading['sessions'][1] .= ' <sup>' . $active_sessions_count . '</sup>';
				$tabs_heading['lockouts'][1] .= ' <sup>' . $lockouts_count . '</sup>';
				$tabs_heading['acl'][1] .= ' <sup>' . $acl_entries_count . '</sup>';

				return $tabs_heading;
			},
			'callback'   => function ( $tab ) {
				switch ( $tab ) {
					case 'acl':
						crb_acl_render_management_page();
						break;
					case 'activity':
						crb_render_activity_view();
						break;
					case 'sessions':
						crb_show_sessions_page();
						break;
					case 'lockouts':
						cerber_show_lockouts_page();
						break;
					case 'help':
						cerber_render_help_page();
						break;
					case 'dashboard':
						cerber_show_dashboard();
						break;
					default:
						CRB_Settings_Renderer::render_form( $tab );
				}
			}
		),
		'cerber-recaptcha' => array(
			'title'    => __( 'Anti-spam and bot detection settings', 'wp-cerber' ),
			'tabs'     => array(
				'antispam' => array( 'bx-chip', __( 'Anti-spam engine', 'wp-cerber' ) ),
				'captcha'  => array( 'bxl-google', 'reCAPTCHA' ),
			),
			'callback' => function ( $tab ) {

				switch ( $tab ) {
					case 'captcha':
						$settings_screen_id = 'recaptcha';
						break;
					default:
						$settings_screen_id = 'antispam';
				}

				CRB_Settings_Renderer::render_form( $settings_screen_id );

			}
		),
		'cerber-traffic'   => array(
			'title'    => __( 'Traffic Inspector', 'wp-cerber' ),
			'tabs'     => array(
				'traffic'     => array( 'bx-show', __( 'Live Traffic', 'wp-cerber' ) ),
				'ti_settings' => array( 'bx-slider', __( 'Settings', 'wp-cerber' ) ),
			),
			'callback' => function ( $tab ) {
				switch ( $tab ) {
					case 'ti_settings':
						CRB_Settings_Renderer::render_form( 'traffic' );
						break;
					default:
						CRB_Traffic_Log_Screen::render_screen();
				}
			}
		),
		'cerber-shield'    => array(
			'title'    => __( 'Data Shield Policies', 'wp-cerber' ),
			'tabs'     => array(
				'user_shield' => array( 'bx-group', __( 'Accounts & Roles', 'wp-cerber' ) ),
				'opt_shield'  => array( 'bx-slider', __( 'Site Settings', 'wp-cerber' ) ),
			),
			'callback' => function ( $tab ) {
				CRB_Settings_Renderer::render_form( $tab );
			}
		),
		'cerber-users'     => array(
			'title'    => __( 'User Policies', 'wp-cerber' ),
			'tabs'     => array(
				'role_policies'   => array( 'bx-group', __( 'Role Policies', 'wp-cerber' ) ),
				'global_policies' => array( 'bx-user-detail', __( 'Global Policies', 'wp-cerber' ) ),
			),
			'callback' => function ( $tab ) {
				switch ( $tab ) {
					case 'role_policies':
						crb_admin_render_role_policies();
						break;
					case 'global_policies':
						CRB_Settings_Renderer::render_form( 'users' );
						break;
					default:
						CRB_Settings_Renderer::render_form( $tab );
				}
			}
		),
		'cerber-rules'     => array(
			'pro_page' => 1,
			'title'    => __( 'Security Rules', 'wp-cerber' ),
			'tabs'     => array(
				'geo' => array( 'bxs-world', __( 'Countries', 'wp-cerber' ) ),
			),
			'callback' => function ( $tab ) {
				switch ( $tab ) {
					case 'geo':
						crb_admin_show_geo_rules();
						break;
					default:
						crb_admin_show_geo_rules();
				}
			}
		),
		'cerber-integrity' => array(
			'title'      => __( 'Site Integrity', 'wp-cerber' ),
			'tabs'       => array(
				'scan_main'       => array( 'bx-radar', __( 'Security Scanner', 'wp-cerber' ) ),
				'scan_settings'   => array( 'bxs-slider-alt', __( 'Settings', 'wp-cerber' ) ),
				'scan_schedule'   => array( 'bx-time', __( 'Scheduling', 'wp-cerber' ) ),
				'scan_policy'     => array( 'bx-bolt', __( 'Cleaning up', 'wp-cerber' ) ),
				'scan_ignore'     => array( 'bx-hide', __( 'Ignore List', 'wp-cerber' ) ),
				'scan_quarantine' => array( 'bx-trash', __( 'Quarantine', 'wp-cerber' ) ),
				'scan_insights'   => array( 'bx-flask', __( 'Analytics', 'wp-cerber' ) ),
			),
			'tab_filter' => function ( $tabs ) {

				$numi = 0;
				if ( $list = cerber_get_set( 'ignore-list' ) ) {
					$numi = count( $list );
				}

				$numq = cerber_get_set( 'quarantined_total', null, false );
				if ( ! is_numeric( $numq ) ) {
					CRB_Deferred_Tasks::add( '_crb_qr_total_sync' );
					$numq = '';
				}

				$tabs['scan_quarantine'][1] .= ' <sup id="crb-quarantined_total">' . $numq . '</sup>';
				$tabs['scan_ignore'][1] .= ' <sup id="crb-ignore_total">' . $numi . '</sup>';

				return $tabs;
			},
			'callback'   => function ( $tab ) {
				switch ( $tab ) {
					case 'scan_settings':
						CRB_Settings_Renderer::render_form( 'scanner' );
						break;
					case 'scan_schedule':
						CRB_Settings_Renderer::render_form( 'schedule' );
						break;
					case 'scan_policy':
						CRB_Settings_Renderer::render_form( 'policies' );
						break;
					case 'scan_quarantine':
						cerber_show_quarantine();
						break;
					case 'scan_ignore':
						cerber_show_ignore();
						break;
					case 'scan_insights':
						cerber_scan_insights();
						break;
					case 'help':
						cerber_render_help_page();
						break;
					default:
						cerber_show_scanner();
				}
			}
		),
		'cerber-tools'     => array(
			'title'    => __( 'Tools', 'wp-cerber' ),
			'tabs'     => array(
				//'imex'       => array( 'bx-layer', __( 'Export & Import', 'wp-cerber' ) ),
				'imex'       => array( 'bx-layer', __( 'Manage Settings', 'wp-cerber' ) ),
				'diagnostic' => array( 'bx-wrench', __( 'Diagnostic', 'wp-cerber' ) ),
				'diag-log'   => array( 'bx-bug', __( 'Diagnostic Log', 'wp-cerber' ) ),
				'changelog'  => array( 'bx-collection', __( 'Changelog', 'wp-cerber' ) ),
				'license'    => array( 'bx-key', __( 'License', 'wp-cerber' ) ),
			),
			'callback' => function ( $tab ) {
				switch ( $tab ) {
					case 'diagnostic':
						cerber_show_diag_page();
						break;
					case 'license':
						cerber_show_lic();
						break;
					case 'diag-log':
						cerber_show_diag_log();
						break;
					case 'changelog':
						cerber_show_change_log();
						break;
					case 'help':
						cerber_render_help_page();
						break;
					default:
						if ( ! nexus_is_valid_request() ) {
							cerber_show_imex();
						}
						else {
							echo 'This admin page is not available in this mode.';
						}
				}
			}
		),
	);


	if ( ! $page ) {
		return $admin_pages;
	}

	return crb_array_get( $admin_pages, $page );
}

/**
 * Identifies and retrieves Page ID by the given Tab ID
 *
 * @param string $tab Tab ID used in the query parameters. In some cases can be the Page ID - in case of the first tab on the page.
 *
 * @return string Page ID to be used in URL query parameters. Safe in any context.
 *
 * @since 9.6.2.1
 */
function crb_determine_page( string $tab ): string {
	static $map = array();

	if ( ! $map ) {
		$pages = crb_get_admin_ui_config();

		// Create tab => page structure

		foreach ( $pages as $page_id => $config ) {
			if ( ! empty ( $config['tabs'] ) ) {
				$map = array_merge( $map, array_fill_keys( array_keys( $config['tabs'] ), $page_id ) );
			}
		}
	}

	if ( $page = $map[ $tab ] ?? '' ) {
		return $page;
	}

	// An exception when the first tab on an admin page is specified by using Page ID since pages and tabs are unique

	if ( in_array( $tab, $map ) ) {
		return $tab;
	}

	if ( $page = CRB_Addons::get_addon_page( $tab ) ) {
		return $page;
	}

	$page = '';

	if ( list( $prefix ) = explode( '_', $tab, 2 ) ) {
		if ( $prefix == 'nexus' ) {
			$page = 'cerber-nexus';
		}
	}

	return $page;
}

/**
 * Extracts specified query parameters from the request URI and returns them as object properties.
 *
 * @param array $fields List of query parameters to extract from the request.
 * @param array $values Optional. Key-value pairs to overwrite extracted parameters.
 *
 * @return object Object with properties corresponding to the specified query parameters. Values are trimmed.
 */
function crb_admin_parse_query( array $fields, array $values = array() ): object {
	$arr = crb_get_query_params();
	$ret = array();

	foreach ( $fields as $field ) {

		$val = $values[ $field ] ?? crb_array_get( $arr, $field, false );

		if ( is_array( $val ) ) {
			$val = array_map( 'trim', $val );
		}
        elseif ( $val !== false ) {
			$val = trim( $val );
		}

		$ret[ $field ] = $val;
	}

	return (object) $ret;
}

function crb_admin_get_page() {
	if ( nexus_is_valid_request() ) {
		return nexus_request_data()->page;
	}

	return cerber_get_get( 'page', '[A-Z0-9\_\-]+' );
}

/**
 * Retrieves the ID of the curretly selected tab of the currently displaying admin page.
 * The tab is identified by $_GET['tab'].
 * If no active tab is detected and the $tabs (group of tabs) provided, returns the first one as the default
 *
 * @param array $tabs
 *
 * @return string
 */
function crb_admin_get_tab( $tabs = array() ): string {
	if ( nexus_is_valid_request() ) {
		$tab = nexus_request_data()->tab;
	}
	else {
		$tab = cerber_get_get( 'tab', '[\w\-]+' );
	}

	if ( empty( $tabs ) ) {
		return (string) $tab;
	}

	$tabs['help'] = 1; // always must be in the array

	if ( ! $tab || ! isset( $tabs[ $tab ] ) ) {
		reset( $tabs );
		$tab = key( $tabs ); // Takes first tab from the group
	}

	return (string) $tab;
}

function cerber_show_tabs( $active, $tabs = array() ) {
	echo '<h2 class="nav-tab-wrapper cerber-tabs">';

	$args = array( 'page' => crb_admin_get_page() );

	foreach ( $tabs as $tab => $data ) {
		echo '<a href="' . cerber_admin_link( $tab, $args ) . '" class="nav-tab ' . ( $tab == $active ? 'nav-tab-active' : '' ) . '"><i class="crb-icon crb-icon-' . $data[0] . '"></i> ' . $data[1] . '</a>';
	}

	echo '<a href="' . cerber_admin_link( 'help', $args ) . '" class="nav-tab ' . ( $active == 'help' ? 'nav-tab-active' : '' ) . '"><i class="crb-icon crb-icon-bx-idea"></i> ' . __( 'Help', 'wp-cerber' ) . '</a>';

	$lab = lab_indicator();
	$ro = '';
	if ( nexus_is_valid_request() && ! nexus_is_granted( 'submit' ) ) {
		$ro = '<span style="font-weight: 600; font-size: 0.8em; color: #f00;">Read-only mode</span>';
	}

	echo '<div style="float: right;">' . $ro . ' ' . $lab . '</div>';

	echo '</h2>';
}

/**
 * Bulk action for WP_List_Table
 *
 * @return bool|array|string
 */
function cerber_get_bulk_action() {

	// GET
	if ( cerber_is_http_get() ) {
		if ( ( $ac = crb_get_query_params( 'action', '[\w\-]+' ) ) && $ac != '-1' ) {
			return $ac;
		}
		if ( ( $ac = crb_get_query_params( 'action2', '[\w\-]+' ) ) && $ac != '-1' ) {
			return $ac;
		}

		return false;
	}

	// POST
	if ( ( $ac = crb_get_post_fields( 'action', false, '[\w\-]+' ) ) && $ac != '-1' ) {
		return $ac;
	}
	if ( ( $ac = crb_get_post_fields( 'action2', false, '[\w\-]+' ) ) && $ac != '-1' ) {
		return $ac;
	}

	return false;
}

function crb_admin_cool_features() {
	return
		'<div class="crb-pro-req">' .
		__( 'These features are available in the professional version of WP Cerber.', 'wp-cerber' ) .
		'<br/><br/>' . __( 'Know more about all advantages at', 'wp-cerber' ) .
		' <a href="https://wpcerber.com/pro/" target="_blank">https://wpcerber.com/pro/</a>
        </div>';
}

/**
 * Generates a flexible box HTML DOM element using string elements from $elements
 *
 * @param string[] $elements Text of child elements
 * @param string $justify How to arrange child elements
 *
 * @return string HTML code of flexible box layout
 *
 * @since 9.6.5.12
 */
function crb_generate_html_flex( array $elements, $class = '', $justify = 'space-between' ): string {

	$ret = '<div class="' . crb_boring_escape( $class ) . '" style="display: flex; justify-content: ' . crb_boring_escape( $justify ) . ';">';
	$ret .= '<div>' . implode( '</div><div>', $elements ) . '</div>';
	$ret .= '</div>';

	return $ret;
}

/**
 * Creates a Copy To Clipboard hypertext link.
 *
 * @param string $source_class The class of the elements to copy text content from.
 * @param bool $plain If true the plain inner text will be copied without tags and processing, otherwise <br/> tags will be converted into new lines and other tags will be removed.
 *
 * @return string HTML code of the link
 *
 * @since 9.6.5.12
 */
function crb_copy_to_clipboard( string $source_class, $plain = true ): string {
	return '<a href="#" data-plain_text="' . ( $plain ? 1 : 0 ) . '" data-copy_clipboard_class="' . crb_boring_escape( $source_class ) . '" class="crb-copy-to-clipboard">' . __( 'Copy To Clipboard', 'wp-cerber' ) . '</a>';
}

/**
 * Creates an HTML link with a confirmation dialog.
 *
 * When the user clicks the link, a confirmation dialog is displayed with the specified message.
 * If the user does not confirm, the action is cancelled, and the browser does not follow the link.
 *
 * @param string $url URL of the link to follow when the user confirms the action
 * @param string $text Link text
 * @param string $msg Optional confirmation message, if not specified the default message "Are you sure?" is used.
 * @param string $class Optional class for a element
 *
 * @return string HTML element with escaped attributes
 */
function crb_confirmation_link( string $url, string $text, string $msg = '', string $class = '' ) {

	$data = $msg ? 'data-user_message="' . crb_escape_html( $msg ) . '"' : '';
	$class = $class ? crb_escape_html( $class ) : '';

	return '<a href="' . crb_escape_url( $url ) . '" class="crb-confirm-action ' . $class . '" ' . $data . '>' . $text . '</a>';
}

/**
 * @param array $args
 * @param string $setting
 *
 * @return string
 *
 * @since 8.9.6.1
 */
function crb_test_notify_link( $args = array(), $setting = null ) {
	if ( $setting
	     && ! crb_get_settings( $setting ) ) {
		return '';
	}

	if ( isset( $args['title'] ) ) {
		$title = $args['title'];
		unset( $args['title'] );
	}
	else {
		$title = __( 'Click to send test', 'wp-cerber' );
	}

	return '<span class="crb-insetting-link">[ <a href="' .
	       cerber_admin_link_add( array_merge(
		       array(
			       'cerber_admin_do' => 'testnotify',
			       'type'            => 'lockout',
		       ), $args ) ) . '">' . $title . '</a> ]</span>';
}

// Pop-up dialogs -------------------------------------------------------------

/**
 * Creates a pop-up dialog DOM element with a button that opens the dialog.
 *
 * @param array $fields Form fields - left side
 * @param array $aside Form fields - right side, optional
 * @param array $control
 * @param array $atts HTML attributes for popup element
 * @param string $method Form method
 * @param string|null $action Form action
 *
 * @return string The HTML code of the pop-up prepared to add to the web page
 *
 * @since 8.9.5.3
 */
function crb_create_popup_form( array $fields, array $aside = array(), array $control = array(), array $atts = array(), string $method = 'get', string $action = '' ) {
	static $counter = 0;
	$counter ++; // In case of multiple dialogs on a page

	$icon = $control['icon'] ?? '';
	$label = $control['label'] ?? '';
	$type = $control['type'] ?? 'button';

	$icon_html = ( $icon ) ? '<i class="crb-icon ' . crb_escape_html( $icon ) . '"></i>' : '';
	$space = ( $icon_html && $label ) ? ' ' : '';

	if ( $type == 'button' ) {
		$opener = '<a data-popup_element_id="crb-popup-dialog-' . $counter . '" class="button button-secondary cerber-button crb-popup-dialog-open" href="#" style="margin-right: 0.5em;">' . $icon_html . $space . $label . '</a>';
	}
	else {
		$opener = $icon_html . $space . '<a data-popup_element_id="crb-popup-dialog-' . $counter . '" class="crb-popup-dialog-open" href="#">' . $label . '</a>';
	}

	if ( $aside ) {
		$class = 'crb-table-2cols';
		$td_aside = '<td><div class="crb-popup-dialog-aside">' . crb_create_dialog_form( $aside ) . '</div></td>';
		$last_row = '<td></td>';
	}
	else {
		$class = 'crb-table-1col';
		$td_aside = '';
		$last_row = '';
	}

	$html_atts = '';

	if ( $atts ) {
		foreach ( $atts as $att => $val ) {
			$html_atts .= ' ' . crb_escape_html( $att ) . '="' . crb_escape_html( $val ) . '" ';
		}
	}

	if ( $action ) {
		$form_action = $action;
	}
	else {
		$form_action = cerber_admin_link( crb_admin_get_tab() );
	}

	$dialog = '<div id="crb-popup-dialog-' . $counter . '" class="mfp-hide crb-popup-dialog" ' . $html_atts . '>';
	$dialog .= '<form action="' . crb_escape_url( $form_action ) . '" method="' . crb_escape_html( $method ) . '">';
	$dialog .= '<table class="' . crb_escape_html( $class ) . '">';
	$dialog .= '<tr><td>' . crb_create_dialog_form( $fields ) . '</td>' . $td_aside . '</tr>';

	$dialog .= '<tr>' . $last_row . '<td class="crb-dialog-controls">
                    <input type="submit" class="button button-primary" value="' . __( 'OK', 'wp-cerber' ) . '">
                    &nbsp; <input type="button" class="button button-secondary crb-popup-dialog-close" value="' . __( 'Cancel', 'wp-cerber' ) . '">
			    </td></tr>';

	$dialog .= '</table></form></div>';

	return $dialog . $opener;
}

/**
 * Creates a basic HTML form for pop-up dialogs
 *
 * @param array $form_data List of form fields
 *
 * @return string HTML code
 *
 * @since 8.9.5.3
 */
function crb_create_dialog_form( $form_data ) {

	$form = '';

	if ( $title = $form_data['title'] ?? '' ) {
		$form .= '<h3>' . $title . '</h3>';
	}

	$form .= $form_data['html'] ?? '';

	$hidden = array_merge(
		$form_data['hidden'] ?? array(), array(
		'page'         => crb_admin_get_page(),
		'tab'          => crb_admin_get_tab(),
		'cerber_nonce' => crb_create_nonce(),
	) );

	foreach ( $hidden as $field => $value ) {

		$field = crb_escape_html( $field );

		if ( is_array( $value ) ) {
			foreach ( $value as $val ) {
				$form .= '<input type="hidden" name="' . $field . '[]" value="' . crb_escape_html( $val ) . '">' . "\n";
			}
		}
		else {
			$form .= '<input type="hidden" name="' . $field . '" value="' . crb_escape_html( $value ) . '">' . "\n";
		}
	}

	$form .= '<table class="crb-form-fields">';

	$visible = $form_data['visible'] ?? array();

	foreach ( $visible as $field => $config ) {

		$atts = '';

		foreach ( $config['atts'] as $att => $val ) {
			$atts .= ' ' . crb_escape_html( $att ) . '="' . crb_escape_html( $val ) . '" ';
		}

		$field = crb_escape_html( $field );

		$input = '<input id="' . $field . '" name="' . $field . '" value="' . crb_escape_html( $config['value'] ) . '" ' . $atts . '>';
		$label = '<label for="' . $field . '">' . $config['label'] . '</label>';

		$type = $config['atts']['type'] ?? '';

		$form .= '<tr><td>';

		if ( $type == 'checkbox' ) {
			$form .= '<div class="crb-wrap-' . $type . '"><div>' . $input . '</div><div>' . $label . '</div></div>';
		}
		else {
			$form .= $label . '<div>' . $input . '</div>';
		}

		$form .= '</td></tr>';
	}

	$form .= '</table>';

	$mess = $form_data['error_messages'] ?? array();

	foreach ( $mess as $id => $msg ) {
		$form .= '<p id="crb-message-' . crb_sanitize_id( $id ) . '" class="crb-error-text" style="display:none;">' . $msg . '</p>';
	}

	return '<div class="crb-popup-dialog-form">' . $form . '</div>';

}

add_filter( 'self_admin_url', 'crb_set_release_note_url' );

/**
 * Sets the correct URL on the Dashboard -> Updates to view the page with the last version info.
 * Fix broken link pointing to a non-existing wordpress.org repository plugin profile.
 *
 * @param string $url
 *
 * @return string
 *
 * @since 9.5.8
 */
function crb_set_release_note_url( $url ): string {
	global $pagenow;

	$add = '';
	$replace = false;

	if ( 'update-core.php' === $pagenow

	     // We are looking for: plugin-install.php?tab=plugin-information&plugin=wp-cerber&section=changelog&TB_iframe=true&width=640&height=662
	     // The URL is hard coded in /wp-admin/update-core.php

	     && strpos( $url, '/wp-admin/plugin-install.php?tab=plugin-information&plugin=wp-cerber&section=changelog&TB_iframe=true&width=640&height=662' ) ) {
		$add = '?TB_iframe=true&width=640&height=662';
		$replace = true;
	}
    elseif ( 'plugins.php' === $pagenow

	         // This URL is generated in wp_plugin_update_row()

	         && strpos( $url, '/wp-admin/plugin-install.php?tab=plugin-information&plugin=wp-cerber&section=changelog' ) ) {
		$replace = true;
	}

	if ( ! $replace ) {
		return $url;
	}

	$plugins = get_plugin_updates();
	$new_version = '';

	if ( $plugin_data = $plugins[ CERBER_PLUGIN_ID ] ?? false ) {
		$new_version = $plugin_data->update->new_version;
	}

	if ( $new_version ) {
		$url = 'https://wpcerber.com/wp-cerber-security-' . str_replace( '.', '-', $new_version ) . '/' . $add;
	}

	return $url;
}

/**
 * @param string $icon_id
 *
 * @return string HTML code to display a font-based icon
 *
 * @since 9.6.1.3
 */
function crb_get_icon( $icon_id ) {
	return '<i class="crb-icon crb-icon-' . ( CRB_ADMIN_ICONS[ $icon_id ] ?? 'unknown-icon' ) . '"></i>';
}

// Setting up WordPress navigation menu editor ----------------------------------

add_action( 'admin_head-nav-menus.php', function () {
	add_meta_box( 'wp_cerber_nav_menu',
		'WP Cerber',
		'cerber_nav_menu_box',
		'nav-menus',
		'side',
		'low' );
} );

function cerber_nav_menu_box() {

	// Warning: do not change array indexes
	$list = array(
		'login-url'  => __( 'Log In', 'wp-cerber' ),
		'logout-url' => __( 'Log Out', 'wp-cerber' ),
		'reg-url'    => __( 'Register', 'wp-cerber' ),
	);
	if ( class_exists( 'WooCommerce' ) ) {
		$list['wc-login-url'] = __( 'WooCommerce Log In', 'wp-cerber' );
		$list['wc-logout-url'] = __( 'WooCommerce Log Out', 'wp-cerber' );
	}

	?>
    <div id="posttype-wp-cerber-nav" class="posttypediv">
        <div id="tabs-panel-wp-cerber-nav" class="tabs-panel tabs-panel-active">
            <ul id="wp-cerber-nav-checklist" class="categorychecklist form-no-clear">
				<?php
				$i = - 1;
				foreach ( $list as $key => $value ) :
					$id = 'wp-cerber-' . $key;
					?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox"
                                   name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-object-id]"
                                   value="<?php echo esc_attr( $i ); ?>"/> <?php echo esc_html( $value ); ?>
                        </label>
                        <input type="hidden" class="menu-item-type"
                               name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-type]" value="custom"/>
                        <input type="hidden" class="menu-item-title"
                               name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-title]"
                               value="<?php echo esc_html( $value ); ?>"/>
                        <input type="hidden" class="menu-item-url"
                               name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-url]"
                               value="<?php echo '#will-be-generated-automatically'; ?>"/>
                        <input type="hidden" class="menu-item-attr-title"
                               name="menu-item[<?php echo esc_attr( $i ); ?>][menu-item-attr-title]"
                               value="<?php echo '*MENU*CERBER*|' . $id; ?>"/>
                    </li>
					<?php
					$i --;
				endforeach;
				?>
            </ul>
        </div>
        <p class="button-controls">
            <span class="add-to-menu">
					<button type="submit" class="button-secondary submit-add-to-menu right"
                            value="<?php esc_attr_e( 'Add to menu' ); ?>" name="add-post-type-menu-item"
                            id="submit-posttype-wp-cerber-nav"><?php esc_html_e( 'Add to menu' ); ?></button>
					<span class="spinner"></span>
            </span>
        </p>
    </div>
	<?php
}

/**
 * Renders a popup form to display screen configuration dialog
 *
 * @return string HTML
 *
 * @since 9.6.4.5
 */
function crb_admin_screen_dialog() {

	$atts = array( 'type' => 'checkbox', 'data-validation_group' => 'required_min_one' );

	$widgets = CRB_Widgets::get_titles( true, false );
	$field_list = array();

	foreach ( $widgets as $widget_id => $widget ) {

		if ( ! CRB_Widgets::is_hideable( $widget_id ) ) {
			continue;
		}

		if ( CRB_Widgets::is_active( $widget_id ) ) {
			$atts['checked'] = 1;
		}
		else {
			unset( $atts['checked'] );
		}

		$field_list[ $widget_id ] = array(
			'label' => $widget[0],
			'value' => '1',
			'atts'  => $atts,
		);
	}

	$form_fields = array(
		'title'          => __( 'Select widgets to display', 'wp-cerber' ),
		'visible'        => $field_list,
		'hidden'         => array(
			'cerber_admin_do' => 'save_widget_list',
		),
		'error_messages' => array( 'required_min_one' => __( 'Please select at least one widget', 'wp-cerber' ) )
	);

	return crb_create_popup_form( $form_fields, [], [ 'icon' => 'crb-icon-bx-cog' ], [ 'data-narrow' => 1 ], 'post' );
}


add_action( 'heartbeat_tick', 'cerber_update_widget_data' );
/**
 * Refresh expiring widgets stored in the WordPress persistent object cache
 *
 * @return void
 *
 * @since 9.6.4.3
 */
function cerber_update_widget_data() {
	if ( ! CRB_Cache::checker()
	     || ! is_super_admin() ) {
		return;
	}

	cerber_widgets_init();
	CRB_Widgets::update_cache();
}
