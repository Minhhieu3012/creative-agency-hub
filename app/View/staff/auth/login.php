<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Staff Login | Creative Agency Hub';
$baseUrl = '/creative-agency-hub';
$assetUrl = $baseUrl . '/public/assets';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/reset.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/auth.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/components.css?v=<?php echo time(); ?>">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-visual">
            <div class="auth-brand">
                <span class="brand-mark">CA</span>
                <div>
                    <strong>Creative Agency Hub</strong>
                    <small>Staff Workspace</small>
                </div>
            </div>

            <div class="auth-hero-copy">
                <span class="auth-eyebrow">MANAGER / EMPLOYEE</span>
                <h1>Không gian vận hành dự án.</h1>
                <p>
                    Manager quản lý project, task và nhân sự. Employee theo dõi công việc,
                    cập nhật tiến độ, chấm công và gửi nghỉ phép.
                </p>
            </div>
        </section>

        <section class="auth-panel">
            <form class="auth-card" id="staffLoginForm">
                <div class="auth-card-head">
                    <span class="auth-eyebrow">STAFF PORTAL</span>
                    <h2>Đăng nhập Staff</h2>
                    <p>Chỉ Manager và Employee được truy cập cổng này.</p>
                </div>

                <div class="form-group">
                    <label for="email">Email công việc</label>
                    <input id="email" name="email" type="email" placeholder="staff@example.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <input id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <div class="form-alert" id="loginMessage" style="display: none;"></div>

                <button class="btn btn-primary btn-block" type="submit">
                    Đăng nhập Staff
                </button>

                <div class="auth-switch">
                    <span>Bạn là Admin?</span>
                    <a href="/creative-agency-hub/app/View/admin/auth/login.php">Vào cổng Admin</a>
                </div>

                <div class="auth-switch">
                    <span>Khách hàng?</span>
                    <a href="/creative-agency-hub/app/View/client-portal/login-client.php">Vào Client Portal</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        const form = document.getElementById('staffLoginForm');
        const message = document.getElementById('loginMessage');

        function showMessage(type, text) {
            message.style.display = 'block';
            message.className = 'form-alert ' + (type === 'success' ? 'form-alert-success' : 'form-alert-danger');
            message.textContent = text;
        }

        function clearAuthStorage() {
            localStorage.removeItem('cah_auth_token');
            localStorage.removeItem('cah_auth_user');
            localStorage.removeItem('cah_token');
            localStorage.removeItem('cah_user');
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);

            button.disabled = true;
            button.textContent = 'Đang đăng nhập...';
            message.style.display = 'none';

            clearAuthStorage();

            try {
                const response = await fetch('/creative-agency-hub/public/api/auth/login-staff', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        email: String(formData.get('email') || '').trim(),
                        password: String(formData.get('password') || '')
                    })
                });

                const result = await response.json();

                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Đăng nhập thất bại.');
                }

                const token = result.data?.token || '';
                const user = result.data?.user || null;

                if (!token || !user) {
                    throw new Error('Server không trả đủ token hoặc thông tin tài khoản.');
                }

                localStorage.setItem('cah_auth_token', token);
                localStorage.setItem('cah_token', token);
                localStorage.setItem('cah_auth_user', JSON.stringify(user));
                localStorage.setItem('cah_user', JSON.stringify(user));

                showMessage('success', 'Đăng nhập thành công. Đang chuyển đến Staff Dashboard...');

                window.setTimeout(() => {
                    window.location.href = '/creative-agency-hub/public/staff/dashboard';
                }, 450);
            } catch (error) {
                showMessage('error', error.message);
            } finally {
                button.disabled = false;
                button.textContent = 'Đăng nhập Staff';
            }
        });
    </script>
</body>
</html>