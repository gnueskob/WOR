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
     * @throws CtxException
     */
    public static function setUserLastVisit(array $data)
    {
        $stmt = UserQuery::updateUserLastVisit($data);
        $cnt = $stmt->rowCount();
        if ($cnt === 0) {
            (new CtxException())->updateFail('setUserLastVisit');
        }
    }

    /**
     * @param array $data
     * @return mixed    false: there are no record, array: any selected record
     */
    public static function getHiveUserId(array $data)
    {
        $stmt = UserQuery::selectHiveUser($data);
        $res = $stmt->fetch();
        if ($res === false) {
            return -1;
        } else {
            return $res['user_id'];
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

            $stmt = UserQuery::insertUserPlatform($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount1');
            }
            $userId = $db->lastInsertId();
            $data['user_id'] = $userId;

            $stmt = UserQuery::insertUserPlatform($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount2');
            }

            $stmt = UserQuery::insertUserStatistics($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('registerNewAccount3');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('registerNewAccount');
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
        return $userId;
    }

    /**
     * @param array $data
     * @return bool|int
     * @throws CtxException
     */
    public static function setUserName(array $data)
    {
        try {
            $stmt = UserQuery::updateUserName($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('setUserName');
            }
            return true;
        } catch (PDOException $e) {
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
    public static function setUserTerritory(array $data): bool
    {
        try {
            $stmt = UserQuery::updateUserTerritory($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('setUserTerritory');
            }
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param array $data
     * @return mixed
     */
    public static function getUser(array $data)
    {
        $stmt = UserQuery::selectUser($data);
        $res = $stmt->fetch();
        if ($res === false) {
            return false;
        } else {
            return DB::trimColumn($res);
        }
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

            $stmt = UserQuery::updateUserResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('upgradeRequestUserCastle');
            }

            $stmt = UserQuery::insertUserCastleUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('upgradeRequestUserCastle');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('upgradeRequestUserCastle');
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
        $stmt = UserQuery::selectUserCastleUpgrade($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail('upgradeFinishUserCastle');
        }
        $toLevel = $res['to_level'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            $stmt = UserQuery::deleteUserCastleUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail('upgradeFinishUserCastle');
            }

            $data['upgrade'] = $toLevel;
            $stmt = UserQuery::updateUserCastleUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('upgradeFinishUserCastle');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('upgradeFinishUserCastle');
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

    public static function getUserInfo(array $data)
    {
        $stmt = UserQuery::selectUserInfo($data);
        $res = $stmt->fetch();
        if ($res === false) {
            return false;
        } else {
            return DB::trimColumn($res);
        }
    }
}
