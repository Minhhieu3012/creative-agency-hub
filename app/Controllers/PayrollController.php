<?php
namespace App\Controllers;

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
            $role = $this->authUser['role'] ?? 'employee';
            $emp_id = $this->authUser['id'] ?? $this->authUser['employee_id'] ?? null;

            // Nhận tham số tháng/năm từ URL (nếu không có thì lấy tháng hiện tại)
            $month = $_GET['month'] ?? date('m');
            $year = $_GET['year'] ?? date('Y');
            
            $standard_days = 24; // Ngày công chuẩn
            $target_tasks = 5;   // Định mức KPI chuẩn

            // 1. Dựng câu Query: Sếp thấy hết, Nhân viên chỉ thấy mình
            $sqlEmp = "SELECT e.id, e.full_name, e.role, c.salary as base_salary 
                       FROM employees e 
                       LEFT JOIN employee_contracts c ON e.id = c.employee_id AND c.status = 'active'";
            $params = [];
            
            if ($role === 'employee') {
                $sqlEmp .= " WHERE e.id = ?";
                $params[] = $emp_id;
            }
            $sqlEmp .= " ORDER BY e.role, e.full_name";

            $stmtEmp = $pdo->prepare($sqlEmp);
            $stmtEmp->execute($params);
            $employees = $stmtEmp->fetchAll();

            $payroll_data = [];

            // Chuẩn bị sẵn các câu lệnh SQL để chạy trong vòng lặp cho tối ưu
            $stmtAtt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ?");
            $stmtKPI = $pdo->prepare("SELECT COUNT(id) FROM tasks WHERE assignee_id = ? AND status = 'Done' AND MONTH(updated_at) = ? AND YEAR(updated_at) = ? AND DATE(updated_at) <= deadline");

            // 2. Vòng lặp tính toán cho từng nhân viên
            foreach ($employees as $emp) {
                $e_id = $emp['id'];
                $base_salary = floatval($emp['base_salary'] ?? 0);

                // --- TÍNH CHUYÊN CẦN ---
                $stmtAtt->execute([$e_id, $month, $year]);
                $atts = $stmtAtt->fetchAll();
                $actual_days = count($atts);
                $late = 0; $early = 0;

                foreach ($atts as $a) {
                    if ($a['status'] == 'Late') $late++;
                    if (!empty($a['check_out_time']) && date('H:i:s', strtotime($a['check_out_time'])) < '17:30:00') {
                        $early++;
                    }
                }

                // --- TÍNH KPI ---
                $stmtKPI->execute([$e_id, $month, $year]);
                $completed_tasks = intval($stmtKPI->fetchColumn());
                $kpi_percent = ($target_tasks > 0) ? round(($completed_tasks / $target_tasks) * 100) : 0;

                // --- CÔNG THỨC LƯƠNG ---
                $salary_per_day = $base_salary / $standard_days;
                $actual_salary = $salary_per_day * $actual_days;
                
                $bonus = 0;
                if ($kpi_percent > 100) {
                    $bonus = $base_salary * (($kpi_percent - 100) / 100); // Vượt 100% thì thưởng
                }

                $penalty = ($late + $early) * 50000; // 50k / lần vi phạm
                $net_salary = max(0, $actual_salary + $bonus - $penalty);

                $payroll_data[] = [
                    'employee_id' => $e_id,
                    'full_name' => $emp['full_name'],
                    'role' => $emp['role'],
                    'base_salary' => $base_salary,
                    'attendance' => [
                        'actual_days' => $actual_days,
                        'standard_days' => $standard_days,
                        'late' => $late,
                        'early' => $early
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
            echo json_encode([
                'status' => 'success',
                'message' => "Tính lương tháng $month/$year thành công",
                'data' => $payroll_data
            ]);

        } catch (Exception $e) {
            error_log("Lỗi tính lương: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // API 2: XUẤT FILE EXCEL (CSV) BẢNG LƯƠNG
    // GET /api/payroll/export
    // ==========================================
    public function exportPdf() {
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
        fputcsv($output, ['Mã NV', 'Họ Tên', 'Chức vụ', 'Lương CB', 'Ngày công', 'Đi muộn', 'Về sớm', 'KPI (%)', 'Lương thực tế', 'Thưởng', 'Phạt', 'THỰC LÃNH']);

        // Ghi từng dòng dữ liệu nhân viên
        foreach ($data as $emp) {
            fputcsv($output, [
                $emp['employee_id'],
                $emp['full_name'],
                $emp['role'],
                number_format($emp['base_salary']) . ' ₫',
                $emp['attendance']['actual_days'] . '/' . $emp['attendance']['standard_days'],
                $emp['attendance']['late'],
                $emp['attendance']['early'],
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