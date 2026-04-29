<?php

// use App\Controllers\TaskController;
// use App\Controllers\NotificationController;
// use App\Controllers\AuthController;
// use App\Controllers\HRM\EmployeeController;
// use App\Controllers\AttendanceController;    
// use App\Controllers\LeaveController;

return [

    // AUTH (Public)
    ['POST', '/api/auth/login',    'AuthController@login',    null],
    ['POST', '/api/auth/register', 'AuthController@register', null],

    // AUTH (Private)
    ['GET', '/api/auth/me', 'AuthController@me', ['admin', 'manager', 'employee', 'client']],

    // HRM 
    ['GET',    '/api/employees',             'HRM\\EmployeeController@index',        ['admin', 'manager']],
    ['POST',   '/api/employees',             'HRM\\EmployeeController@store',        ['admin']],
    ['GET',    '/api/employees/:id',         'HRM\\EmployeeController@show',         ['admin', 'manager']],
    ['PUT',    '/api/employees/:id',         'HRM\\EmployeeController@update',       ['admin', 'manager']],
    ['DELETE', '/api/employees/:id',         'HRM\\EmployeeController@destroy',      ['admin']],
    
    ['POST',   '/api/employees/:id/adjust-leave', 'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST',   '/api/employees/:id/avatar',       'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],
    
    // TASK
    ['GET',   '/api/tasks',               'TaskController@index',        ['admin', 'manager', 'employee', 'client']],
    ['POST',  '/api/tasks',               'TaskController@store',        ['admin', 'manager']],
    ['PUT',   '/api/tasks/:id',           'TaskController@update',       ['admin', 'manager']],
    ['PATCH', '/api/tasks/:id/status',    'TaskController@updateStatus', ['admin', 'manager', 'employee']],

    // TASK APPROVAL
    ['POST', '/api/tasks/:id/submit',  'TaskApprovalController@submit', ['employee']],
    ['POST', '/api/tasks/:id/approve', 'TaskApprovalController@approve', ['admin','manager']],
    ['POST', '/api/tasks/:id/reject',  'TaskApprovalController@reject', ['admin','manager']],
    ['GET',  '/api/tasks/submit',      'TaskApprovalController@getReviewTasks', ['admin','manager']],

    // ASSIGN
    ['POST', '/api/tasks/:id/assign', 'TaskAssignController@assign', ['admin','manager']],

    // ATTACHMENT
    ['POST', '/api/tasks/:id/attachments', 'TaskAttachmentController@upload', ['admin','manager','employee']],
    ['GET',  '/api/tasks/:id/attachments', 'TaskAttachmentController@list', null],
    ['GET',  '/api/attachments/:id/download', 'TaskAttachmentController@download', null],

    // ACTIVITY
    ['GET', '/api/tasks/:id/activity', 'TaskActivityController@history', null],

    // NOTIFICATION
    ['GET', '/api/notifications',              'NotificationController@index', null],
    ['GET', '/api/notifications/unread',       'NotificationController@unread', null],
    ['GET', '/api/notifications/unread-count', 'NotificationController@unreadCount', null],
    ['PATCH', '/api/notifications/:id/read',   'NotificationController@markAsRead', null],

    // COMMENT
    ['GET',    '/api/tasks/comments',         'TaskCommentController@getAll', null],
    ['GET',    '/api/tasks/comments/:id',     'TaskCommentController@getById', null],
    ['GET',    '/api/tasks/:id/comments',     'TaskCommentController@getByTask', null],
    ['POST',   '/api/tasks/:id/comments',     'TaskCommentController@store', ['admin','manager','employee']],
    ['PUT',    '/api/tasks/comments/:id',     'TaskCommentController@update', ['admin','manager','employee']],
    ['DELETE', '/api/tasks/comments/:id',     'TaskCommentController@delete', ['admin','manager','employee']],

    // ATTENDANCE & LEAVE
    ['POST',  '/api/attendance/checkin',   'AttendanceController@checkin', ['admin', 'manager', 'employee']],
    ['POST',  '/api/leaves',               'LeaveController@store',        ['admin', 'manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve',   'LeaveController@approve',      ['admin', 'manager']],

];