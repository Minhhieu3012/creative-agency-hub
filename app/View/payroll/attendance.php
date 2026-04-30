<?php
$pageTitle = 'Chấm công | Creative Agency Hub';
$pageCss = ['payroll.css', 'hrm.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'attendance';
$topbarTitle = 'Web Check-in';
$brandName = 'Creative Agency Hub';

$history = $history ?? [
    ['date' => '21/10/2026', 'checkin' => '08:02', 'checkout' => '17:35', 'status' => 'Đúng giờ', 'tone' => 'success'],
    ['date' => '20/10/2026', 'checkin' => '08:16', 'checkout' => '17:42', 'status' => 'Đi muộn', 'tone' => 'warning'],
    ['date' => '19/10/2026', 'checkin' => '07:58', 'checkout' => '17:31', 'status' => 'Đúng giờ', 'tone' => 'success'],
    ['date' => '18/10/2026', 'checkin' => '--', 'checkout' => '--', 'status' => 'Nghỉ phép', 'tone' => 'info'],
];

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
                    <strong data-checkin-status>Chưa check-in</strong>
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
                <strong>21</strong>
                <small>Trên tổng 22 ngày</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Đúng giờ</span>
                <strong>18</strong>
                <small>Tỷ lệ 86%</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">△</div>
            <div class="stat-card-body">
                <span>Đi muộn</span>
                <strong>02</strong>
                <small>Cần cải thiện</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Thiếu checkout</span>
                <strong>01</strong>
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
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['checkin']); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($row['checkout']); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($row['tone']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>Ghi nhận từ Web Check-in</td>
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