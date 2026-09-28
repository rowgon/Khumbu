<?php

namespace FluentSupport\App\Modules\MCP\Tools;

use FluentSupport\App\Models\Activity;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\MCP\Helpers\MCPHelper;
use FluentSupport\App\Modules\MCP\Support\CustomerMetaEnricher;
use FluentSupport\App\Modules\MCP\Support\TicketAccessGuard;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\ProfileInfoService;
use FluentSupport\App\Services\Tickets\ResponseService;
use FluentSupport\App\Services\Tickets\TicketService;

class TicketTools
{
    const MAX_RESPONSES = 50;

    public static function listTickets($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $query = Ticket::with([
            'customer' => function ($q) {
                $q->select(['id', 'first_name', 'last_name', 'email']);
            },
            'agent' => function ($q) {
                $q->select(['id', 'first_name', 'last_name', 'email']);
            },
            'product',
            'tags',
        ]);

        $filterError = self::applyFilters($query, $params);
        if (is_wp_error($filterError)) {
            return $filterError;
        }

        do_action_ref_array('fluent_support/tickets_query_by_permission_ref', [&$query]);

        $restrictedMailboxes = PermissionManager::getRestrictedMailboxIds();
        if ($restrictedMailboxes) {
            $query->whereNotIn('mailbox_id', $restrictedMailboxes);
        }

        // Allowed sort columns that are supported by keyset pagination. Low-cardinality
        // columns (status, priority, client_priority) rely on their plain single-column
        // index — id is always the tiebreaker, so a composite (col,id) index is only
        // worth the write overhead for the high-churn triage columns below.
        // For fresh installs: defined in CREATE TABLE statement (TicketsMigrator::migrate)
        // For upgrades: added by addMissingIndexes() (TicketsMigrator::alterTable)
        // Composite (col,id) indexes: (waiting_since,id), (updated_at,id), (response_count,id)
        $allowedSortColumns = ['id', 'created_at', 'updated_at', 'waiting_since', 'status', 'priority', 'client_priority', 'response_count'];
        $sortBy   = sanitize_text_field($params['sort_by'] ?? 'id');
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'id';
        }
        $sortType = Helper::sanitizeOrderValue($params['sort_type'] ?? $params['order'] ?? 'DESC');

        ['page' => $page, 'per_page' => $perPage] = MCPHelper::pagination($params);

        // Tiebreaker matches the primary sort direction (not a fixed DESC) so
        // rows with equal $sortBy values are still totally ordered — required
        // for the keyset cursor below to be a valid resume point, not just for
        // display determinism.
        $query->orderBy($sortBy, $sortType)->orderBy('id', $sortType);

        $cursor = sanitize_text_field($params['cursor'] ?? '');
        if ($cursor !== '') {
            $decoded = self::decodeListCursor($cursor);
            if (!$decoded || $decoded['sort_by'] !== $sortBy || $decoded['sort_type'] !== $sortType) {
                return MCPHelper::error('invalid_param', __('Invalid or expired cursor, or it was generated with a different sort_by/sort_type than this request', 'fluent-support'), ['fields' => ['cursor'], 'next_step' => 'Use the same sort_by/sort_type as the call that produced this cursor, or omit cursor to start a fresh sweep']);
            }

            self::applyCursorWhere($query, $sortBy, $sortType, $decoded);
        }

