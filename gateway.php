<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;

$config = Config::getInstance();
$config->setMode(DEV);
$config->setLogMode(SCRIBE);

$app = new App();
$app->use('/wor', new WOR());
$app->get('/phpinfo', function () {
    phpinfo();
});
$app->run();
