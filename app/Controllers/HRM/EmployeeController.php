<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Employee;
use Core\Database;
use Core\JwtHandler;
use PDO;
use Exception;

class EmployeeController {
    private $db;
    private $jwt;
    private $employeeModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->jwt = new JwtHandler();
        $this->employeeModel = new \App\Models\HRM\Employee();
    }

    /**
     * API: Tạo nhân sự mới
     */
    public function store() {
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            $token = str_replace("Bearer ", "", $authHeader);
            $authUser = $this->jwt->decode($token);
            
            if (!$authUser) {
                http_response_code(401);
                throw new Exception("Unauthorized: Hết phiên đăng nhập.");
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) throw new Exception("Dữ liệu không hợp lệ.");

            // VALIDATION BẮT BUỘC
            if (empty($input['full_name']) || empty($input['email']) || empty($input['password']) || empty($input['role'])) {
                http_response_code(400);
                throw new Exception("Vui lòng điền đầy đủ các trường bắt buộc (Tên, Email, Mật khẩu, Vai trò).");
            }

            // PHÂN QUYỀN CHẶT CHẼ
            if ($authUser['role'] === 'employee') {
                http_response_code(403);
                throw new Exception("Bạn không có quyền thực hiện hành động này.");
            }
            if ($authUser['role'] === 'manager' && $input['role'] !== 'employee') {
                http_response_code(403);
                throw new Exception("Quản lý chỉ được phép tạo tài khoản cấp nhân viên.");
            }

            // KIỂM TRA TRÙNG EMAIL TRONG DATABASE
            if ($this->employeeModel->findByEmail($input['email'])) {
                http_response_code(409);
                throw new Exception("Email này đã tồn tại trong hệ thống. Vui lòng dùng email khác.");
            }

            // Gửi dữ liệu xuống Model
            $id = $this->employeeModel->create([
                'department_id' => $input['department_id'] ?? null,
                'position_id'   => $input['position_id'] ?? null,
                'manager_id'    => $authUser['id'],
                'employee_code' => 'EMP' . time(),
                'full_name'     => trim($input['full_name']),
                'email'         => trim($input['email']),
                'password'      => password_hash($input['password'], PASSWORD_BCRYPT),
                'role'          => $input['role'],
                'phone'         => $input['phone'] ?? null,
                'status'        => 'active',
                'hire_date'     => date('Y-m-d')
            ]);

            echo json_encode(["status" => "success", "message" => "Tạo nhân sự thành công!", "id" => $id]);

        } catch (Exception $e) {
            if (http_response_code() === 200) http_response_code(400);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Lấy danh sách nhân sự
     */
    public function index() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');
            
            // ROOT CAUSE FIX: Sử dụng 2 biến đại diện riêng biệt (:s1 và :s2)
            $query = "SELECT e.*, d.name as department_name, p.name as position_name 
                      FROM employees e 
                      LEFT JOIN departments d ON e.department_id = d.id 
                      LEFT JOIN positions p ON e.position_id = p.id 
                      WHERE (e.full_name LIKE :s1 OR e.email LIKE :s2)";
            
            if ($status) $query .= " AND e.status = :status";
            $query .= " ORDER BY e.id DESC";

            $stmt = $this->db->prepare($query);
            
            // Bind riêng biệt cho từng biến
            $stmt->bindValue(':s1', "%$search%");
            $stmt->bindValue(':s2', "%$search%");
            
            if ($status) $stmt->bindValue(':status', $status);
            $stmt->execute();
            
            echo json_encode(["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "SQLSTATE[" . $e->getCode() . "]: " . $e->getMessage()]);
        }
        exit;
    }
}