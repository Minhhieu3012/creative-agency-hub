<?php
$pageTitle = 'Hỗ trợ Khách hàng | Creative Agency Hub';
$pageCss = ['client-portal.css'];
$pageJs = ['client-portal.js'];
$brandName = 'Creative Agency Hub';
$clientActive = 'support';

$currentUser = $currentUser ?? [
    'name' => 'Khách hàng',
    'role' => 'Client Portal',
    'avatar' => null,
];

$supportTickets = $supportTickets ?? [
    [
        'title' => 'Yêu cầu cập nhật deadline',
        'desc' => 'Khách hàng muốn điều chỉnh thời hạn review UI Client Portal.',
        'status' => 'Đang xử lý',
        'tone' => 'warning',
        'time' => '2 giờ trước',
    ],
    [
        'title' => 'Bổ sung tài liệu nghiệm thu',
        'desc' => 'Đội dự án đã nhận yêu cầu bổ sung checklist nghiệm thu module Task.',
        'status' => 'Đã tiếp nhận',
        'tone' => 'primary',
        'time' => 'Hôm qua',
    ],
    [
        'title' => 'Hỏi về phạm vi bảo trì',
        'desc' => 'Câu hỏi liên quan phạm vi hỗ trợ sau khi bàn giao phiên bản đầu tiên.',
        'status' => 'Đã phản hồi',
        'tone' => 'success',
        'time' => '3 ngày trước',
    ],
];

ob_start();
?>

<section class="client-hero">
    <div class="client-hero-copy">
        <span class="client-kicker">Support Center • Creative Agency Hub</span>
        <h1>Trung tâm hỗ trợ khách hàng.</h1>
        <p>
            Gửi yêu cầu thay đổi, hỏi về tiến độ, báo lỗi hoặc trao đổi nhanh với đội dự án.
            Mọi phản hồi sẽ được ghi nhận để Project Manager xử lý theo đúng luồng.
        </p>
    </div>

    <aside class="client-hero-panel">
        <div class="client-hero-panel-row">
            <span>Yêu cầu đang mở</span>
            <strong>02</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Thời gian phản hồi TB</span>
            <strong>2h</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Người phụ trách</span>
            <strong>Project Manager</strong>
        </div>

        <div class="client-hero-panel-row">
            <span>Trạng thái hỗ trợ</span>
            <strong>Online</strong>
        </div>
    </aside>
</section>

<section class="client-detail-layout">
    <main class="client-detail-main">
        <article class="card">
            <div class="card-header">
                <h2 class="section-title">Gửi yêu cầu hỗ trợ mới</h2>
                <p class="section-subtitle">
                    Điền nội dung bên dưới để gửi yêu cầu đến đội dự án. Đây là giao diện demo,
                    backend sẽ được nối ở phase sau.
                </p>
            </div>

            <div class="card-body">
                <form class="client-feedback-box" data-client-feedback-form>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="support_type">Loại yêu cầu</label>
                            <select id="support_type" class="form-select" name="support_type" required>
                                <option value="">-- Chọn loại yêu cầu --</option>
                                <option value="scope">Thay đổi phạm vi</option>
                                <option value="bug">Báo lỗi giao diện</option>
                                <option value="deadline">Cập nhật deadline</option>
                                <option value="question">Câu hỏi chung</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="support_priority">Mức độ ưu tiên</label>
                            <select id="support_priority" class="form-select" name="support_priority" required>
                                <option value="normal">Bình thường</option>
                                <option value="high">Cao</option>
                                <option value="urgent">Khẩn cấp</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="support_title">Tiêu đề</label>
                        <input
                            id="support_title"
                            class="form-control"
                            type="text"
                            name="support_title"
                            placeholder="VD: Cần cập nhật deadline milestone UI"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="support_message">Nội dung yêu cầu</label>
                        <textarea
                            id="support_message"
                            class="form-textarea"
                            name="feedback"
                            placeholder="Nhập chi tiết yêu cầu hỗ trợ..."
                            required
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="support_attachment">Tài liệu đính kèm</label>
                        <input
                            id="support_attachment"
                            class="form-control"
                            type="file"
                            name="support_attachment"
                        >
                    </div>

                    <button class="btn btn-primary btn-block" type="submit">
                        Gửi yêu cầu hỗ trợ
                    </button>
                </form>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Yêu cầu gần đây</h2>
                    <p class="section-subtitle">Theo dõi trạng thái các yêu cầu đã gửi.</p>
                </div>

                <button class="btn btn-soft" type="button" data-client-action="mock-download">
                    Tải lịch sử
                </button>
            </div>

            <div class="card-body">
                <div class="client-task-list">
                    <?php foreach ($supportTickets as $ticket): ?>
                        <article class="client-task-item">
                            <div class="client-task-info">
                                <h3><?php echo htmlspecialchars($ticket['title']); ?></h3>
                                <p><?php echo htmlspecialchars($ticket['desc']); ?></p>

                                <div class="client-task-meta">
                                    <span class="badge badge-<?php echo htmlspecialchars($ticket['tone']); ?>">
                                        <?php echo htmlspecialchars($ticket['status']); ?>
                                    </span>

                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($ticket['time']); ?>
                                    </span>
                                </div>
                            </div>

                            <button class="btn btn-light" type="button" data-client-action="mock-support">
                                Xem
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </main>

    <aside class="client-detail-side">
        <article class="client-contact-card">
            <h2>Liên hệ nhanh</h2>
            <p>
                Với các yêu cầu gấp, bạn có thể gửi thông báo ưu tiên cho Project Manager.
                Hệ thống sẽ đánh dấu yêu cầu để team xử lý sớm hơn.
            </p>
            <button class="btn btn-light" type="button" data-client-action="mock-support">
                Gửi thông báo ưu tiên
            </button>
        </article>

        <article class="card">
            <div class="card-header">
                <h2 class="section-title">Thông tin hỗ trợ</h2>
                <p class="section-subtitle">Kênh hỗ trợ trong giai đoạn triển khai dự án.</p>
            </div>

            <div class="card-body">
                <div class="client-side-summary">
                    <div class="client-summary-row">
                        <span>Project Manager</span>
                        <strong>Project Manager</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Email hỗ trợ</span>
                        <strong>support@agency.vn</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>Giờ hỗ trợ</span>
                        <strong>08:30 - 17:30</strong>
                    </div>

                    <div class="client-summary-row">
                        <span>SLA phản hồi</span>
                        <strong>Trong 4 giờ</strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-header">
                <h2 class="section-title">Gợi ý gửi yêu cầu</h2>
            </div>

            <div class="card-body">
                <div class="client-milestone-list">
                    <div class="client-milestone is-done">
                        <div class="client-milestone-dot">1</div>
                        <div class="client-milestone-body">
                            <h3>Ghi rõ mục tiêu</h3>
                            <p>Nêu rõ bạn muốn thay đổi gì và lý do cần cập nhật.</p>
                        </div>
                    </div>

                    <div class="client-milestone">
                        <div class="client-milestone-dot">2</div>
                        <div class="client-milestone-body">
                            <h3>Đính kèm tài liệu</h3>
                            <p>Thêm ảnh, file mô tả hoặc checklist nếu có.</p>
                        </div>
                    </div>

                    <div class="client-milestone">
                        <div class="client-milestone-dot">3</div>
                        <div class="client-milestone-body">
                            <h3>Chọn đúng độ ưu tiên</h3>
                            <p>Giúp đội dự án sắp xếp thứ tự xử lý phù hợp hơn.</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </aside>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/client.php';
?>