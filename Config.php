<?php

namespace lsb\Config;

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
define('URL', 'http://127.0.0.1');
define('WOR', 'wor');
define('CONTROLLER_PATH', 'lsb\App\controller\\');

// TODO: DB connection conf


class Config
{
    private static $instance = null;
    private static $mode = null;

    public static function getConfig($mode = null)
    {
        if (self::$instance === null) {
            self::$mode = $mode;

            self::$instance = new self();
        }
    }

    public static function uStrToCamelCase($str)
    {

    }

    public static function uprintArr($array, $str = null)
    {
        if (self::$mode === null) {
            return;
        }
        print("<pre>");
        print($str ? "[".$str."]\n" : null);
        print_r($array);
        print("</pre>");
    }

    public static function uprint($data, $str = null)
    {
        if (self::$mode === null) {
            return;
        }
        print("<pre>");
        print($str ? "[".$str."]\n" : null);
        print($data);
        print("</pre>");
    }
}
