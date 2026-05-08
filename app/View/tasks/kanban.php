<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Bảng Kanban | Creative Agency Hub';
$pageCss = ['tasks.css'];
$pageJs = ['tasks-kanban.js'];
$activeMenu = 'kanban';
$topbarTitle = 'Task Board';
$brandName = 'Creative Agency Hub';

$userRole = strtolower((string)($_SESSION['user_role'] ?? 'user'));
$isManager = $userRole === 'manager';

ob_start();
?>

<?php
$pageHeading = 'Bảng Công việc';
$pageSubtitle = 'Theo dõi luồng task: Cần sửa, Task mới, Chờ Duyệt và Hoàn Thành.';
$pageAction = '';

if ($isManager) {
    $pageAction = '
    <div class="task-top-actions">
        <button class="btn btn-primary" type="button" data-add-task>
            ＋ Tạo task
        </button>
    </div>';
}

require __DIR__ . '/../components/page-header.php';
?>

<section class="kanban-shell">
    <div
        id="js-board-message"
        style="display: none; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 12px;"
    ></div>

    <div class="kanban-board" data-kanban-board></div>
</section>

<?php
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>