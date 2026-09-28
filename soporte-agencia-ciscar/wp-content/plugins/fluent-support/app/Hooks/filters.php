<?php
defined('ABSPATH') or die;

/**
 * @var $app \FluentSupport\Framework\Foundation\Application;
 */

add_filter('fluent_support/parse_smartcode_data', function ($string, $data) {
    return (new \FluentSupport\App\Services\Parser\Parser())->parse($string, $data);
}, 10, 2);

add_filter('fluent_support/dashboard_notice', function ($messages) {
    if(defined('FLUENTSUPPORTPRO_PLUGIN_VERSION') && version_compare(FLUENT_SUPPORT_PRO_MIN_VERSION, FLUENTSUPPORTPRO_PLUGIN_VERSION, '>')) {
        $updateUrl = admin_url('plugins.php?s=fluent-support-pro&plugin_status=all&fluentsupport_pro_check_update=' . time());
        $html = '<div class="fs_alert_notification fs_alert_warning" style="border-radius: 8px; margin-bottom: 24px; max-width: 1360px; margin-left: auto; margin-right: auto;">
            <div style="display: flex; gap: 8px; align-items: center; padding: 8px 8px 8px 16px;">
                <span style="font-size: 15px; line-height: 18px; flex-shrink: 0;">⚠️</span>
                <p class="fs_alert_text">Fluent Support Pro Plugin needs to be updated for compatibility.</p>
            </div>
        </div>';
        $messages .= $html;
    }
    return $messages;
}, 100);

add_filter('fluent_support/mail_to_customer_header', function ($headers, $data){
    return (new \FluentSupport\App\Hooks\Handlers\EmailNotificationHandler())->getMailerHeaderWithCc($headers, $data);
}, 10, 2);
