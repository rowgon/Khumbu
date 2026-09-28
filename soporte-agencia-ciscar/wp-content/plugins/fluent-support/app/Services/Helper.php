<?php

namespace FluentSupport\App\Services;

use FluentSupport\App\App;
use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\Attachment;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Customer;
use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\AIActivityLogs;
use FluentSupport\App\Models\Person;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Services\Includes\UploadService;
use FluentSupport\App\Services\EmailNotification\Settings;
use FluentSupport\Framework\Support\Arr;

/**
 *  Helper - REST API Helper Class
 *
 *  App helper for REST API
 *
 * @package FluentSupport\App\Services
 *
 * @version 1.0.0
 */
class Helper
{
    public static function FluentSupport($module = null)
    {
        return App::getInstance($module);
    }

    /**
     * Get agent information by user id
     * The function will get user id as parameter or get id from session and return agent information
     * @param null $userId
     * @return false | Agent
     */
    public static function getAgentByUserId($userId = null)
    {
        if ($userId === null) {
            $userId = get_current_user_id();
        }
        if (!$userId) {
            return false;
        }
        return Agent::where('user_id', $userId)->first();
    }

    /**
     * This function will return the list of ticket priorities list for customer
     *
     * @return mixed
     */
    public static function customerTicketPriorities()
    {
        return apply_filters('fluent_support/customer_ticket_priorities', [
            'normal'   => __('Normal', 'fluent-support'),
            'medium'   => __('Medium', 'fluent-support'),
            'critical' => __('Critical', 'fluent-support')
        ]);
    }

    /**
     * This function will return the list of ticket priorities list for Admin
     *
     * @return mixed
     */
    public static function adminTicketPriorities()
    {
        return apply_filters('fluent_support/admin_ticket_priorities', [
            'normal'   => __('Normal', 'fluent-support'),
            'medium'   => __('Medium', 'fluent-support'),
            'critical' => __('Critical', 'fluent-support')
        ]);
    }


    /**
     * This function will return ticket status group
     *
     * @return mixed
     */
    public static function ticketStatusGroups()
    {
        return apply_filters('fluent_support/ticket_status_groups', [
            'open'   => ['new', 'active'],
            'active' => ['active'],
            'closed' => ['closed'],
            'new'    => ['new'],
            'all'    => []
        ]);
    }

    /**
     * This function will return custom ticket status group
     *
     * @return mixed
     */
    public static function changeableTicketStatuses()
    {
        $ticketStatus = static::ticketStatusGroups();

        unset($ticketStatus['all']);
        unset($ticketStatus['open']);

        return apply_filters('fluent_support/changeable_ticket_statuses', $ticketStatus);
    }

    /**
     * This function will return ticket status list
     *
     * @return mixed
     */
    public static function ticketStatuses()
    {
        return apply_filters('fluent_support/ticket_statuses', [
            'new'    => __('New', 'fluent-support'),
            'active' => __('Active', 'fluent-support'),
            'closed' => __('Closed', 'fluent-support'),
        ]);
    }

    public static function getTkStatusesByGroupName($groupName)
    {
        $groups = self::ticketStatusGroups();
        return Arr::get($groups, $groupName, []);
    }

    public static function ticketAcceptedFileMiles()
    {
        $groups = self::getMimeGroups();
        $globalSettings = (new Settings())->globalBusinessSettings();

        if (empty($globalSettings['accepted_file_types'])) {
            return apply_filters('fluent_support/accepted_ticket_mimes', []);
        }

        $mimes = [];
        $typesGroups = Arr::only($groups, $globalSettings['accepted_file_types']);
        foreach ($typesGroups as $mimesGroup) {
            $mimes = array_merge($mimes, $mimesGroup['mimes']);
        }

        return apply_filters('fluent_support/accepted_ticket_mimes', $mimes);
    }

    public static function getAcceptedMimeHeadings()
    {
        $groups = self::getMimeGroups();
        $globalSettings = (new Settings())->globalBusinessSettings();

        if (empty($globalSettings['accepted_file_types'])) {
            return [];
        }

        $mimeNames = [];
        $typesGroups = Arr::only($groups, $globalSettings['accepted_file_types']);
        foreach ($typesGroups as $mimesGroup) {
            $mimeNames[] = $mimesGroup['title'];
        }

        return $mimeNames;
    }

    public static function getFileUploadMessage()
    {
        $mimeHeadings = self::getAcceptedMimeHeadings();
        $settings = (new Settings())->globalBusinessSettings();
        $maxFileSize = floatval($settings['max_file_size']);

        // translators: %1$s is a comma-separated list of supported file types, %2$.01f is the maximum file size in megabytes
        return sprintf(__('Supported Types: %1$s and max file size: %2$.01fMB', 'fluent-support'), implode(', ', $mimeHeadings), $maxFileSize);
    }

    public static function getMimeGroups()
    {
        return apply_filters('fluent_support/mime_groups', [
            'images'    => [
                'title' => __('Photos', 'fluent-support'),
                'mimes' => [
                    'image/gif',
                    'image/ief',
                    'image/jpeg',
                    'image/webp',
                    'image/pjpeg',
                    'image/ktx',
                    'image/png'
                ]
            ],
            'csv'       => [
                'title' => __('CSV', 'fluent-support'),
                'mimes' => [
                    'application/csv',
                    'application/txt',
                    'text/csv',
                    'text/plain',
                    'text/comma-separated-values',
                    'text/anytext',
                ]
            ],
            'documents' => [
                'title' => __('PDF/Docs', 'fluent-support'),
                'mimes' => [
                    'application/excel',
                    'application/vnd.ms-excel',
                    'application/vnd.msexcel',
                    'application/octet-stream',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ]
            ],
            'zip'       => [
                'title' => __('Zip', 'fluent-support'),
                'mimes' => [
                    'application/zip'
                ]
            ],
            'json'      => [
                'title' => __('JSON', 'fluent-support'),
                'mimes' => [
                    'application/json',
                    'application/jsonml+json'
                ]
            ]
        ]);
    }

    /**
     * getOption method will return settings using key
     * This method will get key as parameter, fetch data from database, beautify the data and return
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    public static function getOption($key, $default = '')
    {
        //Get settings from meta table using the key
        $data = Meta::where('object_type', 'option')
            ->where('key', $key)
            ->first();

        if ($data) {
            $value = static::safeUnserialize($data->value);
            if ($value) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * updateOption method will update or insert settings
     * This method will get key and value as parameter, check exists or not. If exist update value by key, else insert value for the key
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function updateOption($key, $value)
    {
        //Get settings from meta table using the key
        $data = Meta::where('object_type', 'option')
            ->where('key', $key)
            ->first();

        //If data is available, update existing data and return
        if ($data) {
            return Meta::where('id', $data->id)
                ->update([
                    'value' => maybe_serialize($value)
                ]);
        }

        //If newly submit, create new record and return
        return Meta::insert([
            'object_type' => 'option',
            'key'         => $key,
            'value'       => maybe_serialize($value)
        ]);
    }

    /** @internal Not called from core controllers — available for Pro/hook usage. */
    public static function deleteOption($key)
    {
        return Meta::where('object_type', 'option')
            ->where('key', $key)
            ->delete();
    }

