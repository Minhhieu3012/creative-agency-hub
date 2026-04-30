(function () {
    "use strict";

    const rangeButtons = document.querySelectorAll("[data-gantt-range]");
    const currentRange = document.querySelector("[data-current-range]");

    rangeButtons.forEach((button) => {
        button.addEventListener("click", function () {
            rangeButtons.forEach((item) => item.classList.remove("is-active"));
            button.classList.add("is-active");

            if (currentRange) {
                currentRange.textContent = button.dataset.ganttRange || "Tuần này";
            }

            if (window.CAHToast) {
                CAHToast.info("Đổi chế độ xem", `Đang xem lịch theo ${button.textContent.trim().toLowerCase()}.`);
            }
        });
    });
})();