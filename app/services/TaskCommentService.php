<?php
require_once __DIR__ . '/../../core/Database.php';

class TaskCommentService {

    public static function create($taskId, $userId, $data) {
        $headers = getallheaders();
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            INSERT INTO task_comments (task_id, user_id, comment_text)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $taskId,
            // TODO: Replace with JWT authentication later
            $userId = $headers['user_id'] ?? 3,
            // $userId = $decodedToken->id;
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