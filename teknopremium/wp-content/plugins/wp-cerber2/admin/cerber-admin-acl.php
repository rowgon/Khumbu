<?php

// Access Lists (ACL) ---------------------------------------------------------

/**
 * Adds an IP address, IP range, or IP network to an IP Access List.
 *
 * Depending on the specified list tag, the entry is added to either the Allowed or Blocked IP Access List.
 *
 * Supported formats for IP addresses (IPv4 and IPv6) with examples:
 *
 *  IPv4 Formats:
 *  - Single address: `192.168.5.22`
 *  - Range (hyphen-separated): `192.168.1.45 - 192.168.22.165`
 *  - CIDR notation: `192.168.128.0/20`
 *  - Wildcard notation: `192.168.77.*`, `192.168.*.*`, `192.*.*.*`
 *  - Any address: `0.0.0.0/0` or `*.*.*.*`
 *
 *  IPv6 Formats:
 *  - Single address: `2001:0db8:85a3:0000:0000:8a2e:0370:7334`
 *  - Range (hyphen-separated): `2001:db8::ff00:41:0 - 2001:db8::ff00:41:12ff`
 *  - CIDR notation: `2001:db8::/46`
 *  - Wildcard notation: `2001:db8::ff00:41:*`
 *  - Any address: `::/0`
 *
 * @param string $ip An IP address, IP range, or IP network in CIDR format.
 * @param string $tag 'W' for allowed list, 'B' for blocked list.
 * @param string|null $comment (Optional) A note visible to website administrators. Default: ''.
 * @param int $acl_slice (Optional) The ID of the Access List. Use 0 for the global Access List. Default: 0.
 *
 * @return true|WP_Error Returns true on success or WP_Error on failure.
 *
 * @throws WP_Error Possible error codes:
 *                  - 'acl_wrong_ip': Invalid IP address, range, or network format.
 *                  - 'acl_duplicate': The IP address or range is already in the list.
 *                  - 'acl_db_error': A database error occurred while inserting the entry.
 */
function cerber_acl_add( $ip, $tag, $comment = '', $acl_slice = 0 ) {
	global $wpdb;

	$ip = trim( $ip );

	$acl_slice = absint( $acl_slice );
	$v6range = '';
	$ver6 = 0;

	if ( cerber_is_ipv4( $ip ) ) {
		$begin = ip2long( $ip );
		$end = ip2long( $ip );
	}
	elseif ( cerber_is_ipv6( $ip ) ) {
		$ip = cerber_ipv6_short( $ip );
		list( $begin, $end, $v6range ) = crb_ipv6_prepare( $ip, $ip );
		$ver6 = 1;
	}
	elseif ( ( $range = cerber_any2range( $ip ) )
	         && is_array( $range ) ) {
		$ver6 = $range['IPV6'];
		$begin = $range['begin'];
		$end = $range['end'];
		$v6range = $range['IPV6range'];
	}
	else {
		/* translators: %s is the IP address or range. */
		return new WP_Error( 'acl_wrong_ip', sprintf( __( 'Incorrect IP address or IP range: %s', 'wp-cerber' ), $ip ) );
	}

	if ( cerber_db_get_var( 'SELECT ip FROM ' . CERBER_ACL_TABLE . ' WHERE acl_slice = ' . $acl_slice . ' AND ver6 = ' . $ver6 . ' AND ip_long_begin = ' . $begin . ' AND ip_long_end = ' . $end . ' AND v6range = "' . $v6range . '" LIMIT 1' ) ) {
		return new WP_Error( 'acl_wrong_ip', __( 'The IP address you are trying to add is already in the list', 'wp-cerber' ) );
	}

	$result = $wpdb->insert( CERBER_ACL_TABLE, array(
		'ip'            => $ip,
		'ip_long_begin' => $begin,
		'ip_long_end'   => $end,
		'tag'           => $tag,
		'comments'      => $comment,
		'acl_slice'     => $acl_slice,
		'v6range'       => $v6range,
		'ver6'          => $ver6,
	), array( '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%d' ) );

	if ( ! $result ) {
		return new WP_Error( 'acl_db_error', $wpdb->last_error );
	}

	crb_event_handler( 'ip_event', array(
		'e_type'   => 'acl_add',
		'ip'       => $ip,
		'tag'      => $tag,
		'slice'    => $acl_slice,
		'comments' => $comment,
	) );

	return true;
}

function cerber_acl_add_allowed( $ip, $comment = '' ) {
	return cerber_acl_add( $ip, 'W', $comment );
}

function cerber_acl_add_blocked( $ip, $comment = '' ) {
	return cerber_acl_add( $ip, 'B', $comment );
}

function cerber_acl_remove( $ip, $acl_slice = 0 ) {

	if ( ! is_numeric( $acl_slice ) ) {
		return false;
	}

	$acl_slice = absint( $acl_slice );
	$ip = preg_replace( CRB_IP_NET_RANGE, ' ', $ip );

	$ret = cerber_db_query( 'DELETE FROM ' . CERBER_ACL_TABLE . ' WHERE acl_slice = ' . $acl_slice . ' AND ip = "' . $ip . '"' );

	crb_event_handler( 'ip_event', array(
		'e_type' => 'acl_remove',
		'ip'     => $ip,
		'slice'  => $acl_slice,
		'result' => $ret
	) );

	return $ret;
}

