(function () {
    "use strict";

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

    function updateClock() {
        const clock = document.querySelector("[data-attendance-clock]");
        const dateEl = document.querySelector("[data-attendance-date]");

        if (!clock) return;

        const now = new Date();

        clock.textContent = new Intl.DateTimeFormat("vi-VN", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit"
        }).format(now);

        if (dateEl) {
            dateEl.textContent = new Intl.DateTimeFormat("vi-VN", {
                weekday: "long",
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            }).format(now);
        }
    }

    function initialsFromName(name) {
        const raw = String(name || "CA").trim();
        const words = raw.split(/\s+/).filter(Boolean);

        if (words.length >= 2) {
            return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase();
        }

        return raw.slice(0, 2).toUpperCase();
    }

    function priorityBadge(priority) {
        const value = String(priority || "Medium").toLowerCase();

        if (value === "high") return "badge-danger";
        if (value === "low") return "badge-info";

        return "badge-warning";
    }

    function isOverdue(deadline) {
        if (!deadline) return false;

        const deadlineDate = new Date(`${deadline}T23:59:59`);
        const now = new Date();

        return !Number.isNaN(deadlineDate.getTime()) && deadlineDate < now;
    }

    function renderApprovalEmpty(title, description) {
        return `
            <div class="approval-empty-state">
                <div class="approval-empty-icon">✓</div>
                <strong>${escapeHtml(title)}</strong>
                <p>${escapeHtml(description)}</p>
            </div>
        `;
    }

    function renderTaskApprovalCard(task) {
        const assigneeName = task.assignee_name || "Chưa gán người thực hiện";
        const assignerName = task.assigner_name || "Người giao việc";
        const deadline = task.deadline || "Chưa có deadline";
        const priority = task.priority || "Medium";
        const overdue = isOverdue(task.deadline);

        return `
            <article class="approval-card" data-approval-card data-task-id="${escapeHtml(task.id)}">
                <div class="approval-avatar">${escapeHtml(initialsFromName(assigneeName))}</div>

                <div class="approval-content">
                    <h3>${escapeHtml(task.title || "Task chờ duyệt")}</h3>
                    <p>${escapeHtml(task.description || "Task đang ở trạng thái Review và cần manager kiểm tra trước khi chuyển Done.")}</p>

                    <div class="approval-meta">
                        <span class="badge badge-primary">${escapeHtml(task.project_name || "Task Review")}</span>
                        <span class="badge ${overdue ? "badge-danger" : "badge-info"}">${escapeHtml(deadline)}</span>
                        <span class="badge ${priorityBadge(priority)}">${escapeHtml(priority)}</span>
                        <span class="badge badge-success">Assignee: ${escapeHtml(assigneeName)}</span>
                        <span class="badge badge-info">Giao bởi: ${escapeHtml(assignerName)}</span>
                    </div>
                </div>

                <div class="approval-actions">
                    <button class="btn btn-danger-soft" type="button" data-approval-action="reject" data-task-id="${escapeHtml(task.id)}">
                        Từ chối
                    </button>
                    <button class="btn btn-light" type="button" data-approval-action="redo" data-task-id="${escapeHtml(task.id)}">
                        Yêu cầu làm lại
                    </button>
                    <button class="btn btn-primary" type="button" data-approval-action="approve" data-task-id="${escapeHtml(task.id)}">
                        Duyệt
                    </button>
                </div>
            </article>
        `;
    }

    function setApprovalStats(tasks) {
        const taskCount = document.querySelector('[data-approval-stat="tasks"]');
        const overdueCount = document.querySelector('[data-approval-stat="overdue"]');

        const total = Array.isArray(tasks) ? tasks.length : 0;
        const overdue = Array.isArray(tasks)
            ? tasks.filter((task) => isOverdue(task.deadline)).length
            : 0;

        if (taskCount) taskCount.textContent = total;
        if (overdueCount) overdueCount.textContent = overdue;
    }

    async function loadApprovalTasks() {
        const list = document.querySelector("[data-approval-task-list]");
        if (!list) return;

        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn?.()) {
            list.innerHTML = renderApprovalEmpty(
                "Chưa đăng nhập",
                "Vui lòng đăng nhập bằng tài khoản manager/admin để xem task chờ duyệt."
            );
            setApprovalStats([]);
            return;
        }

        list.innerHTML = renderApprovalEmpty(
            "Đang tải task chờ duyệt...",
            "Hệ thống đang đồng bộ task trạng thái Review từ Kanban."
        );

        try {
            const response = await CAHApi.get("/api/tasks/submit", {
                loading: false
            });

            const tasks = Array.isArray(response.data) ? response.data : [];

            setApprovalStats(tasks);

            if (!tasks.length) {
                list.innerHTML = renderApprovalEmpty(
                    "Không có task chờ duyệt",
                    "Khi nhân viên gửi task sang trạng thái Review, task sẽ xuất hiện ở đây."
                );
                return;
            }

            list.innerHTML = tasks.map(renderTaskApprovalCard).join("");
        } catch (error) {
            list.innerHTML = renderApprovalEmpty(
                "Không tải được danh sách duyệt",
                error.message || "API phê duyệt chưa phản hồi."
            );
            setApprovalStats([]);
        }
    }

    async function handleApprovalAction(button) {
        const action = button.dataset.approvalAction;
        const taskId = button.dataset.taskId;

        if (!taskId) return;

        let endpoint = "";
        let successMessage = "";

        if (action === "approve") {
            endpoint = `/api/tasks/${taskId}/approve`;
            successMessage = "Task đã được duyệt và chuyển sang Hoàn thành.";
        }

        if (action === "reject" || action === "redo") {
            endpoint = `/api/tasks/${taskId}/reject`;
            successMessage = action === "redo"
                ? "Task đã được yêu cầu làm lại và chuyển về Đang thực hiện."
                : "Task đã bị từ chối và chuyển về Đang thực hiện.";
        }

        if (!endpoint) return;

        const confirmed = action === "approve"
            ? window.confirm("Duyệt task này và chuyển sang Done?")
            : window.confirm("Từ chối task này và chuyển về Doing?");

        if (!confirmed) return;

        try {
            button.disabled = true;
            button.textContent = "Đang xử lý...";

            await CAHApi.post(endpoint, {}, {
                loading: true,
                loadingMessage: "Đang xử lý phê duyệt..."
            });

            if (window.CAHToast) {
                CAHToast.success("Đã xử lý", successMessage);
            }

            await loadApprovalTasks();

            if (window.CAHNotifications?.reload) {
                await CAHNotifications.reload();
            }
        } catch (error) {
            if (window.CAHToast) {
                CAHToast.error("Không thể xử lý", error.message || "API phê duyệt chưa phản hồi.");
            }
        } finally {
            button.disabled = false;
            button.textContent = action === "approve"
                ? "Duyệt"
                : action === "redo"
                    ? "Yêu cầu làm lại"
                    : "Từ chối";
        }
    }

    if (document.querySelector("[data-attendance-clock]")) {
        updateClock();
        setInterval(updateClock, 1000);
    }

    document.addEventListener("click", function (event) {
        const approvalButton = event.target.closest("[data-approval-action]");
        if (approvalButton) {
            event.preventDefault();
            handleApprovalAction(approvalButton);
            return;
        }

        const actionButton = event.target.closest("[data-payroll-action]");

        if (actionButton) {
            const action = actionButton.dataset.payrollAction;

            if (action === "refresh-approvals") {
                loadApprovalTasks();

                if (window.CAHNotifications?.reload) {
                    CAHNotifications.reload();
                }

                if (window.CAHToast) {
                    CAHToast.info("Đang làm mới", "Danh sách phê duyệt đang được đồng bộ lại.");
                }
                return;
            }

            if (action === "check-in") {
                actionButton.disabled = true;
                actionButton.innerHTML = "Đã check-in";
                document.querySelector("[data-checkin-status]")?.classList.add("badge-success");

                if (window.CAHToast) {
                    CAHToast.success("Check-in thành công", "Giờ vào đã được ghi nhận trên giao diện demo.");
                }
            }

            if (action === "check-out") {
                actionButton.disabled = true;
                actionButton.innerHTML = "Đã check-out";

                if (window.CAHToast) {
                    CAHToast.success("Check-out thành công", "Giờ ra đã được ghi nhận trên giao diện demo.");
                }
            }

            if (action === "approve-leave") {
                const card = actionButton.closest("[data-approval-card]");
                card?.classList.add("hidden");

                if (window.CAHToast) {
                    CAHToast.success("Đã duyệt phép", "Yêu cầu nghỉ phép đã được xử lý trên giao diện.");
                }
            }

            if (action === "reject-leave") {
                const card = actionButton.closest("[data-approval-card]");
                card?.classList.add("hidden");

                if (window.CAHToast) {
                    CAHToast.error("Đã từ chối", "Yêu cầu nghỉ phép đã được từ chối trên giao diện.");
                }
            }

            if (action === "mock-save" && window.CAHToast) {
                CAHToast.success("Đã ghi nhận", "Thao tác UI đã được xử lý. Backend sẽ được nối ở bước sau.");
            }
        }

        const tab = event.target.closest("[data-approval-tab]");
        if (tab) {
            const target = tab.dataset.approvalTab;

            document.querySelectorAll("[data-approval-tab]").forEach((item) => {
                item.classList.toggle("is-active", item === tab);
            });

            document.querySelectorAll("[data-approval-panel]").forEach((panel) => {
                panel.classList.toggle("is-active", panel.dataset.approvalPanel === target);
            });
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-leave-form]");
        if (!form) return;

        event.preventDefault();

        if (window.CAHToast) {
            CAHToast.success("Đã gửi đơn nghỉ phép", "Đơn nghỉ phép đã được gửi lên quản lý trên giao diện demo.");
        }

        form.reset();
    });

    if (document.querySelector("[data-approval-page]")) {
        loadApprovalTasks();
    }

    window.CAHApprovalCenter = {
        reload: loadApprovalTasks
    };
})();