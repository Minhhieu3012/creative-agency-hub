<?php
$activeMenu = $activeMenu ?? 'dashboard';
$brandName = $brandName ?? 'Creative Agency Hub';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$menus = [
    [
        'key' => 'dashboard',
        'label' => 'Bảng điều khiển',
        'href' => $viewUrl . '/dashboard/index.php',
        'icon' => '▦',
    ],
    [
        'key' => 'departments',
        'label' => 'Tổ chức',
        'href' => $viewUrl . '/hrm/departments.php',
        'icon' => '▤',
    ],
    [
        'key' => 'employees',
        'label' => 'Nhân sự',
        'href' => $viewUrl . '/hrm/employees.php',
        'icon' => '◉',
    ],
    [
        'key' => 'projects',
        'label' => 'Dự án',
        'href' => $viewUrl . '/tasks/projects.php',
        'icon' => '▣',
    ],
    [
        'key' => 'kanban',
        'label' => 'Bảng Kanban',
        'href' => $viewUrl . '/tasks/kanban.php',
        'icon' => '☑',
    ],
    [
        'key' => 'gantt',
        'label' => 'Gantt Chart',
        'href' => $viewUrl . '/tasks/gantt.php',
        'icon' => '▥',
    ],
    [
        'key' => 'attendance',
        'label' => 'Chấm công',
        'href' => $viewUrl . '/payroll/attendance.php',
        'icon' => '◴',
    ],
    [
        'key' => 'leave_request',
        'label' => 'Nghỉ phép',
        'href' => $viewUrl . '/payroll/leave_request.php',
        'icon' => '✦',
    ],
    [
        'key' => 'approvals',
        'label' => 'Phê duyệt',
        'href' => $viewUrl . '/payroll/manager_approvals.php',
        'icon' => '☷',
    ],
    [
        'key' => 'payroll',
        'label' => 'Bảng lương',
        'href' => $viewUrl . '/payroll/payroll_summary.php',
        'icon' => '▧',
    ],
    [
        'key' => 'profile',
        'label' => 'Hồ sơ cá nhân',
        'href' => $viewUrl . '/hrm/profile.php',
        'icon' => '◌',
    ],
];

$secondaryMenus = [
    [
        'key' => 'client_portal',
        'label' => 'Client Portal',
        'href' => $viewUrl . '/client-portal/projects.php',
        'icon' => '◇',
    ],
    [
        'key' => 'settings',
        'label' => 'Cài đặt',
        'href' => '#settings',
        'icon' => '⚙',
    ],
    [
        'key' => 'help',
        'label' => 'Trợ giúp',
        'href' => '#help',
        'icon' => '?',
    ],
];
?>

<aside class="app-sidebar" id="appSidebar" aria-label="Sidebar điều hướng">
    <div class="sidebar-header">
        <a href="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php" class="sidebar-brand">
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

    <div class="sidebar-scroll">
        <nav class="sidebar-nav" aria-label="Điều hướng chính">
            <?php foreach ($menus as $menu): ?>
                <a
                    href="<?php echo htmlspecialchars($menu['href']); ?>"
                    class="sidebar-link <?php echo $activeMenu === $menu['key'] ? 'is-active' : ''; ?>"
                    data-sidebar-link
                >
                    <span class="sidebar-icon"><?php echo htmlspecialchars($menu['icon']); ?></span>
                    <span><?php echo htmlspecialchars($menu['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Không gian khác</div>

            <nav class="sidebar-nav sidebar-nav-compact" aria-label="Điều hướng phụ">
                <?php foreach ($secondaryMenus as $menu): ?>
                    <a
                        href="<?php echo htmlspecialchars($menu['href']); ?>"
                        class="sidebar-link <?php echo $activeMenu === $menu['key'] ? 'is-active' : ''; ?>"
                        data-sidebar-link
                    >
                        <span class="sidebar-icon"><?php echo htmlspecialchars($menu['icon']); ?></span>
                        <span><?php echo htmlspecialchars($menu['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <div class="sidebar-footer">
        <a href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php" class="btn btn-primary btn-block">
            <span>＋</span>
            <span>Tạo mới</span>
        </a>

        <a href="<?php echo htmlspecialchars($viewUrl); ?>/auth/login.php" class="sidebar-link sidebar-link-danger">
            <span class="sidebar-icon">↪</span>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>

<div class="sidebar-backdrop" data-sidebar-backdrop></div>