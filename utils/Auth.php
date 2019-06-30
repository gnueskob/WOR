<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Context;
use lsb\Libs\CtxException AS CE;
use lsb\Libs\Encrypt;
use lsb\Libs\ErrorCode;
use lsb\Libs\Memcached;

class Auth
{
    public static function sessionHandler(): callable
    {
        return function (Context $ctx): void {
            if (Config::getInstance()->getMode() === DEV) {
                $ctx->next();
                return;
            }

            $hasToken = property_exists($ctx->req, 'httpXAccessToken');
            CE::unauthenticatedException(false === $hasToken, ErrorCode::SESSION_INVALID);

            $key = Config::getInstance()->getConfig('encrypt')['tokenKey'];
            $cipherToken = $ctx->req->httpXAccessToken;
            $token = json_decode(Encrypt::decrypt($cipherToken, $key));

            $mcd = Memcached::getInstance()->getMemcached();

            $sessionKey = "session::user::{$token['hiveUid']}";
            $storedToken = $mcd->get($sessionKey);

            CE::unauthenticatedException($storedToken === false, ErrorCode::SESSION_EXPIRED);
            CE::unauthenticatedException($token === $storedToken, ErrorCode::SESSION_INVALID);

            $mcd->set($sessionKey, $token, 30 * 60);

            $ctx->next();
        };
    }

    public static function sessionGenerator(): callable
    {
        return function (Context $ctx): void {
            $token = [
                'hiveUid' => $ctx->res->body['user']['hive_uid'],
                'userId' => $ctx->res->body['user']['user_id']
            ];

            $key = Config::getInstance()->getConfig('encrypt')['tokenKey'];
            $cipherToken = Encrypt::encrypt(json_encode($token), $key);

            $ctx->res->setHeader('httpXAccessToken', $cipherToken);
        };
    }
}
