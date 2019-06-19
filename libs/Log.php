<?php

namespace lsb\Libs;

use lsb\Config\Config;
use lsb\Log\Scribe;
use lsb\Log\LocalLog;
use Exception;

define('SCRIBE', 'scribe');
define('LOCAL', 'localLog');
define('CATEGORY_EX', 'Exception');
define('CATEGORY_CTX_EX', 'CtxException');
define('CATEGORY_PDO_EX', 'PDOException');
define('CATEGORY_QRY_PERF', 'QueryPerformance');
define('CATEGORY_API_PERF', 'APIPerformance');

class Log extends Singleton implements ILog
{
    private $driver;

    protected function __construct()
    {
        parent::__construct();
        $conf = Config::getInstance()->getConfig('log');
        $dirverConf = $conf['driver'];
        switch ($dirverConf) {
            default:
            case SCRIBE:
                $this->driver = new Scribe($conf['scribe']);
                break;
            case LOCAL:
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
        $this->driver->addLog($category, $msg);
    }

    public function addExceptionLog(Exception $e)
    {
        try {
            $time = Timezone::getNowUTC();
        } catch (Exception $e) {
            $time = $e->getMessage();
        }
        $logMsg = [
            'time' => $time,
            'code' => $e->getCode(),
            'error' => $e->getMessage()
        ];
        $this->addLog(CATEGORY_EX, json_encode($logMsg));
    }

    public function addCtxExceptionLog(CtxException $e)
    {
        try {
            $time = Timezone::getNowUTC();
        } catch (Exception $e) {
            $time = $e->getMessage();
        }
        $logMsg = [
            'time' => $time,
            'code' => $e->getCode(),
            'error' => $e->getMessage(),
            'scode' => $e->getServerErrCode(),
            'smsg' => $e->getServerMsg()
        ];
        $this->addLog(CATEGORY_CTX_EX, json_encode($logMsg));
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
