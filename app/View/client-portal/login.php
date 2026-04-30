<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập Nội bộ - Creative Agency Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>.material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }</style>
</head>
<body class="bg-slate-50 font-['Inter'] antialiased min-h-screen flex items-center justify-center bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
    <div class="max-w-md w-full mx-auto p-6 relative z-10">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
            
            <!-- Header Form -->
            <div class="bg-[#000B1A] p-8 text-center flex flex-col items-center">
                <div class="w-12 h-12 bg-[#107ED2] rounded-xl flex items-center justify-center text-white mb-4 shadow-lg">
                    <span class="material-symbols-outlined text-2xl">widgets</span>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-tight">Creative Hub</h2>
                <p class="text-slate-400 mt-1 text-xs font-bold uppercase tracking-[0.2em]">Internal Portal</p>
            </div>
            
            <!-- Body Form -->
            <div class="p-8">
                <!-- Chỗ này ông chỉnh lại thuộc tính action trỏ về file xử lý login PHP của ông nhé (ví dụ auth.php) -->
                <!-- Tạm thời tui để action trỏ về portal.php để ông bấm test giao diện -->
                <form action="portal.php" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Email nội bộ</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">mail</span>
                            <input type="email" name="email" required placeholder="nhanvien@creativehub.com" 
                                   class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-300 focus:border-[#107ED2] focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-sm font-semibold text-slate-700">Mật khẩu</label>
                            <a href="#" class="text-xs font-medium text-[#107ED2] hover:underline">Quên mật khẩu?</a>
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">lock</span>
                            <input type="password" name="password" required placeholder="••••••••" 
                                   class="w-full pl-10 pr-4 py-3 rounded-lg border border-slate-300 focus:border-[#107ED2] focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-[#107ED2] text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors shadow-md shadow-blue-200 mt-4 flex justify-center items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">login</span> ĐĂNG NHẬP HỆ THỐNG
                    </button>
                </form>

                <div class="mt-6 border-t border-slate-100 pt-6 text-center">
                    <a href="../client-portal/login-client.php" class="text-sm font-medium text-slate-500 hover:text-orange-600 transition-colors flex items-center justify-center gap-1 group">
                        <span class="material-symbols-outlined text-[18px] group-hover:-translate-x-1 transition-transform">arrow_back</span> Về trang Khách hàng (Client Portal)
                    </a>
                </div>
            </div>

        </div>
    </div>
</body>
</html>