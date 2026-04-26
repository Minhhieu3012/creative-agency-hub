CREATE DATABASE IF NOT EXISTS creative_agency
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE creative_agency;

-- 1. BẢNG PHÒNG BAN (Departments)
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG NHÂN VIÊN & TÀI KHOẢN (Employees)
-- Owner: Thành (HRM) & Hiếu (Auth/RBAC)
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    -- Tích hợp Role-Based Access Control (RBAC) trực tiếp vào Schema
    role ENUM('Admin', 'Manager', 'Employee', 'Client') NOT NULL DEFAULT 'Employee',
    -- Phục vụ bài toán tính lương cho Tiến
    base_salary DECIMAL(15,2) DEFAULT 0.00, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BẢNG CÔNG VIỆC (Tasks)
-- Owner: Huy (Kanban Board)
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('To do', 'Doing', 'Done') DEFAULT 'To do',
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    deadline DATE,
    assigner_id INT, -- Manager giao việc
    assignee_id INT, -- Employee nhận việc
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigner_id) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (assignee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG BÌNH LUẬN (Task Comments)
-- Owner: Bảo (Approval & Interaction)
CREATE TABLE task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Ràng buộc chặt: Xóa task thì comment cũng bay màu (CASCADE)
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BẢNG NGHỈ PHÉP (Leave Requests)
-- Owner: Tiến (Attendance & Payroll)
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;