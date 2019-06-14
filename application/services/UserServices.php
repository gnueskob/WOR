<?php

namespace lsb\App\services;

use lsb\App\controller\User;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\query\UserQuery as UQ;
use Exception;
use lsb\Libs\Timezone;
use PDOStatement;
use lsb\Libs\Plan;

class UserServices extends Services
{
    private static $queryContainer = null;

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

        $stmt = UQ::qSelectHiveUser($dao)->run();
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

        $stmt = UQ::qSelectHiveUser($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::alreadyRegistered(false === $user->isEmpty());
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function visit(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->lastVisit = Timezone::getNowUTC();

        $stmt = UQ::qUpdateUserSetLastVisit($dao)->run();
        static::validateUpdate($stmt);
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

        $stmt = UQ::selectUser($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());
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

        $stmt = UQ::qSelectUserInfo($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());
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

        $stmt = UQ::qSelectUserInfoByTerritory($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());
        return $user;
    }

    /**
     * @param int $userId
     * @param string $name
     * @throws CtxException
     */
    public static function rename(int $userId, string $name)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->name = $name;

        $stmt = UQ::qUpdateUserInfoSetName($dao)->run([DUPLICATE_ERRORCODE]);
        CtxException::alreadyUsedName($stmt === false);
        static::validateUpdate($stmt);
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @throws CtxException
     */
    public static function relocateTerritory(int $userId, int $territoryId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;

        $stmt = UQ::qUpdateUserInfoSetTerritoryId($dao)->run([DUPLICATE_ERRORCODE]);
        CtxException::alreadyUsedTerritory($stmt === false);
        static::validateUpdate($stmt);
    }

    /**
     * @param array $data
     * @return UserDAO
     * @throws CtxException|Exception
     */
    public static function registerNewAccount(array $data)
    {
        $dao = new UserDAO();
        $dao->hiveId = $data['hive_id'];
        $dao->hiveUid = $data['hive_uid'];
        $dao->registerDate = $data['register_date'];
        $dao->country = $data['country'];
        $dao->lang = $data['lang'];
        $dao->osVersion = $data['os_version'];
        $dao->appVersion = $data['app_version'];

        $dao->castleLevel = 1;
        $dao->castleToLevel = 1;
        $dao->upgradeTime = Timezone::getNowUTC();
        $dao->autoGenerateManpower = true;
        $dao->manpower = 10;
        $dao->appendedManpower = 0;
        $dao->tacticalResource = 0;
        $dao->foodResource = 0;
        $dao->luxuryResource = 0;
        $dao->lastVisit = $data['last_visit'];

        $dao->warRequest = 0;
        $dao->warVictory = 0;
        $dao->warDefeated = 0;
        $dao->despoilDefenseSuccess = 0;
        $dao->despoilDefenseFail = 0;
        $dao->boss1KillCount = 0;
        $dao->boss2KillCount = 0;
        $dao->boss3KillCount = 0;

        DB::beginTransaction();
        // user_platform 테이블 레코드 추가
        $stmt = UQ::qInsertUserPlatform($dao)->run();
        static::validateInsert($stmt);

        $dao->userId = DB::getLastInsertId();

        // user_info 테이블 레코드 추가
        $stmt = UQ::qInsertUserInfo($dao)->run();
        static::validateInsert($stmt);

        // user_statistics 테이블 레코드 추가
        $stmt = UQ::qInsertUserStat($dao)->run();
        static::validateInsert($stmt);
        DB::endTransaction();

        return $dao;
    }

    /**
     * @param UserDAO $user
     * @param array $resource
     * @throws Exception
     */
    public static function checkUpgradePossible(UserDAO $user, array $resource)
    {
        // 이미 업그레이드 진행중 인지 검사
        CtxException::notUpgradedYet($user->isUpgrading());

        $neededTactical = $resource['need_tactical_resource'];
        $neededFood = $resource['need_food_resource'];
        $neededLuxury = $resource['need_luxury_resource'];

        // 필요한 재료를 가지고 있는 지 검사
        $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
        CtxException::resourceInsufficient(!$hasResource);
    }

    /**
     * @param UserDAO $user
     * @param array $resource
     * @throws Exception
     */
    public static function upgradeCastle(UserDAO $user, array $resource)
    {
        $dao = new UserDAO();
        $dao->userId = $user->userId;

        $dao->castleLevel = $user->currentCastleLevel;
        $dao->castleToLevel = $user->currentCastleLevel + 1;

        $dao->tacticalResource = $resource['need_tactical_resource'];
        $dao->foodResource = $resource['need_food_resource'];
        $dao->luxuryResource = $resource['need_luxury_resource'];

        // 업그레이드에 필요한 시간
        $castleUpgradeUnitTime = Plan::getData(PLAN_BUILDING, PLAN_BUILDING_ID_CASTLE)['upgrade_unit_time'];
        $dao->upgradeTime = Timezone::getCompleteTime($castleUpgradeUnitTime);

        $stmt = UQ::qUpdateUserInfoSetCastleLevel($dao)->run();
        static::validateUpdate($stmt);
    }

    /**
     * @param UserDAO $user
     * @throws Exception
     */
    public static function checkUpgradeFinished(UserDAO $user)
    {
        CtxException::invalidId($user->isEmpty());
        CtxException::notUpgradedYet(!$user->isUpgraded());
    }

    /**
     * @param int $tactical
     * @param int $food
     * @param int $luxury
     * @return UserServices
     */
    public static function modifyResource(array $data)
    {


        $container = self::getContainer();
        $container->tacticalResource =  $tactical;
        $container->foodResource = $food;
        $container->luxuryResource = $luxury;
        $container->updateProperty(['tacticalResource', 'foodResource', 'luxuryResource']);

        return new self();
    }

    /**
     * @param int $manpower
     * @param int $manpowerUsed
     * @param int $appendedManpower
     * @return UserServices
     */
    public static function modifyUserManpower(int $manpower, int $manpowerUsed, int $appendedManpower)
    {
        $container = self::getContainer();
        $container->manpower = $manpower;
        $container->manpowerUsed = $manpowerUsed;
        $container->appendedManpower = $appendedManpower;
        $container->updateProperty(['manpower', 'manpowerUsed', 'appendedManpower']);

        return new self();
    }

    /**************************************************************************/

    public static function getLocation(int $territoryId)
    {
        $territory = Plan::getData(PLAN_TERRITORY, $territoryId);
        return [$territory['location_x'], $territory['location_y']];
    }

    /**
     * @param int $territoryId
     * @return int
     * @throws Exception
     */
    public static function getTargetDefense(int $territoryId)
    {
        $targetUser = UserServices::getUserInfoByTerritory($territoryId);

        // 성 방어력
        $planCastle = Plan::getData(PLAN_UPG_CASTLE, $targetUser->currentCastleLevel);
        $castleDefense = $planCastle['defense'];

        // 방어탑 방어력
        $planTowers = Plan::getDataAll(PLAN_UPG_DEF_TOWER);
        $towerDefense = 0;
        $targetBuildings = BuildingServices::getBuildingsByUser($targetUser->userId);
        foreach ($targetBuildings as $building) {
            if ($building->buildingType !== PLAN_BUILDING_ID_TOWER || !$building->isDeployed()) {
                continue;
            }
            $towerDefense += $planTowers[$building->currentLevel]['defense'];
        }
        return $castleDefense + $towerDefense;
    }
}
