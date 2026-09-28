<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\Models\Meta;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Notifications\MentionParser;
use FluentSupport\App\Services\Notifications\NotificationEventMap;
use FluentSupport\App\Services\Notifications\NotificationService;
use FluentSupport\App\Services\Notifications\RecipientResolver;
use FluentSupport\App\Services\Tickets\AgentTicketAccess;

class InternalNotificationHandler
{
    public function init()
    {
        add_action('fluent_support/response_added_by_agent', [$this, 'handleAgentReply'], 20, 3);
        add_action('fluent_support/response_added_by_agent', [$this, 'handleAgentMention'], 20, 3);
        add_action('fluent_support/note_added_by_agent', [$this, 'handleAgentMention'], 20, 3);
        add_action('fluent_support/agent_assigned_to_ticket', [$this, 'handleAgentAssignment'], 15, 4);
        add_action('fluent_support/response_added_by_customer', [$this, 'handleCustomerReply'], 20, 3);
        add_action('fluent_support/ticket_closed', [$this, 'handleTicketClosed'], 15, 2);
        add_action('fluent_support/ticket_reopen', [$this, 'handleTicketReopened'], 15, 2);
        add_action('fluent_support/workflow_triggered', [$this, 'handleWorkflowTriggered'], 15, 4);
    }

    public function handleAgentReply($response, $ticket, $person)
    {
        $recipientResolver = new RecipientResolver();
        $assignedAgent = $recipientResolver->resolveAssignedAgent($ticket);

        if (!$assignedAgent) {
            return;
        }

        $recipientIds = $recipientResolver->extractRecipientPersonIds([$assignedAgent]);

        if (!$recipientIds) {
            return;
        }

        (new NotificationService())->createNotification([
            'actor_id'        => $person->id,
            'ticket_id'       => $ticket->id,
            'conversation_id' => $response->id,
            'event_type'      => NotificationEventMap::AGENT_REPLIED,
            'channel'         => 'web',
            'payload'         => [
                'ticket_title'      => $ticket->title,
                'ticket_priority'   => $ticket->priority,
                'agent'             => $this->formatPerson($person),
                'conversation_type' => $response->conversation_type,
                'content_preview'   => $this->makeContentPreview($response->content)
            ]
        ], $recipientIds);
    }

    public function handleAgentMention($response, $ticket, $person)
    {
        $agentIds = (new MentionParser())->extractMentionIds($response->content);

        if (!$agentIds) {
            return;
        }

        $recipientResolver = new RecipientResolver();
        $recipients = $recipientResolver->resolveMentionedAgents($agentIds);
        $recipients = $this->filterTicketAccessibleAgents($recipients, $ticket);
        $recipientIds = $recipientResolver->extractRecipientPersonIds($recipients);

        if (!$recipientIds) {
            return;
        }

        $mentionedNames = array_values(array_map(
            fn($a) => trim("{$a->first_name} {$a->last_name}"),
            $recipients
        ));

        (new NotificationService())->createNotification([
            'actor_id'        => $person->id,
            'ticket_id'       => $ticket->id,
            'conversation_id' => $response->id,
            'event_type'      => NotificationEventMap::AGENT_MENTIONED,
            'channel'         => 'web',
            'payload'         => [
                'ticket_title'      => $ticket->title,
                'conversation_type' => $response->conversation_type,
                'mentioned_handles' => $mentionedNames,
                'content_preview'   => $this->makeContentPreview($response->content)
            ]
        ], $recipientIds);
    }

    public function handleAgentAssignment($assignedAgent, $ticket, $assigner, $previousAgentId = null)
    {
        if (!$assignedAgent || empty($assignedAgent->id)) {
            return;
        }

        if ((int) $ticket->agent_id !== (int) $assignedAgent->id) {
            return;
        }

        $accessibleAgents = $this->filterTicketAccessibleAgents([$assignedAgent], $ticket);

        if (!$accessibleAgents) {
            return;
        }

        $assignerId = (!empty($assigner) && !empty($assigner->id)) ? (int) $assigner->id : null;
        $recipientIds = (new RecipientResolver())->extractRecipientPersonIds($accessibleAgents);

        if (!$recipientIds) {
            return;
        }

        $eventType = $this->hasPreviousAssignment($previousAgentId)
            ? NotificationEventMap::TICKET_REASSIGNED
            : NotificationEventMap::TICKET_ASSIGNED;

        (new NotificationService())->createNotification([
            'actor_id'   => $assignerId,
            'ticket_id'  => $ticket->id,
            'event_type' => $eventType,
            'channel'    => 'web',
            'payload'    => [
                'ticket_title'    => $ticket->title,
                'ticket_priority' => $ticket->priority,
                'assignment_type' => $eventType === NotificationEventMap::TICKET_REASSIGNED ? 'reassignment' : 'assignment',
                'assigned_agent'  => $this->formatPerson($assignedAgent),
                'assigner'        => $this->formatPerson($assigner)
            ]
        ], $recipientIds);
    }

    public function handleCustomerReply($response, $ticket, $person)
    {
        $recipientResolver = new RecipientResolver();
        $assignedAgent = $recipientResolver->resolveAssignedAgent($ticket);

        if (!$assignedAgent) {
            return;
        }

        $recipientIds = $recipientResolver->extractRecipientPersonIds([$assignedAgent]);

        if (!$recipientIds) {
            return;
        }

        (new NotificationService())->createNotification([
            'actor_id'        => $person->id,
            'ticket_id'       => $ticket->id,
            'conversation_id' => $response->id,
            'event_type'      => NotificationEventMap::CUSTOMER_REPLIED,
            'channel'         => 'web',
            'payload'         => [
                'ticket_title'      => $ticket->title,
                'ticket_priority'   => $ticket->priority,
                'customer'          => $this->formatPerson($person),
                'conversation_type' => $response->conversation_type,
                'content_preview'   => $this->makeContentPreview($response->content)
            ]
        ], $recipientIds);
    }

