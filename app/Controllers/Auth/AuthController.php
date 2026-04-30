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

    /**
     * Helper: Đọc dữ liệu đầu vào một cách chính xác nhất
     */
    private function getInputData() {
        // Đọc dữ liệu thô từ luồng đầu vào
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);

        // Nếu là JSON hợp lệ thì trả về, nếu không thì lấy từ $_POST (giống Postman)
        return (!empty($json)) ? $json : $_POST;
    }

    /**
     * Xử lý Đăng nhập - Cấu hình lại để khớp với Postman
     */
    public function login() {
        header('Content-Type: application/json; charset=utf-8');
        
        $input = $this->getInputData();

        $email    = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';
        // Nhận diện xem request đến từ cổng nào (mặc định là rỗng nếu không có)
        $portal   = isset($input['portal']) ? $input['portal'] : '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ email và mật khẩu"]);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $role = $user['role'];

            // ==========================================
            // LOGIC KIỂM TRA QUYỀN TRUY CẬP TỪNG PORTAL
            // ==========================================
            
            // 1. Nếu đăng nhập ở cổng Quản trị (internal) nhưng role là client
            if ($portal === 'internal' && $role === 'client') {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Tài khoản khách hàng không thể truy cập cổng quản trị nội bộ."]);
                return;
            }

            // 2. Nếu đăng nhập ở cổng Khách hàng (client) nhưng role là nội bộ
            $internalRoles = ['admin', 'manager', 'employee'];
            if ($portal === 'client' && in_array($role, $internalRoles)) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Tài khoản nhân sự vui lòng đăng nhập tại cổng quản trị nội bộ."]);
                return;
            }
            // ==========================================

            // Nếu vượt qua bài test phân quyền thì mới cấp Token
            $payload = [
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $role
            ];

            $token = $this->jwt->encode($payload);

            echo json_encode([
                "status"  => "success",
                "message" => "Đăng nhập thành công",
                "data"    => [
                    "token" => $token,
                    "user"  => [
                        "id"        => $user['id'],
                        "full_name" => $user['full_name'] ?? '',
                        "role"      => $role
                    ]
                ]
            ]);
        } else {
            error_log("Login failed for email: " . $email);
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Email hoặc mật khẩu không chính xác"]);
        }
    }

    /**
     * Giữ nguyên các hàm khác nhưng đảm bảo dùng getInputData() đồng nhất
     */
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
            echo json_encode(["status" => "error", "message" => "Email đã tồn tại"]);
            return;
        }

        $newUserId = $this->userModel->create([
            'full_name' => $fullName,
            'email'     => $email,
            'password'  => $password,
            'role'      => 'client'
        ]);

        if ($newUserId) {
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Đăng ký thành công"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi server"]);
        }
    }

    /**
     * Hiển thị giao diện Quên mật khẩu
     */
    public function showForgotPasswordForm() {
    // Nhận diện portal từ URL (mặc định là client nếu không có)
    $portal = $_GET['portal'] ?? 'client'; 
    
    // Truyền biến $portal vào view
    require BASE_PATH . '/app/View/client-portal/forgot-password.php';
}

    /**
     * Xử lý API gửi yêu cầu khôi phục mật khẩu
     */
    public function forgotPassword() {
        header('Content-Type: application/json; charset=utf-8');
        
        // Logic thực tế: Kiểm tra email tồn tại -> Tạo token reset -> Gửi email
        
        echo json_encode([
            "status" => "success", 
            "message" => "Nếu email hợp lệ, hướng dẫn khôi phục sẽ được gửi tới hộp thư của bạn trong giây lát."
        ]);
    }

    public function showClientLoginForm() {
        require BASE_PATH . '/app/View/client-portal/login-client.php';
    }
}