<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Quản lý nhân viên - Creative Agency Hub</title>
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
            <span class="text-slate-400 text-[13px] uppercase tracking-wider ml-11">Admin Portal</span>
        </div>
        <div class="flex-1 flex flex-col gap-1 px-2">
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="admin_dashboard.php"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="employees.php"><span class="material-symbols-outlined">group</span><span>Quản lý nhân viên</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="tasks.php"><span class="material-symbols-outlined">view_kanban</span><span>Công việc & Dự án</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="manager_approvals.php"><span class="material-symbols-outlined">rule_folder</span><span>Duyệt đơn</span></a>
        </div>
    </nav>

    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="font-bold text-lg">Hệ thống nhân sự</div>
            <a href="portal.php" class="text-sm text-[#107ED2] font-medium">Quay về Portal</a>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-6 pb-24">
            
            <div class="flex justify-between items-center bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Quản lý nhân viên</h1>
                    <p class="text-slate-500 text-sm mt-1">Danh sách nhân sự toàn hệ thống</p>
                </div>
                <button onclick="toggleModal('addEmployeeModal')" class="flex items-center gap-2 bg-[#107ED2] text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700 transition-colors">
                    <span class="material-symbols-outlined text-sm">person_add</span> Thêm nhân viên
                </button>
            </div>

            <!-- Bảng nhân viên -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 font-medium w-16">#</th>
                            <th class="px-6 py-4 font-medium">Họ tên & Email</th>
                            <th class="px-6 py-4 font-medium">Phòng ban</th>
                            <th class="px-6 py-4 font-medium">Chức vụ</th>
                            <th class="px-6 py-4 font-medium text-center">Trạng thái</th>
                            <th class="px-6 py-4 font-medium text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <!-- NV 1 -->
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">1</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">Nguyễn Văn A</div>
                                <div class="text-slate-500 text-xs mt-1">a@agency.com</div>
                            </td>
                            <td class="px-6 py-4">Creative</td>
                            <td class="px-6 py-4">Designer</td>
                            <td class="px-6 py-4 text-center"><span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Active</span></td>
                            <td class="px-6 py-4 text-right">
                                <button class="p-1.5 text-slate-400 hover:text-[#107ED2] hover:bg-blue-50 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <button class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                            </td>
                        </tr>
                        <!-- NV 2 -->
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">2</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">Trần Thị B</div>
                                <div class="text-slate-500 text-xs mt-1">b@agency.com</div>
                            </td>
                            <td class="px-6 py-4">Account</td>
                            <td class="px-6 py-4">Manager</td>
                            <td class="px-6 py-4 text-center"><span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold">Inactive</span></td>
                            <td class="px-6 py-4 text-right">
                                <button class="p-1.5 text-slate-400 hover:text-[#107ED2] hover:bg-blue-50 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <button class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                            </td>
                        </tr>
                        <!-- NV 3 -->
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">3</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">Lê Văn C</div>
                                <div class="text-slate-500 text-xs mt-1">c@agency.com</div>
                            </td>
                            <td class="px-6 py-4">Dev</td>
                            <td class="px-6 py-4">Engineer</td>
                            <td class="px-6 py-4 text-center"><span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Active</span></td>
                            <td class="px-6 py-4 text-right">
                                <button class="p-1.5 text-slate-400 hover:text-[#107ED2] hover:bg-blue-50 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                <button class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Thêm Nhân Viên -->
<div id="addEmployeeModal" class="hidden fixed inset-0 z-[100] bg-slate-900/50 flex items-center justify-center backdrop-blur-sm transition-all">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-900">Thêm nhân viên mới</h3>
            <button onclick="toggleModal('addEmployeeModal')" class="text-slate-400 hover:text-rose-600 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Họ tên</label>
                <input type="text" placeholder="Nhập họ tên" class="w-full border-slate-300 rounded-md focus:border-[#107ED2] focus:ring-[#107ED2] text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" placeholder="name@agency.com" class="w-full border-slate-300 rounded-md focus:border-[#107ED2] focus:ring-[#107ED2] text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phòng ban</label>
                <input type="text" placeholder="Creative" class="w-full border-slate-300 rounded-md focus:border-[#107ED2] focus:ring-[#107ED2] text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Chức vụ</label>
                <input type="text" placeholder="Designer" class="w-full border-slate-300 rounded-md focus:border-[#107ED2] focus:ring-[#107ED2] text-sm">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
            <button onclick="toggleModal('addEmployeeModal')" class="px-4 py-2 text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 font-medium text-sm transition-colors">Hủy</button>
            <button class="px-4 py-2 bg-[#107ED2] text-white rounded-md hover:bg-blue-700 font-medium text-sm transition-colors">Lưu nhân viên</button>
        </div>
    </div>
</div>

<script>
    function toggleModal(modalID) {
        document.getElementById(modalID).classList.toggle('hidden');
    }
</script>
</body>
</html>