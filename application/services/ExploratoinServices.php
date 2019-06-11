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
        if ($res === false) {
            return null;
        }
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
        if ($res === false) {
            return null;
        }
        return new TileDAO($res);
    }

    /**
     * @param int $userId
     * @return array
     * @throws Exception|Exception
     */
    public static function getTilesByUser(int $userId)
    {
        $container = new TileDAO();
        $container->userId = $userId;
        $stmt = ExplorationQuery::selectTilesByUser($container);
        $res = $stmt->fetchAll();
        if ($res === false) {
            return [];
        }
        foreach ($res as $key => $value) {
            $res[$key] = new TileDAO($value);
        }
        return $res;
    }

    /**
     * @param int $exploreId
     * @return TerritoryDAO
     * @throws CtxException|Exception
     */
    public static function getTerritory(int $exploreId)
    {
        $container = new TerritoryDAO();
        $container->exploreId = $exploreId;

        $stmt = ExplorationQuery::selectTerritory($container);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        }
        return new TerritoryDAO($res);
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @return TerritoryDAO|null
     * @throws Exception
     */
    public static function getTerritoryByUserAndTerritory(int $userId, int $territoryId)
    {
        $container = new TerritoryDAO();
        $container->userId = $userId;
        $container->territoryId = $territoryId;

        $stmt = ExplorationQuery::selectTerritoryByUserAndTerritory($container);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        }
        return new TerritoryDAO($res);
    }

    /**
     * @param int $userId
     * @return array|bool
     * @throws CtxException|Exception
     */
    public static function getTerritoriesByUser(int $userId)
    {
        $container = new TerritoryDAO();
        $container->userId = $userId;

        $stmt = ExplorationQuery::selectTerritoriesByUser($container);
        $res = $stmt->fetchAll();
        if ($res === false) {
            return [];
        }
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
        if ($stmt->rowCount() === 0) {
            (new CtxException())->insertFail();
        }
        $db = DB::getInstance()->getDBConnection();
        return $db->lastInsertId();
    }

    /*
    public static function resolveExploreTile(array $data)
    {
        // 기존 타일 탐사 정보 검색
        $stmt = ExplorationQuery::selectTile($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $exploreTime = $res['explore_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 탐사 job 삭제
            $stmt = ExplorationQuery::deleteTimeExplore($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 업그레이드 레벨 갱신
            $data['explore_time'] = $exploreTime;
            $stmt = ExplorationQuery::updateTileWithExploreTime($data);
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
    }*/

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
        if ($stmt->rowCount() === 0) {
            (new CtxException())->insertFail();
        }

        $db = DB::getInstance()->getDBConnection();
        return $db->lastInsertId();
    }

    /*
    public static function resolveExploreTerritory(array $data)
    {
        // 기존 영토 탐사 정보 검색
        $stmt = ExplorationQuery::selectTerritory($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $exploreTime = $res['explore_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 탐사 job 삭제
            $stmt = ExplorationQuery::deleteTerritoryExplore($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 탐사 시간 갱신
            $data['explore_time'] = $exploreTime;
            $stmt = ExplorationQuery::updateTerritoryWithExploreTime($data);
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
    }*/
}
