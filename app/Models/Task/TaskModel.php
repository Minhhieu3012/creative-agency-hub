<?php
namespace App\Models\Task;

class TaskModel {
    private \PDO $pdo;

    public function __construct() {
        $this->pdo = \Core\Database::getConnection();
    }

    public function getAllTasks($filters = []) {
        try {
            $sql = "
                SELECT
                    t.id,
                    t.title,
                    t.description,
                    t.status,
                    t.priority,
                    t.deadline,
                    t.project_id,
                    t.assigner_id,
                    t.assignee_id,
                    t.watcher_id,
                    p.name AS project_name,
                    p.manager_id AS project_manager_id,
                    assigner.full_name AS assigner_name,
                    assignee.full_name AS assignee_name,
                    watcher.full_name AS watcher_name,
                    t.created_at,
                    t.updated_at
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN employees assigner ON t.assigner_id = assigner.id
                LEFT JOIN employees assignee ON t.assignee_id = assignee.id
                LEFT JOIN employees watcher ON t.watcher_id = watcher.id
                WHERE 1=1
            ";

            $params = [];

            if (!empty($filters['project_id'])) {
                $sql .= " AND t.project_id = ?";
                $params[] = $filters['project_id'];
            }

            if (!empty($filters['assignee_id'])) {
                $sql .= " AND t.assignee_id = ?";
                $params[] = $filters['assignee_id'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['deadline'])) {
                $sql .= " AND t.deadline <= ?";
                $params[] = $filters['deadline'];
            }

            if (!empty($filters['manager_id'])) {
                $sql .= "
                    AND (
                        p.manager_id = ?
                        OR t.assigner_id = ?
                    )
                ";
                $params[] = $filters['manager_id'];
                $params[] = $filters['manager_id'];
            }

            if (!empty($filters['user_id']) && ($filters['role'] ?? '') === 'employee') {
                $sql .= "
                    AND (
                        t.assignee_id = ?
                        OR t.assigner_id = ?
                        OR t.watcher_id = ?
                    )
                ";
                $params[] = $filters['user_id'];
                $params[] = $filters['user_id'];
                $params[] = $filters['user_id'];
            }

            $sql .= " ORDER BY t.id DESC";

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
            $stmt = $this->pdo->prepare("
                SELECT
                    t.id,
                    t.title,
                    t.description,
                    t.status,
                    t.priority,
                    t.deadline,
                    t.project_id,
                    t.assigner_id,
                    t.assignee_id,
                    t.watcher_id,
                    p.name AS project_name,
                    p.manager_id AS project_manager_id,
                    assigner.full_name AS assigner_name,
                    assignee.full_name AS assignee_name,
                    watcher.full_name AS watcher_name,
                    t.created_at,
                    t.updated_at
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN employees assigner ON t.assigner_id = assigner.id
                LEFT JOIN employees assignee ON t.assignee_id = assignee.id
                LEFT JOIN employees watcher ON t.watcher_id = watcher.id
                WHERE t.id = ?
            ");

            $stmt->execute([$id]);

            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Lỗi Model getTaskById: " . $e->getMessage());
            return false;
        }
    }

    public function createTask(
        $title,
        $description,
        $priority,
        $deadline,
        $assigner_id,
        $assignee_id = null,
        $watcher_id = null,
        $project_id = null
    ) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tasks (
                    title,
                    description,
                    priority,
                    deadline,
                    status,
                    assigner_id,
                    assignee_id,
                    watcher_id,
                    project_id
                )
                VALUES (?, ?, ?, ?, 'To do', ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $description,
                $priority,
                $deadline,
                $assigner_id,
                $assignee_id,
                $watcher_id,
                $project_id
            ]);

            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Lỗi Model createTask: " . $e->getMessage());
            return false;
        }
    }

    public function updateTask(
        $id,
        $title,
        $description,
        $priority,
        $deadline,
        $assignee_id = null,
        $watcher_id = null,
        $project_id = null
    ) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE tasks
                SET
                    title = ?,
                    description = ?,
                    priority = ?,
                    deadline = ?,
                    assignee_id = ?,
                    watcher_id = ?,
                    project_id = ?
                WHERE id = ?
            ");

            return $stmt->execute([
                $title,
                $description,
                $priority,
                $deadline,
                $assignee_id,
                $watcher_id,
                $project_id,
                $id
            ]);
        } catch (\PDOException $e) {
            error_log("Lỗi Model updateTask: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE tasks
                SET status = ?
                WHERE id = ?
            ");

            return $stmt->execute([$status, $id]);
        } catch (\PDOException $e) {
            error_log("Lỗi Model updateStatus: " . $e->getMessage());
            return false;
        }
    }

    public function createNotification($user_id, $message) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, message)
                VALUES (?, ?)
            ");

            return $stmt->execute([$user_id, $message]);
        } catch (\PDOException $e) {
            error_log("Lỗi Model createNotification: " . $e->getMessage());
            return false;
        }
    }

    public function isManagerOfProject($taskId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT
                t.assigner_id,
                p.manager_id
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            WHERE t.id = ?
        ");

        $stmt->execute([$taskId]);
        $project = $stmt->fetch();

        if (!$project) {
            return false;
        }

        return (int) $project['manager_id'] === (int) $userId
            || (int) $project['assigner_id'] === (int) $userId;
    }

    public function isManagerOfProjectByProjectId($projectId, $userId) {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM projects
            WHERE id = ? AND manager_id = ?
        ");

        $stmt->execute([$projectId, $userId]);

        return $stmt->fetch() ? true : false;
    }

    public function getFirstProjectManagedBy($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, name, manager_id, status
                FROM projects
                WHERE manager_id = ?
                ORDER BY id ASC
                LIMIT 1
            ");

            $stmt->execute([$userId]);

            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log("Lỗi Model getFirstProjectManagedBy: " . $e->getMessage());
            return false;
        }
    }
}