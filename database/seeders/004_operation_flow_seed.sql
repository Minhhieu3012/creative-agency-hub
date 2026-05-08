-- =========================================================
-- Creative Agency Hub
-- Seeder 004 - Operation Flow Seed
--
-- Safe version:
-- - Không hardcode department_id / position_id.
-- - Không hardcode employee id.
-- - Tự lấy ID thật bằng name/email.
-- - Chạy được nhiều lần.
-- Password test: 123456
-- =========================================================

USE creative_agency;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1. Departments
-- =========================================================

INSERT INTO departments (name, description, status, deleted_at)
VALUES
    ('Design', 'Đội thiết kế giao diện, branding và visual asset.', 'active', NULL),
    ('Development', 'Đội phát triển web, backend, frontend và hệ thống.', 'active', NULL),
    ('Marketing', 'Đội nội dung, truyền thông và chiến dịch.', 'active', NULL)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    status = VALUES(status),
    deleted_at = NULL;

SET @design_department_id := (
    SELECT id FROM departments WHERE name = 'Design' LIMIT 1
);

SET @development_department_id := (
    SELECT id FROM departments WHERE name = 'Development' LIMIT 1
);

SET @marketing_department_id := (
    SELECT id FROM departments WHERE name = 'Marketing' LIMIT 1
);

-- =========================================================
-- 2. Positions
-- =========================================================

INSERT INTO positions (name, description, status, deleted_at)
VALUES
    ('Leader', 'Vai trò lead chuyên môn hoặc lead vận hành.', 'active', NULL),
    ('Staff', 'Nhân sự thực thi công việc hằng ngày.', 'active', NULL),
    ('Client Representative', 'Đại diện khách hàng theo dõi dự án.', 'active', NULL)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    status = VALUES(status),
    deleted_at = NULL;

SET @leader_position_id := (
    SELECT id FROM positions WHERE name = 'Leader' LIMIT 1
);

SET @staff_position_id := (
    SELECT id FROM positions WHERE name = 'Staff' LIMIT 1
);

SET @client_position_id := (
    SELECT id FROM positions WHERE name = 'Client Representative' LIMIT 1
);

-- =========================================================
-- 3. Test Accounts
-- Password: 123456
-- =========================================================

