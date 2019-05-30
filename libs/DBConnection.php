<?php

namespace lsb\Libs;

use Exception;
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

    private function queryTrim(string $query): string
    {
        $qry = preg_replace('/\r\n/', ' ', $query);
        $qry = preg_replace('/  /', '', $qry);
        $qry = preg_replace('/^ /', '', $qry);
        $qry = preg_replace('/ $/', '', $qry);
        return $qry;
    }

    public function query(string $query, array $param)
    {
        $log = Log::getInstance();
        $category = 'QueryPerformance';

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
                'query' => $this->queryTrim($query),
                'param' => json_encode($param)
            ];
            $log->addLog(PDO_EX, json_encode($logMsg));
        }

        $end = microtime(true);
        $elapsed = $end - $start;

        $logMsg = [
            'query' => $this->queryTrim($query),
            'param' => json_encode($param),
            'time' => $time,
            'start' => $start,
            'end' => $end,
            'elapsed' => $elapsed
        ];
        $log->addLog(PDO_EX, json_encode($logMsg));
    }
}
