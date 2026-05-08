<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Bảng điều khiển | Creative Agency Hub';
$pageCss = ['dashboard.css'];
$pageJs = ['dashboard.js'];
$activeMenu = 'dashboard';
$brandName = 'Creative Agency Hub';

$currentRole = strtolower((string)($_SESSION['user_role'] ?? 'employee'));

$roleCopies = [
    'admin' => [
        'topbarTitle' => 'Admin Dashboard',
        'heading' => 'Chào buổi sáng, Quản trị viên!',
        'subtitle' => 'Theo dõi tổng quan hệ thống, tài khoản và hoạt động vận hành của Creative Agency Hub.',
        'projectSectionTitle' => 'Tổng quan dự án hệ thống',
        'projectLink' => '/creative-agency-hub/app/View/tasks/projects.php',
        'projectLinkText' => 'Xem dự án',
        'resourceTitle' => 'Tổng quan nguồn lực',
        'resourceLink' => '/creative-agency-hub/app/View/hrm/employees.php',
        'resourceLinkText' => 'Chi tiết',
        'summaryTitle' => 'Tình hình hệ thống',
        'summaryStatus' => 'Đang vận hành',
        'summaryText' => 'Hệ thống đang hoạt động ổn định. Ưu tiên theo dõi tài khoản, nhân sự và dữ liệu vận hành.',
        'summaryLink' => '/creative-agency-hub/app/View/hrm/employees.php',
        'summaryLinkText' => 'Quản lý nhân sự',
    ],
    'manager' => [
        'topbarTitle' => 'Manager Dashboard',
        'heading' => 'Chào buổi sáng, Quản lý!',
        'subtitle' => 'Theo dõi dự án, công việc, nhân sự và tiến độ vận hành trong ngày hôm nay.',
        'projectSectionTitle' => 'Tiến độ Dự án Trọng điểm',
        'projectLink' => '/creative-agency-hub/app/View/tasks/projects.php',
        'projectLinkText' => 'Xem tất cả',
        'resourceTitle' => 'Phân bổ nguồn lực',
        'resourceLink' => '/creative-agency-hub/app/View/hrm/employees.php',
        'resourceLinkText' => 'Chi tiết',
        'summaryTitle' => 'Tình hình hôm nay',
        'summaryStatus' => 'Ổn định',
        'summaryText' => 'Ưu tiên kiểm tra tiến độ dự án, task quá hạn và hoạt động của nhân sự trong nhóm.',
        'summaryLink' => '/creative-agency-hub/app/View/tasks/kanban.php',
        'summaryLinkText' => 'Mở bảng công việc',
    ],
    'employee' => [
        'topbarTitle' => 'Employee Dashboard',
        'heading' => 'Chào buổi sáng, Nhân viên!',
        'subtitle' => 'Theo dõi công việc được giao, tiến độ cá nhân, chấm công và các đầu việc cần xử lý.',
        'projectSectionTitle' => 'Công việc & Dự án của tôi',
        'projectLink' => '/creative-agency-hub/app/View/tasks/kanban.php',
        'projectLinkText' => 'Mở Kanban',
        'resourceTitle' => 'Tình trạng công việc cá nhân',
        'resourceLink' => '/creative-agency-hub/app/View/payroll/attendance.php',
        'resourceLinkText' => 'Chấm công',
        'summaryTitle' => 'Việc cần ưu tiên',
        'summaryStatus' => 'Tập trung',
        'summaryText' => 'Kiểm tra task được giao, cập nhật trạng thái đúng hạn và hoàn tất chấm công trong ngày.',
        'summaryLink' => '/creative-agency-hub/app/View/tasks/kanban.php',
        'summaryLinkText' => 'Xem task của tôi',
    ],
    'client' => [
        'topbarTitle' => 'Client Portal',
        'heading' => 'Chào mừng Khách hàng!',
        'subtitle' => 'Theo dõi tiến độ dự án và các công việc liên quan trong cổng khách hàng.',
        'projectSectionTitle' => 'Dự án của tôi',
        'projectLink' => '/creative-agency-hub/app/View/client-portal/projects.php',
        'projectLinkText' => 'Xem dự án',
        'resourceTitle' => 'Tổng quan tiến độ',
        'resourceLink' => '/creative-agency-hub/app/View/client-portal/tasks.php',
        'resourceLinkText' => 'Xem task',
        'summaryTitle' => 'Trạng thái dự án',
        'summaryStatus' => 'Đang theo dõi',
        'summaryText' => 'Bạn có thể xem tiến độ và trạng thái công việc liên quan đến dự án của mình.',
        'summaryLink' => '/creative-agency-hub/app/View/client-portal/projects.php',
        'summaryLinkText' => 'Client Portal',
    ],
];

$copy = $roleCopies[$currentRole] ?? $roleCopies['employee'];
$topbarTitle = $copy['topbarTitle'];

