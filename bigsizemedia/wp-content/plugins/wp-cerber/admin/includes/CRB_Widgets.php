<?php

/**
 * WP Cerber Dashboard Widget Manager
 *
 * Provides functionality to register, render, and manage WP Cerber dashboard widgets,
 * including user-specific settings such as widget display order.
 *
 * @since 9.6.4.2
 */
final class CRB_Widgets {

	// All widgets are here
	private static array $widgets = array();

	// If a widget loaded from the cache
	static bool $from_cache = false;

	/**
	 * A key indicating that there is nothing to cache and show placeholder
	 */
	public const NO_DATA = 'no_data';

	/**
	 * Registers a new dashboard widget.
	 *
	 * @param string $id Unique identifier for the widget.
	 * @param string $title The title of the widget displayed in the heading of the widget
	 * @param string $sub_title Subtitle displayed next to the title
	 * @param string $controls HTML content for the widget's control area, if any.
	 *
	 * @param callable $callback Renders the widget body.
	 *                             Expected return value:
	 *                             - string: Widget body HTML.
	 *                             - array{0: string, 1?: bool, 2?: bool}:
	 *                               [0] Widget body HTML.
	 *                               [1] Whether to display the controls area (default: true).
	 *                               [2] Whether the widget body should be loaded via AJAX (default: false).
	 * @param array{0?: string, 1?: int} $cache_config Optional caching configuration.
	 *                             If not empty and a persistent object cache is available, widget content may be cached.
	 *                             [0] Cache key for the data source.
	 *                             [1] Allowed **lag** behind the data source, in seconds (default: 60).
	 * @param array{no_heading?: bool, hideable?: bool} $flags Miscellaneous flags.
	 *                             - no_heading: Hide the widget UI heading (default: false).
	 *                             - hideable: Allow hiding from the Dashboard by the admin (default: true).
	 *
	 * @return void
	 */
	public static function register( string $id, string $title, string $sub_title, string $controls, callable $callback, array $cache_config = [], array $flags = [] ) {
		$flag_defaults = array(
			'no_heading' => false,
			'hideable'   => true
		);

		$flags = array_merge( $flag_defaults, array_intersect_key( $flags, $flag_defaults ) );

		$configuration = array_merge( array( $title, $controls, $callback, $cache_config, 'sub_title' => $sub_title ), $flags );

		self::$widgets[ crb_sanitize_id( $id ) ] = $configuration;
	}

	/**
	 * Returns a list of all registered widget IDs and their titles.
	 *
	 * @param bool $sort Optional. Whether to sort the widgets based on the saved user order of widgets.
	 * @param bool $active_only Optional. Whether to filter out the widgets based on the saved user list of active widgets.
	 *
	 * @return array An associative array where the keys are widget IDs and the values are widget titles.
	 */
	public static function get_titles( bool $sort = false, bool $active_only = true ): array {
		if ( ! self::$widgets ) {
			return array();
		}

		$result = array();

		foreach ( self::$widgets as $id => $widget ) {
			$result[ $id ] = array( $widget[0], $widget['sub_title'] );
		}

		if ( $active_only
		     && $list = self::get_active_list() ) {
			$result = array_intersect_key( $result, array_filter( $list ) );
		}

		if ( $sort
		     && ( $order = self::get_screen_parameter( 'widget_order' ) )
		     && ! crb_is_wp_error( $order ) ) {

			$order = array_filter( $order );
			$order_indices = array_flip( $order );

			uksort( $result, function ( $a, $b ) use ( $order_indices ) {
				return ( $order_indices[ $a ] ?? 0 ) <=> ( $order_indices[ $b ] ?? 0 );
			} );
		}

		return $result;
	}

	/**
	 * Returns the HTML content for a widget's control area.
	 *
	 * @param string $widget_id The ID of the widget.
	 *
	 * @return string The HTML content of the widget controls, or an empty string if no controls are defined.
	 */
	public static function get_controls( string $widget_id ): string {
		return self::$widgets[ $widget_id ][1] ?? '';
	}

