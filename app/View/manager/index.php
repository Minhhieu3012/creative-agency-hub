<?php
$pageTitle = 'Trung tâm quản lý | Creative Agency Hub';
$pageCss = ['role-home.css'];
$pageJs = ['app.js', 'forms.js', 'toast.js'];
$activeMenu = 'manager_home';
$topbarTitle = 'Trung tâm quản lý';
$brandName = 'Creative Agency Hub';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

ob_start();
?>

<section class="role-home role-home-manager" data-manager-home>
    <div class="role-hero">
        <div class="role-hero-copy">
            <span class="role-kicker">Manager Workspace • Creative Agency Hub</span>
            <h1>Điều phối dự án, task và đội ngũ trong một màn hình.</h1>
            <p>
                Theo dõi tiến độ, phân công đầu việc, duyệt kết quả và kiểm soát hoạt động
                của team theo dữ liệu thật từ project/task.
            </p>

            <div class="role-hero-actions">
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php">
                    Mở dự án
                </a>
                <a class="btn btn-ghost" href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/manager_approvals.php">
                    Xem phê duyệt
                </a>
            </div>
        </div>

        <div class="role-hero-panel">
            <div class="role-panel-row">
                <span>Vai trò</span>
                <strong>Manager</strong>
            </div>
            <div class="role-panel-row">
                <span>Trọng tâm hôm nay</span>
                <strong>Dự án & Task</strong>
            </div>
            <div class="role-panel-row">
                <span>Ưu tiên</span>
                <strong>Duyệt tiến độ</strong>
            </div>
            <div class="role-panel-row">
                <span>Trạng thái</span>
                <strong>Đang hoạt động</strong>
            </div>
        </div>
    </div>

    <div class="role-stat-grid">
        <article class="role-stat-card">
            <span class="role-stat-icon">▣</span>
            <div>
                <h3>Dự án đang quản lý</h3>
                <strong data-manager-total-projects>0</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">☑</span>
            <div>
                <h3>Task đang mở</h3>
                <strong data-manager-total-tasks>0</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">◉</span>
            <div>
                <h3>Employee tham gia</h3>
                <strong data-manager-total-members>0</strong>
            </div>
        </article>

        <article class="role-stat-card">
            <span class="role-stat-icon">◇</span>
            <div>
                <h3>Tiến độ trung bình</h3>
                <strong><span data-manager-average-progress>0</span>%</strong>
            </div>
        </article>
    </div>

    <div class="role-layout">
        <article class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Project đang quản lý</h2>
                    <p>Dữ liệu lấy từ database qua API project thật.</p>
                </div>
                <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php">
                    Xem tất cả
                </a>
            </div>

            <div class="role-card-body">
                <div class="role-list" data-manager-project-list>
                    <div class="role-list-item">
                        <span class="role-list-icon">…</span>
                        <div class="role-list-content">
                            <h3>Đang tải dữ liệu...</h3>
                            <p>Vui lòng chờ trong giây lát.</p>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <aside class="role-card">
            <div class="role-card-header">
                <div>
                    <h2>Truy cập nhanh</h2>
                    <p>Các khu vực manager dùng nhiều nhất.</p>
                </div>
            </div>

            <div class="role-card-body">
                <div class="role-quick-grid">
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php">
                        <span>Dự án</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
                        <span>Bảng Kanban</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/gantt.php">
                        <span>Gantt Chart</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/payroll/manager_approvals.php">
                        <span>Phê duyệt</span>
                        <span>→</span>
                    </a>
                    <a class="role-quick-link" href="<?php echo htmlspecialchars($viewUrl); ?>/hrm/employees.php">
                        <span>Nhân sự</span>
                        <span>→</span>
                    </a>
                </div>

                <div class="role-note" style="margin-top: 18px;">
                    <h3>Luồng nhiều employee</h3>
                    <p>
                        Manager tạo nhiều task trong cùng project, mỗi task giao cho một employee khác nhau.
                        Project sẽ tự tổng hợp employee từ các task đó.
                    </p>
                </div>
            </div>
        </aside>
    </div>
