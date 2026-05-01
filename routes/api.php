<?php
/**
 * NEXUS AGENCY HUB - DEFINITION OF ROUTES
 * Kết hợp hoàn hảo Logic API và Điều hướng Hệ thống
 * Format: [Method, Path, Handler, Roles]
 */

return [
    // AUTH (XÁC THỰC)
    ['POST', '/api/auth/login-internal', 'Auth\\AuthController@loginInternal', null],
    ['POST', '/api/auth/login-client',   'Auth\\AuthController@loginClient', null],
    ['POST', '/api/auth/register-client','Auth\\AuthController@registerClient', null],
    ['GET', '/api/auth/me', 'Auth\\AuthController@me', ['admin', 'manager', 'employee', 'client']],
    ['GET', '/auth/logout', 'Auth\\LogoutController@index', null],

    // DASHBOARD (BẢNG ĐIỀU KHIỂN)
    ['GET', '/api/dashboard/stats', 'DashboardController@getStats', ['admin', 'manager']],

    // HRM (QUẢN TRỊ NHÂN SỰ & TỔ CHỨC)
    ['GET', '/api/organization/data', 'OrganizationController@getOrgData', ['admin', 'manager', 'employee']],
    ['POST', '/api/organization/store', 'OrganizationController@storeDepartment', ['admin', 'manager']],
    ['POST', '/api/organization/positions/store', 'OrganizationController@storePosition', ['admin', 'manager']],

    ['GET',    '/api/employees',             'HRM\\EmployeeController@index',        ['admin', 'manager']],
    ['POST',   '/api/employees',             'HRM\\EmployeeController@store',        ['admin', 'manager']],
    ['GET',    '/api/employees/:id',         'HRM\\EmployeeController@show',         ['admin', 'manager', 'employee']],
    ['PUT',    '/api/employees/:id',         'HRM\\EmployeeController@update',       ['admin', 'manager', 'employee']],
    ['DELETE', '/api/employees/:id',         'HRM\\EmployeeController@destroy',      ['admin', 'manager']],

    ['POST',   '/api/employees/:id/adjust-leave', 'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST',   '/api/employees/:id/avatar',       'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],

    // PROJECT (QUẢN LÝ DỰ ÁN)
    ['GET',    '/api/projects',        'ProjectController@index',  ['admin','manager']],
    ['GET',    '/api/projects/:id',    'ProjectController@show',   ['admin','manager']],
    ['POST',   '/api/projects',        'ProjectController@store',  ['admin','manager']],
    ['PUT',    '/api/projects/:id',    'ProjectController@update', ['admin','manager']],
    ['DELETE', '/api/projects/:id',    'ProjectController@delete', ['admin', 'manager']],

    // TASK (QUẢN LÝ CÔNG VIỆC)
    ['GET',    '/api/tasks',            'Task\\TaskController@index',        ['admin', 'manager', 'employee', 'client']],
    ['POST',   '/api/tasks',            'Task\\TaskController@store',        ['admin', 'manager']],
    ['PUT',    '/api/tasks/:id',        'Task\\TaskController@update',       ['admin', 'manager']],
    ['DELETE', '/api/tasks/:id',        'Task\\TaskController@destroy',      ['admin', 'manager']],
    ['PATCH',  '/api/tasks/:id/status', 'Task\\TaskController@updateStatus', ['admin', 'manager', 'employee']],

    // TASK APPROVAL (PHÊ DUYỆT CÔNG VIỆC)
    ['POST', '/api/tasks/:id/submit',  'Task\\TaskApprovalController@submit', ['employee', 'admin', 'manager']],
    ['POST', '/api/tasks/:id/approve', 'Task\\TaskApprovalController@approve', ['admin','manager']],
    ['POST', '/api/tasks/:id/reject',  'Task\\TaskApprovalController@reject', ['admin','manager']],
    ['GET',  '/api/tasks/submit',      'Task\\TaskApprovalController@getReviewTasks', ['admin','manager']],

    // ASSIGN & ATTACHMENTS
    ['POST', '/api/tasks/:id/assign', 'Task\\TaskAssignController@assign', ['admin','manager']],
    ['POST', '/api/tasks/:id/attachments', 'Task\\TaskAttachmentController@upload', ['admin','manager','employee']],
    ['GET',  '/api/tasks/:id/attachments', 'Task\\TaskAttachmentController@list', ['admin','manager','employee']],
    ['GET',  '/api/attachments/:id/download', 'Task\\TaskAttachmentController@download', ['admin','manager','employee']],

    // ACTIVITY & HISTORY
    ['GET', '/api/tasks/:id/activity', 'Task\\TaskActivityController@history', ['admin','manager','employee']],

    // CORE SERVICES (NOTIFICATIONS & COMMENTS)
    ['GET', '/api/notifications',               'Core\\NotificationController@index', ['admin', 'manager', 'employee']],
    ['GET', '/api/notifications/unread',        'Core\\NotificationController@unread', ['admin', 'manager', 'employee']],
    ['GET', '/api/notifications/unread-count',  'Core\\NotificationController@unreadCount', ['admin', 'manager', 'employee']],
    ['PATCH', '/api/notifications/:id/read',    'Core\\NotificationController@markAsRead', ['admin', 'manager', 'employee']],

    ['GET',    '/api/tasks/comments',          'Task\\TaskCommentController@getAll', ['admin', 'manager']],
    ['GET',    '/api/tasks/comments/:id',      'Task\\TaskCommentController@getById', ['admin', 'manager', 'employee']],
    ['GET',    '/api/tasks/:id/comments',      'Task\\TaskCommentController@getByTask', ['admin', 'manager', 'employee']],
    ['POST',   '/api/tasks/:id/comments',      'Task\\TaskCommentController@store', ['admin','manager','employee']],
    ['PUT',    '/api/tasks/comments/:id',      'Task\\TaskCommentController@update', ['admin','manager','employee']],
    ['DELETE', '/api/tasks/comments/:id',      'Task\\TaskCommentController@delete', ['admin','manager','employee']],

    // PAYROLL (CHẤM CÔNG & NGHỈ PHÉP)
    ['GET',   '/api/leaves',                'Payroll\\LeaveController@index',        ['admin', 'manager', 'employee']],
    ['GET',   '/api/admin/leaves',          'Payroll\\LeaveController@adminIndex',   ['admin', 'manager']],
    ['POST',  '/api/leaves',                'Payroll\\LeaveController@store',        ['admin', 'manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve',    'Payroll\\LeaveController@approve',      ['admin', 'manager']],

    ['GET',   '/api/attendance',            'Payroll\\AttendanceController@index',    ['admin', 'manager', 'employee']],
    ['POST',  '/api/attendance/checkin',    'Payroll\\AttendanceController@checkin',  ['admin', 'manager', 'employee']],
    ['POST',  '/api/attendance/checkout',   'Payroll\\AttendanceController@checkout', ['admin', 'manager', 'employee']],
    
    // PAYROLL SUMMARY & EXPORT
    ['GET',   '/api/payroll/summary',      'Payroll\\PayrollController@getSummary',  ['admin', 'manager']],
    ['GET',   '/api/payroll/export',       'Payroll\\PayrollController@exportCsv',   ['admin', 'manager']],
];