<?php

/*
	Show Help tab screen
*/
function cerber_render_help_page() {
	switch ( crb_admin_get_page() ) {
		case 'cerber-integrity':
			crb_render_scan_help_panel();
			break;
		case 'cerber-nexus':
			crb_render_nexus_help_panel();
			break;
		case 'cerber-recaptcha':
			crb_render_anti_help_panel();
			break;
		default:
			crb_render_general_help_panel();
	}
}

function crb_render_nexus_help_panel() {

	?>
	<div id="crb-help">
		<table id="admin-help">
			<tr>
				<td>

					<div>
						<h2>How remote management works</h2>

						<p>Our technology enables you to manage WP Cerber plugins, monitor activity, and upgrade plugins on multiple WordPress powered websites from a main Cerber.Hub website which is also called a main website.</p>

						<p>To activate this technology, you need to enable a Cerber.Hub main mode on the main website and a managed mode on each website you want to connect to the main website.</p>

						<p>Read more: <a href="https://wpcerber.com/manage-multiple-websites/" target="_blank">
								Manage multiple WP Cerber instances from one dashboard</a></p>

					</div>

					<div>
						<h2>A safety note</h2>

						<p>All access tokens are stored in the databases of the main and managed websites in unencrypted form (plaintext). Store a backup copy of all websites in a safe and trusted place.</p>
					</div>

					<div>
						<h2>Troubleshooting</h2>
						<p>
							If you’re unable to get it working, that may be caused by a number of reasons. Enable the diagnostic log on the main website and on the managed one to obtain more information. You can view the diagnostic log on
							the Tools admin page. Here is a list of the most common causes of issues on the managed website.
						</p>

						<ul>
							<li>A security plugin on the managed website is interfering with the WP Cerber plugin</li>
							<li>A security directive in the .htaccess file on the managed website is blocking incoming requests as suspicious</li>
							<li>A firewall or a proxy service (like Cloudflare) is blocking (filtering out) incoming requests to the managed website</li>
							<li>The IP address of the main website is locked out or on the Blocked IP Access List on the managed website</li>
							<li>The managed mode on the remote website has been re-enabled making the security token saved on the main website invalid</li>
							<li>The IP address of the main website does not match the one set in the access settings on the managed website</li>
						</ul>

					</div>

				</td>
				<td>

					<div>
						<h2>Getting started</h2>

						<p>Start with activating main Cerber.Hub website. Go to the Cerber.Hub admin page and enable main Cerber.Hub mode. Once you’ve done this you can connect managed websites by using security tokens generated on managed
							websites.
						</p>

					</div>

					<div>
						<h2>Connecting managed websites</h2>

						<p>To connect a remote website to the main website as a remote managed website, you need to enable the managed mode on that website. Go to the Cerber.Hub admin page and enable the managed mode. During the activation
							of the managed mode, a unique security access token is generated and saved into the database of the managed website. Keep the token secret.
						</p>
						<p>
							Now, go to your main Cerber.Hub website and click the “Add” button on the “My Websites” admin page. Copy the token and paste it in the “Add a remote website” popup window.
						</p>


					</div>

					<div>
						<h2>Manage websites remotely</h2>

						<p>Once you’ve connected all your remote websites to the main website, you can easily switch between them with a single click in the top navigation menu on the admin bar or by clicking the name of a remote website on
							the “My Websites” page. Once you’ve switched to a remote managed website, use the plugin menu and admin links the way like you do this normally. To switch back to the main website, click the X icon on the admin
							bar.
						</p>
						<p>
							Note: when you’re managing remote website, the color of the admin bar is blue and the left admin menu on the main website is dimmed.
						</p>
					</div>

				</td>
			</tr>
		</table>
	</div>
	<?php

}

