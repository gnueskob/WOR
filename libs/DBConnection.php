<?php

namespace lsb\Libs;

use PDO;
use PDOException;
use lsb\Config\Config;
use lsb\Utils\Dev;

class DB extends Singleton
{
    private static $db = null;

    protected function __construct()
    {
        parent::__construct();
        $config = Config::getInstance();
        list($dbms,
            $host,
            $port,
            $dbname,
            $charset,
            $user,
            $password) = $config->getDBConfig();

        $dsn = $dbms . ':' .
            'host=' . $host . ';' .
            'dbname=' . $dbname . ';' .
            'charset=' . $charset;

        try {
            $db = new PDO($dsn, $user, $password);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            DEV::log($e);
        }
    }
}
