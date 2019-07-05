<?php

namespace lsb\Libs;

use Exception;
use Redis as Rds;
use lsb\Config\Config;

class Redis extends Singleton
{
    public const PLAN = "plan";
    public const RANK = "rank";

    private $redisPlan;
    private $redisRank;

    public function getRedis(string $mode): Rds
    {
        if ($mode === static::PLAN) {
            return $this->redisPlan;
        } elseif ($mode === static::RANK) {
            return $this->redisRank;
        }
    }

    /**
     * Redis constructor.
     * @throws CtxException
     */
    protected function __construct()
    {
        parent::__construct();

        $conf = Config::getInstance()->getConfig('redis');
        $planHost = $conf[static::PLAN]['host'];
        $planPort = $conf[static::PLAN]['port'];

        $rankHost = $conf[static::RANK]['host'];
        $rankPort = $conf[static::RANK]['port'];

        // TODO: 연결 실패시 재 시도 로직 추가
        try {
            $redisPlan = new Rds();
            $redisPlan->connect($planHost, $planPort, 1000);
            $this->redisPlan = $redisPlan;

            $redisRank = new Rds();
            $redisRank->connect($rankHost, $rankPort, 1000);
            $this->redisRank = $redisRank;
        } catch (Exception $e) {
            CtxException::check(true, ErrorCode::REDIS_ERROR);
        }
    }
}
//$redis = RedisInstance::getInstance()->getRedis();
//$key = "test:key1"; //키분류는 :(콜론)을 찍는게 일반적
//$value = $redis->get($key);
//echo "value : " . $value . "<br>";
