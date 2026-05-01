<?php
namespace App\Controllers;

use Core\Database;
use Core\JwtHandler;

class DashboardController {
    private $db;
    private $jwt;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->jwt = new JwtHandler();
    }

    public function getStats() {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Kiểm tra Token
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Unauthorized"]);
            return;
        }

        $token = str_replace("Bearer ", "", $authHeader);
        $decoded = $this->jwt->decode($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Invalid Token"]);
            return;
        }

        // 2. Query dữ liệu thật
        try {
            // -- 4 Ô SỐ LIỆU TỔNG --
            $empStmt = $this->db->query("SELECT COUNT(id) FROM employees WHERE status = 'active'");
            $totalEmployees = $empStmt->fetchColumn();

            $projStmt = $this->db->query("SELECT COUNT(id) FROM projects WHERE status = 'in_progress'");
            $activeProjects = $projStmt->fetchColumn() ?: 0;

            $taskStmt = $this->db->query("SELECT COUNT(id) FROM tasks WHERE deadline < CURDATE() AND status != 'Done'");
            $overdueTasks = $taskStmt->fetchColumn() ?: 0;

            try {
                $progressStmt = $this->db->query("SELECT AVG(progress) FROM projects WHERE status = 'in_progress'");
                $avgProgress = round($progressStmt->fetchColumn() ?: 0);
            } catch (\PDOException $e) {
                $avgProgress = 0; 
            }

            // -- DANH SÁCH DỰ ÁN TRỌNG ĐIỂM --
            $projectsList = [];
            try {
                $projListStmt = $this->db->query("SELECT id, name, deadline, progress FROM projects WHERE status = 'in_progress' ORDER BY deadline ASC LIMIT 4");
                $projectsData = $projListStmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($projectsData as $p) {
                    $tStmt = $this->db->prepare("SELECT COUNT(id) as total, SUM(CASE WHEN status = 'Done' THEN 1 ELSE 0 END) as completed FROM tasks WHERE project_id = :pid");
                    $tStmt->execute(['pid' => $p['id']]);
                    $tStats = $tStmt->fetch(\PDO::FETCH_ASSOC);

                    $totalT = $tStats['total'] ?: 0;
                    $completedT = $tStats['completed'] ?: 0;

                    $tone = $p['progress'] < 50 ? 'warning' : 'primary';
                    if ($p['deadline'] < date('Y-m-d')) {
                        $tone = 'danger';
                    }

                    $date = new \DateTime($p['deadline']);
                    $months = ['', 'TH01', 'TH02', 'TH03', 'TH04', 'TH05', 'TH06', 'TH07', 'TH08', 'TH09', 'TH10', 'TH11', 'TH12'];
                    $formattedDeadline = $date->format('d') . ' ' . $months[(int)$date->format('m')] . ', ' . $date->format('Y');

                    $projectsList[] = [
                        'name' => $p['name'],
                        'deadline' => $formattedDeadline,
                        'progress' => $p['progress'],
                        'tasks' => "{$completedT}/{$totalT} Tasks",
                        'tone' => $tone,
                        'members' => ['A', 'B', '+1'] // Mock tĩnh tạm thời
                    ];
                }
            } catch (\PDOException $e) {
                // Bỏ qua lỗi cấu trúc dự án
            }

            // -- HOẠT ĐỘNG GẦN ĐÂY --
            $activitiesList = [];
            try {
                // JOIN lấy tên người dùng và tên công việc
                $actStmt = $this->db->query("
                    SELECT 
                        al.action, 
                        al.description, 
                        al.created_at, 
                        e.full_name as user_name,
                        t.title as task_title
                    FROM task_activity_logs al
                    LEFT JOIN employees e ON al.user_id = e.id
                    LEFT JOIN tasks t ON al.task_id = t.id
                    ORDER BY al.created_at DESC 
                    LIMIT 4
                ");
                $activitiesData = $actStmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($activitiesData as $act) {
                    $icon = '❖';
                    $tone = 'secondary';
                    $title = 'Cập nhật hệ thống';

                    // Map action thành UI
                    switch ($act['action']) {
                        case 'create':
                            $icon = '+';
                            $tone = 'primary';
                            $title = 'Tạo công việc mới';
                            break;
                        case 'assign':
                        case 'reassign':
                            $icon = '👤';
                            $tone = 'info';
                            $title = 'Phân công nhân sự';
                            break;
                        case 'status_change':
                            $icon = '✓';
                            $tone = 'success';
                            $title = 'Cập nhật trạng thái';
                            break;
                    }

                    // Tạo description hiển thị Tên Task và Người thực hiện
                    $taskName = $act['task_title'] ? htmlspecialchars($act['task_title']) : 'Công việc đã xóa';
                    $userName = $act['user_name'] ? "<strong>" . htmlspecialchars($act['user_name']) . "</strong>" : 'Hệ thống';
                    $desc = "{$taskName} - {$userName}";
                    
                    if (!empty($act['description'])) {
                        $desc .= "<br><small class='text-muted'>" . htmlspecialchars($act['description']) . "</small>";
                    }

                    // Tính thời gian "Time ago"
                    $time = strtotime($act['created_at']);
                    $diff = time() - $time;
                    if ($diff < 60) {
                        $timeStr = 'Vừa xong';
                    } elseif ($diff < 3600) {
                        $timeStr = floor($diff / 60) . ' phút trước';
                    } elseif ($diff < 86400) {
                        $timeStr = floor($diff / 3600) . ' giờ trước';
                    } else {
                        $timeStr = date('d/m/Y H:i', $time);
                    }

                    $activitiesList[] = [
                        'icon' => $icon,
                        'tone' => $tone,
                        'title' => $title,
                        'description' => $desc,
                        'time' => $timeStr
                    ];
                }
            } catch (\PDOException $e) {
                // Bỏ qua lỗi nếu sai cột
            }

            // 3. Trả về JSON
            echo json_encode([
                "status" => "success",
                "data" => [
                    "active_projects" => (int)$activeProjects,
                    "total_employees" => (int)$totalEmployees,
                    "avg_progress"    => (int)$avgProgress,
                    "overdue_tasks"   => (int)$overdueTasks,
                    "projects"        => $projectsList,
                    "activities"      => $activitiesList
                ]
            ]);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }
}