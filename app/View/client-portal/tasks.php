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

$tasks = $tasks ?? [
    [
        'title' => 'Thiết kế giao diện đăng nhập',
        'desc' => 'Hoàn thiện UI login nội bộ và client portal theo theme đã duyệt.',
        'status' => 'Đã hoàn thành',
        'tone' => 'success',
        'deadline' => '18/10/2026',
        'owner' => 'UI Team',
    ],
    [
        'title' => 'Xây dựng Dashboard tổng quan',
        'desc' => 'Tạo dashboard quản trị gồm KPI, tiến độ dự án và hoạt động gần đây.',
        'status' => 'Đã hoàn thành',
        'tone' => 'success',
        'deadline' => '20/10/2026',
        'owner' => 'Frontend Team',
    ],
    [
        'title' => 'Hoàn thiện Kanban/Gantt',
        'desc' => 'Bổ sung bảng task kéo-thả và biểu đồ lịch trình dự án.',
        'status' => 'Đang kiểm tra',
        'tone' => 'warning',
        'deadline' => '24/10/2026',
        'owner' => 'Task Team',
    ],
    [
        'title' => 'Kiểm thử responsive',
        'desc' => 'Kiểm tra trải nghiệm trên desktop, tablet và mobile.',
        'status' => 'Đang triển khai',
        'tone' => 'primary',
        'deadline' => '27/10/2026',
        'owner' => 'QA Team',
    ],
];

$feedbacks = $feedbacks ?? [
    [
        'avatar' => 'C',
        'name' => 'Client Team',
        'message' => 'Giao diện hiện tại đã đúng hướng, ưu tiên kiểm tra kỹ màn Client Portal trên mobile.',
        'time' => '2 giờ trước',
    ],
    [
        'avatar' => 'P',
        'name' => 'Project Manager',
        'message' => 'Đội dự án đã nhận phản hồi và sẽ cập nhật trong phiên bản tiếp theo.',
        'time' => '1 giờ trước',
    ],
];

ob_start();
?>

<section class="client-hero">
    <div class="client-hero-copy">
        <span class="client-kicker">Project Detail • NexusHR Web Platform</span>
        <h1>Chi tiết tiến độ dự án.</h1>
        <p>
            Theo dõi milestone, task được chia sẻ và gửi phản hồi trực tiếp cho đội phụ trách.
            Những trao đổi nội bộ của team sẽ không hiển thị trong khu vực khách hàng.
        </p>
    </div>

    <aside class="client-hero-panel">
        <div class="client-hero-panel-row">
            <span>Trạng thái</span>
            <strong>Đang triển khai</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Tiến độ</span>
            <strong>76%</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Deadline</span>
            <strong>25/12/2026</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Project Manager</span>
            <strong>Project Manager</strong>
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
                            <strong>76%</strong>
                        </div>

                        <div class="client-progress-copy">
                            <h2>Tổng quan tiến độ</h2>
                            <p>
                                Dự án đang đi đúng kế hoạch. Các phần UI nền, dashboard và task board đã hoàn thiện.
                                Giai đoạn tiếp theo tập trung kiểm thử responsive và nối backend.
                            </p>
                        </div>
                    </div>

                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: 76%;"></div>
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
                <div class="client-milestone-list">
                    <div class="client-milestone is-done">
                        <div class="client-milestone-dot">✓</div>
                        <div class="client-milestone-body">
                            <h3>Khởi tạo UI system</h3>
                            <p>Hoàn thiện layout, component, CSS/JS nền và màn đăng nhập.</p>
                        </div>
                    </div>

                    <div class="client-milestone is-done">
                        <div class="client-milestone-dot">✓</div>
                        <div class="client-milestone-body">
                            <h3>Hoàn thiện module nội bộ</h3>
                            <p>Dashboard, HRM, Task Board, Payroll và Approvals đã được dựng giao diện.</p>
                        </div>
                    </div>

                    <div class="client-milestone">
                        <div class="client-milestone-dot">3</div>
                        <div class="client-milestone-body">
                            <h3>Client Portal & kiểm thử</h3>
                            <p>Hoàn thiện không gian khách hàng và kiểm thử responsive toàn hệ thống.</p>
                        </div>
                    </div>

                    <div class="client-milestone">
                        <div class="client-milestone-dot">4</div>
                        <div class="client-milestone-body">
                            <h3>Nối backend</h3>
                            <p>Kết nối API thật, xử lý dữ liệu động, auth và phân quyền.</p>
                        </div>
                    </div>
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
                    </select>
                </div>

                <div class="client-task-list">
                    <?php foreach ($tasks as $task): ?>
                        <article
                            class="client-task-item"
                            data-client-task-item
                            data-status="<?php echo htmlspecialchars($task['tone']); ?>"
                        >
                            <div class="client-task-info">
                                <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                                <p><?php echo htmlspecialchars($task['desc']); ?></p>

                                <div class="client-task-meta">
                                    <span class="badge badge-<?php echo htmlspecialchars($task['tone']); ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                    <span class="badge badge-info">
                                        Deadline: <?php echo htmlspecialchars($task['deadline']); ?>
                                    </span>
                                    <span class="badge badge-primary">
                                        <?php echo htmlspecialchars($task['owner']); ?>
                                    </span>
                                </div>
                            </div>

                            <button class="btn btn-light" type="button" data-client-action="mock-download">
                                Xem
                            </button>
                        </article>
                    <?php endforeach; ?>
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
                        <strong>NexusHR Web Platform</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Ngày bắt đầu</span>
                        <strong>10/10/2026</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Deadline</span>
                        <strong>25/12/2026</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Task hoàn thành</span>
                        <strong>12/18</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Phản hồi mở</span>
                        <strong>02</strong>
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
                    <?php foreach ($feedbacks as $feedback): ?>
                        <div class="client-feedback-item">
                            <div class="client-feedback-avatar">
                                <?php echo htmlspecialchars($feedback['avatar']); ?>
                            </div>

                            <div class="client-feedback-content">
                                <strong><?php echo htmlspecialchars($feedback['name']); ?></strong>
                                <p><?php echo htmlspecialchars($feedback['message']); ?></p>
                                <small><?php echo htmlspecialchars($feedback['time']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </aside>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/client.php';
?>