<?php
/**
 * Pro Reports logs connector class.
 *
 */

use MainWP\Dashboard\Module\Log\Log_Connector;

defined( 'ABSPATH' ) || exit;

/**
 * Class Connector_Client.
 *
 * @package MainWP\Dashboard
 */
class MainWP_Pro_Reports_Log_Connector extends Log_Connector {

	/** @var string Connector slug. */
	public $name = 'pro-reports';


	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public $actions = array(
		'mainwp_pro_reports_log_report_action',
	);

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public function get_label() {
		return esc_html__( 'Pro Reports', 'mainwp-pro-reports-extension' );
	}

	/**
	 * Return translated action term labels
	 *
	 * @return array Action terms label translation
	 */
	public function get_action_labels() {
		return array(
			'generated' => esc_html__( 'Generated', 'mainwp-pro-reports-extension' ),
			'send'      => esc_html__( 'Send', 'mainwp-pro-reports-extension' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		return array(
			'report' => esc_html__( 'Report', 'mainwp-pro-reports-extension' ),
		);
	}

	/**
	 * Register log data.
	 *
	 */
	public function register() {
		parent::register();
	}

	/**
	 * Log Pro Reports actions
	 *
	 * @action mainwp_pro_reports_log_action
	 *
	 * @param string $action  report action.
	 * @param object   $website website data.
	 * @param array   $data report data.
	 */
	public function callback_mainwp_pro_reports_log_report_action( $action, $website, $data ) {
		if ( ! in_array( $action, array( 'generated', 'send' ), true ) || empty( $website ) || ! is_array( $data ) ) {
			return;
		}

		$state = null;

		$args = array(
			'subject' => isset( $data['subject'] ) ? $data['subject'] : '',
		);
		if ( 'generated' === $action ) {
			// translators: 1 - report title, 2 - website url.
			$message         = esc_html__( '%1$s', 'mainwp-pro-reports-extension' );
			$args['siteurl'] = isset( $website->url ) ? $website->url : '';
			$args            = array(
				'title'   => isset( $data['title'] ) ? $data['title'] : '',
				'siteurl' => ! empty( $website->url ) ? $website->url : '',
			);
			if ( ! empty( $data['extra_info'] ) && is_array( $data['extra_info'] ) ) {
				$args['extra_info'] = wp_json_encode( $data['extra_info'] );
			}
			$state = 1;
		} elseif ( 'send' === $action ) {
			// translators: 1 - send subject, 2 - to email, 3 - website url.
			$message = esc_html__( '%1$s', 'mainwp-pro-reports-extension' );
			$args    = array(
				'subject' => isset( $data['subject'] ) ? $data['subject'] : '',
				'email'   => isset( $data['email'] ) ? $data['email'] : '',
				'siteurl' => ! empty( $website->url ) ? $website->url : '',
			);
			$state   = ! empty( $data['success'] ) ? 1 : 0;
		} else {
			return;
		}

		$args['site_name'] = ! empty( $website->name ) ? $website->name : '';

		$this->log(
			$message,
			$args,
			$website->id,
			'report',
			$action,
			$state
		);
	}
}
