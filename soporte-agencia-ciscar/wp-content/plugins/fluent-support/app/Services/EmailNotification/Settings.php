<?php

namespace FluentSupport\App\Services\EmailNotification;
use FluentSupportPro\Database\Migrations\TimeTrackMigrator;

use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Notifications\NotificationSettings;
use FluentSupport\Framework\Support\Arr;

class Settings
{
    public function getEmailSettingsKeys()
    {
        $key = apply_filters('fluent_support/email_setting_keys', [
            'ticket_created_email_to_customer',
            'ticket_replied_by_agent_email_to_customer',
            'ticket_closed_by_agent_email_to_customer',
            'ticket_created_email_to_admin',
            'ticket_replied_by_customer_email_to_admin',
            'ticket_agent_on_change',
            'ticket_created_by_agent_email_to_customer',
            'ticket_created_by_agent_on_behalf_email_to_customer'
        ]);
        return $key;
    }

    public function get($settingsKey)
    {
        if ($settingsKey == 'global_business_settings') {
            return [
                'settings' => $this->globalBusinessSettings(),
                'fields'   => $this->getGlobalBusinessSettingsFields()
            ];
        }

        if ($settingsKey == NotificationSettings::OPTION_KEY) {
            $notificationSettings = new NotificationSettings();

            return [
                'settings' => $notificationSettings->get(false),
                'fields'   => $notificationSettings->getFields()
            ];
        }

        return [
            'settings' => [],
            'fields'   => []
        ];
    }

    /**
     * save method will save the requested settings by settings key
     * @param $settingsKey
     * @param $settings
     * @return mixed
     */
    public function save($settingsKey, $settings)
    {
        if ($settingsKey == 'global_business_settings' && is_array($settings) && array_key_exists('internal_notifications_enabled', $settings)) {
            $notificationSettings = new NotificationSettings();
            $savedNotificationSettings = $notificationSettings->get(false);
            $savedNotificationSettings['enabled'] = $settings['internal_notifications_enabled'];
            $notificationSettings->save($savedNotificationSettings);
        }

        if ($settingsKey == 'global_business_settings' && empty($settings['accepted_file_types'])) {
            $settings['accepted_file_types'] = [];
        }

        if ($settingsKey == 'global_business_settings') {
            $settings['ticket_link_portal_migrated'] = 'yes';
            if (isset($settings['min_serial_number'])) {
                $settings['min_serial_number'] = max(1, (int) $settings['min_serial_number']);
            }
        }

        if ($settingsKey == 'global_business_settings' && !empty($settings['agent_time_tracking'])) {
            if (class_exists(TimeTrackMigrator::class) && $settings['agent_time_tracking'] === 'yes') {
                TimeTrackMigrator::migrate();
            }
        }

        if ($settingsKey == NotificationSettings::OPTION_KEY) {
            return (new NotificationSettings())->save($settings);
        }

        $result = Helper::updateOption($settingsKey, $settings);

        return $result;
    }

