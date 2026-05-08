(function () {
    "use strict";

    function qs(selector, scope = document) {
        return scope.querySelector(selector);
    }

    function formToJson(form) {
        const formData = new FormData(form);
        const data = {};

        formData.forEach(function (value, key) {
            data[key] = String(value || "").trim();
        });

        return data;
    }

    function showAlert(type, message) {
        const alert = qs("[data-landing-alert]");

        if (!alert) {
            if (type === "error") {
                console.error(message);
            } else {
                console.log(message);
            }

            return;
        }

        alert.style.display = "block";
        alert.classList.remove("is-success", "is-error");
        alert.classList.add(type === "success" ? "is-success" : "is-error");
        alert.textContent = message;

        alert.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }

    async function submitManagerRegister(form) {
        const apiUrl = form.getAttribute("data-api-url");
        const submitButton = form.querySelector("button[type='submit']");
        const originalText = submitButton ? submitButton.textContent : "";

        if (!apiUrl) {
            showAlert("error", "Thiếu API đăng ký Manager.");
            return;
        }

        const payload = formToJson(form);

        if (payload.password !== payload.password_confirm) {
            showAlert("error", "Mật khẩu xác nhận không khớp.");
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Đang gửi đăng ký...";
        }

        try {
            const response = await fetch(apiUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            });

            const text = await response.text();

            let result;

            try {
                result = JSON.parse(text);
            } catch (error) {
                console.error("Register API không trả JSON:", text);
                throw new Error("Server không trả JSON hợp lệ.");
            }

            if (!response.ok || result.status === "error") {
                throw new Error(result.message || "Không thể đăng ký tài khoản.");
            }

            form.reset();

            showAlert(
                "success",
                result.message || "Đăng ký thành công. Tài khoản Manager đang chờ Admin duyệt."
            );
        } catch (error) {
            showAlert("error", error.message || "Có lỗi xảy ra khi đăng ký.");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    }

    function init() {
        const form = qs("[data-manager-register-form]");

        if (!form) {
            return;
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();
            submitManagerRegister(form);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();