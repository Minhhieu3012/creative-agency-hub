<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public / Auth Pages
    |--------------------------------------------------------------------------
    */

    ['GET', '/', 'View\\PageController@redirectToLogin', null],

    ['GET', '/login', 'View\\PageController@login', null],
    ['GET', '/client-login', 'View\\PageController@clientLogin', null],


    /*
    |--------------------------------------------------------------------------
    | Internal Role Landing Pages
    |--------------------------------------------------------------------------
    | Admin / Manager / Employee có landing page riêng.
    | Admin không tham gia workflow project/task/employee vận hành.
    |--------------------------------------------------------------------------
    */

    ['GET', '/admin', 'View\\PageController@adminHome', ['admin']],
    ['GET', '/manager', 'View\\PageController@managerHome', ['manager']],
    ['GET', '/employee', 'View\\PageController@employeeHome', ['employee']],


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    ['GET', '/dashboard', 'View\\PageController@dashboard', ['manager']],


    /*
    |--------------------------------------------------------------------------
    | HRM Pages
    |--------------------------------------------------------------------------
    | Manager quản lý nhân sự vận hành.
    | Employee chỉ xem hồ sơ cá nhân.
    |--------------------------------------------------------------------------
    */

    ['GET', '/hrm/departments', 'View\\PageController@departments', ['manager']],
    ['GET', '/hrm/employees', 'View\\PageController@employees', ['manager']],
    ['GET', '/hrm/profile', 'View\\PageController@profile', ['manager', 'employee']],


    /*
    |--------------------------------------------------------------------------
    | Task / Project Pages
    |--------------------------------------------------------------------------
    | Manager tạo project/task.
    | Employee xem project/task được giao.
    |--------------------------------------------------------------------------
    */

    ['GET', '/projects', 'View\\PageController@projects', ['manager', 'employee']],
    ['GET', '/tasks/projects', 'View\\PageController@projects', ['manager', 'employee']],

    ['GET', '/kanban', 'View\\PageController@kanban', ['manager', 'employee']],
    ['GET', '/tasks/kanban', 'View\\PageController@kanban', ['manager', 'employee']],

    ['GET', '/gantt', 'View\\PageController@gantt', ['manager', 'employee']],
    ['GET', '/tasks/gantt', 'View\\PageController@gantt', ['manager', 'employee']],


    /*
    |--------------------------------------------------------------------------
    | Payroll / Leave Pages
    |--------------------------------------------------------------------------
    */

    ['GET', '/attendance', 'View\\PageController@attendance', ['manager', 'employee']],
    ['GET', '/payroll/attendance', 'View\\PageController@attendance', ['manager', 'employee']],

    ['GET', '/leave', 'View\\PageController@leaveRequest', ['manager', 'employee']],
    ['GET', '/payroll/leave-request', 'View\\PageController@leaveRequest', ['manager', 'employee']],

    ['GET', '/approvals', 'View\\PageController@managerApprovals', ['manager']],
    ['GET', '/payroll/manager-approvals', 'View\\PageController@managerApprovals', ['manager']],

    ['GET', '/payroll/summary', 'View\\PageController@payrollSummary', ['manager']],


    /*
    |--------------------------------------------------------------------------
    | Client Portal Pages
    |--------------------------------------------------------------------------
    */

    ['GET', '/client', 'View\\PageController@clientProjects', ['client']],
    ['GET', '/client/projects', 'View\\PageController@clientProjects', ['client']],
    ['GET', '/client/tasks', 'View\\PageController@clientTasks', ['client']],
    ['GET', '/client/support', 'View\\PageController@clientSupport', ['client']],


    /*
    |--------------------------------------------------------------------------
    | Admin Console Pages
    |--------------------------------------------------------------------------
    | Các page này sẽ được triển khai sâu ở phase Admin Console.
    |--------------------------------------------------------------------------
    */

    ['GET', '/admin/accounts', 'View\\PageController@adminAccounts', ['admin']],
    ['GET', '/admin/services', 'View\\PageController@adminServices', ['admin']],
    ['GET', '/admin/feedback', 'View\\PageController@adminFeedback', ['admin']],
    ['GET', '/admin/settings', 'View\\PageController@adminSettings', ['admin']],

];