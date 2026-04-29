let allTasksData = [];

document.addEventListener('DOMContentLoaded', function() {
    const taskLists = document.querySelectorAll('.task-list');
    const createTaskForm = document.getElementById('createTaskForm');
    const filterForm = document.getElementById('filterForm');
    const editTaskForm = document.getElementById('editTaskForm');
    
    loadTasks();
    loadEmployees(); 

    taskLists.forEach(list => {
        list.addEventListener('dragover', e => {
            e.preventDefault();
            const dragging = document.querySelector('.dragging');
            if (dragging) list.appendChild(dragging);
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

    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const projectId = document.getElementById('filterProject').value;
            const assigneeId = document.getElementById('filterAssignee').value;
            const deadline = document.getElementById('filterDeadline').value;
            loadTasks(projectId, assigneeId, deadline);
        });
    }

    if (createTaskForm) {
        createTaskForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const payload = {
                title: document.getElementById('taskTitle').value,
                description: document.getElementById('taskDescription').value,
                priority: document.getElementById('taskPriority').value,
                deadline: document.getElementById('taskDeadline').value,
                project_id: document.getElementById('taskProject').value,
                assignee_id: document.getElementById('taskAssignee').value
            };

            try {
                const token = localStorage.getItem('jwt_token') || '';
                const response = await fetch('/creative-agency-hub/public/api/tasks', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                if(result.status === 'success') {
                    $('#createTaskModal').modal('hide');
                    createTaskForm.reset();
                    loadTasks();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (err) {
                console.error("Lỗi tạo task:", err);
            }
        });
    }

    if (editTaskForm) {
        editTaskForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('editTaskId').value;
            const payload = {
                title: document.getElementById('editTaskTitle').value,
                description: document.getElementById('editTaskDescription').value,
                priority: document.getElementById('editTaskPriority').value,
                deadline: document.getElementById('editTaskDeadline').value,
                project_id: document.getElementById('editTaskProject').value,
                assignee_id: document.getElementById('editTaskAssignee').value
            };

            try {
                const token = localStorage.getItem('jwt_token') || '';
                const response = await fetch(`/creative-agency-hub/public/api/tasks/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                
                if(result.status === 'success') {
                    $('#editTaskModal').modal('hide');
                    loadTasks();
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }
});

document.addEventListener('dragend', e => {
    if (e.target.classList.contains('task-card')) e.target.classList.remove('dragging');
});

async function loadEmployees() {
    try {
        const response = await fetch('/creative-agency-hub/public/api/employees');
        const json = await response.json();
        
        if (json.status === 200 && json.data) {
            const selects = document.querySelectorAll('.assignee-select, #filterAssignee');
            json.data.forEach(emp => {
                selects.forEach(select => {
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = `${emp.full_name}`;
                    select.appendChild(option);
                });
            });
        }
    } catch (err) {}
}

async function loadTasks(projectId = '', assigneeId = '', deadline = '') {
    try {
        const query = new URLSearchParams();
        if(projectId) query.append('project_id', projectId);
        if(assigneeId) query.append('assignee_id', assigneeId);
        if(deadline) query.append('deadline', deadline);

        const response = await fetch(`/creative-agency-hub/public/api/tasks?${query.toString()}`);
        const json = await response.json();
        
        if (json.status === 'success') {
            allTasksData = json.data;
            renderTasks(json.data);
        }
    } catch (err) {}
}

function renderTasks(tasks) {
    document.querySelectorAll('.task-list').forEach(l => l.innerHTML = '');
    
    tasks.forEach(task => {
        const card = document.createElement('div');
        card.className = `task-card priority-${task.priority ? task.priority.toLowerCase() : 'medium'}`;
        card.draggable = true;
        card.dataset.id = task.id;
        
        card.innerHTML = `
            <div class="d-flex justify-content-between">
                <span class="task-title">${task.title}</span>
                <button class="btn btn-sm btn-link text-info" onclick="openEditModal(${task.id})"><i class="fas fa-edit"></i></button>
            </div>
            <div class="task-meta">
                <span><i class="fas fa-calendar-alt"></i> ${task.deadline || 'Chưa thiết lập'}</span>
                <span><i class="fas fa-user"></i> ID: ${task.assignee_id || 'Trống'}</span>
            </div>
        `;

        card.addEventListener('dragstart', () => card.classList.add('dragging'));
        
        const listId = task.status.toLowerCase().replace(' ', '') + '-list';
        const list = document.getElementById(listId);
        if (list) list.appendChild(card);
    });
    updateCounts();
}

function openEditModal(id) {
    const task = allTasksData.find(t => t.id == id);
    if(task) {
        document.getElementById('editTaskId').value = task.id;
        document.getElementById('editTaskTitle').value = task.title;
        document.getElementById('editTaskDescription').value = task.description;
        document.getElementById('editTaskPriority').value = task.priority;
        document.getElementById('editTaskDeadline').value = task.deadline;
        document.getElementById('editTaskProject').value = task.project_id || '';
        document.getElementById('editTaskAssignee').value = task.assignee_id || '';
        
        $('#editTaskModal').modal('show');
    }
}

async function updateTaskStatus(id, status) {
    try {
        const token = localStorage.getItem('jwt_token') || '';
        const response = await fetch(`/creative-agency-hub/public/api/tasks/${id}/status`, {
            method: 'PATCH',
            body: JSON.stringify({ status: status }),
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const result = await response.json();
        if(result.status === 'success') {
            updateCounts();
        } else {
            loadTasks(); 
        }
    } catch(err) {}
}

function updateCounts() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const count = col.querySelectorAll('.task-card').length;
        col.querySelector('.task-count').innerText = count;
    });
}