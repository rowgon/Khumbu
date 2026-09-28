<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Modules\StatModule;
use FluentSupport\App\Services\AvatarUploder;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Http\Requests\AgentCreateRequest;

/**
 *  AgentController class for REST API
 * This class is responsible for getting data for all request related to agent
 *
 * @package FluentSupport\App\Http\Controllers
 *
 * @version 1.0.0
 */
class AgentController extends Controller
{
    public function index(Request $request, Agent $agent)
    {
        return [
            'agents'        => $agent->getAgents($request->getSafe('search', 'sanitize_text_field')),
            'permissions'   => PermissionManager::getReadablePermissionGroups(),
            'businessBoxes' => PermissionManager::getMailboxesForRestriction(),
        ];
    }

    /**
     * addAgent method will add new agent in person table
     * @param AgentCreateRequest $request
     * @return \WP_REST_Response | array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function addAgent(AgentCreateRequest $request, Agent $agent)
    {
        $data = $this->sanitizeAgentPayload($request, true);

        try {
            return [
                'message' => __('Support Staff has been added', 'fluent-support'),
                'agent'   => $agent->createAgent($data)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * updateAgent method will update the information of an exiting agent
     * @param AgentCreateRequest $request
     * @param Agent $agent
     * @param $agent_id
     * @return \WP_REST_Response | array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function updateAgent(AgentCreateRequest $request, Agent $agent, $agent_id)
    {
        $agent = $agent::findOrFail($agent_id);
        $data = $this->sanitizeAgentPayload($request);

        if (!$agent->user_id && ($user = get_user_by('email', $agent->email))) {
            $agent->user_id = $user->ID;
        }

        try {
            return [
                'message' => __('Support Staff has been updated', 'fluent-support'),
                'agent'   => $agent->updateAgent($data, $agent)
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * deleteAgent will delete an exiting agent and add an alternative agent as replacement
     * @param Request $request
     * @param Agent $agent
     * @param $agent_id
     * @return \WP_REST_Response | array
     * @throws \FluentSupport\Framework\Validator\ValidationException
     */
    public function deleteAgent(Request $request, Agent $agent, $agent_id)
    {
        try {
            $agent->deleteAgent($request->getSafe('fallback_agent_id', 'intval'), $agent_id);

            return [
                'message' => __('Support Staff has been deleted', 'fluent-support')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * @param Request $request
     * @return \WP_REST_Response | array
     */
    public function myStats(Request $request)
    {
        // Get logged in agent information.
        $agent = Helper::getAgentByUserId();

        try {
            // Get ticket statistics.
            $stats = StatModule::getAgentStat($agent->id);
            $with = $request->get('with', []);
            $with = is_array($with) ? map_deep($with, 'sanitize_text_field') : [];

            $response = (new Agent())->getAgentStat($stats, $with, $agent->id);

            if (defined('FLUENTSUPPORTPRO')) {
                $response['dashboard_notice'] = apply_filters('fluent_support/dashboard_notice', '', $agent);
            }

            return $response;
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function agentPerformance()
    {
        try {
            if (PermissionManager::currentUserCan('fst_agent_today_performance')) {
                $agentTodayStats = StatModule::getAgentTodayStats();

                return ['agent_today_stats' => $agentTodayStats];
            }
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }

        return [];
    }

    /**
     * addOrUpdateProfileImage method will upload profile picture for a given agent id
     * For a successful upload it's required to send file object, agent id and the user type(agent)
     * @param Request $request
     * @param AvatarUploder $avatarUploder
     * @return \WP_REST_Response | array
     */
    public function addOrUpdateProfileImage(Request $request, AvatarUploder $avatarUploder)
    {
        try {
            return $avatarUploder->addOrUpdateProfileImage($request->files(), $request->getSafe('agent_id', 'intval'), 'agent');
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    /**
     * resetAvatar method will restore a Support Staff avatar
     * For a successful upload it's required to send file object, Support Staff id and the user type(Support Staff)
     * @param Agent $agent
     * @param $agent_id
     * @return array
     */
    public function resetAvatar(Agent $agent)
    {
        try {
            $agent->restoreAvatar();

            return [
                'message' => __('Support Staff avatar reset to gravatar default', 'fluent-support')
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }

    public function getAgentInsights(Request $request, Agent $agent)
    {
        return [
            'agents' => $agent->agentInsights($request->getSafe('search', 'sanitize_text_field')),
        ];
    }

    public function ping(Request $request)
    {
        return [
            'ping'          => 'pong',
        ];
    }

    /**
     * Sanitize payload data for create/update operations.
     *
     * @param AgentCreateRequest $request
     * @param bool $includeEmail
     * @return array
     */
    private function sanitizeAgentPayload(AgentCreateRequest $request, $includeEmail = false)
    {
        $data = [
            'first_name'       => $request->getSafe('first_name', 'sanitize_text_field'),
            'last_name'        => $request->getSafe('last_name', 'sanitize_text_field'),
            'title'            => $request->getSafe('title', 'sanitize_text_field'),
            'permissions'      => is_array($request->get('permissions')) ? array_map('sanitize_key', $request->get('permissions')) : [],
            'telegram_chat_id' => $request->getSafe('telegram_chat_id', 'sanitize_text_field'),
            'slack_user_id'    => $request->getSafe('slack_user_id', 'sanitize_text_field'),
            'whatsapp_number'  => $request->getSafe('whatsapp_number', 'sanitize_text_field'),
            'restrictions'     => $this->sanitizeRestrictions($request->get('restrictions')),
        ];

        if ($request->has('agent_signature')) {
            $data['agent_signature'] = wp_kses_post(wp_unslash($request->get('agent_signature', '')));
        }

        if ($request->has('agent_signature_enabled')) {
            $data['agent_signature_enabled'] = $request->get('agent_signature_enabled') === 'yes' ? 'yes' : 'no';
        }

        if ($includeEmail) {
            $data['email'] = $request->getSafe('email', 'sanitize_email');
        }

        return $data;
    }

    /**
     * Sanitize restrictions data for agent
     *
     * @param mixed $restrictions
     * @return array
     */
    private function sanitizeRestrictions($restrictions)
    {
        if (!is_array($restrictions)) {
            return [
                'restrictedBusinessBoxes' => [],
                'businessBoxRestrictions' => false,
            ];
        }

        $restrictedBusinessBoxes = isset($restrictions['restrictedBusinessBoxes']) && is_array($restrictions['restrictedBusinessBoxes'])
            ? array_map('absint', $restrictions['restrictedBusinessBoxes'])
            : [];

        $businessBoxRestrictions = isset($restrictions['businessBoxRestrictions'])
            ? rest_sanitize_boolean($restrictions['businessBoxRestrictions'])
            : false;

        return [
            'restrictedBusinessBoxes' => $restrictedBusinessBoxes,
            'businessBoxRestrictions' => $businessBoxRestrictions,
        ];
    }
}
