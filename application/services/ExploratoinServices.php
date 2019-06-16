<?php

namespace lsb\App\services;

use lsb\App\models\TerritoryDAO;
use lsb\App\models\TileDAO;
use lsb\App\query\TerritoryQuery;
use lsb\App\query\TileQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use lsb\Utils\Utils;
use PDOStatement;

class ExploratoinServices extends Services
{
    /**
     * @param PDOStatement $stmt
     * @return TileDAO
     * @throws Exception
     */
    private static function getTileDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TileDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return TileDAO[]
     * @throws Exception
     */
    private static function getTileDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new TileDAO($row);
        }
        return $res;
    }

    /**
     * @param PDOStatement $stmt
     * @return TerritoryDAO
     * @throws Exception
     */
    private static function getTerritoryDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TerritoryDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return TerritoryDAO[]
     * @throws Exception
     */
    private static function getTerritoriesDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new TerritoryDAO($row);
        }
        return $res;
    }

    /***********************************************************/

    /**
     * @param int $exploreId
     * @return TileDAO
     * @throws CtxException|Exception
     */
    public static function getTile(int $exploreId)
    {
        $dao = new TileDAO();
        $dao->exploreId = $exploreId;

        $stmt = TileQuery::qSelectTile($dao)->run();
        $tile = static::getTileDAO($stmt);
        CtxException::invalidTile($tile->isEmpty());
        return $tile;
    }

    /**
     * @param int $userId
     * @param int $tileId
     * @throws CtxException|Exception
     */
    public static function checkUserExploredTile(int $userId, int $tileId)
    {
        $dao = new TileDAO();
        $dao->userId = $userId;
        $dao->tileId = $tileId;

        $stmt = TileQuery::qSelectTileByUserAndTileId($dao)->run();
        $tile = static::getTileDAO($stmt);
        CtxException::notExploredYet($tile->isEmpty());
        CtxException::notExploredYet(!$tile->isExplored());
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @throws CtxException|Exception
     */
    public static function checkUserExploredTerritory(int $userId, int $territoryId)
    {
        $dao = new TerritoryDAO();
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;

        $stmt = TerritoryQuery::qSelectTerritoryByUserAndTerritoryId($dao)->run();
        $territory = static::getTerritoryDAO($stmt);
        CtxException::notExploredYet($territory->isEmpty());
        CtxException::notExploredYet(!$territory->isExplored());
    }

    /**
     * @param int $userId
     * @return TileDAO[]
     * @throws Exception|Exception
     */
    public static function getTilesByUser(int $userId)
    {
        $dao = new TileDAO();
        $dao->userId = $userId;

        $stmt = TileQuery::qSelectTileByUser($dao)->run();
        return static::getTileDAOs($stmt);
    }

    /**
     * @param int $exploreId
     * @return TerritoryDAO
     * @throws Exception
     */
    public static function getTerritory(int $exploreId)
    {
        $dao = new TerritoryDAO();
        $dao->exploreId = $exploreId;

        $stmt = TerritoryQuery::qSelectTerritory($dao)->run();
        $territory = static::getTerritoryDAO($stmt);
        CtxException::invalidTerritory($territory->isEmpty());
        return $territory;
    }

    /**
     * @param int $userId
     * @return TerritoryDAO[]
     * @throws Exception
     */
    public static function getTerritoriesByUser(int $userId)
    {
        $dao = new TerritoryDAO();
        $dao->userId = $userId;

        $stmt = TerritoryQuery::qSelectTerritoryByUser($dao)->run();
        return static::getTerritoriesDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $tileId
     * @param int $exploreUnitTime
     * @return int
     * @throws CtxException
     */
    public static function exploreTile(int $userId, int $tileId, int $exploreUnitTime)
    {
        $dao = new TileDAO();
        $dao->exploreId = null;
        $dao->userId = $userId;
        $dao->tileId = $tileId;
        $dao->exploreTime = Timezone::getCompleteTime($exploreUnitTime);

        // 타일 탐사 데이터 추가
        $stmt = TileQuery::qInsertTile($dao)->run();
        static::validateInsert($stmt);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @param int $exploreUnitTime
     * @return int
     * @throws CtxException
     */
    public static function exploreTerritory(int $userId, int $territoryId, int $exploreUnitTime, bool $skip = false)
    {
        $dao = new TerritoryDAO();
        $dao->exploreId = null;
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;
        $dao->exploreTime = Timezone::getCompleteTime($exploreUnitTime);

        // 영토 탐사 데이터 추가
        $stmt = TerritoryQuery::qInsertTerritory($dao)
            ->checkError([DUPLICATE_ERRORCODE])
            ->run();
        $err = static::validateInsert($stmt);
        if ($skip) {
            return null;
        } else {
            CtxException::alreadyExplored($err === DUPLICATE_ERRORCODE);
            return DB::getLastInsertId();
        }
    }

    /**************************************************************/

    /**
     * @param int $tileId
     * @throws CtxException
     */
    public static function checkTileAvailable(int $tileId)
    {
        list($tileClass) = Plan::getTileClass($tileId);
        CtxException::notUsedTile($tileClass === PLAN_TILE_TYPE_NOT_USED);
    }

    /**
     * @param int $territoryId
     * @throws CtxException
     */
    public static function checkTerritoryAvailable(int $territoryId)
    {
        list($territoryClass) = Plan::getTerritoryClass($territoryId);
        CtxException::notUsedTerritory($territoryClass === PLAN_TERRITORY_TYPE_NOT_USED);
    }

    /**
     * @param TileDAO $tile
     * @throws CtxException|Exception
     */
    public static function checkTileExplored(TileDAO $tile)
    {
        CtxException::notExploredYet(!$tile->isExplored());
    }

    /**
     * @param TerritoryDAO $territory
     * @throws CtxException|Exception
     */
    public static function checkTerritoryExplored(TerritoryDAO $territory)
    {
        CtxException::notExploredYet(!$territory->isExplored());
    }

    public static function getDistanceToTargetTile(int $tileId)
    {
        list($tileX, $tileY) = Plan::getTileLocation($tileId);
        list($centerX, $centerY) = UserServices::getCastleLocation();

        $dist = Utils::getDistance($tileX, $tileY, $centerX, $centerY);
        return $dist;
    }

    public static function getDistanceToTargetTerritory(int $territoryId, int $targetTerritoryId)
    {
        list($userX, $userY) = Plan::getTerritoryLocation($territoryId);
        list($targetX, $targetY) = Plan::getTerritoryLocation($targetTerritoryId);

        $dist = Utils::getDistance($userX, $userY, $targetX, $targetY);
        return $dist;
    }

    /*************************************************************/

    /**
     * @param int $userId
     * @return mixed
     * @throws Exception
     */
    public static function getTerritoryUsedManpower(int $userId)
    {
        $dao = new TerritoryDAO();
        $dao->userId = $userId;
        $dao->exploreTime = Timezone::getNowUTC();

        $countFetchName = "CNT";

        $stmt = TerritoryQuery::qCountExploringTerritoryByUser($dao, $countFetchName)->run();
        return $stmt->fetch()[$countFetchName];
    }
}
