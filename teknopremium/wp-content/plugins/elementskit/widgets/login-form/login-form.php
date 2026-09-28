<?php
namespace Elementor;

use \Elementor\ElementsKit_Widget_Login_Form_Handler as Handler;

if ( ! defined( 'ABSPATH' ) ) exit;

class ElementsKit_Widget_Login_Form extends Widget_Base {
	use \ElementsKit_Lite\Widgets\Widget_Notice;

	public function get_name() {
        return Handler::get_name();
    }

    public function get_title() {
        return Handler::get_title();
    }

    public function get_icon() {
        return Handler::get_icon();
    }

    public function get_categories() {
        return Handler::get_categories();
    }

	public function get_keywords() {
		return Handler::get_keywords();
	}

    public function get_help_url() {
        return 'https://wpmet.com/doc/login-form/';
    }

	public function get_script_depends() {
		return ['ekit-pro-login-form'];
	}
	
    protected function is_dynamic_content(): bool {
        return false;
    }

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Get array of Style type.
	 *
	 * @since 1.5.0
	 * @access protected
	 * @return array
	 */
	protected function get_style_type() {

		$style = array(
			'style-1' => esc_html__( 'Icon with providers label', 'elementskit' ),
			'style-2' => esc_html__( 'Only social icon', 'elementskit' ),
			'style-3' => esc_html__( 'Only providers label', 'elementskit' ),
		);

		// Add Pro styles only if WP_Social Pro is active
		if ( class_exists( '\WP_Social' ) && \WP_Social::is_pro_active() ) {
			$style['style-4'] = esc_html__( 'Icon Overlay', 'elementskit' );
			$style['style-5'] = esc_html__( 'Left Slide', 'elementskit' );
			$style['style-6'] = esc_html__( 'Circle Blow', 'elementskit' );
			$style['style-7'] = esc_html__( 'Left Slide Overlay', 'elementskit' );
			$style['style-8'] = esc_html__( 'Circle Line Icon', 'elementskit' );
			$style['style-9'] = esc_html__( 'Slide to arrow', 'elementskit' );
			$style['style-10'] = esc_html__( 'Stroke right radius', 'elementskit' );
			$style['style-11'] = esc_html__( 'Gradient Icon', 'elementskit' );
			$style['style-12'] = esc_html__( 'Box Style', 'elementskit' );
			$style['style-13'] = esc_html__( 'Drop icon', 'elementskit' );
			$style['style-14'] = esc_html__( 'Minimal Stack Icons', 'elementskit' );
			$style['style-15'] = esc_html__( 'Capsule Icons', 'elementskit' );
			$style['style-16'] = esc_html__( 'Dark Dot Icons', 'elementskit' );
			$style['style-17'] = esc_html__( 'Dark Card Icons', 'elementskit' );
			$style['style-18'] = esc_html__( 'Dark Ghost Icons', 'elementskit' );
			$style['style-19'] = esc_html__( 'Dark Auth Stack Icons', 'elementskit' );
			$style['style-20'] = esc_html__( 'Floating Icons', 'elementskit' );
			$style['style-21'] = esc_html__( 'Clean Auth Icons', 'elementskit' );
			$style['style-22'] = esc_html__( 'Bubble Icons', 'elementskit' );
			$style['style-23'] = esc_html__( 'Bordered Card Icons', 'elementskit' );
		}

		$style = apply_filters( 'elementskit/social/style', $style );

		return $style;
	}

