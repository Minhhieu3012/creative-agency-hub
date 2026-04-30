<?php
$pageTitle = 'Client Portal | Creative Agency Hub';
$pageCss = ['auth.css'];
$pageJs = ['forms.js'];
$brandName = 'Creative Agency Hub';
$bodyClass = 'client-login-body';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$error = $error ?? null;

ob_start();
?>

<section class="client-login-wrapper">
    <div class="client-login-card">
        <section class="client-login-form">
            <a href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/login-client.php" class="client-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <div class="client-login-title">
                <h1>Cổng thông tin Khách hàng</h1>
                <p>
                    Theo dõi tiến độ dự án, xem các đầu việc được chia sẻ và gửi phản hồi trực tiếp
                    đến đội ngũ phụ trách.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="form-alert form-alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form
                method="POST"
                action="<?php echo htmlspecialchars($baseUrl); ?>/public/api/auth/login"
                data-ui-form
                data-auth-login="true"
                data-success-message="Đăng nhập Client Portal thành công."
                data-redirect="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php"
            >
                <div class="form-group">
                    <label class="form-label" for="client_email">Địa chỉ Email</label>
                    <div class="input-with-icon">
                        <span class="input-icon">✉</span>
                        <input
                            id="client_email"
                            class="form-control"
                            type="email"
                            name="email"
                            placeholder="client@company.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="client_password">
                        <span>Mật khẩu</span>
                        <a href="#" data-disabled-demo>Quên mật khẩu?</a>
                    </label>

                    <div class="input-with-icon">
                        <span class="input-icon">▣</span>
                        <input
                            id="client_password"
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
                            data-password-toggle="#client_password"
                            aria-label="Hiện/ẩn mật khẩu"
                        >👁</button>
                    </div>
                </div>

                <label class="checkbox-line">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập trên thiết bị này</span>
                </label>

                <button type="submit" class="btn btn-primary auth-submit">
                    <span>Đăng nhập hệ thống</span>
                    <span>→</span>
                </button>
            </form>

            <p class="auth-footer-line">
                Bạn gặp sự cố khi truy cập?
                <a href="#" data-disabled-demo>Liên hệ hỗ trợ kỹ thuật</a>
            </p>

            <div class="auth-legal">
                <span>© 2026 Creative Agency Hub</span>
                <span>
                    <a href="#" data-disabled-demo>Quy định bảo mật</a>
                    &nbsp;&nbsp;
                    <a href="#" data-disabled-demo>Điều khoản sử dụng</a>
                </span>
            </div>
        </section>

        <aside class="client-login-visual">
            <img
                class="client-login-visual-image"
                src="<?php echo htmlspecialchars($baseUrl); ?>/public/assets/pictures/customerpagelogin.jpg"
                alt="Client collaboration portal"
            >

            <div class="client-glass-panel">
                <div class="client-trust-line">
                    <span class="brand-mark">✓</span>
                    <span>Không gian cộng tác dành riêng cho khách hàng</span>
                </div>

                <h2>Nâng tầm trải nghiệm theo dõi dự án.</h2>

                <p>
                    Minh bạch tiến độ, tập trung phản hồi và giữ mọi cập nhật quan trọng
                    trong cùng một luồng làm việc chuyên nghiệp.
                </p>

                <div class="client-pill-row">
                    <span class="client-pill">✦ Bảo mật dữ liệu</span>
                    <span class="client-pill">↗ Cập nhật realtime</span>
                    <span class="client-pill">☑ Phản hồi tập trung</span>
                </div>
            </div>
        </aside>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';
?>