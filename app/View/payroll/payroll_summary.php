<?php
$pageTitle = 'Bảng lương | Creative Agency Hub';
$pageCss = ['payroll.css', 'hrm.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'payroll';
$topbarTitle = 'Payroll Summary';
$brandName = 'Creative Agency Hub';

$rows = $rows ?? [
    [
        'name' => 'Nguyễn Văn An',
        'email' => 'an.nguyen@agency.vn',
        'department' => 'Ban Giám đốc',
        'working_days' => 22,
        'late' => 0,
        'kpi' => 96,
        'base' => '38.000.000đ',
        'net' => '41.200.000đ',
        'status' => 'Đã tính',
        'tone' => 'success',
        'initials' => 'NA',
    ],
    [
        'name' => 'Lê Thị Mai',
        'email' => 'mai.lt@agency.vn',
        'department' => 'Kỹ thuật',
        'working_days' => 21,
        'late' => 1,
        'kpi' => 88,
        'base' => '24.000.000đ',
        'net' => '25.100.000đ',
        'status' => 'Đã tính',
        'tone' => 'success',
        'initials' => 'LM',
    ],
    [
        'name' => 'Phạm Duy Anh',
        'email' => 'anh.pd@agency.vn',
        'department' => 'Design',
        'working_days' => 20,
        'late' => 2,
        'kpi' => 82,
        'base' => '18.000.000đ',
        'net' => '18.250.000đ',
        'status' => 'Chờ duyệt',
        'tone' => 'warning',
        'initials' => 'PA',
    ],
    [
        'name' => 'Trần Minh Huy',
        'email' => 'huy.tm@agency.vn',
        'department' => 'Marketing',
        'working_days' => 18,
        'late' => 3,
        'kpi' => 76,
        'base' => '16.000.000đ',
        'net' => '15.600.000đ',
        'status' => 'Cần kiểm tra',
        'tone' => 'danger',
        'initials' => 'TH',
    ],
];

ob_start();
?>

<?php
$pageHeading = 'Báo cáo Chấm công & Bảng lương';
$pageSubtitle = 'Tổng hợp ngày công, KPI, thưởng phạt và lương thực nhận của nhân sự.';
$pageAction = '<button class="btn btn-light" type="button" data-payroll-action="mock-save">⇩ Xuất Excel</button><button class="btn btn-primary" type="button" data-payroll-action="mock-save">Chốt bảng lương</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-shell">
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">▧</div>
            <div class="stat-card-body">
                <span>Tổng quỹ lương</span>
                <strong>126M</strong>
                <small>Tháng 10/2026</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Tổng ngày công</span>
                <strong>81</strong>
                <small>4 nhân sự demo</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✦</div>
            <div class="stat-card-body">
                <span>KPI trung bình</span>
                <strong>86%</strong>
                <small>Đạt mục tiêu</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Cần kiểm tra</span>
                <strong>01</strong>
                <small>Trước khi chốt</small>
            </div>
        </article>
    </div>

    <div class="payroll-filter">
        <select class="form-select">
            <option>Tháng 10/2026</option>
            <option>Tháng 09/2026</option>
            <option>Tháng 08/2026</option>
        </select>

        <select class="form-select">
            <option>Tất cả phòng ban</option>
            <option>Kỹ thuật</option>
            <option>Design</option>
            <option>Marketing</option>
        </select>

        <div class="input-with-icon">
            <span class="input-icon">⌕</span>
            <input class="form-control" type="search" placeholder="Tìm nhân sự...">
        </div>

        <button class="btn btn-soft" type="button" data-payroll-action="mock-save">
            Lọc dữ liệu
        </button>
    </div>

    <section class="payroll-grid">
        <article class="card employee-table-card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Bảng lương tháng</h2>
                    <p class="section-subtitle">Dữ liệu demo UI. Backend sẽ nối công thức tính sau.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table payroll-summary-table">
                    <thead>
                        <tr>
                            <th>Nhân sự</th>
                            <th>Phòng ban</th>
                            <th>Ngày công</th>
                            <th>Đi muộn</th>
                            <th>KPI</th>
                            <th>Lương cơ bản</th>
                            <th>Thực nhận</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="employee-avatar"><?php echo htmlspecialchars($row['initials']); ?></div>

                                        <div class="employee-name">
                                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                            <small><?php echo htmlspecialchars($row['email']); ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><strong><?php echo (int) $row['working_days']; ?></strong></td>
                                <td><?php echo (int) $row['late']; ?></td>
                                <td><strong><?php echo (int) $row['kpi']; ?>%</strong></td>
                                <td><?php echo htmlspecialchars($row['base']); ?></td>
                                <td class="salary-amount"><?php echo htmlspecialchars($row['net']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($row['tone']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="card">
            <div class="card-header">
                <h2 class="section-title">Chi tiết tổng hợp</h2>
                <p class="section-subtitle">Tạm tính theo dữ liệu chấm công và KPI.</p>
            </div>

            <div class="card-body">
                <div class="payroll-detail-card">
                    <div class="payroll-detail-row">
                        <span>Lương cơ bản</span>
                        <strong>96.000.000đ</strong>
                    </div>

                    <div class="payroll-detail-row">
                        <span>Thưởng KPI</span>
                        <strong>8.950.000đ</strong>
                    </div>

                    <div class="payroll-detail-row">
                        <span>Phạt đi muộn</span>
                        <strong>-1.200.000đ</strong>
                    </div>

                    <div class="payroll-detail-row">
                        <span>Phụ cấp</span>
                        <strong>5.400.000đ</strong>
                    </div>

                    <div class="payroll-detail-row total">
                        <span>Tổng thực nhận</span>
                        <strong>100.150.000đ</strong>
                    </div>
                </div>

                <button class="btn btn-primary btn-block" type="button" style="margin-top: 24px;" data-payroll-action="mock-save">
                    Xem báo cáo chi tiết
                </button>
            </div>
        </aside>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>