<?php

namespace FluentSupport\App\Services\Tickets\Importer;

use FluentSupport\App\Models\Meta;
use FluentSupport\App\Services\Helper;

class ZendeskTickets extends BaseImporter
{
    protected $handler = 'zendesk';
    public $accessToken;
    public $mailbox_id;
    private $domain;
    private $email;
    protected $limit;
    private $hasMore;
    private $currentPage;
    private $totalTickets;
    private $originId;
    private $totalPage;
    private $errorMessage;
    private $afterCursor = null;
    private $nextPageUrl = null;
    private $skippedTickets = [];
    private $includeArchived = false;
    private $importLimitReached = false;
    private static $personCache = [];

    public function stats()
    {
        $metadata = Meta::where('object_type', '_fs_zendesk_migration_info')->first();
        $previouslyImported = Helper::safeUnserialize($metadata->value ?? []);
        $previouslyImported['domain'] = $metadata->key ?? '';

        if (!empty($previouslyImported['total_tickets'])) {
            $progress = $this->getProgress((int) $previouslyImported['total_tickets']);
            $previouslyImported = array_merge($previouslyImported, $progress);
        }

        return [
            'name' => esc_html('Zendesk'),
            'handler' => $this->handler,
            'type' => 'sass',
            'last_migrated' => get_option('_fs_migrate_zendesk'),
            'previously_imported' => $previouslyImported,
        ];
    }

    private function getProgress($totalTickets)
    {
        $importedTickets = (int) $this->db->table('fs_tickets')
            ->where('source', $this->handler)
            ->count();

        return [
            'imported_tickets' => $importedTickets,
            'completed'        => $totalTickets > 0 ? min(100, intval(($importedTickets / $totalTickets) * 100)) : 0,
            'remaining'        => max(0, $totalTickets - $importedTickets),
        ];
    }

    public function setCursor($cursor)
    {
        $this->afterCursor = $cursor;
    }

    public function setNextPageUrl($url)
    {
        $this->nextPageUrl = $url;
    }

    public function doMigration($page, $handler)
    {

        $this->currentPage = $page;
        $this->handler = $handler;
        $this->errorMessage = null;
        $this->skippedTickets = [];

        // Cache total ticket count: fetch from API on first page, read from saved progress after
        if ($page == 1) {
            $this->totalTickets = $this->countTotalTickets();
            update_option('_fs_zendesk_total_tickets', $this->totalTickets, false);
            $this->cleanupOrphanedOriginIds();
        } else {
            $this->totalTickets = 0;
            // On resume, prefer total from saved migration info (consistent with the saved cursor/page)
            $savedProgress = Meta::where('object_type', '_fs_zendesk_migration_info')->first();
            if ($savedProgress) {
                $savedData = Helper::safeUnserialize($savedProgress->value, []);
                $this->totalTickets = (int) ($savedData['total_tickets'] ?? 0);
            }
            if (!$this->totalTickets) {
                $this->totalTickets = (int) get_option('_fs_zendesk_total_tickets', 0);
            }
        }

        $tickets = $this->ticketsWithReply();
        $results = $this->migrateTickets($tickets);

        $actualPageSize = $this->includeArchived ? 100 : $this->limit;
        $this->totalPage = $actualPageSize > 0 ? ceil($this->totalTickets / $actualPageSize) : 0;

        $this->hasMore = !empty($this->afterCursor) || $this->importLimitReached;
        $progress = $this->getProgress($this->totalTickets);

        $response = [
            'handler' => $this->handler,
            'insert_ids' => $results['inserts'],
            'skips' => count($results['skips']) + count($this->skippedTickets),
            'has_more' => $this->hasMore,
            'completed' => $progress['completed'],
            'imported_page' => $page,
            'total_pages' => $this->totalPage,
            'next_page' => $page + 1,
            'total_tickets' => $this->totalTickets,
            'imported_tickets' => $progress['imported_tickets'],
            'remaining' => $progress['remaining'],
            'cursor' => $this->afterCursor,
            'next_page_url' => $this->nextPageUrl,
            'include_archived' => $this->includeArchived,
        ];

        // Handle errors or success
        if ($this->errorMessage) {
            $response['error'] = true;
            $response['message'] = $this->errorMessage;
        } elseif ($this->hasMore) {
            // Save progress for resume capability (exclude bulk data not needed for resume)
            $progressData = $response;
            unset($progressData['insert_ids'], $progressData['skipped_ticket_ids']);

            $previousValue = Meta::where('object_type', '_fs_zendesk_migration_info')->first();
            if ($previousValue) {
                Meta::where('object_type', '_fs_zendesk_migration_info')->update([
                    'key' => $this->domain,
                    'value' => maybe_serialize($progressData)
                ]);
            } else {
                Meta::insert([
                    'object_type' => '_fs_zendesk_migration_info',
                    'key' => $this->domain,
                    'value' => maybe_serialize($progressData)
                ]);
            }
        } elseif (!$this->hasMore && ($this->totalTickets > 0 || $progress['imported_tickets'] > 0)) {
            Meta::where('object_type', '_fs_zendesk_migration_info')->delete();
            $response['message'] = __('All tickets have been imported successfully', 'fluent-support');
            update_option('_fs_migrate_zendesk', current_time('mysql'), 'no');
            delete_option('_fs_zendesk_total_tickets');
        }

        return $response;
    }

