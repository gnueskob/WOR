<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Request;

class Auth
{
    public static function isValid()
    {
        return function (Request $req): bool {
            $config = Config::getInstance();
            if ($config->getMode() === DEV) {
                return true;
            }

            if (empty($req->httpXAccessToken)) {
                return false;
            }

            // TODO: validation JWT

            return true;
        };
    }
}
