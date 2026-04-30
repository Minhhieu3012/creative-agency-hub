<?php
$pageTitle = $pageTitle ?? 'Đăng nhập | Creative Agency Hub';
$pageCss = $pageCss ?? ['auth.css'];
$pageJs = $pageJs ?? ['forms.js'];
$bodyClass = $bodyClass ?? 'auth-body';
$brandName = $brandName ?? 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $baseUrl . '/public/assets';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php require __DIR__ . '/../components/head.php'; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <main class="auth-page-shell">
        <?php echo $content ?? ''; ?>
    </main>

    <script src="<?php echo $assetUrl; ?>/js/app.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/toast.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <?php if ($js !== 'forms.js'): ?>
            <script src="<?php echo $assetUrl; ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>