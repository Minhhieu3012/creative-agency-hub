<?php
/**
 * TRANG PHÊ DUYỆT - MANAGER APPROVALS (BẢN KẾT HỢP TỐI ƯU)
 */
$pageTitle = 'Phê duyệt | Creative Agency Hub';
$pageCss = ['payroll.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'approvals';
$topbarTitle = 'Manager Approvals';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Trung tâm Phê duyệt';
$pageSubtitle = 'Xử lý các yêu cầu duyệt hoàn thành task và đơn nghỉ phép từ nhân viên.';
// Nút làm mới dữ liệu để kích hoạt hàm load từ API
$pageAction = '<button class="btn btn-light" type="button" data-payroll-action="mock-save">⇩ Xuất báo cáo</button>
               <button class="btn btn-primary" type="button" onclick="loadManagerApprovals()">Làm mới dữ liệu</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-shell">
    <!-- Thẻ thống kê nhanh: Kết hợp Icon/Style bản cũ + ID động bản mới[cite: 6] -->
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon" style="background: #e1f5fe; color: #0288d1;">☑</div>
            <div class="stat-card-body">
                <span>Task chờ duyệt</span>
                <strong id="js-count-tasks">--</strong> 
                <small>Cần kiểm tra</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon" style="background: #e8f5e9; color: #2e7d32;">✦</div>
            <div class="stat-card-body">
                <span>Đơn nghỉ phép</span>
                <strong id="js-count-leaves">--</strong> 
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

    <!-- Hệ thống Tabs và Danh sách Phê duyệt động[cite: 6] -->
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
            <!-- Panel Duyệt Task: Container cho JS render[cite: 6] -->
            <section class="approval-panel is-active" data-approval-panel="tasks">
                <div class="approval-list" id="js-task-list">
                    <p class="loading-msg">Đang tải danh sách công việc từ hệ thống...</p>
                </div>
            </section>

            <!-- Panel Duyệt Nghỉ phép: Container cho JS render[cite: 6] -->
            <section class="approval-panel" data-approval-panel="leaves">
                <div class="approval-list" id="js-leave-list">
                    <p class="loading-msg">Đang truy vấn đơn nghỉ phép chờ duyệt...</p>
                </div>
            </section>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>