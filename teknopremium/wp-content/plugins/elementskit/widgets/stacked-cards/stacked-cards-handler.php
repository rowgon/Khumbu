<?php
namespace Elementor;

defined('ABSPATH') || exit;
class ElementsKit_Widget_Stacked_Cards_Handler extends \ElementsKit_Lite\Core\Handler_Widget {

    static function get_name() {
        return 'elementskit-stacked-cards';
    }

    static function get_title() {
        return esc_html__( 'Stacked Cards', 'elementskit' );
    }

    static function get_icon() {
        return 'ekit ekit-widget-icon ekit-stacked-cards';
    }

    static function get_categories() {
        return [ 'elementskit' ];
    }

	static function get_keywords() {
        return [ 'ekit', 'stacked', 'cards', 'info', 'stylish', 'stacking' ];
	}

	static function get_dir() {
		return \ElementsKit::widget_dir() . 'stacked-cards/';
	}

    static function get_url() {
        return \ElementsKit::widget_url() . 'stacked-cards/';
    }
}
