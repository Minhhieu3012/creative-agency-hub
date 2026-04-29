<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kanban Board - Creative Agency Hub</title>

    <link href="../../../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../../public/assets/css/sb-admin-2.css" rel="stylesheet">`n    <link href="../../../public/assets/css/agency-theme.css" rel="stylesheet">
    <link href="../../../public/assets/css/task-style.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include __DIR__ . '/../../../components/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include __DIR__ . '/../../../components/navbar.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Kanban Board</h1>
                        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#createTaskModal">
                            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Thêm công việc
                        </button>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form id="filterForm" class="row align-items-end">
                                <div class="col-md-3">
                                    <label class="font-weight-bold text-gray-800 small">Dự án</label>
                                    <select class="form-control" id="filterProject">
                                        <option value="">Tất cả</option>
                                        <option value="1">Creative Agency Hub</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="font-weight-bold text-gray-800 small">Người thực hiện</label>
                                    <select class="form-control" id="filterAssignee">
                                        <option value="">Tất cả</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="font-weight-bold text-gray-800 small">Deadline (trước ngày)</label>
                                    <input type="date" class="form-control" id="filterDeadline">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter"></i> Lọc dữ liệu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row g-3" id="kanban-wrapper">
                        <div class="col-md-3 mb-4">
                            <div class="kanban-column shadow-sm" data-status="To do">
                                <div class="column-header bg-light text-primary border-bottom font-weight-bold d-flex justify-content-between align-items-center">
                                    <span>TO DO</span>
                                    <span class="badge badge-primary badge-counter task-count">0</span>
                                </div>
                                <div class="task-list" id="todo-list"></div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="kanban-column shadow-sm" data-status="Doing">
                                <div class="column-header bg-light text-info border-bottom font-weight-bold d-flex justify-content-between align-items-center">
                                    <span>DOING</span>
                                    <span class="badge badge-info badge-counter task-count">0</span>
                                </div>
                                <div class="task-list" id="doing-list"></div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="kanban-column shadow-sm" data-status="Review">
                                <div class="column-header bg-light text-warning border-bottom font-weight-bold d-flex justify-content-between align-items-center">
                                    <span>REVIEW</span>
                                    <span class="badge badge-warning badge-counter task-count text-dark">0</span>
                                </div>
                                <div class="task-list" id="review-list"></div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-4">
                            <div class="kanban-column shadow-sm" data-status="Done">
                                <div class="column-header bg-light text-success border-bottom font-weight-bold d-flex justify-content-between align-items-center">
                                    <span>DONE</span>
                                    <span class="badge badge-success badge-counter task-count">0</span>
                                </div>
                                <div class="task-list" id="done-list"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include __DIR__ . '/../../../components/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="createTaskModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">Tạo Công Việc Mới</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="createTaskForm">
                    <div class="modal-body p-4">
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-800">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="taskTitle" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Dự án</label>
                                <select class="form-control" id="taskProject"><option value="1">Creative Agency Hub</option></select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Deadline <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="taskDeadline" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Người thực hiện</label>
                                <select class="form-control assignee-select" id="taskAssignee"><option value="">-- Để trống --</option></select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Độ ưu tiên</label>
                                <select class="form-control" id="taskPriority">
                                    <option value="Low">Thấp</option><option value="Medium" selected>Trung bình</option><option value="High">Cao</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-800">Mô tả</label>
                            <textarea class="form-control" id="taskDescription" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu lại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold">Cập Nhật Công Việc</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="editTaskForm">
                    <div class="modal-body p-4">
                        <input type="hidden" id="editTaskId">
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-800">Tiêu đề <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editTaskTitle" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Dự án</label>
                                <select class="form-control" id="editTaskProject"><option value="1">Creative Agency Hub</option></select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Deadline <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="editTaskDeadline" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Người thực hiện</label>
                                <select class="form-control assignee-select" id="editTaskAssignee"><option value="">-- Để trống --</option></select>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold text-gray-800">Độ ưu tiên</label>
                                <select class="form-control" id="editTaskPriority">
                                    <option value="Low">Thấp</option><option value="Medium">Trung bình</option><option value="High">Cao</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold text-gray-800">Mô tả</label>
                            <textarea class="form-control" id="editTaskDescription" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-info">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../../public/assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/sb-admin-2.min.js"></script>
    <script src="../../../public/assets/js/task-script.js"></script>
</body>
</html>
