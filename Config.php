<?php

namespace lsb\Config;

use lsb\Libs\Singleton;

define('URL', 'http://127.0.0.1');
define('WOR', 'wor');
define('DEV', 'dev');

// TODO: DB connection conf

class Config extends Singleton
{
    private $mode = null;

    // DB connection config.
    private $dbConf = [];
    private $redisConf = [];

    protected function __construct()
    {
        parent::__construct();

        // set db connection config
        $conf = json_decode(file_get_contents('config.json'), true);
        $conf = $this->mode === DEV ? $conf[DEV] : $conf[DEV];

        $this->dbConf = $conf['db'];
        $this->redisConf = $conf['redis'];
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    public function getDBConfig()
    {
        return $this->dbConf;
    }

    public function getRedisConfig()
    {
        return $this->redisConf;
    }
}
