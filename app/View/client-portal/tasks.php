<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Công việc dự án | Creative Agency Hub';

$baseUrl = '/creative-agency-hub';
$publicUrl = $baseUrl . '/public';
$viewUrl = $baseUrl . '/app/View';
$assetUrl = $publicUrl . '/assets';
$cacheBust = time();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>

    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/css/reset.css?v=<?php echo $cacheBust; ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/css/app.css?v=<?php echo $cacheBust; ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/css/components.css?v=<?php echo $cacheBust; ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/css/client-portal.css?v=<?php echo $cacheBust; ?>">
</head>
<body class="client-body">
    <div class="client-shell">
        <header class="client-topbar">
            <a class="client-brand" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php">
                <span class="brand-mark">CA</span>
                <span>Creative Agency Hub</span>
            </a>

            <nav class="client-nav" aria-label="Client navigation">
                <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php">
                    Dự án
                </a>
                <a class="client-nav-link is-active" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/tasks.php">
                    Công việc
                </a>
                <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/support.php">
                    Feedback
                </a>
            </nav>

            <div class="client-user">
                <div>
                    <strong data-client-name>Client</strong>
                    <small style="display:block; color: var(--text-muted); font-weight: 800;">CLIENT PORTAL</small>
                </div>

                <span class="client-avatar" data-client-avatar>CL</span>

                <button class="btn btn-light" type="button" data-client-logout>
                    Đăng xuất
                </button>
            </div>
        </header>

        <main class="client-content">
            <section class="client-hero">
                <div class="client-hero-copy">
                    <span class="client-kicker" data-project-kicker>Project Detail</span>
                    <h1>Chi tiết tiến độ<br>dự án.</h1>
                    <p>
                        Theo dõi task được chia sẻ công khai, xem trạng thái xử lý và gửi feedback
                        trực tiếp cho đội ngũ phụ trách. Những trao đổi nội bộ sẽ không hiển thị ở Client Portal.
                    </p>
                </div>

                <aside class="client-hero-panel">
                    <div class="client-hero-panel-row">
                        <span>Project</span>
                        <strong data-project-name>Đang tải...</strong>
                    </div>
                    <div class="client-hero-panel-row">
                        <span>Task public</span>
                        <strong data-stat-total-tasks>0</strong>
                    </div>
                    <div class="client-hero-panel-row">
                        <span>Đã hoàn thành</span>
                        <strong data-stat-done-tasks>0</strong>
                    </div>
                    <div class="client-hero-panel-row">
                        <span>Tiến độ</span>
                        <strong><span data-stat-progress>0</span>%</strong>
                    </div>
                </aside>
            </section>

            <section class="client-detail-layout">
                <div class="client-detail-main">
                    <article class="card">
                        <div class="card-body">
                            <div class="client-progress-overview">
                                <div class="client-progress-big">
                                    <div class="client-progress-circle" data-progress-circle>
                                        <strong><span data-progress-big>0</span>%</strong>
                                    </div>

                                    <div class="client-progress-copy">
                                        <h2>Tổng quan tiến độ</h2>
                                        <p data-project-description>
                                            Đang tải dữ liệu dự án...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <section class="client-section">
                        <div class="client-section-header">
                            <div>
                                <h2>Task được chia sẻ</h2>
                                <p>Chỉ các task Manager đánh dấu public mới hiển thị cho client.</p>
                            </div>

                            <div class="client-filter-row">
                                <select class="form-select" data-project-select>
                                    <option value="">Đang tải project...</option>
                                </select>

                                <select class="form-select" data-status-filter>
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="To do">To do</option>
                                    <option value="Doing">Doing</option>
                                    <option value="Review">Review</option>
                                    <option value="Done">Done</option>
                                </select>

                                <input class="form-control" type="search" placeholder="Tìm task..." data-task-search>

                                <button class="btn btn-primary" type="button" data-task-refresh>
                                    Làm mới
                                </button>
                            </div>
                        </div>

                        <div class="client-task-list" data-task-list>
                            <article class="client-task-item">
                                <div class="client-task-info">
                                    <h3>Đang tải task...</h3>
                                    <p>Client Portal đang lấy danh sách task public.</p>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <aside class="client-detail-side">
                    <article class="card">
                        <div class="card-header">
                            <h2>Thông tin dự án</h2>
                            <p>Tóm tắt phạm vi và trạng thái hiện tại.</p>
                        </div>

                        <div class="card-body">
                            <div class="client-side-summary">
                                <div class="client-summary-row">
                                    <span>Tên dự án</span>
                                    <strong data-side-project-name>Đang tải...</strong>
                                </div>

                                <div class="client-summary-row">
                                    <span>Trạng thái</span>
                                    <strong data-side-project-status>Đang tải...</strong>
                                </div>

                                <div class="client-summary-row">
                                    <span>Manager</span>
                                    <strong data-side-manager-name>Đang tải...</strong>
                                </div>

                                <div class="client-summary-row">
                                    <span>Deadline gần nhất</span>
                                    <strong data-side-deadline>Chưa đặt</strong>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="client-contact-card">
                        <h2>Cần chỉnh sửa?</h2>
                        <p>
                            Chọn task bên dưới và gửi feedback trực tiếp. Manager phụ trách sẽ nhận thông báo
                            để xử lý đúng luồng.
                        </p>
                        <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/support.php">
                            Mở trung tâm Feedback
                        </a>
                    </article>
                </aside>
            </section>
        </main>
    </div>

    <script>
        window.CAH_CONFIG = {
            baseUrl: '<?php echo $baseUrl; ?>',
            publicUrl: '<?php echo $publicUrl; ?>',
            viewUrl: '<?php echo $viewUrl; ?>',
            apiRoot: '<?php echo $publicUrl; ?>'
        };
    </script>

    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/app.js?v=<?php echo $cacheBust; ?>"></script>
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/toast.js?v=<?php echo $cacheBust; ?>"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiRoot = window.CAH_CONFIG.apiRoot;
            const viewUrl = window.CAH_CONFIG.viewUrl;

            const clientNameEl = document.querySelector('[data-client-name]');
            const clientAvatarEl = document.querySelector('[data-client-avatar]');
            const logoutButton = document.querySelector('[data-client-logout]');

            const projectSelect = document.querySelector('[data-project-select]');
            const statusFilter = document.querySelector('[data-status-filter]');
            const taskSearch = document.querySelector('[data-task-search]');
            const refreshButton = document.querySelector('[data-task-refresh]');
            const taskList = document.querySelector('[data-task-list]');

            const projectKicker = document.querySelector('[data-project-kicker]');
            const projectName = document.querySelector('[data-project-name]');
            const sideProjectName = document.querySelector('[data-side-project-name]');
            const sideProjectStatus = document.querySelector('[data-side-project-status]');
            const sideManagerName = document.querySelector('[data-side-manager-name]');
            const sideDeadline = document.querySelector('[data-side-deadline]');
            const projectDescription = document.querySelector('[data-project-description]');

            const statTotalTasks = document.querySelector('[data-stat-total-tasks]');
            const statDoneTasks = document.querySelector('[data-stat-done-tasks]');
            const statProgress = document.querySelector('[data-stat-progress]');
            const progressBig = document.querySelector('[data-progress-big]');
            const progressCircle = document.querySelector('[data-progress-circle]');

            let currentUser = null;
            let projects = [];
            let currentProject = null;
            let tasks = [];
            let commentsByTask = {};

            function getToken() {
                return localStorage.getItem('cah_auth_token') || localStorage.getItem('cah_token') || '';
            }

            function clearAuth() {
                localStorage.removeItem('cah_auth_token');
                localStorage.removeItem('cah_token');
                localStorage.removeItem('cah_auth_user');
                localStorage.removeItem('cah_user');

                document.cookie = 'cah_token=; path=/; max-age=0; SameSite=Lax';
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

            function initialsFromName(name) {
                const parts = String(name || '').trim().split(/\s+/).filter(Boolean);

                if (parts.length === 0) {
                    return 'CL';
                }

                const first = parts[0].charAt(0);
                const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';

                return (first + last).toUpperCase();
            }

            function statusLabel(status) {
                const map = {
                    'To do': 'To do',
                    Doing: 'Đang làm',
                    Review: 'Chờ duyệt',
                    Done: 'Hoàn thành',
                    Active: 'Đang triển khai',
                    Completed: 'Hoàn thành',
                    Archived: 'Đã lưu trữ'
                };

                return map[status] || status || 'Chưa rõ';
            }

            function priorityLabel(priority) {
                const map = {
                    Low: 'Thấp',
                    Medium: 'Trung bình',
                    High: 'Cao'
                };

                return map[priority] || priority || 'Trung bình';
            }

            function badgeClassByStatus(status) {
                if (status === 'Done') {
                    return 'badge-success';
                }

                if (status === 'Review') {
                    return 'badge-warning';
                }

                if (status === 'Doing') {
                    return 'badge-info';
                }

                return 'badge-light';
            }

            function badgeClassByPriority(priority) {
                if (priority === 'High') {
                    return 'badge-danger';
                }

                if (priority === 'Low') {
                    return 'badge-info';
                }

                return 'badge-warning';
            }

            function formatDate(dateValue) {
                if (!dateValue) {
                    return 'Chưa đặt';
                }

                const date = new Date(String(dateValue) + 'T00:00:00');

                if (Number.isNaN(date.getTime())) {
                    return dateValue;
                }

                return date.toLocaleDateString('vi-VN');
            }

            function getQueryParam(name) {
                return new URL(window.location.href).searchParams.get(name);
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

            function requireClientSession() {
                if (!getToken()) {
                    window.location.href = viewUrl + '/client-portal/login-client.php';
                    return false;
                }

                return true;
            }

            async function loadCurrentUser() {
                const payload = await apiRequest('/api/auth/me');
                const user = payload.data && payload.data.user ? payload.data.user : payload.data;

                currentUser = user || null;

                if (!currentUser || String(currentUser.role || '').toLowerCase() !== 'client') {
                    clearAuth();
                    window.location.href = viewUrl + '/client-portal/login-client.php';
                    return;
                }

                const name = currentUser.full_name || currentUser.name || currentUser.email || 'Client';

                clientNameEl.textContent = name;
                clientAvatarEl.textContent = initialsFromName(name);
            }

            function normalizeProject(project) {
                return {
                    id: Number(project.id || 0),
                    name: project.name || 'Dự án chưa đặt tên',
                    description: project.description || 'Chưa có mô tả dự án.',
                    status: project.status || 'Active',
                    manager_name: project.manager_name || 'Project Manager',
                    client_name: project.client_name || 'Client',
                    nearest_deadline: project.nearest_deadline || project.deadline || null
                };
            }

            async function loadProjects() {
                const payload = await apiRequest('/api/projects');
                projects = Array.isArray(payload.data) ? payload.data.map(normalizeProject) : [];

                projectSelect.innerHTML = projects.length > 0
                    ? projects.map(function (project) {
                        return `<option value="${project.id}">${escapeHtml(project.name)}</option>`;
                    }).join('')
                    : '<option value="">Chưa có project</option>';

                const queryProjectId = Number(getQueryParam('project_id') || 0);

                if (queryProjectId > 0 && projects.some(function (project) {
                    return project.id === queryProjectId;
                })) {
                    projectSelect.value = String(queryProjectId);
                }

                const selectedId = Number(projectSelect.value || 0);
                currentProject = projects.find(function (project) {
                    return project.id === selectedId;
                }) || projects[0] || null;

                if (currentProject) {
                    projectSelect.value = String(currentProject.id);
                }

                updateProjectInfo();
            }

            function updateProjectInfo() {
                if (!currentProject) {
                    projectKicker.textContent = 'Project Detail';
                    projectName.textContent = 'Chưa có project';
                    sideProjectName.textContent = 'Chưa có project';
                    sideProjectStatus.textContent = 'Chưa rõ';
                    sideManagerName.textContent = 'Chưa rõ';
                    sideDeadline.textContent = 'Chưa đặt';
                    projectDescription.textContent = 'Client hiện chưa được gán project nào.';
                    return;
                }

                projectKicker.textContent = 'Project Detail • ' + currentProject.name;
                projectName.textContent = currentProject.name;
                sideProjectName.textContent = currentProject.name;
                sideProjectStatus.textContent = statusLabel(currentProject.status);
                sideManagerName.textContent = currentProject.manager_name;
                sideDeadline.textContent = formatDate(currentProject.nearest_deadline);
                projectDescription.textContent = currentProject.description;
            }

            async function loadCommentsForTasks(publicTasks) {
                commentsByTask = {};

                await Promise.all(publicTasks.map(async function (task) {
                    try {
                        const payload = await apiRequest('/api/tasks/' + encodeURIComponent(task.id) + '/comments');
                        commentsByTask[task.id] = Array.isArray(payload.data) ? payload.data : [];
                    } catch (error) {
                        commentsByTask[task.id] = [];
                    }
                }));
            }

            async function loadTasks() {
                if (!currentProject) {
                    tasks = [];
                    renderAll();
                    return;
                }

                taskList.innerHTML = `
                    <article class="client-task-item">
                        <div class="client-task-info">
                            <h3>Đang tải task...</h3>
                            <p>Client Portal đang lấy danh sách task public.</p>
                        </div>
                    </article>
                `;

                const payload = await apiRequest('/api/tasks?project_id=' + encodeURIComponent(currentProject.id));
                tasks = Array.isArray(payload.data) ? payload.data : [];

                await loadCommentsForTasks(tasks);
                renderAll();
            }

            function getFilteredTasks() {
                const search = String(taskSearch.value || '').toLowerCase().trim();
                const status = String(statusFilter.value || '').trim();

                return tasks.filter(function (task) {
                    const matchSearch = !search
                        || String(task.title || '').toLowerCase().includes(search)
                        || String(task.description || '').toLowerCase().includes(search)
                        || String(task.assignee_name || '').toLowerCase().includes(search);

                    const matchStatus = !status || String(task.status || '') === status;

                    return matchSearch && matchStatus;
                });
            }

            function updateStats() {
                const total = tasks.length;
                const done = tasks.filter(function (task) {
                    return String(task.status || '') === 'Done';
                }).length;

                const progress = total > 0 ? Math.round(done / total * 100) : 0;

                statTotalTasks.textContent = String(total);
                statDoneTasks.textContent = String(done);
                statProgress.textContent = String(progress);
                progressBig.textContent = String(progress);

                progressCircle.style.background = `conic-gradient(var(--primary) 0 ${progress}%, #edf2f7 ${progress}% 100%)`;
            }

            function renderEmpty(message, description) {
                taskList.innerHTML = `
                    <article class="client-task-item">
                        <div class="client-task-info">
                            <h3>${escapeHtml(message)}</h3>
                            <p>${escapeHtml(description || 'Dữ liệu sẽ hiển thị khi Manager public task cho Client.')}</p>
                        </div>
                    </article>
                `;
            }

            function renderComments(taskId) {
                const comments = commentsByTask[taskId] || [];

                if (comments.length === 0) {
                    return `
                        <div class="client-feedback-list">
                            <div class="client-feedback-item">
                                <div class="client-feedback-avatar">CA</div>
                                <div class="client-feedback-content">
                                    <strong>Chưa có feedback</strong>
                                    <p>Hãy gửi phản hồi đầu tiên cho task này nếu cần chỉnh sửa.</p>
                                    <small>Client Portal</small>
                                </div>
                            </div>
                        </div>
                    `;
                }

                return `
                    <div class="client-feedback-list">
                        ${comments.map(function (comment) {
                            const name = comment.user_name || 'Client';
                            return `
                                <div class="client-feedback-item">
                                    <div class="client-feedback-avatar">${escapeHtml(initialsFromName(name))}</div>
                                    <div class="client-feedback-content">
                                        <strong>${escapeHtml(name)}</strong>
                                        <p>${escapeHtml(comment.comment_text || '')}</p>
                                        <small>${escapeHtml(comment.created_at || '')}</small>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            function renderTasks() {
                const visibleTasks = getFilteredTasks();

                if (!currentProject) {
                    renderEmpty('Chưa có project', 'Client hiện chưa được gán project nào.');
                    return;
                }

                if (visibleTasks.length === 0) {
                    renderEmpty('Chưa có task public phù hợp', 'Không có task nào khớp bộ lọc hiện tại.');
                    return;
                }

                taskList.innerHTML = visibleTasks.map(function (task) {
                    const comments = commentsByTask[task.id] || [];
                    const statusClass = badgeClassByStatus(task.status);
                    const priorityClass = badgeClassByPriority(task.priority);

                    return `
                        <article class="client-task-item" data-task-id="${task.id}">
                            <div class="client-task-info">
                                <div class="client-task-meta">
                                    <span class="badge ${statusClass}">${escapeHtml(statusLabel(task.status))}</span>
                                    <span class="badge ${priorityClass}">${escapeHtml(priorityLabel(task.priority))}</span>
                                    <span class="badge badge-info">Public Client</span>
                                </div>

                                <h3>${escapeHtml(task.title || 'Task chưa đặt tên')}</h3>

                                <p>${escapeHtml(task.description || 'Chưa có mô tả task.')}</p>

                                <div class="client-task-meta">
                                    <span>Deadline: ${escapeHtml(formatDate(task.deadline))}</span>
                                    <span>Assignee: ${escapeHtml(task.assignee_name || 'Đội dự án')}</span>
                                    <span>Feedback: ${comments.length}</span>
                                </div>

                                <div class="client-feedback-box" style="margin-top: 16px;">
                                    ${renderComments(task.id)}

                                    <form data-client-task-feedback-form data-task-id="${task.id}" style="display:grid; gap:12px; margin-top: 14px;">
                                        <textarea
                                            class="form-textarea"
                                            name="comment_text"
                                            rows="3"
                                            placeholder="Nhập feedback cho task này..."
                                            required
                                        ></textarea>

                                        <div style="display:flex; justify-content:flex-end;">
                                            <button class="btn btn-primary" type="submit">
                                                Gửi feedback
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </article>
                    `;
                }).join('');
            }

            function renderAll() {
                updateProjectInfo();
                updateStats();
                renderTasks();
            }

            async function submitFeedback(form) {
                const taskId = Number(form.dataset.taskId || 0);
                const textarea = form.querySelector('textarea[name="comment_text"]');
                const commentText = String(textarea?.value || '').trim();

                if (!taskId || !commentText) {
                    toast('error', 'Thiếu nội dung', 'Vui lòng nhập feedback trước khi gửi.');
                    return;
                }

                try {
                    await apiRequest('/api/tasks/' + encodeURIComponent(taskId) + '/comments', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            comment_text: commentText
                        })
                    });

                    textarea.value = '';
                    toast('success', 'Đã gửi feedback', 'Feedback của bạn đã được gửi tới Manager phụ trách.');
                    await loadTasks();
                } catch (error) {
                    toast('error', 'Không thể gửi feedback', error.message);
                }
            }

            logoutButton.addEventListener('click', function () {
                clearAuth();
                window.location.href = viewUrl + '/client-portal/login-client.php';
            });

            projectSelect.addEventListener('change', async function () {
                const selectedId = Number(projectSelect.value || 0);

                currentProject = projects.find(function (project) {
                    return project.id === selectedId;
                }) || null;

                if (currentProject) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('project_id', String(currentProject.id));
                    window.history.replaceState({}, '', url.toString());
                }

                await loadTasks();
            });

            statusFilter.addEventListener('change', renderTasks);
            taskSearch.addEventListener('input', renderTasks);
            refreshButton.addEventListener('click', loadTasks);

            document.addEventListener('submit', function (event) {
                const form = event.target.closest('[data-client-task-feedback-form]');

                if (!form) {
                    return;
                }

                event.preventDefault();
                submitFeedback(form);
            });

            async function init() {
                if (!requireClientSession()) {
                    return;
                }

                try {
                    await loadCurrentUser();
                    await loadProjects();
                    await loadTasks();
                } catch (error) {
                    toast('error', 'Không thể tải Client Portal', error.message);
                    clearAuth();
                    window.location.href = viewUrl + '/client-portal/login-client.php';
                }
            }

            init();
        });
    </script>
</body>
</html>