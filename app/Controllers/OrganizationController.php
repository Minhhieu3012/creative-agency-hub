<?php
namespace App\Controllers;

use Core\Database;
use Core\JwtHandler;

class OrganizationController {
    private $db;
    private $jwt;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->jwt = new JwtHandler();
    }

    /**
     * Lấy toàn bộ dữ liệu cơ cấu tổ chức
     * Bao gồm: Phòng ban, Chức danh, và 10 nhân sự mới nhất (mọi trạng thái)
     */
    public function getOrgData() {
        header('Content-Type: application/json; charset=utf-8');
        
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        $token = str_replace("Bearer ", "", $authHeader);
        $decoded = $this->jwt->decode($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Invalid Token"]);
            return;
        }

        try {
            // 1. Lấy dữ liệu Phòng ban (Chỉ lấy phòng đang hoạt động và không bị xóa)
            $deptStmt = $this->db->query("
                SELECT d.id, d.name, d.description, 
                       (SELECT COUNT(id) FROM employees e WHERE e.department_id = d.id AND e.status = 'active') as employee_count
                FROM departments d 
                WHERE d.deleted_at IS NULL AND d.status = 'active'
                ORDER BY d.id ASC
            ");
            $departments = $deptStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // 2. Lấy dữ liệu Chức danh (Chỉ lấy chức danh đang hoạt động)
            $posStmt = $this->db->query("
                SELECT id, name, description 
                FROM positions 
                WHERE deleted_at IS NULL AND status = 'active'
            ");
            $positions = $posStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // 3. FIX ROOT CAUSE: Lấy toàn bộ nhân sự, không lọc riêng 'active' để tránh bị ẩn
            $empStmt = $this->db->query("
                SELECT e.id, e.full_name, e.email, e.status,
                       d.name as department_name, 
                       p.name as position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                ORDER BY e.created_at DESC 
                LIMIT 10
            ");
            $employees = $empStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            echo json_encode([
                "status" => "success",
                "data" => [
                    "departments" => $departments,
                    "positions"   => $positions,
                    "employees"   => $employees
                ]
            ]);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }

    /**
     * Lưu thông tin phòng ban mới
     */
    public function storeDepartment() {
        header('Content-Type: application/json; charset=utf-8');
        $headers = getallheaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'] ?? '');
        if (!$this->jwt->decode($token)) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => "Tên phòng ban không được để trống"]);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO departments (name, description, status, created_at) VALUES (:name, :description, 'active', NOW())");
            $stmt->execute([':name' => $name, ':description' => $description]);
            echo json_encode(["status" => "success", "message" => "Đã tạo phòng ban thành công"]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi Database: " . $e->getMessage()]);
        }
    }

    /**
     * Lưu thông tin chức danh mới
     */
    public function storePosition() {
        header('Content-Type: application/json; charset=utf-8');
        $headers = getallheaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'] ?? '');
        if (!$this->jwt->decode($token)) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => "Tên chức danh không được để trống"]);
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO positions (name, description, status, created_at) VALUES (:name, :description, 'active', NOW())");
            $stmt->execute([':name' => $name, ':description' => $description]);
            echo json_encode(["status" => "success", "message" => "Đã tạo chức danh thành công"]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi Database: " . $e->getMessage()]);
        }
    }
}