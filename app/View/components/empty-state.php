<?php
if (!function_exists('renderEmptyState')) {
    function renderEmptyState(
        string $title = 'Chưa có dữ liệu',
        string $message = 'Dữ liệu sẽ hiển thị tại đây khi hệ thống được cập nhật.',
        string $icon = '◇'
    ): string {
        ob_start();
        ?>
        <div class="empty-state">
            <div class="empty-state-icon"><?php echo htmlspecialchars($icon); ?></div>
            <h3><?php echo htmlspecialchars($title); ?></h3>
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}