<?php
/**
 * Class MainWP_Pro_Reports_Rest_Handler.
 *
 * @package MainWP/Extensions
 */
class MainWP_Pro_Reports_Rest_Handler {

	/**
	 * Protected static variable to hold the single instance of the class.
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Create public static instance.
	 *
	 * @static
	 * @return self::$instance
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Method get_rest_section_tokens_by_context().
	 */
	public function get_rest_section_tokens_by_context( $context, $action = false ) {

		$sections_context_tokens = array(
			'wordpress' => array(  //phpcs:ignore -- to fix wordpress.
				'updated',
				'pending',
			),
			'plugins'     => array(
				'installed',
				'activated',
				'deactivated',
				'updated',
				'edited',
				'deleted',
				'abandoned',
				'pending',
			),
			'themes'      => array(
				'installed',
				'activated',
				'updated',
				'edited',
				'deleted',
				'abandoned',
				'pending',
			),
			'posts'       => array(
				'created',
				'updated',
				'trashed',
				'deleted',
				'restored',
			),
			'pages'       => array(
				'created',
				'updated',
				'trashed',
				'deleted',
				'restored',
			),
			'comments'    => array(
				'created',
				'updated',
				'trashed',
				'deleted',
				'edited',
				'restored',
				'approved',
				'spam',
				'replied',
			),
			'users'       => array(
				'created',
				'updated',
				'deleted',
			),
			'menus'       => array(
				'created',
				'updated',
				'deleted',
			),
			'backups'     => array(
				'created',
			),
			'maintenance' => array(
				'process',
			),
			'sucuri'      => array(
				'checks',
			),
			'virusdie'    => array(
				'scans',
			),
			'wordfence'   => array(
				'scan',
			),
			'ithemes'     => array(
				'scan',
			),

		);

		$sec_token = '';
		if ( isset( $sections_context_tokens[ $context ] ) ) {
			if ( false === $action ) {
				return $sections_context_tokens[ $context ]; // rerturn all contexts of the section.
			} elseif ( ! empty( $action ) && in_array( $action, $sections_context_tokens[ $context ] ) ) {
				$sec_token = '[section.' . $context . '.' . $action . ']';
			}
		}
		return $sec_token;
	}

	/**
	 * Method get_rest_orther_section_tokens_by_context().
	 */
	public function get_rest_orther_section_tokens_by_context( $context ) {
		$sections_context_tokens = array(
			'virusdie' => array(  //phpcs:ignore -- to fix wordpress.
				'section_token'          => array(
					'[section.virusdie.scans]',
				),
				'section_content_tokens' => array(
					'[virusdie.scan.time]',
					'[virusdie.scan.date]',
					'[virusdie.scan.status]',
					'[virusdie.scan.details]',
				),
			),
			'abandoned' => array(
				'section_token'          => array(
					'[section.plugins.abandoned]',
					'[section.themes.abandoned]',
				),
				'section_content_tokens' => array(
					array(
						'[plugin.name]',
						'[plugin.abandoned.version]',
						'[plugin.abandoned.lastupdated]',
					),
					array(
						'[theme.name]',
						'[theme.abandoned.version]',
						'[theme.abandoned.lastupdated]',
					),
				),
			),
			'pending'   => array(
				'section_token'          => array(
					'[section.wordpress.pending]', // phpcs:ignore -- wordpress.
					'[section.plugins.pending]',
					'[section.themes.pending]',
				),
				'section_content_tokens' => array(
					array(
						'[wordpress.current.version]',
						'[wordpress.new.version]',
					),
					array(
						'[plugin.name]',
						'[plugin.current.version]',
						'[plugin.new.version]',
					),
					array(
						'[theme.name]',
						'[theme.current.version]',
						'[theme.new.version]',
					),
				),
			),
		);
		if ( isset( $sections_context_tokens[ $context ] ) ) {
			return $sections_context_tokens[ $context ];
		}
		return array();
	}


