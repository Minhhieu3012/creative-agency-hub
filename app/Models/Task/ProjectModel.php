<?php
namespace App\Models\Task;

use Core\Database;
use PDO;

class ProjectModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function progressByStatus(?string $status): int {
        $status = strtolower(trim((string)$status));

        if ($status === 'done') {
            return 100;
        }

        if ($status === 'review') {
            return 82;
        }

        if ($status === 'doing') {
            return 55;
        }

        if ($status === 'pending approval') {
            return 0;
        }

        return 10;
    }

    private function enrichProject(array $project): array {
        $projectId = (int)$project['id'];

        $stmt = $this->db->prepare("
            SELECT
                COUNT(t.id) AS task_count,
                SUM(CASE WHEN t.status <> 'Done' THEN 1 ELSE 0 END) AS open_task_count,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_task_count,
                MIN(CASE WHEN t.status <> 'Done' THEN t.deadline ELSE NULL END) AS nearest_deadline,
                SUM(
                    CASE 
                        WHEN t.status <> 'Done'
                         AND t.deadline IS NOT NULL
                         AND t.deadline < CURDATE()
                        THEN 1 ELSE 0
                    END
                ) AS overdue_task_count,
                COUNT(DISTINCT t.assignee_id) AS assignee_count
            FROM tasks t
            WHERE t.project_id = :project_id
        ");

        $stmt->execute([':project_id' => $projectId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $progressStmt = $this->db->prepare("
            SELECT status
            FROM tasks
            WHERE project_id = :project_id
        ");

        $progressStmt->execute([':project_id' => $projectId]);
        $statuses = $progressStmt->fetchAll(PDO::FETCH_COLUMN);

        $progress = 0;

        if (!empty($statuses)) {
            $sum = 0;

            foreach ($statuses as $status) {
                $sum += $this->progressByStatus($status);
            }

            $progress = (int)round($sum / count($statuses));
        }

        $project['tasks'] = (int)($stats['task_count'] ?? 0);
        $project['open_tasks'] = (int)($stats['open_task_count'] ?? 0);
        $project['done_tasks'] = (int)($stats['done_task_count'] ?? 0);
        $project['members'] = max(1, (int)($stats['assignee_count'] ?? 0));
        $project['progress'] = $progress;
        $project['deadline'] = $stats['nearest_deadline'] ?? null;
        $project['risk_tasks'] = (int)($stats['overdue_task_count'] ?? 0);
        $project['is_virtual'] = false;

        return $project;
    }

    private function getUnassignedProjectCard(?int $managerId = null): ?array {
        $where = "t.project_id IS NULL";
        $params = [];

        if ($managerId) {
            $where .= " AND (t.assigner_id = :manager_id OR t.assignee_id = :manager_id OR t.watcher_id = :manager_id)";
            $params[':manager_id'] = $managerId;
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(t.id) AS task_count,
                SUM(CASE WHEN t.status <> 'Done' THEN 1 ELSE 0 END) AS open_task_count,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_task_count,
                MIN(CASE WHEN t.status <> 'Done' THEN t.deadline ELSE NULL END) AS nearest_deadline,
                SUM(
                    CASE 
                        WHEN t.status <> 'Done'
                         AND t.deadline IS NOT NULL
                         AND t.deadline < CURDATE()
                        THEN 1 ELSE 0
                    END
                ) AS overdue_task_count,
                COUNT(DISTINCT t.assignee_id) AS assignee_count
            FROM tasks t
            WHERE {$where}
        ");

        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ((int)($stats['task_count'] ?? 0) === 0) {
            return null;
        }

        $progressStmt = $this->db->prepare("
            SELECT t.status
            FROM tasks t
            WHERE {$where}
        ");

        $progressStmt->execute($params);
        $statuses = $progressStmt->fetchAll(PDO::FETCH_COLUMN);

        $progress = 0;

        if (!empty($statuses)) {
            $sum = 0;

            foreach ($statuses as $status) {
                $sum += $this->progressByStatus($status);
            }

            $progress = (int)round($sum / count($statuses));
        }

        return [
            'id' => '__unassigned__',
            'name' => 'Công việc chưa gán dự án',
            'description' => 'Nhóm tạm các task chưa có project_id. Khi tạo project thật và gán task vào project, nhóm này sẽ tự giảm.',
            'manager_id' => null,
            'client_id' => null,
            'status' => 'Active',
            'created_at' => null,
            'updated_at' => null,
            'manager_name' => 'Chưa gán quản lý',
            'tasks' => (int)($stats['task_count'] ?? 0),
            'open_tasks' => (int)($stats['open_task_count'] ?? 0),
            'done_tasks' => (int)($stats['done_task_count'] ?? 0),
            'members' => max(1, (int)($stats['assignee_count'] ?? 0)),
            'progress' => $progress,
            'deadline' => $stats['nearest_deadline'] ?? null,
            'risk_tasks' => (int)($stats['overdue_task_count'] ?? 0),
            'is_virtual' => true,
        ];
    }

    public function getByManager($managerId) {
        $stmt = $this->db->prepare("
            SELECT p.*, e.full_name AS manager_name
            FROM projects p
            LEFT JOIN employees e ON p.manager_id = e.id
            WHERE p.manager_id = ?
            ORDER BY p.created_at DESC
        ");

        $stmt->execute([$managerId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $projects = array_map(function ($project) {
            return $this->enrichProject($project);
        }, $projects);

        $unassigned = $this->getUnassignedProjectCard((int)$managerId);

        if ($unassigned) {
            $projects[] = $unassigned;
        }

        return $projects;
    }

    public function getAll() {
        $stmt = $this->db->query("
            SELECT p.*, e.full_name AS manager_name
            FROM projects p
            LEFT JOIN employees e ON p.manager_id = e.id
            ORDER BY p.created_at DESC
        ");

        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $projects = array_map(function ($project) {
            return $this->enrichProject($project);
        }, $projects);

        $unassigned = $this->getUnassignedProjectCard();

        if ($unassigned) {
            $projects[] = $unassigned;
        }

        return $projects;
    }

    public function findById($id) {
        if ((string)$id === '__unassigned__') {
            return $this->getUnassignedProjectCard();
        }

        $stmt = $this->db->prepare("
            SELECT p.*, e.full_name AS manager_name
            FROM projects p
            LEFT JOIN employees e ON p.manager_id = e.id
            WHERE p.id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ? $this->enrichProject($project) : false;
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO projects (name, description, manager_id, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['manager_id'],
            $data['status']
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE projects
            SET name = ?, description = ?, manager_id = ?, status = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['manager_id'],
            $data['status'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function existsManager($id) {
        $stmt = $this->db->prepare("
            SELECT id
            FROM employees
            WHERE id = ?
              AND deleted_at IS NULL
              AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute([$id]);
        return $stmt->fetch() ? true : false;
    }
}