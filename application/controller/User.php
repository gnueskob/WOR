<?php

namespace lsb\App\controller;

use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Utils\Auth;

class User extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->get(
            '/:id/:action',
            Auth::errorHandler(),
            Auth::isValid(),
            function (Context $ctx) {
                $data['url'] = $ctx->req->requestUri;
                $data['params'] = $ctx->req->getParams();
                $data['test'] = 'test';
                $ctx->res->body = json_encode($data);
                $ctx->next();
            }
        );

        $router->put('/:param', function (Context $ctx) {
            $data['url'] = $ctx->req->requestUri;
            $data['body'] = $ctx->req->getBody();
            $data['params'] = $ctx->req->getParams();
            $ctx->res->send($data);
        });

        $router->post('/info', function (Context $ctx) {
            $data = $ctx->req->getBody();
            $ctx->res->send($data);
        });
    }
}
