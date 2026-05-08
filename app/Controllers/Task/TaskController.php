<?php
namespace App\Controllers\Task;

use App\Middleware\AuthMiddleware;
use App\Models\Task\TaskModel;
use Exception;

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
        if ($value === null || $value === '' || $value === false) return null;
        $number = (int) $value;
        return $number > 0 ? $number : null;
    }

    private function normalizePriority(?string $priority): string {
        $priority = ucfirst(strtolower(trim((string) $priority)));
        return in_array($priority, ['Low', 'Medium', 'High'], true) ? $priority : 'Medium';
    }

    private function normalizeStatus(?string $status): string {
        $status = trim((string) $status);
        $map = ['to do' => 'To do', 'todo' => 'To do', 'doing' => 'Doing', 'review' => 'Review', 'done' => 'Done'];
        return $map[strtolower($status)] ?? 'To do';
    }

    // CẬP NHẬT: Manager có toàn quyền quản lý như Admin
    private function ensureCanManageTask(array $task, array $authUser): bool {
        $role = strtolower($authUser['role'] ?? 'employee');
        return in_array($role, ['admin', 'manager']);
    }

    private function ensureCanSeeTask(array $task, array $authUser): bool {
        if ($this->ensureCanManageTask($task, $authUser)) return true;
        if (($authUser['role'] ?? '') === 'employee') {
            return (int)($task['assignee_id'] ?? 0) === (int)$authUser['id']
                || (int)($task['assigner_id'] ?? 0) === (int)$authUser['id']
                || (int)($task['watcher_id'] ?? 0) === (int)$authUser['id'];
        }
        return ($authUser['role'] ?? '') === 'client';
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
        if (($authUser['role'] ?? '') === 'employee') $filters['user_id'] = $authUser['id'];
        
        $tasks = $this->taskModel->getAllTasks($filters);
        $this->jsonResponse(['status' => 'success', 'data' => $tasks]);
    }

    public function store() {
        $authUser = AuthMiddleware::check();
        // CẬP NHẬT: Cho phép Manager tạo Task
        if (!in_array(strtolower($authUser['role'] ?? ''), ['admin', 'manager'])) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Permission denied'], 403);
        }

        $input = $this->getJsonInput();
        $title = trim((string)($input['title'] ?? ''));
        $deadline = trim((string)($input['deadline'] ?? ''));

        if ($title === '' || $deadline === '') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập Tiêu đề và Deadline'], 400);
        }

        $taskId = $this->taskModel->createTask(
            $title,
            trim((string)($input['description'] ?? '')),
            $this->normalizePriority($input['priority'] ?? 'Medium'),
            $deadline,
            (int)$authUser['id'],
            $this->normalizeNullableInt($input['assignee_id'] ?? null),
            $this->normalizeNullableInt($input['watcher_id'] ?? null),
            $this->normalizeNullableInt($input['project_id'] ?? null)
        );

        if (!$taskId) $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi server'], 500);
        $this->jsonResponse(['status' => 'success', 'message' => 'Tạo Task thành công', 'data' => ['id' => (int)$taskId]]);
    }

    public function updateStatus($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int)$taskId);
        if (!$task) $this->jsonResponse(['status' => 'error', 'message' => 'Task không tồn tại'], 404);

        $userRole = strtolower($authUser['role'] ?? 'employee');
        // Manager và Admin được sửa mọi task, Employee chỉ sửa task của mình
        if (!in_array($userRole, ['admin', 'manager']) && (int)($task['assignee_id'] ?? 0) !== (int)$authUser['id']) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không phải người thực hiện task này.'], 403);
        }

        $input = $this->getJsonInput();
        $status = $this->normalizeStatus($input['status'] ?? '');
        if ($this->taskModel->updateStatus((int)$taskId, $status)) {
            $this->jsonResponse(['status' => 'success', 'data' => ['task' => $this->taskModel->getTaskById((int)$taskId)]]);
        }
        $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi server'], 500);
    }

    public function update($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int)$taskId);
        if (!$task || !$this->ensureCanManageTask($task, $authUser)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Permission denied'], 403);
        }
        // ... (Phần logic Update giữ nguyên như cũ)
    }

    public function destroy($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int)$taskId);
        if (!$task || !$this->ensureCanManageTask($task, $authUser)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Permission denied'], 403);
        }
        if ($this->taskModel->deleteTask((int)$taskId)) $this->jsonResponse(['status' => 'success', 'message' => 'Xoá thành công']);
    }
}