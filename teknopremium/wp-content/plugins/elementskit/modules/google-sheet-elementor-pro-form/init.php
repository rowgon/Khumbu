<?php

namespace ElementsKit\Modules\Google_Sheet_Elementor_Pro_Form;

use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Class Init
 *
 * Bootstraps the Google Sheet integration for Elementor Pro forms.
 * Registers admin settings hooks, form control panels, and handles
 * new form submission records.
 *
 * @package ElementsKit\Modules\Google_Sheet_Elementor_Pro_Form
 */
class Init
{
    /**
     * Admin settings pulled from ElementsKit user data option.
     *
     * Contains Google OAuth credentials (client_id, client_secret) and
     * any other user-configured values stored under the 'user_data' key.
     *
     * @var array
     */
    public $admin_settings;

    /**
     * Init constructor.
     *
     * Bails early if Elementor Pro is not active, then registers all
     * WordPress action hooks needed for the Google Sheet integration.
     */
    public function __construct()
    {
        if (!class_exists('\ElementorPro\Core\App\App')) {
            return;
        }

        $this->admin_settings = \ElementsKit_Lite\Libs\Framework\Classes\Utils::instance()
            ->get_option('user_data', []);

        // Exchange the OAuth authorization code for an access token when
        // Google redirects back to the admin page with ?code=...
        add_action('elementskit/admin/settings_sections/before', [$this, 'google_sheet_access_token']);

        // Inject the Google Sheet section into every Elementor Pro form widget.
        add_action('elementor/element/form/section_form_options/after_section_end', [$this, 'form_control']);

        // Process a new form submission and push the data to Google Sheets.
        add_action('elementor_pro/forms/new_record', [$this, 'new_record'], 10, 2);
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * Fired on the ElementsKit admin settings page. If a `code` query
     * parameter is present in the URL (i.e. Google redirected back after
     * the user granted consent), it exchanges that code for an access token.
     *
     * @return void
     */
    public function google_sheet_access_token()
    {
        if (!empty($_GET['code'])) {
            $this->get_access_token();
        }
    }

    /**
     * Register the Google Sheet controls section inside the Elementor Pro form widget.
     *
     * Adds an "Enable" switcher and a "Sheet Name" text field under a
     * dedicated "Google Sheet" panel section. When enabled, form submissions
     * are pushed to a Google Sheet with the provided name.
     *
     * @param \Elementor\Widget_Base $settings The Elementor form widget instance.
     * @return \Elementor\Widget_Base The same widget instance (for chaining).
     */
    public function form_control($settings)
    {
        $settings->start_controls_section(
            'ekit_section_form_fields',
            [
                'label' => esc_html__('Google Sheet', 'elementskit'),
            ]
        );

        $settings->add_control(
            'ekit_google_sheet_enable',
            [
                'label'        => esc_html__('Enable', 'elementskit'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'elementskit'),
                'label_off'    => esc_html__('No', 'elementskit'),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $settings->add_control(
            'ekit_google_sheet_name',
            [
                'label'       => esc_html__('Name', 'elementskit'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Enter your sheet name', 'elementskit'),
                'description' => esc_html__(
                    'Note: A google sheet will be generated with the given name and submissions will be stored there.',
                    'elementskit'
                ),
                'condition'   => ['ekit_google_sheet_enable' => 'yes'],
                'label_block' => true,
            ]
        );

        $settings->add_control(
            'ekit_google_sheet_help_text',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => sprintf(
                    '%1$s <a href="%2$s" target="blank"> %3$s </a>',
                    esc_html__('Google sheet', 'elementskit'),
                    esc_url(admin_url('admin.php?page=elementskit#v-elementskit-usersettings')),
                    esc_html__('Settings', 'elementskit')
                ),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition'       => ['ekit_google_sheet_enable' => 'yes'],
            ]
        );

        $settings->end_controls_section();

        return $settings;
    }

    /**
     * Process a new Elementor Pro form submission.
     *
     * Called by the `elementor_pro/forms/new_record` action hook.
     * Checks whether the Google Sheet integration is enabled for the
     * submitted form and, if Google OAuth credentials are present, passes
     * the formatted submission data to {@see Google_Sheet::insert()}.
     *
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record The submitted form record.
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $form   The form handler instance.
     * @return void
     */
    public function new_record($record, $form)
    {
        $form_settings = $form->get_current_form()['settings'];

        if ($form_settings['ekit_google_sheet_enable'] !== 'yes') {
            return;
        }

        $has_client_id     = !empty($this->admin_settings['google']['client_id']);
        $has_client_secret = !empty($this->admin_settings['google']['client_secret']);

        if (!$has_client_id || !$has_client_secret) {
            return;
        }

        $sheet_name = empty($form_settings['ekit_google_sheet_name'])
            ? $form_settings['form_name']
            : $form_settings['ekit_google_sheet_name'];

        $google_sheet = new Google_Sheet(
            [
                'id'   => $form_settings['id'],
                'name' => $sheet_name,
                'data' => $record->get_formatted_data(),
            ],
            $this->admin_settings
        );

        $google_sheet->insert();
    }

    /**
     * Exchange a Google OAuth authorization code for an access + refresh token.
     *
     * Sends a POST request to Google's token endpoint using the `code` query
     * parameter returned after the user grants consent. On success, stores the
     * token payload in a WordPress option and caches it in a transient for the
     * duration of `expires_in` (minus a 20-second safety buffer).
     * Redirects the browser back to the ElementsKit settings page via JavaScript
     * and calls `exit` to stop further output.
     *
     * @return void
     */
    private function get_access_token()
    {
        $code = $_GET['code'];

        $params = [
            'code'          => $code,
            'client_id'     => $this->admin_settings['google']['client_id']     ?? '',
            'client_secret' => $this->admin_settings['google']['client_secret'] ?? '',
            'redirect_uri'  => admin_url('admin.php?page=elementskit'),
            'grant_type'    => 'authorization_code',
            'access_type'   => 'offline',
        ];

        $response = wp_remote_post('https://accounts.google.com/o/oauth2/token', [
            'method' => 'POST',
            'body'   => $params,
        ]);

        $body   = wp_remote_retrieve_body($response);
        $parsed = json_decode($body, true);

        if (!empty($parsed['access_token'])) {
            // Record when this token was generated so expiry can be calculated later.
            $parsed['generated_at'] = time();

            $ttl = ($parsed['expires_in'] ?? 3599) - 20;

            update_option(Google_Sheet::ACCESS_TOKEN_KEY, json_encode($parsed));
            set_transient(Google_Sheet::ACCESS_TRANSIENT_KEY, json_encode($body), $ttl);
        }
        ?>
        <script>
            // Redirect back to the settings page after the token exchange.
            window.location.href = "<?php echo admin_url('admin.php?page=elementskit#v-elementskit-usersettings'); ?>";
        </script>
        <?php
        exit;
    }

    /**
     * Build the Google OAuth authorization URL.
     *
     * Constructs the URL that sends the admin user to Google's consent screen.
     * Requests offline access so a refresh token is issued alongside the
     * initial access token.
     *
     * @return string The fully-qualified Google OAuth authorization URL.
     */
    public static function get_code_url()
    {
        $user_data = \ElementsKit_Lite\Libs\Framework\Classes\Utils::instance()
            ->get_option('user_data', []);

        $params = [
            'response_type'   => 'code',
            'client_id'       => $user_data['google']['client_id'] ?? '',
            'redirect_uri'    => admin_url('admin.php?page=elementskit'),
            'scope'           => 'https://www.googleapis.com/auth/spreadsheets',
            'approval_prompt' => 'force',
            'access_type'     => 'offline',
        ];

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }
}