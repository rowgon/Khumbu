<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Ticket;

class AsyncActionHandler
{
    public function resolveAgentAssigned($agentId, $ticketId, $assignerId, $previousAgentId = 0)
    {
        $agent = Agent::find($agentId);
        $ticket = Ticket::find($ticketId);
        $assigner = Agent::find($assignerId);

        if ($agent && $ticket) {
            do_action('fluent_support/agent_assigned_to_ticket', $agent, $ticket, $assigner ?: $agent, $previousAgentId);
        }
    }
}
