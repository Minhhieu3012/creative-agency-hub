(function () {
    "use strict";

    const state = {
        employees: [],
        departments: [],
        positions: [],
        pagination: null,
        filters: {
            search: "",
            department_id: "",
            status: ""
        }
    };

    function escapeHtml(value) {
        return window.CAHApp?.escapeHtml
            ? CAHApp.escapeHtml(value)
            : String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
    }

    function normalize(value) {
        return String(value || "").toLowerCase().trim();
    }

    function getPage() {
        return document.querySelector("[data-hrm-page]")?.dataset.hrmPage || "";
    }

    function getCurrentUser() {
        return window.CAHAuth?.getUser?.() || {};
    }

    function getInitials(name) {
        const raw = String(name || "CA").trim();
        const words = raw.split(/\s+/).filter(Boolean);

        if (words.length >= 2) {
            return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase();
        }

        return raw.slice(0, 2).toUpperCase();
    }

    function statusLabel(status) {
        const map = {
            active: "Đang làm việc",
            inactive: "Tạm nghỉ",
            resigned: "Đã nghỉ",
            suspended: "Tạm khóa"
        };

        return map[String(status || "").toLowerCase()] || "Không rõ";
    }

    function statusBadge(status) {
        const value = String(status || "").toLowerCase();

        if (value === "active") return "success";
        if (value === "inactive") return "warning";
        if (value === "suspended") return "danger";
        if (value === "resigned") return "info";

        return "primary";
    }

    function roleBadge(role) {
        const value = String(role || "").toLowerCase();

        if (value === "admin") return "danger";
        if (value === "manager") return "primary";
        if (value === "client") return "info";

        return "success";
    }

    function genderLabel(gender) {
        const value = String(gender || "").toLowerCase();

        if (value === "male") return "Nam";
        if (value === "female") return "Nữ";
        if (value === "other") return "Khác";

        return "—";
    }

    function avatarUrl(filename) {
        if (!filename) return "";

        if (/^https?:\/\//i.test(filename) || filename.startsWith("/")) {
            return filename;
        }

        return `${window.CAHApp?.baseUrl || "/creative-agency-hub"}/public/uploads/avatars/${filename}`;
    }

    function renderEmptyRow(colspan, title, description) {
        return `
            <tr>
                <td colspan="${colspan}">
                    <div class="ui-empty-state" style="min-height: 180px;">
                        <div class="ui-empty-icon">◌</div>
                        <div class="ui-empty-content">
                            <h3>${escapeHtml(title)}</h3>
                            <p>${escapeHtml(description)}</p>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    async function apiGet(path, options = {}) {
        if (!window.CAHApi) {
            throw new Error("CAHApi chưa sẵn sàng.");
        }

        return CAHApi.get(path, options);
    }

    async function apiPost(path, data, options = {}) {
        if (!window.CAHApi) {
            throw new Error("CAHApi chưa sẵn sàng.");
        }

        return CAHApi.post(path, data, options);
    }

    async function apiPut(path, data, options = {}) {
        if (!window.CAHApi) {
            throw new Error("CAHApi chưa sẵn sàng.");
        }

        return CAHApi.put(path, data, options);
    }

    async function apiDelete(path, options = {}) {
        if (!window.CAHApi) {
            throw new Error("CAHApi chưa sẵn sàng.");
        }

        return CAHApi.delete(path, options);
    }

    function buildQuery(params) {
        const query = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && String(value).trim() !== "") {
                query.set(key, value);
            }
        });

        const string = query.toString();
        return string ? `?${string}` : "";
    }

    async function loadDepartments() {
        const response = await apiGet("/api/departments", {
            loading: false
        });

        state.departments = Array.isArray(response.data) ? response.data : [];
        return state.departments;
    }

    async function loadPositions() {
        const response = await apiGet("/api/positions", {
            loading: false
        });

        state.positions = Array.isArray(response.data) ? response.data : [];
        return state.positions;
    }

    async function loadEmployees() {
        const query = buildQuery({
            search: state.filters.search,
            department_id: state.filters.department_id,
            status: state.filters.status,
            limit: 100,
            page: 1
        });

        const response = await apiGet(`/api/employees${query}`, {
            loading: true,
            loadingMessage: "Đang tải danh sách nhân sự..."
        });

        state.employees = Array.isArray(response.data) ? response.data : [];
        state.pagination = response.pagination || null;

        return state.employees;
    }

    function renderDepartmentOptions() {
        const selects = document.querySelectorAll("[data-hrm-department-filter], [data-employee-department-select]");

        selects.forEach((select) => {
            const current = select.value;
            const isFilter = select.matches("[data-hrm-department-filter]");

            select.innerHTML = isFilter
                ? '<option value="">Tất cả phòng ban</option>'
                : '<option value="">-- Chọn phòng ban --</option>';

            state.departments.forEach((department) => {
                select.insertAdjacentHTML(
                    "beforeend",
                    `<option value="${escapeHtml(department.id)}">${escapeHtml(department.name)}</option>`
                );
            });

            if (current) {
                select.value = current;
            }
        });
    }

    function renderPositionOptions() {
        const selects = document.querySelectorAll("[data-employee-position-select]");

        selects.forEach((select) => {
            const current = select.value;

            select.innerHTML = '<option value="">-- Chọn chức danh --</option>';

            state.positions.forEach((position) => {
                select.insertAdjacentHTML(
                    "beforeend",
                    `<option value="${escapeHtml(position.id)}">${escapeHtml(position.name)}</option>`
                );
            });

            if (current) {
                select.value = current;
            }
        });
    }

    function renderEmployeeTable() {
        const tbody = document.querySelector("[data-employee-table-body]");
        if (!tbody) return;

        if (!state.employees.length) {
            tbody.innerHTML = renderEmptyRow(
                7,
                "Chưa có nhân sự",
                "Không tìm thấy nhân sự phù hợp với bộ lọc hiện tại."
            );
            renderEmployeeStats();
            return;
        }

        tbody.innerHTML = state.employees.map((employee) => {
            const avatar = employee.avatar ? avatarUrl(employee.avatar) : "";
            const initials = getInitials(employee.full_name);

            return `
                <tr
                    data-status="${escapeHtml(employee.status)}"
                    data-department-id="${escapeHtml(employee.department_id)}"
                    data-employee-id="${escapeHtml(employee.id)}"
                >
                    <td>
                        <div class="employee-cell">
                            <div class="employee-avatar">
                                ${avatar
                                    ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(employee.full_name)}">`
                                    : escapeHtml(initials)
                                }
                            </div>

                            <div class="employee-name">
                                <strong>${escapeHtml(employee.full_name)}</strong>
                                <small>${escapeHtml(employee.email)}</small>
                                <small>${escapeHtml(employee.employee_code || "")}</small>
                            </div>
                        </div>
                    </td>

                    <td>${escapeHtml(employee.department_name || "—")}</td>

                    <td>
                        <strong class="text-primary">${escapeHtml(employee.position_name || "—")}</strong>
                    </td>

                    <td>
                        <span class="badge badge-${roleBadge(employee.role)}">
                            ${escapeHtml(employee.role || "employee")}
                        </span>
                    </td>

                    <td>
                        <span class="badge badge-${statusBadge(employee.status)}">
                            ${escapeHtml(statusLabel(employee.status))}
                        </span>
                    </td>

                    <td>${escapeHtml(employee.hire_date || "—")}</td>

                    <td>
                        <button class="icon-btn" type="button" data-hrm-action="open-edit-employee" data-employee-id="${escapeHtml(employee.id)}" title="Chỉnh sửa">✎</button>
                        <button class="icon-btn" type="button" data-hrm-action="view-employee" data-employee-id="${escapeHtml(employee.id)}" title="Xem chi tiết">👁</button>
                        <button class="icon-btn text-danger" type="button" data-hrm-action="delete-employee" data-employee-id="${escapeHtml(employee.id)}" title="Xóa">×</button>
                    </td>
                </tr>
            `;
        }).join("");

        renderEmployeeStats();
    }

    function renderEmployeeStats() {
        const total = state.employees.length;
        const active = state.employees.filter((employee) => employee.status === "active").length;
        const manager = state.employees.filter((employee) => employee.role === "manager").length;
        const inactive = state.employees.filter((employee) => employee.status !== "active").length;

        const map = {
            total,
            active,
            manager,
            inactive
        };

        Object.entries(map).forEach(([key, value]) => {
            const element = document.querySelector(`[data-employee-stat="${key}"]`);
            if (element) element.textContent = value;
        });
    }

    function renderOrgTree() {
        const tree = document.querySelector("[data-department-tree]");
        if (!tree) return;

        if (!state.departments.length) {
            tree.innerHTML = `
                <div class="ui-empty-state" style="min-height: 220px;">
                    <div class="ui-empty-icon">▤</div>
                    <div class="ui-empty-content">
                        <h3>Chưa có phòng ban</h3>
                        <p>Tạo phòng ban đầu tiên để bắt đầu quản lý cơ cấu tổ chức.</p>
                    </div>
                </div>
            `;
            return;
        }

        tree.innerHTML = state.departments.map((department, index) => {
            const count = state.employees.filter((employee) => String(employee.department_id) === String(department.id)).length;

            return `
                <div class="org-node ${index === 0 ? "is-parent" : ""}" data-department-id="${escapeHtml(department.id)}">
                    <div class="org-node-icon">${index === 0 ? "▣" : "▤"}</div>

                    <div class="org-node-text">
                        <strong>${escapeHtml(department.name)}</strong>
                        <small>${count} thành viên • ${escapeHtml(department.status || "active")}</small>
                    </div>

                    <button class="icon-btn" type="button" data-hrm-action="delete-department" data-department-id="${escapeHtml(department.id)}">⋮</button>
                </div>
            `;
        }).join("");
    }

    function renderPositions() {
        const list = document.querySelector("[data-position-list]");
        if (!list) return;

        if (!state.positions.length) {
            list.innerHTML = `
                <div class="ui-empty-state" style="min-height: 180px;">
                    <div class="ui-empty-icon">✦</div>
                    <div class="ui-empty-content">
                        <h3>Chưa có chức danh</h3>
                        <p>Tạo chức danh để gán cho nhân sự.</p>
                    </div>
                </div>
            `;
            return;
        }

        list.innerHTML = state.positions.map((position) => {
            const count = state.employees.filter((employee) => String(employee.position_id) === String(position.id)).length;

            return `
                <div class="role-card" data-position-id="${escapeHtml(position.id)}">
                    <div class="role-card-head">
                        <h3>${escapeHtml(position.name)}</h3>
                        <span class="badge badge-primary">${count} nhân sự</span>
                    </div>

                    <p>${escapeHtml(position.description || "Chưa có mô tả chức danh.")}</p>

                    <div class="role-permission-row">
                        <span class="badge badge-info">${escapeHtml(position.status || "active")}</span>
                        <button class="btn btn-light btn-sm" type="button" data-hrm-action="delete-position" data-position-id="${escapeHtml(position.id)}">Xóa</button>
                    </div>
                </div>
            `;
        }).join("");
    }

    function renderOrgPreview() {
        const tbody = document.querySelector("[data-org-employee-preview]");
        if (!tbody) return;

        const preview = state.employees.slice(0, 6);

        if (!preview.length) {
            tbody.innerHTML = renderEmptyRow(5, "Chưa có nhân sự", "Danh sách preview đang trống.");
            return;
        }

        tbody.innerHTML = preview.map((employee) => `
            <tr>
                <td>
                    <div class="employee-cell">
                        <div class="employee-avatar">${escapeHtml(getInitials(employee.full_name))}</div>
                        <div class="employee-name">
                            <strong>${escapeHtml(employee.full_name)}</strong>
                            <small>${escapeHtml(employee.email)}</small>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(employee.department_name || "—")}</td>
                <td>${escapeHtml(employee.position_name || "—")}</td>
                <td>
                    <span class="badge badge-${statusBadge(employee.status)}">
                        ${escapeHtml(statusLabel(employee.status))}
                    </span>
                </td>
                <td>
                    <a class="text-primary" href="/creative-agency-hub/app/View/hrm/employees.php">Xem</a>
                </td>
            </tr>
        `).join("");
    }

    function renderOrgStats() {
        const employees = document.querySelector('[data-org-stat="employees"]');
        const departments = document.querySelector('[data-org-stat="departments"]');
        const positions = document.querySelector('[data-org-stat="positions"]');

        if (employees) employees.textContent = state.employees.length;
        if (departments) departments.textContent = state.departments.length;
        if (positions) positions.textContent = state.positions.length;
    }

    async function loadEmployeesPage() {
        await Promise.all([loadDepartments(), loadPositions()]);
        renderDepartmentOptions();
        renderPositionOptions();
        await loadEmployees();
        renderEmployeeTable();
    }

    async function loadDepartmentsPage() {
        await Promise.all([loadDepartments(), loadPositions(), loadEmployees()]);
        renderOrgTree();
        renderPositions();
        renderOrgPreview();
        renderOrgStats();
    }

    function findEmployee(employeeId) {
        return state.employees.find((employee) => String(employee.id) === String(employeeId));
    }

    function employeeForm(mode, employee) {
        const isEdit = mode === "edit";

        return `
            <form class="hrm-modal-form" data-hrm-employee-form data-form-mode="${escapeHtml(mode)}" data-employee-id="${escapeHtml(employee?.id || "")}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mã nhân viên</label>
                        <input class="form-control" name="employee_code" value="${escapeHtml(employee?.employee_code || "")}" placeholder="EMP-0001" ${isEdit ? "readonly" : "required"}>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Họ tên</label>
                        <input class="form-control" name="full_name" value="${escapeHtml(employee?.full_name || "")}" placeholder="Nguyễn Văn A" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="${escapeHtml(employee?.email || "")}" placeholder="name@agency.vn" ${isEdit ? "readonly" : "required"}>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input class="form-control" name="phone" value="${escapeHtml(employee?.phone || "")}" placeholder="0900000000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phòng ban</label>
                        <select class="form-select" name="department_id" data-employee-department-select required>
                            <option value="">-- Chọn phòng ban --</option>
                            ${state.departments.map((department) => `
                                <option value="${escapeHtml(department.id)}" ${String(employee?.department_id || "") === String(department.id) ? "selected" : ""}>
                                    ${escapeHtml(department.name)}
                                </option>
                            `).join("")}
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Chức danh</label>
                        <select class="form-select" name="position_id" data-employee-position-select required>
                            <option value="">-- Chọn chức danh --</option>
                            ${state.positions.map((position) => `
                                <option value="${escapeHtml(position.id)}" ${String(employee?.position_id || "") === String(position.id) ? "selected" : ""}>
                                    ${escapeHtml(position.name)}
                                </option>
                            `).join("")}
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="employee" ${employee?.role === "employee" ? "selected" : ""}>Employee</option>
                            <option value="manager" ${employee?.role === "manager" ? "selected" : ""}>Manager</option>
                            <option value="admin" ${employee?.role === "admin" ? "selected" : ""}>Admin</option>
                            <option value="client" ${employee?.role === "client" ? "selected" : ""}>Client</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="active" ${employee?.status === "active" ? "selected" : ""}>Đang làm việc</option>
                            <option value="inactive" ${employee?.status === "inactive" ? "selected" : ""}>Tạm nghỉ</option>
                            <option value="suspended" ${employee?.status === "suspended" ? "selected" : ""}>Tạm khóa</option>
                            <option value="resigned" ${employee?.status === "resigned" ? "selected" : ""}>Đã nghỉ</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Giới tính</label>
                        <select class="form-select" name="gender">
                            <option value="other" ${employee?.gender === "other" ? "selected" : ""}>Khác</option>
                            <option value="male" ${employee?.gender === "male" ? "selected" : ""}>Nam</option>
                            <option value="female" ${employee?.gender === "female" ? "selected" : ""}>Nữ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ngày sinh</label>
                        <input class="form-control" type="date" name="date_of_birth" value="${escapeHtml(employee?.date_of_birth || "")}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ngày vào làm</label>
                        <input class="form-control" type="date" name="hire_date" value="${escapeHtml(employee?.hire_date || new Date().toISOString().slice(0, 10))}" ${isEdit ? "" : "required"}>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mật khẩu mặc định</label>
                        <input class="form-control" type="text" name="password" value="" placeholder="${isEdit ? "Không đổi mật khẩu" : "Mặc định 123456"}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ</label>
                    <textarea class="form-textarea" name="address" placeholder="Địa chỉ liên hệ">${escapeHtml(employee?.address || "")}</textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">${isEdit ? "Lưu thay đổi" : "Tạo nhân viên"}</button>
                </div>
            </form>
        `;
    }

    function departmentForm() {
        return `
            <form class="hrm-modal-form" data-hrm-department-form>
                <div class="form-group">
                    <label class="form-label">Tên phòng ban</label>
                    <input class="form-control" name="name" placeholder="Phòng Kỹ thuật" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-textarea" name="description" placeholder="Mô tả chức năng phòng ban"></textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Tạo phòng ban</button>
                </div>
            </form>
        `;
    }

    function positionForm() {
        return `
            <form class="hrm-modal-form" data-hrm-position-form>
                <div class="form-group">
                    <label class="form-label">Tên chức danh</label>
                    <input class="form-control" name="name" placeholder="Project Manager" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-textarea" name="description" placeholder="Mô tả quyền hạn hoặc nhiệm vụ"></textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Tạo chức danh</button>
                </div>
            </form>
        `;
    }

    function profileEditForm(employee) {
        return `
            <form class="hrm-modal-form" data-hrm-profile-form data-employee-id="${escapeHtml(employee?.id || "")}">
                <div class="form-group">
                    <label class="form-label">Họ tên</label>
                    <input class="form-control" name="full_name" value="${escapeHtml(employee?.full_name || "")}" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input class="form-control" name="phone" value="${escapeHtml(employee?.phone || "")}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giới tính</label>
                        <select class="form-select" name="gender">
                            <option value="other" ${employee?.gender === "other" ? "selected" : ""}>Khác</option>
                            <option value="male" ${employee?.gender === "male" ? "selected" : ""}>Nam</option>
                            <option value="female" ${employee?.gender === "female" ? "selected" : ""}>Nữ</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Ngày sinh</label>
                    <input class="form-control" type="date" name="date_of_birth" value="${escapeHtml(employee?.date_of_birth || "")}">
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ</label>
                    <textarea class="form-textarea" name="address">${escapeHtml(employee?.address || "")}</textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Lưu hồ sơ</button>
                </div>
            </form>
        `;
    }

    function avatarForm(employee) {
        return `
            <form class="hrm-modal-form" data-hrm-avatar-form data-employee-id="${escapeHtml(employee?.id || "")}">
                <div class="form-group">
                    <label class="form-label">Ảnh đại diện</label>
                    <input class="form-control" type="file" name="avatar" accept=".jpg,.jpeg,.png,.gif" required>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Upload avatar</button>
                </div>
            </form>
        `;
    }

    function openModal(title, subtitle, body) {
        if (!window.CAHModal) {
            if (window.CAHToast) {
                CAHToast.error("Thiếu modal", "modal.js chưa sẵn sàng.");
            }
            return;
        }

        CAHModal.open({ title, subtitle, body });
    }

    function openCreateEmployee() {
        openModal(
            "Thêm nhân viên",
            "Tạo tài khoản nhân sự mới trong hệ thống HRM.",
            employeeForm("create", null)
        );
    }

    function openEditEmployee(employeeId) {
        const employee = findEmployee(employeeId);

        if (!employee) {
            if (window.CAHToast) {
                CAHToast.error("Không tìm thấy nhân sự", "Vui lòng tải lại danh sách.");
            }
            return;
        }

        openModal(
            "Chỉnh sửa nhân viên",
            "Cập nhật thông tin nhân sự cơ bản.",
            employeeForm("edit", employee)
        );
    }

    function openViewEmployee(employeeId) {
        const employee = findEmployee(employeeId);

        if (!employee) return;

        openModal(
            "Chi tiết nhân sự",
            employee.full_name || "Thông tin nhân sự",
            `
                <div class="info-grid">
                    <div class="info-item"><small>Mã nhân viên</small><strong>${escapeHtml(employee.employee_code || "—")}</strong></div>
                    <div class="info-item"><small>Email</small><strong>${escapeHtml(employee.email || "—")}</strong></div>
                    <div class="info-item"><small>Phòng ban</small><strong>${escapeHtml(employee.department_name || "—")}</strong></div>
                    <div class="info-item"><small>Chức danh</small><strong>${escapeHtml(employee.position_name || "—")}</strong></div>
                    <div class="info-item"><small>Role</small><strong>${escapeHtml(employee.role || "—")}</strong></div>
                    <div class="info-item"><small>Trạng thái</small><strong>${escapeHtml(statusLabel(employee.status))}</strong></div>
                    <div class="info-item"><small>Số điện thoại</small><strong>${escapeHtml(employee.phone || "—")}</strong></div>
                    <div class="info-item"><small>Ngày vào làm</small><strong>${escapeHtml(employee.hire_date || "—")}</strong></div>
                    <div class="info-item" style="grid-column: 1 / -1;"><small>Địa chỉ</small><strong>${escapeHtml(employee.address || "—")}</strong></div>
                </div>
                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="button" data-hrm-action="open-edit-employee" data-employee-id="${escapeHtml(employee.id)}">Chỉnh sửa</button>
                </div>
            `
        );
    }

    async function createEmployee(form) {
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : Object.fromEntries(new FormData(form));

        await apiPost("/api/employees", data, {
            loading: true,
            loadingMessage: "Đang tạo nhân viên..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã tạo nhân viên", "Nhân sự mới đã được lưu vào database.");
        }

        window.CAHModal?.close();
        await loadEmployeesPage();
    }

    async function updateEmployee(form) {
        const employeeId = form.dataset.employeeId;
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : Object.fromEntries(new FormData(form));

        await apiPut(`/api/employees/${employeeId}`, data, {
            loading: true,
            loadingMessage: "Đang cập nhật nhân viên..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã cập nhật", "Thông tin nhân sự đã được lưu.");
        }

        window.CAHModal?.close();

        if (getPage() === "profile") {
            await loadProfilePage();
        } else {
            await loadEmployeesPage();
        }
    }

    async function deleteEmployee(employeeId) {
        const employee = findEmployee(employeeId);
        const confirmed = window.confirm(`Xóa mềm nhân sự "${employee?.full_name || employeeId}"?`);

        if (!confirmed) return;

        await apiDelete(`/api/employees/${employeeId}`, {
            loading: true,
            loadingMessage: "Đang xóa nhân sự..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã xóa nhân sự", "Nhân sự đã được chuyển sang trạng thái deleted.");
        }

        await loadEmployeesPage();
    }

    async function createDepartment(form) {
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : Object.fromEntries(new FormData(form));

        await apiPost("/api/departments", data, {
            loading: true,
            loadingMessage: "Đang tạo phòng ban..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã tạo phòng ban", "Phòng ban mới đã được lưu.");
        }

        window.CAHModal?.close();
        await loadDepartmentsPage();
    }

    async function createPosition(form) {
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : Object.fromEntries(new FormData(form));

        await apiPost("/api/positions", data, {
            loading: true,
            loadingMessage: "Đang tạo chức danh..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã tạo chức danh", "Chức danh mới đã được lưu.");
        }

        window.CAHModal?.close();
        await loadDepartmentsPage();
    }

    async function deleteDepartment(departmentId) {
        const confirmed = window.confirm("Xóa mềm phòng ban này? Hệ thống sẽ chặn nếu vẫn còn nhân sự active.");

        if (!confirmed) return;

        await apiDelete(`/api/departments/${departmentId}`, {
            loading: true,
            loadingMessage: "Đang xóa phòng ban..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã xóa phòng ban", "Cơ cấu tổ chức đã được cập nhật.");
        }

        await loadDepartmentsPage();
    }

    async function deletePosition(positionId) {
        const confirmed = window.confirm("Xóa mềm chức danh này? Hệ thống sẽ chặn nếu còn nhân sự đang giữ chức danh.");

        if (!confirmed) return;

        await apiDelete(`/api/positions/${positionId}`, {
            loading: true,
            loadingMessage: "Đang xóa chức danh..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã xóa chức danh", "Danh sách chức danh đã được cập nhật.");
        }

        await loadDepartmentsPage();
    }

    async function loadProfilePage() {
        const currentUser = getCurrentUser();
        const userId = currentUser?.id || currentUser?.employee_id;

        if (!userId) {
            if (window.CAHToast) {
                CAHToast.error("Chưa có user", "Không tìm thấy thông tin người dùng đăng nhập.");
            }
            return;
        }

        const response = await apiGet(`/api/employees/${userId}`, {
            loading: true,
            loadingMessage: "Đang tải hồ sơ cá nhân..."
        });

        const employee = response.data || {};
        state.employees = [employee];

        renderProfile(employee);
    }

    function renderProfile(employee) {
        const name = employee.full_name || "Creative Agency";
        const avatar = employee.avatar ? avatarUrl(employee.avatar) : "";
        const remainingLeave = Number(employee.remaining_leave_days || 0);
        const totalLeave = Number(employee.total_leave_days || 12);
        const leavePercent = totalLeave > 0 ? Math.round((remainingLeave / totalLeave) * 100) : 0;

        const avatarBox = document.querySelector("[data-profile-avatar]");
        if (avatarBox) {
            avatarBox.innerHTML = avatar
                ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(name)}">`
                : `<span>${escapeHtml(getInitials(name))}</span>`;
        }

        const mappings = {
            "[data-profile-name]": name,
            "[data-profile-position]": `${employee.position_name || "Chưa có chức danh"} • ${employee.department_name || "Chưa có phòng ban"}`,
            "[data-profile-role]": employee.role || "employee",
            "[data-profile-status]": statusLabel(employee.status),
            "[data-profile-code]": employee.employee_code || "—",
            "[data-profile-email]": employee.email || "—",
            "[data-profile-phone]": employee.phone || "—",
            "[data-profile-gender]": genderLabel(employee.gender),
            "[data-profile-dob]": employee.date_of_birth || "—",
            "[data-profile-department]": employee.department_name || "—",
            "[data-profile-hire-date]": employee.hire_date || "—",
            "[data-profile-address]": employee.address || "—",
            "[data-profile-remaining-leave]": `${remainingLeave} ngày`,
            "[data-profile-total-leave]": `${totalLeave} ngày`,
            "[data-profile-status-note]": statusLabel(employee.status)
        };

        Object.entries(mappings).forEach(([selector, value]) => {
            const element = document.querySelector(selector);
            if (element) element.textContent = value;
        });

        const leaveProgress = document.querySelector("[data-profile-leave-progress]");
        if (leaveProgress) {
            leaveProgress.style.width = `${Math.max(0, Math.min(100, leavePercent))}%`;
        }

        const statusProgress = document.querySelector("[data-profile-status-progress]");
        if (statusProgress) {
            statusProgress.style.width = employee.status === "active" ? "100%" : "45%";
        }
    }

    function openEditProfile() {
        const employee = state.employees[0];

        if (!employee?.id) {
            if (window.CAHToast) {
                CAHToast.error("Chưa có hồ sơ", "Vui lòng tải lại hồ sơ trước khi chỉnh sửa.");
            }
            return;
        }

        openModal(
            "Cập nhật hồ sơ cá nhân",
            "Chỉnh sửa các thông tin cơ bản được phép cập nhật.",
            profileEditForm(employee)
        );
    }

    function openAvatarUpload() {
        const employee = state.employees[0];

        if (!employee?.id) {
            if (window.CAHToast) {
                CAHToast.error("Chưa có hồ sơ", "Vui lòng tải lại hồ sơ trước khi upload avatar.");
            }
            return;
        }

        openModal(
            "Upload avatar",
            "Chỉ chấp nhận JPG, PNG hoặc GIF.",
            avatarForm(employee)
        );
    }

    async function uploadAvatar(form) {
        const employeeId = form.dataset.employeeId;
        const input = form.querySelector("[name='avatar']");

        if (!input?.files?.length) {
            if (window.CAHToast) {
                CAHToast.error("Chưa chọn file", "Vui lòng chọn ảnh đại diện.");
            }
            return;
        }

        const formData = new FormData();
        formData.append("avatar", input.files[0]);

        await CAHApi.request(`/api/employees/${employeeId}/avatar`, {
            method: "POST",
            formData,
            loading: true,
            loadingMessage: "Đang upload avatar..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã upload avatar", "Ảnh đại diện đã được cập nhật.");
        }

        window.CAHModal?.close();
        await loadProfilePage();
    }

    function bindEvents() {
        let searchTimer = null;

        document.addEventListener("input", function (event) {
            const input = event.target.closest("[data-hrm-employee-search]");
            if (!input) return;

            clearTimeout(searchTimer);

            searchTimer = window.setTimeout(async () => {
                state.filters.search = input.value.trim();
                await loadEmployeesPage();
            }, 350);
        });

        document.addEventListener("change", async function (event) {
            const departmentFilter = event.target.closest("[data-hrm-department-filter]");
            const statusFilter = event.target.closest("[data-hrm-status-filter]");

            if (departmentFilter) {
                state.filters.department_id = departmentFilter.value;
                await loadEmployeesPage();
            }

            if (statusFilter) {
                state.filters.status = statusFilter.value;
                await loadEmployeesPage();
            }
        });

        document.addEventListener("click", function (event) {
            const button = event.target.closest("[data-hrm-action]");
            if (!button) return;

            const action = button.dataset.hrmAction;

            if (action === "refresh-employees") {
                loadEmployeesPage();
                return;
            }

            if (action === "refresh-organization") {
                loadDepartmentsPage();
                return;
            }

            if (action === "refresh-profile") {
                loadProfilePage();
                return;
            }

            if (action === "open-create-employee") {
                openCreateEmployee();
                return;
            }

            if (action === "open-edit-employee") {
                openEditEmployee(button.dataset.employeeId);
                return;
            }

            if (action === "view-employee") {
                openViewEmployee(button.dataset.employeeId);
                return;
            }

            if (action === "delete-employee") {
                deleteEmployee(button.dataset.employeeId).catch(showError);
                return;
            }

            if (action === "open-create-department") {
                openModal("Thêm phòng ban", "Tạo phòng ban mới trong cơ cấu tổ chức.", departmentForm());
                return;
            }

            if (action === "open-create-position") {
                openModal("Thêm chức danh", "Tạo position mới để gán cho nhân sự.", positionForm());
                return;
            }

            if (action === "delete-department") {
                deleteDepartment(button.dataset.departmentId).catch(showError);
                return;
            }

            if (action === "delete-position") {
                deletePosition(button.dataset.positionId).catch(showError);
                return;
            }

            if (action === "open-edit-profile") {
                openEditProfile();
                return;
            }

            if (action === "open-avatar-upload") {
                openAvatarUpload();
                return;
            }

            if (action === "upload-doc" && window.CAHToast) {
                CAHToast.info("Upload hồ sơ", "Khu vực tài liệu cá nhân sẽ được nối ở scope document sau.");
            }
        });

        document.addEventListener("submit", function (event) {
            const employeeFormEl = event.target.closest("[data-hrm-employee-form]");
            const departmentFormEl = event.target.closest("[data-hrm-department-form]");
            const positionFormEl = event.target.closest("[data-hrm-position-form]");
            const profileFormEl = event.target.closest("[data-hrm-profile-form]");
            const avatarFormEl = event.target.closest("[data-hrm-avatar-form]");

            if (employeeFormEl) {
                event.preventDefault();

                const mode = employeeFormEl.dataset.formMode;
                const handler = mode === "edit" ? updateEmployee : createEmployee;

                handler(employeeFormEl).catch(showError);
                return;
            }

            if (departmentFormEl) {
                event.preventDefault();
                createDepartment(departmentFormEl).catch(showError);
                return;
            }

            if (positionFormEl) {
                event.preventDefault();
                createPosition(positionFormEl).catch(showError);
                return;
            }

            if (profileFormEl) {
                event.preventDefault();
                updateEmployee(profileFormEl).catch(showError);
                return;
            }

            if (avatarFormEl) {
                event.preventDefault();
                uploadAvatar(avatarFormEl).catch(showError);
            }
        });
    }

    function showError(error) {
        if (window.CAHToast) {
            CAHToast.error("Không thể xử lý HRM", error.message || "API HRM chưa phản hồi.");
        }
    }

    async function init() {
        bindEvents();

        const page = getPage();

        try {
            if (page === "employees") {
                await loadEmployeesPage();
            }

            if (page === "departments") {
                await loadDepartmentsPage();
            }

            if (page === "profile") {
                await loadProfilePage();
            }
        } catch (error) {
            showError(error);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    window.CAHHRM = {
        reloadEmployees: loadEmployeesPage,
        reloadOrganization: loadDepartmentsPage,
        reloadProfile: loadProfilePage
    };
})();