        // Keyset mode: fetch one extra row to detect has_more without a
        // separate COUNT — a mutating waiting_since makes "total" only ever
        // an approximation mid-sweep anyway, so it's computed once (not
        // re-derived from the keyset window) purely for a consistent
        // response shape with page-mode, matching what pagingMeta() returns.
        if ($cursor !== '') {
            $rows     = $query->limit($perPage + 1)->get();
            $hasMore  = $rows->count() > $perPage;
            $rows     = $rows->slice(0, $perPage)->values();
            $nextCursor = $hasMore && $rows->isNotEmpty()
                ? self::encodeListCursor($rows->last(), $sortBy, $sortType)
                : null;

            $summary = sprintf(_n('Found %d ticket', 'Found %d tickets', $rows->count(), 'fluent-support'), $rows->count());

            $customerMeta = CustomerMetaEnricher::resolve($rows, [
                'surface'  => 'ticket_list',
                'agent_id' => $agent->id,
            ]);

            return MCPHelper::envelope($summary, ['tickets' => MCPHelper::formatTicketList($rows, $customerMeta)], [
                'paging' => [
                    'per_page'    => $perPage,
                    'has_more'    => $hasMore,
                    'next_cursor' => $nextCursor,
                ],
            ]);
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $total   = $paginated->total();
        $summary = sprintf(_n('Found %d ticket', 'Found %d tickets', $total, 'fluent-support'), $total)
            . sprintf(__(' — page %d of %d', 'fluent-support'), $paginated->currentPage(), $paginated->lastPage());

        $items = $paginated->items();

        // Optional integration-provided per-customer meta line (one batch call, gated by fst_sensitive_data).
        $customerMeta = CustomerMetaEnricher::resolve($items, [
            'surface'  => 'ticket_list',
            'agent_id' => $agent->id,
        ]);

        $meta = MCPHelper::pagingMeta($paginated);
        // Offer a stable cursor from page 1's last row so a sweep can switch to
        // keyset pagination from here on instead of continuing with page/offset.
        $meta['paging']['next_cursor'] = !empty($items)
            ? self::encodeListCursor(end($items), $sortBy, $sortType)
            : null;

        return MCPHelper::envelope($summary, ['tickets' => MCPHelper::formatTicketList($items, $customerMeta)], $meta);
    }

    /**
     * Builds the keyset WHERE for "rows strictly after $decoded". NULL-aware:
     * MySQL sorts NULLs first in ASC and last in DESC by default, so a naive
     * `$sortBy > $v` / `< $v` would silently exclude NULL rows forever once
     * the cursor passes into non-null values (NULL > x and NULL < x are both
     * SQL UNKNOWN, never true). Each branch below matches that default
     * ordering so NULL rows are neither skipped nor duplicated.
     */
    private static function applyCursorWhere($query, $sortBy, $sortType, $decoded)
    {
        $id = $decoded['id'];
        $v  = $decoded['v'];

        if ($sortType === 'ASC') {
            if ($v === null) {
                // Still among the leading NULL rows, or past them entirely.
                $query->where(function ($q) use ($sortBy, $id) {
                    $q->where(function ($q2) use ($sortBy, $id) {
                        $q2->whereNull($sortBy)->where('id', '>', $id);
                    })->orWhereNotNull($sortBy);
                });
            } else {
                // Past the NULL rows (they all sort first in ASC) — plain tuple compare.
                $query->where(function ($q) use ($sortBy, $v, $id) {
                    $q->where($sortBy, '>', $v)
                      ->orWhere(function ($q2) use ($sortBy, $v, $id) {
                          $q2->where($sortBy, '=', $v)->where('id', '>', $id);
                      });
                });
            }
        } else {
            if ($v === null) {
                // Already among the trailing NULL rows (they sort last in DESC).
                $query->whereNull($sortBy)->where('id', '<', $id);
            } else {
                // Not yet into the NULL rows — include them once past all non-null values.
                $query->where(function ($q) use ($sortBy, $v, $id) {
                    $q->where($sortBy, '<', $v)
                      ->orWhere(function ($q2) use ($sortBy, $v, $id) {
                          $q2->where($sortBy, '=', $v)->where('id', '<', $id);
                      })
                      ->orWhereNull($sortBy);
                });
            }
        }
    }

    /**
     * Opaque continuation token for keyset pagination: the last row's id, its
     * value for the current sort column, and the sort_by/sort_type it was
     * generated with. The sort binding matters — without it, reusing a
     * cursor after changing sort_by/sort_type would compare the old value
     * against an unrelated column with no error. Not encrypted/signed — it
     * only encodes values the requester already saw in the prior response.
     */
    private static function encodeListCursor($lastRow, $sortBy, $sortType)
    {
        // getRawOriginal(), not the cast attribute — date columns like
        // waiting_since cast to a DateTime-like object, which would serialize
        // as a nested {date, timezone_type, timezone} blob and break the
        // plain scalar WHERE comparison in applyCursorWhere().
        $value = $lastRow->getRawOriginal($sortBy);
        return base64_encode(wp_json_encode([
            'id'        => (int) $lastRow->id,
            'v'         => $value,
            'sort_by'   => $sortBy,
            'sort_type' => $sortType,
        ]));
    }

