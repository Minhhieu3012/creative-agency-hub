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

<section class="kanban-shell">
    <div class="task-filter-bar">
        <!-- Các bộ lọc này sẽ được nâng cấp thành API động trong tương lai -->
        <select id="js-filter-project" class="form-select">
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
        <!-- CỘT 1: CẦN LÀM -->
        <section class="kanban-column" data-kanban-column data-status="todo">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot todo"></span>
                    <span>Cần làm</span>
                    <span class="kanban-count" id="js-count-todo">0</span>
                </div>
                <button class="kanban-column-menu" type="button">•••</button>
            </header>
            <div class="kanban-card-list" id="js-list-todo" data-kanban-list>
                <div style="text-align: center; color: #999; padding: 20px;">Đang tải...</div>
            </div>
            <button class="task-add-card" type="button" data-add-task>＋ Thêm Task</button>
        </section>

        <!-- CỘT 2: ĐANG THỰC HIỆN -->
        <section class="kanban-column" data-kanban-column data-status="doing">
            <header class="kanban-column-head">
                <div class="kanban-column-title">
                    <span class="kanban-dot doing"></span>
                    <span>Đang thực hiện</span>
                    <span class="kanban-count" id="js-count-doing">0</span>
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
                    <span class="kanban-count" id="js-count-review">0</span>
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
                    <span class="kanban-count" id="js-count-done">0</span>
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
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub';
    const boardMsg = document.getElementById('js-board-message');

    // Các container của từng cột
    const lists = {
        'todo': document.getElementById('js-list-todo'),
        'doing': document.getElementById('js-list-doing'),
        'review': document.getElementById('js-list-review'),
        'done': document.getElementById('js-list-done')
    };

    // Chỗ đếm số lượng task của từng cột
    const counts = {
        'todo': document.getElementById('js-count-todo'),
        'doing': document.getElementById('js-count-doing'),
        'review': document.getElementById('js-count-review'),
        'done': document.getElementById('js-count-done')
    };

    // Từ điển map Status Backend sang Cột Frontend
    const statusMap = {
        'To do': 'todo',
        'Doing': 'doing',
        'Review': 'review',
        'Done': 'done'
    };

    const showMessage = (msg, isError = false) => {
        boardMsg.style.display = 'block';
        boardMsg.style.backgroundColor = isError ? '#ffebee' : '#e8f5e9';
        boardMsg.style.color = isError ? '#c62828' : '#2e7d32';
        boardMsg.innerHTML = msg;
    };

    if (!token) {
        showMessage('<b>Lỗi:</b> Bạn chưa đăng nhập hoặc Token đã mất. Vui lòng đăng nhập lại.', true);
        return;
    }

    // Hàm render toàn bộ bảng
    const renderBoard = (tasks) => {
        // Xóa sạch dữ liệu cũ/loading
        Object.values(lists).forEach(list => list.innerHTML = '');
        let taskCounts = { 'todo': 0, 'doing': 0, 'review': 0, 'done': 0 };

        if (!tasks || tasks.length === 0) {
            lists['todo'].innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">Chưa có công việc nào</div>';
            return;
        }

        tasks.forEach(task => {
            // Xác định cột tương ứng, nếu status lạ thì đẩy vào todo
            const colId = statusMap[task.status] || 'todo'; 
            
            // Map độ ưu tiên sang màu sắc giao diện
            let tagTone = 'primary';
            if (task.priority === 'High') tagTone = 'danger';
            if (task.priority === 'Medium') tagTone = 'warning';
            if (task.priority === 'Low') tagTone = 'info';

            // Xử lý dữ liệu hiển thị an toàn
            const progress = parseInt(task.progress) || 0;
            const assigneeName = task.assignee_name || 'Unassigned';
            const avatarChar = assigneeName.charAt(0).toUpperCase();
            const commentsCount = parseInt(task.comments_count) || 0;
            const attachCount = parseInt(task.attachments_count) || 0;

            const cardHTML = `
                <article class="task-card" draggable="true" data-task-card data-task-id="${task.id}" data-status="${colId}">
                    <div class="task-card-top">
                        <span class="badge badge-${tagTone}">
                            ${task.priority || 'Task'}
                        </span>
                        <button class="kanban-column-menu" type="button">⋮</button>
                    </div>

                    <h3 class="task-card-title">${task.title}</h3>
                    <p class="task-card-desc">${task.description || 'Không có mô tả'}</p>

                    <div class="task-card-progress">
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: ${progress}%;"></div>
                        </div>
                        <small>${progress}% hoàn thành</small>
                    </div>

                    <div class="task-assignee-row">
                        <div class="task-assignee">
                            <span class="task-avatar" title="${assigneeName}">${avatarChar}</span>
                            <span>${task.deadline || 'Chưa có hạn'}</span>
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