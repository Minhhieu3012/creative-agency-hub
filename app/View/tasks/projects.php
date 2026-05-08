<?php
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
$pageAction = '
    <button class="btn btn-primary" type="button" data-project-create style="display:none;">＋ Tạo Project</button>
    <a class="btn btn-light" href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a>
    <a class="btn btn-primary" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Mở Kanban</a>
';
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
            <option value="progress-desc">Tiến độ cao nhất</option>
            <option value="risk">Rủi ro cao nhất</option>
        </select>

        <button class="btn btn-soft" type="button" id="js-btn-filter">Lọc dữ liệu</button>
    </div>

    <section class="project-grid" id="js-project-grid">
        <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
            Đang tải dữ liệu dự án...
        </p>
    </section>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '/creative-agency-hub';
    const apiRoot = window.CAH_CONFIG?.apiRoot || `${baseUrl}/public`;
    const projectGrid = document.getElementById('js-project-grid');
    const createProjectButton = document.querySelector('[data-project-create]');
    const searchInput = document.getElementById('js-search-input');
    const statusFilter = document.getElementById('js-status-filter');
    const sortFilter = document.getElementById('js-sort-filter');
    const filterButton = document.getElementById('js-btn-filter');

    let allProjects = [];
    let projectOptions = null;
    let currentUser = window.CAH_CURRENT_USER || null;

    function getToken() {
        return localStorage.getItem('cah_auth_token') || localStorage.getItem('cah_token') || '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toast(type, title, message) {
        if (window.CAHToast && typeof window.CAHToast[type] === 'function') {
            window.CAHToast[type](title, message);
            return;
        }

        if (type === 'error') {
            console.error(title, message);
            return;
        }

        console.log(title, message);
    }

    async function apiRequest(path, options = {}) {
        const token = getToken();

        const headers = {
            Accept: 'application/json',
            ...(options.headers || {})
        };

        if (token) {
            headers.Authorization = 'Bearer ' + token;
        }

        const response = await fetch(apiRoot + path, {
            credentials: 'same-origin',
            ...options,
            headers
        });

        const payload = await response.json().catch(() => ({
            status: 'error',
            message: 'Server không trả JSON hợp lệ.'
        }));

        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || 'Yêu cầu không thành công.');
        }

        return payload;
    }

    function roleOfCurrentUser() {
        return String(currentUser?.role || window.CAH_CURRENT_USER?.role || '').toLowerCase();
    }

    async function loadCurrentUser() {
        try {
            const payload = await apiRequest('/api/auth/me');
            currentUser = payload?.data?.user || payload?.data || currentUser;
        } catch (error) {
            currentUser = window.CAH_CURRENT_USER || currentUser;
        }

        const role = roleOfCurrentUser();

        if (createProjectButton) {
            createProjectButton.style.display = role === 'manager' ? 'inline-flex' : 'none';
        }
    }

    function normalizeStatusLabel(status) {
        const value = String(status || 'Active');

        const labels = {
            Active: 'Đang triển khai',
            Completed: 'Hoàn thành',
            Archived: 'Đã lưu trữ'
        };

        return labels[value] || value;
    }

    function formatDate(value) {
        if (!value) {
            return 'Chưa xác định';
        }

        const date = new Date(String(value) + 'T00:00:00');

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString('vi-VN');
    }

    function initialsFromName(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);

        if (parts.length === 0) {
            return 'CA';
        }

        const first = parts[0].charAt(0);
        const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';

        return (first + last).toUpperCase();
    }

    function normalizeProject(project) {
        const totalTasks = Number(project.total_tasks ?? project.tasks ?? 0) || 0;
        const doneTasks = Number(project.done_tasks ?? 0) || 0;
        const progress = Number(project.progress ?? (totalTasks > 0 ? Math.round(doneTasks / totalTasks * 100) : 0)) || 0;
        const membersArray = Array.isArray(project.members) ? project.members : [];
        const memberCount = Number(project.member_count ?? project.members_count ?? membersArray.length ?? 0) || 0;

        return {
            ...project,
            id: Number(project.id || 0),
            name: project.name || 'Chưa đặt tên',
            description: project.description || 'Chưa có mô tả',
            status: project.status || 'Active',
            total_tasks: totalTasks,
            done_tasks: doneTasks,
            overdue_tasks: Number(project.overdue_tasks || 0) || 0,
            progress,
            nearest_deadline: project.nearest_deadline || project.deadline || '',
            client_name: project.client_name || 'Chưa gán client',
            manager_name: project.manager_name || 'Chưa rõ manager',
            members: membersArray,
            member_count: memberCount
        };
    }

    async function hydrateProjectDetails(projects) {
        const role = roleOfCurrentUser();

        if (!['admin', 'manager', 'employee', 'client'].includes(role)) {
            return projects.map(normalizeProject);
        }

        const detailPromises = projects.map(async function (project) {
            if (!project.id) {
                return normalizeProject(project);
            }

            try {
                const payload = await apiRequest(`/api/projects/${project.id}`);
                return normalizeProject({
                    ...project,
                    ...(payload.data || {})
                });
            } catch (error) {
                return normalizeProject(project);
            }
        });

        return Promise.all(detailPromises);
    }

    function memberInitials(project) {
        if (Array.isArray(project.members) && project.members.length > 0) {
            return project.members
                .slice(0, 3)
                .map(function (member) {
                    return initialsFromName(member.full_name || member.name || member.email || 'CA');
                });
        }

        const result = [];

        if (project.manager_name && project.manager_name !== 'Chưa rõ manager') {
            result.push(initialsFromName(project.manager_name));
        }

        if (project.client_name && project.client_name !== 'Chưa gán client') {
            result.push(initialsFromName(project.client_name));
        }

        if (result.length === 0) {
            result.push('CA');
        }

        return result.slice(0, 3);
    }

    function renderProjects(projects) {
        if (!projects || projects.length === 0) {
            projectGrid.innerHTML = `
                <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                    Không tìm thấy dự án nào phù hợp.
                </p>
            `;
            return;
        }

        projectGrid.innerHTML = projects.map(function (project) {
            const progress = Math.max(0, Math.min(100, Number(project.progress || 0)));
            const memberCount = Number(project.member_count || 0);
            const initials = memberInitials(project);
            const extraMembers = Math.max(0, memberCount - initials.length);
            const statusText = normalizeStatusLabel(project.status);
            const deadlineText = formatDate(project.nearest_deadline);
            const riskBadge = Number(project.overdue_tasks || 0) > 0
                ? `<span class="badge badge-danger">${project.overdue_tasks} quá hạn</span>`
                : '';

            const avatarsHtml = initials.map(function (initial) {
                return `<span>${escapeHtml(initial)}</span>`;
            }).join('');

            const extraHtml = extraMembers > 0
                ? `<span>+${extraMembers}</span>`
                : '';

            return `
                <article class="project-card" data-project-id="${project.id}">
                    <div class="project-card-head">
                        <div class="project-card-title-row">
                            <h2>${escapeHtml(project.name)}</h2>
                            <span class="project-status-pill">${escapeHtml(statusText)}</span>
                        </div>
                        <p>${escapeHtml(project.description)}</p>
                    </div>

                    <div class="project-card-meta">
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: ${progress}%;"></div>
                        </div>

                        <div class="project-progress-meta">
                            <span>${progress}% hoàn thành</span>
                            <span>Deadline: ${escapeHtml(deadlineText)}</span>
                        </div>

                        <div class="project-stat-row">
                            <div class="project-mini-stat">
                                <strong>${Number(project.total_tasks || 0)}</strong>
                                <span>Tasks</span>
                            </div>
                            <div class="project-mini-stat">
                                <strong>${memberCount}</strong>
                                <span>Members</span>
                            </div>
                            <div class="project-mini-stat">
                                <strong>${progress}%</strong>
                                <span>Progress</span>
                            </div>
                        </div>

                        <div class="project-progress-meta" style="margin-top: 12px;">
                            <span>Client: ${escapeHtml(project.client_name)}</span>
                            <span>Manager: ${escapeHtml(project.manager_name)}</span>
                        </div>

                        ${riskBadge ? `<div style="margin-top: 12px;">${riskBadge}</div>` : ''}
                    </div>

                    <div class="project-card-footer">
                        <div class="avatar-stack">
                            ${avatarsHtml}
                            ${extraHtml}
                        </div>

                        <a href="/creative-agency-hub/app/View/tasks/kanban.php?project_id=${project.id || ''}" class="btn btn-light">
                            Xem bảng
                        </a>
                    </div>
                </article>
            `;
        }).join('');
    }

    function updateStats(projects) {
        const total = projects.length;
        const openTasks = projects.reduce(function (sum, project) {
            const totalTasks = Number(project.total_tasks || 0);
            const doneTasks = Number(project.done_tasks || 0);

            return sum + Math.max(0, totalTasks - doneTasks);
        }, 0);

        const avgProgress = total > 0
            ? Math.round(projects.reduce(function (sum, project) {
                return sum + (Number(project.progress || 0) || 0);
            }, 0) / total)
            : 0;

        const riskDeadlines = projects.filter(function (project) {
            return Number(project.overdue_tasks || 0) > 0 || Number(project.progress || 0) < 50;
        }).length;

        document.getElementById('js-stat-total-projects').innerText = String(total);
        document.getElementById('js-stat-open-tasks').innerText = String(openTasks);
        document.getElementById('js-stat-avg-progress').innerText = String(avgProgress);
        document.getElementById('js-stat-risk-deadlines').innerText = String(riskDeadlines);
    }

    function getFilteredProjects() {
        const searchVal = String(searchInput?.value || '').toLowerCase().trim();
        const statusVal = String(statusFilter?.value || '').trim();
        const sortVal = String(sortFilter?.value || 'deadline').trim();

        let filtered = [...allProjects];

        if (searchVal) {
            filtered = filtered.filter(function (project) {
                return String(project.name || '').toLowerCase().includes(searchVal)
                    || String(project.description || '').toLowerCase().includes(searchVal)
                    || String(project.client_name || '').toLowerCase().includes(searchVal)
                    || String(project.manager_name || '').toLowerCase().includes(searchVal);
            });
        }

        if (statusVal) {
            filtered = filtered.filter(function (project) {
                return String(project.status || '') === statusVal;
            });
        }

        filtered.sort(function (a, b) {
            if (sortVal === 'progress-desc') {
                return Number(b.progress || 0) - Number(a.progress || 0);
            }

            if (sortVal === 'risk') {
                const riskA = Number(a.overdue_tasks || 0) * 100 + (100 - Number(a.progress || 0));
                const riskB = Number(b.overdue_tasks || 0) * 100 + (100 - Number(b.progress || 0));

                return riskB - riskA;
            }

            const dateA = a.nearest_deadline ? new Date(a.nearest_deadline + 'T00:00:00').getTime() : Number.MAX_SAFE_INTEGER;
            const dateB = b.nearest_deadline ? new Date(b.nearest_deadline + 'T00:00:00').getTime() : Number.MAX_SAFE_INTEGER;

            return dateA - dateB;
        });

        return filtered;
    }

    function filterData() {
        renderProjects(getFilteredProjects());
    }

    async function loadProjects() {
        const token = getToken();

        if (!token) {
            projectGrid.innerHTML = `
                <p style="grid-column: 1 / -1; text-align: center; color: red; padding: 40px;">
                    Lỗi: Bạn chưa đăng nhập hoặc token đã mất. Vui lòng đăng nhập lại.
                </p>
            `;
            return;
        }

        projectGrid.innerHTML = `
            <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                Đang tải dữ liệu dự án...
            </p>
        `;

        try {
            const payload = await apiRequest('/api/projects');
            const rawProjects = Array.isArray(payload.data) ? payload.data : [];

            allProjects = await hydrateProjectDetails(rawProjects);

            updateStats(allProjects);
            filterData();
        } catch (error) {
            projectGrid.innerHTML = `
                <p style="grid-column: 1 / -1; color: red; text-align: center; padding: 40px;">
                    <b>Lỗi tải dự án:</b> ${escapeHtml(error.message)}
                </p>
            `;
        }
    }

    async function loadProjectOptions() {
        if (projectOptions) {
            return projectOptions;
        }

        const payload = await apiRequest('/api/projects/options');
        projectOptions = payload.data || {
            clients: [],
            employees: []
        };

        return projectOptions;
    }

    function buildClientOptions(clients) {
        if (!Array.isArray(clients) || clients.length === 0) {
            return '<option value="">Chưa có client khả dụng</option>';
        }

        return [
            '<option value="">Chọn client</option>',
            ...clients.map(function (client) {
                return `<option value="${client.id}">${escapeHtml(client.full_name || client.email || ('Client #' + client.id))}</option>`;
            })
        ].join('');
    }

    function buildEmployeeCheckboxes(employees) {
        if (!Array.isArray(employees) || employees.length === 0) {
            return `
                <div class="ui-empty-state" style="min-height: 120px;">
                    <div class="ui-empty-content">
                        <h3>Chưa có employee khả dụng</h3>
                        <p>Hãy tạo nhân sự employee trước khi kéo vào project.</p>
                    </div>
                </div>
            `;
        }

        return employees.map(function (employee) {
            const label = employee.full_name || employee.email || ('Employee #' + employee.id);
            const sub = [employee.department_name, employee.position_name].filter(Boolean).join(' • ');

            return `
                <label class="checkbox-line" style="align-items:flex-start; margin-bottom: 10px;">
                    <input type="checkbox" name="member_ids" value="${employee.id}">
                    <span>
                        <strong>${escapeHtml(label)}</strong>
                        ${sub ? `<small style="display:block; color: var(--text-muted); margin-top: 4px;">${escapeHtml(sub)}</small>` : ''}
                    </span>
                </label>
            `;
        }).join('');
    }

    async function openCreateProjectModal() {
        const role = roleOfCurrentUser();

        if (role !== 'manager') {
            toast('error', 'Không có quyền', 'Chỉ Manager được tạo project.');
            return;
        }

        try {
            const options = await loadProjectOptions();
            const clients = Array.isArray(options.clients) ? options.clients : [];
            const employees = Array.isArray(options.employees) ? options.employees : [];

            const body = `
                <form data-project-form>
                    <div class="form-group">
                        <label class="form-label" for="project-name">Tên project</label>
                        <input id="project-name" class="form-control" type="text" name="name" placeholder="VD: Thiết kế Web Vinamilk" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="project-description">Mô tả</label>
                        <textarea id="project-description" class="form-textarea" name="description" rows="4" placeholder="Mô tả ngắn về mục tiêu dự án"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="project-client">Client</label>
                            <select id="project-client" class="form-select" name="client_id">
                                ${buildClientOptions(clients)}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="project-status">Trạng thái</label>
                            <select id="project-status" class="form-select" name="status">
                                <option value="Active">Đang triển khai</option>
                                <option value="Completed">Hoàn thành</option>
                                <option value="Archived">Đã lưu trữ</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Employee tham gia project</label>
                        <div style="max-height: 220px; overflow:auto; border:1px solid var(--line); border-radius:14px; padding:14px;">
                            ${buildEmployeeCheckboxes(employees)}
                        </div>
                    </div>

                    <div class="task-modal-footer" style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                        <button class="btn btn-light" type="button" data-modal-close>Hủy</button>
                        <button class="btn btn-primary" type="submit">Tạo Project</button>
                    </div>
                </form>
            `;

            if (window.CAHModal && typeof CAHModal.open === 'function') {
                CAHModal.open({
                    title: 'Tạo project mới',
                    body
                });
                return;
            }

            toast('error', 'Modal chưa sẵn sàng', 'Không tìm thấy CAHModal trong layout.');
        } catch (error) {
            toast('error', 'Không thể mở form tạo project', error.message);
        }
    }

    async function createProjectFromForm(form) {
        const formData = new FormData(form);

        const payload = {
            name: String(formData.get('name') || '').trim(),
            description: String(formData.get('description') || '').trim(),
            client_id: formData.get('client_id') ? Number(formData.get('client_id')) : null,
            status: String(formData.get('status') || 'Active'),
            member_ids: formData.getAll('member_ids').map(function (id) {
                return Number(id);
            }).filter(function (id) {
                return Number.isFinite(id) && id > 0;
            })
        };

        if (!payload.name) {
            toast('error', 'Thiếu tên project', 'Vui lòng nhập tên project.');
            return;
        }

        try {
            await apiRequest('/api/projects', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (window.CAHModal && typeof CAHModal.close === 'function') {
                CAHModal.close();
            }

            toast('success', 'Tạo project thành công', 'Project mới đã được thêm vào danh sách.');
            await loadProjects();
        } catch (error) {
            toast('error', 'Không thể tạo project', error.message);
        }
    }

    searchInput?.addEventListener('input', filterData);
    statusFilter?.addEventListener('change', filterData);
    sortFilter?.addEventListener('change', filterData);
    filterButton?.addEventListener('click', filterData);

    createProjectButton?.addEventListener('click', function (event) {
        event.preventDefault();
        openCreateProjectModal();
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-project-form]');

        if (!form) {
            return;
        }

        event.preventDefault();
        createProjectFromForm(form);
    });

    loadCurrentUser().then(loadProjects);
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>