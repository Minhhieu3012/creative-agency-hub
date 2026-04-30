(function () {
    "use strict";

    const sidebar = document.querySelector("#appSidebar");
    const openBtn = document.querySelector("[data-sidebar-toggle]");
    const closeBtn = document.querySelector("[data-sidebar-close]");
    const backdrop = document.querySelector("[data-sidebar-backdrop]");

    if (!sidebar) return;

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

    openBtn?.addEventListener("click", openSidebar);
    closeBtn?.addEventListener("click", closeSidebar);
    backdrop?.addEventListener("click", closeSidebar);

    window.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeSidebar();
    });
})();