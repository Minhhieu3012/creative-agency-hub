<?php

$router->get('/', function() {
    echo json_encode([
        "status" => "success",
        "message" => "API is running"
    ]);
});

// api to check server is running(debug)
$router->get('/api/test', 'HealthController@index');


// business API 
    // comments
$router->get('/api/tasks/{id}/comments', 'TaskCommentController@getByTask');
$router->post('/api/tasks/{id}/comments', 'TaskCommentController@store');

// 
$router->post('/api/tasks/{id}/submit', 'TaskApprovalController@submit');

$router->post('/api/tasks/{id}/approve', 'TaskApprovalController@approve');

$router->post('/api/tasks/{id}/reject', 'TaskApprovalController@reject');