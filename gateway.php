<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;

$config = Config::getInstance();
$config->setMode(Config::DEV);

$app = new App();
$app->use('/wor', new WOR());
$app->get('/server_info', function () {
    print_r($_SERVER);
});
$app->get('/plan', function () {
    $c = \lsb\Libs\Plan::getBuilding(\lsb\Libs\Plan::BUILDING_ID_ARMY);
    var_dump($c);
});
$app->get('/phpinfo', function () {
    phpinfo();
});
$app->get('/test', function (\lsb\Libs\Context $ctx) {
    $redis = \lsb\Libs\Redis::getInstance()->getRedis(\lsb\Libs\Redis::RANK);
    $key = "test:key1"; //키분류는 :(콜론)을 찍는게 일반적

    echo date('W');
    $value = $redis->get($key);
    echo "value : " . $value . "<br>";
});
$app->run();
