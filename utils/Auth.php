<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Log;
use lsb\Libs\Context;
use lsb\Libs\CtxException;

class Auth
{
    public static function isValid(): callable
    {
        return function (Context $ctx): void {
            if (Config::getInstance()->getMode() === DEV) {
                $ctx->next();
                return;
            }

            if (!property_exists($ctx->req, 'httpXAccessToken')) {
                (new CtxException())->throwUnauthenticatedException();
                return;
            }

            // TODO: validation JWT
            // body에 들어오는 데이터를 key를 통해 암호화
            return;
        };
    }
}
