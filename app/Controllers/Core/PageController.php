<?php
namespace App\Controllers\Core;

class PageController {
    
    // 1. Render giao diện
    public function showContact() {
        require BASE_PATH . '/app/View/client-portal/contact.php';
    }

    public function showPrivacy() {
        require BASE_PATH . '/app/View/client-portal/privacy.php';
    }

    public function showTerms() {
        require BASE_PATH . '/app/View/client-portal/terms.php';
    }

    // 2. Xử lý API gửi form liên hệ
    public function handleContact() {
        header('Content-Type: application/json; charset=utf-8');
        
        // Bạn có thể lấy $input = json_decode(file_get_contents('php://input'), true);
        // và lưu vào DB hoặc gửi Email tại đây.
        
        // Trả về JSON giả lập thành công để form.js hiển thị Toast
        echo json_encode([
            "status" => "success", 
            "message" => "Cảm ơn bạn! Chúng tôi đã nhận được yêu cầu và sẽ phản hồi sớm nhất."
        ]);
    }
}