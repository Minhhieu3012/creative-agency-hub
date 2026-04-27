<div class="task-management-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-uppercase fw-bold m-0" style="letter-spacing: 2px;">Kanban Board</h2>
        <button class="btn btn-dark" style="border-radius: 0; padding: 10px 25px;">+ NEW TASK</button>
    </div>

    <div class="row g-4" id="kanban-wrapper">
        <div class="col-md-4">
            <div class="kanban-column" data-status="To do">
                <div class="column-header d-flex justify-content-between align-items-center">
                    <span>TO DO</span>
                    <span class="task-count">0</span>
                </div>
                <div class="task-list" id="todo-list"></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="kanban-column" data-status="Doing">
                <div class="column-header d-flex justify-content-between align-items-center">
                    <span>DOING</span>
                    <span class="task-count">0</span>
                </div>
                <div class="task-list" id="doing-list"></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="kanban-column" data-status="Done">
                <div class="column-header d-flex justify-content-between align-items-center">
                    <span>DONE</span>
                    <span class="task-count">0</span>
                </div>
                <div class="task-list" id="done-list"></div>
            </div>
        </div>
    </div>
</div>