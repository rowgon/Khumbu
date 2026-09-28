<?php
namespace Elementor;

use Elementor\Controls_Manager;
use ElementsKit_Lite\Utils;

defined('ABSPATH') || die();

class ElementsKit_Scroll_Reveal {

    public  function __construct() {
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_controls' ] );
		add_action( 'elementor/element/column/section_advanced/after_section_end', [ $this, 'register_controls' ] );
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_controls' ] );
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ] );
	}

    public function enqueue_frontend_scripts($key = 'scroll_reveal_enable') {
        return [
            'styles' => [
				[
					'name' => 'scroll-reveal',
					'conditions' => [
						'terms' => [
							[
								'name' => $key,
								'operator' => '===',
								'value' => 'yes',
							],
						],
					],
				],
			],
			'scripts' => [
				[
					'name' => 'scroll-reveal',
					'conditions' => [
						'terms' => [
							[
								'name' => $key,
								'operator' => '===',
								'value' => 'yes',
							]
						],
					],
				],
			],
        ];
    }

    public function register_controls( $widget ) {
		$widget->start_controls_section(
			'section_scroll_reveal',
			[
				'label' => ( method_exists( Utils::class, 'get_promo_icon' ) ? Utils::get_promo_icon() : 'ElementsKit' ) . esc_html__( 'Scroll Reveal', 'elementskit' ),
				'tab' => Controls_Manager::TAB_ADVANCED,
			]
		);
        $widget->add_control(
            'scroll_reveal_enable',
            [
                'label' => esc_html__( 'Enable Scroll Reveal', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'elementskit' ),
                'label_off' => esc_html__( 'No', 'elementskit' ),
                'render_type'        => 'template',
                'return_value'       => 'yes',
                'frontend_available' => true,
				'assets' 		   => $this->enqueue_frontend_scripts(),
            ]
        );
        
		$widget->add_control(
			'scroll_reveal_direction',
			[
				'label' => esc_html__( 'Animation Direction', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'left' => esc_html__( 'Left', 'elementskit' ),
					'right' => esc_html__( 'Right', 'elementskit' ),
					'top' => esc_html__( 'Up', 'elementskit' ),
					'bottom' => esc_html__( 'Down', 'elementskit' ),
				],
				'default' => 'left',
                'frontend_available' => true,
                'condition' => [
                    'scroll_reveal_enable' => 'yes',
                ],
			]
		);

		$widget->add_control(
			'scroll_reveal_delay',
			[
				'label' => esc_html__( 'Transition Delay', 'elementskit' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 250,
				'min' => 0,
				'step' => 1,
                'condition' => [
                    'scroll_reveal_enable' => 'yes',
                ],
                'frontend_available' => true,
			]
		);

		$widget->add_control(
			'scroll_reveal_duration',
			[
				'label' => esc_html__( 'Animation Speed', 'elementskit' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 500,
				'min' => 0,
				'step' => 1,
                'condition' => [
                    'scroll_reveal_enable' => 'yes',
                ],
                'frontend_available' => true,
			]
		);
        $widget->add_control(
            'scroll_reveal_image_overlay',
            [
                'label' => esc_html__( 'Enable Image Overlay', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'elementskit' ),
                'label_off' => esc_html__( 'No', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'scroll_reveal_enable' => 'yes',
                ],
                'frontend_available' => true,
            ]
		);

        $widget->add_control(
            'scroll_reveal_overlay',
            [
                'label' => esc_html__( 'Overlay Color', 'elementskit' ),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.5)',
                'condition' => [
                    'scroll_reveal_enable' => 'yes',
                    'scroll_reveal_image_overlay' => 'yes',
                ],
                'frontend_available' => true,
            ]
		);

		$widget->add_control(
			'scroll_reveal_bg_primary',
			[
				'label' => esc_html__( 'Primary Reveal Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
                'conditions' => [
                   'terms' => [
                        [
                            'name' => 'scroll_reveal_enable',
                            'operator' => '===',
                            'value' => 'yes',
                        ],
                        [
                            'name' => 'scroll_reveal_image_overlay',
                            'operator' => '!==',
                            'value' => 'yes',
                        ],
                    ],
                ],
                'frontend_available' => true,
			]
		);

		// $widget->add_control(
		// 	'scroll_reveal_bg_secondary',
		// 	[
		// 		'label' => esc_html__( 'Secondary Reveal Color', 'elementskit' ),
		// 		'type' => Controls_Manager::COLOR,
		// 		'default' => '#ffffff',
        //         'conditions' => [
        //              'terms' => [
        //                     [
        //                      'name' => 'scroll_reveal_enable',
        //                      'operator' => '===',
        //                      'value' => 'yes',
        //                     ],
        //                     [
        //                      'name' => 'scroll_reveal_image_overlay',
        //                      'operator' => '!==',
        //                      'value' => 'yes',
        //                     ],
        //               ],
        //         ],
        //         'frontend_available' => true,
		// 	]
		// );

		$widget->add_control(
			'scroll_reveal_repeat',
			[
				'label' => esc_html__( 'Reveal Each Time on Scroll', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'yes',
                'condition' => [
                    'scroll_reveal_enable' => 'yes',
                ],
                'frontend_available' => true,
			]
		);

        $widget->end_controls_section();
    }
}