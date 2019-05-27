<?php

namespace lsb\Libs;

use PDO;
use PDOException;
use lsb\Config\Config;
use lsb\Utils\Dev;

class DBConnection extends Singleton
{
    private $db = null;

    protected function __construct()
    {
        parent::__construct();
        $conf = Config::getInstance()->getConfig('db');
        $dataSourceName = [
            'host' => $conf['host'],
            'port' => $conf['port'],
            'dbname' => $conf['dbname'],
            'charset' => $conf['charset']
        ];

        $dsn = $conf['driver'] . ':';
        foreach ($dataSourceName as $key => $value) {
            $dsn = $dsn . "{$key}={$value};";
        }

        try {
            $db = new PDO($dsn, $conf['user'], $conf['password']);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db = $db;
        } catch (PDOException $e) {
            DEV::log($e);
        }
    }

    public function getDBConnection()
    {
        return $this->db;
    }
}
