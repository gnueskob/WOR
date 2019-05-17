<?php

namespace lsb\Utils;

use lsb\Config\Config;

class Dev
{
    public static function uprintArr($array, $str = null)
    {
        if (Config::getInstance()->getMode() === null) {
            return;
        }
        print("<pre>");
        print($str ? "[".$str."]\n" : null);
        print_r($array);
        print("</pre>");
    }

    public static function uprint($data, $str = null)
    {
        if (Config::getInstance()->getMode() === null) {
            return;
        }
        print("<pre>");
        print($str ? "[".$str."]\n" : null);
        print($data);
        print("</pre>");
    }

    public static function log($msg)
    {
        echo $msg;
    }
}
