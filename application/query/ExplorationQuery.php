<?php

namespace lsb\App\query;

use lsb\App\models\TerritoryDAO;
use lsb\App\models\TileDAO;
use lsb\Libs\DB;
use PDOStatement;

class ExplorationQuery
{
    public static function selectTile(TileDAO $tile)
    {
        $q = "
                SELECT *
                FROM tile
                WHERE explore_id = :explore_id;
            ";
        $p = [':explore_id' => $tile->exploreId];
        return DB::runQuery($q, $p);
    }

    public static function selectTilesByUser(TileDAO $tile)
    {
        $q = "
            SELECT *
            FROM tile 
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $tile->userId];
        return DB::runQuery($q, $p);
    }

    public static function selectTerritory(TerritoryDAO $territory)
    {
        $q = "
            SELECT *
            FROM territory 
            WHERE explore_id = :explore_id;
        ";
        $p = [':explore_id' => $territory->exploreId];
        return DB::runQuery($q, $p);
    }

    public static function selectTerritoriesByUser(TerritoryDAO $territory)
    {
        $q = "
            SELECT *
            FROM territory
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $territory->userId];
        return DB::runQuery($q, $p);
    }

    /**
     * @param TileDAO $tile
     * @return PDOStatement
     */
    public static function insertTile(TileDAO $tile)
    {
        $q = "
            INSERT INTO tile
            VALUE (
                   :explore_id,
                   :user_id,
                   :tile_id,
                   :explore_time
            );
        ";
        $p = [
            ':explore_id' => null,
            ':user_id' => $tile->userId,
            ':tile_id' => $tile->tileId,
            ':explore_time' => $tile->exploreTime
        ];
        return DB::runQuery($q, $p);
    }

    /*
    public static function insertTileExplore(array $d)
    {
        $q = "
            INSERT INTO tile_explore
            VALUE (
                   :explore_id,
                   :tile_id,
                   :explore_finish_time
            );
        ";
        $p = [
            ':explore_id' => $d['explore_id'],
            ':tile_id' => $d['tile_id'],
            ':explore_finish_time' => $d['explore_finish_time']
        ];
        return DB::runQuery($q, $p);
    }
    */

    /**
     * @param TerritoryDAO $territory
     * @return PDOStatement
     */
    public static function insertTerritory(TerritoryDAO $territory)
    {
        $q = "
            INSERT INTO territory
            VALUE (
                   :explore_id,
                   :user_id,
                   :territory_id,
                   :explore_time
            );
        ";
        $p = [
            ':explore_id' => null,
            ':user_id' => $territory->userId,
            ':territory_id' => $territory->territoryId,
            ':explore_time' => $territory->exploreTime
        ];
        return DB::runQuery($q, $p);
    }

    /*
    public static function insertTerritoryExplore(array $d)
    {
        $q = "
            INSERT INTO territory_explore
            VALUE (
                   :explore_id,
                   :territory_id,
                   :explore_finish_time
            );
        ";
        $p = [
            ':explore_id' => $d['explore_id'],
            ':territory_id' => $d['territory_id'],
            ':explore_finish_time' => $d['explore_finish_time']
        ];
        return DB::runQuery($q, $p);
    }*/

    /*
    public static function updateTileWithExploreTime(array $d)
    {
        $q = "
            UPDATE tile
            SET explore_time = :explore_time
            WHERE explore_id = :explore_id;
        ";
        $p = [
            ':explore_time' => $d['explore_time'],
            ':explore_id' => $d['explore_id']
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateTerritoryWithExploreTime(array $d)
    {
        $q = "
            UPDATE territory
            SET explore_time = :explore_time
            WHERE explore_id = :explore_id;
        ";
        $p = [
            ':explore_time' => $d['explore_time'],
            ':explore_id' => $d['explore_id']
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteTimeExplore(array $d)
    {
        $q = "
            DELETE FROM tile_explore
            WHERE explore_id = :explore_id
              AND explore_finish_time <= :explore_finish_time;
        ";
        $p = [
            ':explore_id' => $d['explore_id'],
            ':explore_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteTerritoryExplore(array $d)
    {
        $q = "
            DELETE FROM territory_explore
            WHERE explore_id = :explore_id
              AND explore_finish_time <= :explore_finish_time;
        ";
        $p = [
            ':explore_id' => $d['explore_id'],
            ':explore_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
    */
}
