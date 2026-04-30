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

    function validateRequired(form) {
        const requiredFields = form.querySelectorAll("[required]");
        let valid = true;

        requiredFields.forEach((field) => {
            const wrapper = field.closest(".form-group") || field.parentElement;

            wrapper?.classList.remove("has-error");

            if (!field.value.trim()) {
                valid = false;
                wrapper?.classList.add("has-error");
            }
        });

        return valid;
    }

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-ui-form]");
        if (!form) return;

        if (!validateRequired(form)) {
            event.preventDefault();

            if (window.CAHToast) {
                CAHToast.error("Thiếu thông tin", "Vui lòng nhập đầy đủ các trường bắt buộc.");
            }

            return;
        }

        if (form.dataset.mockSubmit === "true") {
            event.preventDefault();
            setLoading(form, true);

            window.setTimeout(() => {
                setLoading(form, false);

                if (window.CAHToast) {
                    CAHToast.success("Thành công", form.dataset.successMessage || "Thao tác đã được ghi nhận.");
                }

                const redirect = form.dataset.redirect;
                if (redirect) {
                    window.setTimeout(() => {
                        window.location.href = redirect;
                    }, 650);
                }
            }, 720);
        }
    });

    document.addEventListener("click", function (event) {
        const toggle = event.target.closest("[data-password-toggle]");
        if (!toggle) return;

        const targetSelector = toggle.dataset.passwordToggle;
        const input = document.querySelector(targetSelector);

        if (!input) return;

        input.type = input.type === "password" ? "text" : "password";
        toggle.textContent = input.type === "password" ? "👁" : "🙈";
    });
})();