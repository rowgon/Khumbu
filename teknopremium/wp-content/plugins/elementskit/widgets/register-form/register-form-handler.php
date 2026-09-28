<?php
namespace Elementor;
use \ElementsKit\Widgets\RegisterForm\Register_Ajax_Handler;
defined('ABSPATH') || exit;

class ElementsKit_Widget_Register_Form_Handler extends \ElementsKit_Lite\Core\Handler_Widget {

    static function get_name() {
        return 'elementskit-register-form';
    }

    static function get_title() {
        return esc_html__( 'Register Form', 'elementskit' );
    }

    static function get_icon() {
        return 'ekit ekit-widget-icon ekit-register';
    }

    static function get_categories() {
        return [ 'elementskit' ];
    }

    static function get_keywords() {
        return [ 'ekit', 'register', 'form', 'signup', 'membership' ];
    }

    static function get_dir() {
        return \ElementsKit::widget_dir() . 'register-form/';
    }

    static function get_url() {
        return \ElementsKit::widget_url() . 'register-form/';
    }
    public function wp_init() {
		new Register_Ajax_Handler();
	}

    static function is_disabled_register() {
        $enabled = (bool) get_option( 'users_can_register' );
        $value = apply_filters( 'elementskit/register-form/is-disabled', false );
    
        if ( $value ) {
           return [
                'value' => !$value,
                'name' => 'ekit_register'
           ];
        } else {
            return [
                'value' => !$enabled,
                'name' => 'wp_register'
            ];
        }
    }
}
