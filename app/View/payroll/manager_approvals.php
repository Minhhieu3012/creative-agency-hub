<?php
$pageTitle = 'Phê duyệt | Creative Agency Hub';
$pageCss = ['payroll.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'approvals';
$topbarTitle = 'Manager Approvals';
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

$leaveApprovals = [];

try {
    $stmt = $pdo->prepare(
        "SELECT lr.id, lr.employee_id, e.full_name, e.email, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, e.remaining_leave_days " .
        "FROM leave_requests lr " .
        "JOIN employees e ON lr.employee_id = e.id " .
        "WHERE lr.status = 'Pending' " .
        "ORDER BY lr.created_at DESC"
    );
    $stmt->execute();
    $leaveApprovals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Manager approvals view DB error: ' . $e->getMessage());
}

$taskApprovals = $taskApprovals ?? [];

ob_start();
?>

<?php
$pageHeading = 'Trung tâm Phê duyệt';
$pageSubtitle = 'Xử lý các yêu cầu duyệt task hoàn thành, nghỉ phép và nghiệp vụ nội bộ từ nhân viên.';
$pageAction = '<button class="btn btn-light" type="button" data-payroll-action="mock-save">⇩ Xuất báo cáo</button><button class="btn btn-primary" type="button" data-payroll-action="mock-save">Làm mới dữ liệu</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-shell">
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">☑</div>
            <div class="stat-card-body">
                <span>Task chờ duyệt</span>
                <strong><?php echo count($taskApprovals); ?></strong>
                <small>Cần kiểm tra</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✦</div>
            <div class="stat-card-body">
                <span>Đơn nghỉ phép</span>
                <strong><?php echo count($leaveApprovals); ?></strong>
                <small>Đang chờ phản hồi</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Thời gian xử lý TB</span>
                <strong>2h</strong>
                <small>Trong tuần này</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Yêu cầu quá hạn</span>
                <strong>01</strong>
                <small>Cần xử lý ngay</small>
            </div>
        </article>
    </div>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Danh sách yêu cầu</h2>
                <p class="section-subtitle">Chuyển tab để xem từng nhóm phê duyệt.</p>
            </div>

            <div class="approval-tabs">
                <button class="approval-tab is-active" type="button" data-approval-tab="tasks">
                    Duyệt Task
                </button>
                <button class="approval-tab" type="button" data-approval-tab="leaves">
                    Duyệt Nghỉ phép
                </button>
            </div>
        </div>

        <div class="card-body">
            <section class="approval-panel is-active" data-approval-panel="tasks">
                <div class="approval-list">
                    <?php foreach ($taskApprovals as $item): ?>
                        <article class="approval-card" data-approval-card>
                            <div class="approval-avatar"><?php echo htmlspecialchars($item['initials']); ?></div>

                            <div class="approval-content">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['desc']); ?></p>

                                <div class="approval-meta">
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($item['project']); ?></span>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($item['deadline']); ?></span>
                                    <span class="badge badge-warning"><?php echo htmlspecialchars($item['priority']); ?></span>
                                </div>
                            </div>

                            <div class="approval-actions">
                                <button class="btn btn-danger-soft" type="button" data-payroll-action="reject">
                                    Từ chối
                                </button>
                                <button class="btn btn-light" type="button" data-payroll-action="mock-save">
                                    Yêu cầu làm lại
                                </button>
                                <button class="btn btn-primary" type="button" data-payroll-action="approve">
                                    Duyệt
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="approval-panel" data-approval-panel="leaves">
                <div class="approval-list">
                    <?php if (empty($leaveApprovals)): ?>
                        <p style="text-align: center; color: #666; padding: 20px;">Không có đơn nào đang chờ phê duyệt.</p>
                    <?php endif; ?>

                    <?php foreach ($leaveApprovals as $item): ?>
                        <article class="approval-card" data-approval-card>
                            <div class="approval-avatar"><?php echo htmlspecialchars(strtoupper(substr($item['full_name'], 0, 1) . (strpos($item['full_name'], ' ') !== false ? substr($item['full_name'], strpos($item['full_name'], ' ') + 1, 1) : ''))); ?></div>

                            <div class="approval-content">
                                <h3>Đơn nghỉ phép: <?php echo htmlspecialchars($item['full_name']); ?></h3>
                                <p><?php echo nl2br(htmlspecialchars($item['reason'])); ?></p>

                                <div class="approval-meta">
                                    <span class="badge badge-primary">Từ <?php echo htmlspecialchars(date('d/m/Y', strtotime($item['start_date']))); ?></span>
                                    <span class="badge badge-info">Đến <?php echo htmlspecialchars(date('d/m/Y', strtotime($item['end_date']))); ?></span>
                                    <span class="badge badge-success">Còn <?php echo htmlspecialchars((string) $item['remaining_leave_days']); ?> ngày phép</span>
                                </div>
                            </div>

                            <div class="approval-actions">
                                <button class="btn btn-danger-soft" type="button" data-payroll-action="reject">
                                    Từ chối
                                </button>
                                <button class="btn btn-primary" type="button" data-payroll-action="approve">
                                    Duyệt phép
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>