(function () {
    "use strict";

    const counters = document.querySelectorAll("[data-count-to]");

    function updateTopbarUser(user) {
        const nameEl = document.querySelector("[data-user-name]");
        const roleEl = document.querySelector("[data-user-role]");
        const avatarEl = document.querySelector("[data-user-avatar]");

        if (nameEl) nameEl.textContent = user.full_name;
        if (roleEl) roleEl.textContent = user.role;

        if (avatarEl) {
            avatarEl.textContent = user.full_name?.charAt(0)?.toUpperCase() || "U";
        }
    }
    
    async function loadCurrentUser() {
        const token = localStorage.getItem("cah_token");

        if (!token) {
            window.location.href = "/creative-agency-hub/app/View/auth/login.php";
            return;
        }

        try {
            const res = await fetch("/creative-agency-hub/public/api/auth/me", {
                headers: {
                    "Authorization": "Bearer " + token
                }
            });

            const data = await res.json();

            if (data.status !== "success") {
                throw new Error("Token lỗi");
            }

            // Gán vào UI (Chỉ gọi hàm này SAU KHI đã có data thành công)
            const user = data.data.user;
            updateTopbarUser(user);

        } catch (e) {
            localStorage.removeItem("cah_token");
            window.location.href = "/creative-agency-hub/app/View/auth/login.php";
        }
    }

    // Gọi khi load
    document.addEventListener("DOMContentLoaded", loadCurrentUser);
    
    function animateCounter(element) {
        const target = Number(element.dataset.countTo || 0);
        const duration = Number(element.dataset.duration || 900);
        const start = performance.now();

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const value = Math.floor(target * progress);

            element.textContent = String(value).padStart(element.dataset.pad || 0, "0");

            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                element.textContent = String(target).padStart(element.dataset.pad || 0, "0");
            }
        }

        requestAnimationFrame(tick);
    }

    counters.forEach(animateCounter);

    document.querySelectorAll("[data-progress]").forEach((bar) => {
        const value = Number(bar.dataset.progress || 0);
        bar.style.width = `${Math.max(0, Math.min(100, value))}%`;
    });
})();