$stats = [
    [
        'id' => 'stat-projects',
        'title' => 'Dự án đang chạy',
        'value' => 0,
        'note' => 'Đang hoạt động',
        'icon' => '▦',
        'tone' => 'primary',
        'suffix' => '',
        'pad' => 0,
    ],
    [
        'id' => 'stat-employees',
        'title' => 'Nhân sự tham gia',
        'value' => 0,
        'note' => 'Đang hoạt động',
        'icon' => '◉',
        'tone' => 'info',
        'suffix' => '',
        'pad' => 0,
    ],
    [
        'id' => 'stat-progress',
        'title' => 'Tiến độ trung bình',
        'value' => 0,
        'note' => 'Mục tiêu tháng này',
        'icon' => '◔',
        'tone' => 'primary',
        'suffix' => '%',
        'pad' => 0,
    ],
    [
        'id' => 'stat-tasks',
        'title' => 'Task quá hạn',
        'value' => 0,
        'note' => 'Cần xử lý hôm nay',
        'icon' => '!',
        'tone' => 'danger',
        'suffix' => '',
        'pad' => 2,
    ],
];

$resources = $resources ?? [
    ['label' => 'To do', 'value' => 25],
    ['label' => 'Doing', 'value' => 45],
    ['label' => 'Review', 'value' => 20],
    ['label' => 'Done', 'value' => 65],
];

ob_start();
?>

<?php
$pageHeading = $copy['heading'];
$pageSubtitle = $copy['subtitle'];
require __DIR__ . '/../components/page-header.php';
?>

<section
    class="stat-grid"
    style="margin-bottom: 28px;"
    data-dashboard-role="<?php echo htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8'); ?>"
>
    <?php foreach ($stats as $stat): ?>
        <article class="stat-card <?php echo $stat['tone'] === 'danger' ? 'stat-card-danger' : ''; ?>">
            <div class="stat-card-icon"><?php echo htmlspecialchars($stat['icon'], ENT_QUOTES, 'UTF-8'); ?></div>

            <div class="stat-card-body">
                <span data-stat-title="<?php echo htmlspecialchars($stat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($stat['title'], ENT_QUOTES, 'UTF-8'); ?>
                </span>

                <strong>
                    <span
                        id="<?php echo htmlspecialchars($stat['id'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-count-to="<?php echo (int)$stat['value']; ?>"
                        data-pad="<?php echo (int)$stat['pad']; ?>"
                    ><?php echo str_pad((string)(int)$stat['value'], (int)$stat['pad'], '0', STR_PAD_LEFT); ?></span><?php echo htmlspecialchars($stat['suffix'], ENT_QUOTES, 'UTF-8'); ?>
                </strong>

                <small data-stat-note="<?php echo htmlspecialchars($stat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($stat['note'], ENT_QUOTES, 'UTF-8'); ?>
                </small>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid">
    <div class="dashboard-main-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2 data-dashboard-project-title><?php echo htmlspecialchars($copy['projectSectionTitle'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <a
                    href="<?php echo htmlspecialchars($copy['projectLink'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-dashboard-project-link
                >
                    <?php echo htmlspecialchars($copy['projectLinkText'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>

            <div class="card-body dashboard-project-list">
                <p style="padding: 20px; color: #6c757d;">Đang tải dữ liệu...</p>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2 data-dashboard-resource-title><?php echo htmlspecialchars($copy['resourceTitle'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <a
                    href="<?php echo htmlspecialchars($copy['resourceLink'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-dashboard-resource-link
                >
                    <?php echo htmlspecialchars($copy['resourceLinkText'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>

            <div class="card-body">
                <div class="resource-chart" data-resource-chart>
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-bar">
                            <div class="resource-bar-track">
                                <div class="resource-bar-fill" style="height: <?php echo (int)$resource['value']; ?>%;"></div>
                            </div>
                            <strong><?php echo htmlspecialchars($resource['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
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
                    <p style="padding: 10px; color: #6c757d;">Đang tải dữ liệu...</p>
                </div>

                <a href="/creative-agency-hub/app/View/tasks/activity.php" class="btn btn-soft btn-block">
                    Xem toàn bộ nhật ký
                </a>
            </div>
        </article>

        <article class="quick-summary-card">
            <div>
                <span data-dashboard-summary-title><?php echo htmlspecialchars($copy['summaryTitle'], ENT_QUOTES, 'UTF-8'); ?></span>
                <strong data-dashboard-summary-status><?php echo htmlspecialchars($copy['summaryStatus'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <p data-dashboard-summary-text><?php echo htmlspecialchars($copy['summaryText'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <a
                href="<?php echo htmlspecialchars($copy['summaryLink'], ENT_QUOTES, 'UTF-8'); ?>"
                class="btn btn-light"
                data-dashboard-summary-link
            >
                <?php echo htmlspecialchars($copy['summaryLinkText'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </article>
    </aside>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>