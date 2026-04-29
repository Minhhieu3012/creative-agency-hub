<?php
namespace App\Services;

use Core\Database;
use Exception;
use App\Services\TaskActivityService;
use App\Enums\TaskAction;

class TaskAssignService {

    public static function assign($taskId, $assignerId, $assigneeId = null, $watcherId = null) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$assignerId]);
        $assigner = $stmt->fetch();

        if (!$assigner || !in_array($assigner['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        $stmt = $conn->prepare("
            SELECT id, title, status, assignee_id, watcher_id 
            FROM tasks 
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) throw new Exception("Task not found");

        if ($task['status'] === 'Done') {
            throw new Exception("Cannot modify completed task");
        }

        if ($task['status'] === 'Review') {
            throw new Exception("Cannot modify task in review");
        }

        $title = $task['title'];
        $assignee = $task['assignee_id'];
        // ONLY WATCHER
        if (!$assigneeId && $watcherId) {

            $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
            $stmt->execute([$watcherId]);
            if (!$stmt->fetch()) {
                throw new Exception("Watcher not found");
            }

            $oldWatcherId = $task['watcher_id'];

            $stmt = $conn->prepare("
                UPDATE tasks SET watcher_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$watcherId, $taskId]);

            TaskActivityService::log(
                $taskId,
                $assignerId,
                TaskAction::ASSIGN,
                "Added watcher to task \"$title\""
            );

            // notify watcher cũ nếu bị thay
            if ($oldWatcherId && $oldWatcherId != $watcherId) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, message)
                    VALUES (?, ?)
                ");
                $stmt->execute([
                    $oldWatcherId,
                    "Bạn không còn theo dõi task \"$title\""
                ]);
            }

            // notify watcher mới
            if ($watcherId) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, message)
                    VALUES (?, ?)
                ");
                $stmt->execute([
                    $watcherId,
                    "Bạn được thêm làm watcher cho task \"$title\""
                ]);
            }

            return [
                "task_id" => $taskId,
                "watcher_id" => $watcherId
            ];
        }

        // ASSIGN / REASSIGN

        if (!$assigneeId) {
            throw new Exception("Assignee is required");
        }

        $isReassign = false;

        if ($task['status'] === 'Doing') {
            if ($task['assignee_id'] == $assigneeId) {
                throw new Exception("Task already assigned for this employee");
            }
            $isReassign = true;
        }

        $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$assigneeId]);
        if (!$stmt->fetch()) {
            throw new Exception("Assignee not found");
        }

        if ($watcherId) {
            $stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
            $stmt->execute([$watcherId]);
            if (!$stmt->fetch()) {
                throw new Exception("Watcher not found");
            }
        }

        if ($isReassign && $task['assignee_id']) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $task['assignee_id'],
                "Task \"$title\" đã được chuyển cho người khác"
            ]);
        }

        $oldWatcherId = $task['watcher_id'];
        $watcherId = $watcherId ?? $oldWatcherId;

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

        TaskActivityService::log(
            $taskId,
            $assignerId,
            $isReassign ? TaskAction::REASSIGN : TaskAction::ASSIGN,
            $isReassign
                ? "Reassigned task \"$title\""
                : "Assigned task \"$title\""
        );

        // notify assignee mới
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message)
            VALUES (?, ?)
        ");
        $stmt->execute([
            $assigneeId,
            $isReassign
                ? "Bạn được giao lại task \"$title\""
                : "Bạn được giao task \"$title\""
        ]);

        // =========================
        // FIX WATCHER
        // =========================

        // 1. watcher bị thay → notify remove
        if ($oldWatcherId && $oldWatcherId != $watcherId) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $oldWatcherId,
                "Bạn không còn theo dõi task \"$title\""
            ]);
        }

        // 2. watcher mới (hoặc lần đầu có watcher)
        if ($watcherId && $watcherId != $assigneeId && $watcherId != $oldWatcherId) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $watcherId,
                $isReassign
                    ? "Task \"$title\" vừa được reassign"
                    : "Task \"$title\" vừa được assign"
            ]);
        }

        // 3. 🔥 FIX QUAN TRỌNG: watcher KHÔNG đổi nhưng task bị reassign
        if ($isReassign && $watcherId && $watcherId == $oldWatcherId) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $watcherId,
                "Task \"$title\" vừa được bàn giao lại cho Employee \"$assignee\""
            ]);
        }

        return [
            "task_id" => $taskId,
            "assigned_to" => $assigneeId,
            "watcher_id" => $watcherId
        ];
    }
}