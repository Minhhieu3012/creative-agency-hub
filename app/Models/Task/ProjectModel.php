<?php
namespace App\Models\Task;

use Core\Database;
use PDO;
use PDOException;

class ProjectModel {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getProjectsForUser(array $authUser, array $filters = []): array {
        try {
            $role = strtolower((string) ($authUser['role'] ?? ''));
            $userId = (int) ($authUser['id'] ?? $authUser['employee_id'] ?? 0);

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
                    client.full_name AS client_name,
                    COUNT(DISTINCT t.id) AS task_count,
                    COUNT(DISTINCT CASE WHEN t.status = 'Done' THEN t.id END) AS done_task_count,
                    COUNT(DISTINCT t.assignee_id) AS member_count,
                    ROUND(
                        COALESCE(
                            AVG(
                                CASE
                                    WHEN t.status = 'Done' THEN 100
                                    WHEN t.status = 'Review' THEN 82
                                    WHEN t.status = 'Doing' THEN 55
                                    ELSE 10
                                END
                            ),
                            0
                        )
                    ) AS progress
                FROM projects p
                LEFT JOIN employees manager ON manager.id = p.manager_id
                LEFT JOIN employees client ON client.id = p.client_id
                LEFT JOIN tasks t ON t.project_id = p.id
                WHERE 1 = 1
            ";

            $params = [];

            if ($role === 'manager') {
                $sql .= " AND p.manager_id = :user_id";
                $params[':user_id'] = $userId;
            } elseif ($role === 'employee') {
                $sql .= "
                    AND EXISTS (
                        SELECT 1
                        FROM tasks employee_tasks
                        WHERE employee_tasks.project_id = p.id
                        AND employee_tasks.assignee_id = :user_id
                    )
                ";
                $params[':user_id'] = $userId;
            } elseif ($role === 'client') {
                $sql .= "
                    AND (
                        p.client_id = :user_id
                        OR EXISTS (
                            SELECT 1
                            FROM tasks client_tasks
                            WHERE client_tasks.project_id = p.id
                            AND client_tasks.watcher_id = :user_id
                        )
                    )
                ";
                $params[':user_id'] = $userId;
            } else {
                return [];
            }

            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $sql .= " AND p.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
                $params[':search'] = '%' . trim($filters['search']) . '%';
            }