    public function handleTicketClosed($ticket, $person)
    {
        $this->createStatusNotification($ticket, $person, NotificationEventMap::TICKET_CLOSED);
    }

    public function handleTicketReopened($ticket, $person)
    {
        $this->createStatusNotification($ticket, $person, NotificationEventMap::TICKET_REOPENED);
    }

    public function handleWorkflowTriggered($workflow, $ticket, $person = null, $context = [])
    {
        if (!$workflow || !$ticket || empty($ticket->id)) {
            return;
        }

        $actorId = (!empty($person) && !empty($person->id)) ? (int) $person->id : null;
        $recipientResolver = new RecipientResolver();
        $assignedAgent = $recipientResolver->resolveAssignedAgent($ticket);

        if (!$assignedAgent) {
            return;
        }

        $recipientIds = $recipientResolver->extractRecipientPersonIds([$assignedAgent]);

        if (!$recipientIds) {
            return;
        }

        $context = is_array($context) ? $context : [];

        (new NotificationService())->createNotification([
            'actor_id'   => $actorId,
            'ticket_id'  => $ticket->id,
            'event_type' => NotificationEventMap::WORKFLOW_TRIGGERED,
            'channel'    => 'web',
            'payload'    => [
                'ticket_title'   => $ticket->title,
                'ticket_priority'=> $ticket->priority,
                'workflow_id'    => !empty($workflow->id) ? (int) $workflow->id : null,
                'workflow_name'  => !empty($workflow->title) ? $workflow->title : __('Workflow', 'fluent-support'),
                'trigger_type'   => !empty($workflow->trigger_type) ? $workflow->trigger_type : null,
                'trigger_key'    => !empty($workflow->trigger_key) ? $workflow->trigger_key : null,
                'summary'        => $this->makeWorkflowSummary($workflow, $context),
                'actor'          => $this->formatPerson($person),
                'context'        => [
                    'source' => !empty($context['source']) ? sanitize_text_field($context['source']) : null,
                    'event'  => !empty($context['event']) ? sanitize_text_field($context['event']) : null
                ]
            ]
        ], $recipientIds);
    }

    protected function createStatusNotification($ticket, $person, $eventType)
    {
        $actorId = (!empty($person) && !empty($person->id)) ? (int) $person->id : null;
        $recipientResolver = new RecipientResolver();
        $assignedAgent = $recipientResolver->resolveAssignedAgent($ticket);

        if (!$assignedAgent) {
            return;
        }

        $recipientIds = $recipientResolver->extractRecipientPersonIds([$assignedAgent]);

        if (!$recipientIds) {
            return;
        }

        (new NotificationService())->createNotification([
            'actor_id'   => $actorId,
            'ticket_id'  => $ticket->id,
            'event_type' => $eventType,
            'channel'    => 'web',
            'payload'    => [
                'ticket_title'    => $ticket->title,
                'ticket_priority' => $ticket->priority,
                'status'          => $eventType === NotificationEventMap::TICKET_CLOSED ? 'closed' : 'reopened',
                'actor'           => $this->formatPerson($person)
            ]
        ], $recipientIds);
    }

    protected function hasPreviousAssignment($previousAgentId)
    {
        return (int) $previousAgentId > 0;
    }

    protected function filterTicketAccessibleAgents($agents, $ticket)
    {
        if (empty($agents)) {
            return [];
        }

        $agentIds = [];
        foreach ($agents as $agent) {
            $agentIds[] = $agent->id;
        }

        $metas = Meta::where('object_type', 'person_meta')
            ->where('key', 'agent_restrictions')
            ->whereIn('object_id', $agentIds)
            ->get();

        $restrictionsMap = [];
        foreach ($metas as $meta) {
            $restrictionsMap[$meta->object_id] = Helper::safeUnserialize($meta->value) ?: [];
        }

        $ticketAccess    = new AgentTicketAccess();
        $accessibleAgents = [];

        foreach ($agents as $agent) {
            $restrictions = $restrictionsMap[$agent->id] ?? [];
            if ($ticketAccess->canAccess($agent, $ticket, $restrictions)) {
                $accessibleAgents[] = $agent;
            }
        }

        return $accessibleAgents;
    }

    protected function makeContentPreview($content)
    {
        return wp_html_excerpt(wp_strip_all_tags((string) $content), 180, '...');
    }

    protected function makeWorkflowSummary($workflow, array $context = [])
    {
        $workflowName = !empty($workflow->title) ? $workflow->title : __('Workflow', 'fluent-support');
        $source = !empty($context['source']) ? sanitize_text_field($context['source']) : 'workflow';

        if ($source === 'manual') {
            return sprintf(__('Manual workflow "%s" was run', 'fluent-support'), $workflowName);
        }

        return sprintf(__('Automation workflow "%s" was triggered', 'fluent-support'), $workflowName);
    }

    protected function formatPerson($person)
    {
        if (!$person) {
            return null;
        }

        return [
            'id'          => (int) $person->id,
            'person_type' => $person->person_type,
            'name'        => $person->full_name ?: $person->email,
            'email'       => $person->email
        ];
    }
}
