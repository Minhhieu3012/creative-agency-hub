<?php
$pageTitle = 'Tiến độ dự án | Creative Agency Hub';
$pageCss = ['client-portal.css'];
$pageJs = ['client-portal.js'];
$brandName = 'Creative Agency Hub';
$activeClientTab = 'tasks';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$tasks = $tasks ?? [
    [
        'title' => 'Hoàn thiện Homepage',
        'desc' => 'Thiết kế lại hero, CTA và khu vực giới thiệu dịch vụ.',
        'status' => 'Đang thực hiện',
        'progress' => 72,
        'owner' => 'An Phạm',
        'deadline' => '12/12/2026',
    ],
    [
        'title' => 'Duyệt Key Visual',
        'desc' => 'Client review key visual và gửi phản hồi phiên bản 02.',
        'status' => 'Chờ phản hồi',
        'progress' => 55,
        'owner' => 'Trang Lê',
        'deadline' => '15/12/2026',
    ],
    [
        'title' => 'Bàn giao tài liệu',
        'desc' => 'Tổng hợp file thiết kế, nội dung và hướng dẫn vận hành.',
        'status' => 'Lên kế hoạch',
        'progress' => 28,
        'owner' => 'Duy Minh',
        'deadline' => '22/12/2026',
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
            <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php">
                Dự án
            </a>
            <a class="client-nav-link is-active" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/tasks.php">
                Tiến độ
            </a>
            <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/support.php">
                Hỗ trợ
            </a>
        </nav>

        <div class="client-user">
            <span>Khách hàng</span>
            <strong data-client-user-name>Khách hàng</strong>
            <span class="client-avatar" data-client-user-avatar>K</span>
        </div>
    </header>

    <main class="client-content">
        <section class="client-welcome-card is-compact">
            <div>
                <span class="client-kicker">Project Detail • Client View</span>
                <h1>Tiến độ dự án</h1>
                <p>
                    Theo dõi các đầu việc được chia sẻ công khai. Trao đổi nội bộ của team sẽ không hiển thị tại đây.
                </p>
            </div>

            <div class="client-summary-panel">
                <div>
                    <span>Trạng thái</span>
                    <strong>Đang triển khai</strong>
                </div>
                <div>
                    <span>Tiến độ</span>
                    <strong>68%</strong>
                </div>
                <div>
                    <span>Deadline</span>
                    <strong>25/12/2026</strong>
                </div>
            </div>
        </section>

        <section class="client-two-column">
            <article class="client-card">
                <div class="client-card-head">
                    <div>
                        <h2>Timeline đầu việc</h2>
                        <p>Các task được chia sẻ với khách hàng.</p>
                    </div>
                    <span class="client-status-pill status-in_progress">Đang triển khai</span>
                </div>

                <div class="client-task-list">
                    <?php foreach ($tasks as $task): ?>
                        <div class="client-task-item">
                            <div class="client-task-main">
                                <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                                <p><?php echo htmlspecialchars($task['desc']); ?></p>

                                <div class="client-progress-block">
                                    <div class="client-progress-row">
                                        <span><?php echo htmlspecialchars($task['status']); ?></span>
                                        <strong><?php echo (int) $task['progress']; ?>%</strong>
                                    </div>
                                    <div class="client-progress-track">
                                        <div class="client-progress-fill" style="width: <?php echo (int) $task['progress']; ?>%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="client-task-meta">
                                <span>Phụ trách</span>
                                <strong><?php echo htmlspecialchars($task['owner']); ?></strong>
                                <small><?php echo htmlspecialchars($task['deadline']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <aside class="client-card">
                <div class="client-card-head">
                    <div>
                        <h2>Gửi phản hồi</h2>
                        <p>Feedback sẽ gửi tới đội phụ trách dự án.</p>
                    </div>
                </div>

                <form class="client-feedback-form" data-ui-form data-client-feedback-form>
                    <label>
                        <span>Chủ đề</span>
                        <input type="text" name="subject" placeholder="Ví dụ: Góp ý phần Homepage" required>
                    </label>

                    <label>
                        <span>Nội dung phản hồi</span>
                        <textarea name="message" rows="6" placeholder="Nhập phản hồi của bạn..." required></textarea>
                    </label>

                    <button class="btn btn-primary" type="submit">
                        Gửi phản hồi
                    </button>
                </form>

                <div class="client-note">
                    <strong>Minh bạch nhưng bảo mật</strong>
                    <p>
                        Client chỉ xem các cập nhật được chia sẻ, không thấy bình luận nội bộ hoặc task riêng của team.
                    </p>
                </div>
            </aside>
        </section>
    </main>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/client.php';
?>