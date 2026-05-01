<?php
$pageTitle = 'Cơ cấu tổ chức | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'departments';
$topbarTitle = 'Cơ cấu tổ chức';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Cơ cấu Tổ chức';
$pageSubtitle = 'Quản lý sơ đồ phòng ban, chức danh và phân quyền hệ thống.';
$pageAction = '
<button class="btn btn-light" type="button" data-hrm-action="refresh-organization">Làm mới</button>
<button class="btn btn-primary" type="button" data-hrm-action="open-create-department">＋ Thêm phòng ban</button>
';
require __DIR__ . '/../components/page-header.php';
?>

<section class="org-grid" data-hrm-page="departments">
    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Sơ đồ phòng ban</h2>
                <p class="section-subtitle">Đồng bộ từ bảng departments và số lượng nhân sự thực tế.</p>
            </div>

            <button class="btn btn-soft" type="button" data-hrm-action="open-create-department">
                ＋ Thêm
            </button>
        </div>

        <div class="card-body">
            <div class="org-tree" data-department-tree>
                <div class="ui-empty-state" style="min-height: 220px;">
                    <div class="ui-empty-icon">▤</div>
                    <div class="ui-empty-content">
                        <h3>Đang tải phòng ban</h3>
                        <p>Dữ liệu tổ chức đang được đồng bộ từ HRM API.</p>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <aside class="hrm-grid">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Chức danh</h2>
                    <p class="section-subtitle">Danh sách position đang hoạt động.</p>
                </div>

                <button class="btn btn-soft" type="button" data-hrm-action="open-create-position">
                    ＋ Thêm
                </button>
            </div>

            <div class="card-body">
                <div class="role-list" data-position-list>
                    <div class="ui-empty-state" style="min-height: 180px;">
                        <div class="ui-empty-icon">✦</div>
                        <div class="ui-empty-content">
                            <h3>Đang tải chức danh</h3>
                            <p>Danh sách chức danh sẽ hiển thị tại đây.</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="quick-summary-card">
            <div>
                <span>Tổng quy mô tổ chức</span>
                <strong data-org-stat="employees">0</strong>
                <p>
                    <span data-org-stat="departments">0</span> phòng ban và
                    <span data-org-stat="positions">0</span> chức danh đang hoạt động.
                </p>
            </div>

            <a class="btn btn-light" href="/creative-agency-hub/app/View/hrm/employees.php">
                Xem danh sách nhân sự
            </a>
        </article>
    </aside>
</section>

<section class="card" style="margin-top: 26px;">
    <div class="card-header dashboard-card-title-row">
        <div>
            <h2>Danh sách nhân sự nòng cốt</h2>
            <p class="section-subtitle">Top nhân sự mới nhất trong hệ thống.</p>
        </div>

        <a href="/creative-agency-hub/app/View/hrm/employees.php" class="text-primary" style="font-weight: 800;">
            Xem tất cả
        </a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nhân sự</th>
                    <th>Phòng ban</th>
                    <th>Chức danh</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>

            <tbody data-org-employee-preview>
                <tr>
                    <td colspan="5">
                        <div class="ui-empty-state" style="min-height: 160px;">
                            <div class="ui-empty-icon">◉</div>
                            <div class="ui-empty-content">
                                <h3>Đang tải nhân sự</h3>
                                <p>Danh sách preview sẽ được đồng bộ từ API employees.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>