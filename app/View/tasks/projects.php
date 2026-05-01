<?php
$pageTitle = 'Quản lý dự án | Creative Agency Hub';
$pageCss = ['projects.css', 'tasks.css'];
$pageJs = ['projects.js'];
$activeMenu = 'projects';
$topbarTitle = 'Dự án';
$brandName = 'Creative Agency Hub';

$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

ob_start();
?>

<?php
$pageHeading = 'Quản lý Dự án';
$pageSubtitle = 'Manager tạo project thật, chọn client theo dõi và chuẩn bị luồng tạo task theo project.';
$pageAction = '
    <a class="btn btn-light" href="' . htmlspecialchars($viewUrl) . '/tasks/gantt.php">▥ Gantt Chart</a>
    <a class="btn btn-light" href="' . htmlspecialchars($viewUrl) . '/tasks/kanban.php">☑ Kanban</a>
    <button class="btn btn-primary" type="button" data-project-create>＋ Tạo Project mới</button>
';
require __DIR__ . '/../components/page-header.php';
?>

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
                <span>Client đang theo dõi</span>
                <strong data-project-stat-clients>0</strong>
                <small>Tài khoản client được gán</small>
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
                <label class="form-label">Client theo dõi</label>
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
            <strong>Ghi chú:</strong>
            Employee sẽ được đưa vào project thông qua task được giao trong phase kế tiếp.
            Schema hiện tại chưa có bảng project_members nên chưa làm invite link thật ở bước này.
        </div>

        <div class="task-modal-footer">
            <button class="btn btn-light" type="button" data-modal-close>Đóng</button>
            <button class="btn btn-primary" type="submit">Tạo project</button>
        </div>
    </form>
</template>

<template id="projectDetailTemplate">
    <div class="project-detail-modal" data-project-detail-modal>
        <div class="project-detail-loading">
            <div class="ui-spinner"></div>
            <strong>Đang tải chi tiết project...</strong>
        </div>
    </div>
</template>

<?php
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>