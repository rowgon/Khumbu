<?php
/**
 * Class MainWP_Pro_Reports_Rest_Api.
 *
 * @package MainWP/Extensions
 */
class MainWP_Pro_Reports_Rest_Api {

	/**
	 * Protected variable to hold the API version.
	 *
	 * @var string API version
	 */
	protected $api_version = '1';

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
	 * Method init()
	 *
	 * Adds an action to to the init hook.
	 */
	public function init() {
		add_action( 'init', array( &$this, 'init_rest_api' ) );
	}

	/**
	 * Method init_rest_api()
	 *
	 * Adds an action to create the rest API endpoints if activated in the plugin settings.
	 */
	public function init_rest_api() {
		if ( has_filter( 'mainwp_rest_api_enabled' ) ) {
			$activated = apply_filters( 'mainwp_rest_api_enabled', false );
		} else {
			$activated = true; // to compatible.
		}
		// only activate the api if enabled in the dashboard plugin.
		if ( $activated ) {
			// init APIs.
			add_action( 'rest_api_init', array( &$this, 'mainwp_register_routes' ) );
		}
	}

	/**
	 * Method mainwp_rest_api_init()
	 *
	 * Creates the necessary endpoints for the api.
	 * Note, for a request to be successful the URL query parameters consumer_key and consumer_secret need to be set and correct.
	 */
	public function mainwp_register_routes() {

		// Create an array which holds all the endpoints. Method can be GET, POST, PUT, DELETE.
		$endpoints = array(
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'plugins',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'themes',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'wordpress', // phpcs:ignore -- wordpress.
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'posts',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'pages',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'users',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'comments',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'media',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'widgets',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'menus',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'backups',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'sucuri',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'wordfence',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'ithemes',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'maintenance',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'virusdie',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'domainmonitor',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'sslmonitor',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'pagespeed',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'woocomstatus',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'vulnerable',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'ga',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'piwik',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'lighthouse',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'aum',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'atarim',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'protect',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'scan',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'website',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'abandoned',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'pending',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'misc',
			),
			array(
				'route'    => 'pro-reports',
				'method'   => 'GET',
				'callback' => 'multi-tokens',
			),
		);

