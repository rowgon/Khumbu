<?php

class MainWP_Pro_Reports_Data {

	private static $buffer = array();

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// construct.
	}

	// to gathering GA tokens values
	public function get_ext_tokens_ga( $site_id, $start_date, $end_date, $chart = false ) {

		// ===============================================================
		// enym new
		// $end_date = strtotime("-1 day", time());
		// $start_date = strtotime( '-31 day', time() ); //31 days is more robust than "1 month" and this must match steprange in MainWPGA.class.php
		// ===============================================================

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}
		$uniq = 'ga_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$result = apply_filters( 'mainwp_ga_get_data', $site_id, $start_date, $end_date, $chart );
		$output = array(
			'ga.visits'         => 'N/A',
			'ga.pageviews'      => 'N/A',
			'ga.pages.visit'    => 'N/A',
			'ga.bounce.rate'    => 'N/A',
			'ga.new.visits'     => 'N/A',
			'ga.avg.time'       => 'N/A',
			'ga.visits.chart'   => 'N/A',
			'ga.visits.maximum' => 'N/A',
			'ga.users'          => 'N/A',
			'ga.new.users'      => 'N/A',
			'ga.startdate'      => 'N/A',
			'ga.enddate'        => 'N/A',
		);

		if ( ! empty( $result ) && is_array( $result ) ) {
			$custom_date_format = apply_filters( 'mainwp-ga-chart-custom-date', false );

			if ( isset( $result['stats_int'] ) ) {
				$values                   = $result['stats_int'];
				$output['ga.visits']      = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:sessions'] ) ) ? $values['aggregates']['ga:sessions'] : 'N/A';
				$output['ga.pageviews']   = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:pageviews'] ) ) ? $values['aggregates']['ga:pageviews'] : 'N/A';
				$output['ga.pages.visit'] = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:pageviewsPerSession'] ) ) ? self::format_stats_values( $values['aggregates']['ga:pageviewsPerSession'], true, false ) : 'N/A';
				$output['ga.bounce.rate'] = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:bounceRate'] ) ) ? self::format_stats_values( $values['aggregates']['ga:bounceRate'], true, true ) : 'N/A';
				$output['ga.new.visits']  = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:percentNewSessions'] ) ) ? self::format_stats_values( $values['aggregates']['ga:percentNewSessions'], true, true ) : 'N/A';
				$output['ga.avg.time']    = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:avgSessionDuration'] ) ) ? self::format_stats_values( $values['aggregates']['ga:avgSessionDuration'], false, false, true ) : 'N/A';
				$output['ga.users']       = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:Users'] ) ) ? $values['aggregates']['ga:Users'] : 'N/A';
				$output['ga.new.users']   = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:newUsers'] ) ) ? $values['aggregates']['ga:newUsers'] : 'N/A';
			}

			// ===============================================================
			// enym new   requires change in mainWPGA.class.php in Ga extension [send pure graph data in array]
			if ( $chart && ! empty( $result['stats_graphdata'] ) ) {
				// INTERVALL chxr=1,1,COUNTALLVALUES
				$intervalls         = '1,0,0';
				$maximum_value      = 0;
				$maximum_value_date = '';
				// MAX DIMENSIONS chds=0,HIGHEST*2
				foreach ( $result['stats_graphdata'] as $k => $v ) {
					if ( $v['1'] > $maximum_value ) {
						$maximum_value      = $v['1'];
						$maximum_value_date = $v['0'];
					}
				}

				$vertical_max = $maximum_value + 1;

				// DATA chd=t:1,2,3,4,5,6,7,8,9,10,11,12,13,14|
				// $graph_values = '';
				// foreach ( $result['stats_graphdata'] as $arr ) {
				// 	$graph_values .= $arr['1'] . ',';
				// }
				// $graph_values = trim( $graph_values, ',' );

				// AXISLEGEND chd=t:1.1|2.1|3.1 ...
				$graph_dates = '';
				$graph_values_display = '';

				$step = 1;

				if ( 14 < count( $result['stats_graphdata'] ) ) {
					$step = intdiv( count( $result['stats_graphdata'] ), 14 );
				}

				$nro = 1;
				foreach ( $result['stats_graphdata'] as $arr ) {
					$nro = $nro + 1;
					if ( 0 == ( $nro % $step ) ) {

						$teile = explode( ' ', $arr['0'] );

						$format_date = '';

						if ( ! $custom_date_format ) {
							if ( isset( $arr[2] ) ) {
								$format_date = $arr[2] . '|';
							} else {
								$format_date = $teile[0] . ' ' . $teile[1] . '|';
							}
						} else {
							$format_date = $teile[0] . ' ' . $teile[1] . '|';
						}

						$format_date  = apply_filters( 'mainwp-reports-ga-chart-format-date', $format_date, $teile[0], $teile[1] );
						$graph_dates .= $format_date;
						$graph_values_display .= $arr[1] . '|';
					}
				}

				$graph_dates = trim( $graph_dates, '|' );
				$graph_values_display = trim( $graph_values_display, '|' );

				// SCALE chxr=1,0,HIGHEST*2
				$scale = '1,0,' . $vertical_max;

				// WIREFRAME chg=0,10,1,4
				$wire = '0,25,0,0';

				// COLORS
				$barcolor  = '18a4e0';
				$fillcolor = '18a4e088';
				// LINEFORMAT chls=1,0,0
				$lineformat = '2,0,0';

				// TITLE
				$chtitle = esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' ) . ' (' . MainWP_Pro_Reports_Utility::format_datestamp( $start_date, true ) . ' - ' . MainWP_Pro_Reports_Utility::format_datestamp( $end_date, true ) . ')';

				$data_graph = explode( '|', $graph_values_display );

				$charts_config = array(
					'type'    => 'line',
					'data'    => array(
						'labels'   => explode( '|', $graph_dates ),
						'datasets' => array(
							array(
								'label'           => 'Visits',
								'data'            => $data_graph,
								'fill'            => true,
								'borderColor'     => '#18a4e0',
								'borderWidth'     => 2,
								'backgroundColor' => '#18a4e088',
							),
						),
					),
					'options' => array(
						'plugins' => array( // version 4.
							'title'  => array(
								'display' => true,
								'text'    => $chtitle,
							),
							'legend' => array(
								'display' => false,
							),
						),
						'scales'  => array(
							'y' => array(
								'beginAtZero' => true, // version 4.
							),
						),
					),
				);

				MainWP_Pro_Reports_Utility::log_debug( '[chart config=' . print_r( $charts_config, true ) . ']' ); //phpcs:ignore -- ok.

				$chart_src                 = MainWP_Pro_Reports_Chart::get_instance()->fetch_chart_url( $charts_config );
				$output['ga.visits.chart'] = '<img style="background:none;padding:20px;width:100%" src="' . $chart_src . '">';

				$date1 = explode( ' ', $maximum_value_date );

				// to fix warning.
				if ( empty( $date1[1] ) ) {
					$date1[1] = '';
				}

				$display_maximum_value_date = apply_filters( 'mainwp_client_reports_ga_visits_maximum_date', false, $date1[1], $date1[0] ); // day.month
				if ( empty( $display_maximum_value_date ) ) {
					$display_maximum_value_date = $date1[0] . ' ' . $date1[1];
				}
				$output['ga.visits.maximum'] = $maximum_value . ' (' . $display_maximum_value_date . ')';
			}

			$output['ga.startdate'] = MainWP_Pro_Reports_Utility::format_datestamp( $start_date, true );
			$output['ga.enddate']   = MainWP_Pro_Reports_Utility::format_datestamp( $end_date, true );
			// }
			// enym end
			// ===============================================================
		}
		self::$buffer[ $uniq ] = $output;
		return $output;
	}

	// to gathering Fathom tokens values
	public function get_ext_tokens_fathom( $site_id, $start_date, $end_date, $chart = false ) {

		// ===============================================================
		// enym new
		// $end_date = strtotime(" - 1 day", time());
		// $start_date = strtotime( '-31 day', time() ); //31 days is more robust than "1 month" and this must match steprange in MainWPGA.class.php
		// ===============================================================

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}
		$uniq = 'fathom_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$result = apply_filters( 'mainwp_fathom_get_data', $site_id, $start_date, $end_date, $chart );
		$output = array(
			'fathom.visits'         => 'N/A',
			'fathom.visitors'       => 'N/A',
			'fathom.pageviews'      => 'N/A',
			'fathom.avg.time'       => 'N/A',
			'fathom.bounce.rate'    => 'N/A',
			'fathom.visits.chart'   => 'N/A',
			'fathom.visits.maximum' => 'N/A',
			'fathom.startdate'      => 'N/A',
			'fathom.enddate'        => 'N/A',
		);

		if ( ! empty( $result ) && is_array( $result ) ) {
			$custom_date_format = apply_filters( 'mainwp-fathom-chart-custom-date', false );

			if ( isset( $result['stats_int'] ) ) {
				$values                       = $result['stats_int'];
				$output['fathom.visits']      = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['visits'] ) ) ? $values['aggregates']['visits'] : 'N/A';
				$output['fathom.visitors']    = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['uniques'] ) ) ? $values['aggregates']['uniques'] : 'N/A';
				$output['fathom.pageviews']   = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['pageviews'] ) ) ? $values['aggregates']['pageviews'] : 'N/A';
				$output['fathom.avg.time']    = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['avg_duration'] ) ) ? self::format_stats_values( $values['aggregates']['avg_duration'], false, false, true ) : 'N/A';
				$output['fathom.bounce.rate'] = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['bounce_rate'] ) ) ? self::format_stats_values( $values['aggregates']['bounce_rate'], true, true ) : 'N/A';
			}

			// ===============================================================
			// enym new   requires change in mainWPGA.class.php in Ga extension [send pure graph data in array]
			if ( $chart && ! empty( $result['stats_graphdata'] ) ) {
				// INTERVALL chxr=1,1,COUNTALLVALUES
				$intervalls         = '1,1,0';
				$maximum_value      = 0;
				$maximum_value_date = '';
				// MAX DIMENSIONS chds=0,HIGHEST*2
				foreach ( $result['stats_graphdata'] as $k => $v ) {
					if ( $v['1'] > $maximum_value ) {
						$maximum_value      = $v['1'];
						$maximum_value_date = $v['0'];
					}
				}

				$vertical_max = $maximum_value + 1;

				// DATA chd=t:1,2,3,4,5,6,7,8,9,10,11,12,13,14|
				// $graph_values = '';
				// foreach ( $result['stats_graphdata'] as $arr ) {
				// 	$graph_values .= $arr['1'] . ',';
				// }
				// $graph_values = trim( $graph_values, ',' );

				// AXISLEGEND chd=t:1.1|2.1|3.1 ...
				$graph_dates = '';
				$graph_values_display = '';

				$step = 1;

				if ( 14 < count( $result['stats_graphdata'] ) ) {
					$step = intdiv( count( $result['stats_graphdata'] ), 14 );
				}

				$nro = 1;
				foreach ( $result['stats_graphdata'] as $arr ) {
					$nro = $nro + 1;
					if ( 0 == ( $nro % $step ) ) {

						$teile = explode( ' ', $arr['0'] );

						$format_date = '';

						if ( ! $custom_date_format ) {
							if ( isset( $arr[2] ) ) {
								$format_date = $arr[2] . '|';
							} else {
								$format_date = $teile[0] . ' ' . $teile[1] . '|';
							}
						} else {
							$format_date = $teile[0] . ' ' . $teile[1] . '|';
						}

						$format_date  = apply_filters( 'mainwp-reports-fathom-chart-format-date', $format_date, $teile[0], $teile[1] );
						$graph_dates .= $format_date;
						$graph_values_display .= $arr[1] . '|';
					}
				}

				$graph_dates = trim( $graph_dates, '|' );
				$graph_values_display = trim( $graph_values_display, '|' );

				// SCALE chxr=1,0,HIGHEST*2
				$scale = '1,0,' . $vertical_max;

				// WIREFRAME chg=0,10,1,4
				$wire = '0,25,0,0';

				// COLORS
				$barcolor  = '18a4e0';
				$fillcolor = '18a4e088';
				// LINEFORMAT chls=1,0,0
				$lineformat = '2,0,0';

				$chtitle = esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' ) . ' (' . MainWP_Pro_Reports_Utility::format_datestamp( $start_date, true ) . ' - ' . MainWP_Pro_Reports_Utility::format_datestamp( $end_date, true ) . ')';

				$data_graph = explode( '|', $graph_values_display );

				$charts_config = array(
					'type'    => 'line',
					'data'    => array(
						'labels'   => explode( '|', $graph_dates ),
						'datasets' => array(
							array(
								'label'           => 'Visits',
								'data'            => $data_graph,
								'fill'            => true,
								'borderColor'     => '#18a4e0',
								'borderWidth'     => 2,
								'backgroundColor' => '#18a4e088',
							),
						),
					),
					'options' => array(
						'plugins' => array( // version 4.
							'title'  => array(
								'display' => true,
								'text'    => $chtitle,
							),
							'legend' => array(
								'display' => false,
							),
						),
						'scales'  => array(
							'y' => array(
								'beginAtZero' => true, // version 4.
							),
						),
					),
				);

				MainWP_Pro_Reports_Utility::log_debug( '[chart config=' . print_r( $charts_config, true ) . ']' ); //phpcs:ignore -- ok.

				$chart_src                     = MainWP_Pro_Reports_Chart::get_instance()->fetch_chart_url( $charts_config );
				$output['fathom.visits.chart'] = '<img style="background:none;padding:20px;width:100%" src="' . $chart_src . '">';

				$date1 = explode( ' ', $maximum_value_date );

				// to fix warning.
				if ( empty( $date1[1] ) ) {
					$date1[1] = '';
				}

				$display_maximum_value_date = apply_filters( 'mainwp_client_reports_fathom_visits_maximum_date', false, $date1[1], $date1[0] ); // day.month
				if ( empty( $display_maximum_value_date ) ) {
					$display_maximum_value_date = $date1[0] . ' ' . $date1[1];
				}
				$output['fathom.visits.maximum'] = $maximum_value . ' (' . $display_maximum_value_date . ')';
			}

			$output['fathom.startdate'] = MainWP_Pro_Reports_Utility::format_datestamp( $start_date, true );
			$output['fathom.enddate']   = MainWP_Pro_Reports_Utility::format_datestamp( $end_date, true );
			// }
			// enym end
			// ===============================================================
		}
		self::$buffer[ $uniq ] = $output;
		return $output;
	}

	public function get_ext_tokens_piwik( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'pw_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$values = apply_filters( 'mainwp_piwik_get_data', $site_id, $start_date, $end_date );

		$output                      = array();
		$output['piwik.visits']      = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_visits'] ) ) ? $values['aggregates']['nb_visits'] : 'N/A';
		$output['piwik.pageviews']   = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_actions'] ) ) ? $values['aggregates']['nb_actions'] : 'N/A';
		$output['piwik.pages.visit'] = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_actions_per_visit'] ) ) ? $values['aggregates']['nb_actions_per_visit'] : 'N/A';
		$output['piwik.bounce.rate'] = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['bounce_rate'] ) ) ? $values['aggregates']['bounce_rate'] : 'N/A';
		$output['piwik.new.visits']  = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_uniq_visitors'] ) ) ? $values['aggregates']['nb_uniq_visitors'] : 'N/A';
		$output['piwik.avg.time']    = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['avg_time_on_site'] ) ) ? self::format_stats_values( $values['aggregates']['avg_time_on_site'], false, false, true ) : 'N/A';
		self::$buffer[ $uniq ]       = $output;

		return $output;
	}

	public function get_ext_tokens_aum( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false; }
		$uniq = 'aum_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$values = apply_filters( 'mainwp_aum_get_data', $site_id, $start_date, $end_date );
		// print_r($values);
		$output                           = array();
		$output['aum.alltimeuptimeratio'] = ( is_array( $values ) && isset( $values['aum.alltimeuptimeratio'] ) && 'N/A' !== $values['aum.alltimeuptimeratio'] ) ? $values['aum.alltimeuptimeratio'] . '%' : 'N/A';
		$output['aum.uptime7']            = ( is_array( $values ) && isset( $values['aum.uptime7'] ) && 'N/A' !== $values['aum.uptime7'] ) ? $values['aum.uptime7'] . '%' : 'N/A';
		$output['aum.uptime15']           = ( is_array( $values ) && isset( $values['aum.uptime15'] ) && 'N/A' !== $values['aum.uptime15'] ) ? $values['aum.uptime15'] . '%' : 'N/A';
		$output['aum.uptime30']           = ( is_array( $values ) && isset( $values['aum.uptime30'] ) && 'N/A' !== $values['aum.uptime30'] ) ? $values['aum.uptime30'] . '%' : 'N/A';
		$output['aum.uptime45']           = ( is_array( $values ) && isset( $values['aum.uptime45'] ) && 'N/A' !== $values['aum.uptime45'] ) ? $values['aum.uptime45'] . '%' : 'N/A';
		$output['aum.uptime60']           = ( is_array( $values ) && isset( $values['aum.uptime60'] ) && 'N/A' !== $values['aum.uptime60'] ) ? $values['aum.uptime60'] . '%' : 'N/A';
		$output['aum.stats']              = ( is_array( $values ) && isset( $values['aum.stats'] ) && 'N/A' !== $values['aum.stats'] ) ? $values['aum.stats'] : 'N/A';

		self::$buffer[ $uniq ] = $output;

		return $output;
	}

	public function get_ext_tokens_woocomstatus( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false; }
		$uniq = 'wcstatus_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$values     = apply_filters( 'mainwp_woocomstatus_get_data', $site_id, $start_date, $end_date );
		$top_seller = 'N/A';
		if ( is_array( $values ) && isset( $values['wcomstatus.topseller'] ) ) {
			$top = $values['wcomstatus.topseller'];
			if ( is_array( $top ) && isset( $top['name'] ) ) {
				$top_seller = $top['name'];
			}
		}

		// print_r($values);
		$output                                  = array();
		$output['wcomstatus.sales']              = ( is_array( $values ) && isset( $values['wcomstatus.sales'] ) ) ? $values['wcomstatus.sales'] : 'N/A';
		$output['wcomstatus.topseller']          = $top_seller;
		$output['wcomstatus.awaitingprocessing'] = ( is_array( $values ) && isset( $values['wcomstatus.awaitingprocessing'] ) ) ? $values['wcomstatus.awaitingprocessing'] : 'N/A';
		$output['wcomstatus.onhold']             = ( is_array( $values ) && isset( $values['wcomstatus.onhold'] ) ) ? $values['wcomstatus.onhold'] : 'N/A';
		$output['wcomstatus.lowonstock']         = ( is_array( $values ) && isset( $values['wcomstatus.lowonstock'] ) ) ? $values['wcomstatus.lowonstock'] : 'N/A';
		$output['wcomstatus.outofstock']         = ( is_array( $values ) && isset( $values['wcomstatus.outofstock'] ) ) ? $values['wcomstatus.outofstock'] : 'N/A';
		self::$buffer[ $uniq ]                   = $output;
		return $output;
	}

	public function get_ext_tokens_pagespeed( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'pagespeed_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_pagespeed_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}


	/**
	 * Virusdie data.
	 *
	 * @param int    $site_id Child site ID.
	 * @param string $start_date Report start date.
	 * @param string $end_date Report end date.
	 *
	 * @return array|false|mixed Return Virusdie data or FALSE on failure.
	 */
	public function get_ext_tokens_virusdie( $site_id, $start_date, $end_date, $sections, $other_tokens ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'virusdie_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_virusdie_get_data', array(), $site_id, $start_date, $end_date, $sections, $other_tokens );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	public function get_ext_tokens_vulnerable( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'vulnerable_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_vulnerable_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	public function get_ext_tokens_lighthouse( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'lighthouse_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_lighthouse_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;

		return $data;
	}

	public function get_ext_tokens_atarim( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'atarim_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_atarim_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	public function get_ext_tokens_jetpack_protect( $site_id, $end_date ) {

		if ( ! $site_id ) {
			return false;
		}

		$uniq = 'jetpack_protect_' . $site_id . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_jetpack_protect_get_reports_data', array(), $site_id, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}


	public function get_ext_tokens_jetpack_scan( $site_id, $end_date ) {

		if ( ! $site_id ) {
			return false;
		}

		$uniq = 'jetpack_scan_' . $site_id . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_jetpack_scan_get_reports_data', array(), $site_id, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	public function get_ext_tokens_domainmonitor( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'domainmonitor_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_domain_monitor_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	public function get_ext_tokens_sslmonitor( $site_id, $start_date, $end_date ) {

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'sslmonitor_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_ssl_monitor_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
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

	private function format_stats_values( $value, $round = false, $perc = false, $showAsTime = false ) {
		if ( $showAsTime ) {
			$value = MainWP_Pro_Reports_Utility::sec2hms( $value );
		} else {
			if ( $round ) {
				$value = round( $value, 2 );
			}
			if ( $perc ) {
				$value = $value . '%';
			}
		}
		return $value;
	}
}
