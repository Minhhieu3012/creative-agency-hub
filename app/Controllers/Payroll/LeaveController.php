<?php
namespace App\Controllers\Payroll;

use Core\Database;
use App\Middleware\AuthMiddleware;
use Exception;
use PDO;

class LeaveController {
    private $authUser;
    private $pdo;

    /**
     * Khởi tạo Controller.
     * Ưu tiên User được truyền từ Router, nếu không sẽ tự check Middleware.
     */
    public function __construct($authUser = null) {
        $this->pdo = Database::getConnection();
        
        if ($authUser) {
            $this->authUser = $authUser;
        } else {
            try {
                $this->authUser = AuthMiddleware::check();
            } catch (Exception $e) {
                $this->authUser = null;
            }
        }
    }

    /**
     * API: GET /api/leaves
     * Lấy quỹ phép và lịch sử đơn của cá nhân nhân viên.
     */
    public function index() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'];

            // 1. Lấy quỹ phép hiện tại
            $stmtEmp = $this->pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ?");
            $stmtEmp->execute([$emp_id]);
            $balance = $stmtEmp->fetchColumn();

            // 2. Lấy lịch sử nghỉ phép của cá nhân
            $stmtHistory = $this->pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
            $stmtHistory->execute([$emp_id]);
            $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'balance' => $balance !== false ? floatval($balance) : 0,
                    'history' => $history ?: []
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * API: GET /api/admin/leaves
     * Lấy danh sách toàn bộ đơn đang chờ duyệt (Dành cho Quản lý).
     */
    public function adminIndex() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            
            // Chuyển role về chữ thường để so sánh chính xác tuyệt đối[cite: 3]
            $role = strtolower($this->authUser['role'] ?? 'employee');
            if ($role === 'employee') {
                throw new Exception('Bạn không có quyền truy cập trung tâm phê duyệt.');
            }

            $stmt = $this->pdo->prepare("
                SELECT lr.*, e.full_name as employee_name 
                FROM leave_requests lr 
                JOIN employees e ON lr.employee_id = e.id 
                WHERE lr.status = 'Pending' 
                ORDER BY lr.created_at ASC
            ");
            $stmt->execute();
            $pendingLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Luôn trả về mảng, ngay cả khi rỗng để tránh lỗi phía Frontend
            echo json_encode(['status' => 'success', 'data' => $pendingLeaves ?: []]);
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * API: POST /api/leaves
     * Nhân viên gửi đơn nghỉ phép mới.
     */
    public function store() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;

            if (!$emp_id) throw new Exception("Không tìm thấy ID người dùng trong phiên làm việc.");

            $data = json_decode(file_get_contents("php://input"), true);
            $start_date = $data['start_date'] ?? '';
            $end_date   = $data['end_date'] ?? '';
            $leave_type = $data['leave_type'] ?? 'annual';
            $reason     = $data['reason'] ?? '';

            if (empty($start_date) || empty($end_date) || empty($reason)) {
                throw new Exception('Vui lòng điền đầy đủ ngày bắt đầu, ngày kết thúc và lý do.');
            }

            // Tính số ngày xin nghỉ (Bao gồm ngày bắt đầu và kết thúc)
            $days_requested = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
            if ($days_requested <= 0) throw new Exception('Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');

            // Kiểm tra quỹ phép hiện tại
            $stmtBalance = $this->pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ?");
            $stmtBalance->execute([$emp_id]);
            $current_balance = floatval($stmtBalance->fetchColumn());

            if ($days_requested > $current_balance) {
                throw new Exception("Quỹ phép không đủ. Bạn xin nghỉ {$days_requested} ngày, nhưng chỉ còn {$current_balance} ngày.");
            }

            $stmtInsert = $this->pdo->prepare("
                INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, duration, reason, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $stmtInsert->execute([$emp_id, $leave_type, $start_date, $end_date, $days_requested, $reason]);

            echo json_encode(['status' => 'success', 'message' => 'Gửi đơn nghỉ phép thành công. Vui lòng chờ quản lý duyệt.']);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * API: PATCH /api/leaves/:id/approve
     * Quản lý phê duyệt hoặc từ chối đơn nghỉ phép[cite: 3].
     */
    public function approve($id) {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            $manager_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;
            $role = strtolower($this->authUser['role'] ?? 'employee');

            if ($role === 'employee') throw new Exception('Bạn không có quyền duyệt đơn.');

            $data = json_decode(file_get_contents("php://input"), true);
            $action = $data['action'] ?? ''; // Approved hoặc Rejected

            // Kiểm tra đơn có đang chờ duyệt hay không
            $stmtReq = $this->pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND status = 'Pending'");
            $stmtReq->execute([$id]);
            $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if (!$request) throw new Exception('Đơn nghỉ phép không tồn tại hoặc đã được xử lý trước đó.');

            // Trường hợp: TỪ CHỐI
            if ($action === 'Rejected') {
                $this->pdo->prepare("UPDATE leave_requests SET status = 'Rejected', approved_by = ? WHERE id = ?")
                          ->execute([$manager_id, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Đã từ chối đơn nghỉ phép.']);
                return;
            }

            // Trường hợp: PHÊ DUYỆT (Sử dụng Transaction để trừ phép an toàn)[cite: 3]
            $this->pdo->beginTransaction();
            
            $emp_id = $request['employee_id'];
            $days = floatval($request['duration']);

            // Khóa dòng dữ liệu nhân viên để tránh Race Condition (Tranh chấp dữ liệu)[cite: 3]
            $stmtEmp = $this->pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ? FOR UPDATE");
            $stmtEmp->execute([$emp_id]);
            $old_days = floatval($stmtEmp->fetchColumn());

            if ($old_days < $days) {
                $this->pdo->rollBack();
                throw new Exception('Nhân viên hiện không đủ ngày phép để thực hiện phê duyệt.');
            }

            $new_days = $old_days - $days;

            // 1. Cập nhật trạng thái đơn
            $this->pdo->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ? WHERE id = ?")
                      ->execute([$manager_id, $id]);

            // 2. Cập nhật quỹ phép trong bảng nhân viên
            $this->pdo->prepare("UPDATE employees SET remaining_leave_days = ? WHERE id = ?")
                      ->execute([$new_days, $emp_id]);

            // 3. Ghi log điều chỉnh
            $this->pdo->prepare("
                INSERT INTO employee_leave_adjustments (employee_id, adjustment_days, old_remaining_days, new_remaining_days, reason, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$emp_id, -$days, $old_days, $new_days, "Hệ thống trừ phép do duyệt đơn #$id", $manager_id]);

            $this->pdo->commit();
            echo json_encode(['status' => 'success', 'message' => "Đã phê duyệt và khấu trừ $days ngày phép thành công."]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Private Helper: Kiểm tra xác thực.
     */
    private function ensureAuth() {
        if (!$this->authUser) throw new Exception("Phiên đăng nhập đã hết hạn hoặc không hợp lệ.");
    }
}