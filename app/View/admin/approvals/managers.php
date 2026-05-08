<?php
$pageTitle = 'Duyệt Manager | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css'];
$pageJs = ['admin.js'];
$activeMenu = 'admin-approve-managers';
$topbarTitle = 'Duyệt Manager';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Duyệt tài khoản Manager';
$pageSubtitle = 'Xử lý các tài khoản Manager mới đang chờ kích hoạt.';
$pageAction = '
    <button class="btn btn-light" type="button" data-admin-page-refresh>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="hrm-grid" data-admin-approval-page data-approval-role="manager">
    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Manager chờ duyệt</h2>
                <p>Các tài khoản Manager có trạng thái pending.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Email</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-admin-approval-body>
                        <tr>
                            <td colspan="5">Đang tải danh sách Manager chờ duyệt...</td>
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