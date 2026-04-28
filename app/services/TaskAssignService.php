<?php
namespace App\Services;
use Core\Database;
use Exception;
Class TaskAssignService{
    public static function assign($taskId, $assignerId, $assigneeId) {

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
        $isReassign = false;

        if ($task['status'] === 'Doing') {

            if ($task['assignee_id'] == $assigneeId) {
                throw new Exception("Task already assigned to this user");
            }

            $isReassign = true;
        }

        // 3. check assignee tồn tại
        $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$assigneeId]);
        if (!$stmt->fetch()) {
            throw new Exception("Assignee not found");
        }

        // 4. nếu re-assign thì notify người cũ TRƯỚC khi update
        if ($isReassign && $task['assignee_id']) {

            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $task['assignee_id'], // người cũ
                "Task \"{$task['title']}\" đã được chuyển cho người khác"
            ]);
        }

        // 5. update task
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET assignee_id = ?, assigner_id = ?, status = 'Doing'
            WHERE id = ?
        ");
        $stmt->execute([$assigneeId, $assignerId, $taskId]);

        // // 5. lưu lịch sử assign
        // $stmt = $conn->prepare("
        //     INSERT INTO task_assignments (task_id, assigner_id, assignee_id)
        //     VALUES (?, ?, ?)
        // ");
        // $stmt->execute([$taskId, $assignerId, $assigneeId]);

        // 6. tạo notification
        $title = $task['title'];

        if ($isReassign) {
            $message = "Bạn được giao lại task: \"$title\"";
        } else {
            $message = "Bạn được giao task: \"$title\"";
        }

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message)
            VALUES (?, ?)
        ");
        $stmt->execute([$assigneeId, $message]);

        return [
            "task_id" => $taskId,
            "assigned_to" => $assigneeId,
            "message" => $message
        ];
    }
}