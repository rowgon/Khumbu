<?php

namespace FluentSupport\App\Modules;

use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Tickets\AgentTicketAccess;
use FluentSupport\Framework\Support\Arr;

/**
 *  PermissionManager class is responsible for getting/settings data related to permission
 * @package FluentSupport\App\Modules
 *
 * @version 1.0.0
 */

class PermissionManager
{
    const META_KEY = '_fluent_support_permissions';

    // Ticket visibility levels returned by resolveTicketVisibility()
    const VISIBILITY_ALL                    = 'all_tickets';
    const VISIBILITY_ASSIGNED_AND_UNASSIGNED = 'assigned_and_unassigned';
    const VISIBILITY_ASSIGNED_ONLY          = 'assigned_only';

    /**
     * pluginPermissions method will return the list of permissions support by Fluent Support Plugin
     * @return string[]
     */
    public static function pluginPermissions()
    {
        return [
            'fst_view_dashboard',
            'fst_view_tickets',
            'fst_manage_own_tickets',
            'fst_manage_unassigned_tickets',
            'fst_manage_other_tickets',
            'fst_delete_tickets',
            'fst_assign_agents',
            'fst_manage_settings',
            'fst_sensitive_data',
            'fst_manage_workflows',
            'fst_run_workflows',
            'fst_view_all_reports',
            'fst_manage_saved_replies',
            'fst_view_activity_logs',
            'fst_merge_tickets',
            'fst_split_ticket',
            'fst_agent_today_performance',
            'fst_draft_reply',
            'fst_approve_draft_reply'
        ];
    }

    /**
     * Primary permission check. Accepts a single permission string or an array (any match).
     *
     * @param string|array $permissions
     * @return bool
     */
    public static function userCan($permissions)
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        $userPermissions = self::currentUserPermissions();

        if (!$userPermissions) {
            return false;
        }

        if (is_string($permissions)) {
            return in_array($permissions, $userPermissions);
        }

        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (in_array($permission, $userPermissions)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * currentUserCan method will return whether a user has the selected permission or not.
     * Backward-compatible alias for userCan().
     *
     * @param $permission
     * @return bool
     */
    public static function currentUserCan($permission)
    {
        return self::userCan($permission);
    }

    /**
     * attachPermissions method will save selected permissions to user meta.
     * Also cleans up any legacy fst_* WordPress capabilities.
     *
     * @param $user
     * @param $permissions
     * @return false|mixed
     */
    public static function attachPermissions($user, $permissions)
    {
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }

        if (!$user) {
            return false;
        }

        if (user_can($user, 'manage_options')) {
            return $user;
        }

        $allPermissions = self::pluginPermissions();

        // Allowlist (never a denylist): only known plugin permissions may be written.
        // The privilege-ceiling invariant (an actor may only grant permissions it holds)
        // is enforced upstream by AgentPolicy, which limits agent mutations to
        // administrators — the only entry point that reaches this write (FS-SEC-003).
        $permissions = array_values(array_intersect($allPermissions, $permissions));

        $exclusionRules = self::getExclusionRules();
        $permissions = self::applyExclusionRules($permissions, $exclusionRules);

        // Auto-grant fst_view_tickets when any manage, draft, or approve permission is present
        $manageOrDraftPermissions = [
            'fst_manage_own_tickets',
            'fst_manage_unassigned_tickets',
            'fst_manage_other_tickets',
            'fst_draft_reply',
            'fst_approve_draft_reply',
        ];

        if (!empty(array_intersect($permissions, $manageOrDraftPermissions))
            && !in_array('fst_view_tickets', $permissions)) {
            $permissions[] = 'fst_view_tickets';
        }

        // Store permissions in user meta
        update_user_meta($user->ID, self::META_KEY, array_values($permissions));

        // Clean up legacy WordPress capabilities
        foreach ($allPermissions as $cap) {
            $user->remove_cap($cap);
        }

        return $user;
    }

