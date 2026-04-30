(function () {
    "use strict";

    function updateClock() {
        const clock = document.querySelector("[data-attendance-clock]");
        const dateEl = document.querySelector("[data-attendance-date]");

        if (!clock) return;

        const now = new Date();

        clock.textContent = new Intl.DateTimeFormat("vi-VN", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit"
        }).format(now);

        if (dateEl) {
            dateEl.textContent = new Intl.DateTimeFormat("vi-VN", {
                weekday: "long",
                day: "2-digit",
                month: "2-digit",
                year: "numeric"
            }).format(now);
        }
    }

    if (document.querySelector("[data-attendance-clock]")) {
        updateClock();
        setInterval(updateClock, 1000);
    }

    document.addEventListener("click", function (event) {
        const actionButton = event.target.closest("[data-payroll-action]");

        if (actionButton) {
            const action = actionButton.dataset.payrollAction;

            if (action === "check-in") {
                actionButton.disabled = true;
                actionButton.innerHTML = "Đã check-in";
                document.querySelector("[data-checkin-status]")?.classList.add("badge-success");

                if (window.CAHToast) {
                    CAHToast.success("Check-in thành công", "Giờ vào đã được ghi nhận trên giao diện demo.");
                }
            }

            if (action === "check-out") {
                actionButton.disabled = true;
                actionButton.innerHTML = "Đã check-out";

                if (window.CAHToast) {
                    CAHToast.success("Check-out thành công", "Giờ ra đã được ghi nhận trên giao diện demo.");
                }
            }

            if (action === "approve") {
                const card = actionButton.closest("[data-approval-card]");
                card?.classList.add("hidden");

                if (window.CAHToast) {
                    CAHToast.success("Đã phê duyệt", "Yêu cầu đã được duyệt trên giao diện.");
                }
            }

            if (action === "reject") {
                const card = actionButton.closest("[data-approval-card]");
                card?.classList.add("hidden");

                if (window.CAHToast) {
                    CAHToast.error("Đã từ chối", "Yêu cầu đã được từ chối trên giao diện.");
                }
            }

            if (action === "mock-save" && window.CAHToast) {
                CAHToast.success("Đã ghi nhận", "Thao tác UI đã được xử lý. Backend sẽ được nối ở bước sau.");
            }
        }

        const tab = event.target.closest("[data-approval-tab]");
        if (tab) {
            const target = tab.dataset.approvalTab;

            document.querySelectorAll("[data-approval-tab]").forEach((item) => {
                item.classList.toggle("is-active", item === tab);
            });

            document.querySelectorAll("[data-approval-panel]").forEach((panel) => {
                panel.classList.toggle("is-active", panel.dataset.approvalPanel === target);
            });
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-leave-form]");
        if (!form) return;

        event.preventDefault();

        if (window.CAHToast) {
            CAHToast.success("Đã gửi đơn nghỉ phép", "Đơn nghỉ phép đã được gửi lên quản lý trên giao diện demo.");
        }

        form.reset();
    });
})();