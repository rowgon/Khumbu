<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\MailBox;
use FluentSupport\App\Services\EmailNotification\Settings;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\MailerInbox\MailBoxService;
use FluentSupport\Framework\Http\Request\Request;

class MailBoxController extends Controller
{
    /**
     * index method will return the list of business inbox
     * @param Request $request
     * @return array
     */
    public function index(MailBoxService $mailboxService)
    {
        return $mailboxService->getMailBoxes();
    }

    /**
     * get method will fetch and return information related to business box
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function get( MailBox $mailBox, $id )
    {
        return [
            'mailbox' => $mailBox->getMailBox( $id )
        ];

    }


    /**
     * Save method will create new business box
     * @param Request $request
     * @param MailBox $mailBox
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function save(Request $request, MailBox $mailBox)
    {
        $data = wp_unslash( $request->get('business', null) );
        $data = $this->sanitizeMailboxData($data);

        $this->validate($data, [
            'name' => 'required',
            'email' => 'required'
        ]);

        return [
            'message' => __('Mailbox has been created successfully', 'fluent-support'),
            'mailbox' => $mailBox->createMailBox( $data )
        ];
    }

    /**
     * This `update` method will update existing information for a business by mailbox id
     * @param Request $request
     * @param MailBox $mailBox
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function update(Request $request, MailBox $mailBox, $id)
    {
        try{
            $data = wp_unslash( $request->get('business', null) );
            $data = $this->sanitizeMailboxData($data);

            $this->validate($data, [
                'name' => 'required',
                'email' => 'required'
            ]);

            return [
                'message' => __( 'Mailbox has been saved', 'fluent-support' ),
                'mailbox' => $mailBox->updateMailBox( $data, $id )
            ];
        }catch (\Exception $e){
            return [
                'message' => Helper::getSafeErrorMessage($e),
            ];
        }
    }

    /**
     * This `delete` method will delete a business from mailbox and replaced with alternative
     * @param Request $request
     * @param MailBoxService $mailBoxService
     * @param int $id
     * @throws \Exception
     * @return array
     */
    public function delete(Request $request, MailBoxService $mailBoxService, $id)
    {
        try {
            return $mailBoxService->deleteMailBox( $id, $request->getSafe('fallback_id', 'intval') );
        } catch (\Exception $e) {
            return [
                'message' => Helper::getSafeErrorMessage($e),
            ];
        }
    }


    /**
     * This `moveTickets` method will move tickets from one mailbox to another
     * @param Request $request
     * @param MailBoxService $mailBoxService
     * @param int $id
     * @throws \Exception
     * @return array
     */
    public function moveTickets(Request $request, MailBoxService $mailBoxService, $id)
    {
        try {
            $data = $request->only(['ticket_ids', 'new_box_id', 'move_type']);
            return $mailBoxService->moveTickets( $data, $id );
        } catch (\Exception $e) {
            return $this->sendError(Helper::getSafeErrorMessage($e));
        }
    }

    /**
     * This `getEmailSettings` method will get and return the mailbox email settings
     * @param Request $request
     * @param Settings $settings
     * @param $id
     * @return array
     */
    public function getEmailSettings(Request $request, Settings $settings, $id)
    {
        $box = MailBox::findOrFail($id);
        $emailType = $request->getSafe('email_type', 'sanitize_text_field');

        return [
            'email_settings' => $settings->getBoxEmailSettings($box, $emailType)
        ];
    }

    /**
     * This `getEmailsSetups` method will return email settings for a business box by box id
     * @param MailBoxService $mailBoxService
     * @param $id
     * @return array
     */
    public function getEmailsSetups( MailBoxService $mailBoxService, $id )
    {
       return $mailBoxService->getEmailsSetups($id);
    }

    /**
     * This `saveEmailSettings` method will save the email settings for a business box using box id
     * @param Request $request
     * @param Settings $settings
     * @param $id
     * @return array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function saveEmailSettings( Request $request, MailBoxService $mailBoxService, $id )
    {
        $data = wp_unslash($request->get('email_settings', null));
        $data = is_array($data) ? [
            'key'              => isset($data['key']) ? sanitize_key($data['key']) : '',
            'title'            => isset($data['title']) ? sanitize_text_field($data['title']) : '',
            'email_subject'    => isset($data['email_subject']) ? sanitize_text_field($data['email_subject']) : '',
            'email_body'       => isset($data['email_body']) ? wp_kses_post($data['email_body']) : '',
            'status'           => isset($data['status']) ? sanitize_text_field($data['status']) : '',
            'can_edit_subject' => isset($data['can_edit_subject']) ? sanitize_text_field($data['can_edit_subject']) : '',
            'send_attachments' => isset($data['send_attachments']) ? sanitize_text_field($data['send_attachments']) : '',
        ] : [];

        $emailType = $request->getSafe('email_type', 'sanitize_text_field');

        $this->validate($data, [
            'email_subject' => 'required',
            'email_body' => 'required'
        ]);

        return $mailBoxService->saveEmailSettings( $emailType, $id, $data );
    }

    /**
     * This `setAsDefault` method will set a business box as default
     * @param MailBoxService $mailBoxService
     * @param $id
     * @return array
     */
    public function setAsDefault( MailBoxService $mailBoxService, $id )
    {
        return $mailBoxService->setAsDefault( $id );
    }

    /**
     * This `getTickets` method will return the list of tickets for a business box
     * @param Request $request
     * @param MailBox $mailBox
     * @param int $id
     * @return array
     */
    public function getTickets(Request $request, MailBoxService $mailBoxService, $id)
    {
        $filters = $request->get('filters', null);
        $filters = is_array($filters) ? [
            'status_type'  => isset($filters['status_type']) ? sanitize_text_field($filters['status_type']) : '',
            'customer_id'  => isset($filters['customer_id']) ? intval($filters['customer_id']) : 0,
            'product_id'   => isset($filters['product_id']) ? intval($filters['product_id']) : 0,
            'mailbox_id'   => isset($filters['mailbox_id']) ? intval($filters['mailbox_id']) : 0,
            'ticket_title' => isset($filters['ticket_title']) ? sanitize_text_field($filters['ticket_title']) : '',
            'notes'        => isset($filters['notes']) ? sanitize_text_field($filters['notes']) : '',
        ] : [];

        return $mailBoxService->getTickets( $filters, $id );
    }

    /**
     * Sanitize mailbox data array
     *
     * @param mixed $data
     * @return array
     */
    private function sanitizeMailboxData($data)
    {
        if (!is_array($data)) {
            return [];
        }

        $sanitized = [
            'name'         => isset($data['name']) ? sanitize_text_field($data['name']) : '',
            'email'        => isset($data['email']) ? sanitize_email($data['email']) : '',
            'box_type'     => isset($data['box_type']) ? sanitize_key($data['box_type']) : '',
            'mapped_email' => isset($data['mapped_email']) ? sanitize_email($data['mapped_email']) : '',
            'email_footer' => isset($data['email_footer']) ? wp_kses_post($data['email_footer']) : '',
            'is_default'   => isset($data['is_default']) ? sanitize_text_field($data['is_default']) : 'no',
        ];

        // Handle nested settings array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $sanitized['settings'] = map_deep($data['settings'], 'sanitize_text_field');
            // Preserve email in settings
            if (isset($data['settings']['admin_email_address'])) {
                $sanitized['settings']['admin_email_address'] = sanitize_email($data['settings']['admin_email_address']);
            }
        }

        return $sanitized;
    }
}
