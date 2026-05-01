<?php
namespace App\Seeders;

use Core\Database;
use PDO;

class AdminSeeder {
    public static function run() {
        $db = Database::getConnection();

        // Kiểm tra đã có admin chưa
        $stmt = $db->prepare("SELECT id FROM employees WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            return; // đã có → không tạo nữa
        }

        // Tạo admin mặc định
        $stmt = $db->prepare("
            INSERT INTO employees 
            (department_id, position_id, employee_code, full_name, email, password, role, status, hire_date)
            VALUES 
            (1, 1, 'ADMIN001', :name, :email, :password, 'admin', 'active', CURDATE())
        ");

        $stmt->execute([
            'name' => 'Super Admin',
            'email' => 'admin@cah.com',
            'password' => password_hash('123456', PASSWORD_BCRYPT)
        ]);

        echo "✅ Admin account created: admin@cah.com / 123456\n";
    }
}