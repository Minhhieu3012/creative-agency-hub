<?php
namespace App\Controllers\Task;

use App\Models\Task\TaskModel;
use Core\JwtHandler;
use Throwable;

class TaskController {
    private TaskModel $taskModel;
    private JwtHandler $jwt;
    private ?array $authUser;

    public function __construct($authUser = null) {
        $this->taskModel = new TaskModel();
        $this->jwt = new JwtHandler();
        $this->authUser = is_array($authUser) ? $authUser : null;
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

    private function getInput(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        if (!empty($raw)) {
            $parsed = [];
            parse_str($raw, $parsed);

            if (is_array($parsed) && !empty($parsed)) {
                return $parsed;
            }
        }

        return $_POST ?? [];
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
            'role' => strtolower((string)($payload['role'] ?? '')),
            'full_name' => $payload['full_name'] ?? ($payload['name'] ?? null),
        ];
    }

    private function requireAuth(): array {
        if ($this->authUser && !empty($this->authUser['id'])) {
            return $this->authUser;
        }

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
                    $this->authUser = $authUser;
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
            $this->authUser = [
                'id' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? null,
                'role' => strtolower((string)($_SESSION['user_role'] ?? '')),
                'full_name' => $_SESSION['full_name'] ?? null,
            ];

            return $this->authUser;
        }

        $this->json([
            'status' => 'error',
            'message' => 'Bạn cần đăng nhập lại.'
        ], 401);
    }

    private function requireRole(array $roles): array {
        $authUser = $this->requireAuth();
        $role = strtolower((string)($authUser['role'] ?? ''));

        if (!in_array($role, $roles, true)) {
            $this->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện thao tác này.'
            ], 403);
        }

        return $authUser;
    }

    private function resolveId($idOrParams): ?int {
        if (is_array($idOrParams)) {
            if (isset($idOrParams['id'])) {
                return (int)$idOrParams['id'];
            }

            if (isset($idOrParams[0])) {
                return (int)$idOrParams[0];
            }

            return null;
        }

        if ($idOrParams !== null && $idOrParams !== '') {
            return (int)$idOrParams;
        }

        return null;
    }

    private function buildFiltersFromQuery(): array {
        return [
            'project_id' => $_GET['project_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'search' => $_GET['search'] ?? ($_GET['q'] ?? ''),
        ];
    }

    private function wantsGroupedResponse(): bool {
        $grouped = strtolower((string)($_GET['grouped'] ?? ''));
        $kanban = strtolower((string)($_GET['kanban'] ?? ''));

        return in_array($grouped, ['1', 'true', 'yes'], true)
            || in_array($kanban, ['1', 'true', 'yes'], true);
    }

    public function index(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $filters = $this->buildFiltersFromQuery();

            if ($this->wantsGroupedResponse()) {
                $this->json([
                    'status' => 'success',
                    'data' => $this->taskModel->getKanbanGrouped($filters, $authUser)
                ]);
            }

            $this->json([
                'status' => 'success',
                'data' => $this->taskModel->all($filters, $authUser)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            if (!$this->taskModel->canUserAccessTask($authUser, $taskId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem task này.'
                ], 403);
            }

            $task = $this->taskModel->findById($taskId);

            if (!$task) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy task.'
                ], 404);
            }

            $this->json([
                'status' => 'success',
                'data' => $task
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function options(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

            if ($projectId <= 0) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn dự án để tải danh sách nhân viên có thể giao task.'
                ], 400);
            }

            $this->json([
                'status' => 'success',
                'data' => [
                    'assignees' => $this->taskModel->getAvailableAssignees($projectId),
                    'statuses' => ['To do', 'Doing', 'Review', 'Done'],
                    'priorities' => ['Low', 'Medium', 'High'],
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải dữ liệu tạo task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function store(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $input = $this->getInput();

            $taskId = $this->taskModel->create($input, $authUser);
            $task = $this->taskModel->findById($taskId);

            $this->json([
                'status' => 'success',
                'message' => 'Tạo task thành công.',
                'data' => $task
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tạo task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $input = $this->getInput();

            $this->taskModel->update($taskId, $input, $authUser);

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật task thành công.',
                'data' => $this->taskModel->findById($taskId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function updateStatus($id = null): void {
        try {
            $authUser = $this->requireRole(['manager', 'employee']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $input = $this->getInput();
            $status = (string)($input['status'] ?? '');

            if ($status === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu trạng thái task.'
                ], 400);
            }

            $this->taskModel->updateStatus($taskId, $status, $authUser);

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật trạng thái task thành công.',
                'data' => $this->taskModel->findById($taskId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật trạng thái task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $this->taskModel->delete($taskId, $authUser);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xoá task.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xoá task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function submit($id = null): void {
        try {
            $authUser = $this->requireRole(['employee']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $this->taskModel->submitForReview($taskId, $authUser);

            $this->json([
                'status' => 'success',
                'message' => 'Đã gửi task sang Review.',
                'data' => $this->taskModel->findById($taskId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể gửi Review: ' . $e->getMessage()
            ], 400);
        }
    }

    public function approve($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $this->taskModel->approve($taskId, $authUser);

            $this->json([
                'status' => 'success',
                'message' => 'Đã duyệt task hoàn thành.',
                'data' => $this->taskModel->findById($taskId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể duyệt task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function reject($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $input = $this->getInput();
            $reason = (string)($input['reason'] ?? $input['reject_reason'] ?? '');

            $this->taskModel->reject($taskId, $reason, $authUser);

            $this->json([
                'status' => 'success',
                'message' => 'Đã trả task về Doing.',
                'data' => $this->taskModel->findById($taskId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể reject task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getReviewTasks(): void {
        try {
            $authUser = $this->requireRole(['manager']);

            $this->json([
                'status' => 'success',
                'data' => $this->taskModel->getReviewTasks($authUser)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải task đang chờ duyệt: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Backward-compatible aliases.
     * Giữ lại để route/JS cũ nếu còn gọi tên cũ không gãy ngay.
     */
    public function getAll(): void {
        $this->index();
    }

    public function getAllTasks(): void {
        $this->index();
    }

    public function getById($id = null): void {
        $this->show($id);
    }

    public function create(): void {
        $this->store();
    }

    public function createTask(): void {
        $this->store();
    }

    public function updateTask($id = null): void {
        $this->update($id);
    }

    public function delete($id = null): void {
        $this->destroy($id);
    }

    public function deleteTask($id = null): void {
        $this->destroy($id);
    }

    public function kanban(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $filters = $this->buildFiltersFromQuery();

            $this->json([
                'status' => 'success',
                'data' => $this->taskModel->getKanbanGrouped($filters, $authUser)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải Kanban: ' . $e->getMessage()
            ], 400);
        }
    }
}