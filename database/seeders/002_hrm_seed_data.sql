-- =========================================================================
-- FILE SEED DATA MODULE HRM (CHUẨN BỊ CHO 30 TEST CASES)
-- Lưu ý: Chạy file này sau khi đã chạy 002_hrm_schema.sql
-- =========================================================================

USE creative_agency;

-- Bỏ qua kiểm tra khóa ngoại tạm thời để truncate/insert an toàn
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE employee_leave_adjustments;
TRUNCATE TABLE employee_contracts;
TRUNCATE TABLE employees;
TRUNCATE TABLE positions;
TRUNCATE TABLE departments;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- 1. SEED DATA: PHÒNG BAN (5 Test Cases)
-- ---------------------------------------------------------
INSERT INTO departments (id, name, description, status) VALUES
(1, 'Creative Department', 'Phòng Sáng tạo - Design & Content', 'active'),
(2, 'Account Department', 'Phòng Quản lý dự án & Khách hàng', 'active'),
(3, 'HR Department', 'Phòng Nhân sự & Hành chính', 'active'),
(4, 'Empty Department', 'Phòng ban trống (để test xóa mềm)', 'active'),
(5, 'Archived Department', 'Phòng ban đã giải thể', 'inactive');

-- ---------------------------------------------------------
-- 2. SEED DATA: CHỨC VỤ (7 Test Cases)
-- ---------------------------------------------------------
INSERT INTO positions (id, name, description, status) VALUES
(1, 'Director', 'Giám đốc/CEO', 'active'),
(2, 'Manager', 'Trưởng phòng', 'active'),
(3, 'Designer', 'Chuyên viên Thiết kế', 'active'),
(4, 'Developer', 'Kỹ sư Phần mềm', 'active'),
(5, 'HR Executive', 'Chuyên viên Nhân sự', 'active'),
(6, 'Intern', 'Thực tập sinh', 'active'),
(7, 'Old Position', 'Chức vụ cũ đã ngừng tuyển', 'inactive');

-- ---------------------------------------------------------
-- 3. SEED DATA: NHÂN VIÊN (12 Test Cases bao phủ Edge Cases)
-- (Password mặc định là mã băm bcrypt của 'password123')
-- ---------------------------------------------------------
INSERT INTO employees 
(id, department_id, position_id, manager_id, employee_code, full_name, email, password, role, phone, gender, date_of_birth, avatar, total_leave_days, remaining_leave_days, status, hire_date, resigned_date) 
VALUES
-- Case 1: Director (manager_id = NULL)
(1, 1, 1, NULL, 'EMP-0001', 'John Director', 'director@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0901000001', 'male', '1980-05-15', 'default-avatar.png', 20, 20.00, 'active', '2020-01-01', NULL),

-- Case 2: Manager (manager_id trỏ về Director)
(2, 1, 2, 1, 'EMP-0002', 'Alice Manager', 'manager@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '0901000002', 'female', '1985-08-20', 'default-avatar.png', 15, 12.50, 'active', '2021-03-15', NULL),

-- Case 3: Employee (manager_id trỏ về Manager, nhiều ngày phép)
(3, 1, 3, 2, 'EMP-0003', 'Bob Designer', 'designer@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '0901000003', 'male', '1995-12-10', 'default-avatar.png', 12, 12.00, 'active', '2023-05-01', NULL),

-- Case 4: Employee chưa có manager_id, hết ngày phép
(4, 2, 4, NULL, 'EMP-0004', 'Charlie Dev', 'dev@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '0901000004', 'male', '1998-02-28', 'default-avatar.png', 12, 0.00, 'active', '2024-01-10', NULL),

-- Case 5: Employee đã nghỉ việc (status = resigned, có resigned_date)
(5, 3, 5, 2, 'EMP-0005', 'Diana HR', 'hr.old@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '0901000005', 'female', '1992-11-05', 'default-avatar.png', 12, 5.00, 'resigned', '2022-06-01', '2023-12-31'),

-- Case 6: Employee đang tạm đình chỉ (suspended)
(6, 2, 4, 2, 'EMP-0006', 'Eve Suspended', 'eve@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '0901000006', 'other', '1990-07-22', 'default-avatar.png', 12, 10.00, 'suspended', '2023-01-15', NULL),

-- Case 7: Employee chưa có avatar (NULL)
(7, 1, 6, 2, 'EMP-0007', 'Frank Intern', 'intern@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '0901000007', 'male', '2002-04-12', NULL, 6, 6.00, 'active', '2024-03-01', NULL),

-- Case 8: Employee inactive (để test trigger khóa Auth)
(8, 2, 3, 2, 'EMP-0008', 'Grace Inactive', 'grace@agency.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '0901000008', 'female', '1994-09-30', 'default-avatar.png', 12, 8.00, 'inactive', '2021-08-01', NULL);

-- ---------------------------------------------------------
-- 4. SEED DATA: HỢP ĐỒNG (5 Test Cases)
-- ---------------------------------------------------------
INSERT INTO employee_contracts 
(id, employee_id, contract_code, contract_type, start_date, end_date, salary, status, note) 
VALUES
-- Case 1: Hợp đồng chính thức còn hiệu lực
(1, 1, 'HD-0001', 'indefinite', '2020-01-01', NULL, 50000000.00, 'active', 'Hợp đồng CEO'),
-- Case 2: Hợp đồng thử việc còn hiệu lực
(2, 7, 'HD-0002', 'probation', '2024-03-01', '2024-05-01', 5000000.00, 'active', 'Thực tập sinh 2 tháng'),
-- Case 3: Hợp đồng sắp hết hạn (của NV Charlie)
(3, 4, 'HD-0003', 'fixed_term', '2024-01-10', '2024-06-10', 15000000.00, 'active', 'Hợp đồng 6 tháng'),
-- Case 4: Hợp đồng đã hết hạn (của NV Bob, Bob đang có HĐ mới)
(4, 3, 'HD-0004', 'probation', '2023-05-01', '2023-07-01', 10000000.00, 'expired', 'Hợp đồng cũ đã hết hạn'),
-- Case 5: Hợp đồng của nhân viên đã nghỉ việc (Diana)
(5, 5, 'HD-0005', 'fixed_term', '2022-06-01', '2023-06-01', 12000000.00, 'terminated', 'Đã chấm dứt theo đơn xin nghỉ');

-- ---------------------------------------------------------
-- 5. SEED DATA: LOG ĐIỀU CHỈNH PHÉP (Chỉ lưu các giao dịch thành công)
-- ---------------------------------------------------------
INSERT INTO employee_leave_adjustments 
(id, employee_id, adjustment_days, old_remaining_days, new_remaining_days, reason, created_by) 
VALUES
-- Log 1: Cộng phép (Ví dụ: Thưởng phép năm)
(1, 2, 2.50, 10.00, 12.50, 'Thưởng phép dự án xuất sắc', 1),
-- Log 2: Trừ phép (Ví dụ: Hệ thống trừ phép sau khi duyệt đơn nghỉ)
(2, 5, -2.00, 7.00, 5.00, 'Trừ phép nghỉ ốm 2 ngày', 2),
-- Log 3: Trừ phép nửa ngày
(3, 4, -0.50, 0.50, 0.00, 'Nghỉ nửa buổi sáng', 2);