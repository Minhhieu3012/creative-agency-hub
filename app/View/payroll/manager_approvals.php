<?php
$pageTitle = 'Phê duyệt | Creative Agency Hub';
$pageCss = ['payroll.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'approvals';
$topbarTitle = 'Manager Approvals';
$brandName = 'Creative Agency Hub';

$taskApprovals = $taskApprovals ?? [
    [
        'initials' => 'PA',
        'title' => 'Duyệt hoàn thành: Thiết kế UI Login',
        'desc' => 'Phạm Anh đã submit task hoàn thành, cần Manager kiểm tra kết quả trước khi chuyển Done.',
        'project' => 'NexusHR Web',
        'deadline' => 'Hôm nay',
        'priority' => 'Cao',
    ],
    [
        'initials' => 'HB',
        'title' => 'Duyệt hoàn thành: Fix Auth API',
        'desc' => 'Hiếu Backend đã gửi yêu cầu review task liên quan đăng nhập và session.',
        'project' => 'Creative Agency Hub',
        'deadline' => 'Ngày mai',
        'priority' => 'Critical',
    ],
    [
        'initials' => 'TM',
        'title' => 'Yêu cầu làm lại: Nội dung hợp đồng mẫu',
        'desc' => 'Cần kiểm tra lại nội dung hợp đồng mẫu trước khi chuyển sang trạng thái hoàn thành.',
        'project' => 'HRM Module',
        'deadline' => '2 ngày tới',
        'priority' => 'Trung bình',
    ],
];

$leaveApprovals = $leaveApprovals ?? [
    [
        'initials' => 'LM',
        'title' => 'Đơn nghỉ phép: Lê Thị Mai',
        'desc' => 'Xin nghỉ 02 ngày từ 22/10/2026 đến 23/10/2026 vì việc gia đình.',
        'balance' => 'Còn 08 ngày phép',
        'type' => 'Nghỉ phép năm',
        'duration' => '02 ngày',
    ],
    [
        'initials' => 'PA',
        'title' => 'Đơn nghỉ phép: Phạm Duy Anh',
        'desc' => 'Xin nghỉ buổi sáng ngày 25/10/2026 để khám sức khỏe định kỳ.',
        'balance' => 'Còn 04 ngày phép',
        'type' => 'Nghỉ nửa ngày',
        'duration' => '0.5 ngày',
    ],
];

ob_start();
?>

<?php
$pageHeading = 'Trung tâm Phê duyệt';
$pageSubtitle = 'Xử lý các yêu cầu duyệt task hoàn thành, nghỉ phép và nghiệp vụ nội bộ từ nhân viên.';
$pageAction = '<button class="btn btn-light" type="button" data-payroll-action="mock-save">⇩ Xuất báo cáo</button><button class="btn btn-primary" type="button" data-payroll-action="mock-save">Làm mới dữ liệu</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-shell">
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">☑</div>
            <div class="stat-card-body">
                <span>Task chờ duyệt</span>
                <strong><?php echo count($taskApprovals); ?></strong>
                <small>Cần kiểm tra</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✦</div>
            <div class="stat-card-body">
                <span>Đơn nghỉ phép</span>
                <strong><?php echo count($leaveApprovals); ?></strong>
                <small>Đang chờ phản hồi</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Thời gian xử lý TB</span>
                <strong>2h</strong>
                <small>Trong tuần này</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Yêu cầu quá hạn</span>
                <strong>01</strong>
                <small>Cần xử lý ngay</small>
            </div>
        </article>
    </div>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Danh sách yêu cầu</h2>
                <p class="section-subtitle">Chuyển tab để xem từng nhóm phê duyệt.</p>
            </div>

            <div class="approval-tabs">
                <button class="approval-tab is-active" type="button" data-approval-tab="tasks">
                    Duyệt Task
                </button>
                <button class="approval-tab" type="button" data-approval-tab="leaves">
                    Duyệt Nghỉ phép
                </button>
            </div>
        </div>

        <div class="card-body">
            <section class="approval-panel is-active" data-approval-panel="tasks">
                <div class="approval-list">
                    <?php foreach ($taskApprovals as $item): ?>
                        <article class="approval-card" data-approval-card>
                            <div class="approval-avatar"><?php echo htmlspecialchars($item['initials']); ?></div>

                            <div class="approval-content">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['desc']); ?></p>

                                <div class="approval-meta">
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($item['project']); ?></span>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($item['deadline']); ?></span>
                                    <span class="badge badge-warning"><?php echo htmlspecialchars($item['priority']); ?></span>
                                </div>
                            </div>

                            <div class="approval-actions">
                                <button class="btn btn-danger-soft" type="button" data-payroll-action="reject">
                                    Từ chối
                                </button>
                                <button class="btn btn-light" type="button" data-payroll-action="mock-save">
                                    Yêu cầu làm lại
                                </button>
                                <button class="btn btn-primary" type="button" data-payroll-action="approve">
                                    Duyệt
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="approval-panel" data-approval-panel="leaves">
                <div class="approval-list">
                    <?php foreach ($leaveApprovals as $item): ?>
                        <article class="approval-card" data-approval-card>
                            <div class="approval-avatar"><?php echo htmlspecialchars($item['initials']); ?></div>

                            <div class="approval-content">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo htmlspecialchars($item['desc']); ?></p>

                                <div class="approval-meta">
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($item['type']); ?></span>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($item['duration']); ?></span>
                                    <span class="badge badge-success"><?php echo htmlspecialchars($item['balance']); ?></span>
                                </div>
                            </div>

                            <div class="approval-actions">
                                <button class="btn btn-danger-soft" type="button" data-payroll-action="reject">
                                    Từ chối
                                </button>
                                <button class="btn btn-primary" type="button" data-payroll-action="approve">
                                    Duyệt phép
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>