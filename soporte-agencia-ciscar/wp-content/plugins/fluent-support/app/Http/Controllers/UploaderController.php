<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Attachment;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\EmailNotification\Settings;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Services\Includes\UploadService;

/**
 * UploaderController class is responsible for uploading file
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class UploaderController extends Controller
{
    /**
     * uploadTicketFiles method will upload all the attached file in a ticket
     * @param Request $request
     * @return array[]
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function uploadTicketFiles(Request $request)
    {
        $settings = (new Settings())->globalBusinessSettings();
        $maxFileSize = floatval($settings['max_file_size']);
        $maxFileUpload = intval($settings['max_file_upload']);
        $mimeHeadings = Helper::getAcceptedMimeHeadings();
        $maxSizeBytes = $maxFileSize * 1024;
        $imageType = $request->type ? $request->type : null;

        $files = $request->files();

        if ($partsError = $this->rejectUnexpectedFileParts($files)) {
            return $partsError;
        }

        $ticketId = $this->resolveTicketId($request);
        $person = $this->resolvePerson($ticketId, $request);

        if ($permissionError = $this->checkPermissionToUploadFile($person)) {
            return $permissionError;
        }

        if ($quotaError = $this->checkAttachmentQuota($files, $person, $ticketId, $maxFileUpload)) {
            return $quotaError;
        }

        $this->validateUploadedFiles($files, $maxSizeBytes, $mimeHeadings, $maxFileSize);

        try {
            $uploadedFiles = UploadService::handleTempFileUpload($files);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e),
            ]);
        }

        if (is_wp_error($uploadedFiles)) {
            return $this->sendError([
                'message' => $uploadedFiles->get_error_message(),
            ]);
        }

        $attachmentHashes = $this->createAttachmentRecords($uploadedFiles, $ticketId, $person, $imageType);

        return [
            'attachments' => $attachmentHashes,
        ];
    }

    /**
     * Only the "file" multipart part is validated and processed downstream
     * (UploadService/FileSystem::put() loops every top-level part it is given), so
     * any other part name must be rejected here rather than silently passed through.
     */
    private function rejectUnexpectedFileParts($files)
    {
        $files = (array) $files;
        $unexpectedKeys = array_diff(array_keys($files), ['file']);

        if ($unexpectedKeys || empty($files['file'])) {
            return $this->sendError([
                'message' => __('Invalid file upload request.', 'fluent-support'),
            ]);
        }

        return null;
    }

    private function checkAttachmentQuota($files, $person, $ticketId, $maxFileUpload)
    {
        if ($maxFileUpload <= 0) {
            return null;
        }

        $newFiles = isset($files['file']) ? $files['file'] : null;
        $newFilesCount = is_array($newFiles) ? count($newFiles) : 1;

        $existingCount = Attachment::where('person_id', $person->id)
            ->where('ticket_id', $ticketId)
            ->where('status', 'in-active')
            ->count();

        if (($existingCount + $newFilesCount) > $maxFileUpload) {
            return $this->sendError([
                // translators: %d is the maximum number of files allowed per ticket
                'message' => sprintf(__('You can upload a maximum of %d files.', 'fluent-support'), $maxFileUpload),
            ]);
        }

        return null;
    }

    private function validateUploadedFiles($files, $maxSizeBytes, $mimeHeadings, $maxFileSize)
    {
        $validationRules = [
            'file' => 'max:' . $maxSizeBytes . '|mimetypes:' . implode(',', Helper::ticketAcceptedFileMiles()),
        ];

        $validationMessages = [
            // translators: %s is a comma-separated list of allowed file types (e.g., "jpg, png, pdf")
            'file.mimetypes' => sprintf(__('Only %s files are allowed.', 'fluent-support'), implode(', ', $mimeHeadings)),
            // translators: %.01f is the maximum file size in megabytes
            'file.max'       => sprintf(__('The file cannot be more than %.01fMB. Please upload somewhere like Dropbox/Google Drive and paste the link in the response', 'fluent-support'), $maxFileSize),
        ];

        $this->validate($files, $validationRules, $validationMessages);
    }

    private function resolveTicketId($request)
    {
        $ticketId = $request->getSafe('ticket_id', 'intval');

        if ($ticketId == 'undefined' || !$ticketId) {
            return null;
        }

        if (Helper::getCurrentAgent()) {
            return $ticketId;
        }

        $ticket = Ticket::wherePublicIdentifier($ticketId)->first();

        return $ticket ? $ticket->id : null;
    }

    private function resolvePerson($ticketId, Request $request)
    {
        $agent = Helper::getCurrentAgent();
        if ($agent) {
            return $agent;
        }

        if ($ticketId && Helper::isPublicSignedTicketEnabled()) {
            $intendedTicketHash = $request->getSafe('intended_ticket_hash', 'sanitize_text_field');
            if ($intendedTicketHash && $intendedTicketHash != 'undefined') {
                $ticket = Ticket::with(['customer'])
                    ->where('hash', $intendedTicketHash)
                    ->wherePublicIdentifier($ticketId)
                    ->first();

                if ($ticket && $ticket->customer) {
                    return $ticket->customer;
                }
            }
        }

        return Helper::getCurrentPerson();
    }

    private function checkPermissionToUploadFile($person)
    {
        if (!$person) {
            return $this->sendError([
                'message' => __('You do not have permission to upload a file', 'fluent-support'),
            ]);
        }

        if ($person->person_type === 'customer') {
            $disabledFields = apply_filters('fluent_support/disabled_ticket_fields', []);
            if (in_array('file_upload', $disabledFields)) {
                return $this->sendError([
                    'message' => __('You do not have permission to upload a file', 'fluent-support'),
                ]);
            }
        }
    }

    private function createAttachmentRecords($uploadedFiles, $ticketId, $person, $imageType)
    {
        $attachments = [];
        $directPasteUrl = null;

        foreach ($uploadedFiles as $file) {
            if (empty($file['file_path'])) continue;

            $fileData = [
                'ticket_id' => intval($ticketId) ?: NULL,
                'person_id' => intval($person->id),
                'file_type' => $file['type'],
                'file_path' => $file['file_path'],
                'full_url'  => esc_url($file['url']),
                'title'     => sanitize_file_name($file['name']),
                'driver'    => 'local',
                'status'    => 'in-active',
                'settings'  => [
                    'local_temp_path' => $file['file_path'],
                ]
            ];

            try {
                $attachment = Attachment::create($fileData);
                $attachments[] = $attachment->file_hash;

                if ($imageType == 'direct_paste') {
                    $directPasteUrl = $attachment->secureUrl;
                }

                do_action('fluent_support/attachment_uploaded_as_temp', $attachment, $ticketId);
                $driver = Helper::getUploadDriverKey();

                do_action_ref_array('fluent_support/attachment_uploaded_as_temp_' . $driver, [&$attachment, $ticketId]);
            } catch (\Exception $exception) {
                continue;
            }
        }

        return $imageType == 'direct_paste' ? $directPasteUrl : $attachments;
    }

    public function uploadImage(Request $request)
    {
        $images = $request->files();
        $ticketId = $this->resolveTicketId($request);

        $validationError = $this->isValidImageType($images);
        if ($validationError) {
            return $validationError;
        }

        try {
            $uploadedFiles = UploadService::handleUploadToLocal($ticketId, $images);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e),
            ]);
        }

        return [
            'images' => $uploadedFiles,
        ];
    }

    private function isValidImageType($images)
    {
        if (empty($images['image'])) {
            return $this->sendError([
                'message' => __('No image file provided.', 'fluent-support'),
            ]);
        }

        $file = $images['image'];
        $tempPath = $file->getPathname();
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['gif', 'ief', 'jpeg', 'jpg', 'webp', 'pjpeg', 'ktx', 'png'];

        if (!in_array($extension, $allowedExtensions)) {
            return $this->sendError([
                'message' => __('Invalid image file type.', 'fluent-support'),
            ]);
        }

        $allowedMimes = Helper::getMimeGroups()['images']['mimes'];
        $realMime = $this->detectMimeType($tempPath);

        if (!$realMime || !in_array($realMime, $allowedMimes)) {
            return $this->sendError([
                'message' => __('File content does not match the image type.', 'fluent-support'),
            ]);
        }

        return null;
    }

    private function detectMimeType($filePath)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mime;
        }

        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }

        // getimagesize works for standard image formats as last resort
        $imageInfo = @getimagesize($filePath);
        return $imageInfo ? $imageInfo['mime'] : false;
    }
}
