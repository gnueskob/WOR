<?php

namespace lsb\Libs;

use Exception;
use \Redis;
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

        $conf = Config::getInstance()->getConfig('redis');
        $host = $conf['host'];
        $port = $conf['port'];

        // TODO: 연결 실패시 재 시도 로직 추가
        try {
            $redis = new Redis();
            $redis->connect($host, $port, 1000);
            $this->redis = $redis;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}
//$redis = RedisInstance::getInstance()->getRedis();
//$key = "test:key1"; //키분류는 :(콜론)을 찍는게 일반적
//$value = $redis->get($key);
//echo "value : " . $value . "<br>";
