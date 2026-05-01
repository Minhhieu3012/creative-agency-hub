<?php
/**
 * Client Layout - Creative Agency Hub
 * Dùng cho Client Portal: login-client.php, projects.php, tasks.php, support.php
 * Layout này tự fallback base URL, không phụ thuộc APP_URL.
 */

$pageTitle = $pageTitle ?? 'Client Portal | Creative Agency Hub';
$pageCss = $pageCss ?? ['client-portal.css'];
$pageJs = $pageJs ?? ['app.js', 'client-portal.js', 'toast.js'];
$bodyClass = $bodyClass ?? 'client-page';
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
                return rtrim(substr($scriptName, 0, $pos), '/');
            }
        }

        return '/creative-agency-hub';
    }
}

$baseUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : cah_detect_base_url();
$assetUrl = $baseUrl . '/public/assets';

if (!function_exists('cah_asset')) {
    function cah_asset(string $path): string
    {
        global $assetUrl;
        return $assetUrl . '/' . ltrim($path, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <meta name="description" content="Creative Agency Hub Client Portal">
    <meta name="theme-color" content="#00513A">

    <link rel="stylesheet" href="<?php echo htmlspecialchars(cah_asset('css/reset.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cah_asset('css/app.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cah_asset('css/components.css')); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(cah_asset('css/ui-states.css')); ?>">

    <?php foreach ((array) $pageCss as $cssFile): ?>
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

    <?php
    $defaultJs = ['app.js', 'client-portal.js', 'toast.js'];
    $mergedJs = array_values(array_unique(array_merge($defaultJs, (array) $pageJs)));
    ?>

    <?php foreach ($mergedJs as $jsFile): ?>
        <script src="<?php echo htmlspecialchars(cah_asset('js/' . $jsFile)); ?>"></script>
    <?php endforeach; ?>
</body>
</html>