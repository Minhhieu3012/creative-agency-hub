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
$pageAction = '<button class="btn btn-primary" type="button" data-create-project>＋ Tạo dự án mới</button><a class="btn btn-light" href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a><a class="btn btn-primary" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Mở Kanban</a>';
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

    let allProjects = [];
    let allEmployees = [];

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toNumberOrNull(value) {
        if (value === undefined || value === null || value === '') return null;
        const number = Number(value);
        return Number.isFinite(number) && number > 0 ? number : null;
    }

    function getCurrentUser() {
        try {
            return JSON.parse(localStorage.getItem('cah_user') || localStorage.getItem('cah_auth_user') || 'null') || {};
        } catch (error) {
            return {};
        }
    }

    function getCurrentRole() {
        return String(getCurrentUser()?.role || '').toLowerCase();
    }

    function normalizeEmployeeName(employee) {
        const role = employee.role ? String(employee.role).toUpperCase() : 'USER';
        return `${employee.full_name || employee.email || 'Chưa đặt tên'} · ${role}`;
    }

    function getEmployeeById(employeeId) {
        return allEmployees.find((employee) => String(employee.id) === String(employeeId)) || null;
    }

    function getManagerOptions() {
        const role = getCurrentRole();
        const candidates = allEmployees.filter((employee) => {
            const employeeRole = String(employee.role || '').toLowerCase();
            return ['admin', 'manager'].includes(employeeRole) && employee.status === 'active' && !employee.deleted_at;
        });

        if (role === 'manager') {
            const currentUserId = Number(getCurrentUser()?.id || 0);
            return candidates.filter((employee) => Number(employee.id) === currentUserId);
        }

        return candidates;
    }

    function getClientOptions() {
        return allEmployees.filter((employee) => {
            const employeeRole = String(employee.role || '').toLowerCase();
            return employeeRole === 'client' && employee.status === 'active' && !employee.deleted_at;
        });
    }

    async function apiRequest(path, options = {}) {
        const response = await fetch(`${baseUrl}/public${path}`, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token,
                ...(options.headers || {})
            }
        });

        const text = await response.text();
        let payload;

        try {
            payload = JSON.parse(text);
        } catch (error) {
            console.error('Raw API response:', text);
            throw new Error('API trả về dữ liệu không hợp lệ. Kiểm tra route/backend.');
        }

        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || `Request lỗi HTTP ${response.status}`);
        }

        return payload;
    }

    function projectFormBody() {
        const managerOptions = getManagerOptions();
        const clientOptions = getClientOptions();
        const isManager = getCurrentRole() === 'manager';

        const managerSelectHtml = managerOptions.length > 0
            ? managerOptions.map((employee) => `<option value="${escapeHtml(employee.id)}">${escapeHtml(normalizeEmployeeName(employee))}</option>`).join('')
            : '<option value="">Chưa có Admin/Manager active</option>';

        const clientSelectHtml = clientOptions.length > 0
            ? '<option value="">Chưa gán khách hàng</option>' + clientOptions.map((employee) => `<option value="${escapeHtml(employee.id)}">${escapeHtml(employee.full_name || employee.email || `Client #${employee.id}`)}${employee.email ? ' · ' + escapeHtml(employee.email) : ''}</option>`).join('')
            : '<option value="">Chưa có tài khoản Client active</option>';

        return `
            <form class="task-modal-form" data-project-form>
                <div class="form-group">
                    <label class="form-label">Tên dự án</label>
                    <input class="form-control" type="text" name="name" placeholder="Ví dụ: Website HRM nội bộ" required maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả dự án</label>
                    <textarea class="form-textarea" name="description" placeholder="Mục tiêu, phạm vi, ghi chú chính của dự án"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Người phụ trách dự án</label>
                        <select class="form-select" name="manager_id" ${isManager ? 'disabled' : ''} required>
                            ${managerSelectHtml}
                        </select>
                        <small class="form-help">Lưu ID người phụ trách vào projects.manager_id.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Khách hàng giám sát</label>
                        <select class="form-select" name="client_id">
                            ${clientSelectHtml}
                        </select>
                        <small class="form-help">Lưu ID khách hàng vào projects.client_id để Client Portal nhìn thấy dự án.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="Active">Đang triển khai</option>
                            <option value="Completed">Hoàn thành</option>
                            <option value="Archived">Đã lưu trữ</option>
                        </select>
                    </div>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Tạo dự án</button>
                </div>
            </form>
        `;
    }

    function openCreateProjectModal() {
        if (!window.CAHModal) {
            alert('Không tìm thấy modal. Kiểm tra modal.js hoặc layout app.php.');
            return;
        }

        CAHModal.open({
            title: 'Tạo dự án mới',
            subtitle: 'Chọn người phụ trách và khách hàng giám sát để biết rõ ai quản lý, ai được xem dự án.',
            body: projectFormBody()
        });
    }

    async function createProject(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        const managerId = toNumberOrNull(data.manager_id) || toNumberOrNull(getCurrentUser()?.id);
        const clientId = toNumberOrNull(data.client_id);

        if (!managerId) {
            throw new Error('Vui lòng chọn người phụ trách dự án.');
        }

        const payload = {
            name: data.name,
            description: data.description || '',
            manager_id: managerId,
            client_id: clientId,
            status: data.status || 'Active'
        };

        await apiRequest('/api/projects', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (window.CAHModal) {
            CAHModal.close();
        }

        if (window.CAHToast) {
            CAHToast.success('Đã tạo dự án', clientId ? 'Project mới đã được gán cho khách hàng giám sát.' : 'Project mới đã được thêm vào danh sách.');
        }

        await loadData();
    }

    if (!token) {
        projectGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: red; padding: 40px;">Lỗi: Bạn chưa đăng nhập hoặc Token đã mất. Vui lòng đăng nhập lại.</p>';
        return;
    }

    const renderProjects = (projects) => {
        if (!projects || projects.length === 0) {
            projectGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">Không tìm thấy dự án nào phù hợp.</p>';
            return;
        }

        projectGrid.innerHTML = projects.map(project => {
            const progress = parseInt(project.progress) || 0;
            const members = parseInt(project.members) || 0;
            const extraMembers = Math.max(0, members - 2);
            const taskCount = parseInt(project.tasks) || 0;
            const openTaskCount = parseInt(project.open_tasks ?? project.tasks) || 0;
            const riskTasks = parseInt(project.risk_tasks) || 0;
            const statusText = statusLabels[project.status] || project.status || 'Khởi tạo';
            const managerName = project.manager_name || 'Chưa gán phụ trách';
            const managerEmail = project.manager_email || '';

            const client = getEmployeeById(project.client_id);
            const clientName = project.client_name || client?.full_name || client?.email || '';
            const clientEmail = project.client_email || client?.email || '';

            return `
            <article class="project-card" data-project-card data-project-id="${escapeHtml(project.id)}">
                <div class="project-card-head">
                    <div class="project-card-title-row">
                        <h2>${escapeHtml(project.name || 'Chưa đặt tên')}</h2>
                        <span class="project-status-pill">${escapeHtml(statusText)}</span>
                    </div>
                    <p>${escapeHtml(project.description || 'Chưa có mô tả')}</p>

                    <div class="project-owner-line">
                        <strong>Phụ trách:</strong>
                        <span>${escapeHtml(managerName)}</span>
                        ${managerEmail ? `<small>${escapeHtml(managerEmail)}</small>` : ''}
                    </div>

                    <div class="project-owner-line">
                        <strong>Khách hàng:</strong>
                        <span>${clientName ? escapeHtml(clientName) : 'Chưa gán khách hàng giám sát'}</span>
                        ${clientEmail ? `<small>${escapeHtml(clientEmail)}</small>` : ''}
                    </div>
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
                        <span title="${escapeHtml(managerName)}">${escapeHtml((managerName || 'CA').charAt(0).toUpperCase())}</span>
                        <span title="${clientName ? escapeHtml(clientName) : 'Chưa gán khách hàng'}">${clientName ? escapeHtml((clientName || 'CL').charAt(0).toUpperCase()) : 'CL'}</span>
                        <span title="Task đang mở">${openTaskCount}</span>
                    </div>
                    <a href="/creative-agency-hub/app/View/tasks/kanban.php?project_id=${encodeURIComponent(project.id || '')}" class="btn btn-light">Xem bảng</a>
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
            filtered = filtered.filter(p => {
                const client = getEmployeeById(p.client_id);
                return (p.name && p.name.toLowerCase().includes(searchVal)) ||
                    (p.description && p.description.toLowerCase().includes(searchVal)) ||
                    (p.manager_name && p.manager_name.toLowerCase().includes(searchVal)) ||
                    (client?.full_name && client.full_name.toLowerCase().includes(searchVal)) ||
                    (client?.email && client.email.toLowerCase().includes(searchVal));
            });
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

    async function loadEmployees() {
        try {
            const response = await apiRequest('/api/employees');
            allEmployees = Array.isArray(response.data) ? response.data : [];
        } catch (error) {
            allEmployees = [];
            console.warn('Không tải được danh sách nhân sự:', error.message);
        }
    }

    async function loadData() {
        try {
            const res = await apiRequest(`/api/projects?_=${Date.now()}`);
            allProjects = Array.isArray(res.data) ? res.data : [];
            updateStats(allProjects);
            filterData();
        } catch (error) {
            projectGrid.innerHTML = `<p style="grid-column: 1 / -1; color: red; text-align: center;"><b>Lỗi JS/API:</b> ${escapeHtml(error.message)}</p>`;
        }
    }

    document.getElementById('js-search-input').addEventListener('input', filterData);
    document.getElementById('js-status-filter').addEventListener('change', filterData);
    document.getElementById('js-sort-filter').addEventListener('change', filterData);
    document.getElementById('js-btn-filter').addEventListener('click', filterData);

    document.addEventListener('click', function(event) {
        const createBtn = event.target.closest('[data-create-project]');
        if (createBtn) {
            event.preventDefault();
            openCreateProjectModal();
        }
    });

    document.addEventListener('submit', function(event) {
        const form = event.target.closest('[data-project-form]');
        if (!form) return;

        event.preventDefault();

        createProject(form).catch((error) => {
            if (window.CAHToast) {
                CAHToast.error('Không thể tạo dự án', error.message || 'API chưa xử lý được yêu cầu.');
            } else {
                alert(error.message || 'Không thể tạo dự án.');
            }
        });
    });

    loadEmployees().then(loadData);
});
</script>

<?php
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>