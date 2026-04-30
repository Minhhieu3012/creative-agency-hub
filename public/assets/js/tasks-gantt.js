(function () {
    "use strict";

    const rangeButtons = document.querySelectorAll("[data-gantt-range]");
    const currentRange = document.querySelector("[data-current-range]");

    function setActiveRange(button) {
        rangeButtons.forEach((item) => {
            item.classList.remove("is-active");
            item.classList.remove("btn-soft");
            item.classList.add("btn-light");
        });

        button.classList.add("is-active");
        button.classList.remove("btn-light");
        button.classList.add("btn-soft");

        if (currentRange) {
            currentRange.textContent = button.dataset.ganttRange || button.textContent.trim();
        }
    }

    rangeButtons.forEach((button) => {
        button.addEventListener("click", function () {
            setActiveRange(button);

            if (window.CAHToast) {
                CAHToast.info("Đổi chế độ xem", `Đang xem lịch theo ${button.textContent.trim().toLowerCase()}.`);
            }
        });
    });

    const initialActive = document.querySelector("[data-gantt-range].is-active") || rangeButtons[0];

    if (initialActive) {
        setActiveRange(initialActive);
    }
})();