	/**
	 * Method get_rest_section_content_tokens().
	 */
	public function get_rest_section_content_tokens( $context, $action ) {

		$sections_content_tokens = array(
			'plugins'     => array(
				'default' => array(
					'plugin.name',
					'date',
					'time',
					'author',
					'slug',
					'utime',
				),
				'updated' => array(
					'plugin.old.version',
					'plugin.current.version',
				),
				// 'abandoned' => array(
				// 'name',
				// 'version',
				// 'lastupdated',
				// ),
				// 'pending'   => array(
				// 'name',
				// 'version',
				// 'lastupdated',
				// ),
			),
			'themes'      => array(
				'default' => array(
					'theme.name',
					'date',
					'time',
					'author',
					'slug',
					'utime',
				),
				'updated' => array(
					'theme.old.version',
					'theme.current.version',
				),
				// 'abandoned' => array(
				// 'name',
				// 'version',
				// 'lastupdated',
				// ),
				// 'pending'   => array(
				// 'name',
				// 'version',
				// 'lastupdated',
				// ),
			),
			'wordpress'   => array(
				'default' => array(
					'date',
					'time',
					'author',
				),
				'updated' => array(
					'wordpress.old.version',
					'wordpress.current.version',
				),
				// 'pending' => array(
				// 'wordpress.new.version', // data from dashboard, after sync, wp_updates.
				// 'wordpress.current.version', // data from dashboard, after sync, wpversion.
				// ),
			),
			'posts'       => array(
				'default' => array(
					'title',
					'date',
					'time',
					'author',
				),
			),
			'pages'       => array(
				'default' => array(
					'title',
					'date',
					'time',
					'author',
				),
			),
			'comments'    => array(
				'default' => array(
					'title',
					'date',
					'time',
					'author',
				),
			),
			'users'       => array(
				'default' => array(
					'user.name',
					'date',
					'time',
					'author',
					'role',
				),
			),
			'menus'       => array(
				'default' => array(
					'menu.title',
					'date',
					'time',
					'author',
				),
			),
			'backups'     => array(
				'default' => array(
					'type',
					'date',
					'time',
				),
			),
			'maintenance' => array(
				'default' => array(
					'result',
					'date',
					'time',
					'details',
				),
			),
			'sucuri'      => array(
				'default' => array(
					'date',
					'time',
					'status',
					'webtrust',
				),
			),
			'virusdie'    => array(
				'default' => array(
					'date',
					'time',
					'status',
					'details',
				),
			),
			'wordfence'   => array(
				'default' => array(
					'result',
					'date',
					'time',
					'details',
				),
			),
			'ithemes'     => array(
				'default' => array(
					'result',
					'date',
					'time',
					'details',
				),
			),

		);

		$sec_content_tokens = array();
		if ( isset( $sections_content_tokens[ $context ] ) ) {
			$section_contexts = $this->get_rest_section_tokens_by_context( $context ); // rerturn all contexts of the section.

			if ( ! empty( $section_contexts ) && ! empty( $action ) && in_array( $action, $section_contexts ) ) {
				$token_context = $context;
				if ( in_array( $token_context, array( 'plugins', 'themes', 'posts', 'pages', 'comments', 'users', 'menus', 'backups' ) ) ) {
					$token_context = substr( $token_context, 0, -1 ); // trip the last 's' character.
				}
				$loop_acts = array(
					'default',
					$action,
				);
				foreach ( $loop_acts as $act ) {
					if ( isset( $sections_content_tokens[ $context ][ $act ] ) ) {
						$data_sections = $sections_content_tokens[ $context ][ $act ];
						if ( ! empty( $data_sections ) ) {
							foreach ( $data_sections as $data_token ) {
								if ( strpos( $data_token, '.' ) ) {  // it is token full name.
									$sec_content_tokens[] = '[' . $data_token . ']';
								} else {
									$sec_content_tokens[] = '[' . $token_context . '.' . $action . '.' . $data_token . ']';
								}
							}
						}
					}
				}
			}
		}

		// add more tokens for rest api.
		if ( 'themes' === $context ) {
			if ( 'updated' === $action ) {
				$sec_content_tokens[] = '[theme.old.version]';
				$sec_content_tokens[] = '[theme.current.version]';
			}
		} elseif ( 'plugins' === $context ) {

		}

		return $sec_content_tokens;
	}


