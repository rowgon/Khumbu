<?php

namespace FluentSupport\App\Modules\MCP\Support;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\MCP\Helpers\MCPHelper;
use FluentSupport\App\Modules\PermissionManager;

class TicketAccessGuard
{
    private static $restricted = null;

    private static function restrictedMailboxIds()
    {
        if (self::$restricted === null) {
            self::$restricted = PermissionManager::getRestrictedMailboxIds() ?: [];
        }
        return self::$restricted;
    }

    /**
     * Assert the current user can read/write $ticket.
     *
     * Checks both the canAccessTicket visibility rule and any mailbox
     * restrictions the agent has been assigned. Returns WP_Error on the first
     * failure, null on success — so callers do:
     *
     *   if ($err = TicketAccessGuard::assert($ticket)) {
     *       return $err;
     *   }
     */
    public static function assert(Ticket $ticket)
    {
        if (!PermissionManager::canAccessTicket($ticket)) {
            return MCPHelper::error('forbidden', __('You do not have access to this ticket', 'fluent-support'));
        }

        $restricted = self::restrictedMailboxIds();
        if ($restricted && in_array((int) $ticket->mailbox_id, array_map('intval', $restricted), true)) {
            return MCPHelper::error('forbidden', __('You do not have access to this ticket', 'fluent-support'));
        }

        return null;
    }

    /**
     * Assert the current user can create tickets in / move tickets to $mailboxId.
     * Returns WP_Error on failure, null on success.
     */
    public static function assertMailboxWritable($mailboxId)
    {
        $restricted = self::restrictedMailboxIds();
        if ($restricted && in_array($mailboxId, array_map('intval', $restricted), true)) {
            return MCPHelper::error('forbidden', __('You do not have access to the specified mailbox', 'fluent-support'));
        }
        return null;
    }

    /**
     * Assert that $targetAgent is not restricted from the mailbox of $ticket.
     * Returns WP_Error on failure, null on success.
     */
    public static function assertAssignableAgent(Ticket $ticket, Agent $targetAgent)
    {
        $meta            = $targetAgent->getMeta('agent_restrictions');
        $agentRestricted = (!empty($meta['businessBoxRestrictions']) && !empty($meta['restrictedBusinessBoxes']))
            ? $meta['restrictedBusinessBoxes']
            : [];

        if ($agentRestricted && in_array((int) $ticket->mailbox_id, array_map('intval', $agentRestricted), true)) {
            return MCPHelper::error('forbidden', __("That agent is restricted from this ticket's mailbox", 'fluent-support'));
        }

        return null;
    }
}
