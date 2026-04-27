<?php
require_once __DIR__ . '/../../core/Database.php';

class TaskApprovalService {

    public static function submit($taskId, $userId) {

        $conn = Database::getConnection();

        // check task
        $stmt = $conn->prepare("SELECT status, assignee_id FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new Exception("Task not found");
        }

        // chỉ người được assign mới submit
        if ($task['assignee_id'] != $userId) {
            throw new Exception("You are not assigned to this task");
        }

        // chỉ submit khi đang Doing
        if ($task['status'] !== 'Doing') {
            throw new Exception("Only Doing tasks can be submitted");
        }

        // update status → Review
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Review' WHERE id = ?");
        $stmt->execute([$taskId]);

        return [
            "task_id" => $taskId,
            "status" => "Review"
        ];
    }

    public static function approve($taskId, $userId) {

        $conn = Database::getConnection();

        // check user role
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!in_array($user['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        // check task
        $stmt = $conn->prepare("SELECT status FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task['status'] !== 'Review') {
            throw new Exception("Task must be in Review state");
        }

        // update → Done
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Done' WHERE id = ?");
        $stmt->execute([$taskId]);

        return [
            "task_id" => $taskId,
            "status" => "Done"
        ];
    }

    public static function reject($taskId, $userId) {

        $conn = Database::getConnection();

        // check role
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!in_array($user['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        // check task
        $stmt = $conn->prepare("SELECT status FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task['status'] !== 'Review') {
            throw new Exception("Task must be in Review state");
        }

        // update -> về lại Doing
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Doing' WHERE id = ?");
        $stmt->execute([$taskId]);

        return [
            "task_id" => $taskId,
            "status" => "Doing"
        ];
    }
}