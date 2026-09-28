<?php

class MainWP_Pro_Reports_Schedule {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// construct.
	}

	public static function init_cron() {
		add_action( 'mainwp_pro_reports_cron_send_reports', array( self::class, 'cron_send_reports' ) );
		add_action( 'mainwp_pro_reports_cron_continue_send_reports', array( self::class, 'cron_continue_send_reports' ) );
		add_action( 'mainwp_pro_reports_cron_notice_ready_reports', array( self::class, 'cron_notice_ready_reports' ) );

		$useWPCron = ( false === get_option( 'mainwp_wp_cron' ) ) || ( 1 == get_option( 'mainwp_wp_cron' ) );

		if ( ( $sched = wp_next_scheduled( 'mainwp_pro_reports_cron_send_reports' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), '5minutely', 'mainwp_pro_reports_cron_send_reports' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_pro_reports_cron_send_reports' );
			}
		}

		if ( ( $sched = wp_next_scheduled( 'mainwp_pro_reports_cron_continue_send_reports' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), 'minutely', 'mainwp_pro_reports_cron_continue_send_reports' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_pro_reports_cron_continue_send_reports' );
			}
		}

		if ( ( $sched = wp_next_scheduled( 'mainwp_pro_reports_cron_notice_ready_reports' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), 'hourly', 'mainwp_pro_reports_cron_notice_ready_reports' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_pro_reports_cron_notice_ready_reports' );
			}
		}
	}

	public static function cron_send_reports() {

		$send_local_time  = apply_filters( 'mainwp_pro_reports_send_local_time', false );
		$timestamp_offset = 0;
		if ( $send_local_time ) {
			$gmtOffset        = get_option( 'gmt_offset' );
			$timestamp_offset = $gmtOffset * HOUR_IN_SECONDS;
		}

		$now        = time();
		$time_check = $now + $timestamp_offset;

		$mainwpLastCheck = get_option( 'mainwp_reports_sendcheck_last' );
		if ( $mainwpLastCheck == date( 'd/m/Y', $time_check ) ) {
			return;
		}

		MainWP_Pro_Reports_Utility::log_info( 'Start check reports today' );
		/**
		* Fires before reports start checking to send.
		*
		* @since 4.0.1
		*/
		 do_action( 'mainwp_pro_reports_before_send', $mainwpLastCheck );

		$allReportsToSend = array();

		$allReadyReports = MainWP_Pro_Reports_DB::get_instance()->get_scheduled_reports_to_send( $timestamp_offset );

		if ( empty( $allReadyReports ) ) {
			MainWP_Pro_Reports_Utility::log_info( 'Checked reports today' );
			update_option( 'mainwp_reports_sendcheck_last', date( 'd/m/Y', time() ) ); // UTC date.
		}

		foreach ( $allReadyReports as $report ) {
				// CHECK: to prevent auto send too quick.
			if ( $report->schedule_lastsend > time() - 6 * HOUR_IN_SECONDS ) { // 6 hours.
				continue;
			}

			$send_at_hh_mm = apply_filters( 'mainwp_pro_reports_send_at_time', false, $report );

			if ( ! empty( $send_at_hh_mm ) ) {
				$send_timestamp = MainWP_Pro_Reports_Utility::get_timestamp_from_hh_mm( $send_at_hh_mm );
				if ( $time_check < $send_timestamp ) {
					continue;
				}
			}

			$cal_recurring = MainWP_Pro_Reports_Overview::calc_recurring_date( $report->recurring_schedule, $report->recurring_day ); // to cron job, pass offset date time support local time

			if ( empty( $cal_recurring ) ) {
				MainWP_Pro_Reports_Utility::log_debug( 'Failed to calculate recurring date :: ' . $report->title );
				continue;
			}

			$log_time = date( 'Y-m-d H:i:s', $cal_recurring['date_send'] );
			MainWP_Pro_Reports_Utility::log_info( 'found report id ' . $report->id . ', next send: ' . $log_time );
			// to fix: send current day/month/year... issue.
			$values    = array(
				'date_from_nextsend' => $cal_recurring['date_from'],
				'date_to_nextsend'   => $cal_recurring['date_to'],
				'schedule_nextsend'  => $cal_recurring['date_send'], // this field using to check if current time > schedule nextsend then prepare report to send
				'noticed'            => 0, // when current time - schedule_nextsend <= 24h then send notice to administrator
			);
			$date_from = $report->date_from_nextsend;
			$date_to   = $report->date_to_nextsend;
			if ( ! empty( $date_from ) ) {
					// using to generate report content to send now
					$values['date_from'] = $date_from;
					$values['date_to']   = $date_to;
			}
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
			$allReportsToSend[] = $report;
		}

		unset( $allReadyReports );

		MainWP_Pro_Reports_Utility::log_info( 'reports to send :: Found ' . count( $allReportsToSend ) . ' reports to send.' );

		foreach ( $allReportsToSend as $report ) {
			// update report to start sending
			MainWP_Pro_Reports_DB::get_instance()->update_reports_send( $report->id );
		}
	}

	public static function cron_continue_send_reports() {

		@ignore_user_abort( true );
		@set_time_limit( 0 );
		$mem = '512M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );

		$reports = MainWP_Pro_Reports_DB::get_instance()->get_scheduled_reports_to_continue_send();

		/**
		 * Fires before reports continue to send.
		 *
		 * $reports will continue to send.
		 *
		 * @since 4.0.1
		 */

		do_action( 'mainwp_pro_reports_before_continue_send', $reports );

		if ( empty( $reports ) ) {
			return;
		}

		// process one report
		$report = current( $reports );

		MainWP_Pro_Reports_Utility::log_info( 'continue send :: ' . $report->title );

		$sites   = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->sites );
		$groups  = MainWP_Pro_Reports_Utility::may_decode_unSerialize( $report->groups );
		$clients = ! empty( $report->clients ) ? json_decode( $report->clients, true ) : array();

		if ( ! is_array( $sites ) ) {
			$sites = array();
		}

		if ( ! is_array( $groups ) ) {
			$groups = array();
		}

		if ( ! is_array( $clients ) ) {
			$clients = array();
		}

		$chunkSend = apply_filters( 'mainwp_pro_reports_chunk_send_number', 1 ); // 1 to fix.

		if ( $chunkSend > 3 || $chunkSend < 0 ) {
			$chunkSend = 1;
		}

		$countSend = 0;

		$dbwebsites_indexed = MainWP_Pro_Reports_Utility::get_db_websites( $sites, $groups, $clients );

		$total_sites = ! empty( $dbwebsites_indexed ) ? count( $dbwebsites_indexed ) : 0;

		MainWP_Pro_Reports_Utility::log_info( 'Send total :: ' . $total_sites . ' sites' );

		if ( $total_sites > 0 ) {

			$dbwebsites  = array();
			$all_siteids = array();
			foreach ( $dbwebsites_indexed as $value ) {
				$dbwebsites[]  = $value;
				$all_siteids[] = $value->id;
			}
			unset( $dbwebsites_indexed );

			$sendme = true;

			$idx = 0;

			$completedSites = MainWP_Pro_Reports_DB::get_instance()->get_completed_websites( $report->id );

			while ( $sendme && ( $idx < $total_sites ) ) {

				$dbsite = $dbwebsites[ $idx ];

				$website = MainWP_Pro_Reports_Utility::map_site( $dbsite, array( 'id', 'name', 'url' ) );
				$site_id = $website['id'];

				$lasttime = time(); // UTC time.
				$values   = array(
					'lastsend' => $lasttime,  // to display last send time.
				);
				MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
				MainWP_Pro_Reports_DB::get_instance()->updateWebsiteOption( $site_id, 'mainwp_pro_reports_last_report', $lasttime );

				$idx++; // count to next site.

				if ( isset( $completedSites[ $site_id ] ) ) {
					continue;
				}

				$completedSites[ $site_id ] = 5; // preparing content.
				self::update_completed_websites( $report, $completedSites, $all_siteids );

				MainWP_Pro_Reports_Utility::log_info( 'preparing content sending report for site :: ' . $website['url'] );

				try {
					$data = MainWP_Pro_Reports::get_instance()->prepare_content_report_email( $report, 'send', $website, false ); // to generate report content.
				} catch ( \Exception $e ) {
					continue;
				}

				// put this here.
				$countSend++;

				if ( ! is_array( $data ) ) {
					MainWP_Pro_Reports_Utility::log_debug( 'ERROR :: Generate content :: ' . $website['url'] );
					$completedSites[ $site_id ] = 3; // generated content failed.
					self::update_completed_websites( $report, $completedSites, $all_siteids );
				} elseif ( empty( $data['to_email'] ) || ( false === stripos( $data['to_email'], '@' ) ) ) {
					MainWP_Pro_Reports_Utility::log_debug( 'ERROR :: Wrong send to email :: [' . $data['to_email'] . '] :: site id :: ' . $site_id );
					$completedSites[ $site_id ] = 1;
					self::update_completed_websites( $report, $completedSites, $all_siteids );
				} else {
					$templ_email = $data['templ_email'];

					/**
					*
					* Filters disabled send reports.
					*
					* @since 4.0.0
					*
					* @param bool  false to enable send reports .
					* @param array $data            The report data.
					* @param html|text $templ_email     The report template
					* @param int $report_id         id of the report
					* @param int $site_id           id of site
					*/
					$disabled = apply_filters( 'mainwp_pro_reports_disable_send_scheduled_reports', false, $data, $templ_email, $report->id, $site_id );

					/*
					* Perform send scheduled report email
					* see send_onetime_report_email()
					*/
					$email_subject = stripslashes( $data['subject'] );

					if ( $disabled !== true ) {
						$success = true;
						if ( wp_mail( $data['to_email'], $email_subject, $templ_email, $data['header'], $data['attachments'] ) ) {
								MainWP_Pro_Reports_Utility::log_debug( 'SUCCESS :: Send report website :: ' . $website['url'] . ' :: Subject :: ' . $email_subject );
								$completedSites[ $site_id ] = 1;
								self::update_completed_websites( $report, $completedSites, $all_siteids );
						} else {
								MainWP_Pro_Reports_Utility::log_debug( 'FAILED :: Send report website :: ' . $website['url'] . ' :: Subject :: ' . $email_subject );
								/*
								* If failed send report
								* update completed sites to prevent re-send report multi-time
								*/
								$completedSites[ $site_id ] = 0;
								self::update_completed_websites( $report, $completedSites, $all_siteids );
								$success = false;
						}

						$obj_site  = (object) $website;
						$action_data = array(
							'email'   => $data['to_email'],
							'subject' => $email_subject,
							'success' => $success,
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
						do_action( 'mainwp_pro_reports_log_report_action', 'send', $obj_site, $action_data );

					} else {
						MainWP_Pro_Reports_Utility::log_debug( 'DISABLED :: Send report website :: ' . $website['url'] . ' :: Subject :: ' . $email_subject );
					}
					/*
					* end send email
					*/

				}

				if ( $countSend >= $chunkSend ) {
					$sendme = false;
				}
				usleep( 200000 );
			}

			if ( $idx >= $total_sites ) {
				// check to finished or re-send failed for sites.
				self::update_completed_websites( $report, $completedSites, $all_siteids );
			}
		} else {
			$lastsend = time();
			$values   = array(
				'lastsend' => $lastsend, // Displays last send time only.
			);
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
			MainWP_Pro_Reports_Utility::log_info( 'scheduled report :: total sites :: 0' );
			MainWP_Pro_Reports_DB::get_instance()->update_completed_report( $report->id ); // to fix reports with no sites.
		}

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
					function( $val ) {
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


	// checking and notice reports ready to send after a day
	public static function cron_notice_ready_reports() {

		@ignore_user_abort( true );
		@set_time_limit( 0 );
		$mem = '512M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );

		$send_local_time  = apply_filters( 'mainwp_pro_reports_send_local_time', false );
		$timestamp_offset = 0;
		if ( $send_local_time ) {
			$gmtOffset        = get_option( 'gmt_offset' );
			$timestamp_offset = $gmtOffset * HOUR_IN_SECONDS;
		}

		$reports = MainWP_Pro_Reports_DB::get_instance()->get_scheduled_reports_ready_to_notice( 3, $timestamp_offset );

		MainWP_Pro_Reports_Utility::log_info( 'Reports ready to send after a day :: Found ' . count( $reports ) );

		if ( empty( $reports ) ) {
			return;
		}

		foreach ( $reports as $report ) {
			self::do_send_ready_notice( $report );
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, array( 'noticed' => 1 ) );
		}
	}

	public function send_onetime_report_email( $data, $report, $website = null ) {

		$email_subject = stripslashes( $data['subject'] );
		$templ_email   = $data['templ_email'];

		$site_id = $website['id'];

		$success = false;

		if ( wp_mail( $data['to_email'], $email_subject, $templ_email, $data['header'], $data['attachments'] ) ) {
			$lasttime = time(); // UTC time.
			$values   = array(
				'lastsend' => $lasttime,  // to display last send time only
			);
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
			MainWP_Pro_Reports_DB::get_instance()->updateWebsiteOption( $site_id, 'mainwp_pro_reports_last_report', $lasttime );
			MainWP_Pro_Reports_Utility::log_debug( 'SUCCESS :: Send report :: Subject :: ' . $email_subject );
			$success = true;
		} else {
			MainWP_Pro_Reports_Utility::log_debug( 'FAILED :: Send report :: Subject :: ' . $email_subject );
		}
		
		$obj_site  = (object) $website;
		$action_data = array(
			'email'   => $data['to_email'],
			'subject' => $email_subject,
			'success' => $success,
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
		do_action( 'mainwp_pro_reports_log_report_action', 'send', $obj_site, $action_data );

		return $success;
	}

	public static function do_send_ready_notice( $report ) {

		if ( empty( $report ) ) {
			return false;
		}

		if ( $report->noticed ) {
			return true;
		}

		$to_email = @apply_filters( 'mainwp_getnotificationemail', false );

		if ( empty( $to_email ) ) {
			return false;
		}

		MainWP_Pro_Reports_Utility::log_info( 'Sending ready notice : ' . $to_email );

		$link         = admin_url( 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=preview&id=' + $report->id . '&pro_reports_action_nonce=' . wp_create_nonce( 'pro_reports_action_nonce' ) );
		$send_subject = 'MainWP Pro Reports notification';
		$content      = sprintf( esc_html__( 'Tomorrow, reports will be sent to clients, click %shere% to preview the report for the past period.', 'mainwp-reports-extension' ), '<a href="' . $link . '">', '</a>' );
		$header       = array( 'content-type: text/html' );

		$format_content = '<table border="0" cellpadding="20" cellspacing="0" width="100%">
                   			<tr>
                       		<td valign="top" style="border-collapse: collapse;">
                          	<div style="color: #505050;font-family: Arial;font-size: 14px;line-height: 150%;text-align: left;"> Hi MainWP user, <br><br>
                           		<br>' . $content . '<br>
                           	</div>
                       		</td>
                   			</tr>
               				</table>';

		if ( wp_mail( $to_email, stripslashes( $send_subject ), $format_content, $header ) ) {
			MainWP_Pro_Reports_Utility::log_debug( 'SUCCESS :: Send ready notice.' );
			return true;
		} else {
			MainWP_Pro_Reports_Utility::log_debug( 'FAILED :: Send ready notice.' );
		}
		return false;
	}
}
