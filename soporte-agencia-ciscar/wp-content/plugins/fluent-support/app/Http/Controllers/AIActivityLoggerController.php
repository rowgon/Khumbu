<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;

/**
 *  ActivityLoggerController class for REST API
 * This class is responsible for getting data for all request related to activity and activity settings
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class AIActivityLoggerController extends Controller
{
    public function getAIActivities(Request $request)
    {
        try {
            $filters = $request->get('filters', null);
            $filters = is_array($filters) ? map_deep($filters, 'sanitize_text_field') : [];

            return Helper::getAIActivities( [
                'page' => $request->getSafe('page', 'intval', 1),
                'per_page' => $request->getSafe('per_page', 'intval', 10),
                'from' => $request->getSafe('from', 'sanitize_text_field', ''),
                'to'   => $request->getSafe('to', 'sanitize_text_field', ''),
                'filters' => $filters,
            ] );
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * updateSettings method will update existing activity settings
     * @return \WP_REST_Response | array
     */
    public function updateSettings (Request $request)
    {
        $settings = $request->get('ai_activity_settings', null);
        $settings = is_array($settings) ? $settings : [];
        $settings = [
            'delete_days'  => intval($settings['delete_days'] ?? 0),
            'disable_logs' => sanitize_text_field($settings['disable_logs'] ?? '')
        ];
        try {
            return Helper::updateAISettings($settings);
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * getSettings method will get the list of activity settings and return
     * @return \WP_REST_Response | array
     */
    public function getSettings()
    {
        try {
            return Helper::getSettings();
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

}
