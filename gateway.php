<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;
use lsb\Libs\DB;
use lsb\Libs\CtxException;

$config = Config::getInstance();
$config->setMode(DEV);

$app = new App();
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
    $userId = 1;
    $manpower = 10;
    \lsb\App\query\UserQuery::userPlatform()
        ->selectQurey()
        ->select(['userId'])
        ->whereEqual([
            'hiveId' => $userId,
            'hiveUid' => $userId
        ])->run();
});
$app->run();
