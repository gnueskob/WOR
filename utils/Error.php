<?php

namespace lsb\Config\utils;

use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\Log;

class Error
{
    public static function errorHandler(): callable
    {
        return function (Context $ctx) {
            try {
                $ctx->next();
                return;
            } catch (CtxException $e) {
                $log = Log::getInstance();
                $category = $e->getServerErrCode();
                $msg = $e->getServerMsg();
                $log->addLog($category, $msg);

                $errRes = [
                    'result' => 1,
                    'error' => $msg
                ];

                $ctx->res->body = $errRes;
                $ctx->res->send();

                // Don't throw Exception more
                // throw $e;
            }
        };
    }
}
