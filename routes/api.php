<?php
/**
 * NEXUS AGENCY HUB - DEFINITION OF ROUTES
 * Kết hợp hoàn hảo Logic API và Điều hướng Hệ thống
 * Format: [Method, Path, Handler, Roles]
 */

return [

    // AUTH (Public)
    ['POST', '/api/auth/login-internal', 'Auth\\AuthController@loginInternal', null],
    ['POST', '/api/auth/login-client',   'Auth\\AuthController@loginClient', null],
    ['POST', '/api/auth/register-client','Auth\\AuthController@registerClient', null],


    // AUTH (Private)
    ['GET', '/api/auth/me', 'Auth\\AuthController@me', ['admin', 'manager', 'employee', 'client']],

     // Hệ thống xử lý Đăng xuất (Điều hướng về Login)
    ['GET', '/auth/logout', 'Auth\\LogoutController@index', null],

    // ==============================================================================
    // HRM (QUẢN TRỊ NHÂN SỰ)
    // ==============================================================================
    ['GET',    '/api/employees',             'HRM\\EmployeeController@index',        ['admin', 'manager']],
    ['POST',   '/api/employees',             'HRM\\EmployeeController@store',        ['admin']],
    ['GET',    '/api/employees/:id',         'HRM\\EmployeeController@show',         ['admin', 'manager']],
    ['PUT',    '/api/employees/:id',         'HRM\\EmployeeController@update',       ['admin', 'manager']],
    ['DELETE', '/api/employees/:id',         'HRM\\EmployeeController@destroy',      ['admin']],

    ['POST',   '/api/employees/:id/adjust-leave', 'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST',   '/api/employees/:id/avatar',       'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],

    // ==============================================================================
    // PROJECT (QUẢN LÝ DỰ ÁN)
    // ==============================================================================
    ['GET',    '/api/projects',        'ProjectController@index',  ['admin','manager']],
    ['GET',    '/api/projects/:id',    'ProjectController@show',   ['admin','manager']],
    ['POST',   '/api/projects',        'ProjectController@store',  ['admin','manager']],
    ['PUT',    '/api/projects/:id',    'ProjectController@update', ['admin','manager']],
    ['DELETE', '/api/projects/:id',    'ProjectController@delete', ['admin', 'manager']],

    // ==============================================================================
    // TASK (QUẢN LÝ CÔNG VIỆC)
    // ==============================================================================
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
    ['GET',  '/api/tasks/:id/attachments', 'Task\\TaskAttachmentController@list', null],
    ['GET',  '/api/attachments/:id/download', 'Task\\TaskAttachmentController@download', null],

    // ACTIVITY & HISTORY
    ['GET', '/api/tasks/:id/activity', 'Task\\TaskActivityController@history', null],

    // ==============================================================================
    // CORE SERVICES (NOTIFICATIONS & COMMENTS)
    // ==============================================================================
    ['GET', '/api/notifications',               'Core\\NotificationController@index', null],
    ['GET', '/api/notifications/unread',        'Core\\NotificationController@unread', null],
    ['GET', '/api/notifications/unread-count',  'Core\\NotificationController@unreadCount', null],
    ['PATCH', '/api/notifications/:id/read',    'Core\\NotificationController@markAsRead', null],

    ['GET',    '/api/tasks/comments',          'Task\\TaskCommentController@getAll', null],
    ['GET',    '/api/tasks/comments/:id',      'Task\\TaskCommentController@getById', null],
    ['GET',    '/api/tasks/:id/comments',      'Task\\TaskCommentController@getByTask', null],
    ['POST',   '/api/tasks/:id/comments',      'Task\\TaskCommentController@store', ['admin','manager','employee']],
    ['PUT',    '/api/tasks/comments/:id',      'Task\\TaskCommentController@update', ['admin','manager','employee']],
    ['DELETE', '/api/tasks/comments/:id',      'Task\\TaskCommentController@delete', ['admin','manager','employee']],

    // ==============================================================================
    // PAYROLL (CHẤM CÔNG & NGHỈ PHÉP)
    // ==============================================================================
    ['POST',  '/api/attendance/checkin',   'Payroll\\AttendanceController@checkin', ['admin', 'manager', 'employee']],
    ['POST',  '/api/leaves',               'Payroll\\LeaveController@store',        ['admin', 'manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve',   'Payroll\\LeaveController@approve',      ['admin', 'manager']],

];