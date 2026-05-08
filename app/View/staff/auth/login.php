<?php
$pageTitle = 'Đăng nhập nội bộ | Creative Agency Hub';
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
                <h1>Không gian vận hành dự án.</h1>
                <p>
                    Manager quản lý project, task và nhân sự. Employee theo dõi công việc,
                    cập nhật tiến độ, chấm công và gửi nghỉ phép trong một luồng nội bộ rõ ràng.
                </p>
            </div>

            <div class="auth-preview-card">
                <div class="auth-preview-image-frame">
                    <img
                        src="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/assets/pictures/teampagelogin.jpg"
                        alt="Creative Agency Hub internal workspace"
                    >
                </div>
            </div>
        </aside>

        <section class="auth-form-side">
            <div class="auth-form-box">
                <div class="auth-form-title">
                    <h2>Đăng nhập nội bộ</h2>
                    <p>Dành cho Quản lý và Nhân viên truy cập workspace vận hành dự án.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="form-alert form-alert-danger">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form
                    method="POST"
                    action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/api/auth/login-staff"
                    data-auth-portal-form
                    data-auth-endpoint="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/api/auth/login-staff"
                    data-auth-redirect="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/staff/dashboard"
                    data-success-message="Đăng nhập thành công. Đang chuyển về Dashboard..."
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
                                placeholder="manager@agency.vn"
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
                        <span>Đăng nhập nội bộ</span>
                        <span>→</span>
                    </button>
                </form>

                <div class="auth-divider">Chuyển cổng đăng nhập</div>

                <div class="auth-social-grid">
                    <a class="btn btn-light" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/app/View/admin/auth/login.php">
                        <span>◆</span>
                        <span>Admin Portal</span>
                    </a>

                    <a class="btn btn-light" href="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/app/View/client-portal/login-client.php">
                        <span>◇</span>
                        <span>Client Portal</span>
                    </a>
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
require __DIR__ . '/../../layouts/auth.php';
?>