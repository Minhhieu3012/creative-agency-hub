(function () {
    "use strict";

    const dropdowns = document.querySelectorAll("[data-dropdown]");

    function closeAll(except) {
        dropdowns.forEach((dropdown) => {
            if (dropdown !== except) {
                dropdown.querySelector("[data-dropdown-menu]")?.classList.remove("is-open");
            }
        });
    }

    dropdowns.forEach((dropdown) => {
        const trigger = dropdown.querySelector("[data-dropdown-trigger]");
        const menu = dropdown.querySelector("[data-dropdown-menu]");

        trigger?.addEventListener("click", function (event) {
            event.stopPropagation();
            closeAll(dropdown);
            menu?.classList.toggle("is-open");
        });
    });

    document.addEventListener("click", function () {
        closeAll();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeAll();
    });
})();