<?php

namespace FluentSupport\App\Modules\MCP\Helpers;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\MCP\Support\TicketAccessGuard;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Parser\Parsedown;
use FluentSupport\App\Services\Tickets\TicketService;

class MCPHelper
{
    /** Default per_page ceiling. Individual tools may raise their own limit up to HARD_MAX_PER_PAGE. */
    const MAX_PER_PAGE = 100;

    /** Absolute ceiling no tool can exceed, however large a per_page it requests. */
    const HARD_MAX_PER_PAGE = 200;

    /** Maximum chars kept in a content preview before truncation. */
    const PREVIEW_CHARS = 150;

    public static function resolveAgent()
    {
        $userId = get_current_user_id();
        if (!$userId) {
            return null;
        }

        return Helper::getAgentByUserId($userId);
    }

    public static function formatTicketForMCP($ticket)
    {
        $data = [
            'id'              => $ticket->id,
            'title'           => $ticket->title,
            'status'          => $ticket->status ?: 'new',
            'priority'        => self::normalizePriority($ticket->priority),
            'client_priority' => $ticket->client_priority ?: 'normal',
            'source'          => $ticket->source,
            'response_count'  => (int) $ticket->response_count,
            'created_at'      => self::toIso8601($ticket->created_at),
            'updated_at'      => self::toIso8601($ticket->updated_at),
            'waiting_since'   => self::toIso8601($ticket->waiting_since),
            'resolved_at'     => self::toIso8601($ticket->resolved_at),
        ];

        if ($ticket->relationLoaded('customer') && $ticket->customer) {
            $data['customer'] = self::formatPersonSummary($ticket->customer);
        }

        if ($ticket->relationLoaded('agent') && $ticket->agent) {
            $data['agent'] = self::formatPersonSummary($ticket->agent);
        }

        if ($ticket->relationLoaded('product') && $ticket->product) {
            $data['product'] = [
                'id'    => $ticket->product->id,
                'title' => $ticket->product->title,
            ];
        }

        if ($ticket->relationLoaded('mailbox') && $ticket->mailbox) {
            $data['mailbox'] = [
                'id'   => $ticket->mailbox->id,
                'name' => $ticket->mailbox->name,
            ];
        }

        if ($ticket->relationLoaded('tags')) {
            $data['tags'] = $ticket->tags->map(function ($tag) {
                return ['id' => $tag->id, 'title' => $tag->title];
            })->toArray();
        }

        $data['content'] = self::htmlToText($ticket->content);

        return $data;
    }

    public static function formatTicketList($paginated, $customerMeta = [])
    {
        // Accepts either a WPFluent paginator (page/per_page mode) or a plain
        // collection/array (keyset cursor mode, which fetches rows directly).
        $items = is_object($paginated) && method_exists($paginated, 'items') ? $paginated->items() : $paginated;

        if (!is_iterable($items)) {
            $type = is_object($items) ? get_class($items) : gettype($items);
            throw new \Exception('MCPHelper::formatTicketList() expects a paginator, Collection, or array of tickets, got ' . $type);
        }

        $tickets = [];
        foreach ($items as $ticket) {
            $item = [
                'id'              => $ticket->id,
                'title'           => $ticket->title,
                'preview'         => self::preview($ticket->content),
                'status'          => $ticket->status ?: 'new',
                'priority'        => self::normalizePriority($ticket->priority),
                'client_priority' => $ticket->client_priority ?: 'normal',
                'response_count'  => (int) $ticket->response_count,
                'last_reply_by'   => $ticket->last_reply_by,
                'created_at'      => self::toIso8601($ticket->created_at),
                'waiting_since'   => self::toIso8601($ticket->waiting_since),
            ];

            if ($ticket->relationLoaded('customer') && $ticket->customer) {
                $item['customer'] = self::formatPersonSummary($ticket->customer);
            }

            if ($ticket->relationLoaded('agent') && $ticket->agent) {
                $item['agent'] = self::formatPersonSummary($ticket->agent);
            }

            if ($ticket->relationLoaded('product') && $ticket->product) {
                $item['product'] = $ticket->product->title;
            }

            if ($ticket->relationLoaded('tags')) {
                $item['tags'] = $ticket->tags->pluck('title')->toArray();
            }

            // Optional integration-provided customer one-liner (gated upstream by fst_sensitive_data).
            if ($ticket->customer_id && isset($customerMeta[$ticket->customer_id])) {
                $item['customer_summary'] = $customerMeta[$ticket->customer_id];
            }

            $tickets[] = $item;
        }

        return $tickets;
    }

