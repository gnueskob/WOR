<?php

namespace lsb\Config;

use lsb\Libs\Singleton;

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
define('URL', 'http://127.0.0.1');
define('WOR', 'wor');
define('DEV', 'dev');

// TODO: DB connection conf

class Config extends Singleton
{
    private $mode = null;

    // DB connection config.
    private $dbConf = array();

    protected function __construct($mode = null)
    {
        parent::__construct();
        $this->mode = $mode ?: DEV;

        // set db connection config
        $conf = json_decode(file_get_contents('db.json'), true);
        foreach ($conf as $key => $value) {
            $this->dbConf[$key] = $value;
        }
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getDBConfig()
    {
        return $this->dbConf;
    }
}
