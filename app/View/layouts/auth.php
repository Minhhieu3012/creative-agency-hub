<?php
/**
 * Auth Layout - Creative Agency Hub
 * Dùng cho các trang login/auth.
 * Bản này KHÔNG phụ thuộc APP_URL để tránh lỗi Undefined constant.
 */

$pageTitle = $pageTitle ?? 'Đăng nhập | Creative Agency Hub';
$pageCss = $pageCss ?? ['auth.css'];
$pageJs = $pageJs ?? ['app.js', 'forms.js', 'toast.js'];
$bodyClass = $bodyClass ?? 'auth-page';
$content = $content ?? '';

if (!function_exists('cah_detect_base_url')) {
    function cah_detect_base_url(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        $markers = [
            '/app/View/',
            '/app/view/',
            '/public/',
        ];

        foreach ($markers as $marker) {
            $pos = stripos($scriptName, $marker);

            if ($pos !== false) {
                $base = substr($scriptName, 0, $pos);
                return $base !== '' ? rtrim($base, '/') : '';
            }
        }

        return '/creative-agency-hub';
    }
}

$baseUrl = defined('APP_URL')
    ? rtrim((string) APP_URL, '/')
    : cah_detect_base_url();

if ($baseUrl === '') {
    $baseUrl = '/creative-agency-hub';
}

$assetUrl = $baseUrl . '/public/assets';

if (!function_exists('cah_asset')) {
    function cah_asset(string $path): string
    {
        global $assetUrl;
        return rtrim($assetUrl, '/') . '/' . ltrim($path, '/');
    }
}

$defaultCss = [
    'reset.css',
    'app.css',
    'components.css',
];

$cssFiles = array_values(array_unique(array_merge($defaultCss, (array) $pageCss)));

$defaultJs = [
    'app.js',
    'forms.js',
    'toast.js',
];

$jsFiles = array_values(array_unique(array_merge($defaultJs, (array) $pageJs)));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <meta name="description" content="Creative Agency Hub - Enterprise Suite">
    <meta name="theme-color" content="#00513A">

    <?php foreach ($cssFiles as $cssFile): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars(cah_asset('css/' . $cssFile)); ?>">
    <?php endforeach; ?>

    <script>
        window.CAH_CONFIG = {
            baseUrl: <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
            assetUrl: <?php echo json_encode($assetUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
            apiBaseUrl: <?php echo json_encode($baseUrl . '/public', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        };
    </script>
</head>

<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <?php echo $content; ?>

    <div class="toast-stack" data-toast-stack></div>

    <?php foreach ($jsFiles as $jsFile): ?>
        <script src="<?php echo htmlspecialchars(cah_asset('js/' . $jsFile)); ?>"></script>
    <?php endforeach; ?>
</body>
</html>