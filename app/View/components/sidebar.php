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
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'departments',
        'label' => 'Tổ chức',
        'href' => $viewUrl . '/hrm/departments.php',
        'icon' => '▤',
        'roles' => ['admin', 'manager'],
    ],
    [
        'key' => 'employees',
        'label' => 'Nhân sự',
        'href' => $viewUrl . '/hrm/employees.php',
        'icon' => '◉',
        'roles' => ['admin', 'manager'],
    ],
    [
        'key' => 'projects',
        'label' => 'Dự án',
        'href' => $viewUrl . '/tasks/projects.php',
        'icon' => '▣',
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'kanban',
        'label' => 'Bảng Kanban',
        'href' => $viewUrl . '/tasks/kanban.php',
        'icon' => '☑',
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'gantt',
        'label' => 'Gantt Chart',
        'href' => $viewUrl . '/tasks/gantt.php',
        'icon' => '▥',
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'attendance',
        'label' => 'Chấm công',
        'href' => $viewUrl . '/payroll/attendance.php',
        'icon' => '◴',
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'leave_request',
        'label' => 'Nghỉ phép',
        'href' => $viewUrl . '/payroll/leave_request.php',
        'icon' => '✦',
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'approvals',
        'label' => 'Phê duyệt',
        'href' => $viewUrl . '/payroll/manager_approvals.php',
        'icon' => '☷',
        'roles' => ['admin', 'manager'],
    ],
    [
        'key' => 'payroll',
        'label' => 'Bảng lương',
        'href' => $viewUrl . '/payroll/payroll_summary.php',
        'icon' => '▧',
        'roles' => ['admin', 'manager'],
    ],
    [
        'key' => 'profile',
        'label' => 'Hồ sơ cá nhân',
        'href' => $viewUrl . '/hrm/profile.php',
        'icon' => '◌',
        'roles' => ['admin', 'manager', 'employee'],
    ],
];

$secondaryMenus = [
    [
        'key' => 'client_portal',
        'label' => 'Client Portal',
        'href' => $viewUrl . '/client-portal/projects.php',
        'icon' => '◇',
        'roles' => ['admin', 'manager', 'client'],
    ],
    [
        'key' => 'settings',
        'label' => 'Cài đặt',
        'href' => '#settings',
        'icon' => '⚙',
        'roles' => ['admin', 'manager', 'employee'],
    ],
    [
        'key' => 'help',
        'label' => 'Trợ giúp',
        'href' => '#help',
        'icon' => '?',
        'roles' => ['admin', 'manager', 'employee', 'client'],
    ],
];

function cah_sidebar_roles_attr(array $roles): string {
    return htmlspecialchars(implode(',', $roles));
}
?>

<aside class="app-sidebar" id="appSidebar" aria-label="Sidebar điều hướng">
    <div class="sidebar-header">
        <a href="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php" class="sidebar-brand" data-sidebar-home>
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
                    data-role-allow="<?php echo cah_sidebar_roles_attr($menu['roles']); ?>"
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
                        data-role-allow="<?php echo cah_sidebar_roles_attr($menu['roles']); ?>"
                    >
                        <span class="sidebar-icon"><?php echo htmlspecialchars($menu['icon']); ?></span>
                        <span><?php echo htmlspecialchars($menu['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <div class="sidebar-footer">
        <a
            href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php"
            class="btn btn-primary btn-block"
            data-role-allow="admin,manager"
        >
            <span>＋</span>
            <span>Tạo mới</span>
        </a>

        <a
            href="<?php echo htmlspecialchars($viewUrl); ?>/auth/login.php"
            class="sidebar-link sidebar-link-danger"
            data-logout
        >
            <span class="sidebar-icon">↪</span>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>

<div class="sidebar-backdrop" data-sidebar-backdrop></div>

<script>
(function () {
    function getCurrentUser() {
        try {
            return JSON.parse(localStorage.getItem("cah_auth_user") || localStorage.getItem("cah_user") || "null");
        } catch (error) {
            return null;
        }
    }

    var user = getCurrentUser();
    var role = String(user && user.role ? user.role : "").toLowerCase();
    var baseUrl = <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var viewUrl = <?php echo json_encode($viewUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    if (role === "client") {
        var home = document.querySelector("[data-sidebar-home]");
        if (home) {
            home.href = viewUrl + "/client-portal/projects.php";
        }
    }

    document.querySelectorAll("[data-role-allow]").forEach(function (item) {
        var allowed = String(item.dataset.roleAllow || "")
            .split(",")
            .map(function (value) {
                return value.trim().toLowerCase();
            })
            .filter(Boolean);

        if (role && allowed.length && allowed.indexOf(role) === -1) {
            item.style.display = "none";
        }
    });
})();
</script>