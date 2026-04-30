<?php

// use App\Controllers\TaskController;
// use App\Controllers\NotificationController;
// use App\Controllers\AuthController;
// use App\Controllers\HRM\EmployeeController;
// use App\Controllers\AttendanceController;    
// use App\Controllers\LeaveController;

return [

    // AUTH (Public)

    // 1. HIỂN THỊ GIAO DIỆN (khi gõ URL trên trình duyệt)
    ['GET', '/login', 'Auth\\AuthController@showLoginForm', null],

    // 2. XỬ LÝ DỮ LIỆU (khi bấm nút Đăng nhập)
    ['POST', '/api/auth/login',    'Auth\\AuthController@login',    null],
    ['POST', '/api/auth/register', 'Auth\\AuthController@register', null],

    // AUTH (Private)
    ['GET', '/api/auth/me', 'Auth\\AuthController@me', ['admin', 'manager', 'employee', 'client']],

    // HRM 
    ['GET',    '/api/employees',             'HRM\\EmployeeController@index',        ['admin', 'manager']],
    ['POST',   '/api/employees',             'HRM\\EmployeeController@store',        ['admin']],
    ['GET',    '/api/employees/:id',         'HRM\\EmployeeController@show',         ['admin', 'manager']],
    ['PUT',    '/api/employees/:id',         'HRM\\EmployeeController@update',       ['admin', 'manager']],
    ['DELETE', '/api/employees/:id',         'HRM\\EmployeeController@destroy',      ['admin']],
    
    ['POST',   '/api/employees/:id/adjust-leave', 'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST',   '/api/employees/:id/avatar',       'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],
    
    // PROJECT
    ['GET',    '/api/projects',        'ProjectController@index', ['admin','manager']],
    ['GET',    '/api/projects/:id',    'ProjectController@show', ['admin','manager']],
    ['POST',   '/api/projects',        'ProjectController@store', ['admin','manager']],
    ['PUT',    '/api/projects/:id',    'ProjectController@update', ['admin','manager']],
    ['DELETE', '/api/projects/:id',    'ProjectController@delete', ['admin', 'manager']],

    // TASK
    ['GET',   '/api/tasks',               'Task\\TaskController@index',        ['admin', 'manager', 'employee', 'client']],
    ['POST',  '/api/tasks',               'Task\\TaskController@store',        ['admin', 'manager']],
    ['PUT',   '/api/tasks/:id',           'Task\\TaskController@update',       ['admin', 'manager']],
    ['PATCH', '/api/tasks/:id/status',    'Task\\TaskController@updateStatus', ['admin', 'manager', 'employee']],

    // TASK APPROVAL
    ['POST', '/api/tasks/:id/submit',  'Task\\TaskApprovalController@submit', ['employee']],
    ['POST', '/api/tasks/:id/approve', 'Task\\TaskApprovalController@approve', ['admin','manager']],
    ['POST', '/api/tasks/:id/reject',  'Task\\TaskApprovalController@reject', ['admin','manager']],
    ['GET',  '/api/tasks/submit',      'Task\\TaskApprovalController@getReviewTasks', ['admin','manager']],

    // ASSIGN
    ['POST', '/api/tasks/:id/assign', 'Task\\TaskAssignController@assign', ['admin','manager']],

    // ATTACHMENT
    ['POST', '/api/tasks/:id/attachments', 'Task\\TaskAttachmentController@upload', ['admin','manager','employee']],
    ['GET',  '/api/tasks/:id/attachments', 'Task\\TaskAttachmentController@list', null],
    ['GET',  '/api/attachments/:id/download', 'Task\\TaskAttachmentController@download', null],

    // ACTIVITY
    ['GET', '/api/tasks/:id/activity', 'Task\\TaskActivityController@history', null],

    // NOTIFICATION
    ['GET', '/api/notifications',              'Core\\NotificationController@index', null],
    ['GET', '/api/notifications/unread',       'Core\\NotificationController@unread', null],
    ['GET', '/api/notifications/unread-count', 'Core\\NotificationController@unreadCount', null],
    ['PATCH', '/api/notifications/:id/read',   'Core\\NotificationController@markAsRead', null],

    // COMMENT
    ['GET',    '/api/tasks/comments',         'Task\\TaskCommentController@getAll', null],
    ['GET',    '/api/tasks/comments/:id',     'Task\\TaskCommentController@getById', null],
    ['GET',    '/api/tasks/:id/comments',     'Task\\TaskCommentController@getByTask', null],
    ['POST',   '/api/tasks/:id/comments',     'Task\\TaskCommentController@store', ['admin','manager','employee']],
    ['PUT',    '/api/tasks/comments/:id',     'Task\\TaskCommentController@update', ['admin','manager','employee']],
    ['DELETE', '/api/tasks/comments/:id',     'Task\\TaskCommentController@delete', ['admin','manager','employee']],

    // ATTENDANCE & LEAVE
    ['POST',  '/api/attendance/checkin',   'Payroll\\AttendanceController@checkin', ['admin', 'manager', 'employee']],
    ['POST', '/api/tasks/:id/comments', 'Task\\TaskCommentController@store', ['admin','manager','employee','client']],
    ['PATCH', '/api/leaves/:id/approve',   'Payroll\\LeaveController@approve',      ['admin', 'manager']],

    // GIAO DIỆN TRANG TĨNH & AUTH BỔ SUNG
    // HIỂN THỊ GIAO DIỆN (Clean URLs)
    ['GET', '/client-portal/forgot-password', 'Auth\\AuthController@showForgotPasswordForm', null],
    ['GET', '/client-portal/contact',         'Core\\PageController@showContact', null],
    ['GET', '/privacy',                       'Core\\PageController@showPrivacy', null],
    ['GET', '/terms',                         'Core\\PageController@showTerms', null],
    ['GET', '/client-portal/login-client', 'Auth\\AuthController@showClientLoginForm', null],

    // API XỬ LÝ DỮ LIỆU
    ['POST', '/api/auth/forgot-password', 'Auth\\AuthController@forgotPassword', null],
    ['POST', '/api/contact/send',          'Core\\PageController@handleContact', null],
    
    ['GET', '/forgot-password', 'Auth\\AuthController@showForgotPasswordForm', null],
];