/**
 * Can a given IP be added to the blacklist
 *
 * @param $ip string A candidate to be added to the list
 * @param $list string
 *
 * @return bool True if IP can be listed
 */
function cerber_acl_can_be_blocked( $ip, $list = 'B' ) {

	if ( $list == 'B' ) {

		$admin_ip = cerber_get_remote_ip();

		if ( cerber_is_ip( $ip ) ) {
			if ( $admin_ip == cerber_ipv6_short( $ip ) ) {
				return false;
			}

			return true;
		}

		// $ip = range

		if ( crb_acl_is_allowed( $admin_ip ) ) {
			return true;
		}

		if ( ! $range = cerber_any2range( $ip ) ) {
			return false;
		}

		if ( cerber_is_ip_in_range( $range, $admin_ip ) ) {
			return false;
		}

		return true;

	}

	return true;
}


/**
 * ACL management forms
 *
 * @return void
 */
function crb_acl_render_management_page() {

	$acl_allowed_form = '<div class="crb-title-plus"><div><h2>' . __( 'Allowed IP Access List', 'wp-cerber' ) . '</h2></div>';
	$acl_allowed_form .= '<div><span style="opacity: 0.6;">&#x2500;&#x2500;&nbsp;&nbsp;&nbsp;</span>';
	$acl_allowed_form .= '<a href="' . cerber_activity_link( [], 500 ) . '">' . __( 'View Activity', 'wp-cerber' ) . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="' . cerber_admin_link( 'imex' ) . '#crb-bulk-load-acl">' . __( 'Import Entries', 'wp-cerber' ) . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a target="_blank" href="https://wpcerber.com/using-ip-access-lists-to-protect-wordpress/">How Access Lists work</a></div></div>';
	$acl_allowed_form .= '<div class="crb-subtitle-plus">' . __( 'IP addresses you trust and allow to access the site. Requests from these IPs are excluded from most security checks and access controls.', 'wp-cerber' ) . '</div>';
	$acl_allowed_form .= crb_acl_render_table_view( 'W' );

	echo $acl_allowed_form;

	$acl_blocked_form = '<div class="crb-title-plus"><div><h2>' . __( 'Blocked IP Access List', 'wp-cerber' ) . '</h2></div>';
	$acl_blocked_form .= '<div><span style="opacity: 0.6;">&#x2500;&#x2500;&nbsp;&nbsp;&nbsp;</span>';
	$acl_blocked_form .= '<a href="' . cerber_activity_link( [], 14 ) . '">' . __( 'View Activity', 'wp-cerber' ) . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="' . cerber_admin_link( 'imex' ) . '#crb-bulk-load-acl">' . __( 'Import Entries', 'wp-cerber' ) . '</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a target="_blank" href="https://wpcerber.com/using-ip-access-lists-to-protect-wordpress/">How Access Lists work</a></div></div>';
	$acl_blocked_form .= '<div class="crb-subtitle-plus">' . __( 'IP addresses you block from interacting with the site. These IPs cannot log in, submit data, or use protected site features, but can still view public pages.', 'wp-cerber' ) . '</div>';
	$acl_blocked_form .= crb_acl_render_table_view( 'B' );

	echo $acl_blocked_form;

	$user_ip = cerber_get_remote_ip();
	$link = cerber_admin_link( 'activity' ) . '&amp;filter_ip=' . $user_ip;

	$name = '';
	if ( $country = lab_get_country( $user_ip, false ) ) {
		$name = ' [ ' . crb_get_country_name( $country ) . ' ] ';
	}

	echo '<div style="margin-bottom: 30px;">' . __( 'Your IP address', 'wp-cerber' ) . ' &nbsp;<b>' . $user_ip . '</b> ' . $name . '&nbsp;&nbsp;|&nbsp;&nbsp;<a href="' . $link . '">' . __( 'View Activity', 'wp-cerber' ) . '</a></div>';

	?>

	<table class="crb-acl-hints">
		<tr>
			<td colspan="3">Use the following formats when adding entries to the access lists</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Single IPv4 address</td>
			<td>192.168.5.22</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Range specified with hyphen (dash)</td>
			<td>192.168.1.45 - 192.168.22.165</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Range specified with CIDR</td>
			<td>192.168.128.0/20</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Subnet Class C specified with CIDR</td>
			<td>192.168.77.0/24</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Any IPv4 address specified with CIDR</td>
			<td>0.0.0.0/0</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Subnet Class C specified with wildcard</td>
			<td>192.168.77.*</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Subnet Class B specified with wildcard</td>
			<td>192.168.*.*</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Subnet Class A specified with wildcard</td>
			<td>192.*.*.*</td>
		</tr>
		<tr>
			<td>IPv4</td>
			<td>Any IPv4 address specified with wildcard</td>
			<td>*.*.*.*</td>
		</tr>
		<tr>
			<td>IPv6</td>
			<td>Single IPv6 address</td>
			<td>2001:0db8:85a3:0000:0000:8a2e:0370:7334</td>
		</tr>
		<tr>
			<td>IPv6</td>
			<td>Range specified with hyphen (dash)</td>
			<td>2001:db8::ff00:41:0 - 2001:db8::ff00:41:12ff</td>
		</tr>
		<tr>
			<td>IPv6</td>
			<td>Range specified with CIDR</td>
			<td>2001:db8::/46</td>
		</tr>
		<tr>
			<td>IPv6</td>
			<td>Range specified with wildcard</td>
			<td>2001:db8::ff00:41:*</td>
		</tr>
		<tr>
			<td>IPv6</td>
			<td>Any IPv6 address specified with CIDR</td>
			<td>::/0</td>
		</tr>
	</table>

	<?php
}

