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
$pageSubtitle = 'Manager tạo project, gán client đã duyệt và kéo employee active vào nhóm triển khai.';
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
                <small>Trong phạm vi quyền xem</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">☑</div>
            <div class="stat-card-body">
                <span>Task đang mở</span>
                <strong id="js-stat-open-tasks">0</strong>
                <small>Chưa hoàn thành</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◔</div>
            <div class="stat-card-body">
                <span>Tiến độ TB</span>
                <strong><span id="js-stat-avg-progress">0</span>%</strong>
                <small>Theo task done</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Rủi ro deadline</span>
                <strong id="js-stat-risk-deadlines">0</strong>
                <small>Cần theo dõi</small>
            </div>
        </article>
    </div>

    <div class="task-filter-bar">
        <div class="input-with-icon">
            <span class="input-icon">⌕</span>
            <input id="js-search-input" class="form-control" type="search" placeholder="Tìm kiếm dự án, client, manager...">
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
    const viewUrl = `${baseUrl}/app/View`;

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

    function isManager() {
        return roleOfCurrentUser() === 'manager';
    }

    async function loadCurrentUser() {
        try {
            const payload = await apiRequest('/api/auth/me');
            currentUser = payload?.data?.user || payload?.data || currentUser;
        } catch (error) {
            currentUser = window.CAH_CURRENT_USER || currentUser;
        }

        if (createProjectButton) {
            createProjectButton.style.display = isManager() ? 'inline-flex' : 'none';
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

            const managerAction = isManager()
                ? `<button class="btn btn-light" type="button" data-project-edit="${project.id}">Sửa</button>`
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

                        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                            ${managerAction}
                            <a href="/creative-agency-hub/app/View/tasks/kanban.php?project_id=${project.id || ''}" class="btn btn-primary">
                                Xem bảng
                            </a>
                        </div>
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

    function buildClientOptions(clients, selectedId = null) {
        if (!Array.isArray(clients) || clients.length === 0) {
            return '<option value="">Chưa có client active đã duyệt</option>';
        }

        return [
            '<option value="">Chọn client active</option>',
            ...clients.map(function (client) {
                const selected = Number(selectedId || 0) === Number(client.id) ? 'selected' : '';
                const label = client.full_name || client.email || ('Client #' + client.id);

                return `<option value="${client.id}" ${selected}>${escapeHtml(label)} • active</option>`;
            })
        ].join('');
    }

    function buildEmployeeCheckboxes(employees, selectedIds = []) {
        const selectedSet = new Set((selectedIds || []).map(function (id) {
            return Number(id);
        }));

        if (!Array.isArray(employees) || employees.length === 0) {
            return `
                <div class="ui-empty-state" style="min-height: 120px;">
                    <div class="ui-empty-content">
                        <h3>Chưa có employee active</h3>
                        <p>Manager cần tạo employee, sau đó Admin duyệt active trước khi kéo vào project.</p>
                    </div>
                </div>
            `;
        }

        return employees.map(function (employee) {
            const label = employee.full_name || employee.email || ('Employee #' + employee.id);
            const sub = [employee.department_name, employee.position_name].filter(Boolean).join(' • ');
            const checked = selectedSet.has(Number(employee.id)) ? 'checked' : '';

            return `
                <label class="checkbox-line" style="align-items:flex-start; margin-bottom: 10px;">
                    <input type="checkbox" name="member_ids" value="${employee.id}" ${checked}>
                    <span>
                        <strong>${escapeHtml(label)}</strong>
                        <small style="display:block; color: var(--text-muted); margin-top: 4px;">
                            ${escapeHtml(sub || 'Employee active đã Admin duyệt')}
                        </small>
                    </span>
                </label>
            `;
        }).join('');
    }

    function modalOpen(title, body) {
        if (window.CAHModal && typeof CAHModal.open === 'function') {
            CAHModal.open({ title, body });
            return true;
        }

        const fallback = document.createElement('div');
        fallback.className = 'modal-backdrop is-visible';
        fallback.setAttribute('data-fallback-project-modal', '');
        fallback.innerHTML = `
            <div class="modal-panel" style="max-width: 780px;">
                <div class="modal-header">
                    <h2>${escapeHtml(title)}</h2>
                    <button class="modal-close" type="button" data-modal-close>×</button>
                </div>
                <div class="modal-body">${body}</div>
            </div>
        `;

        document.body.appendChild(fallback);
        return true;
    }

    function modalClose() {
        if (window.CAHModal && typeof CAHModal.close === 'function') {
            CAHModal.close();
        }

        document.querySelectorAll('[data-fallback-project-modal]').forEach(function (modal) {
            modal.remove();
        });
    }

    function buildProjectForm(options, project = null) {
        const clients = Array.isArray(options.clients) ? options.clients : [];
        const employees = Array.isArray(options.employees) ? options.employees : [];
        const members = Array.isArray(project?.members) ? project.members : [];
        const selectedMemberIds = members.map(function (member) {
            return Number(member.id);
        });

        const hasClient = clients.length > 0;
        const hasEmployee = employees.length > 0;

        const warning = (!hasClient || !hasEmployee)
            ? `
                <div class="form-alert form-alert-danger" style="margin-bottom:16px;">
                    ${!hasClient ? '<div>Chưa có Client active. Manager cần tạo Client và Admin duyệt trước.</div>' : ''}
                    ${!hasEmployee ? '<div>Chưa có Employee active. Manager cần tạo Employee và Admin duyệt trước.</div>' : ''}
                </div>
            `
            : `
                <div class="form-alert form-alert-success" style="margin-bottom:16px;">
                    Chỉ các tài khoản đã được Admin duyệt active mới xuất hiện trong form này.
                </div>
            `;

        return `
            <form data-project-form data-project-id="${project?.id || ''}">
                ${warning}

                <div class="form-group">
                    <label class="form-label" for="project-name">Tên project</label>
                    <input
                        id="project-name"
                        class="form-control"
                        type="text"
                        name="name"
                        placeholder="VD: Thiết kế Web Vinamilk"
                        value="${escapeHtml(project?.name || '')}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="project-description">Mô tả</label>
                    <textarea
                        id="project-description"
                        class="form-textarea"
                        name="description"
                        rows="4"
                        placeholder="Mô tả ngắn về mục tiêu dự án"
                    >${escapeHtml(project?.description || '')}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="project-client">Client active</label>
                        <select id="project-client" class="form-select" name="client_id" ${!hasClient ? 'disabled' : ''}>
                            ${buildClientOptions(clients, project?.client_id || null)}
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="project-status">Trạng thái</label>
                        <select id="project-status" class="form-select" name="status">
                            <option value="Active" ${project?.status === 'Active' ? 'selected' : ''}>Đang triển khai</option>
                            <option value="Completed" ${project?.status === 'Completed' ? 'selected' : ''}>Hoàn thành</option>
                            <option value="Archived" ${project?.status === 'Archived' ? 'selected' : ''}>Đã lưu trữ</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Employee active tham gia project</label>
                    <div style="max-height: 240px; overflow:auto; border:1px solid var(--line); border-radius:14px; padding:14px;">
                        ${buildEmployeeCheckboxes(employees, selectedMemberIds)}
                    </div>
                </div>

                <div class="task-modal-footer" style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                    <button class="btn btn-light" type="button" data-modal-close>Hủy</button>
                    <button class="btn btn-primary" type="submit" ${(!hasClient || !hasEmployee) ? 'disabled' : ''}>
                        ${project?.id ? 'Lưu Project' : 'Tạo Project'}
                    </button>
                </div>
            </form>
        `;
    }

    async function openCreateProjectModal() {
        if (!isManager()) {
            toast('error', 'Không có quyền', 'Chỉ Manager được tạo project.');
            return;
        }

        try {
            const options = await loadProjectOptions();
            modalOpen('Tạo project mới', buildProjectForm(options));
        } catch (error) {
            toast('error', 'Không thể mở form tạo project', error.message);
        }
    }

    async function openEditProjectModal(projectId) {
        if (!isManager()) {
            toast('error', 'Không có quyền', 'Chỉ Manager được sửa project.');
            return;
        }

        try {
            const options = await loadProjectOptions();
            const payload = await apiRequest('/api/projects/' + encodeURIComponent(projectId));
            const project = payload.data || null;

            modalOpen('Cập nhật project', buildProjectForm(options, project));
        } catch (error) {
            toast('error', 'Không thể mở form sửa project', error.message);
        }
    }

    async function submitProjectForm(form) {
        const projectId = Number(form.getAttribute('data-project-id') || 0);
        const formData = new FormData(form);

        const memberIds = formData.getAll('member_ids').map(function (id) {
            return Number(id);
        }).filter(function (id) {
            return Number.isFinite(id) && id > 0;
        });

        const payload = {
            name: String(formData.get('name') || '').trim(),
            description: String(formData.get('description') || '').trim(),
            client_id: formData.get('client_id') ? Number(formData.get('client_id')) : null,
            status: String(formData.get('status') || 'Active'),
            member_ids: memberIds
        };

        if (!payload.name) {
            toast('error', 'Thiếu tên project', 'Vui lòng nhập tên project.');
            return;
        }

        if (!payload.client_id) {
            toast('error', 'Thiếu Client active', 'Project cần chọn một Client active đã được Admin duyệt.');
            return;
        }

        if (payload.member_ids.length === 0) {
            toast('error', 'Thiếu Employee active', 'Project cần ít nhất một Employee active tham gia.');
            return;
        }

        try {
            if (projectId > 0) {
                await apiRequest('/api/projects/' + encodeURIComponent(projectId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                toast('success', 'Đã cập nhật project', 'Thông tin project đã được lưu.');
            } else {
                await apiRequest('/api/projects', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                toast('success', 'Tạo project thành công', 'Project mới đã được thêm vào danh sách.');
            }

            modalClose();
            projectOptions = null;
            await loadProjects();
        } catch (error) {
            toast('error', projectId > 0 ? 'Không thể cập nhật project' : 'Không thể tạo project', error.message);
        }
    }

    searchInput?.addEventListener('input', function () {
        window.clearTimeout(searchInput._timer);
        searchInput._timer = window.setTimeout(filterData, 180);
    });

    statusFilter?.addEventListener('change', filterData);
    sortFilter?.addEventListener('change', filterData);
    filterButton?.addEventListener('click', filterData);

    createProjectButton?.addEventListener('click', function (event) {
        event.preventDefault();
        openCreateProjectModal();
    });

    document.addEventListener('click', function (event) {
        const closeButton = event.target.closest('[data-modal-close]');
        const editButton = event.target.closest('[data-project-edit]');

        if (closeButton) {
            event.preventDefault();
            modalClose();
        }

        if (editButton) {
            event.preventDefault();
            openEditProjectModal(editButton.getAttribute('data-project-edit'));
        }
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('[data-project-form]');

        if (!form) {
            return;
        }

        event.preventDefault();
        submitProjectForm(form);
    });

    loadCurrentUser().then(loadProjects);
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>