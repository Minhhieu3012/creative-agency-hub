-- =========================================================
-- Creative Agency Hub
-- Auth + Task/Kanban Test Seed
--
-- KHÔNG ĐỤNG SCHEMA
-- KHÔNG ALTER TABLE
-- KHÔNG THÊM CỘT
--
-- Tài khoản test:
-- Email: manager@agency.vn
-- Password: 123456
--
-- Email: employee@agency.vn
-- Password: 123456
--
-- Email: client@agency.vn
-- Password: 123456
--
-- Password dưới đây là bcrypt hash của chuỗi: 123456
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1. Departments
-- =========================================================

INSERT INTO departments (
    id,
    name,
    description,
    status,
    deleted_at
)
VALUES
    (
        1,
        'Phòng Kỹ thuật',
        'Phòng ban test phục vụ đăng nhập và kiểm thử Kanban.',
        'active',
        NULL
    ),
    (
        2,
        'Khách hàng',
        'Phòng ban test dành cho tài khoản client portal.',
        'active',
        NULL
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    status = VALUES(status),
    deleted_at = NULL;

-- =========================================================
-- 2. Positions
-- =========================================================

INSERT INTO positions (
    id,
    name,
    description,
    status,
    deleted_at
)
VALUES
    (
        1,
        'Project Manager',
        'Chức vụ test dành cho tài khoản quản lý dự án.',
        'active',
        NULL
    ),
    (
        2,
        'Frontend Developer',
        'Chức vụ test dành cho tài khoản nhân viên.',
        'active',
        NULL
    ),
    (
        3,
        'Client Representative',
        'Chức vụ test dành cho tài khoản khách hàng.',
        'active',
        NULL
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    status = VALUES(status),
    deleted_at = NULL;

-- =========================================================
-- 3. Employees / Test Accounts
-- =========================================================

INSERT INTO employees (
    id,
    department_id,
    position_id,
    manager_id,
    employee_code,
    full_name,
    email,
    password,
    role,
    phone,
    gender,
    date_of_birth,
    address,
    avatar,
    total_leave_days,
    remaining_leave_days,
    status,
    hire_date,
    resigned_date,
    deleted_at
)
VALUES
    (
        1,
        1,
        1,
        NULL,
        'MNG-TEST-001',
        'Manager Test',
        'manager@agency.vn',
        '$2y$10$wH6bDAyr7qLf.og9eQFnk.5GXz4C9f8d6ZllSPFs/t1zGZOCUe8lW',
        'manager',
        '0900000001',
        'other',
        '1999-01-01',
        'Creative Agency Hub',
        NULL,
        12,
        12.00,
        'active',
        CURDATE(),
        NULL,
        NULL
    ),
    (
        2,
        1,
        2,
        1,
        'EMP-TEST-001',
        'Employee Test',
        'employee@agency.vn',
        '$2y$10$wH6bDAyr7qLf.og9eQFnk.5GXz4C9f8d6ZllSPFs/t1zGZOCUe8lW',
        'employee',
        '0900000002',
        'other',
        '1999-01-01',
        'Creative Agency Hub',
        NULL,
        12,
        12.00,
        'active',
        CURDATE(),
        NULL,
        NULL
    ),
    (
        3,
        2,
        3,
        1,
        'CLI-TEST-001',
        'Client Test',
        'client@agency.vn',
        '$2y$10$wH6bDAyr7qLf.og9eQFnk.5GXz4C9f8d6ZllSPFs/t1zGZOCUe8lW',
        'client',
        '0900000003',
        'other',
        '1999-01-01',
        'Client Company',
        NULL,
        12,
        12.00,
        'active',
        CURDATE(),
        NULL,
        NULL
    )
ON DUPLICATE KEY UPDATE
    department_id = VALUES(department_id),
    position_id = VALUES(position_id),
    manager_id = VALUES(manager_id),
    full_name = VALUES(full_name),
    password = VALUES(password),
    role = VALUES(role),
    phone = VALUES(phone),
    gender = VALUES(gender),
    date_of_birth = VALUES(date_of_birth),
    address = VALUES(address),
    total_leave_days = VALUES(total_leave_days),
    remaining_leave_days = VALUES(remaining_leave_days),
    status = VALUES(status),
    hire_date = VALUES(hire_date),
    resigned_date = NULL,
    deleted_at = NULL;

-- =========================================================
-- 4. Project
-- Bản này KHÔNG dùng client_id vì database hiện tại chưa có cột đó.
-- =========================================================

INSERT INTO projects (
    id,
    name,
    description,
    manager_id,
    status
)
VALUES
    (
        1,
        'NexusHR Web Platform',
        'Project test để kiểm tra đăng nhập, JWT và Kanban API thật.',
        1,
        'Active'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    manager_id = VALUES(manager_id),
    status = VALUES(status);

-- =========================================================
-- 5. Tasks
-- =========================================================

INSERT INTO tasks (
    id,
    project_id,
    title,
    description,
    status,
    priority,
    deadline,
    assigner_id,
    assignee_id,
    watcher_id
)
VALUES
    (
        1,
        1,
        'Thiết kế UI Login',
        'Hoàn thiện màn đăng nhập nội bộ và client portal.',
        'To do',
        'Medium',
        DATE_ADD(CURDATE(), INTERVAL 2 DAY),
        1,
        2,
        1
    ),
    (
        2,
        1,
        'Fix Auth API',
        'Chuẩn hóa response login/logout và session state.',
        'Doing',
        'High',
        DATE_ADD(CURDATE(), INTERVAL 3 DAY),
        1,
        2,
        1
    ),
    (
        3,
        1,
        'Review Kanban Board',
        'Kiểm tra kéo thả task và cập nhật trạng thái qua API.',
        'Review',
        'Medium',
        DATE_ADD(CURDATE(), INTERVAL 5 DAY),
        1,
        2,
        1
    ),
    (
        4,
        1,
        'Kickoff Project',
        'Hoàn tất checklist khởi tạo dự án.',
        'Done',
        'Low',
        DATE_SUB(CURDATE(), INTERVAL 1 DAY),
        1,
        2,
        1
    )
ON DUPLICATE KEY UPDATE
    project_id = VALUES(project_id),
    title = VALUES(title),
    description = VALUES(description),
    status = VALUES(status),
    priority = VALUES(priority),
    deadline = VALUES(deadline),
    assigner_id = VALUES(assigner_id),
    assignee_id = VALUES(assignee_id),
    watcher_id = VALUES(watcher_id);

SET FOREIGN_KEY_CHECKS = 1;