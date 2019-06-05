<?php

namespace lsb\App\services;

use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\App\models\UserQuery;
use Exception;
use PDOException;

class UserServices
{
    /**
     * @param array $data
     * @return array
     * @throws CtxException
     */
    public static function getUser(array $data)
    {
        $stmt = UserQuery::selectUser($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        } else {
            return DB::trimColumn($res);
        }
    }

    /**
     * @param array $data
     * @return mixed    false: there are no record, array: any selected record
     */
    public static function getUserByHive(array $data)
    {
        $stmt = UserQuery::selectUserByHive($data);
        $res = $stmt->fetch();
        if ($res === false) {
            return -1;
        } else {
            return $res['user_id'];
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws CtxException
     */
    public static function getUserInfo(array $data)
    {
        $stmt = UserQuery::selectUserInfo($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        } else {
            return DB::trimColumn($res);
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function setUserLastVisit(array $data)
    {
        $stmt = UserQuery::updateUserInfoWithLastVisit($data);
        $cnt = $stmt->rowCount();
        if ($cnt === 0) {
            (new CtxException())->updateFail();
        }
    }

    /**
     * @param array $data
     * @return bool|int
     * @throws CtxException
     */
    public static function setUserName(array $data)
    {
        try {
            $stmt = UserQuery::updateUserInfoWithName($data);
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
     * @param array $data
     * @return bool
     * @throws CtxException
     */
    public static function setUserTerritory(array $data)
    {
        try {
            $stmt = UserQuery::updateUserInfoWithTerritory($data);
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
     * @param array $data
     * @return int      -1 : register failed, {number} : registered user_id
     * @throws Exception
     */
    public static function registerNewAccount(array $data): int
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // user_platform 테이블 레코드 추가
            $stmt = UserQuery::insertUserPlatform($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount1');
            }
            $userId = $db->lastInsertId();
            $data['user_id'] = $userId;

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
     * @param array $data
     * @return mixed
     * @throws CtxException
     */
    public static function upgradeUserCastle(array $data)
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 유저 자원 소모시키기
            $stmt = UserQuery::updateUserInfoWithResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            // 성 업그레이드 job 추가
            $stmt = UserQuery::insertUserCastleUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
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
    }

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
