<?php

namespace lsb\Utils;

class Dev
{
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
