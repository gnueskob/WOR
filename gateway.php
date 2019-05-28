<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;
use lsb\Libs\Timezone;

$config = Config::getInstance();
$config->setMode(DEV);

//$app = new App();
//$app->use('/wor', new WOR());
//$app->post('/plan', function () {
//    echo "<pre>";
//    $handle = fopen($_FILES['building']['tmp_name'], 'r');
//    $data = fgetcsv($handle, ",");
//    print_r($data);
//    echo "</pre>";
//});
//$app->get('/phpinfo', function () {
//    phpinfo();
//});
//$app->run();

try {
    $now = new Timezone('Asia/Seoul', '2019-05-28 20:46:00');
    echo $now->getTime(). '</br>';
    echo $now->getUTC(). '</br>';
    echo $now->modify("+1 days");
} catch (Exception $e) {
    echo $e->getMessage();
}