    /**
     * Wrap a tool response in a consistent envelope.
     *
     * Every tool response has:
     *   summary — one-line the agent can quote ("12 tickets found, page 1 of 3")
     *   data    — the actual payload
     *   meta    — schema_version, generated_at, plus any caller-supplied keys (e.g. paging)
     */
    public static function envelope($summary, $data, $meta = [])
    {
        return [
            'summary' => $summary,
            'data'    => $data,
            'meta'    => array_merge(['schema_version' => 1, 'generated_at' => gmdate('c')], $meta),
        ];
    }

    /**
     * Extract and clamp page/per_page from $params.
     * Returns ['page' => int, 'per_page' => int].
     */
    public static function pagination($params, $default = 15, $max = null)
    {
        $max = min($max ?? self::MAX_PER_PAGE, self::HARD_MAX_PER_PAGE);
        return [
            'page'     => max((int) ($params['page'] ?? 1), 1),
            'per_page' => min(max((int) ($params['per_page'] ?? $default), 1), $max),
        ];
    }

    /**
     * Build the paging meta block from a WPFluent paginator.
     * Merge this into the $meta arg of envelope().
     */
    public static function pagingMeta($paginator)
    {
        return [
            'paging' => [
                'current'  => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total'    => $paginator->total(),
                'pages'    => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }

    public static function formatResponseThread($responses)
    {
        $thread = [];
        foreach ($responses as $response) {
            $entry = [
                'id'        => $response->id,
                'type'      => $response->conversation_type,
                'content'   => self::htmlToText($response->content),
                'source'    => $response->source,
                'created_at' => self::toIso8601($response->created_at),
            ];

            if ($response->relationLoaded('person') && $response->person) {
                $entry['person'] = [
                    'name' => self::personName($response->person),
                    'type' => $response->person->person_type,
                ];
            }

            $thread[] = $entry;
        }

        return $thread;
    }

    public static function formatCustomerForMCP($customer)
    {
        return [
            'id'         => $customer->id,
            'first_name' => $customer->first_name,
            'last_name'  => $customer->last_name,
            'email'      => $customer->email,
            'status'     => $customer->status,
            'city'       => $customer->city,
            'state'      => $customer->state,
            'country'    => $customer->country,
            'created_at' => self::toIso8601($customer->created_at),
        ];
    }

    public static function personName($model)
    {
        if (!$model) {
            return null;
        }
        $name = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));
        return $name !== '' ? $name : null;
    }

    public static function formatPersonSummary($person)
    {
        return [
            'id'    => $person->id,
            'name'  => self::personName($person),
            'email' => $person->email,
        ];
    }

    public static function normalizePriority($priority)
    {
        if (!$priority || $priority === '') {
            return 'normal';
        }

        $aliases = [
            'low'  => 'normal',
            'high' => 'critical',
        ];

        $normalized = strtolower(trim($priority));
        $normalized = $aliases[$normalized] ?? $normalized;

        $valid = ['normal', 'medium', 'critical'];
        return in_array($normalized, $valid, true) ? $normalized : 'normal';
    }

    public static function toIso8601($value)
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        try {
            if (is_object($value) && isset($value->date)) {
                // WPFluent sometimes returns a DateTimeImmutable-style object.
                $str = (string) $value->date;
                if (self::isZeroDate($str)) {
                    return null;
                }
                $dt = new \DateTime($str, new \DateTimeZone($value->timezone ?? 'UTC'));
                if ((int) $dt->format('Y') < 1) {
                    return null;
                }
                return $dt->format('c');
            }

            if (is_string($value)) {
                if (self::isZeroDate($value)) {
                    return null;
                }
                $dt = new \DateTime($value);
                if ((int) $dt->format('Y') < 1) {
                    return null;
                }
                return $dt->format('c');
            }
        } catch (\Exception $e) {
            // Malformed date string — return null rather than throwing.
        }

