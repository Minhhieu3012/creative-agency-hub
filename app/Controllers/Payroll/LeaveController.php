<?php
namespace App\Controllers\Payroll;

use Core\Database;
use App\Middleware\AuthMiddleware;
use Exception;
use PDO;

class LeaveController {
    private $authUser;
    private $pdo;

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
     * Lấy quỹ phép và lịch sử đơn của nhân viên[cite: 6]
     */
    public function index() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'];

            $stmtEmp = $this->pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ?");
            $stmtEmp->execute([$emp_id]);
            $balance = $stmtEmp->fetchColumn();

            $stmtHistory = $this->pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
            $stmtHistory->execute([$emp_id]);
            $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'balance' => $balance !== false ? floatval($balance) : 0,
                    'history' => $history
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Nhân viên gửi đơn mới[cite: 6]
     */
    public function store() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? $this->authUser['user_id'] ?? null;

            if (!$emp_id) throw new Exception("Không tìm thấy ID người dùng!");

            $data = json_decode(file_get_contents("php://input"), true);
            $start_date = $data['start_date'] ?? '';
            $end_date   = $data['end_date'] ?? '';
            $leave_type = $data['leave_type'] ?? 'annual';
            $reason     = $data['reason'] ?? '';

            if (empty($start_date) || empty($end_date) || empty($reason)) {
                throw new Exception('Vui lòng điền đầy đủ ngày bắt đầu, ngày kết thúc và lý do.');
            }

            // Tính số ngày nghỉ thực tế (bao gồm cả ngày bắt đầu và kết thúc)[cite: 6]
            $days_requested = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
            if ($days_requested <= 0) throw new Exception('Ngày kết thúc phải sau ngày bắt đầu.');

            // Kiểm tra quỹ phép hiện tại[cite: 6]
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
     * Quản lý duyệt hoặc từ chối đơn
     */
    public function approve($id) {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->ensureAuth();
            $manager_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;
            $role = $this->authUser['role'] ?? 'employee';

            if ($role === 'employee') throw new Exception('Bạn không có quyền duyệt đơn.');

            $data = json_decode(file_get_contents("php://input"), true);
            $action = $data['action'] ?? ''; // Approved hoặc Rejected

            $stmtReq = $this->pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND status = 'Pending'");
            $stmtReq->execute([$id]);
            $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if (!$request) throw new Exception('Đơn không tồn tại hoặc đã xử lý.');

            if ($action === 'Rejected') {
                $this->pdo->prepare("UPDATE leave_requests SET status = 'Rejected', approved_by = ? WHERE id = ?")->execute([$manager_id, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Đã từ chối đơn.']);
                return;
            }

            // Giao dịch trừ phép[cite: 3]
            $this->pdo->beginTransaction();
            $emp_id = $request['employee_id'];
            $days = floatval($request['duration']);

            $stmtEmp = $this->pdo->prepare("SELECT remaining_leave_days FROM employees WHERE id = ? FOR UPDATE");
            $stmtEmp->execute([$emp_id]);
            $old_days = floatval($stmtEmp->fetchColumn());

            if ($old_days < $days) {
                $this->pdo->rollBack();
                throw new Exception('Nhân viên không đủ ngày phép.');
            }

            $new_days = $old_days - $days;
            $this->pdo->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = ? WHERE id = ?")->execute([$manager_id, $id]);
            $this->pdo->prepare("UPDATE employees SET remaining_leave_days = ? WHERE id = ?")->execute([$new_days, $emp_id]);
            $this->pdo->commit();

            echo json_encode(['status' => 'success', 'message' => "Đã duyệt và trừ $days ngày phép."]);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function ensureAuth() {
        if (!$this->authUser) throw new Exception("Phiên đăng nhập không hợp lệ.");
    }
}