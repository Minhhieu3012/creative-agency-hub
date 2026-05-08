<?php
namespace App\Controllers\Auth;

class LogoutController {
    /**
     * Xử lý đăng xuất và chuyển hướng về trang chủ/đăng nhập qua Router
     */
    public function index() {
        // 1. Xóa toàn bộ dữ liệu session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        // 2. Chuyển hướng về trang login THÔNG QUA Router (index.php)
        // Lưu ý: APP_URL đã được định nghĩa trong index.php
        header("Location: " . '/creative-agency-hub/public/auth/login.php');
        exit;
    }
}