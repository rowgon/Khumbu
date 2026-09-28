<?php
namespace Elementor;
use \ElementsKit\Widgets\LoginForm\Login_Ajax_Handler;

class ElementsKit_Widget_Login_Form_Handler extends \ElementsKit_Lite\Core\Handler_Widget{

	static function get_name() {
		return 'elementskit-login-form';
	}

	static function get_title() {
		return esc_html__( 'Login Form', 'elementskit' );
	}

	static function get_icon() {
		return 'ekit ekit-widget-icon ekit-login';
	}

	static function get_categories() {
		return [ 'elementskit' ];
	}

	static function get_keywords() {
		return ['ekit', 'login', 'form', 'signin', 'sign in', 'authentication'];
	}

	static function get_dir() {
		return \ElementsKit::widget_dir() . 'login-form/';
	}

	static function get_url() {
		return \ElementsKit::widget_url() . 'login-form/';
	}

	static function is_disabled_register() {
		$enabled = (bool) get_option( 'users_can_register' );
		$value = apply_filters( 'elementskit/login-form/is_disabled_register', false );

		if ( $value ) {
			return [
				'value' => ! $value,
				'name'  => 'ekit_register',
			];
		}

		return [
			'value' => ! $enabled,
			'name'  => 'wp_register',
		];
	}

	public function wp_init() {
		new Login_Ajax_Handler();
	}
}
