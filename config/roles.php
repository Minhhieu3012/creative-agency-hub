<?php

return [
    'admin' => [
        'description' => 'Toàn quyền quản trị hệ thống',
        'permissions' => ['*']
    ],
    'manager' => [
        'description' => 'Quản lý dự án và nhân sự',
        'permissions' => [
            'view_employees', 'manage_tasks', 'manage_projects',
            'approve_leave_requests', 'view_attendance',
            'view_own_profile', 'checkin', 'add_comments'
        ]
    ],
    'employee' => [
        'description' => 'Nhân viên thực hiện công việc',
        'permissions' => [
            'view_tasks', 'update_task_status', 'add_comments',
            'create_leave_requests', 'view_own_profile', 'checkin'
        ]
    ],
    'client' => [
        'description' => 'Khách hàng theo dõi dự án',
        'permissions' => [
            'view_own_projects', 'view_project_tasks', 'add_comments'
        ]
    ]
];