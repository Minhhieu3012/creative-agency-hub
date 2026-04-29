<?php
session_start();
require_once '../config/db_connect.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$_SESSION['employee_id'] = 2; 
$manager_id = $_SESSION['employee_id'];
$month = date('m');
$year = date('Y');

$stmtUser = $pdo->prepare("SELECT full_name FROM employees WHERE id = ?");
$stmtUser->execute([$manager_id]);
$manager = $stmtUser->fetch();

$sqlLeave = "SELECT lr.*, e.full_name, e.remaining_leave_days FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.status = 'Pending' AND e.manager_id = ? ORDER BY lr.created_at DESC";
$stmtLeaves = $pdo->prepare($sqlLeave);
$stmtLeaves->execute([$manager_id]);
$requests = $stmtLeaves->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Không gian Quản lý - Creative Agency Hub</title>
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
                <div class="w-8 h-8 rounded bg-[#1cc88a] flex items-center justify-center text-white"><span class="material-symbols-outlined text-lg">admin_panel_settings</span></div>
                <span class="text-white text-lg font-black tracking-tight">Creative Hub</span>
            </div>
            <span class="text-slate-400 text-[13px] uppercase tracking-wider ml-11">Manager Portal</span>
        </div>
        <div class="flex-1 flex flex-col gap-1 px-2">
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="manager_dashboard.php"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#1cc88a] text-white rounded-md text-sm font-medium" href="manager_approvals.php"><span class="material-symbols-outlined">rule_folder</span><span>Duyệt đơn từ</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="tasks.php"><span class="material-symbols-outlined">view_kanban</span><span>Quản lý công việc</span></a>
        </div>
    </nav>

    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="font-bold text-lg text-[#1cc88a]">Không gian Quản lý</div>
            <a href="portal.php" class="text-sm text-[#1cc88a] font-medium">Quay về Portal</a>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-emerald-800">Xin chào Manager, <?= htmlspecialchars($manager['full_name'] ?? 'Sếp') ?></h1>
                <p class="text-emerald-600 mt-1">Dưới đây là các đầu việc nhân sự cần sếp xử lý hôm nay.</p>
            </div>

            <!-- Bảng Duyệt Đơn -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600">inbox</span>
                    <h2 class="font-bold text-slate-800">Đơn xin nghỉ phép cần xử lý</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-white text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 font-medium">Nhân viên</th>
                            <th class="px-6 py-4 font-medium">Thời gian nghỉ</th>
                            <th class="px-6 py-4 font-medium">Lý do</th>
                            <th class="px-6 py-4 font-medium text-center">Số ngày</th>
                            <th class="px-6 py-4 font-medium text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if(empty($requests)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Không có đơn từ nào cần duyệt. Tuyệt vời! 🎉</td></tr>
                        <?php else: foreach($requests as $req): $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img class="w-10 h-10 rounded-full bg-slate-200" src="https://ui-avatars.com/api/?name=<?= urlencode($req['full_name']) ?>&background=random"/>
                                    <div>
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($req['full_name']) ?></div>
                                        <div class="text-xs text-emerald-600 font-medium">Quỹ phép: <?= floatval($req['remaining_leave_days']) ?> ngày</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700">
                                <span class="text-[#107ED2]"><?= date('d/m/Y', strtotime($req['start_date'])) ?></span> 
                                <span class="text-slate-400 mx-1">-></span> 
                                <span class="text-[#107ED2]"><?= date('d/m/Y', strtotime($req['end_date'])) ?></span>
                            </td>
                            <td class="px-6 py-4 max-w-[200px] truncate text-slate-600" title="<?= htmlspecialchars($req['reason']) ?>"><?= htmlspecialchars($req['reason']) ?></td>
                            <td class="px-6 py-4 text-center"><span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full font-bold text-xs"><?= $d ?> ngày</span></td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="handleApprove(<?= $req['id'] ?>, 'Approved')" class="w-8 h-8 inline-flex items-center justify-center bg-emerald-100 text-emerald-700 hover:bg-emerald-600 hover:text-white rounded-full transition-colors mr-1" title="Duyệt"><span class="material-symbols-outlined text-[18px]">check</span></button>
                                <button onclick="handleApprove(<?= $req['id'] ?>, 'Rejected')" class="w-8 h-8 inline-flex items-center justify-center bg-rose-100 text-rose-700 hover:bg-rose-600 hover:text-white rounded-full transition-colors" title="Từ chối"><span class="material-symbols-outlined text-[18px]">close</span></button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bảng Lương Tổng Hợp -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mt-4">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#107ED2]">payments</span>
                    <h2 class="font-bold text-slate-800">Bảng Lương Tổng Hợp - Tháng <?= $month ?></h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 font-medium">Nhân sự</th>
                                <th class="px-6 py-4 font-medium text-center">Lương cơ bản</th>
                                <th class="px-6 py-4 font-medium text-center">Ngày công</th>
                                <th class="px-6 py-4 font-medium text-center">Thưởng KPI</th>
                                <th class="px-6 py-4 font-medium text-center">Vi phạm</th>
                                <th class="px-6 py-4 font-medium text-right">Thực lãnh (VNĐ)</th>
                            </tr>
                        </thead>
                        <tbody id="payrollTableBody" class="divide-y divide-slate-100">
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                    <span class="material-symbols-outlined animate-spin text-2xl text-[#107ED2] mb-2">sync</span><br>
                                    Đang tải dữ liệu từ API...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
