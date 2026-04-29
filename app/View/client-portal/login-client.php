<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Client Login - Creative Agency Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>.material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }</style>
</head>
<body class="bg-slate-50 font-['Inter'] antialiased min-h-screen flex items-center justify-center">

    <div class="max-w-4xl w-full mx-auto p-6 flex items-center justify-center">
        <!-- Khung Card Đăng nhập -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full flex overflow-hidden min-h-[500px]">
            
            <!-- Cột trái: Form Đăng nhập -->
            <div class="w-full lg:w-1/2 p-10 sm:p-12 flex flex-col justify-center">
                <div class="mb-10">
                    <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center text-white mb-4">
                        <span class="material-symbols-outlined">cases</span>
                    </div>
                    <h2 class="text-3xl font-bold text-slate-900">Chào mừng trở lại</h2>
                    <p class="text-slate-500 mt-2">Đăng nhập vào Client Portal để theo dõi tiến độ dự án của bạn.</p>
                </div>

                <form action="auth-client.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email của bạn</label>
                        <input type="email" name="email" required placeholder="name@company.com" 
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-semibold text-slate-700">Mật khẩu</label>
                            <a href="#" class="text-sm font-medium text-orange-600 hover:text-orange-700">Quên mật khẩu?</a>
                        </div>
                        <input type="password" name="password" required placeholder="••••••••" 
                               class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>
                    
                    <button type="submit" class="w-full bg-orange-500 text-white font-bold py-3 px-4 rounded-lg hover:bg-orange-600 transition-colors shadow-md shadow-orange-200">
                        ĐĂNG NHẬP
                    </button>
                </form>

                <div class="mt-8 text-center text-sm text-slate-500 border-t border-slate-100 pt-6">
                    Bạn là nhân sự của Agency? <a href="login.php" class="font-bold text-[#107ED2] hover:underline">Đăng nhập nội bộ</a>
                </div>
            </div>

            <!-- Cột phải: Hình ảnh minh họa (Ẩn trên Mobile) -->
            <div class="hidden lg:flex w-1/2 bg-slate-900 p-12 flex-col justify-between relative overflow-hidden">
                <div class="relative z-10 text-white">
                    <h3 class="text-3xl font-black mb-4">Creative Agency Hub</h3>
                    <p class="text-slate-300 leading-relaxed">Chúng tôi cung cấp giải pháp sáng tạo toàn diện. Theo dõi từng bước tiến độ dự án của bạn một cách minh bạch và trực quan nhất.</p>
                </div>
                <!-- Background Pattern -->
                <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-orange-500 rounded-full blur-3xl opacity-20"></div>
                <div class="absolute -top-24 -left-24 w-72 h-72 bg-blue-500 rounded-full blur-3xl opacity-20"></div>
            </div>
            
        </div>
    </div>
</body>
</html>