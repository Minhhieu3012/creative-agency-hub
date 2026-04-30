<?php
$pageTitle = 'Đăng nhập nội bộ | Creative Agency Hub';
$pageCss = ['auth.css'];
$pageJs = ['forms.js'];
$brandName = 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$error = $error ?? null;

ob_start();
?>

<section class="auth-split-wrapper">
    <div class="auth-split-card">
        <aside class="auth-hero">
            <div class="auth-hero-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </div>

            <div class="auth-hero-copy">
                <h1>Quản trị đội ngũ sáng tạo trong một nền tảng.</h1>
                <p>
                    Theo dõi nhân sự, dự án, tiến độ công việc, chấm công và phê duyệt nội bộ
                    với trải nghiệm gọn gàng, hiện đại và chuẩn doanh nghiệp.
                </p>
            </div>

            <div class="auth-preview-card">
                <div class="auth-preview-image-frame">
                    <<<<<<< HEAD <img src="/creative-agency-hub/public/assets/pictures/teampagelogin.jpg"
                        alt="Creative Agency Hub team workspace">
                        =======
                        <img src="<?php echo htmlspecialchars($baseUrl); ?>/public/assets/pictures/teampagelogin.jpg"
                            alt="Creative Agency Hub team workspace">
                        >>>>>>> ccc7777a98bedbb44367200728c146105f63c457
                </div>
            </div>
        </aside>

        <section class="auth-form-side">
            <div class="auth-form-box">
                <div class="auth-form-title">
                    <h2>Chào mừng trở lại!</h2>
                    <p>Vui lòng nhập thông tin để truy cập hệ thống quản trị của Creative Agency Hub.</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="form-alert form-alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($baseUrl); ?>/public/api/auth/login"
                    data-ui-form data-auth-login="true"
                    data-success-message="Đăng nhập thành công. Đang chuyển về Dashboard..."
                    data-redirect="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-with-icon">
                            <span class="input-icon">✉</span>
                            <input id="email" class="form-control" type="email" name="email"
                                placeholder="name@company.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <span>Mật khẩu</span>
                            <a href="#">Quên mật khẩu?</a>
                        </label>

                        <div class="input-with-icon">
                            <span class="input-icon">▣</span>
                            <input id="password" class="form-control" type="password" name="password"
                                placeholder="••••••••" autocomplete="current-password" required>

                            <button type="button" class="password-eye" data-password-toggle="#password"
                                aria-label="Hiện/ẩn mật khẩu">👁</button>
                        </div>
                    </div>

                    <label class="checkbox-line">
                        <input type="checkbox" name="remember">
                        <span>Ghi nhớ đăng nhập</span>
                    </label>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <span>Đăng nhập hệ thống</span>
                        <span>→</span>
                    </button>
                </form>

                <div class="auth-divider">Hoặc tiếp tục với</div>

                <div class="auth-social-grid">
                    <button class="btn btn-light" type="button" data-disabled-demo>
                        <span>G</span>
                        <span>Google</span>
                    </button>

                    <button class="btn btn-light" type="button" data-disabled-demo>
                        <span>▦</span>
                        <span>SSO</span>
                    </button>
                </div>

                <p class="auth-footer-line">
                    Chưa có tài khoản? <a href="#" data-disabled-demo>Liên hệ quản trị viên</a>
                </p>

                <div class="auth-legal">
                    <span>© 2026 Creative Agency Hub</span>
                    <span>
                        <a href="#" data-disabled-demo>Bảo mật</a>
                        &nbsp;&nbsp;
                        <a href="#" data-disabled-demo>Điều khoản</a>
                    </span>
                </div>
            </div>
        </section>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';
?>