<?php
/**
 * Class: Module
 * Name: Global Badge
 * Slug: premium-global-badge
 *
 * @since 2.7.0
 */

namespace PremiumAddonsPro\Modules\PremiumGlobalBadge;

use PremiumAddonsPro\Includes\PAPRO_Helper;

// Elementor Classes.
use Elementor\Utils;
use Elementor\Control_Media;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;

// Premium Addons Classes.
use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Helper_Functions;
use PremiumAddonsPro\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

/**
 * Class Module For Premium Global Badge Addon.
 */
class Module extends Module_Base {

	/**
	 * Load Script
	 *
	 * @var $load_script
	 */
	private $load_script = null;

	/**
	 * Class Constructor Function.
	 */
	public function __construct() {

		// Enqueue the required JS file.
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_styles' ) );

		// Creates Premium Global Badge tab at the end of layout/content tab.
		add_action( 'elementor/element/section/section_layout/after_section_end', array( $this, 'register_controls' ), 10 );
		add_action( 'elementor/element/column/section_advanced/after_section_end', array( $this, 'register_controls' ), 10 );
		add_action( 'elementor/element/common/_section_style/after_section_end', array( $this, 'register_controls' ), 10 );

		// Editor Hooks.
		add_action( 'elementor/section/print_template', array( $this, 'print_template' ), 10, 2 );
		add_action( 'elementor/column/print_template', array( $this, 'print_template' ), 10, 2 );
		add_action( 'elementor/widget/print_template', array( $this, 'print_template' ), 10, 2 );

		// Frontend Hooks.
		add_action( 'elementor/frontend/section/before_render', array( $this, 'before_render' ) );
		add_action( 'elementor/frontend/column/before_render', array( $this, 'before_render' ) );
		add_action( 'elementor/widget/before_render_content', array( $this, 'before_render' ), 10, 1 );

		add_action( 'elementor/frontend/before_render', array( $this, 'check_script_enqueue' ) );

		add_action( 'elementor/element/container/section_layout/after_section_end', array( $this, 'register_controls' ), 10 );
		add_action( 'elementor/container/print_template', array( $this, 'print_template' ), 10, 2 );
		add_action( 'elementor/frontend/container/before_render', array( $this, 'before_render' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * Registers required dependencies for the extension and enqueues them.
	 *
	 * @since 1.6.5
	 * @access public
	 */
	public function enqueue_scripts() {

		if ( ! wp_script_is( 'lottie-js', 'enqueued' ) ) {
			wp_enqueue_script( 'lottie-js' );
		}

		if ( ! wp_script_is( 'pa-anime', 'enqueued' ) ) {
			wp_enqueue_script( 'pa-anime' );
		}

		if ( ! wp_script_is( 'pa-badge', 'enqueued' ) ) {
			wp_enqueue_script( 'pa-badge' );
		}
	}

	/**
	 * Enqueue styles.
	 *
	 * Registers required dependencies for the extension and enqueues them.
	 *
	 * @since 2.6.5
	 * @access public
	 */
	public function enqueue_styles() {

		if ( ! wp_style_is( 'pa-global', 'enqueued' ) ) {
			wp_enqueue_style( 'pa-global' );
		}
	}

	/**
	 * Register Global badge controls.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param object $element for current element.
	 */
	public function register_controls( $element ) {

		$tab = ! in_array( $element->get_name(), array( 'common', 'common-optimized' ) ) ? Controls_Manager::TAB_LAYOUT : Controls_Manager::TAB_CONTENT;

		$element->start_controls_section(
			'section_premium_badge',
			array(
				'label' => sprintf( '<i class="pa-extension-icon pa-dash-icon"></i> %s', __( 'Global Badge', 'premium-addons-pro' ) ),
				'tab'   => $tab,
			)
		);

		$this->add_content_controls( $element );

		$element->add_control(
			'pa_badge_heading',
			array(
				'label'     => esc_html__( 'Style & Layout', 'premium-addons-pro' ),
				'separator' => 'before',
				'type'      => Controls_Manager::HEADING,
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->start_controls_tabs( 'pa_style_tabs' );

		// Display section.
		$element->start_controls_tab(
			'pa_display_controls',
			array(
				'label'     => __( 'Layout', 'premium-addons-pro' ),
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$this->add_display_controls( $element );

		$element->end_controls_tab();

		// Style section.
		$element->start_controls_tab(
			'pa_style_controls',
			array(
				'label'     => __( 'Style', 'premium-addons-pro' ),
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$this->add_style_controls( $element );

		$element->end_controls_tab();

		// Icon style section.
		$element->start_controls_tab(
			'pa_icon_styles',
			array(
				'label'     => __( 'Icon', 'premium-addons-pro' ),
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type!'                 => 'none',
				),
			)
		);

		$this->add_icon_style( $element );

		$element->end_controls_tab();

		// Svg layer style section.
		$element->start_controls_tab(
			'pa_svg_layer_style',
			array(
				'label'     => __( 'SVG Layer', 'premium-addons-pro' ),
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_svg_enabled'          => 'yes',
					'pa_badge_type'                 => 'custom',
				),
			)
		);

		$this->add_svg_layer_style( $element );

		$element->end_controls_tab();

		$element->end_controls_tabs();

		$element->add_control(
			'pa_badge_tutorial',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'separator'       => 'before',
				'raw'             => sprintf( '<a href="%s" target="_blank">%s</a>', 'https://www.youtube.com/watch?v=PsYFwEXEXZg/', __( 'Check the video tutorial »', 'premium-addons-for-elementor' ) ),
				'content_classes' => 'editor-pa-doc',
				'condition'       => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->end_controls_section();
	}

	/**
	 * Add content controls.
	 *
	 * @access public
	 * @since 2.7.0
	 *
	 * @param object $element elementor element.
	 */
	public function add_content_controls( $element ) {

		$element->add_control(
			'premium_global_badge_switcher',
			array(
				'label'        => __( 'Enable Global Badge', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'premium-gbadge-',
				'render_type'  => 'template',
			)
		);

		$doc_link = Helper_Functions::get_campaign_link( 'https://premiumaddons.com/docs/elementor-badge-global-addon-tutorial/', 'badge-addon', 'wp-editor', 'get-support' );

		$element->add_control(
			'pa_badge_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => '<a href="' . esc_url( $doc_link ) . '" target="_blank">' . __( 'How to use Premium Global Badge for Elementor »', 'premium-addons-pro' ) . '</a>',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_badge_text',
			array(
				'label'       => __( 'Text', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'New',
				'dynamic'     => array( 'active' => true ),
				'label_block' => true,
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_badge_hide_if_empty',
			array(
				'label'       => __( 'Hide if Empty Text', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'When enabled, the badge will be hidden if the badge text is empty.', 'premium-addons-pro' ),
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_badge_type',
			array(
				'label'        => __( 'Style', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SELECT,
				'prefix_class' => 'premium-gbadge-',
				'render_type'  => 'template',
				'groups'       => array(
					'general'  => array(
						'label'   => __( 'General', 'premium-addons-pro' ),
						'options' => array(
							'stripe' => __( 'Stripe', 'premium-addons-pro' ),
							'tri'    => __( 'Triangle', 'premium-addons-pro' ),
							'circle' => __( 'Circle', 'premium-addons-pro' ),
							'banner' => __( 'Banner', 'premium-addons-pro' ),
						),
					),
					'flag'     => array(
						'label'   => __( 'Flag', 'premium-addons-pro' ),
						'options' => array(
							'flag'   => __( 'Style 1', 'premium-addons-pro' ),
							'flag-2' => __( 'Style 2', 'premium-addons-pro' ),
						),
					),
					'bookmark' => array(
						'label'   => __( 'Bookmark', 'premium-addons-pro' ),
						'options' => array(
							'bookmark'   => __( 'Style 1', 'premium-addons-pro' ),
							'bookmark-2' => __( 'Style 2', 'premium-addons-pro' ),
							'bookmark-3' => __( 'Style 3', 'premium-addons-pro' ),
							'bookmark-4' => __( 'Style 4', 'premium-addons-pro' ),
						),
					),
					'wrap'     => array(
						'label'   => __( 'Wrap', 'premium-addons-pro' ),
						'options' => array(
							'wrap-1' => __( 'Style 1', 'premium-addons-pro' ),
							'wrap-2' => __( 'Style 2', 'premium-addons-pro' ),
						),
					),
					'ribbon'   => array(
						'label'   => __( 'Ribbon', 'premium-addons-pro' ),
						'options' => array(
							'ribbon-1' => __( 'Style 1', 'premium-addons-pro' ),
							'ribbon-2' => __( 'Style 2', 'premium-addons-pro' ),
						),
					),
					'custom'   => array(
						'label'   => __( 'Custom', 'premium-addons-pro' ),
						'options' => array(
							'custom' => __( 'Custom Layout', 'premium-addons-pro' ),
						),
					),
				),
				'default'      => 'stripe',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_icon_type',
			array(
				'label'       => __( 'Icon', 'premium-addons-pro' ),
				'type'        => Controls_Manager::CHOOSE,
				'render_type' => 'template',
				'options'     => array(
					'none'   => array(
						'title' => __( 'None', 'premium-addons-pro' ),
						'icon'  => 'eicon-ban',
					),
					'icon'   => array(
						'title' => __( 'Icon', 'premium-addons-pro' ),
						'icon'  => 'divider-type-icon',
					),
					'image'  => array(
						'title' => __( 'Image', 'premium-addons-pro' ),
						'icon'  => 'divider-type-image',
					),
					'lottie' => array(
						'title' => __( 'Lottie Animation', 'premium-addons-pro' ),
						'icon'  => 'divider-type-lottie',
					),
				),
				'default'     => 'none',
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_badge_icon',
			array(
				'label'                  => __( 'Choose Icon', 'premium-addons-pro' ),
				'type'                   => Controls_Manager::ICONS,
				'skin'                   => 'inline',
				'label_block'            => false,
				'exclude_inline_options' => array( 'none' ),
				'default'                => array(
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				),
				'condition'              => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => 'icon',
				),
			)
		);

		$element->add_control(
			'pa_badge_img',
			array(
				'label'     => __( 'Choose Image', 'premium-addons-pro' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => 'image',
				),
			)
		);

		$element->add_control(
			'pa_badge_lottie_url',
			array(
				'label'       => __( 'Animation JSON URL', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXT,
				'description' => 'Get JSON code URL from <a href="https://lottiefiles.com/" target="_blank">here</a>',
				'label_block' => true,
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => 'lottie',

				),
			)
		);

		$element->add_control(
			'pa_badge_loop',
			array(
				'label'        => __( 'Loop', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'default'      => 'true',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => 'lottie',
				),
			)
		);

		$element->add_control(
			'pa_badge_reverse',
			array(
				'label'        => __( 'Reverse', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'true',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => 'lottie',
				),
			)
		);

		$element->add_control(
			'pa_badge_clip_enabled',
			array(
				'label'       => __( 'Enable Clip Path', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'template',
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => 'custom',
				),
			)
		);

		$element->add_control(
			'pa_badge_path',
			array(
				'label'       => __( 'Path Value', 'premium-addons-pro' ),
				'type'        => Controls_Manager::TEXTAREA,
				'description' => 'Get the clip path code from <a href="https://bennettfeely.com/clippy/" target="_blank">Clippy</a>.',
				'placeholder' => __( 'Paste your path code here. EX: polygon(50% 0%, 0% 100%, 100% 100%)', 'premium-addons-pro' ),
				'label_block' => true,
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => 'custom',
					'pa_badge_clip_enabled'         => 'yes',
				),
				'selectors'   => array(
					'{{WRAPPER}}.premium-gbadge-custom > .premium-global-badge-{{ID}}' => 'filter:blur(.25px); clip-path: {{VALUE}}; -webkit-clip-path: {{VALUE}}; -ms-clip-path: {{VALUE}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_svg_enabled',
			array(
				'label'       => __( 'Add SVG Layer', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'template',
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => 'custom',
				),
			)
		);

		$element->add_control(
			'pa_badge_svg',
			array(
				'label'       => __( 'SVG Code', 'premium-addons-pro' ),
				'type'        => Controls_Manager::CODE,
				'description' => 'Get Blob SVG code from <a href="https://www.blobmaker.app/" target="_blank">Blobmaker</a>',
				'label_block' => true,
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => 'custom',
					'pa_badge_svg_enabled'          => 'yes',
				),
			)
		);

		$this->add_floating_effects_controls( $element );
	}

	/**
	 * Add display controls.
	 *
	 * @access public
	 * @since 2.7.0
	 *
	 * @param object $element elementor element.
	 */
	public function add_display_controls( $element ) {

		/** Display & Position */
		$element->add_control(
			'pa_badge_display',
			array(
				'label'        => __( 'Display', 'premium-addons-pro' ),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'premium-gbadge-',
				'toggle'       => false,
				'options'      => array(
					'row'    => array(
						'title' => __( 'Inline', 'premium-addons-pro' ),
						'icon'  => 'eicon-ellipsis-h',
					),
					'column' => array(
						'title' => __( 'Block', 'premium-addons-pro' ),
						'icon'  => 'eicon-ellipsis-v',
					),
				),
				'default'      => 'row',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type!'                 => 'none',
				),
				'selectors'    => array(
					'{{WRAPPER}}:not(.premium-gbadge-bookmark-3):not(.premium-gbadge-bookmark-2).premium-gbadge-yes .premium-global-badge-{{ID}} .premium-badge-container' => 'flex-direction: {{VALUE}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_hor',
			array(
				'label'        => __( 'Horizontal Position', 'premium-addons-pro' ),
				'type'         => Controls_Manager::CHOOSE,
				'prefix_class' => 'premium-gbadge-',
				'toggle'       => false,
				'options'      => array(
					'left'  => array(
						'title' => __( 'Left', 'premium-addons-pro' ),
						'icon'  => 'eicon-h-align-left',
					),
					'right' => array(
						'title' => __( 'Right', 'premium-addons-pro' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'      => 'right',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type!'                => array( 'wrap-1', 'ribbon-1', 'ribbon-2' ),
				),
				'selectors'    => array(
					'{{WRAPPER}}.premium-gbadge-stripe .premium-global-badge-{{ID}}, .premium-gbadge-tri .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-custom > .premium-gbadge-svg-{{ID}}' => '{{VALUE}}: 0;',
					'{{WRAPPER}}.premium-gbadge-circle .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-custom .premium-global-badge-{{ID}}' => '{{VALUE}}: 8px;',
					'{{WRAPPER}}.premium-gbadge-bookmark .premium-global-badge-{{ID}}' => '{{VALUE}}: 20px;',
					'{{WRAPPER}}.premium-gbadge-bookmark-3 .premium-global-badge-{{ID}}' => '{{VALUE}}: 25px;',
				),
			)
		);

		$element->add_control(
			'pa_badge_ver',
			array(
				'label'        => __( 'Vertical Position', 'premium-addons-pro' ),
				'type'         => Controls_Manager::CHOOSE,
				'toggle'       => false,
				'prefix_class' => 'premium-gbadge-',
				'options'      => array(
					'top'    => array(
						'title' => __( 'Top', 'premium-addons-pro' ),
						'icon'  => 'eicon-v-align-top',
					),
					'bottom' => array(
						'title' => __( 'Bottom', 'premium-addons-pro' ),
						'icon'  => 'eicon-v-align-bottom',
					),
				),
				'default'      => 'top',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'custom', 'circle', 'wrap-1', 'ribbon-1', 'wrap-2', 'ribbon-2', 'bookmark-2', 'bookmark', 'bookmark-3' ),
				),
				'selectors'    => array(
					'{{WRAPPER}}:not(.premium-gbadge-flag):not(.premium-gbadge-circle):not(.premium-gbadge-custom):not(.premium-gbadge-bookmark-3):not(.premium-gbadge-wrap-2):not(.premium-gbadge-ribbon-1):not(.premium-gbadge-ribbon-2) .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-custom > .premium-gbadge-svg-{{ID}}' => '{{VALUE}}: 0;',
					'{{WRAPPER}}.premium-gbadge-circle .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-custom .premium-global-badge-{{ID}}' => '{{VALUE}}: 8px;',
				),
			)
		);

		$element->add_responsive_control(
			'pa_badge_hor_offset',
			array(
				'label'      => __( 'Horizontal Offset', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type!'                => array( 'flag', 'stripe', 'bookmark-4', 'flag-2', 'wrap-1', 'wrap-2' ),
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-circle .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-custom .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-bookmark .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-bookmark-3 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-bookmark-2 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-banner .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-banner .premium-global-badge-{{ID}} .premium-badge-container' => '{{pa_badge_hor.VALUE}}: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-tri .premium-global-badge-{{ID}} .premium-badge-container,{{WRAPPER}}.premium-gbadge-ribbon-1 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-ribbon-2 .premium-global-badge-{{ID}}' => 'left: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_responsive_control(
			'pa_badge_ver_offset',
			array(
				'label'      => __( 'Vertical Offset', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type!'                => array( 'bookmark', 'stripe', 'banner', 'bookmark-3', 'wrap-2', 'bookmark-2' ),
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-flag .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-circle .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-custom .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-bookmark-4 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-flag-2 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-wrap-1 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-ribbon-1 .premium-global-badge-{{ID}},{{WRAPPER}}.premium-gbadge-ribbon-2 .premium-global-badge-{{ID}}' => '{{pa_badge_ver.VALUE}}: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-tri.premium-gbadge-left .premium-global-badge-{{ID}} .premium-badge-container' => 'bottom: {{SIZE}}px;',
					'{{WRAPPER}}.premium-gbadge-tri.premium-gbadge-right .premium-global-badge-{{ID}} .premium-badge-container' => 'top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_icon_position',
			array(
				'label'     => __( 'Icon Position', 'premium-addons-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'separator' => 'before',
				'toggle'    => false,
				'options'   => array(
					'0' => array(
						'title' => __( 'Start', 'premium-addons-pro' ),
						'icon'  => 'eicon-h-align-left',
					),
					'2' => array(
						'title' => __( 'End', 'premium-addons-pro' ),
						'icon'  => 'eicon-h-align-right',
					),
				),
				'default'   => '2',
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type!'                 => 'none',
				),
				'selectors' => array(
					'{{WRAPPER}}:not(.premium-gbadge-bookmark-2):not(.premium-gbadge-bookmark-3) .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-icon,{{WRAPPER}}.premium-gbadge-bookmark-2 .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text,{{WRAPPER}}.premium-gbadge-bookmark-3 .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text' => 'order:{{VALUE}}',
				),
			)
		);

		$element->add_control(
			'pa_badge_spacing',
			array(
				'label'      => __( 'Spacing', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type!'                 => 'none',
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-container' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_rotate',
			array(
				'label'      => __( 'Rotate (Degrees)', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'deg' ),
				'separator'  => 'before',
				'default'    => array(
					'unit' => 'deg',
					'size' => 0,
				),
				'range'      => array(
					'deg' => array(
						'min' => -180,
						'max' => 180,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}}' => 'transform: rotate({{SIZE}}deg);',
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'custom', 'ribbon-1', 'ribbon-2' ),
					'pa_badge_ftranslate!'          => 'yes',
					'pa_badge_frotate!'             => 'yes',
				),
			)
		);
	}

	/**
	 * Add style controls.
	 *
	 * @access public
	 * @since 2.7.0
	 *
	 * @param object $element elementor element.
	 */
	public function add_style_controls( $element ) {

		$element->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'pa_badge_typo',
				'condition'      => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type!'                => array( 'bookmark', 'bookmark-2', 'bookmark-3', 'bookmark-4', 'ribbon-2', 'ribbon-1', 'wrap-2' ),
				),
				'fields_options' => array(
					'letter_spacing' => array(
						'responsive' => false,
					),
					'word_spacing'   => array(
						'responsive' => false,
					),
				),
				'selector'       => '{{WRAPPER}}:not(.premium-gbadge-stripe):not(.premium-gbadge-ribbon-1):not(.premium-gbadge-wrap-2) .premium-global-badge-{{ID}},
								{{WRAPPER}}.premium-gbadge-stripe > .premium-global-badge-{{ID}} .premium-badge-container,
								{{WRAPPER}}.premium-gbadge-custom > .premium-global-badge-{{ID}} .premium-badge-container,
                                {{WRAPPER}}.premium-gbadge-wrap-2 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text',
			)
		);

		$element->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'           => 'pa_bookmark_typo',
				'condition'      => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'bookmark', 'bookmark-2', 'bookmark-3', 'wrap-2', 'bookmark-4', 'ribbon-1', 'ribbon-2' ),
				),
				'fields_options' => array(
					'font_size'      => array(
						'selectors' => array(
							'{{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}} .premium-badge-text, {{WRAPPER}}.premium-gbadge-bookmark-2 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container::after, {{WRAPPER}}.premium-gbadge-wrap-2 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text, {{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text, {{WRAPPER}}.premium-gbadge-ribbon-1 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text, {{WRAPPER}}.premium-gbadge-ribbon-2 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text' => 'font-size: {{SIZE}}{{UNIT}};',
						),
					),
					'line_height'    => array(
						// 'default'   => array(
						// 'size' => '32',
						// 'unit' => 'px',
						// ),
						'selectors' => array(
							'{{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}}' => 'width: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
							'{{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}}:after, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container::after' => 'border-left-width: calc( {{SIZE}}{{UNIT}} / 2); border-right-width: calc( {{SIZE}}{{UNIT}} / 2);',
							'{{WRAPPER}}.premium-gbadge-bookmark-2 > .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container' => 'width: {{SIZE}}{{UNIT}};',
							'{{WRAPPER}}.premium-gbadge-wrap-2 > .premium-global-badge-{{ID}}' => '--pa-badge-size: {{SIZE}}{{UNIT}};',
							'{{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-container' => 'height: {{SIZE}}{{UNIT}};',
							'{{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-container::after' => 'border-top-width: calc( {{SIZE}}{{UNIT}} / 2 ); border-bottom-width: calc( {{SIZE}}{{UNIT}} / 2 );',
							'{{WRAPPER}}.premium-gbadge-ribbon-1 > .premium-global-badge-{{ID}} .premium-badge-container' => 'height: {{SIZE}}{{UNIT}}; ---pa-notch: calc( {{SIZE}}{{UNIT}} / 3);',
							'{{WRAPPER}}.premium-gbadge-ribbon-2 > .premium-global-badge-{{ID}} .premium-badge-container' => 'height: {{SIZE}}{{UNIT}}; --pa-ribbon2-height: calc( {{SIZE}}{{UNIT}} / 2);',
						),
					),
					'letter_spacing' => array(
						'responsive' => false,
					),
					'word_spacing'   => array(
						'responsive' => false,
					),
				),
				'selectors'      => array(
					'{{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}}',
					'{{WRAPPER}}.premium-gbadge-bookmark-2 > .premium-global-badge-{{ID}} .premium-badge-container',
					'{{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text',
					'{{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-container',
				),
			)
		);

		$element->add_control(
			'bookmark_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Use <b>Line Height</b> to control the bookmark size.', 'premium-addons-pro' ),
				'content_classes' => 'papro-upgrade-notice',
				'condition'       => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'bookmark', 'bookmark-2', 'bookmark-3', 'bookmark-4' ),
				),
			)
		);

		$element->add_control(
			'pa_badge_color',
			array(
				'label'     => __( 'Text Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-text' => 'color: {{VALUE}};',
				),
			)
		);

		$element->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			array(
				'name'      => 'pa_badge_text_shadow',
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
				),
				'selector'  => '{{WRAPPER}} .premium-global-badge-{{ID}}',
			)
		);

		$element->add_responsive_control(
			'pa_badge_margin',
			array(
				'label'      => __( 'Text Margin', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%', 'custom' ),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => 'custom',
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-custom > .premium-global-badge-{{ID}} .premium-badge-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_hr',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$element->add_responsive_control(
			'pa_badge_size',
			array(
				'label'      => __( 'Badge Size', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', '%', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'tri', 'ribbon-1', 'ribbon-2', 'custom' ),
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-tri.premium-gbadge-left > .premium-global-badge-{{ID}}' => 'border-top-width: {{SIZE}}{{UNIT}}; border-bottom-width: {{SIZE}}{{UNIT}}; border-right-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-tri.premium-gbadge-right > .premium-global-badge-{{ID}}' => 'border-left-width: {{SIZE}}{{UNIT}}; border-bottom-width: {{SIZE}}{{UNIT}}; border-right-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-custom > .premium-global-badge-{{ID}}' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-ribbon-1 > .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-ribbon-2 > .premium-global-badge-{{ID}}' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'           => 'pa_badge_shadow',
				'condition'      => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type!'                => 'tri',
				),
				'selector'       => '{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-container',
				'fields_options' => array(
					'box_shadow' => array(
						'selectors' => array(
							'{{WRAPPER}}:is(.premium-gbadge-stripe, .premium-gbadge-custom, .premium-gbadge-circle) > .premium-global-badge-{{ID}} .premium-badge-container' => 'box-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{COLOR}} {{box_shadow_position.VALUE}};',
							'{{WRAPPER}}:is(.premium-gbadge-wrap-2, .premium-gbadge-ribbon-1 ) > .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-bookmark .premium-global-badge-{{ID}}' => 'filter: drop-shadow({{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{COLOR}});',
							'{{WRAPPER}}:not(
								.premium-gbadge-bookmark,
								.premium-gbadge-stripe,
								.premium-gbadge-custom,
								.premium-gbadge-circle,
								.premium-gbadge-wrap-2,
								.premium-gbadge-ribbon-1
							) .premium-global-badge-{{ID}} .premium-badge-container' => 'filter: drop-shadow({{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{COLOR}})',
						),
					),
				),
			)
		);

		$element->add_group_control(
			PAPRO_Helper::get_bg_control_class()::get_type(),
			array(
				'name'           => 'pa_badge_bg',
				'types'          => array( 'classic', 'gradient' ),
				'fields_options' => array(
					'background' => array(
						'default' => 'classic',
					),
					'color'      => array(
						'global' => array(
							'default' => Global_Colors::COLOR_PRIMARY,
						),
					),
				),
				'condition'      => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'wrap-1', 'custom', 'circle', 'stripe' ),
				),
				'selector'       => '{{WRAPPER}}.pa-bg-clipped-yes.premium-gbadge-custom .premium-global-badge-{{ID}} .premium-badge-text, {{WRAPPER}}:not(.premium-gbadge-custom) .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-custom:not(.pa-bg-clipped-yes) .premium-global-badge-{{ID}} .premium-badge-container',
			)
		);

		$element->add_control(
			'pa_badge_bgcolor',
			array(
				'label'     => __( 'Background Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#6EC1E4',
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'bookmark', 'tri', 'flag', 'banner', 'bookmark-3', 'bookmark-4', 'flag-2', 'wrap-2', 'ribbon-1', 'bookmark-2', 'ribbon-2' ),
				),
				'selectors' => array(
					'{{WRAPPER}}.premium-gbadge-flag .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-bookmark .premium-global-badge-{{ID}}' => 'background-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-banner > .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-banner > .premium-global-badge-{{ID}} .premium-badge-container::after, {{WRAPPER}}.premium-gbadge-banner > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text::before, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-flag-2 > .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-flag-2 > .premium-global-badge-{{ID}} .premium-badge-container::before, {{WRAPPER}}.premium-gbadge-bookmark-2 .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-bookmark-2 .premium-global-badge-{{ID}} .premium-badge-container::after' => 'background: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-ribbon-1 > .premium-global-badge-{{ID}} .premium-badge-container' => 'background: {{VALUE}}; --pa-border-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-flag.premium-gbadge-right .premium-global-badge-{{ID}}:after, {{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}}:after, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container::after, {{WRAPPER}}.premium-gbadge-bookmark-4.premium-gbadge-left > .premium-global-badge-{{ID}} .premium-badge-container::after' => 'border-left-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-flag.premium-gbadge-left .premium-global-badge-{{ID}}:after, {{WRAPPER}}.premium-gbadge-tri.premium-gbadge-right .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}}:after, {{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container::after, {{WRAPPER}}.premium-gbadge-bookmark-4.premium-gbadge-right > .premium-global-badge-{{ID}} .premium-badge-container::after' => 'border-right-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-tri.premium-gbadge-left .premium-global-badge-{{ID}}' => 'border-top-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-wrap-2 > .premium-global-badge-{{ID}} .premium-badge-container' => '--pa-badge-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-ribbon-2 .premium-global-badge-{{ID}} .premium-badge-container' => '--pa-ribbon2-color: {{VALUE}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_edge_color',
			array(
				'label'     => __( 'Edge Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'banner', 'wrap-1', 'wrap-2', 'bookmark-3', 'bookmark-4', 'flag', 'flag-2' ),
				),
				'selectors' => array(
					'{{WRAPPER}}.premium-gbadge-banner > .premium-global-badge-{{ID}} .premium-badge-container .premium-badge-text::after, {{WRAPPER}}.premium-gbadge-flag-2 > .premium-global-badge-{{ID}} .premium-badge-container::after, {{WRAPPER}}.premium-gbadge-wrap-2 > .premium-global-badge-{{ID}}::after, {{WRAPPER}}.premium-gbadge-wrap-2 > .premium-global-badge-{{ID}}::before' => 'background: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-bookmark-3 > .premium-global-badge-{{ID}} .premium-badge-container::before' => '--pa-edge-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-container::before' => 'border-bottom-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-wrap-1 > .premium-global-badge-{{ID}} .premium-badge-container::before, {{WRAPPER}}.premium-gbadge-wrap-1 > .premium-global-badge-{{ID}} .premium-badge-container::after' => 'border-top-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-flag.premium-gbadge-right > .premium-global-badge-{{ID}}::after' => 'border-left-color: {{VALUE}};',
					'{{WRAPPER}}.premium-gbadge-flag.premium-gbadge-left > .premium-global-badge-{{ID}}::after' => 'border-right-color: {{VALUE}};',
				),
			),
		);

		$element->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'      => 'pa_badge_border',
				'selector'  => '{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-container',
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'custom', 'circle', 'wrap-2' ),
					'pa_badge_clip_enabled!'        => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_badge_border_rad',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'custom' ),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_adv_radius!'          => 'yes',
					'pa_badge_clip_enabled!'        => 'yes',
					'pa_badge_type'                 => array( 'custom', 'circle', 'flag', 'flag-2' ),
				),
				'selectors'  => array(
					'{{WRAPPER}}:not(.premium-gbadge-flag):not(.premium-gbadge-flag-2) .premium-global-badge-{{ID}} .premium-badge-container' => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-flag.premium-gbadge-left .premium-global-badge-{{ID}} .premium-badge-container' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0;',
					'{{WRAPPER}}.premium-gbadge-flag.premium-gbadge-right .premium-global-badge-{{ID}} .premium-badge-container' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-flag-2.premium-gbadge-right .premium-global-badge-{{ID}} .premium-badge-container' => 'border-radius: {{SIZE}}{{UNIT}} 0 0 {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-flag-2.premium-gbadge-left .premium-global-badge-{{ID}} .premium-badge-container' => 'border-radius: 0 {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0;',
				),
			)
		);

		$element->add_control(
			'pa_badge_adv_radius',
			array(
				'label'       => __( 'Advanced Border Radius', 'premium-addons-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Apply custom radius values. Get the radius value from ', 'premium-addons-for-elementor' ) . '<a href="https://9elements.github.io/fancy-border-radius/" target="_blank">here</a>' . __( '. See ', 'premium-addons-for-elementor' ) . '<a href="https://www.youtube.com/watch?v=S0BJazLHV-M" target="_blank">tutorial</a>',
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_clip_enabled!'        => 'yes',
					'pa_badge_type'                 => array( 'custom', 'circle' ),
				),
			)
		);

		$element->add_control(
			'pa_badge_adv_radius_val',
			array(
				'label'     => __( 'Border Radius', 'premium-addons-pro' ),
				'type'      => Controls_Manager::TEXT,
				'selectors' => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-container' => 'border-radius: {{VALUE}};',
				),
				'condition' => array(
					'pa_badge_adv_radius'    => 'yes',
					'pa_badge_clip_enabled!' => 'yes',
					'pa_badge_type'          => array( 'custom', 'circle' ),
				),
			)
		);

		$element->add_control(
			'pa_badge_zindex',
			array(
				'label'       => __( 'Z-Index', 'premium-addons-pro' ),
				'type'        => Controls_Manager::NUMBER,
				'step'        => 1,
				'description' => __( 'Default is 5', 'premium-addons-pro' ),
				'selectors'   => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}}' => 'z-index: {{VALUE}}',
				),
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_responsive_control(
			'pa_badge_padding',
			array(
				'label'      => __( 'Padding', 'premium-addons-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'custom' ),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'custom', 'circle', 'flag', 'flag-2', 'banner', 'circle' ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-banner .premium-global-badge-{{ID}} .premium-badge-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_padding_bookmark',
			array(
				'label'              => __( 'Padding', 'premium-addons-pro' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'allowed_dimensions' => 'vertical',
				'placeholder'        => array(
					'top'    => '',
					'right'  => '',
					'bottom' => '',
					'left'   => '',
				),
				'size_units'         => array( 'px', 'em', 'custom' ),
				'condition'          => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'bookmark', 'bookmark-3', 'bookmark-2' ),
				),
				'selectors'          => array(
					'{{WRAPPER}}.premium-gbadge-bookmark .premium-global-badge-{{ID}}, {{WRAPPER}}.premium-gbadge-bookmark-3 .premium-global-badge-{{ID}} .premium-badge-container, {{WRAPPER}}.premium-gbadge-bookmark-2 .premium-global-badge-{{ID}} .premium-badge-container' => 'padding: {{TOP}}{{UNIT}} 0 {{BOTTOM}}{{UNIT}} 0;',
				),
			)
		);

		$element->add_control(
			'pa_badge_hor_padding',
			array(
				'label'              => __( 'Padding', 'premium-addons-pro' ),
				'type'               => Controls_Manager::DIMENSIONS,
				'allowed_dimensions' => 'horizontal',
				'placeholder'        => array(
					'top'    => '',
					'right'  => '',
					'bottom' => '',
					'left'   => '',
				),
				'size_units'         => array( 'px', 'em', 'custom' ),
				'condition'          => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_type'                 => array( 'bookmark-4' ),
				),
				'selectors'          => array(
					'{{WRAPPER}}.premium-gbadge-bookmark-4 .premium-global-badge-{{ID}} .premium-badge-container' => 'padding-right: {{RIGHT}}{{UNIT}} ; padding-left: {{LEFT}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_background_style',
			array(
				'label'        => __( 'Animated Gradient', 'premium-addons-for-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'description'  => __( 'Enable this option to apply an animated gradient effect to the text.', 'premium-addons-for-elementor' ),
				'prefix_class' => 'pa-badge-gradient-',
				'separator'    => 'before',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_control(
			'pa_gradient_speed',
			array(
				'label'     => __( 'Animation Speed (sec)', 'premium-addons-for-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => .1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}}.pa-badge-gradient-yes .premium-badge-text, {{WRAPPER}}.pa-badge-gradient-yes .premium-badge-icon' => 'animation-duration: {{SIZE}}s ',
				),
				'condition' => array(
					'pa_background_style'           => 'yes',
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$element->add_group_control(
			PAPRO_Helper::get_bg_control_class()::get_type(),
			array(
				'name'      => 'pa-text_gradient',
				'types'     => array( 'gradient' ),
				'selector'  => '{{WRAPPER}}.pa-badge-gradient-yes .premium-badge-text, {{WRAPPER}}.pa-badge-gradient-yes .premium-badge-icon',
				'condition' => array(
					'pa_background_style'           => 'yes',
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);
	}

	/**
	 * Add icon style controls.
	 *
	 * @access public
	 * @since 2.7.0
	 *
	 * @param object $element elementor element.
	 */
	public function add_icon_style( $element ) {

		$element->add_control(
			'pa_badge_icon_color',
			array(
				'label'     => __( 'Color', 'premium-addons-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array(
					'default' => Global_Colors::COLOR_SECONDARY,
				),
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => 'icon',
				),
				'selectors' => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				),
			)
		);

		$element->add_responsive_control(
			'pa_badge_icon_size',
			array(
				'label'      => __( 'Size', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 500,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type!'                 => 'none',
				),
				'selectors'  => array(
					'{{WRAPPER}}:not(.premium-gbadge-bookmark):not(.premium-gbadge-bookmark-4) > .premium-global-badge-{{ID}} .premium-badge-icon' => 'font-size: {{SIZE}}{{UNIT}}; line-height:{{SIZE}}{{UNIT}}',
					'{{WRAPPER}}.premium-gbadge-bookmark > .premium-global-badge-{{ID}} .premium-badge-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}:not(.premium-gbadge-bookmark-4) > .premium-global-badge-{{ID}} .premium-badge-icon, {{WRAPPER}} > .premium-global-badge-{{ID}} .premium-lottie-animation,
					 {{WRAPPER}}:not(.premium-gbadge-bookmark) > .premium-global-badge-{{ID}} .premium-badge-img ' => 'width:{{SIZE}}{{UNIT}}; height:{{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.premium-gbadge-bookmark-4 > .premium-global-badge-{{ID}} .premium-badge-icon' => 'font-size: {{SIZE}}{{UNIT}}; width:{{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_icon_rad',
			array(
				'label'      => __( 'Border Radius', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'custom' ),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_icon_type'                  => array( 'image', 'lottie' ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .premium-global-badge-{{ID}} .premium-badge-img, {{WRAPPER}} .premium-global-badge-{{ID}} .premium-lottie-animation svg' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);
	}

	/**
	 * Add svg layer style controls.
	 *
	 * @access public
	 * @since 2.7.0
	 *
	 * @param object $element elementor element.
	 */
	public function add_svg_layer_style( $element ) {
		$element->add_responsive_control(
			'pa_badge_svg_size',
			array(
				'label'      => __( 'Layer Size', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 500,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_svg_enabled'          => 'yes',
					'pa_badge_type'                 => 'custom',
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-custom > .premium-gbadge-svg-{{ID}}' => 'width:{{SIZE}}{{UNIT}}; height:{{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_responsive_control(
			'pa_badge_svg_hor',
			array(
				'label'      => __( 'SVG Horizontal Offset', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => -200,
						'max' => 200,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_svg_enabled'          => 'yes',
					'pa_badge_type'                 => 'custom',
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-custom > .premium-gbadge-svg-{{ID}}' => '{{pa_badge_hor.VALUE}}: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_responsive_control(
			'pa_badge_svg_ver',
			array(
				'label'      => __( 'SVG Vertical Offset', 'premium-addons-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'em', 'custom' ),
				'range'      => array(
					'px' => array(
						'min' => -200,
						'max' => 200,
					),
				),
				'condition'  => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_svg_enabled'          => 'yes',
					'pa_badge_type'                 => 'custom',
				),
				'selectors'  => array(
					'{{WRAPPER}}.premium-gbadge-custom > .premium-gbadge-svg-{{ID}}' => '{{pa_badge_ver.VALUE}}: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$element->add_control(
			'pa_badge_svg_zindex',
			array(
				'label'       => __( 'Z-Index', 'premium-addons-pro' ),
				'type'        => Controls_Manager::NUMBER,
				'step'        => 1,
				'description' => __( 'Default is 2', 'premium-addons-pro' ),
				'selectors'   => array(
					'{{WRAPPER}}.premium-gbadge-custom > .premium-gbadge-svg-{{ID}}' => 'z-index: {{VALUE}}',
				),
				'condition'   => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_svg_enabled'          => 'yes',
					'pa_badge_type'                 => 'custom',
				),
			)
		);
	}

	/**
	 * Add floating effects controls.
	 *
	 * @access public
	 * @since 2.7.0
	 *
	 * @param object $element elementor element.
	 */
	public function add_floating_effects_controls( $element ) {

		$element->add_control(
			'pa_badge_effects',
			array(
				'label'     => __( 'Floating Effects', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => array(
					'premium_global_badge_switcher' => 'yes',
				),
			)
		);

		$float_conditions = array(
			'pa_badge_effects'              => 'yes',
			'premium_global_badge_switcher' => 'yes',
		);

		$element->add_control(
			'pa_badge_ftranslate',
			array(
				'label'     => __( 'Translate', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_type!' => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_ftranslatex',
			array(
				'label'     => __( 'Translate X', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => -5,
						'end'   => 5,
					),
					'unit'  => 'px',
				),
				'range'     => array(
					'px' => array(
						'min' => -100,
						'max' => 100,
					),
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_ftranslate' => 'yes',
						'pa_badge_type!'      => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_ftranslatey',
			array(
				'label'     => __( 'Translate Y', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => -5,
						'end'   => 5,
					),
					'unit'  => 'px',
				),
				'range'     => array(
					'px' => array(
						'min' => -100,
						'max' => 100,
					),
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_ftranslate' => 'yes',
						'pa_badge_type!'      => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_ftranslate_speed',
			array(
				'label'     => __( 'Speed', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'   => array(
					'size' => 1,
				),
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_ftranslate' => 'yes',
						'pa_badge_type!'      => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_frotate',
			array(
				'label'     => __( 'Rotate', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_type!' => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_frotatex',
			array(
				'label'     => __( 'Rotate X', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 45,
					),
					'unit'  => 'deg',
				),
				'range'     => array(
					'deg' => array(
						'min' => -180,
						'max' => 180,
					),
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_frotate' => 'yes',
						'pa_badge_type!'   => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_frotatey',
			array(
				'label'     => __( 'Rotate Y', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 45,
					),
					'unit'  => 'deg',
				),
				'range'     => array(
					'deg' => array(
						'min' => -180,
						'max' => 180,
					),
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_frotate' => 'yes',
						'pa_badge_type!'   => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_frotatez',
			array(
				'label'     => __( 'Rotate Z', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 45,
					),
					'unit'  => 'deg',
				),
				'range'     => array(
					'deg' => array(
						'min' => -180,
						'max' => 180,
					),
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_frotate' => 'yes',
						'pa_badge_type!'   => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_frotate_speed',
			array(
				'label'     => __( 'Speed', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'   => array(
					'size' => 1,
				),
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_frotate' => 'yes',
						'pa_badge_type!'   => array( 'stripe', 'wrap-2' ),
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_fopacity',
			array(
				'label'     => __( 'Opacity', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => $float_conditions,
			)
		);

		$element->add_control(
			'pa_badge_fopacity_value',
			array(
				'label'     => __( 'Value', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 50,
					),
					'unit'  => '%',
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_fopacity' => 'yes',
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_fopacity_speed',
			array(
				'label'     => __( 'Speed', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'   => array(
					'size' => 1,
				),
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_fopacity' => 'yes',
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_fblur',
			array(
				'label'     => __( 'Blur', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => $float_conditions,
			)
		);

		$element->add_control(
			'pa_badge_fblur_value',
			array(
				'label'     => __( 'Value', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 1,
					),
					'unit'  => 'px',
				),
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					),
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_fblur' => 'yes',
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_fblur_speed',
			array(
				'label'     => __( 'Speed', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'   => array(
					'size' => 1,
				),
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_fblur' => 'yes',
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_fgrayscale',
			array(
				'label'     => __( 'Grayscale', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SWITCHER,
				'condition' => $float_conditions,
			)
		);

		$element->add_control(
			'pa_badge_fgscale_value',
			array(
				'label'     => __( 'Value', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array(
					'sizes' => array(
						'start' => 0,
						'end'   => 50,
					),
					'unit'  => '%',
				),
				'labels'    => array(
					__( 'From', 'premium-addons-pro' ),
					__( 'To', 'premium-addons-pro' ),
				),
				'scales'    => 1,
				'handles'   => 'range',
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_fgrayscale' => 'yes',
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_fgscale_speed',
			array(
				'label'     => __( 'Speed', 'premium-addons-pro' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 10,
						'step' => 0.1,
					),
				),
				'default'   => array(
					'size' => 1,
				),
				'condition' => array_merge(
					$float_conditions,
					array(
						'pa_badge_fgrayscale' => 'yes',
					)
				),
			)
		);

		$element->add_control(
			'pa_badge_disable_on_safari',
			array(
				'label'        => __( 'Disable Floating Effects On Safari', 'premium-addons-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'prefix_class' => 'pa-badge-disable-fe-',
				'separator'    => 'before',
				'condition'    => array(
					'premium_global_badge_switcher' => 'yes',
					'pa_badge_effects'              => 'yes',
				),
			)
		);
	}

	/**
	 * Render Global badge output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 2.2.8
	 * @access public
	 *
	 * @param object $template for current template.
	 * @param object $element for current element.
	 */
	public function print_template( $template, $element ) {

		if ( ! $template && 'widget' === $element->get_type() ) {
			return;
		}

		$old_template = $template;
		ob_start();
		?>
		<#
			var isEnabled = 'yes' === settings.premium_global_badge_switcher;

			if ( isEnabled ) {

				var text = settings.pa_badge_text,
				iconEnabled = settings.pa_icon_type !== 'none',
				svgEnabled = 'custom' === settings.pa_badge_type && 'yes' === settings.pa_badge_svg_enabled,
				floatingEnabled    = 'yes' === settings.pa_badge_effects,
				badgeSettings = {
					text: text,
				};

				if ( svgEnabled ) {
					badgeSettings.svgLayer = settings.pa_badge_svg;
				}

				if ( iconEnabled ) {
					var type = settings.pa_icon_type,
						icon = {};

					badgeSettings.iconType = type;

					switch( type ) {
						case 'icon':
							icon = settings.pa_badge_icon;
							break;

						case 'image':
							icon.url = settings.pa_badge_img.url;
							break;

						case 'lottie':
							icon.url     = settings.pa_badge_lottie_url;
							icon.loop    = settings.pa_badge_loop;
							icon.reverse = settings.pa_badge_reverse;
							break;

						default:
						icon = false;
					}
				} else {
					icon = false;
				}

				badgeSettings.icon = icon;

				if ( floatingEnabled ) {
					var floatingSettings = {},
						filtersEnabled = 'yes' === settings.pa_badge_fblur || 'yes' === settings.pa_badge_fgrayscale ? true : false,
						isStripe       = 'stripe' === settings.pa_badge_type;

					if ( !isStripe && 'yes' === settings.pa_badge_ftranslate ) {

						var translateSettings = {
							x_param_from: settings.pa_badge_ftranslatex.sizes.start,
							x_param_to: settings.pa_badge_ftranslatex.sizes.end,
							y_param_from: settings.pa_badge_ftranslatey.sizes.start,
							y_param_to: settings.pa_badge_ftranslatey.sizes.end,
							speed: settings.pa_badge_ftranslate_speed.size * 1000,
						};

						floatingSettings.translate = translateSettings;
					}

					if ( !isStripe && 'yes' === settings.pa_badge_frotate ) {

						var rotateSettings = {
							x_param_from: settings.pa_badge_frotatex.sizes.start,
							x_param_to: settings.pa_badge_frotatex.sizes.end,
							y_param_from: settings.pa_badge_frotatey.sizes.start,
							y_param_to: settings.pa_badge_frotatey.sizes.end,
							z_param_from: settings.pa_badge_frotatez.sizes.start,
							z_param_to: settings.pa_badge_frotatez.sizes.end,
							speed: settings.pa_badge_frotate_speed.size * 1000,
						};

						floatingSettings.rotate = rotateSettings;
					}

					if ( 'yes' === settings.pa_badge_fopacity ) {

						var opacitySettings = {
							from: settings.pa_badge_fopacity_value.sizes.start / 100,
							to: settings.pa_badge_fopacity_value.sizes.end / 100,
							speed: settings.pa_badge_fopacity_speed.size * 1000,
						};

						floatingSettings.opacity = opacitySettings;
					}

					if ( filtersEnabled ) {
						var filtersSettings = {};

						if ( 'yes' === settings.pa_badge_fblur ) {

							var blurSettings = {
								from: 'blur(' + settings.pa_badge_fblur_value.sizes.start + 'px)',
								to: 'blur(' + settings.pa_badge_fblur_value.sizes.end + 'px)',
								speed: settings.pa_badge_fblur_speed.size * 1000,
							};

							filtersSettings.blur = blurSettings;
						}

						if ( 'yes' === settings.pa_badge_fgrayscale ) {
							var gscaleSettings = {
								from: 'grayscale(' + settings.pa_badge_fgscale_value.sizes.start + '%)',
								to: 'grayscale(' + settings.pa_badge_fgscale_value.sizes.end + '%)',
								speed: settings.pa_badge_fgscale_speed.size * 1000,
							};

							filtersSettings.gscale = gscaleSettings;
						}

						floatingSettings.filters = filtersSettings;
					}

					badgeSettings.floating = floatingSettings;
				}

				view.addRenderAttribute( 'badge_data', {
					'id': 'premium-global-badge-' + view.getID(),
					'class': 'premium-global-badge-wrapper',
					'data-gbadge': JSON.stringify( badgeSettings ),
					'style': 'display: none'
				});
		#>
				<div {{{ view.getRenderAttributeString( 'badge_data' ) }}}></div>
		<#
			}
		#>

		<?php

			$slider_content = ob_get_contents();
			ob_end_clean();
			$template = $slider_content . $old_template;
			return $template;
	}

	/**
	 * Render Global badge output on the frontend.
	 *
	 * Written in PHP and used to collect badge settings and add it as an element attribute.
	 *
	 * @access public
	 * @param object $element for current element.
	 */
	public function before_render( $element ) {

		if ( 'yes' === $element->get_settings_for_display( 'premium_global_badge_switcher' ) ) {

			$id           = $element->get_id();
			$element_type = $element->get_type();
			$settings     = $element->get_settings_for_display();

			$text             = strip_tags( $settings['pa_badge_text'] );
			$icon_enabled     = $settings['pa_icon_type'] !== 'none';
			$svg_enabled      = 'custom' === $settings['pa_badge_type'] && 'yes' === $settings['pa_badge_svg_enabled'];
			$floating_enabled = 'yes' === $settings['pa_badge_effects'];
			$hide_if_empty    = 'yes' === $settings['pa_badge_hide_if_empty'];
			$badge_settings   = array(
				'text'        => $text,
				'hideIfEmpty' => $hide_if_empty,
			);

			if ( $svg_enabled ) {
				$badge_settings['svgLayer'] = $settings['pa_badge_svg'];
			}

			if ( $icon_enabled ) {
				$type                       = $settings['pa_icon_type'];
				$badge_settings['iconType'] = $type;

				switch ( $type ) {
					case 'icon':
						$icon = $settings['pa_badge_icon'];
						break;

					case 'image':
						$icon        = array();
						$icon['url'] = $settings['pa_badge_img']['url'];
						$icon['alt'] = Control_Media::get_image_alt( $settings['pa_badge_img'] );
						break;

					case 'lottie':
						$icon            = array();
						$icon['url']     = esc_url( $settings['pa_badge_lottie_url'] );
						$icon['loop']    = $settings['pa_badge_loop'];
						$icon['reverse'] = $settings['pa_badge_reverse'];
						break;

					default:
						$icon = false;
						break;
				}
			} else {
				$icon = false;
			}

			$badge_settings['icon'] = $icon;

			if ( $floating_enabled ) {
				$floating_settings = array();
				$filters_enabled   = 'yes' === $settings['pa_badge_fblur'] || 'yes' === $settings['pa_badge_fgrayscale'] ? true : false;
				$is_stripe         = 'stripe' === $settings['pa_badge_type'];

				if ( ! $is_stripe && 'yes' === $settings['pa_badge_ftranslate'] ) {

					$translate_settings = array(
						'x_param_from' => $settings['pa_badge_ftranslatex']['sizes']['start'],
						'x_param_to'   => $settings['pa_badge_ftranslatex']['sizes']['end'],
						'y_param_from' => $settings['pa_badge_ftranslatey']['sizes']['start'],
						'y_param_to'   => $settings['pa_badge_ftranslatey']['sizes']['end'],
						'speed'        => $settings['pa_badge_ftranslate_speed']['size'] * 1000,
					);

					$floating_settings['translate'] = $translate_settings;
				}

				if ( ! $is_stripe && 'yes' === $settings['pa_badge_frotate'] ) {

					$rotate_settings = array(
						'x_param_from' => $settings['pa_badge_frotatex']['sizes']['start'],
						'x_param_to'   => $settings['pa_badge_frotatex']['sizes']['end'],
						'y_param_from' => $settings['pa_badge_frotatey']['sizes']['start'],
						'y_param_to'   => $settings['pa_badge_frotatey']['sizes']['end'],
						'z_param_from' => $settings['pa_badge_frotatez']['sizes']['start'],
						'z_param_to'   => $settings['pa_badge_frotatez']['sizes']['end'],
						'speed'        => $settings['pa_badge_frotate_speed']['size'] * 1000,
					);

					$floating_settings['rotate'] = $rotate_settings;
				}

				if ( 'yes' === $settings['pa_badge_fopacity'] ) {

					$opacity_settings = array(
						'from'  => $settings['pa_badge_fopacity_value']['sizes']['start'] / 100,
						'to'    => $settings['pa_badge_fopacity_value']['sizes']['end'] / 100,
						'speed' => $settings['pa_badge_fopacity_speed']['size'] * 1000,
					);

					$floating_settings['opacity'] = $opacity_settings;
				}

				if ( $filters_enabled ) {
					$filters_settings = array();
					if ( 'yes' === $settings['pa_badge_fblur'] ) {

						$blur_settings = array(
							'from'  => 'blur(' . $settings['pa_badge_fblur_value']['sizes']['start'] . 'px)',
							'to'    => 'blur(' . $settings['pa_badge_fblur_value']['sizes']['end'] . 'px)',
							'speed' => $settings['pa_badge_fblur_speed']['size'] * 1000,
						);

						$filters_settings['blur'] = $blur_settings;
					}

					if ( 'yes' === $settings['pa_badge_fgrayscale'] ) {
						$gscale_settings = array(
							'from'  => 'grayscale(' . $settings['pa_badge_fgscale_value']['sizes']['start'] . '%)',
							'to'    => 'grayscale(' . $settings['pa_badge_fgscale_value']['sizes']['end'] . '%)',
							'speed' => $settings['pa_badge_fgscale_speed']['size'] * 1000,
						);

						$filters_settings['gscale'] = $gscale_settings;
					}

					$floating_settings['filters'] = $filters_settings;
				}

				$badge_settings['floating'] = $floating_settings;
			}

			$element->add_render_attribute( '_wrapper', 'data-gbadge', wp_json_encode( $badge_settings ) );

			if ( 'widget' === $element_type && \Elementor\Plugin::instance()->editor->is_edit_mode() ) {

				$element->add_render_attribute(
					'badge' . $id,
					array(
						'id'          => 'premium-global-badge-temp-' . $id,
						'data-gbadge' => wp_json_encode( $badge_settings ),
					)
				);

				?>
				<div <?php $element->print_render_attribute_string( 'badge' . $id ); ?>></div>
				<?php
			}
		}
	}

	/**
	 * Check Script Enqueue
	 *
	 * Check if the script files should be loaded.
	 *
	 * @since 2.6.3
	 * @access public
	 *
	 * @param object $element for current element.
	 */
	public function check_script_enqueue( $element ) {

		$settings = $element->get_active_settings();

		if ( ! empty( $settings['premium_global_badge_switcher'] ) ) {

			$this->enqueue_styles();

			if ( 'lottie' === $settings['pa_icon_type'] && ! wp_script_is( 'lottie-js', 'enqueued' ) ) {
				wp_enqueue_script( 'lottie-js' );
			}

			if ( 'yes' === $settings['pa_badge_effects'] && ! wp_script_is( 'pa-anime', 'enqueued' ) ) {
				wp_enqueue_script( 'pa-anime' );
			}

			if ( ! wp_script_is( 'pa-badge', 'enqueued' ) ) {
				wp_enqueue_script( 'pa-badge' );
			}
		}
	}
}
