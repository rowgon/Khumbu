<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Handles outbound messages, including security alerts, reports,
 * system notifications, and user-facing messages such as 2FA codes.
 *
 * Builds message content based on message type, applies channel policies and
 * rate limits, and delivers messages via supported transports (email, Pushbullet).
 * Also manages email delivery details such as SMTP configuration, fallback to
 * default wp_mail(), deferred sending, and error handling.
 *
 * This class acts as a single entry point for sending all outgoing messages.
 */
class CRB_Messaging {

	const SEND_ISSUE_CODE = 'email_send_error';

	private static ?self $instance = null;
	private bool $smpt_enabled = false;
	private bool $no_smtp = false; // If true, it temporarily disables using the WP Cerber's SMTP settings
	private ?PHPMailer $mailer = null; // Last used mailer
	private array $email_args = array(); // Holds email parameters in case of postponed sending
	private bool $error = false; // If an error occurred while sending

	private static function instance() : self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Sends outbound messages via configured delivery channels.
	 *
	 * Sends administrative notifications, reports, and user-facing messages
	 * (including 2FA codes) via enabled delivery channels, applying rate limits
	 * and channel policies.
	 *
	 * Replacement for cerber_send_message().
	 *
	 * @param string $type Message type identifier.
	 * @param array $msg_parts Message content parts and optional variants.
	 * @param array $channels Delivery channels to use.
	 * @param bool $ignore Whether to ignore global rate limits.
	 * @param array $args Additional context and message parameters.
	 *
	 * @return array|false List of recipients on success, false on failure.
	 *
	 * @since 9.6.16
	 */
	public static function send( $type, $msg_parts = array(), $channels = array(), $ignore = false, $args = array() ) {
		return self::instance()->send_message( $type, $msg_parts, $channels, $ignore, $args );
	}

