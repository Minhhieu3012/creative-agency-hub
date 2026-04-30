(function () {
    "use strict";

    const counters = document.querySelectorAll("[data-count-to]");

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