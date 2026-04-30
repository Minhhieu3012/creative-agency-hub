<?php
$currentUser = $currentUser ?? [
    'name' => 'Nguyễn Quản Lý',
    'role' => 'Project Director',
    'avatar' => null,
];

$topbarTitle = $topbarTitle ?? '';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$userInitial = strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1, 'UTF-8'));
?>

<header class="app-topbar">
    <div class="topbar-left">
        <button class="icon-btn topbar-menu-btn" type="button" data-sidebar-toggle aria-label="Mở menu">
            ☰
        </button>

        <form class="topbar-search" action="#" method="GET">
            <span class="search-icon">⌕</span>
            <input type="search" name="q" placeholder="Tìm kiếm dự án, nhân sự, công việc...">
        </form>

        <?php if (!empty($topbarTitle)): ?>
            <div class="topbar-title"><?php echo htmlspecialchars($topbarTitle); ?></div>
        <?php endif; ?>
    </div>

    <div class="topbar-actions">
        <button class="icon-btn has-dot" type="button" aria-label="Thông báo" data-dropdown-trigger-standalone="notifications">
            ♢
        </button>

        <button class="icon-btn desktop-only" type="button" aria-label="Lịch sử">
            ↺
        </button>

        <button class="icon-btn desktop-only" type="button" aria-label="Trợ giúp">
            ?
        </button>

        <div class="topbar-divider"></div>

        <div class="user-menu" data-dropdown>
            <button class="user-menu-trigger" type="button" data-dropdown-trigger aria-label="Menu người dùng">
                <span class="user-meta">
                    <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>
                    <small><?php echo htmlspecialchars($currentUser['role']); ?></small>
                </span>

                <?php if (!empty($currentUser['avatar'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($currentUser['avatar']); ?>"
                        alt="<?php echo htmlspecialchars($currentUser['name']); ?>"
                        class="user-avatar"
                    >
                <?php else: ?>
                    <span class="user-avatar"><?php echo htmlspecialchars($userInitial); ?></span>
                <?php endif; ?>
            </button>

            <div class="dropdown-menu user-dropdown" data-dropdown-menu>
                <a href="<?php echo htmlspecialchars($viewUrl); ?>/hrm/profile.php">Hồ sơ cá nhân</a>
                <a href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/attendance.php">Chấm công hôm nay</a>
                <a href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php">Client Portal</a>
                <a href="<?php echo htmlspecialchars($viewUrl); ?>/auth/login.php" class="text-danger">Đăng xuất</a>
            </div>
        </div>
    </div>
</header>