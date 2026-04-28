<?php
class TaskModel {
    private $pdo;

    public function __construct() {
        $this->pdo = \Core\Database::getConnection();
    }

    public function getAllTasks($filters = []) {
        try {
            $sql = "SELECT id, title, description, status, priority, deadline, project_id, assigner_id, assignee_id, watcher_id FROM tasks WHERE 1=1";
            $params = [];

            if (!empty($filters['project_id'])) {
                $sql .= " AND project_id = ?";
                $params[] = $filters['project_id'];
            }
            if (!empty($filters['assignee_id'])) {
                $sql .= " AND assignee_id = ?";
                $params[] = $filters['assignee_id'];
            }
            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            if (!empty($filters['deadline'])) {
                $sql .= " AND deadline <= ?";
                $params[] = $filters['deadline'];
            }

            $sql .= " ORDER BY id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
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

    public function createTask($title, $description, $priority, $deadline, $assigner_id, $assignee_id = null, $watcher_id = null, $project_id = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO tasks (title, description, priority, deadline, status, assigner_id, assignee_id, watcher_id, project_id) VALUES (?, ?, ?, ?, 'To do', ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $priority, $deadline, $assigner_id, $assignee_id, $watcher_id, $project_id]);
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Lỗi Model createTask: " . $e->getMessage());
            return false;
        }
    }

    public function updateTask($id, $title, $description, $priority, $deadline, $assignee_id = null, $watcher_id = null, $project_id = null) {
        try {
            $stmt = $this->pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, deadline = ?, assignee_id = ?, watcher_id = ?, project_id = ? WHERE id = ?");
            return $stmt->execute([$title, $description, $priority, $deadline, $assignee_id, $watcher_id, $project_id, $id]);
        } catch (\PDOException $e) {
            error_log("Lỗi Model updateTask: " . $e->getMessage());
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