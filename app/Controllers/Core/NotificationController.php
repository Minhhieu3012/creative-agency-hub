<?php
namespace App\Controllers\Core;

use App\Services\Core\NotificationService;
use App\Middleware\AuthMiddleware;
use Exception;
use Throwable;

class NotificationController {
    private function json(array $payload, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function resolveId($idOrParams): ?int {
        if (is_array($idOrParams)) {
            if (isset($idOrParams['id'])) {
                return (int)$idOrParams['id'];
            }

            if (isset($idOrParams[0])) {
                return (int)$idOrParams[0];
            }

            return null;
        }

        if ($idOrParams !== null && $idOrParams !== '') {
            $id = (int)$idOrParams;
            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function currentUser(): array {
        $user = AuthMiddleware::check();

        if (is_object($user)) {
            $user = (array)$user;
        }

        if (!is_array($user) || empty($user['id'])) {
            $this->json([
                'status' => 'error',
                'message' => 'Bạn cần đăng nhập lại để xem thông báo.'
            ], 401);
        }

        return $user;
    }

    public function index(): void {
        try {
            $user = $this->currentUser();

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $data = NotificationService::getByUser(
                (int)$user['id'],
                $limit,
                $offset
            );

            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function unread(): void {
        try {
            $user = $this->currentUser();

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $data = NotificationService::getUnreadByUser(
                (int)$user['id'],
                $limit,
                $offset
            );

            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function unreadCount(): void {
        try {
            $user = $this->currentUser();

            $count = NotificationService::countUnread((int)$user['id']);

            $this->json([
                'status' => 'success',
                'data' => [
                    'unread' => $count
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function markAsRead($id = null): void {
        try {
            $user = $this->currentUser();
            $notificationId = $this->resolveId($id);

            if (!$notificationId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID thông báo.'
                ], 400);
            }

            NotificationService::markAsRead($notificationId, (int)$user['id']);

            $this->json([
                'status' => 'success',
                'message' => 'Đã đánh dấu thông báo là đã đọc.'
            ]);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}