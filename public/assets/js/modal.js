(function () {
    "use strict";

    const modalRoot = document.querySelector("[data-modal-root]");

    function ensureModal() {
        if (modalRoot) return modalRoot;

        const root = document.createElement("div");
        root.className = "modal-root";
        root.setAttribute("data-modal-root", "");

        root.innerHTML = `
            <div class="modal-backdrop" data-modal-close></div>
            <section class="modal-panel" role="dialog" aria-modal="true">
                <button class="modal-close" type="button" data-modal-close aria-label="Đóng">×</button>
                <div class="modal-header">
                    <h3 data-modal-title>Tiêu đề</h3>
                    <p data-modal-subtitle></p>
                </div>
                <div class="modal-body" data-modal-body></div>
            </section>
        `;

        document.body.appendChild(root);
        return root;
    }

    function openModal(options) {
        const root = ensureModal();
        const title = root.querySelector("[data-modal-title]");
        const subtitle = root.querySelector("[data-modal-subtitle]");
        const body = root.querySelector("[data-modal-body]");

        title.textContent = options?.title || "Thông tin";
        subtitle.textContent = options?.subtitle || "";
        body.innerHTML = options?.body || "";

        root.classList.add("is-open");
        document.body.classList.add("modal-open");
    }

    function closeModal() {
        const root = document.querySelector("[data-modal-root]");
        if (!root) return;

        root.classList.remove("is-open");
        document.body.classList.remove("modal-open");
    }

    document.addEventListener("click", function (event) {
        const openBtn = event.target.closest("[data-modal-open]");
        const closeBtn = event.target.closest("[data-modal-close]");

        if (openBtn) {
            const title = openBtn.dataset.modalTitle || "Thông tin";
            const subtitle = openBtn.dataset.modalSubtitle || "";
            const bodySelector = openBtn.dataset.modalBody;
            const bodyTemplate = bodySelector ? document.querySelector(bodySelector) : null;

            openModal({
                title,
                subtitle,
                body: bodyTemplate ? bodyTemplate.innerHTML : "<p>Chưa có nội dung.</p>"
            });
        }

        if (closeBtn) {
            closeModal();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeModal();
    });

    window.CAHModal = {
        open: openModal,
        close: closeModal
    };
})();