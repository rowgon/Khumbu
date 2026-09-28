<?php

namespace FluentSupport\App\Modules\MCP;

use FluentSupport\App\Modules\MCP\Support\AbilityGuard;
use FluentSupport\App\Modules\MCP\Tools\TicketTools;
use FluentSupport\App\Modules\MCP\Tools\ResponseTools;
use FluentSupport\App\Modules\MCP\Tools\ManagementTools;
use FluentSupport\App\Modules\MCP\Tools\CustomerTools;

class AbilitiesRegistrar
{
    private static $definitions = null;

    public static function getDefinitions(): array
    {
        if (self::$definitions !== null) {
            return self::$definitions;
        }

        self::$definitions = [
            'fluent-support/list-tickets' => [
                'label'       => __('List Support Tickets', 'fluent-support'),
                'description' => __('List and filter support tickets. Returns ticket summaries with content preview, customer, agent, product, and tags inline — no need to call get-ticket for each. Each row carries triage signals so you can sweep a backlog page without opening tickets: last_reply_by ("agent" | "customer" | null) and response_count. last_reply_by="customer" means the customer spoke last, so the ticket is awaiting an agent reply (this is the per-ticket value behind the waiting_for_reply filter); "agent" means we replied and the ball is in the customer\'s court. The original ticket message counts as the customer speaking, so a brand-new ticket with response_count=0 still resolves to last_reply_by="customer" (correctly — it is awaiting an agent) rather than null; null only occurs on legacy-imported tickets with no reply history at all. On open tickets this is your "is it waiting on us?" signal; on closed tickets it just reflects who spoke last. response_count is the number of replies made after ticket creation (the original message and internal notes are not counted, so response_count=0 does not imply last_reply_by is null). An optional customer_summary line may be present when an integration provides one (e.g. plan/usage info) — treat its absence as normal. For triage: use sort_by=waiting_since, order=ASC, waiting_for_reply=true. For a specific customer\'s tickets, prefer get-customer-tickets instead. Customer name and email are included for all agents with fst_view_tickets — agents need customer context to work their queue. For a multi-page sweep where you act on tickets as you go (replying/closing shifts waiting_since), use meta.paging.next_cursor from each response as the next call\'s cursor instead of incrementing page — this is immune to rows shifting position mid-sweep; plain page/per_page is fine for a one-off fetch.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'status'               => ['type' => 'string', 'enum' => ['open', 'closed', 'new', 'active', 'all'], 'description' => 'Filter by status group'],
                        'agent_id'             => ['type' => 'integer', 'description' => 'Filter by assigned agent ID'],
                        'product_id'           => ['type' => 'integer', 'description' => 'Filter by product ID'],
                        'mailbox_id'           => ['type' => 'integer', 'description' => 'Filter by mailbox ID'],
                        'priority'             => ['type' => 'string', 'enum' => ['low', 'normal', 'medium', 'high', 'critical'], 'description' => 'Filter by the internal (agent-set) priority. Aliases: low=normal, high=critical'],
                        'client_priority'      => ['type' => 'string', 'enum' => ['low', 'normal', 'medium', 'high', 'critical'], 'description' => 'Filter by the customer-flagged priority (e.g. set via a submission form) — distinct from priority, which is agent-set. Same aliases: low=normal, high=critical'],
                        'tags'                 => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Filter by tag IDs'],
                        'waiting_for_reply'    => ['type' => 'boolean', 'description' => 'Only show tickets where the customer is waiting for an agent response (last reply was from customer). Implies status=open unless status is explicitly set, since a closed ticket cannot be awaiting a reply.'],
                        'unassigned'           => ['type' => 'boolean', 'description' => 'Only show tickets with no agent assigned'],
                        'needs_first_response' => ['type' => 'boolean', 'description' => 'Only show open tickets that have never had a reply (response_count=0). Combine with unassigned=true for a new-and-untouched queue view.'],
                        'customer_id'          => ['type' => 'integer', 'description' => 'Filter by customer ID'],
                        'search'               => ['type' => 'string', 'description' => 'Full-text search across ticket title, content, conversation messages, and customer name/email'],
                        'created_after'        => ['type' => 'string', 'description' => 'Only tickets created on or after this date (YYYY-MM-DD or ISO 8601). A bare date is interpreted as midnight in the site\'s configured timezone (Settings > General), not UTC; an explicit UTC offset in the value is not converted before comparison, so omit it to match site time.'],
                        'created_before'       => ['type' => 'string', 'description' => 'Only tickets created on or before this date (YYYY-MM-DD or ISO 8601). A bare date is interpreted as midnight in the site\'s configured timezone (Settings > General), not UTC; an explicit UTC offset in the value is not converted before comparison, so omit it to match site time.'],
                        'page'                 => ['type' => 'integer', 'default' => 1, 'description' => 'Page number'],
                        'per_page'             => ['type' => 'integer', 'default' => 15, 'description' => 'Results per page (max 100)'],
                        'sort_by'              => ['type' => 'string', 'default' => 'id', 'enum' => ['id', 'created_at', 'updated_at', 'waiting_since', 'status', 'priority', 'client_priority', 'response_count'], 'description' => 'Sort column'],
                        'sort_type'            => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'default' => 'DESC', 'description' => 'Sort direction. Alias: order'],
                        'order'                => ['type' => 'string', 'enum' => ['ASC', 'DESC'], 'description' => 'Alias for sort_type'],
                        'cursor'               => ['type' => 'string', 'description' => 'Opaque continuation token from a previous response\'s meta.paging.next_cursor. When set, returns rows strictly after that position using the same sort_by/sort_type — stable even if earlier rows\' waiting_since changed. Overrides page when both are set.'],
                    ],
                ],
                'execute_callback' => [TicketTools::class, 'listTickets'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/get-ticket' => [
                'label'       => __('Get Ticket Details', 'fluent-support'),
                'description' => __('Get full ticket details with conversation thread, customer history (total/open tickets, first seen), previous tickets, custom fields, purchase history (WooCommerce/FluentCart), and CRM data (FluentCRM tags/lists). The thread is paginated: 50 responses per page, most recent first. Check responses_meta.has_more and increment response_page to fetch older messages. Customer name and email are included for all agents with fst_view_tickets — agents need customer context to work their queue.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id'         => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'with_responses'    => ['type' => 'boolean', 'default' => true, 'description' => 'Include conversation thread (default: true). Set false to get only ticket metadata.'],
                        'response_page'     => ['type' => 'integer', 'default' => 1, 'description' => 'Page of conversation thread. Page 1 = most recent 50 responses. Increment to fetch older messages when responses_meta.has_more is true.'],
                        'with_integrations' => ['type' => 'boolean', 'default' => true, 'description' => 'Include third-party integration data (e.g. purchase history from WooCommerce/FluentCart). Default: true. Set false to skip when you only need the ticket/thread.'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [TicketTools::class, 'getTicket'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/create-ticket' => [
                'label'       => __('Create Support Ticket', 'fluent-support'),
                'description' => __('Create a new support ticket on behalf of a customer. Requires title, content, and customer_email. Automatically creates the customer if the email is new. By default the ticket is logged on the customer\'s behalf ("created by agent") — the customer gets a notification that a ticket was opened for them. Set agent_initiated=true only when the agent is proactively reaching out to start a conversation (e.g. billing follow-up, onboarding, account review); then the content is sent to the customer as the agent\'s first reply instead. Use get-support-context first to get valid product_id, mailbox_id, and agent_id values.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'title'               => ['type' => 'string', 'description' => 'Ticket title/subject'],
                        'content'             => ['type' => 'string', 'description' => 'Ticket message content. Format controlled by content_format (default: markdown).'],
                        'content_format'      => ['type' => 'string', 'enum' => ['markdown', 'html', 'text'], 'default' => 'markdown', 'description' => 'Format of the content field. markdown (default): Markdown converted to HTML. html: passed through as-is. text: plain text wrapped in paragraphs.'],
                        'customer_email'      => ['type' => 'string', 'format' => 'email', 'description' => 'Customer email address'],
                        'customer_first_name' => ['type' => 'string', 'description' => 'Customer first name (for new customers)'],
                        'customer_last_name'  => ['type' => 'string', 'description' => 'Customer last name (for new customers)'],
                        'priority'            => ['type' => 'string', 'enum' => ['low', 'normal', 'medium', 'high', 'critical'], 'description' => 'Ticket priority. Aliases: low=normal, high=critical'],
                        'product_id'          => ['type' => 'integer', 'description' => 'Product ID'],
                        'mailbox_id'          => ['type' => 'integer', 'description' => 'Mailbox ID'],
                        'agent_id'            => ['type' => 'integer', 'description' => 'Assign to agent ID'],
                        'agent_initiated'     => ['type' => 'boolean', 'description' => 'Optional, default false. Set true ONLY when the agent is proactively starting the conversation (the content is then sent to the customer as the agent\'s first reply, and the "ticket created by agent" email is skipped). Leave false/unset to log a ticket on the customer\'s behalf.'],
                    ],
                    'required' => ['title', 'content', 'customer_email'],
                ],
                'execute_callback' => [TicketTools::class, 'createTicket'],
            ],

            'fluent-support/reply-to-ticket' => [
                'label'       => __('Reply to Ticket', 'fluent-support'),
                'description' => __('Send a customer-visible reply on a ticket. If the ticket is unassigned it is automatically assigned to you; pass assignee_id to assign someone else in the same call. Set close_ticket=true to close after replying. Supports saved replies via saved_reply_id (use list-saved-replies to browse). If you just need to close without a reply, use close-ticket instead.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id'      => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'content'        => ['type' => 'string', 'description' => 'Response message content (visible to customer). Format controlled by content_format (default: markdown). Not required if saved_reply_id is provided.'],
                        'content_format' => ['type' => 'string', 'enum' => ['markdown', 'html', 'text'], 'default' => 'markdown', 'description' => 'Format of the content field. markdown (default): Markdown converted to HTML. html: passed through as-is. text: plain text wrapped in paragraphs.'],
                        'saved_reply_id' => ['type' => 'integer', 'description' => 'Use a saved reply by ID (use list-saved-replies to find IDs). Content overrides saved reply if both provided.'],
                        'assignee_id'    => ['type' => 'integer', 'description' => 'Optional. Assign the ticket to this agent as part of the reply (requires fst_assign_agents; use get-support-context for agent IDs). If omitted and the ticket is unassigned, it is auto-assigned to you.'],
                        'close_ticket'   => ['type' => 'boolean', 'default' => false, 'description' => 'Close the ticket after replying'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [ResponseTools::class, 'replyToTicket'],
            ],

            'fluent-support/close-ticket' => [
                'label'       => __('Close Ticket', 'fluent-support'),
                'description' => __('Close a ticket. Optionally send a final customer-visible reply and/or an internal note in the same call. This is the simplest way to close — use it instead of reply-to-ticket when closing is the primary intent.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id'      => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'reply_content'  => ['type' => 'string', 'description' => 'Optional final reply visible to the customer (sent before closing). Format controlled by content_format (default: markdown).'],
                        'internal_note'  => ['type' => 'string', 'description' => 'Optional internal note for the closure (visible to agents only). Format controlled by content_format (default: markdown).'],
                        'content_format' => ['type' => 'string', 'enum' => ['markdown', 'html', 'text'], 'default' => 'markdown', 'description' => 'Format of reply_content and internal_note. markdown (default): Markdown converted to HTML. html: passed through as-is. text: plain text wrapped in paragraphs.'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [TicketTools::class, 'closeTicket'],
            ],

            'fluent-support/reopen-ticket' => [
                'label'       => __('Reopen Ticket', 'fluent-support'),
                'description' => __('Reopen a closed ticket. Sets status back to active and clears the resolved date. Use when a customer follows up on a closed issue.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id' => ['type' => 'integer', 'description' => 'The ticket ID'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [TicketTools::class, 'reopenTicket'],
                'annotations'      => ['idempotent' => true],
            ],

            'fluent-support/update-ticket' => [
                'label'       => __('Update Ticket Properties', 'fluent-support'),
                'description' => __('Update ticket properties like title, priority, status, product, mailbox, or assigned agent. Can change multiple fields at once. Use get-support-context to look up valid IDs. For just closing or reopening, use close-ticket or reopen-ticket instead.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id'  => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'title'      => ['type' => 'string', 'description' => 'New ticket title'],
                        'priority'   => ['type' => 'string', 'enum' => ['low', 'normal', 'medium', 'high', 'critical'], 'description' => 'New priority. Aliases: low=normal, high=critical'],
                        'status'     => ['type' => 'string', 'enum' => ['new', 'active'], 'description' => 'New status'],
                        'product_id' => ['type' => 'integer', 'description' => 'New product ID (use get-support-context to find IDs)'],
                        'mailbox_id' => ['type' => 'integer', 'description' => 'New mailbox ID (use get-support-context to find IDs)'],
                        'agent_id'   => ['type' => 'integer', 'description' => 'Assign to agent ID (use get-support-context to find IDs, requires fst_assign_agents permission)'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [TicketTools::class, 'updateTicket'],
            ],

            'fluent-support/delete-ticket' => [
                'label'       => __('Delete Ticket', 'fluent-support'),
                'description' => __('Permanently delete a support ticket and all its responses, attachments, and metadata. This action cannot be undone.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id' => ['type' => 'integer', 'description' => 'The ticket ID'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [TicketTools::class, 'deleteTicket'],
                'annotations'      => ['destructive' => true],
            ],

            'fluent-support/get-ticket-activity' => [
                'label'       => __('Get Ticket Activity Log', 'fluent-support'),
                'description' => __('Get the activity timeline for a ticket — who did what and when. Shows ticket creation, responses, status changes, agent assignments, and closures. Use to understand ticket history without reading the full conversation.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id' => ['type' => 'integer', 'description' => 'The ticket ID'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [TicketTools::class, 'getTicketActivity'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/merge-tickets' => [
                'label'       => __('Merge Tickets', 'fluent-support'),
                'description' => __('Merge duplicate tickets into one. All conversations and attachments from the merged tickets are moved into the target ticket. The merged tickets are deleted. Requires Fluent Support Pro.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'target_ticket_id' => ['type' => 'integer', 'description' => 'The ticket to merge into (this one survives)'],
                        'merge_ticket_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Ticket IDs to merge into the target (these get deleted)'],
                    ],
                    'required' => ['target_ticket_id', 'merge_ticket_ids'],
                ],
                'execute_callback' => [TicketTools::class, 'mergeTickets'],
                'annotations'      => ['destructive' => true],
            ],

            'fluent-support/add-internal-note' => [
                'label'       => __('Add Internal Note', 'fluent-support'),
                'description' => __('Add an internal note to a ticket. Requires ticket_id and content. Internal notes are only visible to agents, not to the customer.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id'      => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'content'        => ['type' => 'string', 'description' => 'Note content. Format controlled by content_format (default: markdown).'],
                        'content_format' => ['type' => 'string', 'enum' => ['markdown', 'html', 'text'], 'default' => 'markdown', 'description' => 'Format of the content field. markdown (default): Markdown converted to HTML. html: passed through as-is. text: plain text wrapped in paragraphs.'],
                    ],
                    'required' => ['ticket_id', 'content'],
                ],
                'execute_callback' => [ResponseTools::class, 'addInternalNote'],
            ],

            'fluent-support/assign-ticket' => [
                'label'       => __('Assign Ticket to Agent', 'fluent-support'),
                'description' => __('Assign a ticket to an agent. Use get-support-context to look up agent IDs. For assigning multiple tickets at once, use bulk-action with action=assign instead.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id' => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'agent_id'  => ['type' => 'integer', 'description' => 'The agent ID to assign'],
                    ],
                    'required' => ['ticket_id', 'agent_id'],
                ],
                'execute_callback' => [ManagementTools::class, 'assignTicket'],
            ],

            'fluent-support/tag-ticket' => [
                'label'       => __('Add or Remove Ticket Tags', 'fluent-support'),
                'description' => __('Add or remove tags on a ticket. Use get-support-context to look up tag IDs, or pass add_tag_names to create-and-apply new tags in one call — a tag whose title already matches is reused, not duplicated. For tagging multiple tickets at once, use bulk-action with action=tag instead.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_id'      => ['type' => 'integer', 'description' => 'The ticket ID'],
                        'add_tag_ids'    => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Existing tag IDs to add'],
                        'add_tag_names'  => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 20, 'description' => 'Tag titles to add (max 20 per request). A tag with a matching title is reused; otherwise it is created first, then applied.'],
                        'remove_tag_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Tag IDs to remove'],
                    ],
                    'required' => ['ticket_id'],
                ],
                'execute_callback' => [ManagementTools::class, 'tagTicket'],
            ],

            'fluent-support/create-tag' => [
                'label'       => __('Create Ticket Tag', 'fluent-support'),
                'description' => __('Create a new ticket tag for categorization and reporting, without attaching it to a ticket. If a tag with the same title already exists, that existing tag is returned instead of creating a duplicate. To create and apply a tag to a ticket in one call, pass add_tag_names to tag-ticket instead.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'title'       => ['type' => 'string', 'description' => 'Tag title'],
                        'description' => ['type' => 'string', 'description' => 'Optional tag description'],
                    ],
                    'required' => ['title'],
                ],
                'execute_callback' => [ManagementTools::class, 'createTag'],
                'annotations'      => ['idempotent' => true],
            ],

            'fluent-support/get-customer-tickets' => [
                'label'       => __('Get Customer Tickets', 'fluent-support'),
                'description' => __('Get a customer\'s tickets by email, name, or ID — no need to search for the customer first. Email is the most reliable. Best tool when someone asks "show me tickets from [customer]".', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'email'       => ['type' => 'string', 'format' => 'email', 'description' => 'Customer email address (recommended — exact match)'],
                        'name'        => ['type' => 'string', 'description' => 'Customer name (partial match on first/last name)'],
                        'customer_id' => ['type' => 'integer', 'description' => 'Customer ID (if already known)'],
                        'status'      => ['type' => 'string', 'enum' => ['open', 'closed', 'new', 'active', 'all'], 'description' => 'Filter by status group'],
                        'page'        => ['type' => 'integer', 'default' => 1],
                        'per_page'    => ['type' => 'integer', 'default' => 15],
                    ],
                ],
                'execute_callback' => [CustomerTools::class, 'getCustomerTickets'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/get-support-context' => [
                'label'       => __('Get Support Context', 'fluent-support'),
                'description' => __('START HERE — call this first in any session. Returns: your agent identity, your open ticket queue, tickets needing attention (unassigned/longest-waiting/critical/SLA breaches), all reference data (agents/products/mailboxes/tags with IDs), ticket stats, valid priority/status values, and AI guidelines. One call gives you full situational awareness. my_queue is split into awaiting_your_reply (customer spoke last — actionable, ranked oldest-waiting-first) and awaiting_customer (you replied last — informational only); each has a _total count and a _truncated flag since only the first page is returned (10 rows for awaiting_your_reply, 5 for awaiting_customer). Each attention-list row includes last_reply_by ("agent" | "customer" | null) and response_count (same meaning as in list-tickets: last_reply_by="customer" means awaiting an agent reply; response_count excludes internal notes) — triage your queue from this single call, starting with my_queue.awaiting_your_reply; do not call get-ticket per ticket unless you need the conversation body.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ],
                'execute_callback' => [ManagementTools::class, 'getSupportContext'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/list-saved-replies' => [
                'label'       => __('List Saved Replies', 'fluent-support'),
                'description' => __('List your saved/canned replies. Only returns replies you created. Optionally filter by search text or product_id. Use the returned ID with reply-to-ticket\'s saved_reply_id param. Content may contain placeholders like {{customer.first_name}} or {{agent.first_name}}, expanded automatically when the reply is sent.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'search'     => ['type' => 'string', 'description' => 'Search in title and content'],
                        'product_id' => ['type' => 'integer', 'description' => 'Filter by product ID'],
                    ],
                ],
                'execute_callback' => [ResponseTools::class, 'listSavedReplies'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/create-saved-reply' => [
                'label'       => __('Create Saved Reply', 'fluent-support'),
                'description' => __('Create a new saved/canned reply for reuse across tickets. Use when you notice yourself sending the same answer repeatedly. Content supports placeholders expanded automatically at send time: {{customer.first_name}}, {{customer.last_name}}, {{agent.first_name}}, {{agent.last_name}}, {{ticket.title}}. Use the dot form exactly as shown — {{customer.first_name}}, not {{customer_first_name}} — unrecognized placeholders are sent to the customer as literal text.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'title'          => ['type' => 'string', 'description' => 'Short title to identify this saved reply'],
                        'content'        => ['type' => 'string', 'description' => 'Reply content. Format controlled by content_format (default: markdown). May include {{customer.first_name}}-style placeholders.'],
                        'content_format' => ['type' => 'string', 'enum' => ['markdown', 'html', 'text'], 'default' => 'markdown', 'description' => 'Format of the content field. markdown (default): Markdown converted to HTML. html: passed through as-is. text: plain text wrapped in paragraphs.'],
                        'product_id'     => ['type' => 'integer', 'description' => 'Optional. Scope this reply to a specific product (use get-support-context to find IDs).'],
                    ],
                    'required' => ['title', 'content'],
                ],
                'execute_callback' => [ResponseTools::class, 'createSavedReply'],
            ],

            'fluent-support/update-saved-reply' => [
                'label'       => __('Update Saved Reply', 'fluent-support'),
                'description' => __('Update an existing saved reply\'s title, content, or product scope. Only the reply\'s creator (or an agent with settings-management permission) can update it.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'             => ['type' => 'integer', 'description' => 'The saved reply ID (use list-saved-replies to find it)'],
                        'title'          => ['type' => 'string', 'description' => 'New title'],
                        'content'        => ['type' => 'string', 'description' => 'New content. Format controlled by content_format (default: markdown).'],
                        'content_format' => ['type' => 'string', 'enum' => ['markdown', 'html', 'text'], 'default' => 'markdown', 'description' => 'Format of the content field, if provided.'],
                        'product_id'     => ['type' => 'integer', 'description' => 'New product scope'],
                    ],
                    'required' => ['id'],
                ],
                'execute_callback' => [ResponseTools::class, 'updateSavedReply'],
            ],

            'fluent-support/delete-saved-reply' => [
                'label'       => __('Delete Saved Reply', 'fluent-support'),
                'description' => __('Permanently delete a saved reply. Only the reply\'s creator (or an agent with settings-management permission) can delete it.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'The saved reply ID (use list-saved-replies to find it)'],
                    ],
                    'required' => ['id'],
                ],
                'execute_callback' => [ResponseTools::class, 'deleteSavedReply'],
                'annotations'      => ['destructive' => true],
            ],

            'fluent-support/get-support-insights' => [
                'label'       => __('Get Support Insights', 'fluent-support'),
                'description' => __('Get performance analytics: first-response times, resolution times, waiting durations, per-agent performance (open tickets, closed count, avg response/resolution time), and volume trends. Use for reporting and team performance reviews. Returns human-readable durations like "2h 30m". waiting is split into waiting_on_agent (last_reply_by=customer — genuinely needs a reply) and waiting_on_customer (last_reply_by=agent — customer has gone idle since our reply); a blended average across both would mostly measure customer silence, not agent responsiveness. For basic ticket counts, use get-support-context instead.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['24h', '7d', '30d', '90d'], 'default' => '7d', 'description' => 'Time period for volume and response time stats'],
                    ],
                ],
                'execute_callback' => [ManagementTools::class, 'getSupportInsights'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/search-customers' => [
                'label'       => __('Search Customers', 'fluent-support'),
                'description' => __('Search for customers by name or email. Returns customer profiles with location and account age. Use get-customer-tickets if you already know which customer and want their tickets. Use this when you need to find or identify a customer first.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'search'   => ['type' => 'string', 'description' => 'Search by name or email (partial match)'],
                        'page'     => ['type' => 'integer', 'default' => 1],
                        'per_page' => ['type' => 'integer', 'default' => 15, 'description' => 'Results per page (max 100)'],
                    ],
                ],
                'execute_callback' => [CustomerTools::class, 'searchCustomers'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/get-mentions' => [
                'label'       => __('Get Agent Mentions', 'fluent-support'),
                'description' => __('Get tickets where you have been @mentioned in internal notes or replies. Returns mention notifications with the ticket context, who mentioned you, and a content preview. Filter by read/unread status or scope to a specific ticket. Requires internal notifications to be enabled in settings.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'status'    => ['type' => 'string', 'enum' => ['all', 'unread', 'read'], 'default' => 'all', 'description' => 'Filter by read status'],
                        'ticket_id' => ['type' => 'integer', 'description' => 'Scope mentions to a specific ticket ID'],
                        'page'      => ['type' => 'integer', 'default' => 1, 'description' => 'Page number'],
                        'per_page'  => ['type' => 'integer', 'default' => 15, 'description' => 'Results per page (max 100)'],
                    ],
                ],
                'execute_callback' => [ManagementTools::class, 'getMentions'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/list-workflows' => [
                'label'       => __('List Workflows', 'fluent-support'),
                'description' => __('List automation workflows — see what automatic actions are configured (auto-assign, auto-tag, auto-reply, etc.) and their triggers. Helps you understand what the system handles automatically so you don\'t duplicate work. Paginated; use page/per_page to navigate. Requires Fluent Support Pro.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'status'       => ['type' => 'string', 'enum' => ['published', 'draft', 'all'], 'default' => 'published', 'description' => 'Filter by workflow status'],
                        'trigger_type' => ['type' => 'string', 'enum' => ['automatic', 'manual'], 'description' => 'Filter by trigger type'],
                        'page'         => ['type' => 'integer', 'default' => 1, 'description' => 'Page number (1-based)'],
                        'per_page'     => ['type' => 'integer', 'default' => 20, 'description' => 'Results per page (max 50)'],
                    ],
                ],
                'execute_callback' => [ManagementTools::class, 'listWorkflows'],
                'annotations'      => ['readonly' => true],
            ],

            'fluent-support/bulk-action' => [
                'label'       => __('Bulk Ticket Action', 'fluent-support'),
                'description' => __('Act on multiple tickets at once — close, assign, or tag in bulk. More efficient than calling individual tools in a loop. Returns per-ticket results showing what succeeded/failed. Maximum 50 ticket IDs per call; split larger sets into multiple requests.', 'fluent-support'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'ticket_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 50, 'description' => 'Array of ticket IDs (max 50 per request)'],
                        'action'     => ['type' => 'string', 'enum' => ['close', 'assign', 'tag'], 'description' => 'Action to perform'],
                        'agent_id'   => ['type' => 'integer', 'description' => 'Agent ID (required for assign action)'],
                        'tag_ids'    => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Tag IDs (required for tag action)'],
                    ],
                    'required' => ['ticket_ids', 'action'],
                ],
                'execute_callback' => [ManagementTools::class, 'bulkAction'],
                'annotations'      => ['destructive' => true],
            ],
        ];

        return self::$definitions;
    }

    public static function register()
    {
        foreach (self::getDefinitions() as $name => $definition) {
            $args = [
                'label'               => $definition['label'],
                'description'         => $definition['description'],
                'category'            => 'fluent-support',
                'execute_callback'    => self::wrapExecuteCallback($name, $definition['execute_callback']),
                'permission_callback' => AbilityGuard::callbackFor($name),
                'meta'                => [
                    'show_in_rest' => true,
                    'mcp'          => ['public' => true],
                ],
            ];

            if (!empty($definition['input_schema'])) {
                $args['input_schema'] = $definition['input_schema'];
            }

            if (!empty($definition['annotations'])) {
                $args['meta']['annotations'] = $definition['annotations'];
            }

            wp_register_ability($name, $args);
        }
    }

    /**
     * Wrap an execute_callback so that:
     * 1. Capability checks produce structured JSON (not the adapter's plain
     *    "Permission denied" string that fires when permission_callback returns false).
     * 2. Uncaught exceptions become structured WP_Error responses.
     */
    private static function wrapExecuteCallback($abilityName, $callback)
    {
        return function ($params) use ($abilityName, $callback) {
            if (!AbilityGuard::check($abilityName)) {
                return \FluentSupport\App\Modules\MCP\Helpers\MCPHelper::error(
                    'forbidden',
                    __('You do not have permission to perform this action.', 'fluent-support'),
                    ['retryable' => false]
                );
            }

            try {
                return call_user_func($callback, $params);
            } catch (\Throwable $e) {
                do_action('fluent_support/mcp_tool_exception', [
                    'exception' => $e,
                    'tool'      => $abilityName,
                    'params'    => $params,
                ]);

                return \FluentSupport\App\Modules\MCP\Helpers\MCPHelper::error(
                    'internal_error',
                    __('An unexpected error occurred. Please try again or contact support.', 'fluent-support'),
                    ['retryable' => true]
                );
            }
        };
    }
}
