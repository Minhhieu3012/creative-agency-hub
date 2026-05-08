-- =========================================================
-- Creative Agency Hub
-- Migration 004 - Operation Flow Schema
--
-- Mục tiêu:
-- 1. Project có nhiều Employee làm thành viên.
-- 2. Task có thể public cho Client xem.
-- 3. Task Review có người duyệt, thời gian duyệt, lý do reject.
-- 4. Comment có visibility để tách nội bộ / client.
--
-- Không tạo bảng lương.
-- Không xoá dữ liệu cũ.
-- =========================================================

USE creative_agency;

-- =========================================================
-- 1. PROJECT MEMBERS
-- Manager kéo nhiều Employee vào Project.
-- Một Project có nhiều Employee.
-- Một Employee có thể tham gia nhiều Project.
-- =========================================================

CREATE TABLE IF NOT EXISTS project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,

    project_id INT NOT NULL,
    employee_id INT NOT NULL,
    added_by INT NULL,

    role_in_project ENUM('member', 'lead', 'reviewer') NOT NULL DEFAULT 'member',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',

    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_project_members_project
        FOREIGN KEY (project_id)
        REFERENCES projects(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_project_members_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_project_members_added_by
        FOREIGN KEY (added_by)
        REFERENCES employees(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    UNIQUE KEY uq_project_member (project_id, employee_id),
    INDEX idx_project_members_project_status (project_id, status),
    INDEX idx_project_members_employee_status (employee_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 2. TASK CLIENT VISIBILITY
-- Client chỉ thấy task được Manager đánh dấu public.
-- =========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND COLUMN_NAME = 'is_client_visible'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE tasks ADD COLUMN is_client_visible TINYINT(1) NOT NULL DEFAULT 0 AFTER watcher_id',
    'SELECT "tasks.is_client_visible already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 3. TASK REVIEW METADATA
-- Manager Approve / Reject task ở trạng thái Review.
-- =========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND COLUMN_NAME = 'reviewed_by'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE tasks ADD COLUMN reviewed_by INT NULL AFTER is_client_visible',
    'SELECT "tasks.reviewed_by already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND CONSTRAINT_NAME = 'fk_tasks_reviewed_by'
);

SET @sql := IF(
    @fk_exists = 0,
    'ALTER TABLE tasks ADD CONSTRAINT fk_tasks_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES employees(id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "fk_tasks_reviewed_by already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND COLUMN_NAME = 'reviewed_at'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE tasks ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by',
    'SELECT "tasks.reviewed_at already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND COLUMN_NAME = 'reject_reason'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE tasks ADD COLUMN reject_reason TEXT NULL AFTER reviewed_at',
    'SELECT "tasks.reject_reason already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 4. TASK COMMENT VISIBILITY
-- internal = Manager/Employee nội bộ.
-- client = feedback/comment từ phía Client hoặc comment public.
-- =========================================================

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'task_comments'
      AND COLUMN_NAME = 'visibility'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE task_comments ADD COLUMN visibility ENUM("internal", "client") NOT NULL DEFAULT "internal" AFTER comment_text',
    'SELECT "task_comments.visibility already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 5. INDEXES
-- Dùng dynamic SQL để tránh lỗi khi index đã tồn tại.
-- =========================================================

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND INDEX_NAME = 'idx_tasks_client_visible'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE tasks ADD INDEX idx_tasks_client_visible (project_id, is_client_visible, status)',
    'SELECT "idx_tasks_client_visible already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tasks'
      AND INDEX_NAME = 'idx_tasks_assignee_status_deadline'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE tasks ADD INDEX idx_tasks_assignee_status_deadline (assignee_id, status, deadline)',
    'SELECT "idx_tasks_assignee_status_deadline already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'task_comments'
      AND INDEX_NAME = 'idx_task_comments_visibility'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE task_comments ADD INDEX idx_task_comments_visibility (task_id, visibility)',
    'SELECT "idx_task_comments_visibility already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;