<?php
session_start();
if (isset($_SESSION['client_id'])) {
    header("Location: projects.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Đăng ký Đối tác - Creative Agency Hub</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                  "primary": "#266a51", "primary-container": "#418369", "on-primary": "#ffffff",
                  "surface": "#f7faf6", "on-surface": "#181c1a", "surface-container-lowest": "#ffffff",
                  "outline": "#707973", "background": "#f7faf6", "on-background": "#181c1a",
                  "error": "#ba1a1a"
              },
              fontFamily: { "h1": ["Manrope"], "body": ["Inter"] }
            }
          }
        }
    </script>
</head>
<body class="bg-background text-on-background font-body min-h-screen flex items-center justify-center p-4 lg:p-8">

    <div class="max-w-6xl w-full mx-auto bg-surface-container-lowest rounded-[24px] shadow-[0_8px_32px_rgba(38,106,81,0.08)] border border-outline/10 flex overflow-hidden min-h-[650px]">
        
        <!-- Form Section -->
        <div class="w-full lg:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-surface-container-lowest">
            <div class="mb-8">
                <div class="w-12 h-12 bg-[#acf1d2] rounded-xl flex items-center justify-center text-primary-container mb-6">
                    <span class="material-symbols-outlined text-[28px]">handshake</span>
                </div>
                <h2 class="text-3xl font-h1 font-bold text-on-background tracking-tight">Trở thành Đối tác</h2>
                <p class="text-outline mt-2 text-sm">Khởi tạo tài khoản để bắt đầu quy trình làm việc chuyên nghiệp cùng Creative Agency Hub.</p>
            </div>

            <!-- Nhớ đổi action trỏ về file xử lý insert DB của ông nhé -->
            <form action="process_register.php" method="POST" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Họ và tên <span class="text-error">*</span></label>
                        <input type="text" name="fullname" required placeholder="Ví dụ: Nguyễn Văn A" 
                               class="w-full px-4 py-3 rounded-lg border border-outline/30 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm bg-surface">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Tên doanh nghiệp</label>
                        <input type="text" name="company" placeholder="Tên công ty (Tùy chọn)" 
                               class="w-full px-4 py-3 rounded-lg border border-outline/30 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm bg-surface">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-on-surface mb-2">Email liên hệ <span class="text-error">*</span></label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">mail</span>
                        <input type="email" name="email" required placeholder="client@company.com" 
                               class="w-full pl-10 pr-4 py-3 rounded-lg border border-outline/30 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm bg-surface">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Mật khẩu <span class="text-error">*</span></label>
                        <input type="password" name="password" required placeholder="••••••••" 
                               class="w-full px-4 py-3 rounded-lg border border-outline/30 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm bg-surface">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-on-surface mb-2">Xác nhận mật khẩu <span class="text-error">*</span></label>
                        <input type="password" name="confirm_password" required placeholder="••••••••" 
                               class="w-full px-4 py-3 rounded-lg border border-outline/30 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-sm bg-surface">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary text-on-primary font-bold py-3.5 px-4 rounded-lg hover:bg-primary-container transition-colors mt-4 text-sm flex items-center justify-center gap-2">
                    TẠO TÀI KHOẢN MỚI
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </form>

            <div class="mt-8 text-center text-sm text-outline border-t border-outline/10 pt-6">
                Đã có tài khoản đối tác? 
                <a href="login-client.php" class="font-bold text-primary hover:underline">Đăng nhập tại đây</a>
            </div>
        </div>

        <!-- Banner Section -->
        <div class="hidden lg:flex w-1/2 bg-primary p-12 flex-col justify-between relative overflow-hidden text-on-primary">
            <div class="relative z-10 flex flex-col h-full justify-center">
                <div class="w-16 h-16 bg-[#418369] rounded-2xl flex items-center justify-center text-white mb-8 border border-[#91d4b6]/30 shadow-lg">
                    <span class="material-symbols-outlined text-3xl">rocket_launch</span>
                </div>
                <h3 class="text-4xl font-h1 font-extrabold mb-6 leading-tight">Định hình <br>Giá trị thương hiệu.</h3>
                <p class="text-[#acf1d2] leading-relaxed max-w-md text-lg font-body">Khởi tạo tài khoản ngay hôm nay để trải nghiệm dịch vụ quản lý dự án chuyên nghiệp, minh bạch và hiệu quả cùng các chuyên gia hàng đầu.</p>
            </div>
            
            <!-- Graphic Elements -->
            <div class="absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full border-[40px] border-[#418369] opacity-50"></div>
            <div class="absolute bottom-12 -left-12 w-48 h-48 bg-[#91d4b6] rounded-full blur-[80px] opacity-40"></div>
        </div>
        
    </div>
</body>
</html>