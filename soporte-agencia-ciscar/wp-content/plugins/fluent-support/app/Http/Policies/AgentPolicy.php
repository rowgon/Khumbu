<?php

namespace FluentSupport\App\Http\Policies;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Foundation\Policy;

class AgentPolicy extends Policy
{
    /**
     * Check user permission for any method
     * @param  \FluentSupport\Framework\Http\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        // Read access (index) and avatar routes keep the existing boundary.
        return PermissionManager::currentUserCan('fst_sensitive_data');
    }

    public function addAgent(Request $request)
    {
        return $this->guardManageOptions();
    }

    public function updateAgent(Request $request)
    {
        return $this->guardManageOptions();
    }

    public function deleteAgent(Request $request)
    {
        return $this->guardManageOptions();
    }

    /**
     * Agent records carry the plugin's permission set, so mutating them is a
     * privilege-granting operation. Gate on a WordPress capability the plugin's
     * own permission system cannot mint (FS-SEC-003). Throwing (not returning
     * false) surfaces a specific 403 message instead of WordPress core's
     * generic "Sorry, you are not allowed to do that."
     *
     * @return Boolean
     * @throws \Exception
     */
    protected function guardManageOptions()
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        throw new \Exception(
            esc_html__('Only administrators can add, edit, or delete support staff.', 'fluent-support')
        );
    }
}
