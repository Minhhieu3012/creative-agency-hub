<?php
$pageTitle = 'Quản lý dự án | Creative Agency Hub';
$pageCss = ['tasks.css', 'dashboard.css'];
$pageJs = ['dashboard.js'];
$activeMenu = 'projects';
$topbarTitle = 'Dự án';
$brandName = 'Creative Agency Hub';

$projects = $projects ?? [
    [
        'name' => 'NexusHR Web Platform',
        'description' => 'Xây dựng nền tảng quản lý nhân sự, công việc và cổng khách hàng.',
        'status' => 'Đang triển khai',
        'progress' => 78,
        'tasks' => 42,
        'members' => 12,
        'deadline' => '25/12/2026',
    ],
    [
        'name' => 'Brand Campaign Q4',
        'description' => 'Quản trị chiến dịch sáng tạo, tracking asset, feedback và phê duyệt.',
        'status' => 'Đang kiểm tra',
        'progress' => 64,
        'tasks' => 28,
        'members' => 8,
        'deadline' => '15/01/2027',
    ],
    [
        'name' => 'Client Portal Upgrade',
        'description' => 'Nâng cấp trải nghiệm khách hàng, phản hồi task và báo cáo tiến độ.',
        'status' => 'Lên kế hoạch',
        'progress' => 36,
        'tasks' => 19,
        'members' => 6,
        'deadline' => '08/02/2027',
    ],
];

ob_start();
?>

<?php
$pageHeading = 'Quản lý Dự án';
$pageSubtitle = 'Theo dõi tiến độ, phân bổ nhân sự và kiểm soát trạng thái các dự án đang vận hành.';
$pageAction = '<a class="btn btn-light" href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a><a class="btn btn-primary" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Mở Kanban</a>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="task-shell">
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">▣</div>
            <div class="stat-card-body">
                <span>Tổng dự án</span>
                <strong data-count-to="15">0</strong>
                <small>Đang theo dõi</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">☑</div>
            <div class="stat-card-body">
                <span>Task đang mở</span>
                <strong data-count-to="89">0</strong>
                <small>Trong tháng này</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◔</div>
            <div class="stat-card-body">
                <span>Tiến độ TB</span>
                <strong><span data-count-to="72">0</span>%</strong>
                <small>+8% so với tuần trước</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Rủi ro deadline</span>
                <strong data-count-to="4">0</strong>
                <small>Cần xử lý</small>
            </div>
        </article>
    </div>

    <div class="task-filter-bar">
        <div class="input-with-icon">
            <span class="input-icon">⌕</span>
            <input class="form-control" type="search" placeholder="Tìm kiếm dự án...">
        </div>

        <select class="form-select">
            <option>Tất cả trạng thái</option>
            <option>Đang triển khai</option>
            <option>Đang kiểm tra</option>
            <option>Lên kế hoạch</option>
        </select>

        <select class="form-select">
            <option>Deadline gần nhất</option>
            <option>Tiến độ cao nhất</option>
            <option>Rủi ro cao nhất</option>
        </select>

        <button class="btn btn-soft" type="button">Lọc dữ liệu</button>
    </div>

    <section class="project-grid">
        <?php foreach ($projects as $project): ?>
            <article class="project-card">
                <div class="project-card-head">
                    <div class="project-card-title-row">
                        <h2><?php echo htmlspecialchars($project['name']); ?></h2>
                        <span class="project-status-pill"><?php echo htmlspecialchars($project['status']); ?></span>
                    </div>

                    <p><?php echo htmlspecialchars($project['description']); ?></p>
                </div>

                <div class="project-card-meta">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: <?php echo (int) $project['progress']; ?>%;"></div>
                    </div>

                    <div class="project-progress-meta">
                        <span><?php echo (int) $project['progress']; ?>% hoàn thành</span>
                        <span>Deadline: <?php echo htmlspecialchars($project['deadline']); ?></span>
                    </div>

                    <div class="project-stat-row">
                        <div class="project-mini-stat">
                            <strong><?php echo (int) $project['tasks']; ?></strong>
                            <span>Tasks</span>
                        </div>

                        <div class="project-mini-stat">
                            <strong><?php echo (int) $project['members']; ?></strong>
                            <span>Members</span>
                        </div>

                        <div class="project-mini-stat">
                            <strong><?php echo (int) $project['progress']; ?>%</strong>
                            <span>Progress</span>
                        </div>
                    </div>
                </div>

                <div class="project-card-footer">
                    <div class="avatar-stack">
                        <span>A</span>
                        <span>B</span>
                        <span>+<?php echo max(0, (int) $project['members'] - 2); ?></span>
                    </div>

                    <a href="/creative-agency-hub/app/View/tasks/kanban.php" class="btn btn-light">Xem bảng</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>