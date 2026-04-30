<?php
$currentUser = $currentUser ?? [
    'name' => 'Nguyễn Quản Lý',
    'role' => 'Project Director',
    'avatar' => null,
];

$topbarTitle = $topbarTitle ?? '';
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
        <button class="icon-btn has-dot" type="button" aria-label="Thông báo">
            ♢
        </button>

        <button class="icon-btn" type="button" aria-label="Lịch sử">
            ↺
        </button>

        <button class="icon-btn" type="button" aria-label="Trợ giúp">
            ?
        </button>

        <div class="topbar-divider"></div>

        <div class="user-menu" data-dropdown>
            <button class="user-menu-trigger" type="button" data-dropdown-trigger>
                <span class="user-meta">
                    <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>
                    <small><?php echo htmlspecialchars($currentUser['role']); ?></small>
                </span>

                <?php if (!empty($currentUser['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="<?php echo htmlspecialchars($currentUser['name']); ?>" class="user-avatar">
                <?php else: ?>
                    <span class="user-avatar">
                        <?php echo strtoupper(mb_substr($currentUser['name'], 0, 1, 'UTF-8')); ?>
                    </span>
                <?php endif; ?>
            </button>

            <div class="dropdown-menu user-dropdown" data-dropdown-menu>
                <a href="/hrm/profile">Hồ sơ cá nhân</a>
                <a href="#">Cài đặt tài khoản</a>
                <a href="/logout" class="text-danger">Đăng xuất</a>
            </div>
        </div>
    </div>
</header>