<?php
$pageHeading = $pageHeading ?? 'Tiêu đề trang';
$pageSubtitle = $pageSubtitle ?? '';
$pageAction = $pageAction ?? '';
?>

<div class="page-header">
    <div>
        <h1><?php echo htmlspecialchars($pageHeading); ?></h1>

        <?php if (!empty($pageSubtitle)): ?>
            <p><?php echo htmlspecialchars($pageSubtitle); ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($pageAction)): ?>
        <div class="page-header-action">
            <?php echo $pageAction; ?>
        </div>
    <?php endif; ?>
</div>