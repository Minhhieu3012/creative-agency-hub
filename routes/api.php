<?php
/**
 * CREATIVE AGENCY HUB - API ROUTES
 *
 * Format:
 * [Method, Path, Handler, Roles]
 */

return [
    /**
     * AUTH
     */
    ['POST', '/api/auth/login-internal', 'Auth\\AuthController@loginInternal', null],
    ['POST', '/api/auth/login-staff',    'Auth\\AuthController@loginStaff', null],
    ['POST', '/api/auth/login-admin',    'Auth\\AuthController@loginAdmin', null],
    ['POST', '/api/auth/login-client',   'Auth\\AuthController@loginClient', null],
    ['POST', '/api/auth/register-client','Auth\\AuthController@registerClient', null],
    ['GET',  '/api/auth/me',             'Auth\\AuthController@me', ['admin', 'manager', 'employee', 'client']],
    ['GET',  '/auth/logout',             'Auth\\LogoutController@index', null],

    /**
     * DASHBOARD
     */
    ['GET', '/api/dashboard/stats', 'DashboardController@getStats', ['admin', 'manager', 'employee', 'client']],

    /**
     * ADMIN ACCOUNT GOVERNANCE
     */
    ['GET',   '/api/admin/accounts',              'Admin\\AccountController@index',        ['admin']],
    ['GET',   '/api/admin/accounts/pending',      'Admin\\AccountController@pending',      ['admin']],
    ['PATCH', '/api/admin/accounts/:id/approve',  'Admin\\AccountController@approve',      ['admin']],
    ['PATCH', '/api/admin/accounts/:id/reject',   'Admin\\AccountController@reject',       ['admin']],
    ['PATCH', '/api/admin/accounts/:id/suspend',  'Admin\\AccountController@suspend',      ['admin']],
    ['PATCH', '/api/admin/accounts/:id/activate', 'Admin\\AccountController@activate',     ['admin']],
    ['PATCH', '/api/admin/accounts/:id/status',   'Admin\\AccountController@updateStatus', ['admin']],

    /**
     * ACCOUNT CREATED BY MANAGER
     */
    ['POST', '/api/accounts', 'HRM\\EmployeeController@storeAccount', ['manager']],

    /**
     * HRM - ORGANIZATION
     */
    ['GET',  '/api/organization/data',            'OrganizationController@getOrgData',       ['admin', 'manager', 'employee']],
    ['POST', '/api/organization/store',           'OrganizationController@storeDepartment',  ['admin']],
    ['POST', '/api/organization/positions/store', 'OrganizationController@storePosition',    ['admin']],

    /**
     * HRM - EMPLOYEES
     */
    ['GET',    '/api/employees',     'HRM\\EmployeeController@index',   ['admin', 'manager']],
    ['POST',   '/api/employees',     'HRM\\EmployeeController@store',   ['admin', 'manager']],
    ['GET',    '/api/employees/:id', 'HRM\\EmployeeController@show',    ['admin', 'manager', 'employee']],
    ['PUT',    '/api/employees/:id', 'HRM\\EmployeeController@update',  ['admin', 'manager', 'employee']],
    ['DELETE', '/api/employees/:id', 'HRM\\EmployeeController@destroy', ['admin']],

    ['POST', '/api/employees/:id/adjust-leave', 'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST', '/api/employees/:id/avatar',       'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],

    /**
     * EMPLOYEE DOCUMENTS
     */
    ['GET',    '/api/employees/:id/documents',         'HRM\\EmployeeController@documents',        ['admin', 'manager', 'employee']],
    ['POST',   '/api/employees/:id/documents',         'HRM\\EmployeeController@uploadDocument',   ['admin', 'manager', 'employee']],
    ['GET',    '/api/employee-documents/:id/download', 'HRM\\EmployeeController@downloadDocument', ['admin', 'manager', 'employee']],
    ['DELETE', '/api/employee-documents/:id',          'HRM\\EmployeeController@deleteDocument',   ['admin', 'manager', 'employee']],

    /**
     * PROJECT
     * Admin chỉ đọc thống kê/danh sách, không tạo/sửa/xóa.
     */
    ['GET',    '/api/projects',                         'Task\\ProjectController@index',        ['admin', 'manager', 'employee', 'client']],
    ['GET',    '/api/projects/options',                 'Task\\ProjectController@options',      ['admin', 'manager']],
    ['GET',    '/api/projects/:id',                     'Task\\ProjectController@show',         ['admin', 'manager', 'employee', 'client']],
    ['POST',   '/api/projects',                         'Task\\ProjectController@store',        ['manager']],
    ['PUT',    '/api/projects/:id',                     'Task\\ProjectController@update',       ['manager']],
    ['DELETE', '/api/projects/:id',                     'Task\\ProjectController@delete',       ['manager']],

    ['GET',    '/api/projects/:id/members',             'Task\\ProjectController@members',      ['admin', 'manager', 'employee']],
    ['POST',   '/api/projects/:id/members',             'Task\\ProjectController@addMember',    ['manager']],
    ['DELETE', '/api/projects/:id/members/:employeeId', 'Task\\ProjectController@removeMember', ['manager']],

    /**
     * TASK COMMENTS
     */
    ['GET',    '/api/tasks/comments',     'Task\\TaskCommentController@getAll',    ['admin', 'manager']],
    ['GET',    '/api/tasks/comments/:id', 'Task\\TaskCommentController@getById',   ['admin', 'manager', 'employee', 'client']],
    ['GET',    '/api/tasks/:id/comments', 'Task\\TaskCommentController@getByTask', ['admin', 'manager', 'employee', 'client']],
    ['POST',   '/api/tasks/:id/comments', 'Task\\TaskCommentController@store',     ['manager', 'employee', 'client']],
    ['PUT',    '/api/tasks/comments/:id', 'Task\\TaskCommentController@update',    ['admin', 'manager', 'employee', 'client']],
    ['DELETE', '/api/tasks/comments/:id', 'Task\\TaskCommentController@delete',    ['admin', 'manager', 'employee', 'client']],

    /**
     * TASK
     * Admin chỉ đọc thống kê/danh sách, không vận hành task.
     */
    ['GET',    '/api/tasks',             'Task\\TaskController@index',          ['admin', 'manager', 'employee', 'client']],
    ['GET',    '/api/tasks/options',     'Task\\TaskController@options',        ['manager']],
    ['GET',    '/api/tasks/kanban',      'Task\\TaskController@kanban',         ['admin', 'manager', 'employee', 'client']],
    ['GET',    '/api/tasks/review',      'Task\\TaskController@getReviewTasks', ['manager']],
    ['GET',    '/api/tasks/submit',      'Task\\TaskController@getReviewTasks', ['manager']],
    
    // ĐÃ THÊM: Route Soi Task sắp hết hạn
    ['GET',    '/api/tasks/upcoming',    'Task\\TaskController@upcoming',       ['admin', 'manager']],

    ['GET',    '/api/tasks/:id',         'Task\\TaskController@show',         ['admin', 'manager', 'employee', 'client']],
    ['POST',   '/api/tasks',             'Task\\TaskController@store',        ['manager']],
    ['PUT',    '/api/tasks/:id',         'Task\\TaskController@update',       ['manager']],
    ['DELETE', '/api/tasks/:id',         'Task\\TaskController@destroy',      ['manager']],
    ['PATCH',  '/api/tasks/:id/status',  'Task\\TaskController@updateStatus', ['manager', 'employee']],

    ['POST', '/api/tasks/:id/submit',  'Task\\TaskController@submit',  ['employee']],
    ['POST', '/api/tasks/:id/approve', 'Task\\TaskController@approve', ['manager']],
    ['POST', '/api/tasks/:id/reject',  'Task\\TaskController@reject',  ['manager']],

    /**
     * TASK ASSIGNMENT
     */
    ['POST', '/api/tasks/:id/assign', 'Task\\TaskAssignController@assign', ['manager']],

    /**
     * TASK ATTACHMENTS
     */
    ['POST', '/api/tasks/:id/attachments',    'Task\\TaskAttachmentController@upload',   ['manager', 'employee']],
    ['GET',  '/api/tasks/:id/attachments',    'Task\\TaskAttachmentController@list',     ['manager', 'employee', 'client']],
    ['GET',  '/api/attachments/:id/download', 'Task\\TaskAttachmentController@download', ['manager', 'employee', 'client']],

    /**
     * TASK ACTIVITY
     */
    ['GET', '/api/tasks/:id/activity', 'Task\\TaskActivityController@history', ['admin', 'manager', 'employee']],

    /**
     * NOTIFICATIONS
     */
    ['GET',   '/api/notifications',              'Core\\NotificationController@index',         ['admin', 'manager', 'employee']],
    ['GET',   '/api/notifications/unread',       'Core\\NotificationController@unread',        ['admin', 'manager', 'employee']],
    ['GET',   '/api/notifications/unread-count', 'Core\\NotificationController@unreadCount',   ['admin', 'manager', 'employee']],
    ['PATCH', '/api/notifications/read-all',     'Core\\NotificationController@markAllAsRead', ['admin', 'manager', 'employee']],
    ['PATCH', '/api/notifications/:id/read',     'Core\\NotificationController@markAsRead',    ['admin', 'manager', 'employee']],

    /**
     * ATTENDANCE & LEAVE
     */
    ['GET',   '/api/leaves',             'Payroll\\LeaveController@index',      ['admin', 'manager', 'employee']],
    ['GET',   '/api/admin/leaves',       'Payroll\\LeaveController@adminIndex', ['admin', 'manager']],
    ['POST',  '/api/leaves',             'Payroll\\LeaveController@store',      ['manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve', 'Payroll\\LeaveController@approve',    ['manager']],

    ['GET',  '/api/attendance',          'Payroll\\AttendanceController@index',    ['admin', 'manager', 'employee']],
    ['POST', '/api/attendance/checkin',  'Payroll\\AttendanceController@checkin',  ['manager', 'employee']],
    ['POST', '/api/attendance/checkout', 'Payroll\\AttendanceController@checkout', ['manager', 'employee']],
];