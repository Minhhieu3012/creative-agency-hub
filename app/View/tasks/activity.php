<?php
$pageTitle = 'Nhật ký hoạt động | Creative Agency Hub';
$pageCss = []; // Thêm css riêng nếu cần
$pageJs = [];  // Thêm js riêng nếu cần
$activeMenu = 'dashboard'; // Giữ sáng menu Bảng điều khiển
$topbarTitle = 'Activity Logs';

ob_start();
?>

<?php
$pageHeading = 'Toàn bộ nhật ký hệ thống';
$pageSubtitle = 'Theo dõi chi tiết các thao tác, thay đổi trạng thái và phân công công việc.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="activity-page-content">
    <article class="card">
        <div class="card-header">
            <h2>Lịch sử hoạt động</h2>
        </div>
        <div class="card-body">
            <p style="padding: 20px; color: #6c757d; text-align: center;">
                Giao diện và API danh sách nhật ký chi tiết sẽ được xây dựng tại đây...
            </p>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
