<?php

namespace lsb\Utils;

use lsb\Libs\Context;

class Performance
{
    public static function APITime(): callable
    {
        return function (Context $ctx) {
            $start = microtime(true);

            $ctx->next();

            $end = microtime(true);

            $apiMethod = $ctx->req->requestMethod;
            $uri = $ctx->req->requestUri;
            $time = $start - $end;

            $category = $apiMethod;
            $msg = "{$apiMethod} {$uri} => total time : {$time}";

            // TODO: 로그 라이브러리 적용
        };
    }
}
