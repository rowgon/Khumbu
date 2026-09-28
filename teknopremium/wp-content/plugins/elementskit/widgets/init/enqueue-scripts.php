<?php
namespace ElementsKit\Widgets\Init;

defined( 'ABSPATH' ) || exit;

class Enqueue_Scripts{

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [$this, 'frontend_js']);
		add_action( 'wp_enqueue_scripts', [$this, 'frontend_css'], 99 );

		add_action( 'elementor/frontend/after_register_scripts', [$this, 'register_elementor_js'], 10 );
	}

	public function register_elementor_js() {
		// Registering Gsap js files
		wp_register_script( 'gsap', \ElementsKit::widget_url() . 'init/assets/js/gsap.js', array(),  \ElementsKit::version(), [ 'strategy' => 'defer', 'in_footer' => true ] );
		wp_register_script( 'gsap-scroll-trigger', \ElementsKit::widget_url() . 'init/assets/js/gsap-scroll-trigger.js', array(), \ElementsKit::version(), [ 'strategy' => 'defer', 'in_footer' => true ] );

		// TODO: Need to remove this script after 4.4.6 or later version, because it's already included in free plugin
		wp_register_script( 'ekit-core', \ElementsKit::widget_url() . 'init/assets/js/widgets/core-free.js', ['jquery', 'elementor-frontend'], \ElementsKit::version(), true );
		
		// Registering Core js file
		wp_register_script(
			'ekit-pro-core',
			\ElementsKit::widget_url() . 'init/assets/js/widgets/core.js',
			array( 'jquery', 'elementor-frontend', 'ekit-core' ),
			\ElementsKit::version(),
			true
		);

		// Google Map widget scripts
		$user_data = \ElementsKit_Lite\Libs\Framework\Attr::instance()->utils->get_option('user_data', []);
		$gmap_api_key = !empty($user_data['google_map']) ? $user_data['google_map']['api_key'] : '';
		wp_register_script( 'ekit-google-map-api', 'https://maps.googleapis.com/maps/api/js?key=' . $gmap_api_key . '', array('jquery'), \ElementsKit::version(), [ 'strategy' => 'async', 'in_footer' => true ] );
		wp_register_script( 'ekit-google-gmaps', \ElementsKit::widget_url() . 'init/assets/js/gmaps.min.js', array('jquery'), \ElementsKit::version(), [ 'strategy' => 'async', 'in_footer' => true ] );
		wp_register_script( 'ekit-pro-google-map', \ElementsKit::widget_url() . 'init/assets/js/widgets/google-map.js', array('jquery', 'ekit-google-map-api', 'ekit-google-gmaps'), \ElementsKit::version(), [ 'strategy' => 'async', 'in_footer' => true ] );

		// table widget script
		wp_register_script( 'datatables', \ElementsKit::widget_url() . 'init/assets/js/datatables.min.js', array( 'jquery' ), \ElementsKit::version(), true );
		
		// register script for gallery
		wp_register_script( 'tilt', \ElementsKit::widget_url() . 'init/assets/js/tilt.jquery.min.js', array('jquery'), \ElementsKit::version(), true );

		// Registering widget js files
		$widget_list = \ElementsKit_Lite\Config\Widget_List::instance()->get_list( 'all' );

		foreach ( $widget_list as $widget_slug => $widget ) {
			if ( empty( $widget['hasJS'] ) || ( $widget['package'] ?? '' ) !== 'pro' ) {
				continue;
			}

			$handle = 'ekit-pro-' . $widget_slug;
			wp_register_script(
				$handle,
				\ElementsKit::widget_url() . 'init/assets/js/widgets/' . $widget_slug . '.js',
				array( 'ekit-pro-core' ),
				\ElementsKit::version(),
				true
			);
		}
	}

	public function frontend_js() {
		if(is_admin()){
			return;
		}

		//Event calendar widget
		wp_register_script( 'full-calendar', \ElementsKit::widget_url() . 'init/assets/js/fullcalendar/index.global.min.js', [], \ElementsKit::version(), true );
		wp_register_script( 'full-calendar-locales', \ElementsKit::widget_url() . 'init/assets/js/fullcalendar/locales-all.global.min.js', [], \ElementsKit::version(), true );
		wp_register_script( 'full-calendar-google', \ElementsKit::widget_url() . 'init/assets/js/fullcalendar/google.global.min.js', [], \ElementsKit::version(), true );
	}

	public function frontend_css() {
		if(!is_admin()){
			wp_enqueue_style( 'ekit-widget-styles-pro', \ElementsKit::widget_url() . 'init/assets/css/widget-styles-pro.css', ['ekit-widget-styles'], \ElementsKit::version() );
		};
	}
}
