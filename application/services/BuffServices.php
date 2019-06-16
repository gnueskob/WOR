<?php

namespace lsb\App\services;

use lsb\App\models\BuffDAO;
use lsb\App\query\BuffQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Timezone;
use PDOStatement;

class BuffServices extends Services
{
    /**
     * @param PDOStatement $stmt
     * @return BuffDAO
     * @throws Exception
     */
    public static function getBuffDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BuffDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return BuffDAO[]
     * @throws Exception
     */
    public static function getBuffDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new BuffDAO($row);
        }
        return $res;
    }

    /************************************************************/

    /**
     * @param int $buffId
     * @return BuffDAO
     * @throws Exception
     */
    public static function getBuff(int $buffId)
    {
        $dao = new BuffDAO();
        $dao->buffId = $buffId;

        $stmt = BuffQuery::qSelectBuff($dao)->run();
        $buff = static::getBuffDAO($stmt);
        CtxException::invalidBuff($buff->isEmpty());
        return $buff;
    }

    /**
     * @param int $userId
     * @return array
     * @throws Exception|Exception
     */
    public static function getBuffsByUser(int $userId)
    {
        $dao = new BuffDAO();
        $dao->userId = $userId;

        $stmt = BuffQuery::qSelectBuffByUser($dao)->run();
        return static::getBuffDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $buffType
     * @param int $finishUnitTime
     * @return int
     * @throws CtxException
     */
    public static function createBuff(int $userId, int $buffType, int $finishUnitTime)
    {
        $dao = new BuffDAO();
        $dao->buffId = null;
        $dao->userId = $userId;
        $dao->buffType = $buffType;
        $dao->finishTime = Timezone::getCompleteTime($finishUnitTime);

        $stmt = BuffQuery::qInsertBuff($dao)
            ->checkError([DUPLICATE_ERRORCODE])
            ->run();
        $err = static::validateInsert($stmt);
        CtxException::alreadyExistsBuff($err === DUPLICATE_ERRORCODE);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function refreshBuff(int $userId)
    {
        $dao = new BuffDAO();
        $dao->userId = $userId;

        BuffQuery::qDeleteExpiredBuff($dao)->run();
    }
}
