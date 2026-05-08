(function () {
    "use strict";

    const API_BASE = window.CAH_CONFIG?.apiRoot || "/creative-agency-hub/public";
    const VIEW_BASE = window.CAH_CONFIG?.baseUrl
        ? `${window.CAH_CONFIG.baseUrl}/app/View`
        : "/creative-agency-hub/app/View";

    function getToken() {
        return localStorage.getItem("cah_auth_token") || localStorage.getItem("cah_token") || "";
    }

    function saveUser(user) {
        if (!user || typeof user !== "object") return;

        localStorage.setItem("cah_auth_user", JSON.stringify(user));
        localStorage.setItem("cah_user", JSON.stringify(user));
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function roleLabel(role) {
        const labels = {
            admin: "ADMIN",
            manager: "MANAGER",
            employee: "EMPLOYEE",
            client: "CLIENT"
        };

        return labels[String(role || "").toLowerCase()] || "USER";
    }

    function normalizeRole(role) {
        return String(role || "employee").toLowerCase().trim();
    }

    function getRoleCopy(role) {
        const normalizedRole = normalizeRole(role);

        const copies = {
            admin: {
                topbarTitle: "Admin Dashboard",
                heading: "Chào buổi sáng, Quản trị viên!",
                subtitle: "Theo dõi tổng quan hệ thống, tài khoản và hoạt động vận hành của Creative Agency Hub.",
                statTitles: {
                    "stat-projects": "Dự án đang chạy",
                    "stat-employees": "Tài khoản hoạt động",
                    "stat-progress": "Tiến độ hệ thống",
                    "stat-tasks": "Task quá hạn"
                },
                statNotes: {
                    "stat-projects": "Toàn hệ thống",
                    "stat-employees": "Admin, Manager, Employee, Client",
                    "stat-progress": "Theo task hoàn thành",
                    "stat-tasks": "Cần theo dõi"
                },
                projectTitle: "Tổng quan dự án hệ thống",
                projectLink: `${VIEW_BASE}/tasks/projects.php`,
                projectLinkText: "Xem dự án",
                resourceTitle: "Tổng quan nguồn lực",
                resourceLink: `${VIEW_BASE}/hrm/employees.php`,
                resourceLinkText: "Chi tiết",
                summaryTitle: "Tình hình hệ thống",
                summaryStatus: "Đang vận hành",
                summaryText: "Hệ thống đang hoạt động ổn định. Ưu tiên theo dõi tài khoản, nhân sự và dữ liệu vận hành.",
                summaryLink: `${VIEW_BASE}/hrm/employees.php`,
                summaryLinkText: "Quản lý nhân sự"
            },
            manager: {
                topbarTitle: "Manager Dashboard",
                heading: "Chào buổi sáng, Quản lý!",
                subtitle: "Theo dõi dự án, công việc, nhân sự và tiến độ vận hành trong ngày hôm nay.",
                statTitles: {
                    "stat-projects": "Dự án đang chạy",
                    "stat-employees": "Nhân sự tham gia",
                    "stat-progress": "Tiến độ trung bình",
                    "stat-tasks": "Task quá hạn"
                },
                statNotes: {
                    "stat-projects": "Đang hoạt động",
                    "stat-employees": "Trong dự án quản lý",
                    "stat-progress": "Theo task hoàn thành",
                    "stat-tasks": "Cần xử lý hôm nay"
                },
                projectTitle: "Tiến độ Dự án Trọng điểm",
                projectLink: `${VIEW_BASE}/tasks/projects.php`,
                projectLinkText: "Xem tất cả",
                resourceTitle: "Phân bổ nguồn lực",
                resourceLink: `${VIEW_BASE}/hrm/employees.php`,
                resourceLinkText: "Chi tiết",
                summaryTitle: "Tình hình hôm nay",
                summaryStatus: "Ổn định",
                summaryText: "Ưu tiên kiểm tra tiến độ dự án, task quá hạn và hoạt động của nhân sự trong nhóm.",
                summaryLink: `${VIEW_BASE}/tasks/kanban.php`,
                summaryLinkText: "Mở bảng công việc"
            },
            employee: {
                topbarTitle: "Employee Dashboard",
                heading: "Chào buổi sáng, Nhân viên!",
                subtitle: "Theo dõi công việc được giao, tiến độ cá nhân, chấm công và các đầu việc cần xử lý.",
                statTitles: {
                    "stat-projects": "Dự án tham gia",
                    "stat-employees": "Task được giao",
                    "stat-progress": "Tiến độ của tôi",
                    "stat-tasks": "Task quá hạn"
                },
                statNotes: {
                    "stat-projects": "Có task liên quan",
                    "stat-employees": "Tổng task cá nhân",
                    "stat-progress": "Task đã hoàn thành",
                    "stat-tasks": "Cần xử lý"
                },
                projectTitle: "Công việc & Dự án của tôi",
                projectLink: `${VIEW_BASE}/tasks/kanban.php`,
                projectLinkText: "Mở Kanban",
                resourceTitle: "Tình trạng công việc cá nhân",
                resourceLink: `${VIEW_BASE}/payroll/attendance.php`,
                resourceLinkText: "Chấm công",
                summaryTitle: "Việc cần ưu tiên",
                summaryStatus: "Tập trung",
                summaryText: "Kiểm tra task được giao, cập nhật trạng thái đúng hạn và hoàn tất chấm công trong ngày.",
                summaryLink: `${VIEW_BASE}/tasks/kanban.php`,
                summaryLinkText: "Xem task của tôi"
            },
            client: {
                topbarTitle: "Client Portal",
                heading: "Chào mừng Khách hàng!",
                subtitle: "Theo dõi tiến độ dự án và các công việc liên quan trong cổng khách hàng.",
                statTitles: {
                    "stat-projects": "Dự án của tôi",
                    "stat-employees": "Task liên quan",
                    "stat-progress": "Tiến độ dự án",
                    "stat-tasks": "Task quá hạn"
                },
                statNotes: {
                    "stat-projects": "Được phép theo dõi",
                    "stat-employees": "Đang triển khai",
                    "stat-progress": "Theo task hoàn thành",
                    "stat-tasks": "Cần cập nhật"
                },
                projectTitle: "Dự án của tôi",
                projectLink: `${VIEW_BASE}/client-portal/projects.php`,
                projectLinkText: "Xem dự án",
                resourceTitle: "Tổng quan tiến độ",
                resourceLink: `${VIEW_BASE}/client-portal/tasks.php`,
                resourceLinkText: "Xem task",
                summaryTitle: "Trạng thái dự án",
                summaryStatus: "Đang theo dõi",
                summaryText: "Bạn có thể xem tiến độ và trạng thái công việc liên quan đến dự án của mình.",
                summaryLink: `${VIEW_BASE}/client-portal/projects.php`,
                summaryLinkText: "Client Portal"
            }
        };

        return copies[normalizedRole] || copies.employee;
    }

    function setText(selector, text) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = text;
        }
    }

    function setLink(selector, href, text) {
        const element = document.querySelector(selector);

        if (!element) return;

        element.href = href;
        element.textContent = text;
    }

    function applyTopbarUser(user) {
        if (!user || typeof user !== "object") return;

        if (window.CAHApp && typeof window.CAHApp.applyUserToTopbar === "function") {
            window.CAHApp.applyUserToTopbar(user);
            return;
        }

        setText("[data-user-name]", user.full_name || user.name || "Người dùng");
        setText("[data-user-role]", roleLabel(user.role));

        const avatarEl = document.querySelector("[data-user-avatar]");
        const initial = String(user.full_name || user.name || "U").charAt(0).toUpperCase();

        if (avatarEl && avatarEl.tagName.toLowerCase() !== "img") {
            avatarEl.textContent = initial;
        }
    }

    function applyDashboardCopy(role) {
        const copy = getRoleCopy(role);

        setText(".topbar-title", copy.topbarTitle);
        setText(".page-header h1", copy.heading);
        setText(".page-header p", copy.subtitle);

        Object.entries(copy.statTitles).forEach(([id, title]) => {
            setText(`[data-stat-title="${id}"]`, title);
        });

        Object.entries(copy.statNotes).forEach(([id, note]) => {
            setText(`[data-stat-note="${id}"]`, note);
        });

        setText("[data-dashboard-project-title]", copy.projectTitle);
        setLink("[data-dashboard-project-link]", copy.projectLink, copy.projectLinkText);

        setText("[data-dashboard-resource-title]", copy.resourceTitle);
        setLink("[data-dashboard-resource-link]", copy.resourceLink, copy.resourceLinkText);

        setText("[data-dashboard-summary-title]", copy.summaryTitle);
        setText("[data-dashboard-summary-status]", copy.summaryStatus);
        setText("[data-dashboard-summary-text]", copy.summaryText);
        setLink("[data-dashboard-summary-link]", copy.summaryLink, copy.summaryLinkText);
    }

    function animateCounter(element, targetValue) {
        if (!element) return;

        const target = Number(targetValue || 0);
        const duration = Number(element.dataset.duration || 900);
        const pad = Number(element.dataset.pad || 0);
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const value = Math.floor(target * progress);

            element.textContent = String(value).padStart(pad, "0");

            if (progress < 1) {
                requestAnimationFrame(tick);
                return;
            }

            element.textContent = String(target).padStart(pad, "0");
        }

        requestAnimationFrame(tick);
    }

    function updateStat(id, value) {
        const element = document.getElementById(id);

        if (!element) return;

        const nextElement = element.cloneNode(true);
        element.parentNode.replaceChild(nextElement, element);
        animateCounter(nextElement, value);
    }

    function renderProjects(projects) {
        const projectListEl = document.querySelector(".dashboard-project-list");

        if (!projectListEl) return;

        projectListEl.innerHTML = "";

        if (!Array.isArray(projects) || projects.length === 0) {
            projectListEl.innerHTML = '<p style="padding: 20px; color: #6c757d;">Chưa có dữ liệu dự án hoặc công việc phù hợp.</p>';
            return;
        }

        projects.forEach((project) => {
            const members = Array.isArray(project.members) ? project.members : [];
            const membersHtml = members.map((member) => `<span>${escapeHtml(member)}</span>`).join("");

            const projectHtml = `
                <div class="project-progress-item">
                    <div class="project-progress-head">
                        <div class="project-progress-title">
                            <strong>${escapeHtml(project.name)}</strong>
                            <small>Deadline: ${escapeHtml(project.deadline || "Chưa đặt")}</small>
                        </div>
                        <div class="avatar-stack">
                            ${membersHtml}
                        </div>
                    </div>

                    <div class="progress-line">
                        <div class="progress-line-fill ${escapeHtml(project.tone || "primary")}" style="width: ${Number(project.progress || 0)}%"></div>
                    </div>

                    <div class="project-progress-meta">
                        <span>${Number(project.progress || 0)}% Hoàn thành</span>
                        <span>${escapeHtml(project.tasks || "0/0 Tasks")}</span>
                    </div>
                </div>
            `;

            projectListEl.insertAdjacentHTML("beforeend", projectHtml);
        });
    }

    function renderActivities(activities) {
        const activityTimelineEl = document.querySelector(".activity-timeline");

        if (!activityTimelineEl) return;

        activityTimelineEl.innerHTML = "";

        if (!Array.isArray(activities) || activities.length === 0) {
            activityTimelineEl.innerHTML = '<p style="padding: 10px; color: #6c757d;">Chưa có hoạt động nào trong hệ thống.</p>';
            return;
        }

        activities.forEach((activity) => {
            const description = activity.description_html || escapeHtml(activity.description || "");

            const html = `
                <div class="activity-item">
                    <div class="activity-icon ${escapeHtml(activity.tone || "secondary")}">${escapeHtml(activity.icon || "❖")}</div>
                    <div class="activity-content">
                        <strong>${escapeHtml(activity.title || "Cập nhật")}</strong>
                        <p>${description}</p>
                        <time>${escapeHtml(activity.time || "")}</time>
                    </div>
                </div>
            `;

            activityTimelineEl.insertAdjacentHTML("beforeend", html);
        });
    }

    function renderResourceChart(resources) {
        const chart = document.querySelector("[data-resource-chart]");

        if (!chart || !Array.isArray(resources) || resources.length === 0) return;

        chart.innerHTML = "";

        resources.forEach((resource) => {
            const value = Math.max(0, Math.min(100, Number(resource.value || 0)));

            const html = `
                <div class="resource-bar">
                    <div class="resource-bar-track">
                        <div class="resource-bar-fill" style="height: ${value}%;"></div>
                    </div>
                    <strong>${escapeHtml(resource.label || "")}</strong>
                </div>
            `;

            chart.insertAdjacentHTML("beforeend", html);
        });
    }

    async function requestJson(path) {
        const headers = {
            Accept: "application/json"
        };

        const token = getToken();

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`${API_BASE}${path}`, {
            method: "GET",
            headers,
            credentials: "same-origin"
        });

        const payload = await response.json().catch(() => ({
            status: "error",
            message: "Không đọc được phản hồi từ server."
        }));

        if (!response.ok || payload.status === "error") {
            throw new Error(payload.message || "Yêu cầu không thành công.");
        }

        return payload;
    }

    async function loadCurrentUser() {
        const payload = await requestJson("/api/auth/me");
        const user = payload?.data?.user || null;

        if (!user) {
            throw new Error("Không tìm thấy thông tin người dùng.");
        }

        saveUser(user);
        applyTopbarUser(user);
        applyDashboardCopy(user.role);

        return user;
    }

    async function loadDashboardStats() {
        const payload = await requestJson("/api/dashboard/stats");
        const stats = payload?.data || {};

        updateStat("stat-projects", stats.active_projects || 0);
        updateStat("stat-employees", stats.total_employees || 0);
        updateStat("stat-progress", stats.avg_progress || 0);
        updateStat("stat-tasks", stats.overdue_tasks || 0);

        renderProjects(stats.projects || []);
        renderActivities(stats.activities || []);
        renderResourceChart(stats.resources || []);
    }

    async function initDashboard() {
        try {
            const serverUser = window.CAH_CURRENT_USER || null;

            if (serverUser && serverUser.role) {
                applyDashboardCopy(serverUser.role);
            }

            await loadCurrentUser();
            await loadDashboardStats();
        } catch (error) {
            console.error("Dashboard error:", error);

            const message = String(error.message || "");

            if (message.includes("Phiên") || message.includes("Unauthorized") || message.includes("Token")) {
                localStorage.removeItem("cah_auth_token");
                localStorage.removeItem("cah_token");
                localStorage.removeItem("cah_auth_user");
                localStorage.removeItem("cah_user");
                window.location.href = "/creative-agency-hub/app/View/auth/login.php";
                return;
            }

            renderProjects([]);
            renderActivities([]);

            if (window.CAHToast) {
                CAHToast.error("Không thể tải dashboard", message || "Có lỗi xảy ra khi tải dữ liệu.");
            }
        }
    }

    document.querySelectorAll("[data-count-to]").forEach((counter) => {
        animateCounter(counter, Number(counter.dataset.countTo || 0));
    });

    document.querySelectorAll("[data-progress]").forEach((bar) => {
        const value = Number(bar.dataset.progress || 0);
        bar.style.width = `${Math.max(0, Math.min(100, value))}%`;
    });

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboard);
    } else {
        initDashboard();
    }
})();