<?php
namespace App\Services\Core;

use Core\Database;
use Exception;

class NotificationService {

    public static function send($userId, $message) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message)
            VALUES (?, ?)
        ");

        $stmt->execute([$userId, $message]);
    }

    public static function sendToMany($userIds, $message) {
        foreach (array_unique($userIds) as $id) {
            if ($id) self::send($id, $message);
        }
    }

    // Get notification by user
    public static function getByUser($userId, $limit = 20, $offset = 0) {
        $conn = Database::getConnection();

        // validate limit
        if ($limit <= 0 || $limit > 100) {
            $limit = 20;
        }

        $stmt = $conn->prepare("
            SELECT id, message, is_read, created_at
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    // count unread notifi 
    public static function countUnread($userId) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM notifications
            WHERE user_id = ? AND is_read = 0
        ");

        $stmt->execute([$userId]);

        return $stmt->fetch()['total'] ?? 0;
    }

    // Mark as read (validate ownership)
    public static function markAsRead($notificationId, $userId) {
        $conn = Database::getConnection();

        // check ownership
        $stmt = $conn->prepare("
            SELECT id FROM notifications
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);

        if (!$stmt->fetch()) {
            throw new Exception("Notification not found or access denied");
        }

        $stmt = $conn->prepare("
            UPDATE notifications SET is_read = 1 WHERE id = ?
        ");
        $stmt->execute([$notificationId]);
    }
    public static function getUnreadByUser($userId, $limit = 20, $offset = 0) {
        $conn = Database::getConnection();

        if ($limit <= 0 || $limit > 100) {
            $limit = 20;
        }

        $stmt = $conn->prepare("
            SELECT id, message, is_read, created_at
            FROM notifications
            WHERE user_id = ? AND is_read = 0
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }
}