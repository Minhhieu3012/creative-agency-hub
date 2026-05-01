<?php
$pageTitle = 'Quản lý dự án | Creative Agency Hub';
$pageCss = ['projects.css', 'tasks.css'];
$pageJs = ['projects.js'];
$activeMenu = 'projects';
$topbarTitle = 'Dự án';
$brandName = 'Creative Agency Hub';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

ob_start();
?>

<section class="page-header">
    <div>
        <span class="page-kicker">Project Lifecycle</span>
        <h1>Quản lý Dự án</h1>
        <p>
            Manager tạo project thật, chọn client chính, sau đó tạo task trong project
            để nhiều employee cùng tham gia.
        </p>
    </div>

    <div class="page-actions">
        <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/gantt.php">
            ▥ Gantt Chart
        </a>
        <a class="btn btn-light" href="<?php echo htmlspecialchars($viewUrl); ?>/tasks/kanban.php">
            ☑ Kanban
        </a>
        <button class="btn btn-primary" type="button" data-project-create>
            ＋ Tạo Project mới
        </button>
    </div>
</section>

<section class="project-page-shell" data-project-page>
    <div class="project-stat-grid">
        <article class="project-stat-card">
            <span class="project-stat-icon">▣</span>
            <div>
                <span>Tổng dự án</span>
                <strong data-project-stat-total>0</strong>
                <small>Project manager đang quản lý</small>
            </div>
        </article>

        <article class="project-stat-card">
            <span class="project-stat-icon">☑</span>
            <div>
                <span>Task đang mở</span>
                <strong data-project-stat-tasks>0</strong>
                <small>Tổng task trong các project</small>
            </div>
        </article>

        <article class="project-stat-card">
            <span class="project-stat-icon">◔</span>
            <div>
                <span>Tiến độ TB</span>
                <strong><span data-project-stat-progress>0</span>%</strong>
                <small>Tính theo trạng thái task</small>
            </div>
        </article>

        <article class="project-stat-card project-stat-card-danger">
            <span class="project-stat-icon">◇</span>
            <div>
                <span>Client theo dõi</span>
                <strong data-project-stat-clients>0</strong>
                <small>Client chính và watcher task</small>
            </div>
        </article>
    </div>

    <div class="project-toolbar">
        <div class="project-toolbar-left">
            <label class="project-search">
                <span>⌕</span>
                <input type="search" placeholder="Tìm kiếm dự án..." data-project-search>
            </label>

            <select class="form-select" data-project-status-filter>
                <option value="all">Tất cả trạng thái</option>
                <option value="Active">Đang triển khai</option>
                <option value="Completed">Hoàn thành</option>
                <option value="Archived">Lưu trữ</option>
            </select>

            <button class="btn btn-soft" type="button" data-project-filter-apply>
                Lọc dữ liệu
            </button>
        </div>

        <div class="project-toolbar-right">
            <button class="btn btn-light" type="button" data-project-refresh>
                ↻ Làm mới
            </button>
        </div>
    </div>

    <section class="project-grid" data-project-grid>
        <div class="project-loading-card">
            <div class="ui-spinner"></div>
            <strong>Đang tải project từ database...</strong>
            <p>Vui lòng chờ trong giây lát.</p>
        </div>
    </section>
</section>

<template id="projectCreateTemplate">
    <form class="project-form" data-project-form>
        <div class="form-group">
            <label class="form-label">Tên project</label>
            <input
                class="form-control"
                type="text"
                name="name"
                placeholder="VD: Website Brand Launch"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label">Mô tả project</label>
            <textarea
                class="form-textarea"
                name="description"
                rows="4"
                placeholder="Mô tả mục tiêu, phạm vi và đầu ra của project..."
            ></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Client chính</label>
                <select class="form-select" name="client_id" data-project-client-select>
                    <option value="">-- Chọn client --</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="status">
                    <option value="Active">Đang triển khai</option>
                    <option value="Completed">Hoàn thành</option>
                    <option value="Archived">Lưu trữ</option>
                </select>
            </div>
        </div>

        <div class="project-form-note">
            <strong>Quy ước hiện tại:</strong>
            Một project có nhiều employee bằng cách tạo nhiều task trong project và gán cho các employee khác nhau.
        </div>

        <div class="task-modal-footer">
            <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
            <button class="btn btn-primary" type="submit">Tạo project</button>
        </div>
    </form>
</template>

<div class="modal-root" data-modal-root hidden>
    <div class="modal-backdrop" data-modal-close></div>

    <section class="modal-panel">
        <header class="modal-header">
            <div>
                <h2 data-modal-title>Tiêu đề</h2>
                <p data-modal-subtitle></p>
            </div>

            <button class="modal-close" type="button" data-modal-close aria-label="Đóng modal">
                ×
            </button>
        </header>

        <div class="modal-body" data-modal-body></div>
    </section>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/app/View/layouts/app.php';
?>