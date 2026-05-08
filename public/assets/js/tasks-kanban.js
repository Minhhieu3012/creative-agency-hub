(function () {
    "use strict";

    const API_ROOT = window.CAH_CONFIG?.apiRoot || "/creative-agency-hub/public";

    const API_STATUS_LIST = ["To do", "Doing", "Review", "Done"];
    const BOARD_COLUMN_LIST = ["Revision", "Task", "Review", "Done"];

    const STATUS_META = {
        Revision: {
            label: "Cần sửa",
            tone: "revision"
        },
        Task: {
            label: "Task mới",
            tone: "task"
        },
        Review: {
            label: "Chờ Duyệt",
            tone: "review"
        },
        Done: {
            label: "Hoàn Thành",
            tone: "done"
        }
    };

    const state = {
        tasksByStatus: createEmptyBoardState(),
        currentUser: null,
        draggedTaskId: null,
        isLoading: false,
        taskOptions: null,
        activeModal: null
    };

    function createEmptyBoardState() {
        return {
            Revision: [],
            Task: [],
            Review: [],
            Done: []
        };
    }

    function getToken() {
        return localStorage.getItem("cah_auth_token") || localStorage.getItem("cah_token") || "";
    }

    function qs(selector, scope = document) {
        return scope.querySelector(selector);
    }

    function qsa(selector, scope = document) {
        return Array.from(scope.querySelectorAll(selector));
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === "function") {
            return window.CSS.escape(String(value));
        }

        return String(value).replace(/"/g, '\\"');
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function normalizeApiStatus(status) {
        const value = String(status || "").trim();

        return API_STATUS_LIST.includes(value) ? value : "To do";
    }

    function normalizeBoardColumn(column) {
        const value = String(column || "").trim();

        return BOARD_COLUMN_LIST.includes(value) ? value : "Task";
    }

    function hasRejectReason(task) {
        return String(task?.reject_reason || "").trim() !== "";
    }

    function boardColumnForTask(task) {
        const status = normalizeApiStatus(task?.status);

        if (status === "Doing" && hasRejectReason(task)) {
            return "Revision";
        }

        if (status === "Review") {
            return "Review";
        }

        if (status === "Done") {
            return "Done";
        }

        return "Task";
    }

    function roleOfCurrentUser() {
        return String(state.currentUser?.role || window.CAH_CURRENT_USER?.role || "").toLowerCase();
    }

    function currentUserId() {
        return Number(state.currentUser?.id || window.CAH_CURRENT_USER?.id || 0);
    }

    function isManager() {
        return roleOfCurrentUser() === "manager";
    }

    function cannotCompleteMessage() {
        return "Bạn không có quyền hoàn thành thao tác này.";
    }

    function invalidFlowMessage() {
        return "Trạng thái này không hợp lệ với luồng hiện tại.";
    }

    function managerReviewOnlyMessage() {
        return "Manager chỉ xử lý task ở cột Chờ Duyệt: trả về Cần sửa hoặc duyệt Hoàn Thành.";
    }

    function canDragTask(task) {
        const role = roleOfCurrentUser();
        const boardColumn = boardColumnForTask(task);

        if (boardColumn === "Done") {
            return false;
        }

        if (role === "manager") {
            return boardColumn === "Review";
        }

        if (role === "employee") {
            return Number(task.assignee_id || 0) === currentUserId()
                && (boardColumn === "Task" || boardColumn === "Revision");
        }

        return false;
    }

    function canDropToColumn(task, nextColumn) {
        const role = roleOfCurrentUser();
        const currentColumn = boardColumnForTask(task);
        const targetColumn = normalizeBoardColumn(nextColumn);

        if (currentColumn === "Done") {
            return false;
        }

        if (role === "manager") {
            if (currentColumn !== "Review") {
                return false;
            }

            return targetColumn === "Revision" || targetColumn === "Done";
        }

        if (role === "employee") {
            if (targetColumn === "Done") {
                return false;
            }

            if (targetColumn === "Revision") {
                return false;
            }

            if (targetColumn === "Task") {
                return false;
            }

            return (currentColumn === "Task" || currentColumn === "Revision") && targetColumn === "Review";
        }

        return false;
    }

    async function apiRequest(path, options = {}) {
        const headers = {
            Accept: "application/json",
            ...(options.headers || {})
        };

        const token = getToken();

        if (token) {
            headers.Authorization = "Bearer " + token;
        }

        const response = await fetch(API_ROOT + path, {
            credentials: "same-origin",
            ...options,
            headers
        });

        const payload = await response.json().catch(() => ({
            status: "error",
            message: "Server không trả JSON hợp lệ."
        }));

        if (!response.ok || payload.status === "error") {
            throw new Error(payload.message || "Yêu cầu không thành công.");
        }

        return payload;
    }

    function toast(type, title, message) {
        if (window.CAHToast && typeof window.CAHToast[type] === "function") {
            window.CAHToast[type](title, message);
            return;
        }

        if (type === "error") {
            console.error(title, message);
            return;
        }

        console.log(title, message);
    }

    function showBoardMessage(message, isError = false) {
        const holder = qs("#js-board-message");

        if (!holder) {
            return;
        }

        holder.style.display = "block";
        holder.style.backgroundColor = isError ? "#fef2f2" : "#ecfdf5";
        holder.style.color = isError ? "#b91c1c" : "#047857";
        holder.innerHTML = message;
    }

    function hideBoardMessage() {
        const holder = qs("#js-board-message");

        if (holder) {
            holder.style.display = "none";
            holder.innerHTML = "";
        }
    }

    function findBoard() {
        return qs("[data-kanban-board]")
            || qs(".kanban-board")
            || qs(".kanban-columns")
            || qs(".kanban-wrapper");
    }

    function findColumn(columnKey) {
        return qs(`[data-kanban-status="${cssEscape(columnKey)}"]`);
    }

    function findColumnList(column) {
        if (!column) {
            return null;
        }

        return qs("[data-kanban-list]", column)
            || qs(".kanban-list", column)
            || qs(".kanban-card-list", column)
            || qs(".kanban-cards", column)
            || qs(".task-list", column)
            || qs(".kanban-column-body", column)
            || column;
    }

    function removeOldColumns(board) {
        qsa(".kanban-column, .kanban-lane, .task-column", board).forEach(function (column) {
            column.remove();
        });
    }

    function createColumn(columnKey) {
        const meta = STATUS_META[columnKey];

        const column = document.createElement("section");
        column.className = `kanban-column kanban-column-${meta.tone}`;
        column.setAttribute("data-kanban-column", "");
        column.setAttribute("data-kanban-status", columnKey);

        column.innerHTML = `
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot ${escapeHtml(meta.tone)}"></span>
                    <span>${escapeHtml(meta.label)}</span>
                    <span class="kanban-count" data-kanban-count="${escapeHtml(columnKey)}">0</span>
                </div>
            </header>
            <div class="kanban-card-list" data-kanban-list></div>
        `;

        return column;
    }

    function ensureBoardSkeleton() {
        let board = findBoard();

        if (!board) {
            const content = qs(".app-content") || qs("main") || document.body;

            board = document.createElement("section");
            board.className = "kanban-board";
            board.setAttribute("data-kanban-board", "");

            content.appendChild(board);
        }

        if (board.dataset.cahFourColumnKanbanReady !== "true") {
            removeOldColumns(board);

            BOARD_COLUMN_LIST.forEach(function (columnKey) {
                board.appendChild(createColumn(columnKey));
            });

            board.dataset.cahFourColumnKanbanReady = "true";
        }

        BOARD_COLUMN_LIST.forEach(function (columnKey) {
            const meta = STATUS_META[columnKey];
            let column = findColumn(columnKey);

            if (!column) {
                column = createColumn(columnKey);
                board.appendChild(column);
            }

            column.classList.add(`kanban-column-${meta.tone}`);

            const title = qs(".kanban-column-title span:nth-child(2), .kanban-column-header h2, h2, h3", column);

            if (title) {
                title.textContent = meta.label;
            }

            let count = qs("[data-kanban-count]", column);

            if (!count) {
                count = document.createElement("span");
                count.className = "kanban-count";
                count.setAttribute("data-kanban-count", columnKey);

                const header = qs(".kanban-column-title", column) || qs(".kanban-column-head", column) || column;
                header.appendChild(count);
            }

            let list = findColumnList(column);

            if (!list || list === column) {
                list = document.createElement("div");
                list.className = "kanban-card-list";
                list.setAttribute("data-kanban-list", "");
                column.appendChild(list);
            }
        });

        return board;
    }

    function setLoading(isLoading) {
        state.isLoading = isLoading;

        const board = findBoard();

        if (board) {
            board.classList.toggle("is-loading", isLoading);
        }
    }

    function renderEmpty(list, columnKey) {
        const label = STATUS_META[columnKey]?.label || columnKey;

        list.innerHTML = `
            <div class="kanban-empty-state" data-kanban-empty style="text-align:center;color:#94a3b8;padding:22px;">
                <span>∅</span>
                <p>Chưa có task ở cột ${escapeHtml(label)}.</p>
            </div>
        `;
    }

    function formatDate(date) {
        if (!date) {
            return "Chưa đặt";
        }

        const parsed = new Date(date + "T00:00:00");

        if (Number.isNaN(parsed.getTime())) {
            return date;
        }

        return parsed.toLocaleDateString("vi-VN");
    }

    function priorityClass(priority) {
        const value = String(priority || "").toLowerCase();

        if (value === "high") {
            return "badge-danger";
        }

        if (value === "low") {
            return "badge-info";
        }

        return "badge-warning";
    }

    function priorityLabel(priority) {
        const value = String(priority || "Medium");

        const labels = {
            Low: "Thấp",
            Medium: "Trung bình",
            High: "Cao"
        };

        return labels[value] || value;
    }

    function publicBadge(task) {
        if (Number(task.is_client_visible || 0) !== 1) {
            return "";
        }

        return `<span class="badge badge-info">Public Client</span>`;
    }

    function revisionBadge(task) {
        if (boardColumnForTask(task) !== "Revision") {
            return "";
        }

        return `<span class="badge badge-danger">Cần sửa</span>`;
    }

    function rejectReasonBlock(task) {
        if (boardColumnForTask(task) !== "Revision") {
            return "";
        }

        return `
            <div class="kanban-task-note" style="margin-top:10px;padding:10px;border-radius:12px;background:#fef2f2;color:#991b1b;">
                <strong>Lý do cần sửa:</strong>
                <span>${escapeHtml(task.reject_reason)}</span>
            </div>
        `;
    }

    function renderCard(task) {
        const card = document.createElement("article");
        const draggable = canDragTask(task);
        const boardColumn = boardColumnForTask(task);

        card.className = "task-card";
        card.setAttribute("data-task-card", "");
        card.setAttribute("data-task-id", String(task.id));
        card.setAttribute("data-task-status", normalizeApiStatus(task.status));
        card.setAttribute("data-board-column", boardColumn);
        card.draggable = draggable;

        if (!draggable) {
            card.classList.add("is-readonly");
        }

        if (boardColumn === "Revision") {
            card.classList.add("is-revision-task");
        }

        if (boardColumn === "Done") {
            card.classList.add("is-done-task");
        }

        const overdue = task.is_overdue ? `<span class="badge badge-danger">Quá hạn</span>` : "";
        const assigneeName = task.assignee_name || "Chưa giao";
        const projectName = task.project_name || "Không có project";
        const managerActions = isManager()
            ? `
                <button class="btn btn-light btn-sm" type="button" data-task-edit="${escapeHtml(task.id)}">Sửa</button>
                <button class="btn btn-danger btn-sm" type="button" data-task-delete="${escapeHtml(task.id)}">Xoá</button>
            `
            : "";

        card.innerHTML = `
            <div class="task-card-top">
                <span class="badge ${priorityClass(task.priority)}">
                    ${escapeHtml(priorityLabel(task.priority))}
                </span>
                <span class="badge badge-info">${escapeHtml(STATUS_META[boardColumn]?.label || boardColumn)}</span>
            </div>

            <span class="kanban-project" style="display:block;margin-top:10px;color:#64748b;font-weight:700;font-size:12px;">
                ${escapeHtml(projectName)}
            </span>

            <h3 class="task-card-title">${escapeHtml(task.title)}</h3>

            ${task.description ? `<p class="task-card-desc">${escapeHtml(task.description)}</p>` : ""}

            ${rejectReasonBlock(task)}

            <div class="task-assignee-row">
                <div class="task-assignee">
                    <span>Người phụ trách: ${escapeHtml(assigneeName)}</span>
                </div>
                <div class="task-card-meta-group">
                    <span>Deadline: ${escapeHtml(formatDate(task.deadline))}</span>
                </div>
            </div>

            <div class="task-card-meta-group" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                ${overdue}
                ${revisionBadge(task)}
                ${publicBadge(task)}
            </div>

            <div class="kanban-task-actions" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
                ${renderWorkflowButtons(task)}
                ${managerActions}
            </div>
        `;

        card.addEventListener("dragstart", function (event) {
            if (!canDragTask(task)) {
                event.preventDefault();
                return;
            }

            state.draggedTaskId = Number(task.id);
            card.classList.add("is-dragging");

            event.dataTransfer.effectAllowed = "move";
            event.dataTransfer.setData("text/plain", String(task.id));
        });

        card.addEventListener("dragend", function () {
            state.draggedTaskId = null;
            card.classList.remove("is-dragging");
        });

        bindCardActions(card, task);

        return card;
    }

    function renderWorkflowButtons(task) {
        const role = roleOfCurrentUser();
        const apiStatus = normalizeApiStatus(task.status);
        const boardColumn = boardColumnForTask(task);

        if (boardColumn === "Done") {
            return "";
        }

        if (role === "employee") {
            if (boardColumn === "Task" || boardColumn === "Revision") {
                return `<button class="btn btn-soft btn-sm" type="button" data-task-submit-review>Gửi Chờ Duyệt</button>`;
            }

            return "";
        }

        if (role === "manager") {
            if (apiStatus === "Review") {
                return `
                    <button class="btn btn-emerald btn-sm" type="button" data-task-approve>Approve</button>
                    <button class="btn btn-light btn-sm" type="button" data-task-reject>Reject</button>
                `;
            }

            return "";
        }

        return "";
    }

    function bindCardActions(card, task) {
        const submitButton = qs("[data-task-submit-review]", card);

        if (submitButton) {
            submitButton.addEventListener("click", async function () {
                await moveTaskToReview(Number(task.id));
            });
        }

        const approveButton = qs("[data-task-approve]", card);

        if (approveButton) {
            approveButton.addEventListener("click", async function () {
                await approveTask(Number(task.id));
            });
        }

        const rejectButton = qs("[data-task-reject]", card);

        if (rejectButton) {
            rejectButton.addEventListener("click", async function () {
                const reason = window.prompt("Nhập lý do cần sửa task:", "Cần chỉnh sửa thêm trước khi duyệt.");

                if (reason === null) {
                    return;
                }

                await rejectTask(Number(task.id), reason);
            });
        }

        const editButton = qs("[data-task-edit]", card);

        if (editButton) {
            editButton.addEventListener("click", async function () {
                await openTaskModal(task);
            });
        }

        const deleteButton = qs("[data-task-delete]", card);

        if (deleteButton) {
            deleteButton.addEventListener("click", async function () {
                await deleteTask(Number(task.id), task.title);
            });
        }
    }

    function renderBoard() {
        ensureBoardSkeleton();

        BOARD_COLUMN_LIST.forEach(function (columnKey) {
            const column = findColumn(columnKey);
            const list = findColumnList(column);
            const tasks = state.tasksByStatus[columnKey] || [];

            if (!list) {
                return;
            }

            list.innerHTML = "";

            const countEls = qsa(`[data-kanban-count="${cssEscape(columnKey)}"]`, column || document);

            countEls.forEach(function (el) {
                el.textContent = String(tasks.length);
            });

            if (tasks.length === 0) {
                renderEmpty(list, columnKey);
                return;
            }

            tasks.forEach(function (task) {
                list.appendChild(renderCard(task));
            });
        });

        bindDropZones();
    }

    function bindDropZones() {
        BOARD_COLUMN_LIST.forEach(function (columnKey) {
            const column = findColumn(columnKey);
            const list = findColumnList(column);

            if (!list || list.dataset.dropReady === "true") {
                return;
            }

            list.dataset.dropReady = "true";

            list.addEventListener("dragover", function (event) {
                event.preventDefault();
                event.stopPropagation();
                list.classList.add("is-drag-over");
            });

            list.addEventListener("dragleave", function () {
                list.classList.remove("is-drag-over");
            });

            list.addEventListener("drop", async function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (typeof event.stopImmediatePropagation === "function") {
                    event.stopImmediatePropagation();
                }

                list.classList.remove("is-drag-over");

                const taskId = Number(event.dataTransfer.getData("text/plain") || state.draggedTaskId || 0);

                if (!taskId) {
                    await loadTasks();
                    return;
                }

                const task = findTaskById(taskId);

                if (!task) {
                    await loadTasks();
                    return;
                }

                const currentColumn = boardColumnForTask(task);
                const targetColumn = normalizeBoardColumn(columnKey);

                if (currentColumn === "Done") {
                    state.draggedTaskId = null;
                    await loadTasks();
                    return;
                }

                if (!canDropToColumn(task, targetColumn)) {
                    if (targetColumn === "Done" && roleOfCurrentUser() === "employee") {
                        toast("error", "Không thể xử lý", cannotCompleteMessage());
                    } else if (roleOfCurrentUser() === "manager") {
                        toast("error", "Không thể xử lý", managerReviewOnlyMessage());
                    } else {
                        toast("error", "Không thể xử lý", invalidFlowMessage());
                    }

                    state.draggedTaskId = null;
                    await loadTasks();
                    return;
                }

                if (targetColumn === "Revision") {
                    const reason = window.prompt("Nhập lý do cần sửa task:", "Cần chỉnh sửa thêm trước khi duyệt.");

                    if (reason === null) {
                        await loadTasks();
                        return;
                    }

                    await rejectTask(taskId, reason);
                    return;
                }

                if (targetColumn === "Review") {
                    await moveTaskToReview(taskId);
                    return;
                }

                if (targetColumn === "Done") {
                    await approveTask(taskId);
                    return;
                }

                await loadTasks();
            });
        });
    }

    function findTaskById(taskId) {
        for (const columnKey of BOARD_COLUMN_LIST) {
            const found = (state.tasksByStatus[columnKey] || []).find(function (task) {
                return Number(task.id) === Number(taskId);
            });

            if (found) {
                return found;
            }
        }

        return null;
    }

    function normalizeGroupedData(data) {
        const result = createEmptyBoardState();

        if (Array.isArray(data)) {
            data.forEach(function (task) {
                const columnKey = boardColumnForTask(task);
                result[columnKey].push(task);
            });

            return result;
        }

        API_STATUS_LIST.forEach(function (status) {
            const list = Array.isArray(data?.[status]) ? data[status] : [];

            list.forEach(function (task) {
                const columnKey = boardColumnForTask(task);
                result[columnKey].push(task);
            });
        });

        return result;
    }

    async function loadCurrentUser() {
        try {
            const payload = await apiRequest("/api/auth/me");
            state.currentUser = payload?.data?.user || payload?.data || window.CAH_CURRENT_USER || null;
        } catch (error) {
            state.currentUser = window.CAH_CURRENT_USER || null;
        }

        qsa("[data-add-task]").forEach(function (button) {
            button.style.display = isManager() ? "inline-flex" : "none";
        });
    }

    function getCurrentProjectId() {
        const url = new URL(window.location.href);
        const fromQuery = url.searchParams.get("project_id");

        if (fromQuery) {
            return fromQuery;
        }

        const holder = qs("[data-current-project-id]") || qs("[data-project-id]");

        if (holder) {
            return holder.getAttribute("data-current-project-id") || holder.getAttribute("data-project-id") || "";
        }

        return "";
    }

    async function loadTasks() {
        setLoading(true);
        hideBoardMessage();

        try {
            const projectId = getCurrentProjectId();
            const params = new URLSearchParams();

            if (projectId) {
                params.set("project_id", projectId);
            }

            const query = params.toString();
            const payload = await apiRequest("/api/tasks/kanban" + (query ? "?" + query : ""));

            state.tasksByStatus = normalizeGroupedData(payload.data);
            renderBoard();
        } catch (error) {
            showBoardMessage(`<b>Không thể tải Kanban:</b> ${escapeHtml(error.message || "Có lỗi khi tải danh sách task.")}`, true);
            ensureBoardSkeleton();
        } finally {
            setLoading(false);
        }
    }

    async function patchTaskStatus(taskId, nextStatus) {
        return apiRequest(`/api/tasks/${taskId}/status`, {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                status: normalizeApiStatus(nextStatus)
            })
        });
    }

    async function moveTaskToReview(taskId) {
        const task = findTaskById(taskId);

        if (!task) {
            await loadTasks();
            return;
        }

        if (!canDropToColumn(task, "Review")) {
            toast("error", "Không thể xử lý", invalidFlowMessage());
            await loadTasks();
            return;
        }

        try {
            setLoading(true);

            const payload = await patchTaskStatus(taskId, "Review");

            toast("success", "Đã gửi Chờ Duyệt", payload.message || "Task đã được gửi sang Chờ Duyệt.");
            await loadTasks();
        } catch (error) {
            toast("error", "Không thể gửi Chờ Duyệt", error.message || "Có lỗi xảy ra.");
            await loadTasks();
        } finally {
            setLoading(false);
        }
    }

    async function approveTask(taskId) {
        try {
            setLoading(true);

            const payload = await apiRequest(`/api/tasks/${taskId}/approve`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({})
            });

            toast("success", "Đã approve", payload.message || "Task đã chuyển Hoàn Thành.");
            await loadTasks();
        } catch (error) {
            toast("error", "Không thể approve", error.message || "Có lỗi xảy ra.");
            await loadTasks();
        } finally {
            setLoading(false);
        }
    }

    async function rejectTask(taskId, reason) {
        try {
            setLoading(true);

            const payload = await apiRequest(`/api/tasks/${taskId}/reject`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    reason
                })
            });

            toast("success", "Đã chuyển về Cần sửa", payload.message || "Task đã chuyển về cột Cần sửa.");
            await loadTasks();
        } catch (error) {
            toast("error", "Không thể reject", error.message || "Có lỗi xảy ra.");
            await loadTasks();
        } finally {
            setLoading(false);
        }
    }

    async function deleteTask(taskId, title) {
        if (!isManager()) {
            toast("error", "Không có quyền", "Chỉ Manager được xoá task.");
            return;
        }

        const ok = window.confirm(`Xoá task "${title || "này"}"?`);

        if (!ok) {
            return;
        }

        try {
            setLoading(true);

            const payload = await apiRequest(`/api/tasks/${taskId}`, {
                method: "DELETE"
            });

            toast("success", "Đã xoá task", payload.message || "Task đã được xoá.");
            await loadTasks();
        } catch (error) {
            toast("error", "Không thể xoá task", error.message || "Có lỗi xảy ra.");
            await loadTasks();
        } finally {
            setLoading(false);
        }
    }

    async function loadTaskOptions(projectId = "") {
        if (!isManager()) {
            return null;
        }

        const params = new URLSearchParams();

        if (projectId) {
            params.set("project_id", projectId);
        }

        const payload = await apiRequest("/api/tasks/options" + (params.toString() ? "?" + params.toString() : ""));
        return payload.data || {};
    }

    function optionHtml(items, selectedValue, labelFallback) {
        if (!Array.isArray(items) || items.length === 0) {
            return `<option value="">${escapeHtml(labelFallback)}</option>`;
        }

        return items.map(function (item) {
            const value = Number(item.id || 0);
            const label = item.name || item.full_name || item.title || item.email || `#${value}`;
            const selected = Number(selectedValue || 0) === value ? "selected" : "";

            return `<option value="${escapeHtml(value)}" ${selected}>${escapeHtml(label)}</option>`;
        }).join("");
    }

    function buildTaskForm(task, options) {
        const isEdit = !!task;
        const projects = Array.isArray(options?.projects) ? options.projects : [];
        const employees = Array.isArray(options?.assignees) ? options.assignees : (Array.isArray(options?.employees) ? options.employees : []);
        const priorities = Array.isArray(options?.priorities) ? options.priorities : ["Low", "Medium", "High"];
        const clientVisibilityEnabled = !!options?.client_visibility_enabled;

        const selectedProject = task?.project_id || getCurrentProjectId() || "";
        const selectedAssignee = task?.assignee_id || "";
        const selectedWatcher = task?.watcher_id || "";
        const selectedPriority = task?.priority || "Medium";

        return `
            <form data-task-form data-task-id="${isEdit ? escapeHtml(task.id) : ""}">
                <div class="form-group">
                    <label class="form-label" for="task-title">Tên task</label>
                    <input
                        id="task-title"
                        class="form-control"
                        type="text"
                        name="title"
                        value="${escapeHtml(task?.title || "")}"
                        placeholder="VD: Thiết kế banner campaign"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="task-description">Mô tả</label>
                    <textarea
                        id="task-description"
                        class="form-textarea"
                        name="description"
                        rows="4"
                        placeholder="Mô tả yêu cầu task"
                    >${escapeHtml(task?.description || "")}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="task-project">Project</label>
                        <select id="task-project" class="form-select" name="project_id" required data-task-project-select ${isEdit ? "disabled" : ""}>
                            <option value="">Chọn project</option>
                            ${optionHtml(projects, selectedProject, "Chưa có project khả dụng")}
                        </select>
                        ${isEdit ? `<input type="hidden" name="project_id" value="${escapeHtml(selectedProject)}">` : ""}
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="task-priority">Ưu tiên</label>
                        <select id="task-priority" class="form-select" name="priority">
                            ${priorities.map(function (priority) {
                                const selected = String(priority) === String(selectedPriority) ? "selected" : "";
                                return `<option value="${escapeHtml(priority)}" ${selected}>${escapeHtml(priorityLabel(priority))}</option>`;
                            }).join("")}
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="task-assignee">Người phụ trách</label>
                        <select id="task-assignee" class="form-select" name="assignee_id" required data-task-assignee-select>
                            <option value="">Chọn employee</option>
                            ${optionHtml(employees, selectedAssignee, "Chưa có employee active trong project")}
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="task-watcher">Watcher</label>
                        <select id="task-watcher" class="form-select" name="watcher_id">
                            <option value="">Không chọn</option>
                            ${optionHtml(employees, selectedWatcher, "Chưa có watcher")}
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="task-deadline">Deadline</label>
                        <input
                            id="task-deadline"
                            class="form-control"
                            type="date"
                            name="deadline"
                            value="${escapeHtml(task?.deadline || "")}"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="task-status">Trạng thái</label>
                        <select id="task-status" class="form-select" name="status">
                            <option value="To do" ${normalizeApiStatus(task?.status) === "To do" ? "selected" : ""}>Task mới</option>
                            <option value="Review" ${normalizeApiStatus(task?.status) === "Review" ? "selected" : ""}>Chờ Duyệt</option>
                            <option value="Done" ${normalizeApiStatus(task?.status) === "Done" ? "selected" : ""}>Hoàn Thành</option>
                        </select>
                    </div>
                </div>

                ${clientVisibilityEnabled ? `
                    <label class="checkbox-line" style="margin-top: 12px;">
                        <input type="checkbox" name="is_client_visible" value="1" ${Number(task?.is_client_visible || 0) === 1 ? "checked" : ""}>
                        <span>Public task này cho Client Portal</span>
                    </label>
                ` : ""}

                <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:22px;">
                    <button class="btn btn-light" type="button" data-task-modal-close>Hủy</button>
                    <button class="btn btn-primary" type="submit">
                        ${isEdit ? "Lưu thay đổi" : "Tạo task"}
                    </button>
                </div>
            </form>
        `;
    }

    function closeTaskModal() {
        if (state.activeModal) {
            state.activeModal.remove();
            state.activeModal = null;
        }
    }

    function openTaskModalShell(title, body) {
        closeTaskModal();

        const modal = document.createElement("div");
        modal.className = "modal-backdrop is-visible";
        modal.setAttribute("data-task-modal", "");
        modal.style.position = "fixed";
        modal.style.inset = "0";
        modal.style.zIndex = "9999";
        modal.style.display = "grid";
        modal.style.placeItems = "center";
        modal.style.padding = "24px";
        modal.style.background = "rgba(15, 23, 42, 0.42)";

        modal.innerHTML = `
            <div class="modal-panel" style="width:min(760px, calc(100vw - 32px)); max-height:calc(100vh - 48px); overflow:auto; background:#fff; border-radius:22px; box-shadow:0 24px 80px rgba(15,23,42,.24);">
                <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 24px;border-bottom:1px solid #e5e7eb;">
                    <h2 style="margin:0;">${escapeHtml(title)}</h2>
                    <button class="modal-close" type="button" data-task-modal-close>×</button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    ${body}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        state.activeModal = modal;
    }

    async function refreshAssigneesForProject(projectId, assigneeSelect, watcherSelect, selectedAssignee = "", selectedWatcher = "") {
        if (!projectId || !assigneeSelect) {
            return;
        }

        try {
            const options = await loadTaskOptions(projectId);
            const employees = Array.isArray(options?.assignees) ? options.assignees : [];

            assigneeSelect.innerHTML = `
                <option value="">Chọn employee</option>
                ${optionHtml(employees, selectedAssignee, "Chưa có employee active trong project")}
            `;

            if (watcherSelect) {
                watcherSelect.innerHTML = `
                    <option value="">Không chọn</option>
                    ${optionHtml(employees, selectedWatcher, "Chưa có watcher")}
                `;
            }
        } catch (error) {
            toast("error", "Không thể tải employee theo project", error.message);
        }
    }

    async function openTaskModal(task = null) {
        if (!isManager()) {
            toast("error", "Không có quyền", "Chỉ Manager được tạo hoặc sửa task.");
            return;
        }

        try {
            const options = await loadTaskOptions(task?.project_id || getCurrentProjectId() || "");
            openTaskModalShell(task ? "Cập nhật task" : "Tạo task mới", buildTaskForm(task, options));

            const form = qs("[data-task-form]", state.activeModal);
            const projectSelect = qs("[data-task-project-select]", form);
            const assigneeSelect = qs("[data-task-assignee-select]", form);
            const watcherSelect = qs('select[name="watcher_id"]', form);

            if (projectSelect && !task) {
                projectSelect.addEventListener("change", async function () {
                    await refreshAssigneesForProject(projectSelect.value, assigneeSelect, watcherSelect);
                });
            }
        } catch (error) {
            toast("error", "Không thể mở form task", error.message);
        }
    }

    async function submitTaskForm(form) {
        const taskId = Number(form.getAttribute("data-task-id") || 0);
        const formData = new FormData(form);

        const payload = {
            title: String(formData.get("title") || "").trim(),
            description: String(formData.get("description") || "").trim(),
            project_id: formData.get("project_id") ? Number(formData.get("project_id")) : null,
            priority: String(formData.get("priority") || "Medium"),
            deadline: String(formData.get("deadline") || "").trim() || null,
            assignee_id: formData.get("assignee_id") ? Number(formData.get("assignee_id")) : null,
            watcher_id: formData.get("watcher_id") ? Number(formData.get("watcher_id")) : null,
            status: String(formData.get("status") || "To do"),
            is_client_visible: formData.get("is_client_visible") ? 1 : 0
        };

        if (!payload.title) {
            toast("error", "Thiếu tên task", "Vui lòng nhập tên task.");
            return;
        }

        if (!payload.project_id) {
            toast("error", "Thiếu project", "Vui lòng chọn project.");
            return;
        }

        if (!payload.assignee_id) {
            toast("error", "Thiếu người phụ trách", "Vui lòng chọn employee nhận task.");
            return;
        }

        try {
            setLoading(true);

            if (taskId > 0) {
                const res = await apiRequest(`/api/tasks/${taskId}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                toast("success", "Đã cập nhật task", res.message || "Task đã được cập nhật.");
            } else {
                const res = await apiRequest("/api/tasks", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                toast("success", "Đã tạo task", res.message || "Task mới đã được giao.");
            }

            closeTaskModal();
            await loadTasks();
        } catch (error) {
            toast("error", taskId > 0 ? "Không thể cập nhật task" : "Không thể tạo task", error.message);
        } finally {
            setLoading(false);
        }
    }

    function bindGlobalActions() {
        qsa("[data-add-task]").forEach(function (button) {
            if (button.dataset.taskCreateReady === "true") {
                return;
            }

            button.dataset.taskCreateReady = "true";

            button.addEventListener("click", function (event) {
                event.preventDefault();
                openTaskModal();
            });
        });

        document.addEventListener("click", function (event) {
            const closeButton = event.target.closest("[data-task-modal-close]");

            if (closeButton) {
                event.preventDefault();
                closeTaskModal();
            }
        });

        document.addEventListener("submit", function (event) {
            const form = event.target.closest("[data-task-form]");

            if (!form) {
                return;
            }

            event.preventDefault();
            submitTaskForm(form);
        });
    }

    function bindRefreshButtons() {
        qsa("[data-kanban-refresh], [data-refresh-kanban]").forEach(function (button) {
            if (button.dataset.kanbanRefreshReady === "true") {
                return;
            }

            button.dataset.kanbanRefreshReady = "true";

            button.addEventListener("click", function (event) {
                event.preventDefault();
                loadTasks();
            });
        });
    }

    async function init() {
        ensureBoardSkeleton();
        bindGlobalActions();
        bindRefreshButtons();

        await loadCurrentUser();
        await loadTasks();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();