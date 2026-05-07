<?php
// Bật lỗi để kiểm tra nếu trang bị trắng
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Quản lý dự án | Creative Agency Hub';
$pageCss = ['tasks.css', 'dashboard.css'];
$pageJs = ['dashboard.js'];
$activeMenu = 'projects';
$topbarTitle = 'Dự án';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Quản lý Dự án';
$pageSubtitle = 'Theo dõi tiến độ, phân bổ nhân sự và kiểm soát trạng thái các dự án đang vận hành.';
$pageAction = '<a class="btn btn-light" href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a><a class="btn btn-primary" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Mở Kanban</a>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="task-shell">
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">▣</div>
            <div class="stat-card-body">
                <span>Tổng dự án</span>
                <strong id="js-stat-total-projects">0</strong>
                <small>Đang theo dõi</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">☑</div>
            <div class="stat-card-body">
                <span>Task đang mở</span>
                <strong id="js-stat-open-tasks">0</strong>
                <small>Tổng cộng</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◔</div>
            <div class="stat-card-body">
                <span>Tiến độ TB</span>
                <strong><span id="js-stat-avg-progress">0</span>%</strong>
                <small>Toàn hệ thống</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Rủi ro deadline</span>
                <strong id="js-stat-risk-deadlines">0</strong>
                <small>Cần xử lý</small>
            </div>
        </article>
    </div>

    <div class="task-filter-bar">
        <div class="input-with-icon">
            <span class="input-icon">⌕</span>
            <input id="js-search-input" class="form-control" type="search" placeholder="Tìm kiếm dự án...">
        </div>

        <select id="js-status-filter" class="form-select">
            <option value="">Tất cả trạng thái</option>
            <option value="Active">Đang triển khai</option>
            <option value="Completed">Hoàn thành</option>
            <option value="Archived">Đã lưu trữ</option>
        </select>

        <select id="js-sort-filter" class="form-select">
            <option value="deadline">Deadline gần nhất</option>
            <option value="progress">Tiến độ cao nhất</option>
            <option value="risk">Rủi ro cao nhất</option>
        </select>

        <button class="btn btn-soft" type="button" id="js-btn-filter">Lọc dữ liệu</button>
    </div>

    <section class="project-grid" id="js-project-grid">
        <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Đang tải dữ liệu dự án...</p>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    const baseUrl = '/creative-agency-hub';
    const projectGrid = document.getElementById('js-project-grid');

    const statusLabels = {
        Active: 'Đang triển khai',
        Completed: 'Hoàn thành',
        Archived: 'Đã lưu trữ'
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (!token) {
        projectGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: red; padding: 40px;">Lỗi: Bạn chưa đăng nhập hoặc Token đã mất. Vui lòng đăng nhập lại.</p>';
        return;
    }

    let allProjects = [];

    const renderProjects = (projects) => {
        if (!projects || projects.length === 0) {
            projectGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Không tìm thấy dự án nào phù hợp.</p>';
            return;
        }

        projectGrid.innerHTML = projects.map(project => {
            const progress = parseInt(project.progress) || 0;
            const taskCount = parseInt(project.tasks) || 0;
            const openTasks = parseInt(project.open_tasks) || 0;
            const members = parseInt(project.members) || 0;
            const riskTasks = parseInt(project.risk_tasks) || 0;
            const extraMembers = Math.max(0, members - 2);
            const statusText = project.is_virtual
                ? 'Chưa gán dự án'
                : (statusLabels[project.status] || project.status || 'Khởi tạo');

            const boardUrl = project.is_virtual
                ? '/creative-agency-hub/app/View/tasks/kanban.php'
                : `/creative-agency-hub/app/View/tasks/kanban.php?project_id=${encodeURIComponent(project.id || '')}`;

            return `
            <article class="project-card">
                <div class="project-card-head">
                    <div class="project-card-title-row">
                        <h2>${escapeHtml(project.name || 'Chưa đặt tên')}</h2>
                        <span class="project-status-pill">${escapeHtml(statusText)}</span>
                    </div>
                    <p>${escapeHtml(project.description || 'Chưa có mô tả')}</p>
                </div>

                <div class="project-card-meta">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: ${progress}%;"></div>
                    </div>

                    <div class="project-progress-meta">
                        <span>${progress}% hoàn thành</span>
                        <span>Deadline: ${escapeHtml(project.deadline || 'Chưa xác định')}</span>
                    </div>

                    <div class="project-stat-row">
                        <div class="project-mini-stat">
                            <strong>${taskCount}</strong>
                            <span>Tasks</span>
                        </div>
                        <div class="project-mini-stat">
                            <strong>${members}</strong>
                            <span>Members</span>
                        </div>
                        <div class="project-mini-stat">
                            <strong>${riskTasks}</strong>
                            <span>Risks</span>
                        </div>
                    </div>
                </div>

                <div class="project-card-footer">
                    <div class="avatar-stack">
                        <span>${escapeHtml((project.manager_name || 'CA').charAt(0).toUpperCase())}</span>
                        <span>${openTasks}</span>
                        <span>+${extraMembers}</span>
                    </div>
                    <a href="${boardUrl}" class="btn btn-light">Xem bảng</a>
                </div>
            </article>
            `;
        }).join('');
    };

    const updateStats = (projects) => {
        const total = projects.length;
        const openTasks = projects.reduce((sum, p) => sum + (parseInt(p.open_tasks ?? p.tasks) || 0), 0);
        const avgProgress = total > 0 ? Math.round(projects.reduce((sum, p) => sum + (parseInt(p.progress) || 0), 0) / total) : 0;
        const riskDeadlines = projects.reduce((sum, p) => sum + (parseInt(p.risk_tasks) || 0), 0);

        document.getElementById('js-stat-total-projects').innerText = total;
        document.getElementById('js-stat-open-tasks').innerText = openTasks;
        document.getElementById('js-stat-avg-progress').innerText = avgProgress;
        document.getElementById('js-stat-risk-deadlines').innerText = riskDeadlines;
    };

    const filterData = () => {
        const searchVal = document.getElementById('js-search-input').value.toLowerCase();
        const statusVal = document.getElementById('js-status-filter').value;
        const sortVal = document.getElementById('js-sort-filter').value;

        let filtered = [...allProjects];

        if (searchVal) {
            filtered = filtered.filter(p =>
                (p.name && p.name.toLowerCase().includes(searchVal)) ||
                (p.description && p.description.toLowerCase().includes(searchVal))
            );
        }

        if (statusVal) {
            filtered = filtered.filter(p => p.status === statusVal);
        }

        if (sortVal === 'progress') {
            filtered.sort((a, b) => (parseInt(b.progress) || 0) - (parseInt(a.progress) || 0));
        } else if (sortVal === 'risk') {
            filtered.sort((a, b) => (parseInt(b.risk_tasks) || 0) - (parseInt(a.risk_tasks) || 0));
        } else {
            filtered.sort((a, b) => String(a.deadline || '9999-12-31').localeCompare(String(b.deadline || '9999-12-31')));
        }

        renderProjects(filtered);
    };

    const loadData = () => {
        fetch(`${baseUrl}/public/api/projects?_=${Date.now()}`, {
            cache: 'no-store',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        })
        .then(async res => {
            const text = await res.text();

            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Lỗi parse JSON:", text);
                throw new Error("API lỗi hoặc Server sập. Vui lòng kiểm tra Console F12.");
            }
        })
        .then(res => {
            if (res.status === 'error') {
                projectGrid.innerHTML = `<p style="grid-column: 1 / -1; color: red; text-align: center;"><b>Lỗi Backend:</b> ${escapeHtml(res.message)}</p>`;
                return;
            }

            allProjects = Array.isArray(res.data) ? res.data : [];
            updateStats(allProjects);
            filterData();
        })
        .catch(error => {
            projectGrid.innerHTML = `<p style="grid-column: 1 / -1; color: red; text-align: center;"><b>Lỗi JS:</b> ${escapeHtml(error.message)}</p>`;
        });
    };

    document.getElementById('js-search-input').addEventListener('input', filterData);
    document.getElementById('js-status-filter').addEventListener('change', filterData);
    document.getElementById('js-sort-filter').addEventListener('change', filterData);
    document.getElementById('js-btn-filter').addEventListener('click', filterData);

    loadData();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>