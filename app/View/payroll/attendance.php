<?php
$pageTitle = 'Chấm công | Creative Agency Hub';
$pageCss = ['payroll.css', 'hrm.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'attendance';
$topbarTitle = 'Web Check-in';
$brandName = 'Creative Agency Hub';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/db_connect.php';

$employeeId = (int) ($_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? 1);

$history = [];
$attendanceStat = [
    'present_days' => 0,
    'late' => 0,
    'missing_checkout' => 0,
    'status_today' => 'Chưa có dữ liệu',
];

try {
    $today = date('Y-m-d');
    $month = date('m');
    $year = date('Y');

    $stmt = $pdo->prepare("SELECT work_date, check_in_time, check_out_time, status FROM attendances WHERE employee_id = ? ORDER BY work_date DESC LIMIT 10");
    $stmt->execute([$employeeId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? AND status <> 'Absent'");
    $stmt->execute([$employeeId, $month, $year]);
    $attendanceStat['present_days'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? AND status = 'Late'");
    $stmt->execute([$employeeId, $month, $year]);
    $attendanceStat['late'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? AND check_out_time IS NULL");
    $stmt->execute([$employeeId, $month, $year]);
    $attendanceStat['missing_checkout'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT status FROM attendances WHERE employee_id = ? AND work_date = ? LIMIT 1");
    $stmt->execute([$employeeId, $today]);
    $todayRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($todayRecord) {
        $attendanceStat['status_today'] = $todayRecord['status'] === 'Late' ? 'Đi muộn' : ($todayRecord['status'] === 'Present' ? 'Đúng giờ' : $todayRecord['status']);
    }
} catch (PDOException $e) {
    error_log('Attendance view DB error: ' . $e->getMessage());
}

ob_start();
?>

<section class="payroll-shell">
    <article class="attendance-hero">
        <div class="attendance-copy">
            <span>Creative Agency Hub • Web Check-in</span>
            <h1>Chấm công nhanh trong một chạm.</h1>
            <p>
                Ghi nhận giờ vào/ra mỗi ngày, theo dõi trạng thái chuyên cần và hỗ trợ dữ liệu
                cho bảng công, lương và KPI cuối tháng.
            </p>
        </div>

        <div class="attendance-clock-card">
            <div class="attendance-clock">
                <strong data-attendance-clock>--:--:--</strong>
                <small data-attendance-date>Đang tải ngày hiện tại...</small>
            </div>

            <div class="attendance-actions">
                <button class="btn btn-light" type="button" data-payroll-action="check-in">
                    Check-in
                </button>
                <button class="btn btn-emerald" type="button" data-payroll-action="check-out">
                    Check-out
                </button>
            </div>

            <div class="attendance-status">
                <div class="attendance-status-item">
                    <span>Trạng thái hôm nay</span>
                    <strong data-checkin-status><?php echo htmlspecialchars($attendanceStat['status_today']); ?></strong>
                </div>

                <div class="attendance-status-item">
                    <span>Ca làm việc</span>
                    <strong>08:00 - 17:30</strong>
                </div>
            </div>
        </div>
    </article>

    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Ngày công tháng này</span>
                <strong><?php echo htmlspecialchars((string) $attendanceStat['present_days']); ?></strong>
                <small>Đã ghi nhận</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Đúng giờ</span>
                <strong><?php echo htmlspecialchars((string) max(0, $attendanceStat['present_days'] - $attendanceStat['late'])); ?></strong>
                <small>Tỷ lệ theo tháng</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">△</div>
            <div class="stat-card-body">
                <span>Đi muộn</span>
                <strong><?php echo htmlspecialchars((string) $attendanceStat['late']); ?></strong>
                <small>Cần cải thiện</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Thiếu checkout</span>
                <strong><?php echo htmlspecialchars((string) $attendanceStat['missing_checkout']); ?></strong>
                <small>Cần bổ sung</small>
            </div>
        </article>
    </div>

    <section class="payroll-grid">
        <article class="card employee-table-card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Lịch sử chấm công</h2>
                    <p class="section-subtitle">Theo dõi các lần check-in/check-out gần nhất.</p>
                </div>

                <button class="btn btn-soft" type="button" data-payroll-action="mock-save">
                    ⇩ Xuất bảng công
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="5">Chưa có dữ liệu chấm công.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($row['work_date']))); ?></td>
                                <td><strong><?php echo htmlspecialchars(date('H:i', strtotime($row['check_in_time']))); ?></strong></td>
                                <td><strong><?php echo $row['check_out_time'] ? htmlspecialchars(date('H:i', strtotime($row['check_out_time']))) : '--'; ?></strong></td>
                                <td>
                                    <?php
                                        $tone = 'info';
                                        if ($row['status'] === 'Late') {
                                            $tone = 'warning';
                                        } elseif ($row['status'] === 'Present') {
                                            $tone = 'success';
                                        } elseif ($row['status'] === 'Absent') {
                                            $tone = 'danger';
                                        }
                                    ?>
                                    <span class="badge badge-<?php echo htmlspecialchars($tone); ?>">
                                        <?php echo htmlspecialchars($row['status'] === 'Present' ? 'Đúng giờ' : $row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['check_out_time'] ? 'Ghi nhận từ Web Check-in' : 'Chưa checkout'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="card">
            <div class="card-body">
                <h2 class="section-title">Timeline hôm nay</h2>

                <div class="timeline-list" style="margin-top: 24px;">
                    <div class="timeline-row">
                        <div class="timeline-dot">1</div>
                        <div class="timeline-content">
                            <strong>08:00</strong>
                            <p>Bắt đầu ca làm việc tiêu chuẩn.</p>
                        </div>
                    </div>

                    <div class="timeline-row">
                        <div class="timeline-dot">2</div>
                        <div class="timeline-content">
                            <strong>12:00</strong>
                            <p>Nghỉ trưa và cập nhật trạng thái công việc.</p>
                        </div>
                    </div>

                    <div class="timeline-row">
                        <div class="timeline-dot">3</div>
                        <div class="timeline-content">
                            <strong>17:30</strong>
                            <p>Kết thúc ca làm việc và check-out.</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>