		// loop through the endpoints.
		foreach ( $endpoints as $epoint ) {
			$function_name = str_replace( '-', '_', $epoint['callback'] );
			register_rest_route(
				'mainwp/v' . $this->api_version,
				'/' . $epoint['route'] . '/' . $epoint['callback'],
				array(
					'methods'             => $epoint['method'],
					'callback'            => array( &$this, 'pro_reports_rest_api_' . $function_name . '_callback' ),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * Method mainwp_authentication_error()
	 *
	 * Common error message when consumer key and secret are wrong.
	 *
	 * @return array $response Array with an error message explaining that the credentials are wrong.
	 */
	public function mainwp_authentication_error() {

		$data = array( 'ERROR' => esc_html__( 'Incorrect or missing consumer key and/or secret. If the issue persists please reset your authentication details from the MainWP > Settings > REST API page, on your MainWP Dashboard site.', 'mainwp-pro-reports-extension' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 401 );

		return $response;
	}

	/**
	 * Method mainwp_missing_data_error()
	 *
	 * Common error message when data is missing from the request.
	 *
	 * @return array $response Array with an error message explaining details are missing.
	 */
	public function mainwp_missing_data_error() {

		$data = array( 'ERROR' => esc_html__( 'Required parameter is missing.', 'mainwp-pro-reports-extension' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 400 );

		return $response;
	}

	/**
	 * Method mainwp_invalid_data_error()
	 *
	 * Common error message when data in request is ivalid.
	 *
	 * @return array $response Array with an error message explaining details are missing.
	 */
	public function mainwp_invalid_data_error() {

		$data = array( 'ERROR' => esc_html__( 'Required parameter data is is not valid.', 'mainwp-pro-reports-extension' ) );

		$response = new \WP_REST_Response( $data );
		$response->set_status( 400 );

		return $response;
	}


	/**
	 * Method get_rest_parameters_request().
	 *
	 * @param array  $request The request made in the API call which includes all parameters.
	 * @param string $endpoint The request endpoint.
	 */
	public function get_rest_parameters_request( $request, $endpoint ) {

		$params = array(
			'website'    => false,
			'context'    => false,
			'action'     => false,
			'start_date' => false,
			'end_date'   => false,
		);

		if ( 'multi-tokens' == $endpoint && empty( $request['multi_tokens'] ) ) {
			throw new \Exception( esc_html__( 'Invalid or missing parameters: content_tokens', 'mainwp-pro-reports-extension' ) );
		}

		$website_id = isset( $request['site_id'] ) ? intval( $request['site_id'] ) : 0;
		if ( is_numeric( $website_id ) && ! empty( $website_id ) ) {
			$website = MainWP_Pro_Reports_Utility::get_websites( $website_id );

			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}
			if ( ! empty( $website ) ) {
				$params['website'] = $website;
			}
		}
		if ( empty( $params['website'] ) ) {
			throw new \Exception( esc_html__( 'Invalid site id or not found site.', 'mainwp-pro-reports-extension' ) );
		}

		$start_date = isset( $request['start_date'] ) && ! empty( $request['start_date'] ) ? strtotime( $request['start_date'] ) : 0;
		$end_date   = isset( $request['end_date'] ) && ! empty( $request['end_date'] ) ? strtotime( $request['end_date'] ) : 0;

		if ( empty( $start_date ) || empty( $end_date ) || ( $start_date >= $end_date ) ) {
			throw new \Exception( esc_html__( 'Invalid or missing parameters: start date or end date.', 'mainwp-pro-reports-extension' ) );
		}
		$params['start_date'] = $start_date;
		$params['end_date']   = $end_date;

		if ( 'multi-tokens' == $endpoint ) {
			$params['multi_tokens'] = $request['multi_tokens'];
		} else {

			if ( ! MainWP_Pro_Reports_Rest_Handler::rest_valid_context( $endpoint ) ) {
				throw new \Exception( esc_html__( 'Invalid or missing parameters: context.', 'mainwp-pro-reports-extension' ) );
			}

			$context           = $endpoint;
			$params['context'] = $context;
			$action            = isset( $request['action'] ) ? $request['action'] : '';

			$contexts = MainWP_Pro_Reports_Rest_Handler::get_rest_sections_contexts(); // get sections contexts.
			if ( in_array( $context, $contexts ) ) { // if it is section context, need to check action parameter.
				// action for section must not be empty.
				if ( empty( $action ) ) {
					throw new \Exception( esc_html__( 'Invalid or missing parameters: action', 'mainwp-pro-reports-extension' ) );
				}
			}
			$params['action'] = $action;

		}

		$params['format'] = isset( $request['format'] ) ? sanitize_text_field( $request['format'] ) : '';
		return $params;
	}

	/**
	 * Method get_rest_pro_reports_data().
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 */
	public function get_rest_pro_reports_data( $request, $endpoint ) {
		// first validate the request.
		if ( apply_filters( 'mainwp_rest_api_validate', false, $request ) ) {
			// get parameters.
			if ( null != $request['site_id'] ) {

				if ( is_numeric( $request['site_id'] ) ) {
					try {
						$params = $this->get_rest_parameters_request( $request, $endpoint );
						$data   = MainWP_Pro_Reports_Rest_Handler::instance()->get_rest_report_tokens_values( $params );
						if ( isset( $params['format'] ) && 'normal' == $params['format'] ) {
							$result = array( 'data' => MainWP_Pro_Reports_Rest_Handler::instance()->format_response_data( $data, $params, $endpoint ) );
						} else {
							$result = array( 'data' => $data );
						}
						$response = new \WP_REST_Response( $result );
						$response->set_status( 200 );
					} catch ( \Exception $e ) {
						$data     = array( 'ERROR' => $e->getMessage() );
						$response = new \WP_REST_Response( $data );
						$response->set_status( 400 );
						return $response;
					}
				} else {
					// throw invalid data error.
					$response = $this->mainwp_invalid_data_error();
				}
			} else {
				// throw missing data error.
				$response = $this->mainwp_missing_data_error();
			}
		} else {
			// throw common error.
			$response = $this->mainwp_authentication_error();
		}
		return $response;

	}

	/**
	 * Method pro_reports_rest_api_plugins_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: plugins
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/plugins
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_plugins_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'plugins' );
	}

	/**
	 * Method pro_reports_rest_api_themes_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: themes
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/themes
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_themes_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'themes' );
	}

	/**
	 * Method pro_reports_rest_api_posts_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: posts
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/posts
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_posts_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'posts' );
	}

	/**
	 * Method pro_reports_rest_api_pages_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: pages
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/pages
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_pages_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'pages' );
	}

	/**
	 * Method pro_reports_rest_api_users_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: users
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/users
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_users_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'users' );
	}

	/**
	 * Method pro_reports_rest_api_comments_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: comments
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/comments
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_comments_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'comments' );
	}

	/**
	 * Method pro_reports_rest_api_media_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: media
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/media
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_media_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'media' );
	}

	/**
	 * Method pro_reports_rest_api_menus_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: menus
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/menus
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_menus_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'menus' );
	}

	/**
	 * Method pro_reports_rest_api_wordpress_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: /wordpress
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/wordpress
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_wordpress_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'wordpress' ); //phpcs:ignore -- to fix wordpress.
	}

	/**
	 * Method pro_reports_rest_api_backups_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: backups
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/backups
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_backups_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'backups' );
	}

	/**
	 * Method pro_reports_rest_api_sucuri_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: sucuri
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/sucuri
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_sucuri_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'sucuri' );
	}


	/**
	 * Method pro_reports_rest_api_wordfence_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: wordfence
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/wordfence
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_wordfence_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'wordfence' );
	}

	/**
	 * Method pro_reports_rest_api_ithemes_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: ithemes
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/ithemes
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_ithemes_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'ithemes' );
	}

	/**
	 * Method pro_reports_rest_api_maintenance_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: maintenance
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/maintenance
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_maintenance_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'maintenance' );
	}


	/**
	 * Method pro_reports_rest_api_virusdie_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: virusdie
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/virusdie
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_virusdie_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'virusdie' );
	}

	/**
	 * Method pro_reports_rest_api_domainmonitor_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: domainmonitor
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/domainmonitor
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_domainmonitor_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'domainmonitor' );
	}

	/**
	 * Method pro_reports_rest_api_sslmonitor_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: sslmonitor
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/sslmonitor
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_sslmonitor_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'sslmonitor' );
	}

	/**
	 * Method pro_reports_rest_api_pagespeed_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: pagespeed
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/pagespeed
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_pagespeed_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'pagespeed' );
	}

	/**
	 * Method pro_reports_rest_api_woocomstatus_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: woocomstatus
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/woocomstatus
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_woocomstatus_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'woocomstatus' );
	}


	/**
	 * Method pro_reports_rest_api_vulnerable_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: vulnerable
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/vulnerable
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_vulnerable_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'vulnerable' );
	}

	/**
	 * Method pro_reports_rest_api_ga_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: ga
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/ga
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_ga_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'ga' );
	}


	/**
	 * Method pro_reports_rest_api_piwik_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: piwik
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/piwik
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_piwik_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'piwik' );
	}


	/**
	 * Method pro_reports_rest_api_lighthouse_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: lighthouse
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/lighthouse
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_lighthouse_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'lighthouse' );
	}


	/**
	 * Method pro_reports_rest_api_aum_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: aum
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/aum
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_aum_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'aum' );
	}


	/**
	 * Method pro_reports_rest_api_atarim_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: atarim
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/atarim
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_atarim_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'atarim' );
	}

	/**
	 * Method pro_reports_rest_api_protect_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: protect
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/protect
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_protect_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'protect' );
	}


	/**
	 * Method pro_reports_rest_api_scan_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: scan
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/scan
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_scan_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'scan' );
	}

	/**
	 * Method pro_reports_rest_api_website_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: website
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/website
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_website_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'website' );
	}

	/**
	 * Method pro_reports_rest_api_abandoned_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: abandoned
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/abandoned
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_abandoned_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'abandoned' );
	}


	/**
	 * Method pro_reports_rest_api_pending_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: pending
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/pending
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_pending_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'pending' );
	}


	/**
	 * Method pro_reports_rest_api_misc_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: misc
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/misc
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_misc_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'misc' );
	}

	/**
	 * Method pro_reports_rest_api_multi_tokens_callback()
	 *
	 * Callback function for managing the response to API requests made for the endpoint: multi-tokens
	 * Can be accessed via a request like: https://yourdomain.com/wp-json/mainwp/v1/pro-reports/multi-tokens
	 * API Method: GET
	 *
	 * @param array $request The request made in the API call which includes all parameters.
	 *
	 * @return object $response An object that contains the return data and status of the API request.
	 */
	public function pro_reports_rest_api_multi_tokens_callback( $request ) {
		return $this->get_rest_pro_reports_data( $request, 'multi-tokens' );
	}
}

// End of class.
