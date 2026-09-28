<?php
/*
Template Name: MainWP Pro Report Modern Template
Description: Modern template for the MainWP Pro Reports extension.
Version: 2.0
Author: MainWP
Screenshot URI: ../wp-content/plugins/mainwp-pro-reports-extension/images/template-modern.jpg
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$config_tokens = array(
	0 => '[hide-if-empty]',
	1 => '', // show report data
	2 => '[hide-section-data]',
);

$default_config = array(
	'wp-update'       => 0,
	'plugins-updates' => 0,
	'themes-updates'  => 0,
	'posts-updates'   => 0,
	'pages-updates'   => 0,
	'users'           => 0,
	'comments'        => 0,
	'uptime'          => 0,
	'domain'          => 0,
	'ssl'             => 0,
	'security'        => 0,
	'itsecurity'      => 0,
	'backups'         => 0,
	'ga'              => 0,
	'matomo'          => 0,
	'pagespeed'       => 0,
	'maintenance'     => 0,
	'lighthouse'      => 0,
	'wordfence'       => 0,
	'protect'         => 0,
	'atarim'          => 0,
	'aum'             => 0,
	'fathom'          => 0,
	'time_tracker'    => 0,
);

$mainwp_analytics_heading        = MainWP_Pro_Reports_Overview::get_analytics_heading( 'google', $report_settings );
$mainwp_fathom_analytics_heading = MainWP_Pro_Reports_Overview::get_analytics_heading( 'fathom', $report_settings );
$mainwp_matomo_analytics_heading = MainWP_Pro_Reports_Overview::get_analytics_heading( 'matomo', $report_settings );

$mainwp_timetracker_heading = MainWP_Pro_Reports_Overview::get_content_heading( 'time_tracker_heading', $report_settings, esc_html__( 'Time Tracker Tasks', 'mainwp-pro-reports-extension' ) );
$mainwp_time_title_heading  = MainWP_Pro_Reports_Overview::get_content_heading( 'time_tracker_title_heading', $report_settings, esc_html__( 'Time Tracker', 'mainwp-pro-reports-extension' ) );
$mainwp_duration_heading    = MainWP_Pro_Reports_Overview::get_content_heading( 'duration_heading', $report_settings, esc_html__( 'Time Tracker', 'mainwp-pro-reports-extension' ) );
$mainwp_price_heading       = MainWP_Pro_Reports_Overview::get_content_heading( 'price_heading', $report_settings, esc_html__( 'Time Tracker', 'mainwp-pro-reports-extension' ) );

if ( ! empty( $report_settings ) && is_array( $report_settings ) ) {
	$bg_color                = isset( $report_settings['background_color'] ) ? $report_settings['background_color'] : '';
	$text_color              = isset( $report_settings['text_color'] ) ? $report_settings['text_color'] : '';
	$accent_color            = isset( $report_settings['accent_color'] ) ? $report_settings['accent_color'] : '';
	$first_page_bg_color     = isset( $report_settings['first_page_background_color'] ) ? $report_settings['first_page_background_color'] : '';
	$link_color              = isset( $report_settings['link_color'] ) ? $report_settings['link_color'] : '';
	$table_background_color  = isset( $report_settings['table_background_color'] ) ? $report_settings['table_background_color'] : '';
	$table_header_color      = isset( $report_settings['table_header_color'] ) ? $report_settings['table_header_color'] : '';
	$table_header_text_color = isset( $report_settings['table_header_text_color'] ) ? $report_settings['table_header_text_color'] : '';
	$table_border_color      = isset( $report_settings['table_border_color'] ) ? $report_settings['table_border_color'] : '';
	// Titles.
	$mainwp_summary_heading          = isset( $report_settings['mainwp_summary_heading'] ) ? $report_settings['mainwp_summary_heading'] : esc_html__( 'Website Care Summary', 'mainwp-pro-reports-extension' );
	$mainwp_updates_heading          = isset( $report_settings['mainwp_updates_heading'] ) ? $report_settings['mainwp_updates_heading'] : esc_html__( 'Updates', 'mainwp-pro-reports-extension' );
	$mainwp_posts_heading            = isset( $report_settings['mainwp_posts_heading'] ) ? $report_settings['mainwp_posts_heading'] : esc_html__( 'Posts Management', 'mainwp-pro-reports-extension' );
	$mainwp_pages_heading            = isset( $report_settings['mainwp_pages_heading'] ) ? $report_settings['mainwp_pages_heading'] : esc_html__( 'Pages Management', 'mainwp-pro-reports-extension' );
	$mainwp_users_heading            = isset( $report_settings['mainwp_users_heading'] ) ? $report_settings['mainwp_users_heading'] : esc_html__( 'Users Management', 'mainwp-pro-reports-extension' );
	$mainwp_comments_heading         = isset( $report_settings['mainwp_comments_heading'] ) ? $report_settings['mainwp_comments_heading'] : esc_html__( 'Comments Management', 'mainwp-pro-reports-extension' );
	$mainwp_security_heading         = isset( $report_settings['mainwp_security_heading'] ) ? $report_settings['mainwp_security_heading'] : esc_html__( 'Security', 'mainwp-pro-reports-extension' );
	$mainwp_backups_heading          = isset( $report_settings['mainwp_backups_heading'] ) ? $report_settings['mainwp_backups_heading'] : esc_html__( 'Backups', 'mainwp-pro-reports-extension' );
	$mainwp_uptime_heading           = isset( $report_settings['mainwp_uptime_heading'] ) ? $report_settings['mainwp_uptime_heading'] : esc_html__( 'Uptime Monitoring', 'mainwp-pro-reports-extension' );
	$mainwp_performance_heading      = isset( $report_settings['mainwp_performance_heading'] ) ? $report_settings['mainwp_performance_heading'] : esc_html__( 'Website Performance', 'mainwp-pro-reports-extension' );
	$mainwp_maintenance_heading      = isset( $report_settings['mainwp_maintenance_heading'] ) ? $report_settings['mainwp_maintenance_heading'] : esc_html__( 'Optimization & Maintenance', 'mainwp-pro-reports-extension' );
	$mainwp_atarim_heading           = isset( $report_settings['mainwp_atarim_heading'] ) ? $report_settings['mainwp_atarim_heading'] : esc_html__( 'Atarim Tasks', 'mainwp-pro-reports-extension' );
	$mainwp_general_heading          = isset( $report_settings['mainwp_general_heading'] ) ? $report_settings['mainwp_general_heading'] : esc_html__( 'General Information', 'mainwp-pro-reports-extension' );
	$mainwp_domain_heading           = isset( $report_settings['mainwp_domain_heading'] ) ? $report_settings['mainwp_domain_heading'] : esc_html__( 'Domain Status', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_heading              = isset( $report_settings['mainwp_ssl_heading'] ) ? $report_settings['mainwp_ssl_heading'] : esc_html__( 'SSL Certificate Status', 'mainwp-pro-reports-extension' );
	$mainwp_misc_heading             = isset( $report_settings['mainwp_misc_heading'] ) ? $report_settings['mainwp_misc_heading'] : esc_html__( 'Misc Information', 'mainwp-pro-reports-extension' );
	$mainwp_vuln_heading             = isset( $report_settings['mainwp_vuln_heading'] ) ? $report_settings['mainwp_vuln_heading'] : esc_html__( 'Vulnerabilities', 'mainwp-pro-reports-extension' );
	$mainwp_wpupdates_heading        = isset( $report_settings['mainwp_wpupdates_heading'] ) ? $report_settings['mainwp_wpupdates_heading'] : esc_html__( 'WordPress Updates', 'mainwp-pro-reports-extension' );
	$mainwp_pluginsupdates_heading   = isset( $report_settings['mainwp_pluginsupdates_heading'] ) ? $report_settings['mainwp_pluginsupdates_heading'] : esc_html__( 'Plugins Updates', 'mainwp-pro-reports-extension' );
	$mainwp_themesupdates_heading    = isset( $report_settings['mainwp_themesupdates_heading'] ) ? $report_settings['mainwp_themesupdates_heading'] : esc_html__( 'Themes Updates', 'mainwp-pro-reports-extension' );
	$mainwp_newposts_heading         = isset( $report_settings['mainwp_newposts_heading'] ) ? $report_settings['mainwp_newposts_heading'] : esc_html__( 'New Posts', 'mainwp-pro-reports-extension' );
	$mainwp_updatedposts_heading     = isset( $report_settings['mainwp_updatedposts_heading'] ) ? $report_settings['mainwp_updatedposts_heading'] : esc_html__( 'Updated Posts', 'mainwp-pro-reports-extension' );
	$mainwp_deletedposts_heading     = isset( $report_settings['mainwp_deletedposts_heading'] ) ? $report_settings['mainwp_deletedposts_heading'] : esc_html__( 'Deleted Posts', 'mainwp-pro-reports-extension' );
	$mainwp_newpages_heading         = isset( $report_settings['mainwp_newpages_heading'] ) ? $report_settings['mainwp_newpages_heading'] : esc_html__( 'New Pages', 'mainwp-pro-reports-extension' );
	$mainwp_updatedpages_heading     = isset( $report_settings['mainwp_updatedpages_heading'] ) ? $report_settings['mainwp_updatedpages_heading'] : esc_html__( 'Updated Pages', 'mainwp-pro-reports-extension' );
	$mainwp_deletedpages_heading     = isset( $report_settings['mainwp_deletedpages_heading'] ) ? $report_settings['mainwp_deletedpages_heading'] : esc_html__( 'Deleted Pages', 'mainwp-pro-reports-extension' );
	$mainwp_newusers_heading         = isset( $report_settings['mainwp_newusers_heading'] ) ? $report_settings['mainwp_newusers_heading'] : esc_html__( 'New Users', 'mainwp-pro-reports-extension' );
	$mainwp_updatedusers_heading     = isset( $report_settings['mainwp_updatedusers_heading'] ) ? $report_settings['mainwp_updatedusers_heading'] : esc_html__( 'Updated Users', 'mainwp-pro-reports-extension' );
	$mainwp_deletedusers_heading     = isset( $report_settings['mainwp_deletedusers_heading'] ) ? $report_settings['mainwp_deletedusers_heading'] : esc_html__( 'Deleted Users', 'mainwp-pro-reports-extension' );
	$mainwp_newcomments_heading      = isset( $report_settings['mainwp_newcomments_heading'] ) ? $report_settings['mainwp_newcomments_heading'] : esc_html__( 'New Comments', 'mainwp-pro-reports-extension' );
	$mainwp_updatedcomments_heading  = isset( $report_settings['mainwp_updatedcomments_heading'] ) ? $report_settings['mainwp_updatedcomments_heading'] : esc_html__( 'Updated Comments', 'mainwp-pro-reports-extension' );
	$mainwp_deletedcomments_heading  = isset( $report_settings['mainwp_deletedcomments_heading'] ) ? $report_settings['mainwp_deletedcomments_heading'] : esc_html__( 'Deleted Comments', 'mainwp-pro-reports-extension' );
	$mainwp_spamcomments_heading     = isset( $report_settings['mainwp_spamcomments_heading'] ) ? $report_settings['mainwp_spamcomments_heading'] : esc_html__( 'Spam Comments', 'mainwp-pro-reports-extension' );
	$mainwp_approvedcomments_heading = isset( $report_settings['mainwp_approvedcomments_heading'] ) ? $report_settings['mainwp_approvedcomments_heading'] : esc_html__( 'Approved Comments', 'mainwp-pro-reports-extension' );
	$mainwp_sucuri_heading           = isset( $report_settings['mainwp_sucuri_heading'] ) ? $report_settings['mainwp_sucuri_heading'] : esc_html__( 'Sucuri Scans', 'mainwp-pro-reports-extension' );
	$mainwp_wordfence_heading        = isset( $report_settings['mainwp_wordfence_heading'] ) ? $report_settings['mainwp_wordfence_heading'] : esc_html__( 'Wordfence Security Scans', 'mainwp-pro-reports-extension' );
	$mainwp_ithemes_heading          = isset( $report_settings['mainwp_ithemes_heading'] ) ? $report_settings['mainwp_ithemes_heading'] : esc_html__( 'iThemes Security Scans', 'mainwp-pro-reports-extension' );
	$mainwp_ithemesbfp_heading       = isset( $report_settings['mainwp_ithemesbfp_heading'] ) ? $report_settings['mainwp_ithemesbfp_heading'] : esc_html__( 'Brute Force Protection', 'mainwp-pro-reports-extension' );
	// Table Headers.
	$mainwp_website_heading            = isset( $report_settings['mainwp_website_heading'] ) ? $report_settings['mainwp_website_heading'] : esc_html__( 'Website', 'mainwp-pro-reports-extension' );
	$mainwp_version_heading            = isset( $report_settings['mainwp_version_heading'] ) ? $report_settings['mainwp_version_heading'] : esc_html__( 'Version', 'mainwp-pro-reports-extension' );
	$mainwp_phpversion_heading         = isset( $report_settings['mainwp_phpversion_heading'] ) ? $report_settings['mainwp_phpversion_heading'] : esc_html__( 'PHP Version', 'mainwp-pro-reports-extension' );
	$mainwp_oldversion_heading         = isset( $report_settings['mainwp_oldversion_heading'] ) ? $report_settings['mainwp_oldversion_heading'] : esc_html__( 'Old Version', 'mainwp-pro-reports-extension' );
	$mainwp_newversion_heading         = isset( $report_settings['mainwp_newversion_heading'] ) ? $report_settings['mainwp_newversion_heading'] : esc_html__( 'New Version', 'mainwp-pro-reports-extension' );
	$mainwp_date_heading               = isset( $report_settings['mainwp_date_heading'] ) ? $report_settings['mainwp_date_heading'] : esc_html__( 'Date', 'mainwp-pro-reports-extension' );
	$mainwp_daterange_heading          = isset( $report_settings['mainwp_daterange_heading'] ) ? $report_settings['mainwp_daterange_heading'] : esc_html__( 'Date Range', 'mainwp-pro-reports-extension' );
	$mainwp_details_heading            = isset( $report_settings['mainwp_details_heading'] ) ? $report_settings['mainwp_details_heading'] : esc_html__( 'Details', 'mainwp-pro-reports-extension' );
	$mainwp_task_heading               = isset( $report_settings['mainwp_task_heading'] ) ? $report_settings['mainwp_task_heading'] : esc_html__( 'Task', 'mainwp-pro-reports-extension' );
	$mainwp_title_heading              = isset( $report_settings['mainwp_title_heading'] ) ? $report_settings['mainwp_title_heading'] : esc_html__( 'Title', 'mainwp-pro-reports-extension' );
	$mainwp_type_heading               = isset( $report_settings['mainwp_type_heading'] ) ? $report_settings['mainwp_type_heading'] : esc_html__( 'Type', 'mainwp-pro-reports-extension' );
	$mainwp_comment_heading            = isset( $report_settings['mainwp_comment_heading'] ) ? $report_settings['mainwp_comment_heading'] : esc_html__( 'Comment', 'mainwp-pro-reports-extension' );
	$mainwp_role_heading               = isset( $report_settings['mainwp_role_heading'] ) ? $report_settings['mainwp_role_heading'] : esc_html__( 'Role', 'mainwp-pro-reports-extension' );
	$mainwp_user_heading               = isset( $report_settings['mainwp_user_heading'] ) ? $report_settings['mainwp_user_heading'] : esc_html__( 'User', 'mainwp-pro-reports-extension' );
	$mainwp_theme_heading              = isset( $report_settings['mainwp_theme_heading'] ) ? $report_settings['mainwp_theme_heading'] : esc_html__( 'Theme', 'mainwp-pro-reports-extension' );
	$mainwp_plugin_heading             = isset( $report_settings['mainwp_plugin_heading'] ) ? $report_settings['mainwp_plugin_heading'] : esc_html__( 'Plugin', 'mainwp-pro-reports-extension' );
	$mainwp_status_heading             = isset( $report_settings['mainwp_status_heading'] ) ? $report_settings['mainwp_status_heading'] : esc_html__( 'Status', 'mainwp-pro-reports-extension' );
	$mainwp_webtrust_heading           = isset( $report_settings['mainwp_webtrust_heading'] ) ? $report_settings['mainwp_webtrust_heading'] : esc_html__( 'Webtrust', 'mainwp-pro-reports-extension' );
	$mainwp_period_heading             = isset( $report_settings['mainwp_period_heading'] ) ? $report_settings['mainwp_period_heading'] : esc_html__( 'Period', 'mainwp-pro-reports-extension' );
	$mainwp_performancescore_heading   = isset( $report_settings['mainwp_performancescore_heading'] ) ? $report_settings['mainwp_performancescore_heading'] : esc_html__( 'Performance Score', 'mainwp-pro-reports-extension' );
	$mainwp_accessibilityscore_heading = isset( $report_settings['mainwp_accessibilityscore_heading'] ) ? $report_settings['mainwp_accessibilityscore_heading'] : esc_html__( 'Accessibility Score', 'mainwp-pro-reports-extension' );
	$mainwp_bestpracticesscore_heading = isset( $report_settings['mainwp_bestpracticesscore_heading'] ) ? $report_settings['mainwp_bestpracticesscore_heading'] : esc_html__( 'Best Practices Score', 'mainwp-pro-reports-extension' );
	$mainwp_seoscore_heading           = isset( $report_settings['mainwp_seoscore_heading'] ) ? $report_settings['mainwp_seoscore_heading'] : esc_html__( 'SEO Score', 'mainwp-pro-reports-extension' );
	$mainwp_desktop_heading            = isset( $report_settings['mainwp_desktop_heading'] ) ? $report_settings['mainwp_desktop_heading'] : esc_html__( 'Desktop', 'mainwp-pro-reports-extension' );
	$mainwp_mobile_heading             = isset( $report_settings['mainwp_mobile_heading'] ) ? $report_settings['mainwp_mobile_heading'] : esc_html__( 'Mobile', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_cname_heading          = isset( $report_settings['mainwp_ssl_cname_heading'] ) ? $report_settings['mainwp_ssl_cname_heading'] : esc_html__( 'CNAME', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_issuer_heading         = isset( $report_settings['mainwp_ssl_issuer_heading'] ) ? $report_settings['mainwp_ssl_issuer_heading'] : esc_html__( 'Certificate Issuer', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_valid_from_heading     = isset( $report_settings['mainwp_ssl_valid_from_heading'] ) ? $report_settings['mainwp_ssl_valid_from_heading'] : esc_html__( 'Valid From', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_valid_to_heading       = isset( $report_settings['mainwp_ssl_valid_to_heading'] ) ? $report_settings['mainwp_ssl_valid_to_heading'] : esc_html__( 'Valid To', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_expires_heading        = isset( $report_settings['mainwp_ssl_expires_heading'] ) ? $report_settings['mainwp_ssl_expires_heading'] : esc_html__( 'Expires', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_status_heading         = isset( $report_settings['mainwp_ssl_status_heading'] ) ? $report_settings['mainwp_ssl_status_heading'] : esc_html__( 'Certificate Status', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_last_check_heading     = isset( $report_settings['mainwp_ssl_last_check_heading'] ) ? $report_settings['mainwp_ssl_last_check_heading'] : esc_html__( 'Checked On', 'mainwp-pro-reports-extension' );
	$mainwp_overalluptime_heading      = isset( $report_settings['mainwp_overalluptime_heading'] ) ? $report_settings['mainwp_overalluptime_heading'] : esc_html__( 'Overall Uptime', 'mainwp-pro-reports-extension' );
	$mainwp_uptime7_heading            = isset( $report_settings['mainwp_uptime7_heading'] ) ? $report_settings['mainwp_uptime7_heading'] : esc_html__( 'Last 7 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime15_heading           = isset( $report_settings['mainwp_uptime15_heading'] ) ? $report_settings['mainwp_uptime15_heading'] : esc_html__( 'Last 15 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime30_heading           = isset( $report_settings['mainwp_uptime30_heading'] ) ? $report_settings['mainwp_uptime30_heading'] : esc_html__( 'Last 30 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime45_heading           = isset( $report_settings['mainwp_uptime45_heading'] ) ? $report_settings['mainwp_uptime45_heading'] : esc_html__( 'Last 45 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime60_heading           = isset( $report_settings['mainwp_uptime60_heading'] ) ? $report_settings['mainwp_uptime60_heading'] : esc_html__( 'Last 60 Days', 'mainwp-pro-reports-extension' );
	$mainwp_websitevisits_heading      = isset( $report_settings['mainwp_websitevisits_heading'] ) ? $report_settings['mainwp_websitevisits_heading'] : esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' );
	$mainwp_pagevisits_heading         = isset( $report_settings['mainwp_pagevisits_heading'] ) ? $report_settings['mainwp_pagevisits_heading'] : esc_html__( 'Page Visits', 'mainwp-pro-reports-extension' );
	$mainwp_pageviews_heading          = isset( $report_settings['mainwp_pageviews_heading'] ) ? $report_settings['mainwp_pageviews_heading'] : esc_html__( 'Page Views', 'mainwp-pro-reports-extension' );
	$mainwp_bouncerate_heading         = isset( $report_settings['mainwp_bouncerate_heading'] ) ? $report_settings['mainwp_bouncerate_heading'] : esc_html__( 'Bounce Rate', 'mainwp-pro-reports-extension' );
	$mainwp_averagetime_heading        = isset( $report_settings['mainwp_averagetime_heading'] ) ? $report_settings['mainwp_averagetime_heading'] : esc_html__( 'Average Time', 'mainwp-pro-reports-extension' );
	$mainwp_newvisits_heading          = isset( $report_settings['mainwp_newvisits_heading'] ) ? $report_settings['mainwp_newvisits_heading'] : esc_html__( 'New Visits', 'mainwp-pro-reports-extension' );
	$mainwp_pluginsthreats_heading     = isset( $report_settings['mainwp_pluginsthreats_heading'] ) ? $report_settings['mainwp_pluginsthreats_heading'] : esc_html__( 'Plugins Threats', 'mainwp-pro-reports-extension' );
	$mainwp_themesthreats_heading      = isset( $report_settings['mainwp_themesthreats_heading'] ) ? $report_settings['mainwp_themesthreats_heading'] : esc_html__( 'Themes Threats', 'mainwp-pro-reports-extension' );
} else {
	// to compatible with old colors settings.
	$bg_color     = $report->background_color;
	$accent_color = $report->accent_color;
	$text_color   = $report->text_color;

	$first_page_bg_color     = '#ffffff';
	$link_color              = '#444444';
	$table_header_color      = '#666666';
	$table_background_color  = '#fafafa';
	$table_header_text_color = '#333333';
	$table_border_color      = '#666666';
	// Titles.
	$mainwp_summary_heading          = esc_html__( 'Website Care Summary', 'mainwp-pro-reports-extension' );
	$mainwp_updates_heading          = esc_html__( 'Updates', 'mainwp-pro-reports-extension' );
	$mainwp_posts_heading            = esc_html__( 'Posts Management', 'mainwp-pro-reports-extension' );
	$mainwp_pages_heading            = esc_html__( 'Pages Management', 'mainwp-pro-reports-extension' );
	$mainwp_users_heading            = esc_html__( 'Users Management', 'mainwp-pro-reports-extension' );
	$mainwp_comments_heading         = esc_html__( 'Comments Management', 'mainwp-pro-reports-extension' );
	$mainwp_security_heading         = esc_html__( 'Security', 'mainwp-pro-reports-extension' );
	$mainwp_backups_heading          = esc_html__( 'Backups', 'mainwp-pro-reports-extension' );
	$mainwp_uptime_heading           = esc_html__( 'Uptime Monitoring', 'mainwp-pro-reports-extension' );
	$mainwp_performance_heading      = esc_html__( 'Website Performance', 'mainwp-pro-reports-extension' );
	$mainwp_maintenance_heading      = esc_html__( 'Optimization & Maintenance', 'mainwp-pro-reports-extension' );
	$mainwp_atarim_heading           = esc_html__( 'Atarim Tasks', 'mainwp-pro-reports-extension' );
	$mainwp_general_heading          = esc_html__( 'General Information', 'mainwp-pro-reports-extension' );
	$mainwp_domain_heading           = esc_html__( 'Domain Status', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_heading              = esc_html__( 'SSL Certificate Status', 'mainwp-pro-reports-extension' );
	$mainwp_misc_heading             = esc_html__( 'Misc Information', 'mainwp-pro-reports-extension' );
	$mainwp_vuln_heading             = esc_html__( 'Vulnerabilities', 'mainwp-pro-reports-extension' );
	$mainwp_wpupdates_heading        = esc_html__( 'WordPress Updates', 'mainwp-pro-reports-extension' );
	$mainwp_pluginsupdates_heading   = esc_html__( 'Plugins Updates', 'mainwp-pro-reports-extension' );
	$mainwp_themesupdates_heading    = esc_html__( 'Themes Updates', 'mainwp-pro-reports-extension' );
	$mainwp_newposts_heading         = esc_html__( 'New Posts', 'mainwp-pro-reports-extension' );
	$mainwp_updatedposts_heading     = esc_html__( 'Updated Posts', 'mainwp-pro-reports-extension' );
	$mainwp_deletedposts_heading     = esc_html__( 'Deleted Posts', 'mainwp-pro-reports-extension' );
	$mainwp_newpages_heading         = esc_html__( 'New Pages', 'mainwp-pro-reports-extension' );
	$mainwp_updatedpages_heading     = esc_html__( 'Updated Pages', 'mainwp-pro-reports-extension' );
	$mainwp_deletedpages_heading     = esc_html__( 'Deleted Pages', 'mainwp-pro-reports-extension' );
	$mainwp_newusers_heading         = esc_html__( 'New Users', 'mainwp-pro-reports-extension' );
	$mainwp_updatedusers_heading     = esc_html__( 'Updated Users', 'mainwp-pro-reports-extension' );
	$mainwp_deletedusers_heading     = esc_html__( 'Deleted Users', 'mainwp-pro-reports-extension' );
	$mainwp_newcomments_heading      = esc_html__( 'New Comments', 'mainwp-pro-reports-extension' );
	$mainwp_updatedcomments_heading  = esc_html__( 'Updated Comments', 'mainwp-pro-reports-extension' );
	$mainwp_deletedcomments_heading  = esc_html__( 'Deleted Comments', 'mainwp-pro-reports-extension' );
	$mainwp_spamcomments_heading     = esc_html__( 'Spam Comments', 'mainwp-pro-reports-extension' );
	$mainwp_approvedcomments_heading = esc_html__( 'Approved Comments', 'mainwp-pro-reports-extension' );
	$mainwp_sucuri_heading           = esc_html__( 'Sucuri Scans', 'mainwp-pro-reports-extension' );
	$mainwp_wordfence_heading        = esc_html__( 'Wordfence Security Scans', 'mainwp-pro-reports-extension' );
	$mainwp_ithemes_heading          = esc_html__( 'iThemes Security Scans', 'mainwp-pro-reports-extension' );
	$mainwp_ithemesbfp_heading       = esc_html__( 'Brute Force Protection', 'mainwp-pro-reports-extension' );
	// Table Headers.
	$mainwp_website_heading            = esc_html__( 'Website', 'mainwp-pro-reports-extension' );
	$mainwp_version_heading            = esc_html__( 'Version', 'mainwp-pro-reports-extension' );
	$mainwp_phpversion_heading         = esc_html__( 'PHP Version', 'mainwp-pro-reports-extension' );
	$mainwp_oldversion_heading         = esc_html__( 'Old Version', 'mainwp-pro-reports-extension' );
	$mainwp_newversion_heading         = esc_html__( 'New Version', 'mainwp-pro-reports-extension' );
	$mainwp_date_heading               = esc_html__( 'Date', 'mainwp-pro-reports-extension' );
	$mainwp_daterange_heading          = esc_html__( 'Date Range', 'mainwp-pro-reports-extension' );
	$mainwp_details_heading            = esc_html__( 'Details', 'mainwp-pro-reports-extension' );
	$mainwp_task_heading               = esc_html__( 'Task', 'mainwp-pro-reports-extension' );
	$mainwp_title_heading              = esc_html__( 'Title', 'mainwp-pro-reports-extension' );
	$mainwp_type_heading               = esc_html__( 'Type', 'mainwp-pro-reports-extension' );
	$mainwp_comment_heading            = esc_html__( 'Comment', 'mainwp-pro-reports-extension' );
	$mainwp_role_heading               = esc_html__( 'Role', 'mainwp-pro-reports-extension' );
	$mainwp_user_heading               = esc_html__( 'User', 'mainwp-pro-reports-extension' );
	$mainwp_theme_heading              = esc_html__( 'Theme', 'mainwp-pro-reports-extension' );
	$mainwp_plugin_heading             = esc_html__( 'Plugin', 'mainwp-pro-reports-extension' );
	$mainwp_status_heading             = esc_html__( 'Status', 'mainwp-pro-reports-extension' );
	$mainwp_webtrust_heading           = esc_html__( 'Webtrust', 'mainwp-pro-reports-extension' );
	$mainwp_period_heading             = esc_html__( 'Period', 'mainwp-pro-reports-extension' );
	$mainwp_performancescore_heading   = esc_html__( 'Performance Score', 'mainwp-pro-reports-extension' );
	$mainwp_accessibilityscore_heading = esc_html__( 'Accessibility Score', 'mainwp-pro-reports-extension' );
	$mainwp_bestpracticesscore_heading = esc_html__( 'Best Practices Score', 'mainwp-pro-reports-extension' );
	$mainwp_seoscore_heading           = esc_html__( 'SEO Score', 'mainwp-pro-reports-extension' );
	$mainwp_desktop_heading            = esc_html__( 'Desktop', 'mainwp-pro-reports-extension' );
	$mainwp_mobile_heading             = esc_html__( 'Mobile', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_cname_heading          = esc_html__( 'CNAME', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_issuer_heading         = esc_html__( 'Certificate Issuer', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_valid_from_heading     = esc_html__( 'Valid From', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_valid_to_heading       = esc_html__( 'Valid To', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_expires_heading        = esc_html__( 'Expires', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_status_heading         = esc_html__( 'Certificate Status', 'mainwp-pro-reports-extension' );
	$mainwp_ssl_last_check_heading     = esc_html__( 'Checked On', 'mainwp-pro-reports-extension' );
	$mainwp_overalluptime_heading      = esc_html__( 'Overall Uptime', 'mainwp-pro-reports-extension' );
	$mainwp_uptime7_heading            = esc_html__( 'Last 7 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime15_heading           = esc_html__( 'Last 15 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime30_heading           = esc_html__( 'Last 30 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime45_heading           = esc_html__( 'Last 45 Days', 'mainwp-pro-reports-extension' );
	$mainwp_uptime60_heading           = esc_html__( 'Last 60 Days', 'mainwp-pro-reports-extension' );
	$mainwp_websitevisits_heading      = esc_html__( 'Website Visits', 'mainwp-pro-reports-extension' );
	$mainwp_pagevisits_heading         = esc_html__( 'Page Visits', 'mainwp-pro-reports-extension' );
	$mainwp_pageviews_heading          = esc_html__( 'Page Views', 'mainwp-pro-reports-extension' );
	$mainwp_bouncerate_heading         = esc_html__( 'Bounce Rate', 'mainwp-pro-reports-extension' );
	$mainwp_averagetime_heading        = esc_html__( 'Average Time', 'mainwp-pro-reports-extension' );
	$mainwp_newvisits_heading          = esc_html__( 'New Visits', 'mainwp-pro-reports-extension' );
	$mainwp_pluginsthreats_heading     = esc_html__( 'Plugins Threats', 'mainwp-pro-reports-extension' );
	$mainwp_themesthreats_heading      = esc_html__( 'Themes Threats', 'mainwp-pro-reports-extension' );
}
$accent_background = $accent_color;

$showhide_values = @json_decode( $report->showhide_sections, 1 );

if ( ! is_array( $showhide_values ) ) {
	$showhide_values = array();
}

$showhide_values = array_merge( $default_config, $showhide_values );

$logo_id = $report->logo_id;

$heading = $report->heading;
$intro   = $report->intro;
$intro   = nl2br( $intro ); // to fix

$outro = $report->outro;
$outro = nl2br( $outro ); // to fix

$plugin_active_comments    = is_plugin_active( 'mainwp-comments-extension/mainwp-comments-extension.php' ) ? true : false;
$plugin_active_uptime      = is_plugin_active( 'advanced-uptime-monitor-extension/advanced-uptime-monitor-extension.php' ) ? true : false;
$plugin_active_sucuri      = ( is_plugin_active( 'mainwp-sucuri-extension/mainwp-sucuri-extension.php' ) ) ? true : false;
$plugin_active_wordfence   = ( is_plugin_active( 'mainwp-wordfence-extension/mainwp-wordfence-extension.php' ) ) ? true : false;
$plugin_active_itsecurity  = ( is_plugin_active( 'mainwp-ithemes-security-extension/mainwp-ithemes-security-extension.php' ) ) ? true : false;
$plugin_active_protect     = ( is_plugin_active( 'mainwp-jetpack-protect-extension/mainwp-jetpack-protect-extension.php' ) ) ? true : false;
$plugin_active_backups     = ( is_plugin_active( 'mainwp-backwpup-extension/mainwp-backwpup-extension.php' )
							|| is_plugin_active( 'mainwp-backupwordpress-extension/mainwp-backupwordpress-extension.php' )
							|| is_plugin_active( 'mainwp-buddy-extension/mainwp-buddy-extension.php' )
							|| is_plugin_active( 'mainwp-updraftplus-extension/mainwp-updraftplus-extension.php' )
							|| is_plugin_active( 'mainwp-timecapsule-extension/mainwp-timecapsule-extension.php' )
							|| is_plugin_active( 'wpvivid-backup-mainwp/wpvivid-backup-mainwp.php' )
				) ? true : false;
$plugin_active_ga          = is_plugin_active( 'mainwp-google-analytics-extension/mainwp-google-analytics-extension.php' ) ? true : false;
$plugin_active_fathom      = is_plugin_active( 'mainwp-fathom-extension/mainwp-fathom-extension.php' ) ? true : false;
$plugin_active_piwik       = is_plugin_active( 'mainwp-piwik-extension/mainwp-piwik-extension.php' ) ? true : false;
$plugin_active_maintenance = is_plugin_active( 'mainwp-maintenance-extension/mainwp-maintenance-extension.php' ) ? true : false;
$plugin_active_pagespeed   = is_plugin_active( 'mainwp-page-speed-extension/mainwp-page-speed-extension.php' ) ? true : false;
$plugin_active_lighthouse  = is_plugin_active( 'mainwp-lighthouse-extension/mainwp-lighthouse-extension.php' ) ? true : false;
$plugin_active_domain      = is_plugin_active( 'mainwp-domain-monitor-extension/mainwp-domain-monitor-extension.php' ) ? true : false;
$plugin_active_ssl         = is_plugin_active( 'mainwp-ssl-monitor-extension/mainwp-ssl-monitor-extension.php' ) ? true : false;
$plugin_active_atarim      = is_plugin_active( 'mainwp-atarim-extension/mainwp-atarim-extension.php' ) ? true : false;
$plugin_active_timetracker = is_plugin_active( 'mainwp-time-tracker-extension/mainwp-time-tracker-extension.php' ) ? true : false;

?>
<html>
	<head>
		<style type="text/css">
		@page { margin: 0; padding-top: 50px !important; background-color: <?php echo esc_html( $bg_color ); ?> !important; }
		body { background-color: <?php echo esc_html( $bg_color ); ?>; color: <?php echo $text_color; ?>; font-size: 13px; font-family: 'Lato', sans-serif; }
		a { color: <?php echo $link_color; ?>; text-decoration: none; }
		p { font-family: 'Lato', sans-serif; }
		.page-break { page-break-after: always; }
		.mainwp-report-first-page { background-color: <?php echo esc_html( $first_page_bg_color ); ?>; height: 100%; background-image: url( "<?php echo $header_image; ?>" ); background-repeat: no-repeat; background-position: center bottom; background-size: contain;}
		.mainwp-report-clear { clear: both; }
		.mainwp-report-segment { padding: 25px 50px; }
		.mainwp-report-title { text-align: center; font-size: 60px; font-weight: bolder; color: <?php echo $accent_color; ?>; padding: 100px 0 100px 0; line-height: 0.8; }
		.mainwp-report-subtitle { margin-left: -50px; margin-right: -50px; background-color: <?php echo $accent_color; ?>; padding: 20px 50px; }
		.mainwp-report-subtitle p { font-size: 14px; color: <?php echo $text_color; ?>; text-align: center; }
		table { border:0px; width:100%; }
		table th { text-align: left; padding: 10px; background-color: <?php echo $table_header_color; ?>; color: <?php echo $table_header_text_color; ?>;}
		table td { padding:10px; background: <?php echo $table_background_color; ?>; border-bottom: 1px dashed <?php echo esc_html( $table_border_color ); ?>; font-family: 'Lato', sans-serif; }
		table.left-th tr td:nth-of-type(2) { text-align: right; }
		h1 { color: <?php echo esc_html( $accent_color ); ?>; font-weight:bold; font-family: 'Lato', sans-serif; font-size:38px; text-align:left; }
		h2 { color: <?php echo esc_html( $accent_color ); ?>; font-weight:bold; font-family: 'Lato', sans-serif; font-size:32px; margin-bottom: 45px; text-align:left; }
		#ga-chart img { width: 100%; }
		</style>
	</head>
	<body>
		<main>

			<div class="mainwp-report-first-page">
				<div class="mainwp-report-segment">
					<div style="text-align:center;">
						<?php if ( $logo_id ) : ?>
							<img src="[logo.url]" alt="logo" style="max-width:200px;height:auto;"/>
						<?php endif; ?>
					</div>
					<div class="mainwp-report-title"><?php echo esc_html( $heading ); ?></div>
					<div style="text-align:center;">
						<img src="[header.image.url]" style="max-width: 400px"/>
					</div>
					</div>
				</div>

				<div class="page-break"></div>

			<div style="margin:0;">

				<div style="padding:0px;">
					<div style="padding:0 60px 60px;">
						<p><?php echo MainWP_Pro_Reports_Utility::esc_content( $intro ); ?></p>
					</div>
				</div>

				<div style="padding:0px;">
					<div style="padding:0 60px 60px;">
						<h2><?php echo esc_html( $mainwp_summary_heading ); ?></h2>
						<table cellspacing="0" class="left-th">
							<tbody>
								<tr><td><?php echo esc_html( $mainwp_website_heading ); ?></td><td><a href="[client.site.url]" target="_blank">[client.site.url]</a></td></tr>
								<tr><td><?php echo esc_html( $mainwp_daterange_heading ); ?></td><td>[report.daterange]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_version_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[client.site.version]</span></td></tr>
								<tr><td><?php echo esc_html( $mainwp_theme_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[client.site.theme]</span></td></tr>
								<tr><td><?php echo esc_html( $mainwp_phpversion_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[client.site.php]</span></td></tr>
								<?php if ( $plugin_active_uptime ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['uptime'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_overalluptime_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[aum.alltimeuptimeratio]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_sucuri ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['security'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_sucuri_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[sucuri.checks.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_wordfence ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['wordfence'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_wordfence_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[wordfence.scan.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_itsecurity ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['itsecurity'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_ithemes_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[ithemes.scan.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_protect ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['protect'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_vuln_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[jetpack.protect.plugins.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['plugins-updates'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_pluginsupdates_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[plugin.updated.count]</span></td></tr>
								[/config-section-data]

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['themes-updates'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_themesupdates_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[theme.updated.count]</span></td></tr>
								[/config-section-data]

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_newposts_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[post.created.count]</span></td></tr>
								[/config-section-data]

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_newpages_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[page.created.count]</span></td></tr>
								[/config-section-data]

								<?php if ( $plugin_active_backups ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['backups'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_backups_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[backup.created.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_maintenance ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['maintenance'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_maintenance_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[maintenance.process.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_domain ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['domain'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_domain_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[domain.monitor.status]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_lighthouse ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['lighthouse'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_performance_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[lighthouse.performance.desktop] / 100</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_ga ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['ga'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[ga.visits]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_fathom ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['fathom'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[fathom.visits]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_piwik ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['matomo'] ]; ?>
								<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[piwik.visits]</span></td></tr>
								[/config-section-data]
								<?php } ?>
								<?php if ( $plugin_active_timetracker ) : ?>
									[config-section-data]
									[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['time_tracker'] ]; ?>
									<tr><td><?php echo esc_html( $mainwp_timetracker_heading ); ?></td><td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[timetracker.tasks.count]</span></td></tr>
									[/config-section-data]
								<?php endif; ?>

							</tbody>
						</table>
					</div>
				</div>

				<!-- Uptime Data -->
				<?php if ( $plugin_active_uptime ) : ?>
				[config-section-data]
				[config-section-extra max-empty="6" /]
					<?php echo $config_tokens[ $showhide_values['uptime'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_uptime_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_uptime' ); ?>
							<table cellspacing="0" class="left-th">
								<thead>
									<tr>
										<th><?php echo esc_html( $mainwp_period_heading ); ?></th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<tr><td><?php echo esc_html( $mainwp_overalluptime_heading ); ?></td><td>[aum.alltimeuptimeratio]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_uptime7_heading ); ?></td><td>[aum.uptime7]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_uptime15_heading ); ?></td><td>[aum.uptime15]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_uptime30_heading ); ?></td><td>[aum.uptime30]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_uptime45_heading ); ?></td><td>[aum.uptime45]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_uptime60_heading ); ?></td><td>[aum.uptime60]</td></tr>
								</tbody>
							</table>
						<?php do_action( 'mainwp_pro_reports_after_uptime' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Uptime Data -->

				<!-- Security Scans Data -->
				<?php if ( $plugin_active_sucuri ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['security'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_sucuri_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_sucuri' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th style="text-align:left;"><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th style="text-align:center;"><?php echo esc_html( $mainwp_status_heading ); ?></th>
									<th style="text-align:right;"><?php echo esc_html( $mainwp_webtrust_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.sucuri.checks]
								<tr>
									<td>[sucuri.check.date]</td>
									<td>[sucuri.check.status]</td>
									<td>[sucuri.check.webtrust]</td>
								</tr>
								[/section.sucuri.checks]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_sucuri' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Security Scans Data -->

				<!-- Wordfence Scans Data -->
				<?php if ( $plugin_active_wordfence ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['wordfence'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_wordfence_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_sucuri' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th style="text-align:left;"><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th style="text-align:center;"><?php echo esc_html( $mainwp_status_heading ); ?></th>
									<th style="text-align:right;"><?php echo esc_html( $mainwp_details_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.wordfence.scan]
								<tr>
									<td style="text-align:left;">[wordfence.scan.date]</td>
									<td style="text-align:center;">[wordfence.scan.result]</td>
									<td style="text-align:right;">[wordfence.scan.details]</td>
								</tr>
								[/section.wordfence.scan]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_sucuri' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Wordfence Scans Data -->

				<!-- iThemes Security Scans Data -->
				<?php if ( $plugin_active_itsecurity ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['itsecurity'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_ithemes_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_sucuri' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th style="text-align:left;"><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th style="text-align:center;"><?php echo esc_html( $mainwp_status_heading ); ?></th>
									<th style="text-align:right;"><?php echo esc_html( $mainwp_details_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.ithemes.scan]
								<tr>
									<td style="text-align:left;">[ithemes.scan.date]</td>
									<td style="text-align:center;">[ithemes.scan.result]</td>
									<td style="text-align:right;">[ithemes.scan.details]</td>
								</tr>
								[/section.ithemes.scan]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_sucuri' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End iThemes Security Scans Data -->

				<!-- Time Tracker Data -->
				<?php if ( $plugin_active_timetracker ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['time_tracker'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_timetracker_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_time_tracker' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th style="text-align:left;"><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th style="text-align:left;"><?php echo esc_html( $mainwp_time_title_heading ); ?></th>
									<th style="text-align:left;"><?php echo esc_html( $mainwp_duration_heading ); ?></th>
									<th style="text-align:right;"><?php echo esc_html( $mainwp_price_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.timetracker.tasks]
								<tr>
									<td style="text-align:left;">[timetracker.task.date]</td>
									<td style="text-align:left;">[timetracker.task.title]</td>
									<td style="text-align:left;">[timetracker.task.duration]</td>
									<td style="text-align:right;">[timetracker.task.total.price]</td>
								</tr>
								[/section.timetracker.tasks]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_time_tracker' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Time Tracker Data -->

				<!-- Updates Data -->

				<?php do_action( 'mainwp_pro_reports_before_updates' ); ?>

				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['wp-update'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_wpupdates_heading ); ?></h2>
						<table cellspacing="0">
							<thead>
								<tr>
									<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_oldversion_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_newversion_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.wordpress.updated] <?php  // phpcs:ignore -- wordpress. ?>
								<tr>
									<td>[wordpress.updated.date]</td> <?php  // phpcs:ignore -- wordpress. ?>
									<td><span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[wordpress.old.version]</span></td> <?php  // phpcs:ignore -- wordpress. ?>
									<td><span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[wordpress.current.version]</span></td> <?php  // phpcs:ignore -- wordpress. ?>
								</tr>
								[/section.wordpress.updated] <?php  // phpcs:ignore -- wordpress. ?>
							</tbody>
						</table>
					</div>
				</div>
				[/config-section-data]

				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['plugins-updates'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_pluginsupdates_heading ); ?></h2>
						<table cellspacing="0">
							<thead>
								<tr>
									<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_plugin_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_version_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.plugins.updated]
								<tr>
									<td>[plugin.updated.date]</td>
									<td><span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[plugin.name]</span></td>
									<td>From [plugin.old.version] to <span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[plugin.current.version]</span></td>
								</tr>
								[/section.plugins.updated]
							</tbody>
						</table>
					</div>
				</div>
				[/config-section-data]

				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['themes-updates'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_themesupdates_heading ); ?></h2>
						<table cellspacing="0">
							<thead>
								<tr>
									<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_theme_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_version_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.themes.updated]
								<tr>
									<td>[theme.updated.date] </td>
									<td><span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[theme.name]</span></td>
									<td>From [theme.old.version] to <span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[theme.current.version]</span></td>
								</tr>
								[/section.themes.updated]
							</tbody>
						</table>
					</div>
				</div>
				[/config-section-data]

				<?php do_action( 'mainwp_pro_reports_after_updates' ); ?>

				<!-- End Updates Data -->

				<!-- Post Data -->
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_newposts_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_title_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.posts.created]
							<tr>
								<td style="width:20%">[post.created.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[post.title]</span></td>
							</tr>
							[/section.posts.created]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_updatedposts_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_title_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.posts.updated]
							<tr>
								<td style="width:20%">[post.updated.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[post.title]</span></td>
							</tr>
							[/section.posts.updated]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_deletedposts_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_title_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.posts.deleted]
							<tr>
								<td style="width:20%">[post.deleted.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[post.title]</span></td>
							</tr>
							[/section.posts.deleted]
						</tbody>
					</table>
				</div>
				[/config-section-data]

				<!-- Page Data -->
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_newpages_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_title_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.pages.created]
							<tr>
								<td style="width:20%">[page.created.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[page.title]</span></td>
							</tr>
							[/section.pages.created]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_updatedpages_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_title_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.pages.updated]
							<tr>
								<td style="width:20%">[page.updated.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[page.title]</span></td>
							</tr>
							[/section.pages.updated]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_deletedpages_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_title_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.pages.deleted]
							<tr>
								<td style="width:20%">[page.deleted.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[page.title]</span></td>
							</tr>
							[/section.pages.deleted]
						</tbody>
					</table>
				</div>
				[/config-section-data]

				<!-- USers Data -->
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['users'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_newusers_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_role_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_user_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.users.created]
							<tr>
								<td>[user.created.date]</td>
								<td>[user.created.role]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[user.name]</span></td>
							</tr>
							[/section.users.created]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['users'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_updatedusers_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_role_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_user_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.users.updated]
							<tr>
								<td>[user.updated.date]</td>
								<td>[user.updated.role]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[user.name]</span></td>
							</tr>
							[/section.users.updated]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['users'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_deletedusers_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_role_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_user_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.users.deleted]
							<tr>
								<td>[user.deleted.date]</td>
								<td>[user.deleted.role]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[user.name]</span></td>
							</tr>
							[/section.users.deleted]
						</tbody>
					</table>
				</div>
				[/config-section-data]

				<!-- Comments Data -->
				<?php if ( $plugin_active_comments ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_newcomments_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_comment_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.comments.created]
							<tr>
								<td>[comment.created.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[comment.title]</span></td>
							</tr>
							[/section.comments.created]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_updatedcomments_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_comment_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.comments.updated]
							<tr>
								<td>[comment.updated.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[comment.title]</span></td>
							</tr>
							[/section.comments.updated]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_approvedcomments_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_comment_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.comments.approved]
							<tr>
								<td>[comment.approved.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[comment.title]</span></td>
							</tr>
							[/section.comments.approved]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_spamcomments_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_comment_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.comments.spam]
							<tr>
								<td>[comment.spam.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[comment.title]</span></td>
							</tr>
							[/section.comments.spam]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
				<div style="padding:0px 60px 60px;">
					<h2><?php echo esc_html( $mainwp_deletedcomments_heading ); ?></h2>
					<table cellspacing="0">
						<thead>
							<tr>
								<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
								<th><?php echo esc_html( $mainwp_comment_heading ); ?></th>
							</tr>
						</thead>
						<tbody>
							[section.comments.deleted]
							<tr>
								<td>[comment.deleted.date]</td>
								<td><span style="font-weight:bold; color: <?php echo esc_html( $accent_color ); ?>;">[comment.title]</span></td>
							</tr>
							[/section.comments.deleted]
						</tbody>
					</table>
				</div>
				[/config-section-data]
				<?php endif; ?>

				<!-- Backups Data -->
				<?php if ( $plugin_active_backups ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['backups'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_backups_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_backups' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_type_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.backups.created]
								<tr>
									<td>[backup.created.date]</td>
									<td><span style="font-weight:bold;<?php echo esc_html( $accent_color ); ?>">[backup.created.type]</span></td>
								</tr>
								[/section.backups.created]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_backups' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Backups Data -->

				<!-- Google Analytics Data -->
				<?php if ( $plugin_active_ga ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['ga'] ]; ?>
				[config-section-extra max-empty="6" /]
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_analytics_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_ga' ); ?>
						<div style="margin: 20px 0;" id="ga-chart">[ga.visits.chart]</div>
						<table class="left-th" cellspacing="0">
							<tbody>
								<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td>[ga.visits]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_newvisits_heading ); ?></td><td>[ga.new.visits]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_pageviews_heading ); ?></td><td>[ga.pageviews]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_pagevisits_heading ); ?></td><td>[ga.pages.visit]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_bouncerate_heading ); ?></td><td>[ga.bounce.rate]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_averagetime_heading ); ?></td><td>[ga.avg.time]</td></tr>
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_ga' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Google Analytics Data -->


				<!-- Fathom Analytics Data -->
				<?php if ( $plugin_active_fathom ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['fathom'] ]; ?>
				[config-section-extra max-empty="5" /]
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_fathom_analytics_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_ga' ); ?>
						<div style="margin: 20px 0;" id="fathom-chart">[fathom.visits.chart]</div>
						<table class="left-th" cellspacing="0">
							<tbody>
								<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td>[fathom.visits]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_newvisits_heading ); ?></td><td>[fathom.visitors]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_pageviews_heading ); ?></td><td>[fathom.pageviews]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_averagetime_heading ); ?></td><td>[fathom.avg.time]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_bouncerate_heading ); ?></td><td>[fathom.bounce.rate]</td></tr>
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_fathom' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Fathom Analytics Data -->

				<!-- Piwik Analytics Data -->
				<?php if ( $plugin_active_piwik ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['matomo'] ]; ?>
				[config-section-extra max-empty="6" /]
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_matomo_analytics_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_matomo' ); ?>
						<table class="left-th" cellspacing="0">
							<tbody>
								<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td>[piwik.visits]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_newvisits_heading ); ?></td><td>[piwik.new.visits]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_pageviews_heading ); ?></td><td>[piwik.pageviews]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_pagevisits_heading ); ?></td><td>[piwik.pages.visit]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_bouncerate_heading ); ?></td><td>[piwik.bounce.rate]</td></tr>
								<tr><td><?php echo esc_html( $mainwp_averagetime_heading ); ?></td><td>[piwik.avg.time]</td></tr>
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_matomo' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Piwik Analytics Data -->

				<!-- Maintenance Data -->
				<?php if ( $plugin_active_maintenance ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['maintenance'] ]; ?>
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_maintenance_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_maintenance' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th style="width:20%"><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_details_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[section.maintenance.process]
								<tr>
									<td style="width:20%">[maintenance.process.date]</td>
									<td>[maintenance.process.details]</td>
								</tr>
								[/section.maintenance.process]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_maintenance' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Maintenance Data -->

				<!-- Lighthouse Data -->
				<?php if ( $plugin_active_lighthouse ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['lighthouse'] ]; ?>
				[config-section-extra max-empty="6" /]
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_performance_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_lighthouse' ); ?>
						<div style="margin: 30px 0;" id="ga-chart">[lighthouse.audits.desktop]</div>
						<table style="border:1px solid #ddd;width:100%;clear:both;" cellspacing="0">
							<tbody>
								<tr><td><?php echo esc_html( $mainwp_performancescore_heading ); ?></td><td>[lighthouse.performance.desktop] / 100</td></tr>
								<tr><td><?php echo esc_html( $mainwp_accessibilityscore_heading ); ?></td><td>[lighthouse.accessibility.desktop] / 100</td></tr>
								<tr><td><?php echo esc_html( $mainwp_bestpracticesscore_heading ); ?></td><td>[lighthouse.bestpractices.desktop] / 100</td></tr>
								<tr><td><?php echo esc_html( $mainwp_seoscore_heading ); ?></td><td>[lighthouse.seo.desktop] / 100</td></tr>
								<tr><td><?php echo esc_html( $mainwp_date_heading ); ?></td><td>[lighthouse.lastcheck.desktop]</td></tr>
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_lighthouse' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Lighthouse Data -->

				<!-- SSL Data -->
				<?php if ( $plugin_active_ssl ) : ?>
					[config-section-data]
					[config-section-extra max-empty="7" /]
					<?php echo $config_tokens[ $showhide_values['ssl'] ]; ?>
					<div style="padding:0px 30px 30px;">
						<div style="padding:0px 30px 30px;">
							<h2><?php echo esc_html( $mainwp_ssl_heading ); ?></h2>
							<table class="mainwp-report-table">
								<tbody>
									<tr><td><?php echo esc_html( $mainwp_ssl_cname_heading ); ?></td><td>[ssl.monitor.name]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_ssl_issuer_heading ); ?></td><td>[ssl.monitor.issuer]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_ssl_valid_from_heading ); ?></td><td>[ssl.monitor.valid.from]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_ssl_valid_to_heading ); ?></td><td>[ssl.monitor.valid.to]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_ssl_expires_heading ); ?></td><td><?php esc_html_e( 'In', 'mainwp-pro-reports-extension' ); ?> [ssl.monitor.expires] <?php esc_html_e( 'days', 'mainwp-pro-reports-extension' ); ?></td></tr>
									<tr><td><?php echo esc_html( $mainwp_ssl_status_heading ); ?></td><td>[ssl.monitor.status]</td></tr>
									<tr><td><?php echo esc_html( $mainwp_ssl_last_check_heading ); ?></td><td>[ssl.monitor.last.check]</td></tr>
								</tbody>
							</table>
						</div>
					</div>
					[/config-section-data]
				<?php endif; ?>

				<!-- Atarim Data -->
				<?php if ( $plugin_active_atarim ) : ?>
				[config-section-data]
					<?php echo $config_tokens[ $showhide_values['atarim'] ]; ?>
				[config-section-extra max-empty="2" /]
				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<h2><?php echo esc_html( $mainwp_atarim_heading ); ?></h2>
						<?php do_action( 'mainwp_pro_reports_before_atarim' ); ?>
						<table cellspacing="0">
							<thead>
								<tr>
									<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_task_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[atarim.all.tasks]
							</tbody>
						</table>
					</div>
					<div style="padding:0px 30px 30px;">
						<table cellspacing="0">
							<thead>
								<tr>
									<th><?php echo esc_html( $mainwp_date_heading ); ?></th>
									<th><?php echo esc_html( $mainwp_task_heading ); ?></th>
								</tr>
							</thead>
							<tbody>
								[atarim.billable.tasks]
							</tbody>
						</table>
						<?php do_action( 'mainwp_pro_reports_after_atarim' ); ?>
					</div>
				</div>
				[/config-section-data]
				<?php endif; ?>
				<!-- End Atarim Data -->

				<div style="padding:0px 30px 30px;">
					<div style="padding:0px 30px 30px;">
						<p><?php echo MainWP_Pro_Reports_Utility::esc_content( $outro ); ?></p>
					</div>
				</div>
			</div>
		</main>
		<footer></footer>
	</body>
</html>
