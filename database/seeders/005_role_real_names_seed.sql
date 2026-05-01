USE creative_agency;

SET FOREIGN_KEY_CHECKS = 0;

UPDATE employees
SET
    full_name = 'Hoàng Minh Quân',
    employee_code = 'ADM001',
    role = 'admin',
    department_id = 1,
    position_id = 1,
    phone = '0901000001',
    address = 'Creative Agency Hub - System Office',
    status = 'active',
    deleted_at = NULL
WHERE email = 'admin@agency.vn';

UPDATE employees
SET
    full_name = 'Trần Gia Huy',
    employee_code = 'MNG001',
    role = 'manager',
    department_id = 2,
    position_id = 2,
    phone = '0901000002',
    address = 'Creative Agency Hub - Project Office',
    status = 'active',
    deleted_at = NULL
WHERE email = 'huy@test.com';

UPDATE employees
SET
    full_name = 'Lê Minh Anh',
    employee_code = 'EMP001',
    role = 'employee',
    department_id = 3,
    position_id = 3,
    manager_id = (
        SELECT manager_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'huy@test.com' LIMIT 1
        ) AS manager_row
    ),
    phone = '0901000003',
    address = 'Creative Agency Hub - Production Team',
    status = 'active',
    deleted_at = NULL
WHERE email = 'employee@agency.vn';

UPDATE employees
SET
    full_name = 'Phạm Khánh Linh',
    employee_code = 'CLI001',
    role = 'client',
    department_id = 4,
    position_id = 4,
    manager_id = (
        SELECT manager_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'huy@test.com' LIMIT 1
        ) AS manager_row
    ),
    phone = '0901000004',
    address = 'Client Company',
    status = 'active',
    deleted_at = NULL
WHERE email = 'client@agency.vn';

UPDATE employees
SET password = '$2y$12$9QaqP4qYEO15R47MQZfT0OmT5JTblFZKF4t0ucqpXW5TRYtMZQ0eS'
WHERE email IN (
    'admin@agency.vn',
    'huy@test.com',
    'employee@agency.vn',
    'client@agency.vn'
);

UPDATE projects
SET
    name = 'Website Brand Launch',
    description = 'Dự án xây dựng website giới thiệu thương hiệu, landing page chiến dịch và hệ thống form liên hệ.',
    manager_id = (
        SELECT manager_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'huy@test.com' LIMIT 1
        ) AS manager_row
    ),
    client_id = (
        SELECT client_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'client@agency.vn' LIMIT 1
        ) AS client_row
    ),
    status = 'Active'
WHERE id = 1;

UPDATE tasks
SET
    title = 'Hoàn thiện Homepage',
    description = 'Thiết kế lại hero, CTA và khu vực giới thiệu dịch vụ.',
    assigner_id = (
        SELECT manager_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'huy@test.com' LIMIT 1
        ) AS manager_row
    ),
    assignee_id = (
        SELECT employee_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'employee@agency.vn' LIMIT 1
        ) AS employee_row
    ),
    watcher_id = (
        SELECT client_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'client@agency.vn' LIMIT 1
        ) AS client_row
    )
WHERE id = 1;

UPDATE tasks
SET
    title = 'Duyệt Key Visual',
    description = 'Client review key visual và gửi phản hồi phiên bản 02.',
    assigner_id = (
        SELECT manager_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'huy@test.com' LIMIT 1
        ) AS manager_row
    ),
    assignee_id = (
        SELECT employee_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'employee@agency.vn' LIMIT 1
        ) AS employee_row
    ),
    watcher_id = (
        SELECT client_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'client@agency.vn' LIMIT 1
        ) AS client_row
    )
WHERE id = 2;

UPDATE tasks
SET
    title = 'Bàn giao tài liệu',
    description = 'Tổng hợp file thiết kế, nội dung và hướng dẫn vận hành.',
    assigner_id = (
        SELECT manager_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'huy@test.com' LIMIT 1
        ) AS manager_row
    ),
    assignee_id = (
        SELECT employee_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'employee@agency.vn' LIMIT 1
        ) AS employee_row
    ),
    watcher_id = (
        SELECT client_row.id
        FROM (
            SELECT id FROM employees WHERE email = 'client@agency.vn' LIMIT 1
        ) AS client_row
    )
WHERE id = 3;

SET FOREIGN_KEY_CHECKS = 1;