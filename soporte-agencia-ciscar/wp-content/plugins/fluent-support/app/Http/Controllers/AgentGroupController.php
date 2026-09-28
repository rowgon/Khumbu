<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Agent;
use FluentSupport\App\Models\AgentGroup;
use FluentSupport\App\Models\TagPivot;
use FluentSupport\Framework\Http\Request\Request;

class AgentGroupController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->getSafe('search', 'sanitize_text_field');

        $groups = AgentGroup::orderBy('id', 'DESC')
            ->searchBy($search)
            ->paginate();

        $groupIds = $groups->pluck('id')->toArray();

        $agentCounts = TagPivot::where('source_type', 'agent_group')
            ->whereIn('tag_id', $groupIds)
            ->selectRaw('tag_id, COUNT(*) as agents_count')
            ->groupBy('tag_id')
            ->pluck('agents_count', 'tag_id');

        foreach ($groups as $group) {
            $group->agents_count = $agentCounts[$group->id] ?? 0;
        }

        return [
            'groups' => $groups
        ];
    }

    public function get($group_id)
    {
        $group = AgentGroup::findOrFail($group_id);
        $group->agent_ids = TagPivot::where('tag_id', $group->id)
            ->where('source_type', 'agent_group')
            ->pluck('source_id')
            ->toArray();

        $agents = Agent::select(['id', 'first_name', 'last_name'])
            ->where('person_type', 'agent')
            ->get();

        foreach ($agents as $agent) {
            $agent->id = strval($agent->id);
        }

        return [
            'group'  => $group,
            'agents' => $agents
        ];
    }

    public function create(Request $request)
    {
        $this->validate($request->all(), [
            'title' => 'required|string'
        ]);

        $data = $this->sanitizeGroupPayload($request);

        $settings = [];
        if ($data['is_default']) {
            $this->clearDefaultGroup();
            $settings['is_default'] = true;
        }

        $group = AgentGroup::create([
            'title'       => $data['title'],
            'description' => $data['description'],
            'settings'    => $settings,
        ]);

        if (!empty($data['agent_ids'])) {
            $this->syncAgents($group, $data['agent_ids']);
        }

        return [
            'message' => __('Agent group has been created', 'fluent-support'),
            'group'   => $group
        ];
    }

    public function update(Request $request, $group_id)
    {
        $this->validate($request->all(), [
            'title' => 'required|string'
        ]);

        $data = $this->sanitizeGroupPayload($request);
        $group = AgentGroup::findOrFail($group_id);

        $settings = $group->settings ?: [];
        if ($data['is_default']) {
            $this->clearDefaultGroup($group->id);
            $settings['is_default'] = true;
        } else {
            $settings['is_default'] = false;
        }

        $group->title = $data['title'];
        $group->description = $data['description'];
        $group->settings = $settings;
        $group->save();

        if ($request->has('agent_ids')) {
            $this->syncAgents($group, $data['agent_ids']);
        }

        return [
            'message' => __('Agent group has been updated', 'fluent-support'),
            'group'   => $group
        ];
    }

    public function delete(Request $request, $group_id)
    {
        $group = AgentGroup::findOrFail($group_id);

        $fallbackGroupId = intval($request->get('fallback_group_id'));

        if (!$fallbackGroupId || $fallbackGroupId === intval($group_id)) {
            return $this->sendError([
                'message' => __('A valid fallback group is required', 'fluent-support')
            ], 422);
        }

        $fallbackGroup = AgentGroup::find($fallbackGroupId);

        if (!$fallbackGroup) {
            return $this->sendError([
                'message' => __('The selected fallback group does not exist', 'fluent-support')
            ], 422);
        }

        // Get agent IDs from the group being deleted
        $agentIds = TagPivot::where('tag_id', $group_id)
            ->where('source_type', 'agent_group')
            ->pluck('source_id')
            ->toArray();

        // Get agent IDs already in the fallback group
        $existingAgentIds = TagPivot::where('tag_id', $fallbackGroupId)
            ->where('source_type', 'agent_group')
            ->pluck('source_id')
            ->toArray();

        // Move agents that aren't already in the fallback group
        $newAgentIds = array_diff($agentIds, $existingAgentIds);

        foreach ($newAgentIds as $agentId) {
            TagPivot::create([
                'tag_id'      => $fallbackGroupId,
                'source_id'   => $agentId,
                'source_type' => 'agent_group',
            ]);
        }

        // Delete old pivot rows and the group
        TagPivot::where('tag_id', $group_id)
            ->where('source_type', 'agent_group')
            ->delete();

        $group->delete();

        return [
            'message' => __('Agent group has been deleted and agents have been reassigned', 'fluent-support')
        ];
    }

    private function sanitizeGroupPayload(Request $request)
    {
        return [
            'title'       => $request->getSafe('title', 'sanitize_text_field'),
            'description' => $request->getSafe('description', 'sanitize_textarea_field'),
            'is_default'  => rest_sanitize_boolean($request->get('is_default', false)),
            'agent_ids'   => is_array($request->get('agent_ids')) ? array_map('intval', $request->get('agent_ids')) : [],
        ];
    }

    private function syncAgents($group, $agentIds)
    {
        TagPivot::where('tag_id', $group->id)
            ->where('source_type', 'agent_group')
            ->delete();

        $agentIds = array_map('intval', array_filter((array) $agentIds));

        foreach ($agentIds as $agentId) {
            TagPivot::create([
                'tag_id'      => $group->id,
                'source_id'   => $agentId,
                'source_type' => 'agent_group',
            ]);
        }
    }

    private function clearDefaultGroup($exceptGroupId = null)
    {
        $groups = AgentGroup::whereNotNull('settings')->get()->filter(function ($group) use ($exceptGroupId) {
            if ($exceptGroupId && $group->id == $exceptGroupId) {
                return false;
            }
            return !empty($group->settings['is_default']);
        });

        foreach ($groups as $group) {
            $settings = $group->settings;
            $settings['is_default'] = false;
            $group->settings = $settings;
            $group->save();
        }
    }
}
