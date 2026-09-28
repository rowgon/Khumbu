<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\App;
use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\AgentGroup;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\TagPivot;
use FluentSupport\App\Models\TicketTag;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Notifications\NotificationSettings;
use FluentSupport\App\Services\TranslationStrings;
use FluentSupport\App\Services\Integrations\FluentBooking\FluentBookingService;
use FluentSupport\App\Vite;

class Menu
{
    public function add()
    {
        $capability = PermissionManager::getMenuPermission();

        if (!$capability) {
            return;
        }

        $menuPosition = 25;
        if (defined('FLUENTCRM')) {
            $menuPosition = 4;
        }

        /*
         * Filter Fluent Support menu position in WordPress dashboard
         * @param integer $menuPosition
         */
        $menuPosition = apply_filters('fluent_support/admin_menu_position', $menuPosition);

        add_menu_page(
            __('Fluent Support', 'fluent-support'),
            __('Fluent Support', 'fluent-support'),
            $capability,
            'fluent-support',
            array($this, 'renderApp'),
            $this->getMenuIcon(),
            $menuPosition
        );

        add_submenu_page(
            'fluent-support',
            __('Dashboard', 'fluent-support'),
            __('Dashboard', 'fluent-support'),
            $capability,
            'fluent-support',
            array($this, 'renderApp')
        );

        add_submenu_page(
            'fluent-support',
            __('Tickets', 'fluent-support'),
            __('Tickets', 'fluent-support'),
            $capability,
            'fluent-support#/tickets',
            array($this, 'renderApp')
        );

        if (PermissionManager::currentUserCan('fst_view_all_reports')) {
            add_submenu_page(
                'fluent-support',
                __('Reports', 'fluent-support'),
                __('Reports', 'fluent-support'),
                $capability,
                'fluent-support#/reports',
                array($this, 'renderApp')
            );
        }

        if (PermissionManager::currentUserCan('fst_sensitive_data')) {
            add_submenu_page(
                'fluent-support',
                __('Customers', 'fluent-support'),
                __('Customers', 'fluent-support'),
                $capability,
                'fluent-support#/customers',
                array($this, 'renderApp')
            );
        }

        if (PermissionManager::currentUserCan('fst_view_activity_logs')) {
            add_submenu_page(
                'fluent-support',
                __('Activities', 'fluent-support'),
                __('Activities', 'fluent-support'),
                $capability,
                'fluent-support#/activity',
                array($this, 'renderApp')
            );
        }

        if (PermissionManager::currentUserCan('fst_manage_settings')) {
            add_submenu_page(
                'fluent-support',
                __('Business Inboxes', 'fluent-support'),
                __('Business Inboxes', 'fluent-support'),
                $capability,
                'fluent-support#/mailboxes',
                array($this, 'renderApp')
            );
        }

        if (PermissionManager::currentUserCan('fst_manage_workflows')) {
            add_submenu_page(
                'fluent-support',
                __('Workflows', 'fluent-support'),
                __('Workflows', 'fluent-support'),
                $capability,
                'fluent-support#/workflows',
                array($this, 'renderApp')
            );
        }

        if (PermissionManager::currentUserCan('fst_manage_saved_replies')) {
            add_submenu_page(
                'fluent-support',
                __('Saved Replies', 'fluent-support'),
                __('Saved Replies', 'fluent-support'),
                $capability,
                'fluent-support#/saved-replies',
                array($this, 'renderApp')
            );
        }

        if (PermissionManager::currentUserCan('fst_manage_settings')) {
            add_submenu_page(
                'fluent-support',
                __('Settings', 'fluent-support'),
                __('Settings', 'fluent-support'),
                $capability,
                'fluent-support#/settings',
                array($this, 'renderApp')
            );
        }
    }

