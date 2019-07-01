<?php

namespace lsb\Utils;

use lsb\Libs\ErrorCode;
use PDOException;
use Exception;
use Throwable;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\Log;
use lsb\Libs\Timezone;

class Logger
{
    public static function APILogger(string $category = null): callable
    {
        return function (Context $ctx) use ($category) {
            $log = Log::getInstance();
            $logCategory = Log::CATEGORY_API_PERF;
            if (isset($category) && $category !== '') {
                $logCategory = Log::CATEGORY_API_PERF . "_{$category}";
            }

            $logMsg = [];
            $logMsg['method'] = $ctx->req->requestMethod;
            $logMsg['uri'] = $ctx->req->requestUri;

            $start = microtime(true);
            $logMsg['time'] = Timezone::getNowUTC();

            $token = $ctx->req->httpXAccessToken;
            $logMsg['session'] = is_null($token) ? '' : $token;

            try {
                $ctx->next();
                $end = microtime(true);
                $logMsg['elapsed_time'] = $end - $start;
                $logMsg['code'] = ErrorCode::FINE;
                $log->addLog($logCategory, json_encode($logMsg));
            } catch (Exception $e) {
                $logMsg['code'] = $e->getCode();
                $log->addLog($logCategory, json_encode($logMsg));
                throw $e;
            } finally {
                $log->flushLog();
            }
        };
    }

    public static function errorLogger(): callable
    {
        return function (Context $ctx) {
            $log = Log::getInstance();

            $logMsg = [];
            $logMsg['method'] = $ctx->req->requestMethod;
            $logMsg['uri'] = $ctx->req->requestUri;
            $logMsg['time'] = Timezone::getNowUTC();

            $token = $ctx->req->httpXAccessToken;
            $logMsg['session'] = is_null($token) ? '' : $token;

            try {
                $ctx->next();
            } catch (CtxException $e) {
                $logMsg['code'] = $e->errorCode;
                $logMsg['msg'] = $e->getMessage();
                $logMsg['class'] = $e->getTrace()[0]['class'];
                $logMsg['args'] = $ctx->req->body;
                $log->addLog(Log::CATEGORY_CTX_EX, json_encode($logMsg));
                throw $e;
            } catch (PDOException $e) {
                $logMsg['code'] = $e->getCode();
                $logMsg['msg'] = $e->getMessage();
                $logMsg['class'] = $e->getTrace()[0]['class'];
                $logMsg['args'] = $ctx->req->body;
                $log->addLog(Log::CATEGORY_PDO_EX, json_encode($logMsg));
                throw $e;
            } catch (Exception $e) {
                $logMsg['code'] = $e->getCode();
                $logMsg['msg'] = $e->getMessage();
                $logMsg['class'] = $e->getTrace()[0]['class'];
                $logMsg['args'] = $ctx->req->body;
                $log->addLog(Log::CATEGORY_EX, json_encode($logMsg));
                throw $e;
            } catch (Throwable $e) {
                $logMsg['code'] = $e->getCode();
                $logMsg['msg'] = $e->getMessage();
                $logMsg['class'] = $e->getTrace()[0]['class'];
                $logMsg['args'] = $ctx->req->body;
                $log->addLog(Log::CATEGORY_FATAL, json_encode($logMsg));
                throw $e;
            } finally {
                $log->flushLog();
            }
        };
    }
}
