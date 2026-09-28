<?php

class CRB_Sessions_Table extends WP_List_Table {
	private string $base_admin;
	private bool $is_geo_shown;

	public function __construct() {
		parent::__construct( array(
			'singular' => 'Session',
			'plural'   => 'Sessions',
			'ajax'     => false,
			'screen'   => 'cerber_user_sessions' // Without this it does not work
		) );

		$this->is_geo_shown = (bool) lab_lab();
		$this->base_admin = wp_nonce_url( cerber_admin_link( 'sessions' ), 'control', 'cerber_nonce' );
	}

	// Columns definition
	function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />', //Render a checkbox instead of text
			'ses_user'    => __( 'User', 'wp-cerber' ),
			//'ses_role'    => __( 'Role', 'wp-cerber' ),
			'ses_started' => __( 'Created', 'wp-cerber' ),
			'ses_expires' => __( 'Expires', 'wp-cerber' ),
			'ses_ip'      => '<div class="crb_act_icon"></div>' . __( 'IP Address', 'wp-cerber' ),
			'ses_host'    => __( 'Host Info', 'wp-cerber' ),
			'ses_action'  => __( 'Action', 'wp-cerber' ),
		);
	}

	// Sortable columns
	function get_sortable_columns() {
		return array(
			'ses_user' => array( 'user_id', false ), // true means dataset is already sorted by ASC
			'ses_started' => array( 'started', false ),
			'ses_expires' => array( 'expires', false ),
			'ses_ip' => array( 'ip', false ),
		);
	}

	// Bulk actions
	function get_bulk_actions() {
		return array(
			'bulk_session_terminate' => __( 'Terminate session', 'wp-cerber' ),
			'bulk_block_user'        => __( 'Block user', 'wp-cerber' ),
		);
	}

	protected function extra_tablenav( $which ) {

		if ( $which == 'top' ) {

			?>
			<div class="alignleft actions">
				<?php

				$filter = '';
				$uname = '';

				if ( $user_id = crb_get_query_params( 'filter_user', '\d+' ) ) {
					if ( $u = crb_get_userdata( $user_id ) ) {
						$uname = crb_format_user_name( $u );
					}
					else {
						$user_id = 0;
					}
				}

				$filter .= cerber_select( 'filter_user', ( $user_id ) ? array( $user_id => $uname ) : array(), $user_id, 'crb-select2-ajax', '', false, esc_html__( 'Filter by registered user', 'wp-cerber' ), array( 'min_symbols' => 3 ) );

				$search_ip = esc_attr( stripslashes( crb_get_query_params( 'search_ip' ) ) );
				$filter .= '<input type="text" value="' . $search_ip . '" name="search_ip" placeholder="' . esc_html__( 'Search for IP address', 'wp-cerber' ) . '">';

				echo '<div id="crb-top-filter">' . $filter . '<input type="submit" value="Filter" class="button button-primary action"></div>';

				?>

			</div>
			<?php
		}
	}

	function prepare_items() {
		global $wpdb;

		if ( ! crb_sessions_get_num() ) {
			crb_sessions_sync_all();
		}
		else {
			crb_sessions_delete_expired();
		}

		$where = array();
		$total_items = 0;
		$get = crb_get_query_params();

		// Sorting
		$orderby = crb_array_get_validated( $get, 'orderby', 'started', '\w+' );
		$order   = crb_array_get_validated( $get, 'order', 'DESC', '\w+' );
		$orderby = sanitize_sql_orderby( $orderby . ' ' . $order ); // !works only with fields, not tables references!
		$orderby = ' ORDER BY ' . $orderby . ' ';

		// Pagination, part 1, SQL
		$per_page = crb_admin_get_per_page();
		$current_page = $this->get_pagenum();
		if ( $current_page > 1 ) {
			$offset = ( $current_page - 1 ) * $per_page;
			$limit  = ' LIMIT ' . $offset . ',' . $per_page;
		}
		else {
			$limit = 'LIMIT ' . $per_page;
		}

		// Search

		if ( $user_id = crb_array_get_validated( $get, 'filter_user', 0, '\d+' ) ) {
			$where[] = 'user_id = ' . $user_id;
		}

		if ( $ip = stripslashes( crb_array_get( $get, 'search_ip' ) ) ) {
			$where[] = 'ip LIKE "%' . preg_replace( '/[^:.\d]/', '', $ip ) . '%"';
		}

		$where = ( ! empty( $where ) ) ? ' WHERE ' . implode( ' AND ', $where ) : '';

		// Retrieving data

		$query = 'SELECT SQL_CALC_FOUND_ROWS * FROM ' . cerber_get_db_prefix() . CERBER_USS_TABLE . ' ses JOIN ' . $wpdb->users . ' us ON (ses.user_id = us.ID) ' . $where . $orderby . $limit;

		if ( $this->items = cerber_db_get_results( $query ) ) {
			$total_items = cerber_db_get_var( 'SELECT FOUND_ROWS()' );
		}

		if ( ! empty( $term ) ) {
			echo '<div style="margin-top:15px;"><b>' . __( 'Search results for:', 'wp-cerber' ) . '</b> “' . crb_escape_html( $term ) . '”</div>';
		}

		// Pagination, part 2
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );

	}

	public function single_row( $item ) {
		//$item['user_data'] = crb_get_userdata( $item['user_id'] );
		parent::single_row( $item );
	}

	function column_cb( $item ) {
		if ( ! crb_admin_is_current_session( $item['wp_session_token'] ) ) {
			return '<input type="checkbox" name="ids[]" value="' . $item['wp_session_token'] . '" />';
		}

		return '';
	}

	/**
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {

		//return $item[ $column_name ]; // raw output as is
		switch ( $column_name ) {
			case 'ses_user':

				$label = '';
				$links = '';

				if ( ! nexus_is_valid_request() ) {
					$links = $this->row_actions( array(
						'<a href="' . get_edit_user_link( $item['user_id'] ) . '">' . __( 'Profile', 'wp-cerber' ) . '</a>',
					) );

					$label = ( crb_admin_is_current_session( $item['wp_session_token'] ) ) ? __( 'You', 'wp-cerber' ) : '';
				}

				return crb_admin_get_user_cell( $item['user_id'], cerber_admin_link( 'sessions' ), $links, $label );

				break;
			case 'ses_started':

				$logins = cerber_activity_link( array( CRB_EV_LIN ) ) . '&amp;filter_user=' . $item['user_id'];
				$set  = array(
					'<a href="' . $logins . '">' . __( 'All Logins', 'wp-cerber' ) . '</a>',
					'<a href="' . cerber_admin_link( 'activity' ) . '&amp;filter_user=' . $item['user_id'] . '">' . __( 'User Activity', 'wp-cerber' ) . '</a>'
				);

				$url = '';
				// TODO: make it via AJAX per user row with a click "Details"
				if ( $item['session_id'] ) {
					/*$log = cerber_db_get_row( 'SELECT * FROM ' . CERBER_LOG_TABLE . ' WHERE session_id = "' . $item['session_id'] . '"' );
					if ( $log ) {
						$det = explode( '|', $log['details'] );
						$url = $det[4];
					}*/
				}

				$label = '';

				if ( $ms = $item['mfa_status'] ) {

					$label_class = 'crb-label crb-label-green';
					$more = '';

					if ( $ms == 1 ) {
						$label = '2FA';
						$label_class .= ' crb-label-outline';
						$more = 'Awaiting 2FA completion';
					}
					elseif ( $ms == 2 ) {
						$label = '2FA';
						$more = '2FA successfully completed';
					}

					$label = ( $label ) ? '<span class="' . $label_class . '" title="' . $more . '">' . $label . '</span>' : '';
				}

				return '<span title="' . $url . '">' . cerber_date( $item['started'] ) . '</span>' . $label . $this->row_actions( $set );

				break;
			case 'ses_expires':
				return cerber_date( $item['expires'] );
				break;
			case 'ses_ip':

				$set = array(
					'<a href="' . cerber_admin_link( 'activity' ) . '&amp;&filter_ip=' . $item['ip'] . '">' . __( 'Activity', 'wp-cerber' ) . '</a>',
					'<a href="' . cerber_admin_link( 'traffic' ) . '&amp;&filter_ip=' . $item['ip'] . '">' . __( 'Traffic', 'wp-cerber' ) . '</a>'
				);

				return crb_admin_ip_cell( $item['ip'], '', $this->row_actions( $set ) );

				break;
			case 'ses_host':
				$ip_id   = cerber_get_id_ip( $item['ip'] );
				$ip_info = cerber_get_ip_info( $item['ip'] );
				if ( ! $hostname = crb_array_get( $ip_info, 'hostname_html' ) ) {
					$hostname = crb_get_ajax_placeholder( 'hostname', $ip_id );
				}
				$country = ( $this->is_geo_shown ) ? '<p style="">' . crb_country_html( $item['country'], $item['ip'] ) . '</p>' : '';

				return $hostname . $country;
				break;
			case 'ses_action':
				if ( crb_admin_is_current_session( $item['wp_session_token'] ) ) {
					return '';
				}

				$terminate_link = $this->base_admin . '&cerber_admin_do=terminate_session&id=' . $item['wp_session_token'] . '&user_id=' . $item['user_id'];

				return crb_confirmation_link( $terminate_link, __( 'Terminate', 'wp-cerber' ) );

				break;
		}

		return '';
	}

	function no_items() {
		if ( ! empty( $_GET['s'] ) ) {
			parent::no_items();
		}
		else {
			echo 'No user sessions found.';
		}
	}
}
