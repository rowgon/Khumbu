<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Tickets\AgentTicketAccess;

class PermissionFilterManager
{
    public function init()
    {
        add_action('fluent_support/tickets_query_by_permission_ref', array($this, 'filterAgentTickets'), 10, 2);
        add_action('fluent_support\main_tickets_query', array($this, 'filterAgentTicketsByMailboxes'), 10, 2);
    }

    public function filterAgentTickets($ticketsQuery, $userId = false)
    {
        $agent = Helper::getAgentByUserId($userId ?: null);

        (new AgentTicketAccess())->applyVisibilityScope($ticketsQuery, $agent);
    }

    public function filterAgentTicketsByMailboxes($ticketsQuery, $args = [] )
    {
        (new AgentTicketAccess())->applyMailboxRestrictionScope($ticketsQuery);
    }
}
