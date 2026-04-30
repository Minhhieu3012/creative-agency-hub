<?php
$pageTitle = 'Quên mật khẩu | Creative Agency Hub';
$pageCss   = ['auth.css'];
$pageJs    = ['forms.js'];
$bodyClass = 'client-login-body';

ob_start();
?>
<section class="client-login-wrapper">
    <div class="client-login-card client-single-card">

        <section class="client-login-form form-reset-padding">
            <a href="<?php echo APP_URL; ?>/client-portal/login-client" class="client-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <div class="client-login-title">
                <h1>Khôi phục mật khẩu</h1>
                <p>Nhập email bạn đã đăng ký. chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu trong giây lát.</p>
            </div>

            <form method="POST" action="<?php echo APP_URL; ?>/api/auth/forgot-password" data-ui-form>
                <div class="form-group">
                    <label class="form-label" for="email">Email đăng ký</label>
                    <div class="input-with-icon">
                        <span class="input-icon">✉</span>
                        <input id="email" class="form-control" type="email" name="email" placeholder="name@company.com"
                            required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary auth-submit" style="margin-top: 12px;">
                    <span>Gửi yêu cầu khôi phục</span>
                    <span>→</span>
                </button>
            </form>

            <p class="auth-footer-line" style="margin-top: 24px; margin-bottom: 0;">
                Nhớ ra mật khẩu? <a href="<?php echo APP_URL; ?>/client-portal/login-client">Quay lại đăng nhập</a>
            </p>
        </section>
    </div>
</section>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>