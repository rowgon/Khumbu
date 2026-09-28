<?php

namespace Elementor;

defined('ABSPATH') || exit;

class ElementsKit_Widget_Event_Calendar_Handler extends \ElementsKit_Lite\Core\Handler_Widget {

    static function get_name() {
        return 'elementskit-event-calendar';
    }

    static function get_title() {
        return esc_html__('Event Calendar', 'elementskit');
    }

    static function get_icon() {
        return 'ekit ekit-widget-icon ekit-event-calendar';
    }

    static function get_categories() {
        return ['elementskit'];
    }

    static function get_keywords() {
        return ['ekit', 'event calendar', 'event', 'calendar'];
    }

    static function get_dir() {
        return \ElementsKit::widget_dir() . 'event-calendar/';
    }

    static function get_url() {
        return \ElementsKit::widget_url() . 'event-calendar/';
    }

    public function wp_init() {
        new \ElementsKit\Widgets\Event_Calendar\Event_Calendar_Api();

        /**
         * Appends Event Calendar header icon HTML to the shared ElementsKit localized JS config object (`window.ekit_config`).
         *
         * The `headerIcons` array is consumed by the JS `initCalendar()` function
         * which prepends each icon into the corresponding FullCalendar toolbar button:
         *
         *  - prev  → .fc-prev-button  (used by FullCalendar internally via buttonIcons)
         *  - next  → .fc-next-button
         *  - year  → .fc-multiMonthYear-button
         *  - month → .fc-dayGridMonth-button
         *  - week  → .fc-timeGridWeek-button
         *  - day   → .fc-timeGridDay-button
         *  - list  → .fc-listWeek-button
         *
         * @param array $localize_settings The existing localized settings array.
         * @return array Modified settings with `widgets.eventCalendar.headerIcons` added.
         *
         * @filter elementskit/common/localize_settings
         */
		add_filter( 'elementskit/common/localize_settings' , function( $localize_settings ) {
            $prev = '';
            $next = '';
            $year = '<svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.375 0.75C12.7892 0.75 13.125 1.08579 13.125 1.5V2.25H14.25C15.4927 2.25 16.5 3.25737 16.5 4.5V15C16.5 16.2427 15.4927 17.25 14.25 17.25H3.75C2.50737 17.25 1.5 16.2427 1.5 15V4.5C1.5 3.25736 2.50736 2.25 3.75 2.25H4.875V1.5C4.875 1.08579 5.21079 0.75 5.625 0.75C6.03921 0.75 6.375 1.08579 6.375 1.5V2.25H11.625V1.5C11.625 1.08579 11.9608 0.75 12.375 0.75ZM3 15C3 15.4142 3.33578 15.75 3.75 15.75H14.25C14.6642 15.75 15 15.4142 15 15V8.25H3V15ZM5.625 12.5625C6.14271 12.5625 6.5625 12.9822 6.5625 13.5C6.5625 14.0178 6.14271 14.4375 5.625 14.4375C5.10729 14.4375 4.6875 14.0178 4.6875 13.5C4.6875 12.9822 5.10729 12.5625 5.625 12.5625ZM9.09668 12.5674C9.56933 12.6155 9.93848 13.0146 9.93848 13.5C9.93848 14.0177 9.51859 14.4373 9.00098 14.4375C8.51575 14.4375 8.11633 14.0687 8.06836 13.5957L8.06348 13.5L8.06836 13.4043C8.11633 12.9313 8.51575 12.5625 9.00098 12.5625L9.09668 12.5674ZM12.375 12.5566C12.8928 12.5566 13.3124 12.9764 13.3125 13.4941C13.3125 14.0119 12.8928 14.4316 12.375 14.4316C11.8575 14.4316 11.4375 14.0121 11.4375 13.4941C11.4376 12.9762 11.8575 12.5566 12.375 12.5566ZM5.625 9.5625C6.14271 9.5625 6.5625 9.98215 6.5625 10.5C6.5625 11.0178 6.14271 11.4375 5.625 11.4375C5.10729 11.4375 4.6875 11.0178 4.6875 10.5C4.6875 9.98215 5.10729 9.5625 5.625 9.5625ZM9.09668 9.56738C9.56933 9.61549 9.93848 10.0146 9.93848 10.5C9.93848 11.0177 9.51859 11.4373 9.00098 11.4375C8.51575 11.4375 8.11633 11.0687 8.06836 10.5957L8.06348 10.5L8.06836 10.4043C8.11633 9.93133 8.51575 9.5625 9.00098 9.5625L9.09668 9.56738ZM12.375 9.5625C12.8928 9.5625 13.3125 9.98221 13.3125 10.5C13.3125 11.0178 12.8928 11.4375 12.375 11.4375C11.8572 11.4375 11.4375 11.0178 11.4375 10.5C11.4375 9.98221 11.8572 9.5625 12.375 9.5625ZM3.75 3.75C3.33579 3.75 3 4.08579 3 4.5V6.75H15V4.5C15 4.08578 14.6642 3.75 14.25 3.75H13.125V4.5C13.125 4.91421 12.7892 5.25 12.375 5.25C11.9608 5.25 11.625 4.91421 11.625 4.5V3.75H6.375V4.5C6.375 4.91421 6.03921 5.25 5.625 5.25C5.21079 5.25 4.875 4.91421 4.875 4.5V3.75H3.75Z"/>
                    </svg>';
            $month = '<svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.375 0.75C12.7892 0.75 13.125 1.08579 13.125 1.5V2.25H14.25C15.4927 2.25 16.5 3.25737 16.5 4.5V15C16.5 16.2427 15.4927 17.25 14.25 17.25H3.75C2.50737 17.25 1.5 16.2427 1.5 15V4.5C1.5 3.25736 2.50736 2.25 3.75 2.25H4.875V1.5C4.875 1.08579 5.21079 0.75 5.625 0.75C6.03921 0.75 6.375 1.08579 6.375 1.5V2.25H11.625V1.5C11.625 1.08579 11.9608 0.75 12.375 0.75ZM3 15C3 15.4142 3.33578 15.75 3.75 15.75H14.25C14.6642 15.75 15 15.4142 15 15V8.25H3V15ZM5.625 12.5625C6.14271 12.5625 6.5625 12.9822 6.5625 13.5C6.5625 14.0178 6.14271 14.4375 5.625 14.4375C5.10729 14.4375 4.6875 14.0178 4.6875 13.5C4.6875 12.9822 5.10729 12.5625 5.625 12.5625ZM9.09668 12.5674C9.56933 12.6155 9.93848 13.0146 9.93848 13.5C9.93848 14.0177 9.51859 14.4373 9.00098 14.4375C8.51575 14.4375 8.11633 14.0687 8.06836 13.5957L8.06348 13.5L8.06836 13.4043C8.11633 12.9313 8.51575 12.5625 9.00098 12.5625L9.09668 12.5674ZM5.625 9.5625C6.14271 9.5625 6.5625 9.98215 6.5625 10.5C6.5625 11.0178 6.14271 11.4375 5.625 11.4375C5.10729 11.4375 4.6875 11.0178 4.6875 10.5C4.6875 9.98215 5.10729 9.5625 5.625 9.5625ZM9.09668 9.56738C9.56933 9.61549 9.93848 10.0146 9.93848 10.5C9.93848 11.0177 9.51859 11.4373 9.00098 11.4375C8.51575 11.4375 8.11633 11.0687 8.06836 10.5957L8.06348 10.5L8.06836 10.4043C8.11633 9.93133 8.51575 9.5625 9.00098 9.5625L9.09668 9.56738ZM12.375 9.5625C12.8928 9.5625 13.3125 9.98221 13.3125 10.5C13.3125 11.0178 12.8928 11.4375 12.375 11.4375C11.8572 11.4375 11.4375 11.0178 11.4375 10.5C11.4375 9.98221 11.8572 9.5625 12.375 9.5625ZM3.75 3.75C3.33579 3.75 3 4.08579 3 4.5V6.75H15V4.5C15 4.08578 14.6642 3.75 14.25 3.75H13.125V4.5C13.125 4.91421 12.7892 5.25 12.375 5.25C11.9608 5.25 11.625 4.91421 11.625 4.5V3.75H6.375V4.5C6.375 4.91421 6.03921 5.25 5.625 5.25C5.21079 5.25 4.875 4.91421 4.875 4.5V3.75H3.75Z"/>
                    </svg>';
            $week = '<svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.375 0.75C12.7892 0.75 13.125 1.08579 13.125 1.5V2.25H14.25C15.4927 2.25 16.5 3.25737 16.5 4.5V15C16.5 16.2427 15.4927 17.25 14.25 17.25H3.75C2.50737 17.25 1.5 16.2427 1.5 15V4.5C1.5 3.25736 2.50736 2.25 3.75 2.25H4.875V1.5C4.875 1.08579 5.21079 0.75 5.625 0.75C6.03921 0.75 6.375 1.08579 6.375 1.5V2.25H11.625V1.5C11.625 1.08579 11.9608 0.75 12.375 0.75ZM3 8.25V15C3 15.4142 3.33578 15.75 3.75 15.75H14.25C14.6642 15.75 15 15.4142 15 15V8.25H3ZM9.75 12.75C10.1642 12.75 10.5 13.0858 10.5 13.5C10.5 13.9142 10.1642 14.25 9.75 14.25H6C5.58579 14.25 5.25 13.9142 5.25 13.5C5.25 13.0858 5.58579 12.75 6 12.75H9.75ZM12 12.75C12.4142 12.75 12.75 13.0858 12.75 13.5C12.75 13.9142 12.4142 14.25 12 14.25H11.9932C11.579 14.25 11.2432 13.9142 11.2432 13.5C11.2432 13.0858 11.579 12.75 11.9932 12.75H12ZM6.00684 9.75C6.421 9.75005 6.75684 10.0858 6.75684 10.5C6.75684 10.9142 6.421 11.2499 6.00684 11.25H6C5.58579 11.25 5.25 10.9142 5.25 10.5C5.25 10.0858 5.58579 9.75 6 9.75H6.00684ZM12 9.75C12.4142 9.75 12.75 10.0858 12.75 10.5C12.75 10.9142 12.4142 11.25 12 11.25H8.25C7.83579 11.25 7.5 10.9142 7.5 10.5C7.5 10.0858 7.83579 9.75 8.25 9.75H12ZM3.75 3.75C3.33579 3.75 3 4.08579 3 4.5V6.75H15V4.5C15 4.08578 14.6642 3.75 14.25 3.75H13.125V4.5C13.125 4.91421 12.7892 5.25 12.375 5.25C11.9608 5.25 11.625 4.91421 11.625 4.5V3.75H6.375V4.5C6.375 4.91421 6.03921 5.25 5.625 5.25C5.21079 5.25 4.875 4.91421 4.875 4.5V3.75H3.75Z"/>
                    </svg>';
            $day = '<svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.375 0.75C12.7892 0.75 13.125 1.08579 13.125 1.5V2.25H14.25C15.4927 2.25 16.5 3.25737 16.5 4.5V15C16.5 16.2427 15.4927 17.25 14.25 17.25H3.75C2.50737 17.25 1.5 16.2427 1.5 15V4.5C1.5 3.25736 2.50736 2.25 3.75 2.25H4.875V1.5C4.875 1.08579 5.21079 0.75 5.625 0.75C6.03921 0.75 6.375 1.08579 6.375 1.5V2.25H11.625V1.5C11.625 1.08579 11.9608 0.75 12.375 0.75ZM3 8.25V15C3 15.4142 3.33578 15.75 3.75 15.75H14.25C14.6642 15.75 15 15.4142 15 15V8.25H3ZM7.5 9.375C7.91416 9.375 8.24992 9.71086 8.25 10.125V13.875C8.25 14.2892 7.91421 14.625 7.5 14.625C7.08579 14.625 6.75 14.2892 6.75 13.875V10.875C6.33579 10.875 6 10.5392 6 10.125C6.00008 9.71086 6.33584 9.375 6.75 9.375H7.5ZM11.625 9.375C11.8621 9.375 12.085 9.48751 12.2266 9.67773C12.368 9.86793 12.4119 10.1138 12.3438 10.3408L11.2188 14.0889C11.0997 14.4856 10.6809 14.7109 10.2842 14.5918C9.88769 14.4726 9.66233 14.0548 9.78125 13.6582L10.6162 10.875H9.75C9.33579 10.875 9 10.5392 9 10.125C9 9.71079 9.33579 9.375 9.75 9.375H11.625ZM3.75 3.75C3.33579 3.75 3 4.08579 3 4.5V6.75H15V4.5C15 4.08578 14.6642 3.75 14.25 3.75H13.125V4.5C13.125 4.91421 12.7892 5.25 12.375 5.25C11.9608 5.25 11.625 4.91421 11.625 4.5V3.75H6.375V4.5C6.375 4.91421 6.03921 5.25 5.625 5.25C5.21079 5.25 4.875 4.91421 4.875 4.5V3.75H3.75Z"/>
                    </svg>';
            $list = '<svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                         <path d="M4.5 10.6875C4.81066 10.6875 5.0625 10.9393 5.0625 11.25V13.5C5.0625 13.8107 4.81066 14.0625 4.5 14.0625H2.8125V15.1875H4.5C4.81066 15.1875 5.0625 15.4393 5.0625 15.75C5.0625 16.0607 4.81066 16.3125 4.5 16.3125H2.25C1.93946 16.3124 1.6875 16.0606 1.6875 15.75V13.5C1.6875 13.1894 1.93946 12.9376 2.25 12.9375H3.9375V11.8125H2.25C1.93934 11.8125 1.6875 11.5607 1.6875 11.25C1.6875 10.9393 1.93934 10.6875 2.25 10.6875H4.5ZM15.75 12.9375C16.0607 12.9375 16.3125 13.1893 16.3125 13.5C16.3125 13.8107 16.0607 14.0625 15.75 14.0625H8.25C7.93934 14.0625 7.6875 13.8107 7.6875 13.5C7.6875 13.1893 7.93934 12.9375 8.25 12.9375H15.75ZM15.75 8.4375C16.0607 8.4375 16.3125 8.68934 16.3125 9C16.3125 9.31066 16.0607 9.5625 15.75 9.5625H8.25C7.93934 9.5625 7.6875 9.31066 7.6875 9C7.6875 8.68934 7.93934 8.4375 8.25 8.4375H15.75ZM3.375 1.6875C3.68566 1.6875 3.9375 1.93934 3.9375 2.25V6.1875H4.5C4.81066 6.1875 5.0625 6.43934 5.0625 6.75C5.0625 7.06066 4.81066 7.3125 4.5 7.3125H2.25C1.93934 7.3125 1.6875 7.06066 1.6875 6.75C1.6875 6.43934 1.93934 6.1875 2.25 6.1875H2.8125V2.8125H2.25C1.93934 2.8125 1.6875 2.56066 1.6875 2.25C1.6875 1.93934 1.93934 1.6875 2.25 1.6875H3.375ZM15.75 3.9375C16.0607 3.9375 16.3125 4.18934 16.3125 4.5C16.3125 4.81066 16.0607 5.0625 15.75 5.0625H8.25C7.93934 5.0625 7.6875 4.81066 7.6875 4.5C7.6875 4.18934 7.93934 3.9375 8.25 3.9375H15.75Z"/>
                    </svg>';

            $localize_settings['widgets']['eventCalendar'] = [
                'headerIcons' => [
                    'prev'  => $prev,
                    'next'  => $next,
                    'year'  => $year,
                    'month' => $month,
                    'week'  => $week,
                    'day'   => $day,
                    'list'  => $list,
                ],
            ];
			return $localize_settings;
		});
    }

