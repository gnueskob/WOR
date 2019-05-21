<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Context;
use lsb\Libs\CtxException;

class Auth
{
    public static function isValid()
    {
        return function (Context $ctx): bool {
            if (Config::getInstance()->getMode() === DEV) {
                $ctx->err->unauthenticatedHandler();
                return $ctx->next();
            }

            if (property_exists($ctx->req, 'httpXAccessToken')) {
                // TODO: do not pass the request
                return false;
            }

            // TODO: validation JWT

            return true;
        };
    }

    public static function errorHandler()
    {
        return function (Context $ctx): bool {
            try {
                return $ctx->next();
            } catch (CtxException $e) {
                throw $e;
            }
        };
    }
}
