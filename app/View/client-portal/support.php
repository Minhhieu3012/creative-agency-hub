<?php
$pageTitle = 'Hỗ trợ Khách hàng | Creative Agency Hub';
$pageCss = ['client-portal.css'];
$pageJs = ['client-portal.js'];
$brandName = 'Creative Agency Hub';
$activeClientTab = 'support';

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

$supportTickets = $supportTickets ?? [
    [
        'title' => 'Yêu cầu cập nhật deadline',
        'desc' => 'Khách hàng muốn điều chỉnh thời hạn review UI Client Portal.',
        'status' => 'Đang xử lý',
        'tone' => 'review',
        'time' => '2 giờ trước',
    ],
    [
        'title' => 'Bổ sung tài liệu nghiệm thu',
        'desc' => 'Đội dự án đã nhận yêu cầu bổ sung checklist nghiệm thu module Task.',
        'status' => 'Đã tiếp nhận',
        'tone' => 'in_progress',
        'time' => 'Hôm qua',
    ],
    [
        'title' => 'Hỏi về phạm vi bảo trì',
        'desc' => 'Câu hỏi liên quan phạm vi hỗ trợ sau khi bàn giao phiên bản đầu tiên.',
        'status' => 'Đã phản hồi',
        'tone' => 'planned',
        'time' => '3 ngày trước',
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
            <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/tasks.php">
                Tiến độ
            </a>
            <a class="client-nav-link is-active" href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/support.php">
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
        <section class="client-welcome-card is-compact">
            <div>
                <span class="client-kicker">Support Center • Creative Agency Hub</span>
                <h1>Trung tâm hỗ trợ</h1>
                <p>
                    Gửi yêu cầu thay đổi, hỏi về tiến độ, báo lỗi hoặc trao đổi nhanh với đội dự án.
                    Mọi phản hồi sẽ được Project Manager xử lý theo đúng luồng.
                </p>
            </div>

            <div class="client-summary-panel">
                <div>
                    <span>Yêu cầu đang mở</span>
                    <strong>02</strong>
                </div>
                <div>
                    <span>Phản hồi trung bình</span>
                    <strong>2h</strong>
                </div>
                <div>
                    <span>Trạng thái hỗ trợ</span>
                    <strong>Online</strong>
                </div>
            </div>
        </section>

        <section class="client-two-column">
            <article class="client-card">
                <div class="client-card-head">
                    <div>
                        <h2>Gửi yêu cầu hỗ trợ mới</h2>
                        <p>
                            Điền nội dung bên dưới để gửi yêu cầu đến đội dự án.
                            Backend sẽ được nối thật ở phase tiếp theo.
                        </p>
                    </div>

                    <span class="client-status-pill status-in_progress">Online</span>
                </div>

                <form class="client-feedback-form" data-ui-form data-client-feedback-form>
                    <label>
                        <span>Loại yêu cầu</span>
                        <select name="support_type" required>
                            <option value="">-- Chọn loại yêu cầu --</option>
                            <option value="scope">Thay đổi phạm vi</option>
                            <option value="bug">Báo lỗi giao diện</option>
                            <option value="deadline">Cập nhật deadline</option>
                            <option value="question">Câu hỏi chung</option>
                        </select>
                    </label>

                    <label>
                        <span>Mức độ ưu tiên</span>
                        <select name="support_priority" required>
                            <option value="normal">Bình thường</option>
                            <option value="high">Cao</option>
                            <option value="urgent">Khẩn cấp</option>
                        </select>
                    </label>

                    <label>
                        <span>Tiêu đề</span>
                        <input
                            type="text"
                            name="support_title"
                            placeholder="VD: Cần cập nhật deadline milestone UI"
                            required
                        >
                    </label>

                    <label>
                        <span>Nội dung yêu cầu</span>
                        <textarea
                            name="feedback"
                            rows="6"
                            placeholder="Nhập chi tiết yêu cầu hỗ trợ..."
                            required
                        ></textarea>
                    </label>

                    <label>
                        <span>Tài liệu đính kèm</span>
                        <input type="file" name="support_attachment">
                    </label>

                    <button class="btn btn-primary" type="submit">
                        Gửi yêu cầu hỗ trợ
                    </button>
                </form>
            </article>

            <aside class="client-card">
                <div class="client-card-head">
                    <div>
                        <h2>Yêu cầu gần đây</h2>
                        <p>Theo dõi trạng thái các yêu cầu đã gửi.</p>
                    </div>
                </div>

                <div class="client-task-list">
                    <?php foreach ($supportTickets as $ticket): ?>
                        <div class="client-task-item">
                            <div class="client-task-main">
                                <h3><?php echo htmlspecialchars($ticket['title']); ?></h3>
                                <p><?php echo htmlspecialchars($ticket['desc']); ?></p>

                                <div class="client-project-meta" style="margin-top: 12px;">
                                    <span class="client-status-pill status-<?php echo htmlspecialchars($ticket['tone']); ?>">
                                        <?php echo htmlspecialchars($ticket['status']); ?>
                                    </span>
                                    <span><?php echo htmlspecialchars($ticket['time']); ?></span>
                                </div>
                            </div>

                            <div class="client-task-meta">
                                <span>Phụ trách</span>
                                <strong>Project Manager</strong>
                                <small>Support</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="client-note">
                    <strong>Gợi ý gửi yêu cầu</strong>
                    <p>
                        Ghi rõ mục tiêu, lý do cần thay đổi và đính kèm tài liệu nếu có.
                        Điều này giúp đội dự án xử lý nhanh hơn.
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