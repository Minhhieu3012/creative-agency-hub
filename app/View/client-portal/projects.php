<!-- Trang số 13 -->
 <?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Bắt buộc phải đăng nhập (token) để xem trang client
if (empty($_SESSION['token'])) {
    header('Location: login-client.php');
    exit;
}

$client_name = $_SESSION['client_name'] ?? 'Khách hàng';

// Lấy danh sách projects + thống kê tiến độ từ DB
$projects = [];
try {
    $pdo = \Core\Database::getConnection();
    $stmt = $pdo->query("SELECT id, name, description, status, created_at FROM projects ORDER BY id DESC");
    $projects = $stmt->fetchAll();

    foreach ($projects as &$p) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status='Done') AS done FROM tasks WHERE project_id = ?");
        $countStmt->execute([$p['id']]);
        $stats = $countStmt->fetch();
        $total = (int)($stats['total'] ?? 0);
        $done = (int)($stats['done'] ?? 0);
        $p['total_tasks'] = $total;
        $p['done_tasks'] = $done;
        $p['progress'] = $total > 0 ? (int)round($done / $total * 100) : 0;
    }
} catch (\Exception $e) {
    $projects = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Danh sách Dự án - Client Portal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                  "primary": "#003827", "primary-container": "#00513a", "on-primary": "#ffffff",
                  "secondary": "#9e412c", "secondary-container": "#ff8b71",
                  "surface": "#f8faf6", "on-surface": "#191c1a", "surface-container-lowest": "#ffffff",
                  "surface-container": "#ecefeb", "surface-container-highest": "#e1e3df",
                  "outline": "#707973", "outline-variant": "#bfc9c2", "background": "#f8faf6",
                  "error": "#ba1a1a", "error-container": "#ffdad6", "on-surface-variant": "#404944"
              },
              fontFamily: { "sans": ["Manrope", "sans-serif"] }
            }
          }
        }
    </script>
</head>
<body class="bg-background text-on-background font-sans h-screen flex overflow-hidden">
    
<!-- SideNavBar -->
<nav id="mobile-sidenav" class="bg-surface-container-lowest text-sm font-medium h-screen w-64 border-r flex flex-col border-outline-variant/30 fixed left-0 top-0 z-40 hidden md:flex">
    <div class="p-6 border-b border-outline-variant/30">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary-container flex items-center justify-center text-white"><span class="material-symbols-outlined">energy_savings_leaf</span></div>
            <div>
                <h1 class="text-lg font-bold text-on-background">Client Portal</h1>
                <p class="text-on-surface-variant text-xs">Enterprise View</p>
            </div>
        </div>
    </div>
    <div class="flex-1 py-6 px-4 space-y-2 overflow-y-auto">
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-surface-container transition-all" href="#"><span class="material-symbols-outlined">grid_view</span> Dashboard</a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg bg-surface-container text-primary-container shadow-sm border-r-4 border-primary-container font-bold" href="projects.php"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">assignment</span> All Projects</a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-surface-container transition-all" href="#"><span class="material-symbols-outlined">event_note</span> Timeline</a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-surface-container transition-all" href="#"><span class="material-symbols-outlined">folder_open</span> Documents</a>
    </div>
    <div class="p-4 border-t border-outline-variant/30">
        <button class="w-full bg-primary-container text-on-primary font-bold py-3 rounded-lg mb-4 flex items-center justify-center gap-2 hover:bg-primary transition-colors"><span class="material-symbols-outlined text-[18px]">add</span> New Feedback</button>
        <a class="flex items-center gap-3 px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container text-sm" href="logout.php"><span class="material-symbols-outlined text-[18px]">logout</span> Đăng xuất</a>
    </div>
</nav>

