<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/*
 * Fallback để file vẫn chạy được khi mở trực tiếp:
 * http://localhost/creative-agency-hub/app/View/client-portal/login-client.php
 *
 * Nếu hệ thống đã define APP_URL / BASE_PATH rồi thì giữ nguyên, không ghi đè.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$publicUrl = defined('APP_URL') ? APP_URL : ($baseUrl . '/public');

if (!defined('APP_URL')) {
    define('APP_URL', $publicUrl);
}

$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$pageTitle = 'Cổng thông tin Khách hàng | Creative Agency Hub';
$pageCss   = ['auth.css'];
$pageJs    = ['forms.js'];
$brandName = 'Creative Agency Hub';
$bodyClass = 'client-login-body';

$error = $error ?? null;

$clientLoginUrl = $viewUrl . '/client-portal/login-client.php';
$clientProjectsUrl = $viewUrl . '/client-portal/projects.php';
$internalLoginUrl = APP_URL . '/auth/login.php';
$clientLoginApiUrl = APP_URL . '/api/auth/login-client';
$customerLoginImage = APP_URL . '/assets/pictures/customerpagelogin.jpg';

ob_start();
?>

<section class="client-login-wrapper">
    <div class="client-login-card">

        <!-- ===== FORM SIDE (left) ===== -->
        <section class="client-login-form">

            <a href="<?php echo htmlspecialchars($clientLoginUrl); ?>" class="client-login-brand">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <div class="client-login-title">
                <h1>Cổng thông tin<br>Khách hàng</h1>
                <p>Theo dõi tiến độ dự án, xem báo cáo và gửi phản hồi trực tiếp tới đội ngũ sáng tạo của chúng tôi.</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="form-alert form-alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div id="client-login-message" class="form-alert" style="display: none;"></div>

            <form
                id="clientLoginForm"
                method="POST"
                action="<?php echo htmlspecialchars($clientLoginApiUrl); ?>"
                data-ui-form
                data-mock-submit="false"
                data-success-message="Đăng nhập thành công!"
                data-redirect="<?php echo htmlspecialchars($clientProjectsUrl); ?>"
            >

                <div class="form-group">
                    <label class="form-label" for="client-email">Email</label>
                    <div class="input-with-icon">
                        <span class="input-icon">✉</span>
                        <input
                            id="client-email"
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
                        <label class="form-label" for="client-password">Mật khẩu</label>
                        <a class="form-label-link" href="#">Quên mật khẩu?</a>
                    </div>
                    <div class="input-with-icon">
                        <span class="input-icon">▣</span>
                        <input
                            id="client-password"
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
                            data-password-toggle="#client-password"
                            aria-label="Hiện/ẩn mật khẩu"
                        >👁</button>
                    </div>
                </div>

                <label class="checkbox-line">
                    <input type="checkbox" name="remember">
                    <span>Ghi nhớ đăng nhập</span>
                </label>

                <button type="submit" class="btn btn-primary auth-submit" id="clientLoginButton">
                    <span>Truy cập cổng thông tin</span>
                    <span>→</span>
                </button>
            </form>

            <div class="auth-divider">Hoặc tiếp tục với</div>

            <div class="auth-social-grid">
                <button class="btn btn-light" type="button">
                    <span>G</span>
                    <span>Google</span>
                </button>
                <button class="btn btn-light" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 21 21"
                        aria-hidden="true">
                        <rect x="1" y="1" width="9" height="9" fill="#f25022" />
                        <rect x="11" y="1" width="9" height="9" fill="#7fba00" />
                        <rect x="1" y="11" width="9" height="9" fill="#00a4ef" />
                        <rect x="11" y="11" width="9" height="9" fill="#ffb900" />
                    </svg>
                    <span>Microsoft</span>
                </button>
            </div>

            <p class="auth-footer-line">
                Chưa có tài khoản? <a href="#">Liên hệ với chúng tôi</a>
            </p>

            <p class="auth-footer-line" style="margin-top: 10px;">
                Bạn là nhân sự nội bộ? <a href="<?php echo htmlspecialchars($internalLoginUrl); ?>">Đăng nhập nội bộ</a>
            </p>

            <div class="auth-legal">
                <span>© 2026 Creative Agency Hub</span>
                <span>
                    <a href="#">Bảo mật</a>
                    &nbsp;&nbsp;
                    <a href="#">Điều khoản</a>
                </span>
            </div>
        </section>

        <!-- ===== VISUAL SIDE (right) ===== -->
        <aside class="client-login-visual">

            <img
                class="client-login-visual-image"
                src="<?php echo htmlspecialchars($customerLoginImage); ?>"
                alt="Creative Agency Hub client portal"
            >

            <div class="client-glass-panel">
                <div class="client-trust-line">
                    <span class="brand-mark">CA</span>
                    <span>Creative Agency Hub</span>
                </div>

                <h2>Dự án của bạn,<br>minh bạch từng bước.</h2>
                <p>
                    Xem tiến độ thực tế, nhận báo cáo định kỳ và trao đổi trực tiếp
                    với đội ngũ — tất cả trong một cổng thông tin dành riêng cho bạn.
                </p>

                <div class="client-pill-row">
                    <span class="client-pill">
                        <span>📊</span>
                        <span>Tiến độ thực tế</span>
                    </span>
                    <span class="client-pill">
                        <span>💬</span>
                        <span>Phản hồi trực tiếp</span>
                    </span>
                    <span class="client-pill">
                        <span>📁</span>
                        <span>Quản lý tài liệu</span>
                    </span>
                </div>
            </div>
        </aside>

    </div>
</section>

<script>
(function () {
    "use strict";

    const form = document.getElementById("clientLoginForm");
    const button = document.getElementById("clientLoginButton");
    const messageBox = document.getElementById("client-login-message");

    if (!form || !button || !messageBox) {
        return;
    }

    const loginApiUrl = <?php echo json_encode($clientLoginApiUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const redirectUrl = <?php echo json_encode($clientProjectsUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    function showMessage(type, message) {
        messageBox.style.display = "block";
        messageBox.className = "form-alert " + (type === "success" ? "form-alert-success" : "form-alert-danger");
        messageBox.textContent = message;
    }

    function resetButton() {
        button.disabled = false;
        button.innerHTML = "<span>Truy cập cổng thông tin</span><span>→</span>";
    }

    /*
     * Dùng capture phase để chặn forms.js xử lý nhầm form client.
     * Nhờ vậy vẫn giữ được data-ui-form/class cũ nhưng login client chạy theo API riêng.
     */
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const emailInput = document.getElementById("client-email");
        const passwordInput = document.getElementById("client-password");

        const email = emailInput ? emailInput.value.trim() : "";
        const password = passwordInput ? passwordInput.value : "";

        if (!email || !password) {
            showMessage("error", "Vui lòng nhập email và mật khẩu.");
            return;
        }

        button.disabled = true;
        button.innerHTML = "<span>Đang đăng nhập...</span><span>⏳</span>";

        try {
            const response = await fetch(loginApiUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const contentType = response.headers.get("content-type") || "";
            const result = contentType.includes("application/json")
                ? await response.json()
                : { status: "error", message: await response.text() };

            if (!response.ok || result.status !== "success") {
                throw new Error(result.message || "Đăng nhập client thất bại.");
            }

            const token = result.token || (result.data && result.data.token);
            const user = result.user || (result.data && result.data.user);

            if (!token) {
                throw new Error("API đăng nhập chưa trả về token.");
            }

            if (!user || String(user.role || "").toLowerCase() !== "client") {
                throw new Error("Tài khoản này không phải tài khoản khách hàng.");
            }

            localStorage.setItem("cah_token", token);
            localStorage.setItem("cah_auth_token", token);
            localStorage.setItem("cah_user", JSON.stringify(user));
            localStorage.setItem("cah_user_role", "client");

            showMessage("success", "Đăng nhập thành công. Đang chuyển sang Client Portal...");

            setTimeout(function () {
                window.location.href = redirectUrl;
            }, 550);
        } catch (error) {
            showMessage("error", error.message || "Không thể đăng nhập Client Portal.");
            resetButton();
        }
    }, true);

    document.addEventListener("click", function (event) {
        const toggle = event.target.closest("[data-password-toggle]");

        if (!toggle) {
            return;
        }

        const targetSelector = toggle.getAttribute("data-password-toggle");
        const input = document.querySelector(targetSelector);

        if (!input) {
            return;
        }

        input.type = input.type === "password" ? "text" : "password";
    });
})();
</script>

<?php
$content = ob_get_clean();

$authLayout = BASE_PATH . '/app/View/layouts/auth.php';

if (file_exists($authLayout)) {
    require $authLayout;
} else {
    echo $content;
}
?>