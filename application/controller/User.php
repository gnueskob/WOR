<?php

namespace lsb\App\controller;

use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Timezone;

class User extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /**
         * User login
         * {
         *      "success":    true
         *      "res": {
         *          "user_id":    :user_id
         *          "token":      :token
         *      }
         * }
         */
        $router->put('/login', function (Context $ctx) {
            $data = $ctx->req->body;

            $res = UserServices::selectHiveUser($data);
            if ($res === false) {
                (new CtxException())->invaildUser();
            }
            $userId = $res['user_id'];

            $now = Timezone::getNowUTC();
            $data = array_merge($data, ['last_visit' => $now]);
            $res = UserServices::updateUserLastVisit($data);
            if ($res === 0) {
                (new CtxException())->invalidHiveId();
            }
            $ctx->res->body = [
                'user_id' => $userId,
                'token' => 'tokentokentoken'
            ];
            $ctx->res->send();
        });

        /**
         * User register
         * {
         *      "success":    true
         *      "res": {
         *          "user_id":    :user_id
         *          "token":      :token
         *      }
         * }
         */
        $router->post('/register', function (Context $ctx) {
            $body = $ctx->req->body;

            $res = UserServices::selectHiveUser($body);
            if ($res !== false) {
                (new CtxException())->alreadyRegistered();
            }

            $userId = UserServices::registerNewAccount($body);
            if ($userId === -1) {
                (new CtxException())->registerFail();
            }

            $ctx->res->body = [
                'user_id' => $userId,
                'token' => 'tokentokentoken'
            ];
            $ctx->res->send();
        });

        /**
         * Update user name
         * {
         *      "success":    true
         * }
         */
        $router->put('/name/:user_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            $res = UserServices::updateUserName($data);
            if ($res === false) {
                (new CtxException())->alreadyUsedName();
            } elseif ($res === 0) {
                (new CtxException())->invalidId();
            }

            $ctx->res->send();
        });

        /**
         * Update user territory_id
         * {
         *      "success":    true
         * }
         */
        $router->put('/territory/:user_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            $res = UserServices::updateUserTerritory($data);
            if ($res === false) {
                (new CtxException())->alreadyUsedName();
            } elseif ($res === 0) {
                (new CtxException())->invalidId();
            }

            $ctx->res->send();
        });

        /**
         * User information
         * {
         *      "success":  true,
         *      "res": {
         *          "user_id": ...
         *          "territory_id": ...
         *          "name": ...
         *          ...
         *      }
         * }
         */
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            $res = UserServices::selectUserInfo($data);
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });
    }
}
