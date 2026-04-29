<?php
namespace App\Services;

use Core\Database;
use Exception;
use App\Enums\TaskAction;


class TaskActivityService {

    
     public static function log($taskId, $userId, $action, $description = null) {

        $conn = Database::getConnection();

        // 1. validate action ENUM
        if (!in_array($action, TaskAction::all())) {
            throw new Exception("Invalid action type");
        }

        // 2. validate task tồn tại
        $stmt = $conn->prepare("SELECT id FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);

        if (!$stmt->fetch()) {
            throw new Exception("Task not found");
        }

        // 3. validate user tồn tại
        $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$userId]);

        if (!$stmt->fetch()) {
            throw new Exception("User not found");
        }

        // 4. insert log
        $stmt = $conn->prepare("
            INSERT INTO task_activity_logs (task_id, user_id, action, description)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $taskId,
            $userId,
            $action,
            trim($description)
        ]);
    }

    //  get history
    public static function getByTask($taskId, $userId) {

        $conn = Database::getConnection();

        // 1. check task
        $stmt = $conn->prepare("
            SELECT id, assignee_id, assigner_id, watcher_id
            FROM tasks WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) {
            throw new Exception("Task not found");
        }

        // 2. check quyền (assignee, assigner, watcher, admin, manager)
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (
            $task['assignee_id'] != $userId &&
            $task['assigner_id'] != $userId &&
            $task['watcher_id'] != $userId &&
            !in_array($user['role'], ['admin', 'manager'])
        ) {
            throw new Exception("Permission denied");
        }

        // 3. lấy log
        $stmt = $conn->prepare("
            SELECT 
                tal.id,
                tal.action,
                tal.description,
                tal.created_at,
                e.full_name
            FROM task_activity_logs tal
            JOIN employees e ON tal.user_id = e.id
            WHERE tal.task_id = ?
            ORDER BY tal.created_at DESC
        ");

        $stmt->execute([$taskId]);

        return $stmt->fetchAll();
    }
}