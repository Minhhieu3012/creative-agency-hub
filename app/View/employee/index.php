<?php
$pageTitle = 'Không gian nhân sự | Creative Agency Hub';
$pageCss = ['role-home.css'];
$pageJs = ['app.js', 'forms.js', 'toast.js'];
$activeMenu = 'employee_home';
$brandName = 'Creative Agency Hub';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

ob_start();
?>

<section class="role-home role-home-employee">
    <div class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker">Employee Workspace • Creative Agency Hub</span>
            <h1>Tập trung vào task của bạn, cập nhật tiến độ thật gọn.</h1>
            <p>
                Xem các công việc được giao, cập nhật trạng thái, gửi duyệt kết quả,
                chấm công và quản lý hồ sơ cá nhân trong cùng một khu vực.
            </p>

            <div class="role-hero-actions">
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                    Xem task của tôi
                </a>
                <a class="btn btn-ghost" href="<?php echo htmlspecialchars($viewUrl); ?>/hrm/profile.php">
                    Hồ sơ cá nhân
                </a>
            </div>
        </div>

        <div class="role-hero-panel">
            <div class="role-panel-row">
                <span>Vai trò</span>
                <strong>Employee</strong>
            </div>
            <div class="role-panel-row">
                <span>Trọng tâm hôm nay</span>
                <strong>Task được giao</strong>
            </div>
            <div class="role-panel-row">
                <span>Ưu tiên</span>
                <strong>Cập nhật tiến độ</strong>
            </div>
            <div class="role-panel-row">
                <span>Trạng thái</span>
                <strong>Đang làm việc</strong>
            </div>
        </div>
    </div>

    <div class="role-stat-grid">
        <article class="role-stat-card">
            <span class="role-stat-icon">☑</span>
            <div>
                <h3>Task của tôi</h3>
                <strong>07</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">▥</span>
            <div>
                <h3>Đang thực hiện</h3>
                <strong>03</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">☷</span>
            <div>
                <h3>Chờ duyệt</h3>
                <strong>02</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">✦</span>
            <div>
                <h3>Ngày phép còn lại</h3>
                <strong>12</strong>
            </div>
        </article>
    </div>

    <div class="role-layout">
        <article class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Công việc ưu tiên</h2>
                    <p>Danh sách này sẽ được nối API thật theo assignee_id ở scope sau.</p>
                </div>
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                    Mở Kanban
                </a>
            </div>

            <div class="role-card-body">
                <div class="role-list">
                    <div class="role-list-item">
                        <span class="role-list-icon">1</span>
                        <div class="role-list-content">
                            <h3>Thiết kế UI Login</h3>
                            <p>Hoàn thiện giao diện đăng nhập nội bộ và client portal.</p>
                            <div class="role-progress" style="margin-top: 12px;">
                                <div class="role-progress-row">
                                    <span>Tiến độ</span>
                                    <span>82%</span>
                                </div>
                                <div class="role-progress-track">
                                    <div class="role-progress-fill" style="width: 82%;"></div>
                                </div>
                            </div>
                        </div>
                        <span class="badge badge-primary">Doing</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">2</span>
                        <div class="role-list-content">
                            <h3>Fix Auth API</h3>
                            <p>Chuẩn hoá login theo bảng employees và role mới.</p>
                        </div>
                        <span class="badge badge-warning">Review</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">3</span>
                        <div class="role-list-content">
                            <h3>Client Portal Feedback</h3>
                            <p>Chuẩn bị khu vực khách hàng gửi phản hồi.</p>
                        </div>
                        <span class="badge badge-success">To do</span>
                    </div>
                </div>
            </div>
        </article>

        <aside class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Truy cập nhanh</h2>
                    <p>Các chức năng chính cho nhân sự.</p>
                </div>
            </div>

            <div class="role-card-body">
                <div class="role-quick-grid">
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                        <span>Task của tôi</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/hrm/profile.php">
                        <span>Hồ sơ cá nhân</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/attendance.php">
                        <span>Chấm công</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/leave_request.php">
                        <span>Nghỉ phép</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="role-note" style="margin-top: 18px;">
                    <h3>Luồng employee</h3>
                    <p>
                        Employee sẽ chỉ thao tác trên task được giao, gửi duyệt và nhận phản hồi từ manager.
                    </p>
                </div>
            </div>
        </aside>
    </div>
</section>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/app.php';
?>