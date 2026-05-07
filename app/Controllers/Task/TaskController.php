<?php
namespace App\Controllers\Task;

use App\Middleware\AuthMiddleware;
use App\Models\Task\TaskModel;
use PDOException;
use Throwable;

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

        header('Content-Type: application/json; charset=utf-8');
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

    private function isAdminOrManager(array $authUser): bool {
        return in_array($this->getRole($authUser), ['admin', 'manager'], true);
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

    public function index() {
        try {
            $authUser = AuthMiddleware::check();

            $filters = [
                'project_id'  => $_GET['project_id'] ?? null,
                'assignee_id' => $_GET['assignee_id'] ?? null,
                'status'      => $_GET['status'] ?? null,
                'deadline'    => $_GET['deadline'] ?? null,
                'manager_id'  => null,
                'user_id'     => null,
                'role'        => $this->getRole($authUser),
            ];

            if ($this->getRole($authUser) === 'employee') {
                $filters['user_id'] = $authUser['id'];
            }

            $tasks = $this->taskModel->getAllTasks($filters);

            $this->jsonResponse([
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể tải danh sách task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store() {
        try {
            $authUser = AuthMiddleware::check();
            $role = $this->getRole($authUser);

            if (!$this->canCreateTask($authUser)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền tạo task.'
                ], 403);
            }

            $input = $this->getJsonInput();

            $title = trim((string)($input['title'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $deadline = trim((string)($input['deadline'] ?? ''));

            if ($title === '') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Vui lòng nhập tên công việc.'
                ], 422);
            }

            if ($deadline === '') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn deadline.'
                ], 422);
            }

            $deadlineDate = date_create_from_format('Y-m-d', $deadline);

            if (!$deadlineDate || $deadlineDate->format('Y-m-d') !== $deadline) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Deadline không hợp lệ.'
                ], 422);
            }

            $projectId = $this->resolveSafeProjectId(
                $this->normalizeNullableInt($input['project_id'] ?? null)
            );

            if (!$projectId) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn dự án hợp lệ trước khi tạo task.'
                ], 422);
            }

            $assigneeId = $this->resolveSafeEmployeeId(
                $this->normalizeNullableInt($input['assignee_id'] ?? null)
            );

            $watcherId = $this->resolveSafeEmployeeId(
                $this->normalizeNullableInt($input['watcher_id'] ?? null)
            );

            if ($role === 'employee') {
                $assigneeId = (int)$authUser['id'];
                $watcherId = $watcherId ?: (int)$authUser['id'];
                $initialStatus = 'Pending approval';
            } else {
                if (!$assigneeId) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => 'Vui lòng chọn nhân viên thực hiện task.'
                    ], 422);
                }

                $watcherId = $watcherId ?: (int)$authUser['id'];
                $initialStatus = 'To do';
            }

            $taskId = $this->taskModel->createTask(
                $title,
                $description,
                $this->normalizePriority($input['priority'] ?? 'Medium'),
                $deadline,
                (int)$authUser['id'],
                $assigneeId,
                $watcherId,
                $projectId,
                $initialStatus
            );

            $createdTask = $this->taskModel->getTaskById((int)$taskId);

            $message = $role === 'employee'
                ? 'Task đã được tạo và đang chờ Admin/Manager duyệt.'
                : 'Tạo task thành công.';

            $this->jsonResponse([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'task' => $createdTask
                ]
            ], 201);
        } catch (PDOException $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Lỗi database khi tạo task: ' . $e->getMessage()
            ], 500);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể tạo task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus($taskId) {
        try {
            $authUser = AuthMiddleware::check();
            $task = $this->taskModel->getTaskById((int)$taskId);

            if (!$task) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task không tồn tại.'
                ], 404);
            }

            $role = $this->getRole($authUser);
            $isOwner = (int)($task['assignee_id'] ?? 0) === (int)$authUser['id']
                || (int)($task['assigner_id'] ?? 0) === (int)$authUser['id'];

            if (!$this->isAdminOrManager($authUser) && !$isOwner) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bạn chỉ được cập nhật task liên quan đến mình.'
                ], 403);
            }

            $input = $this->getJsonInput();
            $status = $this->normalizeStatus($input['status'] ?? '');

            if ($role === 'employee') {
                if ((string)$task['status'] === 'Pending approval') {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => 'Task đang chờ duyệt, bạn chưa thể đổi trạng thái.'
                    ], 403);
                }

                if ($status === 'Done') {
                    $status = 'Review';
                }

                if ($status === 'Pending approval') {
                    $status = 'Review';
                }
            }

            if ($this->taskModel->updateStatus((int)$taskId, $status)) {
                $this->jsonResponse([
                    'status' => 'success',
                    'message' => 'Cập nhật trạng thái task thành công.',
                    'data' => [
                        'task' => $this->taskModel->getTaskById((int)$taskId)
                    ]
                ]);
            }

            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể cập nhật trạng thái task.'
            ], 500);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể cập nhật trạng thái task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update($taskId) {
        try {
            $authUser = AuthMiddleware::check();

            if (!$this->isAdminOrManager($authUser)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền chỉnh sửa task.'
                ], 403);
            }

            $task = $this->taskModel->getTaskById((int)$taskId);

            if (!$task) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task không tồn tại.'
                ], 404);
            }

            $input = $this->getJsonInput();

            $title = trim((string)($input['title'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $deadline = trim((string)($input['deadline'] ?? ''));

            if ($title === '' || $deadline === '') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Vui lòng nhập tên công việc và deadline.'
                ], 422);
            }

            $deadlineDate = date_create_from_format('Y-m-d', $deadline);

            if (!$deadlineDate || $deadlineDate->format('Y-m-d') !== $deadline) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Deadline không hợp lệ.'
                ], 422);
            }

            $projectId = $this->resolveSafeProjectId(
                $this->normalizeNullableInt($input['project_id'] ?? null)
            );

            if (!$projectId) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn dự án hợp lệ.'
                ], 422);
            }

            $assigneeId = $this->resolveSafeEmployeeId(
                $this->normalizeNullableInt($input['assignee_id'] ?? null)
            );

            if (!$assigneeId) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn nhân viên thực hiện hợp lệ.'
                ], 422);
            }

            $watcherId = $this->resolveSafeEmployeeId(
                $this->normalizeNullableInt($input['watcher_id'] ?? null)
            );

            $this->taskModel->updateTask(
                (int)$taskId,
                $title,
                $description,
                $this->normalizePriority($input['priority'] ?? 'Medium'),
                $deadline,
                $assigneeId,
                $watcherId,
                $projectId
            );

            if (!empty($input['status'])) {
                $this->taskModel->updateStatus((int)$taskId, $this->normalizeStatus($input['status']));
            }

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Cập nhật task thành công.',
                'data' => [
                    'task' => $this->taskModel->getTaskById((int)$taskId)
                ]
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể cập nhật task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($taskId) {
        try {
            $authUser = AuthMiddleware::check();

            if (!$this->isAdminOrManager($authUser)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xóa task.'
                ], 403);
            }

            $task = $this->taskModel->getTaskById((int)$taskId);

            if (!$task) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task không tồn tại.'
                ], 404);
            }

            $this->taskModel->deleteTask((int)$taskId);

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Xóa task thành công.'
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể xóa task: ' . $e->getMessage()
            ], 500);
        }
    }
}