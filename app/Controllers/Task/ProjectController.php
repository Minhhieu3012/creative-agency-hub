<?php
namespace App\Controllers\Task;

use App\Services\Task\ProjectService;
use App\Middleware\AuthMiddleware;
use Throwable;

class ProjectController {
    private ProjectService $service;

    public function __construct() {
        $authUser = AuthMiddleware::check();
        $this->service = new ProjectService($authUser);
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
        $input = json_decode(file_get_contents('php://input'), true);

        return is_array($input) ? $input : ($_POST ?? []);
    }

    private function resolveId($id) {
        if (is_array($id)) {
            return $id['id'] ?? $id[0] ?? null;
        }

        return $id;
    }

    public function index(): void {
        try {
            $this->json([
                "status" => "success",
                "data" => $this->service->getAll()
            ]);
        } catch (Throwable $e) {
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 403);
        }
    }

    public function show($id): void {
        try {
            $project = $this->service->getById($this->resolveId($id));

            $this->json([
                "status" => "success",
                "data" => $project
            ]);
        } catch (Throwable $e) {
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 404);
        }
    }

    public function store(): void {
        try {
            $id = $this->service->create($this->getInput());

            $this->json([
                "status" => "success",
                "message" => "Tạo project thành công",
                "data" => ["id" => $id]
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function update($id): void {
        try {
            $this->service->update($this->resolveId($id), $this->getInput());

            $this->json([
                "status" => "success",
                "message" => "Cập nhật thành công"
            ]);
        } catch (Throwable $e) {
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 400);
        }
    }

    public function delete($id): void {
        try {
            $this->service->delete($this->resolveId($id));

            $this->json([
                "status" => "success",
                "message" => "Xóa thành công"
            ]);
        } catch (Throwable $e) {
            $this->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 400);
        }
    }
}