    public function getMenuItems()
    {
        $baseUrl = apply_filters('fluent_support/base_url', admin_url('admin.php?page=fluent-support#/'));

        $menuItems = [
            'dashboard' => [
                'key'       => 'dashboard',
                'label'     => __('Dashboard', 'fluent-support'),
                'permalink' => $baseUrl,
            ],
            'tickets'   => [
                'key'       => 'tickets',
                'label'     => __('Tickets', 'fluent-support'),
                'permalink' => $baseUrl . 'tickets',
            ],
            'reports'   => [
                'key'       => 'reports',
                'label'     => __('Reports', 'fluent-support'),
                'permalink' => $baseUrl . 'reports',
            ],
        ];

        $canManageSettings = PermissionManager::currentUserCan('fst_manage_settings');

        if ($canManageSettings) {
            $menuItems['mailboxes'] = [
                'key'       => 'mailboxes',
                'label'     => __('Business Inboxes', 'fluent-support'),
                'permalink' => $baseUrl . 'mailboxes',
            ];
        }

        if (PermissionManager::currentUserCan('fst_view_activity_logs')) {
            $menuItems['activity'] = [
                'key'       => 'activity',
                'label'     => __('Activities', 'fluent-support'),
                'permalink' => $baseUrl . 'activity',
            ];
        }

        if (PermissionManager::currentUserCan('fst_sensitive_data')) {
            $menuItems['customers'] = [
                'key'       => 'customers',
                'label'     => __('Customers', 'fluent-support'),
                'permalink' => $baseUrl . 'customers',
            ];
        }

        $moreChildren = [];

        if (PermissionManager::currentUserCan('fst_manage_saved_replies')) {
            $moreChildren['saved_replies'] = [
                'key'       => 'saved_replies',
                'label'     => __('Saved Replies', 'fluent-support'),
                'permalink' => $baseUrl . 'saved-replies',
            ];
        }

        if (PermissionManager::currentUserCan('fst_manage_workflows')) {
            $moreChildren['workflows'] = [
                'key'       => 'workflows',
                'label'     => __('Workflows', 'fluent-support'),
                'permalink' => $baseUrl . 'workflows',
            ];
        }

        if (!empty($moreChildren)) {
            $menuItems['more'] = [
                'key'      => 'more',
                'label'    => __('More', 'fluent-support'),
                'permalink' => '#',
                'children' => $moreChildren,
            ];
        }

        if ($canManageSettings) {
            $menuItems['settings'] = [
                'key'       => 'settings',
                'label'     => __('Global Settings', 'fluent-support'),
                'permalink' => $baseUrl . 'settings',
            ];
        }

        return apply_filters('fluent_support/menu_items', $menuItems);
    }

    public function renderApp()
    {
        $app = App::getInstance();

        $assets = $app['url.assets'];

        $allItems = $this->getMenuItems();

        $secondaryKeys = ['settings'];
        $menuItems     = [];
        $secondaryItems = [];

        foreach ($allItems as $key => $item) {
            if (in_array($key, $secondaryKeys, true)) {
                $secondaryItems[] = $item;
            } else {
                $menuItems[] = $item;
            }
        }

        $menuItems = apply_filters('fluent_support/primary_menu_items', $menuItems);
        $secondaryItems = apply_filters('fluent_support/secondary_menu_items', $secondaryItems);

        if (!defined('FLUENT_SUPPORT_PRO_DIR_FILE')) {
            $secondaryItems[] = [
                'key'       => 'upgrade_to_pro',
                'label'     => 'Upgrade to Pro',
                'permalink' => Helper::getUpgradeUrl('sidebar_cta')
            ];
        }

        $baseUrl = apply_filters('fluent_support/base_url', admin_url('admin.php?page=fluent-support#/'));

        $this->enqueueAssets();

        do_action('fluent_support/admin_app_loaded', $app);
        $app->view->render('admin.menu', [
            'base_url'       => $baseUrl,
            'logo'           => $assets . 'images/logo.svg',
            'settingsLogo'   => $assets . 'images/gear.svg',
            'upgradeLogo'     => $assets . 'images/crown.svg',
            'assets'         => $assets,
            'menuItems'      => $menuItems,
            'secondaryItems' => isset($secondaryItems) ? $secondaryItems : [],
        ]);
    }