	/**
	 * Method get_rest_addition_sections_tokens().
	 */
	public function get_rest_addition_sections_tokens( $context, $action = '' ) {

		$orthers_contexts_tokens = array(
			'plugins'      => array(
				'plugin.installed.count',
				'plugin.activated.count',
				'plugin.deactivated.count',
				'plugin.updated.count',
				'plugin.edited.count',
				'plugin.deleted.count',
			),
			'themes'       => array(
				'theme.installed.count',
				'theme.activated.count',
				'theme.updated.count',
				'theme.edited.count',
				'theme.deleted.count',
			),
			'wordpress' => array(  //phpcs:ignore -- to fix wordpress.
				'wordpress.updated.count',
			),
			'posts'        => array(
				'post.created.count',
				'post.updated.count',
				'post.trashed.count',
				'post.deleted.count',
				'post.restored.count',
			),
			'pages'        => array(
				'page.created.count',
				'page.updated.count',
				'page.trashed.count',
				'page.deleted.count',
				'page.restored.count',
			),
			'comments'     => array(
				'comment.created.count',
				'comment.updated.count',
				'comment.trashed.count',
				'comment.deleted.count',
				'comment.edited.count',
				'comment.restored.count',
				'comment.approved.count',
				'comment.spam.count',
				'comment.replied.count',
			),
			'users'        => array(
				'user.created.count',
				'user.updated.count',
				'user.deleted.count',
			),
			'menus'        => array(
				'menu.created.count',
				'menu.updated.count',
				'menu.deleted.count',
			),
			'backups'      => array(
				'backup.created.count',
			),
			'maintenance'  => array(
				'maintenance.process.count',
			),
			'sucuri'       => array(
				'sucuri.checks.count',
			),
			'virusdie'     => array(
				'virusdie.scan.count',
			),
			'wordfence'    => array(
				'wordfence.scan.count',
			),
			'ithemes'      => array(
				'ithemes.scan.count',
			),
			'woocomstatus' => array(
				'wcomstatus.sales',
				'wcomstatus.topseller',
				'wcomstatus.awaitingprocessing',
				'wcomstatus.onhold',
				'wcomstatus.lowonstock',
				'wcomstatus.outofstock',
			),
			'pending'      => array(
				'plugin.pending.count',
				'theme.pending.count',
				'wordpress.pending.count',
			),
			'abandoned'    => array(
				'plugin.abandoned.count',
				'theme.abandoned.count',
			),
		);

		$orthers_tokens = array();
		if ( isset( $orthers_contexts_tokens[ $context ] ) ) {
			foreach ( $orthers_contexts_tokens[ $context ] as $orther_tokens ) {
				$token_context = $context;
				if ( in_array( $token_context, array( 'plugins', 'themes', 'posts', 'pages', 'comments', 'users', 'menus', 'backups' ) ) ) {
					$token_context = substr( $token_context, 0, -1 ); // trip the last 's' character.
				}
				if ( ! empty( $action ) ) {
					if ( false !== strpos( $orther_tokens, $token_context . '.' . $action ) ) {
						$orthers_tokens[] = '[' . $orther_tokens . ']';
					}
				} else {
					$orthers_tokens[] = '[' . $orther_tokens . ']';
				}
			}
		}
		return $orthers_tokens;
	}

	/**
	 * Method rest_valid_context()
	 *
	 * Check valid contexts.
	 *
	 * @param string $endpoint The endpoint request.
	 */
	public static function rest_valid_context( $endpoint ) {
		$contexts = self::get_rest_all_contexts(); // all contexts.
		return ! empty( $endpoint ) && in_array( $endpoint, $contexts );
	}


	/**
	 * Method get_rest_sections_contexts()
	 *
	 * Get sections contexts.
	 */
	public static function get_rest_sections_contexts() {
		return self::get_rest_report_contexts( 'section' ); // section contexts.
	}

	/**
	 * Method get_rest_all_contexts()
	 *
	 * Get all contexts.
	 */
	public static function get_rest_all_contexts() {
		return self::get_rest_report_contexts(); // all contexts.
	}

