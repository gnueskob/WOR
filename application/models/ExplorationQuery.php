<?php

namespace lsb\App\models;

use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class ExplorationQuery
{
    public static function selectTile(array $d)
    {
        $q = "
                SELECT t.*, te.explore_finish_time
                FROM tile t
                    LEFT JOIN tile_explore te ON t.tile_id = te.tile_id 
                WHERE t.explore_id = :explore_id;
            ";
        $p = [':explore_id' => $d['explore_id']];
        return DB::runQuery($q, $p);
    }

    public static function selectTilesByUser(array $d)
    {
        $q = "
            SELECT t.*, te.explore_finish_time
            FROM tile t
                LEFT JOIN tile_explore te ON t.tile_id = te.tile_id 
            WHERE t.user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }

    public static function selectTerritory(array $d)
    {
        $q = "
            SELECT t.*, te.explore_finish_time
            FROM territory t
                LEFT JOIN territory_explore te ON t.territory_id = te.territory_id 
            WHERE t.explore_id = :explore_id;
        ";
        $p = [':explore_id' => $d['explore_id']];
        return DB::runQuery($q, $p);
    }

    public static function selectTerritoriesByUser(array $d)
    {
        $q = "
            SELECT t.*, te.explore_finish_time
            FROM territory t
                LEFT JOIN territory_explore te ON t.territory_id = te.territory_id 
            WHERE t.user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertTile(array $d)
    {
        $q = "
            INSERT INTO tile
            VALUE (
                   :explore_id,
                   :user_id,
                   :tile_id,
                   :explore_time,
                   :last_update
            );
        ";
        $p = [
            ':explore_id' => null,
            ':user_id' => $d['user_id'],
            ':tile_id' => $d['tile_id'],
            ':explore_time' => null,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

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

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertTerritory(array $d)
    {
        $q = "
            INSERT INTO territory
            VALUE (
                   :explore_id,
                   :user_id,
                   :territory_id,
                   :explore_time,
                   :last_update
            );
        ";
        $p = [
            ':explore_id' => null,
            ':user_id' => $d['user_id'],
            ':territory_id' => $d['territory_id'],
            ':explore_time' => null,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

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
    }

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

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
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

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
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
}
