CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `priority` ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    `status` ENUM('To do', 'Doing', 'Done') DEFAULT 'To do',
    `start_date` DATE NOT NULL,
    `deadline` DATE NOT NULL,
    `assignee_id` INT NULL, -- Liên kết với bảng employees của Thành [cite: 255]
    `watcher_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Ràng buộc sau này sẽ thêm khi Hiếu xong bảng employees
    -- CONSTRAINT fk_assignee FOREIGN KEY (assignee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu để test [cite: 514]
INSERT INTO `tasks` (`title`, `description`, `priority`, `status`, `start_date`, `deadline`) VALUES
('Thiết kế Giao diện Dior', 'Làm giao diện vuông vức, sang trọng', 'High', 'Doing', '2026-04-20', '2026-04-30'),
('Fix Bug Drag & Drop', 'Xử lý lỗi dính chuột khi kéo thả', 'High', 'To do', '2026-04-25', '2026-04-27'),
('Họp Team', 'Chốt schema với Bảo và Hiếu', 'Medium', 'Done', '2026-04-01', '2026-04-05');