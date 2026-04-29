<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Internal Portal - Creative Agency Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-slate-50 font-['Inter'] antialiased flex items-center justify-center min-h-screen">
    
    <div class="max-w-5xl w-full p-6">
        <div class="text-center mb-12">
            <div class="w-16 h-16 bg-[#107ED2] rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <span class="material-symbols-outlined text-white text-3xl">widgets</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900">Creative Agency Hub</h1>
            <p class="text-slate-500 mt-2">Cổng thông tin nội bộ - Chọn không gian làm việc của bạn</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Card Admin -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col items-center text-center group cursor-pointer" onclick="window.location.href='admin_dashboard.php'">
                <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 mb-1">Admin</h3>
                <p class="text-sm text-slate-500 mb-6">Quản trị toàn hệ thống</p>
                <a href="admin_dashboard.php" class="w-full py-2 bg-slate-50 text-blue-600 font-medium rounded-lg group-hover:bg-blue-50 group-hover:text-blue-700 transition-colors">Vào Dashboard</a>
            </div>

            <!-- Card Manager -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col items-center text-center group cursor-pointer" onclick="window.location.href='manager_dashboard.php'">
                <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">manage_accounts</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 mb-1">Manager</h3>
                <p class="text-sm text-slate-500 mb-6">Duyệt & giao việc</p>
                <a href="manager_dashboard.php" class="w-full py-2 bg-slate-50 text-emerald-600 font-medium rounded-lg group-hover:bg-emerald-50 group-hover:text-emerald-700 transition-colors">Vào Dashboard</a>
            </div>

            <!-- Card Employee -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col items-center text-center group cursor-pointer" onclick="window.location.href='employee_dashboard.php'">
                <div class="w-16 h-16 rounded-full bg-cyan-50 text-cyan-600 flex items-center justify-center mb-4 group-hover:bg-cyan-600 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">badge</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 mb-1">Employee</h3>
                <p class="text-sm text-slate-500 mb-6">Chấm công & đơn từ</p>
                <a href="employee_dashboard.php" class="w-full py-2 bg-slate-50 text-cyan-600 font-medium rounded-lg group-hover:bg-cyan-50 group-hover:text-cyan-700 transition-colors">Vào Dashboard</a>
            </div>

            <!-- Card Client -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex flex-col items-center text-center group cursor-pointer" onclick="window.location.href='../app/View/client-portal/login-client.php'">
                <div class="w-16 h-16 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center mb-4 group-hover:bg-orange-600 group-hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">cases</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900 mb-1">Client</h3>
                <p class="text-sm text-slate-500 mb-6">Khách hàng theo dõi</p>
                <a href="../app/View/client-portal/login-client.php" class="w-full py-2 bg-slate-50 text-orange-600 font-medium rounded-lg group-hover:bg-orange-50 group-hover:text-orange-700 transition-colors">Vào Client Portal</a>
            </div>
        </div>
    </div>
</body>
</html>