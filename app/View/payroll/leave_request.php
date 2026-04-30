<?php
$pageTitle = 'Xin nghỉ phép | Creative Agency Hub';
$pageCss = ['payroll.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'leave_request';
$topbarTitle = 'Leave Request';
$brandName = 'Creative Agency Hub';

$baseUrl = $baseUrl ?? (function () {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($scriptName, '/public/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/public'));
    }
    if (strpos($scriptName, '/app/View/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/app/View'));
    }
    $dir = dirname($scriptName);
    return $dir === '/' ? '' : $dir;
})();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db_connect.php';

$employeeId = (int) ($_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? 1);

$leaveHistory = [];
$leaveBalance = ['total' => 12, 'remaining' => 12.0];

try {
    $stmt = $pdo->prepare("SELECT total_leave_days, remaining_leave_days FROM employees WHERE id = ? LIMIT 1");
    $stmt->execute([$employeeId]);
    $balance = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($balance) {
        $leaveBalance['total'] = (int) $balance['total_leave_days'];
        $leaveBalance['remaining'] = (float) $balance['remaining_leave_days'];
    }

    $stmt = $pdo->prepare("SELECT start_date, end_date, reason, status, created_at FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$employeeId]);
    $leaveHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Leave request view DB error: ' . $e->getMessage());
}

ob_start();
?>

<?php
$pageHeading = 'Xin Nghỉ phép';
$pageSubtitle = 'Gửi đơn nghỉ trực tuyến, theo dõi quỹ phép còn lại và lịch sử phê duyệt.';
$pageAction = '<a class="btn btn-light" href="' . htmlspecialchars($baseUrl) . '/app/View/payroll/manager_approvals.php">Xem phê duyệt</a>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-grid">
    <div class="payroll-shell">
        <article class="leave-balance-card">
            <div>
                <h2>Quỹ phép còn lại</h2>

                <div class="leave-balance-number">
                    <strong><?php echo htmlspecialchars((string) $leaveBalance['remaining']); ?></strong>
                    <span>ngày</span>
                </div>

                <p>
                    Tổng phép năm: <?php echo htmlspecialchars((string) $leaveBalance['total']); ?> ngày • Còn lại: <?php echo htmlspecialchars(number_format($leaveBalance['remaining'], 1)); ?> ngày.
                </p>
            </div>

            <button class="btn btn-light" type="button" data-payroll-action="mock-save">
                Xem chính sách nghỉ phép
            </button>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Lịch sử đơn nghỉ</h2>
                <button class="btn btn-soft" type="button" data-payroll-action="mock-save">Lọc</button>
            </div>

            <div class="card-body">
                <div class="leave-history">
                    <?php if (empty($leaveHistory)): ?>
                        <p>Chưa có yêu cầu nghỉ phép nào.</p>
                    <?php endif; ?>

                    <?php foreach ($leaveHistory as $leave): ?>
                        <div class="leave-history-item">
                            <div>
                                <h3>Đơn nghỉ phép</h3>
                                <p><?php echo htmlspecialchars(date('d/m/Y', strtotime($leave['start_date']))); ?> - <?php echo htmlspecialchars(date('d/m/Y', strtotime($leave['end_date']))); ?></p>
                                <p><?php echo nl2br(htmlspecialchars($leave['reason'])); ?></p>
                            </div>

                            <?php
                                $tone = 'info';
                                if ($leave['status'] === 'Approved') {
                                    $tone = 'success';
                                } elseif ($leave['status'] === 'Rejected') {
                                    $tone = 'danger';
                                } elseif ($leave['status'] === 'Pending') {
                                    $tone = 'warning';
                                }
                            ?>

                            <span class="badge badge-<?php echo htmlspecialchars($tone); ?>">
                                <?php echo htmlspecialchars($leave['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </div>

    <article class="card leave-form-card">
        <div class="card-header">
            <h2 class="section-title">Tạo đơn nghỉ mới</h2>
            <p class="section-subtitle">Thông tin sẽ được gửi đến quản lý trực tiếp để phê duyệt.</p>
        </div>

        <div class="card-body">
            <form data-leave-form>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="leave_type">Loại nghỉ</label>
                        <select id="leave_type" class="form-select" name="leave_type" required>
                            <option value="">-- Chọn loại nghỉ --</option>
                            <option value="annual">Nghỉ phép năm</option>
                            <option value="sick">Nghỉ ốm</option>
                            <option value="personal">Nghỉ việc cá nhân</option>
                            <option value="half_day">Nghỉ nửa ngày</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="leave_duration">Số ngày</label>
                        <input id="leave_duration" class="form-control" type="number" min="0.5" step="0.5" name="duration" placeholder="VD: 1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Từ ngày</label>
                        <input id="start_date" class="form-control" type="date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_date">Đến ngày</label>
                        <input id="end_date" class="form-control" type="date" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Lý do nghỉ</label>
                    <textarea id="reason" class="form-textarea" name="reason" placeholder="Nhập lý do nghỉ phép..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="attachment">Tài liệu đính kèm</label>
                    <input id="attachment" class="form-control" type="file" name="attachment">
                </div>

                <button class="btn btn-primary btn-block" type="submit">
                    Gửi đơn nghỉ phép
                </button>
            </form>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>