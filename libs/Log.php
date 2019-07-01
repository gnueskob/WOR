<?php

namespace lsb\Libs;

use lsb\Config\Config;
use lsb\Log\Scribe;
use lsb\Log\LocalLog;
use Exception;

class Log extends Singleton implements ILog
{
    public const SCRIBE = 'scribe';
    public const LOCAL = 'localLog';
    public const CATEGORY_EX = 'Exception';
    public const CATEGORY_FATAL = 'FatalError';
    public const CATEGORY_CTX_EX = 'CtxException';
    public const CATEGORY_PDO_EX = 'PDOException';
    public const CATEGORY_QRY_PERF = 'QueryPerformance';
    public const CATEGORY_API_PERF = 'APIPerformance';

    private $driver;

    protected function __construct()
    {
        parent::__construct();
        $conf = Config::getInstance()->getConfig('log');
        $dirverConf = $conf['driver'];
        switch ($dirverConf) {
            default:
            case Log::SCRIBE:
                $this->driver = new Scribe($conf['scribe']);
                break;
            case Log::LOCAL:
                $this->driver = new LocalLog($conf['localLog']);
                break;
        }
    }

    /**
     * Stack the log massages with category
     * @param string $category
     * @param string $msg
     */
    public function addLog(string $category, string $msg)
    {
        $date = date('Y-m-d');
        $category = "{$category}_{$date}";
        $this->driver->addLog($category, $msg);
    }

    /**
     * Write all of stacked log massages
     */
    public function flushLog()
    {
        $this->driver->flushLog();
    }

    /**
     * Write log message right now
     * @param string $category
     * @param string $msg
     */
    public function writeLog(string $category, string $msg)
    {
        $this->driver->writeLog($category, $msg);
    }
}
