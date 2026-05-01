(function () {
    "use strict";

    const page = document.querySelector("[data-project-page]");
    if (!page) return;

    const grid = document.querySelector("[data-project-grid]");
    const createButton = document.querySelector("[data-project-create]");
    const refreshButton = document.querySelector("[data-project-refresh]");
    const searchInput = document.querySelector("[data-project-search]");
    const statusFilter = document.querySelector("[data-project-status-filter]");
    const filterButton = document.querySelector("[data-project-filter-apply]");

    const statTotal = document.querySelector("[data-project-stat-total]");
    const statTasks = document.querySelector("[data-project-stat-tasks]");
    const statProgress = document.querySelector("[data-project-stat-progress]");
    const statClients = document.querySelector("[data-project-stat-clients]");

    let projects = [];
    let options = {
        clients: [],
        employees: []
    };

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

    function currentUser() {
        return window.CAHAuth?.getUser?.()
            || JSON.parse(localStorage.getItem("cah_auth_user") || localStorage.getItem("cah_user") || "null");
    }

    function isManager() {
        return String(currentUser()?.role || "").toLowerCase() === "manager";
    }

    function statusLabel(status) {
        const map = {
            Active: "Đang triển khai",
            Completed: "Hoàn thành",
            Archived: "Lưu trữ"
        };

        return map[status] || status || "Đang triển khai";
    }

    function statusTone(status) {
        const map = {
            Active: "active",
            Completed: "completed",
            Archived: "archived"
        };

        return map[status] || "active";
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString("vi-VN");
    }

    function setLoading(message = "Đang tải project từ database...") {
        if (!grid) return;

        grid.innerHTML = `
            <div class="project-loading-card">
                <div class="ui-spinner"></div>
                <strong>${escapeHtml(message)}</strong>
                <p>Vui lòng chờ trong giây lát.</p>
            </div>
        `;
    }

    function setEmpty() {
        if (!grid) return;

        grid.innerHTML = `
            <div class="project-empty-card">
                <div class="project-empty-icon">▣</div>
                <h3>Chưa có project nào</h3>
                <p>Manager có thể tạo project mới, chọn client theo dõi và bắt đầu tạo task.</p>
                ${isManager() ? `<button class="btn btn-primary" type="button" data-project-create-inline>＋ Tạo project đầu tiên</button>` : ""}
            </div>
        `;
    }

    function updateStats() {
        const total = projects.length;
        const totalTasks = projects.reduce((sum, project) => sum + Number(project.task_count || 0), 0);
        const progress = total
            ? Math.round(projects.reduce((sum, project) => sum + Number(project.progress || 0), 0) / total)
            : 0;
        const clientCount = new Set(
            projects
                .map((project) => project.client_id)
                .filter(Boolean)
        ).size;

        if (statTotal) statTotal.textContent = formatNumber(total);
        if (statTasks) statTasks.textContent = formatNumber(totalTasks);
        if (statProgress) statProgress.textContent = formatNumber(progress);
        if (statClients) statClients.textContent = formatNumber(clientCount);
    }

    function renderProjects() {
        updateStats();

        if (!grid) return;

        if (!projects.length) {
            setEmpty();
            return;
        }

        grid.innerHTML = projects.map((project) => {
            const progress = Math.max(0, Math.min(100, Number(project.progress || 0)));
            const taskCount = Number(project.task_count || 0);
            const doneCount = Number(project.done_task_count || 0);
            const memberCount = Number(project.member_count || 0);
            const projectId = Number(project.id || 0);
            const clientName = project.client_name || "Chưa gán client";
            const managerName = project.manager_name || "Manager";

            return `
                <article class="project-card project-real-card" data-project-card data-project-id="${projectId}">
                    <div class="project-card-head">
                        <div class="project-card-title-row">
                            <h2>${escapeHtml(project.name)}</h2>
                            <span class="project-status-pill status-${statusTone(project.status)}">
                                ${escapeHtml(statusLabel(project.status))}
                            </span>
                        </div>

                        <p>${escapeHtml(project.description || "Chưa có mô tả project.")}</p>
                    </div>

                    <div class="project-card-meta">
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: ${progress}%;"></div>
                        </div>

                        <div class="project-progress-meta">
                            <span>${progress}% hoàn thành</span>
                            <span>${doneCount}/${taskCount} task hoàn thành</span>
                        </div>

                        <div class="project-stat-row">
                            <div class="project-mini-stat">
                                <strong>${taskCount}</strong>
                                <span>Tasks</span>
                            </div>

                            <div class="project-mini-stat">
                                <strong>${memberCount}</strong>
                                <span>Members</span>
                            </div>

                            <div class="project-mini-stat">
                                <strong>${progress}%</strong>
                                <span>Progress</span>
                            </div>
                        </div>
                    </div>

                    <div class="project-person-row">
                        <div class="project-person">
                            <span>${escapeHtml(initials(managerName))}</span>
                            <div>
                                <small>Manager</small>
                                <strong>${escapeHtml(managerName)}</strong>
                            </div>
                        </div>

                        <div class="project-person">
                            <span>${escapeHtml(initials(clientName))}</span>
                            <div>
                                <small>Client</small>
                                <strong>${escapeHtml(clientName)}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="project-card-footer">
                        <button class="btn btn-light" type="button" data-project-detail="${projectId}">
                            Xem chi tiết
                        </button>

                        <a href="${window.CAH_CONFIG?.baseUrl || "/creative-agency-hub"}/app/View/tasks/kanban.php?project_id=${projectId}" class="btn btn-primary">
                            Mở Kanban
                        </a>
                    </div>
                </article>
            `;
        }).join("");
    }

    function initials(name) {
        const parts = String(name || "")
            .trim()
            .split(/\s+/)
            .filter(Boolean);

        if (!parts.length) return "CA";

        if (parts.length === 1) {
            return parts[0].slice(0, 2).toUpperCase();
        }

        return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
    }

    function buildQuery() {
        const params = new URLSearchParams();

        const search = String(searchInput?.value || "").trim();
        const status = String(statusFilter?.value || "all").trim();

        if (search) {
            params.set("search", search);
        }

        if (status && status !== "all") {
            params.set("status", status);
        }

        return params.toString();
    }

    async function loadProjects() {
        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn?.()) {
            setEmpty();
            return;
        }

        setLoading();

        try {
            const query = buildQuery();
            const response = await CAHApi.get(`/api/projects${query ? `?${query}` : ""}`, {
                loading: false
            });

            projects = Array.isArray(response.data) ? response.data : [];
            renderProjects();
        } catch (error) {
            projects = [];
            grid.innerHTML = `
                <div class="project-empty-card is-error">
                    <div class="project-empty-icon">!</div>
                    <h3>Không tải được project</h3>
                    <p>${escapeHtml(error.message || "Vui lòng kiểm tra API /api/projects.")}</p>
                    <button class="btn btn-primary" type="button" data-project-refresh-inline>Thử lại</button>
                </div>
            `;
            updateStats();
        }
    }

    async function loadOptions() {
        if (!isManager()) return;

        try {
            const response = await CAHApi.get("/api/projects/options", {
                loading: false
            });

            options = response.data || options;
        } catch (error) {
            options = {
                clients: [],
                employees: []
            };
        }
    }

    function fillClientSelect(scope = document) {
        const select = scope.querySelector("[data-project-client-select]");
        if (!select) return;

        select.innerHTML = `<option value="">-- Chọn client --</option>`;

        options.clients.forEach((client) => {
            const option = document.createElement("option");
            option.value = client.id;
            option.textContent = `${client.full_name} · ${client.email}`;
            select.appendChild(option);
        });
    }

    async function openCreateProjectModal() {
        if (!isManager()) {
            CAHToast?.error?.("Không có quyền", "Chỉ manager được tạo project.");
            return;
        }

        await loadOptions();

        const template = document.querySelector("#projectCreateTemplate");
        if (!template || !window.CAHModal) return;

        CAHModal.open({
            title: "Tạo project mới",
            subtitle: "Project sẽ được lưu vào database thật và gán cho manager hiện tại.",
            body: template.innerHTML
        });

        const root = document.querySelector("[data-modal-root]");
        fillClientSelect(root || document);
    }

    function projectDetailHtml(project) {
        const progress = Math.max(0, Math.min(100, Number(project.progress || 0)));
        const tasks = Array.isArray(project.tasks) ? project.tasks : [];

        return `
            <div class="project-detail-modal">
                <div class="project-detail-hero">
                    <div>
                        <span class="project-status-pill status-${statusTone(project.status)}">
                            ${escapeHtml(statusLabel(project.status))}
                        </span>
                        <h2>${escapeHtml(project.name)}</h2>
                        <p>${escapeHtml(project.description || "Chưa có mô tả project.")}</p>
                    </div>

                    <div class="project-detail-progress">
                        <strong>${progress}%</strong>
                        <span>Tiến độ tổng</span>
                    </div>
                </div>

                <div class="project-detail-grid">
                    <div class="project-detail-info">
                        <span>Manager</span>
                        <strong>${escapeHtml(project.manager_name || "Chưa rõ")}</strong>
                    </div>

                    <div class="project-detail-info">
                        <span>Client</span>
                        <strong>${escapeHtml(project.client_name || "Chưa gán")}</strong>
                    </div>

                    <div class="project-detail-info">
                        <span>Task</span>
                        <strong>${Number(project.task_count || 0)}</strong>
                    </div>

                    <div class="project-detail-info">
                        <span>Thành viên</span>
                        <strong>${Number(project.member_count || 0)}</strong>
                    </div>
                </div>

                <div class="project-detail-actions">
                    <a class="btn btn-primary" href="${window.CAH_CONFIG?.baseUrl || "/creative-agency-hub"}/app/View/tasks/kanban.php?project_id=${project.id}">
                        Mở Kanban project
                    </a>
                    <a class="btn btn-light" href="${window.CAH_CONFIG?.baseUrl || "/creative-agency-hub"}/app/View/tasks/gantt.php?project_id=${project.id}">
                        Xem Gantt
                    </a>
                </div>

                <div class="project-task-preview">
                    <h3>Task trong project</h3>
                    ${
                        tasks.length
                            ? tasks.slice(0, 5).map((task) => `
                                <div class="project-task-row">
                                    <div>
                                        <strong>${escapeHtml(task.title)}</strong>
                                        <p>${escapeHtml(task.description || "Không có mô tả.")}</p>
                                    </div>
                                    <span class="project-status-pill status-${escapeHtml(String(task.status || "To do").toLowerCase().replace(/\s+/g, "-"))}">
                                        ${escapeHtml(task.status || "To do")}
                                    </span>
                                </div>
                            `).join("")
                            : `
                                <div class="project-empty-mini">
                                    Chưa có task nào. Bước tiếp theo mình sẽ nối form tạo task theo project.
                                </div>
                            `
                    }
                </div>
            </div>
        `;
    }

    async function openProjectDetail(projectId) {
        if (!window.CAHModal) return;

        CAHModal.open({
            title: "Chi tiết project",
            subtitle: "Dữ liệu đang được tải từ database.",
            body: `
                <div class="project-detail-loading">
                    <div class="ui-spinner"></div>
                    <strong>Đang tải chi tiết project...</strong>
                </div>
            `
        });

        try {
            const response = await CAHApi.get(`/api/projects/${projectId}`, {
                loading: false
            });

            const root = document.querySelector("[data-modal-root]");
            const body = root?.querySelector("[data-modal-body]");

            if (body) {
                body.innerHTML = projectDetailHtml(response.data || {});
            }
        } catch (error) {
            const root = document.querySelector("[data-modal-root]");
            const body = root?.querySelector("[data-modal-body]");

            if (body) {
                body.innerHTML = `
                    <div class="project-empty-card is-error">
                        <div class="project-empty-icon">!</div>
                        <h3>Không tải được chi tiết project</h3>
                        <p>${escapeHtml(error.message || "Vui lòng thử lại.")}</p>
                    </div>
                `;
            }
        }
    }

    async function handleCreateProject(form) {
        const data = window.CAHApp?.formToObject
            ? CAHApp.formToObject(form)
            : Object.fromEntries(new FormData(form).entries());

        if (!String(data.name || "").trim()) {
            CAHToast?.error?.("Thiếu thông tin", "Vui lòng nhập tên project.");
            return;
        }

        const submitButton = form.querySelector("[type='submit']");
        const oldText = submitButton?.innerHTML;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = "Đang tạo...";
        }

        try {
            await CAHApi.post("/api/projects", data, {
                loading: true,
                loadingMessage: "Đang tạo project..."
            });

            CAHToast?.success?.("Tạo project thành công", "Project đã được lưu vào database.");
            CAHModal?.close?.();

            await loadProjects();
        } catch (error) {
            CAHToast?.error?.("Không thể tạo project", error.message || "Vui lòng thử lại.");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = oldText || "Tạo project";
            }
        }
    }

    createButton?.addEventListener("click", openCreateProjectModal);
    refreshButton?.addEventListener("click", loadProjects);
    filterButton?.addEventListener("click", loadProjects);

    searchInput?.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            loadProjects();
        }
    });

    statusFilter?.addEventListener("change", loadProjects);

    document.addEventListener("click", function (event) {
        const inlineCreate = event.target.closest("[data-project-create-inline]");
        const inlineRefresh = event.target.closest("[data-project-refresh-inline]");
        const detailButton = event.target.closest("[data-project-detail]");

        if (inlineCreate) {
            openCreateProjectModal();
        }

        if (inlineRefresh) {
            loadProjects();
        }

        if (detailButton) {
            openProjectDetail(detailButton.dataset.projectDetail);
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-project-form]");
        if (!form) return;

        event.preventDefault();
        handleCreateProject(form);
    });

    loadProjects();
})();