<?php
require_once __DIR__ . '/../Models/TaskModel.php';

class TaskController {
    private $taskModel;

    public function __construct() {
        $this->taskModel = new TaskModel();
    }

    public function showBoard() {
        require_once __DIR__ . '/../Views/tasks/kanban.php';
    }

    public function getTasksAPI() {
        $tasks = $this->taskModel->getAllTasks();
        
        echo json_encode([
            "status" => "success",
            "message" => "Lấy danh sách task thành công",
            "data" => $tasks
        ]);
        exit;
    }

    public function createTaskAPI() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title']) || empty($input['deadline'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Vui lòng nhập Tiêu đề và Deadline"
            ]);
            exit;
        }

        $priority = $input['priority'] ?? 'Medium';
        $description = $input['description'] ?? '';
        $assignee_id = !empty($input['assignee_id']) ? $input['assignee_id'] : null;
        $watcher_id = !empty($input['watcher_id']) ? $input['watcher_id'] : null;

        $taskId = $this->taskModel->createTask($input['title'], $description, $priority, $input['deadline'], $assignee_id, $watcher_id);
        
        if ($taskId) {
            // Kích hoạt thông báo cho người nhận việc
            if ($assignee_id) {
                $this->taskModel->createNotification($assignee_id, "Bạn vừa được giao một công việc mới: " . $input['title']);
            }
            if ($watcher_id) {
                $this->taskModel->createNotification($watcher_id, "Bạn được thêm làm người theo dõi cho công việc: " . $input['title']);
            }

            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Tạo Task thành công",
                "data" => ["id" => $taskId]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Lỗi server khi tạo Task"
            ]);
        }
        exit;
    }

    public function updateTaskStatusAPI($taskId) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['status'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Thiếu dữ liệu status"
            ]);
            exit;
        }

        $validStatuses = ['To do', 'Doing', 'Review', 'Done'];
        if (!in_array($input['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Trạng thái không hợp lệ"
            ]);
            exit;
        }

        // Lấy thông tin Task cũ trước khi cập nhật để lấy ID người dùng
        $task = $this->taskModel->getTaskById($taskId);
        if (!$task) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Không tìm thấy công việc"
            ]);
            exit;
        }

        $success = $this->taskModel->updateStatus($taskId, $input['status']);
        
        if ($success) {
            // Bắn thông báo báo cáo tiến độ cho các bên liên quan
            $notifyMsg = "Công việc '" . $task['title'] . "' đã chuyển sang trạng thái: " . $input['status'];
            
            if ($task['assignee_id']) {
                $this->taskModel->createNotification($task['assignee_id'], $notifyMsg);
            }
            if ($task['assigner_id']) {
                $this->taskModel->createNotification($task['assigner_id'], $notifyMsg);
            }

            echo json_encode([
                "status" => "success",
                "message" => "Cập nhật trạng thái thành công",
                "data" => []
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Lỗi cập nhật Database"
            ]);
        }
        exit;
    }
}
?>