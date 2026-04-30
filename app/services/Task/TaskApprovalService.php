<?php
namespace App\Services;

use Exception;
use PDO;
use Core\Database;
use App\Enums\TaskAction;
use App\Services\TaskActivityService;
use App\Services\NotificationService;

class TaskApprovalService {

    public static function submit($taskId, $userId) {

        $conn = Database::getConnection();

        // check task
        $stmt = $conn->prepare("SELECT title, status, assignee_id FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new Exception("Task not found");
        }

        if ($task['assignee_id'] != $userId) {
            throw new Exception("You are not assigned to this task");
        }

        if ($task['status'] !== 'Doing') {
            throw new Exception("Only Doing tasks can be submitted");
        }

        // update
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Review' WHERE id = ?");
        $stmt->execute([$taskId]);

        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);
        // activity log
        TaskActivityService::log(
            $taskId,
            $userId,
            TaskAction::STATUS_CHANGE,
            "{$actor['full_name']} submitted task \"{$task['title']}\" for review"
        );

        // notify assigner
        $stmt = $conn->prepare("SELECT assigner_id FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $data = $stmt->fetch();

        if ($data && $data['assigner_id']) {
            NotificationService::send(
                $data['assigner_id'],
                "Task \"{$task['title']}\" đã được submit bởi \"{$actor['full_name']}\" để review"
            );
        }

        return [
            "task_id" => $taskId,
            "status" => "Review"
        ];
    }

    public static function approve($taskId, $userId) {

        $conn = Database::getConnection();

        // check role
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!in_array($user['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        // lấy thêm title + assignee name
        $stmt = $conn->prepare("
            SELECT t.title, t.status, e.full_name
            FROM tasks t
            LEFT JOIN employees e ON t.assignee_id = e.id
            WHERE t.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task['status'] !== 'Review') {
            throw new Exception("Task must be in Review state");
        }

        // update
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Done' WHERE id = ?");
        $stmt->execute([$taskId]);

        // lấy tên manager
        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        TaskActivityService::log(
            $taskId,
            $userId,
            TaskAction::STATUS_CHANGE,
            "{$actor['full_name']} approved task \"{$task['title']}\" → Done (Assignee: {$task['full_name']})"
        );
        // lấy assignee_id
        $stmt = $conn->prepare("SELECT assignee_id FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $data = $stmt->fetch();

        if ($data && $data['assignee_id']) {
            NotificationService::send(
                $data['assignee_id'],
                "Task \"{$task['title']}\" đã được duyệt bởi manager. DONE!"
            );
        }

        return [
            "task_id" => $taskId,
            "status" => "Done"
        ];
    }

    public static function reject($taskId, $userId) {

        $conn = Database::getConnection();

        // check role
        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!in_array($user['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        // lấy thêm title + assignee name
        $stmt = $conn->prepare("
            SELECT t.title, t.status, e.full_name
            FROM tasks t
            LEFT JOIN employees e ON t.assignee_id = e.id
            WHERE t.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task['status'] !== 'Review') {
            throw new Exception("Task must be in Review state");
        }

        // update
        $stmt = $conn->prepare("UPDATE tasks SET status = 'Doing' WHERE id = ?");
        $stmt->execute([$taskId]);

        // lấy tên manager
        $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        TaskActivityService::log(
            $taskId,
            $userId,
            TaskAction::STATUS_CHANGE,
            "{$actor['full_name']} rejected task \"{$task['title']}\" → Back to Doing (Assignee: {$task['full_name']})"
        );
        $stmt = $conn->prepare("SELECT assignee_id FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $data = $stmt->fetch();

        if ($data && $data['assignee_id']) {
            NotificationService::send(
                $data['assignee_id'],
                "Task \"{$task['title']}\" bị từ chối bởi manager \"{$actor['full_name']}\". Yêu cầu làm lại!"
            );
        }

        return [
            "task_id" => $taskId,
            "status" => "Doing"
        ];
    }

    public static function getTasksInReview($userId) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        $stmt = $conn->prepare("
            SELECT 
                t.id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.deadline,
                assigner.full_name AS assigner_name,
                assignee.full_name AS assignee_name,
                t.created_at,
                t.updated_at
            FROM tasks t
            LEFT JOIN employees assigner ON t.assigner_id = assigner.id
            LEFT JOIN employees assignee ON t.assignee_id = assignee.id
            WHERE t.status = 'Review'
            ORDER BY t.updated_at DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}