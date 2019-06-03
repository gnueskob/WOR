<?php

namespace lsb\App\controller;

use lsb\App\services\BufServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;

class Buf extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            BufServices::deleteUserBuf($data);
            $res = BufServices::selectUserBuf($data);
            $ctx->res->body = $res;
            $ctx->res->send();
        });

        $router->post('/add/:user_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            $res = BufServices::insertUserBuf($data);
            if ($res !== true) {
                (new CtxException())->insertBufFail();
            }
            $ctx->res->send();
        });
    }
}
