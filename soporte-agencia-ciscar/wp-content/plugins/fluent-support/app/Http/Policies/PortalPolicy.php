<?php

namespace FluentSupport\App\Http\Policies;

use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Foundation\Policy;

class PortalPolicy extends Policy
{
    /**
     * Check user permission for any method
     * @param \FluentSupport\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        if ($request->get('on_behalf')) {
            return PermissionManager::userCan(['fst_sensitive_data', 'fst_manage_other_tickets']);
        }

        $hasAccess = !!get_current_user_id();

        if (!$hasAccess) {
            return false;
        }

        /*
         * Filter portal access settings
         *
         * @since v1.0.0
         * @param array $canAccess
         */
        $canAccess = apply_filters('fluent_support/user_portal_access_config', [
            'status'  => true,
            'message' => __('You do not have permission', 'fluent-support')
        ]);

        return $canAccess['status'];
    }

    /**
     * @param \FluentSupport\Framework\Request\Request $request
     * @return Boolean
     */
    public function getTicket(Request $request)
    {
        return $this->maybePublicSignedRequest($request);
    }

    public function createResponse(Request $request)
    {
        return $this->maybePublicSignedRequest($request);
    }

    public function closeTicket(Request $request)
    {
        return $this->maybePublicSignedRequest($request);
    }

    public function reOpenTicket(Request $request)
    {
        return $this->maybePublicSignedRequest($request);
    }

    public function uploadTicketFiles(Request $request)
    {
        if ($person = Helper::getCurrentAgent()) {
            return true;
        }

        return $this->maybePublicSignedRequest($request);
    }

    public function getCountries(Request $request)
    {
        if ($person = Helper::getCurrentAgent()) {
            return true;
        }

        return $this->maybePublicSignedRequest($request);
    }

    /**
     * @param \FluentSupport\Framework\Request\Request $request
     * @return Boolean
     */
    protected function maybePublicSignedRequest($request)
    {
        $ticketHash = $request->getSafe('intended_ticket_hash', 'sanitize_text_field');

        if ($ticketHash && Helper::isPublicSignedTicketEnabled()) {
            $urlParams = (array) $request->get_url_params();
            $ticketId  = isset($urlParams['ticket_id']) ? (int) $urlParams['ticket_id'] : $request->getSafe('ticket_id', 'intval');
            return !!Ticket::where('hash', $ticketHash)
                ->wherePublicIdentifier($ticketId)
                ->first();
        }

        return $this->verifyRequest($request);
    }

}
