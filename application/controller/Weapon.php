<?php

namespace lsb\App\controller;

use lsb\App\services\WeaponServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;

class Weapon extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $res = WeaponServices::selectUserWeapon($data);
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });
    }
}
