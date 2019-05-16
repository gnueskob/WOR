<?php

namespace lsb\Config;

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
define('URL', 'http://127.0.0.1');
define('WOR', 'wor');
define('DEV_MODE', 'dev');

// TODO: DB connection conf


class Config
{
    private static $mode = null;

    public static function getConfig($mode = null)
    {
        static $instance = null;
        if ($instance === null) {
            self::$mode = $mode;
            $instance = new static();
        }
        return $instance;
    }

    public static function getMode()
    {
        return self::$mode;
    }
}
