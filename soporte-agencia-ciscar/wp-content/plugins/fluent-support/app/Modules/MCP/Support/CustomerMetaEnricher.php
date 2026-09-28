<?php

namespace FluentSupport\App\Modules\MCP\Support;

use FluentSupport\App\Modules\PermissionManager;

/**
 * Resolves an optional, integration-provided one-line meta string per customer
 * for MCP list surfaces (e.g. "Paying · 8,016 sent/30d · domain verified").
 *
 * Fluent Support generates nothing here — it only exposes a batch filter that
 * integrations (FluentCRM/FluentCart/etc.) may hook. The string can carry
 * billing/CRM PII, so resolution is gated on the fst_sensitive_data capability,
 * the same boundary AdminSensitivePolicy uses for customer data.
 */
class CustomerMetaEnricher
{
    const MAX_LEN = 120;

    /**
     * Build a [customer_id => meta line] map for the customers on one list page.
     *
     * Fires the filter ONCE per page (never per row) and returns [] for agents
     * without fst_sensitive_data, so the integration round-trip is skipped and
     * no PII leaks by default.
     *
     * @param iterable $tickets  The page of Ticket models (customer eager-loaded).
     * @param array    $context  Extra context passed to the filter (e.g. agent_id).
     * @return array<int,string>
     */
    public static function resolve($tickets, array $context = [])
    {
        if (!PermissionManager::userCan('fst_sensitive_data')) {
            return [];
        }

        $customers = [];
        foreach ($tickets as $ticket) {
            if ($ticket->customer_id && $ticket->relationLoaded('customer') && $ticket->customer) {
                $customers[$ticket->customer_id] = $ticket->customer;
            }
        }

        if (!$customers) {
            return [];
        }

        $customerIds = array_keys($customers);

        /**
         * Filter: fluent_support/mcp_customer_list_meta
         *
         * Inject a short, plain-text status line per customer for MCP list
         * surfaces, e.g. "Paying · 8,016 sent/30d · domain verified".
         *
         * CONTRACT — read before hooking:
         *   • BATCH ONLY. Fired ONCE per page with a deduplicated array of
         *     customer IDs. Do ALL work in a single query (WHERE customer_id
         *     IN (...)). DO NOT query per customer in a loop — that reintroduces
         *     N+1 and shows up in list latency. The loaded Customer models are
         *     passed so you can read email/user_id without an extra DB hit.
         *   • CHEAP. Runs on every list render. No remote HTTP calls. Cache
         *     anything expensive yourself (transient / object cache).
         *   • PLAIN STRING. The return value is sanitized to plain text: tags
         *     stripped, control chars removed, capped at 120 chars. HTML,
         *     markdown, arrays, objects and unknown keys are DISCARDED.
         *   • ADDITIVE. Merge into the incoming map; preserve existing keys.
         *
         * @param array<int,string> $lines       Default []. Merge, don't overwrite.
         * @param int[]             $customerIds Deduped customer IDs on this page.
         * @param array             $customers   [customer_id => loaded Customer model].
         * @param array             $context     ['surface' => string, 'agent_id' => int|null].
         * @return array<int,string>             [customer_id => plain-text line].
         */
        $raw = apply_filters(
            'fluent_support/mcp_customer_list_meta',
            [],
            $customerIds,
            $customers,
            $context
        );

        return self::sanitize($raw, $customerIds);
    }

    /**
     * Coerce the untrusted filter output into a safe [int => plain string] map.
     *
     * @param mixed $raw
     * @param int[] $allowedIds
     * @return array<int,string>
     */
    private static function sanitize($raw, array $allowedIds)
    {
        if (!is_array($raw)) {
            return [];
        }

        $allowed = array_flip(array_map('intval', $allowedIds));
        $clean   = [];

        foreach ($raw as $id => $value) {
            $id = (int) $id;

            // Drop keys not on this page and non-scalar (array/object) values.
            if (!isset($allowed[$id]) || !is_scalar($value)) {
                continue;
            }

            $value = wp_strip_all_tags((string) $value, true);
            $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value); // strip control chars
            $value = trim(preg_replace('/\s+/u', ' ', $value));

            if ($value === '') {
                continue;
            }

            $clean[$id] = function_exists('mb_substr')
                ? mb_substr($value, 0, self::MAX_LEN)
                : substr($value, 0, self::MAX_LEN);
        }

        return $clean;
    }
}
