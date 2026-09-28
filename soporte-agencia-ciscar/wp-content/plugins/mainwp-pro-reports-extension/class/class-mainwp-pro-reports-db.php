<?php

class MainWP_Pro_Reports_DB {

	private $db_version = '3.9.2';
	private $table_prefix;
	// Singleton
	private static $instance = null;
	private $wpdb;

	public $default_tokens = array();

	// Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
		$this->init_default_data();
		$this->wpdb = &$wpdb;
	}

	function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	// Support old & new versions of WordPress (3.9+)
	public static function use_mysqli() {
		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}

		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	// Installs new DB
	function install() {
		global $wpdb;

		$currentVersion = get_option( 'mainwp_pro_reports_db_version' );

		$hard_check = isset( $_GET['hardCheck'] ) && $_GET['hardCheck'] == 'yes' ? true : false;
		if ( $hard_check && isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Mainwp-Pro-Reports-Extension' ) {
			$this->update_db_to_pro_reports();
		}

		if ( version_compare( $currentVersion, $this->db_version, '=' ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$rslt = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'pro_reports_token' ) . "'" );

		$table_existed = ! empty( $rslt ) ? true : false;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'pro_reports_token' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`token_name` varchar(512) NOT NULL DEFAULT "",
`token_description` text NOT NULL,
`type` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion || ! $table_existed ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}

		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$rslt          = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'pro_reports_site_token' ) . "'" );
		$table_existed = ! empty( $rslt ) ? true : false;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'pro_reports_site_token' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`token_id` int(11) NOT NULL,
`token_value` text NOT NULL';
		if ( '' == $currentVersion || ! $table_existed ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}

		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$rslt          = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'pro_reports' ) . "'" );
		$table_existed = ! empty( $rslt ) ? true : false;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'pro_reports' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`title` text NOT NULL,
