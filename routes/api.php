<?php

use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Controllers\NotificationController;


return [
    // AUTH (Public)
    ['POST', '/api/auth/login',    AuthController::class, 'login',    null],
    ['POST', '/api/auth/register', AuthController::class, 'register', null],

    // AUTH (Private)
    ['GET', '/api/auth/me', AuthController::class, 'me', ['admin', 'manager', 'employee', 'client']],

    // HRM - Thành (uncomment khi làm xong)
    // ['GET',    '/api/employees',     EmployeeController::class, 'index',   ['admin', 'manager']],
    // ['POST',   '/api/employees',     EmployeeController::class, 'store',   ['admin']],
    // ['GET',    '/api/employees/:id', EmployeeController::class, 'show',    ['admin', 'manager']],
    // ['PUT',    '/api/employees/:id', EmployeeController::class, 'update',  ['admin', 'manager']],
    // ['DELETE', '/api/employees/:id', EmployeeController::class, 'destroy', ['admin']],

    // TASK - Huy & Bảo (uncomment khi làm xong)
    ['GET',   '/api/tasks',                TaskController::class, 'index',        ['admin', 'manager', 'employee', 'client']],
    ['POST',  '/api/tasks',                TaskController::class, 'store',        ['admin', 'manager']],
    ['PUT',  '/api/tasks/{id}',                TaskController::class, 'update',        ['admin', 'manager']],
    ['PATCH', '/api/tasks/{id}/status',     TaskController::class, 'updateStatus', ['admin', 'manager', 'employee']],
    // ['POST',  '/api/tasks/:id/comments',   TaskController::class, 'addComment',   ['admin', 'manager', 'employee', 'client']],

    // NOTIFICATION - Bảo

    ['GET', '/api/notifications', NotificationController::class, 'index'],
    ['GET', '/api/notifications/unread', NotificationController::class, 'unread'],
    ['GET', '/api/notifications/unread-count', NotificationController::class, 'unreadCount'],
    ['PATCH', '/api/notifications/{id}/read', NotificationController::class, 'markAsRead'],

    // ATTENDANCE & LEAVE - Tiến (uncomment khi làm xong)
    // ['POST',  '/api/attendance/checkin',   AttendanceController::class, 'checkin', ['admin', 'manager', 'employee']],
    // ['POST',  '/api/leaves',               LeaveController::class,      'store',   ['admin', 'manager', 'employee']],
    // ['PATCH', '/api/leaves/:id/approve',   LeaveController::class,      'approve', ['admin', 'manager']],
];