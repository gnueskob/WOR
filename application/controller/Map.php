<?php

namespace lsb\App\controller;

use lsb\App\services\MapServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;

class Map extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;
        /**
         * {
         *      "success":  true,
         *      "res": [
         *          {"explore_id": .., "user_id": .., "tile_id": .., "finish_time": .., ..},
         *          {"explore_id": .., "user_id": .., "tile_id": .., "finish_time": .., ..},
         *          ..
         *      ]
         * }
         */
        $router->get('/tile/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $res = MapServices::selectUserTile($data);
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });

        /**
         * {
         *      "success":  true,
         *      "res": [
         *          {"explore_id": .., "user_id": .., "explore_id": .., "finish_time": .., ..},
         *          {"explore_id": .., "user_id": .., "explore_id": .., "finish_time": .., ..},
         *          ..
         *      ]
         * }
         */
        $router->get('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $res = MapServices::selectUserExploration($data);
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });
    }
}
