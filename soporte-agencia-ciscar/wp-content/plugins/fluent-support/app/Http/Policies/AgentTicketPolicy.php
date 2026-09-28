<?php

namespace FluentSupport\App\Http\Policies;

use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\Framework\Foundation\Policy;

class AgentTicketPolicy extends Policy
{
    /**
     * Default policy check: GET requests require view permission, POST/PUT/DELETE require manage permission.
     *
     * @param \FluentSupport\Framework\Request\Request $request
     * @return Boolean
     */
    public function verifyRequest(Request $request)
    {
        if ($request->method() === 'GET') {
            $status = PermissionManager::userCan([
                'fst_view_tickets', 
                'fst_draft_reply', 
                'fst_manage_own_tickets',
                'fst_manage_unassigned_tickets', 
                'fst_manage_other_tickets'
            ]);
        } else {
            // Non-GET requests require manage permission by default.
            // Routes safe for draft/view-only agents have dedicated policy methods
            // (e.g. createResponse, createOrUpdatDraft, storeOrUpdateLabelSearch).
            $status = PermissionManager::canManageTickets();
        }

        return apply_filters('fluent_support/agent_has_access', $status, $request);
    }

    /**
     * Contact search backs the Add Ticket flow, so any agent who can manage
     * tickets needs it to work.
     */
    public function searchContact(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Bulk actions: delete requires fst_delete_tickets plus manage permission
     * (mirroring deleteTicket), others require manage permission.
     */
    public function doBulkActions(Request $request)
    {
        $action = $request->getSafe('bulk_action', 'sanitize_text_field');

        if ($action === 'delete_tickets') {
            return PermissionManager::currentUserCan('fst_delete_tickets')
                && PermissionManager::canManageTickets();
        }

        return PermissionManager::canManageTickets();
    }

    /**
     * Delete ticket requires explicit delete permission and manage permission.
     * View-only and draft agents resolve to full ticket visibility, so the
     * delete capability alone would let them delete any ticket.
     */
    public function deleteTicket(Request $request)
    {
        return PermissionManager::currentUserCan('fst_delete_tickets')
            && PermissionManager::canManageTickets();
    }

    /**
     * Delete an individual response: requires explicit delete permission and
     * manage permission, mirroring deleteTicket. Per-ticket access is enforced
     * in the controller via ensureCanAccessTicket().
     */
    public function deleteResponse(Request $request)
    {
        return PermissionManager::currentUserCan('fst_delete_tickets')
            && PermissionManager::canManageTickets();
    }

    /**
     * Update an individual response: requires manage permission. Author/type
     * and per-ticket access rules are enforced in the controller.
     */
    public function updateResponse(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Creating a response: accessible to manage and draft agents.
     * View-only agents cannot create responses or drafts.
     */
    public function createResponse(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    /**
     * Auto-save draft: accessible to manage and draft agents.
     * View-only agents cannot create drafts.
     */
    public function createOrUpdatDraft(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    /**
     * Label search save: POST endpoint accessible to any agent with ticket access.
     */
    public function storeOrUpdateLabelSearch(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    /**
     * Label search delete: DELETE endpoint accessible to any agent (personal data).
     */
    public function deleteLabelSearch(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    /**
     * Live activity removal: DELETE endpoint accessible to any agent (cleanup).
     */
    public function removeLiveActivity(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    /**
     * Update ticket property (product, priority, etc.): requires manage ticket permission.
     */
    public function updateTicketProperty(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Add watchers to a ticket: requires manage ticket permission.
     */
    public function addTicketWatchers(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Sync watchers on a ticket: requires manage ticket permission.
     */
    public function syncTicketWatchers(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Update estimated time: requires manage ticket permission.
     */
    public function updateEstimatedTime(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Manual time commit: requires manage ticket permission.
     */
    public function manualCommitTrack(Request $request)
    {
        return PermissionManager::canManageTickets();
    }

    /**
     * Approve draft response: requires approve permission and ticket route access.
     */
    public function approveDraftResponse(Request $request)
    {
        return PermissionManager::currentUserCan('fst_approve_draft_reply')
            && PermissionManager::canAccessTicketRoutes();
    }

    /**
     * Delete draft: accessible to draft-capable agents (same as create/update draft).
     */
    public function deleteDraft(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    /**
     * FluentBot AI-assist actions: accessible to any agent with ticket access.
     */
    public function generateResponse(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getRuntimeConfig(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function generateStreamResponse(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getTicketSummary(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getTicketTone(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function markRead(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function markAllRead(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getChatId(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getChatMessages(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getContextSelection(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function getConversations(Request $request)
    {
        return PermissionManager::canAccessTicketRoutes();
    }

    public function saveChatId(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    public function deleteChatId(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    public function saveContextSelection(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    public function switchConversation(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    public function createFeedback(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }

    public function deleteFeedback(Request $request)
    {
        return PermissionManager::canManageTickets()
            || PermissionManager::currentUserCan('fst_draft_reply');
    }
}
