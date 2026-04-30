<?php
$pageTitle = 'Gantt Chart | Creative Agency Hub';
$pageCss = ['tasks.css'];
$pageJs = ['tasks-gantt.js'];
$activeMenu = 'gantt';
$topbarTitle = 'Gantt Chart';
$brandName = 'Creative Agency Hub';

$tasks = $tasks ?? [
    [
        'name' => 'Thiết kế UI Login',
        'bars' => [
            'mon' => ['label' => 'Hoàn thành 100%', 'type' => 'done'],
            'tue' => null,
            'wed' => null,
            'thu' => null,
            'fri' => null,
            'sat' => null,
            'sun' => null,
        ],
    ],
    [
        'name' => 'Cấu hình Database',
        'bars' => [
            'mon' => null,
            'tue' => null,
            'wed' => ['label' => 'Đang chạy - 65%', 'type' => 'running'],
            'thu' => ['label' => 'Đang chạy - 65%', 'type' => 'running'],
            'fri' => ['label' => 'Đang chạy - 65%', 'type' => 'running'],
            'sat' => ['label' => 'Đang chạy - 65%', 'type' => 'running'],
            'sun' => null,
        ],
    ],
    [
        'name' => 'Fix bug Auth API',
        'bars' => [
            'mon' => null,
            'tue' => null,
            'wed' => null,
            'thu' => null,
            'fri' => ['label' => 'Chưa bắt đầu', 'type' => 'planned'],
            'sat' => ['label' => 'Chưa bắt đầu', 'type' => 'planned'],
            'sun' => null,
        ],
    ],
    [
        'name' => 'Client Portal Feedback',
        'bars' => [
            'mon' => null,
            'tue' => ['label' => 'Đang chạy - 40%', 'type' => 'running'],
            'wed' => ['label' => 'Đang chạy - 40%', 'type' => 'running'],
            'thu' => null,
            'fri' => null,
            'sat' => null,
            'sun' => null,
        ],
    ],
];

$days = [
    'mon' => 'TH 2',
    'tue' => 'TH 3',
    'wed' => 'TH 4',
    'thu' => 'TH 5',
    'fri' => 'TH 6',
    'sat' => 'TH 7',
    'sun' => 'CN',
];

ob_start();
?>

<?php
$pageHeading = 'Biểu đồ Gantt';
$pageSubtitle = 'Theo dõi lịch trình, deadline và trạng thái triển khai của các đầu việc trong dự án.';
$pageAction = '
<div class="task-top-actions">
    <div class="kanban-view-switch">
        <a href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Kanban</a>
        <a class="is-active" href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a>
    </div>
    <button class="btn btn-primary" type="button">＋ Tạo lịch mới</button>
</div>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="task-shell">
    <div class="task-filter-bar">
        <select class="form-select">
            <option>Dự án: NexusHR Web</option>
            <option>Brand Campaign Q4</option>
            <option>Client Portal Upgrade</option>
        </select>

        <select class="form-select">
            <option>Tháng 10, 2026</option>
            <option>Tháng 11, 2026</option>
            <option>Tháng 12, 2026</option>
        </select>

        <button class="btn btn-soft is-active" type="button" data-gantt-range="Tuần này">Tuần</button>
        <button class="btn btn-light" type="button" data-gantt-range="Tháng này">Tháng</button>
        <button class="btn btn-light" type="button" data-gantt-range="Quý này">Quý</button>
    </div>

    <article class="card gantt-card">
        <div class="card-header gantt-toolbar">
            <div>
                <h2 class="section-title">Lịch trình dự án</h2>
                <p class="section-subtitle">Đang xem: <strong data-current-range>Tuần này</strong></p>
            </div>

            <div class="gantt-legend">
                <span><i class="done"></i> Hoàn thành</span>
                <span><i class="running"></i> Đang chạy</span>
                <span><i class="planned"></i> Dự kiến</span>
            </div>
        </div>

        <div class="gantt-table-wrap">
            <table class="gantt-table">
                <thead>
                    <tr>
                        <th>Công việc</th>
                        <?php foreach ($days as $day): ?>
                            <th><?php echo htmlspecialchars($day); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['name']); ?></td>

                            <?php foreach ($days as $key => $day): ?>
                                <td class="gantt-timeline-cell">
                                    <?php if (!empty($task['bars'][$key])): ?>
                                        <div class="gantt-bar <?php echo htmlspecialchars($task['bars'][$key]['type']); ?>">
                                            <?php echo htmlspecialchars($task['bars'][$key]['label']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-body" style="text-align: center;">
            <a href="/creative-agency-hub/app/View/tasks/kanban.php" class="btn btn-soft">
                Xem bảng Kanban dự án →
            </a>
        </div>
    </article>

    <section class="project-grid">
        <article class="quick-summary-card">
            <div>
                <span>Tổng quan lịch trình</span>
                <strong>76%</strong>
                <p>Tiến độ đang đi đúng kế hoạch. Có 2 task cần theo dõi sát trong tuần này.</p>
            </div>
            <button class="btn btn-light" type="button">Xem task rủi ro</button>
        </article>

        <article class="card">
            <div class="card-body">
                <h2 class="section-title">Mốc quan trọng</h2>
                <div class="activity-timeline" style="margin-top: 24px;">
                    <div class="activity-item">
                        <div class="activity-icon primary">1</div>
                        <div class="activity-content">
                            <strong>Hoàn thiện UI nền</strong>
                            <p>Layout, component và màn login.</p>
                            <time>Đã hoàn thành</time>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-icon info">2</div>
                        <div class="activity-content">
                            <strong>Tích hợp Task Board</strong>
                            <p>Kanban kéo-thả và Gantt preview.</p>
                            <time>Đang thực hiện</time>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-body">
                <h2 class="section-title">Tài nguyên tuần này</h2>
                <p class="section-subtitle">Design và Backend đang có tải công việc cao nhất. Nên hạn chế thêm task mới trong 48h tới.</p>

                <div class="kpi-list" style="margin-top: 24px;">
                    <div class="kpi-line">
                        <div class="kpi-line-head">
                            <span>Design</span>
                            <span>88%</span>
                        </div>
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: 88%;"></div>
                        </div>
                    </div>

                    <div class="kpi-line">
                        <div class="kpi-line-head">
                            <span>Backend</span>
                            <span>82%</span>
                        </div>
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: 82%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>