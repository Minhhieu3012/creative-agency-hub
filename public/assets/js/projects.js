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

    let openedProjectId = null;

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
        try {
            return window.CAHAuth?.getUser?.()
                || JSON.parse(localStorage.getItem("cah_auth_user") || localStorage.getItem("cah_user") || "null")
                || {};
        } catch (error) {
            return {};
        }
    }

    function isManager() {
        return String(currentUser()?.role || "").toLowerCase() === "manager";
    }

    function baseUrl() {
        return window.CAH_CONFIG?.baseUrl || "/creative-agency-hub";
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

    function taskStatusTone(status) {
        return String(status || "To do")
            .toLowerCase()
            .replace(/\s+/g, "-");
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString("vi-VN");
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
                <p>Manager có thể tạo project mới, chọn client chính và bắt đầu tạo task cho nhiều employee.</p>
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
        const clientCount = projects.reduce((sum, project) => sum + Number(project.client_count || 0), 0);

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
                                <span>Employees</span>
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
                                <small>Manager chính</small>
                                <strong>${escapeHtml(managerName)}</strong>
                            </div>
                        </div>

                        <div class="project-person">
                            <span>${escapeHtml(initials(clientName))}</span>
                            <div>
                                <small>Client chính</small>
                                <strong>${escapeHtml(clientName)}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="project-card-footer">
                        <button class="btn btn-light" type="button" data-project-detail="${projectId}">
                            Xem chi tiết
                        </button>

                        <a href="${baseUrl()}/app/View/tasks/kanban.php?project_id=${projectId}" class="btn btn-primary">
                            Mở Kanban
                        </a>
                    </div>
                </article>
            `;
        }).join("");
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
            options.clients = Array.isArray(options.clients) ? options.clients : [];
            options.employees = Array.isArray(options.employees) ? options.employees : [];
        } catch (error) {
            options = {
                clients: [],
                employees: []
            };
        }
    }

    function clientOptionsHtml(selectedId = "") {
        return `
            <option value="">-- Chọn client --</option>
            ${options.clients.map((client) => `
                <option value="${escapeHtml(client.id)}" ${String(selectedId) === String(client.id) ? "selected" : ""}>
                    ${escapeHtml(client.full_name)} · ${escapeHtml(client.email)}
                </option>
            `).join("")}
        `;
    }

    function employeeOptionsHtml(selectedId = "") {
        return `
            <option value="">-- Chọn employee --</option>
            ${options.employees.map((employee) => `
                <option value="${escapeHtml(employee.id)}" ${String(selectedId) === String(employee.id) ? "selected" : ""}>
                    ${escapeHtml(employee.full_name)} · ${escapeHtml(employee.email)}
                </option>
            `).join("")}
        `;
    }

    function fillClientSelect(scope = document) {
        const select = scope.querySelector("[data-project-client-select]");
        if (!select) return;

        select.innerHTML = clientOptionsHtml();
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

    function renderPeopleList(items, type) {
        if (!Array.isArray(items) || !items.length) {
            return `
                <div class="project-empty-mini">
                    ${type === "employee"
                        ? "Chưa có employee nào. Hãy tạo task và gán employee để họ tham gia project."
                        : "Chưa có client theo dõi thêm. Client chính hoặc watcher task sẽ hiện ở đây."
                    }
                </div>
            `;
        }

        return `
            <div class="project-people-list">
                ${items.map((item) => `
                    <div class="project-people-item">
                        <span class="project-people-avatar">${escapeHtml(initials(item.full_name))}</span>
                        <div>
                            <strong>${escapeHtml(item.full_name)}</strong>
                            <small>${escapeHtml(item.email || item.employee_code || "")}</small>
                        </div>
                        <em>
                            ${type === "employee"
                                ? `${Number(item.task_count || 0)} task`
                                : (Number(item.is_primary || 0) ? "Client chính" : `${Number(item.task_count || 0)} watcher task`)
                            }
                        </em>
                    </div>
                `).join("")}
            </div>
        `;
    }

    function renderTasksList(project) {
        const tasks = Array.isArray(project.tasks) ? project.tasks : [];

        if (!tasks.length) {
            return `
                <div class="project-empty-mini">
                    Project chưa có task. Bấm “Tạo task trong project” để giao việc cho employee đầu tiên.
                </div>
            `;
        }

        return tasks.map((task) => `
            <div class="project-task-row">
                <div>
                    <strong>${escapeHtml(task.title)}</strong>
                    <p>${escapeHtml(task.description || "Không có mô tả.")}</p>
                    <small>
                        Assignee: ${escapeHtml(task.assignee_name || "Chưa gán")}
                        · Watcher: ${escapeHtml(task.watcher_name || "Không có")}
                        · Deadline: ${escapeHtml(task.deadline || "Chưa có")}
                    </small>
                </div>
                <span class="project-status-pill status-${escapeHtml(taskStatusTone(task.status))}">
                    ${escapeHtml(task.status || "To do")}
                </span>
            </div>
        `).join("");
    }

    function projectDetailHtml(project) {
        const progress = Math.max(0, Math.min(100, Number(project.progress || 0)));
        const projectId = Number(project.id || 0);

        return `
            <div class="project-detail-modal" data-project-detail-root data-project-id="${projectId}">
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
                        <span>Manager chính</span>
                        <strong>${escapeHtml(project.manager_name || "Chưa rõ")}</strong>
                    </div>

                    <div class="project-detail-info">
                        <span>Client chính</span>
                        <strong>${escapeHtml(project.client_name || "Chưa gán")}</strong>
                    </div>

                    <div class="project-detail-info">
                        <span>Task</span>
                        <strong>${Number(project.task_count || 0)}</strong>
                    </div>

                    <div class="project-detail-info">
                        <span>Employee tham gia</span>
                        <strong>${Number(project.member_count || 0)}</strong>
                    </div>
                </div>

                <div class="project-detail-actions">
                    ${isManager() ? `
                        <button class="btn btn-primary" type="button" data-project-task-create="${projectId}">
                            ＋ Tạo task trong project
                        </button>
                    ` : ""}

                    <a class="btn btn-light" href="${baseUrl()}/app/View/tasks/kanban.php?project_id=${projectId}">
                        Mở Kanban project
                    </a>
                    <a class="btn btn-light" href="${baseUrl()}/app/View/tasks/gantt.php?project_id=${projectId}">
                        Xem Gantt
                    </a>
                </div>

                <div class="project-detail-columns">
                    <section class="project-detail-section">
                        <div class="project-detail-section-head">
                            <h3>Employee trong project</h3>
                            <p>Employee được tính là tham gia khi có task thuộc project.</p>
                        </div>
                        ${renderPeopleList(project.members || [], "employee")}
                    </section>

                    <section class="project-detail-section">
                        <div class="project-detail-section-head">
                            <h3>Client theo dõi</h3>
                            <p>Gồm client chính của project và client được gán watcher ở task.</p>
                        </div>
                        ${renderPeopleList(project.clients || [], "client")}
                    </section>
                </div>

                <div class="project-task-preview">
                    <div class="project-detail-section-head">
                        <h3>Task thuộc project</h3>
                        <p>Mỗi task có thể gán employee khác nhau, nhờ đó một project có nhiều employee.</p>
                    </div>

                    ${renderTasksList(project)}
                </div>
            </div>
        `;
    }

    async function openProjectDetail(projectId) {
        if (!window.CAHModal) return;

        openedProjectId = Number(projectId || 0);

        CAHModal.open({
            title: "Chi tiết project",
            subtitle: "Employee/client trong project được tổng hợp từ task thật.",
            body: `
                <div class="project-detail-loading">
                    <div class="ui-spinner"></div>
                    <strong>Đang tải chi tiết project...</strong>
                </div>
            `
        });

        try {
            const response = await CAHApi.get(`/api/projects/${openedProjectId}`, {
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

    function createTaskFormHtml(projectId) {
        return `
            <form class="project-task-form" data-project-task-form data-project-id="${escapeHtml(projectId)}">
                <div class="form-group">
                    <label class="form-label">Tên task</label>
                    <input class="form-control" name="title" placeholder="VD: Thiết kế landing page" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mô tả task</label>
                    <textarea class="form-textarea" name="description" rows="4" placeholder="Mô tả công việc cần xử lý..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Employee phụ trách</label>
                        <select class="form-select" name="assignee_id" required>
                            ${employeeOptionsHtml()}
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client/watcher theo dõi task</label>
                        <select class="form-select" name="watcher_id">
                            ${clientOptionsHtml()}
                        </select>
                    </div>
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

                <div class="project-form-note">
                    Mỗi lần tạo task với một employee khác nhau, project sẽ có thêm employee tham gia.
                    Không cần thêm bảng mới.
                </div>

                <div class="task-modal-footer">
                    <button class="btn btn-light" type="button" data-project-detail-back="${escapeHtml(projectId)}">
                        Quay lại chi tiết
                    </button>
                    <button class="btn btn-primary" type="submit">
                        Tạo task
                    </button>
                </div>
            </form>
        `;
    }

    async function openCreateTaskInProject(projectId) {
        if (!isManager()) {
            CAHToast?.error?.("Không có quyền", "Chỉ manager được tạo task trong project.");
            return;
        }

        await loadOptions();

        const root = document.querySelector("[data-modal-root]");
        const body = root?.querySelector("[data-modal-body]");
        const title = root?.querySelector("[data-modal-title]");
        const subtitle = root?.querySelector("[data-modal-subtitle]");

        if (title) title.textContent = "Tạo task trong project";
        if (subtitle) subtitle.textContent = "Gán task cho employee. Employee sẽ tự động được tính là tham gia project.";
        if (body) body.innerHTML = createTaskFormHtml(projectId);
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

    async function handleCreateTaskInProject(form) {
        const data = window.CAHApp?.formToObject
            ? CAHApp.formToObject(form)
            : Object.fromEntries(new FormData(form).entries());

        const projectId = Number(form.dataset.projectId || openedProjectId || data.project_id || 0);

        if (!projectId) {
            CAHToast?.error?.("Thiếu project", "Không xác định được project để tạo task.");
            return;
        }

        const payload = {
            project_id: projectId,
            title: data.title,
            description: data.description || "",
            priority: data.priority || "Medium",
            deadline: data.deadline,
            assignee_id: data.assignee_id ? Number(data.assignee_id) : null,
            watcher_id: data.watcher_id ? Number(data.watcher_id) : null
        };

        if (!payload.title || !payload.deadline || !payload.assignee_id) {
            CAHToast?.error?.("Thiếu thông tin", "Vui lòng nhập tên task, deadline và employee phụ trách.");
            return;
        }

        const submitButton = form.querySelector("[type='submit']");
        const oldText = submitButton?.innerHTML;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = "Đang tạo task...";
        }

        try {
            await CAHApi.post("/api/tasks", payload, {
                loading: true,
                loadingMessage: "Đang tạo task trong project..."
            });

            CAHToast?.success?.("Đã tạo task", "Employee đã được thêm vào project thông qua task này.");
            await loadProjects();
            await openProjectDetail(projectId);
        } catch (error) {
            CAHToast?.error?.("Không thể tạo task", error.message || "Vui lòng thử lại.");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = oldText || "Tạo task";
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
        const taskCreateButton = event.target.closest("[data-project-task-create]");
        const detailBackButton = event.target.closest("[data-project-detail-back]");

        if (inlineCreate) {
            openCreateProjectModal();
        }

        if (inlineRefresh) {
            loadProjects();
        }

        if (detailButton) {
            openProjectDetail(detailButton.dataset.projectDetail);
        }

        if (taskCreateButton) {
            openCreateTaskInProject(taskCreateButton.dataset.projectTaskCreate);
        }

        if (detailBackButton) {
            openProjectDetail(detailBackButton.dataset.projectDetailBack);
        }
    });

    document.addEventListener("submit", function (event) {
        const projectForm = event.target.closest("[data-project-form]");
        const taskForm = event.target.closest("[data-project-task-form]");

        if (projectForm) {
            event.preventDefault();
            handleCreateProject(projectForm);
        }

        if (taskForm) {
            event.preventDefault();
            handleCreateTaskInProject(taskForm);
        }
    });

    loadProjects();
})();