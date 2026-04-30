<!-- Trang số 14 -->
 <?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['token'])) {
    header('Location: login-client.php');
    exit;
}
$client_name = $_SESSION['client_name'] ?? 'Khách hàng';

// Lấy project_id từ query string (nếu có) và danh sách task từ DB
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
$project = null;
$tasks = [];
try {
    $pdo = \Core\Database::getConnection();
    if ($project_id) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
    }
    if (!$project) {
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY id LIMIT 1");
        $project = $stmt->fetch();
        $project_id = $project['id'] ?? null;
    }

    if ($project_id) {
        $taskStmt = $pdo->prepare("SELECT id, title, description, status, priority, deadline, created_at FROM tasks WHERE project_id = ? ORDER BY id DESC");
        $taskStmt->execute([$project_id]);
        $tasks = $taskStmt->fetchAll();
    }
} catch (\Exception $e) {
    $tasks = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Tiến độ & Phản hồi - Client Portal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              colors: {
                  "primary": "#003827", "primary-container": "#00513a", "on-primary": "#ffffff",
                  "secondary": "#9e412c", "secondary-container": "#ff8b71", "on-secondary": "#ffffff",
                  "surface": "#f8faf6", "on-surface": "#191c1a", "surface-container-lowest": "#ffffff",
                  "surface-container-low": "#f2f4f0", "surface-container": "#ecefeb", "surface-container-highest": "#e1e3df",
                  "outline": "#707973", "outline-variant": "#bfc9c2", "background": "#f8faf6", "on-background": "#191c1a",
                  "error": "#ba1a1a", "on-error": "#ffffff", "error-container": "#ffdad6", "on-surface-variant": "#404944"
              },
              fontFamily: { "h1": ["Manrope"], "h2": ["Manrope"], "h3": ["Manrope"], "body-md": ["Manrope"], "label-md": ["Manrope"], "label-sm": ["Manrope"], "body-sm": ["Manrope"], "body-lg": ["Manrope"] }
            }
          }
        }
    </script>
</head>
<body class="bg-background text-on-background font-body-md h-screen flex overflow-hidden">
    
<!-- SideNavBar -->
<nav id="mobile-sidenav" class="bg-surface-container-lowest font-manrope text-sm font-medium h-screen w-64 border-r flex flex-col border-outline-variant/30 fixed left-0 top-0 z-40 hidden md:flex">
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
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-all" href="#"><span class="material-symbols-outlined">grid_view</span> Dashboard</a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg bg-surface-container text-primary-container shadow-sm border-r-4 border-primary-container font-bold" href="projects.php"><span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">assignment</span> All Projects</a>
        <a class="flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-all" href="#"><span class="material-symbols-outlined">event_note</span> Timeline</a>
    </div>
    <div class="p-4 border-t border-outline-variant/30">
        <button class="w-full bg-primary-container text-on-primary font-bold py-3 rounded-lg mb-4 flex items-center justify-center gap-2 hover:bg-primary transition-colors"><span class="material-symbols-outlined text-[18px]">add</span> New Feedback</button>
        <a class="flex items-center gap-3 px-4 py-2 rounded-lg text-on-surface-variant hover:bg-surface-container-low text-sm" href="logout.php"><span class="material-symbols-outlined text-[18px]">logout</span> Đăng xuất</a>
    </div>
</nav>

