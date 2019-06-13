<?php

namespace lsb\App\services;

use lsb\Libs\Plan;
use lsb\App\models\WeaponDAO;
use lsb\App\query\UserQuery;
use lsb\App\query\WeaponQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Timezone;
use PDOException;
use PDOStatement;

class WeaponServices extends Update
{
    /* @return WeaponDAO */
    protected static function getContainer()
    {
        return parent::getContainer();
    }

    protected static function getNewContainer()
    {
        return new WeaponDAO();
    }

    protected static function updateAll($container, $assign): PDOStatement
    {
        return WeaponQuery::updateBuildingAll(self::getContainer(), $assign);
    }

    public static function watchWeaponId(int $weaponId)
    {
        self::getContainer()->weaponId = $weaponId;
        return new self();
    }

    /**
     * @param int $weaponId
     * @return WeaponDAO
     * @throws Exception
     */
    public static function getWeapon(int $weaponId)
    {
        $container = new WeaponDAO();
        $container->weaponId = $weaponId;

        $stmt = WeaponQuery::selectWeapon($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WeaponDAO($res);
    }

    /**
     * @param int $userId
     * @return WeaponDAO[]|bool
     * @throws Exception
     */
    public static function getWeaponsByUser(int $userId)
    {
        $container = new WeaponDAO();
        $container->userId = $userId;

        $stmt = WeaponQuery::selectWeaponsByUser($container);
        $res = $stmt->fetchAll();
        $res = $res === false ? [] : $res;
        foreach ($res as $key => $value) {
            $res[$key] = new WeaponDAO($value);
        }
        return $res;
    }

    /**
     * @param WeaponDAO $container
     * @return int
     * @throws CtxException|Exception
     */
    public static function createWeapon(WeaponDAO $container)
    {
        $stmt = WeaponQuery::insertWeapon($container);
        CtxException::insertFail($stmt->rowCount() === 0);
        return DB::getLastInsertId();
    }

    /**
     * @param int $currentLevel
     * @param string $date
     * @return WeaponServices
     */
    public static function upgradeWeapon(int $currentLevel, string $date)
    {
        $container = self::getContainer();
        $container->level = $currentLevel;
        $container->toLevel = $currentLevel + 1;
        $container->upgradeTime = $date;
        $container->updateProperty(['level', 'toLevel', 'upgradeTime']);
        return new self();
    }

    /********************************************************************/

    /**
     * @param int $userId
     * @return float|int
     * @throws Exception
     */
    public static function getAttack(int $userId)
    {
        $weapons = self::getWeaponsByUser($userId);
        $planWeapon = Plan::getDataAll(PLAN_WEAPON);
        $attack = 0;
        foreach ($weapons as $weapon) {
            if ($weapon->createTime < Timezone::getNowUTC()) {
                continue;
            }
            $attack += $planWeapon[$weapon->weaponType] * $weapon->currentLevel;
        }
        return $attack;
    }
}