function crb_render_scan_help_panel() {

	?>
	<div id="crb-help">
		<table id="admin-help">
			<tr>
				<td>

					<div>
						<h2>Using the malware scanner</h2>

						<p>To start scanning, click either the Start Quick Scan button or the Start Full Scan button. Do
							not close the browser window while the scan is in progress. You may just open a new browser
							tab to do something else on the website. Once the scan is finished you can close the window,
							the results are stored in the DB until the next scan.</p>

						<p>Depending on server performance and the number of files, the Quick scan may take about 3-5
							minutes and the Full scan can take about ten minutes or less.</p>

						<p>During the scan, the plugin verifies plugins, themes, and WordPress by trying to retrieve
							checksum data from wordpress.org. If the integrity data is not available, you can upload an
							appropriate source ZIP archive for a plugin or a theme. The plugin will use it to detect
							changes in files. You need to do it once, after the first scan.</p>

						<p>Read more: <a href="https://wpcerber.com/malware-scanner-settings/" target="_blank">Cerber
								Security Scanner Settings</a></p>

					</div>

					<div>
						<h2>Interpreting scan results</h2>

						<p>The scanner shows you a list of issues and possible actions you can take. If the integrity of
							an object has been verified, you see a green mark Verified. If you see the “Integrity data
							not found” message, you need to upload a reference ZIP archive by clicking “Resolve issue”.
							For all other issues, click on an appropriate issue link. To view the content of a file,
							click on its name.</p>
					</div>

					<div>
						<h2>Troubleshooting</h2>

						<p>If the scanner window stops responding or updating, it usually means the process of scanning on the server is hung. This might happen due to a number of reasons but typically this happens due to a misconfigured
							server or it’s caused by some hosting limitations. Do the following:</p>

						<ul>
							<li>Open the browser console (use F12 key on PC or Cmd + Option + J on Mac) and check it for CERBER ERROR messages</li>
							<li>Try to disable scanning the session directory or the temp directory (or both) in the scanner settings</li>
							<li>Enable diagnostic logging in the scanner settings and check the log after scanning</li>
						</ul>

						<p>Note: The scanner requires the cURL library to be enabled for PHP scripts. Usually, it's enabled by
							default.</p>

						<p>Read more: <a href="https://wpcerber.com/wordpress-security-scanner/" target="_blank">Malware
								Scanner & Integrity Checker</a></p>

					</div>

					<div>
						<h2>What's the Quick Scan?</h2>

						<p>During the Quick Scan, the scanner verifies the integrity and inspects the code of all files
							with executable extensions only.</p>

						<h2>What's the Full Scan?</h2>

						<p>During the Full Scan, the scanner verifies the integrity and inspects the content of all
							files on the website. All media files are scanned for malicious payload.</p>

						<p>Read more: <a href="https://wpcerber.com/wordpress-security-scanner-scan-malware-detect/"
						                 target="_blank">What Cerber Security Scanner scans and detects</a>
					</div>

				</td>
				<td>

					<div>
						<h2>Configuring scheduled scans</h2>

						<p>In the Automated recurring scan schedule section you set up your schedule. Select the desired
							frequency of the Quick Scan and specify the time of the Full Scan. By default, all automated
							recurring scans are turned off.
						</p>

						<p>The Scan results reporting section is about reporting. Here you can easily and flexibly
							configure conditions for generating and sending reports.
						</p>

						<p>The email report will only include issues that match conditions in the Report an issue if any
							of the following is true filter. So this setting works as a filter for issues you want to
							get in a email report. The report will only be sent if there are issues to report and the
							following condition is true.</p>

						<p>The second condition is configured with Send email report. The report will be sent if a
							selected condition is true. The last option is the most restrictive.</p>

						<p>Read more: <a href="https://wpcerber.com/automated-recurring-malware-scans/" target="_blank">Automated
								recurring scans and email reporting</a></p>

					</div>

					<div>
						<h2>Automatic cleanup of malware</h2>

						<p>The plugin can automatically delete malicious and suspicious files. Automatic removal
							policies are enforced at the end of every scheduled scan based on its results. The list of
							files to be deleted depends on the scanner settings. By default automatic removal is
							disabled. It's advised to enable it at least for unattended files and files in the media
							uploads folder for files with the High severity risk. The plugin deletes only files that
							have malicious or suspicious code payload. All detected malicious and suspicious files are
							moved to the quarantine.
						</p>

						Read more: <a href="https://wpcerber.com/automatic-malware-removal-wordpress/" target="_blank">Automatic cleanup of malware and suspicious files</a>

					</div>

					<div>
						<h2>Deleting files</h2>

						<p>Usually, you can delete any suspicious or malicious file if it has a checkbox in its row in
							the leftmost cell. Before deleting a file, click the issue link in its row to see an
							explanation. When you delete a file WP Cerber moves it to a quarantine folder.</p>
					</div>

					<div>
						<h2>Restoring deleted files</h2>

						<p>If you delete an important file by chance, you can restore the file from the quarantine. To
							restore one or more files from within the WordPress dashboard, click the Quarantine tab.
							Find the filename in the File column and click Restore in the Action column. The file will
							be restored to its original location.</p>

						<p>To restore a file manually, you need to use a file manager in your hosting control panel. All
							deleted files are stored in a special quarantine folder. The location of the folder is shown
							on the Tools / Diagnostic admin page. The original name and location of a deleted file are
							saved in a .restore file. It’s a text file. Open it in a browser or a file viewer, find the
							filename you need to restore in a list of deleted files and copy the file back to its
							location by using the original name and location of the file.
						</p>

					</div>

				</td>
			</tr>
		</table>
	</div>
	<?php

}

