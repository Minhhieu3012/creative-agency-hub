(function () {
    "use strict";

    const board = document.querySelector("[data-kanban-board]");
    if (!board) return;

    const STATUS_TO_COLUMN = {
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
        todo: "To do",
        doing: "Doing",
        review: "Review",
        done: "Done"
    };

    const STATUS_LABELS = {
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

    const DEFAULT_PROJECT_ID = 1;
    const DEFAULT_ASSIGNEE_ID = 2;
    const DEFAULT_WATCHER_ID = 1;

    let draggedCard = null;
    let previousDropState = null;
    let latestTasks = [];
    let taskMetaCounts = new Map();

    injectActionStyles();

    function escapeHtml(value) {
        return window.CAHApp?.escapeHtml
            ? CAHApp.escapeHtml(value)
            : String(value || "");
    }

    function toNumberOrNull(value) {
        if (value === undefined || value === null || value === "") return null;

        const number = Number(value);
        return Number.isFinite(number) && number > 0 ? number : null;
    }

    function getCurrentUser() {
        return window.CAHAuth?.getUser?.() || {};
    }

    function getCurrentUserId() {
        const user = getCurrentUser();

        return toNumberOrNull(user?.id)
            || toNumberOrNull(user?.employee_id)
            || DEFAULT_WATCHER_ID;
    }

    function getFallbackAssigneeId() {
        const user = getCurrentUser();

        if (user?.role === "employee") {
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

        const projectSelects = Array.from(document.querySelectorAll("select"));

        for (const select of projectSelects) {
            const selectedText = String(select.options?.[select.selectedIndex]?.textContent || "").toLowerCase();
            const selectedValue = toNumberOrNull(select.value);

            if (selectedValue && (
                selectedText.includes("project")
                || selectedText.includes("dự án")
                || selectedText.includes("nexus")
            )) {
                return selectedValue;
            }
        }

        return DEFAULT_PROJECT_ID;
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
            project_name: task?.project_name || ""
        };
    }

    function isManagerLike() {
        const role = String(getCurrentUser()?.role || "").toLowerCase();
        return role === "admin" || role === "manager";
    }

    function renderTaskCard(rawTask) {
        const task = normalizeTask(rawTask);
        const columnKey = getColumnByStatus(task.status);
        const priorityTone = PRIORITY_TONE[task.priority] || "primary";
        const progress = getTaskProgress(task.status);
        const deadlineText = task.deadline ? `Deadline: ${escapeHtml(task.deadline)}` : "Chưa có deadline";
        const initials = escapeHtml(getInitials(task));
        const doneClass = columnKey === "done" ? " is-completed" : "";
        const counts = taskMetaCounts.get(String(task.id)) || { comments: 0, attachments: 0 };

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
                    <span class="badge badge-${escapeHtml(priorityTone)}">
                        ${escapeHtml(String(task.priority).toUpperCase())}
                    </span>

                    <button
                        class="kanban-column-menu"
                        type="button"
                        data-task-menu-trigger
                        data-task-id="${escapeHtml(task.id)}"
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
                        ${columnKey === "done"
                            ? "<span>✓ Done</span>"
                            : `
                                <span data-task-comment-count="${escapeHtml(task.id)}">💬 ${counts.comments}</span>
                                <span data-task-attachment-count="${escapeHtml(task.id)}">📎 ${counts.attachments}</span>
                            `
                        }
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
        hydrateCardMetaCounts();
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
        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn()) {
            updateColumnCounts();
            return;
        }

        try {
            const response = await CAHApi.get("/api/tasks", {
                loading: true,
                loadingMessage: "Đang tải dữ liệu Kanban..."
            });

            renderTasks(response.data || []);
        } catch (error) {
            updateColumnCounts();

            if (window.CAHToast) {
                CAHToast.info("Dùng dữ liệu tạm", "Không tải được task từ API, giao diện đang giữ dữ liệu demo.");
            }
        }
    }

    async function hydrateCardMetaCounts() {
        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn()) return;

        const tasksToHydrate = latestTasks.slice(0, 30);

        await Promise.allSettled(tasksToHydrate.map(async (task) => {
            if (!task.id) return;

            const [commentsResponse, attachmentsResponse] = await Promise.allSettled([
                CAHApi.get(`/api/tasks/${task.id}/comments`, { loading: false }),
                CAHApi.get(`/api/tasks/${task.id}/attachments`, { loading: false })
            ]);

            const comments = commentsResponse.status === "fulfilled" && Array.isArray(commentsResponse.value?.data)
                ? commentsResponse.value.data
                : [];

            const attachments = attachmentsResponse.status === "fulfilled" && Array.isArray(attachmentsResponse.value?.data)
                ? attachmentsResponse.value.data
                : [];

            taskMetaCounts.set(String(task.id), {
                comments: comments.length,
                attachments: attachments.length
            });

            const safeId = typeof CSS !== "undefined" && CSS.escape
                ? CSS.escape(String(task.id))
                : String(task.id).replace(/"/g, '\\"');

            const commentEl = document.querySelector(`[data-task-comment-count="${safeId}"]`);
            const attachmentEl = document.querySelector(`[data-task-attachment-count="${safeId}"]`);

            if (commentEl) commentEl.textContent = `💬 ${comments.length}`;
            if (attachmentEl) attachmentEl.textContent = `📎 ${attachments.length}`;
        }));
    }

    function taskInfoForm(mode, taskData) {
        const isCreate = mode === "create";
        const isEdit = mode === "edit";
        const canEdit = isCreate || isEdit;
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
                        ${canEdit ? "required" : "readonly"}
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status" ${canEdit ? "" : "disabled"}>
                            <option value="To do" ${task?.status === "To do" ? "selected" : ""}>Cần làm</option>
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
                        <label class="form-label">Project ID</label>
                        <input
                            class="form-control"
                            type="number"
                            name="project_id"
                            value="${escapeHtml(task?.project_id || selectedProjectId)}"
                            min="1"
                            ${canEdit ? "" : "readonly"}
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
                            ${canEdit ? "" : "readonly"}
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
                            ${canEdit ? "" : "readonly"}
                        >
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

                    ${!isCreate && isManagerLike() ? `<button class="btn btn-light" type="button" data-task-action="edit" data-task-id="${escapeHtml(task?.id || "")}">Chỉnh sửa</button>` : ""}
                    ${!isCreate && isManagerLike() ? `<button class="btn btn-danger" type="button" data-task-action="delete" data-task-id="${escapeHtml(task?.id || "")}">Xoá</button>` : ""}

                    ${task?.status === "Doing" ? `<button class="btn btn-soft" type="button" data-task-action="submit" data-task-id="${escapeHtml(task?.id || "")}">Gửi duyệt</button>` : ""}
                    ${task?.status === "Review" && isManagerLike() ? `<button class="btn btn-light" type="button" data-task-action="reject" data-task-id="${escapeHtml(task?.id || "")}">Từ chối</button>` : ""}
                    ${task?.status === "Review" && isManagerLike() ? `<button class="btn btn-primary" type="button" data-task-action="approve" data-task-id="${escapeHtml(task?.id || "")}">Duyệt hoàn thành</button>` : ""}

                    ${canEdit ? '<button class="btn btn-primary" type="submit">' + (isEdit ? 'Lưu thay đổi' : 'Tạo task') + '</button>' : ''}
                </div>
            </form>
        `;
    }

    function taskDetailBody(mode, taskData) {
        const isCreate = mode === "create";
        const task = taskData ? normalizeTask(taskData) : null;

        if (isCreate) {
            return taskInfoForm(mode, taskData);
        }

        return `
            <div class="task-detail-shell" data-task-detail-shell data-task-id="${escapeHtml(task?.id || "")}">
                <div class="task-detail-tabs">
                    <button class="is-active" type="button" data-task-tab="info">Thông tin</button>
                    <button type="button" data-task-tab="comments">Bình luận <span data-detail-comment-count>0</span></button>
                    <button type="button" data-task-tab="attachments">Tệp <span data-detail-attachment-count>0</span></button>
                    <button type="button" data-task-tab="activity">Hoạt động</button>
                </div>

                <div class="task-detail-panel is-active" data-task-panel="info">
                    ${taskInfoForm(mode, taskData)}
                </div>

                <div class="task-detail-panel" data-task-panel="comments">
                    <div class="task-comments-box">
                        <div data-task-comments-list class="task-comments-list">
                            ${renderLoadingState("Đang tải bình luận...")}
                        </div>

                        <form class="task-comment-form" data-task-comment-form data-task-id="${escapeHtml(task?.id || "")}">
                            <textarea class="form-textarea" name="content" rows="3" placeholder="Nhập bình luận cho task này..." required></textarea>
                            <div class="task-modal-footer">
                                <button class="btn btn-primary" type="submit">Gửi bình luận</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="task-detail-panel" data-task-panel="attachments">
                    <div class="task-attachments-box">
                        <form class="task-upload-form" data-task-attachment-form data-task-id="${escapeHtml(task?.id || "")}">
                            <input class="form-control" type="file" name="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
                            <button class="btn btn-primary" type="submit">Tải tệp lên</button>
                        </form>

                        <div data-task-attachments-list class="task-attachments-list">
                            ${renderLoadingState("Đang tải tệp đính kèm...")}
                        </div>
                    </div>
                </div>

                <div class="task-detail-panel" data-task-panel="activity">
                    <div data-task-activity-list class="task-detail-activity">
                        ${renderLoadingState("Đang tải lịch sử hoạt động...")}
                    </div>
                </div>
            </div>
        `;
    }

    function renderLoadingState(text) {
        return `
            <div class="task-detail-empty">
                <strong>${escapeHtml(text)}</strong>
                <p>Vui lòng chờ trong giây lát.</p>
            </div>
        `;
    }

    function renderEmptyState(title, text) {
        return `
            <div class="task-detail-empty">
                <strong>${escapeHtml(title)}</strong>
                <p>${escapeHtml(text)}</p>
            </div>
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
            create: "Task sẽ được lưu vào database và tự đồng bộ lại Kanban.",
            view: "Xem thông tin, bình luận, tệp đính kèm và lịch sử hoạt động.",
            edit: "Cập nhật nội dung task và đồng bộ lại Kanban."
        };

        CAHModal.open({
            title: titleMap[mode] || "Công việc",
            subtitle: subtitleMap[mode] || "",
            body: taskDetailBody(mode, taskData)
        });

        if (mode !== "create") {
            loadTaskDetailData(taskData?.id);
        }
    }

    async function loadTaskDetailData(taskId) {
        if (!taskId || !window.CAHApi) return;

        await Promise.allSettled([
            loadTaskComments(taskId),
            loadTaskAttachments(taskId),
            loadTaskActivity(taskId)
        ]);

        await hydrateCardMetaCounts();
    }

    async function loadTaskComments(taskId) {
        const list = document.querySelector("[data-task-comments-list]");
        const countEl = document.querySelector("[data-detail-comment-count]");

        if (!list) return;

        try {
            const response = await CAHApi.get(`/api/tasks/${taskId}/comments`, { loading: false });
            const comments = Array.isArray(response.data) ? response.data : [];

            if (countEl) countEl.textContent = comments.length;

            if (!comments.length) {
                list.innerHTML = renderEmptyState("Chưa có bình luận", "Bình luận đầu tiên sẽ giúp team nắm rõ tiến độ hơn.");
                return;
            }

            list.innerHTML = comments.map((comment) => `
                <article class="task-comment-item">
                    <div class="task-comment-avatar">${escapeHtml(getInitials({ assignee_name: comment.full_name || "CA" }))}</div>
                    <div class="task-comment-content">
                        <div class="task-comment-head">
                            <strong>${escapeHtml(comment.full_name || "Người dùng")}</strong>
                            <time>${escapeHtml(comment.created_at || "")}</time>
                        </div>
                        <p>${escapeHtml(comment.comment_text || comment.content || "")}</p>
                    </div>
                </article>
            `).join("");
        } catch (error) {
            list.innerHTML = renderEmptyState("Không tải được bình luận", error.message || "API bình luận chưa phản hồi.");
        }
    }

    async function loadTaskAttachments(taskId) {
        const list = document.querySelector("[data-task-attachments-list]");
        const countEl = document.querySelector("[data-detail-attachment-count]");

        if (!list) return;

        try {
            const response = await CAHApi.get(`/api/tasks/${taskId}/attachments`, { loading: false });
            const attachments = Array.isArray(response.data) ? response.data : [];

            if (countEl) countEl.textContent = attachments.length;

            if (!attachments.length) {
                list.innerHTML = renderEmptyState("Chưa có tệp đính kèm", "Upload file PDF, DOCX hoặc hình ảnh để lưu cùng task.");
                return;
            }

            list.innerHTML = attachments.map((file) => `
                <article class="task-attachment-item">
                    <div class="task-attachment-icon">📎</div>
                    <div>
                        <strong>${escapeHtml(file.file_name || "Tệp đính kèm")}</strong>
                        <p>${escapeHtml(file.uploaded_at || "Chưa rõ thời gian")}</p>
                    </div>
                    <button
                        class="btn btn-light"
                        type="button"
                        data-task-download-attachment
                        data-attachment-id="${escapeHtml(file.id)}"
                        data-file-name="${escapeHtml(file.file_name || "attachment")}"
                    >
                        Tải xuống
                    </button>
                </article>
            `).join("");
        } catch (error) {
            list.innerHTML = renderEmptyState("Không tải được tệp", error.message || "API tệp đính kèm chưa phản hồi.");
        }
    }

    async function loadTaskActivity(taskId) {
        const list = document.querySelector("[data-task-activity-list]");
        if (!list) return;

        try {
            const response = await CAHApi.get(`/api/tasks/${taskId}/activity`, { loading: false });
            const activities = Array.isArray(response.data) ? response.data : [];

            if (!activities.length) {
                list.innerHTML = renderEmptyState("Chưa có hoạt động", "Các thao tác như comment, upload, cập nhật sẽ được ghi lại tại đây.");
                return;
            }

            list.innerHTML = activities.map((activity) => `
                <article class="task-activity-item">
                    <div class="task-activity-dot"></div>
                    <div>
                        <strong>${escapeHtml(activity.action || "activity")}</strong>
                        <p>${escapeHtml(activity.description || "")}</p>
                        <time>${escapeHtml(activity.created_at || "")} · ${escapeHtml(activity.full_name || "")}</time>
                    </div>
                </article>
            `).join("");
        } catch (error) {
            list.innerHTML = renderEmptyState("Không tải được hoạt động", error.message || "API activity chưa phản hồi.");
        }
    }

    async function updateTaskStatusById(taskId, newStatus) {
        await CAHApi.patch(`/api/tasks/${taskId}/status`, { status: newStatus }, {
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

        if (!taskId || !window.CAHApi || !window.CAHAuth?.isLoggedIn()) {
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
            loadingMessage: "Đang tạo task mới..."
        });

        const createdTask = response?.data?.task || response?.data || {
            id: response?.data?.id,
            ...payload,
            status: "To do",
            assigner_id: getCurrentUserId()
        };

        if (window.CAHModal) {
            CAHModal.close();
        }

        appendTaskToBoard(createdTask);

        if (window.CAHToast) {
            CAHToast.success("Tạo task thành công", "Task mới đã hiển thị trên Kanban.");
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
            watcher_id: toNumberOrNull(data.watcher_id) || null
        };

        const response = await CAHApi.put(`/api/tasks/${taskId}`, payload, {
            loading: true,
            loadingMessage: "Đang lưu thay đổi..."
        });

        if (data.status) {
            await CAHApi.patch(`/api/tasks/${taskId}/status`, { status: data.status }, {
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
        const task = findTaskById(taskId);

        if (!task) return;

        const confirmed = window.confirm(`Xoá task "${task.title}"? Thao tác này không thể hoàn tác.`);

        if (!confirmed) return;

        await CAHApi.delete(`/api/tasks/${taskId}`, {
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

        await CAHApi.post(endpointMap[action], {}, {
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

    async function submitComment(form) {
        const taskId = form.dataset.taskId;
        const textarea = form.querySelector("[name='content']");
        const content = textarea?.value.trim();

        if (!content || content.length < 3) {
            if (window.CAHToast) {
                CAHToast.error("Bình luận quá ngắn", "Nội dung bình luận cần tối thiểu 3 ký tự.");
            }
            return;
        }

        await CAHApi.post(`/api/tasks/${taskId}/comments`, { content }, {
            loading: true,
            loadingMessage: "Đang gửi bình luận..."
        });

        textarea.value = "";

        if (window.CAHToast) {
            CAHToast.success("Đã gửi bình luận", "Bình luận đã được lưu vào task.");
        }

        await loadTaskComments(taskId);
        await loadTaskActivity(taskId);
        await hydrateCardMetaCounts();
    }

    async function uploadAttachment(form) {
        const taskId = form.dataset.taskId;
        const input = form.querySelector("[name='file']");

        if (!input?.files?.length) {
            if (window.CAHToast) {
                CAHToast.error("Chưa chọn file", "Vui lòng chọn file trước khi tải lên.");
            }
            return;
        }

        const formData = new FormData();
        formData.append("file", input.files[0]);

        await CAHApi.request(`/api/tasks/${taskId}/attachments`, {
            method: "POST",
            formData,
            loading: true,
            loadingMessage: "Đang tải tệp lên..."
        });

        input.value = "";

        if (window.CAHToast) {
            CAHToast.success("Đã tải tệp", "File đã được lưu vào task.");
        }

        await loadTaskAttachments(taskId);
        await loadTaskActivity(taskId);
        await hydrateCardMetaCounts();
    }

    async function downloadAttachment(button) {
        const attachmentId = button.dataset.attachmentId;
        const fileName = button.dataset.fileName || "attachment";

        if (!attachmentId) {
            if (window.CAHToast) {
                CAHToast.error("Không thể tải tệp", "Thiếu ID file đính kèm.");
            }
            return;
        }

        const token =
            window.CAHAuth?.getToken?.()
            || localStorage.getItem("cah_auth_token")
            || localStorage.getItem("cah_token")
            || localStorage.getItem("token")
            || localStorage.getItem("auth_token")
            || localStorage.getItem("access_token");

        if (!token) {
            if (window.CAHToast) {
                CAHToast.error("Phiên đăng nhập hết hạn", "Vui lòng đăng nhập lại để tải tệp.");
            }
            return;
        }

        const url = window.CAHApp?.buildApiUrl
            ? CAHApp.buildApiUrl(`/api/attachments/${attachmentId}/download`)
            : `/creative-agency-hub/public/api/attachments/${attachmentId}/download`;

        try {
            button.disabled = true;
            button.textContent = "Đang tải...";

            const response = await fetch(url, {
                method: "GET",
                headers: {
                    Authorization: `Bearer ${token}`
                }
            });

            if (!response.ok) {
                let message = "Không thể tải file.";

                try {
                    const errorData = await response.json();
                    message = errorData.message || message;
                } catch (_) {}

                throw new Error(message);
            }

            const blob = await response.blob();
            const objectUrl = window.URL.createObjectURL(blob);

            const tempLink = document.createElement("a");
            tempLink.href = objectUrl;
            tempLink.download = fileName;
            document.body.appendChild(tempLink);
            tempLink.click();
            tempLink.remove();

            window.URL.revokeObjectURL(objectUrl);

            if (window.CAHToast) {
                CAHToast.success("Đã tải xuống", `File "${fileName}" đã được tải về máy.`);
            }
        } catch (error) {
            if (window.CAHToast) {
                CAHToast.error("Không thể tải tệp", error.message || "API download chưa phản hồi.");
            }
        } finally {
            button.disabled = false;
            button.textContent = "Tải xuống";
        }
    }

    function openActionMenu(button) {
        closeActionMenu();

        const taskId = button.dataset.taskId;
        const task = findTaskById(taskId);

        if (!task) return;

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

        if (task.status !== "Doing") {
            actions.push(`<button type="button" data-task-action="move-doing" data-task-id="${escapeHtml(taskId)}">Chuyển Đang thực hiện</button>`);
        }

        if (task.status === "Doing") {
            actions.push(`<button type="button" data-task-action="submit" data-task-id="${escapeHtml(taskId)}">Gửi duyệt</button>`);
        }

        if (task.status === "Review" && isManagerLike()) {
            actions.push(`<button type="button" data-task-action="approve" data-task-id="${escapeHtml(taskId)}">Duyệt hoàn thành</button>`);
            actions.push(`<button type="button" data-task-action="reject" data-task-id="${escapeHtml(taskId)}">Từ chối</button>`);
        }

        if (task.status !== "Done" && isManagerLike()) {
            actions.push(`<button type="button" data-task-action="move-done" data-task-id="${escapeHtml(taskId)}">Đánh dấu hoàn thành</button>`);
        }

        if (task.status === "Done") {
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
        const task = findTaskById(taskId);

        if (!task) return;

        closeActionMenu();

        if (action === "view") {
            openTaskModal("view", task);
            return;
        }

        if (action === "edit") {
            openTaskModal("edit", task);
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
            .kanban-action-menu {
                position: absolute;
                z-index: 220;
                width: 220px;
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
        const menuTrigger = event.target.closest("[data-task-menu-trigger]");
        const actionButton = event.target.closest("[data-task-action]");
        const tabButton = event.target.closest("[data-task-tab]");
        const downloadButton = event.target.closest("[data-task-download-attachment]");
        const addButton = event.target.closest("[data-add-task]");
        const card = event.target.closest("[data-task-card]");

        if (!event.target.closest("[data-kanban-action-menu]") && !menuTrigger) {
            closeActionMenu();
        }

        if (downloadButton) {
            event.preventDefault();
            event.stopPropagation();

            downloadAttachment(downloadButton);
            return;
        }

        if (tabButton) {
            const shell = tabButton.closest("[data-task-detail-shell]");
            const tab = tabButton.dataset.taskTab;

            shell?.querySelectorAll("[data-task-tab]").forEach((button) => {
                button.classList.toggle("is-active", button === tabButton);
            });

            shell?.querySelectorAll("[data-task-panel]").forEach((panel) => {
                panel.classList.toggle("is-active", panel.dataset.taskPanel === tab);
            });

            return;
        }

        if (menuTrigger) {
            event.preventDefault();
            event.stopPropagation();
            openActionMenu(menuTrigger);
            return;
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

        if (card && !event.target.closest("button")) {
            const taskId = card.dataset.taskId;
            const task = findTaskById(taskId);

            openTaskModal("view", task || {
                id: taskId,
                title: card.dataset.title || "",
                description: card.dataset.description || "",
                project_id: card.dataset.projectId || "",
                assignee_id: card.dataset.assigneeId || "",
                watcher_id: card.dataset.watcherId || "",
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
        const taskForm = event.target.closest("[data-task-form]");
        const commentForm = event.target.closest("[data-task-comment-form]");
        const attachmentForm = event.target.closest("[data-task-attachment-form]");

        if (commentForm) {
            event.preventDefault();

            submitComment(commentForm).catch((error) => {
                if (window.CAHToast) {
                    CAHToast.error("Không thể gửi bình luận", error.message || "API chưa xử lý được yêu cầu.");
                }
            });
            return;
        }

        if (attachmentForm) {
            event.preventDefault();

            uploadAttachment(attachmentForm).catch((error) => {
                if (window.CAHToast) {
                    CAHToast.error("Không thể tải tệp", error.message || "API chưa xử lý được yêu cầu.");
                }
            });
            return;
        }

        if (!taskForm) return;

        event.preventDefault();

        const mode = taskForm.dataset.taskFormMode;
        const title = taskForm.querySelector("[name='title']");
        const deadline = taskForm.querySelector("[name='deadline']");

        if (!title?.value.trim() || !deadline?.value.trim()) {
            if (window.CAHToast) {
                CAHToast.error("Thiếu thông tin", "Vui lòng nhập tên công việc và deadline.");
            }
            return;
        }

        const handler = mode === "edit" ? updateTaskFromForm : createTaskFromForm;

        handler(taskForm).catch((error) => {
            if (window.CAHToast) {
                CAHToast.error("Không thể lưu task", error.message || "API chưa xử lý được yêu cầu.");
            }
        });
    });

    updateColumnCounts();
    loadTasksFromApi();
})();