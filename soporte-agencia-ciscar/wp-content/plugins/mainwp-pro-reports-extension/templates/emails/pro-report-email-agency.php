<?php

defined( 'ABSPATH' ) || exit;

$heading    = $report->heading;
$from_email = $report->femail;
$logo_id    = $report->logo_id;
$intro      = $report->intro;
$intro      = nl2br( $intro ); // to fix

do_action( 'mainwp_pro_reports_email_header' );

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
);

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
$plugin_active_atarim      = is_plugin_active( 'mainwp-atarim-extension/mainwp-atarim-extension.php' ) ? true : false;
$plugin_active_timetracker = is_plugin_active( 'mainwp-time-tracker-extension/mainwp-time-tracker-extension.php' ) ? true : false;

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
}


$showhide_values = ! empty( $report->showhide_sections ) ? @json_decode( $report->showhide_sections, 1 ) : array();

if ( ! is_array( $showhide_values ) ) {
	$showhide_values = array();
}

$showhide_values = array_merge( $default_config, $showhide_values );

?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>
		<style type="text/css">
			table.mainwp-report-table th { padding: 10px; text-align: left; border-bottom: 1px solid #dedede; }
			table.mainwp-report-table td { padding: 10px; text-align: right; border-bottom: 1px solid #dedede; }
		</style>
	</head>
	<body marginwidth="0" topmargin="0" marginheight="0" offset="0" style="background-color:#f7f7f7;">
		<div id="mainwp-report-wrapper">
			<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
				<tr>
					<td align="center" valign="top">
						<?php if ( ! empty( $logo_id ) ) : ?>
						<img src="<?php echo MainWP_Pro_Reports_Utility::get_attachment_url( $logo_id ); ?>" alt="logo" style="max-width:200px;height:auto;margin-top:50px;"/>
						<?php endif; ?>
						<table border="0" cellpadding="0" cellspacing="0" width="600" style="margin-top: 30px; background-color: #ffffff; border: 1px solid #e0e3e5; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border-radius: 6px; overflow: hidden;">
							<tr>
								<td align="center" valign="top">
									<!-- Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #003a62;">
										<tr>
											<td id="header_wrapper" style="padding: 32px 48px; display: block;">
												<h1 style="text-align:center;color:#ffffff; font-size:22px; font-weight:700; margin:0;"><?php echo $heading; ?></h1>
											</td>
										</tr>
									</table>
									<!-- End Header -->
								</td>
							</tr>
							<tr>
					<td align="center" valign="top">
						<div style="padding:30px">
							<p>
							<?php
							if ( $email_message ) {
								$email_message = stripslashes( $email_message );
								echo wp_kses_post( wpautop( wptexturize( $email_message ) ) );
							}
							?>
							</p>
						</div>

						<table cellspacing="0" class="mainwp-report-table" style="margin-bottom:30px; width:90%; border:1px solid #e0e3e5; border-radius:4px;">
							<tbody>
								<tr><th><?php echo __( 'Sitio Web', 'mainwp-pro-reports-extension' ); ?></th><td><a href="[client.site.url]" target="_blank" style="color:#065186; text-decoration:none; font-weight:bold;">[client.site.url]</a></td></tr>
								<tr><th><?php echo __( 'Versión de WordPress', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold;">[client.site.version]</span></td></tr>
								<tr><th><?php echo __( 'Tema Activo', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold;">[client.site.theme]</span></td></tr>
								<tr><th><?php echo __( 'Versión PHP', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold;">[client.site.php]</span></td></tr>
								<tr><th><?php echo __( 'Seguridad Perimetral (Cloudflare CDN)', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; color:#065186;">&#10004; [cloudflare.threats.mitigated] mitigados ([cloudflare.cache.ratio] caché)</span></td></tr>
								<tr><th><?php echo __( 'Firewall WP & Anti-Spam (WP Cerber Security)', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; color:#2e7d32;">&#10004; [cerber.blocks.count] bloqueos / [cerber.scans.count] escaneados</span></td></tr>
								<tr><th><?php echo __( 'Auditoría e Integridad del Servidor', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; color:#2e7d32;">&#10004; [server.integrity.status]</span></td></tr>

								<?php if ( $plugin_active_uptime ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['aum'] ]; ?>
								<tr><th><?php echo __( 'Website Uptime', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[aum.alltimeuptimeratio]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_sucuri ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['security'] ]; ?>
								<tr><th><?php echo __( 'Sucuri Scans', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[sucuri.checks.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_wordfence ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['wordfence'] ]; ?>
								<tr><th><?php echo __( 'Wordfence Scans', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[wordfence.scan.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_itsecurity ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['itsecurity'] ]; ?>
								<tr><th><?php echo __( 'iThemes Security Scans', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[ithemes.scan.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_protect ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['protect'] ]; ?>
								<tr><th><?php echo __( 'Vulnerable Plugins', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[jetpack.protect.plugins.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['plugins-updates'] ]; ?>
								<tr><th><?php echo __( 'Plugins Updated', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[plugin.updated.count]</span></td></tr>
								[/config-section-data]

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['themes-updates'] ]; ?>
								<tr><th><?php echo __( 'Themes Updated', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[theme.updated.count]</span></td></tr>
								[/config-section-data]

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['posts-updates'] ]; ?>
								<tr><th><?php echo __( 'Posts Created', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[post.created.count]</span></td></tr>
								[/config-section-data]

								[config-section-data]
								[config-section-extra max-empty="1" /]
								<?php echo $config_tokens[ $showhide_values['pages-updates'] ]; ?>
								<tr><th><?php echo __( 'Pages Created', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[page.created.count]</span></td></tr>
								[/config-section-data]

								<?php if ( $plugin_active_backups ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['backups'] ]; ?>
								<tr><th><?php echo __( 'Backups Created', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[backup.created.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_maintenance ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['maintenance'] ]; ?>
								<tr><th><?php echo __( 'Database Optimizations', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[maintenance.process.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_domain ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['domain'] ]; ?>
								<tr><th><?php echo __( 'Domain Status', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[domain.monitor.status]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_lighthouse ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['lighthouse'] ]; ?>
								<tr><th><?php echo __( 'Lighthouse Performance', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[lighthouse.performance.desktop] / 100</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_ga ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['ga'] ]; ?>
								<tr><th><?php echo __( 'Total Visits', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[ga.visits]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_fathom ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['fathom'] ]; ?>
								<tr><th><?php echo __( 'Total Visits', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[fathom.visits]</span></td></tr>
								[/config-section-data]
								<?php } ?>

								<?php if ( $plugin_active_piwik ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['matomo'] ]; ?>
								<tr><th><?php echo __( 'Total Visits', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[piwik.visits]</span></td></tr>
								[/config-section-data]
								<?php } ?>
								<?php if ( $plugin_active_timetracker ) { ?>
								[config-section-data]
								[config-section-extra max-empty="1" /]
									<?php echo $config_tokens[ $showhide_values['time_tracker'] ]; ?>
								<tr><th><?php echo __( 'Time Tracker', 'mainwp-pro-reports-extension' ); ?></th><td><span style="font-weight:bold; ">[timetracker.tasks.count]</span></td></tr>
								[/config-section-data]
								<?php } ?>
							</tbody>
						</table>
					</td>
				</tr>
						</table>
					</td>
				</tr>

				<tr>
					<td align="center" valign="top">
						<!-- Footer -->
						<table border="0" cellpadding="10" cellspacing="0" width="600">
							<tr>
								<td valign="top">
									<table border="0" cellpadding="10" cellspacing="0" width="100%">
										<tr>
											<td colspan="2" align="center" valign="middle">
												<?php echo __( 'Have questions? Email us at ', 'mainwp-pro-reports-extension' ) . $from_email; ?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<!-- End Footer -->
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
<?php

do_action( 'mainwp_pro_reports_email_footer' );
