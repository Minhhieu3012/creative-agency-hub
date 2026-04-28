<?php

namespace App\Middleware;

class RoleMiddleware {
    /**
     * Kiểm tra quyền truy cập dựa trên Role
     * @param array $authUser Dữ liệu user từ AuthMiddleware
     * @param array $allowedRoles Danh sách các role được phép (ví dụ: ['admin', 'manager'])
     */
    public static function handle($authUser, $allowedRoles) {
        $userRole = strtolower($authUser['role'] ?? '');

        // Lấy permissions từ config
        $roles           = require __DIR__ . '/../../config/roles.php';
        $userPermissions = $roles[$userRole]['permissions'] ?? [];

        // Admin có wildcard '*' -> bypass tất cả
        if (in_array('*', $userPermissions) || in_array($userRole, $allowedRoles)) {
            return true;
        }

        // Không đủ quyền -> 403 Forbidden
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            "status"  => "error",
            "message" => "Forbidden: Bạn không có quyền thực hiện hành động này."
        ]);
        exit;
    }
}