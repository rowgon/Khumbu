<?php
namespace Elementor;
use \ElementsKit\Widgets\Copyright\Copyright_Shortcode as Shortcode;

class ElementsKit_Widget_Copyright_Handler extends \ElementsKit_Lite\Core\Handler_Widget {

    static function get_name() {
        return 'elementskit-copyright';
    }

    static function get_title() {
        return esc_html__( 'Copyright', 'elementskit' );
    }

    static function get_icon() {
        return 'ekit ekit-widget-icon ekit-copyright';
    }

    static function get_categories() {
        return [ 'elementskit' ];
    }

    static function get_dir() {
        return \ElementsKit::widget_dir() . 'copyright/';
    }

    static function get_url() {
        return \ElementsKit::widget_url() . 'copyright/';
    }
    public function wp_init() {
        new Shortcode();
    }
}
