<?php

class BaseController {

    protected function success($data = [], $message = "Success") {
        echo json_encode([
            "status" => "success",
            "message" => $message,
            "data" => $data
        ]);
    }

    protected function error($message = "Error", $code = 400) {
        http_response_code($code);

        echo json_encode([
            "status" => "error",
            "message" => $message
        ]);
    }
}