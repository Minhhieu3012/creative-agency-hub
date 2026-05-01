<?php
$pageTitle = 'Đăng nhập | Creative Agency Hub';
$pageCss = ['auth.css'];
$pageJs = ['app.js', 'forms.js', 'toast.js'];
$brandName = 'Creative Agency Hub';
$bodyClass = 'auth-page';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$error = $error ?? null;

ob_start();
?>

<section class="team-login-wrapper">
    <div class="team-login-card">
        <section class="team-login-visual">
            <div class="team-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </div>

            <div class="team-login-copy">
                <h1>Quản trị đội ngũ sáng tạo trong một nền tảng.</h1>
                <p>
                    Theo dõi nhân sự, dự án, tiến độ công việc, chấm công và phê duyệt nội bộ
                    với trải nghiệm gọn gàng, hiện đại và chuẩn doanh nghiệp.
                </p>
            </div>

            <div class="team-login-picture-frame">
                <img
                    class="team-login-picture"
                    src="<?php echo htmlspecialchars($baseUrl); ?>/public/assets/pictures/teampagelogin.jpg"
                    alt="Creative Agency Hub team workspace"
                >
            </div>
        </section>

        <section class="team-login-form-panel">
            <div class="auth-form-box">
                <div class="auth-form-heading">
                    <h2>Chào mừng trở lại!</h2>
                    <p>Vui lòng nhập thông tin để truy cập hệ thống quản trị của Creative Agency Hub.</p>
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
                    data-auth-login-form
                    data-login-type="team"
                    data-success-message="Đăng nhập thành công!"
                    data-redirect-admin="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php"
                    data-redirect-manager="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php"
                    data-redirect-employee="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php"
                    data-redirect-client="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php"
                >
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-with-icon">
                            <span class="input-icon">✉</span>
                            <input
                                id="email"
                                class="form-control"
                                type="email"
                                name="email"
                                placeholder="name@company.com"
                                autocomplete="email"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-label-row">
                            <label class="form-label" for="password">Mật khẩu</label>
                            <a class="form-label-link" href="#">Quên mật khẩu?</a>
                        </div>

                        <div class="input-with-icon">
                            <span class="input-icon">▣</span>
                            <input
                                id="password"
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
                                data-password-toggle="#password"
                                aria-label="Hiện/ẩn mật khẩu"
                            >👁</button>
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
                    Chưa có tài khoản?
                    <a href="#">Liên hệ quản trị viên</a>
                </p>

                <div class="auth-legal">
                    <span>© 2026 Creative Agency Hub</span>
                    <span>
                        <a href="#">Bảo mật</a>
                        &nbsp;&nbsp;
                        <a href="#">Điều khoản</a>
                    </span>
                </div>
            </div>
        </section>
    </div>
</section>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>