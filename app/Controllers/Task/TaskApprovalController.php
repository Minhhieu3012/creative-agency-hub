<?php
namespace App\Controllers\Task;
use Exception;
use App\Middleware\AuthMiddleware;
use App\Services\Task\TaskApprovalService;
use App\Controllers\BaseController;

class TaskApprovalController extends BaseController {

    public function submit($taskId) {

        $authUser = AuthMiddleware::check();
        $userId = $authUser['id'];


        try {
            $result = TaskApprovalService::submit($taskId, $userId);
            return $this->success($result, "Task submitted for review");
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function approve($taskId) {

        $authUser = AuthMiddleware::check();
        $userId = $authUser['id'];

        try {
            $result = TaskApprovalService::approve($taskId, $userId);
            return $this->success($result, "Task approved");
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reject($taskId) {

        $authUser = AuthMiddleware::check();
        $userId = $authUser['id'];

        try {
            $result = TaskApprovalService::reject($taskId, $userId);
            return $this->success($result, "Task rejected");
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    public function getReviewTasks() {

        try {
            // giả lập user login
            $authUser = AuthMiddleware::check();
        $userId = $authUser['id'];

            $tasks = TaskApprovalService::getTasksInReview($userId);

            return $this->success($tasks, "Review tasks list");

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}