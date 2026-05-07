<?php
namespace App\Controllers\Task;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Models\Task\TaskModel;
use Throwable;

class TaskApprovalController extends BaseController {
    private TaskModel $taskModel;

    public function __construct() {
        $this->taskModel = new TaskModel();
    }

    private function getRole(array $authUser): string {
        return strtolower((string)($authUser['role'] ?? 'employee'));
    }

    private function isAdminOrManager(array $authUser): bool {
        return in_array($this->getRole($authUser), ['admin', 'manager'], true);
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        http_response_code($statusCode);

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function tagTaskApprovalType(array $task, string $approvalType): array {
        $task['approval_type'] = $approvalType;

        if ($approvalType === 'new_task') {
            $task['approval_label'] = 'Duyệt task mới';
            $task['approval_next_status'] = 'To do';
        } else {
            $task['approval_label'] = 'Duyệt hoàn thành';
            $task['approval_next_status'] = 'Done';
        }

        return $task;
    }

    public function submit($taskId) {
        try {
            $authUser = AuthMiddleware::check();
            $task = $this->taskModel->getTaskById((int)$taskId);

            if (!$task) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task không tồn tại.'
                ], 404);
            }

            if ((string)$task['status'] === 'Pending approval') {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task mới đang chờ duyệt, chưa thể gửi duyệt hoàn thành.'
                ], 422);
            }

            if (!$this->isAdminOrManager($authUser) && (int)($task['assignee_id'] ?? 0) !== (int)$authUser['id']) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bạn chỉ được gửi duyệt task được giao cho mình.'
                ], 403);
            }

            $this->taskModel->updateStatus((int)$taskId, 'Review');

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Task đã được gửi sang Đang kiểm tra.',
                'data' => [
                    'task' => $this->taskModel->getTaskById((int)$taskId)
                ]
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể gửi duyệt task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve($taskId) {
        try {
            $authUser = AuthMiddleware::check();

            if (!$this->isAdminOrManager($authUser)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Chỉ Admin hoặc Manager được duyệt task.'
                ], 403);
            }

            $task = $this->taskModel->getTaskById((int)$taskId);

            if (!$task) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task không tồn tại.'
                ], 404);
            }

            $currentStatus = (string)$task['status'];

            if ($currentStatus === 'Pending approval') {
                $nextStatus = 'To do';
                $message = 'Task mới đã được duyệt và chuyển sang Cần làm.';
            } elseif ($currentStatus === 'Review') {
                $nextStatus = 'Done';
                $message = 'Task hoàn thành đã được duyệt.';
            } else {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Chỉ task ở Chờ duyệt hoặc Đang kiểm tra mới có thể duyệt.'
                ], 422);
            }

            $this->taskModel->updateStatus((int)$taskId, $nextStatus);

            $this->jsonResponse([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'task' => $this->taskModel->getTaskById((int)$taskId)
                ]
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể duyệt task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject($taskId) {
        try {
            $authUser = AuthMiddleware::check();

            if (!$this->isAdminOrManager($authUser)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Chỉ Admin hoặc Manager được từ chối task.'
                ], 403);
            }

            $task = $this->taskModel->getTaskById((int)$taskId);

            if (!$task) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Task không tồn tại.'
                ], 404);
            }

            $currentStatus = (string)$task['status'];

            if (!in_array($currentStatus, ['Pending approval', 'Review'], true)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Chỉ task ở Chờ duyệt hoặc Đang kiểm tra mới có thể từ chối.'
                ], 422);
            }

            $this->taskModel->updateStatus((int)$taskId, 'Doing');

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Task đã bị từ chối và chuyển về Đang thực hiện để chỉnh sửa.',
                'data' => [
                    'task' => $this->taskModel->getTaskById((int)$taskId)
                ]
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể từ chối task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReviewTasks() {
        try {
            $authUser = AuthMiddleware::check();

            if (!$this->isAdminOrManager($authUser)) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem danh sách task chờ duyệt.'
                ], 403);
            }

            $pendingTasks = $this->taskModel->getAllTasks([
                'status' => 'Pending approval',
                'role' => $this->getRole($authUser)
            ]);

            $reviewTasks = $this->taskModel->getAllTasks([
                'status' => 'Review',
                'role' => $this->getRole($authUser)
            ]);

            $pendingTasks = array_map(function ($task) {
                return $this->tagTaskApprovalType($task, 'new_task');
            }, $pendingTasks);

            $reviewTasks = array_map(function ($task) {
                return $this->tagTaskApprovalType($task, 'completion');
            }, $reviewTasks);

            $tasks = array_merge($pendingTasks, $reviewTasks);

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Danh sách task chờ duyệt.',
                'data' => $tasks
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể tải task chờ duyệt: ' . $e->getMessage()
            ], 500);
        }
    }
}