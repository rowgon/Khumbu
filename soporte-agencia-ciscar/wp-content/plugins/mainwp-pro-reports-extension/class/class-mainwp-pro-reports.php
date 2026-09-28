<?php

class MainWP_Pro_Reports {

	private static $buffer           = array();
	private static $count_sec_body   = 0;
	private static $count_sec_header = 0;

	public $update_version = '1.0';

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	public function admin_init() {
		if ( isset( $_GET['page'] ) && 'Extensions-Mainwp-Pro-Reports-Extension' === $_GET['page'] && isset( $_GET['pro_reports_action_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['pro_reports_action_nonce'], 'pro_reports_action_nonce' ) ) {
				wp_die( esc_html__( 'Invalid nonce!', 'mainwp-pro-reports-extension' ) );
			}
		}
	}

	public static function managesite_schedule_backup( $website, $args, $backupResult ) {

		if ( empty( $website ) ) {
			return;
		}

		$type = isset( $args['type'] ) ? $args['type'] : '';
		if ( empty( $type ) ) {
			return;
		}

		$destination = '';
		if ( is_array( $backupResult ) ) {
			$error = false;
			if ( isset( $backupResult['error'] ) ) {
				$destination .= $backupResult['error'] . '<br />';
				$error        = true;
			}

			if ( isset( $backupResult['ftp'] ) ) {
				if ( 'success' != $backupResult['ftp'] ) {
					$destination .= 'FTP: ' . $backupResult['ftp'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'FTP: success<br />';
				}
			}

			if ( isset( $backupResult['dropbox'] ) ) {
				if ( 'success' != $backupResult['dropbox'] ) {
					$destination .= 'Dropbox: ' . $backupResult['dropbox'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'Dropbox: success<br />';
				}
			}
			if ( isset( $backupResult['amazon'] ) ) {
				if ( 'success' != $backupResult['amazon'] ) {
					$destination .= 'Amazon: ' . $backupResult['amazon'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'Amazon: success<br />';
				}
			}

			if ( isset( $backupResult['copy'] ) ) {
				if ( 'success' != $backupResult['copy'] ) {
					$destination .= 'Copy.com: ' . $backupResult['amazon'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'Copy.com: success<br />';
				}
			}

			if ( empty( $destination ) ) {
				$destination = 'Local Server';
			}
		} else {
			$destination = $backupResult;
		}

		if ( 'full' == $type ) {
			$message     = 'Schedule full backup.';
			$backup_type = 'Full';
		} else {
			$message     = 'Schedule database backup.';
			$backup_type = 'Database';
		}

		global $mainWPProReportsExtensionActivator;

		// save results to child site stream
		$post_data = array(
			'mwp_action'  => 'save_backup_stream',
			'size'        => 'N/A',
			'message'     => $message,
			'destination' => $destination,
			'status'      => 'N/A',
			'type'        => $backup_type,
		);
		apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $website->id, 'client_report', $post_data );
	}

	function mainwp_postprocess_backup_sites_feedback( $output, $unique ) {
		if ( ! is_array( $output ) ) {

		} else {
			foreach ( $output as $key => $value ) {
				$output[ $key ] = $value;
			}
		}

		return $output;
	}

	/**
	 * Get Time Stamp from $hh_mm.
	 *
	 * @param mixed $hh_mm Global time stamp variable.
	 *
	 * @return time Y-m-d 00:00:59.
	 */
	public static function get_timestamp_from_hh_mm( $hh_mm ) {
		$hh_mm = explode( ':', $hh_mm );
		$_hour = isset( $hh_mm[0] ) ? intval( $hh_mm[0] ) : 0;
		$_mins = isset( $hh_mm[1] ) ? intval( $hh_mm[1] ) : 0;
		if ( $_hour < 0 || $_hour > 23 ) {
			$_hour = 0;
		}
		if ( $_mins < 0 || $_mins > 59 ) {
			$_mins = 0;
		}
		return strtotime( date( 'Y-m-d' ) . ' ' . $_hour . ':' . $_mins . ':59' );
	}


	public static function has_tokens( $value ) {
		if ( empty( $value ) ) {
			return false;
		}
		return preg_match( '/\[[^\]]+\]/is', $value );
	}


	public static function find_and_replace_email_tokens( $email_token, $site_id, $tokens_values = array() ) {

		if ( ! self::has_tokens( $email_token ) ) {
			return $email_token;
		}

		// find tokens in send to email to get token value
		if ( preg_match_all( '/\[[^\]]+\]/is', $email_token, $matches ) ) {
			$email_tokens = $matches[0];
			foreach ( $email_tokens as $tk ) {
				$token_val = '';
				$token     = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'token_name', $tk, $site_id );
				if ( is_object( $token ) && property_exists( $token, 'site_token' ) ) {
					$token_val = $token->site_token->token_value;
					$token_val = trim( $token_val );
				}
				if ( ! empty( $token_val ) ) {
					$tokens_values[ $tk ] = $token_val;
				}
			}
		}

		$email_token = self::replace_site_tokens( $email_token, $tokens_values );
		$email_token = preg_replace( '/\[[^\]]+\]/is', '', $email_token ); // to remove other tokens

		$items  = explode( ',', $email_token );
		$items  = array_filter(
			$items,
			function ( $value ) {
				$value = trim( $value );
				return ! empty( $value );
			}
		); // remove empty values
		$emails = implode( ',', $items );
		return $emails;
	}


	public function prepare_content_report_email( $report, $send_what = 'send', $site = null, $generated_content = true ) {

		if ( ! is_object( $report ) ) {
			return false;
		}

		if ( empty( $site ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$site_id = $site['id'];

		// generate report content and report data here, if needed.
		if ( ! $generated_content ) {
			$this->generate_report_content_and_data_for_website( $report, $site );
		}
		$generate_for = $send_what;
		// get the generated report data.
		$result = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_content( $report->id, $site_id, 0, 0, $generate_for );

		$send_content = '';
		$templ_email  = '';
		if ( $result ) {
			if ( ! empty( $result->report_content ) ) {
				$send_content = $result->report_content;
			}

			if ( ! empty( $result->report_email_content ) ) {
				$templ_email = $result->report_email_content;
			}
		}

		// report content empty so return
		if ( empty( $send_content ) ) {
			MainWP_Pro_Reports_Utility::log_debug( 'ERROR :: Send report :: content empty.' );
			return false;
		} else {
			MainWP_Pro_Reports_Utility::log_debug( 'SUCCESS :: generate report content.' );
		}

		// templ email empty so return
		if ( empty( $templ_email ) ) {
			MainWP_Pro_Reports_Utility::log_debug( 'ERROR :: Send report :: email content empty.' );
			return false;
		}

		$sendto_email = '';
		$subject      = '';
		$bcc_email_me = '';

		$noti_email = @apply_filters( 'mainwp_getnotificationemail', false );

		$send_to_me_review = false;
		if ( 'send_test' === $send_what ) {
			if ( ! empty( $noti_email ) ) {
				$sendto_email = $noti_email;
				$subject      = 'Send Test Email';
			}
		} elseif ( ! empty( $report->scheduled ) ) {
			if ( $report->schedule_send_email == 'email_auto' ) {
				$sendto_email = ! empty( $report->send_to_email ) ? $report->send_to_email : '';
				if ( $report->schedule_bcc_me ) {
					$bcc_email_me = $noti_email;
				}
			} elseif ( $report->schedule_send_email == 'email_review' && ! empty( $noti_email ) ) {
				$sendto_email      = $noti_email;
				$subject           = 'Review report';
				$send_to_me_review = true;
			}
		} else {
			$sendto_email = $report->send_to_email;
		}

		// send to email empty so return
		if ( empty( $sendto_email ) ) {
			MainWP_Pro_Reports_Utility::log_debug( 'ERROR :: Send report :: email empty.' );
			return false;
		}

		$email_subject = '';
		if ( ! empty( $subject ) ) {
			$email_subject = $subject;
		}

		$email_subject = isset( $report->subject ) && ! empty( $report->subject ) ? $email_subject . ' - ' . $report->subject : $email_subject . ' - ' . 'Website Report';
		$email_subject = ltrim( $email_subject, ' - ' );

		$email_message = $report->message;
		$from_name     = $report->fname;
		$reply_to_name = $report->reply_to_name;

		$tokens_values = array();

		// $templ_email = MainWP_Pro_Reports_Template::get_instance()->get_template_email_file_content( $report, $email_message );

		// find and replace tokens values
		if ( self::has_tokens( $email_subject )
			|| self::has_tokens( $from_name )
			|| self::has_tokens( $templ_email )
			|| self::has_tokens( $reply_to_name )
			|| self::has_tokens( $sendto_email )
		) {

			$tokens_values = self::get_tokens_of_site( $report, $site_id );

			/**
			* Filters for custom values of site tokens before generate the report content
			*
			* @since 4.0.1
			*
			* @param array $tokens_values The array of tokens
			* @param object $report        The report.
			* @param string $website       The website.
			*/
			$tokens_values = apply_filters( 'mainwp_pro_reports_custom_tokens', $tokens_values, $report, $site, $templ_email );

			if ( self::has_tokens( $email_subject ) ) {
				$email_subject = self::replace_site_tokens( $email_subject, $tokens_values );
			}

			if ( self::has_tokens( $from_name ) ) {
				$from_name = self::replace_site_tokens( $from_name, $tokens_values );
			}

			if ( self::has_tokens( $templ_email ) ) {
				$templ_email = self::replace_site_tokens( $templ_email, $tokens_values );
			}

			if ( self::has_tokens( $reply_to_name ) ) {
				$reply_to_name = self::replace_site_tokens( $reply_to_name, $tokens_values );
			}
		}

		// set header values
		$header = array( 'content-type: text/html' );

		$from_email = '';
		if ( ! empty( $report->femail ) ) {
			$from_email = self::find_and_replace_email_tokens( $report->femail, $site_id, $tokens_values );
			$from_email = ' ' . '<' . $from_email . '>';
		}

		$header[] = 'From: ' . $from_name . $from_email;

		if ( ! empty( $report->bcc_email ) ) {
			$bcc_email = self::find_and_replace_email_tokens( $report->bcc_email, $site_id, $tokens_values );
			$header[]  = 'Bcc: ' . $bcc_email;
		}

		if ( ! empty( $bcc_email_me ) ) {
			// do not replace tokens in bcc me email
			$header[] = 'Bcc: ' . $bcc_email_me;
		}

		if ( ! empty( $report->reply_to ) ) {
			$header[] = 'Reply-To: ' . $reply_to_name . '<' . $report->reply_to . '>';
		}

		$to_emails = $sendto_email;

		if ( ! $send_to_me_review ) {
			$to_emails = self::find_and_replace_email_tokens( $to_emails, $site_id, $tokens_values );
		}

		if ( empty( $to_emails ) ) {
			throw new \Exception( 'No valid email address found for the client.' );
		}

		$files       = $report->attach_files;
		$attachments = array();

		if ( ! empty( $files ) ) {
			$creport_dir = MainWP_Pro_Reports_Template::get_instance()->get_mainwp_sub_dir( 'report-attached' );
			$files       = explode( ',', $files );
			foreach ( $files as $file ) {
				$file          = trim( $file );
				$attachments[] = $creport_dir . $file;
			}
		}

		if ( ! empty( $to_emails ) ) {

			MainWP_Pro_Reports_Utility::log_info( 'Sending report to : ' . $to_emails . ' :: From: ' . $from_name );

			$data = array(
				'header'      => $header,
				'to_email'    => $to_emails,
				'subject'     => $email_subject,
				'content'     => $send_content,
				'attachments' => $attachments,
			);

			$data = apply_filters( 'mainwp_pro_reports_send_mail_data', $data );

			$html_to_pdf = $data['content'];

			$data['attachments'] = apply_filters( 'mainwp_pro_reports_email_attachments', $data['attachments'], $html_to_pdf, $report, $site_id );

			$data['templ_email'] = $templ_email;
			return $data;
		}

		return false;
	}

	/**
	 * Get other tokens value.
	 *
	 * @param array $tokens_values Section matches.
	 */
	public static function get_report_other_tokens_values( &$tokens_values, $site_id ) {
		if ( $site_id ) {
			$option = array(
				'plugins' => true,
				'themes'  => true,
			);
			global $mainWPProReportsExtensionActivator;
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array( $site_id ), array(), $option );
			if ( $dbwebsites ) {
				$website = current( $dbwebsites );

				if ( property_exists( $website, 'plugins' ) ) {
					$installed_plugins = $website->plugins;
					$installed_plugins = json_decode( $installed_plugins, true );
					$str               = '';
					$items             = array();
					if ( ! empty( $installed_plugins ) ) {
						foreach ( $installed_plugins as $item ) {
							$str    .= '<p style="margin: 2px 0px 2px 0px">' . $item['name'] . '-' . $item['version'] . '</p>';
							$items[] = array(
								'name'    => $item['name'],
								'version' => $item['version'],
							);
						}
					}

					if ( self::is_rest_api_request() ) {
						$tokens_values['[installed.plugins.items]'] = $items;
					} else {
						$tokens_values['[installed.plugins]'] = $str;
					}
				}

				if ( property_exists( $website, 'themes' ) ) {
					$installed_themes = $website->themes;
					$installed_themes = json_decode( $installed_themes, true );
					$str              = '';
					$items            = array();
					if ( ! empty( $installed_themes ) ) {
						foreach ( $installed_themes as $item ) {
							$str    .= '<p style="margin: 2px 0px 2px 0px">' . $item['name'] . ' - ' . $item['version'] . '</p>';
							$items[] = array(
								'name'    => $item['name'],
								'version' => $item['version'],
							);
						}
					}
					if ( self::is_rest_api_request() ) {
						$tokens_values['[installed.themes.items]'] = $items;
					} else {
						$tokens_values['[installed.themes]'] = $str;
					}
				}
			}
		}
	}

	public static function is_rest_api_request() {
		if ( defined( 'MAINWP_REST_API' ) && MAINWP_REST_API ) {
			return true;
		}
		return false;
	}

	/**
	 * Method get_tokens_of_site().
	 *
	 * @param object $report The report.
	 * @param int    $site_id The id of site
	 *
	 * @return array Site's tokens.
	 */
	public static function get_tokens_of_site( $report, $site_id ) {
		// get tokens of the site
		$sites_token = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $site_id, 'token_name' );
		if ( ! is_array( $sites_token ) ) {
			$sites_token = array();
		}
		$now           = time();
		$tokens_values = array();

		if ( $report && is_object( $report ) ) {
			if ( self::is_rest_api_request() ) {
				$tokens_values['[report.from.date]'] = ! empty( $report->date_from ) ? date( 'Y-m-d H:i:s', $report->date_from ) : '';
				$tokens_values['[report.to.date]']   = ! empty( $report->date_to ) ? date( 'Y-m-d H:i:s', $report->date_to ) : '';
			} else {
				$tokens_values['[report.daterange]'] = MainWP_Pro_Reports_Utility::format_datestamp( $report->date_from ) . ' - ' . MainWP_Pro_Reports_Utility::format_datestamp( $report->date_to );
				$tokens_values['[report.send.date]'] = MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $now ) );
			}
		}

		foreach ( $sites_token as $token_name => $token ) {
			$tokens_values[ '[' . $token_name . ']' ] = $token->token_value;
		}

		// to support new mainwp clients.
		$mainwp_client_tokens = apply_filters( 'mainwp_clients_get_website_client_tokens', false, $site_id );

		if ( is_array( $mainwp_client_tokens ) && count( $mainwp_client_tokens ) > 0 ) {
			foreach ( $mainwp_client_tokens as $tok_name => $tok_value ) {
				if ( ! isset( $tokens_values[ '[' . $tok_name . ']' ] ) || empty( $tokens_values[ '[' . $tok_name . ']' ] ) ) {
					$tokens_values[ '[' . $tok_name . ']' ] = $tok_value; // use tokens of mainwp client.
				}
			}
		}

		return $tokens_values;
	}

	/**
	 * Method replace_site_tokens().
	 *
	 * @param int       $string input value.
	 * @param int array $replace_tokens array of tokens.
	 *
	 * @return array Site's array of tokens.
	 */
	public static function replace_site_tokens( $string, $replace_tokens ) {
		$tokens = array_keys( $replace_tokens );
		$values = array_values( $replace_tokens );
		return str_replace( $tokens, $values, $string );
	}


	/*
	* update the completed sites for scheduled report.
	*/
	public static function update_completed_websites( $report, $pCompletedSites, $all_siteids ) {
		$total_sites = count( $all_siteids );
		if ( $report->scheduled ) {
			MainWP_Pro_Reports_DB::get_instance()->update_reports_completed_websites( $report->id, $pCompletedSites );
			// Update completed sites.
			if ( $total_sites > 0 && count( $pCompletedSites ) >= $total_sites ) {
				// check to resend failed reports.
				$filteredCompletedSites = array_filter(
					$pCompletedSites,
					function ( $val ) {
						return ( $val > 0 && $val != 5 );  // successed or generated failed.
					}
				);
				$retried                = $report->retry_counter;
				if ( count( $filteredCompletedSites ) >= $total_sites || ( $retried >= 3 ) ) {
					MainWP_Pro_Reports_Utility::log_info( 'Schedule reports :: completed sites :: ' . count( $filteredCompletedSites ) );
					// update to finish sending this report.
					MainWP_Pro_Reports_DB::get_instance()->update_completed_report( $report->id );
				} else {
					$values = array(
						'retry_counter' => $retried + 1, // increase process counter to re-try to send.
					);
					MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
					MainWP_Pro_Reports_DB::get_instance()->update_reports_completed_websites( $report->id, $filteredCompletedSites ); // update so countinue to re-send failed reports.
				}
			}
		}
	}

	public static function set_init_params() {
		@ignore_user_abort( true );
		$timeout = 10 * 60 * 60;
		@set_time_limit( $timeout );
		$mem = '1024M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );
	}



	public static function generate_report_filtered_content( $report, $which = 'body' ) {
		$html = '';
		if ( is_array( $report ) && isset( $report['error'] ) ) {
			$html = 'Error reporting data';
		} elseif ( is_object( $report ) ) {
			$convert_nl2br = apply_filters( 'mainwp_client_reports_newline_break', false );
			if ( 'body' === $which ) {
				if ( $convert_nl2br ) {
					$html = stripslashes( nl2br( $report->filtered_body ) );
				} else {
					$html = stripslashes( $report->filtered_body );
				}
			} elseif ( 'email' === $which ) {
				if ( $convert_nl2br ) {
					$html = stripslashes( nl2br( $report->filtered_email ) );
				} else {
					$html = stripslashes( $report->filtered_email );
				}
			}
		}
		return $html;
	}

	public static function get_addition_tokens( $site_id ) {
		$tokens_value = array();
		$site_info    = apply_filters( 'mainwp_getwebsiteoptions', false, $site_id, 'site_info' );
		if ( $site_info ) {
			$site_info = json_decode( $site_info, true );
			if ( is_array( $site_info ) ) {
				$map_site_tokens = array(
					'client.site.version' => 'wpversion',   // Displays the WP version of the child site,
					'client.site.theme'   => 'themeactivated', // Displays the currently active theme for the child site
					'client.site.php'     => 'phpversion', // Displays the PHP version of the child site
					'client.site.mysql'   => 'mysql_version', // Displays the MySQL version of the child site
					'client.site.dbsize'  => 'db_size',
				);
				foreach ( $map_site_tokens as $tok => $val ) {
					$tokens_value[ $tok ] = ( is_array( $site_info ) && isset( $site_info[ $val ] ) ) ? $site_info[ $val ] : '';
				}
			}
		}
		$get_issues   = apply_filters( 'mainwp_getwebsiteoptions', false, $site_id, 'health_site_status' );
		$issue_counts = json_decode( $get_issues, true );
		$issues_total = 0;
		if ( is_array( $issue_counts ) ) {
			if ( isset( $issue_counts['recommended'] ) ) {
				$issues_total += intval( $issue_counts['recommended'] );
			}
			if ( isset( $issue_counts['critical'] ) ) {
				$issues_total += intval( $issue_counts['critical'] );
			}
		}
		$tokens_value['site.health.score'] = intval( $issues_total );
		return $tokens_value;
	}

	public static function get_pattern_normal_section() {
		return '/(\[section\.[^\]]+\])(.*?)(\[\/section\.[^\]]+\])/is';
	}

	public function filter_report_content( $templ_content, $report, $website, $cust_from_date = 0, $cust_to_date = 0, $data_type = '', &$output = array() ) {

		$templ_content = apply_filters( 'mainwp_pro_reports_filter_report_content', $templ_content, $report, $website, $cust_from_date, $cust_to_date, $data_type );

		$rep_from_date = 0;
		$rep_to_date   = 0;

		$report_id       = 0;
		$logo_id         = 0;
		$header_image_id = 0;
		$templ_email     = '';

		if ( $report ) {
			$rep_from_date = $report->date_from;
			$rep_to_date   = $report->date_to;

			$report_id       = $report->id;
			$logo_id         = $report->logo_id;
			$header_image_id = $report->header_image_id;
			$email_message   = $report->message;
			$templ_email     = MainWP_Pro_Reports_Template::get_instance()->get_template_email_file_content( $report, $email_message );
		}

		$date_from = $cust_from_date ? $cust_from_date : $rep_from_date;
		$date_to   = $cust_to_date ? $cust_to_date : $rep_to_date;

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// first, to remove section with [hide-section-data]
		$templ_content = preg_replace_callback( '/\[config-section-data\](.*?)\[\/config-section-data\]/is', array( self::class, '_callback_hide_section' ), $templ_content );
		if ( ! empty( $templ_email ) ) {
			$templ_email = preg_replace_callback( '/\[config-section-data\](.*?)\[\/config-section-data\]/is', array( self::class, '_callback_hide_section' ), $templ_email );
		}

		$attach_logo = $header_image = '';

		if ( $logo_id ) {
			$attach_logo = MainWP_Pro_Reports_Utility::get_attachment_url( $logo_id ); // do not encode url for <img> tag.
		}

		if ( $header_image_id ) {
			$header_image = MainWP_Pro_Reports_Utility::get_attachment_url( $header_image_id );
		}

		$return                 = new stdClass();
		$return->filtered_body  = $templ_content;
		$return->filtered_email = $templ_email;

		$return->id = $report_id;

		$get_ga_tokens = ( strpos( $templ_content, '[ga.' ) !== false ) ? true : false;
		$get_ga_chart  = ( strpos( $templ_content, '[ga.visits.chart]' ) !== false ) ? true : false;
		$get_ga_chart  = $get_ga_chart || ( ( strpos( $templ_content, '[ga.visits.maximum]' ) !== false ) ? true : false );

		$get_fathom_tokens = ( strpos( $templ_content, '[fathom.' ) !== false ) ? true : false;
		$get_fathom_chart  = ( strpos( $templ_content, '[fathom.visits.chart]' ) !== false ) ? true : false;
		$get_fathom_chart  = $get_fathom_chart || ( ( strpos( $templ_content, '[fathom.visits.maximum]' ) !== false ) ? true : false );

		$get_piwik_tokens         = ( strpos( $templ_content, '[piwik.' ) !== false ) ? true : false;
		$get_aum_tokens           = ( strpos( $templ_content, '[aum.' ) !== false ) ? true : false;
		$get_woocom_tokens        = ( strpos( $templ_content, '[wcomstatus.' ) !== false ) ? true : false;
		$get_pagespeed_tokens     = ( strpos( $templ_content, '[pagespeed.' ) !== false ) ? true : false;
		$get_virusdie_tokens      = ( strpos( $templ_content, '[virusdie.' ) !== false ) ? true : false;
		$get_vulnerable_tokens    = ( strpos( $templ_content, '[vulnerable.' ) !== false || strpos( $templ_content, '[vulnerabilities.' ) !== false ) ? true : false;
		$get_lighthouse_tokens    = ( strpos( $templ_content, '[lighthouse.' ) !== false ) ? true : false;
		$get_domainmonitor_tokens = ( strpos( $templ_content, '[domain.' ) !== false ) ? true : false;
		$get_sslmonitor_tokens    = ( strpos( $templ_content, '[ssl.' ) !== false ) ? true : false;
		$get_atarim_tokens        = ( strpos( $templ_content, '[atarim.' ) !== false ) ? true : false;
		$get_jp_protect_tokens    = ( strpos( $templ_content, '[jetpack.protect.' ) !== false ) ? true : false;
		$get_jp_scan_tokens       = ( strpos( $templ_content, '[jetpack.scan.' ) !== false ) ? true : false;
		$get_time_tracker_tokens  = ( strpos( $templ_content, '[timetracker.' ) !== false ) ? true : false;

		$get_plugins_abandoned_tokens = ( strpos( $templ_content, '[section.plugins.abandoned]' ) !== false ) ? true : false;
		$get_themes_abandoned_tokens  = ( strpos( $templ_content, '[section.themes.abandoned]' ) !== false ) ? true : false;

		$get_plugins_pending_tokens = ( strpos( $templ_content, '[section.plugins.pending]' ) !== false ) ? true : false;
		$get_themes_pending_tokens  = ( strpos( $templ_content, '[section.themes.pending]' ) !== false ) ? true : false;

		$get_wp_pending_tokens = ( strpos( $templ_content, '[section.wordpress.pending]' ) !== false ) ? true : false; // phpcs:ignore -- wordpress.
		$get_translation_pending_tokens = ( strpos( $templ_content, '[section.translation.pending]' ) !== false ) ? true : false;

		if ( ! $get_plugins_abandoned_tokens ) {
			$get_plugins_abandoned_tokens = ( strpos( $templ_content, '[plugin.abandoned.count]' ) !== false ) ? true : false;
		}

		if ( ! $get_themes_abandoned_tokens ) {
			$get_themes_abandoned_tokens = ( strpos( $templ_content, '[theme.abandoned.count]' ) !== false ) ? true : false;
		}

		if ( ! $get_plugins_pending_tokens ) {
			$get_plugins_pending_tokens = ( strpos( $templ_content, '[plugin.pending.count]' ) !== false ) ? true : false;
		}
		if ( ! $get_themes_pending_tokens ) {
			$get_themes_pending_tokens = ( strpos( $templ_content, '[theme.pending.count]' ) !== false ) ? true : false;
		}
		if ( ! $get_wp_pending_tokens ) {
			$get_wp_pending_tokens = ( strpos( $templ_content, '[wordpress.pending.count]' ) !== false ) ? true : false; // phpcs:ignore -- wordpress.
		}

		$get_other_tokens = ( strpos( $templ_content, '[installed.plugins]' ) !== false ) || ( strpos( $templ_content, '[installed.themes]' ) !== false );

		if ( ! empty( $templ_email ) ) {
			if ( ! $get_ga_tokens ) {
				$get_ga_tokens = ( strpos( $templ_email, '[ga.' ) !== false ) ? true : false;
			}

			if ( ! $get_ga_chart ) {
				$get_ga_chart = ( strpos( $templ_email, '[ga.visits.chart]' ) !== false ) ? true : false;
				$get_ga_chart = $get_ga_chart || ( ( strpos( $templ_email, '[ga.visits.maximum]' ) !== false ) ? true : false );
			}

			if ( ! $get_fathom_tokens ) {
				$get_fathom_tokens = ( strpos( $templ_email, '[fathom.' ) !== false ) ? true : false;
			}

			if ( ! $get_fathom_chart ) {
				$get_fathom_chart = ( strpos( $templ_email, '[fathom.visits.chart]' ) !== false ) ? true : false;
				$get_fathom_chart = $get_fathom_chart || ( ( strpos( $templ_email, '[fathom.visits.maximum]' ) !== false ) ? true : false );
			}

			if ( ! $get_piwik_tokens ) {
				$get_piwik_tokens = ( strpos( $templ_email, '[piwik.' ) !== false ) ? true : false;
			}

			if ( ! $get_aum_tokens ) {
				$get_aum_tokens = ( strpos( $templ_email, '[aum.' ) !== false ) ? true : false;
			}
			if ( ! $get_woocom_tokens ) {
				$get_woocom_tokens = ( strpos( $templ_email, '[wcomstatus.' ) !== false ) ? true : false;
			}

			if ( ! $get_pagespeed_tokens ) {
				$get_pagespeed_tokens = ( strpos( $templ_email, '[pagespeed.' ) !== false ) ? true : false;
			}

			if ( ! $get_virusdie_tokens ) {
				$get_virusdie_tokens = ( strpos( $templ_email, '[virusdie.' ) !== false ) ? true : false;
			}
			if ( ! $get_vulnerable_tokens ) {
				$get_vulnerable_tokens = ( strpos( $templ_email, '[vulnerable.' ) !== false || strpos( $templ_email, '[vulnerabilities.' ) !== false ) ? true : false;
			}

			if ( ! $get_lighthouse_tokens ) {
				$get_lighthouse_tokens = ( strpos( $templ_email, '[lighthouse.' ) !== false ) ? true : false;
			}

			if ( ! $get_domainmonitor_tokens ) {
				$get_domainmonitor_tokens = ( strpos( $templ_email, '[domain.' ) !== false ) ? true : false;
			}

			if ( ! $get_sslmonitor_tokens ) {
				$get_sslmonitor_tokens = ( strpos( $templ_email, '[ssl.' ) !== false ) ? true : false;
			}

			if ( ! $get_atarim_tokens ) {
				$get_atarim_tokens = ( strpos( $templ_email, '[atarim.' ) !== false ) ? true : false;
			}

			if ( ! $get_jp_protect_tokens ) {
				$get_jp_protect_tokens = ( strpos( $templ_email, '[jetpack.protect.' ) !== false ) ? true : false;
			}

			if ( ! $get_jp_scan_tokens ) {
				$get_jp_scan_tokens = ( strpos( $templ_email, '[jetpack.scan.' ) !== false ) ? true : false;
			}

			if ( ! $get_time_tracker_tokens ) {
				$get_time_tracker_tokens = ( strpos( $templ_email, '[timetracker.' ) !== false ) ? true : false;
			}

			if ( ! $get_plugins_abandoned_tokens ) {
				$get_plugins_abandoned_tokens = ( strpos( $templ_email, '[section.plugins.abandoned]' ) !== false ) ? true : false;
			}

			if ( ! $get_themes_abandoned_tokens ) {
				$get_themes_abandoned_tokens = ( strpos( $templ_email, '[section.themes.abandoned]' ) !== false ) ? true : false;
			}

			if ( ! $get_plugins_pending_tokens ) {
				$get_plugins_pending_tokens = ( strpos( $templ_email, '[section.plugins.pending]' ) !== false ) ? true : false;
			}

			if ( ! $get_themes_pending_tokens ) {
				$get_themes_pending_tokens = ( strpos( $templ_email, '[section.themes.pending]' ) !== false ) ? true : false;
			}

			if ( ! $get_wp_pending_tokens ) {
				$get_wp_pending_tokens = ( strpos( $templ_email, '[section.wordpress.pending]' ) !== false ) ? true : false; // phpcs:ignore -- wordpress.
			}

			if ( ! $get_translation_pending_tokens ) {
				$get_translation_pending_tokens = ( strpos( $templ_email, '[section.translation.pending]' ) !== false ) ? true : false;
			}

			if ( ! $get_other_tokens ) {
				$get_other_tokens = ( strpos( $templ_email, '[installed.plugins]' ) !== false ) || ( strpos( $templ_email, '[installed.themes]' ) !== false );
			}

			if ( ! $get_plugins_abandoned_tokens ) {
				$get_plugins_abandoned_tokens = ( strpos( $templ_email, '[plugin.abandoned.count]' ) !== false ) ? true : false;
			}

			if ( ! $get_themes_abandoned_tokens ) {
				$get_themes_abandoned_tokens = ( strpos( $templ_email, '[theme.abandoned.count]' ) !== false ) ? true : false;
			}

			if ( ! $get_plugins_pending_tokens ) {
				$get_plugins_pending_tokens = ( strpos( $templ_email, '[plugin.pending.count]' ) !== false ) ? true : false;
			}
			if ( ! $get_themes_pending_tokens ) {
				$get_themes_pending_tokens = ( strpos( $templ_email, '[theme.pending.count]' ) !== false ) ? true : false;
			}
			if ( ! $get_wp_pending_tokens ) {
				$get_wp_pending_tokens = ( strpos( $templ_email, '[wordpress.pending.count]' ) !== false ) ? true : false; // phpcs:ignore -- wordpress.
			}
		}

		$replace_tokens_values = array();

		if ( ! empty( $attach_logo ) ) {
			$replace_tokens_values['[logo.url]'] = $attach_logo;
		} else {
			$replace_tokens_values['[logo.url]'] = '';
		}

		if ( ! empty( $header_image ) ) {
			$replace_tokens_values['[header.image.url]'] = $header_image;
		} else {
			$replace_tokens_values['[header.image.url]'] = MainWP_Pro_Reports_Utility::get_included_image_url( 'background.png', false ); // do not encode url for <img> tag.
		}

		if ( ! empty( $website ) ) {

			$get_website_tokens = true;

			$params_single = compact(
				'get_ga_tokens',
				'get_fathom_tokens',
				'get_ga_chart',
				'get_fathom_chart',
				'get_piwik_tokens',
				'get_aum_tokens',
				'get_woocom_tokens',
				'get_pagespeed_tokens',
				'get_vulnerable_tokens',
				'get_lighthouse_tokens',
				'get_domainmonitor_tokens',
				'get_sslmonitor_tokens',
				'get_atarim_tokens',
				'get_jp_protect_tokens',
				'get_jp_scan_tokens',
				'get_website_tokens',
				'get_other_tokens'
			);

			$this->get_report_single_tokens_values( $replace_tokens_values, $website, $date_from, $date_to, $params_single, $report, $templ_content );

			// replace empty/NA token value by [empty+report+data].
			foreach ( $replace_tokens_values as $token => $value ) {
				if ( $value === '' || $value === 'N/A' ) {
					$replace_tokens_values[ $token ] = '[empty-report-data]';
				}
			}

			// Parse report content.
			$parse_report = self::parse_report_content( $templ_content, $replace_tokens_values );

			$sections             = array();
			$other_tokens         = array();
			$sections['body']     = $parse_report['sections'];
			$other_tokens['body'] = $parse_report['other_tokens'];

			self::$buffer['sections']['body'] = $parse_report['sections']; // sections tokens
			$filtered_body                    = $parse_report['filtered_content']; // filtered content: replaced addition tokens value
			unset( $parse_report );
			// End.

			// Parse email content.
			if ( ! empty( $templ_email ) ) {
				$parse_email            = self::parse_report_content( $templ_email, $replace_tokens_values );
				$sections['header']     = $parse_email['sections'];
				$other_tokens['header'] = $parse_email['other_tokens'];

				self::$buffer['sections']['header'] = $parse_email['sections']; // to compatible with process on child, sections tokens.
				$filtered_email                     = $parse_email['filtered_content']; // filtered content: replaced addition tokens value
				unset( $parse_email );
			}
			// End.

			$information = $this->get_report_sections_tokens_values( $website['id'], $sections, $other_tokens, $date_from, $date_to );

			$params_other_sections = compact(
				'get_virusdie_tokens',
				'get_plugins_abandoned_tokens',
				'get_themes_abandoned_tokens',
				'get_plugins_pending_tokens',
				'get_themes_pending_tokens',
				'get_wp_pending_tokens',
				'get_translation_pending_tokens',
				'get_time_tracker_tokens',
			);

			// run after fetch_remote_data().
			$this->get_report_other_sections_tokens_values( $information, $sections, $other_tokens, $website['id'], $date_from, $date_to, $params_other_sections );

			self::fix_empty_logs_values( $information, 'body' );
			self::fix_empty_logs_values( $information, 'header' );

			$sections_data                 = isset( $information['sections_data'] ) ? $information['sections_data'] : array();
			self::$buffer['sections_data'] = $sections_data;
			$other_tokens_data             = isset( $information['other_tokens_data'] ) ? $information['other_tokens_data'] : array();

			unset( $information );

			$pattern = self::get_pattern_normal_section();

			self::$count_sec_body   = 0;
			self::$count_sec_header = 0;

			// if fetched sections data is correct then start replace
			if ( isset( $sections_data['body'] ) && is_array( $sections_data['body'] ) && count( $sections_data['body'] ) > 0 ) {
				// replace data in sections token
				$filtered_body = preg_replace_callback( $pattern, array( self::class, '_callback_replace_sections_body' ), $filtered_body );
			}

			// if fetched sections data is correct then start replace
			if ( isset( $sections_data['header'] ) && is_array( $sections_data['header'] ) && count( $sections_data['header'] ) > 0 ) {
				// replace data in sections token
				$filtered_email = preg_replace_callback( $pattern, array( self::class, '_callback_replace_sections_header' ), $filtered_email );
			}

			// support to get raw report data .
			$filtered_raw = array();

			if ( is_array( $replace_tokens_values ) ) {
				foreach ( $replace_tokens_values as $token => $value ) {
					if ( strpos( $templ_content, $token ) !== false ) {
						$filtered_raw[ $token ] = $value;
					}
				}
			}
			if ( isset( $other_tokens_data['body'] ) && is_array( $other_tokens_data['body'] ) ) {
				foreach ( $other_tokens_data['body'] as $token => $value ) {
					if ( in_array( $token, $other_tokens['body'] ) ) {
						$filtered_raw[ $token ] = $value;
					}
				}
			}

			if ( ! is_array( $output ) ) {
				$output = array();
			}

			if ( $data_type == 'raw' ) {
				if ( is_array( $sections_data ) && isset( $sections_data['body'] ) ) {
					$filtered_raw['sections_data'] = $sections_data['body'];
				}
				return $filtered_raw;
			}

			$other_compatible_tokens = MainWP_Pro_Reports_Tokens::get_instance()->get_compatible_tokens_values();

			// replace other tokens data
			$filtered_body  = $this->replace_other_tokens_data( $filtered_body, $other_tokens_data, $other_tokens, $other_compatible_tokens, 'body' );
			$filtered_email = $this->replace_other_tokens_data( $filtered_email, $other_tokens_data, $other_tokens, $other_compatible_tokens, 'header' );

			/**
			 *
			 * Replace content.
			 */
			$return->filtered_body  = $this->filter_replace_content( $filtered_body );
			$return->filtered_email = $this->filter_replace_content( $filtered_email );

			self::$buffer = array();
		}
		return $return;
	}

	public function filter_replace_content( $filtered_content ) {
		$filtered_content = preg_replace_callback( '/\[config-section-data\](.*?)\[\/config-section-data\]/is', array( self::class, '_callback_config_section' ), $filtered_content );

		$filtered_content = preg_replace_callback( '/\[config-section-parent-data\](.*?)\[\/config-section-parent-data\]/is', array( self::class, '_callback_config_section_parent' ), $filtered_content );

		// to compatible: remove [remove+if+empty] if data empty [empty+report+data]
		$filtered_content = preg_replace_callback( '/\[remove-if-empty\](.*?)\[\/remove-if-empty\]/is', array( self::class, '_callback_remove_empty_content' ), $filtered_content ); // to compatible.

		// clear config-section-extra token
		$filtered_content = preg_replace_callback( '/\[config-section-extra[^\]]*\]/is', '__return_empty_string', $filtered_content );
		$filtered_content = preg_replace_callback( '/\[config-section-parent-extra[^\]]*\]/is', '__return_empty_string', $filtered_content );

		// clear tokens
		$filtered_content = str_replace( '[empty-report-data]', '', $filtered_content );
		$filtered_content = str_replace( '[empty-section-data]', '', $filtered_content );
		$filtered_content = str_replace( '[empty-content-data]', '', $filtered_content );
		$filtered_content = str_replace( '[hide-if-empty]', '', $filtered_content );
		return $filtered_content;
	}

	public function replace_other_tokens_data( $filtered_content, $other_tokens_data, $other_tokens, $other_compatible_tokens, $part ) {
		if ( ! is_array( $other_tokens ) ) {
			$other_tokens = array();
		}
		// replace other tokens data
		if ( isset( $other_tokens_data[ $part ] ) && is_array( $other_tokens_data[ $part ] ) && count( $other_tokens_data[ $part ] ) > 0 ) {
			$search = $replace = array();
			foreach ( $other_tokens_data[ $part ] as $token => $value ) {
				if ( in_array( $token, $other_tokens[ $part ] ) ) {
					$search[] = $token;
					if ( $value === '' || $value === 'N/A' ) {
						if ( false !== strpos( $token, '.count]' ) ) {
							$replace[] = '<span token-control="[empty-section-data]">' . $value . '</span>';
						} else {
							$replace[] = '[empty-section-data]';
						}
					} elseif ( '0' === (string) $value && false !== strpos( $token, '.count]' ) ) {
						$replace[] = '<span token-control="[empty-token-value]">' . $value . '</span>';
					} else {
						$replace[] = $value;
					}
				}
			}
			// to fix.
			// to compatible.
			if ( is_array( $other_compatible_tokens ) ) {
				foreach ( $other_compatible_tokens as $token => $val ) {
					if ( ! in_array( $token, $search ) ) {
						$search[]  = $token;
						$replace[] = '[empty-report-data]'; // to support remove+if+empty
					}
				}
			}
			$filtered_content = str_replace( $search, $replace, $filtered_content );
		}

		return $filtered_content;
	}

	public function get_report_sections_tokens_values( $site_id, $sections, $other_tokens, $date_from, $date_to ) {

		MainWP_Pro_Reports_Utility::log_debug( 'Fetch remote data :: Date range :: ' . $date_from . ' - ' . $date_to );

		MainWP_Pro_Reports_Utility::log_debug( 'Fetch remote data :: Section Tokens :: ' . print_r( $sections, true ) );
		MainWP_Pro_Reports_Utility::log_debug( 'Fetch remote data :: Other Tokens :: ' . print_r( $other_tokens, true ) );

		$information = array();
		// gathering tokens data from child site
		// included: other_tokens_data and sections_data
		if ( ! empty( $sections ) || ! empty( $other_tokens ) ) {
			$information = self::fetch_remote_data( $site_id, $sections, $other_tokens, $date_from, $date_to );
			if ( ! is_array( $information ) || isset( $information['error'] ) ) {
				$information = array();
			}
		}

		MainWP_Pro_Reports_Utility::log_debug( 'Fetch remote data :: Response data :: [siteid=' . intval( $site_id ) . '] :: ' . print_r( $information, true ) );

		return $information;
	}

	public function get_report_other_sections_tokens_values( &$information, $sections, $other_tokens, $site_id, $date_from, $date_to, $params = array() ) {

		$default = array(
			'get_virusdie_tokens'            => false,
			'get_plugins_abandoned_tokens'   => false,
			'get_themes_abandoned_tokens'    => false,
			'get_plugins_pending_tokens'     => false,
			'get_themes_pending_tokens'      => false,
			'get_wp_pending_tokens'          => false,
			'get_translation_pending_tokens' => false,
			'get_time_tracker_tokens'        => false,
		);

		$params = array_merge( $default, $params );

		extract( $params );

		$parts = array( 'body', 'header' );

		foreach ( $parts as $part ) :
			if ( ! isset( $sections[ $part ] ) ) {
				continue;
			}
			// proccess virusdie data.
			if ( $get_virusdie_tokens ) {
				$virusdie_sections     = array();
				$virusdie_other_tokens = array();
				$section_idx           = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.virusdie.' ) ) {
							$virusdie_sections['section_token'][]          = $sec;
							$virusdie_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                 = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[virusdie.' ) ) {
							$virusdie_other_tokens[] = $tok;
						}
					}
				}

				$virusdie_data              = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_virusdie( $site_id, $date_from, $date_to, $virusdie_sections, $virusdie_other_tokens );
				$virusdie_sections_data     = isset( $virusdie_data['sections_data'] ) ? $virusdie_data['sections_data'] : array();
				$virusdie_other_tokens_data = isset( $virusdie_data['other_tokens_data'] ) ? $virusdie_data['other_tokens_data'] : array();

				$vir_idx = 0;
				foreach ( $section_idx as $idx ) {
					if ( isset( $virusdie_sections_data[ $vir_idx ] ) ) {
						$information['sections_data'][ $part ][ $idx ] = $virusdie_sections_data[ $vir_idx ];
					}
					++$vir_idx;
				}

				foreach ( $virusdie_other_tokens_data as $tok => $val ) {
					$information['other_tokens_data'][ $part ][ $tok ] = $val;
				}
			}

