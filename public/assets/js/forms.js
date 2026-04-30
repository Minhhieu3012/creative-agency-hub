/**
 * FORMS JS - XỬ LÝ ĐĂNG NHẬP THỰC TẾ (API FETCH)
 */
(function () {
    "use strict";

    function setLoading(form, isLoading) {
        const submitBtn = form.querySelector("[type='submit']");
        if (!submitBtn) return;

        if (isLoading) {
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = "Đang xử lý...";
            submitBtn.disabled = true;
        } else {
            submitBtn.innerHTML = submitBtn.dataset.originalText || submitBtn.innerHTML;
            submitBtn.disabled = false;
        }
    }

    document.addEventListener("submit", async function (event) {
        const form = event.target.closest("[data-ui-form]");
        if (!form) return;

        event.preventDefault(); // Chặn hành vi submit mặc định
        setLoading(form, true);

        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Thực hiện Fetch thật đến API
            const response = await fetch(form.action, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.status === "success") {
                if (window.CAHToast) CAHToast.success("Thành công", result.message || "Đăng nhập thành công!");
                
                // Lưu token nếu cần
                if (result.data?.token) localStorage.setItem('cah_token', result.data.token);

                const redirect = form.dataset.redirect;
                if (redirect) {
                    setTimeout(() => window.location.href = redirect, 1000);
                }
            } else {
                throw new Error(result.message || "Đăng nhập thất bại");
            }

        } catch (error) {
            if (window.CAHToast) CAHToast.error("Lỗi", error.message);
        } finally {
            setLoading(form, false);
        }
    });

    // Xử lý ẩn hiện mật khẩu
    document.addEventListener("click", function (event) {
        const toggle = event.target.closest("[data-password-toggle]");
        if (!toggle) return;
        const target = document.querySelector(toggle.dataset.passwordToggle);
        if (target) {
            target.type = target.type === "password" ? "text" : "password";
            toggle.textContent = target.type === "password" ? "👁" : "🙈";
        }
    });
})();