<?php
$pageTitle = $pageTitle ?? 'Creative Agency Hub';
$pageCss = $pageCss ?? [];
$pageJs = $pageJs ?? [];
$activeMenu = $activeMenu ?? 'dashboard';
$brandName = $brandName ?? 'Creative Agency Hub';

$baseUrl = $baseUrl ?? (function () {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (strpos($scriptName, '/public/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/public'));
    }

    if (strpos($scriptName, '/app/View/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/app/View'));
    }

    $dir = dirname($scriptName);
    return $dir === '/' ? '' : $dir;
})();
$assetUrl = $baseUrl . '/public/assets';

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

    <script src="<?php echo $assetUrl; ?>/js/app.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/sidebar.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/dropdown.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/modal.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/toast.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <script src="<?php echo $assetUrl; ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
</body>
</html>