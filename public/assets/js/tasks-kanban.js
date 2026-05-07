(function () {
    "use strict";

    const board = document.querySelector("[data-kanban-board]");
    if (!board) return;

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

    const PROGRESS_BY_COLUMN = {
        pending: 0,
        todo: 10,
        doing: 55,
        review: 82,
        done: 100
    };

    const PRIORITY_TONE = {
        Low: "info",
        Medium: "primary",
        High: "danger",
        low: "info",
        medium: "primary",
        high: "danger"
    };

    const DEFAULT_PROJECT_ID = 1;
    const DEFAULT_ASSIGNEE_ID = 2;
    const DEFAULT_WATCHER_ID = 1;

    let draggedCard = null;
    let previousDropState = null;
    let latestTasks = [];

    injectActionStyles();

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

    function toNumberOrNull(value) {
        if (value === undefined || value === null || value === "") return null;

        const number = Number(value);
        return Number.isFinite(number) && number > 0 ? number : null;
    }

    function getToken() {
        return localStorage.getItem("cah_token") || localStorage.getItem("cah_auth_token") || "";
    }

    function getDecodedToken() {
        try {
            const token = getToken();
            if (!token) return null;

            const base64Url = token.split(".")[1];
            if (!base64Url) return null;

            const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
            const paddedBase64 = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, "=");
            const jsonPayload = decodeURIComponent(atob(paddedBase64).split("").map(function (char) {
                return "%" + ("00" + char.charCodeAt(0).toString(16)).slice(-2);
            }).join(""));

            return JSON.parse(jsonPayload);
        } catch (error) {
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

        return window.CAHAuth?.getUser?.() || {};
    }

    function getCurrentUserId() {
        const user = getCurrentUser();

        return toNumberOrNull(user?.id)
            || toNumberOrNull(user?.employee_id)
            || DEFAULT_WATCHER_ID;
    }

    function isManagerLike() {
        const role = String(getCurrentUser()?.role || "").toLowerCase();
        return role === "admin" || role === "manager";
    }

    function isEmployee() {
        return String(getCurrentUser()?.role || "").toLowerCase() === "employee";
    }

    function getFallbackAssigneeId() {
        if (isEmployee()) {
            return getCurrentUserId();
        }

        return DEFAULT_ASSIGNEE_ID;
    }

    function normalizePriority(priority) {
        const value = String(priority || "Medium").trim().toLowerCase();

        if (value === "low") return "Low";
        if (value === "high") return "High";
        return "Medium";
    }

    function getColumnByStatus(status) {
        const key = String(status || "To do").trim().toLowerCase();
        return STATUS_TO_COLUMN[key] || "todo";
    }

    function getStatusByColumn(column) {
        return COLUMN_TO_STATUS[column] || "To do";
    }

    function normalizeStatus(status) {
        const column = getColumnByStatus(status);
        return getStatusByColumn(column);
    }

    function getSelectedProjectId() {
        const selectors = [
            "[data-project-filter]",
            "[name='project_id_filter']",
            "[name='project_id']",
            "[data-task-project-id]"
        ];

        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (!element) continue;

            const value = element.dataset.taskProjectId || element.value;
            const number = toNumberOrNull(value);

            if (number) return number;
        }

        return DEFAULT_PROJECT_ID;
    }

    function updateColumnCounts() {
        document.querySelectorAll("[data-kanban-column]").forEach((column) => {
            const count = column.querySelectorAll("[data-task-card]").length;
            const countEl = column.querySelector("[data-column-count], .kanban-count");

            if (countEl) {
                countEl.textContent = count;
            }
        });
    }

    function getTaskProgress(status) {
        return PROGRESS_BY_COLUMN[getColumnByStatus(status)] ?? 10;
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

        return {
            id: task?.id,
            title: task?.title || "Chưa có tiêu đề",
            description: task?.description || "Chưa có mô tả.",
            status: normalizedStatus,
            priority: normalizePriority(task?.priority),
            deadline: task?.deadline || "",
            assignee_id: task?.assignee_id || "",
            assignee_name: task?.assignee_name || "",
            assigner_id: task?.assigner_id || "",
            assigner_name: task?.assigner_name || "",
            watcher_id: task?.watcher_id || "",
            watcher_name: task?.watcher_name || "",
            project_id: task?.project_id || "",
            project_name: task?.project_name || "",
            assigner_role: task?.assigner_role || ""
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

                    ${columnKey === "pending" ? '<span class="badge badge-warning">CHỜ DUYỆT</span>' : ""}

                    <button
                        class="kanban-column-menu"
                        type="button"
                        aria-label="Mở menu task"
                    >⋮</button>
                </div>

                <h3 class="task-card-title">${escapeHtml(task.title)}</h3>
                <p class="task-card-desc">${escapeHtml(task.description)}</p>

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
            const pendingList = document.querySelector('[data-kanban-column][data-status="pending"] [data-kanban-list]')
                || document.querySelector('[data-kanban-column][data-status="todo"] [data-kanban-list]');

            if (pendingList) {
                pendingList.innerHTML = `
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

    async function loadTasksFromApi() {
        if (!window.CAHApi || !getToken()) {
            updateColumnCounts();
            return;
        }

        try {
            const response = await CAHApi.get("/api/tasks", {
                loading: true,
                loadingMessage: "Đang tải dữ liệu Kanban...",
                headers: {
                    Authorization: "Bearer " + getToken()
                }
            });

            renderTasks(response.data || []);
        } catch (error) {
            updateColumnCounts();

            if (window.CAHToast) {
                CAHToast.info("Dùng dữ liệu tạm", "Không tải được task từ API, giao diện đang giữ dữ liệu hiện tại.");
            }
        }
    }

    function taskFormBody(mode, taskData) {
        const isEdit = mode === "edit";
        const task = taskData ? normalizeTask(taskData) : null;
        const selectedProjectId = getSelectedProjectId();
        const fallbackAssigneeId = getFallbackAssigneeId();
        const fallbackWatcherId = getCurrentUserId();

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
                        required
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="Pending approval" ${task?.status === "Pending approval" ? "selected" : ""}>Chờ duyệt</option>
                            <option value="To do" ${!task || task?.status === "To do" ? "selected" : ""}>Cần làm</option>
                            <option value="Doing" ${task?.status === "Doing" ? "selected" : ""}>Đang thực hiện</option>
                            <option value="Review" ${task?.status === "Review" ? "selected" : ""}>Đang kiểm tra</option>
                            <option value="Done" ${task?.status === "Done" ? "selected" : ""}>Hoàn thành</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select class="form-select" name="priority">
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
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Project ID</label>
                        <input
                            class="form-control"
                            type="number"
                            name="project_id"
                            value="${escapeHtml(task?.project_id || selectedProjectId)}"
                            min="1"
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Assignee ID</label>
                        <input
                            class="form-control"
                            type="number"
                            name="assignee_id"
                            value="${escapeHtml(task?.assignee_id || fallbackAssigneeId)}"
                            min="1"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Watcher ID</label>
                        <input
                            class="form-control"
                            type="number"
                            name="watcher_id"
                            value="${escapeHtml(task?.watcher_id || fallbackWatcherId)}"
                            min="1"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea
                        class="form-textarea"
                        name="description"
                        placeholder="Mô tả ngắn về công việc"
                    >${escapeHtml(task?.description || "")}</textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">${isEdit ? "Lưu thay đổi" : "Tạo task"}</button>
                </div>
            </form>
        `;
    }

    function openTaskModal(mode, taskData) {
        if (!window.CAHModal) return;

        const titleMap = {
            create: "Tạo công việc mới",
            view: "Chi tiết công việc",
            edit: "Chỉnh sửa công việc"
        };

        const subtitleMap = {
            create: isEmployee()
                ? "Task do Employee tạo sẽ chuyển vào Chờ duyệt trước khi vào Cần làm."
                : "Task do Admin/Manager tạo sẽ đi thẳng vào Cần làm.",
            view: "Xem thông tin task và xử lý workflow.",
            edit: "Cập nhật nội dung task và đồng bộ lại Kanban."
        };

        CAHModal.open({
            title: titleMap[mode] || "Công việc",
            subtitle: subtitleMap[mode] || "",
            body: taskFormBody(mode, taskData)
        });
    }

    async function updateTaskStatusById(taskId, newStatus) {
        await CAHApi.patch(`/api/tasks/${taskId}/status`, { status: newStatus }, {
            loading: false,
            headers: {
                Authorization: "Bearer " + getToken()
            }
        });

        if (window.CAHToast) {
            CAHToast.success("Đã cập nhật", `Task đã chuyển sang trạng thái ${newStatus}.`);
        }

        await loadTasksFromApi();
    }

    async function updateTaskStatus(card, newColumnKey) {
        const taskId = card.dataset.taskId;
        const newStatus = getStatusByColumn(newColumnKey);

        if (!taskId || !window.CAHApi || !getToken()) {
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
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : {};

        const payload = {
            title: data.title,
            description: data.description || "",
            priority: data.priority || "Medium",
            deadline: data.deadline,
            project_id: toNumberOrNull(data.project_id) || getSelectedProjectId(),
            assignee_id: toNumberOrNull(data.assignee_id) || getFallbackAssigneeId(),
            watcher_id: toNumberOrNull(data.watcher_id) || getCurrentUserId()
        };

        const response = await CAHApi.post("/api/tasks", payload, {
            loading: true,
            loadingMessage: "Đang tạo task mới...",
            headers: {
                Authorization: "Bearer " + getToken()
            }
        });

        const createdTask = response?.data?.task || response?.data || {
            id: response?.data?.id,
            ...payload,
            status: isEmployee() ? "Pending approval" : "To do",
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
        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : {};

        if (!taskId) {
            throw new Error("Thiếu ID task.");
        }

        const payload = {
            title: data.title,
            description: data.description || "",
            priority: data.priority || "Medium",
            deadline: data.deadline,
            project_id: toNumberOrNull(data.project_id) || getSelectedProjectId(),
            assignee_id: toNumberOrNull(data.assignee_id) || null,
            watcher_id: toNumberOrNull(data.watcher_id) || null,
            status: data.status || undefined
        };

        const response = await CAHApi.put(`/api/tasks/${taskId}`, payload, {
            loading: true,
            loadingMessage: "Đang lưu thay đổi...",
            headers: {
                Authorization: "Bearer " + getToken()
            }
        });

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

        await CAHApi.delete(`/api/tasks/${taskId}`, {
            loading: true,
            loadingMessage: "Đang xoá task...",
            headers: {
                Authorization: "Bearer " + getToken()
            }
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
            submit: "Task đã được gửi sang Đang kiểm tra.",
            approve: "Task đã được duyệt.",
            reject: "Task đã bị từ chối và chuyển về Đang thực hiện."
        };

        if (!endpointMap[action]) return;

        await CAHApi.post(endpointMap[action], {}, {
            loading: true,
            loadingMessage: "Đang xử lý workflow...",
            headers: {
                Authorization: "Bearer " + getToken()
            }
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
            `<button type="button" data-task-action="view" data-task-id="${escapeHtml(taskId)}">Xem chi tiết</button>`
        ];

        if (isManagerLike()) {
            actions.push(`<button type="button" data-task-action="edit" data-task-id="${escapeHtml(taskId)}">Chỉnh sửa</button>`);
            actions.push(`<button type="button" data-task-action="delete" data-task-id="${escapeHtml(taskId)}" class="danger">Xoá task</button>`);
        }

        if (columnKey === "pending") {
            if (isManagerLike()) {
                actions.push(`<button type="button" data-task-action="approve" data-task-id="${escapeHtml(taskId)}">Duyệt vào Cần làm</button>`);
                actions.push(`<button type="button" data-task-action="reject" data-task-id="${escapeHtml(taskId)}">Từ chối</button>`);
            } else {
                actions.push(`<button type="button" disabled>Đang chờ Admin/Manager duyệt</button>`);
            }
        }

        if (columnKey === "todo") {
            actions.push(`<button type="button" data-task-action="move-doing" data-task-id="${escapeHtml(taskId)}">Chuyển Đang thực hiện</button>`);
        }

        if (columnKey === "doing") {
            actions.push(`<button type="button" data-task-action="submit" data-task-id="${escapeHtml(taskId)}">Gửi kiểm tra hoàn thành</button>`);
        }

        if (columnKey === "review") {
            if (isManagerLike()) {
                actions.push(`<button type="button" data-task-action="approve" data-task-id="${escapeHtml(taskId)}">Duyệt hoàn thành</button>`);
                actions.push(`<button type="button" data-task-action="reject" data-task-id="${escapeHtml(taskId)}">Từ chối</button>`);
            } else {
                actions.push(`<button type="button" disabled>Đang chờ kiểm tra</button>`);
            }
        }

        if (columnKey !== "done" && isManagerLike()) {
            actions.push(`<button type="button" data-task-action="move-done" data-task-id="${escapeHtml(taskId)}">Đánh dấu hoàn thành</button>`);
        }

        if (columnKey === "done") {
            actions.push(`<button type="button" disabled>✓ Đã hoàn thành</button>`);
        }

        menu.innerHTML = actions.join("");

        document.body.appendChild(menu);

        const rect = button.getBoundingClientRect();
        menu.style.top = `${rect.bottom + window.scrollY + 8}px`;
        menu.style.left = `${Math.max(12, rect.right + window.scrollX - 220)}px`;
    }

    function closeActionMenu() {
        document.querySelectorAll("[data-kanban-action-menu]").forEach((menu) => menu.remove());
    }

    async function handleTaskAction(action, taskId) {
        let task = findTaskById(taskId);

        if (!task) {
            task = { id: taskId, title: "Công việc này" };
        }

        closeActionMenu();

        if (action === "view" || action === "edit") {
            openTaskModal(action, task);
            return;
        }

        if (action === "delete") {
            await deleteTask(taskId);
            return;
        }

        if (action === "submit" || action === "approve" || action === "reject") {
            await approvalAction(taskId, action);
            return;
        }

        if (action === "move-doing") {
            await updateTaskStatusById(taskId, "Doing");
            return;
        }

        if (action === "move-done") {
            await updateTaskStatusById(taskId, "Done");
        }
    }

    function injectActionStyles() {
        if (document.querySelector("[data-kanban-action-style]")) return;

        const style = document.createElement("style");
        style.setAttribute("data-kanban-action-style", "");
        style.textContent = `
            .kanban-board {
                grid-template-columns: repeat(5, minmax(260px, 1fr));
            }

            .kanban-dot.pending {
                background: #f59e0b;
            }

            .task-card.is-pending-approval {
                border-color: rgba(245, 158, 11, .35);
                background: linear-gradient(180deg, rgba(255, 251, 235, .72), #fff);
            }

            .kanban-action-menu {
                position: absolute;
                z-index: 9999;
                width: 240px;
                display: grid;
                gap: 4px;
                padding: 8px;
                border-radius: 16px;
                border: 1px solid rgba(15, 23, 42, 0.1);
                background: #fff;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
            }

            .kanban-action-menu button {
                min-height: 38px;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                padding: 0 12px;
                border-radius: 10px;
                color: #22314d;
                background: transparent;
                font-weight: 750;
                text-align: left;
                border: none;
                cursor: pointer;
            }

            .kanban-action-menu button:hover {
                color: var(--primary);
                background: var(--mint);
            }

            .kanban-action-menu button.danger {
                color: var(--danger);
            }

            .kanban-action-menu button.danger:hover {
                background: var(--danger-soft);
            }

            .kanban-action-menu button:disabled {
                opacity: .58;
                cursor: default;
            }

            .task-card.is-completed {
                opacity: .82;
            }

            .task-card.is-completed .task-card-title {
                text-decoration: line-through;
                color: #64748b;
            }

            .btn-danger {
                color: #fff;
                background: var(--danger);
            }

            .btn-danger:hover {
                filter: brightness(.95);
            }

            @media (max-width: 1280px) {
                .kanban-board {
                    grid-template-columns: repeat(5, minmax(280px, 1fr));
                    overflow-x: auto;
                }
            }
        `;
        document.head.appendChild(style);
    }

    document.addEventListener("dragstart", function (event) {
        const card = event.target.closest("[data-task-card]");
        if (!card) return;

        draggedCard = card;
        previousDropState = {
            list: card.parentElement,
            nextSibling: card.nextElementSibling,
            status: card.dataset.status
        };

        card.classList.add("is-dragging");
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData("text/plain", card.dataset.taskId || "");
    });

    document.addEventListener("dragend", function () {
        if (draggedCard) {
            draggedCard.classList.remove("is-dragging");
        }

        document.querySelectorAll(".kanban-column.is-over").forEach((column) => {
            column.classList.remove("is-over");
        });

        draggedCard = null;
        previousDropState = null;
        updateColumnCounts();
    });

    document.querySelectorAll("[data-kanban-column]").forEach((column) => {
        column.addEventListener("dragover", function (event) {
            event.preventDefault();
            column.classList.add("is-over");
        });

        column.addEventListener("dragleave", function (event) {
            if (!column.contains(event.relatedTarget)) {
                column.classList.remove("is-over");
            }
        });

        column.addEventListener("drop", function (event) {
            event.preventDefault();

            if (!draggedCard) return;

            const list = column.querySelector("[data-kanban-list]");
            if (!list) return;

            const newColumnKey = column.dataset.status || "todo";
            const oldStatus = draggedCard.dataset.status;

            list.appendChild(draggedCard);
            draggedCard.dataset.status = newColumnKey;
            column.classList.remove("is-over");
            updateColumnCounts();

            if (oldStatus !== newColumnKey) {
                updateTaskStatus(draggedCard, newColumnKey);
            }
        });
    });

    document.addEventListener("click", function (event) {
        const actionButton = event.target.closest("[data-task-action]");
        const addButton = event.target.closest("[data-add-task]");
        const menuBtn = event.target.closest(".kanban-column-menu");

        if (!event.target.closest("[data-kanban-action-menu]") && !menuBtn) {
            closeActionMenu();
        }

        if (menuBtn) {
            const cardParent = menuBtn.closest("[data-task-card]");

            if (cardParent) {
                event.preventDefault();
                event.stopPropagation();
                openActionMenu(menuBtn, cardParent.dataset.taskId);
                return;
            }
        }

        if (actionButton) {
            event.preventDefault();
            event.stopPropagation();

            const action = actionButton.dataset.taskAction;
            const taskId = actionButton.dataset.taskId;

            handleTaskAction(action, taskId).catch((error) => {
                if (window.CAHToast) {
                    CAHToast.error("Không thể xử lý task", error.message || "API chưa xử lý được yêu cầu.");
                }
            });
            return;
        }

        if (addButton) {
            openTaskModal("create");
            return;
        }

        const card = event.target.closest("[data-task-card]");

        if (card && !event.target.closest("button")) {
            const taskId = card.dataset.taskId;
            const task = findTaskById(taskId);

            openTaskModal("view", task || {
                id: taskId,
                title: card.dataset.title || "",
                description: card.dataset.description || "",
                project_id: card.dataset.projectId || "",
                assignee_id: card.dataset.assigneeId || "",
                watcher_id: card.datasetWatcherId || "",
                status: getStatusByColumn(card.dataset.status || "todo")
            });
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeActionMenu();
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-task-form]");
        if (!form) return;

        event.preventDefault();

        const mode = form.dataset.taskFormMode;
        const title = form.querySelector("[name='title']");
        const deadline = form.querySelector("[name='deadline']");

        if (!title?.value.trim() || !deadline?.value.trim()) {
            if (window.CAHToast) {
                CAHToast.error("Thiếu thông tin", "Vui lòng nhập tên công việc và deadline.");
            }
            return;
        }

        const handler = mode === "edit" ? updateTaskFromForm : createTaskFromForm;

        handler(form).catch((error) => {
            if (window.CAHToast) {
                CAHToast.error("Không thể lưu task", error.message || "API chưa xử lý được yêu cầu.");
            }
        });
    });

    updateColumnCounts();
    loadTasksFromApi();
})();