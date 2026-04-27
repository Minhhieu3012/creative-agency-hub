document.addEventListener('DOMContentLoaded', function() {
    const taskLists = document.querySelectorAll('.task-list');
    
    loadTasks();

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

document.addEventListener('dragend', e => {
    if (e.target.classList.contains('task-card')) {
        e.target.classList.remove('dragging');
    }
});

async function loadTasks() {
    try {
        const response = await fetch('/creative-agency-hub/public/api/tasks');
        const json = await response.json();
        
        if (json.status === 'success') {
            renderTasks(json.data);
        }
    } catch (err) {
        console.error("Lỗi mạng:", err);
    }
}

function renderTasks(tasks) {
    document.querySelectorAll('.task-list').forEach(l => l.innerHTML = '');
    
    tasks.forEach(task => {
        const card = document.createElement('div');
        card.className = `task-card priority-${task.priority ? task.priority.toLowerCase() : 'medium'}`;
        card.draggable = true;
        card.dataset.id = task.id;
        card.innerHTML = `
            <span class="task-title">${task.title}</span>
            <div class="task-meta">Deadline: ${task.deadline || 'Chưa thiết lập'}</div>
        `;

        card.addEventListener('dragstart', () => card.classList.add('dragging'));
        
        // Map 3 trạng thái
        const listId = task.status.toLowerCase().replace(' ', '') + '-list';
        const list = document.getElementById(listId);
        if (list) list.appendChild(card);
    });
    updateCounts();
}

async function updateTaskStatus(id, status) {
    try {
        const response = await fetch(`/creative-agency-hub/public/api/tasks/${id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: status }),
            headers: { 'Content-Type': 'application/json' }
        });
        const result = await response.json();
        
        if(result.status === 'success') {
            updateCounts();
        } else {
            alert('Lỗi: ' + result.message);
            // Reload lại nếu thả lỗi
            loadTasks(); 
        }
    } catch(err) {
        console.error(err);
    }
}

function updateCounts() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const count = col.querySelectorAll('.task-card').length;
        col.querySelector('.task-count').innerText = count;
    });
}