    /**
     * getIntegrationOption method will return the integration settings by integration key
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    public static function getIntegrationOption($key, $default = '')
    {
        $data = Meta::where('object_type', 'integration_settings')
            ->where('key', $key)
            ->first();

        if ($data) {
            $value = static::safeUnserialize($data->value);
            if ($value) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * updateIntegrationOption method will update existing settings or create new settings by integration key
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function updateIntegrationOption($key, $value)
    {
        $data = Meta::where('object_type', 'integration_settings')
            ->where('key', $key)
            ->first();

        if ($data) {
            return Meta::where('id', $data->id)
                ->update([
                    'value' => maybe_serialize($value)
                ]);
        }

        return Meta::insert([
            'object_type' => 'integration_settings',
            'key'         => $key,
            'value'       => maybe_serialize($value)
        ]);
    }

    public static function getTicketViewUrl($ticket)
    {
        $baseUrl = self::getPortalBaseUrl();
        $ticketNumber = $ticket->serial_number ?? $ticket->id;
        return $baseUrl . '/#/ticket/' . $ticketNumber . '/view';
    }

    public static function getTicketViewSignedUrl($ticket)
    {
        if (!self::isPublicSignedTicketEnabled()) {
            return self::getTicketViewUrl($ticket);
        }

        $baseUrl = self::getPortalBaseUrl();
        $ticketNumber = $ticket->serial_number ?? $ticket->id;

        $baseUrl = add_query_arg([
            'fs_view'      => 'ticket',
            'support_hash' => $ticket->hash,
            'ticket_id'    => $ticketNumber,
        ], $baseUrl);

        return $baseUrl . '#/ticket/' . $ticketNumber . '/view';
    }

    public static function saveOpenAIData($objectType, $key, $data)
    {
        $serializedData = maybe_serialize($data);

        $previousValue = Meta::where('object_type', $objectType)->first();

        if ($previousValue) {
           return Meta::where('object_type', $objectType)->update([
                'value' => $serializedData
            ]);
        } else {
            return  Meta::insert([
                'object_type' => $objectType,
                'key' => $key,
                'value' => $serializedData
            ]);
        }

    }

    public static function saveAIProviderSettings(array $data)
    {
        if (!empty($data['api_key'])) {
            $data['api_key'] = static::encryptApiKey($data['api_key']);
        }

        $serializedData = maybe_serialize($data);
        $previous       = Meta::where('object_type', '_fs_ai_provider_settings')->first();

        if ($previous) {
            return Meta::where('object_type', '_fs_ai_provider_settings')->update([
                'value' => $serializedData,
            ]);
        }

        return Meta::insert([
            'object_type' => '_fs_ai_provider_settings',
            'key'         => '_fs_ai_provider_data',
            'value'       => $serializedData,
        ]);
    }

    public static function getAIProviderSettings(): array
    {
        $record = Meta::where('object_type', '_fs_ai_provider_settings')->first();

        if (!$record) {
            $record = Meta::where('object_type', '_fs_openai_settings')->first();
        }

        if (!$record) {
            return [];
        }

        $settings = static::safeUnserialize($record->value);

        if (empty($settings) || !is_array($settings)) {
            return [];
        }

        if (!isset($settings['provider'])) {
            $settings['provider'] = 'openai';
        }

        if (!empty($settings['api_key'])) {
            $settings['api_key'] = static::decryptApiKey($settings['api_key']);
        }

        return $settings;
    }

    public static function encryptApiKey(string $apiKey): string
    {
        if (!extension_loaded('openssl')) {
            return $apiKey;
        }

        $cipher = 'AES-256-CBC';
        $key    = substr(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true), 0, 32);
        $iv     = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        $encrypted = openssl_encrypt($apiKey, $cipher, $key, 0, $iv);

        if ($encrypted === false) {
            return $apiKey;
        }

        return 'fsai:' . base64_encode($iv . $encrypted);
    }

    public static function decryptApiKey(string $value): string
    {
        if (!extension_loaded('openssl') || strncmp($value, 'fsai:', 5) !== 0) {
            return $value;
        }

        $cipher  = 'AES-256-CBC';
        $decoded = base64_decode(substr($value, 5));
        $ivLen   = openssl_cipher_iv_length($cipher);

        if (strlen($decoded) <= $ivLen) {
            return $value;
        }

        $key       = substr(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true), 0, 32);
        $iv        = substr($decoded, 0, $ivLen);
        $decrypted = openssl_decrypt(substr($decoded, $ivLen), $cipher, $key, 0, $iv);

        return $decrypted !== false ? $decrypted : $value;
    }

    public static function authorizeChatGPTAPIKey($data)
    {
       return wp_remote_get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['api_key'],
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public static function isPublicSignedTicketEnabled()
    {
        $businessSettings = self::getBusinessSettings();

        return (Arr::get($businessSettings, 'disable_public_ticket') != 'yes');
    }

    public static function getTicketAdminUrl($ticket)
    {
        $baseUrl = self::getPortalAdminBaseUrl();
        return $baseUrl . 'tickets/' . $ticket->id . '/view';
    }

    /**
     * getPortalBaseUrl will get the portal page id and return link of the page
     * @return mixed
     */
    public static function getPortalBaseUrl()
    {
        $businessSettings = self::getBusinessSettings();
        $portalType = Arr::get($businessSettings, 'ticket_link_portal', 'default');
        $baseUrl = null;

        if (self::isPortalActive($portalType)) {
            if ($portalType === 'woocommerce' && function_exists('wc_get_endpoint_url') && function_exists('wc_get_page_permalink')) {
                $accountUrl = wc_get_page_permalink('myaccount');
                if ($accountUrl && $accountUrl !== '#') {
                    $baseUrl = wc_get_endpoint_url('support-tickets', '', $accountUrl);
                }
            } elseif ($portalType === 'fluent_cart') {
                $baseUrl = \FluentCart\App\Services\URL::getCustomerDashboardUrl('fluent-support') ?: null;
            } elseif ($portalType === 'fluent_community') {
                $baseUrl = \FluentCommunity\App\Services\Helper::baseUrl('support/') ?: null;
            }
        }

        if (!$baseUrl) {
            $baseUrl = get_permalink(absint(Arr::get($businessSettings, 'portal_page_id')));
        }

        return apply_filters('fluent_support/portal_base_url', rtrim((string) $baseUrl, '/\\'));
    }

    public static function isPortalActive($portalType)
    {
        if ($portalType === 'woocommerce') {
            return defined('FLUENTSUPPORTPRO_PLUGIN_VERSION') && defined('WC_PLUGIN_FILE');
        }

        if ($portalType === 'fluent_cart') {
            return defined('FLUENTCART_VERSION');
        }

        if ($portalType === 'fluent_community') {
            return defined('FLUENT_COMMUNITY_PLUGIN_VERSION');
        }

        return false;
    }

    public static function getPortalAdminBaseUrl()
    {
        return apply_filters('fluent_support/portal_admin_base_url', admin_url('admin.php?page=fluent-support/#/'));
    }

    public static function getBusinessSettings($key = null)
    {
        static $settings;

        if ($settings && $key) {
            return Arr::get($settings, $key);
        }

        if ($settings) {
            return $settings;
        }

        $settings = (new Settings())->globalBusinessSettings();

        if ($key) {
            return Arr::get($settings, $key);
        }
        return $settings;
    }

    public static function isAgentFeedbackEnabled()
    {
        return self::getBusinessSettings('agent_feedback_rating', 'no') == 'yes';
    }

