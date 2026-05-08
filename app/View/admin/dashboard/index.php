<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Admin Home | Creative Agency Hub';
$pageCss = ['dashboard.css', 'hrm.css', 'tasks.css'];
$pageJs = [];
$activeMenu = 'dashboard';
$topbarTitle = 'Admin Home';
$brandName = 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$publicUrl = $baseUrl . '/public';
$viewUrl = $baseUrl . '/app/View';

ob_start();
?>

<?php
$pageHeading = 'Admin Home';
$pageSubtitle = 'Quản trị hệ thống, kiểm soát tài khoản và theo dõi sức khỏe tổng quan của nền tảng.';
$pageAction = '
    <button class="btn btn-light" type="button" data-admin-refresh>⟳ Làm mới</button>
';
require __DIR__ . '/../../components/page-header.php';
?>

<section class="dashboard-grid" data-admin-dashboard>
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◉</div>
            <div class="stat-card-body">
                <span>Tổng tài khoản</span>
                <strong data-stat-total-accounts>0</strong>
                <small>Không tính tài khoản đã xóa mềm</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Đang hoạt động</span>
                <strong data-stat-active-accounts>0</strong>
                <small>Có thể đăng nhập</small>
            </div>
        </article>

        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Chờ duyệt / đóng băng</span>
                <strong data-stat-inactive-accounts>0</strong>
                <small>Không thể đăng nhập</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Bị khóa</span>
                <strong data-stat-suspended-accounts>0</strong>
                <small>Suspended</small>
            </div>
        </article>
    </div>

    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">▣</div>
            <div class="stat-card-body">
                <span>Tổng project</span>
                <strong data-stat-projects>0</strong>
                <small>Admin chỉ theo dõi số lượng</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">☑</div>
            <div class="stat-card-body">
                <span>Tổng task</span>
                <strong data-stat-tasks>0</strong>
                <small>Không vận hành trực tiếp</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◆</div>
            <div class="stat-card-body">
                <span>Manager</span>
                <strong data-stat-managers>0</strong>
                <small>Tài khoản quản lý vận hành</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◇</div>
            <div class="stat-card-body">
                <span>Client</span>
                <strong data-stat-clients>0</strong>
                <small>Tài khoản khách hàng</small>
            </div>
        </article>
    </div>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tài khoản chờ duyệt</h2>
                <p>Manager tạo Employee/Client, Admin duyệt thì tài khoản mới được đăng nhập.</p>
            </div>

            <button class="btn btn-soft" type="button" data-refresh-pending>
                ⟳ Tải lại pending
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Manager tạo</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-pending-table-body>
                        <tr>
                            <td colspan="6">Đang tải tài khoản chờ duyệt...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Quản lý tài khoản hệ thống</h2>
                <p>Admin có quyền kích hoạt, đóng băng, khóa hoặc ban mềm tài khoản.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <div class="input-with-icon">
                    <span class="input-icon">⌕</span>
                    <input
                        class="form-control"
                        type="search"
                        placeholder="Tìm tên, email, mã tài khoản..."
                        data-account-search
                    >
                </div>

                <select class="form-select" data-role-filter>
                    <option value="">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                    <option value="client">Client</option>
                </select>

                <select class="form-select" data-status-filter>
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive / Frozen</option>
                    <option value="suspended">Suspended</option>
                    <option value="resigned">Resigned</option>
                </select>

                <button class="btn btn-soft" type="button" data-apply-filter>
                    Lọc
                </button>
            </div>

            <div class="form-alert form-alert-success" style="margin-bottom: 18px;">
                <strong>Quy tắc:</strong>
                Admin không tạo project/task. Admin chỉ quản trị hệ thống, tài khoản và theo dõi chỉ số tổng quan.
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Phòng ban</th>
                            <th>Manager</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th style="text-align:right;">Quản trị</th>
                        </tr>
                    </thead>
                    <tbody data-account-table-body>
                        <tr>
                            <td colspan="7">Đang tải danh sách tài khoản...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Project Overview</h2>
                <p>Admin chỉ xem số liệu tổng quan, không thao tác vào project.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Manager</th>
                            <th>Client</th>
                            <th>Trạng thái</th>
                            <th>Task</th>
                            <th>Tiến độ</th>
                        </tr>
                    </thead>
                    <tbody data-project-table-body>
                        <tr>
                            <td colspan="6">Đang tải tổng quan project...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = '<?php echo $baseUrl; ?>';
    const publicUrl = '<?php echo $publicUrl; ?>';
    const viewUrl = '<?php echo $viewUrl; ?>';
    const apiRoot = window.CAH_CONFIG?.apiRoot || publicUrl;

    const refreshButton = document.querySelector('[data-admin-refresh]');
    const refreshPendingButton = document.querySelector('[data-refresh-pending]');
    const pendingBody = document.querySelector('[data-pending-table-body]');
    const accountBody = document.querySelector('[data-account-table-body]');
    const projectBody = document.querySelector('[data-project-table-body]');

    const searchInput = document.querySelector('[data-account-search]');
    const roleFilter = document.querySelector('[data-role-filter]');
    const statusFilter = document.querySelector('[data-status-filter]');
    const applyFilterButton = document.querySelector('[data-apply-filter]');

    const statTotalAccounts = document.querySelector('[data-stat-total-accounts]');
    const statActiveAccounts = document.querySelector('[data-stat-active-accounts]');
    const statInactiveAccounts = document.querySelector('[data-stat-inactive-accounts]');
    const statSuspendedAccounts = document.querySelector('[data-stat-suspended-accounts]');
    const statProjects = document.querySelector('[data-stat-projects]');
    const statTasks = document.querySelector('[data-stat-tasks]');
    const statManagers = document.querySelector('[data-stat-managers]');
    const statClients = document.querySelector('[data-stat-clients]');

    let currentUser = null;
    let accounts = [];
    let pendingAccounts = [];
    let projects = [];
    let tasks = [];

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

    function roleLabel(role) {
        const map = {
            admin: 'Admin',
            manager: 'Manager',
            employee: 'Employee',
            client: 'Client'
        };

        return map[String(role || '').toLowerCase()] || role || 'Employee';
    }

    function statusLabel(status) {
        const map = {
            active: 'Active',
            inactive: 'Inactive / Frozen',
            suspended: 'Suspended / Locked',
            resigned: 'Resigned'
        };

        return map[String(status || '').toLowerCase()] || status || 'Chưa rõ';
    }

    function statusBadgeClass(status) {
        status = String(status || '').toLowerCase();

        if (status === 'active') {
            return 'badge-success';
        }

        if (status === 'inactive') {
            return 'badge-warning';
        }

        if (status === 'suspended') {
            return 'badge-danger';
        }

        return 'badge-info';
    }

    function formatDate(value) {
        if (!value) {
            return 'Chưa cập nhật';
        }

        const safeValue = String(value).includes(' ') ? String(value).replace(' ', 'T') : String(value) + 'T00:00:00';
        const date = new Date(safeValue);

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

    function accountIdentityHtml(account) {
        const name = account.full_name || account.name || 'Chưa có tên';
        const email = account.email || 'Chưa có email';
        const code = account.employee_code || ('#' + account.id);

        return `
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="client-avatar" style="width:38px;height:38px;font-size:12px;">
                    ${escapeHtml(initialsFromName(name))}
                </span>
                <div>
                    <strong>${escapeHtml(name)}</strong>
                    <small style="display:block; color: var(--text-muted); margin-top:4px;">
                        ${escapeHtml(email)} • ${escapeHtml(code)}
                    </small>
                </div>
            </div>
        `;
    }

    function buildAccountQuery() {
        const params = new URLSearchParams();

        const search = String(searchInput.value || '').trim();
        const role = String(roleFilter.value || '').trim();
        const status = String(statusFilter.value || '').trim();

        if (search) {
            params.set('search', search);
        }

        if (role) {
            params.set('role', role);
        }

        if (status) {
            params.set('status', status);
        }

        params.set('limit', '300');

        return params.toString();
    }

    async function ensureAdminSession() {
        const payload = await apiRequest('/api/auth/me');
        currentUser = payload.data && payload.data.user ? payload.data.user : payload.data;

        if (!currentUser || String(currentUser.role || '').toLowerCase() !== 'admin') {
            toast('error', 'Không có quyền', 'Trang này chỉ dành cho Admin.');
            window.location.href = viewUrl + '/dashboard/index.php';
            return false;
        }

        return true;
    }

    async function loadAccounts() {
        accountBody.innerHTML = `
            <tr>
                <td colspan="7">Đang tải danh sách tài khoản...</td>
            </tr>
        `;

        try {
            const query = buildAccountQuery();
            const payload = await apiRequest('/api/employees' + (query ? '?' + query : ''));

            accounts = Array.isArray(payload.data) ? payload.data : [];

            renderAccounts();
            updateAccountStats();
        } catch (error) {
            accountBody.innerHTML = `
                <tr>
                    <td colspan="7" style="color: var(--danger);">
                        Không thể tải tài khoản: ${escapeHtml(error.message)}
                    </td>
                </tr>
            `;
        }
    }

    async function loadPendingAccounts() {
        pendingBody.innerHTML = `
            <tr>
                <td colspan="6">Đang tải tài khoản chờ duyệt...</td>
            </tr>
        `;

        try {
            const payload = await apiRequest('/api/admin/accounts/pending');
            pendingAccounts = Array.isArray(payload.data) ? payload.data : [];

            renderPendingAccounts();
            updateAccountStats();
        } catch (error) {
            pendingBody.innerHTML = `
                <tr>
                    <td colspan="6" style="color: var(--danger);">
                        Không thể tải pending: ${escapeHtml(error.message)}
                    </td>
                </tr>
            `;
        }
    }

    async function loadProjects() {
        projectBody.innerHTML = `
            <tr>
                <td colspan="6">Đang tải tổng quan project...</td>
            </tr>
        `;

        try {
            const payload = await apiRequest('/api/projects');
            projects = Array.isArray(payload.data) ? payload.data : [];

            renderProjects();
            updateProjectStats();
        } catch (error) {
            projectBody.innerHTML = `
                <tr>
                    <td colspan="6" style="color: var(--danger);">
                        Không thể tải project overview: ${escapeHtml(error.message)}
                    </td>
                </tr>
            `;
        }
    }

    async function loadTasksForStats() {
        try {
            const payload = await apiRequest('/api/tasks');
            tasks = Array.isArray(payload.data) ? payload.data : [];
        } catch (error) {
            tasks = [];
        }

        updateProjectStats();
    }

    function renderPendingAccounts() {
        if (pendingAccounts.length === 0) {
            pendingBody.innerHTML = `
                <tr>
                    <td colspan="6">Không có tài khoản nào đang chờ duyệt.</td>
                </tr>
            `;
            return;
        }

        pendingBody.innerHTML = pendingAccounts.map(function (account) {
            const isSelf = Number(account.id) === Number(currentUser?.id || 0);

            return `
                <tr data-pending-id="${account.id}">
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.manager_name || 'Không rõ')}</td>
                    <td>${escapeHtml(formatDate(account.created_at))}</td>
                    <td>
                        <span class="badge badge-warning">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap;">
                            <button class="btn btn-emerald btn-sm" type="button" data-admin-approve="${account.id}" ${isSelf ? 'disabled' : ''}>
                                Duyệt
                            </button>
                            <button class="btn btn-light btn-sm" type="button" data-admin-reject="${account.id}" ${isSelf ? 'disabled' : ''}>
                                Từ chối
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderAccounts() {
        if (accounts.length === 0) {
            accountBody.innerHTML = `
                <tr>
                    <td colspan="7">Không có tài khoản phù hợp bộ lọc.</td>
                </tr>
            `;
            return;
        }

        accountBody.innerHTML = accounts.map(function (account) {
            const status = String(account.status || '').toLowerCase();
            const isSelf = Number(account.id) === Number(currentUser?.id || 0);
            const role = String(account.role || '').toLowerCase();

            const canActivate = status !== 'active';
            const canFreeze = status !== 'inactive';
            const canLock = status !== 'suspended';

            const disabledSelf = isSelf ? 'disabled title="Không thao tác lên chính tài khoản đang đăng nhập"' : '';

            return `
                <tr data-account-id="${account.id}">
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(role))}</td>
                    <td>${escapeHtml(account.department_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(account.manager_name || 'Chưa gán')}</td>
                    <td>
                        <span class="badge ${statusBadgeClass(status)}">
                            ${escapeHtml(statusLabel(status))}
                        </span>
                    </td>
                    <td>${escapeHtml(formatDate(account.created_at || account.hire_date))}</td>
                    <td style="text-align:right;">
                        <div style="display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap;">
                            <button
                                class="btn btn-emerald btn-sm"
                                type="button"
                                data-account-status-action="active"
                                data-account-id="${account.id}"
                                ${(!canActivate || isSelf) ? 'disabled' : ''}
                            >
                                Kích hoạt
                            </button>

                            <button
                                class="btn btn-light btn-sm"
                                type="button"
                                data-account-status-action="inactive"
                                data-account-id="${account.id}"
                                ${(!canFreeze || isSelf) ? 'disabled' : ''}
                            >
                                Đóng băng
                            </button>

                            <button
                                class="btn btn-light btn-sm"
                                type="button"
                                data-account-status-action="suspended"
                                data-account-id="${account.id}"
                                ${(!canLock || isSelf) ? 'disabled' : ''}
                            >
                                Khóa
                            </button>

                            <button
                                class="btn btn-danger btn-sm"
                                type="button"
                                data-account-ban="${account.id}"
                                ${isSelf ? disabledSelf : ''}
                            >
                                Ban mềm
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderProjects() {
        if (projects.length === 0) {
            projectBody.innerHTML = `
                <tr>
                    <td colspan="6">Chưa có project nào trong hệ thống.</td>
                </tr>
            `;
            return;
        }

        projectBody.innerHTML = projects.slice(0, 12).map(function (project) {
            const totalTasks = Number(project.total_tasks || 0);
            const doneTasks = Number(project.done_tasks || 0);
            const progress = Number(project.progress || (totalTasks > 0 ? Math.round(doneTasks / totalTasks * 100) : 0));

            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(project.name || 'Project chưa đặt tên')}</strong>
                        <small style="display:block; color: var(--text-muted); margin-top:4px;">
                            ID: ${escapeHtml(project.id)}
                        </small>
                    </td>
                    <td>${escapeHtml(project.manager_name || 'Chưa rõ')}</td>
                    <td>${escapeHtml(project.client_name || 'Chưa gán')}</td>
                    <td>
                        <span class="badge badge-info">
                            ${escapeHtml(project.status || 'Active')}
                        </span>
                    </td>
                    <td>${totalTasks}</td>
                    <td>
                        <div style="display:grid; gap:6px; min-width:120px;">
                            <div class="progress-line">
                                <div class="progress-line-fill" style="width:${Math.max(0, Math.min(100, progress))}%;"></div>
                            </div>
                            <small>${progress}%</small>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateAccountStats() {
        const merged = [...accounts];

        pendingAccounts.forEach(function (pending) {
            if (!merged.some(function (account) {
                return Number(account.id) === Number(pending.id);
            })) {
                merged.push(pending);
            }
        });

        const total = merged.length;
        const active = merged.filter(function (account) {
            return String(account.status || '').toLowerCase() === 'active';
        }).length;
        const inactive = merged.filter(function (account) {
            return String(account.status || '').toLowerCase() === 'inactive';
        }).length;
        const suspended = merged.filter(function (account) {
            return String(account.status || '').toLowerCase() === 'suspended';
        }).length;
        const managers = merged.filter(function (account) {
            return String(account.role || '').toLowerCase() === 'manager';
        }).length;
        const clients = merged.filter(function (account) {
            return String(account.role || '').toLowerCase() === 'client';
        }).length;

        statTotalAccounts.textContent = String(total);
        statActiveAccounts.textContent = String(active);
        statInactiveAccounts.textContent = String(inactive);
        statSuspendedAccounts.textContent = String(suspended);
        statManagers.textContent = String(managers);
        statClients.textContent = String(clients);
    }

    function updateProjectStats() {
        statProjects.textContent = String(projects.length);
        statTasks.textContent = String(tasks.length);
    }

    async function approveAccount(id) {
        try {
            await apiRequest('/api/admin/accounts/' + encodeURIComponent(id) + '/approve', {
                method: 'PATCH'
            });

            toast('success', 'Đã duyệt tài khoản', 'Tài khoản hiện có thể đăng nhập.');
            await reloadAccountsOnly();
        } catch (error) {
            toast('error', 'Không thể duyệt tài khoản', error.message);
        }
    }

    async function rejectAccount(id) {
        const ok = window.confirm('Từ chối tài khoản này? Tài khoản sẽ chuyển sang trạng thái Suspended.');

        if (!ok) {
            return;
        }

        try {
            await apiRequest('/api/admin/accounts/' + encodeURIComponent(id) + '/reject', {
                method: 'PATCH'
            });

            toast('success', 'Đã từ chối tài khoản', 'Tài khoản đã chuyển sang Suspended.');
            await reloadAccountsOnly();
        } catch (error) {
            toast('error', 'Không thể từ chối tài khoản', error.message);
        }
    }

    async function updateAccountStatus(id, nextStatus) {
        const labels = {
            active: 'kích hoạt',
            inactive: 'đóng băng',
            suspended: 'khóa'
        };

        const ok = window.confirm('Xác nhận ' + (labels[nextStatus] || 'cập nhật') + ' tài khoản này?');

        if (!ok) {
            return;
        }

        try {
            await apiRequest('/api/employees/' + encodeURIComponent(id), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    status: nextStatus
                })
            });

            toast('success', 'Đã cập nhật tài khoản', 'Trạng thái tài khoản đã được cập nhật.');
            await reloadAccountsOnly();
        } catch (error) {
            toast('error', 'Không thể cập nhật tài khoản', error.message);
        }
    }

    async function softBanAccount(id) {
        const ok = window.confirm('Ban mềm tài khoản này? Tài khoản sẽ bị xóa mềm khỏi danh sách sử dụng.');

        if (!ok) {
            return;
        }

        try {
            await apiRequest('/api/employees/' + encodeURIComponent(id), {
                method: 'DELETE'
            });

            toast('success', 'Đã ban mềm tài khoản', 'Tài khoản đã được xóa mềm khỏi hệ thống.');
            await reloadAccountsOnly();
        } catch (error) {
            toast('error', 'Không thể ban mềm tài khoản', error.message);
        }
    }

    async function reloadAccountsOnly() {
        await loadPendingAccounts();
        await loadAccounts();
    }

    async function reloadAll() {
        await loadPendingAccounts();
        await loadAccounts();
        await loadProjects();
        await loadTasksForStats();
    }

    refreshButton.addEventListener('click', reloadAll);
    refreshPendingButton.addEventListener('click', loadPendingAccounts);

    applyFilterButton.addEventListener('click', loadAccounts);

    searchInput.addEventListener('input', function () {
        window.clearTimeout(searchInput._timer);
        searchInput._timer = window.setTimeout(loadAccounts, 260);
    });

    roleFilter.addEventListener('change', loadAccounts);
    statusFilter.addEventListener('change', loadAccounts);

    document.addEventListener('click', function (event) {
        const approveButton = event.target.closest('[data-admin-approve]');
        const rejectButton = event.target.closest('[data-admin-reject]');
        const statusButton = event.target.closest('[data-account-status-action]');
        const banButton = event.target.closest('[data-account-ban]');

        if (approveButton) {
            approveAccount(approveButton.getAttribute('data-admin-approve'));
        }

        if (rejectButton) {
            rejectAccount(rejectButton.getAttribute('data-admin-reject'));
        }

        if (statusButton) {
            updateAccountStatus(
                statusButton.getAttribute('data-account-id'),
                statusButton.getAttribute('data-account-status-action')
            );
        }

        if (banButton) {
            softBanAccount(banButton.getAttribute('data-account-ban'));
        }
    });

    async function init() {
        try {
            const isAdmin = await ensureAdminSession();

            if (!isAdmin) {
                return;
            }

            await reloadAll();
        } catch (error) {
            toast('error', 'Không thể tải Admin Home', error.message);
        }
    }

    init();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/app.php';
?>