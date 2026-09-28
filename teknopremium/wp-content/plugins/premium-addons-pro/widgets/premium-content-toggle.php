<?php
/**
 * Class: Premium_Content_Toggle
 * Name: Content Toggle
 * Slug: premium-addon-content-toggle
 * Prefix: pa-ct__
 */

namespace PremiumAddonsPro\Widgets;

// PremiumAddonsPro Classes.
use PremiumAddonsPro\Includes\PAPRO_Helper;

// Elementor Classes.
use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Box_Shadow;

// PremiumAddons Classes.
use PremiumAddons\Includes\Helper_Functions;
use PremiumAddons\Includes\Controls\Premium_Post_Filter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Premium_Content_Toggle
 */
class Premium_Content_Toggle extends Widget_Base {

	/**
	 * Retrieve Widget Name.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function get_name() {
		return 'premium-addon-content-toggle';
	}

	/**
	 * Retrieve Widget Title.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function get_title() {
		return __( 'Content Toggle', 'premium-addons-pro' );
	}

	/**
	 * Retrieve Widget Icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string widget icon.
	 */
	public function get_icon() {
		return 'pa-pro-content-switcher';
	}

	/**
	 * Retrieve Widget Categories.
	 *
	 * @since 1.5.1
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'premium-elements' );
	}

	/**
	 * Retrieve Widget Keywords.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget keywords.
	 */
	public function get_keywords() {
		return array( 'pa', 'premium', 'premium content toggle', 'toggle' );
	}

	protected function is_dynamic_content(): bool {

		return true;
	}

	/**
	 * Retrieve Widget Dependent CSS.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array CSS style handles.
	 */
	public function get_style_depends() {
		return array(
			'pa-btn',
			'premium-pro',
		);
	}

	/**
	 * Retrieve Widget Dependent JS.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array JS script handles.
	 */
	public function get_script_depends() {
		return array( 'premium-pro' );
	}

	/**
	 * Widget preview refresh button.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function is_reload_preview_required() {
		return true;
	}

	/**
	 * Retrieve Widget Support URL.
	 *
	 * @access public
	 *
	 * @return string support URL.
	 */
	public function get_custom_help_url() {
		return 'https://premiumaddons.com/support/';
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Helper_Functions::check_elementor_experiment( 'e_optimized_markup' );
	}

