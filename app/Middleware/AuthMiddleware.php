<?php
namespace App\Middleware;

use Core\JwtHandler;

class AuthMiddleware {
    public static function check() {
        // Lấy Authorization Header - tương thích mọi server
        $authHeader = '';
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
        }

        // Kiểm tra đúng định dạng "Bearer <token>"
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $jwt = new JwtHandler();
            $decoded = $jwt->decode($token);

            if ($decoded) {
                return $decoded;
            }
        }

        // Token không hợp lệ hoặc hết hạn
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode([
            "status"  => "error",
            "message" => "Unauthorized: Bạn chưa đăng nhập hoặc Token đã hết hạn."
        ]);
        exit;
    }
}