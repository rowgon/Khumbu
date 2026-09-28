<?php

namespace Elementor;

use \Elementor\ElementsKit_Widget_Event_Calendar_Handler as Handler;

defined('ABSPATH') || exit;

class ElementsKit_Widget_Event_Calendar extends Widget_Base
{
	use \ElementsKit_Lite\Widgets\Widget_Notice;

	public function get_name()
	{
		return Handler::get_name();
	}

	public function get_title()
	{
		return Handler::get_title();
	}

	public function get_icon()
	{
		return Handler::get_icon();
	}

	public function get_categories()
	{
		return Handler::get_categories();
	}

	public function get_keywords()
	{
		return Handler::get_keywords();
	}

	public function get_help_url()
	{
		return 'https://wpmet.com/doc/event-calendar/';
	}

	public function get_script_depends()
	{
		return ['full-calendar', 'full-calendar-locales', 'full-calendar-google', 'ekit-pro-event-calendar'];
	}

	public function __construct($data = [], $args = null)
	{
		add_action('elementor/editor/after_enqueue_scripts', function () {
			wp_enqueue_script('google-calendar-delete', Handler::get_url() . 'assets/script.js', ['elementor-editor'], \ElementsKit_Lite::version(), true);

			$config['restUrl'] = get_rest_url();
			$config['nonce']    = wp_create_nonce('wp_rest');

			Utils::print_js_config('google-calendar-delete', 'googleCalendarDeleteConfig', $config);
		});

		parent::__construct($data, $args);
	}

