<?php

require(__DIR__ . '/vendor/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\controller\User;

$config = Config::getInstance();
$config->setMode(DEV);

$app = new App();
$app->use('/wor/user', new User());
$app->run();
