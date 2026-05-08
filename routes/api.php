<?php
/**
 * CREATIVE AGENCY HUB - API ROUTES
 *
 * Role baseline:
 * - Admin: quản trị hệ thống web, tài khoản, customer, manager, employee, cấu trúc tổ chức.
 * - Manager: quản lý project, task, employee trong phạm vi vận hành, chấm công/nghỉ phép.
 * - Employee: làm task, cập nhật tiến độ, chấm công, gửi nghỉ phép.
 * - Client: xem portal khách hàng.
 *
 * Format: [Method, Path, Handler, Roles]
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
     * HRM - ORGANIZATION
     */
    ['GET',  '/api/organization/data',            'OrganizationController@getOrgData',       ['admin', 'manager', 'employee']],
    ['POST', '/api/organization/store',           'OrganizationController@storeDepartment',  ['admin']],
    ['POST', '/api/organization/positions/store', 'OrganizationController@storePosition',    ['admin']],

    /**
     * HRM - EMPLOYEES
     */
    ['GET',    '/api/employees',     'HRM\\EmployeeController@index',   ['admin', 'manager']],
    ['POST',   '/api/employees',     'HRM\\EmployeeController@store',   ['admin']],
    ['GET',    '/api/employees/:id', 'HRM\\EmployeeController@show',    ['admin', 'manager', 'employee']],
    ['PUT',    '/api/employees/:id', 'HRM\\EmployeeController@update',  ['admin', 'manager', 'employee']],
    ['DELETE', '/api/employees/:id', 'HRM\\EmployeeController@destroy', ['admin']],

    ['POST', '/api/employees/:id/adjust-leave', 'HRM\\EmployeeController@adjustLeave',  ['admin', 'manager']],
    ['POST', '/api/employees/:id/avatar',       'HRM\\EmployeeController@uploadAvatar', ['admin', 'manager', 'employee']],

    /**
     * EMPLOYEE DOCUMENTS
     * Giữ API vì DB hiện tại có bảng employee_documents.
     * UI có thể ẩn nút upload, nhưng backend không xoá để tránh phá chức năng cũ.
     */
    ['GET',    '/api/employees/:id/documents',         'HRM\\EmployeeController@documents',        ['admin', 'manager', 'employee']],
    ['POST',   '/api/employees/:id/documents',         'HRM\\EmployeeController@uploadDocument',   ['admin', 'manager', 'employee']],
    ['GET',    '/api/employee-documents/:id/download', 'HRM\\EmployeeController@downloadDocument', ['admin', 'manager', 'employee']],
    ['DELETE', '/api/employee-documents/:id',          'HRM\\EmployeeController@deleteDocument',   ['admin', 'manager', 'employee']],

    /**
     * PROJECT
     * Admin chỉ xem tổng quan.
     * Manager mới được tạo/sửa/xoá project.
     */
    ['GET',    '/api/projects',     'ProjectController@index',  ['admin', 'manager']],
    ['GET',    '/api/projects/:id', 'ProjectController@show',   ['admin', 'manager']],
    ['POST',   '/api/projects',     'ProjectController@store',  ['manager']],
    ['PUT',    '/api/projects/:id', 'ProjectController@update', ['manager']],
    ['DELETE', '/api/projects/:id', 'ProjectController@delete', ['manager']],

    /**
     * TASK
     * Manager tạo/sửa/xoá/giao task.
     * Employee chỉ xem/cập nhật trạng thái task được giao.
     * Admin không tạo task trong workflow vận hành.
     */
    ['GET',    '/api/tasks',            'Task\\TaskController@index',        ['admin', 'manager', 'employee', 'client']],
    ['POST',   '/api/tasks',            'Task\\TaskController@store',        ['manager']],
    ['PUT',    '/api/tasks/:id',        'Task\\TaskController@update',       ['manager']],
    ['DELETE', '/api/tasks/:id',        'Task\\TaskController@destroy',      ['manager']],
    ['PATCH',  '/api/tasks/:id/status', 'Task\\TaskController@updateStatus', ['manager', 'employee']],

    /**
     * TASK APPROVAL
     */
    ['POST', '/api/tasks/:id/submit',  'Task\\TaskApprovalController@submit',         ['employee']],
    ['POST', '/api/tasks/:id/approve', 'Task\\TaskApprovalController@approve',        ['manager']],
    ['POST', '/api/tasks/:id/reject',  'Task\\TaskApprovalController@reject',         ['manager']],
    ['GET',  '/api/tasks/submit',      'Task\\TaskApprovalController@getReviewTasks', ['manager']],

    /**
     * TASK ASSIGNMENT
     */
    ['POST', '/api/tasks/:id/assign', 'Task\\TaskAssignController@assign', ['manager']],

    /**
     * TASK ATTACHMENTS
     */
    ['POST', '/api/tasks/:id/attachments',    'Task\\TaskAttachmentController@upload',   ['manager', 'employee']],
    ['GET',  '/api/tasks/:id/attachments',    'Task\\TaskAttachmentController@list',     ['manager', 'employee']],
    ['GET',  '/api/attachments/:id/download', 'Task\\TaskAttachmentController@download', ['manager', 'employee']],

    /**
     * TASK ACTIVITY
     */
    ['GET', '/api/tasks/:id/activity', 'Task\\TaskActivityController@history', ['admin', 'manager', 'employee']],

    /**
     * NOTIFICATIONS
     */
    ['GET',   '/api/notifications',              'Core\\NotificationController@index',       ['manager', 'employee']],
    ['GET',   '/api/notifications/unread',       'Core\\NotificationController@unread',      ['manager', 'employee']],
    ['GET',   '/api/notifications/unread-count', 'Core\\NotificationController@unreadCount', ['manager', 'employee']],
    ['PATCH', '/api/notifications/:id/read',     'Core\\NotificationController@markAsRead',  ['manager', 'employee']],

    /**
     * COMMENTS
     */
    ['GET',    '/api/tasks/comments',     'Task\\TaskCommentController@getAll',    ['manager']],
    ['GET',    '/api/tasks/comments/:id', 'Task\\TaskCommentController@getById',   ['manager', 'employee']],
    ['GET',    '/api/tasks/:id/comments', 'Task\\TaskCommentController@getByTask', ['manager', 'employee']],
    ['POST',   '/api/tasks/:id/comments', 'Task\\TaskCommentController@store',     ['manager', 'employee']],
    ['PUT',    '/api/tasks/comments/:id', 'Task\\TaskCommentController@update',    ['manager', 'employee']],
    ['DELETE', '/api/tasks/comments/:id', 'Task\\TaskCommentController@delete',    ['manager', 'employee']],

    /**
     * ATTENDANCE & LEAVE
     * Giữ chấm công và nghỉ phép.
     * Đã xoá API tính lương/export lương.
     */
    ['GET',   '/api/leaves',             'Payroll\\LeaveController@index',      ['admin', 'manager', 'employee']],
    ['GET',   '/api/admin/leaves',       'Payroll\\LeaveController@adminIndex', ['admin', 'manager']],
    ['POST',  '/api/leaves',             'Payroll\\LeaveController@store',      ['manager', 'employee']],
    ['PATCH', '/api/leaves/:id/approve', 'Payroll\\LeaveController@approve',    ['manager']],

    ['GET',  '/api/attendance',          'Payroll\\AttendanceController@index',    ['admin', 'manager', 'employee']],
    ['POST', '/api/attendance/checkin',  'Payroll\\AttendanceController@checkin',  ['manager', 'employee']],
    ['POST', '/api/attendance/checkout', 'Payroll\\AttendanceController@checkout', ['manager', 'employee']],
];