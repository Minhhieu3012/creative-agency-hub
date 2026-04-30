<?php
namespace App\Controllers\Task;

use App\Middleware\AuthMiddleware;
use App\Services\Task\TaskCommentService;
use App\Controllers\BaseController;

class TaskCommentController extends BaseController {

    public function getAll() {
        $taskId = $_GET['task_id'] ?? null;

        $comments = TaskCommentService::getAll($taskId);

        return $this->success($comments, "List comments");
    }

    public function getById($commentId) {
        if (!is_numeric($commentId)) {
            return $this->error("Invalid comment id");
        }

        $comment = TaskCommentService::getById($commentId);

        if (!$comment) {
            return $this->error("Comment not found");
        }

        return $this->success($comment, "Comment detail");
    }

    public function store($taskId) {
        $user = AuthMiddleware::check();
        $userId = $user['id'];

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['content'])) {
            return $this->error("Missing content");
        }

        if (strlen(trim($data['content'])) < 3) {
            return $this->error("Content must be at least 3 characters");
        }

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

    public function update($commentId) {
        $authUser = AuthMiddleware::check();
        $userId = $authUser['id'];

        if (!is_numeric($commentId)) {
            return $this->error("Invalid comment id");
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['content'])) {
            return $this->error("Missing content");
        }

        if (strlen(trim($data['content'])) < 3) {
            return $this->error("Content must be at least 3 characters");
        }

        try {
            $result = TaskCommentService::update($commentId, $userId, $data);
            return $this->success($result, "Comment updated");
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete($commentId) {
        if (!is_numeric($commentId)) {
            return $this->error("Invalid comment id");
        }

        $authUser = AuthMiddleware::check();
        $userId = $authUser['id'];

        try {
            TaskCommentService::delete($commentId, $userId);
            return $this->success(null, "Comment deleted");
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}