    public static function getTicketMeta($ticketId, $key, $default = '')
    {
        $data = Meta::where('object_type', 'ticket_meta')
            ->where('key', $key)
            ->where('object_id', $ticketId)
            ->first();

        if ($data) {
            $value = static::safeUnserialize($data->value);
            if ($value) {
                return $value;
            }
        }

        return $default;
    }

    public static function updateTicketMeta($ticketId, $key, $value)
    {
        $data = Meta::where('object_type', 'ticket_meta')
            ->where('key', $key)
            ->where('object_id', $ticketId)
            ->first();

        if ($data) {
            return Meta::where('id', $data->id)
                ->update([
                    'value' => maybe_serialize($value)
                ]);
        }

        return Meta::insert([
            'object_type' => 'ticket_meta',
            'object_id'   => $ticketId,
            'key'         => $key,
            'value'       => maybe_serialize($value)
        ]);
    }

    public static function getWPPages()
    {
        $pages = (self::FluentSupport())->app->db
            ->table('posts')
            ->select(['ID', 'post_title'])
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->latest('ID')
            ->get();
        $formattedPages = [];
        foreach ($pages as $page) {
            $formattedPages[] = [
                'id'    => intval($page->ID),
                'title' => $page->post_title ?: __('(no title)', 'fluent-support')
            ];
        }
        return $formattedPages;
    }

    public static function getDefaultMailBox()
    {
        $mailbox = MailBox::where('is_default', 'yes')->first();

        if ($mailbox) {
            return $mailbox;
        }

        return MailBox::oldest('id')->first();
    }

    public static function getCurrentAgent()
    {
        // If user is logged in then return the agent by user id.
        // This `get_current_user_id` function is WP function and
        // it returns user id if user is logged in.
        if (get_current_user_id()) {
            return Agent::where('user_id', get_current_user_id())->first();
        }
    }

    public static function getCurrentCustomer()
    {
        // If user is logged in then return the customer by user id.
        // This `get_current_user_id` function is WP function and
        // it returns user id if user is logged in.
        if (get_current_user_id()) { //if user is logged in
            return Customer::where('user_id', get_current_user_id())->first();
        }
    }

    public static function getCurrentPerson()
    {
        // If user is logged in then return the person(agent/customer) by user id.
        // This `get_current_user_id` function is WP function and
        // it returns user id if user is logged in.
        if (get_current_user_id()) {
            return Person::where('user_id', get_current_user_id())
                ->orderBy('id', 'ASC')
                ->first();
        }
        return null;
    }

    public static function getCustomerByID($customerid)
    {
        return Customer::where('id', $customerid)->first();
    }

    /** @internal Not called from core controllers — available for Pro/hook usage. */
    public static function sanitizeOrderValue($orderType = '')
    {
        $orderBys = ['ASC', 'DESC'];

        $orderType = trim(strtoupper((string)($orderType ?? '')));

        return in_array($orderType, $orderBys) ? $orderType : 'DESC';
    }

    public static function getFluentCRMTagConfig()
    {
        if (!defined('FLUENTCRM')) {
            return [
                'can_add_tags' => false,
                'tags'         => [],
                'lists'        => [],
                'logo'         => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/fluentcrm-logo.svg',
                'icon'         => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/fluent-crm-icon.png',
            ];
        }

        $canAddTags = \FluentCrm\App\Services\PermissionManager::currentUserCan('fcrm_manage_contacts');

        $canAddTags = apply_filters('fluent_support/can_user_add_tags_to_customer', $canAddTags);
        $crmTags = [];
        $crmLists = [];
        if ($canAddTags) {
            $crmTags = \FluentCrm\App\Models\Tag::select(['id', 'title'])->oldest('title')->get();
            $crmLists = \FluentCrm\App\Models\Lists::select(['id', 'title'])->oldest('title')->get();
        }

        $crmConfigs = [
            'can_add_tags' => $canAddTags,
            'tags'         => $crmTags,
            'lists'        => $crmLists,
            'logo'         => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/fluentcrm-logo.svg',
            'icon'         => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/fluent-crm-icon.png',
        ];

        if (defined('FLUENTCRM')) {
            $crmConfigs['contacts'] = []; //(new \FluentCrm\App\Models\Subscriber)->get();
        }

        return $crmConfigs;
    }

    /**
     * getFluentCrmContactData method will get information from fluent crm using user email
     * @param $customer
     * @return array|false
     */
    public static function getFluentCrmContactData($customer)
    {
        if (!defined('FLUENTCRM')) {
            return false;
        }
        //Get contact info from FluentCRM using customer email
        $contact = \FluentCrmApi('contacts')->getContactByUserRef($customer->email);
        if ($contact) {
            $tags = $contact->tags;
            $lists = $contact->lists;
            $urlBase = apply_filters('fluentcrm_menu_url_base', admin_url('admin.php?page=fluentcrm-admin#/'));
            $crmProfileUrl = $urlBase . 'subscribers/' . $contact->id;

            //Return contact data
            return [
                'id'            => $contact->id,
                'first_name'    => $contact->first_name,
                'last_name'     => $contact->last_name,
                'full_name'     => $contact->full_name,
                'name_mismatch' => $contact->full_name != $customer->full_name,
                'tags'          => $tags,
                'lists'         => $lists,
                'status'        => $contact->status,
                'stats'         => $contact->stats(),
                'view_url'      => $crmProfileUrl
            ];
        }

        return false;
    }

    public static function openAIIntegrationStatus()
    {
        $settings = static::getAIProviderSettings();

        if (($settings['enabled'] ?? 'yes') === 'no') {
            return false;
        }

        return !empty($settings['api_key']);
    }

    public static function fluentBotIntegrationStatus()
    {
        // Include object_id and order by id desc to always read the latest row; the
        // write path (saveFluentBotSettings) does not prune siblings, so duplicates
        // may exist and a non-deterministic read can return stale state.
        $meta = Meta::where([
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_config'
        ])->orderByDesc('id')->first();

        $settings = static::safeUnserialize($meta ? $meta->value : null);

        return isset($settings['isEnabled']) && filter_var($settings['isEnabled'], FILTER_VALIDATE_BOOLEAN);
    }


    public static function showTicketSummaryAdminBar()
    {
        $data = self::getOption('global_business_settings');

        if ($data && isset($data["enable_admin_bar_summary"]) && $data["enable_admin_bar_summary"] == 'yes') {
            return true;
        }

        return false;
    }

    public static function generateMessageID($email)
    {
        if (!$email || !is_string($email)) {
            return false;
        }

        $emailParts = explode('@', $email);
        if (count($emailParts) != 2) {
            return false;
        }

        $emailDomain = $emailParts[1];
        try {
            return sprintf(
                "<%s.%s@%s>",
                base_convert((int)microtime(true), 10, 36),
                base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
                $emailDomain
            );
        } catch (\Exception $exception) {
            return false;
        }
    }

