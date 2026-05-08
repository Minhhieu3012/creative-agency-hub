<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Dự án của tôi | Creative Agency Hub';

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
                <a class="client-nav-link is-active" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php">
                    Dự án
                </a>
                <a class="client-nav-link" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/tasks.php">
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
                    <span class="client-kicker">Client Portal</span>
                    <h1>Dự án của bạn,<br>minh bạch từng bước.</h1>
                    <p>
                        Theo dõi tiến độ các project được gán cho bạn, xem task đã public,
                        tải file thiết kế và gửi feedback trực tiếp tới đội ngũ phụ trách.
                    </p>
                </div>

                <aside class="client-hero-panel">
                    <div class="client-hero-panel-row">
                        <span>Dự án đang theo dõi</span>
                        <strong data-stat-projects>0</strong>
                    </div>
                    <div class="client-hero-panel-row">
                        <span>Task public</span>
                        <strong data-stat-tasks>0</strong>
                    </div>
                    <div class="client-hero-panel-row">
                        <span>Tiến độ trung bình</span>
                        <strong><span data-stat-progress>0</span>%</strong>
                    </div>
                </aside>
            </section>

            <section class="client-section">
                <div class="client-section-header">
                    <div>
                        <h2>Danh sách dự án</h2>
                        <p>Chỉ hiển thị những project được gán cho tài khoản client hiện tại.</p>
                    </div>

                    <div class="client-filter-row">
                        <input
                            class="form-control"
                            type="search"
                            placeholder="Tìm dự án..."
                            data-project-search
                        >

                        <select class="form-select" data-project-status>
                            <option value="">Tất cả trạng thái</option>
                            <option value="Active">Đang triển khai</option>
                            <option value="Completed">Hoàn thành</option>
                            <option value="Archived">Đã lưu trữ</option>
                        </select>

                        <button class="btn btn-primary" type="button" data-project-refresh>
                            Làm mới
                        </button>
                    </div>
                </div>

                <div class="client-project-grid" data-project-list>
                    <article class="client-project-card" style="grid-column: 1 / -1;">
                        <div class="client-project-card-header">
                            <div class="client-project-card-title">
                                <h2>Đang tải dự án...</h2>
                            </div>
                            <p>Hệ thống đang lấy dữ liệu Client Portal.</p>
                        </div>
                    </article>
                </div>
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
    <script src="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/js/client-portal.js?v=<?php echo $cacheBust; ?>"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiRoot = window.CAH_CONFIG.apiRoot;
            const viewUrl = window.CAH_CONFIG.viewUrl;

            const projectList = document.querySelector('[data-project-list]');
            const searchInput = document.querySelector('[data-project-search]');
            const statusSelect = document.querySelector('[data-project-status]');
            const refreshButton = document.querySelector('[data-project-refresh]');
            const logoutButton = document.querySelector('[data-client-logout]');

            const clientNameEl = document.querySelector('[data-client-name]');
            const clientAvatarEl = document.querySelector('[data-client-avatar]');

            const statProjects = document.querySelector('[data-stat-projects]');
            const statTasks = document.querySelector('[data-stat-tasks]');
            const statProgress = document.querySelector('[data-stat-progress]');

            let currentUser = null;
            let projects = [];

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
                    Active: 'Đang triển khai',
                    Completed: 'Hoàn thành',
                    Archived: 'Đã lưu trữ'
                };

                return map[status] || status || 'Đang triển khai';
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

            function clearAuth() {
                localStorage.removeItem('cah_auth_token');
                localStorage.removeItem('cah_token');
                localStorage.removeItem('cah_auth_user');
                localStorage.removeItem('cah_user');

                document.cookie = 'cah_token=; path=/; max-age=0; SameSite=Lax';
            }

            function requireClientSession() {
                const token = getToken();

                if (!token) {
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

            async function loadPublicTaskStats(projectId) {
                try {
                    const payload = await apiRequest('/api/tasks?project_id=' + encodeURIComponent(projectId));
                    const tasks = Array.isArray(payload.data) ? payload.data : [];

                    const total = tasks.length;
                    const done = tasks.filter(function (task) {
                        return String(task.status || '') === 'Done';
                    }).length;

                    return {
                        total_public_tasks: total,
                        done_public_tasks: done,
                        progress_public: total > 0 ? Math.round(done / total * 100) : 0
                    };
                } catch (error) {
                    return {
                        total_public_tasks: 0,
                        done_public_tasks: 0,
                        progress_public: 0
                    };
                }
            }

            async function hydrateProjects(rawProjects) {
                const hydrated = [];

                for (const project of rawProjects) {
                    const stats = await loadPublicTaskStats(project.id);

                    hydrated.push({
                        id: Number(project.id || 0),
                        name: project.name || 'Dự án chưa đặt tên',
                        description: project.description || 'Chưa có mô tả dự án.',
                        status: project.status || 'Active',
                        manager_name: project.manager_name || 'Chưa rõ manager',
                        client_name: project.client_name || 'Client',
                        nearest_deadline: project.nearest_deadline || null,
                        total_public_tasks: stats.total_public_tasks,
                        done_public_tasks: stats.done_public_tasks,
                        progress: stats.progress_public
                    });
                }

                return hydrated;
            }

            function updateStats() {
                const totalProjects = projects.length;
                const totalTasks = projects.reduce(function (sum, project) {
                    return sum + Number(project.total_public_tasks || 0);
                }, 0);

                const avgProgress = totalProjects > 0
                    ? Math.round(projects.reduce(function (sum, project) {
                        return sum + Number(project.progress || 0);
                    }, 0) / totalProjects)
                    : 0;

                statProjects.textContent = String(totalProjects);
                statTasks.textContent = String(totalTasks);
                statProgress.textContent = String(avgProgress);
            }

            function filteredProjects() {
                const search = String(searchInput.value || '').toLowerCase().trim();
                const status = String(statusSelect.value || '').trim();

                return projects.filter(function (project) {
                    const matchSearch = !search
                        || String(project.name || '').toLowerCase().includes(search)
                        || String(project.description || '').toLowerCase().includes(search)
                        || String(project.manager_name || '').toLowerCase().includes(search);

                    const matchStatus = !status || project.status === status;

                    return matchSearch && matchStatus;
                });
            }

            function renderEmpty(message) {
                projectList.innerHTML = `
                    <article class="client-project-card" style="grid-column: 1 / -1;">
                        <div class="client-project-card-header">
                            <div class="client-project-card-title">
                                <h2>${escapeHtml(message)}</h2>
                            </div>
                            <p>Dữ liệu sẽ hiển thị khi project được manager gán cho client này.</p>
                        </div>
                    </article>
                `;
            }

            function renderProjects() {
                const visibleProjects = filteredProjects();

                if (visibleProjects.length === 0) {
                    renderEmpty('Chưa có dự án phù hợp');
                    return;
                }

                projectList.innerHTML = visibleProjects.map(function (project) {
                    const progress = Math.max(0, Math.min(100, Number(project.progress || 0)));
                    const remainingTasks = Math.max(0, Number(project.total_public_tasks || 0) - Number(project.done_public_tasks || 0));

                    return `
                        <article class="client-project-card">
                            <div class="client-project-card-header">
                                <div class="client-project-card-title">
                                    <h2>${escapeHtml(project.name)}</h2>
                                    <span class="badge badge-success">${escapeHtml(statusLabel(project.status))}</span>
                                </div>

                                <p>${escapeHtml(project.description)}</p>
                            </div>

                            <div class="client-project-meta">
                                <div class="progress-line">
                                    <div class="progress-line-fill" style="width: ${progress}%;"></div>
                                </div>

                                <div class="client-project-stats">
                                    <div class="client-project-stat">
                                        <strong>${progress}%</strong>
                                        <span>Tiến độ</span>
                                    </div>

                                    <div class="client-project-stat">
                                        <strong>${Number(project.total_public_tasks || 0)}</strong>
                                        <span>Task public</span>
                                    </div>

                                    <div class="client-project-stat">
                                        <strong>${remainingTasks}</strong>
                                        <span>Đang mở</span>
                                    </div>
                                </div>

                                <div class="client-side-summary">
                                    <div class="client-summary-row">
                                        <span>Manager phụ trách</span>
                                        <strong>${escapeHtml(project.manager_name)}</strong>
                                    </div>

                                    <div class="client-summary-row">
                                        <span>Deadline gần nhất</span>
                                        <strong>${escapeHtml(formatDate(project.nearest_deadline))}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="client-project-footer">
                                <div class="client-manager">
                                    <span class="client-manager-avatar">${escapeHtml(initialsFromName(project.manager_name))}</span>
                                    <span>${escapeHtml(project.manager_name)}</span>
                                </div>

                                <a class="btn btn-primary" href="${viewUrl}/client-portal/tasks.php?project_id=${project.id}">
                                    Xem chi tiết
                                </a>
                            </div>
                        </article>
                    `;
                }).join('');
            }

            async function loadProjects() {
                projectList.innerHTML = `
                    <article class="client-project-card" style="grid-column: 1 / -1;">
                        <div class="client-project-card-header">
                            <div class="client-project-card-title">
                                <h2>Đang tải dữ liệu...</h2>
                            </div>
                            <p>Client Portal đang lấy danh sách project của bạn.</p>
                        </div>
                    </article>
                `;

                try {
                    const payload = await apiRequest('/api/projects');
                    const rawProjects = Array.isArray(payload.data) ? payload.data : [];

                    projects = await hydrateProjects(rawProjects);

                    updateStats();
                    renderProjects();
                } catch (error) {
                    renderEmpty('Không thể tải dự án');
                    toast('error', 'Không thể tải dự án', error.message);
                }
            }

            logoutButton.addEventListener('click', function () {
                clearAuth();
                window.location.href = viewUrl + '/client-portal/login-client.php';
            });

            searchInput.addEventListener('input', renderProjects);
            statusSelect.addEventListener('change', renderProjects);
            refreshButton.addEventListener('click', loadProjects);

            async function init() {
                if (!requireClientSession()) {
                    return;
                }

                try {
                    await loadCurrentUser();
                    await loadProjects();
                } catch (error) {
                    clearAuth();
                    window.location.href = viewUrl + '/client-portal/login-client.php';
                }
            }

            init();
        });
    </script>
</body>
</html>