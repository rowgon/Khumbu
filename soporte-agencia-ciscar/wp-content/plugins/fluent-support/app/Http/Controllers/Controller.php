<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\Framework\Http\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Ensure the current agent has at least one ticket management permission.
     * Draft-only agents (fst_draft_reply only) cannot perform mutating actions.
     *
     * @throws \Exception
     */
    protected function ensureCanManageTickets()
    {
        if (PermissionManager::canManageTickets()) {
            return;
        }

        throw new \Exception(
            esc_html__('You do not have permission to perform this action.', 'fluent-support')
        );
    }

    /**
     * Ensure the current agent can access the given ticket based on visibility level.
     *
     * @param object $ticket
     * @throws \Exception
     */
    protected function ensureCanAccessTicket($ticket)
    {
        if (PermissionManager::canAccessTicket($ticket)) {
            return;
        }

        throw new \Exception(
            esc_html__('Sorry, You do not have permission to this ticket', 'fluent-support')
        );
    }
}
