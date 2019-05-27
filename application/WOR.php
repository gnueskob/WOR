<?php

namespace lsb\App;

use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\App\controller\User;

class WOR extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router
            ->use('', function (Context $ctx) {
                $ctx->res->setHeader('Access-Control-Allow-Origin', '*');
                $ctx->res->setHeader('Content-Type', 'application/json', 'charset=UTF-8');
                $ctx->next();
            })
            ->use('/user', new User());

        $router->get('/user/:id/:pw', function (Context $ctx) {
            $ctx->res->send();
        });
    }
}
