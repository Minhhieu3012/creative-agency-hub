<?php
namespace App\Controllers\HRM;

use Core\Database;
use PDO;

class DepartmentController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
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

    public function index(): void {
        $stmt = $this->db->prepare("
            SELECT
                d.*,
                COUNT(e.id) AS employee_count
            FROM departments d
            LEFT JOIN employees e
                ON e.department_id = d.id
                AND e.deleted_at IS NULL
            WHERE d.deleted_at IS NULL
            GROUP BY d.id
            ORDER BY d.id ASC
        ");

        $stmt->execute();

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy danh sách phòng ban thành công',
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    public function store(): void {
        $input = $this->input();
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        if ($name === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Tên phòng ban không được để trống'
            ], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO departments (name, description, status)
                VALUES (:name, :description, 'active')
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description
            ]);

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Tạo phòng ban thành công',
                'data' => [
                    'id' => (int) $this->db->lastInsertId(),
                    'name' => $name,
                    'description' => $description,
                    'status' => 'active'
                ]
            ], 201);
        } catch (\PDOException $e) {
            $message = $e->getCode() === '23000'
                ? 'Tên phòng ban đã tồn tại'
                : $e->getMessage();

            $this->jsonResponse([
                'status' => 'error',
                'message' => $message
            ], 400);
        }
    }

    public function destroy($id): void {
        $departmentId = (int) $id;

        if ($departmentId <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'ID phòng ban không hợp lệ'
            ], 400);
        }

        $countStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM employees
            WHERE department_id = :id
            AND deleted_at IS NULL
            AND status != 'resigned'
        ");

        $countStmt->execute([':id' => $departmentId]);
        $activeCount = (int) $countStmt->fetchColumn();

        if ($activeCount > 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Từ chối xóa. Phòng ban này vẫn còn {$activeCount} nhân viên đang hoạt động.",
                'code' => 'DEPARTMENT_NOT_EMPTY'
            ], 400);
        }

        $getStmt = $this->db->prepare("
            SELECT name
            FROM departments
            WHERE id = :id
            AND deleted_at IS NULL
        ");

        $getStmt->execute([':id' => $departmentId]);
        $name = $getStmt->fetchColumn();

        if (!$name) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy phòng ban'
            ], 404);
        }

        $stmt = $this->db->prepare("
            UPDATE departments
            SET deleted_at = CURRENT_TIMESTAMP,
                status = 'inactive',
                name = :name
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $departmentId,
            ':name' => $name . '_deleted_' . time()
        ]);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Xóa mềm phòng ban thành công'
        ]);
    }
}