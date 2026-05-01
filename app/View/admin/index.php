<?php
$pageTitle = 'Quản trị hệ thống | Creative Agency Hub';
$pageCss = ['role-home.css'];
$pageJs = ['app.js', 'forms.js', 'toast.js'];
$activeMenu = 'admin_home';
$topbarTitle = 'Admin Console';
$brandName = 'Creative Agency Hub';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

ob_start();
?>

<section class="role-home role-home-admin">
    <div class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker">Admin Console • Creative Agency Hub</span>
            <h1>Quản trị nền tảng, tài khoản, dịch vụ và phản hồi hệ thống.</h1>
            <p>
                Admin không xử lý project, task hay nhân sự vận hành. Khu vực này tập trung
                vào tài khoản đăng nhập, cấu hình dịch vụ, feedback khách hàng và trạng thái hệ thống.
            </p>

            <div class="role-hero-actions">
                <a class="btn btn-light" href="#accounts">Quản lý tài khoản</a>
                <a class="btn btn-ghost" href="#feedback">Xem feedback</a>
            </div>
        </div>

        <div class="role-hero-panel">
            <div class="role-panel-row">
                <span>Vai trò</span>
                <strong>Admin</strong>
            </div>
            <div class="role-panel-row">
                <span>Trọng tâm</span>
                <strong>Hệ thống</strong>
            </div>
            <div class="role-panel-row">
                <span>Không xử lý</span>
                <strong>Project / Task</strong>
            </div>
            <div class="role-panel-row">
                <span>Trạng thái</span>
                <strong>Toàn quyền nền tảng</strong>
            </div>
        </div>
    </div>

    <div class="role-stat-grid">
        <article class="role-stat-card" id="accounts">
            <span class="role-stat-icon">◎</span>
            <div>
                <h3>Tài khoản hệ thống</h3>
                <strong>04</strong>
            </div>
        </article>

        <article class="role-stat-card" id="services">
            <span class="role-stat-icon">◇</span>
            <div>
                <h3>Dịch vụ đang bật</h3>
                <strong>06</strong>
            </div>
        </article>

        <article class="role-stat-card" id="feedback">
            <span class="role-stat-icon">☷</span>
            <div>
                <h3>Feedback mới</h3>
                <strong>12</strong>
            </div>
        </article>

        <article class="role-stat-card" id="settings">
            <span class="role-stat-icon">⚙</span>
            <div>
                <h3>Cấu hình cần kiểm tra</h3>
                <strong>03</strong>
            </div>
        </article>
    </div>

    <div class="role-layout">
        <article class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Trung tâm vận hành hệ thống</h2>
                    <p>Những phần admin nên quản lý, tách khỏi nghiệp vụ manager.</p>
                </div>
                <button class="btn btn-light" type="button" data-disabled-demo>Làm mới</button>
            </div>

            <div class="role-card-body">
                <div class="role-list">
                    <div class="role-list-item">
                        <span class="role-list-icon">1</span>
                        <div class="role-list-content">
                            <h3>Tài khoản đăng nhập</h3>
                            <p>Tạo, khóa, mở khóa tài khoản admin / manager / employee / client.</p>
                        </div>
                        <span class="badge badge-primary">Account</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">2</span>
                        <div class="role-list-content">
                            <h3>Dịch vụ hệ thống</h3>
                            <p>Kiểm tra trạng thái đăng nhập, upload, notification và client portal.</p>
                        </div>
                        <span class="badge badge-success">Service</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">3</span>
                        <div class="role-list-content">
                            <h3>Feedback & hỗ trợ</h3>
                            <p>Theo dõi phản hồi của client và tình trạng xử lý yêu cầu hỗ trợ.</p>
                        </div>
                        <span class="badge badge-warning">Feedback</span>
                    </div>

                    <div class="role-list-item">
                        <span class="role-list-icon">4</span>
                        <div class="role-list-content">
                            <h3>Cấu hình bảo mật</h3>
                            <p>Kiểm tra JWT, session, quyền truy cập và các khóa cấu hình.</p>
                        </div>
                        <span class="badge badge-danger">Security</span>
                    </div>
                </div>
            </div>
        </article>

        <aside class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Admin scope</h2>
                    <p>Chốt rõ để không lẫn với manager.</p>
                </div>
            </div>

            <div class="role-card-body">
                <div class="role-quick-grid">
                    <a class="role-quick-link" href="#accounts">
                        <span>Tài khoản</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="#services">
                        <span>Dịch vụ</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="#feedback">
                        <span>Feedback</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="#settings">
                        <span>Cấu hình</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="role-note" style="margin-top: 18px;">
                    <h3>Không quản lý task/project</h3>
                    <p>
                        Project, task, employee workflow thuộc manager. Admin chỉ giữ vai trò vận hành nền tảng
                        và kiểm soát hệ thống.
                    </p>
                </div>
            </div>
        </aside>
    </div>
</section>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/app.php';
?>