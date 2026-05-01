<?php
namespace App\Controllers\Task;

use App\Middleware\AuthMiddleware;
use App\Models\Task\ProjectModel;

class ProjectController {
    private ProjectModel $projectModel;

    public function __construct() {
        $this->projectModel = new ProjectModel();
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function input(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        return $_POST ?? [];
    }

    private function currentUser(): array {
        return AuthMiddleware::check();
    }

    private function currentUserId(array $authUser): int {
        return (int) ($authUser['id'] ?? $authUser['employee_id'] ?? 0);
    }

    public function index(): void {
        $authUser = $this->currentUser();

        $filters = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
        ];

        $projects = $this->projectModel->getProjectsForUser($authUser, $filters);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy danh sách dự án thành công.',
            'data' => $projects,
        ]);
    }

    public function show($id): void {
        $authUser = $this->currentUser();
        $projectId = (int) $id;

        if ($projectId <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Project ID không hợp lệ.',
            ], 400);
        }

        $project = $this->projectModel->getProjectByIdForUser($projectId, $authUser);

        if (!$project) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy dự án hoặc bạn không có quyền truy cập.',
            ], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy chi tiết dự án thành công.',
            'data' => $project,
        ]);
    }

    public function store(): void {
        $authUser = $this->currentUser();
        $role = strtolower((string) ($authUser['role'] ?? ''));

        if ($role !== 'manager') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Chỉ manager được tạo project.',
            ], 403);
        }

        $input = $this->input();

        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng nhập tên dự án.',
            ], 400);
        }

        $projectId = $this->projectModel->createProject([
            'name' => $name,
            'description' => $input['description'] ?? '',
            'manager_id' => $this->currentUserId($authUser),
            'client_id' => $input['client_id'] ?? null,
            'status' => $input['status'] ?? 'Active',
        ]);

        if (!$projectId) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể tạo project. Vui lòng kiểm tra database.',
            ], 500);
        }

        $project = $this->projectModel->getProjectByIdForUser((int) $projectId, $authUser);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Tạo project thành công.',
            'data' => $project,
        ], 201);
    }

    public function update($id): void {
        $authUser = $this->currentUser();
        $role = strtolower((string) ($authUser['role'] ?? ''));
        $managerId = $this->currentUserId($authUser);
        $projectId = (int) $id;

        if ($role !== 'manager') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Chỉ manager được cập nhật project.',
            ], 403);
        }

        if (!$this->projectModel->isManagerProject($projectId, $managerId)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Bạn không có quyền cập nhật project này.',
            ], 403);
        }

        $input = $this->input();

        $name = trim((string) ($input['name'] ?? ''));

        if ($name === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng nhập tên dự án.',
            ], 400);
        }

        $ok = $this->projectModel->updateProject($projectId, $managerId, [
            'name' => $name,
            'description' => $input['description'] ?? '',
            'client_id' => $input['client_id'] ?? null,
            'status' => $input['status'] ?? 'Active',
        ]);

        if (!$ok) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể cập nhật project.',
            ], 500);
        }

        $project = $this->projectModel->getProjectByIdForUser($projectId, $authUser);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Cập nhật project thành công.',
            'data' => $project,
        ]);
    }

    public function delete($id): void {
        $authUser = $this->currentUser();
        $role = strtolower((string) ($authUser['role'] ?? ''));
        $managerId = $this->currentUserId($authUser);
        $projectId = (int) $id;

        if ($role !== 'manager') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Chỉ manager được xoá project.',
            ], 403);
        }

        if (!$this->projectModel->isManagerProject($projectId, $managerId)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Bạn không có quyền xoá project này.',
            ], 403);
        }

        $ok = $this->projectModel->deleteProject($projectId, $managerId);

        if (!$ok) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể xoá project.',
            ], 500);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Xoá project thành công.',
            'data' => [
                'id' => $projectId,
            ],
        ]);
    }

    public function options(): void {
        $authUser = $this->currentUser();
        $role = strtolower((string) ($authUser['role'] ?? ''));

        if ($role !== 'manager') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Chỉ manager được lấy danh sách tạo project.',
            ], 403);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy dữ liệu tạo project thành công.',
            'data' => [
                'clients' => $this->projectModel->getClientOptions(),
                'employees' => $this->projectModel->getEmployeeOptions(),
                'statuses' => [
                    ['value' => 'Active', 'label' => 'Đang triển khai'],
                    ['value' => 'Completed', 'label' => 'Hoàn thành'],
                    ['value' => 'Archived', 'label' => 'Lưu trữ'],
                ],
            ],
        ]);
    }
}