            $sql .= "
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
                    client.full_name
                ORDER BY p.updated_at DESC, p.id DESC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("ProjectModel getProjectsForUser error: " . $e->getMessage());
            return [];
        }
    }

    public function getProjectByIdForUser(int $projectId, array $authUser): ?array {
        try {
            $role = strtolower((string) ($authUser['role'] ?? ''));
            $userId = (int) ($authUser['id'] ?? $authUser['employee_id'] ?? 0);

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
                    client.full_name AS client_name,
                    COUNT(DISTINCT t.id) AS task_count,
                    COUNT(DISTINCT CASE WHEN t.status = 'Done' THEN t.id END) AS done_task_count,
                    COUNT(DISTINCT t.assignee_id) AS member_count,
                    ROUND(
                        COALESCE(
                            AVG(
                                CASE
                                    WHEN t.status = 'Done' THEN 100
                                    WHEN t.status = 'Review' THEN 82
                                    WHEN t.status = 'Doing' THEN 55
                                    ELSE 10
                                END
                            ),
                            0
                        )
                    ) AS progress
                FROM projects p
                LEFT JOIN employees manager ON manager.id = p.manager_id
                LEFT JOIN employees client ON client.id = p.client_id
                LEFT JOIN tasks t ON t.project_id = p.id
                WHERE p.id = :project_id
            ";

            $params = [
                ':project_id' => $projectId,
            ];

            if ($role === 'manager') {
                $sql .= " AND p.manager_id = :user_id";
                $params[':user_id'] = $userId;
            } elseif ($role === 'employee') {
                $sql .= "
                    AND EXISTS (
                        SELECT 1
                        FROM tasks employee_tasks
                        WHERE employee_tasks.project_id = p.id
                        AND employee_tasks.assignee_id = :user_id
                    )
                ";
                $params[':user_id'] = $userId;
            } elseif ($role === 'client') {
                $sql .= "
                    AND (
                        p.client_id = :user_id
                        OR EXISTS (
                            SELECT 1
                            FROM tasks client_tasks
                            WHERE client_tasks.project_id = p.id
                            AND client_tasks.watcher_id = :user_id
                        )
                    )
                ";
                $params[':user_id'] = $userId;
            } else {
                return null;
            }

            $sql .= "
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
                    client.full_name
                LIMIT 1
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $project = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$project) {
                return null;
            }

            $project['tasks'] = $this->getProjectTasks($projectId, $authUser);

            return $project;
        } catch (PDOException $e) {
            error_log("ProjectModel getProjectByIdForUser error: " . $e->getMessage());
            return null;
        }
    }

    public function getProjectTasks(int $projectId, array $authUser): array {
        try {
            $role = strtolower((string) ($authUser['role'] ?? ''));
            $userId = (int) ($authUser['id'] ?? $authUser['employee_id'] ?? 0);

            $sql = "
                SELECT
                    t.id,
                    t.project_id,
                    t.title,
                    t.description,
                    t.status,
                    t.priority,
                    t.deadline,
                    t.assigner_id,
                    t.assignee_id,
                    t.watcher_id,
                    assigner.full_name AS assigner_name,
                    assignee.full_name AS assignee_name,
                    watcher.full_name AS watcher_name,
                    p.name AS project_name
                FROM tasks t
                LEFT JOIN projects p ON p.id = t.project_id
                LEFT JOIN employees assigner ON assigner.id = t.assigner_id
                LEFT JOIN employees assignee ON assignee.id = t.assignee_id
                LEFT JOIN employees watcher ON watcher.id = t.watcher_id
                WHERE t.project_id = :project_id
            ";

            $params = [
                ':project_id' => $projectId,
            ];

            if ($role === 'employee') {
                $sql .= " AND t.assignee_id = :user_id";
                $params[':user_id'] = $userId;
            }

            if ($role === 'client') {
                $sql .= " AND (t.watcher_id = :user_id OR p.client_id = :user_id)";
                $params[':user_id'] = $userId;
            }

            $sql .= " ORDER BY t.deadline IS NULL, t.deadline ASC, t.id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("ProjectModel getProjectTasks error: " . $e->getMessage());
            return [];
        }
    }

    public function createProject(array $data): int|false {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO projects (
                    name,
                    description,
                    manager_id,
                    client_id,
                    status
                )
                VALUES (
                    :name,
                    :description,
                    :manager_id,
                    :client_id,
                    :status
                )
            ");

            $stmt->execute([
                ':name' => trim((string) $data['name']),
                ':description' => trim((string) ($data['description'] ?? '')),
                ':manager_id' => (int) $data['manager_id'],
                ':client_id' => $this->nullableInt($data['client_id'] ?? null),
                ':status' => $this->normalizeStatus($data['status'] ?? 'Active'),
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("ProjectModel createProject error: " . $e->getMessage());
            return false;
        }
    }

    public function updateProject(int $projectId, int $managerId, array $data): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE projects
                SET
                    name = :name,
                    description = :description,
                    client_id = :client_id,
                    status = :status
                WHERE id = :id
                AND manager_id = :manager_id
            ");

            return $stmt->execute([
                ':id' => $projectId,
                ':manager_id' => $managerId,
                ':name' => trim((string) $data['name']),
                ':description' => trim((string) ($data['description'] ?? '')),
                ':client_id' => $this->nullableInt($data['client_id'] ?? null),
                ':status' => $this->normalizeStatus($data['status'] ?? 'Active'),
            ]);
        } catch (PDOException $e) {
            error_log("ProjectModel updateProject error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProject(int $projectId, int $managerId): bool {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM projects
                WHERE id = :id
                AND manager_id = :manager_id
            ");

            return $stmt->execute([
                ':id' => $projectId,
                ':manager_id' => $managerId,
            ]);
        } catch (PDOException $e) {
            error_log("ProjectModel deleteProject error: " . $e->getMessage());
            return false;
        }
    }

    public function isManagerProject(int $projectId, int $managerId): bool {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM projects
                WHERE id = :project_id
                AND manager_id = :manager_id
            ");

            $stmt->execute([
                ':project_id' => $projectId,
                ':manager_id' => $managerId,
            ]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("ProjectModel isManagerProject error: " . $e->getMessage());
            return false;
        }
    }

    public function getClientOptions(): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    employee_code,
                    full_name,
                    email,
                    role
                FROM employees
                WHERE role = 'client'
                AND status = 'active'
                AND deleted_at IS NULL
                ORDER BY full_name ASC
            ");

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("ProjectModel getClientOptions error: " . $e->getMessage());
            return [];
        }
    }

    public function getEmployeeOptions(): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    id,
                    employee_code,
                    full_name,
                    email,
                    role
                FROM employees
                WHERE role = 'employee'
                AND status = 'active'
                AND deleted_at IS NULL
                ORDER BY full_name ASC
            ");

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("ProjectModel getEmployeeOptions error: " . $e->getMessage());
            return [];
        }
    }

    private function nullableInt($value): ?int {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function normalizeStatus(string $status): string {
        $status = trim($status);
        $allowed = ['Active', 'Completed', 'Archived'];

        return in_array($status, $allowed, true) ? $status : 'Active';
    }
}