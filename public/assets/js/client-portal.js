(function () {
    "use strict";

    function normalize(value) {
        return String(value || "").toLowerCase().trim();
    }

    document.addEventListener("input", function (event) {
        const input = event.target.closest("[data-client-search]");
        if (!input) return;

        const keyword = normalize(input.value);
        const targetSelector = input.dataset.clientSearch;
        const items = document.querySelectorAll(targetSelector);

        items.forEach((item) => {
            const text = normalize(item.textContent);
            item.style.display = text.includes(keyword) ? "" : "none";
        });
    });

    document.addEventListener("change", function (event) {
        const select = event.target.closest("[data-client-filter]");
        if (!select) return;

        const value = normalize(select.value);
        const targetSelector = select.dataset.clientFilter;
        const key = select.dataset.filterKey;
        const items = document.querySelectorAll(targetSelector);

        items.forEach((item) => {
            const itemValue = normalize(item.dataset[key]);
            item.style.display = !value || itemValue === value ? "" : "none";
        });
    });

    document.addEventListener("submit", function (event) {
        const form = event.target.closest("[data-client-feedback-form]");
        if (!form) return;

        event.preventDefault();

        const textarea = form.querySelector("textarea");
        const message = textarea ? textarea.value.trim() : "";

        if (!message) {
            if (window.CAHToast) {
                CAHToast.error("Thiếu nội dung", "Vui lòng nhập phản hồi trước khi gửi.");
            }
            return;
        }

        const list = document.querySelector("[data-client-feedback-list]");

        if (list) {
            const item = document.createElement("div");
            item.className = "client-feedback-item";
            item.innerHTML = `
                <div class="client-feedback-avatar">C</div>
                <div class="client-feedback-content">
                    <strong>Bạn</strong>
                    <p>${message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</p>
                    <small>Vừa xong</small>
                </div>
            `;
            list.prepend(item);
        }

        form.reset();

        if (window.CAHToast) {
            CAHToast.success("Đã gửi phản hồi", "Phản hồi của bạn đã được ghi nhận trên giao diện demo.");
        }
    });

    document.addEventListener("click", function (event) {
        const button = event.target.closest("[data-client-action]");
        if (!button) return;

        const action = button.dataset.clientAction;

        if (action === "mock-download" && window.CAHToast) {
            CAHToast.info("Tải báo cáo", "Tính năng tải báo cáo sẽ được nối backend sau.");
        }

        if (action === "mock-support" && window.CAHToast) {
            CAHToast.success("Đã gửi yêu cầu", "Đội dự án sẽ liên hệ lại với bạn sớm.");
        }
    });
})();