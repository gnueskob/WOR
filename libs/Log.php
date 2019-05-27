<?php

namespace lsb\Libs;

use lsb\Config\Config;
use lsb\Log\Scribe;
use lsb\Log\LocalLog;

define('SCRIBE', 'scribe');
define('LOCAL', 'localLog');

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
