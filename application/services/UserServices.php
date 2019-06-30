<?php

namespace lsb\App\services;

use lsb\App\models\UserDAO;
use lsb\Libs\CtxException AS CE;
use lsb\Libs\ErrorCode;
use lsb\App\query\UserQuery;
use Exception;
use lsb\Libs\Timezone;
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
        CE::check($user->isEmpty(), ErrorCode::INVALID_USER);

        // TODO: 인구 조율
        $usedManpower = UserServices::getUsedManpower($userId);
        $user->usedManpower = $usedManpower;
        $user->availableManpower = $user->manpower - $user->usedManpower;

        return $user;
    }

    /**
     * @param int $userId
     * @return mixed
     * @throws Exception
     */
    public static function getUserInfoWithManpower(int $userId)
    {
        $user = UserDAO::getUserInfo($userId);
        CE::check($user->isEmpty(), ErrorCode::INVALID_USER);

        $usedManpower = UserServices::getUsedManpower($userId);
        $user->usedManpower = $usedManpower;
        $user->availableManpower = $user->manpower - $user->usedManpower;

        return $user;
    }

    /**
     * @param int $territoryId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUserInfoByTerritory(int $territoryId)
    {
        $dao = new UserDAO();
        $dao->territoryId = $territoryId;

        $stmt = UserQuery::qSelectUserInfoByTerritory($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());
        return $user;
    }

    /*************************************************************************/

    // Change user data

    /**
     * @param int $userId
     * @param int $manpower
     * @param bool $pending
     * @throws CtxException
     */
    public static function useManpower(int $userId, int $manpower, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->manpower = $manpower;

        $query = UserQuery::qSubtractManpowerFromUserInfo($dao);
        static::validateUpdate($query, $pending);
    }

    /**
     * @param int $userId
     * @param int $manpower
     * @param bool $pending
     * @throws CtxException
     */
    public static function obtainManpower(int $userId, int $manpower, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->manpower = $manpower;

        $query = UserQuery::qAddManpowerFromUserInfo($dao);
        static::validateUpdate($query, $pending);
    }

    /**************************************************************************/

    // CHECK
    /**
     * @param UserDAO $user
     * @param int $neededManpower
     * @throws CtxException
     */
    public static function checkAvailableManpowerSufficient(UserDAO $user, int $neededManpower)
    {
        // 인력이 충분한지 검사
        $hasManpower = $user->hasSufficientAvailableManpower($neededManpower);
        CtxException::manpowerInsufficient(!$hasManpower);
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
        $exploreUsedManpower = ExploratoinServices::getTerritoryUsedManpower($userId);

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
        $castleDefense = Plan::getBuildingDefense(PLAN_BUILDING_ID_CASTLE, $user->currentCastleLevel);
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
