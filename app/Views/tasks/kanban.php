<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Kanban Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/creative-agency-hub/public/assets/css/task-style.css">
</head>
<body>
<div class="task-management-container">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 border-dark">
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
                <div class="column-header d-flex justify-content-between align-items-center bg-success text-white">
                    <span>DONE</span>
                    <span class="task-count">0</span>
                </div>
                <div class="task-list" id="done-list"></div>
            </div>
        </div>
    </div>
</div>

<script src="/creative-agency-hub/public/assets/js/task-script.js"></script>
</body>
</html>