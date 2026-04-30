<?php
$pageTitle = 'Cổng thông tin Khách hàng | Creative Agency Hub';
$pageCss   = ['auth.css'];
$pageJs    = ['forms.js'];
$bodyClass = 'client-login-body';

$error = $error ?? null;
ob_start();
?>

<section class="client-login-wrapper">
    <div class="client-login-card">
        <section class="client-login-form">
            <a href="<?php echo APP_URL; ?>/client-portal/login-client.php" class="client-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <div class="client-login-title">
                <h1>Cổng thông tin Khách hàng</h1>
                <p>Theo dõi tiến độ dự án và gửi phản hồi trực tiếp.</p>
            </div>

            <form
                method="POST"
                action="<?php echo APP_URL; ?>/api/auth/login"
                data-ui-form
                data-mock-submit="false"
                data-success-message="Đăng nhập thành công!"
                data-redirect="<?php echo APP_URL; ?>/client-portal/projects.php"
            >
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu</label>
                    <input class="form-control" type="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    <span>Đăng nhập hệ thống</span> →
                </button>
            </form>
        </section>

        <aside class="client-login-visual">
            <img src="<?php echo APP_URL; ?>/assets/pictures/customerpagelogin.jpg" alt="Visual">
        </aside>
    </div>
</section>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>