<!-- Main Canvas -->
<main class="flex-1 flex flex-col md:ml-64 bg-background min-h-screen">
    <!-- TopNavBar -->
    <header class="bg-surface-container-lowest font-manrope border-b z-50 border-outline-variant/30 flex justify-between items-center w-full px-6 py-3 h-16 sticky top-0">
        <button id="mobile-nav-toggle" class="md:hidden mr-2 p-2 rounded-lg bg-surface-container text-on-surface-variant">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="flex items-center gap-4">
            <span class="text-xl font-extrabold text-primary-container">ProjectHub</span>
            <div class="hidden sm:flex items-center text-sm text-on-surface-variant gap-2 ml-4">
                <a href="projects.php" class="hover:underline">All Projects</a>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                <span class="text-on-background font-bold">Alpha Redesign</span>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                <button class="p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full relative"><span class="material-symbols-outlined">notifications</span><span class="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full"></span></button>
                <div class="h-6 w-px bg-outline-variant/50 mx-2"></div>
                <div class="flex items-center gap-2 pr-3">
                    <div class="w-8 h-8 rounded-full bg-secondary-container text-secondary flex items-center justify-center font-bold">C</div>
                    <span class="text-sm font-bold text-on-background"><?= htmlspecialchars($client_name) ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Content Area (Giữ nguyên cấu trúc của file mẫu) -->
    <div class="flex-1 overflow-y-auto p-6 md:p-8 lg:px-12">
        <div class="max-w-[1280px] mx-auto space-y-8">
            <!-- Page Header & Status Card -->
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0_4px_24px_rgba(0,0,0,0.04)] border border-outline-variant/30 flex flex-col md:flex-row md:items-start justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 bg-[#acf1d1]/20 text-primary-container font-bold text-xs rounded-full border border-[#acf1d1]/50 uppercase tracking-wider"><?= htmlspecialchars($project['status'] ?? 'Active') ?></span>
                        <span class="text-on-surface-variant text-sm flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">calendar_today</span> Cập nhật: <?= htmlspecialchars($project['created_at'] ?? '') ?></span>
                    </div>
                    <h2 class="text-3xl font-bold text-on-background mb-4"><?= htmlspecialchars($project['name'] ?? 'Dự án') ?></h2>
                    <p class="text-lg text-on-surface-variant max-w-2xl"><?= htmlspecialchars($project['description'] ?? '') ?></p>
                </div>
                <div class="flex flex-col gap-4 min-w-[200px]">
                    <div class="bg-surface-container-low rounded-lg p-4 border border-outline-variant/50">
                        <div class="flex justify-between items-end mb-2">
                            <span class="font-bold text-sm text-on-surface-variant">Tiến độ tổng thể</span>
                            <span class="text-xl font-bold text-primary-container">65%</span>
                        </div>
                        <div class="w-full bg-surface-container-highest rounded-full h-2">
                            <div class="bg-primary-container h-2 rounded-full" style="width: 65%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Timeline -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-on-background flex items-center gap-2"><span class="material-symbols-outlined text-primary-container">timeline</span> Dòng thời gian dự án</h3>
                        <button class="text-primary-container text-sm font-bold hover:underline">Xem tất cả</button>
                    </div>
                    <div class="bg-surface-container-lowest rounded-xl p-8 shadow-[0_4px_24px_rgba(0,0,0,0.04)] border border-outline-variant/30 relative">
                        <div class="absolute left-[47px] top-12 bottom-12 w-[2px] bg-surface-container-highest"></div>
                        <div class="space-y-8 relative">
                            <?php if (!empty($tasks)): ?>
                                <?php foreach ($tasks as $t): ?>
                                    <div class="flex gap-6 group">
                                        <div class="relative z-10 flex flex-col items-center mt-1">
                                            <div class="w-10 h-10 rounded-full <?= $t['status'] === 'Done' ? 'bg-primary-container' : 'bg-white' ?> flex items-center justify-center border-4 border-surface-container-lowest shadow-sm <?= $t['status'] === 'Done' ? '' : 'ring-2 ring-primary-container' ?>">
                                                <span class="material-symbols-outlined text-on-primary text-[20px]"><?= $t['status'] === 'Done' ? 'check' : 'adjust' ?></span>
                                            </div>
                                        </div>
                                        <div class="flex-1 pb-6">
                                            <div class="flex justify-between items-start mb-1">
                                                <h4 class="font-bold text-on-background text-[16px]"><?= htmlspecialchars($t['title']) ?></h4>
                                                <span class="text-xs font-bold text-on-surface-variant"><?= htmlspecialchars($t['deadline'] ?? '') ?></span>
                                            </div>
                                            <p class="text-sm text-on-surface-variant mb-3"><?= htmlspecialchars($t['description'] ?? '') ?></p>
                                            <div class="flex gap-2">
                                                <span class="px-2 py-1 bg-surface-container rounded text-xs font-medium text-on-surface-variant border border-outline-variant/50"><?= htmlspecialchars($t['priority'] ?? '') ?></span>
                                                <span class="px-2 py-1 bg-surface-container rounded text-xs font-medium text-on-surface-variant border border-outline-variant/50"><?= htmlspecialchars($t['status']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-on-surface-variant">Chưa có công việc trong dự án này.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Feedback -->
                <div class="space-y-6">
                    <h3 class="text-xl font-bold text-on-background flex items-center gap-2"><span class="material-symbols-outlined text-secondary">forum</span> Gửi phản hồi cho chúng tôi</h3>
                    <div class="bg-surface-container-lowest rounded-xl shadow-[0_4px_24px_rgba(0,0,0,0.04)] border border-outline-variant/30 flex flex-col overflow-hidden">
                        <div class="p-5 border-b border-outline-variant/30 bg-surface-container-low/50">
                            <label class="font-bold text-sm text-on-background block mb-2">Chủ đề phản hồi</label>
                            <input class="w-full bg-white border border-outline-variant rounded-md px-3 py-2 text-sm focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none" placeholder="Vd: Nhận xét về màu sắc..." type="text"/>
                        </div>
                        <div class="p-5 flex-1">
                            <textarea class="w-full min-h-[120px] bg-transparent border-none resize-none p-0 text-sm focus:ring-0 outline-none placeholder:text-on-surface-variant/60" placeholder="Mô tả chi tiết phản hồi của bạn..."></textarea>
                        </div>
                        <div class="p-4 border-t border-outline-variant/30 flex justify-between items-center bg-surface-container-lowest">
                            <button class="p-2 text-on-surface-variant hover:bg-surface-container rounded-full"><span class="material-symbols-outlined text-[20px]">attach_file</span></button>
                            <button class="bg-primary-container text-on-primary font-bold text-sm px-6 py-2 rounded-lg hover:bg-primary transition-colors flex items-center gap-2"><span>Gửi ngay</span><span class="material-symbols-outlined text-[18px]">send</span></button>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-xs text-on-surface-variant mb-4 uppercase tracking-wider">Lịch sử trao đổi</h4>
                        <div class="bg-surface-container-lowest rounded-lg p-4 border border-outline-variant/30 shadow-sm">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-secondary-container text-secondary flex items-center justify-center font-bold text-[10px]">C</div>
                                    <span class="font-bold text-xs text-on-background">Client (Bạn)</span>
                                </div>
                                <span class="text-[11px] text-on-surface-variant">Hôm qua</span>
                            </div>
                            <p class="text-sm text-on-surface-variant line-clamp-2 mb-3">Font chữ hiện tại ở phần tiêu đề hơi nhỏ, có thể tăng lên một chút để dễ đọc hơn không?</p>
                            <div class="flex items-center gap-2 text-primary-container bg-[#acf1d1]/20 px-3 py-1.5 rounded-md inline-flex border border-[#acf1d1]/50">
                                <span class="material-symbols-outlined text-[16px]">reply</span>
                                <span class="font-bold text-[11px]">Đã phản hồi (1)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
<script>
// Mobile sidebar toggle for tasks page
;(function(){
    const btn = document.getElementById('mobile-nav-toggle');
    const nav = document.getElementById('mobile-sidenav');
    if (!btn || !nav) return;
    btn.addEventListener('click', ()=>{
        nav.classList.toggle('hidden');
    });
})();
</script>
</body></html>