<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Employee;
use App\Middleware\AuthMiddleware;
use PDO;
use Exception;

class EmployeeController {
    private $employeeModel;

    public function __construct() {
        $this->employeeModel = new \App\Models\HRM\Employee();
    }
    public function store() {
        try {
            $authUser = AuthMiddleware::check();
            $input = json_decode(file_get_contents('php://input'), true);

            // =========================
            // VALIDATE INPUT
            // =========================
            $required = ['full_name', 'email', 'password', 'department_id', 'position_id', 'role'];

            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new \Exception("Thiếu trường: $field");
                }
            }

            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Email không hợp lệ");
            }

            if (strlen($input['password']) < 6) {
                throw new \Exception("Mật khẩu tối thiểu 6 ký tự");
            }

            // =========================
            // VALIDATE ROLE
            // =========================
            $allowedRoles = ['admin', 'manager', 'employee', 'client'];

            if (!in_array($input['role'], $allowedRoles)) {
                throw new \Exception("Role không hợp lệ");
            }

            // =========================
            // PHÂN QUYỀN
            // =========================

            // employee không được tạo
            if ($authUser['role'] === 'employee') {
                throw new \Exception("Bạn không có quyền tạo user");
            }

            // manager chỉ tạo employee
            if ($authUser['role'] === 'manager' && $input['role'] !== 'employee') {
                throw new \Exception("Manager chỉ được tạo employee");
            }

            // chỉ admin tạo admin
            if ($input['role'] === 'admin' && $authUser['role'] !== 'admin') {
                throw new \Exception("Chỉ admin mới được tạo admin");
            }

            // =========================
            // CHECK EMAIL TRÙNG
            // =========================
            if ($this->employeeModel->findByEmail($input['email'])) {
                throw new \Exception("Email đã tồn tại");
            }

            // =========================
            // TẠO USER
            // =========================
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

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    /**
     * API: Lấy danh sách nhân viên đa năng (Phân trang, Tìm kiếm, Lọc)
     * Giữ nguyên cấu trúc JSON có trường 'message' từ mã nguồn cũ của bạn.
     */
    public function index() {
        $params = [
            'search'        => $_GET['search'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'status'        => $_GET['status'] ?? null,
            'page'          => $_GET['page'] ?? 1,
            'limit'         => $_GET['limit'] ?? 10
        ];

        $result = $this->employeeModel->getList($params);

        header('Content-Type: application/json');
        echo json_encode([
            'status'     => 200,
            'message'    => 'Lấy danh sách nhân viên thành công',
            'data'       => $result['items'],
            'pagination' => $result['pagination']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * API Cập nhật thông tin (Security: Allowlist)
     * Ngăn chặn ghi đè các trường nhạy cảm như lương, ngày phép.
     */
    public function update($id) {
        header('Content-Type: application/json');
        
        // Đọc dữ liệu từ Raw JSON hoặc Form-data
        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        // BẢO MẬT: Chỉ cho phép các trường này được cập nhật (Allowlist)
        // Root Cause: Tránh việc người dùng tự ý cập nhật remaining_leave_days hoặc base_salary
        $allowlist = ['full_name', 'phone', 'gender', 'date_of_birth', 'address'];
        $filteredData = array_intersect_key($input, array_flip($allowlist));

        if (empty($filteredData)) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Không có dữ liệu hợp lệ để cập nhật'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        if ($this->employeeModel->update($id, $filteredData)) {
            echo json_encode([
                'status'  => 200, 
                'message' => 'Cập nhật hồ sơ nhân viên thành công'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 500, 
                'error'  => 'Lỗi hệ thống khi cập nhật dữ liệu'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }

    /**
     * API Upload Avatar an toàn (Security: MIME Check & Storage Cleanup)
     */
    public function uploadAvatar($id) {
        header('Content-Type: application/json');

        // Kiểm tra file có tồn tại và không có lỗi upload
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Vui lòng chọn một file ảnh hợp lệ'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName    = $_FILES['avatar']['name'];
        
        // 1. Kiểm tra MIME Type thực tế (Security) - Không tin vào đuôi file
        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType  = $finfo->file($fileTmpPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($mimeType, $allowedMimes)) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Định dạng không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // 2. Tạo tên file duy nhất (Tránh ghi đè file của người khác)
        $extension   = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = bin2hex(random_bytes(10)) . '.' . $extension;

        // 3. Đường dẫn lưu trữ (đảm bảo thư mục tồn tại)
        $uploadDir = __DIR__ . '/../../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // 4. Xóa ảnh cũ nếu có (Storage Cleanup) để tránh rác server
            $oldAvatar = $this->employeeModel->getAvatar($id);
            if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
                unlink($uploadDir . $oldAvatar);
            }

            // 5. Cập nhật đường dẫn mới vào Database
            $this->employeeModel->updateAvatar($id, $newFileName);

            echo json_encode([
                'status'  => 200, 
                'message' => 'Upload ảnh đại diện thành công', 
                'avatar'  => $newFileName
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 500, 
                'error'  => 'Không thể lưu file vào máy chủ'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }

    /**
     * API Điều chỉnh quỹ phép (Atomic Update) - Giai đoạn 4
     */
    public function adjustLeave($id) {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        $adjustDays = (float)($input['adjust_days'] ?? 0);
        $reason = $input['reason'] ?? 'Điều chỉnh thủ công';

        if ($adjustDays == 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Số ngày điều chỉnh phải khác 0'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        try {
            $this->employeeModel->adjustLeaveBalance($id, $adjustDays, $reason);
            echo json_encode([
                'status'  => 200, 
                'message' => 'Cập nhật quỹ phép và ghi log thành công'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }
}