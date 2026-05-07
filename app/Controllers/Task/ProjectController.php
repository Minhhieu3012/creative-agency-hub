<?php
namespace App\Controllers\Task;

use App\Services\Task\ProjectService;
use App\Middleware\AuthMiddleware;
use Core\Database;

class ProjectController {
    private $service;

    public function __construct() {
        $authUser = AuthMiddleware::check();
        $this->service = new ProjectService($authUser);
    }

    private function getInput() {
        return json_decode(file_get_contents('php://input'), true) ?? $_POST;
    }

    public function index() {
        echo json_encode([
            "status" => "success",
            "data" => $this->service->getAll()
        ]);
    }

    public function show($id) {
        try {
            $project = $this->service->getById($id);

            echo json_encode([
                "status" => "success",
                "data" => $project
            ]);
        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function store() {
        try {
            $id = $this->service->create($this->getInput());

            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Tạo project thành công",
                "data" => ["id"=>$id]
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function update($id) {
        try {
            $this->service->update($id, $this->getInput());

            echo json_encode([
                "status" => "success",
                "message" => "Cập nhật thành công"
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function delete($id) {
        try {
            $this->service->delete($id);

            echo json_encode([
                "status" => "success",
                "message" => "Xóa thành công"
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    private function clientJsonResponse(array $payload, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getClientAuthUser(): array {
        $authUser = AuthMiddleware::check();
        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role !== 'client') {
            $this->clientJsonResponse([
                'status' => 'error',
                'message' => 'Bạn không có quyền truy cập Client Portal'
            ], 403);
        }

        return $authUser;
    }

    private function clientDb(): \PDO {
        return Database::getConnection();
    }

    private function clientStatusLabel(?string $status): string {
        $status = strtolower(trim((string)$status));

        if ($status === 'completed' || $status === 'done') {
            return 'Hoàn thành';
        }

        if ($status === 'archived') {
            return 'Đã lưu trữ';
        }

        if ($status === 'review') {
            return 'Đang duyệt';
        }

        if ($status === 'planned') {
            return 'Lên kế hoạch';
        }

        return 'Đang triển khai';
    }

    private function clientStatusTone(?string $status): string {
        $status = strtolower(trim((string)$status));

        if ($status === 'completed' || $status === 'done') {
            return 'success';
        }

        if ($status === 'archived') {
            return 'info';
        }

        if ($status === 'review') {
            return 'warning';
        }

        return 'primary';
    }

    private function clientTaskStatusLabel(?string $status): string {
        $status = strtolower(trim((string)$status));

        if ($status === 'pending approval') {
            return 'Chờ duyệt';
        }

        if ($status === 'doing') {
            return 'Đang thực hiện';
        }

        if ($status === 'review') {
            return 'Đang kiểm tra';
        }

        if ($status === 'done') {
            return 'Hoàn thành';
        }

        return 'Cần làm';
    }

    private function clientTaskTone(?string $status): string {
        $status = strtolower(trim((string)$status));

        if ($status === 'done') {
            return 'success';
        }

        if ($status === 'review') {
            return 'warning';
        }

        if ($status === 'doing') {
            return 'primary';
        }

        return 'info';
    }

    private function clientProgressValue(?string $status): int {
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

    private function getClientProjectTasks(int $projectId): array {
        $stmt = $this->clientDb()->prepare("\n            SELECT\n                t.*,\n                assignee.full_name AS assignee_name,\n                assignee.email AS assignee_email\n            FROM tasks t\n            LEFT JOIN employees assignee ON assignee.id = t.assignee_id\n            WHERE t.project_id = ?\n            ORDER BY\n                FIELD(t.status, 'Pending approval', 'To do', 'Doing', 'Review', 'Done'),\n                t.deadline ASC,\n                t.id DESC\n        ");
        $stmt->execute([$projectId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function buildClientProjectPayload(array $project, array $tasks): array {
        $taskCount = count($tasks);
        $doneCount = 0;
        $progressSum = 0;
        $nearestDeadline = null;
        $lastUpdate = $project['updated_at'] ?? $project['created_at'] ?? null;

        foreach ($tasks as $task) {
            $status = (string)($task['status'] ?? 'To do');

            if (strtolower($status) === 'done') {
                $doneCount++;
            }

            $progressSum += $this->clientProgressValue($status);

            if (!empty($task['deadline']) && strtolower($status) !== 'done') {
                if ($nearestDeadline === null || $task['deadline'] < $nearestDeadline) {
                    $nearestDeadline = $task['deadline'];
                }
            }

            if (!empty($task['updated_at']) && ($lastUpdate === null || $task['updated_at'] > $lastUpdate)) {
                $lastUpdate = $task['updated_at'];
            }
        }

        $progress = $taskCount > 0 ? (int)round($progressSum / $taskCount) : 0;
        $managerName = $project['manager_name'] ?? 'Chưa gán quản lý';

        return [
            'id' => (int)$project['id'],
            'name' => $project['name'] ?? 'Dự án chưa đặt tên',
            'description' => $project['description'] ?? '',
            'status' => $project['status'] ?? 'Active',
            'status_label' => $this->clientStatusLabel($project['status'] ?? 'Active'),
            'status_tone' => $this->clientStatusTone($project['status'] ?? 'Active'),
            'manager' => mb_strtoupper(mb_substr((string)$managerName, 0, 2, 'UTF-8'), 'UTF-8'),
            'manager_name' => $managerName,
            'manager_email' => $project['manager_email'] ?? '',
            'client_id' => (int)($project['client_id'] ?? 0),
            'tasks' => $taskCount,
            'done' => $doneCount,
            'progress' => $progress,
            'deadline' => $nearestDeadline,
            'created_at' => $project['created_at'] ?? null,
            'updated_at' => $lastUpdate,
        ];
    }

    private function getClientProjects(int $clientId): array {
        $stmt = $this->clientDb()->prepare("\n            SELECT\n                p.*,\n                manager.full_name AS manager_name,\n                manager.email AS manager_email\n            FROM projects p\n            LEFT JOIN employees manager ON manager.id = p.manager_id\n            WHERE p.client_id = ?\n            ORDER BY p.updated_at DESC, p.id DESC\n        ");
        $stmt->execute([$clientId]);

        $projects = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $payload = [];

        foreach ($projects as $project) {
            $tasks = $this->getClientProjectTasks((int)$project['id']);
            $payload[] = $this->buildClientProjectPayload($project, $tasks);
        }

        return $payload;
    }

    private function getClientProjectById(int $projectId, int $clientId): ?array {
        $stmt = $this->clientDb()->prepare("\n            SELECT\n                p.*,\n                manager.full_name AS manager_name,\n                manager.email AS manager_email\n            FROM projects p\n            LEFT JOIN employees manager ON manager.id = p.manager_id\n            WHERE p.id = ?\n              AND p.client_id = ?\n            LIMIT 1\n        ");
        $stmt->execute([$projectId, $clientId]);

        $project = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $project ?: null;
    }

    private function getClientFeedbacks(int $projectId): array {
        try {
            $stmt = $this->clientDb()->prepare("\n                SELECT\n                    c.comment_text,\n                    c.created_at,\n                    e.full_name AS author_name\n                FROM task_comments c\n                INNER JOIN tasks t ON t.id = c.task_id\n                LEFT JOIN employees e ON e.id = c.user_id\n                WHERE t.project_id = ?\n                ORDER BY c.created_at DESC\n                LIMIT 10\n            ");
            $stmt->execute([$projectId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return array_map(function ($row) {
                $name = $row['author_name'] ?? 'Creative Agency Hub';

                return [
                    'avatar' => mb_strtoupper(mb_substr((string)$name, 0, 1, 'UTF-8'), 'UTF-8'),
                    'name' => $name,
                    'message' => $row['comment_text'] ?? '',
                    'time' => $row['created_at'] ?? null,
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getClientUpdates(int $clientId): array {
        try {
            $stmt = $this->clientDb()->prepare("\n                SELECT\n                    t.title,\n                    t.status,\n                    t.updated_at,\n                    p.name AS project_name\n                FROM tasks t\n                INNER JOIN projects p ON p.id = t.project_id\n                WHERE p.client_id = ?\n                ORDER BY t.updated_at DESC, t.id DESC\n                LIMIT 6\n            ");
            $stmt->execute([$clientId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            return array_map(function ($row) {
                $statusLabel = $this->clientTaskStatusLabel($row['status'] ?? 'To do');

                return [
                    'title' => $row['title'] ?? 'Cập nhật task',
                    'description' => ($row['project_name'] ?? 'Dự án') . ' • ' . $statusLabel,
                    'tone' => strtolower((string)($row['status'] ?? '')) === 'done' ? 'success' : 'info',
                    'updated_at' => $row['updated_at'] ?? null,
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function clientIndex() {
        try {
            $authUser = $this->getClientAuthUser();
            $clientId = (int)($authUser['id'] ?? $authUser['employee_id'] ?? 0);
            $projects = $this->getClientProjects($clientId);

            $openProjects = array_values(array_filter($projects, function ($project) {
                return strtolower((string)($project['status'] ?? 'Active')) !== 'archived';
            }));

            $avgProgress = count($openProjects) > 0
                ? (int)round(array_sum(array_column($openProjects, 'progress')) / count($openProjects))
                : 0;

            $lastUpdate = null;

            foreach ($projects as $project) {
                if (!empty($project['updated_at']) && ($lastUpdate === null || $project['updated_at'] > $lastUpdate)) {
                    $lastUpdate = $project['updated_at'];
                }
            }

            $this->clientJsonResponse([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'open_projects' => count($openProjects),
                        'avg_progress' => $avgProgress,
                        'pending_feedback' => 0,
                        'last_update' => $lastUpdate,
                    ],
                    'projects' => $projects,
                    'updates' => $this->getClientUpdates($clientId),
                ]
            ]);
        } catch (\Throwable $e) {
            $this->clientJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function clientShow($id) {
        try {
            $authUser = $this->getClientAuthUser();
            $clientId = (int)($authUser['id'] ?? $authUser['employee_id'] ?? 0);
            $projectId = (int)$id;
            $project = $this->getClientProjectById($projectId, $clientId);

            if (!$project) {
                $this->clientJsonResponse([
                    'status' => 'error',
                    'message' => 'Dự án không tồn tại hoặc không thuộc tài khoản khách hàng này'
                ], 404);
            }

            $tasks = $this->getClientProjectTasks($projectId);
            $projectPayload = $this->buildClientProjectPayload($project, $tasks);
            $taskPayload = array_map(function ($task) {
                return [
                    'id' => (int)($task['id'] ?? 0),
                    'title' => $task['title'] ?? 'Task chưa đặt tên',
                    'desc' => $task['description'] ?? '',
                    'status' => $task['status'] ?? 'To do',
                    'status_label' => $this->clientTaskStatusLabel($task['status'] ?? 'To do'),
                    'tone' => $this->clientTaskTone($task['status'] ?? 'To do'),
                    'deadline' => $task['deadline'] ?? null,
                    'owner' => $task['assignee_name'] ?? 'Chưa gán',
                    'updated_at' => $task['updated_at'] ?? null,
                ];
            }, $tasks);

            $this->clientJsonResponse([
                'status' => 'success',
                'data' => [
                    'project' => $projectPayload,
                    'tasks' => $taskPayload,
                    'feedbacks' => $this->getClientFeedbacks($projectId),
                ]
            ]);
        } catch (\Throwable $e) {
            $this->clientJsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}