(function () {
    "use strict";

    function escapeHtml(value) {
        if (window.CAHApp && typeof CAHApp.escapeHtml === "function") {
            return CAHApp.escapeHtml(value);
        }

        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function getStack() {
        let stack = document.querySelector("[data-toast-stack]");

        if (!stack) {
            stack = document.createElement("div");
            stack.className = "toast-stack";
            stack.setAttribute("data-toast-stack", "");
            document.body.appendChild(stack);
        }

        return stack;
    }

    function show(type, title, message, timeout = 3600) {
        const stack = getStack();
        const toast = document.createElement("div");

        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <strong>${escapeHtml(title || "Thông báo")}</strong>
            <p>${escapeHtml(message || "")}</p>
        `;

        stack.appendChild(toast);

        window.setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(10px)";
            window.setTimeout(() => toast.remove(), 180);
        }, timeout);
    }

    window.CAHToast = {
        success(title, message) {
            show("success", title, message);
        },

        error(title, message) {
            show("danger", title, message, 5200);
        },

        info(title, message) {
            show("info", title, message);
        }
    };
})();