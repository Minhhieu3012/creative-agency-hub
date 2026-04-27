<?php
namespace Core;

class Security {
    
    // Chống XSS: Mã hóa các ký tự đặc biệt (<, >, ', ") thành dạng an toàn (HTML entities)
    public static function escape($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::escape($value);
            }
            return $data;
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    // Chống CSRF: Sinh ra Token ngẫu nhiên gán vào Session
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Kiểm tra Token từ form gửi lên có khớp với Session không
    public static function validateCsrfToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $valid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);

        if ($valid) {
            // Xóa token cũ, lần sau sẽ tạo mới
            unset($_SESSION['csrf_token']);
        }

        return $valid;
    }
}