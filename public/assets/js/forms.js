/**
 * Forms JS - Creative Agency Hub
 * Auth thật theo bảng employees + redirect riêng theo role.
 */
(function () {
    "use strict";

    function setLoading(form, isLoading) {
        const button = form.querySelector("[type='submit']");

        if (!button) return;

        if (isLoading) {
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }

            button.disabled = true;
            button.innerHTML = "Đang xử lý...";
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }

    function validateRequired(form) {
        let valid = true;

        form.querySelectorAll("[required]").forEach(function (field) {
            const wrapper = field.closest(".form-group") || field.parentElement;
            const value = field.type === "file" ? field.files.length : String(field.value || "").trim();

            wrapper?.classList.remove("has-error");

            if (!value) {
                valid = false;
                wrapper?.classList.add("has-error");
            }
        });

        return valid;
    }

    function formToObject(form) {
        if (window.CAHApp && typeof window.CAHApp.formToObject === "function") {
            return window.CAHApp.formToObject(form);
        }

        const data = {};

        new FormData(form).forEach(function (value, key) {
            data[key] = value instanceof File ? value : String(value || "").trim();
        });

        return data;
    }

    function getBaseUrl() {
        return window.CAH_CONFIG?.baseUrl || window.CAHApp?.baseUrl || "/creative-agency-hub";
    }

    function getApiBaseUrl() {
        return window.CAH_CONFIG?.apiBaseUrl || window.CAHApp?.apiBaseUrl || `${getBaseUrl()}/public`;
    }

    function buildApiUrl(path) {
        if (/^https?:\/\//i.test(path)) return path;

        const cleanPath = String(path || "").startsWith("/") ? path : `/${path}`;

        if (window.CAHApp && typeof window.CAHApp.buildApiUrl === "function") {
            return window.CAHApp.buildApiUrl(cleanPath);
        }

        return `${getApiBaseUrl()}${cleanPath}`;
    }

    async function apiPost(path, data) {
        if (window.CAHApi && typeof window.CAHApi.post === "function") {
            return window.CAHApi.post(path, data, {
                auth: false,
                loading: false
            });
        }

        const response = await fetch(buildApiUrl(path), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify(data)
        });

        const result = await response.json().catch(function () {
            return {};
        });

        if (!response.ok || result.status === "error") {
            throw new Error(result.message || "Không thể xử lý yêu cầu.");
        }

        return result;
    }

    function storeAuth(token, user) {
        localStorage.setItem("cah_auth_token", token);
        localStorage.setItem("cah_token", token);

        localStorage.setItem("cah_auth_user", JSON.stringify(user || {}));
        localStorage.setItem("cah_user", JSON.stringify(user || {}));

        if (window.CAHAuth) {
            if (typeof CAHAuth.setToken === "function") {
                CAHAuth.setToken(token);
            }

            if (typeof CAHAuth.setUser === "function") {
                CAHAuth.setUser(user);
            }
        }
    }

    function getRedirectUrl(form, user) {
        const role = String(user?.role || "").toLowerCase();
        const viewUrl = `${getBaseUrl()}/app/View`;

        const roleKey = `redirect${role.charAt(0).toUpperCase()}${role.slice(1)}`;
        const roleRedirect = form.dataset[roleKey];

        if (roleRedirect) return roleRedirect;
        if (form.dataset.redirect) return form.dataset.redirect;

        /**
         * Chốt tạm routing theo vai trò:
         * - manager: trung tâm điều phối
         * - employee: hồ sơ cá nhân / task được giao sẽ nối tiếp ở scope sau
         * - admin: quản trị nhân sự
         * - client: client portal
         */
        const map = {
            manager: `${viewUrl}/dashboard/index.php`,
            employee: `${viewUrl}/hrm/profile.php`,
            admin: `${viewUrl}/hrm/employees.php`,
            client: `${viewUrl}/client-portal/projects.php`
        };

        return map[role] || `${viewUrl}/dashboard/index.php`;
    }

    async function handleLogin(form) {
        if (!validateRequired(form)) {
            window.CAHToast?.error?.("Thiếu thông tin", "Vui lòng nhập email và mật khẩu.");
            return;
        }

        const payload = formToObject(form);

        setLoading(form, true);

        try {
            const response = await apiPost("/api/auth/login", payload);

            const token = response?.data?.token;
            const user = response?.data?.user;

            if (!token) {
                throw new Error("API chưa trả về token.");
            }

            if (!user || !user.role) {
                throw new Error("API chưa trả về thông tin role.");
            }

            storeAuth(token, user);

            window.CAHToast?.success?.("Đăng nhập thành công", "Đang chuyển trang...");

            window.setTimeout(function () {
                window.location.href = getRedirectUrl(form, user);
            }, 450);
        } catch (error) {
            window.CAHToast?.error?.("Đăng nhập thất bại", error.message || "Vui lòng kiểm tra lại tài khoản.");
        } finally {
            setLoading(form, false);
        }
    }

    async function handleGenericForm(form) {
        if (!validateRequired(form)) {
            window.CAHToast?.error?.("Thiếu thông tin", "Vui lòng kiểm tra lại các trường bắt buộc.");
            return;
        }

        const action = form.dataset.action || form.getAttribute("action");
        const method = String(form.dataset.method || form.method || "POST").toUpperCase();
        const data = formToObject(form);

        if (!action) {
            window.CAHToast?.error?.("Thiếu action", "Form chưa có đường dẫn xử lý.");
            return;
        }

        setLoading(form, true);

        try {
            const response = await fetch(action, {
                method,
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(data)
            });

            const result = await response.json().catch(function () {
                return {};
            });

            if (!response.ok || result.status === "error") {
                throw new Error(result.message || "Không thể xử lý form.");
            }

            window.CAHToast?.success?.("Thành công", result.message || form.dataset.successMessage || "Đã xử lý thành công.");

            if (form.dataset.redirect) {
                window.setTimeout(function () {
                    window.location.href = form.dataset.redirect;
                }, 450);
            }
        } catch (error) {
            window.CAHToast?.error?.("Không thể xử lý", error.message || "Có lỗi xảy ra.");
        } finally {
            setLoading(form, false);
        }
    }

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-ui-form]");

        if (!form) return;

        event.preventDefault();

        if (form.matches("[data-auth-login-form]")) {
            handleLogin(form);
            return;
        }

        handleGenericForm(form);
    });

    document.addEventListener("click", function (event) {
        const toggle = event.target.closest("[data-password-toggle]");

        if (!toggle) return;

        const target = document.querySelector(toggle.dataset.passwordToggle);

        if (!target) return;

        target.type = target.type === "password" ? "text" : "password";
        toggle.textContent = target.type === "password" ? "👁" : "🙈";
    });

    document.addEventListener("click", function (event) {
        const logout = event.target.closest("[data-logout]");

        if (!logout) return;

        event.preventDefault();

        localStorage.removeItem("cah_auth_token");
        localStorage.removeItem("cah_token");
        localStorage.removeItem("cah_auth_user");
        localStorage.removeItem("cah_user");

        if (window.CAHAuth && typeof CAHAuth.clearToken === "function") {
            CAHAuth.clearToken();
        }

        window.location.href = logout.getAttribute("href") || `${getBaseUrl()}/app/View/auth/login.php`;
    });
})();