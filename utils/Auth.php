<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Context;

class Auth
{
    public static function isValid()
    {
        return function (Context $ctx): void {
            $config = Config::getInstance();
            if ($config->getMode() === DEV) {
                $ctx->next();
                return;
            }

            if (property_exists($ctx->req, 'httpXAccessToken')) {
                // TODO: do not pass request
                return;
            }

            // TODO: validation JWT

            return;
        };
    }
}
