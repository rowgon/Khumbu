<?php

namespace FluentSupport\App\Services\Integrations\FluentCommunity;

/**
 * FluentCommunityIntegration bootstraps the Fluent Support portal
 * inside the FluentCommunity community portal.
 *
 * Activated only when FLUENT_COMMUNITY_PLUGIN_VERSION is defined and
 * the portal destination is set to fluent_community.
 */
class FluentCommunityIntegration
{
    public function boot()
    {
        if (!$this->shouldUseCommunityPortal()) {
            return;
        }

        // Register 'support' as a known FC route so FC's Vue router treats
        // it as a valid path and does not fire the NotFound → all_feeds
        // redirect chain on client-side navigation.
        add_filter('fluent_community/app_route_paths', [$this, 'registerSupportRoute']);

        // Handle the support route entirely in the SSR hook — mirrors the
        add_action('fluent_community/rendering_path_ssr_support', [$this, 'renderSupportPortal']);

        // FC's Vue Router intercepts all portal-domain <a> clicks. Since /support
        // is not a registered FC Vue route, the router falls through to NotFound
        // which redirects to all_feeds. This beforeEach guard converts any SPA
        // navigation to /support into a full page load, which then hits the SSR hook.
        // Must run on ALL community portal pages, not just the support route.
        add_action('fluent_community/portal_footer', [$this, 'outputRouterGuard'], 999);
    }

    public function registerSupportRoute($paths)
    {
        $paths[] = 'support';
        return $paths;
    }

    public function renderSupportPortal($parts = [])
    {
        if (!is_user_logged_in()) {
            wp_safe_redirect($this->getCommunityAuthUrl());
            exit;
        }

        (new PortalRenderer())->render();
    }

    public function outputRouterGuard()
    {
        $supportUrl = \FluentCommunity\App\Services\Helper::baseUrl('support/');
        ?>
        <script>
        (function () {
            var supportUrl = <?php echo wp_json_encode(esc_url($supportUrl)); ?>;

            function addRouterGuard() {
                var router = window.fluentFrameworkAppRouter;
                if (!router || typeof router.beforeEach !== 'function') {
                    return;
                }
                router.beforeEach(function (to, from, next) {
                    if (to.path === '/support' || to.path.indexOf('/support/') === 0) {
                        window.location.href = supportUrl;
                        return false;
                    }
                    next();
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', addRouterGuard);
            } else {
                addRouterGuard();
            }
        })();
        </script>
        <?php
    }

    private function getCommunityAuthUrl()
    {
        $supportUrl = \FluentCommunity\App\Services\Helper::baseUrl('support/');
        $authUrl    = \FluentCommunity\App\Services\Helper::getAuthUrl();

        if (!$authUrl) {
            $authUrl = add_query_arg('fcom_action', 'auth', \FluentCommunity\App\Services\Helper::baseUrl(''));
        }

        return add_query_arg('redirect_to', $supportUrl, $authUrl);
    }

    private function shouldUseCommunityPortal()
    {
        return \FluentSupport\App\Services\Helper::getBusinessSettings('ticket_link_portal') === 'fluent_community';
    }
}
