<?php

namespace FluentSupport\App\Services\Integrations\FluentCommunity;

use FluentCommunity\Modules\Theming\TemplateLoader;
use FluentSupport\App\Hooks\Handlers\CustomerPortalHandler;

/**
 * PortalRenderer handles asset enqueuing and HTML output for the
 * Fluent Support portal embedded inside FluentCommunity.
 *
 *  - Enqueue FS portal assets — wp_head() is called by the frame template
 *    so standard WP enqueueing works automatically, no force-printing needed.
 *  - Replace the default theme_content with the FS portal mount point.
 *  - Load the FC frame template directly and exit.
 */
class PortalRenderer
{
    public function render()
    {
        (new CustomerPortalHandler())->enqueueScripts();

        add_action('wp_head', [$this, 'printPortalStyles'], 999);
        add_action('wp_footer', [$this, 'markSupportLinkActive']);

        remove_all_actions('fluent_community/theme_content');
        add_action('fluent_community/theme_content', [$this, 'renderContent']);

        (new TemplateLoader())->loadScriptsAndStyles();

        status_header(200);
        require_once FLUENT_COMMUNITY_PLUGIN_DIR . 'Modules/Theming/templates/fluent-community-frame-full.php';
        exit();
    }

    public function printPortalStyles()
    {
        ?>
        <style>
        #fluent_support_client_app {
            background: var(--fs-bg-white, #ffffff);
            min-height: calc(100dvh - var(--fcom-header-height, 55px) - 5px);
            box-sizing: border-box;
            width: calc(100% - 4rem);
            max-width: 1080px;
            border-radius: 14px;
            margin: 2rem auto;
        }
        #fluent_support_client_app .fs_create_ticket_container,
        #fluent_support_client_app .fs_ticket {
            padding: 2rem;
        }

        @media (max-width: 768px) {
            #fluent_support_client_app {
                width: calc(100% - 2rem);
                margin: 1rem auto;
            }

            #fluent_support_client_app .fs_create_ticket_container,
            #fluent_support_client_app .fs_ticket {
                padding: 1rem;
            }
        }
        </style>
        <?php
    }

    public function markSupportLinkActive()
    {
        $supportUrl = \FluentCommunity\App\Services\Helper::baseUrl('support/');
        ?>
        <script>
        (function () {
            var url = <?php echo wp_json_encode(esc_url($supportUrl)); ?>;
            document.querySelectorAll('a[href="' + url + '"]').forEach(function (el) {
                el.classList.add('router-link-active', 'router-link-exact-active');
            });
        })();
        </script>
        <?php
    }

    public function renderContent()
    {
        ?>
        <div id="fluent_support_client_app">
            <h3 class="fs_loading_text" style="padding:2rem;text-align:center;">
                <?php esc_html_e('Loading Support Portal. Please wait...', 'fluent-support'); ?>
            </h3>
        </div>
        <?php
    }
}
