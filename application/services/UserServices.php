<?php

namespace lsb\App\services;

use lsb\App\controller\User;
use lsb\App\models\UserDAO;
use lsb\App\models\Utils;
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
        $data = ['user_id' => $userId];
        $stmt = UserQuery::selectUser($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
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
        $data = [
            'hive_id' => $hiveId,
            'hive_uid' => $hiveUid
        ];
        $stmt = UserQuery::selectUserByHive($data);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        } else {
            return new UserDAO($res);
        }
    }

    /**
     * @param array $data
     * @return UserDAO
     * @throws CtxException|Exception
     */
    public static function getUserInfo(array $data)
    {
        $stmt = UserQuery::selectUserInfo($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        } else {
            return new UserDAO($res);
        }
    }

    /**
     * @param UserDAO $user
     * @throws CtxException|Exception
     */
    public static function setUserLastVisit(UserDAO $user)
    {
        $data = Utils::getQueryParameters($user, UserDAO::$dbColumMap);
        $stmt = UserQuery::updateUserInfoByUserId($data);
        $cnt = $stmt->rowCount();
        if ($cnt === 0) {
            (new CtxException())->updateFail();
        }
    }

    /**
     * @param UserDAO $user
     * @return bool
     * @throws CtxException|Exception
     */
    public static function setUserName(UserDAO $user)
    {
        $data = Utils::getQueryParameters($user, UserDAO::$dbColumMap);
        try {
            $stmt = UserQuery::updateUserInfoByUserId($data);
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
     * @param UserDAO $user
     * @return bool
     * @throws CtxException|Exception
     */
    public static function setUserTerritory(UserDAO $user)
    {
        $data = Utils::getQueryParameters($user, UserDAO::$dbColumMap);
        try {
            $stmt = UserQuery::updateUserInfoByUserId($data);
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
     * @param UserDAO $user
     * @return int
     * @throws CtxException
     */
    public static function registerNewAccount(UserDAO $user): int
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // user_platform 테이블 레코드 추가
            $data = Utils::getQueryParameters($user, UserDAO::$dbColumMap);
            $stmt = UserQuery::insertUserPlatform($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount1');
            }
            $user->userId = $db->lastInsertId();
            $data = Utils::getQueryParameters($user, UserDAO::$dbColumMap);

            // user_info 테이블 레코드 추가
            $stmt = UserQuery::insertUserInfo($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount2');
            }

            // user_statistics 테이블 레코드 추가
            $stmt = UserQuery::insertUserStatistics($data);
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
     * @throws CtxException|Exception
     */
    public static function upgradeUserCastle(UserDAO $user)
    {
        $data = Utils::getQueryParameters($user, UserDAO::$dbColumMap);
        // 유저 자원 소모시키기 및 업그레이드 갱신
        $stmt = UserQuery::updateUserInfoByUserId($data);
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
     * @param array $data
     * @throws CtxException
     */
    public static function modifyUserResource(array $data)
    {
        $stmt = UserQuery::updateUserResource($data);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->updateFail('setUserTerritory');
        }
    }
}
