<?php
$pageTitle = 'Bảng Kanban | Creative Agency Hub';
$pageCss = ['tasks.css'];
$pageJs = ['tasks-kanban.js'];
$activeMenu = 'kanban';
$topbarTitle = 'Task Board';
$brandName = 'Creative Agency Hub';

$columns = [
    'todo' => [
        'label' => 'Cần làm',
        'dot' => 'todo',
        'tasks' => [
            [
                'id' => 1,
                'tag' => 'UI/UX',
                'tag_tone' => 'info',
                'title' => 'Thiết kế UI Login',
                'description' => 'Hoàn thiện màn đăng nhập nội bộ và client portal.',
                'deadline' => '2 ngày tới',
                'assignee' => 'PA',
                'comments' => 4,
                'attachments' => 1,
                'progress' => 20,
            ],
            [
                'id' => 2,
                'tag' => 'Critical',
                'tag_tone' => 'danger',
                'title' => 'Cấu hình Database Core',
                'description' => 'Kiểm tra migration và khóa ngoại bảng chính.',
                'deadline' => 'Hôm nay',
                'assignee' => 'HA',
                'comments' => 2,
                'attachments' => 0,
                'progress' => 10,
            ],
            [
                'id' => 3,
                'tag' => 'Research',
                'tag_tone' => 'primary',
                'title' => 'Khảo sát trải nghiệm người dùng quý 4',
                'description' => 'Tổng hợp insight từ khách hàng và nhân sự nội bộ.',
                'deadline' => 'Tuần này',
                'assignee' => 'LM',
                'comments' => 0,
                'attachments' => 0,
                'progress' => 0,
            ],
        ],
    ],
    'doing' => [
        'label' => 'Đang thực hiện',
        'dot' => 'doing',
        'tasks' => [
            [
                'id' => 4,
                'tag' => 'Backend',
                'tag_tone' => 'warning',
                'title' => 'Fix bug API Authentication',
                'description' => 'Chuẩn hóa response login/logout và session state.',
                'deadline' => 'Còn 4h',
                'assignee' => 'HB',
                'comments' => 4,
                'attachments' => 2,
                'progress' => 65,
            ],
            [
                'id' => 5,
                'tag' => 'UI Design',
                'tag_tone' => 'info',
                'title' => 'Xây dựng Design System cho Creative Agency Hub',
                'description' => 'Đồng bộ button, form, card, layout và responsive.',
                'deadline' => '3 ngày tới',
                'assignee' => 'PA',
                'comments' => 12,
                'attachments' => 0,
                'progress' => 58,
            ],
        ],
    ],
    'review' => [
        'label' => 'Đang kiểm tra',
        'dot' => 'review',
        'tasks' => [
            [
                'id' => 6,
                'tag' => 'Pháp lý',
                'tag_tone' => 'warning',
                'title' => 'Soạn thảo hợp đồng lao động mẫu',
                'description' => 'Chờ trưởng phòng phê duyệt nội dung mẫu.',
                'deadline' => 'Chờ sếp duyệt',
                'assignee' => 'TM',
                'comments' => 1,
                'attachments' => 3,
                'progress' => 88,
            ],
        ],
    ],
    'done' => [
        'label' => 'Hoàn thành',
        'dot' => 'done',
        'tasks' => [
            [
                'id' => 7,
                'tag' => 'Nội bộ',
                'tag_tone' => 'success',
                'title' => 'Buổi họp Kick-off dự án',
                'description' => 'Đã hoàn tất checklist và biên bản họp.',
                'deadline' => 'Xong 12/10',
                'assignee' => 'TA',
                'comments' => 3,
                'attachments' => 1,
                'progress' => 100,
            ],
        ],
    ],
];

ob_start();
?>

<?php
$pageHeading = 'Bảng Công việc';
$pageSubtitle = 'Quản lý và theo dõi tiến độ dự án Creative Agency Hub theo từng trạng thái.';
$pageAction = '
<div class="task-top-actions">
    <div class="kanban-view-switch">
        <a class="is-active" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Kanban</a>
        <a href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a>
    </div>
    <button class="btn btn-primary" type="button" data-add-task>＋ Tạo Task mới</button>
</div>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="kanban-shell">
    <div class="task-filter-bar">
        <select class="form-select">
            <option>Dự án: NexusHR Web</option>
            <option>Brand Campaign Q4</option>
            <option>Client Portal Upgrade</option>
        </select>

        <select class="form-select">
            <option>Người phụ trách: Tất cả</option>
            <option>Phạm Anh</option>
            <option>Hiếu Backend</option>
            <option>Trần Minh</option>
        </select>

        <select class="form-select">
            <option>Thời gian: Tháng 10</option>
            <option>Tuần này</option>
            <option>Quý này</option>
        </select>

        <button class="btn btn-soft" type="button">Lọc task</button>
    </div>

    <div class="kanban-board" data-kanban-board>
        <?php foreach ($columns as $columnKey => $column): ?>
            <section class="kanban-column" data-kanban-column data-status="<?php echo htmlspecialchars($columnKey); ?>">
                <header class="kanban-column-head">
                    <div class="kanban-column-title">
                        <span class="kanban-dot <?php echo htmlspecialchars($column['dot']); ?>"></span>
                        <span><?php echo htmlspecialchars($column['label']); ?></span>
                        <span class="kanban-count" data-column-count><?php echo count($column['tasks']); ?></span>
                    </div>

                    <button class="kanban-column-menu" type="button">•••</button>
                </header>

                <div class="kanban-card-list" data-kanban-list>
                    <?php foreach ($column['tasks'] as $task): ?>
                        <article
                            class="task-card"
                            draggable="true"
                            data-task-card
                            data-task-id="<?php echo (int) $task['id']; ?>"
                            data-status="<?php echo htmlspecialchars($columnKey); ?>"
                            data-title="<?php echo htmlspecialchars($task['title']); ?>"
                            data-description="<?php echo htmlspecialchars($task['description']); ?>"
                        >
                            <div class="task-card-top">
                                <span class="badge badge-<?php echo htmlspecialchars($task['tag_tone']); ?>">
                                    <?php echo htmlspecialchars($task['tag']); ?>
                                </span>

                                <button class="kanban-column-menu" type="button">⋮</button>
                            </div>

                            <h3 class="task-card-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                            <p class="task-card-desc"><?php echo htmlspecialchars($task['description']); ?></p>

                            <div class="task-card-progress">
                                <div class="progress-line">
                                    <div class="progress-line-fill" style="width: <?php echo (int) $task['progress']; ?>%;"></div>
                                </div>
                                <small><?php echo (int) $task['progress']; ?>% hoàn thành</small>
                            </div>

                            <div class="task-assignee-row">
                                <div class="task-assignee">
                                    <span class="task-avatar"><?php echo htmlspecialchars($task['assignee']); ?></span>
                                    <span><?php echo htmlspecialchars($task['deadline']); ?></span>
                                </div>

                                <div class="task-card-meta-group">
                                    <span>▣ <?php echo (int) $task['attachments']; ?></span>
                                    <span>□ <?php echo (int) $task['comments']; ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($columnKey === 'todo'): ?>
                    <button class="task-add-card" type="button" data-add-task>＋ Thêm Task</button>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</section>

<?php
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>