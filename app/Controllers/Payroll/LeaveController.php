<?php
namespace App\Controllers;

use Core\Database;
use Exception;

class LeaveController {
    private $authUser;

    public function __construct($authUser) {
        $this->authUser = $authUser;
    }

    // ==========================================
    // API 1: GỬI ĐƠN NGHỈ PHÉP (Dành cho Nhân viên)
    // POST /api/leaves
    // ==========================================
    public function store() {
        try {
            $pdo = Database::getConnection();
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;

            // Đọc dữ liệu JSON gửi lên từ Client
            $data = json_decode(file_get_contents("php://input"), true);
            $start_date = $data['start_date'] ?? '';
            $end_date = $data['end_date'] ?? '';
            $reason = $data['reason'] ?? '';

            if (empty($start_date) || empty($end_date) || empty($reason)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ ngày bắt đầu, kết thúc và lý do.']);
                return;
            }

            // Tính số ngày xin nghỉ
            $days_requested = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;

            if ($days_requested <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Ngày kết thúc phải sau ngày bắt đầu.']);
                return;
            }

            // Kiểm tra quỹ phép còn lại
            $stmtBalance = $pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ?");
            $stmtBalance->execute([$emp_id]);
            $leave_balance = floatval($stmtBalance->fetchColumn());

            if ($days_requested > $leave_balance) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error', 
                    'message' => "Quỹ phép không đủ. Bạn xin nghỉ {$days_requested} ngày, nhưng chỉ còn {$leave_balance} ngày."
                ]);
                return;
            }

            // Thêm đơn vào Database với trạng thái Pending
            $stmtInsert = $pdo->prepare("INSERT INTO leave_requests (employee_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmtInsert->execute([$emp_id, $start_date, $end_date, $reason]);

            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Gửi đơn nghỉ phép thành công. Vui lòng chờ duyệt.']);

        } catch (Exception $e) {
            error_log("Lỗi gửi đơn phép: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // API 2: DUYỆT / TỪ CHỐI ĐƠN (Dành cho Quản lý)
    // PATCH /api/leaves/:id/approve
    // ==========================================
    public function approve($id) {
        try {
            $pdo = Database::getConnection();
            $manager_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;
            $role = $this->authUser['role'] ?? 'employee';

            // 1. Phân quyền: Chặn nhân viên thực hiện thao tác này
            if ($role === 'employee') {
                http_response_code(403); // Forbidden
                echo json_encode(['status' => 'error', 'message' => 'Truy cập bị từ chối. Chỉ Quản lý mới được duyệt đơn.']);
                return;
            }

            $data = json_decode(file_get_contents("php://input"), true);
            $action = $data['action'] ?? ''; // Gửi lên 'Approved' hoặc 'Rejected'

            if (!in_array($action, ['Approved', 'Rejected'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Hành động không hợp lệ (Phải là Approved hoặc Rejected).']);
                return;
            }

            // 2. Tìm đơn đang ở trạng thái Pending
            $stmtReq = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND status = 'Pending'");
            $stmtReq->execute([$id]);
            $request = $stmtReq->fetch();

            if (!$request) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đơn hoặc đơn đã được xử lý trước đó.']);
                return;
            }

            // 3A. XỬ LÝ TỪ CHỐI
            if ($action === 'Rejected') {
                $stmtUpdate = $pdo->prepare("UPDATE leave_requests SET status = 'Rejected', approved_by = ? WHERE id = ?");
                $stmtUpdate->execute([$manager_id, $id]);
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Đã từ chối đơn nghỉ phép.']);
                return;
            }

            // 3B. XỬ LÝ DUYỆT (Sử dụng Transaction để đảm bảo toàn vẹn dữ liệu)
            $pdo->beginTransaction();
            
            $emp_id = $request['employee_id'];
            $days_requested = (strtotime($request['end_date']) - strtotime($request['start_date'])) / 86400 + 1;

            // FOR UPDATE: Khóa dòng dữ liệu của nhân viên này lại không cho tiến trình khác trừ tiền/trừ phép cùng lúc
            $stmtEmp = $pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ? FOR UPDATE");
            $stmtEmp->execute([$emp_id]);
            $old_leave = floatval($stmtEmp->fetchColumn());

            if ($old_leave < $days_requested) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Nhân viên không đủ ngày phép để duyệt.']);
                return;
            }

            $new_leave = $old_leave - $days_requested;

            // Cập nhật trạng thái đơn
            $pdo->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ? WHERE id = ?")
                ->execute([$manager_id, $id]);
            
            // Cập nhật quỹ phép bảng Employees
            $pdo->prepare("UPDATE employees SET remaining_leave_days = ? WHERE id = ?")
                ->execute([$new_leave, $emp_id]);

            // Ghi nhật ký (Leave Adjustments)
            $reason_log = "Hệ thống tự động trừ do duyệt đơn nghỉ phép #" . $id;
            $pdo->prepare("INSERT INTO employee_leave_adjustments (employee_id, adjustment_days, old_remaining_days, new_remaining_days, reason, created_by) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$emp_id, -$days_requested, $old_leave, $new_leave, $reason_log, $manager_id]);

            // Lưu toàn bộ
            $pdo->commit();
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => "Duyệt đơn thành công. Đã trừ {$days_requested} ngày phép."]);

        } catch (Exception $e) {
            // Nếu có bất kỳ lỗi nào xảy ra ở các lệnh SQL, rollback (hủy) toàn bộ thay đổi
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Lỗi duyệt đơn: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }
}