function crb_render_anti_help_panel() {

	?>
	<div id="crb-help">
		<table id="admin-help">
			<tr>
				<td>
					<?php

					crb_render_common_help_panel();

					?>

				</td>
				<td>
					<h3>Setting up anti-spam protection</h3>

					<p>
						The Cerber anti-spam and bot detection engine is capable to protect virtually any form on a website. It’s a great alternative to reCAPTCHA.
						Tested with Caldera Forms, Gravity Forms, Contact Form 7, Ninja Forms, Formidable Forms, Fast Secure Contact Form, Contact Form by WPForms and WooCommerce forms.
					</p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/how-to-stop-spam-user-registrations-wordpress/">How to stop spam user registrations on your
							WordPress</a></p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/antispam-for-wordpress-contact-forms/">How to stop spam form submissions on your WordPress</a></p>

					<h3>Configuring exceptions for the anti-spam engine</h3>

					<p>
						Usually, you need to specify an exception if you use a plugin or some technology that communicates with your website by submitting forms or sending POST requests programmatically. In this case, Cerber can block these
						legitimate requests because it recognizes them as generated by bots. This may lead to multiple false positives which you can see on the Activity tab. These entries are marked as <b>Spam form submission denied</b>.
					</p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a href="https://wpcerber.com/antispam-exception-for-specific-http-request/" target="_blank">Configuring exceptions for the anti-spam engine</a></p>

					<h3>How to set up reCAPTCHA</h3>

					<p>

						Before you can start using reCAPTCHA on the website, you have to obtain a Site key and a Secret key on the Google website. To get the keys you have to have Google account.

						Register your website and get both keys here: <a href="" target="_blank" rel="noopener noreferrer">https://www.google.com/recaptcha/admin</a>

						Note: If you are going to use an invisible version, you must get and use Site key and a Secret key for the invisible version only.

					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/how-to-setup-recaptcha/">How to set up reCAPTCHA in details</a></p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/why-recaptcha-does-not-protect-wordpress/">Why does reCAPTCHA not protect WordPress against bots and
							brute-force attacks?</a></p>
					</p>

				</td>
			</tr>
		</table>
	</div>
	<?php

}

