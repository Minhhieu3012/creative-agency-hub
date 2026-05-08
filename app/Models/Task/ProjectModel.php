<?php
namespace App\Models\Task;

use Core\Database;
use PDO;
use RuntimeException;

class ProjectModel {
    private PDO $db;
    private string $table = 'projects';

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ");

        $stmt->execute([
            ':table_name' => $table,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function normalizeStatus(?string $status): string {
        $status = trim((string)$status);

        $allowed = ['Active', 'Completed', 'Archived'];

        return in_array($status, $allowed, true) ? $status : 'Active';
    }

    private function normalizeId($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
    }

    private function normalizeMemberIds($memberIds): array {
        if (!is_array($memberIds)) {
            return [];
        }

        $ids = [];

        foreach ($memberIds as $id) {
            $id = (int)$id;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function getAvailableClients(): array {
        $stmt = $this->db->prepare("
            SELECT
                id,
                full_name,
                email,
                avatar,
                status
            FROM employees
            WHERE role = 'client'
              AND status = 'active'
              AND deleted_at IS NULL
            ORDER BY full_name ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableEmployees(): array {
        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.full_name,
                e.email,
                e.avatar,
                e.department_id,
                e.position_id,
                d.name AS department_name,
                p.name AS position_name
            FROM employees e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            WHERE e.role = 'employee'
              AND e.status = 'active'
              AND e.deleted_at IS NULL
            ORDER BY e.full_name ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function all(array $filters = [], ?array $authUser = null): array {
        $params = [];
        $where = [];

        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . trim((string)$filters['search']) . '%';
        }

        if (!empty($filters['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $this->normalizeStatus($filters['status']);
        } else {
            $where[] = "p.status <> 'Archived'";
        }

        if ($role === 'manager') {
            $where[] = "p.manager_id = :auth_manager_id";
            $params[':auth_manager_id'] = $userId;
        }

        if ($role === 'client') {
            $where[] = "p.client_id = :auth_client_id";
            $params[':auth_client_id'] = $userId;
        }

        if ($role === 'employee') {
            if ($this->tableExists('project_members')) {
                $where[] = "(
                    EXISTS (
                        SELECT 1
                        FROM project_members pm
                        WHERE pm.project_id = p.id
                          AND pm.employee_id = :auth_employee_id
                          AND pm.status = 'active'
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM tasks t_emp
                        WHERE t_emp.project_id = p.id
                          AND t_emp.assignee_id = :auth_employee_id_task
                    )
                )";

                $params[':auth_employee_id'] = $userId;
                $params[':auth_employee_id_task'] = $userId;
            } else {
                $where[] = "EXISTS (
                    SELECT 1
                    FROM tasks t_emp
                    WHERE t_emp.project_id = p.id
                      AND t_emp.assignee_id = :auth_employee_id_task
                )";

                $params[':auth_employee_id_task'] = $userId;
            }
        }

        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT
                p.id,
                p.name,
                p.description,
                p.manager_id,
                p.client_id,
                p.status,
                p.created_at,
                p.updated_at,

                manager.full_name AS manager_name,
                manager.email AS manager_email,

                client.full_name AS client_name,
                client.email AS client_email,

                COUNT(DISTINCT t.id) AS total_tasks,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks,
                SUM(CASE WHEN t.deadline < CURDATE() AND t.status <> 'Done' THEN 1 ELSE 0 END) AS overdue_tasks,
                MIN(CASE WHEN t.status <> 'Done' THEN t.deadline ELSE NULL END) AS nearest_deadline
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            LEFT JOIN employees client ON client.id = p.client_id
            LEFT JOIN tasks t ON t.project_id = p.id
            {$whereSql}
            GROUP BY
                p.id,
                p.name,
                p.description,
                p.manager_id,
                p.client_id,
                p.status,
                p.created_at,
                p.updated_at,
                manager.full_name,
                manager.email,
                client.full_name,
                client.email
            ORDER BY p.created_at DESC, p.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($projects as &$project) {
            $project = $this->decorateProject($project);
        }

        unset($project);

        return $projects;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.name,
                p.description,
                p.manager_id,
                p.client_id,
                p.status,
                p.created_at,
                p.updated_at,

                manager.full_name AS manager_name,
                manager.email AS manager_email,

                client.full_name AS client_name,
                client.email AS client_email,

                COUNT(DISTINCT t.id) AS total_tasks,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks,
                SUM(CASE WHEN t.deadline < CURDATE() AND t.status <> 'Done' THEN 1 ELSE 0 END) AS overdue_tasks,
                MIN(CASE WHEN t.status <> 'Done' THEN t.deadline ELSE NULL END) AS nearest_deadline
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            LEFT JOIN employees client ON client.id = p.client_id
            LEFT JOIN tasks t ON t.project_id = p.id
            WHERE p.id = :id
            GROUP BY
                p.id,
                p.name,
                p.description,
                p.manager_id,
                p.client_id,
                p.status,
                p.created_at,
                p.updated_at,
                manager.full_name,
                manager.email,
                client.full_name,
                client.email
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            return null;
        }

        $project = $this->decorateProject($project);
        $project['members'] = $this->getMembers($id);

        return $project;
    }

    private function decorateProject(array $project): array {
        $totalTasks = (int)($project['total_tasks'] ?? 0);
        $doneTasks = (int)($project['done_tasks'] ?? 0);
        $overdueTasks = (int)($project['overdue_tasks'] ?? 0);

        $project['total_tasks'] = $totalTasks;
        $project['done_tasks'] = $doneTasks;
        $project['overdue_tasks'] = $overdueTasks;
        $project['progress'] = $totalTasks > 0 ? (int)round(($doneTasks / $totalTasks) * 100) : 0;
        $project['nearest_deadline'] = $project['nearest_deadline'] ?? null;

        return $project;
    }

    public function create(array $data, int $managerId): int {
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $clientId = $this->normalizeId($data['client_id'] ?? null);
        $status = $this->normalizeStatus($data['status'] ?? 'Active');

        if ($name === '') {
            throw new RuntimeException('Tên dự án không được để trống.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO projects
            (
                name,
                description,
                manager_id,
                client_id,
                status
            )
            VALUES
            (
                :name,
                :description,
                :manager_id,
                :client_id,
                :status
            )
        ");

        $stmt->execute([
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':manager_id' => $managerId,
            ':client_id' => $clientId,
            ':status' => $status,
        ]);

        $projectId = (int)$this->db->lastInsertId();

        $memberIds = $this->normalizeMemberIds($data['member_ids'] ?? []);
        $this->syncMembers($projectId, $memberIds, $managerId);

        return $projectId;
    }

    public function update(int $id, array $data, ?int $managerId = null): bool {
        $project = $this->findById($id);

        if (!$project) {
            throw new RuntimeException('Không tìm thấy dự án.');
        }

        if ($managerId !== null && (int)$project['manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được cập nhật dự án do mình quản lý.');
        }

        $name = array_key_exists('name', $data)
            ? trim((string)$data['name'])
            : (string)$project['name'];

        if ($name === '') {
            throw new RuntimeException('Tên dự án không được để trống.');
        }

        $description = array_key_exists('description', $data)
            ? trim((string)$data['description'])
            : ($project['description'] ?? null);

        $clientId = array_key_exists('client_id', $data)
            ? $this->normalizeId($data['client_id'])
            : $this->normalizeId($project['client_id'] ?? null);

        $status = array_key_exists('status', $data)
            ? $this->normalizeStatus($data['status'])
            : $this->normalizeStatus($project['status'] ?? 'Active');

        $stmt = $this->db->prepare("
            UPDATE projects
            SET
                name = :name,
                description = :description,
                client_id = :client_id,
                status = :status
            WHERE id = :id
        ");

        $result = $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':description' => $description !== '' ? $description : null,
            ':client_id' => $clientId,
            ':status' => $status,
        ]);

        if (array_key_exists('member_ids', $data)) {
            $memberIds = $this->normalizeMemberIds($data['member_ids']);
            $this->syncMembers($id, $memberIds, $managerId);
        }

        return $result;
    }

    public function archive(int $id, ?int $managerId = null): bool {
        $project = $this->findById($id);

        if (!$project) {
            throw new RuntimeException('Không tìm thấy dự án.');
        }

        if ($managerId !== null && (int)$project['manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được lưu trữ dự án do mình quản lý.');
        }

        $stmt = $this->db->prepare("
            UPDATE projects
            SET status = 'Archived'
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    public function getMembers(int $projectId): array {
        if (!$this->tableExists('project_members')) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                pm.id,
                pm.project_id,
                pm.employee_id,
                pm.added_by,
                pm.role_in_project,
                pm.status,
                pm.joined_at,
                pm.left_at,

                e.full_name,
                e.email,
                e.avatar,
                e.department_id,
                e.position_id,

                d.name AS department_name,
                p.name AS position_name
            FROM project_members pm
            INNER JOIN employees e ON e.id = pm.employee_id
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            WHERE pm.project_id = :project_id
              AND pm.status = 'active'
              AND e.deleted_at IS NULL
            ORDER BY e.full_name ASC
        ");

        $stmt->execute([
            ':project_id' => $projectId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addMember(int $projectId, int $employeeId, ?int $addedBy = null, string $roleInProject = 'member'): bool {
        if (!$this->tableExists('project_members')) {
            throw new RuntimeException('Thiếu bảng project_members. Hãy chạy migration 004_operation_flow_schema.sql trước.');
        }

        $roleInProject = in_array($roleInProject, ['member', 'lead', 'reviewer'], true)
            ? $roleInProject
            : 'member';

        $stmt = $this->db->prepare("
            INSERT INTO project_members
            (
                project_id,
                employee_id,
                added_by,
                role_in_project,
                status,
                joined_at,
                left_at
            )
            VALUES
            (
                :project_id,
                :employee_id,
                :added_by,
                :role_in_project,
                'active',
                NOW(),
                NULL
            )
            ON DUPLICATE KEY UPDATE
                added_by = VALUES(added_by),
                role_in_project = VALUES(role_in_project),
                status = 'active',
                left_at = NULL,
                updated_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            ':project_id' => $projectId,
            ':employee_id' => $employeeId,
            ':added_by' => $addedBy,
            ':role_in_project' => $roleInProject,
        ]);
    }

    public function removeMember(int $projectId, int $employeeId): bool {
        if (!$this->tableExists('project_members')) {
            throw new RuntimeException('Thiếu bảng project_members. Hãy chạy migration 004_operation_flow_schema.sql trước.');
        }

        $stmt = $this->db->prepare("
            UPDATE project_members
            SET
                status = 'inactive',
                left_at = NOW()
            WHERE project_id = :project_id
              AND employee_id = :employee_id
        ");

        return $stmt->execute([
            ':project_id' => $projectId,
            ':employee_id' => $employeeId,
        ]);
    }

    public function syncMembers(int $projectId, array $memberIds, ?int $addedBy = null): void {
        if (!$this->tableExists('project_members')) {
            if (empty($memberIds)) {
                return;
            }

            throw new RuntimeException('Thiếu bảng project_members. Hãy chạy migration 004_operation_flow_schema.sql trước.');
        }

        $memberIds = $this->normalizeMemberIds($memberIds);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                UPDATE project_members
                SET
                    status = 'inactive',
                    left_at = NOW()
                WHERE project_id = :project_id
            ");

            $stmt->execute([
                ':project_id' => $projectId,
            ]);

            foreach ($memberIds as $employeeId) {
                $this->addMember($projectId, $employeeId, $addedBy, 'member');
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function isEmployeeInProject(int $projectId, int $employeeId): bool {
        if ($this->tableExists('project_members')) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM project_members
                WHERE project_id = :project_id
                  AND employee_id = :employee_id
                  AND status = 'active'
            ");

            $stmt->execute([
                ':project_id' => $projectId,
                ':employee_id' => $employeeId,
            ]);

            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM tasks
            WHERE project_id = :project_id
              AND assignee_id = :employee_id
        ");

        $stmt->execute([
            ':project_id' => $projectId,
            ':employee_id' => $employeeId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function canUserAccessProject(array $authUser, int $projectId): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        $project = $this->findById($projectId);

        if (!$project) {
            return false;
        }

        if ($role === 'manager') {
            return (int)$project['manager_id'] === $userId;
        }

        if ($role === 'client') {
            return (int)$project['client_id'] === $userId;
        }

        if ($role === 'employee') {
            return $this->isEmployeeInProject($projectId, $userId);
        }

        return false;
    }
}