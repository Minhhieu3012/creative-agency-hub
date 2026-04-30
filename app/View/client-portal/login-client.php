<!-- Trang số 2 -->
 <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập rồi thì redirect
if (isset($_SESSION['token']) || isset($_SESSION['client_id'])) {
    header('Location: projects.php');
    exit;
}

$error = '';
$email = '';

// Xử lý form POST: gọi API backend /api/auth/login (JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập email và mật khẩu';
    } else {
        $apiUrl = 'http://localhost/creative-agency-hub/public/api/auth/login';
        $payload = json_encode([
            'email' => $email,
            'password' => $password
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($apiUrl, false, $context);

        if ($response === false) {
            $error = 'Lỗi kết nối đến server. Vui lòng thử lại sau.';
        } else {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['data']['token'])) {
                // Lưu token và thông tin user vào session
                $_SESSION['token'] = $data['data']['token'];
                $_SESSION['client_name'] = $data['data']['user']['full_name'] ?? $data['data']['user']['full_name'] ?? 'Khách hàng';
                $_SESSION['client_id'] = $data['data']['user']['id'] ?? null;

                // Nếu người dùng chọn "remember", đặt cookie ngắn (tùy ý)
                if (!empty($_POST['remember'])) {
                    setcookie('cah_remember', $_SESSION['token'], time() + (30 * 24 * 60 * 60), '/');
                }

                header('Location: projects.php');
                exit;
            } else {
                $error = $data['message'] ?? 'Đăng nhập thất bại. Kiểm tra email và mật khẩu.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Client Login - Creative Agency Hub</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "outline-variant": "#bbcac1",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#161c22",
                        "outline": "#6c7a72",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "surface-container-highest": "#dde3eb",
                        "primary-container": "#20c997",
                        "surface-container": "#e9eef6",
                        "surface": "#f7f9ff",
                        "surface-container-low": "#eff4fc",
                        "on-surface-variant": "#3c4a43",
                        "primary": "#006c4f",
                        "on-primary": "#ffffff",
                        "background": "#f7f9ff",
                        "error": "#ba1a1a",
                    },
                    "fontFamily": {
                        "label-bold": ["Inter"], "headline-md": ["Manrope"], "body-sm": ["Inter"],
                        "body-lg": ["Inter"], "headline-lg": ["Manrope"], "label-caps": ["Inter"],
                        "display-xl": ["Manrope"], "body-md": ["Inter"]
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9ff; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .tonal-shadow { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04); }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex items-center justify-center p-0 md:p-8 lg:p-12">
<div class="w-full max-w-7xl bg-surface-container-lowest md:rounded-xl overflow-hidden flex flex-col md:flex-row min-h-[900px] tonal-shadow">
    
    <!-- Login Form Section -->
    <div class="flex-1 flex flex-col px-6 py-10 md:px-12 lg:px-16 justify-between">
        <!-- Header/Logo Area -->
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-on-primary">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">energy_savings_leaf</span>
            </div>
            <h1 class="font-headline-md text-2xl font-bold text-primary tracking-tight">Creative Agency Hub</h1>
        </div>
        
        <!-- Main Form Canvas -->
        <div class="max-w-md w-full mx-auto py-12">
            <header class="mb-8">
                <h2 class="font-headline-lg text-3xl font-bold text-on-surface mb-2">Cổng thông tin Khách hàng</h2>
                <p class="font-body-md text-on-surface-variant">Chào mừng quay trở lại. Vui lòng nhập thông tin để theo dõi tiến độ dự án.</p>
            </header>
            
            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-error-container border border-error rounded-lg">
                <p class="font-body-sm text-on-error-container"><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Form Action -->
            <form action="" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="font-label-bold text-sm font-semibold text-on-surface-variant block" for="email">Email liên hệ</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                        <input class="w-full pl-12 pr-4 py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-on-surface" id="email" name="email" placeholder="client@company.com" required="" type="email" value="<?php echo htmlspecialchars($email); ?>"/>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="font-label-bold text-sm font-semibold text-on-surface-variant block" for="password">Mã truy cập</label>
                        <a class="font-label-bold text-sm font-semibold text-primary hover:underline" href="#">Quên mật khẩu?</a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                        <input class="w-full pl-12 pr-4 py-3 bg-surface-container-low border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all font-body-md text-on-surface" id="password" name="password" placeholder="••••••••" required="" type="password"/>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <input class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" id="remember" name="remember" type="checkbox"/>
                    <label class="font-body-sm text-sm text-on-surface-variant cursor-pointer" for="remember">Ghi nhớ phiên đăng nhập</label>
                </div>
                
                <button class="w-full bg-primary hover:bg-primary-container text-on-primary py-4 rounded-xl font-label-bold font-bold shadow-lg shadow-primary/20 transform active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-4" type="submit">
                    Đăng nhập
                    <span class="material-symbols-outlined">login</span>
                </button>
            </form>
            
            <!-- ĐÃ BỎ PHẦN ĐĂNG NHẬP NỘI BỘ, CHỈ CÒN ĐĂNG KÝ -->
            <div class="mt-8 pt-8 border-t border-surface-container-highest flex flex-col gap-4 text-center">
                <p class="font-body-sm text-on-surface-variant bg-surface-container-low p-4 rounded-lg border border-outline-variant/30">
                    Chưa có tài khoản đối tác? <br>
                    <a class="text-primary font-label-bold font-bold hover:underline inline-block mt-1" href="register-client.php">Đăng ký tài khoản mới</a>
                </p>
            </div>
        </div>
        
        <!-- Footer Meta -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 opacity-60 pb-4">
            <p class="font-label-caps text-xs font-bold uppercase text-on-surface-variant tracking-wider">© 2026 Creative Agency Hub.</p>
            <div class="flex gap-6">
                <a class="font-label-caps text-xs font-bold uppercase text-on-surface-variant tracking-wider hover:text-primary" href="#">Bảo mật</a>
                <a class="font-label-caps text-xs font-bold uppercase text-on-surface-variant tracking-wider hover:text-primary" href="#">Điều khoản</a>
            </div>
        </div>
    </div>
    
    <!-- Illustration Section (Split Screen) -->
    <div class="hidden lg:flex flex-1 relative bg-primary-container overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover grayscale-[20%]" alt="Creative collaboration" src="https://images.unsplash.com/photo-1542744173-8e7e53415bb0?q=80&w=2070&auto=format&fit=crop"/>
            <div class="absolute inset-0 bg-gradient-to-tr from-primary/90 to-primary-container/70 mix-blend-multiply"></div>
        </div>
        
        <div class="relative z-10 p-16 flex flex-col justify-end h-full w-full">
            <div class="bg-white/10 backdrop-blur-md p-8 rounded-2xl border border-white/20 max-w-lg mb-12">
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex -space-x-3">
                        <img class="w-12 h-12 rounded-full border-2 border-primary-container object-cover" alt="Client 1" src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=100&auto=format&fit=crop"/>
                        <img class="w-12 h-12 rounded-full border-2 border-primary-container object-cover" alt="Client 2" src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=100&auto=format&fit=crop"/>
                    </div>
                    <span class="font-label-bold font-semibold text-white">Đồng hành cùng 50+ thương hiệu</span>
                </div>
                <h3 class="font-headline-lg text-4xl font-extrabold text-white mb-4 leading-tight">Minh bạch & <br>Hiệu quả</h3>
                <p class="font-body-md text-white/90 text-lg leading-relaxed">Không gian riêng biệt giúp khách hàng dễ dàng theo dõi tiến độ, xem báo cáo và kiểm soát dự án một cách trực quan nhất.</p>
            </div>
            
            <!-- Decorative Elements -->
            <div class="flex gap-4">
                <div class="px-5 py-2.5 bg-white/20 backdrop-blur-sm rounded-lg border border-white/10 flex items-center gap-2">
                    <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">verified</span>
                    <span class="text-white font-label-bold text-sm font-semibold">Bảo mật dữ liệu</span>
                </div>
                <div class="px-5 py-2.5 bg-white/20 backdrop-blur-sm rounded-lg border border-white/10 flex items-center gap-2">
                    <span class="material-symbols-outlined text-white" style="font-variation-settings: 'FILL' 1;">speed</span>
                    <span class="text-white font-label-bold text-sm font-semibold">Cập nhật Real-time</span>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>