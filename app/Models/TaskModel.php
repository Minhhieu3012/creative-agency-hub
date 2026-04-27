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

    public function createTask($title, $description, $priority, $deadline, $assignee_id = null, $watcher_id = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO tasks (title, description, priority, deadline, status, assignee_id, watcher_id) VALUES (?, ?, ?, ?, 'To do', ?, ?)");
            return $stmt->execute([$title, $description, $priority, $deadline, $assignee_id, $watcher_id]);
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
}
?>