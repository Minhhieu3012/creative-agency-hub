-- =========================================================================
-- MIGRATION: 002_HRM_SCHEMA
-- OWNER: THÀNH (MODULE HRM)
-- MỤC TIÊU: Khởi tạo 5 bảng lõi nhân sự kèm ràng buộc toàn vẹn dữ liệu
-- =========================================================================

USE creative_agency;

-- Tắt kiểm tra khóa ngoại tạm thời để có thể Drop bảng an toàn khi chạy lại script
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS employee_leave_adjustments;
DROP TABLE IF EXISTS employee_contracts;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS departments;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. BẢNG PHÒNG BAN (Departments)
-- =========================================================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    -- Ghi chú thiết kế: Giữ UNIQUE để tránh tái sử dụng tên phòng ban lịch sử.
    INDEX idx_departments_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- 2. BẢNG CHỨC VỤ (Positions)
-- =========================================================================
CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    INDEX idx_positions_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- 3. BẢNG NHÂN VIÊN (Employees)
-- =========================================================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    position_id INT NOT NULL,
    manager_id INT NULL,
    employee_code VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'employee') NOT NULL DEFAULT 'employee',
    phone VARCHAR(20) NULL,
    gender ENUM('male', 'female', 'other') NULL,
    date_of_birth DATE NULL,
    address TEXT NULL,
    avatar VARCHAR(255) NULL,
    total_leave_days INT NOT NULL DEFAULT 12,
    remaining_leave_days DECIMAL(5,2) NOT NULL DEFAULT 12.00,
    status ENUM('active', 'inactive', 'resigned', 'suspended') NOT NULL DEFAULT 'active',
    hire_date DATE NOT NULL, 
    resigned_date DATE NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Ràng buộc logic ngày tháng và vòng đời nhân sự
    CONSTRAINT chk_emp_dates CHECK (resigned_date IS NULL OR resigned_date >= hire_date),
    CONSTRAINT chk_employee_status_resigned CHECK (
        (status = 'resigned' AND resigned_date IS NOT NULL) OR (status <> 'resigned')
    ),
    
    -- Ràng buộc toán học quỹ phép
    CONSTRAINT chk_leave_days CHECK (
        total_leave_days >= 0 
        AND remaining_leave_days >= 0 
        AND remaining_leave_days <= total_leave_days
    ),

    -- Tối ưu hóa truy vấn
    INDEX idx_employees_deleted_at (deleted_at),
    INDEX idx_employees_status_deleted (status, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- 4. BẢNG HỢP ĐỒNG LAO ĐỘNG (Employee Contracts)
-- =========================================================================
CREATE TABLE employee_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    contract_code VARCHAR(100) NOT NULL UNIQUE,
    contract_type ENUM('probation', 'fixed_term', 'indefinite') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    salary DECIMAL(15, 2) NOT NULL,
    status ENUM('active', 'expired', 'terminated') NOT NULL DEFAULT 'active',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    CONSTRAINT chk_contract_dates CHECK (end_date IS NULL OR end_date >= start_date),
    CONSTRAINT chk_contract_salary CHECK (salary > 0),
    
    INDEX idx_contracts_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================================================================
-- 5. BẢNG NHẬT KÝ ĐIỀU CHỈNH QUỸ PHÉP (Leave Adjustments)
-- =========================================================================
CREATE TABLE employee_leave_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    adjustment_days DECIMAL(5, 2) NOT NULL,
    old_remaining_days DECIMAL(5, 2) NOT NULL,
    new_remaining_days DECIMAL(5, 2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES employees(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    CONSTRAINT chk_leave_adjustment_math CHECK (new_remaining_days = old_remaining_days + adjustment_days),
    CONSTRAINT chk_leave_adjustment_non_negative CHECK (
        old_remaining_days >= 0 AND new_remaining_days >= 0
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;