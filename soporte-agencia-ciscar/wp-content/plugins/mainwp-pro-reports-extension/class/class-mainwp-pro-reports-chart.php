<?php

class MainWP_Pro_Reports_Chart {

	private static $instance = null;

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// contructor.
		require_once MAINWP_PRO_REPORTS_PLUGIN_DIR . 'libs/quickchart/QuickChart.php';
	}

	public function fetch_chart_url( $chart_data ) {
		$chart_src = '';
		if ( class_exists( '\QuickChart' ) ) {
			$qc        = new \QuickChart(
				array(
					'width'   => 850,
					'height'  => 350,
					'version' => 4,
				)
			);
			$chart_key = MainWP_Pro_Reports_Key::get_instance()->get_key();
			if ( ! empty( $chart_key ) ) {
				try {
					$qc->setApiKey( $chart_key );
					$qc->setConfig( wp_json_encode( $chart_data ) );
					$chart_src = $qc->getShortUrl();
				} catch ( \Exception $e ) {
					MainWP_Pro_Reports_Utility::log_debug( 'fetch reports chart :: [Error=' . $e->getMessage() . ']' );
				}
			}
		}
		return $chart_src;
	}
}
