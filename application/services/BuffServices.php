<?php

namespace lsb\App\services;

use lsb\App\models\BuffDAO;
use lsb\App\query\BuffQuery;
use lsb\Libs\CtxException;
use lsb\Libs\Timezone;
use lsb\Libs\DB;
use Exception;
use PDOException;

class BuffServices
{
    /**
     * @param int $buffId
     * @return BuffDAO
     * @throws Exception
     */
    public static function getBuff(int $buffId)
    {
        $container = new BuffDAO();
        $container->buffId = $buffId;

        $stmt = BuffQuery::selectBuffByUser($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BuffDAO($res);
    }

    /**
     * @param int $userId
     * @return array
     * @throws Exception|Exception
     */
    public static function getBuffsByUser(int $userId)
    {
        $container = new BuffDAO();
        $container->userId = $userId;

        $stmt = BuffQuery::selectBuffByUser($container);
        $res = $stmt->fetchAll();
        $res = $res === false ? [] : $res;
        foreach ($res as $key => $value) {
            $res[$key] = new BuffDAO($value);
        }
        return $res;
    }

    /**
     * @param BuffDAO $container
     * @return int
     * @throws CtxException
     */
    public static function createBuff(BuffDAO $container)
    {
        try {
            $stmt = BuffQuery::insertBuff($container);
            CtxException::insertFail($stmt->rowCount() === 0);
            return DB::getLastInsertId();
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return -1;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public static function refreshBuff(int $userId)
    {
        $container = new BuffDAO();
        $container->userId = $userId;

        BuffQuery::deleteBuffByUser($container);

        return true;
    }
}
