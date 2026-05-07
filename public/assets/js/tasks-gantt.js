(function () {
    "use strict";

    const rangeButtons = document.querySelectorAll("[data-gantt-range]");
    const currentRange = document.querySelector("[data-current-range]");
    const ganttTable = document.querySelector("[data-gantt-table]") || document.querySelector(".gantt-table");
    const projectFilter = document.querySelector("[data-gantt-project-filter]");
    const monthFilter = document.querySelector("[data-gantt-month-filter]");

    const progressValue = document.querySelector("[data-gantt-progress]");
    const progressNote = document.querySelector("[data-gantt-progress-note]");
    const milestoneWrap = document.querySelector("[data-gantt-milestones]");
    const resourceWrap = document.querySelector("[data-gantt-resources]");

    let currentMode = "week";
    let tasks = [];
    let projects = [];

    const RANGE_LABELS = {
        week: ["TH 2", "TH 3", "TH 4", "TH 5", "TH 6", "TH 7", "CN"],
        month: ["Tuần 1", "Tuần 2", "Tuần 3", "Tuần 4", "Tuần 5"],
        quarter: ["Tháng 1", "Tháng 2", "Tháng 3"]
    };

    const RANGE_NAMES = {
        week: "Tuần",
        month: "Tháng",
        quarter: "Quý"
    };

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

        return window.CAHAuth?.getUser?.() || {};
    }

    function getCurrentUserId() {
        const user = getCurrentUser();
        return toNumberOrNull(user?.id) || toNumberOrNull(user?.employee_id) || 1;
    }

    function getFallbackAssigneeId() {
        const user = getCurrentUser();
        return String(user?.role || "").toLowerCase() === "employee" ? getCurrentUserId() : 2;
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

    function normalizeStatus(status) {
        const value = String(status || "To do").trim().toLowerCase();

        if (value === "done" || value.includes("hoàn")) return "Done";
        if (value === "review" || value.includes("kiểm")) return "Review";
        if (value === "doing" || value.includes("thực")) return "Doing";
        if (value === "pending approval" || value.includes("chờ duyệt")) return "Pending approval";

        return "To do";
    }

    function normalizeTask(task) {
        return {
            id: task?.id || "",
            title: task?.title || "Chưa có tiêu đề",
            description: task?.description || "",
            status: normalizeStatus(task?.status),
            priority: task?.priority || "Medium",
            deadline: task?.deadline || "",
            project_id: task?.project_id || "",
            project_name: task?.project_name || (task?.project_id ? `Dự án #${task.project_id}` : "Chưa gán dự án"),
            assignee_id: task?.assignee_id || "",
            assignee_name: task?.assignee_name || "",
            assigner_name: task?.assigner_name || "",
            watcher_name: task?.watcher_name || "",
            created_at: task?.created_at || "",
            updated_at: task?.updated_at || ""
        };
    }

    function progressByStatus(status) {
        const normalized = normalizeStatus(status);

        if (normalized === "Done") return 100;
        if (normalized === "Review") return 82;
        if (normalized === "Doing") return 55;
        if (normalized === "Pending approval") return 0;

        return 10;
    }

    function barClass(status) {
        const normalized = normalizeStatus(status);

        if (normalized === "Done") return "done";
        if (normalized === "Doing" || normalized === "Review") return "running";

        return "planned";
    }

    function barText(task) {
        const normalized = normalizeStatus(task.status);

        if (normalized === "Done") return "HOÀN THÀNH 100%";
        if (normalized === "Review") return "ĐANG KIỂM TRA - 82%";
        if (normalized === "Doing") return "ĐANG CHẠY - 55%";
        if (normalized === "Pending approval") return "CHỜ DUYỆT - 0%";

        return "DỰ KIẾN - 10%";
    }

    function parseDate(value) {
        if (!value) return null;

        const normalized = String(value).slice(0, 10);
        const date = new Date(`${normalized}T00:00:00`);

        return Number.isNaN(date.getTime()) ? null : date;
    }

    function activeCellIndex(task, totalCells) {
        const status = normalizeStatus(task.status);

        if (status === "Done") {
            return 0;
        }

        const date = parseDate(task.deadline) || parseDate(task.created_at);

        if (date) {
            if (currentMode === "week") {
                const day = date.getDay();
                return day === 0 ? 6 : Math.max(0, day - 1);
            }

            if (currentMode === "month") {
                return Math.min(totalCells - 1, Math.max(0, Math.ceil(date.getDate() / 7) - 1));
            }

            return Math.min(totalCells - 1, Math.max(0, date.getMonth() % 3));
        }

        return 0;
    }

    function getFilteredTasks() {
        const projectId = projectFilter?.value || "";
        const monthFilterVal = monthFilter?.value || "";

        let filtered = [...tasks];

        if (projectId) {
            if (projectId === "__unassigned__") {
                filtered = filtered.filter((task) => !task.project_id);
            } else {
                filtered = filtered.filter((task) => String(task.project_id || "") === String(projectId));
            }
        }

        if (monthFilterVal) {
            const now = new Date();
            const currentMonth = now.getMonth();
            const currentYear = now.getFullYear();

            filtered = filtered.filter((task) => {
                const date = parseDate(task.deadline) || parseDate(task.created_at);
                if (!date) return false;

                const taskMonth = date.getMonth();
                const taskYear = date.getFullYear();

                if (monthFilterVal === "current") {
                    return taskMonth === currentMonth && taskYear === currentYear;
                }

                if (monthFilterVal === "next") {
                    const nextMonth = (currentMonth + 1) % 12;
                    const nextYear = currentMonth === 11 ? currentYear + 1 : currentYear;

                    return taskMonth === nextMonth && taskYear === nextYear;
                }

                return true;
            });
        }

        return filtered;
    }

    function setActiveRange(button) {
        rangeButtons.forEach((item) => {
            item.classList.remove("is-active");
            item.classList.remove("btn-soft");
            item.classList.add("btn-light");
        });

        button.classList.add("is-active");
        button.classList.remove("btn-light");
        button.classList.add("btn-soft");

        currentMode = button.dataset.ganttRange || "week";

        if (currentRange) {
            currentRange.textContent = RANGE_NAMES[currentMode] || "Tuần";
        }

        renderGantt();
    }

    function ensureTableStructure() {
        if (!ganttTable) return null;

        let thead = ganttTable.querySelector("thead");
        let tbody = ganttTable.querySelector("tbody");

        if (!thead) {
            thead = document.createElement("thead");
            ganttTable.prepend(thead);
        }

        if (!tbody) {
            tbody = document.createElement("tbody");
            ganttTable.appendChild(tbody);
        }

        return { thead, tbody };
    }

    function renderEmpty(tbody, labels, title, description) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${labels.length + 1}">
                    <div class="ui-empty-state" style="min-height: 220px;">
                        <div class="ui-empty-icon">▥</div>
                        <div class="ui-empty-content">
                            <h3>${escapeHtml(title)}</h3>
                            <p>${escapeHtml(description)}</p>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderGantt() {
        const structure = ensureTableStructure();
        if (!structure) return;

        const labels = RANGE_LABELS[currentMode] || RANGE_LABELS.week;
        const { thead, tbody } = structure;
        const visibleTasks = getFilteredTasks();

        thead.innerHTML = `
            <tr>
                <th>CÔNG VIỆC</th>
                ${labels.map((label) => `<th>${escapeHtml(label)}</th>`).join("")}
            </tr>
        `;

        if (!getToken()) {
            renderEmpty(tbody, labels, "Chưa đăng nhập", "Vui lòng đăng nhập để đồng bộ dữ liệu.");
            renderSummary([]);
            return;
        }

        if (visibleTasks.length === 0) {
            renderEmpty(tbody, labels, "Chưa có dữ liệu Gantt", "Không có task phù hợp với bộ lọc hiện tại.");
            renderSummary([]);
            return;
        }

        tbody.innerHTML = visibleTasks.map((task) => {
            const cellIndex = activeCellIndex(task, labels.length);
            const cells = labels.map((_, index) => {
                if (index !== cellIndex) {
                    return `<td class="gantt-timeline-cell"></td>`;
                }

                return `
                    <td class="gantt-timeline-cell">
                        <div class="gantt-bar ${barClass(task.status)}">${barText(task)}</div>
                    </td>
                `;
            }).join("");

            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(task.title)}</strong>
                        <div style="margin-top: 6px; color: #8190a6; font-size: 12px;">Deadline: ${escapeHtml(task.deadline || "Chưa có")}</div>
                        <div style="margin-top: 4px; color: #94a3b8; font-size: 12px;">${escapeHtml(task.project_name)}</div>
                    </td>
                    ${cells}
                </tr>
            `;
        }).join("");

        renderSummary(visibleTasks);
    }

    function renderSummary(visibleTasks) {
        if (!progressValue || !progressNote) return;

        if (!visibleTasks.length) {
            progressValue.textContent = "0%";
            progressNote.textContent = "Chưa có task để tính tiến độ.";
            renderMilestones([]);
            renderResources([]);
            return;
        }

        const totalProgress = Math.round(visibleTasks.reduce((sum, task) => sum + progressByStatus(task.status), 0) / visibleTasks.length);
        const doneCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Done").length;
        const reviewCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Review").length;
        const doingCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Doing").length;
        const pendingCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Pending approval").length;

        progressValue.textContent = `${totalProgress}%`;
        progressNote.textContent = `Có ${visibleTasks.length} task. ${doneCount} xong, ${doingCount} đang chạy, ${reviewCount} đang kiểm tra, ${pendingCount} chờ duyệt.`;

        renderMilestones(visibleTasks);
        renderResources(visibleTasks);
    }

    function renderMilestones(visibleTasks) {
        if (!milestoneWrap) return;

        if (!visibleTasks.length) {
            milestoneWrap.innerHTML = `<div class="activity-item"><div class="activity-content"><strong>Chưa có milestone</strong></div></div>`;
            return;
        }

        const milestoneTasks = [...visibleTasks]
            .sort((a, b) => String(a.deadline || "9999-12-31").localeCompare(String(b.deadline || "9999-12-31")))
            .slice(0, 3);

        milestoneWrap.innerHTML = milestoneTasks.map((task, index) => `
            <div class="activity-item">
                <div class="activity-icon ${index === 0 ? "primary" : "info"}">${index + 1}</div>
                <div class="activity-content">
                    <strong>${escapeHtml(task.title)}</strong>
                    <time>${escapeHtml(task.deadline || "Chưa có deadline")}</time>
                </div>
            </div>
        `).join("");
    }

    function renderResources(visibleTasks) {
        if (!resourceWrap) return;

        if (!visibleTasks.length) {
            resourceWrap.innerHTML = `<div class="kpi-line"><span>Chưa có dữ liệu</span></div>`;
            return;
        }

        const counts = new Map();

        visibleTasks.forEach((task) => {
            const key = task.assignee_name || (task.assignee_id ? `Nhân sự #${task.assignee_id}` : "Chưa gán");
            counts.set(key, (counts.get(key) || 0) + 1);
        });

        const max = Math.max(...counts.values());
        const rows = [...counts.entries()].slice(0, 4);

        resourceWrap.innerHTML = rows.map(([name, count]) => {
            const percent = Math.max(12, Math.round((count / max) * 100));

            return `
                <div class="kpi-line">
                    <div class="kpi-line-head"><span>${escapeHtml(name)}</span><span>${count} task</span></div>
                    <div class="progress-line"><div class="progress-line-fill" style="width: ${percent}%;"></div></div>
                </div>
            `;
        }).join("");
    }

    async function apiGet(endpoint) {
        if (window.CAHApi) {
            return CAHApi.get(endpoint, {
                loading: false,
                headers: {
                    Authorization: "Bearer " + getToken()
                }
            });
        }

        const response = await fetch(`/creative-agency-hub/public${endpoint}`, {
            cache: "no-store",
            headers: {
                Authorization: "Bearer " + getToken(),
                Accept: "application/json",
                "Cache-Control": "no-cache"
            }
        });

        const payload = await response.json();

        if (!response.ok || payload.status === "error") {
            throw new Error(payload.message || "Không thể tải dữ liệu.");
        }

        return payload;
    }

    function populateProjectFilter() {
        if (!projectFilter) return;

        const currentValue = projectFilter.value;

        projectFilter.innerHTML = `<option value="">Tất cả dự án</option>`;

        const hasUnassignedTasks = tasks.some((task) => !task.project_id);

        projects.forEach((project) => {
            if (!project || project.is_virtual) return;

            const option = document.createElement("option");
            option.value = project.id;
            option.textContent = project.name || `Dự án #${project.id}`;
            projectFilter.appendChild(option);
        });

        if (hasUnassignedTasks) {
            const option = document.createElement("option");
            option.value = "__unassigned__";
            option.textContent = "Công việc chưa gán dự án";
            projectFilter.appendChild(option);
        }

        if ([...projectFilter.options].some((option) => option.value === currentValue)) {
            projectFilter.value = currentValue;
        }
    }

    async function loadTasksAndProjects() {
        if (!getToken()) {
            tasks = [];
            projects = [];
            renderGantt();
            return;
        }

        try {
            const [taskResponse, projectResponse] = await Promise.allSettled([
                apiGet("/api/tasks?_=" + Date.now()),
                apiGet("/api/projects?_=" + Date.now())
            ]);

            if (taskResponse.status === "fulfilled") {
                tasks = Array.isArray(taskResponse.value.data)
                    ? taskResponse.value.data.map(normalizeTask)
                    : [];
            } else {
                tasks = [];
            }

            if (projectResponse.status === "fulfilled") {
                projects = Array.isArray(projectResponse.value.data)
                    ? projectResponse.value.data
                    : [];
            } else {
                projects = [];
            }

            populateProjectFilter();
            renderGantt();
        } catch (error) {
            tasks = [];
            projects = [];
            renderGantt();
        }
    }

    function openCreateTaskModal() {
        if (!window.CAHModal) return;

        const selectedProject = projectFilter?.value || "";
        const defaultProjectId = selectedProject && selectedProject !== "__unassigned__" ? selectedProject : "";

        CAHModal.open({
            title: "Tạo công việc mới",
            body: `
            <form class="task-modal-form" data-task-form data-task-form-mode="create">
                <div class="form-group">
                    <label class="form-label">Tên công việc</label>
                    <input class="form-control" type="text" name="title" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="To do">Cần làm</option>
                            <option value="Doing">Đang thực hiện</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Độ ưu tiên</label>
                        <select class="form-select" name="priority">
                            <option value="Medium">Trung bình</option>
                            <option value="High">Cao</option>
                            <option value="Low">Thấp</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input class="form-control" type="date" name="deadline" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Project ID</label>
                        <input class="form-control" type="number" name="project_id" value="${escapeHtml(defaultProjectId)}" min="1" placeholder="Để trống nếu chưa gán dự án">
                    </div>
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
                    <button class="btn btn-primary" type="submit">Tạo task</button>
                </div>
            </form>`
        });
    }

    async function createTaskFromForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        const payload = {
            title: data.title,
            deadline: data.deadline,
            project_id: toNumberOrNull(data.project_id),
            status: data.status,
            priority: data.priority,
            assignee_id: getFallbackAssigneeId(),
            watcher_id: getCurrentUserId()
        };

        await CAHApi.post("/api/tasks", payload, {
            loading: true,
            headers: {
                Authorization: "Bearer " + getToken()
            }
        });

        if (window.CAHModal) {
            CAHModal.close();
        }

        await loadTasksAndProjects();
    }

    rangeButtons.forEach((btn) => btn.addEventListener("click", () => setActiveRange(btn)));
    projectFilter?.addEventListener("change", renderGantt);
    monthFilter?.addEventListener("change", renderGantt);

    document.addEventListener("click", (e) => {
        if (e.target.closest("[data-add-task]")) {
            e.preventDefault();
            openCreateTaskModal();
        }
    });

    document.addEventListener("submit", (e) => {
        const form = e.target.closest("[data-task-form]");

        if (form) {
            e.preventDefault();
            createTaskFromForm(form);
        }
    });

    loadTasksAndProjects();
})();