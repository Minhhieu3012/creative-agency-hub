<?php
$pageTitle = 'Chính sách bảo mật | Creative Agency Hub';
$pageCss   = ['auth.css']; 
$bodyClass = 'client-login-body';
ob_start();
?>
<section class="client-login-wrapper">
    <div class="client-login-card" style="grid-template-columns: 1fr; padding: 40px;">
        <div style="max-width: 800px; margin: 0 auto; width: 100%;">
            <a href="javascript:history.back()"
                style="color: var(--primary); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <span>←</span> Quay lại trang trước
            </a>

            <h1 style="margin-top: 24px; color: var(--text);">Chính sách bảo mật</h1>
            <p style="color: var(--text-soft); line-height: 1.8; margin-top: 16px;">
                Tại Creative Agency Hub, chúng tôi coi trọng việc bảo vệ dữ liệu dự án và thông tin cá nhân của bạn.
                Dữ liệu của bạn được mã hóa và chỉ được sử dụng cho mục đích quản lý dự án nội bộ.
                <br><br>
                (Bạn có thể bổ sung nội dung văn bản pháp lý chi tiết của công ty tại đây...)
            </p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/auth.php';
?>