    /**
     * Clean removal of all Fluent Support permissions for a user.
     *
     * @param int $userId
     * @return void
     */
    public static function detachPermissions($userId)
    {
        delete_user_meta($userId, self::META_KEY);

        // Clean up any legacy WordPress capabilities
        $user = get_user_by('ID', $userId);
        if ($user && !user_can($user, 'manage_options')) {
            foreach (self::pluginPermissions() as $cap) {
                $user->remove_cap($cap);
            }
        }
    }

    /**
     * Remove conflicting permissions based on exclusion rules.
     *
     * @param array $permissions The array of permissions to filter.
     * @param array $rules       Each key => value pair means: if key is present, remove value.
     * @return array The filtered array of permissions.
     */
    public static function applyExclusionRules($permissions, $rules)
    {
        foreach ($rules as $requiredKey => $removeKey) {
            if (in_array($requiredKey, $permissions) && in_array($removeKey, $permissions)) {
                unset($permissions[array_search($removeKey, $permissions)]);
            }
        }
        return $permissions;
    }

    /**
     * Get the mutual exclusion rules for permission assignment.
     *
     * @return array Each key => value pair means: if key is present, remove value.
     */
    public static function getExclusionRules()
    {
        // Mutual exclusion rules applied when assigning permissions:
        // - If agent has any manage_*_tickets permission, remove fst_draft_reply
        //   (draft-only mode is for agents who CANNOT manage tickets)
        // - If agent has fst_draft_reply, remove fst_approve_draft_reply
        //   (draft-only agents should not approve their own drafts)
        return [
            'fst_manage_unassigned_tickets' => 'fst_draft_reply',
            'fst_manage_other_tickets'      => 'fst_draft_reply',
            'fst_manage_own_tickets'        => 'fst_draft_reply',
            'fst_draft_reply'               => 'fst_approve_draft_reply'
        ];
    }

    /**
     * Get raw permissions from user meta.
     *
     * @param int|null $userId
     * @return array
     */
    public static function getMetaPermissions($userId = null)
    {
        if ($userId === null) {
            $userId = get_current_user_id();
        }

        if (!$userId) {
            return [];
        }

        $permissions = get_user_meta($userId, self::META_KEY, true);

        return is_array($permissions) ? $permissions : [];
    }

    /**
     * getUserPermissions method will get all permissions for a user.
     * Reads from user meta with legacy wp_capabilities fallback.
     *
     * @param false $user
     * @return array|string[]
     */
    public static function getUserPermissions($user = false)
    {
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }

        if (!$user) {
            return [];
        }

        $pluginPermission = self::pluginPermissions();

        if ($user->has_cap('manage_options')) {
            $pluginPermission[] = 'administrator';
            $pluginPermission = array_values(array_diff($pluginPermission, ['fst_draft_reply']));
            return $pluginPermission;
        }

        // Read from meta first
        $permissions = self::getMetaPermissions($user->ID);

        if (!empty($permissions)) {
            return array_values(array_intersect($permissions, $pluginPermission));
        }

        // Legacy fallback: read from wp_capabilities and migrate
        $legacyPermissions = array_values(array_intersect(array_keys($user->allcaps), $pluginPermission));

        if (!empty($legacyPermissions)) {
            // Migrate to meta
            update_user_meta($user->ID, self::META_KEY, $legacyPermissions);

            // Clean up legacy caps
            foreach ($legacyPermissions as $cap) {
                $user->remove_cap($cap);
            }
        }

