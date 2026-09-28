<?php
namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\Tickets\Importer\MigratorService;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Services\Tickets\Importer\BaseImporter;


class TicketImportController extends Controller
{
    public function getStats ( MigratorService $importService )
    {
        $stats = $importService->getStats();
        if(!$stats) {
            return [];
        }
        return $stats;
    }

    public function importTickets(MigratorService $importService, Request $request)
    {
        try {
            $handler = $request->getSafe('handler', 'sanitize_key');
            $rawQuery = $request->get('query', []);
            $query = [];

            if (is_array($rawQuery)) {
                if (isset($rawQuery['access_token'])) {
                    $query['access_token'] = sanitize_text_field($rawQuery['access_token']);
                }
                if (isset($rawQuery['mailbox'])) {
                    $query['mailbox'] = intval($rawQuery['mailbox']);
                }
                if (isset($rawQuery['domain'])) {
                    $query['domain'] = sanitize_text_field($rawQuery['domain']);
                }
                if (isset($rawQuery['email'])) {
                    $query['email'] = sanitize_email($rawQuery['email']);
                }
                if (isset($rawQuery['cursor'])) {
                    $query['cursor'] = sanitize_text_field($rawQuery['cursor']);
                }
                if (!empty($rawQuery['include_archived'])) {
                    $query['include_archived'] = true;
                }
            }

            return $importService->handleImport( $request->getSafe('page', 'intval'), $handler, $query );
        } catch (\Exception $e) {
            return $this->sendError(Helper::getSafeErrorMessage($e));
        }
    }

    public function deleteTickets (MigratorService $importService, Request $request)
    {
        return $importService->deleteTickets($request->getSafe('page', 'intval'), $request->getSafe('handler', 'sanitize_key'));
    }
}
