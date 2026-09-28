<?php

namespace FluentSupport\App\Modules\MCP;

use FluentSupport\App\Modules\MCP\Support\PermissionGate;
use FluentSupport\App\Modules\MCP\Tools\ManagementTools;

/**
 * Bootstrap for Fluent Support's Model Context Protocol (MCP) integration.
 *
 * Wires the WordPress Abilities API (core 6.9+) + the WP MCP Adapter (provided
 * by FluentHub or the standalone mcp-adapter plugin). Fluent Support bundles
 * nothing; it consumes whatever is loaded. If neither is available an admin
 * notice is shown when MCP is enabled.
 *
 * The whole surface is gated behind the `_mcp_settings` option (default off).
 * Even when on, the endpoint stays behind WP auth + a Fluent Support agent
 * permission (transport gate) + per-ability permission_callback checks.
 *
 * Entry point: MCPInit::boot(), called from app/Hooks/actions.php.
 */
class MCPInit
{
    const SERVER_ID = 'fluent-support';

    /**
     * Bootstrap entry point, called once from app/Hooks/actions.php.
     *
     * Toolkit discovery runs UNCONDITIONALLY so FluentHub can list Fluent
     * Support on its MCP page even while disabled. The actual server
     * registration only happens when MCP is enabled — zero overhead by default.
     */
    public static function boot()
    {
        // Toolkit discovery runs unconditionally — FluentHub needs this to list
        // Fluent Support on its MCP page even on WP < 6.9 or when MCP is off.
        self::registerWithToolkit();

        // Server init requires both the feature flag and the WP 6.9 Abilities API.
        if (PermissionGate::isEnabled() && function_exists('wp_register_ability')) {
            (new self())->init();
        }
    }

    public function init()
    {
        // Abilities API hooks (fire only on WP 6.9+).
        add_action('wp_abilities_api_categories_init', [$this, 'registerCategory']);
        add_action('wp_abilities_api_init',            [$this, 'registerAbilities']);

        // Dedicated server registration (fires only when an adapter is loaded).
        add_action('mcp_adapter_init', [$this, 'registerCustomServer']);

        // Warn the operator if MCP is on but no adapter is installed.
        add_action('admin_notices', [$this, 'maybeShowAdapterNotice']);

        // Keep get-support-context fresh: invalidate its cache when anything it reports changes.
        $invalidate = [ManagementTools::class, 'invalidateSupportContextCache'];
        foreach ([
            'fluent_support/ticket_created',
            'fluent_support/ticket_closed',
            'fluent_support/agent_assigned_to_ticket',
            'fluent_support/mailbox_saved',
            'fluent_support/agent_created',
        ] as $hook) {
            add_action($hook, $invalidate);
        }
    }

    public function registerCategory()
    {
        wp_register_ability_category(self::SERVER_ID, [
            'label'       => __('Fluent Support', 'fluent-support'),
            'description' => __('Helpdesk ticket management abilities for Fluent Support.', 'fluent-support'),
        ]);
    }

    public function registerAbilities()
    {
        AbilitiesRegistrar::register();

        /**
         * Fires after Fluent Support registers its core MCP abilities. Pro
         * hooks this to register additional abilities under the same namespace.
         *
         * @since 1.0.0
         */
        do_action('fluent_support/mcp_loaded');
    }

