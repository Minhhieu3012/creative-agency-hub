<?php
$pageTitle = 'Admin Dashboard | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css'];
$pageJs = ['admin.js'];
$activeMenu = 'admin-dashboard';
$topbarTitle = 'Admin Dashboard';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Admin Dashboard';
$pageSubtitle = 'Tổng quan hệ thống Creative Agency Hub.';
$pageAction = '
    <a class="btn btn-primary" href="/creative-agency-hub/app/View/admin/accounts/index.php">
        Quản lý tài khoản
    </a>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="dashboard-grid" data-admin-dashboard>
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◉</div>
            <div class="stat-card-body">
                <span>Tổng tài khoản</span>
                <strong data-admin-stat="total_accounts">0</strong>
                <small>Toàn hệ thống</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">M</div>
            <div class="stat-card-body">
                <span>Manager</span>
                <strong data-admin-stat="managers">0</strong>
                <small>Tài khoản quản lý</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">E</div>
            <div class="stat-card-body">
                <span>Employee</span>
                <strong data-admin-stat="employees">0</strong>
                <small>Nhân sự vận hành</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">C</div>
            <div class="stat-card-body">
                <span>Client</span>
                <strong data-admin-stat="clients">0</strong>
                <small>Tài khoản khách hàng</small>
            </div>
        </article>

        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Chờ duyệt</span>
                <strong data-admin-stat="pending_accounts">0</strong>
                <small>Tài khoản pending</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Bị khóa</span>
                <strong data-admin-stat="suspended_accounts">0</strong>
                <small>Suspended</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">P</div>
            <div class="stat-card-body">
                <span>Project</span>
                <strong data-admin-stat="projects">0</strong>
                <small>Tổng dự án</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">T</div>
            <div class="stat-card-body">
                <span>Task</span>
                <strong data-admin-stat="tasks">0</strong>
                <small>Tổng công việc</small>
            </div>
        </article>
    </div>

    <section class="dashboard-two-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Tài khoản chờ duyệt</h2>
                    <p>Danh sách tài khoản mới nhất cần xử lý.</p>
                </div>

                <a class="btn btn-soft" href="/creative-agency-hub/app/View/admin/approvals/managers.php">
                    Xem duyệt Manager
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tài khoản</th>
                                <th>Vai trò</th>
                                <th>Manager</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody data-admin-dashboard-pending>
                            <tr>
                                <td colspan="4">Đang tải tài khoản chờ duyệt...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Truy cập nhanh</h2>
                    <p>Các khu vực quản trị chính.</p>
                </div>
            </div>

            <div class="card-body">
                <div class="hrm-grid">
                    <a class="document-card" href="/creative-agency-hub/app/View/admin/approvals/managers.php">
                        <div class="document-icon">M</div>
                        <div class="document-info">
                            <strong>Duyệt Manager</strong>
                            <small>Tài khoản quản lý mới đăng ký.</small>
                        </div>
                        <span>→</span>
                    </a>

                    <a class="document-card" href="/creative-agency-hub/app/View/admin/approvals/accounts.php">
                        <div class="document-icon">✓</div>
                        <div class="document-info">
                            <strong>Duyệt nhân sự</strong>
                            <small>Employee và Client do Manager tạo.</small>
                        </div>
                        <span>→</span>
                    </a>

                    <a class="document-card" href="/creative-agency-hub/app/View/admin/accounts/security.php">
                        <div class="document-icon">!</div>
                        <div class="document-info">
                            <strong>Khóa / mở khóa</strong>
                            <small>Quản lý trạng thái tài khoản.</small>
                        </div>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </article>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>