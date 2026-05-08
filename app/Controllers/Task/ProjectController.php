<?php
namespace App\Controllers\Task;

use App\Services\Task\ProjectService;
use App\Middleware\AuthMiddleware;

class ProjectController {
    private $service;

    public function __construct() {
        $authUser = AuthMiddleware::check();
        $this->service = new ProjectService($authUser);
    }

    private function getInput() {
        return json_decode(file_get_contents('php://input'), true) ?? $_POST;
    }

    public function index() {
        echo json_encode([
            "status" => "success",
            "data" => $this->service->getAll()
        ]);
    }

    public function show($id) {
        try {
            $project = $this->service->getById($id);

            echo json_encode([
                "status" => "success",
                "data" => $project
            ]);
        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function store() {
        try {
            $id = $this->service->create($this->getInput());

            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Tạo project thành công",
                "data" => ["id"=>$id]
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function update($id) {
        try {
            $this->service->update($id, $this->getInput());

            echo json_encode([
                "status" => "success",
                "message" => "Cập nhật thành công"
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }

    public function delete($id) {
        try {
            $this->service->delete($id);

            echo json_encode([
                "status" => "success",
                "message" => "Xóa thành công"
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
        }
    }
}