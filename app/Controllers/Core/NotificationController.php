<?php
namespace App\Controllers;

use App\Services\NotificationService;
use App\Middleware\AuthMiddleware;
use Exception;

class NotificationController {

    // GET /api/notifications
    public function index() {
        try {
            $user = AuthMiddleware::check();

            $limit  = $_GET['limit'] ?? 20;
            $offset = $_GET['offset'] ?? 0;

            $data = NotificationService::getByUser(
                $user['id'],
                (int)$limit,
                (int)$offset
            );

            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }

    // GET /api/notifications/unread-count
    public function unreadCount() {
        $user = AuthMiddleware::check();

        $count = NotificationService::countUnread($user['id']);

        echo json_encode([
            "status" => "success",
            "data" => [
                "unread" => $count
            ]
        ]);
    }

    // PATCH /api/notifications/{id}/read
    public function markAsRead($id) {
        try {
            $user = AuthMiddleware::check();

            NotificationService::markAsRead($id, $user['id']);

            echo json_encode([
                "status" => "success",
                "message" => "Marked as read"
            ]);

        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
    // GET /api/notifications/unread
    public function unread() {
        try {
            $user = AuthMiddleware::check();

            $limit  = $_GET['limit'] ?? 20;
            $offset = $_GET['offset'] ?? 0;

            $data = NotificationService::getUnreadByUser(
                $user['id'],
                (int)$limit,
                (int)$offset
            );

            echo json_encode([
                "status" => "success",
                "data" => $data
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => $e->getMessage()
            ]);
        }
    }
}