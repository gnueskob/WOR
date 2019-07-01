<?php

namespace lsb\Config\utils;

use Exception;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ErrorCode;
use PDOException;
use Throwable;

class Error
{
    public static function errorHandler(): callable
    {
        return function (Context $ctx): void {
            try {
                $ctx->res->setHeader('Access-Control-Allow-Origin', '*');
                $ctx->next();
                $ctx->res->body = ['success' => true, 'res' => $ctx->res->body];
            } catch (CtxException $e) {
                $ctx->res->body = ['success' => false, 'res' => ['code' => $e->errorCode]];
            } catch (PDOException $e) {
                $ctx->res->body = ['success' => false, 'res' => ['code' => ErrorCode::DB_ERROR]];
            } catch (Exception $e) {
                $ctx->res->body = ['success' => false, 'res' => ['code' => ErrorCode::UNKNOWN_EXCEPTION]];
            } catch (Throwable $e) {
                $ctx->res->body = ['success' => false, 'res' => ['code' => ErrorCode::FATAL_ERROR]];
            }
        };
    }
}
