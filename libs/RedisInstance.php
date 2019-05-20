<?php

namespace lsb\Libs;

use Exception;
use Redis;
use lsb\Config\Config;

class RedisInstance extends Singleton
{
    private $redis;

    public function getRedis(): Redis
    {
        return $this->redis;
    }

    protected function __construct()
    {
        parent::__construct();

        $conf = Config::getInstance()->getRedisConfig();
        $host = $conf['host'];
        $port = $conf['port'];

        try {
            $redis = new Redis();
            $redis->connect($host, $port, 1000);
            $this->redis = $redis;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}
