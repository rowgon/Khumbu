<?php

namespace FluentSupport\App\Modules\MCP\Tools;

use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\MCP\Helpers\MCPHelper;
use FluentSupport\App\Modules\PermissionManager;

class CustomerTools
{
    public static function searchCustomers($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $search = sanitize_text_field($params['search'] ?? '');

        ['page' => $page, 'per_page' => $perPage] = MCPHelper::pagination($params);

        $restricted = PermissionManager::getRestrictedMailboxIds();

        $query = Customer::select([
            'id', 'first_name', 'last_name', 'email', 'status', 'city', 'state', 'country', 'created_at',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Exclude customers who only have tickets in restricted mailboxes.
        if ($restricted) {
            $query->where(function ($q) use ($restricted) {
                $q->whereDoesntHave('tickets')
                  ->orWhereHas('tickets', function ($tq) use ($restricted) {
                      $tq->whereNotIn('mailbox_id', $restricted);
                  });
            });
        }

        $paginated = $query->orderBy('id', 'DESC')->paginate($perPage, ['*'], 'page', $page);

        $pageItems   = $paginated->items();
        $customerIds = array_map(fn($c) => $c->id, $pageItems);

        $ticketCounts = [];
        if ($customerIds) {
            $countQuery = Ticket::whereIn('customer_id', $customerIds);
            if ($restricted) {
                $countQuery->whereNotIn('mailbox_id', $restricted);
            }
            $rows = $countQuery
                ->selectRaw('customer_id, COUNT(*) as total_tickets, SUM(CASE WHEN status != \'closed\' THEN 1 ELSE 0 END) as open_tickets')
                ->groupBy('customer_id')
                ->get()
                ->keyBy('customer_id');

            foreach ($rows as $cid => $row) {
                $ticketCounts[$cid] = [
                    'total_tickets' => (int) $row->total_tickets,
                    'open_tickets'  => (int) $row->open_tickets,
                ];
            }
        }

        $customers = [];
        foreach ($pageItems as $customer) {
            $item                  = MCPHelper::formatCustomerForMCP($customer);
            $item['total_tickets'] = $ticketCounts[$customer->id]['total_tickets'] ?? 0;
            $item['open_tickets']  = $ticketCounts[$customer->id]['open_tickets'] ?? 0;
            $customers[] = $item;
        }

        $total   = $paginated->total();
        $summary = $search
            ? sprintf(_n("Found %d customer matching '%s'", "Found %d customers matching '%s'", $total, 'fluent-support'), $total, $search)
            : sprintf(_n('Found %d customer', 'Found %d customers', $total, 'fluent-support'), $total);

        return MCPHelper::envelope($summary, ['customers' => $customers], MCPHelper::pagingMeta($paginated));
    }

    public static function getCustomerTickets($params)
    {
        $customerId = (int) ($params['customer_id'] ?? 0);
        $email = sanitize_email($params['email'] ?? '');
        $name = sanitize_text_field($params['name'] ?? '');

        $customer = null;

        if ($customerId) {
            $customer = Customer::find($customerId);
        } elseif ($email) {
            $customer = Customer::where('email', $email)->first();
        } elseif ($name) {
            $customer = Customer::where(function ($q) use ($name) {
                $q->where('first_name', 'LIKE', '%' . $name . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $name . '%')
                  ->orWhere('email', 'LIKE', '%' . $name . '%');
            })->first();
        }

        if (!$customer) {
            return MCPHelper::error('not_found', __('Customer not found. Provide customer_id, email, or name.', 'fluent-support'), ['next_step' => 'Use search-customers to find the customer first']);
        }

        ['page' => $page, 'per_page' => $perPage] = MCPHelper::pagination($params);

        $query = Ticket::with([
            'agent' => function ($q) {
                $q->select(['id', 'first_name', 'last_name', 'email']);
            },
        ])->where('customer_id', $customer->id);

        if (!empty($params['status'])) {
            $statuses = \FluentSupport\App\Services\Helper::getTkStatusesByGroupName($params['status']);
            if ($statuses) {
                $query->whereIn('status', $statuses);
            }
        }

        do_action_ref_array('fluent_support/tickets_query_by_permission_ref', [&$query]);

        $restricted = PermissionManager::getRestrictedMailboxIds();
        if ($restricted) {
            $query->whereNotIn('mailbox_id', $restricted);
        }

        $paginated = $query->orderBy('id', 'DESC')->paginate($perPage, ['*'], 'page', $page);

        $tickets = [];
        foreach ($paginated->items() as $ticket) {
            $tickets[] = [
                'id'             => $ticket->id,
                'title'          => $ticket->title,
                'status'         => $ticket->status,
                'priority'       => MCPHelper::normalizePriority($ticket->priority),
                'response_count' => (int) $ticket->response_count,
                'created_at'     => MCPHelper::toIso8601($ticket->created_at),
                'agent'          => $ticket->agent ? MCPHelper::formatPersonSummary($ticket->agent) : null,
            ];
        }

        $total      = $paginated->total();
        $identifier = $customer->email ?: MCPHelper::personName($customer);
        $summary    = sprintf(_n('Found %d ticket for %s', 'Found %d tickets for %s', $total, 'fluent-support'), $total, $identifier);

        return MCPHelper::envelope(
            $summary,
            ['customer' => MCPHelper::formatCustomerForMCP($customer), 'tickets' => $tickets],
            MCPHelper::pagingMeta($paginated)
        );
    }
}
