<?php

namespace FluentSupport\App\Http\Controllers;

use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Services\FluentBoardsService;
use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Http\Request\Request;

class FluentBoardsController extends Controller
{
    public function getBoards()
    {
        if (!function_exists('FluentBoardsApi')) {
            return $this->sendError([
                'message' => __('Fluent Boards plugin is not installed or activated.', 'fluent-support')
            ], 400);
        }

        $boards = FluentBoardsApi('boards')->getBoards();
        $formattedBoards = [];

        foreach ($boards as $board) {
            $formattedBoards[] = [
                'id'    => $board->id,
                'title' => $board->title,
                'tasks' => [],
            ];
        }

        return ['boards' => $formattedBoards];
    }

    public function getStages(Request $request)
    {
        if (!function_exists('FluentBoardsApi')) {
            return $this->sendError([
                'message' => __('Fluent Boards plugin is not installed or activated.', 'fluent-support')
            ], 400);
        }

        $boardId = $request->getSafe('board_id', 'intval');
        $boardStages = FluentBoardsApi('boards')->getStagesByBoard($boardId);

        $formattedStages = [];
        if (!empty($boardStages)) {
            foreach ($boardStages[0]->stages as $stage) {
                $formattedStages[] = [
                    'id'    => $stage->id,
                    'title' => $stage->title,
                ];
            }
        }

        return ['stages' => $formattedStages];
    }

    public function createTask(Request $request, FluentBoardsService $fluentBoardsService)
    {
        if (!function_exists('FluentBoardsApi')) {
            return $this->sendError([
                'message' => __('Fluent Boards plugin is not installed or activated.', 'fluent-support')
            ], 400);
        }

        try {
            $sourceId = $request->getSafe('source_id', 'intval');

            $ticket = Ticket::findOrFail($sourceId);

            $this->ensureCanAccessTicket($ticket);

            $taskData = [
                'source_id'      => $sourceId,
                'board_id'       => $request->getSafe('board_id', 'intval'),
                'stage_id'       => $request->getSafe('stage_id', 'intval'),
                'crm_contact_id' => $request->getSafe('crm_contact_id', 'intval') ?: null,
                'title'          => $request->getSafe('title', 'sanitize_text_field'),
                'description'    => $request->getSafe('description', 'wp_kses_post'),
                'source'         => $request->getSafe('source', 'sanitize_text_field'),
                'started_at'     => $request->getSafe('started_at', 'sanitize_text_field'),
                'due_at'         => $request->getSafe('due_at', 'sanitize_text_field'),
                'labels'         => [],
                'assignees'      => [],
            ];

            $task = FluentBoardsApi('tasks')->create($taskData);

            if (!$task) {
                return $this->sendError(__('Failed to create task.', 'fluent-support'));
            }

            $fluentBoardsService->addInternalNote($task);
            $fluentBoardsService->addComment($task);

            return [
                'message' => __('Task successfully added to Fluent Boards', 'fluent-support'),
                'task'    => $task
            ];
        } catch (\Exception $e) {
            return $this->sendError([
                'message' => Helper::getSafeErrorMessage($e)
            ]);
        }
    }
}
