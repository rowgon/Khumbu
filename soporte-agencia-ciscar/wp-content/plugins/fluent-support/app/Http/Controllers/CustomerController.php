<?php

namespace FluentSupport\App\Http\Controllers;

use FluentCrm\App\Models\Subscriber;
use FluentSupport\App\Models\Customer;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Services\AvatarUploder;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;

/**
 * CustomerController class for REST API
 * This class is responsible for getting data for all request related to customer
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class CustomerController extends Controller
{
    /**
     * index method will return the list of customers
     * @param Request $request
     * @param Customer $customer
     * @return array
     */
    public function index(Request $request, Customer $customer)
    {
        return [
            'customers' => $customer->getCustomers($request->getSafe('search', 'sanitize_text_field'), $request->getSafe('status', 'sanitize_text_field')),
        ];
    }

    public function customerField (Request $request,Customer $customer, $customer_id) {

        $userID = $request->getSafe('user_id', 'intval');
        return[
            'customerField' => $customer->getCustomerField($customer_id,$userID)
        ];
    }


    /**
     * getCustomer method will return individual customer information by customer id
     * This function will also get information about extra widgets, tickets and Fluent CRM
     * @param Request $request
     * @param Customer $customer
     * @param $customer_id
     * @return array
     */
    public function getCustomer(Request $request, Customer $customer, $customer_id)
    {
        $with = $request->get('with', null);
        $with = is_array($with) ? array_map('sanitize_key', $with) : [];

        return $customer->getCustomer($customer_id, $with);
    }

    /**
     * Create method will create new customer
     * @param Request $request
     * @param Customer $customer
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function create(Request $request, Customer $customer)
    {
        // Define expected fields with their sanitizers
        $fields = [
            'id' => 'intval',
            'customer_id' => 'intval',
            'avatar' => 'esc_url_raw',
            'person_type' => 'sanitize_text_field',
            'hash' => 'sanitize_text_field',
            'description' => 'sanitize_text_field',
            'photo' => 'esc_url_raw',
            'email' => 'sanitize_email',
            'first_name' => 'sanitize_text_field',
            'last_name' => 'sanitize_text_field',
            'title' => 'sanitize_text_field',
            'user_id' => 'intval',
            'remote_uid' => 'sanitize_text_field',
            'status' => 'sanitize_text_field',
            'address_line_1' => 'sanitize_textarea_field',
            'address_line_2' => 'sanitize_textarea_field',
            'city' => 'sanitize_text_field',
            'state' => 'sanitize_text_field',
            'zip' => 'sanitize_text_field',
            'country' => 'sanitize_text_field',
            'note' => 'sanitize_textarea_field',
            'ip_address' => 'sanitize_text_field',
            'last_ip_address' => 'sanitize_text_field',
        ];

        $data = $this->sanitizeRequestData($request, $fields);

        $data = $this->validate($data, [
            'email' => 'required|email|unique:fs_persons',
            'first_name' => 'required',
            'last_name' => 'nullable|string',
            'title' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'remote_uid' => 'nullable|string',
            'status' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'nullable|string',
            'note' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'last_ip_address' => 'nullable|string',
        ]);

        return [
            'message'  => __('Customer has been added', 'fluent-support'),
            'customer' => $customer->createCustomer($data)
        ];
    }

    /**
     * update method will update existing customer by customer id
     * @param Request $request
     * @param Customer $customer
     * @param $customerId
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function update(Request $request, Customer $customer, $customer_id)
    {
        // Sanitize only allowed fields and also sanitize any extra fields from hooks
        $fields = [
            'id' => 'intval',
            'customer_id' => 'intval',
            'avatar' => 'esc_url_raw',
            'person_type' => 'sanitize_text_field',
            'hash' => 'sanitize_text_field',
            'description' => 'sanitize_text_field',
            'photo' => 'esc_url_raw',
            'email' => 'sanitize_email',
            'first_name' => 'sanitize_text_field',
            'last_name' => 'sanitize_text_field',
            'title' => 'sanitize_text_field',
            'user_id' => 'intval',
            'remote_uid' => 'sanitize_text_field',
            'status' => 'sanitize_text_field',
            'address_line_1' => 'sanitize_textarea_field',
            'address_line_2' => 'sanitize_textarea_field',
            'city' => 'sanitize_text_field',
            'state' => 'sanitize_text_field',
            'zip' => 'sanitize_text_field',
            'country' => 'sanitize_text_field',
            'note' => 'sanitize_textarea_field',
            'ip_address' => 'sanitize_text_field',
            'last_ip_address' => 'sanitize_text_field',
        ];

        $data = $this->sanitizeRequestData($request, $fields);

        $data = $this->validate($data, [
            'email'      => 'required|email',
            'first_name' => 'required',
            'last_name' => 'nullable|string',
            'title' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'remote_uid' => 'nullable|string',
            'status' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'zip' => 'nullable|string',
            'country' => 'nullable|string',
            'note' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'last_ip_address' => 'nullable|string',
        ]);

        try {
            return [
                'message'  => __('Customer has been updated', 'fluent-support'),
                'customer' => $customer->updateCustomer($customer_id, $data)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e),
                'errors'  => [
                    'email' => [
                        'unique' => __('Email address has been assigned to other customer', 'fluent-support'),
                    ]
                ]
            ], 423);
        }
    }

    /**
     * delete method will delete a customer and all tickets by that customer
     * @param Request $request
     * @param Customer $customer
     * @param int $customerId
     * @return array
     */
    public function delete(Request $request, Customer $customer, $customer_id)
    {
        return $customer->deleteCustomer($customer_id);
    }

    /**
     * bulkDelete method will delete multiple customers and all their tickets
     * @param Request $request
     * @param Customer $customer
     * @return array
     */
    public function bulkDelete(Request $request, Customer $customer)
    {
        // Get and sanitize customer_ids before validation
        $customerIds = $request->get('customer_ids', []);
        $customerIds = is_array($customerIds) ? array_map('intval', $customerIds) : [];

        // Filter out any zero values (from invalid input)
        $customerIds = array_filter($customerIds, function ($id) {
            return $id > 0;
        });

        $this->validate(['customer_ids' => $customerIds], [
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'required|integer|exists:fs_persons,id'
        ]);

        return $customer->bulkDeleteCustomers($customerIds);
    }

    /**
     * addOrUpdateProfileImage method will update a customer avatar
     * For a successful upload it's required to send file object, customer id and the user type(customer)
     * @param Request $request
     * @return array
     */
    public function addOrUpdateProfileImage(Request $request, AvatarUploder $avatarUploder)
    {
        try {
            return $avatarUploder->addOrUpdateProfileImage($request->files(), $request->getSafe('customer_id', 'intval'), 'customer');
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e),
            ],
            $e->getCode()
        );
        }
    }

    /**
     * resetAvatar method will restore a customer avatar
     * For a successful upload it's required to send file object, customer id and the user type(customer)
     * @param Request $request
     * @param $id
     * @return array
     */
    public function resetAvatar(Customer $customer)
    {
        try {
            $customer->restoreAvatar();

            return [
                'message' => __('Customer avatar reset to gravatar default', 'fluent-support'),
            ];
        } catch (\Exception $e) {
            return [
                'message' => Helper::getSafeErrorMessage($e)
            ];
        }
    }

    public function searchContact(Request $request)
    {
        $search = trim($request->getSafe('search', 'sanitize_text_field'));

        // '*' is a WP_User_Query wildcard and survives sanitize_text_field, so a
        // lone '*' would list every user on the site (FS-SEC-014). Stripping it
        // leaves WP_User_Query doing an exact match.
        $search = trim(str_replace('*', '', $search));

        if (!$search) {
            return $this->sendError([
                'message' => __('Please provide search string', 'fluent-support')
            ]);
        }

        $isEmail = is_email($search);

        // Require a meaningful prefix so the endpoint can't be walked one letter
        // at a time. Emails are matched exactly, so they need no minimum.
        if (!$isEmail && mb_strlen($search) < 3) {
            return $this->sendError([
                'message' => __('Please provide at least 3 characters to search', 'fluent-support')
            ]);
        }

        if (Helper::hitRateLimit('fs_contact_search_' . get_current_user_id(), 60, 5 * MINUTE_IN_SECONDS)) {
            return $this->sendError([
                'message' => __('Too many contact searches. Please try again in a few minutes.', 'fluent-support')
            ], 429);
        }

        // '%' and '_' are LIKE wildcards for the customer and CRM scopes below.
        // Escape rather than strip: underscores are legitimate in emails.
        global $wpdb;
        $likeSearch = $wpdb->esc_like($search);

        // search the existing customers first
        if ($isEmail) {
            $customers = Customer::select(['first_name', 'last_name', 'email', 'id', 'user_id'])
                ->where('email', $search)
                ->get();
        } else {
            $customers = Customer::select(['first_name', 'last_name', 'email', 'id', 'user_id'])
                ->searchBy($likeSearch)
                ->limit(10)
                ->get();
        }

        if (!$customers->isEmpty()) {
            return [
                'type'     => 'search_result',
                'provider' => 'fluent_support',
                'data'     => $customers,
                'is_email' => $isEmail,
                'search' => $search
            ];
        }

        // If FluentCRM exist then let's search for
        if (defined('FLUENTCRM')) {

            if ($isEmail) {
                $contacts = \FluentCrm\App\Models\Subscriber::where('email', $search)
                    ->select(['first_name', 'last_name', 'email', 'id', 'user_id'])
                    ->get();
            } else {

                $contacts = \FluentCrm\App\Models\Subscriber::searchBy($likeSearch)
                     ->select(['first_name', 'last_name', 'email', 'id', 'user_id'])
                    ->limit(10)
                    ->get();
            }

            if (!$contacts->isEmpty()) {
                return [
                    'type'     => 'search_result',
                    'provider' => 'fluent_crm',
                    'data'     => $contacts,
                    'is_email' => $isEmail
                ];
            }
        }

        // let's search from user's database
        $user_query = new \WP_User_Query(array('search' => $search, 'number' => 10));

        $users = $user_query->get_results();

        if ($users) {
            $formattedUsers = [];

            foreach ($users as $user) {
                $formattedUsers[] = [
                    'id'         => $user->ID,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'user_id'    => $user->ID,
                    'email'      => $user->user_email
                ];
            }

            return [
                'type'     => 'search_result',
                'provider' => 'wp_users',
                'data'     => $formattedUsers,
                'is_email' => $isEmail
            ];
        }

        return [
            'type'     => 'none',
            'provider' => 'none',
            'data'     => [],
            'is_email' => $isEmail
        ];

    }

    /**
     * Sanitize request data for given fields. Uses Request::getSafe for known fields
     * and falls back to sanitize_text_field for any other keys present in the raw request
     * (useful when hooks inject extra data).
     *
     * @param Request $request
     * @param array $fieldsMap associative array field => sanitizer callable name
     * @return array
     */
    private function sanitizeRequestData(Request $request, array $fieldsMap)
    {
        $sanitized = [];

        // Use getSafe for known fields
        foreach ($fieldsMap as $field => $sanitizer) {
            $sanitized[$field] = $request->getSafe($field, $sanitizer);
        }

        // Now sanitize any other incoming keys to avoid unsanitized data
        $raw = $request->get();
        foreach ($raw as $key => $value) {
            if (array_key_exists($key, $sanitized)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                // Fallback sanitizer for unknown fields
                $sanitized[$key] = is_string($value) ? sanitize_text_field($value) : $value;
            }
        }

        return $sanitized;
    }
}
