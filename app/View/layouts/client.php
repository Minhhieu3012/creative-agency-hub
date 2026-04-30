<?php
$pageTitle = $pageTitle ?? 'Client Portal | Creative Agency Hub';
$pageCss = $pageCss ?? ['client-portal.css'];
$pageJs = $pageJs ?? ['client-portal.js'];
$brandName = $brandName ?? 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $baseUrl . '/public/assets';

$currentUser = $currentUser ?? [
    'name' => 'Khách hàng',
    'role' => 'Client Portal',
    'avatar' => null,
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body class="client-body">
    <div class="client-shell">
        <header class="client-topbar">
            <a href="<?php echo $baseUrl; ?>/app/View/client-portal/projects.php" class="client-brand">
                <span class="brand-mark">CA</span>
                <span><?php echo htmlspecialchars($brandName); ?></span>
            </a>

            <nav class="client-nav">
                <a href="<?php echo $baseUrl; ?>/app/View/client-portal/projects.php" class="client-nav-link">Dự án</a>
                <a href="<?php echo $baseUrl; ?>/app/View/client-portal/tasks.php" class="client-nav-link">Tiến độ</a>
                <a href="#" class="client-nav-link">Hỗ trợ</a>
            </nav>

            <div class="client-user">
                <span><?php echo htmlspecialchars($currentUser['name']); ?></span>
                <div class="client-avatar">
                    <?php echo strtoupper(mb_substr($currentUser['name'], 0, 1, 'UTF-8')); ?>
                </div>
            </div>
        </header>

        <main class="client-content">
            <?php echo $content ?? ''; ?>
        </main>
    </div>

    <?php require __DIR__ . '/../components/toast.php'; ?>

    <script src="<?php echo $assetUrl; ?>/js/app.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/modal.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/toast.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <script src="<?php echo $assetUrl; ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
</body>
</html>