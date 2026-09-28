<?php

namespace FluentSupport\App\Services\Notifications;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Ticket;

class RecipientResolver
{
    /**
     * @param array $agentIds
     * @param int|null $excludePersonId
     * @return \FluentSupport\Framework\Support\Collection
     */
    public function resolveMentionedAgents(array $agentIds, $excludePersonId = null)
    {
        $agentIds = array_values(array_unique(array_filter(array_map('intval', $agentIds))));

        if (!$agentIds) {
            return Agent::whereIn('id', [0])->get();
        }

        $query = Agent::whereIn('id', $agentIds);

        if ($excludePersonId) {
            $query->where('id', '!=', (int) $excludePersonId);
        }

        return $query->get();
    }

    /**
     * Resolve a ticket's assigned agent, optionally excluding the actor.
     *
     * @param \FluentSupport\App\Models\Ticket $ticket
     * @param int|null $excludePersonId
     * @return \FluentSupport\App\Models\Agent|null
     */
    public function resolveAssignedAgent(Ticket $ticket, $excludePersonId = null)
    {
        if (!$ticket->agent_id) {
            return null;
        }

        $query = Agent::where('id', $ticket->agent_id);

        if ($excludePersonId) {
            $query->where('id', '!=', $excludePersonId);
        }

        return $query->first();
    }

    /**
     * Normalize recipient person IDs for notification fanout.
     *
     * @param iterable $recipients
     * @param int|null $excludePersonId
     * @return array
     */
    public function extractRecipientPersonIds($recipients, $excludePersonId = null)
    {
        $ids = [];

        foreach ($recipients as $recipient) {
            if (!$recipient || empty($recipient->id)) {
                continue;
            }

            $recipientId = (int) $recipient->id;

            if ($excludePersonId && $recipientId === (int) $excludePersonId) {
                continue;
            }

            $ids[] = $recipientId;
        }

        return array_values(array_unique($ids));
    }
}