/*
	Generates HTML view to display the ACL management area
*/
function crb_acl_render_table_view( string $tag, int $slice_id = 0 ): string {
	global $wpdb;

	$activity_url = cerber_admin_link( 'activity' );
	$slice_id = absint( $slice_id );
	$tag = preg_replace( '/[^WB]/', '', $tag );

	if ( $rows = $wpdb->get_results( 'SELECT * FROM ' . CERBER_ACL_TABLE . ' WHERE acl_slice = ' . $slice_id . ' AND tag = "' . $tag . '" ORDER BY ip_long_begin, ip' ) ) {

		$list = array();

		foreach ( $rows as $row ) {
			$links = '';
			if ( ! $row->ver6 || cerber_is_ipv6( $row->ip ) ) {
				$links = '<a class="crb-button-tiny" href="' . crb_escape_url( $activity_url . '&filter_ip=' . urlencode( cerber_ipv6_short( $row->ip ) ) ) . '">' . __( 'Check for activities', 'wp-cerber' ) . '</a> ' . crb_get_traffic_link( array( 'filter_ip' => $row->ip ) );
			}

			$list[] = '<td>' . $row->ip . '</td><td>' . $row->comments . '</td><td>' . $links . '</td>
            <td><a class="delete_entry crb-button-tiny" href="javascript:void(0)" data-ip="' . crb_attr_escape( $row->ip ) . '">' . __( 'Remove', 'wp-cerber' ) . '</a>
            </td>';
		}

		$html_view = '<table id="acl_' . $tag . '" class="acl-table" data-acl-slice="' . $slice_id . '"><tr>' . implode( '</tr><tr>', $list ) . '</tr></table>';
	}
	else {
		$html_view = '<p style="text-align: center;">' . __( 'List is empty', 'wp-cerber' ) . '</p>';
	}

	$html_view = '<div class="acl-wrapper"><div class="acl-items">'
	             . $html_view . '</div>
            <form action="" method="post">
	        <table>
            <tr><td><input class="crb-monospace" type="text" name="add_acl" required maxlength="100" placeholder="' . __( 'IP address, range, wildcard, or CIDR', 'wp-cerber' ) . '"> 
            </td><td><input type="submit" class="button button-primary" value="' . __( 'Add Entry', 'wp-cerber' ) . '" ></td></tr>
            <tr><td><input class="crb-monospace" type="text" name="add_acl_comment" maxlength="250" placeholder="' . __( 'Optional comment for this entry', 'wp-cerber' ) . '"> 
            </td><td></td></tr>
            </table>
            <input type="hidden" name="cerber_admin_do" value="add2acl">
	        <input type="hidden" name="acl_tag" value="' . $tag . '">'
	             . cerber_nonce_field()
	             . '</form></div>';

	return $html_view;
}

/*
	Handle actions with items in ACLs in the dashboard
*/
function crb_acl_form_process( $post = array() ) {
	$tag = crb_array_get_validated( $post, 'acl_tag', '', 'W|B' );
	$ip = trim( crb_array_get( $post, 'add_acl' ) );
	$ip = preg_replace( CRB_IP_NET_RANGE, ' ', $ip );
	$ip = preg_replace( '/\s+/', ' ', $ip );
	$comment = strip_tags( stripslashes( crb_array_get( $post, 'add_acl_comment', '' ) ) );

	if ( $tag == 'B' ) {
		if ( ! cerber_acl_can_be_blocked( $ip ) ) {
			cerber_admin_notice( __( "You cannot add your IP address or network", 'wp-cerber' ) );

			return;
		}
		/* translators: %s is the IP address. */
		$ok = sprintf( __( 'IP address %s has been added to the Blocked IP Access List', 'wp-cerber' ), $ip );
	}
	else {
		/* translators: %s is the IP address. */
		$ok = sprintf( __( 'IP address %s has been added to the Allowed IP Access List', 'wp-cerber' ), $ip );
	}

	$result = cerber_acl_add( $ip, $tag, $comment );

	if ( crb_is_wp_error( $result ) ) {
		crb_admin_error_notice( $result );
	}
	else {
		cerber_admin_message( $ok );
	}

	return;

}


