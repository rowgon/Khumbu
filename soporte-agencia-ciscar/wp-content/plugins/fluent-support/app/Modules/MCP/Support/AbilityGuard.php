<?php

namespace FluentSupport\App\Modules\MCP\Support;

use FluentSupport\App\Modules\PermissionManager;

class AbilityGuard
{
    const MANAGE_TICKET_CAPS = [
        'fst_manage_own_tickets',
        'fst_manage_unassigned_tickets',
        'fst_manage_other_tickets',
    ];

    /**
     * Per-ability permission requirements.
     *
     * any_of — passes if the user holds at least one of the listed caps.
     * all_of — every listed cap must be held individually.
     *
     * Both keys may be present; both must pass.
     */
    private static function capabilities()
    {
        return [
            'fluent-support/list-tickets'         => ['any_of' => ['fst_view_tickets']],
            'fluent-support/get-ticket'           => ['any_of' => ['fst_view_tickets']],
            'fluent-support/create-ticket'        => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/reply-to-ticket'      => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/close-ticket'         => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/reopen-ticket'        => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/update-ticket'        => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/delete-ticket'        => ['any_of' => ['fst_delete_tickets']],
            'fluent-support/get-ticket-activity'  => ['any_of' => ['fst_view_tickets']],
            'fluent-support/merge-tickets'        => ['any_of' => ['fst_merge_tickets']],
            'fluent-support/add-internal-note'    => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/assign-ticket'        => ['any_of' => self::MANAGE_TICKET_CAPS, 'all_of' => ['fst_assign_agents']],
            'fluent-support/tag-ticket'           => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/create-tag'           => ['any_of' => self::MANAGE_TICKET_CAPS],
            'fluent-support/get-customer-tickets' => ['any_of' => ['fst_sensitive_data']],
            'fluent-support/get-support-context'  => ['any_of' => ['fst_view_tickets']],
            'fluent-support/list-saved-replies'   => ['any_of' => ['fst_manage_saved_replies']],
            'fluent-support/create-saved-reply'   => ['any_of' => ['fst_manage_saved_replies']],
            'fluent-support/update-saved-reply'   => ['any_of' => ['fst_manage_saved_replies']],
            'fluent-support/delete-saved-reply'   => ['any_of' => ['fst_manage_saved_replies']],
            'fluent-support/get-support-insights' => ['any_of' => ['fst_view_all_reports']],
            'fluent-support/search-customers'     => ['any_of' => ['fst_sensitive_data']],
            'fluent-support/get-mentions'         => ['any_of' => ['fst_view_tickets']],
            'fluent-support/list-workflows'       => ['any_of' => ['fst_manage_workflows']],
            'fluent-support/bulk-action'          => ['any_of' => self::MANAGE_TICKET_CAPS],
        ];
    }

    /**
     * Returns a permission_callback closure for the given ability name.
     *
     * Only checks that the user is authenticated. Capability enforcement
     * is deferred to wrapExecuteCallback() in AbilitiesRegistrar so that
     * all denials — both auth and cap — return structured JSON via
     * MCPHelper::error() rather than the adapter's plain "Permission denied".
     *
     * Fails closed for unknown abilities.
     */
    public static function callbackFor($abilityName)
    {
        return function () use ($abilityName) {
            if (!array_key_exists($abilityName, self::capabilities())) {
                return false;
            }
            return is_user_logged_in();
        };
    }

    public static function check($abilityName)
    {
        $caps = self::capabilities()[$abilityName] ?? null;

        if ($caps === null) {
            return false;
        }

        if (!empty($caps['any_of']) && !PermissionManager::userCan($caps['any_of'])) {
            return false;
        }

        if (!empty($caps['all_of'])) {
            foreach ($caps['all_of'] as $cap) {
                if (!PermissionManager::currentUserCan($cap)) {
                    return false;
                }
            }
        }

        return true;
    }
}
