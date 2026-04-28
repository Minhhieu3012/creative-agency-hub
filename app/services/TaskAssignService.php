<?php
namespace App\Services;
use Core\Database;
use Exception;
Class TaskAssignService{
    public static function assign($taskId, $assignerId, $assigneeId, $watcherId = null) {

        $conn = Database::getConnection();

        // 1. check role
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$assignerId]);
        $assigner = $stmt->fetch();

        if (!$assigner || !in_array($assigner['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        // 2. check task
        $stmt = $conn->prepare("
            SELECT id, title, status, assignee_id 
            FROM tasks 
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) {
            throw new Exception("Task not found");
        }

        if ($task['status'] === 'Done') {
            throw new Exception("Cannot assign completed task");
        }

        if ($task['status'] === 'Review') {
            throw new Exception("Cannot assign task while it is under review");
        }

        $isReassign = false;

        if ($task['status'] === 'Doing') {
            if ($task['assignee_id'] == $assigneeId) {
                throw new Exception("Task already assigned to this user");
            }
            $isReassign = true;
        }

        // 3. check assignee
        $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$assigneeId]);
        if (!$stmt->fetch()) {
            throw new Exception("Assignee not found");
        }

        // 4. check watcher (nếu có)
        if ($watcherId) {
            $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
            $stmt->execute([$watcherId]);
            if (!$stmt->fetch()) {
                throw new Exception("Watcher not found");
            }
        }

        // 5. notify người cũ nếu reassign
        if ($isReassign && $task['assignee_id']) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $task['assignee_id'],
                "Task \"{$task['title']}\" đã được chuyển cho người khác"
            ]);
        }

        // 6. update task (thêm watcher)
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET assignee_id = ?, assigner_id = ?, watcher_id = ?, status = 'Doing'
            WHERE id = ?
        ");
        $stmt->execute([
            $assigneeId,
            $assignerId,
            $watcherId,
            $taskId
        ]);

        $title = $task['title'];

        // 7. notify assignee
        $message = $isReassign
            ? "Bạn được giao lại task: \"$title\""
            : "Bạn được giao task: \"$title\"";

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message)
            VALUES (?, ?)
        ");
        $stmt->execute([$assigneeId, $message]);

        // 8. notify watcher (nếu có)
        if ($watcherId) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $watcherId,
                "Bạn đang theo dõi task: \"$title\""
            ]);
        }

        return [
            "task_id" => $taskId,
            "assigned_to" => $assigneeId,
            "watcher_id" => $watcherId,
            "message" => $message
        ];
    }
}