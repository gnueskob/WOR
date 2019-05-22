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
    private $conf;

    protected function __construct()
    {
        parent::__construct();

        // set db connection config
        $conf = json_decode(file_get_contents('config.json'), true);

        // TODO: DEV 모드 아닐 시 설정 불러오기
        $conf = $this->mode === DEV ? $conf[DEV] : $conf[DEV];

        $this->conf = $conf;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode($mode): void
    {
        $this->mode = $mode;
    }

    public function getConfig(string $key): array
    {
        return $this->conf[$key];
    }
}