`date_from` int(11) NOT NULL,
`date_to` int(11) NOT NULL,
`date_from_nextsend` int(11) NOT NULL,
`date_to_nextsend` int(11) NOT NULL,
`fname` VARCHAR(512),
`femail` VARCHAR(128),
`bcc_email` VARCHAR(128),
`send_to_email` VARCHAR(128),
`send_to_name` VARCHAR(512),
`reply_to` VARCHAR(128),
`reply_to_name` VARCHAR(512),
`attach_files` text NOT NULL,
`logo_id` int(11) NOT NULL,
`header_image_id` int(11) NOT NULL,
`lastsend` int(11) NOT NULL,
`subject` text NOT NULL,
`message` text NOT NULL,
`heading` text NOT NULL,
`intro` text NOT NULL,
`outro` text NOT NULL,
`background_color` VARCHAR(32) NOT NULL,
`text_color` VARCHAR(32) NOT NULL,
`accent_color` VARCHAR(32) NOT NULL,
`showhide_sections` text NOT NULL,
`recurring_schedule` VARCHAR(32) NOT NULL DEFAULT "",
`recurring_day` VARCHAR(10) DEFAULT NULL,
`schedule_send_email` VARCHAR(32) NOT NULL,
`schedule_bcc_me` tinyint(1) NOT NULL DEFAULT 0,
`scheduled` tinyint(1) NOT NULL DEFAULT 0,
`noticed` tinyint(1) NOT NULL DEFAULT 0,
`schedule_nextsend` int(11) NOT NULL,
`schedule_lastsend` int(11) NOT NULL,
`completed` int(11) NOT NULL,
`completed_sites` text NOT NULL,
`template` text NOT NULL DEFAULT "",
`template_email` text NOT NULL DEFAULT "",
`sites` text NOT NULL,
`groups` text NOT NULL,
`clients` text NOT NULL,
`settings` longtext NOT NULL DEFAULT "",
`custom_pdf_file_name` VARCHAR(255) NOT NULL DEFAULT "",
`retry_counter` tinyint(1) DEFAULT 0';

		if ( '' == $currentVersion || ! $table_existed ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}

		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$rslt          = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'pro_reports_content' ) . "'" );
		$table_existed = ! empty( $rslt ) ? true : false;

				$tbl = 'CREATE TABLE `' . $this->table_name( 'pro_reports_content' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`report_id` int(11) NOT NULL,
`site_id` int(11) NOT NULL,
`report_content` longtext NOT NULL,
`report_email_content` longtext NOT NULL,
`report_content_pdf` longtext NOT NULL';

		if ( '' == $currentVersion || ! $table_existed ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}

		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output.

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		// create or update default token
		foreach ( $this->default_tokens as $token_name => $token_description ) {
			$token = array(
				'type'              => 1,
				'token_name'        => $token_name,
				'token_description' => $token_description,
			);
			if ( $current = $this->get_tokens_by( 'token_name', $token_name ) ) {
				$this->update_token( $current->id, $token );
			} else {
				$this->add_token( $token );
			}
		}

		update_option( 'mainwp_pro_reports_db_version', $this->db_version );

		$this->check_update( $currentVersion );
	}

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Pro_Reports_DB();
		}
		return self::$instance;
	}

	function check_update( $check_version ) {
		global $wpdb;

		// check if update from client report extension
		if ( empty( $check_version ) ) {
			$this->update_db_to_pro_reports();
		}

		// check for update
		if ( ! empty( $check_version ) ) {

		}
	}

	function update_db_to_pro_reports() {

		global $wpdb;

		$rslt          = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'client_report_token' ) . "'" );
		$table_existed = ! empty( $rslt ) ? true : false;

		if ( ! $table_existed ) {
			return;
		}

		// update tokens and site tokens
		$token_entries = $wpdb->get_results( 'SELECT * FROM ' . $this->table_name( 'client_report_token' ) . ' WHERE 1 ' );

		foreach ( $token_entries as $entry ) {
			$new_token_entry = array(
				'token_name'        => $entry->token_name,
				'token_description' => $entry->token_description,
				'type'              => $entry->type,
			);

			$new_token_id = 0;
			if ( $current = $this->get_tokens_by( 'token_name', $entry->token_name ) ) {
				$new_token_id = $current->id;
			} elseif ( $wpdb->insert( $this->table_name( 'pro_reports_token' ), $new_token_entry ) ) {
				$new_token_id = $wpdb->insert_id;
			}

			if ( $new_token_id ) {
				$site_token_entries = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'client_report_site_token' ) . ' WHERE token_id = %d', $entry->id ) );
				foreach ( $site_token_entries as $site_token_entry ) {

					$website = apply_filters( 'mainwp_getwebsitesbyurl', $site_token_entry->site_url );

					if ( $website ) {
						$website = current( $website );
					}

					if ( is_object( $website ) ) {
						$site_id = $website->id;
						// check existed
						$current = self::get_instance()->get_tokens_by( 'id', $new_token_id, $site_id );
						if ( $current ) {
							self::get_instance()->update_token_site( $new_token_id, $site_token_entry->token_value, $site_id );
						} else {
							self::get_instance()->add_token_site( $new_token_id, $site_token_entry->token_value, $site_id );
						}
						// $new_site_token_entry = array(
						// 'site_id' => $site_id,
						// 'token_id' => $new_token_id,
						// 'token_value' => $site_token_entry->token_value
						// );
						// $wpdb->insert( $this->table_name( 'pro_reports_site_token' ), $new_site_token_entry );
					}
				}
			}
		}
	}

	public function init_default_data() {

		$this->default_tokens = array(
			'client.site.name'         => 'Displays the Site Name',
			'client.site.url'          => 'Displays the Site Url',
			'client.name'              => 'Displays the Client Name',
			'client.contact.name'      => 'Displays the Client Contact Name',
			'client.contact.address.1' => 'Displays the Client Contact Address 1',
			'client.contact.address.2' => 'Displays the Client Contact Address 2',
			'client.city'              => 'Displays the Client City',
			'client.state'             => 'Displays the Client State',
			'client.zip'               => 'Displays the Client Zip',
			'client.phone'             => 'Displays the Client Phone',
			'client.email'             => 'Displays the Client Email',
		);
	}

	public function add_token( $token ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		if ( ! empty( $token['token_name'] ) && ! empty( $token['token_description'] ) ) {
			if ( $current = $this->get_tokens_by( 'token_name', $token['token_name'] ) ) {
				return false; }
			if ( $wpdb->insert( $this->table_name( 'pro_reports_token' ), $token ) ) {
				return $this->get_tokens_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	public function update_token( $id, $token ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		if ( MainWP_Pro_Reports_Utility::ctype_digit( $id ) && ! empty( $token['token_name'] ) && ! empty( $token['token_description'] ) ) {
			if ( $wpdb->update( $this->table_name( 'pro_reports_token' ), $token, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_tokens_by( 'id', $id );
			}
		}
		return false;
	}

	public function get_tokens_by( $by = 'id', $value = null, $site_id = false ) {
		global $wpdb;

		if ( empty( $by ) || empty( $value ) ) {
			return null;
		}

		if ( 'token_name' == $by ) {
			$value = str_replace( array( '[', ']' ), '', $value );
		}

		$sql = '';

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'pro_reports_token' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'token_name' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'pro_reports_token' ) . " WHERE `token_name` = '%s' ", $value );
		}

		$token = null;

		if ( ! empty( $sql ) ) {
			$token = $wpdb->get_row( $sql );
		}

		if ( empty( $site_id ) ) {
			return $token;
		}

		if ( $token && ! empty( $site_id ) ) {
				$sql        = 'SELECT * FROM ' . $this->table_name( 'pro_reports_site_token' ) . ' WHERE site_id = ' . intval( $site_id ) . ' AND token_id = ' . $token->id;
				$site_token = $wpdb->get_row( $sql );
			if ( $site_token ) {
				$token->site_token = $site_token;
				return $token;
			} else {
				return null;
			}
		}
		return null;
	}

	public function get_tokens() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . $this->table_name( 'pro_reports_token' ) . ' WHERE 1 = 1 ORDER BY type DESC, token_name ASC' );
	}


	public function get_site_tokens( $site_id, $index = 'id' ) {
		global $wpdb;

		$qry = ' SELECT st.*, t.token_name FROM ' . $this->table_name( 'pro_reports_site_token' ) . ' st , ' . $this->table_name( 'pro_reports_token' ) . ' t ' . ' WHERE st.site_id = ' . intval( $site_id ) . ' AND st.token_id = t.id ';
		// echo $qry;
		$site_tokens = $wpdb->get_results( $qry );

		$return = array();
		if ( is_array( $site_tokens ) ) {
			foreach ( $site_tokens as $token ) {
				if ( 'id' == $index ) {
					$return[ $token->token_id ] = $token;
				} else {
					$return[ $token->token_name ] = $token;
				}
			}
		}
		// get default token value if empty
		$tokens = $this->get_tokens();
		if ( is_array( $tokens ) ) {
			foreach ( $tokens as $token ) {
				// check default tokens if it is empty
				if ( is_object( $token ) ) {
					if ( 'id' == $index ) {
						if ( 1 == $token->type && ( ! isset( $return[ $token->id ] ) || empty( $return[ $token->id ] ) ) ) {
							if ( ! isset( $return[ $token->id ] ) ) {
								$return[ $token->id ] = new stdClass();
							}
							$return[ $token->id ]->token_value = $this->_get_default_token_site( $token->token_name, $site_id );
						}
					} elseif ( $token->type == 1 && ( ! isset( $return[ $token->token_name ] ) || empty( $return[ $token->token_name ] ) ) ) {
						if ( ! isset( $return[ $token->token_name ] ) ) {
							$return[ $token->token_name ] = new stdClass();
						}
							$return[ $token->token_name ]->token_value = $this->_get_default_token_site( $token->token_name, $site_id );
					}
				}
			}
		}
		return $return;
	}

	public function _get_default_token_site( $token_name, $site_id ) {

		if ( empty( $this->default_tokens[ $token_name ] ) ) {
			return false;
		}

		global $mainWPProReportsExtensionActivator;
		$website = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( is_array( $website ) && isset( $website['id'] ) ) {
			$url_site  = $website['url'];
			$name_site = $website['name'];
		} else {
			return false;
		}

		switch ( $token_name ) {
			case 'client.site.url':
				$token_value = $url_site;
				break;
			case 'client.site.name':
				$token_value = $name_site;
				break;
			default:
				$token_value = '';
				break;
		}
		return $token_value;
	}

	public function add_token_site( $token_id, $token_value, $site_id ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $token_id ) ) {
			return false;
		}

		if ( $wpdb->insert(
			$this->table_name( 'pro_reports_site_token' ),
			array(
				'token_id'    => $token_id,
				'token_value' => $token_value,
				'site_id'     => $site_id,
			)
		) ) {
			return $this->get_tokens_by( 'id', $token_id, $site_id );
		}

		return false;
	}

	public function update_token_site( $token_id, $token_value, $site_id ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $token_id ) ) {
			return false;
		}

		$sql = 'UPDATE ' . $this->table_name( 'pro_reports_site_token' ) .
				" SET token_value = '" . $this->escape( $token_value ) . "' " .
				' WHERE token_id = ' . intval( $token_id ) .
				" AND site_id = '" . intval( $site_id ) . "'";
		if ( $wpdb->query( $sql ) ) {
			return $this->get_tokens_by( 'id', $token_id, $site_id );
		}

		return false;
	}

	public function delete_site_tokens( $token_id = null, $site_id = null ) {
		global $wpdb;
		if ( ! empty( $token_id ) ) {
			return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports_site_token' ) . ' WHERE token_id = %d ', $token_id ) );
		} elseif ( ! empty( $site_id ) ) {
			return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports_site_token' ) . ' WHERE site_id = %d ', $site_id ) );
		}
		return false;
	}

	public function delete_token_by( $by = 'id', $token_id = null ) {
		global $wpdb;
		if ( 'id' == $by ) {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports_token' ) . ' WHERE id=%d ', $token_id ) ) ) {
				$this->delete_site_tokens( $token_id );
				return true;
			}
		}
		return false;
	}

	public function update_report( $report ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id = isset( $report['id'] ) ? $report['id'] : 0;

		$report_fields = array(
			'id',
			'title',
			'date_from',
			'date_to',
			'date_from_nextsend',
			'date_to_nextsend',
			'fname',
			'femail',
			'send_to_email',
			'send_to_name',
			'bcc_email',
			'reply_to',
			'reply_to_name',
			// 'client_id',
			'logo_id',
			'header_image_id',
			'lastsend',
			'subject',
			'message',
			'heading',
			'intro',
			'outro',
			'background_color',
			'text_color',
			'accent_color',
			'showhide_sections',
			'recurring_schedule',
			'recurring_day',
			'schedule_send_email',
			'schedule_bcc_me',
			'attach_files',
			'scheduled',
			'noticed',
			'template',
			'template_email',
			'schedule_lastsend',
			'schedule_nextsend',
			'sites',
			'groups',
			'clients',
			'completed',
			'completed_sites',
			'settings',
			'custom_pdf_file_name',
		);

		$update_report = array();

		foreach ( $report as $key => $value ) {
			if ( in_array( $key, $report_fields ) ) {
				$update_report[ $key ] = $value;
			}
		}

		if ( ! empty( $id ) ) {
			$wpdb->update( $this->table_name( 'pro_reports' ), $update_report, array( 'id' => intval( $id ) ) );
		} else {
			if ( ! isset( $update_report['title'] ) || empty( $update_report['title'] ) ) {
				return false;
			}
			if ( $wpdb->insert( $this->table_name( 'pro_reports' ), $update_report ) ) {
				$id = $wpdb->insert_id;
			}
		}

		if ( $id ) {
			return $this->get_report_by( 'id', $id );
		} else {
			return false;
		}
	}

	public function update_pro_report_content( $report ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		$report_id = isset( $report['report_id'] ) ? $report['report_id'] : 0;
		$site_id   = isset( $report['site_id'] ) ? $report['site_id'] : 0;

		if ( empty( $report_id ) || empty( $site_id ) ) {
			return false;
		}

		$current = $this->get_pro_report_content( $report_id, $site_id );
		if ( $current ) {
			$wpdb->update( $this->table_name( 'pro_reports_content' ), $report, array( 'id' => intval( $current->id ) ) );
			return $this->get_pro_report_content( $report_id, $site_id );
		} else {
			return $wpdb->insert( $this->table_name( 'pro_reports_content' ), $report );
		}

		return false;
	}

	public function get_pro_report_content( $report_id, $site_id = null ) {
		global $wpdb;

		if ( empty( $report_id ) ) {
			return false;
		}

		if ( ! empty( $site_id ) ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'pro_reports_content' ) . ' WHERE `report_id` = %d AND `site_id` = %d ', $report_id, $site_id );
			return $wpdb->get_row( $sql );
		} else {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'pro_reports_content' ) . ' WHERE `report_id` = %d ', $report_id );
			return $wpdb->get_results( $sql );
		}
	}

	public function delete_generated_report_content( $report_id = null, $site_id = null ) {
		global $wpdb;
		if ( ! empty( $report_id ) && ! empty( $site_id ) ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports_content' ) . ' WHERE `report_id` = %d AND `site_id` = %d ', $report_id, $site_id );
			return $wpdb->get_row( $sql );
		} elseif ( ! empty( $report_id ) ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports_content' ) . ' WHERE `report_id` = %d ', $report_id );
			return $wpdb->get_results( $sql );
		} elseif ( ! empty( $site_id ) ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports_content' ) . ' WHERE `site_id` = %d ', $site_id );
			return $wpdb->get_results( $sql );
		}
	}


	public function get_report_by( $by = 'id', $value = null, $orderby = null, $order = null, $output = OBJECT ) {
		global $wpdb, $mainWPProReportsExtensionActivator;

		if ( empty( $by ) || ( 'all' !== $by && empty( $value ) ) ) {
			return false;
		}

		$_order_by = '';

		if ( ! empty( $orderby ) ) {
			$orderby   = 'rp.' . $orderby;
			$_order_by = ' ORDER BY ' . $orderby;
			if ( ! empty( $order ) ) {
				$_order_by .= ' ' . $order;
			}
		}

		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT rp.* FROM ' . $this->table_name( 'pro_reports' ) . ' rp WHERE `id`=%d ' . $_order_by, $value );
		} elseif ( 'site_id' == $by ) {
			$sql_all = 'SELECT * FROM ' . $this->table_name( 'pro_reports' ) . ' WHERE 1 = 1 ';

			$all_reports    = $wpdb->get_results( $sql_all );
			$sql_report_ids = array( -1 );

			foreach ( $all_reports as $report ) {
				if ( $report->sites != '' || $report->groups != '' || $report->clients != '' ) {

					$sites = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
					if ( ! is_array( $sites ) ) {
						$sites = array();
					}

					if ( in_array( $value, $sites ) ) {
						if ( ! in_array( $report->id, $sql_report_ids ) ) {
							$sql_report_ids[] = $report->id;
						}
					} elseif ( $report->groups != '' ) {
						$groups = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );
						if ( ! is_array( $groups ) ) {
							$groups = array();
						}

						$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array(), $groups );

						foreach ( $dbwebsites as $pSite ) {
							if ( $pSite->id == $value ) {
								if ( ! in_array( $report->id, $sql_report_ids ) ) {
									$sql_report_ids[] = $report->id;
								}
								break;
							}
						}
					} elseif ( $report->clients != '' ) {
						$clients = json_decode( $report->clients, true );
						if ( ! is_array( $clients ) ) {
							$clients = array();
						}

						if ( ! empty( $clients ) ) {
							$dbwebsites = MainWP_Pro_Reports_Utility::get_db_websites( array(), array(), $clients );
							if ( ! empty( $dbwebsites ) ) {
								foreach ( $dbwebsites as $pSite ) {
									if ( $pSite->id == $value ) {
										if ( ! in_array( $report->id, $sql_report_ids ) ) {
											$sql_report_ids[] = $report->id;
										}
										break; // dbwebsites.
									}
								}
							}
						}
					}
				}
			}

			if ( ! empty( $sql_report_ids ) ) {
				$sql_report_ids = implode( ',', $sql_report_ids );

				$sql = 'SELECT rp.* FROM ' . $this->table_name( 'pro_reports' ) . ' rp WHERE rp.id IN (' . $sql_report_ids . ') ' . $_order_by;

				return $wpdb->get_results( $sql, $output );
			}
			return false;

		} elseif ( 'all' == $by ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'pro_reports' ) . ' rp WHERE 1 = 1 ' . $_order_by;
			return $wpdb->get_results( $sql, $output );
		}

		if ( ! empty( $sql ) ) {
			$report = $wpdb->get_row( $sql, $output );
			if ( 'id' === $by && $report ) {
				if ( is_array( $report ) ) {
					$settings = ! empty( $report['settings'] ) ? json_decode( $report['settings'], true ) : array();
					if ( ! is_array( $settings ) ) {
						$settings = array();
					}
					$report['settings'] = $settings;
				} elseif ( is_object( $report ) ) {
					$settings = ! empty( $report->settings ) ? json_decode( $report->settings, true ) : array();
					if ( ! is_array( $settings ) ) {
						$settings = array();
					}
					$report->settings = $settings;
				}
			}
			return $report;
		}

		return false;
	}

	public function checked_if_site_have_report( $site_id ) {
		global $wpdb, $mainWPProReportsExtensionActivator;

		if ( empty( $site_id ) ) {
			return false;
		}

		$sql_all        = 'SELECT * FROM ' . $this->table_name( 'pro_reports' ) . ' WHERE 1 = 1 ';
		$all_reports    = $wpdb->get_results( $sql_all );
		$sql_report_ids = array();

		$found = false;

		if ( is_array( $all_reports ) && count( $all_reports ) > 0 ) {
			foreach ( $all_reports as $report ) {
				if ( $report->sites != '' || $report->groups != '' ) {
					$sites = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
					if ( ! is_array( $sites ) ) {
						$sites = array();
					}

					if ( in_array( $site_id, $sites ) ) {
						if ( ! in_array( $report->id, $sql_report_ids ) ) {
							$found = true;
							break;
						}
					} elseif ( $report->groups != '' ) {
						$groups = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );
						if ( ! is_array( $groups ) ) {
								$groups = array();
						}
						$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array(), $groups );
						foreach ( $dbwebsites as $pSite ) {
							if ( $pSite->id == $site_id ) {
								$found = true;
								break;
							}
						}
					} elseif ( $report->clients != '' ) {
						$clients = json_decode( $report->clients, true );
						if ( ! is_array( $clients ) ) {
							$clients = array();
						}
						if ( ! empty( $clients ) ) {
							$dbwebsites = MainWP_Pro_Reports_Utility::get_db_websites( array(), array(), $clients );
							if ( ! empty( $dbwebsites ) ) {
								foreach ( $dbwebsites as $pSite ) {
									if ( $pSite->id == $site_id ) {
										$found = true;
										break;
									}
								}
							}
						}
					}

					if ( $found ) {
						break;
					}
				}
			}
		}
		return $found;
	}


	public function updateWebsiteOption( $website_id, $option, $value ) {
		$rslt = $this->wpdb->get_results( 'SELECT name FROM ' . $this->table_name( 'wp_options' ) . ' WHERE wpid = ' . $website_id . ' AND name = "' . $this->escape( $option ) . '"' );
		if ( count( $rslt ) > 0 ) {
			$this->wpdb->delete(
				$this->table_name( 'wp_options' ),
				array(
					'wpid' => $website_id,
					'name' => $this->escape( $option ),
				)
			);
			$rslt = $this->wpdb->get_results( 'SELECT name FROM ' . $this->table_name( 'wp_options' ) . ' WHERE wpid = ' . $website_id . ' AND name = "' . $this->escape( $option ) . '"' );
		}

		if ( count( $rslt ) == 0 ) {
			$this->wpdb->insert(
				$this->table_name( 'wp_options' ),
				array(
					'wpid'  => $website_id,
					'name'  => $option,
					'value' => $value,
				)
			);
		} else {
			$this->wpdb->update(
				$this->table_name( 'wp_options' ),
				array( 'value' => $value ),
				array(
					'wpid' => $website_id,
					'name' => $option,
				)
			);
		}
	}

	public function getWebsiteOption( $website, $option ) {
		if ( property_exists( $website, $option ) ) {
			return $website->{$option};
		}

		return $this->wpdb->get_var( 'SELECT value FROM ' . $this->table_name( 'wp_options' ) . ' WHERE wpid = ' . $website->id . ' AND name = "' . $this->escape( $option ) . '"' );
	}

	public function getOptionOfWebsites( $websiteIds, $option ) {
		if ( ! is_array( $websiteIds ) || count( $websiteIds ) == 0 ) {
			return array();
		}
		return $this->wpdb->get_results( 'SELECT wpid, value FROM ' . $this->table_name( 'wp_options' ) . ' WHERE wpid IN (' . implode( ',', $websiteIds ) . ') AND name = "' . $this->escape( $option ) . '"' );
	}

	public function get_scheduled_reports_to_send( $timestamp_offset = 0 ) {
		global $wpdb;

		/*
		 * For testing, to force the schedule reports start run.
		 * Reset values: `schedule_nextsend`, `schedule_lastsend` and option 'mainwp_reports_sendcheck_last',
		 * to corresponding values ( values one day ago, for example )
		 * cron job will update 'schedule_lastsend' to current time, and 'completed_sites' to empty array().
		 *
		 */

		$sql = 'SELECT rp.* FROM ' . $this->table_name( 'pro_reports' ) . ' rp ' . " WHERE rp.recurring_schedule != '' AND rp.scheduled = 1 " . ' AND rp.schedule_nextsend < ' . ( time() + $timestamp_offset ); // to support send report at local time.
		return $wpdb->get_results( $sql );
	}


	public function get_scheduled_reports_to_continue_send() {

		global $wpdb;

		$sql = 'SELECT rp.* FROM ' . $this->table_name( 'pro_reports' ) . ' rp '
		. " WHERE rp.recurring_schedule != '' AND rp.scheduled = 1 "
		. ' AND rp.completed < rp.schedule_lastsend '
		. ' AND rp.retry_counter < 3 ' // try to send three time.
		. ' ORDER BY rp.lastsend ASC '
		. ' LIMIT 1';

		return $wpdb->get_results( $sql );
	}

	public function get_scheduled_reports_ready_to_notice( $limit = 5, $timestamp_offset = 0 ) {
		global $wpdb;
		$sql = 'SELECT rp.* FROM ' . $this->table_name( 'pro_reports' ) . ' rp ' . " WHERE rp.recurring_schedule != '' AND rp.scheduled = 1 AND rp.noticed = 0 " . ' AND rp.schedule_nextsend < ' . ( time() - 24 * 60 * 60 + $timestamp_offset ) . ' LIMIT ' . intval( $limit );
		return $wpdb->get_results( $sql );
	}

	public function update_reports_with_values( $id, $values ) {
		if ( ! is_array( $values ) ) {
			return false;
		}

		global $wpdb;
		return $wpdb->update( $this->table_name( 'pro_reports' ), $values, array( 'id' => $id ) );
	}


	public function update_reports_send( $id ) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name( 'pro_reports' ),
			array(
				'schedule_lastsend' => time(), // to check for contiunue sending only
				'completed_sites'   => json_encode( array() ),
				'retry_counter'     => 0,
			),
			array( 'id' => $id )
		);
		return false;
	}

	public function update_completed_report( $id ) {
		global $wpdb;
		return $wpdb->update( $this->table_name( 'pro_reports' ), array( 'completed' => time() ), array( 'id' => $id ) );
	}

	public function update_reports_completed_websites( $id, $pCompletedSites = array() ) {
		global $wpdb;
		return $wpdb->update( $this->table_name( 'pro_reports' ), array( 'completed_sites' => json_encode( $pCompletedSites ) ), array( 'id' => $id ) );
	}

	public function get_completed_websites( $id ) {
		global $wpdb;

		if ( empty( $id ) ) {
			return array();
		}

		$qry = 'SELECT completed_sites FROM ' . $this->table_name( 'pro_reports' ) . ' WHERE id = ' . intval( $id );

		$com_sites = $wpdb->get_var( $qry );

		if ( $com_sites != '' ) {
			$com_sites = json_decode( $com_sites, true );
		}
		if ( ! is_array( $com_sites ) ) {
			$com_sites = array();
		}
		return $com_sites;
	}

	public function delete_report_by( $by = 'id', $report_id = null ) {
		global $wpdb;
		if ( 'id' == $by ) {
			if ( $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'pro_reports' ) . ' WHERE id=%d ', $report_id ) ) ) {
				$this->delete_generated_report_content( $report_id );
				return true;
			}
		}
		return false;
	}

	protected function escape( $data ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data );
		}
	}

	public function query( $sql ) {
		if ( null == $sql ) {
			return false; }
		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
			return false;
		}
		return $result;
	}

	public static function _query( $query, $link ) {
		if ( self::use_mysqli() ) {
			return mysqli_query( $link, $query );
		} else {
			return mysql_query( $query, $link );
		}
	}

	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {
		if ( self::use_mysqli() ) {
			return mysqli_data_seek( $result, $offset );
		} else {
			return mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
		}
	}

	public static function num_rows( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}

	public function get_results_result( $sql ) {
		if ( null == $sql ) {
			return null;
		}
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_results( $sql, OBJECT_K );
	}
}
