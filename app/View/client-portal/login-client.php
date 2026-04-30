<?php
$pageTitle = 'Cổng thông tin Khách hàng | Creative Agency Hub';
$pageCss   = ['auth.css'];
$pageJs    = ['forms.js'];
$brandName = 'Creative Agency Hub';
$bodyClass = 'client-login-body';

$error = $error ?? null;
ob_start();
?>

<section class="client-login-wrapper">
    <div class="client-login-card">

        <!-- ===== FORM SIDE (left) ===== -->
        <section class="client-login-form">
            <a href="<?php echo APP_URL; ?>/client-portal/login-client.php" class="client-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <div class="client-login-title">
                <h1>Cổng thông tin<br>Khách hàng</h1>
                <p>Theo dõi tiến độ dự án, xem báo cáo và gửi phản hồi trực tiếp tới đội ngũ sáng tạo của chúng tôi.</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="form-alert form-alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo APP_URL; ?>/api/auth/login" data-ui-form data-mock-submit="false"
                data-success-message="Đăng nhập thành công!"
                data-redirect="<?php echo APP_URL; ?>/client-portal/projects.php">
                <div class="form-group">
                    <label class="form-label" for="client-email">Email</label>
                    <div class="input-with-icon">
                        <span class="input-icon">✉</span>
                        <input id="client-email" class="form-control" type="email" name="email"
                            placeholder="name@company.com" autocomplete="email" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label-row">
                        <label class="form-label" for="client-password">Mật khẩu</label>
                        <a class="form-label-link" href="#">Quên mật khẩu?</a>
                    </div>
                    <div class="input-with-icon">
                        <span class="input-icon">▣</span>
                        <input id="client-password" class="form-control" type="password" name="password"
                            placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="password-eye" data-password-toggle="#client-password"
                            aria-label="Hiện/ẩn mật khẩu">👁</button>
                    </div>
                </div>

                <label class="checkbox-line">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>

                <button type="submit" class="btn btn-primary auth-submit">
                    <span>Truy cập cổng thông tin</span>
                    <span>→</span>
                </button>
            </form>

            <div class="auth-divider">Hoặc tiếp tục với</div>

            <div class="auth-social-grid">
                <button class="btn btn-light" type="button">
                    <span>G</span>
                    <span>Google</span>
                </button>
                <button class="btn btn-light" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 21 21"
                        aria-hidden="true">
                        <rect x="1" y="1" width="9" height="9" fill="#f25022" />
                        <rect x="11" y="1" width="9" height="9" fill="#7fba00" />
                        <rect x="1" y="11" width="9" height="9" fill="#00a4ef" />
                        <rect x="11" y="11" width="9" height="9" fill="#ffb900" />
                    </svg>
                    <span>Microsoft</span>
                </button>
            </div>

            <p class="auth-footer-line">
                Chưa có tài khoản? <a href="#">Liên hệ với chúng tôi</a>
            </p>

            <div class="auth-legal">
                <span>© 2026 Creative Agency Hub</span>
                <span>
                    <a href="#">Bảo mật</a>
                    &nbsp;&nbsp;
                    <a href="#">Điều khoản</a>
                </span>
            </div>
        </section>

        <!-- ===== VISUAL SIDE (right) ===== -->
        <aside class="client-login-visual">
            <img class="client-login-visual-image" src="<?php echo APP_URL; ?>/assets/pictures/customerpagelogin.jpg"
                alt="Creative Agency Hub client portal">

            <div class="client-glass-panel">
                <div class="client-trust-line">
                    <span class="brand-mark">CA</span>
                    <span>Creative Agency Hub</span>
                </div>

                <h2>Dự án của bạn,<br>minh bạch từng bước.</h2>
                <p>
                    Xem tiến độ thực tế, nhận báo cáo định kỳ và trao đổi trực tiếp
                    với đội ngũ — tất cả trong một cổng thông tin dành riêng cho bạn.
                </p>

                <div class="client-pill-row">
                    <span class="client-pill">
                        <span>📊</span>
                        <span>Tiến độ thực tế</span>
                    </span>
                    <span class="client-pill">
                        <span>💬</span>
                        <span>Phản hồi trực tiếp</span>
                    </span>
                    <span class="client-pill">
                        <span>📁</span>
                        <span>Quản lý tài liệu</span>
                    </span>
                </div>
            </div>
        </aside>

    </div>
</section>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>