<?php

namespace FluentSupport\App\Http\Controllers;

use Exception;
use FluentSupport\App\Http\Requests\TicketResponseRequest;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\CustomerPortalService;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Support\Arr;

/**
 * CustomerPortalController class for REST API
 * This class is responsible for getting data for all request related to customer and customer portal
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class CustomerPortalController extends Controller
{
    /**
     * getTickets will generate ticket information with customer and agents by customer
     * @param Request $request
     * @return array|\WP_REST_Response
     */
    public function getTickets(Request $request)
    {

        $onBehalf = $request->get('on_behalf', []);
        if ($onBehalf) {
            $onBehalf = array_map(function ($item) {
                return sanitize_text_field($item);
            }, $onBehalf);
        }

        $userIP = $request->getIp();
        $requestedStatus = $request->getSafe('filter_type', 'sanitize_text_field');
        $ticketOptions = $request->getSafe([
            'search'             => 'sanitize_text_field',
            'filters.product_id' => 'intval',
            'sorting.sort_type'  => 'sanitize_sql_orderby',
            'sorting.sort_by'    => 'sanitize_sql_orderby'
        ]);

        $customer = (new CustomerPortalService)->resolveCustomer($onBehalf, $userIP);

        if (!$customer) {
            return [
                'tickets' => [
                    'data'         => [],
                    'current_page' => $request->getSafe('page', 'intval', 1),
                    'last_page'    => 1,
                    'per_page'     => $request->getSafe('per_page', 'intval', 10),
                    'total'        => 0,
                    'from'         => null,
                    'to'           => null
                ]
            ];
        }

        if ($customer->status !== 'active') {
            return $this->sendError([
                'message'    => __('Your account is not active. Please contact support.', 'fluent-support'),
                'error_type' => '403'
            ], 403);
        }

        $canAccess = apply_filters('fluent_support/can_customer_access_portal', true, $customer);

        if (is_wp_error($canAccess)) {
            return $this->sendError([
                'message'    => $canAccess->get_error_message(),
                'error_type' => $canAccess->get_error_code()
            ], 403);
        }

        $statuses = [
            'open'   => ['new', 'active', 'on-hold'],
            'all'    => [],
            'closed' => ['closed']
        ];

        $statusFilter = $statuses[$requestedStatus] ?? $statuses['all'];

        $sortBy = Arr::get($ticketOptions, 'sorting.sort_by', 'created_at');
        $sortType = Arr::get($ticketOptions, 'sorting.sort_type', 'desc');

        $ticketsQuery = Ticket::where('customer_id', $customer->id)
            ->filterByStatues($statusFilter)
            ->searchBy(Arr::get($ticketOptions, 'search'))
            ->filterByProductId(Arr::get($ticketOptions, 'filters.product_id'))
            ->orderBy($sortBy, $sortType);

        do_action_ref_array('fluent_support/customer_portal/tickets_query', [&$ticketsQuery, $customer, $request]);

        $tickets = $ticketsQuery->paginate($request->getInt('per_page', 10));

        foreach ($tickets as $ticket) {
            $ticket->human_date = sprintf(__('%s ago', 'fluent-support'), human_time_diff(strtotime($ticket->created_at), current_time('timestamp')));
            $ticket->preview_response = $ticket->getLastResponse();
        }

        return [
            'tickets' => $tickets
        ];
    }

    /**
     * createTicket method will create ticket submitted by customers
     * @param Request $request
     * @return array | \WP_REST_Response
     */
    public function createTicket(Request $request)
    {
        $dataRules = $this->app->applyCustomFilters('custom_field_required_before_ticket_create', [
            'required_fields' => [
                'title'   => 'required',
                'content' => 'required'
            ],
            'error_messages'  => [
                'title.required'   => __('Title is required', 'fluent-support'),
                'content.required' => __('Content is required', 'fluent-support')
            ]
        ]);

        $settings = Helper::getOption('_ticket_form_settings', []);

        if (!empty($settings['product_required_field']) && $settings['product_required_field'] === 'yes') {
            $productCount = Product::count();
            if ($productCount > 0) {
                $dataRules['required_fields']['product_id'] = 'required';
                $dataRules['error_messages']['product_id.required'] = __('Product is required', 'fluent-support');
            }
        }

        $defaultData = [
            'ticket_title'   => $request->getSafe('title', 'sanitize_text_field'),
            'ticket_content' => $request->getSafe('content', 'wp_kses_post')
        ];

        if ($request->has('product_id')) {
            $defaultData['ticket_product_id'] = $request->getSafe('product_id', 'intval');
        }

        if ($request->has('client_priority')) {
            $defaultData['ticket_client_priority'] = $request->getSafe('client_priority', 'sanitize_text_field');
        }

        $customData = $request->get('custom_data', []);
        $customData = is_array($customData) ? map_deep($customData, 'sanitize_text_field') : [];

        $dataRules = $this->app->applyCustomFilters('custom_field_required_by_conditions_before_ticket_create', [
            'required_fields' => $dataRules['required_fields'],
            'error_messages'  => $dataRules['error_messages'],
            'custom_data'     => $customData,
            'default_data'    => $defaultData
        ]);

        if (!isset($dataRules['required_fields']) && !isset($dataRules['error_messages'])) {
            return $this->sendError([
                'message'    => __('Invalid form data submitted', 'fluent-support'),
                'error_type' => '400'
            ], 400);
        }

        $data = $this->validate($request->get(), $dataRules['required_fields'], $dataRules['error_messages']);

        $data['title'] = sanitize_text_field($data['title']);
        $data['content'] = wp_kses_post($data['content']);

        $onBehalf = $request->get('on_behalf', []);
        $userIP = $request->getIp();

        if ($onBehalf) {
            $onBehalf = array_map(function ($item) {
                return sanitize_text_field($item);
            }, $onBehalf);

            if (!empty($onBehalf['last_ip_address'])) {
                $userIP = $onBehalf['last_ip_address'];
            }
        }

        try {
            $customer = (new CustomerPortalService())->resolveCustomer($onBehalf, $userIP, true);

            if (!$customer) {
                return $this->sendError([
                    'message'    => __('Unable to identify. Please make sure you have provided correct information.', 'fluent-support'),
                    'error_type' => '403'
                ], 403);
            }

            $canCreateTicket = apply_filters('fluent_support/can_customer_create_ticket', true, $customer, $data);

            if (!$canCreateTicket || is_wp_error($canCreateTicket)) {
                $isWpError = is_wp_error($canCreateTicket);

                $message = ($isWpError) ? $canCreateTicket->get_error_message() : __('Sorry you cannot create ticket', 'fluent-support');
                $errorCode = ($isWpError) ? $canCreateTicket->get_error_code() : 'general_error';

                return $this->sendError([
                    'message'    => $message,
                    'error_type' => $errorCode
                ]);
            }

            if ($messageId = Helper::generateMessageID($customer->email)) {
                $data['message_id'] = $messageId;
            }

            $defaultMailbox = Helper::getDefaultMailBox();
            $defaultMailboxId = $defaultMailbox ? $defaultMailbox->id : null;
            $ticket = (new CustomerPortalService())->createTicket($customer, $data, $request->getSafe('mailbox_id', 'intval', $defaultMailboxId));

            return [
                'message' => __('Ticket has been created successfully', 'fluent-support'),
                'ticket'  => $ticket
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message'    => Helper::getSafeErrorMessage($e),
                'error_type' => $e->getCode()
            ]);
        }
    }

    /**
     * getTicket method will get the ticket information with customer and agent as well as response in a ticket by ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array|\WP_REST_Response
     */
    public function getTicket(Request $request, $ticket_id)
    {
        $customerAdditionalData = $this->getCustomerAdditionalData($request);

        try {
            return (new CustomerPortalService())->getTicket($customerAdditionalData, $ticket_id);
        } catch (\Exception $e) {
            return $this->sendError([
                'message'    => Helper::getSafeErrorMessage($e),
                'error_type' => $e->getCode()
            ]);
        }
    }

    /**
     * createResponse method will create response by customer in a ticket by ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array|\WP_REST_Response
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function createResponse(TicketResponseRequest $request, $ticket_id)
    {

        $customerAdditionalData = $this->getCustomerAdditionalData($request);

        $ticket = Ticket::wherePublicIdentifier($ticket_id)->firstOrFail();

        $data = $request->sanitize();

        $canCreateResponse = apply_filters('fluent_support/can_customer_create_response', true, $ticket->customer, $ticket, $data);

        if (!$canCreateResponse || is_wp_error($canCreateResponse)) {
            return [
                'type'    => 'error',
                'message' => (is_wp_error($canCreateResponse)) ? $canCreateResponse->get_error_message() : __('Sorry you cannot create response', 'fluent-support')
            ];
        }

        try {
            return (new CustomerPortalService())->createResponse($customerAdditionalData, $ticket_id, $data);
        } catch (\Exception $e) {
            return $this->sendError([
                'message'    => Helper::getSafeErrorMessage($e),
                'error_type' => $e->getCode()
            ]);
        }
    }

    /**
     * This `closeTicket` is responsible for closing ticket by ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function closeTicket(Request $request, $ticket_id)
    {
        $customerAdditionalData = $this->getCustomerAdditionalData($request);

        try {
            return (new CustomerPortalService())->closeTicket($customerAdditionalData, $ticket_id);
        } catch (Exception $e) {
            return $this->sendError([
                'message'    => Helper::getSafeErrorMessage($e),
                'error_type' => $e->getCode()
            ]);
        }
    }

    /**
     * closeTicket method will re-open a ticket by customer using ticket id
     * @param Request $request
     * @param $ticket_id
     * @return array
     */
    public function reOpenTicket(Request $request, $ticket_id)
    {
        $customerAdditionalData = $this->getCustomerAdditionalData($request);

        try {
            return (new CustomerPortalService())->reOpenTicket($customerAdditionalData, $ticket_id);
        } catch (Exception $e) {
            return $this->sendError([
                'message'    => Helper::getSafeErrorMessage($e),
                'error_type' => $e->getCode()
            ]);
        }
    }

    public function agentFeedbackRating(Request $request, $ticketId)
    {

        $customerPortalService = new CustomerPortalService();

        // just for validation
        $ticket = Ticket::with(['customer'])->wherePublicIdentifier($ticketId)->firstOrFail();
        $customerAdditionalData = $this->getCustomerAdditionalData($request);
        $customer = $customerPortalService->getCustomer($customerAdditionalData, $ticket);
        $customerPortalService->checkCustomerTicketAccess($customer, $ticket, 'feedback');

        $conversationID = $request->getSafe('conversation_id', 'intval');
        $approvalStatus = $request->getSafe('approval_status', 'sanitize_text_field');

        try {
            return $customerPortalService->addUserFeedback($approvalStatus, $conversationID, $ticket->id);
        } catch (Exception $e) {
            return $this->sendError([
                'message'    => Helper::getSafeErrorMessage($e),
                'error_type' => $e->getCode()
            ]);
        }
    }

    /**
     * getPublicOptions method will return the list of product and customer priorities
     * @return array
     */
    public function getPublicOptions()
    {
        $products = Product::select(['id', 'title'])->get();

        return [
            'support_products'           => $products,
            'customer_ticket_priorities' => Helper::customerTicketPriorities()
        ];
    }

    /**
     * getCustomFieldsRender method will return the list of custom fields
     * @return array|array[]
     */
    public function getCustomFieldsRender()
    {
        if (!defined('FLUENTSUPPORTPRO')) {
            return [
                'custom_fields_rendered' => []
            ];
        }

        return [
            'custom_fields_rendered' => \FluentSupportPro\App\Services\CustomFieldsService::getRenderedPublicFields()
        ];
    }


    /**
     * logout method will logout the customer
     * @return mixed
     */
    public function logout()
    {
        wp_logout();

        return $this->sendSuccess([
            'message' => __('You have been logged out', 'fluent-support')
        ]);
    }

    private function getCustomerAdditionalData($request)
    {

        $onBehalf = $request->get('on_behalf', []);
        if ($onBehalf) {
            $onBehalf = array_map(function ($item) {
                return sanitize_text_field($item);
            }, $onBehalf);
        }

        $customerAdditionalData = [
            'intended_ticket_hash' => $request->getSafe('intended_ticket_hash', 'sanitize_text_field'),
            'on_behalf'            => $onBehalf,
            'user_ip'              => $request->getIp()
        ];

        return $customerAdditionalData;
    }
}
