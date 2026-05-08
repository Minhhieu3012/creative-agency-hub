<?php
namespace App\Services\Core;

use Core\Database;
use Exception;
use PDO;
use Throwable;

class NotificationService {
    private static function connection(): PDO {
        $conn = Database::getConnection();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }

    private static function normalizeLimit($limit): int {
        $limit = (int)$limit;

        if ($limit <= 0 || $limit > 100) {
            return 20;
        }

        return $limit;
    }

    private static function normalizeOffset($offset): int {
        $offset = (int)$offset;

        return max(0, $offset);
    }

    public static function send($userId, $message): bool {
        $userId = (int)$userId;
        $message = trim((string)$message);

        if ($userId <= 0 || $message === '') {
            return false;
        }

        $conn = self::connection();

        $stmt = $conn->prepare("
            INSERT INTO notifications
            (
                user_id,
                message,
                is_read
            )
            VALUES
            (
                :user_id,
                :message,
                0
            )
        ");

        return $stmt->execute([
            ':user_id' => $userId,
            ':message' => $message,
        ]);
    }

    public static function sendToMany($userIds, $message): void {
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        foreach (array_unique(array_map('intval', $userIds)) as $id) {
            if ($id > 0) {
                self::send($id, $message);
            }
        }
    }

    public static function getUserIdsByRole(string $role): array {
        $role = strtolower(trim($role));

        if ($role === '') {
            return [];
        }

        $conn = self::connection();

        $stmt = $conn->prepare("
            SELECT id
            FROM employees
            WHERE role = :role
              AND status = 'active'
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':role' => $role,
        ]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function sendToRole(string $role, string $message): void {
        self::sendToMany(self::getUserIdsByRole($role), $message);
    }

    public static function sendToAdmins(string $message): void {
        self::sendToRole('admin', $message);
    }

    public static function sendToManagers(string $message): void {
        self::sendToRole('manager', $message);
    }

    public static function getByUser($userId, $limit = 20, $offset = 0): array {
        $conn = self::connection();

        $limit = self::normalizeLimit($limit);
        $offset = self::normalizeOffset($offset);

        $stmt = $conn->prepare("
            SELECT
                id,
                message,
                is_read,
                created_at
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC, id DESC
            LIMIT :limit_value OFFSET :offset_value
        ");

        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_value', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset_value', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getUnreadByUser($userId, $limit = 20, $offset = 0): array {
        $conn = self::connection();

        $limit = self::normalizeLimit($limit);
        $offset = self::normalizeOffset($offset);

        $stmt = $conn->prepare("
            SELECT
                id,
                message,
                is_read,
                created_at
            FROM notifications
            WHERE user_id = :user_id
              AND is_read = 0
            ORDER BY created_at DESC, id DESC
            LIMIT :limit_value OFFSET :offset_value
        ");

        $stmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit_value', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset_value', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countUnread($userId): int {
        $conn = self::connection();

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = :user_id
              AND is_read = 0
        ");

        $stmt->execute([
            ':user_id' => (int)$userId,
        ]);

        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public static function markAsRead($notificationId, $userId): bool {
        $conn = self::connection();

        $stmt = $conn->prepare("
            SELECT id
            FROM notifications
            WHERE id = :id
              AND user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$notificationId,
            ':user_id' => (int)$userId,
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Không tìm thấy thông báo hoặc bạn không có quyền truy cập.');
        }

        $stmt = $conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = :id
              AND user_id = :user_id
        ");

        return $stmt->execute([
            ':id' => (int)$notificationId,
            ':user_id' => (int)$userId,
        ]);
    }

    public static function safeSend($userId, $message): void {
        try {
            self::send($userId, $message);
        } catch (Throwable $e) {
            // Notification không được làm hỏng flow chính.
        }
    }

    public static function safeSendToMany($userIds, $message): void {
        try {
            self::sendToMany($userIds, $message);
        } catch (Throwable $e) {
            // Notification không được làm hỏng flow chính.
        }
    }

    public static function safeSendToAdmins($message): void {
        try {
            self::sendToAdmins($message);
        } catch (Throwable $e) {
            // Notification không được làm hỏng flow chính.
        }
    }
}