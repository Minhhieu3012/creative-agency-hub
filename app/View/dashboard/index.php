<?php
$pageTitle = 'Bảng điều khiển | Creative Agency Hub';
$pageCss = ['dashboard.css'];
$pageJs = ['dashboard.js'];
$activeMenu = 'dashboard';
$topbarTitle = 'Manager Dashboard';
$brandName = 'Creative Agency Hub';

$currentUser = $currentUser ?? [
    'name' => 'Nguyễn Quản Lý',
    'role' => 'Project Director',
    'avatar' => null,
];

$stats = $stats ?? [
    [
        'title' => 'Dự án đang chạy',
        'value' => 15,
        'note' => '+12% so với tháng trước',
        'icon' => '▦',
        'tone' => 'primary',
    ],
    [
        'title' => 'Nhân sự tham gia',
        'value' => 48,
        'note' => 'Đang hoạt động',
        'icon' => '◉',
        'tone' => 'info',
    ],
    [
        'title' => 'Tiến độ trung bình',
        'value' => 72,
        'note' => 'Mục tiêu tháng này',
        'icon' => '◔',
        'tone' => 'primary',
    ],
    [
        'title' => 'Task quá hạn',
        'value' => 4,
        'note' => 'Cần xử lý hôm nay',
        'icon' => '!',
        'tone' => 'danger',
    ],
];

$projects = $projects ?? [
    [
        'name' => 'Nâng cấp Core Banking',
        'deadline' => '25 TH12, 2026',
        'progress' => 85,
        'tasks' => '42/50 Tasks',
        'tone' => 'primary',
        'members' => ['A', 'B', '+4'],
    ],
    [
        'name' => 'Thiết kế Website Corporate',
        'deadline' => '15 TH01, 2027',
        'progress' => 45,
        'tasks' => '18/40 Tasks',
        'tone' => 'warning',
        'members' => ['D', 'M', '+2'],
    ],
];

$activities = $activities ?? [
    [
        'icon' => '✓',
        'tone' => 'primary',
        'title' => 'Hoàn thành Milestone 2',
        'description' => 'Dự án Core Banking - <a href="#">Trần Văn A</a>',
        'time' => '10 phút trước',
    ],
    [
        'icon' => '□',
        'tone' => 'info',
        'title' => 'Bình luận mới',
        'description' => '“Cần kiểm tra lại UI trên thiết bị mobile...”',
        'time' => '2 giờ trước',
    ],
    [
        'icon' => '△',
        'tone' => 'danger',
        'title' => 'Task quá hạn',
        'description' => 'Tối ưu hóa DB - Team Backend',
        'time' => '5 giờ trước',
    ],
    [
        'icon' => '+',
        'tone' => 'primary',
        'title' => 'Thêm nhân sự mới',
        'description' => 'Lê Thị B gia nhập Design Team',
        'time' => 'Hôm qua',
    ],
];

$resources = $resources ?? [
    ['label' => 'Dev Team', 'value' => 82],
    ['label' => 'Design', 'value' => 66],
    ['label' => 'Marketing', 'value' => 54],
    ['label' => 'QA/QC', 'value' => 72],
];

ob_start();
?>

<?php
$pageHeading = 'Chào buổi sáng, Quản lý!';
$pageSubtitle = 'Dưới đây là tổng quan tình hình công việc trong ngày hôm nay của Creative Agency Hub.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="stat-grid" style="margin-bottom: 28px;">
    <?php foreach ($stats as $stat): ?>
        <article class="stat-card <?php echo $stat['tone'] === 'danger' ? 'stat-card-danger' : ''; ?>">
            <div class="stat-card-icon"><?php echo htmlspecialchars($stat['icon']); ?></div>
            <div class="stat-card-body">
                <span><?php echo htmlspecialchars($stat['title']); ?></span>

                <?php if ($stat['title'] === 'Tiến độ trung bình'): ?>
                    <strong><span data-count-to="<?php echo (int) $stat['value']; ?>">0</span>%</strong>
                <?php elseif ($stat['title'] === 'Task quá hạn'): ?>
                    <strong><span data-count-to="<?php echo (int) $stat['value']; ?>" data-pad="2">00</span></strong>
                <?php else: ?>
                    <strong data-count-to="<?php echo (int) $stat['value']; ?>">0</strong>
                <?php endif; ?>

                <small><?php echo htmlspecialchars($stat['note']); ?></small>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid">
    <div class="dashboard-main-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Tiến độ Dự án Trọng điểm</h2>
                <a href="/creative-agency-hub/app/View/tasks/projects.php">Xem tất cả</a>
            </div>

            <div class="card-body dashboard-project-list">
                <?php foreach ($projects as $project): ?>
                    <div class="project-progress-item">
                        <div class="project-progress-head">
                            <div class="project-progress-title">
                                <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                <small>Deadline: <?php echo htmlspecialchars($project['deadline']); ?></small>
                            </div>

                            <div class="avatar-stack">
                                <?php foreach ($project['members'] as $member): ?>
                                    <span><?php echo htmlspecialchars($member); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="progress-line">
                            <div
                                class="progress-line-fill <?php echo $project['tone'] === 'warning' ? 'warning' : ''; ?>"
                                data-progress="<?php echo (int) $project['progress']; ?>"
                            ></div>
                        </div>

                        <div class="project-progress-meta">
                            <span><?php echo (int) $project['progress']; ?>% Hoàn thành</span>
                            <span><?php echo htmlspecialchars($project['tasks']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Phân bổ nguồn lực</h2>
                <a href="/creative-agency-hub/app/View/hrm/employees.php">Chi tiết</a>
            </div>

            <div class="card-body">
                <div class="resource-chart">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-bar">
                            <div class="resource-bar-track">
                                <div class="resource-bar-fill" style="height: <?php echo (int) $resource['value']; ?>%;"></div>
                            </div>
                            <strong><?php echo htmlspecialchars($resource['label']); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </div>

    <aside class="dashboard-side-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Hoạt động gần đây</h2>
            </div>

            <div class="card-body">
                <div class="activity-timeline">
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo htmlspecialchars($activity['tone']); ?>">
                                <?php echo htmlspecialchars($activity['icon']); ?>
                            </div>

                            <div class="activity-content">
                                <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                <p><?php echo $activity['description']; ?></p>
                                <time><?php echo htmlspecialchars($activity['time']); ?></time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="#" class="btn btn-soft btn-block">Xem toàn bộ nhật ký</a>
            </div>
        </article>

        <article class="quick-summary-card">
            <div>
                <span>Tình hình hôm nay</span>
                <strong>Ổn định</strong>
                <p>Không có rủi ro lớn. Ưu tiên xử lý 4 task quá hạn và kiểm tra tiến độ dự án trọng điểm.</p>
            </div>

            <a href="/creative-agency-hub/app/View/tasks/kanban.php" class="btn btn-light">Mở bảng công việc</a>
        </article>
    </aside>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>