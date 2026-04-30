<?php
$pageTitle = $pageTitle ?? 'Creative Agency Hub';
$pageCss = $pageCss ?? [];
$pageJs = $pageJs ?? [];
$activeMenu = $activeMenu ?? 'dashboard';
$topbarTitle = $topbarTitle ?? '';
$brandName = $brandName ?? 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $assetUrl ?? ($baseUrl . '/public/assets');
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$currentUser = $currentUser ?? [
    'name' => 'Nguyễn Quản Lý',
    'role' => 'Project Director',
    'avatar' => null,
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body class="app-body">
    <div class="app-shell" data-layout="internal">
        <?php require __DIR__ . '/../components/sidebar.php'; ?>

        <section class="app-main">
            <?php require __DIR__ . '/../components/topbar.php'; ?>

            <main class="app-content">
                <?php echo $content ?? ''; ?>
            </main>
        </section>
    </div>

    <?php require __DIR__ . '/../components/toast.php'; ?>

    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/app.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/sidebar.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/dropdown.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/toast.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
</body>
</html>