	/**
	 * Sends outbound messages via configured delivery channels.
	 *
	 * Builds message content based on message type, applies channel selection and
	 * rate limits, and dispatches delivery via supported transports.
	 *
	 * @param string $type Message type identifier.
	 * @param array $msg_parts Message content parts and optional variants.
	 * @param array $channels Delivery channels to use.
	 * @param bool $ignore Whether to ignore global rate limits.
	 * @param array $args Additional context and message parameters.
	 *
	 * @return array|false List of recipients on success, false on failure.
	 *
	 * @since 9.5.5.1
	 */
	private function send_message( $type, $msg_parts = array(), $channels = array(), $ignore = false, $args = array() ) {

		$type = $type ?: 'generic';

		$sb = $msg_parts['subj'] ?? '';
		$msg = $msg_parts['text'] ?? '';
		$msg_masked = $msg_parts['text_masked'] ?? '';
		$more = $msg_parts['more'] ?? '';
		$ip = $msg_parts['ip'] ?? '';

		$channels = array_merge( CRB_CHANNELS, $channels );

		if ( ! array_filter( $channels ) ) {
			return false;
		}

		if ( ! $ignore
		     && ! is_admin()
		     && in_array( $type, array( 'lockout', 'send_alert' ) ) ) {

			$channels = $this->check_limits( $channels );

			if ( ! array_filter( $channels ) ) {
				return false;
			}
		}

		$html_mode = false;
		$blogname = crb_get_blogname_decoded();

		if ( ! in_array( $type, array( '2fa', 'new_version' ) ) ) {
			$subj = '[' . $blogname . '] WP Cerber: ' . $sb;
		}
		else {
			$subj = '[' . $blogname . '] ' . $sb;
		}

		$body = '';
		$body_masked = '';

		if ( is_array( $msg ) ) {
			$msg = implode( "\n\n", $msg ) . "\n\n";
		}

		if ( is_array( $msg_masked ) ) {
			$msg_masked = implode( "\n\n", $msg_masked ) . "\n\n";
		}

		$last = null;

		switch ( $type ) {
			case 'citadel_mode':
				$max = cerber_db_get_var( 'SELECT MAX(stamp) FROM ' . CERBER_LOG_TABLE . ' WHERE  activity = ' . CRB_EV_LFL );
				if ( $max ) {
					$last_date = cerber_date( $max, false );
					$last = cerber_db_get_row( 'SELECT * FROM ' . CERBER_LOG_TABLE . ' WHERE stamp = ' . $max . ' AND activity = ' . CRB_EV_LFL, MYSQL_FETCH_OBJECT );
				}

				if ( ! $last ) { // workaround for the empty log table
					$last = new stdClass();
					$last->ip = CERBER_NO_REMOTE_IP;
					$last->user_login = 'test';
				}

				$subj .= __( 'Citadel mode is active', 'wp-cerber' );

				/* translators: %1$d is the number of failed login attempts, %2$d is the time period in minutes. */
				$body = sprintf( __( 'Citadel mode has been activated after %1$d failed login attempts in %2$d minutes.', 'wp-cerber' ), crb_get_settings( 'cilimit' ), crb_get_settings( 'ciperiod' ) ) . "\n\n";
				/* translators: %1$s is the date and time, %2$s is the IP address, %3$s is the username. */
				$body .= sprintf( __( 'Last failed attempt was at %1$s from IP %2$s using username: %3$s.', 'wp-cerber' ), $last_date, $last->ip, $last->user_login ) . "\n\n";

				/* translators: %s is the URL to view activity in the Dashboard. */
				$more = sprintf( __( 'View activity in the Dashboard: %s', 'wp-cerber' ), cerber_admin_link( 'activity', array(), false, false ) ) . "\n\n";
				break;
			case 'lockout':
				$max = cerber_db_get_var( 'SELECT MAX(stamp) FROM ' . CERBER_LOG_TABLE . ' WHERE  activity IN (10,11)' );

				if ( $max ) {
					$last_date = cerber_date( $max, false );
					$last = cerber_db_get_row( 'SELECT * FROM ' . CERBER_LOG_TABLE . ' WHERE stamp = ' . $max . ' AND activity IN (10,11)', MYSQL_FETCH_OBJECT );
				}
				else {
					$last_date = '';

					// workaround for the empty log table
					$last = new stdClass();
					$last->ip = CERBER_NO_REMOTE_IP;
					$last->user_login = 'test';
				}

				$active = cerber_blocked_num();

				if ( $last->ip && ( $block = cerber_get_block( $last->ip ) ) ) {
					$reason = $block->reason;
				}
				else {
					$reason = __( 'unspecified', 'wp-cerber' );
				}

				/* translators: %d is the number of active lockouts. */
				$subj .= sprintf( __( 'Number of lockouts is increasing (%d)', 'wp-cerber' ), $active );

				/* translators: %d is the number of active lockouts. */
				$body = sprintf( __( 'Number of active lockouts at the moment: %d', 'wp-cerber' ), $active ) . "\n\n";

				if ( $last_date ) {
					/* translators: %1$s is the IP address with hostname, %2$s is the date and time of the lockout. */
					$body .= sprintf( __( 'Last security action: IP address %1$s was locked out at %2$s', 'wp-cerber' ), $last->ip . ' (' . @gethostbyaddr( $last->ip ) . ')', $last_date ) . "\n\n";
					/* translators: %s is the lockout reason. */
					$body .= sprintf( __( 'Reason: %s', 'wp-cerber' ), strip_tags( $reason ) ) . "\n\n";
				}

				/* translators: %s is the URL to view activity for the IP. */
				$more = sprintf( __( 'View activity for this IP: %s', 'wp-cerber' ), cerber_admin_link( 'activity', array(), false, false ) . '&filter_ip=' . $last->ip ) . "\n\n";
				/* translators: %s is the URL to view all lockouts. */
				$more .= sprintf( __( 'View all lockouts: %s', 'wp-cerber' ), cerber_admin_link( 'lockouts', array(), false, false ) ) . "\n\n";

				/* translators: %s is the URL to learn more about alerting. */
				$more .= sprintf( __( 'Learn more about advanced alerting in WP Cerber: %s', 'wp-cerber' ), 'https://wpcerber.com/wordpress-notifications-made-easy/' );
				break;
			case 'new_version':
				$body = $msg . "\n\n";
				/* translators: %s is the website name. */
				$more = sprintf( __( 'Website: %s', 'wp-cerber' ), $blogname );
				break;
			case 'shutdown':
				$d = __( 'The WP Cerber Security plugin has been deactivated', 'wp-cerber' );
				$subj = '[' . $blogname . '] ' . $d;
				$body .= "\n" . $d . "\n\n";
				if ( ! is_user_logged_in() ) {
					$u = __( 'Unknown', 'wp-cerber' );
				}
				else {
					$user = wp_get_current_user();
					$u = $user->display_name;
				}
				/* translators: %s is the website name. */
				$body .= sprintf( __( 'Website: %s', 'wp-cerber' ), $blogname ) . "\n";

				/* translators: %s is the user name. */
				$more = sprintf( __( 'By the user: %s', 'wp-cerber' ), $u ) . "\n";
				/* translators: %s is the IP address. */
				$more .= sprintf( __( 'From the IP address: %s', 'wp-cerber' ), cerber_get_remote_ip() ) . "\n";

				$ip_rdap = CRB_RDAP_Client::get_parsed_ip_info( $ip );

				if ( ! crb_is_wp_error( $ip_rdap ) && ! empty( $ip_rdap->country ) ) {
					/* translators: %s is the country name. */
					$more .= sprintf( __( 'From the country: %s', 'wp-cerber' ), crb_get_country_name( $ip_rdap->country ) );
				}

				break;
			case 'activated':
				$subj = '[' . $blogname . '] ' . __( 'The WP Cerber Security plugin is now active', 'wp-cerber' );
				$body = "\n" . __( 'WP Cerber is now active and has started protecting your site', 'wp-cerber' ) . "\n\n";
				$body .= __( 'Getting Started Guide', 'wp-cerber' ) . "\n\n";
				$body .= 'https://wpcerber.com/getting-started/' . "\n\n";
				$body .= 'Is your website under Cloudflare? You have to enable a crucial WP Cerber setting.' . "\n\n";
				$body .= 'https://wpcerber.com/cloudflare-and-wordpress-cerber/' . "\n\n";
				$body .= 'Be in touch with the developer.' . "\n\n";
				$body .= 'Follow Cerber on X: https://twitter.com/wpcerber' . "\n\n";
				$body .= "Subscribe to Cerber's newsletter: https://wpcerber.com/subscribe-newsletter/" . "\n\n";
				break;
			case 'newlurl':
				$subj .= __( 'New Custom login URL', 'wp-cerber' );
				$body .= $msg;
				break;
			case 'send_alert':
				$body = __( 'A new activity has occurred', 'wp-cerber' ) . "\n\n";
				$body_masked = $body;
				$body .= $msg;
				$body_masked .= $msg_masked;
				break;
			case 'report':
				list ( $title, $body ) = cerber_generate_email_report( $args );
				$subj .= $title;
				$link = cerber_admin_link( 'notifications', array(), false, false );
				$body .= '<br/>' . __( 'To change reporting settings visit', 'wp-cerber' ) . ' <a href="' . $link . '">' . $link . '</a>';
				$body .= $msg;
				$html_mode = true;
				break;
			case 'scan':
				$subj .= __( 'Scanner Report', 'wp-cerber' );
				$body = $msg;

				$link = cerber_admin_link( 'scan_main', array(), false, false );
				$body .= '<p>' . __( 'To view full report, navigate to:', 'wp-cerber' ) . ' <a href="' . $link . '">' . $link . '</a></p>';

				$link = cerber_admin_link( 'scan_schedule', array(), false, false );
				$body .= '<br/>' . __( 'To modify your reporting settings, navigate to:', 'wp-cerber' ) . ' <a href="' . $link . '">' . $link . '</a>';

				$html_mode = true;
				break;
			case 'generic':
			case '2fa':
			default:
				$body = $msg;
				break;
		}

		$to_list = array();
		$to = '';

		if ( $channels['email'] ) {
			$to_list = cerber_get_email( $type, $args );
			$to = implode( ', ', $to_list );
		}

		$body_filtered = apply_filters( 'cerber_notify_body', $body, array(
			'type'    => $type,
			'IP'      => $ip,
			'to'      => $to,
			'subject' => $subj
		) );

		if ( $body_filtered && is_string( $body_filtered ) ) {
			$body = $body_filtered;
		}

		if ( ! $body ) {
			//return new WP_Error( 'notifications', 'No text of the message provided.' );
			return false;
		}

		$footer = '';
		$mf = crb_get_settings( 'email_format' );

		if ( ( 1 > $mf )
		     && ! in_array( $type, array( 'shutdown', 'generic', '2fa' ) )
		     && $lolink = cerber_get_custom_login_url() ) {

			$lourl = urldecode( $lolink );

			if ( $html_mode ) {
				$lourl = '<a href="' . $lolink . '">' . $lourl . '</a>';
			}

			/* translators: %s is the custom login page URL. */
			$footer .= "\n\n" . sprintf( __( 'Your login page: %s', 'wp-cerber' ), $lourl );

		}

		if ( ( 1 > $mf )
		     && $type == 'report'
		     && $date = lab_lab( 1 ) ) {
			/* translators: %s is the license expiration date. */
			$footer .= "\n\n" . sprintf( __( 'Your license is valid until %s', 'wp-cerber' ), $date );
		}

		if ( $type != '2fa' ) {
			/* translators: %s is the plugin name and version. */
			$footer .= "\n\n\n" . sprintf( __( 'This message was created by %s', 'wp-cerber' ), 'WP Cerber Security ' . ( lab_lab() ? 'Professional ' : '' ) . ( 1 > $mf ? CERBER_VER : '' ) );
			/* translators: %s is the date. */
			$footer .= "\n" . sprintf( __( 'Date: %s', 'wp-cerber' ), cerber_date( time(), false ) );
			$footer .= "\n" . 'https://wpcerber.com';
		}

		// Everything is prepared, let's send it out

		$results = array();
		$recipients = array();
		$success = false;

		$go = 'pushbullet';
		if ( $channels[ $go ] && ! $html_mode ) {
			$body_go = ( $type == 'send_alert' && crb_get_settings( 'pb_mask' ) ) ? $body_masked : $body;
			$res = cerber_pb_send( $subj, $body_go, $more, $footer );
			if ( $res && ! crb_is_wp_error( $res ) ) {
				$results[ $go ] = true;
				$recipients[ $go ] = cerber_pb_get_active();
				$success = true;
			}
		}

		$go = 'email';
		if ( $channels[ $go ] ) {
			$body_go = ( $type == 'send_alert' && crb_get_settings( 'email_mask' ) ) ? $body_masked : $body;
			if ( $results[ $go ] = $this->send_email( $type, $html_mode, $to_list, $subj, $body_go, $more, $footer, $ip ) ) {
				$recipients[ $go ] = $to;
				$success = true;
			}
		}

		if ( ! $success ) {

			return false;
		}

		$sent = cerber_get_set( '_cerber_last_send' );

		if ( ! is_array( $sent ) ) {
			$sent = array();
		}

		foreach ( $results as $channel_id => $result ) {
			$sent[ $channel_id ] = ( $result ) ? time() : 0;
		}

		cerber_update_set( '_cerber_last_send', $sent );

		return $recipients;
	}

