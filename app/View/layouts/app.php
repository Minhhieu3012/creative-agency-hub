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
    'name' => 'Creative User',
    'role' => 'Workspace',
    'avatar' => null,
];

$defaultJs = [
    'app.js',
    'sidebar.js',
    'dropdown.js',
    'modal.js',
    'toast.js',
    'forms.js',
];

$mergedJs = array_values(array_unique(array_merge($defaultJs, (array) $pageJs)));
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

    <script>
        window.CAH_CONFIG = window.CAH_CONFIG || {
            baseUrl: <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
            assetUrl: <?php echo json_encode($assetUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
            apiBaseUrl: <?php echo json_encode($baseUrl . '/public', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        };
    </script>

    <?php foreach ($mergedJs as $js): ?>
        <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
</body>
</html>