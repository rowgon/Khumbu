<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Attachment;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Customer;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Support\Arr;
use FluentSupport\App\Http\Requests\TicketRequest;
use FluentSupport\App\Http\Requests\TicketResponseRequest;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\FluentCRMServices;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\ProfileInfoService;
use FluentSupport\App\Services\TicketHelper;
use FluentSupport\App\Services\TicketQueryService;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Tickets\AgentTicketAccess;
use FluentSupport\App\Services\Tickets\ResponseService;
use FluentSupport\App\Models\AgentGroup;
use FluentSupport\App\Services\Tickets\TicketService;
use FluentSupport\App\Services\Integrations\FluentBooking\FluentBookingService;

/**
 *  TicketController class for REST API related to ticket
 * This class is responsible for getting / inserting/ modifying data for all request related to ticket
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class TicketController extends Controller
{
    /**
     * This `me` method will return the current user profile info
     * @param Request $request
     * @return array
     */
    public function me(Request $request)
    {
        $user = wp_get_current_user();
        $requestData = $request->all();
        $sanitizedRequest = [];
        foreach ($requestData as $key => $value) {
            if (is_array($value)) {
                $sanitizedRequest[$key] = map_deep($value, 'sanitize_text_field');
            } else {
                $sanitizedRequest[$key] = sanitize_text_field($value);
            }
        }

        $settings = [
            'user_id'     => $user->ID,
            'email'       => $user->user_email,
            'person'      => Helper::getAgentByUserId($user->ID),
            'permissions' => PermissionManager::currentUserPermissions(),
            'request'     => $sanitizedRequest
        ];

        if ($request->getSafe('with_portal_settings', 'sanitize_text_field')) {
            $mimeHeadings = Helper::getAcceptedMimeHeadings();
            $businessSettings = (new \FluentSupport\App\Services\EmailNotification\Settings())->globalBusinessSettings();
            $maxFileSize = absint($businessSettings['max_file_size']);

            $portalSettings = [
                'support_products'           => \FluentSupport\App\Models\Product::select(['id', 'title'])->get(),
                'customer_ticket_priorities' => Helper::customerTicketPriorities(),
                'has_file_upload'            => !!Helper::ticketAcceptedFileMiles(),
                'has_rich_text_editor'       => true,
                'max_file_size'              => $maxFileSize,
                'mime_headings'              => $mimeHeadings
            ];

            $portalSettings = apply_filters('fluent_support/customer_portal_vars', $portalSettings);
            $settings['portal_settings'] = $portalSettings;
        }

        return $settings;
    }

    /**
     * index method will return the list of ticket based on the selected filter
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        //Selected filter type, either simple or Advanced
        $filterType = $request->getSafe('filter_type', 'sanitize_text_field', 'simple');

        /*Prepare Query Arguments*/
        $queryArgs = [
            'with'        => [],
            'filter_type' => $filterType,
            'sort_by'     => sanitize_sql_orderby($request->getSafe('order_by', 'sanitize_text_field', 'id')),
            'sort_type'   => $request->getSafe('order_type', 'sanitize_text_field', 'DESC') == 'DESC' ? 'DESC' : 'ASC',
        ];

        //If the selected filter type is advanced
        if ($filterType == 'advanced') {
            $advanced_filters = map_deep($request->get('advanced_filters', []), 'sanitize_text_field');
            //Get the selected query params for advanced filter
            $queryArgs['filters_groups_raw'] = json_decode($advanced_filters, true);
        } else {
            //Selected filter type is simple
            $queryArgs['simple_filters'] = map_deep($request->get('filters', []), 'sanitize_text_field');
            $queryArgs['search'] = trim($request->getSafe('search', 'sanitize_text_field', ''));

            if ($customerId = $request->getSafe('customer_id', 'intval')) {
                $queryArgs['customer_id'] = $customerId;
            }
        }
        /*End Prepare Query Arguments*/

        $ticketsModel = (new TicketQueryService($queryArgs))->getModel();

        $ticketsModel = $ticketsModel->with([
            'customer'         => function ($query) {
                $query->select(['first_name', 'last_name', 'email', 'id', 'avatar']);
            }, 'agent'         => function ($query) {
                $query->select(['first_name', 'last_name', 'email', 'avatar', 'id']);
            },
            'mailbox',
            'product',
            'tags',
            'preview_response' => function ($query) {
                $query->latest('id');
            }
        ]);

        // apply filters by access level
        do_action_ref_array('fluent_support/tickets_query_by_permission_ref', [&$ticketsModel, false]);

        $tickets = $ticketsModel->paginate();

        $perPage = $request->getSafe('per_page', 'intval', 15);

        // Load live activity for small page sizes (board/kanban view)
        if ($perPage < 15) {
            TicketHelper::loadBatchLiveActivities($tickets);
        }

        return [
            'tickets' => $tickets
        ];
    }

    /**
     * createTicket method will create new ticket as well as customer or WP user
     * @param TicketRequest $request
     * @return array
     */
    public function createTicket(TicketRequest $request)
    {
        try {
            //Sanitize and validate request data via TicketRequest
            $data = $request->sanitize();
            $ticketData = $data['ticket'];
            $maybeNewCustomer = Arr::get($data, 'newCustomer', []);

            //Include attachments if provided
            if (!empty($data['attachments'])) {
                $ticketData['attachments'] = $data['attachments'];
            }

            /*
             * If customer_id is not provided, attempt to create a new customer
             * This handles WP user creation and customer creation
             */
            if (empty($ticketData['customer_id'])) {
                $createdUserId = false;

                //If user selected create WP user during ticket creation
                if (Arr::get($ticketData, 'create_wp_user') == 'yes' && !empty($maybeNewCustomer['username'])) {
                    //Check if username already in use, if not create new user
                    if (!username_exists($maybeNewCustomer['username'])) {
                        $authController = new AuthController();
                        $createdUserId = $authController->createUser($maybeNewCustomer);
                        $authController->maybeUpdateUser($createdUserId, $maybeNewCustomer);
                    }
                }

                $email = Arr::get($maybeNewCustomer, 'email');
                if (!$email || !is_email($email)) {
                    return $this->sendError([
                        'message' => __('A valid email is required to create a ticket', 'fluent-support')
                    ]);
                }

                //Check if customer already exists by email
                $existingCustomer = Customer::where('email', $email)->first();

                if ($existingCustomer) {
                    $ticketData['customer_id'] = $existingCustomer->id;
                } else {
                    //Create the customer now
                    $customerData = Arr::only($maybeNewCustomer, (new Customer())->getFillable());
                    $customerData['user_id'] = $createdUserId;
                    $customerData = array_filter($customerData);

                    $createCustomer = Customer::create($customerData);

                    do_action('fluent_support/customer_created', $createCustomer);

                    if (!$createCustomer) {
                        return $this->sendError([
                            'message' => __('Customer could not be created', 'fluent-support')
                        ]);
                    }

                    $ticketData['customer_id'] = $createCustomer->id;
                }
            }

            //Get customer information from db
            $customer = Customer::findOrFail($ticketData['customer_id']);

            //Sanitize, store ticket, handle attachments, fire hooks
            $createdTicket = (new TicketService())->storeTicket($ticketData, $customer);

            return [
                'message' => __('Ticket has been created successfully', 'fluent-support'),
                'ticket'  => $createdTicket
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * getTicket method will return ticket information by ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function getTicket(Request $request, $ticket_id)
    {
        try {
            //Get logged in agent information
            $agent = Helper::getAgentByUserId();

            $ticketWith = $request->get('with');
            $ticketWith = is_array($ticketWith) ? map_deep($ticketWith, 'sanitize_text_field') : null;

            if (!$ticketWith) {
                $ticketWith = ['customer', 'agent', 'product', 'mailbox', 'tags', 'attachments' => function ($q) {
                    $q->where('status', 'active');
                }];
            }

            //Get ticket by id
            $ticket = Ticket::with($ticketWith)->findOrFail($ticket_id);

            //Eager load responses with their nested relations to avoid N+1 queries
            $ticket->load(['responses' => function ($q) {
                $q->with([
                    'person',
                    'ccinfo',
                    'attachments' => function ($q) {
                        $q->where('status', 'active');
                    }
                ]);
            }]);

            //Check if ticket is in a restricted mailbox
            $restrictedBusinessBoxes = PermissionManager::getRestrictedMailboxIds();

            if (in_array($ticket->mailbox_id, $restrictedBusinessBoxes)) {
                throw new \Exception(esc_html__('Ticket cannot be fetched due to restricted mailbox', 'fluent-support'));
            }

            $this->ensureCanAccessTicket($ticket);

            //If ticket has customer, set custom fields and profile url
            if ($ticket->customer) {
                $customFieldsKey = apply_filters('fluent_support/custom_registration_form_fields_key', Helper::getBusinessSettings('custom_registration_form_field'));
                $ticket->customer->custom_field_keys = $customFieldsKey;

                if ($ticket->customer->user_id) {
                    $customFieldKeysUsingHook = apply_filters('fluent_support/custom_registration_form_fields_key', []);
                    if (!empty($customFieldKeysUsingHook)) {
                        $allUserMeta = get_user_meta($ticket->customer->user_id);
                        foreach ($customFieldKeysUsingHook as $key) {
                            if (isset($allUserMeta[$key][0]) && $allUserMeta[$key][0]) {
                                $ticket->customer->$key = $allUserMeta[$key][0];
                            }
                        }
                    }
                }

                $ticket->customer->profile_edit_url = $ticket->customer->getUserProfileEditUrl();
            }

            //If ticket is closed, load closed by person
            if ($ticket->status == 'closed') {
                $ticket->load('closed_by_person');
            }

            //Load agent feedback ratings if pro is active and feature is enabled
            if (defined('FLUENTSUPPORTPRO_PLUGIN_VERSION') && Helper::isAgentFeedbackEnabled()) {
                $responseIds = $ticket->responses->pluck('id')->toArray();
                $feedbacks = Meta::where('object_type', 'conversation_meta')
                    ->where('key', 'agent_feedback_ratings')
                    ->whereIn('object_id', $responseIds)
                    ->get()
                    ->keyBy('object_id');

                foreach ($ticket->responses as $response) {
                    if ($feedbacks->has($response->id)) {
                        $response->agent_feedback = $feedbacks->get($response->id)->value;
                    }
                }
            }

            $contents = ['ticket' => $ticket->content];
            foreach ($ticket->responses as $response) {
                $contents['response_' . $response->id] = $response->content;
            }

            $contents = Helper::refreshSignedAttachmentUrlsInContents($contents, $ticket->id);
            $ticket->content = $contents['ticket'];

            //Format response content
            foreach ($ticket->responses as $response) {
                $responseKey = 'response_' . $response->id;
                if (isset($contents[$responseKey])) {
                    $response->content = $contents[$responseKey];
                }

                $responseContent = apply_filters(
                    'fluent_support/response_content_before_render',
                    $response->content,
                    $response,
                    $ticket
                );

                if ($response->conversation_type === 'note') {
                    $responseContent = wpautop($responseContent, false);
                } else {
                    $responseContent = links_add_target(make_clickable(wpautop($responseContent, false)));
                }


                $response->content = apply_filters(
                    'fluent_support/response_content_after_render',
                    $responseContent,
                    $response,
                    $ticket
                );

                if (!empty($response->ccinfo)) {
                    $val = Helper::safeUnserialize($response->ccinfo->value);
                    if (isset($val['cc_email']) && !empty($val['cc_email'])) {
                        $response->cc_info = $val['cc_email'];
                    } else {
                        $response->cc_info = '';
                    }
                } else {
                    $response->cc_info = '';
                }
            }

            $ticketContent = apply_filters(
                'fluent_support/ticket_content_before_render',
                $ticket->content,
                $ticket
            );

            $ticketContent = links_add_target(make_clickable(wpautop($ticketContent, false)));

            $ticket->content = apply_filters(
                'fluent_support/ticket_content_after_render',
                $ticketContent,
                $ticket
            );

            //Get last activity by agent
            $ticket->live_activity = TicketHelper::getActivity($ticket->id, $agent->id);

            //Get all carbon copy customer
            $ccInfo = $ticket->getSettingsValue('cc_email', []);
            $ticket->carbon_copy = !empty($ccInfo) ? implode(', ', $ccInfo) : '';

            if (defined('FLUENTSUPPORTPRO')) {
                $ticket->custom_fields = $ticket->customData('admin', true);
            }

            // Load agent info if ticket was created on behalf of customer
            if ($ticket->created_by) {
                $ticket->load('created_by_person');
                if ($ticket->created_by_person) {
                    $isAgentInitiated = strpos($ticket->content, __(' initialized this ticket', 'fluent-support')) !== false;
                    $ticket->created_by_agent = [
                        'id'        => $ticket->created_by_person->id,
                        'full_name' => $ticket->created_by_person->full_name,
                        'agent_initiated' => $isAgentInitiated,
                    ];
                }
            }

            $data = [
                'ticket'    => $ticket,
                'responses' => $ticket->responses,
                'agent_id'  => $agent->id
            ];

            if (defined('FLUENTSUPPORTPRO') && $ticket->watchers) {
                $data['watchers'] = TicketHelper::getWatchers($ticket->watchers);
            }

            $withData = $request->get('with_data', null);
            $withDataArray = is_array($withData) ? map_deep($withData, 'sanitize_text_field') : [];

            if (defined('FLUENTCRM') && in_array('fluentcrm_profile', $withDataArray)) {
                $data['fluentcrm_profile'] = Helper::getFluentCrmContactData($ticket->customer);
            }

            return $data;
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getMentionableAgents(Request $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            if (in_array($ticket->mailbox_id, PermissionManager::getRestrictedMailboxIds())) {
                throw new \Exception(esc_html__('Ticket cannot be fetched due to restricted mailbox', 'fluent-support'));
            }

            $this->ensureCanAccessTicket($ticket);

            $search = trim($request->getSafe('search', 'sanitize_text_field', ''));
            $limit = min(max(absint($request->getSafe('limit', 'intval', 20)), 1), 50);

            return [
                'agents' => $this->getMentionableAgentList($ticket, $search, $limit)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    protected function getMentionableAgentList($ticket, $search, $limit)
    {
        $allAgents = Agent::select(['id', 'first_name', 'last_name', 'email', 'user_id'])
            ->mentionBy($search)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        if ($allAgents->isEmpty()) {
            return [];
        }

        $restrictions = $this->getAgentRestrictionsMap($allAgents->pluck('id')->all());
        $ticketAccess = new AgentTicketAccess();
        $results = [];

        foreach ($allAgents as $agent) {
            if (!$ticketAccess->canAccess($agent, $ticket, $restrictions[$agent->id] ?? [])) {
                continue;
            }

            $results[] = [
                'id'         => strval($agent->id),
                'first_name' => $agent->first_name,
                'last_name'  => $agent->last_name,
                'email'      => $agent->email,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    protected function getAgentRestrictionsMap(array $agentIds)
    {
        if (!$agentIds) {
            return [];
        }

        $metas = Meta::where('object_type', 'person_meta')
            ->where('key', 'agent_restrictions')
            ->whereIn('object_id', $agentIds)
            ->get();

        $restrictions = [];
        foreach ($metas as $meta) {
            $restrictions[$meta->object_id] = Helper::safeUnserialize($meta->value) ?: [];
        }

        return $restrictions;
    }

    /**
     * createResponse method will create response by agent for the ticket
     * @param Request $request
     * @param Ticket $ticket
     * @param int $ticket_id
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function createResponse(TicketResponseRequest $request, $ticket_id)
    {
        $data = $request->sanitize();

        try {
            $convoType = Arr::get($data, 'conversation_type', 'response');
            $isDraft = $convoType === 'draft_response';

            if (!$isDraft) {
                $this->ensureCanManageTickets();
            }

            //Get logged-in agent information
            $agent = Helper::getAgentByUserId();

            if (!$agent) {
                return $this->sendError([
                    'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
                ]);
            }

            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $responseData = (new ResponseService())->createResponse($data, $agent, $ticket);

            $responseData['response']->content = Helper::refreshSignedAttachmentUrls($responseData['response']->content, $ticket->id);
            $responseData['response']->load([
                'attachments' => function ($q) {
                    $q->where('status', 'active');
                }
            ]);
            $responseData['response']->content = wp_specialchars_decode(wpautop($responseData['response']->content, false));

            return [
                'message'     => __('Response has been added', 'fluent-support'),
                'response'    => $responseData['response'],
                'ticket'      => $responseData['ticket'],
                'update_data' => $responseData['update_data']
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getFluentBookingEventTypes()
    {
        try {
            // All FluentBooking endpoints require manage permission; view-only agents cannot call a meeting.
            $this->ensureCanManageTickets();

            $service = new FluentBookingService();
            $eventTypes = $service->getEventTypes();

            return [
                'status'      => $service->getStatus($eventTypes),
                'event_types' => $eventTypes
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function createFluentBookingLink(Request $request, $ticket_id)
    {
        try {
            // All FluentBooking endpoints require manage permission; view-only agents cannot call a meeting.
            $this->ensureCanManageTickets();

            $ticket = Ticket::with('customer')->findOrFail($ticket_id);

            // Enforces per-ticket visibility (e.g. own-tickets-only agents cannot access unassigned tickets).
            $this->ensureCanAccessTicket($ticket);

            $eventId = $request->getSafe('event_type_id', 'intval');

            if (!$eventId) {
                throw new \Exception(esc_html__('Please select a FluentBooking event type.', 'fluent-support'));
            }

            return (new FluentBookingService())->createBookingLink(
                $ticket,
                $eventId,
                $request->getSafe('message', 'wp_kses_post'),
                $request->get('selected_slots', []),
                $request->getSafe('timezone', 'sanitize_text_field', '')
            );
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getFluentBookingAvailability(Request $request, $ticket_id)
    {
        try {
            // All FluentBooking endpoints require manage permission; view-only agents cannot call a meeting.
            $this->ensureCanManageTickets();

            $ticket = Ticket::with('customer')->findOrFail($ticket_id);

            // Enforces per-ticket visibility (e.g. own-tickets-only agents cannot access unassigned tickets).
            $this->ensureCanAccessTicket($ticket);

            $eventId = $request->getSafe('event_type_id', 'intval');

            if (!$eventId) {
                throw new \Exception(esc_html__('Please select a FluentBooking event type.', 'fluent-support'));
            }

            return [
                'availability' => (new FluentBookingService())->getAvailabilitySlots(
                    $eventId,
                    $request->getSafe('range', 'sanitize_key', 'next_3_days'),
                    $request->getSafe('timezone', 'sanitize_text_field'),
                    $request->getSafe('duration', 'intval'),
                    $ticket,
                    $request->get('selected_dates', []),
                    $request->getSafe('calendar_month', 'sanitize_text_field', '')
                )
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getFluentBookingMeetings($ticket_id)
    {
        try {
            // All FluentBooking endpoints require manage permission; view-only agents cannot call a meeting.
            $this->ensureCanManageTickets();

            $ticket = Ticket::with('customer')->findOrFail($ticket_id);

            // Enforces per-ticket visibility (e.g. own-tickets-only agents cannot access unassigned tickets).
            $this->ensureCanAccessTicket($ticket);

            return [
                'meetings' => (new FluentBookingService())->getTicketMeetings($ticket)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * createDraft method will create draft by agent for the ticket
     * @param Request $request
     * @param Ticket $ticket
     * @param int $ticket_id
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function createOrUpdatDraft(TicketResponseRequest $request, $ticket_id)
    {
        $data = $request->sanitize();

        try {
            //Get logged-in agent information
            $agent = Helper::getAgentByUserId();

            if (!$agent) {
                return $this->sendError([
                    'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
                ]);
            }

            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $key = 'ticket_no_' . $ticket_id . '_agent_id_' . $agent->id . '_response_draft';
            $previousDraft = Meta::where('key', $key)->first();

            if ($data['draftID'] || $previousDraft) {
                Meta::where('key', $key)->update([
                    'value' => maybe_serialize($data)
                ]);

                return [
                    'message' => __('Draft has been updated', 'fluent-support'),
                    'draftID' => $data['draftID']
                ];
            }

            $draftID = Meta::insertGetId([
                'object_type' => '_fs_auto_draft',
                'object_id'   => $ticket_id,
                'key'         => $key,
                'value'       => maybe_serialize($data)
            ]);

            return [
                'message' => __('Draft has been added', 'fluent-support'),
                'draftID' => $draftID
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getDraft($ticket_id)
    {
        try {
            //Get logged-in agent information
            $agent = Helper::getAgentByUserId();

            if (!$agent) {
                return $this->sendError([
                    'message' => __('Sorry, You do not have permission. Please add yourself as support agent first', 'fluent-support')
                ]);
            }

            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $key = 'ticket_no_' . $ticket_id . '_agent_id_' . $agent->id . '_response_draft';

            $draft = Meta::where([
                'object_type' => '_fs_auto_draft',
                'key'         => $key,
            ])->first();

            if ($draft) {
                $draft->value = Helper::safeUnserialize($draft->value);
            }

            return [
                'draft' => $draft
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function deleteDraft($draft_id)
    {
        $draft_id = intval($draft_id);

        try {
            $agent = Helper::getAgentByUserId();

            if (!$agent) {
                return $this->sendError([
                    'message' => __('You do not have permission to perform this action', 'fluent-support'),
                ]);
            }

            $draft = Meta::where('id', $draft_id)
                ->where('object_type', '_fs_auto_draft')
                ->first();

            if (!$draft) {
                return $this->sendError([
                    'message' => __('Draft not found', 'fluent-support'),
                ]);
            }

            // Authorize the ticket this draft belongs to (closes the mailbox/visibility
            // dimension for managers deleting other agents' drafts).
            $ticket = Ticket::findOrFail($draft->object_id);

            $this->ensureCanAccessTicket($ticket);

            // Verify ownership: draft key contains agent_id, only managers can delete others' drafts
            $isOwnDraft = strpos($draft->key, '_agent_id_' . $agent->id . '_') !== false;

            if (!$isOwnDraft && !PermissionManager::canManageTickets()) {
                return $this->sendError([
                    'message' => __('You do not have permission to delete this draft', 'fluent-support'),
                ]);
            }

            $draft->delete();

            return [
                'message' => __('Discard draft successfully', 'fluent-support'),
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * getTicketWidgets method generate additional information for a ticket by  customer
     * @param Ticket $ticket
     * @param $ticket_id
     * @return array
     */
    public function getTicketWidgets(Request $request, $ticket_id)
    {
        try {
            //Get ticket with customer by ticket id
            $ticket = Ticket::with('customer')->findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $perPage = max(1, absint(apply_filters('fluent_support/previous_ticket_widgets_limit', 5)));
            $page    = max(1, absint($request->get('page', 1)));
            $offset  = ($page - 1) * $perPage;

            $baseQuery = Ticket::where('id', '!=', $ticket_id)
                ->where('customer_id', $ticket->customer_id);

            (new AgentTicketAccess())->applyAccessScope($baseQuery);

            $total = $baseQuery->count();

            $otherTickets = (clone $baseQuery)
                ->select(['id', 'title', 'status', 'created_at'])
                ->latest('id')
                ->limit($perPage)
                ->offset($offset)
                ->get();

            $response = [
                'other_tickets'       => $otherTickets,
                'other_tickets_total' => $total,
                'other_tickets_more'  => ($offset + $perPage) < $total,
            ];

            if (in_array('extra_widgets', $request->get('with', []))) {
                $response['extra_widgets'] = ProfileInfoService::getProfileExtraWidgets($ticket->customer);
            }

            return $response;
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * updateTicketProperty method will update ticket property
     * @param Request $request
     * @param Ticket $ticket
     * @param $ticket_id
     * @return array
     */
    public function updateTicketProperty(Request $request, $ticket_id)
    {
        try {
            $assigner = Helper::getAgentByUserId();
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $propName = $request->getSafe('prop_name', 'sanitize_text_field');
            $propValue = $request->getSafe('prop_value', 'sanitize_text_field');

            // FS-SEC-007: this generic endpoint may only touch a fixed set of
            // ticket columns. Previously prop_name was assigned straight onto the
            // model ($ticket->{$propName} = $propValue), letting a caller rewrite
            // ownership, mailbox, privacy, hash, serial_number, created_by and
            // other sensitive columns and bypass $fillable entirely. Every
            // property is now allowlisted and its value validated/capability-
            // gated below; anything else is rejected outright.
            if (!in_array($propName, $this->updatableTicketProperties(), true)) {
                throw new \Exception(esc_html__('This ticket property cannot be updated.', 'fluent-support'), 403);
            }

            $propValue = $this->sanitizeTicketProperty($ticket, $propName, $propValue);

            $prevValue = $ticket->{$propName};

            if ($propName && $propValue !== null && $prevValue != $propValue) {
                $ticket->{$propName} = $propValue;
                $ticket->save();

                // Log an internal note for status changes so the activity is
                // traceable, mirroring the close/reopen flows.
                if ($propName === 'status') {
                    $statuses = Helper::ticketStatuses();
                    $fromLabel = isset($statuses[$prevValue]) ? $statuses[$prevValue] : $prevValue;
                    $toLabel = isset($statuses[$propValue]) ? $statuses[$propValue] : $propValue;

                    $internalNote = sprintf(
                        /* translators: 1: previous status, 2: new status */
                        __('Ticket status changed from %1$s to %2$s', 'fluent-support'),
                        esc_html($fromLabel),
                        esc_html($toLabel)
                    );

                    Conversation::create([
                        'ticket_id'         => $ticket->id,
                        'person_id'         => $assigner->id,
                        'conversation_type' => 'internal_info',
                        'content'           => $internalNote
                    ]);
                }
            }

            $updateData = [];

            if ($propName == 'product_id') {
                $ticket->load('product');
                $updateData['product'] = $ticket->product;
            } else if ($propName == 'agent_id') {
                $previousAgentId = (int) $prevValue;
                $ticket->load('agent');
                $updateData['agent'] = $ticket->agent;
                $updateData['assigner'] = (new TicketService())->onAgentChange($ticket, $assigner);
                if ($prevValue != $ticket->{$propName}) {
                    do_action('fluent_support/agent_assigned_to_ticket', $ticket->agent, $ticket, $assigner, $previousAgentId);
                }
            }

            $message = sprintf(
                /* translators: %s: The name of the property that was updated */
                __('%s has been updated', 'fluent-support'),
                esc_html(str_replace('_', ' ', ucwords((string) $propName)))
            );

            return [
                'message'     => $message,
                'update_data' => $updateData
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * The only ticket columns that may be changed through updateTicketProperty.
     * This mirrors exactly what the admin UI edits (agent, title, mailbox,
     * product, status and the two priority fields). Ownership, audit,
     * public-identifier and other sensitive columns are intentionally absent
     * and must go through their dedicated workflows (FS-SEC-007).
     *
     * @return array
     */
    protected function updatableTicketProperties()
    {
        return [
            'agent_id',
            'title',
            'mailbox_id',
            'product_id',
            'status',
            'priority',
            'client_priority',
        ];
    }

    /**
     * Validate and normalize a single ticket-property update. Each allowlisted
     * property is checked against its own value domain and capability, so a
     * caller can neither set an out-of-range value nor perform a change the UI
     * gates behind a stronger permission (FS-SEC-007).
     *
     * @param Ticket $ticket
     * @param string $propName  Already confirmed to be in the allowlist.
     * @param string $propValue Raw (text-sanitized) value from the request.
     * @return mixed Normalized value ready to assign to the model.
     * @throws \Exception When the value is invalid or the caller lacks permission.
     */
    protected function sanitizeTicketProperty(Ticket $ticket, $propName, $propValue)
    {
        switch ($propName) {
            case 'title':
                $propValue = trim(sanitize_text_field($propValue));
                if ($propValue === '') {
                    throw new \Exception(esc_html__('Ticket title cannot be empty.', 'fluent-support'), 422);
                }
                return $propValue;

            case 'status':
                // Mirror the ticket-view status dropdown, which is built from
                // changeable_ticket_statuses. The dropdown submits the group
                // KEY as the status value (getTicketStatus in ViewTicket.vue
                // keys the options by group name and el-option binds :value to
                // that key), and only groups with a non-empty value list are
                // shown. Validate against those same keys so the endpoint honors
                // the fluent_support/changeable_ticket_statuses filter exactly.
                $allowedStatuses = [];
                foreach (Helper::changeableTicketStatuses() as $statusKey => $statusGroup) {
                    if (!empty($statusGroup)) {
                        $allowedStatuses[] = $statusKey;
                    }
                }

                if (!in_array($propValue, $allowedStatuses, true)) {
                    throw new \Exception(esc_html__('Invalid ticket status.', 'fluent-support'), 422);
                }
                return $propValue;

            case 'priority':
                if (!array_key_exists($propValue, Helper::adminTicketPriorities())) {
                    throw new \Exception(esc_html__('Invalid ticket priority.', 'fluent-support'), 422);
                }
                return $propValue;

            case 'client_priority':
                if (!array_key_exists($propValue, Helper::customerTicketPriorities())) {
                    throw new \Exception(esc_html__('Invalid client priority.', 'fluent-support'), 422);
                }
                return $propValue;

            case 'product_id':
                $productId = (int) $propValue;
                if (!$productId || !Product::where('id', $productId)->exists()) {
                    throw new \Exception(esc_html__('Invalid product.', 'fluent-support'), 422);
                }
                return $productId;

            case 'agent_id':
                if (!PermissionManager::currentUserCan('fst_assign_agents')) {
                    throw new \Exception(esc_html__('Permission denied to assign agent', 'fluent-support'), 403);
                }

                $agentId = (int) $propValue;
                $agent = Agent::findOrFail($agentId);
                $restrictedBoxes = (new AgentTicketAccess())->getRestrictedMailboxIds($agent);

                if (in_array((int) $ticket->mailbox_id, $restrictedBoxes, true)) {
                    throw new \Exception(esc_html__('Agent is restricted for this mailbox ticket', 'fluent-support'), 403);
                }
                return $agentId;

            case 'mailbox_id':
                // The admin UI only exposes the mailbox switcher to agents with
                // fst_manage_settings; enforce the same gate on the API so the
                // permission can't be bypassed by calling the endpoint directly.
                if (!PermissionManager::currentUserCan('fst_manage_settings')) {
                    throw new \Exception(esc_html__('Permission denied to move this ticket to another mailbox.', 'fluent-support'), 403);
                }

                $mailboxId = (int) $propValue;
                $restrictedBoxes = array_map('intval', PermissionManager::getRestrictedMailboxIds());

                if (!MailBox::where('id', $mailboxId)->exists() || in_array($mailboxId, $restrictedBoxes, true)) {
                    throw new \Exception(esc_html__('Invalid or restricted mailbox.', 'fluent-support'), 422);
                }

                // Preserve the agent/mailbox compatibility invariant that the
                // agent_id branch enforces on assignment: a ticket must not be
                // moved into a mailbox its currently assigned agent is restricted
                // from, which would otherwise persist an assignment the assign
                // flow would have rejected.
                if ($ticket->agent_id) {
                    $assignedAgent = Agent::find($ticket->agent_id);
                    if ($assignedAgent) {
                        $agentRestrictedBoxes = (new AgentTicketAccess())->getRestrictedMailboxIds($assignedAgent);
                        if (in_array($mailboxId, $agentRestrictedBoxes, true)) {
                            throw new \Exception(esc_html__('The assigned agent is restricted from the selected mailbox. Reassign the ticket before moving it.', 'fluent-support'), 403);
                        }
                    }
                }
                return $mailboxId;
        }

        // Unreachable: updateTicketProperty already rejected non-allowlisted
        // properties before calling this method. Fail closed regardless.
        throw new \Exception(esc_html__('This ticket property cannot be updated.', 'fluent-support'), 403);
    }

    /**
     * closeTicket method close the ticket by id
     * @param Ticket $ticket
     * @param int $ticket_id
     * @return array
     */
    public function closeTicket(Request $request, $ticket_id)
    {
        try {
            $agent = Helper::getAgentByUserId();
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $closeSilently = $request->getSafe('close_ticket_silently', 'sanitize_text_field');

            return [
                'message' => __('Ticket has been closed', 'fluent-support'),
                'ticket'  => (new TicketService())->close($ticket, $agent, '', $closeSilently)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * reOpenTicket method will reopen a closed ticket
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function reOpenTicket($ticket_id)
    {
        try {
            $agent = Helper::getAgentByUserId();
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            return [
                'message' => __('Ticket has been opened again', 'fluent-support'),
                'ticket'  => (new TicketService())->reopen($ticket, $agent)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * doBulkActions method is responsible for bulk action
     * This function will get ticket ids and action as parameter and perform action based on the selection
     * @param Request $request
     * @param Ticket $ticket
     * @return array|string[]|void
     * @throws \Exception
     */
    public function doBulkActions(Request $request)
    {
        try {
            $action = $request->getSafe('bulk_action', 'sanitize_text_field');
            $ticketIds = array_map('intval', $request->get('ticket_ids', null, []));

            $agent = Helper::getAgentByUserId();
            $query = Ticket::whereIn('id', $ticketIds);

            //Scope selected tickets to what the agent can access, matching the
            //per-ticket ensureCanAccessTicket() check on the single-ticket routes
            (new AgentTicketAccess())->applyAccessScope($query, $agent);

            //If bulk action is close tickets
            if ($action == 'close_tickets') {
                $tickets = $query->get();
                $tickets->each(function ($ticket) use ($agent) {
                    (new TicketService())->close($ticket, $agent);
                });

                return [
                    'message' => sprintf(
                        /* translators: %d represents the number of closed tickets. */
                        __('%d tickets have been closed.', 'fluent-support'),
                        count($tickets)
                    )
                ];
            } else if ($action == 'delete_tickets') {
                $tickets = $query->get();
                $ticketService = new TicketService();

                foreach ($tickets as $ticket) {
                    $ticketService->deleteTicket($ticket, $agent);
                }

                return [
                    'message' => sprintf(
                        /* translators: %d is the number of tickets that were deleted */
                        __('%d tickets have been deleted', 'fluent-support'),
                        count($tickets)
                    )
                ];
            } else if ($action == 'assign_agent') {
                if (!$request->has('agent_id')) {
                    throw new \Exception(esc_html__('agent_id param is required', 'fluent-support'));
                }

                $assignAgent = Agent::findOrFail($request->getSafe('agent_id', 'intval'));

                $query->where(function ($q) use ($assignAgent) {
                    $q->where('agent_id', '!=', $assignAgent->id)
                        ->orWhereNull('agent_id');
                });

                $tickets = $query->get();
                $assignedCount = 0;
                $skippedCount = 0;

                $restrictedBoxes = (new AgentTicketAccess())->getRestrictedMailboxIds($assignAgent);

                $tickets->each(function ($ticket) use ($assignAgent, $agent, $restrictedBoxes, &$assignedCount, &$skippedCount) {
                    $previousAgentId = (int) $ticket->agent_id;

                    //Skip ticket if mailbox is restricted for the agent
                    if (!empty($ticket->mailbox_id) && in_array((int) $ticket->mailbox_id, $restrictedBoxes, true)) {
                        $skippedCount++;
                        return;
                    }

                    $ticket->agent_id = $assignAgent->id;
                    $ticket->save();
                    $assignedCount++;

                    do_action('fluent_support/agent_assigned_to_ticket', $assignAgent, $ticket, $agent, $previousAgentId);
                });

                $assignedMessage = sprintf(
                    /* translators: %1$d is the number of tickets assigned, %2$s is the agent's name. */
                    __('%1$d tickets have been assigned to %2$s.', 'fluent-support'),
                    $assignedCount,
                    $assignAgent->full_name
                );

                $skippedMessage = $skippedCount > 0
                    ? sprintf(
                        /* translators: %1$d is the number of skipped tickets due to mailbox restrictions. */
                        __('%1$d tickets were skipped due to mailbox restrictions or already being assigned.', 'fluent-support'),
                        $skippedCount
                    )
                    : '';

                return [
                    'message' => trim($assignedMessage . ' ' . $skippedMessage)
                ];
            } else if ($action == 'assign_agent_group') {
                if (!$request->has('agent_group_id')) {
                    throw new \Exception(esc_html__('agent_group_id param is required', 'fluent-support'));
                }

                $groupId = $request->getSafe('agent_group_id', 'intval');
                $group = AgentGroup::findOrFail($groupId);

                if ($group->agents()->count() === 0) {
                    throw new \Exception(esc_html__('No agents found in this group', 'fluent-support'));
                }

                $tickets = $query->get();
                $assignedCount = 0;
                $skippedCount = 0;
                $currentCounts = [];

                foreach ($tickets as $ticket) {
                    $previousAgentId = (int) $ticket->agent_id;
                    $selectedAgent = $group->getLeastLoadedAgent(
                        $ticket->mailbox_id, $currentCounts
                    );

                    if (!$selectedAgent) {
                        $skippedCount++;
                        continue;
                    }

                    $ticket->agent_id = $selectedAgent->id;
                    $ticket->save();
                    $assignedCount++;
                    $currentCounts[$selectedAgent->id]++;

                    as_enqueue_async_action('fluent_support/async_agent_assigned_to_ticket', [
                        $selectedAgent->id, $ticket->id, $agent->id, $previousAgentId
                    ], 'fluent-support');
                }

                return [
                    'message' => sprintf(
                        /* translators: %1$d is tickets assigned, %2$d is tickets skipped. */
                        __('%1$d tickets assigned via agent group. %2$d skipped.', 'fluent-support'),
                        $assignedCount,
                        $skippedCount
                    )
                ];
            } else if ($action == 'assign_tags') {
                $tagIds = $request->get('tag_ids', null);
                if (!is_array($tagIds)) {
                    $tagIds = [];
                }
                $tags = array_filter(array_map('absint', $tagIds));

                $query->get()->each(function ($ticket) use ($tags) {
                    $ticket->applyTags($tags);
                });

                return [
                    'message' => __('Selected tags has been added to tickets', 'fluent-support')
                ];
            }

            throw new \Exception(esc_html__('Sorry no action found as available', 'fluent-support'));
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * deleteTicket method will delete a ticket
     * @param int $ticket_id
     * @return array
     */
    public function deleteTicket($ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            (new TicketService())->deleteTicket($ticket);

            return [
                'message' => __('Ticket has been deleted successfully', 'fluent-support')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * doBulkReplies method will create response for bulk tickets
     * This function will get ticket ids, content, attachment etc and create response for tickets
     * @param Request $request
     * @param Conversation $conversation
     * @return array
     * @throws \Exception
     */
    public function doBulkReplies(Request $request)
    {
        try {
            // Sanitize all request data before validation
            $requestData = $request->all();
            $data = [];
            foreach ($requestData as $key => $value) {
                if (is_array($value)) {
                    if ($key === 'ticket_ids') {
                        $data[$key] = array_map('intval', $value);
                    } elseif ($key === 'content') {
                        $data[$key] = wp_kses_post($value);
                    } else {
                        $data[$key] = map_deep($value, 'sanitize_text_field');
                    }
                } else {
                    $data[$key] = sanitize_text_field($value);
                }
            }

            $this->validate($data, [
                'content'    => 'required',
                'ticket_ids' => 'required|array'
            ]);

            //Get logged in agent information
            $agent = Helper::getAgentByUserId();
            $ticketIds = array_filter($data['ticket_ids'], 'absint');

            $query = Ticket::whereIn('id', $ticketIds)->where('status', '!=', 'closed');

            // Scope to tickets the agent may access (visibility + mailbox restrictions).
            (new AgentTicketAccess())->applyAccessScope($query, $agent);

            $tickets = $query->get();

            if ($tickets->isEmpty()) {
                throw new \Exception(esc_html__('Sorry no tickets found based on your filter and bulk actions', 'fluent-support'));
            }

            $responseData = [
                'content'           => wp_kses_post(Arr::get($data, 'content', '')),
                'conversation_type' => 'response',
                'close_ticket'      => Arr::get($data, 'close_ticket'),
            ];

            //If request with file attachments
            $attachmentHashes = Arr::get($data, 'attachments', []);
            $attachments = false;
            if ($attachmentHashes) {
                $attachments = Attachment::whereNull('ticket_id')
                    ->orderBy('id', 'asc')
                    ->whereIn('file_hash', $attachmentHashes)
                    ->get();
            }

            $responseService = new ResponseService();

            foreach ($tickets as $ticket) {
                if ($attachments) {
                    $responseData['attachments'] = [];
                    $attachmentRecords = [];
                    foreach ($attachments as $attachment) {
                        $fileHash = bin2hex(random_bytes(16));
                        $attachmentRecords[] = [
                            'ticket_id'  => $ticket->id,
                            'file_path'  => $attachment->file_path,
                            'full_url'   => $attachment->full_url,
                            'title'      => $attachment->title,
                            'driver'     => $attachment->driver,
                            'file_size'  => $attachment->file_size,
                            'status'     => $attachment->status,
                            'file_hash'  => $fileHash,
                        ];
                        $responseData['attachments'][] = $fileHash;
                    }
                    if ($attachmentRecords) {
                        Attachment::insert($attachmentRecords);
                    }
                }

                $responseService->createResponse($responseData, $agent, $ticket);
            }

            return [
                'message' => __('Response has been added to the selected tickets', 'fluent-support')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * deleteResponse method will remove a response from ticket by ticket id and response id
     * @param Request $request
     * @param Conversation $conversation
     * @param $ticket_id
     * @param $response_id
     * @return array
     */
    public function deleteResponse($ticket_id, $response_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            if (in_array($ticket->mailbox_id, PermissionManager::getRestrictedMailboxIds())) {
                throw new \Exception(esc_html__('Ticket cannot be fetched due to restricted mailbox', 'fluent-support'));
            }

            // The caller must have access to this specific ticket (visibility +
            // ownership + mailbox), not merely a global manage capability.
            $this->ensureCanAccessTicket($ticket);

            // Deleting a response always requires the explicit delete capability,
            // mirroring deleteTicket(). Assignment alone is not sufficient.
            if (!PermissionManager::currentUserCan('fst_delete_tickets')) {
                throw new \Exception(
                    esc_html__('Sorry, you do not have permission to delete this response.', 'fluent-support')
                );
            }

            $response = Conversation::where('id', $response_id)
                ->where('ticket_id', $ticket_id)
                ->firstOrFail();

            $response->delete();
            $response->ccinfo()->delete();

            return [
                'message' => __('Selected response has been deleted', 'fluent-support')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * updateResponse method will update ticket response using ticket and response id
     * @param Request $request
     * @param int $ticket_id
     * @param int $response_id
     * @return array
     * @throws \Exception
     */
    public function updateResponse(TicketResponseRequest $request, $ticket_id, $response_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            if (in_array($ticket->mailbox_id, PermissionManager::getRestrictedMailboxIds())) {
                throw new \Exception(esc_html__('Ticket cannot be fetched due to restricted mailbox', 'fluent-support'));
            }

            // The caller must have access to this specific ticket (visibility +
            // ownership + mailbox), not merely a global manage capability.
            $this->ensureCanAccessTicket($ticket);

            $response = Conversation::where('id', $response_id)
                ->where('ticket_id', $ticket_id)
                ->with('person')
                ->firstOrFail();
            $agent = Helper::getAgentByUserId();

            // Only agent-authored conversation types may be edited here. Customer
            // replies and system entries must not be rewritten via this endpoint.
            $editableTypes = ['response', 'draft_response', 'note', 'internal_info'];
            if (!in_array($response->conversation_type, $editableTypes, true)) {
                throw new \Exception(
                    esc_html__('This response type cannot be edited.', 'fluent-support')
                );
            }

            // Customer messages share the 'response' type but are authored by a
            // customer person; they are never editable by an agent.
            if ($response->person && $response->person->person_type !== 'agent') {
                throw new \Exception(
                    esc_html__('Sorry, you do not have permission to update this response.', 'fluent-support')
                );
            }

            $isDraft = $response->conversation_type == 'draft_response';
            $isAuthor = (int) $response->person_id === (int) $agent->id;
            $canApproveDraft = PermissionManager::currentUserCan('fst_approve_draft_reply');

            if ($isDraft && !$isAuthor) {
                // Another agent's draft can only be edited/approved by an approver.
                if (!$canApproveDraft) {
                    throw new \Exception(
                        esc_html__('Sorry, You do not have permission to approve this draft response', 'fluent-support')
                    );
                }
            } elseif (!$isAuthor && !PermissionManager::currentUserCan('fst_manage_other_tickets')) {
                // Editing another agent's response requires manage-others capability.
                throw new \Exception(
                    esc_html__('Sorry, you do not have permission to update this response.', 'fluent-support')
                );
            }

            $content = wp_unslash(wp_kses_post($request->getSafe('content', 'wp_kses_post')));
            $response->content = $content;

            if ($isDraft && !$isAuthor && $canApproveDraft) {
                $response = $this->approveDraftConversation($ticket, $response, $agent, $content);
            } else {
                $response->save();
            }

            return [
                'message'  => __('Selected response has been updated', 'fluent-support'),
                'response' => $response
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function approveDraftResponse(TicketResponseRequest $request, $ticket_id, $response_id)
    {
        try {
            if (!PermissionManager::currentUserCan('fst_approve_draft_reply')) {
                throw new \Exception(
                    esc_html__('You do not have permission to approve draft responses.', 'fluent-support')
                );
            }

            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $response = Conversation::where('id', $response_id)
                ->where('ticket_id', $ticket_id)
                ->where('conversation_type', 'draft_response')
                ->firstOrFail();

            $person = Helper::getAgentByUserId();

            $response = $this->approveDraftConversation(
                $ticket,
                $response,
                $person,
                wp_unslash(wp_kses_post($request->getSafe('content', 'wp_kses_post')))
            );

            return [
                'message'  => __('Draft response has been successfully approved.', 'fluent-support'),
                'response' => $response,
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    protected function approveDraftConversation($ticket, $response, $person, $content)
    {
        $resetWaitingSince = apply_filters('fluent_support/reset_waiting_since', true, $content);

        $response->content = $content;
        $response->conversation_type = 'response';
        $response->created_at = current_time('mysql');
        $response->save();

        if ($person->person_type == 'agent' && $ticket->status == 'new') {
            $ticket->status = 'active';
            if ($ticket->created_at) {
                $ticket->first_response_time = strtotime(current_time('mysql')) - strtotime($ticket->created_at);
            } else {
                $ticket->first_response_time = 300;
            }
        }

        if ($resetWaitingSince) {
            $ticket->last_agent_response = current_time('mysql');
            $ticket->waiting_since = current_time('mysql');
        }

        $ticket->response_count += 1;
        $ticket->save();

        do_action('fluent_support/response_added_by_' . $person->person_type, $response, $ticket, $person);

        return $response;
    }

    /**
     * getLiveActivity method will return the activity in a ticket by agents
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function getLiveActivity(Request $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $agent = Helper::getAgentByUserId();

            return [
                'live_activity' => TicketHelper::getActivity($ticket_id, $agent->id)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * removeLiveActivity method will remove activities that
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function removeLiveActivity(Request $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $agent = Helper::getAgentByUserId();

            return [
                'result'   => TicketHelper::removeFromActivities($ticket_id, $agent->id),
                'agent_id' => $agent->id
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * addTag method will add tag in ticket by ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function addTag(Request $request, $ticket_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $ticket->applyTags($request->getSafe('tag_id', 'intval'));

            return [
                'message' => __('Tag has been added to this ticket', 'fluent-support'),
                'tags'    => $ticket->tags
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * detachTag method will remove all tags from tickets
     * @param $ticket_id
     * @param $tag_id
     * @return array
     */
    public function detachTag($ticket_id, $tag_id)
    {
        try {
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            $ticket->detachTags($tag_id);

            return [
                'message' => __('Tag has been removed from this ticket', 'fluent-support'),
                'tags'    => $ticket->tags
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * changeTicketCustomer method will update customer in a ticket
     * This method will get ticket id and customer id as parameter, it will replace existing customer id with new
     * @param Request $request
     * @return array
     */
    public function changeTicketCustomer(Request $request, $ticket_id)
    {
        $ticketId = (int) $ticket_id;
        $newCustomerId = $request->getSafe('customer', 'intval');

        if (!$newCustomerId) {
            return $this->sendError(__('Invalid customer selected.', 'fluent-support'));
        }

        // Rebinding a ticket to another customer exposes that customer's private
        // data (profile, custom fields) through the ticket, so it requires the same
        // sensitive-data capability that gates the customer routes.
        if (!PermissionManager::currentUserCan('fst_sensitive_data')) {
            return $this->sendError(__('You do not have permission to change the ticket customer.', 'fluent-support'));
        }

        try {
            $ticket = Ticket::findOrFail($ticketId);

            $this->ensureCanAccessTicket($ticket);

            $targetCustomer = Customer::where('id', $newCustomerId)
                ->where('person_type', 'customer')
                ->first();

            if (!$targetCustomer) {
                return $this->sendError(__('Invalid customer selected.', 'fluent-support'));
            }

            if ($ticket->customer_id == $newCustomerId) {
                return $this->sendError(__('Customer already assigned to this ticket.', 'fluent-support'));
            }

            $ticket->customer_id = $newCustomerId;
            $ticket->save();

            return ['message' => __('Customer has been updated', 'fluent-support')];

        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * getTicketCustomData method will return the custom data by ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array|array[]
     */
    public function getTicketCustomData(Request $request, $ticket_id)
    {
        if (!defined('FLUENTSUPPORTPRO')) {
            return [
                'custom_data'     => [],
                'rendered_fields' => []
            ];
        }

        try {
            $ticket = Ticket::findOrFail($ticket_id);

            $this->ensureCanAccessTicket($ticket);

            return [
                'custom_data'     => (object)$ticket->customData(),
                'rendered_fields' => \FluentSupportPro\App\Services\CustomFieldsService::getRenderedPublicFields($ticket->customer, 'admin')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * syncFluentCrmTags method will synchronize the tags with Fluent CRM by contact id
     *This function will get contact id and tags as parameter, get existing tags from crm and updated added/removed tags
     * @param Request $request
     * @param FluentCRMServices $fluentCRMServices
     * @return array
     */
    public function syncFluentCrmTags(Request $request, FluentCRMServices $fluentCRMServices)
    {
        $data = [
            'contact_id' => $request->getSafe('contact_id', 'intval'),
            'tags'       => $request->get('tags', null)
        ];

        // Sanitize tags array if it's an array
        if (is_array($data['tags'])) {
            $data['tags'] = array_map('intval', $data['tags']);
        }

        try {
            return $fluentCRMServices->syncCrmTags($data);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * This `syncFluentCrmLists` method will synchronize the lists with Fluent CRM by contact id
     *  This method will get contact id and lists as parameter, get existing lists from crm and updated added/removed lists
     * @param Request $request
     * @param FluentCRMServices $fluentCRMServices
     * @return array
     */

    public function syncFluentCrmLists(Request $request, FluentCRMServices $fluentCRMServices)
    {
        $data = [
            'contact_id' => $request->getSafe('contact_id', 'intval'),
            'lists'      => $request->get('lists', null, [])
        ];

        // Sanitize lists array if it's an array
        if (is_array($data['lists'])) {
            $data['lists'] = array_map('intval', $data['lists']);
        }

        try {
            return $fluentCRMServices->syncCrmLists($data);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * Get ticket essentials data based on the provided types.
     *
     * @param \Illuminate\Http\Request $request
     * @return array The ticket essentials data.
     */
    public function getTicketEssentials(Request $request)
    {
        $type = $request->getSafe('type', 'sanitize_text_field');

        return TicketHelper::getTicketEssentials($type);
    }

    public function fetchLabelSearch()
    {
        try {
            $agent_id = get_current_user_id();
            return TicketHelper::getLabelSearch($agent_id);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function storeOrUpdateLabelSearch(Request $request)
    {
        try {
            $agent_id = get_current_user_id();
            $searchData = $request->get('query', null, []);
            if (is_array($searchData)) {
                $searchData = map_deep($searchData, 'sanitize_text_field');
            }
            $filterType = Arr::get($searchData, 'filter_type', '');
            if ($filterType == 'advanced') {
                return TicketHelper::saveSearchLabel($agent_id, $searchData, $filterType);
            }

            return [
                'message' => __('Invalid filter type.', 'fluent-support'),
            ];

        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function deleteLabelSearch(Request $request, $search_id)
    {
        try {
            $agent_id = get_current_user_id();
            return TicketHelper::deleteSavedSearch($search_id);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }
}
