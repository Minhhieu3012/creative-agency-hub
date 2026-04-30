<?php
$pageTitle = 'Gantt Chart | Creative Agency Hub';
$pageCss = ['tasks.css'];
$pageJs = ['tasks-gantt.js'];
$activeMenu = 'gantt';
$topbarTitle = 'Gantt Chart';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Biểu đồ Gantt';
$pageSubtitle = 'Theo dõi lịch trình, deadline và trạng thái triển khai của các đầu việc trong dự án.';
$pageAction = '
<div class="task-top-actions">
    <div class="kanban-view-switch">
        <a href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Kanban</a>
        <a class="is-active" href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a>
    </div>
    <a class="btn btn-primary" href="/creative-agency-hub/app/View/tasks/kanban.php">＋ Tạo task mới</a>
</div>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="task-shell">
    <div class="task-filter-bar">
        <select class="form-select" data-gantt-project-filter>
            <option value="">Tất cả dự án</option>
            <option value="1" selected>NexusHR Web</option>
        </select>

        <select class="form-select" data-gantt-month-filter>
            <option value="">Tất cả thời gian</option>
            <option value="current" selected>Tháng hiện tại</option>
            <option value="next">Tháng kế tiếp</option>
        </select>

        <button class="btn btn-soft is-active" type="button" data-gantt-range="week">Tuần</button>
        <button class="btn btn-light" type="button" data-gantt-range="month">Tháng</button>
        <button class="btn btn-light" type="button" data-gantt-range="quarter">Quý</button>
    </div>

    <article class="card gantt-card">
        <div class="card-header gantt-toolbar">
            <div>
                <h2 class="section-title">Lịch trình dự án</h2>
                <p class="section-subtitle">
                    Đang xem: <strong data-current-range>Tuần</strong>
                </p>
            </div>

            <div class="gantt-legend">
                <span><i class="done"></i> Hoàn thành</span>
                <span><i class="running"></i> Đang chạy</span>
                <span><i class="planned"></i> Dự kiến</span>
            </div>
        </div>

        <div class="gantt-table-wrap">
            <table class="gantt-table" data-gantt-table>
                <thead>
                    <tr>
                        <th>CÔNG VIỆC</th>
                        <th>TH 2</th>
                        <th>TH 3</th>
                        <th>TH 4</th>
                        <th>TH 5</th>
                        <th>TH 6</th>
                        <th>TH 7</th>
                        <th>CN</th>
                    </tr>
                </thead>

                <tbody data-gantt-body>
                    <tr>
                        <td colspan="8">
                            <div class="ui-empty-state" style="min-height: 220px;">
                                <div class="ui-empty-icon">▥</div>
                                <div class="ui-empty-content">
                                    <h3>Đang đồng bộ Gantt</h3>
                                    <p>Dữ liệu sẽ được tải trực tiếp từ Task/Kanban.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-body" style="text-align: center;">
            <a href="/creative-agency-hub/app/View/tasks/kanban.php" class="btn btn-soft">
                Xem bảng Kanban dự án →
            </a>
        </div>
    </article>

    <section class="project-grid" data-gantt-summary>
        <article class="quick-summary-card">
            <div>
                <span>Tổng quan lịch trình</span>
                <strong data-gantt-progress>0%</strong>
                <p data-gantt-progress-note>
                    Đang đồng bộ dữ liệu task từ hệ thống.
                </p>
            </div>
            <a class="btn btn-light" href="/creative-agency-hub/app/View/tasks/kanban.php">Xem task rủi ro</a>
        </article>

        <article class="card">
            <div class="card-body">
                <h2 class="section-title">Mốc quan trọng</h2>
                <div class="activity-timeline" style="margin-top: 24px;" data-gantt-milestones>
                    <div class="activity-item">
                        <div class="activity-icon info">…</div>
                        <div class="activity-content">
                            <strong>Đang tải dữ liệu</strong>
                            <p>Các milestone sẽ được tính từ danh sách task thật.</p>
                            <time>Đang đồng bộ</time>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="card">
            <div class="card-body">
                <h2 class="section-title">Tài nguyên tuần này</h2>
                <p class="section-subtitle" data-gantt-resource-note>
                    Dữ liệu phân bổ sẽ được suy luận từ người phụ trách task.
                </p>

                <div class="kpi-list" style="margin-top: 24px;" data-gantt-resources>
                    <div class="kpi-line">
                        <div class="kpi-line-head">
                            <span>Đang đồng bộ</span>
                            <span>0%</span>
                        </div>
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>