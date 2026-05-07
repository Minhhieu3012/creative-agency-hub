<?php
namespace App\Controllers\Payroll;

use Core\Database;
use Exception;

class PayrollController {
    private $authUser;

    public function __construct($authUser) {
        $this->authUser = $authUser;
    }

    // ==========================================
    // API 1: LẤY DỮ LIỆU BẢNG LƯƠNG
    // GET /api/payroll/summary?month=04&year=2026
    // ==========================================
    public function getSummary() {
        try {
            $pdo = Database::getConnection();
            $role = strtolower((string)($this->authUser['role'] ?? 'employee'));
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;

            // Nhận tham số tháng/năm từ URL (nếu không có thì lấy tháng hiện tại)
            $month = (int)($_GET['month'] ?? date('m'));
            $year = (int)($_GET['year'] ?? date('Y'));

            if ($month < 1 || $month > 12) {
                $month = (int)date('m');
            }

            if ($year < 2000 || $year > 2100) {
                $year = (int)date('Y');
            }
            
            $standard_days = 24; // Ngày công chuẩn
            $target_tasks = 5;   // Định mức KPI chuẩn
            $work_end_time = '17:00:00'; // Giờ kết thúc ca làm. Check-out đúng 17:00 không bị tính về sớm.
            $penalty_per_violation = 50000; // 50k / lần vi phạm

            // 1. Dựng câu Query: Sếp thấy hết, Nhân viên chỉ thấy mình
            // Bổ sung phòng ban/chức danh và lấy hợp đồng active mới nhất để tránh duplicate nhân sự.
            $sqlEmp = "
                SELECT
                    e.id,
                    e.employee_code,
                    e.full_name,
                    e.email,
                    e.role,
                    e.status,
                    e.department_id,
                    e.position_id,
                    COALESCE(d.name, 'Chưa có phòng ban') AS department_name,
                    COALESCE(p.name, e.role) AS position_name,
                    COALESCE(c.salary, 0) AS base_salary
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN (
                    SELECT ec1.*
                    FROM employee_contracts ec1
                    INNER JOIN (
                        SELECT employee_id, MAX(id) AS latest_contract_id
                        FROM employee_contracts
                        WHERE status = 'active'
                          AND deleted_at IS NULL
                        GROUP BY employee_id
                    ) latest ON latest.latest_contract_id = ec1.id
                ) c ON e.id = c.employee_id
                WHERE e.deleted_at IS NULL
            ";
            $params = [];
            
            if ($role === 'employee') {
                $sqlEmp .= " AND e.id = ?";
                $params[] = $emp_id;
            }
            $sqlEmp .= "
                ORDER BY
                    COALESCE(d.name, 'Chưa có phòng ban'),
                    FIELD(e.role, 'admin', 'manager', 'employee', 'client'),
                    e.full_name
            ";

            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute($params);
            $employees = $stmtEmp->fetchAll();

            $payroll_data = [];

            // Chuẩn bị sẵn các câu lệnh SQL để chạy trong vòng lặp cho tối ưu
            $stmtAtt = $pdo->prepare("
                SELECT *
                FROM attendances
                WHERE employee_id = ?
                  AND MONTH(work_date) = ?
                  AND YEAR(work_date) = ?
                ORDER BY work_date ASC
            ");
            
            // Đã fix lỗi logic KPI: Hỗ trợ cả các task không có deadline
            $stmtKPI = $pdo->prepare("
                SELECT COUNT(id) 
                FROM tasks 
                WHERE assignee_id = ? 
                  AND status = 'Done' 
                  AND MONTH(updated_at) = ? 
                  AND YEAR(updated_at) = ? 
                  AND (deadline IS NULL OR DATE(updated_at) <= deadline)
            ");

            // 2. Vòng lặp tính toán cho từng nhân viên
            foreach ($employees as $emp) {
                $e_id = (int)$emp['id'];
                $base_salary = floatval($emp['base_salary'] ?? 0);

                // --- TÍNH CHUYÊN CẦN ---
                $stmtAtt->execute([$e_id, $month, $year]);
                $atts = $stmtAtt->fetchAll();
                $actual_days = 0;
                $late = 0;
                $early = 0;
                $absent = 0;

                foreach ($atts as $a) {
                    $attendanceStatus = (string)($a['status'] ?? '');

                    // Chỉ Present/Late mới tính là có công. Absent không tính ngày công.
                    if (in_array($attendanceStatus, ['Present', 'Late'], true)) {
                        $actual_days++;
                    }

                    if ($attendanceStatus == 'Late') {
                        $late++;
                    }

                    if ($attendanceStatus == 'Absent') {
                        $absent++;
                    }

                    // Nếu check-out trước 17:00 thì mới tính về sớm.
                    // Check-out đúng 17:00 không bị phạt.
                    if (
                        !empty($a['check_out_time'])
                        && in_array($attendanceStatus, ['Present', 'Late'], true)
                        && date('H:i:s', strtotime($a['check_out_time'])) < $work_end_time
                    ) {
                        $early++;
                    }
                }

                // --- TÍNH KPI ---
                $stmtKPI->execute([$e_id, $month, $year]);
                $completed_tasks = intval($stmtKPI->fetchColumn());
                $kpi_percent = ($target_tasks > 0) ? round(($completed_tasks / $target_tasks) * 100) : 0;

                // --- CÔNG THỨC LƯƠNG ---
                $salary_per_day = $standard_days > 0 ? ($base_salary / $standard_days) : 0;
                $actual_salary = $salary_per_day * $actual_days;
                
                $bonus = 0;
                if ($kpi_percent > 100) {
                    $bonus = $base_salary * (($kpi_percent - 100) / 100); // Vượt 100% thì thưởng
                }

                $penalty = ($late + $early) * $penalty_per_violation; // 50k / lần vi phạm
                $net_salary = max(0, $actual_salary + $bonus - $penalty);

                $payroll_data[] = [
                    'employee_id' => $e_id,
                    'employee_code' => $emp['employee_code'] ?? null,
                    'full_name' => $emp['full_name'],
                    'email' => $emp['email'] ?? null,
                    'role' => $emp['role'],
                    'status' => $emp['status'] ?? null,
                    'department_id' => $emp['department_id'] ?? null,
                    'department_name' => $emp['department_name'] ?? 'Chưa có phòng ban',
                    'position_id' => $emp['position_id'] ?? null,
                    'position_name' => $emp['position_name'] ?? $emp['role'],
                    'base_salary' => $base_salary,
                    'attendance' => [
                        'actual_days' => $actual_days,
                        'standard_days' => $standard_days,
                        'late' => $late,
                        'early' => $early,
                        'absent' => $absent
                    ],
                    'kpi' => [
                        'completed_tasks' => $completed_tasks,
                        'target_tasks' => $target_tasks,
                        'percent' => $kpi_percent
                    ],
                    'financial' => [
                        'actual_salary' => round($actual_salary),
                        'bonus' => round($bonus),
                        'penalty' => $penalty,
                        'net_salary' => round($net_salary)
                    ]
                ];
            }

            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'success',
                'message' => "Tính lương tháng $month/$year thành công",
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'standard_days' => $standard_days,
                    'target_tasks' => $target_tasks,
                    'work_end_time' => $work_end_time,
                    'penalty_per_violation' => $penalty_per_violation
                ],
                'data' => $payroll_data
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Lỗi tính lương: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    // ==========================================
    // API 2: XUẤT FILE EXCEL (CSV) BẢNG LƯƠNG
    // GET /api/payroll/export
    // ==========================================
    // Đã đổi tên hàm từ exportPdf thành exportCsv cho chuẩn với hành vi xuất file
    public function exportCsv() {
        // Gọi lại hàm getSummary nhưng chặn echo json
        ob_start();
        $this->getSummary();
        $json_response = ob_get_clean();
        $result = json_decode($json_response, true);

        if ($result['status'] !== 'success') {
            die("Lỗi khi tạo dữ liệu xuất file.");
        }

        $data = $result['data'];
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        
        // Thiết lập Headers để trình duyệt hiểu đây là file tải về
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Bang_Luong_Thang_' . $month . '_' . $year . '.csv');
        
        // Mở luồng ghi file
        $output = fopen('php://output', 'w');
        
        // Ghi BOM để Excel đọc tiếng Việt có dấu không bị lỗi font
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Ghi dòng tiêu đề
        fputcsv($output, [
            'Mã NV',
            'Họ Tên',
            'Email',
            'Phòng ban',
            'Chức danh',
            'Chức vụ',
            'Lương CB',
            'Ngày công',
            'Đi muộn',
            'Về sớm',
            'Nghỉ vắng',
            'KPI (%)',
            'Lương thực tế',
            'Thưởng',
            'Phạt',
            'THỰC LÃNH'
        ]);

        // Ghi từng dòng dữ liệu nhân viên
        foreach ($data as $emp) {
            fputcsv($output, [
                $emp['employee_code'] ?? $emp['employee_id'],
                $emp['full_name'],
                $emp['email'] ?? '',
                $emp['department_name'] ?? 'Chưa có phòng ban',
                $emp['position_name'] ?? '',
                $emp['role'],
                number_format($emp['base_salary']) . ' ₫',
                $emp['attendance']['actual_days'] . '/' . $emp['attendance']['standard_days'],
                $emp['attendance']['late'],
                $emp['attendance']['early'],
                $emp['attendance']['absent'] ?? 0,
                $emp['kpi']['percent'] . '%',
                number_format($emp['financial']['actual_salary']) . ' ₫',
                number_format($emp['financial']['bonus']) . ' ₫',
                number_format($emp['financial']['penalty']) . ' ₫',
                number_format($emp['financial']['net_salary']) . ' ₫'
            ]);
        }
        fclose($output);
        exit;
    }

}