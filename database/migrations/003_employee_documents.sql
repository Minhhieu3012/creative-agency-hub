USE creative_agency;

CREATE TABLE IF NOT EXISTS employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    uploaded_by INT NULL,

    document_type ENUM('identity', 'contract', 'education', 'profile', 'other') NOT NULL DEFAULT 'other',
    title VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    file_size INT UNSIGNED NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,

    CONSTRAINT fk_employee_documents_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_employee_documents_uploaded_by
        FOREIGN KEY (uploaded_by)
        REFERENCES employees(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_employee_documents_employee_deleted (employee_id, deleted_at),
    INDEX idx_employee_documents_type (document_type),
    INDEX idx_employee_documents_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;