<?php

$app->get('/', function (Request $requset, Response $response) {
    return $requset->getBody();
});

$app->post('/wor/User/info', function (Request $request, Response $response) {
    $data = $request->getBody();
    $response->send($data);
});
