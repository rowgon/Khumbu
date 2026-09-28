<?php

namespace ElementsKit\Modules\Google_Sheet_Elementor_Pro_Form;

defined('ABSPATH') || exit;

/**
 * Class Google_Sheet
 *
 * Handles all communication with the Google Sheets REST API v4.
 * Responsible for creating spreadsheets, writing column headers,
 * appending form submission rows, and managing OAuth token lifecycle
 * (refresh + transient caching) — all via raw WordPress HTTP functions
 * with no Composer dependencies.
 *
 * @package ElementsKit\Modules\Google_Sheet_Elementor_Pro_Form
 */
class Google_Sheet
{
    /**
     * WordPress option key used to persist the full OAuth token payload
     * (access_token, refresh_token, expires_in, generated_at, …).
     * @since 4.4.0
     * @var string
     */
    const ACCESS_TOKEN_KEY = 'ekits_google_sheet_access_token';

    /**
     * WordPress transient key used to cache the active access token.
     * The transient TTL mirrors `expires_in - 20` seconds so it expires
     * slightly before the token itself, prompting a proactive refresh.
     * @since 4.4.0
     * @var string
     */
    const ACCESS_TRANSIENT_KEY = 'ekit_google_sheet_transient';

    /**
     * Decoded OAuth token array, loaded from the WordPress option on construction.
     * Keys include: access_token, refresh_token, expires_in, generated_at.
     * @since 4.4.0
     * @var array
     */
    private $access_token;

    /**
     * ElementsKit admin settings array containing Google OAuth credentials.
     * Expected keys: ['google']['client_id'], ['google']['client_secret'].
     * @since 4.4.0
     * @var array
     */
    private $admin_settings;

    /**
     * Form metadata and submission payload.
     *
     * Expected keys:
     *   - 'id'   (string|int) — Elementor form widget ID, used as part of the option key.
     *   - 'name' (string)     — Desired spreadsheet title.
     *   - 'data' (array)      — Associative array of field_label => field_value pairs.
     * @since 4.4.0
     * @var array
     */
    private $form;

    /**
     * Google Sheets spreadsheet ID associated with the current form.
     * Populated either from the WordPress option or after a new sheet is created.
     * @since 4.4.0
     * @var string|null
     */
    private $spreadsheet_id;

    /**
     * Google_Sheet constructor.
     *
     * Stores the form data and admin settings, then loads the persisted
     * OAuth token from the WordPress options table.
     * @since 4.4.0
     * @param array $form           Form metadata and submission data (id, name, data).
     * @param array $admin_settings ElementsKit admin settings containing Google credentials.
     */
    public function __construct($form, $admin_settings)
    {
        $this->form           = $form;
        $this->admin_settings = $admin_settings;
        $this->access_token   = json_decode(get_option(self::ACCESS_TOKEN_KEY), true);
    }

    /**
     * Insert a form submission row into the appropriate Google Sheet.
     *
     * Verifies that a valid access token exists, ensures the target
     * spreadsheet exists (creating it if necessary), then appends the
     * submission data as a new row.
     * @since 4.4.0
     * @return bool|mixed False if no access token is available or sheet
     *                    creation fails; otherwise the result of {@see send_form_data()}.
     */
    public function insert()
    {
        if (empty($this->access_token['access_token'])) {
            return false;
        }

        if ($this->has_spreadsheet()) {
            return $this->send_form_data();
        }
    }

    /**
     * Ensure a spreadsheet exists for the current form.
     *
     * Checks the WordPress options table for a previously stored spreadsheet ID.
     * If none is found, or if the stored ID no longer exists on Google Drive,
     * a new spreadsheet is created and its ID is persisted.
     * @since 4.4.0
     * @return bool True when a valid spreadsheet is available; false on creation failure.
     */
    public function has_spreadsheet()
    {
        $key                  = 'ekit_google_sheet_' . $this->form['id'];
        $this->spreadsheet_id = get_option($key);

        if (!$this->spreadsheet_id) {
            // No sheet on record — create a fresh one.
            if (!$this->create_sheet()) return false;
            update_option($key, $this->spreadsheet_id);
        } else {
            // Sheet ID exists locally but may have been deleted from Google Drive.
            if (!$this->is_sheet_exist()) {
                if (!$this->create_sheet()) return false;
                update_option($key, $this->spreadsheet_id);
            }
        }

        return true;
    }

    /**
     * Check whether the stored spreadsheet still exists on Google Drive.
     *
     * Sends a GET request to the Sheets API. A 200 response means the sheet
     * exists and is accessible; any other status (404, 403, …) returns false.
     * @since 4.4.0
     * @return bool True if the spreadsheet is accessible; false otherwise.
     */
    public function is_sheet_exist()
    {
        $url      = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}";
        $response = wp_remote_get($url, [
            'headers' => $this->auth_headers(),
        ]);

