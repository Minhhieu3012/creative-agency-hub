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
$pageSubtitle = 'Tổng quan hệ thống Creative Agency Hub ở cấp quản trị. Admin chỉ giám sát, duyệt và quản lý tài khoản, không vận hành project/task.';
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
                <small>Admin / Manager / Employee / Client</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">M</div>
            <div class="stat-card-body">
                <span>Manager</span>
                <strong data-admin-stat="managers">0</strong>
                <small>Tài khoản quản lý workspace</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">E</div>
            <div class="stat-card-body">
                <span>Employee</span>
                <strong data-admin-stat="employees">0</strong>
                <small>Nhân sự vận hành task</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">C</div>
            <div class="stat-card-body">
                <span>Client</span>
                <strong data-admin-stat="clients">0</strong>
                <small>Tài khoản cổng khách hàng</small>
            </div>
        </article>

        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Chờ duyệt</span>
                <strong data-admin-stat="pending_accounts">0</strong>
                <small>Tài khoản cần Admin xử lý</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Bị khóa</span>
                <strong data-admin-stat="suspended_accounts">0</strong>
                <small>Tài khoản suspended</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">P</div>
            <div class="stat-card-body">
                <span>Project</span>
                <strong data-admin-stat="projects">0</strong>
                <small>Chỉ thống kê, không vận hành</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">T</div>
            <div class="stat-card-body">
                <span>Task</span>
                <strong data-admin-stat="tasks">0</strong>
                <small>Chỉ thống kê, không thao tác</small>
            </div>
        </article>
    </div>

    <section class="dashboard-two-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Tài khoản chờ duyệt</h2>
                    <p>Manager đăng ký mới hoặc Manager tạo Employee/Client sẽ xuất hiện tại đây.</p>
                </div>

                <a class="btn btn-soft" href="/creative-agency-hub/app/View/admin/accounts/index.php">
                    Xem tất cả
                </a>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tài khoản</th>
                                <th>Vai trò</th>
                                <th>Người quản lý</th>
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
                    <h2>Phạm vi quyền Admin</h2>
                    <p>Admin giữ vai trò quản trị hệ thống, không vận hành thay Manager.</p>
                </div>
            </div>

            <div class="card-body">
                <div class="hrm-grid">
                    <div class="document-card">
                        <div class="document-icon">✓</div>
                        <div class="document-info">
                            <strong>Được làm</strong>
                            <small>Duyệt tài khoản, khóa/mở khóa, xem thống kê hệ thống.</small>
                        </div>
                        <span></span>
                    </div>

                    <div class="document-card">
                        <div class="document-icon">×</div>
                        <div class="document-info">
                            <strong>Không vận hành</strong>
                            <small>Không tạo project, không tạo task, không giao việc.</small>
                        </div>
                        <span></span>
                    </div>

                    <div class="document-card">
                        <div class="document-icon">◉</div>
                        <div class="document-info">
                            <strong>Luồng chuẩn</strong>
                            <small>Manager được duyệt sẽ tự quản lý project, employee và client của họ.</small>
                        </div>
                        <span></span>
                    </div>
                </div>
            </div>
        </article>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>