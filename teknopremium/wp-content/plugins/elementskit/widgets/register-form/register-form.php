<?php
namespace Elementor;
use \Elementor\ElementsKit_Widget_Register_Form_Handler as Handler;

defined('ABSPATH') || exit;

class ElementsKit_Widget_Register_Form extends Widget_Base {
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

	public function get_script_depends() {
		return ['ekit-pro-register-form'];
	}
    
    public function get_help_url() {
        return 'https://wpmet.com/docs/register-form/';
    }

    protected function is_dynamic_content(): bool {
        return true;
    }

    /**
	 * Get array of fields type.
	 *
	 * @since 1.5.0
	 * @access protected
	 * @return array fields.
	 */
	protected function get_field_type() {
		$fields = array(
			'user_login'        => esc_html__( 'Username', 'elementskit' ),
			'user_pass'         => esc_html__( 'Password', 'elementskit' ),
			'user_confirm_password' => esc_html__( 'Confirm Password', 'elementskit' ),
			'user_email'        => esc_html__( 'Email', 'elementskit' ),
			'user_phone'             => esc_html__( 'Phone', 'elementskit' ),
			'user_first_name'        => esc_html__( 'First Name', 'elementskit' ),
			'user_last_name'         => esc_html__( 'Last Name', 'elementskit' ),
			'user_url'          => esc_html__( 'Website', 'elementskit' ),
		);

		$fields = apply_filters( 'elementskit/register_form_fields', $fields );

		return $fields;
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
        $this->start_controls_section(
            'ekit_section_register_form_fields',
            [
                'label' => esc_html__( 'Form Fields', 'elementskit' ),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
         $this->add_control(
            'ekit_register_show_labels',
            [
                'label'   => esc_html__( 'Field Label', 'elementskit' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'yes',
                'options' => [
                    'yes' => esc_html__( 'Default', 'elementskit' ),
                    'no'  => esc_html__( 'Hide', 'elementskit' ),
                ],
                'default' => 'yes',
            ]
		);

        $repeater = new Repeater();
        $repeater->add_control(
            'ekit_register_form_field_label',
            [
                'label'   => esc_html__( 'Field Label', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Username', 'elementskit' ),
                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );
        $repeater->add_control(
            'ekit_register_form_field_placeholder',
            [
                'label'   => esc_html__( 'Field Placeholder', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Enter your username', 'elementskit' ),

                'label_block' => true,
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );
        $repeater->add_control(
            'ekit_register_form_field_type',
            [
                'label'   => esc_html__( 'Field Type', 'elementskit' ),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->get_field_type(),
                'default' => 'user_login',
            ]
        );
        $repeater->add_control(
            'ekit_register_form_field_icon',
            [
                'label' => esc_html__( 'Field Icon', 'elementskit' ),
               	'type'  => Controls_Manager::ICONS,
            ]
        );
        $this->add_control(
            'ekit_register_form_fields',
            [
                'label' => esc_html__( 'Fields', 'elementskit' ),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'default' => [
                    [
                        'ekit_register_form_field_label' => esc_html__( 'Username', 'elementskit' ),
						'ekit_register_form_field_placeholder' => esc_html__( 'Enter your username', 'elementskit' ),
                        'ekit_register_form_field_type' => 'user_login',
						'ekit_register_form_field_icon' => [
							'value' => 'fas fa-user',
							'library' => 'fa-solid',
						]
                    ],
                    [
                        'ekit_register_form_field_label' => esc_html__( 'Email', 'elementskit' ),
						'ekit_register_form_field_placeholder' => esc_html__( 'Enter your email', 'elementskit' ),
                        'ekit_register_form_field_type' => 'user_email',
						'ekit_register_form_field_icon' => [
							'value' => 'fas fa-envelope',
							'library' => 'fa-solid',
						]
                    ],
                    [
                        'ekit_register_form_field_label' => esc_html__( 'Password', 'elementskit' ),
						'ekit_register_form_field_placeholder' => esc_html__( 'Enter your password', 'elementskit' ),
                        'ekit_register_form_field_type' => 'user_pass',
						'ekit_register_form_field_icon' => [
							'value' => 'fas fa-lock',
							'library' => 'fa-solid',
						]
                    ],
                ],
                'title_field' => '{{{ ekit_register_form_field_label }}}',
            ]
        );

        $this->add_control(
            'ekit_register_field_size',
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
            'ekit_register_field_show_placeholder',
            [
                'label' => esc_html__( 'Field Placeholder', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'elementskit' ),
                'label_off' => esc_html__( 'Hide', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        $this->add_control(
            'ekit_register_required_mark',
            [
                'label' => esc_html__( 'Required Mark', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'elementskit' ),
                'label_off' => esc_html__( 'Hide', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'yes',
            ] 
        );

        $this->end_controls_section();
    
        $this->start_controls_section(
			'ekit_register_section_fields_icons',
			[
				'label' => esc_html__( 'Fields Icons', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);
        $this->add_control(
            'ekit_register_fields_icons',
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
            'ekit_register_fields_divider',
            [
                'label' => esc_html__( 'Icons Divider', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Show', 'elementskit' ),
                'label_off' => esc_html__( 'Hide', 'elementskit' ),
                'return_value' => 'yes',
                'default' => 'no',
				'condition' => [
					'ekit_register_fields_icons' => 'yes',
				],
            ]
        );

		// Icons position and selection
		$this->add_control(
			'ekit_register_icon_position',
			[
				'label' => esc_html__( 'Icons Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'start' => esc_html__( 'Start', 'elementskit' ),
					'end'   => esc_html__( 'End', 'elementskit' ),
				],
				'default' => 'start',
				'condition' => [
					'ekit_register_fields_icons' => 'yes',
				],
			]
		);

		$this->end_controls_section();

        
		// Button Section
		$this->start_controls_section(
			'ekit_section_register_form_button',
			[
				'label' => esc_html__( 'Button', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
            'ekit_register_button_text',
            [
                'label'   => esc_html__( 'Button Text', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Create Account', 'elementskit' ),
            ]
		);

		$this->add_responsive_control(
			'ekit_register_button_align',
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
					'{{WRAPPER}} .ekit-register-form-button-wrapper' => '{{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_button_size',
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
			'ekit_register_button_icon',
			[
				'label' => esc_html__( 'Button Icon', 'elementskit' ),
				'type'  => Controls_Manager::ICONS,
			]
		);

		$this->add_control(
			'ekit_register_button_icon_position',
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
					'{{WRAPPER}} .ekit-register-form-button-wrapper .ekit-register-form-button' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_register_button_icon_spacing',
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
					'{{WRAPPER}} .ekit-register-form-button-wrapper .ekit-register-form-button' => 'gap: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'ekit_register_button_icon[value]!' => '',
				],
			]
		);

		$this->end_controls_section();
		// Register Link
		$this->start_controls_section(
			'ekit_section_register_form_login_link',
			[
				'label' => esc_html__( 'Login Link', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);
		$this->add_control(
			'ekit_register_show_login',
			[
				'label' => esc_html__( 'Login Link', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementskit' ),
				'label_off' => esc_html__( 'Hide', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'separator' => 'before',
			]
		);

		$this->add_control(
            'ekit_register_login_text',
            [
                'label'   => esc_html__( 'Login Link Text', 'elementskit' ),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__( 'Login', 'elementskit' ),
				'condition' => [
					'ekit_register_show_login' => 'yes',
				],
            ]
		);

		$this->add_control(
			'ekit_register_show_login_position',
			[
				'label' => esc_html__( 'Link Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'form_start' => esc_html__( 'Form Start', 'elementskit' ),
					'form_end'   => esc_html__( 'Form End', 'elementskit' ),
				],
				'default' => 'form_end',
				'condition' => [
					'ekit_register_show_login' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_register_show_login_align',
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
					'{{WRAPPER}} .ekit-register-form-links' => '--ekit-register-link-alignment: {{VALUE}};',

				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_register_show_login',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_register_show_login_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_register_show_login_position',
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
			'ekit_register_login_text_before_after',
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
					'{{WRAPPER}} .ekit-register-form-links' => '{{VALUE}};',
				],
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_register_show_login',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_register_show_login_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_register_show_login_position',
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
			'ekit_register_show_login_description',
			[
				'label'   => esc_html__( 'Description', 'elementskit' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Already have an account?', 'elementskit' ),
				'conditions' => [
					'relation' => 'and',
					'terms' => [
						[
							'name'     => 'ekit_register_show_login',
							'operator' => '===',
							'value'    => 'yes',
						],
						[
							'relation' => 'or',
							'terms' => [
								[
									'name'     => 'ekit_register_show_login_position',
									'operator' => '===',
									'value'    => 'form_start',
								],
								[
									'name'     => 'ekit_register_show_login_position',
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
			'ekit_register_login_url',
			[
				'label' => esc_html__( 'Login URL', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'elementskit' ),
				'condition' => [
					'ekit_register_show_login' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// Settings Section
		$this->start_controls_section(
			'ekit_section_register_form_settings',
			[
				'label' => esc_html__( 'Settings', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_register_password_toggle',
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
			'ekit_register_redirect_after_register',
			[
				'label' => esc_html__( 'Redirect After Register', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$this->add_control(
			'ekit_register_redirect_url',
			[
				'label' => esc_html__( 'Redirect URL', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'elementskit' ),
				'condition' => [
					'ekit_register_redirect_after_register' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_hide_for_logged_in',
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
			'ekit_register_logged_in_message',
			[
				'label' => esc_html__( 'Logged In Message', 'elementskit' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'You are already logged in.', 'elementskit' ),
				'condition' => [
					'ekit_register_hide_for_logged_in' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_conditions_enable',
			[
				'label' => esc_html__( 'Enable Terms & Conditions', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_register_terms_conditions_text',
			[
				'label' => esc_html__( 'Checkbox Label', 'elementskit' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'I agree with', 'elementskit' ),
				'render_type' => 'template',
				'condition' => [
					'ekit_register_terms_conditions_enable' => 'yes',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_conditions_link_text',
			[
				'label' => esc_html__( 'Link Text', 'elementskit' ),
				'type' => Controls_Manager::TEXT,
				'default' => esc_html__( 'Terms & Condition Policy', 'elementskit' ),
				'condition' => [
					'ekit_register_terms_conditions_enable' => 'yes',
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_conditions_url',
			[
				'label' => esc_html__( 'Terms & Conditions URL', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-site.com/terms', 'elementskit' ),
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => false,
				],
				'condition' => [
					'ekit_register_terms_conditions_enable' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_wp_social_login',
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
			'ekit_register_wp_social_notice',
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
			'ekit_register_social_login_position',
			[
				'label' => esc_html__( 'Position', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'top' => esc_html__( 'Top', 'elementskit' ),
					'bottom'   => esc_html__( 'Bottom', 'elementskit' ),
				],
				'default' => 'top',
				'condition' => [
					'ekit_register_wp_social_login' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_social_login_style',
			[
				'label' => esc_html__( 'Style', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => $this->get_style_type(),
				'default' => 'style-1',
				'condition' => [
					'ekit_register_wp_social_login' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_social_login_separator',
			[
				'label'   => esc_html__( 'Show Separator', 'elementskit' ),
				'type'    => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'elementskit' ),
				'label_off' => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
				'condition' => [
					'ekit_register_wp_social_login' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_social_login_separator_text',
			[
				'label'   => esc_html__( 'Separator Text', 'elementskit' ),
				'type'    =>Controls_Manager::TEXT,
				'default' => esc_html__( 'Or Signup with', 'elementskit' ),
				'condition' => [
					'ekit_register_wp_social_login' => 'yes',
					'ekit_register_social_login_separator' => 'yes',
				],
			]
		);

		$this->end_controls_section();

        // Style: Labels
		$this->start_controls_section(
			'ekit_section_register_labels_style',
			[
				'label' => esc_html__( 'Labels', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_register_show_labels!' => 'no',
				],
			]
		);

		$this->add_control(
			'ekit_register_label_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_register_label_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-field label',
			]
		);

		$this->add_responsive_control(
			'ekit_register_label_spacing',
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
					'{{WRAPPER}} .ekit-register-form-field label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Input Fields
		$this->start_controls_section(
			'ekit_section_register_fields_style',
			[
				'label' => esc_html__( 'Fields', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'ekit_register_field_tabs' );

		$this->start_controls_tab(
			'ekit_register_field_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_register_field_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field input' => 'color: {{VALUE}};'
				],
			]
		);

		$this->add_control(
			'ekit_register_field_placeholder_color',
			[
				'label' => esc_html__( 'Placeholder Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field input::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_register_field_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-register-form-field input',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_register_field_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-field input',
			]
		);

		$this->add_responsive_control(
			'ekit_register_field_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_register_field_box_shadow',
				'selector' => '{{WRAPPER}} .ekit-register-form-field input',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_register_field_focus',
			[
				'label' => esc_html__( 'Focus', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_register_field_focus_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field input:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_register_field_focus_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-register-form-field input:focus',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_register_field_focus_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-field input:focus',
			]
		);

		$this->add_responsive_control(
			'ekit_register_field_focus_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field input:focus' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_register_field_focus_box_shadow',
				'selector' => '{{WRAPPER}} .ekit-register-form-field input:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_register_field_padding',
			[
				'label' => esc_html__( 'Padding', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'ekit_register_field_spacing',
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
					'{{WRAPPER}} .ekit-register-form-field' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_register_field_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-field input',
			]
		);

		$this->end_controls_section();

		// Style: Fields Icons
		$this->start_controls_section(
			'ekit_section_register_fields_icons_style',
			[
				'label' => esc_html__( 'Fields Icons', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_register_fields_icons' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_register_fields_icons_color',
			[
				'label' => esc_html__( 'Icons Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-field .ekit-register-form-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_fields_icons_size',
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
					'{{WRAPPER}} .ekit-register-form-field .ekit-register-form-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_fields_icons_divider_color',
			[
				'label' => esc_html__( 'Divider Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
					'ekit_register_fields_divider' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-icon.ekit-icon-end::before' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-register-form-icon.ekit-icon-start::after' => 'border-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_register_fields_divider_width',
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
					'ekit_register_fields_divider' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-icon.ekit-icon-end::before' => 'border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-register-form-icon.ekit-icon-start::after' => 'border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Submit Button
		$this->start_controls_section(
			'ekit_section_register_button_style',
			[
				'label' => esc_html__( 'Button', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'ekit_register_button_tabs' );

		$this->start_controls_tab(
			'ekit_register_button_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_register_button_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_register_button_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-register-form-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_register_button_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-button',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_register_button_box_shadow',
				'label' => esc_html__( 'Box Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-button',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_register_button_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_register_button_hover_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_register_button_hover_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .ekit-register-form-button:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_register_button_hover_border',
				'label' => esc_html__( 'Border', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-button:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_register_button_hover_box_shadow',
				'label' => esc_html__( 'Box Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-button:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_register_button_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_register_button_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-button',
			]
		);

		$this->add_responsive_control(
			'ekit_register_button_padding',
			[
				'label' => esc_html__( 'Padding', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_button_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_register_button_spacing',
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
					'{{WRAPPER}} .ekit-register-form-button-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Terms & Conditions
		$this->start_controls_section(
			'ekit_section_register_terms_style',
			[
				'label' => esc_html__( 'Terms & Conditions', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_register_terms_conditions_enable' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_link_color',
			[
				'label' => esc_html__( 'Link Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_link_hover_color',
			[
				'label' => esc_html__( 'Link Hover Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-link:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_register_terms_checkbox_color',
			[
				'label' => esc_html__( 'Checkbox Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-checkbox' => 'accent-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_register_terms_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-terms-text',
			]
		);

		$this->add_responsive_control(
			'ekit_register_terms_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
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
				'default' => [
					'size' => 15,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-field' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_register_terms_checkbox_size',
			[
				'label' => esc_html__( 'Checkbox Size', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 12,
						'max' => 30,
					],
				],
				'default' => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-checkbox' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_register_terms_checkbox_spacing',
			[
				'label' => esc_html__( 'Checkbox Spacing', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 30,
					],
				],
				'default' => [
					'size' => 8,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-terms-checkbox' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_section_register_links_style',
			[
				'label' => esc_html__( 'Login Links', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'ekit_register_links_tabs' );

		$this->start_controls_tab(
			'ekit_register_links_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_register_links_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-links a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_register_links_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_register_links_hover_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-links a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'ekit_register_links_decoration_color',
			[
				'label' => esc_html__( 'Decoration Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-links a' => 'text-decoration-color: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);
		
		$this->add_control(
			'ekit_register_links_desc_color',
			[
				'label' => esc_html__( 'Description Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-links' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_register_links_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit-register-form-links, {{WRAPPER}} .ekit-register-form-links a',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_register_links_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-register-form-links' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_register_section_social_style',
			[
				'label' => esc_html__( 'Social Login', 'elementskit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_register_wp_social_login' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_register_social_login_margin',
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
					'{{WRAPPER}} .ekit-register-form #xs-social-login-container' =>
						'margin-top: {{TOP}}{{UNIT}}; margin-bottom: {{BOTTOM}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'ekit_register_social_login_padding',
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
					'{{WRAPPER}} .ekit-register-form #xs-social-login-container' =>
						'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		
		$this->start_controls_section(
			'ekit_register_section_separator_style',
			[
				'label' => esc_html__( 'Separator', 'elementskit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_register_wp_social_login' => 'yes',
					'ekit_register_social_login_separator' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_register_separator_text_color',
			[
				'label'     => __( 'Text Color', 'elementskit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-separator span' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_register_separator_typography',
				'selector' => '{{WRAPPER}} .ekit-register-separator span',
			]
		);

		$this->add_control(
			'ekit_register_separator_line_color',
			[
				'label'     => __( 'Line Color', 'elementskit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-register-separator::before,
					{{WRAPPER}} .ekit-register-separator::after' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'ekit_register_separator_line_height',
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
					'{{WRAPPER}} .ekit-register-separator::before,
					{{WRAPPER}} .ekit-register-separator::after' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_register_separator_margin',
			[
				'label'      => esc_html__( 'Margin', 'elementskit' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .ekit-register-separator' =>
						'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

    }

    protected function render() {
        ?>
            <div class="ekit-wid-con">
                <?php $this->render_row(); ?>
            </div>
        <?php
    }

   	protected function render_row() {
		$settings = $this->get_settings_for_display();

		// Update database setting based on control value
		if ( ! empty( $settings['ekit_register_wp_social_login'] ) ) {
			$style_data = get_option( 'xs_style_setting_data', [] );
			if ( ! is_array( $style_data ) ) {
				$style_data = [];
			}
			$style_data['login_button_style'] = $settings['ekit_register_social_login_style'];
			update_option( 'xs_style_setting_data', $style_data );
		}
		
		$is_disabled = Handler::is_disabled_register();
		if ( $is_disabled['value'] ) {
			echo '<div class="ekit-register-form-logged-in-message">'
				. sprintf(
					esc_html__( 'User registration is disabled. Please enable it in the settings: %s', 'elementskit' ),
					sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( admin_url( 'options-general.php' ) ),
						esc_html__( 'Go to Settings', 'elementskit' )
					)
				)
				. '</div>';

			return; // Stop rendering the form
		}

		// Check if user is logged in and hide form if needed
		if ( is_user_logged_in() && 'yes' === $settings['ekit_register_hide_for_logged_in'] ) {
			echo '<div class="ekit-register-form-logged-in-message">' 
				. esc_html( $settings['ekit_register_logged_in_message'] ) 
				. '</div>';
			return; // Stop rendering the form
		}

		// Determine field display settings
		$determine = [
			'show_icons'   => ( isset( $settings['ekit_register_fields_icons'] ) && 'yes' === $settings['ekit_register_fields_icons'] ),
			'show_divider' => ( isset( $settings['ekit_register_fields_divider'] ) && 'yes' === $settings['ekit_register_fields_divider'] ),
			'icon_pos'     => ! empty( $settings['ekit_register_icon_position'] ) ? $settings['ekit_register_icon_position'] : 'start',
			'show_toggle'  => ! isset( $settings['ekit_register_password_toggle'] ) ? true : ( 'yes' === $settings['ekit_register_password_toggle'] ),
		];

		// Determine redirect URL
		$redirect_url = '';
		if ( 'yes' === $settings['ekit_register_redirect_after_register'] && ! empty( $settings['ekit_register_redirect_url']['url'] ) ) {
			$redirect_url = $settings['ekit_register_redirect_url']['url'];
		} else {
			$redirect_url = wp_login_url();
		}

		?>
			<div class="ekit-register-form-wrapper">
				<?php $this->login_form_links( $settings, 'ekit-position-form-start', 'form_start' ); ?>
				<form 
					class="ekit-register-form" 
					method="post"
					name="registerform" 
					id="registerform"
                    novalidate="novalidate"
					aria-label="<?php echo esc_attr__( 'Register form', 'elementskit' ); ?>"
				>
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>">
					<!-- Error Message Display -->
					<?php $this->messages_html( true, false ); 
						$show_social_top = (
							'yes' === $settings['ekit_register_wp_social_login'] &&
							'top' === $settings['ekit_register_social_login_position'] &&
							class_exists( '\WP_Social' ) // WP Social plugin active
						);
						if ( $show_social_top ) {
							/**
							 * Fires before the register form is rendered.
							 *
							 * @since 3.0.0
							 *
							 * @param array  $settings Widget settings.
							 * @param string $widget_id Widget ID.
							 */
							do_action( 'elementskit/register_form/render', $settings, $this->get_id() );
						}

						if ( $show_social_top && 'yes' === $settings['ekit_register_social_login_separator'] ) {
							$this->render_separator( $settings );
						}
					
					?>

					<div class="ekit-register-form-fields">

						<?php 
							if ( ! empty( $settings['ekit_register_form_fields'] ) ) : 

								// Collect unique fields by type
								$unique_fields = [];
								foreach ( $settings['ekit_register_form_fields'] as $field ) {
									$type = $field['ekit_register_form_field_type'] ?? '';
									if ( $type && ! isset( $unique_fields[ $type ] ) ) {
										$unique_fields[ $type ] = $field;
									}
								}

								// Render each type only once
								foreach ( $unique_fields as $type => $field ) {
									switch ( $type ) {
										case 'user_login':
										case 'user_email':
										case 'user_first_name':
										case 'user_last_name':
										case 'user_phone':
										case 'user_url':
											$this->input_field( $determine, $settings, $field );
											break;

										case 'user_pass':
										case 'user_confirm_password':
											$this->password_field( $determine, $settings, $field );
											break;

										default:
											// Do nothing for unknown types
											break;
									}
								}

							endif;
						?>

						<!-- Terms & Conditions Checkbox -->
						<?php $this->render_terms_conditions( $settings ); ?>
						<!-- Create a Account -->
						 <?php $this->render_submit_button( $settings ); ?>

					</div>
					<?php
						$show_social_bottom = (
							'yes' === $settings['ekit_register_wp_social_login'] &&
							'bottom' === $settings['ekit_register_social_login_position'] &&
							class_exists( '\WP_Social' ) // WP Social plugin active
						);

						if ( $show_social_bottom && 'yes' === $settings['ekit_register_social_login_separator'] ) {
							$this->render_separator( $settings );
						}

						if ( $show_social_bottom ) {
							/**
							 * Fires before the register form is rendered.
							 *
							 * @since 3.0.0
							 *
							 * @param array  $settings Widget settings.
							 * @param string $widget_id Widget ID.
							 */
							do_action( 'elementskit/register_form/render', $settings, $this->get_id() );
						}
					?>
				</form>
				<?php $this->login_form_links( $settings, 'ekit-position-form-end', 'form_end' ); ?>
			</div>

		<?php
	}

	//Username/Email Field
	
	protected function input_field( $determine, $settings, $field ) {
		$this->add_input_group_classes( 'input_group', $determine );

		?>
		<div class="ekit-register-form-field elementor-repeater-item-<?php echo esc_attr( $field['_id'] ); ?>">
			<?php if ( 'yes' === $settings['ekit_register_show_labels'] ) : ?>
				<label for="<?php echo esc_attr( $field['ekit_register_form_field_type'] . '_' . $field['_id'] ); ?>" class="ekit-register-form-label">
					<?php echo esc_html( $field['ekit_register_form_field_label'] ); ?>
					<?php if ( 'yes' === $settings['ekit_register_required_mark'] ) : ?>
						<span class="ekit-register-form-required">*</span>
					<?php endif; ?>
				</label>
			<?php endif; ?>
			<div <?php echo $this->get_render_attribute_string( 'input_group' ); ?>>
				<input 
					type="<?php echo $this->get_input_type( $field['ekit_register_form_field_type'] ); ?>" 
					name="<?php echo esc_attr( $field['ekit_register_form_field_type']); ?>" 
					id="<?php echo esc_attr( $field['ekit_register_form_field_type'] . '_' . $field['_id'] ); ?>"
					class="ekit-register-form-input elementor-field-textual elementor-size-<?php echo esc_attr( $settings['ekit_register_field_size'] ); ?>"
					<?php if ( 'yes' === $settings['ekit_register_field_show_placeholder'] ) : ?>
						placeholder="<?php 
							echo esc_attr( ! empty( $field['ekit_register_form_field_placeholder'] ) 
								? $field['ekit_register_form_field_placeholder'] 
								: __( 'Enter your Placeholder', 'elementskit' ) 
							); ?>"
					<?php endif; ?>
					required
				/>
				<?php if ( $determine['show_icons'] && ! empty( $field['ekit_register_form_field_icon']['value'] ) ) : ?>
					<span class="ekit-register-form-icon <?php echo ( 'end' === $determine['icon_pos'] ) ? 'ekit-icon-end' : 'ekit-icon-start'; ?>" aria-hidden="true">
						<?php \Elementor\Icons_Manager::render_icon( $field['ekit_register_form_field_icon'], [ 'aria-hidden' => 'true', 'fill' => 'currentColor' ] ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	//Password Field
	protected function password_field( $determine, $settings, $field ) {
		$this->add_input_group_classes( 'password_group', $determine );
		if ( $determine['show_toggle'] ) {
			$this->add_render_attribute( 'password_group', 'class', 'has-toggle' );
		}
		?>
			<div class="ekit-register-form-field elementor-repeater-item-<?php echo esc_attr( $field['_id'] ); ?>">
				<?php if ( 'yes' === $settings['ekit_register_show_labels'] ) : ?>
					<label for="<?php echo esc_attr( $field['ekit_register_form_field_type'] .'_'. $field['_id'] ); ?>" class="ekit-register-form-label">
						<?php echo esc_html( $field['ekit_register_form_field_label'] ? $field['ekit_register_form_field_label'] : esc_html__( 'Password', 'elementskit' ) ); ?>
						<?php if ( 'yes' === $settings['ekit_register_required_mark'] ) : ?>
							<span class="ekit-register-form-required">*</span>
						<?php endif; ?>
					</label>
				<?php endif; ?>
				<div <?php echo $this->get_render_attribute_string( 'password_group' ); ?>>
					<input 
						type="<?php echo $this->get_input_type( $field['ekit_register_form_field_type'] ); ?>"
						name="<?php echo esc_attr( $field['ekit_register_form_field_type']); ?>" 
						id="<?php echo esc_attr( $field['ekit_register_form_field_type'] . '_' . $field['_id'] ); ?>"
						autocomplete="current-password" spellcheck="false"
						class="ekit-register-form-input elementor-field-textual elementor-size-<?php echo esc_attr( $settings['ekit_register_field_size'] ); ?>"
						<?php if ( 'yes' === $settings['ekit_register_field_show_placeholder'] ) : ?>
							placeholder="<?php 
								echo esc_attr( ! empty( $field['ekit_register_form_field_placeholder'] ) 
									? $field['ekit_register_form_field_placeholder'] 
									: __( 'Enter your password', 'elementskit' ) 
								); ?>"
						<?php endif; ?>
						required
					/>

					<?php if ( $determine['show_icons'] && ! empty( $field['ekit_register_form_field_icon']['value'] ) ) : ?>
						<span class="ekit-register-form-icon <?php echo ( 'end' === $determine['icon_pos'] ) ? 'ekit-icon-end' : 'ekit-icon-start'; ?>" aria-hidden="true">
							<?php Icons_Manager::render_icon( $field['ekit_register_form_field_icon'], [ 'aria-hidden' => 'true' , 'fill' => 'currentColor' ] ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $determine['show_toggle'] ) : ?>
						<button type="button" class="ekit-register-form-password-toggle" data-target="<?php echo esc_attr( $field['ekit_register_form_field_type'] . '_' . $field['_id'] ); ?>" aria-label="<?php echo esc_attr__( 'Show password', 'elementskit' ); ?>">
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
		<?php
	}
	// Terms and Conditions
	protected function render_terms_conditions( $settings ) {
		if ( ! empty( $settings['ekit_register_terms_conditions_url']['url'] ) ) {
			$this->add_link_attributes( 'terms_condition', $settings['ekit_register_terms_conditions_url'] );
		}

		if ( 'yes' === $settings['ekit_register_terms_conditions_enable'] ) : ?>
			<div class="ekit-register-form-field ekit-register-terms-field">
				<label class="ekit-register-terms-label">
					<input 
						type="checkbox" 
						name="ekit_register_terms" 
						id="ekit-register-terms" 
						class="ekit-register-terms-checkbox"
						required
						aria-required="true"
					/>
					<span class="ekit-register-terms-text">
						<?php 
							echo esc_html( $settings['ekit_register_terms_conditions_text'] ?: __( 'I agree with', 'elementskit' ) ); 
							if ( ! empty( $settings['ekit_register_terms_conditions_url']['url'] ) ) :
						?>
							<a <?php echo $this->get_render_attribute_string( 'terms_condition' ); ?> class="ekit-register-terms-link">
								<?php echo esc_html( $settings['ekit_register_terms_conditions_link_text'] ?: __( 'Terms & Condition Policy', 'elementskit' ) ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $settings['ekit_register_terms_conditions_link_text'] ?: __( 'Terms & Condition Policy', 'elementskit' ) ); ?>
						<?php endif; ?>
					</span>
				</label>
			</div>
		<?php endif; 
	}		
	// Register Button
    protected function render_submit_button( $settings ) {
        ?>
            <div class="ekit-register-form-button-wrapper">
                <button 
                    type="submit" 
                    class="ekit-register-form-button elementor-button elementor-size-<?php echo esc_attr( $settings['ekit_register_button_size'] ?? 'sm' ); ?>" 
                    name="wp-submit"
                    id="wp-submit"
                >
                    <span class="ekit-register-form-button-text"> <?php echo esc_html( $settings['ekit_register_button_text'] ?? __( 'Create Account', 'elementskit' ) ); ?> </span>
                    <?php if ( ! empty( $settings['ekit_register_button_icon']['value'] ) ) : ?>
                        <?php Icons_Manager::render_icon( $settings['ekit_register_button_icon'], ['class' => 'ekit-register-form-button-icon', 'aria-hidden' => 'true', 'fill' => 'currentColor'] ); ?>
                    <?php endif; ?>
                </button>
            </div>
        <?php
    }
    /**
     * Outputs HTML placeholders for register-related messages.
     *
     * @param bool $error Whether to render the error message container. Default true.
     * @return void
     */
    protected function messages_html( $error = true ) {
        if ( $error ) : ?>
            <div class="ekit-register-error-message hidden" role="alert"></div>
        <?php endif;
    }

	/**
	 * Get input type for a given registration field type.
	 *
	 * @param string $field_type Field type from settings.
	 * @return string Input type for HTML <input>.
	 */
	protected function get_input_type( $field_type ) {
		switch ( $field_type ) {
			case 'user_email':
				return 'email';
			case 'user_url':
				return 'url';
			case 'user_phone':
				return 'tel';
			case 'user_pass':
			case 'user_confirm_password':
				return 'password';
			case 'user_first_name':
			case '':
			case 'user_last_name':
			default:
				return 'text';
		}
	}

	/**
	 * Renders login link section for the login form.
	 *
	 * This method displays a login link if user login is enabled in WordPress settings
	 * and the widget settings allow it. The link can be positioned at different locations based on
	 * the provided condition.
	 *
	 * @since 4.0.0
	 *
	 * @param array  $settings       Widget settings array containing login link configuration.
	 * @param string $position_class CSS class for positioning the login link container.
	 * @param string $condition      Current position context ('form_start', 'form_end', etc.) to match against settings.
	 *
	 * @return void Outputs HTML markup directly. Returns early if conditions are not met.
	 */
	protected function login_form_links( $settings, $position_class, $condition ) {

		if ( 'yes' !== $settings['ekit_register_show_login'] ) {
			return;
		}

		if ( $condition !== $settings['ekit_register_show_login_position'] ) {
			return;
		}

		// 2. Prepare login URL (fallback to WP default)
		if ( empty( $settings['ekit_register_login_url']['url'] ) ) {
			$settings['ekit_register_login_url']['url'] = wp_login_url();
		}

		// 3. Add Elementor link attributes
		$this->add_link_attributes(
			'ekit_login_url',
			$settings['ekit_register_login_url']
		);
		?>

		<div class="ekit-register-form-links <?php echo esc_attr( $position_class ); ?>">

			<?php if ( in_array( $condition, [ 'form_start', 'form_end' ], true ) ) : ?>
				<span class="ekit-register-login-desc">
					<?php echo esc_html( $settings['ekit_register_show_login_description'] ); ?>
				</span>
			<?php endif; ?>

			<a class="ekit-register-login" <?php echo $this->get_render_attribute_string( 'ekit_login_url' ); ?>>
				<?php echo esc_html( $settings['ekit_register_login_text'] ); ?>
			</a>

		</div>
		<?php
	}

    /**
     * Add CSS classes to an input group's render attributes based on provided options.
     *
     * @param string $group     Render attribute group name to modify.
     * @param array  $determine Associative array of options.
     * @return void
     */
	protected function add_input_group_classes( string $group, array $determine ) {
		// Base class
		$this->add_render_attribute( $group, 'class', 'ekit-register-form-input-group' );

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

	/**
	 * Renders a separator element for the register form, typically used to visually separate
	 * sections such as social login options from other form fields.
	 *
	 * @param array $settings The settings array containing configuration for the register form.
	 *      Expects 'ekit_register_social_login_separator_text' key for the separator label.
	 */
	protected function render_separator( $settings ) {
		?>
		<div class="ekit-register-separator">
			<span><?php echo esc_html( $settings['ekit_register_social_login_separator_text'] ); ?></span>
		</div>
		<?php
	}
}