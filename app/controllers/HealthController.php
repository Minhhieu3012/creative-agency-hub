<?php
namespace App\Controllers;
class TestController {
    public function index() {
        echo json_encode([
            "status" => "success",
            "message" => "Controller OK"
        ]);
    }
}