(function () {
    "use strict";

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
            <strong>${title || "Thông báo"}</strong>
            <p>${message || ""}</p>
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
            show("danger", title, message);
        },
        info(title, message) {
            show("info", title, message);
        }
    };
})();