	/**
	 * Method get_rest_report_contexts()
	 */
	public static function get_rest_report_contexts( $type = 'all' ) {

		$sections_contexts = array(
			'plugins',
			'themes',
			'wordpress', //phpcs:ignore -- to fix wordpress.
			'posts',
			'pages',
			'comments',
			'users',
			'menus',
			'backups',
			'maintenance',
			'sucuri',
			'virusdie',
			'wordfence',
			'ithemes',
		);

		$other_contexts = array(
			'pagespeed',
			'woocomstatus',
			'vulnerable',
			'ga',
			'piwik',
			'lighthouse',
			'domainmonitor',
			'sslmonitor',
			'aum',
			'atarim',
			'protect', // jetpack.
			'scan', // jetpack.
			'website',
			'abandoned',
			'pending',
			'misc',
		);

		if ( 'section' === $type ) {
			return $sections_contexts;
		} elseif ( 'other' === $type ) {
			return $other_contexts;
		}

		return array_merge( $sections_contexts, $other_contexts );
	}

	/**
	 * Method get_rest_single_tokens_params_by_context()
	 *
	 * Get plugins report tokens.
	 *
	 * @param array $params Rest params.
	 */
	public function get_rest_single_tokens_params_by_context( $context ) {
		return array(
			'get_ga_tokens'                  => ( 'ga' === $context ) ? true : false,
			'get_ga_chart'                   => ( 'ga' === $context ) ? true : false,
			'get_piwik_tokens'               => ( 'piwik' === $context ) ? true : false,
			'get_aum_tokens'                 => ( 'aum' === $context ) ? true : false,
			'get_woocom_tokens'              => ( 'woocomstatus' === $context ) ? true : false,
			'get_pagespeed_tokens'           => ( 'pagespeed' === $context ) ? true : false,
			'get_vulnerable_tokens'          => ( 'vulnerable' === $context ) ? true : false,
			'get_lighthouse_tokens'          => ( 'lighthouse' === $context ) ? true : false,
			'get_domainmonitor_tokens'       => ( 'domainmonitor' === $context ) ? true : false,
			'get_sslmonitor_tokens'          => ( 'sslmonitor' === $context ) ? true : false,
			'get_atarim_tokens'              => ( 'atarim' === $context ) ? true : false,
			'get_jp_protect_tokens'          => ( 'protect' === $context ) ? true : false,
			'get_jp_scan_tokens'             => ( 'scan' === $context ) ? true : false,
			'get_translation_pending_tokens' => ( 'pending' === $context ) ? true : false,
			'get_website_tokens'             => ( 'website' === $context ) ? true : false,
			'get_other_tokens'               => ( 'misc' === $context ) ? true : false,
		);
	}


	/**
	 * Method get_rest_params_other_sections()
	 *
	 * Get plugins report tokens.
	 *
	 * @param array $params Rest params.
	 */
	public function get_rest_params_other_sections( $context ) {

		if ( ! in_array( $context, array( 'virusdie', 'abandoned', 'pending' ) ) ) {
			return array();
		}

		return array(
			'get_virusdie_tokens'            => ( 'virusdie' === $context ) ? true : false,
			'get_plugins_abandoned_tokens'   => ( 'abandoned' === $context ) ? true : false,
			'get_themes_abandoned_tokens'    => ( 'abandoned' === $context ) ? true : false,
			'get_plugins_pending_tokens'     => ( 'pending' === $context ) ? true : false,
			'get_themes_pending_tokens'      => ( 'pending' === $context ) ? true : false,
			'get_wp_pending_tokens'          => ( 'pending' === $context ) ? true : false,
			'get_translation_pending_tokens' => ( 'pending' === $context ) ? true : false,
		);
	}

