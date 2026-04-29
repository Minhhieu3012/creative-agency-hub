<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Employee Dashboard - Creative Agency Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style>.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }</style>
</head>
<body class="bg-[#f7f9fb] text-[#191c1e] font-['Inter'] antialiased overflow-hidden">
<div class="flex h-screen w-full">
    
    <!-- SIDEBAR -->
    <nav class="fixed left-0 top-0 h-full flex flex-col py-6 w-64 bg-[#000B1A] z-50">
        <div class="px-6 mb-8 flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded bg-[#107ED2] flex items-center justify-center text-white"><span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">widgets</span></div>
                <span class="text-white text-lg font-black tracking-tight">Creative Hub</span>
            </div>
            <span class="text-slate-400 text-[13px] uppercase tracking-wider ml-11">My Workspace</span>
        </div>
        <div class="flex-1 flex flex-col gap-1 overflow-y-auto px-2">
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="employee_dashboard.php"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="attendance.php"><span class="material-symbols-outlined">timer</span><span>Chấm công</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="leave_request.php"><span class="material-symbols-outlined">event_note</span><span>Đơn xin nghỉ</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="payroll_summary.php"><span class="material-symbols-outlined">payments</span><span>Bảng lương</span></a>
        </div>
        <div class="mt-auto flex flex-col gap-1 px-2 pt-4 border-t border-white/10">
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="portal.php"><span class="material-symbols-outlined">logout</span><span>Quay về Portal</span></a>
        </div>
    </nav>

    <!-- NỘI DUNG -->
    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="flex items-center w-96 max-w-full">
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-md focus:outline-none focus:border-[#107ED2] text-sm" placeholder="Tìm kiếm..." type="text"/>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 pl-2">
                    <span class="text-sm font-medium text-slate-700">Lưu Quốc Phú</span>
                    <img class="w-8 h-8 rounded-full border border-slate-200" src="https://ui-avatars.com/api/?name=Phu+Luu&background=107ED2&color=fff"/>
                </div>
            </div>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-[32px] font-semibold text-slate-900 leading-tight">CHÀO BUỔI SÁNG, PHÚ</h1>
                    <p class="text-[16px] text-slate-500 mt-1">Đây là tóm tắt công việc của bạn hôm nay.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#4e73df] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Chấm công hôm nay</span>
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-md"><span class="material-symbols-outlined">timer</span></div>
                    </div>
                    <span class="text-[28px] font-bold text-slate-900 leading-none">Đã check-in</span>
                </div>
                
                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#1cc88a] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Số ngày phép còn</span>
                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-md"><span class="material-symbols-outlined">event_available</span></div>
                    </div>
                    <span class="text-[40px] font-bold text-slate-900 leading-none">8</span>
                </div>

                <div class="bg-white p-6 rounded-xl border border-slate-200 border-l-4 border-l-[#36b9cc] shadow-sm flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[13px] text-slate-500 font-bold uppercase tracking-wider">Lương tháng này</span>
                        <div class="p-2 bg-cyan-50 text-cyan-600 rounded-md"><span class="material-symbols-outlined">payments</span></div>
                    </div>
                    <span class="text-[32px] font-bold text-slate-900 leading-none">12,500,000đ</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h6 class="font-bold text-lg text-[#107ED2] mb-2">Chấm công</h6>
                    <p class="text-slate-500 mb-6">Check-in, check-out và thống kê.</p>
                    <a href="attendance.php" class="inline-block bg-[#107ED2] text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors font-medium">Mở Chấm công</a>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h6 class="font-bold text-lg text-[#36b9cc] mb-2">Xin nghỉ phép</h6>
                    <p class="text-slate-500 mb-6">Gửi đơn và theo dõi trạng thái.</p>
                    <a href="leave_request.php" class="inline-block bg-[#36b9cc] text-white px-4 py-2 rounded-md hover:bg-cyan-600 transition-colors font-medium">Mở Đơn nghỉ</a>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h6 class="font-bold text-lg text-[#1cc88a] mb-2">Bảng lương</h6>
                    <p class="text-slate-500 mb-6">Xem phiếu lương cá nhân.</p>
                    <a href="payroll_summary.php" class="inline-block bg-[#1cc88a] text-white px-4 py-2 rounded-md hover:bg-emerald-600 transition-colors font-medium">Mở Bảng lương</a>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>