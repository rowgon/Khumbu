<?php

namespace FluentSupport\App\Modules\MCP\Tools;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Models\TicketTag;
use FluentSupport\App\Modules\MCP\Helpers\MCPHelper;
use FluentSupport\App\Modules\MCP\Support\PermissionGate;
use FluentSupport\App\Modules\MCP\Support\TicketAccessGuard;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\TicketHelper;
use FluentSupport\App\Services\Tickets\TicketService;

class ManagementTools
{
    const CACHE_PREFIX  = 'fsmcp_ctx_';
    const CACHE_TTL     = 60;
    const MAX_AGENTS    = 50;
    const MAX_BULK      = 50;
    const MAX_TAG_NAMES = 20;

    public static function getSupportContext($params)
    {
        $currentAgent = MCPHelper::resolveAgent();
        if (!$currentAgent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $cacheKey = self::CACHE_PREFIX . $currentAgent->id;
        $cached   = get_transient($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $result = self::buildContext($currentAgent);
        set_transient($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    private static function buildContext($currentAgent)
    {
        $canSeeSensitive     = PermissionManager::userCan('fst_sensitive_data');
        $restrictedMailboxes = PermissionManager::getRestrictedMailboxIds();

        $agents = Agent::select(['id', 'first_name', 'last_name', 'email', 'status'])
            ->where('person_type', 'agent')
            ->where('first_name', '!=', '')
            ->whereNotNull('first_name')
            ->orderBy('first_name', 'ASC')
            ->get()
            ->map(function ($agent) use ($canSeeSensitive) {
                $entry = [
                    'id'     => $agent->id,
                    'name'   => MCPHelper::personName($agent),
                    'status' => $agent->status,
                ];
                if ($canSeeSensitive) {
                    $entry['email'] = $agent->email;
                }
                return $entry;
            })->toArray();

        $products = Product::select(['id', 'title', 'description'])
            ->orderBy('title', 'ASC')
            ->get()
            ->map(function ($p) {
                return ['id' => $p->id, 'title' => $p->title, 'description' => $p->description ?: ''];
            })->toArray();

        $mailboxes = MailBox::getAccessibleBoxes($canSeeSensitive)
            ->map(function ($mb) use ($canSeeSensitive) {
                $entry = ['id' => $mb->id, 'name' => $mb->name];
                if ($canSeeSensitive) {
                    $entry['email'] = $mb->email;
                }
                return $entry;
            })->toArray();

        $tags = TicketTag::select(['id', 'title'])
            ->orderBy('title', 'ASC')
            ->get()
            ->toArray();

        $statusCounts = Ticket::selectRaw('status, COUNT(*) as cnt')
            ->when($restrictedMailboxes, fn($q) => $q->whereNotIn('mailbox_id', $restrictedMailboxes))
            ->groupBy('status')
            ->get()
            ->pluck('cnt', 'status');

        $stats = [
            'total'      => (int) $statusCounts->sum(),
            'new'        => (int) ($statusCounts['new'] ?? 0),
            'active'     => (int) ($statusCounts['active'] ?? 0),
            'closed'     => (int) ($statusCounts['closed'] ?? 0),
            'unassigned' => Ticket::whereNull('agent_id')
                ->whereNotIn('status', ['closed'])
                ->when($restrictedMailboxes, fn($q) => $q->whereNotIn('mailbox_id', $restrictedMailboxes))
                ->count(),
        ];

        $ticketFields = ['id', 'title', 'status', 'priority', 'customer_id', 'agent_id', 'waiting_since', 'created_at', 'response_count', 'last_agent_response', 'last_customer_response'];
        $eagerLoad    = [
            'customer' => function ($q) { $q->select(['id', 'first_name', 'last_name', 'email']); },
        ];

        $slaSettings        = PermissionGate::getSlaSettings();
        $firstResponseHours = $slaSettings['first_response_hours'];
        $resolutionHours    = $slaSettings['resolution_hours'];

        $now                 = strtotime(current_time('mysql'));
        $firstResponseCutoff = date('Y-m-d H:i:s', $now - ($firstResponseHours * 3600));
        $resolutionCutoff    = date('Y-m-d H:i:s', $now - ($resolutionHours * 3600));

        $myQueueBase = static::scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
            ->where('agent_id', $currentAgent->id)
            ->whereNotIn('status', ['closed']);

        // Split by who's turn it is instead of ranking both populations by raw waiting_since:
        // an agent-last ticket idle for 12 days and a customer-last ticket waiting 12 days both
        // have an old waiting_since, but only the latter needs a reply. Mixing them let stale
        // agent-last tickets crowd the actionable ones out of the capped list.
        $awaitingYourReplyQuery = (clone $myQueueBase)->waitingOnly();
        $awaitingYourReplyTotal = (clone $awaitingYourReplyQuery)->count();
        $awaitingYourReply      = $awaitingYourReplyQuery
            ->orderBy('waiting_since', 'ASC')
            ->limit(10)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $awaitingCustomerQuery = (clone $myQueueBase)->where(function ($q) {
            $q->whereColumn('last_customer_response', '<', 'last_agent_response');
        });
        $awaitingCustomerTotal = (clone $awaitingCustomerQuery)->count();
        $awaitingCustomer      = $awaitingCustomerQuery
            ->orderBy('waiting_since', 'ASC')
            ->limit(5)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $myQueue = [
            'awaiting_your_reply'           => $awaitingYourReply,
            'awaiting_your_reply_total'     => $awaitingYourReplyTotal,
            'awaiting_your_reply_truncated' => $awaitingYourReplyTotal > count($awaitingYourReply),
            'awaiting_customer'             => $awaitingCustomer,
            'awaiting_customer_total'       => $awaitingCustomerTotal,
            'awaiting_customer_truncated'   => $awaitingCustomerTotal > count($awaitingCustomer),
        ];

        $unassigned = static::scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
            ->whereNull('agent_id')
            ->whereNotIn('status', ['closed'])
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $longestWaiting = static::scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
            ->whereNotIn('status', ['closed'])
            ->whereNotNull('waiting_since')
            ->orderBy('waiting_since', 'ASC')
            ->limit(5)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $critical = static::scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
            ->where('priority', 'critical')
            ->whereNotIn('status', ['closed'])
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $slaFirstResponse = static::scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
            ->whereNotIn('status', ['closed'])
            ->where('response_count', 0)
            ->where('created_at', '<=', $firstResponseCutoff)
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $slaResolution = static::scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
            ->whereNotIn('status', ['closed'])
            ->where('created_at', '<=', $resolutionCutoff)
            ->orderBy('created_at', 'ASC')
            ->limit(5)
            ->get()
            ->map(function ($t) use ($now) { return self::formatContextTicket($t, $now); })
            ->toArray();

        $guidelines = apply_filters('fluent_support/mcp_ai_guidelines', PermissionGate::getAiGuidelines());
        if (!$guidelines) {
            $guidelines = 'Be professional and empathetic. Address customers by name. '
                . 'For triage, prioritize: critical priority first, then longest waiting, then unassigned. '
                . 'Always check the conversation history before replying. '
                . 'Use internal notes to document decisions or escalation reasons.';
        }

        $openCount = $stats['new'] + $stats['active'];

        return MCPHelper::envelope(
            "Support context: {$openCount} open ticket(s), {$stats['unassigned']} unassigned",
            [
                'you'      => [
                    'agent_id' => $currentAgent->id,
                    'name'     => MCPHelper::personName($currentAgent),
                    'email'    => $currentAgent->email,
                ],
                'my_queue' => $myQueue,
                'needs_attention' => [
                    'unassigned'      => $unassigned,
                    'longest_waiting' => $longestWaiting,
                    'critical'        => $critical,
                    'sla_breach'      => [
                        'no_first_response'  => $slaFirstResponse,
                        'overdue_resolution' => $slaResolution,
                        'thresholds'         => [
                            'first_response' => $firstResponseHours . 'h',
                            'resolution'     => $resolutionHours . 'h',
                        ],
                    ],
                ],
                'agents'     => $agents,
                'products'   => $products,
                'mailboxes'  => $mailboxes,
                'tags'       => $tags,
                'stats'      => $stats,
                'priorities' => ['normal', 'medium', 'critical'],
                'statuses'   => ['new', 'active', 'closed'],
                'guidelines' => $guidelines,
            ]
        );
    }

    public static function invalidateSupportContextCache()
    {
        global $wpdb;
        $like = $wpdb->esc_like('_transient_' . self::CACHE_PREFIX) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
        $like = $wpdb->esc_like('_transient_timeout_' . self::CACHE_PREFIX) . '%';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like));
    }

    /**
     * Build a ticket query scoped to the current agent's visibility, mirroring listTickets().
     *
     * Applies the same `fluent_support/tickets_query_by_permission_ref` permission scope used by
     * TicketTools::listTickets so the "needs attention" lists never expose tickets — or the
     * customer names/emails they serialize — outside the agent's visibility scope. The
     * restricted-mailbox filter alone is not enough: an agent scoped to only their own tickets
     * must not enumerate other customers' tickets across the rest of the instance.
     */
    private static function scopedTicketQuery($ticketFields, $eagerLoad, $restrictedMailboxes)
    {
        $query = Ticket::select($ticketFields)->with($eagerLoad);

        do_action_ref_array('fluent_support/tickets_query_by_permission_ref', [&$query]);

        if ($restrictedMailboxes) {
            $query->whereNotIn('mailbox_id', $restrictedMailboxes);
        }

        return $query;
    }

    private static function formatContextTicket($ticket, int $now)
    {
        $item = [
            'id'             => $ticket->id,
            'title'          => $ticket->title,
            'status'         => $ticket->status ?: 'new',
            'priority'       => MCPHelper::normalizePriority($ticket->priority),
            'response_count' => (int) $ticket->response_count,
            'last_reply_by'  => $ticket->last_reply_by,
        ];

        if ($ticket->relationLoaded('customer') && $ticket->customer) {
            $item['customer'] = MCPHelper::formatPersonSummary($ticket->customer);
        }

        if ($ticket->waiting_since) {
            $wait = max(0, $now - strtotime($ticket->waiting_since));
            $item['waiting'] = self::formatDuration($wait);
        }

        return $item;
    }

    public static function assignTicket($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $ticketId   = (int) ($params['ticket_id'] ?? 0);
        $newAgentId = (int) ($params['agent_id'] ?? 0);

        if (!$ticketId || !$newAgentId) {
            return MCPHelper::error('invalid_param', __('ticket_id and agent_id are required', 'fluent-support'), ['fields' => ['ticket_id', 'agent_id']]);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return MCPHelper::error('not_found', __('Ticket not found', 'fluent-support'), ['next_step' => 'Use list-tickets to find valid ticket IDs']);
        }

        if ($err = TicketAccessGuard::assert($ticket)) {
            return $err;
        }

        $newAgent = MCPHelper::resolveAssignmentTarget($newAgentId, $ticket, 'agent_id');
        if (is_wp_error($newAgent)) {
            return $newAgent;
        }

        MCPHelper::applyAgentAssignment($ticket, $newAgent, $agent);

        $agentName = MCPHelper::personName($newAgent);

        return MCPHelper::envelope(
            "Ticket #{$ticketId} assigned to {$agentName}",
            ['agent' => MCPHelper::formatPersonSummary($newAgent)]
        );
    }

    public static function tagTicket($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $ticketId = (int) ($params['ticket_id'] ?? 0);
        if (!$ticketId) {
            return MCPHelper::error('invalid_param', __('ticket_id is required', 'fluent-support'), ['fields' => ['ticket_id']]);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return MCPHelper::error('not_found', __('Ticket not found', 'fluent-support'), ['next_step' => 'Use list-tickets to find valid ticket IDs']);
        }

        if ($err = TicketAccessGuard::assert($ticket)) {
            return $err;
        }

        $addTagIds    = array_map('intval', (array) ($params['add_tag_ids'] ?? []));
        $removeTagIds = array_map('intval', (array) ($params['remove_tag_ids'] ?? []));

        $addTagNames = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) ($params['add_tag_names'] ?? [])), function ($name) {
            return $name !== '';
        })));

        if (count($addTagNames) > self::MAX_TAG_NAMES) {
            return MCPHelper::error('invalid_param', sprintf(__('add_tag_names may not exceed %d items per request. Split into multiple calls.', 'fluent-support'), self::MAX_TAG_NAMES), ['fields' => ['add_tag_names'], 'limit' => self::MAX_TAG_NAMES]);
        }

        $namesResult    = self::findOrCreateTagsByTitles($addTagNames);
        $failedTagNames = $namesResult['failed'];
        $createdAnyTag  = $namesResult['created_any'];
        foreach ($namesResult['tags'] as $tag) {
            $addTagIds[] = (int) $tag->id;
        }

        if ($addTagIds) {
            $ticket->applyTags(array_unique($addTagIds));
        }

        if ($removeTagIds) {
            $ticket->detachTags($removeTagIds);
        }

        if ($createdAnyTag) {
            self::invalidateSupportContextCache();
        }

        $ticket->load('tags');

        $summary = "Tags updated on ticket #{$ticketId}";
        if ($failedTagNames) {
            $summary .= sprintf(__(' (failed to create: %s)', 'fluent-support'), implode(', ', $failedTagNames));
        }

        $data = ['tags' => $ticket->tags->map(function ($tag) {
            return ['id' => $tag->id, 'title' => $tag->title];
        })->toArray()];

        if ($failedTagNames) {
            $data['failed_tag_names'] = array_values($failedTagNames);
        }

        return MCPHelper::envelope($summary, $data);
    }

    /**
     * Find an existing ticket tag by exact title match, or create one.
     * created_by is left unset so TicketTag::boot() applies its own
     * get_current_user_id() default — the same convention every other
     * tag-creation path (REST, UI) relies on; setting it here to an
     * agent/person id would mix two incompatible id spaces in the column.
     *
     * @return array{tag: ?TicketTag, created: bool}|null
     */
    private static function findOrCreateTag($title, $description = null)
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }

        $existing = TicketTag::where('title', $title)->first();
        if ($existing) {
            return ['tag' => $existing, 'created' => false];
        }

        $data = ['title' => $title];
        if ($description !== null) {
            $data['description'] = $description;
        }

        return ['tag' => TicketTag::create($data), 'created' => true];
    }

    /**
     * Batched sibling of findOrCreateTag() for tag-ticket's add_tag_names —
     * resolves all titles with a single whereIn() lookup instead of one
     * query per name, then creates only the titles that didn't already
     * exist. Bounded by MAX_TAG_NAMES at the call site.
     *
     * @return array{tags: TicketTag[], created_any: bool, failed: string[]}
     */
    private static function findOrCreateTagsByTitles(array $titles)
    {
        if (!$titles) {
            return ['tags' => [], 'created_any' => false, 'failed' => []];
        }

        $existingByTitle = TicketTag::whereIn('title', $titles)->get()->keyBy('title');

        $tags      = [];
        $failed    = [];
        $createdAny = false;

        foreach ($titles as $title) {
            $existing = $existingByTitle->get($title);
            if ($existing) {
                $tags[] = $existing;
                continue;
            }

            $tag = TicketTag::create(['title' => $title]);
            if ($tag && $tag->id) {
                $tags[]     = $tag;
                $createdAny = true;
            } else {
                $failed[] = $title;
            }
        }

        return ['tags' => $tags, 'created_any' => $createdAny, 'failed' => $failed];
    }

    public static function createTag($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $title = sanitize_text_field($params['title'] ?? '');
        if ($title === '') {
            return MCPHelper::error('invalid_param', __('title is required', 'fluent-support'), ['fields' => ['title']]);
        }

        $description = isset($params['description']) ? sanitize_textarea_field($params['description']) : null;

        $resolved = self::findOrCreateTag($title, $description);
        $tag      = $resolved['tag'] ?? null;

        if (!$tag || !$tag->id) {
            return MCPHelper::error('failed', __('Failed to create tag', 'fluent-support'), ['retryable' => true]);
        }

        if ($resolved['created']) {
            self::invalidateSupportContextCache();
        }

        $summary = $resolved['created']
            ? sprintf(__('Tag "%s" created', 'fluent-support'), $tag->title)
            : sprintf(__('Tag "%s" already exists', 'fluent-support'), $tag->title);

        return MCPHelper::envelope(
            $summary,
            ['tag' => ['id' => $tag->id, 'title' => $tag->title], 'created' => $resolved['created']]
        );
    }

    public static function getSupportInsights($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $period    = sanitize_text_field($params['period'] ?? '7d');
        $periodMap = ['24h' => 1, '7d' => 7, '30d' => 30, '90d' => 90];
        $days      = $periodMap[$period] ?? 7;
        $since     = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        $restrictedMailboxes = PermissionManager::getRestrictedMailboxIds();
        $scoped              = function () use ($restrictedMailboxes) {
            $query = Ticket::query();
            do_action_ref_array('fluent_support/tickets_query_by_permission_ref', [&$query]);
            if ($restrictedMailboxes) {
                $query->whereNotIn('mailbox_id', $restrictedMailboxes);
            }
            return $query;
        };

        $periodTicketsQuery  = $scoped()->where('created_at', '>=', $since);
        $closedInPeriodQuery = $scoped()->where('resolved_at', '>=', $since);

        // Response times — aggregates in SQL; bounded sample only for median.
        $rtBase   = $scoped()->where('first_response_time', '>', 0)->where('created_at', '>=', $since);
        $rtAgg    = (clone $rtBase)->selectRaw('COUNT(*) as cnt, AVG(first_response_time) as avg_val, MAX(first_response_time) as max_val, MIN(first_response_time) as min_val')->first();
        $rtSample = ($rtAgg && (int) $rtAgg->cnt > 0)
            ? (clone $rtBase)->orderByDesc('id')->limit(500)->pluck('first_response_time')->toArray()
            : [];

        // Resolution times — same pattern.
        $ctBase   = (clone $closedInPeriodQuery)->where('total_close_time', '>', 0);
        $ctAgg    = (clone $ctBase)->selectRaw('COUNT(*) as cnt, AVG(total_close_time) as avg_val, MAX(total_close_time) as max_val, MIN(total_close_time) as min_val')->first();
        $ctSample = ($ctAgg && (int) $ctAgg->cnt > 0)
            ? (clone $ctBase)->orderByDesc('id')->limit(500)->pluck('total_close_time')->toArray()
            : [];

        // Waiting — split by who's turn it is. last_reply_by=customer tickets are genuinely
        // waiting on an agent; last_reply_by=agent tickets only reflect how long the customer
        // has been idle since our last reply. A single blended average over both populations
        // measures mostly customer silence and hides how many tickets actually need a reply.
        $waitOnAgentBase    = $scoped()->whereNotIn('status', ['closed'])->whereNotNull('waiting_since')->waitingOnly();
        $waitOnCustomerBase = $scoped()->whereNotIn('status', ['closed'])->whereNotNull('waiting_since')->where(function ($q) {
            $q->whereColumn('last_customer_response', '<', 'last_agent_response');
        });
        $waitingOnAgent    = self::computeWaitStats($waitOnAgentBase);
        $waitingOnCustomer = self::computeWaitStats($waitOnCustomerBase);

        $allAgents = Agent::where('person_type', 'agent')
            ->whereNotNull('first_name')
            ->where('first_name', '!=', '')
            ->select(['id', 'first_name', 'last_name'])
            ->get()
            ->keyBy('id');

        $agentWorkload = $scoped()->whereNotIn('status', ['closed'])
            ->whereNotNull('agent_id')
            ->selectRaw('agent_id, COUNT(*) as ticket_count')
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');

        $agentClosedInPeriod = $scoped()->where('resolved_at', '>=', $since)
            ->whereNotNull('agent_id')
            ->selectRaw('agent_id, COUNT(*) as closed_count')
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');

        $agentResponseTimes = $scoped()->where('first_response_time', '>', 0)
            ->where('created_at', '>=', $since)
            ->whereNotNull('agent_id')
            ->selectRaw('agent_id, AVG(first_response_time) as avg_response_time')
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');

        $agentResolutionTimes = $scoped()->where('resolved_at', '>=', $since)
            ->where('total_close_time', '>', 0)
            ->whereNotNull('agent_id')
            ->selectRaw('agent_id, AVG(total_close_time) as avg_close_time')
            ->groupBy('agent_id')
            ->get()
            ->keyBy('agent_id');

        $agentIds = $allAgents->keys()
            ->merge($agentWorkload->keys())
            ->merge($agentClosedInPeriod->keys())
            ->unique();

        $performance = [];
        foreach ($agentIds as $agentId) {
            $agentModel = $allAgents->get($agentId);
            if (!$agentModel) {
                continue;
            }

            $entry = [
                'agent_id'         => $agentId,
                'agent_name'       => MCPHelper::personName($agentModel),
                'open_tickets'     => (int) ($agentWorkload->get($agentId)->ticket_count ?? 0),
                'closed_in_period' => (int) ($agentClosedInPeriod->get($agentId)->closed_count ?? 0),
            ];

            $avgResponse = $agentResponseTimes->get($agentId);
            $entry['avg_first_response'] = $avgResponse
                ? self::formatDuration((float) $avgResponse->avg_response_time)
                : null;

            $avgResolution = $agentResolutionTimes->get($agentId);
            $entry['avg_resolution'] = $avgResolution
                ? self::formatDuration((float) $avgResolution->avg_close_time)
                : null;

            $performance[] = $entry;
        }

        usort($performance, fn($a, $b) => $b['open_tickets'] - $a['open_tickets']);

        $truncatedAgents = count($performance) > self::MAX_AGENTS;
        if ($truncatedAgents) {
            $performance = array_slice($performance, 0, self::MAX_AGENTS);
        }

        $created = (clone $periodTicketsQuery)->count();
        $closed  = (clone $closedInPeriodQuery)->count();

        return MCPHelper::envelope(
            "Support insights for {$period}: {$created} created, {$closed} closed",
            [
                'period' => $period,
                'volume' => ['created' => $created, 'closed' => $closed],
                'first_response_time' => self::computeTimeStatsFromAgg($rtAgg, $rtSample),
                'resolution_time'     => self::computeTimeStatsFromAgg($ctAgg, $ctSample),
                'waiting'             => [
                    'waiting_on_agent'    => $waitingOnAgent,
                    'waiting_on_customer' => $waitingOnCustomer,
                ],
                'agent_performance'          => $performance,
                'agent_performance_truncated' => $truncatedAgents,
            ]
        );
    }

    private static function computeTimeStatsFromAgg($agg, array $sample)
    {
        if (!$agg || (int) $agg->cnt === 0) {
            return ['count' => 0, 'average' => null, 'median' => null, 'max' => null, 'min' => null];
        }

        return [
            'count'   => (int) $agg->cnt,
            'average' => self::formatDuration((float) $agg->avg_val),
            'median'  => self::formatDuration(self::median($sample)),
            'max'     => self::formatDuration((float) $agg->max_val),
            'min'     => self::formatDuration((float) $agg->min_val),
        ];
    }

    /**
     * Aggregate + bounded-sample-for-median pattern for a waiting_since-based population,
     * mirroring computeTimeStatsFromAgg() but for the TIMESTAMPDIFF-against-now duration used
     * by the two waiting populations (waiting_on_agent / waiting_on_customer) instead of a
     * stored duration column.
     */
    private static function computeWaitStats($baseQuery)
    {
        $agg = (clone $baseQuery)
            ->selectRaw('COUNT(*) as cnt, AVG(TIMESTAMPDIFF(SECOND, waiting_since, UTC_TIMESTAMP())) as avg_val, MAX(TIMESTAMPDIFF(SECOND, waiting_since, UTC_TIMESTAMP())) as max_val')
            ->first();
        $sample = ($agg && (int) $agg->cnt > 0)
            ? (clone $baseQuery)->selectRaw('TIMESTAMPDIFF(SECOND, waiting_since, UTC_TIMESTAMP()) as wait_sec')->orderByDesc('id')->limit(500)->pluck('wait_sec')->toArray()
            : [];

        return [
            'count'   => $agg ? (int) $agg->cnt : 0,
            'average' => self::formatDuration($agg ? (float) $agg->avg_val : 0),
            'max'     => self::formatDuration($agg ? (float) $agg->max_val : 0),
            'median'  => self::formatDuration(self::median($sample)),
        ];
    }

    private static function median(array $values)
    {
        if (empty($values)) {
            return 0;
        }
        sort($values);
        $count = count($values);
        $mid   = (int) floor($count / 2);
        return ($count % 2 === 0)
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : $values[$mid];
    }

    private static function formatDuration($seconds)
    {
        return MCPHelper::formatDuration($seconds);
    }

    public static function bulkAction($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $ticketIds = array_map('intval', (array) ($params['ticket_ids'] ?? []));
        $action    = sanitize_text_field($params['action'] ?? '');

        if (empty($ticketIds) || !$action) {
            return MCPHelper::error('invalid_param', __('ticket_ids and action are required', 'fluent-support'), ['fields' => ['ticket_ids', 'action']]);
        }

        if (count($ticketIds) > self::MAX_BULK) {
            return MCPHelper::error('invalid_param', sprintf(__('ticket_ids may not exceed %d items per request. Split into multiple calls.', 'fluent-support'), self::MAX_BULK), ['fields' => ['ticket_ids'], 'limit' => self::MAX_BULK]);
        }

        $allowedActions = ['close', 'assign', 'tag'];
        if (!in_array($action, $allowedActions)) {
            return MCPHelper::error('invalid_param', sprintf(__('action must be one of: %s', 'fluent-support'), implode(', ', $allowedActions)), ['fields' => ['action']]);
        }

        $assignTarget = null;
        if ($action === 'assign') {
            // Null ticket: each ticket's mailbox restriction is checked per-item
            // in the loop below, since the batch can span multiple mailboxes.
            $assignTarget = MCPHelper::resolveAssignmentTarget($params['agent_id'] ?? 0, null, 'agent_id');
            if (is_wp_error($assignTarget)) {
                return $assignTarget;
            }
        }

        if ($action === 'tag') {
            $tagIds = array_map('intval', (array) ($params['tag_ids'] ?? []));
            if (empty($tagIds)) {
                return MCPHelper::error('invalid_param', __('tag_ids is required for the tag action', 'fluent-support'), ['fields' => ['tag_ids']]);
            }
        }

        $tickets       = Ticket::whereIn('id', $ticketIds)->get();
        $ticketService = new TicketService();
        $results       = [];
        $foundIds      = $tickets->pluck('id')->toArray();

        foreach ($ticketIds as $tid) {
            if (!in_array($tid, $foundIds)) {
                $results[] = ['id' => $tid, 'status' => 'not_found'];
            }
        }

        foreach ($tickets as $ticket) {
            if (TicketAccessGuard::assert($ticket)) {
                $results[] = ['id' => $ticket->id, 'status' => 'forbidden'];
                continue;
            }

            // Isolate each ticket: a failure on one (e.g. inside close(),
            // onAgentChange(), or applyTags()) must not abort the whole batch.
            try {
                switch ($action) {
                    case 'close':
                        if ($ticket->status === 'closed') {
                            $results[] = ['id' => $ticket->id, 'status' => 'already_closed'];
                        } else {
                            $ticketService->close($ticket, $agent);
                            $results[] = ['id' => $ticket->id, 'status' => 'closed'];
                        }
                        break;

                    case 'assign':
                        if (TicketAccessGuard::assertAssignableAgent($ticket, $assignTarget)) {
                            $results[] = ['id' => $ticket->id, 'status' => 'mailbox_restricted'];
                            break;
                        }
                        MCPHelper::applyAgentAssignment($ticket, $assignTarget, $agent);
                        $results[] = ['id' => $ticket->id, 'status' => 'assigned'];
                        break;

                    case 'tag':
                        $tagIds = array_map('intval', (array) ($params['tag_ids'] ?? []));
                        $ticket->applyTags($tagIds);
                        $results[] = ['id' => $ticket->id, 'status' => 'tagged'];
                        break;
                }
            } catch (\Exception $e) {
                $results[] = ['id' => $ticket->id, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $processed = count(array_filter($results, function ($r) {
            return !in_array($r['status'], ['not_found', 'forbidden', 'mailbox_restricted', 'error']);
        }));

        $total     = count($ticketIds);
        $verbMap   = ['close' => 'closed', 'assign' => 'assigned', 'tag' => 'tagged'];
        $verb      = $verbMap[$action] ?? $action;

        return MCPHelper::envelope(
            "{$processed} of {$total} ticket(s) {$verb}",
            ['results' => $results],
            ['processed' => $processed, 'total' => $total]
        );
    }

    public static function getMentions($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $notificationSettings = new \FluentSupport\App\Services\Notifications\NotificationSettings();
        if (!$notificationSettings->canUseNotificationTables(true)) {
            return MCPHelper::error('not_available', __('Internal notifications are not enabled. Enable them in Fluent Support Settings > Notifications.', 'fluent-support'), [
                'next_step' => 'Enable internal notifications in Fluent Support admin settings',
            ]);
        }

        ['page' => $page, 'per_page' => $perPage] = MCPHelper::pagination($params, 15);

        $status = sanitize_text_field($params['status'] ?? 'all');
        if (!in_array($status, ['all', 'unread', 'read'], true)) {
            $status = 'all';
        }

        $filters = [
            'category' => \FluentSupport\App\Services\Notifications\NotificationCategory::MENTIONS,
        ];

        if ($status !== 'all') {
            $filters['status'] = $status;
        }

        if (!empty($params['ticket_id'])) {
            $filters['ticket_id'] = (int) $params['ticket_id'];
        }

        $queryService = new \FluentSupport\App\Services\Notifications\NotificationQueryService();
        $query        = $queryService->getNotificationsForPerson($agent->id, $filters);

        if (!$query) {
            return MCPHelper::error('not_available', __('Internal notifications are not enabled.', 'fluent-support'));
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $unreadCount = (new \FluentSupport\App\Services\Notifications\NotificationQueryService())
            ->getUnreadCount($agent->id, ['category' => \FluentSupport\App\Services\Notifications\NotificationCategory::MENTIONS]);

        $mentions = [];
        foreach ($paginated->items() as $notification) {
            $payload = $notification->getPayloadAttribute($notification->getRawOriginal('payload') ?? null);
            if (!is_array($payload)) {
                $payload = [];
            }

            $readStatus = null;
            if ($notification->relationLoaded('recipients') && $notification->recipients->isNotEmpty()) {
                $recipient  = $notification->recipients->first();
                $readStatus = (bool) $recipient->is_read;
            }

            $actor      = $notification->relationLoaded('actor') ? $notification->actor : null;
            $ticket     = $notification->relationLoaded('ticket') ? $notification->ticket : null;
            $actorName  = $actor ? MCPHelper::personName($actor) : (__('Someone', 'fluent-support'));
            $ticketTitle = $ticket ? $ticket->title : ($payload['ticket_title'] ?? null);

            $summary = $ticketTitle
                ? sprintf(__('%1$s mentioned you in "%2$s"', 'fluent-support'), $actorName, $ticketTitle)
                : sprintf(__('%1$s mentioned you', 'fluent-support'), $actorName);

            $mention = [
                'id'              => $notification->id,
                'summary'         => $summary,
                'is_read'         => $readStatus,
                'ticket_id'       => $notification->ticket_id,
                'ticket_title'    => $ticketTitle,
                'conversation_id' => $notification->conversation_id,
                'mentioned_by'    => $actor ? MCPHelper::formatPersonSummary($actor) : null,
                'content_preview' => isset($payload['content_preview']) ? sanitize_text_field($payload['content_preview']) : null,
                'created_at'      => MCPHelper::toIso8601($notification->created_at),
            ];

            $mentions[] = $mention;
        }

        $total = $paginated->total();

        return MCPHelper::envelope(
            sprintf(
                _n('Found %d mention', 'Found %d mentions', $total, 'fluent-support'),
                $total
            ) . ($unreadCount ? " ({$unreadCount} unread)" : ''),
            ['mentions' => $mentions, 'unread_count' => $unreadCount],
            MCPHelper::pagingMeta($paginated)
        );
    }

    public static function listWorkflows($params)
    {
        if (!class_exists('\FluentSupportPro\App\Models\Workflow')) {
            return MCPHelper::error('not_available', __('Workflows require Fluent Support Pro', 'fluent-support'));
        }

        ['page' => $page, 'per_page' => $perPage] = MCPHelper::pagination($params);

        $query = \FluentSupportPro\App\Models\Workflow::query();

        $status = sanitize_text_field($params['status'] ?? 'published');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($params['trigger_type'])) {
            $query->where('trigger_type', sanitize_text_field($params['trigger_type']));
        }

        $paginated = $query->with([
            'actions' => fn($q) => $q->select(['id', 'workflow_id', 'title', 'action_name']),
        ])->orderBy('priority', 'ASC')->paginate($perPage, ['*'], 'page', $page);

        $triggerLabels = [
            'fluent_support/ticket_created'             => 'When a new ticket is created',
            'fluent_support/response_added_by_customer' => 'When a customer replies',
            'fluent_support/ticket_closed'              => 'When a ticket is closed',
        ];

        $total = $paginated->total();

        $workflows = array_map(function ($wf) use ($triggerLabels) {
            return [
                'id'            => $wf->id,
                'title'         => $wf->title,
                'status'        => $wf->status,
                'trigger_type'  => $wf->trigger_type,
                'trigger_key'   => sanitize_text_field($wf->trigger_key),
                'trigger_label' => $triggerLabels[$wf->trigger_key] ?? sanitize_text_field($wf->trigger_key),
                'actions'       => $wf->actions->map(fn($a) => [
                    'action' => $a->action_name,
                    'title'  => $a->title,
                ])->toArray(),
                'last_ran_at'   => MCPHelper::toIso8601($wf->last_ran_at),
            ];
        }, $paginated->items());

        return MCPHelper::envelope(
            sprintf(_n('Found %d workflow', 'Found %d workflows', $total, 'fluent-support'), $total),
            ['workflows' => $workflows],
            MCPHelper::pagingMeta($paginated)
        );
    }
}
