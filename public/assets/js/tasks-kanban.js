(function () {
    "use strict";

    const board = document.querySelector("[data-kanban-board]");
    if (!board) return;

    const BASE_URL = "/creative-agency-hub";

    const STATUS_TO_COLUMN = {
        "pending approval": "pending",
        "pending": "pending",
        "chờ duyệt": "pending",

        "to do": "todo",
        "todo": "todo",
        "cần làm": "todo",

        "doing": "doing",
        "in_progress": "doing",
        "đang thực hiện": "doing",

        "review": "review",
        "đang kiểm tra": "review",

        "done": "done",
        "completed": "done",
        "hoàn thành": "done"
    };

    const COLUMN_TO_STATUS = {
        pending: "Pending approval",
        todo: "To do",
        doing: "Doing",
        review: "Review",
        done: "Done"
    };

    const STATUS_LABELS = {
        pending: "Chờ duyệt",
        todo: "Cần làm",
        doing: "Đang thực hiện",
        review: "Đang kiểm tra",
        done: "Hoàn thành"
    };

    const PRIORITY_TONE = {
        Low: "info",
        Medium: "primary",
        High: "danger",
        low: "info",
        medium: "primary",
        high: "danger"
    };

    let draggedCard = null;
    let previousDropState = null;
    let latestTasks = [];
    let latestProjects = [];
    let latestEmployees = [];

    injectActionStyles();

    function getToken() {
        return localStorage.getItem("cah_token") || localStorage.getItem("cah_auth_token") || "";
    }

    function escapeHtml(value) {
        return window.CAHApp?.escapeHtml
            ? CAHApp.escapeHtml(value)
            : String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
    }

    function toNumberOrNull(value) {
        if (value === undefined || value === null || value === "") return null;

        const number = Number(value);
        return Number.isFinite(number) && number > 0 ? number : null;
    }

    function getDecodedToken() {
        try {
            const token = getToken();
            if (!token) return null;

            const base64Url = token.split(".")[1];
            if (!base64Url) return null;

            const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
            const paddedBase64 = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, "=");
            const jsonPayload = decodeURIComponent(atob(paddedBase64).split("").map(function (c) {
                return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(""));

            return JSON.parse(jsonPayload);
        } catch (e) {
            return null;
        }
    }

    function getCurrentUser() {
        const tokenData = getDecodedToken();

        if (tokenData) {
            return {
                id: tokenData.id,
                role: tokenData.role,
                email: tokenData.email
            };
        }

        try {
            const storedUser = JSON.parse(localStorage.getItem("cah_user") || localStorage.getItem("cah_auth_user") || "null");
            if (storedUser) return storedUser;
        } catch (error) {
            // Ignore localStorage parse fallback.
        }

        return window.CAHAuth?.getUser?.() || {};
    }

    function getCurrentRole() {
        return String(getCurrentUser()?.role || "").toLowerCase();
    }

    function getCurrentUserId() {
        const user = getCurrentUser();

        return toNumberOrNull(user?.id)
            || toNumberOrNull(user?.employee_id)
            || 1;
    }

    function isManagerLike() {
        const role = getCurrentRole();
        return role === "admin" || role === "manager";
    }

    function isEmployee() {
        return getCurrentRole() === "employee";
    }

    function getFallbackAssigneeId() {
        const user = getCurrentUser();

        if (String(user?.role || "").toLowerCase() === "employee") {
            return getCurrentUserId();
        }

        const firstEmployee = latestEmployees.find((employee) => {
            return String(employee.role || "").toLowerCase() === "employee" && String(employee.status || "").toLowerCase() === "active";
        });

        return toNumberOrNull(firstEmployee?.id) || getCurrentUserId();
    }

    function normalizePriority(priority) {
        const value = String(priority || "Medium").trim().toLowerCase();

        if (value === "low") return "Low";
        if (value === "high") return "High";

        return "Medium";
    }

    function normalizeStatus(status) {
        const key = String(status || "To do").trim().toLowerCase();
        return STATUS_TO_COLUMN[key] ? getStatusByColumn(STATUS_TO_COLUMN[key]) : "To do";
    }

    function getColumnByStatus(status) {
        const key = String(status || "To do").trim().toLowerCase();
        return STATUS_TO_COLUMN[key] || "todo";
    }

    function getStatusByColumn(column) {
        return COLUMN_TO_STATUS[column] || "To do";
    }

    function getProjectNameById(projectId) {
        const project = latestProjects.find((item) => String(item.id) === String(projectId));
        return project?.name || "";
    }

    function getEmployeeNameById(employeeId) {
        const employee = latestEmployees.find((item) => String(item.id) === String(employeeId));
        return employee?.full_name || employee?.email || "";
    }

    function getSelectedProjectId() {
        const urlParams = new URLSearchParams(window.location.search);
        const projectIdFromUrl = toNumberOrNull(urlParams.get("project_id"));

        if (projectIdFromUrl) {
            return projectIdFromUrl;
        }

        const projectFilter = document.querySelector("[data-project-filter]");
        const filterValue = toNumberOrNull(projectFilter?.value);

        if (filterValue) {
            return filterValue;
        }

        const firstProject = latestProjects.find((project) => !project.is_virtual);
        return toNumberOrNull(firstProject?.id);
    }

    function updateColumnCounts() {
        document.querySelectorAll("[data-kanban-column]").forEach((column) => {
            const count = column.querySelectorAll("[data-task-card]").length;
            const countEl = column.querySelector("[data-column-count]");

            if (countEl) {
                countEl.textContent = count;
            }
        });
    }

    function getTaskProgress(status) {
        const column = getColumnByStatus(status);

        return {
            pending: 0,
            todo: 10,
            doing: 55,
            review: 82,
            done: 100
        }[column] || 10;
    }

    function getInitials(task) {
        const value = task.assignee_name || task.assignee || task.assignee_id || task.assigner_id || "CA";
        const str = String(value).trim();

        if (/^\d+$/.test(str)) return `#${str}`;

        const words = str.split(/\s+/).filter(Boolean);

        if (words.length >= 2) {
            return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase();
        }

        return str.slice(0, 2).toUpperCase();
    }

    function normalizeTask(task) {
        const normalizedStatus = normalizeStatus(task?.status);
        const projectId = task?.project_id || "";
        const assigneeId = task?.assignee_id || "";

        return {
            id: task?.id,
            title: task?.title || "Chưa có tiêu đề",
            description: task?.description || "Chưa có mô tả.",
            status: normalizedStatus,
            priority: normalizePriority(task?.priority),
            deadline: task?.deadline || "",
            assignee_id: assigneeId,
            assignee_name: task?.assignee_name || getEmployeeNameById(assigneeId),
            assigner_id: task?.assigner_id || "",
            assigner_name: task?.assigner_name || "",
            watcher_id: task?.watcher_id || "",
            watcher_name: task?.watcher_name || "",
            project_id: projectId,
            project_name: task?.project_name || getProjectNameById(projectId)
        };
    }

    function renderTaskCard(rawTask) {
        const task = normalizeTask(rawTask);
        const columnKey = getColumnByStatus(task.status);
        const priorityTone = PRIORITY_TONE[task.priority] || "primary";
        const progress = getTaskProgress(task.status);
        const deadlineText = task.deadline ? `Deadline: ${escapeHtml(task.deadline)}` : "Chưa có deadline";
        const initials = escapeHtml(getInitials(task));
        const doneClass = columnKey === "done" ? " is-completed" : "";
        const pendingClass = columnKey === "pending" ? " is-pending-approval" : "";
        const projectText = task.project_name || (task.project_id ? `Dự án #${task.project_id}` : "Chưa gán dự án");
        const pendingBadge = columnKey === "pending"
            ? '<span class="badge badge-warning">CHỜ DUYỆT</span>'
            : "";

        return `
            <article
                class="task-card${doneClass}${pendingClass}"
                draggable="true"
                data-task-card
                data-task-id="${escapeHtml(task.id)}"
                data-status="${escapeHtml(columnKey)}"
                data-title="${escapeHtml(task.title)}"
                data-description="${escapeHtml(task.description)}"
                data-project-id="${escapeHtml(task.project_id)}"
                data-assignee-id="${escapeHtml(task.assignee_id)}"
                data-watcher-id="${escapeHtml(task.watcher_id)}"
            >
                <div class="task-card-top">
                    <span class="badge badge-${escapeHtml(priorityTone)}">
                        ${escapeHtml(String(task.priority).toUpperCase())}
                    </span>
                    ${pendingBadge}
                    <button
                        class="kanban-column-menu"
                        type="button"
                        aria-label="Mở menu task"
                    >⋮</button>
                </div>

                <h3 class="task-card-title">${escapeHtml(task.title)}</h3>
                <p class="task-card-desc">${escapeHtml(task.description)}</p>
                <p class="task-card-desc task-project-label">${escapeHtml(projectText)}</p>

                <div class="task-card-progress">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: ${progress}%;"></div>
                    </div>
                    <small>${progress}% hoàn thành</small>
                </div>

                <div class="task-assignee-row">
                    <div class="task-assignee">
                        <span class="task-avatar">${initials}</span>
                        <span>${deadlineText}</span>
                    </div>

                    <div class="task-card-meta-group">
                        ${columnKey === "done" ? "<span>✓ Done</span>" : "<span>▣ 0</span><span>□ 0</span>"}
                    </div>
                </div>
            </article>
        `;
    }

    function clearBoard() {
        document.querySelectorAll("[data-kanban-list]").forEach((list) => {
            list.innerHTML = "";
        });
    }

    function renderTasks(tasks) {
        clearBoard();

        latestTasks = Array.isArray(tasks) ? tasks.map(normalizeTask) : [];

        if (latestTasks.length === 0) {
            const todoList = document.querySelector('[data-kanban-column][data-status="todo"] [data-kanban-list]');

            if (todoList) {
                todoList.innerHTML = `
                    <div class="ui-empty-state" style="min-height: 220px;">
                        <div class="ui-empty-icon">☑</div>
                        <div class="ui-empty-content">
                            <h3>Chưa có task</h3>
                            <p>Tạo task mới để bắt đầu quản lý công việc trên Kanban.</p>
                        </div>
                    </div>
                `;
            }

            updateColumnCounts();
            return;
        }

        latestTasks.forEach((task) => {
            const columnKey = getColumnByStatus(task.status);
            const list = document.querySelector(`[data-kanban-column][data-status="${columnKey}"] [data-kanban-list]`);

            if (list) {
                list.insertAdjacentHTML("beforeend", renderTaskCard(task));
            }
        });

        updateColumnCounts();
    }

    function getFilteredTasks() {
        const projectFilter = document.querySelector("[data-project-filter]");
        const assigneeFilter = document.querySelector("[data-assignee-filter]") || document.querySelector("#js-filter-assignee");
        const timeFilter = document.querySelector("[data-time-filter]") || document.querySelector("#js-filter-time");

        const selectedProjectId = projectFilter?.value || "";
        const selectedAssigneeId = assigneeFilter?.value || "";
        const selectedTime = timeFilter?.value || "";

        let filtered = [...latestTasks];

        if (selectedProjectId) {
            filtered = filtered.filter((task) => String(task.project_id || "") === String(selectedProjectId));
        }

        if (selectedAssigneeId) {
            filtered = filtered.filter((task) => String(task.assignee_id || "") === String(selectedAssigneeId));
        }

        if (selectedTime) {
            const now = new Date();
            now.setHours(0, 0, 0, 0);

            filtered = filtered.filter((task) => {
                if (!task.deadline) return false;

                const deadline = new Date(`${String(task.deadline).slice(0, 10)}T00:00:00`);
                if (Number.isNaN(deadline.getTime())) return false;

                if (selectedTime === "overdue") {
                    return deadline < now && getColumnByStatus(task.status) !== "done";
                }

                if (selectedTime === "today") {
                    return deadline.getFullYear() === now.getFullYear()
                        && deadline.getMonth() === now.getMonth()
                        && deadline.getDate() === now.getDate();
                }

                if (selectedTime === "week") {
                    const nextWeek = new Date(now);
                    nextWeek.setDate(now.getDate() + 7);

                    return deadline >= now && deadline <= nextWeek;
                }

                return true;
            });
        }

        return filtered;
    }

    function findTaskById(taskId) {
        return latestTasks.find((task) => String(task.id) === String(taskId));
    }

    function appendTaskToBoard(task) {
        const normalized = normalizeTask(task);
        const columnKey = getColumnByStatus(normalized.status);
        const list = document.querySelector(`[data-kanban-column][data-status="${columnKey}"] [data-kanban-list]`);

        if (!list) return;

        const emptyState = list.querySelector(".ui-empty-state");
        if (emptyState) {
            emptyState.remove();
        }

        latestTasks.unshift(normalized);
        list.insertAdjacentHTML("afterbegin", renderTaskCard(normalized));
        updateColumnCounts();
    }

    async function apiRequest(endpoint, options = {}) {
        if (window.CAHApi) {
            const method = String(options.method || "GET").toLowerCase();
            const body = options.body ? JSON.parse(options.body) : undefined;

            const requestOptions = {
                loading: options.loading ?? false,
                loadingMessage: options.loadingMessage || "",
                headers: {
                    Authorization: "Bearer " + getToken()
                }
            };

            if (method === "get") return CAHApi.get(endpoint, requestOptions);
            if (method === "post") return CAHApi.post(endpoint, body || {}, requestOptions);
            if (method === "put") return CAHApi.put(endpoint, body || {}, requestOptions);
            if (method === "patch") return CAHApi.patch(endpoint, body || {}, requestOptions);
            if (method === "delete") return CAHApi.delete(endpoint, requestOptions);
        }

        const response = await fetch(`${BASE_URL}/public${endpoint}`, {
            ...options,
            headers: {
                Accept: "application/json",
                Authorization: "Bearer " + getToken(),
                ...(options.headers || {})
            }
        });

        const text = await response.text();
        let payload;

        try {
            payload = JSON.parse(text);
        } catch (error) {
            console.error("Raw API response:", text);
            throw new Error("API trả về dữ liệu không hợp lệ.");
        }

        if (!response.ok || payload.status === "error") {
            throw new Error(payload.message || `HTTP ${response.status}`);
        }

        return payload;
    }

    async function loadProjectsFromApi() {
        if (!getToken()) return;

        try {
            const response = await apiRequest("/api/projects?_=" + Date.now());
            latestProjects = Array.isArray(response.data)
                ? response.data.filter((project) => !project.is_virtual)
                : [];

            populateProjectControls();
        } catch (error) {
            latestProjects = [];
            populateProjectControls();
        }
    }

    async function loadEmployeesFromApi() {
        if (!getToken()) return;

        try {
            const response = await apiRequest("/api/employees?_=" + Date.now());
            latestEmployees = Array.isArray(response.data) ? response.data : [];
            populateEmployeeControls();
        } catch (error) {
            latestEmployees = [];
            populateEmployeeControls();
        }
    }

    async function loadTasksFromApi() {
        if (!getToken()) {
            updateColumnCounts();
            return;
        }

        try {
            const urlParams = new URLSearchParams(window.location.search);
            const projectId = toNumberOrNull(urlParams.get("project_id"));

            let endpoint = "/api/tasks?_=" + Date.now();

            if (projectId) {
                endpoint += "&project_id=" + encodeURIComponent(projectId);
            }

            const response = await apiRequest(endpoint, {
                method: "GET",
                loading: true,
                loadingMessage: "Đang tải dữ liệu Kanban..."
            });

            latestTasks = Array.isArray(response.data) ? response.data.map(normalizeTask) : [];
            renderTasks(getFilteredTasks());
        } catch (error) {
            updateColumnCounts();

            if (window.CAHToast) {
                CAHToast.error("Không tải được Kanban", error.message || "API task đang lỗi.");
            }
        }
    }

    function populateProjectControls() {
        const projectFilter = document.querySelector("[data-project-filter]");
        const urlParams = new URLSearchParams(window.location.search);
        const projectIdFromUrl = urlParams.get("project_id");

        if (projectFilter) {
            const currentValue = projectFilter.value;
            projectFilter.innerHTML = '<option value="">Dự án: Tất cả</option>';

            latestProjects.forEach((project) => {
                const option = document.createElement("option");
                option.value = project.id;
                option.textContent = project.name || `Dự án #${project.id}`;
                projectFilter.appendChild(option);
            });

            if (projectIdFromUrl) {
                projectFilter.value = projectIdFromUrl;
            } else if ([...projectFilter.options].some((option) => option.value === currentValue)) {
                projectFilter.value = currentValue;
            }
        }
    }

    function populateEmployeeControls() {
        const assigneeFilter = document.querySelector("[data-assignee-filter]") || document.querySelector("#js-filter-assignee");

        if (assigneeFilter) {
            const currentValue = assigneeFilter.value;
            assigneeFilter.innerHTML = '<option value="">Người phụ trách: Tất cả</option>';

            latestEmployees
                .filter((employee) => String(employee.role || "").toLowerCase() === "employee" && String(employee.status || "").toLowerCase() === "active")
                .forEach((employee) => {
                    const option = document.createElement("option");
                    option.value = employee.id;
                    option.textContent = employee.full_name || employee.email || `Nhân sự #${employee.id}`;
                    assigneeFilter.appendChild(option);
                });

            if ([...assigneeFilter.options].some((option) => option.value === currentValue)) {
                assigneeFilter.value = currentValue;
            }
        }
    }

    function projectOptionsHtml(selectedId) {
        if (latestProjects.length === 0) {
            return '<option value="">Chưa có dự án, hãy tạo project trước</option>';
        }

        return latestProjects.map((project) => {
            const selected = String(project.id) === String(selectedId) ? "selected" : "";
            return `<option value="${escapeHtml(project.id)}" ${selected}>${escapeHtml(project.name || `Dự án #${project.id}`)}</option>`;
        }).join("");
    }

    function employeeOptionsHtml(selectedId) {
        const employees = latestEmployees.filter((employee) => {
            return String(employee.role || "").toLowerCase() === "employee" && String(employee.status || "").toLowerCase() === "active";
        });

        if (employees.length === 0) {
            return '<option value="">Chưa có employee active</option>';
        }

        return employees.map((employee) => {
            const selected = String(employee.id) === String(selectedId) ? "selected" : "";
            return `<option value="${escapeHtml(employee.id)}" ${selected}>${escapeHtml(employee.full_name || employee.email || `Nhân sự #${employee.id}`)}</option>`;
        }).join("");
    }

    function watcherOptionsHtml(selectedId) {
        const users = latestEmployees.filter((employee) => {
            return String(employee.status || "").toLowerCase() === "active";
        });

        if (users.length === 0) {
            return `<option value="${escapeHtml(getCurrentUserId())}">Người đang đăng nhập</option>`;
        }

        return users.map((employee) => {
            const selected = String(employee.id) === String(selectedId) ? "selected" : "";
            const role = employee.role ? ` · ${String(employee.role).toUpperCase()}` : "";
            return `<option value="${escapeHtml(employee.id)}" ${selected}>${escapeHtml((employee.full_name || employee.email || `Nhân sự #${employee.id}`) + role)}</option>`;
        }).join("");
    }

    function taskFormBody(mode, taskData) {
        const isEdit = mode === "edit";
        const canEdit = true;
        const task = taskData ? normalizeTask(taskData) : null;
        const selectedProjectId = task?.project_id || getSelectedProjectId() || "";
        const fallbackAssigneeId = task?.assignee_id || getFallbackAssigneeId() || "";
        const fallbackWatcherId = task?.watcher_id || getCurrentUserId() || "";

        return `
            <form class="task-modal-form" data-task-form data-task-form-mode="${escapeHtml(mode)}" data-task-id="${escapeHtml(task?.id || "")}">
                <div class="form-group">
                    <label class="form-label">Tên công việc</label>
                    <input
                        class="form-control"
                        type="text"
                        name="title"
                        value="${escapeHtml(task?.title || "")}"
                        placeholder="Nhập tên công việc"
                        ${canEdit ? "required" : "readonly"}
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Dự án</label>
                        <select class="form-select" name="project_id" ${canEdit ? "required" : "disabled"}>
                            ${projectOptionsHtml(selectedProjectId)}
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Người thực hiện</label>
                        <select class="form-select" name="assignee_id" ${canEdit ? "required" : "disabled"}>
                            ${employeeOptionsHtml(fallbackAssigneeId)}
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status" ${canEdit ? "" : "disabled"}>
                            <option value="Pending approval" ${task?.status === "Pending approval" ? "selected" : ""}>Chờ duyệt</option>
                            <option value="To do" ${!task || task?.status === "To do" ? "selected" : ""}>Cần làm</option>
                            <option value="Doing" ${task?.status === "Doing" ? "selected" : ""}>Đang thực hiện</option>
                            <option value="Review" ${task?.status === "Review" ? "selected" : ""}>Đang kiểm tra</option>
                            <option value="Done" ${task?.status === "Done" ? "selected" : ""}>Hoàn thành</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select class="form-select" name="priority" ${canEdit ? "" : "disabled"}>
                            <option value="Low" ${task?.priority === "Low" ? "selected" : ""}>Thấp</option>
                            <option value="Medium" ${!task || task?.priority === "Medium" ? "selected" : ""}>Trung bình</option>
                            <option value="High" ${task?.priority === "High" ? "selected" : ""}>Cao</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input
                            class="form-control"
                            type="date"
                            name="deadline"
                            value="${escapeHtml(task?.deadline || "")}"
                            ${canEdit ? "required" : "readonly"}
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Người theo dõi</label>
                        <select class="form-select" name="watcher_id" ${canEdit ? "" : "disabled"}>
                            ${watcherOptionsHtml(fallbackWatcherId)}
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea
                        class="form-textarea"
                        name="description"
                        placeholder="Mô tả ngắn về công việc"
                        ${canEdit ? "" : "readonly"}
                    >${escapeHtml(task?.description || "")}</textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    ${task?.id ? `<button class="btn btn-danger" type="button" data-delete-task="${escapeHtml(task.id)}">Xóa task</button>` : ""}
                    ${canEdit ? '<button class="btn btn-primary" type="submit">' + (isEdit ? 'Lưu thay đổi' : 'Tạo task') + '</button>' : ''}
                </div>
            </form>
        `;
    }

    function openTaskModal(mode, taskData) {
        if (!window.CAHModal) {
            if (window.CAHToast) {
                CAHToast.error("Không mở được modal", "Không tìm thấy CAHModal.");
            }
            return;
        }

        const titleMap = {
            create: "Tạo công việc mới",
            view: "Chi tiết công việc",
            edit: "Chỉnh sửa công việc"
        };

        const subtitleMap = {
            create: "Task sẽ được lưu vào database và tự đồng bộ lại Kanban.",
            view: "Xem thông tin task, gửi duyệt hoặc xử lý hoàn thành.",
            edit: "Cập nhật nội dung task và đồng bộ lại Kanban."
        };

        CAHModal.open({
            title: titleMap[mode] || "Công việc",
            subtitle: subtitleMap[mode] || "",
            body: taskFormBody(mode, taskData)
        });
    }

    async function updateTaskStatusById(taskId, newStatus) {
        await apiRequest(`/api/tasks/${taskId}/status`, {
            method: "PATCH",
            body: JSON.stringify({ status: newStatus }),
            loading: false
        });

        if (window.CAHToast) {
            CAHToast.success("Đã cập nhật", `Task đã chuyển sang trạng thái ${newStatus}.`);
        }

        await loadTasksFromApi();
    }

    async function updateTaskStatus(card, newColumnKey) {
        const taskId = card.dataset.taskId;
        const newStatus = getStatusByColumn(newColumnKey);

        if (!taskId || !getToken()) {
            if (window.CAHToast) {
                CAHToast.info("Cập nhật giao diện", "Bạn chưa đăng nhập nên thay đổi chỉ áp dụng trên UI demo.");
            }
            return;
        }

        try {
            await updateTaskStatusById(taskId, newStatus);
        } catch (error) {
            if (previousDropState?.list && previousDropState?.nextSibling !== undefined) {
                previousDropState.list.insertBefore(card, previousDropState.nextSibling);
                card.dataset.status = previousDropState.status;
                updateColumnCounts();
            }

            if (window.CAHToast) {
                CAHToast.error("Không thể cập nhật", error.message || "Không thể chuyển trạng thái task.");
            }
        }
    }

    async function createTaskFromForm(form) {
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : Object.fromEntries(new FormData(form));

        const projectId = toNumberOrNull(data.project_id);
        const assigneeId = toNumberOrNull(data.assignee_id);

        if (!projectId) {
            throw new Error("Vui lòng chọn dự án trước khi tạo task.");
        }

        if (!assigneeId) {
            throw new Error("Vui lòng chọn người thực hiện task.");
        }

        const payload = {
            title: data.title,
            description: data.description || "",
            priority: data.priority || "Medium",
            deadline: data.deadline,
            project_id: projectId,
            assignee_id: assigneeId,
            watcher_id: toNumberOrNull(data.watcher_id) || getCurrentUserId(),
            status: isEmployee() ? "Pending approval" : (data.status || "To do")
        };

        const response = await apiRequest("/api/tasks", {
            method: "POST",
            body: JSON.stringify(payload),
            loading: true,
            loadingMessage: "Đang tạo task mới..."
        });

        const createdTask = response?.data?.task || response?.data || {
            id: response?.data?.id,
            ...payload,
            assigner_id: getCurrentUserId()
        };

        if (window.CAHModal) {
            CAHModal.close();
        }

        appendTaskToBoard(createdTask);

        if (window.CAHToast) {
            CAHToast.success("Tạo task thành công", response.message || "Task mới đã hiển thị trên Kanban.");
        }

        await loadTasksFromApi();
    }

    async function updateTaskFromForm(form) {
        const taskId = form.dataset.taskId;
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : Object.fromEntries(new FormData(form));

        if (!taskId) {
            throw new Error("Thiếu ID task.");
        }

        const projectId = toNumberOrNull(data.project_id);
        const assigneeId = toNumberOrNull(data.assignee_id);

        if (!projectId) {
            throw new Error("Vui lòng chọn dự án.");
        }

        if (!assigneeId) {
            throw new Error("Vui lòng chọn người thực hiện.");
        }

        const payload = {
            title: data.title,
            description: data.description || "",
            priority: data.priority || "Medium",
            deadline: data.deadline,
            project_id: projectId,
            assignee_id: assigneeId,
            watcher_id: toNumberOrNull(data.watcher_id) || null,
            status: data.status || undefined
        };

        const response = await apiRequest(`/api/tasks/${taskId}`, {
            method: "PUT",
            body: JSON.stringify(payload),
            loading: true,
            loadingMessage: "Đang lưu thay đổi..."
        });

        if (data.status) {
            await apiRequest(`/api/tasks/${taskId}/status`, {
                method: "PATCH",
                body: JSON.stringify({ status: data.status }),
                loading: false
            });
        }

        if (window.CAHModal) {
            CAHModal.close();
        }

        if (window.CAHToast) {
            CAHToast.success("Đã cập nhật", response.message || "Task đã được cập nhật.");
        }

        await loadTasksFromApi();
    }

    async function deleteTask(taskId) {
        let task = findTaskById(taskId);
        if (!task) task = { title: "Công việc này" };

        const confirmed = window.confirm(`Xoá task "${task.title}"? Thao tác này không thể hoàn tác.`);

        if (!confirmed) return;

        await apiRequest(`/api/tasks/${taskId}`, {
            method: "DELETE",
            loading: true,
            loadingMessage: "Đang xoá task..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã xoá task", "Task đã được xoá khỏi Kanban.");
        }

        if (window.CAHModal) {
            CAHModal.close();
        }

        await loadTasksFromApi();
    }

    async function approvalAction(taskId, action) {
        const endpointMap = {
            submit: `/api/tasks/${taskId}/submit`,
            approve: `/api/tasks/${taskId}/approve`,
            reject: `/api/tasks/${taskId}/reject`
        };

        const messageMap = {
            submit: "Đã gửi task sang trạng thái chờ duyệt.",
            approve: "Task đã được duyệt và chuyển sang Hoàn thành.",
            reject: "Task đã bị từ chối và chuyển về Đang thực hiện."
        };

        if (!endpointMap[action]) return;

        await apiRequest(endpointMap[action], {
            method: "POST",
            body: JSON.stringify({}),
            loading: true,
            loadingMessage: "Đang xử lý workflow..."
        });

        if (window.CAHToast) {
            CAHToast.success("Đã xử lý", messageMap[action] || "Workflow đã được cập nhật.");
        }

        if (window.CAHModal) {
            CAHModal.close();
        }

        await loadTasksFromApi();
    }

    function openActionMenu(button, explicitTaskId) {
        closeActionMenu();

        const cardContainer = button.closest("[data-task-card]");
        const taskId = explicitTaskId || (cardContainer ? cardContainer.dataset.taskId : null);

        if (!taskId) {
            console.error("Không tìm thấy ID của Task này!");
            return;
        }

        let task = findTaskById(taskId);

        if (!task) {
            task = {
                id: taskId,
                status: cardContainer?.dataset?.status || "todo",
                title: cardContainer?.querySelector(".task-card-title")?.innerText || "Công việc này"
            };
        }

        const columnKey = getColumnByStatus(task.status);
        const menu = document.createElement("div");
        menu.className = "kanban-action-menu";
        menu.setAttribute("data-kanban-action-menu", "");

        const actions = [
            `<button type="button" data-task-action="view" data-task-id="${escapeHtml(taskId)}">👁 Xem chi tiết</button>`,
            `<button type="button" data-task-action="edit" data-task-id="${escapeHtml(taskId)}">✎ Chỉnh sửa</button>`
        ];

        if (columnKey !== "doing" && columnKey !== "pending" && columnKey !== "done") {
            actions.push(`<button type="button" data-task-action="move-doing" data-task-id="${escapeHtml(taskId)}">→ Chuyển Đang thực hiện</button>`);
        }

        if (columnKey !== "review" && columnKey !== "pending" && columnKey !== "done") {
            actions.push(`<button type="button" data-task-action="move-review" data-task-id="${escapeHtml(taskId)}">→ Chuyển Đang kiểm tra</button>`);
        }

        if (columnKey !== "done" && isManagerLike()) {
            actions.push(`<button type="button" data-task-action="approve" data-task-id="${escapeHtml(taskId)}">✓ Duyệt hoàn thành</button>`);
        }

        if (columnKey !== "pending" && isEmployee()) {
            actions.push(`<button type="button" data-task-action="submit" data-task-id="${escapeHtml(taskId)}">↥ Gửi duyệt</button>`);
        }

        if (columnKey === "pending" && isManagerLike()) {
            actions.push(`<button type="button" data-task-action="reject" data-task-id="${escapeHtml(taskId)}">↩ Từ chối</button>`);
        }

        actions.push(`<button type="button" class="danger" data-task-action="delete" data-task-id="${escapeHtml(taskId)}">🗑 Xóa task</button>`);

        menu.innerHTML = actions.join("");

        document.body.appendChild(menu);

        const rect = button.getBoundingClientRect();
        const menuWidth = 220;

        menu.style.position = "fixed";
        menu.style.top = `${rect.bottom + 8}px`;
        menu.style.left = `${Math.min(rect.left, window.innerWidth - menuWidth - 16)}px`;
        menu.style.zIndex = "9999";
    }

    function closeActionMenu() {
        document.querySelectorAll("[data-kanban-action-menu]").forEach((menu) => menu.remove());
    }

    function getColumnFromEventTarget(target) {
        return target.closest("[data-kanban-column]");
    }

    function bindBoardEvents() {
        document.addEventListener("click", async (event) => {
            const addButton = event.target.closest("[data-add-task]");
            const menuButton = event.target.closest(".kanban-column-menu");
            const taskCard = event.target.closest("[data-task-card]");
            const menuAction = event.target.closest("[data-task-action]");
            const deleteButton = event.target.closest("[data-delete-task]");

            if (addButton) {
                event.preventDefault();

                if (latestProjects.length === 0) {
                    if (window.CAHToast) {
                        CAHToast.error("Chưa có dự án", "Hãy tạo project trước rồi mới tạo task.");
                    } else {
                        alert("Hãy tạo project trước rồi mới tạo task.");
                    }

                    return;
                }

                openTaskModal("create");
                return;
            }

            if (deleteButton) {
                event.preventDefault();
                await deleteTask(deleteButton.dataset.deleteTask);
                return;
            }

            if (menuAction) {
                event.preventDefault();

                const taskId = menuAction.dataset.taskId;
                const action = menuAction.dataset.taskAction;
                const task = findTaskById(taskId);

                closeActionMenu();

                try {
                    if (action === "view") openTaskModal("view", task);
                    if (action === "edit") openTaskModal("edit", task);
                    if (action === "delete") await deleteTask(taskId);
                    if (action === "move-doing") await updateTaskStatusById(taskId, "Doing");
                    if (action === "move-review") await updateTaskStatusById(taskId, "Review");
                    if (action === "submit") await approvalAction(taskId, "submit");
                    if (action === "approve") await approvalAction(taskId, "approve");
                    if (action === "reject") await approvalAction(taskId, "reject");
                } catch (error) {
                    if (window.CAHToast) {
                        CAHToast.error("Không thể xử lý", error.message || "Có lỗi khi xử lý task.");
                    }
                }

                return;
            }

            if (menuButton && taskCard) {
                event.preventDefault();
                event.stopPropagation();
                openActionMenu(menuButton);
                return;
            }

            if (!event.target.closest("[data-kanban-action-menu]")) {
                closeActionMenu();
            }
        });

        document.addEventListener("submit", async (event) => {
            const form = event.target.closest("[data-task-form]");
            if (!form) return;

            event.preventDefault();

            try {
                const mode = form.dataset.taskFormMode;

                if (mode === "edit") {
                    await updateTaskFromForm(form);
                } else {
                    await createTaskFromForm(form);
                }
            } catch (error) {
                if (window.CAHToast) {
                    CAHToast.error("Không thể lưu task", error.message || "Vui lòng kiểm tra dữ liệu.");
                } else {
                    alert(error.message || "Không thể lưu task.");
                }
            }
        });

        document.addEventListener("dragstart", (event) => {
            const card = event.target.closest("[data-task-card]");
            if (!card) return;

            draggedCard = card;
            previousDropState = {
                list: card.parentElement,
                nextSibling: card.nextSibling,
                status: card.dataset.status
            };

            card.classList.add("is-dragging");
            event.dataTransfer.effectAllowed = "move";
            event.dataTransfer.setData("text/plain", card.dataset.taskId || "");
        });

        document.addEventListener("dragend", () => {
            if (draggedCard) {
                draggedCard.classList.remove("is-dragging");
            }

            document.querySelectorAll("[data-kanban-column]").forEach((column) => {
                column.classList.remove("is-over");
            });

            draggedCard = null;
        });

        document.querySelectorAll("[data-kanban-column]").forEach((column) => {
            column.addEventListener("dragover", (event) => {
                event.preventDefault();
                column.classList.add("is-over");
            });

            column.addEventListener("dragleave", (event) => {
                if (!column.contains(event.relatedTarget)) {
                    column.classList.remove("is-over");
                }
            });

            column.addEventListener("drop", async (event) => {
                event.preventDefault();
                column.classList.remove("is-over");

                if (!draggedCard) return;

                const list = column.querySelector("[data-kanban-list]");
                const newColumnKey = column.dataset.status;

                if (!list || !newColumnKey) return;

                list.appendChild(draggedCard);
                draggedCard.dataset.status = newColumnKey;
                updateColumnCounts();

                await updateTaskStatus(draggedCard, newColumnKey);
            });
        });

        document.querySelector("[data-project-filter]")?.addEventListener("change", () => renderTasks(getFilteredTasks()));
        document.querySelector("[data-assignee-filter]")?.addEventListener("change", () => renderTasks(getFilteredTasks()));
        document.querySelector("#js-filter-assignee")?.addEventListener("change", () => renderTasks(getFilteredTasks()));
        document.querySelector("[data-time-filter]")?.addEventListener("change", () => renderTasks(getFilteredTasks()));
        document.querySelector("#js-filter-time")?.addEventListener("change", () => renderTasks(getFilteredTasks()));
        document.querySelector("[data-filter-task]")?.addEventListener("click", () => renderTasks(getFilteredTasks()));
        document.querySelector("#js-btn-filter")?.addEventListener("click", () => renderTasks(getFilteredTasks()));
    }

    function injectActionStyles() {
        if (document.getElementById("kanban-action-menu-style")) return;

        const style = document.createElement("style");
        style.id = "kanban-action-menu-style";
        style.textContent = `
            .kanban-action-menu {
                width: 220px;
                padding: 8px;
                border: 1px solid rgba(15, 23, 42, .12);
                border-radius: 14px;
                background: #fff;
                box-shadow: 0 24px 60px rgba(15, 23, 42, .16);
                display: grid;
                gap: 4px;
            }

            .kanban-action-menu button {
                width: 100%;
                min-height: 36px;
                padding: 0 10px;
                border: 0;
                border-radius: 10px;
                text-align: left;
                color: #0f172a;
                background: transparent;
                font-size: 13px;
                font-weight: 800;
                cursor: pointer;
            }

            .kanban-action-menu button:hover {
                color: #00513a;
                background: #e9fbf4;
            }

            .kanban-action-menu button.danger {
                color: #b91c1c;
            }

            .kanban-action-menu button.danger:hover {
                color: #991b1b;
                background: #fee2e2;
            }
        `;

        document.head.appendChild(style);
    }

    async function init() {
        bindBoardEvents();

        await Promise.allSettled([
            loadProjectsFromApi(),
            loadEmployeesFromApi()
        ]);

        await loadTasksFromApi();
    }

    init();
})();