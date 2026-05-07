(function () {
    "use strict";

    const API_BASE = "/creative-agency-hub/public";

    function normalize(value) {
        return String(value || "").toLowerCase().trim();
    }

    function getToken() {
        return localStorage.getItem("cah_token") || "";
    }

    function showSuccess(title, message) {
        if (window.CAHToast) {
            CAHToast.success(title, message);
        }
    }

    function showError(title, message) {
        if (window.CAHToast) {
            CAHToast.error(title, message);
        } else {
            alert(message || title);
        }
    }

    function setFormLoading(form, isLoading) {
        const button = form.querySelector("[type='submit']");
        if (!button) return;

        if (isLoading) {
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = "Đang xử lý...";
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.originalText || button.innerHTML;
            button.disabled = false;
        }
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
            "Accept": "application/json",
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

    function getFileNameFromDisposition(disposition) {
        if (!disposition) return null;

        const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8Match && utf8Match[1]) {
            return decodeURIComponent(utf8Match[1]);
        }

        const asciiMatch = disposition.match(/filename="([^"]+)"/i);
        if (asciiMatch && asciiMatch[1]) {
            return asciiMatch[1];
        }

        return null;
    }

    async function downloadDocument(documentId) {
        const headers = {};
        const token = getToken();

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`${API_BASE}/api/employee-documents/${documentId}/download`, {
            method: "GET",
            headers,
            credentials: "same-origin"
        });

        if (!response.ok) {
            const result = await parseResponse(response);
            throw new Error(result.message || "Không thể tải tài liệu.");
        }

        const blob = await response.blob();
        const disposition = response.headers.get("content-disposition");
        const fileName = getFileNameFromDisposition(disposition) || `employee-document-${documentId}`;

        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");

        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();

        link.remove();
        URL.revokeObjectURL(url);
    }

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

    document.addEventListener("click", async function (event) {
        const documentDownload = event.target.closest("[data-document-download]");
        if (documentDownload) {
            event.preventDefault();

            try {
                await downloadDocument(documentDownload.dataset.documentDownload);
            } catch (error) {
                showError("Không thể tải xuống", error.message);
            }

            return;
        }

        const documentDelete = event.target.closest("[data-document-delete]");
        if (documentDelete) {
            event.preventDefault();

            if (!confirm("Bạn chắc chắn muốn xóa tài liệu này?")) {
                return;
            }

            try {
                await apiRequest(`/api/employee-documents/${documentDelete.dataset.documentDelete}`, {
                    method: "DELETE"
                });

                showSuccess("Đã xóa", "Tài liệu hồ sơ đã được xóa.");
                window.setTimeout(() => window.location.reload(), 650);
            } catch (error) {
                showError("Không thể xóa", error.message);
            }

            return;
        }

        const button = event.target.closest("[data-hrm-action]");
        if (!button) return;

        const action = button.dataset.hrmAction;

        if (action === "edit-profile" || action === "mock-save") {
            openTemplateModal(
                "#profile-edit-template",
                "Chỉnh sửa hồ sơ",
                "Cập nhật thông tin cá nhân. Email, vai trò, phòng ban và chức vụ được quản lý bởi hệ thống."
            );
            return;
        }

        if (action === "upload-avatar") {
            openTemplateModal(
                "#profile-avatar-template",
                "Tải ảnh đại diện",
                "Ảnh được kiểm tra định dạng thật ở backend trước khi lưu."
            );
            return;
        }

        if (action === "upload-document" || action === "upload-doc") {
            openTemplateModal(
                "#profile-document-template",
                "Tải hồ sơ điện tử",
                "Tài liệu được kiểm tra định dạng thật ở backend trước khi lưu."
            );
        }
    });

    document.addEventListener("submit", async function (event) {
        const form = event.target.closest("[data-profile-edit-form]");
        if (!form) return;

        event.preventDefault();
        setFormLoading(form, true);

        try {
            const employeeId = form.dataset.employeeId;
            const formData = new FormData(form);

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

            await apiRequest(`/api/employees/${employeeId}`, {
                method: "PUT",
                data: payload
            });

            showSuccess("Đã cập nhật", "Hồ sơ cá nhân đã được lưu vào cơ sở dữ liệu.");

            if (window.CAHModal) {
                CAHModal.close();
            }

            window.setTimeout(() => {
                window.location.reload();
            }, 650);
        } catch (error) {
            showError("Không thể cập nhật", error.message);
        } finally {
            setFormLoading(form, false);
        }
    });

    document.addEventListener("submit", async function (event) {
        const form = event.target.closest("[data-profile-avatar-form]");
        if (!form) return;

        event.preventDefault();
        setFormLoading(form, true);

        try {
            const employeeId = form.dataset.employeeId;
            const fileInput = form.querySelector("input[type='file'][name='avatar']");

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

            await apiRequest(`/api/employees/${employeeId}/avatar`, {
                method: "POST",
                formData: payload
            });

            showSuccess("Upload thành công", "Ảnh đại diện đã được cập nhật.");

            if (window.CAHModal) {
                CAHModal.close();
            }

            window.setTimeout(() => {
                window.location.reload();
            }, 650);
        } catch (error) {
            showError("Không thể upload", error.message);
        } finally {
            setFormLoading(form, false);
        }
    });

    document.addEventListener("submit", async function (event) {
        const form = event.target.closest("[data-profile-document-form]");
        if (!form) return;

        event.preventDefault();
        setFormLoading(form, true);

        try {
            const employeeId = form.dataset.employeeId;
            const fileInput = form.querySelector("input[type='file'][name='document']");

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                throw new Error("Vui lòng chọn tài liệu hồ sơ.");
            }

            const file = fileInput.files[0];
            const allowedTypes = [
                "application/pdf",
                "application/msword",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            const maxSize = 10 * 1024 * 1024;

            if (!allowedTypes.includes(file.type)) {
                throw new Error("Chỉ cho phép PDF, DOC, DOCX, JPG, PNG hoặc WEBP.");
            }

            if (file.size > maxSize) {
                throw new Error("Tài liệu tối đa 10MB.");
            }

            const payload = new FormData(form);

            await apiRequest(`/api/employees/${employeeId}/documents`, {
                method: "POST",
                formData: payload
            });

            showSuccess("Upload thành công", "Hồ sơ điện tử đã được lưu.");

            if (window.CAHModal) {
                CAHModal.close();
            }

            window.setTimeout(() => {
                window.location.reload();
            }, 650);
        } catch (error) {
            showError("Không thể upload", error.message);
        } finally {
            setFormLoading(form, false);
        }
    });
})();