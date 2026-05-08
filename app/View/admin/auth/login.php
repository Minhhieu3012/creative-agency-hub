<?php
$pageTitle = 'Đăng nhập Admin | Creative Agency Hub';
$pageCss = ['auth.css'];
$pageJs = ['forms.js', 'auth-portal.js'];
$brandName = 'Creative Agency Hub';
$bodyClass = 'auth-body';

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
                <h1>Quản trị hệ thống web.</h1>
                <p>
                    Admin quản lý tài khoản, phòng ban, chức vụ, customer, manager và employee.
                    Đây là cổng kiểm soát hệ thống, không phải workspace tạo project/task hằng ngày.
                </p>
            </div>

            <div class="auth-preview-card">
                <div class="auth-preview-image-frame">
                    <img
                        src="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/assets/pictures/teampagelogin.jpg"
                        alt="Creative Agency Hub admin workspace"
                    >
                </div>
            </div>
        </aside>

        <section class="auth-form-side">
            <div class="auth-form-box">
                <div class="auth-form-title">
                    <h2>Đăng nhập Admin</h2>
                    <p>Chỉ tài khoản Admin mới được truy cập cổng quản trị hệ thống.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="form-alert form-alert-danger">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form
                    method="POST"
                    action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/api/auth/login-admin"
                    data-auth-portal-form
                    data-auth-endpoint="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/api/auth/login-admin"
                    data-auth-redirect="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/admin/dashboard"
                    data-success-message="Đăng nhập thành công. Đang chuyển về Admin Dashboard..."
                >
                    <div class="form-group">
                        <label class="form-label" for="email">Email Admin</label>
                        <div class="input-with-icon">
                            <span class="input-icon">✉</span>
                            <input
                                id="email"
                                class="form-control"
                                type="email"
                                name="email"
                                placeholder="admin@agency.vn"
                                autocomplete="email"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <span>Mật khẩu</span>
                            <a href="#" data-disabled-demo>Quên mật khẩu?</a>
                        </label>

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

                    <div class="form-alert" data-auth-message style="display: none;"></div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <span>Đăng nhập Admin</span>
                        <span>→</span>
                    </button>
                </form>

                <div class="auth-divider">Chuyển cổng đăng nhập</div>

                <div class="auth-social-grid">
                    <a class="btn btn-light" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/app/View/staff/auth/login.php">
                        <span>▦</span>
                        <span>Internal Portal</span>
                    </a>

                    <a class="btn btn-light" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/app/View/client-portal/login-client.php">
                        <span>◇</span>
                        <span>Client Portal</span>
                    </a>
                </div>

                <p class="auth-footer-line">
                    Không có quyền Admin? <a href="#" data-disabled-demo>Liên hệ quản trị hệ thống</a>
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
require __DIR__ . '/../../layouts/auth.php';
?>