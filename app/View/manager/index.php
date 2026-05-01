<?php
$pageTitle = 'Trung tâm quản lý | Creative Agency Hub';
$pageCss = ['role-home.css'];
$pageJs = ['app.js', 'forms.js', 'toast.js'];
$activeMenu = 'manager_home';
$brandName = 'Creative Agency Hub';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

ob_start();
?>

<section class="role-home role-home-manager">
    <div class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker">Manager Workspace • Creative Agency Hub</span>
            <h1>Điều phối dự án, task và đội ngũ trong một màn hình.</h1>
            <p>
                Theo dõi tiến độ, phân công đầu việc, duyệt kết quả và kiểm soát hoạt động
                của team theo luồng quản lý rõ ràng.
            </p>

            <div class="role-hero-actions">
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                    Mở Kanban
                </a>
                <a class="btn btn-ghost" href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/manager_approvals.php">
                    Xem phê duyệt
                </a>
            </div>
        </div>

        <div class="role-hero-panel">
            <div class="role-panel-row">
                <span>Vai trò</span>
                <strong>Manager</strong>
            </div>
            <div class="role-panel-row">
                <span>Trọng tâm hôm nay</span>
                <strong>Dự án & Task</strong>
            </div>
            <div class="role-panel-row">
                <span>Ưu tiên</span>
                <strong>Duyệt tiến độ</strong>
            </div>
            <div class="role-panel-row">
                <span>Trạng thái</span>
                <strong>Đang hoạt động</strong>
            </div>
        </div>
    </div>

    <div class="role-stat-grid">
        <article class="role-stat-card">
            <span class="role-stat-icon">▣</span>
            <div>
                <h3>Dự án đang quản lý</h3>
                <strong>04</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">☑</span>
            <div>
                <h3>Task đang chạy</h3>
                <strong>18</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">☷</span>
            <div>
                <h3>Chờ phê duyệt</h3>
                <strong>06</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">◉</span>
            <div>
                <h3>Nhân sự phụ trách</h3>
                <strong>12</strong>
            </div>
        </article>
    </div>

    <div class="role-layout">
        <article class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Việc cần xử lý</h2>
                    <p>Các đầu việc quan trọng của manager trong ngày.</p>
                </div>
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                    Xem tất cả
                </a>
            </div>

            <div class="role-card-body">
                <div class="role-list">
                    <div class="role-list-item">
                        <span class="role-list-icon">1</span>
                        <div class="role-list-content">
                            <h3>Duyệt task “Fix Auth API”</h3>
                            <p>Employee đã gửi kết quả, cần kiểm tra và phản hồi.</p>
                        </div>
                        <span class="badge badge-warning">Review</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">2</span>
                        <div class="role-list-content">
                            <h3>Phân công task mới cho dự án Creative Website Revamp</h3>
                            <p>Tạo task thật và gán nhân sự phụ trách từ bảng employees.</p>
                        </div>
                        <span class="badge badge-success">Task</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">3</span>
                        <div class="role-list-content">
                            <h3>Kiểm tra tiến độ tuần này</h3>
                            <p>Đối chiếu Kanban và Gantt để chuẩn bị báo cáo.</p>
                        </div>
                        <span class="badge badge-primary">Gantt</span>
                    </div>
                </div>
            </div>
        </article>

        <aside class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Truy cập nhanh</h2>
                    <p>Các khu vực manager dùng nhiều nhất.</p>
                </div>
            </div>

            <div class="role-card-body">
                <div class="role-quick-grid">
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php">
                        <span>Dự án</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                        <span>Bảng Kanban</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/gantt.php">
                        <span>Gantt Chart</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/manager_approvals.php">
                        <span>Phê duyệt</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/hrm/employees.php">
                        <span>Nhân sự</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="role-note" style="margin-top: 18px;">
                    <h3>Manager là luồng ưu tiên cao nhất</h3>
                    <p>
                        Các scope tiếp theo sẽ ưu tiên tạo task thật, giao việc thật,
                        duyệt task thật và đồng bộ notification.
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