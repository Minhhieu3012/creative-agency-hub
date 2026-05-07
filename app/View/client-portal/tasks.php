<?php
$pageTitle = 'Chi tiết tiến độ | Creative Agency Hub';
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
 * GET /creative-agency-hub/public/api/client/projects/:id
 *
 * Không dùng dữ liệu giả mặc định nữa.
 * Nếu controller có truyền sẵn $project/$tasks/$feedbacks thì vẫn render được.
 * Nếu không có, public/assets/js/client-portal.js sẽ fetch API và đổ dữ liệu vào các data-* bên dưới.
 */
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$project = $project ?? [];
$tasks = $tasks ?? [];
$feedbacks = $feedbacks ?? [];

ob_start();
?>

<section
    class="client-hero"
    data-client-project-detail-page
    data-project-id="<?php echo (int)$projectId; ?>"
>
    <div class="client-hero-copy">
        <span class="client-kicker" data-client-detail="kicker">
            Project Detail • <?php echo htmlspecialchars($project['name'] ?? 'Đang tải dự án'); ?>
        </span>
        <h1 data-client-detail="title">
            <?php echo htmlspecialchars($project['name'] ?? 'Chi tiết tiến độ dự án.'); ?>
        </h1>
        <p data-client-detail="description">
            <?php if (!empty($project['description'])): ?>
                <?php echo htmlspecialchars($project['description']); ?>
            <?php else: ?>
                Theo dõi milestone, task được chia sẻ và gửi phản hồi trực tiếp cho đội phụ trách.
                Những trao đổi nội bộ của team sẽ không hiển thị trong khu vực khách hàng.
            <?php endif; ?>
        </p>
    </div>

    <aside class="client-hero-panel">
        <div class="client-hero-panel-row">
            <span>Trạng thái</span>
            <strong data-client-detail="status_label">
                <?php echo htmlspecialchars($project['status_label'] ?? '--'); ?>
            </strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Tiến độ</span>
            <strong data-client-detail="progress">
                <?php echo isset($project['progress']) ? (int)$project['progress'] . '%' : '--'; ?>
            </strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Deadline</span>
            <strong data-client-detail="deadline">
                <?php echo htmlspecialchars($project['deadline'] ?? '--'); ?>
            </strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Project Manager</span>
            <strong data-client-detail="manager_name">
                <?php echo htmlspecialchars($project['manager_name'] ?? '--'); ?>
            </strong>
        </div>
    </aside>
</section>

