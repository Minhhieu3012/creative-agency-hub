(function () {
    "use strict";

    const baseUrl = "/creative-agency-hub/public";

    function getToken() {
        return localStorage.getItem("cah_token") || localStorage.getItem("cah_auth_token") || "";
    }

    function normalize(value) {
        return String(value || "").toLowerCase().trim();
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(value) {
        if (!value) return "Chưa cập nhật";

        const date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat("vi-VN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }).format(date);
    }

    function formatRelative(value) {
        if (!value) return "Chưa cập nhật";

        const date = new Date(String(value).replace(" ", "T"));

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        const diffMs = Date.now() - date.getTime();
        const diffMinutes = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMinutes / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMinutes < 1) return "Vừa xong";
        if (diffMinutes < 60) return `${diffMinutes} phút trước`;
        if (diffHours < 24) return `${diffHours} giờ trước`;
        if (diffDays < 7) return `${diffDays} ngày trước`;

        return formatDate(value);
    }

    async function callClientApi(endpoint) {
        const token = getToken();

        if (!token) {
            throw new Error("Không tìm thấy token đăng nhập client. Vui lòng đăng nhập lại.");
        }

        const response = await fetch(`${baseUrl}/api${endpoint}${endpoint.includes("?") ? "&" : "?"}_=${Date.now()}`, {
            method: "GET",
            cache: "no-store",
            headers: {
                "Authorization": "Bearer " + token,
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Cache-Control": "no-cache"
            }
        });

        const contentType = response.headers.get("content-type") || "";
        const payload = contentType.includes("application/json")
            ? await response.json()
            : { status: "error", message: await response.text() };

        if (!response.ok || payload.status === "error") {
            throw new Error(payload.message || "Không thể tải dữ liệu Client Portal.");
        }

        return payload;
    }

    function setText(selector, value) {
        document.querySelectorAll(selector).forEach((element) => {
            element.textContent = value;
        });
    }

    function renderEmpty(container, title, message) {
        if (!container) return;

        container.innerHTML = `
            <article class="card" style="padding: 24px; text-align: center; grid-column: 1 / -1;">
                <h3>${escapeHtml(title)}</h3>
                <p style="margin-top: 8px; color: #64748b;">${escapeHtml(message)}</p>
            </article>
        `;
    }

    function renderProjectCard(project) {
        const progress = Number(project.progress || 0);
        const deadline = formatDate(project.deadline);
        const tone = project.status_tone || "primary";

        return `
            <article
                class="client-project-card"
                data-client-project-card
                data-status="${escapeHtml(project.status || "in_progress")}"
            >
                <div class="client-project-card-header">
                    <div class="client-project-card-title">
                        <h2>${escapeHtml(project.name || "Dự án chưa đặt tên")}</h2>
                        <span class="badge badge-${escapeHtml(tone)}">
                            ${escapeHtml(project.status_label || "Đang triển khai")}
                        </span>
                    </div>

                    <p>${escapeHtml(project.description || "Chưa có mô tả dự án.")}</p>
                </div>

                <div class="client-project-meta">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: ${progress}%;"></div>
                    </div>

                    <div class="project-progress-meta">
                        <span>${progress}% hoàn thành</span>
                        <span>Deadline: ${escapeHtml(deadline)}</span>
                    </div>

                    <div class="client-project-stats">
                        <div class="client-project-stat">
                            <strong>${Number(project.tasks || 0)}</strong>
                            <span>Tasks</span>
                        </div>

                        <div class="client-project-stat">
                            <strong>${Number(project.done || 0)}</strong>
                            <span>Done</span>
                        </div>

                        <div class="client-project-stat">
                            <strong>${progress}%</strong>
                            <span>Progress</span>
                        </div>
                    </div>
                </div>

                <div class="client-project-footer">
                    <div class="client-manager">
                        <span class="client-manager-avatar">${escapeHtml(project.manager || "CA")}</span>
                        <span>${escapeHtml(project.manager_name || "Chưa gán quản lý")}</span>
                    </div>

                    <a class="btn btn-primary" href="/creative-agency-hub/app/View/client-portal/tasks.php?project_id=${encodeURIComponent(project.id)}">
                        Xem chi tiết
                    </a>
                </div>
            </article>
        `;
    }

    function renderUpdates(updates) {
        const list = document.querySelector("[data-client-updates-list]");

        if (!list) return;

        if (!Array.isArray(updates) || updates.length === 0) {
            list.innerHTML = `
                <div class="client-milestone">
                    <div class="client-milestone-dot">i</div>
                    <div class="client-milestone-body">
                        <h3>Chưa có cập nhật mới</h3>
                        <p>Các cập nhật từ task thật sẽ hiển thị tại đây khi dự án bắt đầu có hoạt động.</p>
                    </div>
                </div>
            `;
            return;
        }

        list.innerHTML = updates.map((update) => `
            <div class="client-milestone ${update.tone === "success" ? "is-done" : ""}">
                <div class="client-milestone-dot">${update.tone === "success" ? "✓" : "•"}</div>
                <div class="client-milestone-body">
                    <h3>${escapeHtml(update.title || "Cập nhật task")}</h3>
                    <p>${escapeHtml(update.description || "Có thay đổi mới trong dự án.")}</p>
                    <small>${escapeHtml(formatRelative(update.updated_at))}</small>
                </div>
            </div>
        `).join("");
    }

    async function loadClientProjectsPage() {
        const page = document.querySelector("[data-client-projects-page]");
        const grid = document.querySelector("[data-client-project-grid]");

        if (!page || !grid) return;

        try {
            const response = await callClientApi("/client/projects");
            const data = response.data || {};
            const summary = data.summary || {};
            const projects = Array.isArray(data.projects) ? data.projects : [];
            const updates = Array.isArray(data.updates) ? data.updates : [];

            setText('[data-client-summary="open_projects"]', Number(summary.open_projects || 0));
            setText('[data-client-summary="avg_progress"]', `${Number(summary.avg_progress || 0)}%`);
            setText('[data-client-summary="pending_feedback"]', String(Number(summary.pending_feedback || 0)).padStart(2, "0"));
            setText('[data-client-summary="last_update"]', formatRelative(summary.last_update));

            if (projects.length === 0) {
                renderEmpty(
                    grid,
                    "Chưa có dự án được chia sẻ",
                    "Tài khoản khách hàng này chưa được gán vào cột client_id của dự án nào."
                );
            } else {
                grid.innerHTML = projects.map(renderProjectCard).join("");
            }

            renderUpdates(updates);
        } catch (error) {
            renderEmpty(grid, "Không thể tải dự án", error.message);

            if (window.CAHToast) {
                CAHToast.error("Lỗi Client Portal", error.message);
            }
        }
    }

    function buildProgressSummary(project) {
        const tasks = Number(project.tasks || 0);
        const done = Number(project.done || 0);
        const open = Math.max(0, tasks - done);

        if (tasks === 0) {
            return "Dự án đã được chia sẻ với khách hàng nhưng chưa có task nào được gán vào dự án.";
        }

        return `Dự án hiện có ${tasks} task, trong đó ${done} task đã hoàn thành và ${open} task đang mở. Tiến độ được tính trực tiếp từ trạng thái task thật trong hệ thống.`;
    }

    function renderMilestones(tasks) {
        const list = document.querySelector("[data-client-milestone-list]");

        if (!list) return;

        const counters = {
            todo: 0,
            doing: 0,
            review: 0,
            done: 0
        };

        tasks.forEach((task) => {
            const status = normalize(task.status);

            if (status === "done") counters.done += 1;
            else if (status === "review") counters.review += 1;
            else if (status === "doing") counters.doing += 1;
            else counters.todo += 1;
        });

        const milestones = [
            {
                title: "Cần làm",
                desc: `${counters.todo} task đang chờ triển khai.`,
                dot: counters.todo,
                done: counters.todo === 0 && tasks.length > 0
            },
            {
                title: "Đang triển khai",
                desc: `${counters.doing} task đang được đội dự án xử lý.`,
                dot: counters.doing,
                done: counters.doing === 0 && (counters.review + counters.done) > 0
            },
            {
                title: "Đang kiểm tra",
                desc: `${counters.review} task đang ở bước review/kiểm tra.`,
                dot: counters.review,
                done: counters.review === 0 && counters.done > 0
            },
            {
                title: "Hoàn thành",
                desc: `${counters.done} task đã hoàn tất.`,
                dot: counters.done,
                done: counters.done > 0
            }
        ];

        list.innerHTML = milestones.map((item) => `
            <div class="client-milestone ${item.done ? "is-done" : ""}">
                <div class="client-milestone-dot">${item.done ? "✓" : escapeHtml(item.dot)}</div>
                <div class="client-milestone-body">
                    <h3>${escapeHtml(item.title)}</h3>
                    <p>${escapeHtml(item.desc)}</p>
                </div>
            </div>
        `).join("");
    }

    function renderTaskItem(task) {
        return `
            <article
                class="client-task-item"
                data-client-task-item
                data-status="${escapeHtml(task.tone || "info")}"
            >
                <div class="client-task-info">
                    <h3>${escapeHtml(task.title || "Task chưa đặt tên")}</h3>
                    <p>${escapeHtml(task.desc || "Không có mô tả")}</p>

                    <div class="client-task-meta">
                        <span class="badge badge-${escapeHtml(task.tone || "info")}">
                            ${escapeHtml(task.status_label || task.status || "Cần làm")}
                        </span>
                        <span class="badge badge-info">
                            Deadline: ${escapeHtml(formatDate(task.deadline))}
                        </span>
                        <span class="badge badge-primary">
                            ${escapeHtml(task.owner || "Chưa gán")}
                        </span>
                    </div>
                </div>

                <button class="btn btn-light" type="button" data-client-action="mock-download">
                    Xem
                </button>
            </article>
        `;
    }

    function renderFeedbacks(feedbacks) {
        const list = document.querySelector("[data-client-feedback-list]");

        if (!list) return;

        if (!Array.isArray(feedbacks) || feedbacks.length === 0) {
            list.innerHTML = `
                <div class="client-feedback-item">
                    <div class="client-feedback-avatar">i</div>
                    <div class="client-feedback-content">
                        <strong>Chưa có phản hồi</strong>
                        <p>Các bình luận task liên quan đến dự án sẽ hiển thị tại đây.</p>
                        <small>Dữ liệu thật từ task_comments</small>
                    </div>
                </div>
            `;
            return;
        }

        list.innerHTML = feedbacks.map((feedback) => `
            <div class="client-feedback-item">
                <div class="client-feedback-avatar">
                    ${escapeHtml(feedback.avatar || "CA")}
                </div>

                <div class="client-feedback-content">
                    <strong>${escapeHtml(feedback.name || "Creative Agency Hub")}</strong>
                    <p>${escapeHtml(feedback.message || "")}</p>
                    <small>${escapeHtml(formatRelative(feedback.time))}</small>
                </div>
            </div>
        `).join("");
    }

    async function loadClientProjectDetailPage() {
        const page = document.querySelector("[data-client-project-detail-page]");

        if (!page) return;

        const projectId = Number(page.dataset.projectId || 0);
        const taskList = document.querySelector("[data-client-task-list]");

        if (!projectId) {
            if (taskList) {
                taskList.innerHTML = `
                    <article class="client-task-item">
                        <div class="client-task-info">
                            <h3>Thiếu mã dự án</h3>
                            <p>Vui lòng quay lại trang Dự án và chọn một dự án cụ thể.</p>
                        </div>
                    </article>
                `;
            }
            return;
        }

        try {
            const response = await callClientApi(`/client/projects/${projectId}`);
            const data = response.data || {};
            const project = data.project || {};
            const tasks = Array.isArray(data.tasks) ? data.tasks : [];
            const feedbacks = Array.isArray(data.feedbacks) ? data.feedbacks : [];
            const progress = Number(project.progress || 0);

            setText('[data-client-detail="kicker"]', `Project Detail • ${project.name || "Dự án"}`);
            setText('[data-client-detail="title"]', project.name || "Chi tiết tiến độ dự án.");
            setText('[data-client-detail="description"]', project.description || "Chưa có mô tả dự án.");
            setText('[data-client-detail="status_label"]', project.status_label || "Đang triển khai");
            setText('[data-client-detail="progress"]', `${progress}%`);
            setText('[data-client-detail="deadline"]', formatDate(project.deadline));
            setText('[data-client-detail="manager_name"]', project.manager_name || "Chưa gán quản lý");
            setText('[data-client-detail="progress_circle"]', `${progress}%`);
            setText('[data-client-detail="progress_summary"]', buildProgressSummary(project));
            setText('[data-client-detail="side_name"]', project.name || "--");
            setText('[data-client-detail="created_at"]', formatDate(project.created_at));
            setText('[data-client-detail="side_deadline"]', formatDate(project.deadline));
            setText('[data-client-detail="done_ratio"]', `${Number(project.done || 0)}/${Number(project.tasks || 0)}`);
            setText('[data-client-detail="open_feedback"]', String(feedbacks.length).padStart(2, "0"));

            const progressBar = document.querySelector('[data-client-detail="progress_bar"]');
            if (progressBar) {
                progressBar.style.width = `${Math.max(0, Math.min(100, progress))}%`;
            }

            renderMilestones(tasks);

            if (taskList) {
                taskList.innerHTML = tasks.length
                    ? tasks.map(renderTaskItem).join("")
                    : `
                        <article class="client-task-item">
                            <div class="client-task-info">
                                <h3>Chưa có task được chia sẻ</h3>
                                <p>Dự án này chưa có task nào trong bảng tasks.</p>
                            </div>
                        </article>
                    `;
            }

            renderFeedbacks(feedbacks);
        } catch (error) {
            if (taskList) {
                taskList.innerHTML = `
                    <article class="client-task-item">
                        <div class="client-task-info">
                            <h3>Không thể tải chi tiết dự án</h3>
                            <p>${escapeHtml(error.message)}</p>
                        </div>
                    </article>
                `;
            }

            if (window.CAHToast) {
                CAHToast.error("Lỗi Client Portal", error.message);
            }
        }
    }

    document.addEventListener("input", function (event) {
        const input = event.target.closest("[data-client-search]");
        if (!input) return;

        const keyword = normalize(input.value);
        const targetSelector = input.dataset.clientSearch;
        const items = document.querySelectorAll(targetSelector);

        items.forEach((item) => {
            const text = normalize(item.textContent);
            item.style.display = text.includes(keyword) ? "" : "none";
        });
    });

    document.addEventListener("change", function (event) {
        const select = event.target.closest("[data-client-filter]");
        if (!select) return;

        const value = normalize(select.value);
        const targetSelector = select.dataset.clientFilter;
        const key = select.dataset.filterKey;
        const items = document.querySelectorAll(targetSelector);

        items.forEach((item) => {
            const itemValue = normalize(item.dataset[key]);
            item.style.display = !value || itemValue === value ? "" : "none";
        });
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-client-feedback-form]");
        if (!form) return;

        event.preventDefault();

        const textarea = form.querySelector("textarea");
        const message = textarea ? textarea.value.trim() : "";

        if (!message) {
            if (window.CAHToast) {
                CAHToast.error("Thiếu nội dung", "Vui lòng nhập phản hồi trước khi gửi.");
            }
            return;
        }

        const list = document.querySelector("[data-client-feedback-list]");

        if (list) {
            const item = document.createElement("div");
            item.className = "client-feedback-item";
            item.innerHTML = `
                <div class="client-feedback-avatar">C</div>
                <div class="client-feedback-content">
                    <strong>Bạn</strong>
                    <p>${escapeHtml(message)}</p>
                    <small>Vừa xong</small>
                </div>
            `;
            list.prepend(item);
        }

        form.reset();

        if (window.CAHToast) {
            CAHToast.success("Đã gửi phản hồi", "Phản hồi đã được thêm vào danh sách hiện tại. API lưu phản hồi sẽ nối ở bước sau.");
        }
    });

    document.addEventListener("click", function (event) {
        const logoutButton = event.target.closest("[data-client-logout]");

        if (logoutButton) {
            event.preventDefault();

            localStorage.removeItem("cah_token");
            localStorage.removeItem("cah_auth_token");
            localStorage.removeItem("cah_user");
            localStorage.removeItem("cah_auth_user");
            localStorage.removeItem("cah_user_role");

            window.location.href = "/creative-agency-hub/app/View/client-portal/login-client.php";
            return;
        }

        const button = event.target.closest("[data-client-action]");
        if (!button) return;

        const action = button.dataset.clientAction;

        if (action === "mock-download" && window.CAHToast) {
            CAHToast.info("Tải báo cáo", "Tính năng tải báo cáo sẽ được nối backend sau.");
        }

        if (action === "mock-support" && window.CAHToast) {
            CAHToast.success("Đã gửi yêu cầu", "Đội dự án sẽ liên hệ lại với bạn sớm.");
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        loadClientProjectsPage();
        loadClientProjectDetailPage();
    });
})();