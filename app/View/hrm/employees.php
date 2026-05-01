<?php
$pageTitle = 'Quản lý nhân sự | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'employees';
$topbarTitle = 'Quản lý nhân sự';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Quản lý Nhân sự';
$pageSubtitle = 'Theo dõi hồ sơ điện tử, phòng ban, chức vụ và trạng thái làm việc của nhân sự.';
$pageAction = '
<button class="btn btn-light" type="button" data-hrm-action="refresh-employees">Làm mới</button>
<button class="btn btn-primary" type="button" data-hrm-action="open-create-employee">＋ Thêm nhân viên</button>
';
require __DIR__ . '/../components/page-header.php';
?>

<section class="hrm-grid" data-hrm-page="employees">
    <article class="card employee-table-card">
        <div class="card-header">
            <div class="toolbar">
                <div>
                    <h2 class="section-title">Danh sách nhân sự</h2>
                    <p class="section-subtitle">
                        Dữ liệu được đồng bộ từ bảng employees, departments và positions.
                    </p>
                </div>

                <div class="toolbar-filter-group">
                    <div class="input-with-icon toolbar-search">
                        <span class="input-icon">⌕</span>
                        <input
                            class="form-control"
                            type="search"
                            placeholder="Tìm kiếm tên, mã NV, email..."
                            data-hrm-employee-search
                        >
                    </div>

                    <select class="form-select" data-hrm-department-filter>
                        <option value="">Tất cả phòng ban</option>
                    </select>

                    <select class="form-select" data-hrm-status-filter>
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Đang làm việc</option>
                        <option value="inactive">Tạm nghỉ</option>
                        <option value="suspended">Tạm khóa</option>
                        <option value="resigned">Đã nghỉ</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table" id="employeesTable">
                <thead>
                    <tr>
                        <th>Nhân sự</th>
                        <th>Phòng ban</th>
                        <th>Chức danh</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày vào làm</th>
                        <th>Hành động</th>
                    </tr>
                </thead>

                <tbody data-employee-table-body>
                    <tr>
                        <td colspan="7">
                            <div class="ui-empty-state" style="min-height: 220px;">
                                <div class="ui-empty-icon">◉</div>
                                <div class="ui-empty-content">
                                    <h3>Đang tải danh sách nhân sự</h3>
                                    <p>Hệ thống đang đồng bộ dữ liệu từ HRM API.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <div class="card-body">
            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-card-icon">◉</div>
                    <div class="stat-card-body">
                        <span>Tổng nhân viên</span>
                        <strong data-employee-stat="total">0</strong>
                        <small>Đang quản lý</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-card-icon">▤</div>
                    <div class="stat-card-body">
                        <span>Đang làm việc</span>
                        <strong data-employee-stat="active">0</strong>
                        <small>Active</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-card-icon">✦</div>
                    <div class="stat-card-body">
                        <span>Manager</span>
                        <strong data-employee-stat="manager">0</strong>
                        <small>Quản lý nhóm</small>
                    </div>
                </article>

                <article class="stat-card stat-card-danger">
                    <div class="stat-card-icon">!</div>
                    <div class="stat-card-body">
                        <span>Inactive</span>
                        <strong data-employee-stat="inactive">0</strong>
                        <small>Cần theo dõi</small>
                    </div>
                </article>
            </div>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>