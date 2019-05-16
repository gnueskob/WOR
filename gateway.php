<?php

require(__DIR__ . '/vendor/autoload.php');

use lsb\Config\Config;
use lsb\App\App;

$config = Config::getConfig(DEV_MODE);

$app = new App();
require(__DIR__ . '/application/Route.php');
$app->run();