    public function maybeEnqueueAssets()
    {
        if (isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'fluent-support') {
            add_action('admin_head', [$this, 'printDarkModeInit'], 1);
            $this->enqueueAssets();
        }
    }

    public function printDarkModeInit()
    {
        ?>
        <script>
            (function () {
                var savedTheme = localStorage.getItem('fs-theme');

                // Check if dark mode should be active
                // localStorage stores: 'dark', 'light', or 'system:dark' / 'system:light'
                var isDark = savedTheme
                    ? savedTheme.split(':').pop() === 'dark'
                    : window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (isDark) {
                    // Apply immediately to <html> to prevent white flash
                    document.documentElement.classList.add('fs-dark-mode');

                    // Watch for <body> to appear and apply class as soon as it exists
                    new MutationObserver(function (mutations, observer) {
                        if (document.body) {
                            document.body.classList.add('fs-dark-mode');
                            observer.disconnect();
                        }
                    }).observe(document.documentElement, { childList: true });
                }
            })();
        </script>
        <?php
    }

    public function enqueueAssets()
    {
        $app = App::getInstance();

        $assets = $app['url.assets'];

        // Inject Vite HMR client for dev mode
        add_action('admin_head', function () {
            Vite::injectViteClient();
        }, 1);

        wp_enqueue_script('dompurify', $assets . 'libs/purify/purify.min.js', [], '3.4.12');

        if (is_rtl()) {
            wp_enqueue_style('fluent_support_admin_app_rtl', $assets . 'admin/css/alpha-admin-rtl.css', [], FLUENT_SUPPORT_VERSION);
        } else {
            wp_enqueue_style('fluent_support_admin_app', Vite::getEnqueuePath('admin/css/alpha-admin.css'), [], FLUENT_SUPPORT_VERSION);
        }

        $agents = Agent::select(['id', 'first_name', 'last_name'])
            ->where('person_type', 'agent')
            ->get()->toArray();

        foreach ($agents as $index => $agent) {
            $agents[$index]['id'] = strval($agent['id']);
        }

        $agentGroups = AgentGroup::select(['id', 'title', 'settings'])->get();
        $groupPivots = TagPivot::where('source_type', 'agent_group')->get();
        foreach ($agentGroups as $group) {
            $group->agent_ids = $groupPivots->where('tag_id', $group->id)
                ->pluck('source_id')
                ->map(function ($id) { return strval($id); })
                ->values()
                ->toArray();
        }

        $me = Helper::getAgentByUserId(get_current_user_id());

        if (!$me && current_user_can('manage_options')) {
            // we should create the agent
            $user = wp_get_current_user();
            $me = Agent::create([
                'email'      => $user->user_email,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'user_id'    => $user->ID
            ]);
        }

        $me->permissions = PermissionManager::currentUserPermissions();

        do_action('fluent_support_loading_app', $app);

        // Editor default styles.
        add_filter('user_can_richedit', '__return_true');
        wp_tinymce_inline_scripts();
        wp_enqueue_editor();
        wp_enqueue_media();

        wp_enqueue_script(
            'fluent_support_admin_app_start',
            Vite::getEnqueuePath('admin/js/start.js'),
            array('jquery'),
            FLUENT_SUPPORT_VERSION,
            true
        );

        wp_enqueue_script(
            'fluent_support_global_admin',
            Vite::getEnqueuePath('admin/js/global_admin.js'),
            array('jquery'),
            FLUENT_SUPPORT_VERSION,
            true
        );

        $integrationDrivers = [];
        if (!defined('FLUENTSUPPORTPRO')) {
            $integrationDrivers = [
                [
                    'key'           => 'telegram_settings',
                    'title'         => __('Telegram', 'fluent-support'),
                    'description'   => __('Send Telegram notifications to Group, Channel or individual person inbox and reply from Telegram inbox', 'fluent-support'),
                    'promo_heading' => __('Get activity notification to Telegram Messenger and reply directly from Telegram inbox', 'fluent-support'),
                    'require_pro'   => true
                ],
                [
                    'key'           => 'slack_settings',
                    'title'         => __('Slack', 'fluent-support'),
                    'description'   => __('Send ticket activity notifications to slack', 'fluent-support'),
                    'promo_heading' => __('Get activity notification to Slack Channel and keep your support team super engaged', 'fluent-support'),
                    'require_pro'   => true
                ]
            ];
        }

        /*
         * Filter integration driver
         * @param array $integrationDrivers
         */
        $integrationDrivers = apply_filters('fluent_support/integration_drivers', $integrationDrivers);

        $tags = TicketTag::select(['id', 'title'])->get()->toArray();

        $tags = array_map(function ($tag) {
            $tag['id'] = strval($tag['id']);
            return $tag;
        }, $tags);

        $i18ns = TranslationStrings::getAdminStrings();
        $i18ns['allowed_files_and_size'] = Helper::getFileUploadMessage();
        $i18ns['open']                   = __('Open', 'fluent-support');
        $i18ns['all']                    = __('All', 'fluent-support');

        /*
         * Filter agent portal localize javascript data
         *
         * @since v1.0.0
         *
         * @param array $appVars
         */
        $appVars = apply_filters('fluent_support_app_vars', array(
            'slug'                       => $slug = $app->config->get('app.slug'),
            'nonce'                      => wp_create_nonce($slug),
            'rest'                       => $this->getRestInfo($app),
            'brand_logo'                 => $this->getMenuIcon(),
            'firstEntry'                 => '',
            'lastEntry'                  => '',
            'asset_url'                  => $assets,
            'support_agents'             => $agents,
            'agent_groups'               => $agentGroups,
            'support_products'           => Product::select(['id', 'title'])->get(),
            'client_priorities'          => Helper::customerTicketPriorities(),
            'ticket_statuses'            => Helper::ticketStatuses(),
            'ticket_statuses_group'      => Helper::ticketStatusGroups(),
            'changeable_ticket_statuses' => Helper::changeableTicketStatuses(),
            'admin_priorities'           => Helper::adminTicketPriorities(),
            'mailboxes'                  => MailBox::getAccessibleBoxes(),
            // Whether the site has any inbox at all, regardless of what this agent may see.
            // `mailboxes` is restriction-filtered and can legitimately be empty, so it cannot
            // double as the "is Fluent Support set up yet?" signal the setup wizard keys off.
            'has_mailboxes'              => MailBox::count() > 0,
            'me'                         => $me,
            'pref'                       => [
                'go_back_after_reply' => 'yes'
            ],
            'notification_integrations'  => $integrationDrivers,
            'internal_notification_settings' => (new NotificationSettings())->get(false),
            'server_time'                => gmdate('Y-m-d\TH:i:sP'),
            'has_email_parser'           => defined('FLUENTSUPPORTPRO_PLUGIN_VERSION'),
            'ticket_tags'                => $tags,
            'i18n'                       => $i18ns,
            'custom_fields'              => apply_filters('fluent_support/ticket_custom_fields', []),
            'has_file_upload'            => !!Helper::ticketAcceptedFileMiles(),
            'repost_export_options'      => Helper::getExportOptions(),
            'enable_draft_mode'          => Helper::getBusinessSettings('enable_draft_mode', 'no'),
            'keyboard_shortcuts'         => Helper::getBusinessSettings('keyboard_shortcuts', 'no'),
            'agent_time_tracking'        => Helper::getBusinessSettings('agent_time_tracking', 'no'),
            'max_file_upload'            => Helper::getBusinessSettings('max_file_upload', 3),
            'ajaxurl'                    => admin_url('admin-ajax.php'),
            'auth_provider'              => Helper::getAuthProvider(),
            'fluent_bot_integration'     =>  Helper::fluentBotIntegrationStatus(),
        ));

        $appVars['fluent_booking'] = (new FluentBookingService())->getStatus();

        if (defined('FLUENTCRM')) {
            $appVars['fluentcrm_config'] = Helper::getFluentCRMTagConfig();
        }

        if (defined('FLUENT_BOARDS')) {
            $appVars['fluent_boards'] = true;
        }

        $appVars['has_pro'] = defined('FLUENTSUPPORTPRO_PLUGIN_VERSION');
        $appVars['version'] = FLUENT_SUPPORT_VERSION;
        if ($appVars['has_pro']) {
            $appVars['agent_feedback_rating'] = Helper::getBusinessSettings('agent_feedback_rating', 'no');
            $appVars['open_ai_integration'] = Helper::openAIIntegrationStatus();
        }

        wp_localize_script('fluent_support_admin_app_start', 'fluentSupportAdmin', $appVars);
    }

