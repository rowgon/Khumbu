<?php

namespace Elementor;

use \Elementor\ElementsKit_Widget_Stacked_Cards_Handler as Handler;
use \ElementsKit_Lite\Modules\Controls\Controls_Manager as ElementsKit_Controls_Manager;

defined('ABSPATH') || exit;
class ElementsKit_Widget_Stacked_Cards extends Widget_Base {
	use \ElementsKit_Lite\Widgets\Widget_Notice;

	public $base;

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
		return 'https://wpmet.com/doc/stacked-cards';
	}

	public function get_script_depends() {
		return ['gsap', 'gsap-scroll-trigger', 'ekit-pro-stacked-cards'];
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	protected function register_controls() {
		// Stacked Card Content
		$this->start_controls_section(
			'section_title',
			[
				'label' => esc_html__('Cards', 'elementskit'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		$repeater->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'card_bg_color',
				'label' => esc_html__('Background', 'elementskit'),
				'types' => ['classic', 'gradient', 'image'],
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}}',
			]
		);

		$repeater->add_control(
			'content_type',
			[
				'label'   => esc_html__('Content Type', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'content'  => esc_html__('Content', 'elementskit'),
					'template' => esc_html__('Saved Templates', 'elementskit'),
				],
				'default' => 'content',
			]
		);

		$repeater->add_control(
			'elementor_templates',
			[
				'label' => esc_html__('Choose Template', 'elementskit'),
				'type'      => ElementsKit_Controls_Manager::AJAXSELECT2,
				'options'   => 'ajaxselect2/elementor_template_list',
				'label_block' => true,
				'multiple'  => false,
				'condition'   => [
					'content_type' => 'template',
				],
			]
		);

		$repeater->start_controls_tabs(
			'card_tabs'
		);

		$repeater->start_controls_tab(
			'ekit_stacked_card_content_tab',
			[
				'label' => esc_html__('Content', 'elementskit'),
				'condition' => [
					'content_type' => 'content',
				],
			]
		);

		$repeater->add_control(
			'title',
			[
				'label'       => esc_html__('Title', 'elementskit'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Card Item', 'elementskit'),
				'label_block' => true,
				'condition' => [
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'title_tag',
			[
				'label'   => esc_html__('Title Tag', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => [
					'h1'   => esc_html__('H1', 'elementskit'),
					'h2'   => esc_html__('H2', 'elementskit'),
					'h3'   => esc_html__('H3', 'elementskit'),
					'h4'   => esc_html__('H4', 'elementskit'),
					'h5'   => esc_html__('H5', 'elementskit'),
					'h6'   => esc_html__('H6', 'elementskit'),
					'span' => esc_html__('Span', 'elementskit'),
					'p'    => esc_html__('P', 'elementskit'),
					'div'  => esc_html__('Div', 'elementskit'),
				],
				'condition' => [
					'content_type' => 'content',
					'title!' => '',
				]
			]
		);

		$repeater->add_control(
			'content_media',
			[
				'label'   => esc_html__('Choose Type', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'none' => [
						'title' => esc_html__('None', 'elementskit'),
						'icon'  => 'fa fa-ban',
					],
					'icon' => [
						'title' => esc_html__('Icon', 'elementskit'),
						'icon'  => 'fa fa-paint-brush',
					],
					'image' => [
						'title' => esc_html__('Image', 'elementskit'),
						'icon'  => 'fa fa-image',
					],
				],
				'default' => 'icon',
				'condition' => [
					'content_type' => 'content',
				],
			]
		);

		$repeater->add_control(
			'ekit_stacked_card_content_image',
			[
				'label'       => esc_html__('Choose Image', 'elementskit'),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => ['image', 'svg'],
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
					'id' => -1
				],
				'condition' => [
					'content_media' => 'image',
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'content_icon',
			[
				'label' => esc_html__('Choose Icon', 'elementskit'),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'ekit_icon_box_header_icon',
				'default' => [
					'value' => 'icon icon-heart',
					'library' => 'ekiticons',
				],
				'label_block' => true,
				'condition' => [
					'content_media' => 'icon',
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'content',
			[
				'label' => esc_html__('Description', 'elementskit'),
				'type' => Controls_Manager::TEXTAREA,
				'default'     => esc_html__('Stacked Cards display content in a layered, interactive layout that highlights each item with smooth transitions.', 'elementskit'),
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'button_heading',
			[
				'label' => esc_html__('Button', 'elementskit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_button' => 'yes',
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'show_button',
			[
				'label'        => esc_html__('Show Button', 'elementskit'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'elementskit'),
				'label_off'    => esc_html__('Hide', 'elementskit'),
				'return_value' => 'yes',
				'default'      => 'no',
				'condition'    => [
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'button_text',
			[
				'label'       => esc_html__('Button Text', 'elementskit'),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__('Read More', 'elementskit'),
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				]
			]
		);

		$repeater->add_control(
			'button_link',
			[
				'label'   => esc_html__('Button Link', 'elementskit'),
				'type'    => Controls_Manager::URL,
				'options' => ['url', 'is_external', 'nofollow'],
				'default' => [
					'url'         => esc_html__('#', 'elementskit'),
					'is_external' => false,
					'nofollow'    => true,
				],
				'label_block' => true,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				]
			]
		);

		$repeater->add_control(
			'button_icon_type',
			[
				'label'   => esc_html__('Choose Button Icon Type', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'none' => [
						'title' => esc_html__('None', 'elementskit'),
						'icon'  => 'fa fa-ban',
					],
					'icon' => [
						'title' => esc_html__('Icon', 'elementskit'),
						'icon'  => 'fa fa-paint-brush',
					],
				],
				'default' => 'icon',
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				]
			]
		);

		$repeater->add_control(
			'button_icon',
			[
				'label' => esc_html__('Choose Button Icon', 'elementskit'),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'ekit_icon_box_header_icon',
				'default' => [
					'value' => 'icon icon-right-arrow',
					'library' => 'ekiticons',
				],
				'label_block' => true,
				'condition' => [
					'button_icon_type' => 'icon',
					'content_type' => 'content',
					'show_button' => 'yes',
				]
			]
		);

		$repeater->add_control(
			'button_normal',
			[
				'label' => esc_html__('Button Normal Styles', 'elementskit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_button' => 'yes',
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'btn_normal_color',
			[
				'label'     => esc_html__('Text Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button' => 'color: {{VALUE}};',
				],
			]
		);

		$repeater->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'btn_normal_bg_color_group',
				'label' => esc_html__('Normal Background', 'elementskit'),
				'types' => ['classic', 'gradient'],
				'exclude' => ['image'],
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				],
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button',
			]
		);

		$repeater->add_control(
			'btn_normal_border_color',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button' => 'border-color: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'button_icon_color',
			[
				'label'     => esc_html__('Button Icon Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
					'button_icon_type' => 'icon',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button > i' => 'color: {{VALUE}};',
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button > svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'button_hover',
			[
				'label' => esc_html__('Button Hover Styles', 'elementskit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => [
					'show_button' => 'yes',
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'btn_hover_color',
			[
				'label'     => esc_html__('Text Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$repeater->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'btn_hover_bg_color_group',
				'label' => esc_html__('Background', 'elementskit'),
				'types' => ['classic', 'gradient'],
				'exclude' => ['image'],
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				],
				'selector' => '{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button:hover',
			]
		);

		$repeater->add_control(
			'btn_hover_border_color',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$repeater->add_control(
			'button_icon_color_hover',
			[
				'label'     => esc_html__('Button Icon Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'condition'   => [
					'content_type' => 'content',
					'show_button' => 'yes',
					'button_icon_type' => 'icon',
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button:hover > i' => 'color: {{VALUE}};',
					'{{WRAPPER}} {{CURRENT_ITEM}} .ekit-stacked-cards__button:hover > svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$repeater->end_controls_tab();

		$repeater->start_controls_tab(
			'card_media_tab',
			[
				'label' => esc_html__('Media', 'elementskit'),
				'condition' => [
					'content_type' => 'content',
				],
			]
		);

		$repeater->add_control(
			'list_type',
			[
				'label'   => esc_html__('Choose Type', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'none' => [
						'title' => esc_html__('None', 'elementskit'),
						'icon'  => 'fa fa-ban',
					],
					'image' => [
						'title' => esc_html__('Image', 'elementskit'),
						'icon'  => 'eicon-image-bold',
					],
					'icon' => [
						'title' => esc_html__('Icon', 'elementskit'),
						'icon'  => 'eicon-icon-box',
					],
				],
				'default' => 'image',
				'condition' => [
					'content_type' => 'content',
				],
			]
		);

		$repeater->add_control(
			'list_direction',
			[
				'label'   => esc_html__('Direction', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'row-reverse' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-arrow-left',
					],
					'row' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-arrow-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} {{CURRENT_ITEM}}.ekit-stacked-cards__item, {{WRAPPER}} {{CURRENT_ITEM}}.ekit-stacked-cards__item_horizontal' => 'flex-direction: {{VALUE}};',
				],
				'condition' => [
					'content_type' => 'content',
				],
			]
		);

		$repeater->add_control(
			'ekit_stacked_card_image',
			[
				'label'       => esc_html__('Choose Image', 'elementskit'),
				'type'        => Controls_Manager::MEDIA,
				'media_types' => ['image', 'svg'],
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
					'id' => -1
				],
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'list_type' => 'image',
					'content_type' => 'content',
				]
			]
		);

		$repeater->add_control(
			'icon',
			[
				'label'       => esc_html__('Choose Icon', 'elementskit'),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-feather-alt',
					'library' => 'fa-solid',
				],
				'condition' => [
					'list_type' => 'icon',
					'content_type' => 'content',
				]
			]
		);

		$repeater->end_controls_tab();

		$repeater->end_controls_tabs();

		$this->add_control(
			'ekit_stacked_card_list',
			[
				'label'       => esc_html__('Card Items', 'elementskit'),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default' => [
					[
						'title' => esc_html__('Card #1', 'elementskit'),
					],
					[
						'title' => esc_html__('Card #2', 'elementskit'),
					],
					[
						'title' => esc_html__('Card #3', 'elementskit'),
					],
				],
				'title_field' => '{{{ title }}}',
			]
		);

		$this->end_controls_section();

		// Stacked Card Settings
		$this->start_controls_section(
			'ekit_stacked_card_settings',
			[
				'label' => esc_html__('Settings', 'elementskit'),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_stacked_card_style',
			[
				'label'   => esc_html__('Card Style', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'vertical',
				'options' => [
					'vertical'   => esc_html__('Vertical', 'elementskit'),
					'horizontal' => esc_html__('Horizontal', 'elementskit'),
				],
			]
		);

		$transform_options = [
			'none'      => esc_html__('None', 'elementskit'),
			'rotate'    => esc_html__('Rotate', 'elementskit'),
			'translate' => esc_html__('Translate', 'elementskit')
		];

		$this->add_control(
			'ekit_stacked_card_transform',
			[
				'label'   => esc_html__('Transform', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'translate',
				'options' => array_merge($transform_options, [
					'scale' => esc_html__('Scale', 'elementskit'),
				]),
				'condition' => ['ekit_stacked_card_style' => 'vertical']
			]
		);

		$this->add_control(
			'ekit_stacked_card_transform_horizontal',
			[
				'label'   => esc_html__('Transform', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'translate',
				'options' => $transform_options,
				'condition' => ['ekit_stacked_card_style' => 'horizontal']
			]
		);

		$this->add_control(
			'ekit_stacked_card_rotation',
			[
				'label' => esc_html__('Rotation', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'size_units' => ['deg'],
				'range' => [
					'deg' => [
						'min'  => -360,
						'max'  => 360,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 6,
				],
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[ // Group 1: Vertical case
							'relation' => 'and',
							'terms' => [
								[
									'name'     => 'ekit_stacked_card_transform',
									'operator' => '==',
									'value'    => 'rotate',
								],
								[
									'name'     => 'ekit_stacked_card_style',
									'operator' => '==',
									'value'    => 'vertical',
								],
							],
						],
						[ // Group 2: Horizontal case
							'relation' => 'and',
							'terms' => [
								[
									'name'     => 'ekit_stacked_card_transform_horizontal',
									'operator' => '==',
									'value'    => 'rotate',
								],
								[
									'name'     => 'ekit_stacked_card_style',
									'operator' => '==',
									'value'    => 'horizontal',
								],
							],
						],

					],
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_scale',
			[
				'label' => esc_html__('Scale', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min'  => -10,
						'max'  => 10,
						'step' => .1,
					],
				],
				'default' => [
					'size' => 0,
				],
				'condition' => [
					'ekit_stacked_card_transform' => 'scale',
					'ekit_stacked_card_style' => 'vertical',
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_translate',
			[
				'label' => esc_html__('Translate', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min'  => -100,
						'max'  => 100,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 20,
				],
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[ // Group 1: Vertical case
							'relation' => 'and',
							'terms' => [
								[
									'name'     => 'ekit_stacked_card_transform',
									'operator' => '==',
									'value'    => 'translate',
								],
								[
									'name'     => 'ekit_stacked_card_style',
									'operator' => '==',
									'value'    => 'vertical',
								],
							],
						],
						[ // Group 2: Horizontal case
							'relation' => 'and',
							'terms' => [
								[
									'name'     => 'ekit_stacked_card_transform_horizontal',
									'operator' => '==',
									'value'    => 'translate',
								],
								[
									'name'     => 'ekit_stacked_card_style',
									'operator' => '==',
									'value'    => 'horizontal',
								],
							],
						],

					],
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_translate_effect',
			[
				'label'   => esc_html__('Translate Animation', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'      => esc_html__('None', 'elementskit'),
					'skew'    => esc_html__('Skew', 'elementskit'),
				],
				'condition' => [
					'ekit_stacked_card_transform' => 'translate',
					'ekit_stacked_card_style' => 'vertical',
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_item_opacity',
			[
				'label' => esc_html__('Opacity', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max'  => 1,
						'step' => .1,
					],
				],
				'default' => [
					'size' => 1,
				],
				'condition' => ['ekit_stacked_card_style' => 'vertical']
			]
		);

		$this->add_control(
			'ekit_stacked_card_filter',
			[
				'label'   => esc_html__('Filter', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none'      => esc_html__('None', 'elementskit'),
					'blur'      => esc_html__('Blur', 'elementskit'),
					'opacity'   => esc_html__('Opacity', 'elementskit'),
					'grayscale' => esc_html__('Grayscale', 'elementskit'),
					'sepia'     => esc_html__('Sepia', 'elementskit'),
				],
				'condition' => ['ekit_stacked_card_style' => 'vertical']
			]
		);

		$this->add_control(
			'ekit_stacked_card_blur',
			[
				'label' => esc_html__('Blur', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 50,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 1,
				],
				'condition' => [
					'ekit_stacked_card_filter' => 'blur',
					'ekit_stacked_card_style' => 'vertical',
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_opacity',
			[
				'label' => esc_html__('Opacity', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
				],
				'default' => [
					'size' => 25,
				],
				'condition' => [
					'ekit_stacked_card_filter' => 'opacity',
					'ekit_stacked_card_style' => 'vertical'
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_grayscale',
			[
				'label' => esc_html__('Grayscale', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
				],
				'default' => [
					'size' => 50,
				],
				'condition' => [
					'ekit_stacked_card_filter' => 'grayscale',
					'ekit_stacked_card_style' => 'vertical',
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_sepia',
			[
				'label' => esc_html__('Sepia', 'elementskit'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 5,
					],
				],
				'default' => [
					'size' => 50,
				],
				'condition' => [
					'ekit_stacked_card_filter' => 'sepia',
					'ekit_stacked_card_style' => 'vertical',
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_start_form',
			[
				'label'     => esc_html__('Card Direction From (Top/Bottom)', 'elementskit'),
				'type'      => Controls_Manager::SLIDER,
				'description' => esc_html__('Define the card’s starting position. Selecting a plus (+) value moves the card from bottom to top, while selecting a minus (–) value moves it from top to bottom.', 'elementskit'),
				'separator' => 'before',
				'range'     => [
					'px' => [
						'min'  => -500,
						'max'  => 500,
						'step' => 10,
					],
				],
				'default' => [
					'size' => 400,
				],
				'condition' => [
					'ekit_stacked_card_style' => 'vertical',
				]
			]
		);

		$this->add_control(
			'ekit_stacked_card_left_from',
			[
				'label'     => esc_html__('Card Direction From (Left/Right)', 'elementskit'),
				'type'      => Controls_Manager::SLIDER,
				'description' => esc_html__('Selecting a plus (+) value moves the card from left to right, while selecting a minus (–) value moves it from right to left.', 'elementskit'),
				'separator' => 'before',
				'range'     => [
					'px' => [
						'min'  => -500,
						'max'  => 500,
						'step' => 10,
					],
				],
				'condition' => [
					'ekit_stacked_card_style' => 'vertical',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_start',
			[
				'label'       => esc_html__('Card Start From', 'elementskit'),
				'type'        => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 10,
					],
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 100,
				],
				'description' => esc_html__('Specifies the pixel value from which the stacked card animation begins.', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_stacked_card_end_from',
			[
				'label'     => esc_html__('Card End From', 'elementskit'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => [
					'default' => esc_html__('Default', 'elementskit'),
					'custom'  => esc_html__('Custom', 'elementskit'),
				],
				'condition' => [
					'ekit_stacked_card_style' => 'vertical',
				],
				'description' => esc_html__('Specifies the pixel value to which the stacked card animation will end.', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_stacked_card_end',
			[
				'label'       => esc_html__('Custom End Value', 'elementskit'),
				'type'        => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range'       => [
					'px' => [
						'min'  => -1000,
						'max'  => 1000,
						'step' => 10,
					],
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 500,
				],
				'condition' => [
					'ekit_stacked_card_end_from' => 'custom',
					'ekit_stacked_card_style' => 'vertical',
				],
				'description' => esc_html__('Defines the ending scroll position for the ScrollTrigger in pixels.', 'elementskit')
			]
		);

		$this->add_control(
			'ekit_stacked_card_hr_end_from',
			[
				'label'     => esc_html__('Card End From', 'elementskit'),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'default',
				'options'   => [
					'default' => esc_html__('Default', 'elementskit'),
					'custom'  => esc_html__('Custom', 'elementskit'),
				],
				'condition' => [
					'ekit_stacked_card_style' => 'horizontal',
				],
				'description' => esc_html__('Specifies the pixel value to which the stacked card animation will end.', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_stacked_hr_card_end',
			[
				'label'       => esc_html__('Custom End Value', 'elementskit'),
				'type'        => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 10,
					],
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 500,
				],
				'condition' => [
					'ekit_stacked_card_hr_end_from' => 'custom',
					'ekit_stacked_card_style' => 'horizontal',
				],
				'description' => esc_html__('Defines the ending scroll position for the ScrollTrigger in pixels.', 'elementskit')
			]
		);

		$this->add_control(
			'ekit_stacked_card_marker',
			[
				'label'        => esc_html__('Show Marker', 'elementskit'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Show', 'elementskit'),
				'label_off'    => esc_html__('Hide', 'elementskit'),
				'separator'    => 'before',
				'return_value' => 'true',
				'description'  => esc_html__('Display markers during development to visualize where the card animation starts and ends.', 'elementskit'),
			]
		);
		$this->end_controls_section();

		// Stacked Card Styles
		$this->start_controls_section(
			'ekit_stacked_card_wrapper_style',
			[
				'label' => esc_html__('Wrapper', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_wrapper_height',
			[
				'label'      => esc_html__('Height', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['vh', 'px', '%', 'em', 'rem'],
				'range'      => [
					'vh' => [
						'min'  => 10,
						'max'  => 100,
						'step' => 5,
					],
					'px' => [
						'min'  => 1,
						'max'  => 2000,
						'step' => 10,
					],
				],
				'default' => [
					'size' => 100,
					'unit' => 'vh',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__container' => 'height: {{SIZE}}{{UNIT}};',
				],
				'render_type' => 'template',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_stacked_cards_style',
			[
				'label' => esc_html__('Cards', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control( 
			'ekit_stacked_card_align_items', 
			[
				'label' => esc_html__( 'Align Items', 'elementskit' ),
				'type' => Controls_Manager::CHOOSE,
				'default' => '',
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'elementskit' ),
						'icon' => 'eicon-flex eicon-align-start-v',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementskit' ),
						'icon' => 'eicon-flex eicon-align-center-v',
					],
					'flex-end' => [
						'title' => esc_html__( 'End', 'elementskit' ),
						'icon' => 'eicon-flex eicon-align-end-v',
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__item' => 'align-items: {{VALUE}};',
					'{{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs(
			'ekit_sc_style_tabs'
		);

		$this->start_controls_tab(
			'ekit_sc_style_normal_tab',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_stacked_card_item_bg',
				'label' => esc_html__('Background', 'elementskit'),
				'types' => ['classic', 'gradient', 'image'],
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal',
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab(
			'ekit_sc_style_hover_tab',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_stacked_card_item_bg_hover',
				'label' => esc_html__('Background Hover', 'elementskit'),
				'types' => ['classic', 'gradient'],
				'exclude' => ['image'],
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__item:hover, {{WRAPPER}} .ekit-stacked-cards__item_horizontal:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'ekit_stacked_card_style_hr',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_container_height',
			[
				'label'      => esc_html__('Height', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['vh', 'px', '%', 'em', 'rem'],
				'range'      => [
					'vh' => [
						'min'  => 10,
						'max'  => 100,
						'step' => 5,
					],
					'px' => [
						'min'  => 1,
						'max'  => 2000,
						'step' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_container_width',
			[
				'label'      => esc_html__('Width', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vw'],
				'range'      => [
					'vw' => [
						'min'  => 10,
						'max'  => 100,
						'step' => 5,
					],
					'px' => [
						'min'  => 1,
						'max'  => 2000,
						'step' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'ekit_stacked_card_item_border',
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal',
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_item_border_radiout',
			[
				'label'      => esc_html__('Border Radius', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'default'    => [
					'top'      => 20,
					'right'    => 20,
					'bottom'   => 20,
					'left'     => 20,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'ekit_stacked_card_item_box_shadow',
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal',
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_item_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'default'    => [
					'top'      => 60,
					'right'    => 45,
					'bottom'   => 60,
					'left'     => 45,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();

		// Stacked Card Content Icon/Image Styles
		$this->start_controls_section(
			'ekit_stacked_card_content_icon_style',
			[
				'label' => esc_html__('Icon / Image', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ekit_stacked_card_item_content_icon_heading',
			[
				'label' => esc_html__('Icon', 'elementskit'),
				'type' => \Elementor\Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_item_content_icon_size',
			[
				'label'      => esc_html__('Size', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'rem'],
				'range'      => [
					'px' => [
						'min'  => 10,
						'max'  => 500,
						'step' => 5,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 70,
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__content__media i, {{WRAPPER}} .ekit-stacked-cards__content__media svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_item_content_icon_color',
			[
				'label'      => esc_html__('Color', 'elementskit'),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__content__media i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-stacked-cards__content__media svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_item_content_image_heading',
			[
				'label' => esc_html__('Image', 'elementskit'),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name' => 'ekit_stacked_card_content_image_size',
				'default' => 'full'
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_item_content_image_height',
			[
				'label'      => esc_html__('Height', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem'],
				'range'      => [
					'px' => [
						'min'  => 10,
						'max'  => 500,
						'step' => 5,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__content__media img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_item_content_image_width',
			[
				'label'      => esc_html__('Width', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem'],
				'range'      => [
					'px' => [
						'min'  => 10,
						'max'  => 500,
						'step' => 5,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__content__media img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_item_content_hr',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_item_content_icon_margin',
			[
				'label'      => esc_html__('Margin', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'default'    => [
					'top'      => 0,
					'right'    => 0,
					'bottom'   => 20,
					'left'     => 0,
					'unit'     => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__content__media' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();

		// Stacked Card Content Title Styles
		$this->start_controls_section(
			'ekit_stacked_card_content_title_style',
			[
				'label' => esc_html__('Title', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ekit_stacked_card_content_title_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_stacked_card_content_title_typography',
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__title',
				'fields_options' => [
					'typography' => ['default' => 'yes'],
					'font_family' => ['default' => 'Inter'],
					'font_weight' => ['default' => 600],
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_content_title_align',
			[
				'label'   => esc_html__('Alignment', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'elementskit'),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'left',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__title' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'ekit_stacked_card_content_title_margin',
			[
				'label'      => esc_html__('Margin', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();

		// Stacked Card Description Styles
		$this->start_controls_section(
			'ekit_stacked_card_content_content_style',
			[
				'label' => esc_html__('Description', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ekit_stacked_card_content_text_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__content p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_stacked_card_content_text_typography',
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__content p',
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_content_align',
			[
				'label'   => esc_html__('Alignment', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'elementskit'),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'left',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__content p' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'ekit_stacked_card_content_margin',
			[
				'label'      => esc_html__('Margin', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__content p' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();

		// Stacked Card Button Styles
		$this->start_controls_section(
			'ekit_stacked_card_content_button',
			[
				'label' => esc_html__('Button', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_stacked_card_content_button_typography',
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__button',
			]
		);

		$this->start_controls_tabs(
			'ekit_stacked_card_style_btn_tabs'
		);

		$this->start_controls_tab(
			'ekit_stacked_card_style_btn_normal_tab',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_stacked_card_button_normal_color',
			[
				'label'     => esc_html__('Text Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#050303',
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_stacked_card_button_normal_bg',
				'label' => esc_html__('Normal Background', 'elementskit'),
				'types' => ['classic', 'gradient'],
				'exclude' => ['image'],
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__button',
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab(
			'ekit_stacked_card_style_btn_hover_tab',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_stacked_card_button_hover_color',
			[
				'label'     => esc_html__('Text Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__button:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_stacked_card_button_hover_bg',
				'label' => esc_html__('Hover Background', 'elementskit'),
				'types' => ['classic', 'gradient'],
				'exclude' => ['image'],
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__button:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'ekit_stacked_card_button_divider',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_content_button_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'default' => [
					'top'    => 10,
					'right'  => 30,
					'bottom' => 10,
					'left'   => 30,
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_content_button_margin',
			[
				'label'      => esc_html__('Margin', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'           => 'ekit_stacked_card_content_btn_normal_border',
				'label'          => esc_html__('Border', 'elementskit'),
				'fields_options' => [
					'border' => [
						'label'   => esc_html__('Border Type', 'elementskit'),
						'default' => 'solid',
					],
					'width' => [
						'label' => esc_html__('Border Width', 'elementskit'),
						'default' => [
							'top'      => '1',
							'right'    => '1',
							'bottom'   => '1',
							'left'     => '1',
							'isLinked' =>  false,
						],
					],
					'color' => [
						'label'   => esc_html__('Border Color', 'elementskit'),
						'default' => '#333',
					],

				],
				'selector' => '{{WRAPPER}} .ekit-stacked-cards__button',
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_content_button_border_radiout',
			[
				'label'      => esc_html__('Border Radius', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'default' => [
					'top'    => 5,
					'right'  => 5,
					'bottom' => 5,
					'left'   => 5,
					'unit'   => 'px',
					'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_button_icon_heading',
			[
				'label' => esc_html__('Icon', 'elementskit'),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_stacked_card_button_icon_direction',
			[
				'label'   => esc_html__('Direction', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'row-reverse' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-arrow-left',
					],
					'row' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-arrow-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__button' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_button_icon_color',
			[
				'label'      => esc_html__('Color', 'elementskit'),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__button > i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-stacked-cards__button > svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_button_icon_size',
			[
				'label'      => esc_html__('Size (px)', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__button > i, {{WRAPPER}} .ekit-stacked-cards__button > svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_button_icon_gap',
			[
				'label'      => esc_html__('Gap Between (px)', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'max' => 100,
					],
				],
				'default' => [
					'size' => 10,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__button' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);



		$this->end_controls_section();

		// Stacked Card Media Style
		$this->start_controls_section(
			'ekit_stacked_card_media_style',
			[
				'label' => esc_html__('Media', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'list_direction',
			[
				'label'   => esc_html__('Direction', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'row-reverse' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-arrow-left',
					],
					'row' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-arrow-right',
					],
				],
				'default' => 'row',
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name' => 'ekit_stacked_card_image_size',
				'default' => 'full'
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_image_section_width',
			[
				'label'      => esc_html__('Section Width', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['%', 'px', 'vw', 'rem', 'custom'],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__media' => 'flex: 0 0 {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_image_gap',
			[
				'label'      => esc_html__('Gap', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'default' => [
					'unit' => 'px',
					'size' => 30,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__item, {{WRAPPER}} .ekit-stacked-cards__item_horizontal' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_image_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_image_margin',
			[
				'label'      => esc_html__('Margin', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__media' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_icon_header',
			[
				'label'     => esc_html__('Icon', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_icon_size',
			[
				'label'      => esc_html__('Icon Size', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'rem'],
				'range'      => [
					'px' => [
						'min'  => 10,
						'max'  => 500,
						'step' => 10,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 100,
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__media i, {{WRAPPER}} .ekit-stacked-cards__media svg' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_stacked_card_icon_color',
			[
				'label'      => esc_html__('Icon Color', 'elementskit'),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__media i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-stacked-cards__media svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_icon_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__media svg, {{WRAPPER}} .ekit-stacked-cards__media i' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_icon_margin',
			[
				'label'      => esc_html__('Margin', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'rem', 'custom'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-stacked-cards__media svg, {{WRAPPER}} .ekit-stacked-cards__media i' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_stacked_card_icon_align',
			[
				'label'   => esc_html__('Icon Alignment', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'elementskit'),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'toggle'    => true,
				'selectors' => [
					'{{WRAPPER}} .ekit-stacked-cards__media' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function get_control_value($settings, $key, $default = '') {
		return !empty($settings[$key]['size']) ? $settings[$key]['size'] : $default;
	}

	protected function ekit_stacked_card_data($settings) {
		$card_data = [];
		$card_data['translate'] = $this->get_control_value($settings, 'ekit_stacked_card_translate', 0);
		$card_data['scale'] = $this->get_control_value($settings, 'ekit_stacked_card_scale', 0);
		$card_data['rotation'] = $this->get_control_value($settings, 'ekit_stacked_card_rotation', 0);
		$card_data['blur'] = $this->get_control_value($settings, 'ekit_stacked_card_blur', 0);
		$card_data['grayscale'] = $this->get_control_value($settings, 'ekit_stacked_card_grayscale', 0);
		$card_data['opacity'] = $this->get_control_value($settings, 'ekit_stacked_card_opacity', 0);
		$card_data['sepia'] = $this->get_control_value($settings, 'ekit_stacked_card_sepia', 0);
		$card_data['marker'] = ! empty($settings['ekit_stacked_card_marker']) ? $settings['ekit_stacked_card_marker'] : false;
		$card_data['start_form'] = $this->get_control_value($settings, 'ekit_stacked_card_start_form', 0);
		$card_data['left_from'] = $this->get_control_value($settings, 'ekit_stacked_card_left_from', 0);
		$card_data['end_from'] = ! empty($settings['ekit_stacked_card_end_from']) ? $settings['ekit_stacked_card_end_from'] : 'default';
		$card_data['start'] = !empty($settings['ekit_stacked_card_start']['size']) ? ($settings['ekit_stacked_card_start']['size'] . $settings['ekit_stacked_card_start']['unit']) : 0;
		$card_data['item_opacity'] = $this->get_control_value($settings, 'ekit_stacked_card_item_opacity', 1.1);
		$card_data['end'] = !empty($settings['ekit_stacked_card_end']['size']) ? ($settings['ekit_stacked_card_end']['size'] . $settings['ekit_stacked_card_end']['unit']) : 0;
		$card_data['hr_end_from'] = ! empty($settings['ekit_stacked_card_hr_end_from']) ? $settings['ekit_stacked_card_hr_end_from'] : 'default';
		$card_data['hr_end'] = !empty($settings['ekit_stacked_hr_card_end']['size']) ? ($settings['ekit_stacked_hr_card_end']['size'] . $settings['ekit_stacked_hr_card_end']['unit']) : 0;
		return $card_data;
	}

	protected function ekit_render_content($settings, $key, $card) { ?>
		<div <?php $this->print_render_attribute_string('ekit-stacked-card'); ?>>
			<div class="ekit-stacked-cards__content">
				<div class="ekit-stacked-cards__body">
					<?php if ($card['content_media'] !== 'none') : ?>
						<div class="ekit-stacked-cards__content__media">
							<?php if ($card['content_media'] === 'image') {
								$card['ekit_stacked_card_content_image_size_size'] = $settings['ekit_stacked_card_content_image_size_size'];
								$card['ekit_stacked_card_content_image_size_custom_dimension'] = $settings['ekit_stacked_card_content_image_size_custom_dimension'];

								$image_html = Group_Control_Image_Size::get_attachment_image_html($card, 'ekit_stacked_card_content_image_size', 'ekit_stacked_card_content_image');
								if (!empty($image_html)) echo wp_kses($image_html, \ElementsKit_Lite\Utils::get_kses_array());
							} elseif ($card['content_media'] === 'icon') {
								Icons_Manager::render_icon($card['content_icon'], ['aria-hidden' => 'true']);
							} ?>
						</div>
					<?php endif;

					echo sprintf(
						'<%1$s class="ekit-stacked-cards__title">%2$s</%1$s>',
						esc_attr(Utils::validate_html_tag($card['title_tag'])),
						esc_html($card['title'])
					); 
					$description = \ElementsKit_Lite\Utils::kspan($card['content']);
					?>
					<p class="ekit-stacked-cards__description"><?php echo wp_kses($description, \ElementsKit_Lite\Utils::get_kses_array()) ?></p>

					<?php if ($card['show_button'] === 'yes') {
						$button_key = 'stacked_cards_btn_' . $key;
						$this->add_render_attribute($button_key, ['class' => 'ekit-stacked-cards__button']);

						// Add link attributes if URL exists
						if (!empty($card['button_link']['url'])) {
							$this->add_link_attributes($button_key, $card['button_link']);
						} ?>
						<a <?php echo $this->get_render_attribute_string($button_key); ?>> <?php
                        echo esc_html($card['button_text']); 	 
                        $card['button_icon_type'] === 'icon' && Icons_Manager::render_icon($card['button_icon'], ['aria-hidden' => 'true']);?>
                    	</a>
					<?php } ?>
				</div>
			</div>

			<?php if ($card['list_type'] !== 'none') : ?>
				<div class="ekit-stacked-cards__media"> <?php
					if ($card['list_type'] === 'image') {
						$card['ekit_stacked_card_image_size_size'] = $settings['ekit_stacked_card_image_size_size'];
						$card['ekit_stacked_card_image_size_custom_dimension'] = $settings['ekit_stacked_card_image_size_custom_dimension'];

						$image_html = Group_Control_Image_Size::get_attachment_image_html($card, 'ekit_stacked_card_image_size', 'ekit_stacked_card_image');
						if (!empty($image_html)) echo wp_kses($image_html, \ElementsKit_Lite\Utils::get_kses_array());
					} elseif ($card['list_type'] === 'icon') {
						Icons_Manager::render_icon($card['icon'], ['aria-hidden' => 'true']);
					} ?>
				</div>
			<?php endif; ?>
		</div>
		<?php }

	protected function ekit_render_template($card) {
		$current_page_id = get_the_ID();
		$revisions       = wp_get_post_revisions($current_page_id);
		$revision_ids    = wp_list_pluck($revisions, 'ID');

		if (absint($card['elementor_templates']) === $current_page_id || in_array(absint($card['elementor_templates']), $revision_ids, true)) {
			_e('<p>The selected template corresponds to the current page or one of its revisions.</p>', 'elementskit');
		} else { ?>
			<div <?php $this->print_render_attribute_string('ekit-stacked-card'); ?>>
				<?php echo \ElementsKit_Lite\Utils::render_elementor_content($card['elementor_templates'], false); ?>
			</div>
		<?php }
	}

	protected function get_scroll_trigger_data($settings) {
		$ekit_card_data = $this->ekit_stacked_card_data($settings);
		$trigger_data = [
			'start'       => $ekit_card_data['start'],
			'marker'      => $ekit_card_data['marker'],
			'widgetId'    => $this->get_id(),
		];

		if ($settings['ekit_stacked_card_style'] === 'vertical') {
			if ($ekit_card_data['end_from'] === 'custom') {
				$trigger_data['end'] = $ekit_card_data['end'];
			} else {
				$trigger_data['end'] = 'default';
			}
		}

		if ($settings['ekit_stacked_card_style'] === 'horizontal') {
			if ($ekit_card_data['hr_end_from'] === 'custom') {
				$trigger_data['end'] = $ekit_card_data['hr_end'];
			} else {
				$trigger_data['end'] = 0;
			}
		}

		return $trigger_data;
	}

	protected function get_scale_x(int $total_items, int $key): float {
		if ($key < 0 || $key >= $total_items) {
			return 0.5;
		}

		return 1 - 0.04 * ($total_items - $key);
	}

	protected function get_filter_effect($settings) {
		$filter_type = $settings['ekit_stacked_card_filter'];
		switch ($filter_type) {
			case 'none':
				return '';
			case 'grayscale':
				return "grayscale({$this->get_control_value($settings, 'ekit_stacked_card_grayscale')}%)";
			case 'blur':
				return "blur({$this->get_control_value($settings, 'ekit_stacked_card_blur')}px)";
			case 'sepia':
				return "sepia({$this->get_control_value($settings, 'ekit_stacked_card_sepia')}%)";
			case 'opacity':
				return "opacity({$this->get_control_value($settings, 'ekit_stacked_card_opacity')}%)";
			default:
				return '';
		}
	}

	protected function get_animation_settings($settings, $key) {
		$ekit_card_data = $this->ekit_stacked_card_data($settings);
		$animation = [
			'opacity' => $ekit_card_data['item_opacity'],
			'ease'         => "none",
		];

		$animation['filter'] = $this->get_filter_effect($settings);

		// Add transform effects based on selected type
		$trans_form_type = $settings['ekit_stacked_card_style'] === 'horizontal' ? $settings['ekit_stacked_card_transform_horizontal'] : $settings['ekit_stacked_card_transform'];
		switch ($trans_form_type) {
			case 'translate':
				$translate_value = $this->get_control_value($settings, 'ekit_stacked_card_translate');
				$translate_number = (float) filter_var(
					$translate_value,
					FILTER_SANITIZE_NUMBER_FLOAT,
					FILTER_FLAG_ALLOW_FRACTION
				);
				$animation['y'] = $translate_number * $key;
				if ($settings['ekit_stacked_card_translate_effect'] === 'skew') {
					$animation['scaleX'] = $this->get_scale_x(count($settings['ekit_stacked_card_list']), $key);
				}
				break;
			case 'rotate':
				$rotation = $this->get_control_value($settings, 'ekit_stacked_card_rotation');
				$animation['rotation'] = $key % 2 === 0 ? $rotation : -$rotation;
				break;
			case 'scale':
				$animation['scale'] = $this->get_control_value($settings, 'ekit_stacked_card_scale');
				break;
		}

		return $animation;
	}

	protected function add_card_item_attributes($settings, $card, $key) {
		$ekit_card_data = $this->ekit_stacked_card_data($settings);
		$animated_card_obj = $this->get_animation_settings($settings, $key);

		// Resetting the 'ekit-stacked-card' attributes to avoid duplication
		$this->remove_render_attribute('ekit-stacked-card');
		if ($settings['ekit_stacked_card_style'] === 'horizontal') {
			$spacer = 0;
			$animation = [
				'x' => $spacer * ($key + 1),
			];
			if ($settings['ekit_stacked_card_transform_horizontal'] === 'rotate') {
				$rotation = $key % 2 === 0 ? $ekit_card_data['rotation'] : -$ekit_card_data['rotation'];
				$animation['rotation'] = $rotation;
			} elseif ($settings['ekit_stacked_card_transform_horizontal'] === 'translate') {
				$translate_value = $this->get_control_value($settings, 'ekit_stacked_card_translate');
				$translate_number = (float) filter_var(
					$translate_value,
					FILTER_SANITIZE_NUMBER_FLOAT,
					FILTER_FLAG_ALLOW_FRACTION
				);
				$animation['x'] = $translate_number * $key;
			}

			$animated_card_hr = json_encode($animation);
			$this->add_render_attribute('ekit-stacked-card', [
				'class'         => 'ekit-stacked-cards__item_horizontal elementor-repeater-item-' . esc_attr($card['_id']),
				'data-stacked-card-hr' => $animated_card_hr,
				'data-yaxis'    => $this->get_control_value($settings, 'ekit_stacked_card_translate'),
			]);
		} elseif ($settings['ekit_stacked_card_style'] === 'vertical') {
			$this->add_render_attribute('ekit-stacked-card', [
				'class'             => 'ekit-stacked-cards__item elementor-repeater-item-' . esc_attr($card['_id']),
				'data-stacked-card' => json_encode($animated_card_obj),
				'data-start-form'   => $ekit_card_data['start_form'],
				'data-left-from'   => $ekit_card_data['left_from'],
				'data-yaxis'            => $this->get_control_value($settings, 'ekit_stacked_card_translate'),
			]);
		}
	}

	protected function render() {
		echo '<div class="ekit-wid-con">';
			$this->render_raw();
		echo '</div>';
	}

	protected function render_raw() {
		$settings       = $this->get_settings_for_display();
		$scroll_trigger_data = $this->get_scroll_trigger_data($settings);
		$this->add_render_attribute('ekit-stacked-card-scrolltrigger', [
			'class'              => 'ekit-stacked-cards__container',
			'data-id'            => $this->get_id(),
			'data-card-style'    => $settings['ekit_stacked_card_style'],
			'data-scrolltrigger' => json_encode($scroll_trigger_data),
		]); ?>

		<div class="ekit-stacked-cards">
			<div <?php $this->print_render_attribute_string('ekit-stacked-card-scrolltrigger'); ?>>
				<?php foreach ($settings['ekit_stacked_card_list'] as $key => $card) {
					$this->add_card_item_attributes($settings, $card, $key);

					if ('content' === $card['content_type']) {
						$this->ekit_render_content($settings, $key, $card);
					} elseif ('template' === $card['content_type']) {
						if (! empty($card['elementor_templates'])) {
							$this->ekit_render_template($card);
						}
					}
				} ?>
			</div>
		</div>
	<?php }
}
