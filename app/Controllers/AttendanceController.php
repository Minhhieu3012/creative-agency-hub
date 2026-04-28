<?php
namespace App\Controllers;

use Core\Database;
use Exception;

class AttendanceController {
    private $authUser;

    // Nhận thông tin user từ Middleware truyền vào
    public function __construct($authUser) {
        $this->authUser = $authUser;
    }

    // API: POST /api/attendance/checkin
    public function checkIn() {
        try {
            // Khởi tạo kết nối PDO từ Singleton Database
            $pdo = Database::getConnection();
            
            // Lấy ID nhân viên từ payload của JWT 
            // (Giả sử lúc Login bạn lưu ID vào key 'id' hoặc 'employee_id')
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;

            if (!$emp_id) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy ID người dùng trong Token!']);
                return;
            }

            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $today = date('Y-m-d');
            $now_datetime = date('Y-m-d H:i:s');
            
            // 1. Kiểm tra xem hôm nay nhân viên đã check-in chưa
            $stmtCheck = $pdo->prepare("SELECT id FROM attendances WHERE employee_id = ? AND work_date = ?");
            $stmtCheck->execute([$emp_id, $today]);
            if ($stmtCheck->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Bạn đã check-in ngày hôm nay rồi!']);
                return;
            }

            // 2. Tính toán đi muộn (Mốc 08:30:00)
            $start_time_limit = date('Y-m-d 08:30:00');
            $status = ($now_datetime > $start_time_limit) ? 'Late' : 'Present';

            // 3. Thêm dữ liệu vào Database
            $stmt = $pdo->prepare("INSERT INTO attendances (employee_id, work_date, check_in_time, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$emp_id, $today, $now_datetime, $status]);

            // 4. Tạo thông báo trả về
            $msg = "Check-in thành công lúc " . date('H:i:s');
            if ($status == 'Late') {
                $msg .= " (Bạn đã đi muộn)";
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => $msg,
                'data' => [
                    'employee_id' => $emp_id,
                    'check_in_time' => $now_datetime,
                    'status' => $status
                ]
            ]);

        } catch (Exception $e) {
            error_log("Lỗi Check-in: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }
}