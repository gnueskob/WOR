<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;
use lsb\Libs\DB;

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
$app->get('/test', function () {
    $qry = "SELECT *
            FROM building b, building_upgrade bu
            WHERE b.building_id = bu.building_id;";
//    $qry = "INSERT INTO building
//            VALUE (123, 123);";

    $p = [];
//    try {
//        $stmt = DB::runQuery($qry, $p);
//        var_dump($stmt->fe);
//    } catch (Exception $e) {
//        echo 'g';
//    }
    $p[0] = 'aa';
    $p['asd'] = 'asd';
    $p[3] = 'a';
    var_dump($p);
    unset($p[0]);
    var_dump($p);
});
$app->run();
