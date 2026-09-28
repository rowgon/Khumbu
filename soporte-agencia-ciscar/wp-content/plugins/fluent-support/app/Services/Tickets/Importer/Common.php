<?php

namespace FluentSupport\App\Services\Tickets\Importer;

use FluentSupport\App\Models\Person;

class Common
{
    public static function updateOrCreatePerson($personData)
    {
        $emailArray = [
            'email' => $personData['email'],
            'person_type' => $personData['person_type']
        ];

        $person = Person::updateOrCreate($emailArray, $personData);
        return $person->toArray();
    }

    public static function formatPersonData($personData, $type)
    {
        if(!$personData) {
            return [];
        }

        $name = explode(' ', $personData->name);

        return [
            'first_name' => $name[0] ?? '',
            'last_name' => $name[1] ?? '',
            'email' => $personData->email ?? $personData->address ?? null,
            'person_type' => $type
        ];
    }

    // Reduce a remote-supplied attachment name to a safe, traversal-free basename.
    public static function sanitizeAttachmentFilename($fileName)
    {
        $fileName = wp_basename((string) $fileName);
        $fileName = sanitize_file_name($fileName);
        $fileName = str_replace("\0", '', $fileName);
        $fileName = ltrim($fileName, '.');

        if ($fileName === '') {
            $fileName = 'attachment-' . wp_generate_password(8, false, false);
        }

        return $fileName;
    }

    // Reject anything that isn't a plain https:// hostname resolving to a public,
    // non-reserved IP — blocks loopback, RFC1918, link-local/cloud-metadata
    // (169.254.0.0/16), and raw IP-literal hosts (real vendor tenants are never IPs).
    public static function isSafeRemoteUrl($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }

        $parts = wp_parse_url($url);
        if (empty($parts['scheme']) || strtolower($parts['scheme']) !== 'https' || empty($parts['host'])) {
            return false;
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return false;
        }

        $host = trim($parts['host'], '.');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip === $host) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    // Download a file from a remote URL and create a new directory for this if not exists
    // Then save the file to the new directory and move this directory to a new given directory
    public static function downloadFile($remoteUrl, $baseDir, $fileName)
    {
        if (!self::isSafeRemoteUrl($remoteUrl)) {
            return new \WP_Error('import_unsafe_url', __('Attachment URL is not allowed.', 'fluent-support'));
        }

        $baseDir = trailingslashit($baseDir);

        if (!wp_mkdir_p($baseDir)) {
            return new \WP_Error('import_dir_error', __('Could not create the attachment import directory.', 'fluent-support'));
        }

        $realBaseDir = realpath($baseDir);
        if ($realBaseDir === false) {
            return new \WP_Error('import_dir_error', __('Could not resolve the attachment import directory.', 'fluent-support'));
        }
        $realBaseDir = rtrim($realBaseDir, '/\\') . DIRECTORY_SEPARATOR;

        $safeFileName = self::sanitizeAttachmentFilename($fileName);
        $existingPath = $baseDir . $safeFileName;

        // Preserve the original dedupe behaviour: if this exact attachment was
        // already imported for this ticket, reuse it instead of re-downloading
        // (wp_unique_filename() would otherwise mint a "-1" copy every re-import).
        if (file_exists($existingPath)) {
            $filePath = $existingPath;
            $uniqueFileName = $safeFileName;
        } else {
            $uniqueFileName = wp_unique_filename($baseDir, $safeFileName);
            $filePath = $baseDir . $uniqueFileName;
        }

        // Belt-and-braces: the resolved parent directory must still be the base dir.
        $resolvedParent = realpath(dirname($filePath));
        if ($resolvedParent === false || strpos($resolvedParent . DIRECTORY_SEPARATOR, $realBaseDir) !== 0) {
            return new \WP_Error('import_path_error', __('Resolved attachment path escapes the import directory.', 'fluent-support'));
        }

        if (file_exists($filePath)) {
            return $filePath;
        }

        $maxBytes = 25 * MB_IN_BYTES;

        // Cap the transfer itself (not just the assembled body) so an oversized
        // remote response can't be fully pulled into memory before we reject it.
        // Ask for one byte over the limit so a truncated response (real size > cap)
        // can still be told apart from a response that legitimately ends at the cap.
        $response = wp_safe_remote_get($remoteUrl, [
            'timeout'              => 60,
            'stream'               => false,
            'limit_response_size'  => $maxBytes + 1,
            'redirection'          => 0,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            return new \WP_Error('import_download_error', sprintf(__('Attachment download failed with HTTP status %d.', 'fluent-support'), $status));
        }

        $file_contents = wp_remote_retrieve_body($response);
        if (empty($file_contents)) {
            return new \WP_Error('import_download_error', __('Attachment download returned an empty response.', 'fluent-support'));
        }

        if (strlen($file_contents) > $maxBytes) {
            return new \WP_Error('import_download_error', __('Attachment exceeds the maximum allowed download size.', 'fluent-support'));
        }

        $tmpPath = $baseDir . '.' . $uniqueFileName . '-' . uniqid('', true) . '.part';
        if (file_put_contents($tmpPath, $file_contents) === false) {
            return new \WP_Error('import_write_error', __('Could not write the downloaded attachment.', 'fluent-support'));
        }

        $fileType = wp_check_filetype_and_ext($tmpPath, $uniqueFileName);
        if (empty($fileType['ext']) || empty($fileType['type'])) {
            @unlink($tmpPath);
            return new \WP_Error('import_filetype_error', __('Attachment file type is not allowed.', 'fluent-support'));
        }

        if (!@rename($tmpPath, $filePath)) {
            @unlink($tmpPath);
            return new \WP_Error('import_write_error', __('Could not save the downloaded attachment.', 'fluent-support'));
        }

        return $filePath;
    }
}
