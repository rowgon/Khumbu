<?php
namespace Elementor;

use Elementor\Controls_Manager;
use ElementsKit_Lite\Utils;

defined('ABSPATH') || die();

class ElementsKit_Wrapper_Link {

	public  function __construct() {
		add_action( 'elementor/element/column/section_advanced/after_section_end', [ $this, 'register_wrapper_section' ], 1 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_wrapper_section' ], 1 );
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_wrapper_section' ], 7 );
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_wrapper_section' ], 7 );
		add_action( 'elementor/frontend/before_render', [ $this, 'before_section_render' ], 1 );
	}

	public function enqueue_frontend_scripts() {
		return [
			'scripts' => [
				[
					'name' => 'elementskit-wrapper',
					'conditions' => [
						'terms' => [
							[
								'name' => 'elementskit_wrapper_link_enable',
								'operator' => '===',
								'value' => 'yes',
							],
						],
					],
				],
			],
		];
	}

	public function register_wrapper_section($element) {

		$element->start_controls_section(
			'elementskit_wrapper_link_section',
			[
				'label' => ( method_exists( Utils::class, 'get_promo_icon' ) ? Utils::get_promo_icon() : 'ElementsKit' ) . esc_html__( 'Wrapper Link', 'elementskit' ),
				'tab' => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'elementskit_wrapper_link_enable',
			[
				'label' => esc_html__( 'Enable Link', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'assets' => $this->enqueue_frontend_scripts(),
			]
		);

		$element->add_control(
			'elementskit_wrapper_link',
			[
				'label' => esc_html__( 'Link', 'elementskit' ),
				'type'  => Controls_Manager::URL,
				'dynamic' => [
					'active' => true,
				],
				'options' => [ 'url', 'is_external', 'nofollow' ],
				'condition' => [
					'elementskit_wrapper_link_enable' => 'yes',
				],
			]
		);

		$element->end_controls_section();
	}

	public function before_section_render($element) {
		$wrapper_settings = $element->get_settings_for_display( 'elementskit_wrapper_link' );
		if ( ! empty( $wrapper_settings['url'] ) &&  $wrapper_settings ) {

			if (isset($wrapper_settings['url'])) {
				$sanitized_url = ['url' => esc_url($wrapper_settings['url'])];
				$wrapper_settings = array_merge($wrapper_settings, $sanitized_url);
			}
			$cursor = $wrapper_settings['url'] ? 'cursor: pointer' : 'cursor: default';

			$element->add_render_attribute(
				'_wrapper',
				[
					'data-wrapper-link' => wp_json_encode( $wrapper_settings ),
					'style' => $cursor
				]
			);
		}
	}
}
