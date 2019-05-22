<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Context;
use lsb\Libs\CtxException;

class Auth
{
    public static function isValid()
    {
        return function (Context $ctx): void {
            if (Config::getInstance()->getMode() === DEV) {
                $ctx->next();
                return;
            }

            if (!property_exists($ctx->req, 'httpXAccessToken')) {
                $ctx->err->throwUnauthenticatedError();
                return;
            }

            // TODO: validation JWT

            return;
        };
    }

    public static function errorHandler()
    {
        return function (Context $ctx) {
            try {
                $ctx->next();
                return;
            } catch (CtxException $e) {
                throw $e;
            }
        };
    }
}
