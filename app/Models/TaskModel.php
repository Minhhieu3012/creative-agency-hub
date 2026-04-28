<?php
class TaskModel {
    private $pdo;

    public function __construct() {
        $this->pdo = \Core\Database::getConnection();
    }

    public function getAllTasks() {
        try {
            $stmt = $this->pdo->query("SELECT id, title, description, status, priority, deadline, assigner_id, assignee_id, watcher_id FROM tasks ORDER BY id DESC");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Lỗi Model getAllTasks: " . $e->getMessage());
            return []; 
        }
    }

    public function getTaskById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Lỗi Model getTaskById: " . $e->getMessage());
            return false;
        }
    }

    public function createTask($title, $description, $priority, $deadline, $assignee_id = null, $watcher_id = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO tasks (title, description, priority, deadline, status, assignee_id, watcher_id) VALUES (?, ?, ?, ?, 'To do', ?, ?)");
            $stmt->execute([$title, $description, $priority, $deadline, $assignee_id, $watcher_id]);
            // Trả về ID để dùng cho các logic khác nếu cần
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Lỗi Model createTask: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $status) {
        try {
            $stmt = $this->pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (\PDOException $e) {
            error_log("Lỗi Model updateStatus: " . $e->getMessage());
            return false;
        }
    }

    // Tích hợp gánh team: Ghi thẳng vào bảng notifications của Bảo
    public function createNotification($user_id, $message) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            return $stmt->execute([$user_id, $message]);
        } catch (\PDOException $e) {
            error_log("Lỗi Model createNotification: " . $e->getMessage());
            return false;
        }
    }
}
?>