<?php

namespace lsb\App\services;

use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\query\UserQuery;
use Exception;
use PDOException;

class UserServices
{
    /**
     * @param int $userId
     * @return UserDAO
     * @throws CtxException|Exception
     */
    public static function getUser(int $userId)
    {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;

        $stmt = UserQuery::selectUser($userContainer);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        } else {
            return new UserDAO($res);
        }
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
        if ($res === false) {
            return null;
        }
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
        if ($res === false) {
            return null;
        }
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
     * @param int $userId
     * @param int $tacticalResource
     * @param int $foodResource
     * @param int $luxuryResource
     * @param int $castleLevel
     * @param string $upgradeTime
     * @throws CtxException|Exception
     */
    public static function upgradeUserCastle(
        int $userId,
        int $tacticalResource,
        int $foodResource,
        int $luxuryResource,
        int $castleLevel,
        string $upgradeTime
    ) {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;
        $userContainer->tacticalResource = $tacticalResource;
        $userContainer->foodResource = $foodResource;
        $userContainer->luxuryResource = $luxuryResource;
        $userContainer->castleLevel = $castleLevel;
        $userContainer->castleToLevel = $castleLevel + 1;
        $userContainer->upgradeTime = $upgradeTime;
        $userContainer->userId = $userId;

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
     * @param int $userId
     * @param int $tacticalResource
     * @param int $foodResource
     * @param int $luxuryResource
     * @throws CtxException|Exception
     */
    public static function modifyUserResource(
        int $userId,
        int $tacticalResource,
        int $foodResource,
        int $luxuryResource
    ) {
        $userContainer = new UserDAO();
        $userContainer->userId = $userId;
        $userContainer->tacticalResource = $tacticalResource;
        $userContainer->foodResource = $foodResource;
        $userContainer->luxuryResource = $luxuryResource;

        $stmt = UserQuery::updateUserInfoWithResource($userContainer);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->updateFail('setUserTerritory');
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
}
