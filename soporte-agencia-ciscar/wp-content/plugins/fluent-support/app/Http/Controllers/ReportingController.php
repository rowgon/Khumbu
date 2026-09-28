<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Modules\Reporting\Reporting;
use FluentSupport\App\Modules\StatModule;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\TagPivot;

/**
 * ReportingController class for REST API
 * This class is responsible for getting data for all request related to report
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class ReportingController extends Controller
{
    private static function getSanitizedDateRange(Request $request)
    {
        $dateRange = $request->get('date_range', []);

        if (is_array($dateRange) && count($dateRange) >= 2) {
            return [
                sanitize_text_field($dateRange[0] ?? ''),
                sanitize_text_field($dateRange[1] ?? '')
            ];
        }

        if (is_string($dateRange)) {
            $parts = array_map('trim', explode(',', $dateRange));
            return [
                sanitize_text_field($parts[0] ?? ''),
                sanitize_text_field($parts[1] ?? '')
            ];
        }

        return ['', ''];
    }

    private static function getAgentIdsForGroup(Request $request)
    {
        $groupId = $request->getSafe('agent_group_id', 'intval');
        if (!$groupId) {
            return null;
        }

        return TagPivot::where('source_type', 'agent_group')
            ->where('tag_id', $groupId)
            ->pluck('source_id')
            ->toArray();
    }

    /**
     * getOverallReports method will return the overall statistics of all ticket by ticket statuses
     * The response will have an array with ticket number by ticket status
     * @param Request $request
     * @return array
     */
    public function getOverallReports(Request $request)
    {
        return [
            'overall_reports' => StatModule::getOverAllStats()
        ];
    }

    public function getActiveTicketsByProduct()
    {
        return [
            'stats' => StatModule::getActiveTicketsByProductStats()
        ];
    }

    /**
     * getTicketsChart method will generate statistics for all tickets within a date range and return ticket number by date
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getTicketsChart(Request $request, Reporting $reporting)
    {
        list($from, $to) = self::getSanitizedDateRange($request);

        $filter = [
            'agent_id' => $request->getSafe('agent_id', 'intval') ?: null,
            'product_id' => $request->getSafe('product_id', 'intval') ?: null,
            'mailbox_id' => $request->getSafe('mailbox_id', 'intval') ?: null,
        ];

        $agentIds = self::getAgentIdsForGroup($request);
        if ($agentIds !== null) {
            $filter['agent_ids'] = $agentIds;
        }

        $stats = $reporting->getTicketsGrowth($from, $to, $filter);

        return [
            'stats' => $stats
        ];
    }

    /**
     * getResolveChart method will generate statistics for closed tickets within a date range and return ticket number by date
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getResolveChart(Request $request, Reporting $reporting): array
    {
        $type = $request->getSafe('type', 'sanitize_text_field');
        list($from, $to) = self::getSanitizedDateRange($request);

        $filter = [
            'agent_id' => $request->getSafe('agent_id', 'intval') ?: null,
            'product_id' => $request->getSafe('product_id', 'intval') ?: null,
            'mailbox_id' => $request->getSafe('mailbox_id', 'intval') ?: null,
        ];

        $agentIds = self::getAgentIdsForGroup($request);
        if ($agentIds !== null) {
            $filter['agent_ids'] = $agentIds;
        }

        $stats = $reporting->getTicketResolveGrowth($from, $to, $filter,$type);

        return [
            'stats' => $stats
        ];
    }

    /**
     * getResponseChart method will generate response statistics for ticket by date range
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getResponseChart(Request $request, Reporting $reporting)
    {
        list($from, $to) = self::getSanitizedDateRange($request);
        $filter = [];

        if ($person_id = $request->getSafe('agent_id', 'intval')) {
            $filter['person_id'] = $person_id;
        }

        $agentIds = self::getAgentIdsForGroup($request);
        if ($agentIds !== null) {
            $filter['person_ids'] = $agentIds;
        }

        $stats = $reporting->getResponseGrowth($from, $to, $filter);

        return [
            'stats' => $stats
        ];
    }

    /**
     * getAgentsSummary method will generate summary for agent
     * This method will count closed tickets, open tickets, responses/interactions with ticket by agent within a date range
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getAgentsSummary(Request $request, Reporting $reporting)
    {
        return [
          'summary' =>  $reporting->agentSummary($request->getSafe('from', 'sanitize_text_field'), $request->getSafe('to', 'sanitize_text_field'))
        ];
    }

    /**
     * getAgentOverallReports method will return the overall statistics report for logged-in agent
     * @param Request $request
     * @return array
     */
    /**
     * getAgentGroupsSummary method will generate summary aggregated by agent group
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getAgentGroupsSummary(Request $request, Reporting $reporting)
    {
        return [
            'summary' => $reporting->agentGroupSummary(
                $request->getSafe('from', 'sanitize_text_field'),
                $request->getSafe('to', 'sanitize_text_field')
            )
        ];
    }

    public function getAgentOverallReports(Request $request): array
    {
        $agent =  Helper::getAgentByUserId(get_current_user_id());

        return [
            'overall_reports' => StatModule::getAgentOverallStats($agent->id),
            'today_reports' => StatModule::getTodayStats($agent->id)
        ];
    }

    /**
     * getResponseGrowthChart method will generate response statistics for ticket by date range for product or mailbox
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getResponseGrowthChart(Request $request, Reporting $reporting): array
    {
        $type = $request->getSafe('type', 'sanitize_text_field');
        list($from, $to) = self::getSanitizedDateRange($request);

        $filter = [
            'product_id' => $request->getSafe('product_id', 'intval') ?: null,
            'mailbox_id' => $request->getSafe('mailbox_id', 'intval') ?: null,
        ];

        $stats = $reporting->getResponseGrowthChart($from, $to, $filter,$type);

        return [
            'stats' => $stats
        ];
    }

    /**
     * getProductsSummary method will generate summary for product
     * This method will count closed tickets, open tickets, responses, interactions with ticket by agent within a date range
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getProductsSummary(Request $request, Reporting $reporting): array
    {
        return [
            'summary' =>  $reporting->getSummary('product',$request->getSafe('from', 'sanitize_text_field'), $request->getSafe('to', 'sanitize_text_field'))
        ];

    }

    /**
     * getMailBoxesSummary method will generate summary for mailbox
     * This method will count closed tickets, open tickets, responses, interactions with ticket by agent within a date range
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getMailBoxesSummary(Request $request, Reporting $reporting): array
    {
        return [
            'summary' =>  $reporting->getSummary('mailbox',$request->getSafe('from', 'sanitize_text_field'), $request->getSafe('to', 'sanitize_text_field'))
        ];
    }

    /**
     * getAgentResolveChart method will generate ticket data for resolved ticket
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getAgentResolveChart(Request $request, Reporting $reporting)
    {
        //Get logged in agent information
        $agent =  Helper::getAgentByUserId(get_current_user_id());
        list($from, $to) = self::getSanitizedDateRange($request);

        return [
            'stats' => $reporting->getTicketResolveGrowth($from, $to, ['agent_id' => $agent->id])
        ];
    }

    /**
     * getAgentResponseChart method will generate the statistics of response by agent in tickets within date range
     * @param Request $request
     * @param Reporting $reporting
     * @return array
     */
    public function getAgentResponseChart(Request $request, Reporting $reporting)
    {
        $agent =  Helper::getAgentByUserId(get_current_user_id());
        list($from, $to) = self::getSanitizedDateRange($request);

        return [
            'stats' => $reporting->getResponseGrowth($from, $to, ['person_id' => $agent->id])
        ];
    }

    /**
     * getPersonalSummary method will generate summary for specific agent
     * This method will count closed tickets, open tickets, responses/interactions with ticket by agent within a date range
     * @param Reporting $reporting
     * @param Request $request
     * @return array
     */
    public function getPersonalSummary(Reporting $reporting, Request $request)
    {
        $agent =  Helper::getAgentByUserId(get_current_user_id());

        return [
            'summary' =>  $reporting->agentSummary($request->getSafe('from', 'sanitize_text_field'), $request->getSafe('to', 'sanitize_text_field'), $agent->id)
        ];
    }

    public function dayTimeStats(Reporting $reporting, Request $request)
    {
        list($from, $to) = self::getSanitizedDateRange($request);

        $filter = [
            'report_type' => $request->getSafe('report_type', 'sanitize_text_field') ?: null,
            'agent_id' => $request->getSafe('agent_id', 'intval') ?: null,
        ];

        $results = $reporting->getQueryResults($from, $to, $filter);

        return $this->send([
            'stats' => $results
        ]);
    }

    public function ticketResponseStats(Reporting $reporting, Request $request)
    {

        list($from, $to) = self::getSanitizedDateRange($request);

        $filter = [
            'person_type' => $request->getSafe('person_type', 'sanitize_text_field') ?: null,
            'person_id' => $request->getSafe('person_id', 'intval') ?: null,
        ];

        return $reporting->getTicketResponseStats($from, $to, $filter);
    }

    /**
     * getStats method will return statistics similar to getOverallReports but with filters
     * Returns: New Tickets, Active Tickets, Closed Tickets, and Responses
     * Filters: date_range, mailbox_id (business_box), product_id, agent_id, customer_id
     * @param Request $request
     * @return array
     */
    public function getStats(Request $request)
    {
        list($from, $to) = self::getSanitizedDateRange($request);

        $filters = [
            'mailbox_id' => $request->getSafe('mailbox_id', 'intval') ?: $request->getSafe('business_box', 'intval'),
            'product_id' => $request->getSafe('product_id', 'intval'),
            'agent_id' => $request->getSafe('agent_id', 'intval'),
            'customer_id' => $request->getSafe('customer_id', 'intval'),
        ];

        $agentIds = self::getAgentIdsForGroup($request);

        $baseQuery = Ticket::query();
        foreach ($filters as $field => $value) {
            if ($value) {
                $baseQuery->where($field, $value);
            }
        }

        if ($agentIds !== null) {
            $baseQuery->whereIn('agent_id', $agentIds);
        }

        $applyDateRange = function($query, $dateField = 'created_at') use ($from, $to) {
            if ($from && $to) {
                $query->whereBetween($dateField, ["$from 00:00:00", "$to 23:59:59"]);
            } elseif ($from) {
                $query->where($dateField, '>=', "$from 00:00:00");
            } elseif ($to) {
                $query->where($dateField, '<=', "$to 23:59:59");
            }
        };

        $countTickets = function($status, $dateField = 'created_at') use ($baseQuery, $applyDateRange) {
            $query = clone $baseQuery;
            $query->where('status', $status);
            $applyDateRange($query, $dateField);
            return $query->count();
        };

        $newTickets = $countTickets('new');
        $closedTickets = $countTickets('closed', 'resolved_at');

        $openQuery = clone $baseQuery;
        $openQuery->where('status', '!=', 'closed');
        $applyDateRange($openQuery);
        $openTickets = $openQuery->count();

        $responsesQuery = Conversation::query()->where('conversation_type', 'response');

        if (array_filter($filters) || $agentIds !== null) {
            $responsesQuery->whereHas('ticket', function ($q) use ($filters, $agentIds) {
                foreach ($filters as $field => $value) {
                    if ($value) {
                        $q->where($field, $value);
                    }
                }
                if ($agentIds !== null) {
                    $q->whereIn('agent_id', $agentIds);
                }
            });
        }

        $applyDateRange($responsesQuery, 'created_at');
        $responses = $responsesQuery->count();

        $agentId = $filters['agent_id'];

        if ($agentId) {
            $repliesQuery = Conversation::query()
                ->where('person_id', $agentId)
                ->where('conversation_type', 'response');

            $applyDateRange($repliesQuery, 'created_at');
            $totalReplies = $repliesQuery->count();

            $stats = [
                'total_replies' => $totalReplies,
                'new_tickets' => $newTickets,
                'closed_tickets' => $closedTickets,
                'responses' => $responses,
                'open_tickets' => $openTickets,
            ];
        } else {
            $activeTickets = $countTickets('active');

            $stats = [
                'new_tickets' => $newTickets,
                'active_tickets' => $activeTickets,
                'closed_tickets' => $closedTickets,
                'responses' => $responses,
                'open_tickets' => $openTickets,
            ];
        }

        $labels = [
            'total_replies'  => __('Total Replies', 'fluent-support'),
            'new_tickets'    => __('New Tickets', 'fluent-support'),
            'active_tickets' => __('Active Tickets', 'fluent-support'),
            'closed_tickets' => __('Closed Tickets', 'fluent-support'),
            'responses'      => __('Responses', 'fluent-support'),
            'open_tickets'   => __('Open Tickets', 'fluent-support'),
        ];

        $overallReports = [];
        foreach ($stats as $key => $count) {
            $overallReports[$key] = [
                'title' => $labels[$key] ?? ucwords(str_replace('_', ' ', (string) $key)),
                'key' => $key,
                'count' => $count,
            ];
        }

        return ['overall_reports' => $overallReports];
    }

}