	protected function register_controls()
	{

		$this->start_controls_section(
			'ekit_event_calendar_section',
			[
				'label' => esc_html__('Events', 'elementskit'),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_event_calendar_source',
			[
				'label'       => esc_html__('Source', 'elementskit'),
				'label_block' => false,
				'type'        => Controls_Manager::SELECT,
				'default'     => 'manual',
				'options'     => [
					'manual'         => esc_html__('Manual', 'elementskit'),
					'google_calendar' => esc_html__('Google Calendar', 'elementskit'),
					'the_events_calendar' => esc_html__('The Event Calendar', 'elementskit'),
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_event_popup_button',
			[
				'label'        => esc_html__('Popup Button Text', 'elementskit'),
				'label_block'  => false,
				'type'         => Controls_Manager::TEXT,
				'default'      => 'Register for Event',
				'condition'    => [
					'ekit_event_calendar_source' => ['the_events_calendar', 'google_calendar'],
				],
			]
		);

		if (! class_exists('Tribe__Events__Main')) {
			$this->add_control(
				'ekit_the_events_calendar_notice',
				[
					'label' => esc_html__('Notice', 'elementskit'),
					'type' => Controls_Manager::RAW_HTML,
					'raw' => sprintf(
						'<span style="background-color: #FFF8E5; border-left: 3px solid #FFB900; display: inline-block; padding: 5px;"> <b>%1$s</b> %2$s <a %3$s> %4$s </a> %5$s <span>',
						esc_html__('The Events Calendar', 'elementskit'),
						esc_html__('plugin is currently not installed or activated on your website. Please ensure that you have installed and activated', 'elementskit'),
						'href="plugin-install.php?s=the-events-calendar&tab=search&type=term" target="_blank"',
						esc_html__('The Events Calendar', 'elementskit'),
						esc_html__('plugin before proceeding.', 'elementskit'),

					),
					'content_classes' => '',
					'show_label' => false,
					'label_block' => false,
					//'separator' => 'after',
					'condition' => [
						'ekit_event_calendar_source' => 'the_events_calendar'
					],
				]
			);
		}

		$this->add_control(
			'ekit_event_calendar_full_day',
			[
				'label'        => esc_html__('Full Day Event', 'elementskit'),
				'type'         => Controls_Manager::SWITCHER,
				'label_block'  => false,
				'return_value' => 'yes',
				'condition'      => [
					'ekit_event_calendar_source' => 'manual',
				],
			]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'ekit_event_calendar_title',
			[
				'label'       => esc_html__('Title', 'elementskit'),
				'type'        => Controls_Manager::TEXTAREA,
				'label_block' => true,
				'rows'        => 3,
				'default'     => esc_html__('Event Title', 'elementskit'),
				'dynamic'     => [
					'active'  => true,
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_title_size',
			[
				'label' => esc_html__('Title HTML Tag', 'elementskit'),
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

		$repeater->add_control(
			'ekit_event_calendar_description',
			[
				'label'      => esc_html__('Description', 'elementskit'),
				'show_label' => true,
				'rows'       => 10,
				'type'       => Controls_Manager::WYSIWYG,
				'default'    => esc_html__('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', 'elementskit'),
				'dynamic'    => [
					'active' => true,
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_speaker',
			[
				'label'       => esc_html__('Guest / Speaker', 'elementskit'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__('John Doe', 'elementskit'),
				'dynamic'     => [
					'active'  => true,
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_location',
			[
				'label'       => esc_html__('Location', 'elementskit'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__('123 Park Rd, WC USA', 'elementskit'),
				'dynamic'     => [
					'active'  => true,
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_host',
			[
				'label'       => esc_html__('Host', 'elementskit'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__('Jhon Doe', 'elementskit'),
				'dynamic'     => [
					'active'  => true,
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_event_banner_image',
			[
				'label'   => esc_html__('Event Banner', 'elementskit'),
				'type'    => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name'      => 'ekit_event_calendar_thumbnail',
				'label'     => 'Thumbnail Size',
				'default'   => 'thumbnail',
				'separator' => 'before',
				'exclude'   => [
					'custom',
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_redirect_event_link',
			[
				'label'        => esc_html__('Redirect to Event Link', 'elementskit'),
				'type'         => Controls_Manager::SWITCHER,
				'label_block'  => false,
				'return_value' => 'yes',
				'default' => '',
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_event_link',
			[
				'label'         => esc_html__('Event Link', 'elementskit'),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__('https://example.com', 'elementskit'),
				'options'       => ['is_external', 'nofollow', 'custom_attributes'],
				'show_external' => true,
				// 'custom_attributes' => false,
				'condition'      => [
					'ekit_event_calendar_redirect_event_link' => 'yes',
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_event_link_button_text',
			[
				'label'        => esc_html__('Popup Button Text', 'elementskit'),
				'label_block'  => false,
				'type'         => Controls_Manager::TEXT,
				'default'      => 'Register for Event',
				'condition'      => [
					'ekit_event_calendar_redirect_event_link' => 'yes',
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_start_date',
			[
				'label'     => esc_html__('Start Date', 'elementskit'),
				'type'      => Controls_Manager::DATE_TIME,
				'picker_options' => ['enableTime' => false],
				'default'   => date('Y-m-d', current_time('timestamp', 0)),
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_end_date',
			[
				'label'     => esc_html__('End Date', 'elementskit'),
				'type'      => Controls_Manager::DATE_TIME,
				'picker_options' => ['enableTime' => false],
				'default'   => date('Y-m-d', strtotime('+59 minute', current_time('timestamp', 0))),
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_single_event_style',
			[
				'label'        => esc_html__('Single Event Style', 'elementskit'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'elementskit'),
				'label_off'    => esc_html__('No', 'elementskit'),
				'return_value' => 'yes',
				'default'      => 'no',
				'separator'    => 'after',
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_text_color',
			[
				'label'     => esc_html__('Text Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}' => 'color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}.fc-h-event .fc-event-main' => '--fc-event-text-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}.fc-h-event .fc-event-title-container .fc-event-title' => '--fc-event-text-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}.fc-h-event .fc-event-title-container .fc-event-title' => 'color: {{VALUE}}',
				],
				'condition'   => [
					'ekit_event_calendar_single_event_style' => 'yes',
				],
				'render_type' => 'template',
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_bg_color',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}.fc-h-event' => '--fc-event-bg-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}.fc-h-event' => '--fc-event-border-color: {{VALUE}}',
				],
				'condition' => [
					'ekit_event_calendar_single_event_style' => 'yes',
				],
			]
		);

		$repeater->add_control(
			'ekit_event_calendar_dot_color',
			[
				'label'     => esc_html__('Dot Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}} .fc-daygrid-event-dot' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness {{CURRENT_ITEM}} .fc-list-event-dot' => 'border-color: {{VALUE}};',
				],
				'condition' => [
					'ekit_event_calendar_single_event_style' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_manual_event_lists',
			[
				'type'       => Controls_Manager::REPEATER,
				'fields'     => $repeater->get_controls(),
				'show_label' => false,
				'default'    => [
					[
						'ekit_event_calendar_title'       => esc_html__('Event Title', 'elementskit'),
						'ekit_event_calendar_description' => esc_html__('Event Description', 'elementskit'),
					],
				],
				'title_field' => '{{{ ekit_event_calendar_title }}} &nbsp;—&nbsp; {{{ ekit_event_calendar_description }}}',
				'condition'   => [
					'ekit_event_calendar_source' => 'manual',
				],
			]
		);

		$time_schedules = new Repeater();
		$time_schedules->add_control(
			'ekit_event_calendar_schedules_title',
			[
				'label'       => esc_html__('Schedule Title', 'elementskit'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => esc_html__('Schedule Title', 'elementskit'),
				'dynamic'     => [
					'active'  => true,
				],
			]
		);

		$time_schedules->add_control(
			'ekit_event_calendar_start_time',
			[
				'label'     => esc_html__('Start Time', 'elementskit'),
				'type'      => Controls_Manager::DATE_TIME,
				'default'   => date('Y-m-d\TH:i', current_time('timestamp', 0)),
			]
		);

		$time_schedules->add_control(
			'ekit_event_calendar_end_time',
			[
				'label'     => esc_html__('End Time', 'elementskit'),
				'type'      => Controls_Manager::DATE_TIME,
				'default'   => date('Y-m-d\TH:i', strtotime('+59 minute', current_time('timestamp', 0))),

			]
		);

		$time_schedules->add_control(
			'ekit_event_calendar_schedules_event_style',
			[
				'label'        => esc_html__('Single Schedules Style', 'elementskit'),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__('Yes', 'elementskit'),
				'label_off'    => esc_html__('No', 'elementskit'),
				'return_value' => 'yes',
				'default'      => 'no',
				'separator'    => 'after',
			]
		);

		$time_schedules->add_control(
			'ekit_event_calendar_schedules_bg_color',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}} .fc-event-main' => '--fc-event-bg-color: {{VALUE}}',
				],
				'default' => '#ffff',
				'condition'   => [
					'ekit_event_calendar_schedules_event_style' => 'yes',
				],
			]
		);

		$time_schedules->add_control(
			'ekit_event_calendar_schedules_text_color',
			[
				'label'     => esc_html__('Text Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}}' => 'color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}} .fc-event-main' => '--fc-event-text-color: {{VALUE}}',
				],
				'default' => '#ffff',
				'condition'   => [
					'ekit_event_calendar_schedules_event_style' => 'yes',
				],
			]
		);

		$time_schedules->add_control(
			'ekit_event_calendar_schedules_dot_color',
			[
				'label'     => esc_html__('Dot Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}} .fc-daygrid-event-dot' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper {{CURRENT_ITEM}} .fc-list-event-dot' => 'border-color: {{VALUE}};',
				],
				'default' => '#FC019E',
				'condition' => [
					'ekit_event_calendar_schedules_event_style' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_manual_time_schedules',
			[
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $time_schedules->get_controls(),
				'show_label'  => false,
				'default'     => [[
					'ekit_event_calendar_schedules_title'  => esc_html__('Schedule Title', 'elementskit'),
				]],
				'title_field' => '{{{ ekit_event_calendar_schedules_title }}}',
				'condition'   => [
					'ekit_event_calendar_source' => 'manual',
					'ekit_event_calendar_full_day' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_google_calendar_section',
			[
				'label'     => __('Google Event', 'elementskit'),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'ekit_event_calendar_source' => 'google_calendar',
				],
			]
		);

		$this->add_control(
			'ekit_event_google_calendar_api_key',
			[
				'label'       => __('API Key', 'elementskit'),
				'label_block' => true,
				'type'        => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
				'default'     => '',
				'description' => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url('https://console.cloud.google.com/apis/credentials'),
					esc_html__('Get API Key', 'elementskit')
				),
			]
		);

		$this->add_control(
			'ekit_event_google_calendar_id',
			[
				'label'       => __('Calendar ID', 'elementskit'),
				'label_block' => true,
				'type'        => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'ai' => [
					'active' => false,
				],
				'default'     => '',
				'description' => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url('https://calendar.google.com/calendar/u/0/r'),
					esc_html__('Find Calendar ID', 'elementskit')
				),
			]
		);

		$this->add_control(
			'ekit_event_calendar_expiration_time',
			[
				'label' => esc_html__('Cache Expiration Time (hr)', 'elementskit'),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 72,
				'description' => 'Set expiration limits for enhanced  API data management for hourly basis. By setting expiration limits, you can control the duration of API access, ensuring that data remains secure and up-to-date.',
				'step' => 0.5,
				'default' => 12,
			]
		);

		// Delete cache
		$this->add_control(
			'ekit_event_google_calendar_delete_cache',
			[
				'label' => esc_html__('Delete Cache', 'elementskit'),
				'type'        => Controls_Manager::BUTTON,
				'button_type' => 'default',
				'text'        => esc_html__('Delete Cache', 'elementskit'),
				'event'       => 'ekit:editor:google_calendar_delete_cache',
				'condition' => [
					'ekit_event_google_calendar_id!' => '',
					'ekit_event_google_calendar_api_key!' => ''
				]
			]
		);

		$this->end_controls_section();

		//the events calendar control
		if (class_exists('Tribe__Events__Main')) {

			$this->start_controls_section(
				'ekit_event_section_the_events_calendar',
				[
					'label'     => esc_html__('The Event Calendar', 'elementskit'),
					'tab'       => Controls_Manager::TAB_CONTENT,
					'condition' => [
						'ekit_event_calendar_source' => 'the_events_calendar',
					],
				]
			);

			$this->add_control(
				'ekit_event_the_events_calendar_source',
				[
					'label'       => esc_html__('Get Events By', 'elementskit'),
					'type'        => Controls_Manager::SELECT,
					'label_block' => true,
					'default'     => ['all_event'],
					'options'     => [
						'all_event'            => esc_html__('All Event', 'elementskit'),
						'category'       => esc_html__('By Category', 'elementskit'),
						'selected_event' => esc_html__('Selected Event', 'elementskit'),
					],
				]
			);

			$this->add_control(
				'ekit_event_the_events_calendar_category',
				[
					'label'       => esc_html__('Event Category', 'elementskit'),
					'label_block' => true,
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'options'     => self::get_the_event_calendar_cats(),
					'condition'   => [
						'ekit_event_the_events_calendar_source' => 'category',
					],
				]
			);

			$this->add_control(
				'ekit_event_the_events_calendar_selected',
				[
					'label'       => esc_html__('Select Events', 'elementskit'),
					'label_block' => true,
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'options'     => self::get_the_event_tribe_events(),
					'condition'   => [
						'ekit_event_the_events_calendar_source' => 'selected_event',
					],
				]
			);

			$this->add_control(
				'ekit_event_the_events_calendar_item',
				[
					'label'     => esc_html__('Event Item', 'elementskit'),
					'type'      => Controls_Manager::NUMBER,
					'min'       => 1,
					'default'   => 12,
					'condition' => [
						'ekit_event_the_events_calendar_source!' => 'selected_event',
					],
				]
			);

			$this->end_controls_section();
		}
		$this->start_controls_section(
			'ekit_event_calendar_settings_section',
			[
				'label' => esc_html__(' Calendar Settings', 'elementskit'),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'ekit_event_calendar_language_code_list',
			[
				'label' => esc_html__('Language', 'elementskit'),
				'type' => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple' => false,
				'options' => Handler::language_codes(),
				'default' => 'en',
				'render_type' => 'template',
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_header_toolbar',
			[
				'label' => esc_html__('Header Toolbar', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Show', 'elementskit'),
				'label_off' => esc_html__('Hide', 'elementskit'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_header_left_toolbar',
			[
				'label' => esc_html__('Left Toolbar', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Show', 'elementskit'),
				'label_off' => esc_html__('Hide', 'elementskit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition'   => [
					'ekit_event_calendar_hide_header_toolbar' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_header_right_toolbar',
			[
				'label' => esc_html__('Right Toolbar', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Show', 'elementskit'),
				'label_off' => esc_html__('Hide', 'elementskit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition'   => [
					'ekit_event_calendar_hide_header_toolbar' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_show_right_left_toolbar_together',
			[
				'label' => esc_html__('Left Toolbar Position', 'elementskit'),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'left'  => esc_html__('Left', 'elementskit'),
					'right' => esc_html__('Right', 'elementskit'),
				],
				'default' => 'left',
			]
		);

		$condition = [
			'ekit_event_calendar_hide_header_right_toolbar' => 'yes',
			'ekit_event_calendar_hide_header_toolbar' => 'yes'
		];

		$this->add_control(
			'ekit_event_calendar_hide_day',
			[
				'label' => esc_html__('Hide Day', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Hide', 'elementskit'),
				'label_off' => esc_html__('Show', 'elementskit'),
				'return_value' => 'yes',
				'default' => '',
				'condition'   => $condition,
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_week',
			[
				'label' => esc_html__('Hide Week', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Hide', 'elementskit'),
				'label_off' => esc_html__('Show', 'elementskit'),
				'return_value' => 'yes',
				'default' => '',
				'condition'   => $condition,
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_month',
			[
				'label' => esc_html__('Hide Month', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Hide', 'elementskit'),
				'label_off' => esc_html__('Show', 'elementskit'),
				'return_value' => 'yes',
				'default' => '',
				'condition'   => $condition,
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_year',
			[
				'label' => esc_html__('Hide Year', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Hide', 'elementskit'),
				'label_off' => esc_html__('Show', 'elementskit'),
				'return_value' => 'yes',
				'default' => '',
				'condition'   => $condition,
			]
		);

		$this->add_control(
			'ekit_event_calendar_hide_list',
			[
				'label' => esc_html__('Hide List', 'elementskit'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Hide', 'elementskit'),
				'label_off' => esc_html__('Show', 'elementskit'),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition'   => $condition,
			]
		);

		$this->add_control(
			'ekit_event_calendar_active',
			[
				'label'   => esc_html__('Active Calendar', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'timeGridDay'  => esc_html__('Day', 'elementskit'),
					'timeGridWeek' => esc_html__('Week', 'elementskit'),
					'dayGridMonth' => esc_html__('Month', 'elementskit'),
					'multiMonthYear' => esc_html__('Year', 'elementskit'),
					'listWeek'    => esc_html__('List', 'elementskit'),
				],
				'default' => 'dayGridMonth',
				'render_type' => 'template',
			]
		);

		$this->add_control(
			'ekit_event_calendar_first_day',
			[
				'label'   => esc_html__('First Day of Week', 'elementskit'),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'0' => esc_html__('Sunday', 'elementskit'),
					'1' => esc_html__('Monday', 'elementskit'),
					'2' => esc_html__('Tuesday', 'elementskit'),
					'3' => esc_html__('Wednesday', 'elementskit'),
					'4' => esc_html__('Thursday', 'elementskit'),
					'5' => esc_html__('Friday', 'elementskit'),
					'6' => esc_html__('Saturday', 'elementskit'),
				],
				'default' => '0',
			]
		);

		$this->add_control(
			'ekit_event_calendar_show_event_popup',
			[
				'label'        => esc_html__('Show Event Popup', 'elementskit'),
				'label_block'  => false,
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'   => esc_html__('The popup will be not enabled and You will be redirected to the Event Link page instead.', 'elementskit'),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_style',
			[
				'label' => esc_html__('Left Toolbar', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_event_calendar_hide_header_toolbar' => 'yes',
					'ekit_event_calendar_hide_header_left_toolbar' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_left_toolbar_buttons_wrapper',
			[
				'label'     => esc_html__('Wrapper', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
			]
		);
		//add a slider control for space between buttons in toolbar when left toolbar position is right
		$this->add_responsive_control(
			'ekit_event_calendar_left_toolbar_space_between_buttons',
			[
				'label'      => esc_html__('Space Between', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'default'    => [
					'size' => 3,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
				'condition'   => [
					'ekit_event_calendar_show_right_left_toolbar_together' => 'right',
				],
			]
		);

		// Background Color
		$this->add_control(
			'ekit_event_calendar_left_toolbar_bg_color',
			[
				'label' => esc_html__('Background Color', 'elementskit'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child' => 'background-color: {{VALUE}};',
				],
			]
		);

		// Border
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_left_toolbar_border',
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child,
                       {{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child',
			]
		);

		// Border Radius
		$this->add_responsive_control(
			'ekit_event_calendar_left_toolbar_border_radius',
			[
				'label' => esc_html__('Border Radius', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Padding
		$this->add_responsive_control(
			'ekit_event_calendar_left_toolbar_padding',
			[
				'label' => esc_html__('Padding', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Header Buttons style
		$this->add_control(
			'ekit_event_calendar_buttons_heading',
			[
				'label'     => esc_html__('Button', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_space',
			[
				'label'      => esc_html__('Space Between', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'default'    => [
					'size' => 3,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child .fc-button-group' => 'gap: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_buttons_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-button, {{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes  .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'ekit_event_calendar_buttons_border',
				'label'    => esc_html__('Border', 'elementskit'),
				'exclude'  => ['color'],
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-button, {{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-button',
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'default' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->add_responsive_control(
			'ekit_event_calendar_buttons_padding',
			[
				'label' => esc_html__('Padding', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'default' => [
					'top' => 5,
					'right' => 5,
					'bottom' => 5,
					'left' => 5,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],

			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_margin',
			[
				'label' => esc_html__('Margin', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		// Header Arrow style
		$this->add_control(
			'ekit_event_calendar_buttons_arrow',
			[
				'label'     => esc_html__('Arrow', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_arrow_size',
			[
				'label' => esc_html__('Size', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vw', 'custom'],
				'range' => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'default' => [
					'size' => 18,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button .fc-icon' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button .fc-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'ekit_event_calendar_arrow_color_tabs'
		);

		// normal arrow
		$this->start_controls_tab(
			'ekit_event_calendar_arrow_normal',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_arrow_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fff',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button:is(.fc-prev-button, .fc-next-button)' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button:is(.fc-prev-button, .fc-next-button)' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_arrow_background',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3788d8',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button:is(.fc-prev-button, .fc-next-button)' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button:is(.fc-prev-button, .fc-next-button)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_arrow_border',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3788d8',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button:is(.fc-prev-button, .fc-next-button)' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button:is(.fc-prev-button, .fc-next-button)' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// hover arrow
		$this->start_controls_tab(
			'ekit_event_calendar_arrow_hover',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_arrow_color_hover',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button:is(.fc-prev-button, .fc-next-button):hover' => 'color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button:is(.fc-prev-button, .fc-next-button):hover' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_arrow_background_hover',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button:is(.fc-prev-button, .fc-next-button):hover' => 'background-color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button:is(.fc-prev-button, .fc-next-button):hover' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_arrow_border_color_hover',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-button:is(.fc-prev-button, .fc-next-button):hover' => 'border-color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-button:is(.fc-prev-button, .fc-next-button):hover' => 'border-color: {{VALUE}}!important;',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'ekit_event_calendar_buttons_today_heading',
			[
				'label'     => esc_html__('Today', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_today_button_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', 'rem'],
				'allowed_dimensions' => ['left', 'right'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button' => 'padding-left: {{LEFT}}{{UNIT}}; padding-right: {{RIGHT}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button' => 'padding-left: {{LEFT}}{{UNIT}}; padding-right: {{RIGHT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'ekit_event_calendar_today_button_tabs'
		);
		// normal today button
		$this->start_controls_tab(
			'ekit_event_calendar_today_button_normal',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);
		$this->add_control(
			'ekit_event_calendar_buttons_color_today',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fff',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button' => 'color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_background_today',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3788d8',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button' => 'background-color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_border_color_today',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3788d8',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button' => 'border-color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button' => 'border-color: {{VALUE}}!important;',
				],
			]
		);

		$this->end_controls_tab();
		// hover today button
		$this->start_controls_tab(
			'ekit_event_calendar_today_button_hover',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);
		$this->add_control(
			'ekit_event_calendar_today_color_hover',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button:hover' => 'color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button:hover' => 'color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_today_background_hover',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button:hover' => 'background-color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button:hover' => 'background-color: {{VALUE}}!important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_today_border_color_hover',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-no .fc-toolbar-chunk:first-child button.fc-today-button:hover' => 'border-color: {{VALUE}}!important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar.ekit-custom-toolbar-yes .fc-toolbar-chunk:last-child .fc-button-group:first-child button.fc-today-button:hover' => 'border-color: {{VALUE}}!important;',
				],
			]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_style_center',
			[
				'label' => esc_html__('Center Toolbar (Title)', 'elementskit'),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_event_calendar_hide_header_toolbar' => 'yes',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_title_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_title_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-title',
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_center_chunk_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:nth-child(2)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_style_right',
			[
				'label' => esc_html__('Right Toolbar', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_event_calendar_hide_header_toolbar' => 'yes',
					'ekit_event_calendar_hide_header_right_toolbar' => 'yes',
				],
			]
		);
		$this->add_control(
			'ekit_event_calendar_right_toolbar_buttons_wrapper',
			[
				'label'     => esc_html__('Wrapper', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
			]
		);

		// Background Color
		$this->add_control(
			'ekit_event_calendar_right_toolbar_bg_color',
			[
				'label' => esc_html__('Background Color', 'elementskit'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child' => 'background-color: {{VALUE}};',
				],
			]
		);

		// Border
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_right_toolbar_border',
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child',
			]
		);

		// Border Radius
		$this->add_responsive_control(
			'ekit_event_calendar_right_toolbar_border_radius',
			[
				'label' => esc_html__('Border Radius', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		// Padding
		$this->add_responsive_control(
			'ekit_event_calendar_right_toolbar_padding',
			[
				'label' => esc_html__('Padding', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);


		// Header Buttons style
		$this->add_control(
			'ekit_event_calendar_buttons_heading_right',
			[
				'label'     => esc_html__('Button', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_space_right',
			[
				'label'      => esc_html__('Space Between', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'default'    => [
					'size' => 3,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child' => 'gap: {{SIZE}}{{UNIT}};'
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_buttons_typography_right',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'ekit_event_calendar_buttons_border_right',
				'label'    => esc_html__('Border', 'elementskit'),
				'exclude'  => ['color'],
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button',
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_border_radius_right',
			[
				'label'      => esc_html__('Border Radius', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'default' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_padding_right',
			[
				'label' => esc_html__('Padding', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'default' => [
					'top' => 5,
					'right' => 5,
					'bottom' => 5,
					'left' => 5,
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_buttons_margin_right',
			[
				'label' => esc_html__('Margin', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'ekit_event_calendar_buttons_color_right'
		);
		// normal
		$this->start_controls_tab(
			'ekit_event_calendar_buttons_normal_right',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_color_normal_right',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#fff',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button:not(.fc-next-button, .fc-prev-button, .fc-today-button)' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_background_normal_right',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3788d8',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button:not(.fc-next-button, .fc-prev-button, .fc-today-button)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_border_color_normal_right',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3788d8',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button:not(.fc-next-button, .fc-prev-button, .fc-today-button)' => 'border-color: {{VALUE}}',
				],
			]
		);
		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'ekit_event_calendar_buttons_hover_right',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_color_hover_right',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button:not(.fc-next-button, .fc-prev-button, .fc-today-button):hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_background_hover_right',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button:not(.fc-next-button, .fc-prev-button, .fc-today-button):hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_border_color_hover_right',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button:not(.fc-next-button, .fc-prev-button, .fc-today-button):hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// active
		$this->start_controls_tab(
			'ekit_event_calendar_buttons_active_right',
			[
				'label' => esc_html__('Active', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_color_active_right',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button.fc-button-active' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_background_active_right',
			[
				'label'     => esc_html__('Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button.fc-button-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_buttons_border_color_active_right',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:last-child .fc-button-group:last-child button.fc-button.fc-button-active' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_calendar_header',
			[
				'label' => esc_html__('Calendar Header', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			// calendar slider control
			'ekit_event_calendar_header_height',
			[
				'label' => esc_html__('Height', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1000,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-scrollgrid table.fc-col-header tr th.fc-col-header-cell' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-list-table tbody tr.fc-list-day:first-child th div.fc-list-day-cushion' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'ekit_event_calendar_header_typography',
				'label' => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper table.fc-scrollgrid table.fc-col-header tr th.fc-col-header-cell .fc-scrollgrid-sync-inner a.fc-col-header-cell-cushion',
			]
		);
		$this->add_responsive_control(
			'ekit_event_calendar_header_horizontal_alignment',
			[
				'label' => esc_html__('Horizontal Alignment', 'elementskit'),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon' => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__('Center', 'elementskit'),
						'icon' => 'eicon-h-align-center',
					],
					'flex-end' => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon' => 'eicon-h-align-right',
					],
				],
				'default' => 'center',
				'selectors' => [
					'{{WRAPPER}} table.fc-col-header th.fc-col-header-cell .fc-scrollgrid-sync-inner' => 'justify-content: {{VALUE}};',
				],
			]
		);
		$this->add_responsive_control(
			'ekit_event_calendar_header_vertical_alignment',
			[
				'label' => esc_html__('Vertical Alignment', 'elementskit'),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__('Top', 'elementskit'),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => esc_html__('Middle', 'elementskit'),
						'icon' => 'eicon-v-align-middle',
					],
					'flex-end' => [
						'title' => esc_html__('Bottom', 'elementskit'),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'default' => 'center',
				'selectors' => [
					'{{WRAPPER}} table.fc-col-header th.fc-col-header-cell .fc-scrollgrid-sync-inner' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_header_bg_color',
			[
				'label'     => esc_html__('Background Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-scrollgrid table.fc-col-header tr th.fc-col-header-cell' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-scrollgrid table.fc-col-header tr th.fc-timegrid-axis' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-list-table tbody tr.fc-list-day:first-child th div.fc-list-day-cushion' => 'background-color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'ekit_event_calendar_header_title_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-scrollgrid table.fc-col-header tr th.fc-col-header-cell .fc-scrollgrid-sync-inner a.fc-col-header-cell-cushion' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper table.fc-list-table tbody tr.fc-list-day:first-child th div.fc-list-day-cushion' => 'color: {{VALUE}};',
				],
			]
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'ekit_event_calendar_section_calendar',
			[
				'label' => esc_html__('Calendar', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_day_number_alignment',
			[
				'label'   => esc_html__('Date Alignment', 'elementskit'),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'flex-end' => [
						'title' => esc_html__('Left', 'elementskit'),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => esc_html__('Center', 'elementskit'),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-start'   => [
						'title' => esc_html__('Right', 'elementskit'),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'flex-start',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-daygrid-day .fc-daygrid-day-top' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view > table' => 'color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view table.fc-list-table' => 'color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view .fc-daygrid-day-top a' => 'color: {{VALUE}}',
				],
			]
		);
		$this->add_control(
			'ekit_event_calendar_buttons_today_color',
			[
				'label'     => esc_html__('Today Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-scrollgrid-sync-table tbody tr td.fc-day-today a.fc-daygrid-day-number' => 'color: {{VALUE}} !important',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_background_odd',
			[
				'label'     => esc_html__('Background (Odd Rows)', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					// DayGrid / general tables
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view table tbody tr:nth-child(odd)' => 'background-color: {{VALUE}};',

					// List view
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-list-table tbody tr:nth-child(odd)' => 'background-color: {{VALUE}};',

					// Multi-month containers
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-multimonth-month:nth-child(odd)' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_background_even',
			[
				'label'     => esc_html__('Background (Even Rows)', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					// DayGrid / general tables
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view table tbody tr:nth-child(even)' => 'background-color: {{VALUE}};',

					// List view
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-list-table tbody tr:nth-child(even)' => 'background-color: {{VALUE}};',

					// Multi-month containers
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-multimonth-month:nth-child(even)' => 'background-color: {{VALUE}};',
				],
			]
		);


		$this->add_control(
			'ekit_event_calendar_buttons_today_background',
			[
				'label'     => esc_html__('Today Background', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-daygrid-day.fc-day-today' => '--fc-today-bg-color: {{VALUE}}; background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-timegrid-col.fc-day-today' => '--fc-today-bg-color: {{VALUE}}; background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-multimonth-day.fc-day-today' => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-list-table tr.fc-list-day.fc-day-today th' => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-list-table tr.fc-list-event.fc-day-today td' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_border_color',
			[
				'label'     => esc_html__('Border Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#CFCFDA',
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness *' => 'border-color: {{VALUE}}',
				],
			]
		);

		$calendar_selectors = [
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-col-header-cell-cushion',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-daygrid-day-number',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-daygrid-more-link',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-multimonth-title',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-timegrid-axis-cushion',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-timegrid-slot-label-cushion',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-list-day-text',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-list-event-time',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-list-event-title',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-view-harness .fc-list-day-side-text',
		];
		$calendar_selector = implode(',', $calendar_selectors);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => $calendar_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'ekit_event_calendar_box_shadow',
				'label'    => esc_html__('Box Shadow', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-calendar-wrapper .fc-view table.fc-scrollgrid,
				{{WRAPPER}} .ekit-calendar-wrapper .fc-view table.fc-list-table',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_event',
			[
				'label' => esc_html__('Event', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_event_border',
				'selector' => '
			{{WRAPPER}} .ekit-calendar-wrapper .fc-view table tbody tr .fc-daygrid-day-events .fc-daygrid-event-harness a.fc-event,
			{{WRAPPER}} .ekit-calendar-wrapper .fc-popover .fc-popover-body a.fc-event
		',
			]
		);
		$this->add_responsive_control(
			'ekit_event_calendar_row_border_radius',
			[
				'label' => esc_html__('Border Radius', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-view table tbody tr .fc-daygrid-day-events .fc-daygrid-event-harness a.fc-event' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_event_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-daygrid-event .fc-event-time' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-daygrid-event .fc-event-title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-event-today' => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event .fc-event-title-container .fc-event-title'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-v-event .fc-event-time'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-daygrid-day-bottom .fc-daygrid-more-link'  => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_event_bg-color',
			[
				'label'     => esc_html__('Background Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-h-event' => '--fc-event-border-color: {{VALUE}}; --fc-event-bg-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event-today' => '--fc-event-bg-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-list-event' => '--fc-event-border-color: {{VALUE}}',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-list-table .fc-event-forced-url' => 'background: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_event_dot_color',
			[
				'label'     => esc_html__('Dot Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-daygrid-event .fc-daygrid-event-dot' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-list-event .fc-list-event-dot' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_event_dot_size',
			[
				'label'      => esc_html__('Dot Size', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => ['min' => 1, 'max' => 20],
				],
				'default' => ['size' => 4, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-daygrid-event .fc-daygrid-event-dot' => 'border-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-list-event .fc-list-event-dot'       => 'border-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$event_selectors = [
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-v-event .fc-event-time',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-daygrid-event .fc-event-time',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-daygrid-event .fc-event-title',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-event .fc-event-title-container .fc-event-title',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-event.fc-list-event',
			'{{WRAPPER}} .ekit-calendar-wrapper .fc-daygrid-day-bottom .fc-daygrid-more-link'
		];
		$event_selector = implode(',', $event_selectors);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_event_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => $event_selector,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_popup',
			[
				'label' => esc_html__('Popup', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_event_calendar_show_event_popup' => 'yes'
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_overlay_color',
			[
				'label'     => esc_html__('Overlay Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.55)',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper' => 'background-color: {{VALUE}};',
				],
			]
		);

		// Popup description  style
		$this->add_control(
			'ekit_event_calendar_popup_body',
			[
				'label'     => esc_html__('Popup Body', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_content_padding',
			[
				'label'      => esc_html__('Content Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_body_shadow',
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content',
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_border_radius',
			[
				'label' => esc_html__('Border Radius', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'default' => [
					'top' => '5',
					'right' => '5',
					'bottom' => '5',
					'left' => '5',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_width',
			[
				'label' => esc_html__('Width', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'unit' => 'px',
					'size' => 520,
				],
				'size_units' => ['px', '%', 'custom'],
				'range' => [
					'%' => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 520,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content-body' => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_height',
			[
				'label' => esc_html__('Height', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px', '%', 'em', 'rem', 'vh', 'custom'],
				'range' => [
					'vh' => [
						'min' => 1,
						'max' => 100,
					],
					'px' => [
						'min' => 1,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content-body' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		// Popup Title style
		$this->add_control(
			'ekit_event_calendar_popup_title',
			[
				'label'     => esc_html__('Title', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_body_padding',
			[
				'label'      => esc_html__('Body Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'    => [
					'top'    => '20',
					'right'  => '20',
					'bottom' => '20',
					'left'   => '20',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_body_gap',
			[
				'label'      => esc_html__('Body Gap', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem'],
				'range'      => [
					'px' => ['min' => 0, 'max' => 100],
				],
				'selectors'  => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_popup_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-title',
			]
		);
		// Popup Meta style
		$this->add_control(
			'ekit_event_calendar_popup_meta',
			[
				'label'     => esc_html__('Meta', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);
		$this->add_control(
			'ekit_event_calendar_popup_meta_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-meta .meta-item' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'ekit_event_calendar_popup_meta_icon_color',
			[
				'label'     => esc_html__('Icon Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-meta .meta-item :is(i, svg)' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_popup_meta_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-meta .meta-item',
			]
		);

		// Popup description  style
		$this->add_control(
			'ekit_event_calendar_popup_description',
			[
				'label'     => esc_html__('Description', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_description_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_popup_description_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-description',
			]
		);
		// Popup Date style
		$this->add_control(
			'ekit_event_calendar_popup_date',
			[
				'label'     => esc_html__('Date', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_date_label_color',
			[
				'label'     => esc_html__('Label Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-dates .date-box__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_date_color',
			[
				'label'     => esc_html__('Date Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-dates .date-box__value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_date_bg_color',
			[
				'label'     => esc_html__('Background Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-dates .date-box__value-wrap' => 'background-color: {{VALUE}};',
				],
				'default'   => '#f0f2f7',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_popup_date_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-dates :is(.date-box__value, .date-box__label)',
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_date_wrap_padding',
			[
				'label'      => esc_html__('Padding', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-dates .date-box__value-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'   => [
					'top' => '10',
					'right' => '15',
					'bottom' => '10',
					'left' => '15',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_date_border_radius',
			[
				'label'      => esc_html__('Border Radius', 'elementskit'),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-dates .date-box__value-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'default'   => [
					'top' => '5',
					'right' => '5',
					'bottom' => '5',
					'left' => '5',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_divider_heading',
			[
				'label'     => esc_html__('Divider', 'elementskit'),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_divider_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#E5E7EB',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-divider' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_divider_height',
			[
				'label'      => esc_html__('Height', 'elementskit'),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range'      => [
					'px' => ['min' => 1, 'max' => 20],
				],
				'default'    => ['size' => 1, 'unit' => 'px'],
				'selectors'  => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-divider' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_popup_button',
			[
				'label' => esc_html__('Popup: Button', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_event_calendar_show_event_popup' => 'yes',
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'ekit_event_calendar_popup_button_typography',
				'label'    => esc_html__('Typography', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta',
			]
		);

		$this->start_controls_tabs(
			'ekit_event_popup_button_style_tabs'
		);

		$this->start_controls_tab(
			'ekit_button_style_normal_tab',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_button_color',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default' 	=>  '#fff',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_button_bg',
			[
				'label'     => esc_html__('	Background Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default' 	=>  '#2575fc',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_border',
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta',
			]
		);

		$this->end_controls_tab();
		$this->start_controls_tab(
			'ekit_button_style_hover_tab',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_button_color_hover',
			[
				'label'     => esc_html__('Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default' 	=>  '#fff',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_button_bg_hover',
			[
				'label'     => esc_html__('	Background Color', 'elementskit'),
				'type'      => Controls_Manager::COLOR,
				'default' 	=>  '#2575fc',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta:hover' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_border_hover',
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta:hover',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_event_calendar_popup_button_border_radius',
			[
				'label' => esc_html__('Border Radius', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'separator' => 'before',
				'default' => [
					'top' => '5',
					'right' => '5',
					'bottom' => '5',
					'left' => '5',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup_button_padding',
			[
				'label' => esc_html__('Padding', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%', 'em', 'rem', 'custom'],
				'default' => [
					'top' => '15',
					'right' => '20',
					'bottom' => '15',
					'left' => '20',
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .popup-body .popup-cta' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'ekit_event_calendar_section_popup_close',
			[
				'label' => esc_html__('Popup: Close', 'elementskit'),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'ekit_event_calendar_show_event_popup' => 'yes',
				]
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup__close_size',
			[
				'label' => esc_html__('Size', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->add_responsive_control(
			'ekit_event_calendar_popup__close_icon_size',
			[
				'label' => esc_html__('Icon Size', 'elementskit'),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 6,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close :is(i, svg)' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
				],
			]
		);

		$this->start_controls_tabs('ekit_event_calendar_popup_close_tabs');

		$this->start_controls_tab(
			'ekit_event_calendar_popup_icon_colors_normal',
			[
				'label' => esc_html__('Normal', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_icon_primary_color',
			[
				'label' => esc_html__('Icon Color', 'elementskit'),
				'type' => Controls_Manager::COLOR,
				'default' => '#000',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close i,
					{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close svg'
					=> 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_secondary_color_normal',
			[
				'label' => esc_html__('Icon Background Color', 'elementskit'),
				'type' => Controls_Manager::COLOR,
				'default' => '#fff',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_border_close',
				'label' => esc_html__('Border', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_icon_shadow_normal',
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'ekit_event_calendar_popup_icon_colors_hover',
			[
				'label' => esc_html__('Hover', 'elementskit'),
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_hover_primary_color',
			[
				'label' => esc_html__('Icon Color', 'elementskit'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close:hover :is(i, svg)' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'ekit_event_calendar_popup_hover_background_color',
			[
				'label' => esc_html__('Icon Background Color', 'elementskit'),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_border_icon',
				'label' => esc_html__('Border', 'elementskit'),
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close:hover',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'ekit_event_calendar_popup_shadow_group',
				'selector' => '{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close:hover',
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'ekit_event_calendar_popup_icon_border_radius',
			[
				'label' => esc_html__('Border Radius', 'elementskit'),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => ['px', '%'],
				'selectors' => [
					'{{WRAPPER}} .ekit-popup-wrapper .ekit-popup-content .popup-close' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}

	protected function render()
	{
		echo '<div class="ekit-wid-con">';
		$this->render_raw();
		echo '</div>';
	}

	protected function render_raw()
	{
		$settings = $this->get_settings_for_display();
		extract($settings);

		$calendar_settings = [
			'languageCode' => $ekit_event_calendar_language_code_list,
			'initialView' => $ekit_event_calendar_active,
			'showEventPopup' => !empty($ekit_event_calendar_show_event_popup),

			// header config
			'headerToolbar' => !empty($ekit_event_calendar_hide_header_toolbar),
			'headerRightToolbar' => !empty($ekit_event_calendar_hide_header_right_toolbar),
			'headerLeftToolbar' => !empty($ekit_event_calendar_hide_header_left_toolbar),
			'toolbarTogether' => !empty($ekit_event_calendar_show_right_left_toolbar_together) ? $ekit_event_calendar_show_right_left_toolbar_together : 'left',
			'hideDay' => !empty($ekit_event_calendar_hide_day),
			'hideWeek' => !empty($ekit_event_calendar_hide_week),
			'hideMonth' => !empty($ekit_event_calendar_hide_month),
			'hideYear' => !empty($ekit_event_calendar_hide_year),
			'hideList' => !empty($ekit_event_calendar_hide_list),

			'firstDay' => (int) $ekit_event_calendar_first_day,
			'allDay' => !empty($ekit_event_calendar_full_day),
			'calendarType' => $ekit_event_calendar_source,
			'theEventsCalendar' => function_exists('tribe_get_events'),
		];

		// event calendar "manual and the event calendar" settings
		if ('manual' === $ekit_event_calendar_source || 'the_events_calendar' ===  $ekit_event_calendar_source) {
			$manual_events_data = $this->get_event_calendar_manual($settings);
			$the_events_data = $this->get_event_calendar_the_event($settings);
			$data_result = ('the_events_calendar' === $ekit_event_calendar_source && isset($the_events_data['calendar_data'])) ? $the_events_data['calendar_data'] : $manual_events_data;
			// Add 'manual and the events calendar' event data
			$calendar_settings['eventData'] = $data_result;
		}


		// event calendar google settings
		if ('google_calendar' === $ekit_event_calendar_source) {
			// Add 'googleCalendarId' key event data
			$calendar_settings['eventData']['googleCalendarId'] = !empty($ekit_event_google_calendar_id) ? $ekit_event_google_calendar_id : '';
			// Add 'googleCalendarApiKey' key event data
			$calendar_settings['googleCalendarApiKey'] = !empty($ekit_event_google_calendar_api_key) ? $ekit_event_google_calendar_api_key : '';
		}

		$id = $this->get_id();
		$this->add_render_attribute('calendar-wrapper', [
			'class' => [
				'ekit-calendar-wrapper',
			],
			'id' => [
				'calendar-' . $id,
			],
			'data-id' => $id,
			'data-calendar-settings' => wp_json_encode($calendar_settings),
		]);
?>

		<div <?php $this->print_render_attribute_string('calendar-wrapper'); ?>></div>

<?php
		if ('yes' === $ekit_event_calendar_show_event_popup) {
			// Unified popup for all event sources
			if ('manual' === $ekit_event_calendar_source && !empty($ekit_event_calendar_manual_event_lists)) {
				include Handler::get_dir() . 'markup/popup.php';
			}

			if ('the_events_calendar' === $ekit_event_calendar_source) {
				$events = $this->get_event_calendar_the_event($settings);
				if (isset($events['popup_data'])) {
					include Handler::get_dir() . 'markup/popup.php';
				}
			}

			if ('google_calendar' === $ekit_event_calendar_source) {
				$expiration_time = (HOUR_IN_SECONDS * ($ekit_event_calendar_expiration_time ? $ekit_event_calendar_expiration_time : 0));
				$events = Handler::google_calendar(
					$ekit_event_google_calendar_api_key,
					$ekit_event_google_calendar_id,
					$expiration_time
				);

				if (! empty($events)) {
					include Handler::get_dir() . 'markup/popup.php';
				}
			}
		}
	}


	//Get a list of all event
	public static function get_the_event_tribe_events()
	{
		if (! function_exists('tribe_get_events')) {
			return [];
		}

		$posts = [];
		$_posts = tribe_get_events();

		if (! empty($_posts)) {
			$posts = wp_list_pluck($_posts, 'post_title', 'ID');
		}
		return $posts;
	}


	//Get the event calendar category list
	public static function get_the_event_calendar_cats()
	{
		if (! function_exists('tribe_get_events')) {
			return [];
		}
		$args    = [
			'taxonomy'   => 'tribe_events_cat',
			'hide_empty' => false,
		];
		$options = [];
		$tags    = get_tags($args);

		if (is_wp_error($tags)) {
			return [];
		}

		foreach ($tags as $tag) {
			$options[$tag->term_id] = $tag->name;
		}
		return $options;
	}

	// event calendar manual settings
	protected function get_event_calendar_manual($settings)
	{
		if ('manual' !== $settings['ekit_event_calendar_source']) {
			return [];
		}

		$event_data = [];
		$events = $settings['ekit_event_calendar_manual_event_lists'] ?? [];

		foreach ($events as $index => $event) {
			$event_data[$index] = [
				'id'          => esc_attr($event['_id']),
				'title'       => !empty($event['ekit_event_calendar_title']) ? esc_html($event['ekit_event_calendar_title']) : '',
				'description' => !empty($event['ekit_event_calendar_description']) ? $this->parse_text_editor($event['ekit_event_calendar_description']) : '',
				'start'       => !empty($event['ekit_event_calendar_start_date']) ? $event['ekit_event_calendar_start_date'] : '',
				'end'  		  => !empty($event['ekit_event_calendar_end_date']) ? date('Y-m-d', strtotime('+1 day', strtotime($event['ekit_event_calendar_end_date']))) : '',
				'url'         => $settings['ekit_event_calendar_show_event_popup'] === 'yes' ? "javascript:void(0)" : (!empty($event['ekit_event_calendar_event_link']['url']) ? $event['ekit_event_calendar_event_link']['url'] : ''),
				'classNames'  => 'elementor-repeater-item-' . esc_attr($event['_id']),
			];
		}

		// Add time schedules if full day is enabled
		if ('yes' === $settings['ekit_event_calendar_full_day']) {
			$schedules_data = [];
			$schedules = $settings['ekit_event_calendar_manual_time_schedules'] ?? [];

			foreach ($schedules as $key => $schedule) {
				$schedules_data[$key] = [
					'id'	=> esc_attr($schedule['_id']),
					'title' => !empty($schedule['ekit_event_calendar_schedules_title']) ? esc_html($schedule['ekit_event_calendar_schedules_title']) : '',
					'start' => !empty($schedule['ekit_event_calendar_start_time']) ? $schedule['ekit_event_calendar_start_time'] : '',
					'end'   => !empty($schedule['ekit_event_calendar_end_time']) ? $schedule['ekit_event_calendar_end_time'] : '',
					'classNames'  => 'elementor-repeater-item-' . esc_attr($schedule['_id']),
				];
			}
			return array_merge($event_data, $schedules_data);
		}

		return $event_data;
	}

	// event calendar the event plugin settings
	public function get_event_calendar_the_event($settings)
	{
		// Use early return to avoid unnecessary nesting
		if (! function_exists('tribe_get_events')) {
			return [];
		}

		// Use switch statement to handle different cases of the source setting
		switch ($settings['ekit_event_the_events_calendar_source']) {
			case 'selected_event':
				$arg = [
					'post__in' => $settings['ekit_event_the_events_calendar_selected'] ?? [],
				];
				break;
			case 'category':
				$arg = [
					'posts_per_page' => $settings['ekit_event_the_events_calendar_item'],
					'tax_query' => [
						[
							'taxonomy' => 'tribe_events_cat',
							'field'    => 'id',
							'terms'    => $settings['ekit_event_the_events_calendar_category'] ?? [],
						],
					],
				];
				break;
			default:
				$arg = [
					'posts_per_page' => $settings['ekit_event_the_events_calendar_item'],
				];
		}

		$the_events = tribe_get_events($arg);
		if (empty($the_events)) {
			return [];
		}

		$calendar_data = [];
		$popup_data = [];

		foreach ($the_events as $key => $the_event) {
			$date_format = 'Y-m-d';

			if (!tribe_event_is_all_day($the_event->ID)) {
				$date_format .= ' H:i';
			}

			$image = get_the_post_thumbnail_url($the_event->ID);
			$alt_text = get_post_meta(get_post_thumbnail_id($the_event->ID), '_wp_attachment_image_alt', true);

			// Store common values to avoid duplication
			$event_id = esc_attr('ekit-' . $the_event->ID);
			$event_title = !empty($the_event->post_title) ? esc_html($the_event->post_title) : '';
			$event_start = esc_html(tribe_get_start_date($the_event->ID, true, $date_format));
			$event_end = esc_html(tribe_get_end_date($the_event->ID, true, $date_format));
			$event_url = !empty(get_the_permalink($the_event->ID)) ? esc_url(get_the_permalink($the_event->ID)) : 'javascript:void(0)';

			// Calendar data (minimal fields for FullCalendar)
			$calendar_data[$key] = [
				'id'          => $event_id,
				'title'       => $event_title,
				'start'       => $event_start,
				'end'         => $event_end,
				'url'         => $event_url,
				'classNames'  => 'repeater-item-' . esc_attr($the_event->ID),
			];

			// Popup data (full details for popup display)
			$popup_data[$key] = [
				'id'          => $event_id,
				'title'       => $event_title,
				'description' => !empty($the_event->post_content) ? \ElementsKit_Lite\Utils::kses($the_event->post_content) : '',
				'start'       => $event_start,
				'end'         => $event_end,
				'guest'       => esc_html(tribe_get_organizer($the_event->ID)),
				'location'    => !empty(tribe_get_venue($the_event->ID)) ? esc_html(tribe_get_venue($the_event->ID)) : '',
				'image'       => [
					'src'     => $image ? esc_url($image) : \Elementor\Utils::get_placeholder_image_src(),
					'alt'     => $alt_text ? \ElementsKit_Lite\Utils::kses($alt_text) : '',
				],
				'url'         => $event_url,
			];
		}

		return [
			'calendar_data' => $calendar_data,
			'popup_data' =>  $popup_data
		];
	}
}
