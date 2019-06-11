<?php

namespace lsb\App\services;

use lsb\App\models\WarDAO;
use lsb\App\query\WarQuery;
use Exception;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use PDOException;

class WarServices
{
    /**
     * @param int $userId
     * @return WarDAO|null
     * @throws Exception
     */
    public static function selectUserWar(int $userId)
    {
        $container = new WarDAO();
        $container->userId = $userId;

        $stmt = WarQuery::selectWarByUser($container);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        }
        return new WarDAO($res);
    }

    /**
     * @param WarDAO $container
     * @return string
     * @throws CtxException
     */
    public static function prepareWar(WarDAO $container)
    {
        try {
            $stmt = WarQuery::insertWar($container);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }
            $db = DB::getInstance()->getDBConnection();
            return $db->lastInsertId();
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                (new CtxException())->alreadyWarExists();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function refreshWarByUser(int $userId)
    {
        $container = new WarDAO();
        $container->userId = $userId;

        WarQuery::deleteWarByUser($container);
    }

    /**
     * @param int $territoryId
     * @throws Exception
     */
    public static function refreshWarByTerritory(int $territoryId)
    {
        $container = new WarDAO();
        $container->territoryId = $territoryId;

        WarQuery::deleteWarByTerritory($container);
    }
}
