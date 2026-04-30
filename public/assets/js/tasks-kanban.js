(function () {
    "use strict";

    const board = document.querySelector("[data-kanban-board]");
    if (!board) return;

    const STATUS_TO_COLUMN = {
        "To do": "todo",
        "Doing": "doing",
        "Review": "review",
        "Done": "done"
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
        High: "danger"
    };

    let draggedCard = null;
    let previousDropState = null;

    function escapeHtml(value) {
        return window.CAHApp?.escapeHtml
            ? CAHApp.escapeHtml(value)
            : String(value || "");
    }

    function getColumnByStatus(status) {
        return STATUS_TO_COLUMN[status] || "todo";
    }

    function getStatusByColumn(column) {
        return COLUMN_TO_STATUS[column] || "To do";
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
        return {
            id: task.id,
            title: task.title || "Chưa có tiêu đề",
            description: task.description || "Chưa có mô tả.",
            status: task.status || "To do",
            priority: task.priority || "Medium",
            deadline: task.deadline || "",
            assignee_id: task.assignee_id || "",
            assigner_id: task.assigner_id || "",
            watcher_id: task.watcher_id || "",
            project_id: task.project_id || ""
        };
    }

    function renderTaskCard(rawTask) {
        const task = normalizeTask(rawTask);
        const columnKey = getColumnByStatus(task.status);
        const priorityTone = PRIORITY_TONE[task.priority] || "primary";
        const progress = getTaskProgress(task.status);
        const deadlineText = task.deadline ? `Deadline: ${escapeHtml(task.deadline)}` : "Chưa có deadline";
        const initials = escapeHtml(getInitials(task));

        return `
            <article
                class="task-card"
                draggable="true"
                data-task-card
                data-task-id="${escapeHtml(task.id)}"
                data-status="${escapeHtml(columnKey)}"
                data-title="${escapeHtml(task.title)}"
                data-description="${escapeHtml(task.description)}"
            >
                <div class="task-card-top">
                    <span class="badge badge-${escapeHtml(priorityTone)}">
                        ${escapeHtml(task.priority)}
                    </span>

                    <button class="kanban-column-menu" type="button">⋮</button>
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
                        <span>▣ 0</span>
                        <span>□ 0</span>
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

        if (!Array.isArray(tasks) || tasks.length === 0) {
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

        tasks.forEach((task) => {
            const normalized = normalizeTask(task);
            const columnKey = getColumnByStatus(normalized.status);
            const list = document.querySelector(`[data-kanban-column][data-status="${columnKey}"] [data-kanban-list]`);

            if (list) {
                list.insertAdjacentHTML("beforeend", renderTaskCard(normalized));
            }
        });

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

    function openTaskModal(mode, taskData) {
        if (!window.CAHModal) return;

        const isCreate = mode === "create";
        const title = isCreate ? "Tạo công việc mới" : "Chi tiết công việc";
        const subtitle = isCreate
            ? "Task sẽ được gửi đến API /api/tasks nếu bạn đã đăng nhập."
            : "Xem nhanh thông tin task và cập nhật giao diện.";

        const body = `
            <form class="task-modal-form" data-task-create-form>
                <div class="form-group">
                    <label class="form-label">Tên công việc</label>
                    <input class="form-control" type="text" name="title" value="${escapeHtml(taskData?.title || "")}" placeholder="Nhập tên công việc" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select class="form-select" name="priority">
                            <option value="Low">Thấp</option>
                            <option value="Medium" selected>Trung bình</option>
                            <option value="High">Cao</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input class="form-control" type="date" name="deadline" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Project ID</label>
                        <input class="form-control" type="number" name="project_id" placeholder="Có thể để trống">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assignee ID</label>
                        <input class="form-control" type="number" name="assignee_id" placeholder="Có thể để trống">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-textarea" name="description" placeholder="Mô tả ngắn về công việc">${escapeHtml(taskData?.description || "")}</textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    ${isCreate ? '<button class="btn btn-primary" type="submit">Tạo task</button>' : ''}
                </div>
            </form>
        `;

        CAHModal.open({ title, subtitle, body });
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
            await CAHApi.patch(`/api/tasks/${taskId}/status`, { status: newStatus }, {
                loading: false
            });

            if (window.CAHToast) {
                CAHToast.success("Đã cập nhật", `Task đã chuyển sang trạng thái ${STATUS_LABELS[newColumnKey]}.`);
            }
        } catch (error) {
            if (previousDropState?.list && previousDropState?.nextSibling !== undefined) {
                previousDropState.list.insertBefore(card, previousDropState.nextSibling);
                card.dataset.status = previousDropState.status;
                updateColumnCounts();
            }
        }
    }

    async function createTaskFromForm(form) {
        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn()) {
            if (window.CAHToast) {
                CAHToast.error("Chưa đăng nhập", "Vui lòng đăng nhập trước khi tạo task thật.");
            }
            return;
        }

        const data = window.CAHApp?.formToObject ? CAHApp.formToObject(form) : {};
        const payload = {
            title: data.title,
            description: data.description || "",
            priority: data.priority || "Medium",
            deadline: data.deadline,
            project_id: data.project_id || null,
            assignee_id: data.assignee_id || null,
            watcher_id: data.watcher_id || null
        };

        try {
            await CAHApi.post("/api/tasks", payload, {
                loading: true,
                loadingMessage: "Đang tạo task mới..."
            });

            if (window.CAHToast) {
                CAHToast.success("Tạo task thành công", "Task mới đã được lưu vào database.");
            }

            if (window.CAHModal) {
                CAHModal.close();
            }

            await loadTasksFromApi();
        } catch (error) {
            if (window.CAHToast) {
                CAHToast.error("Không thể tạo task", error.message || "API chưa xử lý được yêu cầu.");
            }
        }
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
        const addButton = event.target.closest("[data-add-task]");
        const card = event.target.closest("[data-task-card]");

        if (addButton) {
            openTaskModal("create");
            return;
        }

        if (card && !event.target.closest("button")) {
            openTaskModal("view", {
                title: card.dataset.title || "",
                description: card.dataset.description || ""
            });
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-task-create-form]");
        if (!form) return;

        event.preventDefault();

        const title = form.querySelector("[name='title']");
        const deadline = form.querySelector("[name='deadline']");

        if (!title?.value.trim() || !deadline?.value.trim()) {
            if (window.CAHToast) {
                CAHToast.error("Thiếu thông tin", "Vui lòng nhập tên công việc và deadline.");
            }
            return;
        }

        createTaskFromForm(form);
    });

    updateColumnCounts();
    loadTasksFromApi();
})();