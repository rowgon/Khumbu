<?php

namespace FluentSupport\App\Modules\MCP\Tools;

use FluentSupport\App\Models\SavedReply;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\MCP\Helpers\MCPHelper;
use FluentSupport\App\Modules\MCP\Support\TicketAccessGuard;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Tickets\ResponseService;

class ResponseTools
{
    public static function listSavedReplies($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $query = SavedReply::where('created_by', $agent->id);

        if (!empty($params['search'])) {
            $search = sanitize_text_field($params['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($params['product_id'])) {
            $query->where('product_id', (int) $params['product_id']);
        }

        $replies = $query->orderBy('title', 'ASC')->get();

        $count = $replies->count();

        return MCPHelper::envelope(
            sprintf(_n('Found %d saved reply', 'Found %d saved replies', $count, 'fluent-support'), $count),
            ['saved_replies' => $replies->map(function ($reply) {
                return self::formatSavedReply($reply);
            })->toArray()],
            ['total' => $count]
        );
    }

    public static function createSavedReply($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $title       = sanitize_text_field($params['title'] ?? '');
        $hasContent  = isset($params['content']) && $params['content'] !== '';
        if ($title === '' || !$hasContent) {
            return MCPHelper::error('invalid_param', __('title and content are required', 'fluent-support'), ['fields' => ['title', 'content']]);
        }

        $productId = null;
        if (array_key_exists('product_id', $params) && $params['product_id'] !== null && $params['product_id'] !== '') {
            $productId = (int) $params['product_id'];
            if (!\FluentSupport\App\Models\Product::find($productId)) {
                return MCPHelper::error('invalid_param', __('The specified product does not exist', 'fluent-support'), ['fields' => ['product_id'], 'next_step' => 'Use get-support-context to see available products and their IDs']);
            }
        }

        $format  = $params['content_format'] ?? 'markdown';
        $content = wp_kses_post(MCPHelper::processContent($params['content'], $format));

        $reply = SavedReply::create([
            'created_by' => $agent->id,
            'title'      => $title,
            'content'    => $content,
            'product_id' => $productId,
        ]);

        return MCPHelper::envelope(
            sprintf(__('Saved reply "%s" created', 'fluent-support'), $title),
            ['saved_reply' => self::formatSavedReply($reply)]
        );
    }

    public static function updateSavedReply($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $id = (int) ($params['id'] ?? 0);
        if (!$id) {
            return MCPHelper::error('invalid_param', __('id is required', 'fluent-support'), ['fields' => ['id']]);
        }

        $reply = SavedReply::find($id);
        if (!$reply) {
            return MCPHelper::error('not_found', __('Saved reply not found', 'fluent-support'), ['next_step' => 'Use list-saved-replies to find valid IDs']);
        }

        if ((int) $reply->created_by !== (int) $agent->id && !PermissionManager::currentUserCan('fst_manage_settings')) {
            return MCPHelper::error('forbidden', __('You do not have permission to update this saved reply', 'fluent-support'), ['retryable' => false]);
        }

        if (array_key_exists('title', $params)) {
            $title = sanitize_text_field($params['title']);
            if ($title === '') {
                return MCPHelper::error('invalid_param', __('title cannot be empty', 'fluent-support'), ['fields' => ['title']]);
            }
            $reply->title = $title;
        }

        if (array_key_exists('content', $params)) {
            if ($params['content'] === '' || $params['content'] === null) {
                return MCPHelper::error('invalid_param', __('content cannot be empty', 'fluent-support'), ['fields' => ['content']]);
            }
            $format        = $params['content_format'] ?? 'markdown';
            $reply->content = wp_kses_post(MCPHelper::processContent($params['content'], $format));
        }

        if (array_key_exists('product_id', $params)) {
            if ($params['product_id'] === null || $params['product_id'] === '') {
                $reply->product_id = null;
            } else {
                $productId = (int) $params['product_id'];
                if (!\FluentSupport\App\Models\Product::find($productId)) {
                    return MCPHelper::error('invalid_param', __('The specified product does not exist', 'fluent-support'), ['fields' => ['product_id'], 'next_step' => 'Use get-support-context to see available products and their IDs']);
                }
                $reply->product_id = $productId;
            }
        }

        $reply->save();

        return MCPHelper::envelope(
            sprintf(__('Saved reply "%s" updated', 'fluent-support'), $reply->title),
            ['saved_reply' => self::formatSavedReply($reply)]
        );
    }

    public static function deleteSavedReply($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $id = (int) ($params['id'] ?? 0);
        if (!$id) {
            return MCPHelper::error('invalid_param', __('id is required', 'fluent-support'), ['fields' => ['id']]);
        }

        $reply = SavedReply::find($id);
        if (!$reply) {
            return MCPHelper::error('not_found', __('Saved reply not found', 'fluent-support'), ['next_step' => 'Use list-saved-replies to find valid IDs']);
        }

        if ((int) $reply->created_by !== (int) $agent->id && !PermissionManager::currentUserCan('fst_manage_settings')) {
            return MCPHelper::error('forbidden', __('You do not have permission to delete this saved reply', 'fluent-support'), ['retryable' => false]);
        }

        $title = $reply->title;
        $reply->delete();

        return MCPHelper::envelope(sprintf(__('Saved reply "%s" deleted', 'fluent-support'), $title), ['id' => $id]);
    }

    private static function formatSavedReply($reply)
    {
        return [
            'id'         => $reply->id,
            'title'      => $reply->title,
            'content'    => MCPHelper::htmlToText($reply->content),
            'product_id' => $reply->product_id,
        ];
    }

    public static function replyToTicket($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $ticketId = (int) ($params['ticket_id'] ?? 0);
        if (!$ticketId) {
            return MCPHelper::error('invalid_param', __('ticket_id is required', 'fluent-support'), ['fields' => ['ticket_id']]);
        }

        $content = '';

        if (!empty($params['saved_reply_id'])) {
            $savedReply = SavedReply::where('id', (int) $params['saved_reply_id'])
                ->where('created_by', $agent->id)
                ->first();
            if (!$savedReply) {
                return MCPHelper::error('not_found', __('Saved reply not found', 'fluent-support'), ['next_step' => 'Use list-saved-replies to find valid IDs']);
            }
            $content = wp_kses_post($savedReply->content);
        }

        if (!empty($params['content'])) {
            $format  = $params['content_format'] ?? 'markdown';
            $content = wp_kses_post(MCPHelper::processContent($params['content'], $format));
        }

        if (!$content) {
            return MCPHelper::error('invalid_param', __('content or saved_reply_id is required', 'fluent-support'), ['fields' => ['content', 'saved_reply_id']]);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return MCPHelper::error('not_found', __('Ticket not found', 'fluent-support'), ['next_step' => 'Use list-tickets to find valid ticket IDs']);
        }

        if ($err = TicketAccessGuard::assert($ticket)) {
            return $err;
        }

        if ($ticket->status === 'closed') {
            return MCPHelper::error(
                'ticket_closed',
                __('Cannot reply to a closed ticket. Reopen it first, then reply.', 'fluent-support'),
                ['next_step' => 'Call reopen-ticket to reopen this ticket before replying', 'retryable' => false]
            );
        }

        // Resolve assignment intent so assign + reply happen in one call.
        // Explicit assignee_id requires fst_assign_agents (resolveAssignmentTarget).
        // Otherwise, replying to an unassigned ticket takes ownership (self),
        // which needs no extra permission. A ticket already assigned to someone
        // else is never silently reassigned.
        $assignTarget = null;
        if (array_key_exists('assignee_id', $params) && $params['assignee_id'] !== null && $params['assignee_id'] !== '') {
            $assignTarget = MCPHelper::resolveAssignmentTarget($params['assignee_id'], $ticket, 'assignee_id');
            if (is_wp_error($assignTarget)) {
                return $assignTarget;
            }
        } elseif (empty($ticket->agent_id)) {
            $assignTarget = $agent; // taking ownership — no fst_assign_agents needed
        }

        $data = [
            'content'           => $content,
            'conversation_type' => 'response',
            'source'            => 'mcp',
        ];

        if (!empty($params['close_ticket'])) {
            $data['close_ticket'] = 'yes';
        }

        // Assign + reply (+ optional close) must be atomic: a failure in the
        // reply rolls back the assignment too. The assignment notification side
        // effects (email/webhooks) are deferred until AFTER the transaction
        // commits — firing them inside the transaction would leak an
        // "assigned to you" email even when the reply fails and the assignment
        // is rolled back.
        $result          = null;
        $assigned        = false;
        $previousAgentId = $ticket->agent_id;
        try {
            (new Ticket())->getConnection()->transaction(function () use (&$result, &$assigned, $assignTarget, $ticket, $agent, $data) {
                if ($assignTarget) {
                    $assigned = MCPHelper::applyAgentAssignment($ticket, $assignTarget, $agent, true);
                }

                $result = (new ResponseService())->createResponse($data, $agent, $ticket);

                if (!$result) {
                    throw new \Exception('reply_failed'); // roll back the assignment
                }
            });
        } catch (\Throwable $e) {
            return MCPHelper::error('failed', __('Failed to create response', 'fluent-support'), ['retryable' => true]);
        }

        // Reply + assignment committed — now fire the assignment side effects.
        if ($assigned) {
            $ticket->load('agent');
            MCPHelper::fireAgentAssignmentSideEffects($ticket, $agent, $previousAgentId);
        }

        $ticketStatus = $result['ticket']->status;

        $assignNote = '';
        if ($assigned) {
            $assignNote = ((int) $assignTarget->id === (int) $agent->id)
                ? __(' and assigned to you', 'fluent-support')
                : sprintf(__(' and assigned to %s', 'fluent-support'), MCPHelper::personName($assignTarget));
        }

        $summary = $ticketStatus === 'closed'
            ? sprintf(__('Reply sent and ticket #%d closed', 'fluent-support'), $ticketId) . $assignNote
            : sprintf(__('Reply added to ticket #%d', 'fluent-support'), $ticketId) . $assignNote;

        $payload = [
            'response' => [
                'id'         => $result['response']->id,
                'content'    => MCPHelper::htmlToText($result['response']->content),
                'type'       => $result['response']->conversation_type,
                'created_at' => MCPHelper::toIso8601($result['response']->created_at),
            ],
            'ticket_status' => $ticketStatus,
        ];

        if ($assigned) {
            $payload['assigned_to'] = [
                'id'   => (int) $assignTarget->id,
                'name' => MCPHelper::personName($assignTarget),
            ];
        }

        return MCPHelper::envelope($summary, $payload);
    }

    public static function addInternalNote($params)
    {
        $agent = MCPHelper::resolveAgent();
        if (!$agent) {
            return MCPHelper::error('unauthorized', __('No agent found for current user', 'fluent-support'));
        }

        $ticketId = (int) ($params['ticket_id'] ?? 0);
        if (!$ticketId) {
            return MCPHelper::error('invalid_param', __('ticket_id is required', 'fluent-support'), ['fields' => ['ticket_id']]);
        }

        $format  = $params['content_format'] ?? 'markdown';
        $content = wp_kses_post(MCPHelper::processContent($params['content'] ?? '', $format));
        if (!$content) {
            return MCPHelper::error('invalid_param', __('content is required', 'fluent-support'), ['fields' => ['content']]);
        }

        $ticket = Ticket::find($ticketId);
        if (!$ticket) {
            return MCPHelper::error('not_found', __('Ticket not found', 'fluent-support'), ['next_step' => 'Use list-tickets to find valid ticket IDs']);
        }

        if ($err = TicketAccessGuard::assert($ticket)) {
            return $err;
        }

        $data = [
            'content'           => $content,
            'conversation_type' => 'internal_info',
            'source'            => 'mcp',
        ];

        $result = (new ResponseService())->createResponse($data, $agent, $ticket, true);

        if (!$result) {
            return MCPHelper::error('failed', __('Failed to create internal note', 'fluent-support'), ['retryable' => true]);
        }

        return MCPHelper::envelope(
            "Internal note added to ticket #{$ticketId}",
            ['note' => [
                'id'         => $result['response']->id,
                'content'    => MCPHelper::htmlToText($result['response']->content),
                'created_at' => MCPHelper::toIso8601($result['response']->created_at),
            ]]
        );
    }
}