    public static function getExportOptions()
    {
        $data = [
            'Agent First Name' => __('Agent First Name', 'fluent-support'),
            'Agent Last Name'  => __('Agent Last Name', 'fluent-support'),
            'Agent Full Name'  => __('Agent Full Name', 'fluent-support'),
            'Responses'        => __('Responses', 'fluent-support'),
            'Interactions'     => __('Interactions', 'fluent-support'),
            'Open Tickets'     => __('Open Tickets', 'fluent-support'),
            'Closed'           => __('Closed', 'fluent-support'),
            'Waiting Tickets'  => __('Waiting Tickets', 'fluent-support'),
            'Average Waiting'  => __('Average Waiting', 'fluent-support'),
            'Max Waiting'      => __('Max Waiting', 'fluent-support'),
        ];

        if (Helper::isAgentFeedbackEnabled()) {
            $data['Likes'] = __('Likes', 'fluent-support');
            $data['Dislikes'] = __('Dislikes', 'fluent-support');
        }

        return $data;
    }

    public static function getAuthProvider()
    {
        if (defined('FLUENT_AUTH_PLUGIN_PATH')) {
            $settings = \FluentAuth\App\Helpers\Helper::getAuthFormsSettings();
            if ($settings['enabled'] == 'yes') {
                return 'fluent_auth';
            }
        }

        return 'fluent_support';
    }

    /** @internal Not called from core controllers — available for Pro/hook usage. */
    public static function getDriversKey(){
        return [
            'dropbox_settings',
            'google_drive_settings',
            'cloudflare_r2_settings',
            'amazon_s3_settings',
            'local'
        ];
    }


    public static function getUploadDriverKey()
    {
        if (!defined('FLUENTSUPPORTPRO')) {
            return 'local';
        }

        $driver = self::getOption('file_upload_driver');

        if ($driver) {
            return $driver;
        }

        // Now guess the driver and save it

        // check if dropbox is enabled
        $dropboxSettings = self::getIntegrationOption('dropbox_settings', null);
        if ($dropboxSettings) {
            $dropBoxEnabled = Meta::where('object_type', 'enabled_upload_drivers')
                ->where('key', 'dropbox_settings')
                ->where('value', 'yes')
                ->first();

            if ($dropBoxEnabled) {
                $driver = 'dropbox';
                self::updateOption('file_upload_driver', $driver);
                return $driver;
            }
        }

        // check if google drive is enabled
        $googleDriveSettings = self::getIntegrationOption('google_drive_settings', null);

        if ($googleDriveSettings) {
            $googleDriveEnabled = Meta::where('object_type', 'enabled_upload_drivers')
                ->where('key', 'google_drive_settings')
                ->where('value', 'yes')
                ->first();

            if ($googleDriveEnabled) {
                $driver = 'google_drive';
                self::updateOption('file_upload_driver', $driver);
                return $driver;
            }
        }

        // check if cloudflare r2 is enabled
        $cloudflareR2Settings = self::getIntegrationOption('cloudflare_r2_settings', null);

        if ($cloudflareR2Settings) {
            $cloudflareR2Enabled = Meta::where('object_type', 'enabled_upload_drivers')
                ->where('key', 'cloudflare_r2_settings')
                ->where('value', 'yes')
                ->first();

            if ($cloudflareR2Enabled) {
                $driver = 'cloudflare_r2';
                self::updateOption('file_upload_driver', $driver);
                return $driver;
            }
        }

        // check if amazon s3 is enabled
        $amazonS3Settings = self::getIntegrationOption('amazon_s3_settings', null);

        if ($amazonS3Settings) {
            $amazonS3Enabled = Meta::where('object_type', 'enabled_upload_drivers')
                ->where('key', 'amazon_s3_settings')
                ->where('value', 'yes')
                ->first();

            if ($amazonS3Enabled) {
                $driver = 'amazon_s3';
                self::updateOption('file_upload_driver', $driver);
                return $driver;
            }
        }

        self::updateOption('file_upload_driver', 'local');
        return 'local';
    }

