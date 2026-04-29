<?php
namespace App\Services;

use PDO;
use Core\Database;
use App\Services\TaskActivityService;
use App\Services\NotificationService;
use App\Enums\TaskAction;
use Exception;

class TaskCommentService {

    public static function getAll($taskId = null) {
        $conn = Database::getConnection();

        $sql = "
            SELECT tc.*, e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
        ";

        if ($taskId) {
            $sql .= " WHERE tc.task_id = ?";
        }

        $sql .= " ORDER BY tc.created_at DESC";

        $stmt = $conn->prepare($sql);
        $taskId ? $stmt->execute([$taskId]) : $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($commentId) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT tc.*, e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
            WHERE tc.id = ?
        ");

        $stmt->execute([$commentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public static function getByTask($taskId) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT 
                tc.id,
                tc.task_id,
                tc.comment_text,
                tc.created_at,
                tc.updated_at,
                tc.user_id,
                e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
            WHERE tc.task_id = ?
            ORDER BY tc.created_at ASC
        ");

        $stmt->execute([$taskId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($taskId, $userId, $data) {
        $conn = Database::getConnection();

        $content = trim($data['content']);

        $stmt = $conn->prepare("
            INSERT INTO task_comments (task_id, user_id, comment_text)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$taskId, $userId, $content]);

        // actor
        $stmt = $conn->prepare("SELECT full_name, role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        // activity log
        TaskActivityService::log(
            $taskId,
            $userId,
            TaskAction::COMMENT,
            "{$actor['full_name']} added comment"
        );

        // task info
        $stmt = $conn->prepare("
            SELECT assigner_id, assignee_id, watcher_id 
            FROM tasks WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        $userIds = array_unique(array_filter([
            $task['assigner_id'],
            $task['assignee_id'],
            $task['watcher_id']
        ]));

        $userIds = array_filter($userIds, fn($id) => $id != $userId);

        $who = in_array($actor['role'], ['admin', 'manager'])
            ? "Manager {$actor['full_name']}"
            : $actor['full_name'];

        NotificationService::sendToMany(
            $userIds,
            "$who đã comment trong task"
        );

        return [
            "id" => $conn->lastInsertId(),
            "task_id" => $taskId,
            "user_id" => $userId,
            "comment_text" => $content
        ];
    }

    public static function update($commentId, $userId, $data) {
        $conn = Database::getConnection();

        // lấy comment
        $stmt = $conn->prepare("
            SELECT user_id, task_id FROM task_comments WHERE id = ?
        ");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            throw new Exception("Comment not found");
        }

        // lấy role
        $stmt = $conn->prepare("SELECT role, full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // check quyền
        if (
            $comment['user_id'] != $userId &&
            !in_array($user['role'], ['admin', 'manager'])
        ) {
            throw new Exception("Permission denied");
        }

        $content = trim($data['content']);

        $stmt = $conn->prepare("
            UPDATE task_comments
            SET comment_text = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$content, $commentId]);

        // activity log
        TaskActivityService::log(
            $comment['task_id'],
            $userId,
            TaskAction::UPDATE,
            "{$user['full_name']} updated a comment"
        );

        // task info
        $stmt = $conn->prepare("
            SELECT assigner_id, assignee_id, watcher_id 
            FROM tasks WHERE id = ?
        ");
        $stmt->execute([$comment['task_id']]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        $who = in_array($user['role'], ['admin', 'manager'])
            ? "manager {$user['full_name']}"
            : $user['full_name'];

        // notify owner
        NotificationService::send(
            $comment['user_id'],
            "Comment của bạn đã được cập nhật bởi $who"
        );

        // notify others
        $userIds = array_unique(array_filter([
            $task['assigner_id'],
            $task['assignee_id'],
            $task['watcher_id']
        ]));

        $userIds = array_filter($userIds, fn($id) =>
            $id != $userId && $id != $comment['user_id']
        );

        NotificationService::sendToMany(
            $userIds,
            "Comment của task đã được cập nhật bởi $who"
        );

        return [
            "id" => $commentId,
            "comment_text" => $content
        ];
    }

    public static function delete($commentId, $userId) {
        $conn = Database::getConnection();

        // lấy comment
        $stmt = $conn->prepare("
            SELECT user_id, task_id FROM task_comments WHERE id = ?
        ");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            throw new Exception("Comment not found");
        }

        // lấy user (actor)
        $stmt = $conn->prepare("SELECT role, full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // check quyền
        if (
            $comment['user_id'] != $userId &&
            !in_array($user['role'], ['admin', 'manager'])
        ) {
            throw new Exception("Permission denied");
        }

        // xoá comment
        $stmt = $conn->prepare("
            DELETE FROM task_comments WHERE id = ?
        ");
        $stmt->execute([$commentId]);

        // activity log
        TaskActivityService::log(
            $comment['task_id'],
            $userId,
            TaskAction::DELETE,
            "{$user['full_name']} đã xóa 1 comment"
        );

        // lấy task info
        $stmt = $conn->prepare("
            SELECT assigner_id, assignee_id, watcher_id 
            FROM tasks WHERE id = ?
        ");
        $stmt->execute([$comment['task_id']]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        // xác định người thực hiện
        $who = in_array($user['role'], ['admin', 'manager'])
            ? "manager {$user['full_name']}"
            : $user['full_name'];

        // 1. notify owner comment
        NotificationService::send(
            $comment['user_id'],
            "Comment của bạn đã bị xoá bởi $who"
        );

        // 2. notify assignee + assigner + watcher
        $userIds = array_unique(array_filter([
            $task['assigner_id'],
            $task['assignee_id'],
            $task['watcher_id']
        ]));

        // loại actor + owner (tránh spam)
        $userIds = array_filter($userIds, function ($id) use ($userId, $comment) {
            return $id != $userId && $id != $comment['user_id'];
        });

        NotificationService::sendToMany(
            $userIds,
            "Một comment trong task đã bị xoá bởi $who"
        );

        return true;
    }
}