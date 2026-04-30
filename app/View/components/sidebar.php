<?php
$activeMenu = $activeMenu ?? 'dashboard';
$brandName = $brandName ?? 'Creative Agency Hub';

$menus = [
    [
        'key' => 'dashboard',
        'label' => 'Bảng điều khiển',
        'href' => '/dashboard',
        'icon' => '▦',
    ],
    [
        'key' => 'departments',
        'label' => 'Tổ chức',
        'href' => '/hrm/departments',
        'icon' => '▤',
    ],
    [
        'key' => 'employees',
        'label' => 'Nhân sự',
        'href' => '/hrm/employees',
        'icon' => '◉',
    ],
    [
        'key' => 'projects',
        'label' => 'Dự án',
        'href' => '/tasks/projects',
        'icon' => '▣',
    ],
    [
        'key' => 'kanban',
        'label' => 'Bảng Kanban',
        'href' => '/tasks/kanban',
        'icon' => '☑',
    ],
    [
        'key' => 'gantt',
        'label' => 'Gantt Chart',
        'href' => '/tasks/gantt',
        'icon' => '▥',
    ],
    [
        'key' => 'attendance',
        'label' => 'Chấm công',
        'href' => '/app/View/payroll/attendance.php',
        'icon' => '◴',
    ],
    [
        'key' => 'leave_request',
        'label' => 'Nghỉ phép',
        'href' => '/app/View/payroll/leave_request.php',
        'icon' => '✦',
    ],
    [
        'key' => 'approvals',
        'label' => 'Phê duyệt',
        'href' => '/app/View/payroll/manager_approvals.php',
        'icon' => '☷',
    ],
    [
        'key' => 'payroll',
        'label' => 'Bảng lương',
        'href' => '/app/View/payroll/payroll_summary.php',
        'icon' => '▧',
    ],
    [
        'key' => 'profile',
        'label' => 'Hồ sơ cá nhân',
        'href' => '/hrm/profile',
        'icon' => '◌',
    ],
];
?>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="/dashboard" class="sidebar-brand">
            <span class="brand-mark">CA</span>
            <span class="brand-text">
                <strong><?php echo htmlspecialchars($brandName); ?></strong>
                <small>Enterprise Suite</small>
            </span>
        </a>

        <button class="sidebar-close" type="button" data-sidebar-close aria-label="Đóng menu">
            ×
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Điều hướng chính">
        <?php foreach ($menus as $menu): ?>
            <a
                href="<?php echo htmlspecialchars($baseUrl . $menu['href']); ?>"
                class="sidebar-link <?php echo $activeMenu === $menu['key'] ? 'is-active' : ''; ?>"
            >
                <span class="sidebar-icon"><?php echo htmlspecialchars($menu['icon']); ?></span>
                <span><?php echo htmlspecialchars($menu['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/tasks/projects" class="btn btn-primary btn-block">
            <span>＋</span>
            <span>Tạo mới</span>
        </a>

        <div class="sidebar-footer-links">
            <a href="#" class="sidebar-link">
                <span class="sidebar-icon">⚙</span>
                <span>Cài đặt</span>
            </a>

            <a href="#" class="sidebar-link">
                <span class="sidebar-icon">?</span>
                <span>Trợ giúp</span>
            </a>

            <a href="/logout" class="sidebar-link sidebar-link-danger">
                <span class="sidebar-icon">↪</span>
                <span>Đăng xuất</span>
            </a>
        </div>
    </div>
</aside>

<div class="sidebar-backdrop" data-sidebar-backdrop></div>