<?php

namespace lsb\App\services;

use lsb\App\models\TerritoryDAO;
use lsb\App\models\UserDAO;
use Exception;
use lsb\Libs\Plan;

class UserServices extends Services
{
    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getAllProperty(int $userId)
    {
        $user = UserDAO::getUser($userId);

        $usedManpower = UserServices::getUsedManpower($userId);
        $user->usedManpower = $usedManpower;
        $user->availableManpower = $user->manpower - $user->usedManpower;

        return $user;
    }

    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUserInfoWithManpower(int $userId)
    {
        $user = UserDAO::getUserInfo($userId);

        $usedManpower = UserServices::getUsedManpower($userId);
        $user->usedManpower = $usedManpower;
        $user->availableManpower = $user->manpower - $user->usedManpower;

        return $user;
    }

    /**************************************************************************/

    /**
     * @param int $userId
     * @return int|mixed
     * @throws Exception
     */
    public static function getUsedManpower(int $userId)
    {
        $buildingUsedManpower = BuildingServices::getUsedManpower($userId);

        $countExploringTerritory = TerritoryDAO::getCurrentExploringTerritoryNumber($userId);
        list(, , $territoryExploreManpower) = Plan::getUnitExplore();
        $exploreUsedManpower = $countExploringTerritory * $territoryExploreManpower;

        return $buildingUsedManpower + $exploreUsedManpower;
    }

    public static function getCastleLocation()
    {
        list($tileWidth, $tileHeight) = Plan::getUnitTileMaxSize();

        // 영내 가운데 위치 (짝수일시 수치상 더 작은 앞 칸)
        $centerX = (int) (($tileWidth - 1) / 2);
        $centerY = (int) (($tileHeight - 1) / 2);

        return [$centerX, $centerY];
    }

    /**
     * @param UserDAO $user
     * @return float|int|mixed
     * @throws Exception
     */
    public static function getTotalDefense(UserDAO $user)
    {
        // 성 방어력
        $castleDefense = Plan::getBuildingDefense(Plan::BUILDING_ID_CASTLE, $user->currentCastleLevel);
        // 방어탑 방어력
        $towerDefense = BuildingServices::getDefensePower($user->userId);
        $totalDefense = $castleDefense + $towerDefense;

        $buffDefense = BuffServices::getBuffDefense($user->userId);
        $totalDefense += $totalDefense * $buffDefense;

        return $totalDefense;
    }

    /**
     * @param UserDAO $user
     * @return array
     * @throws Exception
     */
    public static function getTotalAttackAndManpower(UserDAO $user)
    {
        // 병영에 등록된 총 병력, 공격력
        list($armyManpower, $armyAttack) = BuildingServices::getArmyManpowerAndAttack($user->userId);

        // 유저가 가지고 있는 무기 별 총 공격력
        $weaponAttack = WeaponServices::getAttackPower($user->userId);
        $totalAttackPower = $armyAttack + $weaponAttack;

        $buffAttack = BuffServices::getBuffAttack($user->userId);
        $totalAttackPower += $totalAttackPower * $buffAttack;

        return [$totalAttackPower, $armyManpower];
    }
}
