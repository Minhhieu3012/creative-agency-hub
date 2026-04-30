(function () {
    "use strict";

    const App = {
        baseUrl: "/creative-agency-hub",

        qs(selector, scope = document) {
            return scope.querySelector(selector);
        },

        qsa(selector, scope = document) {
            return Array.from(scope.querySelectorAll(selector));
        },

        escapeHtml(value) {
            return String(value || "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        setLoading(isLoading, message) {
            let overlay = document.querySelector("[data-ui-loading-overlay]");

            if (!overlay) {
                overlay = document.createElement("div");
                overlay.className = "ui-loading-overlay";
                overlay.setAttribute("data-ui-loading-overlay", "");
                overlay.innerHTML = `
                    <div class="ui-loading-card">
                        <div class="ui-spinner"></div>
                        <strong data-ui-loading-title>Đang xử lý...</strong>
                        <p data-ui-loading-message>Vui lòng chờ trong giây lát.</p>
                    </div>
                `;
                document.body.appendChild(overlay);
            }

            const messageEl = overlay.querySelector("[data-ui-loading-message]");

            if (messageEl && message) {
                messageEl.textContent = message;
            }

            overlay.classList.toggle("is-active", Boolean(isLoading));
        },

        fakeDelay(callback, delay = 650) {
            App.setLoading(true, "Đang cập nhật giao diện demo...");

            window.setTimeout(function () {
                App.setLoading(false);

                if (typeof callback === "function") {
                    callback();
                }
            }, delay);
        },

        initExternalLinks() {
            document.querySelectorAll('a[href="#"], button[data-disabled-demo]').forEach((element) => {
                element.addEventListener("click", function (event) {
                    event.preventDefault();

                    if (window.CAHToast) {
                        CAHToast.info("Tính năng demo", "Chức năng này sẽ được nối backend ở phase tiếp theo.");
                    }
                });
            });
        },

        initScrollHints() {
            document.querySelectorAll(".table-responsive, .gantt-table-wrap, .kanban-board").forEach((element) => {
                if (element.dataset.scrollHintReady) return;

                element.dataset.scrollHintReady = "true";

                const hint = document.createElement("div");
                hint.className = "ui-mobile-scroll-hint";
                hint.textContent = "Vuốt ngang để xem thêm nội dung";

                element.parentNode?.insertBefore(hint, element.nextSibling);
            });
        },

        initProgressBars() {
            document.querySelectorAll("[data-progress]").forEach((bar) => {
                const value = Number(bar.dataset.progress || 0);
                bar.style.width = `${Math.max(0, Math.min(100, value))}%`;
            });
        },

        initPageEntrance() {
            document.body.classList.add("is-ready");
        },

        init() {
            App.initExternalLinks();
            App.initScrollHints();
            App.initProgressBars();
            App.initPageEntrance();
        }
    };

    window.CAHApp = App;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", App.init);
    } else {
        App.init();
    }
})();