<?php

namespace FluentSupport\App\Models;

use FluentSupport\App\Services\Tickets\AgentTicketAccess;

class AgentGroup extends Tag
{
    protected static $type = 'agent_group';

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->tag_type = static::$type;
            if (empty($model->created_by) && $userId = get_current_user_id()) {
                $model->created_by = $userId;
            }

            $model->slug = static::slugify($model->title);
        });

        static::addGlobalScope(function ($builder) {
            $builder->where('tag_type', static::$type);
        });
    }

    public static function slugify($title)
    {
        $slug = sanitize_title($title, 'agent-group', 'display');
        if (static::where('slug', $slug)->first()) {
            $slug .= '-' . time();
        }
        return $slug;
    }

    public function agents()
    {
        return $this->belongsToMany(
            Agent::class, 'fs_tag_pivot', 'tag_id', 'source_id'
        )->wherePivot('source_type', 'agent_group');
    }

    /**
     * Get the least-loaded agent in this group, respecting mailbox restrictions.
     *
     * @param int|null $mailboxId
     * @param array $currentCounts Optional pre-loaded counts [agent_id => count]
     * @return Agent|null
     */
    public function getLeastLoadedAgent($mailboxId = null, array &$currentCounts = [])
    {
        $agents = $this->agents()->get();

        if ($agents->isEmpty()) {
            return null;
        }

        if (empty($currentCounts)) {
            $agentIds = $agents->pluck('id')->toArray();
            $currentCounts = Ticket::whereIn('agent_id', $agentIds)
                ->where('status', '!=', 'closed')
                ->selectRaw('agent_id, COUNT(*) as cnt')
                ->groupBy('agent_id')
                ->pluck('cnt', 'agent_id')
                ->toArray();
        }

        $selectedAgent = null;
        $minCount = PHP_INT_MAX;
        $ticketAccess = new AgentTicketAccess();

        foreach ($agents as $agent) {
            if ($mailboxId) {
                $restrictedBoxes = $ticketAccess->getRestrictedMailboxIds($agent);
                if (in_array((int) $mailboxId, $restrictedBoxes, true)) {
                    continue;
                }
            }

            $count = $currentCounts[$agent->id] ?? 0;
            if ($count < $minCount) {
                $minCount = $count;
                $selectedAgent = $agent;
            }
        }

        return $selectedAgent;
    }
}
