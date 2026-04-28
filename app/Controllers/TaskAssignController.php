<?php
namespace App\Controllers;
use App\Middleware\AuthMiddleware;
use App\Services\TaskAssignService;
use Exception;
use App\Controllers\BaseController;
Class TaskAssignController extends BaseController{
    public function assign($taskId) {

        try {
            $authUser = AuthMiddleware::check();

            $data = json_decode(file_get_contents("php://input"), true);

            if (!isset($data['assignee_id'])) {
                return $this->error("Missing assignee_id");
            }

            $result = TaskAssignService::assign(
                $taskId,
                $authUser['id'],
                $data['assignee_id']
            );

            return $this->success($result, "Task assigned");

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}