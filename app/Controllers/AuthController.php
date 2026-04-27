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