document.addEventListener('DOMContentLoaded', function() {
    const taskLists = document.querySelectorAll('.task-list');
    
    // 1. Tải dữ liệu ban đầu từ API
    loadTasks();

    // 2. Thiết lập Drag & Drop
    taskLists.forEach(list => {
        list.addEventListener('dragover', e => {
            e.preventDefault();
            const dragging = document.querySelector('.dragging');
            if (dragging) {
                list.appendChild(dragging);
            }
        });

        list.addEventListener('drop', e => {
            e.preventDefault();
            const dragging = document.querySelector('.dragging');
            if (dragging) {
                const newStatus = list.parentElement.dataset.status;
                const taskId = dragging.dataset.id;
                updateTaskStatus(taskId, newStatus);
            }
        });
    });
});

// Xử lý triệt để lỗi dính chuột: Lắng nghe sự kiện dragend trên toàn cục
document.addEventListener('dragend', e => {
    if (e.target.classList.contains('task-card')) {
        e.target.classList.remove('dragging');
    }
});

async function loadTasks() {
    try {
        const response = await fetch('/api/tasks');
        const tasks = await response.json();
        renderTasks(tasks);
    } catch (err) {
        console.error("Không thể tải task:", err);
    }
}

function renderTasks(tasks) {
    // Xóa list cũ
    document.querySelectorAll('.task-list').forEach(l => l.innerHTML = '');
    
    tasks.forEach(task => {
        const card = document.createElement('div');
        card.className = `task-card priority-${task.priority.toLowerCase()}`;
        card.draggable = true;
        card.dataset.id = task.id;
        card.innerHTML = `
            <span class="task-title">${task.title}</span>
            <div class="task-meta">Deadline: ${task.deadline}</div>
        `;

        card.addEventListener('dragstart', () => card.classList.add('dragging'));
        
        // Gán vào đúng cột
        const listId = task.status.toLowerCase().replace(' ', '') + '-list';
        const list = document.getElementById(listId);
        if (list) list.appendChild(card);
    });
    updateCounts();
}

async function updateTaskStatus(id, status) {
    await fetch('/api/tasks/update', {
        method: 'POST',
        body: JSON.stringify({ id, status }),
        headers: { 'Content-Type': 'application/json' }
    });
    updateCounts();
}

function updateCounts() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const count = col.querySelectorAll('.task-card').length;
        col.querySelector('.task-count').innerText = count;
    });
}