const token = localStorage.getItem('jwt_token');

function handleApprove(id, action) {
    if (!token) return alert("Vui lòng đăng nhập (Chưa có Token)!");
    if (!confirm(`Bạn chắc chắn muốn ${action === 'Approved' ? 'DUYỆT' : 'TỪ CHỐI'} đơn này?`)) return;

    fetch(`/creative-agency-hub/public/api/leaves/${id}/approve`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({ action: action })
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') { alert("✅ " + data.message); location.reload(); } 
        else { alert("❌ Lỗi: " + data.message); }
    }).catch(err => alert("Lỗi kết nối API!"));
}

if (token) {
    fetch('/creative-agency-hub/public/api/payroll/summary', {
        headers: { 'Authorization': 'Bearer ' + token }
    }).then(res => res.json()).then(data => {
        if (data.status === 'success') {
            const tbody = document.getElementById('payrollTableBody');
            tbody.innerHTML = ''; 
            data.data.forEach(p => {
                const roleColor = p.role === 'admin' ? 'bg-rose-100 text-rose-700' : (p.role === 'manager' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700');
                const row = `
                    <tr class="hover:bg-slate-50">
                      <td class="px-6 py-4">
                        <div class="font-bold text-slate-900">${p.full_name}</div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase ${roleColor}">${p.role}</span>
                      </td>
                      <td class="px-6 py-4 text-center text-slate-500">${p.base_salary.toLocaleString()} ₫</td>
                      <td class="px-6 py-4 text-center"><span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold">${p.attendance.actual_days} / ${p.attendance.standard_days}</span></td>
                      <td class="px-6 py-4 text-center text-emerald-600 font-bold">+ ${p.financial.bonus.toLocaleString()} ₫ <br><span class="text-xs font-normal text-slate-400">(${p.kpi.percent}%)</span></td>
                      <td class="px-6 py-4 text-center text-rose-600 font-bold">- ${p.financial.penalty.toLocaleString()} ₫ <br><span class="text-xs font-normal text-slate-400">(${p.attendance.late + p.attendance.early} lỗi)</span></td>
                      <td class="px-6 py-4 text-right font-black text-[#107ED2] text-lg">${p.financial.net_salary.toLocaleString()} ₫</td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        }
    });
}
</script>
</body>
</html>