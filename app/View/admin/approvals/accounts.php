<?php
$pageTitle = 'Duyệt nhân sự | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css'];
$pageJs = ['admin.js'];
$activeMenu = 'admin-approve-accounts';
$topbarTitle = 'Duyệt nhân sự';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Duyệt nhân sự và Client';
$pageSubtitle = 'Xử lý tài khoản Employee hoặc Client do Manager tạo.';
$pageAction = '
    <button class="btn btn-light" type="button" data-admin-page-refresh>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="hrm-grid" data-admin-approval-page data-approval-role="staff-client">
    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Employee / Client chờ duyệt</h2>
                <p>Các tài khoản đang đợi Admin kích hoạt.</p>
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
                    <tbody data-admin-approval-body>
                        <tr>
                            <td colspan="6">Đang tải danh sách tài khoản chờ duyệt...</td>
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