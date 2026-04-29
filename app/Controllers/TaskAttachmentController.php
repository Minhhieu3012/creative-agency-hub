<?php
namespace App\Controllers;

use App\Services\TaskAttachmentService;
use App\Middleware\AuthMiddleware;
use Exception;

class TaskAttachmentController {

    public function upload($taskId) {
        try {
            $authUser = AuthMiddleware::check();

            $result = TaskAttachmentService::upload(
                $taskId,
                $authUser['id'],
                $_FILES['file']
            );

            echo json_encode([
                "status" => "success",
                "data" => $result
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    public function list($taskId) {
        $data = TaskAttachmentService::getByTask($taskId);

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    }

    public function download($id) {
        try {
            $authUser = AuthMiddleware::check();

            TaskAttachmentService::download(
                $id,
                $authUser['id']
            );

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}