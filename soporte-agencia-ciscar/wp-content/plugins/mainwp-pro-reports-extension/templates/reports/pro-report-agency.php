<?php
/*
Template Name: MainWP Pro Report Agency Template
Description: Report template suitable for agencies for the MainWP Pro Reports extension.
Version: 1.0
Author: MainWP
Screenshot URI: ../wp-content/plugins/mainwp-pro-reports-extension/images/agency-template.jpg
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
$mainwp_timetracker_heading      = MainWP_Pro_Reports_Overview::get_content_heading( 'time_tracker_heading', $report_settings, esc_html__( 'Time Tracker', 'mainwp-pro-reports-extension' ) );


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
	$mainwp_expirydate_heading         = isset( $report_settings['mainwp_expirydate_heading'] ) ? $report_settings['mainwp_expirydate_heading'] : esc_html__( 'Expiry Date', 'mainwp-pro-reports-extension' );
	$mainwp_expiresin_heading          = isset( $report_settings['mainwp_expiresin_heading'] ) ? $report_settings['mainwp_expiresin_heading'] : esc_html__( 'Expires In', 'mainwp-pro-reports-extension' );
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
	$mainwp_expirydate_heading         = esc_html__( 'Expiry Date', 'mainwp-pro-reports-extension' );
	$mainwp_expiresin_heading          = esc_html__( 'Expires In', 'mainwp-pro-reports-extension' );
}

$accent_background = $accent_color;

$header_image_id = $report->header_image_id;
$logo_id         = $report->logo_id;

if ( $header_image_id ) {
	$header_image = MainWP_Pro_Reports_Utility::get_attachment_url( $header_image_id );
} else {
	$header_image = MainWP_Pro_Reports_Utility::get_included_image_url( 'agency-report.jpg' );
}

$page_bg_image = MainWP_Pro_Reports_Utility::get_included_image_url( 'agency-report-page-bg.png' );

$showhide_values = @json_decode( $report->showhide_sections, 1 );

if ( ! is_array( $showhide_values ) ) {
	$showhide_values = array();
}

$showhide_values = array_merge( $default_config, $showhide_values );


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
		@page { margin: 0; padding-top: 50px !important; background-color: <?php echo esc_html( ! empty( $bg_color ) ? $bg_color : '#ffffff' ); ?> !important; }
		.canvasWrapper { background-color: <?php echo esc_html( ! empty( $bg_color ) ? $bg_color : '#ffffff' ); ?> !important; }
		body {
			background-color: <?php echo esc_html( ! empty( $bg_color ) ? $bg_color : '#ffffff' ); ?>;
			background-image: url( "<?php echo $page_bg_image; ?>" ); background-repeat: no-repeat; background-position: center bottom;
			color: <?php echo ! empty( $text_color ) ? $text_color : '#191c1e'; ?>;
			font-size: 13px;
			font-family: 'Inter', 'DejaVu Sans', sans-serif;
			max-width: 100%;
		}
		.page-break { page-break-after: always; }
		.mainwp-report-clear { clear: both; }
		.mainwp-report-segment { padding: 25px 50px; }
		.mainwp-report-segment h1 { font-size: 26px; color: <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; border-bottom: 2px solid #e6e8ea; padding-bottom: 10px; margin-bottom: 20px; font-weight: 700; }
		.mainwp-report-segment h3 { font-size: 18px; color: <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; padding-bottom: 12px; border-bottom: 3px solid <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; margin-top: 25px; font-weight: 600; }
		.mainwp-report-grid { clear: both; }

		.mainwp-report-grid .mainwp-report-column-2 { width: 46%; margin: 2%; float: left; background: #f7f9fb; border: 1px solid #e0e3e5; border-radius: 6px; padding: 16px; box-sizing: border-box; }

		.mainwp-report-grid .mainwp-report-column-2 h4 { font-size: 15px; color: <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; padding-bottom: 10px; margin-top: 0; border-bottom: 3px solid <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; font-weight: 700; }
		.mainwp-report-grid .mainwp-report-column-3 { width: 30%; float:left; margin:2%; padding-top: 30px; }
		.mainwp-report-grid .mainwp-report-column-3 h4 { padding-bottom: 15px; border-bottom: 4px solid <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; }
		.mainwp-report-list .item { padding: 8px 4px; border-bottom: 1px solid #eeeeee; font-size: 13px; }
		.mainwp-report-list .item:last-child { border-bottom: none !important; }
		.mainwp-report-list .item strong { float: right; color: #003a62; }
		.mainwp-report-first-page { background-color: <?php echo esc_html( ! empty( $first_page_bg_color ) && $first_page_bg_color !== '#ffffff' ? $first_page_bg_color : '#003a62' ); ?>; height: 100%; background-image: url( "<?php echo $header_image; ?>" ); background-repeat: no-repeat; background-position: center bottom; background-size: contain;}
		.mainwp-report-title { font-size: 64px; font-weight: bolder; color: <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; padding: 180px 50px 30px 50px; line-height: 1.05; }
		.mainwp-report-subtitle { margin-left: 0; margin-right: 0; background-color: <?php echo ! empty( $accent_color ) ? $accent_color : '#065186'; ?>; padding: 22px 50px; }
		.mainwp-report-subtitle p { font-size: 16px; color: #ffffff; font-weight: bold; margin: 4px 0; }
		.mainwp-report-table { border: 1px solid #e0e3e5; width: 100%; border-spacing: 0px; margin: 25px 0; background: <?php echo ! empty( $table_background_color ) ? $table_background_color : '#f7f9fb'; ?>; border-radius: 6px; overflow: hidden; }
		.mainwp-report-table thead { background-color: <?php echo ! empty( $table_header_color ) ? $table_header_color : '#003a62'; ?>; color: <?php echo ! empty( $table_header_text_color ) ? $table_header_text_color : '#ffffff'; ?>; }
		.mainwp-report-table thead tr th { padding: 11px 14px; font-weight: 600; text-transform: uppercase; font-size: 12px; }
		.mainwp-report-table tbody tr { }
		.mainwp-report-table tbody tr td { padding: 10px 14px; border-bottom: 1px solid <?php echo ! empty( $table_border_color ) ? $table_border_color : '#e0e3e5'; ?> !important; background: <?php echo ! empty( $table_background_color ) ? $table_background_color : '#ffffff'; ?>;}
		.mainwp-report-table tbody tr:last-child td { border-bottom: none !important; }
		</style>
	</head>
	<body>
		<main>
			<div class="mainwp-report-first-page">
				<div class="mainwp-report-segment">
					<div style="text-align:left; padding-top: 30px;">
						<?php if ( $logo_id ) : ?>
							<img src="[logo.url]" alt="Agencia Ciscar Logo" style="max-width:240px;height:auto;"/>
						<?php endif; ?>
					</div>
					<div class="mainwp-report-title"><?php echo esc_html( $heading ); ?></div>
					<div class="mainwp-report-subtitle">
						<p>[client.site.url]</p>
						<p>[report.daterange]</p>
					</div>
				</div>
			</div>

			<div class="page-break"></div>

			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_summary_heading ); ?></h1>
				<div style="font-size: 14px; line-height: 1.6; margin-bottom: 25px; color: #41474f;"><?php echo $intro; ?></div>

				<div class="mainwp-report-grid">

					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_general_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_version_heading ); ?>: <strong>[client.site.version]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_theme_heading ); ?>: <strong>[client.site.theme]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_phpversion_heading ); ?>: <strong>[client.site.php]</strong></div>
						</div>
					</div>

					<div class="mainwp-report-column-2">
						<h4>Seguridad & Protección Perimetral</h4>
						<div class="mainwp-report-list">
							<div class="item">CDN & WAF Cloudflare: <strong style="color: #065186;">[cloudflare.status] ([cloudflare.cache.ratio] caché)</strong></div>
							<div class="item">WP Cerber Security Pro: <strong style="color: #065186;">[cerber.blocks.count] bloqueos ([cerber.spam.count] spam)</strong></div>
							<div class="item">Auditoría e Integridad WP: <strong style="color: #2e7d32;">[server.integrity.status]</strong></div>
						</div>
					</div>
					[config-section-parent-data]
					[config-section-parent-extra max-empty="3" /]
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_updates_heading ); ?></h4>
						<div class="mainwp-report-list">
							[config-section-data]
							[config-section-extra max-empty="1" /]
							<?php echo $config_tokens[ $showhide_values['plugins-updates'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_pluginsupdates_heading ); ?>: <strong>[plugin.updated.count]</strong></div>
							[/config-section-data]
							[config-section-data]
							[config-section-extra max-empty="1" /]
							<?php echo $config_tokens[ $showhide_values['themes-updates'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_themesupdates_heading ); ?>: <strong>[theme.updated.count]</strong></div>
							[/config-section-data]
							[config-section-data]
							[config-section-extra max-empty="1" /]
							<?php echo $config_tokens[ $showhide_values['wp-update'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_wpupdates_heading ); ?>: <strong>[wordpress.updated.count]</strong></div> <?php //phpcs:ignore -- wordpress. ?>
							[/config-section-data]
						</div>
					</div>
					[/config-section-parent-data]

					<div class="mainwp-report-clear"></div>

					[config-section-data]
					[config-section-extra max-empty="3" /]
					<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_posts_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_newposts_heading ); ?>: <strong>[post.created.count]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_updatedposts_heading ); ?>: <strong>[post.updated.count]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_deletedposts_heading ); ?>: <strong>[post.deleted.count]</strong></div>
						</div>
					</div>
					[/config-section-data]

					[config-section-data]
					[config-section-extra max-empty="3" /]
					<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_pages_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_newpages_heading ); ?>: <strong>[page.created.count]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_updatedpages_heading ); ?>: <strong>[page.updated.count]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_deletedpages_heading ); ?>: <strong>[page.deleted.count]</strong></div>
						</div>
					</div>
					[/config-section-data]

					<div class="mainwp-report-clear"></div>

					<?php if ( $plugin_active_domain ) : ?>
					[config-section-data]
					[config-section-extra max-empty="3" /]
						<?php echo $config_tokens[ $showhide_values['domain'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_domain_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_status_heading ); ?>: <strong>[domain.monitor.status]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_expirydate_heading ); ?>: <strong>[domain.monitor.expiry.date]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_expiresin_heading ); ?>: <strong>[domain.monitor.expires] <?php echo esc_html__( 'days', 'mainwp-pro-reports-extension' ); ?></strong></div>
						</div>
					</div>
					[/config-section-data]
					<?php endif; ?>

					<?php if ( $plugin_active_backups || $plugin_active_uptime || $plugin_active_maintenance || $plugin_active_comments ) : ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_misc_heading ); ?></h4>
						<div class="mainwp-report-list">
							<?php if ( $plugin_active_backups ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['backups'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_backups_heading ); ?>: <strong>[backup.created.count]</strong></div>
							[/config-section-data]
							<?php endif; ?>
							<?php if ( $plugin_active_uptime ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['aum'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_overalluptime_heading ); ?>: <strong>[aum.alltimeuptimeratio]</strong></div>
							[/config-section-data]
							<?php endif; ?>
							<?php if ( $plugin_active_maintenance ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['maintenance'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_maintenance_heading ); ?>: <strong>[maintenance.process.count]</strong></div>
							[/config-section-data]
							<?php endif; ?>
							<?php if ( $plugin_active_comments ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_newcomments_heading ); ?>: <strong>[comment.created.count]</strong></div>
							[/config-section-data]
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>

					<div class="mainwp-report-clear"></div>

					<?php if ( $plugin_active_lighthouse ) : ?>
					[config-section-data]
					[config-section-extra max-empty="2" /]
						<?php echo $config_tokens[ $showhide_values['lighthouse'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_performance_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_desktop_heading ); ?>: <strong>[lighthouse.performance.desktop]/100</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_mobile_heading ); ?>: <strong>[lighthouse.performance.mobile]/100</strong></div>
						</div>
					</div>
					[/config-section-data]
					<?php endif; ?>

					<?php if ( $plugin_active_sucuri || $plugin_active_wordfence || $plugin_active_itsecurity ) : ?>
					[config-section-parent-data]
					[config-section-parent-extra max-empty="3" /]
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_security_heading ); ?></h4>
						<div class="mainwp-report-list">
							<?php if ( $plugin_active_wordfence ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['wordfence'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_wordfence_heading ); ?>: <strong>[wordfence.scan.count]</strong></div>
							[/config-section-data]
							<?php endif; ?>
							<?php if ( $plugin_active_sucuri ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['security'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_sucuri_heading ); ?>: <strong>[sucuri.checks.count]</strong></div>
							[/config-section-data]
							<?php endif; ?>
							<?php if ( $plugin_active_itsecurity ) : ?>
							[config-section-data]
							[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['itsecurity'] ]; ?>
							<div class="item"><?php echo esc_html( $mainwp_ithemes_heading ); ?>: <strong>[ithemes.scan.count]</strong></div>
							[/config-section-data]
							<?php endif; ?>
							<?php if ( $plugin_active_timetracker ) : ?>
							<?php endif; ?>
						</div>
					</div>
					[/config-section-parent-data]
					<?php endif; ?>

					<div class="mainwp-report-clear"></div>

					<?php if ( $plugin_active_protect ) : ?>
					[config-section-data]
					[config-section-extra max-empty="2" /]
						<?php echo $config_tokens[ $showhide_values['protect'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_vuln_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_pluginsthreats_heading ); ?>: <strong>[jetpack.protect.plugins.count]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_themesthreats_heading ); ?>: <strong>[jetpack.protect.themes.count]</strong></div>
						</div>
					</div>
					[/config-section-data]
					<?php endif; ?>

					<?php if ( $plugin_active_ga ) : ?>
					[config-section-data]
					[config-section-extra max-empty="2" /]
						<?php echo $config_tokens[ $showhide_values['ga'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_analytics_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_websitevisits_heading ); ?>: <strong>[ga.visits]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_newvisits_heading ); ?>: <strong>[ga.new.visits]</strong></div>
						</div>
					</div>
					[/config-section-data]
					<?php endif; ?>

					<?php if ( $plugin_active_fathom ) : ?>
					[config-section-data]
					[config-section-extra max-empty="1" /]
						<?php echo $config_tokens[ $showhide_values['fathom'] ]; ?>
					<div class="mainwp-report-column-2">
						<h4><?php echo esc_html( $mainwp_fathom_analytics_heading ); ?></h4>
						<div class="mainwp-report-list">
							<div class="item"><?php echo esc_html( $mainwp_websitevisits_heading ); ?>: <strong>[fathom.visits]</strong></div>
							<div class="item"><?php echo esc_html( $mainwp_newvisits_heading ); ?>: <strong>[fathom.visitors]</strong></div>
						</div>
					</div>
					[/config-section-data]
					<?php endif; ?>

				</div>
				<div class="mainwp-report-clear"></div>
			</div>

			<div class="mainwp-report-segment">
				<h1>Estado de Ciberseguridad & Rendimiento CDN</h1>
				<p style="margin-bottom: 20px; font-size: 13px; line-height: 1.6; color: #41474f;">
					Tu sitio web dispone de un sistema de doble barrera de seguridad perimetral y a nivel de servidor administrado por <strong>Agencia Císcar</strong>:
				</p>
				<table class="mainwp-report-table">
					<thead>
						<tr>
							<th style="text-align:left; width: 26%;">Capa de Seguridad</th>
							<th style="text-align:left; width: 49%;">Actividad y Métricas del Periodo</th>
							<th style="text-align:right; width: 25%;">Estado y Auditoría</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td style="text-align:left; vertical-align:top;">
								<strong style="color:#003a62; font-size:14px;">Cloudflare Enterprise</strong><br>
								<span style="font-size:11px; color:#666;">CDN Global & Firewall L7</span>
							</td>
							<td style="text-align:left; vertical-align:top; color:#41474f;">
								<div style="margin-bottom:6px; font-size:13px;">Mitigación DDoS, filtrado en borde y aceleración CDN global.</div>
								<div style="background-color:#f8fafc; border-left:3px solid #065186; padding:8px 12px; font-size:12px; line-height:1.6; border-radius:2px;">
									• <strong>Amenazas y DDoS mitigados:</strong> [cloudflare.threats.mitigated] intrusiones<br>
									• <strong>Ahorro y eficiencia CDN:</strong> [cloudflare.cache.ratio] servido en caché<br>
									• <strong>Protocolo SSL/TLS:</strong> [cloudflare.ssl.status]
								</div>
							</td>
							<td style="text-align:right; vertical-align:top; color:#2e7d32; font-weight:bold; font-size:13px;">
								&#10004; [cloudflare.status]
							</td>
						</tr>
						<tr>
							<td style="text-align:left; vertical-align:top;">
								<strong style="color:#003a62; font-size:14px;">WP Cerber Security Pro</strong><br>
								<span style="font-size:11px; color:#666;">Firewall WP & Anti-Spam</span>
							</td>
							<td style="text-align:left; vertical-align:top; color:#41474f;">
								<div style="margin-bottom:6px; font-size:13px;">Escaneo profundo de malware, protección antispam y fuerza bruta.</div>
								<div style="background-color:#f8fafc; border-left:3px solid #F15A29; padding:8px 12px; font-size:12px; line-height:1.6; border-radius:2px;">
									• <strong>Ataques y accesos bloqueados:</strong> [cerber.blocks.count] intentos<br>
									• <strong>Comentarios & Spam rechazados:</strong> [cerber.spam.count] solicitudes<br>
									• <strong>Auditoría de archivos:</strong> [cerber.scans.count] inspeccionados<br>
									• <strong>Último escaneo profundo:</strong> [cerber.last_scan_date]
								</div>
							</td>
							<td style="text-align:right; vertical-align:top; color:#2e7d32; font-weight:bold; font-size:13px;">
								&#10004; [cerber.status]
							</td>
						</tr>
						<tr>
							<td style="text-align:left; vertical-align:top;">
								<strong style="color:#003a62; font-size:14px;">Auditoría e Integridad</strong><br>
								<span style="font-size:11px; color:#666;">Núcleo WP & Servidor</span>
							</td>
							<td style="text-align:left; vertical-align:top; color:#41474f;">
								<div style="margin-bottom:6px; font-size:13px;">Monitoreo constante de logs del sistema y endurecimiento de WordPress.</div>
								<div style="background-color:#f8fafc; border-left:3px solid #2e7d32; padding:8px 12px; font-size:12px; line-height:1.6; border-radius:2px;">
									• <strong>Integridad del núcleo:</strong> Archivos de core verificados intactos<br>
									• <strong>Permisos de ficheros:</strong> Reglas de protección críticas activas
								</div>
							</td>
							<td style="text-align:right; vertical-align:top; color:#2e7d32; font-weight:bold; font-size:13px;">
								&#10004; [server.integrity.status]
							</td>
						</tr>
					</tbody>
				</table>
			</div>


			<!-- Updates Data -->
			[config-section-parent-data]
			[config-section-parent-extra max-empty="3" /]
			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_updates_heading ); ?></h1>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['wp-update'] ]; ?>
					<h3><?php echo esc_html( $mainwp_wpupdates_heading ); ?></h3>
					<table class="mainwp-report-table">
						<tbody>
							[section.wordpress.updated] <?php //phpcs:ignore -- wordpress. ?>
							<tr>
								<td style="text-align:left">[wordpress.old.version] &rarr; [wordpress.current.version]</td> <?php //phpcs:ignore -- wordpress. ?>
								<td style="text-align:right">[wordpress.updated.date]</td> <?php //phpcs:ignore -- wordpress. ?>
							</tr>
							[/section.wordpress.updated] <?php //phpcs:ignore -- wordpress. ?>
						</tbody>
					</table>
				[/config-section-data]

				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['plugins-updates'] ]; ?>
					<h3><?php echo esc_html( $mainwp_pluginsupdates_heading ); ?></h3>
					<table class="mainwp-report-table">

						<tbody>
							[section.plugins.updated]
							<tr>
								<td style="text-align:left">[plugin.name]</td>
								<td style="text-align:center">[plugin.old.version] &rarr; [plugin.current.version]</td>
								<td style="text-align:right">[plugin.updated.date]</td>
							</tr>
							[/section.plugins.updated]
						</tbody>
					</table>
				[/config-section-data]

				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['themes-updates'] ]; ?>
					<h3><?php echo esc_html( $mainwp_themesupdates_heading ); ?></h3>
					<table class="mainwp-report-table">
						<tbody>
							[section.themes.updated]
							<tr>
								<td style="text-align:left">[theme.name]</td>
								<td style="text-align:center">[theme.old.version] &rarr; [theme.current.version]</td>
								<td style="text-align:right">[theme.updated.date]</td>
							</tr>
							[/section.themes.updated]
						</tbody>
					</table>
				[/config-section-data]
			</div>
			[/config-section-parent-data]

			<!-- Posts & Pages Data -->
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
			<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_posts_heading ); ?></h1>
				<h3><?php echo esc_html( $mainwp_newposts_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.posts.created]
						<tr>
							<td style="text-align:left">[post.title]</td>
							<td style="text-align:right">[post.created.date]</td>
						</tr>
						[/section.posts.created]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_updatedposts_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.posts.updated]
						<tr>
							<td style="text-align:left">[post.title]</td>
							<td style="text-align:right">[post.updated.date]</td>
						</tr>
						[/section.posts.updated]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_deletedposts_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.posts.deleted]
						<tr>
							<td style="text-align:left">[post.title]</td>
							<td style="text-align:right">[post.deleted.date]</td>
						</tr>
						[/section.posts.deleted]
					</tbody>
				</table>
			</div>
			[/config-section-data]

			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
			<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_pages_heading ); ?></h1>
				<h3><?php echo esc_html( $mainwp_newpages_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.pages.created]
						<tr>
							<td style="text-align:left">[page.title]</td>
							<td style="text-align:right">[page.created.date]</td>
						</tr>
						[/section.pages.created]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_updatedpages_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.pages.updated]
						<tr>
							<td style="text-align:left">[page.title]</td>
							<td style="text-align:right">[page.updated.date]</td>
						</tr>
						[/section.pages.updated]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_deletedpages_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.pages.deleted]
						<tr>
							<td style="text-align:left">[page.title]</td>
							<td style="text-align:right">[page.deleted.date]</td>
						</tr>
						[/section.pages.deleted]
					</tbody>
				</table>
			</div>
			[/config-section-data]

			<!-- Users Data -->
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['users'] ]; ?>
			<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_users_heading ); ?></h1>
				<h3><?php echo esc_html( $mainwp_newusers_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.users.created]
						<tr>
							<td style="text-align:left">[user.name]</td>
							<td style="text-align:center">[user.created.role]</td>
							<td style="text-align:right">[user.created.date]</td>
						</tr>
						[/section.users.created]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['users'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_updatedusers_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.users.updated]
						<tr>
							<td style="text-align:left">[user.name]</td>
							<td style="text-align:center">[user.updated.role]</td>
							<td style="text-align:right">[user.updated.date]</td>
						</tr>
						[/section.users.updated]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
			<?php echo $config_tokens[ $showhide_values['users'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_deletedusers_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.users.deleted]
						<tr>
							<td style="text-align:left">[user.name]</td>
							<td style="text-align:center">[user.deleted.role]</td>
							<td style="text-align:right">[user.deleted.date]</td>
						</tr>
						[/section.users.deleted]
					</tbody>
				</table>
			</div>
			[/config-section-data]


			<?php if ( $plugin_active_comments ) : ?>
			[config-section-data]
				<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
			<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_comments_heading ); ?></h1>
				<h3><?php echo esc_html( $mainwp_newcomments_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.comments.created]
						<tr>
							<td style="text-align:left">[comment.title]</td>
							<td style="text-align:right">[comment.created.date]</td>
						</tr>
						[/section.comments.created]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
				<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_updatedcomments_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.comments.updated]
						<tr>
							<td style="text-align:left">[comment.title]</td>
							<td style="text-align:right">[comment.updated.date]</td>
						</tr>
						[/section.comments.updated]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			[config-section-data]
				<?php echo $config_tokens[ $showhide_values['comments'] ]; ?>
			<div class="mainwp-report-segment">
				<h3><?php echo esc_html( $mainwp_deletedcomments_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
						[section.comments.deleted]
						<tr>
							<td style="text-align:left">[comment.title]</td>
							<td style="text-align:right">[comment.deleted.date]</td>
						</tr>
						[/section.comments.deleted]
					</tbody>
				</table>
			</div>
			[/config-section-data]
			<?php endif; ?>

			<?php if ( $plugin_active_timetracker ) : ?>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['time_tracker'] ]; ?>
				<div class="mainwp-report-segment">
					<h1><?php echo esc_html( $mainwp_timetracker_heading ); ?></h1>
						[section.timetracker.tasks]
						<table class="mainwp-report-table" style="margin-top:0px">
							<thead>
								<tr>
									<th colspan="6" style="text-align:left">[timetracker.task.title]</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong>[timetracker.task.status]</strong></td>
									<td style="text-align:left">[timetracker.task.date]</td>
									<td style="text-align:right">[timetracker.task.hourly.price]/h</td>
									<td style="text-align:right">[timetracker.task.duration]</td>
									<td style="text-align:right"><strong>[timetracker.task.total.price]</strong></td>
									<td style="text-align:right"><strong>[timetracker.task.payment.status]</strong></td>
								</tr>
								<tr>
									<td colspan="6">[timetracker.task.description]</td>
								</tr>
							</tbody>
						</table>
					[/section.timetracker.tasks]
				</div>
				[/config-section-data]
			<?php endif; ?>

			<!-- Security Data -->
			<?php if ( $plugin_active_sucuri || $plugin_active_wordfence || $plugin_active_itsecurity ) : ?>
			<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
				<?php if ( $plugin_active_sucuri ) : ?>
				[config-section-data]
				<h1><?php echo esc_html( $mainwp_security_heading ); ?></h1>
					<?php echo $config_tokens[ $showhide_values['security'] ]; ?>
				<h3><?php echo esc_html( $mainwp_sucuri_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
					[section.sucuri.checks]
						<tr>
							<td>[sucuri.check.status]</td>
							<td style="text-align:center">[sucuri.check.webtrust]</td>
							<td style="text-align:right">[sucuri.check.date]</td>
						</tr>
					[/section.sucuri.checks]
					</tbody>
				</table>
				[/config-section-data]
				<?php endif; ?>
				<?php if ( $plugin_active_wordfence ) : ?>
					[config-section-data]
					<h1><?php echo esc_html( $mainwp_security_heading ); ?></h1>
					<?php echo $config_tokens[ $showhide_values['wordfence'] ]; ?>
					<h3><?php echo esc_html( $mainwp_wordfence_heading ); ?></h3>
					<table class="mainwp-report-table">
						<tbody>
						[section.wordfence.scan]
							<tr>
								<td>[wordfence.scan.result]</td>
								<td style="text-align:left">[wordfence.scan.details]</td>
								<td style="text-align:right">[wordfence.scan.date]</td>
							</tr>
						[/section.wordfence.scan]
						</tbody>
					</table>
					[/config-section-data]
				<?php endif; ?>
				<?php if ( $plugin_active_itsecurity ) : ?>
				[config-section-data]
					<h1><?php echo esc_html( $mainwp_security_heading ); ?></h1>
					<?php echo $config_tokens[ $showhide_values['itsecurity'] ]; ?>
				<h3><?php echo esc_html( $mainwp_ithemes_heading ); ?></h3>
				<table class="mainwp-report-table">
					<tbody>
					[section.ithemes.scan]
						<tr>
							<td>[ithemes.scan.result]</td>
							<td style="text-align:left">[ithemes.scan.details]</td>
							<td style="text-align:right">[ithemes.scan.date]</td>
						</tr>
					[/section.ithemes.scan]
					</tbody>
				</table>
				<h4><?php echo esc_html( $mainwp_ithemesbfp_heading ); ?></h4>
				<div class="mainwp-report-list">
					<div class="item"><?php echo esc_html__( 'Total Blocks: ', 'mainwp-pro-reports-extension' ); ?> <strong>[ithemes.blocked.count]</strong></div>
					<div class="item"><?php echo esc_html__( 'Total Lockouts: ', 'mainwp-pro-reports-extension' ); ?> <strong>[ithemes.lockout.count]</strong></div>
				</div>
				[/config-section-data]
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Backups Data -->
			<?php if ( $plugin_active_backups ) : ?>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['backups'] ]; ?>
				<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
				<h1><?php echo esc_html( $mainwp_backups_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							[section.backups.created]
							<tr>
								<td style="text-align:left">[backup.created.type]</td>
								<td style="text-align:right">[backup.created.date]</td>
							</tr>
							[/section.backups.created]
						</tbody>
					</table>
				</div>
				[/config-section-data]
			<?php endif; ?>
			<!-- End Backups Data -->

			<!-- Analytics Data -->
			<?php if ( $plugin_active_ga ) : ?>
			[config-section-data]
				<?php echo $config_tokens[ $showhide_values['ga'] ]; ?>
				<!--<div class="page-break"></div>-->
					<div class="mainwp-report-segment">
					[config-section-extra max-empty="7" /]
					<h1><?php echo esc_html( $mainwp_analytics_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td style="text-align:right">[ga.visits]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_newvisits_heading ); ?></td><td style="text-align:right">[ga.new.visits]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_pageviews_heading ); ?></td><td style="text-align:right">[ga.pageviews]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_pagevisits_heading ); ?></td><td style="text-align:right">[ga.pages.visit]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_bouncerate_heading ); ?></td><td style="text-align:right">[ga.bounce.rate]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_averagetime_heading ); ?></td><td style="text-align:right">[ga.avg.time]</td></tr>
						</tbody>
					</table>
					<div style="text-align:center; margin-top: 100px;">[ga.visits.chart]</div>
				</div>
				[/config-section-data]
			<?php endif; ?>
			<!-- Analytics Data -->

			<!-- Analytics Data -->
			<?php if ( $plugin_active_fathom ) : ?>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['fathom'] ]; ?>
				<!--<div class="page-break"></div>-->
					<div class="mainwp-report-segment">
					[config-section-extra max-empty="5" /]
					<h1><?php echo esc_html( $mainwp_fathom_analytics_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td style="text-align:right">[fathom.visits]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_newvisits_heading ); ?></td><td style="text-align:right">[fathom.visitors]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_pageviews_heading ); ?></td><td style="text-align:right">[fathom.pageviews]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_averagetime_heading ); ?></td><td style="text-align:right">[fathom.avg.time]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_bouncerate_heading ); ?></td><td style="text-align:right">[fathom.bounce.rate]</td></tr>
						</tbody>
					</table>
					<div style="text-align:center; margin-top: 100px;">[fathom.visits.chart]</div>
				</div>
				[/config-section-data]
			<?php endif; ?>
			<!-- Analytics Data -->


			<!-- Matomo Data -->
			<?php if ( $plugin_active_piwik ) : ?>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['matomo'] ]; ?>
				<!--<div class="page-break"></div>-->
			<div class="mainwp-report-segment">
					[config-section-extra max-empty="6" /]
					<h1><?php echo esc_html( $mainwp_matomo_analytics_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							<tr><td><?php echo esc_html( $mainwp_websitevisits_heading ); ?></td><td style="text-align:right">[piwik.visits]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_newvisits_heading ); ?></td><td style="text-align:right">[piwik.new.visits]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_pageviews_heading ); ?></td><td style="text-align:right">[piwik.pageviews]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_pagevisits_heading ); ?></td><td style="text-align:right">[piwik.pages.visit]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_bouncerate_heading ); ?></td><td style="text-align:right">[piwik.bounce.rate]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_averagetime_heading ); ?></td><td style="text-align:right">[piwik.avg.time]</td></tr>
						</tbody>
					</table>
					</div>
				[/config-section-data]

			<?php endif; ?>
			<!-- End Matomo Data -->

			<!-- Uptime & Performance Data -->
			<?php
			if ( $plugin_active_uptime || $plugin_active_lighthouse ) :
				?>
				<!--<div class="page-break"></div>--><?php endif; ?>
				<?php if ( $plugin_active_uptime ) : ?>
				[config-section-data]
				[config-section-extra max-empty="6" /]
				<div class="mainwp-report-segment">
					<?php echo $config_tokens[ $showhide_values['uptime'] ]; ?>
					<h1><?php echo esc_html( $mainwp_uptime_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							<tr><td><?php echo esc_html( $mainwp_overalluptime_heading ); ?></td><td style="text-align:right">[aum.alltimeuptimeratio]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_uptime7_heading ); ?></td><td style="text-align:right">[aum.uptime7]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_uptime15_heading ); ?></td><td style="text-align:right">[aum.uptime15]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_uptime30_heading ); ?></td><td style="text-align:right">[aum.uptime30]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_uptime45_heading ); ?></td><td style="text-align:right">[aum.uptime45]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_uptime60_heading ); ?></td><td style="text-align:right">[aum.uptime60]</td></tr>
						</tbody>
					</table>
					</div>
				[/config-section-data]

			<?php endif; ?>

			<?php if ( $plugin_active_lighthouse ) : ?>
				[config-section-data]
				[config-section-extra max-empty="4" /]
				<?php echo $config_tokens[ $showhide_values['lighthouse'] ]; ?>
				<div class="mainwp-report-segment">
					<h1><?php echo esc_html( $mainwp_performance_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							<tr><td><?php echo esc_html( $mainwp_performancescore_heading ); ?></td><td style="text-align:right">[lighthouse.performance.desktop] / 100</td></tr>
							<tr><td><?php echo esc_html( $mainwp_accessibilityscore_heading ); ?></td><td style="text-align:right">[lighthouse.accessibility.desktop] / 100</td></tr>
							<tr><td><?php echo esc_html( $mainwp_bestpracticesscore_heading ); ?></td><td style="text-align:right">[lighthouse.bestpractices.desktop] / 100</td></tr>
							<tr><td><?php echo esc_html( $mainwp_seoscore_heading ); ?></td><td style="text-align:right">[lighthouse.seo.desktop] / 100</td></tr>
							<tr><td><?php echo esc_html( $mainwp_date_heading ); ?></td><td style="text-align:right">[lighthouse.lastcheck.desktop]</td></tr>
						</tbody>
					</table>
					<div>
					[lighthouse.audits.desktop]
					</div>
				[/config-section-data]
				</div>
			<?php endif; ?>
			<!-- End Lighthouse Data -->

			<!-- SSL Data -->
			<?php if ( $plugin_active_ssl ) : ?>
				[config-section-data]
				[config-section-extra max-empty="7" /]
				<?php echo $config_tokens[ $showhide_values['ssl'] ]; ?>
				<div class="mainwp-report-segment">
					<h1><?php echo esc_html( $mainwp_ssl_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
							<tr><td><?php echo esc_html( $mainwp_ssl_cname_heading ); ?></td><td style="text-align:right">[ssl.monitor.name]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_ssl_issuer_heading ); ?></td><td style="text-align:right">[ssl.monitor.issuer]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_ssl_valid_from_heading ); ?></td><td style="text-align:right">[ssl.monitor.valid.from]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_ssl_valid_to_heading ); ?></td><td style="text-align:right">[ssl.monitor.valid.to]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_ssl_expires_heading ); ?></td><td style="text-align:right"><?php esc_html_e( 'In', 'mainwp-pro-reports-extension' ); ?> [ssl.monitor.expires] <?php esc_html_e( 'days', 'mainwp-pro-reports-extension' ); ?></td></tr>
							<tr><td><?php echo esc_html( $mainwp_ssl_status_heading ); ?></td><td style="text-align:right">[ssl.monitor.status]</td></tr>
							<tr><td><?php echo esc_html( $mainwp_ssl_last_check_heading ); ?></td><td style="text-align:right">[ssl.monitor.last.check]</td></tr>
						</tbody>
					</table>
				</div>
				[/config-section-data]
			<?php endif; ?>

			<!-- Maintenance Data -->
			<?php if ( $plugin_active_maintenance ) : ?>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['maintenance'] ]; ?>
				<!--<div class="page-break"></div>-->
				<div class="mainwp-report-segment">

					<h1><?php echo esc_html( $mainwp_maintenance_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>
						[section.maintenance.process]
							<tr><td>[maintenance.process.details]</td><td style="text-align:right;width:20%;">[maintenance.process.date]</td></tr>
						[/section.maintenance.process]
						</tbody>
					</table>
				</div>
				[/config-section-data]
			<?php endif; ?>
			<!-- End Maintenance Data -->

			<!-- Atarim Data -->
			<?php if ( $plugin_active_atarim ) : ?>
				[config-section-data]
				<?php echo $config_tokens[ $showhide_values['atarim'] ]; ?>
				[config-section-extra max-empty="2" /]
				<!--<div class="page-break"></div>-->
				<div class="mainwp-report-segment">
					<h1><?php echo esc_html( $mainwp_atarim_heading ); ?></h1>
					<table class="mainwp-report-table">
						<tbody>[atarim.all.tasks]</tbody>
					</table>
					<table class="mainwp-report-table">
						<tbody>[atarim.billable.tasks]</tbody>
					</table>
				</div>
				[/config-section-data]
			<?php endif; ?>
				<!-- End Atarim Data -->

			<?php if ( '' != $outro ) : ?>
				<!--<div class="page-break"></div>-->
				<div class="mainwp-report-segment">
				<div><?php echo $outro; ?></div>
				</div>
			<?php endif; ?>

		</main>
	</body>
</html>
