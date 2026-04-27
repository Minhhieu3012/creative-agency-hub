<?php

class TestController {
    public function index() {
        echo json_encode([
            "status" => "success",
            "message" => "Controller OK"
        ]);
    }
}