	/**
	 * Sends emails. Acts as a go_send_email() wrapper.
	 *
	 * @see self::go_send_email()
	 *
	 * @param string $type
	 * @param bool $html_mode
	 * @param array $to_list
	 * @param string $subj
	 * @param string $body
	 * @param string $more
	 * @param string $footer
	 * @param string $ip
	 *
	 * @return bool
	 *
	 * @since 9.5.5.1
	 */
	private function send_email( $type, $html_mode, $to_list, $subj, $body, $more, $footer, $ip ) {

		if ( function_exists( 'wp_mail' ) ) {
			return $this->go_send_email( $type, $html_mode, $to_list, $subj, $body, $more, $footer, $ip );
		}

		// Here wp_mail() is not yet defined
		// We postpone sending to the moment when we expect it is defined

		$this->email_args[] = func_get_args();

		add_action( 'plugins_loaded', array( $this, 'launch_send_email' ) );

		// If 'plugins_loaded' is not invoked - e.g. HTTP redirection occurred before
		register_shutdown_function( [ $this, 'launch_send_email' ] );

		// We have to return the result of sending, but at this pont, we have no idea how the postponed sending will end
		// We hope it will be OK.
		return true;
	}

	/**
	 * Sends emails if sending was postponed due to wp_mail() is not defined
	 *
	 * @return void
	 *
	 * @since 9.5.5.1
	 */
	public function launch_send_email() {

		if ( ! $this->email_args ) {
			return;
		}

		foreach ( $this->email_args as $item ) {
			$this->go_send_email( ...$item );
		}

		$this->email_args = array();
	}

