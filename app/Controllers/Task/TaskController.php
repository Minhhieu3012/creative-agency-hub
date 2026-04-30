<?php
namespace App\Controllers\Task;

use App\Middleware\AuthMiddleware;
use App\Models\Task\TaskModel;

class TaskController {
    private TaskModel $taskModel;

    public function __construct() {
        $this->taskModel = new TaskModel();
    }

    public function showBoard() {
        require_once __DIR__ . '/../../View/tasks/kanban.php';
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getJsonInput(): array {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);

        return is_array($input) ? $input : [];
    }

    private function normalizeNullableInt($value): ?int {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $number = (int) $value;
        return $number > 0 ? $number : null;
    }

    private function normalizePriority(?string $priority): string {
        $priority = ucfirst(strtolower(trim((string) $priority)));
        $valid = ['Low', 'Medium', 'High'];

        return in_array($priority, $valid, true) ? $priority : 'Medium';
    }

    private function normalizeStatus(?string $status): string {
        $status = trim((string) $status);

        $map = [
            'to do' => 'To do',
            'todo' => 'To do',
            'doing' => 'Doing',
            'review' => 'Review',
            'done' => 'Done',
        ];

        $key = strtolower($status);

        return $map[$key] ?? 'To do';
    }

    private function ensureCanSeeTask(array $task, array $authUser): bool {
        if ($authUser['role'] === 'admin') {
            return true;
        }

        if ($authUser['role'] === 'manager') {
            if (empty($task['project_id'])) {
                return (int) $task['assigner_id'] === (int) $authUser['id'];
            }

            return $this->taskModel->isManagerOfProjectByProjectId((int) $task['project_id'], (int) $authUser['id']);
        }

        if ($authUser['role'] === 'employee') {
            return (int) $task['assignee_id'] === (int) $authUser['id']
                || (int) $task['assigner_id'] === (int) $authUser['id']
                || (int) $task['watcher_id'] === (int) $authUser['id'];
        }

        if ($authUser['role'] === 'client') {
            return true;
        }

        return false;
    }

    public function index() {
        $authUser = AuthMiddleware::check();

        $filters = [
            'project_id'  => $_GET['project_id'] ?? null,
            'assignee_id' => $_GET['assignee_id'] ?? null,
            'status'      => $_GET['status'] ?? null,
            'deadline'    => $_GET['deadline'] ?? null,
            'manager_id'  => null,
            'user_id'     => null,
            'role'        => $authUser['role'] ?? null,
        ];

        if ($authUser['role'] === 'employee') {
            $filters['user_id'] = $authUser['id'];
        }

        if ($authUser['role'] === 'manager') {
            $filters['manager_id'] = $authUser['id'];
        }

        $tasks = $this->taskModel->getAllTasks($filters);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy danh sách task thành công',
            'data' => $tasks
        ]);
    }

    public function store() {
        $authUser = AuthMiddleware::check();

        if (($authUser['role'] ?? '') === 'employee') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Permission denied'
            ], 403);
        }

        $input = $this->getJsonInput();

        $title = trim((string) ($input['title'] ?? ''));
        $deadline = trim((string) ($input['deadline'] ?? ''));

        if ($title === '' || $deadline === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng nhập Tiêu đề và Deadline'
            ], 400);
        }

        $projectId = $this->normalizeNullableInt($input['project_id'] ?? null);
        $assigneeId = $this->normalizeNullableInt($input['assignee_id'] ?? null);
        $watcherId = $this->normalizeNullableInt($input['watcher_id'] ?? null);
        $assignerId = (int) ($authUser['id'] ?? 0);

        if (!$projectId && ($authUser['role'] ?? '') === 'manager') {
            $defaultProject = $this->taskModel->getFirstProjectManagedBy((int) $authUser['id']);
            $projectId = $defaultProject ? (int) $defaultProject['id'] : null;
        }

        if ($projectId && ($authUser['role'] ?? '') === 'manager') {
            if (!$this->taskModel->isManagerOfProjectByProjectId($projectId, (int) $authUser['id'])) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Không có quyền tạo task trong project này'
                ], 403);
            }
        }

        $priority = $this->normalizePriority($input['priority'] ?? 'Medium');
        $description = trim((string) ($input['description'] ?? ''));

        $taskId = $this->taskModel->createTask(
            $title,
            $description,
            $priority,
            $deadline,
            $assignerId,
            $assigneeId,
            $watcherId,
            $projectId
        );

        if (!$taskId) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi server'
            ], 500);
        }

        $task = $this->taskModel->getTaskById((int) $taskId);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Tạo Task thành công',
            'data' => [
                'id' => (int) $taskId,
                'task' => $task
            ]
        ]);
    }

    public function update($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int) $taskId);

        if (!$task) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Task không tồn tại'
            ], 404);
        }

        if (($authUser['role'] ?? '') === 'employee') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Permission denied'
            ], 403);
        }

        if (!$this->ensureCanSeeTask($task, $authUser)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không có quyền'
            ], 403);
        }

        $input = $this->getJsonInput();

        $title = trim((string) ($input['title'] ?? $task['title']));
        $description = trim((string) ($input['description'] ?? $task['description']));
        $priority = $this->normalizePriority($input['priority'] ?? $task['priority']);
        $deadline = trim((string) ($input['deadline'] ?? $task['deadline']));

        $assigneeId = array_key_exists('assignee_id', $input)
            ? $this->normalizeNullableInt($input['assignee_id'])
            : $this->normalizeNullableInt($task['assignee_id']);

        $watcherId = array_key_exists('watcher_id', $input)
            ? $this->normalizeNullableInt($input['watcher_id'])
            : $this->normalizeNullableInt($task['watcher_id']);

        $projectId = array_key_exists('project_id', $input)
            ? $this->normalizeNullableInt($input['project_id'])
            : $this->normalizeNullableInt($task['project_id']);

        if ($projectId && ($authUser['role'] ?? '') === 'manager') {
            if (!$this->taskModel->isManagerOfProjectByProjectId($projectId, (int) $authUser['id'])) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Không có quyền cập nhật task trong project này'
                ], 403);
            }
        }

        $success = $this->taskModel->updateTask(
            (int) $taskId,
            $title,
            $description,
            $priority,
            $deadline,
            $assigneeId,
            $watcherId,
            $projectId
        );

        if (!$success) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi server'
            ], 500);
        }

        $updatedTask = $this->taskModel->getTaskById((int) $taskId);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Cập nhật thành công',
            'data' => [
                'task' => $updatedTask
            ]
        ]);
    }

    public function updateStatus($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int) $taskId);

        if (!$task) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Task không tồn tại'
            ], 404);
        }

        if (($authUser['role'] ?? '') === 'employee') {
            if ((int) $task['assignee_id'] !== (int) $authUser['id']) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Không có quyền'
                ], 403);
            }
        }

        if (($authUser['role'] ?? '') === 'manager' && !$this->ensureCanSeeTask($task, $authUser)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không có quyền'
            ], 403);
        }

        $input = $this->getJsonInput();
        $status = $this->normalizeStatus($input['status'] ?? '');

        $validStatuses = ['To do', 'Doing', 'Review', 'Done'];

        if (!in_array($status, $validStatuses, true)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Status không hợp lệ'
            ], 400);
        }

        $success = $this->taskModel->updateStatus((int) $taskId, $status);

        if (!$success) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi server'
            ], 500);
        }

        $updatedTask = $this->taskModel->getTaskById((int) $taskId);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Update status thành công',
            'data' => [
                'task' => $updatedTask
            ]
        ]);
    }
}