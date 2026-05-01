<?php
namespace App\Middleware;

use Core\JwtHandler;

class AuthMiddleware {
    public static function check() {
        $authHeader = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $jwt = new JwtHandler();
            $decoded = $jwt->decode($token);

            if ($decoded) {
                // --- QUAN TRỌNG: CẬP NHẬT SESSION NGAY LẬP TỨC ---
                // Mỗi khi có API gọi lên, ta làm mới Session bằng dữ liệu từ Token
                if (session_status() === PHP_SESSION_NONE) session_start();
                
                $_SESSION['user_id'] = $decoded['id'];
                $_SESSION['user_role'] = strtolower($decoded['role']);
                
                // Ghi dữ liệu xuống đĩa ngay để tránh tranh chấp dữ liệu
                session_write_close(); 
                
                return $decoded;
            }
        }

        // Nếu là yêu cầu API mà không có Token hợp lệ
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            "status"  => "error",
            "message" => "Unauthorized: Phiên làm việc hết hạn. Vui lòng đăng nhập lại."
        ]);
        exit;
    }
}