	/**
	 * Determines whether the widget header will be shown or hidden
	 * when displaying the given widget on admin pages
	 *
	 * @param string $widget_id
	 *
	 * @return bool If true, the widget header must be hidden
	 */
	public static function hide_header( string $widget_id ): bool {
		return ! empty( self::$widgets[ $widget_id ]['no_heading'] );
	}

	/**
	 * Determines whether the widget can be hidden on admin pages.
	 *
	 * @param string $widget_id
	 *
	 * @return bool True if the widget is hideable.
	 */
	public static function is_hideable( string $widget_id ): bool {
		return (bool) ( self::$widgets[ $widget_id ]['hideable'] ?? true );
	}

	/**
	 * Renders a dashboard widget by its ID.
	 *
	 * @param string $widget_id The ID of the widget to render.
	 * @param bool $is_ajax Optional. Whether the rendering is triggered in an AJAX context. Default false.
	 *
	 * @return string|array|WP_Error The rendered widget content as a string or an array,
	 *                               or a WP_Error object if the widget cannot be rendered.
	 */
	public static function render_widget( string $widget_id, bool $is_ajax = false ) {
		if ( ! $widget = self::$widgets[ $widget_id ] ?? false ) {
			return new WP_Error( 'cerber_widget_not_found', 'Widget not found:' . $widget_id );
		}

		if ( $cached = self::get_cache( $widget_id ) ) {
			self::$from_cache = true;

			return $cached;
		}

		self::$from_cache = false;

		$callback = $widget[2];

		if ( ! is_callable( $callback ) ) {
			return new WP_Error( 'cerber_not_callable', 'Widget callback is not callable (Widget ID ' . $widget_id . ').' );
		}

		try {
			$result = call_user_func( $callback, $is_ajax );
		} catch ( Exception $e ) {
			return new WP_Error(
				'cerber_callback_error',
				'An exception occurred during widget callback execution (Widget ID ' . $widget_id . '). ERROR: ' . $e->getMessage(),
				array( 'exception' => $e->getMessage() )
			);
		}

		if ( crb_is_wp_error( $result ) ) {
			return $result;
		}

		if ( $placeholder = crb_array_get( $result, self::NO_DATA ) ) {
			// There is nothing to display, we show a placeholder
			return array( '<div class="crb-dash-padding crb-dash-placeholder">' . $placeholder . '</div>', false );
		}

		self::set_cache( $widget_id, $result );

		if ( $is_ajax
		     || ! is_array( $result ) ) {
			return $result;
		}

		// Check if AJAX loading is required - based on the returned value from the callback

		$ajax = $result[2] ?? false;

		if ( ! $ajax ) {
			return $result;
		}

		return self::get_ajax_area( $widget_id );
	}

	/**
	 * Retrieves a widget's content from the WordPress persistent object cache.
	 * Note: If no persistent object cache is available, the content will be lost between HTTP requests.
	 *
	 * @param string $widget_id The ID of the widget.
	 *
	 * @return mixed|false The cached widget content if available and valid. Returns false if:
	 *                     - The cache is unavailable.
	 *                     - The cache has expired.
	 *                     - The widget does not have a valid cache configuration.
	 */
	private static function get_cache( string $widget_id ) {
		list ( $source_key, $lag ) = self::get_cache_params( $widget_id );

		if ( ! $source_key
		     || ! ( $source = cerber_cache_get( $source_key, false ) )
		     || ! ( $modified = $source['data_modified'] ?? false )
		     || ! ( $cached = cerber_cache_get( 'dash_widget_' . $widget_id, false ) )
		     || empty( $cached['widget'] ) ) {

			return false;
		}

		$saved = $cached['saved'];

		// Check if the cache has expired
		if ( ( $saved + $lag ) < $modified ) {
			return false;
		}

		// Check if the cache is stale
		if ( $saved < $modified
		     && $saved < ( time() - 600 ) ) {
			return false;
		}

		return $cached['widget'];
	}


