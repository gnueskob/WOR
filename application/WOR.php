<?php

namespace lsb\App;

use lsb\App\controller\Plan;
use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\App\controller\User;

class WOR extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // common middleware
        $router
            ->use('', function (Context $ctx) {
                $ctx->res->setHeader('Access-Control-Allow-Origin', '*');
                $ctx->res->setHeader('Content-Type', 'application/json', 'charset=UTF-8');
                $ctx->next();
            });

        $router
            ->use('/user', new User())
            ->use('/plan', new Plan());
    }
}
