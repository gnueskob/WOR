<?php

define('ROOT', __DIR__ . DIRECTORY_SEPARATOR);
define('VENDOR', ROOT . 'vendor' . DIRECTORY_SEPARATOR);

require VENDOR . 'autoload.php';

lsb\Config\Config::getConfig('dev');

$app = new lsb\App\App();
require('./application/Route.php');
$app->run();

//print("<pre>");
//print("Host: ".$_SERVER['HTTP_HOST']."\n");
//print("Request_URI: ".$_SERVER['REQUEST_URI']."\n");
//print_r($GLOBALS);
//$post_data = json_decode(file_get_contents('php://input'), true);
//print_r($post_data);
//print("</pre>");