    protected function getRestInfo($app)
    {
        $ns = $app->config->get('app.rest_namespace');
        $v = $app->config->get('app.rest_version');

        return [
            'base_url'  => esc_url_raw(rest_url()),
            'url'       => rest_url($ns . '/' . $v),
            'nonce'     => wp_create_nonce('wp_rest'),
            'namespace' => $ns,
            'version'   => $v,
        ];
    }

    protected function getMenuIcon()
    {
        $app = App::getInstance();

        $assets = $app['path.assets'];

        return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg width="100" height="100" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path d="M90 0C95.5228 1.28852e-06 100 4.47715 100 10V90C100 95.5228 95.5228 100 90 100H10C4.47715 100 0 95.5228 0 90V10C1.28855e-06 4.47715 4.47715 0 10 0H90ZM70.2314 44.8672L70.2266 44.876H69.4941C68.7844 44.876 68.0791 44.9715 67.3965 45.1523L60.3203 47.0508L22.0703 57.3096C21.4826 57.4 20.9177 57.604 20.4023 57.9023H20.3428C18.7515 58.8292 17.7751 60.5292 17.7705 62.3691V81.9424C17.7662 82.0596 17.8564 82.1632 17.9736 82.168C18.0595 82.168 18.1409 82.1224 18.1816 82.041C19.9811 78.7134 22.5589 75.8698 25.7012 73.7539C27.0801 72.7774 28.5854 71.9902 30.1768 71.416C30.6694 71.2352 31.167 71.0821 31.6777 70.9375L34.3682 70.209L70.9092 60.4209L71.6641 60.2129C72.0348 60.1496 72.3973 60.0494 72.75 59.9229C76.7916 58.503 78.9165 54.0818 77.4971 50.04C76.4074 46.9384 73.4817 44.8626 70.1992 44.8535L70.2314 44.8672ZM77.1172 19.3086C76.8415 19.3267 76.5612 19.3583 76.29 19.417C75.9194 19.4532 75.5485 19.5217 75.1914 19.6211L21.79 33.9219C20.1217 34.365 18.7609 35.5766 18.1279 37.1816C18.1189 37.2178 18.1001 37.2588 18.082 37.2949C18.046 37.3851 18.0144 37.4798 17.9873 37.5654C17.9602 37.6513 17.915 37.7422 17.915 37.8281C17.9015 37.8733 17.8928 37.9187 17.8838 37.9639C17.8567 38.0765 17.8118 38.2653 17.8115 38.2979V38.3975C17.7889 38.5105 17.7796 38.6285 17.7705 38.7461V45.6494C17.7705 45.8348 17.7844 46.0164 17.8115 46.1973C17.8343 48.3176 19.5613 50.0217 21.6816 50.0127C21.8895 50.0127 22.0978 49.9951 22.3057 49.959C22.6085 49.9454 22.9113 49.8954 23.2051 49.8096L68.7705 37.5928C69.9958 37.2627 71.2669 37.1404 72.5283 37.2354C76.9635 37.5068 80.8557 40.2878 82.5557 44.3975V25.2637C82.5557 25.0286 82.5425 24.8066 82.5244 24.5986V24.4717C82.5242 21.619 80.2141 19.3087 77.3613 19.3086H77.1172Z" fill="#9CA1A8"/>
                        </svg>'
            );
    }
}
