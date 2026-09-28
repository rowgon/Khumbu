<?php

namespace FluentSupport\App\Services\Integrations;

use FluentSupport\App\Services\Helper;

class IntegrationInit
{
    const SUPPORT_ENDPOINT = 'support-tickets';

    public function init()
    {
        $this->registerWooCommercePortal();
        add_action('template_redirect', [$this, 'maybeRedirectPortalUrl']);

        if(defined('FLUENTCRM') && class_exists('\FluentSupport\App\Services\Integrations\FluentCrm\FluentCRMWidgets')) {
            (new \FluentSupport\App\Services\Integrations\FluentCrm\FluentCRMWidgets())->boot();
        }

        if (defined('FLUENTFORM') && class_exists('\FluentSupport\App\Services\Integrations\FluentForm\FeedIntegration')) {
            new \FluentSupport\App\Services\Integrations\FluentForm\FeedIntegration();
        }

        if (defined('FLUENTCART_VERSION') && class_exists('\FluentSupport\App\Services\Integrations\FluentCart\FluentCart')) {
            (new \FluentSupport\App\Services\Integrations\FluentCart\FluentCart())->boot();
        }

        if (defined('FLUENT_BOOKING_VERSION') && class_exists('\FluentSupport\App\Services\Integrations\FluentBooking\FluentBookingService')) {
            $fluentBookingService = new \FluentSupport\App\Services\Integrations\FluentBooking\FluentBookingService();
            if ($fluentBookingService->isActive()) {
                $fluentBookingService->init();
            }
        }

        if (defined('FLUENT_COMMUNITY_PLUGIN_VERSION') && class_exists('\FluentSupport\App\Services\Integrations\FluentCommunity\FluentCommunityIntegration')) {
            (new \FluentSupport\App\Services\Integrations\FluentCommunity\FluentCommunityIntegration())->boot();
        }
    }

    private function registerWooCommercePortal()
    {
        if (!defined('WC_PLUGIN_FILE')) {
            return;
        }

        add_rewrite_endpoint(self::SUPPORT_ENDPOINT, EP_ROOT | EP_PAGES);

        add_filter('woocommerce_get_query_vars', function ($queryVars) {
            $queryVars[self::SUPPORT_ENDPOINT] = self::SUPPORT_ENDPOINT;
            return $queryVars;
        });

        add_filter('woocommerce_account_menu_items', function ($items) {
            if (Helper::getBusinessSettings('ticket_link_portal') !== 'woocommerce') {
                return $items;
            }

            $supportPosition = apply_filters('fluent_support/woo_menu_link_position', 3);
            if (count($items) <= $supportPosition) {
                $supportPosition = count($items) - 1;
            }

            $supportLabel = apply_filters('fluent_support/woo_menu_label', __('Support', 'fluent-support'));

            return array_slice($items, 0, $supportPosition, true)
                + [self::SUPPORT_ENDPOINT => $supportLabel]
                + array_slice($items, $supportPosition, null, true);
        });

        add_action('woocommerce_account_' . self::SUPPORT_ENDPOINT . '_endpoint', function () {
            if (Helper::getBusinessSettings('ticket_link_portal') === 'woocommerce') {
                echo do_shortcode('[fluent_support_portal]');
            }
        });
    }

    public function maybeRedirectPortalUrl()
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (!$requestUri) {
            return;
        }

        $requestPath = trim((string) wp_parse_url($requestUri, PHP_URL_PATH), '/');
        $pathSegments = explode('/', $requestPath);
        $hasKnownEndpoint = in_array(self::SUPPORT_ENDPOINT, $pathSegments, true);

        if (!$hasKnownEndpoint && strpos($requestUri, 'fs_view=ticket') === false) {
            return;
        }

        $baseUrl = Helper::getPortalBaseUrl();
        $basePath = trim((string) wp_parse_url($baseUrl, PHP_URL_PATH), '/');

        if ($requestPath === $basePath) {
            return;
        }

        $redirectUrl = $baseUrl;
        $query = (string) wp_parse_url($requestUri, PHP_URL_QUERY);

        if ($query) {
            $redirectUrl .= '?' . $query;
        }

        wp_safe_redirect($redirectUrl, 302);
        exit;
    }

}
