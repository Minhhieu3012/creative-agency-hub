<?php
$pageTitle = $pageTitle ?? 'Creative Agency Hub';
$pageCss = $pageCss ?? [];
$brandName = $brandName ?? 'Creative Agency Hub';

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
| Hiện tại bạn đang chạy:
| http://localhost/creative-agency-hub/
|
| Nên baseUrl mặc định là /creative-agency-hub.
| Sau này nếu trỏ Apache thẳng vào public/ hoặc dùng Router đẹp,
| chỉ cần đổi $baseUrl trong layout/controller.
*/
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $assetUrl ?? ($baseUrl . '/public/assets');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/reset.css">
<link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/app.css">
<link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/layout.css">
<link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/components.css">
<link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/ui-states.css">
<link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/responsive.css">

<?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl); ?>/css/<?php echo htmlspecialchars($css); ?>">
<?php endforeach; ?>