<?php
namespace Elementor;

use \Elementor\ElementsKit_Widget_Motion_Text_Handler as Handler;
use \ElementsKit_Lite\Modules\Controls\Controls_Manager as ElementsKit_Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class ElementsKit_Widget_Motion_Text extends Widget_Base {
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
        return 'https://wpmet.com/doc/motion-text/';
    }

	public function get_script_depends() {
		return ['ekit-pro-motion-text'];
	}

    protected function is_dynamic_content(): bool {
        return false;
    }
	protected function register_controls() {
		$this->start_controls_section(
			'ekit_motion_text_content_tab',
			[
				'label' => esc_html__( 'Content', 'elementskit' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_motion_text_content_text',
			[
				'label' => esc_html__( 'Title', 'elementskit' ),
				'type' => Controls_Manager::TEXTAREA,
				'rows' => 5,
				'default' => esc_html__( 'Default description', 'elementskit' ),
				'placeholder' => esc_html__( 'Type your title here', 'elementskit' ),
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'ekit_motion_text_sub_title_tag',
			[
				'label' => esc_html__( 'Title HTML Tag', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
					'h4' => 'H4',
					'h5' => 'H5',
					'h6' => 'H6',
					'div' => 'div',
					'span' => 'span',
					'p' => 'p',
				],
				'default' => 'h2',
			]
		);

		$this->add_control(
			'ekit_motion_text_sub_title_website_link',
			[
				'label' => esc_html__( 'Link', 'elementskit' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://wpmet.com', 'elementskit' ),
				'show_external' => true,
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
				],
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_responsive_control(
			'ekit_motion_text_sub_title_text_align',
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
				],
				'default' => 'center',
				'toggle' => true,
				'selectors' => [
					'{{WRAPPER}} .elementskit_motion_text_wraper' => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'ekit_motion_enable_switch',
			[
				'label' => esc_html__( 'Enable Animation', 'elementskit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'elementskit' ),
				'label_off' => esc_html__( 'Hide', 'elementskit' ),
				'return_value' => 'yes',
				'default' => 'no',
			]
		);
		$this->add_control(
			'ekit_motion_text_motions',
			[
				'label'   => esc_html__( 'Motion Animation', 'elementskit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'none'          => esc_html__( 'None', 'elementskit' ),

					// Reveal
					'RevealInTop'    => esc_html__( 'Reveal — Top', 'elementskit' ),
					'RevealInBottom' => esc_html__( 'Reveal — Bottom', 'elementskit' ),
					'RevealInLeft'   => esc_html__( 'Reveal — Left', 'elementskit' ),
					'RevealInRight'  => esc_html__( 'Reveal — Right', 'elementskit' ),

					// Entrance
					'FadeIn'         => esc_html__( 'Fade In', 'elementskit' ),
					'FadeInLeft'     => esc_html__( 'Fade In — Left', 'elementskit' ),
					'FadeInRight'    => esc_html__( 'Fade In — Right', 'elementskit' ),
					'FadeInTop'      => esc_html__( 'Fade In — Top', 'elementskit' ),
					'FadeInBottom'   => esc_html__( 'Fade In — Bottom', 'elementskit' ),

					// Attention
					'Beat'           => esc_html__( 'Beat', 'elementskit' ),
					'Magnify'        => esc_html__( 'Magnify', 'elementskit' ),
					'JoltZoom'       => esc_html__( 'Jolt Zoom', 'elementskit' ),
					'lightning'      => esc_html__( 'Lightning', 'elementskit' ),
					'RainDrop'       => esc_html__( 'Rain Drop', 'elementskit' ),

					// One After One
					'oaoRotateIn'    => esc_html__( 'One by One — Rotate In', 'elementskit' ),
					'oaoRotateXIn'   => esc_html__( 'One by One — Rotate X In', 'elementskit' ),
					'oaoRotateYIn'   => esc_html__( 'One by One — Rotate Y In', 'elementskit' ),
				],
				'default'   => 'none',
				'condition' => [
					'ekit_motion_enable_switch' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_motion_text_motions_spilit',
			[
				'label' => esc_html__( 'Spilit Text Animation', 'elementskit' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'no_spilit',
				'options' => [
					'no_spilit'  => esc_html__( 'No Spilit', 'elementskit' ),
					'char_based' => esc_html__( 'Letter Based', 'elementskit' ),
				],
				'condition' => [
					'ekit_motion_text_motions!' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight', 'oaoFadeIn', 'oaoFadeOut', 'oaoFlyIn', 'oaoFlyOut', 'oaoRotateIn', 'oaoRotateOut', 'oaoRotateXIn', 'oaoRotateXOut', 'oaoRotateYIn', 'oaoRotateYOut', 'none'],
					'ekit_motion_enable_switch' => 'yes'
				]
			]
		);

		$this->add_control(
			'ekit_motion_text_animation_duration_char_based',
			[
				'label' => esc_html__( 'Animation Duration By Charecter (in s)', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 100,
						'step' => .5,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
				'selectors' => [
					'{{WRAPPER}} .ekit_animation.ekit_char_based .ekit-letter' => 'animation-duration: {{SIZE}}s;',
				],
				'condition' => [
					'ekit_motion_text_motions_spilit' => 'char_based',
					'ekit_motion_text_motions!' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight', 'oaoFadeIn', 'oaoFadeOut', 'oaoFlyIn', 'oaoFlyOut', 'oaoRotateIn', 'oaoRotateOut', 'oaoRotateXIn', 'oaoRotateXOut', 'oaoRotateYIn', 'oaoRotateYOut', 'none'],
					'ekit_motion_enable_switch' => 'yes'
				]
			]
		);

		$this->add_control(
			'ekit_motion_text_animation_delay_char_based',
			[
				'label' => esc_html__( 'Animation Delay By Charecter (in ms)', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 1000,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'condition' => [
					'ekit_motion_text_motions_spilit' => 'char_based',
					'ekit_motion_text_motions!' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight', 'oaoFadeIn', 'oaoFadeOut', 'oaoFlyIn', 'oaoFlyOut', 'oaoRotateIn', 'oaoRotateOut', 'oaoRotateXIn', 'oaoRotateXOut', 'oaoRotateYIn', 'oaoRotateYOut', 'none'],
					'ekit_motion_enable_switch' => 'yes'
				]
			]
		);

		$this->add_control(
			'ekit_motion_text_animation_duration_no_spilit',
			[
				'label' => esc_html__( 'Animation Duration (in s)', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 100,
						'step' => .5,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
				'selectors' => [
					'{{WRAPPER}} .ekit_animation' => 'animation-duration: {{SIZE}}s;',
				],
				'condition' => [
					'ekit_motion_text_motions_spilit' => 'no_spilit',
					'ekit_motion_text_motions!' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight', 'none'],
					'ekit_motion_enable_switch' => 'yes'
				]
			]
		);

		$this->add_control(
			'ekit_motion_text_animation_duration_reveal',
			[
				'label' => esc_html__( 'Animation Duration Reveal (in s)', 'elementskit' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 100,
						'step' => .5,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
				'selectors' => [
					'{{WRAPPER}} .background_reveal_anim .elementkit_background_reveal_bg' => 'animation-duration: {{SIZE}}s;',
				],
				'condition' => [
					'ekit_motion_text_motions' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight',],
					'ekit_motion_enable_switch' => 'yes'
				]
			]
		);
		$this->add_control(
			'ekit_motion_infinite_loop',
			[
				'label'        => esc_html__( 'Infinite Loop', 'elementskit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'elementskit' ),
				'label_off'    => esc_html__( 'No', 'elementskit' ),
				'return_value' => 'yes',
				'default'      => 'no',
				// Only meaningful when animation is actually on
				'condition'    => [
					'ekit_motion_enable_switch'  => 'yes',
					'ekit_motion_text_motions!' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight'],
				],
				// When on, override animation-iteration-count to infinite
				'selectors'    => [
					'{{WRAPPER}} .ekit_animation.ekit-infinite .ekit-letter' => 'animation-iteration-count: infinite;',
					'{{WRAPPER}} .ekit_animation.ekit-infinite'              => 'animation-iteration-count: infinite;',
					'{{WRAPPER}} .ekit-infinite .elementkit_background_reveal_bg' => 'animation-iteration-count: infinite;',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_motion_text_style_tab',
			[
				'label' => esc_html__( 'Style', 'elementskit' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'ekit_motion_text_style_title_color',
			[
				'label' => esc_html__( 'Color', 'elementskit' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .ekit_motion_text_title' => 'color: {{VALUE}}',
					'{{WRAPPER}} .ekit_motion_text_title > a' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_motion_text_style_title_content_typography',
				'label' => esc_html__( 'Typography', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit_motion_text_title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'ekit_motion_text_style_title_text_shadow',
				'label' => esc_html__( 'Text Shadow', 'elementskit' ),
				'selector' => '{{WRAPPER}} .ekit_motion_text_title',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'ekit_motion_reveal_background',
				'label' => esc_html__( 'Background', 'elementskit' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [
					'image'
				],
				'selector' => '{{WRAPPER}} .background_reveal_anim .elementkit_background_reveal_bg',
				'condition' => [
					'ekit_motion_text_motions' => ['RevealInTop', 'RevealInBottom', 'RevealInLeft', 'RevealInRight',],
					'ekit_motion_enable_switch' => 'yes'
				]
			]
		);

		$this->end_controls_section();

		$this->insert_pro_message();
	}

	protected function render( ) {
        echo '<div class="ekit-wid-con" >';
            $this->render_raw();
        echo '</div>';
    }

	protected function render_raw() {
		$settings = $this->get_settings_for_display();

		$title_tag  = \Elementor\Utils::validate_html_tag( $settings['ekit_motion_text_sub_title_tag'] );
		$title_text = $settings['ekit_motion_text_content_text'];
		$url        = $settings['ekit_motion_text_sub_title_website_link']['url'];

		// Classes & Conditions — guard missing or null settings to avoid warnings
		$motion_setting = isset( $settings['ekit_motion_text_motions'] ) ? (string) $settings['ekit_motion_text_motions'] : '';
		$motion_spilit  = isset( $settings['ekit_motion_text_motions_spilit'] ) ? (string) $settings['ekit_motion_text_motions_spilit'] : '';
		$enable_switch  = isset( $settings['ekit_motion_enable_switch'] ) ? $settings['ekit_motion_enable_switch'] : 'no';
		$infinite_loop  = isset( $settings['ekit_motion_infinite_loop'] ) ? $settings['ekit_motion_infinite_loop'] : 'no';

		$motion_enabled = (
			$enable_switch === 'yes' &&
			$motion_setting !== 'none'
		);

		// Check for specific animation types (only run preg_match when we have a string)
		$is_reveal   = $motion_setting !== '' && preg_match( '/^Reveal/', $motion_setting ) === 1;
		$is_onebyone = $motion_setting !== '' && preg_match( '/^oao/', $motion_setting ) === 1;

		// Animation Class
		$animation_class = $motion_enabled ? 'ekit-' . $motion_setting : '';
		$split_class     = ( ! $is_reveal && $motion_enabled ) ? 'ekit_' . $motion_spilit : '';
		$infinite_class  = ( $infinite_loop === 'yes' && $motion_enabled ) ? 'ekit-infinite' : '';
		$wrapper_class   = $is_reveal ? 'background_reveal_anim' : '';

		$char_class = ( $is_onebyone || $motion_spilit === 'char_based' ) && $motion_enabled ? 'ekit_char_based' : '';

		// Delay for Char Based Animation
		$delay = ( $motion_spilit === 'char_based' && $motion_enabled ) ? ( $settings['ekit_motion_text_animation_delay_char_based']['size'] ?? '' ) : '';

		// Attributes
		$this->add_render_attribute('title', 'class', [
			'ekit_motion_text_title',
			$motion_enabled ? 'ekit_animation' : '',
			$split_class,
			$char_class,
			$infinite_class,
		]);

		$this->add_render_attribute('title', 'data-ekit-animation-delay-s', '10');
		$this->add_render_attribute('title', 'data-animate-class', $animation_class);

		$this->add_render_attribute('text', 'class', 'ekit_motion_text');
		$this->add_render_attribute('text', 'data-ekit-animation-delay', $delay);

		if ( ! empty( $url ) ) {
			$this->add_link_attributes( 'link', $settings['ekit_motion_text_sub_title_website_link'] );
		}
		?>

		<div class="elementskit_motion_text_wraper">
			<div class="ekit_motion_text_inner_wraper <?php echo esc_attr( $wrapper_class ); ?>">

				<<?php echo esc_attr( $title_tag ); ?> <?php echo $this->get_render_attribute_string('title'); ?>>

					<?php if ( ! empty( $url ) ) : ?>
						<a <?php echo $this->get_render_attribute_string('link'); ?>>
					<?php endif; ?>

					<span <?php echo $this->get_render_attribute_string('text'); ?>>
						<?php echo wp_kses( $title_text, \ElementsKit_Lite\Utils::get_kses_array() ); ?>
					</span>

					<?php if ( ! empty( $url ) ) : ?>
						</a>
					<?php endif; ?>

				</<?php echo esc_attr( $title_tag ); ?>>

				<?php if ( $is_reveal ) : ?>
					<div class="elementkit_background_reveal_bg"></div>
				<?php endif; ?>

			</div>
		</div>

		<?php
	}
}