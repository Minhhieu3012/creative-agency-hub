<?php
$currentUser = $currentUser ?? [
    'name' => 'Người dùng',
    'full_name' => 'Người dùng',
    'role' => 'user',
    'avatar' => null,
];

$topbarTitle = $topbarTitle ?? '';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$userName = $currentUser['full_name'] ?? ($currentUser['name'] ?? 'Người dùng');
$userRole = strtolower((string)($currentUser['role'] ?? 'user'));
$userAvatar = $currentUser['avatar'] ?? null;

$roleLabels = [
    'admin' => 'ADMIN',
    'manager' => 'MANAGER',
    'employee' => 'EMPLOYEE',
    'client' => 'CLIENT',
    'user' => 'USER',
];

$userRoleLabel = $roleLabels[$userRole] ?? strtoupper($userRole ?: 'USER');
$userInitial = strtoupper(mb_substr($userName ?: 'U', 0, 1, 'UTF-8'));

if (!function_exists('cah_topbar_avatar_url')) {
    function cah_topbar_avatar_url($avatar, $baseUrl) {
        $avatar = trim((string)$avatar);

        if ($avatar === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $avatar) || substr($avatar, 0, 1) === '/') {
            return $avatar;
        }

        if (substr($avatar, 0, 7) === 'public/') {
            return rtrim($baseUrl, '/') . '/' . ltrim($avatar, '/');
        }

        if (substr($avatar, 0, 8) === 'uploads/') {
            return rtrim($baseUrl, '/') . '/public/' . ltrim($avatar, '/');
        }

        return rtrim($baseUrl, '/') . '/public/uploads/avatars/' . ltrim($avatar, '/');
    }
}

$userAvatarUrl = cah_topbar_avatar_url($userAvatar, $baseUrl);
?>

<header class="app-topbar">
    <div class="topbar-left">
        <button class="icon-btn topbar-menu-btn" type="button" data-sidebar-toggle aria-label="Ẩn hoặc hiện sidebar">
            ☰
        </button>

        <form class="topbar-search" action="#" method="GET" data-topbar-search>
            <button class="search-icon" type="submit" aria-label="Tìm kiếm">
                ⌕
            </button>
            <input type="search" name="q" placeholder="Tìm kiếm dự án, nhân sự, công việc...">
        </form>

        <?php if (!empty($topbarTitle)): ?>
            <div class="topbar-title"><?php echo htmlspecialchars($topbarTitle, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    </div>

    <div class="topbar-actions">
        <button class="icon-btn topbar-refresh-btn" type="button" aria-label="Tải lại trang" title="Tải lại trang" data-refresh-page>
            ↻
        </button>

        <div class="topbar-divider"></div>

        <div class="user-menu" data-dropdown>
            <button class="user-menu-trigger" type="button" data-dropdown-trigger aria-label="Menu người dùng">
                <span class="user-meta">
                    <strong data-user-name><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small data-user-role><?php echo htmlspecialchars($userRoleLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                </span>

                <?php if (!empty($userAvatarUrl)): ?>
                    <img
                        src="<?php echo htmlspecialchars($userAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>"
                        class="user-avatar"
                        data-user-avatar
                    >
                <?php else: ?>
                    <span class="user-avatar" data-user-avatar><?php echo htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </button>

            <div class="dropdown-menu user-dropdown" data-dropdown-menu>
                <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/hrm/profile.php">Hồ sơ cá nhân</a>
                <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/payroll/attendance.php">Chấm công hôm nay</a>
                <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php">Client Portal</a>
                <a href="#" class="text-danger" data-logout>Đăng xuất</a>
            </div>
        </div>
    </div>
</header>