	/**
	 * Method get_rest_report_tokens_values()
	 *
	 * Get plugins report tokens.
	 *
	 * @param array $params Rest params.
	 */
	public function get_rest_report_tokens_values( $params ) {

		$context   = isset( $params['context'] ) ? $params['context'] : '';
		$action    = isset( $params['action'] ) && ! empty( $params['action'] ) ? $params['action'] : false;
		$date_from = isset( $params['start_date'] ) ? $params['start_date'] : 0;
		$date_to   = isset( $params['end_date'] ) ? $params['end_date'] : 0;
		$website   = isset( $params['website'] ) ? $params['website'] : 0;

		if ( isset( $params['multi_tokens'] ) ) {
			$data = MainWP_Pro_Reports::get_instance()->filter_report_content( $params['multi_tokens'], false, $website, $date_from, $date_to, 'raw' );
			return $data;
		}

		$sections_tokens = $this->get_rest_section_tokens_by_context( $context, $action );

		$sections     = array();
		$other_tokens = array();

		if ( ! empty( $sections_tokens ) ) {
			$section_content_tokens = $this->get_rest_section_content_tokens( $context, $action );
			$sections               = array(
				'body' => array(
					'section_token'          => array( $sections_tokens ),
					'section_content_tokens' => array( $section_content_tokens ),
				),
			);
		}

		$other_tokens = $this->get_rest_addition_sections_tokens( $context, $action );
		if ( ! empty( $other_tokens ) ) {
			$other_tokens = array(
				'body' => $other_tokens,
			);
		}

		$information = array();

		if ( ! empty( $sections ) && ! empty( $other_tokens ) ) {
			$information = MainWP_Pro_Reports::get_instance()->get_report_sections_tokens_values( $website['id'], $sections, $other_tokens, $date_from, $date_to );
		} else {
			$params_other_sections = $this->get_rest_params_other_sections( $context );
			if ( ! empty( $params_other_sections ) ) {
				$sections = array(
					'body' => $this->get_rest_orther_section_tokens_by_context( $context ),
				);
				MainWP_Pro_Reports::get_instance()->get_report_other_sections_tokens_values( $information, $sections, $other_tokens, $website['id'], $date_from, $date_to, $params_other_sections );
			}
		}

		MainWP_Pro_Reports_Utility::log_debug( 'Rest API :: Response data :: ' . print_r( $information, true ) );

		$single_tokens_values = array();
		$other_tokens_data    = array();

		$params_single = $this->get_rest_single_tokens_params_by_context( $context );
		MainWP_Pro_Reports::get_instance()->get_report_single_tokens_values( $single_tokens_values, $website, $date_from, $date_to, $params_single );

		$return = array();
		if ( is_array( $information ) && isset( $information['other_tokens_data'] ) ) {
			// MainWP_Pro_Reports::fix_empty_logs_values( $information, 'body' );
			$other_tokens_data = isset( $information['other_tokens_data'] ) && isset( $information['other_tokens_data']['body'] ) && ! empty( $information['other_tokens_data']['body'] ) ? $information['other_tokens_data']['body'] : array();
			$sections_data     = isset( $information['sections_data'] ) && isset( $information['sections_data']['body'] ) && ! empty( $information['sections_data']['body'] ) ? $information['sections_data']['body'] : array();

			if ( ! empty( $sections_data ) ) {
				$return['sections_data'] = $sections_data;
			}
		}

		if ( ! empty( $single_tokens_values ) ) {
			$other_tokens_data = array_merge( $single_tokens_values, $other_tokens_data );
		}

		if ( ! empty( $other_tokens_data ) ) {
			$return['other_tokens_data'] = $other_tokens_data;
		}

		return $return;
	}


