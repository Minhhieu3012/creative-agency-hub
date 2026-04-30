<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Cổng đăng nhập nội bộ Creative Agency Hub" />
    <title>Đăng nhập — Creative Agency Hub</title>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet" />

    <!-- Nhúng CSS chuẩn hóa -->
    <link rel="stylesheet" href="/creative-agency-hub/public/assets/css/login.css" />
</head>

<body>
    <main class="login-card" role="main">
        <!-- ── Form Panel ── -->
        <section class="form-panel" aria-label="Form đăng nhập">
            <div class="brand">
                <div class="brand-icon" aria-hidden="true">
                    <span class="material-symbols-outlined">hub</span>
                </div>
                <span class="brand-name">Creative Agency Hub</span>
            </div>

            <div class="form-body">
                <header>
                    <h1 class="form-title">Cổng Đăng nhập<br>Nội bộ</h1>
                    <p class="form-subtitle">
                        Chào mừng quay trở lại. Vui lòng xác thực danh tính
                        <strong>(Admin, Manager, Employee)</strong> để truy cập không gian làm việc.
                    </p>
                </header>

                <form id="loginForm" novalidate>
                    <!-- Email -->
                    <div class="field">
                        <label class="field-label" for="email">Địa chỉ Email</label>
                        <div class="input-wrap">
                            <input class="text-input" id="email" name="email" type="email"
                                placeholder="email@creativeagency.com" autocomplete="email" required
                                aria-describedby="emailError" />
                            <span class="input-icon material-symbols-outlined" aria-hidden="true">mail</span>
                        </div>
                        <span class="field-error" id="emailError" role="alert" aria-live="polite">
                            <span class="material-symbols-outlined" style="font-size:14px">error</span>
                            <span id="emailMsg"></span>
                        </span>
                    </div>

                    <!-- Password -->
                    <div class="field">
                        <div class="field-row">
                            <label class="field-label" for="password">Mật khẩu</label>
                            <a class="forgot-link" href="#">Quên mật khẩu?</a>
                        </div>
                        <div class="input-wrap">
                            <input class="text-input has-toggle" id="password" name="password" type="password"
                                placeholder="••••••••" autocomplete="current-password" required
                                aria-describedby="passError" />
                            <span class="input-icon material-symbols-outlined" aria-hidden="true">lock</span>
                            <button type="button" id="togglePassword" class="input-toggle"
                                aria-label="Hiện/ẩn mật khẩu">
                                <span class="material-symbols-outlined" id="toggleIcon">visibility</span>
                            </button>
                        </div>
                        <span class="field-error" id="passError" role="alert" aria-live="polite">
                            <span class="material-symbols-outlined" style="font-size:14px">error</span>
                            <span id="passMsg"></span>
                        </span>
                    </div>

                    <!-- Remember -->
                    <div class="remember-row field">
                        <input class="checkbox" id="remember" name="remember" type="checkbox" />
                        <label class="remember-label" for="remember">Ghi nhớ đăng nhập trên thiết bị này</label>
                    </div>

                    <!-- Toast -->
                    <div id="toast" class="toast" role="alert" aria-live="assertive"></div>

                    <!-- Submit -->
                    <button type="submit" id="btnSubmit" class="btn-submit">
                        <span class="btn-text">Đăng nhập hệ thống</span>
                        <span class="material-symbols-outlined btn-icon" aria-hidden="true">login</span>
                        <div class="spinner" aria-hidden="true"></div>
                    </button>
                </form>

                <div class="form-footer">
                    Bạn gặp sự cố?
                    <a href="#">Liên hệ IT Support</a>
                </div>
            </div>

            <footer class="panel-footer">
                <span>© 2026 Creative Agency Hub</span>
                <nav class="panel-footer-links">
                    <a href="#">Quy định nội bộ</a>
                    <a href="#">Bảo mật thông tin</a>
                </nav>
            </footer>
        </section>

        <!-- ── Visual Panel ── -->
        <aside class="visual-panel" aria-hidden="true">
            <img class="hero-img" loading="lazy" alt=""
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCw9aOEiPjv2Ma43qFE2_zNFVH7uLEkN0405YXz4jVlmQ6ClVTo0zVqq__9ipLsFqAmuhGoYEUrvQK2Y5h15rrRSmnAdjg1aj1xRTpMaHGnbmagWVBwL5xfGN3OjGl0K45Mt2CR2K__ozM9JHjunneekgWNmGTHJEfikqqlNiNu2KVVavxuHekqQxgnzPkqOLkCoLX9y1WdWdU5gtIhDY4YxgbxOseTiRSqzMKHIZyR2rCUCFclLn8xAwd-wjlPRP1MQWOf00RWvULc" />
            <div class="visual-overlay"></div>

            <div class="deco-dots" aria-hidden="true">
                <?php for ($i = 0; $i < 25; $i++): ?><span></span><?php endfor; ?>
            </div>

            <div class="visual-content">
                <div class="visual-card">
                    <div class="visual-avatars">
                        <div class="avatar-stack">
                            <img alt=""
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuDqPe2_gqFAdBi0X14cbGsRsu5vnj08dq2KvZJElWk1RQ6MMOCVxihcokNWb411TgN4ZLLSDmpPE8wPZ4Isudph1FBDQXLiU_VoxuPH9_B-KQbF73JISn1yv0y0mOSQymelUkgCFlKKZoqGpjwbwctHn_JV0Y170mL9G8DSI9o9XStu9lIMaTnp-J2PWEinfcJ1OGjqctHyZN84YWsYPf6u0cS1lP6uWZeuWXqqxmkBnWX7UHgBk4KHJOXX_7xOrxywu_1b-3UcbyMi" />
                            <img alt=""
                                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBr-Y_e66qjyg-9RZSXGjIHkCxzh6Sx-vBLPpVrfRwWidm8ObBsoQFxp3SRfj9z93Zp2PP2QAq1EG2f-8NxQZhoN6wgmGJhRJGJakP7tqAvzFvFYLC2BS04GqPB8irgx5PEEE5ZrFKmNq4tf_WaL2QKyk_BzqtzSe0sl06WTUVmmByfYsOHqeNOGJ64PseIkXngEJ7jOcELP9G3hnkxva52f6AoJ9nqED03DHZ7l3dlzHMSOF39WsmcWxjeMBZHA0pTsdhaHwDhx-bz" />
                        </div>
                        <span class="avatar-label">Quản trị nguồn nhân lực hiệu quả</span>
                    </div>
                    <h2 class="visual-title">Creative Agency Hub</h2>
                    <p class="visual-desc">
                        Trải nghiệm hệ thống quản lý chuyên nghiệp, giúp tối ưu hoá quy trình làm việc
                        và thúc đẩy sự gắn kết trong tổ chức của bạn.
                    </p>
                </div>
                <div class="visual-badges">
                    <div class="badge"><span class="material-symbols-outlined">verified</span>Bảo mật Nội bộ</div>
                    <div class="badge"><span class="material-symbols-outlined">speed</span>Tốc độ tối ưu</div>
                </div>
            </div>
        </aside>
    </main>

    <!-- Nhúng Javascript xử lý Logic -->
    <script src="/creative-agency-hub/public/assets/js/login.js"></script>
</body>

</html>