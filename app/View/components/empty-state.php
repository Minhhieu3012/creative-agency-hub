<?php
/*
|--------------------------------------------------------------------------
| Empty State Component
|--------------------------------------------------------------------------
| Cách dùng:
|
| $emptyIcon = '☷';
| $emptyTitle = 'Chưa có dữ liệu';
| $emptyDescription = 'Dữ liệu sẽ hiển thị sau khi backend được kết nối.';
| $emptyAction = '<button class="btn btn-primary">Tạo mới</button>';
| require __DIR__ . '/../components/empty-state.php';
|
*/

$emptyIcon = $emptyIcon ?? '☷';
$emptyTitle = $emptyTitle ?? 'Chưa có dữ liệu';
$emptyDescription = $emptyDescription ?? 'Dữ liệu sẽ hiển thị tại đây sau khi hệ thống được kết nối.';
$emptyAction = $emptyAction ?? '';
$emptyTone = $emptyTone ?? 'default';
?>

<div class="ui-empty-state ui-empty-state-<?php echo htmlspecialchars($emptyTone); ?>">
    <div class="ui-empty-icon">
        <?php echo htmlspecialchars($emptyIcon); ?>
    </div>

    <div class="ui-empty-content">
        <h3><?php echo htmlspecialchars($emptyTitle); ?></h3>
        <p><?php echo htmlspecialchars($emptyDescription); ?></p>
    </div>

    <?php if (!empty($emptyAction)): ?>
        <div class="ui-empty-action">
            <?php echo $emptyAction; ?>
        </div>
    <?php endif; ?>
</div>