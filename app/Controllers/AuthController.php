<?php
namespace App\Controllers;

use App\Models\User;
use Core\JwtHandler;
use Core\Security;

class AuthController {
    private $userModel;
    private $jwt;
    private $authUser;

    public function __construct($authUser = null) {
        $this->userModel = new User();
        $this->jwt = new JwtHandler();
        $this->authUser = $authUser;
    }

    // Xử lý Đăng nhập
    public function login() {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Làm sạch và validate dữ liệu đầu vào
        $email    = Security::escape($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Email và mật khẩu không được để trống"]);
            return;
        }

        // 2. Tìm user trong DB
        $user = $this->userModel->findByEmail($email);

        // 3. Kiểm tra mật khẩu
        if ($user && password_verify($password, $user['password'])) {
            $payload = [
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role']
            ];

            $token = $this->jwt->encode($payload);

            echo json_encode([
                "status"  => "success",
                "message" => "Đăng nhập thành công",
                "data"    => [
                    "token" => $token,
                    "user"  => [
                        "id"        => $user['id'],
                        "full_name" => $user['full_name'],
                        "role"      => $user['role']
                    ]
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Email hoặc mật khẩu không chính xác"]);
        }
    }

    public function register() {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Lấy và làm sạch dữ liệu
        $fullName     = Security::escape($_POST['full_name'] ?? '');
        $email        = Security::escape($_POST['email'] ?? '');
        $password     = $_POST['password'] ?? '';
        $departmentId = $_POST['department_id'] ?? '';
        $positionId   = $_POST['position_id'] ?? '';
        $employeeCode = Security::escape($_POST['employee_code'] ?? '');

        // 2. Validate cơ bản
        if (empty($fullName) || empty($email) || empty($password) || empty($departmentId) || empty($positionId) || empty($employeeCode)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vui lòng điền đầy đủ các thông tin bắt buộc"]);
            return;
        }

        // 3. Validate format email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Email không đúng định dạng"]);
            return;
        }

        // 4. Validate độ dài password
        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Mật khẩu phải có ít nhất 6 ký tự"]);
            return;
        }

        // 5. Kiểm tra Email đã tồn tại chưa
        if ($this->userModel->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "Email này đã được sử dụng trong hệ thống"]);
            return;
        }

        // 6. Lưu vào Database
        $newUserId = $this->userModel->create([
            'full_name'     => $fullName,
            'email'         => $email,
            'password'      => $password,
            'role'          => 'employee',
            'department_id' => $departmentId,
            'position_id'   => $positionId,
            'employee_code' => $employeeCode
        ]);

        if ($newUserId) {
            http_response_code(201);
            echo json_encode([
                "status"  => "success",
                "message" => "Đăng ký tài khoản thành công",
                "data"    => ["id" => $newUserId]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống khi tạo tài khoản"]);
        }
    }

    // Lấy thông tin user đang đăng nhập
    public function me() {
        header('Content-Type: application/json; charset=utf-8');

        $user = $this->userModel->findById($this->authUser['id']);

        if (!$user) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Không tìm thấy người dùng"]);
            return;
        }

        echo json_encode([
            "status"  => "success",
            "message" => "Lấy thông tin thành công",
            "data"    => $user
        ]);
    }
}