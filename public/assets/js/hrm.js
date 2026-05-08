(function () {
    "use strict";

    const API_BASE = window.CAH_CONFIG?.apiRoot || "/creative-agency-hub/public";

    function normalize(value) {
        return String(value || "").toLowerCase().trim();
    }

    function getToken() {
        return localStorage.getItem("cah_auth_token") || localStorage.getItem("cah_token") || "";
    }

    function getStoredUser() {
        const raw =
            localStorage.getItem("cah_auth_user") ||
            localStorage.getItem("cah_user") ||
            "{}";

        try {
            return JSON.parse(raw || "{}");
        } catch (error) {
            return {};
        }
    }

    function saveStoredUser(user) {
        if (!user || typeof user !== "object") return;

        localStorage.setItem("cah_auth_user", JSON.stringify(user));
        localStorage.setItem("cah_user", JSON.stringify(user));
    }

    function updateStoredUser(partialUser) {
        const currentUser = getStoredUser();
        const nextUser = {
            ...currentUser,
            ...(partialUser || {})
        };

        saveStoredUser(nextUser);
    }

    function showSuccess(title, message) {
        if (window.CAHToast) {
            CAHToast.success(title, message);
            return;
        }

        alert(message || title);
    }

    function showError(title, message) {
        if (window.CAHToast) {
            CAHToast.error(title, message);
            return;
        }

        alert(message || title);
    }

    function setFormLoading(form, isLoading) {
        const button = form.querySelector("[type='submit']");
        if (!button) return;

        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = "Đang xử lý...";
            button.disabled = true;
            return;
        }

        button.innerHTML = button.dataset.originalText || button.innerHTML;
        button.disabled = false;
    }

    async function parseResponse(response) {
        const contentType = response.headers.get("content-type") || "";

        if (contentType.includes("application/json")) {
            return response.json();
        }

        const text = await response.text();

        return {
            status: response.ok ? "success" : "error",
            message: text || "Không đọc được phản hồi từ server."
        };
    }

    async function apiRequest(path, options = {}) {
        const headers = {
            Accept: "application/json",
            ...(options.headers || {})
        };

        const token = getToken();

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const fetchOptions = {
            method: options.method || "GET",
            headers,
            credentials: "same-origin"
        };

        if (options.data !== undefined) {
            headers["Content-Type"] = "application/json";
            fetchOptions.body = JSON.stringify(options.data);
        }

        if (options.formData instanceof FormData) {
            delete headers["Content-Type"];
            fetchOptions.body = options.formData;
        }

        const response = await fetch(`${API_BASE}${path}`, fetchOptions);
        const result = await parseResponse(response);

        if (!response.ok || result.status === "error") {
            throw new Error(result.message || "Thao tác thất bại.");
        }

        return result;
    }

    function openTemplateModal(templateId, title, subtitle) {
        const template = document.querySelector(templateId);

        if (!template) {
            showError("Không tìm thấy form", "Thiếu template form trong trang hiện tại.");
            return;
        }

        if (!window.CAHModal) {
            showError("Thiếu modal", "Module modal chưa được tải.");
            return;
        }

        CAHModal.open({
            title,
            subtitle,
            body: template.innerHTML
        });
    }

    function closeModal() {
        if (window.CAHModal) {
            CAHModal.close();
        }
    }

    function reloadSoon(delay = 650) {
        window.setTimeout(() => {
            window.location.reload();
        }, delay);
    }

    function extractEmployeeFromResponse(result) {
        if (!result || typeof result !== "object") {
            return null;
        }

        if (result.data?.employee) {
            return result.data.employee;
        }

        if (result.data && result.data.full_name) {
            return result.data;
        }

        if (result.employee) {
            return result.employee;
        }

        return null;
    }

    function refreshTopbarFromEmployee(employee) {
        if (!employee || typeof employee !== "object") return;

        updateStoredUser({
            id: employee.id,
            full_name: employee.full_name,
            name: employee.full_name,
            email: employee.email,
            role: employee.role,
            avatar: employee.avatar
        });

        if (window.CAHApp && typeof window.CAHApp.applyUserToTopbar === "function") {
            window.CAHApp.applyUserToTopbar({
                id: employee.id,
                full_name: employee.full_name,
                name: employee.full_name,
                email: employee.email,
                role: employee.role,
                avatar: employee.avatar
            });
        }
    }

    function initTableSearch() {
        document.addEventListener("input", function (event) {
            const input = event.target.closest("[data-table-search]");
            if (!input) return;

            const targetSelector = input.dataset.tableSearch;
            const table = document.querySelector(targetSelector);
            if (!table) return;

            const keyword = normalize(input.value);
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach((row) => {
                const text = normalize(row.textContent);
                row.style.display = text.includes(keyword) ? "" : "none";
            });
        });
    }

    function initTableFilter() {
        document.addEventListener("change", function (event) {
            const select = event.target.closest("[data-table-filter]");
            if (!select) return;

            const targetSelector = select.dataset.tableFilter;
            const key = select.dataset.filterKey;
            const table = document.querySelector(targetSelector);

            if (!table || !key) return;

            const value = normalize(select.value);
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach((row) => {
                const rowValue = normalize(row.dataset[key]);
                row.style.display = !value || rowValue === value ? "" : "none";
            });
        });
    }

    function initProfileActions() {
        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-hrm-action]");
            if (!button) return;

            const action = button.dataset.hrmAction;

            if (action === "edit-profile" || action === "mock-save") {
                event.preventDefault();

                openTemplateModal(
                    "#profile-edit-template",
                    "Chỉnh sửa hồ sơ",
                    "Cập nhật thông tin cá nhân. Email, vai trò, phòng ban và chức vụ được quản lý bởi hệ thống."
                );

                return;
            }

            if (action === "upload-avatar") {
                event.preventDefault();

                openTemplateModal(
                    "#profile-avatar-template",
                    "Tải ảnh đại diện",
                    "Chọn ảnh JPG, PNG hoặc WEBP. Dung lượng tối đa 4MB."
                );
            }
        });
    }

    function initProfileEditSubmit() {
        document.addEventListener("submit", async function (event) {
            const form = event.target.closest("[data-profile-edit-form]");
            if (!form) return;

            event.preventDefault();
            setFormLoading(form, true);

            try {
                const employeeId = form.dataset.employeeId;
                const formData = new FormData(form);

                if (!employeeId) {
                    throw new Error("Không xác định được nhân sự cần cập nhật.");
                }

                const payload = {
                    full_name: String(formData.get("full_name") || "").trim(),
                    phone: String(formData.get("phone") || "").trim(),
                    gender: String(formData.get("gender") || "").trim(),
                    date_of_birth: String(formData.get("date_of_birth") || "").trim(),
                    address: String(formData.get("address") || "").trim()
                };

                if (!payload.full_name) {
                    throw new Error("Họ và tên không được để trống.");
                }

                const result = await apiRequest(`/api/employees/${employeeId}`, {
                    method: "PUT",
                    data: payload
                });

                const employee = extractEmployeeFromResponse(result);
                refreshTopbarFromEmployee(employee);

                showSuccess("Đã cập nhật", "Hồ sơ cá nhân đã được lưu vào cơ sở dữ liệu.");
                closeModal();
                reloadSoon();
            } catch (error) {
                showError("Không thể cập nhật", error.message);
            } finally {
                setFormLoading(form, false);
            }
        });
    }

    function initAvatarSubmit() {
        document.addEventListener("submit", async function (event) {
            const form = event.target.closest("[data-profile-avatar-form]");
            if (!form) return;

            event.preventDefault();
            setFormLoading(form, true);

            try {
                const employeeId = form.dataset.employeeId;
                const fileInput = form.querySelector("input[type='file'][name='avatar']");

                if (!employeeId) {
                    throw new Error("Không xác định được nhân sự cần cập nhật ảnh.");
                }

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    throw new Error("Vui lòng chọn ảnh đại diện.");
                }

                const file = fileInput.files[0];
                const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
                const maxSize = 4 * 1024 * 1024;

                if (!allowedTypes.includes(file.type)) {
                    throw new Error("Chỉ cho phép ảnh JPG, PNG hoặc WEBP.");
                }

                if (file.size > maxSize) {
                    throw new Error("Ảnh đại diện tối đa 4MB.");
                }

                const payload = new FormData();
                payload.append("avatar", file);

                const result = await apiRequest(`/api/employees/${employeeId}/avatar`, {
                    method: "POST",
                    formData: payload
                });

                const employee = extractEmployeeFromResponse(result);

                if (employee) {
                    refreshTopbarFromEmployee(employee);
                } else if (result.data?.avatar) {
                    updateStoredUser({
                        avatar: result.data.avatar
                    });
                }

                showSuccess("Upload thành công", "Ảnh đại diện đã được cập nhật.");
                closeModal();
                reloadSoon();
            } catch (error) {
                showError("Không thể upload", error.message);
            } finally {
                setFormLoading(form, false);
            }
        });
    }

    function init() {
        initTableSearch();
        initTableFilter();
        initProfileActions();
        initProfileEditSubmit();
        initAvatarSubmit();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();