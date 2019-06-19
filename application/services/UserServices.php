<?php

namespace lsb\App\services;

use lsb\App\controller\Weapon;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\query\UserQuery;
use Exception;
use lsb\Libs\Timezone;
use lsb\Libs\Plan;

class UserServices extends Services
{
    /**
     * @param string $hiveId
     * @param int $hiveUid
     * @return UserDAO
     * @throws Exception
     */
    public static function checkHiveUserExists(string $hiveId, int $hiveUid)
    {
        $dao = new UserDAO();
        $dao->hiveId = $hiveId;
        $dao->hiveUid = $hiveUid;

        $stmt = UserQuery::qSelectHiveUser($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());

        return $user;
    }

    /**
     * @param string $hiveId
     * @param int $hiveUid
     * @throws Exception
     */
    public static function checkNewHiveUser(string $hiveId, int $hiveUid)
    {
        $dao = new UserDAO();
        $dao->hiveId = $hiveId;
        $dao->hiveUid = $hiveUid;

        $stmt = UserQuery::qSelectHiveUser($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::alreadyRegistered(false === $user->isEmpty());
    }

    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUser(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $stmt = UserQuery::jSelectUserFromAll($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());

        $usedManpower = static::getUsedManpower($userId);
        $user->usedManpower = $usedManpower;
        $user->availableManpower = $user->manpower - $user->usedManpower;

        return $user;
    }

    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUserInfo(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $stmt = UserQuery::qSelectUserInfo($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());
        return $user;
    }

    /**
     * @param int $userId
     * @return mixed
     * @throws Exception
     */
    public static function getUserInfoWithManpower(int $userId)
    {
        $user = static::getUserInfo($userId);

        $usedManpower = static::getUsedManpower($userId);
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
     * @param bool $pending
     * @throws CtxException|Exception
     */
    public static function visit(int $userId, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->lastVisit = Timezone::getNowUTC();

        $query = UserQuery::qSetLastVisitFromUserInfo($dao);
        static::validateUpdate($query, $pending);
    }


    /**
     * @param int $userId
     * @param string $name
     * @param bool $pending
     * @throws CtxException
     */
    public static function rename(int $userId, string $name, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->name = $name;

        $query = UserQuery::qSetNameFromUserInfo($dao)
            ->checkError([DUPLICATE_ERRORCODE]);
        $res = static::validateUpdate($query, $pending);
        CtxException::alreadyUsedName($res === DUPLICATE_ERRORCODE);
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @param bool $pending
     * @throws CtxException
     */
    public static function relocateTerritory(int $userId, int $territoryId, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;

        $query = UserQuery::qSetTerritoryIdFromUserInfo($dao)
            ->checkError([DUPLICATE_ERRORCODE]);
        $res = static::validateUpdate($query, $pending);
        CtxException::alreadyUsedTerritory($res === DUPLICATE_ERRORCODE);
    }

    /**
     * @param int $userId
     * @param int $currentCastleLevet
     * @param float $upgradeUnitTime
     * @param bool $pending
     * @throws CtxException
     */
    public static function upgradeCastle(
        int $userId,
        int $currentCastleLevet,
        float $upgradeUnitTime,
        bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $dao->castleLevel = $currentCastleLevet;
        $dao->castleToLevel = $currentCastleLevet + 1;

        $dao->upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

        $query = UserQuery::qUpdateUserInfoSetCastle($dao);
        static::validateUpdate($query, $pending);
    }

    /**
     * @param int $userId
     * @param int $neededTactical
     * @param int $neededFood
     * @param int $neededLuxury
     * @param bool $pending
     * @throws CtxException
     */
    public static function useResource(
        int $userId,
        int $neededTactical,
        int $neededFood,
        int $neededLuxury,
        bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $dao->tacticalResource = $neededTactical;
        $dao->foodResource = $neededFood;
        $dao->luxuryResource = $neededLuxury;

        $query = UserQuery::qSubtarctResourcesFromUserInfo($dao);
        static::validateUpdate($query, $pending);
    }

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
     * @param int $neededTactical
     * @param int $neededFood
     * @param int $neededLuxury
     * @param bool $pending
     * @throws CtxException
     */
    public static function obtainResource(
        int $userId,
        int $neededTactical,
        int $neededFood,
        int $neededLuxury,
        bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $dao->tacticalResource = $neededTactical;
        $dao->foodResource = $neededFood;
        $dao->luxuryResource = $neededLuxury;

        $query = UserQuery::qAddResourcesFromUserInfo($dao);
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

    /**
     * @param int $userId
     * @param bool $pending
     * @throws CtxException|Exception
     */
    public static function registerFriendAttackPower(int $userId, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        list(, $buildingAttack) = BuildingServices::getArmyManpowerAndAttack($userId);
        $weaponAttack = WeaponServices::getAttackPower($userId);

        $dao->friendAttack = $buildingAttack + $weaponAttack;

        $query = UserQuery::qSetFriendAttack($dao);
        static::validateUpdate($query, $pending);
    }

    /*
    public static function useAvailableManpower(int $userId, int $manpower, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $dao->manpowerUsed = $manpower;

        $query = UserQuery::qAddManpowerUsedFromUserInfo($dao);
        static::validateUpdate($query, $pending);
    }

    public static function freeUsedManpower(int $userId, int $usedManpower, bool $pending = false)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->manpowerUsed = $usedManpower;

        $query = UserQuery::qSubtractManpowerUsedFromUserInfo($dao);
        static::validateUpdate($query, $pending);
    }*/

    /************************************************************/

    /**
     * @param string $hiveId
     * @param int $hiveUid
     * @param string $country
     * @param string $lang
     * @param string $osVersion
     * @param string $appVersion
     * @return int
     * @throws CtxException|Exception
     */
    public static function createNewUserPlatform(
        string $hiveId,
        int $hiveUid,
        string $country,
        string $lang,
        string $osVersion,
        string $appVersion)
    {
        $dao = new UserDAO();
        $dao->userId = null;
        $dao->hiveId = $hiveId;
        $dao->hiveUid = $hiveUid;
        $dao->registerDate = Timezone::getNowUTC();
        $dao->country = $country;
        $dao->lang = $lang;
        $dao->osVersion = $osVersion;
        $dao->appVersion = $appVersion;

        // user_platform 테이블 레코드 추가
        $stmt = UserQuery::qInsertUserPlatform($dao)->run();
        static::validateInsert($stmt);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @throws CtxException|Exception
     */
    public static function createNewUserInfo(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->lastVisit = Timezone::getNowUTC();
        $dao->territoryId = null;
        $dao->name = null;
        $dao->castleLevel = 1;
        $dao->castleToLevel = 1;
        $dao->upgradeTime = null;
        $dao->penaltyFinishTime = null;
        $dao->autoGenerateManpower = true;
        $dao->manpower = 10;
        $dao->appendedManpower = 0;
        $dao->tacticalResource = 0;
        $dao->foodResource = 0;
        $dao->luxuryResource = 0;

        // user_info 테이블 레코드 추가
        $stmt = UserQuery::qInsertUserInfo($dao)->run();
        static::validateInsert($stmt);
    }

    /**
     * @param int $userId
     * @throws CtxException
     */
    public static function createNewUserStat(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->warRequest = 0;
        $dao->warVictory = 0;
        $dao->warDefeated = 0;
        $dao->despoilDefenseSuccess = 0;
        $dao->despoilDefenseFail = 0;
        $dao->boss1KillCount = 0;
        $dao->boss2KillCount = 0;
        $dao->boss3KillCount = 0;

        // user_statistics 테이블 레코드 추가
        $stmt = UserQuery::qInsertUserStat($dao)->run();
        static::validateInsert($stmt);
    }

    /**************************************************************************/

    // CHECK

    /**
     * @param UserDAO $user
     * @param int $neededTactical
     * @param int $neededFood
     * @param int $neededLuxury
     * @throws CtxException
     */
    public static function checkResourceSufficient(UserDAO $user, int $neededTactical, int $neededFood, int $neededLuxury)
    {
        // 필요한 재료를 가지고 있는 지 검사
        $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
        CtxException::resourceInsufficient(!$hasResource);
    }

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

    /**
     * @param UserDAO $user
     * @throws Exception
     */
    public static function checkUpgradeStatus(UserDAO $user)
    {
        // 이미 업그레이드 진행중 인지 검사
        CtxException::notCompletedPreviousJobYet($user->isUpgrading());
    }

    /**
     * @param UserDAO $user
     * @throws Exception
     */
    public static function checkUpgradeFinished(UserDAO $user)
    {
        CtxException::notUpgradedYet(!$user->isUpgraded());
    }

    /**
     * @param UserDAO $user
     * @param $maxLevel
     * @throws CtxException
     */
    public static function checkMaxLevelOver(UserDAO $user, $maxLevel)
    {
        CtxException::maxLevel($user->currentCastleLevel >= $maxLevel);
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
