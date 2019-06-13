<?php

namespace lsb\App\query;

use lsb\App\models\BuildingDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use lsb\Utils\Utils;
use PDOStatement;

class BuildingQuery
{
    public static function selectBuilding(BuildingDAO $building)
    {
        $q = "
            SELECT *
            FROM building
            WHERE building_id = :building_id;
        ";
        $p = [':building_id' => $building->buildingId];
        return DB::runQuery($q, $p);
    }

    public static function selectBuildingsById(array $buildingIds)
    {
        $q = "
            SELECT *
            FROM building
            WHERE building_id IN (:building_ids);
        ";
        $p = [':building_id' => implode(',', $buildingIds)];
        return DB::runQuery($q, $p);
    }

    public static function selectBuildingsByUser(BuildingDAO $building)
    {
        $q = "
            SELECT *
            FROM building
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $building->userId];
        return DB::runQuery($q, $p);
    }

    public static function updateBuildingAll(BuildingDAO $building, bool $assign = false)
    {
        $set = Utils::makeSetClause($building, $assign);
        $q = "
            UPDATE building
            SET {$set}
            WHERE building_id = :building_id;
        ";
        $p = Utils::makeBindParameters($building);
        $p[':building_id'] = $building->buildingId;
        return DB::runQuery($q, $p);
    }

    /*
    public static function updateBuildingWithLevel(BuildingDAO $building)
    {
        $q = "
            UPDATE building
            SET level = :level,
                to_level = :to_level,
                upgrade_time = :upgrade_time
            WHERE building_id = :building_id;
        ";
        $p = [
            ':level' => $building->level,
            ':to_level' => $building->toLevel,
            ':upgrade_time' => $building->upgradeTime
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateBuildingWithCreateTime(array $d)
    {
        $q = "
            UPDATE building
            SET create_time = :create_time
            WHERE building_id = :building_id;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':create_time' => $d['create_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateBuildingWithDeployTimeAndManpower(BuildingDAO $building)
    {
        $q = "
            UPDATE building
            SET deploy_time = :deploy_time,
                manpower = :manpower
            WHERE building_id = :building_id;
        ";
        $p = [
            ':deploy_time' => $building->deployTime,
            ':manpower' => $building->manpower,
            ':building_id' => $building->buildingId
        ];
        return DB::runQuery($q, $p);
    }*/

    /**
     * @param BuildingDAO $building
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertBuilding(BuildingDAO $building)
    {
        $q = "
            INSERT INTO building
            VALUE (
                   :building_id,
                   :user_id,
                   :territory_id,
                   :tile_id,
                   :building_type,
                   :create_time,
                   :deploy_time,
                   :upgrade_time,
                   :upgrade,
                   :manpower,
                   :last_update
            );
        ";
        $p = [
            ':building_id' => null,
            ':user_id' => $building->userId,
            ':territory_id' => $building->territoryId,
            ':tile_id' => $building->tileId,
            ':building_type' => $building->buildingType,
            ':create_time' => $building->createTime,
            ':deploy_time' => null,
            ':upgrade_time' => null,
            ':upgrade' => 1,
            ':manpower' => 0,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
}
