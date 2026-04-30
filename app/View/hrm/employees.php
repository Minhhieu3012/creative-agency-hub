<?php
$pageTitle = 'Quản lý nhân sự | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'employees';
$topbarTitle = 'Quản lý nhân sự';
$brandName = 'Creative Agency Hub';

$employees = $employees ?? [
    [
        'name' => 'Nguyễn Văn An',
        'email' => 'an.nguyen@agency.vn',
        'department' => 'Ban Giám đốc',
        'position' => 'Tổng Giám đốc',
        'status' => 'Đang làm việc',
        'status_key' => 'active',
        'initials' => 'NA',
    ],
    [
        'name' => 'Lê Thị Mai',
        'email' => 'mai.lt@agency.vn',
        'department' => 'Kỹ thuật',
        'position' => 'Technical Lead',
        'status' => 'Đang làm việc',
        'status_key' => 'active',
        'initials' => 'LM',
    ],
    [
        'name' => 'Phạm Duy Anh',
        'email' => 'anh.pd@agency.vn',
        'department' => 'Design',
        'position' => 'UI Designer',
        'status' => 'Thử việc',
        'status_key' => 'probation',
        'initials' => 'PA',
    ],
    [
        'name' => 'Trần Minh Huy',
        'email' => 'huy.tm@agency.vn',
        'department' => 'Marketing',
        'position' => 'Content Executive',
        'status' => 'Tạm nghỉ',
        'status_key' => 'inactive',
        'initials' => 'TH',
    ],
];

ob_start();
?>

<?php
$pageHeading = 'Quản lý Nhân sự';
$pageSubtitle = 'Theo dõi hồ sơ điện tử, phòng ban, chức vụ và trạng thái làm việc của nhân sự.';
$pageAction = '<button class="btn btn-primary" type="button" data-hrm-action="mock-save">＋ Thêm nhân viên</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="hrm-grid">
    <article class="card employee-table-card">
        <div class="card-header">
            <div class="toolbar">
                <div>
                    <h2 class="section-title">Danh sách nhân sự</h2>
                    <p class="section-subtitle">Tìm kiếm, lọc và quản lý hồ sơ nhân viên.</p>
                </div>

                <div class="toolbar-filter-group">
                    <div class="input-with-icon toolbar-search">
                        <span class="input-icon">⌕</span>
                        <input
                            class="form-control"
                            type="search"
                            placeholder="Tìm kiếm nhân viên..."
                            data-table-search="#employeesTable"
                        >
                    </div>

                    <select class="form-select" data-table-filter="#employeesTable" data-filter-key="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active">Đang làm việc</option>
                        <option value="probation">Thử việc</option>
                        <option value="inactive">Tạm nghỉ</option>
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
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($employees as $employee): ?>
                        <tr data-status="<?php echo htmlspecialchars($employee['status_key']); ?>">
                            <td>
                                <div class="employee-cell">
                                    <div class="employee-avatar">
                                        <?php echo htmlspecialchars($employee['initials']); ?>
                                    </div>

                                    <div class="employee-name">
                                        <strong><?php echo htmlspecialchars($employee['name']); ?></strong>
                                        <small><?php echo htmlspecialchars($employee['email']); ?></small>
                                    </div>
                                </div>
                            </td>

                            <td><?php echo htmlspecialchars($employee['department']); ?></td>

                            <td>
                                <strong class="text-primary"><?php echo htmlspecialchars($employee['position']); ?></strong>
                            </td>

                            <td>
                                <?php
                                $badgeTone = $employee['status_key'] === 'active'
                                    ? 'success'
                                    : ($employee['status_key'] === 'probation' ? 'warning' : 'danger');
                                ?>
                                <span class="badge badge-<?php echo $badgeTone; ?>">
                                    <?php echo htmlspecialchars($employee['status']); ?>
                                </span>
                            </td>

                            <td>
                                <button class="icon-btn" type="button" data-hrm-action="mock-save" title="Chỉnh sửa">✎</button>
                                <button class="icon-btn" type="button" data-hrm-action="mock-save" title="Xem chi tiết">👁</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
                        <strong><?php echo count($employees); ?></strong>
                        <small>Dữ liệu demo UI</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-card-icon">▤</div>
                    <div class="stat-card-body">
                        <span>Phòng ban</span>
                        <strong>04</strong>
                        <small>Đang hoạt động</small>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-card-icon">✦</div>
                    <div class="stat-card-body">
                        <span>Thử việc</span>
                        <strong>01</strong>
                        <small>Cần theo dõi</small>
                    </div>
                </article>

                <article class="stat-card stat-card-danger">
                    <div class="stat-card-icon">!</div>
                    <div class="stat-card-body">
                        <span>Tạm nghỉ</span>
                        <strong>01</strong>
                        <small>Cần cập nhật</small>
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