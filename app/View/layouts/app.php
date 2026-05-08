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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $currentUser ?? null;

if ($currentUser === null) {
    $currentUser = [
        'id' => $_SESSION['user_id'] ?? null,
        'full_name' => 'Người dùng',
        'name' => 'Người dùng',
        'role' => $_SESSION['user_role'] ?? 'user',
        'avatar' => null,
    ];

    if (!empty($_SESSION['user_id']) && class_exists('\App\Models\Auth\User')) {
        try {
            $userModel = new \App\Models\Auth\User();
            $user = $userModel->findById((int)$_SESSION['user_id']);

            if (is_array($user) && !empty($user)) {
                $currentUser = array_merge($currentUser, $user);
                $currentUser['name'] = $user['full_name'] ?? ($user['name'] ?? 'Người dùng');
            }
        } catch (\Throwable $e) {
            // Không phá layout nếu DB tạm lỗi. JS và fallback phía dưới vẫn xử lý được.
        }
    }
}

$clientConfig = [
    'baseUrl' => $baseUrl,
    'apiRoot' => $baseUrl . '/public',
];

$clientUser = [
    'id' => $currentUser['id'] ?? null,
    'full_name' => $currentUser['full_name'] ?? ($currentUser['name'] ?? 'Người dùng'),
    'name' => $currentUser['name'] ?? ($currentUser['full_name'] ?? 'Người dùng'),
    'email' => $currentUser['email'] ?? '',
    'role' => $currentUser['role'] ?? 'user',
    'avatar' => $currentUser['avatar'] ?? null,
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

    <script>
        window.CAH_CONFIG = <?php echo json_encode($clientConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.CAH_CURRENT_USER = <?php echo json_encode($clientUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/app.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/sidebar.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/dropdown.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/modal.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/toast.js"></script>
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/forms.js"></script>

    <?php foreach ($pageJs as $js): ?>
        <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/<?php echo htmlspecialchars($js, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
</body>
</html>