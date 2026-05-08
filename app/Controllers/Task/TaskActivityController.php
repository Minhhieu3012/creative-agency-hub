<?php
namespace App\Controllers\Task;

use App\Services\Task\TaskActivityService;
use App\Middleware\AuthMiddleware;
use Exception;

class TaskActivityController {

    public function history($taskId) {

        try {
            $authUser = AuthMiddleware::check();

            $data = TaskActivityService::getByTask(
                $taskId,
                $authUser['id']
            );

            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);

        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}