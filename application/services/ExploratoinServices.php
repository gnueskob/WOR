<?php

namespace lsb\App\services;

use lsb\App\models\TerritoryDAO;
use lsb\App\models\TileDAO;
use lsb\App\query\UserQuery;
use lsb\App\query\ExplorationQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use PDOException;

class ExploratoinServices
{
    /**
     * @param int $exploreId
     * @return bool|TileDAO
     * @throws Exception
     */
    public static function getTile(int $exploreId)
    {
        $container = new TileDAO();
        $container->exploreId = $exploreId;

        $stmt = ExplorationQuery::selectTile($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TileDAO($res);
    }

    /**
     * @param int $userId
     * @param int $tileId
     * @return TileDAO|null
     * @throws Exception
     */
    public static function getTileByUserAndTile(int $userId, int $tileId)
    {
        $container = new TileDAO();
        $container->userId = $userId;
        $container->tileId = $tileId;

        $stmt = ExplorationQuery::selectTileByUserAndTile($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TileDAO($res);
    }

    /**
     * @param int $userId
     * @return TileDAO[]
     * @throws Exception|Exception
     */
    public static function getTilesByUser(int $userId)
    {
        $container = new TileDAO();
        $container->userId = $userId;
        $stmt = ExplorationQuery::selectTilesByUser($container);
        $res = $stmt->fetchAll();
        $res = $res === false ? [] : $res;
        foreach ($res as $key => $value) {
            $res[$key] = new TileDAO($value);
        }
        return $res;
    }

    /**
     * @param int $exploreId
     * @return TerritoryDAO
     * @throws Exception
     */
    public static function getTerritory(int $exploreId)
    {
        $container = new TerritoryDAO();
        $container->exploreId = $exploreId;

        $stmt = ExplorationQuery::selectTerritory($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TerritoryDAO($res);
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @return TerritoryDAO
     * @throws Exception
     */
    public static function getTerritoryByUserAndTerritory(int $userId, int $territoryId)
    {
        $container = new TerritoryDAO();
        $container->userId = $userId;
        $container->territoryId = $territoryId;

        $stmt = ExplorationQuery::selectTerritoryByUserAndTerritory($container);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TerritoryDAO($res);
    }

    /**
     * @param int $userId
     * @return TerritoryDAO[]
     * @throws Exception
     */
    public static function getTerritoriesByUser(int $userId)
    {
        $container = new TerritoryDAO();
        $container->userId = $userId;

        $stmt = ExplorationQuery::selectTerritoriesByUser($container);
        $res = $stmt->fetchAll();
        $res = $res === false ? [] : $res;
        foreach ($res as $key => $value) {
            $res[$key] = new TerritoryDAO($value);
        }
        return $res;
    }

    /**
     * @param int $userId
     * @param int $tileId
     * @param string $date
     * @return string
     * @throws CtxException
     */
    public static function exploreTile(int $userId, int $tileId, string $date)
    {
        $container = new TileDAO();
        $container->userId = $userId;
        $container->tileId = $tileId;
        $container->exploreTime = $date;

        // 타일 탐사 데이터 추가
        $stmt = ExplorationQuery::insertTile($container);
        CtxException::insertFail($stmt->rowCount() === 0);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @param string $date
     * @return string
     * @throws CtxException
     */
    public static function exploreTerritory(int $userId, int $territoryId, string $date)
    {
        $container = new TerritoryDAO();
        $container->userId = $userId;
        $container->territoryId = $territoryId;
        $container->exploreTime = $date;

        $stmt = ExplorationQuery::insertTerritory($container);
        CtxException::insertFail($stmt->rowCount() === 0);

        return DB::getLastInsertId();
    }
}
