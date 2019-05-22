<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/scribe/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;
use lsb\Scribe\Scribe;

$config = Config::getInstance();
$config->setMode(DEV);

//$app = new App();
//$app->use('/wor', new WOR());
//$app->get('/phpinfo', function () {
//    phpinfo();
//});
//$app->run();
$s = Scribe::getInstance();

$msg[] = new LogEntry([
    'category' => 'php_test',
    'message' => 'This is php scribe Test!!'
]);
$s->log($msg);