	/**
	 * Register Content Toggle controls.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->add_switcher_content_tab();
		$this->add_label_content_tab();
		// $this->add_inner_content_tab();
		$this->add_fcontent_tab();
		$this->add_scontent_tab();
		$this->add_inner_labels_tab();
		$this->add_display_option_tab();

		$this->add_switcher_style_tab();
		$this->add_label_style_tab();
		$this->add_inner_labels_style_tab();
		$this->add_toggle_container_style_tab();
		$this->add_content_style_tab();
	}

	private function add_switcher_content_tab() {
		$presets_url = 'https://premiumtemplates.io/wp-content/uploads/premium-content-toggle/';

		$show_labels_conds = array(
			'terms' => array(
				array(
					'name'  => 'type',
					'value' => 'switcher',
				),
				array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'     => 'premium_content_toggle_heading_one',
							'operator' => '!==',
							'value'    => '',
						),
						array(
							'name'     => 'premium_content_toggle_heading_two',
							'operator' => '!==',
							'value'    => '',
						),
					),
				),
			),
		);

		$skins_group = array(
			'circle' => array(
				'label'   => __( 'Circle', 'premium-addons-pro' ),
				'options' => array(
					'circle-1' => __( 'Slide', 'premium-addons-pro' ),
					'circle-2' => __( 'Dual Swap', 'premium-addons-pro' ),
					'circle-4' => __( 'Vertical Swap', 'premium-addons-pro' ),
					'circle-3' => __( 'Stretch Toggle', 'premium-addons-pro' ),
					'circle-5' => __( 'Elastic Toggle', 'premium-addons-pro' ),
					'circle-6' => __( 'Icon Toggle', 'premium-addons-pro' ),
				),
			),
			'square' => array(
				'label'   => __( 'Square', 'premium-addons-pro' ),
				'options' => array(
					'square-1' => __( 'Slide', 'premium-addons-pro' ),
					'square-3' => __( 'Dual Swap', 'premium-addons-pro' ),
					'square-4' => __( 'Vertical Swap', 'premium-addons-pro' ),
					'square-2' => __( '3D Flip', 'premium-addons-pro' ),
					'square-5' => __( 'Scale Swap', 'premium-addons-pro' ),
				),
			),
		);

		$this->start_controls_section(
			'premium_content_toggle_headings_section',
			array(
				'label' => __( 'Toggle', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'type',
			array(
				'label'        => __( 'Type', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SELECT,
				'prefix_class' => 'pa-ct__',
				'render_type'  => 'template',
				'options'      => array(
					'switcher'      => 'Switcher',
					'button'        => 'Button',
					'nested_toggle' => 'Nested Toggle',
				),
				'default'      => 'switcher',
			)
		);

		$this->add_control(
			'switcher_presets',
			array(
				'label'        => __( 'Effect', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SELECT,
				'groups'       => $skins_group,
				'default'      => 'circle-1',
				'prefix_class' => 'pa-ct__',
				'render_type'  => 'template',
				'condition'    => array(
					'type' => 'switcher',
				),
			)
		);

		$this->add_control(
			'btn_presets',
			array(
				'label'        => __( 'Presets', 'premium-addons-pro' ),
				'type'         => Controls_Manager::VISUAL_CHOICE,
				'label_block'  => true,
				'options'      => array(
					'preset-1' => array(
						'title' => __( 'Preset 1 ', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-1.svg',
					),
					'preset-2' => array(
						'title' => __( 'Preset 2 ', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-2.svg',
					),
					'preset-3' => array(
						'title' => __( 'Preset 3', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-3.svg',
					),
					'preset-4' => array(
						'title' => __( 'Preset 4', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-4.svg',
					),
					'preset-5' => array(
						'title' => __( 'Preset 5', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-5.svg',
					),
				),
				'default'      => 'preset-1',
				'columns'      => 2,
				'render_type'  => 'template',
				'prefix_class' => 'pa-ct__',
				'condition'    => array(
					'type' => 'button',
				),
			)
		);

		$this->add_control(
			'nested_presets',
			array(
				'label'        => __( 'Presets', 'premium-addons-pro' ),
				'type'         => Controls_Manager::VISUAL_CHOICE,
				'label_block'  => true,
				'options'      => array(
					'preset-6' => array(
						'title' => __( 'Preset 1', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-6.svg',
					),
					'preset-7' => array(
						'title' => __( 'Preset 2', 'premium-addons-pro' ),
						'image' => $presets_url . 'preset-7.svg',
					),
				),
				'default'      => 'preset-6',
				'columns'      => 2,
				'render_type'  => 'template',
				'prefix_class' => 'pa-ct__',
				'condition'    => array(
					'type' => 'nested_toggle',
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_heading_layout',
			array(
				'label'       => __( 'Display', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SELECT,
				'render_type' => 'template',
				'options'     => array(
					'no'  => __( 'Inline', 'premium-addons-pro' ),
					'yes' => __( 'Block', 'premium-addons-pro' ),
				),
				'default'     => 'no',
				'conditions'  => $show_labels_conds,
				'selectors'   => array(
					'{{WRAPPER}}' => '--pa-content-toggle-stack: {{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_headings_alignment',
			array(
				'label'     => __( 'Alignment', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'separator' => 'before',
				'options'   => array(
					'flex-start' => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => __( 'Center', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'toggle'    => false,
				'default'   => 'center',
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__switcher-container' => 'align-self: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_label_content_tab() {

		$show_labels_conds = array(
			'relation' => 'or',
			'terms'    => array(
				array(
					'name'     => 'premium_content_toggle_heading_one',
					'operator' => '!==',
					'value'    => '',
				),
				array(
					'name'     => 'premium_content_toggle_heading_two',
					'operator' => '!==',
					'value'    => '',
				),
			),
		);

		$has_icons_cond = array(
			'relation' => 'or',
			'terms'    => array(
				array(
					'name'     => 'flabel_icon[value]',
					'operator' => '!==',
					'value'    => '',
				),
				array(
					'name'     => 'slabel_icon[value]',
					'operator' => '!==',
					'value'    => '',
				),
			),
		);

		$normal_icon_cond = array(
			'relation' => 'or',
			'terms'    => array(
				array(
					// 'name'  => 'type',
					// 'value' => 'button',
					'terms' => array(
						array(
							'name'  => 'type',
							'value' => 'button',
						),
						array(
							'name'     => 'btn_presets',
							'operator' => '!==',
							'value'    => 'preset-6',
						),
					),
				),
				array(
					'terms' => array(
						array(
							'name'  => 'type',
							'value' => 'switcher',
						),
						array(
							'name'     => 'switcher_presets',
							'operator' => '!==',
							'value'    => 'circle-6',
						),
					),
				),
			),
		);

		$this->start_controls_section(
			'pa_labels_section',
			array(
				'label' => __( 'Labels', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_heading_one',
			array(
				'label'   => __( 'Primary Label', 'premium-addons-pro' ),
				'default' => __( 'Content #1', 'premium-addons-pro' ),
				'type'    => Controls_Manager::TEXT,
				'dynamic' => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'flabel_icon',
			array(
				'label'       => __( 'Icon', 'premium-addons-pro' ),
				'type'        => Controls_Manager::ICONS,
				'label_block' => false,
				'skin'        => 'inline',
				'conditions'  => $normal_icon_cond,
			)
		);

		$this->add_control(
			'ficon_order',
			array(
				'label'      => __( 'Icon Order', 'premium-addons-pro' ),
				'type'       => Controls_Manager::CHOOSE,
				'options'    => array(
					'-1' => array(
						'title' => __( 'Before', 'premium-addons-pro' ),
						'icon'  => is_rtl() ? 'eicon-order-end' : 'eicon-order-start',
					),
					'2'  => array(
						'title' => __( 'After', 'premium-addons-pro' ),
						'icon'  => is_rtl() ? 'eicon-order-start' : 'eicon-order-end',
					),
				),
				'default'    => '-1',
				'toggle'     => false,
				'conditions' => array(
					'terms' => array(
						array(
							'name'     => 'premium_content_toggle_heading_one',
							'operator' => '!==',
							'value'    => '',
						),
						array(
							'name'     => 'flabel_icon[value]',
							'operator' => '!==',
							'value'    => '',
						),
						$normal_icon_cond,
					),
				),
				'selectors'  => array(
					// '{{WRAPPER}} .pa-ct__flabel-wrapper .pa-ct__icon' => 'order: {{VALUE}}',
					'{{WRAPPER}} .pa-ct__flabel-wrapper svg, {{WRAPPER}} .pa-ct__flabel-wrapper i' => 'order: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'flabel_toggle_icon',
			array(
				'label'                  => __( 'Icon', 'premium-addons-pro' ),
				'type'                   => Controls_Manager::ICONS,
				'label_block'            => false,
				'skin'                   => 'inline',
				'exclude_inline_options' => 'none',
				'default'                => array(
					'value'   => 'fas fa-sun',
					'library' => 'fa-solid',
				),
				'condition'              => array(
					'type'             => 'switcher',
					'switcher_presets' => 'circle-6',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_heading_two',
			array(
				'label'     => __( 'Secondary Label', 'premium-addons-pro' ),
				'default'   => __( 'Content #2', 'premium-addons-pro' ),
				'type'      => Controls_Manager::TEXT,
				'separator' => 'before',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'slabel_icon',
			array(
				'label'       => __( 'Icon', 'premium-addons-pro' ),
				'type'        => Controls_Manager::ICONS,
				'label_block' => false,
				'skin'        => 'inline',
				'conditions'  => $normal_icon_cond,
			)
		);

		$this->add_control(
			'sicon_order',
			array(
				'label'      => __( 'Icon Order', 'premium-addons-pro' ),
				'type'       => Controls_Manager::CHOOSE,
				'options'    => array(
					'-1' => array(
						'title' => __( 'Before', 'premium-addons-pro' ),
						'icon'  => is_rtl() ? 'eicon-order-end' : 'eicon-order-start',
					),
					'2'  => array(
						'title' => __( 'After', 'premium-addons-pro' ),
						'icon'  => is_rtl() ? 'eicon-order-start' : 'eicon-order-end',
					),
				),
				'default'    => '2',
				'toggle'     => false,
				'conditions' => array(
					'terms' => array(
						array(
							'name'     => 'premium_content_toggle_heading_two',
							'operator' => '!==',
							'value'    => '',
						),
						array(
							'name'     => 'slabel_icon[value]',
							'operator' => '!==',
							'value'    => '',
						),
						$normal_icon_cond,
					),
				),
				'selectors'  => array(
					// '{{WRAPPER}} .pa-ct__slabel-wrapper .pa-ct__icon' => 'order: {{VALUE}}',
					'{{WRAPPER}} .pa-ct__slabel-wrapper svg, {{WRAPPER}} .pa-ct__slabel-wrapper i' => 'order: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'slabel_toggle_icon',
			array(
				'label'                  => __( 'Icon', 'premium-addons-pro' ),
				'type'                   => Controls_Manager::ICONS,
				'label_block'            => false,
				'skin'                   => 'inline',
				'exclude_inline_options' => 'none',
				'default'                => array(
					'value'   => 'fas fa-moon',
					'library' => 'fa-solid',
				),
				'condition'              => array(
					'type'             => 'switcher',
					'switcher_presets' => 'circle-6',
				),
			)
		);

		$this->add_responsive_control(
			'icon_size',
			array(
				'label'      => __( 'Icon Size', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'separator'  => 'before',
				'size_units' => array( 'px', 'custom' ),
				'selectors'  => array(
					// '{{WRAPPER}} i.pa-ct__icon, {{WRAPPER}} .pa-ct__toggle-icon i'   => 'font-size: {{SIZE}}{{UNIT}}',
					// '{{WRAPPER}} svg.pa-ct__icon, {{WRAPPER}} .pa-ct__toggle-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .pa-ct__label-wrapper i, {{WRAPPER}} .pa-ct__toggle-icon i' => 'font-size: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .pa-ct__label-wrapper svg, {{WRAPPER}} .pa-ct__toggle-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
				),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'terms' => array(
								array(
									'name'  => 'type',
									'value' => 'switcher',
								),
								array(
									'name'  => 'switcher_presets',
									'value' => 'circle-6',
								),
							),
						),
						$has_icons_cond,
					),
				),
			)
		);

		$this->add_responsive_control(
			'icon_spacing',
			array(
				'label'      => __( 'Icon Spacing', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__label-wrapper' => 'gap: {{SIZE}}px;',
				),
				'conditions' => array(
					'terms' => array(
						$has_icons_cond,
						$normal_icon_cond,
						$show_labels_conds,
					),
				),
			),
		);

		$this->add_responsive_control(
			'premium_content_toggle_switcher_headings_spacing',
			array(
				'label'      => __( 'Switcher Spacing', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'separator'  => 'before',
				'size_units' => array( 'px', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__switcher-container' => 'gap: {{SIZE}}{{UNIT}};',
				),
				// 'conditions' => $show_labels_conds,
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'type',
							'value' => 'switcher',
						),
						array(
							'name'  => 'type',
							'value' => 'button',
						),
					),
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_headings_size',
			array(
				'label'      => __( 'HTML Tag', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SELECT,
				'separator'  => 'before',
				'options'    => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'span' => 'Span',
				),
				'default'    => 'h3',
				'conditions' => $show_labels_conds,
			)
		);

		$effects_group = array(
			'none'     => array(
				'label'   => __( 'Default', 'premium-addons-pro' ),
				'options' => array(
					'' => __( 'None', 'premium-addons-pro' ),
				),
			),
			'active'   => array(
				'label'   => __( 'Active Label', 'premium-addons-pro' ),
				'options' => array(
					'line1'            => __( 'Curly', 'premium-addons-pro' ),
					'underline'        => __( 'Underline', 'premium-addons-pro' ),
					'line5'            => __( 'Double Underline', 'premium-addons-pro' ),
					'line4'            => __( 'Wave Line', 'premium-addons-pro' ),
					'circle'           => __( 'Circle', 'premium-addons-pro' ),
					'h-underline'      => __( 'Hand-drawn Underline', 'premium-addons-pro' ),
					'underline-zigzag' => __( 'Underline Zigzag', 'premium-addons-pro' ),
				),
			),
			'inactive' => array(
				'label'   => __( 'Inactive Label', 'premium-addons-pro' ),
				'options' => array(
					'line2'    => __( 'Strikethrough', 'premium-addons-pro' ),
					'diagonal' => __( 'Diagonal', 'premium-addons-pro' ),
					'x'        => __( 'X', 'premium-addons-pro' ),
				),
			),
		);

		$this->add_control(
			'pa_label_effects',
			array(
				'label'       => __( 'Effects', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'groups'      => $effects_group,
				'separator'   => 'before',
				'render_type' => 'template',
				'label_block' => true,
				'condition'   => array(
					'type' => 'switcher',
				),
			)
		);

		$this->add_control(
			'underline_color',
			array(
				'label'     => __( 'First Line Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000',
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__flabel-wrapper .premium-btn-svg,
					{{WRAPPER}} .pa-ct__switcher-container .pa-ct__flabel-wrapper.pa-ct__active-label:not(.premium-button-diagonal):not(.premium-button-x) svg.outline-svg,
					{{WRAPPER}} .pa-ct__switcher-container .pa-ct__flabel-wrapper.premium-button-diagonal svg.outline-svg, {{WRAPPER}} .pa-ct__switcher-container .pa-ct__flabel-wrapper.premium-button-x svg.outline-svg' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__flabel-wrapper.premium-button-line2::before, {{WRAPPER}} .pa-ct__flabel-wrapper.premium-button-line4::before, {{WRAPPER}} .pa-ct__flabel-wrapper.premium-button-line5::before, {{WRAPPER}} .pa-ct__flabel-wrapper.premium-button-line5::after, {{WRAPPER}} .pa-ct__flabel-wrapper.premium-button-underline::after, {{WRAPPER}} .pa-ct__flabel-wrapper.premium-button-line7::before' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'pa_label_effects!' => '',
					'type'              => 'switcher',
				),
			)
		);

		$this->add_control(
			'sec_underline_color',
			array(
				'label'     => __( 'Second Line Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#000',
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__slabel-wrapper .premium-btn-svg,
					{{WRAPPER}} .pa-ct__switcher-container .pa-ct__slabel-wrapper.pa-ct__active-label:not(.premium-button-diagonal):not(.premium-button-x) svg.outline-svg,
					{{WRAPPER}} .pa-ct__switcher-container .pa-ct__slabel-wrapper.premium-button-diagonal svg.outline-svg, {{WRAPPER}} .pa-ct__switcher-container .pa-ct__slabel-wrapper.premium-button-x svg.outline-svg' => 'stroke: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__slabel-wrapper.premium-button-line2::before, {{WRAPPER}} .pa-ct__slabel-wrapper.premium-button-line4::before, {{WRAPPER}} .pa-ct__slabel-wrapper.premium-button-line5::before, {{WRAPPER}} .pa-ct__slabel-wrapper.premium-button-line5::after, {{WRAPPER}} .pa-ct__slabel-wrapper.premium-button-underline::after, {{WRAPPER}} .pa-ct__slabel-wrapper.premium-button-line7::before' => 'background-color: {{VALUE}};',
				),
				'condition' => array(
					'pa_label_effects!' => '',
					'type'              => 'switcher',
				),
			)
		);

		$this->add_control(
			'pa_line_stroke_width',
			array(
				'label'       => __( 'Line Thickness', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'custom' ),
				'label_block' => true,
				'selectors'   => array(
					'{{WRAPPER}} .pa-ct__switcher-container .pa-ct__active-label:not(.premium-button-diagonal):not(.premium-button-x) svg.outline-svg,
					 {{WRAPPER}} .pa-ct__switcher-container .premium-button-diagonal svg.outline-svg, {{WRAPPER}} .pa-ct__switcher-container .premium-button-x svg.outline-svg'   => 'stroke-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .premium-button-underline::after'   => 'height: {{SIZE}}{{UNIT}};',
				),
				'condition'   => array(
					'pa_label_effects' => array( 'circle', 'diagonal', 'x', 'underline-zigzag', 'h-underline' ),
					'type'             => 'switcher',
				),
			)
		);

		$this->add_control(
			'pa_anim_speed',
			array(
				'label'       => __( 'Animation Speed (Sec)', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 's' ),
				'render_type' => 'template',
				'range'       => array(
					's' => array(
						'min'  => 0,
						'max'  => 50,
						'step' => 0.1,
					),
				),
				'label_block' => true,
				'selectors'   => array(
					'{{WRAPPER}}  .pa-ct__switcher-container svg.outline-svg path '   => 'animation-duration: {{SIZE}}s !important;',
				),
				'condition'   => array(
					'pa_label_effects' => array( 'circle', 'diagonal', 'x', 'underline-zigzag', 'h-underline' ),
					'type'             => 'switcher',
				),
			)
		);

		$this->add_responsive_control(
			'line_height',
			array(
				'label'     => __( 'Line Height (PX)', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'pa_label_effects' => array( 'line2', 'underline' ),
					'type'             => 'switcher',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-button-line2::before, {{WRAPPER}} .premium-button-underline::after' => 'height: {{SIZE}}px',
				),
			)
		);

		$this->add_responsive_control(
			'line_v_position',
			array(
				'label'     => __( 'Line Vertical Position (%)', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'condition' => array(
					'pa_label_effects' => array( 'line1', 'line2', 'underline' ),
					'type'             => 'switcher',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-btn-line-wrap, {{WRAPPER}} .premium-button-line2::before, {{WRAPPER}} .premium-button-underline::after' => 'top: {{SIZE}}%',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_inner_labels_tab() {

		$this->start_controls_section(
			'pa_inner_labels_sec',
			array(
				'label'     => __( 'Nested Toggle', 'premium-addons-pro' ),
				'condition' => array(
					'type' => 'nested_toggle',
				),
			)
		);

		$this->add_control(
			'finner_label',
			array(
				'label'     => __( 'First Label', 'premium-addons-pro' ),
				'default'   => __( 'Label #1', 'premium-addons-pro' ),
				'type'      => Controls_Manager::TEXT,
				'separator' => 'before',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_control(
			'sinner_label',
			array(
				'label'     => __( 'Second Label', 'premium-addons-pro' ),
				'default'   => __( 'Label #2', 'premium-addons-pro' ),
				'type'      => Controls_Manager::TEXT,
				'separator' => 'before',
				'dynamic'   => array(
					'active' => true,
				),
			)
		);

		$this->add_inner_content_tab();

		$this->end_controls_section();
	}

	private function add_fcontent_tab() {

		$this->start_controls_section(
			'premium_content_toggle_first_content_section',
			array(
				'label' => __( 'Primary Content', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_tools',
			array(
				'label'   => __( 'Type', 'premium-addons-pro' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'text_editor'         => __( 'Text Editor', 'premium-addons-pro' ),
					'elementor_templates' => __( 'Elementor Template', 'premium-addons-pro' ),
				),
				'default' => 'text_editor',
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_text',
			array(
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'Donec id elit non mi porta gravida at eget metus. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor. Cras mattis consectetur purus sit amet fermentum. Nullam id dolor id nibh ultricies vehicula ut id elit. Donec id elit non mi porta gravida at eget metus.',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'premium_content_toggle_first_content_tools'  => 'text_editor',
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_first_content_alignment',
			array(
				'label'     => __( 'Alignment', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'left'    => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => __( 'Center', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => __( 'Justify', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'condition' => array(
					'premium_content_toggle_first_content_tools'  => 'text_editor',
				),
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__monthly-text' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'live_temp_content',
			array(
				'label'       => __( 'Template Title', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'ai'          => array(
					'active' => false,
				),
				'classes'     => 'premium-live-temp-title control-hidden',
				'label_block' => true,
				'condition'   => array(
					'premium_content_toggle_first_content_tools'  => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_templates_live',
			array(
				'type'        => Controls_Manager::BUTTON,
				'label_block' => true,
				'button_type' => 'default papro-btn-block',
				'text'        => __( 'Create / Edit Template', 'premium-addons-pro' ),
				'event'       => 'createLiveTemp',
				'condition'   => array(
					'premium_content_toggle_first_content_tools'  => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_templates',
			array(
				'label'       => __( 'OR Select Existing Template', 'premium-addons-pro' ),
				'type'        => Premium_Post_Filter::TYPE,
				'classes'     => 'premium-live-temp-label',
				'label_block' => true,
				'multiple'    => false,
				'source'      => 'elementor_library',
				'condition'   => array(
					'premium_content_toggle_first_content_tools'  => 'elementor_templates',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_scontent_tab() {

		$this->start_controls_section(
			'premium_content_toggle_second_content_section',
			array(
				'label'      => __( 'Secondary Content', 'premium-addons-pro' ),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'type',
							'value' => 'switcher',
						),
						array(
							'name'  => 'type',
							'value' => 'button',
						),
					),
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_tools',
			array(
				'label'   => __( 'Type', 'premium-addons-pro' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'text_editor'         => __( 'Text Editor', 'premium-addons-pro' ),
					'elementor_templates' => __( 'Elementor Template', 'premium-addons-pro' ),
				),
				'default' => 'text_editor',
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_text',
			array(
				'label'       => __( 'Text Editor', 'premium-addons-pro' ),
				'type'        => Controls_Manager::WYSIWYG,
				'dynamic'     => array( 'active' => true ),
				'default'     => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
				'label_block' => true,
				'condition'   => array(
					'premium_content_toggle_second_content_tools'  => 'text_editor',
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_second_content_alignment',
			array(
				'label'     => __( 'Alignment', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'left'    => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => __( 'Center', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => __( 'Justify', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'condition' => array(
					'premium_content_toggle_second_content_tools'  => 'text_editor',
				),
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__yearly-text' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'live_temp_content_extra',
			array(
				'label'       => __( 'Template Title', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'ai'          => array(
					'active' => false,
				),
				'classes'     => 'premium-live-temp-title control-hidden',
				'label_block' => true,
				'condition'   => array(
					'premium_content_toggle_second_content_tools'  => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_templates_live',
			array(
				'type'        => Controls_Manager::BUTTON,
				'label_block' => true,
				'button_type' => 'default papro-btn-block',
				'text'        => __( 'Create / Edit Template', 'premium-addons-pro' ),
				'event'       => 'createLiveTemp',
				'condition'   => array(
					'premium_content_toggle_second_content_tools'  => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_templates',
			array(
				'label'       => __( 'OR Select Existing Template', 'premium-addons-pro' ),
				'type'        => Premium_Post_Filter::TYPE,
				'classes'     => 'premium-live-temp-label',
				'label_block' => true,
				'multiple'    => false,
				'source'      => 'elementor_library',
				'condition'   => array(
					'premium_content_toggle_second_content_tools'  => 'elementor_templates',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_inner_content_tab() {

		// $this->start_controls_section(
		// 'pa_inner_content_sec',
		// array(
		// 'label'     => __( 'Inner Content', 'premium-addons-pro' ),
		// 'condition' => array(
		// 'type'        => 'button',
		// 'btn_presets' => 'preset-6',
		// ),
		// )
		// );

		$this->add_control(
			'inner_cont_type',
			array(
				'label'     => __( 'Content Type', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SELECT,
				'separator' => 'before',
				'options'   => array(
					'text_editor'         => __( 'Text Editor', 'premium-addons-pro' ),
					'elementor_templates' => __( 'Elementor Template', 'premium-addons-pro' ),
				),
				'default'   => 'text_editor',
			)
		);

		$this->add_control(
			'finner_content',
			array(
				'label'       => __( 'First Content', 'premium-addons-pro' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'Sed ornare diam eget lectus pretium, vitae auctor justo elementum. Nulla cursus at lectus eu varius. Cras at tortor bibendum, consectetur dui eget, lobortis urna. Suspendisse at turpis in est maximus tincidunt. Etiam rutrum et tellus id elementum.',
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'inner_cont_type' => 'text_editor',
				),
			)
		);

		$this->add_responsive_control(
			'fcontent_align',
			array(
				'label'     => __( 'Alignment', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'left'    => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => __( 'Center', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => __( 'Justify', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'condition' => array(
					'inner_cont_type' => 'text_editor',
				),
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__inner-content-wrapper .pa-ct__inner-monthly' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'finner_heading',
			array(
				'label'     => __( 'First Content', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'pa_finner_live_temp_content',
			array(
				'label'       => __( 'Template Title', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'premium-live-temp-title control-hidden',
				'label_block' => true,
				'condition'   => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'pa_finner_temp_live',
			array(
				'type'        => Controls_Manager::BUTTON,
				'label_block' => true,
				'button_type' => 'default papro-btn-block',
				'text'        => __( 'Create / Edit Template', 'premium-addons-pro' ),
				'event'       => 'createLiveTemp',
				'condition'   => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'pa_finner_temp',
			array(
				'label'       => __( 'OR Select Existing Template', 'premium-addons-pro' ),
				'type'        => Premium_Post_Filter::TYPE,
				'classes'     => 'premium-live-temp-label',
				'label_block' => true,
				'multiple'    => false,
				'source'      => 'elementor_library',
				'condition'   => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'sinner_heading',
			array(
				'label'     => __( 'Second Content', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'sinner_content',
			array(
				'label'       => __( 'Second Content', 'premium-addons-pro' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'In porttitor pellentesque est ac pharetra. Praesent pharetra urna diam, quis gravida velit tincidunt id. Curabitur in nunc ac nisi facilisis ultrices. Nunc rhoncus dui eu dolor venenatis imperdiet. Sed rutrum ullamcorper libero, at porta nunc iaculis eget.',
				'label_block' => true,
				'separator'   => 'before',
				'dynamic'     => array( 'active' => true ),
				'condition'   => array(
					'inner_cont_type' => 'text_editor',
				),
			)
		);

		$this->add_responsive_control(
			'scontent_align',
			array(
				'label'     => __( 'Alignment', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => array(
					'left'    => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center'  => array(
						'title' => __( 'Center', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'   => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-right',
					),
					'justify' => array(
						'title' => __( 'Justify', 'premium-addons-pro' ),
						'icon'  => 'eicon-text-align-justify',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__inner-content-wrapper .pa-ct__inner-yearly' => 'text-align: {{VALUE}};',
				),
				'condition' => array(
					'inner_cont_type' => 'text_editor',
				),
			)
		);

		$this->add_control(
			'pa_sinner_live_temp_content',
			array(
				'label'       => __( 'Template Title', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'classes'     => 'premium-live-temp-title control-hidden',
				'label_block' => true,
				'condition'   => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'pa_sinner_temp_live',
			array(
				'type'        => Controls_Manager::BUTTON,
				'label_block' => true,
				'button_type' => 'default papro-btn-block',
				'text'        => __( 'Create / Edit Template', 'premium-addons-pro' ),
				'event'       => 'createLiveTemp',
				'condition'   => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		$this->add_control(
			'pa_sinner_temp',
			array(
				'label'       => __( 'OR Select Existing Template', 'premium-addons-pro' ),
				'type'        => Premium_Post_Filter::TYPE,
				'classes'     => 'premium-live-temp-label',
				'label_block' => true,
				'multiple'    => false,
				'source'      => 'elementor_library',
				'condition'   => array(
					'inner_cont_type' => 'elementor_templates',
				),
			)
		);

		// $this->end_controls_section();
	}

	private function add_display_option_tab() {

		$this->start_controls_section(
			'premium_content_toggle_content_display',
			array(
				'label' => __( 'Display Options', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_animation',
			array(
				'label'   => __( 'Animation', 'premium-addons-pro' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'none'     => __( 'None', 'premium-addons-pro' ),
					'opacity'  => __( 'Fade', 'premium-addons-pro' ),
					'fade'     => __( 'Slide', 'premium-addons-pro' ),
					'zoom-in'  => __( 'Zoom In', 'premium-addons-pro' ),
					'zoom-out' => __( 'Zoom Out', 'premium-addons-pro' ),
				),
				'default' => 'opacity',
			)
		);

		$this->add_control(
			'premium_content_toggle_fade_dir',
			array(
				'label'     => __( 'Direction', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'top'    => array(
						'title' => __( 'Top', 'premium-addons-pro' ),
						'icon'  => 'eicon-arrow-down',
					),
					'right'  => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-arrow-left',
					),
					'bottom' => array(
						'title' => __( 'Bottom', 'premium-addons-pro' ),
						'icon'  => 'eicon-arrow-up',
					),
					'left'   => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-arrow-right',
					),
				),
				'default'   => 'top',
				'condition' => array(
					'premium_content_toggle_animation' => 'fade',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_switcher_style_tab() {

		$this->start_controls_section(
			'premium_content_toggle_switcher_headings_container_style_section',
			array(
				'label'     => __( 'Toggle', 'premium-addons-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'type' => 'switcher',
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_switch_size',
			array(
				'label'     => __( 'Size', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'size' => 15,
				),
				'range'     => array(
					'px' => array(
						'min' => 1,
						'max' => 40,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__switch-wrapper' => 'font-size: {{SIZE}}px',
				),

			)
		);

		$this->add_control(
			'pa_ctrl_heading',
			array(
				'label'     => __( 'Controller', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			PAPRO_Helper::get_bg_control_class()::get_type(),
			array(
				'name'           => 'premium_content_toggle_switch_normal_background_color',
				'types'          => array( 'classic', 'gradient' ),
				'fields_options' => array(
					'background' => array(
						'label' => __( 'State 1 Color', 'premium-addons-pro' ),
					),
				),
				'selector'       => '{{WRAPPER}} .pa-ct__switch-control:before, {{WRAPPER}}.pa-ct__square-2 .pa-ct__switch-control span',
			)
		);

		$this->add_group_control(
			PAPRO_Helper::get_bg_control_class()::get_type(),
			array(
				'name'           => 'premium_content_toggle_switch_active_background_color',
				'types'          => array( 'classic', 'gradient' ),
				'fields_options' => array(
					'background' => array(
						'label' => __( 'State 2 Color', 'premium-addons-pro' ),
					),
				),
				'selector'       => '
					{{WRAPPER}}.pa-ct__circle-1 .pa-ct__switch:checked + .pa-ct__switch-control:before,
					{{WRAPPER}}.pa-ct__circle-3 .pa-ct__switch:checked + .pa-ct__switch-control:before,
					{{WRAPPER}}.pa-ct__square-1 .pa-ct__switch:checked + .pa-ct__switch-control:before,
					{{WRAPPER}}.pa-ct__circle-5 .pa-ct__switch:checked + .pa-ct__switch-control:before,
					{{WRAPPER}}.pa-ct__circle-6 .pa-ct__switch:checked + .pa-ct__switch-control:before,

					{{WRAPPER}}.pa-ct__circle-2 .pa-ct__switch:checked + .pa-ct__switch-control:after,
					{{WRAPPER}}.pa-ct__circle-4 .pa-ct__switch:checked + .pa-ct__switch-control:after,
					{{WRAPPER}}.pa-ct__square-3 .pa-ct__switch:checked + .pa-ct__switch-control:after,
					{{WRAPPER}}.pa-ct__square-4 .pa-ct__switch:checked + .pa-ct__switch-control:after,
					{{WRAPPER}}.pa-ct__square-5 .pa-ct__switch:checked + .pa-ct__switch-control:after,

					{{WRAPPER}}.pa-ct__square-2 .pa-ct__switch:checked + .pa-ct__switch-control span',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'label'    => __( 'Shadow', 'premium-addons-pro' ),
				'name'     => 'premium_content_toggle_switch_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__switch-control:before, {{WRAPPER}} .pa-ct__switch-control:after',
			)
		);

		$this->add_control(
			'controller_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__switch-control:before, {{WRAPPER}} .pa-ct__switch-control:after' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
				'condition'  => array(
					'type'             => 'switcher',
					'switcher_presets' => array( 'circle-1', 'square-1', 'square-2', 'square-3', 'square-4' ),
				),
			)
		);

		$this->add_control(
			'pa_ctrl_bg_heading',
			array(
				'label'     => __( 'Background', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			PAPRO_Helper::get_bg_control_class()::get_type(),
			array(
				'name'           => 'pa_inactive_switcher_bg_color',
				'types'          => array( 'classic', 'gradient' ),
				'fields_options' => array(
					'background' => array(
						'label' => __( 'State 1 Color', 'premium-addons-pro' ),
					),
				),
				'selector'       => '{{WRAPPER}} .pa-ct__switch-control',
			)
		);

		$this->add_group_control(
			PAPRO_Helper::get_bg_control_class()::get_type(),
			array(
				'name'           => 'premium_content_toggle_fieldset_active_background_color',
				'types'          => array( 'classic', 'gradient' ),
				'fields_options' => array(
					'background' => array(
						'label' => __( 'State 2 Color', 'premium-addons-pro' ),
					),
				),
				'selector'       => '{{WRAPPER}} .pa-ct__switch:checked + .pa-ct__switch-control',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'label'    => __( 'Shadow', 'premium-addons-pro' ),
				'name'     => 'premium_content_toggle_fieldset_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__switch-control',
			)
		);

		$this->add_control(
			'switcher_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__switch-control' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
				'condition'  => array(
					'type'             => 'switcher',
					'switcher_presets' => array( 'circle-1', 'square-1', 'square-2', 'square-3', 'square-4' ),
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_label_style_tab() {
		$label_txt_conds = array(
			'relation' => 'or',
			'terms'    => array(
				array(
					'name'     => 'premium_content_toggle_heading_one',
					'operator' => '!==',
					'value'    => '',
				),
				array(
					'name'     => 'premium_content_toggle_heading_two',
					'operator' => '!==',
					'value'    => '',
				),
			),
		);

		$this->start_controls_section(
			'pa_label_style_section',
			array(
				'label'      => __( 'Labels', 'premium-addons-pro' ),
				'tab'        => Controls_Manager::TAB_STYLE,
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'type',
							'value' => 'switcher',
						),
						array(
							'name'  => 'type',
							'value' => 'button',
						),
					),
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'       => 'premium_content_toggle_left_heading_typhography',
				'selector'   => '{{WRAPPER}} .pa-ct__label-wrapper > .pa-ct__label',
				'conditions' => $label_txt_conds,
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'       => 'premium_content_toggle_left_heading_text_shadow',
				'selector'   => '{{WRAPPER}} .pa-ct__label-wrapper > .pa-ct__label',
				'conditions' => $label_txt_conds,
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'premium_content_toggle_left_heading_border',
				'selector' => '{{WRAPPER}} .pa-ct__label-wrapper',
				'exclude'  => array( 'color' ),
			)
		);

		$this->start_controls_tabs( 'pa_labels_style_tabs' );
		$this->start_controls_tab(
			'pa_left_label_style_tab',
			array(
				'label' => __( 'Primary Label', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'active_flabel_heading',
			array(
				'label' => __( 'Active', 'premium-addons-pro' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'premium_content_toggle_left_heading_color',
			array(
				'label'      => __( 'Text Color', 'premium-addons-pro' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__active-label .pa-ct__first-label' => 'color: {{VALUE}};',
				),
				'conditions' => $label_txt_conds,
			)
		);

		$this->add_control(
			'flabel_icon_color',
			array(
				'label'     => __( 'Icon Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__flabel-wrapper i, {{WRAPPER}} .pa-ct__ftoggle-icon i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__flabel-wrapper svg, {{WRAPPER}} .pa-ct__ftoggle-icon svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_left_heading_background_color',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}.pa-ct__switcher .pa-ct__active-label.pa-ct__flabel-wrapper, {{WRAPPER}}.pa-ct__button .pa-ct__flabel-wrapper:before' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_left_heading_box_shadow',
				'selector' => '{{WRAPPER}}.pa-ct__switcher .pa-ct__active-label.pa-ct__flabel-wrapper, {{WRAPPER}}.pa-ct__button .pa-ct__flabel-wrapper:before',
			)
		);

		$this->add_control(
			'flabel_border_color',
			array(
				'label'     => __( 'Border Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__flabel-wrapper' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'premium_content_toggle_left_heading_border_border!' => array( '', 'none' ),
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_left_heading_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__flabel-wrapper' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'type' => 'switcher',
				),
			)
		);

		$this->add_control(
			'inactive_flabel_heading',
			array(
				'label'     => __( 'Inactive', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'inactive_flabel_color',
			array(
				'label'      => __( 'Text Color', 'premium-addons-pro' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__first-label' => 'color: {{VALUE}};',
				),
				'conditions' => $label_txt_conds,
			)
		);

		$this->add_control(
			'ficon_color_inactive',
			array(
				'label'      => __( 'Icon Color', 'premium-addons-pro' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__flabel-wrapper i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__flabel-wrapper svg' => 'fill: {{VALUE}};',
				),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'type',
							'value' => 'button',
						),
						array(
							'terms' => array(
								array(
									'name'  => 'type',
									'value' => 'switcher',
								),
								array(
									'name'     => 'switcher_presets',
									'operator' => '!==',
									'value'    => 'circle-6',
								),
							),
						),
					),
				),
			)
		);

		$this->add_control(
			'flabel_bg_inc',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__flabel-wrapper' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'flabel_box_shadow_inc',
				'selector' => '{{WRAPPER}} .pa-ct__flabel-wrapper:not(.pa-ct__active-label)',
			)
		);

		$this->add_control(
			'flabel_border_color_inc',
			array(
				'label'     => __( 'Border Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__flabel-wrapper:not(.pa-ct__active-label)' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'premium_content_toggle_left_heading_border_border!' => array( '', 'none' ),
				),
			)
		);

		$this->add_control(
			'flabel_border_radius_inc',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__flabel-wrapper:not(.pa-ct__active-label)' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'type' => 'switcher',
				),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'pa_right_label_style_tab',
			array(
				'label' => __( 'Secondary Label', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'active_slabel_heading',
			array(
				'label' => __( 'Active', 'premium-addons-pro' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'premium_content_toggle_right_heading_color',
			array(
				'label'     => __( 'Text Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__active-label .pa-ct__second-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'slabel_icon_color',
			array(
				'label'     => __( 'Icon Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__slabel-wrapper i, {{WRAPPER}} .pa-ct__stoggle-icon i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__slabel-wrapper svg, {{WRAPPER}} .pa-ct__stoggle-icon svg' => 'fill: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_right_heading_background_color',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}}.pa-ct__switcher .pa-ct__active-label.pa-ct__slabel-wrapper, {{WRAPPER}}.pa-ct__button .pa-ct__slabel-wrapper:before' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_right_heading_box_shadow',
				'selector' => '{{WRAPPER}}.pa-ct__switcher .pa-ct__active-label.pa-ct__slabel-wrapper, {{WRAPPER}}.pa-ct__button .pa-ct__slabel-wrapper:before',
			)
		);

		$this->add_control(
			'slabel_border_color',
			array(
				'label'     => __( 'Border Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__slabel-wrapper' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'premium_content_toggle_left_heading_border_border!' => array( '', 'none' ),
				),
			)
		);

		$this->add_control(
			'premium_content_toggle_right_heading_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__active-label.pa-ct__slabel-wrapper' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'type' => 'switcher',
				),
			)
		);

		$this->add_control(
			'inactive_slabel_heading',
			array(
				'label'     => __( 'Inactive', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'inactive_second_label_color',
			array(
				'label'     => __( 'Text Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__second-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'slabel_icon_color_inc',
			array(
				'label'      => __( 'Icon Color', 'premium-addons-pro' ),
				'type'       => Controls_Manager::COLOR,
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__slabel-wrapper i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__slabel-wrapper svg' => 'fill: {{VALUE}};',
				),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'type',
							'value' => 'button',
						),
						array(
							'terms' => array(
								array(
									'name'  => 'type',
									'value' => 'switcher',
								),
								array(
									'name'     => 'switcher_presets',
									'operator' => '!==',
									'value'    => 'circle-6',
								),
							),
						),
					),
				),
			)
		);

		$this->add_control(
			'slabel_bg_inc',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__slabel-wrapper' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'slabel_box_shadow_inc',
				'selector' => '{{WRAPPER}} .pa-ct__slabel-wrapper:not(.pa-ct__active-label)',
			)
		);

		$this->add_control(
			'slabel_border_color_inc',
			array(
				'label'     => __( 'Border Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__slabel-wrapper' => 'border-color: {{VALUE}};',
				),
				'condition' => array(
					'premium_content_toggle_left_heading_border_border!' => array( '', 'none' ),
				),
			)
		);

		$this->add_control(
			'slabel_border_radius_inc',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__slabel-wrapper' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'condition'  => array(
					'type' => 'switcher',
				),
			)
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'premium_content_toggle_left_headings_padding',
			array(
				'label'      => __( 'Padding', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => array( 'px', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__label-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_inner_labels_style_tab() {

		$this->start_controls_section(
			'pa_inner_label_style_section',
			array(
				'label'     => __( 'Labels', 'premium-addons-pro' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'type' => 'nested_toggle',
				),
			)
		);

		$this->add_responsive_control(
			'sw_height',
			array(
				'label'       => __( 'Size', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SLIDER,
				'description' => __( 'Use this to control the switcher size to fit larger text.', 'premium-addons-pro' ),
				'range'       => array(
					'px' => array(
						'min' => 40,
						'max' => 500,
					),
				),
				'size_units'  => array( 'px', 'custom' ),
				'selectors'   => array(
					'{{WRAPPER}} .pa-ct__switcher-container' => 'height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'dot_size',
			array(
				'label'     => __( 'Dot Size', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__outer-container' => '--pa-dot-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'dot_spacing',
			array(
				'label'      => __( 'Dot Spacing', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'separator'  => 'before',
				'size_units' => array( 'px', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__ml-wrapper.pa-has-inner-sw:not(.pa-ct__active-label) .pa-ct__inner-sw-wrapper' => 'gap: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_control(
			'main_label_heading',
			array(
				'label'     => __( 'Main Labels', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typo',
				'selector' => '{{WRAPPER}} .pa-ct__ml-wrapper .pa-ct__mlabel, {{WRAPPER}} .pa-ct__ml-wrapper.pa-has-inner-sw.pa-ct__active-label .pa-ct__inner-label',
			)
		);

		$this->add_control(
			'active_color',
			array(
				'label'     => __( 'Active Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__ml-wrapper.pa-ct__active-label .pa-ct__mlabel' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'inactive_color',
			array(
				'label'     => __( 'Inactive Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__ml-wrapper .pa-ct__mlabel' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pa_main_bg',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__background' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'main_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__background',
			)
		);

		$this->add_control(
			'inner_color_heading',
			array(
				'label'     => __( 'Nested Labels', 'premium-addons-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'inactive_inner_label_typo',
				'selector' => '{{WRAPPER}} .pa-has-inner-sw:not(.pa-ct__active-label) .pa-ct__inner-label',
			)
		);

		$this->add_control(
			'inner_active_color',
			array(
				'label'     => __( 'Active Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__ml-wrapper.pa-ct__active-label .pa-ct__inner-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'inner_inactive_color',
			array(
				'label'     => __( 'Inactive Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-has-inner-sw.pa-ct__active-label.pa-ct__ml-wrapper .pa-ct__inner-label:not(.pa-ct__active)' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_when_inactive_parent',
			array(
				'label'       => __( 'Inactive color (Inactive Parent)', 'premium-addons-pro' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'This is applied on the inner labels when their main label is inactive.', 'premium-addons-pro' ),
				'selectors'   => array(
					'{{WRAPPER}} .pa-ct__ml-wrapper .pa-ct__inner-label' => 'color: {{VALUE}};',
					'{{WRAPPER}} .pa-ct__sw-dot > div' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'override_active_parent_color',
			array(
				'label'       => __( 'Override Active Parent Color', 'premium-addons-pro' ),
				'type'        => Controls_Manager::COLOR,
				'description' => __( 'Overrides the color of the active parent label.', 'premium-addons-pro' ),
				'selectors'   => array(
					'{{WRAPPER}} .pa-ct__ml-wrapper.pa-has-inner-sw.pa-ct__active-label .pa-ct__mlabel' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'pa_inner_bg',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__inner-background' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'inner_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__inner-background',
			)
		);

		$this->add_control(
			'inactive_opacity',
			array(
				'label'     => __( 'Inactive Opacity', 'premium-addons-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'separator' => 'before',
				'step'      => 0.1,
				'min'       => 0,
				'max'       => 1,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__sw-dot, {{WRAPPER}} .pa-ct__ml-wrapper:not(.pa-has-inner-sw) .pa-ct__mlabel, {{WRAPPER}}.pa-ct__preset-6 .pa-ct__inner-label, {{WRAPPER}}.pa-ct__preset-7 .pa-ct__inner-label' => 'opacity: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_content_style_tab() {

		$this->start_controls_section(
			'premium_content_toggle_content_style_section',
			array(
				'label' => __( 'Content', 'premium-addons-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'premium_content_toggle_two_content_height',
			array(
				'label'      => __( 'Height', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%', 'custom' ),
				'range'      => array(
					'px' => array(
						'min'  => 0,
						'step' => 1,
						'max'  => 1000,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__outer-container .pa-ct__content-list > li' => 'min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->start_controls_tabs( 'premium_content_toggle_content_style_tabs' );

		$this->start_controls_tab(
			'premium_content_toggle_first_content_style_tab',
			array(
				'label' => __( 'Primary Content', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_color',
			array(
				'label'     => __( 'Text Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__primary-content .pa-ct__monthly-text, {{WRAPPER}} .pa-ct__primary-content .pa-ct__monthly-text *'   => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'premium_content_toggle_first_content_typhography',
				'selector' => '{{WRAPPER}} .pa-ct__primary-content .pa-ct__monthly-text',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_first_content_text_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__primary-content .pa-ct__monthly-text',
			)
		);

		$this->add_control(
			'f_content_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'separator'       => 'before',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'raw'             => __( 'The above controls are only applicable on <b>Text Editor</b> content.', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_background_color',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'separator' => 'before',
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__primary-content' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'premium_content_toggle_first_content_border',
				'selector' => '{{WRAPPER}} .pa-ct__primary-content',
			)
		);

		$this->add_control(
			'premium_content_toggle_first_content_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__primary-content' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_first_content_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__primary-content',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'premium_content_toggle_second_content_style_tab',
			array(
				'label' => __( 'Secondary/Nested Content', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_color',
			array(
				'label'     => __( 'Text Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__secondary-content .pa-ct__yearly-text, {{WRAPPER}} .pa-ct__secondary-content .pa-ct__yearly-text *, {{WRAPPER}} .pa-ct__secondary-content .pa-ct__inner-content-wrapper > li *'   => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'premium_content_toggle_second_content_typhography',
				'selector' => '{{WRAPPER}} .pa-ct__secondary-content .pa-ct__yearly-text, {{WRAPPER}} .pa-ct__secondary-content .pa-ct__inner-content-wrapper > li',
			)
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_second_content_text_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__secondary-content .pa-ct__yearly-text, {{WRAPPER}} .pa-ct__secondary-content .pa-ct__inner-content-wrapper > li',
			)
		);

		$this->add_control(
			's_content_notice',
			array(
				'type'      => Controls_Manager::RAW_HTML,
				'separator' => 'before',
				'raw'       => __( 'The above controls are only applicable on <b>Text Editor</b> content.', 'premium-addons-pro' ),
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_background_color',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__secondary-content' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'premium_content_toggle_second_content_border',
				'selector' => '{{WRAPPER}} .pa-ct__secondary-content',
			)
		);

		$this->add_control(
			'premium_content_toggle_second_content_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__secondary-content' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_second_content_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__secondary-content',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'premium_content_toggle_contents_margin',
			array(
				'label'      => __( 'Margin', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__content-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_contents_padding',
			array(
				'label'      => __( 'Padding', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__primary-content, {{WRAPPER}} .pa-ct__secondary-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function add_toggle_container_style_tab() {

		$this->start_controls_section(
			'premium_content_toggle_container_style',
			array(
				'label' => __( 'Toggle Container', 'premium-addons-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'premium_content_toggle_switcher_container_background_color',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .pa-ct__switcher-container' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'premium_content_toggle_switcher_container_box_shadow',
				'selector' => '{{WRAPPER}} .pa-ct__switcher-container',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'premium_content_toggle_switcher_container_border',
				'selector' => '{{WRAPPER}} .pa-ct__switcher-container',
			)
		);

		$this->add_control(
			'premium_content_toggle_switcher_container_border_radius',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__switcher-container, {{WRAPPER}}.pa-ct__preset-6 .pa-ct__inner-background, {{WRAPPER}}.pa-ct__preset-6 .pa-ct__background, {{WRAPPER}}.pa-ct__preset-7 .pa-ct__inner-background, {{WRAPPER}}.pa-ct__preset-7 .pa-ct__background' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_switcher_container_padding',
			array(
				'label'      => __( 'Padding', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__switcher-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
				'conditions' => array(
					'relation' => 'or',
					'terms'    => array(
						array(
							'name'  => 'type',
							'value' => 'switcher',
						),
						array(
							'name'  => 'type',
							'value' => 'button',
						),
					),
				),
			)
		);

		$this->add_responsive_control(
			'premium_content_toggle_switcher_container_margin',
			array(
				'label'      => __( 'Margin', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .pa-ct__switcher-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render Content Toggle widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$render_switch = 'switcher' === $settings['type'];

		$has_labels = ! empty( $settings['premium_content_toggle_heading_one'] ) || ! empty( $settings['premium_content_toggle_heading_two'] );

		$label_tag = $has_labels ? PAPRO_Helper::validate_html_tag( $settings['premium_content_toggle_headings_size'] ) : 'span';

		$render_inner_switcher = 'nested_toggle' === $settings['type'];
		?>
			<div class='pa-ct__outer-container'>
				<div class="pa-ct__switcher-container">
					<?php
					if ( ! $render_inner_switcher ) {
						$this->pa_render_fmain_label( $label_tag, $settings );

						if ( $render_switch ) {
							$this->pa_render_switch( $settings );
						}

						$this->pa_render_smain_label( $label_tag, $settings );

					} else {
						$this->pa_render_inner_switcher( $label_tag, $settings );
					}
					?>
				</div>

				<?php $this->pa_render_toggle_content( $settings ); ?>
			</div>
		<?php
	}


	/**
	 * Render Inner Switcher Preset ( preset-6 ).
	 *
	 * @param array $settings Widget settings
	 */
	private function pa_render_inner_switcher( $label_tag, $settings ) {
		$is_reversed = 'preset-7' === $settings['nested_presets'];
		// if true => first label -> inner parent => second label => is active
		?>
			<div class="pa-ct__switcher-inner-wrapper">

				<div class="pa-ct__background"></div>
				<?php
					$this->pa_render_finner_label( $label_tag, $settings, $is_reversed );
					$this->pa_render_sinner_label( $label_tag, $settings, $is_reversed );
				?>
			</div>

		<?php
	}

