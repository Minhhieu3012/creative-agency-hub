<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/TaskApprovalService.php';

class TaskApprovalController extends BaseController {

    public function submit($taskId) {

        $userId = getallheaders()['user_id'] ?? 1;

        try {
            $result = TaskApprovalService::submit($taskId, $userId);
            return $this->success($result, "Task submitted for review");
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function approve($taskId) {

        $userId = getallheaders()['user_id'] ?? 1;

        try {
            $result = TaskApprovalService::approve($taskId, $userId);
            return $this->success($result, "Task approved");
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reject($taskId) {

        $userId = getallheaders()['user_id'] ?? 1;

        try {
            $result = TaskApprovalService::reject($taskId, $userId);
            return $this->success($result, "Task rejected");
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}