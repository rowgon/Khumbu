<?php
require_once 'bootstrap.php';

if ( class_exists( 'MainWP_Pro_Reports_Schedule' ) ) {
	MainWP_Pro_Reports_Schedule::cron_notice_ready_reports();
}