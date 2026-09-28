<?php

namespace FluentSupport\App\Services\Tickets;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;

class AgentTicketAccess
{
    public function currentAgentCanAccess($ticket)
    {
        return $this->canAccess(Helper::getAgentByUserId(), $ticket);
    }

    public function canAccess($agent, $ticket, $restrictions = null)
    {
        if (!$agent || empty($agent->user_id) || !$ticket || empty($ticket->id)) {
            return false;
        }

        if ($this->isMailboxRestricted($agent, $ticket, $restrictions)) {
            return false;
        }

        $visibility = PermissionManager::getAgentTicketVisibility($agent->user_id);

        if ($visibility === PermissionManager::VISIBILITY_ALL) {
            return true;
        }

        if ((int) $ticket->agent_id === (int) $agent->id) {
            return true;
        }

        return !$ticket->agent_id && $visibility === PermissionManager::VISIBILITY_ASSIGNED_AND_UNASSIGNED;
    }

    public function applyAccessScope($query, $agent = null, $restrictions = null)
    {
        $agent = $agent ?: Helper::getAgentByUserId();

        if (!$agent || empty($agent->user_id)) {
            $query->where('id', 0);
            return $query;
        }

        $this->applyVisibilityScope($query, $agent);
        $this->applyMailboxRestrictionScope($query, $agent, $restrictions);

        return $query;
    }

    public function applyVisibilityScope($query, $agent = null)
    {
        $agent = $agent ?: Helper::getAgentByUserId();

        if (!$agent || empty($agent->user_id)) {
            $query->where('id', 0);
            return $query;
        }

        $visibility = PermissionManager::getAgentTicketVisibility($agent->user_id);

        if ($visibility === PermissionManager::VISIBILITY_ALL) {
            return $query;
        }

        if ($visibility === PermissionManager::VISIBILITY_ASSIGNED_ONLY) {
            $query->where('agent_id', $agent->id);
            return $query;
        }

        $query->where(function ($q) use ($agent) {
            $q->where('agent_id', $agent->id);
            $q->orWhereNull('agent_id');
        });

        return $query;
    }

    public function applyMailboxRestrictionScope($query, $agent = null, $restrictions = null)
    {
        $agent = $agent ?: Helper::getAgentByUserId();
        $restrictedBoxes = $this->getRestrictedMailboxIds($agent, $restrictions);

        if ($restrictedBoxes) {
            $query->where(function ($q) use ($restrictedBoxes) {
                $q->whereNotIn('mailbox_id', $restrictedBoxes);
                $q->orWhereNull('mailbox_id');
            });
        }

        return $query;
    }

    public function getRestrictedMailboxIds($agent = null, $restrictions = null)
    {
        $agent = $agent ?: Helper::getAgentByUserId();

        if (!$agent) {
            return [];
        }

        if ($restrictions === null) {
            $restrictions = $agent->getMeta('agent_restrictions', []);
        }

        if (!empty($restrictions['businessBoxRestrictions']) && !empty($restrictions['restrictedBusinessBoxes'])) {
            return array_values(array_unique(array_map('intval', $restrictions['restrictedBusinessBoxes'])));
        }

        return [];
    }

    protected function isMailboxRestricted($agent, $ticket, $restrictions = null)
    {
        $restrictedBoxes = $this->getRestrictedMailboxIds($agent, $restrictions);

        return !empty($ticket->mailbox_id) && in_array((int) $ticket->mailbox_id, $restrictedBoxes, true);
    }
}