function crb_render_general_help_panel() {

	?>
	<div id="crb-help">
		<table id="admin-help">
			<tr>
				<td>

					<?php

					crb_render_common_help_panel();

					?>


					<h3>Troubleshooting</h3>

					<p><a href="https://wpcerber.com/antispam-exception-for-specific-http-request/" target="_blank">Configuring exceptions for the anti-spam engine</a></p>

					<p><a href="https://wpcerber.com/wordpress-probing-for-vulnerable-php-code/" target="_blank">I’m getting "Probing for vulnerable code"</a></p>

					<p><a href="https://wpcerber.com/firewall-http-requests-are-being-blocked/" target="_blank">Some legitimate HTTP requests are being blocked</a></p>

					<p><a href="https://wpcerber.com/php-warning-cannot-modify-header-information/" target="_blank">PHP Warning: Cannot modify header information – headers already sent in</a></p>

					<h3>Traffic Inspector</h3>

					<p>Traffic Inspector is a set of specialized request inspection algorithms that acts as an additional protection layer (firewall) for your WordPress</p>

					<p>
						<span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank"
						                                                                       href="https://wpcerber.com/traffic-inspector-in-a-nutshell/">Traffic
							Inspector in a nutshell</a>
					</p>

					<h3>What's new in this version of the plugin?</h3>

					<p><a href="https://wpcerber.com/?plugin_version=<?php echo CERBER_VER; ?>" target="_blank">The release note</a></p>

					<h3>Changelog</h3>

					<p><a href="<?php echo cerber_admin_link( 'changelog' ); ?>">View the WP Cerber changelog</a></p>

					<h3>Bug Bounty Program</h3>

					<p>We are deeply committed to maintaining a secure and trustworthy approach to website protection. That is why our priority is to develop secure software solutions and that is why the WP Cerber bug bounty program came
						into existence.</p>

					<p><a href="https://wpcerber.com/bug-bounty-program/" target="_blank">Know more</a></p>

				</td>
				<td>
					<h3>Online Documentation</h3>

					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/toc/">Plugin documentation on wpcerber.com</a></p>
					<p><span class="dashicons dashicons-before dashicons-shield-alt"></span> <a target="_blank" href="https://wpcerber.com/how-to-protect-wordpress-checklist/">How to protect WordPress effectively: a must-do list</a></p>

					<h3>What is IP address of your computer?</h3>

					<p>To find out your current IP address go to this page: <a target="_blank" href="https://wpcerber.com/what-is-my-ip/">What is my IP</a>. If you see a different IP address on the Activity tab for your login or logout
						events, try to enable <b><?php _e( 'My site is behind a reverse proxy', 'wp-cerber' ); ?></b>.</p>
					<p>
						<span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/wordpress-ip-address-detection/">Solving problem with incorrect IP address detection</a>
					</p>


					<h3>Setting up anti-spam protection</h3>

					<p>
						The WP Cerber anti-spam and bot detection engine can effectively protect virtually any form on your website.
						It’s a great alternative to reCAPTCHA.
					</p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/how-to-stop-spam-user-registrations-wordpress/">How to stop spam user registrations on your
							WordPress</a></p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/antispam-for-wordpress-contact-forms/">Spam protection for contact forms</a></p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a href="https://wpcerber.com/antispam-exception-for-specific-http-request/" target="_blank">Configuring exceptions for the anti-spam engine</a></p>


					<h3>Mobile and browser notifications with Pushbullet</h3>

					<p>
						WP Cerber enables you to effortlessly get desktop and mobile notifications on critical events on your website. In a desktop browser, you will receive popup messages even if you are logged out of your WordPress. To
						begin receiving notifications, you need to install the free Pushbullet mobile application on your mobile device or a free browser extension.
					</p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span>
						<a target="_blank" href="https://wpcerber.com/wordpress-mobile-and-browser-notifications-pushbullet/">A three steps instruction how to set up push notifications</a>
					</p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span>
						<a target="_blank" href="https://wpcerber.com/wordpress-notifications-made-easy/">How to get alerts for specific activity on your website</a>
					</p>

					<h3>WordPress security explained</h3>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/why-recaptcha-does-not-protect-wordpress/">Why reCAPTCHA does not protect WordPress against bots and
							brute-force attacks?</a></p>
					<p><span class="dashicons dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/why-we-need-to-use-custom-login-url/">Why you need to use Custom login URL for your WordPress</a></p>
					<p><span class="dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/why-its-important-to-restrict-access-to-rest-api/">Why it is important to restrict access to WordPress REST
							API</a></p>
					<p><span class="dashicons-before dashicons-book-alt"></span> <a target="_blank" href="https://wpcerber.com/mitigating-brute-force-dos-and-ddos-attacks/">Brute-force, DoS, and DDoS attacks - what is the difference?</a>
					</p>
				</td>
			</tr>
		</table>

		<h3>What is Cerber Lab?</h3>

		<p>
			Cerber Laboratory is a forensic team at Cerber Tech Inc. The team studies and analyzes
			patterns of hacker and botnet attacks, malware, vulnerabilities in major plugins and how they are
			exploitable on WordPress powered websites.
		</p>
		<p><span class="dashicons-before dashicons-info" style="vertical-align: middle;"></span>
			<a href="https://wpcerber.com/cerber-laboratory/">Know more</a>
		</p>

		<h3>Do you have an idea for a cool new feature that you would love to see in WP Cerber?</h3>

		<p>
			Feel free to submit your ideas here: <a href="https://wpcerber.com/new-feature-request/">New Feature
				Request</a>.
		</p>

		<h3>Are you ready to translate WP Cerber into your language?</h3>

		<p>We would appreciate that! Please, <a href="https://wpcerber.com/contact/">notify us</a></p>

	</div>
	<?php
}

