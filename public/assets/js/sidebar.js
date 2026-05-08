(function () {
    "use strict";

    const sidebar = document.querySelector("#appSidebar");
    const openBtn = document.querySelector("[data-sidebar-toggle]");
    const closeBtn = document.querySelector("[data-sidebar-close]");
    const backdrop = document.querySelector("[data-sidebar-backdrop]");

    if (!sidebar) return;

    function isMobileShell() {
        return window.matchMedia("(max-width: 920px)").matches;
    }

    function openSidebar() {
        sidebar.classList.add("is-open");
        backdrop?.classList.add("is-open");
        document.body.classList.add("sidebar-open");
    }

    function closeSidebar() {
        sidebar.classList.remove("is-open");
        backdrop?.classList.remove("is-open");
        document.body.classList.remove("sidebar-open");
    }

    openBtn?.addEventListener("click", function () {
        openSidebar();
    });

    closeBtn?.addEventListener("click", function () {
        closeSidebar();
    });

    backdrop?.addEventListener("click", function () {
        closeSidebar();
    });

    document.addEventListener("click", function (event) {
        const link = event.target.closest("[data-sidebar-link]");
        if (!link) return;

        if (isMobileShell()) {
            closeSidebar();
        }
    });

    window.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeSidebar();
        }
    });

    window.addEventListener("resize", function () {
        if (!isMobileShell()) {
            closeSidebar();
        }
    });
})();