    public static function getIntegrationStatuses()
    {
        $connections = [
            'woocommerce'     => [
                'title'          => __('WooCommerce', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/woocommerce.png',
                'is_integrated'   => defined('WC_PLUGIN_FILE'),
                'description'    => __('The most popular e-commerce platform for WordPress', 'fluent-support'),
                'doc_url'  => 'https://docs.fluentsupport.com/woocommerce-integration',
            ],
            'fluent-cart'     => [
                'title'          => __('FluentCart', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/fluent-cart.webp',
                'is_integrated'  => defined('FLUENTCART_VERSION'),
                'description'    => __('A New Era of eCommerce with WordPress', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/fluentcart-integration',
            ],
            'lifter-lms'     => [
                'title'          => __('LifterLMS', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/lifter-lms.png',
                'is_integrated'   => defined('LLMS_PLUGIN_FILE'),
                'description'    => __('Course and e-learning platform built for WordPress', 'fluent-support'),
                'doc_url'  => 'https://docs.fluentsupport.com/lifterlms-integration',
            ],
            'slack' => [
                'title'          => __('Slack', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/slack.png',
                'is_integrated'   => self::getFSIntegrationStatus('slack_settings'),
                'description'    => __('Business communication platform designed to scale', 'fluent-support'),
                'doc_url'  => 'https://docs.fluentsupport.com/managing-tickets-using-slack',
            ],
            'pm-pro'  => [
                'title'          => __('Paid Memberships Pro', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/pmpro.png',
                'is_integrated'   => defined('PMPRO_VERSION'),
                'description'    => __('The ultimate platform for any member-focused business', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/paid-membership-pro-integration',
            ],
            'tutor-lms'  => [
                'title'          => __('Tutor LMS', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/tutor-lms.png',
                'is_integrated'   => defined('TUTOR_VERSION'),
                'description'    => __('Course and e-learning platform built for WordPress', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/tutorlms-integration',
            ],
            'telegram'  => [
                'title'          => __('Telegram', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/telegram.jpeg',
                'is_integrated'  => self::getFSIntegrationStatus('telegram_settings'),
                'description'    => __('Business communication platform designed for security', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/managing-tickets-using-telegram',
            ],
            'fluent-crm'  => [
                'title'          => __('FluentCRM', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/fluent-crm.png',
                'is_integrated'   => defined('FLUENTCRM'),
                'description'    => __('Self-hosted email and marketing automation for WordPress', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/fluentcrm-integration',
            ],
            'fluent-community'  => [
                'title'          => __('FluentCommunity', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/fluent-community.png',
                'is_integrated'   => defined('FLUENT_COMMUNITY_PLUGIN_VERSION'),
                'description'    => __('Build and manage vibrant online communities with integrated LMS features directly within WordPress.', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/fluent-community-integration',
            ],
            'fluent-forms'  => [
                'title'          => __('Fluent Forms', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/fluent-forms.png',
                'is_integrated'   => defined('FLUENTFORM'),
                'description'    => __('A robust form plugin suitable for any business', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/fluent-form-integration',
            ],
            'buddy-boss'  => [
                'title'          => __('BuddyBoss', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/buddy-boss.png',
                'is_integrated'   => defined('BP_PLUGIN_DIR'),
                'description'    => __('Powerful platform for any member-focused business', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/buddyboss-integration'
            ],
            'discord'  => [
                'title'          => __('Discord', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/discord.png',
                'is_integrated'   => self::getFSIntegrationStatus('discord_settings'),
                'description'    => __('Business communication platform designed for tech', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/managing-tickets-using-discord',
            ],
            'wishlist-member'  => [
                'title'          => __('WishList Member', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/wishlist-member.png',
                'is_integrated'   => defined('WLM3_PLUGIN_VERSION'),
                'description'    => __('Powerful platform for any member-focused business', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/wishlist-member-integration',
            ],
            'easy-digital-downloads'  => [
                'title'          => __('Easy Digital Downloads', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/easy-digital-downloads.png',
                'is_integrated'   => class_exists('\Easy_Digital_Downloads'),
                'description'    => __('The ultimate WordPress platform for digital products', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/edd-integration',
            ],
            'restrict-content-pro'  => [
                'title'          => __('Restrict Content pro', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/restrict-content-pro.png',
                'is_integrated'   => class_exists('\Restrict_Content_Pro' ),
                'description'    => __('Powerful platform for any member-focused business', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/restrict-content-pro-integration',
            ],
            'better-docs'  => [
                'title'          => __('BetterDocs', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/better-docs.png',
                'is_integrated'   => false,
                'description'    => __('The standard plugin for knowledge base and documentation', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/betterdocs-integration',
            ],
            'whatsapp'  => [
                'title'          => __('WhatsApp', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/whatsapp.jpeg',
                'is_integrated'   => self::getFSIntegrationStatus('twilio_settings'),
                'description'    => __('Business communication platform designed for privacy', 'fluent-support'),
                'doc_url'      => 'https://docs.fluentsupport.com/whatsapp-integration-via-twilio',
            ],
            'paymattic'  => [
                'title'          => __('Paymattic', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/paymattic.png',
                'is_integrated'   => defined('WPPAYFORM_VERSION'),
                'description'    => __('All-in-one payment gateway designed for WordPress', 'fluent-support'),
                'doc_url'       => 'https://paymattic.com/docs/how-to-integrate-fluent-support-with-paymattic-in-wordpress/',
            ],
            'learn-dash'  => [
                'title'          => __('LearnDash', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/learn-dash.png',
                'is_integrated'   => defined('LEARNDASH_VERSION'),
                'description'    => __('The leading course platform built for WordPress', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/learndash-integration',
            ],
            'learn-press'  => [
                'title'          => __('LearnPress', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/learn-press.png',
                'is_integrated'   => defined('LP_PLUGIN_FILE'),
                'description'    => __('Course and e-learning platform built for WordPress', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/learnpress-integration',
            ],
            'google-drive'  => [
                'title'          => __('Google Drive', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/google-drive.jpeg',
                'is_integrated'   => self::getFSIntegrationStatus('google_drive_settings'),
                'description'    => __('A cloud storage service by Google for storing, syncing, and sharing files.', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/google-drive-integration'
            ],
            'dropbox'  => [
                'title'          => __('Dropbox', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/dropbox.png',
                'is_integrated'   => self::getFSIntegrationStatus('dropbox_settings'),
                'description'    => __('A cloud-based file storage and sharing service that allows users to store files online and sync them across devices.', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/dropbox-integration',
            ],
            'member-press'  => [
                'title'          => __('MemberPress', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/member-press.png',
                'is_integrated'   => class_exists('MeprUtils'),
                'description'    => __('A WordPress plugin that enables the creation and management of membership sites, including content access control and subscription billing.', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/memberpress-integration'
            ],
            'google-recaptcha'  => [
                'title'          => __('Google reCAPTCHA', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/google-recaptcha.png',
                'is_integrated'   => self::getFSIntegrationStatus('recaptcha_setting'),
                'description'    => __('A security service by Google designed to protect websites from bots and abuse by using challenges to distinguish between human and automated access.', 'fluent-support'),
                'doc_url'       => 'https://docs.fluentsupport.com/google-recaptcha-integration',
            ],
            'fluent-boards'  => [
                'title'          => __('FluentBoards', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/fluent-boards.png',
                'is_integrated'   =>  defined('FLUENT_BOARDS'),
                'description'    => __('A project management tool designed to streamline workflows and collaboration through customizable, kanban-style boards.', 'fluent-support'),
                'doc_url'       => '',
            ],
            'fluent-booking'  => [
                'title'          => __('FluentBooking', 'fluent-support'),
                'logo'           => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/integrations/fluent-booking.svg',
                'is_integrated'  => defined('FLUENT_BOOKING_VERSION'),
                'description'    => __('Appointment and booking management plugin for WordPress', 'fluent-support'),
                'doc_url'        => 'https://docs.fluentsupport.com/fluentbooking-integration',
            ],
        ];

        return $connections;
    }

    public static function getGlobalSettingsMenu()
    {
        $menu = [
            [
                'title' => __('Global Settings', 'fluent-support'),
                'route_name' => 'global_settings',
                'icon' => 'settings',
            ],
            [
                'title' => __('Ticket Tags', 'fluent-support'),
                'route_name' => 'tags',
                'icon' => 'ticketTag',
            ],
            [
                'title' => __('Ticket Form Config', 'fluent-support'),
                'route_name' => 'ticket-form-config',
                'icon' => 'formConfig',
            ],
            [
                'title' => __('Custom Fields', 'fluent-support'),
                'route_name' => 'custom_fields',
                'icon' => 'customFields',
            ],
            [
                'title' => __('Products', 'fluent-support'),
                'route_name' => 'products',
                'icon' => 'products',
            ],
            [
                'title' => __('Support Staff', 'fluent-support'),
                'route_name' => 'support-staffs',
                'icon' => 'supportStaffs',
            ],
            [
                'title' => __('FluentCRM Integration', 'fluent-support'),
                'route_name' => 'fluentcrm_integration',
                'icon' => 'crmIntegration',
            ],
            [
                'title' => __('Incoming Webhook', 'fluent-support'),
                'route_name' => 'incoming-webhook',
                'icon' => 'incomingWebhook',
            ],
            [
                'title' => __('Notification Integrations', 'fluent-support'),
                'route_name' => 'integration',
                'icon' => 'notification',
            ],
            [
                'title' => __('File Upload Integrations', 'fluent-support'),
                'route_name' => 'upload_integration',
                'icon' => 'fileUpload',
            ],
            [
                'title' => __('Auto Close Settings', 'fluent-support'),
                'route_name' => 'auto_close',
                'icon' => 'autoClose',
            ],
            [
                'title' => __('Ticket Importer', 'fluent-support'),
                'route_name' => 'ticket_importer',
                'icon' => 'importer',
            ],
            [
                'title' => __('Recaptcha', 'fluent-support'),
                'route_name' => 'reCaptcha',
                'icon' => 'reCaptcha',
            ],
            [
                'title' => __('Integration Statuses', 'fluent-support'),
                'route_name' => 'integration_statuses',
                'icon' => 'status',
            ],
            [
                'title'    => __('AI Integration', 'fluent-support'),
                'icon'     => 'aiIntegration',
                'children' => [
                    [
                        'title'      => __('AI Model Setup', 'fluent-support'),
                        'route_name' => 'ai_integration',
                    ],
                    [
                        'title'      => __('MCP for AI Agents', 'fluent-support'),
                        'route_name' => 'mcp_settings',
                    ],
                ],
            ],
        ];

        if (defined('FLUENT_SUPPORT_PRO_DIR_FILE')) {
            $menu[] = [
                'title' => __('License Management', 'fluent-support'),
                'route_name' => 'license',
                'icon' => 'license',
            ];
        }

        return apply_filters('fluent_support/settings_menu_items', $menu);
    }

    /** @internal Not called from core controllers — available for Pro/hook usage. */
    public static function getFSIntegrationStatus($connection_name)
    {
        $integrationMap = [
            'slack_settings' => 'slack_settings',
            'discord_settings' => 'discord_settings',
            'twilio_settings' => 'twilio_settings',
            'telegram_settings' => 'telegram_settings',
            'google_drive_settings' => 'google_drive_settings',
            'dropbox_settings' => 'dropbox_settings',
            'recaptcha_setting' => '_fs_recaptcha_settings'
        ];

        if (array_key_exists($connection_name, $integrationMap)) {
            if ($connection_name == 'google_drive_settings' || $connection_name == 'dropbox_settings') {
                return self::checkUploadDriverStatus($connection_name);
            } elseif ($connection_name == 'recaptcha_setting') {
                return self::checkRecaptchaStatus();
            } else {
                return self::checkNotificationIntegrationStatus($integrationMap[$connection_name]);
            }
        }

        return false;
    }

    private static function checkNotificationIntegrationStatus($settingName)
    {
        $settings = self::getIntegrationOption($settingName, null);
        if ($settings) {
            $status = Arr::get($settings, 'status', false);
            return $status ? true : false;
        }
        return false;
    }

    private static function checkUploadDriverStatus($settingName)
    {
        $settings = self::getIntegrationOption($settingName, null);
        if ($settings) {
            $enabled = Arr::get($settings, 'status', false);
            return $enabled ? true : false;
        }
        return false;
    }

    private static function checkRecaptchaStatus()
    {
        $reCaptchaSettingsData = Meta::where('object_type', '_fs_recaptcha_settings')->first();

        if ($reCaptchaSettingsData) {
            $settings = static::safeUnserialize($reCaptchaSettingsData->value);
            $status = Arr::get($settings, 'is_enabled', false);
            return $status == 'true' ? true : false;
        }
        return false;
    }

    public static function getAIActivities($data)
    {
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $perPage = isset($data['per_page']) ? intval($data['per_page']) : 10;

        $activitiesQuery = AIActivityLogs::with([
            'person' => function ($query) {
                $query->select(['first_name', 'person_type', 'last_name', 'id', 'avatar']);
            },
            'ticket' => function ($query) {
                $query->select(['id', 'title']);
            }
        ])->latest('id');

        $from = sanitize_text_field( Arr::get( $data, 'from', '' ) );
        $to = sanitize_text_field( Arr::get( $data, 'to', '') );

        if ( $from != $to ) {
            $from = $from . ' ' . '00:00:00';
            $to = $to . ' ' . '23:59:59';
        }

        if ( ( !empty($from) && !empty($to) ) && $from == $to ) {
            $activitiesQuery->whereDate('created_at', '=', $from);
        } elseif (!empty($from) && !empty($to)) {
            $activitiesQuery->whereBetween('created_at', [ $from, $to ]);
        }

        $agentId = intval( Arr::get($data, 'filters.agent_id') );

        if ($agentId) {
            $activitiesQuery->where('agent_id', $agentId);
        }

        $activities = $activitiesQuery->paginate($perPage, ['*'], 'page', $page);

        $settings = static::getSettings();

        return [
            'data' => $activities->items(),
            'total' => $activities->total(),
            'per_page' => $activities->perPage(),
            'current_page' => $activities->currentPage(),
            'last_page' => $activities->lastPage(),
            'settings' => $settings['ai_activity_settings']
        ];
    }

    public static function updateAISettings($settings)
    {
        $defaults = [
            'delete_days'  => 14,
            'disable_logs' => 'no'
        ];

        $settings = wp_parse_args($settings, $defaults);
        $settings['delete_days'] = (int)$settings['delete_days'];

        Helper::updateOption('_ai_activity_settings', $settings);

        return [
            'message' => __('AI Activity settings have been updated', 'fluent-support')
        ];
    }

    public static function getSettings()
    {
        $settings = Helper::getOption('_ai_activity_settings', []);

        $defaults = [
            'delete_days'  => 14,
            'disable_logs' => 'no'
        ];

        $settings = wp_parse_args($settings, $defaults);

        if (! $settings ) throw new \Exception(esc_html__('No activity settings found', 'fluent-support'));

        return [
            'ai_activity_settings' => $settings
        ];
    }

    /**
     * Check if activity logs are disabled for a given option key
     * @param string $optionKey The option key to check
     * @return bool
     */
    public static function areLogsDisabled($optionKey)
    {
        if (empty($optionKey)) {
            return false;
        }

        $settings = Helper::getOption($optionKey, []);
        $defaults = [
            'disable_logs' => 'no'
        ];
        $settings = wp_parse_args($settings, $defaults);
        return isset($settings['disable_logs']) && $settings['disable_logs'] === 'yes';
    }

    public static function getIp($anonymize = false)
    {
        static $ipAddress;

        if ($ipAddress) {
            return $ipAddress;
        }

        if (empty($_SERVER['REMOTE_ADDR'])) {
            // It's a local cli request
            return '127.0.0.1';
        }

        $ipAddress = '';
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            //If it's a valid Cloudflare request
            if (self::isCfIp($remoteAddr)) {
                //Use the CF-Connecting-IP header.
                $ipAddress = sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
            } else {
                //If it isn't valid, then use REMOTE_ADDR.
                $ipAddress = $remoteAddr;
            }
        } else if ($remoteAddr == '127.0.0.1') {
            // most probably it's local reverse proxy
            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ipAddress = sanitize_text_field(wp_unslash($_SERVER["HTTP_CLIENT_IP"]));
            } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $forwardedFor = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
                $splitResult = preg_split('/,/', $forwardedFor);
                $firstIp = is_array($splitResult) ? current($splitResult) : '';
                $ipAddress = (string)rest_is_ip_address(trim((string)$firstIp));
            }
        }

        if (!$ipAddress) {
            $ipAddress = $remoteAddr;
        }

        if ($ipAddress) {
            $ipAddress = preg_replace('/^(\d+\.\d+\.\d+\.\d+):\d+$/', '\1', $ipAddress);
        }

        $ipAddress = apply_filters('fluent_auth/user_ip', $ipAddress);

        if ($anonymize) {
            return wp_privacy_anonymize_ip($ipAddress);
        }

        $ipAddress = sanitize_text_field(wp_unslash($ipAddress));

        return $ipAddress;
    }

    /**
     * Atomically increments the counter for $rateLimitKey and reports whether the
     * limit is now exceeded. On sites with a persistent external object cache
     * (Redis/Memcached), wp_cache_incr() is a real atomic increment, so concurrent
     * requests can't race past the limit. Without one, this falls back to a plain
     * transient read/write (same best-effort behavior as the rest of this codebase's
     * rate limiters, e.g. AuthController::incrementLoginAttempts).
     *
     * Note the counter is incremented before it is compared, so the returned value
     * already accounts for the current request.
     */
    public static function hitRateLimit($rateLimitKey, $limit, $window = null)
    {
        $window = $window ?: 15 * MINUTE_IN_SECONDS;

        if (wp_using_ext_object_cache()) {
            $group = 'fs_rate_limit';
            if (false === wp_cache_get($rateLimitKey, $group)) {
                wp_cache_add($rateLimitKey, 0, $group, $window);
            }
            $attempts = wp_cache_incr($rateLimitKey, 1, $group);

            // Fail closed: if the cache backend couldn't increment (evicted key, hiccup),
            // treat the request as rate-limited rather than silently letting it through.
            if ($attempts === false) {
                return true;
            }

            return $attempts > $limit;
        }

        $now = time();
        $record = get_transient($rateLimitKey);

        // A record without a live deadline means the window is over (or the record predates
        // this format), so the count starts again. Anything else keeps the deadline it was
        // created with.
        //
        // The `<=` must not be loosened to `<`: it is what guarantees the TTL below is at
        // least 1. A request landing exactly on the deadline would otherwise compute a TTL
        // of 0, and set_transient() reads 0 as "never expires" — wedging this limiter shut
        // permanently.
        if (!is_array($record) || empty($record['expires']) || $record['expires'] <= $now) {
            $record = ['count' => 0, 'expires' => $now + $window];
        }

        $record['count']++;

        // The TTL is the time left until the original deadline, never the full window:
        // set_transient() rewrites expiry on every call, so passing $window here would let
        // rejected requests push the deadline forward and keep a tripped limiter tripped
        // for as long as traffic kept arriving.
        set_transient($rateLimitKey, $record, $record['expires'] - $now);

        return $record['count'] > $limit;
    }

    /** @internal Not called from core controllers — available for Pro/hook usage. */
    public static function isCfIp($ip = '')
    {
        if (!$ip) {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        }
        $cloudflareIPRanges = array(
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '108.162.192.0/18',
            '131.0.72.0/22',
            '141.101.64.0/18',
            '162.158.0.0/15',
            '172.64.0.0/13',
            '173.245.48.0/20',
            '188.114.96.0/20',
            '190.93.240.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17'
        );
        $validCFRequest = false;
        //Make sure that the request came via Cloudflare.
        foreach ($cloudflareIPRanges as $range) {
            //Use the ip_in_range function from Joomla.
            if (self::ipInRange($ip, $range)) {
                //IP is valid. Belongs to Cloudflare.
                return true;
            }
        }

        return false;
    }

    private static function ipInRange($ip, $range)
    {
        if (!$ip || !$range || !is_string($ip) || !is_string($range)) {
            return false;
        }

        if (strpos($range, '/') !== false) {
            // $range is in IP/NETMASK format
            list($range, $netmask) = explode('/', $range, 2);
            if (strpos($netmask, '.') !== false) {
                // $netmask is a 255.255.0.0 format
                $netmask = str_replace('*', '0', $netmask);
                $netmask_dec = ip2long($netmask);
                return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
            } else {
                // $netmask is a CIDR size block
                // fix the range argument
                $x = explode('.', $range);
                while (count($x) < 4) $x[] = '0';
                list($a, $b, $c, $d) = $x;
                $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
                $range_dec = ip2long($range);
                $ip_dec = ip2long($ip);

                # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
                #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

                # Strategy 2 - Use math to create it
                $wildcard_dec = pow(2, (32 - $netmask)) - 1;
                $netmask_dec = ~$wildcard_dec;

                return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
            }
        } else {
            // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
            if (strpos($range, '*') !== false) { // a.b.*.* format
                // Just convert to A-B format by setting * to 0 for A and 255 for B
                $lower = str_replace('*', '0', $range);
                $upper = str_replace('*', '255', $range);
                $range = "$lower-$upper";
            }

            if (strpos($range, '-') !== false) { // A-B format
                list($lower, $upper) = explode('-', $range, 2);
                $lower_dec = (float)sprintf("%u", ip2long($lower));
                $upper_dec = (float)sprintf("%u", ip2long($upper));
                $ip_dec = (float)sprintf("%u", ip2long($ip));
                return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
            }
            return false;
        }
    }

    public static function loadView($template, $data)
    {
        extract($data, EXTR_OVERWRITE);

        $template = sanitize_file_name($template);

        $template = str_replace('.', DIRECTORY_SEPARATOR, $template);

        ob_start();
        include FLUENT_SUPPORT_PLUGIN_PATH . 'app/Views/emails/' . $template . '.php';
        return ob_get_clean();
    }

    public static function isProductRequired()
    {
        $settings = Helper::getOption('_ticket_form_settings', []);
        return Arr::get($settings, 'product_required_field') === 'yes';
    }

    /**
     * Build a fluentsupport.com upgrade/pricing link tagged with UTM params
     * per the standard "Upgrade to Pro" link spec (utm_source is always
     * "fluent-support"; utm_medium reflects free vs pro install).
     */
    public static function getUpgradeUrl($content, $args = [])
    {
        $args = wp_parse_args($args, [
            'campaign' => 'upgrade_pro',
            'base_url' => 'https://fluentsupport.com/pricing'
        ]);

        $params = [
            'utm_source'   => 'fluent-support',
            'utm_medium'   => defined('FLUENTSUPPORTPRO') ? 'pro_plugin' : 'free_plugin',
            'utm_campaign' => $args['campaign'],
            'utm_content'  => $content,
            'utm_term'     => FLUENT_SUPPORT_VERSION,
            'utm_id'       => ''
        ];

        return add_query_arg($params, $args['base_url']);
    }

    /** @internal Not called from core controllers — available for Pro/hook usage. */
    public static function getBusinessBox()
    {
        $businessEmailBoxes = MailBox::select(['id', 'name', 'email', 'mapped_email'])
                                    ->where('box_type', 'email')
                                    ->get();
        return $businessEmailBoxes;
    }

    public static function tempImageMoveUploadDir($ticketId, $contentType, $replyId = null, $personId = null)
    {
        // Fetch content based on the content type
        $content = self::getContentByType($ticketId, $contentType , $replyId);
        if (empty($content)) {
            return;
        }

        // Extract image URLs from the content
        $imageUrls = self::extractImageUrls($content);
        if (empty($imageUrls)) {
            return;
        }

        // Move images to the upload directory and update content
        self::moveImagesAndUpdateContent($imageUrls, $ticketId, $contentType, $content, $replyId, $personId);
    }

    /**
     * Fetch content based on the content type.
     */
    private static function getContentByType($ticketId, $contentType, $replyId)
    {
        if ($contentType == 'ticket-create') {
            $ticket = Ticket::find($ticketId);
            return $ticket ? $ticket->content : null;
        }

        $conversation = Conversation::find($replyId);
        return $conversation ? $conversation->content : null;
    }

    /**
     * Extract image URLs from the content.
     */
    private static function extractImageUrls($content)
    {
        preg_match_all('/<img[^>]+src=(["\'])(.*?)\1/i', (string) $content, $matches);
        return $matches[2] ?? [];
    }

    /**
     * Move images to the upload directory and update content.
     */
    private static function moveImagesAndUpdateContent($imageUrls, $ticketId, $contentType, $content, $replyId, $personId)
    {
        // Get the current site's upload directory
        $uploadDirInfo = wp_upload_dir();
        $uploadsDir = $uploadDirInfo['basedir'];
        $tempDir = $uploadsDir . '/fluent-support/temp_files/';
        $signedAttachments = self::getSignedImageAttachments($imageUrls, $ticketId, $personId);

        foreach ($imageUrls as $imageUrl) {
            $fileHash = self::extractAttachmentHashFromUrl($imageUrl);
            if ($fileHash && isset($signedAttachments[$fileHash]) && ($referenceUrl = self::finalizeSignedTempImage($signedAttachments[$fileHash], $ticketId, $contentType, $replyId, $personId))) {
                $content = str_replace($imageUrl, $referenceUrl, $content);
                continue;
            }

            // Build the absolute path for the temporary file
            $imageRelativePath = $tempDir . basename($imageUrl);
            $absolutePath = $imageRelativePath;

            // Move the file to the ticket-specific folder
            $newFileInfo = UploadService::copyFileTicketFolder($absolutePath, $ticketId);

            // Check if the move was successful
            if (empty($newFileInfo['file_path'])) {
                continue; // Skip if the file couldn't be copied
            }

            // Ensure the new URL is correctly constructed
            $newFileInfo['url'] = trailingslashit($uploadDirInfo['baseurl']) . 'fluent-support/ticket_' . $ticketId . '/' . basename($newFileInfo['file_path']);

            // Replace the old URL with the new one in the content
            $content = str_replace($imageUrl, $newFileInfo['url'], $content);
        }

        // Save the updated content
        self::saveUpdatedContent($ticketId, $contentType, $content, $replyId);
    }

    private static function getSignedImageAttachments($imageUrls, $ticketId, $personId)
    {
        $fileHashes = [];
        foreach ($imageUrls as $imageUrl) {
            $fileHash = self::extractAttachmentHashFromUrl($imageUrl);
            if ($fileHash) {
                $fileHashes[] = $fileHash;
            }
        }

        $fileHashes = array_values(array_unique($fileHashes));
        if (!$fileHashes) {
            return [];
        }

        $attachments = Attachment::whereIn('file_hash', $fileHashes)
            ->where(function ($query) use ($ticketId, $personId) {
                $query->where('ticket_id', $ticketId);

                if ($personId) {
                    $query->orWhere(function ($query) use ($personId) {
                        $query->whereNull('ticket_id')
                            ->where('person_id', $personId);
                    });
                }
            })
            ->get();
        $mappedAttachments = [];
        foreach ($attachments as $attachment) {
            $mappedAttachments[$attachment->file_hash] = $attachment;
        }

        return $mappedAttachments;
    }

    private static function finalizeSignedTempImage($attachment, $ticketId, $contentType, $replyId, $personId)
    {
        if (!$attachment || $attachment->driver !== 'local') {
            return false;
        }

        if ($attachment->ticket_id && intval($attachment->ticket_id) !== intval($ticketId)) {
            return false;
        }

        if (!$attachment->ticket_id && $personId && intval($attachment->person_id) !== intval($personId)) {
            return false;
        }

        if ($attachment->conversation_id && $replyId && intval($attachment->conversation_id) !== intval($replyId)) {
            return false;
        }

        if ($attachment->conversation_id && $contentType === 'ticket-create') {
            return false;
        }

        if ($attachment->status !== 'in-active') {
            return $attachment->ticket_id ? self::getAttachmentReferenceUrl($attachment) : false;
        }

        if (!$attachment->file_path || !file_exists($attachment->file_path)) {
            return false;
        }

        $newFileInfo = UploadService::copyFileTicketFolder($attachment->file_path, $ticketId);
        if (empty($newFileInfo['file_path'])) {
            return false;
        }

        $attachment->file_path = $newFileInfo['file_path'];
        $attachment->full_url = $newFileInfo['url'];
        $attachment->ticket_id = $attachment->ticket_id ?: $ticketId;
        $attachment->status = 'inline';

        if ($contentType !== 'ticket-create' && $replyId) {
            $attachment->conversation_id = $replyId;
        }

        $attachment->save();

        return self::getAttachmentReferenceUrl($attachment);
    }

    private static function extractAttachmentHashFromUrl($imageUrl)
    {
        $decodedUrl = html_entity_decode($imageUrl, ENT_QUOTES, 'UTF-8');
        $query = wp_parse_url($decodedUrl, PHP_URL_QUERY);

        if (!$query) {
            return '';
        }

        parse_str($query, $params);

        return !empty($params['fst_file']) ? sanitize_text_field($params['fst_file']) : '';
    }

    private static function getAttachmentReferenceUrl($attachment)
    {
        return add_query_arg([
            'fst_file' => $attachment->file_hash
        ], site_url('/index.php'));
    }

    public static function refreshSignedAttachmentUrls($content, $ticketId = null)
    {
        $contents = self::refreshSignedAttachmentUrlsInContents([$content], $ticketId);

        return $contents[0];
    }

    public static function refreshSignedAttachmentUrlsInContents($contents, $ticketId = null)
    {
        $fileHashes = [];

        foreach ($contents as $content) {
            foreach (self::extractImageUrls($content) as $imageUrl) {
                $fileHash = self::extractAttachmentHashFromUrl($imageUrl);
                if ($fileHash) {
                    $fileHashes[] = $fileHash;
                }
            }
        }

        $fileHashes = array_values(array_unique($fileHashes));
        if (!$fileHashes) {
            return $contents;
        }

        $attachmentsQuery = Attachment::whereIn('file_hash', $fileHashes);
        if ($ticketId) {
            $attachmentsQuery->where('ticket_id', $ticketId);
        }

        $attachments = $attachmentsQuery->get();
        $attachmentsByHash = [];

        foreach ($attachments as $attachment) {
            $attachmentsByHash[$attachment->file_hash] = $attachment;
        }

        foreach ($contents as $key => $content) {
            foreach (self::extractImageUrls($content) as $imageUrl) {
                $fileHash = self::extractAttachmentHashFromUrl($imageUrl);
                if (!$fileHash || empty($attachmentsByHash[$fileHash])) {
                    continue;
                }

                $content = str_replace($imageUrl, $attachmentsByHash[$fileHash]->secureUrl, $content);
            }

            $contents[$key] = $content;
        }

        return $contents;
    }

    /**
     * Save the updated content based on the content type.
     */
    private static function saveUpdatedContent($ticketId, $contentType, $content, $replyId)
    {
        if ($contentType == 'ticket-create') {
            $ticket = Ticket::find($ticketId);
            if ($ticket) {
                $ticket->content = $content;
                $ticket->save();
            }
        } else {
            $conversation = Conversation::find($replyId);
            if ($conversation) {
                $conversation->content = $content;
                $conversation->save();
            }
        }
    }

    /**
     * Throw a ValidationException with a safe error message.
     * In debug mode the real exception message is used; in production a generic message is returned.
     * Throwing instead of returning ensures the error bypasses the framework's HTTP exception
     * wrapper (which would prepend a status-code prefix to the message).
     *
     * @param \Throwable $e The original exception.
     * @throws \FluentSupport\Framework\Validator\ValidationException Always thrown.
     */
    public static function getSafeErrorMessage($e)
    {
        $message = $e->getMessage() ?: __('Something went wrong. Please try again later.', 'fluent-support');

        // ValidationException carries the message through the framework's JSON error
        // handler — it is never echoed directly. $e is the chained previous exception,
        // not output. phpcs:disable covers the multi-line constructor call.
        // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new \FluentSupport\Framework\Validator\ValidationException(
            '', 422, ($e instanceof \Exception ? $e : null), ['message' => $message]
        );
        // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
    }

    /**
     * Safely unserialize data.
     *
     * @param string $data The serialized data.
     * @return mixed The unserialized data or the original data if not serialized.
     */
    public static function safeUnserialize($data)
    {
        if (is_serialized($data)) { // Don't attempt to unserialize data that wasn't serialized going in.
            return @unserialize(trim($data), ['allowed_classes' => false]);
        }

        return $data;
    }
}
