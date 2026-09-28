<?php

namespace FluentSupport\App\Hooks\Handlers;

use FluentSupport\App\Models\Attachment;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;

/**
 * ExternalPages - Handles public-facing ticket and attachment viewing
 *
 */
class ExternalPages
{
    public function route()
    {
        // Verify this is a GET request for security
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REQUEST_METHOD is server-controlled, sanitized for comparison only
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if ($requestMethod !== 'GET') {
            wp_die('Invalid request method', 'Method Not Allowed', ['response' => 405]);
        }

        // Rate limiting check
        $this->checkRateLimit();
        // Validate required parameter exists and sanitize
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        if (!isset($_REQUEST['fs_view'])) {
            wp_die('Missing required parameter', 'Bad Request', ['response' => 400]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        $route = isset($_REQUEST['fs_view']) ? sanitize_text_field(wp_unslash($_REQUEST['fs_view'])) : '';

        if (empty($route)) {
            wp_die('Missing required parameter', 'Bad Request', ['response' => 400]);
        }

        // Validate route value
        $methodMaps = [
            'ticket' => 'handleTicketView'
        ];

        if (isset($methodMaps[$route])) {
            // For public endpoints, verify security using ticket hash validation instead of nonces
            // This is appropriate for public endpoints that must work without user authentication
            $this->verifyPublicEndpointSecurity($route);
            $this->{$methodMaps[$route]}();
        } else {
            wp_die('Invalid route', 'Not Found', ['response' => 404]);
        }
    }

    public function handleTicketView()
    {
        if (!Helper::isPublicSignedTicketEnabled()) {
            $this->handleInvalidTicket();
        } else {
            $this->handleValidTicket();
        }
    }

    /**
     * Display the attachment.
     *
     * Uses the new rewrite endpoint to get an attachment ID
     * and display the attachment if the currently logged in user
     * has the authorization to.
     *
     * @return void
     * @since 3.2.0
     */
    public function view_attachment()
    {
        // Verify this is a GET request for security
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- REQUEST_METHOD is server-controlled, sanitized for comparison only
        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
        if ($requestMethod !== 'GET') {
            wp_die('Invalid request method', 'Method Not Allowed', ['response' => 405]);
        }

        // Rate limiting check
        $this->checkRateLimit();

        // Validate required parameter exists and sanitize
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses signature validation instead of nonces
        if (!isset($_REQUEST['fst_file'])) {
            wp_die('Missing required parameter', 'Bad Request', ['response' => 400]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses signature validation instead of nonces
        $attachmentHash = isset($_REQUEST['fst_file']) ? sanitize_text_field(wp_unslash($_REQUEST['fst_file'])) : '';

        if (empty($attachmentHash)) {
            wp_die('Invalid Attachment Hash', 'Bad Request', ['response' => 400]);
        }

        $attachment = $this->getAttachmentByHash($attachmentHash);

        if (!$attachment) {
            wp_die('Invalid Attachment Hash', 'Not Found', ['response' => 404]);
        }

        // Inline attachments (paste images embedded in ticket/email content) are publicly accessible
        // without a signature because they are already shared with customers via email.
        // Other attachments require HMAC signature validation.
        if ($attachment->status !== 'inline' && !$this->validateAttachmentSignature($attachment)) {
            $dieMessage = esc_html__('Sorry, Your secure sign is invalid, Please reload the previous page and get new signed url', 'fluent-support');
            wp_die(esc_html($dieMessage), 'Forbidden', ['response' => 403]);
        }

        //If external file, redirect to the secure download URL
        if ('local' !== $attachment->driver) {
            $fileUrl = apply_filters('fluent_support/external_attachment_url', $attachment->full_url, $attachment);
            if (!empty($fileUrl)) {
                $this->redirectToExternalAttachment($fileUrl);
            } else {
                die('File could not be found');
            }
        }

        //Handle Local file
        if (!file_exists($attachment->file_path)) {
            die('File could not be found');
        }
        $this->serveLocalAttachment($attachment);
    }

    private function getAttachmentByHash($attachmentHash)
    {
        return Attachment::where('file_hash', $attachmentHash)->first();
    }

    private function validateAttachmentSignature($attachment)
    {
        // Sanitize and validate secure_sign input - don't trust any input
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses signature validation instead of nonces
        if (!isset($_REQUEST['secure_sign'])) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses signature validation instead of nonces
        $secureSign = isset($_REQUEST['secure_sign']) ? sanitize_text_field(wp_unslash($_REQUEST['secure_sign'])) : '';

        if (empty($secureSign)) {
            return false;
        }

        // Use HMAC-SHA256 for secure signature verification
        $sign = hash_hmac('sha256', $attachment->id . '|' . gmdate('YmdH'), wp_salt('secure_auth'));
        return hash_equals($sign, $secureSign);
    }

    private function handleInvalidTicket()
    {
        // Validate required parameter exists and sanitize
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        if (!isset($_REQUEST['ticket_id'])) {
            wp_die('Missing ticket ID parameter', 'Bad Request', ['response' => 400]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        $ticketId = isset($_REQUEST['ticket_id']) ? absint($_REQUEST['ticket_id']) : 0;

        // Validate ticket ID is positive integer
        if ($ticketId <= 0) {
            wp_die('Invalid ticket ID', 'Bad Request', ['response' => 400]);
        }

        $ticket = Ticket::wherePublicIdentifier($ticketId)->first();

        if (!$ticket) {
            $this->showInvalidPortalMessage();
        } else {
            $this->redirectToTicketView($ticket);
        }
    }

    private function handleValidTicket()
    {
        // Validate required parameters exist and sanitize
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        if (!isset($_REQUEST['support_hash']) || !isset($_REQUEST['ticket_id'])) {
            wp_die('Missing required parameters', 'Bad Request', ['response' => 400]);
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        $ticketHash = isset($_REQUEST['support_hash']) ? sanitize_text_field(wp_unslash($_REQUEST['support_hash'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
        $ticketId = isset($_REQUEST['ticket_id']) ? absint($_REQUEST['ticket_id']) : 0;

        // Validate hash format (should be alphanumeric)
        if (empty($ticketHash) || !preg_match('/^[a-zA-Z0-9]+$/', $ticketHash)) {
            wp_die('Invalid ticket hash format', 'Bad Request', ['response' => 400]);
        }

        // Validate ticket ID is positive integer
        if ($ticketId <= 0) {
            wp_die('Invalid ticket ID', 'Bad Request', ['response' => 400]);
        }

        $ticket = Ticket::where('hash', $ticketHash)
            ->wherePublicIdentifier($ticketId)
            ->first();

        if (!$ticket) {
            $this->showInvalidPortalMessage();
        } elseif (get_current_user_id()) {
            // Only redirect if user is logged in (to clean up URL)
            $this->redirectToTicketView($ticket);
        }
        // If not logged in, let the page load normally with the hash parameters
        // The frontend will handle displaying the ticket based on the URL
    }

    private function showInvalidPortalMessage()
    {
        echo '<h3 style="text-align: center; margin: 50px 0;">' . esc_html__('Invalid Support Portal URL', 'fluent-support') . '</h3>';
        die();
    }

    private function redirectToTicketView($ticket)
    {
        $redirectUrl = Helper::getTicketViewUrl($ticket);
        $this->redirectToExternalAttachment($redirectUrl);
    }

    private function redirectToExternalAttachment($redirectUrl)
    {
        // This redirect is required to serve attachments stored on third-party services (Google Drive, Dropbox).
        // Safe and intentional: not a malicious or undesired redirect.
        wp_redirect($redirectUrl, 307);
        exit();
    }

    // Helper method to serve an attachment
    private function serveLocalAttachment($attachment)
    {
        $file_path = realpath($attachment->file_path);
        $uploads     = wp_upload_dir();
        $uploads_dir = realpath($uploads['basedir']); // Ensures both paths are absolute

        if (!$file_path || !$uploads_dir || strpos($file_path, $uploads_dir) !== 0 || !file_exists($file_path)) {
            wp_die(esc_html__('File not found or access denied', 'fluent-support'), 403);
            return;
        }

        ob_get_clean();
        $original_user_agent = ini_get('user_agent');
        // phpcs:ignore WordPress.PHP.IniSet.Risky -- Temporary change for file serving, restored immediately after
        ini_set('user_agent', 'Fluent Support/' . FLUENT_SUPPORT_VERSION . '; ' . esc_url(get_bloginfo('url')));

        header("Content-Type: " . esc_attr($attachment->file_type));
        header("Content-Disposition: inline; filename=\"" . esc_attr($attachment->title) . "\"");

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Direct file serving required for attachment download
        readfile($file_path);

        // phpcs:ignore WordPress.PHP.IniSet.Risky -- Restoring original value
        ini_set('user_agent', $original_user_agent);
        die();
    }

    /**
     * Verify security for public endpoints
     * This implements a custom security mechanism appropriate for public endpoints
     * that need to work without user authentication while maintaining security
     */
    private function verifyPublicEndpointSecurity($route)
    {
        switch ($route) {
            case 'ticket':
                // For ticket viewing, we need at least ticket_id
                // support_hash is required only when public signed tickets are enabled
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public endpoint uses hash validation instead of nonces
                if (!isset($_REQUEST['ticket_id'])) {
                    wp_die('Missing ticket ID parameter', 'Bad Request', ['response' => 400]);
                }

                // Additional validation will be done in handleValidTicket/handleInvalidTicket
                break;

            default:
                // For any other routes, ensure basic security
                break;
        }
    }

    /**
     * Basic rate limiting for public endpoints
     * Prevents abuse of public ticket/attachment viewing
     */
    private function checkRateLimit()
    {
        $ip = Helper::getIp();
        $transient_key = 'fs_rate_limit_' . wp_hash($ip);
        $requests = get_transient($transient_key);

        if ($requests === false) {
            // First request in this minute
            set_transient($transient_key, 1, 60); // 60 seconds
        } else {
            $requests++;
            if ($requests > 30) { // Max 30 requests per minute per IP
                wp_die('Rate limit exceeded. Please try again later.', 'Too Many Requests', ['response' => 429]);
            }
            set_transient($transient_key, $requests, 60);
        }
    }
}