function crb_render_common_help_panel() {

	if ( lab_lab() ) {
		$support = '<p style="margin: 2em 0 5em 0;">Submit a support ticket on our Help Desk: <a href="https://my.wpcerber.com/">https://my.wpcerber.com</a></p>';
	}
	else {
		$support = '<p>Support for the free version is provided on the <a target="_blank" href="https://talk.wpcerber.com">the community forum only</a>, though, please note, that it is free support hence it is
                        not always possible to answer all questions on a timely manner, although we do try.</p>';
	}

	?>

	<img style="width: 120px; float: left; margin-right: 30px; margin-bottom: 30px;" src="<?php CRB_Globals::assets_url( 'wrench.png', true ); ?>"/>

	<h3 style="font-size: 150%;">How to configure the plugin</h3>

	<p style="font-size: 120%;">To get the most out of WP Cerber Security, you need to configure the plugin properly</p>

	<p style="font-size: 120%;"><a target="_blank" href="https://wpcerber.com/getting-started/">Getting Started Guide</a></p>

	<p style="clear: both;"></p>

	<h3>Do you have a question or need help?</h3>

	<?php echo $support; ?>

	<p><span class="dashicons dashicons-before dashicons-format-chat"></span> <a target="_blank" href="https://talk.wpcerber.com">Get answers on the community support forum</a></p>

	<form style="margin-top: 2em;" action="https://wpcerber.com" target="_blank">
		<h3>Search plugin documentation on wpcerber.com</h3>
		<input type="text" style="width: 80%;" name="s" placeholder="Enter a word or phrase to search"><input type="submit" value="Search" class="button button-primary">
	</form>

	<?php
}

/**
 * @return array A set of links to the plugin documentation web pages
 */
function crb_get_doc_links() {
	static $links = array(
		'cerber-integrity' => array(
			array( 'https://wpcerber.com/wordpress-security-scanner/', 'How to use the integrity checker and malware scanner' ),
			array( 'https://wpcerber.com/wordpress-security-scanner-scan-malware-detect/', 'What the scanner scans and detects' ),
			array( 'https://wpcerber.com/malware-scanner-settings/', 'Configuring the scanner settings' ),
			array( 'https://wpcerber.com/troubleshooting-malware-scanner/', 'Troubleshooting malware scanner issues' ),
			array( 'https://wpcerber.com/automatic-malware-removal-wordpress/', 'Automatic cleanup of malware and suspicious files' ),
			array( 'https://wpcerber.com/automated-recurring-malware-scans/', 'Automated recurring scans and email reporting' )
		),
		'*'                => array(
			array( 'https://wpcerber.com/automatic-updates-for-wp-cerber/', 'How to enable automatic updates for WP Cerber' ),
			array( 'https://wpcerber.com/troubleshooting-login-issues/', 'Troubleshooting login issues' ),
			array( 'https://wpcerber.com/wordpress-application-passwords-how-to/', 'Managing WordPress application passwords a hassle-free way' ),
			array( 'https://wpcerber.com/how-to-protect-wordpress-checklist/', 'How to protect WordPress effectively: a must-do list' ),
			array( 'https://wpcerber.com/manage-multiple-websites/', 'Managing multiple WP Cerber instances from one dashboard' ),
			array( 'https://wpcerber.com/wordpress/gdpr/', 'Stay in compliance with GDPR' ),
			array( 'https://wpcerber.com/two-factor-authentication-for-wordpress/', 'How to protect user accounts with Two-Factor Authentication' ),
			array( 'https://wpcerber.com/limiting-concurrent-user-sessions-in-wordpress/', 'How to stop your users from sharing their WordPress accounts' ),
			array( 'https://wpcerber.com/wordpress-mobile-and-browser-notifications-pushbullet/', 'Be notified with mobile and browser notifications' ),
			array( 'https://wpcerber.com/wordpress-notifications-made-easy/', 'WordPress notifications made easy' ),
			array( 'https://wpcerber.com/restrict-access-to-wordpress-rest-api/', 'How to restrict access to REST API' ),
			array( 'https://wpcerber.com/automatic-malware-removal-wordpress/', 'Automatic cleanup of malware and suspicious files' ),
			array( 'https://wpcerber.com/automated-recurring-malware-scans/', 'Automated recurring scans and email reporting' ),
		),
	);

	static $get_started = array( 'https://wpcerber.com/getting-started/', '<b>Getting Started Guide</b>' );

	$ret = crb_array_get( $links, crb_admin_get_page() );
	$common = crb_array_get( $links, '*' );

	if ( ! $ret ) {
		array_unshift( $common, $get_started );

		return $common;
	}

	$ret = array_merge( $ret, array_slice( $common, 0, 5 ) );
	$ret[] = $get_started;

	return $ret;
}