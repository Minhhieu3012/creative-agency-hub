(function () {
    "use strict";

    const rangeButtons = document.querySelectorAll("[data-gantt-range]");
    const currentRange = document.querySelector("[data-current-range]");
    const ganttTable = document.querySelector("[data-gantt-table]") || document.querySelector(".gantt-table");
    const projectFilter = document.querySelector("[data-gantt-project-filter]");

    const progressValue = document.querySelector("[data-gantt-progress]");
    const progressNote = document.querySelector("[data-gantt-progress-note]");
    const milestoneWrap = document.querySelector("[data-gantt-milestones]");
    const resourceWrap = document.querySelector("[data-gantt-resources]");
    const resourceNote = document.querySelector("[data-gantt-resource-note]");

    let currentMode = "week";
    let tasks = [];

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

    function escapeHtml(value) {
        return window.CAHApp?.escapeHtml
            ? CAHApp.escapeHtml(value)
            : String(value || "");
    }

    function normalizeStatus(status) {
        const value = String(status || "To do").trim().toLowerCase();

        if (value === "done" || value.includes("hoàn")) return "Done";
        if (value === "review" || value.includes("kiểm")) return "Review";
        if (value === "doing" || value.includes("thực")) return "Doing";

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
            project_name: task?.project_name || "Chưa gán dự án",
            assignee_id: task?.assignee_id || "",
            assignee_name: task?.assignee_name || "",
            assigner_name: task?.assigner_name || "",
            watcher_name: task?.watcher_name || ""
        };
    }

    function progressByStatus(status) {
        const normalized = normalizeStatus(status);

        if (normalized === "Done") return 100;
        if (normalized === "Review") return 82;
        if (normalized === "Doing") return 55;

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
        if (normalized === "Review") return "ĐANG REVIEW - 82%";
        if (normalized === "Doing") return "ĐANG CHẠY - 55%";

        return "DỰ KIẾN - 10%";
    }

    function parseDate(value) {
        if (!value) return null;

        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function activeCellIndex(task, totalCells) {
        const status = normalizeStatus(task.status);

        if (status === "Done") return 0;

        const date = parseDate(task.deadline);

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

        if (status === "Doing") return Math.min(2, totalCells - 1);
        if (status === "Review") return Math.min(3, totalCells - 1);

        return 0;
    }

    function getFilteredTasks() {
        const projectId = projectFilter?.value || "";

        if (!projectId) {
            return tasks;
        }

        return tasks.filter((task) => String(task.project_id || "") === String(projectId));
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

        if (!RANGE_LABELS[currentMode]) {
            currentMode = "week";
        }

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

        if (!window.CAHAuth?.isLoggedIn?.()) {
            renderEmpty(
                tbody,
                labels,
                "Chưa đăng nhập",
                "Vui lòng đăng nhập để đồng bộ dữ liệu Gantt từ Kanban."
            );
            renderSummary([]);
            return;
        }

        if (!Array.isArray(visibleTasks) || visibleTasks.length === 0) {
            renderEmpty(
                tbody,
                labels,
                "Chưa có dữ liệu Gantt",
                "Không có task phù hợp với bộ lọc hiện tại."
            );
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
                        <div style="margin-top: 6px; color: #8190a6; font-size: 12px;">
                            ${escapeHtml(task.deadline ? `Deadline: ${task.deadline}` : "Chưa có deadline")}
                        </div>
                        <div style="margin-top: 4px; color: #94a3b8; font-size: 12px;">
                            ${escapeHtml(task.project_name || "Chưa gán dự án")}
                        </div>
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

        const totalProgress = Math.round(
            visibleTasks.reduce((sum, task) => sum + progressByStatus(task.status), 0) / visibleTasks.length
        );

        const doneCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Done").length;
        const reviewCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Review").length;
        const doingCount = visibleTasks.filter((task) => normalizeStatus(task.status) === "Doing").length;

        progressValue.textContent = `${totalProgress}%`;
        progressNote.textContent = `Có ${visibleTasks.length} task trong hệ thống. ${doneCount} hoàn thành, ${doingCount} đang chạy, ${reviewCount} đang review.`;

        renderMilestones(visibleTasks);
        renderResources(visibleTasks);
    }

    function renderMilestones(visibleTasks) {
        if (!milestoneWrap) return;

        if (!visibleTasks.length) {
            milestoneWrap.innerHTML = `
                <div class="activity-item">
                    <div class="activity-icon info">…</div>
                    <div class="activity-content">
                        <strong>Chưa có milestone</strong>
                        <p>Tạo task trên Kanban để Gantt tự sinh milestone.</p>
                        <time>Đang chờ dữ liệu</time>
                    </div>
                </div>
            `;
            return;
        }

        const milestoneTasks = [...visibleTasks]
            .sort((a, b) => String(a.deadline || "").localeCompare(String(b.deadline || "")))
            .slice(0, 3);

        milestoneWrap.innerHTML = milestoneTasks.map((task, index) => `
            <div class="activity-item">
                <div class="activity-icon ${index === 0 ? "primary" : "info"}">${index + 1}</div>
                <div class="activity-content">
                    <strong>${escapeHtml(task.title)}</strong>
                    <p>${escapeHtml(task.description || "Không có mô tả.")}</p>
                    <time>${escapeHtml(task.deadline ? `Deadline: ${task.deadline}` : "Chưa có deadline")}</time>
                </div>
            </div>
        `).join("");
    }

    function renderResources(visibleTasks) {
        if (!resourceWrap) return;

        if (!visibleTasks.length) {
            resourceWrap.innerHTML = `
                <div class="kpi-line">
                    <div class="kpi-line-head">
                        <span>Chưa có dữ liệu</span>
                        <span>0%</span>
                    </div>
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: 0%;"></div>
                    </div>
                </div>
            `;
            return;
        }

        const counts = new Map();

        visibleTasks.forEach((task) => {
            const key = task.assignee_name || (task.assignee_id ? `Nhân sự #${task.assignee_id}` : "Chưa gán người");
            counts.set(key, (counts.get(key) || 0) + 1);
        });

        const max = Math.max(...counts.values());
        const rows = [...counts.entries()].slice(0, 4);

        if (resourceNote) {
            resourceNote.textContent = `Phân bổ dựa trên ${visibleTasks.length} task đang hiển thị.`;
        }

        resourceWrap.innerHTML = rows.map(([name, count]) => {
            const percent = Math.max(12, Math.round((count / max) * 100));

            return `
                <div class="kpi-line">
                    <div class="kpi-line-head">
                        <span>${escapeHtml(name)}</span>
                        <span>${count} task</span>
                    </div>
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: ${percent}%;"></div>
                    </div>
                </div>
            `;
        }).join("");
    }

    async function loadTasks() {
        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn()) {
            tasks = [];
            renderGantt();
            return;
        }

        try {
            const response = await CAHApi.get("/api/tasks", {
                loading: true,
                loadingMessage: "Đang đồng bộ Gantt từ Kanban..."
            });

            tasks = Array.isArray(response.data)
                ? response.data.map(normalizeTask)
                : [];

            renderGantt();
        } catch (error) {
            tasks = [];

            if (window.CAHToast) {
                CAHToast.info("Gantt chưa đồng bộ", "Không tải được dữ liệu task từ API.");
            }

            renderGantt();
        }
    }

    rangeButtons.forEach((button) => {
        button.addEventListener("click", function () {
            setActiveRange(button);

            if (window.CAHToast) {
                CAHToast.info("Đổi chế độ xem", `Đang xem lịch theo ${RANGE_NAMES[currentMode].toLowerCase()}.`);
            }
        });
    });

    projectFilter?.addEventListener("change", renderGantt);

    const initialActive = document.querySelector("[data-gantt-range].is-active") || rangeButtons[0];

    if (initialActive) {
        setActiveRange(initialActive);
    }

    loadTasks();

    window.CAHGantt = {
        reload: loadTasks
    };
})();