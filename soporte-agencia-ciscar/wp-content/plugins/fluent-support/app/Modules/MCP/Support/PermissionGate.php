<?php

namespace FluentSupport\App\Modules\MCP\Support;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;

/**
 * Maps MCP abilities to Fluent Support's existing capability model. The MCP
 * user IS a WordPress user with Fluent Support agent permissions (fst_*),
 * stored in _fluent_support_permissions user meta. We never invent a parallel
 * permission system — we reuse PermissionManager, the same check the admin
 * REST routes use (AgentTicketPolicy calls the same methods for every ticket
 * API request).
 *
 * Two layers:
 *   - transport(): can this user reach the Fluent Support MCP endpoint at all?
 *   - per-ability permission_callback: gating inside AbilitiesRegistrar.
 *
 * Annotations are UX hints only; this class is the transport enforcement boundary.
 */
class PermissionGate
{
    const OPTION_KEY = '_mcp_settings';

    /**
     * Transport gate for the fluent-support MCP server. Runs on every request.
     * Checks (a) MCP is enabled and (b) user holds at least a Fluent Support
     * agent permission. Per-ability permission_callback still runs on top.
     *
     * @param mixed $request
     * @return true|\WP_Error
     */
    public static function transport($request = null)
    {
        if (!self::isEnabled()) {
            return new \WP_Error(
                'fluent_support_mcp_disabled',
                __('Fluent Support MCP is disabled. Enable it in Settings → MCP.', 'fluent-support')
            );
        }

        // Explicit login check for a clear error code; canAccessTicketRoutes()
        // already returns false for unauthenticated users (user_id 0 → empty
        // permissions), but the distinct code helps the agent distinguish
        // "not logged in" from "logged in but no Fluent Support role".
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'fluent_support_mcp_unauthorized',
                __('Authentication required to access the Fluent Support MCP server.', 'fluent-support')
            );
        }

        // Reuse the same check AgentTicketPolicy uses for all GET ticket routes.
        if (!PermissionManager::canAccessTicketRoutes()) {
            return new \WP_Error(
                'fluent_support_mcp_forbidden',
                __('Your account does not have Fluent Support access.', 'fluent-support')
            );
        }

        return true;
    }

    /**
     * Whether the MCP server is enabled. Ships OFF; enabled via the
     * fluent_kit/mcp_toggle_handlers filter (FluentHub) or a future settings UI.
     */
    public static function isEnabled()
    {
        $settings = Helper::getOption(self::OPTION_KEY, []);

        return is_array($settings) && isset($settings['active']) && $settings['active'] === 'yes';
    }

    /**
     * Persist the master on/off switch. Called from the FluentHub toggle handler
     * and any future settings controller.
     *
     * Defense-in-depth: re-check manage_options here because the toolkit toggle
     * path delegates auth to an external plugin.
     *
     * @param bool $enabled
     * @return bool
     */
    public static function setEnabled($enabled)
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $settings = Helper::getOption(self::OPTION_KEY, []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['active'] = $enabled ? 'yes' : 'no';
        Helper::updateOption(self::OPTION_KEY, $settings);

        return (bool) $enabled;
    }

    /**
     * SLA thresholds used by getSupportContext and getSupportInsights.
     * Written by McpSettingsController::saveSettings().
     *
     * @return array{first_response_hours: int, resolution_hours: int}
     */
    public static function getSlaSettings()
    {
        $settings = Helper::getOption(self::OPTION_KEY, []);
        $sla      = isset($settings['sla']) && is_array($settings['sla']) ? $settings['sla'] : [];

        return [
            'first_response_hours' => min(max((int) ($sla['first_response_hours'] ?? 4), 1), 720),
            'resolution_hours'     => min(max((int) ($sla['resolution_hours'] ?? 24), 1), 8760),
        ];
    }

    /**
     * AI guidelines injected into getSupportContext. Sanitized at read time so
     * anything stored raw before Phase 3 is cleaned on the way out.
     *
     * @return string Up to 2000 characters, no HTML.
     */
    public static function getAiGuidelines()
    {
        $settings = Helper::getOption(self::OPTION_KEY, []);
        $raw      = isset($settings['ai_guidelines']) ? (string) $settings['ai_guidelines'] : '';

        return mb_substr(wp_strip_all_tags($raw), 0, 2000);
    }
}
