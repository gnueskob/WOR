<?php

namespace lsb\Utils;

use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\Log;

class Logger
{
    public static function APILogger(string $category = null): callable
    {
        return function (Context $ctx) use ($category) {
            // TODO: redis 실시간 API 성능 지표 갱신
            $log = Log::getInstance();
            $logCategory = 'performance';
            if (isset($category) && $category !== '') {
                $logCategory = "{$logCategory}_{$category}";
            }

            $logMsg = [];
            $logMsg['method'] = $ctx->req->requestMethod;
            $logMsg['uri'] = $ctx->req->requestUri;

            $start = microtime(true);
            $logMsg['start_time'] = $start;

            try {
                $ctx->next();
            } catch (CtxException $e) {
                $logMsg['result'] = $e->getCode();
                $logMsg['error'] = $e->getMessage();
                $log->addLog($logCategory, json_encode($logMsg));
                throw $e;
            }

            $end = microtime(true);
            $logMsg['end_time'] = $end;
            $logMsg['elapsed_time'] = $end - $start;
            $logMsg['result'] = 200;
            $log->addLog($logCategory, json_encode($logMsg));
        };
    }
}
