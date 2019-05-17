<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Context;

class Auth
{
    public static function isValid()
    {
        return function (Context $ctx): bool {
            $config = Config::getInstance();
            if ($config->getMode() === DEV) {
                return true;
            }

            if (empty($ctx->req->httpXAccessToken)) {
                return false;
            }

            // TODO: validation JWT

            return true;
        };
    }
}
