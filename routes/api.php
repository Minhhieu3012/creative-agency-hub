<?php

return [

    // AUTH (Public)
    ['POST', '/api/auth/login',    'Auth\\AuthController@login',    null],
    ['POST', '/api/auth/register', 'Auth\\AuthController@register', null],

    // AUTH (Private)
    ['GET', '/api/auth/me', 'Auth\\AuthController@me', ['admin', 'manager', 'employee', 'client']],

    // HRM - ACCOUNTS / EMPLOYEES
    ['GET',    '/api/employees',                   'HRM\\EmployeeController@index',        ['admin', 'manager']],
    ['POST',   '/api/employees',                   'HRM\\EmployeeController@store',        ['admin']],
    ['GET',    '/api/employees/:id',               'HRM\\EmployeeController@show',         ['admin', 'manager', 'employee', 'client']],
    ['PUT',    '/api/employees/:id',               'HRM\\EmployeeController@update',       ['admin', 'manager', 'employee']],
    ['DELETE', '/api/employees/:id',               'HRM\\EmployeeController@destroy',      ['admin']],
    ['POST',   '/api/employees/:id/adjust-leave',  'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST',   '/api/employees/:id/avatar',        'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],

    // HRM - DEPARTMENTS
    ['GET',    '/api/departments',      'HRM\\DepartmentController@index',   ['admin', 'manager']],
    ['POST',   '/api/departments',      'HRM\\DepartmentController@store',   ['admin']],
    ['DELETE', '/api/departments/:id',  'HRM\\DepartmentController@destroy', ['admin']],

    // HRM - POSITIONS
    ['GET',    '/api/positions',      'HRM\\PositionController@index',   ['admin', 'manager']],
    ['POST',   '/api/positions',      'HRM\\PositionController@store',   ['admin']],
    ['DELETE', '/api/positions/:id',  'HRM\\PositionController@destroy', ['admin']],

    // PROJECT LIFECYCLE
    // Admin không tham gia project workflow.
    // Manager tạo/sửa/xóa project.
    // Employee xem project có task được giao.
    // Client xem project được chia sẻ.
    ['GET',    '/api/projects',          'Task\\ProjectController@index',   ['manager', 'employee', 'client']],
    ['GET',    '/api/projects/options',  'Task\\ProjectController@options', ['manager']],
    ['GET',    '/api/projects/:id',      'Task\\ProjectController@show',    ['manager', 'employee', 'client']],
    ['POST',   '/api/projects',          'Task\\ProjectController@store',   ['manager']],
    ['PUT',    '/api/projects/:id',      'Task\\ProjectController@update',  ['manager']],
    ['DELETE', '/api/projects/:id',      'Task\\ProjectController@delete',  ['manager']],

    // TASK
    // Admin không tham gia task workflow.
    ['GET',    '/api/tasks',            'Task\\TaskController@index',        ['manager', 'employee', 'client']],
    ['POST',   '/api/tasks',            'Task\\TaskController@store',        ['manager']],
    ['PUT',    '/api/tasks/:id',        'Task\\TaskController@update',       ['manager']],
    ['DELETE', '/api/tasks/:id',        'Task\\TaskController@destroy',      ['manager']],
    ['PATCH',  '/api/tasks/:id/status', 'Task\\TaskController@updateStatus', ['manager', 'employee']],

    // TASK APPROVAL
    ['POST', '/api/tasks/:id/submit',  'Task\\TaskApprovalController@submit',         ['employee']],
    ['POST', '/api/tasks/:id/approve', 'Task\\TaskApprovalController@approve',        ['manager']],
    ['POST', '/api/tasks/:id/reject',  'Task\\TaskApprovalController@reject',         ['manager']],
    ['GET',  '/api/tasks/submit',      'Task\\TaskApprovalController@getReviewTasks', ['manager']],

    // ASSIGN
    ['POST', '/api/tasks/:id/assign', 'Task\\TaskAssignController@assign', ['manager']],

    // ATTACHMENT
    ['POST', '/api/tasks/:id/attachments',    'Task\\TaskAttachmentController@upload',   ['manager', 'employee']],
    ['GET',  '/api/tasks/:id/attachments',    'Task\\TaskAttachmentController@list',     ['manager', 'employee', 'client']],
    ['GET',  '/api/attachments/:id/download', 'Task\\TaskAttachmentController@download', ['manager', 'employee', 'client']],

    // ACTIVITY
    ['GET', '/api/tasks/:id/activity', 'Task\\TaskActivityController@history', ['manager', 'employee', 'client']],

    // NOTIFICATION
    ['GET',   '/api/notifications',              'Core\\NotificationController@index',       ['admin', 'manager', 'employee', 'client']],
    ['GET',   '/api/notifications/unread',       'Core\\NotificationController@unread',      ['admin', 'manager', 'employee', 'client']],
    ['GET',   '/api/notifications/unread-count', 'Core\\NotificationController@unreadCount', ['admin', 'manager', 'employee', 'client']],
    ['PATCH', '/api/notifications/:id/read',     'Core\\NotificationController@markAsRead', ['admin', 'manager', 'employee', 'client']],

    // COMMENT
    ['GET',    '/api/tasks/comments',      'Task\\TaskCommentController@getAll',    ['manager', 'employee']],
    ['GET',    '/api/tasks/comments/:id',  'Task\\TaskCommentController@getById',   ['manager', 'employee']],
    ['GET',    '/api/tasks/:id/comments',  'Task\\TaskCommentController@getByTask', ['manager', 'employee', 'client']],
    ['POST',   '/api/tasks/:id/comments',  'Task\\TaskCommentController@store',     ['manager', 'employee']],
    ['PUT',    '/api/tasks/comments/:id',  'Task\\TaskCommentController@update',    ['manager', 'employee']],
    ['DELETE', '/api/tasks/comments/:id',  'Task\\TaskCommentController@delete',    ['manager', 'employee']],

    // ATTENDANCE & LEAVE
    ['POST',  '/api/attendance/checkin', 'Payroll\\AttendanceController@checkin', ['admin', 'manager', 'employee']],
    ['POST',  '/api/leaves',             'Payroll\\LeaveController@store',        ['admin', 'manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve', 'Payroll\\LeaveController@approve',      ['admin', 'manager']],

];