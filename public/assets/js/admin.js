(function () {
    "use strict";

    const API_ROOT = window.CAH_CONFIG?.apiRoot || "/creative-agency-hub/public";

    const state = {
        currentUser: null,
        accounts: [],
        pendingAccounts: [],
        projects: [],
        tasks: []
    };

    function qs(selector, scope = document) {
        return scope.querySelector(selector);
    }

    function qsa(selector, scope = document) {
        return Array.from(scope.querySelectorAll(selector));
    }

    function getToken() {
        return localStorage.getItem("cah_auth_token") || localStorage.getItem("cah_token") || "";
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    async function apiRequest(path, options = {}) {
        const headers = {
            Accept: "application/json",
            ...(options.headers || {})
        };

        const token = getToken();

        if (token) {
            headers.Authorization = "Bearer " + token;
        }

        const response = await fetch(API_ROOT + path, {
            credentials: "same-origin",
            ...options,
            headers
        });

        const text = await response.text();

        let payload;

        try {
            payload = JSON.parse(text);
        } catch (error) {
            console.error("API không trả JSON:", path, text);
            throw new Error("Server không trả JSON hợp lệ.");
        }

        if (!response.ok || payload.status === "error") {
            throw new Error(payload.message || "Yêu cầu không thành công.");
        }

        return payload;
    }

    function toast(type, title, message) {
        if (window.CAHToast && typeof window.CAHToast[type] === "function") {
            window.CAHToast[type](title, message);
            return;
        }

        if (type === "error") {
            console.error(title, message);
            return;
        }

        console.log(title, message);
    }

    function roleLabel(role) {
        const map = {
            admin: "Admin",
            manager: "Manager",
            employee: "Employee",
            client: "Client"
        };

        return map[String(role || "").toLowerCase()] || role || "User";
    }

    function statusLabel(status) {
        const map = {
            active: "Active",
            inactive: "Chờ duyệt",
            suspended: "Suspended",
            resigned: "Resigned"
        };

        return map[String(status || "").toLowerCase()] || status || "Chưa rõ";
    }

    function statusBadgeClass(status) {
        const value = String(status || "").toLowerCase();

        if (value === "active") {
            return "badge-success";
        }

        if (value === "inactive") {
            return "badge-warning";
        }

        if (value === "suspended") {
            return "badge-danger";
        }

        return "badge-info";
    }

    function formatDate(value) {
        if (!value) {
            return "Chưa cập nhật";
        }

        const normalized = String(value).replace(" ", "T");
        const date = new Date(normalized);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleDateString("vi-VN");
    }

    function initialsFromName(name) {
        const parts = String(name || "").trim().split(/\s+/).filter(Boolean);

        if (parts.length === 0) {
            return "CA";
        }

        const first = parts[0].charAt(0);
        const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : "";

        return (first + last).toUpperCase();
    }

    function accountIdentityHtml(account) {
        const name = account.full_name || account.name || "Chưa có tên";
        const email = account.email || "Chưa có email";
        const code = account.employee_code || ("#" + account.id);

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

    function extractAccounts(payload) {
        const data = payload?.data;

        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.accounts)) {
            return data.accounts;
        }

        return [];
    }

    function extractArray(payload) {
        const data = payload?.data;

        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.items)) {
            return data.items;
        }

        if (Array.isArray(data?.projects)) {
            return data.projects;
        }

        if (Array.isArray(data?.tasks)) {
            return data.tasks;
        }

        return [];
    }

    async function loadCurrentUser() {
        const payload = await apiRequest("/api/auth/me");
        state.currentUser = payload?.data?.user || payload?.data || null;

        const role = String(state.currentUser?.role || "").toLowerCase();

        if (role !== "admin") {
            throw new Error("Trang này chỉ dành cho Admin.");
        }
    }

    async function loadAccounts(params = {}) {
        const query = new URLSearchParams();

        if (params.search) {
            query.set("search", params.search);
        }

        if (params.role) {
            query.set("role", params.role);
        }

        if (params.status) {
            query.set("status", params.status);
        }

        query.set("limit", "1000");

        const payload = await apiRequest("/api/admin/accounts?" + query.toString());
        state.accounts = extractAccounts(payload);

        return state.accounts;
    }

    async function loadPendingAccounts() {
        const payload = await apiRequest("/api/admin/accounts/pending");
        state.pendingAccounts = extractAccounts(payload);

        return state.pendingAccounts;
    }

    async function loadProjects() {
        try {
            const payload = await apiRequest("/api/projects");
            state.projects = extractArray(payload);
        } catch (error) {
            state.projects = [];
        }

        return state.projects;
    }

    async function loadTasks() {
        try {
            const payload = await apiRequest("/api/tasks");
            state.tasks = extractArray(payload);
        } catch (error) {
            state.tasks = [];
        }

        return state.tasks;
    }

    function countByRole(role) {
        return state.accounts.filter(function (account) {
            return String(account.role || "").toLowerCase() === role;
        }).length;
    }

    function countByStatus(status) {
        return state.accounts.filter(function (account) {
            return String(account.status || "").toLowerCase() === status;
        }).length;
    }

    function setText(selector, value, scope = document) {
        const el = qs(selector, scope);

        if (el) {
            el.textContent = String(value);
        }
    }

    function renderDashboardStats() {
        setText('[data-admin-stat="total_accounts"]', state.accounts.length);
        setText('[data-admin-stat="managers"]', countByRole("manager"));
        setText('[data-admin-stat="employees"]', countByRole("employee"));
        setText('[data-admin-stat="clients"]', countByRole("client"));
        setText('[data-admin-stat="pending_accounts"]', countByStatus("inactive"));
        setText('[data-admin-stat="suspended_accounts"]', countByStatus("suspended"));
        setText('[data-admin-stat="projects"]', state.projects.length);
        setText('[data-admin-stat="tasks"]', state.tasks.length);
    }

    function renderDashboardPending() {
        const body = qs("[data-admin-dashboard-pending]");

        if (!body) {
            return;
        }

        const items = state.pendingAccounts.slice(0, 6);

        if (items.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="4">Không có tài khoản nào đang chờ duyệt.</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = items.map(function (account) {
            return `
                <tr>
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.manager_name || "Tự đăng ký / Chưa có")}</td>
                    <td>
                        <span class="badge ${statusBadgeClass(account.status)}">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                </tr>
            `;
        }).join("");
    }

    function renderAccountStats() {
        setText('[data-admin-account-stat="total"]', state.accounts.length);
        setText('[data-admin-account-stat="active"]', countByStatus("active"));
        setText('[data-admin-account-stat="inactive"]', countByStatus("inactive"));
        setText('[data-admin-account-stat="suspended"]', countByStatus("suspended"));
    }

    function actionButtons(account) {
        const id = Number(account.id || 0);
        const status = String(account.status || "").toLowerCase();
        const role = String(account.role || "").toLowerCase();

        if (!id) {
            return "";
        }

        const buttons = [];

        if (status === "inactive") {
            buttons.push(`
                <button class="btn btn-emerald btn-sm" type="button" data-admin-approve="${id}">
                    Duyệt
                </button>
            `);

            buttons.push(`
                <button class="btn btn-light btn-sm" type="button" data-admin-reject="${id}">
                    Từ chối
                </button>
            `);
        }

        if (status === "active") {
            buttons.push(`
                <button class="btn btn-light btn-sm" type="button" data-admin-suspend="${id}">
                    Khóa/đóng băng
                </button>
            `);
        }

        if (status === "suspended") {
            buttons.push(`
                <button class="btn btn-emerald btn-sm" type="button" data-admin-activate="${id}">
                    Mở khóa
                </button>
            `);
        }

        if (role === "admin" && status === "active") {
            return `<span style="color:var(--text-muted);font-weight:700;">Admin core</span>`;
        }

        return `
            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                ${buttons.join("") || '<span style="color:var(--text-muted);">Không có thao tác</span>'}
            </div>
        `;
    }

    function renderPendingAccountsTable() {
        const body = qs("[data-admin-pending-body]");

        if (!body) {
            return;
        }

        if (state.pendingAccounts.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="6">Không có tài khoản nào đang chờ duyệt.</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = state.pendingAccounts.map(function (account) {
            return `
                <tr>
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.manager_name || "Tự đăng ký / Chưa có")}</td>
                    <td>${escapeHtml(formatDate(account.created_at))}</td>
                    <td>
                        <span class="badge ${statusBadgeClass(account.status)}">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        ${actionButtons(account)}
                    </td>
                </tr>
            `;
        }).join("");
    }

    function renderAccountsTable() {
        const body = qs("[data-admin-accounts-body]");

        if (!body) {
            return;
        }

        if (state.accounts.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="7">Không có tài khoản phù hợp.</td>
                </tr>
            `;
            return;
        }

        body.innerHTML = state.accounts.map(function (account) {
            return `
                <tr>
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.department_name || "Chưa cập nhật")}</td>
                    <td>${escapeHtml(account.position_name || "Chưa cập nhật")}</td>
                    <td>${escapeHtml(account.manager_name || "Chưa có")}</td>
                    <td>
                        <span class="badge ${statusBadgeClass(account.status)}">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        ${actionButtons(account)}
                    </td>
                </tr>
            `;
        }).join("");
    }

    async function performAccountAction(id, action) {
        const map = {
            approve: {
                path: `/api/admin/accounts/${id}/approve`,
                confirm: "Duyệt tài khoản này?",
                success: "Đã duyệt tài khoản."
            },
            reject: {
                path: `/api/admin/accounts/${id}/reject`,
                confirm: "Từ chối tài khoản này? Tài khoản sẽ bị khóa.",
                success: "Đã từ chối tài khoản."
            },
            suspend: {
                path: `/api/admin/accounts/${id}/suspend`,
                confirm: "Khóa/đóng băng tài khoản này?",
                success: "Đã khóa/đóng băng tài khoản."
            },
            activate: {
                path: `/api/admin/accounts/${id}/activate`,
                confirm: "Mở khóa tài khoản này?",
                success: "Đã mở khóa tài khoản."
            }
        };

        const config = map[action];

        if (!config) {
            return;
        }

        const ok = window.confirm(config.confirm);

        if (!ok) {
            return;
        }

        try {
            await apiRequest(config.path, {
                method: "PATCH"
            });

            toast("success", "Thành công", config.success);
            await reloadAccountsPage();
        } catch (error) {
            toast("error", "Không thể xử lý", error.message);
        }
    }

    function currentAccountFilters() {
        return {
            search: String(qs("[data-admin-account-search]")?.value || "").trim(),
            role: String(qs("[data-admin-account-role]")?.value || "").trim(),
            status: String(qs("[data-admin-account-status]")?.value || "").trim()
        };
    }

    async function reloadAccountsPage() {
        await loadAccounts(currentAccountFilters());
        await loadPendingAccounts();

        renderAccountStats();
        renderPendingAccountsTable();
        renderAccountsTable();
    }

    function bindAccountActions() {
        document.addEventListener("click", function (event) {
            const approve = event.target.closest("[data-admin-approve]");
            const reject = event.target.closest("[data-admin-reject]");
            const suspend = event.target.closest("[data-admin-suspend]");
            const activate = event.target.closest("[data-admin-activate]");

            if (approve) {
                performAccountAction(approve.getAttribute("data-admin-approve"), "approve");
            }

            if (reject) {
                performAccountAction(reject.getAttribute("data-admin-reject"), "reject");
            }

            if (suspend) {
                performAccountAction(suspend.getAttribute("data-admin-suspend"), "suspend");
            }

            if (activate) {
                performAccountAction(activate.getAttribute("data-admin-activate"), "activate");
            }
        });

        const refreshButton = qs("[data-admin-accounts-refresh]");

        if (refreshButton) {
            refreshButton.addEventListener("click", function () {
                reloadAccountsPage();
            });
        }

        const applyButton = qs("[data-admin-account-apply-filter]");

        if (applyButton) {
            applyButton.addEventListener("click", function () {
                reloadAccountsPage();
            });
        }

        const searchInput = qs("[data-admin-account-search]");

        if (searchInput) {
            searchInput.addEventListener("input", function () {
                window.clearTimeout(searchInput._adminTimer);
                searchInput._adminTimer = window.setTimeout(function () {
                    reloadAccountsPage();
                }, 280);
            });
        }

        qsa("[data-admin-account-role], [data-admin-account-status]").forEach(function (select) {
            select.addEventListener("change", function () {
                reloadAccountsPage();
            });
        });
    }

    async function initDashboard() {
        const root = qs("[data-admin-dashboard]");

        if (!root) {
            return;
        }

        await loadCurrentUser();
        await Promise.all([
            loadAccounts(),
            loadPendingAccounts(),
            loadProjects(),
            loadTasks()
        ]);

        renderDashboardStats();
        renderDashboardPending();
    }

    async function initAccounts() {
        const root = qs("[data-admin-accounts]");

        if (!root) {
            return;
        }

        await loadCurrentUser();
        bindAccountActions();
        await reloadAccountsPage();
    }

    async function init() {
        try {
            await initDashboard();
            await initAccounts();
        } catch (error) {
            toast("error", "Không thể tải trang Admin", error.message);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();