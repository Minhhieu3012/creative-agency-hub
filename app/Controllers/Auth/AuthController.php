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

        // QUAN TRỌNG: Với Login, không nên dùng Security::escape cho Email 
        // vì nó có thể làm thay đổi chuỗi tìm kiếm trong Database.
        // Chỉ dùng trim() để xóa khoảng trắng dư thừa.
        $email    = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? $input['password'] : '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ email và mật khẩu"]);
            return;
        }

        // Tìm user trong DB (Đảm bảo Model User.php đã trỏ vào bảng 'users')
        $user = $this->userModel->findByEmail($email);

        // Kiểm tra mật khẩu
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
                        "full_name" => $user['full_name'] ?? '',
                        "role"      => $user['role']
                    ]
                ]
            ]);
        } else {
            // Log lỗi để bạn kiểm tra trong php_error_log nếu cần
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

    public function loginInternal() {
        header('Content-Type: application/json; charset=utf-8');

        $input = $this->getInputData();

        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

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

        // ❌ CHẶN CLIENT
        if ($user['role'] === 'client') {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Vui lòng đăng nhập ở trang Client"]);
            return;
        }

        // ❌ CHẶN ACCOUNT BỊ KHÓA
        if ($user['status'] !== 'active') {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Tài khoản bị khóa"]);
            return;
        }

        $token = $this->jwt->encode([
            'id' => $user['id'],
            'role' => $user['role'],
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
    public function loginClient() {
        header('Content-Type: application/json; charset=utf-8');

        $input = $this->getInputData();

        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

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

        // ❌ CHỈ CHO CLIENT
        if ($user['role'] !== 'client') {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Bạn không phải client"]);
            return;
        }

        if ($user['status'] !== 'active') {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Tài khoản bị khóa"]);
            return;
        }

        $token = $this->jwt->encode([
            'id' => $user['id'],
            'role' => $user['role'],
            'email' => $user['email']
        ]);

        echo json_encode([
            "status"=>"success",
            "message"=>"Đăng nhập client thành công",
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
    public function registerClient() {
        header('Content-Type: application/json; charset=utf-8');

        $input = $this->getInputData();

        $fullName = trim($input['full_name'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        // VALIDATE
        if (empty($fullName) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>"Thiếu thông tin"]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>"Email không hợp lệ"]);
            return;
        }

        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>"Mật khẩu tối thiểu 6 ký tự"]);
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(["status"=>"error","message"=>"Email đã tồn tại"]);
            return;
        }

        $id = $this->userModel->create([
            'full_name'=>$fullName,
            'email'=>$email,
            'password'=>$password,
            'role'=>'client'
        ]);

        echo json_encode([
            "status"=>"success",
            "message"=>"Đăng ký client thành công",
            "data"=>["id"=>$id]
        ]);
    }
}