	/**
	 * Saves a widget's rendered content to the WordPress persistent object cache.
	 * Note: If no persistent object cache is available, the content will be lost between HTTP requests.
	 *
	 * @param string $widget_id The unique identifier of the widget.
	 * @param array|string $contents The content of the widget to be cached.
	 *
	 * @return bool True if the cache entry was successfully saved, false otherwise.
	 *
	 */
	private static function set_cache( string $widget_id, $contents ) {
		list ( $source_key, $lag ) = self::get_cache_params( $widget_id );

		if ( ! $source_key ) {
			return false;
		}

		return cerber_cache_set( 'dash_widget_' . $widget_id, array( 'widget' => $contents, 'saved' => time(), 'lag' => $lag ) );
	}

	/**
	 * Purges all cached widgets
	 *
	 * @param string $widget_id
	 *
	 * @return void
	 */
	public static function purge_cache( string $widget_id = '' ) {
		if ( $widget_id ) {
			cerber_cache_set( 'dash_widget_' . $widget_id, array( 'purged' => time() ) );

			return;
		}

		foreach ( array_keys( self::$widgets ) as $widget_id ) {
			cerber_cache_set( 'dash_widget_' . $widget_id, array( 'purged' => time() ) );
		}
	}

	/**
	 * Returns cache parameters if specified for a widget. Parameters are defined when registering widgets.
	 *
	 * @param string $widget_id Widget ID.
	 *
	 * @return array Contains 1) key to get the last modification time of the data source and 2) Allowed lag behind the data source
	 */
	private static function get_cache_params( string $widget_id ): array {
		$key = self::$widgets[ $widget_id ][3][0] ?? '';
		$lag = self::$widgets[ $widget_id ][3][1] ?? 120; // Default value is 2 minutes

		return array( $key, $lag );
	}

	/**
	 * Forcefully update widget cache elements that will expire soon
	 *
	 * @return void
	 */
	public static function update_cache() {
		if ( ! self::$widgets
		     || ! CRB_Cache::checker()
		     || ! is_super_admin() ) {
			return;
		}

		foreach ( array_keys( self::$widgets ) as $widget_id ) {
			list ( $source_key, $lag ) = self::get_cache_params( $widget_id );

			if ( ! $source_key ) { // Meaning cache not in use for this widget
				continue;
			}

			// Do we have valid data source modification time?

			if ( ! ( $source = cerber_cache_get( $source_key, false ) )
			     || ! ( $modified = $source['data_modified'] ?? false ) ) {
				continue;
			}

			// Try to get widget from cache

			if ( ! ( $cached = cerber_cache_get( 'dash_widget_' . $widget_id, false ) )
			     || ! ( $saved = $cached['saved'] ?? false ) ) {

				self::render_widget( $widget_id );
				continue;
			}

			// If cache will expire soon (less than in 30 sec) we update it preliminary

			if ( ( $saved + $lag - 30 ) < $modified ) {
				self::purge_cache( $widget_id );
				self::render_widget( $widget_id );
			}
		}
	}

	/**
	 * Updates the list of active widgets for the current user on a specific admin screen.
	 *
	 * @param array $post_fields Array containing $_POST fields that represent enabled widgets as array keys
	 * @param string $screen
	 *
	 * @return true|WP_Error
	 */
	public static function save_list( $post_fields, string $screen = 'main' ) {
		if ( empty( self::$widgets ) ) {
			return new WP_Error( 'cerber_no_widgets', 'No widgets are registered yet. Did you forget to call ' . __CLASS__ . '::register();?' );
		}

		// Make sure we're saving existing widget IDs only

		$widgets = array_fill_keys( array_keys( self::$widgets ), 0 );
		$list = array_merge( $widgets, array_intersect_key( $post_fields, $widgets ) );

		// Sanitize values

		$list = array_map( function ( $val ) {
			return absint( $val );
		}, $list );

		return self::save_screen_parameter( 'widget_list', $list, $screen );
	}

	/**
	 * Returns the list of active widgets for the current user on a specific admin screen.
	 *
	 * @param string $screen
	 *
	 * @return array The list of active widgets, including those that were registered after the list was saved.
	 */
	public static function get_active_list( string $screen = 'main' ) {
		if ( empty( self::$widgets ) ) {
			return array();
		}

		$list = self::get_screen_parameter( 'widget_list', $screen );

		if ( crb_is_wp_error( $list ) ) {
			return self::$widgets;
		}

		$disabled = array_filter( $list, function ( $value ) {
			return empty( $value );
		} );

		foreach ( $disabled as $widget_id => $value ) {
			if ( ! self::is_hideable( $widget_id ) ) {
				unset( $disabled[ $widget_id ] );
			}
		}

		return array_diff_key( self::$widgets, $disabled );
	}

