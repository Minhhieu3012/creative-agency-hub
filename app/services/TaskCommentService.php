<?php
namespace App\Services;

use PDO;
use Core\Database;
use App\Services\TaskActivityService;
use App\Enums\TaskAction;

class TaskCommentService {

    public static function getAll($taskId = null) {

        $conn = Database::getConnection();

        $sql = "
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
            WHERE tc.id = ?
            LIMIT 1
        ");

        $stmt->execute([$commentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($taskId, $userId, $data) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            INSERT INTO task_comments (task_id, user_id, comment_text)
            VALUES (?, ?, ?)
        ");

        $content = trim($data['content']);

        $stmt->execute([$taskId, $userId, $content]);

        // lấy tên user
        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        TaskActivityService::log(
            $taskId,
            $userId,
            TaskAction::COMMENT,
            "{$actor['full_name']} added comment: \"" . substr($content, 0, 50) . "\""
        );

        return [
            "id" => $conn->lastInsertId(),
            "task_id" => $taskId,
            "user_id" => $userId,
            "comment_text" => $content,
            "created_at" => date("Y-m-d H:i:s")
        ];
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

    public static function update($commentId, $userId, $data) {

        $conn = Database::getConnection();

        // lấy thêm task_id
        $stmt = $conn->prepare("
            SELECT user_id, task_id FROM task_comments WHERE id = ?
        ");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) return false;

        if ($comment['user_id'] != $userId) return false;

        $content = trim($data['content']);

        $stmt = $conn->prepare("
            UPDATE task_comments
            SET comment_text = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$content, $commentId]);

        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        TaskActivityService::log(
            $comment['task_id'],
            $userId,
            TaskAction::UPDATE,
            "{$actor['full_name']} updated a comment"
        );

        return [
            "id" => $commentId,
            "user_update" => $userId,
            "comment_text" => $content,
            "updated_at" => date("Y-m-d H:i:s")
        ];
    }

    public static function delete($commentId, $userId) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT user_id, task_id FROM task_comments WHERE id = ?
        ");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) return false;

        if ($comment['user_id'] != $userId) return false;

        $stmt = $conn->prepare("
            DELETE FROM task_comments WHERE id = ?
        ");

        $stmt->execute([$commentId]);

        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        TaskActivityService::log(
            $comment['task_id'],
            $userId,
            TaskAction::UPDATE,
            "{$actor['full_name']} deleted a comment"
        );
        return true;
    }
}