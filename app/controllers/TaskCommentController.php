<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../services/TaskCommentService.php';

class TaskCommentController extends BaseController {

    public function getAll() {

        $taskId = $_GET['task_id'] ?? null;

        $comments = TaskCommentService::getAll($taskId);

        return $this->success($comments, "List comments");
    }
    public function store($taskId) {

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['content'])) {
            return $this->error("Missing content");
        }

        if (strlen($data['content']) < 3) {
            return $this->error("content must be at least 3 characters");
        }

        // sau này lấy từ JWT
        $headers = getallheaders();

        // TODO: Replace with JWT authentication later
        $userId = $headers['user_id'] ?? 4;
        // $userId = $decodedToken->id;

        $result = TaskCommentService::create($taskId, $userId, $data);

        return $this->success($result, "Comment created");
    }
    public function getByTask($taskId) {

        if (!is_numeric($taskId)) {
            return $this->error("Invalid task id");
        }

        $comments = TaskCommentService::getByTask($taskId);

        return $this->success($comments, "List comments");
    }
}