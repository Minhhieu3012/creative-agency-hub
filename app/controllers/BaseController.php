<?php
namespace App\Controllers;

class BaseController {

    // ==========================================
    // CÁC HÀM DÀNH CHO API (TRẢ VỀ JSON)
    // ==========================================

    protected function success($data = [], $message = "Success") {
        echo json_encode([
            "status" => "success",
            "message" => $message,
            "data" => $data
        ]);
    }

    protected function error($message = "Error", $code = 400) {
        http_response_code($code);

        echo json_encode([
            "status" => "error",
            "message" => $message
        ]);
    }

    // ==========================================
    // HÀM DÀNH CHO WEB (TRẢ VỀ GIAO DIỆN HTML)
    // ==========================================

    /**
     * Hàm dùng để gọi và hiển thị file giao diện (View)
     * 
     * @param string $view Tên đường dẫn file view (ví dụ: 'auth/login')
     * @param array $data Dữ liệu truyền từ Controller sang View (nếu có)
     */
    protected function render($view, $data = []) {
        // BẮT BUỘC THÊM DÒNG NÀY: Ép trình duyệt phải hiểu đây là trang web (HTML)
        // Nó sẽ đè lên cấu hình JSON mặc định của hệ thống API
        header('Content-Type: text/html; charset=utf-8');

        // Giải nén mảng data thành các biến độc lập
        extract($data);

        // Định vị đường dẫn tuyệt đối tới thư mục View
        $viewFile = dirname(__DIR__) . '/View/' . $view . '.php';

        // Kiểm tra xem file có tồn tại không trước khi nhúng vào
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            http_response_code(404);
            echo "<div style='font-family: sans-serif; padding: 20px; color: #ba1a1a;'>";
            echo "<h2>Lỗi hệ thống (MVC View Error):</h2>";
            echo "<p>Không tìm thấy file giao diện tại: <strong>{$viewFile}</strong></p>";
            echo "</div>";
        }
    }
}