<?php

namespace lsb\App\services;

use lsb\App\models\AllianceDAO;
use lsb\App\query\AllianceQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

class AllianceServices extends Services
{
    /**
     * @param PDOStatement $stmt
     * @return AllianceDAO
     * @throws Exception
     */
    public static function getAllianceDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new AllianceDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return AllianceDAO[]
     * @throws Exception
     */
    public static function getAllianceDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new AllianceDAO($row);
        }
        return $res;
    }

    /**************************************************************/

    /**
     * @param int $allianceId
     * @return AllianceDAO
     * @throws CtxException|Exception
     */
    public static function getAlliance(int $allianceId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = $allianceId;

        $stmt = AllianceQuery::qSelectAlliance($dao)->run();
        $alliance = static::getAllianceDAO($stmt);
        CtxException::invalidAlliance($alliance->isEmpty());
        return $alliance;
    }

    /**
     * @param int $allianceWaitId
     * @return AllianceDAO
     * @throws CtxException|Exception
     */
    public static function getAllianceWait(int $allianceWaitId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = $allianceWaitId;

        $stmt = AllianceQuery::qSelectAllianceWait($dao)->run();
        $alliance = static::getAllianceDAO($stmt);
        CtxException::invalidAlliance($alliance->isEmpty());
        return $alliance;
    }

    /**
     * @param int $userId
     * @return AllianceDAO[]
     * @throws Exception
     */
    public static function getWatingAllianceByUser(int $userId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;

        $stmt = AllianceQuery::qSelectWatingAlliances($dao)->run();
        return static::getAllianceDAOs($stmt);
    }

    /**
     * @param int $userId
     * @return AllianceDAO[]
     * @throws Exception
     */
    public static function getAcceptedAlliance(int $userId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;

        $stmt = AllianceQuery::qSelectAcceptedAlliances($dao)->run();
        return static::getAllianceDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return int
     * @throws CtxException|Exception
     */
    public static function requesetAlliance(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = null;
        $dao->userId = $userId;
        $dao->friendId = $friendId;
        $dao->createdTime = Timezone::getNowUTC();

        $stmt = AllianceQuery::qInsertAllianceWait($dao)
            ->checkError([DUPLICATE_ERRORCODE])
            ->run();
        $err = static::validateInsert($stmt);
        CtxException::alreadyRequestedAlliance($err === DUPLICATE_ERRORCODE);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return int
     * @throws CtxException|Exception
     */
    public static function acceptAlliance(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = null;
        $dao->userId = $userId;
        $dao->friendId = $friendId;
        $dao->createdTime = Timezone::getNowUTC();

        $stmt = AllianceQuery::qInsertAlliance($dao);
        static::validateInsert($stmt);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @throws CtxException
     */
    public static function cancelAlliance(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;
        $dao->friendId = $friendId;

        $stmt = AllianceQuery::qDeleteAlliance($dao)->run();
        static::validateDelete($stmt);
    }

    /**
     * @param int $allianceWaitId
     * @throws CtxException
     */
    public static function rejectAllianceWait(int $allianceWaitId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = $allianceWaitId;

        $stmt = AllianceQuery::qDeleteAllianceWait($dao)->run();
        static::validateDelete($stmt);
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @throws CtxException|Exception
     */
    public static function checkAllianceWithFriend(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;
        $dao->friendId = $friendId;

        $stmt = AllianceQuery::qSelectAllianceByUserAndFriend($dao)->run();
        $alliance = static::getAllianceDAO($stmt);
        CtxException::notAlliance($alliance->isEmpty());
    }
}
