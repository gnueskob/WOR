<?php

namespace lsb\Config\libs;

use lsb\Libs\Redis;
use DateTime;

class Rank
{
    public const RESOURCE = 'resource';
    public const DAMAGE = 'damage';
    public const LOYALTY = 'loyalty';
    public const PAST = 'past_week';
    public const PRESENT = 'current_week';

    // TODO: 자원, 충성도, 레이드 데미지 [주간 누적]

    public static function flushRank()
    {
        $currentWeek = new DateTime();
        $redis = Redis::getInstance()->getRedis(Redis::RANK);

        // TODO: delete 2주 전 자료
        $expiredWeek = $redis->get(self::PAST);
        // $redis->del($keys); 사용

        // 저번 주 까지 사용한 rank key
        $previousWeek = $redis->get(self::PRESENT);
        $redis->set(self::PAST, $previousWeek);

        // 이번 주 사용할 rank key
        $redis->set(self::PRESENT, $currentWeek);
    }

    // TODO: 저번 주 사용한 rank 키를 통해 rank 정보 반환
}
