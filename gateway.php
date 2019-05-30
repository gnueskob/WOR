<?php

require(__DIR__ . '/vendor/autoload.php');
// Load up all the thrift stuff
require(__DIR__ . '/libs/log/thrift/autoload.php');

use lsb\Config\Config;
use lsb\App\App;
use lsb\App\WOR;
use lsb\Libs\DBConnection;

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
    $db = DBConnection::getInstance()->getDBConnection();
    $qry = "
            UPDATE `building`
            SET `upgrade` = 112
            WHERE `building_id` = :id;
        ";
    $qry = preg_replace('/\r\n/', ' ', $qry);
    $qry = preg_replace('/  /', '', $qry);
    $qry = preg_replace('/^ /', '', $qry);
    $qry = preg_replace('/ $/', '', $qry);

    $stmt = $db->prepare($qry);
    $id = 2;
    $stmt->bindParam(':id', $id);

    try {
        $stmt->execute();
        $res = $stmt->fetch();
        var_dump($res);
    } catch (PDOException $e) {
        if ($e->getCode() === "23000") {
        } else {
            throw $e;
        }
    }
});
$app->run();
