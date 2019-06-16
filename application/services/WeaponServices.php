<?php

namespace lsb\App\services;

use lsb\Libs\Plan;
use lsb\App\models\WeaponDAO;
use lsb\App\query\WeaponQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Timezone;

class WeaponServices extends Services
{

    /**
     * @param int $weaponId
     * @return WeaponDAO
     * @throws Exception
     */
    public static function getWeapon(int $weaponId)
    {
        $dao = new WeaponDAO();
        $dao->weaponId = $weaponId;

        $stmt = WeaponQuery::qSelectWeapon($dao)->run();
        $weapon = static::getWeaponDAO($stmt);
        CtxException::invalidWeapon($weapon->isEmpty());
        return $weapon;
    }

    /**
     * @param int $userId
     * @return WeaponDAO[]|bool
     * @throws Exception
     */
    public static function getWeaponsByUser(int $userId)
    {
        $dao = new WeaponDAO();
        $dao->userId = $userId;

        $stmt = WeaponQuery::qSelectWeaponsByUser($dao)->run();
        return static::getWeaponDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $weaponType
     * @param int $createUnitTime
     * @return int
     * @throws CtxException|Exception
     */
    public static function createWeapon(int $userId, int $weaponType, int $createUnitTime)
    {
        $dao = new WeaponDAO();
        $dao->weaponId = null;
        $dao->userId = $userId;
        $dao->weaponType = $weaponType;
        $dao->createTime = Timezone::getCompleteTime($createUnitTime);
        $dao->upgradeTime = null;
        $dao->level = 1;
        $dao->toLevel = 1;
        $dao->lastUpdate = Timezone::getNowUTC();

        $stmt = WeaponQuery::qInsertWeapon($dao)->run();
        static::validateInsert($stmt);
        return DB::getLastInsertId();
    }

    /**
     * @param int $weaponId
     * @param int $currentLevel
     * @param float $upgradeUnitTime
     * @param bool $pending
     * @throws CtxException
     */
    public static function upgradeWeapon(
        int $weaponId,
        int $currentLevel,
        float $upgradeUnitTime,
        bool $pending = false)
    {
        $dao = new WeaponDAO();
        $dao->weaponId = $weaponId;
        $dao->level = $currentLevel;
        $dao->toLevel = $currentLevel + 1;
        $dao->upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

        $query = WeaponQuery::qSetUpgradeFromWeapon($dao);
        static::validateUpdate($query, $pending);
    }

    /********************************************************************/

    // CHECK

    /**
     * @param WeaponDAO $weapon
     * @throws CtxException|Exception
     */
    public static function checkCreateFinished(WeaponDAO $weapon)
    {
        CtxException::notCreatedYet(!$weapon->isCreated());
    }

    /**
     * @param WeaponDAO $weapon
     * @throws CtxException|Exception
     */
    public static function checkUpgradeStatus(WeaponDAO $weapon)
    {
        CtxException::notCompletedPreviousJobYet($weapon->isUpgrading());
    }

    /**
     * @param WeaponDAO $weapon
     * @throws CtxException|Exception
     */
    public static function checkUpgradeFinished(WeaponDAO $weapon)
    {
        CtxException::notUpgradedYet(!$weapon->isUpgraded());
    }

    /********************************************************************/

    /**
     * @param int $userId
     * @return float|int
     * @throws Exception
     */
    public static function getAttackPower(int $userId)
    {
        Plan::getDataAll(PLAN_WEAPON);

        $attack = 0;
        $weapons = static::getWeaponsByUser($userId);
        foreach ($weapons as $weapon) {
            if (!$weapon->isCreated()) {
                continue;
            }
            $attack += Plan::getWeaponAttack($weapon->weaponType, $weapon->currentLevel);
        }
        return $attack;
    }
}
