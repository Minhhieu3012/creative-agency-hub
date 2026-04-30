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

        initExternalLinks() {
            document.querySelectorAll('a[href="#"], button[data-disabled-demo]').forEach((element) => {
                element.addEventListener("click", function (event) {
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
            App.initExternalLinks();
            App.initScrollHints();
            App.initProgressBars();
            App.initPageEntrance();
        }
    };

    const Auth = {
        tokenKey: "cah_auth_token",
        userKey: "cah_auth_user",

        getToken() {
            return localStorage.getItem(Auth.tokenKey) || "";
        },

        setToken(token) {
            if (!token) return;
            localStorage.setItem(Auth.tokenKey, token);
        },

        clearToken() {
            localStorage.removeItem(Auth.tokenKey);
            localStorage.removeItem(Auth.userKey);
        },

        setUser(user) {
            if (!user) return;
            localStorage.setItem(Auth.userKey, JSON.stringify(user));
        },

        getUser() {
            try {
                return JSON.parse(localStorage.getItem(Auth.userKey) || "null");
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