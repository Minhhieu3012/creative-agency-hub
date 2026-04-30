<?php
namespace App\Controllers\Task;

use App\Services\Task\TaskActivityService;
use App\Enums\TaskAction;
use App\Middleware\AuthMiddleware;
use App\Services\Core\NotificationService;
use App\Models\Task\TaskModel;

class TaskController {
    private $taskModel;

    public function __construct() {
        $this->taskModel = new TaskModel(); 
    }

    public function showBoard() {
        require_once __DIR__ . '/../View/tasks/kanban.php';
    }

    // =========================
    // GET ALL TASKS (CÓ PHÂN QUYỀN)
    // =========================
    public function index() {
        $authUser = AuthMiddleware::check();

        $filters = [
            'project_id'  => $_GET['project_id'] ?? null,
            'assignee_id' => $_GET['assignee_id'] ?? null,
            'status'      => $_GET['status'] ?? null,
            'deadline'    => $_GET['deadline'] ?? null,
            'manager_id'  => null
        ];

        // EMPLOYEE → chỉ thấy task của mình
        if ($authUser['role'] === 'employee') {
            $filters['assignee_id'] = $authUser['id'];
        }

        // MANAGER → chỉ thấy task thuộc project mình
        if ($authUser['role'] === 'manager') {
            $filters['manager_id'] = $authUser['id'];
        }

        $tasks = $this->taskModel->getAllTasks($filters);

        echo json_encode([
            "status" => "success",
            "message" => "Lấy danh sách task thành công",
            "data" => $tasks
        ]);
        exit;
    }

    // =========================
    // CREATE TASK
    // =========================
    public function store() {
        $authUser = AuthMiddleware::check();
        $assigner_id = $authUser['id'] ?? null;

        if ($authUser['role'] === 'employee') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Permission denied"]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['title']) || empty($input['deadline'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vui lòng nhập Tiêu đề và Deadline"]);
            exit;
        }

        $project_id = $input['project_id'] ?? null;

        // MANAGER check project ownership
        if ($project_id && $authUser['role'] === 'manager') {
            if (!$this->taskModel->isManagerOfProjectByProjectId($project_id, $authUser['id'])) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Không có quyền tạo task trong project này"]);
                exit;
            }
        }

        $priority = $input['priority'] ?? 'Medium';
        $description = $input['description'] ?? '';
        $assignee_id = $input['assignee_id'] ?? null;
        $watcher_id  = $input['watcher_id'] ?? null;

        $taskId = $this->taskModel->createTask(
            $input['title'],
            $description,
            $priority,
            $input['deadline'],
            $assigner_id,
            $assignee_id,
            $watcher_id,
            $project_id
        );

        if ($taskId) {
            echo json_encode([
                "status" => "success",
                "message" => "Tạo Task thành công",
                "data" => ["id" => $taskId]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi server"]);
        }
        exit;
    }

    // =========================
    // UPDATE TASK (PARTIAL UPDATE)
    // =========================
    public function update($taskId) {
        $authUser = AuthMiddleware::check();
        $user_id = $authUser['id'] ?? null;

        $task = $this->taskModel->getTaskById($taskId);
        if (!$task) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Task không tồn tại"]);
            exit;
        }

        // EMPLOYEE → không được update
        if ($authUser['role'] === 'employee') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Permission denied"]);
            exit;
        }

        // MANAGER → chỉ project của mình
        if ($authUser['role'] === 'manager' &&
            !$this->taskModel->isManagerOfProject($taskId, $authUser['id'])) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Không có quyền"]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // ✅ PATCH STYLE
        $title       = $input['title']       ?? $task['title'];
        $description = $input['description'] ?? $task['description'];
        $priority    = $input['priority']    ?? $task['priority'];
        $deadline    = $input['deadline']    ?? $task['deadline'];

        $assignee_id = array_key_exists('assignee_id', $input) 
            ? $input['assignee_id'] 
            : $task['assignee_id'];

        $watcher_id = array_key_exists('watcher_id', $input) 
            ? $input['watcher_id'] 
            : $task['watcher_id'];

        $project_id = array_key_exists('project_id', $input) 
            ? $input['project_id'] 
            : $task['project_id'];

        $success = $this->taskModel->updateTask(
            $taskId,
            $title,
            $description,
            $priority,
            $deadline,
            $assignee_id,
            $watcher_id,
            $project_id
        );

        if ($success) {
            echo json_encode([
                "status" => "success",
                "message" => "Cập nhật thành công"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Lỗi server"]);
        }
        exit;
    }

    // =========================
    // UPDATE STATUS
    // =========================
    public function updateStatus($taskId) {
        $authUser = AuthMiddleware::check();

        $task = $this->taskModel->getTaskById($taskId);
        if (!$task) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Task không tồn tại"]);
            exit;
        }

        // EMPLOYEE → chỉ task của mình
        if ($authUser['role'] === 'employee') {
            if ($task['assignee_id'] != $authUser['id']) {
                http_response_code(403);
                echo json_encode(["status" => "error", "message" => "Không có quyền"]);
                exit;
            }
        }

        // MANAGER
        if ($authUser['role'] === 'manager' &&
            !$this->taskModel->isManagerOfProject($taskId, $authUser['id'])) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Không có quyền"]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $validStatuses = ['To do', 'Doing', 'Review', 'Done'];
        if (!in_array($input['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Status không hợp lệ"]);
            exit;
        }

        $this->taskModel->updateStatus($taskId, $input['status']);

        echo json_encode([
            "status" => "success",
            "message" => "Update status thành công"
        ]);
        exit;
    }
}