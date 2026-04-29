<?php
namespace App\Services;

use Core\Database;

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
        foreach ($userIds as $id) {
            self::send($id, $message);
        }
    }
}
