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
        isLoading: false
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

    function columnToApiStatus(column) {
        const boardColumn = normalizeBoardColumn(column);

        if (boardColumn === "Revision") {
            return "Doing";
        }

        if (boardColumn === "Review") {
            return "Review";
        }

        if (boardColumn === "Done") {
            return "Done";
        }

        return "To do";
    }

    function roleOfCurrentUser() {
        return String(state.currentUser?.role || window.CAH_CURRENT_USER?.role || "").toLowerCase();
    }

    function currentUserId() {
        return Number(state.currentUser?.id || window.CAH_CURRENT_USER?.id || 0);
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

        const column = document.createElement("article");
        column.className = `kanban-column kanban-column-${meta.tone}`;
        column.setAttribute("data-kanban-status", columnKey);

        column.innerHTML = `
            <div class="kanban-column-header">
                <h2>${escapeHtml(meta.label)}</h2>
                <span class="kanban-count" data-kanban-count="${escapeHtml(columnKey)}">0</span>
            </div>
            <div class="kanban-list" data-kanban-list></div>
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

            const title = qs(".kanban-column-header h2, h2, h3", column);

            if (title) {
                title.textContent = meta.label;
            }

            let count = qs("[data-kanban-count]", column);

            if (!count) {
                count = document.createElement("span");
                count.className = "kanban-count";
                count.setAttribute("data-kanban-count", columnKey);

                const header = qs(".kanban-column-header", column) || column;
                header.appendChild(count);
            }

            let list = findColumnList(column);

            if (!list || list === column) {
                list = document.createElement("div");
                list.className = "kanban-list";
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
            <div class="kanban-empty-state" data-kanban-empty>
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
            <div class="kanban-task-note">
                <strong>Lý do cần sửa:</strong>
                <span>${escapeHtml(task.reject_reason)}</span>
            </div>
        `;
    }

    function renderCard(task) {
        const card = document.createElement("article");
        const draggable = canDragTask(task);
        const boardColumn = boardColumnForTask(task);

        card.className = "kanban-card task-card";
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

        card.innerHTML = `
            <div class="kanban-card-head">
                <div>
                    <span class="kanban-project">${escapeHtml(projectName)}</span>
                    <h3>${escapeHtml(task.title)}</h3>
                </div>
                <span class="badge ${priorityClass(task.priority)}">${escapeHtml(priorityLabel(task.priority))}</span>
            </div>

            ${task.description ? `<p class="kanban-task-desc">${escapeHtml(task.description)}</p>` : ""}

            ${rejectReasonBlock(task)}

            <div class="kanban-task-meta">
                <span>Người phụ trách: ${escapeHtml(assigneeName)}</span>
                <span>Deadline: ${escapeHtml(formatDate(task.deadline))}</span>
            </div>

            <div class="kanban-task-tags">
                ${overdue}
                ${revisionBadge(task)}
                ${publicBadge(task)}
            </div>

            <div class="kanban-task-actions">
                ${renderActionButtons(task)}
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

    function renderActionButtons(task) {
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
        qsa("[data-task-status-action]", card).forEach(function (button) {
            button.addEventListener("click", async function () {
                const nextStatus = button.getAttribute("data-task-status-action");
                await updateTaskStatus(Number(task.id), nextStatus);
            });
        });

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
            toast("error", "Không thể tải Kanban", error.message || "Có lỗi khi tải danh sách task.");
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

    async function updateTaskStatus(taskId, nextStatus) {
        const task = findTaskById(taskId);

        if (!task) {
            await loadTasks();
            return;
        }

        const normalizedNextStatus = normalizeApiStatus(nextStatus);
        const targetColumn = normalizedNextStatus === "Done"
            ? "Done"
            : normalizedNextStatus === "Review"
                ? "Review"
                : "Task";

        if (!canDropToColumn(task, targetColumn)) {
            toast(
                "error",
                "Không thể xử lý",
                targetColumn === "Done" ? cannotCompleteMessage() : invalidFlowMessage()
            );

            await loadTasks();
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