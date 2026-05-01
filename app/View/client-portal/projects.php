<?php
$pageTitle = 'Dự án của Khách hàng | Creative Agency Hub';
$pageCss = ['client-portal.css'];
$pageJs = ['client-portal.js'];
$brandName = 'Creative Agency Hub';
$activeClientTab = 'projects';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$currentUser = $currentUser ?? [
    'name' => 'Khách hàng',
    'role' => 'Client Portal',
    'avatar' => 'K',
];

$projects = $projects ?? [
    [
        'name' => 'Website Brand Launch',
        'description' => 'Dự án xây dựng website giới thiệu thương hiệu, landing page chiến dịch và hệ thống form liên hệ.',
        'status' => 'in_progress',
        'status_label' => 'Đang triển khai',
        'progress' => 76,
        'tasks' => 18,
        'done' => 12,
        'deadline' => '25/12/2026',
        'manager' => 'AP',
        'manager_name' => 'An Phạm',
    ],
    [
        'name' => 'Social Campaign Q4',
        'description' => 'Chiến dịch truyền thông cuối năm gồm key visual, social content và quy trình duyệt nội dung.',
        'status' => 'review',
        'status_label' => 'Đang duyệt',
        'progress' => 64,
        'tasks' => 24,
        'done' => 15,
        'deadline' => '15/01/2027',
        'manager' => 'TL',
        'manager_name' => 'Trang Lê',
    ],
    [
        'name' => 'Client Portal Upgrade',
        'description' => 'Nâng cấp trải nghiệm phản hồi, timeline và báo cáo tiến độ dành riêng cho khách hàng.',
        'status' => 'planned',
        'status_label' => 'Lên kế hoạch',
        'progress' => 36,
        'tasks' => 11,
        'done' => 4,
        'deadline' => '08/02/2027',
        'manager' => 'DM',
        'manager_name' => 'Duy Minh',
    ],
];

ob_start();
?>

<div class="client-shell">
    <header class="client-topbar">
        <a class="client-brand" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php">
            <span class="brand-mark">CA</span>
            <span>Creative Agency Hub</span>
        </a>

        <nav class="client-nav" aria-label="Client Portal">
            <a class="client-nav-link is-active" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php">
                Dự án
            </a>
            <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/tasks.php">
                Tiến độ
            </a>
            <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/support.php">
                Hỗ trợ
            </a>
        </nav>

        <div class="client-user">
            <span>Khách hàng</span>
            <strong data-client-user-name><?php echo htmlspecialchars($currentUser['name']); ?></strong>
            <span class="client-avatar" data-client-user-avatar><?php echo htmlspecialchars($currentUser['avatar']); ?></span>
        </div>
    </header>

    <main class="client-content">
        <section class="client-welcome-card">
            <div>
                <span class="client-kicker">Client Portal • Creative Agency Hub</span>
                <h1>Dự án được chia sẻ</h1>
                <p>
                    Theo dõi tiến độ, deadline và các đầu việc công khai dành riêng cho tài khoản khách hàng.
                </p>
            </div>

            <div class="client-summary-panel">
                <div>
                    <span>Dự án đang mở</span>
                    <strong><?php echo count($projects); ?></strong>
                </div>
                <div>
                    <span>Tiến độ trung bình</span>
                    <strong>68%</strong>
                </div>
                <div>
                    <span>Phản hồi chờ xử lý</span>
                    <strong>02</strong>
                </div>
            </div>
        </section>

        <section class="client-toolbar">
            <div>
                <h2>Dự án của bạn</h2>
                <p>Danh sách các dự án mà tài khoản khách hàng có quyền theo dõi.</p>
            </div>

            <div class="client-filters">
                <label class="client-search">
                    <span>⌕</span>
                    <input type="search" placeholder="Tìm dự án..." data-client-search>
                </label>

                <select data-client-status-filter>
                    <option value="all">Tất cả trạng thái</option>
                    <option value="in_progress">Đang triển khai</option>
                    <option value="review">Đang duyệt</option>
                    <option value="planned">Lên kế hoạch</option>
                </select>
            </div>
        </section>

        <section class="client-project-grid" data-client-project-grid>
            <?php foreach ($projects as $project): ?>
                <article
                    class="client-project-card"
                    data-client-project-card
                    data-project-status="<?php echo htmlspecialchars($project['status']); ?>"
                    data-project-name="<?php echo htmlspecialchars(mb_strtolower($project['name'])); ?>"
                >
                    <div class="client-project-head">
                        <div>
                            <h3><?php echo htmlspecialchars($project['name']); ?></h3>
                            <p><?php echo htmlspecialchars($project['description']); ?></p>
                        </div>

                        <span class="client-status-pill status-<?php echo htmlspecialchars($project['status']); ?>">
                            <?php echo htmlspecialchars($project['status_label']); ?>
                        </span>
                    </div>

                    <div class="client-progress-block">
                        <div class="client-progress-row">
                            <span>Tiến độ</span>
                            <strong><?php echo (int) $project['progress']; ?>%</strong>
                        </div>
                        <div class="client-progress-track">
                            <div class="client-progress-fill" style="width: <?php echo (int) $project['progress']; ?>%;"></div>
                        </div>
                    </div>

                    <div class="client-project-meta">
                        <span><?php echo (int) $project['done']; ?>/<?php echo (int) $project['tasks']; ?> task</span>
                        <span>Deadline: <?php echo htmlspecialchars($project['deadline']); ?></span>
                    </div>

                    <div class="client-project-footer">
                        <div class="client-manager">
                            <span><?php echo htmlspecialchars($project['manager']); ?></span>
                            <div>
                                <small>Phụ trách</small>
                                <strong><?php echo htmlspecialchars($project['manager_name']); ?></strong>
                            </div>
                        </div>

                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/tasks.php">
                            Xem tiến độ
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/client.php';
?>