<section class="client-detail-layout">
    <main class="client-detail-main">
        <article class="card">
            <div class="card-body">
                <div class="client-progress-overview">
                    <div class="client-progress-big">
                        <div class="client-progress-circle">
                            <strong data-client-detail="progress_circle">
                                <?php echo isset($project['progress']) ? (int)$project['progress'] . '%' : '--'; ?>
                            </strong>
                        </div>

                        <div class="client-progress-copy">
                            <h2>Tổng quan tiến độ</h2>
                            <p data-client-detail="progress_summary">
                                <?php if (!empty($project)): ?>
                                    Dự án hiện có <?php echo (int)($project['tasks'] ?? count($tasks)); ?> task,
                                    trong đó <?php echo (int)($project['done'] ?? 0); ?> task đã hoàn thành.
                                <?php else: ?>
                                    Đang tải dữ liệu thật từ dự án được chia sẻ cho tài khoản khách hàng.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="progress-line">
                        <div
                            class="progress-line-fill"
                            data-client-detail="progress_bar"
                            style="width: <?php echo (int)($project['progress'] ?? 0); ?>%;"
                        ></div>
                    </div>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Milestone dự án</h2>
                    <p class="section-subtitle">Các mốc triển khai chính được chia sẻ với khách hàng.</p>
                </div>
            </div>

            <div class="card-body">
                <div class="client-milestone-list" data-client-milestone-list>
                    <?php if (!empty($tasks)): ?>
                        <?php
                        $todoCount = 0;
                        $doingCount = 0;
                        $reviewCount = 0;
                        $doneCount = 0;

                        foreach ($tasks as $task) {
                            $status = strtolower((string)($task['status'] ?? ''));

                            if ($status === 'done' || $status === 'đã hoàn thành') {
                                $doneCount++;
                            } elseif ($status === 'review' || $status === 'đang kiểm tra') {
                                $reviewCount++;
                            } elseif ($status === 'doing' || $status === 'đang triển khai') {
                                $doingCount++;
                            } else {
                                $todoCount++;
                            }
                        }
                        ?>

                        <div class="client-milestone <?php echo $todoCount === 0 ? 'is-done' : ''; ?>">
                            <div class="client-milestone-dot"><?php echo $todoCount === 0 ? '✓' : (int)$todoCount; ?></div>
                            <div class="client-milestone-body">
                                <h3>Cần làm</h3>
                                <p><?php echo (int)$todoCount; ?> task đang chờ triển khai.</p>
                            </div>
                        </div>

                        <div class="client-milestone <?php echo $doingCount === 0 && ($reviewCount + $doneCount) > 0 ? 'is-done' : ''; ?>">
                            <div class="client-milestone-dot"><?php echo $doingCount === 0 && ($reviewCount + $doneCount) > 0 ? '✓' : (int)$doingCount; ?></div>
                            <div class="client-milestone-body">
                                <h3>Đang triển khai</h3>
                                <p><?php echo (int)$doingCount; ?> task đang được đội dự án xử lý.</p>
                            </div>
                        </div>

                        <div class="client-milestone <?php echo $reviewCount === 0 && $doneCount > 0 ? 'is-done' : ''; ?>">
                            <div class="client-milestone-dot"><?php echo $reviewCount === 0 && $doneCount > 0 ? '✓' : (int)$reviewCount; ?></div>
                            <div class="client-milestone-body">
                                <h3>Đang kiểm tra</h3>
                                <p><?php echo (int)$reviewCount; ?> task đang ở bước kiểm tra.</p>
                            </div>
                        </div>

                        <div class="client-milestone <?php echo $doneCount > 0 ? 'is-done' : ''; ?>">
                            <div class="client-milestone-dot"><?php echo $doneCount > 0 ? '✓' : '0'; ?></div>
                            <div class="client-milestone-body">
                                <h3>Hoàn thành</h3>
                                <p><?php echo (int)$doneCount; ?> task đã hoàn tất.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="client-milestone">
                            <div class="client-milestone-dot">…</div>
                            <div class="client-milestone-body">
                                <h3>Đang tải milestone</h3>
                                <p>Hệ thống đang tổng hợp số lượng task theo từng trạng thái thật.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Task được chia sẻ</h2>
                    <p class="section-subtitle">Danh sách task khách hàng được phép theo dõi.</p>
                </div>

                <button class="btn btn-soft" type="button" data-client-action="mock-download">
                    ⇩ Tải báo cáo
                </button>
            </div>

            <div class="card-body">
                <div class="client-filter-row" style="margin-bottom: 18px;">
                    <div class="input-with-icon">
                        <span class="input-icon">⌕</span>
                        <input
                            class="form-control"
                            type="search"
                            placeholder="Tìm task..."
                            data-client-search="[data-client-task-item]"
                        >
                    </div>

                    <select class="form-select" data-client-filter="[data-client-task-item]" data-filter-key="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="success">Đã hoàn thành</option>
                        <option value="warning">Đang kiểm tra</option>
                        <option value="primary">Đang triển khai</option>
                        <option value="info">Cần làm</option>
                    </select>
                </div>

                <div class="client-task-list" data-client-task-list>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <?php
                            $taskTone = $task['tone'] ?? 'info';
                            $taskStatus = $task['status_label'] ?? ($task['status'] ?? 'Cần làm');
                            ?>
                            <article
                                class="client-task-item"
                                data-client-task-item
                                data-status="<?php echo htmlspecialchars($taskTone); ?>"
                            >
                                <div class="client-task-info">
                                    <h3><?php echo htmlspecialchars($task['title'] ?? 'Task chưa đặt tên'); ?></h3>
                                    <p><?php echo htmlspecialchars($task['desc'] ?? ($task['description'] ?? 'Không có mô tả')); ?></p>

                                    <div class="client-task-meta">
                                        <span class="badge badge-<?php echo htmlspecialchars($taskTone); ?>">
                                            <?php echo htmlspecialchars($taskStatus); ?>
                                        </span>
                                        <span class="badge badge-info">
                                            Deadline: <?php echo htmlspecialchars($task['deadline'] ?? 'Chưa cập nhật'); ?>
                                        </span>
                                        <span class="badge badge-primary">
                                            <?php echo htmlspecialchars($task['owner'] ?? ($task['assignee_name'] ?? 'Chưa gán')); ?>
                                        </span>
                                    </div>
                                </div>

                                <button class="btn btn-light" type="button" data-client-action="mock-download">
                                    Xem
                                </button>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="client-task-item" data-client-empty-tasks>
                            <div class="client-task-info">
                                <h3>Đang tải task...</h3>
                                <p>Hệ thống đang lấy dữ liệu thật từ bảng tasks.</p>
                            </div>
                        </article>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </main>

    <aside class="client-detail-side">
        <article class="card">
            <div class="card-header">
                <h2 class="section-title">Thông tin dự án</h2>
                <p class="section-subtitle">Tóm tắt phạm vi và trạng thái hiện tại.</p>
            </div>

            <div class="card-body">
                <div class="client-side-summary">
                    <div class="client-summary-row">
                        <span>Tên dự án</span>
                        <strong data-client-detail="side_name">
                            <?php echo htmlspecialchars($project['name'] ?? '--'); ?>
                        </strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Ngày bắt đầu</span>
                        <strong data-client-detail="created_at">
                            <?php echo htmlspecialchars($project['created_at'] ?? '--'); ?>
                        </strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Deadline</span>
                        <strong data-client-detail="side_deadline">
                            <?php echo htmlspecialchars($project['deadline'] ?? '--'); ?>
                        </strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Task hoàn thành</span>
                        <strong data-client-detail="done_ratio">
                            <?php echo (int)($project['done'] ?? 0); ?>/<?php echo (int)($project['tasks'] ?? count($tasks)); ?>
                        </strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Phản hồi mở</span>
                        <strong data-client-detail="open_feedback">
                            <?php echo count($feedbacks); ?>
                        </strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="client-contact-card">
            <h2>Gửi yêu cầu thay đổi</h2>
            <p>Nếu bạn muốn bổ sung scope, thay đổi ưu tiên hoặc yêu cầu báo cáo riêng, hãy gửi yêu cầu cho đội dự án.</p>
            <button class="btn btn-light" type="button" data-client-action="mock-support">
                Gửi yêu cầu
            </button>
        </article>

        <article class="card">
            <div class="card-header">
                <h2 class="section-title">Phản hồi</h2>
                <p class="section-subtitle">Khu vực trao đổi dành riêng cho khách hàng.</p>
            </div>

            <div class="card-body">
                <form class="client-feedback-box" data-client-feedback-form>
                    <div class="form-group">
                        <label class="form-label" for="client_feedback">Nội dung phản hồi</label>
                        <textarea
                            id="client_feedback"
                            class="form-textarea"
                            name="feedback"
                            placeholder="Nhập phản hồi của bạn..."
                        ></textarea>
                    </div>

                    <button class="btn btn-primary btn-block" type="submit">
                        Gửi phản hồi
                    </button>
                </form>

                <div class="client-feedback-list" data-client-feedback-list style="margin-top: 22px;">
                    <?php if (!empty($feedbacks)): ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <div class="client-feedback-item">
                                <div class="client-feedback-avatar">
                                    <?php echo htmlspecialchars($feedback['avatar'] ?? 'C'); ?>
                                </div>

                                <div class="client-feedback-content">
                                    <strong><?php echo htmlspecialchars($feedback['name'] ?? 'Khách hàng'); ?></strong>
                                    <p><?php echo htmlspecialchars($feedback['message'] ?? ''); ?></p>
                                    <small><?php echo htmlspecialchars($feedback['time'] ?? ''); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="client-feedback-item" data-client-empty-feedbacks>
                            <div class="client-feedback-avatar">…</div>

                            <div class="client-feedback-content">
                                <strong>Đang tải phản hồi</strong>
                                <p>Hệ thống đang lấy bình luận liên quan đến task của dự án.</p>
                                <small>Dữ liệu thật từ task_comments</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </aside>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/client.php';
?>