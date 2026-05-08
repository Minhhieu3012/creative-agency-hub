(function () {
    "use strict";

    const CONFIG = {
        baseUrl: window.CAH_CONFIG?.baseUrl || "/creative-agency-hub",
        apiRoot: window.CAH_CONFIG?.apiRoot || "/creative-agency-hub/public"
    };

    const App = {
        baseUrl: CONFIG.baseUrl,
        apiRoot: CONFIG.apiRoot,

        qs(selector, scope = document) {
            return scope.querySelector(selector);
        },

        qsa(selector, scope = document) {
            return Array.from(scope.querySelectorAll(selector));
        },

        escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        formToObject(form) {
            const data = {};
            const formData = new FormData(form);

            formData.forEach((value, key) => {
                if (value instanceof File) {
                    if (value.name) {
                        data[key] = value;
                    }
                    return;
                }

                data[key] = typeof value === "string" ? value.trim() : value;
            });

            return data;
        },

        buildApiUrl(path) {
            if (!path) return CONFIG.apiRoot;

            if (/^https?:\/\//i.test(path)) {
                return path;
            }

            if (path.startsWith(CONFIG.apiRoot)) {
                return path;
            }

            if (path.startsWith("/api/")) {
                return `${CONFIG.apiRoot}${path}`;
            }

            if (path.startsWith("api/")) {
                return `${CONFIG.apiRoot}/${path}`;
            }

            return `${CONFIG.apiRoot}/${path.replace(/^\/+/, "")}`;
        },

        buildViewUrl(path) {
            return `${CONFIG.baseUrl}/app/View/${String(path || "").replace(/^\/+/, "")}`;
        },

        setLoading(isLoading, message) {
            let overlay = document.querySelector("[data-ui-loading-overlay]");

            if (!overlay) {
                overlay = document.createElement("div");
                overlay.className = "ui-loading-overlay";
                overlay.setAttribute("data-ui-loading-overlay", "");
                overlay.innerHTML = `
                    <div class="ui-loading-card">
                        <div class="ui-spinner"></div>
                        <strong data-ui-loading-title>Đang xử lý...</strong>
                        <p data-ui-loading-message>Vui lòng chờ trong giây lát.</p>
                    </div>
                `;
                document.body.appendChild(overlay);
            }

            const messageEl = overlay.querySelector("[data-ui-loading-message]");

            if (messageEl && message) {
                messageEl.textContent = message;
            }

            overlay.classList.toggle("is-active", Boolean(isLoading));
        },

        fakeDelay(callback, delay = 650) {
            App.setLoading(true, "Đang cập nhật giao diện demo...");

            window.setTimeout(function () {
                App.setLoading(false);

                if (typeof callback === "function") {
                    callback();
                }
            }, delay);
        },

        formatRole(role) {
            const normalizedRole = String(role || "user").trim().toLowerCase();

            const labels = {
                admin: "ADMIN",
                manager: "MANAGER",
                employee: "EMPLOYEE",
                client: "CLIENT",
                user: "USER"
            };

            return labels[normalizedRole] || normalizedRole.toUpperCase();
        },

        getInitial(name) {
            const cleanName = String(name || "U").trim();
            return (cleanName.charAt(0) || "U").toUpperCase();
        },

        normalizeAvatarUrl(avatar) {
            const value = String(avatar || "").trim();

            if (!value) {
                return "";
            }

            if (/^https?:\/\//i.test(value) || value.startsWith("/")) {
                return value;
            }

            if (value.startsWith("public/")) {
                return `${CONFIG.baseUrl}/${value}`;
            }

            if (value.startsWith("uploads/")) {
                return `${CONFIG.baseUrl}/public/${value}`;
            }

            return `${CONFIG.baseUrl}/public/uploads/avatars/${value}`;
        },

        applyUserToTopbar(user) {
            if (!user || typeof user !== "object") return;

            const name = user.full_name || user.name || user.email || "Người dùng";
            const role = user.role || "user";
            const avatarUrl = App.normalizeAvatarUrl(user.avatar || user.avatar_url || "");
            const initial = App.getInitial(name);

            document.querySelectorAll("[data-user-name]").forEach((element) => {
                element.textContent = name;
            });

            document.querySelectorAll("[data-user-role]").forEach((element) => {
                element.textContent = App.formatRole(role);
            });

            document.querySelectorAll("[data-user-avatar]").forEach((element) => {
                if (avatarUrl) {
                    if (element.tagName.toLowerCase() === "img") {
                        element.src = avatarUrl;
                        element.alt = name;
                        return;
                    }

                    const image = document.createElement("img");
                    image.src = avatarUrl;
                    image.alt = name;
                    image.className = element.className || "user-avatar";
                    image.setAttribute("data-user-avatar", "");
                    element.replaceWith(image);
                    return;
                }

                if (element.tagName.toLowerCase() === "img") {
                    element.alt = name;
                    return;
                }

                element.textContent = initial;
            });
        },

        async initTopbarUser() {
            const hasTopbarUser = document.querySelector("[data-user-name], [data-user-role], [data-user-avatar]");

            if (!hasTopbarUser) return;

            const serverUser = window.CAH_CURRENT_USER || null;

            if (serverUser) {
                Auth.setUser(serverUser);
                App.applyUserToTopbar(serverUser);
            }

            const cachedUser = Auth.getUser();

            if (!serverUser && cachedUser) {
                App.applyUserToTopbar(cachedUser);
            }

            if (!Auth.getToken()) return;

            try {
                const payload = await Api.get("/api/auth/me", { auth: true });
                const freshUser = payload?.data?.user || payload?.user || null;

                if (freshUser) {
                    Auth.setUser(freshUser);
                    App.applyUserToTopbar(freshUser);
                }
            } catch (error) {
                const nameElement = document.querySelector("[data-user-name]");
                const roleElement = document.querySelector("[data-user-role]");

                if (nameElement && nameElement.textContent.trim().toLowerCase() === "loading...") {
                    nameElement.textContent = "Người dùng";
                }

                if (roleElement && !roleElement.textContent.trim()) {
                    roleElement.textContent = "USER";
                }
            }
        },

        isRemovedPayrollKeyword(keyword) {
            const value = String(keyword || "").toLowerCase();

            return (
                value.includes("lương") ||
                value.includes("luong") ||
                value.includes("salary") ||
                value.includes("payroll") ||
                value.includes("bảng lương") ||
                value.includes("bang luong")
            );
        },

        resolveSearchTarget(keyword) {
            const value = String(keyword || "").toLowerCase();

            if (App.isRemovedPayrollKeyword(value)) {
                return null;
            }

            if (
                value.includes("nhân sự") ||
                value.includes("nhan su") ||
                value.includes("nhân viên") ||
                value.includes("nhan vien") ||
                value.includes("employee") ||
                value.includes("staff")
            ) {
                return App.buildViewUrl("hrm/employees.php");
            }

            if (
                value.includes("dự án") ||
                value.includes("du an") ||
                value.includes("project")
            ) {
                return App.buildViewUrl("tasks/projects.php");
            }

            if (
                value.includes("công việc") ||
                value.includes("cong viec") ||
                value.includes("task") ||
                value.includes("kanban")
            ) {
                return App.buildViewUrl("tasks/kanban.php");
            }

            if (
                value.includes("gantt") ||
                value.includes("timeline") ||
                value.includes("tiến độ") ||
                value.includes("tien do")
            ) {
                return App.buildViewUrl("tasks/gantt.php");
            }

            if (
                value.includes("nghỉ phép") ||
                value.includes("nghi phep") ||
                value.includes("leave")
            ) {
                return App.buildViewUrl("payroll/manager_approvals.php");
            }

            if (
                value.includes("chấm công") ||
                value.includes("cham cong") ||
                value.includes("attendance") ||
                value.includes("checkin") ||
                value.includes("check-in")
            ) {
                return App.buildViewUrl("payroll/attendance.php");
            }

            return window.location.pathname;
        },

        initTopbarSearch() {
            document.querySelectorAll("[data-topbar-search]").forEach((form) => {
                if (form.dataset.searchReady) return;
                form.dataset.searchReady = "true";

                const input = form.querySelector('input[name="q"]');

                form.addEventListener("submit", function (event) {
                    event.preventDefault();

                    const keyword = input ? input.value.trim() : "";

                    if (!keyword) {
                        input?.focus();

                        if (window.CAHToast) {
                            CAHToast.info("Tìm kiếm", "Nhập từ khóa rồi bấm biểu tượng kính lúp hoặc nhấn Enter.");
                        }

                        return;
                    }

                    if (App.isRemovedPayrollKeyword(keyword)) {
                        if (window.CAHToast) {
                            CAHToast.info(
                                "Module đã được loại bỏ",
                                "Phần tính lương đã được xoá khỏi dự án. Chấm công và nghỉ phép vẫn được giữ lại."
                            );
                        }

                        input.value = "";
                        input.focus();
                        return;
                    }

                    const target = App.resolveSearchTarget(keyword);

                    if (!target) {
                        if (window.CAHToast) {
                            CAHToast.info("Không tìm thấy trang phù hợp", "Thử tìm theo nhân sự, dự án, task, chấm công hoặc nghỉ phép.");
                        }

                        return;
                    }

                    const separator = target.includes("?") ? "&" : "?";
                    window.location.href = `${target}${separator}search=${encodeURIComponent(keyword)}`;
                });
            });
        },

        initRefreshPage() {
            document.querySelectorAll("[data-refresh-page]").forEach((button) => {
                if (button.dataset.refreshReady) return;
                button.dataset.refreshReady = "true";

                button.addEventListener("click", function (event) {
                    event.preventDefault();
                    button.classList.add("is-spinning");
                    window.location.reload();
                });
            });
        },

        initSidebarToggle() {
            const shell = document.querySelector(".app-shell");
            const sidebar = document.querySelector(".app-sidebar");
            const toggleButtons = document.querySelectorAll("[data-sidebar-toggle]");

            if (!shell || !sidebar || !toggleButtons.length) return;

            let backdrop = document.querySelector("[data-sidebar-backdrop]");

            if (!backdrop) {
                backdrop = document.createElement("div");
                backdrop.className = "sidebar-backdrop";
                backdrop.setAttribute("data-sidebar-backdrop", "");
                document.body.appendChild(backdrop);
            }

            function isMobile() {
                return window.matchMedia("(max-width: 920px)").matches;
            }

            function closeMobileSidebar() {
                sidebar.classList.remove("is-open");
                backdrop.classList.remove("is-open");
                document.body.classList.remove("is-sidebar-open");
            }

            function openMobileSidebar() {
                sidebar.classList.add("is-open");
                backdrop.classList.add("is-open");
                document.body.classList.add("is-sidebar-open");
            }

            function toggleDesktopSidebar() {
                shell.classList.toggle("is-sidebar-collapsed");

                try {
                    localStorage.setItem(
                        "cah_sidebar_collapsed",
                        shell.classList.contains("is-sidebar-collapsed") ? "1" : "0"
                    );
                } catch (error) {
                    // LocalStorage có thể bị chặn ở một số trình duyệt. Bỏ qua để UI không gãy.
                }
            }

            try {
                const savedState = localStorage.getItem("cah_sidebar_collapsed");

                if (!isMobile() && savedState === "1") {
                    shell.classList.add("is-sidebar-collapsed");
                }
            } catch (error) {
                // Không cần xử lý.
            }

            toggleButtons.forEach((button) => {
                if (button.dataset.sidebarReady) return;
                button.dataset.sidebarReady = "true";

                button.addEventListener("click", function (event) {
                    event.preventDefault();
                    event.stopImmediatePropagation();

                    if (isMobile()) {
                        if (sidebar.classList.contains("is-open")) {
                            closeMobileSidebar();
                        } else {
                            openMobileSidebar();
                        }

                        return;
                    }

                    closeMobileSidebar();
                    toggleDesktopSidebar();
                }, true);
            });

            document.querySelectorAll("[data-sidebar-close]").forEach((button) => {
                if (button.dataset.sidebarCloseReady) return;
                button.dataset.sidebarCloseReady = "true";

                button.addEventListener("click", function (event) {
                    event.preventDefault();
                    closeMobileSidebar();
                });
            });

            backdrop.addEventListener("click", closeMobileSidebar);

            document.addEventListener("keydown", function (event) {
                if (event.key === "Escape") {
                    closeMobileSidebar();
                }
            });

            window.addEventListener("resize", function () {
                if (isMobile()) {
                    shell.classList.remove("is-sidebar-collapsed");
                    return;
                }

                closeMobileSidebar();

                try {
                    const savedState = localStorage.getItem("cah_sidebar_collapsed");
                    shell.classList.toggle("is-sidebar-collapsed", savedState === "1");
                } catch (error) {
                    // Không cần xử lý.
                }
            });
        },

        initLogout() {
            document.querySelectorAll("[data-logout]").forEach((button) => {
                if (button.dataset.logoutReady) return;
                button.dataset.logoutReady = "true";

                button.addEventListener("click", function (event) {
                    event.preventDefault();
                    Auth.clearToken();
                    window.location.href = App.buildApiUrl("/auth/logout");
                });
            });
        },

        initExternalLinks() {
            document.querySelectorAll('a[href="#"], button[data-disabled-demo]').forEach((element) => {
                if (element.dataset.externalLinkReady) return;
                element.dataset.externalLinkReady = "true";

                element.addEventListener("click", function (event) {
                    if (
                        element.matches("[data-logout]") ||
                        element.matches("[data-refresh-page]") ||
                        element.matches("[data-sidebar-toggle]")
                    ) {
                        return;
                    }

                    event.preventDefault();

                    if (window.CAHToast) {
                        CAHToast.info("Tính năng demo", "Chức năng này sẽ được nối backend ở phase tiếp theo.");
                    }
                });
            });
        },

        initScrollHints() {
            document.querySelectorAll(".table-responsive, .gantt-table-wrap, .kanban-board").forEach((element) => {
                if (element.dataset.scrollHintReady) return;

                element.dataset.scrollHintReady = "true";

                const hint = document.createElement("div");
                hint.className = "ui-mobile-scroll-hint";
                hint.textContent = "Vuốt ngang để xem thêm nội dung";

                element.parentNode?.insertBefore(hint, element.nextSibling);
            });
        },

        initProgressBars() {
            document.querySelectorAll("[data-progress]").forEach((bar) => {
                const value = Number(bar.dataset.progress || 0);
                bar.style.width = `${Math.max(0, Math.min(100, value))}%`;
            });
        },

        initPageEntrance() {
            document.body.classList.add("is-ready");
        },

        init() {
            App.initSidebarToggle();
            App.initTopbarSearch();
            App.initRefreshPage();
            App.initLogout();
            App.initExternalLinks();
            App.initScrollHints();
            App.initProgressBars();
            App.initPageEntrance();
            App.initTopbarUser();
        }
    };

    const Auth = {
        tokenKey: "cah_auth_token",
        userKey: "cah_auth_user",

        getToken() {
            return localStorage.getItem(Auth.tokenKey) || localStorage.getItem("cah_token") || "";
        },

        setToken(token) {
            if (!token) return;
            localStorage.setItem(Auth.tokenKey, token);
            localStorage.setItem("cah_token", token);
        },

        clearToken() {
            localStorage.removeItem(Auth.tokenKey);
            localStorage.removeItem(Auth.userKey);
            localStorage.removeItem("cah_token");
            localStorage.removeItem("cah_user");
        },

        setUser(user) {
            if (!user) return;
            localStorage.setItem(Auth.userKey, JSON.stringify(user));
            localStorage.setItem("cah_user", JSON.stringify(user));
        },

        getUser() {
            try {
                return JSON.parse(localStorage.getItem(Auth.userKey) || localStorage.getItem("cah_user") || "null");
            } catch (error) {
                return null;
            }
        },

        isLoggedIn() {
            return Boolean(Auth.getToken());
        }
    };

    const Api = {
        async request(path, options = {}) {
            const {
                method = "GET",
                data = null,
                formData = null,
                auth = true,
                headers = {},
                loading = false,
                loadingMessage = "Đang kết nối API..."
            } = options;

            const requestHeaders = {
                Accept: "application/json",
                ...headers
            };

            const fetchOptions = {
                method,
                headers: requestHeaders
            };

            if (auth) {
                const token = Auth.getToken();

                if (token) {
                    requestHeaders.Authorization = `Bearer ${token}`;
                }
            }

            if (formData) {
                fetchOptions.body = formData;
            } else if (data !== null && data !== undefined) {
                requestHeaders["Content-Type"] = "application/json";
                fetchOptions.body = JSON.stringify(data);
            }

            if (loading) {
                App.setLoading(true, loadingMessage);
            }

            try {
                const response = await fetch(App.buildApiUrl(path), fetchOptions);
                const contentType = response.headers.get("content-type") || "";
                const payload = contentType.includes("application/json")
                    ? await response.json()
                    : { status: response.ok ? "success" : "error", message: await response.text() };

                if (!response.ok || payload.status === "error") {
                    const error = new Error(payload.message || "Yêu cầu không thành công.");
                    error.status = response.status;
                    error.payload = payload;
                    throw error;
                }

                return payload;
            } catch (error) {
                if (error.status === 401) {
                    Auth.clearToken();

                    if (window.CAHToast) {
                        CAHToast.error("Phiên đăng nhập hết hạn", "Vui lòng đăng nhập lại để tiếp tục.");
                    }
                } else if (window.CAHToast) {
                    CAHToast.error("Không thể xử lý", error.message || "Có lỗi xảy ra khi gọi API.");
                }

                throw error;
            } finally {
                if (loading) {
                    App.setLoading(false);
                }
            }
        },

        get(path, options = {}) {
            return Api.request(path, { ...options, method: "GET" });
        },

        post(path, data, options = {}) {
            return Api.request(path, { ...options, method: "POST", data });
        },

        put(path, data, options = {}) {
            return Api.request(path, { ...options, method: "PUT", data });
        },

        patch(path, data, options = {}) {
            return Api.request(path, { ...options, method: "PATCH", data });
        },

        delete(path, options = {}) {
            return Api.request(path, { ...options, method: "DELETE" });
        }
    };

    window.CAHApp = App;
    window.CAHAuth = Auth;
    window.CAHApi = Api;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", App.init);
    } else {
        App.init();
    }
})();