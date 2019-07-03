<?php

namespace lsb\App\services;

use lsb\Libs\Plan;
use lsb\App\models\WeaponDAO;
use Exception;

class WeaponServices
{
    /**
     * @param int $userId
     * @return float|int
     * @throws Exception
     */
    public static function getAttackPower(int $userId)
    {
        Plan::getDataAll(PLAN_WEAPON);

        $attack = 0;
        $weapons = WeaponDAO::getWeapons($userId);
        foreach ($weapons as $weapon) {
            if (!$weapon->isCreated()) {
                continue;
            }
            $attack += Plan::getWeaponAttack($weapon->weaponType, $weapon->currentLevel);
        }
        return $attack;
    }
}
