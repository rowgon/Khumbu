<?php
namespace Elementor;

use \Elementor\ElementsKit_Widget_Copyright_Handler as Handler;
use Elementor\Utils;


if ( ! defined( 'ABSPATH' ) ) exit;

class Elementskit_Widget_Copyright extends Widget_Base {
	use \Elementskit_Lite\Widgets\Widget_Notice;

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
		return ['ekit', 'elementskit', 'copyright', 'footer', 'text'];
	}

	public function get_help_url() {
		return 'https://wpmet.com/doc/copyright/';
	}

    protected function is_dynamic_content(): bool {
        return false;
    }
	protected function register_controls() {

		// Copyright Content
		$this->start_controls_section (
			'ekit_copyright_content',
			[
				'label' => esc_html__( 'Content', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_copyright_copyright_text',
			[
				'label' => esc_html__( 'Copyright Text', 'elementskit' ),
				'type' => Controls_Manager::TEXTAREA,
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'Copyright © [ekit_current_year] [ekit_site_title]. All rights reserved.', 'elementskit' ),
				'placeholder' => esc_html__( 'Copyright © [ekit_current_year] [ekit_site_title]', 'elementskit' ),
				'description' => esc_html__( 'Use [ekit_current_year] for current year and [ekit_site_title] for site title', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_copyright_custom_site_title',
			[
				'label' => esc_html__( 'Custom Site Title', 'elementskit' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'placeholder' => get_bloginfo( 'name' ),
				'description' => esc_html__( 'Override the [ekit_site_title] text. Leave empty to use the actual site title.', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_copyright_link',
			[
				'label' => esc_html__( 'Link', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'dynamic' => [
					'active' => true,
				],
                'default' => [
                    'url' => home_url( '/' ),
                ],
				'placeholder' => home_url( '/' ),
			]
		);

		$this->add_control(
			'ekit_copyright_link_type',
			[
				'label' => esc_html__( 'Link Type', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'No Link', 'elementskit' ),
					'site_title' => esc_html__( 'Link Site Title', 'elementskit' ),
					'full' => esc_html__( 'Link Entire Text', 'elementskit' ),
				],
				'default' => 'site_title',
				'description' => esc_html__( 'Choose how to apply the link: to the entire copyright text or just the [ekit_site_title] shortcode', 'elementskit' ),
			]
		);

		$this->add_responsive_control(
			'ekit_copyright_align',
			[
				'label' => esc_html__( 'Alignment', 'elementskit' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
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
				'default' => 'center',
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_copyright_html_tag',
			[
				'label' => esc_html__( 'HTML Tag', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'p' => 'p',
					'div' => 'div',
					'span' => 'span',
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
				],
				'default' => 'p',
			]
		);
		
		$this->end_controls_section();

		// Style Section
		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Text', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ekit_copyright_text_color',
			[
				'label' => esc_html__( 'Text Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-copyright-wrapper a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_copyright_text_typography',
				'selector' => '{{WRAPPER}} .ekit-copyright-wrapper',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'ekit_copyright_text_shadow',
				'selector' => '{{WRAPPER}} .ekit-copyright-wrapper',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'ekit_copyright_text_stroke',
				'selector' => '{{WRAPPER}} .ekit-copyright-wrapper',
			]
		);

		$this->add_control(
			'ekit_copyright_blend_mode',
			[
				'label' => esc_html__( 'Blend Mode', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'' => esc_html__( 'Normal', 'elementskit' ),
					'multiply' => 'Multiply',
					'screen' => 'Screen',
					'overlay' => 'Overlay',
					'darken' => 'Darken',
					'lighten' => 'Lighten',
					'color-dodge' => 'Color Dodge',
					'color-burn' => 'Color Burn',
					'hue' => 'Hue',
					'saturation' => 'Saturation',
					'color' => 'Color',
					'exclusion' => 'Exclusion',
					'luminosity' => 'Luminosity',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper' => 'mix-blend-mode: {{VALUE}}',
				],
				'separator' => 'before',
			]
		);
        $this->add_control(
            'ekit_copyright_link_style_heading',
            [
                'label' => esc_html__( 'Site Link', 'elementskit' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_copyright_link_typography',
				'selector' => '{{WRAPPER}} .ekit-copyright-wrapper a',
			]
		);
		
        $this->start_controls_tabs( 'ekit_copyright_link_style_tabs' );

		$this->start_controls_tab(
			'ekit_copyright_link_normal',
			[
				'label' => esc_html__( 'Normal', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_copyright_link_color',
			[
				'label' => esc_html__( 'Link Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_copyright_link_hover',
			[
				'label' => esc_html__( 'Hover', 'elementskit' ),
			]
		);

		$this->add_control(
			'ekit_copyright_link_hover_color',
			[
				'label' => esc_html__( 'Link Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
                'default' => '#2119ff',
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper a:hover' => 'color: {{VALUE}};',
				],
			]
		);

        $this->add_control(
            'ekit_copyright_link_hover_underline',
            [
                'label' => esc_html__( 'Underline on Hover', 'elementskit' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'elementskit' ),
                'label_off' => esc_html__( 'No', 'elementskit' ),
                'return_value' => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .ekit-copyright-wrapper a:hover' => 'text-decoration: underline;',
                ],
            ]
        );

        $this->add_control(
            'ekit_copyright_transition_duration',
            [
                'label' => esc_html__( 'Transition Duration (ms)', 'elementskit' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 300,
                'selectors' => [
                    '{{WRAPPER}} .ekit-copyright-wrapper a' => 'transition-duration: {{VALUE}}ms; transition-property: color; transition-timing-function: ease;',
                ],
            ]
        );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

		// Spacing Section
		$this->start_controls_section(
			'section_spacing',
			[
				'label' => esc_html__( 'Spacing', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'ekit_copyright_text_padding',
			[
				'label' => esc_html__( 'Padding', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_copyright_text_margin',
			[
				'label' => esc_html__( 'Margin', 'elementskit' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .ekit-copyright-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		echo '<div class="ekit-wid-con">';
			$this-> render_raw();
		echo '</div>';
	}

	/**
	 * Render copyright widget output in the frontend.
	 * @since 4.2.1
	 * @return void
	 */
    protected function render_raw() {
        $settings = $this->get_settings_for_display();
        
        $copyright_text = !empty($settings['ekit_copyright_copyright_text']) ? $settings['ekit_copyright_copyright_text'] : 'Copyright © [ekit_current_year] [ekit_site_title]. All rights reserved.';
        $html_tag = Utils::validate_html_tag( !empty($settings['ekit_copyright_html_tag']) ? $settings['ekit_copyright_html_tag'] : 'p' );

        // Get link settings
        $link_url = '';
        $link_type = !empty($settings['ekit_copyright_link_type']) ? $settings['ekit_copyright_link_type'] : 'site_title';
        
        if ( ! empty( $settings['ekit_copyright_link']['url'] ) ) {
            $link_url = $settings['ekit_copyright_link']['url'];
            $this->add_link_attributes( 'ekit_copyright_link', $settings['ekit_copyright_link'] );
        }

        // Process copyright text based on link type
        $custom_site_title = !empty($settings['ekit_copyright_custom_site_title']) ? $settings['ekit_copyright_custom_site_title'] : get_bloginfo('name');
        if ( 'site_title' === $link_type && !empty($link_url) ) {
            $copyright_text = $this->parse_text_with_linked_title($copyright_text);
        } else {
            $copyright_text = str_replace('[ekit_site_title]', esc_html($custom_site_title), $copyright_text);
            $copyright_text = do_shortcode( shortcode_unautop( $copyright_text ) );
        }

        ?>
        <<?php Utils::print_validated_html_tag( $html_tag ); ?> class="ekit-copyright-wrapper">
            <?php if ( 'full' === $link_type && !empty($link_url) ) { ?>
                <a <?php echo wp_kses_post( $this->get_render_attribute_string( 'ekit_copyright_link' ) ); ?>>
                    <span><?php echo wp_kses_post($copyright_text); ?></span>
                </a>
            <?php } else { ?>
                <?php echo wp_kses_post($copyright_text); ?>
            <?php } ?>
        </<?php Utils::print_validated_html_tag( $html_tag ); ?>>
        <?php
    }

    /**
     * Parse text with linked site title
     * 
     * @param string $text
     * @return string
     */
    protected function parse_text_with_linked_title($text) {
        // First, replace current year shortcode
        $text = str_replace('[ekit_current_year]', do_shortcode('[ekit_current_year]'), $text);
        
        // Replace site title shortcode with linked version
        $settings = $this->get_settings_for_display();
        $site_title = !empty($settings['ekit_copyright_custom_site_title']) ? $settings['ekit_copyright_custom_site_title'] : get_bloginfo('name');
        $linked_title = '<a ' . $this->get_render_attribute_string('ekit_copyright_link') . '>' . esc_html($site_title) . '</a>';
        $text = str_replace('[ekit_site_title]', $linked_title, $text);
        
        // Process any remaining shortcodes
        $text = do_shortcode( shortcode_unautop( $text ) );
        
        return $text;
    }
}