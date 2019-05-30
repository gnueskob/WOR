<?php

namespace lsb\App\controller;

use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\DBConnection;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\SpinLock;
use lsb\Utils\Auth;
use lsb\Utils\Logger;
use Exception;

class User extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->put('/login', function (Context $ctx) {
            $body = $ctx->req->body;

            $row = UserServices::findHiveUser($body);
            if ($row === false) {
                (new CtxException())->throwInvaildUserException();
            }
            $ctx->res->body = [
                'success' => true,
                'user_id' => $row['user_id'],
                'token' => 'tokentokentoken'
            ];
            $ctx->res->send(true);
        });

        $router->post('/register', function (Context $ctx) {
            $body = $ctx->req->body;

            $isRegistered = !!UserServices::findHiveUser($body);
            if ($isRegistered) {
                (new CtxException())->throwAlreadyRegisteredException();
            }

            $row = UserServices::registerNewAccount($body);
            if ($row === false) {
                (new CtxException())->throwRegisterException();
            }

            $ctx->res->body = [
                'success' => true,
                'user_id' => $row['user_id'],
                'token' => 'tokentokentoken'
            ];
            $ctx->res->send(true);
        });

        $router->post('/name', function (Context $ctx) {
            $body = $ctx->req->body;

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
