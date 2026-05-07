<?php
/**
 * TRANG XIN NGHỈ PHÉP - KẾT HỢP DỮ LIỆU ĐỘNG
 */
$pageTitle = 'Xin nghỉ phép | Creative Agency Hub';
$pageCss = ['payroll.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'leave_request';
$topbarTitle = 'Leave Request';
$brandName = 'Creative Agency Hub';

// Đã loại bỏ mảng $leaveHistory giả để ưu tiên dữ liệu thực từ API thông qua payroll.js.

ob_start();
?>

<?php
// Tích hợp Component Header trang
$pageHeading = 'Xin Nghỉ phép';
$pageSubtitle = 'Gửi đơn nghỉ trực tuyến, theo dõi quỹ phép còn lại và lịch sử phê duyệt.';
$pageAction = '<a class="btn btn-light" href="/creative-agency-hub/app/View/payroll/manager_approvals.php">Xem phê duyệt</a>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-grid">
    <div class="payroll-shell">
        <!-- Thẻ Quỹ phép: Sử dụng ID để JavaScript cập nhật dữ liệu từ employees table -->
        <article class="leave-balance-card">
            <div>
                <h2>Quỹ phép còn lại</h2>

                <div class="leave-balance-number">
                    <strong id="js-leave-balance">--</strong>
                    <span>ngày</span>
                </div>

                <p id="js-leave-summary">
                    Đang kết nối dữ liệu quỹ phép...
                </p>
            </div>

            <button class="btn btn-light" type="button" data-payroll-action="mock-save">
                Xem chính sách nghỉ phép
            </button>
        </article>

        <!-- Thẻ Lịch sử: Chuyển sang chế độ Render động qua JavaScript -->
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Lịch sử đơn nghỉ</h2>
                <button class="btn btn-soft" type="button">Lọc</button>
            </div>

            <div class="card-body">
                <div class="leave-history" id="js-leave-history">
                    <p style="text-align:center; color:#999; padding: 20px;">Đang tải lịch sử đơn nghỉ...</p>
                </div>
            </div>
        </article>
    </div>

    <!-- Thẻ Tạo đơn mới: Giữ nguyên cấu trúc Form để đồng bộ với xử lý Submit trong payroll.js -->
    <article class="card leave-form-card">
        <div class="card-header">
            <h2 class="section-title">Tạo đơn nghỉ mới</h2>
            <p class="section-subtitle">Thông tin sẽ được gửi đến quản lý trực tiếp để phê duyệt.</p>
        </div>

        <div class="card-body">
            <form data-leave-form>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="leave_type">Loại nghỉ</label>
                        <select id="leave_type" class="form-select" name="leave_type" required>
                            <option value="">-- Chọn loại nghỉ --</option>
                            <option value="annual">Nghỉ phép năm</option>
                            <option value="sick">Nghỉ ốm</option>
                            <option value="personal">Nghỉ việc cá nhân</option>
                            <option value="half_day">Nghỉ nửa ngày</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="leave_duration">Số ngày</label>
                        <input id="leave_duration" class="form-control" type="number" min="0.5" step="0.5" name="duration" placeholder="Tự tính theo ngày" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Từ ngày</label>
                        <input id="start_date" class="form-control" type="date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_date">Đến ngày</label>
                        <input id="end_date" class="form-control" type="date" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason">Lý do nghỉ</label>
                    <textarea id="reason" class="form-textarea" name="reason" placeholder="Nhập lý do nghỉ phép..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="attachment">Tài liệu đính kèm</label>
                    <input id="attachment" class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    <small class="form-help">
                        Tệp đính kèm đang ở bước giao diện. API hiện tại nhận JSON nên chưa lưu file vào database.
                    </small>
                </div>

                <button class="btn btn-primary btn-block" type="submit">
                    Gửi đơn nghỉ phép
                </button>
            </form>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>