			if ( $get_plugins_abandoned_tokens ) {
				$website_data_sections     = array();
				$website_data_other_tokens = array();
				$section_idx               = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.plugins.abandoned]' ) ) {
							$website_data_sections['section_token'][]          = $sec;
							$website_data_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                     = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[plugin.abandoned.count]' ) ) {
							$website_data_other_tokens[] = $tok;
						}
					}
				}
				$this->get_website_tokens_values( $information, $part, $section_idx, $site_id, $date_from, $date_to, $website_data_sections, $website_data_other_tokens, 'plugins.abandoned' );
			}

			if ( $get_themes_abandoned_tokens ) {
				$website_data_sections     = array();
				$website_data_other_tokens = array();
				$section_idx               = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.themes.abandoned]' ) ) {
							$website_data_sections['section_token'][]          = $sec;
							$website_data_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                     = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[theme.abandoned.count]' ) ) {
							$website_data_other_tokens[] = $tok;
						}
					}
				}
				$this->get_website_tokens_values( $information, $part, $section_idx, $site_id, $date_from, $date_to, $website_data_sections, $website_data_other_tokens, 'themes.abandoned' );
			}

			if ( $get_plugins_pending_tokens ) {
				$website_data_sections     = array();
				$website_data_other_tokens = array();
				$section_idx               = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.plugins.pending]' ) ) {
							$website_data_sections['section_token'][]          = $sec;
							$website_data_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                     = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[plugin.pending.count]' ) ) {
							$website_data_other_tokens[] = $tok;
						}
					}
				}
				$this->get_website_tokens_values( $information, $part, $section_idx, $site_id, $date_from, $date_to, $website_data_sections, $website_data_other_tokens, 'plugins.pending' );
			}

			if ( $get_themes_pending_tokens ) {
				$website_data_sections     = array();
				$website_data_other_tokens = array();
				$section_idx               = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.themes.pending]' ) ) {
							$website_data_sections['section_token'][]          = $sec;
							$website_data_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                     = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[theme.pending.count]' ) ) {
							$website_data_other_tokens[] = $tok;
						}
					}
				}
				$this->get_website_tokens_values( $information, $part, $section_idx, $site_id, $date_from, $date_to, $website_data_sections, $website_data_other_tokens, 'themes.pending' );
			}

			if ( $get_wp_pending_tokens ) {
				$website_data_sections     = array();
				$website_data_other_tokens = array();
				$section_idx               = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.wordpress.pending]' ) ) { // phpcs:ignore -- wordpress
							$website_data_sections['section_token'][]          = $sec;
							$website_data_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                     = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[wordpress.pending.count]' ) ) { // phpcs:ignore -- wordpress
							$website_data_other_tokens[] = $tok;
						}
					}
				}
				$this->get_website_tokens_values( $information, $part, $section_idx, $site_id, $date_from, $date_to, $website_data_sections, $website_data_other_tokens, 'wordpress.pending' ); // phpcs:ignore -- wordpress
			}

			if ( $get_translation_pending_tokens ) {
				$website_data_sections     = array();
				$website_data_other_tokens = array();
				$section_idx               = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.translation.pending]' ) ) {
							$website_data_sections['section_token'][]          = $sec;
							$website_data_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                     = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[theme.translation.count]' ) ) {
							$website_data_other_tokens[] = $tok;
						}
					}
				}
				$this->get_website_tokens_values( $information, $part, $section_idx, $site_id, $date_from, $date_to, $website_data_sections, $website_data_other_tokens, 'translation.pending' );
			}

			if ( $get_time_tracker_tokens ) {
				$reports_sections     = array();
				$reports_other_tokens = array();
				$section_idx          = array();

				if ( isset( $sections[ $part ] ) ) {
					foreach ( $sections[ $part ]['section_token'] as $idx => $sec ) {
						if ( false !== strpos( $sec, '[section.timetracker.' ) ) {
							$reports_sections['section_token'][]          = $sec;
							$reports_sections['section_content_tokens'][] = $sections[ $part ]['section_content_tokens'][ $idx ];
							$section_idx[]                                = $idx;
						}
					}
					foreach ( $other_tokens[ $part ] as $tok ) {
						if ( false !== strpos( $tok, '[timetracker.' ) ) {
							$reports_other_tokens[] = $tok;
						}
					}
				}

				$reports_data = apply_filters( 'mainwp_pro_reports_get_reports_data', 'time_tracker', $site_id, $date_from, $date_to, $reports_sections, $reports_other_tokens );
				if ( is_array( $reports_data ) ) {
					$reports_sections_data     = isset( $reports_data['sections_data'] ) ? $reports_data['sections_data'] : array();
					$reports_other_tokens_data = isset( $reports_data['other_tokens_data'] ) ? $reports_data['other_tokens_data'] : array();

					$sec_idx = 0;
					foreach ( $section_idx as $idx ) {
						if ( isset( $reports_sections_data[ $sec_idx ] ) ) {
							$information['sections_data'][ $part ][ $idx ] = $reports_sections_data[ $sec_idx ];
						}
						++$sec_idx;
					}

					foreach ( $reports_other_tokens_data as $tok => $val ) {
						$information['other_tokens_data'][ $part ][ $tok ] = $val;
					}
				}
			}

		endforeach;
	}


	public function get_report_single_tokens_values( &$single_tokens, $website, $date_from, $date_to, $params, $report = false, $templ_content = '' ) {

		$defaults = array(
			'get_ga_tokens'            => false,
			'get_fathom_tokens'        => false,
			'get_ga_chart'             => false,
			'get_fathom_chart'         => false,
			'get_piwik_tokens'         => false,
			'get_aum_tokens'           => false,
			'get_woocom_tokens'        => false,
			'get_pagespeed_tokens'     => false,
			'get_vulnerable_tokens'    => false,
			'get_lighthouse_tokens'    => false,
			'get_domainmonitor_tokens' => false,
			'get_sslmonitor_tokens'    => false,
			'get_atarim_tokens'        => false,
			'get_jp_protect_tokens'    => false,
			'get_jp_scan_tokens'       => false,
			'get_website_tokens'       => false,
			'get_other_tokens'         => false,
		);

		$params = array_merge( $defaults, $params );

		extract( $params );

		$now = time();

		if ( $get_other_tokens ) {
			if ( self::is_rest_api_request() ) {
				$single_tokens['[report.from.date]'] = ! empty( $date_from ) ? date( 'Y-m-d H:i:s', $date_from ) : '';
				$single_tokens['[report.to.date]']   = ! empty( $date_to ) ? date( 'Y-m-d H:i:s', $date_to ) : '';
			} else {
				$single_tokens['[report.daterange]'] = MainWP_Pro_Reports_Utility::format_date( $date_from ) . ' - ' . MainWP_Pro_Reports_Utility::format_date( $date_to );
				$single_tokens['[report.send.date]'] = MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $now ) );
			}
			self::get_report_other_tokens_values( $single_tokens, $website['id'] );
		}

		if ( $get_website_tokens ) {
			$tokens      = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
			$site_tokens = self::get_tokens_of_site( $report, $website['id'] ); // with [].

			foreach ( $tokens as $token ) {
				$single_tokens[ '[' . $token->token_name . ']' ] = isset( $site_tokens[ '[' . $token->token_name . ']' ] ) ? $site_tokens[ '[' . $token->token_name . ']' ] : '';
			}

			$client_addition_tokens = self::get_addition_tokens( $website['id'] );

			if ( is_array( $client_addition_tokens ) ) {
				foreach ( $client_addition_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}

			foreach ( $site_tokens as $tok_name => $tok_value ) {
				$single_tokens[ $tok_name ] = $tok_value;
			}
		}

		if ( $get_piwik_tokens ) {
			$piwik_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_piwik( $website['id'], $date_from, $date_to );
			if ( is_array( $piwik_tokens ) ) {
				foreach ( $piwik_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_ga_tokens ) {
			$ga_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_ga( $website['id'], $date_from, $date_to, $get_ga_chart );
			if ( is_array( $ga_tokens ) ) {
				foreach ( $ga_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_fathom_tokens ) {
			$fathom_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_fathom( $website['id'], $date_from, $date_to, $get_fathom_chart );
			if ( is_array( $fathom_tokens ) ) {
				foreach ( $fathom_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_aum_tokens ) {
			$aum_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_aum( $website['id'], $date_from, $date_to );
			if ( is_array( $aum_tokens ) ) {
				foreach ( $aum_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_woocom_tokens ) {
			$wcomstatus_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_woocomstatus( $website['id'], $date_from, $date_to );
			if ( is_array( $wcomstatus_tokens ) ) {
				foreach ( $wcomstatus_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_pagespeed_tokens ) {
			$pagespeed_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_pagespeed( $website['id'], $date_from, $date_to );
			if ( is_array( $pagespeed_tokens ) ) {
				foreach ( $pagespeed_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_vulnerable_tokens ) {

			$default_vulnerable_values = MainWP_Pro_Reports_Tokens::get_instance()->get_pro_reports_default_tokens_values( 'vulnerable', 'vulnerable' );
			if ( is_array( $default_vulnerable_values ) && ! empty( $default_vulnerable_values ) ) {
				$single_tokens = array_merge( $single_tokens, $default_vulnerable_values );
			}

			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_vulnerable( $website['id'], $date_from, $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_lighthouse_tokens ) {

			$default_values = MainWP_Pro_Reports_Tokens::get_instance()->get_pro_reports_default_tokens_values( 'lighthouse', 'lighthouse' );
			if ( is_array( $default_values ) && ! empty( $default_values ) ) {
				$single_tokens = array_merge( $single_tokens, $default_values );
			}

			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_lighthouse( $website['id'], $date_from, $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_domainmonitor_tokens ) {

			$default_domainmonitor_values = MainWP_Pro_Reports_Tokens::get_instance()->get_pro_reports_default_tokens_values( 'domainmonitor', 'domainmonitor' );
			if ( is_array( $default_domainmonitor_values ) && ! empty( $default_domainmonitor_values ) ) {
				$single_tokens = array_merge( $single_tokens, $default_domainmonitor_values );
			}

			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_domainmonitor( $website['id'], $date_from, $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_sslmonitor_tokens ) {

			$default_sslmonitor_values = MainWP_Pro_Reports_Tokens::get_instance()->get_pro_reports_default_tokens_values( 'sslmonitor', 'sslmonitor' );
			if ( is_array( $default_sslmonitor_values ) && ! empty( $default_sslmonitor_values ) ) {
				$single_tokens = array_merge( $single_tokens, $default_sslmonitor_values );
			}

			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_sslmonitor( $website['id'], $date_from, $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_atarim_tokens ) {
			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_atarim( $website['id'], $date_from, $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_jp_protect_tokens ) {
			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_jetpack_protect( $website['id'], $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		if ( $get_jp_scan_tokens ) {
			$ext_tokens = MainWP_Pro_Reports_Data::get_instance()->get_ext_tokens_jetpack_scan( $website['id'], $date_to );
			if ( is_array( $ext_tokens ) ) {
				foreach ( $ext_tokens as $token => $value ) {
					$single_tokens[ '[' . $token . ']' ] = $value;
				}
			}
		}

		/**
		* Filters for custom values of site tokens before generate the report content
		*
		* @since 4.0.1
		*
		* @param array $single_tokens The array of tokens
		* @param object $report        The report.
		* @param string $website       The website.
		*/
		$single_tokens = apply_filters( 'mainwp_pro_reports_custom_tokens', $single_tokens, $report, $website, $templ_content );
		// flush for some cases with notice:  ob_end_flush(): Failed to send buffer of zlib output compression.
		if ( ob_get_length() ) {
			ob_flush();
		}
	}

	/**
	 * Virusdie data.
	 *
	 * @param array  $information Tokens reports data reference.
	 * @param array  $section_idx sections index.
	 * @param int    $site_id Child site ID.
	 * @param string $start_date Report start date.
	 * @param string $end_date Report end date.
	 * @param array  $website_data_sections Sections.
	 * @param array  $website_data_other_tokens Other tokens.
	 * @param string $data_path Data path to get.
	 *
	 * @return array|false|mixed Return Virusdie data or FALSE on failure.
	 */
	public function get_website_tokens_values( &$information, $part, $section_idx, $site_id, $start_date, $end_date, $website_data_sections, $website_data_other_tokens, $data_path ) {
		$website_data                   = MainWP_Pro_Reports_Website_Data::get_instance()->get_website_reports_tokens_data( $site_id, $start_date, $end_date, $website_data_sections, $website_data_other_tokens, $data_path );
		$website_data_sections_data     = isset( $website_data['sections_data'] ) ? $website_data['sections_data'] : array();
		$website_data_other_tokens_data = isset( $website_data['other_tokens_data'] ) ? $website_data['other_tokens_data'] : array();

		$data_idx = 0;

		foreach ( $section_idx as $idx ) {
			if ( isset( $website_data_sections_data[ $data_idx ] ) ) {
				$information['sections_data'][ $part ][ $idx ] = $website_data_sections_data[ $data_idx ];
			}
			++$data_idx;
		}

		foreach ( $website_data_other_tokens_data as $tok => $val ) {
			$information['other_tokens_data'][ $part ][ $tok ] = $val;
		}
	}

	public static function fix_empty_logs_values( &$information, $part ) {

		// only body sections in pro reports
		if ( isset( $information['sections_data'] ) && isset( $information['sections_data'][ $part ] ) ) {

			// [sections_data][body] => [section_rows_data] => [tokens_values].
			$sections_data     = $information['sections_data'][ $part ];
			$other_tokens_data = isset( $information['other_tokens_data'] ) && isset( $information['other_tokens_data'][ $part ] ) ? $information['other_tokens_data'][ $part ] : array();

			$fix_section_count = array();
			$fix_sections_data = $sections_data;
			foreach ( $sections_data as $index1 => $sec_logs ) { // section_index => section_rows_data.
				foreach ( $sec_logs as $index2 => $log_records ) { // section_rows_data => row_index => tokens_values

					$count_values = count( $log_records );
					$count_empty  = 0;
					foreach ( $log_records as $token => $value ) {
						if ( empty( $value ) ) {
							++$count_empty;
						}
					}

					$removed_empty = false;
					if ( $count_values === $count_empty ) {
						unset( $fix_sections_data[ $index1 ][ $index2 ] );
						$removed_empty = true;
					}

					if ( $removed_empty ) {
						foreach ( $log_records as $token => $value ) {
							$str_tmp   = str_replace( array( '[', ']' ), '', $token );
							$array_tmp = explode( '.', $str_tmp );

							if ( count( $array_tmp ) == 3 ) { // to able to get .count token
								if ( isset( $fix_section_count[ $token ] ) ) {
									++$fix_section_count[ $token ];
								} else {
									$fix_section_count[ $token ] = 1;
								}
								break;
							}
						}
					}
				}
			}

			// fix count tokens value
			foreach ( $fix_section_count as $tk => $count ) {
				$str_tmp                         = str_replace( array( '[', ']' ), '', $tk );
				$array_tmp                       = explode( '.', $str_tmp );
				list( $context, $action, $data ) = $array_tmp;
				// to do: some count token not in this format.
				$count_token = '[' . $context . '.' . $action . '.count]';
				if ( isset( $other_tokens_data[ $count_token ] ) && ( $other_tokens_data[ $count_token ] >= $count ) ) {
					$other_tokens_data[ $count_token ] = $other_tokens_data[ $count_token ] - $count; // fix count value
				}
			}
			$information['other_tokens_data'][ $part ] = $other_tokens_data;
			$information['sections_data'][ $part ]     = $fix_sections_data;
		}
	}

	public static function _callback_replace_sections_body( $matches ) {
		$start_sec  = $matches[1];
		$index      = self::$count_sec_body;
		$tokens_sec = self::$buffer['sections']['body']['section_content_tokens'][ $index ]; // tokens in sections
		++self::$count_sec_body;
		$sec_content = trim( $matches[2] ); // content of section.

		// [sections_data][body] => [0..x] :: sections => [0..y] :: rows.
		// fetched section data is correct
		if ( isset( self::$buffer['sections_data']['body'][ $index ] ) && ! empty( self::$buffer['sections_data']['body'][ $index ] ) ) {
			$data_rows        = self::$buffer['sections_data']['body'][ $index ];
			$replaced_content = '';
			$count            = 0;
			if ( is_array( $data_rows ) ) {
				foreach ( $data_rows as $tokens_value ) {
					$replaced          = self::replace_section_content( $sec_content, $tokens_sec, $tokens_value );
					$replaced_content .= $replaced;
					++$count;
				}
			}

			if ( $count == 0 ) {
				$replaced_content .= '[empty-section-data]';
			}

			return $replaced_content;
		}
		return '[empty-section-data]';
	}


	public static function _callback_replace_sections_header( $matches ) {
		$start_sec  = $matches[1];
		$index      = self::$count_sec_header;
		$tokens_sec = self::$buffer['sections']['header']['section_content_tokens'][ $index ]; // tokens in sections
		++self::$count_sec_header;
		$sec_content = trim( $matches[2] ); // content of section.

		// fetched section data is correct
		if ( isset( self::$buffer['sections_data']['header'][ $index ] ) && ! empty( self::$buffer['sections_data']['header'][ $index ] ) ) {
			$data_rows        = self::$buffer['sections_data']['header'][ $index ];
			$replaced_content = '';
			$count            = 0;
			if ( is_array( $data_rows ) ) {
				foreach ( $data_rows as $tokens_value ) {
					$replaced          = self::replace_section_content( $sec_content, $tokens_sec, $tokens_value );
					$replaced_content .= $replaced;
					++$count;
				}
			}

			if ( $count == 0 ) {
				$replaced_content .= '[empty-section-data]';
			}

			return $replaced_content;
		}
		return '[empty-section-data]';
	}


	// to compatible.
	public static function _callback_remove_empty_content( $matches ) {
		$content = $matches[1]; // this is content in [remove+if+empty] section
		if ( ( strpos( $content, '[empty-report-data]' ) !== false ) ) { // to compatible.
			return '';
		}
		return $content;
	}

	public static function _callback_hide_section( $matches ) {
		$content = $matches[1]; // this is content in [config-section-data] section
		if ( strpos( $content, '[hide-section-data]' ) !== false ) {
			return '[empty-content-data]'; // hide this section
		}
		return '[config-section-data]' . $content . '[/config-section-data]'; // do not remove config tokens at the moment.
	}

	public static function _callback_config_section( $matches ) {
		$content = $matches[1]; // this is content in [config-section-data] section
		if ( strpos( $content, '[hide-if-empty]' ) !== false ) {

			// to fix: support remove multi (max-empty) section data, and empty+report+data
			if ( preg_match_all( '/\[config-section-extra[^\]]+\]/is', $content, $matches1 ) ) { // parse extra section config
				$max_empty_data = $matches1[0][0];
				$atts           = shortcode_parse_atts( $max_empty_data );
				$max_empty      = 0;
				if ( is_array( $atts ) && isset( $atts['max-empty'] ) ) {
					$max_empty = intval( $atts['max-empty'] );
				}
				if ( $max_empty ) {
					// if empty report data more than $max_empty then empty section
					if ( preg_match_all( '/\[empty-report-data\]/is', $content, $matches2 ) ) {
						$count_empty = $matches2[0];
						if ( is_array( $count_empty ) && ( count( $count_empty ) >= $max_empty ) ) {
							return '[empty-content-data]'; // empty section.
						}
					}
					// if empty report data more that $max_empty then empty section
					if ( preg_match_all( '/\[empty-section-data\]/is', $content, $matches3 ) ) {
						$count_empty = $matches3[0];
						if ( is_array( $count_empty ) && ( count( $count_empty ) >= $max_empty ) ) {
							return '[empty-content-data]'; // empty section
						}
					}
					// if empty report data more that $max_empty then empty section
					if ( preg_match_all( '/\[empty-token-value\]/is', $content, $matches4 ) ) {
						$count_empty = $matches4[0];
						if ( is_array( $count_empty ) && ( count( $count_empty ) >= $max_empty ) ) {
							return '[empty-content-data]'; // support remove section config with count token = 0.
						}
					}
				}
			} elseif ( strpos( $content, '[empty-section-data]' ) !== false ) {
				return '[empty-content-data]'; // empty section.
			}
		}
		return $content;
	}

	public static function _callback_config_section_parent( $matches ) {
		$content = $matches[1]; // this is content in [config-section-data] section
		// to fix: support remove multi (max-empty) section data, and empty+report+data
		if ( preg_match_all( '/\[config-section-parent-extra[^\]]+\]/is', $content, $matches1 ) ) { // parse extra section config
			$max_empty_data = $matches1[0][0];
			$atts           = shortcode_parse_atts( $max_empty_data );
			$max_empty      = 0;
			if ( is_array( $atts ) && isset( $atts['max-empty'] ) ) {
				$max_empty = intval( $atts['max-empty'] );
			}
			if ( $max_empty ) {
				// if empty report data more than $max_empty then empty section
				if ( preg_match_all( '/\[empty-content-data\]/is', $content, $matches2 ) ) {
					$count_empty = $matches2[0];
					if ( is_array( $count_empty ) && ( count( $count_empty ) >= $max_empty ) ) {
						return ''; // empty section.
					}
				}
			}
		}
		return $content;
	}

	public static function replace_section_content( $content, $tokens, $replace_tokens ) {
		$count_empty = false;

		foreach ( $replace_tokens as $token => $value ) {

			if ( strpos( $token, '.count]' ) !== false ) {
				if ( $value == 0 ) {
					$count_empty = true;
				}
			}

			$value   = strip_tags( $value ); // to fix.
			$content = str_replace( $token, $value, $content );
		}
		$content = str_replace( $tokens, array(), $content ); // clear others tokens.

		if ( $count_empty ) {
			$content .= '[empty-section-data]';
		}

		return $content;
	}

	// Function: parse report content to find sections and single tokens
	// Params:
	// $content: content of the report
	// $replaceTokensValues: addition tokens and replace values (tokens of extensions, etc ...)
	// Return: sections tokens, other tokens,  filtered content after replaced addition tokens value
	public static function parse_report_content( $content, $replaceTokensValues ) {
		$client_tokens  = array_keys( $replaceTokensValues );
		$replace_values = array_values( $replaceTokensValues );

		$pattern = self::get_pattern_normal_section();

		// to replace addition tokens value
		$filtered_content = $content = str_replace( $client_tokens, $replace_values, $content );
		$sections         = array(
			'section_token'          => array(),
			'section_content_tokens' => array(),
		);
		if ( preg_match_all( $pattern, $content, $matches ) ) {
			for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
				$sec         = $matches[1][ $i ]; // open token of the section
				$sec_content = $matches[2][ $i ]; // content of the section
				$sec_tokens  = array();
				if ( preg_match_all( '/\[[^\]]+\]/is', $sec_content, $matches2 ) ) {
					$sec_tokens = $matches2[0]; // to find token in the section
				}
				// $sections[$sec] = $sec_tokens;
				$sections['section_token'][]          = $sec; // do not remove
				$sections['section_content_tokens'][] = $sec_tokens;
			}
		}
		// remove sections token, to find other tokens in the report content
		$removed_sections = preg_replace_callback( $pattern, '__return_empty_string', $content );
		$other_tokens     = array();
		// find other tokens
		if ( preg_match_all( '/\[[^\]]+\]/is', $removed_sections, $matches ) ) {
			$other_tokens = $matches[0];
		}

		// exclude config tokens
		$exclude_tks = array(
			'[config-section-data]',
			'[/config-section-data]',
			'[config-section-parent-data]',
			'[/config-section-parent-data]',
			'[remove-if-empty]', // compatible, token not used anymore.
			'[/remove-if-empty]', // compatible, token not used anymore.
			'[hide-if-empty]',
		);

		$other_tokens2 = array();
		foreach ( $other_tokens as $tk ) {
			if ( ! in_array( $tk, $exclude_tks ) ) {
				$other_tokens2[] = $tk;
			}
		}

		$other_tokens3 = array();
		foreach ( $other_tokens2 as $tk ) {
			if ( ! preg_match_all( '/\[config-section-extra[^\]]+\]/is', $tk, $matches1 ) && ! preg_match_all( '/\[config-section-parent-extra[^\]]+\]/is', $tk, $matches2 ) ) { // parse extra section config
				$other_tokens3[] = $tk;
			}
		}

		return array(
			'sections'         => $sections,
			'other_tokens'     => $other_tokens3,
			'filtered_content' => $filtered_content,
		);
	}


	public static function render_reports_site_tokens() {

		$websiteid = isset( $_GET['id'] ) ? $_GET['id'] : null;
		if ( empty( $websiteid ) ) {
			return;
		}

		$tokens      = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
		$site_tokens = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $websiteid );
		$html        = '';
		if ( is_array( $tokens ) && count( $tokens ) > 0 ) {
			$html .= '
				<h3 class="ui dividing header">' . esc_html__( 'Pro Reports Tokens', 'boilerplate-extension' ) . '</h3>
				<div class="ui form">
			';
			foreach ( $tokens as $token ) {
				if ( ! $token ) {
					continue;
				}
				$token_value = '';
				if ( isset( $site_tokens[ $token->id ] ) && $site_tokens[ $token->id ] ) {
					$token_value = htmlspecialchars( stripslashes( $site_tokens[ $token->id ]->token_value ) );
				}

				$input_name = 'pro_reports_token_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );

				$html .= '

				<div class="ui grid field">
					<label class="six wide column middle aligned">[' . stripslashes( $token->token_name ) . ']</label>
					<div class="ui six wide column">
						<div class="ui left labeled input">
							<input type="text" value="' . $token_value . '" class="regular-text" name="' . esc_attr( $input_name ) . '"/>
						</div>
					</div>
				</div>';
			}
			$html .= '</div>';
		}
		echo $html;
	}


	public function get_sites_with_reports( $websites ) {
		$sites = array();

		if ( is_array( $websites ) && count( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {
								if ( ! $plugin['active'] ) {
									break;
								}
								$site    = MainWP_Pro_Reports_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
								$sites[] = $site;
								break;
							}
						}
					}
				}
			}
		}
		return $sites;
	}

	public function generate_report_content_and_data_for_website( $report, $site, $cust_from_date = 0, $cust_to_date = 0, $generate_for = '' ) {
		if ( empty( $site ) || ! is_array( $site ) ) {
			return false;
		}
		$site_id = $site['id'];

		// fix bug
		if ( empty( $site_id ) ) {
			return false;
		}

		$option = array(
			'plugins' => true,
		);

		$enable_woocom = false;

		global $mainWPProReportsExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array( $site_id ), array(), $option );
		if ( $dbwebsites ) {
			$website = current( $dbwebsites );
			$plugins = json_decode( $website->plugins, 1 );
			if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
				foreach ( $plugins as $plugin ) {
					if ( 'woocommerce/woocommerce.php' == $plugin['slug'] ) {
						if ( $plugin['active'] ) {
							$enable_woocom = true;
						}
						break;
					}
				}
			}
		}

		$templ_content = MainWP_Pro_Reports_Template::get_instance()->get_template_file_content( $report, $site, $enable_woocom );

		$output = array();

		$filtered_reports = $this->filter_report_content( $templ_content, $report, $site, $cust_from_date, $cust_to_date, '', $output );

		$content       = self::generate_report_filtered_content( $filtered_reports, 'body' );
		$email_content = self::generate_report_filtered_content( $filtered_reports, 'email' );

		$values = array(
			'report_id'            => $report->id,
			'site_id'              => $site_id,
			'report_content'       => $content,
			'report_content_pdf'   => $content,
			'report_email_content' => $email_content,
		);

		if ( MainWP_Pro_Reports_DB::get_instance()->update_pro_report_content( $values ) ) {

			// logs generate action for: send, save_pdf, download_pdf actions.
			if ( in_array( $generate_for, array( 'send', 'save_pdf', 'download_pdf' ), true ) ) {

				$obj_site    = (object) $site;
				$action_data = array(
					'title'      => $report->title,
					'extra_info' => array(
						'report_id' => $report->id,
						'date_from' => $report->date_from,
						'date_to'   => $report->date_to,
					),
				);

				/**
				* Report action.
				*
				* @since 4.1.2
				*
				* @param string  report action generated|send.
				* @param object $obj_site website object data.
				* @param array $action_data  action data.
				*/
				do_action( 'mainwp_pro_reports_log_report_action', 'generated', $obj_site, $action_data );

			}
			return true;
		} else {
			return false;
		}
	}



	public static function fetch_remote_data( $site_id, $sections, $tokens, $date_from, $date_to ) {

		global $mainWPProReportsExtensionActivator;

		$post_data = array(
			'mwp_action'   => 'get_stream',
			'sections'     => base64_encode( wp_json_encode( $sections ) ),
			'other_tokens' => base64_encode( wp_json_encode( $tokens ) ),
			'date_from'    => $date_from,
			'date_to'      => $date_to,
		);

		$post_data   = apply_filters( 'mainwp_pro_reports_fetch_remote_post_data', $post_data );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id, 'client_report', $post_data );

		if ( is_array( $information ) && ! isset( $information['error'] ) ) {
			return $information;
		} else {
			if ( isset( $information['error'] ) ) {
				$error = $information['error'];
				if ( 'NO_CREPORT' == $error ) {
					$error = esc_html__( 'Error: No MainWP Client Reports plugin installed.' );
				}
			} else {
				$error = is_array( $information ) ? @implode( '<br>', $information ) : $information;
			}
			return array( 'error' => $error );
		}
	}
}
