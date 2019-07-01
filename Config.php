<?php

namespace lsb\Config;

use lsb\Libs\Singleton;

class Config extends Singleton
{
    public const URL = 'http://127.0.0.1';
    public const WOR = 'wor';
    public const DEV = 'dev';

    private $mode = Config::WOR;

    // DB connection config.
    private $conf;

    protected function __construct()
    {
        parent::__construct();

        // set db connection config
        $conf = json_decode(file_get_contents('config.json'), true);

        // TODO: DEV 모드 아닐 시 설정 불러오기
        $conf = $this->mode === Config::DEV ? $conf[Config::DEV] : $conf[Config::DEV];

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

    public function getConfig(string $key)
    {
        return $this->conf[$key];
    }
}
