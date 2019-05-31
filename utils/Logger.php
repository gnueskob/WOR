<?php

namespace lsb\Utils;

use PDOException;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\Log;
use lsb\Libs\Timezone;

class Logger
{
    public static function APILogger(string $category = null): callable
    {
        return function (Context $ctx) use ($category) {
            // TODO: redis 실시간 API 성능 지표 갱신
            $log = Log::getInstance();
            $logCategory = CATEGORY_API_PERF;
            if (isset($category) && $category !== '') {
                $logCategory = CATEGORY_API_PERF . "_{$category}";
            }

            $logMsg = [];
            $logMsg['method'] = $ctx->req->requestMethod;
            $logMsg['uri'] = $ctx->req->requestUri;

            $start = microtime(true);
            $logMsg['time'] = Timezone::getNowUTC();
            $logMsg['start_time'] = $start;

            try {
                $ctx->next();
            } catch (CtxException $e) {
                $logMsg['code'] = $e->getCode();
                $logMsg['msg'] = $e->getMessage();
                $logMsg['error_code'] = $e->getServerErrCode();
                $logMsg['error_msg'] = $e->getServerMsg();
                $log->addLog($logCategory, json_encode($logMsg));
                throw $e;
            } catch (PDOException $e) {
                $logMsg['code'] = $e->getCode();
                $logMsg['msg'] = $e->getMessage();
                $log->addLog($logCategory, json_encode($logMsg));
                throw $e;
            }

            $end = microtime(true);
            $logMsg['end_time'] = $end;
            $logMsg['elapsed_time'] = $end - $start;
            $logMsg['code'] = 200;
            $log->addLog($logCategory, json_encode($logMsg));
        };
    }
}