	/**
	 * Sends email
	 *
	 * @param string $type
	 * @param bool $html_mode
	 * @param array $to_list
	 * @param string $subj
	 * @param string $body
	 * @param string $more
	 * @param string $footer
	 * @param string $ip
	 *
	 * @return bool
	 *
	 * @since 8.9.6.1
	 */
	private function go_send_email( $type, $html_mode, $to_list, $subj, $body, $more, $footer, $ip ) {

		$this->error = false;
		add_action( 'wp_mail_failed', array( $this, 'process_email_errors' ) );

		if ( $html_mode ) {
			add_filter( 'wp_mail_content_type', 'cerber_enable_html' );
			$footer = str_replace( "\n", '<br/>', $footer );
		}

		if ( crb_get_settings( 'email_format' ) < 2 ) {
			$body .= $more;
		}

		$this->enable_smtp();

		$result = false;
		$to = implode( ', ', $to_list );

		if ( $to_list && $subj && $body ) {

			$lang = crb_get_bloginfo( 'language' );

			if ( $type == 'report') {

				$result = true;

				foreach ( $to_list as $email ) {

					$lastus = '';

					if ( ( $user = get_user_by( 'email', $email ) )
					     && $last = crb_get_last_user_login( $user->ID ) ) {

						$last_ip = crb_get_settings( 'email_mask' ) ? crb_mask_ip( $last['ip'] ) : $last['ip'];

						/* translators: Here the first placeholder %s is a date of the last user login and the second placeholder %s is the user's IP address. */
						$lastus = sprintf( __( 'Your last sign-in was at %1$s from the IP address %2$s', 'wp-cerber' ), cerber_date( $last['ts'], false ), $last_ip );

						if ( $country = crb_get_country_name( $last['cn'] ) ) {
							$lastus .= ' (' . $country . ')';
						}

						if ( $html_mode ) {
							$lastus = '<br/><br/>' . $lastus;
						}
						else {
							$lastus = "\n\n" . $lastus;
						}
					}

					$body = '<html lang="' . $lang . '">' . $body . $lastus . $footer . '</html>';

					if ( ! $this->transmit_email( $email, $subj, $body ) ) {
						$result = false;
					}
				}
			}
			else {

				$result = true;
				$body = $body . $footer;

				if ( $html_mode ) {
					$body = '<html lang="' . $lang . '">' . $body . '</html>';
				}

				foreach ( $to_list as $email ) {
					if ( ! $this->transmit_email( $email, $subj, $body ) ) {
						$result = false;
					}
				}
			}
		}

		$this->disable_smtp();

		remove_filter('wp_mail_content_type', 'cerber_enable_html');

		remove_action( 'wp_mail_failed', array( $this, 'process_email_errors' ) );

		$params = array( 'type' => $type, 'IP' => $ip, 'to' => $to, 'subject' => $subj );

		if ( $result ) {
			do_action( 'cerber_notify_sent', $body, $params );

			if ( ! $this->error ) { // Delete error occurred during a previous sending only
				CRB_Issues::delete_item( self::SEND_ISSUE_CODE);
			}
		}
		else {
			do_action( 'cerber_notify_fail', $body, $params );
		}

		return $result;
	}