    private function ticketsWithReply()
    {
        try {
            $this->totalTickets = $this->totalTickets ?: 0;

            if ($this->nextPageUrl) {
                // Use the full next page URL from the previous response for proper session continuity
                $url = $this->nextPageUrl;
            } elseif ($this->includeArchived) {
                // Search Export API includes archived tickets
                $url = $this->buildSearchExportUrl();
                if ($this->afterCursor) {
                    $url .= '&page[after]=' . urlencode($this->afterCursor);
                }
            } else {
                $url = "{$this->domain}/api/v2/tickets?page[size]={$this->limit}";
                if ($this->afterCursor) {
                    $url .= '&page[after]=' . urlencode($this->afterCursor);
                }
            }

            try {
                $tickets = $this->makeRequest($url);
            } catch (\Exception $e) {
                // Handle expired/invalid cursor (Search Export API cursors expire after 60 minutes)
                // Only recover for cursor-related errors, not temporary server errors (502, 503, timeouts)
                $errorMsg = strtolower($e->getMessage());
                $isCursorError = strpos($errorMsg, 'cursor') !== false
                    || strpos($errorMsg, 'invalid') !== false
                    || strpos($errorMsg, 'pagination') !== false;

                if ($isCursorError && ($this->afterCursor || $this->nextPageUrl)) {
                    $this->afterCursor = null;
                    $this->nextPageUrl = null;

                    $url = $this->includeArchived
                        ? $this->buildSearchExportUrl()
                        : "{$this->domain}/api/v2/tickets?page[size]={$this->limit}";

                    $tickets = $this->makeRequest($url);
                } else {
                    throw $e;
                }
            }

            // Search Export API returns 'results', Tickets API returns 'tickets'
            $ticketList = $this->includeArchived
                ? ($tickets->results ?? [])
                : ($tickets->tickets ?? []);

            $formattedTickets = [];
            if (empty($ticketList)) {
                $this->hasMore = false;
                $this->afterCursor = null;
                return [];
            }

            // Save current cursor before reading next page's cursor
            $previousCursor = $this->afterCursor;
            $previousNextPageUrl = $this->nextPageUrl;

            // Read cursor pagination metadata
            if (isset($tickets->meta->has_more) && $tickets->meta->has_more && !empty($tickets->meta->after_cursor)) {
                $this->afterCursor = $tickets->meta->after_cursor;
                // Store the full next page URL for session continuity (especially for Search Export API)
                $this->nextPageUrl = $tickets->links->next ?? null;
            } else {
                $this->afterCursor = null;
                $this->nextPageUrl = null;
            }

            $this->hasMore = !empty($this->afterCursor);

            // Batch check which tickets are already migrated (single DB query instead of N)
            $ticketOriginIds = array_map(function ($t) { return intval($t->id); }, $ticketList);
            $alreadyMigrated = $this->getMigratedOriginIds($ticketOriginIds);

            foreach ($ticketList as $ticket) {
                try {
                    // Skip already-migrated tickets before making expensive API calls
                    if (in_array(intval($ticket->id), $alreadyMigrated)) {
                        $this->skippedTickets[] = $ticket->id;
                        continue;
                    }

                    // Cap imports per batch to avoid PHP timeout (30s max_execution_time)
                    // Remaining unimported tickets will be picked up when this page is re-fetched
                    if (count($formattedTickets) >= $this->limit) {
                        $this->afterCursor = $previousCursor;
                        $this->nextPageUrl = $previousNextPageUrl;
                        $this->importLimitReached = true;
                        break;
                    }

                    $singleTicketUrl = $this->domain . '/api/v2/tickets/' . $ticket->id . '/comments.json?include=attachments,users';
                    $singleTicket = $this->makeRequest($singleTicketUrl);
                    $this->originId = $ticket->id;
                    $ticketAttacments  = [];
                    if (!empty($singleTicket->comments[0]->attachments)) {
                        $ticketAttacments = $this->getAttachments($singleTicket->comments[0]->attachments);
                    }

                    $subject = is_object($ticket->subject) ? ($ticket->subject->value ?? '') : ($ticket->subject ?? '');
                    $description = is_object($ticket->description) ? ($ticket->description->value ?? '') : ($ticket->description ?? '');

                    $formattedTickets[] = [
                        'title' => sanitize_text_field($subject),
                        'content' => wp_kses_post($description),
                        'origin_id' => intval($ticket->id),
                        'source' => sanitize_text_field($this->handler),
                        'customer' => $this->fetchPerson($ticket->requester_id),
                        'replies' => $this->getReplies($singleTicket),
                        'status' => $this->getStatus($ticket->status),
                        'client_priority' => $this->getPriority($ticket->priority),
                        'priority' => $this->getPriority($ticket->priority),
                        'created_at' => $ticket->created_at ? gmdate('Y-m-d h:i:s', strtotime($ticket->created_at)) : null,
                        'updated_at' => $ticket->updated_at ? gmdate('Y-m-d h:i:s', strtotime($ticket->updated_at)) : null,
                        'last_customer_response' => NULL,
                        'last_agent_response' => NULL,
                        'attachments' => $ticketAttacments
                    ];
                } catch (\Exception $e) {
                    // Skip this ticket and continue with the rest
                    $this->skippedTickets[] = $ticket->id;
                }
            }

            return $formattedTickets;

        } catch (\Exception $e) {
            // Store error message for authentication errors
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'authenticate') !== false ||
                strpos($errorMsg, 'Couldn\'t authenticate') !== false ||
                strpos($errorMsg, '401') !== false) {
                $this->errorMessage = __('Authentication failed. Please check your Zendesk credentials.', 'fluent-support');
            } else {
                // Store any other error message
                $this->errorMessage = $errorMsg;
            }
            return [];
        }
    }

    private function getReplies($replies)
    {
        unset($replies->comments[0]);
        $formattedReplies = [];
        foreach ($replies->comments as $reply) {
            $ticketReply = [
                'content' => wp_kses_post($reply->body),
                'conversation_type' => 'response',
                'created_at' => $reply->created_at ? gmdate('Y-m-d h:i:s', strtotime($reply->created_at)) : null,
                'updated_at' => !empty($reply->updated_at) ? gmdate('Y-m-d h:i:s', strtotime($reply->updated_at)) : null,
            ];

            $ticketReply = $this->populatePersonInfo($ticketReply, $reply, $replies->users);

            if (!empty($reply->attachments) && count($reply->attachments)) {
                $ticketReply['attachments'] = $this->getAttachments($reply->attachments);
            }

            $formattedReplies[] = $ticketReply;
        }

        return $formattedReplies;
    }

    private function populatePersonInfo($ticketReply, $reply, $users)
    {
        foreach ($users as $user) {
            if ($user->id !== $reply->author_id) {
                continue;
            }

            $ticketReply['is_customer_reply'] = $user->role === 'end-user';
            $type = $user->role === 'end-user' ? 'user' : 'agent';
            $ticketReply['user'] = Common::formatPersonData($user, $type);
            return $ticketReply;
        }

        // Author not found in users list (deleted user, system, etc.)
        $ticketReply['is_customer_reply'] = false;
        $ticketReply['user'] = null;

        return $ticketReply;
    }

    private function makeRequest($url, $retryCount = 0)
    {
        if (!Common::isSafeRemoteUrl($url)) {
            throw new \Exception(esc_html__('Zendesk API URL is not allowed.', 'fluent-support'));
        }

        $token = base64_encode($this->email . '/token:' . $this->accessToken);

        $request = wp_safe_remote_get($url, [
            'headers' => [
                'Authorization' => "Basic {$token}",
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60,
            'redirection' => 0
        ]);

        if (is_wp_error($request)) {
            // Retry on timeout errors (cURL error 28)
            if ($retryCount < 2 && strpos($request->get_error_message(), 'cURL error 28') !== false) {
                sleep(5);
                return $this->makeRequest($url, $retryCount + 1);
            }
            throw new \Exception('Error while making request: ' . esc_html($request->get_error_message()));
        }

        $response_code = wp_remote_retrieve_response_code($request);
        $response_body = wp_remote_retrieve_body($request);
        $response = json_decode($response_body);

        // Handle rate limiting (429) and temporary server errors (502, 503) with retry
        if (in_array($response_code, [429, 502, 503]) && $retryCount < 2) {
            $retryAfter = (int) wp_remote_retrieve_header($request, 'retry-after');
            $retryAfter = $retryAfter > 0 ? min($retryAfter, 60) : 10;
            sleep($retryAfter);
            return $this->makeRequest($url, $retryCount + 1);
        }

        // If status code is 200, don't throw error - check response body for errors instead
        if ($response_code === 200) {
            // Check for errors in response body
            if (isset($response->error)) {
                $error_msg = is_object($response->error) ? json_encode($response->error) : (string) $response->error;
                if (isset($response->description)) {
                    $error_msg .= ': ' . $response->description;
                }
                throw new \Exception(esc_html($error_msg));
            }
            return $response;
        }

        // Handle non-200 status codes
        if ($response_code === 401) {
            throw new \Exception('Couldn\'t authenticate you');
        }

        // Other error status codes
        $error_msg = 'HTTP Error ' . $response_code;
        if (isset($response->error)) {
            $error_msg = is_object($response->error) ? json_encode($response->error) : (string) $response->error;
            if (isset($response->description)) {
                $error_msg .= ': ' . $response->description;
            }
        }
        throw new \Exception(esc_html($error_msg));
    }

    private function fetchPerson($requesterId)
    {
        // Return from cache if already fetched
        if (isset(self::$personCache[$requesterId])) {
            return self::$personCache[$requesterId];
        }

        $userUrl = $this->domain . '/api/v2/users/' . $requesterId . '.json';
        $fetchUser = $this->makeRequest($userUrl);

        $user = (object)[
            'name' => $fetchUser->user->name,
            'address' => $fetchUser->user->email
        ];

        $personArray = Common::formatPersonData($user, 'customer');
        $person = Common::updateOrCreatePerson($personArray);

        self::$personCache[$requesterId] = $person;

        return $person;
    }

    /**
     * Build Search Export API URL
     * Uses a larger page size (100) to reduce scanning overhead for already-imported batches
     */
    private function buildSearchExportUrl()
    {
        $query = 'type:ticket';
        $pageSize = 100;

        return "{$this->domain}/api/v2/search/export?query=" . urlencode($query) . "&filter[type]=ticket&page[size]={$pageSize}";
    }

    private function countTotalTickets()
    {
        if ($this->includeArchived) {
            // Search count API includes archived tickets
            $url = "{$this->domain}/api/v2/search/count?query=" . urlencode('type:ticket');
            $count = $this->makeRequest($url);
            return (int) $count->count;
        }

        $url = "{$this->domain}/api/v2/tickets/count.json";
        $count = $this->makeRequest($url);
        return (int) $count->count->value;
    }

    private function getAttachments($attachments)
    {
        $wpUploadDir = wp_upload_dir();
        $safeOriginId = (int) $this->originId;
        $baseDir = $wpUploadDir['basedir'] . '/fluent-support/zendesk-ticket-' . $safeOriginId . '/';

        $formattedAttachments = [];
        foreach ($attachments as $attachment) {
            $filePath = Common::downloadFile($attachment->content_url, $baseDir, $attachment->file_name);

            // Skip attachments that fail to download/validate rather than aborting the whole ticket;
            // callers of getAttachments() don't check for WP_Error, so never return one here.
            if (is_wp_error($filePath) || !$filePath) {
                continue;
            }

            $fileUrl = $wpUploadDir['baseurl'] . '/fluent-support/zendesk-ticket-' . $safeOriginId . '/' . basename($filePath);
            $formattedAttachments[] = [
                'full_url' => $fileUrl,
                'title' => $attachment->file_name,
                'file_path' => $filePath,
                'driver' => 'local',
                'status' => 'active',
                'file_type' => $attachment->content_type
            ];
        }

        return $formattedAttachments;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function setDomain($domain)
    {
        $domain = is_string($domain) ? rtrim(trim($domain), '/') : '';

        // Not locked to *.zendesk.com: Zendesk's host mapping feature lets tenants
        // run the help center and REST API on their own domain. We still require
        // https and reject any loopback/private/link-local target.
        if (!Common::isSafeRemoteUrl($domain)) {
            throw new \InvalidArgumentException(__('Invalid Zendesk domain. Use a https:// URL pointing to your Zendesk instance.', 'fluent-support'));
        }

        $this->domain = $domain;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setIncludeArchived($includeArchived)
    {
        $this->includeArchived = (bool) $includeArchived;
    }

    private function getStatus($status)
    {
        switch ($status) {
            case 'open':
                return 'active';
            case 'pending':
                return 'waiting';
            case 'solved':
                return 'closed';
            default:
                return 'active';
        }

    }

    private function getPriority($priority)
    {
        switch ($priority) {
            case 'low':
            case 'normal':
                return 'normal';
            case 'high':
                return 'medium';
            case 'urgent':
                return 'critical';
            default:
                return 'normal';
        }
    }

    /**
     * Batch check which origin IDs are already migrated (single DB query)
     */
    private function getMigratedOriginIds(array $originIds)
    {
        if (empty($originIds)) {
            return [];
        }

        $results = $this->db->table('fs_meta')
            ->where('object_type', 'ticket_meta')
            ->where('key', '_' . $this->handler . '_origin_id')
            ->whereIn('value', $originIds)
            ->pluck('value');

        return array_map('intval', $results->toArray());
    }

    /**
     * Remove orphaned origin IDs from fs_meta where the ticket no longer exists in fs_tickets.
     * This allows re-importing tickets that were previously imported but later deleted.
     */
    private function cleanupOrphanedOriginIds()
    {
        global $wpdb;

        $metaKey = '_' . $this->handler . '_origin_id';

        $wpdb->query($wpdb->prepare(
            "DELETE m FROM {$wpdb->prefix}fs_meta m
             LEFT JOIN {$wpdb->prefix}fs_tickets t ON m.object_id = t.id
             WHERE m.object_type = 'ticket_meta'
             AND m.key = %s
             AND t.id IS NULL",
            $metaKey
        ));
    }

    public function deleteTickets($page)
    {
        return;
    }
}
