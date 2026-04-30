<?php
$pageTitle = $pageTitle ?? 'Creative Agency Hub';
$pageCss = $pageCss ?? [];
$brandName = $brandName ?? 'Creative Agency Hub';

/*
|--------------------------------------------------------------------------
| PROJECT BASE URL
|--------------------------------------------------------------------------
| Nếu bạn chạy bằng:
| http://localhost/creative-agency-hub/
| thì giữ nguyên dòng dưới.
|
| Nếu sau này bạn cấu hình Apache trỏ thẳng vào public/
| thì đổi thành:
| $baseUrl = '';
*/
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $baseUrl . '/public/assets';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/reset.css">
<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/app.css">
<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/layout.css">
<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/components.css">
<link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/responsive.css">

<?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/<?php echo htmlspecialchars($css); ?>">
<?php endforeach; ?>