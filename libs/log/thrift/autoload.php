<?php
$GLOBALS['THRIFT_ROOT'] = __DIR__;

require_once($GLOBALS['THRIFT_ROOT'] . '/Thrift.php');
require_once($GLOBALS['THRIFT_ROOT'] . '/packages/scribe/scribe.php');

function scribe_autoload($class_name)
{
    $namespace = explode('\\', $class_name);
    $class_name = $namespace[count($namespace) - 1];

    $directory_array = Array('/protocol', '/transport');
    foreach ($directory_array as $dir) {
        $file = $GLOBALS['THRIFT_ROOT'] . $dir . '/' . $class_name . '.php';
        if (file_exists($file)) {
            require_once($file);
        }
    }
}

spl_autoload_register('scribe_autoload');