	/**
	 * Method format_response_data()
	 *
	 * Handle to format response data.
	 *
	 * @param array  $data Response data.
	 * @param array  $params Request params.
	 * @param string $endpoint Request endpoint.
	 * @param string $format Format of response data.
	 */
	public function format_response_data( $data, $params, $endpoint, $format = 'normal' ) {

		if ( is_array( $data ) ) {
			$context = isset( $params['context'] ) ? $params['context'] : '';
			if ( 'normal' == $format ) {
				$formated_data = array();
				if ( isset( $data['sections_data'] ) ) {
					$sections_data = $data['sections_data'];
					if ( is_array( $sections_data ) ) {
						$formated_sections = array();
						foreach ( $sections_data as $sec_index => $sec_data ) {
							$formated_sec = array();
							if ( is_array( $sec_data ) ) {
								foreach ( $sec_data as $row_index => $sec_tokens ) {
									if ( is_array( $sec_tokens ) && ! empty( $sec_tokens ) ) {
										$formated_sec[ $row_index ] = array();
										foreach ( $sec_tokens as $tkn => $tkv ) {
											$simple                                = $this->simply_response_indexed_token( $tkn, $context, $endpoint );
											$formated_sec[ $row_index ][ $simple ] = $tkv;
										}
									}
								}
							} else {
								$formated_sec = $sec_data;
							}
							$formated_sections[ $sec_index ] = $formated_sec;
						}
						$formated_data = $formated_sections;
					}
					unset( $data['sections_data'] );
				}

				// simply the output.
				if ( is_array( $formated_data ) && 1 == count( $formated_data ) && is_array( $formated_data[0] ) ) {
					$formated_data = current( $formated_data );
				}

				$other_pending = array();
				if ( isset( $data['other_tokens_data'] ) ) {
					if ( is_array( $data['other_tokens_data'] ) ) {
						$other_data = $data['other_tokens_data'];
						if ( count( $other_data ) > 0 ) {
							foreach ( $other_data as $tkn => $tkv ) {
								$simple = $this->simply_response_indexed_token( $tkn, $context, $endpoint );
								if ( 'pending' == $context ) {
									$other_pending[ $simple ] = $tkv;
								} else {
									$formated_data[ $simple ] = $tkv;
								}
							}
						}
					}
					unset( $data['other_tokens_data'] );
				}

				if ( 'pending' == $context && ! empty( $other_pending ) ) {
					$formated_data[] = $other_pending;
				}

				// to format other tokens value if existed.
				// after unset: 'sections_data' and 'other_tokens_data'.
				if ( ! empty( $data ) && is_array( $data ) ) {
					$single_data = $data;
					foreach ( $single_data as $tkn => $tkv ) {
						$simple                   = $this->simply_response_indexed_token( $tkn, $context, $endpoint );
						$formated_data[ $simple ] = $tkv;
					}
				}

				return $formated_data;
			}
		}
		return $data;
	}

	/**
	 * Method simply_response_indexed_token()
	 *
	 * Handle simply response indexed tokens.
	 *
	 * @param string $token token report.
	 * @param string $context request context.
	 * @param string $endpoint request endpoint.
	 */
	public function simply_response_indexed_token( $token, $context, $endpoint ) {
		$new_token = str_replace( array( '[', ']' ), '', $token );
		$new_array = explode( '.', $new_token );

		if ( 'multi-tokens' === $endpoint ) {
			$simple_name = str_replace( '.', '_', $new_token );
			return $simple_name;
		}

		$first  = '';
		$second = '';
		$third  = '';
		$fourth = '';

		if ( 1 === count( $new_array ) ) {
			list( $first ) = $new_array;
		} elseif ( 2 === count( $new_array ) ) {
			list( $second, $first ) = $new_array;
		} elseif ( 3 === count( $new_array ) ) {
			list( $third, $second, $first ) = $new_array;
		} elseif ( 4 === count( $new_array ) ) {
			list( $fourth, $third, $second, $first ) = $new_array;
		}

		$check_actions   = array( 'updated', 'installed', 'deleted', 'activated' );
		$check_contexts  = array( 'aum', 'piwik' );
		$check_contexts2 = array( 'scan', 'protect' );
		$check_contexts3 = array( 'website' );
		$check_contexts4 = array( 'pending' );

		if ( in_array( $context, $check_contexts2 ) ) {
			$simple_name = $second . '_' . $first;
			if ( ! empty( $fourth ) ) {
				$simple_name = $third . '_' . $simple_name;
			}
			return $simple_name;
		}

		if ( in_array( $context, $check_contexts3 ) || in_array( $context, $check_contexts4 ) ) {
			$simple_name = str_replace( '.', '_', $new_token );
			return $simple_name;
		}

		$simple_name = $first;
		if ( empty( $third ) ) {
			if ( ! empty( $second ) && ! in_array( $second, $check_contexts ) ) {
				$simple_name = $second . '_' . $first;
			}
		} elseif ( ! empty( $second ) && ( ! in_array( $second, $check_actions ) ) ) {
				$simple_name = $second . '_' . $first;
		} elseif ( 'count' == $first ) {
			$simple_name = $second . '_' . $first;
		}
		return $simple_name;
	}
}

// End of class.
