(function () {
    "use strict";

    window.CAH = window.CAH || {};

    CAH.qs = function (selector, scope) {
        return (scope || document).querySelector(selector);
    };

    CAH.qsa = function (selector, scope) {
        return Array.from((scope || document).querySelectorAll(selector));
    };

    CAH.on = function (target, event, handler) {
        if (!target) return;
        target.addEventListener(event, handler);
    };

    CAH.debounce = function (callback, wait) {
        let timeoutId;

        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => callback.apply(this, args), wait);
        };
    };

    CAH.formatCurrency = function (value) {
        const number = Number(value || 0);

        return new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
            maximumFractionDigits: 0
        }).format(number);
    };

    CAH.formatDate = function (value) {
        if (!value) return "--";

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;

        return new Intl.DateTimeFormat("vi-VN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        }).format(date);
    };

    CAH.safeFetch = async function (url, options) {
        const config = {
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || "Có lỗi xảy ra khi xử lý yêu cầu.");
            }

            return data;
        } catch (error) {
            if (window.CAHToast) {
                CAHToast.error("Không thể kết nối", error.message);
            }

            throw error;
        }
    };

    document.documentElement.classList.add("js-ready");
})();