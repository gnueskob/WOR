<?php

namespace lsb\Libs;

use Exception;
use PDO;
use PDOStatement;
use lsb\Config\Config;

class DB extends Singleton
{
    private $db = null;
    private $transactionMode = 0;

    public const DUPLICATE_ERRORCODE = '23000';

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

        $db = new PDO($dsn, $conf['user'], $conf['password']);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $db->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, true);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
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

        $stmt = $this->db->prepare($query);
        $stmt->execute($param);

        $end = microtime(true);
        $elapsed = $end - $start;
        $logMsg = [
            'query' => $this->queryTrim($query, $param),
            'time' => $time,
            'elapsed' => $elapsed
        ];
        $log->addLog(Log::CATEGORY_QRY_PERF, json_encode($logMsg));

        return $stmt;
    }

    public static function runQuery(string $q, array $p)
    {
        return self::getInstance()->query($q, $p);
    }

    public static function getSelectResult(string $query, array $param, bool $all = false)
    {
        $dbMngr = self::getInstance();
        if ($all) {
            $res = $dbMngr->query($query, $param)->fetchAll();
            return $res;
        } else {
            return $dbMngr->query($query, $param)->fetch();
        }
    }


    public static function beginTransaction()
    {
        self::getInstance()->getDBConnection()->beginTransaction();
        self::getInstance()->transactionMode++;
    }

    /* @throws CtxException */
    public static function endTransaction()
    {
        if (self::getInstance()->transactionMode === 1) {
            $res = self::getInstance()->getDBConnection()->commit();
            CtxException::check($res === false, ErrorCode::TRANSACTION_FAIL);
        }
        self::getInstance()->transactionMode--;
    }

    public static function getTransactionMode()
    {
        return self::getInstance()->transactionMode;
    }

    public static function getLastInsertId()
    {
        return (int) (self::getInstance()->getDBConnection()->lastInsertId());
    }
}
