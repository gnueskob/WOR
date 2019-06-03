<?php

namespace lsb\App\controller;

use lsb\App\services\WarServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;

class War extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $res = WarServices::selectUserWar($data);
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });
    }
}