	/**
	 * Send out one email message.
	 * If SMTP sending has failed, use generic wp_mail() without SMTP.
	 *
	 * @param string|string[] $email
	 * @param string $subj
	 * @param string $body
	 *
	 * @return bool
	 *
	 * @since 9.5.5.1
	 */
	private function transmit_email( $email, $subj, $body ) {

		crb_load_dependencies( 'wp_mail' );  // It's crucial in some configurations

		if ( ( ! $result = wp_mail( $email, $subj, $body ) )
		     && $this->smpt_enabled ) {

			$this->no_smtp = true;

			if ( ! $result = wp_mail( $email, $subj, $body ) ) {
				$this->no_smtp = false;
			}
		}

		return $result;
	}

	/**
	 * @param PHPMailer $pm
	 *
	 * @return void
	 *
	 * @since 8.9.6.3
	 */
	public function set_smtp_credentials( $pm ) {
		if ( ! lab_lab()
		     || $this->no_smtp ) {
			return;
		}

		$config = crb_get_settings();

		$pm->isSMTP();
		$pm->SMTPAuth = true;
		$pm->Timeout = 5;
		$pm->Host = $config['smtp_host'];
		$pm->Port = $config['smtp_port'];
		$pm->Username = $config['smtp_user'];
		$pm->Password = $config['smtp_pwd'];

		// For better deliverability we use "smtp_user"
		$pm->From = ( $config['smtp_from'] ) ?: $config['smtp_user'];

		if ( $config['smtp_from_name'] ) {
			$pm->FromName = $config['smtp_from_name'];
		}

		if ( $config['smtp_encr'] ) {
			$pm->SMTPSecure = $config['smtp_encr'];
		}

		$this->save_mailer( array( &$pm ) );

	}

