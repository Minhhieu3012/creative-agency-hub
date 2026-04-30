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

    function getDropdowns() {
        return Array.from(document.querySelectorAll("[data-dropdown]"));
    }

    function closeAll(except) {
        getDropdowns().forEach((dropdown) => {
            if (dropdown !== except) {
                dropdown.querySelector("[data-dropdown-menu]")?.classList.remove("is-open");
            }
        });
    }

    function setupDropdown(dropdown) {
        if (dropdown.dataset.dropdownReady === "true") return;

        dropdown.dataset.dropdownReady = "true";

        const trigger = dropdown.querySelector("[data-dropdown-trigger]");
        const menu = dropdown.querySelector("[data-dropdown-menu]");

        if (!trigger || !menu) return;

        trigger.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = menu.classList.contains("is-open");

            closeAll(dropdown);

            if (!isOpen) {
                menu.classList.add("is-open");

                if (dropdown.matches("[data-notification-dropdown]") && window.CAHNotifications?.reload) {
                    CAHNotifications.reload();
                }
            }
        });

        menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    }

    function renderNotificationEmpty(title, text) {
        return `
            <div class="notification-empty">
                <strong>${escapeHtml(title)}</strong>
                <p>${escapeHtml(text)}</p>
            </div>
        `;
    }

    function setNotificationCount(count) {
        const badge = document.querySelector("[data-notification-count]");
        const trigger = document.querySelector("[data-notification-trigger]");

        if (!badge) return;

        const number = Number(count || 0);

        badge.textContent = number > 99 ? "99+" : String(number);
        badge.hidden = number <= 0;

        if (trigger) {
            trigger.classList.toggle("has-dot", number > 0);
        }
    }

    function renderNotifications(items) {
        const list = document.querySelector("[data-notification-list]");
        if (!list) return;

        if (!items.length) {
            list.innerHTML = renderNotificationEmpty(
                "Chưa có thông báo",
                "Các cập nhật về task, phê duyệt và hệ thống sẽ xuất hiện tại đây."
            );
            return;
        }

        list.innerHTML = items.map((item) => `
            <article
                class="notification-item ${Number(item.is_read) === 0 ? "is-unread" : ""}"
                data-notification-item
                data-notification-id="${escapeHtml(item.id)}"
            >
                <div class="notification-dot"></div>
                <div>
                    <strong>${escapeHtml(item.message || "Thông báo hệ thống")}</strong>
                    <time>${escapeHtml(item.created_at || "")}</time>
                </div>
            </article>
        `).join("");
    }

    async function loadNotifications() {
        const list = document.querySelector("[data-notification-list]");
        if (!list) return;

        if (!window.CAHApi || !window.CAHAuth?.isLoggedIn?.()) {
            setNotificationCount(0);
            list.innerHTML = renderNotificationEmpty(
                "Chưa đăng nhập",
                "Vui lòng đăng nhập để xem thông báo cá nhân."
            );
            return;
        }

        try {
            const [notificationsResponse, countResponse] = await Promise.all([
                CAHApi.get("/api/notifications?limit=8", { loading: false }),
                CAHApi.get("/api/notifications/unread-count", { loading: false })
            ]);

            const notifications = Array.isArray(notificationsResponse.data)
                ? notificationsResponse.data
                : [];

            const unread = Number(countResponse?.data?.unread || 0);

            setNotificationCount(unread);
            renderNotifications(notifications);
        } catch (error) {
            list.innerHTML = renderNotificationEmpty(
                "Không tải được thông báo",
                error.message || "API thông báo chưa phản hồi."
            );
        }
    }

    async function markNotificationAsRead(notificationId) {
        if (!notificationId || !window.CAHApi) return;

        try {
            await CAHApi.patch(`/api/notifications/${notificationId}/read`, {}, {
                loading: false
            });

            await loadNotifications();

            if (window.CAHToast) {
                CAHToast.success("Đã đọc thông báo", "Thông báo đã được đánh dấu là đã đọc.");
            }
        } catch (error) {
            if (window.CAHToast) {
                CAHToast.error("Không thể cập nhật", error.message || "API thông báo chưa phản hồi.");
            }
        }
    }

    function init() {
        getDropdowns().forEach(setupDropdown);
        loadNotifications();
    }

    document.addEventListener("click", function (event) {
        const refresh = event.target.closest("[data-notification-refresh]");
        if (refresh) {
            event.preventDefault();
            loadNotifications();
            return;
        }

        const notification = event.target.closest("[data-notification-item]");
        if (notification) {
            event.preventDefault();
            markNotificationAsRead(notification.dataset.notificationId);
            return;
        }

        closeAll();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeAll();
        }
    });

    window.addEventListener("resize", function () {
        closeAll();
    });

    window.CAHNotifications = {
        reload: loadNotifications,
        markAsRead: markNotificationAsRead
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();