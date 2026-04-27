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
    //get all comments 
$router->get('/api/tasks/comments', 'TaskCommentController@getAll');
    // get comment detail
$router->get('/api/tasks/comments/{id}', 'TaskCommentController@getById');
    // get by task 
$router->get('/api/tasks/{id}/comments', 'TaskCommentController@getByTask');
    // create task comment
$router->post('/api/tasks/{id}/comments', 'TaskCommentController@store');
    // update comment
$router->put('/api/tasks/comments/{id}', 'TaskCommentController@update');
    // delete comment
$router->delete('/api/tasks/comments/{id}', 'TaskCommentController@delete');

// 
$router->post('/api/tasks/{id}/submit', 'TaskApprovalController@submit');

$router->post('/api/tasks/{id}/approve', 'TaskApprovalController@approve');

$router->post('/api/tasks/{id}/reject', 'TaskApprovalController@reject');