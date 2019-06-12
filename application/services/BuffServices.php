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
        if ($res === false) {
            return null;
        }
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
        if ($res === false) {
            return [];
        }
        foreach ($res as $key => $value) {
            $res[$key] = new BuffDAO($value);
        }
        return $res;
    }

    /**
     * @param int $userId
     * @param int $buffType
     * @param string $date
     * @return string
     * @throws CtxException|Exception
     */
    public static function makeBuff(int $userId, int $buffType, string $date)
    {
        $container = new BuffDAO();
        $container->userId = $userId;
        $container->buffType = $buffType;
        $container->finishTime = $date;

        try {
            $stmt = BuffQuery::insertBuff($container);

            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            $db = DB::getInstance()->getDBConnection();
            return $db->lastInsertId();
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                (new CtxException())->alreadyExistsBuff();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function refreshBuff(int $userId)
    {
        $container = new BuffDAO();
        $container->userId = $userId;

        BuffQuery::deleteBuffByUser($container);
    }
}
