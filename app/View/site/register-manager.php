<?php
$pageTitle = 'Đăng ký Manager | Creative Agency Hub';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $assetUrl ?? ($baseUrl . '/public/assets');
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');
$apiUrl = $baseUrl . '/public/api/auth/register-manager';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/css/site.css?v=<?php echo time(); ?>">
</head>
<body class="site-body site-register-body">
    <div class="site-orb site-orb-one"></div>
    <div class="site-orb site-orb-two"></div>

    <header class="site-header">
        <a class="site-brand" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/site/index.php">
            <span class="site-brand-mark">CA</span>
            <span>Creative Agency Hub</span>
        </a>

        <div class="site-header-actions">
            <a class="site-btn site-btn-light" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/auth/login.php">
                Đăng nhập
            </a>
        </div>
    </header>

    <main class="site-register-shell">
        <section class="site-register-copy">
            <span class="site-kicker">MANAGER ACCESS</span>
            <h1>Tạo tài khoản quản lý workspace.</h1>
            <p>
                Tài khoản Manager của bạn sẽ được gửi tới Admin để duyệt. Sau khi kích hoạt,
                bạn có thể đăng nhập, tạo project, thêm nhân sự, thêm client và vận hành toàn bộ luồng sản xuất.
            </p>

            <div class="site-mini-flow">
                <div>
                    <strong>01</strong>
                    <span>Gửi thông tin</span>
                </div>
                <div>
                    <strong>02</strong>
                    <span>Admin duyệt</span>
                </div>
                <div>
                    <strong>03</strong>
                    <span>Đăng nhập làm việc</span>
                </div>
            </div>
        </section>

        <section class="site-register-card">
            <div class="site-form-head">
                <h2>Đăng ký Manager</h2>
                <p>Điền thông tin để tạo tài khoản chờ duyệt.</p>
            </div>

            <div class="site-alert" data-landing-alert style="display:none;"></div>

            <form
                class="site-form"
                data-manager-register-form
                data-api-url="<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>"
            >
                <div class="site-form-grid">
                    <label class="site-field">
                        <span>Họ và tên</span>
                        <input type="text" name="full_name" placeholder="Nguyễn Văn A" required>
                    </label>

                    <label class="site-field">
                        <span>Công ty / đội nhóm</span>
                        <input type="text" name="company_name" placeholder="Creative Team, Agency..." required>
                    </label>
                </div>

                <label class="site-field">
                    <span>Email đăng nhập</span>
                    <input type="email" name="email" placeholder="manager@company.com" required>
                </label>

                <label class="site-field">
                    <span>Số điện thoại</span>
                    <input type="tel" name="phone" placeholder="090..." autocomplete="tel">
                </label>

                <div class="site-form-grid">
                    <label class="site-field">
                        <span>Mật khẩu</span>
                        <input type="password" name="password" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                    </label>

                    <label class="site-field">
                        <span>Xác nhận mật khẩu</span>
                        <input type="password" name="password_confirm" placeholder="Nhập lại mật khẩu" minlength="6" required>
                    </label>
                </div>

                <label class="site-field">
                    <span>Ghi chú</span>
                    <textarea name="note" rows="4" placeholder="Nhu cầu quản lý, quy mô team, số lượng project..."></textarea>
                </label>

                <label class="site-checkline">
                    <input type="checkbox" required>
                    <span>Tôi xác nhận thông tin đăng ký là chính xác.</span>
                </label>

                <button class="site-btn site-btn-primary site-btn-xl site-btn-full" type="submit">
                    Gửi đăng ký chờ duyệt
                </button>

                <p class="site-form-foot">
                    Đã có tài khoản?
                    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/auth/login.php">
                        Đăng nhập tại đây
                    </a>
                </p>
            </form>
        </section>
    </main>

    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/landing.js?v=<?php echo time(); ?>"></script>
</body>
</html>