(function () {
    "use strict";

    function setCookie(name, value, maxAgeSeconds) {
        document.cookie = [
            encodeURIComponent(name) + "=" + encodeURIComponent(value),
            "path=/",
            "max-age=" + String(maxAgeSeconds || 86400),
            "SameSite=Lax"
        ].join("; ");
    }

    function clearAuthStorage() {
        localStorage.removeItem("cah_auth_token");
        localStorage.removeItem("cah_auth_user");
        localStorage.removeItem("cah_token");
        localStorage.removeItem("cah_user");

        document.cookie = "cah_token=; path=/; max-age=0; SameSite=Lax";
    }

    function showMessage(form, type, text) {
        let alert = form.querySelector("[data-auth-message]");

        if (!alert) {
            alert = document.createElement("div");
            alert.setAttribute("data-auth-message", "");
            alert.className = "form-alert";
            form.insertBefore(alert, form.querySelector("button[type='submit']"));
        }

        alert.style.display = "block";
        alert.className = "form-alert " + (type === "success" ? "form-alert-success" : "form-alert-danger");
        alert.textContent = text;
    }

    function hideMessage(form) {
        const alert = form.querySelector("[data-auth-message]");

        if (alert) {
            alert.style.display = "none";
            alert.textContent = "";
        }
    }

    function getFormPayload(form) {
        const formData = new FormData(form);

        return {
            email: String(formData.get("email") || "").trim(),
            password: String(formData.get("password") || "")
        };
    }

    function persistAuth(result) {
        const token = result && result.data && result.data.token ? result.data.token : "";
        const user = result && result.data && result.data.user ? result.data.user : null;

        if (!token || !user) {
            throw new Error("Server không trả đủ token hoặc thông tin tài khoản.");
        }

        localStorage.setItem("cah_auth_token", token);
        localStorage.setItem("cah_token", token);
        localStorage.setItem("cah_auth_user", JSON.stringify(user));
        localStorage.setItem("cah_user", JSON.stringify(user));

        /*
         * Server route /public/staff/dashboard không đọc được localStorage.
         * Vì vậy cần cookie để AuthMiddleware đọc được khi redirect.
         */
        setCookie("cah_token", token, 86400);
    }

    function initAuthPortalForms() {
        document.querySelectorAll("[data-auth-portal-form]").forEach((form) => {
            if (form.dataset.authPortalReady === "true") {
                return;
            }

            form.dataset.authPortalReady = "true";

            form.addEventListener("submit", async function (event) {
                event.preventDefault();

                const endpoint = form.getAttribute("data-auth-endpoint") || form.getAttribute("action");
                const redirect = form.getAttribute("data-auth-redirect") || form.getAttribute("data-redirect") || "/";
                const successMessage = form.getAttribute("data-success-message") || "Đăng nhập thành công.";
                const submitButton = form.querySelector("button[type='submit']");
                const originalText = submitButton ? submitButton.innerHTML : "";

                hideMessage(form);
                clearAuthStorage();

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = "<span>Đang đăng nhập...</span>";
                }

                try {
                    const response = await fetch(endpoint, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json"
                        },
                        credentials: "same-origin",
                        body: JSON.stringify(getFormPayload(form))
                    });

                    const result = await response.json().catch(() => ({
                        status: "error",
                        message: "Server không trả JSON hợp lệ."
                    }));

                    if (!response.ok || result.status !== "success") {
                        throw new Error(result.message || "Đăng nhập thất bại.");
                    }

                    persistAuth(result);
                    showMessage(form, "success", successMessage);

                    window.setTimeout(() => {
                        window.location.href = redirect;
                    }, 350);
                } catch (error) {
                    showMessage(form, "error", error.message || "Đăng nhập thất bại.");
                } finally {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    }
                }
            });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAuthPortalForms);
    } else {
        initAuthPortalForms();
    }
})();