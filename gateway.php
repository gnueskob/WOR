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
    $dbMngr = DB::getInstance();

    $qry = "SELECT *
FROM building b,
      building_upgrade bu
WHERE b.building_id = bu.building_id;";
    $qry = preg_replace('/\r\n/', ' ', $qry);
    $qry = preg_replace('/  /', '', $qry);
    $qry = preg_replace('/^ /', '', $qry);
    $qry = preg_replace('/ $/', '', $qry);

    $id = 111;

    try {
        $stmt = $dbMngr->query($qry, []);
        $res = $stmt->fetchAll();
        $res = ['a' => true, 'res' => $res];
        echo json_encode($res);
    } catch (Exception $e) {
        if ($e->getCode() === "23000") {
            echo 'hi';
        } else {
            throw $e;
        }
    }
});
$app->run();
