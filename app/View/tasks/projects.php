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
            <option value="Đang triển khai">Đang triển khai</option>
            <option value="Đang kiểm tra">Đang kiểm tra</option>
            <option value="Lên kế hoạch">Lên kế hoạch</option>
        </select>

        <select class="form-select">
            <option>Deadline gần nhất</option>
            <option>Tiến độ cao nhất</option>
            <option>Rủi ro cao nhất</option>
        </select>

        <button class="btn btn-soft" type="button" id="js-btn-filter">Lọc dữ liệu</button>
    </div>

    <section class="project-grid" id="js-project-grid">
        <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Đang tải dữ liệu dự án...</p>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub';
    const projectGrid = document.getElementById('js-project-grid');

    // 1. Kiểm tra Token
    if (!token) {
        projectGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: red; padding: 40px;">Lỗi: Bạn chưa đăng nhập hoặc Token đã mất. Vui lòng đăng nhập lại.</p>';
        return;
    }

    // Biến lưu trữ dữ liệu gốc để lọc Client-side
    let allProjects = [];

    // 2. Hàm render giao diện Card dự án
    const renderProjects = (projects) => {
        if (!projects || projects.length === 0) {
            projectGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Không tìm thấy dự án nào phù hợp.</p>';
            return;
        }

        projectGrid.innerHTML = projects.map(project => {
            const progress = parseInt(project.progress) || 0;
            const members = parseInt(project.members) || 0;
            const extraMembers = Math.max(0, members - 2);

            return `
            <article class="project-card">
                <div class="project-card-head">
                    <div class="project-card-title-row">
                        <h2>${project.name || 'Chưa đặt tên'}</h2>
                        <span class="project-status-pill">${project.status || 'Khởi tạo'}</span>
                    </div>
                    <p>${project.description || 'Chưa có mô tả'}</p>
                </div>

                <div class="project-card-meta">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: ${progress}%;"></div>
                    </div>

                    <div class="project-progress-meta">
                        <span>${progress}% hoàn thành</span>
                        <span>Deadline: ${project.deadline || 'Chưa xác định'}</span>
                    </div>

                    <div class="project-stat-row">
                        <div class="project-mini-stat">
                            <strong>${parseInt(project.tasks) || 0}</strong>
                            <span>Tasks</span>
                        </div>
                        <div class="project-mini-stat">
                            <strong>${members}</strong>
                            <span>Members</span>
                        </div>
                        <div class="project-mini-stat">
                            <strong>${progress}%</strong>
                            <span>Progress</span>
                        </div>
                    </div>
                </div>

                <div class="project-card-footer">
                    <div class="avatar-stack">
                        <span>A</span>
                        <span>B</span>
                        <span>+${extraMembers}</span>
                    </div>
                    <a href="/creative-agency-hub/app/View/tasks/kanban.php?project_id=${project.id || ''}" class="btn btn-light">Xem bảng</a>
                </div>
            </article>
            `;
        }).join('');
    };

    // 3. Hàm render các con số thống kê (Dashboard Stats)
    const updateStats = (projects) => {
        const total = projects.length;
        const openTasks = projects.reduce((sum, p) => sum + (parseInt(p.tasks) || 0), 0);
        const avgProgress = total > 0 ? Math.round(projects.reduce((sum, p) => sum + (parseInt(p.progress) || 0), 0) / total) : 0;
        const riskDeadlines = projects.filter(p => (parseInt(p.progress) || 0) < 50).length; // Giả lập logic rủi ro

        document.getElementById('js-stat-total-projects').innerText = total;
        document.getElementById('js-stat-open-tasks').innerText = openTasks;
        document.getElementById('js-stat-avg-progress').innerText = avgProgress;
        document.getElementById('js-stat-risk-deadlines').innerText = riskDeadlines;
    };

    // 4. Hàm lọc dữ liệu trên Front-end
    const filterData = () => {
        const searchVal = document.getElementById('js-search-input').value.toLowerCase();
        const statusVal = document.getElementById('js-status-filter').value;

        let filtered = allProjects;

        if (searchVal) {
            filtered = filtered.filter(p => 
                (p.name && p.name.toLowerCase().includes(searchVal)) || 
                (p.description && p.description.toLowerCase().includes(searchVal))
            );
        }

        if (statusVal) {
            filtered = filtered.filter(p => p.status === statusVal);
        }

        renderProjects(filtered);
    };

    // 5. Hàm gọi API lấy dữ liệu
    const loadData = () => {
        fetch(`${baseUrl}/public/api/projects`, { 
            headers: { 'Authorization': 'Bearer ' + token } 
        })
        .then(async res => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Lỗi parse JSON:", text);
                throw new Error("API lỗi hoặc Server sập. Vui lòng kiểm tra Console (F12).");
            }
        })
        .then(res => {
            if (res.status === 'error') {
                projectGrid.innerHTML = `<p style="grid-column: 1 / -1; color: red; text-align: center;"><b>Lỗi Backend:</b> ${res.message}</p>`;
                return;
            }

            allProjects = res.data || [];
            updateStats(allProjects); // Cập nhật thống kê
            filterData(); // Render danh sách
        })
        .catch(error => {
            projectGrid.innerHTML = `<p style="grid-column: 1 / -1; color: red; text-align: center;"><b>Lỗi JS:</b> ${error.message}</p>`;
        });
    };

    // Lắng nghe sự kiện Lọc
    document.getElementById('js-search-input').addEventListener('input', filterData);
    document.getElementById('js-status-filter').addEventListener('change', filterData);
    document.getElementById('js-btn-filter').addEventListener('click', filterData);

    // Bắt đầu load dữ liệu
    loadData();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>