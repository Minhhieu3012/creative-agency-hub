(function () {
    "use strict";

    const board = document.querySelector("[data-kanban-board]");
    if (!board) return;

    let draggedCard = null;

    function updateColumnCounts() {
        document.querySelectorAll("[data-kanban-column]").forEach((column) => {
            const count = column.querySelectorAll("[data-task-card]").length;
            const countEl = column.querySelector("[data-column-count]");

            if (countEl) {
                countEl.textContent = count;
            }
        });
    }

    function openTaskModal(mode, taskData) {
        if (!window.CAHModal) return;

        const title = mode === "create" ? "Tạo công việc mới" : "Chi tiết công việc";
        const subtitle = mode === "create"
            ? "Tạo task demo trên UI. Backend sẽ được nối ở bước sau."
            : "Xem nhanh thông tin task và cập nhật giao diện.";

        const body = `
            <form class="task-modal-form" data-ui-form data-mock-submit="true" data-success-message="Đã lưu task trên giao diện.">
                <div class="form-group">
                    <label class="form-label">Tên công việc</label>
                    <input class="form-control" type="text" name="title" value="${taskData?.title || ""}" placeholder="Nhập tên công việc" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select class="form-select" name="priority">
                            <option value="normal">Bình thường</option>
                            <option value="high">Ưu tiên cao</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input class="form-control" type="date" name="deadline">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-textarea" name="description" placeholder="Mô tả ngắn về công việc">${taskData?.description || ""}</textarea>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Lưu công việc</button>
                </div>
            </form>
        `;

        CAHModal.open({ title, subtitle, body });
    }

    document.addEventListener("dragstart", function (event) {
        const card = event.target.closest("[data-task-card]");
        if (!card) return;

        draggedCard = card;
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
        updateColumnCounts();

        if (window.CAHToast) {
            CAHToast.success("Đã cập nhật", "Trạng thái công việc đã được thay đổi trên giao diện.");
        }
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

            list.appendChild(draggedCard);
            draggedCard.dataset.status = column.dataset.status || "";
            column.classList.remove("is-over");
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

    updateColumnCounts();
})();