    /**
     * globalBusinessSettings method will fetch global settings from database, parse and return
     * @param bool $cached
     * @return array|mixed
     */
    public function globalBusinessSettings($cached = true)
    {
        static $settings;

        if($cached && $settings) {
            return $settings;
        }

        $defaults = [
            'portal_page_id'        => '',
            'ticket_link_portal'    => 'default',
            'ticket_link_portal_migrated' => 'no',
            // translators: %1$s is opening paragraph tag, %2$s is closing paragraph tag
            'login_message'         => sprintf(__('%1$sPlease login or create an account to access the Customer Support Portal%2$s [fluent_support_auth]', 'fluent-support'), '<p>', '</p>'),
            'disable_public_ticket' => 'no',
            'accepted_file_types'   => ['images', 'csv', 'documents', 'zip', 'json'],
            'max_file_size'         => 2,
            'max_file_upload'       => 3,
            'del_files_on_close'    => 'no',
            'enable_admin_bar_summary' => 'no',
            'internal_notifications_enabled' => 'no',
            'enable_draft_mode' => 'no',
            'agent_feedback_rating' => 'no',
            'keyboard_shortcuts'   => 'yes',
            'enable_min_serial_number' => 'no',
            'ticket_prefix'        => '',
            'min_serial_number'    => 1,
            'enable_fluent_booking_integration' => 'no',
        ];

        //Get default/existing settings from database using the key global_business_settings
        $existingSettings = Helper::getOption('global_business_settings', []);

        if (!$existingSettings) {
            $settings = $defaults;
            return $settings;
        }

        $settings = wp_parse_args($existingSettings, $defaults);
        $settings['ticket_link_portal'] = $settings['ticket_link_portal'] ?: 'default';

        if (Arr::get($settings, 'ticket_link_portal_migrated') !== 'yes') {
            $settings['ticket_link_portal'] = $this->resolveLegacyTicketLinkPortal($settings);
            $settings['ticket_link_portal_migrated'] = 'yes';
            Helper::updateOption('global_business_settings', $settings);
        }

        return $settings;
    }

    private function resolveLegacyTicketLinkPortal($settings)
    {
        $selectedPortal = Arr::get($settings, 'ticket_link_portal', 'default');

        if ($selectedPortal && $selectedPortal !== 'default') {
            return $selectedPortal;
        }

        $ticketFormSettings = Helper::getOption('_ticket_form_settings', []);

        if (Arr::get($settings, 'enable_fc_menu') === 'yes') {
            return 'fluent_cart';
        }

        // The legacy pro plugin defaulted enable_woo_menu to 'yes', so treat an
        // absent value the same as 'yes' — only skip migration if explicitly 'no'.
        // WC portal was always Pro-only, so require Pro to be active as well.
        if (defined('FLUENTSUPPORTPRO_PLUGIN_VERSION') && defined('WC_PLUGIN_FILE') && Arr::get($ticketFormSettings, 'enable_woo_menu', 'yes') !== 'no') {
            return 'woocommerce';
        }

        return 'default';
    }


