<?php
session_start();
require_once '../config/db_connect.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$emp_id = $_SESSION['employee_id'] ?? 3; 
$today = date('Y-m-d');
$month = date('m');
$year = date('Y');

$stmt = $pdo->prepare("SELECT check_in_time, check_out_time FROM attendances WHERE employee_id = ? AND work_date = ?");
$stmt->execute([$emp_id, $today]);
$attendance_today = $stmt->fetch();

$has_checked_in = !empty($attendance_today['check_in_time']);
$has_checked_out = !empty($attendance_today['check_out_time']);

$stmtDetails = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? ORDER BY work_date DESC");
$stmtDetails->execute([$emp_id, $month, $year]);
$raw_details = $stmtDetails->fetchAll();

$report = ['total_days' => count($raw_details), 'total_late' => 0, 'total_early' => 0, 'total_hours' => 0];
$details = []; 

foreach ($raw_details as $row) {
    $is_late = ($row['status'] == 'Late');
    $is_early_leave = false;
    $work_hours = 0;

    if ($is_late) $report['total_late']++;

    if (!empty($row['check_out_time'])) {
        $out_time_limit = date('Y-m-d 17:30:00', strtotime($row['work_date']));
        if ($row['check_out_time'] < $out_time_limit) {
            $is_early_leave = true;
            $report['total_early']++;
        }
        $in_sec = strtotime($row['check_in_time']);
        $out_sec = strtotime($row['check_out_time']);
        $work_hours = max(0, round(($out_sec - $in_sec - 5400) / 3600, 2)); 
        $report['total_hours'] += $work_hours;
    }

    $details[] = [
        'check_date' => $row['work_date'],
        'check_in' => $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : null,
        'check_out' => $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : null,
        'is_late' => $is_late,
        'is_early_leave' => $is_early_leave,
        'work_hours' => $work_hours
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Chấm Công - Creative Agency Hub</title>
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
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 bg-[#107ED2] text-white rounded-md text-sm font-medium" href="attendance.php"><span class="material-symbols-outlined">timer</span><span>Chấm công</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="leave_request.php"><span class="material-symbols-outlined">event_note</span><span>Đơn xin nghỉ</span></a>
            <a class="px-4 py-3 my-1 mx-2 flex items-center gap-3 text-slate-400 hover:text-white hover:bg-white/10 rounded-md text-sm font-medium transition-all" href="payroll_summary.php"><span class="material-symbols-outlined">payments</span><span>Bảng lương</span></a>
        </div>
    </nav>

    <main class="flex-1 ml-64 flex flex-col min-w-0 h-full overflow-y-auto">
        <header class="sticky top-0 z-40 flex justify-between items-center px-6 h-16 w-full bg-white border-b border-slate-200">
            <div class="font-bold text-lg">Hệ thống nhân sự</div>
            <a href="portal.php" class="text-sm text-[#107ED2] font-medium">Quay về Portal</a>
        </header>

        <div class="p-8 max-w-[1440px] mx-auto w-full flex flex-col gap-8 pb-24">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Ghi nhận thời gian làm việc</h1>
                <p class="text-slate-500 mt-1">Hôm nay: <?= date('d/m/Y') ?></p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Nút Checkin -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 text-center flex flex-col justify-center">
                    <span class="material-symbols-outlined text-[64px] text-[#107ED2] mb-4">schedule</span>
                    <button onclick="handleAttendance('checkin')" class="w-full py-3 bg-[#107ED2] text-white font-bold rounded-lg mb-3 hover:bg-blue-700 transition-colors disabled:opacity-50" <?= $has_checked_in ? 'disabled' : '' ?>>
                        BẮT ĐẦU CA LÀM
                    </button>
                    <button onclick="handleAttendance('checkout')" class="w-full py-3 bg-rose-50 text-rose-600 border border-rose-200 font-bold rounded-lg hover:bg-rose-100 transition-colors disabled:opacity-50" <?= (!$has_checked_in || $has_checked_out) ? 'disabled' : '' ?>>
                        KẾT THÚC CA LÀM
                    </button>
                    <?php if ($has_checked_in): ?>
                    <div class="mt-4 text-emerald-600 bg-emerald-50 py-2 rounded-lg text-sm font-medium">
                        Giờ vào ca: <?= date('H:i:s', strtotime($attendance_today['check_in_time'])) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Thống kê -->
                <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
                        <div class="p-4 bg-blue-50 text-blue-600 rounded-full"><span class="material-symbols-outlined text-2xl">calendar_month</span></div>
                        <div>
                            <div class="text-sm text-slate-500">Tổng ngày làm</div>
                            <div class="text-2xl font-bold"><?= $report['total_days'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
                        <div class="p-4 bg-cyan-50 text-cyan-600 rounded-full"><span class="material-symbols-outlined text-2xl">timelapse</span></div>
                        <div>
                            <div class="text-sm text-slate-500">Tổng giờ làm</div>
                            <div class="text-2xl font-bold"><?= $report['total_hours'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
                        <div class="p-4 bg-yellow-50 text-yellow-600 rounded-full"><span class="material-symbols-outlined text-2xl">warning</span></div>
                        <div>
                            <div class="text-sm text-slate-500">Số lần đi muộn</div>
                            <div class="text-2xl font-bold text-yellow-600"><?= $report['total_late'] ?? 0 ?></div>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex items-center gap-4">
                        <div class="p-4 bg-rose-50 text-rose-600 rounded-full"><span class="material-symbols-outlined text-2xl">directions_run</span></div>
                        <div>
                            <div class="text-sm text-slate-500">Số lần về sớm</div>
                            <div class="text-2xl font-bold text-rose-600"><?= $report['total_early'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng chi tiết -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 font-bold text-slate-800">Lịch sử chấm công chi tiết</div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Ngày</th>
                            <th class="px-6 py-3 font-medium">Giờ vào</th>
                            <th class="px-6 py-3 font-medium">Giờ ra</th>
                            <th class="px-6 py-3 font-medium">Số giờ</th>
                            <th class="px-6 py-3 font-medium">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if(empty($details)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Chưa có dữ liệu.</td></tr>
                        <?php else: foreach($details as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900"><?= date('d/m/Y', strtotime($row['check_date'])) ?></td>
                            <td class="px-6 py-3"><?= $row['check_in'] ?? '-' ?></td>
                            <td class="px-6 py-3"><?= $row['check_out'] ?? '-' ?></td>
                            <td class="px-6 py-3 font-medium text-[#107ED2]"><?= $row['work_hours'] ?> h</td>
                            <td class="px-6 py-3 flex gap-2">
                                <?php if($row['is_late']): ?><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Muộn</span><?php endif; ?>
                                <?php if($row['is_early_leave']): ?><span class="px-2 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-medium">Sớm</span><?php endif; ?>
                                <?php if(!$row['is_late'] && !$row['is_early_leave'] && $row['check_out']): ?><span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">Đúng giờ</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
<script>
function handleAttendance(action) {
    const token = localStorage.getItem('jwt_token');
    if (!token) return alert("Bạn chưa đăng nhập (Chưa có Token)!");
    const apiUrl = (action === 'checkin') ? '/creative-agency-hub/public/api/attendance/checkin' : '/creative-agency-hub/public/api/attendance/checkout';
    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token }
    }).then(r => r.json()).then(d => {
        if (d.status === 'success') { alert("✅ " + d.message); location.reload(); } else { alert("❌ Lỗi: " + d.message); }
    });
}
</script>
</body>
</html>