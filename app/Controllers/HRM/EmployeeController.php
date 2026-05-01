<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Employee;
use App\Middleware\AuthMiddleware;
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
        $this->jwt = new JwtHandler();
        // Giữ lại Model để sử dụng các logic nghiệp vụ phức tạp (getList, adjustLeave...)
        $this->employeeModel = new \App\Models\HRM\Employee();
    }

    /**
     * API: Tạo nhân sự mới
     * Kết hợp logic Phân quyền và Validation từ mã nguồn cũ
     */
    public function store() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $authUser = AuthMiddleware::check();
            $input = json_decode(file_get_contents('php://input'), true);

            // VALIDATE INPUT
            $required = ['full_name', 'email', 'password', 'department_id', 'position_id', 'role'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Thiếu trường: $field");
                }
            }

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email không hợp lệ");
            }

            // PHÂN QUYỀN
            $allowedRoles = ['admin', 'manager', 'employee', 'client'];
            if (!in_array($input['role'], $allowedRoles)) {
                throw new Exception("Role không hợp lệ");
            }

            if ($authUser['role'] === 'employee') {
                throw new Exception("Bạn không có quyền tạo user");
            }

            if ($authUser['role'] === 'manager' && $input['role'] !== 'employee') {
                throw new Exception("Manager chỉ được tạo employee");
            }

            // CHECK EMAIL TRÙNG
            if ($this->employeeModel->findByEmail($input['email'])) {
                throw new Exception("Email đã tồn tại");
            }

            // TẠO USER
            $employee_code = 'EMP' . time();
            $id = $this->employeeModel->create([
                'department_id' => $input['department_id'],
                'position_id'   => $input['position_id'],
                'manager_id'    => $input['manager_id'] ?? null,
                'employee_code' => $employee_code,
                'full_name'     => $input['full_name'],
                'email'         => $input['email'],
                'password'      => password_hash($input['password'], PASSWORD_BCRYPT),
                'role'          => $input['role'],
                'phone'         => $input['phone'] ?? null,
                'status'        => 'active',
                'hire_date'     => date('Y-m-d')
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Tạo user thành công",
                "data" => ["id" => $id]
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * API: Lấy danh sách nhân viên (Phân trang, Tìm kiếm)
     * Sử dụng Model logic từ mã nguồn cũ
     */
    public function index() {
        header('Content-Type: application/json; charset=utf-8');
        $params = [
            'search'        => $_GET['search'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'status'        => $_GET['status'] ?? null,
            'page'          => $_GET['page'] ?? 1,
            'limit'         => $_GET['limit'] ?? 10
        ];

        $result = $this->employeeModel->getList($params);

        echo json_encode([
            'status'     => 200,
            'message'    => 'Lấy danh sách nhân viên thành công',
            'data'       => $result['items'],
            'pagination' => $result['pagination']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * API: Lấy chi tiết nhân sự để chỉnh sửa
     * FIX ROOT CAUSE: Method 'show' phục vụ giao diện sửa
     */
    public function show($id) {
        header('Content-Type: application/json; charset=utf-8');
        
        $headers = getallheaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'] ?? '');
        
        if (!$this->jwt->decode($token)) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id, full_name, email, department_id, position_id, status 
                FROM employees 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Không tìm thấy nhân sự"]);
                return;
            }

            echo json_encode([
                "status" => "success", 
                "data" => ["employee" => $employee]
            ]);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }

    /**
     * API: Cập nhật thông tin nhân sự
     * Kết hợp Allowlist bảo mật và logic Cập nhật đa trường
     */
    public function update($id) {
        header('Content-Type: application/json; charset=utf-8');
        
        $headers = getallheaders();
        $token = str_replace("Bearer ", "", $headers['Authorization'] ?? '');
        if (!$this->jwt->decode($token)) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // BẢO MẬT: Allowlist các trường được phép cập nhật
        $allowlist = [
            'full_name', 'phone', 'gender', 'date_of_birth', 'address', 
            'department_id', 'position_id', 'status'
        ];
        $filteredData = array_intersect_key($input, array_flip($allowlist));

        if (empty($filteredData)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Không có dữ liệu hợp lệ để cập nhật"]);
            return;
        }

        try {
            // Sử dụng Direct PDO để cập nhật chính xác các trường từ UI chỉnh sửa
            $stmt = $this->db->prepare("
                UPDATE employees 
                SET department_id = :dept, 
                    position_id = :pos, 
                    status = :status,
                    full_name = :name
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':dept'   => $filteredData['department_id'] ?? null,
                ':pos'    => $filteredData['position_id'] ?? null,
                ':status' => $filteredData['status'] ?? 'active',
                ':name'   => $filteredData['full_name'] ?? '',
                ':id'     => $id
            ]);

            echo json_encode([
                "status" => "success", 
                "message" => "Cập nhật hồ sơ nhân viên thành công"
            ]);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống: " . $e->getMessage()]);
        }
    }

    /**
     * API: Upload Avatar (Bảo mật MIME & Xóa ảnh cũ)
     */
    public function uploadAvatar($id) {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['status' => "error", 'message' => 'Vui lòng chọn một file ảnh hợp lệ']);
            return;
        }

        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType  = $finfo->file($fileTmpPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($mimeType, $allowedMimes)) {
            http_response_code(400);
            echo json_encode(['status' => "error", 'message' => 'Định dạng không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF']);
            return;
        }

        $newFileName = bin2hex(random_bytes(10)) . '.' . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $uploadDir = __DIR__ . '/../../../public/uploads/avatars/';
        
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
            // Xóa ảnh cũ để tiết kiệm dung lượng
            $oldAvatar = $this->employeeModel->getAvatar($id);
            if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) unlink($uploadDir . $oldAvatar);

            $this->employeeModel->updateAvatar($id, $newFileName);

            echo json_encode([
                'status'  => "success", 
                'message' => 'Upload ảnh đại diện thành công', 
                'avatar'  => $newFileName
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => "error", 'message' => 'Không thể lưu file vào máy chủ']);
        }
    }

    /**
     * API: Điều chỉnh quỹ phép (Atomic Update)
     */
    public function adjustLeave($id) {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        $adjustDays = (float)($input['adjust_days'] ?? 0);
        $reason = $input['reason'] ?? 'Điều chỉnh thủ công';

        if ($adjustDays == 0) {
            http_response_code(400);
            echo json_encode(['status' => "error", 'message' => 'Số ngày điều chỉnh phải khác 0']);
            return;
        }

        try {
            $this->employeeModel->adjustLeaveBalance($id, $adjustDays, $reason);
            echo json_encode(['status' => "success", 'message' => 'Cập nhật quỹ phép thành công']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => "error", 'message' => $e->getMessage()]);
        }
    }
}