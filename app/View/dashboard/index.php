<?php
$pageTitle = 'Bảng điều khiển | Creative Agency Hub';
$pageCss = ['dashboard.css'];
$pageJs = ['dashboard.js'];
$activeMenu = 'dashboard';
// $topbarTitle = 'Manager Dashboard';
$topbarTitle = 'Dashboard';
$brandName = 'Creative Agency Hub';

$stats = [
    [
        'id' => 'stat-projects',
        'title' => 'Dự án đang chạy',
        'value' => 0,
        'note' => '+12% so với tháng trước',
        'icon' => '▦',
        'tone' => 'primary',
    ],
    [
        'id' => 'stat-employees',
        'title' => 'Nhân sự tham gia',
        'value' => 0,
        'note' => 'Đang hoạt động',
        'icon' => '◉',
        'tone' => 'info',
    ],
    [
        'id' => 'stat-progress',
        'title' => 'Tiến độ trung bình',
        'value' => 0,
        'note' => 'Mục tiêu tháng này',
        'icon' => '◔',
        'tone' => 'primary',
    ],
    [
        'id' => 'stat-tasks',
        'title' => 'Task quá hạn',
        'value' => 0,
        'note' => 'Cần xử lý hôm nay',
        'icon' => '!',
        'tone' => 'danger',
    ],
];

// Dữ liệu giả tĩnh (Mock) - Sẽ bị JS ghi đè ngay khi load xong
$projects = []; 
$activities = [];

$resources = $resources ?? [
    ['label' => 'Dev Team', 'value' => 82],
    ['label' => 'Design', 'value' => 66],
    ['label' => 'Marketing', 'value' => 54],
    ['label' => 'QA/QC', 'value' => 72],
];

ob_start();
?>

