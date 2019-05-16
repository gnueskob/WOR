<?php

namespace lsb\Utils;

use lsb\Config\Config;
use lsb\Libs\Request;

class Auth
{
    public static function isValid()
    {
        return function (Request $req) {
            if (Config::getMode() === DEV_MODE) {
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
