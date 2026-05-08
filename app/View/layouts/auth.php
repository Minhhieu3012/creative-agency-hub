<?php
/**
 * LAYOUT AUTH - HYBRID VERSION
 * Kết hợp tính an toàn của mã mới và cấu trúc chuẩn của mã cũ
 */

// 1. PHÒNG THỦ: Đảm bảo APP_URL luôn tồn tại để tránh Fatal Error khi mất context
if (!defined('APP_URL')) {
    define('APP_URL', '/creative-agency-hub/public');
}

// 2. KHỞI TẠO BIẾN MẶC ĐỊNH
$pageTitle = $pageTitle ?? 'Đăng nhập | Creative Agency Hub';
$pageCss   = $pageCss   ?? ['auth.css'];
$pageJs    = $pageJs    ?? ['forms.js'];
$bodyClass = $bodyClass ?? 'auth-body';

// 3. XỬ LÝ ĐƯỜNG DẪN ASSETS (Fix Double Public)
$assetUrl = APP_URL . '/assets'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <?php 
    // Kiểm tra file trước khi require để tránh lỗi hệ thống
    $headPath = __DIR__ . '/../components/head.php';
    if (file_exists($headPath)) {
        require $headPath; 
    }
    ?>
    
    <?php foreach ($pageCss as $css): ?>
        <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <main class="auth-page-shell">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- 4. NẠP SCRIPTS LÕI -->
    <script src="<?php echo $assetUrl; ?>/js/app.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/toast.js"></script>
    <script src="<?php echo $assetUrl; ?>/js/forms.js"></script>

    <!-- 5. NẠP SCRIPTS BỔ SUNG (Loại trừ forms.js đã nạp ở trên) -->
    <?php foreach ($pageJs as $js): ?>
        <?php if ($js !== 'forms.js'): ?>
            <script src="<?php echo htmlspecialchars($assetUrl); ?>/js/<?php echo htmlspecialchars($js); ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>