<main class="flex-1 flex flex-col md:ml-64 bg-background min-h-screen">
    <!-- Header -->
    <header class="bg-surface-container-lowest border-b z-50 border-outline-variant/30 flex justify-end items-center w-full px-6 py-3 h-16 sticky top-0">
        <button id="mobile-nav-toggle" class="md:hidden mr-auto p-2 rounded-lg bg-surface-container text-on-surface-variant">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="flex items-center gap-2 pr-3">
            <div class="w-8 h-8 rounded-full bg-secondary-container text-secondary flex items-center justify-center font-bold">C</div>
            <span class="text-sm font-bold text-on-background"><?= htmlspecialchars($client_name) ?></span>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-6 md:p-8 lg:px-12">
        <div class="max-w-[1280px] mx-auto space-y-8">
            
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-extrabold text-on-background mb-1">Danh sách Dự án</h1>
                    <p class="text-on-surface-variant">Tổng quan về tất cả các dự án đang được tài trợ.</p>
                </div>
                <div class="flex gap-3">
                    <button class="px-4 py-2 rounded-lg border border-outline-variant/50 bg-surface-container-lowest text-on-background font-bold flex items-center gap-2 hover:bg-surface-container"><span class="material-symbols-outlined text-[18px]">filter_list</span> Lọc</button>
                    <button class="px-4 py-2 rounded-lg bg-primary-container text-on-primary font-bold flex items-center gap-2 hover:bg-primary"><span class="material-symbols-outlined text-[18px]">download</span> Xuất báo cáo</button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Stat 1 -->
                <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-outline-variant/30 flex flex-col justify-between h-36">
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">Tổng số dự án</span>
                        <div class="w-10 h-10 rounded-full bg-[#acf1d2] text-primary-container flex items-center justify-center"><span class="material-symbols-outlined">workspaces</span></div>
                    </div>
                    <div>
                        <div class="text-5xl font-black text-on-background">12</div>
                        <div class="text-sm text-primary-container font-medium mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">trending_up</span> +2 tháng này</div>
                    </div>
                </div>
                <!-- Stat 2 -->
                <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-outline-variant/30 flex flex-col justify-between h-36 relative overflow-hidden">
                    <div class="flex justify-between items-start relative z-10">
                        <span class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">Tiến độ tổng thể</span>
                        <div class="w-10 h-10 rounded-full bg-[#acf1d2] text-primary-container flex items-center justify-center"><span class="material-symbols-outlined">bar_chart</span></div>
                    </div>
                    <div class="relative z-10">
                        <div class="text-5xl font-black text-on-background">68%</div>
                        <div class="w-3/4 bg-surface-container-highest rounded-full h-2 mt-3"><div class="bg-primary-container h-2 rounded-full" style="width: 68%"></div></div>
                    </div>
                    <!-- Vòng tròn trang trí -->
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 rounded-full border-[12px] border-surface-container"></div>
                </div>
                <!-- Stat 3 -->
                <div class="bg-surface-container-lowest rounded-xl p-6 shadow-sm border border-outline-variant/30 flex flex-col justify-between h-36">
                    <div class="flex justify-between items-start">
                        <span class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">Yêu cầu phản hồi</span>
                        <div class="w-10 h-10 rounded-full bg-[#ffdad6] text-[#ba1a1a] flex items-center justify-center"><span class="material-symbols-outlined">campaign</span></div>
                    </div>
                    <div>
                        <div class="text-5xl font-black text-on-background">3</div>
                        <div class="text-sm text-[#ba1a1a] font-bold mt-1 flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">error</span> Cần xử lý gấp</div>
                    </div>
                </div>
            </div>

            <!-- Project Cards Grid -->
            <div>
                <h2 class="text-2xl font-bold text-on-background mb-6">Chi tiết Dự án</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $p): ?>
                            <div class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/30 flex flex-col cursor-pointer hover:shadow-md transition-shadow">
                                <div class="p-6 flex-1">
                                    <div class="flex justify-between items-start mb-4">
                                        <span class="px-3 py-1 bg-[#acf1d2]/40 text-primary-container text-xs font-bold rounded-full flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                            <?= htmlspecialchars($p['status'] ?? 'Active') ?>
                                        </span>
                                        <a href="tasks.php?project_id=<?= urlencode($p['id']) ?>" class="text-on-surface-variant"><span class="material-symbols-outlined">more_vert</span></a>
                                    </div>
                                    <h3 class="text-xl font-bold text-on-background mb-2"><?= htmlspecialchars($p['name']) ?></h3>
                                    <p class="text-sm text-on-surface-variant line-clamp-2"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                                </div>
                                <div class="px-6 pb-6 border-b border-outline-variant/20">
                                    <div class="flex justify-between text-xs font-bold text-on-background mb-2"><span>Tiến độ</span><span class="text-primary-container"><?= htmlspecialchars($p['progress']) ?>%</span></div>
                                    <div class="w-full bg-surface-container-highest rounded-full h-2"><div class="bg-primary-container h-2 rounded-full" style="width: <?= htmlspecialchars($p['progress']) ?>%"></div></div>
                                </div>
                                <div class="p-6 bg-surface-container-lowest rounded-b-xl">
                                    <div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
                                        <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Tổng công việc</div>
                                        <div class="text-sm font-semibold text-on-background flex items-start gap-2"><span class="material-symbols-outlined text-[16px] text-primary-container">flag</span> <?= intval($p['total_tasks']) ?> công việc</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full bg-surface-container-lowest rounded-xl p-8 text-center border border-outline-variant/30">Chưa có dự án nào. Vui lòng liên hệ quản trị viên.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
</body>
<script>
// Mobile sidebar toggle
;(function(){
    const btn = document.getElementById('mobile-nav-toggle');
    const nav = document.getElementById('mobile-sidenav');
    if (!btn || !nav) return;
    btn.addEventListener('click', ()=>{
        nav.classList.toggle('hidden');
    });
})();
</script>
</html>