INSERT INTO employees (
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
VALUES (
    @development_department_id,
    @leader_position_id,
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
)
ON DUPLICATE KEY UPDATE
    department_id = VALUES(department_id),
    position_id = VALUES(position_id),
    manager_id = NULL,
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

SET @manager_id := (
    SELECT id FROM employees WHERE email = 'manager@agency.vn' LIMIT 1
);

INSERT INTO employees (
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
VALUES (
    @design_department_id,
    @staff_position_id,
    @manager_id,
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

SET @employee_id := (
    SELECT id FROM employees WHERE email = 'employee@agency.vn' LIMIT 1
);

INSERT INTO employees (
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
VALUES (
    @marketing_department_id,
    @client_position_id,
    @manager_id,
    'CLI-TEST-001',
    'Vinamilk Client',
    'client@agency.vn',
    '$2y$10$wH6bDAyr7qLf.og9eQFnk.5GXz4C9f8d6ZllSPFs/t1zGZOCUe8lW',
    'client',
    '0900000003',
    'other',
    '1999-01-01',
    'Vinamilk',
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

SET @client_id := (
    SELECT id FROM employees WHERE email = 'client@agency.vn' LIMIT 1
);

-- =========================================================
-- 4. Project
-- =========================================================

SET @project_id := (
    SELECT id FROM projects
    WHERE name = 'Thiết kế Web Vinamilk'
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO projects (
    name,
    description,
    manager_id,
    client_id,
    status
)
SELECT
    'Thiết kế Web Vinamilk',
    'Project demo cho luồng Manager tạo project, gán client, kéo employee vào team và quản lý task.',
    @manager_id,
    @client_id,
    'Active'
WHERE @project_id IS NULL;

SET @project_id := COALESCE(@project_id, LAST_INSERT_ID());

UPDATE projects
SET
    description = 'Project demo cho luồng Manager tạo project, gán client, kéo employee vào team và quản lý task.',
    manager_id = @manager_id,
    client_id = @client_id,
    status = 'Active'
WHERE id = @project_id;

-- =========================================================
-- 5. Project Members
-- =========================================================

INSERT INTO project_members (
    project_id,
    employee_id,
    added_by,
    role_in_project,
    status,
    joined_at,
    left_at
)
VALUES (
    @project_id,
    @employee_id,
    @manager_id,
    'member',
    'active',
    NOW(),
    NULL
)
ON DUPLICATE KEY UPDATE
    added_by = VALUES(added_by),
    role_in_project = VALUES(role_in_project),
    status = 'active',
    left_at = NULL,
    updated_at = CURRENT_TIMESTAMP;

-- =========================================================
-- 6. Tasks
-- =========================================================

SET @task_logo_id := (
    SELECT id FROM tasks
    WHERE project_id = @project_id
      AND title = 'Thiết kế Logo'
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO tasks (
    project_id,
    title,
    description,
    status,
    priority,
    deadline,
    assigner_id,
    assignee_id,
    watcher_id,
    is_client_visible,
    reviewed_by,
    reviewed_at,
    reject_reason
)
SELECT
    @project_id,
    'Thiết kế Logo',
    'Thiết kế logo direction đầu tiên cho landing page Vinamilk.',
    'To do',
    'High',
    DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    @manager_id,
    @employee_id,
    @manager_id,
    1,
    NULL,
    NULL,
    NULL
WHERE @task_logo_id IS NULL;

SET @task_logo_id := COALESCE(@task_logo_id, LAST_INSERT_ID());

UPDATE tasks
SET
    description = 'Thiết kế logo direction đầu tiên cho landing page Vinamilk.',
    status = 'To do',
    priority = 'High',
    deadline = DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    assigner_id = @manager_id,
    assignee_id = @employee_id,
    watcher_id = @manager_id,
    is_client_visible = 1,
    reviewed_by = NULL,
    reviewed_at = NULL,
    reject_reason = NULL
WHERE id = @task_logo_id;

SET @task_homepage_id := (
    SELECT id FROM tasks
    WHERE project_id = @project_id
      AND title = 'Thiết kế UI Homepage'
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO tasks (
    project_id,
    title,
    description,
    status,
    priority,
    deadline,
    assigner_id,
    assignee_id,
    watcher_id,
    is_client_visible,
    reviewed_by,
    reviewed_at,
    reject_reason
)
SELECT
    @project_id,
    'Thiết kế UI Homepage',
    'Hoàn thiện section hero, brand story và CTA chính.',
    'Doing',
    'High',
    DATE_ADD(CURDATE(), INTERVAL 4 DAY),
    @manager_id,
    @employee_id,
    @manager_id,
    1,
    NULL,
    NULL,
    NULL
WHERE @task_homepage_id IS NULL;

SET @task_homepage_id := COALESCE(@task_homepage_id, LAST_INSERT_ID());

UPDATE tasks
SET
    description = 'Hoàn thiện section hero, brand story và CTA chính.',
    status = 'Doing',
    priority = 'High',
    deadline = DATE_ADD(CURDATE(), INTERVAL 4 DAY),
    assigner_id = @manager_id,
    assignee_id = @employee_id,
    watcher_id = @manager_id,
    is_client_visible = 1,
    reviewed_by = NULL,
    reviewed_at = NULL,
    reject_reason = NULL
WHERE id = @task_homepage_id;

SET @task_review_id := (
    SELECT id FROM tasks
    WHERE project_id = @project_id
      AND title = 'Review Visual Direction'
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO tasks (
    project_id,
    title,
    description,
    status,
    priority,
    deadline,
    assigner_id,
    assignee_id,
    watcher_id,
    is_client_visible,
    reviewed_by,
    reviewed_at,
    reject_reason
)
SELECT
    @project_id,
    'Review Visual Direction',
    'Employee upload file thiết kế và chuyển sang Review để Manager duyệt.',
    'Review',
    'Medium',
    DATE_ADD(CURDATE(), INTERVAL 5 DAY),
    @manager_id,
    @employee_id,
    @manager_id,
    0,
    NULL,
    NULL,
    NULL
WHERE @task_review_id IS NULL;

SET @task_review_id := COALESCE(@task_review_id, LAST_INSERT_ID());

UPDATE tasks
SET
    description = 'Employee upload file thiết kế và chuyển sang Review để Manager duyệt.',
    status = 'Review',
    priority = 'Medium',
    deadline = DATE_ADD(CURDATE(), INTERVAL 5 DAY),
    assigner_id = @manager_id,
    assignee_id = @employee_id,
    watcher_id = @manager_id,
    is_client_visible = 0,
    reviewed_by = NULL,
    reviewed_at = NULL,
    reject_reason = NULL
WHERE id = @task_review_id;

SET @task_kickoff_id := (
    SELECT id FROM tasks
    WHERE project_id = @project_id
      AND title = 'Kickoff Project'
    ORDER BY id ASC
    LIMIT 1
);

INSERT INTO tasks (
    project_id,
    title,
    description,
    status,
    priority,
    deadline,
    assigner_id,
    assignee_id,
    watcher_id,
    is_client_visible,
    reviewed_by,
    reviewed_at,
    reject_reason
)
SELECT
    @project_id,
    'Kickoff Project',
    'Hoàn tất checklist kickoff và tài liệu brief ban đầu.',
    'Done',
    'Low',
    DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    @manager_id,
    @employee_id,
    @manager_id,
    1,
    @manager_id,
    NOW(),
    NULL
WHERE @task_kickoff_id IS NULL;

SET @task_kickoff_id := COALESCE(@task_kickoff_id, LAST_INSERT_ID());

UPDATE tasks
SET
    description = 'Hoàn tất checklist kickoff và tài liệu brief ban đầu.',
    status = 'Done',
    priority = 'Low',
    deadline = DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    assigner_id = @manager_id,
    assignee_id = @employee_id,
    watcher_id = @manager_id,
    is_client_visible = 1,
    reviewed_by = @manager_id,
    reviewed_at = NOW(),
    reject_reason = NULL
WHERE id = @task_kickoff_id;

-- =========================================================
-- 7. Comments
-- =========================================================

DELETE FROM task_comments
WHERE task_id IN (@task_logo_id, @task_review_id, @task_kickoff_id)
  AND comment_text IN (
    'Sếp duyệt giúp em visual direction bản đầu tiên.',
    'Approved. Checklist kickoff đã ổn.',
    'Client muốn logo giữ cảm giác tươi, sạch và dễ nhận diện hơn.'
  );

INSERT INTO task_comments (
    task_id,
    user_id,
    comment_text,
    visibility
)
VALUES
    (
        @task_review_id,
        @employee_id,
        'Sếp duyệt giúp em visual direction bản đầu tiên.',
        'internal'
    ),
    (
        @task_kickoff_id,
        @manager_id,
        'Approved. Checklist kickoff đã ổn.',
        'internal'
    ),
    (
        @task_logo_id,
        @client_id,
        'Client muốn logo giữ cảm giác tươi, sạch và dễ nhận diện hơn.',
        'client'
    );

-- =========================================================
-- 8. Notifications
-- =========================================================

DELETE FROM notifications
WHERE message IN (
    'Employee Test đã gửi task "Review Visual Direction" sang Review.',
    'Bạn được giao task "Thiết kế Logo" trong project Thiết kế Web Vinamilk.'
);

INSERT INTO notifications (
    user_id,
    message,
    is_read
)
VALUES
    (
        @manager_id,
        'Employee Test đã gửi task "Review Visual Direction" sang Review.',
        FALSE
    ),
    (
        @employee_id,
        'Bạn được giao task "Thiết kế Logo" trong project Thiết kế Web Vinamilk.',
        FALSE
    );

-- =========================================================
-- 9. Activity Logs
-- =========================================================

DELETE FROM task_activity_logs
WHERE description IN (
    'Manager giao task Thiết kế Logo cho Employee Test.',
    'Employee chuyển task sang Review.',
    'Manager approve task Kickoff Project.'
);

INSERT INTO task_activity_logs (
    task_id,
    user_id,
    action,
    description
)
VALUES
    (
        @task_logo_id,
        @manager_id,
        'assign',
        'Manager giao task Thiết kế Logo cho Employee Test.'
    ),
    (
        @task_review_id,
        @employee_id,
        'status_change',
        'Employee chuyển task sang Review.'
    ),
    (
        @task_kickoff_id,
        @manager_id,
        'status_change',
        'Manager approve task Kickoff Project.'
    );

SET FOREIGN_KEY_CHECKS = 1;