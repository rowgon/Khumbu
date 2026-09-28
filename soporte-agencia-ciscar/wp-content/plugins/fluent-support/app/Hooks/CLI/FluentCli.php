<?php

namespace FluentSupport\App\Hooks\CLI;

use FluentSupport\App\Modules\StatModule;
use FluentSupport\App\Services\Tickets\Importer\MigratorService;
use FluentSupport\App\Models\Meta;

class FluentCli
{
    private $domain;
    private $accessToken;

    public function stats($args, $assoc_args)
    {
        $overallStats = StatModule::getOverAllStats();
        $format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'table');

        \WP_CLI\Utils\format_items(
            $format,
            $overallStats,
            ['title', 'count']
        );
    }

    public function activate_license($args, $assoc_args)
    {
        if (empty($assoc_args['key'])) {
            \WP_CLI::line('use --key=LICENSE_KEY to activate the license');
            return;
        }

        $licenseKey = trim(sanitize_text_field($assoc_args['key'] ?? ''));

        if (!class_exists('\FluentSupportPro\App\Services\PluginManager\LicenseManager')) {
            \WP_CLI::line('FluentSupport Pro is required');
            return;
        }

        \WP_CLI::line('Validating License, Please wait');

        $licenseManager = new \FluentSupportPro\App\Services\PluginManager\LicenseManager();
        $response = $licenseManager->activateLicense($licenseKey);

        if (is_wp_error($response)) {
            \WP_CLI::error($response->get_error_message());
            return;
        }

        \WP_CLI::line('Your license key has been successfully updated');
        \WP_CLI::line('Your License Status: ' . $response['status']);
        \WP_CLI::line('Expire Date: ' . $response['expires']);
        return;
    }

    public function license_status()
    {

        if (!class_exists('\FluentSupportPro\App\Services\PluginManager\LicenseManager')) {
            \WP_CLI::line('FluentSupport Pro is required');
            return;
        }

        \WP_CLI::line('Fetching License details, Please wait');

        $licenseManager = new \FluentSupportPro\App\Services\PluginManager\LicenseManager();
        $licenseManager->verifyRemoteLicense(true);
        $response = $licenseManager->getLicenseDetails();

        \WP_CLI::line('Your License Status: ' . $response['status']);
        \WP_CLI::line('Expires: ' . $response['expires']);
        return;
    }

    public function freshdesk_ticket_import($args, $assoc_args)
    {
        try {
            if (!defined('FLUENTSUPPORTPRO_PLUGIN_VERSION')) {
                \WP_CLI::error('Fluent Support Pro is required for this command.');
                return;
            }

            if (!$this->validateAndExtractParams($assoc_args)) {
                return;
            }

            $startPage = $this->checkPreviousMigration();

            $this->processMigration($startPage);

        } finally {
            $this->domain = null;
            $this->accessToken = null;
        }
    }

    /**
     * Validates and extracts parameters from CLI arguments.
     *
     * @param array $assoc_args Associative array of options.
     * @return bool True if validation succeeded, false otherwise
     */
    private function validateAndExtractParams($assoc_args)
    {
        $this->domain = \WP_CLI\Utils\get_flag_value($assoc_args, 'domain');
        if (empty($this->domain)) {
            $this->domain = \cli\prompt('Enter your support domain (e.g. https://yourcompany.freshdesk.com)');
        }

        $this->accessToken = \WP_CLI\Utils\get_flag_value($assoc_args, 'access_token');
        if (empty($this->accessToken)) {
            $this->accessToken = \cli\prompt('Enter your access token');
        }

        if (empty($this->accessToken) || empty($this->domain)) {
            \WP_CLI::error('Both domain and access token are required to proceed.');
            return false;
        }

        return true;
    }

    /**
     * Check for previous migration data and determine the starting page.
     *
     * @param string      $metaKey    The object_type key in fs_meta. Defaults to Freshdesk.
     * @param string|null $identifier The domain or other unique identifier. Defaults to $this->domain.
     * @return int Page number to start from
     */
    private function checkPreviousMigration($metaKey = '_fs_freshdesk_migration_info', $identifier = null)
    {
        if ($identifier === null) {
            $identifier = $this->domain;
        }

        $metadata = Meta::where('object_type', $metaKey)->first();

        if (empty($metadata)) {
            return 1;
        }

        $previouslyImported = maybe_unserialize($metadata->value ?? []);
        $previousDomain = $metadata->key ?? '';

        if ($previousDomain === $identifier &&
            !empty($previouslyImported['has_more']) &&
            $previouslyImported['has_more'] === true &&
            !empty($previouslyImported['next_page'])) {

            \WP_CLI::line("A previous incomplete migration was found for this domain:");
            \WP_CLI::line(" - Last imported page: " . ($previouslyImported['imported_page'] ?? 'N/A'));
            \WP_CLI::line(" - Tickets imported so far: " . ($previouslyImported['completed'] ?? 0));

            $choice = \cli\prompt('Do you want to resume from the last incomplete page? (yes/no)', 'yes');

            if (strtolower($choice) === 'yes') {
                $startPage = (int) $previouslyImported['next_page'];
                \WP_CLI::line("Resuming from page $startPage...");
                return $startPage;
            }

            \WP_CLI::line("Starting over from page 1...");
        }

        return 1;
    }

    /**
     * Process the migration by fetching and importing tickets page by page.
     *
     * Supports both page-number pagination (Freshdesk) and cursor-based pagination
     * (Zendesk) by carrying cursor/next_page_url forward from each response.
     *
     * @param int    $startPage Page number to start from
     * @param string $provider  Handler name passed to MigratorService. Defaults to 'freshdesk'.
     * @param array  $query     Credentials and pagination state. Defaults to Freshdesk class props.
     */
    private function processMigration($startPage = 1, $provider = 'freshdesk', $query = null)
    {
        try {
            if ($query === null) {
                $query = [
                    'access_token' => $this->accessToken,
                    'domain'       => $this->domain,
                ];
            }

            $page = $startPage;
            $migrator = new MigratorService;
            $totalImported = 0;
            $totalSkipped = 0;

            while (true) {
                \WP_CLI::line("Processing page: $page");

                $result = $migrator->handleImport($page, $provider, $query);

                if (empty($result) || !isset($result['completed'])) {
                    \WP_CLI::warning("Invalid response received for page $page. Stopping migration.");
                    break;
                }

                // Carry cursor/next_page_url forward for cursor-based providers (e.g. Zendesk)
                if (array_key_exists('cursor', $result)) {
                    $query['cursor'] = $result['cursor'];
                }
                if (array_key_exists('next_page_url', $result)) {
                    $query['next_page_url'] = $result['next_page_url'];
                }

                if (!empty($result['error'])) {
                    \WP_CLI::error("Migration error: " . ($result['message'] ?? 'Unknown error'));
                    break;
                }

                $imported = count($result['insert_ids'] ?? []);
                $skipped = intval($result['skips'] ?? 0);

                $totalImported += $imported;
                $totalSkipped += $skipped;

                \WP_CLI::success(sprintf(
                    "Page %d processed: %d ticket(s) imported, %d skipped",
                    $page,
                    $imported,
                    $skipped
                ));

                if (empty($result['has_more'])) {
                    break;
                }

                $page = intval($result['next_page'] ?? ($page + 1));
            }

            \WP_CLI::success(sprintf(
                "Migration complete. Total: %d imported, %d skipped.",
                $totalImported,
                $totalSkipped
            ));
        } catch (\Exception $e) {
            \WP_CLI::error("Migration failed: " . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Zendesk
    // -------------------------------------------------------------------------

    public function zendesk_ticket_import($args, $assoc_args)
    {
        try {
            if (!defined('FLUENTSUPPORTPRO_PLUGIN_VERSION')) {
                \WP_CLI::error('Fluent Support Pro is required for this command.');
                return;
            }

            $this->domain = \WP_CLI\Utils\get_flag_value($assoc_args, 'domain');
            if (empty($this->domain)) {
                $this->domain = \cli\prompt('Enter your Zendesk domain (e.g. https://yourcompany.zendesk.com)');
            }

            $email = \WP_CLI\Utils\get_flag_value($assoc_args, 'email');
            if (empty($email)) {
                $email = \cli\prompt('Enter your Zendesk admin email');
            }

            $this->accessToken = \WP_CLI\Utils\get_flag_value($assoc_args, 'access_token');
            if (empty($this->accessToken)) {
                $this->accessToken = \cli\prompt('Enter your API token');
            }

            if (empty($this->domain) || empty($email) || empty($this->accessToken)) {
                \WP_CLI::error('Domain, email, and access token are all required to proceed.');
                return;
            }

            $includeArchived = \WP_CLI\Utils\get_flag_value($assoc_args, 'include_archived', false);

            $startPage = $this->checkPreviousMigration('_fs_zendesk_migration_info', $this->domain);

            $query = [
                'access_token'    => $this->accessToken,
                'domain'          => $this->domain,
                'email'           => $email,
                'include_archived' => $includeArchived,
            ];

            // On resume, restore cursor state saved by the previous run
            if ($startPage > 1) {
                $savedProgress = Meta::where('object_type', '_fs_zendesk_migration_info')->first();
                if ($savedProgress) {
                    $savedData = maybe_unserialize($savedProgress->value ?? []);
                    if (!empty($savedData['cursor'])) {
                        $query['cursor'] = $savedData['cursor'];
                    }
                    if (!empty($savedData['next_page_url'])) {
                        $query['next_page_url'] = $savedData['next_page_url'];
                    }
                }
            }

            $this->processMigration($startPage, 'zendesk', $query);

        } finally {
            $this->domain = null;
            $this->accessToken = null;
        }
    }

    // -------------------------------------------------------------------------
    // HelpScout
    // -------------------------------------------------------------------------

    public function helpscout_ticket_import($args, $assoc_args)
    {
        try {
            if (!defined('FLUENTSUPPORTPRO_PLUGIN_VERSION')) {
                \WP_CLI::error('Fluent Support Pro is required for this command.');
                return;
            }

            $this->accessToken = \WP_CLI\Utils\get_flag_value($assoc_args, 'access_token');
            if (empty($this->accessToken)) {
                $this->accessToken = \cli\prompt('Enter your HelpScout access token');
            }

            $mailboxId = \WP_CLI\Utils\get_flag_value($assoc_args, 'mailbox_id');
            if (empty($mailboxId)) {
                $mailboxId = \cli\prompt('Enter your HelpScout mailbox ID');
            }

            if (empty($this->accessToken) || empty($mailboxId)) {
                \WP_CLI::error('Both access token and mailbox ID are required to proceed.');
                return;
            }

            $startPage = $this->checkPreviousMigration('_fs_helpscout_migration_info', $mailboxId);

            $query = [
                'access_token' => $this->accessToken,
                'mailbox'      => $mailboxId,
            ];

            $this->processMigration($startPage, 'helpscout', $query);

        } finally {
            $this->accessToken = null;
        }
    }
}
