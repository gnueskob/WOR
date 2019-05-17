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
    private $dbConf = array();

    protected function __construct()
    {
        parent::__construct();

        // set db connection config
        $conf = json_decode(file_get_contents('db.json'), true);
        foreach ($conf as $key => $value) {
            $this->dbConf[$key] = $value;
        }
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
}
