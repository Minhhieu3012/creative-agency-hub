<?php
namespace App\Controllers;

use Core\Database;
use Core\JwtHandler;
use PDO;
use Throwable;

class DashboardController {
    private PDO $db;
    private JwtHandler $jwt;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->jwt = new JwtHandler();
    }

    private function json(array $payload, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getAuthorizationHeader(): string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return trim((string)(
            $headers['Authorization']
            ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? ''
        ));
    }

    private function extractBearerToken(string $authHeader): string {
        if ($authHeader === '') {
            return '';
        }

        if (stripos($authHeader, 'Bearer ') === 0) {
            return trim(substr($authHeader, 7));
        }

        return trim($authHeader);
    }

    private function normalizeAuthPayload($payload): ?array {
        if (!$payload) {
            return null;
        }

        if (is_object($payload)) {
            $payload = (array)$payload;
        }

        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['data']) && (is_array($payload['data']) || is_object($payload['data']))) {
            $payload = (array)$payload['data'];
        }

        if (empty($payload['id'])) {
            return null;
        }

        return [
            'id' => (int)$payload['id'],
            'email' => $payload['email'] ?? null,
            'role' => strtolower((string)($payload['role'] ?? 'employee')),
            'full_name' => $payload['full_name'] ?? ($payload['name'] ?? null),
        ];
    }

    private function getAuthUser(): array {
        $token = '';

        $authHeader = $this->getAuthorizationHeader();

        if ($authHeader !== '') {
            $token = $this->extractBearerToken($authHeader);
        }

        if ($token === '' && !empty($_COOKIE['cah_token'])) {
            $token = (string)$_COOKIE['cah_token'];
        }

        if ($token !== '') {
            try {
                $decoded = $this->jwt->decode($token);
                $authUser = $this->normalizeAuthPayload($decoded);

                if ($authUser && !empty($authUser['id'])) {
                    return $authUser;
                }
            } catch (Throwable $e) {
                // Fallback xuống session.
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            return [
                'id' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? null,
                'role' => strtolower((string)($_SESSION['user_role'] ?? 'employee')),
                'full_name' => $_SESSION['full_name'] ?? null,
            ];
        }

        $this->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }

    private function fetchValue(string $sql, array $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    private function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function formatDate(?string $date): string {
        if (!$date) {
            return 'Chưa đặt';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return 'Chưa đặt';
        }

        return date('d/m/Y', $timestamp);
    }

    private function timeAgo(?string $dateTime): string {
        if (!$dateTime) {
            return 'Không rõ';
        }

        $timestamp = strtotime($dateTime);

        if (!$timestamp) {
            return 'Không rõ';
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Vừa xong';
        }

        if ($diff < 3600) {
            return floor($diff / 60) . ' phút trước';
        }

        if ($diff < 86400) {
            return floor($diff / 3600) . ' giờ trước';
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private function initials(string $name): string {
        $name = trim($name);

        if ($name === '') {
            return 'U';
        }

        $parts = preg_split('/\s+/u', $name);
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        $firstInitial = function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1);
        $lastInitial = function_exists('mb_substr') ? mb_substr($last, 0, 1, 'UTF-8') : substr($last, 0, 1);

        $initials = $firstInitial . ($lastInitial ?: '');

        return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
    }

    private function getMembersByProject(int $projectId): array {
        $rows = $this->fetchAll("
            SELECT DISTINCT e.full_name
            FROM tasks t
            INNER JOIN employees e ON e.id = t.assignee_id
            WHERE t.project_id = :project_id
              AND e.deleted_at IS NULL
            ORDER BY e.full_name ASC
            LIMIT 3
        ", [
            ':project_id' => $projectId,
        ]);

        $members = [];

        foreach ($rows as $row) {
            $members[] = $this->initials((string)($row['full_name'] ?? ''));
        }

        if (count($members) === 0) {
            $members[] = 'CA';
        }

        return $members;
    }

    private function progressFromTasks(int $totalTasks, int $doneTasks): int {
        if ($totalTasks <= 0) {
            return 0;
        }

        return (int)round(($doneTasks / $totalTasks) * 100);
    }

    private function buildResourceData(array $authUser): array {
        $role = $authUser['role'];
        $userId = (int)$authUser['id'];

        if ($role === 'employee') {
            $params = [':user_id' => $userId];

            $todo = (int)$this->fetchValue("SELECT COUNT(*) FROM tasks WHERE assignee_id = :user_id AND status = 'To do'", $params);
            $doing = (int)$this->fetchValue("SELECT COUNT(*) FROM tasks WHERE assignee_id = :user_id AND status = 'Doing'", $params);
            $review = (int)$this->fetchValue("SELECT COUNT(*) FROM tasks WHERE assignee_id = :user_id AND status = 'Review'", $params);
            $done = (int)$this->fetchValue("SELECT COUNT(*) FROM tasks WHERE assignee_id = :user_id AND status = 'Done'", $params);

            $max = max(1, $todo, $doing, $review, $done);

            return [
                ['label' => 'To do', 'value' => (int)round(($todo / $max) * 100)],
                ['label' => 'Doing', 'value' => (int)round(($doing / $max) * 100)],
                ['label' => 'Review', 'value' => (int)round(($review / $max) * 100)],
                ['label' => 'Done', 'value' => (int)round(($done / $max) * 100)],
            ];
        }

        $rows = $this->fetchAll("
            SELECT d.name AS label, COUNT(e.id) AS total
            FROM departments d
            LEFT JOIN employees e
                ON e.department_id = d.id
               AND e.status = 'active'
               AND e.deleted_at IS NULL
            WHERE d.deleted_at IS NULL
            GROUP BY d.id, d.name
            ORDER BY total DESC, d.name ASC
            LIMIT 4
        ");

        if (empty($rows)) {
            return [
                ['label' => 'HRM', 'value' => 0],
                ['label' => 'Task', 'value' => 0],
                ['label' => 'Client', 'value' => 0],
                ['label' => 'System', 'value' => 0],
            ];
        }

        $max = max(1, ...array_map(fn($row) => (int)$row['total'], $rows));

        return array_map(function ($row) use ($max) {
            return [
                'label' => $row['label'] ?: 'Khác',
                'value' => (int)round(((int)$row['total'] / $max) * 100),
            ];
        }, $rows);
    }

    private function buildProjectList(array $authUser): array {
        $role = $authUser['role'];
        $userId = (int)$authUser['id'];

        if ($role === 'employee') {
            $rows = $this->fetchAll("
                SELECT
                    p.id,
                    p.name,
                    MIN(t.deadline) AS nearest_deadline,
                    COUNT(t.id) AS total_tasks,
                    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE t.assignee_id = :user_id
                  AND p.status = 'Active'
                GROUP BY p.id, p.name
                ORDER BY nearest_deadline IS NULL ASC, nearest_deadline ASC
                LIMIT 4
            ", [
                ':user_id' => $userId,
            ]);
        } elseif ($role === 'manager') {
            $rows = $this->fetchAll("
                SELECT
                    p.id,
                    p.name,
                    MIN(t.deadline) AS nearest_deadline,
                    COUNT(t.id) AS total_tasks,
                    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks
                FROM projects p
                LEFT JOIN tasks t ON t.project_id = p.id
                WHERE p.status = 'Active'
                  AND p.manager_id = :user_id
                GROUP BY p.id, p.name
                ORDER BY nearest_deadline IS NULL ASC, nearest_deadline ASC, p.created_at DESC
                LIMIT 4
            ", [
                ':user_id' => $userId,
            ]);
        } elseif ($role === 'client') {
            $rows = $this->fetchAll("
                SELECT
                    p.id,
                    p.name,
                    MIN(t.deadline) AS nearest_deadline,
                    COUNT(t.id) AS total_tasks,
                    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks
                FROM projects p
                LEFT JOIN tasks t ON t.project_id = p.id
                WHERE p.status = 'Active'
                  AND p.client_id = :user_id
                GROUP BY p.id, p.name
                ORDER BY nearest_deadline IS NULL ASC, nearest_deadline ASC, p.created_at DESC
                LIMIT 4
            ", [
                ':user_id' => $userId,
            ]);
        } else {
            $rows = $this->fetchAll("
                SELECT
                    p.id,
                    p.name,
                    MIN(t.deadline) AS nearest_deadline,
                    COUNT(t.id) AS total_tasks,
                    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks
                FROM projects p
                LEFT JOIN tasks t ON t.project_id = p.id
                WHERE p.status = 'Active'
                GROUP BY p.id, p.name
                ORDER BY nearest_deadline IS NULL ASC, nearest_deadline ASC, p.created_at DESC
                LIMIT 4
            ");
        }

        $projects = [];

        foreach ($rows as $row) {
            $totalTasks = (int)($row['total_tasks'] ?? 0);
            $doneTasks = (int)($row['done_tasks'] ?? 0);
            $progress = $this->progressFromTasks($totalTasks, $doneTasks);
            $deadline = $row['nearest_deadline'] ?? null;

            $tone = 'primary';

            if ($deadline && $deadline < date('Y-m-d') && $progress < 100) {
                $tone = 'danger';
            } elseif ($progress < 50) {
                $tone = 'warning';
            }

            $projects[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'deadline' => $this->formatDate($deadline),
                'progress' => $progress,
                'tasks' => "{$doneTasks}/{$totalTasks} Tasks",
                'tone' => $tone,
                'members' => $this->getMembersByProject((int)$row['id']),
            ];
        }

        return $projects;
    }

    private function buildActivities(array $authUser): array {
        $role = $authUser['role'];
        $userId = (int)$authUser['id'];
        $params = [];
        $where = '';

        if ($role === 'employee') {
            $where = "WHERE t.assignee_id = :user_id OR al.user_id = :user_id";
            $params[':user_id'] = $userId;
        } elseif ($role === 'manager') {
            $where = "WHERE p.manager_id = :user_id OR al.user_id = :user_id";
            $params[':user_id'] = $userId;
        } elseif ($role === 'client') {
            $where = "WHERE p.client_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $rows = $this->fetchAll("
            SELECT
                al.action,
                al.description,
                al.created_at,
                e.full_name AS user_name,
                t.title AS task_title
            FROM task_activity_logs al
            LEFT JOIN employees e ON al.user_id = e.id
            LEFT JOIN tasks t ON al.task_id = t.id
            LEFT JOIN projects p ON t.project_id = p.id
            {$where}
            ORDER BY al.created_at DESC
            LIMIT 4
        ", $params);

        $activities = [];

        foreach ($rows as $row) {
            $icon = '❖';
            $tone = 'secondary';
            $title = 'Cập nhật hệ thống';

            switch ($row['action']) {
                case 'create':
                    $icon = '+';
                    $tone = 'primary';
                    $title = 'Tạo công việc mới';
                    break;

                case 'assign':
                case 'reassign':
                    $icon = '👤';
                    $tone = 'info';
                    $title = 'Phân công nhân sự';
                    break;

                case 'status_change':
                    $icon = '✓';
                    $tone = 'success';
                    $title = 'Cập nhật trạng thái';
                    break;

                case 'upload':
                    $icon = '⇧';
                    $tone = 'info';
                    $title = 'Tải tệp báo cáo';
                    break;

                case 'comment':
                    $icon = '✎';
                    $tone = 'primary';
                    $title = 'Bình luận mới';
                    break;
            }

            $taskName = htmlspecialchars((string)($row['task_title'] ?: 'Công việc đã xoá'), ENT_QUOTES, 'UTF-8');
            $userName = htmlspecialchars((string)($row['user_name'] ?: 'Hệ thống'), ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars((string)($row['description'] ?? ''), ENT_QUOTES, 'UTF-8');

            $descriptionHtml = "{$taskName} - <strong>{$userName}</strong>";

            if ($description !== '') {
                $descriptionHtml .= "<br><small class=\"text-muted\">{$description}</small>";
            }

            $activities[] = [
                'icon' => $icon,
                'tone' => $tone,
                'title' => $title,
                'description' => strip_tags($descriptionHtml),
                'description_html' => $descriptionHtml,
                'time' => $this->timeAgo($row['created_at'] ?? null),
            ];
        }

        return $activities;
    }

    private function buildStats(array $authUser): array {
        $role = $authUser['role'];
        $userId = (int)$authUser['id'];

        if ($role === 'employee') {
            $activeProjects = (int)$this->fetchValue("
                SELECT COUNT(DISTINCT t.project_id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE t.assignee_id = :user_id
                  AND p.status = 'Active'
            ", [
                ':user_id' => $userId,
            ]);

            $totalTasks = (int)$this->fetchValue("
                SELECT COUNT(*)
                FROM tasks
                WHERE assignee_id = :user_id
            ", [
                ':user_id' => $userId,
            ]);

            $doneTasks = (int)$this->fetchValue("
                SELECT COUNT(*)
                FROM tasks
                WHERE assignee_id = :user_id
                  AND status = 'Done'
            ", [
                ':user_id' => $userId,
            ]);

            $overdueTasks = (int)$this->fetchValue("
                SELECT COUNT(*)
                FROM tasks
                WHERE assignee_id = :user_id
                  AND deadline < CURDATE()
                  AND status <> 'Done'
            ", [
                ':user_id' => $userId,
            ]);

            return [
                'active_projects' => $activeProjects,
                'total_employees' => $totalTasks,
                'avg_progress' => $this->progressFromTasks($totalTasks, $doneTasks),
                'overdue_tasks' => $overdueTasks,
            ];
        }

        if ($role === 'manager') {
            $activeProjects = (int)$this->fetchValue("
                SELECT COUNT(*)
                FROM projects
                WHERE status = 'Active'
                  AND manager_id = :user_id
            ", [
                ':user_id' => $userId,
            ]);

            $totalEmployees = (int)$this->fetchValue("
                SELECT COUNT(DISTINCT t.assignee_id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.manager_id = :user_id
                  AND t.assignee_id IS NOT NULL
            ", [
                ':user_id' => $userId,
            ]);

            $totalTasks = (int)$this->fetchValue("
                SELECT COUNT(t.id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.manager_id = :user_id
            ", [
                ':user_id' => $userId,
            ]);

            $doneTasks = (int)$this->fetchValue("
                SELECT COUNT(t.id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.manager_id = :user_id
                  AND t.status = 'Done'
            ", [
                ':user_id' => $userId,
            ]);

            $overdueTasks = (int)$this->fetchValue("
                SELECT COUNT(t.id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.manager_id = :user_id
                  AND t.deadline < CURDATE()
                  AND t.status <> 'Done'
            ", [
                ':user_id' => $userId,
            ]);

            return [
                'active_projects' => $activeProjects,
                'total_employees' => $totalEmployees,
                'avg_progress' => $this->progressFromTasks($totalTasks, $doneTasks),
                'overdue_tasks' => $overdueTasks,
            ];
        }

        if ($role === 'client') {
            $activeProjects = (int)$this->fetchValue("
                SELECT COUNT(*)
                FROM projects
                WHERE status = 'Active'
                  AND client_id = :user_id
            ", [
                ':user_id' => $userId,
            ]);

            $totalTasks = (int)$this->fetchValue("
                SELECT COUNT(t.id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.client_id = :user_id
            ", [
                ':user_id' => $userId,
            ]);

            $doneTasks = (int)$this->fetchValue("
                SELECT COUNT(t.id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.client_id = :user_id
                  AND t.status = 'Done'
            ", [
                ':user_id' => $userId,
            ]);

            $overdueTasks = (int)$this->fetchValue("
                SELECT COUNT(t.id)
                FROM tasks t
                INNER JOIN projects p ON p.id = t.project_id
                WHERE p.client_id = :user_id
                  AND t.deadline < CURDATE()
                  AND t.status <> 'Done'
            ", [
                ':user_id' => $userId,
            ]);

            return [
                'active_projects' => $activeProjects,
                'total_employees' => $totalTasks,
                'avg_progress' => $this->progressFromTasks($totalTasks, $doneTasks),
                'overdue_tasks' => $overdueTasks,
            ];
        }

        $activeProjects = (int)$this->fetchValue("
            SELECT COUNT(*)
            FROM projects
            WHERE status = 'Active'
        ");

        $activeAccounts = (int)$this->fetchValue("
            SELECT COUNT(*)
            FROM employees
            WHERE status = 'active'
              AND deleted_at IS NULL
        ");

        $totalTasks = (int)$this->fetchValue("
            SELECT COUNT(*)
            FROM tasks
        ");

        $doneTasks = (int)$this->fetchValue("
            SELECT COUNT(*)
            FROM tasks
            WHERE status = 'Done'
        ");

        $overdueTasks = (int)$this->fetchValue("
            SELECT COUNT(*)
            FROM tasks
            WHERE deadline < CURDATE()
              AND status <> 'Done'
        ");

        return [
            'active_projects' => $activeProjects,
            'total_employees' => $activeAccounts,
            'avg_progress' => $this->progressFromTasks($totalTasks, $doneTasks),
            'overdue_tasks' => $overdueTasks,
        ];
    }

    public function getStats(): void {
        try {
            $authUser = $this->getAuthUser();

            $stats = $this->buildStats($authUser);
            $projects = $this->buildProjectList($authUser);
            $activities = $this->buildActivities($authUser);
            $resources = $this->buildResourceData($authUser);

            $this->json([
                'status' => 'success',
                'data' => [
                    'role' => $authUser['role'],
                    'active_projects' => (int)$stats['active_projects'],
                    'total_employees' => (int)$stats['total_employees'],
                    'avg_progress' => (int)$stats['avg_progress'],
                    'overdue_tasks' => (int)$stats['overdue_tasks'],
                    'projects' => $projects,
                    'activities' => $activities,
                    'resources' => $resources,
                ],
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }
}