<?php
$pageHeading = 'Chào buổi sáng!';
$pageSubtitle = 'Dưới đây là tổng quan tình hình công việc trong ngày hôm nay của Creative Agency Hub.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="stat-grid" style="margin-bottom: 28px;">
    <?php foreach ($stats as $stat): ?>
        <article class="stat-card <?php echo $stat['tone'] === 'danger' ? 'stat-card-danger' : ''; ?>">
            <div class="stat-card-icon"><?php echo htmlspecialchars($stat['icon']); ?></div>
            <div class="stat-card-body">
                <span><?php echo htmlspecialchars($stat['title']); ?></span>

                <?php if ($stat['title'] === 'Tiến độ trung bình'): ?>
                    <strong><span id="<?php echo htmlspecialchars($stat['id']); ?>" data-count-to="<?php echo (int) $stat['value']; ?>">0</span>%</strong>
                <?php elseif ($stat['title'] === 'Task quá hạn'): ?>
                    <strong><span id="<?php echo htmlspecialchars($stat['id']); ?>" data-count-to="<?php echo (int) $stat['value']; ?>" data-pad="2">00</span></strong>
                <?php else: ?>
                    <strong id="<?php echo htmlspecialchars($stat['id']); ?>" data-count-to="<?php echo (int) $stat['value']; ?>">0</strong>
                <?php endif; ?>

                <small><?php echo htmlspecialchars($stat['note']); ?></small>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="dashboard-grid">
    <div class="dashboard-main-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Tiến độ Dự án Trọng điểm</h2>
                <a href="/creative-agency-hub/app/View/tasks/projects.php">Xem tất cả</a>
            </div>
            <div class="card-body dashboard-project-list">
                <p style="padding: 20px; color: #6c757d;">Đang tải dữ liệu...</p>
            </div>
        </article>

        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Phân bổ nguồn lực</h2>
                <a href="/creative-agency-hub/app/View/hrm/employees.php">Chi tiết</a>
            </div>

            <div class="card-body">
                <div class="resource-chart">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-bar">
                            <div class="resource-bar-track">
                                <div class="resource-bar-fill" style="height: <?php echo (int) $resource['value']; ?>%;"></div>
                            </div>
                            <strong><?php echo htmlspecialchars($resource['label']); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </div>

    <aside class="dashboard-side-column">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Hoạt động gần đây</h2>
            </div>

            <div class="card-body">
                <div class="activity-timeline">
                    <p style="padding: 10px; color: #6c757d;">Đang tải dữ liệu...</p>
                </div>

                <a href="/creative-agency-hub/app/View/tasks/activity.php" class="btn btn-soft btn-block">Xem toàn bộ nhật ký</a>
            </div>
        </article>

        <article class="quick-summary-card">
            <div>
                <span>Tình hình hôm nay</span>
                <strong>Ổn định</strong>
                <p>Không có rủi ro lớn. Ưu tiên xử lý 4 task quá hạn và kiểm tra tiến độ dự án trọng điểm.</p>
            </div>

            <a href="/creative-agency-hub/app/View/tasks/kanban.php" class="btn btn-light">Mở bảng công việc</a>
        </article>
    </aside>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token'); 

    if (!token) {
        window.location.href = '/creative-agency-hub/public/auth/login.php';
        return;
    }

    function animateRealData(element, targetValue) {
        const duration = 900;
        const start = performance.now();
        const pad = element.dataset.pad || 0;

        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const value = Math.floor(targetValue * progress);
            element.textContent = String(value).padStart(pad, "0");

            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                element.textContent = String(targetValue).padStart(pad, "0");
            }
        }
        requestAnimationFrame(tick);
    }

    fetch('/creative-agency-hub/public/api/dashboard/stats', {
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const stats = data.data;
            
            // 1. CẬP NHẬT 4 Ô SỐ LIỆU TỔNG
            const updateStat = (id, value) => {
                const oldEl = document.getElementById(id);
                if (oldEl) {
                    const newEl = oldEl.cloneNode(true);
                    oldEl.parentNode.replaceChild(newEl, oldEl);
                    animateRealData(newEl, value);
                }
            };

            updateStat('stat-projects', stats.active_projects);
            updateStat('stat-employees', stats.total_employees);
            updateStat('stat-progress', stats.avg_progress);
            updateStat('stat-tasks', stats.overdue_tasks);

            // 2. CẬP NHẬT DANH SÁCH DỰ ÁN
            const projectListEl = document.querySelector('.dashboard-project-list');
            if (projectListEl && stats.projects) {
                projectListEl.innerHTML = ''; 
                if (stats.projects.length > 0) {
                    stats.projects.forEach(project => {
                        let membersHtml = '';
                        project.members.forEach(m => {
                            membersHtml += `<span>${m}</span>`;
                        });

                        const projectHtml = `
                            <div class="project-progress-item">
                                <div class="project-progress-head">
                                    <div class="project-progress-title">
                                        <strong>${project.name}</strong>
                                        <small>Deadline: ${project.deadline}</small>
                                    </div>
                                    <div class="avatar-stack">
                                        ${membersHtml}
                                    </div>
                                </div>
                                <div class="progress-line">
                                    <div class="progress-line-fill ${project.tone}" style="width: ${project.progress}%"></div>
                                </div>
                                <div class="project-progress-meta">
                                    <span>${project.progress}% Hoàn thành</span>
                                    <span>${project.tasks}</span>
                                </div>
                            </div>
                        `;
                        projectListEl.innerHTML += projectHtml;
                    });
                } else {
                    projectListEl.innerHTML = '<p style="padding: 20px; color: #6c757d;">Hiện chưa có dự án nào đang chạy.</p>';
                }
            }

            // 3. CẬP NHẬT HOẠT ĐỘNG GẦN ĐÂY
            const activityTimelineEl = document.querySelector('.activity-timeline');
            if (activityTimelineEl && stats.activities) {
                activityTimelineEl.innerHTML = '';
                if (stats.activities.length > 0) {
                    stats.activities.forEach(act => {
                        // Lưu ý: act.description chứa HTML (<strong>, <br>) từ Controller nên in thẳng ra
                        activityTimelineEl.innerHTML += `
                            <div class="activity-item">
                                <div class="activity-icon ${act.tone}">${act.icon}</div>
                                <div class="activity-content">
                                    <strong>${act.title}</strong>
                                    <p>${act.description}</p>
                                    <time>${act.time}</time>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    activityTimelineEl.innerHTML = '<p style="padding: 10px; color: #6c757d;">Chưa có hoạt động nào trong hệ thống.</p>';
                }
            }
            
        }
    })
    .catch(error => {
        console.error('Lỗi kết nối mạng:', error);
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>