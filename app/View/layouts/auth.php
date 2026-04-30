<?php
/**
 * LAYOUT AUTH - FIX DOUBLE PUBLIC
 */
$pageTitle = $pageTitle ?? 'Đăng nhập | Creative Agency Hub';
$pageCss   = $pageCss   ?? ['auth.css'];
$pageJs    = $pageJs    ?? ['forms.js'];
$bodyClass = $bodyClass ?? 'auth-body';

// SỬA LỖI TẠI ĐÂY: APP_URL đã có sẵn /public rồi.
// Nếu nãy bạn viết APP_URL . '/public/assets' thì nó sẽ thành /public/public/assets.
$assetUrl = APP_URL . '/assets'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php require __DIR__ . '/../components/head.php'; ?>
    
    <?php foreach ($pageCss as $css): ?>
        <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <main class="auth-page-shell">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Nạp Scripts lõi -->
    <script src="<?php echo $assetUrl; ?>/js/app.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/toast.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <?php if ($js !== 'forms.js'): ?>
            <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>