<?php

// use App\Controllers\TaskController;
// use App\Controllers\NotificationController;
// use App\Controllers\AuthController;
// use App\Controllers\HRM\EmployeeController;
// use App\Controllers\AttendanceController;    
// use App\Controllers\LeaveController;

return [

    // AUTH (Public)
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
    ['POST',  '/api/leaves',               'Payroll\\LeaveController@store',        ['admin', 'manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve',   'Payroll\\LeaveController@approve',      ['admin', 'manager']],

];