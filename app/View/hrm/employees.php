<?php
$pageTitle = 'Nhân sự & Tài khoản | Creative Agency Hub';
$pageCss = ['hrm.css', 'dashboard.css'];
$pageJs = [];
$activeMenu = 'employees';
$topbarTitle = 'Nhân sự & Tài khoản';
$brandName = 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$publicUrl = $baseUrl . '/public';
$viewUrl = $baseUrl . '/app/View';

ob_start();
?>

<?php
$pageHeading = 'Nhân sự & Tài khoản';
$pageSubtitle = 'Quản lý nhân sự, tài khoản client/employee và luồng duyệt tài khoản theo vai trò.';
$pageAction = '
    <button class="btn btn-primary" type="button" data-open-create-account style="display:none;">
        ＋ Tạo tài khoản
    </button>
    <button class="btn btn-light" type="button" data-refresh-accounts>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../components/page-header.php';
?>

<section class="hrm-grid" data-account-governance-page>
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◉</div>
            <div class="stat-card-body">
                <span>Tổng tài khoản</span>
                <strong data-stat-total>0</strong>
                <small>Trong phạm vi quyền xem</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Đang hoạt động</span>
                <strong data-stat-active>0</strong>
                <small>Có thể đăng nhập</small>
            </div>
        </article>

        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Chờ duyệt</span>
                <strong data-stat-pending>0</strong>
                <small>Đợi Admin xử lý</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Bị khóa</span>
                <strong data-stat-suspended>0</strong>
                <small>Suspended</small>
            </div>
        </article>
    </div>

    <article class="card" data-create-account-panel style="display:none;">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tạo tài khoản chờ duyệt</h2>
                <p>Manager tạo Employee/Client. Admin duyệt xong tài khoản mới đăng nhập được.</p>
            </div>

            <button class="btn btn-light" type="button" data-close-create-account>
                Đóng
            </button>
        </div>

        <div class="card-body">
            <form data-create-account-form>
                <div class="hrm-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="account_full_name">Họ và tên</label>
                        <input
                            id="account_full_name"
                            class="form-control"
                            type="text"
                            name="full_name"
                            placeholder="VD: Nguyễn Văn A"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_email">Email</label>
                        <input
                            id="account_email"
                            class="form-control"
                            type="email"
                            name="email"
                            placeholder="name@agency.vn"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_password">Mật khẩu mặc định</label>
                        <input
                            id="account_password"
                            class="form-control"
                            type="text"
                            name="password"
                            value="123456"
                            minlength="6"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_role">Loại tài khoản</label>
                        <select id="account_role" class="form-select" name="role" required>
                            <option value="employee">Employee</option>
                            <option value="client">Client</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_department">Phòng ban</label>
                        <select id="account_department" class="form-select" name="department_id">
                            <option value="">Tự chọn mặc định</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_position">Chức vụ</label>
                        <select id="account_position" class="form-select" name="position_id">
                            <option value="">Tự chọn mặc định</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_phone">Số điện thoại</label>
                        <input
                            id="account_phone"
                            class="form-control"
                            type="text"
                            name="phone"
                            maxlength="20"
                            placeholder="Tuỳ chọn"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_gender">Giới tính</label>
                        <select id="account_gender" class="form-select" name="gender">
                            <option value="">Chưa cập nhật</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 22px; flex-wrap:wrap;">
                    <button class="btn btn-light" type="button" data-close-create-account>
                        Hủy
                    </button>
                    <button class="btn btn-primary" type="submit">
                        Gửi Admin duyệt
                    </button>
                </div>
            </form>
        </div>
    </article>

    <article class="card" data-admin-pending-card style="display:none;">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tài khoản chờ Admin duyệt</h2>
                <p>Approve để kích hoạt đăng nhập, Reject để khóa tài khoản.</p>
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
                            <th>Nhân sự</th>
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
                <h2>Danh sách tài khoản</h2>
                <p data-list-description>Danh sách tài khoản trong phạm vi quyền của bạn.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <div class="input-with-icon">
                    <span class="input-icon">⌕</span>
                    <input class="form-control" type="search" placeholder="Tìm tên, email, mã..." data-account-search>
                </div>

                <select class="form-select" data-account-role-filter>
                    <option value="">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                    <option value="client">Client</option>
                </select>

                <select class="form-select" data-account-status-filter>
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="inactive">Chờ duyệt</option>
                    <option value="suspended">Suspended</option>
                    <option value="resigned">Resigned</option>
                </select>

                <button class="btn btn-soft" type="button" data-apply-account-filter>
                    Lọc
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Manager</th>
                            <th>Trạng thái</th>
                            <th>Ngày vào</th>
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
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiRoot = '<?php echo $publicUrl; ?>';

    const createButton = document.querySelector('[data-open-create-account]');
    const refreshButton = document.querySelector('[data-refresh-accounts]');
    const createPanel = document.querySelector('[data-create-account-panel]');
    const createForm = document.querySelector('[data-create-account-form]');
    const closeCreateButtons = document.querySelectorAll('[data-close-create-account]');

    const adminPendingCard = document.querySelector('[data-admin-pending-card]');
    const pendingBody = document.querySelector('[data-pending-table-body]');
    const refreshPendingButton = document.querySelector('[data-refresh-pending]');

    const accountBody = document.querySelector('[data-account-table-body]');
    const searchInput = document.querySelector('[data-account-search]');
    const roleFilter = document.querySelector('[data-account-role-filter]');
    const statusFilter = document.querySelector('[data-account-status-filter]');
    const applyFilterButton = document.querySelector('[data-apply-account-filter]');
    const listDescription = document.querySelector('[data-list-description]');

    const departmentSelect = document.querySelector('#account_department');
    const positionSelect = document.querySelector('#account_position');

    const statTotal = document.querySelector('[data-stat-total]');
    const statActive = document.querySelector('[data-stat-active]');
    const statPending = document.querySelector('[data-stat-pending]');
    const statSuspended = document.querySelector('[data-stat-suspended]');

    let currentUser = null;
    let accounts = [];
    let pendingAccounts = [];

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
            inactive: 'Chờ duyệt',
            suspended: 'Suspended',
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

        const date = new Date(String(value).replace(' ', 'T'));

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

    async function loadCurrentUser() {
        const payload = await apiRequest('/api/auth/me');
        currentUser = payload.data && payload.data.user ? payload.data.user : payload.data;

        const role = String(currentUser?.role || '').toLowerCase();

        if (role === 'manager') {
            createButton.style.display = 'inline-flex';
            listDescription.textContent = 'Manager xem các tài khoản mình tạo/quản lý. Tài khoản mới sẽ chờ Admin duyệt.';
        }

        if (role === 'admin') {
            adminPendingCard.style.display = 'block';
            listDescription.textContent = 'Admin xem toàn bộ tài khoản và duyệt các tài khoản Manager gửi lên.';
        }
    }

    async function loadOrganizationOptions() {
        try {
            const payload = await apiRequest('/api/organization/data');
            const data = payload.data || {};
            const departments = Array.isArray(data.departments) ? data.departments : [];
            const positions = Array.isArray(data.positions) ? data.positions : [];

            departmentSelect.innerHTML = '<option value="">Tự chọn mặc định</option>' + departments.map(function (department) {
                return `<option value="${department.id}">${escapeHtml(department.name)}</option>`;
            }).join('');

            positionSelect.innerHTML = '<option value="">Tự chọn mặc định</option>' + positions.map(function (position) {
                return `<option value="${position.id}">${escapeHtml(position.name)}</option>`;
            }).join('');
        } catch (error) {
            departmentSelect.innerHTML = '<option value="">Tự chọn mặc định</option>';
            positionSelect.innerHTML = '<option value="">Tự chọn mặc định</option>';
        }
    }

    function buildEmployeesQuery() {
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

        params.set('limit', '200');

        return params.toString();
    }

    async function loadAccounts() {
        accountBody.innerHTML = `
            <tr>
                <td colspan="7">Đang tải danh sách tài khoản...</td>
            </tr>
        `;

        try {
            const query = buildEmployeesQuery();
            const payload = await apiRequest('/api/employees' + (query ? '?' + query : ''));

            accounts = Array.isArray(payload.data) ? payload.data : [];

            renderAccounts();
            updateStats();
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
        if (!currentUser || String(currentUser.role || '').toLowerCase() !== 'admin') {
            return;
        }

        pendingBody.innerHTML = `
            <tr>
                <td colspan="6">Đang tải tài khoản chờ duyệt...</td>
            </tr>
        `;

        try {
            const payload = await apiRequest('/api/admin/accounts/pending');
            pendingAccounts = Array.isArray(payload.data) ? payload.data : [];

            renderPendingAccounts();
            updateStats();
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
            return `
                <tr>
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.department_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(account.position_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(account.manager_name || 'Chưa gán')}</td>
                    <td>
                        <span class="badge ${statusBadgeClass(account.status)}">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                    <td>${escapeHtml(formatDate(account.hire_date || account.created_at))}</td>
                </tr>
            `;
        }).join('');
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
            return `
                <tr data-pending-account-id="${account.id}">
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
                            <button class="btn btn-emerald btn-sm" type="button" data-approve-account="${account.id}">
                                Duyệt
                            </button>
                            <button class="btn btn-light btn-sm" type="button" data-reject-account="${account.id}">
                                Từ chối
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateStats() {
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
        const pending = merged.filter(function (account) {
            return String(account.status || '').toLowerCase() === 'inactive';
        }).length;
        const suspended = merged.filter(function (account) {
            return String(account.status || '').toLowerCase() === 'suspended';
        }).length;

        statTotal.textContent = String(total);
        statActive.textContent = String(active);
        statPending.textContent = String(pending);
        statSuspended.textContent = String(suspended);
    }

    function toggleCreatePanel(forceOpen = null) {
        const isOpen = createPanel.style.display !== 'none';
        const nextOpen = forceOpen === null ? !isOpen : Boolean(forceOpen);

        createPanel.style.display = nextOpen ? 'block' : 'none';

        if (nextOpen) {
            createPanel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    async function createPendingAccount(form) {
        const formData = new FormData(form);

        const payload = {
            full_name: String(formData.get('full_name') || '').trim(),
            email: String(formData.get('email') || '').trim(),
            password: String(formData.get('password') || '').trim(),
            role: String(formData.get('role') || 'employee').trim(),
            department_id: formData.get('department_id') ? Number(formData.get('department_id')) : null,
            position_id: formData.get('position_id') ? Number(formData.get('position_id')) : null,
            phone: String(formData.get('phone') || '').trim(),
            gender: String(formData.get('gender') || '').trim()
        };

        if (!payload.full_name || !payload.email || !payload.password || !payload.role) {
            toast('error', 'Thiếu thông tin', 'Vui lòng nhập đầy đủ họ tên, email, mật khẩu và vai trò.');
            return;
        }

        try {
            await apiRequest('/api/accounts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            toast('success', 'Đã gửi Admin duyệt', 'Tài khoản mới đang ở trạng thái chờ duyệt.');
            form.reset();
            document.querySelector('#account_password').value = '123456';
            toggleCreatePanel(false);

            await loadAccounts();
        } catch (error) {
            toast('error', 'Không thể tạo tài khoản', error.message);
        }
    }

    async function approveAccount(id) {
        try {
            await apiRequest('/api/admin/accounts/' + encodeURIComponent(id) + '/approve', {
                method: 'PATCH'
            });

            toast('success', 'Đã duyệt tài khoản', 'Tài khoản hiện có thể đăng nhập.');
            await loadPendingAccounts();
            await loadAccounts();
        } catch (error) {
            toast('error', 'Không thể duyệt', error.message);
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
            await loadPendingAccounts();
            await loadAccounts();
        } catch (error) {
            toast('error', 'Không thể từ chối', error.message);
        }
    }

    createButton.addEventListener('click', function () {
        toggleCreatePanel(true);
    });

    refreshButton.addEventListener('click', async function () {
        await loadAccounts();
        await loadPendingAccounts();
    });

    refreshPendingButton.addEventListener('click', loadPendingAccounts);

    closeCreateButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            toggleCreatePanel(false);
        });
    });

    createForm.addEventListener('submit', function (event) {
        event.preventDefault();
        createPendingAccount(createForm);
    });

    applyFilterButton.addEventListener('click', loadAccounts);
    searchInput.addEventListener('input', function () {
        window.clearTimeout(searchInput._timer);
        searchInput._timer = window.setTimeout(loadAccounts, 280);
    });
    roleFilter.addEventListener('change', loadAccounts);
    statusFilter.addEventListener('change', loadAccounts);

    document.addEventListener('click', function (event) {
        const approveButton = event.target.closest('[data-approve-account]');
        const rejectButton = event.target.closest('[data-reject-account]');

        if (approveButton) {
            approveAccount(approveButton.getAttribute('data-approve-account'));
        }

        if (rejectButton) {
            rejectAccount(rejectButton.getAttribute('data-reject-account'));
        }
    });

    async function init() {
        try {
            await loadCurrentUser();
            await loadOrganizationOptions();
            await loadAccounts();
            await loadPendingAccounts();
        } catch (error) {
            toast('error', 'Không thể tải trang nhân sự', error.message);
        }
    }

    init();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>