	private function pa_render_inner_sw( $settings ) {
		$f_label = $settings['finner_label'];
		$s_label = $settings['sinner_label'];
		?>
			<div class="pa-ct__inner-sw-wrapper" role="radiogroup">
				<span class="pa-ct__inner-label pa-ct__active" role="radio" aria-checked="true" tabindex="0"><?php echo wp_kses_post( $f_label ); ?></span>
				<div class="pa-ct__sw-dot"><div></div></div>
				<span class="pa-ct__inner-label" role="radio" aria-checked="false" tabindex="0"><?php echo wp_kses_post( $s_label ); ?></span>
				<div class="pa-ct__inner-background"></div>
			</div>
		<?php
	}

	/**
	 * Render Switcher HTML
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $settings widget settings
	 */
	private function pa_render_switch( $settings ) {
		$preset = $settings['switcher_presets'];
		$this->add_render_attribute( 'pa_switch_wrapper', 'class', 'pa-ct__switch-wrapper' );
		?>
			<div <?php $this->print_render_attribute_string( 'pa_switch_wrapper' ); ?>>
				<label class="pa-ct__switch-label">
					<input class="pa-ct__switch pa-ct__switch-normal elementor-clickable" type="checkbox" name="premium-content-toggle-switch" aria-label="<?php echo esc_attr__( 'Toggle content', 'premium-addons-pro' ); ?>" />
					<span class="pa-ct__switch-control elementor-clickable">
						<?php
						if ( 'square-2' === $preset ) :
							?>
								<span></span>
							<?php
						endif;

						if ( 'circle-6' === $settings['switcher_presets'] ) :
							?>
									<span class="pa-ct__toggle-icon pa-ct__ftoggle-icon">
									<?php
										Icons_Manager::render_icon(
											$settings['flabel_toggle_icon'],
											array(
												// 'class'       => 'pa-ct__toggle-icon',
												'aria-hidden' => 'true',
											)
										);
									?>
									</span>
									<span class="pa-ct__toggle-icon pa-ct__stoggle-icon">
									<?php
										Icons_Manager::render_icon(
											$settings['slabel_toggle_icon'],
											array(
												// 'class'       => 'pa-ct__toggle-icon',
												'aria-hidden' => 'true',
											)
										);
									?>
									</span>
								<?php
							endif;
						?>
					</span>
				</label>
			</div>
		<?php
	}

