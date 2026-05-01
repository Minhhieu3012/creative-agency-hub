<?php
namespace App\Controllers\Auth;

use App\Models\Auth\User;
use Core\JwtHandler;
use Core\Security;
use Exception;

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
     * Helper: Đọc dữ liệu đầu vào từ JSON hoặc POST
     */
    private function getInputData() {
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);
        return (!empty($json)) ? $json : $_POST;
    }

    /**
     * Xử lý Đăng nhập chung
     * Tích hợp: Cookie JWT + Trả về JSON chuẩn Stateless
     */
    public function login() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $input = $this->getInputData();
            $email    = isset($input['email']) ? trim($input['email']) : '';
            $password = isset($input['password']) ? trim($input['password']) : '';

            if (empty($email) || empty($password)) {
                throw new Exception("Vui lòng nhập đầy đủ email và mật khẩu");
            }

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                $role = strtolower($user['role']);

                // Tạo Token chứa thông tin định danh
                $token = $this->jwt->encode([
                    'id'        => $user['id'],
                    'email'     => $user['email'],
                    'role'      => $role,
                    'full_name' => $user['full_name'] ?? ''
                ]);

                // Lưu Cookie làm dự phòng cho Server-side Rendering
                setcookie('cah_token', $token, time() + 86400, '/', '', false, true);

                // Đồng bộ hóa Session (Fallback)
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $role;
                session_write_close(); 

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
                throw new Exception("Email hoặc mật khẩu không chính xác");
            }
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    /**
     * Đăng nhập Nội bộ (Admin, Manager, Employee)
     * KẾT HỢP: Logic DEBUG chi tiết + Cookie JWT + Trả về data cho Sidebar JS
     */
    public function loginInternal() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $input = $this->getInputData();
            $email    = trim($input['email'] ?? '');
            $password = trim($input['password'] ?? '');

            if (empty($email) || empty($password)) {
                throw new Exception('DEBUG 1: Thiếu dữ liệu Email hoặc Password');
            }

            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                throw new Exception('DEBUG 2: Tài khoản không tồn tại hoặc bị khóa');
            }

            if (!password_verify($password, $user['password'])) {
                $hashLen = strlen($user['password']);
                throw new Exception("DEBUG 3: Sai mật khẩu! (Độ dài Hash trong DB: $hashLen ký tự).");
            }

            if ($user['role'] === 'client') {
                http_response_code(403);
                echo json_encode(["status"=>"error","message"=>"Vui lòng đăng nhập ở trang Client"]);
                return;
            }

            $role = strtolower($user['role']);
            
            // Tạo Token
            $token = $this->jwt->encode([
                'id'        => $user['id'],
                'role'      => $role,
                'email'     => $user['email'],
                'full_name' => $user['full_name']
            ]);

            // Lưu Cookie & Session Persistence
            setcookie('cah_token', $token, time() + 86400, '/', '', false, true);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $role;
            session_write_close(); 

            echo json_encode([
                "status" => "success",
                "message"=> "Đăng nhập thành công",
                "data"   => [
                    "token" => $token,
                    "user"  => [
                        "id"        => $user['id'],
                        "full_name" => $user['full_name'],
                        "role"      => $role
                    ]
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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
            echo json_encode(["status"=>"error", "message"=>"Token không hợp lệ"]);
            return;
        }

        $user = $this->userModel->findById($decoded['id']);
        echo json_encode(["status"=>"success", "data"=>["user" => $user]]);
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