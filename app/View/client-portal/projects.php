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

/*
 * Client Portal lấy dữ liệu thật từ API:
 * GET /creative-agency-hub/public/api/client/projects
 *
 * Không dùng dữ liệu giả mặc định nữa.
 * Nếu controller có truyền sẵn $projects/$updates thì vẫn render được.
 * Nếu không có, public/assets/js/client-portal.js sẽ fetch API và đổ dữ liệu vào các data-* bên dưới.
 */
$projects = $projects ?? [];
$updates = $updates ?? [];

ob_start();
?>

<section class="client-hero" data-client-projects-page>
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
            <strong data-client-summary="open_projects">
                <?php echo count($projects); ?>
            </strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Tiến độ trung bình</span>
            <strong data-client-summary="avg_progress">
                <?php
                if (!empty($projects)) {
                    $totalProgress = array_sum(array_map(static function ($project) {
                        return (int)($project['progress'] ?? 0);
                    }, $projects));

                    echo round($totalProgress / count($projects)) . '%';
                } else {
                    echo '--';
                }
                ?>
            </strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Phản hồi chờ xử lý</span>
            <strong data-client-summary="pending_feedback">--</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Lần cập nhật gần nhất</span>
            <strong data-client-summary="last_update">--</strong>
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
                <option value="completed">Hoàn thành</option>
                <option value="archived">Đã lưu trữ</option>
            </select>
        </div>
    </div>

    <div class="client-project-grid" data-client-project-grid>
        <?php if (!empty($projects)): ?>
            <?php foreach ($projects as $project): ?>
                <?php
                $projectStatus = $project['status'] ?? 'in_progress';
                $projectStatusLabel = $project['status_label'] ?? 'Đang triển khai';

                $badgeTone = $projectStatus === 'in_progress'
                    ? 'primary'
                    : ($projectStatus === 'review'
                        ? 'warning'
                        : ($projectStatus === 'completed' ? 'success' : 'info'));

                $projectId = $project['id'] ?? null;
                $detailUrl = '/creative-agency-hub/app/View/client-portal/tasks.php';

                if (!empty($projectId)) {
                    $detailUrl .= '?project_id=' . urlencode((string)$projectId);
                }
                ?>
                <article
                    class="client-project-card"
                    data-client-project-card
                    data-status="<?php echo htmlspecialchars($projectStatus); ?>"
                >
                    <div class="client-project-card-header">
                        <div class="client-project-card-title">
                            <h2><?php echo htmlspecialchars($project['name'] ?? 'Dự án chưa đặt tên'); ?></h2>
                            <span class="badge badge-<?php echo htmlspecialchars($badgeTone); ?>">
                                <?php echo htmlspecialchars($projectStatusLabel); ?>
                            </span>
                        </div>

                        <p><?php echo htmlspecialchars($project['description'] ?? 'Chưa có mô tả dự án.'); ?></p>
                    </div>

                    <div class="client-project-meta">
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: <?php echo (int)($project['progress'] ?? 0); ?>%;"></div>
                        </div>

                        <div class="project-progress-meta">
                            <span><?php echo (int)($project['progress'] ?? 0); ?>% hoàn thành</span>
                            <span>Deadline: <?php echo htmlspecialchars($project['deadline'] ?? 'Chưa cập nhật'); ?></span>
                        </div>

                        <div class="client-project-stats">
                            <div class="client-project-stat">
                                <strong><?php echo (int)($project['tasks'] ?? 0); ?></strong>
                                <span>Tasks</span>
                            </div>

                            <div class="client-project-stat">
                                <strong><?php echo (int)($project['done'] ?? 0); ?></strong>
                                <span>Done</span>
                            </div>

                            <div class="client-project-stat">
                                <strong><?php echo (int)($project['progress'] ?? 0); ?>%</strong>
                                <span>Progress</span>
                            </div>
                        </div>
                    </div>

                    <div class="client-project-footer">
                        <div class="client-manager">
                            <span class="client-manager-avatar">
                                <?php echo htmlspecialchars($project['manager'] ?? 'CA'); ?>
                            </span>
                            <span><?php echo htmlspecialchars($project['manager_name'] ?? 'Chưa gán quản lý'); ?></span>
                        </div>

                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($detailUrl); ?>">
                            Xem chi tiết
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <article class="card" style="padding: 24px; text-align: center; grid-column: 1 / -1;" data-client-empty-projects>
                Đang tải dữ liệu dự án được chia sẻ...
            </article>
        <?php endif; ?>
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
                <div class="client-milestone-list" data-client-updates-list>
                    <?php if (!empty($updates)): ?>
                        <?php foreach ($updates as $update): ?>
                            <div class="client-milestone <?php echo !empty($update['is_done']) ? 'is-done' : ''; ?>">
                                <div class="client-milestone-dot">
                                    <?php echo !empty($update['is_done']) ? '✓' : '•'; ?>
                                </div>
                                <div class="client-milestone-body">
                                    <h3><?php echo htmlspecialchars($update['title'] ?? 'Cập nhật dự án'); ?></h3>
                                    <p><?php echo htmlspecialchars($update['description'] ?? 'Có cập nhật mới từ đội dự án.'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="client-milestone">
                            <div class="client-milestone-dot">…</div>
                            <div class="client-milestone-body">
                                <h3>Đang tải cập nhật</h3>
                                <p>Hệ thống đang lấy dữ liệu mới nhất từ các task thuộc dự án được chia sẻ.</p>
                            </div>
                        </div>
                    <?php endif; ?>
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