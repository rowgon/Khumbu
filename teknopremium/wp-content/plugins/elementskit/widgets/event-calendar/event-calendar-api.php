<?php

namespace ElementsKit\Widgets\Event_Calendar;

defined('ABSPATH') || exit;

use Elementor\ElementsKit_Widget_Event_Calendar_Handler as Calendar_Handler;
use ElementsKit_Lite\Core\Handler_Api;

class Event_Calendar_Api extends Handler_Api {

    public function __construct() {
        parent::__construct();
    }

    public function config() {
        $this->prefix = 'widget/calendar';
        $this->param  = "";
    }

    public function post_remove_cache() {
        $trans_key = Calendar_Handler::$transient_name;
        $is_remove = delete_transient($trans_key);

        if ($is_remove) {
            return [
                'success' => true,
                'msg'     => esc_html__('Cache Successfully Deleted', 'elementskit'),
            ];
        } else {
            return [
                'success' => false,
                'msg'     => esc_html__('Data Not Found in Server', 'elementskit'),
            ];
        }
    }
}
