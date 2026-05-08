<?php
$pageTitle = $pageTitle ?? 'Client Portal | Creative Agency Hub';
$pageCss = $pageCss ?? ['client-portal.css'];
$pageJs = $pageJs ?? ['client-portal.js'];
$brandName = $brandName ?? 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $assetUrl ?? ($baseUrl . '/public/assets');
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$currentUser = $currentUser ?? [
    'name' => 'Khách hàng',
    'role' => 'Client Portal',
    'avatar' => null,
];

/*
|--------------------------------------------------------------------------
| CLIENT ACTIVE NAV
|--------------------------------------------------------------------------
| Nếu page truyền $clientActive thì dùng biến đó.
| Nếu không truyền, layout tự nhận diện theo tên file hiện tại:
| - projects.php => projects
| - tasks.php    => tasks
| - support.php  => support
*/
$currentClientFile = basename($_SERVER['SCRIPT_NAME'] ?? '');

if (!isset($clientActive)) {
    $clientActive = match ($currentClientFile) {
        'tasks.php' => 'tasks',
        'support.php' => 'support',
        default => 'projects',
    };
}

$clientLinks = [
    [
        'key' => 'projects',
        'label' => 'Dự án',
        'href' => $viewUrl . '/client-portal/projects.php',
    ],
    [
        'key' => 'tasks',
        'label' => 'Tiến độ',
        'href' => $viewUrl . '/client-portal/tasks.php',
    ],
    [
        'key' => 'support',
        'label' => 'Hỗ trợ',
        'href' => $viewUrl . '/client-portal/support.php',
    ],
];

$userInitial = strtoupper(mb_substr($currentUser['name'] ?? 'K', 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body class="client-body">
    <div class="client-shell">
        <header class="client-topbar">
            <a href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php" class="client-brand">
                <span class="brand-mark">CA</span>
                <span><?php echo htmlspecialchars($brandName); ?></span>
            </a>

            <nav class="client-nav" aria-label="Điều hướng Client Portal">
                <?php foreach ($clientLinks as $link): ?>
                    <a
                        href="<?php echo htmlspecialchars($link['href']); ?>"
                        class="client-nav-link <?php echo $clientActive === $link['key'] ? 'is-active' : ''; ?>"
                    >
                        <?php echo htmlspecialchars($link['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="client-user">
                <span><?php echo htmlspecialchars($currentUser['name']); ?></span>

                <?php if (!empty($currentUser['avatar'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($currentUser['avatar']); ?>"
                        alt="<?php echo htmlspecialchars($currentUser['name']); ?>"
                        class="client-avatar"
                    >
                <?php else: ?>
                    <div class="client-avatar">
                        <?php echo htmlspecialchars($userInitial); ?>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="client-content">
            <?php echo $content ?? ''; ?>
        </main>
    </div>

    <?php require __DIR__ . '/../components/toast.php'; ?>

    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/app.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/toast.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
</body>
</html>