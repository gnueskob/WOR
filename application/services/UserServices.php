<?php

namespace lsb\App\services;

use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\query\UserQuery;
use Exception;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use PDOException;

class UserServices
{
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
     * @throws CtxException|Exception
     */
    public static function setUserLastVisit(int $userId, string $date)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;
        $userContainer->lastVisit = $date;

        $stmt = UserQuery::updateUserInfoWithLastVisit($userContainer);
        $cnt = $stmt->rowCount();
        if ($cnt === 0) {
            (new CtxException())->updateFail();
        }
    }

    /**
     * @param int $userId
     * @param string $name
     * @return bool
     * @throws CtxException|Exception
     */
    public static function setUserName(int $userId, string $name)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;
        $userContainer->name = $name;

        try {
            $stmt = UserQuery::updateUserInfoWithName($userContainer);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }
            return true;
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @return bool
     * @throws CtxException|Exception
     */
    public static function setUserTerritory(int $userId, int $territoryId)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;
        $userContainer->territoryId = $territoryId;

        try {
            $stmt = UserQuery::updateUserInfoWithTerritory($userContainer);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }
            return true;
        } catch (PDOException $e) {
            // Unique key 중복 제한은 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param string $hiveId
     * @param int $hiveUid
     * @return int
     * @throws CtxException
     */
    public static function registerNewAccount(string $hiveId, int $hiveUid): int
    {
        $userContainer = new UserDAO();
        $userContainer->hiveId = $hiveId;
        $userContainer->hiveUid = $hiveUid;

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // user_platform 테이블 레코드 추가
            $stmt = UserQuery::insertUserPlatform($userContainer);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount1');
            }
            $userId = $db->lastInsertId();
            $userContainer->userId = $userId;

            // user_info 테이블 레코드 추가
            $stmt = UserQuery::insertUserInfo($userContainer);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount2');
            }

            // user_statistics 테이블 레코드 추가
            $stmt = UserQuery::insertUserStatistics($userContainer);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount3');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
        return $userId;
    }

    /**
     * @param UserDAO $user
     * @param int $neededTacticalResource
     * @param int $neededFoodResource
     * @param int $neededLuxuryResource
     * @param string $upgradeTime
     * @throws CtxException
     */
    public static function upgradeUserCastle(
        UserDAO $user,
        int $neededTacticalResource,
        int $neededFoodResource,
        int $neededLuxuryResource,
        string $upgradeTime
    ) {
        $userContainer = new UserDAO();
        $userContainer->userId = $user->userId;
        $userContainer->tacticalResource = $user->tacticalResource - $neededTacticalResource;
        $userContainer->foodResource = $user->foodResource - $neededFoodResource;
        $userContainer->luxuryResource = $user->luxuryResource - $neededLuxuryResource;
        $userContainer->castleLevel = $user->currentCastleLevel;
        $userContainer->castleToLevel = $user->currentCastleLevel + 1;
        $userContainer->upgradeTime = $upgradeTime;

        // 유저 자원 소모시키기 및 업그레이드 갱신
        $stmt = UserQuery::updateUserInfoWithUpgradeAndResource($userContainer);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->updateFail();
        }
    }

    /* not used
     * @param array $data
     * @throws CtxException
    public static function resolveUpgradeUserCastle(array $data)
    {
        // 성 업그레이드 정보 검색 후 업그레이드 레벨 가져오기
        $stmt = UserQuery::selectUserCastleUpgrade($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $toLevel = $res['to_level'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 성 업그레이드 job 삭제
            $stmt = UserQuery::deleteUserCastleUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 성 업그레이드 단계 갱신
            $data['upgrade'] = $toLevel;
            $stmt = UserQuery::updateUserInfoWithUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }*/

    /**
     * @param UserDAO $user
     * @param int $neededtacticalResource
     * @param int $neededFoodResource
     * @param int $neededLuxuryResource
     * @throws CtxException
     */
    public static function modifyUserResource(
        UserDAO $user,
        int $neededtacticalResource,
        int $neededFoodResource,
        int $neededLuxuryResource
    ) {
        $userContainer = new UserDAO();
        $userContainer->userId = $user->userId;
        $userContainer->tacticalResource = $user->tacticalResource - $neededtacticalResource;
        $userContainer->foodResource = $user->foodResource - $neededFoodResource;
        $userContainer->luxuryResource = $user->luxuryResource - $neededLuxuryResource;

        $stmt = UserQuery::updateUserInfoWithResource($userContainer);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->updateFail();
        }
    }

    /**
     * @param int $userId
     * @param int $manpowerUsed
     * @throws CtxException
     */
    public static function modifyUserUsedManpower(int $userId, int $manpowerUsed)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;
        $userContainer->manpowerUsed = $manpowerUsed;

        $stmt = UserQuery::updateUserInfoWithUsedManpower($userContainer);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->updateFail('setUserTerritory');
        }
    }

    /**************************************************************************/

    public static function getLocation(int $territoryId)
    {
        $territory = Plan::getData(PLAN_TERRITORY, $territoryId);
        return [$territory['location_x'], $territory['location_y']];
    }

    /**
     * @param int $territoryId
     * @param string $warTime
     * @return null
     * @throws Exception
     */
    public static function getTargetDefense(int $territoryId, string $warTime)
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
            if ($building->buildingType !== PLAN_BUILDING_ID_TOWER ||
                is_null($building->deployTime) ||
                $building->deployTime > $warTime) {
                continue;
            }
            $towerDefense += $planTowers[$building->currentLevel]['defense'];
        }
        return $castleDefense + $towerDefense;
    }
}
