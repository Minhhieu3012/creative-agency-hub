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
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
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

        $number = (int)$value;
        return $number > 0 ? $number : null;
    }

    private function normalizePriority(?string $priority): string {
        $priority = ucfirst(strtolower(trim((string)$priority)));
        return in_array($priority, ['Low', 'Medium', 'High'], true) ? $priority : 'Medium';
    }

    private function normalizeStatus(?string $status): string {
        $status = trim((string)$status);

        $map = [
            'pending approval' => 'Pending approval',
            'pending' => 'Pending approval',
            'chờ duyệt' => 'Pending approval',

            'to do' => 'To do',
            'todo' => 'To do',
            'cần làm' => 'To do',

            'doing' => 'Doing',
            'in progress' => 'Doing',
            'in_progress' => 'Doing',
            'đang thực hiện' => 'Doing',

            'review' => 'Review',
            'đang kiểm tra' => 'Review',

            'done' => 'Done',
            'completed' => 'Done',
            'hoàn thành' => 'Done',
        ];

        return $map[strtolower($status)] ?? 'To do';
    }

    private function getRole(array $authUser): string {
        return strtolower((string)($authUser['role'] ?? 'employee'));
    }

    private function getUserId(array $authUser): int {
        return (int)($authUser['id'] ?? $authUser['employee_id'] ?? 0);
    }

    // CẬP NHẬT: Manager có toàn quyền quản lý như Admin
    private function ensureCanManageTask(array $task, array $authUser): bool {
        $role = $this->getRole($authUser);
        return in_array($role, ['admin', 'manager'], true);
    }

    private function ensureCanSeeTask(array $task, array $authUser): bool {
        if ($this->ensureCanManageTask($task, $authUser)) {
            return true;
        }

        if ($this->getRole($authUser) === 'employee') {
            $userId = $this->getUserId($authUser);

            return (int)($task['assignee_id'] ?? 0) === $userId
                || (int)($task['assigner_id'] ?? 0) === $userId
                || (int)($task['watcher_id'] ?? 0) === $userId;
        }

        return $this->getRole($authUser) === 'client';
    }

    private function canCreateTask(array $authUser): bool {
        return in_array($this->getRole($authUser), ['admin', 'manager', 'employee'], true);
    }

    private function resolveSafeProjectId(?int $projectId): ?int {
        if (!$projectId) {
            return null;
        }

        return $this->taskModel->projectExists($projectId) ? $projectId : null;
    }

    private function resolveSafeEmployeeId(?int $employeeId): ?int {
        if (!$employeeId) {
            return null;
        }

        return $this->taskModel->employeeExists($employeeId) ? $employeeId : null;
    }

    private function userCanUseProjectForTask(int $projectId, array $authUser): bool {
        $role = $this->getRole($authUser);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager') {
            if (method_exists($this->taskModel, 'isManagerOfProjectByProjectId')) {
                return $this->taskModel->isManagerOfProjectByProjectId($projectId, $this->getUserId($authUser));
            }

            return true;
        }

        if ($role === 'employee') {
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

        if (($authUser['role'] ?? '') === 'employee') {
            $filters['user_id'] = $this->getUserId($authUser);
        }

        if (($authUser['role'] ?? '') === 'manager') {
            $filters['manager_id'] = $this->getUserId($authUser);
        }

        $tasks = $this->taskModel->getAllTasks($filters);
        $this->jsonResponse(['status' => 'success', 'data' => $tasks]);
    }

    public function store() {
        $authUser = AuthMiddleware::check();
        $role = $this->getRole($authUser);

        // CẬP NHẬT: Cho phép Admin/Manager tạo Task trực tiếp, Employee tạo Task chờ duyệt
        if (!$this->canCreateTask($authUser)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Permission denied'], 403);
        }

        $input = $this->getJsonInput();
        $title = trim((string)($input['title'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $deadline = trim((string)($input['deadline'] ?? ''));

        if ($title === '' || $deadline === '') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng nhập Tiêu đề và Deadline'], 400);
        }

        $deadlineDate = date_create_from_format('Y-m-d', $deadline);

        if (!$deadlineDate || $deadlineDate->format('Y-m-d') !== $deadline) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Deadline không hợp lệ'], 422);
        }

        $projectId = $this->resolveSafeProjectId(
            $this->normalizeNullableInt($input['project_id'] ?? null)
        );

        if (!$projectId) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng chọn dự án hợp lệ trước khi tạo task'
            ], 422);
        }

        if (!$this->userCanUseProjectForTask($projectId, $authUser)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Bạn không có quyền tạo task trong dự án này'
            ], 403);
        }

        $assigneeId = $this->resolveSafeEmployeeId(
            $this->normalizeNullableInt($input['assignee_id'] ?? null)
        );

        $watcherId = $this->resolveSafeEmployeeId(
            $this->normalizeNullableInt($input['watcher_id'] ?? null)
        );

        if ($role === 'employee') {
            $assigneeId = $this->getUserId($authUser);
            $watcherId = $watcherId ?: $this->getUserId($authUser);
            $initialStatus = 'Pending approval';
        } else {
            $assigneeId = $assigneeId ?: $this->getUserId($authUser);
            $watcherId = $watcherId ?: $this->getUserId($authUser);
            $initialStatus = $this->normalizeStatus($input['status'] ?? 'To do');

            if ($initialStatus === 'Pending approval') {
                $initialStatus = 'To do';
            }
        }

        $taskId = $this->taskModel->createTask(
            $title,
            $description,
            $this->normalizePriority($input['priority'] ?? 'Medium'),
            $deadline,
            $this->getUserId($authUser),
            $assigneeId,
            $watcherId,
            $projectId
        );

        if (!$taskId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi server'], 500);
        }

        if ($initialStatus !== 'To do') {
            $this->taskModel->updateStatus((int)$taskId, $initialStatus);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => $role === 'employee'
                ? 'Tạo Task thành công, task đang chờ Admin/Manager duyệt'
                : 'Tạo Task thành công',
            'data' => [
                'id' => (int)$taskId,
                'task' => $this->taskModel->getTaskById((int)$taskId)
            ]
        ], 201);
    }

    public function updateStatus($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int)$taskId);

        if (!$task) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Task không tồn tại'], 404);
        }

        $userRole = $this->getRole($authUser);

        // Manager và Admin được sửa mọi task, Employee chỉ sửa task của mình
        if (!in_array($userRole, ['admin', 'manager'], true) && (int)($task['assignee_id'] ?? 0) !== $this->getUserId($authUser)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Bạn không phải người thực hiện task này.'], 403);
        }

        $input = $this->getJsonInput();
        $status = $this->normalizeStatus($input['status'] ?? '');

        if ($userRole === 'employee') {
            if ((string)($task['status'] ?? '') === 'Pending approval') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task đang chờ duyệt, bạn chưa thể đổi trạng thái.'
                ], 403);
            }

            if ($status === 'Done' || $status === 'Pending approval') {
                $status = 'Review';
            }
        }

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

        $input = $this->getJsonInput();
        $title = trim((string)($input['title'] ?? $task['title'] ?? ''));
        $description = trim((string)($input['description'] ?? $task['description'] ?? ''));
        $deadline = trim((string)($input['deadline'] ?? $task['deadline'] ?? ''));

        if ($title === '' || $deadline === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng nhập tên công việc và deadline'
            ], 422);
        }

        $deadlineDate = date_create_from_format('Y-m-d', $deadline);

        if (!$deadlineDate || $deadlineDate->format('Y-m-d') !== $deadline) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Deadline không hợp lệ'], 422);
        }

        $projectId = $this->resolveSafeProjectId(
            $this->normalizeNullableInt($input['project_id'] ?? $task['project_id'] ?? null)
        );

        if (!$projectId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng chọn dự án hợp lệ'], 422);
        }

        if (!$this->userCanUseProjectForTask($projectId, $authUser)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Bạn không có quyền chỉnh sửa task trong dự án này'
            ], 403);
        }

        $assigneeId = $this->resolveSafeEmployeeId(
            $this->normalizeNullableInt($input['assignee_id'] ?? $task['assignee_id'] ?? null)
        );

        if (!$assigneeId) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Vui lòng chọn nhân viên thực hiện hợp lệ'], 422);
        }

        $watcherId = $this->resolveSafeEmployeeId(
            $this->normalizeNullableInt($input['watcher_id'] ?? $task['watcher_id'] ?? null)
        );

        $updated = $this->taskModel->updateTask(
            (int)$taskId,
            $title,
            $description,
            $this->normalizePriority($input['priority'] ?? $task['priority'] ?? 'Medium'),
            $deadline,
            $assigneeId,
            $watcherId,
            $projectId
        );

        if (!$updated) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi server'], 500);
        }

        if (!empty($input['status'])) {
            $this->taskModel->updateStatus((int)$taskId, $this->normalizeStatus($input['status']));
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Cập nhật task thành công',
            'data' => [
                'task' => $this->taskModel->getTaskById((int)$taskId)
            ]
        ]);
    }

    public function destroy($taskId) {
        $authUser = AuthMiddleware::check();
        $task = $this->taskModel->getTaskById((int)$taskId);

        if (!$task || !$this->ensureCanManageTask($task, $authUser)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Permission denied'], 403);
        }

        if ($this->taskModel->deleteTask((int)$taskId)) {
            $this->jsonResponse(['status' => 'success', 'message' => 'Xoá thành công']);
        }

        $this->jsonResponse(['status' => 'error', 'message' => 'Lỗi server'], 500);
    }
}