<?php

require(__DIR__ . '/vendor/autoload.php');

use lsb\Libs\RedisInstance;
use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;

$config = Config::getInstance();
$config->setMode(DEV);

//$app = new App();
//$app->use('/wor', new WOR());
//$app->run();

//$redis = RedisInstance::getInstance()->getRedis();
//$key = "test:key1"; //키분류는 :(콜론)을 찍는게 일반적
//$value = $redis->get($key);
//echo "value : " . $value . "<br>";

header("HTTP/1.1 203 aa");
