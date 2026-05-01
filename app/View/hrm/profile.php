<?php
$pageTitle = 'Hồ sơ cá nhân | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'profile';
$topbarTitle = 'Hồ sơ cá nhân';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Hồ sơ Cá nhân';
$pageSubtitle = 'Xem thông tin nhân sự, số ngày phép và các dữ liệu cá nhân liên quan đến tài khoản đăng nhập.';
$pageAction = '
<button class="btn btn-light" type="button" data-hrm-action="refresh-profile">Làm mới</button>
<button class="btn btn-primary" type="button" data-hrm-action="open-edit-profile">Cập nhật hồ sơ</button>
';
require __DIR__ . '/../components/page-header.php';
?>

<section class="hrm-grid" data-hrm-page="profile">
    <article class="hrm-profile-hero">
        <div class="profile-avatar-large" data-profile-avatar>
            <span>CA</span>
        </div>

        <div class="profile-main-info">
            <h1 data-profile-name>Đang tải hồ sơ...</h1>
            <p data-profile-position>Đang đồng bộ dữ liệu nhân sự từ HRM API.</p>

            <div class="profile-badge-row">
                <span class="badge badge-primary" data-profile-role>Role</span>
                <span class="badge badge-success" data-profile-status>Status</span>
                <span class="badge badge-info" data-profile-code>Employee Code</span>
            </div>
        </div>

        <div class="profile-actions">
            <button class="btn btn-light" type="button" data-hrm-action="open-edit-profile">
                ✎ Chỉnh sửa thông tin
            </button>

            <button class="btn btn-primary" type="button" data-hrm-action="open-avatar-upload">
                ↑ Upload avatar
            </button>
        </div>
    </article>

    <section class="hrm-two-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Thông tin cá nhân</h2>
                <button class="btn btn-soft" type="button" data-hrm-action="open-edit-profile">Cập nhật</button>
            </div>

            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <small>Email</small>
                        <strong data-profile-email>—</strong>
                    </div>

                    <div class="info-item">
                        <small>Số điện thoại</small>
                        <strong data-profile-phone>—</strong>
                    </div>

                    <div class="info-item">
                        <small>Giới tính</small>
                        <strong data-profile-gender>—</strong>
                    </div>

                    <div class="info-item">
                        <small>Ngày sinh</small>
                        <strong data-profile-dob>—</strong>
                    </div>

                    <div class="info-item">
                        <small>Phòng ban</small>
                        <strong data-profile-department>—</strong>
                    </div>

                    <div class="info-item">
                        <small>Ngày vào làm</small>
                        <strong data-profile-hire-date>—</strong>
                    </div>

                    <div class="info-item" style="grid-column: 1 / -1;">
                        <small>Địa chỉ</small>
                        <strong data-profile-address>—</strong>
                    </div>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Ngày phép</h2>
                <span class="badge badge-primary">Leave Balance</span>
            </div>

            <div class="card-body">
                <div class="kpi-list">
                    <div class="kpi-line">
                        <div class="kpi-line-head">
                            <span>Số ngày phép còn lại</span>
                            <span data-profile-remaining-leave>0 ngày</span>
                        </div>
                        <div class="progress-line">
                            <div class="progress-line-fill" data-profile-leave-progress style="width: 0%;"></div>
                        </div>
                    </div>

                    <div class="kpi-line">
                        <div class="kpi-line-head">
                            <span>Tổng ngày phép năm</span>
                            <span data-profile-total-leave>0 ngày</span>
                        </div>
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: 100%;"></div>
                        </div>
                    </div>

                    <div class="kpi-line">
                        <div class="kpi-line-head">
                            <span>Trạng thái hồ sơ</span>
                            <span data-profile-status-note>Đang tải</span>
                        </div>
                        <div class="progress-line">
                            <div class="progress-line-fill" data-profile-status-progress style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tài liệu cá nhân</h2>
                <p class="section-subtitle">Khu vực demo hồ sơ giấy tờ; backend document sẽ nối sau.</p>
            </div>

            <button class="btn btn-soft" type="button" data-hrm-action="upload-doc">＋ Upload hồ sơ</button>
        </div>

        <div class="card-body">
            <div class="document-grid">
                <div class="document-card">
                    <div class="document-icon">PDF</div>
                    <div class="document-info">
                        <strong>Hợp đồng lao động</strong>
                        <small>Đang chờ module EmployeeContract</small>
                    </div>
                    <button class="document-download" type="button" data-hrm-action="upload-doc">↓</button>
                </div>

                <div class="document-card">
                    <div class="document-icon">ID</div>
                    <div class="document-info">
                        <strong>Hồ sơ định danh</strong>
                        <small>Upload sau khi hoàn thiện API document</small>
                    </div>
                    <button class="document-download" type="button" data-hrm-action="upload-doc">↓</button>
                </div>
            </div>
        </div>
    </article>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>