        return 200 === wp_remote_retrieve_response_code($response);
    }

    /**
     * Create a new Google Sheet with the form's configured name.
     *
     * Sends a POST request to the Sheets API to create a blank spreadsheet,
     * then immediately writes the form field names as column headers in row 1.
     * Populates {@see $spreadsheet_id} on success.
     * @since 4.4.0
     * @return bool True on successful creation; false if the API request fails.
     */
    public function create_sheet()
    {
        $this->maybe_refresh_token();

        $response = wp_remote_post('https://sheets.googleapis.com/v4/spreadsheets', [
            'headers' => $this->auth_headers(),
            'body'    => wp_json_encode([
                'properties' => ['title' => $this->form['name']],
            ]),
        ]);

        if (200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->spreadsheet_id = $body['spreadsheetId'] ?? null;

        if (!$this->spreadsheet_id) return false;

        $this->insert_sheet_column_names();

        return true;
    }

    /**
     * Write the form field labels as column headers in the first row (A1:Z1).
     *
     * Called immediately after a new spreadsheet is created. Uses the array
     * keys from the submission data as column names, so the headers always
     * match the order of the values appended by {@see send_form_data()}.
     * @since 4.4.0
     * @return void
     */
    public function insert_sheet_column_names()
    {
        $this->values_update('A1:Z1', [array_keys($this->form['data'])]);
    }

    /**
     * Append the form submission as a new row in the spreadsheet.
     *
     * Uses the Sheets API `values.append` endpoint with `valueInputOption=USER_ENTERED`
     * so that numeric strings, dates, and formulae are parsed naturally by Sheets.
     * Retries once if the API returns a 401 Unauthorized (expired token).
     * @since 4.4.0
     * @return bool True on success; false if the request fails after a token refresh.
     */
    public function send_form_data()
    {
        $this->maybe_refresh_token();

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}"
             . "/values/A1:Z1:append?valueInputOption=USER_ENTERED";

        $response = wp_remote_post($url, [
            'headers' => $this->auth_headers(),
            'body'    => wp_json_encode(['values' => [array_values($this->form['data'])]]),
        ]);

        // Retry once on 401 (token may have expired between the transient check and this call).
        if (401 === wp_remote_retrieve_response_code($response) && $this->refresh_access_token()) {
            return $this->send_form_data();
        }

        return 200 === wp_remote_retrieve_response_code($response);
    }

    /**
     * Overwrite values in a specific sheet range using the `values.update` (PUT) endpoint.
     *
     * Used internally to write column headers on sheet creation. Retries once
     * automatically if a 401 is returned.
     * @since 4.4.0
     * @param string  $range  A1 notation range, e.g. 'A1:Z1'.
     * @param array[] $values A 2-D array of row arrays to write into the range.
     * @return void
     */
    private function values_update($range, array $values)
    {
        $this->maybe_refresh_token();

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheet_id}"
             . "/values/" . rawurlencode($range) . "?valueInputOption=USER_ENTERED";

        $response = wp_remote_request($url, [
            'method'  => 'PUT',
            'headers' => $this->auth_headers(),
            'body'    => wp_json_encode(['values' => $values]),
        ]);

        // Retry once on 401.
        if (401 === wp_remote_retrieve_response_code($response) && $this->refresh_access_token()) {
            $this->values_update($range, $values);
        }
    }


    /**
     * Proactively refresh the access token if the transient has expired.
     *
     * The transient TTL is set to `expires_in - 20` seconds, so it disappears
     * slightly before the token itself becomes invalid. Calling this before any
     * API request avoids mid-request 401 errors in the common case.
     * @since 4.4.0
     * @return void
     */
    private function maybe_refresh_token()
    {
        if (!get_transient(self::ACCESS_TRANSIENT_KEY)) {
            $this->refresh_access_token();
        }
    }

    /**
     * Exchange the stored refresh token for a new access token.
     *
     * Sends a POST request to Google's token endpoint. On success:
     *   - Preserves the existing refresh token (Google only issues it once).
     *   - Updates both the WordPress option and the transient.
     *   - Refreshes the in-memory {@see $access_token} so subsequent calls
     *     in the same request use the new token immediately.
     * @since 4.4.0
     * @return bool True if the token was refreshed successfully; false otherwise.
     */
    public function refresh_access_token()
    {
        $response = wp_remote_post('https://accounts.google.com/o/oauth2/token', [
            'body' => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->access_token['refresh_token'] ?? '',
                'client_id'     => $this->admin_settings['google']['client_id']     ?? '',
                'client_secret' => $this->admin_settings['google']['client_secret'] ?? '',
            ],
        ]);

        if (200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Google does not re-issue the refresh token on every refresh; carry it forward.
        $body['refresh_token'] = $this->access_token['refresh_token'] ?? '';

        $ttl = ($body['expires_in'] ?? 3599) - 20;
        set_transient(self::ACCESS_TRANSIENT_KEY, json_encode($body), $ttl);
        update_option(self::ACCESS_TOKEN_KEY, json_encode($body));

        // Update in-memory token so the current request uses the new one immediately.
        $this->access_token = $body;

        return true;
    }

    /**
     * Build the HTTP headers required for an authenticated Sheets API request.
     *
     * Returns an associative array suitable for the `headers` key of any
     * `wp_remote_*` call. The Bearer token is taken from the current
     * in-memory access token.
     * @since 4.4.0
     * @return array{Authorization: string, Content-Type: string}
     */
    private function auth_headers()
    {
        return [
            'Authorization' => 'Bearer ' . ($this->access_token['access_token'] ?? ''),
            'Content-Type'  => 'application/json',
        ];
    }
}