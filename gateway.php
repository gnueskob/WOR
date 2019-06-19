<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;
use lsb\Config\utils\Error;

$config = Config::getInstance();
$config->setMode(DEV);

$app = new App();
$app->use('', Error::errorHandler());
$app->use('/wor', new WOR());
$app->get('/server_info', function () {
    print_r($_SERVER);
});
$app->post('/plan', function () {
    echo "<pre>";
    $handle = fopen($_FILES['building']['tmp_name'], 'r');
    $data = fgetcsv($handle, ",");
    print_r($data);
    echo "</pre>";
});
$app->get('/phpinfo', function () {
    phpinfo();
});
$app->get('/test', function (\lsb\Libs\Context $ctx) {

    $mcd = \lsb\Libs\Memcached::getInstance()->getMemcached();
    $dao = new \lsb\App\models\UserDAO();
//    $mcd->add('test', $dao, 10);
    $c = $mcd->get('test');
    var_dump($c);
});
$app->run();
