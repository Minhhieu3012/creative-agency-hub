<?php
$pageTitle = $pageTitle ?? 'Creative Agency Hub';
$pageCss = $pageCss ?? [];
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $assetUrl ?? ($baseUrl . '/public/assets');

$globalCss = [
    'reset.css',
    'app.css',
    'components.css',
    'layout.css',
    'responsive.css',
    'ui-states.css',
];

$cssFiles = array_values(array_unique(array_merge($globalCss, $pageCss)));

if (!function_exists('cah_asset_url')) {
    function cah_asset_url(string $assetUrl, string $path): string {
        return rtrim($assetUrl, '/') . '/' . ltrim($path, '/');
    }
}
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<?php foreach ($cssFiles as $css): ?>
    <link
        rel="stylesheet"
        href="<?php echo htmlspecialchars(cah_asset_url($assetUrl . '/css', $css), ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo time(); ?>"
    >
<?php endforeach; ?>

<script>
    window.CAH_ASSET_URL = "<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>";
</script>