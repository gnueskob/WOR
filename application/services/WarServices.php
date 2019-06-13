<?php

namespace lsb\App\services;

use lsb\App\models\BuildingDAO;
use lsb\App\models\WarDAO;
use lsb\App\query\WarQuery;
use Exception;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use PDOException;

class WarServices
{
    /**
     * @param int $warId
     * @return WarDAO|null
     * @throws Exception
     */
    public static function getWar(int $warId)
    {
        $container = new WarDAO();
        $container->warId = $warId;

        $stmt = WarQuery::selectWar($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WarDAO($res);
    }

    /**
     * @param int $userId
     * @return WarDAO|null
     * @throws Exception
     */
    public static function getWarByUser(int $userId)
    {
        $container = new WarDAO();
        $container->userId = $userId;

        $stmt = WarQuery::selectWarByUser($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WarDAO($res);
    }

    /**
     * @param WarDAO $container
     * @return string
     * @throws CtxException
     */
    public static function createWar(WarDAO $container)
    {
        try {
            $stmt = WarQuery::insertWar($container);
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

    /**********************************************************/

    public static function resolveWarResult(WarDAO $war)
    {
        $atk = $war->attack;
        $dfns = $war->targetDefense;
        $ratio = $atk / ($atk + $dfns);

        $armyManpower = json_decode($war->buildingList);

        if ($ratio < 0.5) { // 패배
            BuildingServices::resetBuildingsManpower($armyManpower);
        }

    }
}
