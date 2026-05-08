<?php
$pageTitle = 'Quản lý tài khoản | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css'];
$pageJs = ['admin.js'];
$activeMenu = 'admin-accounts';
$topbarTitle = 'Quản lý tài khoản';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Quản lý tài khoản hệ thống';
$pageSubtitle = 'Admin duyệt tài khoản Manager đăng ký, duyệt Employee/Client do Manager tạo, khóa hoặc mở khóa tài khoản. Không vận hành project/task.';
$pageAction = '
    <button class="btn btn-light" type="button" data-admin-accounts-refresh>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="hrm-grid" data-admin-accounts>
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◉</div>
            <div class="stat-card-body">
                <span>Tổng tài khoản</span>
                <strong data-admin-account-stat="total">0</strong>
                <small>Trong hệ thống</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Active</span>
                <strong data-admin-account-stat="active">0</strong>
                <small>Được đăng nhập</small>
            </div>
        </article>

        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Chờ duyệt</span>
                <strong data-admin-account-stat="inactive">0</strong>
                <small>Pending approval</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Suspended</span>
                <strong data-admin-account-stat="suspended">0</strong>
                <small>Bị khóa/đóng băng</small>
            </div>
        </article>
    </div>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tài khoản chờ duyệt</h2>
                <p>Gồm Manager đăng ký mới và Employee/Client do Manager tạo.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Manager</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-admin-pending-body>
                        <tr>
                            <td colspan="6">Đang tải tài khoản chờ duyệt...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Danh sách tài khoản</h2>
                <p>Xem, lọc và khóa/mở khóa tài khoản trong hệ thống.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <div class="input-with-icon">
                    <span class="input-icon">⌕</span>
                    <input
                        class="form-control"
                        type="search"
                        placeholder="Tìm tên, email, mã tài khoản..."
                        data-admin-account-search
                    >
                </div>

                <select class="form-select" data-admin-account-role>
                    <option value="">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                    <option value="client">Client</option>
                </select>

                <select class="form-select" data-admin-account-status>
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="inactive">Chờ duyệt</option>
                    <option value="suspended">Suspended</option>
                    <option value="resigned">Resigned</option>
                </select>

                <button class="btn btn-soft" type="button" data-admin-account-apply-filter>
                    Lọc
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Manager</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-admin-accounts-body>
                        <tr>
                            <td colspan="7">Đang tải danh sách tài khoản...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>