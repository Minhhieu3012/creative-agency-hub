<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Task Hub - Creative Agency Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>.material-symbols-outlined { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }</style>
</head>
<body class="bg-[#f7f9fb] text-[#191c1e] font-['Inter'] antialiased">
<div class="flex h-screen w-full">
    
    <nav class="fixed left-0 top-0 h-full flex flex-col py-6 w-64 bg-[#000B1A] z-50">
        <div class="px-6 mb-8 flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded bg-[#107ED2] flex items-center justify-center text-white"><span class="material-symbols-outlined text-lg">widgets</span></div>
                <span class="text-white text-lg font-black tracking-tight">Creative Hub</span>
            </div>
        </div>
        <div class="flex-1 flex flex-col gap-1 px-2">
            <!-- Lưu ý: Nếu Admin đăng nhập thì sửa link về admin_dashboard.php, PM thì về manager_dashboard.php -->
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="portal.php"><span class="material-symbols-outlined">arrow_back</span><span>Về Portal</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="tasks.php"><span class="material-symbols-outlined">view_kanban</span><span>Task Hub</span></a>
        </div>
    </nav>

    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="font-bold text-lg">Quản lý Công việc & Dự án</div>
            <div class="text-sm font-medium text-slate-500"><?= date('d/m/Y') ?></div>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Task Hub</h1>
                <p class="text-slate-500 mt-1">Trung tâm điều phối và theo dõi tiến độ công việc</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kanban Board Card -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 flex flex-col items-center text-center group">
                    <div class="w-20 h-20 rounded-2xl bg-blue-50 text-[#107ED2] flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-4xl">view_column</span>
                    </div>
                    <h3 class="font-bold text-xl text-slate-900 mb-2">Kanban Board</h3>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm">Quản lý các luồng công việc theo trạng thái To Do, Doing, Review, Done một cách trực quan.</p>
                    <a href="../app/View/tasks/kanban.php" class="bg-[#107ED2] text-white px-6 py-2.5 rounded-md font-medium hover:bg-blue-700 transition-colors">Mở Kanban</a>
                </div>

                <!-- Gantt Chart Card -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200 flex flex-col items-center text-center group">
                    <div class="w-20 h-20 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-4xl">waterfall_chart</span>
                    </div>
                    <h3 class="font-bold text-xl text-slate-900 mb-2">Gantt Chart</h3>
                    <p class="text-slate-500 text-sm mb-6 max-w-sm">Theo dõi chi tiết tiến độ dự án theo trục thời gian, quản lý deadline hiệu quả.</p>
                    <a href="../app/View/tasks/gantt.php" class="bg-emerald-600 text-white px-6 py-2.5 rounded-md font-medium hover:bg-emerald-700 transition-colors">Mở Gantt</a>
                </div>
            </div>

            <!-- Bảng Task Gần Đây -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mt-4">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-500">history</span>
                    <h2 class="font-bold text-slate-800">Task theo dõi gần đây</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 font-medium w-16">#</th>
                            <th class="px-6 py-4 font-medium">Tên Task</th>
                            <th class="px-6 py-4 font-medium">Dự án</th>
                            <th class="px-6 py-4 font-medium">Deadline</th>
                            <th class="px-6 py-4 font-medium text-right">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-400">1</td>
                            <td class="px-6 py-4 font-bold text-slate-900">Thiết kế landing page</td>
                            <td class="px-6 py-4 text-slate-600">Agency Website</td>
                            <td class="px-6 py-4 text-rose-600 font-medium">15/05/2026</td>
                            <td class="px-6 py-4 text-right"><span class="px-3 py-1 bg-blue-100 text-[#107ED2] rounded-full text-xs font-bold">Doing</span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-400">2</td>
                            <td class="px-6 py-4 font-bold text-slate-900">Chuẩn hóa brand guideline</td>
                            <td class="px-6 py-4 text-slate-600">Creative Kit</td>
                            <td class="px-6 py-4 text-slate-600 font-medium">20/05/2026</td>
                            <td class="px-6 py-4 text-right"><span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Review</span></td>
                        </tr>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-400">3</td>
                            <td class="px-6 py-4 font-bold text-slate-900 line-through text-slate-400">Bàn giao UI kit</td>
                            <td class="px-6 py-4 text-slate-600">Client A</td>
                            <td class="px-6 py-4 text-slate-600 font-medium">25/05/2026</td>
                            <td class="px-6 py-4 text-right"><span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Done</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
</body>
</html>