<?php
$pageTitle = 'Khóa / mở khóa tài khoản | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css'];
$pageJs = ['admin.js'];
$activeMenu = 'admin-account-security';
$topbarTitle = 'Khóa / mở khóa';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Khóa / mở khóa tài khoản';
$pageSubtitle = 'Quản lý trạng thái hoạt động của tài khoản.';
$pageAction = '
    <button class="btn btn-light" type="button" data-admin-security-refresh>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="hrm-grid" data-admin-security>
    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Quản lý trạng thái</h2>
                <p>Khóa, đóng băng hoặc mở lại tài khoản.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <div class="input-with-icon">
                    <span class="input-icon">⌕</span>
                    <input class="form-control" type="search" placeholder="Tìm tên, email, mã..."
                        data-admin-security-search>
                </div>

                <select class="form-select" data-admin-security-status>
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="inactive">Chờ duyệt</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Manager</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-admin-security-body>
                        <tr>
                            <td colspan="5">Đang tải tài khoản...</td>
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