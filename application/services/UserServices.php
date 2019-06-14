<?php

namespace lsb\App\services;

use lsb\App\controller\User;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\query\UserQuery as UQ;
use Exception;
use PDOStatement;
use lsb\Libs\Plan;

class UserServices
{
    private static $queryContainer = null;

    /**
     * @param PDOStatement $stmt
     * @return UserDAO
     * @throws Exception
     */
    private static function getUserDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param array $data
     * @return UserDAO
     * @throws CtxException|Exception
     */
    public static function checkHiveUserExists(array $data)
    {
        $dao = new UserDAO();
        $dao->hiveId = $data['hiveId'];
        $dao->hiveUid = $data['hiveUid'];

        $stmt = UQ::qSelectHiveUser($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::invalidUser($user->isEmpty());

        return $user;
    }

    /**
     * @param array $data
     * @throws CtxException|Exception
     */
    public static function checkNewHiveUser(array $data)
    {
        $dao = new UserDAO();
        $dao->hiveId = $data['hiveId'];
        $dao->hiveUid = $data['hiveUid'];

        $stmt = UQ::qSelectHiveUser($dao)->run();
        $user = static::getUserDAO($stmt);
        CtxException::alreadyRegistered(false === $user->isEmpty());
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public static function visitUser(array $data)
    {
        $dao = new UserDAO();
        $dao->userId = $data['userId'];

        UQ::qUpdateUserLastVisit($dao)->run();
    }

    /**
     * @param array $data
     * @return UserDAO
     * @throws Exception
     */
    public static function getUser(array $data)
    {
        $dao = new UserDAO();
        $dao->userId = $data['userId'];

        $stmt = UQ::selectUser($dao)->run();
        return static::getUserDAO($stmt);
    }

    /**
     * @param array $data
     * @return UserDAO
     * @throws Exception
     */
    public static function getUserInfo(array $data)
    {
        $dao = new UserDAO();
        $dao->userId = $data['userId'];

        $stmt = UQ::qSelectUserInfo($dao)->run();
        return static::getUserDAO($stmt);
    }

    /**
     * @param array $data
     * @return UserDAO
     * @throws Exception
     */
    public static function getUserInfoByTerritory(array $data)
    {
        $dao = new UserDAO();
        $dao->territoryId = $data['territory_id'];

        $stmt = UQ::qSelectUserInfoByTerritory($dao)->run();
        return static::getUserDAO($stmt);
    }

    /**
     * @param array $data
     * @return UserServices
     * @throws Exception
     */
    public static function renameUser(array $data)
    {
        $dao = new UserDAO();
        $dao->userId = $data['user_id'];
        $dao->name = $data['name'];

        $stmt = UQ::qUpdateUserInfoSetName($dao);
        // TODO:
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
    public static function registerNewAccount(array $data): int
    {


        DB::beginTransaction();
        // user_platform 테이블 레코드 추가
        $stmt = UQ::insertUserPlatform($userContainer);
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
