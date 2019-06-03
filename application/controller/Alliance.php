<?php

namespace lsb\App\controller;

use lsb\App\services\AllianceServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;

class Alliance extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        $router->get('/info/:user_id/:action', function (Context $ctx) {
            $data = $ctx->req->getParams();
            switch ($data['action']) {
                default:
                case 'ally':
                    $res = AllianceServices::selectUserAlly($data);
                    break;
                case 'ally_request':
                    $res = AllianceServices::selectUserAllyReq($data);
                    break;
                case 'ally_response':
                    $res = AllianceServices::selectUserAllyRes($data);
                    break;
            }
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });
    }
}