    protected function register_controls() {

		// Form Fields Section
		$this->start_controls_section(
			'ekit_section_login_form_fields',
			[
				'label' => esc_html__( 'Form Fields', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

        $this->add_control(
            'ekit_login_show_labels',
            [
                'label'   => esc_html__( 'Field Label', 'elementskit' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'yes',
                'options' => [
                    'yes' => esc_html__( 'Default', 'elementskit' ),
                    'no'  => esc_html__( 'Hide', 'elementskit' ),
                    'custom' => esc_html__( 'Custom', 'elementskit' ),
                ],
                'default' => 'yes',
            ]
		);

		$this->add_control(
            'ekit_login_username_label',
            [
                'label'   => esc_html__( 'Username Label', 'elementskit' ),
				'label_block' => true,
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Username or Email Address', 'elementskit' ),
				'condition' => [
					'ekit_login_show_labels' => 'custom',
				],
				'dynamic' => [
					'active' => true,
				],
            ]
		);

		$this->add_control(
            'ekit_login_password_label',
            [
                'label'   => esc_html__( 'Password Label', 'elementskit' ),
				'label_block' => true,
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Password', 'elementskit' ),
				'condition' => [
					'ekit_login_show_labels' => 'custom',
				],
				'dynamic' => [
					'active' => true,
				],
				'separator' => 'after',
            ]
		);

		$this->add_control(
			'ekit_lost_password_instruction',
			[
				'label' => esc_html__( 'Lost Password Instruction', 'elementskit' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Please enter your username or email address. You will receive a link to create a new password via email.', 'elementskit' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'ekit_lost_password_label',
			[
				'label'   => esc_html__( 'Lost Password Label', 'elementskit' ),
				'label_block' => true,
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Username or Email', 'elementskit' ),
				'condition' => [
					'ekit_login_show_labels' => 'custom',
				],
				'dynamic' => [
					'active' => true,
				],
            ]
		);

        $this->add_control(
            'ekit_login_field_show_placeholder',
            [
                'label' => esc_html__( 'Show Placeholder', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'elementskit' ),
                'label_off' => esc_html__( 'Hide', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

		$this->add_control(
            'ekit_login_username_placeholder',
            [
                'label'   => esc_html__( 'Username Placeholder', 'elementskit' ),
				'label_block' => true,
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Enter your username or email', 'elementskit' ),
                'condition' => [
                    'ekit_login_field_show_placeholder' => 'yes',
                    'ekit_login_show_labels' => 'custom',
                ],
				'dynamic' => [
					'active' => true,
				],
            ]
		);

		$this->add_control(
            'ekit_login_password_placeholder',
            [
                'label'   => esc_html__( 'Password Placeholder', 'elementskit' ),
				'label_block' => true,
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__('Enter your password', 'elementskit' ),
                'condition' => [
                    'ekit_login_field_show_placeholder' => 'yes',
                    'ekit_login_show_labels' => 'custom',
                ],
				'dynamic' => [
					'active' => true,
				],
            ]
		);

		$this->add_control(
			'ekit_lost_password_placeholder',
			[
				'label'   => esc_html__( 'Lost Password Placeholder', 'elementskit' ),
				'label_block' => true,
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__('Enter your username or email', 'elementskit' ),
				'condition' => [
					'ekit_login_field_show_placeholder' => 'yes',
					'ekit_login_show_labels' => 'custom',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

        $this->add_control(
            'ekit_login_field_size',
            [
                'label' => esc_html__( 'Field Size', 'elementskit' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'md',
                'options' => [
                    'xs' => esc_html__( 'Extra Small', 'elementskit' ),
                    'sm' => esc_html__( 'Small', 'elementskit' ),
                    'md'  => esc_html__( 'Medium', 'elementskit' ),
                    'lg' => esc_html__( 'Large', 'elementskit' ),
                    'xl' => esc_html__( 'Extra Large', 'elementskit' ),
                ],
            ]
        );

		$this->add_control(
			'ekit_login_remember_me',
			[
				'label' => esc_html__( 'Remember Me', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementskit' ),
				'label_off' => esc_html__( 'Hide', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
            'ekit_login_remember_me_text',
            [
                'label'   => esc_html__( 'Remember Me Text', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Remember Me', 'elementskit' ),
				'condition' => [
					'ekit_login_remember_me' => 'yes',
				],
            ]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_section_login_fields_icons',
			[
				'label' => esc_html__( 'Fields Icons', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);
        $this->add_control(
            'ekit_login_fields_icons',
            [
                'label' => esc_html__( 'Fields Icons', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'elementskit' ),
                'label_off' => esc_html__( 'Hide', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'ekit_login_fields_divider',
            [
                'label' => esc_html__( 'Icons Divider', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'elementskit' ),
                'label_off' => esc_html__( 'Hide', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'no',
				'condition' => [
					'ekit_login_fields_icons' => 'yes',
				],
            ]
        );

		// Icons position and selection
		$this->add_control(
			'ekit_login_icon_position',
			[
				'label' => esc_html__( 'Icons Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'start' => esc_html__( 'Start', 'elementskit' ),
					'end'   => esc_html__( 'End', 'elementskit' ),
				],
				'default' => 'start',
				'condition' => [
					'ekit_login_fields_icons' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_username_icon',
			[
				'label' => esc_html__( 'Username Icon', 'elementskit' ),
				'type'  => Controls_Manager::ICONS,
				'condition' => [
					'ekit_login_fields_icons' => 'yes',
				],
				'label_block' => false,
				'skin' => 'inline',
				'fa4compatibility' => 'icon',
				'default' => [
					'value' => 'fas fa-user',
					'library' => 'fa-regular',
				],
			]
		);

		$this->add_control(
			'ekit_login_password_icon',
			[
				'label' => esc_html__( 'Password Icon', 'elementskit' ),
				'type'  => Controls_Manager::ICONS,
				'condition' => [
					'ekit_login_fields_icons' => 'yes',
				],
				'label_block' => false,
				'skin' => 'inline',
				'fa4compatibility' => 'icon',
				'default' => [
					'value' => 'fas fa-unlock-alt',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'ekit_lost_password_icon',
			[
				'label' => esc_html__( 'Lost Icon', 'elementskit' ),
				'type'  => Controls_Manager::ICONS,
				'condition' => [
					'ekit_login_fields_icons' => 'yes',
				],
				'label_block' => false,
				'skin' => 'inline',
				'fa4compatibility' => 'icon',
				'default' => [
					'value' => 'fas fa-key',
					'library' => 'fa-solid',
				],
			]
		);

		$this->end_controls_section();

		// Button Section
		$this->start_controls_section(
			'ekit_section_login_button',
			[
				'label' => esc_html__( 'Button', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
            'ekit_login_button_text',
            [
                'label'   => esc_html__( 'Button Text', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Log In', 'elementskit' ),
            ]
		);

		$this->add_responsive_control(
			'ekit_login_button_align',
			[
				'label' => esc_html__( 'Alignment', 'elementskit' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => esc_html__( 'Left', 'elementskit' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementskit' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'elementskit' ),
						'icon' => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'elementskit' ),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'default' => 'left',
				'selectors_dictionary' => [
					'left' => 'text-align: left',
					'center' => 'text-align: center',
					'right' => 'text-align: right',
					'justify' => 'width: 100%; text-align: justify;',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button-wrapper' => '{{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_button_size',
			[
				'label' => esc_html__( 'Button Size', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'sm',
				'options' => [
					'xs' => esc_html__( 'Extra Small', 'elementskit' ),
					'sm' => esc_html__( 'Small', 'elementskit' ),
					'md'  => esc_html__( 'Medium', 'elementskit' ),
					'lg' => esc_html__( 'Large', 'elementskit' ),
					'xl' => esc_html__( 'Extra Large', 'elementskit' ),
				],
			]
		);

		$this->add_control(
			'ekit_login_button_icon',
			[
				'label' => esc_html__( 'Button Icon', 'elementskit' ),
				'type'  => Controls_Manager::ICONS,
			]
		);

		$this->add_control(
			'ekit_login_button_icon_position',
			[
				'label' => esc_html__( 'Icon Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'left' => esc_html__( 'Before', 'elementskit' ),
					'right'   => esc_html__( 'After', 'elementskit' ),
				],
				'default' => 'left',
				'condition' => [
					'ekit_login_button_icon[value]!' => '',
				],
				'selectors_dictionary' => [
					'left' => '	flex-direction: row;',
					'right' => 'flex-direction: row-reverse;',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button-wrapper .ekit-login-form-button' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_login_button_icon_spacing',
			[
				'label' => esc_html__( 'Icon Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button-wrapper .ekit-login-form-button' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'ekit_login_button_icon[value]!' => '',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_reset_button_section',
			[
				'label' => esc_html__( 'Reset Password', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_login_reset_button_align',
			[
				'label' => esc_html__( 'Alignment', 'elementskit' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => esc_html__( 'Left', 'elementskit' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementskit' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'elementskit' ),
						'icon' => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'elementskit' ),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'default' => 'left',
				'selectors_dictionary' => [
					'left' => 'justify-content: left; display: flex; flex-direction: row',
					'center' => 'justify-content: center ; display: flex; flex-direction: row',
					'right' => 'justify-content: right ; display: flex; flex-direction: row',
					'justify' => 'width: 100%; text-align: justify; display: flex; flex-direction: column;',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button-wrapper.ekit-reset' => '{{VALUE}};',
				],
			]
		);
		$this->add_control(
			'ekit_login_reset_button_gap',
			[
				'label' => esc_html__( 'Button Gap', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'size' => 0,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button-wrapper.ekit-reset' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_reset_button_size',
			[
				'label' => esc_html__( 'Button Size', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'sm',
				'options' => [
					'xs' => esc_html__( 'Extra Small', 'elementskit' ),
					'sm' => esc_html__( 'Small', 'elementskit' ),
					'md'  => esc_html__( 'Medium', 'elementskit' ),
					'lg' => esc_html__( 'Large', 'elementskit' ),
					'xl' => esc_html__( 'Extra Large', 'elementskit' ),
				],
				'separator' => 'after',
			]
		);

		$this->add_control(
			'ekit_login_reset_button_text',
			[
				'label'   => esc_html__( 'Reset Password', 'elementskit' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Reset Password', 'elementskit' ),
			]
		);


		$this->add_control(
			'ekit_login_back_button_text',
			[
				'label'   => esc_html__( 'Back to Login', 'elementskit' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Back to Login', 'elementskit' ),
			]
		);

		$this->end_controls_section();

		// Links Section
		$this->start_controls_section(
			'ekit_section_login_links',
			[
				'label' => esc_html__( 'Links', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_login_show_lost_password',
			[
				'label' => esc_html__( 'Lost Password Link', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementskit' ),
				'label_off' => esc_html__( 'Hide', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->add_control(
			'ekit_login_show_lost_password_position',
			[
				'label' => esc_html__( 'Link Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'top' => esc_html__( 'Top', 'elementskit' ),
					'bottom'   => esc_html__( 'Bottom', 'elementskit' ),
					'form_start' => esc_html__( 'Form Start', 'elementskit' ),
					'form_end'   => esc_html__( 'Form End', 'elementskit' ),
				],
				'default' => 'bottom',
				'condition' => [
					'ekit_login_show_lost_password' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_login_show_lost_password_align',
			[
				'label' => esc_html__( 'Link Alignment', 'elementskit' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => esc_html__( 'Left', 'elementskit' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementskit' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'elementskit' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'default' => 'left',
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.lost-password-link' => '--ekit-login-link-alignment: {{VALUE}};',

				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_lost_password',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_login_show_lost_password_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_login_show_lost_password_position',
									'operator' => '===',
									'value'    => 'form_end',
								],
							],
						],
					],
				],
			]
		);
		$this->add_control(
            'ekit_login_lost_password_text',
            [
                'label'   => esc_html__( 'Lost Password Text', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Forgot Password?', 'elementskit' ),
				'condition' => [
					'ekit_login_show_lost_password' => 'yes',
				],
            ]
		);
		$this->add_control(
			'ekit_login_lost_password_text_before_after',
			[
				'label' => esc_html__( 'Text Before/After Link', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'before' => esc_html__( 'Before', 'elementskit' ),
					'after'   => esc_html__( 'After', 'elementskit' ),
				],
				'default' => 'after',
				'selectors_dictionary' => [
					'before' => 'flex-direction: row-reverse;',
					'after' => 'flex-direction: row;',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links' => '{{VALUE}};',
				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_lost_password',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_login_show_lost_password_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_login_show_lost_password_position',
									'operator' => '===',
									'value'    => 'form_end',
								],
							],
						],
					],
				],
			]
		);

		$this->add_control(
			'ekit_login_show_lost_password_description',
			[
				'label'   => esc_html__( 'Description', 'elementskit' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Click here to reset your password.', 'elementskit' ),
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_lost_password',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_login_show_lost_password_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_login_show_lost_password_position',
									'operator' => '===',
									'value'    => 'form_end',
								],
							],
						],
					],
				],
			]
		);

		$this->add_control(
			'ekit_login_show_register',
			[
				'label' => esc_html__( 'Register Link', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementskit' ),
				'label_off' => esc_html__( 'Hide', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'separator' => 'before',
			]
		);
		$this->add_control(
			'ekit_login_register_disabled_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => sprintf(
					'<span><strong>%1$s</strong> %2$s</span>',
					esc_html__( 'Note:', 'elementskit' ),
					sprintf(
						/* translators: %s: link to WordPress General Settings */
						esc_html__(
							'User registration is disabled. Please enable it in the settings: %s',
							'elementskit'
						),
						sprintf(
							'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
							esc_url( admin_url( 'options-general.php' ) ),
							esc_html__( 'Go to Settings', 'elementskit' )
						)
					)
				),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'show_label'      => false,
				'label_block'    => false,
			]
		);


		$this->add_control(
            'ekit_login_register_text',
            [
                'label'   => esc_html__( 'Register Link Text', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Register', 'elementskit' ),
				'condition' => [
					'ekit_login_show_register' => 'yes',
				],
            ]
		);

		$this->add_control(
			'ekit_login_show_register_position',
			[
				'label' => esc_html__( 'Link Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'form_start' => esc_html__( 'Form Start', 'elementskit' ),
					'form_end'   => esc_html__( 'Form End', 'elementskit' ),
				],
				'default' => 'form_end',
				'condition' => [
					'ekit_login_show_register' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_login_show_register_align',
			[
				'label' => esc_html__( 'Link Alignment', 'elementskit' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => esc_html__( 'Left', 'elementskit' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementskit' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'elementskit' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'default' => 'left',
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.register-link' => '--ekit-login-link-alignment: {{VALUE}};',

				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_register',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_login_show_register_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_login_show_register_position',
									'operator' => '===',
									'value'    => 'form_end',
								],
							],
						],
					],
				],
			]
		);
		$this->add_control(
			'ekit_login_register_text_before_after',
			[
				'label' => esc_html__( 'Text Before/After Link', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'before' => esc_html__( 'Before', 'elementskit' ),
					'after'   => esc_html__( 'After', 'elementskit' ),
				],
				'default' => 'after',
				'selectors_dictionary' => [
					'before' => 'flex-direction: row-reverse;',
					'after' => 'flex-direction: row;',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links' => '{{VALUE}};',
				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_register',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_login_show_register_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_login_show_register_position',
									'operator' => '===',
									'value'    => 'form_end',
								],
							],
						],
					],
				],
			]
		);
		$this->add_control(
			'ekit_login_show_register_description',
			[
				'label'   => esc_html__( 'Description', 'elementskit' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Click here to create an account.', 'elementskit' ),
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_register',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_login_show_register_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_login_show_register_position',
									'operator' => '===',
									'value'    => 'form_end',
								],
							],
						],
					],
				],
			]
		);
		$this->add_control(
			'ekit_login_register_url',
			[
				'label' => esc_html__( 'Register URL', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'elementskit' ),
				'condition' => [
					'ekit_login_show_register' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// Settings Section
		$this->start_controls_section(
			'ekit_section_login_settings',
			[
				'label' => esc_html__( 'Settings', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_login_password_toggle',
			[
				'label' => esc_html__( 'Password Toggle', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementskit' ),
				'label_off' => esc_html__( 'Hide', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'ekit_login_remember_me_duration',
			[
				'label'   => esc_html__( 'Remember Me Duration', 'elementskit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '1209600', // 14 days in seconds
				'options' => [
					'60'       => esc_html__( '1 Minute', 'elementskit' ),
					'3600'     => esc_html__( '1 Hour', 'elementskit' ),
					'18000'    => esc_html__( '5 Hours', 'elementskit' ),
					'43200'    => esc_html__( '12 Hours', 'elementskit' ),
					'86400'    => esc_html__( '1 Day', 'elementskit' ),
					'172800'   => esc_html__( '2 Days', 'elementskit' ),
					'259200'   => esc_html__( '3 Days', 'elementskit' ),
					'604800'   => esc_html__( '7 Days', 'elementskit' ),
					'1209600'  => esc_html__( '14 Days', 'elementskit' ),
					'2592000'  => esc_html__( '30 Days', 'elementskit' ),
				],
				'condition' => [
					'ekit_login_remember_me' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_redirect_after_login',
			[
				'label' => esc_html__( 'Redirect After Login', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'ekit_login_redirect_url',
			[
				'label' => esc_html__( 'Redirect URL', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'elementskit' ),
				'condition' => [
					'ekit_login_redirect_after_login' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_hide_for_logged_in',
			[
				'label' => esc_html__( 'Hide for Logged-In Users', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
			]
		);

		$this->add_control(
			'ekit_login_logged_in_message',
			[
				'label' => esc_html__( 'Logged In Message', 'elementskit' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'You are already logged in.', 'elementskit' ),
				'condition' => [
					'ekit_login_hide_for_logged_in' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_wp_social_login',
			[
				'label' => esc_html__( 'Enable Social Login', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_login_wp_social_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => sprintf(
					'<span><strong>%1$s</strong> %2$s</span>',
					esc_html__( 'Note:', 'elementskit' ),
					wp_kses_post(
						__( 'You must have <strong>WP Social</strong> installed and activated; otherwise, this feature will not work. <a href="https://wordpress.org/plugins/wp-social/" target="_blank" rel="noopener noreferrer">Install WP Social</a>', 'elementskit' )
					)
				),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'show_label'      => false,
				'label_block'    => false,
			]
		);

		$this->add_control(
			'ekit_login_social_login_position',
			[
				'label' => esc_html__( 'Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'top' => esc_html__( 'Top', 'elementskit' ),
					'bottom'   => esc_html__( 'Bottom', 'elementskit' ),
				],
				'default' => 'top',
				'condition' => [
					'ekit_login_wp_social_login' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_login_social_login_style',
			[
				'label' => esc_html__( 'Style', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => $this->get_style_type(),
				'default' => 'style-1',
				'condition' => [
					'ekit_login_wp_social_login' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_social_login_separator',
			[
				'label'   => esc_html__( 'Show Separator', 'elementskit' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'condition' => [
					'ekit_login_wp_social_login' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_social_login_separator_text',
			[
				'label'   => esc_html__( 'Separator Text', 'elementskit' ),
				'type'    =>Controls_Manager::TEXT,
				'default' => esc_html__( 'Or login with', 'elementskit' ),
				'condition' => [
					'ekit_login_wp_social_login' => 'yes',
					'ekit_login_social_login_separator' => 'yes',
				],
			]
		);
		$this->end_controls_section();

		// Style: Labels
		$this->start_controls_section(
			'ekit_section_login_labels_style',
			[
				'label' => esc_html__( 'Labels', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_login_show_labels!' => 'no',
				],
			]
		);

		$this->add_control(
			'ekit_login_label_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_login_label_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-field label',
			]
		);

		$this->add_responsive_control(
			'ekit_login_label_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px','em', 'rem'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
					'em' => [
						'min' => 0,
						'max' => 5,
					],
					'rem' => [
						'min' => 0,
						'max' => 5,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Input Fields
		$this->start_controls_section(
			'ekit_section_login_fields_style',
			[
				'label' => esc_html__( 'Fields', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'ekit_login_field_tabs' );

		$this->start_controls_tab(
			'ekit_login_field_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_field_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field input[type="text"]' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="password"]' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="email"]' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_field_placeholder_color',
			[
				'label' => esc_html__( 'Placeholder Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field input::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_login_field_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"], {{WRAPPER}} .ekit-login-form-field input[type="password"], {{WRAPPER}} .ekit-login-form-field input[type="email"]',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_login_field_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"], {{WRAPPER}} .ekit-login-form-field input[type="password"], {{WRAPPER}} .ekit-login-form-field input[type="email"]',
			]
		);

		$this->add_responsive_control(
			'ekit_login_field_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field input[type="text"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="password"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="email"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_login_field_box_shadow',
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"], {{WRAPPER}} .ekit-login-form-field input[type="password"], {{WRAPPER}} .ekit-login-form-field input[type="email"]',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_login_field_focus',
			[
				'label' => esc_html__( 'Focus', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_field_focus_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field input[type="text"]:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="password"]:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="email"]:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_login_field_focus_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"]:focus, {{WRAPPER}} .ekit-login-form-field input[type="password"]:focus, {{WRAPPER}} .ekit-login-form-field input[type="email"]:focus',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_login_field_focus_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"]:focus, {{WRAPPER}} .ekit-login-form-field input[type="password"]:focus, {{WRAPPER}} .ekit-login-form-field input[type="email"]:focus',
			]
		);

		$this->add_responsive_control(
			'ekit_login_field_focus_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field input[type="text"]:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="password"]:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-login-form-field input[type="email"]:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_login_field_focus_box_shadow',
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"]:focus, {{WRAPPER}} .ekit-login-form-field input[type="password"]:focus, {{WRAPPER}} .ekit-login-form-field input[type="email"]:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_login_field_padding',
			[
				'label' => esc_html__( 'Padding', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field input[type="text"],
					{{WRAPPER}} .ekit-login-form-field input[type="password"],
					{{WRAPPER}} .ekit-login-form-field input[type="email"]'
					=> 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'ekit_login_field_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
					],
					'rem' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_login_field_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-field input[type="text"], {{WRAPPER}} .ekit-login-form-field input[type="password"], {{WRAPPER}} .ekit-login-form-field input[type="email"]',
			]
		);

		$this->end_controls_section();

		// Style: Fields Icons
		$this->start_controls_section(
			'ekit_section_login_fields_icons_style',
			[
				'label' => esc_html__( 'Fields Icons', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_login_fields_icons' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_login_fields_icons_color',
			[
				'label' => esc_html__( 'Icons Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field .ekit-login-form-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_fields_icons_size',
			[
				'label' => esc_html__( 'Icons Size', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 8,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-field .ekit-login-form-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_fields_icons_divider_color',
			[
				'label' => esc_html__( 'Divider Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'ekit_login_fields_divider' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-icon.ekit-icon-end::before' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-login-form-icon.ekit-icon-start::after' => 'border-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_login_fields_divider_width',
			[
				'label' => esc_html__('Divider Width', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 10,
					],
					'%' => [
						'min' => 1,
						'max' => 100,
					],
				],
				'condition' => [
					'ekit_login_fields_divider' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-icon.ekit-icon-end::before' => 'border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-login-form-icon.ekit-icon-start::after' => 'border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		// Style: Remember Me
		$this->start_controls_section(
			'ekit_section_login_remember_style',
			[
				'label' => esc_html__( 'Remember Me', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_login_remember_me' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_remember_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-remember label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_remember_checkbox_color',
			[
				'label' => esc_html__( 'Checkbox Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-remember input[type="checkbox"]' => 'accent-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_login_remember_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-remember label',
			]
		);

		$this->add_responsive_control(
			'ekit_login_remember_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
					],
					'rem' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-remember' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Submit Button
		$this->start_controls_section(
			'ekit_section_login_button_style',
			[
				'label' => esc_html__( 'Button', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'ekit_login_button_tabs' );

		$this->start_controls_tab(
			'ekit_login_button_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_button_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_login_button_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-login-form-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_login_button_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-button',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_login_button_box_shadow',
				'label' => esc_html__( 'Box Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-button',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_login_button_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_button_hover_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_login_button_hover_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-login-form-button:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_login_button_hover_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-button:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_login_button_hover_box_shadow',
				'label' => esc_html__( 'Box Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-button:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_login_button_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_login_button_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-button',
			]
		);

		$this->add_responsive_control(
			'ekit_login_button_padding',
			[
				'label' => esc_html__( 'Padding', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_button_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_login_button_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 10,
					],
					'rem' => [
						'min' => 0,
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-button-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		$this->start_controls_section(
			'ekit_lost_reset_button_style_section',
			[
				'label' => esc_html__( 'Reset Password', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'ekit_lost_reset_button_tabs' );

		$this->start_controls_tab(
			'ekit_lost_reset_button_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_lost_reset_button_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-lost-password-form-back' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-lost-password-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_lost_reset_button_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-lost-password-button, {{WRAPPER}} .ekit-lost-password-form-back',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_lost_reset_button_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-lost-password-button, {{WRAPPER}} .ekit-lost-password-form-back',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_lost_reset_button_box_shadow',
				'label' => esc_html__( 'Box Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-lost-password-button, {{WRAPPER}} .ekit-lost-password-form-back',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_lost_reset_button_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_lost_reset_button_hover_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-lost-password-form-back:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-lost-password-button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_lost_reset_button_hover_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-lost-password-button:hover, {{WRAPPER}} .ekit-lost-password-form-back:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_lost_reset_button_hover_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-lost-password-button:hover, {{WRAPPER}} .ekit-lost-password-form-back:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_lost_reset_button_hover_box_shadow',
				'label' => esc_html__( 'Box Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-lost-password-button:hover, {{WRAPPER}} .ekit-lost-password-form-back:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_lost_reset_button_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-lost-password-form-back' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-lost-password-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_lost_reset_button_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-lost-password-button, {{WRAPPER}} .ekit-lost-password-form-back',
			]
		);

		$this->add_responsive_control(
			'ekit_lost_reset_button_padding',
			[
				'label' => esc_html__( 'Padding', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-lost-password-form-back' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-lost-password-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_lost_reset_button_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-lost-password-form-back' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-lost-password-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		// Style: Links
		$this->start_controls_section(
			'ekit_section_login_links_style',
			[
				'label' => esc_html__( 'Links', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'ekit_login_links-lost_heading',
			[
				'label' => esc_html__( 'Lost Password Link', 'elementskit' ),
				'type' => Controls_Manager::HEADING,
			]
		);
		$this->start_controls_tabs( 'ekit_login_links_tabs' );

		$this->start_controls_tab(
			'ekit_login_links_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_links_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.lost-password-link a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_login_links_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_links_hover_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.lost-password-link a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'ekit_login_links_decoration_color',
			[
				'label' => esc_html__( 'Decoration Color', 'elementskit' ),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.lost-password-link a' => 'text-decoration-color: {{VALUE}};',
				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_lost_password',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'name'     => 'ekit_login_show_lost_password_position',
							'operator' => 'in',
							'value'    => [ 'form_start', 'form_end' ],
						],
					],
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_login_links_desc_color',
			[
				'label' => esc_html__( 'Description Color', 'elementskit' ),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.lost-password-link' => 'color: {{VALUE}};',
				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_lost_password',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'name'     => 'ekit_login_show_lost_password_position',
							'operator' => 'in',
							'value'    => [ 'form_start', 'form_end' ],
						],
					],
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_login_links_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-links.lost-password-link, {{WRAPPER}} .ekit-login-form-links.lost-password-link a',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_login_links_lost_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.lost-password-link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_login_links_register_heading',
			[
				'label' => esc_html__( 'Register Link', 'elementskit' ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);
		
		$this->start_controls_tabs( 'ekit_login_register_links_tabs' );
		$this->start_controls_tab(
			'ekit_login_register_links_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_register_links_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.register-link a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_login_register_links_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_login_register_links_hover_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.register-link a:hover' => 'color: {{VALUE}};',
				],
			]
		);

        $this->end_controls_tab();
        $this->end_controls_tabs();

		$this->add_control(
			'ekit_login_register_links_decoration_color',
			[
				'label' => esc_html__( 'Decoration Color', 'elementskit' ),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.register-link a' => 'text-decoration-color: {{VALUE}};',
				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_login_show_register',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'name'     => 'ekit_login_show_register_position',
							'operator' => 'in',
							'value'    => [ 'form_start', 'form_end' ],
						],
					],
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_login_register_links_desc_color',
			[
				'label' => esc_html__( 'Description Color', 'elementskit' ),
				'type'  => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.register-link' => 'color: {{VALUE}};',
				]
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_login_register_links_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-login-form-links.register-link, {{WRAPPER}} .ekit-login-form-links.register-link a',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_login_links_register_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-form-links.register-link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		$this->start_controls_section(
			'ekit_login_section_social_style',
			[
				'label' => esc_html__( 'Social Login', 'elementskit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_login_wp_social_login' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_login_social_login_margin',
			[
				'label'      => esc_html__( 'Margin', 'elementskit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'allowed_dimensions' => 'vertical',
				'default'    => [
					'top' => '30',
					'right' => 'auto',
					'bottom' => '30',
					'left' => 'auto',
					'unit' => 'px',
					'isLinked' => 'false',
				],
				'placeholder' => [
					'top' => '',
					'right' => 'auto',
					'bottom' => '',
					'left' => 'auto',
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-login-form #xs-social-login-container' =>
						'margin-top: {{TOP}}{{UNIT}}; margin-bottom: {{BOTTOM}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'ekit_login_social_logi_padding',
			[
				'label'      => esc_html__( 'Padding', 'elementskit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default'    => [
					'top' => '0',
					'right' => '0',
					'bottom' => '0',
					'left' => '0',
					'unit' => 'px',
					'isLinked' => 'false',
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-login-form #xs-social-login-container' =>
						'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		$this->start_controls_section(
			'ekit_login_section_separator_style',
			[
				'label' => esc_html__( 'Separator', 'elementskit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_login_wp_social_login' => 'yes',
					'ekit_login_social_login_separator' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_login_separator_text_color',
			[
				'label'     => __( 'Text Color', 'elementskit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-separator span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_login_separator_typography',
				'selector' => '{{WRAPPER}} .ekit-login-separator span',
			]
		);

		$this->add_control(
			'ekit_login_separator_line_color',
			[
				'label'     => __( 'Line Color', 'elementskit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-login-separator::before,
					{{WRAPPER}} .ekit-login-separator::after' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'ekit_login_separator_line_height',
			[
				'label'     => esc_html__( 'Line Height', 'elementskit' ),
				'type'      => Controls_Manager::SLIDER,
				'size_units'=> [ 'px' ],
				'range'     => [
					'px' => [
						'min' => 1,
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-login-separator::before,
					{{WRAPPER}} .ekit-login-separator::after' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_login_separator_margin',
			[
				'label'      => esc_html__( 'Margin', 'elementskit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .ekit-login-separator' =>
						'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

    }

	protected function render() { ?>
		<div class="ekit-wid-con">
			<?php $this->render_row(); ?>
		</div>
		<?php
	}

	protected function render_row() {
		$settings = $this->get_settings_for_display();

		// Update database setting based on control value
		if ( ! empty( $settings['ekit_login_wp_social_login'] ) ) {
			$style_data = get_option( 'xs_style_setting_data', [] );
			if ( ! is_array( $style_data ) ) {
				$style_data = [];
			}
			$style_data['login_button_style'] = $settings['ekit_login_social_login_style'];
			update_option( 'xs_style_setting_data', $style_data );
		}
    
		// Check if user is logged in
		if ( is_user_logged_in() && 'yes' === $settings['ekit_login_hide_for_logged_in'] ) {
			echo '<div class="ekit-login-form-logged-in-message">' . esc_html( $settings['ekit_login_logged_in_message'] ) . '</div>';
			return;
		}

		// Determine field display settings
		$determine = [
			'show_icons'   => ( isset( $settings['ekit_login_fields_icons'] ) && 'yes' === $settings['ekit_login_fields_icons'] ),
			'show_divider' => ( isset( $settings['ekit_login_fields_divider'] ) && 'yes' === $settings['ekit_login_fields_divider'] ),
			'icon_pos'     => ! empty( $settings['ekit_login_icon_position'] ) ? $settings['ekit_login_icon_position'] : 'start',
			'show_toggle'  => ( ! isset( $settings['ekit_login_password_toggle'] ) ) ? true : ( 'yes' === $settings['ekit_login_password_toggle'] ),
		];

		// Determine redirect URL
		$redirect_url = '';
		if ( 'yes' === $settings['ekit_login_redirect_after_login'] && ! empty( $settings['ekit_login_redirect_url']['url'] ) ) {
			$redirect_url = $settings['ekit_login_redirect_url']['url'];
		} else {
			$redirect_url = admin_url();
		}

		// Get remember me duration
   		$remember_duration = ! empty( $settings['ekit_login_remember_me_duration'] ) ? absint( $settings['ekit_login_remember_me_duration'] ) : 86400;

		?>
			<div class="ekit-login-form-wrapper">
				<?php $this->lost_password_form( $settings, $determine ); ?>
				<!-- Links at form end -->
				<?php $this->lostpassword_url( $settings, 'ekit-position-form-start', 'form_start' ); ?>
				<?php $this->register_form_links( $settings, 'ekit-position-form-start', 'form_start' ); ?>
				<form
					class="ekit-login-form"
					method="post"
					name="loginform"
					id="loginform"
					data-remember-duration="<?php echo esc_attr( $remember_duration ); ?>"
					aria-label="<?php echo esc_attr__( 'Login form', 'elementskit' ); ?>"
				>
					<?php 
						$show_social_top = (
							'yes' === $settings['ekit_login_wp_social_login'] &&
							'top' === $settings['ekit_login_social_login_position'] &&
							class_exists( '\WP_Social' ) // WP Social plugin active
						);


						if ( $show_social_top ) {
							/**
							 * Fires before the login form is rendered.
							 *
							 * @since 3.0.0
							 *
							 * @param array  $settings Widget settings.
							 * @param string $widget_id Widget ID.
							 */
							do_action( 'elementskit/login_form/render', $settings, $this->get_id() );
						}

						if ( $show_social_top && 'yes' === $settings['ekit_login_social_login_separator'] ) {
							$this->render_separator( $settings );
						}
						
					?>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>">
					<input type="hidden" name="ekit_remember_duration" value="<?php echo esc_attr( $remember_duration ); ?>">

					<!-- Error Message Display -->
					<?php $this->messages_html( true, false ); ?>

					<div class="ekit-login-form-fields">

						<!-- Username/Email Field -->
						<?php $this->username_field( $determine, $settings ); ?>

						<!-- Password Field -->
						<?php $this->password_field( $determine, $settings ); ?>

						<!-- Submit Button -->
						<?php $this->submit_button( $settings ); ?>
					</div>

					<?php
						$show_social_bottom = (
							'yes' === $settings['ekit_login_wp_social_login'] &&
							'bottom' === $settings['ekit_login_social_login_position'] &&
							class_exists( '\WP_Social' ) // WP Social plugin active
						);

						if ( $show_social_bottom && 'yes' === $settings['ekit_login_social_login_separator'] ) {
							$this->render_separator( $settings );
						}

						if ( $show_social_bottom ) {
							/**
							 * Fires before the login form is rendered.
							 *
							 * @since 3.0.0
							 *
							 * @param array  $settings Widget settings.
							 * @param string $widget_id Widget ID.
							 */
							do_action( 'elementskit/login_form/render', $settings, $this->get_id() );
						}
					?>
				</form>
				<!-- Links at form end -->
				<?php $this->lostpassword_url( $settings, 'ekit-position-form-end', 'form_end' ); ?>
				<?php $this->register_form_links( $settings, 'ekit-position-form-end', 'form_end' ); ?>
			</div>
		<?php
	}
	//Username/Email Field
	protected function username_field( $determine, $settings ) {
		$this->add_input_group_classes( 'username_group', $determine );

		?>
			<div class="ekit-login-form-field">
				<?php if ( 'yes' === $settings['ekit_login_show_labels'] || 'custom' === $settings['ekit_login_show_labels'] ) : ?>
					<label for="user_login" class="ekit-login-form-label">
						<?php echo esc_html( 'yes' === $settings['ekit_login_show_labels'] ? esc_html__( 'Username or Email', 'elementskit' ) : $settings['ekit_login_username_label'] ); ?>
					</label>
				<?php endif; ?>
				<div <?php echo $this->get_render_attribute_string( 'username_group' ); ?>>
					<input
						type="text"
						name="log"
						id="user_login"
						autocapitalize="off" autocomplete="username"
						class="ekit-login-form-input elementor-field-textual elementor-size-<?php echo esc_attr( $settings['ekit_login_field_size'] ); ?>"
						<?php if ( $settings['ekit_login_field_show_placeholder'] === 'yes' ) : ?>
							placeholder="<?php echo esc_attr( 'custom' === $settings['ekit_login_show_labels'] ? $settings['ekit_login_username_placeholder'] : esc_html__( 'Enter your username or email', 'elementskit' ) ); ?>"
						<?php endif; ?>
						required
					/>

					<?php if ( $determine['show_icons'] && ! empty( $settings['ekit_login_username_icon']['value'] ) ) : ?>
						<span class="ekit-login-form-icon <?php echo ( 'end' === $determine['icon_pos'] ) ? 'ekit-icon-end' : 'ekit-icon-start'; ?>" aria-hidden="true">
							<?php \Elementor\Icons_Manager::render_icon( $settings['ekit_login_username_icon'], [ 'aria-hidden' => 'true', 'fill' => 'currentColor' ] ); ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
		<?php
	}

	//Password Field
	protected function password_field( $determine, $settings) {
		$this->add_input_group_classes( 'password_group', $determine );
		if ( $determine['show_toggle'] ) {
			$this->add_render_attribute( 'password_group', 'class', 'has-toggle' );
		}

		?>
			<div class="ekit-login-form-field">
				<div class="ekit-login-form-label-wrapper">
					<?php if ( 'yes' === $settings['ekit_login_show_labels'] || 'custom' === $settings['ekit_login_show_labels'] ) : ?>
						<label for="user_pass" class="ekit-login-form-label">
							<?php echo 'yes' === $settings['ekit_login_show_labels'] ? esc_html__( 'Password', 'elementskit' ) : $settings['ekit_login_password_label'] ; ?>
						</label>
					<?php endif; 
						$this->lostpassword_url( $settings, 'ekit-position-top', 'top' ); ?>
				</div>
				<div <?php echo $this->get_render_attribute_string( 'password_group' ); ?>>
					<input
						type="password"
						name="pwd"
						id="user_pass"
						autocomplete="current-password" spellcheck="false"
						class="ekit-login-form-input elementor-field-textual elementor-size-<?php echo esc_attr( $settings['ekit_login_field_size'] ); ?>"
						<?php if ( 'yes' === $settings['ekit_login_field_show_placeholder'] ) : ?>
							placeholder="<?php echo esc_attr( 'custom' === $settings['ekit_login_show_labels'] ? $settings['ekit_login_password_placeholder'] : esc_html__( 'Enter your password', 'elementskit' ) ); ?>"
						<?php endif; ?>
						required
				/>

					<?php if ( $determine['show_icons'] && ! empty( $settings['ekit_login_password_icon']['value'] ) ) : ?>
						<span class="ekit-login-form-icon <?php echo ( 'end' === $determine['icon_pos'] ) ? 'ekit-icon-end' : 'ekit-icon-start'; ?>" aria-hidden="true">
							<?php \Elementor\Icons_Manager::render_icon( $settings['ekit_login_password_icon'], [ 'aria-hidden' => 'true' , 'fill' => 'currentColor' ] ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $determine['show_toggle'] ) : ?>
						<button type="button" class="ekit-login-form-password-toggle" data-target="user_pass" aria-label="<?php echo esc_attr__( 'Show password', 'elementskit' ); ?>">
							<svg class="ekit-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 -960 960 960" aria-hidden="true">
								<path d="m644-428-58-58q9-47-27-88t-93-32l-58-58q17-8 34.5-12t37.5-4q75 0 127.5 52.5T660-500q0 20-4 37.5T644-428Zm128 126-58-56q38-29 67.5-63.5T832-500q-50-101-143.5-160.5T480-720q-29 0-57 4t-55 12l-62-62q41-17 84-25.5t90-8.5q151 0 269 83.5T920-500q-23 59-60.5 109.5T772-302Zm20 246L624-222q-35 11-70.5 16.5T480-200q-151 0-269-83.5T40-500q21-53 53-98.5t73-81.5L56-792l56-56 736 736-56 56ZM222-624q-29 26-53 57t-41 67q50 101 143.5 160.5T480-280q20 0 39-2.5t39-5.5l-36-38q-11 3-21 4.5t-21 1.5q-75 0-127.5-52.5T300-500q0-11 1.5-21t4.5-21l-84-82Zm319 93Zm-151 75Z"/>
							</svg>
							<svg class="ekit-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 -960 960 960" aria-hidden="true">
								<path d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z"/>
							</svg>
						</button>
					<?php endif; ?>

				</div>
			</div>
			<!-- Remember Me -->
			<?php $this->remember_me_field( $settings ); ?>
		<?php
	}

	//Remember Me
	protected function remember_me_field( $settings ) {
		if ( 'yes' === $settings['ekit_login_remember_me'] ) : ?>
			<div class="ekit-login-form-remember">
				<label id="rememberme">
					<input
						type="checkbox"
						name="rememberme"
						id="rememberme"
						value="forever"
					>
					<span class="ekit-login-remember-text"><?php echo esc_html( $settings['ekit_login_remember_me_text'] ); ?></span>
				</label>
				<!-- Lost Password Link at bottom -->
				<?php $this->lostpassword_url( $settings, 'ekit-position-bottom', 'bottom' ); ?>
			</div>
		<?php endif;
	}

	//Submit Button
	protected function submit_button( $settings ) {
		?>
			<div class="ekit-login-form-button-wrapper">
				<button
					type="submit"
					class="ekit-login-form-button elementor-button elementor-size-<?php echo esc_attr( $settings['ekit_login_button_size'] ); ?>"
					name="wp-submit"
					id="wp-submit"
				>
					<span class="ekit-login-form-button-text"> <?php echo esc_html( $settings['ekit_login_button_text'] ); ?> </span>
					<?php if ( ! empty( $settings['ekit_login_button_icon']['value'] ) ) : ?>
						<?php \Elementor\Icons_Manager::render_icon( $settings['ekit_login_button_icon'], ['class' => 'ekit-login-form-button-icon', 'aria-hidden' => 'true', 'fill' => 'currentColor'] ); ?>
					<?php endif; ?>
				</button>
			</div>
		<?php
	}

	protected function lost_password_form($settings, $determine) {

		$this->add_input_group_classes( 'lost_group', $determine );
		?>
		<div class="ekit-login-lost-password-form">
			<form id="ekit-lost-password-form" method="post" aria-label="<?php echo esc_attr__( 'Lost password form', 'elementskit' ); ?>">
				<!-- Error/Success Message Display -->
				<?php $this->messages_html( false, true ); ?>

				<div class="ekit-login-form-group">
					<p class="ekit-login-form-info">
						<?php echo $settings['ekit_lost_password_instruction'] ? esc_html( $settings['ekit_lost_password_instruction'] ) : esc_html__('Please enter your username or email address. You will receive a link to create a new password via email.', 'elementskit'); ?>
					</p>
					<div class="ekit-login-form-field">
						<?php if ( 'yes' === $settings['ekit_login_show_labels'] || 'custom' === $settings['ekit_login_show_labels'] ) : ?>
							<label for="user_login_recover" class="ekit-login-form-label">
								<?php echo 'yes' === $settings['ekit_login_show_labels'] ? esc_html__( 'Username or Email', 'elementskit' ) : $settings['ekit_lost_password_label']; ?>
							</label>
						<?php endif; ?>
						<div <?php echo $this->get_render_attribute_string( 'lost_group' ); ?>>
							<input type="text"
								name="user_login"
								id="user_login_recover"
								class="ekit-login-form-input elementor-field-textual elementor-size-<?php echo esc_attr( $settings['ekit_login_field_size'] ); ?>"
								<?php if ( 'yes' === $settings['ekit_login_field_show_placeholder'] ) : ?>
									placeholder="<?php echo esc_attr( 'custom' === $settings['ekit_login_show_labels'] ? $settings['ekit_lost_password_placeholder'] : esc_html__( 'Enter your username or email', 'elementskit' ) ); ?>"
								<?php endif; ?>
								required>

							<?php if ( $determine['show_icons'] && ! empty( $settings['ekit_lost_password_icon']['value'] ) ) : ?>
								<span class="ekit-login-form-icon <?php echo ( 'end' === $determine['icon_pos'] ) ? 'ekit-icon-end' : 'ekit-icon-start'; ?>" aria-hidden="true">
									<?php \Elementor\Icons_Manager::render_icon( $settings['ekit_lost_password_icon'], [ 'aria-hidden' => 'true', 'fill' => 'currentColor' ] ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="ekit-login-form-button-wrapper ekit-reset">
					<button type="submit" class="ekit-lost-password-button ekit-login-form-button  elementor-button elementor-size-<?php echo esc_attr( $settings['ekit_login_reset_button_size'] ); ?>">
						<?php echo !empty($settings['ekit_login_reset_button_text']) ? esc_html($settings['ekit_login_reset_button_text']) : esc_html__('Reset Password', 'elementskit'); ?>
					</button>
					<button type="button" class="ekit-lost-password-form-back ekit-login-form-button elementor-button elementor-size-<?php echo esc_attr( $settings['ekit_login_reset_button_size'] ); ?>">
						<?php echo !empty($settings['ekit_login_back_button_text']) ? esc_html($settings['ekit_login_back_button_text']) : esc_html__('Back to Login', 'elementskit'); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Renders lost password link section for the login form.
	 *
	 * This method displays a lost password link if the widget settings allow it.
	 * The link can be positioned at different locations based on the provided condition.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $settings       Widget settings array containing lost password link configuration.
	 * @param string $position_class CSS class for positioning the lost password link container.
	 * @param string $condition      Current position context ('form_start', 'form_end', etc.) to match against settings.
	 *
	 * @return void Outputs HTML markup directly.
	 */
	protected function lostpassword_url( $settings, $position_class, $condition ) {

		if ( 'yes' !== $settings['ekit_login_show_lost_password'] ) {
			return;
		}

		if ( $condition !== $settings['ekit_login_show_lost_password_position'] ) {
			return;
		}
		?>

		<div class="ekit-login-form-links lost-password-link <?php echo esc_attr( $position_class ); ?>">

			<?php if ( in_array( $condition, [ 'form_start', 'form_end' ], true ) ) : ?>
				<span class="ekit-login-lost-password-desc">
					<?php echo esc_html( $settings['ekit_login_show_lost_password_description'] ); ?>
				</span>
			<?php endif; ?>

			<a class="ekit-login-lost-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
				<?php echo esc_html( $settings['ekit_login_lost_password_text'] ); ?>
			</a>

		</div>

		<?php
	}

	
	/**
	 * Renders registration link section for the login form.
	 *
	 * This method displays a registration link if user registration is enabled in WordPress settings
	 * and the widget settings allow it. The link can be positioned at different locations based on
	 * the provided condition.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $settings       Widget settings array containing registration link configuration.
	 * @param string $position_class CSS class for positioning the registration link container.
	 * @param string $condition      Current position context ('form_start', 'form_end', etc.) to match against settings.
	 *
	 * @return void Outputs HTML markup directly. Returns early if conditions are not met.
	 */
	protected function register_form_links( $settings, $position_class, $condition ) {

		if ( 'yes' !== $settings['ekit_login_show_register'] ) {
			return;
		}

		$is_disabled = Handler::is_disabled_register();

		if ( ! empty( $is_disabled['value'] ) ) {
			return;
		}

		if ( $condition !== $settings['ekit_login_show_register_position'] ) {
			return;
		}

		// 2. Prepare register URL (fallback to WP default)
		if ( empty( $settings['ekit_login_register_url']['url'] ) ) {
			$settings['ekit_login_register_url']['url'] = wp_registration_url();
		}

		// 3. Add Elementor link attributes
		$this->add_link_attributes(
			'ekit_register_link',
			$settings['ekit_login_register_url']
		);
		?>

		<div class="ekit-login-form-links register-link <?php echo esc_attr( $position_class ); ?>">

			<?php if ( in_array( $condition, [ 'form_start', 'form_end' ], true ) ) : ?>
				<span class="ekit-login-register-desc">
					<?php echo esc_html( $settings['ekit_login_show_register_description'] ); ?>
				</span>
			<?php endif; ?>

			<a class="ekit-login-register" <?php echo $this->get_render_attribute_string( 'ekit_register_link' ); ?>>
				<?php echo esc_html( $settings['ekit_login_register_text'] ); ?>
			</a>

		</div>
		<?php
	}

	/**
	 * Renders a separator element for the register form, typically used to visually separate
	 * sections such as social login options from other form fields.
	 *
	 * @param array $settings The settings array containing configuration for the register form.
	 *      Expects 'ekit_register_social_login_separator_text' key for the separator label.
	 */
	protected function render_separator( $settings ) {
		?>
		<div class="ekit-login-separator">
			<span><?php echo esc_html( $settings['ekit_login_social_login_separator_text'] ); ?></span>
		</div>
		<?php
	}

	/**
	 * Outputs HTML placeholders for login-related messages.
	 *
	 * @param bool $error       Whether to render the login error message container. Default true.
	 * @param bool $pass_error  Whether to render the lost password message container. Default true.
	 * @return void
	 */
	protected function messages_html( $error = true, $pass_error = true ) {
		// Lost password message
		if ( $pass_error ) : ?>
			<div class="ekit-lost-password-message hidden" role="alert"></div>
		<?php endif;

		// Login error message
		if ( $error ) : ?>
			<div class="ekit-login-error-message hidden" role="alert"></div>
		<?php endif;
	}

	/**
	 * Add CSS classes to an input group's render attributes based on provided options.
	 *
	 * This method appends a base class for the input group and conditionally adds
	 * icon-related classes when requested. It uses $this->add_render_attribute()
	 * to add classes to the specified render attribute group.
	 *
	 * @param string $group     Render attribute group name to modify.
	 * @param array  $determine Associative array of options:
	 *                 - 'show_icons'   (bool)   Whether to include icon-related classes.
	 *                 - 'icon_pos'     (string) Position of the icon ('end' for icon-end; defaults to start).
	 *                 - 'show_divider' (bool)   Whether to include the divider class when icons are shown.
	 *
	 * @return void
	 */
	protected function add_input_group_classes( string $group, array $determine ) {
		// Base class
		$this->add_render_attribute( $group, 'class', 'ekit-login-form-input-group' );

		// Icon-related classes
		if ( ! empty( $determine['show_icons'] ) ) {
			$this->add_render_attribute( $group, 'class', 'has-icon' );

			$position_class = ( 'end' === ( $determine['icon_pos'] ?? '' ) ) ? 'icon-end' : 'icon-start';
			$this->add_render_attribute( $group, 'class', $position_class );

			if ( ! empty( $determine['show_divider'] ) ) {
				$this->add_render_attribute( $group, 'class', 'has-divider' );
			}
		}
	}

}