    /**
     * Register the dedicated Fluent Support MCP server.
     * Endpoint: /wp-json/fluent-support/mcp
     *
     * @param object $adapter The \WP\MCP\Core\McpAdapter instance.
     */
    public function registerCustomServer($adapter)
    {
        if (!$adapter || !is_object($adapter) || !method_exists($adapter, 'create_server')) {
            return;
        }

        $abilityNames = array_keys(AbilitiesRegistrar::getDefinitions());

        /**
         * Filter the ability names exposed by the Fluent Support MCP server.
         * Pro and extensions push their ability names here.
         *
         * @since 1.0.0
         *
         * @param array $abilityNames Fully-qualified ability names.
         */
        $abilityNames = apply_filters('fluent_support/mcp_ability_names', $abilityNames);
        $abilityNames = array_values(array_unique(array_filter(
            (array) $abilityNames,
            fn($n) => is_string($n) && preg_match('/^[a-z0-9\-\/]+$/', $n)
        )));

        $namespace = sanitize_key(apply_filters('fluent_support/mcp_server_namespace', self::SERVER_ID)) ?: self::SERVER_ID;
        $route     = sanitize_key(apply_filters('fluent_support/mcp_server_route', 'mcp')) ?: 'mcp';

        $adapter->create_server(
            self::SERVER_ID,
            $namespace,
            $route,
            __('Fluent Support MCP Server', 'fluent-support'),
            __('AI agent tools for helpdesk ticket management.', 'fluent-support'),
            defined('FLUENT_SUPPORT_VERSION') ? FLUENT_SUPPORT_VERSION : '1.0.0',
            ['\WP\MCP\Transport\HttpTransport'],
            '\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler',
            '\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler',
            $abilityNames,
            [],
            [],
            [PermissionGate::class, 'transport']
        );
    }

    /**
     * Announce Fluent Support to FluentHub's MCP page via the toolkit filters.
     * Runs UNCONDITIONALLY (even when MCP is off) so the operator can find
     * the card and toggle it on; the toggle handler maps that switch onto our
     * _mcp_settings option. Both filters are cheap no-ops unless the Toolkit
     * applies them, so there's no cost when FluentHub is absent.
     */
    public static function registerWithToolkit()
    {
        add_filter('fluent_kit/mcp_products', function ($products) {
            if (!is_array($products)) {
                $products = [];
            }

            $products[] = [
                'slug'         => self::SERVER_ID,
                'name'         => __('Fluent Support', 'fluent-support'),
                'mcp_enabled'  => PermissionGate::isEnabled(),
                'tools_count'  => self::toolsCount(),
                'endpoint_url' => self::getEndpointUrl(),
                'status'       => self::toolkitStatus(),
            ];

            return $products;
        });

        add_filter('fluent_kit/mcp_toggle_handlers', function ($handlers) {
            if (!is_array($handlers)) {
                $handlers = [];
            }

            $handlers[self::SERVER_ID] = [
                'get_enabled' => [PermissionGate::class, 'isEnabled'],
                'set_enabled' => function ($enabled) {
                    return PermissionGate::setEnabled($enabled);
                },
            ];

            return $handlers;
        });
    }

    /** Count of abilities the server exposes, including any pushed by Pro. */
    public static function toolsCount()
    {
        $names = array_keys(AbilitiesRegistrar::getDefinitions());
        $names = apply_filters('fluent_support/mcp_ability_names', $names);

        return is_array($names) ? count(array_unique($names)) : 0;
    }

    /** Status key the Toolkit renders on the Fluent Support card. */
    public static function toolkitStatus()
    {
        if (!self::adapterAvailable()) {
            return 'adapter_required';
        }

        return PermissionGate::isEnabled() ? 'ready' : 'disabled';
    }

    /** Stable endpoint URL shown in connection snippets. */
    public static function getEndpointUrl()
    {
        $namespace = sanitize_key(apply_filters('fluent_support/mcp_server_namespace', self::SERVER_ID)) ?: self::SERVER_ID;
        $route     = sanitize_key(apply_filters('fluent_support/mcp_server_route', 'mcp')) ?: 'mcp';

        return get_rest_url(null, trailingslashit($namespace) . $route);
    }

    /** True when a WP MCP adapter + the Abilities API are both present. */
    public static function adapterAvailable()
    {
        return defined('WP_MCP_VERSION')
            && class_exists('\WP\MCP\Core\McpAdapter')
            && function_exists('wp_register_ability');
    }

    public function maybeShowAdapterNotice()
    {
        if (self::adapterAvailable() || !current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__(
            'Fluent Support MCP is enabled but no MCP adapter was found. Install FluentHub (recommended) or the MCP Adapter plugin on WordPress 6.9+.',
            'fluent-support'
        );
        echo '</p></div>';
    }
}
