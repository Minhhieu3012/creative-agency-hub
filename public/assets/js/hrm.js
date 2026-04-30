(function () {
    "use strict";

    function normalize(value) {
        return String(value || "").toLowerCase().trim();
    }

    document.addEventListener("input", function (event) {
        const input = event.target.closest("[data-table-search]");
        if (!input) return;

        const targetSelector = input.dataset.tableSearch;
        const table = document.querySelector(targetSelector);
        if (!table) return;

        const keyword = normalize(input.value);
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach((row) => {
            const text = normalize(row.textContent);
            row.style.display = text.includes(keyword) ? "" : "none";
        });
    });

    document.addEventListener("change", function (event) {
        const select = event.target.closest("[data-table-filter]");
        if (!select) return;

        const targetSelector = select.dataset.tableFilter;
        const key = select.dataset.filterKey;
        const table = document.querySelector(targetSelector);
        if (!table || !key) return;

        const value = normalize(select.value);
        const rows = table.querySelectorAll("tbody tr");

        rows.forEach((row) => {
            const rowValue = normalize(row.dataset[key]);
            row.style.display = !value || rowValue === value ? "" : "none";
        });
    });

    document.addEventListener("click", function (event) {
        const button = event.target.closest("[data-hrm-action]");
        if (!button) return;

        const action = button.dataset.hrmAction;

        if (action === "mock-save" && window.CAHToast) {
            CAHToast.success("Đã ghi nhận", "Thao tác UI đã được xử lý. Backend sẽ được nối ở bước sau.");
        }

        if (action === "upload-doc" && window.CAHToast) {
            CAHToast.info("Upload hồ sơ", "Khu vực upload sẽ được nối backend sau khi hoàn thiện API.");
        }
    });
})();