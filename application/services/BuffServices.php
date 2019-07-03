<?php

namespace lsb\App\services;

use lsb\App\models\BuffDAO;
use Exception;
use lsb\Libs\Plan;

class BuffServices
{
    /**
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public static function getBuffAttack(int $userId)
    {
        $buffs = BuffDAO::getBuffs($userId);

        Plan::getDataAll(PLAN_BUFF);

        $attackRatio = 0;
        foreach ($buffs as $buff) {
            list($attackIncrementRatio) = Plan::getBuffPower($buff->buffType);
            $attackRatio += $attackIncrementRatio;
        }

        return $attackRatio;
    }

    /**
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public static function getBuffDefense(int $userId)
    {
        $buffs = BuffDAO::getBuffs($userId);

        Plan::getDataAll(PLAN_BUFF);

        $defenseRatio = 0;
        foreach ($buffs as $buff) {
            list(, $attackIncrementRatio) = Plan::getBuffPower($buff->buffType);
            $defenseRatio += $attackIncrementRatio;
        }

        return $defenseRatio;
    }
}
