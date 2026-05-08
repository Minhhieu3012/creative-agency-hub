<?php
namespace App\Services\Task;
use App\Services\Task\TaskActivityService;
use Core\Database;
use Exception;
use App\Enums\TaskAction;
use App\Services\Core\NotificationService;

class TaskAttachmentService {

    public static function upload($taskId, $userId, $file) {

        $conn = Database::getConnection();

        // 1. check task tồn tại
        $stmt = $conn->prepare("
            SELECT assignee_id, assigner_id, watcher_id 
            FROM tasks WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) {
            throw new Exception("Task not found");
        }

        // 2. check quyền (chỉ assignee hoặc manager)
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (
            $task['assignee_id'] != $userId &&
            $task['assigner_id'] != $userId &&
            $task['watcher_id'] != $userId &&
            !in_array($user['role'], ['admin', 'manager'])
        ) {
            throw new Exception("Permission denied");
        }

        // 3. check upload
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed");
        }

        // 4. validate size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("File too large (max 5MB)");
        }

        // 5. validate extension
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            throw new Exception("File type not allowed");
        }

        // 6. validate MIME (chống fake file)
        $allowedMime = [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowedMime)) {
            throw new Exception("Invalid file content");
        }

        // 7. generate unique name
        $fileName = uniqid('task_', true) . "." . $ext;

        $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filePath = $uploadDir . $fileName;

        // 8. save file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Cannot save file");
        }

        // 9. transaction
        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO task_attachments (task_id, user_id, file_name, file_path)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $taskId,
                $userId,
                $file['name'],
                $fileName
            ]);

            $conn->commit();

        } catch (Exception $e) {
            $conn->rollBack();
            unlink($filePath); // xoá file nếu DB fail
            throw $e;
        }

        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch();

        // Task Activity logs
        TaskActivityService::log(
            $taskId,
            $userId,
            TaskAction::UPLOAD,
            "{$actor['full_name']} uploaded file \"{$file['name']}\""
        );

        $notifyMsg = "{$actor['full_name']} đã upload file \"{$file['name']}\"";

        NotificationService::sendToMany(
            array_filter([
                $task['assignee_id'],
                $task['assigner_id'],
                $task['watcher_id']
            ]),
            $notifyMsg
        );
        return [
            "id" => $conn->lastInsertId(),
            "file_name" => $file['name'],
            "url" => "/uploads/tasks/" . $fileName
        ];
    }
        public static function getByTask($taskId) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT id, file_name, file_path, uploaded_at
            FROM task_attachments
            WHERE task_id = ?
            ORDER BY uploaded_at DESC
        ");

        $stmt->execute([$taskId]);

        return $stmt->fetchAll();
    }
        public static function download($fileId, $userId) {

            $conn = Database::getConnection();

            // 1. Lấy file
            $stmt = $conn->prepare("
                SELECT ta.file_name, ta.file_path, ta.task_id
                FROM task_attachments ta
                WHERE ta.id = ?
            ");
            $stmt->execute([$fileId]);
            $file = $stmt->fetch();

            if (!$file) {
                throw new Exception("File not found");
            }

            // 2. Lấy task + quyền user
            $stmt = $conn->prepare("
                SELECT t.assignee_id, t.assigner_id, t.watcher_id, e.role
                FROM tasks t
                JOIN employees e ON e.id = ?
                WHERE t.id = ?
            ");
            $stmt->execute([$userId, $file['task_id']]);
            $data = $stmt->fetch();

            if (!$data) {
                throw new Exception("Task not found");
            }

            // 3. Check quyền
           if (
                $data['assignee_id'] != $userId &&
                $data['assigner_id'] != $userId &&
                $data['watcher_id'] != $userId &&
                !in_array($data['role'], ['admin', 'manager'])
            ) {
                throw new Exception("Permission denied");
            }

            // 4. Check file tồn tại
            $path = __DIR__ . '/../../public/uploads/tasks/' . $file['file_path'];

            if (!file_exists($path)) {
                throw new Exception("File missing on server");
            }

            // 5. Clean filename (tránh lỗi header)
            $safeName = basename($file['file_name']);

            // 6. Header chuẩn
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');
            $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
            $stmt->execute([$userId]);
            $actor = $stmt->fetch();

            TaskActivityService::log(
                $file['task_id'],
                $userId,
                TaskAction::DOWNLOAD,
                "{$actor['full_name']} downloaded file \"{$file['file_name']}\""
            );
            $notifyMsg = "{$actor['full_name']} đã tải file \"{$file['file_name']}\"";

            NotificationService::send(
                $data['assigner_id'],
                $notifyMsg
            );

            // 7. Output file
            readfile($path);
            exit;
        }
}