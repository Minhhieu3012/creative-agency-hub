<?php
require_once __DIR__ . '/../../core/Database.php';

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
    public static function create($taskId, $userId, $data) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            INSERT INTO task_comments (task_id, user_id, comment_text)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $taskId,
            $userId,
            trim($data['content']) 
        ]);

        return [
            "id" => $conn->lastInsertId(),
            "task_id" => $taskId,
            "user_id" => $userId,
            "comment_text" => $data['content'],
            "created_at" => date("Y-m-d H:i:s")
        ];
    }
    public static function getByTask($taskId) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT 
                tc.id,
                tc.comment_text,
                tc.created_at,
                e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
            WHERE tc.task_id = ?
            ORDER BY tc.created_at ASC
        ");

        $stmt->execute([$taskId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}