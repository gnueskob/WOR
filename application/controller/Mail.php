<?php

namespace lsb\App\controller;

use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;

class Mail extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 메일 확인
        });

        $router->post('/add/:target_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 메일 쓰기
        });

        $router->put('/accept', function (Context $ctx) {
            $data = $ctx->getBody();
            // 메일 받기
        });
    }
}
