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
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);
        return (!empty($json)) ? $json : $_POST;
    }

    /**
     * Xử lý Đăng nhập chung
     */
    public function login() {
        header('Content-Type: application/json; charset=utf-8');
        $input = $this->getInputData();

        $email    = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ email và mật khẩu"]);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $token = $this->jwt->encode([
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role']
            ]);

            echo json_encode([
                "status"  => "success",
                "message" => "Đăng nhập thành công",
                "data"    => [
                    "token" => $token,
                    "user"  => [
                        "id"        => $user['id'],
                        "full_name" => $user['full_name'] ?? '',
                        "role"      => $user['role']
                    ]
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Email hoặc mật khẩu không chính xác"]);
        }
    }

    /**
     * Đăng nhập Nội bộ (Admin, Manager, Employee)
     */
    public function loginInternal() {
        header('Content-Type: application/json; charset=utf-8');
        $input = $this->getInputData();

        // Sử dụng trim() cho cả password để tránh khoảng trắng vô tình
        $email    = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>"DEBUG 1: Thiếu dữ liệu Email hoặc Pass"]);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        // Kiểm tra User và Trạng thái
        if (!$user) {
            http_response_code(401);
            echo json_encode(["status"=>"error","message"=>"DEBUG 2: Tài khoản không tồn tại hoặc bị khóa"]);
            return;
        }

        // KIỂM TRA MẬT KHẨU
        if (!password_verify($password, $user['password'])) {
            // Kiểm tra độ dài hash để cảnh báo nếu bị cắt cụt
            $hashLen = strlen($user['password']);
            http_response_code(401);
            echo json_encode([
                "status"=>"error",
                "message"=>"DEBUG 3: Sai mật khẩu! (Độ dài Hash trong DB: $hashLen ký tự. Chuẩn phải là 60).",
                "debug_info" => [
                    "input_pass" => $password,
                    "db_hash" => $user['password']
                ]
            ]);
            return;
        }

        if ($user['role'] === 'client') {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Vui lòng đăng nhập ở trang Client"]);
            return;
        }

        $token = $this->jwt->encode([
            'id'    => $user['id'],
            'role'  => $user['role'],
            'email' => $user['email']
        ]);

        echo json_encode([
            "status"=>"success",
            "message"=>"Đăng nhập thành công",
            "data"=>[
                "token"=>$token,
                "user"=>[
                    "id"=>$user['id'],
                    "full_name"=>$user['full_name'],
                    "role"=>$user['role']
                ]
            ]
        ]);
    }

    public function register() {
        header('Content-Type: application/json; charset=utf-8');
        $input = $this->getInputData();

        $fullName = Security::escape($input['full_name'] ?? '');
        $email    = Security::escape($input['email'] ?? '');
        $password = $input['password'] ?? ''; // Tuyệt đối không escape password

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

    public function me() {
        header('Content-Type: application/json; charset=utf-8');
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(["status"=>"error","message"=>"Thiếu token"]);
            return;
        }

        $token = str_replace("Bearer ", "", $authHeader);
        $decoded = $this->jwt->decode($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(["status"=>"error","message"=>"Token không hợp lệ"]);
            return;
        }

        $user = $this->userModel->findById($decoded['id']);
        if (!$user) {
            http_response_code(404);
            echo json_encode(["status"=>"error","message"=>"Không tìm thấy user"]);
            return;
        }

        echo json_encode(["status"=>"success", "data"=>["user"=>$user]]);
    }

    public function loginClient() {
        header('Content-Type: application/json; charset=utf-8');
        $input = $this->getInputData();
        $email    = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>"Thiếu email hoặc password"]);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(["status"=>"error","message"=>"Sai tài khoản hoặc mật khẩu"]);
            return;
        }

        if ($user['role'] !== 'client') {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Bạn không phải client"]);
            return;
        }

        $token = $this->jwt->encode(['id' => $user['id'], 'role' => $user['role'], 'email' => $user['email']]);
        echo json_encode(["status"=>"success", "message"=>"Đăng nhập client thành công", "data"=>["token"=>$token, "user"=>$user]]);
    }

    public function registerClient() {
        header('Content-Type: application/json; charset=utf-8');
        $input = $this->getInputData();
        $fullName = trim($input['full_name'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($fullName) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>"Thiếu thông tin"]);
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(["status"=>"error","message"=>"Email đã tồn tại"]);
            return;
        }

        $id = $this->userModel->create(['full_name' => $fullName, 'email' => $email, 'password' => $password, 'role' => 'client']);
        echo json_encode(["status" => "success", "message" => "Đăng ký thành công", "data" => ["id" => $id]]);
    }
}