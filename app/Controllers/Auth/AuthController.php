<?php
namespace App\Controllers\Auth;

use App\Models\Auth\User;
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

    private function getInputData() {
        $json = json_decode(file_get_contents('php://input'), true);
        return $json ?: $_POST;
    }

    public function login() {
        header('Content-Type: application/json; charset=utf-8');
        
        $input = $this->getInputData();

        // Chỉ escape email, password giữ nguyên để password_verify hoạt động chính xác
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Email và mật khẩu không được để trống"]);
            return;
        }

        // Tìm user trong DB (Bảng users)
        $user = $this->userModel->findByEmail($email);

        // Kiểm tra mật khẩu bằng Bcrypt verify
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
            // Log nhẹ để debug nếu cần
            error_log("Login attempt failed for: " . $email);
            
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Email hoặc mật khẩu không chính xác"]);
        }
    }

    public function register() {
        header('Content-Type: application/json; charset=utf-8');
        $input = $this->getInputData();

        $fullName = Security::escape($input['full_name'] ?? '');
        $email    = Security::escape($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($fullName) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vui lòng điền đầy đủ thông tin"]);
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "Email này đã tồn tại"]);
            return;
        }

        $newUserId = $this->userModel->create([
            'full_name' => $fullName,
            'email'     => $email,
            'password'  => $password,
            'role'      => $input['role'] ?? 'employee'
        ]);

        if ($newUserId) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Tạo tài khoản thành công"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi hệ thống"]);
        }
    }

    public function me() {
        header('Content-Type: application/json; charset=utf-8');
        $user = $this->userModel->findById($this->authUser['id']);
        if (!$user) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Không tìm thấy user"]);
            return;
        }
        echo json_encode(["status" => "success", "data" => $user]);
    }
}