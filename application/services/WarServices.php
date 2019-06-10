<?php

namespace lsb\App\services;

use lsb\App\models\WarDAO;
use lsb\App\query\WarQuery;
use Exception;

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
