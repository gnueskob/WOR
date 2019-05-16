<?php

use lsb\Libs\Router;
use lsb\Libs\Request;
use lsb\Libs\Response;
use lsb\Utils\Auth;

$userRouter = new Router();

$userRouter->get('/:id/:action', Auth::isValid(), function (Response $res) {
//    $data['url'] = $req->requestUri;
//    $data['params'] = $req->getParams();
    $data['test'] = 'test';
    $res->send($data);
});

$userRouter->put('/:param', function (Request $req, Response $res) {
    $data['url'] = $req->requestUri;
    $data['body'] = $req->getBody();
    $data['params'] = $req->getParams();
    $res->send($data);
});

$userRouter->post('/info', function (Request $req, Response $res) {
    $data = $req->getBody();
    $res->send($data);
});
