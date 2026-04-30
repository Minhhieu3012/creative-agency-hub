<?php
$pageTitle = 'Liên hệ với chúng tôi | Creative Agency Hub';
$pageCss   = ['auth.css'];
$pageJs    = ['forms.js'];
$bodyClass = 'client-login-body';

ob_start();
?>
<section class="client-login-wrapper">
    <div class="client-login-card">
        <section class="client-login-form">
            <div class="client-login-title">
                <h1>Liên hệ hỗ trợ</h1>
                <p>Gửi tin nhắn cho chúng tôi nếu bạn cần hỗ trợ kỹ thuật hoặc tư vấn dịch vụ.</p>
            </div>

            <form method="POST" action="/api/contact/send" data-ui-form>
                <div class="form-group">
                    <label class="form-label">Họ và tên</label>
                    <input class="form-control" type="text" name="name" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="email" placeholder="email@example.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tin nhắn</label>
                    <textarea class="form-control" name="message" rows="4" placeholder="Nội dung cần hỗ trợ..."
                        required></textarea>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    <span>Gửi tin nhắn ngay</span>
                </button>

                <p class="auth-footer-line">
                    <a href="login-client.php">Quay lại đăng nhập</a>
                </p>
            </form>
        </section>

        <aside class="client-login-visual">
            <img class="client-login-visual-image" src="<?php echo APP_URL; ?>/assets/pictures/contact.jpg"
                alt="Contact Visual">
            <div class="client-glass-panel">
                <h2>Chúng tôi luôn lắng nghe.</h2>
                <p>Đội ngũ sáng tạo của Creative Agency Hub sẽ phản hồi bạn trong vòng 24 giờ làm việc.</p>
            </div>
        </aside>
    </div>
</section>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>