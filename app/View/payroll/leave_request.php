<?php
$pageTitle = 'Xin nghỉ phép | Creative Agency Hub';
$pageCss = ['payroll.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'leave_request';
$topbarTitle = 'Leave Request';
$brandName = 'Creative Agency Hub';

$leaveHistory = $leaveHistory ?? [
    ['title' => 'Nghỉ phép năm', 'date' => '12/10/2026 - 13/10/2026', 'status' => 'Đã duyệt', 'tone' => 'success'],
    ['title' => 'Nghỉ nửa ngày', 'date' => '05/10/2026', 'status' => 'Đã duyệt', 'tone' => 'success'],
    ['title' => 'Nghỉ việc cá nhân', 'date' => '25/10/2026', 'status' => 'Chờ duyệt', 'tone' => 'warning'],
];

ob_start();
?>

<?php
$pageHeading = 'Xin Nghỉ phép';
$pageSubtitle = 'Gửi đơn nghỉ trực tuyến, theo dõi quỹ phép còn lại và lịch sử phê duyệt.';
$pageAction = '<a class="btn btn-light" href="/creative-agency-hub/app/View/payroll/manager_approvals.php">Xem phê duyệt</a>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-grid">
    <div class="payroll-shell">
        <article class="leave-balance-card">
            <div>
                <h2>Quỹ phép còn lại</h2>

                <div class="leave-balance-number">
                    <strong>08</strong>
                    <span>ngày</span>
                </div>

                <p>
                    Tổng phép năm: 12 ngày • Đã sử dụng: 04 ngày • Đang chờ duyệt: 01 ngày.
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
                    <?php foreach ($leaveHistory as $leave): ?>
                        <div class="leave-history-item">
                            <div>
                                <h3><?php echo htmlspecialchars($leave['title']); ?></h3>
                                <p><?php echo htmlspecialchars($leave['date']); ?></p>
                            </div>

                            <span class="badge badge-<?php echo htmlspecialchars($leave['tone']); ?>">
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