	/**
	 * Set up hooks to configure PHPMailer if SMTP configured
	 *
	 * @return void
	 *
	 * @since 9.5.5.1
	 */
	private function enable_smtp() {
		if ( ! $this->smpt_enabled = (bool) crb_get_settings( 'use_smtp' ) ) {
			return;
		}

		add_action( 'phpmailer_init', array( $this, 'set_smtp_credentials' ) );
	}

	/**
	 * Remove our hooks
	 *
	 * @return void
	 *
	 * @since 9.5.5.1
	 */
	private function disable_smtp() {
		if ( ! $this->smpt_enabled ) {
			return;
		}

		remove_action( 'phpmailer_init', array( $this, 'set_smtp_credentials' ) );

		// Warn the website admin if the wp_mail() function is redefined somewhere or/and our SMTP settings were not used

		if ( is_admin() && crb_get_query_params( 'cerber_admin_do' ) ) {
			$mailer = $this->mailer;

			if ( ! ( $mailer instanceof PHPMailer )
			     || $mailer->Host != crb_get_settings( 'smtp_host' )
			     || $mailer->Port != crb_get_settings( 'smtp_port' )
			     || $mailer->Username != crb_get_settings( 'smtp_user' ) ) {
				cerber_admin_notice( "Warning: The WP Cerber SMTP settings were not used while sending email. They can be altered by another plugin." );
				$alien = true;
			}
			else {
				$alien = false;
			}

			if ( $alien && $mailer->Host ) {
				cerber_admin_notice( 'Warning: The email was sent using the host ' . $mailer->Host . ' and the user ' . $mailer->Username );
			}
		}
	}

