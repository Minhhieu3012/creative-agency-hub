<?php
$pageTitle = 'Cơ cấu tổ chức | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'departments';
$topbarTitle = 'Cơ cấu tổ chức';
$brandName = 'Creative Agency Hub';

$departments = $departments ?? [
    ['name' => 'Ban Giám đốc', 'members' => '05 Thành viên', 'location' => 'Head Office', 'icon' => '▣', 'parent' => true],
    ['name' => 'Phòng Kỹ thuật', 'members' => '24 Thành viên', 'location' => 'Product & Engineering', 'icon' => '⚙', 'parent' => false],
    ['name' => 'Phòng HR & Hành chính', 'members' => '08 Thành viên', 'location' => 'People Operations', 'icon' => '◉', 'parent' => false],
    ['name' => 'Phòng Kế toán', 'members' => '04 Thành viên', 'location' => 'Finance', 'icon' => '▧', 'parent' => false],
];

$roles = $roles ?? [
    [
        'title' => 'Giám đốc Điều hành (CEO)',
        'badge' => 'Admin',
        'description' => 'Toàn quyền hệ thống và cấu hình tổ chức.',
        'permissions' => ['Quản lý nhân sự', 'Duyệt chi', 'Cấu hình hệ thống'],
    ],
    [
        'title' => 'Trưởng phòng Kỹ thuật',
        'badge' => 'Manager',
        'description' => 'Quản lý đội ngũ và dự án thuộc phạm vi phụ trách.',
        'permissions' => ['Phân công việc', 'Duyệt nghỉ phép', 'Theo dõi tiến độ'],
    ],
    [
        'title' => 'Chuyên viên Nhân sự',
        'badge' => 'Standard',
        'description' => 'Thực thi nghiệp vụ hồ sơ, hợp đồng và hỗ trợ nhân viên.',
        'permissions' => ['Nhập liệu hồ sơ', 'Tính lương', 'Báo cáo'],
    ],
];

ob_start();
?>

<?php
$pageHeading = 'Cơ cấu Tổ chức';
$pageSubtitle = 'Quản lý sơ đồ phòng ban, chức danh và phân quyền hệ thống.';
$pageAction = '<button class="btn btn-light" type="button" data-hrm-action="mock-save">⇩ Xuất báo cáo</button><button class="btn btn-primary" type="button" data-hrm-action="mock-save">＋ Thêm phòng ban</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="org-grid">
    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <h2>Sơ đồ phòng ban</h2>
            <button class="btn btn-soft" type="button" data-hrm-action="mock-save">Mở rộng tất cả</button>
        </div>

        <div class="card-body">
            <div class="org-tree">
                <?php foreach ($departments as $department): ?>
                    <div class="org-node <?php echo !empty($department['parent']) ? 'is-parent' : ''; ?>">
                        <div class="org-node-icon"><?php echo htmlspecialchars($department['icon']); ?></div>

                        <div class="org-node-text">
                            <strong><?php echo htmlspecialchars($department['name']); ?></strong>
                            <small>
                                <?php echo htmlspecialchars($department['members']); ?>
                                •
                                <?php echo htmlspecialchars($department['location']); ?>
                            </small>
                        </div>

                        <button class="icon-btn" type="button" data-hrm-action="mock-save">⋮</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </article>

    <aside class="hrm-grid">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Chức danh & Phân quyền</h2>
                <button class="btn btn-soft" type="button" data-hrm-action="mock-save">＋ Thêm</button>
            </div>

            <div class="card-body">
                <div class="role-list">
                    <?php foreach ($roles as $role): ?>
                        <div class="role-card">
                            <div class="role-card-head">
                                <h3><?php echo htmlspecialchars($role['title']); ?></h3>
                                <span class="badge badge-primary"><?php echo htmlspecialchars($role['badge']); ?></span>
                            </div>

                            <p><?php echo htmlspecialchars($role['description']); ?></p>

                            <div class="role-permission-row">
                                <?php foreach ($role['permissions'] as $permission): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($permission); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <article class="quick-summary-card">
            <div>
                <span>Tổng quy mô tổ chức</span>
                <strong>142</strong>
                <p>Tăng 12% so với tháng trước. Cơ cấu hiện tại ổn định và sẵn sàng mở rộng.</p>
            </div>

            <button class="btn btn-light" type="button" data-hrm-action="mock-save">Xem báo cáo nhân sự</button>
        </article>
    </aside>
</section>

<section class="card" style="margin-top: 26px;">
    <div class="card-header dashboard-card-title-row">
        <h2>Danh sách nhân sự nòng cốt</h2>
        <a href="/creative-agency-hub/app/View/hrm/employees.php" class="text-primary" style="font-weight: 800;">Xem tất cả</a>
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

            <tbody>
                <tr>
                    <td>
                        <div class="employee-cell">
                            <div class="employee-avatar">NA</div>
                            <div class="employee-name">
                                <strong>Nguyễn Văn An</strong>
                                <small>an.nguyen@agency.vn</small>
                            </div>
                        </div>
                    </td>
                    <td>Ban Giám đốc</td>
                    <td><strong class="text-primary">Tổng Giám đốc</strong></td>
                    <td><span class="badge badge-success">Đang làm việc</span></td>
                    <td><button class="icon-btn" type="button" data-hrm-action="mock-save">✎</button></td>
                </tr>

                <tr>
                    <td>
                        <div class="employee-cell">
                            <div class="employee-avatar">LM</div>
                            <div class="employee-name">
                                <strong>Lê Thị Mai</strong>
                                <small>mai.lt@agency.vn</small>
                            </div>
                        </div>
                    </td>
                    <td>Kỹ thuật</td>
                    <td><strong class="text-primary">Technical Lead</strong></td>
                    <td><span class="badge badge-success">Đang làm việc</span></td>
                    <td><button class="icon-btn" type="button" data-hrm-action="mock-save">✎</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>