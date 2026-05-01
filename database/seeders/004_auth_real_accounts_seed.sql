USE creative_agency;

SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO departments (
    id,
    name,
    description,
    status
)
VALUES
    (
        1,
        'Ban Điều hành',
        'Nhóm quản trị hệ thống và điều phối toàn bộ vận hành Creative Agency Hub.',
        'active'
    ),
    (
        2,
        'Phòng Dự án',
        'Nhóm quản lý dự án, phân công công việc và theo dõi tiến độ.',
        'active'
    ),
    (
        3,
        'Phòng Sản xuất',
        'Nhóm nhân sự triển khai thiết kế, frontend, backend và nội dung.',
        'active'
    ),
    (
        4,
        'Khách hàng',
        'Nhóm tài khoản đại diện khách hàng theo dõi dự án.',
        'active'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    status = VALUES(status),
    deleted_at = NULL;

INSERT INTO positions (
    id,
    name,
    description,
    status
)
VALUES
    (
        1,
        'System Administrator',
        'Quản trị hệ thống, cấu hình phân quyền và dữ liệu nền.',
        'active'
    ),
    (
        2,
        'Project Manager',
        'Quản lý dự án, phân công task và phê duyệt tiến độ.',
        'active'
    ),
    (
        3,
        'Project Executive',
        'Nhân sự thực thi công việc, cập nhật tiến độ và gửi duyệt task.',
        'active'
    ),
    (
        4,
        'Client Representative',
        'Đại diện khách hàng theo dõi project và gửi phản hồi.',
        'active'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    status = VALUES(status),
    deleted_at = NULL;

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
        'ADM001',
        'Admin Test',
        'admin@agency.vn',
        '$2y$12$9QaqP4qYEO15R47MQZfT0OmT5JTblFZKF4t0ucqpXW5TRYtMZQ0eS',
        'admin',
        '0900000000',
        'other',
        '1997-01-01',
        'Creative Agency Hub',
        NULL,
        12,
        12,
        'active',
        '2026-04-30',
        NULL,
        NULL
    ),
    (
        2,
        2,
        2,
        1,
        'MNG001',
        'Manager Test',
        'huy@test.com',
        '$2y$12$9QaqP4qYEO15R47MQZfT0OmT5JTblFZKF4t0ucqpXW5TRYtMZQ0eS',
        'manager',
        '0900000001',
        'other',
        '1999-01-01',
        'Creative Agency Hub',
        NULL,
        12,
        12,
        'active',
        '2026-04-30',
        NULL,
        NULL
    ),
    (
        3,
        3,
        3,
        2,
        'EMP001',
        'Employee Test',
        'employee@agency.vn',
        '$2y$12$9QaqP4qYEO15R47MQZfT0OmT5JTblFZKF4t0ucqpXW5TRYtMZQ0eS',
        'employee',
        '0900000002',
        'other',
        '2000-02-02',
        'Creative Agency Hub',
        NULL,
        12,
        12,
        'active',
        '2026-04-30',
        NULL,
        NULL
    ),
    (
        4,
        4,
        4,
        2,
        'CLI001',
        'Client Test',
        'client@agency.vn',
        '$2y$12$9QaqP4qYEO15R47MQZfT0OmT5JTblFZKF4t0ucqpXW5TRYtMZQ0eS',
        'client',
        '0900000003',
        'other',
        '1998-03-03',
        'Client Company',
        NULL,
        12,
        12,
        'active',
        '2026-04-30',
        NULL,
        NULL
    )
ON DUPLICATE KEY UPDATE
    department_id = VALUES(department_id),
    position_id = VALUES(position_id),
    manager_id = VALUES(manager_id),
    employee_code = VALUES(employee_code),
    full_name = VALUES(full_name),
    email = VALUES(email),
    password = VALUES(password),
    role = VALUES(role),
    phone = VALUES(phone),
    gender = VALUES(gender),
    date_of_birth = VALUES(date_of_birth),
    address = VALUES(address),
    avatar = VALUES(avatar),
    total_leave_days = VALUES(total_leave_days),
    remaining_leave_days = VALUES(remaining_leave_days),
    status = VALUES(status),
    hire_date = VALUES(hire_date),
    resigned_date = VALUES(resigned_date),
    deleted_at = NULL;

INSERT INTO projects (
    id,
    name,
    description,
    manager_id,
    client_id,
    status
)
VALUES
    (
        1,
        'Creative Website Revamp',
        'Dự án test thật để kiểm tra manager giao task, employee xử lý và client theo dõi.',
        2,
        4,
        'Active'
    )
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    manager_id = VALUES(manager_id),
    client_id = VALUES(client_id),
    status = VALUES(status);

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
        'Hoàn thiện màn đăng nhập nội bộ và client portal theo style Creative Agency Hub.',
        'Doing',
        'Medium',
        '2026-05-10',
        2,
        3,
        4
    ),
    (
        2,
        1,
        'Fix Auth API',
        'Chuẩn hóa luồng đăng nhập theo bảng employees và role mới.',
        'Review',
        'High',
        '2026-05-12',
        2,
        3,
        4
    ),
    (
        3,
        1,
        'Client Portal Feedback',
        'Chuẩn bị khu vực khách hàng xem tiến độ và gửi phản hồi.',
        'To do',
        'Medium',
        '2026-05-18',
        2,
        3,
        4
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