	/**
	 * Check if the widget is active for the current user on a specific admin screen.
	 *
	 * @param string $widget_id The ID of the widget to check.
	 * @param string $screen The target screen ID.
	 *
	 * @return bool True if the widget is active.
	 */
	public static function is_active( string $widget_id, string $screen = 'main' ) {
		$list = self::get_active_list( $screen );

		return isset( $list[ $widget_id ] );
	}

	/**
	 * Updates the display order of widgets for the current user on a specific admin screen.
	 *
	 * @param array $order An array of widget IDs in the desired display order.
	 * @param string $screen Optional. The admin screen ID where the order applies. Default 'main'.
	 *
	 * @return true|WP_Error True on success, or a WP_Error object on failure.
	 */
	public static function save_order( array $order, string $screen = 'main' ) {

		$order = array_filter( $order );

		return self::save_screen_parameter( 'widget_order', $order, $screen );
	}

	/**
	 * Save a specific configuration parameter for the current user and a given screen (admin page)
	 *
	 * @param string $key The screen meta key to save/retrieve the parameter.
	 * @param array $value The parameter value.
	 * @param string $screen Optional. The admin screen ID where the $value saved for. Default 'main'.
	 *
	 * @return true|WP_Error True on success, or a WP_Error object on failure.
	 */
	private static function save_screen_parameter( string $key, array $value, string $screen = 'main' ) {
		if ( ! $user_id = get_current_user_id() ) {
			return new WP_Error( 'cerber_non_user', 'User is not authenticated.' );
		}

		$meta = get_user_meta( $user_id, 'cerber_dashboard_config', true );

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$meta[ $screen ][ $key ] = $value;

		if ( ! update_user_meta( $user_id, 'cerber_dashboard_config', $meta ) ) {
			return new WP_Error( 'cerber_not_updated', 'User meta not updated. Possibly duplicate value.' );
		}

		return true;
	}

	/**
	 * Retrieve a configuration parameter specified by $key for the current user and a given screen (admin page)
	 *
	 * @param string $key The screen meta key to save/retrieve the parameter
	 * @param string $screen Optional. The admin screen ID where the $value saved for. Default 'main'.
	 *
	 * @return array|WP_Error The parameter value, or a WP_Error object on failure.
	 */
	public static function get_screen_parameter( string $key, string $screen = 'main' ) {
		if ( ! $user_id = get_current_user_id() ) {
			return new WP_Error( 'cerber_non_user', 'User is not authenticated.' );
		}

		if ( ( $meta = get_user_meta( $user_id, 'cerber_dashboard_config', true ) )
		     && ( $value = $meta[ $screen ][ $key ] ?? false )
		     && is_array( $value ) ) {

			return $value;
		}

		return array();
	}

	/**
	 * Generates an HTML skeleton loader for a table-like structure.
	 *
	 * @param int $rows Optional. The number of rows to generate.
	 * @param int $cols Optional. The number of columns to generate.
	 *
	 * @return string The generated HTML for the skeleton loader.
	 */
	static function get_skeleton( int $rows = 5, int $cols = 5 ): string {

		$html = '<div class="crb-skeleton-table">';

		for ( $i = 0; $i < $rows; $i ++ ) {
			$html .= '<div class="crb-skeleton-row">';

			$html .= str_repeat( '<div class="crb-skeleton-cell"></div>', $cols );

			$html .= '</div>' . PHP_EOL;
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Generates an HTML container for asynchronously loading a widget via AJAX.
	 *
	 * @param string $widget_id The unique ID of the widget to load via AJAX.
	 *
	 * @return string The generated HTML container with AJAX-related attributes.
	 */
	static function get_ajax_area( string $widget_id ): string {

		return '<div class="crb_async_content" data-ajax_route="dashboard_analytics" data-ds_widget="' . crb_escape_html( $widget_id ) . '">' . self::get_skeleton() . '</div>';

	}
}
