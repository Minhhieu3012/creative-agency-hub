<?php
namespace App\Controllers\ClientPortal;

use App\Middleware\AuthMiddleware;
use Core\Database;
use PDO;
use Throwable;

class ClientProjectController {
    private PDO $pdo;
    private array $authUser;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->authUser = AuthMiddleware::check();
        $this->ensureClient();
    }

    private function ensureClient(): void {
        $role = strtolower((string)($this->authUser['role'] ?? ''));

        if ($role !== 'client') {
            $this->json([
                'status' => 'error',
                'message' => 'Chỉ tài khoản khách hàng mới được truy cập Client Portal.'
            ], 403);
        }
    }

    private function clientId(): int {
        return (int)($this->authUser['id'] ?? $this->authUser['employee_id'] ?? 0);
    }

    private function resolveId($id): int {
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? 0;
        }

        return (int)$id;
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

        return 10;
    }

    private function projectStatusMeta(?string $projectStatus, int $progress): array {
        $status = strtolower(trim((string)$projectStatus));

        if ($status === 'completed') {
            return [
                'key' => 'completed',
                'label' => 'Hoàn thành',
                'tone' => 'success'
            ];
        }

        if ($status === 'archived') {
            return [
                'key' => 'archived',
                'label' => 'Đã lưu trữ',
                'tone' => 'info'
            ];
        }

        if ($progress >= 80) {
            return [
                'key' => 'review',
                'label' => 'Đang duyệt',
                'tone' => 'warning'
            ];
        }

        return [
            'key' => 'in_progress',
            'label' => 'Đang triển khai',
            'tone' => 'primary'
        ];
    }

    private function taskStatusMeta(?string $taskStatus): array {
        $status = trim((string)$taskStatus);
        $lower = strtolower($status);

        if ($lower === 'done') {
            return [
                'key' => 'success',
                'label' => 'Đã hoàn thành',
                'tone' => 'success'
            ];
        }

        if ($lower === 'review') {
            return [
                'key' => 'warning',
                'label' => 'Đang kiểm tra',
                'tone' => 'warning'
            ];
        }

        if ($lower === 'doing') {
            return [
                'key' => 'primary',
                'label' => 'Đang triển khai',
                'tone' => 'primary'
            ];
        }

        return [
            'key' => 'info',
            'label' => 'Cần làm',
            'tone' => 'info'
        ];
    }

    private function enrichProject(array $project): array {
        $projectId = (int)$project['id'];

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(t.id) AS task_count,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_task_count,
                SUM(CASE WHEN t.status <> 'Done' THEN 1 ELSE 0 END) AS open_task_count,
                MIN(CASE WHEN t.status <> 'Done' THEN t.deadline ELSE NULL END) AS nearest_deadline,
                SUM(
                    CASE
                        WHEN t.status <> 'Done'
                         AND t.deadline IS NOT NULL
                         AND t.deadline < CURDATE()
                        THEN 1 ELSE 0
                    END
                ) AS overdue_task_count,
                MAX(t.updated_at) AS latest_task_update
            FROM tasks t
            WHERE t.project_id = ?
        ");
        $stmt->execute([$projectId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $statusStmt = $this->pdo->prepare("
            SELECT status
            FROM tasks
            WHERE project_id = ?
        ");
        $statusStmt->execute([$projectId]);
        $statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

        $progress = 0;

        if (!empty($statuses)) {
            $sum = 0;

            foreach ($statuses as $status) {
                $sum += $this->progressByStatus($status);
            }

            $progress = (int)round($sum / count($statuses));
        }

        if (strtolower((string)$project['status']) === 'completed') {
            $progress = 100;
        }

        $statusMeta = $this->projectStatusMeta($project['status'] ?? 'Active', $progress);
        $managerName = $project['manager_name'] ?: 'Chưa gán quản lý';
        $managerInitials = $this->initials($managerName);
        $lastUpdate = $stats['latest_task_update'] ?: ($project['updated_at'] ?? $project['created_at'] ?? null);

        return [
            'id' => $projectId,
            'name' => $project['name'],
            'description' => $project['description'] ?? '',
            'raw_status' => $project['status'] ?? 'Active',
            'status' => $statusMeta['key'],
            'status_label' => $statusMeta['label'],
            'status_tone' => $statusMeta['tone'],
            'progress' => $progress,
            'tasks' => (int)($stats['task_count'] ?? 0),
            'done' => (int)($stats['done_task_count'] ?? 0),
            'open_tasks' => (int)($stats['open_task_count'] ?? 0),
            'risk_tasks' => (int)($stats['overdue_task_count'] ?? 0),
            'deadline' => $stats['nearest_deadline'] ?? null,
            'manager_id' => $project['manager_id'] ?? null,
            'manager' => $managerInitials,
            'manager_name' => $managerName,
            'manager_email' => $project['manager_email'] ?? null,
            'created_at' => $project['created_at'] ?? null,
            'updated_at' => $project['updated_at'] ?? null,
            'last_update' => $lastUpdate,
        ];
    }

    private function initials(?string $name): string {
        $name = trim((string)$name);

        if ($name === '') {
            return 'CA';
        }

        $parts = preg_split('/\s+/u', $name);
        $first = mb_substr($parts[0] ?? '', 0, 1, 'UTF-8');
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';

        return mb_strtoupper($first . $last, 'UTF-8');
    }

    private function getProjectsForClient(int $clientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                p.*,
                manager.full_name AS manager_name,
                manager.email AS manager_email
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            WHERE p.client_id = ?
            ORDER BY p.updated_at DESC, p.created_at DESC, p.id DESC
        ");
        $stmt->execute([$clientId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($project) {
            return $this->enrichProject($project);
        }, $projects);
    }

    private function getProjectForClient(int $clientId, int $projectId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                p.*,
                manager.full_name AS manager_name,
                manager.email AS manager_email
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            WHERE p.client_id = ?
              AND p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$clientId, $projectId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ? $this->enrichProject($project) : null;
    }

    private function getTasksForClientProject(int $clientId, int $projectId): array {
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
                t.created_at,
                t.updated_at,
                assigner.full_name AS assigner_name,
                assignee.full_name AS assignee_name,
                watcher.full_name AS watcher_name
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            LEFT JOIN employees assigner ON assigner.id = t.assigner_id
            LEFT JOIN employees assignee ON assignee.id = t.assignee_id
            LEFT JOIN employees watcher ON watcher.id = t.watcher_id
            WHERE p.client_id = ?
              AND p.id = ?
            ORDER BY
                FIELD(t.status, 'Review', 'Doing', 'To do', 'Done'),
                t.deadline IS NULL,
                t.deadline ASC,
                t.updated_at DESC
        ");
        $stmt->execute([$clientId, $projectId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($task) {
            $meta = $this->taskStatusMeta($task['status'] ?? 'To do');

            return [
                'id' => (int)$task['id'],
                'title' => $task['title'],
                'desc' => $task['description'] ?? '',
                'description' => $task['description'] ?? '',
                'status' => $task['status'],
                'status_label' => $meta['label'],
                'status_key' => $meta['key'],
                'tone' => $meta['tone'],
                'priority' => $task['priority'] ?? 'Medium',
                'deadline' => $task['deadline'],
                'owner' => $task['assignee_name'] ?: ($task['assigner_name'] ?: 'Chưa gán'),
                'assigner_name' => $task['assigner_name'] ?? null,
                'assignee_name' => $task['assignee_name'] ?? null,
                'watcher_name' => $task['watcher_name'] ?? null,
                'created_at' => $task['created_at'] ?? null,
                'updated_at' => $task['updated_at'] ?? null,
            ];
        }, $tasks);
    }

    private function getFeedbacksForClientProject(int $clientId, int $projectId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                tc.id,
                tc.comment_text,
                tc.created_at,
                e.full_name,
                e.role
            FROM task_comments tc
            INNER JOIN tasks t ON t.id = tc.task_id
            INNER JOIN projects p ON p.id = t.project_id
            LEFT JOIN employees e ON e.id = tc.user_id
            WHERE p.client_id = ?
              AND p.id = ?
            ORDER BY tc.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$clientId, $projectId]);
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($feedback) {
            $name = $feedback['full_name'] ?: 'Creative Agency Hub';

            return [
                'id' => (int)$feedback['id'],
                'avatar' => $this->initials($name),
                'name' => $name,
                'role' => $feedback['role'] ?? null,
                'message' => $feedback['comment_text'] ?? '',
                'time' => $feedback['created_at'] ?? null,
            ];
        }, $feedbacks);
    }

    private function getRecentUpdates(int $clientId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                t.id,
                t.title,
                t.status,
                t.updated_at,
                p.name AS project_name
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            WHERE p.client_id = ?
            ORDER BY t.updated_at DESC
            LIMIT 6
        ");
        $stmt->execute([$clientId]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($update) {
            $meta = $this->taskStatusMeta($update['status'] ?? 'To do');

            return [
                'id' => (int)$update['id'],
                'title' => $update['title'],
                'description' => ($update['project_name'] ?? 'Dự án') . ' • ' . $meta['label'],
                'status' => $update['status'],
                'tone' => $meta['tone'],
                'updated_at' => $update['updated_at'] ?? null,
            ];
        }, $updates);
    }

    private function buildSummary(array $projects, array $updates): array {
        $openProjects = array_values(array_filter($projects, function ($project) {
            return !in_array($project['status'], ['completed', 'archived'], true);
        }));

        $avgProgress = 0;

        if (!empty($projects)) {
            $avgProgress = (int)round(array_sum(array_column($projects, 'progress')) / count($projects));
        }

        return [
            'open_projects' => count($openProjects),
            'avg_progress' => $avgProgress,
            'pending_feedback' => 0,
            'last_update' => $updates[0]['updated_at'] ?? ($projects[0]['last_update'] ?? null),
            'total_projects' => count($projects),
        ];
    }

    public function index(): void {
        try {
            $clientId = $this->clientId();
            $projects = $this->getProjectsForClient($clientId);
            $updates = $this->getRecentUpdates($clientId);

            $this->json([
                'status' => 'success',
                'data' => [
                    'summary' => $this->buildSummary($projects, $updates),
                    'projects' => $projects,
                    'updates' => $updates,
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): void {
        try {
            $projectId = $this->resolveId($id);
            $clientId = $this->clientId();
            $project = $this->getProjectForClient($clientId, $projectId);

            if (!$project) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Dự án không tồn tại hoặc không được chia sẻ cho tài khoản này.'
                ], 404);
            }

            $tasks = $this->getTasksForClientProject($clientId, $projectId);
            $feedbacks = $this->getFeedbacksForClientProject($clientId, $projectId);

            $this->json([
                'status' => 'success',
                'data' => [
                    'project' => $project,
                    'tasks' => $tasks,
                    'feedbacks' => $feedbacks,
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tasks($id): void {
        try {
            $projectId = $this->resolveId($id);
            $clientId = $this->clientId();
            $project = $this->getProjectForClient($clientId, $projectId);

            if (!$project) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Dự án không tồn tại hoặc không được chia sẻ cho tài khoản này.'
                ], 404);
            }

            $this->json([
                'status' => 'success',
                'data' => [
                    'project' => $project,
                    'tasks' => $this->getTasksForClientProject($clientId, $projectId),
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}