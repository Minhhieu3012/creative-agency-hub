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
        
        // Validate cơ bản
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

        $success = $this->taskModel->createTask($input['title'], $description, $priority, $input['deadline']);
        
        if ($success) {
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Tạo Task thành công",
                "data" => []
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

        // Bảng schema.sql mới chỉ có 3 ENUM này
        $validStatuses = ['To do', 'Doing', 'Done'];
        if (!in_array($input['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Trạng thái không hợp lệ"
            ]);
            exit;
        }

        $success = $this->taskModel->updateStatus($taskId, $input['status']);
        
        if ($success) {
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