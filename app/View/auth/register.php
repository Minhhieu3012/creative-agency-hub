<?php
$pageTitle = 'Đăng ký nhân viên | Creative Agency Hub';
$pageCss = ['auth.css'];
$pageJs = ['forms.js'];
$brandName = 'Creative Agency Hub';

// Compute baseUrl before using it
$baseUrl = $baseUrl ?? (function () {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (strpos($scriptName, '/public/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/public'));
    }

    if (strpos($scriptName, '/app/View/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/app/View'));
    }

    $dir = dirname($scriptName);
    return $dir === '/' ? '' : $dir;
})();

require_once __DIR__ . '/../../../config/db_connect.php';

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
        <h1>Tham gia đội ngũ Creative Agency.</h1>
        <p>
          Đăng ký tài khoản của bạn để truy cập hệ thống quản lý đội ngũ, dự án, công việc
          và các công cụ hỗ trợ chuyên nghiệp.
        </p>
      </div>

      <div class="auth-preview-card">
        <div class="auth-preview-image-frame">
          <img src="<?php echo $baseUrl; ?>/public/assets/pictures/teampagelogin.jpg"
            alt="Creative Agency Hub team workspace">
        </div>
      </div>
    </aside>

    <section class="auth-form-side">
      <div class="auth-form-box">
        <div class="auth-form-title">
          <h2>Tạo tài khoản mới</h2>
          <p>Đăng ký thông tin cơ bản. Quản trị viên sẽ duyệt và gán phòng ban/chức vụ cho bạn.</p>
        </div>

        <?php if (!empty($error)): ?>
        <div class="form-alert form-alert-danger">
          <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo $baseUrl; ?>/public/api/auth/register" data-ui-form
          data-success-message="Đăng ký thành công! Chờ quản trị viên duyệt tài khoản của bạn."
          data-redirect="./login.php">
          <div class="form-group">
            <label class="form-label" for="reg_full_name">Họ và tên <span style="color: red;">*</span></label>
            <div class="input-with-icon">
              <span class="input-icon">👤</span>
              <input id="reg_full_name" class="form-control" type="text" name="full_name" placeholder="Nguyễn Văn A"
                required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="reg_email">Email công ty <span style="color: red;">*</span></label>
            <div class="input-with-icon">
              <span class="input-icon">✉</span>
              <input id="reg_email" class="form-control" type="email" name="email" placeholder="name@company.com"
                autocomplete="email" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="reg_password">Mật khẩu <span style="color: red;">*</span></label>
            <div class="input-with-icon">
              <span class="input-icon">▣</span>
              <input id="reg_password" class="form-control" type="password" name="password" placeholder="••••••••"
                autocomplete="new-password" required>
              <button type="button" class="password-eye" data-password-toggle="#reg_password"
                aria-label="Hiện/ẩn mật khẩu">👁</button>
            </div>
            <small style="color: #666; margin-top: 5px; display: block;">Ít nhất 6 ký tự</small>
          </div>

          <button type="submit" class="btn btn-primary auth-submit">
            <span>Hoàn tất đăng ký</span>
            <span>→</span>
          </button>
        </form>

        <div
          style="background: #f0f8ff; border-left: 4px solid #0066cc; padding: 12px; border-radius: 4px; margin-top: 16px;">
          <p style="margin: 0; font-size: 13px; color: #333;">
            <strong>📋 Lưu ý:</strong> Tài khoản của bạn sẽ được quản trị viên duyệt trong thời gian sớm nhất.
            Sau khi duyệt, mã nhân viên sẽ được tự động sinh và phòng ban/chức vụ sẽ được gán cho bạn.
          </p>
        </div>

        <p class="auth-footer-line">
          Đã có tài khoản? <a href="./login.php" class="text-primary">Đăng nhập ngay</a>
        </p>

        <div class="auth-legal">
          <span>© 2026 Creative Agency Hub</span>
          <span>
            <a href="#">Bảo mật</a>
            &nbsp;&nbsp;
            <a href="#">Điều khoản</a>
          </span>
        </div>
      </div>
    </section>
  </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';
?>