        return null;
    }

    /** True for MySQL zero-dates that PHP's DateTime constructor would misparse. */
    private static function isZeroDate($str)
    {
        return $str === '' || strpos($str, '0000-00-00') === 0;
    }

    public static function formatExtraWidgets($widgets)
    {
        $formatted = [];

        foreach ($widgets as $key => $widget) {
            if (!empty($widget['orders'])) {
                $orders = [];
                foreach ($widget['orders'] as $order) {
                    $item = [
                        'order_id' => $order['id'] ?? $order['order_id'] ?? null,
                        'status'   => $order['status'] ?? '',
                        'total'    => $order['total'] ?? $order['amount'] ?? '',
                        'date'     => $order['date'] ?? $order['date_created'] ?? '',
                    ];
                    $orders[] = array_filter($item);
                }
                $formatted[$key] = [
                    'title'  => $widget['title'] ?? $key,
                    'orders' => $orders,
                ];
            } elseif (!empty($widget['products'])) {
                $products = [];
                foreach ($widget['products'] as $product) {
                    $item = [
                        'name'   => $product['title'] ?? $product['name'] ?? '',
                        'status' => $product['status'] ?? '',
                        'price'  => $product['price'] ?? '',
                    ];
                    $products[] = array_filter($item);
                }
                $entry = [
                    'title'    => $widget['title'] ?? $key,
                    'products' => $products,
                ];
                if (!empty($widget['summary'])) {
                    $entry['summary'] = $widget['summary'];
                }
                $formatted[$key] = $entry;
            } else {
                $skipKeys = ['title', 'header', 'render', 'content_type'];
                $data     = array_diff_key($widget, array_flip($skipKeys));

                if (isset($data['body_html'])) {
                    // Raw dashboard HTML (often with an inline <style> block) is
                    // token-expensive and repeated on every fetch — plain-text it,
                    // keeping links (e.g. "view order") instead of dropping them.
                    // Don't clobber a summary the integration already curated.
                    if (empty($data['summary'])) {
                        $html             = $data['body_html'];
                        $data['summary'] = self::htmlToTextWithLinks($html);
                    }
                    unset($data['body_html']);
                }

                $formatted[$key] = [
                    'title' => $widget['title'] ?? $key,
                    'data'  => $data,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Truncate plain-text content to PREVIEW_CHARS with an ellipsis.
     *
     * @param string $html Raw HTML or plain text.
     * @return string
     */
    public static function preview($html, $chars = self::PREVIEW_CHARS)
    {
        $text = self::htmlToText($html);

        if (mb_strlen($text) <= $chars) {
            return $text;
        }

        return mb_substr($text, 0, $chars) . '...';
    }

    // Returns unsanitized HTML — callers must run through wp_kses_post.
    public static function processContent($content, $format = 'markdown')
    {
        if ($content === '' || $content === null) {
            return '';
        }

        switch ($format) {
            case 'html':
                return $content;
            case 'text':
                return wpautop(esc_html($content));
            default:
                return self::markdownToHtml($content);
        }
    }

    private static function markdownToHtml($content)
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false);

        return $parsedown->text($content);
    }

    public static function htmlToText($html)
    {
        if (!$html) {
            return '';
        }

        $text = wp_strip_all_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    // Like htmlToText(), but keeps <a href> targets instead of dropping them —
    // integration widgets (order/license links) lose meaning without the URL.
    // Parses via DOMDocument (same approach as Emogrifier::createRawXmlDocument())
    // rather than regex, so malformed/nested markup degrades gracefully.
    public static function htmlToTextWithLinks($html)
    {
        if (!$html) {
            return '';
        }

        $dom = new \DOMDocument();
        $dom->encoding = 'UTF-8';
        $dom->strictErrorChecking = false;

        $libXmlState = libxml_use_internal_errors(true);
        $loaded      = $dom->loadHTML(
            '<?xml encoding="UTF-8"?><div>' . $html . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($libXmlState);

        if (!$loaded) {
            return self::htmlToText($html);
        }

        // textContent doesn't know style/script are non-visible — drop them first,
        // otherwise the CSS this whole fix exists to remove leaks back in.
        foreach (['style', 'script'] as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        foreach (iterator_to_array($dom->getElementsByTagName('a')) as $anchor) {
            $url  = trim($anchor->getAttribute('href'));
            $text = trim($anchor->textContent);

            if ($text !== '' && $url !== '') {
                $replacement = "{$text} ({$url})";
            } else {
                $replacement = $text !== '' ? $text : $url;
            }

            // Pad with boundary spaces — otherwise adjacent anchors (no
            // whitespace between them in source) run together, e.g.
            // "...(url)Next link...". Collapsed back down below.
            $anchor->parentNode->replaceChild($dom->createTextNode(" {$replacement} "), $anchor);
        }

        // block-level tags carry no implicit whitespace of their own — textContent
        // concatenates "<div>$0.00</div><div>Free user</div>" as "$0.00Free user"
        // with nothing between them. Insert a separator on BOTH sides of each one:
        // "after" alone closes the gap between two sibling blocks, but misses text
        // immediately preceding a nested block with no source whitespace, e.g.
        // "<div>TextBefore<p>TextAfter</p></div>" would still squash to
        // "TextBeforeTextAfter" with an after-only separator. Newline for most
        // block tags, but just a space for td/th — those sit inside a <tr> that
        // already gets a newline, and a full break between them would wrongly
        // split a label from its value (e.g. "Credits" / "19,790"). Any resulting
        // doubled-up separators (two adjacent blocks each contributing one) are
        // collapsed by the normalization pass below.
        $cellTags = ['td' => true, 'th' => true];
        $separatorTags = array_merge(array_keys($cellTags), [
            'div', 'p', 'br', 'hr', 'li', 'tr', 'table', 'ul', 'ol',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'section', 'article', 'header', 'footer', 'blockquote',
        ]);

        // A single unioned XPath query, not one getElementsByTagName() call per
        // tag — that would re-traverse the whole DOM once per tag name.
        $xpath = new \DOMXPath($dom);
        $query = implode(' | ', array_map(fn($tag) => "//{$tag}", $separatorTags));
        foreach (iterator_to_array($xpath->query($query)) as $node) {
            $separator = isset($cellTags[$node->tagName]) ? ' ' : "\n";
            $node->parentNode->insertBefore($dom->createTextNode($separator), $node);
            $node->parentNode->insertBefore($dom->createTextNode($separator), $node->nextSibling);
        }

        // /u so \s also matches multi-byte whitespace (e.g. &nbsp; / U+00A0),
        // which PCRE's \s ignores in byte mode. Collapse horizontal whitespace
        // first, then fold the line breaks we just inserted down to one each —
        // this keeps the block boundaries as real newlines instead of squashing
        // them into a single space along with everything else.
        $text = preg_replace('/[^\S\n]+/u', ' ', $dom->textContent);
        $text = preg_replace('/ *\n */u', "\n", $text);
        $text = preg_replace('/\n{2,}/u', "\n", $text);

        return trim($text);
    }

    /**
     * Return a structured WP_Error whose message is a JSON-encoded error envelope.
     *
     * The MCP adapter forwards only the WP_Error message string to the agent,
     * dropping error_data. By encoding into the message the agent receives a
     * machine-readable structure it can branch on (code, fields, next_step, retryable).
     *
     * Supported $details keys:
     *   fields    => string[]  Input parameter names that caused the error.
     *   next_step => string    What the agent should do to recover.
     *   hint      => string    Extra context.
     *   retryable => bool      Whether retrying the same call might succeed (default false).
     */
    public static function error($code, $message, $details = [])
    {
        $payload = array_merge(['code' => $code, 'message' => $message, 'retryable' => false], $details);
        return new \WP_Error($code, wp_json_encode(['error' => $payload]));
    }

    /**
     * Resolve and authorize an explicit agent-assignment target for an MCP tool.
     *
     * Enforces the fst_assign_agents capability, validates the agent exists, and
     * — when a ticket is supplied — that the agent isn't restricted from the
     * ticket's mailbox. This is the single gate every MCP path uses before
     * assigning a ticket to a specific agent.
     *
     * @param int         $agentId   The requested agent ID.
     * @param Ticket|null $ticket    Ticket the agent will be assigned to, for the
     *                               mailbox-restriction check; null skips it (e.g.
     *                               bulk assign, where each ticket is checked in
     *                               the loop, or ticket creation).
     * @param string      $fieldName Input field name to report in error details.
     * @return Agent|\WP_Error  The resolved agent, or a WP_Error to return as-is.
     */
    public static function resolveAssignmentTarget($agentId, $ticket = null, $fieldName = 'agent_id')
    {
        if (!PermissionManager::currentUserCan('fst_assign_agents')) {
            return self::error('forbidden', __('You do not have permission to assign agents', 'fluent-support'));
        }

        $agentId = (int) $agentId;
        if ($agentId <= 0) {
            return self::error('invalid_param', sprintf(__('%s must be a positive integer', 'fluent-support'), $fieldName), ['fields' => [$fieldName]]);
        }

        $targetAgent = Agent::find($agentId);
        if (!$targetAgent) {
            return self::error('invalid_param', __('The specified agent does not exist', 'fluent-support'), ['fields' => [$fieldName], 'next_step' => 'Use get-support-context to see available agents and their IDs']);
        }

        if ($ticket instanceof Ticket) {
            if ($err = TicketAccessGuard::assertAssignableAgent($ticket, $targetAgent)) {
                return $err;
            }
        }

        return $targetAgent;
    }

    /**
     * Assign $ticket to $targetAgent and fire the canonical side effects: the
     * assignment internal-note conversation (TicketService::onAgentChange) and
     * the fluent_support/agent_assigned_to_ticket hook.
     *
     * Always persists the ticket — so callers may set other field changes on the
     * model first and let this save them in one write — but only fires the
     * assignment side effects when the assignee actually changes. Callers must
     * authorize $targetAgent first (see resolveAssignmentTarget);
     * self-assignment may skip that gate.
     *
     * When called inside a DB transaction that can roll back, pass
     * $deferSideEffects = true so the non-transactional side effects (assignment
     * email, webhooks) do NOT fire before the transaction commits. The caller
     * must then invoke fireAgentAssignmentSideEffects() after a successful
     * commit — capturing $ticket->agent_id BEFORE this call as the previous
     * agent id, since this method overwrites it.
     *
     * @return bool True if the assignee changed, false if it was already $targetAgent.
     */
    public static function applyAgentAssignment(Ticket $ticket, Agent $targetAgent, Agent $actingAgent, $deferSideEffects = false)
    {
        $changed         = (int) $ticket->agent_id !== (int) $targetAgent->id;
        $previousAgentId = $ticket->agent_id;

        if ($changed) {
            $ticket->agent_id = $targetAgent->id;
        }

        $ticket->save();

        if ($changed && !$deferSideEffects) {
            $ticket->load('agent');
            self::fireAgentAssignmentSideEffects($ticket, $actingAgent, $previousAgentId);
        }

        return $changed;
    }

    /**
     * Fire the canonical assignment side effects: the assignment internal-note
     * conversation (TicketService::onAgentChange) and the
     * fluent_support/agent_assigned_to_ticket hook (which sends the assignment
     * email synchronously). These are NOT transactional — only call this after
     * the assignment has been durably committed. $ticket must already carry the
     * new agent (its 'agent' relation loaded).
     */
    public static function fireAgentAssignmentSideEffects(Ticket $ticket, Agent $actingAgent, $previousAgentId)
    {
        (new TicketService())->onAgentChange($ticket, $actingAgent);
        do_action('fluent_support/agent_assigned_to_ticket', $ticket->agent, $ticket, $actingAgent, $previousAgentId);
    }

    public static function formatDuration($seconds)
    {
        if (!$seconds) {
            return '0m';
        }
        $seconds = (int) round($seconds);
        if ($seconds < 60) {
            return $seconds . 's';
        }
        if ($seconds < 3600) {
            return round($seconds / 60) . 'm';
        }
        if ($seconds < 86400) {
            $h = floor($seconds / 3600);
            $m = round(($seconds % 3600) / 60);
            return $h . 'h ' . $m . 'm';
        }
        $d = floor($seconds / 86400);
        $h = round(($seconds % 86400) / 3600);
        return $d . 'd ' . $h . 'h';
    }
}