    public static $transient_name = '__event-calendars';

    /**
     * Fetches Google Calendar events and caches the result in a transient.
     *
     * Returns cached data when available, otherwise requests events from the
     * Google Calendar API, stores the decoded event list, and returns it.
     *
     * @param string $key Google Calendar API key.
     * @param string $calendar_ID Google Calendar ID.
     * @param int $expiration_time Transient expiration time in seconds.
     * @return array|false Event items array on success, or false on request failure.
     */
    public static function google_calendar($key, $calendar_ID, $expiration_time) {

        $transient_name  = self::$transient_name;
        $transient_value = get_transient($transient_name);
        if (false !== $transient_value) {
            return $transient_value;
        }

        $args    = 'events?key=' . $key;
        $request = wp_remote_get('https://www.googleapis.com/calendar/v3/calendars/' . $calendar_ID . '/' . $args);
        if (is_wp_error($request)) {
            return false;
        }

        $body   = wp_remote_retrieve_body($request);
        $data   = json_decode($body, true);
        $result = $data['items'] ?? [];

        set_transient($transient_name, $result, $expiration_time);
        return $result;
    }

    public static function reset_cache() {
        delete_transient(self::$transient_name);
    }

    static function language_codes() {
        $lang_codes = [
            'af'    => 'Afrikaans',
            'sq'    => 'Albanian',
            'ar'    => 'Arabic',
            'eu'    => 'Basque',
            'bn'    => 'Bengali',
            'bs'    => 'Bosnian',
            'bg'    => 'Bulgarian',
            'ca'    => 'Catalan',
            'zh-cn' => 'Chinese',
            'zh-tw' => 'Chinese-tw',
            'hr'    => 'Croatian',
            'cs'    => 'Czech',
            'da'    => 'Danish',
            'nl'    => 'Dutch',
            'en'    => 'English',
            'et'    => 'Estonian',
            'fi'    => 'Finnish',
            'fr'    => 'French',
            'gl'    => 'Galician',
            'ka'    => 'Georgian',
            'de'    => 'German',
            'el'    => 'Greek (Modern)',
            'he'    => 'Hebrew',
            'hi'    => 'Hindi',
            'hu'    => 'Hungarian',
            'is'    => 'Icelandic',
            'io'    => 'Ido',
            'id'    => 'Indonesian',
            'it'    => 'Italian',
            'ja'    => 'Japanese',
            'kk'    => 'Kazakh',
            'ko'    => 'Korean',
            'lv'    => 'Latvian',
            'lb'    => 'Letzeburgesch',
            'lt'    => 'Lithuanian',
            'lu'    => 'Luba-Katanga',
            'mk'    => 'Macedonian',
            'mg'    => 'Malagasy',
            'ms'    => 'Malay',
            'ro'    => 'Moldovan, Moldavian, Romanian',
            'nb'    => 'Norwegian Bokmål',
            'nn'    => 'Norwegian Nynorsk',
            'fa'    => 'Persian',
            'pl'    => 'Polish',
            'pt'    => 'Portuguese',
            'ru'    => 'Russian',
            'sr'    => 'Serbian',
            'sk'    => 'Slovak',
            'sl'    => 'Slovenian',
            'es'    => 'Spanish',
            'sv'    => 'Swedish',
            'tr'    => 'Turkish',
            'uk'    => 'Ukrainian',
            'vi'    => 'Vietnamese',
        ];
        return $lang_codes;
    }
}
