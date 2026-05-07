<?php
$pageTitle = 'Bảng Kanban | Creative Agency Hub';
$pageCss = ['tasks.css'];
$pageJs = ['tasks-kanban.js']; // File này sau bạn có thể dùng để viết logic Drag & Drop
$activeMenu = 'kanban';
$topbarTitle = 'Task Board';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Bảng Công việc';
$pageSubtitle = 'Quản lý và theo dõi tiến độ dự án Creative Agency Hub theo từng trạng thái.';
$pageAction = '
<div class="task-top-actions">
    <div class="kanban-view-switch">
        <a class="is-active" href="/creative-agency-hub/app/View/tasks/kanban.php">☑ Kanban</a>
        <a href="/creative-agency-hub/app/View/tasks/gantt.php">▥ Gantt Chart</a>
    </div>
    <button class="btn btn-primary" type="button" data-add-task>＋ Tạo Task mới</button>
</div>';
require __DIR__ . '/../components/page-header.php';
?>

<style>
    .kanban-board {
        grid-template-columns: repeat(5, minmax(260px, 1fr));
    }

    .kanban-dot.pending {
        background: #f59e0b;
    }

    .task-card.is-pending-approval {
        border-color: rgba(245, 158, 11, .35);
        background: linear-gradient(180deg, rgba(255, 251, 235, .72), #fff);
    }

    @media (max-width: 1280px) {
        .kanban-board {
            grid-template-columns: repeat(5, minmax(280px, 1fr));
            overflow-x: auto;
        }
    }
</style>

<section class="kanban-shell">
    <div class="task-filter-bar">
        <!-- Các bộ lọc này sẽ được nâng cấp thành API động trong tương lai -->
        <select id="js-filter-project" class="form-select" data-project-filter>
            <option value="">Dự án: Tất cả</option>
        </select>

        <select id="js-filter-assignee" class="form-select">
            <option value="">Người phụ trách: Tất cả</option>
        </select>

        <select id="js-filter-time" class="form-select">
            <option value="">Thời gian: Tất cả</option>
        </select>

        <button class="btn btn-soft" type="button" id="js-btn-filter">Lọc task</button>
    </div>

    <!-- Khu vực hiển thị lỗi nếu API sập -->
    <div id="js-board-message" style="display: none; padding: 20px; text-align: center; margin-bottom: 20px; border-radius: 8px;"></div>

    <div class="kanban-board" data-kanban-board>
        <!-- CỘT 0: CHỜ DUYỆT -->
        <section class="kanban-column" data-kanban-column data-status="pending">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot pending"></span>
                    <span>Chờ duyệt</span>
                    <span class="kanban-count" id="js-count-pending" data-column-count>0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-pending" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>

            <!-- ĐÃ CHUYỂN NÚT + THÊM TASK SANG CỘT CHỜ DUYỆT -->
            <button class="task-add-card" type="button" data-add-task>＋ Thêm Task</button>
        </section>

        <!-- CỘT 1: CẦN LÀM -->
        <section class="kanban-column" data-kanban-column data-status="todo">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot todo"></span>
                    <span>Cần làm</span>
                    <span class="kanban-count" id="js-count-todo" data-column-count>0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-todo" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>

        <!-- CỘT 2: ĐANG THỰC HIỆN -->
        <section class="kanban-column" data-kanban-column data-status="doing">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot doing"></span>
                    <span>Đang thực hiện</span>
                    <span class="kanban-count" id="js-count-doing" data-column-count>0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-doing" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>

        <!-- CỘT 3: ĐANG KIỂM TRA -->
        <section class="kanban-column" data-kanban-column data-status="review">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot review"></span>
                    <span>Đang kiểm tra</span>
                    <span class="kanban-count" id="js-count-review" data-column-count>0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-review" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>

        <!-- CỘT 4: HOÀN THÀNH -->
        <section class="kanban-column" data-kanban-column data-status="done">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot done"></span>
                    <span>Hoàn thành</span>
                    <span class="kanban-count" id="js-count-done" data-column-count>0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-done" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
        </section>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token');
    const baseUrl = '/creative-agency-hub';
    const boardMsg = document.getElementById('js-board-message');

    // Các container của từng cột
    const lists = {
        'pending': document.getElementById('js-list-pending'),
        'todo': document.getElementById('js-list-todo'),
        'doing': document.getElementById('js-list-doing'),
        'review': document.getElementById('js-list-review'),
        'done': document.getElementById('js-list-done')
    };

    // Chỗ đếm số lượng task của từng cột
    const counts = {
        'pending': document.getElementById('js-count-pending'),
        'todo': document.getElementById('js-count-todo'),
        'doing': document.getElementById('js-count-doing'),
        'review': document.getElementById('js-count-review'),
        'done': document.getElementById('js-count-done')
    };

    // Từ điển map Status Backend sang Cột Frontend
    const statusMap = {
        'Pending approval': 'pending',
        'pending approval': 'pending',
        'Pending': 'pending',
        'pending': 'pending',
        'Chờ duyệt': 'pending',
        'chờ duyệt': 'pending',

        'To do': 'todo',
        'to do': 'todo',
        'Todo': 'todo',
        'todo': 'todo',
        'Cần làm': 'todo',
        'cần làm': 'todo',

        'Doing': 'doing',
        'doing': 'doing',
        'In progress': 'doing',
        'in_progress': 'doing',
        'Đang thực hiện': 'doing',
        'đang thực hiện': 'doing',

        'Review': 'review',
        'review': 'review',
        'Đang kiểm tra': 'review',
        'đang kiểm tra': 'review',

        'Done': 'done',
        'done': 'done',
        'Completed': 'done',
        'completed': 'done',
        'Hoàn thành': 'done',
        'hoàn thành': 'done'
    };

    const progressMap = {
        'pending': 0,
        'todo': 10,
        'doing': 55,
        'review': 82,
        'done': 100
    };

    const showMessage = (msg, isError = false) => {
        boardMsg.style.display = 'block';
        boardMsg.style.backgroundColor = isError ? '#ffebee' : '#e8f5e9';
        boardMsg.style.color = isError ? '#c62828' : '#2e7d32';
        boardMsg.innerHTML = msg;
    };

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    if (!token) {
        showMessage('<b>Lỗi:</b> Bạn chưa đăng nhập hoặc Token đã mất. Vui lòng đăng nhập lại.', true);
        return;
    }

    // Hàm render toàn bộ bảng
    const renderBoard = (tasks) => {
        // Xóa sạch dữ liệu cũ/loading
        Object.values(lists).forEach(list => {
            if (list) {
                list.innerHTML = '';
            }
        });

        let taskCounts = {
            'pending': 0,
            'todo': 0,
            'doing': 0,
            'review': 0,
            'done': 0
        };

        if (!tasks || tasks.length === 0) {
            if (lists['todo']) {
                lists['todo'].innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">Chưa có công việc nào</div>';
            }

            Object.keys(taskCounts).forEach(key => {
                if (counts[key]) {
                    counts[key].innerText = taskCounts[key];
                }
            });

            return;
        }

        tasks.forEach(task => {
            // Xác định cột tương ứng, nếu status lạ thì đẩy vào todo
            const rawStatus = String(task.status || '');
            const normalizedStatus = rawStatus.toLowerCase();
            const colId = statusMap[rawStatus] || statusMap[normalizedStatus] || 'todo';

            // Map độ ưu tiên sang màu sắc giao diện
            let tagTone = 'primary';
            if (task.priority === 'High') tagTone = 'danger';
            if (task.priority === 'Medium') tagTone = 'warning';
            if (task.priority === 'Low') tagTone = 'info';

            // Xử lý dữ liệu hiển thị an toàn
            const progress = task.progress !== undefined && task.progress !== null
                ? parseInt(task.progress) || 0
                : progressMap[colId] || 0;

            const assigneeName = task.assignee_name || 'Unassigned';
            const avatarChar = assigneeName.charAt(0).toUpperCase();
            const commentsCount = parseInt(task.comments_count) || 0;
            const attachCount = parseInt(task.attachments_count) || 0;
            const pendingBadge = colId === 'pending'
                ? '<span class="badge badge-warning">CHỜ DUYỆT</span>'
                : '';

            const cardClass = colId === 'pending'
                ? 'task-card is-pending-approval'
                : 'task-card';

            const cardHTML = `
                <article class="${cardClass}" draggable="true" data-task-card data-task-id="${escapeHtml(task.id)}" data-status="${escapeHtml(colId)}">
                    <div class="task-card-top">
                        <span class="badge badge-${tagTone}">
                            ${escapeHtml(task.priority || 'Task')}
                        </span>
                        ${pendingBadge}
                        <button class="kanban-column-menu" type="button">⋮</button>
                    </div>

                    <h3 class="task-card-title">${escapeHtml(task.title)}</h3>
                    <p class="task-card-desc">${escapeHtml(task.description || 'Không có mô tả')}</p>

                    <div class="task-card-progress">
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: ${progress}%;"></div>
                        </div>
                        <small>${progress}% hoàn thành</small>
                    </div>

                    <div class="task-assignee-row">
                        <div class="task-assignee">
                            <span class="task-avatar" title="${escapeHtml(assigneeName)}">${escapeHtml(avatarChar)}</span>
                            <span>${escapeHtml(task.deadline || 'Chưa có hạn')}</span>
                        </div>

                        <div class="task-card-meta-group">
                            <span title="Đính kèm">▣ ${attachCount}</span>
                            <span title="Bình luận">□ ${commentsCount}</span>
                        </div>
                    </div>
                </article>
            `;

            if (lists[colId]) {
                lists[colId].insertAdjacentHTML('beforeend', cardHTML);
                taskCounts[colId]++;
            }
        });

        // Cập nhật số lượng trên tiêu đề cột
        Object.keys(taskCounts).forEach(key => {
            if (counts[key]) counts[key].innerText = taskCounts[key];
        });
    };

    // Hàm gọi API
    const loadTasks = () => {
        // Lấy query filter nếu sau này truyền ID từ trang Dự án sang
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('project_id') || '';

        let apiUrl = `${baseUrl}/public/api/tasks`;
        if (projectId) apiUrl += `?project_id=${projectId}`;

        fetch(apiUrl, {
            headers: { 'Authorization': 'Bearer ' + token }
        })
        .then(async res => {
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Dữ liệu lỗi từ Server:", text);
                throw new Error("API bị lỗi hoặc Server sập. Vui lòng ấn F12 xem Console.");
            }
        })
        .then(res => {
            if (res.status === 'error') {
                showMessage(`<b>Lỗi Database:</b> ${res.message}`, true);
                return;
            }
            renderBoard(res.data);
        })
        .catch(error => {
            showMessage(`<b>Lỗi JS:</b> ${error.message}`, true);
        });
    };

    // Chạy lần đầu
    loadTasks();
});
</script>

<?php
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>