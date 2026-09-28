<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Ticket;
use FluentSupport\Framework\Http\Request\Request;

class WidgetsController extends Controller
{
    public function __invoke(Request $request)
    {
        $filter = $request->get('filter', '');
        $filter = sanitize_text_field($filter);
        $filter = str_replace('fluent_support_', '', $filter);

        // Only allow alphanumeric, underscores, and hyphens in filter names
        if (!$filter || !preg_match('/^[a-zA-Z0-9_\-]+$/', $filter)) {
            return $this->sendError([
                'message' => __('Invalid filter name', 'fluent-support')
            ]);
        }

        $data = $request->get('data', []);

        if (!is_array($data)) {
            $data = [];
        }

        // Sanitize all scalar values in data
        $data = array_map(function ($value) {
            return is_scalar($value) ? sanitize_text_field($value) : $value;
        }, $data);

        if ($filter === 'ticket_sidebar') {
            $ticketId = intval($data['ticket_id'] ?? 0);
            if (!$ticketId) {
                return $this->sendError([
                    'message' => __('Invalid ticket ID', 'fluent-support')
                ]);
            }

            $ticket = Ticket::with('customer')->find($ticketId);
            if (!$ticket) {
                return $this->sendError([
                    'message' => __('Ticket not found', 'fluent-support')
                ]);
            }

            $this->ensureCanAccessTicket($ticket);

            $data['ticket'] = $ticket;
        }

        return $this->sendSuccess([
            'widgets' => apply_filters('fluent_support/widgets/' . $filter, [], $data)
        ]);
    }
}
