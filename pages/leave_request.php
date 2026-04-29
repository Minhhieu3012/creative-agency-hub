<?php
session_start();
require_once '../config/db_connect.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$_SESSION['employee_id'] = 3; 
$emp_id = $_SESSION['employee_id'];

$stmtUser = $pdo->prepare("SELECT full_name, remaining_leave_days FROM employees WHERE id = ?");
$stmtUser->execute([$emp_id]);
$user = $stmtUser->fetch();
$leave_balance = isset($user['remaining_leave_days']) ? floatval($user['remaining_leave_days']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_leave'])) {
    $req_id = $_POST['request_id'];
    $stmtCancel = $pdo->prepare("DELETE FROM leave_requests WHERE id = ? AND employee_id = ? AND status = 'Pending'");
    $stmtCancel->execute([$req_id, $emp_id]);
    if ($stmtCancel->rowCount() > 0) {
        $_SESSION['flash_msg'] = "Đã hủy đơn thành công!"; $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_msg'] = "Không thể hủy! Đơn này đã được xử lý."; $_SESSION['flash_type'] = "error";
    }
    header("Location: leave_request.php");
    exit();
}

$stmtRequests = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmtRequests->execute([$emp_id]);
$requests = $stmtRequests->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Xin Nghỉ Phép - Creative Agency Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="leave_request.php"><span class="material-symbols-outlined">event_note</span><span>Đơn xin nghỉ</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="payroll_summary.php"><span class="material-symbols-outlined">payments</span><span>Bảng lương</span></a>
        </div>
    </nav>

    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="font-bold text-lg">Hệ thống nhân sự</div>
            <a href="portal.php" class="text-sm text-[#107ED2] font-medium">Quay về Portal</a>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Cột Form Gửi Đơn -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 h-fit">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                        <h2 class="font-bold text-lg text-slate-900">Tạo đơn mới</h2>
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-bold">Quỹ phép: <?= $leave_balance ?> ngày</span>
                    </div>

                    <?php if (isset($_SESSION['flash_msg'])): ?>
                    <div class="mb-4 p-3 rounded-md text-sm font-medium <?= $_SESSION['flash_type'] == 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                        <?= $_SESSION['flash_msg'] ?>
                    </div>
                    <?php unset($_SESSION['flash_msg']); unset($_SESSION['flash_type']); endif; ?>

                    <form id="leaveForm" onsubmit="event.preventDefault(); submitLeaveRequest();" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Từ ngày:</label>
                            <input type="date" id="start_date" required min="<?= date('Y-m-d') ?>" class="w-full border-slate-300 rounded-md shadow-sm focus:border-[#107ED2] focus:ring-[#107ED2] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Đến ngày:</label>
                            <input type="date" id="end_date" required min="<?= date('Y-m-d') ?>" class="w-full border-slate-300 rounded-md shadow-sm focus:border-[#107ED2] focus:ring-[#107ED2] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Lý do nghỉ:</label>
                            <textarea id="reason" rows="3" required placeholder="Nhập chi tiết..." class="w-full border-slate-300 rounded-md shadow-sm focus:border-[#107ED2] focus:ring-[#107ED2] text-sm"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2 bg-[#107ED2] text-white font-medium rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50" <?= $leave_balance <= 0 ? 'disabled' : '' ?>>
                            GỬI YÊU CẦU
                        </button>
                    </form>
                </div>

                <!-- Cột Lịch sử -->
                <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <h2 class="font-bold text-lg text-slate-900">Lịch sử nghỉ phép của bạn</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Thời gian</th>
                                    <th class="px-6 py-3 font-medium">Lý do</th>
                                    <th class="px-6 py-3 font-medium text-center">Số ngày</th>
                                    <th class="px-6 py-3 font-medium text-right">Trạng thái / Tác vụ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <?php if(empty($requests)): ?>
                                <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">Chưa có dữ liệu.</td></tr>
                                <?php else: foreach($requests as $req): $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1; ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900"><?= date('d/m/Y', strtotime($req['start_date'])) ?></div>
                                        <div class="text-xs text-slate-500">đến <?= date('d/m/Y', strtotime($req['end_date'])) ?></div>
                                    </td>
                                    <td class="px-6 py-4 max-w-[200px] truncate text-slate-600" title="<?= htmlspecialchars($req['reason']) ?>"><?= htmlspecialchars($req['reason']) ?></td>
                                    <td class="px-6 py-4 text-center"><span class="px-2 py-1 bg-slate-100 rounded-md font-medium text-slate-700"><?= $d ?> ngày</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <?php if($req['status'] == 'Pending'): ?>
                                            <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-bold mb-2">Chờ duyệt</span>
                                            <form method="POST" onsubmit="return confirm('Bạn chắc chắn hủy đơn này?');">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <button type="submit" name="cancel_leave" class="text-xs text-rose-600 hover:underline">Hủy đơn</button>
                                            </form>
                                        <?php elseif($req['status'] == 'Approved'): ?>
                                            <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">Đã duyệt</span>
                                        <?php else: ?>
                                            <span class="inline-block px-3 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-bold">Từ chối</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
function submitLeaveRequest() {
    const token = localStorage.getItem('jwt_token');
    if (!token) return alert("Lỗi: Bạn chưa đăng nhập!");
    fetch('/creative-agency-hub/public/api/leaves', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify({
            start_date: document.getElementById('start_date').value,
            end_date: document.getElementById('end_date').value,
            reason: document.getElementById('reason').value
        })
    }).then(r => r.json()).then(d => {
        if (d.status === 'success') { alert("✅ " + d.message); location.reload(); } else { alert("❌ Lỗi: " + d.message); }
    });
}
</script>
</body>
</html>