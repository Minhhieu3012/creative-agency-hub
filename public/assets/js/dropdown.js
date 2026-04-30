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

        if (!trigger || !menu) return;

        trigger.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            const isOpen = menu.classList.contains("is-open");

            closeAll(dropdown);

            if (!isOpen) {
                menu.classList.add("is-open");
            }
        });

        menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    });

    document.addEventListener("click", function () {
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
})();