<?php

namespace lsb\App\services;

use lsb\App\controller\User;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\query\UserQuery;
use Exception;
use lsb\Libs\Plan;
use PDOStatement;

class UserServices extends Update
{
    /* @return UserDAO */
    protected static function getContainer()
    {
        return parent::getContainer();
    }

    protected static function getNewContainer()
    {
        return new UserDAO();
    }

    protected static function updateAll($container, $assign): PDOStatement
    {
        return UserQuery::updateUserInfoAll(self::getContainer(), $assign);
    }

    public static function watchUserId(int $userId)
    {
        self::getContainer()->userId = $userId;
        return new self();
    }

    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUser(int $userId)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;

        $stmt = UserQuery::selectUser($userContainer);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param $hiveId
     * @param $hiveUid
     * @return UserDAO|null
     * @throws Exception
     */
    public static function getUserByHive(string $hiveId, int $hiveUid)
    {
        $userContainer = new UserDAO();
        $userContainer->hiveId = $hiveId;
        $userContainer->hiveUid = $hiveUid;

        $stmt = UserQuery::selectUserPlatformByHive($userContainer);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param int $userId
     * @return UserDAO|null
     * @throws Exception
     */
    public static function getUserInfo(int $userId)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;

        $stmt = UserQuery::selectUserInfo($userContainer);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param int $territoryId
     * @return UserDAO|null
     * @throws Exception
     */
    public static function getUserInfoByTerritory(int $territoryId)
    {
        $userContainer = new UserDAO();
        $userContainer->territoryId = $territoryId;

        $stmt = UserQuery::selectUserInfoByTerritory($userContainer);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param int $userId
     * @param string $date
     * @return UserServices
     * @throws Exception
     */
    public static function setUserLastVisit(int $userId, string $date)
    {
        $container = self::getContainer();
        $container->userId = $userId;
        $container->lastVisit = $date;
        $container->updateProperty(['lastVisit']);
        return new self();
    }

    /**
     * @param string $name
     * @return UserServices
     */
    public static function setUserName(string $name)
    {
        $container = self::getContainer();
        $container->name = $name;
        $container->updateProperty(['name']);
        return new self();
    }

    /**
     * @param int $territoryId
     * @return UserServices
     */
    public static function setUserTerritory(int $territoryId)
    {
        $container = self::getContainer();
        $container->territoryId = $territoryId;
        $container->updateProperty(['territoryId']);
        return new self();
    }

    /**
     * @param string $hiveId
     * @param int $hiveUid
     * @return int
     * @throws CtxException|Exception
     */
    public static function registerNewAccount(string $hiveId, int $hiveUid): int
    {
        $userContainer = new UserDAO();
        $userContainer->hiveId = $hiveId;
        $userContainer->hiveUid = $hiveUid;

        DB::beginTransaction();
        // user_platform 테이블 레코드 추가
        $stmt = UserQuery::insertUserPlatform($userContainer);
        CtxException::insertFail($stmt->rowCount() === 0);

        $userId = DB::getLastInsertId();
        $userContainer->userId = $userId;

        // user_info 테이블 레코드 추가
        $stmt = UserQuery::insertUserInfo($userContainer);
        CtxException::insertFail($stmt->rowCount() === 0);

        // user_statistics 테이블 레코드 추가
        $stmt = UserQuery::insertUserStatistics($userContainer);
        CtxException::insertFail($stmt->rowCount() === 0);
        DB::endTransaction();

        return $userId;
    }

    /**
     * @param int $currentCastleLevel
     * @param string $upgradeTime
     * @return UserServices
     */
    public static function upgradeUserCastle(int $currentCastleLevel, string $upgradeTime)
    {
        $container = self::getContainer();
        $container->castleLevel = $currentCastleLevel;
        $container->castleToLevel = $currentCastleLevel + 1;
        $container->upgradeTime = $upgradeTime;
        $container->updateProperty(['castleLevel', 'castleToLevel', 'upgradeTime']);

        return new self();
    }

    /**
     * @param int $tactical
     * @param int $food
     * @param int $luxury
     * @return UserServices
     */
    public static function modifyUserResource(int $tactical, int $food, int $luxury)
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
