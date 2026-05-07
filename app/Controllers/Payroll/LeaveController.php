<?php
namespace App\Controllers\Payroll;

use Core\Database;
use App\Middleware\AuthMiddleware;
use Exception;
use PDO;
use Throwable;

class LeaveController {
    private $authUser;
    private $pdo;

    /**
     * Khởi tạo Controller.
     * Ưu tiên User được truyền từ Router, nếu không sẽ tự check Middleware.
     */
    public function __construct($authUser = null) {
        $this->pdo = Database::getConnection();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

    private function json(array $payload, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function ensureAuth(): void {
        if (!$this->authUser) {
            throw new Exception("Phiên đăng nhập đã hết hạn hoặc không hợp lệ.");
        }
    }

    private function getEmployeeId(): int {
        $id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;

        if (!$id) {
            throw new Exception("Không tìm thấy ID người dùng trong phiên làm việc.");
        }

        return (int)$id;
    }

    private function getRole(): string {
        return strtolower((string)($this->authUser['role'] ?? 'employee'));
    }

    private function getJsonInput(): array {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    private function resolveId($id): int {
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? 0;
        }

        return (int)$id;
    }

    private function normalizeDate(string $date, string $fieldLabel): string {
        $date = trim($date);

        if ($date === '') {
            throw new Exception("Vui lòng nhập {$fieldLabel}.");
        }

        $dateObject = date_create_from_format('Y-m-d', $date);

        if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
            throw new Exception("{$fieldLabel} không hợp lệ.");
        }

        return $date;
    }

    private function calculateLeaveDays(string $startDate, string $endDate): float {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        if (!$start || !$end) {
            throw new Exception("Ngày nghỉ không hợp lệ.");
        }

        $days = (($end - $start) / 86400) + 1;

        if ($days <= 0) {
            throw new Exception("Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.");
        }

        return (float)$days;
    }

    private function getLeaveTypeLabel(?string $leaveType): string {
        $leaveType = strtolower(trim((string)$leaveType));

        $labels = [
            'annual' => 'Nghỉ phép năm',
            'sick' => 'Nghỉ ốm',
            'personal' => 'Nghỉ việc cá nhân',
            'half_day' => 'Nghỉ nửa ngày',
        ];

        return $labels[$leaveType] ?? 'Nghỉ phép';
    }

    private function buildReasonWithLeaveType(string $leaveType, string $reason): string {
        $label = $this->getLeaveTypeLabel($leaveType);
        return "[{$label}] {$reason}";
    }

    private function createLeaveAdjustment(
        int $employeeId,
        float $adjustmentDays,
        float $oldDays,
        float $newDays,
        string $reason,
        int $createdBy
    ): void {
        $stmtLog = $this->pdo->prepare("
            INSERT INTO employee_leave_adjustments (
                employee_id,
                adjustment_days,
                old_remaining_days,
                new_remaining_days,
                reason,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmtLog->execute([
            $employeeId,
            $adjustmentDays,
            $oldDays,
            $newDays,
            $reason,
            $createdBy
        ]);
    }

    /**
     * API: GET /api/leaves
     * Lấy quỹ phép và lịch sử đơn của cá nhân.
     */
    public function index() {
        try {
            $this->ensureAuth();

            $empId = $this->getEmployeeId();

            $stmtEmp = $this->pdo->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = ?
                LIMIT 1
            ");
            $stmtEmp->execute([$empId]);
            $balance = $stmtEmp->fetchColumn();

            /*
             * Schema hiện tại không có leave_type và duration.
             * duration được tính bằng DATEDIFF.
             * leave_type trả alias để frontend không bị vỡ.
             */
            $stmtHistory = $this->pdo->prepare("
                SELECT
                    lr.id,
                    lr.employee_id,
                    lr.approved_by,
                    lr.start_date,
                    lr.end_date,
                    lr.reason,
                    lr.status,
                    lr.created_at,
                    lr.updated_at,
                    DATEDIFF(lr.end_date, lr.start_date) + 1 AS duration,
                    'annual' AS leave_type,
                    approver.full_name AS approved_by_name
                FROM leave_requests lr
                LEFT JOIN employees approver ON approver.id = lr.approved_by
                WHERE lr.employee_id = ?
                ORDER BY lr.created_at DESC
            ");
            $stmtHistory->execute([$empId]);
            $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => [
                    'balance' => $balance !== false ? (float)$balance : 0,
                    'history' => $history ?: []
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * API: GET /api/admin/leaves
     * Chỉ Admin mới thấy danh sách đơn nghỉ phép chờ duyệt.
     * Manager được trả mảng rỗng để không làm vỡ trang phê duyệt task.
     */
    public function adminIndex() {
        try {
            $this->ensureAuth();

            $role = $this->getRole();

            if ($role === 'employee') {
                throw new Exception('Bạn không có quyền truy cập trung tâm phê duyệt.');
            }

            if ($role !== 'admin') {
                $this->json([
                    'status' => 'success',
                    'message' => 'Chỉ Admin được duyệt đơn nghỉ phép.',
                    'data' => []
                ]);
            }

            $stmt = $this->pdo->prepare("
                SELECT
                    lr.id,
                    lr.employee_id,
                    lr.approved_by,
                    lr.start_date,
                    lr.end_date,
                    lr.reason,
                    lr.status,
                    lr.created_at,
                    lr.updated_at,
                    DATEDIFF(lr.end_date, lr.start_date) + 1 AS duration,
                    'annual' AS leave_type,
                    e.full_name AS employee_name,
                    e.email AS employee_email,
                    e.role AS employee_role,
                    approver.full_name AS approved_by_name
                FROM leave_requests lr
                JOIN employees e ON lr.employee_id = e.id
                LEFT JOIN employees approver ON approver.id = lr.approved_by
                WHERE lr.status = 'Pending'
                ORDER BY lr.created_at ASC
            ");
            $stmt->execute();

            $pendingLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => $pendingLeaves ?: []
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * API: POST /api/leaves
     * Employee/Manager gửi đơn Pending.
     * Admin gửi đơn thì Approved ngay và tự trừ quỹ phép.
     */
    public function store() {
        try {
            $this->ensureAuth();

            $empId = $this->getEmployeeId();
            $role = $this->getRole();

            $data = $this->getJsonInput();

            $startDate = $this->normalizeDate((string)($data['start_date'] ?? ''), 'ngày bắt đầu');
            $endDate = $this->normalizeDate((string)($data['end_date'] ?? ''), 'ngày kết thúc');
            $leaveType = (string)($data['leave_type'] ?? 'annual');
            $reason = trim((string)($data['reason'] ?? ''));

            if ($reason === '') {
                throw new Exception('Vui lòng nhập lý do nghỉ.');
            }

            $daysRequested = $this->calculateLeaveDays($startDate, $endDate);

            $stmtBalance = $this->pdo->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = ?
                LIMIT 1
            ");
            $stmtBalance->execute([$empId]);
            $currentBalance = (float)$stmtBalance->fetchColumn();

            if ($daysRequested > $currentBalance) {
                throw new Exception("Quỹ phép không đủ. Bạn xin nghỉ {$daysRequested} ngày, nhưng chỉ còn {$currentBalance} ngày.");
            }

            $reasonToStore = $this->buildReasonWithLeaveType($leaveType, $reason);

            /*
             * Admin xin nghỉ: duyệt thẳng và trừ phép ngay.
             */
            if ($role === 'admin') {
                $this->pdo->beginTransaction();

                $stmtEmp = $this->pdo->prepare("
                    SELECT remaining_leave_days
                    FROM employees
                    WHERE id = ?
                    LIMIT 1
                    FOR UPDATE
                ");
                $stmtEmp->execute([$empId]);
                $oldDays = (float)$stmtEmp->fetchColumn();

                if ($oldDays < $daysRequested) {
                    throw new Exception("Quỹ phép không đủ. Bạn xin nghỉ {$daysRequested} ngày, nhưng chỉ còn {$oldDays} ngày.");
                }

                $newDays = $oldDays - $daysRequested;

                $stmtInsert = $this->pdo->prepare("
                    INSERT INTO leave_requests (
                        employee_id,
                        approved_by,
                        start_date,
                        end_date,
                        reason,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, 'Approved')
                ");

                $stmtInsert->execute([
                    $empId,
                    $empId,
                    $startDate,
                    $endDate,
                    $reasonToStore
                ]);

                $leaveId = (int)$this->pdo->lastInsertId();

                $stmtUpdateBalance = $this->pdo->prepare("
                    UPDATE employees
                    SET remaining_leave_days = ?
                    WHERE id = ?
                ");
                $stmtUpdateBalance->execute([$newDays, $empId]);

                $this->createLeaveAdjustment(
                    $empId,
                    -$daysRequested,
                    $oldDays,
                    $newDays,
                    "Admin tự duyệt đơn nghỉ phép #{$leaveId}",
                    $empId
                );

                $this->pdo->commit();

                $this->json([
                    'status' => 'success',
                    'message' => "Admin gửi đơn nghỉ phép thành công. Đơn đã được duyệt tự động và khấu trừ {$daysRequested} ngày phép.",
                    'data' => [
                        'id' => $leaveId,
                        'duration' => $daysRequested,
                        'status' => 'Approved'
                    ]
                ], 201);
            }

            /*
             * Employee và Manager xin nghỉ: bắt buộc chờ Admin duyệt.
             */
            $stmtInsert = $this->pdo->prepare("
                INSERT INTO leave_requests (
                    employee_id,
                    approved_by,
                    start_date,
                    end_date,
                    reason,
                    status
                )
                VALUES (?, NULL, ?, ?, ?, 'Pending')
            ");

            $stmtInsert->execute([
                $empId,
                $startDate,
                $endDate,
                $reasonToStore
            ]);

            $this->json([
                'status' => 'success',
                'message' => 'Gửi đơn nghỉ phép thành công. Vui lòng chờ Admin duyệt.',
                'data' => [
                    'id' => (int)$this->pdo->lastInsertId(),
                    'duration' => $daysRequested,
                    'status' => 'Pending'
                ]
            ], 201);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * API: PATCH /api/leaves/:id/approve
     * Chỉ Admin được phê duyệt hoặc từ chối đơn nghỉ phép.
     */
    public function approve($id) {
        try {
            $this->ensureAuth();

            $leaveId = $this->resolveId($id);
            $adminId = $this->getEmployeeId();
            $role = $this->getRole();

            if ($role !== 'admin') {
                throw new Exception('Chỉ Admin mới có quyền duyệt đơn nghỉ phép.');
            }

            $data = $this->getJsonInput();
            $action = $data['action'] ?? '';

            if (!in_array($action, ['Approved', 'Rejected'], true)) {
                throw new Exception('Trạng thái phê duyệt không hợp lệ.');
            }

            if ($action === 'Rejected') {
                $stmtReq = $this->pdo->prepare("
                    SELECT id
                    FROM leave_requests
                    WHERE id = ?
                      AND status = 'Pending'
                    LIMIT 1
                ");
                $stmtReq->execute([$leaveId]);
                $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

                if (!$request) {
                    throw new Exception('Đơn nghỉ phép không tồn tại hoặc đã được xử lý trước đó.');
                }

                $stmtReject = $this->pdo->prepare("
                    UPDATE leave_requests
                    SET status = 'Rejected',
                        approved_by = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtReject->execute([$adminId, $leaveId]);

                $this->json([
                    'status' => 'success',
                    'message' => 'Đã từ chối đơn nghỉ phép.'
                ]);
            }

            $this->pdo->beginTransaction();

            $stmtReq = $this->pdo->prepare("
                SELECT *
                FROM leave_requests
                WHERE id = ?
                  AND status = 'Pending'
                LIMIT 1
                FOR UPDATE
            ");
            $stmtReq->execute([$leaveId]);
            $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception('Đơn nghỉ phép không tồn tại hoặc đã được xử lý trước đó.');
            }

            $employeeId = (int)$request['employee_id'];
            $days = $this->calculateLeaveDays($request['start_date'], $request['end_date']);

            $stmtEmp = $this->pdo->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");
            $stmtEmp->execute([$employeeId]);
            $oldDays = (float)$stmtEmp->fetchColumn();

            if ($oldDays < $days) {
                throw new Exception('Nhân viên hiện không đủ ngày phép để thực hiện phê duyệt.');
            }

            $newDays = $oldDays - $days;

            $stmtApprove = $this->pdo->prepare("
                UPDATE leave_requests
                SET status = 'Approved',
                    approved_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmtApprove->execute([$adminId, $leaveId]);

            $stmtUpdateBalance = $this->pdo->prepare("
                UPDATE employees
                SET remaining_leave_days = ?
                WHERE id = ?
            ");
            $stmtUpdateBalance->execute([$newDays, $employeeId]);

            $this->createLeaveAdjustment(
                $employeeId,
                -$days,
                $oldDays,
                $newDays,
                "Admin duyệt đơn nghỉ phép #{$leaveId}",
                $adminId
            );

            $this->pdo->commit();

            $this->json([
                'status' => 'success',
                'message' => "Đã phê duyệt và khấu trừ {$days} ngày phép thành công."
            ]);
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}