    /**
     * getGlobalBusinessSettingsFields method will prepare the list of field, and it's property that will be used in global settings form
     * @return array
     */
    private function getGlobalBusinessSettingsFields()
    {
        $nextSerialNumber = \FluentSupport\App\Models\Ticket::getNextSerialNumber();

        $mimeGroups = Helper::getMimeGroups();

        $formattedMimeGroups = [];

        foreach ($mimeGroups as $mimeGroup => $mime) {
            $formattedMimeGroups[$mimeGroup] = $mime['title'];
        }

        $customRegistrationFormOptions = array(
            'address_line_1' => 'Address Line 1',
            'address_line_2' => 'Address Line 2',
            'city' => 'City',
            'zip' => 'Zip Code',
            'state' => 'State',
            'country' => 'Country',
        );

        $fields = [
            'ticket_link_portal_description' => [
                'type'        => 'html-viewer',
                'label'       => __('Customer Portal', 'fluent-support'),
                'wrapper_class' => 'fs_portal_destination_description',
            ],
            'ticket_link_portal' => [
                'type'        => 'input-radio',
                'wrapper_class' => 'fs_portal_destination_options',
                'options'     => $this->getTicketLinkPortalOptions(),
            ],
            'fluent_community_portal_link' => [
                'type'          => 'html-viewer',
                'wrapper_class' => 'fs_fluent_community_portal_link',
                'html'          => $this->getFluentCommunityPortalLinkHtml(),
                'dependency'    => [
                    'depends_on' => 'ticket_link_portal',
                    'operator'   => '=',
                    'value'      => 'fluent_community'
                ],
            ],
            'portal_page_id'        => [
                'type'        => 'input-options',
                'label'       => __('Select a Page', 'fluent-support'),
                'wrapper_class' => 'fs_portal_page_selector',
                'show_id'     => true,
                'placeholder' => __('Select Portal Page', 'fluent-support'),
                'options'     => Helper::getWPPages(),//Get list of published pages
                'inline_help' => __('Please provide the page id where you want to show the tickets for your customers. Use shortcode <code>[fluent_support_portal]</code> in that page', 'fluent-support'),
                'admin_url'   => admin_url(),
                'home_url'    => home_url('/'),
                'dependency'  => [
                    'depends_on' => 'ticket_link_portal',
                    'operator'   => '=',
                    'value'      => 'default'
                ],
            ],
            'login_message'         => [
                'type'        => 'wp-editor',
                'label'       => __('Message for non logged in users', 'fluent-support'),
                'inline_help' => __('Please provide message for not logged in users. You can place login shortcode too. Use shortcode <code>[fluent_support_login]</code> to show built-in login form. For the user registration use this shortcode <code>[fluent_support_signup]</code> and for both form please use <code>[fluent_support_auth]</code>', 'fluent-support')
            ],
            'disable_public_ticket' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'label'       => __('Public Ticket Interaction', 'fluent-support'),
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Disable Public Ticket interaction', 'fluent-support'),
                'inline_help'    => __('If you enable this then only logged in user can reply the tickets. Otherwise, url will be signed and intended user can reply without logging in', 'fluent-support')
            ],
            'accepted_file_types'   => [
                'wrapper_class' => '',
                'type'    => 'checkbox-group',
                'label'   => __('Accepted File Types', 'fluent-support'),
                'options' => $formattedMimeGroups
            ],
            'max_file_size' => [
                'wrapper_class' => 'fs_settings_half_field',
                'type'    => 'input-text',
                'data_type' => 'number',
                'label'   => __('Max File Size (in MegaByte)', 'fluent-support'),
            ],
            'max_file_upload' => [
                'wrapper_class' => 'fs_settings_half_field',
                'type'    => 'input-text',
                'data_type' => 'number',
                'label'   => __('Maximum File Upload', 'fluent-support'),
            ],
            'del_files_on_close' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Delete all attachments on ticket close', 'fluent-support'),
                'inline_help'    => __('If you enable this feature, all attachments associated with a ticket will be deleted when the ticket is closed.', 'fluent-support')
            ],
            'enable_admin_bar_summary' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Fluent Summary In Admin Bar', 'fluent-support'),
                'inline_help'    => __('If you enable this, logged in user can see the ticket summary from top nav bar.', 'fluent-support')
            ],
            'internal_notifications_enabled' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Internal Notifications', 'fluent-support'),
                'inline_help'    => sprintf(
                    '<ul><li>%1$s</li><li>%2$s</li></ul>',
                    __('Enable the in-app notification bell, unread counts, and notification event storage for agents.', 'fluent-support'),
                    __('Notification data older than 15 days will be removed automatically.', 'fluent-support')
                )
            ],
            'enable_draft_mode' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Draft Mode', 'fluent-support'),
                'inline_help'    => __('If you enable this setting, any written response will be saved as a draft if an agent accidentally closes a ticket.', 'fluent-support')
            ],
            'custom_registration_form_field'   => [
                'wrapper_class' => '',
                'type'    => 'checkbox-group',
                'label'   => __('Custom Registration Form Field', 'fluent-support'),
                'options' => $customRegistrationFormOptions
            ],
            'enable_two_fa' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Two-Factor Authentication', 'fluent-support'),
                'inline_help'    => __('If you enable this setting, users will be required to submit a second form of authentication, such as a code sent to their email, to login.', 'fluent-support')
            ],
            'keyboard_shortcuts' => [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Keyboard Shortcuts', 'fluent-support'),
                'inline_help'    => __("If you enable this, agents can use keyboard shortcuts for faster actions.", 'fluent-support'),
                'shortcut_modal' => $this->getKeyboardShortcutModalData()
            ],
            'enable_min_serial_number' => [
                'wrapper_class'  => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Minimum Ticket Number', 'fluent-support'),
                'inline_help'    => __('Enable this to start public ticket numbers from a custom minimum number.', 'fluent-support')
            ],
            'min_serial_number' => [
                'wrapper_class'      => 'fs_settings_half_field',
                'type'               => 'input-text',
                'data_type'          => 'number',
                'label'              => __('Minimum Ticket Number', 'fluent-support'),
                'help'               => __('Once a ticket is created, you cannot lower this value below the current highest ticket number. Set this carefully — raising it is easy, but lowering it below an existing ticket number is not allowed.', 'fluent-support'),
                'inline_help'        => sprintf(
                    __('Next Ticket Number: %d', 'fluent-support'),
                    $nextSerialNumber
                ),
                'next_serial_number' => $nextSerialNumber,
                'dependency'         => [
                    'depends_on' => 'enable_min_serial_number',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ],
            'ticket_prefix' => [
                'wrapper_class' => 'fs_settings_half_field',
                'type'          => 'input-text',
                'data_type'     => 'text',
                'label'         => __('Ticket Prefix', 'fluent-support'),
                'inline_help'   => __('Optional prefix for newly created public ticket numbers.', 'fluent-support'),
                'dependency'    => [
                    'depends_on' => 'enable_min_serial_number',
                    'operator'   => '=',
                    'value'      => 'yes'
                ]
            ]
        ];
        if (defined('FLUENT_BOOKING_VERSION')) {
            $fields['enable_fluent_booking_integration'] = [
                'wrapper_class'  => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Enable Fluent Booking Integration', 'fluent-support'),
                'inline_help'    => __('If you enable this, agents can create booking links and view meeting details inside tickets.', 'fluent-support')
            ];
        }

        if (defined('FLUENTSUPPORTPRO_PLUGIN_VERSION')) {
            $fields['agent_feedback_rating'] = [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Agent Feedback Rating', 'fluent-support'),
                'inline_help'    => __("If you enable this setting, users will have the option to provide feedback on an agent's response.", 'fluent-support')
            ];

            $fields['agent_time_tracking'] = [
                'wrapper_class' => '',
                'type'           => 'inline-checkbox',
                'true_label'     => 'yes',
                'false-label'    => 'no',
                'checkbox_label' => __('Agent Time Tracking', 'fluent-support'),
                'inline_help' => __("If you enable this setting, the agent can specify the amount of time needed to complete a ticket.", 'fluent-support')
            ];

        }

        return $fields;
    }

    private function getTicketLinkPortalOptions()
    {
        $options = [
            [
                'id'    => 'default',
                'label' => __('Default Portal Page', 'fluent-support'),
            ],
        ];

        if (Helper::isPortalActive('fluent_cart')) {
            $options[] = [
                'id'    => 'fluent_cart',
                'label' => __('FluentCart Account Navigation', 'fluent-support'),
            ];
        }

        if (Helper::isPortalActive('woocommerce')) {
            $options[] = [
                'id'    => 'woocommerce',
                'label' => __('WooCommerce Account Navigation', 'fluent-support'),
            ];
        }

        if (Helper::isPortalActive('fluent_community')) {
            $options[] = [
                'id'    => 'fluent_community',
                'label' => __('FluentCommunity Portal', 'fluent-support'),
            ];
        }

        return apply_filters('fluent_support/ticket_link_portal_options', $options);
    }

    private function getFluentCommunityPortalLinkHtml()
    {
        if (!defined('FLUENT_COMMUNITY_PLUGIN_VERSION') || !class_exists('\FluentCommunity\App\Services\Helper')) {
            return '';
        }

        $portalLink = rtrim(\FluentCommunity\App\Services\Helper::baseUrl('support/'), '/\\') . '/';

        return '<div class="fs_portal_link_content">'
            . '<span class="fs_portal_link_label">' . esc_html__('FluentCommunity Portal URL', 'fluent-support') . '</span>'
            . '<button type="button" class="fs_portal_link_token" data-portal-link="' . esc_attr($portalLink) . '">'
            . '<span class="fs_portal_link_text">' . esc_html($portalLink) . '</span>'
            . '<span class="fs_portal_link_action">' . esc_html__('Copy', 'fluent-support') . '</span>'
            . '</button>'
            . '<p class="fc_inline_help">' . esc_html__('Copy this URL and add it to your FluentCommunity menu.', 'fluent-support') . '</p>'
            . '</div>';
    }

    /**
     * Returns translatable keyboard shortcut modal data for the settings form.
     *
     * @return array{title: string, mac_label: string, win_label: string, action_label: string, shortcut_label: string, mac_shortcuts: array, win_shortcuts: array}
     */
    public function getKeyboardShortcutModalData()
    {
        $actionLabel = __('Action', 'fluent-support');
        $shortcutLabel = __('Shortcut', 'fluent-support');

        $macShortcuts = [
            ['action' => __('Tickets – Advanced filter toggle', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + f'],
            ['action' => __('Tickets – Refresh', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + q'],
            ['action' => __('Tickets – Create Ticket', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + n'],
            ['action' => __('Tickets – Reset filter', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + r'],
            ['action' => __('Tickets – All Tickets', 'fluent-support'), 'shortcut' => '⌘ + ⇧ + a'],
            ['action' => __('Tickets – My Tickets', 'fluent-support'), 'shortcut' => '⌘ + ⇧ + m'],
            ['action' => __('Tickets – Unassigned', 'fluent-support'), 'shortcut' => '⌘ + ⇧ + u'],
            ['action' => __('Tickets – Waiting for reply', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + w'],
            ['action' => __('Tickets – Bookmarks', 'fluent-support'), 'shortcut' => '⌘ + ⇧ + b'],
            ['action' => __('Tickets – Toggle (Open, active, close, all)', 'fluent-support'), 'shortcut' => '⌘ + ⇧ + → / ←'],
            ['action' => __('Ticket Reply – Reply', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + r'],
            ['action' => __('Ticket Reply – Personal note', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + n'],
            ['action' => __('Ticket Reply – Merge', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + m'],
            ['action' => __('Ticket Reply – Bookmarks', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + b'],
            ['action' => __('Ticket Reply – Refresh', 'fluent-support'), 'shortcut' => '⌘ + ⌥ + q'],
        ];

        $winShortcuts = [
            ['action' => __('Tickets – Advanced filter toggle', 'fluent-support'), 'shortcut' => 'Alt + Win + Shift + f'],
            ['action' => __('Tickets – Refresh', 'fluent-support'), 'shortcut' => 'Alt + Win + q'],
            ['action' => __('Tickets – Create Ticket', 'fluent-support'), 'shortcut' => 'Alt + Win + n'],
            ['action' => __('Tickets – Reset filter', 'fluent-support'), 'shortcut' => 'Alt + Win + Shift + r'],
            ['action' => __('Tickets – All Tickets', 'fluent-support'), 'shortcut' => 'Win + Shift + a'],
            ['action' => __('Tickets – My Tickets', 'fluent-support'), 'shortcut' => 'Alt + Win + Shift + m'],
            ['action' => __('Tickets – Unassigned', 'fluent-support'), 'shortcut' => 'Win + Shift + u'],
            ['action' => __('Tickets – Waiting for reply', 'fluent-support'), 'shortcut' => 'Alt + Win + Shift + w'],
            ['action' => __('Tickets – Bookmarks', 'fluent-support'), 'shortcut' => 'Win + Shift + b'],
            ['action' => __('Ticket Reply – Reply', 'fluent-support'), 'shortcut' => 'Alt + Win + Shift + r'],
            ['action' => __('Ticket Reply – Personal note', 'fluent-support'), 'shortcut' => 'Alt + Win + n'],
            ['action' => __('Ticket Reply – Merge', 'fluent-support'), 'shortcut' => 'Ctrl + Alt + Win + m'],
            ['action' => __('Ticket Reply – Bookmarks', 'fluent-support'), 'shortcut' => 'Ctrl + Alt + Win + b'],
            ['action' => __('Ticket Reply – Refresh', 'fluent-support'), 'shortcut' => 'Alt + Win + q'],
        ];

        return [
            'title'         => __('Keyboard Shortcuts', 'fluent-support'),
            'mac_label'     => __('macOS', 'fluent-support'),
            'win_label'     => __('Windows', 'fluent-support'),
            'action_label'  => $actionLabel,
            'shortcut_label'=> $shortcutLabel,
            'mac_shortcuts' => $macShortcuts,
            'win_shortcuts' => $winShortcuts,
        ];
    }

    public function saveBoxEmailSettings($box, $emailKey, $settings)
    {
        return $box->saveMeta('_email_' . $emailKey, $settings);
    }

    /**
     * getBoxEmailSettings method will reply the email settings
     * @param $box
     * @param $emailKey
     * @return array|false
     */
    public function getBoxEmailSettings($box, $emailKey)
    {
        if (!$box) {
            return false;
        }

        #ticket_closed_by_agent_email_to_customer 2 times, is it wrong or right!!!
        $strictSubjectKeys = apply_filters('fluent_support/strict_subjects', [
            'ticket_replied_by_agent_email_to_customer',
            'ticket_closed_by_agent_email_to_customer',
            'ticket_created_email_to_customer'
        ]);

        $settingsDefaults = [
            'ticket_created_email_to_customer'          => [
                'key'            => 'ticket_created_email_to_customer',
                'title'          => __('Ticket Created (To Customer)', 'fluent-support'),
                'description'    => __('This email will be sent when a customer submit a support ticket', 'fluent-support'),
                'email_subject'  => 'Re: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'no',
                'send_attachments'=> 'no'
            ],
            'ticket_replied_by_agent_email_to_customer' => [
                'key'            => 'ticket_replied_by_agent_email_to_customer',
                'title'          => __('Replied by Agent (To Customer)', 'fluent-support'),
                'description'    => __('This email will be sent when an agent reply to a ticket', 'fluent-support'),
                'email_subject'  => 'Re: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'yes',
                'send_attachments'=> 'no'
            ],
            'ticket_closed_by_agent_email_to_customer'  => [
                'key'            => 'ticket_closed_by_agent_email_to_customer',
                'title'          => __('Ticket Closed by Agent (To Customer)', 'fluent-support'),
                'description'    => __('This email will be sent when an agent close a ticket', 'fluent-support'),
                'email_subject'  => 'Re: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'no',
                'send_attachments'=> 'no'
            ],
            'ticket_created_email_to_admin'             => [
                'key'            => 'ticket_created_email_to_admin',
                'title'          => __('Ticket Created (To Admin)', 'fluent-support'),
                'description'    => __('This email will be sent when the business when a new ticket has been submitted', 'fluent-support'),
                'email_subject'  => 'New Ticket: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'yes',
                'send_attachments'=> 'no'
            ],
            'ticket_replied_by_customer_email_to_admin' => [
                'key'            => 'ticket_replied_by_customer_email_to_admin',
                'title'          => __('Replied by Customer (To Agent/Admin)', 'fluent-support'),
                'description'    => __('This email will be sent to Assigned Agent or Admin when a customer reply to a ticket', 'fluent-support'),
                'email_subject'  => 'New Response: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'yes',
                'send_attachments'=> 'no'
            ],
            'ticket_agent_on_change' => [
                'key'            => 'ticket_agent_on_change',
                'title'          => __('Ticket Agent Change (To Agent)', 'fluent-support'),
                'description'    => __('This email will be sent to newly assigned agent', 'fluent-support'),
                'email_subject'  => 'Ticket Agent Change: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'yes',
                'send_attachments'=> 'no'
            ],
            'ticket_created_by_agent_email_to_customer' => [
                'key' => 'ticket_created_by_agent_email_to_customer',
                'title' => __('Ticket Logged by Agent (To Customer)', 'fluent-support'),
                'description' => __('This email will be sent when an agent logs a ticket on behalf of a customer (e.g. from a phone call or email received outside the system)', 'fluent-support'),
                'email_subject' => 'Re: {{ticket.title}} #{{ticket.id}}',
                'default_status' => 'no',
                'send_attachments'=> 'no'
            ],
            'ticket_created_by_agent_on_behalf_email_to_customer' => [
                'key' => 'ticket_created_by_agent_on_behalf_email_to_customer',
                'title' => __('Agent Outreach Ticket (To Customer)', 'fluent-support'),
                'description' => __('This email will be sent when an agent creates a ticket using the "Agent Initiated" option to proactively reach out to a customer — includes the agent\'s message content', 'fluent-support'),
                'email_subject' => 'Your ticket has been created (#{{ticket.id}})',
                'default_status' => 'yes',
                'send_attachments'=> 'no'
            ]
        ];

        if (!isset($settingsDefaults[$emailKey])) {
            return false;
        }

        $savedSettings = (array)$box->getMeta('_email_' . $emailKey, []);


        if (!$savedSettings) {
            $savedSettings = [
                'key'              => $settingsDefaults[$emailKey]['key'],
                'title'            => $settingsDefaults[$emailKey]['title'],
                'email_subject'    => $settingsDefaults[$emailKey]['email_subject'],
                'email_body'       => $this->getDefaultEmailBody($emailKey, $box->box_type),
                'status'           => $settingsDefaults[$emailKey]['default_status'],
                'can_edit_subject' => (in_array($emailKey, $strictSubjectKeys) && $box->box_type == 'email') ? 'no' : 'yes',
                'send_attachments' => $settingsDefaults[$emailKey]['send_attachments']
            ];

            if ($box->box_type == 'email' && in_array($emailKey, $strictSubjectKeys)) {
                $savedSettings['email_subject'] = 'Re: {{ticket.title}}';
                $savedSettings['can_edit_subject'] = 'no';
            }

            return $savedSettings;
        }

        $savedSettings['key'] = $settingsDefaults[$emailKey]['key'];
        $savedSettings['title'] = $settingsDefaults[$emailKey]['title'];
        $savedSettings['description'] = $settingsDefaults[$emailKey]['description'];

        if ($box->box_type == 'email' && in_array($emailKey, $strictSubjectKeys)) {
            $savedSettings['email_subject'] = 'Re: {{ticket.title}}';
            $savedSettings['can_edit_subject'] = 'no';
        }

        if (empty($savedSettings['email_subject'])) {
            $savedSettings['email_subject'] = $settingsDefaults[$emailKey]['email_subject'];
        }

        if (empty($savedSettings['status'])) {
            $savedSettings['status'] = $settingsDefaults[$emailKey]['default_status'];
        }

        if (empty($savedSettings['email_body'])) {
            $savedSettings['email_body'] = $this->getDefaultEmailBody($emailKey, $box->box_type);
        }

        if (empty($savedSettings['send_attachments'])) {
            $savedSettings['send_attachments'] = $settingsDefaults[$emailKey]['send_attachments'];
        }

        return $savedSettings;
    }

    /**
     * getDefaultEmailBody method will return html for email body
     * @param $emailKey
     * @param string $type
     * @return string
     */
    private function getDefaultEmailBody($emailKey, $type = 'web')
    {
        if ($emailKey == 'ticket_created_email_to_customer') {
            if ($type == 'web') {
                return '<p>Hi <strong><em>{{customer.full_name}}</em>,</strong></p><p>Your request (<a href="{{ticket.public_url}}">#{{ticket.id}}</a>) has been received, and is being reviewed by our support staff.</p><p>To add additional comments, follow the link below:</p><h4><a href="{{ticket.public_url}}">View Ticket</a></h4><p>&nbsp;</p><p>or follow this link: {{ticket.public_url}}</p><hr /><p>{{business.name}}</p>';
            } else {
                return '<p>Hi <strong><em>{{customer.full_name}}</em>,</strong></p><p>Your request has been received, and is being reviewed by our support staff.</p><p>Our support staff will reply back to you soon</p>';
            }
        } else if ($emailKey == 'ticket_replied_by_agent_email_to_customer') {
            if ($type == 'web') {
                return '<p>Hi <strong><em>{{customer.full_name}}</em>,</strong></p><p>An agent just replied to your ticket "<strong>{{ticket.title}}</strong>" (<a href="{{ticket.public_url}}">#{{ticket.id}}</a>). To view his reply or add additional comments, click the button below:</p><h4><a href="{{ticket.public_url}}">View Ticket</a></h4><p>or follow this link: {{ticket.public_url}}</p><hr /><p>Regards,<br />{{business.name}}</p>';
            } else {
                return '{{response.full_content}}<p>Regards,<br />{{agent.full_name}}</p>';
            }
        } else if ($emailKey == 'ticket_closed_by_agent_email_to_customer') {
            if ($type == 'web') {
                return '<p>Hi <strong><em>{{customer.full_name}},</strong></p><p>Your ticket - {{ticket.title}}</p><p>We hope that the ticket was resolved to your satisfaction. If you feel that the ticket should not be closed or if the ticket has not been resolved, please reopen the ticket (<a href="{{ticket.public_url}}">#{{ticket.id}}</a>)</p><p>Regards,<br />{{business.name}}</p>';
            } else {
                return '<p>Hi <strong><em>{{customer.full_name}},</strong></p><p>Your ticket - {{ticket.title}}</p><p>We hope that the ticket was resolved to your satisfaction. If you feel that the ticket should not be closed or if the ticket has not been resolved, please feel free to reply back.<p>Regards,<br />{{business.name}}</p>';
            }
        } else if ($emailKey == 'ticket_created_email_to_admin') {
            return '<p>A new ticket (<a href="{{ticket.admin_url}}">{{ticket.title}}</a>) as been submitted by {{customer.full_name}}</p><h4>Ticket Body</h4><p>{{ticket.content}}</p><p><b><a href="{{ticket.admin_url}}">View Ticket</a></b></p>';
        } else if ($emailKey == 'ticket_replied_by_customer_email_to_admin') {
            return '<p>A new response has been added to "<a href="{{ticket.admin_url}}">{{ticket.title}}</a>"  by {{customer.full_name}}</p><h4>Response Body</h4><p>{{response.content}}</p><p><b><a href="{{ticket.admin_url}}">View Ticket</a></b></p>';
        } else if($emailKey == 'ticket_agent_on_change') {
            return '<p>Hi <strong><em>{{agent.full_name}}</em>,</strong></p><p>Ticket "<a href="{{ticket.admin_url}}">#{{ticket.id}}</a>" assigned to you.</p>';
        } else if($emailKey == 'ticket_created_by_agent_email_to_customer') {
            return '<p>Hi <strong><em>{{customer.full_name}}</em>,</strong></p><p>{{agent.full_name}} created a ticket on behalf of you, you can check it <a href="{{ticket.public_url}}">here</a></p>.';
        } else if($emailKey == 'ticket_created_by_agent_on_behalf_email_to_customer') {
            if ($type == 'web') {
                return '<p>Hi <strong><em>{{customer.full_name}}</em>,</strong></p><p>A support agent has created a new ticket on your behalf titled "{{ticket.title}}" (#{{ticket.id}}).</p><p>You can review the details, track progress, or add additional comments by clicking the button below:</p><h4><a href="{{ticket.public_url}}">View Ticket</a></h4><p>Or follow this link: {{ticket.public_url}}</p><p>Best regards,<br />{{agent.full_name}}</p>';
            } else {
                return '{{response.full_content}}<p>Regards,<br />{{agent.full_name}}</p>';
            }
        }

        return '';
    }
}
