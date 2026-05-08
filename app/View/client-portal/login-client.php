<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Cổng thông tin Khách hàng | Creative Agency Hub';
$pageCss   = ['auth.css'];
$pageJs    = ['forms.js', 'auth-portal.js'];
$brandName = 'Creative Agency Hub';
$bodyClass = 'client-login-body';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$publicUrl = $baseUrl . '/public';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$error = $error ?? null;

ob_start();
?>

<section class="client-login-wrapper">
    <div class="client-login-card">
        <section class="client-login-form">
            <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/login-client.php" class="client-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <div class="client-login-title">
                <h1>Cổng thông tin<br>Khách hàng</h1>
                <p>
                    Theo dõi tiến độ dự án, xem task public, tải file thiết kế và gửi phản hồi
                    trực tiếp tới đội ngũ sáng tạo.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="form-alert form-alert-danger">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form
                method="POST"
                action="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>/api/auth/login-client"
                data-auth-portal-form
                data-auth-endpoint="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>/api/auth/login-client"
                data-auth-redirect="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php"
                data-success-message="Đăng nhập thành công. Đang chuyển vào Client Portal..."
            >
                <div class="form-group">
                    <label class="form-label" for="client-email">Email</label>
                    <div class="input-with-icon">
                        <span class="input-icon">✉</span>
                        <input
                            id="client-email"
                            class="form-control"
                            type="email"
                            name="email"
                            placeholder="client@agency.vn"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label-row">
                        <label class="form-label" for="client-password">Mật khẩu</label>
                        <a class="form-label-link" href="#" data-disabled-demo>Quên mật khẩu?</a>
                    </div>

                    <div class="input-with-icon">
                        <span class="input-icon">▣</span>
                        <input
                            id="client-password"
                            class="form-control"
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-eye"
                            data-password-toggle="#client-password"
                            aria-label="Hiện/ẩn mật khẩu"
                        >👁</button>
                    </div>
                </div>

                <label class="checkbox-line">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>

                <div class="form-alert" data-auth-message style="display: none;"></div>

                <button type="submit" class="btn btn-primary auth-submit">
                    <span>Truy cập cổng thông tin</span>
                    <span>→</span>
                </button>
            </form>

            <div class="auth-divider">Chuyển cổng đăng nhập</div>

            <div class="auth-social-grid">
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/staff/auth/login.php">
                    <span>▦</span>
                    <span>Internal Portal</span>
                </a>

                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/admin/auth/login.php">
                    <span>◆</span>
                    <span>Admin Portal</span>
                </a>
            </div>

            <p class="auth-footer-line">
                Chưa có tài khoản? <a href="#" data-disabled-demo>Liên hệ với đội ngũ dự án</a>
            </p>

            <div class="auth-legal">
                <span>© 2026 Creative Agency Hub</span>
                <span>
                    <a href="#" data-disabled-demo>Bảo mật</a>
                    &nbsp;&nbsp;
                    <a href="#" data-disabled-demo>Điều khoản</a>
                </span>
            </div>
        </section>

        <aside class="client-login-visual">
            <img
                class="client-login-visual-image"
                src="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>/assets/pictures/customerpagelogin.jpg"
                alt="Creative Agency Hub client portal"
            >

            <div class="client-glass-panel">
                <div class="client-trust-line">
                    <span class="brand-mark">CA</span>
                    <span>Creative Agency Hub</span>
                </div>

                <h2>Dự án của bạn,<br>minh bạch từng bước.</h2>
                <p>
                    Xem tiến độ thực tế, theo dõi các task được public và phản hồi trực tiếp
                    với Manager phụ trách dự án.
                </p>

                <div class="client-pill-row">
                    <span class="client-pill">
                        <span>📊</span>
                        <span>Tiến độ thực tế</span>
                    </span>
                    <span class="client-pill">
                        <span>💬</span>
                        <span>Feedback trực tiếp</span>
                    </span>
                    <span class="client-pill">
                        <span>📁</span>
                        <span>File thiết kế</span>
                    </span>
                </div>
            </div>
        </aside>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';
?>