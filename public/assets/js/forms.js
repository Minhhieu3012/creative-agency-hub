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

            const value = field.type === "file" ? field.files?.length : String(field.value || "").trim();

            if (!value) {
                valid = false;
                wrapper?.classList.add("has-error");
            }
        });

        return valid;
    }

    function getFormDataObject(form) {
        if (window.CAHApp && typeof CAHApp.formToObject === "function") {
            return CAHApp.formToObject(form);
        }

        const data = {};
        new FormData(form).forEach((value, key) => {
            data[key] = value;
        });

        return data;
    }

    async function handleAuthLogin(form) {
        const data = getFormDataObject(form);

        setLoading(form, true);

        try {
            const response = await CAHApi.post("/api/auth/login", data, {
                auth: false,
                loading: false
            });

            const token = response?.data?.token;
            const user = response?.data?.user;

            if (!token) {
                throw new Error("API đăng nhập chưa trả về token.");
            }

            CAHAuth.setToken(token);
            CAHAuth.setUser(user);

            if (window.CAHToast) {
                CAHToast.success("Đăng nhập thành công", response.message || "Đang chuyển trang...");
            }

            const redirect = form.dataset.redirect;

            if (redirect) {
                window.setTimeout(() => {
                    window.location.href = redirect;
                }, 520);
            }
        } catch (error) {
            if (window.CAHToast) {
                CAHToast.error("Đăng nhập thất bại", error.message || "Vui lòng kiểm tra lại tài khoản.");
            }
        } finally {
            setLoading(form, false);
        }
    }

    async function handleApiForm(form) {
        const method = form.dataset.method || form.method || "POST";
        const action = form.dataset.action || form.getAttribute("action");
        const successMessage = form.dataset.successMessage || "Thao tác đã được ghi nhận.";
        const redirect = form.dataset.redirect;

        if (!action) {
            throw new Error("Form chưa có action API.");
        }

        const data = getFormDataObject(form);

        setLoading(form, true);

        try {
            const response = await CAHApi.request(action, {
                method: method.toUpperCase(),
                data,
                auth: form.dataset.auth === "false" ? false : true
            });

            if (window.CAHToast) {
                CAHToast.success("Thành công", response.message || successMessage);
            }

            form.reset();

            if (redirect) {
                window.setTimeout(() => {
                    window.location.href = redirect;
                }, 520);
            }
        } finally {
            setLoading(form, false);
        }
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

        if (form.dataset.authLogin === "true") {
            event.preventDefault();
            handleAuthLogin(form);
            return;
        }

        if (form.dataset.apiSubmit === "true") {
            event.preventDefault();
            handleApiForm(form).catch((error) => {
                if (window.CAHToast) {
                    CAHToast.error("Không thể lưu dữ liệu", error.message || "API chưa xử lý được yêu cầu.");
                }
            });
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

    document.addEventListener("click", function (event) {
        const logout = event.target.closest("[data-logout]");
        if (!logout) return;

        event.preventDefault();

        if (window.CAHAuth) {
            CAHAuth.clearToken();
        }

        const redirect = logout.getAttribute("href") || "/creative-agency-hub/app/View/auth/login.php";
        window.location.href = redirect;
    });
})();