	/**
	 * Render Toggle Content HTML
	 *
	 * @since 1.0.0
	 * @access private
	 * @param array $settings Widget settings
	 */
	private function pa_render_toggle_content( $settings ) {
		$animation   = 'fade' === $settings['premium_content_toggle_animation'] ? 'fade-' . $settings['premium_content_toggle_fade_dir'] : $settings['premium_content_toggle_animation'];
		$is_inner_sw = 'nested_toggle' === $settings['type'];

		$is_reversed = $is_inner_sw && 'preset-7' === $settings['nested_presets'];

		?>
			<div class="pa-ct__content-container <?php echo esc_attr( $animation ); ?>">
				<ul class="pa-ct__content-list">
					<li data-type="pa-ct__monthly" class="<?php echo esc_attr( $is_reversed ? 'pa-ct__is-visible pa-has-inner-content pa-ct__secondary-content' : 'pa-ct__is-visible pa-ct__primary-content' ); ?> pa-ct__monthly">
						<?php
						if ( $is_inner_sw && $is_reversed ) {
							$this->render_inner_content( $settings );

						} elseif ( 'text_editor' === $settings['premium_content_toggle_first_content_tools'] ) {

								$this->add_render_attribute( 'premium_content_toggle_first_content_text', 'class', 'pa-ct__monthly-text' );
							?>
											<div <?php $this->print_render_attribute_string( 'premium_content_toggle_first_content_text' ); ?>>
										<?php echo $this->parse_text_editor( $settings['premium_content_toggle_first_content_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											</div>
									<?php
						} else {
							$first_template = empty( $settings['premium_content_toggle_first_content_templates'] ) ? $settings['live_temp_content'] : $settings['premium_content_toggle_first_content_templates'];
							?>
									<div class="pa-ct__first-content-item-wrapper">
									<?php echo Helper_Functions::render_elementor_template( $first_template ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								<?php

						}
						?>
					</li>

					<li data-type="pa-ct__yearly" class="<?php echo esc_attr( $is_reversed ? 'pa-ct__is-hidden pa-ct__primary-content' : 'pa-ct__is-hidden pa-has-inner-content pa-ct__secondary-content' ); ?> pa-ct__yearly">
						<?php
						if ( $is_inner_sw && ! $is_reversed ) {
							// Nested toggle preset, not reversed: second label shows the nested toggle.
							$this->render_inner_content( $settings );

						} elseif ( $is_inner_sw && $is_reversed ) {
							// Nested toggle preset, reversed: second slot shows the standalone Primary Content.
							if ( 'text_editor' === $settings['premium_content_toggle_first_content_tools'] ) {

								$this->add_render_attribute( 'premium_content_toggle_first_content_text', 'class', 'pa-ct__monthly-text' );
								?>
									<div <?php $this->print_render_attribute_string( 'premium_content_toggle_first_content_text' ); ?>>
										<?php echo $this->parse_text_editor( $settings['premium_content_toggle_first_content_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								<?php
							} else {
								$first_template = empty( $settings['premium_content_toggle_first_content_templates'] ) ? $settings['live_temp_content'] : $settings['premium_content_toggle_first_content_templates'];
								?>
									<div class="pa-ct__first-content-item-wrapper">
										<?php echo Helper_Functions::render_elementor_template( $first_template ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								<?php
							}
						} elseif ( 'text_editor' === $settings['premium_content_toggle_second_content_tools'] ) {

								$this->add_render_attribute( 'premium_content_toggle_second_content_text', 'class', 'pa-ct__yearly-text' );
							?>
								<div <?php $this->print_render_attribute_string( 'premium_content_toggle_second_content_text' ); ?>>
									<?php echo $this->parse_text_editor( $settings['premium_content_toggle_second_content_text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<?php
						} else {
							$second_template = empty( $settings['premium_content_toggle_second_content_templates'] ) ? $settings['live_temp_content_extra'] : $settings['premium_content_toggle_second_content_templates'];
							?>
								<div class="pa-ct__second-content-item-wrapper">
									<?php echo Helper_Functions::render_elementor_template( $second_template ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<?php

						}
						?>
					</li>
				</ul>
			</div>
		<?php
	}

	private function render_inner_content( $settings ) {
		$content_type = $settings['inner_cont_type'];
		?>
		<ul class="pa-ct__inner-content-wrapper">
			<li class="pa-ct__inner-monthly pa-ct__is-visible">
				<?php
				if ( 'text_editor' === $content_type ) {
					$this->add_render_attribute( 'pa_inner_monthly', 'class', 'pa-ct__inner-monthly-text' );
					?>
							<div <?php $this->print_render_attribute_string( 'pa_inner_monthly' ); ?>>
							<?php echo $this->parse_text_editor( $settings['finner_content'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php
				} else {
					$first_inner_temp = empty( $settings['pa_finner_temp'] ) ? $settings['pa_finner_live_temp_content'] : $settings['pa_finner_temp'];
					?>
							<div class="pa-ct__first-content-item-wrapper">
							<?php echo Helper_Functions::render_elementor_template( $first_inner_temp ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php
				}

				?>
			</li>
			<li class="pa-ct__inner-yearly pa-ct__is-hidden">
				<?php
				if ( 'text_editor' === $content_type ) {
					$this->add_render_attribute( 'pa_inner_yearly', 'class', 'pa-ct__inner-yearly-text' );
					?>
							<div <?php $this->print_render_attribute_string( 'pa_inner_yearly' ); ?>>
							<?php echo $this->parse_text_editor( $settings['sinner_content'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php
				} else {
					$sec_inner_temp = empty( $settings['pa_sinner_temp'] ) ? $settings['pa_sinner_live_temp_content'] : $settings['pa_sinner_temp'];
					?>
							<div class="pa-ct__second-content-item-wrapper">
							<?php echo Helper_Functions::render_elementor_template( $sec_inner_temp ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php
				}

				?>
			</li>
		</ul>
		<?php
	}

	/**
	 * Render First Main Label HTML
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $label_tag HTML tag for the label
	 * @param array  $settings Widget settings
	 */
	private function pa_render_fmain_label( $label_tag, $settings ) {

		$label_txt = $settings['premium_content_toggle_heading_one'];
		// Use the normal icon if the type is 'switcher' and the preset is not 'circle-6'; otherwise use the toggle icon.
		$is_normal_icon = 'switcher' === $settings['type'] && 'circle-6' !== $settings['switcher_presets'];

		$icon     = 'button' === $settings['type'] || $is_normal_icon ? $settings['flabel_icon'] : false;
		$has_icon = $icon && '' !== $icon['value'];

		$effect         = 'switcher' === $settings['type'] ? $settings['pa_label_effects'] : '';
		$label_wrap_cls = 'pa-ct__label-wrapper pa-ct__flabel-wrapper pa-ct__active-label' . ' premium-button-' . $effect;
		?>
		<div class="<?php echo esc_attr( $label_wrap_cls ); ?>"<?php echo 'button' === $settings['type'] ? ' role="button" tabindex="0"' : ''; ?>>
			<?php
			if ( $has_icon ) {
				Icons_Manager::render_icon(
					$settings['flabel_icon'],
					array(
						'class'       => 'pa-ct__icon',
						'aria-hidden' => 'true',
					)
				);
			}

			if ( $effect ) {
				$this->add_label_effect( $effect ); }

			if ( ! empty( $label_txt ) ) {
				$this->add_render_attribute( 'premium_content_toggle_heading_one', 'class', array( 'pa-ct__label', 'pa-ct__first-label' ) );
				?>
						<<?php echo wp_kses_post( $label_tag . ' ' . $this->get_render_attribute_string( 'premium_content_toggle_heading_one' ) ); ?>>
						<?php echo wp_kses_post( $label_txt ); ?>
						</<?php echo wp_kses_post( $label_tag ); ?>>
					<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render Second Main Label HTML
	 *
	 * @since 1.0.0
	 * @access private
	 * @param string $label_tag HTML tag for the label
	 * @param array  $settings Widget settings
	 */
	private function pa_render_smain_label( $label_tag, $settings ) {

		$label_txt = $settings['premium_content_toggle_heading_two'];

		$is_normal_icon = 'switcher' === $settings['type'] && 'circle-6' !== $settings['switcher_presets'];

		$icon     = 'button' === $settings['type'] || $is_normal_icon ? $settings['slabel_icon'] : false;
		$has_icon = $icon && '' !== $icon['value'];

		$effect         = 'switcher' === $settings['type'] ? $settings['pa_label_effects'] : '';
		$label_wrap_cls = 'pa-ct__label-wrapper pa-ct__slabel-wrapper' . ' premium-button-' . $effect;
		?>
			<div class="<?php echo esc_attr( $label_wrap_cls ); ?>"<?php echo 'button' === $settings['type'] ? ' role="button" tabindex="0"' : ''; ?>>
				<?php
				if ( $has_icon ) {
					Icons_Manager::render_icon(
						$settings['slabel_icon'],
						array(
							'class'       => 'pa-ct__icon',
							'aria-hidden' => 'true',
						)
					);
				}

				if ( $effect ) {
					$this->add_label_effect( $effect ); }

				if ( ! empty( $label_txt ) ) {
					$this->add_render_attribute( 'premium_content_toggle_heading_two', 'class', array( 'pa-ct__label', 'pa-ct__second-label' ) );
					?>
							<<?php echo wp_kses_post( $label_tag . ' ' . $this->get_render_attribute_string( 'premium_content_toggle_heading_two' ) ); ?>>
							<?php echo wp_kses_post( $settings['premium_content_toggle_heading_two'] ); ?>
							</<?php echo wp_kses_post( $label_tag ); ?>>
						<?php
				}
				?>
			</div>
		<?php
	}

	/**
	 * Get effect svg
	 *
	 * @access private
	 * @since 4.9.15
	 */
	private function get_effect_svg( $effects ) {

		$effects_svg = apply_filters(
			'pa_showcase_highlights',
			array(
				'strikethrough' => '<svg class="outline-svg " aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 150" preserveAspectRatio="none"><path d="M3,75h493.5"></path></svg>',
			)
		);

		return $effects_svg[ $effects ] ?? '';
	}

	private function add_label_effect( $effect ) {

		if ( in_array( $effect, array( 'line1', 'line2', 'line4', 'line5', 'underline' ), true ) ) {
			echo Helper_Functions::get_btn_svgs( $effect ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( in_array( $effect, array( 'circle', 'h-underline', 'underline-zigzag', 'diagonal', 'x' ), true ) ) {
			echo $this->get_effect_svg( $effect ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	private function pa_render_finner_label( $label_tag, $settings, $is_reversed ) {
		$cls       = $is_reversed ? 'pa-has-inner-sw pa-ct__active-label' : 'pa-ct__active-label';
		$label_txt = $is_reversed ? $settings['premium_content_toggle_heading_two'] : $settings['premium_content_toggle_heading_one'];

		?>
			<div class="pa-ct__ml-wrapper pa-ct__fm-label-wrapper <?php echo esc_attr( $cls ); ?>" role="button" tabindex="0">
				<?php

				if ( ! empty( $label_txt ) ) {
					$this->add_render_attribute( 'premium_content_toggle_heading_one', 'class', array( 'pa-ct__mlabel', 'pa-ct__first-main-label' ) );
					?>
								<<?php echo wp_kses_post( $label_tag . ' ' . $this->get_render_attribute_string( 'premium_content_toggle_heading_one' ) ); ?>>
							<?php echo wp_kses_post( $label_txt ); ?>
								</<?php echo wp_kses_post( $label_tag ); ?>>
							<?php
				}

				if ( $is_reversed ) {
					$this->pa_render_inner_sw( $settings );
				}
				?>
			</div>
		<?php
	}

	private function pa_render_sinner_label( $label_tag, $settings, $is_reversed ) {
		$cls   = $is_reversed ? '' : 'pa-has-inner-sw';
		$label = $is_reversed ? $settings['premium_content_toggle_heading_one'] : $settings['premium_content_toggle_heading_two'];

		?>
			<div class="pa-ct__ml-wrapper pa-ct__sm-label-wrapper <?php echo esc_attr( $cls ); ?>" role="button" tabindex="0">
				<?php
				if ( ! empty( $label ) ) {

					$this->add_render_attribute( 'premium_content_toggle_heading_two', 'class', array( 'pa-ct__mlabel', 'pa-ct__second-main-label' ) );
					?>
							<<?php echo wp_kses_post( $label_tag . ' ' . $this->get_render_attribute_string( 'premium_content_toggle_heading_two' ) ); ?>>
						<?php echo wp_kses_post( $label ); ?>
							</<?php echo wp_kses_post( $label_tag ); ?>>
						<?php
				}

				if ( ! $is_reversed ) {
					$this->pa_render_inner_sw( $settings );
				}
				?>
			</div>
		<?php
	}
}
