<?php
namespace App\Controllers\Task;

use App\Middleware\AuthMiddleware;
use App\Services\Task\TaskAssignService;
use Exception;
use App\Controllers\BaseController;

class TaskAssignController extends BaseController {

    public function assign($taskId) {

        try {
            $authUser = AuthMiddleware::check();

            $data = json_decode(file_get_contents("php://input"), true);

            $assigneeId = $data['assignee_id'] ?? null;
            $watcherId  = $data['watcher_id'] ?? null;

            if (!$assigneeId && !$watcherId) {
                return $this->error("Phải có ít nhất assignee hoặc watcher");
            }

            $result = TaskAssignService::assign(
                $taskId,
                $authUser['id'],
                $assigneeId,
                $watcherId
            );

            return $this->success($result, "Success");

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}