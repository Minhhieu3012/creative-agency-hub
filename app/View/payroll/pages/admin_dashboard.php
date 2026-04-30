<?php
session_start();
// Đoạn này giữ nguyên logic của ông
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard - Creative Agency Hub</title>
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
                "primary": "#005ea1",
                "surface": "#f7f9fb",
                "on-surface": "#191c1e",
                "surface-container-lowest": "#ffffff",
                "outline-variant": "#c0c7d3",
                "on-surface-variant": "#404752",
                "error-container": "#ffdad6",
                "error": "#ba1a1a",
                "secondary-container": "#d3e1f6",
                "secondary": "#525f72",
                "primary-fixed": "#d2e4ff",
                "surface-container-low": "#f2f4f6",
                "surface-container-highest": "#e0e3e5"
            },
            fontFamily: {
                "body-md": ["Inter"], "label-md": ["Inter"], "display-lg": ["Inter"],
                "body-sm": ["Inter"], "title-sm": ["Inter"], "headline-md": ["Inter"]
            }
          }
        }
      }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body-md antialiased overflow-hidden">
<div class="flex h-screen w-full">
    
    <!-- SIDEBAR SIÊU ĐẸP -->
    <nav class="fixed left-0 top-0 h-full flex flex-col py-6 w-64 bg-[#000B1A] z-50">
        <div class="px-6 mb-8 flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded bg-[#107ED2] flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">widgets</span>
                </div>
                <span class="text-white text-lg font-black tracking-tight">Creative Hub</span>
            </div>
            <span class="text-slate-400 text-[13px] uppercase tracking-wider ml-11">Admin Portal</span>
        </div>
        
        <div class="flex-1 flex flex-col gap-1 overflow-y-auto px-2">
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="admin_dashboard.php">
                <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
            </a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="employees.php">
                <span class="material-symbols-outlined">group</span><span>Quản lý nhân viên</span>
            </a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="tasks.php">
                <span class="material-symbols-outlined">view_kanban</span><span>Công việc & Dự án</span>
            </a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="manager_approvals.php">
                <span class="material-symbols-outlined">assignment_turned_in</span><span>Duyệt đơn</span>
            </a>
        </div>

        <div class="mt-auto flex flex-col gap-1 px-2 pt-4 border-t border-white/10">
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="portal.php">
                <span class="material-symbols-outlined">logout</span><span>Quay về Portal</span>
            </a>
        </div>
    </nav>

    <!-- NỘI DUNG CHÍNH -->
    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto bg-surface">
        
        <!-- NAVBAR -->
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="flex items-center w-96 max-w-full">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-md focus:outline-none focus:border-[#107ED2] text-sm" placeholder="Tìm kiếm..." type="text"/>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-50 rounded-full transition-colors"><span class="material-symbols-outlined">notifications</span></button>
                <div class="h-8 w-px bg-slate-200 mx-2"></div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-700">Xin chào, Admin</span>
                    <img alt="Admin" class="w-8 h-8 rounded-full border border-slate-200" src="https://ui-avatars.com/api/?name=Admin&background=107ED2&color=fff"/>
                </div>
            </div>
        </header>

        <!-- KHUNG CHỨA DATA CỦA ÔNG -->
        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-[32px] font-semibold text-slate-900 leading-tight">ADMIN DASHBOARD</h1>
                    <p class="text-[16px] text-slate-500 mt-1">Tổng quan dữ liệu toàn hệ thống</p>
                </div>
            </div>

            <!-- 4 THẺ THỐNG KÊ (Đã đổi data khớp với file cũ của ông) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Thẻ 1 -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#4e73df] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Nhân sự</span>
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-md"><span class="material-symbols-outlined">badge</span></div>
                    </div>
                    <span class="text-[40px] font-bold text-slate-900 leading-none">32</span>
                </div>
                
                <!-- Thẻ 2 -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#1cc88a] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Dự án</span>
                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-md"><span class="material-symbols-outlined">rocket_launch</span></div>
                    </div>
                    <span class="text-[40px] font-bold text-slate-900 leading-none">8</span>
                </div>

                <!-- Thẻ 3 -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#36b9cc] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Task</span>
                        <div class="p-2 bg-cyan-50 text-cyan-600 rounded-md"><span class="material-symbols-outlined">task</span></div>
                    </div>
                    <span class="text-[40px] font-bold text-slate-900 leading-none">124</span>
                </div>

                <!-- Thẻ 4 -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#f6c23e] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Đơn cần duyệt</span>
                        <div class="p-2 bg-yellow-50 text-yellow-600 rounded-md"><span class="material-symbols-outlined">rule_folder</span></div>
                    </div>
                    <span class="text-[40px] font-bold text-slate-900 leading-none">5</span>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>