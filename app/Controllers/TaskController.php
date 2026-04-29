<?php
namespace App\Controllers;

use App\Services\TaskActivityService;
use App\Enums\TaskAction;
use App\Middleware\AuthMiddleware;
use App\Services\NotificationService;
require_once __DIR__ . '/../Models/TaskModel.php';

class TaskController {
    private $taskModel;

    public function __construct() {
        $this->taskModel = new \TaskModel(); 
    }

    public function showBoard() {
        require_once __DIR__ . '/../Views/tasks/kanban.php';
    }

    public function index() {
        $filters = [
            'project_id'  => $_GET['project_id'] ?? null,
            'assignee_id' => $_GET['assignee_id'] ?? null,
            'status'      => $_GET['status'] ?? null,
            'deadline'    => $_GET['deadline'] ?? null
        ];

        $tasks = $this->taskModel->getAllTasks($filters);
        
        echo json_encode([
            "status" => "success",
            "message" => "Lấy danh sách task thành công",
            "data" => $tasks
        ]);
        exit;
    }

    public function store() {
        $authUser = AuthMiddleware::check();
        $assigner_id = $authUser['id'] ?? $authUser['user_id'] ?? null;
        // bổ sung phân quyền
        if (!in_array($authUser['role'], ['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Permission denied"
            ]);
            exit;
        }
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
        $project_id = !empty($input['project_id']) ? $input['project_id'] : null;

        if (!$assigner_id) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "Không xác định được danh tính người giao việc"
            ]);
            exit;
        }

        $taskId = $this->taskModel->createTask($input['title'], $description, $priority, $input['deadline'], $assigner_id, $assignee_id, $watcher_id, $project_id);
        
        if ($taskId) {
            // get user full_name
            $stmt = \Core\Database::getConnection()->prepare("SELECT full_name FROM employees WHERE id = ?");
            $stmt->execute([$assigner_id]);
            $actor = $stmt->fetch();

            // activity log create
            TaskActivityService::log(
                $taskId,
                $assigner_id,
                TaskAction::CREATE,
                "{$actor['full_name']} created task \"{$input['title']}\""
            );

            if ($assignee_id) {
                // Thay notification của Huy bằng notification của Bảo nhé (lưu vào bảng notifications trong DB)
                // $this->taskModel->createNotification($assignee_id, "Bạn vừa được giao một công việc mới: " . $input['title']);
                NotificationService::send(
                    $assignee_id,
                    "Bạn được giao task: " . $input['title']
                );

                // get user full_name
                $stmt = \Core\Database::getConnection()->prepare("SELECT full_name FROM employees WHERE id = ?");
                $stmt->execute([$assignee_id]);
                $assignee = $stmt->fetch();
                // activity log create
                TaskActivityService::log(
                    $taskId,
                    $assigner_id,
                    TaskAction::ASSIGN,
                    "{$actor['full_name']} assigned task to {$assignee['full_name']}"
                );
            }
            if ($watcher_id) {
                NotificationService::send(
                    $watcher_id,
                    "Bạn được thêm vào vị trí có thể theo dõi task: " . $input['title']
                );
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

    public function update($taskId) {
        $authUser = AuthMiddleware::check();
        $user_id = $authUser['id'] ?? $authUser['user_id'] ?? null;
        // Lấy task trước
        $task = $this->taskModel->getTaskById($taskId);

        if (!$task) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Không tìm thấy công việc"
            ]);
            exit;
        }
        // Phân quyền
        if (!in_array($authUser['role'], ['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Permission denied"
            ]);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title']) || empty($input['deadline'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Vui lòng nhập Tiêu đề và Deadline"
            ]);
            exit;
        }

        $task = $this->taskModel->getTaskById($taskId);
        if (!$task) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Không tìm thấy công việc"
            ]);
            exit;
        }

        $priority = $input['priority'] ?? $task['priority'];
        $description = $input['description'] ?? $task['description'];
        $assignee_id = !empty($input['assignee_id']) ? $input['assignee_id'] : null;
        $watcher_id = !empty($input['watcher_id']) ? $input['watcher_id'] : null;
        $project_id = !empty($input['project_id']) ? $input['project_id'] : null;

        $success = $this->taskModel->updateTask($taskId, $input['title'], $description, $priority, $input['deadline'], $assignee_id, $watcher_id, $project_id);
        
        if ($success) {
            $stmt = \Core\Database::getConnection()->prepare("SELECT full_name FROM employees WHERE id = ?");
            $stmt->execute([$user_id]);
            $actor = $stmt->fetch();

            TaskActivityService::log(
                $taskId,
                $user_id,
                TaskAction::UPDATE,
                "{$actor['full_name']} updated task \"{$input['title']}\""
            );
            $notifyMsg = "Task '{$input['title']}' vừa được cập nhật";

            NotificationService::sendToMany(
                array_filter([
                    $task['assignee_id'],
                    // $task['assigner_id'], Không cần thông báo cho người update task (manager)
                    $task['watcher_id']
                ]),
                $notifyMsg
            );

            echo json_encode([
                "status" => "success",
                "message" => "Cập nhật công việc thành công",
                "data" => []
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Lỗi server khi cập nhật công việc"
            ]);
        }
        exit;
    }

    public function updateStatus($taskId) {
        $authUser = AuthMiddleware::check();
        $user_id = $authUser['id'] ?? $authUser['user_id'] ?? null;
        // Lấy task trước
        $task = $this->taskModel->getTaskById($taskId);

        if (!$task) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Không tìm thấy công việc"
            ]);
            exit;
        }
        // Phân quyền
        if (!in_array($authUser['role'], ['admin', 'manager'])) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Permission denied"
            ]);
            exit;
        }

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
            $stmt = \Core\Database::getConnection()->prepare("SELECT full_name FROM employees WHERE id = ?");
            $stmt->execute([$user_id]);
            $actor = $stmt->fetch();

            TaskActivityService::log(
                $taskId,
                $user_id,
                TaskAction::STATUS_CHANGE,
                "{$actor['full_name']} moved task to \"{$input['status']}\""
            );

            $notifyMsg = "Task '{$task['title']}' đã chuyển trạng thái sang {$input['status']}";

            NotificationService::sendToMany(
                array_filter([
                    $task['assignee_id'],
                    $task['watcher_id']
                ]),
                $notifyMsg
            );

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