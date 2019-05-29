<?php

namespace lsb\App\controller;

use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Utils\Auth;
use lsb\Utils\Logger;

class User extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->get('/login', function (Context $ctx) {
            $body = $ctx->req->body;
            $hiveId = $body['hive_id'];
            $hiveUid = $body['hive_uid'];
        });

        $router->put('/:param', function (Context $ctx) {
            $data['url'] = $ctx->req->requestUri;
            $data['body'] = $ctx->req->body;
            $data['params'] = $ctx->req->getParams();
            $ctx->res->send($data);
        });

        $router->post('/info', function (Context $ctx) {
            $data = $ctx->req->body;
            $ctx->res->body = $data;
            $ctx->res->send(true);
        });
    }
}