        return $legacyPermissions;
    }

    /**
     * currentUserPermissions method will return the permission of logged-in user
     * @param bool $cached
     * @return array|mixed|string[]
     */
    public static function currentUserPermissions($cached = true)
    {
        static $permissions;

        if ($permissions && $cached) {
            return $permissions;
        }

        $permissions = self::getUserPermissions(get_current_user_id());

        return $permissions;
    }

    /**
     * Determine the WordPress capability string for menu registration.
     * Returns 'manage_options' for admins, the user's WP role for agents
     * with permissions, or empty string to hide the menu.
     *
     * @return string
     */
    public static function getMenuPermission()
    {
        if (current_user_can('manage_options')) {
            return 'manage_options';
        }

        $userId = get_current_user_id();

        if (!$userId) {
            return '';
        }

        $metaPermissions = self::getMetaPermissions($userId);

        // Legacy fallback: check wp_capabilities for fst_* caps
        if (empty($metaPermissions)) {
            $user = get_user_by('ID', $userId);
            if ($user) {
                $legacyPermissions = array_intersect(array_keys($user->allcaps), self::pluginPermissions());
                if (empty($legacyPermissions)) {
                    return '';
                }
            } else {
                return '';
            }
        }

        $user = wp_get_current_user();
        $roles = array_values((array) $user->roles);

        return Arr::get($roles, 0, '');
    }

    /**
     * Get the mailbox IDs that the current agent is restricted from accessing.
     *
     * @return array Mailbox IDs the agent cannot access, or empty array if unrestricted.
     */
    public static function getRestrictedMailboxIds()
    {
        return (new AgentTicketAccess())->getRestrictedMailboxIds();

    }

    /**
     * Whether the current user can perform mutating ticket actions (reply, close, reopen, assign, etc.).
     * Draft-only agents return false here — they can view tickets and create drafts but cannot publish.
     *
     * @return bool
     */
    public static function canManageTickets()
    {
        return self::userCan([
            'fst_manage_own_tickets',
            'fst_manage_unassigned_tickets',
            'fst_manage_other_tickets'
        ]);
    }

    /**
     * Whether the current user can access ticket API routes at all (read or write).
     * Includes manage, merge, draft-only, and view-only agents.
     *
     * @return bool
     */
    public static function canAccessTicketRoutes()
    {
        return self::userCan([
            'fst_view_tickets',
            'fst_manage_own_tickets',
            'fst_manage_unassigned_tickets',
            'fst_manage_other_tickets',
            'fst_merge_tickets',
            'fst_draft_reply'
        ]);
    }

    /**
     * Determine ticket visibility level from a permission set.
     *
     * Business rule: fst_view_tickets and fst_draft_reply get full visibility because
     * read-only and draft agents need to view any ticket, even though they cannot publish.
     *
     * @param array $permissions
     * @return string One of the VISIBILITY_* constants.
     */
    private static function resolveTicketVisibility(array $permissions)
    {
        // Manage-level permissions take priority for visibility
        if (in_array('fst_manage_other_tickets', $permissions)) {
            return self::VISIBILITY_ALL;
        }

        if (in_array('fst_manage_unassigned_tickets', $permissions)) {
            return self::VISIBILITY_ASSIGNED_AND_UNASSIGNED;
        }

        if (in_array('fst_manage_own_tickets', $permissions)) {
            return self::VISIBILITY_ASSIGNED_ONLY;
        }

        // Non-manage roles (draft, view-only) can see all tickets but cannot modify
        if (in_array('fst_draft_reply', $permissions)
            || in_array('fst_view_tickets', $permissions)) {
            return self::VISIBILITY_ALL;
        }

        return self::VISIBILITY_ASSIGNED_ONLY;
    }

    /**
     * currentTicketVisibility method will return the permission level for a user in tickets
     * @return string
     */
    public static function currentTicketVisibility()
    {
        $permissions = self::currentUserPermissions();
        return self::resolveTicketVisibility($permissions);
    }

    /**
     * getAgentTicketVisibility method will return the access level of an agent in tickets
     * @param false $userId
     * @return string
     */
    public static function getAgentTicketVisibility($userId = false)
    {
        if (!$userId) {
            $userId = get_current_user_id();
        }

        $permissions = self::getUserPermissions($userId);

        return self::resolveTicketVisibility($permissions);
    }

    /**
     * canAccessTicket method will return whether the selected user has permission in selected ticket or not
     * @param $ticket
     * @return bool
     */
    public static function canAccessTicket($ticket)
    {
        return (new AgentTicketAccess())->currentAgentCanAccess($ticket);
    }

    /**
     * getReadablePermissionGroups method will return the permission group as array
     * @return array[]
     */
    public static function getReadablePermissionGroups()
    {
        return [
            [
                'title'       => __('Tickets Permissions', 'fluent-support'),
                'permissions' => [
                    'fst_view_dashboard'            => __('View Dashboard', 'fluent-support'),
                    'fst_manage_own_tickets'        => __('Manage Own Tickets', 'fluent-support'),
                    'fst_manage_unassigned_tickets' => __('Manage Unassigned Tickets', 'fluent-support'),
                    'fst_manage_other_tickets'      => __('Manage Others Tickets', 'fluent-support'),
                    'fst_assign_agents'             => __('Assign Agents', 'fluent-support'),
                    'fst_delete_tickets'            => __('Delete Tickets & Individual Responses', 'fluent-support'),
                    'fst_merge_tickets'             => __('Merge Tickets', 'fluent-support'),
                    'fst_split_ticket'              => __('Split Ticket', 'fluent-support'),
                    'fst_draft_reply'               => __('Draft Reply', 'fluent-support'),
                    'fst_approve_draft_reply'       => __('Approve Draft Reply', 'fluent-support'),
                    'fst_view_tickets'              => __('View Tickets (Read Only)', 'fluent-support'),
                ]
            ],
            [
                'title'       => __('Workflow Permissions', 'fluent-support'),
                'permissions' => [
                    'fst_manage_workflows'     => __('Manage Workflows', 'fluent-support'),
                    'fst_run_workflows'        => __('Run workflows', 'fluent-support'),
                    'fst_manage_saved_replies' => __('Manage Saved Replies', 'fluent-support')
                ]
            ],
            [
                'title'       => __('Settings', 'fluent-support'),
                'permissions' => [
                    'fst_manage_settings' => __('Manage Overall Settings', 'fluent-support'),
                    'fst_sensitive_data'  => __('Access Private Data (Customers, Agents)', 'fluent-support')
                ]
            ],
            [
                'title'       => __('Reporting', 'fluent-support'),
                'permissions' => [
                    'fst_view_all_reports'   => __('View All Reports', 'fluent-support'),
                    'fst_view_activity_logs' => __('View Activity Logs', 'fluent-support'),
                    'fst_agent_today_performance' => __('View Agent Today Performance', 'fluent-support'),
                ]
            ]
        ];
    }

    public static function getMailboxesForRestriction()
    {
        return MailBox::select(['id', 'name'])->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Deprecated Methods
    |--------------------------------------------------------------------------
    | These methods are kept for backward compatibility with third-party add-ons.
    | They delegate to the renamed replacements and will be removed in a future release.
    */

    /**
     * @deprecated Use currentTicketVisibility() instead.
     */
    public static function currentUserTicketsPermissionLevel()
    {
        _deprecated_function(__METHOD__, '2.0.5', 'PermissionManager::currentTicketVisibility()');

        return self::mapVisibilityToLegacy(self::currentTicketVisibility());
    }

    /**
     * @deprecated Use getAgentTicketVisibility() instead.
     */
    public static function agentTicketPermissionLevel($userId = false)
    {
        _deprecated_function(__METHOD__, '2.0.5', 'PermissionManager::getAgentTicketVisibility()');

        return self::mapVisibilityToLegacy(self::getAgentTicketVisibility($userId));
    }

    /**
     * @deprecated Use canAccessTicket() instead.
     */
    public static function hasTicketPermission($ticket)
    {
        _deprecated_function(__METHOD__, '2.0.5', 'PermissionManager::canAccessTicket()');

        return self::canAccessTicket($ticket);
    }

    /**
     * Map new VISIBILITY_* constants back to legacy string values.
     *
     * @param string $visibility
     * @return string 'all', 'own_plus', or 'own'
     */
    private static function mapVisibilityToLegacy($visibility)
    {
        $map = [
            self::VISIBILITY_ALL                    => 'all',
            self::VISIBILITY_ASSIGNED_AND_UNASSIGNED => 'own_plus',
            self::VISIBILITY_ASSIGNED_ONLY          => 'own',
        ];

        return $map[$visibility] ?? 'own';
    }
}
