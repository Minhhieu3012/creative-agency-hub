<?php
$pageTitle = 'Dự án của Khách hàng | Creative Agency Hub';
$pageCss = ['client-portal.css'];
$pageJs = ['client-portal.js'];
$brandName = 'Creative Agency Hub';

$currentUser = $currentUser ?? [
    'name' => 'Khách hàng',
    'role' => 'Client Portal',
    'avatar' => null,
];

$projects = $projects ?? [
    [
        'name' => 'NexusHR Web Platform',
        'description' => 'Nền tảng quản lý nhân sự, công việc và cổng thông tin khách hàng cho doanh nghiệp.',
        'status' => 'in_progress',
        'status_label' => 'Đang triển khai',
        'progress' => 76,
        'tasks' => 18,
        'done' => 12,
        'deadline' => '25/12/2026',
        'manager' => 'PM',
        'manager_name' => 'Project Manager',
    ],
    [
        'name' => 'Brand Campaign Q4',
        'description' => 'Chiến dịch sáng tạo cuối năm gồm key visual, social content và approval workflow.',
        'status' => 'review',
        'status_label' => 'Đang duyệt',
        'progress' => 64,
        'tasks' => 24,
        'done' => 15,
        'deadline' => '15/01/2027',
        'manager' => 'CM',
        'manager_name' => 'Campaign Manager',
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
        'manager' => 'UX',
        'manager_name' => 'UX Lead',
    ],
];

ob_start();
?>

<section class="client-hero">
    <div class="client-hero-copy">
        <span class="client-kicker">Client Portal • Creative Agency Hub</span>
        <h1>Theo dõi dự án của bạn trong một không gian riêng.</h1>
        <p>
            Xem tiến độ, deadline, đầu việc được chia sẻ và gửi phản hồi trực tiếp
            đến đội ngũ phụ trách mà không can thiệp vào luồng trao đổi nội bộ.
        </p>
    </div>

    <aside class="client-hero-panel">
        <div class="client-hero-panel-row">
            <span>Dự án đang mở</span>
            <strong><?php echo count($projects); ?></strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Tiến độ trung bình</span>
            <strong>68%</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Phản hồi chờ xử lý</span>
            <strong>02</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Lần cập nhật gần nhất</span>
            <strong>Hôm nay</strong>
        </div>
    </aside>
</section>

<section class="client-section">
    <div class="client-section-header">
        <div>
            <h2>Dự án được chia sẻ</h2>
            <p>Danh sách các dự án mà tài khoản khách hàng của bạn có quyền theo dõi.</p>
        </div>

        <div class="client-filter-row">
            <div class="input-with-icon">
                <span class="input-icon">⌕</span>
                <input
                    class="form-control"
                    type="search"
                    placeholder="Tìm dự án..."
                    data-client-search="[data-client-project-card]"
                >
            </div>

            <select class="form-select" data-client-filter="[data-client-project-card]" data-filter-key="status">
                <option value="">Tất cả trạng thái</option>
                <option value="in_progress">Đang triển khai</option>
                <option value="review">Đang duyệt</option>
                <option value="planned">Lên kế hoạch</option>
            </select>
        </div>
    </div>

    <div class="client-project-grid">
        <?php foreach ($projects as $project): ?>
            <?php
            $badgeTone = $project['status'] === 'in_progress'
                ? 'primary'
                : ($project['status'] === 'review' ? 'warning' : 'info');
            ?>
            <article
                class="client-project-card"
                data-client-project-card
                data-status="<?php echo htmlspecialchars($project['status']); ?>"
            >
                <div class="client-project-card-header">
                    <div class="client-project-card-title">
                        <h2><?php echo htmlspecialchars($project['name']); ?></h2>
                        <span class="badge badge-<?php echo htmlspecialchars($badgeTone); ?>">
                            <?php echo htmlspecialchars($project['status_label']); ?>
                        </span>
                    </div>

                    <p><?php echo htmlspecialchars($project['description']); ?></p>
                </div>

                <div class="client-project-meta">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: <?php echo (int) $project['progress']; ?>%;"></div>
                    </div>

                    <div class="project-progress-meta">
                        <span><?php echo (int) $project['progress']; ?>% hoàn thành</span>
                        <span>Deadline: <?php echo htmlspecialchars($project['deadline']); ?></span>
                    </div>

                    <div class="client-project-stats">
                        <div class="client-project-stat">
                            <strong><?php echo (int) $project['tasks']; ?></strong>
                            <span>Tasks</span>
                        </div>

                        <div class="client-project-stat">
                            <strong><?php echo (int) $project['done']; ?></strong>
                            <span>Done</span>
                        </div>

                        <div class="client-project-stat">
                            <strong><?php echo (int) $project['progress']; ?>%</strong>
                            <span>Progress</span>
                        </div>
                    </div>
                </div>

                <div class="client-project-footer">
                    <div class="client-manager">
                        <span class="client-manager-avatar"><?php echo htmlspecialchars($project['manager']); ?></span>
                        <span><?php echo htmlspecialchars($project['manager_name']); ?></span>
                    </div>

                    <a class="btn btn-primary" href="/creative-agency-hub/app/View/client-portal/tasks.php">
                        Xem chi tiết
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="client-section">
    <div class="client-section-header">
        <div>
            <h2>Cập nhật gần đây</h2>
            <p>Các thay đổi mới nhất được chia sẻ từ đội dự án.</p>
        </div>
    </div>

    <div class="client-detail-layout">
        <article class="card">
            <div class="card-body">
                <div class="client-milestone-list">
                    <div class="client-milestone is-done">
                        <div class="client-milestone-dot">✓</div>
                        <div class="client-milestone-body">
                            <h3>Hoàn thiện giao diện đăng nhập</h3>
                            <p>Đã cập nhật UI login nội bộ và client portal theo theme Creative Agency Hub.</p>
                        </div>
                    </div>

                    <div class="client-milestone is-done">
                        <div class="client-milestone-dot">✓</div>
                        <div class="client-milestone-body">
                            <h3>Hoàn thiện Dashboard nội bộ</h3>
                            <p>Đội ngũ đã bổ sung dashboard tổng quan, HRM, task board và payroll screen.</p>
                        </div>
                    </div>

                    <div class="client-milestone">
                        <div class="client-milestone-dot">3</div>
                        <div class="client-milestone-body">
                            <h3>Đang kiểm tra responsive</h3>
                            <p>Các màn hình sẽ được kiểm thử trên desktop, tablet và mobile.</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <aside class="client-contact-card">
            <h2>Cần hỗ trợ nhanh?</h2>
            <p>Gửi yêu cầu cho đội dự án nếu bạn cần thay đổi scope, thêm phản hồi hoặc cập nhật deadline.</p>
            <button class="btn btn-light" type="button" data-client-action="mock-support">
                Gửi yêu cầu hỗ trợ
            </button>
        </aside>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/client.php';
?>