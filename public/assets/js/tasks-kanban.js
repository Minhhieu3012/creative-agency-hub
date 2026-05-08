(function () {
    "use strict";

    const board = document.querySelector("[data-kanban-board]");
    if (!board) return;

    // Hỗ trợ cả tên cột mới và cũ phòng trường hợp DB của bạn dùng tên khác
    const STATUS_TO_COLUMN = {
        "to do": "todo",
        "todo": "todo",
        "cần làm": "todo",
        "task mới": "todo", 

        "doing": "doing",
        "in_progress": "doing",
        "đang thực hiện": "doing",
        "cần sửa": "doing", 

        "review": "review",
        "đang kiểm tra": "review",
        "chờ duyệt": "review",

        "done": "done",
        "completed": "done",
        "hoàn thành": "done"
    };

    const COLUMN_TO_STATUS = {
        todo: "To do",
        doing: "Doing",
        review: "Review",
        done: "Done"
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
    let isFilterUpcoming = false; // BIẾN TRẠNG THÁI NÚT KÍNH LÚP

    injectActionStyles();

    function escapeHtml(value) {
        if (window.CAHApp && typeof window.CAHApp.escapeHtml === "function") {
            return window.CAHApp.escapeHtml(value);
        }
        return String(value || "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
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
            return { id: tokenData.id, role: tokenData.role, email: tokenData.email };
        }
        if (window.CAH_CURRENT_USER) return window.CAH_CURRENT_USER;
        if (window.CAHAuth && typeof window.CAHAuth.getUser === "function") return window.CAHAuth.getUser();
        return {};
    }

    function getCurrentUserId() {
        const user = getCurrentUser();
        return toNumberOrNull(user.id) || toNumberOrNull(user.employee_id) || DEFAULT_WATCHER_ID;
    }

    function getCurrentRole() {
        return String(getCurrentUser().role || "").toLowerCase();
    }

    function isManagerLike() {
        const role = getCurrentRole();
        return role === "admin" || role === "manager";
    }

    function isEmployee() {
        return getCurrentRole() === "employee";
    }

    function getFallbackAssigneeId() {
        if (isEmployee()) return getCurrentUserId();
        return DEFAULT_ASSIGNEE_ID;
    }

    function getAuthHeaders() {
        const token = getToken();
        return token ? { Authorization: "Bearer " + token } : {};
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

    function getTaskProgress(status) {
        const column = getColumnByStatus(status);
        return { todo: 10, doing: 55, review: 82, done: 100 }[column] || 10;
    }

    function getInitials(task) {
        const value = task.assignee_name || task.assignee || task.assignee_id || task.assigner_id || "CA";
        const str = String(value).trim();
        if (/^\d+$/.test(str)) return "#" + str;
        const words = str.split(/\s+/).filter(Boolean);
        if (words.length >= 2) return (words[0][0] + words[words.length - 1][0]).toUpperCase();
        return str.slice(0, 2).toUpperCase();
    }

    function normalizeTask(task) {
        const normalizedStatus = normalizeStatus(task && task.status);
        return {
            id: task && task.id,
            title: task && task.title ? task.title : "Chưa có tiêu đề",
            description: task && task.description ? task.description : "Chưa có mô tả.",
            status: normalizedStatus,
            priority: normalizePriority(task && task.priority),
            deadline: task && task.deadline ? task.deadline : "",
            assignee_id: task && task.assignee_id ? task.assignee_id : "",
            assignee_name: task && task.assignee_name ? task.assignee_name : "",
            assigner_id: task && task.assigner_id ? task.assigner_id : "",
            assigner_name: task && task.assigner_name ? task.assigner_name : "",
            watcher_id: task && task.watcher_id ? task.watcher_id : "",
            watcher_name: task && task.watcher_name ? task.watcher_name : "",
            project_id: task && task.project_id ? task.project_id : "",
            project_name: task && task.project_name ? task.project_name : ""
        };
    }

    function showToast(type, title, message) {
        if (!window.CAHToast) {
            if (type === "error") alert(message || title);
            return;
        }
        if (type === "success" && typeof window.CAHToast.success === "function") return window.CAHToast.success(title, message);
        if (type === "error" && typeof window.CAHToast.error === "function") return window.CAHToast.error(title, message);
        if (type === "info" && typeof window.CAHToast.info === "function") window.CAHToast.info(title, message);
    }

    function updateColumnCounts() {
        document.querySelectorAll("[data-kanban-column]").forEach(function (column) {
            const count = column.querySelectorAll("[data-task-card]").length;
            const countEl = column.querySelector("[data-column-count]");
            if (countEl) countEl.textContent = count;
        });
    }

    function renderTaskCard(rawTask) {
        const task = normalizeTask(rawTask);
        const columnKey = getColumnByStatus(task.status);
        const priorityTone = PRIORITY_TONE[task.priority] || "primary";
        const progress = getTaskProgress(task.status);
        
        let deadlineText = task.deadline ? "Deadline: " + escapeHtml(task.deadline) : "Chưa có deadline";
        let deadlineStyle = "";

        // TÔ ĐỎ NẾU TRỄ HẠN / SẮP HẾT HẠN
        if (task.deadline && columnKey !== "done") {
            const today = new Date();
            today.setHours(0,0,0,0);
            const dDate = new Date(task.deadline);
            if (dDate < today) {
                deadlineStyle = "color: #dc2626; font-weight: bold;";
                deadlineText = "Trễ hạn: " + escapeHtml(task.deadline);
            } else if (dDate.getTime() === today.getTime()) {
                deadlineStyle = "color: #d97706; font-weight: bold;";
                deadlineText = "Hôm nay: " + escapeHtml(task.deadline);
            }
        }

        const initials = escapeHtml(getInitials(task));
        const doneClass = columnKey === "done" ? " is-completed" : "";

        return `
            <article
                class="task-card${doneClass}"
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
                    <span class="badge badge-${escapeHtml(priorityTone)}">${escapeHtml(String(task.priority).toUpperCase())}</span>
                    <button class="kanban-column-menu" type="button" aria-label="Mở menu task">⋮</button>
                </div>
                <h3 class="task-card-title">${escapeHtml(task.title)}</h3>
                <p class="task-card-desc">${escapeHtml(task.description)}</p>
                <div class="task-card-progress">
                    <div class="progress-line"><div class="progress-line-fill" style="width: ${progress}%;"></div></div>
                    <small>${progress}% hoàn thành</small>
                </div>
                <div class="task-assignee-row">
                    <div class="task-assignee">
                        <span class="task-avatar">${initials}</span>
                        <span style="${deadlineStyle}">${deadlineText}</span>
                    </div>
                    <div class="task-card-meta-group">
                        ${columnKey === "done" ? "<span>✓ Done</span>" : "<span>▣ 0</span><span>□ 0</span>"}
                    </div>
                </div>
            </article>
        `;
    }

    function clearBoard() {
        document.querySelectorAll("[data-kanban-list]").forEach(function (list) {
            list.innerHTML = "";
        });
    }

    function renderTasks(tasks) {
        clearBoard();
        latestTasks = Array.isArray(tasks) ? tasks.map(normalizeTask) : [];
        let displayTasks = latestTasks;

        if (isFilterUpcoming) {
            const today = new Date();
            today.setHours(0,0,0,0);
            const limitDate = new Date(today);
            limitDate.setDate(today.getDate() + 3);

            displayTasks = latestTasks.filter(task => {
                const columnKey = getColumnByStatus(task.status);
                if (columnKey === "done") return false; 
                if (!task.deadline) return false;       
                
                const dDate = new Date(task.deadline);
                return dDate <= limitDate;
            });
        }

        if (displayTasks.length === 0) {
            const todoList = document.querySelector('[data-kanban-column][data-status="todo"] [data-kanban-list]');
            if (todoList) {
                todoList.innerHTML = `
                    <div class="ui-empty-state" style="min-height: 220px;">
                        <div class="ui-empty-icon">${isFilterUpcoming ? '🎉' : '☑'}</div>
                        <div class="ui-empty-content">
                            <h3>${isFilterUpcoming ? 'Thảnh thơi!' : 'Chưa có task'}</h3>
                            <p>${isFilterUpcoming ? 'Tuyệt vời, bạn không có deadline nào bị trễ hoặc sắp đến hạn.' : 'Tạo task mới hoặc chọn dự án khác trên bộ lọc.'}</p>
                        </div>
                    </div>
                `;
            }
            updateColumnCounts();
            return;
        }

        displayTasks.forEach(function (task) {
            const columnKey = getColumnByStatus(task.status);
            const list = document.querySelector(`[data-kanban-column][data-status="${columnKey}"] [data-kanban-list]`);
            if (list) {
                list.insertAdjacentHTML("beforeend", renderTaskCard(task));
            }
        });

        updateColumnCounts();
    }

    function findTaskById(taskId) {
        return latestTasks.find(function (task) { return String(task.id) === String(taskId); });
    }

    function appendTaskToBoard(task) {
        const normalized = normalizeTask(task);
        const columnKey = getColumnByStatus(normalized.status);
        const list = document.querySelector(`[data-kanban-column][data-status="${columnKey}"] [data-kanban-list]`);
        if (!list) return;

        const emptyState = list.querySelector(".ui-empty-state");
        if (emptyState) emptyState.remove();

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
            let apiUrl = "/api/tasks";
            const params = new URLSearchParams();

            // Lấy project_id từ URL nếu có (trường hợp click từ trang khác sang)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('project_id')) params.append("project_id", urlParams.get('project_id'));

            const queryString = params.toString();
            if (queryString) apiUrl += `?${queryString}`;

            const response = await window.CAHApi.get(apiUrl, {
                loading: true,
                loadingMessage: "Đang tải dữ liệu Kanban...",
                headers: getAuthHeaders()
            });

            renderTasks(response.data || []);
        } catch (error) {
            updateColumnCounts();
            showToast("info", "Thông báo", "Lỗi tải danh sách công việc.");
        }
    }

    function taskFormBody(mode, taskData) {
        const isEdit = mode === "edit";
        const task = taskData ? normalizeTask(taskData) : null;
        const selectedProjectId = getSelectedProjectId();
        const fallbackAssigneeId = getFallbackAssigneeId();
        const fallbackWatcherId = getCurrentUserId();
        const canEdit = isManagerLike();

        return `
            <form class="task-modal-form" data-task-form data-task-form-mode="${escapeHtml(mode)}" data-task-id="${escapeHtml(task && task.id ? task.id : "")}">
                <div class="form-group"><label class="form-label">Tên công việc</label><input class="form-control" type="text" name="title" value="${escapeHtml(task && task.title ? task.title : "")}" ${canEdit ? "required" : "readonly"}></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Trạng thái</label><select class="form-select" name="status" ${canEdit ? "" : "disabled"}><option value="To do" ${task && task.status === "To do" ? "selected" : ""}>Cần làm</option><option value="Doing" ${task && task.status === "Doing" ? "selected" : ""}>Đang thực hiện</option><option value="Review" ${task && task.status === "Review" ? "selected" : ""}>Đang kiểm tra</option><option value="Done" ${task && task.status === "Done" ? "selected" : ""}>Hoàn thành</option></select></div>
                    <div class="form-group"><label class="form-label">Độ ưu tiên</label><select class="form-select" name="priority" ${canEdit ? "" : "disabled"}><option value="Low" ${task && task.priority === "Low" ? "selected" : ""}>Thấp</option><option value="Medium" ${!task || task.priority === "Medium" ? "selected" : ""}>Trung bình</option><option value="High" ${task && task.priority === "High" ? "selected" : ""}>Cao</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Deadline</label><input class="form-control" type="date" name="deadline" value="${escapeHtml(task && task.deadline ? task.deadline : "")}" ${canEdit ? "required" : "readonly"}></div>
                    <div class="form-group"><label class="form-label">Project ID</label><input class="form-control" type="number" name="project_id" value="${escapeHtml(task && task.project_id ? task.project_id : selectedProjectId)}" min="1" ${canEdit ? "" : "readonly"}></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Assignee ID</label><input class="form-control" type="number" name="assignee_id" value="${escapeHtml(task && task.assignee_id ? task.assignee_id : fallbackAssigneeId)}" min="1" ${canEdit ? "" : "readonly"}></div>
                    <div class="form-group"><label class="form-label">Watcher ID</label><input class="form-control" type="number" name="watcher_id" value="${escapeHtml(task && task.watcher_id ? task.watcher_id : fallbackWatcherId)}" min="1" ${canEdit ? "" : "readonly"}></div>
                </div>
                <div class="form-group"><label class="form-label">Mô tả</label><textarea class="form-textarea" name="description" ${canEdit ? "" : "readonly"}>${escapeHtml(task && task.description ? task.description : "")}</textarea></div>
                <div class="task-modal-footer"><button class="btn btn-light" type="button" data-modal-close>Đóng</button>${canEdit ? '<button class="btn btn-primary" type="submit">' + (isEdit ? 'Lưu thay đổi' : 'Tạo task') + '</button>' : ''}</div>
            </form>
        `;
    }

    function openTaskModal(mode, taskData) {
        if (!window.CAHModal) return;
        window.CAHModal.open({
            title: mode === "create" ? "Tạo công việc mới" : "Chi tiết công việc",
            subtitle: "Task sẽ được lưu vào database và tự đồng bộ lại Kanban.",
            body: taskFormBody(mode, taskData)
        });
    }

    async function updateTaskStatusById(taskId, newStatus) {
        await window.CAHApi.patch(`/api/tasks/${taskId}/status`, { status: newStatus }, { loading: false, headers: getAuthHeaders() });
        showToast("success", "Đã cập nhật", `Task đã chuyển sang trạng thái ${newStatus}.`);
        await loadTasksFromApi();
    }

    async function updateTaskStatus(card, newColumnKey) {
        const taskId = card.dataset.taskId;
        const newStatus = getStatusByColumn(newColumnKey);

        if (!taskId || !window.CAHApi || !getToken()) return;

        try {
            await updateTaskStatusById(taskId, newStatus);
        } catch (error) {
            if (previousDropState && previousDropState.list && previousDropState.nextSibling !== undefined) {
                previousDropState.list.insertBefore(card, previousDropState.nextSibling);
                card.dataset.status = previousDropState.status;
                updateColumnCounts();
            }
            showToast("error", "Lỗi cập nhật", error.message);
        }
    }

    async function createTaskFromForm(form) {
        const data = Object.fromEntries(new FormData(form));
        const payload = {
            title: data.title, description: data.description || "", priority: data.priority || "Medium", deadline: data.deadline,
            project_id: toNumberOrNull(data.project_id) || getSelectedProjectId(), assignee_id: toNumberOrNull(data.assignee_id) || getFallbackAssigneeId(), watcher_id: toNumberOrNull(data.watcher_id) || getCurrentUserId()
        };
        await window.CAHApi.post("/api/tasks", payload, { loading: true, headers: getAuthHeaders() });
        if (window.CAHModal) window.CAHModal.close();
        showToast("success", "Tạo task thành công", "Task mới đã hiển thị trên Kanban.");
        await loadTasksFromApi();
    }

    async function updateTaskFromForm(form) {
        const taskId = form.dataset.taskId;
        const data = Object.fromEntries(new FormData(form));
        const payload = {
            title: data.title, description: data.description || "", priority: data.priority || "Medium", deadline: data.deadline,
            project_id: toNumberOrNull(data.project_id) || getSelectedProjectId(), assignee_id: toNumberOrNull(data.assignee_id) || null, watcher_id: toNumberOrNull(data.watcher_id) || null
        };
        await window.CAHApi.put(`/api/tasks/${taskId}`, payload, { loading: true, headers: getAuthHeaders() });
        if (data.status) await window.CAHApi.patch(`/api/tasks/${taskId}/status`, { status: data.status }, { loading: false, headers: getAuthHeaders() });
        if (window.CAHModal) window.CAHModal.close();
        showToast("success", "Đã cập nhật", "Task đã được cập nhật.");
        await loadTasksFromApi();
    }

    async function deleteTask(taskId) {
        const confirmed = window.confirm(`Xoá task này? Thao tác này không thể hoàn tác.`);
        if (!confirmed) return;
        await window.CAHApi.delete(`/api/tasks/${taskId}`, { loading: true, headers: getAuthHeaders() });
        if (window.CAHModal) window.CAHModal.close();
        await loadTasksFromApi();
    }

    async function approvalAction(taskId, action) {
        const endpointMap = { submit: `/api/tasks/${taskId}/submit`, approve: `/api/tasks/${taskId}/approve`, reject: `/api/tasks/${taskId}/reject` };
        if (!endpointMap[action]) return;
        await window.CAHApi.post(endpointMap[action], {}, { loading: true, headers: getAuthHeaders() });
        if (window.CAHModal) window.CAHModal.close();
        await loadTasksFromApi();
    }

    function openActionMenu(button, explicitTaskId) {
        closeActionMenu();
        const cardContainer = button.closest("[data-task-card]");
        const taskId = explicitTaskId || (cardContainer ? cardContainer.dataset.taskId : null);
        if (!taskId) return;

        let task = findTaskById(taskId) || { id: taskId, status: cardContainer?.dataset.status || "todo" };
        const menu = document.createElement("div");
        menu.className = "kanban-action-menu";
        menu.setAttribute("data-kanban-action-menu", "");

        const currentColumn = getColumnByStatus(task.status);
        const manager = isManagerLike();
        const employee = isEmployee();

        const actions = [`<button type="button" data-task-action="view" data-task-id="${escapeHtml(taskId)}">Xem chi tiết</button>`];
        if (manager) {
            actions.push(`<button type="button" data-task-action="edit" data-task-id="${escapeHtml(taskId)}">Chỉnh sửa</button>`);
            actions.push(`<button type="button" data-task-action="delete" data-task-id="${escapeHtml(taskId)}" class="danger">Xoá task</button>`);
        }
        if (currentColumn === "todo" && (employee || manager)) actions.push(`<button type="button" data-task-action="move-doing" data-task-id="${escapeHtml(taskId)}">Chuyển Đang thực hiện</button>`);
        if (currentColumn === "doing" && (employee || manager)) actions.push(`<button type="button" data-task-action="submit" data-task-id="${escapeHtml(taskId)}">Gửi duyệt</button>`);
        if (currentColumn === "review" && manager) {
            actions.push(`<button type="button" data-task-action="approve" data-task-id="${escapeHtml(taskId)}">Duyệt hoàn thành</button>`);
            actions.push(`<button type="button" data-task-action="reject" data-task-id="${escapeHtml(taskId)}">Từ chối</button>`);
        }

        menu.innerHTML = actions.join("");
        document.body.appendChild(menu);
        const rect = button.getBoundingClientRect();
        menu.style.top = `${rect.bottom + window.scrollY + 8}px`;
        menu.style.left = `${Math.max(12, rect.right + window.scrollX - 220)}px`;
    }

    function closeActionMenu() { document.querySelectorAll("[data-kanban-action-menu]").forEach(m => m.remove()); }

    async function handleTaskAction(action, taskId) {
        const task = findTaskById(taskId) || { id: taskId, title: "Công việc này" };
        closeActionMenu();
        if (action === "view" || action === "edit") return openTaskModal(action, task);
        if (action === "delete") return deleteTask(taskId);
        if (["submit", "approve", "reject"].includes(action)) return approvalAction(taskId, action);
        if (action === "move-doing") return updateTaskStatusById(taskId, "Doing");
    }

    function injectActionStyles() {
        if (document.querySelector("[data-kanban-action-style]")) return;
        const style = document.createElement("style");
        style.setAttribute("data-kanban-action-style", "");
        style.textContent = `
            .kanban-action-menu { position: absolute; z-index: 9999; width: 220px; display: grid; gap: 4px; padding: 8px; border-radius: 16px; border: 1px solid rgba(15, 23, 42, 0.1); background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18); }
            .kanban-action-menu button { min-height: 38px; display: flex; align-items: center; padding: 0 12px; border-radius: 10px; color: #22314d; background: transparent; font-weight: 750; border: none; cursor: pointer; }
            .kanban-action-menu button:hover { color: var(--primary); background: var(--mint); }
            .kanban-action-menu button.danger { color: var(--danger); }
            .kanban-action-menu button.danger:hover { background: var(--danger-soft); }
            .task-card.is-completed { opacity: .82; }
            .task-card.is-completed .task-card-title { text-decoration: line-through; color: #64748b; }
        `;
        document.head.appendChild(style);
    }

    document.addEventListener("dragstart", function (event) {
        const card = event.target.closest("[data-task-card]");
        if (!card) return;
        draggedCard = card;
        previousDropState = { list: card.parentElement, nextSibling: card.nextElementSibling, status: card.dataset.status };
        card.classList.add("is-dragging");
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData("text/plain", card.dataset.taskId || "");
    });

    document.addEventListener("dragend", function () {
        if (draggedCard) draggedCard.classList.remove("is-dragging");
        document.querySelectorAll(".kanban-column.is-over").forEach(c => c.classList.remove("is-over"));
        draggedCard = null;
        previousDropState = null;
        updateColumnCounts();
    });

    document.querySelectorAll("[data-kanban-column]").forEach(function (column) {
        column.addEventListener("dragover", function (event) { event.preventDefault(); column.classList.add("is-over"); });
        column.addEventListener("dragleave", function (event) { if (!column.contains(event.relatedTarget)) column.classList.remove("is-over"); });
        column.addEventListener("drop", function (event) {
            event.preventDefault();
            if (!draggedCard) return;
            const list = column.querySelector("[data-kanban-list]");
            if (!list) return;

            const newColumnKey = column.dataset.status || "todo";
            const oldStatus = draggedCard.dataset.status;
            const taskId = draggedCard.dataset.taskId;

            function revertDrop() {
                if (previousDropState && previousDropState.list && previousDropState.nextSibling !== undefined) {
                    previousDropState.list.insertBefore(draggedCard, previousDropState.nextSibling);
                    draggedCard.dataset.status = previousDropState.status;
                    updateColumnCounts();
                }
            }

            if (oldStatus === newColumnKey) { draggedCard.dataset.status = newColumnKey; column.classList.remove("is-over"); return; }

            if (isEmployee()) {
                const isValid = (oldStatus === "todo" && newColumnKey === "doing") || (oldStatus === "doing" && newColumnKey === "review");
                if (!isValid) { revertDrop(); column.classList.remove("is-over"); return showToast("error", "Sai luồng công việc", "Chỉ kéo: Cần làm -> Đang thực hiện -> Đang kiểm tra."); }
            }

            if (newColumnKey === "done" && oldStatus !== "done") {
                if (oldStatus === "review" && isManagerLike()) {
                    list.appendChild(draggedCard); draggedCard.dataset.status = newColumnKey; column.classList.remove("is-over"); updateColumnCounts();
                    return approvalAction(taskId, "approve").catch(e => { revertDrop(); showToast("error", "Lỗi", e.message); });
                }
                revertDrop(); column.classList.remove("is-over"); return showToast("error", "Lỗi", "Task phải ở Review và được Manager duyệt.");
            }

            if (newColumnKey === "review" && oldStatus === "doing") {
                list.appendChild(draggedCard); draggedCard.dataset.status = newColumnKey; column.classList.remove("is-over"); updateColumnCounts();
                return approvalAction(taskId, "submit").catch(e => { revertDrop(); showToast("error", "Lỗi", e.message); });
            }

            if (newColumnKey === "doing" && oldStatus === "review" && isManagerLike()) {
                list.appendChild(draggedCard); draggedCard.dataset.status = newColumnKey; column.classList.remove("is-over"); updateColumnCounts();
                return approvalAction(taskId, "reject").catch(e => { revertDrop(); showToast("error", "Lỗi", e.message); });
            }

            list.appendChild(draggedCard);
            draggedCard.dataset.status = newColumnKey;
            column.classList.remove("is-over");
            updateColumnCounts();
            updateTaskStatus(draggedCard, newColumnKey);
        });
    });

    document.addEventListener("click", function (event) {
        const actionButton = event.target.closest("[data-task-action]");
        const addButton = event.target.closest("[data-add-task]");
        const menuButton = event.target.closest(".kanban-column-menu");

        if (!event.target.closest("[data-kanban-action-menu]") && !menuButton) closeActionMenu();
        if (menuButton) {
            const cardParent = menuButton.closest("[data-task-card]");
            if (cardParent) { event.preventDefault(); event.stopPropagation(); return openActionMenu(menuButton, cardParent.dataset.taskId); }
        }
        if (actionButton) {
            event.preventDefault(); event.stopPropagation();
            return handleTaskAction(actionButton.dataset.taskAction, actionButton.dataset.taskId).catch(e => showToast("error", "Lỗi", e.message));
        }
        if (addButton) return openTaskModal("create");

        const card = event.target.closest("[data-task-card]");
        if (card && !event.target.closest("button")) {
            openTaskModal("view", findTaskById(card.dataset.taskId) || { id: card.dataset.taskId, status: getStatusByColumn(card.dataset.status) });
        }
    });

    document.addEventListener("keydown", e => { if (e.key === "Escape") closeActionMenu(); });
    document.addEventListener("submit", e => {
        const form = e.target.closest("[data-task-form]");
        if (form) { e.preventDefault(); (form.dataset.taskFormMode === "edit" ? updateTaskFromForm : createTaskFromForm)(form); }
    });

    const btnFilter = document.getElementById("js-btn-filter");
    if (btnFilter) btnFilter.addEventListener("click", loadTasksFromApi);

    const btnUpcoming = document.getElementById("js-btn-upcoming");
    if (btnUpcoming) {
        btnUpcoming.addEventListener("click", function() {
            isFilterUpcoming = !isFilterUpcoming;
            if (isFilterUpcoming) {
                btnUpcoming.classList.replace("btn-warning", "btn-danger");
                btnUpcoming.innerText = "⏳ Đang soi Deadline";
            } else {
                btnUpcoming.classList.replace("btn-danger", "btn-warning");
                btnUpcoming.innerHTML = `<span style="font-size: 1.1em;">🔎</span> Soi Deadline (3 ngày)`;
            }
            renderTasks(latestTasks); // Lọc ngay trên RAM
        });
    }

    updateColumnCounts();
    // Bỏ gọi hàm loadFilterData() để tránh việc gọi API project/nhân viên
    loadTasksFromApi();
})();