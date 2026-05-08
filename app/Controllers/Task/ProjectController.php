<?php
namespace App\Controllers\Task;

use App\Models\Task\ProjectModel;
use Core\JwtHandler;
use Throwable;

class ProjectController {
    private ProjectModel $projectModel;
    private JwtHandler $jwt;
    private ?array $authUser;

    public function __construct($authUser = null) {
        $this->projectModel = new ProjectModel();
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

    private function normalizeMemberIds($value): array {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        $ids = [];

        foreach ($value as $id) {
            $id = (int)$id;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    public function index(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);

            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
            ];

            $projects = $this->projectModel->all($filters, $authUser);

            $this->json([
                'status' => 'success',
                'data' => $projects
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function options(): void {
        try {
            $this->requireRole(['admin', 'manager']);

            $this->json([
                'status' => 'success',
                'data' => [
                    'clients' => $this->projectModel->getAvailableClients(),
                    'employees' => $this->projectModel->getAvailableEmployees(),
                    'statuses' => ['Active', 'Completed', 'Archived']
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải dữ liệu tạo dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID dự án.'
                ], 400);
            }

            if (!$this->projectModel->canUserAccessProject($authUser, $projectId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem dự án này.'
                ], 403);
            }

            $project = $this->projectModel->findById($projectId);

            if (!$project) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy dự án.'
                ], 404);
            }

            $this->json([
                'status' => 'success',
                'data' => $project
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function store(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $input = $this->getInput();

            $input['member_ids'] = $this->normalizeMemberIds($input['member_ids'] ?? []);

            $projectId = $this->projectModel->create($input, (int)$authUser['id']);
            $project = $this->projectModel->findById($projectId);

            $this->json([
                'status' => 'success',
                'message' => 'Tạo dự án thành công.',
                'data' => $project
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tạo dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID dự án.'
                ], 400);
            }

            $input = $this->getInput();

            if (array_key_exists('member_ids', $input)) {
                $input['member_ids'] = $this->normalizeMemberIds($input['member_ids']);
            }

            $this->projectModel->update($projectId, $input, (int)$authUser['id']);
            $project = $this->projectModel->findById($projectId);

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật dự án thành công.',
                'data' => $project
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function delete($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID dự án.'
                ], 400);
            }

            $this->projectModel->archive($projectId, (int)$authUser['id']);

            $this->json([
                'status' => 'success',
                'message' => 'Đã lưu trữ dự án.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể lưu trữ dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function members($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID dự án.'
                ], 400);
            }

            if (!$this->projectModel->canUserAccessProject($authUser, $projectId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem thành viên dự án này.'
                ], 403);
            }

            $this->json([
                'status' => 'success',
                'data' => $this->projectModel->getMembers($projectId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải thành viên dự án: ' . $e->getMessage()
            ], 400);
        }
    }

    public function addMember($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);
            $input = $this->getInput();
            $employeeId = (int)($input['employee_id'] ?? 0);
            $roleInProject = (string)($input['role_in_project'] ?? 'member');

            if (!$projectId || !$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID dự án hoặc ID nhân viên.'
                ], 400);
            }

            $project = $this->projectModel->findById($projectId);

            if (!$project) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy dự án.'
                ], 404);
            }

            if ((int)$project['manager_id'] !== (int)$authUser['id']) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn chỉ được thêm thành viên vào dự án do mình quản lý.'
                ], 403);
            }

            $this->projectModel->addMember($projectId, $employeeId, (int)$authUser['id'], $roleInProject);

            $this->json([
                'status' => 'success',
                'message' => 'Đã thêm thành viên vào dự án.',
                'data' => $this->projectModel->getMembers($projectId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể thêm thành viên: ' . $e->getMessage()
            ], 400);
        }
    }

    public function removeMember($id = null, $employeeId = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);
            $memberId = $this->resolveId($employeeId);

            if (!$projectId || !$memberId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID dự án hoặc ID nhân viên.'
                ], 400);
            }

            $project = $this->projectModel->findById($projectId);

            if (!$project) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy dự án.'
                ], 404);
            }

            if ((int)$project['manager_id'] !== (int)$authUser['id']) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn chỉ được xoá thành viên khỏi dự án do mình quản lý.'
                ], 403);
            }

            $this->projectModel->removeMember($projectId, $memberId);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xoá thành viên khỏi dự án.',
                'data' => $this->projectModel->getMembers($projectId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xoá thành viên: ' . $e->getMessage()
            ], 400);
        }
    }
}