</section>

<script>
(function () {
    const root = document.querySelector("[data-manager-home]");
    if (!root) return;

    const totalProjectsEl = document.querySelector("[data-manager-total-projects]");
    const totalTasksEl = document.querySelector("[data-manager-total-tasks]");
    const totalMembersEl = document.querySelector("[data-manager-total-members]");
    const avgProgressEl = document.querySelector("[data-manager-average-progress]");
    const listEl = document.querySelector("[data-manager-project-list]");

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString("vi-VN");
    }

    function statusLabel(status) {
        const map = {
            Active: "Đang triển khai",
            Completed: "Hoàn thành",
            Archived: "Lưu trữ"
        };

        return map[status] || status || "Đang triển khai";
    }

    function renderEmpty(message) {
        if (!listEl) return;

        listEl.innerHTML = `
            <div class="role-list-item">
                <span class="role-list-icon">▣</span>
                <div class="role-list-content">
                    <h3>Chưa có dữ liệu</h3>
                    <p>${escapeHtml(message || "Bạn chưa có project nào.")}</p>
                </div>
                <a class="badge badge-primary" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php">Tạo</a>
            </div>
        `;
    }

    function renderProjects(projects) {
        const totalProjects = projects.length;
        const totalTasks = projects.reduce((sum, project) => sum + Number(project.task_count || 0), 0);
        const totalMembers = projects.reduce((sum, project) => sum + Number(project.member_count || 0), 0);
        const avgProgress = totalProjects
            ? Math.round(projects.reduce((sum, project) => sum + Number(project.progress || 0), 0) / totalProjects)
            : 0;

        if (totalProjectsEl) totalProjectsEl.textContent = formatNumber(totalProjects);
        if (totalTasksEl) totalTasksEl.textContent = formatNumber(totalTasks);
        if (totalMembersEl) totalMembersEl.textContent = formatNumber(totalMembers);
        if (avgProgressEl) avgProgressEl.textContent = formatNumber(avgProgress);

        if (!projects.length) {
            renderEmpty("Tạo project đầu tiên để bắt đầu giao task cho employee.");
            return;
        }

        if (!listEl) return;

        listEl.innerHTML = projects.slice(0, 5).map((project, index) => {
            const progress = Math.max(0, Math.min(100, Number(project.progress || 0)));

            return `
                <div class="role-list-item">
                    <span class="role-list-icon">${index + 1}</span>
                    <div class="role-list-content">
                        <h3>${escapeHtml(project.name)}</h3>
                        <p>
                            ${escapeHtml(statusLabel(project.status))}
                            · ${formatNumber(project.task_count)} task
                            · ${formatNumber(project.member_count)} employee
                            · ${progress}% hoàn thành
                        </p>
                        <div class="role-progress" style="margin-top: 10px;">
                            <div class="role-progress-row">
                                <span>Tiến độ</span>
                                <span>${progress}%</span>
                            </div>
                            <div class="role-progress-track">
                                <div class="role-progress-fill" style="width: ${progress}%;"></div>
                            </div>
                        </div>
                    </div>
                    <a class="badge badge-success" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/projects.php">
                        Xem
                    </a>
                </div>
            `;
        }).join("");
    }

    async function loadManagerProjects() {
        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn?.()) {
            renderEmpty("Bạn cần đăng nhập manager để xem dữ liệu.");
            return;
        }

        try {
            const response = await CAHApi.get("/api/projects", {
                loading: false
            });

            const projects = Array.isArray(response.data) ? response.data : [];
            renderProjects(projects);
        } catch (error) {
            renderEmpty(error.message || "Không tải được dữ liệu project.");
        }
    }

    loadManagerProjects();
})();
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/app.php';
?>