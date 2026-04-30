<?php
$pageTitle = 'Điều khoản sử dụng | Creative Agency Hub';
$pageCss   = ['auth.css']; 
$bodyClass = 'client-login-body';
ob_start();
?>
<section class="client-login-wrapper client-document-wrapper">

    <div class="client-login-card client-document-card">
        <div style="width: 100%;">
            <a href="javascript:history.back()"
                style="color: var(--primary); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <span>←</span> Quay lại trang trước
            </a>

            <h1
                style="margin-top: 24px; margin-bottom: 8px; color: var(--text); font-size: clamp(28px, 4vw, 36px); letter-spacing: -0.04em;">
                Điều khoản sử dụng</h1>

            <div style="color: var(--text-soft); line-height: 1.8; font-size: 16px;">
                <p>Tại Creative Agency Hub, chúng tôi cam kết bảo vệ quyền riêng tư và dữ liệu cá nhân của bạn. Khi sử
                    dụng hệ thống của chúng tôi, bạn đồng ý với các điều khoản và chính sách dưới đây:</p>

                <h3 style="color: var(--text); margin-top: 24px; font-size: 18px;">1. Thu thập thông tin</h3>
                <p>Chúng tôi có thể thu thập các thông tin như: họ tên, email, thông tin đăng nhập, và dữ liệu liên quan
                    đến dự án mà bạn cung cấp trong quá trình sử dụng hệ thống.</p>

                <h3 style="color: var(--text); margin-top: 24px; font-size: 18px;">2. Mục đích sử dụng</h3>
                <p>Thông tin của bạn được sử dụng nhằm:</p>
                <ul style="padding-left: 20px; margin-top: 8px;">
                    <li>Quản lý và vận hành hệ thống dự án</li>
                    <li>Cải thiện trải nghiệm người dùng</li>
                    <li>Hỗ trợ và chăm sóc khách hàng</li>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>