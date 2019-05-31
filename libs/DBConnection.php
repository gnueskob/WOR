<?php

namespace lsb\Libs;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use lsb\Config\Config;

define('DUPLICATE_ERRORCODE', '23000');

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
            $db->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, true);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db = $db;
        } catch (PDOException $e) {
            $log = Log::getInstance();
            $log->addExceptionLog(CATEGORY_PDO_EX, $e);
        }
    }

    public function getDBConnection()
    {
        return $this->db;
    }

    private function queryTrim(string $query, array $param): string
    {
        $qry = preg_replace('/\r\n/', ' ', $query);
        $qry = preg_replace('/  /', '', $qry);
        $qry = preg_replace('/^ /', '', $qry);
        $qry = preg_replace('/ $/', '', $qry);
        foreach ($param as $key => $value) {
            $qry = preg_replace("/{$key}/", $value, $qry);
        }
        return $qry;
    }

    public function query(string $query, array $param): PDOStatement
    {
        $log = Log::getInstance();

        try {
            $time = Timezone::getNowUTC();
        } catch (Exception $e) {
            $time = $e->getMessage();
        }

        $start = microtime(true);

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($param);
        } catch (PDOException $e) {
            $logMsg = [
                'query' => $this->queryTrim($query, $param),
                'time' => $time,
                'code' => $e->getCode(),
                'error' => $e->getMessage()
            ];
            $log->addLog(CATEGORY_QRY_PERF, json_encode($logMsg));
            $log->addExceptionLog(CATEGORY_PDO_EX, $e);

            throw $e;
        }

        $end = microtime(true);
        $elapsed = $end - $start;
        $logMsg = [
            'query' => $this->queryTrim($query, $param),
            'time' => $time,
            'start' => $start,
            'end' => $end,
            'elapsed' => $elapsed
        ];
        $log->addLog(CATEGORY_QRY_PERF, json_encode($logMsg));

        return $stmt;
    }
}
