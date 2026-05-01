<?php
namespace App\Controllers\HRM;

use Core\Database;
use PDO;

class PositionController {
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
                p.*,
                COUNT(e.id) AS employee_count
            FROM positions p
            LEFT JOIN employees e
                ON e.position_id = p.id
                AND e.deleted_at IS NULL
            WHERE p.deleted_at IS NULL
            GROUP BY p.id
            ORDER BY p.id ASC
        ");

        $stmt->execute();

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy danh sách chức danh thành công',
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
                'message' => 'Tên chức danh không được để trống'
            ], 400);
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO positions (name, description, status)
                VALUES (:name, :description, 'active')
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description
            ]);

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Tạo chức danh thành công',
                'data' => [
                    'id' => (int) $this->db->lastInsertId(),
                    'name' => $name,
                    'description' => $description,
                    'status' => 'active'
                ]
            ], 201);
        } catch (\PDOException $e) {
            $message = $e->getCode() === '23000'
                ? 'Tên chức danh đã tồn tại'
                : $e->getMessage();

            $this->jsonResponse([
                'status' => 'error',
                'message' => $message
            ], 400);
        }
    }

    public function destroy($id): void {
        $positionId = (int) $id;

        if ($positionId <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'ID chức danh không hợp lệ'
            ], 400);
        }

        $countStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM employees
            WHERE position_id = :id
            AND deleted_at IS NULL
            AND status != 'resigned'
        ");

        $countStmt->execute([':id' => $positionId]);
        $activeCount = (int) $countStmt->fetchColumn();

        if ($activeCount > 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => "Từ chối xóa. Chức danh này đang được gán cho {$activeCount} nhân viên.",
                'code' => 'POSITION_NOT_EMPTY'
            ], 400);
        }

        $getStmt = $this->db->prepare("
            SELECT name
            FROM positions
            WHERE id = :id
            AND deleted_at IS NULL
        ");

        $getStmt->execute([':id' => $positionId]);
        $name = $getStmt->fetchColumn();

        if (!$name) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy chức danh'
            ], 404);
        }

        $stmt = $this->db->prepare("
            UPDATE positions
            SET deleted_at = CURRENT_TIMESTAMP,
                status = 'inactive',
                name = :name
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $positionId,
            ':name' => $name . '_deleted_' . time()
        ]);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Xóa mềm chức danh thành công'
        ]);
    }
}