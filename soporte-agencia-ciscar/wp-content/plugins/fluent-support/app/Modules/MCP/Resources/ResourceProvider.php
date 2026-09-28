<?php

namespace FluentSupport\App\Modules\MCP\Resources;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\TicketTag;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\TicketHelper;

/** @internal Dead class — not wired up. Keep for future WP Resources API integration or remove. */
class ResourceProvider
{
    public static function getResourceDefinitions()
    {
        return [
            'fluentsupport://agents' => [
                'name'        => 'Support Agents',
                'description' => 'List of all support agents with ID, name, and email',
                'mimeType'    => 'application/json',
            ],
            'fluentsupport://products' => [
                'name'        => 'Products',
                'description' => 'List of all products/categories for ticket classification',
                'mimeType'    => 'application/json',
            ],
            'fluentsupport://mailboxes' => [
                'name'        => 'Mailboxes',
                'description' => 'List of all mailboxes with ID, name, and email',
                'mimeType'    => 'application/json',
            ],
            'fluentsupport://tags' => [
                'name'        => 'Ticket Tags',
                'description' => 'List of all ticket tags with ID and title',
                'mimeType'    => 'application/json',
            ],
            'fluentsupport://priorities' => [
                'name'        => 'Priorities',
                'description' => 'Available ticket priority values',
                'mimeType'    => 'application/json',
            ],
            'fluentsupport://statuses' => [
                'name'        => 'Statuses',
                'description' => 'Available ticket status values and groups',
                'mimeType'    => 'application/json',
            ],
            'fluentsupport://stats/overview' => [
                'name'        => 'Dashboard Stats',
                'description' => 'Overview of ticket counts by status',
                'mimeType'    => 'application/json',
            ],
        ];
    }

    public static function readResource($uri)
    {
        if (!is_user_logged_in() || !PermissionManager::currentUserCan('fst_view_tickets')) {
            return new \WP_Error('forbidden', 'You do not have Fluent Support access');
        }

        switch ($uri) {
            case 'fluentsupport://agents':
                return Agent::select(['id', 'first_name', 'last_name', 'email'])
                    ->where('person_type', 'agent')
                    ->get()
                    ->toArray();

            case 'fluentsupport://products':
                return Product::select(['id', 'title'])->get()->toArray();

            case 'fluentsupport://mailboxes':
                return MailBox::getAccessibleBoxes(PermissionManager::userCan('fst_sensitive_data'))->toArray();

            case 'fluentsupport://tags':
                return TicketTag::select(['id', 'title'])->get()->toArray();

            case 'fluentsupport://priorities':
                return [
                    'admin_priorities'  => Helper::adminTicketPriorities(),
                    'client_priorities' => Helper::customerTicketPriorities(),
                ];

            case 'fluentsupport://statuses':
                return [
                    'statuses'       => Helper::ticketStatuses(),
                    'status_groups'  => Helper::ticketStatusGroups(),
                ];

            case 'fluentsupport://stats/overview':
                return [
                    'total'      => TicketHelper::countAllTickets(),
                    'new'        => TicketHelper::countNewTickets(),
                    'active'     => TicketHelper::countActiveTickets(),
                    'closed'     => TicketHelper::countClosedTickets(),
                    'unassigned' => TicketHelper::countUnassignedTickets(),
                ];

            default:
                return new \WP_Error('not_found', 'Resource not found', ['uri' => sanitize_text_field($uri)]);
        }
    }
}
