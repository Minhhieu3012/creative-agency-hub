<?php
session_start();
require_once '../config/db_connect.php';
$month = date('m');
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bảng Lương - Creative Agency Hub</title>
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
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="employee_dashboard.php"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="attendance.php"><span class="material-symbols-outlined">timer</span><span>Chấm công</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="leave_request.php"><span class="material-symbols-outlined">event_note</span><span>Đơn xin nghỉ</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="payroll_summary.php"><span class="material-symbols-outlined">payments</span><span>Bảng lương</span></a>
        </div>
    </nav>

    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="font-bold text-lg">Hệ thống nhân sự</div>
            <a href="portal.php" class="text-sm text-[#107ED2] font-medium">Quay về Portal</a>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            
            <div class="flex justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <h1 class="font-bold text-lg text-slate-800">Phiếu lương cá nhân - Tháng <?= $month ?>/<?= $year ?></h1>
                <button onclick="downloadExcel()" class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-md font-medium hover:bg-emerald-700 transition-colors">
                    <span class="material-symbols-outlined text-sm">download</span> Tải Excel
                </button>
            </div>

            <div id="loading" class="text-center py-12 text-slate-500">
                <span class="material-symbols-outlined animate-spin text-4xl text-[#107ED2] mb-2">sync</span>
                <p>Đang tính toán dữ liệu lương...</p>
            </div>

            <div id="payrollContent" class="grid grid-cols-1 lg:grid-cols-3 gap-8 hidden">
                <!-- Profile Cột Trái -->
                <div class="flex flex-col gap-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden text-center pb-6">
                        <div class="h-24 bg-gradient-to-r from-blue-600 to-blue-400 w-full mb-12"></div>
                        <img id="empAvatar" src="" class="w-24 h-24 rounded-full border-4 border-white shadow-md mx-auto -mt-16 mb-4">
                        <h2 id="empName" class="font-bold text-xl text-slate-900">...</h2>
                        <span id="empRole" class="inline-block mt-2 px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full uppercase">...</span>
                        
                        <div class="mt-6 px-6 border-t border-slate-100 pt-4 flex justify-between text-sm">
                            <span class="text-slate-500">Lương cơ bản:</span>
                            <strong id="baseSalary" class="text-[#107ED2]">0 đ</strong>
                        </div>
                        <div class="px-6 flex justify-between text-sm mt-2">
                            <span class="text-slate-500">Ngày công chuẩn:</span>
                            <strong id="standardDays" class="text-slate-700">24 ngày</strong>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 border-l-4 border-l-emerald-500 text-center">
                        <h3 class="font-bold text-sm text-emerald-600 mb-2 uppercase">Tiến độ KPI</h3>
                        <div id="kpiPercent" class="text-4xl font-black text-slate-800 mb-1">0%</div>
                        <p class="text-sm text-slate-500">Hoàn thành <strong id="kpiTasks" class="text-slate-800">0/0</strong> task</p>
                    </div>
                </div>

                <!-- Chi tiết lương Cột Phải -->
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                    <h2 class="font-bold text-lg text-slate-800 mb-6 pb-4 border-b border-slate-100">Chi tiết thanh toán</h2>
                    
                    <div class="space-y-6">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded bg-blue-50 text-blue-600 flex items-center justify-center"><span class="material-symbols-outlined">calendar_month</span></div>
                                <div><div class="font-bold text-slate-800">Lương ngày công</div><div id="actualDaysBadge" class="text-xs text-slate-500">0 / 24 ngày</div></div>
                            </div>
                            <div id="actualSalary" class="font-bold text-lg text-slate-900">0 đ</div>
                        </div>

                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded bg-emerald-50 text-emerald-600 flex items-center justify-center"><span class="material-symbols-outlined">trending_up</span></div>
                                <div><div class="font-bold text-slate-800">Thưởng vượt KPI</div><div id="bonusBadge" class="text-xs text-slate-500">Đạt 0%</div></div>
                            </div>
                            <div id="bonusValue" class="font-bold text-lg text-emerald-600">+ 0 đ</div>
                        </div>

                        <div class="flex justify-between items-center pb-6 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded bg-rose-50 text-rose-600 flex items-center justify-center"><span class="material-symbols-outlined">money_off</span></div>
                                <div>
                                    <div class="font-bold text-slate-800">Phạt chuyên cần</div>
                                    <div id="penaltyBadge" class="text-xs text-slate-500 mb-1">0 vi phạm</div>
                                    <div id="penaltyDetail" class="text-xs text-slate-400">Đi trễ: 0 | Về sớm: 0</div>
                                </div>
                            </div>
                            <div id="penaltyValue" class="font-bold text-lg text-rose-600">- 0 đ</div>
                        </div>

                        <div class="flex justify-between items-center pt-2">
                            <h3 class="font-black text-xl text-slate-900">TỔNG THỰC LÃNH</h3>
                            <div id="netSalary" class="font-black text-3xl text-[#107ED2]">0 đ</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
const token = localStorage.getItem('jwt_token');
if (!token) {
    alert("Bạn chưa đăng nhập!");
} else {
    fetch('/creative-agency-hub/public/api/payroll/summary', {
        headers: { 'Authorization': 'Bearer ' + token }
    }).then(r => r.json()).then(data => {
        if (data.status === 'success' && data.data.length > 0) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('payrollContent').classList.remove('hidden');

            const emp = data.data[0];
            document.getElementById('empName').textContent = emp.full_name;
            document.getElementById('empAvatar').src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(emp.full_name) + "&background=random&size=100";
            document.getElementById('empRole').textContent = emp.role;
            document.getElementById('baseSalary').textContent = emp.base_salary.toLocaleString() + ' đ';
            document.getElementById('standardDays').textContent = emp.attendance.standard_days + ' ngày';
            
            document.getElementById('kpiPercent').textContent = emp.kpi.percent + '%';
            if(emp.kpi.percent < 100) document.getElementById('kpiPercent').classList.add('text-amber-500');
            document.getElementById('kpiTasks').textContent = emp.kpi.completed_tasks + '/' + emp.kpi.target_tasks;
            
            document.getElementById('actualDaysBadge').textContent = emp.attendance.actual_days + ' / ' + emp.attendance.standard_days + ' ngày';
            document.getElementById('actualSalary').textContent = emp.financial.actual_salary.toLocaleString() + ' đ';
            
            document.getElementById('bonusBadge').textContent = 'Đạt ' + emp.kpi.percent + '%';
            document.getElementById('bonusValue').textContent = '+ ' + emp.financial.bonus.toLocaleString() + ' đ';
            
            document.getElementById('penaltyBadge').textContent = (emp.attendance.late + emp.attendance.early) + ' vi phạm';
            document.getElementById('penaltyDetail').textContent = 'Đi trễ: ' + emp.attendance.late + ' | Về sớm: ' + emp.attendance.early;
            document.getElementById('penaltyValue').textContent = '- ' + emp.financial.penalty.toLocaleString() + ' đ';
            
            document.getElementById('netSalary').textContent = emp.financial.net_salary.toLocaleString() + ' đ';
        }
    });
}
function downloadExcel() {
    if (token) window.open('/creative-agency-hub/public/api/payroll/export?token=' + token, '_blank');
}
</script>
</body>
</html>