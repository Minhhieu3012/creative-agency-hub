<?php
$pageTitle = 'Danh sách project | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css', 'tasks.css'];
$pageJs = ['admin.js'];
$activeMenu = 'admin-projects';
$topbarTitle = 'Danh sách project';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Danh sách project';
$pageSubtitle = 'Admin xem tổng quan project trong hệ thống.';
$pageAction = '
    <button class="btn btn-light" type="button" data-admin-projects-refresh>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="hrm-grid" data-admin-projects>
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">▣</div>
            <div class="stat-card-body">
                <span>Tổng project</span>
                <strong data-admin-project-stat="total">0</strong>
                <small>Toàn hệ thống</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Active</span>
                <strong data-admin-project-stat="active">0</strong>
                <small>Đang triển khai</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">★</div>
            <div class="stat-card-body">
                <span>Completed</span>
                <strong data-admin-project-stat="completed">0</strong>
                <small>Đã hoàn thành</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">×</div>
            <div class="stat-card-body">
                <span>Archived</span>
                <strong data-admin-project-stat="archived">0</strong>
                <small>Đã lưu trữ</small>
            </div>
        </article>
    </div>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tất cả project</h2>
                <p>Danh sách project được tạo bởi các Manager.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <div class="input-with-icon">
                    <span class="input-icon">⌕</span>
                    <input
                        class="form-control"
                        type="search"
                        placeholder="Tìm tên project, client, manager..."
                        data-admin-project-search
                    >
                </div>

                <select class="form-select" data-admin-project-status>
                    <option value="">Tất cả trạng thái</option>
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                    <option value="Archived">Archived</option>
                </select>

                <button class="btn btn-soft" type="button" data-admin-project-filter>
                    Lọc
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Manager</th>
                            <th>Client</th>
                            <th>Trạng thái</th>
                            <th>Tiến độ</th>
                            <th>Ngày bắt đầu</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody data-admin-projects-body>
                        <tr>
                            <td colspan="7">Đang tải danh sách project...</td>
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