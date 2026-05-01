<?php
/**
 * SIDEBAR COMPONENT - BẢN HỢP NHẤT HOÀN HẢO
 * Fix lỗi nhảy Role, Logout 404 và Đầy đủ Menu Manager
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// Lấy role từ Session đã lưu ở AuthController, mặc định là employee nếu trống
$userRole = strtolower($_SESSION['user_role'] ?? 'employee'); 

$activeMenu = $activeMenu ?? 'dashboard';
$brandName = $brandName ?? 'Creative Agency Hub';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');
$publicUrl = $baseUrl . '/public';

// Định nghĩa toàn bộ menu (Hợp nhất danh sách đầy đủ)
$allMenus = [
    [
        'key' => 'manager_workspace',
        'label' => 'Trung tâm quản lý',
        'href' => $viewUrl . '/dashboard/manager_workspace.php',
        'icon' => '▦',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'dashboard',
        'label' => 'Bảng điều khiển',
        'href' => $viewUrl . '/dashboard/index.php',
        'icon' => '▤',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'departments',
        'label' => 'Tổ chức',
        'href' => $viewUrl . '/hrm/departments.php',
        'icon' => '▤',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'employees',
        'label' => 'Nhân sự',
        'href' => $viewUrl . '/hrm/employees.php',
        'icon' => '◉',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'profile',
        'label' => 'Hồ sơ cá nhân',
        'href' => $viewUrl . '/hrm/profile.php',
        'icon' => '◌',
        'roles' => ['admin', 'manager', 'employee']
    ],
    [
        'key' => 'projects',
        'label' => 'Dự án',
        'href' => $viewUrl . '/tasks/projects.php',
        'icon' => '▣',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'kanban',
        'label' => 'Bảng Kanban',
        'href' => $viewUrl . '/tasks/kanban.php',
        'icon' => '☑',
        'roles' => ['admin', 'manager', 'employee']
    ],
    [
        'key' => 'gantt',
        'label' => 'Gantt Chart',
        'href' => $viewUrl . '/tasks/gantt.php',
        'icon' => '▥',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'attendance',
        'label' => 'Chấm công',
        'href' => $viewUrl . '/payroll/attendance.php',
        'icon' => '◴',
        'roles' => ['admin', 'manager', 'employee']
    ],
    [
        'key' => 'leave_request',
        'label' => 'Nghỉ phép',
        'href' => $viewUrl . '/payroll/leave_request.php',
        'icon' => '✦',
        'roles' => ['admin', 'manager', 'employee']
    ],
    [
        'key' => 'approvals',
        'label' => 'Phê duyệt',
        'href' => $viewUrl . '/payroll/manager_approvals.php',
        'icon' => '☷',
        'roles' => ['admin', 'manager']
    ],
    [
        'key' => 'payroll_summary',
        'label' => 'Bảng lương',
        'href' => $viewUrl . '/payroll/payroll_summary.php',
        'icon' => '▧',
        'roles' => ['admin', 'manager']
    ],
];

// Lọc menu theo Role hiện tại
$menus = array_filter($allMenus, function($m) use ($userRole) {
    return in_array($userRole, $m['roles']);
});

$secondaryMenus = [
    ['key' => 'client_portal', 'label' => 'Client Portal', 'href' => $viewUrl . '/client-portal/projects.php', 'icon' => '◇'],
    ['key' => 'help', 'label' => 'Trợ giúp', 'href' => '#help', 'icon' => '?'],
];
?>

<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php" class="sidebar-brand">
            <span class="brand-mark">CA</span>
            <span class="brand-text"><strong><?php echo htmlspecialchars($brandName); ?></strong></span>
        </a>
    </div>

    <div class="sidebar-scroll">
        <nav class="sidebar-nav">
            <?php foreach ($menus as $menu): ?>
                <a href="<?php echo htmlspecialchars($menu['href']); ?>" 
                   class="sidebar-link <?php echo $activeMenu === $menu['key'] ? 'is-active' : ''; ?>">
                    <span class="sidebar-icon"><?php echo htmlspecialchars($menu['icon']); ?></span>
                    <span><?php echo htmlspecialchars($menu['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-section">
            <div class="sidebar-section-title">KHÔNG GIAN KHÁC</div>
            <nav class="sidebar-nav sidebar-nav-compact">
                <?php foreach ($secondaryMenus as $menu): ?>
                    <a href="<?php echo htmlspecialchars($menu['href']); ?>" class="sidebar-link">
                        <span class="sidebar-icon"><?php echo htmlspecialchars($menu['icon']); ?></span>
                        <span><?php echo htmlspecialchars($menu['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <div class="sidebar-footer">
        <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
            <a href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php" class="btn btn-primary btn-block">＋ Tạo mới</a>
        <?php endif; ?>
        
        <a href="javascript:void(0)" onclick="handleLogout()" class="sidebar-link sidebar-link-danger">
            <span class="sidebar-icon">↪</span><span>Đăng xuất</span>
        </a>
    </div>
</aside>

<script>
/**
 * Xử lý đăng xuất: Xóa Token và gọi route xóa Session
 */
function handleLogout() {
    localStorage.removeItem('cah_token');
    window.location.href = "<?php echo $publicUrl; ?>/auth/logout";
}
</script>