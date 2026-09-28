=== MainWP Pro Reports Extension ===
Plugin Name: MainWP Pro Reports Extension
Plugin URI: https://mainwp.com
Description: MainWP Pro Reports Extension allows you to generate pro reports for your child sites. Requires MainWP Dashboard.
Version: 5.0.5
Author: MainWP
Author URI: https://mainwp.com

== Installation ==

1. Please install plugin "MainWP Dashboard" and active it before install MainWP Pro Reports Extension plugin (get the MainWP Dashboard plugin from url:https://mainwp.com/)
2. Upload the `mainwp-pro-reports-extension` folder to the `/wp-content/plugins/` directory
3. Activate the MainWP Pro Reports Extension plugin through the 'Plugins' menu in WordPress

== Screenshots ==
1. Enable or Disable extension on the "Extensions" page in the dashboard

== Changelog ==

= Version  5.0.5 - 7-22-2024 =
* Fixed: Issue with generating PDF reports on specific setups.
* Fixed: Issue with displaying images in PDF reports on specific setups.
* Added: Ability to add multiple BCC email addresses, separated by commas.

= Version  5.0.4 - 6-28-2024 =
* Fixed: An issue with generating analytics charts for longer date ranges.

= Version  5.0.3 - 6-25-2024 =
* Fixed: An issue with rendering PNG images in PDF on specific hosts
* Updated: Data query process for better perforamce

= Version 5.0.2 - 5-29-2024 =
* Fixed: An issue with Analytics charts where the first 3 items on the X axis always shows 0
* Updated: Compatibility with the latest DataTables library version

= 5.0.1 - 4-15-2024 =
* Fixed: An issue with displaying pending updates data, ensuring accuracy in update reports.
* Fixed: Problems with rendering analytics charts, improving visual data representation.
* Fixed: An error with rendering incorrect email messages in the basic email template, enhancing communication clarity.
* Added: Support for Time Tracker data to enhance reporting capabilities.
* Added: Time Tracker sections to default templates for comprehensive activity tracking.

= 4.2 - 11-21-2023 =
* Fixed: Standardized the use of capital letters in the `[wordpress.updated]` tokens for consistency.
* Fixed: Compatibility issues impacting PDF generation on certain hosting environments.
* Fixed: Display errors in email content using the basic template.
* Fixed: Issues related to revealing previously hidden information messages.
* Fixed: Overflow issues in the Actions menu within the Child Reports Dashboard table.
* Added: SSL Monitor tokens in default templates for enhanced reporting capabilities.
* Added: SSL Monitor configuration option to the Report Data settings.
* Added: SSL Monitor settings into the Custom Titles configuration for enhanced customization.
* Added: A condition to verify custom logo usage and skip its display if not used.
* Added: The option to suppress informational messages on the Create Report page.
* Added: Chart titles to the Google Analytics and Fathom charts for better data understanding.
* Added: An option to conceal informational messages in the Select Sites interface.
* Added: Enhanced the Select Sites sidebar element with an accordion feature for improved usability.
* Added: The "Insert tokens" action link and modal for the "Report PDF Filename" input field, enhancing customizability.
* Tweaked: Refined the `[report.daterange]` token to exclude time information for clearer date representation.
* Tweaked: Updated the styling of the "Additional email attachment" field for a more streamlined appearance.
* Tweaked: Modified Google Analytics chart aesthetics, including line color, fill color, and the removal of the background color for clarity.
* Tweaked: Adjusted the number of dates and the date format on the X-axis of the Google Analytics chart for enhanced readability.
* Tweaked: Reduced the number of horizontal lines on the Google Analytics Chart for a cleaner visual presentation.
* Tweaked: Applied similar aesthetic enhancements to the Fathom chart, including line color, fill color adjustments, and background color removal.
* Tweaked: Revised the number of dates and date format on the X-axis of the Fathom chart for improved clarity.
* Tweaked: Reduced horizontal lines on the Fathom Chart for a simplified visual appeal.
* Tweaked: Eliminated excessive padding on the Create Report tab following the Sidebar element for a more organized layout.
* Tweaked: Repositioned the Send Now button to the left side in the Preview Report modal for ergonomic accessibility.
* Tweaked: Adjusted the height of the Report Preview modal for an optimized view.
* Tweaked: Reconfigured the padding of the sites filter in the Actions bar for a more efficient layout.
* Tweaked: Altered the status element location during the report deletion process for better user experience.
* Tweaked: Refined the wording of error and success messages for enhanced clarity.

= 4.1.3 - 9-18-2023 =
* Fixed: An issue with creating the Extension database tables

= 4.1.2 - 9-13-2023 =
* Fixed: An issue with showing section when the section data is empty
* Added: Support for the MainWP Fathom Extension

= 4.1.1 - 5-12-2023 =
* Fixed: REST API authentication process
* Added: Options to translate Titles, Subtitles and Table Headers

= 4.1 - 5-9-2023 =
* Fixed: Issues with getting Client Email value from Client settings
* Added: Support for the MainWP REST API
* Added: Support for using all tokens in the email body
* Added: New default report template called Agency
* Added: New default report template called Agency Dark
* Added: New email template called Agency
* Added: Reports DB Size column to the Child Reports overview page
* Added: PDF file name field to report settings
* Added: Posts info to reports
* Added: Pages info to reports
* Added: Users info to reports
* Added: Comments info to reports
* Added: Domain info to reports
* Added: Wordfence info to reports
* Added: iThemes Security info to reports
* Added: Jetpack Protect info to reports
* Added: Matomo analytics data to reports
* Added: First-page background color option
* Added: Table background color option
* Added: Table header background and text color options
* Added: Table border color option
* Added: Tokens to show pending Plugins and Themes updates
* Added: Tokens to show abandoned Plugins and Themes
* Updated: Select Sites element to support selection by Client
* Updated: Moved report template info to a dedicated Modal element
* Updated: Merged Branding and Color selection options to the same tab in report settings
* Updated: Old report templates to support new changes
* Updated: Renamed Groups to Tags

= 4.0.12 - 12-14-2022 =
* Updated: MainWP 4.3.1 compatibility

= 4.0.11 - 8-88-2022 =
* Added: Support for the Atarim extension data
* Added: French translation files

= 4.0.10 - 6-21-2022 =
* Added: WP Nonce verification to the Go to WP Admin request for additional security

= 4.0.9 - 5-6-2022 =
* Fixed: An issue with displaying Domain Monitor data in reports
* Fixed: An issue with displaying Lighthouse data in reports


= 4.0.8 - 2-25-2022 =
* Updated: php-css-parser library

= 4.0.7 - 12-20-2021 =
* Updated: DOMPDF library
* Updated: PHP 8 compatibility
* Updated: Support for the Lighthouse extension tokens

= 4.0.6 - 6-11-2021 =
* Updated: General performance improvements

= 4.0.5 - 4-28-2021 =
* Fixed: An issue with sending reports for specific setups
* Added: 'mainwp_pro_reports_get_tokens_value' hook for extra logging
* Added: 'mainwp-reports-ga-chart-format-date' hook to allow date format change on GA chart
* Added: Support for Virusdie tokens
* Updated: General performance improvements

= 4.0.4 - 1-15-2021 =
* Updated: Support for default WP datepicker

= 4.0.3 - 12-11-2020 =
* Fixed: An issue with sending scheduled reports
* Fixed: An issue with selecting a date range
* Fixed: An issue with a few incorrect tokens in default report templates
* Added: mainwp_pro_reports_filter_report_content hook

= 4.0.2.1 - 9-10-2020 =
* Updated: MainWP Dashboard 4.1 compatiblity

= 4.0.2 - 8-28-2020 =
* Fixed: An issue with displaying WooCommerce Top Seller product in reports

= 4.0.1 - 7-28-2020 =
* Fixed: an error with sending scheduled reports
* Fixed: compatibility with the Sucuri scan hook
* Added: support to fetch Site ID in report templates
* Added: mainwp_pro_reports_generate_report_content hook to support tokens in MainWP Dashboard notifications
* Added: mainwp_pro_reports_get_site_tokens hook to support tokens in MainWP Dashboard notifications
* Added: mainwp_pro_reports_generate_content hook to support tokens in MainWP Dashboard notifications
* Added: mainwp_pro_reports_fetch_remote_post_data hook for fetching post data
* Added: mainwp_pro_reports_send_local_time hook to allow sending reports in loca
* Updated: send email process to send reports at localtime.
* Updated: DOMPDF library version

= 4.0 - 1-22-2020 =
* Initial release

= 4.0-beta1 - 12-9-2019 =
* Beta1 release
