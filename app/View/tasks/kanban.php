<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = strtolower((string)($_SESSION['user_role'] ?? 'employee'));
$isManagerLike = in_array($userRole, ['admin', 'manager'], true);

$pageTitle = 'Bảng Kanban | Creative Agency Hub';
$pageCss = ['tasks.css'];
$pageJs = ['tasks-kanban.js']; 
$activeMenu = 'kanban';
$topbarTitle = 'Task Board';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Bảng Công việc';
$pageSubtitle = 'Quản lý và theo dõi tiến độ dự án Creative Agency Hub theo từng trạng thái.';

$createTaskBtn = $isManagerLike 
    ? '<button class="btn btn-primary" type="button" data-add-task>＋ Tạo Task mới</button>' 
    : '';

$pageAction = '
<div class="task-top-actions">
    <div class="kanban-view-switch">
        <a class="is-active" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Kanban</a>
        <a href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a>
    </div>
    ' . $createTaskBtn . '
</div>';

require __DIR__ . '/../components/page-header.php';
?>

<section class="kanban-shell">
    <div class="task-filter-bar" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 24px;">
        
        <button class="btn btn-warning" type="button" id="js-btn-upcoming" style="font-weight: 700; display: flex; gap: 6px; align-items: center;">
            <span style="font-size: 1.1em;"></span> Deadline (3 ngày)
        </button>

        <button class="btn btn-soft" type="button" id="js-btn-filter" style="margin-left: auto;">↻ Làm mới bảng</button>
    </div>

    <div id="js-board-message" style="display: none; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 8px;"></div>

    <div class="kanban-board" data-kanban-board>
        <section class="kanban-column" data-kanban-column data-status="todo">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot todo"></span>
                    <span>Cần làm</span>
                    <span class="kanban-count" id="js-count-todo">0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-todo" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
            
            <?php if ($isManagerLike): ?>
            <button class="task-add-card" type="button" data-add-task>＋ Thêm Task</button>
            <?php endif; ?>
        </section>

        <section class="kanban-column" data-kanban-column data-status="doing">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot doing"></span>
                    <span>Đang thực hiện</span>
                    <span class="kanban-count" id="js-count-doing">0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-doing" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>

        <section class="kanban-column" data-kanban-column data-status="review">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot review"></span>
                    <span>Đang kiểm tra</span>
                    <span class="kanban-count" id="js-count-review">0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-review" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>

        <section class="kanban-column" data-kanban-column data-status="done">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot done"></span>
                    <span>Hoàn thành</span>
                    <span class="kanban-count" id="js-count-done">0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-done" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>
    </div>
</section>

<?php
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>