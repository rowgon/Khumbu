<?php

namespace FluentSupport\App\Http\Controllers;


use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Models\Meta;
use FluentSupport\App\Models\Product;
use FluentSupport\App\Services\EmailNotification\Settings;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Integrations\AI\AIProviderFactory;
use FluentSupport\Database\Migrations\AIActivityLogsMigrator;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Hooks\Handlers\ReCaptchaHandler;
use FluentSupport\Framework\Support\Arr;

/**
 *  SettingsController class is responsible for all settings
 * This class is responsible for all request related to settings under global settings tab
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class SettingsController extends Controller
{
    /**
     * getSettings method will return the settings by settings key
     * @param Request $request
     * @return array|array[]
     */
    public function getSettings(Request $request)
    {
        $settingsKey = $request->getSafe('settings_key', 'sanitize_text_field');

        return (new Settings)->get($settingsKey);
    }

    /**
     * getIntegrationSettings method will return the settings for integration
     * @param Request $request
     * @return array
     */
    public function getIntegrationSettings(Request $request)
    {
        $settings = Meta::where('object_type', 'integration_settings')->get();
        $integrationSettings = [];
        foreach ($settings as $index => $setting) {
            $data = Helper::safeUnserialize($setting->value);
            if (!empty($data['status']) && $data && $data['status'] == 'yes') {
                $integrationSettings[] = $setting->key;
            }
        }
        return $integrationSettings;
    }

    /**
     * saveSettings method will save the requested settings data by setting key
     * @param Request $request
     * @return array
     */
    public function saveSettings(Request $request)
    {
        $settingsKey = $request->getSafe('settings_key', 'sanitize_text_field');
        $settings = wp_unslash($request->get('settings', null));

        // wp-editor fields: sanitize with wp_kses_post (same approach as Fluent Cart)
        $htmlFields = ['login_message'];
        $htmlValues = [];
        if (is_array($settings)) {
            foreach ($htmlFields as $field) {
                if (isset($settings[$field])) {
                    $htmlValues[$field] = wp_kses_post($settings[$field]);
                }
            }
        }

        $settings = is_array($settings) ? map_deep($settings, 'sanitize_text_field') : [];

        // Restore HTML fields
        foreach ($htmlValues as $field => $value) {
            $settings[$field] = $value;
        }

        (new Settings)->save($settingsKey, $settings);

        return [
            'message' => __('Settings has been updated', 'fluent-support')
        ];
    }

    /**
     * getPages method will return the list of pages created in WP
     * @return array
     */
    public function getPages()
    {
        return [
            'pages' => Helper::getWPPages()
        ];
    }

    /**
     * setupPortal method will setup the support portal
     * @param Request $request
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function setupPortal(Request $request)
    {
        $mailbox = $request->get('mailbox', null);
        $mailbox = is_array($mailbox) ? [
            'name'     => isset($mailbox['name']) ? sanitize_text_field($mailbox['name']) : '',
            'email'    => isset($mailbox['email']) ? sanitize_email($mailbox['email']) : '',
            'box_type' => isset($mailbox['box_type']) ? sanitize_key($mailbox['box_type']) : '',
            'is_default' => isset($mailbox['is_default']) ? sanitize_text_field($mailbox['is_default']) : 'yes',
        ] : [];

        $this->validate($mailbox, [
            'name'     => 'required',
            'email'    => 'required|email',
            'box_type' => 'required'
        ]);

        $settings = $request->get('global_settings', null);
        $settings = is_array($settings) ? [
            'create_portal_page' => isset($settings['create_portal_page']) ? sanitize_text_field($settings['create_portal_page']) : 'no',
            'portal_page_id'     => isset($settings['portal_page_id']) ? intval($settings['portal_page_id']) : 0,
        ] : [];

        $createPage = $settings['create_portal_page'] == 'yes';

        if (!$createPage && empty($settings['portal_page_id'])) {
            Helper::getSafeErrorMessage(new \Exception(__('Please select a page or enable create page', 'fluent-support')));
        }

        if ($createPage) {
            // we have to create the page
            $page_id = wp_insert_post(
                array(
                    'comment_status' => 'close',
                    'ping_status'    => 'close',
                    'post_author'    => get_current_user_id(),
                    'post_title'     => __('Support Portal', 'fluent-support'),
                    'post_status'    => 'publish',
                    'post_content'   => '<!-- wp:shortcode -->[fluent_support_portal]<!-- /wp:shortcode -->',
                    'post_type'      => 'page'
                )
            );
        } else {
            $page_id = intval($settings['portal_page_id']);
        }

        $newMailBox = MailBox::first();
        if (!$newMailBox) {
            $mailbox['is_default'] = 'yes';
            $mailbox['created_by'] = get_current_user_id();
            $mailbox['settings']['admin_email_address'] = $mailbox['email'];
            $newMailBox = MailBox::create($mailbox);
        }

        $settingsClass = new Settings();
        $globalSettings = $settingsClass->globalBusinessSettings();

        $globalSettings['portal_page_id'] = $page_id;

        $settingsClass->save('global_business_settings', $globalSettings);


        if (defined('WC_PLUGIN_FILE')) {
            // URL Flash
            flush_rewrite_rules(false);
        }

        return [
            'mailbox'         => $newMailBox,
            'global_settings' => $globalSettings,
            'mailboxes'       => MailBox::select(['id', 'name', 'settings'])->get(),
            'has_fluentform'  => defined('FLUENTFORM')
        ];

    }

    /**
     * getFluentCRMSettings method will return the settings for Fluent CRM
     * @param Request $request
     * @return array
     */
    public function getFluentCRMSettings(Request $request)
    {
        if (defined('FLUENTCRM')) {
            $settingDefault = [
                'enabled'        => 'no',
                'default_status' => 'subscribed',
                'assigned_list'  => '',
                'assigned_tags'  => []
            ];

            $settings = Helper::getOption('_fluentcrm_intergration_settings');

            $settings = wp_parse_args($settings, $settingDefault);

            $settingsFields = [
                'enabled'        => [
                    'type'           => 'inline-checkbox',
                    'true_label'     => 'yes',
                    'false_label'    => 'no',
                    'checkbox_label' => __('Enable FluentCRM Integration', 'fluent-support')
                ],
                'default_status' => [
                    'type'        => 'input-radio',
                    'label'       => __('Default status for new contacts', 'fluent-support'),
                    'options'     => [
                        [
                            'id'    => 'subscribed',
                            'label' => __('Subscribed', 'fluent-support')
                        ],
                        [
                            'id'    => 'pending',
                            'label' => __('Pending', 'fluent-support')
                        ]
                    ],
                    'dependency'  => [
                        'depends_on' => 'enabled',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ],
                    'inline_help' => __('Select the default status for new contacts. If you select pending and it\'s a new contact then a double optin email will be sent', 'fluent-support')
                ],
                'assigned_list'  => [
                    'type'       => 'input-options',
                    'label'      => __('Add to FluentCRM list (optional)', 'fluent-support'),
                    'options'    => \FluentCrm\App\Models\Lists::select(['id', 'title'])->orderBy('title', 'ASC')->get(),
                    'dependency' => [
                        'depends_on' => 'enabled',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ],
                ],
                'assigned_tags'  => [
                    'type'       => 'input-options',
                    'multiple'   => true,
                    'label'      => __('Add to Tags', 'fluent-support'),
                    'options'    => \FluentCrm\App\Models\Tag::select(['id', 'title'])->orderBy('title', 'ASC')->get(),
                    'dependency' => [
                        'depends_on' => 'enabled',
                        'operator'   => '=',
                        'value'      => 'yes'
                    ]
                ]
            ];

            return [
                'is_installed'    => true,
                'settings'        => $settings,
                'settings_fields' => $settingsFields,
                'fluentcrm_logo'  => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/fluentcrm-logo.svg'
            ];
        }

        return [
            'is_installed'   => false,
            'fluentcrm_logo' => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/fluentcrm-logo.svg'
        ];

    }

    public function setupInstallation(Request $request)
    {
        $installFluentForm = $request->getSafe('install_fluentform', 'sanitize_text_field', 'no');

        if ($installFluentForm == 'yes' && !defined('FLUENTFORM')) {
            $this->installFluentForm();
        }

        $optinEmail = $request->getSafe('optin_email', 'sanitize_email', '');
        if ($optinEmail && is_email($optinEmail)) {
            $this->shareEmail($optinEmail);
        }

        $shareEssential = $request->getSafe('share_essentials', 'sanitize_text_field', 'no');
        if ($shareEssential == 'yes') {
            Helper::updateOption('_share_essential', $shareEssential);
        }

        return $this->sendSuccess([
            'message' => __('Installation has been completed', 'fluent-support')
        ]);

    }

    public function saveReCaptchaSettings(Request $request)
    {
        $data = $request->get('reCaptcha');

        if (is_string($data) && 'clear-reCaptcha-settings' === sanitize_text_field($data)) {
            if (Meta::where('object_type', '_fs_recaptcha_settings')->delete()) {
                return $this->sendSuccess([
                    'message' => __('Your reCAPTCHA settings deleted successfully.', 'fluent-support'),
                ]);
            }

            return $this->sendError([
                'message' => __('Unable to delete reCAPTCHA settings, try again', 'fluent-support'),
            ]);
        }

        if (!is_array($data)) {
            return $this->sendError([
                'message' => __('Invalid reCAPTCHA data.', 'fluent-support'),
            ]);
        }

        $reCaptchaData = [
            'reCaptcha_version'       => sanitize_text_field($data['reCaptchaVersion'] ?? ''),
            'siteKey'                 => sanitize_text_field($data['siteKey'] ?? ''),
            'secretKey'               => sanitize_text_field($data['secretKey'] ?? ''),
            'formContainingReCaptcha' => array_map('sanitize_text_field', (array) ($data['formContainingReCaptcha'] ?? [])),
            'is_enabled'              => sanitize_text_field($data['reCaptchaEnabled'] ?? 'no'),
        ];

        $previousValue = Meta::where('object_type', '_fs_recaptcha_settings')->first();

        if ($previousValue === $reCaptchaData) {
            return $this->sendError([
                'message' => __('Your recaptcha details are already saved.', 'fluent-support'),
            ]);
        }

        $captchaResponse = sanitize_text_field($data['captchaResponse'] ?? '');

        if ($captchaResponse) {
            $verifyReCaptcha = ReCaptchaHandler::validateRecaptcha($captchaResponse, $reCaptchaData['secretKey'], $reCaptchaData['reCaptcha_version']);

            if (!$verifyReCaptcha) {
                return $this->sendError([
                    'message' => __('Your reCAPTCHA settings are not valid.', 'fluent-support'),
                ]);
            }
        } elseif (!$previousValue) {
            return $this->sendError([
                'message' => __('Please verify reCAPTCHA before saving.', 'fluent-support'),
            ]);
        }

        if ($previousValue) {
            Meta::where('object_type', '_fs_recaptcha_settings')->update([
                'value' => maybe_serialize($reCaptchaData)
            ]);
            return $this->sendSuccess([
                'message' => __('Your reCAPTCHA settings updated successfully.', 'fluent-support'),
            ]);
        } else {
            Meta::insert([
                'object_type' => '_fs_recaptcha_settings',
                'key'         => '_fs_recaptcha_data',
                'value'       => maybe_serialize($reCaptchaData)
            ]);
        }

        return $this->sendSuccess([
            'message' => __('Your reCAPTCHA settings added successfully.', 'fluent-support'),
        ]);
    }

    public function saveAIProviderSettings(Request $request)
    {
        $allowedProviders = AIProviderFactory::getAllowedProviders();
        $provider         = $request->getSafe('provider', 'sanitize_text_field', 'openai');

        if (!in_array($provider, $allowedProviders, true)) {
            return $this->sendError([
                'message' => __('Invalid AI provider selected.', 'fluent-support'),
            ]);
        }

        $enabled = $request->getSafe('enabled', 'sanitize_text_field', 'yes');
        $apiKey  = $request->getSafe('api_key', 'sanitize_text_field', '');
        $model   = $request->getSafe('model', 'sanitize_text_field', '');

        if (empty($apiKey) || strpos($apiKey, '****') === 0) {
            $existing = Helper::getAIProviderSettings();
            if (($existing['provider'] ?? '') === $provider) {
                $apiKey = $existing['api_key'] ?? '';
            } else {
                $apiKey = '';
            }
        }

        $data = [
            'enabled'  => $enabled,
            'provider' => $provider,
            'api_key'  => $apiKey,
            'model'    => $model,
        ];

        try {
            Helper::saveAIProviderSettings($data);
            AIProviderFactory::clearCache();
            AIActivityLogsMigrator::migrate();

            return $this->sendSuccess([
                'message' => __('AI settings have been successfully saved.', 'fluent-support'),
            ]);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => sprintf(
                    __('An error occurred while saving the settings: %s', 'fluent-support'),
                    Helper::getSafeErrorMessage($e)
                ),
            ]);
        }
    }

    public function disconnectAIProvider()
    {
        Meta::where('object_type', '_fs_ai_provider_settings')->delete();
        Meta::where([
            'object_type' => '_fs_openai_settings',
            'key'         => '_fs_openai_data',
        ])->delete();

        AIProviderFactory::clearCache();

        return $this->sendSuccess([
            'message' => __('AI provider settings have been successfully disconnected.', 'fluent-support'),
        ]);
    }

    public function getAIProviderSettings()
    {
        $settings = Helper::getAIProviderSettings();

        $provider     = $settings['provider'] ?? 'openai';
        $apiKey       = $settings['api_key'] ?? '';
        $model        = $settings['model'] ?? '';
        $defaultEnabled = !empty($apiKey) ? 'yes' : 'no';
        $enabled        = $settings['enabled'] ?? $defaultEnabled;

        $availableModels = [];
        foreach (['openai', 'gemini', 'anthropic'] as $p) {
            $instance = AIProviderFactory::make($p, '', '');
            if (!is_wp_error($instance)) {
                $availableModels[$p] = $instance->getAvailableModels();
            }
        }

        if (empty($model) && !empty($availableModels[$provider])) {
            $model = $availableModels[$provider][0]['value'];
        }

        return $this->sendSuccess([
            'enabled'          => $enabled,
            'provider'         => $provider,
            'api_key'          => !empty($apiKey) ? '****' . substr($apiKey, -4) : '',
            'model'            => $model,
            'available_models' => $availableModels,
        ]);
    }

    public function getReCaptchaSettings()
    {
        $reCaptchaSettingsData = Meta::where('object_type', '_fs_recaptcha_settings')->first();
        if ($reCaptchaSettingsData) {
            $settings = Helper::safeUnserialize($reCaptchaSettingsData->value);
            return $this->sendSuccess($settings);
        }

        return [];
    }

    private function shareEmail($optinEmail)
    {
        $user = get_user_by('ID', get_current_user_id());
        $data = [
            'answers'    => [
                'website'        => site_url(),
                'email'          => $optinEmail,
                'first_name'     => $user->first_name,
                'last_name'      => $user->last_name,
                'name'           => $user->display_name,
                'has_fluentform' => defined('FLUENTFORM') ? 'yes' : 'no'
            ],
            'questions'  => [
                'website'        => 'website',
                'first_name'     => 'first_name',
                'last_name'      => 'last_name',
                'email'          => 'email',
                'name'           => 'name',
                'has_fluentform' => 'has_fluentform'
            ],
            'user'       => [
                'email' => $optinEmail
            ],
            'fb_capture' => 1,
            'form_id'    => 77
        ];

        $url = add_query_arg($data, 'https://wpmanageninja.com/');

        wp_remote_post($url);
    }

    /**
     * installFluentCRM method will install Fluent CRM plugin
     * @return array
     */
    public function installFluentCRM()
    {

        if (defined('FLUENTCRM')) {
            return [
                'is_installed' => true,
                'message'      => __('FluentCRM plugin has been installed and activated successfully', 'fluent-support')
            ];
        }

        if (!current_user_can('install_plugins')) {
            return $this->sendError([
                'message' => __('Sorry! you do not have permission to install plugin', 'fluent-support')
            ]);
        }

        $plugin_id = 'fluent-crm';
        $plugin = [
            'name'      => 'Fluent CRM',
            'repo-slug' => 'fluent-crm',
            'file'      => 'fluent-crm.php',
        ];

        $this->backgroundInstaller($plugin, $plugin_id);

        if (defined('FLUENTCRM')) {
            return [
                'is_installed' => true,
                'message'      => __('FluentCRM plugin has been installed and activated successfully', 'fluent-support')
            ];
        } else {
            return $this->sendError([
                'message' => __('Sorry! FluentCRM could not be installed. Please install manually', 'fluent-support')
            ]);
        }
    }

    public function installFluentForm()
    {

        if (defined('FLUENTFORM')) {
            return [
                'is_installed' => true,
                'message'      => __('Fluent Forms plugin has been installed and activated successfully', 'fluent-support')
            ];
        }

        if (!current_user_can('install_plugins')) {
            return $this->sendError([
                'message' => __('Sorry! you do not have permission to install plugin', 'fluent-support')
            ]);
        }

        $plugin_id = 'fluentform';
        $plugin = [
            'name'      => 'Fluent Forms',
            'repo-slug' => 'fluentform',
            'file'      => 'fluentform.php',
        ];

        $this->backgroundInstaller($plugin, $plugin_id);

        if (defined('FLUENTFORM')) {
            return [
                'is_installed' => true,
                'message'      => __('Fluent Forms plugin has been installed and activated successfully', 'fluent-support')
            ];
        } else {
            return [
                'is_installed' => false,
                'message'      => __('Fluent Forms could not be installed', 'fluent-support')
            ];
        }
    }

    private function backgroundInstaller($plugin_to_install, $plugin_id)
    {
        if (!empty($plugin_to_install['repo-slug'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            WP_Filesystem();

            $skin = new \Automatic_Upgrader_Skin();
            $upgrader = new \WP_Upgrader($skin);
            $installed_plugins = array_reduce(array_keys(\get_plugins()), array($this, 'associate_plugin_file'), array());
            $plugin_slug = $plugin_to_install['repo-slug'];
            $plugin_file = isset($plugin_to_install['file']) ? $plugin_to_install['file'] : $plugin_slug . '.php';
            $installed = false;
            $activate = false;

            // See if the plugin is installed already.
            if (isset($installed_plugins[$plugin_file])) {
                $installed = true;
                $activate = !is_plugin_active($installed_plugins[$plugin_file]);
            }

            // Install this thing!
            if (!$installed) {
                // Suppress feedback.
                ob_start();

                try {
                    $plugin_information = plugins_api(
                        'plugin_information',
                        array(
                            'slug'   => $plugin_slug,
                            'fields' => array(
                                'short_description' => false,
                                'sections'          => false,
                                'requires'          => false,
                                'rating'            => false,
                                'ratings'           => false,
                                'downloaded'        => false,
                                'last_updated'      => false,
                                'added'             => false,
                                'tags'              => false,
                                'homepage'          => false,
                                'donate_link'       => false,
                                'author_profile'    => false,
                                'author'            => false,
                            ),
                        )
                    );

                    if (is_wp_error($plugin_information)) {
                        throw new \Exception($plugin_information->get_error_message());
                    }

                    $package = $plugin_information->download_link;
                    $download = $upgrader->download_package($package);

                    if (is_wp_error($download)) {
                        throw new \Exception($download->get_error_message());
                    }

                    $working_dir = $upgrader->unpack_package($download, true);

                    if (is_wp_error($working_dir)) {
                        throw new \Exception($working_dir->get_error_message());
                    }

                    $result = $upgrader->install_package(
                        array(
                            'source'                      => $working_dir,
                            'destination'                 => WP_PLUGIN_DIR,
                            'clear_destination'           => false,
                            'abort_if_destination_exists' => false,
                            'clear_working'               => true,
                            'hook_extra'                  => array(
                                'type'   => 'plugin',
                                'action' => 'install',
                            ),
                        )
                    );

                    if (is_wp_error($result)) {
                        throw new \Exception($result->get_error_message());
                    }

                    $activate = true;

                } catch (\Exception $e) {
                }

                // Discard feedback.
                ob_end_clean();
            }

            wp_clean_plugins_cache();

            // Activate this thing.
            if ($activate) {
                try {
                    $result = activate_plugin($installed ? $installed_plugins[$plugin_file] : $plugin_slug . '/' . $plugin_file);

                    if (is_wp_error($result)) {
                        throw new \Exception($result->get_error_message());
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function associate_plugin_file($plugins, $key)
    {
        $path = explode('/', $key);
        $filename = end($path);
        $plugins[$filename] = $key;
        return $plugins;
    }

    public function getRemoteUploadSettings(Request $request)
    {
        $dropBoxConfigured = false;
        $googleDriveConfigured = false;
        $cloudflareR2Configured = false;
        $amazonS3Configured = false;

        if (defined('FLUENTSUPPORTPRO')) {
            $dropBoxSettings = Helper::getIntegrationOption('dropbox_settings');
            $dropBoxConfigured = $dropBoxSettings && !empty($dropBoxSettings['access_token']);

            $googleDriveSettings = Helper::getIntegrationOption('google_drive_settings');
            $googleDriveConfigured = $googleDriveSettings && !empty($googleDriveSettings['access_token']);

            $cloudflareR2Settings = Helper::getIntegrationOption('cloudflare_r2_settings');
            $cloudflareR2Configured = $cloudflareR2Settings && !empty($cloudflareR2Settings['secret_access_key']) && Arr::get($cloudflareR2Settings, 'status') == 'yes';

            $amazonS3Settings = Helper::getIntegrationOption('amazon_s3_settings');
            $amazonS3Configured = $amazonS3Settings && !empty($amazonS3Settings['secret_access_key']) && Arr::get($amazonS3Settings, 'status') == 'yes';
        }

        $drivers = apply_filters('fluent_support/storage_drivers_info', [
            'local'        => [
                'title'         => 'Default WordPress Storage',
                'is_disabled'   => false,
                'is_configured' => true,
                'icon'          => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/folder.svg',
                'description'   => __('Upload and store the files to your WordPress File System Storage.', 'fluent-support')
            ],
            'dropbox'      => [
                'meta_key'      => 'dropbox_settings',
                'title'         => 'Dropbox',
                'has_config'    => true,
                'is_configured' => $dropBoxConfigured,
                'require_pro'   => !defined('FLUENTSUPPORTPRO'),
                'upgrade_url'   => Helper::getUpgradeUrl('feature_lock_dropbox'),
                'icon'          => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/dbox.svg',
                'description'   => __('Upload and store the files to your Dropbox Storage.', 'fluent-support')
            ],
            'google_drive' => [
                'meta_key'      => 'google_drive_settings',
                'title'         => 'Google Drive',
                'has_config'    => true,
                'is_configured' => $googleDriveConfigured,
                'require_pro'   => !defined('FLUENTSUPPORTPRO'),
                'upgrade_url'   => Helper::getUpgradeUrl('feature_lock_google_drive'),
                'description'   => __('Upload and store the files to your Google Drive Storage.', 'fluent-support'),
                'icon'          => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/drive.svg',
            ],
            'cloudflare_r2' => [
                'meta_key'      => 'cloudflare_r2_settings',
                'title'         => 'Cloudflare R2',
                'has_config'    => true,
                'is_configured' => $cloudflareR2Configured,
                'require_pro'   => !defined('FLUENTSUPPORTPRO'),
                'upgrade_url'   => Helper::getUpgradeUrl('feature_lock_cloudflare_r2'),
                'description'   => __('Upload and store the files to Cloudflare R2 Storage with zero egress fees.', 'fluent-support'),
                'icon'          => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/cloudflare-r2.svg',
            ],
            'amazon_s3' => [
                'meta_key'      => 'amazon_s3_settings',
                'title'         => 'Amazon S3',
                'has_config'    => true,
                'is_configured' => $amazonS3Configured,
                'require_pro'   => !defined('FLUENTSUPPORTPRO'),
                'upgrade_url'   => Helper::getUpgradeUrl('feature_lock_amazon_s3'),
                'description'   => __('Upload and store the files to Amazon S3 cloud storage.', 'fluent-support'),
                'icon'          => FLUENT_SUPPORT_PLUGIN_URL . 'assets/images/icons/amazon-s3.svg',
            ]
        ]);

        return [
            'drivers'        => $drivers,
            'enabled_driver' => Helper::getUploadDriverKey()
        ];
    }

    public function updateRemoteUploadDriver(Request $request)
    {
        $driver = $request->getSafe('driver', 'sanitize_text_field');
        Helper::updateOption('file_upload_driver', $driver);

        return [
            'message' => 'Upload driver has been updated successfully',
            'driver'  => $driver
        ];
    }

    /**
     * getIntegrationLogs method will return the integration logs
     * @return array
     */
    public function integrationStatuses()
    {
        return [
            'connections'  => Helper::getIntegrationStatuses()
        ];
    }

    public function getSettingsMenu()
    {
        return Helper::getGlobalSettingsMenu();
    }

    public function getFluentBotSettings()
    {
        $meta = Meta::where([
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_config'
        ])->orderByDesc('id')->first();

        $settings = $meta ? Helper::safeUnserialize($meta->value) : [];

        if (!is_array($settings)) {
            $settings = [];
        }

        // Write-only secret: never return the raw team API key to the browser
        // (it authenticates the FluentBot API and would leak to logs/extensions/
        // XSS). Expose only whether a key is stored so the form can show a
        // "saved" hint; the input stays blank and only overwrites on a new key.
        $hasApiKey = !empty($settings['generalApiKey']);
        unset($settings['generalApiKey']);

        // Per-mapping apiKey is unused (the key is team-wide).
        if (!empty($settings['productMappings']) && is_array($settings['productMappings'])) {
            $settings['productMappings'] = array_map(function ($mapping) {
                if (!is_array($mapping)) {
                    return $mapping;
                }
                unset($mapping['apiKey']);
                return $mapping;
            }, $settings['productMappings']);
        }

        $productItems = Product::all()->map(function ($product) {
            return [
                'id'    => $product->id,
                'title' => $product->title
            ];
        })->values()->all();

        // Default generalBotEnabled to true for backward compatibility with configs saved
        // before this flag existed — existing installs expect general bot to work on GET.
        $defaults = [
            'generalBotId'      => '',
            'generalApiKey'     => '',
            'generalBotEnabled' => true,
            'isEnabled'         => false,
            'productMappings'   => [],
            'products'          => $productItems,
        ];

        return array_merge($defaults, $settings, [
            'products'  => $productItems,
            'hasApiKey' => $hasApiKey,
        ]);
    }

    public function saveFluentBotSettings(Request $request)
    {
        $where = [
            'object_type' => 'fluent_bot_settings',
            'object_id'   => 1,
            'key'         => '_fs_fluent_bot_config'
        ];

        $existing = Meta::where($where)->orderByDesc('id')->first();
        $existingConfig = $existing ? Helper::safeUnserialize($existing->value) : [];
        if (!is_array($existingConfig)) {
            $existingConfig = [];
        }

        // Write-only key: a blank submission means "keep the stored key" (the form
        // never round-trips the secret), so only overwrite when a new key is sent.
        $submittedApiKey = trim($request->getSafe('generalApiKey', 'sanitize_text_field'));
        $apiKey = $submittedApiKey !== '' ? $submittedApiKey : ($existingConfig['generalApiKey'] ?? '');

        $data = [
            'generalBotId'      => $request->getSafe('generalBotId', 'sanitize_text_field'),
            'generalApiKey'     => $apiKey,
            'generalBotEnabled' => filter_var($request->get('generalBotEnabled', true), FILTER_VALIDATE_BOOLEAN),
            'isEnabled'         => $request->getSafe('isEnabled', 'rest_sanitize_boolean'),
            'productMappings'  => []
        ];

        $productMappings = (array) $request->get('productMappings', []);
        $seenProductIds = [];

        foreach ($productMappings as $mapping) {
            if (!is_array($mapping)) {
                continue;
            }

            $productId = intval($mapping['productId'] ?? 0);
            $botId = trim(sanitize_text_field($mapping['botId'] ?? ''));

            // Drop invalid rows: empty botId would override the general bot with nothing
            // at resolution time (resolveApiCredentials), producing runtime failures.
            if ($productId < 1 || $botId === '') {
                continue;
            }

            // Dedupe by productId — first valid mapping wins.
            if (isset($seenProductIds[$productId])) {
                continue;
            }
            $seenProductIds[$productId] = true;

            $data['productMappings'][] = [
                'productId'    => $productId,
                'productTitle' => sanitize_text_field($mapping['productTitle'] ?? ''),
                'botId'        => $botId,
            ];
        }

        $serialized = maybe_serialize($data);

        if ($existing) {
            // Update the latest row; do not prune siblings — concurrent first-writes could
            // race and delete each other's inserts, leaving zero rows (data loss).
            // Reads use orderByDesc('id')->first() so duplicates are harmless at read time.
            $existing->update(['value' => $serialized]);
        } else {
            Meta::create(array_merge($where, ['value' => $serialized]));
            AIActivityLogsMigrator::migrate();
        }

        // Write-only: never echo the raw key back to the browser.
        $responseData = $data;
        unset($responseData['generalApiKey']);
        $responseData['hasApiKey'] = $apiKey !== '';

        return [
            'success' => true,
            'message' => 'Settings saved successfully',
            'data'    => $responseData
        ];
    }

    public function getFluentBotPresets()
    {
        $service = new \FluentSupport\App\Services\Integrations\FluentBot\FluentBotService();
        $custom = $service->getCustomPresets();

        if (!empty($custom)) {
            return ['presets' => $custom];
        }

        // Return defaults without persisting — saving happens only when the user explicitly posts.
        $helper = new \FluentSupport\App\Services\Integrations\FluentBot\FluentBotHelper();
        return ['presets' => $helper->getPresetPrompts('createResponse')];
    }

    public function saveFluentBotPresets(Request $request)
    {
        $presets = (array) $request->get('presets', []);

        $service = new \FluentSupport\App\Services\Integrations\FluentBot\FluentBotService();
        $saved = $service->saveCustomPresets($presets);

        return [
            'success' => true,
            'message' => __('Prompt options saved successfully', 'fluent-support'),
            'presets' => $saved
        ];
    }

}