    private static function decodeListCursor($cursor)
    {
        $decoded = json_decode(base64_decode($cursor, true), true);
        if (!is_array($decoded) || !isset($decoded['id'], $decoded['sort_by'], $decoded['sort_type']) || !array_key_exists('v', $decoded)) {
            return null;
        }
        return $decoded;
    }

    private static function applyFilters($query, $params)
    {
        $filters = [];

        if (!empty($params['status'])) {
            $filters['status_type'] = $params['status'];
        } else if (!empty($params['waiting_for_reply'])) {
            // A closed ticket can't be "waiting for an agent response" -
            // default to open tickets unless the caller explicitly asked for another status.
            $filters['status_type'] = 'open';
        }
        if (!empty($params['agent_id'])) {
            $filters['agent_id'] = (int) $params['agent_id'];
        }
        if (!empty($params['product_id'])) {
            $filters['product_id'] = (int) $params['product_id'];
        }
        if (!empty($params['mailbox_id'])) {
            $filters['mailbox_id'] = (int) $params['mailbox_id'];
        }
        if (!empty($params['priority'])) {
            $filters['priority'] = MCPHelper::normalizePriority($params['priority']);
        }
        if (!empty($params['client_priority'])) {
            $filters['client_priority'] = MCPHelper::normalizePriority($params['client_priority']);
        }
        if (!empty($params['tags'])) {
            $filters['ticket_tags'] = array_map('intval', (array) $params['tags']);
        }
        if (!empty($params['waiting_for_reply'])) {
            $filters['waiting_for_reply'] = 'yes';
        }

        if (!empty($params['customer_id'])) {
            $query->where('customer_id', (int) $params['customer_id']);
        }

        if (!empty($params['unassigned'])) {
            $query->whereNull('agent_id');
        }

        if (!empty($params['needs_first_response'])) {
            $query->where('response_count', 0)->whereNotIn('status', ['closed']);
        }

        $query->applyFilters($filters);

        foreach (['created_after' => '>=', 'created_before' => '<='] as $field => $op) {
            if (empty($params[$field])) {
                continue;
            }
            try {
                $d = new \DateTime(sanitize_text_field($params[$field]));
                $query->where('created_at', $op, $d->format('Y-m-d H:i:s'));
            } catch (\Exception $e) {
                return MCPHelper::error('invalid_param', sprintf(__("Invalid date format for '%s'", 'fluent-support'), $field), ['fields' => [$field], 'hint' => 'Use YYYY-MM-DD or ISO 8601']);
            }
        }

        if (!empty($params['search'])) {
            $search = sanitize_text_field($params['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%")
                  ->orWhere('id', '=', is_numeric($search) ? (int) $search : 0)
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('email', 'LIKE', "%{$search}%")
                         ->orWhere('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('responses', function ($rq) use ($search) {
                      $rq->where('content', 'LIKE', "%{$search}%");
                  });
            });
        }
    }

    public static function getTicket($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $ticketId = (int) ($params['ticket_id'] ?? 0);
        if (!$ticketId) {
            return MCPHelper::error('invalid_param', __('ticket_id is required', 'fluent-support'), ['fields' => ['ticket_id']]);
        }

        $ticket = Ticket::with(['customer', 'agent', 'product', 'mailbox', 'tags'])->find($ticketId);

        if (!$ticket) {
            return MCPHelper::error('not_found', __('Ticket not found', 'fluent-support'), ['next_step' => 'Use list-tickets to find valid ticket IDs']);
        }

        if ($err = TicketAccessGuard::assert($ticket)) {
            return $err;
        }

        $data = MCPHelper::formatTicketForMCP($ticket);

        if ($ticket->customer) {
            $customer   = $ticket->customer;
            $customerId = $customer->id;
            $data['customer']['total_tickets'] = Ticket::where('customer_id', $customerId)->count();
            $data['customer']['open_tickets']  = Ticket::where('customer_id', $customerId)
                ->whereNotIn('status', ['closed'])->count();
            $data['customer']['first_seen'] = MCPHelper::toIso8601($customer->created_at);

            $prevTickets = Ticket::where('customer_id', $customerId)
                ->where('id', '!=', $ticket->id)
                ->select(['id', 'title', 'status', 'priority', 'created_at'])
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get();

            if ($prevTickets->count()) {
                $data['previous_tickets'] = $prevTickets->map(function ($t) {
                    return [
                        'id'         => $t->id,
                        'title'      => $t->title,
                        'status'     => $t->status ?: 'new',
                        'priority'   => MCPHelper::normalizePriority($t->priority),
                        'created_at' => MCPHelper::toIso8601($t->created_at),
                    ];
                })->toArray();
            }

            $withIntegrations = ($params['with_integrations'] ?? true) !== false;
            if ($withIntegrations) {
                $extraWidgets = ProfileInfoService::getProfileExtraWidgets($customer);
                if ($extraWidgets) {
                    $data['integrations'] = MCPHelper::formatExtraWidgets($extraWidgets);
                }
            }

            $crmData = Helper::getFluentCrmContactData($customer);
            if ($crmData) {
                $data['crm'] = [
                    'name'   => $crmData['full_name'] ?? '',
                    'status' => $crmData['status'] ?? '',
                    'tags'   => !empty($crmData['tags']) ? $crmData['tags']->pluck('title')->toArray() : [],
                    'lists'  => !empty($crmData['lists']) ? $crmData['lists']->pluck('title')->toArray() : [],
                ];
            }
        }

        $customFields = $ticket->customData('admin', true);
        if ($customFields) {
            $data['custom_fields'] = $customFields;
        }

        $withResponses = ($params['with_responses'] ?? true) !== false;
        if ($withResponses) {
            $responsePage = max(1, (int) ($params['response_page'] ?? 1));
            $offset       = ($responsePage - 1) * self::MAX_RESPONSES;

            // Fetch one extra to detect if an older page exists (no separate COUNT).
            $rows    = \FluentSupport\App\Models\Conversation::where('ticket_id', $ticket->id)
                ->with('person')
                ->orderBy('id', 'desc')
                ->offset($offset)
                ->limit(self::MAX_RESPONSES + 1)
                ->get();
            $hasMore = $rows->count() > self::MAX_RESPONSES;
            if ($hasMore) {
                $rows = $rows->slice(0, self::MAX_RESPONSES);
            }

            $data['responses']      = MCPHelper::formatResponseThread($rows->reverse()->values());
            $data['responses_meta'] = [
                'page'     => $responsePage,
                'per_page' => self::MAX_RESPONSES,
                'has_more' => $hasMore,
            ];
        }

        $summary = "Ticket #{$ticket->id}: {$ticket->title} [{$data['status']}, {$data['priority']}]";

        return MCPHelper::envelope($summary, $data);
    }

    public static function createTicket($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $title   = sanitize_text_field($params['title'] ?? '');
        $format  = $params['content_format'] ?? 'markdown';
        $content = wp_kses_post(MCPHelper::processContent($params['content'] ?? '', $format));

        if (!$title || !$content) {
            return MCPHelper::error('invalid_param', __('title and content are required', 'fluent-support'), ['fields' => ['title', 'content']]);
        }

        $email = sanitize_email($params['customer_email'] ?? '');
        if (!$email || !is_email($email)) {
            return MCPHelper::error('invalid_param', __('A valid customer_email is required', 'fluent-support'), ['fields' => ['customer_email']]);
        }

        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            $customerData = [
                'email'      => $email,
                'first_name' => sanitize_text_field($params['customer_first_name'] ?? ''),
                'last_name'  => sanitize_text_field($params['customer_last_name'] ?? ''),
            ];

            $wpUser = get_user_by('email', $email);
            if ($wpUser) {
                $customerData['user_id'] = $wpUser->ID;
                if (!$customerData['first_name']) {
                    $customerData['first_name'] = $wpUser->first_name ?: $wpUser->display_name;
                }
                if (!$customerData['last_name']) {
                    $customerData['last_name'] = $wpUser->last_name;
                }
            }

            $customerData = array_filter($customerData);
            $customer     = Customer::create($customerData);
            do_action('fluent_support/customer_created', $customer);
        }

        $ticketData = [
            'title'           => $title,
            'content'         => $content,
            'customer_id'     => $customer->id,
            'source'          => 'mcp',
            'status'          => 'new',
            'priority'        => 'normal',
            // Logged on the customer's behalf ("created by agent"), NOT agent-initiated:
            // storeTicket() sets created_by to the acting agent and fires the
            // ticket_created_by_agent_email_to_customer notification. We deliberately do
            // not set 'agent_initiated' => 'yes' — that flow suppresses the created-by-agent
            // email and treats the content as the agent's opening reply, which is wrong for
            // a ticket logged via MCP on the customer's behalf.
        ];

        if (!empty($params['priority'])) {
            $ticketData['priority'] = MCPHelper::normalizePriority($params['priority']);
        }

        if (!empty($params['product_id'])) {
            $productId = (int) $params['product_id'];
            if (!\FluentSupport\App\Models\Product::find($productId)) {
                return MCPHelper::error('invalid_param', __('The specified product does not exist', 'fluent-support'), ['fields' => ['product_id'], 'next_step' => 'Use get-support-context to see available products and their IDs']);
            }
            $ticketData['product_id'] = $productId;
        }

        if (!empty($params['mailbox_id'])) {
            $mailboxId = (int) $params['mailbox_id'];
            if ($err = TicketAccessGuard::assertMailboxWritable($mailboxId)) {
                return $err;
            }
            if (!\FluentSupport\App\Models\MailBox::find($mailboxId)) {
                return MCPHelper::error('invalid_param', __('The specified mailbox does not exist', 'fluent-support'), ['fields' => ['mailbox_id'], 'next_step' => 'Use get-support-context to see available mailboxes and their IDs']);
            }
            $ticketData['mailbox_id'] = $mailboxId;
        }

        if (!empty($params['agent_id'])) {
            // Probe ticket carries the effective target mailbox so
            // resolveAssignmentTarget can enforce the assignee's mailbox
            // restriction at creation too. When mailbox_id is omitted,
            // storeTicket() falls back to the default mailbox — mirror that
            // here so the restriction is checked against the mailbox the
            // ticket will actually be persisted with.
            $effectiveMailboxId = (int) ($ticketData['mailbox_id'] ?? 0);
            if (!$effectiveMailboxId) {
                $defaultMailbox     = Helper::getDefaultMailBox();
                $effectiveMailboxId = $defaultMailbox ? (int) $defaultMailbox->id : 0;
            }

            $probeTicket = null;
            if ($effectiveMailboxId) {
                $probeTicket             = new Ticket();
                $probeTicket->mailbox_id = $effectiveMailboxId;
            }

            $agentRecord = MCPHelper::resolveAssignmentTarget($params['agent_id'], $probeTicket, 'agent_id');
            if (is_wp_error($agentRecord)) {
                return $agentRecord;
            }
            $ticketData['agent_id'] = $agentRecord->id;
        }

        // Opt-in: caller can turn this into an agent-initiated ticket (agent proactively
        // reaching out — billing follow-up, onboarding, etc.). When enabled, storeTicket()
        // suppresses the created-by-agent email and posts `content` as the agent's opening
        // reply to the customer. Default (off) logs the ticket on the customer's behalf.
        if (filter_var($params['agent_initiated'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $ticketData['agent_initiated'] = 'yes';
        }

        $ticket = (new TicketService())->storeTicket($ticketData, $customer);
        $ticket->load(['customer', 'agent', 'product', 'mailbox', 'tags']);

        $data = ['ticket' => MCPHelper::formatTicketForMCP($ticket)];

        if (
            !$ticket->agent_id &&
            !PermissionManager::currentUserCan('fst_manage_unassigned_tickets') &&
            !PermissionManager::currentUserCan('fst_manage_other_tickets')
        ) {
            $data['warning'] = __('Ticket created unassigned. Your permission level does not include access to unassigned tickets, so follow-up actions (reply, close, update) will be denied until the ticket is assigned to you by a manager.', 'fluent-support');
        }

        return MCPHelper::envelope(
            "Ticket #{$ticket->id} created: {$ticket->title}",
            $data
        );
    }

    public static function closeTicket($params)
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

        if ($ticket->status === 'closed') {
            $ticket->load(['customer', 'agent', 'product', 'mailbox', 'tags']);
            return MCPHelper::envelope(
                "Ticket #{$ticket->id} is already closed",
                ['ticket' => MCPHelper::formatTicketForMCP($ticket)]
            );
        }

        $format       = $params['content_format'] ?? 'markdown';
        $replyContent = wp_kses_post(MCPHelper::processContent($params['reply_content'] ?? '', $format));
        $internalNote = wp_kses_post(MCPHelper::processContent($params['internal_note'] ?? '', $format));

        // Reply + close must be atomic. Without a transaction, a failure in
        // close() after the reply was created would leave the reply visible on
        // a still-open ticket.
        (new Ticket())->getConnection()->transaction(function () use ($replyContent, $internalNote, $agent, $ticket) {
            if ($replyContent) {
                $data = [
                    'content'           => $replyContent,
                    'conversation_type' => 'response',
                    'source'            => 'mcp',
                ];
                (new ResponseService())->createResponse($data, $agent, $ticket);
            }

            (new TicketService())->close($ticket, $agent, $internalNote);
        });

        $ticket->load(['customer', 'agent', 'product', 'mailbox', 'tags']);

        $summary = $replyContent
            ? "Reply sent and ticket #{$ticket->id} closed"
            : "Ticket #{$ticket->id} closed";

        return MCPHelper::envelope($summary, ['ticket' => MCPHelper::formatTicketForMCP($ticket)]);
    }

    public static function reopenTicket($params)
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

        (new TicketService())->reopen($ticket, $agent);
        $ticket->load(['customer', 'agent', 'product', 'mailbox', 'tags']);

        return MCPHelper::envelope(
            "Ticket #{$ticket->id} reopened",
            ['ticket' => MCPHelper::formatTicketForMCP($ticket)]
        );
    }

    public static function updateTicket($params)
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

        $intFields = ['product_id', 'mailbox_id'];
        $updatable = ['title', 'priority', 'status', 'product_id', 'mailbox_id'];
        $changed   = false;

        foreach ($updatable as $field) {
            if (!isset($params[$field])) {
                continue;
            }

            if ($field === 'mailbox_id') {
                $mid = (int) $params['mailbox_id'];
                if ($err = TicketAccessGuard::assertMailboxWritable($mid)) {
                    return $err;
                }
                if (!\FluentSupport\App\Models\MailBox::find($mid)) {
                    return MCPHelper::error('invalid_param', __('The specified mailbox does not exist', 'fluent-support'), ['fields' => ['mailbox_id'], 'next_step' => 'Use get-support-context to see available mailboxes and their IDs']);
                }
                $ticket->mailbox_id = $mid;
                $changed = true;
                continue;
            }

            if ($field === 'product_id') {
                $pid = (int) $params['product_id'];
                if ($pid > 0 && !\FluentSupport\App\Models\Product::find($pid)) {
                    return MCPHelper::error('invalid_param', __('The specified product does not exist', 'fluent-support'), ['fields' => ['product_id'], 'next_step' => 'Use get-support-context to see available products and their IDs']);
                }
                $ticket->product_id = $pid ?: null;
                $changed = true;
                continue;
            }

            $value = in_array($field, $intFields, true)
                ? (int) $params[$field]
                : sanitize_text_field($params[$field]);

            if ($field === 'priority') {
                $value = MCPHelper::normalizePriority($value);
            }

            if ($field === 'status') {
                $allowedStatuses = ['new', 'active'];
                if (!in_array($value, $allowedStatuses, true)) {
                    return MCPHelper::error('invalid_param', sprintf(__("Invalid status '%s'", 'fluent-support'), $value), ['fields' => ['status'], 'allowed' => $allowedStatuses]);
                }
                if ($ticket->status === 'closed') {
                    return MCPHelper::error(
                        'ticket_closed',
                        __('Cannot change the status of a closed ticket via update-ticket.', 'fluent-support'),
                        ['next_step' => 'Use reopen-ticket to reopen the ticket first', 'retryable' => false]
                    );
                }
            }

            $ticket->{$field} = $value;
            $changed = true;
        }

        $assignTarget = null;
        if (isset($params['agent_id'])) {
            $assignTarget = MCPHelper::resolveAssignmentTarget($params['agent_id'], $ticket, 'agent_id');
            if (is_wp_error($assignTarget)) {
                return $assignTarget;
            }
        }

        if ($assignTarget) {
            // Persists agent_id together with any scalar field changes above and
            // fires the assignment side effects when the assignee changes.
            MCPHelper::applyAgentAssignment($ticket, $assignTarget, $agent);
        } elseif ($changed) {
            $ticket->save();
        }

        $ticket->load(['customer', 'agent', 'product', 'mailbox', 'tags']);

        return MCPHelper::envelope(
            "Ticket #{$ticket->id} updated",
            ['ticket' => MCPHelper::formatTicketForMCP($ticket)]
        );
    }

    public static function deleteTicket($params)
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

        $ticketTitle = $ticket->title;
        (new TicketService())->deleteTicket($ticket, $agent);

        return MCPHelper::envelope(
            "Ticket #{$ticketId} permanently deleted: {$ticketTitle}",
            ['deleted_title' => sanitize_text_field($ticketTitle)]
        );
    }

    public static function getTicketActivity($params)
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

        $activities = Activity::where('object_type', 'ticket')
            ->where('object_id', $ticketId)
            ->with('person')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $count = $activities->count();

        return MCPHelper::envelope(
            "Found {$count} activity entries for ticket #{$ticketId}",
            [
                'ticket_id'  => $ticketId,
                'activities' => $activities->map(function ($a) {
                    return [
                        'id'          => $a->id,
                        'event'       => $a->event_type,
                        'description' => MCPHelper::htmlToText($a->description),
                        'person'      => MCPHelper::personName($a->person),
                        'person_type' => $a->person_type,
                        'created_at'  => MCPHelper::toIso8601($a->created_at),
                    ];
                })->toArray(),
            ],
            ['total' => $count]
        );
    }

    public static function mergeTickets($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        if (!class_exists('\FluentSupportPro\App\Services\ProTicketService')) {
            return MCPHelper::error('not_available', __('Merge tickets requires Fluent Support Pro', 'fluent-support'));
        }

        $targetId = (int) ($params['target_ticket_id'] ?? 0);
        $mergeIds = array_map('intval', (array) ($params['merge_ticket_ids'] ?? []));

        if (!$targetId || empty($mergeIds)) {
            return MCPHelper::error('invalid_param', __('target_ticket_id and merge_ticket_ids are required', 'fluent-support'), ['fields' => ['target_ticket_id', 'merge_ticket_ids']]);
        }

        $mergeIds = array_values(array_unique($mergeIds));

        if (in_array($targetId, $mergeIds, true)) {
            return MCPHelper::error(
                'invalid_param',
                __('target_ticket_id must not also appear in merge_ticket_ids', 'fluent-support'),
                ['fields' => ['target_ticket_id', 'merge_ticket_ids']]
            );
        }

        $target = Ticket::find($targetId);
        if (!$target) {
            return MCPHelper::error('not_found', __('Target ticket not found', 'fluent-support'), ['fields' => ['target_ticket_id'], 'next_step' => 'Use list-tickets to find valid ticket IDs']);
        }

        if ($err = TicketAccessGuard::assert($target)) {
            return $err;
        }

        $inaccessible   = [];
        $mergeTicketMap = Ticket::whereIn('id', $mergeIds)->get()->keyBy('id');
        foreach ($mergeIds as $mergeId) {
            $source = $mergeTicketMap->get($mergeId);
            if (!$source || TicketAccessGuard::assert($source)) {
                $inaccessible[] = $mergeId;
            }
        }

        if ($inaccessible) {
            return MCPHelper::error(
                'forbidden',
                sprintf(__('You do not have access to the following ticket(s): %s', 'fluent-support'), implode(', ', $inaccessible))
            );
        }

        $proService = new \FluentSupportPro\App\Services\ProTicketService();
        $result     = $proService->mergeCustomerTickets($mergeIds, $targetId);

        if ($result === null) {
            return MCPHelper::error('merge_failed', __('Ticket merge failed or was only partially completed. Some tickets may be in an inconsistent state.', 'fluent-support'));
        }

        $target->load(['customer', 'agent', 'product', 'mailbox', 'tags']);

        $mergeCount = count($mergeIds);

        return MCPHelper::envelope(
            "{$mergeCount} ticket(s) merged into #{$targetId}",
            ['ticket' => MCPHelper::formatTicketForMCP($target)]
        );
    }
}