	/**
	 * Saves and provides access to last used PHPMailer configuration
	 *
	 * @param PHPMailer $pm
	 *
	 * @return PHPMailer|null
	 *
	 * @since 8.9.6.4
	 */
	private function save_mailer( $pm = null ) {

		if ( isset( $pm[0] ) ) {
			$this->mailer = $pm[0];
		}

		return $this->mailer;
	}

	/**
	 * A wp_mail() error handler
	 *
	 * @param WP_Error $error
	 *
	 * @return void
	 *
	 * @since 9.5.5.1
	 */
	public function process_email_errors( $error ) {

		$this->error = true;

		if ( ! $error instanceof WP_Error ) {
			return;
		}

		$mailer_data = $error->get_error_data();
		if ( ! is_array( $mailer_data ) ) {
			$mailer_data = array();
		}

		$exception_code = $mailer_data['phpmailer_exception_code'] ?? '';

		if ( is_admin() && crb_get_query_params( 'cerber_admin_do' ) ) {
			cerber_admin_notice( strip_tags( $error->get_error_message() ) . ' (' . $exception_code . ')' );

			return;
		}

		$to = $mailer_data['to'] ?? array();
		if ( ! is_array( $to ) ) {
			$to = array( $to );
		}

		$smtp_host = '';
		$smtp_user = '';
		if ( $this->mailer instanceof PHPMailer ) {
			$smtp_host = (string) $this->mailer->Host;
			$smtp_user = (string) $this->mailer->Username;
		}

		$context_data = array(
			'format_version' => 1,
			'ts'             => time(),
			'remote_ip'      => cerber_get_remote_ip(),
			'error_message'  => strip_tags( $error->get_error_message() ),
			'exception_code' => $exception_code,
			'smtp_host'      => $smtp_host,
			'smtp_user'      => $smtp_user,
			'to'             => $to,
			'subject'        => (string) ( $mailer_data['subject'] ?? '' ),
		);

		CRB_Issues::add( self::SEND_ISSUE_CODE,	__( 'An error occurred while sending email. Security alerts and reports may not reach you. Check email notification settings and recipient addresses.', 'wp-cerber' ),
			array(
				'type'    => CRB_Issues::TYPE_EVENT,
				'context' => array(
					'mailer_data' => $context_data,
				),
			)
		);

	}


	/**
	 * Check sending limits and disable channels if it's needed
	 *
	 * @param array $channels
	 *
	 * @return array
	 *
	 * @since 8.9.6.1
	 *
	 */
	private function check_limits( $channels ) {
		static $ref = array( 'email' => 'emailrate', 'pushbullet' => 'pbrate' );

		if ( ! lab_lab() ) {
			$ref ['pushbullet'] = 'emailrate';
		}

		$limits = array_filter( array_intersect_key( crb_get_settings(), array_flip( $ref ) ) );

		if ( ! $limits ) {
			return $channels;
		}

		$sent = cerber_get_set( '_cerber_last_send' );

		if ( empty( $sent ) ) {
			return $channels;
		}

		foreach ( $ref as $channel_id => $key ) {
			$rate = absint( $limits[ $key ] ?? 0 );
			if ( $rate && ( $sent[ $channel_id ] ?? 0 ) > ( time() - 3600 / $rate ) ) {
				$channels[ $channel_id ] = 0; // Do not send
			}
		}

		return $channels;
	}
}
